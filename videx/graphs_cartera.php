<?php
/*******************************************
Cartera y seguimiento de clientes (GRAFICAS)

Una fila por cliente con su dinero (cobrado, ganancia, pendiente por cobrar)
y su ultima comunicacion (cuando, quien, por que medio, que se hablo), con
semaforo de dias sin contacto. Con ?cliente=ID abre la ficha 360 del cliente.
Exporta a Excel (?excelX=1) y a PDF (impresion del navegador).

SOLO LECTURA: no escribe nada en la base de datos.
Requiere una fila en menu_graphs con php = 'graphs_cartera'
(ver sql_menu_graficas_finanzas.sql).
*******************************************/

require_once(__DIR__ . '/graphs_finanzas_service.php');
require_once(__DIR__ . '/graphs_finanzas_ui.php');

gf_requerir_acceso('graphs_cartera');

$svc = new GraphsFinanzasService();
list($desde, $hasta, $anioSel) = GraphsFinanzasService::rango($_GET);
$ambito = gf_ambito(isset($_GET['ambito']) ? $_GET['ambito'] : 'negocio');
$idFicha = isset($_GET['cliente']) ? (int) preg_replace('/\D/', '', (string) $_GET['cliente']) : 0;
$esExcel = isset($_GET['excelX']);
$etiquetaPeriodo = gf_fecha($desde) . ' al ' . gf_fecha($hasta);

$qPeriodo = $anioSel !== '' ? ['anio' => $anioSel] : ['fechaInicial' => $desde, 'fechaFinal' => $hasta];

// =====================================================================
// FICHA 360 DE UN CLIENTE
// =====================================================================
if ($idFicha > 0) {
    $ficha = $svc->ficha($idFicha, $desde, $hasta);
    $paramsExport = array_merge(['doc' => 'graphs_cartera', 'cliente' => $idFicha, 'ambito' => $ambito], $qPeriodo);

    if ($ficha === null) {
        if ($esExcel) { echo 'Cliente no encontrado.'; return; }
        gf_estilos();
        echo '<div class="gf-wrap"><div class="gf-error">Cliente no encontrado o sin acceso. <a href="index.php?doc=graphs_cartera">Volver a la cartera</a></div></div>';
        return;
    }
    $cli = $ficha['cliente'];
    $cots = $ficha['cotizaciones'];
    $seg = $ficha['seguimientos'];
    $sumCobP = $sumGasP = $sumCobT = $sumGasT = $pend = $cotizado = 0.0;
    foreach ($cots as $c) {
        $sumCobP += $c['cobrado_p']; $sumGasP += $c['gasto_p']; $sumCobT += $c['cobrado_t'];
        $sumGasT += $c['gasto_t']; $pend += $c['pendiente']; $cotizado += $c['cotizado'];
    }
    $gananciaP = $sumCobP - $sumGasP - $ficha['aud_periodo'];
    $gananciaT = $sumCobT - $sumGasT - $ficha['aud_total'];
    $porMedio = [];
    $nPeriodo = 0;
    foreach ($seg as $s) {
        if (!isset($porMedio[$s['medio']])) { $porMedio[$s['medio']] = ['total' => 0, 'periodo' => 0, 'ultimo' => '']; }
        $porMedio[$s['medio']]['total']++;
        if ($porMedio[$s['medio']]['ultimo'] === '') { $porMedio[$s['medio']]['ultimo'] = $s['fecha']; }
        $dia = substr($s['fecha'], 0, 10);
        if ($dia >= $desde && $dia <= $hasta) { $porMedio[$s['medio']]['periodo']++; $nPeriodo++; }
    }
    $ultimo = $seg ? $seg[0] : null;
    $dias = $ultimo ? (int) floor((strtotime(date('Y-m-d')) - strtotime(substr($ultimo['fecha'], 0, 10))) / 86400) : null;
    $sem = GraphsFinanzasService::semaforo($dias);

    // ------------------------------------------------------------ Excel de la ficha
    if ($esExcel) {
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<h3>Ficha del cliente: ' . gf_h($cli['nombre']) . ' &mdash; ' . gf_h($etiquetaPeriodo) . '</h3>';
        echo '<table border="1"><tr><th colspan="2">DATOS</th></tr>'
            . '<tr><td>NIT</td><td>' . gf_h($cli['nit']) . '</td></tr><tr><td>Representante</td><td>' . gf_h($cli['representante_legal']) . '</td></tr>'
            . '<tr><td>Email</td><td>' . gf_h($cli['email']) . '</td></tr><tr><td>Telefono / celular</td><td>' . gf_h($cli['telefono'] . ' / ' . $cli['celular']) . '</td></tr>'
            . '<tr><td>Comercial</td><td>' . gf_h($cli['comercial']) . '</td></tr><tr><td>Tipo</td><td>' . gf_h($cli['tipo_nombre']) . '</td></tr>'
            . '<tr><td>Cobrado periodo</td><td>' . round($sumCobP) . '</td></tr><tr><td>Gasto directo periodo</td><td>' . round($sumGasP) . '</td></tr>'
            . '<tr><td>Auditoria periodo</td><td>' . round($ficha['aud_periodo']) . '</td></tr><tr><td>Ganancia periodo</td><td>' . round($gananciaP) . '</td></tr>'
            . '<tr><td>Cobrado total</td><td>' . round($sumCobT) . '</td></tr><tr><td>Pendiente por cobrar</td><td>' . round($pend) . '</td></tr>'
            . '<tr><td>Ultimo contacto</td><td>' . ($ultimo ? gf_h(gf_fecha_hora($ultimo['fecha']) . ' - ' . $ultimo['medio'] . ' - ' . $ultimo['usuario']) : 'Sin registro') . '</td></tr></table><br />';
        echo '<table border="1"><tr><th colspan="8">COTIZACIONES</th></tr><tr><th>#</th><th>Descripcion</th><th>Estado</th><th>Fecha</th><th>A cobrar</th><th>Cobrado total</th><th>Gasto total</th><th>Pendiente</th></tr>';
        foreach ($cots as $c) {
            echo '<tr><td>' . $c['id'] . '</td><td>' . gf_h(gf_resumir($c['descripcion'], 150)) . '</td><td>' . gf_h($c['estado_nombre']) . '</td><td>' . gf_fecha($c['fecha']) . '</td><td>' . round($c['a_cobrar']) . '</td><td>' . round($c['cobrado_t']) . '</td><td>' . round($c['gasto_t']) . '</td><td>' . round($c['pendiente']) . '</td></tr>';
        }
        echo '</table><br /><table border="1"><tr><th colspan="6">HISTORIAL DE COMUNICACION (' . count($seg) . ')</th></tr><tr><th>Fecha</th><th>Medio</th><th>Quien (VidEx)</th><th>Cotizacion</th><th>Contacto</th><th>Detalle</th></tr>';
        foreach ($seg as $s) {
            echo '<tr><td>' . gf_h(gf_fecha_hora($s['fecha'])) . '</td><td>' . gf_h($s['medio']) . '</td><td>' . gf_h($s['usuario']) . '</td><td>' . ($s['idCotizacion'] ?: '') . '</td><td>' . gf_h($s['contacto']) . '</td><td>' . gf_h($s['observacion']) . '</td></tr>';
        }
        echo '</table><br /><table border="1"><tr><th colspan="4">ABONOS RECIENTES</th></tr><tr><th>Fecha</th><th>Cotizacion</th><th>Tipo</th><th>Valor</th></tr>';
        foreach ($ficha['abonos'] as $a) {
            echo '<tr><td>' . gf_fecha($a['fecha']) . '</td><td>' . $a['idCotizacion'] . '</td><td>' . gf_h($a['tipo']) . '</td><td>' . round($a['valor']) . '</td></tr>';
        }
        echo '</table><br /><table border="1"><tr><th colspan="5">GASTOS DIRECTOS RECIENTES</th></tr><tr><th>Fecha</th><th>Cotizacion</th><th>Tipo</th><th>Observacion</th><th>Valor</th></tr>';
        foreach ($ficha['gastos'] as $g) {
            echo '<tr><td>' . gf_fecha($g['fecha']) . '</td><td>' . $g['idCotizacion'] . '</td><td>' . gf_h($g['tipo']) . '</td><td>' . gf_h(gf_resumir($g['observacion'], 150)) . '</td><td>' . round($g['valor']) . '</td></tr>';
        }
        echo '</table><br /><table border="1"><tr><th colspan="5">AUDITORIA POR PERSONA</th></tr><tr><th>Persona</th><th>Tipo</th><th>Registros</th><th>Total</th><th>Ultimo</th></tr>';
        foreach ($ficha['auditoria'] as $a) {
            echo '<tr><td>' . gf_h($a['persona']) . '</td><td>' . gf_h($a['tipo']) . '</td><td>' . $a['n'] . '</td><td>' . round($a['total']) . '</td><td>' . gf_fecha($a['ultimo']) . '</td></tr>';
        }
        echo '</table>';
        return;
    }

    // ------------------------------------------------------------ Ficha en pantalla
    $chartMedio = [['Medio', 'Contactos']];
    foreach ($porMedio as $m => $v) { $chartMedio[] = [$m, $v['total']]; }
    $js = JSON_UNESCAPED_UNICODE;
    gf_estilos();
    ?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<div class="gf-wrap">
    <div class="gf-hero">
        <h1><?= gf_h($cli['nombre']); ?></h1>
        <p>Ficha 360 &middot; <?= gf_h($etiquetaPeriodo); ?>
            <?php if (!empty($cli['personal'])) { ?> &middot; <span class="gf-tag morado">Personal / familiar</span><?php } ?>
            <?php if ($cli['tipo_nombre'] !== '' && $cli['tipo_nombre'] !== null) { ?> &middot; <?= gf_h($cli['tipo_nombre']); ?><?php } ?></p>
    </div>

    <form method="get" action="index.php" class="gf-card gf-noprint">
        <input type="hidden" name="doc" value="graphs_cartera" /><input type="hidden" name="cliente" value="<?= $idFicha; ?>" />
        <div class="gf-filtros">
            <div class="gf-field"><label>Año</label>
                <select name="anio" id="gf-anio"><option value="" <?= $anioSel === '' ? 'selected' : ''; ?>>Personalizado</option>
                    <option value="todos" <?= $anioSel === 'todos' ? 'selected' : ''; ?>>Todos los años</option>
                    <?php foreach (gf_anios() as $y) { ?><option value="<?= $y; ?>" <?= $anioSel === $y ? 'selected' : ''; ?>><?= $y; ?></option><?php } ?>
                </select></div>
            <div class="gf-field"><label>Fecha inicial</label><input type="date" name="fechaInicial" id="gf-fi" value="<?= gf_h($desde); ?>"></div>
            <div class="gf-field"><label>Fecha final</label><input type="date" name="fechaFinal" id="gf-ff" value="<?= gf_h($hasta); ?>"></div>
        </div>
        <div class="gf-acciones">
            <button type="submit" class="gf-btn gf-btn-primary"><i class="bi bi-funnel"></i> Aplicar</button>
            <a class="gf-btn gf-btn-ghost" href="index.php?<?= gf_h(http_build_query(array_merge(['doc' => 'graphs_cartera', 'ambito' => $ambito], $qPeriodo))); ?>"><i class="bi bi-arrow-left"></i> Volver a la cartera</a>
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

    <div class="gf-kpis">
        <div class="gf-kpi verde"><div class="t">Cobrado en el período</div><div class="v"><?= gf_money($sumCobP); ?></div><div class="s">Histórico: <?= gf_money($sumCobT); ?></div></div>
        <div class="gf-kpi naranja"><div class="t">Gastos directos</div><div class="v"><?= gf_money($sumGasP); ?></div><div class="s">Histórico: <?= gf_money($sumGasT); ?></div></div>
        <div class="gf-kpi morado"><div class="t">Auditoría</div><div class="v"><?= gf_money($ficha['aud_periodo']); ?></div><div class="s">Histórico: <?= gf_money($ficha['aud_total']); ?></div></div>
        <div class="gf-kpi azul"><div class="t">Ganancia del período</div><div class="v <?= gf_clase_valor($gananciaP); ?>"><?= gf_money($gananciaP); ?></div><div class="s">Histórico: <?= gf_money($gananciaT); ?></div></div>
        <div class="gf-kpi rojo"><div class="t">Pendiente por cobrar</div><div class="v"><?= gf_money($pend); ?></div><div class="s">de <?= count($cots); ?> cotizaciones</div></div>
        <div class="gf-kpi <?= $sem === 'verde' ? 'verde' : ($sem === 'amarillo' ? 'naranja' : 'rojo'); ?>"><div class="t">Último contacto</div>
            <div class="v" style="font-size:18px"><?= $ultimo ? gf_fecha($ultimo['fecha']) : 'Sin registro'; ?></div>
            <div class="s"><?= $dias === null ? 'Nunca se ha registrado una comunicación' : ($dias . ' días · ' . gf_h($ultimo['medio']) . ' · ' . gf_h($ultimo['usuario'])); ?></div></div>
        <div class="gf-kpi gris"><div class="t">Contactos en el período</div><div class="v"><?= $nPeriodo; ?></div><div class="s">Total histórico: <?= count($seg); ?></div></div>
    </div>

    <div class="gf-grid2">
        <div class="gf-card">
            <h2>Datos del cliente</h2>
            <dl class="gf-kv">
                <dt>NIT</dt><dd><?= gf_h($cli['nit']); ?></dd>
                <dt>Representante</dt><dd><?= gf_h($cli['representante_legal']); ?></dd>
                <dt>Correo</dt><dd><?= gf_h($cli['email']); ?></dd>
                <dt>Teléfono / celular</dt><dd><?= gf_h(trim($cli['telefono'] . ' / ' . $cli['celular'], ' /')); ?></dd>
                <dt>Comercial asignado</dt><dd><?= gf_h($cli['comercial']); ?></dd>
                <dt>Total cotizado</dt><dd><?= gf_money($cotizado); ?></dd>
            </dl>
            <?php if ($ficha['contacto']) { $ct = $ficha['contacto']; ?>
                <h2 style="margin-top:16px">Contacto principal <small>de la cotización más reciente</small></h2>
                <dl class="gf-kv">
                    <dt>Nombre</dt><dd><?= gf_h($ct['nombre']); ?></dd>
                    <dt>Teléfono / celular</dt><dd><?= gf_h(trim($ct['telefono'] . ' / ' . $ct['celular'], ' /')); ?></dd>
                    <dt>Correo</dt><dd><?= gf_h($ct['email']); ?></dd>
                </dl>
            <?php } ?>
        </div>
        <div class="gf-card">
            <h2>Comunicación por medio</h2>
            <div class="gf-grid2">
                <div class="gf-tablewrap">
                    <table class="gf-table"><thead><tr><th>Medio</th><th class="n">Período</th><th class="n">Total</th><th>Último</th></tr></thead><tbody>
                    <?php foreach ($porMedio as $m => $v) { ?>
                        <tr><td><?= gf_h($m); ?></td><td class="n"><?= $v['periodo']; ?></td><td class="n"><?= $v['total']; ?></td><td><?= gf_fecha($v['ultimo']); ?></td></tr>
                    <?php } if (!$porMedio) { ?><tr><td colspan="4" style="color:#64748b">Sin comunicaciones registradas.</td></tr><?php } ?>
                    </tbody></table>
                </div>
                <div id="gf-ch-medio" class="gf-chart" style="min-height:220px"></div>
            </div>
        </div>
    </div>

    <div class="gf-card">
        <h2>Cotizaciones y proyectos <small><?= count($cots); ?></small></h2>
        <div class="gf-tablewrap"><table class="gf-table">
            <thead><tr><th class="sortable">#</th><th>Proyecto</th><th class="sortable">Estado</th><th class="sortable">Fecha</th><th class="sortable n">A cobrar</th><th class="sortable n">Cobrado total</th><th class="sortable n">Gasto directo total</th><th class="sortable n">Ganancia</th><th class="sortable n">Pendiente</th><th>Alerta</th></tr></thead>
            <tbody>
            <?php foreach ($cots as $c) { ?>
                <tr><td data-v="<?= $c['id']; ?>"><a href="index.php?doc=cotizacion&opc=2&id=<?= $c['id']; ?>" target="_blank">#<?= $c['id']; ?></a></td>
                    <td title="<?= gf_h($c['descripcion']); ?>"><?= gf_h(gf_resumir($c['descripcion'], 90)); ?></td>
                    <td><span class="gf-tag azul"><?= gf_h($c['estado_nombre']); ?></span></td>
                    <td data-v="<?= gf_h($c['fecha']); ?>"><?= gf_fecha($c['fecha']); ?></td>
                    <td class="n" data-v="<?= round($c['a_cobrar']); ?>"><?= gf_money($c['a_cobrar']); ?></td>
                    <td class="n" data-v="<?= round($c['cobrado_t']); ?>"><?= gf_money($c['cobrado_t']); ?></td>
                    <td class="n" data-v="<?= round($c['gasto_t']); ?>"><?= gf_money($c['gasto_t']); ?></td>
                    <td class="n <?= gf_clase_valor($c['ganancia_t']); ?>" data-v="<?= round($c['ganancia_t']); ?>"><strong><?= gf_money($c['ganancia_t']); ?></strong></td>
                    <td class="n" data-v="<?= round($c['pendiente']); ?>"><?= $c['pendiente'] > 0 ? '<strong>' . gf_money($c['pendiente']) . '</strong>' : '—'; ?></td>
                    <td><?php if ($c['revisar'] !== '') { ?><span class="gf-tag rojo"><?= gf_h($c['revisar']); ?></span><?php } ?></td></tr>
            <?php } if (!$cots) { ?><tr><td colspan="10" style="color:#64748b;text-align:center;padding:20px">Este cliente no tiene cotizaciones.</td></tr><?php } ?>
            </tbody>
        </table></div>
    </div>

    <div class="gf-card">
        <h2>Historial de comunicación <small><?= count($seg); ?> registros &middot; del más reciente al más antiguo</small></h2>
        <div class="gf-timeline">
        <?php foreach (array_slice($seg, 0, 300) as $s) { ?>
            <div class="gf-tl-item">
                <div class="h"><strong><?= gf_h(gf_fecha_hora($s['fecha'])); ?></strong> &middot; <span class="gf-tag azul"><?= gf_h($s['medio']); ?></span>
                    &middot; por <strong><?= gf_h($s['usuario']); ?></strong>
                    <?php if ($s['idCotizacion'] > 0) { ?> &middot; cotización <a href="index.php?doc=cotizacion&opc=2&id=<?= $s['idCotizacion']; ?>" target="_blank">#<?= $s['idCotizacion']; ?></a><?php } ?>
                    <?php if ($s['contacto'] !== '') { ?> &middot; contacto: <?= gf_h($s['contacto']); ?><?php } ?></div>
                <div class="b"><?= gf_h($s['observacion']); ?></div>
            </div>
        <?php } if (!$seg) { ?><p class="gf-nota">Este cliente no tiene comunicaciones registradas.</p><?php } ?>
        <?php if (count($seg) > 300) { ?><p class="gf-nota">Se muestran los 300 más recientes; el Excel incluye todos.</p><?php } ?>
        </div>
    </div>

    <div class="gf-grid2">
        <div class="gf-card"><h2>Abonos recientes <small>últimos 100</small></h2>
            <div class="gf-tablewrap"><table class="gf-table"><thead><tr><th>Fecha</th><th>Cot.</th><th>Tipo</th><th class="n">Valor</th></tr></thead><tbody>
            <?php foreach ($ficha['abonos'] as $a) { ?><tr><td><?= gf_fecha($a['fecha']); ?></td><td>#<?= (int) $a['idCotizacion']; ?></td><td><?= gf_h($a['tipo']); ?></td><td class="n"><?= gf_money($a['valor']); ?></td></tr>
            <?php } if (!$ficha['abonos']) { ?><tr><td colspan="4" style="color:#64748b">Sin abonos.</td></tr><?php } ?></tbody></table></div></div>
        <div class="gf-card"><h2>Gastos directos recientes <small>últimos 100</small></h2>
            <div class="gf-tablewrap"><table class="gf-table"><thead><tr><th>Fecha</th><th>Cot.</th><th>Tipo</th><th>Detalle</th><th class="n">Valor</th></tr></thead><tbody>
            <?php foreach ($ficha['gastos'] as $g) { ?><tr><td><?= gf_fecha($g['fecha']); ?></td><td>#<?= (int) $g['idCotizacion']; ?></td><td><?= gf_h($g['tipo']); ?></td><td><?= gf_h(gf_resumir($g['observacion'], 60)); ?></td><td class="n"><?= gf_money($g['valor']); ?></td></tr>
            <?php } if (!$ficha['gastos']) { ?><tr><td colspan="5" style="color:#64748b">Sin gastos directos.</td></tr><?php } ?></tbody></table></div></div>
    </div>

    <?php if ($ficha['auditoria']) { ?>
    <div class="gf-card"><h2>Auditoría: pagos a personas <small>histórico</small></h2>
        <div class="gf-tablewrap"><table class="gf-table"><thead><tr><th>Persona</th><th>Tipo</th><th class="n">Registros</th><th class="n">Total</th><th>Último pago</th></tr></thead><tbody>
        <?php foreach ($ficha['auditoria'] as $a) { ?><tr><td><?= gf_h($a['persona']); ?></td><td><?= gf_h($a['tipo']); ?></td><td class="n"><?= (int) $a['n']; ?></td><td class="n"><?= gf_money($a['total']); ?></td><td><?= gf_fecha($a['ultimo']); ?></td></tr><?php } ?>
        </tbody></table></div></div>
    <?php } ?>
</div>

<script>
google.charts.load('current', {packages: ['corechart']});
google.charts.setOnLoadCallback(function () {
    var datos = <?= json_encode($chartMedio, $js); ?>;
    var el = document.getElementById('gf-ch-medio');
    if (datos.length > 1) {
        new google.visualization.PieChart(el).draw(google.visualization.arrayToDataTable(datos), {pieHole: 0.4, legend: {position: 'bottom'}, chartArea: {width: '90%'}});
    } else { el.innerHTML = '<p class="gf-nota" style="padding:20px;text-align:center">Sin datos.</p>'; }
});
</script>
<?php
    gf_script_tablas();
    return;
}

// =====================================================================
// CARTERA (LISTA DE CLIENTES)
// =====================================================================
$com = isset($_GET['com']) ? (string) $_GET['com'] : '';
if (!in_array($com, ['', 'verde', 'amarillo', 'rojo', 'alerta'], true)) { $com = ''; }
$soloPend = !empty($_GET['pend']);
$tipoSel = isset($_GET['tipo']) ? trim((string) $_GET['tipo']) : '';
$comSel = isset($_GET['comercial']) ? trim((string) $_GET['comercial']) : '';
$busqueda = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$orden = isset($_GET['orden']) ? (string) $_GET['orden'] : 'prioridad';
if (!in_array($orden, ['prioridad', 'pendiente', 'dias', 'ganancia', 'cobrado', 'nombre'], true)) { $orden = 'prioridad'; }

$todas = $svc->cartera($desde, $hasta, $ambito);
$tipos = GraphsFinanzasService::valoresUnicos($todas, 'tipo');
$comerciales = GraphsFinanzasService::valoresUnicos($todas, 'comercial');

$filas = [];
foreach ($todas as $f) {
    $alerta = $f['pendiente'] > 0 && $f['semaforo'] === 'rojo';
    $f['alerta'] = $alerta;
    if ($com === 'alerta' && !$alerta) { continue; }
    if (in_array($com, ['verde', 'amarillo', 'rojo'], true) && $f['semaforo'] !== $com) { continue; }
    if ($soloPend && $f['pendiente'] <= 0) { continue; }
    if ($tipoSel !== '' && $f['tipo'] !== $tipoSel) { continue; }
    if ($comSel !== '' && $f['comercial'] !== $comSel) { continue; }
    if ($busqueda !== '' && stripos($f['nombre'] . ' ' . $f['nit'] . ' ' . $f['representante'], $busqueda) === false) { continue; }
    $filas[] = $f;
}
usort($filas, function ($a, $b) use ($orden) {
    switch ($orden) {
        case 'pendiente': return $b['pendiente'] <=> $a['pendiente'];
        case 'dias': return ($b['dias'] === null ? 99999 : $b['dias']) <=> ($a['dias'] === null ? 99999 : $a['dias']);
        case 'ganancia': return $b['ganancia_p'] <=> $a['ganancia_p'];
        case 'cobrado': return $b['cobrado_p'] <=> $a['cobrado_p'];
        case 'nombre': return strcasecmp($a['nombre'], $b['nombre']);
    }
    $pa = ($a['alerta'] ? 2 : ($a['pendiente'] > 0 ? 1 : 0));
    $pb = ($b['alerta'] ? 2 : ($b['pendiente'] > 0 ? 1 : 0));
    if ($pa !== $pb) { return $pb <=> $pa; }
    if ($a['pendiente'] != $b['pendiente']) { return $b['pendiente'] <=> $a['pendiente']; }
    return ($b['dias'] === null ? 99999 : $b['dias']) <=> ($a['dias'] === null ? 99999 : $a['dias']);
});

// KPIs
$k = ['clientes' => count($filas), 'pend_n' => 0, 'pend_v' => 0.0, 'alertas' => 0, 'alerta_v' => 0.0, 'verde' => 0, 'amarillo' => 0, 'rojo' => 0,
      'contactos' => 0, 'cobrado' => 0.0, 'ganancia' => 0.0];
$medios = [];
$idsMostrados = [];
foreach ($filas as $f) {
    $idsMostrados[$f['id']] = true;
    if ($f['pendiente'] > 0) { $k['pend_n']++; $k['pend_v'] += $f['pendiente']; }
    if ($f['alerta']) { $k['alertas']++; $k['alerta_v'] += $f['pendiente']; }
    $k[$f['semaforo']]++;
    $k['contactos'] += $f['n_contactos_p'];
    $k['cobrado'] += $f['cobrado_p'];
    $k['ganancia'] += $f['ganancia_p'];
    foreach ($f['medios_p'] as $m => $n) { $medios[$m] = (isset($medios[$m]) ? $medios[$m] : 0) + $n; }
}
arsort($medios);

// Contactos por mes y medio (para la grafica)
$porMes = [];
$mediosMes = [];
foreach ($svc->seguimientos() as $s) {
    if (!isset($idsMostrados[$s['idCliente']])) { continue; }
    $dia = substr($s['fecha'], 0, 10);
    if ($dia < $desde || $dia > $hasta) { continue; }
    $mes = substr($dia, 0, 7);
    $porMes[$mes][$s['medio']] = (isset($porMes[$mes][$s['medio']]) ? $porMes[$mes][$s['medio']] : 0) + 1;
    $mediosMes[$s['medio']] = true;
}
ksort($porMes);
$mediosMes = array_keys($mediosMes);
sort($mediosMes);

$paramsExport = array_merge(['doc' => 'graphs_cartera', 'ambito' => $ambito, 'orden' => $orden], $qPeriodo);
foreach (['com' => $com, 'tipo' => $tipoSel, 'comercial' => $comSel, 'q' => $busqueda] as $kk => $vv) { if ($vv !== '') { $paramsExport[$kk] = $vv; } }
if ($soloPend) { $paramsExport['pend'] = 1; }

// ------------------------------------------------------------ Excel de la cartera
if ($esExcel) {
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<h3>Cartera y seguimiento de clientes &mdash; ' . gf_h($etiquetaPeriodo) . ' &mdash; ' . gf_h(gf_nombre_ambito($ambito)) . '</h3>';
    echo '<table border="1"><tr>';
    foreach (['Cliente', 'NIT', 'Tipo', 'Comercial', 'Correo', 'Telefono', 'Cotizaciones', 'Cotizado', 'Cobrado periodo', 'Gasto directo periodo', 'Auditoria periodo', 'Ganancia periodo', 'Margen %', 'Cobrado total', 'Pendiente por cobrar',
              'Ultimo contacto', 'Medio', 'Quien (VidEx)', 'Contacto en cliente', 'Detalle del ultimo contacto', 'Dias sin contacto', 'Semaforo', 'Contactos periodo', 'Contactos total', 'Alerta'] as $h) { echo '<th>' . $h . '</th>'; }
    echo '</tr>';
    foreach ($filas as $f) {
        $u = $f['ultimo'];
        echo '<tr><td>' . gf_h($f['nombre']) . '</td><td>' . gf_h($f['nit']) . '</td><td>' . gf_h($f['tipo']) . '</td><td>' . gf_h($f['comercial']) . '</td><td>' . gf_h($f['email']) . '</td><td>' . gf_h(trim($f['telefono'] . ' / ' . $f['celular'], ' /')) . '</td>'
            . '<td>' . $f['n_cot'] . '</td><td>' . round($f['cotizado']) . '</td><td>' . round($f['cobrado_p']) . '</td><td>' . round($f['gasto_p']) . '</td><td>' . round($f['aud_p']) . '</td><td>' . round($f['ganancia_p']) . '</td>'
            . '<td>' . ($f['margen_p'] === null ? '' : round($f['margen_p'], 1)) . '</td><td>' . round($f['cobrado_t']) . '</td><td>' . round($f['pendiente']) . '</td>'
            . '<td>' . ($u ? gf_h(gf_fecha_hora($u['fecha'])) : 'Sin registro') . '</td><td>' . ($u ? gf_h($u['medio']) : '') . '</td><td>' . ($u ? gf_h($u['usuario']) : '') . '</td><td>' . ($u ? gf_h($u['contacto']) : '') . '</td><td>' . ($u ? gf_h($u['observacion']) : '') . '</td>'
            . '<td>' . ($f['dias'] === null ? '' : $f['dias']) . '</td><td>' . $f['semaforo'] . '</td><td>' . $f['n_contactos_p'] . '</td><td>' . $f['n_contactos_t'] . '</td><td>' . ($f['alerta'] ? 'Pendiente de cobro y mas de 90 dias sin contacto' : '') . '</td></tr>';
    }
    echo '</table>';
    return;
}

$chartSem = [['Semáforo', 'Clientes'], ['Al día (≤30 días)', $k['verde']], ['Atención (31–90)', $k['amarillo']], ['Abandonado (>90 o sin registro)', $k['rojo']]];
$chartMedios = [['Medio', 'Contactos']];
foreach ($medios as $m => $n) { $chartMedios[] = [$m, $n]; }
$chartMes = [array_merge(['Mes'], $mediosMes)];
foreach ($porMes as $mes => $v) {
    $fila = [$mes];
    foreach ($mediosMes as $m) { $fila[] = isset($v[$m]) ? $v[$m] : 0; }
    $chartMes[] = $fila;
}
$chartDeuda = [['Cliente', 'Pendiente']];
$deudores = array_filter($filas, function ($f) { return $f['pendiente'] > 0; });
usort($deudores, function ($a, $b) { return $b['pendiente'] <=> $a['pendiente']; });
foreach (array_slice($deudores, 0, 10) as $f) { $chartDeuda[] = [gf_resumir($f['nombre'], 26), round($f['pendiente'])]; }
$js = JSON_UNESCAPED_UNICODE;
gf_estilos();
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<div class="gf-wrap">
    <div class="gf-hero">
        <h1>Cartera y seguimiento de clientes</h1>
        <p>Dinero y última comunicación de cada cliente &mdash; <?= gf_h($etiquetaPeriodo); ?> &middot; <?= gf_h(gf_nombre_ambito($ambito)); ?></p>
    </div>

    <?php if ($svc->getLastError() !== '') { ?>
        <div class="gf-error"><strong>No se pudo cargar parte de la información.</strong>
            <?php if ((int) ($_SESSION['superusuario'] ?? 0) === 1) { echo gf_h($svc->getLastError()); } ?></div>
    <?php } ?>
    <?php if ($svc->esRestringido()) { ?><div class="gf-aviso">Su usuario tiene clientes asignados: solo se muestran esos clientes.</div><?php } ?>

    <form method="get" action="index.php" class="gf-card gf-noprint">
        <input type="hidden" name="doc" value="graphs_cartera" />
        <div class="gf-filtros">
            <div class="gf-field"><label>Año</label>
                <select name="anio" id="gf-anio"><option value="" <?= $anioSel === '' ? 'selected' : ''; ?>>Personalizado</option>
                    <option value="todos" <?= $anioSel === 'todos' ? 'selected' : ''; ?>>Todos los años</option>
                    <?php foreach (gf_anios() as $y) { ?><option value="<?= $y; ?>" <?= $anioSel === $y ? 'selected' : ''; ?>><?= $y; ?></option><?php } ?>
                </select></div>
            <div class="gf-field"><label>Fecha inicial</label><input type="date" name="fechaInicial" id="gf-fi" value="<?= gf_h($desde); ?>"></div>
            <div class="gf-field"><label>Fecha final</label><input type="date" name="fechaFinal" id="gf-ff" value="<?= gf_h($hasta); ?>"></div>
            <div class="gf-field"><label>Ámbito</label>
                <select name="ambito"><?php foreach (['negocio', 'personal', 'todo'] as $a) { ?><option value="<?= $a; ?>" <?= $ambito === $a ? 'selected' : ''; ?>><?= gf_h(gf_nombre_ambito($a)); ?></option><?php } ?></select></div>
            <div class="gf-field"><label>Comunicación</label>
                <select name="com">
                    <option value="" <?= $com === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="alerta" <?= $com === 'alerta' ? 'selected' : ''; ?>>⚠ Deben y sin contacto &gt;90 días</option>
                    <option value="verde" <?= $com === 'verde' ? 'selected' : ''; ?>>Al día (≤30 días)</option>
                    <option value="amarillo" <?= $com === 'amarillo' ? 'selected' : ''; ?>>Atención (31–90 días)</option>
                    <option value="rojo" <?= $com === 'rojo' ? 'selected' : ''; ?>>Abandonados (&gt;90 o sin registro)</option>
                </select></div>
            <div class="gf-field"><label>Tipo de cliente</label>
                <select name="tipo"><option value="">Todos</option><?php foreach ($tipos as $t) { ?><option <?= $tipoSel === $t ? 'selected' : ''; ?>><?= gf_h($t); ?></option><?php } ?></select></div>
            <div class="gf-field"><label>Comercial</label>
                <select name="comercial"><option value="">Todos</option><?php foreach ($comerciales as $t) { ?><option <?= $comSel === $t ? 'selected' : ''; ?>><?= gf_h($t); ?></option><?php } ?></select></div>
            <div class="gf-field"><label>Ordenar por</label>
                <select name="orden">
                    <?php foreach (['prioridad' => 'Prioridad (deuda + abandono)', 'pendiente' => 'Mayor pendiente', 'dias' => 'Más días sin contacto', 'ganancia' => 'Mayor ganancia', 'cobrado' => 'Mayor cobrado', 'nombre' => 'Nombre'] as $v => $t) { ?>
                        <option value="<?= $v; ?>" <?= $orden === $v ? 'selected' : ''; ?>><?= $t; ?></option><?php } ?>
                </select></div>
            <div class="gf-field"><label>Buscar</label><input type="text" name="q" value="<?= gf_h($busqueda); ?>" placeholder="Nombre, NIT o representante"></div>
            <div class="gf-field"><label>&nbsp;</label><label style="text-transform:none;font-size:13px;font-weight:600"><input type="checkbox" name="pend" value="1" <?= $soloPend ? 'checked' : ''; ?> style="width:auto"> Solo con pendiente</label></div>
        </div>
        <div class="gf-acciones">
            <button type="submit" class="gf-btn gf-btn-primary"><i class="bi bi-funnel"></i> Aplicar</button>
            <a class="gf-btn gf-btn-ghost" href="index.php?doc=graphs_cartera">Limpiar</a>
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

    <div class="gf-kpis">
        <div class="gf-kpi azul"><div class="t">Clientes</div><div class="v"><?= $k['clientes']; ?></div><div class="s">con cotizaciones, auditoría o comunicación</div></div>
        <div class="gf-kpi verde"><div class="t">Cobrado en el período</div><div class="v"><?= gf_money($k['cobrado']); ?></div><div class="s">Ganancia: <span class="<?= gf_clase_valor($k['ganancia']); ?>"><?= gf_money($k['ganancia']); ?></span></div></div>
        <div class="gf-kpi rojo"><div class="t">Pendiente por cobrar</div><div class="v"><?= gf_money($k['pend_v']); ?></div><div class="s"><?= $k['pend_n']; ?> clientes con saldo</div></div>
        <div class="gf-kpi rojo"><div class="t">⚠ Deben y sin contacto &gt;90 días</div><div class="v"><?= $k['alertas']; ?></div><div class="s"><?= gf_money($k['alerta_v']); ?> en riesgo</div></div>
        <div class="gf-kpi verde"><div class="t">Al día (≤30 días)</div><div class="v"><?= $k['verde']; ?></div><div class="s">Atención (31–90): <?= $k['amarillo']; ?></div></div>
        <div class="gf-kpi naranja"><div class="t">Abandonados</div><div class="v"><?= $k['rojo']; ?></div><div class="s">&gt;90 días o sin registro</div></div>
        <div class="gf-kpi gris"><div class="t">Contactos en el período</div><div class="v"><?= $k['contactos']; ?></div>
            <div class="s"><?php $i = 0; foreach ($medios as $m => $n) { if ($i++ >= 3) { break; } echo gf_h($m) . ': ' . $n . ' &middot; '; } ?></div></div>
    </div>

    <div class="gf-grid3">
        <div class="gf-card"><h2>Semáforo de comunicación</h2><div id="gf-ch-sem" class="gf-chart" style="min-height:260px"></div></div>
        <div class="gf-card"><h2>Contactos por medio</h2><div id="gf-ch-medios" class="gf-chart" style="min-height:260px"></div></div>
        <div class="gf-card"><h2>Mayor cartera pendiente <small>top 10</small></h2><div id="gf-ch-deuda" class="gf-chart" style="min-height:260px"></div></div>
    </div>
    <div class="gf-card"><h2>Contactos por mes y medio</h2><div id="gf-ch-mes" class="gf-chart" style="min-height:300px"></div></div>

    <div class="gf-card">
        <h2>Clientes <small><?= count($filas); ?> &middot; clic en el encabezado para ordenar &middot; clic en el nombre para abrir la ficha 360</small></h2>
        <div class="gf-tablewrap">
        <table class="gf-table">
            <thead><tr>
                <th class="sortable">Cliente</th><th class="sortable">Tipo</th><th class="sortable">Comercial</th><th class="sortable n">Cot.</th>
                <th class="sortable n">Cobrado período</th><th class="sortable n">Ganancia período</th><th class="sortable n">Pendiente</th>
                <th class="sortable">Último contacto</th><th class="sortable">Medio</th><th class="sortable">Quién</th><th>Qué se habló</th>
                <th class="sortable n">Días</th><th class="sortable n">Contactos período</th>
            </tr></thead>
            <tbody>
            <?php foreach ($filas as $f) { $u = $f['ultimo']; ?>
                <tr>
                    <td><a href="index.php?<?= gf_h(http_build_query(array_merge(['doc' => 'graphs_cartera', 'cliente' => $f['id'], 'ambito' => $ambito], $qPeriodo))); ?>"><strong><?= gf_h($f['nombre']); ?></strong></a>
                        <?php if ($f['personal']) { ?><span class="gf-tag morado">Personal</span><?php } ?>
                        <?php if (!$f['existe']) { ?><span class="gf-tag rojo">Eliminado</span><?php } ?>
                        <?php if ($f['alerta']) { ?><span class="gf-tag rojo" title="Tiene saldo pendiente y más de 90 días sin contacto">⚠ Cobrar</span><?php } ?></td>
                    <td><?= gf_h($f['tipo']); ?></td><td><?= gf_h($f['comercial']); ?></td>
                    <td class="n"><?= $f['n_cot']; ?></td>
                    <td class="n" data-v="<?= round($f['cobrado_p']); ?>"><?= gf_money($f['cobrado_p']); ?></td>
                    <td class="n <?= gf_clase_valor($f['ganancia_p']); ?>" data-v="<?= round($f['ganancia_p']); ?>"><?= gf_money($f['ganancia_p']); ?></td>
                    <td class="n" data-v="<?= round($f['pendiente']); ?>"><?= $f['pendiente'] > 0 ? '<strong>' . gf_money($f['pendiente']) . '</strong>' : '—'; ?></td>
                    <td data-v="<?= $u ? gf_h($u['fecha']) : ''; ?>"><?= $u ? gf_fecha($u['fecha']) : '<em>Sin registro</em>'; ?></td>
                    <td><?= $u ? '<span class="gf-tag azul">' . gf_h($u['medio']) . '</span>' : ''; ?></td>
                    <td><?= $u ? gf_h($u['usuario']) : ''; ?></td>
                    <td title="<?= $u ? gf_h($u['observacion']) : ''; ?>"><?= $u ? gf_h(gf_resumir($u['observacion'], 90)) : ''; ?></td>
                    <td class="n" data-v="<?= $f['dias'] === null ? 99999 : $f['dias']; ?>"><span class="gf-dot <?= $f['semaforo']; ?>"></span><?= $f['dias'] === null ? '—' : $f['dias']; ?></td>
                    <td class="n"><?= $f['n_contactos_p']; ?></td>
                </tr>
            <?php } ?>
            <?php if (!$filas) { ?><tr><td colspan="13" style="text-align:center;color:#64748b;padding:24px">No hay clientes para estos filtros.</td></tr><?php } ?>
            </tbody>
        </table>
        </div>
        <p class="gf-nota" style="margin-top:8px">«Último contacto» sale de los seguimientos registrados (<code>seguimiento</code>): fecha, medio, usuario de VidEx y lo anotado. Semáforo: verde ≤30 días, amarillo 31–90, rojo &gt;90 o sin registro. Solo cuenta lo que el equipo anotó: una llamada o un WhatsApp sin registrar no aparece. «Pendiente» = A cobrar − abonos en cotizaciones aprobadas con saldo.</p>
    </div>
</div>

<script>
google.charts.load('current', {packages: ['corechart']});
google.charts.setOnLoadCallback(gfDibujar);
var gfSem = <?= json_encode($chartSem, $js); ?>;
var gfMedios = <?= json_encode($chartMedios, $js); ?>;
var gfDeuda = <?= json_encode($chartDeuda, $js); ?>;
var gfMes = <?= json_encode($chartMes, $js); ?>;
function gfDibujar() {
    function vacio(id) { document.getElementById(id).innerHTML = '<p class="gf-nota" style="padding:30px;text-align:center">Sin datos para graficar.</p>'; }
    var fmt = new google.visualization.NumberFormat({prefix: '$', groupingSymbol: '.', decimalSymbol: ',', fractionDigits: 0});
    if (gfSem[1][1] + gfSem[2][1] + gfSem[3][1] > 0) {
        new google.visualization.PieChart(document.getElementById('gf-ch-sem')).draw(google.visualization.arrayToDataTable(gfSem),
            {pieHole: 0.45, colors: ['#16a34a', '#eab308', '#dc2626'], legend: {position: 'bottom'}, chartArea: {width: '90%'}});
    } else { vacio('gf-ch-sem'); }
    if (gfMedios.length > 1) {
        new google.visualization.PieChart(document.getElementById('gf-ch-medios')).draw(google.visualization.arrayToDataTable(gfMedios),
            {legend: {position: 'bottom'}, chartArea: {width: '90%'}});
    } else { vacio('gf-ch-medios'); }
    if (gfDeuda.length > 1) {
        var d = google.visualization.arrayToDataTable(gfDeuda); fmt.format(d, 1);
        new google.visualization.BarChart(document.getElementById('gf-ch-deuda')).draw(d,
            {colors: ['#dc2626'], legend: {position: 'none'}, hAxis: {format: 'short'}, chartArea: {left: 130, width: '60%'}});
    } else { vacio('gf-ch-deuda'); }
    if (gfMes.length > 1 && gfMes[0].length > 1) {
        new google.visualization.ColumnChart(document.getElementById('gf-ch-mes')).draw(google.visualization.arrayToDataTable(gfMes),
            {isStacked: true, legend: {position: 'bottom'}, chartArea: {width: '88%'}});
    } else { vacio('gf-ch-mes'); }
}
window.addEventListener('resize', function () { if (window.google && google.visualization) { gfDibujar(); } });
window.addEventListener('beforeprint', function () { if (window.google && google.visualization) { gfDibujar(); } });
</script>
<?php gf_script_tablas(); ?>
