<?php
/**
 * Tickets/Tareas - Acciones AJAX sobre el ticket (hoy: eliminar).
 *
 * Eliminar: solo el solicitante (quien creo el ticket), el encargado (a quien se
 * le asigno) y el administrador (GpTicketService::puedeEliminarTicket). Siempre
 * se valida en backend: ocultar el boton en pantalla es solo comodidad.
 */
session_start();
include_once('funciones.php');
require_once('gp_tickets_config.php');
require_once('gp_ticket_service.php');

header('Content-Type: application/json; charset=utf-8');

function gpt_ajax_error($mensaje, $codigo) {
    http_response_code($codigo);
    echo json_encode(['error' => $mensaje]);
    exit;
}

if (!is_logged_in()) {
    gpt_ajax_error('Sesión expirada. Vuelva a iniciar sesión.', 401);
}
if (!gp_tickets_puede_consultar() && !gp_tickets_puede_modificar()) {
    gpt_ajax_error('No autorizado.', 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gpt_ajax_error('Método no permitido.', 405);
}
if (empty($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
    gpt_ajax_error('Sesión inválida. Recargue la página e intente de nuevo.', 403);
}

$service = new GpTicketService();
$esAdmin = gp_tickets_es_admin();
$idUsuario = (int) $_SESSION['id'];
$accion = $_POST['accion'] ?? '';

if ($accion === 'eliminar') {
    $ticketId = (int) ($_POST['id'] ?? 0);
    $ticket = $ticketId > 0 ? $service->getTicket($ticketId) : null;
    // Mismo mensaje si no existe, ya fue eliminado o no tiene permiso: no revelar que existe.
    if (!$ticket || !$service->puedeEliminarTicket($ticket, $idUsuario, $esAdmin)) {
        gpt_ajax_error('No tiene permiso para eliminar este ticket, o ya no existe.', 403);
    }
    if (!$service->eliminarTicket($ticketId, $idUsuario, $esAdmin)) {
        gpt_ajax_error('No se pudo eliminar el ticket. Intente de nuevo.', 500);
    }
    echo json_encode(['ok' => true, 'id' => $ticketId, 'titulo' => $ticket['titulo']]);
    exit;
}

gpt_ajax_error('Acción inválida.', 400);
