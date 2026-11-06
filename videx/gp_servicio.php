<?php
require_once('gp_config.php');
require_once('gp_service.php');
gp_requerir_acceso();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$service = new GpService();

$opc = isset($_GET['opc']) ? (int) $_GET['opc'] : 1;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$proveedorId = isset($_GET['proveedor_id']) ? (int) $_GET['proveedor_id'] : 0;
$esEdicion = ($id > 0);
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['funcion'] ?? '') === 'guardar_datos') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Error de validación CSRF.';
    } elseif (!gp_puede_modificar()) {
        $error = 'No tiene permiso de modificación.';
    } else {
        $idPost = (int) ($_POST['id'] ?? 0);
        $vencimiento = trim($_POST['fecha_vencimiento'] ?? '');
        $data = [
            'tipo' => $_POST['tipo'] ?? GP_TIPO_HOSTING,
            'nombre' => trim($_POST['nombre'] ?? ''),
            'host_ip' => trim($_POST['host_ip'] ?? ''),
            'proveedor_id' => (int) ($_POST['proveedor_id'] ?? 0),
            'fecha_vencimiento' => $vencimiento, // opcional: vacio = sin vencimiento, sin alertas
        ];
        if ($data['nombre'] === '' || $data['proveedor_id'] <= 0) {
            $error = 'El nombre del servicio es obligatorio.';
        } elseif ($vencimiento !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimiento)) {
            $error = 'La fecha de vencimiento no es válida.';
        } elseif ($idPost > 0) {
            $service->actualizarServicio($idPost, $data, $_SESSION['id']);
            $id = $idPost;
            $esEdicion = true;
            $mensaje = 'Servicio actualizado correctamente.';
        } else {
            $nuevoId = $service->crearServicio($data, $_SESSION['id']);
            if ($nuevoId) {
                // Ver comentario equivalente en gp_proveedor.php: hay que
                // redirigir a la URL real de edicion para que un reload
                // posterior no vuelva a crear otro servicio duplicado.
                echo '<script>window.location.replace("index.php?doc=gp_servicio&opc=2&id=' . (int) $nuevoId . '");</script>';
                return;
            } else {
                $error = 'No se pudo crear el servicio.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['funcion'] ?? '') === 'eliminar_servicio') {
    $idEliminar = (int) ($_POST['id'] ?? 0);
    $servicioEliminar = $idEliminar > 0 ? $service->getServicio($idEliminar) : null;
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Error de validación CSRF.';
    } elseif (!gp_puede_eliminar_proveedores()) {
        // Mismo permiso especial que eliminar proveedores (Usuario > Acceso), consultado en vivo en la BD.
        $error = 'No tiene el permiso especial para eliminar en Gestión Proyectos.';
    } elseif (!$servicioEliminar) {
        $error = 'Servicio no encontrado.';
    } elseif (mb_strtolower(trim((string) ($_POST['confirmar_nombre'] ?? ''))) !== mb_strtolower(trim($servicioEliminar['nombre']))) {
        $error = 'El nombre escrito no coincide: no se eliminó el servicio.';
    } elseif ($service->eliminarServicio($idEliminar, $_SESSION['id'])) {
        echo '<script>window.location.replace("index.php?doc=gp_proveedor&opc=2&id=' . (int) $servicioEliminar['proveedor_id'] . '&eliminado=1");</script>';
        return;
    } else {
        $error = 'No se pudo eliminar el servicio.';
    }
    $id = $idEliminar;
    $esEdicion = ($id > 0);
}

$servicio = $esEdicion ? $service->getServicio($id) : null;
if ($esEdicion && !$servicio) {
    echo '<div class="gp-wrap"><p>Servicio no encontrado.</p></div>';
    return;
}
if ($esEdicion) {
    $proveedorId = (int) $servicio['proveedor_id'];
}
$proveedor = $service->getProveedor($proveedorId);
if (!$proveedor) {
    echo '<div class="gp-wrap"><p>Proveedor no encontrado.</p></div>';
    return;
}

$puedeModificar = gp_puede_modificar();
$puedeEliminar = $esEdicion && gp_puede_eliminar_proveedores();
$resumenEliminacion = $puedeEliminar ? $service->resumenEliminacionServicio($id) : null;
$credenciales = $esEdicion ? $service->listarCredencialesServicio($id) : [];
$dominios = $esEdicion ? $service->listarDominiosPorServicio($id) : [];
$proyectos = $esEdicion ? $service->listarProyectosPorServicio($id) : [];
?>
<link rel="stylesheet" href="usuario-form.css?v=<?= @filemtime('usuario-form.css'); ?>" />
<link rel="stylesheet" href="gp.css?v=<?= @filemtime('gp.css'); ?>" />
<link rel="stylesheet" href="gp_dominios.css?v=<?= @filemtime('gp_dominios.css'); ?>" />
<script src="js/gp.js?v=<?= @filemtime('js/gp.js'); ?>"></script>

<div class="gp-wrap">
    <div class="gp-breadcrumb">
        <a href="index.php?doc=gp_proveedores">Proveedores</a>
        <i class="bi bi-chevron-right"></i>
        <a href="index.php?doc=gp_proveedor&opc=2&id=<?= $proveedorId; ?>"><?= htmlspecialchars($proveedor['nombre'], ENT_QUOTES); ?></a>
        <i class="bi bi-chevron-right"></i>
        <span class="gp-actual"><?= $esEdicion ? htmlspecialchars($servicio['nombre'], ENT_QUOTES) : 'Nuevo servicio'; ?></span>
    </div>

    <?php if ($mensaje) { ?><div class="alert alert-success"><?= htmlspecialchars($mensaje, ENT_QUOTES); ?></div><?php } ?>
    <?php if ($error) { ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES); ?></div><?php } ?>
    <?php if (!$puedeModificar) { ?>
        <div class="gp-solo-consulta-aviso"><i class="bi bi-eye"></i> Modo solo consulta: no tiene permiso de modificación en Gestión Proyectos.</div>
    <?php } ?>

    <div class="gp-page-header">
        <div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <h1 style="margin:0;"><?= $esEdicion ? htmlspecialchars($servicio['nombre'], ENT_QUOTES) : 'Nuevo servicio'; ?></h1>
                <?php if ($esEdicion) { ?>
                    <span class="gp-badge <?= $servicio['tipo'] === 'VPS' ? 'gp-badge-tipo-vps' : 'gp-badge-tipo-hosting'; ?>"><?= htmlspecialchars($servicio['tipo'], ENT_QUOTES); ?></span>
                <?php } ?>
            </div>
            <p class="gp-page-header-sub">Proveedor: <b><?= htmlspecialchars($proveedor['nombre'], ENT_QUOTES); ?></b></p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <?php if ($esEdicion) { ?><span class="gp-badge-estado">Activo</span><?php } ?>
            <?php if ($puedeEliminar) { ?>
                <button type="button" class="gp-btn-peligro" id="gpBtnEliminarServicio"><i class="bi bi-trash"></i> Eliminar servicio</button>
            <?php } ?>
        </div>
    </div>

    <?php if ($esEdicion) { ?>
        <div class="gp-kpi-grid">
            <div class="gp-kpi-card">
                <div>
                    <p class="gp-kpi-label">Contraseñas</p>
                    <div class="gp-kpi-value"><?= count($credenciales); ?></div>
                </div>
                <div class="gp-kpi-icon"><i class="bi bi-lock"></i></div>
            </div>
            <div class="gp-kpi-card">
                <div>
                    <p class="gp-kpi-label">Dominios</p>
                    <div class="gp-kpi-value"><?= count($dominios); ?></div>
                </div>
                <div class="gp-kpi-icon"><i class="bi bi-globe"></i></div>
            </div>
            <div class="gp-kpi-card">
                <div>
                    <p class="gp-kpi-label">Proyectos</p>
                    <div class="gp-kpi-value"><?= count($proyectos); ?></div>
                </div>
                <div class="gp-kpi-icon"><i class="bi bi-kanban"></i></div>
            </div>
        </div>
    <?php } ?>

    <div class="gp-accordion">
        <div class="gp-accordion-item gp-open" data-accordion="datos">
            <div class="gp-accordion-header">
                <span class="gp-accordion-header-title"><i class="bi bi-info-circle"></i> Datos del servicio</span>
                <i class="bi bi-chevron-down gp-accordion-chevron"></i>
            </div>
            <div class="gp-accordion-body">
                <form method="post">
                    <input type="hidden" name="funcion" value="guardar_datos" />
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>" />
                    <input type="hidden" name="id" value="<?= $id; ?>" />
                    <input type="hidden" name="proveedor_id" value="<?= $proveedorId; ?>" />
                    <div class="gp-form-grid">
                        <div class="gp-field">
                            <label>Tipo *</label>
                            <?php if ($puedeModificar) { ?>
                                <select name="tipo" class="form-control">
                                    <?php foreach (GP_TIPOS_SERVICIO as $k => $v) { ?>
                                        <option value="<?= $k; ?>" <?= ($servicio['tipo'] ?? GP_TIPO_HOSTING) === $k ? 'selected' : ''; ?>><?= $v; ?></option>
                                    <?php } ?>
                                </select>
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= GP_TIPOS_SERVICIO[$servicio['tipo']] ?? ''; ?></div>
                            <?php } ?>
                        </div>
                        <div class="gp-field">
                            <label>Nombre *</label>
                            <?php if ($puedeModificar) { ?>
                                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($servicio['nombre'] ?? '', ENT_QUOTES); ?>" required />
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= htmlspecialchars($servicio['nombre'] ?? '', ENT_QUOTES); ?></div>
                            <?php } ?>
                        </div>
                        <div class="gp-field gp-field-full" style="grid-column:1/-1;">
                            <label>Host / IP</label>
                            <?php if ($puedeModificar) { ?>
                                <input type="text" name="host_ip" class="form-control" value="<?= htmlspecialchars($servicio['host_ip'] ?? '', ENT_QUOTES); ?>" />
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= htmlspecialchars($servicio['host_ip'] ?? '', ENT_QUOTES); ?></div>
                            <?php } ?>
                        </div>
                        <div class="gp-field">
                            <label>Fecha de vencimiento <span style="font-weight:400;color:#94a3b8;">(opcional)</span></label>
                            <?php if ($puedeModificar) { ?>
                                <input type="date" name="fecha_vencimiento" class="form-control" value="<?= htmlspecialchars((string) ($servicio['fecha_vencimiento'] ?? ''), ENT_QUOTES); ?>" />
                                <p class="gp-pass-hint">Si se informa, se enviará una alerta diaria por correo desde 5 días antes de vencer (configurable en Dominios → Configurar alertas por correo).</p>
                            <?php } elseif (!empty($servicio['fecha_vencimiento'])) { ?>
                                <div class="gp-field-readonly"><?= gp_badge_vencimiento($servicio['fecha_vencimiento']); ?></div>
                            <?php } else { ?>
                                <div class="gp-field-readonly">Sin vencimiento configurado.</div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php if ($puedeModificar) { ?>
                        <div class="gp-form-actions">
                            <a href="index.php?doc=gp_proveedor&opc=2&id=<?= $proveedorId; ?>" class="gp-btn-secondary">Cancelar</a>
                            <button type="submit" class="gp-btn-primary"><i class="bi bi-check-lg"></i> <?= $esEdicion ? 'Guardar cambios' : 'Crear servicio'; ?></button>
                        </div>
                    <?php } ?>
                </form>
            </div>
        </div>

        <?php if ($esEdicion) { ?>
            <div class="gp-accordion-item" data-accordion="contrasenas">
                <div class="gp-accordion-header">
                    <span class="gp-accordion-header-title"><i class="bi bi-lock"></i> Contraseñas</span>
                    <div class="gp-accordion-header-right">
                        <?php if ($puedeModificar) { ?>
                            <button type="button" class="gp-btn-primary" onclick="event.stopPropagation(); gpAbrirModalCredencialServicio(0)"><i class="bi bi-plus-lg"></i> Agregar credencial</button>
                        <?php } ?>
                        <i class="bi bi-chevron-down gp-accordion-chevron"></i>
                    </div>
                </div>
                <div class="gp-accordion-body">
                    <?php if (empty($credenciales)) { ?>
                        <div class="gp-table-empty"><i class="bi bi-key"></i><p>No hay credenciales registradas para este servicio.</p></div>
                    <?php } else { ?>
                        <table class="gp-table">
                            <thead><tr><th>Tipo</th><th>Usuario</th><th>Contraseña</th><th>Host</th><th>Puerto</th><th>Destino</th><th class="gp-th-derecha">Acciones</th></tr></thead>
                            <tbody>
                                <?php foreach ($credenciales as $c) { ?>
                                    <tr>
                                        <td><span class="gp-badge gp-badge-gris"><?= htmlspecialchars($c['tipo'], ENT_QUOTES); ?></span></td>
                                        <td class="gp-mono"><?= htmlspecialchars($c['user'], ENT_QUOTES); ?></td>
                                        <td>
                                            <div class="gp-pass-mask" id="gpPassSCred<?= (int) $c['id']; ?>">
                                                <span class="gp-pass-dots">••••••••</span>
                                                <?php if (gp_puede_ver_credenciales()) { ?>
                                                    <button type="button" class="gp-icon-btn gp-btn-revelar" data-tipo="servicio_credencial" data-id="<?= (int) $c['id']; ?>" data-destino="gpPassSCred<?= (int) $c['id']; ?>" title="Ver contraseña"><i class="bi bi-eye"></i></button>
                                                <?php } ?>
                                            </div>
                                        </td>
                                        <td class="gp-mono"><?= htmlspecialchars($c['host'], ENT_QUOTES); ?></td>
                                        <td class="gp-mono"><?= htmlspecialchars($c['puerto'], ENT_QUOTES); ?></td>
                                        <td class="gp-mono"><?= htmlspecialchars($c['destino'], ENT_QUOTES); ?></td>
                                        <td class="gp-col-accion">
                                            <?php if ($puedeModificar) { ?>
                                                <button type="button" class="gp-icon-btn" onclick='gpAbrirModalCredencialServicio(<?= (int) $c['id']; ?>, <?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Editar"><i class="bi bi-pencil"></i></button>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <div class="gp-card-footer">Mostrando <?= count($credenciales); ?> credencial<?= count($credenciales) == 1 ? '' : 'es'; ?></div>
                    <?php } ?>
                </div>
            </div>

            <div class="gp-accordion-item" data-accordion="dominios">
                <div class="gp-accordion-header">
                    <span class="gp-accordion-header-title"><i class="bi bi-globe"></i> Dominios</span>
                    <div class="gp-accordion-header-right">
                        <?php if ($puedeModificar) { ?>
                            <button type="button" class="gp-btn-primary" onclick="event.stopPropagation(); gpAbrirModalDominio(0)"><i class="bi bi-plus-lg"></i> Agregar dominio</button>
                        <?php } ?>
                        <i class="bi bi-chevron-down gp-accordion-chevron"></i>
                    </div>
                </div>
                <div class="gp-accordion-body">
                    <?php if (empty($dominios)) { ?>
                        <div class="gp-table-empty"><i class="bi bi-globe"></i><p>No hay dominios registrados para este servicio.</p></div>
                    <?php } else { ?>
                        <table class="gp-table">
                            <thead><tr><th>Dominio / Subdominio</th><th>Descripción</th><th>Carpeta destino</th><th>Proyecto relacionado</th><th>Estado</th><th class="gp-th-derecha">Acciones</th></tr></thead>
                            <tbody>
                                <?php foreach ($dominios as $d) { ?>
                                    <tr>
                                        <td class="gp-mono"><?= htmlspecialchars($d['dominio'], ENT_QUOTES); ?></td>
                                        <td><?= $d['descripcion'] ? htmlspecialchars($d['descripcion'], ENT_QUOTES) : '<span style="color:#94a3b8;">—</span>'; ?></td>
                                        <td class="gp-mono"><?= htmlspecialchars($d['carpeta_destino'], ENT_QUOTES); ?></td>
                                        <td><?= $d['proyecto_nombre'] ? htmlspecialchars($d['proyecto_nombre'], ENT_QUOTES) : '<span style="color:#94a3b8;">—</span>'; ?></td>
                                        <td><span class="gp-badge <?= $d['estado'] ? 'gp-badge-verde' : 'gp-badge-rojo'; ?>"><?= $d['estado'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                        <td class="gp-col-accion">
                                            <?php if ($puedeModificar) { ?>
                                                <button type="button" class="gp-icon-btn" onclick='gpAbrirModalDominio(<?= (int) $d['id']; ?>, <?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Editar"><i class="bi bi-pencil"></i></button>
                                                <button type="button" class="gp-icon-btn gp-icon-danger" onclick='gpEliminarDominio(<?= (int) $d['id']; ?>, <?= json_encode($d['dominio'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP); ?>)' title="Eliminar"><i class="bi bi-trash"></i></button>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <div class="gp-card-footer">Mostrando <?= count($dominios); ?> dominio<?= count($dominios) == 1 ? '' : 's'; ?></div>
                    <?php } ?>
                </div>
            </div>

            <div class="gp-accordion-item" data-accordion="proyectos">
                <div class="gp-accordion-header">
                    <span class="gp-accordion-header-title"><i class="bi bi-kanban"></i> Proyectos</span>
                    <div class="gp-accordion-header-right">
                        <?php if ($puedeModificar) { ?>
                            <a href="index.php?doc=gp_proyecto&opc=1&servicio_id=<?= $id; ?>" class="gp-btn-primary" onclick="event.stopPropagation();"><i class="bi bi-plus-lg"></i> Agregar proyecto</a>
                        <?php } ?>
                        <i class="bi bi-chevron-down gp-accordion-chevron"></i>
                    </div>
                </div>
                <div class="gp-accordion-body">
                    <?php if (empty($proyectos)) { ?>
                        <div class="gp-table-empty"><i class="bi bi-kanban"></i><p>No hay proyectos registrados para este servicio.</p></div>
                    <?php } else { ?>
                        <div class="gp-mini-card-grid">
                            <?php foreach ($proyectos as $p) { ?>
                                <a href="index.php?doc=gp_proyecto&opc=2&id=<?= (int) $p['id']; ?>" class="gp-mini-card" style="text-decoration:none;">
                                    <div class="gp-mini-card-top">
                                        <span class="gp-badge <?= $p['estado'] ? 'gp-badge-verde' : 'gp-badge-rojo'; ?>"><?= $p['estado'] ? 'Activo' : 'Inactivo'; ?></span>
                                        <span class="gp-badge gp-badge-gris"><i class="bi bi-key"></i> <?= (int) $p['total_credenciales']; ?></span>
                                    </div>
                                    <div class="gp-mini-card-nombre"><?= htmlspecialchars($p['nombre'], ENT_QUOTES); ?></div>
                                    <div class="gp-mini-card-sub"><?= htmlspecialchars($p['descripcion'], ENT_QUOTES); ?></div>
                                    <div class="gp-mini-card-footer">
                                        <span class="gp-btn-secondary" style="padding:4px 12px;font-size:12px;">Ver <i class="bi bi-arrow-right"></i></span>
                                    </div>
                                </a>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<?php if ($puedeEliminar) { ?>
<div class="gp-modal-overlay" id="gpModalEliminarServicio">
    <div class="gp-modal-form-box">
        <div class="gp-modal-form-header"><h3 style="color:#b91c1c;"><i class="bi bi-exclamation-triangle"></i> Eliminar servicio</h3><button type="button" id="gpElimServCerrar">&times;</button></div>
        <form method="post" id="gpFormEliminarServicio">
            <input type="hidden" name="funcion" value="eliminar_servicio" />
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>" />
            <input type="hidden" name="id" value="<?= (int) $id; ?>" />
            <div style="padding:18px 22px;font-size:13.5px;color:#334155;">
                <p style="margin:0 0 10px;">Se eliminará <strong><?= htmlspecialchars($servicio['nombre'] ?? '', ENT_QUOTES); ?></strong> y, junto con él, todo lo que tiene registrado:</p>
                <ul style="margin:0 0 12px 18px;padding:0;">
                    <li><strong><?= (int) $resumenEliminacion['proyectos']; ?></strong> proyecto(s)</li>
                    <li><strong><?= (int) $resumenEliminacion['dominios']; ?></strong> dominio(s)</li>
                    <li><strong><?= (int) $resumenEliminacion['credenciales']; ?></strong> credencial(es)</li>
                </ul>
                <?php if ((int) $resumenEliminacion['tickets'] > 0) { ?>
                    <p style="margin:0 0 12px;color:#b45309;"><i class="bi bi-ticket-detailed"></i> Hay <strong><?= (int) $resumenEliminacion['tickets']; ?></strong> ticket(s) ligados a sus proyectos: se conservan, pero quedarán sin proyecto seleccionable.</p>
                <?php } ?>
                <p style="margin:0 0 6px;">Para confirmar, escriba el nombre del servicio:</p>
                <input type="text" name="confirmar_nombre" id="gpElimServNombre" class="form-control" autocomplete="off" placeholder="<?= htmlspecialchars($servicio['nombre'] ?? '', ENT_QUOTES); ?>" />
                <p style="margin:10px 0 0;font-size:12px;color:#94a3b8;">No se elimina el proveedor; deja de mostrarse en el sistema, los datos no se borran físicamente.</p>
            </div>
            <div class="gp-modal-form-footer">
                <button type="button" class="gp-modal-cancelar" id="gpElimServCancelar">Cancelar</button>
                <button type="submit" class="gp-modal-confirmar gp-btn-peligro-solido" id="gpElimServConfirmar" disabled>Eliminar definitivamente</button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    var overlay = document.getElementById('gpModalEliminarServicio');
    var nombre = <?= json_encode(mb_strtolower(trim($servicio['nombre'] ?? ''))); ?>;
    var input = document.getElementById('gpElimServNombre');
    var boton = document.getElementById('gpElimServConfirmar');
    document.getElementById('gpBtnEliminarServicio').addEventListener('click', function () {
        overlay.classList.add('gp-open'); input.value = ''; boton.disabled = true; setTimeout(function () { input.focus(); }, 50);
    });
    var cerrar = function () { overlay.classList.remove('gp-open'); };
    document.getElementById('gpElimServCerrar').onclick = cerrar;
    document.getElementById('gpElimServCancelar').onclick = cerrar;
    input.addEventListener('input', function () { boton.disabled = input.value.trim().toLowerCase() !== nombre; });
})();
</script>
<?php } ?>

<?php if ($puedeModificar && $esEdicion) { ?>
<script>
function gpAbrirModalCredencialServicio(id, datos) {
    datos = datos || {};
    var overlay = document.getElementById('gpModalOverlay');
    if (overlay) overlay.remove();
    var div = document.createElement('div');
    div.className = 'gp-modal-overlay gp-open';
    div.id = 'gpModalOverlay';
    div.innerHTML =
        '<div class="gp-modal-form-box">' +
        '  <div class="gp-modal-form-header"><h3>' + (id ? 'Editar credencial' : 'Agregar credencial') + '</h3><button type="button" onclick="document.getElementById(\'gpModalOverlay\').remove()">&times;</button></div>' +
        '  <div class="gp-modal-form-body">' +
        '    <div class="gp-field gp-field-full"><label>Tipo *</label><select class="form-control" id="gpCredTipo"><?php foreach (GP_TIPOS_CREDENCIAL as $k => $v) { echo '<option value="' . $k . '">' . $v . '</option>'; } ?></select></div>' +
        '    <div class="gp-field"><label>Usuario</label><input type="text" class="form-control" id="gpCredUser" /></div>' +
        '    <div class="gp-field"><label>Contraseña</label><input type="password" class="form-control" id="gpCredPass" placeholder="' + (id ? 'Dejar en blanco para no cambiarla' : '') + '" /></div>' +
        '    <div class="gp-field"><label>Host</label><input type="text" class="form-control" id="gpCredHost" /></div>' +
        '    <div class="gp-field"><label>Puerto</label><input type="text" class="form-control" id="gpCredPuerto" /></div>' +
        '    <div class="gp-field gp-field-full"><label>Destino</label><input type="text" class="form-control" id="gpCredDestino" /></div>' +
        '  </div>' +
        '  <div class="gp-modal-form-footer"><button type="button" class="gp-modal-cancelar" onclick="document.getElementById(\'gpModalOverlay\').remove()">Cancelar</button><button type="button" class="gp-modal-confirmar" id="gpCredGuardar">Guardar</button></div>' +
        '</div>';
    document.body.appendChild(div);
    if (datos.tipo) document.getElementById('gpCredTipo').value = datos.tipo;
    document.getElementById('gpCredUser').value = datos.user || '';
    document.getElementById('gpCredHost').value = datos.host || '';
    document.getElementById('gpCredPuerto').value = datos.puerto || '';
    document.getElementById('gpCredDestino').value = datos.destino || '';
    document.getElementById('gpCredGuardar').onclick = function () {
        var body = new URLSearchParams();
        body.set('accion', 'guardar');
        body.set('padre', 'servicio');
        body.set('padre_id', '<?= $id; ?>');
        body.set('id', id);
        body.set('tipo', document.getElementById('gpCredTipo').value);
        body.set('user', document.getElementById('gpCredUser').value);
        body.set('pass', document.getElementById('gpCredPass').value);
        body.set('host', document.getElementById('gpCredHost').value);
        body.set('puerto', document.getElementById('gpCredPuerto').value);
        body.set('destino', document.getElementById('gpCredDestino').value);
        fetch('gp_credencial_ajax.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) { sessionStorage.setItem('gpAccordionAbierto', 'contrasenas'); location.reload(); }
                else { alert(data.error || 'No se pudo guardar.'); }
            });
    };
}

function gpAbrirModalDominio(id, datos) {
    datos = datos || {};
    var overlay = document.getElementById('gpModalOverlay');
    if (overlay) overlay.remove();
    var div = document.createElement('div');
    div.className = 'gp-modal-overlay gp-open';
    div.id = 'gpModalOverlay';
    var opciones = <?= json_encode(array_map(function ($p) { return ['id' => $p['id'], 'nombre' => $p['nombre']]; }, $proyectos)); ?>;
    var selectHtml = '<option value="">Sin proyecto relacionado</option>';
    opciones.forEach(function (o) { selectHtml += '<option value="' + o.id + '">' + gpEscapeHtml(o.nombre) + '</option>'; });
    div.innerHTML =
        '<div class="gp-modal-form-box">' +
        '  <div class="gp-modal-form-header"><h3>' + (id ? 'Editar dominio' : 'Agregar dominio') + '</h3><button type="button" onclick="document.getElementById(\'gpModalOverlay\').remove()">&times;</button></div>' +
        '  <div class="gp-modal-form-body">' +
        '    <div class="gp-field gp-field-full"><label>Dominio / Subdominio *</label><input type="text" class="form-control" id="gpDomDominio" placeholder="ej: app.miempresa.com" /></div>' +
        '    <div class="gp-field gp-field-full"><label>Descripción</label><input type="text" class="form-control" id="gpDomDescripcion" placeholder="Opcional" /></div>' +
        '    <div class="gp-field gp-field-full"><label>Carpeta destino</label><input type="text" class="form-control" id="gpDomCarpeta" placeholder="ej: /public_html/app" /></div>' +
        '    <div class="gp-field gp-field-full"><label>Proyecto relacionado</label><select class="form-control" id="gpDomProyecto">' + selectHtml + '</select></div>' +
        '    <div class="gp-field gp-field-full"><label>Estado</label><select class="form-control" id="gpDomEstado"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>' +
        '  </div>' +
        '  <div class="gp-modal-form-footer"><button type="button" class="gp-modal-cancelar" onclick="document.getElementById(\'gpModalOverlay\').remove()">Cancelar</button><button type="button" class="gp-modal-confirmar" id="gpDomGuardar">Guardar</button></div>' +
        '</div>';
    document.body.appendChild(div);
    document.getElementById('gpDomDominio').value = datos.dominio || '';
    document.getElementById('gpDomDescripcion').value = datos.descripcion || '';
    document.getElementById('gpDomCarpeta').value = datos.carpeta_destino || '';
    if (datos.proyecto_id) document.getElementById('gpDomProyecto').value = datos.proyecto_id;
    document.getElementById('gpDomEstado').value = (id && datos.estado == '0') ? '0' : '1';
    document.getElementById('gpDomGuardar').onclick = function () {
        var body = new URLSearchParams();
        body.set('accion', 'guardar');
        body.set('servicio_id', '<?= $id; ?>');
        body.set('id', id);
        body.set('dominio', document.getElementById('gpDomDominio').value);
        body.set('descripcion', document.getElementById('gpDomDescripcion').value);
        body.set('carpeta_destino', document.getElementById('gpDomCarpeta').value);
        body.set('proyecto_id', document.getElementById('gpDomProyecto').value);
        body.set('estado', document.getElementById('gpDomEstado').value);
        fetch('gp_dominio_ajax.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) { sessionStorage.setItem('gpAccordionAbierto', 'dominios'); location.reload(); }
                else { alert(data.error || 'No se pudo guardar.'); }
            });
    };
}

function gpEliminarDominio(id, nombre) {
    if (!confirm('¿Eliminar el dominio "' + nombre + '"?')) return;
    var body = new URLSearchParams();
    body.set('accion', 'eliminar');
    body.set('id', id);
    fetch('gp_dominio_ajax.php', { method: 'POST', body: body })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) { sessionStorage.setItem('gpAccordionAbierto', 'dominios'); location.reload(); }
            else { alert(data.error || 'No se pudo eliminar.'); }
        });
}
</script>
<?php } ?>

<script>
(function () {
    var items = document.querySelectorAll('.gp-accordion-item');
    var abiertoGuardado = sessionStorage.getItem('gpAccordionAbierto');
    if (abiertoGuardado) {
        sessionStorage.removeItem('gpAccordionAbierto');
        items.forEach(function (item) {
            item.classList.toggle('gp-open', item.getAttribute('data-accordion') === abiertoGuardado);
        });
    }
    items.forEach(function (item) {
        var header = item.querySelector('.gp-accordion-header');
        header.addEventListener('click', function () {
            item.classList.toggle('gp-open');
        });
    });
})();
</script>
