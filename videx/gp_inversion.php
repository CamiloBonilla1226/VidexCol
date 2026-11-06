<?php
/**
 * Proyecto de Inversion: listado de proyectos (uno por cliente que invierte
 * a traves de la empresa). Cada proyecto se gestiona en gp_inversion_proyecto.php.
 */
require_once('gp_inversion_config.php');
require_once('gp_inversion_service.php');
gp_inv_requerir_acceso();

if (gp_inv_es_externo()) {
    // El inversionista externo no tiene listado: se le resuelve directo a su proyecto.
    $clienteId = gp_inv_cliente_restriccion();
    $service = new GpInversionService();
    $proyecto = $service->getProyectoPorCliente($clienteId);
    if ($proyecto) {
        header('Location: index.php?doc=gp_inversion_proyecto&id=' . (int) $proyecto['id']);
        exit;
    }
    die('<div style="padding:40px;text-align:center;color:#64748b;font-family:Inter,sans-serif;"><h3>Aún no tiene un proyecto de inversión asignado.</h3></div>');
}

$service = new GpInversionService();
$buscar = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$proyectos = $service->listarProyectos($buscar);

$db = new DBbase_Sql();
$db->query("SELECT id, nombre, nit FROM cliente WHERE estado != 0 ORDER BY nombre ASC");
$clientes = [];
while ($db->next_record()) {
    $clientes[] = ['id' => $db->f('id'), 'nombre' => $db->f('nombre'), 'nit' => $db->f('nit')];
}
?>
<link rel="stylesheet" href="usuario-form.css?v=<?= @filemtime('usuario-form.css'); ?>" />
<link rel="stylesheet" href="gp.css?v=<?= @filemtime('gp.css'); ?>" />
<link rel="stylesheet" href="gp_inversion.css?v=<?= @filemtime('gp_inversion.css'); ?>" />

<div class="gp-wrap">
    <div class="gp-page-header">
        <div>
            <h1>Proyecto de Inversión</h1>
            <p class="gp-page-header-sub">Capital, rendimientos y gastos por cliente inversionista.</p>
        </div>
    </div>

    <div class="gp-toolbar">
        <form method="get" class="gp-search">
            <input type="hidden" name="doc" value="gp_inversion" />
            <i class="bi bi-search"></i>
            <input type="text" name="q" placeholder="Buscar por cliente o proyecto..." value="<?= htmlspecialchars($buscar, ENT_QUOTES); ?>" onchange="this.form.submit()" />
        </form>
        <?php if (gp_inv_puede_escribir()) { ?>
            <button type="button" class="gp-btn-primary" onclick="gpInvAbrirModalProyecto()"><i class="bi bi-plus-lg"></i> Nuevo proyecto de inversión</button>
        <?php } ?>
    </div>

    <?php if (empty($proyectos)) { ?>
        <div class="gp-card">
            <div class="gp-table-empty">
                <i class="bi bi-graph-up-arrow"></i>
                <p>No hay proyectos de inversión registrados<?= $buscar !== '' ? ' que coincidan con "' . htmlspecialchars($buscar, ENT_QUOTES) . '"' : ''; ?>.</p>
            </div>
        </div>
    <?php } else { ?>
        <div class="gp-card">
            <table class="gp-table">
                <thead><tr><th>Cliente</th><th>Proyecto</th><th>Estado</th><th class="gp-th-derecha">Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($proyectos as $p) { ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['cliente_nombre'], ENT_QUOTES); ?></strong><?php if ($p['cliente_nit']) { ?><div class="gp-inv-sub"><?= htmlspecialchars($p['cliente_nit'], ENT_QUOTES); ?></div><?php } ?></td>
                            <td><?= htmlspecialchars($p['nombre'], ENT_QUOTES); ?></td>
                            <td><span class="gp-badge <?= $p['estado'] ? 'gp-badge-verde' : 'gp-badge-rojo'; ?>"><?= $p['estado'] ? 'En ejecución' : 'Cerrado'; ?></span></td>
                            <td class="gp-th-derecha"><a href="index.php?doc=gp_inversion_proyecto&id=<?= (int) $p['id']; ?>" class="gp-btn-secondary">Ver proyecto <i class="bi bi-arrow-right"></i></a></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="gp-card-footer">Mostrando <?= count($proyectos); ?> proyecto<?= count($proyectos) == 1 ? '' : 's'; ?></div>
        </div>
    <?php } ?>
</div>

<?php if (gp_inv_puede_escribir()) { ?>
<script>
function gpInvAbrirModalProyecto() {
    var overlay = document.getElementById('gpModalOverlay');
    if (overlay) overlay.remove();
    var div = document.createElement('div');
    div.className = 'gp-modal-overlay gp-open';
    div.id = 'gpModalOverlay';
    div.innerHTML =
        '<div class="gp-modal-form-box">' +
        '  <div class="gp-modal-form-header"><h3>Nuevo proyecto de inversión</h3><button type="button" onclick="document.getElementById(\'gpModalOverlay\').remove()">&times;</button></div>' +
        '  <div class="gp-modal-form-body">' +
        '    <div class="gp-field gp-field-full"><label>Cliente *</label><select class="form-control" id="gpInvCliente"><option value="">Seleccione...</option><?php foreach ($clientes as $c) { echo '<option value="' . (int) $c['id'] . '">' . htmlspecialchars($c['nombre'], ENT_QUOTES) . '</option>'; } ?></select></div>' +
        '    <div class="gp-field gp-field-full"><label>Nombre del proyecto *</label><input type="text" class="form-control" id="gpInvNombre" placeholder="Ej: Proyecto de Expansión LatAm 2025" /></div>' +
        '    <div class="gp-field gp-field-full"><label>Descripción</label><input type="text" class="form-control" id="gpInvDescripcion" /></div>' +
        '  </div>' +
        '  <div class="gp-modal-form-footer"><button type="button" class="gp-modal-cancelar" onclick="document.getElementById(\'gpModalOverlay\').remove()">Cancelar</button><button type="button" class="gp-modal-confirmar" id="gpInvGuardar">Crear proyecto</button></div>' +
        '</div>';
    document.body.appendChild(div);
    document.getElementById('gpInvGuardar').onclick = function () {
        var cliente = document.getElementById('gpInvCliente').value;
        var nombre = document.getElementById('gpInvNombre').value.trim();
        if (!cliente || !nombre) { alert('Cliente y nombre del proyecto son obligatorios.'); return; }
        var body = new URLSearchParams();
        body.set('accion', 'guardar_proyecto');
        body.set('csrf_token', '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>');
        body.set('cliente_id', cliente);
        body.set('nombre', nombre);
        body.set('descripcion', document.getElementById('gpInvDescripcion').value);
        fetch('gp_inversion_ajax.php', { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) { window.location.href = 'index.php?doc=gp_inversion_proyecto&id=' + data.id; }
                else { alert(data.error || 'No se pudo crear el proyecto.'); }
            });
    };
}
</script>
<?php } ?>
