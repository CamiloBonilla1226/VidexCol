<?php

/*******************************************
GRAFICA DE INVENTARIO DE FACILITADORES
Línea de tiempo (Google Charts) con estado, regional,
último reporte, cantidad de reportes y datos completos.
 *******************************************/

// Solo usuarios con sesión y perfil de administración/coordinación (162, 167) o superusuario
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "" || ($_SESSION["superusuario"] != 1 && $_SESSION["perfil"] != 167 && $_SESSION["perfil"] != 162)) {
  die("<h5 class='alert alert-danger text-center'>NO esta autorizado a ver esta grafica</h5>");
}

$PSN = new DBbase_Sql;
$PSN2 = new DBbase_Sql;

/*
*   FILTROS (todos se convierten a entero o se validan contra lista blanca)
*/
$buscar_regional = isset($_REQUEST["empresa_pd"]) ? intval($_REQUEST["empresa_pd"]) : 0;
if ($_SESSION["perfil"] != 167 && $_SESSION["superusuario"] != 1 && intval($_SESSION["empresa_pd"]) != 0) {
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

/*
*   INVENTARIO: una sola consulta (sin N+1). Los reportes de los tres modelos
*   (reporte_lpp, reporte_cm, sat_reportes) se agrupan por usuario en una subconsulta.
*/
$sql = "SELECT U.id, U.nombre, U.acceso, U.identificacion, U.email, U.celular, U.direccion, U.usua_muni,
  C.descripcion AS regional,
  R.total_reportes, R.primer_reporte, R.ultimo_reporte
  FROM usuario AS U
  LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
  LEFT JOIN categorias AS C ON C.id = UE.empresa_pd
  LEFT JOIN (
    SELECT uid, COUNT(*) AS total_reportes, MIN(f) AS primer_reporte, MAX(f) AS ultimo_reporte
    FROM (
      SELECT usuario_id AS uid, fecha_reporte AS f FROM reporte_lpp
      UNION ALL
      SELECT usuario_id, fecha_reporte FROM reporte_cm WHERE inactivo = 0
      UNION ALL
      SELECT idUsuario, fechaReporte FROM sat_reportes WHERE inactivo = 0
    ) AS X
    GROUP BY uid
  ) AS R ON R.uid = U.id
  WHERE U.tipo IN (162, 163, 167) " . $sqlFiltro . "
  GROUP BY U.id
  ORDER BY U.acceso DESC, C.descripcion ASC, U.nombre ASC";
$PSN->query($sql);

$facilitadores = array();
$tot_activos = $tot_inactivos = $tot_completos = $tot_sin_reportes = 0;
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

  $activo ? $tot_activos++ : $tot_inactivos++;
  if (empty($faltantes)) {
    $tot_completos++;
  }
  if ($total == 0) {
    $tot_sin_reportes++;
  }

  $facilitadores[] = array(
    "id" => intval($PSN->f('id')),
    "nombre" => $PSN->f('nombre'),
    "regional" => $PSN->f('regional') != "" ? $PSN->f('regional') : "Sin regional",
    "activo" => $activo,
    "total" => $total,
    "primero" => $PSN->f('primer_reporte'),
    "ultimo" => $PSN->f('ultimo_reporte'),
    "faltantes" => $faltantes,
  );
}
$tot_facilitadores = count($facilitadores);

// Filas para la línea de tiempo (solo facilitadores con al menos un reporte)
$filasTimeline = array();
foreach ($facilitadores as $f) {
  if ($f["total"] > 0 && $f["primero"] != "" && $f["ultimo"] != "") {
    $filasTimeline[] = array(
      "fac" => $f["nombre"] . " (" . $f["regional"] . ")",
      "estado" => $f["activo"] ? "Activo" : "Inactivo",
      "color" => $f["activo"] ? "#239B56" : "#C0392B",
      "tip" => "<div style='padding:8px;font-size:13px'><strong>" . htmlspecialchars($f["nombre"], ENT_QUOTES, 'UTF-8') . "</strong><br>"
        . "Regional: " . htmlspecialchars($f["regional"], ENT_QUOTES, 'UTF-8') . "<br>"
        . "Estado: " . ($f["activo"] ? "Activo" : "Inactivo") . "<br>"
        . "Primer reporte: " . htmlspecialchars($f["primero"], ENT_QUOTES, 'UTF-8') . "<br>"
        . "Último reporte: " . htmlspecialchars($f["ultimo"], ENT_QUOTES, 'UTF-8') . "<br>"
        . "Reportes: " . $f["total"] . "<br>"
        . "Datos: " . (empty($f["faltantes"]) ? "completos" : "faltan " . htmlspecialchars(implode(", ", $f["faltantes"]), ENT_QUOTES, 'UTF-8')) . "</div>",
      "ini" => $f["primero"],
      "fin" => $f["ultimo"],
    );
  }
}
$alturaTimeline = max(200, count($filasTimeline) * 41 + 60);

?>
<div class="container">
  <form action="index.php" method="get" name="form1" class="form-horizontal">
    <input type="hidden" name="doc" value="grafica_facilitadores" />
    <div class="row">
      <h3 class="alert alert-info text-center">INVENTARIO DE FACILITADORES</h3>
    </div>
    <div class="cont-tit">
      <div class="hr">
        <hr>
      </div>
      <div class="tit-cen">
        <h3>FILTRO DE BUSQUEDA</h3>
        <h5>de la gráfica</h5>
      </div>
      <div class="hr">
        <hr>
      </div>
    </div>
    <div class="form-group">
      <div class="col-sm-3">
        <strong>Regional:</strong>
        <select name="empresa_pd" class="form-control" onchange="this.form.submit()">
          <?php
          if ($_SESSION["perfil"] == 167 || $_SESSION["superusuario"] == 1 || intval($_SESSION["empresa_pd"]) == 0) {
            echo '<option value="">Todas las regionales</option>';
          }
          $sql = "SELECT C.id, C.descripcion AS regional FROM categorias AS C
                  LEFT JOIN categorias AS CA ON CA.id = C.idSec
                  WHERE CA.idSec = 85 ";
          if ($_SESSION["perfil"] != 167 && $_SESSION["superusuario"] != 1 && intval($_SESSION["empresa_pd"]) != 0) {
            $sql .= " AND C.id = " . intval($_SESSION["empresa_pd"]);
          }
          $sql .= " ORDER BY C.descripcion ASC";
          $PSN2->query($sql);
          while ($PSN2->next_record()) { ?>
            <option value="<?= intval($PSN2->f('id')); ?>" <?= ($buscar_regional == $PSN2->f('id')) ? 'selected="selected"' : ''; ?>><?= htmlspecialchars($PSN2->f('regional'), ENT_QUOTES, 'UTF-8'); ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="col-sm-3">
        <strong>Estado:</strong>
        <select name="estado" class="form-control" onchange="this.form.submit()">
          <option value="">Activos e inactivos</option>
          <option value="1" <?= $buscar_estado === "1" ? 'selected' : ''; ?>>Solo activos</option>
          <option value="0" <?= $buscar_estado === "0" ? 'selected' : ''; ?>>Solo inactivos</option>
        </select>
      </div>
      <div class="col-sm-2"><br>
        <input type="submit" value="Buscar" class="btn btn-success" />
      </div>
    </div>
  </form>

  <div class="cont-tit">
    <div class="hr">
      <hr>
    </div>
    <div class="tit-cen">
      <h3 class="text-center">RESULTADOS DEL FILTRO</h3>
      <h5>Línea de tiempo de facilitadores</h5>
    </div>
    <div class="hr">
      <hr>
    </div>
  </div>

  <?php if ($tot_facilitadores == 0) { ?>
    <div class="row">
      <h5 class="alert alert-warning text-center">No se ha encontrado ningún facilitador con los filtros seleccionados.</h5>
    </div>
  <?php } else { ?>
    <div class="contenedor-flex content-grafic fl-sard">
      <div class="cont-resu fl-sard">
        <div class="resu-item bck-col-1">
          <div class="item-ico"><i class="fas fa-users"></i></div>
          <div class="item-con">
            <div class="item-text">
              <h3>Total</h3>
              <p>de facilitadores</p>
            </div>
            <div class="item-num"><span><?= $tot_facilitadores; ?></span></div>
          </div>
        </div>
        <div class="resu-item bck-col-2">
          <div class="item-ico"><i class="fas fa-users"></i></div>
          <div class="item-con">
            <div class="item-text">
              <h3>Número</h3>
              <p>de activos</p>
            </div>
            <div class="item-num"><span><?= $tot_activos; ?></span></div>
          </div>
        </div>
        <div class="resu-item bck-col-5">
          <div class="item-ico"><i class="fas fa-users"></i></div>
          <div class="item-con">
            <div class="item-text">
              <h3>Número</h3>
              <p>de inactivos</p>
            </div>
            <div class="item-num"><span><?= $tot_inactivos; ?></span></div>
          </div>
        </div>
        <div class="resu-item bck-col-3">
          <div class="item-ico"><i class="fas fa-users"></i></div>
          <div class="item-con">
            <div class="item-text">
              <h3>Con datos</h3>
              <p>completos</p>
            </div>
            <div class="item-num"><span><?= $tot_completos; ?></span></div>
          </div>
        </div>
        <div class="resu-item bck-col-4">
          <div class="item-ico"><i class="fas fa-users"></i></div>
          <div class="item-con">
            <div class="item-text">
              <h3>Sin</h3>
              <p>reportes</p>
            </div>
            <div class="item-num"><span><?= $tot_sin_reportes; ?></span></div>
          </div>
        </div>
      </div>
    </div>

    <?php if (count($filasTimeline) > 0) { ?>
      <p class="text-center text-muted">Cada barra va del primer al último reporte del facilitador. Verde: activo · Rojo: inactivo.</p>
      <div id="timeline_facilitadores" style="width: 100%; height: <?= $alturaTimeline; ?>px;"></div>
      <script type="text/javascript">
        google.charts.load("current", {
          packages: ["timeline"]
        });
        google.charts.setOnLoadCallback(drawTimeline);

        function parseFecha(s) {
          var p = s.split("-");
          return new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        }

        function drawTimeline() {
          var filas = <?= json_encode($filasTimeline, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
          var data = new google.visualization.DataTable();
          data.addColumn({type: "string", id: "Facilitador"});
          data.addColumn({type: "string", id: "Estado"});
          data.addColumn({type: "string", role: "style"});
          data.addColumn({type: "string", role: "tooltip", p: {html: true}});
          data.addColumn({type: "date", id: "Inicio"});
          data.addColumn({type: "date", id: "Fin"});
          var rows = [];
          for (var i = 0; i < filas.length; i++) {
            var ini = parseFecha(filas[i].ini);
            var fin = parseFecha(filas[i].fin);
            // Un único reporte generaría una barra invisible: se amplía visualmente 15 días
            if (fin.getTime() <= ini.getTime()) {
              fin = new Date(ini.getTime() + 15 * 86400000);
            }
            rows.push([filas[i].fac, filas[i].estado, filas[i].color, filas[i].tip, ini, fin]);
          }
          data.addRows(rows);
          var chart = new google.visualization.Timeline(document.getElementById("timeline_facilitadores"));
          chart.draw(data, {
            tooltip: {isHtml: true},
            timeline: {showBarLabels: false}
          });
        }
      </script>
    <?php } else { ?>
      <h5 class="alert alert-warning text-center">Ningún facilitador del filtro tiene reportes para mostrar en la línea de tiempo.</h5>
    <?php } ?>

    <div class="cont-tit">
      <div class="hr">
        <hr>
      </div>
      <div class="tit-cen">
        <h3 class="text-center">DETALLE DEL INVENTARIO</h3>
      </div>
      <div class="hr">
        <hr>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Facilitador</th>
            <th>Regional</th>
            <th>Estado</th>
            <th>Último reporte</th>
            <th class="text-center">Reportes</th>
            <th>Datos completos</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($facilitadores as $f) { ?>
            <tr>
              <td><a href="index.php?doc=usuario&id=<?= $f["id"]; ?>"><?= htmlspecialchars($f["nombre"], ENT_QUOTES, 'UTF-8'); ?></a></td>
              <td><?= htmlspecialchars($f["regional"], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><span class="label <?= $f["activo"] ? 'label-success' : 'label-danger'; ?>"><?= $f["activo"] ? 'Activo' : 'Inactivo'; ?></span></td>
              <td><?= $f["ultimo"] != "" ? htmlspecialchars($f["ultimo"], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">Sin reportes</span>'; ?></td>
              <td class="text-center"><?= $f["total"]; ?></td>
              <td>
                <?php if (empty($f["faltantes"])) { ?>
                  <span class="label label-success">Completos</span>
                <?php } else { ?>
                  <span class="label label-warning">Incompletos</span>
                  <small class="text-muted">Falta: <?= htmlspecialchars(implode(", ", $f["faltantes"]), ENT_QUOTES, 'UTF-8'); ?></small>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } ?>
</div>
