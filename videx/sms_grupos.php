<?php
/**
 * SMS Grupos Module Wrapper
 * Refactored from 505 monolithic lines to 80-line thin wrapper
 * Delegates to modular layers: config + service + controller + form
 */

// Check Authorization
if ($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 4 || $_SESSION["perfil"] == 160) {
    die("<h1>No está autorizado para ver esta información</h1>");
}

// Load modular layers
require_once('sms_grupos_config.php');
require_once('sms_grupos_service.php');
require_once('sms_grupos_controller.php');
require_once('sms_grupos_form.php');

// Initialize controller
$controller = new SmsGruposController();
$controller->authorize();

// Determine operation from URL parameters
$opc = intval($_GET['opc'] ?? 2);
$id = intval($_GET['id'] ?? 0);
$pagina = intval($_GET['pagina'] ?? 1);

// Build filters array from GET parameters
$filters = [
    'nombre' => $_GET['nombre'] ?? '',
];

// Route to appropriate handler based on operation
try {
    if ($opc == 1) {
        // Create new group
        $result = $controller->handleCreate($_POST);
    } elseif ($opc == 2 && !isset($_POST['id'])) {
        // List groups with pagination
        $result = $controller->handleList($pagina, $filters);
    } elseif ($opc == 2 && isset($_POST['id'])) {
        // Edit or special operation on group
        $id = intval($_POST['id']);
        $result = $controller->handleEdit($id, $_POST);

        if (isset($result['mode']) && $result['mode'] === 'edit_success') {
            // Success - refresh the page to show updated list
            $result = $controller->handleList(1, $filters);
        }
    } elseif ($id > 0 && isset($_GET['id'])) {
        // Edit group form display
        $result = $controller->handleEdit($id, $_POST);
    } else {
        // Default to list
        $result = $controller->handleList($pagina, $filters);
    }
} catch (Exception $e) {
    $result = [
        'mode' => 'error',
        'error' => 'Error del sistema: ' . $e->getMessage()
    ];
}

// Render based on mode
$mode = $result['mode'] ?? 'list';
$successMessage = null;

// Check for success message from edit
if ($mode === 'edit_success') {
    $successMessage = $result['message'] ?? 'Operación completada correctamente';
    $mode = 'list'; // Change mode to list and show message
}

echo '<link rel="stylesheet" href="sms-grupos.css?v=1.0">' . "\n";

if ($successMessage) {
    showToastMessage($successMessage, 'success');
}

if ($mode == 'create') {
    renderGroupForm($result['posted_data'] ?? [], 'create', $result['errors'] ?? []);
} elseif ($mode == 'create_success') {
    showToastMessage($result['message'], 'success');
    echo '<script>setTimeout(function() { window.location.href="index.php?doc=sms_grupos&opc=2&id=' . $result['group_id'] . '"; }, 1500);</script>';
} elseif ($mode == 'edit') {
    renderGroupForm($result['group'] ?? [], 'edit', $result['errors'] ?? []);
} elseif ($mode == 'error') {
    showToastMessage($result['error'] ?? 'Error desconocido', 'danger');
    $result = $controller->handleList($pagina, $filters);
    renderGroupList($result['groups'] ?? [], $result['total_records'] ?? 0, $result['current_page'] ?? 1, $result['records_per_page'] ?? 50, $result['filters'] ?? []);
} else {
    // List mode
    renderGroupList($result['groups'] ?? [], $result['total_records'] ?? 0, $result['current_page'] ?? 1, $result['records_per_page'] ?? 50, $result['filters'] ?? []);
}

echo '<script src="js/sms_grupos.js"></script>' . "\n";
?>
