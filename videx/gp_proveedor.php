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
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'host' => trim($_POST['host'] ?? ''),
            'user' => trim($_POST['user'] ?? ''),
            'pass' => $_POST['pass'] ?? '',
        ];
        if ($data['nombre'] === '') {
            $error = 'El nombre del proveedor es obligatorio.';
        } elseif ($idPost > 0) {
            $service->actualizarProveedor($idPost, $data, $_SESSION['id']);
            $id = $idPost;
            $esEdicion = true;
            $mensaje = 'Proveedor actualizado correctamente.';
        } else {
            $nuevoId = $service->crearProveedor($data, $_SESSION['id']);
            if ($nuevoId) {
                /*
                 * Redirigimos a la URL de edicion real (opc=2&id=X). Si nos
                 * quedaramos en la URL de creacion, cualquier location.reload()
                 * disparado despues (ej. al guardar una credencial) volveria a
                 * crear OTRO proveedor en vez de mostrar el que ya existe.
                 */
                echo '<script>window.location.replace("index.php?doc=gp_proveedor&opc=2&id=' . (int) $nuevoId . '");</script>';
                return;
            } else {
                $error = 'No se pudo crear el proveedor.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['funcion'] ?? '') === 'eliminar_proveedor') {
    $idEliminar = (int) ($_POST['id'] ?? 0);
    $proveedorEliminar = $idEliminar > 0 ? $service->getProveedor($idEliminar) : null;
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Error de validación CSRF.';
    } elseif (!gp_puede_eliminar_proveedores()) {
        // Permiso especial (Usuario > Acceso), consultado en vivo en la BD.
        $error = 'No tiene el permiso especial para eliminar proveedores.';
    } elseif (!$proveedorEliminar) {
        $error = 'Proveedor no encontrado.';
    } elseif (mb_strtolower(trim((string) ($_POST['confirmar_nombre'] ?? ''))) !== mb_strtolower(trim($proveedorEliminar['nombre']))) {
        $error = 'El nombre escrito no coincide: no se eliminó el proveedor.';
    } elseif ($service->eliminarProveedor($idEliminar, $_SESSION['id'])) {
        echo '<script>window.location.replace("index.php?doc=gp_proveedores&eliminado=1");</script>';
        return;
    } else {
        $error = 'No se pudo eliminar el proveedor.';
    }
    $id = $idEliminar;
    $esEdicion = ($id > 0);
}

$proveedor = $esEdicion ? $service->getProveedor($id) : null;
if ($esEdicion && !$proveedor) {
    echo '<div class="gp-wrap"><p>Proveedor no encontrado.</p></div>';
    return;
}

$puedeModificar = gp_puede_modificar();
$puedeEliminar = $esEdicion && gp_puede_eliminar_proveedores();
$resumenEliminacion = $puedeEliminar ? $service->resumenEliminacionProveedor($id) : null;
$servicios = $esEdicion ? $service->listarServiciosPorProveedor($id) : [];
$credenciales = $esEdicion ? $service->listarCredencialesProveedor($id) : [];
?>
<link rel="stylesheet" href="usuario-form.css?v=<?= @filemtime('usuario-form.css'); ?>" />
<link rel="stylesheet" href="gp.css?v=<?= @filemtime('gp.css'); ?>" />
<link rel="stylesheet" href="gp_dominios.css?v=<?= @filemtime('gp_dominios.css'); ?>" />
<script src="js/gp.js?v=<?= @filemtime('js/gp.js'); ?>"></script>

<div class="gp-wrap">
    <div class="gp-breadcrumb">
        <a href="index.php?doc=gp_proveedores">Proveedores</a>
        <i class="bi bi-chevron-right"></i>
        <span class="gp-actual"><?= $esEdicion ? htmlspecialchars($proveedor['nombre'], ENT_QUOTES) : 'Nuevo proveedor'; ?></span>
    </div>

    <?php if ($mensaje) { ?><div class="alert alert-success"><?= htmlspecialchars($mensaje, ENT_QUOTES); ?></div><?php } ?>
    <?php if ($error) { ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES); ?></div><?php } ?>
    <?php if (!$puedeModificar) { ?>
        <div class="gp-solo-consulta-aviso"><i class="bi bi-eye"></i> Modo solo consulta: no tiene permiso de modificación en Gestión Proyectos.</div>
    <?php } ?>

    <div class="gp-page-header">
        <div>
            <h1><?= $esEdicion ? htmlspecialchars($proveedor['nombre'], ENT_QUOTES) : 'Nuevo proveedor'; ?></h1>
            <p class="gp-page-header-sub">Detalle de proveedor</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <?php if ($esEdicion) { ?><span class="gp-badge-estado">Activo</span><?php } ?>
            <?php if ($puedeEliminar) { ?>
                <button type="button" class="gp-btn-peligro" id="gpBtnEliminarProveedor"><i class="bi bi-trash"></i> Eliminar proveedor</button>
            <?php } ?>
        </div>
    </div>

    <?php if ($esEdicion) { ?>
        <div class="gp-kpi-grid">
            <div class="gp-kpi-card">
                <div>
                    <p class="gp-kpi-label">Servicios</p>
                    <div class="gp-kpi-value"><?= (int) $proveedor['total_servicios']; ?></div>
                </div>
                <div class="gp-kpi-icon"><i class="bi bi-hdd-stack"></i></div>
            </div>
            <div class="gp-kpi-card">
                <div>
                    <p class="gp-kpi-label">Proyectos</p>
                    <div class="gp-kpi-value"><?= (int) $proveedor['total_proyectos']; ?></div>
                </div>
                <div class="gp-kpi-icon"><i class="bi bi-kanban"></i></div>
            </div>
            <div class="gp-kpi-card">
                <div>
                    <p class="gp-kpi-label">Credenciales</p>
                    <div class="gp-kpi-value"><?= (int) $proveedor['total_credenciales']; ?></div>
                </div>
                <div class="gp-kpi-icon"><i class="bi bi-key"></i></div>
            </div>
        </div>
    <?php } ?>

    <div class="gp-accordion">
        <div class="gp-accordion-item gp-open" data-accordion="datos">
            <div class="gp-accordion-header">
                <span class="gp-accordion-header-title"><i class="bi bi-info-circle"></i> Datos del proveedor</span>
                <i class="bi bi-chevron-down gp-accordion-chevron"></i>
            </div>
            <div class="gp-accordion-body">
                <form method="post">
                    <input type="hidden" name="funcion" value="guardar_datos" />
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>" />
                    <input type="hidden" name="id" value="<?= $id; ?>" />
                    <div class="gp-form-grid">
                        <div class="gp-field">
                            <label>Nombre del Proveedor *</label>
                            <?php if ($puedeModificar) { ?>
                                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($proveedor['nombre'] ?? '', ENT_QUOTES); ?>" required />
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= htmlspecialchars($proveedor['nombre'] ?? '', ENT_QUOTES); ?></div>
                            <?php } ?>
                        </div>
                        <div class="gp-field">
                            <label>Host / URL</label>
                            <?php if ($puedeModificar) { ?>
                                <input type="text" name="host" class="form-control" value="<?= htmlspecialchars($proveedor['host'] ?? '', ENT_QUOTES); ?>" />
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= htmlspecialchars($proveedor['host'] ?? '', ENT_QUOTES); ?></div>
                            <?php } ?>
                        </div>
                        <div class="gp-field">
                            <label>Usuario</label>
                            <?php if ($puedeModificar) { ?>
                                <input type="text" name="user" class="form-control" value="<?= htmlspecialchars($proveedor['user'] ?? '', ENT_QUOTES); ?>" />
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= htmlspecialchars($proveedor['user'] ?? '', ENT_QUOTES); ?></div>
                            <?php } ?>
                        </div>
                        <div class="gp-field">
                            <label>Contraseña</label>
                            <?php if (!$esEdicion) { ?>
                                <?php if ($puedeModificar) { ?><input type="password" name="pass" class="form-control" /><?php } ?>
                            <?php } else { ?>
                                <div class="gp-pass-field" id="gpPassPrincipal">
                                    <input type="text" class="form-control" value="••••••••" readonly />
                                    <?php if (gp_puede_ver_credenciales()) { ?>
                                        <button type="button" class="gp-icon-btn gp-btn-revelar" data-tipo="proveedor" data-id="<?= $id; ?>" data-destino="gpPassPrincipal" title="Ver contraseña"><i class="bi bi-eye"></i></button>
                                    <?php } ?>
                                </div>
                                <p class="gp-pass-hint">Requiere reautenticación para revelar.</p>
                                <?php if ($puedeModificar) { ?>
                                    <label style="margin-top:14px;">Nueva contraseña (opcional)</label>
                                    <input type="password" name="pass" class="form-control" placeholder="Solo si desea cambiarla" />
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                    <?php if ($puedeModificar) { ?>
                        <div class="gp-form-actions">
                            <a href="index.php?doc=gp_proveedores" class="gp-btn-secondary">Cancelar</a>
                            <button type="submit" class="gp-btn-primary"><i class="bi bi-check-lg"></i> <?= $esEdicion ? 'Guardar cambios' : 'Crear proveedor'; ?></button>
                        </div>
                    <?php } ?>
                </form>
            </div>
        </div>

        <?php if ($esEdicion) { ?>
            <div class="gp-accordion-item" data-accordion="credenciales">
                <div class="gp-accordion-header">
                    <span class="gp-accordion-header-title"><i class="bi bi-lock"></i> Credenciales</span>
                    <div class="gp-accordion-header-right">
                        <?php if ($puedeModificar) { ?>
                            <button type="button" class="gp-btn-primary" onclick="event.stopPropagation(); gpAbrirModalCredencialProveedor(0)"><i class="bi bi-plus-lg"></i> Agregar credencial</button>
                        <?php } ?>
                        <i class="bi bi-chevron-down gp-accordion-chevron"></i>
                    </div>
                </div>
                <div class="gp-accordion-body">
                    <?php if (empty($credenciales)) { ?>
                        <div class="gp-table-empty"><i class="bi bi-key"></i><p>No hay credenciales adicionales para este proveedor.</p></div>
                    <?php } else { ?>
                        <table class="gp-table">
                            <thead><tr><th>Tipo</th><th>Usuario</th><th>Contraseña</th><th>Host</th><th>Puerto</th><th>Destino</th><th class="gp-th-derecha">Acciones</th></tr></thead>
                            <tbody>
                                <?php foreach ($credenciales as $c) { ?>
                                    <tr>
                                        <td><span class="gp-badge gp-badge-gris"><?= htmlspecialchars($c['tipo'], ENT_QUOTES); ?></span></td>
                                        <td class="gp-mono"><?= htmlspecialchars($c['user'], ENT_QUOTES); ?></td>
                                        <td>
                                            <div class="gp-pass-mask" id="gpPassCred<?= (int) $c['id']; ?>">
                                                <span class="gp-pass-dots">••••••••</span>
                                                <?php if (gp_puede_ver_credenciales()) { ?>
                                                    <button type="button" class="gp-icon-btn gp-btn-revelar" data-tipo="proveedor_credencial" data-id="<?= (int) $c['id']; ?>" data-destino="gpPassCred<?= (int) $c['id']; ?>" title="Ver contraseña"><i class="bi bi-eye"></i></button>
                                                <?php } ?>
                                            </div>
                                        </td>
                                        <td class="gp-mono"><?= htmlspecialchars($c['host'], ENT_QUOTES); ?></td>
                                        <td class="gp-mono"><?= htmlspecialchars($c['puerto'], ENT_QUOTES); ?></td>
                                        <td class="gp-mono"><?= htmlspecialchars($c['destino'], ENT_QUOTES); ?></td>
                                        <td class="gp-col-accion">
                                            <?php if ($puedeModificar) { ?>
                                                <button type="button" class="gp-icon-btn" onclick='gpAbrirModalCredencialProveedor(<?= (int) $c['id']; ?>, <?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Editar"><i class="bi bi-pencil"></i></button>
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

            <div class="gp-accordion-item" data-accordion="servicios">
                <div class="gp-accordion-header">
                    <span class="gp-accordion-header-title"><i class="bi bi-hdd-stack"></i> Servicios</span>
                    <div class="gp-accordion-header-right">
                        <?php if ($puedeModificar) { ?>
                            <a href="index.php?doc=gp_servicio&opc=1&proveedor_id=<?= $id; ?>" class="gp-btn-primary" onclick="event.stopPropagation();"><i class="bi bi-plus-lg"></i> Agregar servicio</a>
                        <?php } ?>
                        <i class="bi bi-chevron-down gp-accordion-chevron"></i>
                    </div>
                </div>
                <div class="gp-accordion-body">
                    <?php if (empty($servicios)) { ?>
                        <div class="gp-table-empty"><i class="bi bi-hdd-stack"></i><p>Este proveedor no tiene servicios registrados.</p></div>
                    <?php } else { ?>
                        <div class="gp-mini-card-grid">
                            <?php foreach ($servicios as $s) { ?>
                                <a href="index.php?doc=gp_servicio&opc=2&id=<?= (int) $s['id']; ?>" class="gp-mini-card" style="text-decoration:none;">
                                    <div class="gp-mini-card-top">
                                        <span class="gp-badge <?= $s['tipo'] === 'VPS' ? 'gp-badge-tipo-vps' : 'gp-badge-tipo-hosting'; ?>"><?= htmlspecialchars($s['tipo'], ENT_QUOTES); ?></span>
                                    </div>
                                    <div class="gp-mini-card-nombre"><?= htmlspecialchars($s['nombre'], ENT_QUOTES); ?></div>
                                    <div class="gp-mini-card-sub"><?= htmlspecialchars($s['host_ip'], ENT_QUOTES); ?></div>
                                    <?php if (!empty($s['fecha_vencimiento'])) { ?>
                                        <div class="gp-mini-card-sub"><?= gp_badge_vencimiento($s['fecha_vencimiento']); ?></div>
                                    <?php } ?>
                                    <div class="gp-mini-card-stats">
                                        <span title="Contraseñas"><i class="bi bi-lock"></i> <?= (int) $s['total_credenciales']; ?></span>
                                        <span title="Dominios"><i class="bi bi-globe"></i> <?= (int) $s['total_dominios']; ?></span>
                                        <span title="Proyectos"><i class="bi bi-kanban"></i> <?= (int) $s['total_proyectos']; ?></span>
                                    </div>
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
<div class="gp-modal-overlay" id="gpModalEliminarProveedor">
    <div class="gp-modal-form-box">
        <div class="gp-modal-form-header"><h3 style="color:#b91c1c;"><i class="bi bi-exclamation-triangle"></i> Eliminar proveedor</h3><button type="button" id="gpElimCerrar">&times;</button></div>
        <form method="post" id="gpFormEliminarProveedor">
            <input type="hidden" name="funcion" value="eliminar_proveedor" />
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>" />
            <input type="hidden" name="id" value="<?= (int) $id; ?>" />
            <div style="padding:18px 22px;font-size:13.5px;color:#334155;">
                <p style="margin:0 0 10px;">Se eliminará <strong><?= htmlspecialchars($proveedor['nombre'] ?? '', ENT_QUOTES); ?></strong> y, junto con él, todo lo que tiene registrado:</p>
                <ul style="margin:0 0 12px 18px;padding:0;">
                    <li><strong><?= (int) $resumenEliminacion['servicios']; ?></strong> servicio(s)</li>
                    <li><strong><?= (int) $resumenEliminacion['proyectos']; ?></strong> proyecto(s)</li>
                    <li><strong><?= (int) $resumenEliminacion['dominios']; ?></strong> dominio(s)</li>
                    <li><strong><?= (int) $resumenEliminacion['credenciales']; ?></strong> credencial(es)</li>
                </ul>
                <?php if ((int) $resumenEliminacion['tickets'] > 0) { ?>
                    <p style="margin:0 0 12px;color:#b45309;"><i class="bi bi-ticket-detailed"></i> Hay <strong><?= (int) $resumenEliminacion['tickets']; ?></strong> ticket(s) ligados a esos proyectos: se conservan, pero quedarán sin proyecto seleccionable.</p>
                <?php } ?>
                <p style="margin:0 0 6px;">Para confirmar, escriba el nombre del proveedor:</p>
                <input type="text" name="confirmar_nombre" id="gpElimNombre" class="form-control" autocomplete="off" placeholder="<?= htmlspecialchars($proveedor['nombre'] ?? '', ENT_QUOTES); ?>" />
                <p style="margin:10px 0 0;font-size:12px;color:#94a3b8;">Deja de mostrarse en el sistema; los datos no se borran físicamente.</p>
            </div>
            <div class="gp-modal-form-footer">
                <button type="button" class="gp-modal-cancelar" id="gpElimCancelar">Cancelar</button>
                <button type="submit" class="gp-modal-confirmar gp-btn-peligro-solido" id="gpElimConfirmar" disabled>Eliminar definitivamente</button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    var overlay = document.getElementById('gpModalEliminarProveedor');
    var nombre = <?= json_encode(mb_strtolower(trim($proveedor['nombre'] ?? ''))); ?>;
    var input = document.getElementById('gpElimNombre');
    var boton = document.getElementById('gpElimConfirmar');
    document.getElementById('gpBtnEliminarProveedor').addEventListener('click', function () {
        overlay.classList.add('gp-open'); input.value = ''; boton.disabled = true; setTimeout(function () { input.focus(); }, 50);
    });
    var cerrar = function () { overlay.classList.remove('gp-open'); };
    document.getElementById('gpElimCerrar').onclick = cerrar;
    document.getElementById('gpElimCancelar').onclick = cerrar;
    input.addEventListener('input', function () { boton.disabled = input.value.trim().toLowerCase() !== nombre; });
})();
</script>
<?php } ?>

<?php if ($puedeModificar) { ?>
<script>
function gpAbrirModalCredencialProveedor(id, datos) {
    datos = datos || {};
    var overlay = document.getElementById('gpModalCredOverlay');
    if (overlay) overlay.remove();

    var div = document.createElement('div');
    div.className = 'gp-modal-overlay gp-open';
    div.id = 'gpModalCredOverlay';
    div.innerHTML =
        '<div class="gp-modal-form-box">' +
        '  <div class="gp-modal-form-header"><h3>' + (id ? 'Editar credencial' : 'Agregar credencial') + '</h3><button type="button" onclick="document.getElementById(\'gpModalCredOverlay\').remove()">&times;</button></div>' +
        '  <div class="gp-modal-form-body">' +
        '    <div class="gp-field gp-field-full"><label>Tipo *</label><select class="form-control" id="gpCredTipo"><?php foreach (GP_TIPOS_CREDENCIAL as $k => $v) { echo '<option value="' . $k . '">' . $v . '</option>'; } ?></select></div>' +
        '    <div class="gp-field"><label>Usuario</label><input type="text" class="form-control" id="gpCredUser" /></div>' +
        '    <div class="gp-field"><label>Contraseña</label><input type="password" class="form-control" id="gpCredPass" placeholder="' + (id ? 'Dejar en blanco para no cambiarla' : '') + '" /></div>' +
        '    <div class="gp-field"><label>Host</label><input type="text" class="form-control" id="gpCredHost" /></div>' +
        '    <div class="gp-field"><label>Puerto</label><input type="text" class="form-control" id="gpCredPuerto" /></div>' +
        '    <div class="gp-field gp-field-full"><label>Destino</label><input type="text" class="form-control" id="gpCredDestino" /></div>' +
        '  </div>' +
        '  <div class="gp-modal-form-footer"><button type="button" class="gp-modal-cancelar" onclick="document.getElementById(\'gpModalCredOverlay\').remove()">Cancelar</button><button type="button" class="gp-modal-confirmar" id="gpCredGuardar">Guardar</button></div>' +
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
        body.set('padre', 'proveedor');
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
                if (data.ok) {
                    sessionStorage.setItem('gpAccordionAbierto', 'credenciales');
                    location.reload();
                } else {
                    alert(data.error || 'No se pudo guardar.');
                }
            });
    };
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
