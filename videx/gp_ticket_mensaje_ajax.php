<?php
session_start();
include_once('funciones.php');
require_once('gp_tickets_config.php');
require_once('gp_ticket_service.php');
require_once('gp_ticket_mail.php');

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['error' => 'No autorizado.']);
    exit;
}
if (!gp_tickets_puede_consultar() && !gp_tickets_puede_modificar()) {
    echo json_encode(['error' => 'No autorizado.']);
    exit;
}

$service = new GpTicketService();
$accion = $_REQUEST['accion'] ?? '';

function gpt_formatear_mensaje($m, $idUsuarioActual) {
    return [
        'id' => (int) $m['id'],
        'usuario_id' => (int) $m['usuario_id'],
        'usuario_nombre' => $m['usuario_nombre'],
        'propio' => (int) $m['usuario_id'] === (int) $idUsuarioActual,
        'mensaje' => $m['mensaje'],
        'archivo_nombre' => $m['archivo_nombre'],
        'archivo_tipo' => $m['archivo_tipo'],
        'archivo_tamano' => $m['archivo_tamano'] !== null ? (int) $m['archivo_tamano'] : null,
        'archivo_id' => $m['archivo_nombre'] ? (int) $m['id'] : null,
        'tipo_evento' => $m['tipo_evento'],
        'fecha' => date('d M Y, h:i A', strtotime($m['creacion_fecha'])),
    ];
}

$esAdmin = gp_tickets_es_admin();

/*
 * Ver o escribir en el seguimiento: admin, solicitante, encargado y colaboradores.
 * Cualquier otro usuario recibe 403 aunque tenga acceso al modulo.
 */
function gpt_ticket_o_403($service, $ticketId, $esAdmin) {
    $ticket = $service->getTicketVisible($ticketId, $_SESSION['id'], $esAdmin);
    if (!$ticket) {
        http_response_code(403);
        echo json_encode(['error' => 'No tiene acceso a este ticket.']);
        exit;
    }
    return $ticket;
}

if ($accion === 'listar') {
    $ticketId = (int) ($_GET['ticket_id'] ?? 0);
    gpt_ticket_o_403($service, $ticketId, $esAdmin);
    $mensajes = $service->listarMensajes($ticketId);
    $formateados = array_map(function ($m) { return gpt_formatear_mensaje($m, $_SESSION['id']); }, $mensajes);
    echo json_encode(['ok' => true, 'items' => $formateados]);
    exit;
}

if ($accion === 'enviar') {
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $texto = trim($_POST['mensaje'] ?? '');
    $ticket = gpt_ticket_o_403($service, $ticketId, $esAdmin);

    $archivoData = null;
    if (!empty($_FILES['archivo']['name'])) {
        $archivo = $_FILES['archivo'];
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'Error al subir el archivo.']);
            exit;
        }
        if ($archivo['size'] > GP_TICKET_ARCHIVO_MAX_BYTES) {
            echo json_encode(['error' => 'El archivo supera el tamaño máximo permitido (10 MB).']);
            exit;
        }
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!isset(GP_TICKET_ARCHIVOS_PERMITIDOS[$ext])) {
            echo json_encode(['error' => 'Tipo de archivo no permitido. Solo PDF, Word, Excel e imágenes.']);
            exit;
        }
        $carpeta = __DIR__ . '/archivos/tickets/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }
        // Los adjuntos solo se sirven por gp_ticket_archivo.php (que valida el acceso al ticket).
        if (!is_file($carpeta . '.htaccess')) {
            @file_put_contents($carpeta . '.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n");
        }
        $nombreGuardado = 'tk' . $ticketId . '_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($archivo['tmp_name'], $carpeta . $nombreGuardado)) {
            echo json_encode(['error' => 'No se pudo guardar el archivo.']);
            exit;
        }
        $archivoData = [
            'nombre' => $archivo['name'],
            'ruta' => 'archivos/tickets/' . $nombreGuardado,
            'tipo' => GP_TICKET_ARCHIVOS_PERMITIDOS[$ext],
            'tamano' => $archivo['size'],
        ];
    }

    if ($texto === '' && !$archivoData) {
        echo json_encode(['error' => 'Escribe un mensaje o adjunta un archivo.']);
        exit;
    }

    $nuevoId = $service->guardarMensaje($ticketId, $_SESSION['id'], $texto, $archivoData, 'mensaje');
    if (!$nuevoId) {
        echo json_encode(['error' => 'No se pudo guardar el mensaje.']);
        exit;
    }

    // Notificar por correo a solicitante y asignado (excepto a quien escribió).
    $destinatarios = [];
    if ($ticket['solicitado_por'] != $_SESSION['id']) {
        $email = $service->getUsuarioEmail($ticket['solicitado_por']);
        if ($email) { $destinatarios[] = $email; }
    }
    if ($ticket['asignado_a'] && $ticket['asignado_a'] != $_SESSION['id']) {
        $email = $service->getUsuarioEmail($ticket['asignado_a']);
        if ($email) { $destinatarios[] = $email; }
    }
    foreach ($service->listarColaboradores($ticketId) as $colaborador) {
        if ((int) $colaborador['id'] !== (int) $_SESSION['id'] && $colaborador['email']) {
            $destinatarios[] = $colaborador['email'];
        }
    }
    $destinatarios = array_values(array_unique($destinatarios));
    if (!empty($destinatarios)) {
        $urlTicket = 'https://videx.sms-app.co/index.php?doc=gp_ticket&opc=2&id=' . $ticketId;
        $cuerpo = '<p><strong>' . htmlspecialchars($_SESSION['nombre']) . '</strong> agregó una actualización en el ticket <strong>' . htmlspecialchars($ticket['titulo']) . '</strong>:</p>';
        $cuerpo .= $texto !== '' ? '<p style="background:#f8fafc;padding:10px;border-radius:6px;">' . nl2br(htmlspecialchars($texto)) . '</p>' : '';
        $cuerpo .= $archivoData ? '<p>📎 Archivo adjunto: ' . htmlspecialchars($archivoData['nombre']) . '</p>' : '';
        gp_ticket_enviar_correo($destinatarios, 'Nueva actualización en el ticket: ' . $ticket['titulo'], gp_ticket_plantilla_correo('Actualización de seguimiento', $cuerpo, $urlTicket));
    }

    $mensaje = $service->getMensaje($nuevoId);
    $mensaje['usuario_nombre'] = $_SESSION['nombre'];
    echo json_encode(['ok' => true, 'item' => gpt_formatear_mensaje($mensaje, $_SESSION['id'])]);
    exit;
}

echo json_encode(['error' => 'Acción inválida.']);
