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
$chartMes = [['Mes', 'Ingresos', 'Costos', 'Resultado']];
$mejorMes = $peorMes = null;
foreach ($serie as $mes => $m) {
    $chartMes[] = [gf_mes_corto($mes), round($m['ingresos']), round($m['directos'] + $m['auditoria'] + $m['globales']), round($m['resultado'])];
    if ($mejorMes === null || $m['resultado'] > $serie[$mejorMes]['resultado']) { $mejorMes = $mes; }
    if ($peorMes === null || $m['resultado'] < $serie[$peorMes]['resultado']) { $peorMes = $mes; }
}
$datoMes = $mejorMes === null ? '' : 'Mejor mes: ' . gf_mes_corto($mejorMes) . ' (' . gf_money_corto($serie[$mejorMes]['resultado']) . '). Peor: '
    . gf_mes_corto($peorMes) . ' (' . gf_money_corto($serie[$peorMes]['resultado']) . ').';

$costos = [
    ['l' => 'Gastos directos', 'v' => round($res['directos']), 'c' => '#eb6834'],
    ['l' => 'Auditoría', 'v' => round($res['auditoria']), 'c' => '#6250d6'],
    ['l' => 'Gastos globales', 'v' => round($res['globales']), 'c' => '#e87ba4'],
];
$totCostos = 0;
foreach ($costos as $c) { $totCostos += $c['v']; }
$chartCostos = [];
$mayorCosto = null;
foreach ($costos as $c) {
    if ($c['v'] <= 0) { continue; }
    $c['txt'] = gf_money_corto($c['v']) . ' · ' . number_format($c['v'] / $totCostos * 100, 0) . ' %';
    $chartCostos[] = $c;
    if ($mayorCosto === null || $c['v'] > $mayorCosto['v']) { $mayorCosto = $c; }
}
$datoCostos = $mayorCosto ? 'Costo total ' . gf_money_corto($totCostos) . '. El mayor: ' . strtolower($mayorCosto['l']) . ' (' . $mayorCosto['txt'] . ').' : '';

$top = array_slice($proy, 0, 10);
$chartTop = [['Proyecto', 'Ingresos', 'Gastos directos']];
foreach ($top as $p) {
    $chartTop[] = ['#' . $p['id'] . ' ' . gf_resumir($p['cliente'], 18), round($p['cobrado_p']), round($p['gasto_p'])];
}
$datoTop = $top ? 'Más rentable: #' . $top[0]['id'] . ' ' . gf_resumir($top[0]['cliente'], 28) . ' (' . gf_money_corto($top[0]['ganancia_p']) . ').' : '';

$chartGlob = [['Tipo', 'Total']];
$totGlob = 0;
$filasGlob = [];
foreach ($glob['porTipo'] as $t) {
    if ($ambito === 'negocio' && $t['dueno']) { continue; }
    if ($ambito === 'personal' && !$t['dueno']) { continue; }
    $filasGlob[] = $t;
    $totGlob += $t['total'];
}
foreach (array_slice($filasGlob, 0, 8) as $t) { $chartGlob[] = [gf_resumir($t['tipo'], 24), round($t['total'])]; }
$datoGlob = $filasGlob ? 'Mayor gasto: ' . gf_resumir($filasGlob[0]['tipo'], 30) . ' (' . number_format($totGlob > 0 ? $filasGlob[0]['total'] / $totGlob * 100 : 0, 0) . ' % del total).' : '';

$datosJs = ['mes' => $chartMes, 'costos' => $chartCostos, 'top' => $chartTop, 'glob' => $chartGlob];
$js = JSON_UNESCAPED_UNICODE;
gf_estilos();
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<div class="gf-wrap">
    <div class="gf-hero">
        <h1>Rentabilidad por proyecto</h1>
        <p><?= gf_h($etiquetaPeriodo); ?> &middot; <?= gf_h(gf_nombre_ambito($ambito)); ?></p>
    </div>

    <?php if ($svc->getLastError() !== '') { ?>
        <div class="gf-error"><strong>No se pudo cargar parte de la información.</strong>
            <?php if ((int) ($_SESSION['superusuario'] ?? 0) === 1) { echo gf_h($svc->getLastError()); } ?></div>
    <?php } ?>
    <?php if ($svc->esRestringido()) { ?>
        <div class="gf-aviso">Solo se muestran sus clientes asignados; no se incluyen gastos globales.</div>
    <?php } ?>

    <form method="get" action="index.php" class="gf-card gf-noprint">
        <input type="hidden" name="doc" value="graphs_rentabilidad" />
        <details class="gf-filtros-wrap" open>
            <summary>Filtros</summary>
            <div class="gf-filtros">
                <div class="gf-field"><label>Año</label>
                    <select name="anio" id="gf-anio">
                        <option value="" <?= $anioSel === '' ? 'selected' : ''; ?>>Personalizado</option>
                        <option value="todos" <?= $anioSel === 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <?php foreach (gf_anios() as $y) { ?><option value="<?= $y; ?>" <?= $anioSel === $y ? 'selected' : ''; ?>><?= $y; ?></option><?php } ?>
                    </select></div>
                <div class="gf-field"><label>Desde</label><input type="date" name="fechaInicial" id="gf-fi" value="<?= gf_h($desde); ?>"></div>
                <div class="gf-field"><label>Hasta</label><input type="date" name="fechaFinal" id="gf-ff" value="<?= gf_h($hasta); ?>"></div>
                <div class="gf-field"><label>Ámbito</label>
                    <select name="ambito">
                        <?php foreach (['negocio', 'personal', 'todo'] as $a) { ?><option value="<?= $a; ?>" <?= $ambito === $a ? 'selected' : ''; ?>><?= gf_h(gf_nombre_ambito($a)); ?></option><?php } ?>
                    </select></div>
                <div class="gf-field"><label>Estado</label>
                    <select name="estado"><option value="">Todos</option>
                        <?php foreach ($estadosDisponibles as $id => $nom) { ?><option value="<?= (int) $id; ?>" <?= (string) $id === $estadoSel ? 'selected' : ''; ?>><?= gf_h($nom); ?></option><?php } ?>
                    </select></div>
                <div class="gf-field"><label>Buscar</label><input type="text" name="q" value="<?= gf_h($busqueda); ?>" placeholder="Cliente o #cotización"></div>
            </div>
            <div class="gf-acciones">
                <button type="submit" class="gf-btn gf-btn-primary">Aplicar</button>
                <a class="gf-btn gf-btn-ghost" href="index.php?doc=graphs_rentabilidad">Limpiar</a>
            </div>
        </details>
        <?php gf_barra_exportar($paramsExport); ?>
    </form>

    <!-- KPIs -->
    <div class="gf-kpis">
        <div class="gf-kpi azul"><div class="t">Ingresos cobrados</div><div class="v"><?= gf_money($res['ingresos']); ?></div><div class="s"><?= (int) $res['n_proyectos']; ?> proyectos</div></div>
        <div class="gf-kpi naranja"><div class="t">Gastos directos</div><div class="v"><?= gf_money($res['directos']); ?></div><div class="s">de los proyectos</div></div>
        <div class="gf-kpi morado"><div class="t">Auditoría</div><div class="v"><?= gf_money($res['auditoria']); ?></div><div class="s">pagos a personas</div></div>
        <div class="gf-kpi verde"><div class="t">Contribución</div><div class="v <?= gf_clase_valor($res['contribucion']); ?>"><?= gf_money($res['contribucion']); ?></div><div class="s">margen <?= gf_pct($res['margen_contribucion']); ?></div></div>
        <div class="gf-kpi magenta"><div class="t">Gastos globales</div><div class="v"><?= gf_money($res['globales']); ?></div><div class="s"><?= $res['globales_restringido'] ? 'no disponibles' : 'indirectos'; ?></div></div>
        <div class="gf-kpi azul"><div class="t">Resultado final</div><div class="v <?= gf_clase_valor($res['resultado']); ?>"><?= gf_money($res['resultado']); ?></div><div class="s">margen <?= gf_pct($res['margen_resultado']); ?></div></div>
        <div class="gf-kpi ambar"><div class="t">Por cobrar</div><div class="v"><?= gf_money($res['pendiente']); ?></div><div class="s">cartera aprobada</div></div>
    </div>

    <div class="gf-grid2">
        <section class="gf-card">
            <h2>Estado de resultados</h2>
            <table class="gf-resultado">
                <tr><td>Ingresos cobrados</td><td><?= gf_money($res['ingresos']); ?></td></tr>
                <tr><td>− Gastos directos</td><td><?= gf_money($res['directos']); ?></td></tr>
                <tr><td>− Auditoría</td><td><?= gf_money($res['auditoria']); ?></td></tr>
                <tr class="total"><td>Contribución</td><td class="<?= gf_clase_valor($res['contribucion']); ?>"><?= gf_money($res['contribucion']); ?></td></tr>
                <tr><td>− Gastos globales</td><td><?= gf_money($res['globales']); ?></td></tr>
                <tr class="total"><td>Resultado final</td><td class="<?= gf_clase_valor($res['resultado']); ?>"><?= gf_money($res['resultado']); ?></td></tr>
            </table>
            <p class="gf-nota" style="margin:10px 0 0">Los gastos globales no se reparten por proyecto: se restan una sola vez.</p>
        </section>
        <section class="gf-card">
            <h2>Fuera del negocio</h2>
            <?php if ($ambito === 'negocio' && $resPersonal !== null) { ?>
                <table class="gf-resultado">
                    <tr><td>Gastos del dueño (globales)</td><td><?= gf_money($res['globales_dueno']); ?></td></tr>
                    <tr><td>Personal: aportes recibidos</td><td><?= gf_money($resPersonal['ingresos']); ?></td></tr>
                    <tr><td>Personal: gastos (casa, carro…)</td><td><?= gf_money($resPersonal['directos']); ?></td></tr>
                    <tr class="total"><td>Saldo personal</td><td class="<?= gf_clase_valor($resPersonal['ingresos'] - $resPersonal['directos']); ?>"><?= gf_money($resPersonal['ingresos'] - $resPersonal['directos']); ?></td></tr>
                </table>
                <p class="gf-nota" style="margin:10px 0 0">No entra en la rentabilidad del negocio.</p>
            <?php } else { ?>
                <p class="gf-nota">Disponible en el ámbito «Solo negocio».</p>
            <?php } ?>
        </section>
    </div>

    <!-- Graficas -->
    <div class="gf-grid2">
        <?php gf_grafica('gf-ch-mes', 'Evolución mensual', [
            'que' => 'Lo que entró, lo que costó y lo que quedó, mes a mes.',
            'leer' => 'Barras azules = ingresos; naranjas = costos. La línea verde es el resultado: sobre cero hubo ganancia.',
            'ojo' => 'Los costos suman directos, auditoría y gastos globales.',
            'dato' => $datoMes,
        ], 280); ?>
        <?php gf_grafica('gf-ch-costos', 'En qué se van los costos', [
            'que' => 'Reparto del costo total del período.',
            'leer' => 'Cada porción es un tipo de costo; la leyenda indica monto y porcentaje.',
            'ojo' => 'Auditoría se carga al cliente y los globales a toda la empresa.',
            'dato' => $datoCostos,
        ], 280); ?>
    </div>
    <div class="gf-grid2">
        <?php gf_grafica('gf-ch-top', 'Proyectos con más ganancia', [
            'que' => 'Los 10 proyectos que más dejaron en el período.',
            'leer' => 'Compare la barra azul (cobrado) con la naranja (gasto directo): entre más distancia, mejor.',
            'ojo' => 'No descuenta auditoría ni gastos globales.',
            'dato' => $datoTop,
        ], 340); ?>
        <?php gf_grafica('gf-ch-glob', 'Gastos globales por tipo', [
            'que' => 'Gastos de la empresa que no pertenecen a un solo proyecto.',
            'leer' => 'Barra más larga = tipo de gasto más pesado. Se muestran los 8 mayores.',
            'ojo' => 'Con «Solo negocio» se excluyen los gastos del dueño.',
            'dato' => $datoGlob,
        ], 340); ?>
    </div>

    <!-- Tabla de proyectos -->
    <section class="gf-card">
        <h2>Detalle por proyecto <small><?= count($proy); ?> · clic en un encabezado para ordenar</small></h2>
        <div class="gf-tablewrap">
        <table class="gf-table gf-t-proy">
            <thead><tr>
                <th class="sortable">#</th><th class="sortable">Cliente</th><th>Proyecto</th><th class="sortable">Estado</th><th class="sortable">Fecha</th>
                <th class="sortable n">Cotizado</th><th class="sortable n">A cobrar</th>
                <th class="sortable n">Cobrado</th><th class="sortable n">Gasto</th><th class="sortable n">Ganancia</th><th class="sortable n">Margen</th>
                <th class="sortable n">Cobrado total</th><th class="sortable n">Gasto total</th><th class="sortable n">Ganancia total</th><th class="sortable n">Pendiente</th><th>Alerta</th>
            </tr></thead>
            <tbody>
            <?php foreach ($proy as $p) { ?>
                <tr>
                    <td data-v="<?= $p['id']; ?>"><a href="index.php?doc=cotizacion&opc=2&id=<?= $p['id']; ?>" target="_blank">#<?= $p['id']; ?></a></td>
                    <td><?php if ($p['cliente_existe']) { ?><a href="<?= gf_h(gf_url_ficha($p['idCliente'], $ambito, $qPeriodo)); ?>"><?= gf_h($p['cliente']); ?></a><?php } else { echo gf_h($p['cliente']); } ?>
                        <?php if ($p['personal']) { ?><span class="gf-tag morado">Personal</span><?php } ?></td>
                    <td title="<?= gf_h($p['descripcion']); ?>"><?= gf_h(gf_resumir($p['descripcion'], 60)); ?></td>
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
            <?php if (!$proy) { ?><tr><td colspan="16" class="gf-vacio">No hay proyectos con movimiento para estos filtros.</td></tr><?php } ?>
            </tbody>
            <tfoot><tr>
                <td colspan="5">Total</td>
                <td class="n"><?= gf_money($tot['cotizado']); ?></td><td class="n"><?= gf_money($tot['a_cobrar']); ?></td>
                <td class="n"><?= gf_money($tot['cobrado_p']); ?></td><td class="n"><?= gf_money($tot['gasto_p']); ?></td>
                <td class="n <?= gf_clase_valor($tot['ganancia_p']); ?>"><?= gf_money($tot['ganancia_p']); ?></td><td></td>
                <td class="n"><?= gf_money($tot['cobrado_t']); ?></td><td class="n"><?= gf_money($tot['gasto_t']); ?></td>
                <td class="n <?= gf_clase_valor($tot['ganancia_t']); ?>"><?= gf_money($tot['ganancia_t']); ?></td>
                <td class="n"><?= gf_money($tot['pendiente']); ?></td><td></td>
            </tr></tfoot>
        </table>
        </div>
        <p class="gf-nota" style="margin:8px 0 0">Cobrado, gasto y ganancia = movimientos del período. «Total» = histórico de la cotización.</p>
    </section>

    <!-- Por cliente -->
    <section class="gf-card">
        <h2>Resultado por cliente <small>incluye auditoría</small></h2>
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
            <?php if (!$porCliente) { ?><tr><td colspan="8" class="gf-vacio">Sin datos.</td></tr><?php } ?>
            </tbody>
        </table>
        </div>
    </section>

    <!-- Globales -->
    <section class="gf-card">
        <h2>Gastos globales por tipo</h2>
        <?php if (!empty($glob['restringido'])) { ?>
            <p class="gf-nota">No disponible para su usuario.</p>
        <?php } else { ?>
        <div class="gf-tablewrap">
        <table class="gf-table">
            <thead><tr><th class="sortable">Tipo</th><th class="sortable n">Registros</th><th class="sortable n">Total</th><th>Clasificación</th></tr></thead>
            <tbody>
            <?php foreach ($glob['porTipo'] as $t) { ?>
                <tr>
                    <td><?= gf_h($t['tipo']); ?></td><td class="n"><?= $t['n']; ?></td>
                    <td class="n" data-v="<?= round($t['total']); ?>"><?= gf_money($t['total']); ?></td>
                    <td><?= $t['dueno'] ? '<span class="gf-tag morado">Del dueño</span>' : '<span class="gf-tag verde">Negocio</span>'; ?></td>
                </tr>
            <?php } ?>
            <?php if (!$glob['porTipo']) { ?><tr><td colspan="4" class="gf-vacio">Sin gastos globales en el período.</td></tr><?php } ?>
            </tbody>
            <tfoot>
                <tr><td>Total negocio</td><td></td><td class="n"><?= gf_money($res['globales_negocio']); ?></td><td></td></tr>
                <tr><td>Total del dueño</td><td></td><td class="n"><?= gf_money($res['globales_dueno']); ?></td><td></td></tr>
            </tfoot>
        </table>
        </div>
        <p class="gf-nota" style="margin:8px 0 0">La clasificación «Del dueño» es provisional (según el nombre del tipo).</p>
        <?php } ?>
    </section>

    <?php if ($revisar) { ?>
    <section class="gf-card">
        <h2>Datos a revisar <small><?= count($revisar); ?></small></h2>
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
    </section>
    <?php } ?>

    <details class="gf-det">
        <summary>Cómo se calcula</summary>
        <ul class="gf-nota">
            <li><b>Ingresos:</b> abonos por cotización (no <code>valorRecibido</code>).</li>
            <li><b>Directos:</b> gastos con cotización. <b>Globales:</b> gastos sin cotización.</li>
            <li><b>Auditoría:</b> pagos a personas, cargados al cliente.</li>
            <li><b>Negocio / personal:</b> según <code>cliente.interno</code> y las excepciones del servicio.</li>
            <li>El período se toma por la fecha de cada abono o gasto.</li>
        </ul>
    </details>
</div>

<?php gf_script_base(); ?>
<script>
var gfD = <?= json_encode($datosJs, $js); ?>;
google.charts.load('current', {packages: ['corechart']});
google.charts.setOnLoadCallback(gfDibujar);
function gfDibujar() {
    var G = GF, C = G.C, d;
    // Evolucion mensual
    if (gfD.mes.length > 1) {
        d = google.visualization.arrayToDataTable(gfD.mes); G.formatear(d, [1, 2, 3]);
        G.leyenda('gf-ch-mes', [{color: C.azul, label: 'Ingresos'}, {color: C.naranja, label: 'Costos'}, {color: C.aqua, label: 'Resultado'}]);
        new google.visualization.ComboChart(document.getElementById('gf-ch-mes')).draw(d, G.base({
            seriesType: 'bars', series: {2: {type: 'line', pointSize: 4, lineWidth: 2}}, colors: [C.azul, C.naranja, C.aqua], bar: {groupWidth: '60%'}
        }));
    } else { G.vacio('gf-ch-mes'); }
    // Composicion de costos
    if (gfD.costos.length) {
        d = new google.visualization.DataTable(); d.addColumn('string', 'Concepto'); d.addColumn('number', 'Valor');
        gfD.costos.forEach(function (c) { d.addRow([c.l, c.v]); }); G.formatear(d, [1]);
        G.leyenda('gf-ch-costos', gfD.costos.map(function (c) { return {color: c.c, label: c.l, valor: c.txt}; }));
        new google.visualization.PieChart(document.getElementById('gf-ch-costos')).draw(d, G.base({
            pieHole: 0.62, pieSliceText: 'none', pieSliceBorderColor: '#fff', chartArea: {left: 0, top: 4, right: 0, bottom: 4, width: '100%', height: '92%'},
            colors: gfD.costos.map(function (c) { return c.c; })
        }));
    } else { G.vacio('gf-ch-costos'); }
    // Proyectos con mas ganancia
    if (gfD.top.length > 1) {
        d = google.visualization.arrayToDataTable(gfD.top); G.formatear(d, [1, 2]);
        G.leyenda('gf-ch-top', [{color: C.azul, label: 'Cobrado'}, {color: C.naranja, label: 'Gasto directo'}]);
        new google.visualization.BarChart(document.getElementById('gf-ch-top')).draw(d, G.base({
            colors: [C.azul, C.naranja], bar: {groupWidth: '70%'}, chartArea: {left: 130, top: 4, right: 8, bottom: 22, width: '100%', height: '88%'},
            hAxis: {format: 'short', gridlines: {color: C.grid}}, vAxis: {gridlines: {color: 'transparent'}, format: ''}
        }));
    } else { G.vacio('gf-ch-top'); }
    // Gastos globales por tipo
    if (gfD.glob.length > 1) {
        d = google.visualization.arrayToDataTable(gfD.glob); G.formatear(d, [1]);
        new google.visualization.BarChart(document.getElementById('gf-ch-glob')).draw(d, G.base({
            colors: [C.magenta], bar: {groupWidth: '65%'}, chartArea: {left: 150, top: 4, right: 8, bottom: 22, width: '100%', height: '88%'},
            hAxis: {format: 'short', gridlines: {color: C.grid}}, vAxis: {gridlines: {color: 'transparent'}, format: ''}
        }));
    } else { G.vacio('gf-ch-glob'); }
}
(function () {
    var a = document.getElementById('gf-anio'), fi = document.getElementById('gf-fi'), ff = document.getElementById('gf-ff');
    if (!a) { return; }
    a.addEventListener('change', function () { if (a.value !== '') { fi.value = ''; ff.value = ''; } });
    [fi, ff].forEach(function (e) { e.addEventListener('change', function () { a.value = ''; }); });
    var t;
    function redibujar() { clearTimeout(t); t = setTimeout(function () { if (window.google && google.visualization) { gfDibujar(); } }, 150); }
    window.addEventListener('resize', redibujar);
    window.addEventListener('beforeprint', redibujar);
})();
</script>
