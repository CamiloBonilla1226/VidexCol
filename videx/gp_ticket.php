<?php
require_once('gp_tickets_config.php');
require_once('gp_ticket_service.php');
require_once('gp_ticket_mail.php');
gp_tickets_requerir_acceso();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$service = new GpTicketService();

$opc = isset($_GET['opc']) ? (int) $_GET['opc'] : 1;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$esEdicion = ($id > 0);
$mensaje = '';
$error = '';
$idUsuarioActual = (int) $_SESSION['id'];
$esAdmin = gp_tickets_es_admin();
$puedeCrear = gp_tickets_puede_modificar();
$puedeModificarFechas = gp_tickets_puede_modificar_fechas();

function gpt_dt_a_html($valorMysql) {
    if (!$valorMysql) { return ''; }
    return str_replace(' ', 'T', substr($valorMysql, 0, 16));
}

function gpt_dt_a_mysql($valorHtml) {
    if ($valorHtml === '') { return ''; }
    return str_replace('T', ' ', $valorHtml) . ':00';
}

function gpt_avisar_colaboradores_nuevos($service, $ticket, $idsNuevos) {
    if (empty($idsNuevos)) { return; }
    $emails = [];
    foreach ($idsNuevos as $uid) {
        $email = $service->getUsuarioEmail($uid);
        if ($email) { $emails[] = $email; }
    }
    if (empty($emails)) { return; }
    $urlTicket = 'https://videx.sms-app.co/index.php?doc=gp_ticket&opc=2&id=' . (int) $ticket['id'];
    $cuerpo = '<p>Fue agregado como <strong>colaborador</strong> en el ticket <strong>' . htmlspecialchars($ticket['titulo']) . '</strong>.</p>'
        . '<p>Podrá ver el ticket y escribir en el seguimiento.</p>';
    gp_ticket_enviar_correo($emails, 'Fue agregado como colaborador: ' . $ticket['titulo'], gp_ticket_plantilla_correo('Nuevo colaborador', $cuerpo, $urlTicket));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['funcion'] ?? '') === 'guardar_ticket') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Error de validación CSRF.';
    } elseif ((int) ($_POST['id'] ?? 0) === 0 && !$puedeCrear) {
        $error = 'No tiene permiso para crear tickets.';
    } elseif ((int) ($_POST['id'] ?? 0) > 0 && !$service->puedeEditarTicket($service->getTicketVisible((int) $_POST['id'], $idUsuarioActual, $esAdmin), $idUsuarioActual, $esAdmin)) {
        $error = 'No tiene permiso para modificar este ticket. Como colaborador solo puede escribir en el seguimiento.';
    } else {
        $idPost = (int) ($_POST['id'] ?? 0);
        $data = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'estado' => $_POST['estado'] ?? 'solicitado',
            'proyecto_id' => (int) ($_POST['proyecto_id'] ?? 0),
            'asignado_a' => (int) ($_POST['asignado_a'] ?? 0),
            'colaboradores' => array_map('intval', (array) ($_POST['colaboradores'] ?? [])),
        ];
        // Fecha estimada de entrega: la puede tocar cualquier editor del ticket
        // (solicitante/encargado/admin), no requiere el permiso especial de fechas.
        // El campo solo se envia en el formulario cuando $puedeModificar es cierto
        // (ver mas abajo), asi que si llega aqui es porque el que guarda ya es editor.
        if (array_key_exists('fecha_entrega_estimada', $_POST)) {
            $data['fecha_entrega_estimada'] = trim($_POST['fecha_entrega_estimada'] ?? '');
        }
        if ($puedeModificarFechas) {
            $data['creacion_fecha_manual'] = gpt_dt_a_mysql($_POST['creacion_fecha_manual'] ?? '');
            $data['inicio_fecha_manual'] = gpt_dt_a_mysql($_POST['inicio_fecha_manual'] ?? '');
            $data['fin_fecha_manual'] = gpt_dt_a_mysql($_POST['fin_fecha_manual'] ?? '');
        }

        if ($data['titulo'] === '' || !array_key_exists($data['estado'], GP_TICKET_ESTADOS)) {
            $error = 'El título y el estado son obligatorios.';
        } else {
            $resultado = $service->guardarTicket($idPost, $data, $idUsuarioActual, $puedeModificarFechas, $esAdmin);
            if ($resultado) {
                $nuevoId = $resultado['id'];
                if ($idPost === 0) {
                    // Crear-luego-redirigir: evita duplicados si un reload posterior
                    // (ej. al enviar un mensaje del chat) volviera a hacer POST aquí.
                    $ticketCreado = $service->getTicket($nuevoId);
                    $destinatarios = [];
                    if ($ticketCreado['solicitado_por'] != $_SESSION['id']) {
                        $email = $service->getUsuarioEmail($ticketCreado['solicitado_por']);
                        if ($email) { $destinatarios[] = $email; }
                    } else {
                        $email = $service->getUsuarioEmail($_SESSION['id']);
                        if ($email) { $destinatarios[] = $email; }
                    }
                    if ($ticketCreado['asignado_a']) {
                        $email = $service->getUsuarioEmail($ticketCreado['asignado_a']);
                        if ($email) { $destinatarios[] = $email; }
                    }
                    if (!empty($destinatarios)) {
                        $urlTicket = 'https://videx.sms-app.co/index.php?doc=gp_ticket&opc=2&id=' . $nuevoId;
                        $cuerpo = '<p>Se creó un nuevo ticket: <strong>' . htmlspecialchars($ticketCreado['titulo']) . '</strong></p>'
                            . '<p>Solicitado por: ' . htmlspecialchars($ticketCreado['solicitado_por_nombre']) . '</p>'
                            . ($ticketCreado['asignado_a_nombre'] ? '<p>Asignado a: ' . htmlspecialchars($ticketCreado['asignado_a_nombre']) . '</p>' : '');
                        gp_ticket_enviar_correo($destinatarios, 'Nuevo ticket creado: ' . $ticketCreado['titulo'], gp_ticket_plantilla_correo('Nuevo ticket', $cuerpo, $urlTicket));
                    }
                    gpt_avisar_colaboradores_nuevos($service, $ticketCreado, $resultado['colaboradores_nuevos']);
                    echo '<script>window.location.replace("index.php?doc=gp_ticket&opc=2&id=' . (int) $nuevoId . '");</script>';
                    return;
                }

                $id = $idPost;
                $esEdicion = true;
                $mensaje = 'Ticket actualizado correctamente.';

                $ticketActualizado = $service->getTicket($id);
                gpt_avisar_colaboradores_nuevos($service, $ticketActualizado, $resultado['colaboradores_nuevos']);
                $destinatarios = [];
                if ($ticketActualizado['solicitado_por'] != $_SESSION['id']) {
                    $email = $service->getUsuarioEmail($ticketActualizado['solicitado_por']);
                    if ($email) { $destinatarios[] = $email; }
                }
                if ($ticketActualizado['asignado_a'] && $ticketActualizado['asignado_a'] != $_SESSION['id']) {
                    $email = $service->getUsuarioEmail($ticketActualizado['asignado_a']);
                    if ($email) { $destinatarios[] = $email; }
                }
                if (!empty($destinatarios)) {
                    $urlTicket = 'https://videx.sms-app.co/index.php?doc=gp_ticket&opc=2&id=' . $id;
                    if ($resultado['estado_anterior'] !== $resultado['estado_nuevo']) {
                        $cuerpo = '<p>El ticket <strong>' . htmlspecialchars($ticketActualizado['titulo']) . '</strong> cambió de estado:</p>'
                            . '<p>' . (GP_TICKET_ESTADOS[$resultado['estado_anterior']] ?? $resultado['estado_anterior']) . ' → <strong>' . (GP_TICKET_ESTADOS[$resultado['estado_nuevo']] ?? $resultado['estado_nuevo']) . '</strong></p>';
                        gp_ticket_enviar_correo($destinatarios, 'Cambio de estado: ' . $ticketActualizado['titulo'], gp_ticket_plantilla_correo('Cambio de estado', $cuerpo, $urlTicket));
                    } else {
                        $cuerpo = '<p>Se actualizó la información del ticket <strong>' . htmlspecialchars($ticketActualizado['titulo']) . '</strong>.</p>';
                        gp_ticket_enviar_correo($destinatarios, 'Ticket actualizado: ' . $ticketActualizado['titulo'], gp_ticket_plantilla_correo('Ticket actualizado', $cuerpo, $urlTicket));
                    }
                }
            } else {
                $error = 'No se pudo guardar el ticket.';
            }
        }
    }
}

$ticket = $esEdicion ? $service->getTicketVisible($id, $idUsuarioActual, $esAdmin) : null;
if ($esEdicion && !$ticket) {
    // Mismo mensaje si no existe o si no tiene acceso: no revelar que existe.
    echo '<div class="gpt-wrap"><p>Ticket no encontrado o no tiene acceso a él.</p><p><a href="index.php?doc=gp_tickets">Volver a Tickets / Tareas</a></p></div>';
    return;
}
if (!$esEdicion && !$puedeCrear) {
    echo '<div class="gpt-wrap"><p>No tiene permiso para crear tickets.</p><p><a href="index.php?doc=gp_tickets">Volver a Tickets / Tareas</a></p></div>';
    return;
}

// Editores (admin/solicitante/encargado, o quien crea uno nuevo) cambian la informacion;
// el colaborador solo ve los datos y usa el seguimiento.
$puedeModificar = $esEdicion ? $service->puedeEditarTicket($ticket, $idUsuarioActual, $esAdmin) : $puedeCrear;
// Las fechas manuales solo las puede tocar un editor del ticket que ademas tenga el permiso especial.
$puedeModificarFechas = $puedeModificarFechas && $puedeModificar;
$rolEnTicket = $esEdicion ? $service->rolEnTicket($ticket, $idUsuarioActual, $esAdmin) : '';
$semaforo = $esEdicion ? gp_tickets_semaforo($ticket['fecha_entrega_estimada'] ?? null, $ticket['estado'] ?? null) : null;
$colaboradores = $esEdicion ? $service->listarColaboradores($id) : [];
$idsColaboradores = array_map('intval', array_column($colaboradores, 'id'));

$proyectos = $service->listarProyectosParaSelect();
$usuarios = $service->listarUsuariosParaSelect();
$mensajes = $esEdicion ? $service->listarMensajes($id) : [];

function gptIniciales2($nombre) {
    $partes = preg_split('/\s+/', trim((string) $nombre));
    $iniciales = '';
    foreach (array_slice($partes, 0, 2) as $p) {
        $iniciales .= strtoupper(substr($p, 0, 1));
    }
    return $iniciales ?: '?';
}
?>
<link rel="stylesheet" href="gp_tickets.css?v=<?= @filemtime('gp_tickets.css'); ?>" />

<div class="gpt-wrap">
    <div class="gpt-breadcrumb">
        <a href="index.php?doc=gp_tickets">Tickets / Tareas</a> &nbsp;›&nbsp;
        <span><?= $esEdicion ? htmlspecialchars($ticket['titulo'], ENT_QUOTES) : 'Nuevo ticket'; ?></span>
    </div>

    <?php if ($esEdicion && $rolEnTicket === 'Colaborador') { ?>
        <div class="alert alert-info" style="font-size:13px;"><i class="bi bi-people"></i> Está como <strong>colaborador</strong> en este ticket: puede ver la información y escribir en el seguimiento, pero no modificar el ticket.</div>
    <?php } ?>
    <?php if ($mensaje) { ?><div class="alert alert-success"><?= htmlspecialchars($mensaje, ENT_QUOTES); ?></div><?php } ?>
    <?php if ($error) { ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES); ?></div><?php } ?>

    <div class="gpt-detalle-layout">
        <div class="gpt-panel">
            <form method="post" id="gptFormTicket">
                <input type="hidden" name="funcion" value="guardar_ticket" />
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>" />
                <input type="hidden" name="id" value="<?= $id; ?>" />

                <div class="gpt-titulo-header">
                    <?php if ($puedeModificar) { ?>
                        <input type="text" name="titulo" class="gpt-select-titulo" placeholder="Título del ticket" value="<?= htmlspecialchars($ticket['titulo'] ?? '', ENT_QUOTES); ?>" required />
                    <?php } else { ?>
                        <div class="gpt-select-titulo"><?= htmlspecialchars($ticket['titulo'] ?? '', ENT_QUOTES); ?></div>
                    <?php } ?>

                    <?php
                    $estadoActual = $ticket['estado'] ?? 'solicitado';
                    $coloresEstado = [
                        'solicitado' => ['bg' => '#f1f5f9', 'color' => '#475569'],
                        'revisado_pendiente' => ['bg' => '#fef3c7', 'color' => '#b45309'],
                        'en_ejecucion' => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
                        'finalizado' => ['bg' => '#dcfce7', 'color' => '#15803d'],
                    ];
                    $c = $coloresEstado[$estadoActual];
                    ?>
                    <?php if ($puedeModificar) { ?>
                        <select name="estado" id="gptEstadoSelect" class="gpt-estado-select" style="background-color:<?= $c['bg']; ?>;color:<?= $c['color']; ?>;">
                            <?php foreach (GP_TICKET_ESTADOS as $k => $v) { ?>
                                <option value="<?= $k; ?>" data-bg="<?= $coloresEstado[$k]['bg']; ?>" data-color="<?= $coloresEstado[$k]['color']; ?>" <?= $estadoActual === $k ? 'selected' : ''; ?>><?= $v; ?></option>
                            <?php } ?>
                        </select>
                    <?php } else { ?>
                        <span class="gpt-badge-estado gpt-badge-<?= $estadoActual; ?>"><?= GP_TICKET_ESTADOS[$estadoActual]; ?></span>
                    <?php } ?>
                </div>

                <div class="gpt-field">
                    <label>Descripción</label>
                    <?php if ($puedeModificar) { ?>
                        <textarea name="descripcion" placeholder="Describe el ticket con el mayor detalle posible..."><?= htmlspecialchars($ticket['descripcion'] ?? '', ENT_QUOTES); ?></textarea>
                    <?php } else { ?>
                        <div class="gpt-field-readonly"><?= nl2br(htmlspecialchars($ticket['descripcion'] ?? '', ENT_QUOTES)); ?></div>
                    <?php } ?>
                </div>

                <div class="gpt-field">
                    <label>Proyecto relacionado</label>
                    <?php if ($puedeModificar) { ?>
                        <select name="proyecto_id" id="gptProyectoSelect">
                            <option value="">Sin proyecto relacionado</option>
                            <?php foreach ($proyectos as $p) { ?>
                                <option value="<?= (int) $p['id']; ?>" data-desc="<?= htmlspecialchars($p['descripcion'], ENT_QUOTES); ?>" <?= (int) ($ticket['proyecto_id'] ?? (int) ($_GET['proyecto_id'] ?? 0)) === (int) $p['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($p['nombre'], ENT_QUOTES); ?></option>
                            <?php } ?>
                        </select>
                        <p class="gpt-proyecto-desc" id="gptProyectoDesc"><?= htmlspecialchars($ticket['proyecto_descripcion'] ?? '', ENT_QUOTES); ?></p>
                    <?php } else { ?>
                        <div class="gpt-field-readonly"><?= $ticket['proyecto_nombre'] ? htmlspecialchars($ticket['proyecto_nombre'], ENT_QUOTES) : '—'; ?></div>
                    <?php } ?>
                </div>

                <div class="gpt-meta-grid">
                    <div class="gpt-meta-card">
                        <label><i class="bi bi-person"></i> Solicitado por</label>
                        <div class="gpt-meta-valor"><?= $esEdicion ? htmlspecialchars($ticket['solicitado_por_nombre'], ENT_QUOTES) : htmlspecialchars($_SESSION['nombre'], ENT_QUOTES); ?></div>
                    </div>
                    <div class="gpt-meta-card">
                        <label><i class="bi bi-person-check"></i> Asignado a</label>
                        <?php if ($puedeModificar) { ?>
                            <select name="asignado_a">
                                <option value="">Sin asignar</option>
                                <?php foreach ($usuarios as $u) { ?>
                                    <option value="<?= (int) $u['id']; ?>" <?= (int) ($ticket['asignado_a'] ?? 0) === (int) $u['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($u['nombre'], ENT_QUOTES); ?></option>
                                <?php } ?>
                            </select>
                        <?php } else { ?>
                            <div class="gpt-meta-valor"><?= $ticket['asignado_a_nombre'] ? htmlspecialchars($ticket['asignado_a_nombre'], ENT_QUOTES) : 'Sin asignar'; ?></div>
                        <?php } ?>
                    </div>
                    <div class="gpt-meta-card gpt-colab-card">
                        <label><i class="bi bi-people"></i> Colaboradores <span class="gpt-colab-nota">(solo seguimiento)</span></label>
                        <?php if ($puedeModificar) { ?>
                            <input type="text" id="gptColabFiltro" class="gpt-colab-filtro" placeholder="Buscar usuario..." autocomplete="off" />
                            <div class="gpt-colab-lista" id="gptColabLista">
                                <?php foreach ($usuarios as $u) { ?>
                                    <label class="gpt-colab-item" data-nombre="<?= htmlspecialchars(mb_strtolower($u['nombre']), ENT_QUOTES); ?>">
                                        <input type="checkbox" name="colaboradores[]" value="<?= (int) $u['id']; ?>" <?= in_array((int) $u['id'], $idsColaboradores, true) ? 'checked' : ''; ?> />
                                        <?= htmlspecialchars($u['nombre'], ENT_QUOTES); ?>
                                    </label>
                                <?php } ?>
                            </div>
                            <div class="gpt-colab-ayuda">El solicitante y el encargado ya tienen acceso completo; no hace falta agregarlos.</div>
                        <?php } else { ?>
                            <div class="gpt-meta-valor">
                                <?php if (empty($colaboradores)) { ?>
                                    —
                                <?php } else { foreach ($colaboradores as $c) { ?>
                                    <span class="gpt-colab-chip"><?= htmlspecialchars($c['nombre'], ENT_QUOTES); ?></span>
                                <?php } } ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="gpt-meta-card">
                        <label><i class="bi bi-calendar-check"></i> Fecha estimada de entrega</label>
                        <?php if ($puedeModificar) { ?>
                            <input type="date" name="fecha_entrega_estimada" value="<?= htmlspecialchars(substr((string) ($ticket['fecha_entrega_estimada'] ?? ''), 0, 10), ENT_QUOTES); ?>" />
                        <?php } else { ?>
                            <div class="gpt-meta-valor"><?= ($esEdicion && $ticket['fecha_entrega_estimada']) ? date('d M Y', strtotime($ticket['fecha_entrega_estimada'])) : 'Sin definir'; ?></div>
                        <?php } ?>
                        <?php if ($semaforo) { ?>
                            <span class="gpt-semaforo gpt-semaforo-<?= $semaforo['color']; ?>">
                                <i class="bi bi-circle-fill"></i>
                                <?= htmlspecialchars($semaforo['label'], ENT_QUOTES); ?>
                                (<?= $semaforo['dias'] >= 0 ? $semaforo['dias'] . ' día' . ($semaforo['dias'] == 1 ? '' : 's') : abs($semaforo['dias']) . ' día' . (abs($semaforo['dias']) == 1 ? '' : 's') . ' tarde'; ?>)
                            </span>
                        <?php } elseif ($esEdicion && $ticket['estado'] === 'finalizado') { ?>
                            <span class="gpt-semaforo gpt-semaforo-neutral"><i class="bi bi-check-circle-fill"></i> Entregado</span>
                        <?php } ?>
                    </div>
                    <div class="gpt-meta-card">
                        <label><i class="bi bi-<?= $puedeModificarFechas ? 'unlock' : 'lock'; ?>"></i> Fecha de creación</label>
                        <?php if ($puedeModificarFechas) { ?>
                            <input type="datetime-local" name="creacion_fecha_manual" value="<?= gpt_dt_a_html($ticket['creacion_fecha'] ?? date('Y-m-d H:i:s')); ?>" />
                        <?php } else { ?>
                            <div class="gpt-meta-valor"><?= $esEdicion ? date('d M Y, h:i A', strtotime($ticket['creacion_fecha'])) : 'Se asignará al crear'; ?></div>
                        <?php } ?>
                    </div>
                    <div class="gpt-meta-card">
                        <label><i class="bi bi-<?= $puedeModificarFechas ? 'unlock' : 'lock'; ?>"></i> Fecha de inicio</label>
                        <?php if ($puedeModificarFechas) { ?>
                            <input type="datetime-local" name="inicio_fecha_manual" value="<?= gpt_dt_a_html($ticket['inicio_fecha'] ?? ''); ?>" />
                        <?php } else { ?>
                            <div class="gpt-meta-valor"><?= ($esEdicion && $ticket['inicio_fecha']) ? date('d M Y, h:i A', strtotime($ticket['inicio_fecha'])) : 'Se llenará al iniciar'; ?></div>
                        <?php } ?>
                    </div>
                    <div class="gpt-meta-card">
                        <label><i class="bi bi-<?= $puedeModificarFechas ? 'unlock' : 'lock'; ?>"></i> Fecha de finalización</label>
                        <?php if ($puedeModificarFechas) { ?>
                            <input type="datetime-local" name="fin_fecha_manual" value="<?= gpt_dt_a_html($ticket['fin_fecha'] ?? ''); ?>" />
                        <?php } else { ?>
                            <div class="gpt-meta-valor"><?= ($esEdicion && $ticket['fin_fecha']) ? date('d M Y, h:i A', strtotime($ticket['fin_fecha'])) : 'Se llenará al finalizar'; ?></div>
                        <?php } ?>
                    </div>
                    <div class="gpt-meta-card">
                        <label><i class="bi bi-pencil-square"></i> Modificado por</label>
                        <div class="gpt-meta-valor"><?= $ticket['modificacion_usuario_nombre'] ?? '—'; ?></div>
                    </div>
                </div>

                <?php if ($puedeModificar) { ?>
                    <div class="gpt-form-actions">
                        <?php if ($esEdicion && $service->puedeEliminarTicket($ticket, $idUsuarioActual, $esAdmin)) { ?>
                            <button type="button" class="gpt-btn gpt-btn-eliminar-ticket" id="gptBtnEliminarTicket" data-titulo="<?= htmlspecialchars($ticket['titulo'], ENT_QUOTES); ?>"><i class="bi bi-trash3"></i> Eliminar ticket</button>
                        <?php } ?>
                        <a href="index.php?doc=gp_tickets" class="gpt-btn gpt-btn-secundario">Cancelar</a>
                        <button type="submit" class="gpt-btn"><i class="bi bi-check-lg"></i> <?= $esEdicion ? 'Guardar cambios' : 'Crear ticket'; ?></button>
                    </div>
                <?php } ?>
            </form>
        </div>

        <?php if ($esEdicion) { ?>
            <div class="gpt-chat-panel">
                <div class="gpt-chat-header"><i class="bi bi-chat-dots"></i> Seguimiento</div>
                <div class="gpt-chat-feed" id="gptChatFeed">
                    <?php foreach ($mensajes as $m) {
                        $propio = (int) $m['usuario_id'] === (int) $_SESSION['id'];
                        if ($m['tipo_evento'] !== 'mensaje') { ?>
                            <div class="gpt-msg-evento"><?= htmlspecialchars($m['mensaje'], ENT_QUOTES); ?></div>
                        <?php continue; }
                        ?>
                        <div class="gpt-msg <?= $propio ? 'gpt-msg-propio' : ''; ?>">
                            <span class="gpt-msg-avatar"><?= gptIniciales2($m['usuario_nombre']); ?></span>
                            <div>
                                <div class="gpt-msg-bubble">
                                    <?php if (!$propio) { ?><div class="gpt-msg-nombre"><?= htmlspecialchars($m['usuario_nombre'], ENT_QUOTES); ?></div><?php } ?>
                                    <?php if ($m['archivo_nombre']) {
                                        $esImagen = strpos($m['archivo_tipo'], 'image/') === 0;
                                        if ($esImagen) { ?>
                                            <img src="gp_ticket_archivo.php?id=<?= (int) $m['id']; ?>&modo=ver" class="gpt-msg-imagen" onclick="gptAbrirLightbox(this.src)" />
                                        <?php } else { ?>
                                            <a href="gp_ticket_archivo.php?id=<?= (int) $m['id']; ?>&modo=descargar" class="gpt-msg-archivo">
                                                <span class="gpt-msg-archivo-icono"><i class="bi bi-file-earmark-text"></i></span>
                                                <span>
                                                    <div class="gpt-msg-archivo-nombre"><?= htmlspecialchars($m['archivo_nombre'], ENT_QUOTES); ?></div>
                                                    <div class="gpt-msg-archivo-tamano"><?= round($m['archivo_tamano'] / 1024); ?> KB</div>
                                                </span>
                                                <i class="bi bi-download gpt-msg-archivo-descarga"></i>
                                            </a>
                                        <?php }
                                    }
                                    if ($m['mensaje']) { ?>
                                        <div class="gpt-msg-texto"><?= htmlspecialchars($m['mensaje'], ENT_QUOTES); ?></div>
                                    <?php } ?>
                                    <div class="gpt-msg-hora"><?= date('h:i A', strtotime($m['creacion_fecha'])); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <?php if ($esEdicion) { ?>
                    <div id="gptArchivoPendiente" class="gpt-archivo-preview-pendiente" style="display:none;">
                        <i class="bi bi-paperclip"></i> <span id="gptArchivoPendienteNombre"></span>
                        <button type="button" onclick="gptQuitarArchivo()">&times;</button>
                    </div>
                    <div class="gpt-chat-input">
                        <input type="file" id="gptArchivoInput" style="display:none;" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp" />
                        <button type="button" class="gpt-chat-btn-icono" onclick="document.getElementById('gptArchivoInput').click()"><i class="bi bi-paperclip"></i></button>
                        <textarea id="gptMensajeInput" rows="1" placeholder="Escribe un mensaje..."></textarea>
                        <button type="button" class="gpt-chat-btn-icono gpt-chat-btn-enviar" onclick="gptEnviarMensaje()"><i class="bi bi-send-fill"></i></button>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>

<div class="gpt-lightbox" id="gptLightbox" onclick="this.classList.remove('gpt-open')">
    <button class="gpt-lightbox-cerrar">&times;</button>
    <img id="gptLightboxImg" src="" />
</div>

<script>
var GPT_TICKET_ID = <?= (int) $id; ?>;

document.getElementById('gptProyectoSelect') && document.getElementById('gptProyectoSelect').addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    document.getElementById('gptProyectoDesc').textContent = opt.getAttribute('data-desc') || '';
});

document.getElementById('gptEstadoSelect') && document.getElementById('gptEstadoSelect').addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    this.style.backgroundColor = opt.getAttribute('data-bg');
    this.style.color = opt.getAttribute('data-color');
});

var gptColabFiltro = document.getElementById('gptColabFiltro');
if (gptColabFiltro) {
    gptColabFiltro.addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        document.querySelectorAll('#gptColabLista .gpt-colab-item').forEach(function (el) {
            el.style.display = (q === '' || el.getAttribute('data-nombre').indexOf(q) !== -1) ? '' : 'none';
        });
    });
}

function gptAbrirLightbox(src) {
    document.getElementById('gptLightboxImg').src = src;
    document.getElementById('gptLightbox').classList.add('gpt-open');
}

function gptIniciales(nombre) {
    var partes = (nombre || '?').trim().split(/\s+/).slice(0, 2);
    return partes.map(function (p) { return p.charAt(0).toUpperCase(); }).join('') || '?';
}

function gptEscapar(texto) {
    var div = document.createElement('div');
    div.textContent = texto || '';
    return div.innerHTML;
}

var gptArchivoSeleccionado = null;
var gptArchivoInput = document.getElementById('gptArchivoInput');
if (gptArchivoInput) {
    gptArchivoInput.addEventListener('change', function () {
        if (this.files.length) {
            gptArchivoSeleccionado = this.files[0];
            document.getElementById('gptArchivoPendienteNombre').textContent = gptArchivoSeleccionado.name;
            document.getElementById('gptArchivoPendiente').style.display = 'flex';
        }
    });
}

function gptQuitarArchivo() {
    gptArchivoSeleccionado = null;
    gptArchivoInput.value = '';
    document.getElementById('gptArchivoPendiente').style.display = 'none';
}

var gptUltimoIdRenderizado = <?= !empty($mensajes) ? (int) end($mensajes)['id'] : 0; ?>;
(function () {
    var feed = document.getElementById('gptChatFeed');
    if (feed) {
        feed.scrollTop = feed.scrollHeight;
    }
})();

function gptRenderMensaje(m) {
    var feed = document.getElementById('gptChatFeed');
    if (!feed) return;
    if (m.tipo_evento !== 'mensaje') {
        var evt = document.createElement('div');
        evt.className = 'gpt-msg-evento';
        evt.textContent = m.mensaje;
        feed.appendChild(evt);
        return;
    }
    var wrap = document.createElement('div');
    wrap.className = 'gpt-msg' + (m.propio ? ' gpt-msg-propio' : '');
    var interior = '<span class="gpt-msg-avatar">' + gptIniciales(m.usuario_nombre) + '</span><div><div class="gpt-msg-bubble">';
    if (!m.propio) interior += '<div class="gpt-msg-nombre">' + gptEscapar(m.usuario_nombre) + '</div>';
    if (m.archivo_nombre) {
        var esImagen = (m.archivo_tipo || '').indexOf('image/') === 0;
        if (esImagen) {
            interior += '<img src="gp_ticket_archivo.php?id=' + m.archivo_id + '&modo=ver" class="gpt-msg-imagen" onclick="gptAbrirLightbox(this.src)" />';
        } else {
            interior += '<a href="gp_ticket_archivo.php?id=' + m.archivo_id + '&modo=descargar" class="gpt-msg-archivo">' +
                '<span class="gpt-msg-archivo-icono"><i class="bi bi-file-earmark-text"></i></span>' +
                '<span><div class="gpt-msg-archivo-nombre">' + gptEscapar(m.archivo_nombre) + '</div><div class="gpt-msg-archivo-tamano">' + Math.round(m.archivo_tamano / 1024) + ' KB</div></span>' +
                '<i class="bi bi-download gpt-msg-archivo-descarga"></i></a>';
        }
    }
    if (m.mensaje) {
        interior += '<div class="gpt-msg-texto">' + gptEscapar(m.mensaje) + '</div>';
    }
    interior += '<div class="gpt-msg-hora">' + m.fecha.split(', ')[1] + '</div></div></div>';
    wrap.innerHTML = interior;
    feed.appendChild(wrap);
    feed.scrollTop = feed.scrollHeight;
    gptUltimoIdRenderizado = Math.max(gptUltimoIdRenderizado, m.id);
}

function gptEnviarMensaje() {
    var texto = document.getElementById('gptMensajeInput').value.trim();
    if (texto === '' && !gptArchivoSeleccionado) return;
    var body = new FormData();
    body.append('accion', 'enviar');
    body.append('ticket_id', GPT_TICKET_ID);
    body.append('mensaje', texto);
    if (gptArchivoSeleccionado) body.append('archivo', gptArchivoSeleccionado);
    fetch('gp_ticket_mensaje_ajax.php', { method: 'POST', body: body })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) {
                gptRenderMensaje(data.item);
                document.getElementById('gptMensajeInput').value = '';
                gptQuitarArchivo();
            } else {
                alert(data.error || 'No se pudo enviar.');
            }
        });
}

document.getElementById('gptMensajeInput') && document.getElementById('gptMensajeInput').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        gptEnviarMensaje();
    }
});

if (GPT_TICKET_ID) {
    setInterval(function () {
        fetch('gp_ticket_mensaje_ajax.php?accion=listar&ticket_id=' + GPT_TICKET_ID)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) return;
                data.items.forEach(function (m) {
                    if (m.id > gptUltimoIdRenderizado) {
                        gptRenderMensaje(m);
                    }
                });
            });
    }, 10000);
}
</script>

<?php if ($esEdicion && $service->puedeEliminarTicket($ticket, $idUsuarioActual, $esAdmin)) { ?>
<script src="gp_tickets_eliminar.js?v=<?= @filemtime('gp_tickets_eliminar.js'); ?>"></script>
<script>
document.getElementById('gptBtnEliminarTicket').addEventListener('click', function () {
    gptEliminarTicket({
        id: <?= (int) $id; ?>,
        titulo: this.getAttribute('data-titulo'),
        csrf: '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>',
        redirect: 'index.php?doc=gp_tickets'
    });
});
</script>
<?php } ?>
