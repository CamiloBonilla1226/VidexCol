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
    $coloresMedio = gf_colores_medio(array_keys($porMedio));
    $datosMedio = [];
    foreach ($porMedio as $m => $v) {
        $datosMedio[] = ['l' => $m, 'v' => $v['total'], 'c' => $coloresMedio[$m], 'txt' => $v['periodo'] . ' en el período · ' . $v['total'] . ' en total'];
    }
    $datoMedio = $porMedio ? 'Último contacto: ' . gf_fecha($ultimo['fecha']) . ' por ' . $ultimo['medio'] . ' (' . $ultimo['usuario'] . ').' : '';
    $js = JSON_UNESCAPED_UNICODE;
    gf_estilos();
    ?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<div class="gf-wrap">
    <div class="gf-hero">
        <h1><?= gf_h($cli['nombre']); ?></h1>
        <p>Ficha del cliente &middot; <?= gf_h($etiquetaPeriodo); ?>
            <?php if (!empty($cli['personal'])) { ?> &middot; <span class="gf-tag morado">Personal</span><?php } ?>
            <?php if ($cli['tipo_nombre'] !== '' && $cli['tipo_nombre'] !== null) { ?> &middot; <?= gf_h($cli['tipo_nombre']); ?><?php } ?></p>
    </div>

    <form method="get" action="index.php" class="gf-card gf-noprint">
        <input type="hidden" name="doc" value="graphs_cartera" /><input type="hidden" name="cliente" value="<?= $idFicha; ?>" />
        <input type="hidden" name="ambito" value="<?= gf_h($ambito); ?>" />
        <details class="gf-filtros-wrap" open>
            <summary>Período</summary>
            <div class="gf-filtros">
                <div class="gf-field"><label>Año</label>
                    <select name="anio" id="gf-anio"><option value="" <?= $anioSel === '' ? 'selected' : ''; ?>>Personalizado</option>
                        <option value="todos" <?= $anioSel === 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <?php foreach (gf_anios() as $y) { ?><option value="<?= $y; ?>" <?= $anioSel === $y ? 'selected' : ''; ?>><?= $y; ?></option><?php } ?>
                    </select></div>
                <div class="gf-field"><label>Desde</label><input type="date" name="fechaInicial" id="gf-fi" value="<?= gf_h($desde); ?>"></div>
                <div class="gf-field"><label>Hasta</label><input type="date" name="fechaFinal" id="gf-ff" value="<?= gf_h($hasta); ?>"></div>
            </div>
            <div class="gf-acciones">
                <button type="submit" class="gf-btn gf-btn-primary">Aplicar</button>
                <a class="gf-btn gf-btn-ghost" href="index.php?<?= gf_h(http_build_query(array_merge(['doc' => 'graphs_cartera', 'ambito' => $ambito], $qPeriodo))); ?>">← Cartera</a>
            </div>
        </details>
        <?php gf_barra_exportar($paramsExport); ?>
    </form>

    <div class="gf-kpis">
        <div class="gf-kpi azul"><div class="t">Cobrado</div><div class="v"><?= gf_money($sumCobP); ?></div><div class="s">histórico <?= gf_money_corto($sumCobT); ?></div></div>
        <div class="gf-kpi naranja"><div class="t">Gastos directos</div><div class="v"><?= gf_money($sumGasP); ?></div><div class="s">histórico <?= gf_money_corto($sumGasT); ?></div></div>
        <div class="gf-kpi morado"><div class="t">Auditoría</div><div class="v"><?= gf_money($ficha['aud_periodo']); ?></div><div class="s">histórico <?= gf_money_corto($ficha['aud_total']); ?></div></div>
        <div class="gf-kpi verde"><div class="t">Ganancia</div><div class="v <?= gf_clase_valor($gananciaP); ?>"><?= gf_money($gananciaP); ?></div><div class="s">histórico <?= gf_money_corto($gananciaT); ?></div></div>
        <div class="gf-kpi ambar"><div class="t">Por cobrar</div><div class="v"><?= gf_money($pend); ?></div><div class="s"><?= count($cots); ?> cotizaciones</div></div>
        <div class="gf-kpi gris"><div class="t">Último contacto</div>
            <div class="v" style="font-size:18px"><?= $ultimo ? gf_fecha($ultimo['fecha']) : 'Sin registro'; ?></div>
            <div class="s"><?= $dias === null ? 'nunca registrado' : ('hace ' . $dias . ' días · ' . gf_h($ultimo['medio'])); ?></div></div>
        <div class="gf-kpi gris"><div class="t">Contactos</div><div class="v"><?= $nPeriodo; ?></div><div class="s">en el período · <?= count($seg); ?> en total</div></div>
    </div>

    <div class="gf-grid2">
        <section class="gf-card">
            <h2>Datos del cliente</h2>
            <dl class="gf-kv">
                <dt>NIT</dt><dd><?= gf_h($cli['nit']); ?></dd>
                <dt>Representante</dt><dd><?= gf_h($cli['representante_legal']); ?></dd>
                <dt>Correo</dt><dd><?= gf_h($cli['email']); ?></dd>
                <dt>Teléfono</dt><dd><?= gf_h(trim($cli['telefono'] . ' / ' . $cli['celular'], ' /')); ?></dd>
                <dt>Comercial</dt><dd><?= gf_h($cli['comercial']); ?></dd>
                <dt>Total cotizado</dt><dd><?= gf_money($cotizado); ?></dd>
                <?php if ($ficha['contacto']) { $ct = $ficha['contacto']; ?>
                    <dt>Contacto</dt><dd><?= gf_h($ct['nombre']); ?><?php $tel = trim($ct['telefono'] . ' / ' . $ct['celular'], ' /'); if ($tel !== '') { echo ' · ' . gf_h($tel); } ?><?php if ($ct['email'] !== '') { echo '<br>' . gf_h($ct['email']); } ?></dd>
                <?php } ?>
            </dl>
        </section>
        <?php gf_grafica('gf-ch-medio', 'Comunicación por medio', [
            'que' => 'Por dónde se ha hablado con este cliente.',
            'leer' => 'Cada porción es un medio. La leyenda indica cuántos contactos hubo en el período y en total.',
            'ojo' => 'Solo cuenta lo que el equipo registró como seguimiento.',
            'dato' => $datoMedio,
        ], 220); ?>
    </div>

    <section class="gf-card">
        <h2>Cotizaciones <small><?= count($cots); ?></small></h2>
        <div class="gf-tablewrap"><table class="gf-table">
            <thead><tr><th class="sortable">#</th><th>Proyecto</th><th class="sortable">Estado</th><th class="sortable">Fecha</th><th class="sortable n">A cobrar</th><th class="sortable n">Cobrado</th><th class="sortable n">Gasto</th><th class="sortable n">Ganancia</th><th class="sortable n">Pendiente</th><th>Alerta</th></tr></thead>
            <tbody>
            <?php foreach ($cots as $c) { ?>
                <tr><td data-v="<?= $c['id']; ?>"><a href="index.php?doc=cotizacion&opc=2&id=<?= $c['id']; ?>" target="_blank">#<?= $c['id']; ?></a></td>
                    <td title="<?= gf_h($c['descripcion']); ?>"><?= gf_h(gf_resumir($c['descripcion'], 80)); ?></td>
                    <td><span class="gf-tag azul"><?= gf_h($c['estado_nombre']); ?></span></td>
                    <td data-v="<?= gf_h($c['fecha']); ?>"><?= gf_fecha($c['fecha']); ?></td>
                    <td class="n" data-v="<?= round($c['a_cobrar']); ?>"><?= gf_money($c['a_cobrar']); ?></td>
                    <td class="n" data-v="<?= round($c['cobrado_t']); ?>"><?= gf_money($c['cobrado_t']); ?></td>
                    <td class="n" data-v="<?= round($c['gasto_t']); ?>"><?= gf_money($c['gasto_t']); ?></td>
                    <td class="n <?= gf_clase_valor($c['ganancia_t']); ?>" data-v="<?= round($c['ganancia_t']); ?>"><strong><?= gf_money($c['ganancia_t']); ?></strong></td>
                    <td class="n" data-v="<?= round($c['pendiente']); ?>"><?= $c['pendiente'] > 0 ? '<strong>' . gf_money($c['pendiente']) . '</strong>' : '—'; ?></td>
                    <td><?php if ($c['revisar'] !== '') { ?><span class="gf-tag rojo"><?= gf_h($c['revisar']); ?></span><?php } ?></td></tr>
            <?php } if (!$cots) { ?><tr><td colspan="10" class="gf-vacio">Este cliente no tiene cotizaciones.</td></tr><?php } ?>
            </tbody>
        </table></div>
    </section>

    <section class="gf-card">
        <h2>Historial de comunicación <small><?= count($seg); ?></small></h2>
        <div class="gf-timeline">
        <?php foreach (array_slice($seg, 0, 300) as $s) { ?>
            <div class="gf-tl-item">
                <div class="h"><strong><?= gf_h(gf_fecha_hora($s['fecha'])); ?></strong> &middot; <?= gf_h($s['medio']); ?> &middot; <?= gf_h($s['usuario']); ?>
                    <?php if ($s['idCotizacion'] > 0) { ?> &middot; <a href="index.php?doc=cotizacion&opc=2&id=<?= $s['idCotizacion']; ?>" target="_blank">#<?= $s['idCotizacion']; ?></a><?php } ?>
                    <?php if ($s['contacto'] !== '') { ?> &middot; <?= gf_h($s['contacto']); ?><?php } ?></div>
                <div class="b"><?= gf_h($s['observacion']); ?></div>
            </div>
        <?php } if (!$seg) { ?><p class="gf-nota">Sin comunicaciones registradas.</p><?php } ?>
        <?php if (count($seg) > 300) { ?><p class="gf-nota">Se muestran las 300 más recientes; el Excel incluye todas.</p><?php } ?>
        </div>
    </section>

    <div class="gf-grid2">
        <section class="gf-card"><h2>Abonos recientes</h2>
            <div class="gf-tablewrap"><table class="gf-table"><thead><tr><th>Fecha</th><th>Cot.</th><th>Tipo</th><th class="n">Valor</th></tr></thead><tbody>
            <?php foreach ($ficha['abonos'] as $a) { ?><tr><td><?= gf_fecha($a['fecha']); ?></td><td>#<?= (int) $a['idCotizacion']; ?></td><td><?= gf_h($a['tipo']); ?></td><td class="n"><?= gf_money($a['valor']); ?></td></tr>
            <?php } if (!$ficha['abonos']) { ?><tr><td colspan="4" class="gf-vacio">Sin abonos.</td></tr><?php } ?></tbody></table></div></section>
        <section class="gf-card"><h2>Gastos directos recientes</h2>
            <div class="gf-tablewrap"><table class="gf-table"><thead><tr><th>Fecha</th><th>Cot.</th><th>Tipo</th><th>Detalle</th><th class="n">Valor</th></tr></thead><tbody>
            <?php foreach ($ficha['gastos'] as $g) { ?><tr><td><?= gf_fecha($g['fecha']); ?></td><td>#<?= (int) $g['idCotizacion']; ?></td><td><?= gf_h($g['tipo']); ?></td><td><?= gf_h(gf_resumir($g['observacion'], 50)); ?></td><td class="n"><?= gf_money($g['valor']); ?></td></tr>
            <?php } if (!$ficha['gastos']) { ?><tr><td colspan="5" class="gf-vacio">Sin gastos directos.</td></tr><?php } ?></tbody></table></div></section>
    </div>

    <?php if ($ficha['auditoria']) { ?>
    <section class="gf-card"><h2>Auditoría: pagos a personas</h2>
        <div class="gf-tablewrap"><table class="gf-table"><thead><tr><th>Persona</th><th>Tipo</th><th class="n">Registros</th><th class="n">Total</th><th>Último pago</th></tr></thead><tbody>
        <?php foreach ($ficha['auditoria'] as $a) { ?><tr><td><?= gf_h($a['persona']); ?></td><td><?= gf_h($a['tipo']); ?></td><td class="n"><?= (int) $a['n']; ?></td><td class="n"><?= gf_money($a['total']); ?></td><td><?= gf_fecha($a['ultimo']); ?></td></tr><?php } ?>
        </tbody></table></div></section>
    <?php } ?>
</div>

<?php gf_script_base(); ?>
<script>
var gfD = <?= json_encode($datosMedio, $js); ?>;
google.charts.load('current', {packages: ['corechart']});
google.charts.setOnLoadCallback(gfDibujar);
function gfDibujar() {
    var G = GF;
    if (!gfD.length) { G.vacio('gf-ch-medio'); return; }
    var d = new google.visualization.DataTable(); d.addColumn('string', 'Medio'); d.addColumn('number', 'Contactos');
    gfD.forEach(function (m) { d.addRow([m.l, m.v]); });
    G.leyenda('gf-ch-medio', gfD.map(function (m) { return {color: m.c, label: m.l, valor: m.txt}; }));
    new google.visualization.PieChart(document.getElementById('gf-ch-medio')).draw(d, G.base({
        pieHole: 0.62, pieSliceText: 'none', pieSliceBorderColor: '#fff', chartArea: {left: 0, top: 4, right: 0, bottom: 4, width: '100%', height: '92%'},
        colors: gfD.map(function (m) { return m.c; })
    }));
}
(function () {
    var a = document.getElementById('gf-anio'), fi = document.getElementById('gf-fi'), ff = document.getElementById('gf-ff');
    if (a) {
        a.addEventListener('change', function () { if (a.value !== '') { fi.value = ''; ff.value = ''; } });
        [fi, ff].forEach(function (e) { e.addEventListener('change', function () { a.value = ''; }); });
    }
    var t;
    function redibujar() { clearTimeout(t); t = setTimeout(function () { if (window.google && google.visualization) { gfDibujar(); } }, 150); }
    window.addEventListener('resize', redibujar);
    window.addEventListener('beforeprint', redibujar);
})();
</script>
<?php
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

// ------------------------------------------------------------ Datos para graficas
$coloresMedio = gf_colores_medio(array_unique(array_merge(array_keys($medios), $mediosMes)));

$chartSem = [
    ['l' => 'Al día (≤30 días)', 'v' => $k['verde'], 'c' => '#0ca30c'],
    ['l' => 'Atención (31–90)', 'v' => $k['amarillo'], 'c' => '#fab219'],
    ['l' => 'Sin contacto (>90 o sin registro)', 'v' => $k['rojo'], 'c' => '#d03b3b'],
];
$totSem = $k['verde'] + $k['amarillo'] + $k['rojo'];
foreach ($chartSem as &$cs) { $cs['txt'] = $cs['v'] . ($totSem > 0 ? ' · ' . number_format($cs['v'] / $totSem * 100, 0) . ' %' : ''); }
unset($cs);
$datoSem = $totSem > 0 ? $k['rojo'] . ' de ' . $totSem . ' clientes llevan más de 90 días sin contacto (' . number_format($k['rojo'] / $totSem * 100, 0) . ' %).' : '';

$chartMedios = [];
$totMedios = array_sum($medios);
foreach ($medios as $m => $n) {
    $chartMedios[] = ['l' => $m, 'v' => $n, 'c' => $coloresMedio[$m], 'txt' => $n . ($totMedios > 0 ? ' · ' . number_format($n / $totMedios * 100, 0) . ' %' : '')];
}
$datoMedios = $chartMedios ? 'Más usado: ' . $chartMedios[0]['l'] . ' (' . $chartMedios[0]['txt'] . ').' : '';

$chartMes = [array_merge(['Mes'], $mediosMes)];
$mesTop = null;
$mesTopN = 0;
foreach ($porMes as $mes => $v) {
    $fila = [gf_mes_corto($mes)];
    $sumaMes = 0;
    foreach ($mediosMes as $m) { $n = isset($v[$m]) ? $v[$m] : 0; $fila[] = $n; $sumaMes += $n; }
    $chartMes[] = $fila;
    if ($sumaMes > $mesTopN) { $mesTopN = $sumaMes; $mesTop = $mes; }
}
$coloresMes = [];
foreach ($mediosMes as $m) { $coloresMes[] = $coloresMedio[$m]; }
$datoMes = $mesTop !== null ? 'Mes más activo: ' . gf_mes_corto($mesTop) . ' (' . $mesTopN . ' contactos).' : '';

$chartDeuda = [['Cliente', 'Pendiente']];
$deudores = array_filter($filas, function ($f) { return $f['pendiente'] > 0; });
usort($deudores, function ($a, $b) { return $b['pendiente'] <=> $a['pendiente']; });
foreach (array_slice($deudores, 0, 8) as $f) { $chartDeuda[] = [gf_resumir($f['nombre'], 22), round($f['pendiente'])]; }
$datoDeuda = $deudores ? 'Mayor saldo: ' . gf_resumir($deudores[0]['nombre'], 28) . ' (' . gf_money_corto($deudores[0]['pendiente']) . ').' : '';

$datosJs = ['sem' => $chartSem, 'medios' => $chartMedios, 'mes' => $chartMes, 'coloresMes' => $coloresMes, 'deuda' => $chartDeuda];
$js = JSON_UNESCAPED_UNICODE;
gf_estilos();
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<div class="gf-wrap">
    <div class="gf-hero">
        <h1>Cartera y seguimiento</h1>
        <p><?= gf_h($etiquetaPeriodo); ?> &middot; <?= gf_h(gf_nombre_ambito($ambito)); ?></p>
    </div>

    <?php if ($svc->getLastError() !== '') { ?>
        <div class="gf-error"><strong>No se pudo cargar parte de la información.</strong>
            <?php if ((int) ($_SESSION['superusuario'] ?? 0) === 1) { echo gf_h($svc->getLastError()); } ?></div>
    <?php } ?>
    <?php if ($svc->esRestringido()) { ?><div class="gf-aviso">Solo se muestran sus clientes asignados.</div><?php } ?>

    <form method="get" action="index.php" class="gf-card gf-noprint">
        <input type="hidden" name="doc" value="graphs_cartera" />
        <details class="gf-filtros-wrap" open>
            <summary>Filtros</summary>
            <div class="gf-filtros">
                <div class="gf-field"><label>Año</label>
                    <select name="anio" id="gf-anio"><option value="" <?= $anioSel === '' ? 'selected' : ''; ?>>Personalizado</option>
                        <option value="todos" <?= $anioSel === 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <?php foreach (gf_anios() as $y) { ?><option value="<?= $y; ?>" <?= $anioSel === $y ? 'selected' : ''; ?>><?= $y; ?></option><?php } ?>
                    </select></div>
                <div class="gf-field"><label>Desde</label><input type="date" name="fechaInicial" id="gf-fi" value="<?= gf_h($desde); ?>"></div>
                <div class="gf-field"><label>Hasta</label><input type="date" name="fechaFinal" id="gf-ff" value="<?= gf_h($hasta); ?>"></div>
                <div class="gf-field"><label>Ámbito</label>
                    <select name="ambito"><?php foreach (['negocio', 'personal', 'todo'] as $a) { ?><option value="<?= $a; ?>" <?= $ambito === $a ? 'selected' : ''; ?>><?= gf_h(gf_nombre_ambito($a)); ?></option><?php } ?></select></div>
                <div class="gf-field"><label>Comunicación</label>
                    <select name="com">
                        <option value="" <?= $com === '' ? 'selected' : ''; ?>>Todos</option>
                        <option value="alerta" <?= $com === 'alerta' ? 'selected' : ''; ?>>Deben y sin contacto &gt;90 días</option>
                        <option value="verde" <?= $com === 'verde' ? 'selected' : ''; ?>>Al día (≤30)</option>
                        <option value="amarillo" <?= $com === 'amarillo' ? 'selected' : ''; ?>>Atención (31–90)</option>
                        <option value="rojo" <?= $com === 'rojo' ? 'selected' : ''; ?>>Sin contacto (&gt;90)</option>
                    </select></div>
                <div class="gf-field"><label>Tipo de cliente</label>
                    <select name="tipo"><option value="">Todos</option><?php foreach ($tipos as $t) { ?><option <?= $tipoSel === $t ? 'selected' : ''; ?>><?= gf_h($t); ?></option><?php } ?></select></div>
                <div class="gf-field"><label>Comercial</label>
                    <select name="comercial"><option value="">Todos</option><?php foreach ($comerciales as $t) { ?><option <?= $comSel === $t ? 'selected' : ''; ?>><?= gf_h($t); ?></option><?php } ?></select></div>
                <div class="gf-field"><label>Ordenar por</label>
                    <select name="orden">
                        <?php foreach (['prioridad' => 'Prioridad', 'pendiente' => 'Mayor pendiente', 'dias' => 'Más días sin contacto', 'ganancia' => 'Mayor ganancia', 'cobrado' => 'Mayor cobrado', 'nombre' => 'Nombre'] as $v => $t) { ?>
                            <option value="<?= $v; ?>" <?= $orden === $v ? 'selected' : ''; ?>><?= $t; ?></option><?php } ?>
                    </select></div>
                <div class="gf-field"><label>Buscar</label><input type="text" name="q" value="<?= gf_h($busqueda); ?>" placeholder="Nombre, NIT o representante"></div>
                <div class="gf-field"><label>&nbsp;</label><label style="font-size:13px;font-weight:500;color:var(--gf-text)"><input type="checkbox" name="pend" value="1" <?= $soloPend ? 'checked' : ''; ?>> Solo con pendiente</label></div>
            </div>
            <div class="gf-acciones">
                <button type="submit" class="gf-btn gf-btn-primary">Aplicar</button>
                <a class="gf-btn gf-btn-ghost" href="index.php?doc=graphs_cartera">Limpiar</a>
            </div>
        </details>
        <?php gf_barra_exportar($paramsExport); ?>
    </form>

    <div class="gf-kpis">
        <div class="gf-kpi azul"><div class="t">Clientes</div><div class="v"><?= $k['clientes']; ?></div><div class="s">con actividad</div></div>
        <div class="gf-kpi azul"><div class="t">Cobrado</div><div class="v"><?= gf_money($k['cobrado']); ?></div><div class="s">ganancia <span class="<?= gf_clase_valor($k['ganancia']); ?>"><?= gf_money_corto($k['ganancia']); ?></span></div></div>
        <div class="gf-kpi ambar"><div class="t">Por cobrar</div><div class="v"><?= gf_money($k['pend_v']); ?></div><div class="s"><?= $k['pend_n']; ?> clientes con saldo</div></div>
        <div class="gf-kpi rojo"><div class="t">En riesgo</div><div class="v"><?= $k['alertas']; ?></div><div class="s">deben y &gt;90 días sin contacto · <?= gf_money_corto($k['alerta_v']); ?></div></div>
        <div class="gf-kpi verde"><div class="t">Al día</div><div class="v"><?= $k['verde']; ?></div><div class="s">contacto en 30 días</div></div>
        <div class="gf-kpi gris"><div class="t">Contactos</div><div class="v"><?= $k['contactos']; ?></div><div class="s">en el período</div></div>
    </div>

    <div class="gf-grid3">
        <?php gf_grafica('gf-ch-sem', 'Estado de la comunicación', [
            'que' => 'Cuántos clientes tienen contacto reciente.',
            'leer' => 'Verde: contacto en los últimos 30 días. Amarillo: entre 31 y 90. Rojo: más de 90 o sin registro.',
            'ojo' => 'Solo cuenta lo que el equipo registró como seguimiento.',
            'dato' => $datoSem,
        ], 230); ?>
        <?php gf_grafica('gf-ch-medios', 'Contactos por medio', [
            'que' => 'Canales usados con los clientes en el período.',
            'leer' => 'Cada porción es un medio; la leyenda indica cuántos contactos y qué porcentaje.',
            'ojo' => 'E-mail y SMS se registran juntos en un solo medio.',
            'dato' => $datoMedios,
        ], 230); ?>
        <?php gf_grafica('gf-ch-deuda', 'Mayor saldo por cobrar', [
            'que' => 'Los clientes que más dinero deben.',
            'leer' => 'Barra más larga = más saldo. Se muestran los 8 mayores.',
            'ojo' => 'Saldo = a cobrar − abonos, en cotizaciones aprobadas.',
            'dato' => $datoDeuda,
        ], 230); ?>
    </div>
    <?php gf_grafica('gf-ch-mes', 'Contactos por mes', [
        'que' => 'Cuánto se habló con los clientes cada mes.',
        'leer' => 'Cada color es un medio; la altura total es el número de contactos del mes.',
        'ojo' => 'Una llamada o mensaje sin registrar no aparece.',
        'dato' => $datoMes,
    ], 260); ?>

    <section class="gf-card">
        <h2>Clientes <small><?= count($filas); ?> · clic en el nombre abre la ficha</small></h2>
        <div class="gf-tablewrap">
        <table class="gf-table gf-t-cart">
            <thead><tr>
                <th class="sortable">Cliente</th><th class="sortable">Tipo</th><th class="sortable">Comercial</th><th class="sortable n">Cot.</th>
                <th class="sortable n">Cobrado</th><th class="sortable n">Ganancia</th><th class="sortable n">Pendiente</th>
                <th class="sortable">Último contacto</th><th class="sortable">Medio</th><th class="sortable">Quién</th><th>Qué se habló</th>
                <th class="sortable n">Días</th><th class="sortable n">Contactos</th>
            </tr></thead>
            <tbody>
            <?php foreach ($filas as $f) { $u = $f['ultimo']; ?>
                <tr>
                    <td><a href="index.php?<?= gf_h(http_build_query(array_merge(['doc' => 'graphs_cartera', 'cliente' => $f['id'], 'ambito' => $ambito], $qPeriodo))); ?>"><strong><?= gf_h($f['nombre']); ?></strong></a>
                        <?php if ($f['personal']) { ?><span class="gf-tag morado">Personal</span><?php } ?>
                        <?php if (!$f['existe']) { ?><span class="gf-tag rojo">Eliminado</span><?php } ?>
                        <?php if ($f['alerta']) { ?><span class="gf-tag rojo" title="Tiene saldo pendiente y más de 90 días sin contacto">Cobrar</span><?php } ?></td>
                    <td><?= gf_h($f['tipo']); ?></td><td><?= gf_h($f['comercial']); ?></td>
                    <td class="n"><?= $f['n_cot']; ?></td>
                    <td class="n" data-v="<?= round($f['cobrado_p']); ?>"><?= gf_money($f['cobrado_p']); ?></td>
                    <td class="n <?= gf_clase_valor($f['ganancia_p']); ?>" data-v="<?= round($f['ganancia_p']); ?>"><?= gf_money($f['ganancia_p']); ?></td>
                    <td class="n" data-v="<?= round($f['pendiente']); ?>"><?= $f['pendiente'] > 0 ? '<strong>' . gf_money($f['pendiente']) . '</strong>' : '—'; ?></td>
                    <td data-v="<?= $u ? gf_h($u['fecha']) : ''; ?>"><?= $u ? gf_fecha($u['fecha']) : '<span class="gf-nota">Sin registro</span>'; ?></td>
                    <td><?= $u ? gf_h($u['medio']) : ''; ?></td>
                    <td><?= $u ? gf_h($u['usuario']) : ''; ?></td>
                    <td title="<?= $u ? gf_h($u['observacion']) : ''; ?>"><?= $u ? gf_h(gf_resumir($u['observacion'], 70)) : ''; ?></td>
                    <td class="n" data-v="<?= $f['dias'] === null ? 99999 : $f['dias']; ?>"><span class="gf-dot <?= $f['semaforo']; ?>"></span><?= $f['dias'] === null ? '—' : $f['dias']; ?></td>
                    <td class="n"><?= $f['n_contactos_p']; ?></td>
                </tr>
            <?php } ?>
            <?php if (!$filas) { ?><tr><td colspan="13" class="gf-vacio">No hay clientes para estos filtros.</td></tr><?php } ?>
            </tbody>
        </table>
        </div>
    </section>

    <details class="gf-det">
        <summary>Cómo se calcula</summary>
        <ul class="gf-nota">
            <li><b>Último contacto:</b> el seguimiento más reciente registrado (fecha, medio, usuario y nota).</li>
            <li><b>Semáforo:</b> verde ≤30 días, amarillo 31–90, rojo &gt;90 o sin registro.</li>
            <li><b>Pendiente:</b> a cobrar − abonos, en cotizaciones aprobadas con saldo.</li>
            <li><b>Ganancia:</b> cobrado − gastos directos − auditoría, en el período.</li>
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
    function dona(id, items) {
        d = new google.visualization.DataTable(); d.addColumn('string', 'Concepto'); d.addColumn('number', 'Valor');
        items.forEach(function (i) { d.addRow([i.l, i.v]); });
        G.leyenda(id, items.map(function (i) { return {color: i.c, label: i.l, valor: i.txt}; }));
        new google.visualization.PieChart(document.getElementById(id)).draw(d, G.base({
            pieHole: 0.62, pieSliceText: 'none', pieSliceBorderColor: '#fff', chartArea: {left: 0, top: 4, right: 0, bottom: 4, width: '100%', height: '92%'},
            colors: items.map(function (i) { return i.c; })
        }));
    }
    var haySem = gfD.sem.some(function (i) { return i.v > 0; });
    if (haySem) { dona('gf-ch-sem', gfD.sem.filter(function (i) { return i.v > 0; })); } else { G.vacio('gf-ch-sem'); }
    if (gfD.medios.length) { dona('gf-ch-medios', gfD.medios); } else { G.vacio('gf-ch-medios'); }
    if (gfD.deuda.length > 1) {
        d = google.visualization.arrayToDataTable(gfD.deuda); G.formatear(d, [1]);
        new google.visualization.BarChart(document.getElementById('gf-ch-deuda')).draw(d, G.base({
            colors: [C.ambar], bar: {groupWidth: '65%'}, chartArea: {left: 120, top: 4, right: 8, bottom: 22, width: '100%', height: '86%'},
            hAxis: {format: 'short', gridlines: {color: C.grid}}, vAxis: {gridlines: {color: 'transparent'}, format: ''}
        }));
    } else { G.vacio('gf-ch-deuda'); }
    if (gfD.mes.length > 1 && gfD.mes[0].length > 1) {
        d = google.visualization.arrayToDataTable(gfD.mes);
        G.leyenda('gf-ch-mes', gfD.mes[0].slice(1).map(function (n, i) { return {color: gfD.coloresMes[i], label: n}; }));
        new google.visualization.ColumnChart(document.getElementById('gf-ch-mes')).draw(d, G.base({
            isStacked: true, colors: gfD.coloresMes, bar: {groupWidth: '60%'}, vAxis: {format: '#'}
        }));
    } else { G.vacio('gf-ch-mes'); }
}
(function () {
    var a = document.getElementById('gf-anio'), fi = document.getElementById('gf-fi'), ff = document.getElementById('gf-ff');
    if (a) {
        a.addEventListener('change', function () { if (a.value !== '') { fi.value = ''; ff.value = ''; } });
        [fi, ff].forEach(function (e) { e.addEventListener('change', function () { a.value = ''; }); });
    }
    var t;
    function redibujar() { clearTimeout(t); t = setTimeout(function () { if (window.google && google.visualization) { gfDibujar(); } }, 150); }
    window.addEventListener('resize', redibujar);
    window.addEventListener('beforeprint', redibujar);
})();
</script>
