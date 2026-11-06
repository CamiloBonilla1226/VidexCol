<?php
/**
 * Contactos AJAX Endpoint - Get contact data
 * Returns JSON response for modal/AJAX operations
 *
 * @package VIDEX
 * @version 1.0
 */

session_start();

require_once('funciones.php');       // defines DBbase_Sql
require_once('contactos_config.php');
require_once('contactos_service.php');

header('Content-Type: application/json');

// Authorization check
if(empty($_SESSION['id']) || empty($_SESSION['perfil'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if(!in_array($_SESSION['perfil'], ALLOWED_PROFILES)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

$id = intval($_GET['id'] ?? 0);

if($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    $service = new ContactosService();
    $contact = $service->getContactById($id);

    if(!$contact) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Contact not found']);
        exit;
    }

    // Get associated groups
    $groups = $service->getContactGroups($id);

    echo json_encode([
        'success' => true,
        'contact' => $contact,
        'groups' => $groups
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
    exit;
}
?>
