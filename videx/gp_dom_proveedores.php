<?php
/**
 * Inventario de Dominios: proveedores de dominios (registradores) y todos los
 * dominios registrados. Independiente del inventario por proveedores de hosting.
 */
require_once('gp_config.php');
require_once('gp_dom_service.php');
gp_requerir_acceso();

$service = new GpDomService();
$buscar = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$proveedores = $service->listarProveedores($buscar);
$dominios = $service->listarTodosLosDominios();
$resumen = $service->contarResumen();

$coloresAvatar = [
    ['bg' => '#dbeafe', 'color' => '#2563eb'],
    ['bg' => '#dcfce7', 'color' => '#16a34a'],
    ['bg' => '#fef3c7', 'color' => '#b45309'],
    ['bg' => '#ede9fe', 'color' => '#7c3aed'],
    ['bg' => '#fee2e2', 'color' => '#dc2626'],
];
?>
<link rel="stylesheet" href="usuario-form.css?v=<?= @filemtime('usuario-form.css'); ?>" />
<link rel="stylesheet" href="gp.css?v=<?= @filemtime('gp.css'); ?>" />
<link rel="stylesheet" href="gp_dominios.css?v=<?= @filemtime('gp_dominios.css'); ?>" />

<div class="gp-wrap">
    <?php if (isset($_GET['eliminado'])) { ?>
        <div class="alert alert-success">Proveedor de dominios eliminado correctamente.</div>
    <?php } ?>
    <div class="gp-page-header">
        <div>
            <h1>Inventario de Dominios</h1>
            <p class="gp-page-header-sub">Proveedores de dominios y los dominios registrados en cada uno, con sus fechas de vencimiento y certificado SSL.</p>
        </div>
        <?php if (gp_puede_modificar()) { ?>
            <a href="index.php?doc=gp_alertas_email" class="gp-btn-secondary"><i class="bi bi-envelope-check"></i> Configurar alertas por correo</a>
        <?php } ?>
    </div>

    <div class="gp-kpi-grid">
        <div class="gp-kpi-card">
            <div><p class="gp-kpi-label">Dominios</p><div class="gp-kpi-value"><?= $resumen['total']; ?></div></div>
            <div class="gp-kpi-icon"><i class="bi bi-globe"></i></div>
        </div>
        <div class="gp-kpi-card">
            <div><p class="gp-kpi-label">Vencen en 30 días</p><div class="gp-kpi-value"><?= $resumen['por_vencer']; ?></div></div>
            <div class="gp-kpi-icon"><i class="bi bi-hourglass-split"></i></div>
        </div>
        <div class="gp-kpi-card">
            <div><p class="gp-kpi-label">Vencidos</p><div class="gp-kpi-value"><?= $resumen['vencidos']; ?></div></div>
            <div class="gp-kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
        </div>
    </div>

    <div class="gp-toolbar">
        <form method="get" class="gp-search">
            <input type="hidden" name="doc" value="gp_dom_proveedores" />
            <i class="bi bi-search"></i>
            <input type="text" name="q" placeholder="Buscar proveedor..." value="<?= htmlspecialchars($buscar, ENT_QUOTES); ?>" onchange="this.form.submit()" />
        </form>
        <?php if (gp_puede_modificar()) { ?>
            <a href="index.php?doc=gp_dom_proveedor&opc=1" class="gp-btn-primary"><i class="bi bi-plus-lg"></i> Agregar proveedor de dominios</a>
        <?php } ?>
    </div>

    <?php if (empty($proveedores)) { ?>
        <div class="gp-card">
            <div class="gp-table-empty">
                <i class="bi bi-inbox"></i>
                <p>No hay proveedores de dominios registrados<?= $buscar !== '' ? ' que coincidan con "' . htmlspecialchars($buscar, ENT_QUOTES) . '"' : ''; ?>.</p>
            </div>
        </div>
    <?php } else { ?>
        <div class="gp-provider-grid">
            <?php foreach ($proveedores as $p) {
                $av = $coloresAvatar[ord(strtoupper(substr($p['nombre'], 0, 1))) % count($coloresAvatar)];
                ?>
                <div class="gp-provider-card">
                    <div class="gp-provider-card-top">
                        <span class="gp-avatar" style="background:<?= $av['bg']; ?>;color:<?= $av['color']; ?>;"><?= htmlspecialchars(strtoupper(substr($p['nombre'], 0, 1)), ENT_QUOTES); ?></span>
                        <div>
                            <strong><?= htmlspecialchars($p['nombre'], ENT_QUOTES); ?></strong>
                            <p class="gp-provider-card-host"><?= htmlspecialchars((string) $p['sitio_web'], ENT_QUOTES); ?></p>
                        </div>
                    </div>
                    <div class="gp-provider-metricas">
                        <div class="gp-provider-metrica">
                            <div class="gp-provider-metrica-valor"><?= (int) $p['total_dominios']; ?></div>
                            <div class="gp-provider-metrica-label">Dominios</div>
                        </div>
                        <div class="gp-provider-metrica">
                            <div class="gp-provider-metrica-valor"><?= (int) $p['total_por_vencer']; ?></div>
                            <div class="gp-provider-metrica-label">Por vencer</div>
                        </div>
                        <div class="gp-provider-metrica">
                            <div class="gp-provider-metrica-valor"><?= (int) $p['total_vencidos']; ?></div>
                            <div class="gp-provider-metrica-label">Vencidos</div>
                        </div>
                    </div>
                    <a href="index.php?doc=gp_dom_proveedor&opc=2&id=<?= (int) $p['id']; ?>" class="gp-btn-primary">Gestionar <i class="bi bi-arrow-right"></i></a>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="gp-page-header" style="margin-top:32px;">
        <div>
            <h1 style="font-size:20px;">Todos los dominios</h1>
            <p class="gp-page-header-sub">Ordenados por fecha de vencimiento.</p>
        </div>
    </div>

    <div class="gp-toolbar">
        <div class="gp-search">
            <i class="bi bi-search"></i>
            <input type="text" id="gpdBuscar" placeholder="Buscar por dominio o proveedor..." />
        </div>
    </div>

    <div class="gp-card">
        <?php if (empty($dominios)) { ?>
            <div class="gp-table-empty"><i class="bi bi-globe"></i><p>Aún no hay dominios registrados.</p></div>
        <?php } else { ?>
            <table class="gp-table" id="gpdTabla">
                <thead>
                    <tr><th>Dominio</th><th>Proveedor</th><th>Vencimiento</th><th>SSL</th><th>Renovación auto.</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($dominios as $d) { ?>
                        <tr>
                            <td class="gp-mono"><strong><?= htmlspecialchars($d['dominio'], ENT_QUOTES); ?></strong></td>
                            <td><a href="index.php?doc=gp_dom_proveedor&opc=2&id=<?= (int) $d['proveedor_id']; ?>"><?= htmlspecialchars($d['proveedor_nombre'], ENT_QUOTES); ?></a></td>
                            <td><?= GpDomService::badgeVencimiento($d['fecha_vencimiento']); ?></td>
                            <td><?= GpDomService::badgeVencimiento($d['ssl_fecha_vencimiento'] ?? null); ?></td>
                            <td><?= $d['renovacion_automatica'] ? '<i class="bi bi-arrow-repeat gpd-si" title="Sí"></i> Sí' : '<span class="gpd-no">No</span>'; ?></td>
                            <td><span class="gp-badge <?= $d['estado'] ? 'gp-badge-verde' : 'gp-badge-rojo'; ?>"><?= $d['estado'] ? 'Activo' : 'Inactivo'; ?></span></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="gp-table-empty" id="gpdSinResultados" hidden><i class="bi bi-search"></i><p>Ningún dominio coincide con la búsqueda.</p></div>
            <div class="gp-card-footer" id="gpdContador">Mostrando <?= count($dominios); ?> dominio<?= count($dominios) == 1 ? '' : 's'; ?></div>
        <?php } ?>
    </div>
</div>

<script>
(function () {
    var input = document.getElementById('gpdBuscar');
    if (!input) return;
    var filas = Array.prototype.slice.call(document.querySelectorAll('#gpdTabla tbody tr'));
    var sin = document.getElementById('gpdSinResultados');
    var contador = document.getElementById('gpdContador');
    input.addEventListener('input', function () {
        var t = input.value.trim().toLowerCase(), visibles = 0;
        filas.forEach(function (f) {
            var ok = f.textContent.toLowerCase().indexOf(t) !== -1;
            f.hidden = !ok;
            if (ok) visibles++;
        });
        sin.hidden = visibles !== 0;
        contador.hidden = visibles === 0;
        contador.textContent = 'Mostrando ' + visibles + ' de ' + filas.length + ' dominio' + (filas.length == 1 ? '' : 's');
    });
})();
</script>
