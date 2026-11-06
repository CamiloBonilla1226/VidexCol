<?php
/*******************************************
Rentabilidad por proyecto (GRAFICAS)

Por cotizacion/proyecto y por cliente, en el periodo elegido:
  ingresos cobrados (abonos), gastos directos, auditoria, contribucion,
  gastos globales (indirectos), resultado final y cartera pendiente.
Con graficas, exportacion a Excel (?excelX=1) y a PDF (impresion del navegador).

SOLO LECTURA: no escribe nada en la base de datos.
Requiere una fila en menu_graphs con php = 'graphs_rentabilidad'
(ver sql_menu_graficas_finanzas.sql).
*******************************************/

require_once(__DIR__ . '/graphs_finanzas_service.php');
require_once(__DIR__ . '/graphs_finanzas_ui.php');

gf_requerir_acceso('graphs_rentabilidad');

$svc = new GraphsFinanzasService();
list($desde, $hasta, $anioSel) = GraphsFinanzasService::rango($_GET);
$ambito = gf_ambito(isset($_GET['ambito']) ? $_GET['ambito'] : 'negocio');
$estadoSel = isset($_GET['estado']) ? preg_replace('/\D/', '', (string) $_GET['estado']) : '';
$busqueda = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$esExcel = isset($_GET['excelX']);

$paramsExport = ['doc' => 'graphs_rentabilidad', 'ambito' => $ambito];
if ($anioSel !== '') { $paramsExport['anio'] = $anioSel; } else { $paramsExport['fechaInicial'] = $desde; $paramsExport['fechaFinal'] = $hasta; }
if ($estadoSel !== '') { $paramsExport['estado'] = $estadoSel; }
if ($busqueda !== '') { $paramsExport['q'] = $busqueda; }

// ---------------------------------------------------------------- datos
$todosProy = $svc->proyectosDelPeriodo($desde, $hasta, $ambito);

$estadosDisponibles = [];
foreach ($todosProy as $p) { $estadosDisponibles[$p['estado']] = $p['estado_nombre']; }
asort($estadosDisponibles);

$proy = [];
foreach ($todosProy as $p) {
    if ($estadoSel !== '' && (string) $p['estado'] !== $estadoSel) { continue; }
    if ($busqueda !== '' && stripos($p['cliente'] . ' ' . $p['descripcion'] . ' #' . $p['id'], $busqueda) === false) { continue; }
    $proy[] = $p;
}
usort($proy, function ($a, $b) { return $b['ganancia_p'] <=> $a['ganancia_p']; });

$res = $svc->resumen($desde, $hasta, $ambito);
$resPersonal = ($ambito === 'negocio') ? $svc->resumen($desde, $hasta, 'personal') : null;
$serie = $svc->serieMensual($desde, $hasta, $ambito);
$glob = $svc->globales($desde, $hasta);
$audCliente = $svc->auditoriaPorCliente($desde, $hasta);
$clientesMap = $svc->clientesMap();

// Totales de la tabla de proyectos (con los filtros aplicados)
$tot = ['cotizado' => 0, 'a_cobrar' => 0, 'cobrado_p' => 0, 'gasto_p' => 0, 'ganancia_p' => 0, 'cobrado_t' => 0, 'gasto_t' => 0, 'ganancia_t' => 0, 'pendiente' => 0];
foreach ($proy as $p) { foreach ($tot as $k => $_) { $tot[$k] += $p[$k]; } }

// Agregado por cliente
$porCliente = [];
foreach ($proy as $p) {
    $id = $p['idCliente'];
    if (!isset($porCliente[$id])) {
        $porCliente[$id] = ['id' => $id, 'cliente' => $p['cliente'], 'n' => 0, 'ingresos' => 0.0, 'directos' => 0.0, 'auditoria' => 0.0, 'pendiente' => 0.0];
    }
    $porCliente[$id]['n']++;
    $porCliente[$id]['ingresos'] += $p['cobrado_p'];
    $porCliente[$id]['directos'] += $p['gasto_p'];
    $porCliente[$id]['pendiente'] += $p['pendiente'];
}
foreach ($porCliente as $id => &$c) {
    $c['auditoria'] = isset($audCliente[$id]) ? $audCliente[$id]['periodo'] : 0.0;
    $c['contribucion'] = $c['ingresos'] - $c['directos'] - $c['auditoria'];
    $c['margen'] = $c['ingresos'] > 0 ? ($c['contribucion'] / $c['ingresos']) * 100 : null;
}
unset($c);
// clientes que solo tienen auditoria en el periodo (sin proyectos con movimiento)
if ($estadoSel === '' && $busqueda === '') {
    foreach ($audCliente as $id => $a) {
        if (!isset($porCliente[$id]) && $a['periodo'] != 0 && isset($clientesMap[$id]) && GraphsFinanzasService::enAmbito($clientesMap[$id]['personal'], $ambito)) {
            $porCliente[$id] = ['id' => $id, 'cliente' => $clientesMap[$id]['nombre'], 'n' => 0, 'ingresos' => 0.0, 'directos' => 0.0,
                'auditoria' => $a['periodo'], 'pendiente' => 0.0, 'contribucion' => -$a['periodo'], 'margen' => null];
        }
    }
}
usort($porCliente, function ($a, $b) { return $b['contribucion'] <=> $a['contribucion']; });

$revisar = [];
foreach ($proy as $p) { if ($p['revisar'] !== '') { $revisar[] = $p; } }

$etiquetaPeriodo = gf_fecha($desde) . ' al ' . gf_fecha($hasta);
$qPeriodo = $anioSel !== '' ? ['anio' => $anioSel] : ['fechaInicial' => $desde, 'fechaFinal' => $hasta];
if (!function_exists('gf_url_ficha')) {
    function gf_url_ficha($idCliente, $ambito, array $qPeriodo)
    {
        return 'index.php?' . http_build_query(array_merge(['doc' => 'graphs_cartera', 'cliente' => (int) $idCliente, 'ambito' => $ambito], $qPeriodo));
    }
}

// ================================================================ EXCEL
if ($esExcel) {
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<h3>Rentabilidad por proyecto &mdash; ' . gf_h($etiquetaPeriodo) . ' &mdash; ' . gf_h(gf_nombre_ambito($ambito)) . '</h3>';
    echo '<table border="1"><tr><th colspan="2">ESTADO DE RESULTADOS</th></tr>';
    $f = [['Ingresos cobrados (abonos)', $res['ingresos']], ['(-) Gastos directos', $res['directos']], ['(-) Auditoria (costo de personas)', $res['auditoria']],
          ['= Contribucion de los proyectos', $res['contribucion']], ['(-) Gastos globales / indirectos', $res['globales']], ['= RESULTADO FINAL', $res['resultado']],
          ['Pendiente por cobrar (cartera)', $res['pendiente']]];
    foreach ($f as $x) { echo '<tr><td>' . gf_h($x[0]) . '</td><td align="right">' . round($x[1]) . '</td></tr>'; }
    echo '</table><br />';

    echo '<table border="1"><tr><th colspan="17">PROYECTOS (' . count($proy) . ')</th></tr><tr>';
    foreach (['Cotizacion', 'Cliente', 'Descripcion', 'Estado', 'Fecha cotizacion', 'Cotizado', 'A cobrar', 'Cobrado periodo', 'Gasto directo periodo', 'Ganancia periodo', 'Margen % periodo', 'Cobrado total', 'Gasto directo total', 'Ganancia total', 'Pendiente por cobrar', 'Tipo', 'Alerta'] as $h) { echo '<th>' . $h . '</th>'; }
    echo '</tr>';
    foreach ($proy as $p) {
        echo '<tr><td>' . $p['id'] . '</td><td>' . gf_h($p['cliente']) . '</td><td>' . gf_h(gf_resumir($p['descripcion'], 120)) . '</td><td>' . gf_h($p['estado_nombre']) . '</td><td>' . gf_fecha($p['fecha']) . '</td>'
            . '<td>' . round($p['cotizado']) . '</td><td>' . round($p['a_cobrar']) . '</td><td>' . round($p['cobrado_p']) . '</td><td>' . round($p['gasto_p']) . '</td><td>' . round($p['ganancia_p']) . '</td>'
            . '<td>' . ($p['margen_p'] === null ? '' : round($p['margen_p'], 1)) . '</td><td>' . round($p['cobrado_t']) . '</td><td>' . round($p['gasto_t']) . '</td><td>' . round($p['ganancia_t']) . '</td>'
            . '<td>' . round($p['pendiente']) . '</td><td>' . ($p['personal'] ? 'Personal' : 'Negocio') . '</td><td>' . gf_h($p['revisar']) . '</td></tr>';
    }
    echo '<tr><td colspan="5"><b>TOTAL</b></td><td>' . round($tot['cotizado']) . '</td><td>' . round($tot['a_cobrar']) . '</td><td>' . round($tot['cobrado_p']) . '</td><td>' . round($tot['gasto_p']) . '</td><td>' . round($tot['ganancia_p']) . '</td><td></td><td>' . round($tot['cobrado_t']) . '</td><td>' . round($tot['gasto_t']) . '</td><td>' . round($tot['ganancia_t']) . '</td><td>' . round($tot['pendiente']) . '</td><td></td><td></td></tr></table><br />';

    echo '<table border="1"><tr><th colspan="7">POR CLIENTE</th></tr><tr><th>Cliente</th><th>Proyectos</th><th>Ingresos</th><th>Directos</th><th>Auditoria</th><th>Contribucion</th><th>Margen %</th></tr>';
    foreach ($porCliente as $c) {
        echo '<tr><td>' . gf_h($c['cliente']) . '</td><td>' . $c['n'] . '</td><td>' . round($c['ingresos']) . '</td><td>' . round($c['directos']) . '</td><td>' . round($c['auditoria']) . '</td><td>' . round($c['contribucion']) . '</td><td>' . ($c['margen'] === null ? '' : round($c['margen'], 1)) . '</td></tr>';
    }
    echo '</table><br />';

    echo '<table border="1"><tr><th colspan="4">GASTOS GLOBALES (INDIRECTOS)</th></tr><tr><th>Tipo</th><th>Registros</th><th>Total</th><th>Clasificacion</th></tr>';
    foreach ($glob['porTipo'] as $t) {
        echo '<tr><td>' . gf_h($t['tipo']) . '</td><td>' . $t['n'] . '</td><td>' . round($t['total']) . '</td><td>' . ($t['dueno'] ? 'Gasto del dueño' : 'Negocio') . '</td></tr>';
    }
    echo '</table><br />';

    echo '<table border="1"><tr><th colspan="6">EVOLUCION MENSUAL</th></tr><tr><th>Mes</th><th>Ingresos</th><th>Directos</th><th>Auditoria</th><th>Globales</th><th>Resultado</th></tr>';
    foreach ($serie as $mes => $m) {
        echo '<tr><td>' . $mes . '</td><td>' . round($m['ingresos']) . '</td><td>' . round($m['directos']) . '</td><td>' . round($m['auditoria']) . '</td><td>' . round($m['globales']) . '</td><td>' . round($m['resultado']) . '</td></tr>';
    }
    echo '</table>';
    return;
}

// ================================================================ datos para graficas
$chartMes = [['Mes', 'Ingresos', 'Costos (directos + auditoría + globales)', 'Resultado']];
foreach ($serie as $mes => $m) {
    $chartMes[] = [$mes, round($m['ingresos']), round($m['directos'] + $m['auditoria'] + $m['globales']), round($m['resultado'])];
}
$top = array_slice($proy, 0, 12);
$chartTop = [['Proyecto', 'Ingresos', 'Gastos directos']];
foreach ($top as $p) {
    $chartTop[] = ['#' . $p['id'] . ' ' . gf_resumir($p['cliente'], 22), round($p['cobrado_p']), round($p['gasto_p'])];
}
$chartCostos = [['Concepto', 'Valor']];
if ($res['directos'] > 0) { $chartCostos[] = ['Gastos directos', round($res['directos'])]; }
if ($res['auditoria'] > 0) { $chartCostos[] = ['Auditoría', round($res['auditoria'])]; }
if ($res['globales'] > 0) { $chartCostos[] = ['Gastos globales', round($res['globales'])]; }
$chartGlob = [['Tipo', 'Total']];
foreach (array_slice($glob['porTipo'], 0, 10) as $t) {
    if ($ambito === 'negocio' && $t['dueno']) { continue; }
    if ($ambito === 'personal' && !$t['dueno']) { continue; }
    $chartGlob[] = [gf_resumir($t['tipo'], 32), round($t['total'])];
}
$js = JSON_UNESCAPED_UNICODE;
?>
<?php gf_estilos(); ?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<div class="gf-wrap">
    <div class="gf-hero">
        <h1>Rentabilidad por proyecto</h1>
        <p>Cuánto se cobró, cuánto costó y cuánto quedó &mdash; <?= gf_h($etiquetaPeriodo); ?> &middot; <?= gf_h(gf_nombre_ambito($ambito)); ?></p>
    </div>

    <?php if ($svc->getLastError() !== '') { ?>
        <div class="gf-error"><strong>No se pudo cargar parte de la información.</strong>
            <?php if ((int) ($_SESSION['superusuario'] ?? 0) === 1) { echo gf_h($svc->getLastError()); } ?></div>
    <?php } ?>
    <?php if ($svc->esRestringido()) { ?>
        <div class="gf-aviso">Su usuario tiene clientes asignados: solo se muestran esos clientes y no se muestran gastos globales.</div>
    <?php } ?>

    <form method="get" action="index.php" class="gf-card gf-noprint">
        <input type="hidden" name="doc" value="graphs_rentabilidad" />
        <div class="gf-filtros">
            <div class="gf-field"><label>Año</label>
                <select name="anio" id="gf-anio">
                    <option value="" <?= $anioSel === '' ? 'selected' : ''; ?>>Personalizado</option>
                    <option value="todos" <?= $anioSel === 'todos' ? 'selected' : ''; ?>>Todos los años</option>
                    <?php foreach (gf_anios() as $y) { ?><option value="<?= $y; ?>" <?= $anioSel === $y ? 'selected' : ''; ?>><?= $y; ?></option><?php } ?>
                </select></div>
            <div class="gf-field"><label>Fecha inicial</label><input type="date" name="fechaInicial" id="gf-fi" value="<?= gf_h($desde); ?>"></div>
            <div class="gf-field"><label>Fecha final</label><input type="date" name="fechaFinal" id="gf-ff" value="<?= gf_h($hasta); ?>"></div>
            <div class="gf-field"><label>Ámbito</label>
                <select name="ambito">
                    <?php foreach (['negocio', 'personal', 'todo'] as $a) { ?><option value="<?= $a; ?>" <?= $ambito === $a ? 'selected' : ''; ?>><?= gf_h(gf_nombre_ambito($a)); ?></option><?php } ?>
                </select></div>
            <div class="gf-field"><label>Estado de la cotización</label>
                <select name="estado"><option value="">Todos</option>
                    <?php foreach ($estadosDisponibles as $id => $nom) { ?><option value="<?= (int) $id; ?>" <?= (string) $id === $estadoSel ? 'selected' : ''; ?>><?= gf_h($nom); ?></option><?php } ?>
                </select></div>
            <div class="gf-field"><label>Buscar (cliente / proyecto)</label><input type="text" name="q" value="<?= gf_h($busqueda); ?>" placeholder="Nombre o #cotización"></div>
        </div>
        <div class="gf-acciones">
            <button type="submit" class="gf-btn gf-btn-primary"><i class="bi bi-funnel"></i> Aplicar</button>
            <a class="gf-btn gf-btn-ghost" href="index.php?doc=graphs_rentabilidad">Limpiar</a>
        </div>
        <?php gf_barra_exportar($paramsExport); ?>
    </form>
    <script>
    (function () {
        var a = document.getElementById('gf-anio'), fi = document.getElementById('gf-fi'), ff = document.getElementById('gf-ff');
        a.addEventListener('change', function () { if (a.value !== '') { fi.value = ''; ff.value = ''; } });
        [fi, ff].forEach(function (e) { e.addEventListener('change', function () { a.value = ''; }); });
    })();
    </script>

    <!-- KPIs -->
    <div class="gf-kpis">
        <div class="gf-kpi verde"><div class="t">Ingresos cobrados</div><div class="v"><?= gf_money($res['ingresos']); ?></div><div class="s"><?= (int) $res['n_proyectos']; ?> proyectos con movimiento</div></div>
        <div class="gf-kpi naranja"><div class="t">Gastos directos</div><div class="v"><?= gf_money($res['directos']); ?></div><div class="s">Ligados a cada proyecto</div></div>
        <div class="gf-kpi morado"><div class="t">Auditoría</div><div class="v"><?= gf_money($res['auditoria']); ?></div><div class="s">Pagos a personas (por cliente)</div></div>
        <div class="gf-kpi azul"><div class="t">Contribución</div><div class="v <?= gf_clase_valor($res['contribucion']); ?>"><?= gf_money($res['contribucion']); ?></div><div class="s">Margen <?= gf_pct($res['margen_contribucion']); ?></div></div>
        <div class="gf-kpi rojo"><div class="t">Gastos globales</div><div class="v"><?= gf_money($res['globales']); ?></div><div class="s"><?= $res['globales_restringido'] ? 'No disponibles para su usuario' : 'Indirectos de la empresa'; ?></div></div>
        <div class="gf-kpi <?= $res['resultado'] >= 0 ? 'verde' : 'rojo'; ?>"><div class="t">Resultado final</div><div class="v <?= gf_clase_valor($res['resultado']); ?>"><?= gf_money($res['resultado']); ?></div><div class="s">Margen <?= gf_pct($res['margen_resultado']); ?></div></div>
        <div class="gf-kpi gris"><div class="t">Pendiente por cobrar</div><div class="v"><?= gf_money($res['pendiente']); ?></div><div class="s">Cotizaciones aprobadas con saldo</div></div>
    </div>

    <div class="gf-grid2">
        <div class="gf-card">
            <h2>Estado de resultados <small><?= gf_h($etiquetaPeriodo); ?></small></h2>
            <table class="gf-resultado" style="width:100%">
                <tr><td>Ingresos cobrados (abonos)</td><td class="n" style="text-align:right"><?= gf_money($res['ingresos']); ?></td></tr>
                <tr><td>(−) Gastos directos de los proyectos</td><td style="text-align:right"><?= gf_money($res['directos']); ?></td></tr>
                <tr><td>(−) Auditoría (costo de personas)</td><td style="text-align:right"><?= gf_money($res['auditoria']); ?></td></tr>
                <tr class="total"><td>= Contribución de los proyectos</td><td style="text-align:right" class="<?= gf_clase_valor($res['contribucion']); ?>"><?= gf_money($res['contribucion']); ?></td></tr>
                <tr><td>(−) Gastos globales / indirectos</td><td style="text-align:right"><?= gf_money($res['globales']); ?></td></tr>
                <tr class="total"><td>= RESULTADO FINAL</td><td style="text-align:right" class="<?= gf_clase_valor($res['resultado']); ?>"><?= gf_money($res['resultado']); ?></td></tr>
            </table>
            <p class="gf-nota" style="margin-top:10px">Los gastos globales no se reparten entre proyectos: no existe en la base un dato que los ligue a uno solo. Se restan una vez, sobre el total.</p>
        </div>
        <div class="gf-card">
            <h2>Fuera del negocio <small>no entra en la rentabilidad</small></h2>
            <?php if ($ambito === 'negocio' && $resPersonal !== null) { ?>
                <table class="gf-resultado" style="width:100%">
                    <tr><td>Gastos del dueño (globales: diezmos, EPS, tarjetas, etc.)</td><td style="text-align:right"><?= gf_money($res['globales_dueno']); ?></td></tr>
                    <tr><td>Proyectos personales: aportes recibidos</td><td style="text-align:right"><?= gf_money($resPersonal['ingresos']); ?></td></tr>
                    <tr><td>Proyectos personales: gastos (casa, carro, impuestos...)</td><td style="text-align:right"><?= gf_money($resPersonal['directos']); ?></td></tr>
                    <tr class="total"><td>Saldo de lo personal (aportes − gastos)</td><td style="text-align:right" class="<?= gf_clase_valor($resPersonal['ingresos'] - $resPersonal['directos']); ?>"><?= gf_money($resPersonal['ingresos'] - $resPersonal['directos']); ?></td></tr>
                </table>
                <p class="gf-nota" style="margin-top:10px">Se separa con <code>cliente.interno = 1</code> y las listas de excepciones de <code>graphs_finanzas_service.php</code>. Cambie el ámbito arriba para verlo en detalle.</p>
            <?php } else { ?>
                <p class="gf-nota">Está viendo un ámbito distinto de «Solo negocio». Para ver este resumen vuelva a «Solo negocio».</p>
            <?php } ?>
        </div>
    </div>

    <!-- Graficas -->
    <div class="gf-grid2">
        <div class="gf-card"><h2>Evolución mensual</h2><div id="gf-ch-mes" class="gf-chart"></div></div>
        <div class="gf-card"><h2>Composición de los costos</h2><div id="gf-ch-costos" class="gf-chart"></div></div>
    </div>
    <div class="gf-grid2">
        <div class="gf-card"><h2>Proyectos con mayor ganancia <small>ingresos vs gastos directos</small></h2><div id="gf-ch-top" class="gf-chart" style="min-height:420px"></div></div>
        <div class="gf-card"><h2>Gastos globales por tipo <small>top 10</small></h2><div id="gf-ch-glob" class="gf-chart" style="min-height:420px"></div></div>
    </div>

    <!-- Tabla de proyectos -->
    <div class="gf-card">
        <h2>Detalle por proyecto <small><?= count($proy); ?> cotizaciones &middot; clic en el encabezado para ordenar</small></h2>
        <div class="gf-tablewrap">
        <table class="gf-table">
            <thead><tr>
                <th class="sortable">#</th><th class="sortable">Cliente</th><th>Proyecto</th><th class="sortable">Estado</th><th class="sortable">Fecha</th>
                <th class="sortable n">Cotizado</th><th class="sortable n">A cobrar</th>
                <th class="sortable n">Cobrado período</th><th class="sortable n">Gasto directo período</th><th class="sortable n">Ganancia período</th><th class="sortable n">Margen</th>
                <th class="sortable n">Cobrado total</th><th class="sortable n">Gasto total</th><th class="sortable n">Ganancia total</th><th class="sortable n">Pendiente</th><th>Alerta</th>
            </tr></thead>
            <tbody>
            <?php foreach ($proy as $p) { ?>
                <tr>
                    <td data-v="<?= $p['id']; ?>"><a href="index.php?doc=cotizacion&opc=2&id=<?= $p['id']; ?>" target="_blank">#<?= $p['id']; ?></a></td>
                    <td><?php if ($p['cliente_existe']) { ?><a href="<?= gf_h(gf_url_ficha($p['idCliente'], $ambito, $qPeriodo)); ?>"><?= gf_h($p['cliente']); ?></a><?php } else { echo gf_h($p['cliente']); } ?>
                        <?php if ($p['personal']) { ?><span class="gf-tag morado">Personal</span><?php } ?></td>
                    <td title="<?= gf_h($p['descripcion']); ?>"><?= gf_h(gf_resumir($p['descripcion'], 70)); ?></td>
                    <td><span class="gf-tag azul"><?= gf_h($p['estado_nombre']); ?></span></td>
                    <td data-v="<?= gf_h($p['fecha']); ?>"><?= gf_fecha($p['fecha']); ?></td>
                    <td class="n" data-v="<?= round($p['cotizado']); ?>"><?= gf_money($p['cotizado']); ?></td>
                    <td class="n" data-v="<?= round($p['a_cobrar']); ?>"><?= gf_money($p['a_cobrar']); ?></td>
                    <td class="n" data-v="<?= round($p['cobrado_p']); ?>"><?= gf_money($p['cobrado_p']); ?></td>
                    <td class="n" data-v="<?= round($p['gasto_p']); ?>"><?= gf_money($p['gasto_p']); ?></td>
                    <td class="n <?= gf_clase_valor($p['ganancia_p']); ?>" data-v="<?= round($p['ganancia_p']); ?>"><strong><?= gf_money($p['ganancia_p']); ?></strong></td>
                    <td class="n" data-v="<?= $p['margen_p'] === null ? -999 : round($p['margen_p'], 1); ?>"><?= gf_pct($p['margen_p']); ?></td>
                    <td class="n" data-v="<?= round($p['cobrado_t']); ?>"><?= gf_money($p['cobrado_t']); ?></td>
                    <td class="n" data-v="<?= round($p['gasto_t']); ?>"><?= gf_money($p['gasto_t']); ?></td>
                    <td class="n <?= gf_clase_valor($p['ganancia_t']); ?>" data-v="<?= round($p['ganancia_t']); ?>"><?= gf_money($p['ganancia_t']); ?></td>
                    <td class="n" data-v="<?= round($p['pendiente']); ?>"><?= $p['pendiente'] > 0 ? '<strong>' . gf_money($p['pendiente']) . '</strong>' : '—'; ?></td>
                    <td><?php if ($p['revisar'] !== '') { ?><span class="gf-tag rojo"><?= gf_h($p['revisar']); ?></span><?php } ?></td>
                </tr>
            <?php } ?>
            <?php if (!$proy) { ?><tr><td colspan="16" style="text-align:center;color:#64748b;padding:24px">No hay proyectos con movimiento para estos filtros.</td></tr><?php } ?>
            </tbody>
            <tfoot><tr>
                <td colspan="5">TOTAL</td>
                <td class="n"><?= gf_money($tot['cotizado']); ?></td><td class="n"><?= gf_money($tot['a_cobrar']); ?></td>
                <td class="n"><?= gf_money($tot['cobrado_p']); ?></td><td class="n"><?= gf_money($tot['gasto_p']); ?></td>
                <td class="n <?= gf_clase_valor($tot['ganancia_p']); ?>"><?= gf_money($tot['ganancia_p']); ?></td><td></td>
                <td class="n"><?= gf_money($tot['cobrado_t']); ?></td><td class="n"><?= gf_money($tot['gasto_t']); ?></td>
                <td class="n <?= gf_clase_valor($tot['ganancia_t']); ?>"><?= gf_money($tot['ganancia_t']); ?></td>
                <td class="n"><?= gf_money($tot['pendiente']); ?></td><td></td>
            </tr></tfoot>
        </table>
        </div>
        <p class="gf-nota" style="margin-top:8px">«Período» = movimientos con fecha dentro del rango elegido. «Total» = histórico completo de esa cotización. Pendiente = A cobrar − cobrado total, solo en estados «Aprobada y con pagos» y «Pendiente de pago».</p>
    </div>

    <!-- Por cliente -->
    <div class="gf-card">
        <h2>Resultado por cliente <small>incluye el costo de auditoría del período</small></h2>
        <div class="gf-tablewrap">
        <table class="gf-table">
            <thead><tr><th class="sortable">Cliente</th><th class="sortable n">Proyectos</th><th class="sortable n">Ingresos</th><th class="sortable n">Directos</th><th class="sortable n">Auditoría</th><th class="sortable n">Contribución</th><th class="sortable n">Margen</th><th class="sortable n">Pendiente</th></tr></thead>
            <tbody>
            <?php foreach ($porCliente as $c) { ?>
                <tr>
                    <td><a href="<?= gf_h(gf_url_ficha($c['id'], $ambito, $qPeriodo)); ?>"><?= gf_h($c['cliente']); ?></a></td>
                    <td class="n"><?= $c['n']; ?></td>
                    <td class="n" data-v="<?= round($c['ingresos']); ?>"><?= gf_money($c['ingresos']); ?></td>
                    <td class="n" data-v="<?= round($c['directos']); ?>"><?= gf_money($c['directos']); ?></td>
                    <td class="n" data-v="<?= round($c['auditoria']); ?>"><?= gf_money($c['auditoria']); ?></td>
                    <td class="n <?= gf_clase_valor($c['contribucion']); ?>" data-v="<?= round($c['contribucion']); ?>"><strong><?= gf_money($c['contribucion']); ?></strong></td>
                    <td class="n" data-v="<?= $c['margen'] === null ? -999 : round($c['margen'], 1); ?>"><?= gf_pct($c['margen']); ?></td>
                    <td class="n" data-v="<?= round($c['pendiente']); ?>"><?= $c['pendiente'] > 0 ? gf_money($c['pendiente']) : '—'; ?></td>
                </tr>
            <?php } ?>
            <?php if (!$porCliente) { ?><tr><td colspan="8" style="text-align:center;color:#64748b;padding:24px">Sin datos.</td></tr><?php } ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Globales -->
    <div class="gf-card">
        <h2>Gastos globales (indirectos) por tipo <small><?= gf_h($etiquetaPeriodo); ?></small></h2>
        <?php if (!empty($glob['restringido'])) { ?>
            <p class="gf-nota">No disponible para su usuario.</p>
        <?php } else { ?>
        <div class="gf-tablewrap">
        <table class="gf-table">
            <thead><tr><th class="sortable">Tipo de gasto</th><th class="sortable n">Registros</th><th class="sortable n">Total</th><th>Clasificación</th></tr></thead>
            <tbody>
            <?php foreach ($glob['porTipo'] as $t) { ?>
                <tr>
                    <td><?= gf_h($t['tipo']); ?></td><td class="n"><?= $t['n']; ?></td>
                    <td class="n" data-v="<?= round($t['total']); ?>"><?= gf_money($t['total']); ?></td>
                    <td><?= $t['dueno'] ? '<span class="gf-tag morado">Gasto del dueño</span>' : '<span class="gf-tag verde">Negocio</span>'; ?></td>
                </tr>
            <?php } ?>
            <?php if (!$glob['porTipo']) { ?><tr><td colspan="4" style="text-align:center;color:#64748b;padding:24px">Sin gastos globales en el período.</td></tr><?php } ?>
            </tbody>
            <tfoot><tr><td>TOTAL NEGOCIO</td><td></td><td class="n"><?= gf_money($res['globales_negocio']); ?></td><td></td></tr>
                   <tr><td>TOTAL GASTOS DEL DUEÑO</td><td></td><td class="n"><?= gf_money($res['globales_dueno']); ?></td><td></td></tr></tfoot>
        </table>
        </div>
        <p class="gf-nota" style="margin-top:8px">La clasificación «Gasto del dueño» es provisional (por el nombre del tipo). Se ajusta en <code>GF_TIPOS_GASTO_DUENO</code>, en <code>graphs_finanzas_service.php</code>.</p>
        <?php } ?>
    </div>

    <!-- Datos a revisar -->
    <?php if ($revisar) { ?>
    <div class="gf-card">
        <h2>Datos a revisar <small><?= count($revisar); ?> alertas</small></h2>
        <div class="gf-tablewrap">
        <table class="gf-table">
            <thead><tr><th>#</th><th>Cliente</th><th>Estado</th><th class="n">Cobrado total</th><th class="n">Gasto total</th><th>Alerta</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($revisar, 0, 60) as $p) { ?>
                <tr><td><a href="index.php?doc=cotizacion&opc=2&id=<?= $p['id']; ?>" target="_blank">#<?= $p['id']; ?></a></td><td><?= gf_h($p['cliente']); ?></td>
                    <td><?= gf_h($p['estado_nombre']); ?></td><td class="n"><?= gf_money($p['cobrado_t']); ?></td><td class="n"><?= gf_money($p['gasto_t']); ?></td>
                    <td><span class="gf-tag rojo"><?= gf_h($p['revisar']); ?></span></td></tr>
            <?php } ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php } ?>

    <!-- Metodologia -->
    <div class="gf-card">
        <h2>Cómo se calcula</h2>
        <ul class="gf-nota" style="margin:0;padding-left:18px">
            <li><b>Ingresos</b>: suma de la tabla <code>abonos</code> por cotización (no se usa <code>valorRecibido</code>, que no coincide con los abonos).</li>
            <li><b>Gastos directos</b>: tabla <code>gastos</code> con cotización asignada. <b>Globales</b>: <code>gastos</code> sin cotización (<code>idCotizacion = 0</code>).</li>
            <li><b>Auditoría</b>: tabla <code>gastosauditoria</code>, imputada al cliente (no existe vínculo con una cotización concreta) y restada de la contribución.</li>
            <li><b>Negocio / personal</b>: <code>cliente.interno</code>. Los clientes con cotizaciones cuyo cliente fue eliminado se muestran como «Cliente eliminado».</li>
            <li>Los movimientos se asignan al período por la fecha del abono o del gasto (<code>fechaCreacion</code>).</li>
        </ul>
    </div>
</div>

<script>
google.charts.load('current', {packages: ['corechart']});
google.charts.setOnLoadCallback(gfDibujar);
var gfMes = <?= json_encode($chartMes, $js); ?>;
var gfTop = <?= json_encode($chartTop, $js); ?>;
var gfCostos = <?= json_encode($chartCostos, $js); ?>;
var gfGlob = <?= json_encode($chartGlob, $js); ?>;
function gfDibujar() {
    var fmt = new google.visualization.NumberFormat({prefix: '$', groupingSymbol: '.', decimalSymbol: ',', fractionDigits: 0});
    function vacio(id) { document.getElementById(id).innerHTML = '<p class="gf-nota" style="padding:30px;text-align:center">Sin datos para graficar.</p>'; }
    if (gfMes.length > 1) {
        var d = google.visualization.arrayToDataTable(gfMes); fmt.format(d, 1); fmt.format(d, 2); fmt.format(d, 3);
        new google.visualization.ComboChart(document.getElementById('gf-ch-mes')).draw(d, {
            seriesType: 'bars', series: {2: {type: 'line', pointSize: 5}}, colors: ['#16a34a', '#ea580c', '#1d4ed8'],
            legend: {position: 'bottom'}, vAxis: {format: 'short'}, chartArea: {width: '82%'}
        });
    } else { vacio('gf-ch-mes'); }
    if (gfCostos.length > 1) {
        var d2 = google.visualization.arrayToDataTable(gfCostos); fmt.format(d2, 1);
        new google.visualization.PieChart(document.getElementById('gf-ch-costos')).draw(d2, {pieHole: 0.45, colors: ['#ea580c', '#7c3aed', '#dc2626'], legend: {position: 'bottom'}, chartArea: {width: '90%'}});
    } else { vacio('gf-ch-costos'); }
    if (gfTop.length > 1) {
        var d3 = google.visualization.arrayToDataTable(gfTop); fmt.format(d3, 1); fmt.format(d3, 2);
        new google.visualization.BarChart(document.getElementById('gf-ch-top')).draw(d3, {colors: ['#16a34a', '#ea580c'], legend: {position: 'bottom'}, hAxis: {format: 'short'}, chartArea: {left: 170, width: '65%'}});
    } else { vacio('gf-ch-top'); }
    if (gfGlob.length > 1) {
        var d4 = google.visualization.arrayToDataTable(gfGlob); fmt.format(d4, 1);
        new google.visualization.BarChart(document.getElementById('gf-ch-glob')).draw(d4, {colors: ['#dc2626'], legend: {position: 'none'}, hAxis: {format: 'short'}, chartArea: {left: 200, width: '60%'}});
    } else { vacio('gf-ch-glob'); }
}
window.addEventListener('resize', function () { if (window.google && google.visualization) { gfDibujar(); } });
window.addEventListener('beforeprint', function () { if (window.google && google.visualization) { gfDibujar(); } });
</script>
<?php gf_script_tablas(); ?>
