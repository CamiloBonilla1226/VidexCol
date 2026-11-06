<?php
/**
 * Informe Ejecutivo imprimible del Proyecto de Inversion (formato A4/Carta).
 * Pagina standalone (no pasa por index.php ni por su shell con sidebar),
 * igual que imprimir.php, pero con su propio boton de Descargar PDF/Imprimir
 * en vez de imprimir y cerrar automaticamente.
 */
session_start();
include_once('funciones.php');
require_once('gp_inversion_config.php');
require_once('gp_inversion_service.php');

if (!is_logged_in()) {
    header('Location: index.php');
    exit;
}
gp_inv_requerir_acceso();

$service = new GpInversionService();
$id = (int) ($_GET['id'] ?? 0);
$proyecto = $id > 0 ? $service->getProyecto($id) : null;
if (!$proyecto) {
    die('Proyecto no encontrado.');
}
$restriccion = gp_inv_cliente_restriccion();
if ($restriccion !== null && (int) $proyecto['cliente_id'] !== $restriccion) {
    die('No tiene acceso a este proyecto.');
}

$desde = isset($_GET['desde']) ? trim((string) $_GET['desde']) : date('Y-01-01');
$hasta = isset($_GET['hasta']) ? trim((string) $_GET['hasta']) : date('Y-m-d');

$consolidado = $service->calcularConsolidado($id, $desde, $hasta);
$porCuenta = $service->consolidadoPorCuenta($id);
$evolucion = $service->evolucionTemporal($id, $desde, $hasta);
$rentabilidad = $service->rentabilidadPorCuenta($id, $desde, $hasta);
$ingresos = $service->listarMovimientos($id, 'ingreso', $desde, $hasta);
$egresos = $service->listarMovimientos($id, 'egreso', $desde, $hasta);

$movimientos = array_merge($ingresos, $egresos);
usort($movimientos, function ($a, $b) { return strcmp($b['fecha'], $a['fecha']); });

function gpiFecha($f) { return $f ? date('d/m/Y', strtotime($f)) : '—'; }
function gpiMoneda($v) { return '$' . number_format((float) $v, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Informe Ejecutivo — <?= htmlspecialchars($proyecto['nombre'], ENT_QUOTES); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<link rel="stylesheet" href="gp_inversion.css?v=<?= @filemtime('gp_inversion.css'); ?>" />
</head>
<body class="gp-inv-informe-body">

<div class="gp-inv-informe-toolbar">
    <div><strong>VidEx</strong> — Vista previa de impresión</div>
    <div>
        <button type="button" class="gp-btn-secondary" onclick="window.close()">Cerrar</button>
        <button type="button" class="gp-btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
        <button type="button" class="gp-btn-primary" onclick="window.print()"><i class="bi bi-file-earmark-pdf"></i> Descargar PDF</button>
    </div>
</div>

<div class="gp-inv-informe-hoja">
    <div class="gp-inv-informe-header">
        <div class="gp-inv-informe-logo">VidEx</div>
        <div class="gp-inv-informe-fecha">Generado el <?= date('d/m/Y H:i'); ?></div>
    </div>
    <h1>Informe Ejecutivo — Proyecto de Inversión</h1>
    <h2><?= htmlspecialchars($proyecto['cliente_nombre'], ENT_QUOTES); ?> — <?= htmlspecialchars($proyecto['nombre'], ENT_QUOTES); ?></h2>
    <p class="gp-inv-informe-periodo">Período: <?= gpiFecha($desde); ?> – <?= gpiFecha($hasta); ?></p>
    <hr>

    <div class="gp-inv-kpis gp-inv-kpis-print">
        <div class="gp-inv-kpi"><div class="gp-inv-kpi-label">Capital Invertido</div><div class="gp-inv-kpi-valor"><?= gpiMoneda($consolidado['capital_invertido']); ?></div></div>
        <div class="gp-inv-kpi"><div class="gp-inv-kpi-label">Utilidades Generadas</div><div class="gp-inv-kpi-valor"><?= gpiMoneda($consolidado['utilidades']); ?></div></div>
        <div class="gp-inv-kpi"><div class="gp-inv-kpi-label">Total Gastos</div><div class="gp-inv-kpi-valor"><?= gpiMoneda($consolidado['gastos']); ?></div></div>
        <div class="gp-inv-kpi"><div class="gp-inv-kpi-label">Saldo Neto</div><div class="gp-inv-kpi-valor"><?= gpiMoneda($porCuenta['total']); ?></div></div>
        <div class="gp-inv-kpi"><div class="gp-inv-kpi-label">ROI</div><div class="gp-inv-kpi-valor"><?= number_format($consolidado['roi'], 2); ?>%</div></div>
    </div>

    <div class="gp-inv-graficos gp-inv-graficos-print">
        <div class="gp-inv-informe-recuadro">
            <h3>Evolución Temporal: Capital vs. Utilidades vs. Gastos</h3>
            <div id="gpInformeEvolucion" class="gp-inv-chart"></div>
        </div>
        <div class="gp-inv-informe-recuadro">
            <h3>Distribución Bancaria del Capital</h3>
            <div id="gpInformeBancos" class="gp-inv-chart"></div>
        </div>
    </div>

    <h3>Resumen de Rentabilidad por Cuenta / Capital</h3>
    <table class="gp-inv-informe-tabla">
        <thead><tr><th>Banco / Cuenta</th><th>Capital Asignado</th><th>Utilidades Generadas</th><th>ROI %</th></tr></thead>
        <tbody>
            <?php foreach ($rentabilidad as $r) { ?>
                <tr>
                    <td><?= htmlspecialchars($r['banco'] . ($r['numero_alias'] ? ' ' . $r['numero_alias'] : ''), ENT_QUOTES); ?></td>
                    <td><?= gpiMoneda($r['capital']); ?></td>
                    <td><?= gpiMoneda($r['utilidades']); ?></td>
                    <td><?= number_format($r['roi'], 2); ?>%</td>
                </tr>
            <?php } ?>
            <?php if (empty($rentabilidad)) { ?><tr><td colspan="4">Sin cuentas registradas.</td></tr><?php } ?>
        </tbody>
    </table>

    <h3>Clasificación de Egresos</h3>
    <table class="gp-inv-informe-tabla">
        <thead><tr><th>Clasificación</th><th>Monto</th><th>% del total de egresos</th></tr></thead>
        <tbody>
            <tr>
                <td>Gastos Operativos / Administrativos</td>
                <td><?= gpiMoneda($consolidado['gastos']); ?></td>
                <td><?= $consolidado['total_egresos'] > 0 ? number_format($consolidado['gastos'] / $consolidado['total_egresos'] * 100, 1) : '0'; ?>%</td>
            </tr>
            <tr>
                <td>Inversión / Capital de Trabajo</td>
                <td><?= gpiMoneda($consolidado['nuevas_inversiones']); ?></td>
                <td><?= $consolidado['total_egresos'] > 0 ? number_format($consolidado['nuevas_inversiones'] / $consolidado['total_egresos'] * 100, 1) : '0'; ?>%</td>
            </tr>
        </tbody>
    </table>

    <h3>Detalle de Movimientos del Período</h3>
    <table class="gp-inv-informe-tabla gp-inv-informe-tabla-densa">
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Banco</th><th>Valor</th></tr></thead>
        <tbody>
            <?php foreach ($movimientos as $m) {
                $esIngreso = $m['tipo_movimiento'] === 'ingreso';
                $tipoTxt = $esIngreso ? (GP_INV_TIPOS_INGRESO[$m['tipo_ingreso']] ?? 'Ingreso') : (GP_INV_CLASIFICACION_EGRESO[$m['clasificacion_egreso']] ?? 'Egreso');
                $descripcion = $esIngreso ? $m['origen_fondos'] : $m['destino_fondo'];
                ?>
                <tr>
                    <td><?= gpiFecha($m['fecha']); ?></td>
                    <td><?= htmlspecialchars($tipoTxt, ENT_QUOTES); ?></td>
                    <td><?= htmlspecialchars((string) $descripcion, ENT_QUOTES); ?></td>
                    <td><?= htmlspecialchars((string) $m['cuenta_banco'], ENT_QUOTES); ?></td>
                    <td class="<?= $esIngreso ? 'gp-inv-kpi-pos' : 'gp-inv-kpi-neg'; ?>"><?= ($esIngreso ? '+' : '-') . gpiMoneda($m['valor']); ?></td>
                </tr>
            <?php } ?>
            <?php if (empty($movimientos)) { ?><tr><td colspan="5">Sin movimientos en el período seleccionado.</td></tr><?php } ?>
        </tbody>
    </table>

    <div class="gp-inv-informe-footer">Documento generado automáticamente por VidEx — Confidencial</div>
</div>

<script>
google.charts.load('current', { packages: ['corechart'] });
google.charts.setOnLoadCallback(function () {
    var evolucion = <?= json_encode($evolucion); ?>;
    var dEvo = new google.visualization.DataTable();
    dEvo.addColumn('string', 'Mes'); dEvo.addColumn('number', 'Capital'); dEvo.addColumn('number', 'Utilidades'); dEvo.addColumn('number', 'Gastos');
    evolucion.forEach(function (f) { dEvo.addRow([f.mes, f.capital, f.utilidades, f.gastos]); });
    new google.visualization.ColumnChart(document.getElementById('gpInformeEvolucion')).draw(dEvo, {
        legend: { position: 'top' }, colors: ['#2563eb', '#16a34a', '#dc2626'],
        chartArea: { left: 55, top: 30, width: '88%', height: '72%' }, height: 220,
    });
    var bancos = <?= json_encode($porCuenta['cuentas']); ?>;
    var dBan = new google.visualization.DataTable();
    dBan.addColumn('string', 'Banco'); dBan.addColumn('number', 'Saldo');
    bancos.forEach(function (f) { dBan.addRow([f.banco, f.saldo]); });
    new google.visualization.PieChart(document.getElementById('gpInformeBancos')).draw(dBan, {
        pieHole: 0.5, legend: { position: 'right' }, colors: ['#2563eb', '#16a34a', '#CEAB37', '#7c3aed', '#dc2626', '#0891b2'],
        chartArea: { top: 10, height: '85%' }, height: 220,
    });
});
</script>
</body>
</html>
