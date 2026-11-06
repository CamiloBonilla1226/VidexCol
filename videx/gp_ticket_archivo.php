<?php
session_start();
include_once('funciones.php');
require_once('gp_tickets_config.php');
require_once('gp_ticket_service.php');

if (!is_logged_in()) {
    http_response_code(401);
    die('No autorizado.');
}
if (!gp_tickets_puede_consultar() && !gp_tickets_puede_modificar()) {
    http_response_code(403);
    die('No autorizado.');
}

$service = new GpTicketService();
$id = (int) ($_GET['id'] ?? 0);
$modo = ($_GET['modo'] ?? 'descargar') === 'ver' ? 'ver' : 'descargar';

$mensaje = $service->getMensaje($id);
if (!$mensaje || !$mensaje['archivo_ruta']) {
    http_response_code(404);
    die('Archivo no encontrado.');
}

// Solo quien puede ver el ticket al que pertenece el mensaje puede abrir el adjunto.
if (!$service->getTicketVisible((int) $mensaje['ticket_id'], $_SESSION['id'], gp_tickets_es_admin())) {
    http_response_code(403);
    die('No autorizado.');
}

$rutaCompleta = __DIR__ . '/' . $mensaje['archivo_ruta'];
if (!is_file($rutaCompleta)) {
    http_response_code(404);
    die('Archivo no encontrado en el servidor.');
}

header('Content-Type: ' . $mensaje['archivo_tipo']);
header('Content-Length: ' . filesize($rutaCompleta));
if ($modo === 'descargar') {
    header('Content-Disposition: attachment; filename="' . basename($mensaje['archivo_nombre']) . '"');
} else {
    header('Content-Disposition: inline; filename="' . basename($mensaje['archivo_nombre']) . '"');
}
header('X-Content-Type-Options: nosniff');
readfile($rutaCompleta);
exit;
