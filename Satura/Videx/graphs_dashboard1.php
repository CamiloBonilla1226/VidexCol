<?php
/*******************************************
DASHBOARD Generaciones 0-5
Archivo: graphs_Gen0-5.php

ORGANIZACIÓN VISUAL:
--Grafica metas-----Capacitación--
-------             Grafica de sankey               -----
--Grafica asistencia--  --Grafica datos del proceso--

ID MENU (permiso): 1
*******************************************/

function obtenerPorcentaje($cantidad, $total) {
    $total = (float)$total;
    if ($total <= 0) return 0;
    $porcentaje = ((float)$cantidad * 100) / $total;
    return round($porcentaje, 2);
}

/* =========================
   HELPERS (para acortar sin cambiar lógica)
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
        $base .= " AND EXISTS (
            SELECT 1
            FROM usuario_empresa
            WHERE usuario_empresa.idUsuario = sat_reportes.idUsuario
              AND usuario_empresa.empresa_paisid = '".$paisId."'
        )";
    }

    $excl = $base;
    $excl .= " AND sat_reportes.generacionNumero != 0";
    $excl .= " AND sat_reportes.generacionNumero != 77";
    $excl .= " AND sat_reportes.generacionNumero != 8";

    return [$excl, $base];
}

// Mantener explícito que estas gráficas filtran por fechaReporte.
function build_filtros_sat_fecha_reporte($idUsuario, $fechaInicial, $fechaFinal, $paisId){
    return build_filtros_sat($idUsuario, $fechaInicial, $fechaFinal, $paisId);
}

$PSN  = new DBbase_Sql;
$PSN1 = new DBbase_Sql; // (se mantiene por compatibilidad, aunque no se use)
$PSN2 = new DBbase_Sql;

/* =========================
   1) AUTORIZACIÓN
   ========================= */
$sql = "SELECT idMenu
        FROM usuarios_menu_graphs
        WHERE idMenu IN (1, 20, 21)
          AND idUsuario = '".$_SESSION["id"]."'";
$PSN->query($sql);
if($PSN->num_rows() == 0){
    die("NO está autorizado a ver este dashboard.");
}

/* =========================
   2) FILTROS GLOBALES
   ========================= */
// Fecha inicial: 01-02 del año actual, o del anterior si aún no llegamos al 1-02
$today = new DateTime();
$febrero01 = new DateTime(date('Y-02-01'));
if ($today < $febrero01) {
    $febrero01->modify('-1 year');
}
$fechaInicial = $febrero01->format('Y-m-d');
$fechaFinal   = date("Y-m-d");

// Si es cliente/perfil 163: forzamos idUsuario
if($_SESSION["perfil"] == 163){
    $_REQUEST["idUsuario"] = $_SESSION["id"];
}

$fechaInicial = req_date_or_default("fechaInicial", $fechaInicial);
$fechaFinal   = req_date_or_default("fechaFinal",   $fechaFinal);

$buscar_idUsuario = req_num("idUsuario");
$empresa_paisid   = req_num("empresa_paisid");

list($sqlFiltro, $sqlFiltroBase) = build_filtros_sat($buscar_idUsuario, $fechaInicial, $fechaFinal, $empresa_paisid);
list($sqlFiltroProcesoReporte, $sqlFiltroBaseProcesoReporte) = build_filtros_sat_fecha_reporte($buscar_idUsuario, $fechaInicial, $fechaFinal, $empresa_paisid);

// reportar_buscar usa intval(generacionNumero); por eso NULL o '' terminan tratándose como 0.
$sqlGenEsCero = "(sat_reportes.generacionNumero = 0 OR sat_reportes.generacionNumero IS NULL OR sat_reportes.generacionNumero = '')";
$sqlGenExcluidaProceso = "(".$sqlGenEsCero." OR sat_reportes.generacionNumero IN (77, 8))";

/* =========================
   3) DATASETS DE GRÁFICAS
   ========================= */

/* ---------- Gráfica 3: SANKY GEN 0-5 ---------- */
$varErrorSankey = 0;
$textoSankey = "";
$totalSankey = 0;

$nombreSankey = "FLUJO POR GENERACIÓN (GEN 0-5)";
$rowsJson = "[]";
$rowsJsonShort = "[]";

// Filtro Sankey: sin restricción de fechas (solo usuario y país)
$sqlFiltroSankeyBase = "";
if($buscar_idUsuario !== ""){
    $sqlFiltroSankeyBase .= " AND sat_reportes.idUsuario = '".$buscar_idUsuario."'";
}
if($empresa_paisid !== ""){
    $sqlFiltroSankeyBase .= " AND EXISTS (
        SELECT 1
        FROM usuario_empresa
        WHERE usuario_empresa.idUsuario = sat_reportes.idUsuario
          AND usuario_empresa.empresa_paisid = '".$empresa_paisid."'
    )";
}
$sqlFiltroSankey = $sqlFiltroSankeyBase." AND sat_reportes.id_grupo = 0 AND sat_reportes.generacionNumero BETWEEN 0 AND 5";

$sql = "SELECT
            sat_reportes.generacionNumero,
            COUNT(sat_reportes.id) AS conteo
        FROM sat_reportes
        WHERE 1 ".$sqlFiltroSankey."
        GROUP BY sat_reportes.generacionNumero
        ORDER BY sat_reportes.generacionNumero ASC";

$PSN->query($sql);
if($PSN->num_rows() > 0){

    $genConteos = array(0=>0,1=>0,2=>0,3=>0,4=>0,5=>0);

    while($PSN->next_record()){
        $gen = (int)$PSN->f('generacionNumero');
        $conteo = (int)$PSN->f('conteo');
        if($gen >= 0 && $gen <= 5){
            $genConteos[$gen] = $conteo;
        }
    }

    foreach($genConteos as $gen => $conteo){
        $textoSankey .= "| GENERACIÓN ".$gen.": ".$conteo." ";
        $totalSankey += (int)$conteo;
    }

    $totalBase = 0;
    for($g = 1; $g <= 5; $g++){
        $peso = (int)$genConteos[$g - 1];
        if($peso > 0){
            $totalBase += $peso;
        }
    }

    if($totalBase <= 0){
        $varErrorSankey = 1;
    } else {

        $rows = [];
        $rowsShort = [];

        for($g = 1; $g <= 5; $g++){
            // Se dibuja la flecha si la generación origen tiene grupos
            $peso = (int)$genConteos[$g - 1];
            if($peso <= 0) continue;

            $pct = round(($peso * 100) / $totalBase, 2);

            $fromLabel  = "GEN ".($g - 1);
            $toLabel    = "GEN ".$g;

            $fromLabelS = "G".($g - 1);
            $toLabelS   = "G".$g;

            $tooltip = "Grupos Gen ".($g - 1)." --> Gen ".$g."\n(".$peso." grupos) ".$pct."%";

            $rows[]      = [$fromLabel,  $toLabel,  $peso, $tooltip];
            $rowsShort[] = [$fromLabelS, $toLabelS, $peso, $tooltip];
        }

        if(count($rows) == 0){
            $varErrorSankey = 1;
        } else {
            $rowsJson      = json_encode($rows, JSON_UNESCAPED_UNICODE);
            $rowsJsonShort = json_encode($rowsShort, JSON_UNESCAPED_UNICODE);
        }
    }

} else {
    $varErrorSankey = 1;
}

/* ---------- Gráfica 1: ASISTENCIA (Pie Donut) ---------- */
$varErrorAsistencia = 0;
$totalAsistencia = 0;
$datosAsistencia = [];

$sql = "SELECT
            SUM(sat_reportes.asistencia_total) as asistencia_total,
            SUM(sat_reportes.asistencia_hom) as asistencia_hom,
            SUM(sat_reportes.asistencia_muj) as asistencia_muj,
            SUM(sat_reportes.asistencia_jov) as asistencia_jov,
            SUM(sat_reportes.asistencia_nin) as asistencia_nin
        FROM sat_reportes
        WHERE 1 ".$sqlFiltroBase;

if($row = db_first_row($PSN, $sql)){
    $hom = (int)$row->f('asistencia_hom');
    $muj = (int)$row->f('asistencia_muj');
    $jov = (int)$row->f('asistencia_jov');
    $nin = (int)$row->f('asistencia_nin');

    $datosAsistencia[] = ["HOMBRES", $hom];
    $datosAsistencia[] = ["MUJERES", $muj];
    $datosAsistencia[] = ["JÓVENES", $jov];
    $datosAsistencia[] = ["NIÑOS",   $nin];

    $totalAsistencia = (int)$row->f('asistencia_total');
} else {
    $varErrorAsistencia = 1;
}

/* ---------- Gráfica 2: DATOS DEL PROCESO (Bar) ---------- */
$varErrorProceso = 0;
$totalProceso = 0;
$datosProceso = [];

// Parte 1: métricas visibles en reportar_buscar.
// Suma las columnas de TODOS los reportes sin exclusiones por generación
$sql = "SELECT
            SUM(sat_reportes.bautizados) as bautizados,
            SUM(sat_reportes.discipulado) as discipulado,
            SUM(sat_reportes.desiciones) as desiciones,
            SUM(sat_reportes.preparandose) as preparandose
        FROM sat_reportes
        WHERE 1 ".$sqlFiltroBaseProcesoReporte;

if($row = db_first_row($PSN, $sql)){
    $bp  = (int)$row->f('bautizados');
    $dis = (int)$row->f('discipulado');
    $dec = (int)$row->f('desiciones');
    $pre = (int)$row->f('preparandose');

    $datosProceso[] = ["BAUTIZADOS",         $bp,  "purple"];
    $datosProceso[] = ["EN DISCIPULADO",     $dis, "orange"];
    $datosProceso[] = ["DECISIONES",         $dec, "green"];
    $datosProceso[] = ["PREPARÁNDOSE",       $pre, "gold"];

    $totalProceso = $bp + $dis + $dec + $pre;
} else {
    $varErrorProceso = 1;
}

// Parte 2: LÍDERES CAPACITADOS gen=0 (usa filtro base)
$sql = "SELECT
            COUNT(sat_reportes.id) as conteo,
            SUM(sat_reportes.asistencia_total) as asistencia_total
        FROM sat_reportes
        WHERE ".$sqlGenEsCero." ".$sqlFiltroBaseProcesoReporte;

if($row = db_first_row($PSN, $sql)){
    $conteo = (int)$row->f('conteo');
    $asis   = (int)$row->f('asistencia_total');

    $lideresCap = $asis;
    if($lideresCap < 0){ $lideresCap = 0; }

    $datosProceso[] = ["LÍDERES CAPACITADOS", $lideresCap, "grey"];
    $totalProceso += $lideresCap;
}

/* ---------- Gráfica DERECHA ARRIBA (REEMPLAZO DE G4): CAPACITACIÓN (Column) ---------- */
$varErrorG4 = 0;
$nombreG4 = "CAPACITACIÓN";
$totalG4 = 0;
$datosG4 = []; // [ [label, value], ... ]

$sql = "SELECT
            COUNT(sat_reportes.id) as conteo,
            SUM(sat_reportes.asistencia_total) as asistencia_total
        FROM sat_reportes
        WHERE ".$sqlGenEsCero." ".$sqlFiltroBase;

if($row = db_first_row($PSN, $sql)){
    $conteo = (int)$row->f('conteo');
    $asisTotal = (int)$row->f('asistencia_total');

    $lideresCap = $asisTotal;
    if($lideresCap < 0){ $lideresCap = 0; }

    // Mantiene exactamente la lógica del código que pasaste
    $datosG4[] = ["Total lideres capacitados ".$lideresCap, $lideresCap];
    $datosG4[] = ["Total capacitaciones ".$conteo, $conteo];

    // Total mostrado (equivale a asistencia_total de gen=0)
    $totalG4 = (int)$asisTotal;
} else {
    $varErrorG4 = 1;
}

/* ---------- Gráfica 5: METAS (Bar: Meta vs Actual) ---------- */
$varErrorG5 = 0;
$nombreG5 = "METAS";
$nombreActualG5 = "";
$paisSeleccionadoG5 = "";

$idUsuarioMetas = (isset($_REQUEST["idUsuario"]) && soloNumeros($_REQUEST["idUsuario"]) != "") ? (int)soloNumeros($_REQUEST["idUsuario"]) : 0;
if($idUsuarioMetas <= 0){
    $_REQUEST["idUsuario"] = 0;
    $idUsuarioMetas = 0;
}

// Filtro "limpio" para 77 y 8 (sin exclusiones)
$sqlFiltroLimpio = $sqlFiltroBaseProcesoReporte;

// Métricas Satura (Actual)
$satura_evangelismo = 0;
$satura_discipulado = 0;
$satura_bautizos = 0;

$satura_iglesias  = 0;
$satura_iglesias2 = 0;
$satura_iglesias3 = 0;

$sum_evangelismos = 0;
$sum_gran_celebracion = 0;
$satura_evangelismo_real = 0;

$sqlUser = ($idUsuarioMetas > 0) ? " sat_reportes.idUsuario = '".$idUsuarioMetas."' AND " : "";

// 1) Totales.
// Evangelismo: Solo gen 77
// Discipulado y bautizos: suma de todas columnas
$sql = "SELECT
            SUM(CASE WHEN sat_reportes.id_actividad = 77 THEN asistencia_total ELSE 0 END) as evangelismo,
            SUM(discipulado) as discipulado,
            SUM(bautizados) as bautizos
        FROM sat_reportes
        WHERE ".$sqlUser." 1 ".$sqlFiltroBaseProcesoReporte;

if($row = db_first_row($PSN, $sql)){
    $satura_evangelismo = (int)$row->f('evangelismo');
    $satura_discipulado = (int)$row->f('discipulado');
    $satura_bautizos    = (int)$row->f('bautizos');
} else {
    $varErrorG5 = 1;
}

// Conteo IPG (id_grupo = 0)
foreach([1 => 'satura_iglesias', 2 => 'satura_iglesias2', 3 => 'satura_iglesias3'] as $genN => $varName){
    $sql = "SELECT COUNT(id) as conteo FROM sat_reportes
            WHERE ".$sqlUser." id_grupo = 0 AND generacionNumero = ".$genN." ".$sqlFiltroBaseProcesoReporte;
    if($row = db_first_row($PSN, $sql)){
        $$varName = (int)$row->f('conteo');
    }
}

// Metas (usuario_metas)
$meta_evangelismo = 0;
$meta_discipulado = 0;
$meta_bautizos = 0;
$meta_iglesias = 0;
$meta_iglesias2 = 0;
$meta_iglesias3 = 0;

$aini = date('Y', strtotime($fechaInicial));
$afin = date('Y', strtotime($fechaFinal));
$num_anios = (int)$afin - (int)$aini;

if($num_anios > 0){
    $sql = "SELECT
                SUM(evangelismo) as evangelismo,
                SUM(discipulado) as discipulado,
                SUM(bautizos) as bautizos,
                SUM(iglesias) as iglesias,
                SUM(iglesias2) as iglesias2,
                SUM(iglesias3) as iglesias3
            FROM usuario_metas
            WHERE (anho >= '".$aini."' AND anho <= '".$afin."')";
} else {
    $sql = "SELECT evangelismo, discipulado, bautizos, iglesias, iglesias2, iglesias3
            FROM usuario_metas
            WHERE anho = '".$aini."'";
}
$sql .= " AND idUsuario = '".$idUsuarioMetas."'";

if($row = db_first_row($PSN, $sql)){
    $meta_evangelismo = (int)$row->f('evangelismo');
    $meta_discipulado = (int)$row->f('discipulado');
    $meta_bautizos    = (int)$row->f('bautizos');
    $meta_iglesias    = (int)$row->f('iglesias');
    $meta_iglesias2   = (int)$row->f('iglesias2');
    $meta_iglesias3   = (int)$row->f('iglesias3');
} else {
    $varErrorG5 = 1;
}

// Dataset Metas
$datosG5 = [];
if($varErrorG5 != 1){

    $datosG5[] = [
        obtenerPorcentaje($satura_evangelismo, $meta_evangelismo)."% Evangelismo",
        (int)$meta_evangelismo,
        (int)$satura_evangelismo
    ];

    $datosG5[] = [
        obtenerPorcentaje($satura_discipulado, $meta_discipulado)."% Discipulado",
        (int)$meta_discipulado,
        (int)$satura_discipulado
    ];

    $datosG5[] = [
        obtenerPorcentaje($satura_bautizos, $meta_bautizos)."% Bautizos",
        (int)$meta_bautizos,
        (int)$satura_bautizos
    ];

    $datosG5[] = [
        obtenerPorcentaje($satura_iglesias, $meta_iglesias)."% IPG Gen 1",
        (int)$meta_iglesias,
        (int)$satura_iglesias
    ];

    $datosG5[] = [
        obtenerPorcentaje($satura_iglesias2, $meta_iglesias2)."% IPG Gen 2",
        (int)$meta_iglesias2,
        (int)$satura_iglesias2
    ];

    $datosG5[] = [
        obtenerPorcentaje($satura_iglesias3, $meta_iglesias3)."% IPG Gen 3",
        (int)$meta_iglesias3,
        (int)$satura_iglesias3
    ];
}

/* =========================
   4) UI (FILTROS + LAYOUT)
   ========================= */
?>
<style>
/* ===== UI Profesional / Limpia / Entendible ===== */
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

/* Contenedores de charts */
.chart-box{ width:100%; height: 340px; }
.chart-top{ height: 360px; }
.chart-sankey{ height: 420px; }
.chart-bottom{ height: 360px; }

/* Sankey: NO SCROLL */
#chart_sankey{
  width: 100%;
  height: 420px;
  overflow: hidden;
}

/* === Igualar altura cards fila superior (Metas/Capacitación) === */
.row-eq{ display:flex; flex-wrap:wrap; }
.row-eq > [class*="col-"]{ display:flex; }

.db-card--eq{ display:flex; flex-direction:column; width:100%; }
.db-card--eq .db-card__body{ display:flex; flex-direction:column; flex:1; }
.db-card--eq .chart-box{ flex:1; min-height:360px; }

.db-summary{
  max-height:40px;
  overflow:auto;
  margin-bottom:8px;
  opacity:.9;
  font-size:12px;
  line-height:1.2;
}

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

/* Responsive */
@media (max-width: 992px){
  .chart-top{ height: 340px; }
  #chart_sankey{ height: 420px; }
}

@media (max-width: 767px){
  .db-card{ border-radius: 12px; }
  .db-card__title{ font-size: 12px; }

  .chart-box{ height: 320px; }
  .chart-top{ height: 320px; }
  #chart_sankey{ height: 460px; }
  .chart-bottom{ height: 380px; }

  .db-summary{ max-height:56px; }
  .db-card--eq .chart-box{ min-height:380px; }

  .row-eq > [class*="col-"]{
    flex: 0 0 100% !important;
    max-width: 100% !important;
    width: 100% !important;
  }

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
  form.form-horizontal .btn{
    width:100%;
  }
}
</style>

<div class="container">

<form action="index.php" method="get" name="formDashboard" class="form-horizontal">
  <input type="hidden" name="doc" value="graphs_dashboard1" />

  <div class="db-header">
    <h3 class="alert alert-info text-center" style="margin-bottom:10px;">DASHBOARD</h3>
  </div>

  <div class="cont-tit">
    <div class="hr"><hr></div>
    <div class="tit-cen">
      <h3>FILTROS DE BÚSQUEDA</h3>
    </div>
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
      <select name="empresa_paisid" class="form-control">
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
  <div class="tit-cen">
    <h3 class="text-center">RESULTADOS</h3>
  </div>
  <div class="hr"><hr></div>
</div>

<!-- 1) FILA SUPERIOR: Metas + Capacitación -->
<div class="row row-eq">

  <!-- Gráfica 5 (METAS) IZQUIERDA -->
  <div class="col-lg-6 col-md-6 col-sm-12">
    <div class="db-card db-card--eq">
      <div class="db-card__head">
        <h4 class="db-card__title">METAS</h4>
        <div class="db-card__meta">
          <button class="db-info-btn" onclick="dbOpenInfo('metas')" title="Ver descripción">i</button>
        </div>
      </div>
      <div class="db-card__body">
        <?php if($varErrorG5 == 1){ ?>
          <div class="alert alert-warning text-center" style="margin-bottom:0;">
            No se ha encontrado información de metas o registros para el rango de fechas seleccionado.
          </div>
        <?php } else { ?>
          <div id="chart_g5" class="chart-box chart-bottom"></div>
        <?php } ?>
      </div>
    </div>
  </div>

  <!-- Gráfica (CAPACITACIÓN) DERECHA -->
  <div class="col-lg-6 col-md-6 col-sm-12">
    <div class="db-card db-card--eq">
      <div class="db-card__head">
        <h4 class="db-card__title"><?=$nombreG4;?></h4>
        <div class="db-card__meta">
          <span class="db-pill">Total: <?=$totalG4;?></span>
          <button class="db-info-btn" onclick="dbOpenInfo('capacitacion')" title="Ver descripción">i</button>
        </div>
      </div>
      <div class="db-card__body">
        <?php if($varErrorG4 == 1){ ?>
          <div class="alert alert-warning text-center" style="margin-bottom:0;">
            No se ha encontrado ningún registro para el rango de fechas seleccionado.
          </div>
        <?php } else { ?>
          <div id="chart_g4" class="chart-box chart-bottom"></div>
        <?php } ?>
      </div>
    </div>
  </div>

</div>

<!-- 2) FILA CENTRAL: Sankey -->
<div class="row">

  <div class="col-lg-12 col-md-12 col-sm-12">
    <div class="db-card">
      <div class="db-card__head">
        <h4 class="db-card__title"><?=$nombreSankey;?></h4>
        <div class="db-card__meta">
          <span class="db-pill">Total: <?=$totalSankey;?></span>
          <button class="db-info-btn" onclick="dbOpenInfo('sankey')" title="Ver descripción">i</button>
        </div>
      </div>
      <div class="db-card__body">
        <?php if($varErrorSankey == 1){ ?>
          <div class="alert alert-warning text-center" style="margin-bottom:0;">
            No se ha encontrado ningún registro para el rango de fechas seleccionado.
          </div>
        <?php } else { ?>
          <div style="margin-bottom:8px; opacity:.9;"><?=$textoSankey;?></div>
          <div id="chart_sankey" class="chart-box chart-sankey"></div>
        <?php } ?>
      </div>
    </div>
  </div>

</div>

<!-- 3) FILA INFERIOR: Asistencia + Datos del Proceso -->
<div class="row">

  <div class="col-lg-6 col-md-6 col-sm-12">
    <div class="db-card">
      <div class="db-card__head">
        <h4 class="db-card__title">GRÁFICA DE ASISTENCIA</h4>
        <div class="db-card__meta">
          <span class="db-pill">Total: <?=$totalAsistencia;?></span>
          <button class="db-info-btn" onclick="dbOpenInfo('asistencia')" title="Ver descripción">i</button>
        </div>
      </div>
      <div class="db-card__body">
        <?php if($varErrorAsistencia == 1){ ?>
          <div class="alert alert-warning text-center" style="margin-bottom:0;">
            No se ha encontrado ningún registro para el rango de fechas seleccionado.
          </div>
        <?php } else { ?>
          <div id="chart_asistencia" class="chart-box chart-top"></div>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="col-lg-6 col-md-6 col-sm-12">
    <div class="db-card">
      <div class="db-card__head">
        <h4 class="db-card__title">DATOS DEL PROCESO</h4>
        <div class="db-card__meta">
          <span class="db-pill">Total: <?=$totalProceso;?></span>
          <button class="db-info-btn" onclick="dbOpenInfo('proceso')" title="Ver descripción">i</button>
        </div>
      </div>
      <div class="db-card__body">
        <?php if($varErrorProceso == 1){ ?>
          <div class="alert alert-warning text-center" style="margin-bottom:0;">
            No se ha encontrado ningún registro para el rango de fechas seleccionado.
          </div>
        <?php } else { ?>
          <div id="chart_proceso" class="chart-box chart-top"></div>
        <?php } ?>
      </div>
    </div>
  </div>

</div>

</div> <!-- /container -->

<!-- ===== Modal de información de gráficas ===== -->
<div class="db-info-overlay" id="dbInfoOverlay">
  <div class="db-info-modal">
    <button class="db-info-modal__close" id="dbInfoClose" title="Cerrar">&times;</button>
    <h4 class="db-info-modal__title" id="dbInfoTitle"></h4>
    <div class="db-info-modal__body" id="dbInfoBody"></div>
  </div>
</div>

<script type="text/javascript">
/* ===== Sistema de Info ===== */
(function(){
  var INFO = {
    'metas': {
      title: '🎯 Gráfica de Metas',
      html: '<ul>'
          + '<li><strong>📣 Evangelismo:</strong> Representa la suma total de la asistencia registrada en todos los reportes de evangelismo.</li>'
          + '<li><strong>📖 En Discipulado:</strong> Corresponde al total de personas que actualmente se encuentran en proceso de discipulado, según los reportes de coaching.</li>'
          + '<li><strong>✝️ Bautizados:</strong> Muestra la suma total de bautizados registrados en los reportes de bautismo.</li>'
          + '<li><strong>🌱 Generación 1, Generación 2 y Generación 3:</strong> Representan la cantidad total de grupos pertenecientes a cada una de estas generaciones.</li>'
          + '</ul>'
    },
    'capacitacion': {
      title: '🎓 Capacitación',
      html: '<ul>'
          + '<li><strong>👨‍🏫 Líderes Capacitados:</strong> Corresponde a la suma total de la asistencia registrada en reportes y grupos pertenecientes a la Generación 0.</li>'
          + '<li><strong>📋 Total Capacitadores:</strong> Representa la cantidad total de reportes y grupos creados que pertenecen a la Generación 0.</li>'
          + '</ul>'
    },
    'sankey': {
      title: '🔄 Flujo por Generación',
      html: '<ul>'
          + '<li><strong>📈 Flujo generacional:</strong> Esta gráfica muestra cómo los grupos han avanzado y se han multiplicado de una generación a otra, permitiendo visualizar el crecimiento y desarrollo del proceso.</li>'
          + '</ul>'
    },
    'asistencia': {
      title: '👥 Gráfica de Asistencia',
      html: '<ul>'
          + '<li><strong>📊 Distribución:</strong> Presenta la asistencia total registrada en reportes y grupos, mostrando cómo se distribuye la participación (Hombres, Mujeres, Jóvenes y Niños) dentro del proceso.</li>'
          + '</ul>'
    },
    'proceso': {
      title: '📋 Datos del Proceso',
      html: '<ul>'
          + '<li><strong>✝️ Bautizados:</strong> Suma total de bautizados registrados en los reportes de bautismo.</li>'
          + '<li><strong>📖 En Discipulado:</strong> Suma total de personas que se encuentran en proceso de discipulado registradas en reportes de coaching.</li>'
          + '<li><strong>❤️ Decisiones:</strong> Suma total de decisiones de fe registradas en reportes de evangelismo y coaching.</li>'
          + '<li><strong>🌟 Preparándose:</strong> Suma total de personas que se encuentran en proceso de preparación registradas en reportes de coaching.</li>'
          + '<li><strong>🎓 Líderes Capacitados:</strong> Suma total de la asistencia registrada en reportes y grupos pertenecientes a la Generación 0.</li>'
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

  // Cerrar con botón X
  document.getElementById('dbInfoClose').addEventListener('click', closeInfo);

  // Cerrar clickeando el overlay (fuera del modal)
  document.getElementById('dbInfoOverlay').addEventListener('click', function(e){
    if(e.target === this) closeInfo();
  });

  // Cerrar con Escape
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeInfo();
  });

  // Exponer función globalmente para los botones
  window.dbOpenInfo = openInfo;
})();
</script>

<script type="text/javascript">
google.charts.load("current", {packages:["corechart","treemap","sankey"]});
google.charts.setOnLoadCallback(drawAllCharts);

function getElWidth(id, fallback){
  var el = document.getElementById(id);
  if(!el) return fallback || 900;
  var w = el.clientWidth;
  return (w && w > 0) ? w : (fallback || 900);
}

// Redibujar en resize (responsive real) - debounce
(function(){
  var resizeTimer = null;
  window.addEventListener('resize', function(){
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function(){
      drawAllCharts();
    }, 220);
  });
  window.addEventListener('orientationchange', function(){
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function(){
      drawAllCharts();
    }, 220);
  });
})();

(function(){
  var ro = null;
  if('ResizeObserver' in window){
    ro = new ResizeObserver(function(){
      if(window.__dbRoTimer) clearTimeout(window.__dbRoTimer);
      window.__dbRoTimer = setTimeout(drawAllCharts, 220);
    });

    ['chart_asistencia','chart_proceso','chart_sankey','chart_g4','chart_g5'].forEach(function(id){
      var el = document.getElementById(id);
      if(el) ro.observe(el);
    });
  }
})();

function drawAllCharts(){
  drawAsistencia();
  drawProceso();
  drawSankey();
  drawG4();  // ahora es Capacitación
  drawG5();
}

/* ===== Sankey (compacto, sin scroll) ===== */
function drawSankey(){
  <?php if($varErrorSankey == 0){ ?>

  var el = document.getElementById('chart_sankey');
  if(!el) return;

  var rowsFull  = <?=$rowsJson;?>;
  var rowsShort = <?=$rowsJsonShort;?>;

  function buildData(rows){
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'From');
    data.addColumn('string', 'To');
    data.addColumn('number', 'Weight');
    data.addColumn({type:'string', role:'tooltip'});
    data.addRows(rows);
    return data;
  }

  var w = el.clientWidth || 900;
  var isMobile = (w <= 480);
  var data = buildData(isMobile ? rowsShort : rowsFull);

  var cfg;
  if(w <= 420){
    cfg = { height: 460, nodePadding: 14, nodeWidth: 12, fontSize: 0 };
  } else if(w <= 768){
    cfg = { height: 440, nodePadding: 22, nodeWidth: 16, fontSize: 9 };
  } else if(w <= 1024){
    cfg = { height: 420, nodePadding: 34, nodeWidth: 22, fontSize: 10 };
  } else {
    cfg = { height: 420, nodePadding: 44, nodeWidth: 26, fontSize: 10 };
  }

  var options = {
    width: w,
    height: cfg.height,
    sankey: {
      node: {
        width: cfg.nodeWidth,
        nodePadding: cfg.nodePadding,
        label: { fontSize: cfg.fontSize }
      },
      link: {}
    }
  };

  var chart = new google.visualization.Sankey(el);
  chart.draw(data, options);

  el.style.height = cfg.height + "px";
  el.style.overflow = "hidden";

  <?php } ?>
}

/* ===== Asistencia (Pie Donut) ===== */
function drawAsistencia(){
  <?php if($varErrorAsistencia == 0){ ?>
  var data = google.visualization.arrayToDataTable([
    ['Clase', 'Cantidad'],
    <?php
      $rows = [];
      foreach($datosAsistencia as $r){
        $label = str_replace("'", "\\'", $r[0]);
        $rows[] = "['".$label."', ".(int)$r[1]."]";
      }
      echo implode(",\n    ", $rows);
    ?>
  ]);

  var options = {
    pieHole: 0.4,
    sliceVisibilityThreshold: 0,
    legend: { position: 'bottom' },
    chartArea: { width: '92%', height: '82%' }
  };

  var chart = new google.visualization.PieChart(document.getElementById('chart_asistencia'));
  chart.draw(data, options);
  <?php } ?>
}

/* ===== Datos del proceso (Bar con anotación) ===== */
function drawProceso(){
  <?php if($varErrorProceso == 0){ ?>
  var data = google.visualization.arrayToDataTable([
    ['Clase', 'Cantidad', { role: 'style' }],
    <?php
      $rows = [];
      foreach($datosProceso as $r){
        $label = str_replace("'", "\\'", $r[0]);
        $rows[] = "['".$label."', ".(int)$r[1].", '".$r[2]."']";
      }
      echo implode(",\n    ", $rows);
    ?>
  ]);

  var view = new google.visualization.DataView(data);
  view.setColumns([0, 1,
    { calc: "stringify", sourceColumn: 1, type: "string", role: "annotation" },
    2
  ]);

  var options = {
    chartArea: { width: '78%', height: '75%' },
    bar: { groupWidth: "80%" },
    legend: { position: 'none' }
  };

  var chart = new google.visualization.BarChart(document.getElementById('chart_proceso'));
  chart.draw(view, options);
  <?php } ?>
}

/* ===== (REEMPLAZO G4) Capacitación (ColumnChart) ===== */
function drawG4(){
  <?php if($varErrorG4 == 0){ ?>
  var data = google.visualization.arrayToDataTable([
    ['Tipo', 'Cantidad'],
    <?php
      $rows = [];
      foreach($datosG4 as $r){
        $label = str_replace("'", "\\'", $r[0]);
        $rows[] = "['".$label."', ".(int)$r[1]."]";
      }
      echo implode(",\n    ", $rows);
    ?>
  ]);

  var options = {
    animation:{
      startup: true,
      duration: 2000,
      easing: 'out'
    },
    colors: ['limegreen', '#00FBFF'],
    legend: { position: 'none' },
    chartArea: { width: '82%', height: '75%' }
  };

  var chart = new google.visualization.ColumnChart(document.getElementById('chart_g4'));
  chart.draw(data, options);
  <?php } ?>
}

/* ===== Gráfica 5: Metas (Bar Meta vs Actual) ===== */
function drawG5(){
  <?php if($varErrorG5 == 0){ ?>

  var el = document.getElementById('chart_g5');
  if(!el) return;

  var w = el.clientWidth || 600;
  var isMobile = (w <= 480);

  var data = google.visualization.arrayToDataTable([
    ['Nombre', 'Meta', 'Actual'],
    <?php
      $rows = [];
      foreach($datosG5 as $r){
        $label = str_replace("'", "\\'", $r[0]);
        $rows[] = "['".$label."', ".(int)$r[1].", ".(int)$r[2]."]";
      }
      echo implode(",\n    ", $rows);
    ?>
  ]);

  var view = new google.visualization.DataView(data);
  view.setColumns([
    0,
    1,
    { calc: "stringify", sourceColumn: 1, type: "string", role: "annotation" },
    2,
    { calc: "stringify", sourceColumn: 2, type: "string", role: "annotation" }
  ]);

  var options = {
    chartArea: isMobile ? { width: '88%', height: '78%' } : { width: '72%', height: '82%' },
    bar: { groupWidth: isMobile ? "80%" : "92%" },
    legend: { position: 'none' },
    colors: ['limegreen', 'crimson'],

    hAxis: { textStyle: { fontSize: isMobile ? 10 : 12 } },
    vAxis: { textStyle: { fontSize: isMobile ? 10 : 12 } },
    annotations: { textStyle: { fontSize: isMobile ? 9 : 11 } }
  };

  var chart = new google.visualization.BarChart(el);
  chart.draw(view, options);

  <?php } ?>
}
</script>