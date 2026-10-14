<?php

/*******************************************
DASHBOARD DE INVENTARIO DE FACILITADORES
Estado, regional, último reporte, cantidad de reportes,
datos completos y actividad en el tiempo (Google Charts).
 *******************************************/

// Solo usuarios con sesión y perfil de administración/coordinación (162, 167) o superusuario
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "" || ($_SESSION["superusuario"] != 1 && $_SESSION["perfil"] != 167 && $_SESSION["perfil"] != 162)) {
  die("<h5 class='alert alert-danger text-center'>NO esta autorizado a ver esta grafica</h5>");
}

$PSN = new DBbase_Sql;
$PSN2 = new DBbase_Sql;

$mesesNom = array("", "Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic");

if (!function_exists('h')) {
  function h($s)
  {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  }
}

/*
*   FILTROS (todos se convierten a entero o se validan contra lista blanca)
*/
$verTodasRegionales = ($_SESSION["perfil"] == 167 || $_SESSION["superusuario"] == 1 || intval($_SESSION["empresa_pd"]) == 0);
$buscar_regional = isset($_REQUEST["empresa_pd"]) ? intval($_REQUEST["empresa_pd"]) : 0;
if (!$verTodasRegionales) {
  // Usuarios de regional solo ven su regional
  $buscar_regional = intval($_SESSION["empresa_pd"]);
}
$buscar_estado = (isset($_REQUEST["estado"]) && in_array($_REQUEST["estado"], array("1", "0"), true)) ? $_REQUEST["estado"] : "";

$sqlFiltro = "";
if ($buscar_regional > 0) {
  $sqlFiltro .= " AND UE.empresa_pd = " . $buscar_regional;
}
if ($buscar_estado !== "") {
  $sqlFiltro .= " AND U.acceso = " . intval($buscar_estado);
}

// Reportes de los tres modelos de datos (LPP, CM y sat_reportes) unidos por usuario
$sqlReportes = "SELECT usuario_id AS uid, fecha_reporte AS f FROM reporte_lpp
      UNION ALL
      SELECT usuario_id, fecha_reporte FROM reporte_cm WHERE inactivo = 0
      UNION ALL
      SELECT idUsuario, fechaReporte FROM sat_reportes WHERE inactivo = 0";

/*
*   INVENTARIO: una sola consulta (sin N+1)
*/
$sql = "SELECT U.id, U.nombre, U.acceso, U.identificacion, U.email, U.celular, U.direccion, U.usua_muni,
  C.descripcion AS regional,
  R.total_reportes, R.ultimo_reporte
  FROM usuario AS U
  LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
  LEFT JOIN categorias AS C ON C.id = UE.empresa_pd
  LEFT JOIN (
    SELECT uid, COUNT(*) AS total_reportes, MAX(f) AS ultimo_reporte
    FROM (" . $sqlReportes . ") AS X
    GROUP BY uid
  ) AS R ON R.uid = U.id
  WHERE U.tipo IN (162, 163, 167) " . $sqlFiltro . "
  GROUP BY U.id
  ORDER BY R.ultimo_reporte DESC, U.nombre ASC";
$PSN->query($sql);

$hoy = new DateTime(date("Y-m-d"));
$facilitadores = array();
$tot_activos = $tot_completos = $tot_reportes = 0;
// Actividad según días desde el último reporte
$actividad = array("≤ 30 días" => 0, "31 - 90 días" => 0, "91 - 180 días" => 0, "> 180 días" => 0, "Sin reportes" => 0);
// Conteo por regional: [activos, inactivos]
$porRegional = array();

while ($PSN->next_record()) {
  // Datos completos: campos requeridos del perfil
  $campos = array(
    "nombre" => trim($PSN->f('nombre')),
    "identificación" => trim($PSN->f('identificacion')),
    "correo" => trim($PSN->f('email')),
    "celular" => trim($PSN->f('celular')),
    "dirección" => trim($PSN->f('direccion')),
    "municipio" => intval($PSN->f('usua_muni')) > 0 ? "x" : "",
  );
  $faltantes = array();
  foreach ($campos as $k => $v) {
    if ($v === "") {
      $faltantes[] = $k;
    }
  }
  $activo = intval($PSN->f('acceso')) == 1;
  $total = intval($PSN->f('total_reportes'));
  $ultimo = $PSN->f('ultimo_reporte');
  $regional = $PSN->f('regional') != "" ? $PSN->f('regional') : "Sin regional";

  if ($activo) {
    $tot_activos++;
  }
  if (empty($faltantes)) {
    $tot_completos++;
  }
  $tot_reportes += $total;

  $dias = null;
  if ($total > 0 && $ultimo != "") {
    $dias = max(0, intval((new DateTime($ultimo))->diff($hoy)->format("%r%a")));
    if ($dias <= 30) {
      $actividad["≤ 30 días"]++;
    } else if ($dias <= 90) {
      $actividad["31 - 90 días"]++;
    } else if ($dias <= 180) {
      $actividad["91 - 180 días"]++;
    } else {
      $actividad["> 180 días"]++;
    }
  } else {
    $actividad["Sin reportes"]++;
  }

  if (!isset($porRegional[$regional])) {
    $porRegional[$regional] = array(0, 0);
  }
  $porRegional[$regional][$activo ? 0 : 1]++;

  $facilitadores[] = array(
    "id" => intval($PSN->f('id')),
    "nombre" => $PSN->f('nombre'),
    "regional" => $regional,
    "activo" => $activo,
    "total" => $total,
    "ultimo" => $ultimo,
    "dias" => $dias,
    "faltantes" => $faltantes,
  );
}
$tot_facilitadores = count($facilitadores);
$tot_inactivos = $tot_facilitadores - $tot_activos;
$pct_activos = $tot_facilitadores > 0 ? round($tot_activos * 100 / $tot_facilitadores) : 0;
$prom_reportes = $tot_facilitadores > 0 ? round($tot_reportes / $tot_facilitadores, 1) : 0;
ksort($porRegional);

/*
*   ACTIVIDAD EN EL TIEMPO (últimos 12 meses): reportes y facilitadores que reportaron.
*   Se filtra con IN (subconsulta) para no duplicar conteos por usuario_empresa.
*/
$reportesMes = array();
$inicioMeses = new DateTime(date("Y-m-01"));
$inicioMeses->modify("-11 months");
$cursor = clone $inicioMeses;
for ($i = 0; $i < 12; $i++) {
  $reportesMes[$cursor->format("Y-m")] = array($mesesNom[intval($cursor->format("n"))] . " " . $cursor->format("y"), 0, 0);
  $cursor->modify("+1 month");
}
if ($tot_facilitadores > 0) {
  $sql = "SELECT DATE_FORMAT(X.f, '%Y-%m') AS mes, COUNT(*) AS reportes, COUNT(DISTINCT X.uid) AS facilitadores
    FROM (" . $sqlReportes . ") AS X
    WHERE X.f >= '" . $inicioMeses->format("Y-m-d") . "'
      AND X.uid IN (
        SELECT U.id FROM usuario AS U
        LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
        WHERE U.tipo IN (162, 163, 167) " . $sqlFiltro . "
      )
    GROUP BY mes";
  $PSN->query($sql);
  while ($PSN->next_record()) {
    if (isset($reportesMes[$PSN->f('mes')])) {
      $reportesMes[$PSN->f('mes')][1] = intval($PSN->f('reportes'));
      $reportesMes[$PSN->f('mes')][2] = intval($PSN->f('facilitadores'));
    }
  }
}
// Facilitadores que reportaron en el mes en curso (último mes de la serie)
$fac_mes_actual = $reportesMes[date("Y-m")][2];
$nombresMesLargo = array("", "enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre");
$nombreMesActual = $nombresMesLargo[intval(date("n"))];

/*
*   DATOS PARA GOOGLE CHARTS
*/
$dataMes = array(array("Mes", "Reportes", "Facilitadores que reportaron"));
foreach ($reportesMes as $m) {
  $dataMes[] = $m;
}
$dataActividad = array(array("Último reporte", "Facilitadores", array("role" => "style")));
$coloresActividad = array("#239B56", "#2E86C1", "#F39C12", "#C0392B", "#BDBDBD");
$i = 0;
foreach ($actividad as $k => $v) {
  $dataActividad[] = array($k, $v, $coloresActividad[$i++]);
}
$dataRegional = array(array("Regional", "Activos", "Inactivos"));
foreach ($porRegional as $k => $v) {
  $dataRegional[] = array($k, $v[0], $v[1]);
}
$alturaRegional = max(220, count($porRegional) * 34 + 60);

$jsonOpts = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

?>
<style>
  /* Estilos exclusivos de este dashboard (prefijo .dash-fac) */
  .dash-fac {
    padding-top: 10px;
    padding-bottom: 30px;
    color: #333;
  }

  .dash-fac .dash-head {
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
  }

  .dash-fac .dash-head h3 {
    margin: 0 0 4px;
    font-weight: bold;
  }

  .dash-fac .dash-head p {
    margin: 0;
    color: #888;
  }

  .dash-fac .dash-filtros select {
    margin-bottom: 8px;
  }

  .dash-fac .card {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 8px;
    padding: 24px 26px;
    margin-bottom: 28px;
  }

  .dash-fac .card-title {
    font-size: 16px;
    font-weight: bold;
    margin: 0;
  }

  .dash-fac .card-sub {
    font-size: 12px;
    color: #999;
    margin: 4px 0 20px;
  }

  /* Filas con tarjetas de igual altura */
  .dash-fac .row-eq {
    display: flex;
    flex-wrap: wrap;
  }

  .dash-fac .row-eq > [class*="col-"] {
    display: flex;
    flex-direction: column;
  }

  .dash-fac .row-eq .card {
    flex: 1;
  }

  /* Indicadores */
  .dash-fac .kpi {
    min-height: 130px;
    padding: 20px 22px;
  }

  .dash-fac .kpi-label {
    font-size: 12px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: .5px;
  }

  .dash-fac .kpi-num {
    font-size: 32px;
    font-weight: bold;
    line-height: 1.2;
    margin: 4px 0;
  }

  .dash-fac .kpi-det {
    font-size: 12px;
    color: #888;
  }

  .dash-fac .kpi-bar {
    height: 4px;
    background: #eee;
    border-radius: 2px;
    margin-top: 8px;
    overflow: hidden;
  }

  .dash-fac .kpi-bar div {
    height: 100%;
  }

  /* Tabla */
  .dash-fac .tabla-fac {
    margin-bottom: 0;
  }

  .dash-fac .tabla-fac th {
    font-size: 12px;
    color: #888;
    font-weight: normal;
    text-transform: uppercase;
    border-bottom: 1px solid #e8e8e8 !important;
  }

  .dash-fac .tabla-fac td {
    vertical-align: middle !important;
    border-top: 1px solid #f2f2f2 !important;
  }

  .dash-fac .tabla-fac .sub {
    display: block;
    font-size: 12px;
    color: #999;
  }

  .dash-fac .punto {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 6px;
  }

  .dash-fac .dash-pag {
    margin-top: 12px;
    font-size: 13px;
    color: #888;
  }

  .dash-fac .dash-pag .btn {
    margin-left: 4px;
  }

  .dash-fac .chips {
    margin: 4px 0 16px;
  }

  .dash-fac .chips .btn {
    margin: 0 4px 6px 0;
    border-radius: 14px;
  }
</style>

<div class="container dash-fac">

  <!-- ENCABEZADO + FILTROS -->
  <div class="row dash-head">
    <div class="col-md-6 col-xs-12">
      <h3>Facilitadores</h3>
      <p>Inventario, estado y actividad de reportes</p>
    </div>
    <form action="index.php" method="get" name="form1" class="col-md-6 col-xs-12 dash-filtros">
      <input type="hidden" name="doc" value="grafica_facilitadores" />
      <div class="row">
        <div class="col-sm-6 col-xs-12">
          <select name="empresa_pd" class="form-control" onchange="this.form.submit()" title="Regional">
            <?php
            if ($verTodasRegionales) {
              echo '<option value="">Todas las regionales</option>';
            }
            $sql = "SELECT C.id, C.descripcion AS regional FROM categorias AS C
                    LEFT JOIN categorias AS CA ON CA.id = C.idSec
                    WHERE CA.idSec = 85 ";
            if (!$verTodasRegionales) {
              $sql .= " AND C.id = " . intval($_SESSION["empresa_pd"]);
            }
            $sql .= " ORDER BY C.descripcion ASC";
            $PSN2->query($sql);
            while ($PSN2->next_record()) { ?>
              <option value="<?= intval($PSN2->f('id')); ?>" <?= ($buscar_regional == $PSN2->f('id')) ? 'selected="selected"' : ''; ?>><?= h($PSN2->f('regional')); ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="col-sm-6 col-xs-12">
          <select name="estado" class="form-control" onchange="this.form.submit()" title="Estado">
            <option value="">Activos e inactivos</option>
            <option value="1" <?= $buscar_estado === "1" ? 'selected' : ''; ?>>Solo activos</option>
            <option value="0" <?= $buscar_estado === "0" ? 'selected' : ''; ?>>Solo inactivos</option>
          </select>
        </div>
      </div>
    </form>
  </div>

  <?php if ($tot_facilitadores == 0) { ?>
    <div class="card text-center text-muted">No se ha encontrado ningún facilitador con los filtros seleccionados.</div>
  <?php } else { ?>

    <!-- 1. INDICADORES -->
    <div class="row">
      <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="card kpi">
          <div class="kpi-label">Facilitadores</div>
          <div class="kpi-num"><?= $tot_facilitadores; ?></div>
          <div class="kpi-det"><?= $tot_activos; ?> activos · <?= $tot_inactivos; ?> inactivos</div>
          <div class="kpi-bar">
            <div style="width: <?= $pct_activos; ?>%; background:#239B56"></div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="card kpi">
          <div class="kpi-label">Reportaron este mes</div>
          <div class="kpi-num"><?= $fac_mes_actual; ?></div>
          <div class="kpi-det">facilitadores con reportes en <?= $nombreMesActual; ?></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="card kpi">
          <div class="kpi-label">Reportes</div>
          <div class="kpi-num"><?= number_format($tot_reportes); ?></div>
          <div class="kpi-det"><?= $prom_reportes; ?> en promedio por facilitador</div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="card kpi">
          <div class="kpi-label">Sin reportes</div>
          <div class="kpi-num"><?= $actividad["Sin reportes"]; ?></div>
          <div class="kpi-det">facilitadores que nunca han reportado</div>
        </div>
      </div>
    </div>

    <!-- 2. ACTIVIDAD EN EL TIEMPO + ULTIMO REPORTE (misma altura fija) -->
    <div class="row row-eq">
      <div class="col-md-7 col-xs-12">
        <div class="card">
          <p class="card-title">Actividad en el tiempo</p>
          <p class="card-sub">Reportes y facilitadores que reportaron, mes a mes (últimos 12 meses)</p>
          <div id="chart_tiempo" style="width:100%; height:320px;"></div>
        </div>
      </div>
      <div class="col-md-5 col-xs-12">
        <div class="card">
          <p class="card-title">Último reporte</p>
          <p class="card-sub">Cuántos facilitadores reportaron en cada rango de tiempo</p>
          <div id="chart_actividad" style="width:100%; height:320px;"></div>
        </div>
      </div>
    </div>

    <!-- 3. REGIONALES (altura según cantidad de regionales) -->
    <div class="card">
      <p class="card-title">Facilitadores por regional</p>
      <p class="card-sub">Activos e inactivos</p>
      <div id="chart_regional" style="width:100%; height: <?= $alturaRegional; ?>px;"></div>
    </div>

    <!-- 4. INVENTARIO -->
    <div class="card">
      <div class="row">
        <div class="col-sm-6 col-xs-12">
          <p class="card-title">Inventario</p>
          <p class="card-sub">Del reporte más reciente al más antiguo</p>
        </div>
        <div class="col-sm-6 col-xs-12">
          <input type="text" id="buscarFacilitador" class="form-control" placeholder="Buscar facilitador o regional" />
        </div>
      </div>
      <div class="chips" id="chipsFiltro">
        <button type="button" class="btn btn-sm btn-primary" data-filtro="">Todos</button>
        <button type="button" class="btn btn-sm btn-default" data-filtro="sin-reportes">Sin reportes</button>
        <button type="button" class="btn btn-sm btn-default" data-filtro="atrasado">Más de 90 días</button>
        <button type="button" class="btn btn-sm btn-default" data-filtro="incompleto">Perfil incompleto</button>
      </div>
      <div class="table-responsive">
        <table class="table tabla-fac" id="tablaFacilitadores">
          <thead>
            <tr>
              <th>Facilitador</th>
              <th>Estado</th>
              <th>Último reporte</th>
              <th class="text-center">Reportes</th>
              <th>Perfil</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($facilitadores as $f) {
              $etiquetas = array();
              if ($f["dias"] === null) {
                $colorDias = "#BDBDBD";
                $textoDias = "Sin reportes";
                $etiquetas[] = "sin-reportes";
              } else {
                $colorDias = $f["dias"] <= 30 ? "#239B56" : ($f["dias"] <= 90 ? "#2E86C1" : ($f["dias"] <= 180 ? "#F39C12" : "#C0392B"));
                $textoDias = $f["dias"] == 0 ? "Hoy" : "Hace " . $f["dias"] . " días";
                if ($f["dias"] > 90) {
                  $etiquetas[] = "atrasado";
                }
              }
              if (!empty($f["faltantes"])) {
                $etiquetas[] = "incompleto";
              }
            ?>
              <tr data-etiquetas="<?= implode(" ", $etiquetas); ?>">
                <td>
                  <a href="index.php?doc=usuario&id=<?= $f["id"]; ?>"><?= h($f["nombre"]); ?></a>
                  <span class="sub"><?= h($f["regional"]); ?></span>
                </td>
                <td>
                  <span class="punto" style="background: <?= $f["activo"] ? '#239B56' : '#C0392B'; ?>"></span><?= $f["activo"] ? 'Activo' : 'Inactivo'; ?>
                </td>
                <td>
                  <span class="punto" style="background: <?= $colorDias; ?>"></span><?= $textoDias; ?>
                  <?php if ($f["dias"] !== null) { ?><span class="sub"><?= h($f["ultimo"]); ?></span><?php } ?>
                </td>
                <td class="text-center"><?= $f["total"]; ?></td>
                <td>
                  <?php if (empty($f["faltantes"])) { ?>
                    <span class="text-success">Completo</span>
                  <?php } else { ?>
                    <span class="text-warning" title="Falta: <?= h(implode(", ", $f["faltantes"])); ?>">Falta <?= count($f["faltantes"]); ?> dato<?= count($f["faltantes"]) > 1 ? 's' : ''; ?></span>
                    <span class="sub"><?= h(implode(", ", $f["faltantes"])); ?></span>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
      <p id="sinCoincidencias" class="text-muted text-center" style="display:none; margin-top:12px">No hay facilitadores que coincidan.</p>
      <div class="dash-pag clearfix">
        <span id="pagInfo"></span>
        <div class="pull-right">
          <button type="button" class="btn btn-default btn-sm" id="pagAnt">&laquo; Anterior</button>
          <button type="button" class="btn btn-default btn-sm" id="pagSig">Siguiente &raquo;</button>
        </div>
      </div>
    </div>

    <script type="text/javascript">
      google.charts.load("current", {
        packages: ["corechart"]
      });
      google.charts.setOnLoadCallback(dibujarDashboard);

      var datosMes = <?= json_encode($dataMes, $jsonOpts); ?>;
      var datosActividad = <?= json_encode($dataActividad, $jsonOpts); ?>;
      var datosRegional = <?= json_encode($dataRegional, $jsonOpts); ?>;

      // Estilo común minimalista para los ejes
      var ejeTexto = {color: "#999", fontSize: 11};
      var ejeLimpio = {textStyle: ejeTexto, gridlines: {color: "#f0f0f0"}, baselineColor: "#e0e0e0", minorGridlines: {count: 0}};

      function dibujarDashboard() {
        // Actividad en el tiempo: columnas (reportes) + línea (facilitadores que reportaron)
        var dataMes = google.visualization.arrayToDataTable(datosMes);
        new google.visualization.ComboChart(document.getElementById("chart_tiempo")).draw(dataMes, {
          seriesType: "bars",
          series: {
            0: {color: "#AED6F1"},
            1: {type: "line", color: "#2E86C1", lineWidth: 3, pointSize: 6, targetAxisIndex: 1}
          },
          vAxes: {
            0: $.extend({}, ejeLimpio, {format: "0", minValue: 0, title: "Reportes", titleTextStyle: ejeTexto}),
            1: $.extend({}, ejeLimpio, {format: "0", minValue: 0, gridlines: {count: 0}, title: "Facilitadores", titleTextStyle: ejeTexto})
          },
          hAxis: {textStyle: ejeTexto},
          legend: {position: "top", alignment: "end", textStyle: {color: "#666", fontSize: 12}},
          chartArea: {left: 55, right: 55, top: 35, bottom: 35},
          bar: {groupWidth: "55%"}
        });

        // Último reporte: columnas por rango de días, con cantidad y porcentaje encima
        var dataAct = google.visualization.arrayToDataTable(datosActividad);
        var totalAct = 0;
        for (var i = 0; i < dataAct.getNumberOfRows(); i++) {
          totalAct += dataAct.getValue(i, 1);
        }
        var vistaAct = new google.visualization.DataView(dataAct);
        vistaAct.setColumns([0, 1, 2, {
          type: "string",
          role: "annotation",
          calc: function(dt, fila) {
            var v = dt.getValue(fila, 1);
            return v + (totalAct > 0 ? " (" + Math.round(v * 100 / totalAct) + "%)" : "");
          }
        }]);
        new google.visualization.ColumnChart(document.getElementById("chart_actividad")).draw(vistaAct, {
          legend: {position: "none"},
          vAxis: $.extend({}, ejeLimpio, {format: "0", minValue: 0}),
          hAxis: {textStyle: {color: "#666", fontSize: 12}, maxAlternation: 1, slantedText: false},
          annotations: {alwaysOutside: true, textStyle: {color: "#555", fontSize: 12}},
          chartArea: {left: 40, right: 15, top: 30, bottom: 40},
          bar: {groupWidth: "55%"}
        });

        // Por regional: barras apiladas activos / inactivos
        var dataReg = google.visualization.arrayToDataTable(datosRegional);
        new google.visualization.BarChart(document.getElementById("chart_regional")).draw(dataReg, {
          isStacked: true,
          colors: ["#239B56", "#E6B0AA"],
          legend: {position: "top", alignment: "end", textStyle: {color: "#666", fontSize: 12}},
          hAxis: $.extend({}, ejeLimpio, {format: "0", minValue: 0}),
          vAxis: {textStyle: {color: "#666", fontSize: 12}},
          chartArea: {left: 170, right: 20, top: 30, bottom: 30},
          bar: {groupWidth: "60%"}
        });
      }

      // Redibujar al cambiar el tamaño de la ventana (responsive)
      var timerResize;
      $(window).on("resize", function() {
        clearTimeout(timerResize);
        timerResize = setTimeout(dibujarDashboard, 250);
      });

      /*
       *  INVENTARIO: búsqueda, filtros rápidos y paginación (10 por página)
       */
      var porPagina = 10,
        paginaActual = 1,
        filtroActual = "";
      var $filas = $("#tablaFacilitadores tbody tr");

      function filasVisibles() {
        var texto = $("#buscarFacilitador").val().toLowerCase();
        return $filas.filter(function() {
          var okTexto = $(this).children("td").first().text().toLowerCase().indexOf(texto) > -1;
          var okFiltro = filtroActual === "" || (" " + $(this).data("etiquetas") + " ").indexOf(" " + filtroActual + " ") > -1;
          return okTexto && okFiltro;
        });
      }

      function pintarTabla() {
        var $vis = filasVisibles();
        var total = $vis.length;
        var paginas = Math.max(1, Math.ceil(total / porPagina));
        paginaActual = Math.min(paginaActual, paginas);
        var desde = (paginaActual - 1) * porPagina;

        $filas.hide();
        $vis.slice(desde, desde + porPagina).show();

        $("#sinCoincidencias").toggle(total === 0);
        $("#pagInfo").text(total === 0 ? "" : "Mostrando " + (desde + 1) + "–" + Math.min(desde + porPagina, total) + " de " + total);
        $("#pagAnt").prop("disabled", paginaActual <= 1);
        $("#pagSig").prop("disabled", paginaActual >= paginas);
      }

      $("#buscarFacilitador").on("keyup", function() {
        paginaActual = 1;
        pintarTabla();
      });
      $("#chipsFiltro .btn").on("click", function() {
        $("#chipsFiltro .btn").removeClass("btn-primary").addClass("btn-default");
        $(this).removeClass("btn-default").addClass("btn-primary");
        filtroActual = $(this).data("filtro");
        paginaActual = 1;
        pintarTabla();
      });
      $("#pagAnt").on("click", function() {
        paginaActual--;
        pintarTabla();
      });
      $("#pagSig").on("click", function() {
        paginaActual++;
        pintarTabla();
      });
      pintarTabla();
    </script>
  <?php } ?>
</div>
