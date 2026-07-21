<?php
/*******************************************
DASHBOARD 3 - 3 VISUALES (REDISEÑO)
Archivo: graphs_dashboard3.php

#1: ACTIVIDADES ESPECIALES (id_actividad IN 5,10,11,12,13,14)
    - PieChart: frecuencia de actividades poco comunes

#2: MADUREZ ESPIRITUAL (mapeo_*, solo reportes de Coach id_actividad=1)
    - ColumnChart: promedio (escala 1-4) de cada área de madurez

#3: CRECIMIENTO ACUMULADO DE PERSONAS ALCANZADAS
    - LineChart: suma acumulada de asistencia_total mes a mes

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
   #2: ACTIVIDADES ESPECIALES
   ========================= */
$varErrorEspeciales = 0;
$nombreEspeciales = "ACTIVIDADES ESPECIALES";
$totalEspeciales = 0;
$datosEspeciales = [];

$nombresActividad = [
    5  => 'Otra',
    10 => 'Siembra abundante',
    11 => 'Caminata de oración',
    12 => 'Identificar al hijo de paz',
    13 => 'Oración Exp y Ferviente',
    14 => 'Taller',
];

$sql = "SELECT
            sat_reportes.id_actividad,
            COUNT(sat_reportes.id) as conteo
        FROM sat_reportes
        LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = sat_reportes.idUsuario
        WHERE sat_reportes.id_grupo > 0
          AND sat_reportes.id_actividad IN (5, 10, 11, 12, 13, 14)
          ".$sqlFiltroBase."
        GROUP BY sat_reportes.id_actividad
        ORDER BY conteo DESC";

$PSN->query($sql);
if($PSN->num_rows() > 0){
    while($PSN->next_record()){
        $idAct  = (int)$PSN->f('id_actividad');
        $conteo = (int)$PSN->f('conteo');
        $nombre = isset($nombresActividad[$idAct]) ? $nombresActividad[$idAct] : "Otra";

        $datosEspeciales[] = [$nombre, $conteo];
        $totalEspeciales += $conteo;
    }
    if($totalEspeciales <= 0) $varErrorEspeciales = 1;
} else {
    $varErrorEspeciales = 1;
}

/* =========================
   #3: CRECIMIENTO ACUMULADO DE PERSONAS ALCANZADAS
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

<!-- #1, #2 y #3 -->
<div class="row">
  <div class="col-lg-4 col-md-6 col-sm-12">
    <div class="db-card">
      <div class="db-card__head">
        <div class="db-card__title-wrap">
          <h4 class="db-card__title"><?=$nombreEspeciales;?></h4>
          <button class="db-info-btn" onclick="dbOpenInfo('especiales')" type="button" title="Ver descripción">i</button>
        </div>
        <div class="db-card__meta">
          <?php if($varErrorEspeciales == 0){ ?><span class="db-pill">Total: <?=$totalEspeciales;?></span><?php } ?>
        </div>
      </div>
      <div class="db-card__body">
        <?php if($varErrorEspeciales == 1){ ?>
          <div class="alert alert-warning text-center" style="margin-bottom:0;">
            No se ha encontrado ningún registro de actividades especiales para el rango de fechas seleccionado.
          </div>
        <?php } else { ?>
          <div id="chart_especiales" class="chart-box"></div>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4 col-md-6 col-sm-12">
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

  <!-- Comentado: no debe aparecer por ahora - CRECIMIENTO ACUMULADO DE PERSONAS ALCANZADAS
  <div class="col-lg-4 col-md-6 col-sm-12">
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
  -->
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
    ['chart_madurez','chart_especiales','chart_crecimiento'].forEach(function(id){
      var el = document.getElementById(id);
      if(el) ro.observe(el);
    });
  }
})();

function drawAllCharts(){
  drawEspeciales();
  drawMadurez();
  // drawCrecimiento(); // Comentado: no debe aparecer por ahora
}

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

/* ===== #2 Actividades Especiales (PieChart) ===== */
function drawEspeciales(){
  <?php if($varErrorEspeciales == 0){ ?>
  var data = google.visualization.arrayToDataTable([
    ['Actividad', 'Cantidad'],
    <?php
      $rows = [];
      foreach($datosEspeciales as $r){
        $label = str_replace("'", "\\'", $r[0]);
        $rows[] = "['".$label."', ".(int)$r[1]."]";
      }
      echo implode(",\n    ", $rows);
    ?>
  ]);

  var el = document.getElementById('chart_especiales');
  if(!el) return;
  var w = el.clientWidth || 600;
  var isMobile = (w <= 480);

  var options = {
    pieHole: 0.45,
    sliceVisibilityThreshold: 0,
    colors: ['#0259a5','#27ae60','#f39c12','#8e44ad','#e74c3c','#16a085'],
    legend: { position: 'bottom', textStyle: { fontSize: isMobile ? 10 : 12 } },
    chartArea: isMobile ? { width:'96%', height:'78%' } : { width:'94%', height:'80%' },
    tooltip: { text: 'both' }
  };

  new google.visualization.PieChart(el).draw(data, options);
  <?php } ?>
}

/* ===== #3 Crecimiento Acumulado de Personas Alcanzadas (LineChart) ===== */
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

  var options = {
    animation:{ startup:true, duration:1000, easing:'out' },
    curveType: 'function',
    colors: ['#8e44ad'],
    lineWidth: 3,
    pointSize: 0,
    areaOpacity: 0.15,
    legend: { position: 'none' },
    chartArea: isMobile ? { width:'90%', height:'68%' } : { width:'88%', height:'74%' },
    hAxis: { textStyle: { fontSize: isMobile ? 10 : 12 }, slantedText: true, slantedTextAngle: isMobile ? 45 : 30 },
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
    'especiales': {
      title: '🎉 Actividades Especiales',
      html: '<ul>'
          + '<li><strong>📌 Actividades:</strong> Frecuencia de actividades poco comunes registradas: Siembra abundante, Caminata de oración, Identificar al hijo de paz, Oración Expectante y Ferviente, Taller y Otra.</li>'
          + '<li><strong>💡 Úsala para:</strong> ver qué tipo de actividades complementarias se están realizando además del proceso regular de grupos.</li>'
          + '</ul>'
    },
    'crecimiento': {
      title: '📈 Crecimiento Acumulado de Personas Alcanzadas',
      html: '<ul>'
          + '<li><strong>👥 Acumulado:</strong> Suma, mes a mes, el total de personas alcanzadas (asistencia) desde el inicio del rango de fechas seleccionado.</li>'
          + '<li><strong>💡 Úsala para:</strong> visualizar el alcance total del movimiento a lo largo del tiempo, sin importar si hubo meses con menor actividad.</li>'
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
