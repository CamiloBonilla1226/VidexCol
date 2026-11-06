<?php
/**
 * Inventario de Dominios: detalle de un proveedor de dominios y los dominios
 * registrados en el. Eliminar el proveedor exige el permiso especial
 * gp_eliminar_proveedores (Usuario > Acceso).
 */
require_once('gp_config.php');
require_once('gp_dom_service.php');
gp_requerir_acceso();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$service = new GpDomService();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$esEdicion = ($id > 0);
$mensaje = '';
$error = '';
$puedeModificar = gp_puede_modificar();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['funcion'] ?? '') === 'guardar_datos') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Error de validación CSRF.';
    } elseif (!$puedeModificar) {
        $error = 'No tiene permiso de modificación.';
    } else {
        $idPost = (int) ($_POST['id'] ?? 0);
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'sitio_web' => trim($_POST['sitio_web'] ?? ''),
            'user' => trim($_POST['user'] ?? ''),
            'pass' => $_POST['pass'] ?? '',
            'notas' => trim($_POST['notas'] ?? ''),
        ];
        if ($data['nombre'] === '') {
            $error = 'El nombre del proveedor es obligatorio.';
        } elseif ($idPost > 0 && !$service->getProveedor($idPost)) {
            $error = 'Proveedor no encontrado.';
        } else {
            $guardado = $service->guardarProveedor($idPost, $data, $_SESSION['id']);
            if (!$guardado) {
                $error = 'No se pudo guardar el proveedor.';
            } elseif ($idPost === 0) {
                // Redirigir a la URL de edicion real evita duplicados si la pagina se recarga.
                echo '<script>window.location.replace("index.php?doc=gp_dom_proveedor&opc=2&id=' . (int) $guardado . '");</script>';
                return;
            } else {
                $id = $idPost;
                $esEdicion = true;
                $mensaje = 'Proveedor actualizado correctamente.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['funcion'] ?? '') === 'eliminar_proveedor') {
    $idEliminar = (int) ($_POST['id'] ?? 0);
    $provEliminar = $idEliminar > 0 ? $service->getProveedor($idEliminar) : null;
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Error de validación CSRF.';
    } elseif (!gp_puede_eliminar_proveedores()) {
        $error = 'No tiene el permiso especial para eliminar proveedores.';
    } elseif (!$provEliminar) {
        $error = 'Proveedor no encontrado.';
    } elseif (mb_strtolower(trim((string) ($_POST['confirmar_nombre'] ?? ''))) !== mb_strtolower(trim($provEliminar['nombre']))) {
        $error = 'El nombre escrito no coincide: no se eliminó el proveedor.';
    } elseif ($service->eliminarProveedor($idEliminar, $_SESSION['id'])) {
        echo '<script>window.location.replace("index.php?doc=gp_dom_proveedores&eliminado=1");</script>';
        return;
    } else {
        $error = 'No se pudo eliminar el proveedor.';
    }
    $id = $idEliminar;
    $esEdicion = ($id > 0);
}

$proveedor = $esEdicion ? $service->getProveedor($id) : null;
if ($esEdicion && !$proveedor) {
    echo '<div class="gp-wrap"><p>Proveedor no encontrado.</p><p><a href="index.php?doc=gp_dom_proveedores">Volver a Dominios</a></p></div>';
    return;
}
if (!$esEdicion && !$puedeModificar) {
    echo '<div class="gp-wrap"><p>No tiene permiso para crear proveedores.</p><p><a href="index.php?doc=gp_dom_proveedores">Volver a Dominios</a></p></div>';
    return;
}

$puedeEliminar = $esEdicion && gp_puede_eliminar_proveedores();
$dominios = $esEdicion ? $service->listarDominiosPorProveedor($id) : [];
?>
<link rel="stylesheet" href="usuario-form.css?v=<?= @filemtime('usuario-form.css'); ?>" />
<link rel="stylesheet" href="gp.css?v=<?= @filemtime('gp.css'); ?>" />
<link rel="stylesheet" href="gp_dominios.css?v=<?= @filemtime('gp_dominios.css'); ?>" />
<script src="js/gp.js?v=<?= @filemtime('js/gp.js'); ?>"></script>

<div class="gp-wrap">
    <div class="gp-breadcrumb">
        <a href="index.php?doc=gp_dom_proveedores">Dominios</a>
        <i class="bi bi-chevron-right"></i>
        <span class="gp-actual"><?= $esEdicion ? htmlspecialchars($proveedor['nombre'], ENT_QUOTES) : 'Nuevo proveedor de dominios'; ?></span>
    </div>

    <?php if ($mensaje) { ?><div class="alert alert-success"><?= htmlspecialchars($mensaje, ENT_QUOTES); ?></div><?php } ?>
    <?php if ($error) { ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES); ?></div><?php } ?>
    <?php if (!$puedeModificar) { ?>
        <div class="gp-solo-consulta-aviso"><i class="bi bi-eye"></i> Modo solo consulta: no tiene permiso de modificación en Gestión Proyectos.</div>
    <?php } ?>

    <div class="gp-page-header">
        <div>
            <h1><?= $esEdicion ? htmlspecialchars($proveedor['nombre'], ENT_QUOTES) : 'Nuevo proveedor de dominios'; ?></h1>
            <p class="gp-page-header-sub">Proveedor de dominios</p>
        </div>
        <?php if ($puedeEliminar) { ?>
            <button type="button" class="gp-btn-peligro" id="gpBtnEliminarProveedor"><i class="bi bi-trash"></i> Eliminar proveedor</button>
        <?php } ?>
    </div>

    <?php if ($esEdicion) { ?>
        <div class="gp-kpi-grid">
            <div class="gp-kpi-card">
                <div><p class="gp-kpi-label">Dominios</p><div class="gp-kpi-value"><?= (int) $proveedor['total_dominios']; ?></div></div>
                <div class="gp-kpi-icon"><i class="bi bi-globe"></i></div>
            </div>
            <div class="gp-kpi-card">
                <div><p class="gp-kpi-label">Vencen en 30 días</p><div class="gp-kpi-value"><?= (int) $proveedor['total_por_vencer']; ?></div></div>
                <div class="gp-kpi-icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="gp-kpi-card">
                <div><p class="gp-kpi-label">Vencidos</p><div class="gp-kpi-value"><?= (int) $proveedor['total_vencidos']; ?></div></div>
                <div class="gp-kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    <?php } ?>

    <div class="gp-accordion">
        <div class="gp-accordion-item <?= $esEdicion ? '' : 'gp-open'; ?>" data-accordion="datos">
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
                            <label>Nombre del proveedor *</label>
                            <?php if ($puedeModificar) { ?>
                                <input type="text" name="nombre" class="form-control" maxlength="150" value="<?= htmlspecialchars($proveedor['nombre'] ?? '', ENT_QUOTES); ?>" required />
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= htmlspecialchars($proveedor['nombre'] ?? '', ENT_QUOTES); ?></div>
                            <?php } ?>
                        </div>
                        <div class="gp-field">
                            <label>Sitio web / panel</label>
                            <?php if ($puedeModificar) { ?>
                                <input type="text" name="sitio_web" class="form-control" maxlength="255" value="<?= htmlspecialchars($proveedor['sitio_web'] ?? '', ENT_QUOTES); ?>" placeholder="https://..." />
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= htmlspecialchars($proveedor['sitio_web'] ?? '', ENT_QUOTES); ?></div>
                            <?php } ?>
                        </div>
                        <div class="gp-field">
                            <label>Usuario del panel</label>
                            <?php if ($puedeModificar) { ?>
                                <input type="text" name="user" class="form-control" maxlength="150" value="<?= htmlspecialchars($proveedor['user'] ?? '', ENT_QUOTES); ?>" />
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= htmlspecialchars($proveedor['user'] ?? '', ENT_QUOTES); ?></div>
                            <?php } ?>
                        </div>
                        <div class="gp-field">
                            <label>Contraseña del panel</label>
                            <?php if (!$esEdicion) { ?>
                                <input type="password" name="pass" class="form-control" autocomplete="new-password" />
                            <?php } else { ?>
                                <div class="gp-pass-field" id="gpPassPrincipal">
                                    <input type="text" class="form-control" value="••••••••" readonly />
                                    <?php if (gp_puede_ver_credenciales()) { ?>
                                        <button type="button" class="gp-icon-btn gp-btn-revelar" data-tipo="dom_proveedor" data-id="<?= $id; ?>" data-destino="gpPassPrincipal" title="Ver contraseña"><i class="bi bi-eye"></i></button>
                                    <?php } ?>
                                </div>
                                <p class="gp-pass-hint">Requiere reautenticación para revelar.</p>
                                <?php if ($puedeModificar) { ?>
                                    <label style="margin-top:14px;">Nueva contraseña (opcional)</label>
                                    <input type="password" name="pass" class="form-control" placeholder="Solo si desea cambiarla" autocomplete="new-password" />
                                <?php } ?>
                            <?php } ?>
                        </div>
                        <div class="gp-field gp-field-full" style="grid-column:1/-1;">
                            <label>Notas</label>
                            <?php if ($puedeModificar) { ?>
                                <textarea name="notas" class="form-control" rows="2"><?= htmlspecialchars($proveedor['notas'] ?? '', ENT_QUOTES); ?></textarea>
                            <?php } else { ?>
                                <div class="gp-field-readonly"><?= nl2br(htmlspecialchars($proveedor['notas'] ?? '', ENT_QUOTES)); ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php if ($puedeModificar) { ?>
                        <div class="gp-form-actions">
                            <a href="index.php?doc=gp_dom_proveedores" class="gp-btn-secondary">Cancelar</a>
                            <button type="submit" class="gp-btn-primary"><i class="bi bi-check-lg"></i> <?= $esEdicion ? 'Guardar cambios' : 'Crear proveedor'; ?></button>
                        </div>
                    <?php } ?>
                </form>
            </div>
        </div>

        <?php if ($esEdicion) { ?>
            <div class="gp-accordion-item gp-open" data-accordion="dominios">
                <div class="gp-accordion-header">
                    <span class="gp-accordion-header-title"><i class="bi bi-globe"></i> Dominios</span>
                    <div class="gp-accordion-header-right">
                        <?php if ($puedeModificar) { ?>
                            <button type="button" class="gp-btn-primary" onclick="event.stopPropagation(); gpdAbrirModalDominio(0)"><i class="bi bi-plus-lg"></i> Agregar dominio</button>
                        <?php } ?>
                        <i class="bi bi-chevron-down gp-accordion-chevron"></i>
                    </div>
                </div>
                <div class="gp-accordion-body">
                    <?php if (empty($dominios)) { ?>
                        <div class="gp-table-empty"><i class="bi bi-globe"></i><p>Este proveedor aún no tiene dominios registrados.</p></div>
                    <?php } else { ?>
                        <table class="gp-table">
                            <thead>
                                <tr><th>Dominio</th><th>Registro</th><th>Vencimiento</th><th>SSL</th><th>Renov. auto.</th><th>Estado</th><th>Notas</th><?php if ($puedeModificar) { ?><th class="gp-th-derecha">Acciones</th><?php } ?></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dominios as $d) { ?>
                                    <tr>
                                        <td class="gp-mono"><strong><?= htmlspecialchars($d['dominio'], ENT_QUOTES); ?></strong></td>
                                        <td><?= $d['fecha_registro'] ? date('d M Y', strtotime($d['fecha_registro'])) : '<span class="gpd-no">—</span>'; ?></td>
                                        <td><?= GpDomService::badgeVencimiento($d['fecha_vencimiento']); ?></td>
                                        <td><?= GpDomService::badgeVencimiento($d['ssl_fecha_vencimiento'] ?? null); ?></td>
                                        <td><?= $d['renovacion_automatica'] ? '<i class="bi bi-arrow-repeat gpd-si"></i> Sí' : '<span class="gpd-no">No</span>'; ?></td>
                                        <td><span class="gp-badge <?= $d['estado'] ? 'gp-badge-verde' : 'gp-badge-rojo'; ?>"><?= $d['estado'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                        <td class="gpd-notas"><?= htmlspecialchars((string) $d['notas'], ENT_QUOTES); ?></td>
                                        <?php if ($puedeModificar) { ?>
                                            <td class="gp-col-accion">
                                                <button type="button" class="gp-icon-btn" onclick='gpdAbrirModalDominio(<?= (int) $d['id']; ?>, <?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP); ?>)' title="Editar"><i class="bi bi-pencil"></i></button>
                                                <button type="button" class="gp-icon-btn gp-icon-danger" onclick='gpdEliminarDominio(<?= (int) $d['id']; ?>, <?= json_encode($d['dominio'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP); ?>)' title="Eliminar"><i class="bi bi-trash"></i></button>
                                            </td>
                                        <?php } ?>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <div class="gp-card-footer">Mostrando <?= count($dominios); ?> dominio<?= count($dominios) == 1 ? '' : 's'; ?></div>
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
        <form method="post">
            <input type="hidden" name="funcion" value="eliminar_proveedor" />
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>" />
            <input type="hidden" name="id" value="<?= (int) $id; ?>" />
            <div style="padding:18px 22px;font-size:13.5px;color:#334155;">
                <p style="margin:0 0 10px;">Se eliminará <strong><?= htmlspecialchars($proveedor['nombre'], ENT_QUOTES); ?></strong> junto con sus <strong><?= (int) $proveedor['total_dominios']; ?></strong> dominio(s) registrados.</p>
                <p style="margin:0 0 6px;">Para confirmar, escriba el nombre del proveedor:</p>
                <input type="text" name="confirmar_nombre" id="gpElimNombre" class="form-control" autocomplete="off" placeholder="<?= htmlspecialchars($proveedor['nombre'], ENT_QUOTES); ?>" />
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
    var nombre = <?= json_encode(mb_strtolower(trim($proveedor['nombre']))); ?>;
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

<?php if ($esEdicion && $puedeModificar) { ?>
<script>
var GPD_PROVEEDOR_ID = <?= (int) $id; ?>;
var GPD_CSRF = <?= json_encode($_SESSION['csrf_token']); ?>;

function gpdPost(datos) {
    var body = new URLSearchParams();
    body.set('csrf_token', GPD_CSRF);
    Object.keys(datos).forEach(function (k) { body.set(k, datos[k]); });
    return fetch('gp_dom_ajax.php', { method: 'POST', body: body, credentials: 'same-origin' })
        .then(function (r) { return r.json().catch(function () { return { error: 'Respuesta inválida del servidor.' }; }); });
}

function gpdAbrirModalDominio(id, datos) {
    datos = datos || {};
    var viejo = document.getElementById('gpdModalDominio');
    if (viejo) viejo.remove();
    var div = document.createElement('div');
    div.className = 'gp-modal-overlay gp-open';
    div.id = 'gpdModalDominio';
    div.innerHTML =
        '<div class="gp-modal-form-box">' +
        '  <div class="gp-modal-form-header"><h3>' + (id ? 'Editar dominio' : 'Agregar dominio') + '</h3><button type="button" id="gpdCerrar">&times;</button></div>' +
        '  <div class="gp-modal-form-body">' +
        '    <div class="gp-field gp-field-full"><label>Dominio *</label><input type="text" class="form-control" id="gpdDominio" maxlength="255" placeholder="midominio.com" autocomplete="off" /></div>' +
        '    <div class="gp-field"><label>Fecha de registro</label><input type="date" class="form-control" id="gpdRegistro" /></div>' +
        '    <div class="gp-field"><label>Fecha de vencimiento</label><input type="date" class="form-control" id="gpdVence" /></div>' +
        '    <div class="gp-field"><label>Estado</label><select class="form-control" id="gpdEstado"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>' +
        '    <div class="gp-field"><label>Renovación automática</label><label class="gpd-check"><input type="checkbox" id="gpdRenov" /> Se renueva solo</label></div>' +
        '    <div class="gp-field gp-field-full" style="grid-column:1/-1;border-top:1px solid #e2e8f0;margin-top:4px;padding-top:12px;"><strong><i class="bi bi-shield-lock"></i> Certificado SSL <span style="font-weight:400;color:#94a3b8;">(opcional)</span></strong></div>' +
        '    <div class="gp-field"><label>Vencimiento del SSL</label><input type="date" class="form-control" id="gpdSslVence" /></div>' +
        '    <div class="gp-field"><label>Proveedor / emisor</label><input type="text" class="form-control" id="gpdSslProveedor" maxlength="150" placeholder="Let\'s Encrypt, Sectigo..." /></div>' +
        '    <div class="gp-field gp-field-full"><label>Notas del SSL</label><input type="text" class="form-control" id="gpdSslNotas" maxlength="255" /></div>' +
        '    <div class="gp-field gp-field-full"><label>Notas</label><input type="text" class="form-control" id="gpdNotas" maxlength="500" /></div>' +
        '    <div class="gp-modal-error gp-field-full" id="gpdError"></div>' +
        '  </div>' +
        '  <div class="gp-modal-form-footer"><button type="button" class="gp-modal-cancelar" id="gpdCancelar">Cancelar</button><button type="button" class="gp-modal-confirmar" id="gpdGuardar">Guardar</button></div>' +
        '</div>';
    document.body.appendChild(div);

    document.getElementById('gpdDominio').value = datos.dominio || '';
    document.getElementById('gpdRegistro').value = datos.fecha_registro || '';
    document.getElementById('gpdVence').value = datos.fecha_vencimiento || '';
    document.getElementById('gpdEstado').value = (datos.estado === undefined || datos.estado === null) ? '1' : String(datos.estado);
    document.getElementById('gpdRenov').checked = String(datos.renovacion_automatica) === '1';
    document.getElementById('gpdNotas').value = datos.notas || '';
    document.getElementById('gpdSslVence').value = datos.ssl_fecha_vencimiento || '';
    document.getElementById('gpdSslProveedor').value = datos.ssl_proveedor || '';
    document.getElementById('gpdSslNotas').value = datos.ssl_notas || '';
    setTimeout(function () { document.getElementById('gpdDominio').focus(); }, 50);

    var cerrar = function () { div.remove(); };
    document.getElementById('gpdCerrar').onclick = cerrar;
    document.getElementById('gpdCancelar').onclick = cerrar;
    document.getElementById('gpdGuardar').onclick = function () {
        var btn = this, error = document.getElementById('gpdError');
        btn.disabled = true;
        gpdPost({
            accion: 'guardar', id: id, proveedor_id: GPD_PROVEEDOR_ID,
            dominio: document.getElementById('gpdDominio').value,
            fecha_registro: document.getElementById('gpdRegistro').value,
            fecha_vencimiento: document.getElementById('gpdVence').value,
            estado: document.getElementById('gpdEstado').value === '1' ? '1' : '',
            renovacion_automatica: document.getElementById('gpdRenov').checked ? '1' : '',
            notas: document.getElementById('gpdNotas').value,
            ssl_fecha_vencimiento: document.getElementById('gpdSslVence').value,
            ssl_proveedor: document.getElementById('gpdSslProveedor').value,
            ssl_notas: document.getElementById('gpdSslNotas').value
        }).then(function (data) {
            btn.disabled = false;
            if (data.ok) { location.reload(); }
            else { error.textContent = data.error || 'No se pudo guardar.'; error.classList.add('gp-show'); }
        }).catch(function () {
            btn.disabled = false;
            error.textContent = 'Error de conexión. Intente de nuevo.';
            error.classList.add('gp-show');
        });
    };
}

function gpdEliminarDominio(id, nombre) {
    if (!confirm('¿Eliminar el dominio "' + nombre + '"?')) return;
    gpdPost({ accion: 'eliminar', id: id }).then(function (data) {
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
