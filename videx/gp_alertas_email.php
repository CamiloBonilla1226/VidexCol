<?php
/**
 * Panel de configuracion de las alertas de vencimiento (Dominios, SSL y
 * Servicios): lista de correos destinatarios + vista previa de que items
 * estan dentro de la ventana de alerta (<=5 dias) en este momento.
 * Compartido entre Inventario de Dominios y Listado de Proveedores.
 */
require_once('gp_config.php');
require_once('gp_alertas_service.php');
gp_requerir_acceso();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$service = new GpAlertasService();
$puedeModificar = gp_puede_modificar();
$error = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['funcion'] ?? '') === 'agregar_destinatario') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Error de validación CSRF.';
    } elseif (!$puedeModificar) {
        $error = 'No tiene permiso de modificación.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if (!$service->agregarDestinatario($email, $nombre, $_SESSION['id'])) {
            $error = $service->getLastError() ?: 'No se pudo agregar el destinatario.';
        } else {
            $mensaje = 'Destinatario agregado correctamente.';
        }
    }
}

$destinatarios = $service->listarDestinatarios();
$items = $service->listarItemsEnVentanaDeAlerta();

$etiquetasTipo = ['dominio' => 'Dominio', 'ssl' => 'Certificado SSL', 'servicio' => 'Servicio'];
?>
<link rel="stylesheet" href="usuario-form.css?v=<?= @filemtime('usuario-form.css'); ?>" />
<link rel="stylesheet" href="gp.css?v=<?= @filemtime('gp.css'); ?>" />
<link rel="stylesheet" href="gp_dominios.css?v=<?= @filemtime('gp_dominios.css'); ?>" />

<div class="gp-wrap">
    <div class="gp-breadcrumb">
        <a href="index.php?doc=gp_dom_proveedores">Dominios</a>
        <i class="bi bi-chevron-right"></i>
        <span class="gp-actual">Alertas por correo</span>
    </div>

    <?php if ($mensaje) { ?><div class="alert alert-success"><?= htmlspecialchars($mensaje, ENT_QUOTES); ?></div><?php } ?>
    <?php if ($error) { ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES); ?></div><?php } ?>

    <div class="gp-page-header">
        <div>
            <h1>Alertas de vencimiento por correo</h1>
            <p class="gp-page-header-sub">
                Un correo diario, a cada destinatario activo, por cada dominio, certificado SSL o servicio que esté a
                <?= GP_ALERTAS_DIAS_ANTICIPACION; ?> días o menos de vencer. Deja de enviarse en cuanto se actualiza la fecha de vencimiento a futuro.
            </p>
        </div>
    </div>

    <div class="gp-accordion">
        <div class="gp-accordion-item gp-open" data-accordion="destinatarios">
            <div class="gp-accordion-header">
                <span class="gp-accordion-header-title"><i class="bi bi-envelope"></i> Destinatarios</span>
                <i class="bi bi-chevron-down gp-accordion-chevron"></i>
            </div>
            <div class="gp-accordion-body">
                <?php if ($puedeModificar) { ?>
                    <form method="post" class="gp-form-grid" style="margin-bottom:18px;">
                        <input type="hidden" name="funcion" value="agregar_destinatario" />
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>" />
                        <div class="gp-field">
                            <label>Correo *</label>
                            <input type="email" name="email" class="form-control" placeholder="persona@empresa.com" required />
                        </div>
                        <div class="gp-field">
                            <label>Nombre</label>
                            <input type="text" name="nombre" class="form-control" maxlength="150" placeholder="Opcional" />
                        </div>
                        <div class="gp-field" style="align-self:end;">
                            <button type="submit" class="gp-btn-primary"><i class="bi bi-plus-lg"></i> Agregar destinatario</button>
                        </div>
                    </form>
                <?php } ?>

                <?php if (empty($destinatarios)) { ?>
                    <div class="gp-table-empty"><i class="bi bi-envelope"></i><p>Aún no hay destinatarios configurados: no se enviará ninguna alerta hasta que agregues al menos uno.</p></div>
                <?php } else { ?>
                    <table class="gp-table">
                        <thead><tr><th>Correo</th><th>Nombre</th><th>Estado</th><?php if ($puedeModificar) { ?><th class="gp-th-derecha">Acciones</th><?php } ?></tr></thead>
                        <tbody>
                            <?php foreach ($destinatarios as $d) { ?>
                                <tr>
                                    <td class="gp-mono"><?= htmlspecialchars($d['email'], ENT_QUOTES); ?></td>
                                    <td><?= htmlspecialchars((string) $d['nombre'], ENT_QUOTES); ?></td>
                                    <td><span class="gp-badge <?= $d['activo'] ? 'gp-badge-verde' : 'gp-badge-rojo'; ?>"><?= $d['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <?php if ($puedeModificar) { ?>
                                        <td class="gp-col-accion">
                                            <button type="button" class="gp-icon-btn" onclick="gpaToggleDestinatario(<?= (int) $d['id']; ?>, <?= $d['activo'] ? 0 : 1; ?>)" title="<?= $d['activo'] ? 'Desactivar' : 'Activar'; ?>"><i class="bi bi-<?= $d['activo'] ? 'toggle-on' : 'toggle-off'; ?>"></i></button>
                                            <button type="button" class="gp-icon-btn gp-icon-danger" onclick="gpaEliminarDestinatario(<?= (int) $d['id']; ?>, <?= json_encode($d['email'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)" title="Eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>
        </div>

        <div class="gp-accordion-item gp-open" data-accordion="items">
            <div class="gp-accordion-header">
                <span class="gp-accordion-header-title"><i class="bi bi-exclamation-triangle"></i> Dentro de la ventana de alerta ahora mismo (<?= count($items); ?>)</span>
                <i class="bi bi-chevron-down gp-accordion-chevron"></i>
            </div>
            <div class="gp-accordion-body">
                <?php if (empty($items)) { ?>
                    <div class="gp-table-empty"><i class="bi bi-check-circle"></i><p>Nada vence en los próximos <?= GP_ALERTAS_DIAS_ANTICIPACION; ?> días. No se enviará ninguna alerta hoy.</p></div>
                <?php } else { ?>
                    <table class="gp-table">
                        <thead><tr><th>Tipo</th><th>Elemento</th><th>Proveedor</th><th>Vencimiento</th></tr></thead>
                        <tbody>
                            <?php foreach ($items as $it) { ?>
                                <tr>
                                    <td><span class="gp-badge gp-badge-gris"><?= $etiquetasTipo[$it['tipo']] ?? $it['tipo']; ?></span></td>
                                    <td class="gp-mono"><?= htmlspecialchars($it['etiqueta'], ENT_QUOTES); ?></td>
                                    <td><?= htmlspecialchars((string) $it['contexto'], ENT_QUOTES); ?></td>
                                    <td><?= gp_badge_vencimiento($it['vencimiento']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
                <p class="gp-pass-hint" style="margin-top:12px;">Esta lista es informativa: el envío real lo hace el cron diario (gp_alertas_cron.php) en el servidor.</p>
            </div>
        </div>
    </div>
</div>

<?php if ($puedeModificar) { ?>
<script>
var GPA_CSRF = <?= json_encode($_SESSION['csrf_token']); ?>;

function gpaPost(datos) {
    var body = new URLSearchParams();
    body.set('csrf_token', GPA_CSRF);
    Object.keys(datos).forEach(function (k) { body.set(k, datos[k]); });
    return fetch('gp_alertas_ajax.php', { method: 'POST', body: body, credentials: 'same-origin' })
        .then(function (r) { return r.json().catch(function () { return { error: 'Respuesta inválida del servidor.' }; }); });
}

function gpaToggleDestinatario(id, nuevoActivo) {
    gpaPost({ accion: 'toggle_destinatario', id: id, activo: nuevoActivo }).then(function (data) {
        if (data.ok) { location.reload(); } else { alert(data.error || 'No se pudo actualizar.'); }
    });
}

function gpaEliminarDestinatario(id, email) {
    if (!confirm('¿Eliminar el destinatario "' + email + '"? Dejará de recibir alertas.')) return;
    gpaPost({ accion: 'eliminar_destinatario', id: id }).then(function (data) {
        if (data.ok) { location.reload(); } else { alert(data.error || 'No se pudo eliminar.'); }
    });
}
</script>
<?php } ?>

<script>
(function () {
    document.querySelectorAll('.gp-accordion-item').forEach(function (item) {
        item.querySelector('.gp-accordion-header').addEventListener('click', function () {
            item.classList.toggle('gp-open');
        });
    });
})();
</script>
