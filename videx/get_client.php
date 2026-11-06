<?php
/**
 * AJAX Endpoint - Get Client Data
 * Returns client data as JSON for modal/detail operations
 *
 * @package VIDEX
 * @version 1.0
 */

// Start session and include dependencies
session_start();
require_once('funciones.php');
require_once('cliente_config.php');
require_once('cliente_service.php');

// Set JSON header immediately
header('Content-Type: application/json; charset=utf-8');
ob_start();  // Start buffering to prevent any output before JSON

// Authorization check
if (empty($_SESSION['id']) || empty($_SESSION['perfil'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Check if user has permission
if (!in_array($_SESSION['perfil'], ALLOWED_PROFILES)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No tiene permiso']);
    exit;
}

// Get client ID
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

// Fetch client data
$service = new ClienteService();
$client = $service->getClientById($id);

ob_end_clean();  // Clear buffer before sending JSON

if ($client) {
    echo json_encode([
        'success' => true,
        'client' => $client
    ]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
}
?>
