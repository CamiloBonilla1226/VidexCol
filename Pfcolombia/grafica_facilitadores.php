<?php

/*******************************************
DASHBOARD DE INVENTARIO DE FACILITADORES
Estado, regional, último reporte, cantidad de reportes,
datos completos y línea de tiempo (Google Charts).
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
  R.total_reportes, R.primer_reporte, R.ultimo_reporte
  FROM usuario AS U
  LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
  LEFT JOIN categorias AS C ON C.id = UE.empresa_pd
  LEFT JOIN (
    SELECT uid, COUNT(*) AS total_reportes, MIN(f) AS primer_reporte, MAX(f) AS ultimo_reporte
    FROM (" . $sqlReportes . ") AS X
    GROUP BY uid
  ) AS R ON R.uid = U.id
  WHERE U.tipo IN (162, 163, 167) " . $sqlFiltro . "
  GROUP BY U.id
  ORDER BY R.ultimo_reporte DESC, U.nombre ASC";
$PSN->query($sql);

$hoy = new DateTime(date("Y-m-d"));
$facilitadores = array();
$tot_activos = $tot_inactivos = $tot_completos = $tot_reportes = 0;
// Actividad según días desde el último reporte
$actividad = array("Al día (≤ 30 días)" => 0, "31 a 90 días" => 0, "91 a 180 días" => 0, "Más de 180 días" => 0, "Sin reportes" => 0);
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

  $activo ? $tot_activos++ : $tot_inactivos++;
  if (empty($faltantes)) {
    $tot_completos++;
  }
  $tot_reportes += $total;

  $dias = null;
  if ($total > 0 && $ultimo != "") {
    $dias = intval($hoy->diff(new DateTime($ultimo))->format("%r%a")) * -1;
    if ($dias <= 30) {
      $actividad["Al día (≤ 30 días)"]++;
    } else if ($dias <= 90) {
      $actividad["31 a 90 días"]++;
    } else if ($dias <= 180) {
      $actividad["91 a 180 días"]++;
    } else {
      $actividad["Más de 180 días"]++;
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
    "primero" => $PSN->f('primer_reporte'),
    "ultimo" => $ultimo,
    "dias" => $dias,
    "faltantes" => $faltantes,
  );
}
$tot_facilitadores = count($facilitadores);
$tot_incompletos = $tot_facilitadores - $tot_completos;
$pct_activos = $tot_facilitadores > 0 ? round($tot_activos * 100 / $tot_facilitadores) : 0;
$pct_completos = $tot_facilitadores > 0 ? round($tot_completos * 100 / $tot_facilitadores) : 0;
$prom_reportes = $tot_facilitadores > 0 ? round($tot_reportes / $tot_facilitadores, 1) : 0;
ksort($porRegional);

/*
*   REPORTES POR MES (últimos 12 meses) de los facilitadores del filtro
*/
$reportesMes = array();
$inicioMeses = new DateTime(date("Y-m-01"));
$inicioMeses->modify("-11 months");
$cursor = clone $inicioMeses;
for ($i = 0; $i < 12; $i++) {
  $reportesMes[$cursor->format("Y-m")] = array("label" => $mesesNom[intval($cursor->format("n"))] . " " . $cursor->format("y"), "total" => 0);
  $cursor->modify("+1 month");
}
if ($tot_facilitadores > 0) {
  $sql = "SELECT DATE_FORMAT(X.f, '%Y-%m') AS mes, COUNT(*) AS total
    FROM (" . $sqlReportes . ") AS X
    INNER JOIN usuario AS U ON U.id = X.uid
    LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
    WHERE U.tipo IN (162, 163, 167) " . $sqlFiltro . "
      AND X.f >= '" . $inicioMeses->format("Y-m-d") . "'
    GROUP BY mes";
  $PSN->query($sql);
  while ($PSN->next_record()) {
    if (isset($reportesMes[$PSN->f('mes')])) {
      $reportesMes[$PSN->f('mes')]["total"] = intval($PSN->f('total'));
    }
  }
}

/*
*   DATOS PARA GOOGLE CHARTS
*/
$dataEstado = array(array("Estado", "Facilitadores"), array("Activos", $tot_activos), array("Inactivos", $tot_inactivos));
$dataDatos = array(array("Datos", "Facilitadores"), array("Completos", $tot_completos), array("Incompletos", $tot_incompletos));
$dataActividad = array(array("Actividad", "Facilitadores"));
foreach ($actividad as $k => $v) {
  $dataActividad[] = array($k, $v);
}
$dataRegional = array(array("Regional", "Activos", "Inactivos"));
foreach ($porRegional as $k => $v) {
  $dataRegional[] = array($k, $v[0], $v[1]);
}
$dataMes = array(array("Mes", "Reportes"));
foreach ($reportesMes as $m) {
  $dataMes[] = array($m["label"], $m["total"]);
}

// Línea de tiempo: solo facilitadores con al menos un reporte (ya vienen del más reciente al más antiguo)
$filasTimeline = array();
foreach ($facilitadores as $f) {
  if ($f["total"] > 0 && $f["primero"] != "" && $f["ultimo"] != "") {
    $filasTimeline[] = array(
      "fac" => $f["nombre"],
      "estado" => $f["activo"] ? "Activo" : "Inactivo",
      "color" => $f["activo"] ? "#239B56" : "#C0392B",
      "tip" => "<div class='fac-tip'><strong>" . h($f["nombre"]) . "</strong><br>"
        . h($f["regional"]) . " · " . ($f["activo"] ? "Activo" : "Inactivo") . "<br>"
        . "Primer reporte: " . h($f["primero"]) . "<br>"
        . "Último reporte: " . h($f["ultimo"]) . "<br>"
        . "Reportes: <strong>" . $f["total"] . "</strong></div>",
      "ini" => $f["primero"],
      "fin" => $f["ultimo"],
    );
  }
}
$alturaTimeline = max(160, count($filasTimeline) * 41 + 60);

$jsonOpts = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

?>
<style>
  /* Estilos exclusivos de este dashboard (prefijo .dash-fac) */
  .dash-fac .panel {
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    margin-bottom: 20px;
  }

  .dash-fac .panel-heading {
    background: #fff;
    border-bottom: 1px solid #eee;
  }

  .dash-fac .panel-title {
    font-weight: bold;
    font-size: 15px;
  }

  .dash-fac .panel-sub {
    color: #888;
    font-size: 12px;
    margin: 3px 0 0;
  }

  .dash-fac .chart-box {
    width: 100%;
    height: 260px;
  }

  .dash-fac .chart-box-lg {
    width: 100%;
    height: 320px;
  }

  .dash-fac .timeline-scroll {
    max-height: 520px;
    overflow-y: auto;
  }

  .dash-fac .leyenda span {
    display: inline-block;
    margin-right: 15px;
    font-size: 12px;
    color: #666;
  }

  .dash-fac .leyenda i {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 2px;
    margin-right: 4px;
    vertical-align: middle;
  }

  .dash-fac .tabla-scroll {
    max-height: 500px;
    overflow-y: auto;
  }

  .dash-fac .tabla-scroll thead th {
    position: sticky;
    top: 0;
    background: #f7f7f7;
    z-index: 1;
  }

  .dash-fac .resu-item .item-num span {
    white-space: nowrap;
  }

  .fac-tip {
    padding: 8px 10px;
    font-size: 13px;
    line-height: 1.5;
  }
</style>

<div class="container dash-fac">
  <div class="row">
    <h3 class="alert alert-info text-center">DASHBOARD DE FACILITADORES</h3>
  </div>

  <!-- FILTROS -->
  <form action="index.php" method="get" name="form1" class="panel panel-default">
    <input type="hidden" name="doc" value="grafica_facilitadores" />
    <div class="panel-body">
      <div class="row">
        <div class="col-sm-4 col-xs-12">
          <strong>Regional</strong>
          <select name="empresa_pd" class="form-control" onchange="this.form.submit()">
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
        <div class="col-sm-4 col-xs-12">
          <strong>Estado</strong>
          <select name="estado" class="form-control" onchange="this.form.submit()">
            <option value="">Activos e inactivos</option>
            <option value="1" <?= $buscar_estado === "1" ? 'selected' : ''; ?>>Solo activos</option>
            <option value="0" <?= $buscar_estado === "0" ? 'selected' : ''; ?>>Solo inactivos</option>
          </select>
        </div>
        <div class="col-sm-4 col-xs-12"><br>
          <a href="index.php?doc=grafica_facilitadores" class="btn btn-default">Limpiar filtros</a>
        </div>
      </div>
    </div>
  </form>

  <?php if ($tot_facilitadores == 0) { ?>
    <div class="row">
      <h5 class="alert alert-warning text-center">No se ha encontrado ningún facilitador con los filtros seleccionados.</h5>
    </div>
  <?php } else { ?>

    <!-- 1. INDICADORES PRINCIPALES -->
    <div class="contenedor-flex content-grafic fl-sard">
      <div class="cont-resu fl-sard">
        <div class="resu-item bck-col-1">
          <div class="item-ico"><i class="fas fa-users"></i></div>
          <div class="item-con">
            <div class="item-text">
              <h3>Facilitadores</h3>
              <p>registrados</p>
            </div>
            <div class="item-num"><span><?= $tot_facilitadores; ?></span></div>
          </div>
        </div>
        <div class="resu-item bck-col-2">
          <div class="item-ico"><i class="fas fa-user-check"></i></div>
          <div class="item-con">
            <div class="item-text">
              <h3>Activos</h3>
              <p><?= $pct_activos; ?>% del total</p>
            </div>
            <div class="item-num"><span><?= $tot_activos; ?></span></div>
          </div>
        </div>
        <div class="resu-item bck-col-5">
          <div class="item-ico"><i class="fas fa-user-times"></i></div>
          <div class="item-con">
            <div class="item-text">
              <h3>Inactivos</h3>
              <p><?= 100 - $pct_activos; ?>% del total</p>
            </div>
            <div class="item-num"><span><?= $tot_inactivos; ?></span></div>
          </div>
        </div>
        <div class="resu-item bck-col-3">
          <div class="item-ico"><i class="fas fa-file-alt"></i></div>
          <div class="item-con">
            <div class="item-text">
              <h3>Reportes</h3>
              <p><?= $prom_reportes; ?> por facilitador</p>
            </div>
            <div class="item-num"><span><?= number_format($tot_reportes); ?></span></div>
          </div>
        </div>
        <div class="resu-item bck-col-4">
          <div class="item-ico"><i class="fas fa-id-card"></i></div>
          <div class="item-con">
            <div class="item-text">
              <h3>Datos completos</h3>
              <p><?= $pct_completos; ?>% del total</p>
            </div>
            <div class="item-num"><span><?= $tot_completos; ?></span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. ESTADO / ACTIVIDAD / DATOS -->
    <div class="row">
      <div class="col-md-4 col-sm-6 col-xs-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <div class="panel-title">Estado de los facilitadores</div>
            <p class="panel-sub">¿Cuántos tienen el acceso habilitado?</p>
          </div>
          <div class="panel-body">
            <div id="chart_estado" class="chart-box"></div>
          </div>
        </div>
      </div>
      <div class="col-md-4 col-sm-6 col-xs-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <div class="panel-title">Actividad reciente</div>
            <p class="panel-sub">Tiempo desde su último reporte</p>
          </div>
          <div class="panel-body">
            <div id="chart_actividad" class="chart-box"></div>
          </div>
        </div>
      </div>
      <div class="col-md-4 col-sm-12 col-xs-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <div class="panel-title">Datos del perfil</div>
            <p class="panel-sub">Nombre, identificación, correo, celular, dirección y municipio</p>
          </div>
          <div class="panel-body">
            <div id="chart_datos" class="chart-box"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. REGIONALES / REPORTES POR MES -->
    <div class="row">
      <div class="col-md-6 col-xs-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <div class="panel-title">Facilitadores por regional</div>
            <p class="panel-sub">Activos e inactivos en cada regional</p>
          </div>
          <div class="panel-body">
            <div id="chart_regional" class="chart-box-lg"></div>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-xs-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <div class="panel-title">Reportes por mes</div>
            <p class="panel-sub">Últimos 12 meses (LPP, C&amp;M y demás programas)</p>
          </div>
          <div class="panel-body">
            <div id="chart_mes" class="chart-box-lg"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. LINEA DE TIEMPO -->
    <div class="panel panel-default">
      <div class="panel-heading">
        <div class="panel-title">Línea de tiempo de reportes</div>
        <p class="panel-sub">Cada barra va desde el primer hasta el último reporte del facilitador. Pase el mouse sobre una barra para ver el detalle.</p>
        <div class="leyenda" style="margin-top:6px">
          <span><i style="background:#239B56"></i>Activo</span>
          <span><i style="background:#C0392B"></i>Inactivo</span>
        </div>
      </div>
      <div class="panel-body">
        <?php if (count($filasTimeline) > 0) { ?>
          <div class="timeline-scroll">
            <div id="chart_timeline" style="width:100%; height: <?= $alturaTimeline; ?>px;"></div>
          </div>
        <?php } else { ?>
          <p class="text-muted text-center">Ningún facilitador del filtro tiene reportes para mostrar.</p>
        <?php } ?>
        <?php if ($actividad["Sin reportes"] > 0) { ?>
          <p class="text-muted" style="margin:10px 0 0"><small><?= $actividad["Sin reportes"]; ?> facilitador(es) sin reportes no aparecen en la línea de tiempo; puede verlos en el detalle de abajo.</small></p>
        <?php } ?>
      </div>
    </div>

    <!-- 5. DETALLE -->
    <div class="panel panel-default">
      <div class="panel-heading">
        <div class="row">
          <div class="col-sm-7 col-xs-12">
            <div class="panel-title">Detalle del inventario</div>
            <p class="panel-sub">Ordenado del reporte más reciente al más antiguo</p>
          </div>
          <div class="col-sm-5 col-xs-12">
            <input type="text" id="buscarFacilitador" class="form-control" placeholder="Buscar por nombre o regional..." />
          </div>
        </div>
      </div>
      <div class="panel-body">
        <div class="table-responsive tabla-scroll">
          <table class="table table-hover table-condensed" id="tablaFacilitadores">
            <thead>
              <tr>
                <th>Facilitador</th>
                <th>Regional</th>
                <th>Estado</th>
                <th>Último reporte</th>
                <th class="text-center">Reportes</th>
                <th>Datos del perfil</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($facilitadores as $f) {
                if ($f["dias"] === null) {
                  $claseDias = "text-muted";
                  $textoDias = "Sin reportes";
                } else {
                  $claseDias = $f["dias"] <= 30 ? "text-success" : ($f["dias"] <= 180 ? "text-warning" : "text-danger");
                  $textoDias = $f["dias"] == 0 ? "hoy" : "hace " . $f["dias"] . " días";
                }
              ?>
                <tr>
                  <td><a href="index.php?doc=usuario&id=<?= $f["id"]; ?>"><?= h($f["nombre"]); ?></a></td>
                  <td><?= h($f["regional"]); ?></td>
                  <td><span class="label <?= $f["activo"] ? 'label-success' : 'label-danger'; ?>"><?= $f["activo"] ? 'Activo' : 'Inactivo'; ?></span></td>
                  <td>
                    <?php if ($f["dias"] !== null) { ?><?= h($f["ultimo"]); ?><br><?php } ?>
                    <small class="<?= $claseDias; ?>"><?= $textoDias; ?></small>
                  </td>
                  <td class="text-center"><strong><?= $f["total"]; ?></strong></td>
                  <td>
                    <?php if (empty($f["faltantes"])) { ?>
                      <span class="label label-success">Completos</span>
                    <?php } else { ?>
                      <span class="label label-warning">Incompletos</span><br>
                      <small class="text-muted">Falta: <?= h(implode(", ", $f["faltantes"])); ?></small>
                    <?php } ?>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
          <p id="sinCoincidencias" class="text-muted text-center" style="display:none">No hay facilitadores que coincidan con la búsqueda.</p>
        </div>
      </div>
    </div>

    <script type="text/javascript">
      google.charts.load("current", {
        packages: ["corechart", "timeline"]
      });
      google.charts.setOnLoadCallback(dibujarDashboard);

      var datosEstado = <?= json_encode($dataEstado, $jsonOpts); ?>;
      var datosActividad = <?= json_encode($dataActividad, $jsonOpts); ?>;
      var datosDatos = <?= json_encode($dataDatos, $jsonOpts); ?>;
      var datosRegional = <?= json_encode($dataRegional, $jsonOpts); ?>;
      var datosMes = <?= json_encode($dataMes, $jsonOpts); ?>;
      var filasTimeline = <?= json_encode($filasTimeline, $jsonOpts); ?>;

      var opcionesDona = {
        pieHole: 0.55,
        chartArea: {left: 10, top: 10, width: "95%", height: "80%"},
        legend: {position: "bottom", textStyle: {fontSize: 12}},
        pieSliceText: "value",
        tooltip: {text: "both"}
      };

      function parseFecha(s) {
        var p = s.split("-");
        return new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
      }

      function dona(id, datos, colores) {
        var data = google.visualization.arrayToDataTable(datos);
        var opts = $.extend({}, opcionesDona, {colors: colores});
        new google.visualization.PieChart(document.getElementById(id)).draw(data, opts);
      }

      function dibujarDashboard() {
        dona("chart_estado", datosEstado, ["#239B56", "#C0392B"]);
        dona("chart_actividad", datosActividad, ["#239B56", "#2E86C1", "#F39C12", "#C0392B", "#BDBDBD"]);
        dona("chart_datos", datosDatos, ["#2E86C1", "#F39C12"]);

        // Facilitadores por regional (barras apiladas)
        var dataReg = google.visualization.arrayToDataTable(datosRegional);
        new google.visualization.BarChart(document.getElementById("chart_regional")).draw(dataReg, {
          isStacked: true,
          colors: ["#239B56", "#C0392B"],
          legend: {position: "top"},
          chartArea: {left: 140, top: 30, width: "65%", height: "85%"},
          hAxis: {format: "0", minValue: 0},
          vAxis: {textStyle: {fontSize: 11}},
          bar: {groupWidth: "65%"}
        });

        // Reportes por mes (columnas con el valor encima)
        var dataMes = google.visualization.arrayToDataTable(datosMes);
        var vistaMes = new google.visualization.DataView(dataMes);
        vistaMes.setColumns([0, 1, {calc: "stringify", sourceColumn: 1, type: "string", role: "annotation"}]);
        new google.visualization.ColumnChart(document.getElementById("chart_mes")).draw(vistaMes, {
          colors: ["#2E86C1"],
          legend: {position: "none"},
          chartArea: {left: 45, top: 20, width: "88%", height: "75%"},
          vAxis: {format: "0", minValue: 0},
          hAxis: {textStyle: {fontSize: 11}},
          annotations: {alwaysOutside: true, textStyle: {fontSize: 11, color: "#555"}},
          bar: {groupWidth: "60%"}
        });

        // Línea de tiempo
        if (filasTimeline.length > 0) {
          var data = new google.visualization.DataTable();
          data.addColumn({type: "string", id: "Facilitador"});
          data.addColumn({type: "string", id: "Estado"});
          data.addColumn({type: "string", role: "style"});
          data.addColumn({type: "string", role: "tooltip", p: {html: true}});
          data.addColumn({type: "date", id: "Inicio"});
          data.addColumn({type: "date", id: "Fin"});
          var rows = [];
          for (var i = 0; i < filasTimeline.length; i++) {
            var ini = parseFecha(filasTimeline[i].ini);
            var fin = parseFecha(filasTimeline[i].fin);
            // Un único reporte generaría una barra invisible: se amplía visualmente 15 días
            if (fin.getTime() <= ini.getTime()) {
              fin = new Date(ini.getTime() + 15 * 86400000);
            }
            rows.push([filasTimeline[i].fac, filasTimeline[i].estado, filasTimeline[i].color, filasTimeline[i].tip, ini, fin]);
          }
          data.addRows(rows);
          new google.visualization.Timeline(document.getElementById("chart_timeline")).draw(data, {
            tooltip: {isHtml: true},
            timeline: {showBarLabels: false, rowLabelStyle: {fontSize: 12}},
            avoidOverlappingGridLines: false
          });
        }
      }

      // Redibujar al cambiar el tamaño de la ventana (responsive)
      var timerResize;
      $(window).on("resize", function() {
        clearTimeout(timerResize);
        timerResize = setTimeout(dibujarDashboard, 250);
      });

      // Búsqueda rápida en la tabla de detalle
      $("#buscarFacilitador").on("keyup", function() {
        var texto = $(this).val().toLowerCase();
        var visibles = 0;
        $("#tablaFacilitadores tbody tr").each(function() {
          var coincide = $(this).children("td").slice(0, 2).text().toLowerCase().indexOf(texto) > -1;
          $(this).toggle(coincide);
          if (coincide) visibles++;
        });
        $("#sinCoincidencias").toggle(visibles === 0);
      });
    </script>
  <?php } ?>
</div>
