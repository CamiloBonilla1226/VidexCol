<?php
/**
 * Proyecto de Inversion: panel del proyecto de un cliente (pestaña principal
 * del modulo). Filtro de linea de tiempo, KPIs, consolidacion multibanco,
 * graficos de evolucion temporal y distribucion bancaria, y las tablas de
 * ingresos / egresos con sus drawers de creacion/edicion.
 */
require_once('gp_inversion_config.php');
require_once('gp_inversion_service.php');
gp_inv_requerir_acceso();

$service = new GpInversionService();
$id = (int) ($_GET['id'] ?? 0);

$restriccion = gp_inv_cliente_restriccion();
$proyecto = $id > 0 ? $service->getProyecto($id) : null;
if (!$proyecto) {
    die('<div style="padding:40px;text-align:center;color:#64748b;font-family:Inter,sans-serif;"><h3>Proyecto no encontrado.</h3></div>');
}
if ($restriccion !== null && (int) $proyecto['cliente_id'] !== $restriccion) {
    die('<div style="padding:40px;text-align:center;color:#64748b;font-family:Inter,sans-serif;"><h3>No tiene acceso a este proyecto.</h3></div>');
}

$puedeEscribir = gp_inv_puede_escribir();
$puedeEliminar = gp_inv_puede_eliminar();

$desde = isset($_GET['desde']) ? trim((string) $_GET['desde']) : date('Y-01-01');
$hasta = isset($_GET['hasta']) ? trim((string) $_GET['hasta']) : date('Y-m-d');

// Los KPIs superiores (Capital Invertido, Gastos, Utilidades, ROI) son totales
// GLOBALES del proyecto: no dependen del filtro de fechas, igual que el Saldo
// Neto Disponible (consolidadoPorCuenta, que tampoco se filtra por fecha). Antes
// usaban el mismo rango que la tabla/grafico y por eso un movimiento con fecha
// fuera del rango por defecto (ej. un typo a futuro) desaparecia de "Gastos"
// pero SI seguia restando del saldo real, dando la falsa impresion de que la
// resta no cuadraba. El filtro de fechas queda como consulta independiente,
// solo para la Evolucion Temporal y las tablas de Ingresos/Egresos (vista del periodo).
$consolidado = $service->calcularConsolidado($id);
$porCuenta = $service->consolidadoPorCuenta($id);
$evolucion = $service->evolucionTemporal($id, $desde, $hasta);
$ingresos = $service->listarMovimientos($id, 'ingreso', $desde, $hasta);
$egresos = $service->listarMovimientos($id, 'egreso', $desde, $hasta);
$cuentas = $service->listarCuentas($id);

$anexosPorMovimiento = [];
foreach (array_merge($ingresos, $egresos) as $m) {
    $anexosPorMovimiento[$m['id']] = $service->listarAnexos($m['id']);
}

function gpInvFecha($f) { return $f ? date('d/m/Y', strtotime($f)) : '—'; }
function gpInvMoneda($v) { return '$' . number_format((float) $v, 0, ',', '.'); }
function gpInvFechaHora($f) { return $f ? date('d/m/Y H:i', strtotime($f)) : ''; }

// Datos de cada movimiento para el panel de detalle, el drawer de edicion y el
// visor de anexos. Los anexos viajan AQUI (antes el drawer de edicion esperaba
// `datos.anexos` pero la fila nunca lo traia, por eso no se veian los ya subidos).
// Solo se exponen id/nombre/tipo/tamano: nunca el nombre fisico del archivo.
$movimientosJs = [];
foreach (array_merge($ingresos, $egresos) as $m) {
    $anexosMov = [];
    foreach ($anexosPorMovimiento[$m['id']] as $a) {
        $anexosMov[] = [
            'id' => (int) $a['id'],
            'nombre_original' => (string) $a['nombre_original'],
            'mime_tipo' => (string) $a['mime_tipo'],
            'tamano' => (int) $a['tamano'],
            'slot' => (int) $a['slot'],
        ];
    }
    $movimientosJs[(int) $m['id']] = [
        'id' => (int) $m['id'],
        'tipo' => $m['tipo_movimiento'],
        'fecha' => $m['fecha'],
        'fecha_fmt' => gpInvFecha($m['fecha']),
        'valor' => $m['valor'],
        'valor_fmt' => gpInvMoneda($m['valor']),
        'tipo_ingreso' => $m['tipo_ingreso'] ?? '',
        'tipo_ingreso_label' => GP_INV_TIPOS_INGRESO[$m['tipo_ingreso'] ?? ''] ?? ($m['tipo_ingreso'] ?? ''),
        'origen_fondos' => $m['origen_fondos'] ?? '',
        'clasificacion_egreso' => $m['clasificacion_egreso'] ?? '',
        'clasificacion_label' => GP_INV_CLASIFICACION_EGRESO[$m['clasificacion_egreso'] ?? ''] ?? ($m['clasificacion_egreso'] ?? ''),
        'destino_fondo' => $m['destino_fondo'] ?? '',
        'cuenta_id' => $m['cuenta_id'],
        'cuenta_banco' => $m['cuenta_banco'] ?? '',
        'cuenta_alias' => $m['cuenta_alias'] ?? '',
        'concepto' => $m['concepto'] ?? '',
        'creacion_fecha_fmt' => gpInvFechaHora($m['creacion_fecha'] ?? ''),
        'modificacion_fecha_fmt' => gpInvFechaHora($m['modificacion_fecha'] ?? ''),
        'puede_editar' => $puedeEscribir && gp_inv_puede_editar_movimiento($m['creacion_fecha']),
        'anexos' => $anexosMov,
    ];
}
?>
<link rel="stylesheet" href="usuario-form.css?v=<?= @filemtime('usuario-form.css'); ?>" />
<link rel="stylesheet" href="gp.css?v=<?= @filemtime('gp.css'); ?>" />
<link rel="stylesheet" href="gp_inversion.css?v=<?= @filemtime('gp_inversion.css'); ?>" />
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<div class="gp-wrap">
    <div class="gp-breadcrumb">
        <a href="index.php?doc=gp_inversion">Proyecto de Inversión</a> <i class="bi bi-chevron-right"></i> <span class="gp-actual"><?= htmlspecialchars($proyecto['cliente_nombre'], ENT_QUOTES); ?></span>
    </div>
    <div class="gp-page-header">
        <div>
            <h1><?= htmlspecialchars($proyecto['cliente_nombre'], ENT_QUOTES); ?> — <?= htmlspecialchars($proyecto['nombre'], ENT_QUOTES); ?></h1>
            <p class="gp-page-header-sub"><?= htmlspecialchars((string) $proyecto['descripcion'], ENT_QUOTES); ?></p>
        </div>
        <div class="gp-inv-header-acciones">
            <span class="gp-badge-estado<?= $proyecto['estado'] ? '' : ' gp-badge-estado-inactivo'; ?>"><?= $proyecto['estado'] ? 'En ejecución' : 'Cerrado'; ?></span>
            <?php if ($puedeEliminar) { ?>
                <button type="button" class="gp-icon-btn gp-icon-danger" title="Eliminar proyecto" onclick="gpInvEliminarProyecto()"><i class="bi bi-trash"></i> Eliminar proyecto</button>
            <?php } ?>
        </div>
    </div>

    <form method="get" class="gp-inv-filtro">
        <input type="hidden" name="doc" value="gp_inversion_proyecto" />
        <input type="hidden" name="id" value="<?= $id; ?>" />
        <div class="gp-field"><label>Fecha inicial</label><input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde, ENT_QUOTES); ?>" /></div>
        <div class="gp-field"><label>Fecha final</label><input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta, ENT_QUOTES); ?>" /></div>
        <button type="submit" class="gp-btn-primary"><i class="bi bi-funnel"></i> Aplicar filtro</button>
        <a class="gp-btn-secondary" target="_blank" href="gp_inversion_informe.php?id=<?= $id; ?>&desde=<?= urlencode($desde); ?>&hasta=<?= urlencode($hasta); ?>"><i class="bi bi-printer"></i> Imprimir Informe Ejecutivo</a>
    </form>
    <p class="gp-inv-sub" style="margin:-4px 0 16px;">Este filtro solo afecta la Evolución Temporal y las tablas de Ingresos/Egresos. Los KPIs de arriba (Capital, Gastos, Utilidades, Saldo y ROI) son siempre totales globales del proyecto.</p>

    <div class="gp-inv-kpis">
        <div class="gp-inv-kpi"><div class="gp-inv-kpi-label"><i class="bi bi-piggy-bank"></i> Capital Invertido Total</div><div class="gp-inv-kpi-valor"><?= gpInvMoneda($consolidado['capital_invertido']); ?></div></div>
        <div class="gp-inv-kpi"><div class="gp-inv-kpi-label"><i class="bi bi-graph-up-arrow"></i> Utilidades Totales</div><div class="gp-inv-kpi-valor gp-inv-kpi-pos"><?= gpInvMoneda($consolidado['utilidades']); ?></div></div>
        <div class="gp-inv-kpi"><div class="gp-inv-kpi-label"><i class="bi bi-wallet2"></i> Total Gastos Operativos</div><div class="gp-inv-kpi-valor gp-inv-kpi-neg"><?= gpInvMoneda($consolidado['gastos']); ?></div></div>
        <div class="gp-inv-kpi"><div class="gp-inv-kpi-label"><i class="bi bi-bank"></i> Saldo Neto Disponible</div><div class="gp-inv-kpi-valor"><?= gpInvMoneda($porCuenta['total']); ?></div></div>
        <div class="gp-inv-kpi"><div class="gp-inv-kpi-label"><i class="bi bi-percent"></i> Rentabilidad ROI</div><div class="gp-inv-kpi-valor <?= $consolidado['roi'] >= 0 ? 'gp-inv-kpi-pos' : 'gp-inv-kpi-neg'; ?>"><?= number_format($consolidado['roi'], 2); ?>%</div></div>
    </div>

    <div class="gp-card">
        <div class="gp-card-header">
            <span class="gp-card-header-title"><i class="bi bi-bank2"></i> Consolidación Multibanco</span>
            <?php if ($puedeEscribir) { ?><button type="button" class="gp-btn-secondary" onclick="gpInvAbrirModalCuenta()"><i class="bi bi-plus-lg"></i> Cuenta bancaria</button><?php } ?>
        </div>
        <?php if (empty($porCuenta['cuentas'])) { ?>
            <div class="gp-table-empty"><i class="bi bi-bank"></i><p>Aún no hay cuentas bancarias registradas para este proyecto.</p></div>
        <?php } else { ?>
            <table class="gp-table">
                <thead><tr><th>Banco / Cuenta</th><th>Saldo disponible</th><th>% del total</th><th>Distribución</th></tr></thead>
                <tbody>
                    <?php foreach ($porCuenta['cuentas'] as $c) { ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['banco'], ENT_QUOTES); ?></strong><?php if ($c['numero_alias']) { ?> <span class="gp-inv-sub"><?= htmlspecialchars($c['numero_alias'], ENT_QUOTES); ?></span><?php } ?></td>
                            <td class="gp-mono"><?= gpInvMoneda($c['saldo']); ?> COP</td>
                            <td><?= number_format($c['porcentaje'], 1); ?>%</td>
                            <td><div class="gp-inv-barra"><div class="gp-inv-barra-rel" style="width:<?= $c['porcentaje']; ?>%;"></div></div></td>
                        </tr>
                    <?php } ?>
                </tbody>
                <tfoot><tr><td>Total consolidado</td><td class="gp-mono"><?= gpInvMoneda($porCuenta['total']); ?> COP</td><td>100%</td><td></td></tr></tfoot>
            </table>
        <?php } ?>
    </div>

    <div class="gp-inv-graficos">
        <div class="gp-card">
            <div class="gp-card-header"><span class="gp-card-header-title"><i class="bi bi-bar-chart"></i> Evolución Temporal</span></div>
            <div id="gpInvGraficoEvolucion" class="gp-inv-chart"></div>
        </div>
        <div class="gp-card">
            <div class="gp-card-header"><span class="gp-card-header-title"><i class="bi bi-pie-chart"></i> Distribución Bancaria</span></div>
            <div id="gpInvGraficoBancos" class="gp-inv-chart"></div>
        </div>
    </div>

    <div class="gp-inv-tablas">
        <div class="gp-card">
            <div class="gp-card-header">
                <span class="gp-card-header-title"><i class="bi bi-arrow-down-circle"></i> Ingresos</span>
                <?php if ($puedeEscribir) { ?><button type="button" class="gp-btn-primary" onclick="gpInvAbrirDrawer('ingreso')"><i class="bi bi-plus-lg"></i> Nuevo ingreso</button><?php } ?>
            </div>
            <?php if (empty($ingresos)) { ?>
                <div class="gp-table-empty"><i class="bi bi-inbox"></i><p>Sin ingresos en el período seleccionado.</p></div>
            <?php } else { ?>
                <table class="gp-table">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Origen</th><th>Banco</th><th class="gp-th-derecha">Valor</th><th>Anexos</th><th class="gp-th-derecha">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($ingresos as $m) {
                            $puedeEditar = gp_inv_puede_editar_movimiento($m['creacion_fecha']);
                            ?>
                            <tr class="gp-inv-fila" data-mov="<?= (int) $m['id']; ?>" tabindex="0" title="Ver detalle">
                                <td><?= gpInvFecha($m['fecha']); ?></td>
                                <td><span class="gp-badge gp-badge-azul"><?= htmlspecialchars(GP_INV_TIPOS_INGRESO[$m['tipo_ingreso']] ?? $m['tipo_ingreso'], ENT_QUOTES); ?></span></td>
                                <td><?= htmlspecialchars((string) $m['origen_fondos'], ENT_QUOTES); ?></td>
                                <td><?= htmlspecialchars((string) $m['cuenta_banco'], ENT_QUOTES); ?></td>
                                <td class="gp-th-derecha gp-mono gp-inv-kpi-pos"><?= gpInvMoneda($m['valor']); ?></td>
                                <td><?= count($anexosPorMovimiento[$m['id']]) ? '<i class="bi bi-paperclip"></i> ' . count($anexosPorMovimiento[$m['id']]) : '—'; ?></td>
                                <td class="gp-col-accion">
                                    <?php if ($puedeEditar) { ?><button type="button" class="gp-icon-btn" title="Editar" onclick="gpInvEditar(<?= (int) $m['id']; ?>)"><i class="bi bi-pencil"></i></button><?php } ?>
                                    <?php if ($puedeEliminar) { ?><button type="button" class="gp-icon-btn gp-icon-danger" title="Eliminar" onclick="gpInvEliminarMovimiento(<?= (int) $m['id']; ?>)"><i class="bi bi-trash"></i></button><?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>

        <div class="gp-card">
            <div class="gp-card-header">
                <span class="gp-card-header-title"><i class="bi bi-arrow-up-circle"></i> Egresos</span>
                <?php if ($puedeEscribir) { ?><button type="button" class="gp-btn-rojo" onclick="gpInvAbrirDrawer('egreso')"><i class="bi bi-plus-lg"></i> Nuevo egreso</button><?php } ?>
            </div>
            <?php if (empty($egresos)) { ?>
                <div class="gp-table-empty"><i class="bi bi-inbox"></i><p>Sin egresos en el período seleccionado.</p></div>
            <?php } else { ?>
                <table class="gp-table">
                    <thead><tr><th>Fecha</th><th>Clasificación</th><th>Destino</th><th>Banco</th><th class="gp-th-derecha">Valor</th><th>Anexos</th><th class="gp-th-derecha">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($egresos as $m) {
                            $puedeEditar = gp_inv_puede_editar_movimiento($m['creacion_fecha']);
                            $esGasto = $m['clasificacion_egreso'] === 'gasto_operativo';
                            ?>
                            <tr class="gp-inv-fila" data-mov="<?= (int) $m['id']; ?>" tabindex="0" title="Ver detalle">
                                <td><?= gpInvFecha($m['fecha']); ?></td>
                                <td><span class="gp-badge <?= $esGasto ? 'gp-badge-rojo' : 'gp-badge-azul'; ?>"><?= htmlspecialchars(GP_INV_CLASIFICACION_EGRESO[$m['clasificacion_egreso']] ?? $m['clasificacion_egreso'], ENT_QUOTES); ?></span></td>
                                <td><?= htmlspecialchars((string) $m['destino_fondo'], ENT_QUOTES); ?></td>
                                <td><?= htmlspecialchars((string) $m['cuenta_banco'], ENT_QUOTES); ?></td>
                                <td class="gp-th-derecha gp-mono gp-inv-kpi-neg"><?= gpInvMoneda($m['valor']); ?></td>
                                <td><?= count($anexosPorMovimiento[$m['id']]) ? '<i class="bi bi-paperclip"></i> ' . count($anexosPorMovimiento[$m['id']]) : '—'; ?></td>
                                <td class="gp-col-accion">
                                    <?php if ($puedeEditar) { ?><button type="button" class="gp-icon-btn" title="Editar" onclick="gpInvEditar(<?= (int) $m['id']; ?>)"><i class="bi bi-pencil"></i></button><?php } ?>
                                    <?php if ($puedeEliminar) { ?><button type="button" class="gp-icon-btn gp-icon-danger" title="Eliminar" onclick="gpInvEliminarMovimiento(<?= (int) $m['id']; ?>)"><i class="bi bi-trash"></i></button><?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>
</div>

<!-- ===== Drawer lateral de solo lectura: detalle del ingreso / egreso ===== -->
<div class="gp-inv-drawer-overlay" id="gpInvDetalleOverlay" onclick="if(event.target===this) gpInvCerrarDetalle()">
    <div class="gp-inv-drawer" id="gpInvDetalle" role="dialog" aria-modal="true" aria-labelledby="gpInvDetTitulo">
        <div class="gp-inv-drawer-header">
            <div>
                <span class="gp-badge" id="gpInvDetBadge"></span>
                <h3 id="gpInvDetTitulo" style="margin-top:8px;"></h3>
                <p id="gpInvDetSub" class="gp-inv-drawer-sub"></p>
            </div>
            <button type="button" onclick="gpInvCerrarDetalle()" aria-label="Cerrar">&times;</button>
        </div>
        <div class="gp-inv-drawer-body">
            <div class="gp-inv-det-valor" id="gpInvDetValor"></div>
            <dl class="gp-inv-det-lista" id="gpInvDetLista"></dl>
            <div class="gp-inv-det-anexos">
                <h4><i class="bi bi-paperclip"></i> Soportes / Anexos <span id="gpInvDetNAnexos"></span></h4>
                <div id="gpInvDetAnexos" class="gp-inv-anexo-grid"></div>
            </div>
        </div>
        <div class="gp-inv-drawer-footer" id="gpInvDetFooter">
            <?php if ($puedeEliminar) { ?><button type="button" class="gp-inv-btn-peligro" id="gpInvDetEliminar"><i class="bi bi-trash"></i> Eliminar</button><?php } ?>
            <button type="button" class="gp-modal-cancelar" onclick="gpInvCerrarDetalle()"><i class="bi bi-x-lg"></i> Cerrar</button>
            <?php if ($puedeEscribir) { ?><button type="button" class="gp-modal-confirmar" id="gpInvDetEditar"><i class="bi bi-pencil"></i> Editar</button><?php } ?>
        </div>
    </div>
</div>

<!-- ===== Visor de anexos (vista previa estilo Google Drive) ===== -->
<div class="gp-inv-visor" id="gpInvVisor" role="dialog" aria-modal="true" aria-label="Vista previa del anexo" hidden>
    <div class="gp-inv-visor-top">
        <button type="button" class="gp-inv-visor-btn" id="gpInvVisorCerrar" aria-label="Cerrar vista previa"><i class="bi bi-x-lg"></i></button>
        <div class="gp-inv-visor-titulo"><i class="bi bi-file-earmark" id="gpInvVisorIcono"></i><span id="gpInvVisorNombre"></span><small id="gpInvVisorMeta"></small></div>
        <div class="gp-inv-visor-acciones">
            <a class="gp-inv-visor-btn" id="gpInvVisorAbrir" target="_blank" rel="noopener" title="Abrir en una pestaña nueva"><i class="bi bi-box-arrow-up-right"></i></a>
            <a class="gp-inv-visor-btn" id="gpInvVisorDescargar" title="Descargar"><i class="bi bi-download"></i></a>
        </div>
    </div>
    <div class="gp-inv-visor-escenario" id="gpInvVisorEscenario"></div>
    <button type="button" class="gp-inv-visor-nav gp-inv-visor-prev" id="gpInvVisorPrev" aria-label="Anexo anterior"><i class="bi bi-chevron-left"></i></button>
    <button type="button" class="gp-inv-visor-nav gp-inv-visor-next" id="gpInvVisorNext" aria-label="Anexo siguiente"><i class="bi bi-chevron-right"></i></button>
    <div class="gp-inv-visor-contador" id="gpInvVisorContador"></div>
</div>

<script>
var GP_INV_MOVS = <?= json_encode($movimientosJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR); ?>;

function gpInvTamano(bytes) {
    bytes = Number(bytes) || 0;
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1).replace('.', ',') + ' MB';
    return Math.max(1, Math.round(bytes / 1024)) + ' KB';
}
function gpInvUrlAnexo(id, descarga) {
    return 'gp_inversion_anexo_descargar.php?id=' + encodeURIComponent(id) + (descarga ? '&dl=1' : '');
}
// 'pdf' | 'imagen' | 'zip' | 'excel' | 'csv' | 'otro'
function gpInvTipoArchivo(a) {
    var m = String(a.mime_tipo || '').toLowerCase();
    if (m === 'application/pdf') return 'pdf';
    if (m.indexOf('image/') === 0) return 'imagen';
    if (m.indexOf('zip') > -1 || m.indexOf('rar') > -1) return 'zip';
    if (m.indexOf('spreadsheetml') > -1) return 'excel';
    if (m.indexOf('csv') > -1) return 'csv';
    return 'otro';
}
var GP_INV_ICONOS = { pdf: 'bi-file-earmark-pdf', imagen: 'bi-file-earmark-image', zip: 'bi-file-earmark-zip', excel: 'bi-file-earmark-excel', csv: 'bi-file-earmark-text', otro: 'bi-file-earmark' };

// Miniatura clicable: la imagen real para fotos, un icono de color para el resto.
function gpInvMiniatura(a) {
    var tipo = gpInvTipoArchivo(a);
    var b = document.createElement('button');
    b.type = 'button';
    b.title = 'Ver ' + a.nombre_original;
    if (tipo === 'imagen') {
        b.className = 'gp-inv-mini gp-inv-mini-imagen';
        var img = document.createElement('img');
        img.loading = 'lazy';
        img.alt = a.nombre_original;
        img.src = gpInvUrlAnexo(a.id, false);
        b.appendChild(img);
    } else {
        b.className = 'gp-inv-mini gp-inv-mini-' + tipo;
        b.innerHTML = '<i class="bi ' + GP_INV_ICONOS[tipo] + '"></i>';
    }
    return b;
}

/* ---------------- Visor de anexos (estilo Google Drive) ---------------- */
var gpInvVisorEstado = { lista: [], i: 0, foco: null };

function gpInvAbrirVisor(lista, i) {
    gpInvVisorEstado.lista = lista;
    gpInvVisorEstado.i = i;
    gpInvVisorEstado.foco = document.activeElement;
    document.getElementById('gpInvVisor').hidden = false;
    document.body.classList.add('gp-inv-visor-abierto');
    gpInvRenderVisor();
    document.getElementById('gpInvVisorCerrar').focus();
}

function gpInvRenderVisor() {
    var est = gpInvVisorEstado, a = est.lista[est.i];
    if (!a) return;
    var tipo = gpInvTipoArchivo(a);
    document.getElementById('gpInvVisorNombre').textContent = a.nombre_original;
    document.getElementById('gpInvVisorMeta').textContent = gpInvTamano(a.tamano);
    document.getElementById('gpInvVisorIcono').className = 'bi ' + GP_INV_ICONOS[tipo];
    document.getElementById('gpInvVisorAbrir').href = gpInvUrlAnexo(a.id, false);
    document.getElementById('gpInvVisorDescargar').href = gpInvUrlAnexo(a.id, true);
    document.getElementById('gpInvVisorPrev').style.display = est.lista.length > 1 ? '' : 'none';
    document.getElementById('gpInvVisorNext').style.display = est.lista.length > 1 ? '' : 'none';
    document.getElementById('gpInvVisorContador').textContent = est.lista.length > 1 ? (est.i + 1) + ' / ' + est.lista.length : '';

    var esc = document.getElementById('gpInvVisorEscenario');
    esc.innerHTML = '';
    if (tipo === 'imagen') {
        var img = document.createElement('img');
        img.className = 'gp-inv-visor-img';
        img.alt = a.nombre_original;
        img.title = 'Clic para ampliar / reducir';
        img.addEventListener('click', function () { img.classList.toggle('gp-zoom'); });
        img.addEventListener('error', function () { esc.innerHTML = '<div class="gp-inv-visor-card"><i class="bi bi-exclamation-triangle"></i><strong>No se pudo cargar la imagen</strong></div>'; });
        img.src = gpInvUrlAnexo(a.id, false);
        esc.appendChild(img);
    } else if (tipo === 'pdf') {
        var fr = document.createElement('iframe');
        fr.className = 'gp-inv-visor-pdf';
        fr.title = a.nombre_original;
        fr.src = gpInvUrlAnexo(a.id, false) + '#view=FitH';
        esc.appendChild(fr);
        var ayuda = document.createElement('div');
        ayuda.className = 'gp-inv-visor-ayuda';
        ayuda.innerHTML = '¿No se ve el PDF? <a target="_blank" rel="noopener"></a>';
        ayuda.lastChild.href = gpInvUrlAnexo(a.id, false);
        ayuda.lastChild.textContent = 'Ábrelo en una pestaña nueva';
        esc.appendChild(ayuda);
    } else {
        var card = document.createElement('div');
        card.className = 'gp-inv-visor-card';
        card.innerHTML = '<i class="bi ' + GP_INV_ICONOS[tipo] + '"></i><strong></strong><span>Este tipo de archivo no tiene vista previa.</span><a class="gp-btn-primary"><i class="bi bi-download"></i> Descargar</a>';
        card.querySelector('strong').textContent = a.nombre_original;
        card.querySelector('a').href = gpInvUrlAnexo(a.id, true);
        esc.appendChild(card);
    }
}

function gpInvVisorMover(delta) {
    var est = gpInvVisorEstado, n = est.lista.length;
    if (n < 2) return;
    est.i = (est.i + delta + n) % n;
    gpInvRenderVisor();
}

function gpInvCerrarVisor() {
    document.getElementById('gpInvVisor').hidden = true;
    document.getElementById('gpInvVisorEscenario').innerHTML = '';
    document.body.classList.remove('gp-inv-visor-abierto');
    var foco = gpInvVisorEstado.foco;
    gpInvVisorEstado.foco = null;
    if (foco && foco.focus) foco.focus();
}

document.getElementById('gpInvVisorCerrar').addEventListener('click', gpInvCerrarVisor);
document.getElementById('gpInvVisorPrev').addEventListener('click', function () { gpInvVisorMover(-1); });
document.getElementById('gpInvVisorNext').addEventListener('click', function () { gpInvVisorMover(1); });
document.getElementById('gpInvVisorEscenario').addEventListener('click', function (e) {
    if (e.target === this) gpInvCerrarVisor(); // clic en el fondo oscuro
});

/* ---------------- Panel lateral de detalle (solo lectura) ---------------- */
var gpInvDetalleId = 0;

function gpInvVerDetalle(id) {
    var m = GP_INV_MOVS[id];
    if (!m) return;
    gpInvDetalleId = id;
    var esIngreso = m.tipo === 'ingreso';

    var badge = document.getElementById('gpInvDetBadge');
    badge.className = 'gp-badge ' + (esIngreso ? 'gp-badge-verde' : 'gp-badge-rojo');
    badge.textContent = esIngreso ? 'Ingreso' : 'Egreso';
    document.getElementById('gpInvDetTitulo').textContent = esIngreso ? (m.tipo_ingreso_label || 'Ingreso') : (m.clasificacion_label || 'Egreso');
    document.getElementById('gpInvDetSub').textContent = m.fecha_fmt + (m.cuenta_banco ? ' · ' + m.cuenta_banco : '');
    var valor = document.getElementById('gpInvDetValor');
    valor.textContent = m.valor_fmt + ' COP';
    valor.className = 'gp-inv-det-valor ' + (esIngreso ? 'gp-inv-kpi-pos' : 'gp-inv-kpi-neg');

    var lista = document.getElementById('gpInvDetLista');
    lista.innerHTML = '';
    function fila(etiqueta, valorTxt) {
        var dt = document.createElement('dt'); dt.textContent = etiqueta;
        var dd = document.createElement('dd'); dd.textContent = valorTxt || '—';
        lista.appendChild(dt); lista.appendChild(dd);
    }
    fila('Fecha', m.fecha_fmt);
    if (esIngreso) {
        fila('Tipo de ingreso', m.tipo_ingreso_label);
        fila('Origen de fondos', m.origen_fondos);
    } else {
        fila('Clasificación', m.clasificacion_label);
        fila('Destino del fondo', m.destino_fondo);
    }
    fila('Cuenta / Banco ' + (esIngreso ? 'destino' : 'origen'), m.cuenta_banco ? m.cuenta_banco + (m.cuenta_alias ? ' - ' + m.cuenta_alias : '') : '');
    fila('Concepto / Razón', m.concepto);
    if (m.creacion_fecha_fmt) fila('Registrado', m.creacion_fecha_fmt);
    if (m.modificacion_fecha_fmt) fila('Última modificación', m.modificacion_fecha_fmt);

    var anexos = m.anexos || [];
    document.getElementById('gpInvDetNAnexos').textContent = anexos.length ? '(' + anexos.length + ')' : '';
    var grid = document.getElementById('gpInvDetAnexos');
    grid.innerHTML = '';
    if (!anexos.length) {
        grid.innerHTML = '<p class="gp-inv-drawer-sub">Este movimiento no tiene anexos.</p>';
    }
    anexos.forEach(function (a, i) {
        var card = document.createElement('div');
        card.className = 'gp-inv-anexo-card';
        var mini = gpInvMiniatura(a);
        mini.addEventListener('click', function () { gpInvAbrirVisor(anexos, i); });
        var nombre = document.createElement('button');
        nombre.type = 'button';
        nombre.className = 'gp-inv-anexo-nombre';
        nombre.innerHTML = '<strong></strong><span></span>';
        nombre.firstChild.textContent = a.nombre_original;
        nombre.lastChild.textContent = gpInvTamano(a.tamano);
        nombre.addEventListener('click', function () { gpInvAbrirVisor(anexos, i); });
        card.appendChild(mini);
        card.appendChild(nombre);
        grid.appendChild(card);
    });

    var bEditar = document.getElementById('gpInvDetEditar');
    if (bEditar) bEditar.style.display = m.puede_editar ? '' : 'none';

    document.getElementById('gpInvDetalleOverlay').classList.add('gp-open');
}

function gpInvCerrarDetalle() {
    document.getElementById('gpInvDetalleOverlay').classList.remove('gp-open');
    gpInvDetalleId = 0;
}

(function () {
    var bEditar = document.getElementById('gpInvDetEditar');
    if (bEditar) bEditar.addEventListener('click', function () {
        var id = gpInvDetalleId;
        gpInvCerrarDetalle();
        if (typeof gpInvEditar === 'function') gpInvEditar(id);
    });
    var bEliminar = document.getElementById('gpInvDetEliminar');
    if (bEliminar) bEliminar.addEventListener('click', function () {
        if (typeof gpInvEliminarMovimiento === 'function') gpInvEliminarMovimiento(gpInvDetalleId);
    });

    // Clic en una fila de Ingresos/Egresos (salvo en los botones de accion).
    document.addEventListener('click', function (e) {
        var tr = e.target.closest ? e.target.closest('tr.gp-inv-fila') : null;
        if (!tr || e.target.closest('.gp-col-accion')) return;
        gpInvVerDetalle(parseInt(tr.getAttribute('data-mov'), 10));
    });
    document.addEventListener('keydown', function (e) {
        if (!document.getElementById('gpInvVisor').hidden) {
            if (e.key === 'Escape') { e.preventDefault(); gpInvCerrarVisor(); }
            else if (e.key === 'ArrowLeft') { gpInvVisorMover(-1); }
            else if (e.key === 'ArrowRight') { gpInvVisorMover(1); }
            return;
        }
        if (e.key === 'Escape' && document.getElementById('gpInvDetalleOverlay').classList.contains('gp-open')) {
            gpInvCerrarDetalle();
        } else if ((e.key === 'Enter' || e.key === ' ') && e.target && e.target.classList && e.target.classList.contains('gp-inv-fila')) {
            e.preventDefault();
            gpInvVerDetalle(parseInt(e.target.getAttribute('data-mov'), 10));
        }
    });
})();
</script>

<?php if ($puedeEscribir) { ?>
<!-- ===== Drawer lateral: Nuevo/Editar Ingreso o Egreso ===== -->
<div class="gp-inv-drawer-overlay" id="gpInvDrawerOverlay" onclick="if(event.target===this) gpInvCerrarDrawer()">
    <div class="gp-inv-drawer" id="gpInvDrawer">
        <div class="gp-inv-drawer-header">
            <div>
                <h3 id="gpInvDrawerTitulo">Nuevo Ingreso</h3>
                <p id="gpInvDrawerSubtitulo" class="gp-inv-drawer-sub"></p>
            </div>
            <button type="button" onclick="gpInvCerrarDrawer()">&times;</button>
        </div>
        <form id="gpInvDrawerForm" class="gp-inv-drawer-body" enctype="multipart/form-data">
            <input type="hidden" id="gpInvTipo" value="ingreso" />
            <input type="hidden" id="gpInvId" value="0" />
            <div class="gp-field"><label>Fecha *</label><input type="date" class="form-control" id="gpInvFecha" required /></div>
            <div class="gp-field"><label>Monto / Valor *</label><div class="gp-inv-input-moneda"><span>$</span><input type="number" step="0.01" min="0" class="form-control" id="gpInvValor" required /><span>COP</span></div></div>

            <div class="gp-field" id="gpInvCampoTipoIngreso">
                <label>Tipo de Ingreso *</label>
                <select class="form-control" id="gpInvTipoIngreso">
                    <?php foreach (GP_INV_TIPOS_INGRESO as $k => $v) { echo '<option value="' . $k . '">' . htmlspecialchars($v, ENT_QUOTES) . '</option>'; } ?>
                </select>
            </div>
            <div class="gp-field" id="gpInvCampoOrigen"><label>Origen de Fondos</label><input type="text" class="form-control" id="gpInvOrigen" placeholder="Nombre del inversionista u origen" /></div>

            <div class="gp-field" id="gpInvCampoClasificacion">
                <label>Clasificación del Egreso *</label>
                <div class="gp-inv-radio-cards">
                    <?php foreach (GP_INV_CLASIFICACION_EGRESO as $k => $v) { ?>
                        <label class="gp-inv-radio-card" data-valor="<?= $k; ?>">
                            <input type="radio" name="gpInvClasificacion" value="<?= $k; ?>" <?= $k === 'gasto_operativo' ? 'checked' : ''; ?> />
                            <strong><?= htmlspecialchars($v, ENT_QUOTES); ?></strong>
                            <span><?= $k === 'gasto_operativo' ? 'Monto no recuperable' : 'Monto destinado a generar retornos'; ?></span>
                        </label>
                    <?php } ?>
                </div>
            </div>
            <div class="gp-field" id="gpInvCampoDestino"><label>Destino del Fondo</label><input type="text" class="form-control" id="gpInvDestino" placeholder="Persona, entidad, proveedor o proyecto receptor" /></div>

            <div class="gp-field"><label>Cuenta / Banco <span id="gpInvEtiquetaCuenta">Destino</span></label>
                <select class="form-control" id="gpInvCuenta">
                    <option value="">Sin asignar</option>
                    <?php foreach ($cuentas as $c) { ?>
                        <option value="<?= (int) $c['id']; ?>"><?= htmlspecialchars($c['banco'] . ($c['numero_alias'] ? ' - ' . $c['numero_alias'] : ''), ENT_QUOTES); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="gp-field"><label>Concepto / Razón</label><textarea class="form-control" id="gpInvConcepto" rows="3" placeholder="Describe el motivo del movimiento"></textarea></div>

            <div class="gp-field">
                <label>Soportes / Anexos</label>
                <div id="gpInvAnexosExistentes"></div>
                <p class="gp-inv-drawer-sub">Carga los documentos de verificación (PDF, imagen, ZIP, RAR, XLSX, CSV - máx 10MB c/u).</p>
                <div class="gp-inv-dropzone"><label>Anexo 1<input type="file" id="gpInvAnexo1" /></label></div>
                <div class="gp-inv-dropzone"><label>Anexo 2<input type="file" id="gpInvAnexo2" /></label></div>
                <div class="gp-inv-dropzone"><label>Anexo 3<input type="file" id="gpInvAnexo3" /></label></div>
            </div>
        </form>
        <div class="gp-inv-drawer-footer">
            <button type="button" class="gp-modal-cancelar" onclick="gpInvCerrarDrawer()">Cancelar</button>
            <button type="button" class="gp-modal-confirmar" id="gpInvGuardarMovimiento"><i class="bi bi-check2"></i> <span id="gpInvGuardarEtiqueta">Guardar</span></button>
        </div>
    </div>
</div>

<script>
var GP_INV_PROYECTO_ID = <?= $id; ?>;
var GP_INV_CSRF = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>';

function gpInvAbrirDrawer(tipo, datos) {
    datos = datos || {};
    document.getElementById('gpInvTipo').value = tipo;
    document.getElementById('gpInvId').value = datos.id || 0;
    document.getElementById('gpInvDrawerTitulo').textContent = (datos.id ? 'Editar ' : 'Nuevo ') + (tipo === 'ingreso' ? 'Ingreso' : 'Egreso');
    document.getElementById('gpInvDrawerSubtitulo').textContent = tipo === 'ingreso'
        ? 'Registrar movimiento de capital o rendimientos para <?= htmlspecialchars($proyecto['cliente_nombre'], ENT_QUOTES); ?>'
        : 'Registrar salida de fondos, gasto operativo o asignación de capital.';
    document.getElementById('gpInvFecha').value = datos.fecha || '';
    document.getElementById('gpInvValor').value = datos.valor || '';
    document.getElementById('gpInvOrigen').value = datos.origen_fondos || '';
    document.getElementById('gpInvDestino').value = datos.destino_fondo || '';
    document.getElementById('gpInvConcepto').value = datos.concepto || '';
    document.getElementById('gpInvCuenta').value = datos.cuenta_id || '';
    if (datos.tipo_ingreso) document.getElementById('gpInvTipoIngreso').value = datos.tipo_ingreso;
    if (datos.clasificacion_egreso) {
        var radios = document.querySelectorAll('input[name="gpInvClasificacion"]');
        radios.forEach(function (r) { r.checked = (r.value === datos.clasificacion_egreso); });
    }

    var esIngreso = tipo === 'ingreso';
    document.getElementById('gpInvCampoTipoIngreso').style.display = esIngreso ? '' : 'none';
    document.getElementById('gpInvCampoOrigen').style.display = esIngreso ? '' : 'none';
    document.getElementById('gpInvCampoClasificacion').style.display = esIngreso ? 'none' : '';
    document.getElementById('gpInvCampoDestino').style.display = esIngreso ? 'none' : '';
    document.getElementById('gpInvEtiquetaCuenta').textContent = esIngreso ? 'Destino' : 'Origen';
    document.getElementById('gpInvGuardarMovimiento').className = esIngreso ? 'gp-modal-confirmar' : 'gp-modal-confirmar gp-modal-confirmar-rojo';
    document.getElementById('gpInvGuardarEtiqueta').textContent = 'Guardar ' + (esIngreso ? 'ingreso' : 'egreso');

    gpInvRenderAnexosEdicion(datos.anexos || []);

    document.getElementById('gpInvDrawerOverlay').classList.add('gp-open');
}

function gpInvEditar(id) {
    var m = GP_INV_MOVS[id];
    if (!m) return;
    gpInvAbrirDrawer(m.tipo, m);
}

// Anexos ya subidos del movimiento que se esta editando: con miniatura/icono,
// vista previa en la misma pagina (visor) y descarga.
function gpInvRenderAnexosEdicion(anexos) {
    var ex = document.getElementById('gpInvAnexosExistentes');
    ex.innerHTML = '';
    if (!anexos.length) return;
    var t = document.createElement('p');
    t.className = 'gp-inv-drawer-sub';
    t.style.margin = '0 0 6px';
    t.textContent = 'Anexos ya cargados (' + anexos.length + ')';
    ex.appendChild(t);
    var lista = document.createElement('div');
    lista.className = 'gp-inv-anexo-lista';
    anexos.forEach(function (a, i) {
        var fila = document.createElement('div');
        fila.className = 'gp-inv-anexo-fila';
        var mini = gpInvMiniatura(a);
        mini.className += ' gp-inv-anexo-mini';
        mini.addEventListener('click', function () { gpInvAbrirVisor(anexos, i); });
        var info = document.createElement('button');
        info.type = 'button';
        info.className = 'gp-inv-anexo-info';
        info.innerHTML = '<strong></strong><span></span>';
        info.firstChild.textContent = a.nombre_original;
        info.lastChild.textContent = gpInvTamano(a.tamano) + ' · Anexo ' + a.slot;
        info.addEventListener('click', function () { gpInvAbrirVisor(anexos, i); });
        var dl = document.createElement('a');
        dl.className = 'gp-icon-btn';
        dl.title = 'Descargar';
        dl.href = gpInvUrlAnexo(a.id, true);
        dl.innerHTML = '<i class="bi bi-download"></i>';
        fila.appendChild(mini); fila.appendChild(info); fila.appendChild(dl);
        lista.appendChild(fila);
    });
    lista.style.marginBottom = '12px';
    ex.appendChild(lista);
}

function gpInvCerrarDrawer() {
    document.getElementById('gpInvDrawerOverlay').classList.remove('gp-open');
    document.getElementById('gpInvDrawerForm').reset();
}

document.getElementById('gpInvGuardarMovimiento').addEventListener('click', function () {
    var tipo = document.getElementById('gpInvTipo').value;
    var fd = new FormData();
    fd.append('accion', tipo === 'ingreso' ? 'guardar_ingreso' : 'guardar_egreso');
    fd.append('csrf_token', GP_INV_CSRF);
    fd.append('proyecto_id', GP_INV_PROYECTO_ID);
    fd.append('id', document.getElementById('gpInvId').value);
    fd.append('fecha', document.getElementById('gpInvFecha').value);
    fd.append('valor', document.getElementById('gpInvValor').value);
    fd.append('cuenta_id', document.getElementById('gpInvCuenta').value);
    fd.append('concepto', document.getElementById('gpInvConcepto').value);
    if (tipo === 'ingreso') {
        fd.append('tipo_ingreso', document.getElementById('gpInvTipoIngreso').value);
        fd.append('origen_fondos', document.getElementById('gpInvOrigen').value);
    } else {
        fd.append('destino_fondo', document.getElementById('gpInvDestino').value);
        var clasif = document.querySelector('input[name="gpInvClasificacion"]:checked');
        fd.append('clasificacion_egreso', clasif ? clasif.value : '');
    }
    [1, 2, 3].forEach(function (n) {
        var input = document.getElementById('gpInvAnexo' + n);
        if (input.files && input.files[0]) fd.append('anexo' + n, input.files[0]);
    });
    if (!document.getElementById('gpInvFecha').value || !document.getElementById('gpInvValor').value) {
        alert('Fecha y valor son obligatorios.');
        return;
    }
    var btn = document.getElementById('gpInvGuardarMovimiento');
    var etiqueta = document.getElementById('gpInvGuardarEtiqueta');
    function restaurar() { btn.disabled = false; btn.classList.remove('gp-cargando'); etiqueta.textContent = 'Guardar ' + (tipo === 'ingreso' ? 'ingreso' : 'egreso'); }
    btn.disabled = true;
    btn.classList.add('gp-cargando');
    etiqueta.textContent = 'Guardando...';
    fetch('gp_inversion_ajax.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) { window.location.reload(); }
            else { restaurar(); alert(data.error || 'No se pudo guardar.'); }
        })
        .catch(function () { restaurar(); alert('No se pudo guardar: error de conexión con el servidor.'); });
});

function gpInvAbrirModalCuenta() {
    var overlay = document.getElementById('gpModalOverlay');
    if (overlay) overlay.remove();
    var div = document.createElement('div');
    div.className = 'gp-modal-overlay gp-open';
    div.id = 'gpModalOverlay';
    div.innerHTML =
        '<div class="gp-modal-form-box">' +
        '  <div class="gp-modal-form-header"><h3>Agregar cuenta bancaria</h3><button type="button" onclick="document.getElementById(\'gpModalOverlay\').remove()">&times;</button></div>' +
        '  <div class="gp-modal-form-body">' +
        '    <div class="gp-field gp-field-full"><label>Banco *</label><input type="text" class="form-control" id="gpInvCtaBanco" placeholder="Ej: Bancolombia" /></div>' +
        '    <div class="gp-field"><label>Tipo de cuenta</label><input type="text" class="form-control" id="gpInvCtaTipo" placeholder="Corriente, Ahorros..." /></div>' +
        '    <div class="gp-field"><label>Número / Alias</label><input type="text" class="form-control" id="gpInvCtaAlias" placeholder="*4581" /></div>' +
        '  </div>' +
        '  <div class="gp-modal-form-footer"><button type="button" class="gp-modal-cancelar" onclick="document.getElementById(\'gpModalOverlay\').remove()">Cancelar</button><button type="button" class="gp-modal-confirmar" id="gpInvCtaGuardar">Guardar</button></div>' +
        '</div>';
    document.body.appendChild(div);
    document.getElementById('gpInvCtaGuardar').onclick = function () {
        var banco = document.getElementById('gpInvCtaBanco').value.trim();
        if (!banco) { alert('El banco es obligatorio.'); return; }
        var body = new URLSearchParams();
        body.set('accion', 'guardar_cuenta');
        body.set('csrf_token', GP_INV_CSRF);
        body.set('proyecto_id', GP_INV_PROYECTO_ID);
        body.set('banco', banco);
        body.set('tipo_cuenta', document.getElementById('gpInvCtaTipo').value);
        body.set('numero_alias', document.getElementById('gpInvCtaAlias').value);
        fetch('gp_inversion_ajax.php', { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) { if (data.ok) window.location.reload(); else alert(data.error || 'No se pudo guardar.'); });
    };
}
</script>
<?php } ?>

<?php if ($puedeEliminar) { ?>
<script>
var GP_INV_CSRF_ELIMINAR = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>';

function gpInvEliminarMovimiento(id) {
    if (!confirm('¿Eliminar este movimiento? Esta acción no se puede deshacer.')) return;
    var body = new URLSearchParams();
    body.set('accion', 'eliminar_movimiento');
    body.set('csrf_token', GP_INV_CSRF_ELIMINAR);
    body.set('id', id);
    fetch('gp_inversion_ajax.php', { method: 'POST', body: body, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) { if (data.ok) window.location.reload(); else alert(data.error || 'No se pudo eliminar.'); });
}

function gpInvEliminarProyecto() {
    if (!confirm('¿Eliminar este proyecto de inversión por completo?\n\nSe eliminarán también todos sus ingresos, egresos y cuentas bancarias registradas. Esta acción no se puede deshacer.')) return;
    var body = new URLSearchParams();
    body.set('accion', 'eliminar_proyecto');
    body.set('csrf_token', GP_INV_CSRF_ELIMINAR);
    body.set('id', <?= $id; ?>);
    fetch('gp_inversion_ajax.php', { method: 'POST', body: body, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) { window.location.href = 'index.php?doc=gp_inversion'; }
            else { alert(data.error || 'No se pudo eliminar el proyecto.'); }
        });
}
</script>
<?php } ?>

<script>
google.charts.load('current', { packages: ['corechart'] });
google.charts.setOnLoadCallback(function () {
    var evolucion = <?= json_encode($evolucion); ?>;
    var dEvo = new google.visualization.DataTable();
    dEvo.addColumn('string', 'Mes');
    dEvo.addColumn('number', 'Capital');
    dEvo.addColumn('number', 'Utilidades');
    dEvo.addColumn('number', 'Gastos');
    evolucion.forEach(function (f) { dEvo.addRow([f.mes, f.capital, f.utilidades, f.gastos]); });
    new google.visualization.ColumnChart(document.getElementById('gpInvGraficoEvolucion')).draw(dEvo, {
        legend: { position: 'top' }, colors: ['#2563eb', '#16a34a', '#dc2626'],
        chartArea: { left: 60, top: 30, width: '88%', height: '75%' }, height: 280,
    });

    var bancos = <?= json_encode($porCuenta['cuentas']); ?>;
    var dBan = new google.visualization.DataTable();
    dBan.addColumn('string', 'Banco');
    dBan.addColumn('number', 'Saldo');
    bancos.forEach(function (f) { dBan.addRow([f.banco, f.saldo]); });
    new google.visualization.PieChart(document.getElementById('gpInvGraficoBancos')).draw(dBan, {
        pieHole: 0.5, legend: { position: 'right' }, colors: ['#2563eb', '#16a34a', '#CEAB37', '#7c3aed', '#dc2626', '#0891b2'],
        chartArea: { top: 10, height: '85%' }, height: 280,
    });
});
</script>
