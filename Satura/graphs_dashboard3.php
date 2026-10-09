<?php
/*******************************************
DASHBOARD 3 - 3 VISUALES (REDISEÑO)
Archivo: graphs_dashboard3.php

#1: CRECIMIENTO ACUMULADO DE PERSONAS ALCANZADAS
    - AreaChart: suma acumulada de asistencia_total mes a mes

#2: MADUREZ ESPIRITUAL (mapeo_*, solo reportes de Coach id_actividad=1)
    - ColumnChart: promedio (escala 1-4) de cada área de madurez

ID MENU (permiso): 23
*******************************************/

/* =========================
   HELPERS
   ========================= */
function db_first_row(DBbase_Sql $db, $sql){
    $db->query($sql);
    if($db->num_rows() > 0){
        $db->next_record();
        return $db;
    }
    return null;
}
function req_num($key){
    return (isset($_REQUEST[$key]) && soloNumeros($_REQUEST[$key]) != "") ? soloNumeros($_REQUEST[$key]) : "";
}
function req_date_or_default($key, $default){
    if(isset($_REQUEST[$key]) && soloNumeros($_REQUEST[$key]) != ""){
        return eliminarInvalidos($_REQUEST[$key]);
    }
    $_REQUEST[$key] = $default;
    return $default;
}
function build_filtros_sat($idUsuario, $fechaInicial, $fechaFinal, $paisId){
    $base = "";

    if($idUsuario !== ""){
        $base .= " AND sat_reportes.idUsuario = '".$idUsuario."'";
    }
    if($fechaInicial !== ""){
        $base .= " AND sat_reportes.fechaReporte >= '".$fechaInicial."'";
    }
    if($fechaFinal !== ""){
        $base .= " AND sat_reportes.fechaReporte <= '".$fechaFinal."'";
    }
    if($paisId !== ""){
        $base .= " AND usuario_empresa.empresa_paisid = '".$paisId."'";
    }

    return $base;
}

/* =========================
   DB
   ========================= */
$mesesNom = array("No", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
$PSN  = new DBbase_Sql;
$PSN2 = new DBbase_Sql;

/* =========================
   AUTORIZACIÓN
   ========================= */
$sql = "SELECT idMenu
        FROM usuarios_menu_graphs
        WHERE idMenu = 23
          AND idUsuario = '".$_SESSION["id"]."'";
$PSN->query($sql);
if($PSN->num_rows() == 0){
    die("NO está autorizado a ver este dashboard.");
}

/* =========================
   FILTROS GLOBALES
   ========================= */
$fechaInicial = "2026-01-01";
$fechaFinal   = date("Y-m-d");

if(isset($_SESSION["perfil"]) && $_SESSION["perfil"] == 163){
    $_REQUEST["idUsuario"] = $_SESSION["id"];
}

$fechaInicial = req_date_or_default("fechaInicial", $fechaInicial);
$fechaFinal   = req_date_or_default("fechaFinal",   $fechaFinal);

$buscar_idUsuario = req_num("idUsuario");
$empresa_paisid   = req_num("empresa_paisid");

$sqlFiltroBase = build_filtros_sat($buscar_idUsuario, $fechaInicial, $fechaFinal, $empresa_paisid);

/* =========================
   #1: MADUREZ ESPIRITUAL (reportes de Coach, id_actividad = 1)
   ========================= */
$varErrorMadurez = 0;
$nombreMadurez = "MADUREZ ESPIRITUAL DE LOS GRUPOS";
$datosMadurez = [];

$camposMadurez = [
    'mapeo_oracion'       => 'Oración',
    'mapeo_companerismo'  => 'Compañerismo',
    'mapeo_adoracion'     => 'Adoración',
    'mapeo_biblia'        => 'Aplicar la Biblia',
    'mapeo_evangelizar'   => 'Evangelizar',
    'mapeo_cena'          => 'Cena del Señor',
    'mapeo_dar'           => 'Dar ofrenda',
    'mapeo_bautizar'      => 'Bautizar',
    'mapeo_trabajadores'  => 'Entrenar líderes',
];

$selectAvg = [];
foreach($camposMadurez as $campo => $label){
    $selectAvg[] = "AVG(sat_reportes.".$campo.") as ".$campo;
}

$sql = "SELECT ".implode(", ", $selectAvg)."
        FROM sat_reportes
        LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = sat_reportes.idUsuario
        WHERE sat_reportes.id_grupo > 0
          AND sat_reportes.id_actividad = 1
          ".$sqlFiltroBase;

if($row = db_first_row($PSN, $sql)){
    $hayDatos = false;
    foreach($camposMadurez as $campo => $label){
        $valor = round((float)$row->f($campo), 2);
        if($valor > 0) $hayDatos = true;
        $datosMadurez[] = [$label, $valor];
    }
    if(!$hayDatos) $varErrorMadurez = 1;
} else {
    $varErrorMadurez = 1;
}

/* =========================
   #2: CRECIMIENTO ACUMULADO DE PERSONAS ALCANZADAS
   ========================= */
$varErrorCrecimiento = 0;
$nombreCrecimiento = "CRECIMIENTO ACUMULADO DE PERSONAS ALCANZADAS";
$totalCrecimiento = 0;
$datosCrecimiento = [];

$sql = "SELECT
            DATE_FORMAT(sat_reportes.fechaReporte, '%Y-%m') as ym,
            DATE_FORMAT(sat_reportes.fechaReporte, '%b %Y') as periodo,
            SUM(sat_reportes.asistencia_total) as total_mes
        FROM sat_reportes
        LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = sat_reportes.idUsuario
        WHERE sat_reportes.id_grupo > 0
          ".$sqlFiltroBase."
        GROUP BY ym, periodo
        ORDER BY ym ASC";

$PSN->query($sql);
if($PSN->num_rows() > 0){
    $acumulado = 0;
    while($PSN->next_record()){
        $acumulado += (int)$PSN->f('total_mes');
        $datosCrecimiento[] = [$PSN->f('periodo'), $acumulado];
    }
    $totalCrecimiento = $acumulado;
    if($totalCrecimiento <= 0) $varErrorCrecimiento = 1;
} else {
    $varErrorCrecimiento = 1;
}

/* =========================
   #4: RECORRIDO DE GRUPOS (GENERACIÓN 0-5) POR FACILITADOR
   - Listado de tarjetas (una por grupo, agrupadas por generación).
   - No se filtra por fecha de reporte (son fechas de inicio de grupo),
     solo por facilitador y país.
   - Al hacer clic en una tarjeta (o al buscar y elegir un grupo con el
     campo "Ver"), se navega a graphs_dashboard3_arbol.php: una página
     dedicada y liviana que dibuja el árbol de ESE grupo (ancestros +
     hijos directos), sin recargar el resto de este dashboard.
   ========================= */
$varErrorGenealogia   = 0;
$nombreGenealogia     = "RECORRIDO DE GRUPOS (GENERACIÓN 0-5)";
$opcionesGenealogia   = [];
$jsOpcionesGenealogia = [];
$totalGenealogiaNodos = 0;
$tarjetasGenealogia   = [];
$LIMITE_TARJETAS_VISIBLES = 24;

$paletaGeneraciones = ['#0259a5','#27ae60','#f39c12','#8e44ad','#e74c3c','#16a085','#d35400','#2c3e50','#c0392b','#2980b9'];

/* Construye la URL hacia la página dedicada del árbol (graphs_dashboard3_arbol.php),
   preservando los filtros actuales (facilitador, país, fechas). */
function arbolUrl($params){
    $base = [
        'doc'            => 'graphs_dashboard3_arbol',
        'idUsuario'      => isset($GLOBALS['buscar_idUsuario']) ? $GLOBALS['buscar_idUsuario'] : '',
        'empresa_paisid' => isset($GLOBALS['empresa_paisid']) ? $GLOBALS['empresa_paisid'] : '',
        'fechaInicial'   => isset($GLOBALS['fechaInicial']) ? $GLOBALS['fechaInicial'] : '',
        'fechaFinal'     => isset($GLOBALS['fechaFinal']) ? $GLOBALS['fechaFinal'] : '',
    ];
    $todos = array_merge($base, $params);
    $qs = [];
    foreach($todos as $k => $v){
        $qs[] = urlencode($k).'='.urlencode((string)$v);
    }
    return 'index.php?'.implode('&', $qs);
}

$sqlFiltroGenealogia = "";
if($buscar_idUsuario !== ""){
    $sqlFiltroGenealogia .= " AND sat_reportes.idUsuario = '".$buscar_idUsuario."'";
}
if($empresa_paisid !== ""){
    $sqlFiltroGenealogia .= " AND usuario_empresa.empresa_paisid = '".$empresa_paisid."'";
}

$sql = "SELECT
            sat_reportes.id,
            sat_reportes.nombreGrupo_txt,
            sat_reportes.idGrupoMadre,
            sat_reportes.generacionNumero,
            sat_reportes.fechaInicio,
            sat_reportes.plantador,
            sat_reportes.grupoMadre_txt
        FROM sat_reportes
        LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = sat_reportes.idUsuario
        WHERE sat_reportes.id_grupo = 0
          ".$sqlFiltroGenealogia."
        ORDER BY sat_reportes.generacionNumero ASC, sat_reportes.fechaInicio ASC";

$PSN->query($sql);
if($PSN->num_rows() > 0){
    $filas = [];
    while($PSN->next_record()){
        $filas[] = [
            'id'          => (int)$PSN->f('id'),
            'nombre'      => trim($PSN->f('nombreGrupo_txt')) != "" ? trim($PSN->f('nombreGrupo_txt')) : "(Sin nombre)",
            'idMadre'     => (int)$PSN->f('idGrupoMadre'),
            'generacion'  => (int)$PSN->f('generacionNumero'),
            'fecha'       => $PSN->f('fechaInicio'),
            'plantador'   => trim($PSN->f('plantador')),
            'madreTxt'    => trim($PSN->f('grupoMadre_txt')),
        ];
    }

    /* Opciones del buscador "Ver": TODOS los grupos, cualquier generación,
       ordenados por generación y nombre. */
    $opcionesGenealogia = $filas;
    usort($opcionesGenealogia, function($a, $b){
        if($a['generacion'] === $b['generacion']){
            return strcasecmp($a['nombre'], $b['nombre']);
        }
        return $a['generacion'] <=> $b['generacion'];
    });

    foreach($opcionesGenealogia as $op){
        $jsOpcionesGenealogia[] = [
            'id'         => $op['id'],
            'nombre'     => $op['nombre'],
            'generacion' => $op['generacion'],
            'url'        => arbolUrl(['idGrupoGenealogia' => $op['id']]),
        ];
    }

    $formatFecha = function($f){
        if(!empty($f) && $f != "0000-00-00"){
            $ts = strtotime($f);
            if($ts) return date("d/m/Y", $ts);
        }
        return "";
    };

    /* Una tarjeta por cada grupo (de cualquier generación), segmentadas
       por generación. Al hacer clic en una tarjeta se abre el árbol
       enfocado de ese grupo en la página dedicada. */
    foreach($filas as $f){
        $tarjetasGenealogia[$f['generacion']][] = [
            'id'     => $f['id'],
            'nombre' => $f['nombre'],
            'fecha'  => $formatFecha($f['fecha']),
        ];
    }
    ksort($tarjetasGenealogia);
    foreach($tarjetasGenealogia as $gen => &$listaGen){
        usort($listaGen, function($a, $b){
            return strcasecmp($a['nombre'], $b['nombre']);
        });
    }
    unset($listaGen);

    $totalGenealogiaNodos = count($filas);
    $varErrorGenealogia = empty($tarjetasGenealogia) ? 1 : 0;
} else {
    $varErrorGenealogia = 1;
}
?>

<style>
.db-header{ margin-bottom: 10px; }
.db-card{
  border: 1px solid rgba(0,0,0,.08);
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 6px 18px rgba(0,0,0,.06);
  margin-bottom: 18px;
  overflow: hidden;
  transition: all .2s ease-in-out;
}
.db-card:hover{ box-shadow: 0 10px 26px rgba(0,0,0,.08); transform: translateY(-2px); }
.db-card__head{
  padding: 14px 16px;
  border-bottom: 1px solid rgba(0,0,0,.06);
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: 10px;
  flex-wrap: wrap;
}
.db-card__title-wrap{
  display:flex;
  align-items:center;
  gap: 8px;
}
.db-card__title{
  margin:0;
  font-size: 13px;
  font-weight: 900;
  letter-spacing: .4px;
  text-transform: uppercase;
}
.db-card__meta{
  display:flex;
  align-items:center;
  gap: 8px;
  flex-wrap: wrap;
  justify-content:flex-end;
}
.db-pill{
  display:inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(2, 117, 216, .10);
  color: #0259a5;
  font-size: 12px;
  font-weight: 900;
}
.db-card__body{ padding: 14px 16px 18px 16px; }

.chart-box{ width:100%; height: 360px; }

/* ===== Botón de información ===== */
.db-info-btn{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid #0259a5;
  background: transparent;
  color: #0259a5;
  font-size: 13px;
  font-weight: 900;
  line-height: 1;
  cursor: pointer;
  transition: background .18s, color .18s;
  flex-shrink: 0;
  padding: 0;
}
.db-info-btn:hover{
  background: #0259a5;
  color: #fff;
}

/* ===== Modal de descripción ===== */
.db-info-overlay{
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.45);
  z-index: 9998;
  align-items: center;
  justify-content: center;
}
.db-info-overlay.active{
  display: flex;
}
.db-info-modal{
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,.18);
  max-width: 480px;
  width: calc(100% - 32px);
  padding: 28px 28px 22px 28px;
  position: relative;
  animation: dbModalIn .18s ease;
}
@keyframes dbModalIn{
  from{ transform: scale(.94); opacity:0; }
  to  { transform: scale(1);   opacity:1; }
}
.db-info-modal__close{
  position: absolute;
  top: 14px;
  right: 16px;
  background: none;
  border: none;
  font-size: 22px;
  color: #888;
  cursor: pointer;
  line-height: 1;
  padding: 0;
}
.db-info-modal__close:hover{ color: #333; }
.db-info-modal__title{
  font-size: 18px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: .4px;
  margin: 0 0 16px 0;
  color: #0259a5;
  padding-right: 24px;
}
.db-info-modal__body{
  font-size: 16px;
  line-height: 1.8;
  color: #333;
}
.db-info-modal__body p{
  margin: 0 0 8px 0;
}
.db-info-modal__body ul{
  margin: 0 0 8px 14px;
  padding: 0;
}
.db-info-modal__body li{
  margin-bottom: 5px;
}

/* ===== GENERACIÓN 0-5 de grupos (tarjetas + buscador) ===== */
.genealogia-filtro{
  display:flex;
  align-items:flex-end;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 16px;
  padding-bottom: 14px;
  border-bottom: 1px dashed rgba(0,0,0,.1);
}
.genealogia-filtro__label{
  display:flex;
  flex-direction: column;
  gap: 4px;
  font-weight: 700;
  min-width: 260px;
  max-width: 420px;
  flex: 1 1 260px;
  margin: 0;
}
.genealogia-filtro__label select[disabled],
.genealogia-filtro__label input[disabled]{
  background-color: rgba(0,0,0,.05);
  color: #999;
  cursor: not-allowed;
}
.genealogia-combo{
  position: relative;
}
.genealogia-combo__lista{
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 4px;
  max-height: 320px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid rgba(0,0,0,.12);
  border-radius: 10px;
  box-shadow: 0 12px 28px rgba(0,0,0,.14);
  z-index: 20;
}
.genealogia-combo__lista.activa{
  display: block;
}
.genealogia-combo__grupo{
  padding: 6px 12px;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: #99a;
  background: rgba(0,0,0,.03);
}
.genealogia-combo__item{
  padding: 8px 12px;
  font-size: 13px;
  font-weight: 600;
  color: #1c2b3a;
  cursor: pointer;
}
.genealogia-combo__item:hover,
.genealogia-combo__item.activo{
  background: rgba(2,117,216,.10);
  color: #0259a5;
}
.genealogia-combo__vacio{
  padding: 10px 12px;
  font-size: 13px;
  color: #999;
}
.genealogia-filtro__hint{
  font-weight: 600;
  font-size: 12px;
  color: #b06a00;
  margin-top: 2px;
}
.genealogia-filtro__hint::before{
  content: "⚠ ";
}
.genealogia-grid__hint{
  margin: 0 0 16px 0;
  font-size: 13px;
  color: #777;
}
.genealogia-gen-section{
  margin-bottom: 12px;
  border: 1px solid rgba(0,0,0,.08);
  border-radius: 10px;
  padding: 10px 14px;
}
.genealogia-gen-section:last-child{
  margin-bottom: 0;
}
.genealogia-gen-section[open]{
  padding-bottom: 16px;
}
.genealogia-gen-title{
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  padding: 4px 0;
  font-size: 11px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: #556;
  cursor: pointer;
  list-style: none;
}
.genealogia-gen-title::-webkit-details-marker{
  display: none;
}
.genealogia-gen-title::marker{
  content: "";
}
.genealogia-gen-title__toggle{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 5px;
  border: 2px solid #0259a5;
  color: #0259a5;
  font-size: 14px;
  font-weight: 900;
  line-height: 1;
  flex-shrink: 0;
}
.genealogia-gen-title__toggle::before{
  content: "+";
}
details[open] > .genealogia-gen-title .genealogia-gen-title__toggle::before{
  content: "\2212";
}
.genealogia-gen-section__body{
  margin-top: 12px;
}
.genealogia-buscador{
  margin-bottom: 12px;
  max-width: 320px;
}
.genealogia-gen-title__dot{
  width: 9px;
  height: 9px;
  border-radius: 50%;
  flex-shrink: 0;
}
.genealogia-gen-title__count{
  font-weight: 700;
  color: #aaa;
  text-transform: none;
  letter-spacing: 0;
}
.genealogia-grid{
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
  gap: 12px;
}
.genealogia-card{
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 16px;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.08);
  border-top: 3px solid #0259a5;
  text-decoration: none;
  color: inherit;
  transition: box-shadow .18s ease, transform .18s ease;
}
.genealogia-card:hover,
.genealogia-card:focus{
  box-shadow: 0 12px 24px rgba(0,0,0,.12);
  transform: translateY(-3px);
  text-decoration: none;
  color: inherit;
}
.genealogia-card__nombre{
  font-weight: 800;
  font-size: 13.5px;
  color: #1c2b3a;
  word-break: break-word;
  line-height: 1.3;
}
.genealogia-card__fecha{
  font-size: 12px;
  color: #93a0ab;
}
.genealogia-card__cta{
  margin-top: 10px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  font-weight: 800;
  color: #0259a5;
}
.genealogia-card__cta::after{
  content: "\2192";
  transition: transform .15s ease;
}
.genealogia-card:hover .genealogia-card__cta::after{
  transform: translateX(3px);
}

@media (max-width: 992px){
  .chart-box{ height: 340px; }
}
@media (max-width: 767px){
  .db-card{ border-radius: 12px; }
  .db-card__title{ font-size: 12px; }
  .chart-box{ height: 320px; }

  form.form-horizontal .form-group > [class*="col-"]{
    width: 100% !important;
    max-width: 100% !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    margin-bottom: 10px;
  }
  form.form-horizontal .form-group strong{
    display:block;
    margin-bottom:4px;
  }
  form.form-horizontal select,
  form.form-horizontal input[type="date"],
  form.form-horizontal input[type="submit"]{
    width:100% !important;
  }
  form.form-horizontal .btn{ width:100%; }
}
</style>

<div class="container">

<form action="index.php" method="get" name="formDashboard3" class="form-horizontal">
  <input type="hidden" name="doc" value="graphs_dashboard3" />

  <div class="db-header">
    <h3 class="alert alert-info text-center" style="margin-bottom:10px;">DASHBOARD 3</h3>
  </div>

  <div class="cont-tit">
    <div class="hr"><hr></div>
    <div class="tit-cen"><h3>FILTROS DE BÚSQUEDA</h3></div>
    <div class="hr"><hr></div>
  </div>

  <div class="form-group">
    <div class="col-sm-4">
      <strong>Facilitador Satura:</strong>
      <select name="idUsuario" onchange="this.form.submit()" class="form-control">
        <?php if($_SESSION["perfil"] != 163){ ?>
          <option value="">Ver todos</option>
        <?php } ?>
        <?php
        $sql = "SELECT id, nombre
                FROM usuario
                WHERE tipo IN (162, 163) ";
        if($_SESSION["perfil"] == 163){
          $sql .= " AND id = '".$_SESSION["id"]."' ";
        }
        $sql .= " ORDER BY nombre asc";
        $PSN2->query($sql);
        if($PSN2->num_rows() > 0){
          while($PSN2->next_record()){
            $id  = $PSN2->f('id');
            $nom = $PSN2->f('nombre');
            $sel = ($buscar_idUsuario == $id) ? 'selected="selected"' : '';
            echo '<option value="'.$id.'" '.$sel.'>'.$nom.'</option>';
          }
        }
        ?>
      </select>
    </div>

    <?php if($_SESSION["perfil"] != 163){ ?>
    <div class="col-sm-3">
      <strong>Nombre del país:</strong>
      <select name="empresa_paisid" class="form-control" onchange="this.form.submit()">
        <option value="">Sin especificar</option>
        <?php
        $sql = "SELECT id, descripcion
                FROM categorias
                WHERE idSec = 37
                ORDER BY descripcion asc";
        $PSN2->query($sql);
        if($PSN2->num_rows() > 0){
          while($PSN2->next_record()){
            $id   = $PSN2->f('id');
            $desc = $PSN2->f('descripcion');
            $sel  = ($empresa_paisid == $id) ? 'selected="selected"' : '';
            echo '<option value="'.$id.'" '.$sel.'>'.$desc.'</option>';
          }
        }
        ?>
      </select>
    </div>
    <?php } ?>

    <div class="col-sm-2">
      <strong>Fecha Inicial:</strong>
      <input type="date" name="fechaInicial" id="fechaInicial" value="<?=$fechaInicial;?>" class="form-control" />
    </div>

    <div class="col-sm-2">
      <strong>Fecha Final:</strong>
      <input type="date" name="fechaFinal" id="fechaFinal" value="<?=$fechaFinal;?>" class="form-control" />
    </div>

    <div class="col-sm-1"><br>
      <input type="submit" value="Filtrar" class="btn btn-success" />
    </div>
  </div>
</form>

<div class="cont-tit">
  <div class="hr"><hr></div>
  <div class="tit-cen"><h3 class="text-center">RESULTADOS</h3></div>
  <div class="hr"><hr></div>
</div>

<!-- #1 y #2 -->
<div class="row">
  <div class="col-lg-6 col-md-6 col-sm-12">
    <div class="db-card">
      <div class="db-card__head">
        <div class="db-card__title-wrap">
          <h4 class="db-card__title"><?=$nombreCrecimiento;?></h4>
          <button class="db-info-btn" onclick="dbOpenInfo('crecimiento')" type="button" title="Ver descripción">i</button>
        </div>
        <div class="db-card__meta">
          <?php if($varErrorCrecimiento == 0){ ?><span class="db-pill">Total: <?=number_format($totalCrecimiento);?></span><?php } ?>
        </div>
      </div>
      <div class="db-card__body">
        <?php if($varErrorCrecimiento == 1){ ?>
          <div class="alert alert-warning text-center" style="margin-bottom:0;">
            No se ha encontrado ningún registro para el rango de fechas seleccionado.
          </div>
        <?php } else { ?>
          <div id="chart_crecimiento" class="chart-box"></div>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="col-lg-6 col-md-6 col-sm-12">
    <div class="db-card">
      <div class="db-card__head">
        <div class="db-card__title-wrap">
          <h4 class="db-card__title"><?=$nombreMadurez;?></h4>
          <button class="db-info-btn" onclick="dbOpenInfo('madurez')" type="button" title="Ver descripción">i</button>
        </div>
        <div class="db-card__meta">
          <span class="db-pill">Escala 1 a 4</span>
        </div>
      </div>
      <div class="db-card__body">
        <?php if($varErrorMadurez == 1){ ?>
          <div class="alert alert-warning text-center" style="margin-bottom:0;">
            No se ha encontrado información de reportes de Coach para el rango de fechas seleccionado.
          </div>
        <?php } else { ?>
          <div id="chart_madurez" class="chart-box"></div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<!-- #4: RECORRIDO DE GRUPOS (GENERACIÓN 0-5) -->
<div class="row" id="genealogiaCard">
  <div class="col-lg-12 col-md-12 col-sm-12">
    <div class="db-card">
      <div class="db-card__head">
        <div class="db-card__title-wrap">
          <h4 class="db-card__title"><?=$nombreGenealogia;?></h4>
          <button class="db-info-btn" onclick="dbOpenInfo('genealogia')" type="button" title="Ver descripción">i</button>
        </div>
        <div class="db-card__meta">
          <?php if($varErrorGenealogia == 0){ ?>
            <span class="db-pill">Grupos: <?=$totalGenealogiaNodos;?></span>
            <span class="db-pill">Generaciones: <?=count($tarjetasGenealogia);?></span>
          <?php } ?>
        </div>
      </div>
      <div class="db-card__body">
        <?php if(!empty($opcionesGenealogia)){ ?>
          <div class="genealogia-filtro">
            <div class="genealogia-filtro__label genealogia-combo" id="genComboWrap">
              <strong>Ver:</strong>
              <input type="text" id="genComboInput" class="form-control" autocomplete="off"
                     placeholder="Escribe el nombre de un grupo..."
                     <?=($buscar_idUsuario === "" ? 'disabled="disabled"' : '');?> />
              <div class="genealogia-combo__lista" id="genComboLista"></div>
              <?php if($buscar_idUsuario === ""){ ?>
                <span class="genealogia-filtro__hint">Selecciona primero un <strong>Facilitador Satura</strong> arriba para poder elegir un grupo puntual.</span>
              <?php } ?>
            </div>
          </div>
          <script type="text/javascript">
            var GEN_OPCIONES = <?=json_encode($jsOpcionesGenealogia, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE);?>;
          </script>
        <?php } ?>

        <?php if($varErrorGenealogia == 1){ ?>
          <div class="alert alert-warning text-center" style="margin-bottom:0;">
            No se ha encontrado ningún grupo madre registrado para el facilitador / filtros seleccionados.
          </div>
        <?php } else { ?>
          <?php foreach($tarjetasGenealogia as $gen => $listaGen){
            $colorGen = $paletaGeneraciones[$gen % count($paletaGeneraciones)];
            $gridId = 'genGrid_'.$gen;
            ?>
            <details class="genealogia-gen-section">
              <summary class="genealogia-gen-title">
                <span class="genealogia-gen-title__toggle" aria-hidden="true"></span>
                <span class="genealogia-gen-title__dot" style="background:<?=$colorGen;?>"></span>
                Generación <?=$gen;?>
                <span class="genealogia-gen-title__count">(<?=count($listaGen);?>)</span>
              </summary>
              <div class="genealogia-gen-section__body">
                <input type="text" class="form-control genealogia-buscador" placeholder="Buscar grupo por nombre..." oninput="dbFiltrarTarjetas(this,'<?=$gridId;?>')" />
                <div class="genealogia-grid" id="<?=$gridId;?>">
                  <?php foreach($listaGen as $i => $t){
                    $urlTarjeta = arbolUrl(['idGrupoGenealogia' => $t['id']]);
                    $esExtra = ($i >= $LIMITE_TARJETAS_VISIBLES);
                    ?>
                    <a href="<?=$urlTarjeta;?>" class="genealogia-card" <?=($esExtra ? 'data-extra="1" style="display:none;border-top-color:'.$colorGen.';"' : 'style="border-top-color:'.$colorGen.';"');?> data-nombre="<?=htmlspecialchars(strtolower($t['nombre']), ENT_QUOTES, 'UTF-8');?>">
                      <div class="genealogia-card__nombre"><?=htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8');?></div>
                      <?php if($t['fecha'] !== ""){ ?>
                        <div class="genealogia-card__fecha">🕓 <?=$t['fecha'];?></div>
                      <?php } ?>
                      <div class="genealogia-card__cta">Ver árbol</div>
                    </a>
                  <?php } ?>
                </div>
                <?php if(count($listaGen) > $LIMITE_TARJETAS_VISIBLES){ ?>
                  <button type="button" class="btn btn-default" style="margin-top:12px;" onclick="dbMostrarMas(this,'<?=$gridId;?>')">Ver más (<?=(count($listaGen) - $LIMITE_TARJETAS_VISIBLES);?> restantes)</button>
                <?php } ?>
              </div>
            </details>
          <?php } ?>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

</div><!-- /container -->

<!-- ===== Modal de información de gráficas ===== -->
<div class="db-info-overlay" id="dbInfoOverlay">
  <div class="db-info-modal">
    <button class="db-info-modal__close" id="dbInfoClose" title="Cerrar">&times;</button>
    <h4 class="db-info-modal__title" id="dbInfoTitle"></h4>
    <div class="db-info-modal__body" id="dbInfoBody"></div>
  </div>
</div>

<script type="text/javascript">
google.charts.load("current", {packages:["corechart"]});
google.charts.setOnLoadCallback(drawAllCharts);

(function(){
  var resizeTimer = null;
  window.addEventListener('resize', function(){
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(drawAllCharts, 220);
  });
  window.addEventListener('orientationchange', function(){
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(drawAllCharts, 220);
  });
})();

(function(){
  if('ResizeObserver' in window){
    var ro = new ResizeObserver(function(){
      if(window.__dbRoTimer) clearTimeout(window.__dbRoTimer);
      window.__dbRoTimer = setTimeout(drawAllCharts, 220);
    });
    ['chart_madurez','chart_crecimiento'].forEach(function(id){
      var el = document.getElementById(id);
      if(el) ro.observe(el);
    });
  }
})();

function drawAllCharts(){
  drawMadurez();
  drawCrecimiento();
}

/* ===== Búsqueda aproximada de tarjetas (sin acentos, tolera errores) ===== */
var DB_REGEX_DIACRITICOS = new RegExp('[' + String.fromCharCode(768) + '-' + String.fromCharCode(879) + ']', 'g');

function dbNormalizar(s){
  s = (s || '').toString().toLowerCase().trim();
  if(s.normalize){
    s = s.normalize('NFD').replace(DB_REGEX_DIACRITICOS, '');
  }
  return s;
}

function dbLevenshtein(a, b){
  var la = a.length, lb = b.length;
  if(la === 0) return lb;
  if(lb === 0) return la;
  var fila = [];
  for(var j = 0; j <= la; j++){ fila[j] = j; }
  for(var i = 1; i <= lb; i++){
    var anterior = fila[0];
    fila[0] = i;
    for(j = 1; j <= la; j++){
      var temp = fila[j];
      fila[j] = (b.charAt(i - 1) === a.charAt(j - 1))
        ? anterior
        : Math.min(anterior + 1, fila[j] + 1, fila[j - 1] + 1);
      anterior = temp;
    }
  }
  return fila[la];
}

/* Compara el nombre de un grupo contra el texto buscado, tolerando
   errores de digitación y coincidencias parciales por palabra. */
function dbCoincideAproximado(nombre, query){
  if(!query) return true;
  var nombreNorm = dbNormalizar(nombre);
  var queryNorm = dbNormalizar(query);
  if(nombreNorm.indexOf(queryNorm) !== -1) return true;

  var palabrasNombre = nombreNorm.split(/\s+/).filter(Boolean);
  var palabrasQuery = queryNorm.split(/\s+/).filter(Boolean);
  if(palabrasQuery.length === 0) return true;

  return palabrasQuery.every(function(pq){
    return palabrasNombre.some(function(pn){
      if(pn.indexOf(pq) !== -1 || pq.indexOf(pn) !== -1) return true;
      var maxDist = pq.length <= 4 ? 1 : (pq.length <= 8 ? 2 : 3);
      return dbLevenshtein(pn, pq) <= maxDist;
    });
  });
}

/* Recalcula qué tarjetas de un grid deben verse, combinando el texto
   buscado (que ignora el límite "ver más" y busca en TODAS las tarjetas)
   con el estado de "mostrar más" cuando no hay búsqueda activa. */
function dbActualizarGrid(grid){
  var q = grid.getAttribute('data-query') || '';
  var mostrarTodo = grid.getAttribute('data-mostrar-todo') === '1';
  var cards = grid.querySelectorAll('.genealogia-card');
  Array.prototype.forEach.call(cards, function(c){
    var nombre = c.getAttribute('data-nombre') || '';
    var esExtra = c.getAttribute('data-extra') === '1';
    var visible;
    if(q !== ''){
      visible = dbCoincideAproximado(nombre, q);
    } else {
      visible = (!esExtra || mostrarTodo);
    }
    c.style.display = visible ? '' : 'none';
  });
}

/* Filtra en vivo las tarjetas de un grid por nombre (sin recargar la página). */
function dbFiltrarTarjetas(inputEl, gridId){
  var grid = document.getElementById(gridId);
  if(!grid) return;
  grid.setAttribute('data-query', inputEl.value || '');
  dbActualizarGrid(grid);
}

/* Revela las tarjetas ocultas (marcadas como "extra") de un grid. */
function dbMostrarMas(btn, gridId){
  var grid = document.getElementById(gridId);
  if(!grid) return;
  grid.setAttribute('data-mostrar-todo', '1');
  dbActualizarGrid(grid);
  btn.style.display = 'none';
}

/* ===== Combobox "Ver" del árbol de GENERACIÓN 0-5: escribir para buscar ===== */
var GEN_COMBO_ACTIVE_INDEX = -1;

function dbEscaparHtml(s){
  var div = document.createElement('div');
  div.textContent = s;
  return div.innerHTML;
}

/* Reparte los resultados por generación (no un solo corte global) para
   que generaciones con muchos grupos (ej. Generación 0) no acaparen
   todo el espacio y dejen sin resultados a las demás generaciones. */
function dbComboLimitarPorGeneracion(coincidencias, porGeneracion){
  var porGen = {};
  var ordenGen = [];
  coincidencias.forEach(function(op){
    if(!porGen[op.generacion]){
      porGen[op.generacion] = [];
      ordenGen.push(op.generacion);
    }
    porGen[op.generacion].push(op);
  });
  ordenGen.sort(function(a, b){ return a - b; });

  var resultado = [];
  ordenGen.forEach(function(gen){
    resultado = resultado.concat(porGen[gen].slice(0, porGeneracion));
  });
  return resultado;
}

function dbComboRenderLista(query){
  var lista = document.getElementById('genComboLista');
  if(!lista || typeof GEN_OPCIONES === 'undefined') return;
  var q = (query || '').trim();
  var coincidencias = q === '' ? GEN_OPCIONES : GEN_OPCIONES.filter(function(op){
    return dbCoincideAproximado(op.nombre, q);
  });
  var filtradas = dbComboLimitarPorGeneracion(coincidencias, 10);
  GEN_COMBO_ACTIVE_INDEX = -1;

  if(filtradas.length === 0){
    lista.innerHTML = '<div class="genealogia-combo__vacio">Sin coincidencias</div>';
    lista.classList.add('activa');
    return;
  }

  var html = '';
  var genActual = null;
  filtradas.forEach(function(op){
    if(genActual !== op.generacion){
      html += '<div class="genealogia-combo__grupo">Generación ' + op.generacion + '</div>';
      genActual = op.generacion;
    }
    html += '<div class="genealogia-combo__item" data-url="' + dbEscaparHtml(op.url) + '">' + dbEscaparHtml(op.nombre) + '</div>';
  });
  lista.innerHTML = html;
  lista.classList.add('activa');

  Array.prototype.forEach.call(lista.querySelectorAll('.genealogia-combo__item'), function(el){
    el.addEventListener('mousedown', function(e){
      e.preventDefault();
      window.location.href = el.getAttribute('data-url');
    });
  });
}

function dbComboOcultarLista(){
  var lista = document.getElementById('genComboLista');
  if(lista) lista.classList.remove('activa');
}

function dbComboMoverActivo(delta){
  var lista = document.getElementById('genComboLista');
  if(!lista) return;
  var items = lista.querySelectorAll('.genealogia-combo__item');
  if(items.length === 0) return;
  GEN_COMBO_ACTIVE_INDEX += delta;
  if(GEN_COMBO_ACTIVE_INDEX < 0) GEN_COMBO_ACTIVE_INDEX = items.length - 1;
  if(GEN_COMBO_ACTIVE_INDEX >= items.length) GEN_COMBO_ACTIVE_INDEX = 0;
  Array.prototype.forEach.call(items, function(el, i){
    el.classList.toggle('activo', i === GEN_COMBO_ACTIVE_INDEX);
  });
  items[GEN_COMBO_ACTIVE_INDEX].scrollIntoView({block:'nearest'});
}

function dbComboSeleccionarActivo(){
  var lista = document.getElementById('genComboLista');
  if(!lista) return;
  var items = lista.querySelectorAll('.genealogia-combo__item');
  if(items.length === 0) return;
  var idx = GEN_COMBO_ACTIVE_INDEX >= 0 ? GEN_COMBO_ACTIVE_INDEX : 0;
  var el = items[idx];
  if(el) window.location.href = el.getAttribute('data-url');
}

(function(){
  var input = document.getElementById('genComboInput');
  if(!input) return;
  input.addEventListener('input', function(){
    dbComboRenderLista(input.value);
  });
  input.addEventListener('focus', function(){
    dbComboRenderLista(input.value);
  });
  input.addEventListener('keydown', function(e){
    if(e.key === 'ArrowDown'){ e.preventDefault(); dbComboMoverActivo(1); }
    else if(e.key === 'ArrowUp'){ e.preventDefault(); dbComboMoverActivo(-1); }
    else if(e.key === 'Enter'){ e.preventDefault(); dbComboSeleccionarActivo(); }
    else if(e.key === 'Escape'){ dbComboOcultarLista(); }
  });
  input.addEventListener('blur', function(){
    setTimeout(dbComboOcultarLista, 120);
  });
})();

/* ===== #1 Madurez Espiritual (ColumnChart escala 1-4) ===== */
function drawMadurez(){
  <?php if($varErrorMadurez == 0){ ?>
  var data = google.visualization.arrayToDataTable([
    ['Área', 'Promedio', { role:'annotation' }],
    <?php
      $rows = [];
      foreach($datosMadurez as $r){
        $label = str_replace("'", "\\'", $r[0]);
        $val = (float)$r[1];
        $rows[] = "['".$label."', ".$val.", '".number_format($val, 2)."']";
      }
      echo implode(",\n    ", $rows);
    ?>
  ]);

  var el = document.getElementById('chart_madurez');
  if(!el) return;
  var w = el.clientWidth || 600;
  var isMobile = (w <= 480);

  var options = {
    animation:{ startup:true, duration:1000, easing:'out' },
    legend: { position: 'none' },
    colors: ['#0259a5'],
    bar: { groupWidth: isMobile ? "70%" : "62%" },
    chartArea: isMobile ? { width:'88%', height:'62%' } : { width:'84%', height:'70%' },
    vAxis: { minValue: 0, maxValue: 4, ticks: [0,1,2,3,4] },
    hAxis: { textStyle: { fontSize: isMobile ? 9 : 11 }, slantedText: true, slantedTextAngle: 30 },
    annotations: { textStyle: { fontSize: isMobile ? 10 : 12 } }
  };

  new google.visualization.ColumnChart(el).draw(data, options);
  <?php } ?>
}

/* ===== #2 Crecimiento Acumulado de Personas Alcanzadas (AreaChart) ===== */
function drawCrecimiento(){
  <?php if($varErrorCrecimiento == 0){ ?>
  var data = google.visualization.arrayToDataTable([
    ['Mes', 'Acumulado'],
    <?php
      $rows = [];
      foreach($datosCrecimiento as $r){
        $label = str_replace("'", "\\'", $r[0]);
        $rows[] = "['".$label."', ".(int)$r[1]."]";
      }
      echo implode(",\n    ", $rows);
    ?>
  ]);

  var el = document.getElementById('chart_crecimiento');
  if(!el) return;
  var w = el.clientWidth || 700;
  var isMobile = (w <= 480);

  /* Evita que los meses del eje X se amontonen: calcula cuántas
     etiquetas caben cómodamente en el ancho disponible y solo muestra
     una de cada N (Google Charts sigue dibujando todos los puntos,
     solo se omiten etiquetas de texto). */
  var totalPuntos = data.getNumberOfRows();
  var anchoPorEtiqueta = isMobile ? 60 : 75;
  var maxEtiquetas = Math.max(4, Math.floor(w / anchoPorEtiqueta));
  var showTextEvery = Math.max(1, Math.ceil(totalPuntos / maxEtiquetas));

  var options = {
    animation:{ startup:true, duration:1000, easing:'out' },
    curveType: 'function',
    colors: ['#8e44ad'],
    lineWidth: 3,
    pointSize: 0,
    areaOpacity: 0.15,
    legend: { position: 'none' },
    chartArea: isMobile ? { width:'90%', height:'66%' } : { width:'92%', height:'72%' },
    hAxis: {
      textStyle: { fontSize: isMobile ? 10 : 12 },
      slantedText: showTextEvery <= 1,
      slantedTextAngle: isMobile ? 45 : 30,
      showTextEvery: showTextEvery,
      maxAlternation: 1
    },
    vAxis: { minValue: 0 }
  };

  new google.visualization.AreaChart(el).draw(data, options);
  <?php } ?>
}

/* ===== Sistema de Info ===== */
(function(){
  var INFO = {
    'madurez': {
      title: '🌱 Madurez Espiritual de los Grupos',
      html: '<ul>'
          + '<li><strong>📊 Promedio por área:</strong> Muestra el promedio (en una escala de 1 a 4) de cómo califican los coaches a los grupos en Oración, Compañerismo, Adoración, Aplicar la Biblia, Evangelizar, Cena del Señor, Dar ofrenda, Bautizar y Entrenar líderes.</li>'
          + '<li><strong>📋 Fuente:</strong> Solo se calcula con los reportes de tipo Coach, donde se evalúa la madurez del grupo como iglesia.</li>'
          + '<li><strong>💡 Úsala para:</strong> identificar qué áreas necesitan más acompañamiento en los grupos.</li>'
          + '</ul>'
    },
    'crecimiento': {
      title: '📈 Crecimiento Acumulado de Personas Alcanzadas',
      html: '<ul>'
          + '<li><strong>👥 Acumulado:</strong> Suma, mes a mes, el total de personas alcanzadas (asistencia) desde el inicio del rango de fechas seleccionado.</li>'
          + '<li><strong>💡 Úsala para:</strong> visualizar el alcance total del movimiento a lo largo del tiempo, sin importar si hubo meses con menor actividad.</li>'
          + '</ul>'
    },
    'genealogia': {
      title: '🌳 Recorrido de Grupos (GENERACIÓN 0-5)',
      html: '<ul>'
          + '<li><strong>🃏 Tarjetas por generación:</strong> al entrar verás los grupos agrupados en secciones plegables por generación (0, 1, 2...). Haz clic en el título de una generación para desplegarla.</li>'
          + '<li><strong>🔍 Buscador y "Ver más":</strong> dentro de cada generación puedes escribir el nombre del grupo para filtrarlo al instante, y usar "Ver más" si hay demasiadas tarjetas para mostrarlas todas de una vez.</li>'
          + '<li><strong>🖱️ Haz clic en una tarjeta</strong> para abrir, en una página aparte y liviana, el árbol genealógico de ese grupo: su cadena completa de ancestros (hasta la Generación 0) y sus hijos directos.</li>'
          + '<li><strong>➕ Nodo "+N":</strong> dentro del árbol, si un grupo tiene más de 4 hijos, solo se muestran 4 y un nodo "+N grupos más" — haz clic en él para expandir ese mismo árbol y ver todos sus hijos.</li>'
          + '<li><strong>👤 Filtro por facilitador:</strong> usa el filtro "Facilitador Satura" en la parte superior para ver solo los grupos que él o ella ha plantado. Es obligatorio elegirlo para poder buscar un grupo puntual con el campo "Ver".</li>'
          + '<li><strong>🔎 Campo "Ver":</strong> escribe el nombre de cualquier grupo (de cualquier generación) para encontrarlo al instante y saltar directo a su árbol, sin importar si es raíz o no.</li>'
          + '<li><strong>💡 Úsala para:</strong> visualizar la multiplicación de los grupos generación tras generación, sin perderte en árboles enormes.</li>'
          + '</ul>'
    }
  };

  function openInfo(key){
    var data = INFO[key];
    if(!data) return;
    document.getElementById('dbInfoTitle').textContent = data.title;
    document.getElementById('dbInfoBody').innerHTML   = data.html;
    document.getElementById('dbInfoOverlay').classList.add('active');
  }

  function closeInfo(){
    document.getElementById('dbInfoOverlay').classList.remove('active');
  }

  document.getElementById('dbInfoClose').addEventListener('click', closeInfo);
  document.getElementById('dbInfoOverlay').addEventListener('click', function(e){
    if(e.target === this) closeInfo();
  });
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeInfo();
  });

  window.dbOpenInfo = openInfo;
})();
</script>
