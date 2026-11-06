<?php
require_once('gp_config.php');
require_once('gp_service.php');
require_once('gp_tickets_config.php');
require_once('gp_ticket_service.php');
gp_requerir_acceso();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$service = new GpService();

$opc = isset($_GET['opc']) ? (int) $_GET['opc'] : 1;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$servicioId = isset($_GET['servicio_id']) ? (int) $_GET['servicio_id'] : 0;
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
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'servicio_id' => (int) ($_POST['servicio_id'] ?? 0),
            'estado' => (int) ($_POST['estado'] ?? 1),
        ];
        if ($data['nombre'] === '') {
            $error = 'El nombre es obligatorio.';
        } elseif ($idPost > 0) {
            $service->guardarProyecto($idPost, $data, $_SESSION['id']);
            $id = $idPost;
            $esEdicion = true;
            $mensaje = 'Proyecto actualizado correctamente.';
        } else {
            $nuevoId = $service->guardarProyecto(0, $data, $_SESSION['id']);
            if ($nuevoId) {
                // Redirigir a la URL real de edicion (ver mismo patron en
                // gp_proveedor.php / gp_servicio.php): evita duplicados si
                // algo dispara un reload despues (ej. agregar credencial).
                echo '<script>window.location.replace("index.php?doc=gp_proyecto&opc=2&id=' . (int) $nuevoId . '");</script>';
                return;
            } else {
                $error = 'No se pudo crear el proyecto.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['funcion'] ?? '') === 'eliminar_proyecto') {
    $idEliminar = (int) ($_POST['id'] ?? 0);
    $proyectoEliminar = $idEliminar > 0 ? $service->getProyecto($idEliminar) : null;
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Error de validación CSRF.';
    } elseif (!gp_puede_eliminar_proveedores()) {
        // Mismo permiso especial que eliminar proveedores (Usuario > Acceso), consultado en vivo en la BD.
        $error = 'No tiene el permiso especial para eliminar en Gestión Proyectos.';
    } elseif (!$proyectoEliminar) {
        $error = 'Proyecto no encontrado.';
    } elseif (mb_strtolower(trim((string) ($_POST['confirmar_nombre'] ?? ''))) !== mb_strtolower(trim($proyectoEliminar['nombre']))) {
        $error = 'El nombre escrito no coincide: no se eliminó el proyecto.';
    } elseif ($service->eliminarProyecto($idEliminar, $_SESSION['id'])) {
        echo '<script>window.location.replace("index.php?doc=gp_servicio&opc=2&id=' . (int) $proyectoEliminar['servicio_id'] . '&eliminado=1");</script>';
        return;
    } else {
        $error = 'No se pudo eliminar el proyecto.';
    }
    $id = $idEliminar;
    $esEdicion = ($id > 0);
}

$proyecto = $esEdicion ? $service->getProyecto($id) : null;
if ($esEdicion && !$proyecto) {
    echo '<div class="gp-wrap"><p>Proyecto no encontrado.</p></div>';
    return;
}
if ($esEdicion) {
    $servicioId = (int) $proyecto['servicio_id'];
}
$servicio = $service->getServicio($servicioId);
if (!$servicio) {
    echo '<div class="gp-wrap"><p>Servicio no encontrado.</p></div>';
    return;
}

$puedeModificar = gp_puede_modificar();
$puedeEliminar = $esEdicion && gp_puede_eliminar_proveedores();
$resumenEliminacion = $puedeEliminar ? $service->resumenEliminacionProyecto($id) : null;
$credenciales = $esEdicion ? $service->listarCredencialesProyecto($id) : [];

// Tareas (tickets) del proyecto: solo con acceso al modulo Tickets/Tareas.
// Se respeta la misma visibilidad que en gp_tickets (admin ve todo; el resto, las suyas).
$verTareas = $esEdicion && (gp_tickets_puede_consultar() || gp_tickets_puede_modificar());
$tareasProyecto = [];
if ($verTareas) {
    $ticketService = new GpTicketService();
    $tareasProyecto = $ticketService->listarTickets('', '', $id, (int) $_SESSION['id'], gp_tickets_es_admin());
}
?>
<link rel="stylesheet" href="usuario-form.css?v=<?= @filemtime('usuario-form.css'); ?>" />
<link rel="stylesheet" href="gp.css?v=<?= @filemtime('gp.css'); ?>" />
<link rel="stylesheet" href="gp_tickets.css?v=<?= @filemtime('gp_tickets.css'); ?>" />
<script src="js/gp.js?v=<?= @filemtime('js/gp.js'); ?>"></script>

<div class="gp-wrap">
    <div class="gp-breadcrumb">
        <a href="index.php?doc=gp_proveedores">Proveedores</a>
        <i class="bi bi-chevron-right"></i>
        <a href="index.php?doc=gp_proveedor&opc=2&id=<?= (int) $servicio['proveedor_id']; ?>"><?= htmlspecialchars($servicio['proveedor_nombre'], ENT_QUOTES); ?></a>
        <i class="bi bi-chevron-right"></i>
        <a href="index.php?doc=gp_servicio&opc=2&id=<?= $servicioId; ?>"><?= htmlspecialchars($servicio['nombre'], ENT_QUOTES); ?></a>
        <i class="bi bi-chevron-right"></i>
        <span class="gp-actual"><?= $esEdicion ? htmlspecialchars($proyecto['nombre'], ENT_QUOTES) : 'Nuevo proyecto'; ?></span>
    </div>

    <?php if ($mensaje) { ?><div class="alert alert-success"><?= htmlspecialchars($mensaje, ENT_QUOTES); ?></div><?php } ?>
    <?php if ($error) { ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES); ?></div><?php } ?>
    <?php if (!$puedeModificar) { ?>
        <div class="gp-solo-consulta-aviso"><i class="bi bi-eye"></i> Modo solo consulta: no tiene permiso de modificación en Gestión Proyectos.</div>
    <?php } ?>

    <div class="gp-page-header">
        <div>
            <h1><?= $esEdicion ? htmlspecialchars($proyecto['nombre'], ENT_QUOTES) : 'Nuevo proyecto'; ?></h1>
            <p class="gp-page-header-sub">Servicio: <b><?= htmlspecialchars($servicio['nombre'], ENT_QUOTES); ?></b></p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <?php if ($esEdicion) { ?>
                <span class="gp-badge-estado<?= $proyecto['estado'] ? '' : ' gp-badge-estado-inactivo'; ?>"><?= $proyecto['estado'] ? 'Activo' : 'Inactivo'; ?></span>
            <?php } ?>
            <?php if ($puedeEliminar) { ?>
                <button type="button" class="gp-btn-peligro" id="gpBtnEliminarProyecto"><i class="bi bi-trash"></i> Eliminar proyecto</button>
            <?php } ?>
        </div>
    </div>

    <div class="gp-tabs-card">
        <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link active" href="#tabDatos">Datos</a></li>
            <?php if ($esEdicion) { ?>
                <li class="nav-item"><a class="nav-link" href="#tabCredenciales">Credenciales</a></li>
                <?php if ($verTareas) { ?>
                    <li class="nav-item"><a class="nav-link" href="#tabTareas">Tareas (<?= count($tareasProyecto); ?>)</a></li>
                <?php } ?>
            <?php } ?>
        </ul>
    </div>

    <div class="tab-content">
        <div class="tab-pane active" id="tabDatos">
            <div class="gp-card">
                <form method="post">
                    <input type="hidden" name="funcion" value="guardar_datos" />
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>" />
                    <input type="hidden" name="id" value="<?= $id; ?>" />
                    <input type="hidden" name="servicio_id" value="<?= $servicioId; ?>" />
                    <div class="gp-form-grid">
                        <div class="gp-field gp-field-full" style="grid-column:1/-1;">
                            <label>Nombre *</label>
                            <?php if ($puedeModificar) { ?>
                                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($proyecto['nombre'] ?? '', ENT_QUOTES); ?>" required />
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= htmlspecialchars($proyecto['nombre'] ?? '', ENT_QUOTES); ?></div>
                            <?php } ?>
                        </div>
                        <div class="gp-field gp-field-full" style="grid-column:1/-1;">
                            <label>Descripción</label>
                            <?php if ($puedeModificar) { ?>
                                <input type="text" name="descripcion" class="form-control" value="<?= htmlspecialchars($proyecto['descripcion'] ?? '', ENT_QUOTES); ?>" />
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= htmlspecialchars($proyecto['descripcion'] ?? '', ENT_QUOTES); ?></div>
                            <?php } ?>
                        </div>
                        <div class="gp-field">
                            <label>Estado</label>
                            <?php if ($puedeModificar) { ?>
                                <select name="estado" class="form-control">
                                    <option value="1" <?= (!$esEdicion || $proyecto['estado']) ? 'selected' : ''; ?>>Activo</option>
                                    <option value="0" <?= ($esEdicion && !$proyecto['estado']) ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= (!$esEdicion || $proyecto['estado']) ? 'Activo' : 'Inactivo'; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php if ($puedeModificar) { ?>
                        <div class="gp-form-actions">
                            <a href="index.php?doc=gp_servicio&opc=2&id=<?= $servicioId; ?>#tabProyectos" class="gp-btn-secondary">Cancelar</a>
                            <button type="submit" class="gp-btn-primary"><i class="bi bi-check-lg"></i> <?= $esEdicion ? 'Guardar cambios' : 'Crear proyecto'; ?></button>
                        </div>
                    <?php } ?>
                </form>
            </div>
        </div>

        <?php if ($esEdicion) { ?>
            <div class="tab-pane" id="tabCredenciales">
                <div class="gp-card">
                    <div class="gp-card-header">
                        <span class="gp-card-header-title"><i class="bi bi-lock"></i> Credenciales del proyecto</span>
                        <?php if ($puedeModificar) { ?>
                            <button type="button" class="gp-btn-primary" onclick="gpAbrirModalCredencialProyecto(0)"><i class="bi bi-plus-lg"></i> Agregar credencial</button>
                        <?php } ?>
                    </div>
                    <?php if (empty($credenciales)) { ?>
                        <div class="gp-table-empty"><i class="bi bi-key"></i><p>No hay credenciales registradas para este proyecto.</p></div>
                    <?php } else { ?>
                        <table class="gp-table">
                            <thead><tr><th>Tipo</th><th>Usuario</th><th>Contraseña</th><th>Host</th><th>Puerto</th><th>Destino</th><th>Persona asignada</th><th class="gp-th-derecha">Acciones</th></tr></thead>
                            <tbody>
                                <?php foreach ($credenciales as $c) { ?>
                                    <tr>
                                        <td><span class="gp-badge gp-badge-gris"><?= htmlspecialchars($c['tipo'], ENT_QUOTES); ?></span></td>
                                        <td class="gp-mono"><?= htmlspecialchars($c['user'], ENT_QUOTES); ?></td>
                                        <td>
                                            <div class="gp-pass-mask" id="gpPassPCred<?= (int) $c['id']; ?>">
                                                <span class="gp-pass-dots">••••••••</span>
                                                <?php if (gp_puede_ver_credenciales()) { ?>
                                                    <button type="button" class="gp-icon-btn gp-btn-revelar" data-tipo="proyecto_credencial" data-id="<?= (int) $c['id']; ?>" data-destino="gpPassPCred<?= (int) $c['id']; ?>" title="Ver contraseña"><i class="bi bi-eye"></i></button>
                                                <?php } ?>
                                            </div>
                                        </td>
                                        <td class="gp-mono"><?= htmlspecialchars($c['host'], ENT_QUOTES); ?></td>
                                        <td class="gp-mono"><?= htmlspecialchars($c['puerto'], ENT_QUOTES); ?></td>
                                        <td class="gp-mono"><?= htmlspecialchars($c['destino'], ENT_QUOTES); ?></td>
                                        <td><?= htmlspecialchars($c['persona_asignada'], ENT_QUOTES); ?></td>
                                        <td class="gp-col-accion">
                                            <?php if ($puedeModificar) { ?>
                                                <button type="button" class="gp-icon-btn" onclick='gpAbrirModalCredencialProyecto(<?= (int) $c['id']; ?>, <?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Editar"><i class="bi bi-pencil"></i></button>
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
        <?php } ?>

        <?php if ($verTareas) { ?>
            <div class="tab-pane" id="tabTareas">
                <div class="gp-card">
                    <div class="gp-card-header">
                        <span class="gp-card-header-title"><i class="bi bi-list-check"></i> Tareas del proyecto</span>
                        <?php if (gp_tickets_puede_modificar()) { ?>
                            <a href="index.php?doc=gp_ticket&opc=1&proyecto_id=<?= $id; ?>" class="gp-btn-primary"><i class="bi bi-plus-lg"></i> Nueva tarea</a>
                        <?php } ?>
                    </div>
                    <?php if (empty($tareasProyecto)) { ?>
                        <div class="gp-table-empty"><i class="bi bi-list-check"></i><p>No hay tareas asignadas a este proyecto.</p></div>
                    <?php } else { ?>
                        <table class="gp-table">
                            <thead><tr><th>Tarea</th><th>Estado</th><th>Asignado a</th><th>Entrega estimada</th><th>Alerta de atención</th></tr></thead>
                            <tbody>
                                <?php foreach ($tareasProyecto as $t) {
                                    $sem = gp_tickets_semaforo($t['fecha_entrega_estimada'] ?? null, $t['estado']);
                                ?>
                                    <tr style="cursor:pointer;" onclick="window.location='index.php?doc=gp_ticket&opc=2&id=<?= (int) $t['id']; ?>'">
                                        <td><?= htmlspecialchars($t['titulo'], ENT_QUOTES); ?></td>
                                        <td><span class="gpt-badge-estado gpt-badge-<?= htmlspecialchars($t['estado'], ENT_QUOTES); ?>"><?= htmlspecialchars(GP_TICKET_ESTADOS[$t['estado']] ?? $t['estado'], ENT_QUOTES); ?></span></td>
                                        <td><?= !empty($t['asignado_a_nombre']) ? htmlspecialchars($t['asignado_a_nombre'], ENT_QUOTES) : '<span style="color:#94a3b8;">Sin asignar</span>'; ?></td>
                                        <td><?= (!empty($t['fecha_entrega_estimada']) && $t['fecha_entrega_estimada'] !== '0000-00-00') ? date('d M Y', strtotime($t['fecha_entrega_estimada'])) : '<span style="color:#94a3b8;">Sin definir</span>'; ?></td>
                                        <td>
                                            <?php if ($sem) {
                                                $d = $sem['dias'];
                                                $detalle = $d < 0 ? 'hace ' . abs($d) . ' día' . (abs($d) == 1 ? '' : 's') : ($d == 0 ? 'hoy' : 'en ' . $d . ' día' . ($d == 1 ? '' : 's'));
                                            ?>
                                                <span class="gpt-semaforo gpt-semaforo-<?= $sem['color']; ?>" style="margin-top:0;"><i class="bi bi-circle-fill"></i> <?= htmlspecialchars($sem['label'], ENT_QUOTES); ?> · <?= $detalle; ?></span>
                                            <?php } elseif ($t['estado'] === 'finalizado') { ?>
                                                <span style="color:#15803d;">Finalizada</span>
                                            <?php } else { ?>
                                                <span style="color:#94a3b8;">Sin fecha</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <div class="gp-card-footer">Mostrando <?= count($tareasProyecto); ?> tarea<?= count($tareasProyecto) == 1 ? '' : 's'; ?></div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<?php if ($puedeEliminar) { ?>
<div class="gp-modal-overlay" id="gpModalEliminarProyecto">
    <div class="gp-modal-form-box">
        <div class="gp-modal-form-header"><h3 style="color:#b91c1c;"><i class="bi bi-exclamation-triangle"></i> Eliminar proyecto</h3><button type="button" id="gpElimProyCerrar">&times;</button></div>
        <form method="post" id="gpFormEliminarProyecto">
            <input type="hidden" name="funcion" value="eliminar_proyecto" />
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>" />
            <input type="hidden" name="id" value="<?= (int) $id; ?>" />
            <div style="padding:18px 22px;font-size:13.5px;color:#334155;">
                <p style="margin:0 0 10px;">Se eliminará <strong><?= htmlspecialchars($proyecto['nombre'] ?? '', ENT_QUOTES); ?></strong> y, junto con él, todo lo que tiene registrado:</p>
                <ul style="margin:0 0 12px 18px;padding:0;">
                    <li><strong><?= (int) $resumenEliminacion['credenciales']; ?></strong> credencial(es)</li>
                </ul>
                <?php if ((int) $resumenEliminacion['tickets'] > 0) { ?>
                    <p style="margin:0 0 12px;color:#b45309;"><i class="bi bi-ticket-detailed"></i> Hay <strong><?= (int) $resumenEliminacion['tickets']; ?></strong> ticket(s) ligados a este proyecto: se conservan, pero quedarán sin proyecto seleccionable.</p>
                <?php } ?>
                <p style="margin:0 0 6px;">Para confirmar, escriba el nombre del proyecto:</p>
                <input type="text" name="confirmar_nombre" id="gpElimProyNombre" class="form-control" autocomplete="off" placeholder="<?= htmlspecialchars($proyecto['nombre'] ?? '', ENT_QUOTES); ?>" />
                <p style="margin:10px 0 0;font-size:12px;color:#94a3b8;">No se elimina el servicio; deja de mostrarse en el sistema, los datos no se borran físicamente.</p>
            </div>
            <div class="gp-modal-form-footer">
                <button type="button" class="gp-modal-cancelar" id="gpElimProyCancelar">Cancelar</button>
                <button type="submit" class="gp-modal-confirmar gp-btn-peligro-solido" id="gpElimProyConfirmar" disabled>Eliminar definitivamente</button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    var overlay = document.getElementById('gpModalEliminarProyecto');
    var nombre = <?= json_encode(mb_strtolower(trim($proyecto['nombre'] ?? ''))); ?>;
    var input = document.getElementById('gpElimProyNombre');
    var boton = document.getElementById('gpElimProyConfirmar');
    document.getElementById('gpBtnEliminarProyecto').addEventListener('click', function () {
        overlay.classList.add('gp-open'); input.value = ''; boton.disabled = true; setTimeout(function () { input.focus(); }, 50);
    });
    var cerrar = function () { overlay.classList.remove('gp-open'); };
    document.getElementById('gpElimProyCerrar').onclick = cerrar;
    document.getElementById('gpElimProyCancelar').onclick = cerrar;
    input.addEventListener('input', function () { boton.disabled = input.value.trim().toLowerCase() !== nombre; });
})();
</script>
<?php } ?>

<?php if ($puedeModificar) { ?>
<script>
function gpAbrirModalCredencialProyecto(id, datos) {
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
        '    <div class="gp-field gp-field-full"><label>Persona asignada *</label><input type="text" class="form-control" id="gpCredPersona" /></div>' +
        '  </div>' +
        '  <div class="gp-modal-form-footer"><button type="button" class="gp-modal-cancelar" onclick="document.getElementById(\'gpModalOverlay\').remove()">Cancelar</button><button type="button" class="gp-modal-confirmar" id="gpCredGuardar">Guardar</button></div>' +
        '</div>';
    document.body.appendChild(div);
    if (datos.tipo) document.getElementById('gpCredTipo').value = datos.tipo;
    document.getElementById('gpCredUser').value = datos.user || '';
    document.getElementById('gpCredHost').value = datos.host || '';
    document.getElementById('gpCredPuerto').value = datos.puerto || '';
    document.getElementById('gpCredDestino').value = datos.destino || '';
    document.getElementById('gpCredPersona').value = datos.persona_asignada || '';
    document.getElementById('gpCredGuardar').onclick = function () {
        var persona = document.getElementById('gpCredPersona').value;
        if (!persona) {
            alert('La persona asignada es obligatoria.');
            return;
        }
        var body = new URLSearchParams();
        body.set('accion', 'guardar');
        body.set('padre', 'proyecto');
        body.set('padre_id', '<?= $id; ?>');
        body.set('id', id);
        body.set('tipo', document.getElementById('gpCredTipo').value);
        body.set('user', document.getElementById('gpCredUser').value);
        body.set('pass', document.getElementById('gpCredPass').value);
        body.set('host', document.getElementById('gpCredHost').value);
        body.set('puerto', document.getElementById('gpCredPuerto').value);
        body.set('destino', document.getElementById('gpCredDestino').value);
        body.set('persona_asignada', persona);
        fetch('gp_credencial_ajax.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) { location.hash = '#tabCredenciales'; location.reload(); }
                else { alert(data.error || 'No se pudo guardar.'); }
            });
    };
}
</script>
<?php } ?>

<script>
(function () {
    var links = document.querySelectorAll('.gp-tabs-card .nav-link');
    var panes = document.querySelectorAll('.tab-pane');
    links.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            links.forEach(function (l) { l.classList.remove('active'); });
            panes.forEach(function (p) { p.classList.remove('active'); });
            this.classList.add('active');
            var pane = document.querySelector(this.getAttribute('href'));
            if (pane) pane.classList.add('active');
        });
    });
    if (window.location.hash) {
        var target = document.querySelector('.gp-tabs-card .nav-link[href="' + window.location.hash + '"]');
        if (target) target.click();
    }
})();
</script>
