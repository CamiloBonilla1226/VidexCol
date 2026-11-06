<?php
/**
 * Alertas de vencimiento - endpoint AJAX (activar/desactivar/eliminar destinatarios).
 * Requiere sesion, permiso de modificacion de Gestion Proyectos y token CSRF.
 */
session_start();
include_once('funciones.php');
require_once('gp_config.php');
require_once('gp_alertas_service.php');

header('Content-Type: application/json; charset=utf-8');

function gpa_responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

if (!is_logged_in()) {
    gpa_responder(['error' => 'No autorizado.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gpa_responder(['error' => 'Método no permitido.'], 405);
}
if (empty($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
    gpa_responder(['error' => 'Sesión expirada. Recargue la página.'], 403);
}
gp_requerir_modificacion();

$service = new GpAlertasService();
$accion = $_POST['accion'] ?? '';

if ($accion === 'toggle_destinatario') {
    $id = (int) ($_POST['id'] ?? 0);
    $activo = !empty($_POST['activo']);
    if ($id <= 0) {
        gpa_responder(['error' => 'Destinatario no encontrado.'], 404);
    }
    $service->cambiarActivoDestinatario($id, $activo) ? gpa_responder(['ok' => true]) : gpa_responder(['error' => 'No se pudo actualizar.'], 500);
}

if ($accion === 'eliminar_destinatario') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        gpa_responder(['error' => 'Destinatario no encontrado.'], 404);
    }
    $service->eliminarDestinatario($id) ? gpa_responder(['ok' => true]) : gpa_responder(['error' => 'No se pudo eliminar.'], 500);
}

gpa_responder(['error' => 'Acción inválida.'], 400);
