<?php
require_once('gp_config.php');
require_once('gp_service.php');
gp_requerir_acceso();

$service = new GpService();
$buscar = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$porPagina = 10;

$proveedores = $service->listarProveedores($buscar, $pagina, $porPagina);
$total = $service->contarProveedores($buscar);
$totalPaginas = max(1, (int) ceil($total / $porPagina));
$todosLosProyectos = $service->listarTodosLosProyectos();

$coloresAvatar = [
    ['bg' => '#dbeafe', 'color' => '#2563eb'],
    ['bg' => '#dcfce7', 'color' => '#16a34a'],
    ['bg' => '#fef3c7', 'color' => '#b45309'],
    ['bg' => '#ede9fe', 'color' => '#7c3aed'],
    ['bg' => '#fee2e2', 'color' => '#dc2626'],
];
function gpColorAvatar($coloresAvatar, $texto) {
    $indice = ord(strtoupper(substr($texto, 0, 1))) % count($coloresAvatar);
    return $coloresAvatar[$indice];
}
?>
<link rel="stylesheet" href="usuario-form.css?v=<?= @filemtime('usuario-form.css'); ?>" />
<link rel="stylesheet" href="gp.css?v=<?= @filemtime('gp.css'); ?>" />

<div class="gp-wrap">
    <?php if (isset($_GET['eliminado'])) { ?>
        <div class="alert alert-success">Proveedor eliminado correctamente.</div>
    <?php } ?>
    <div class="gp-page-header">
        <div>
            <h1>Listado de Proveedores</h1>
            <p class="gp-page-header-sub">Gestión de infraestructura técnica y cuentas de hosting.</p>
        </div>
        <?php if (gp_puede_modificar()) { ?>
            <a href="index.php?doc=gp_alertas_email" class="gp-btn-secondary"><i class="bi bi-envelope-check"></i> Configurar alertas por correo</a>
        <?php } ?>
    </div>

    <div class="gp-toolbar">
        <form method="get" class="gp-search">
            <input type="hidden" name="doc" value="gp_proveedores" />
            <i class="bi bi-search"></i>
            <input type="text" name="q" placeholder="Buscar proveedor, host..." value="<?= htmlspecialchars($buscar, ENT_QUOTES); ?>" onchange="this.form.submit()" />
        </form>
        <?php if (gp_puede_modificar()) { ?>
            <a href="index.php?doc=gp_proveedor&opc=1" class="gp-btn-primary"><i class="bi bi-plus-lg"></i> Agregar proveedor</a>
        <?php } ?>
    </div>

    <?php if (empty($proveedores)) { ?>
        <div class="gp-card">
            <div class="gp-table-empty">
                <i class="bi bi-inbox"></i>
                <p>No hay proveedores registrados<?= $buscar !== '' ? ' que coincidan con "' . htmlspecialchars($buscar, ENT_QUOTES) . '"' : ''; ?>.</p>
            </div>
        </div>
    <?php } else { ?>
        <div class="gp-provider-grid">
            <?php foreach ($proveedores as $p) {
                $avatar = gpColorAvatar($coloresAvatar, $p['nombre']);
                ?>
                <div class="gp-provider-card">
                    <div class="gp-provider-card-top">
                        <span class="gp-avatar" style="background:<?= $avatar['bg']; ?>;color:<?= $avatar['color']; ?>;"><?= htmlspecialchars(strtoupper(substr($p['nombre'], 0, 1)), ENT_QUOTES); ?></span>
                        <div>
                            <strong><?= htmlspecialchars($p['nombre'], ENT_QUOTES); ?></strong>
                            <p class="gp-provider-card-host"><?= htmlspecialchars($p['host'], ENT_QUOTES); ?></p>
                        </div>
                    </div>
                    <div class="gp-provider-metricas">
                        <div class="gp-provider-metrica">
                            <div class="gp-provider-metrica-valor"><?= (int) $p['total_servicios']; ?></div>
                            <div class="gp-provider-metrica-label">Servicios</div>
                        </div>
                        <div class="gp-provider-metrica">
                            <div class="gp-provider-metrica-valor"><?= (int) $p['total_proyectos']; ?></div>
                            <div class="gp-provider-metrica-label">Proyectos</div>
                        </div>
                        <div class="gp-provider-metrica">
                            <div class="gp-provider-metrica-valor"><?= (int) $p['total_credenciales']; ?></div>
                            <div class="gp-provider-metrica-label">Credenciales</div>
                        </div>
                    </div>
                    <a href="index.php?doc=gp_proveedor&opc=2&id=<?= (int) $p['id']; ?>" class="gp-btn-primary">Gestionar <i class="bi bi-arrow-right"></i></a>
                </div>
            <?php } ?>
        </div>
        <div class="gp-card-footer" style="border:1px solid #e2e8f0;border-top:none;border-radius:0 0 10px 10px;margin-top:-1px;">
            <div class="gp-paginacion">
                <span>Mostrando <?= count($proveedores); ?> de <?= $total; ?> proveedor<?= $total == 1 ? '' : 'es'; ?></span>
                <?php if ($totalPaginas > 1) { ?>
                    <div class="gp-paginacion-paginas">
                        <a href="index.php?doc=gp_proveedores&q=<?= urlencode($buscar); ?>&pagina=<?= max(1, $pagina - 1); ?>"><i class="bi bi-chevron-left"></i></a>
                        <?php for ($i = 1; $i <= $totalPaginas; $i++) { ?>
                            <?php if ($i == $pagina) { ?>
                                <span class="gp-pagina-activa"><?= $i; ?></span>
                            <?php } else { ?>
                                <a href="index.php?doc=gp_proveedores&q=<?= urlencode($buscar); ?>&pagina=<?= $i; ?>"><?= $i; ?></a>
                            <?php } ?>
                        <?php } ?>
                        <a href="index.php?doc=gp_proveedores&q=<?= urlencode($buscar); ?>&pagina=<?= min($totalPaginas, $pagina + 1); ?>"><i class="bi bi-chevron-right"></i></a>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

    <div class="gp-page-header" style="margin-top:32px;">
        <div>
            <h1 style="font-size:20px;">Todos los proyectos</h1>
            <p class="gp-page-header-sub">Proyectos de todos los proveedores y servicios registrados.</p>
        </div>
    </div>

    <div class="gp-toolbar">
        <div class="gp-search">
            <i class="bi bi-search"></i>
            <input type="text" id="gpBuscarProyectos" placeholder="Buscar por proyecto, servicio, proveedor o estado..." />
        </div>
    </div>

    <div class="gp-card">
        <?php if (empty($todosLosProyectos)) { ?>
            <div class="gp-table-empty">
                <i class="bi bi-kanban"></i>
                <p>No hay proyectos registrados.</p>
            </div>
        <?php } else { ?>
            <table class="gp-table" id="gpTablaProyectos">
                <thead>
                    <tr>
                        <th>Proyecto</th>
                        <th>Servicio</th>
                        <th>Proveedor</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todosLosProyectos as $pr) { ?>
                        <tr>
                            <td><a href="index.php?doc=gp_proyecto&opc=2&id=<?= (int) $pr['id']; ?>"><strong><?= htmlspecialchars($pr['nombre'], ENT_QUOTES); ?></strong></a></td>
                            <td><a href="index.php?doc=gp_servicio&opc=2&id=<?= (int) $pr['servicio_id']; ?>"><?= htmlspecialchars($pr['servicio_nombre'], ENT_QUOTES); ?></a></td>
                            <td><a href="index.php?doc=gp_proveedor&opc=2&id=<?= (int) $pr['proveedor_id']; ?>"><?= htmlspecialchars($pr['proveedor_nombre'], ENT_QUOTES); ?></a></td>
                            <td><span class="gp-badge <?= $pr['estado'] ? 'gp-badge-verde' : 'gp-badge-rojo'; ?>"><?= $pr['estado'] ? 'Activo' : 'Inactivo'; ?></span></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="gp-table-empty" id="gpProyectosSinResultados" hidden>
                <i class="bi bi-search"></i>
                <p>Ningún proyecto coincide con la búsqueda.</p>
            </div>
            <div class="gp-card-footer" id="gpProyectosContador">Mostrando <?= count($todosLosProyectos); ?> proyecto<?= count($todosLosProyectos) == 1 ? '' : 's'; ?></div>
        <?php } ?>
    </div>
</div>

<script>
(function () {
    var input = document.getElementById('gpBuscarProyectos');
    if (!input) return;
    var filas = Array.prototype.slice.call(document.querySelectorAll('#gpTablaProyectos tbody tr'));
    var sinResultados = document.getElementById('gpProyectosSinResultados');
    var contador = document.getElementById('gpProyectosContador');
    input.addEventListener('input', function () {
        var termino = input.value.trim().toLowerCase();
        var visibles = 0;
        filas.forEach(function (fila) {
            var coincide = fila.textContent.toLowerCase().indexOf(termino) !== -1;
            fila.hidden = !coincide;
            if (coincide) visibles++;
        });
        sinResultados.hidden = visibles !== 0;
        contador.hidden = visibles === 0;
        contador.textContent = 'Mostrando ' + visibles + ' de ' + filas.length + ' proyecto' + (filas.length == 1 ? '' : 's');
    });
})();
</script>
