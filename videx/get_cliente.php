<?php
/**
 * Cliente AJAX Endpoint - Get Single Client
 * Returns full client data for modal editing
 *
 * @package VIDEX
 * @version 1.0
 */

error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Verify authentication
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Get client ID from GET parameter
$client_id = intval($_GET['id'] ?? 0);

if ($client_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid client ID']);
    exit;
}

// Load database layer (defines DBbase_Sql) and service layer
require_once('funciones.php');
require_once('cliente_service.php');
$service = new ClienteService();

// Fetch client data
$client = $service->getClientById($client_id);

if (!$client) {
    echo json_encode(['success' => false, 'message' => 'Client not found']);
    exit;
}

// Return success with client data
echo json_encode([
    'success' => true,
    'data' => $client
]);
exit;
?>
