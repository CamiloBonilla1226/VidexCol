<?php
// CAMPANHA.PHP - Campaign Module Wrapper
// Refactored from 690 monolithic lines to 50-line thin wrapper
// Delegates to modular layers: config + service + controller + form

// DEBUG: Log all requests
include_once('debug_campanha_flow_logger.php');

// Enable error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_campanha_errors.log');

// Check Authorization
if($_SESSION["perfil"] != 1 && $_SESSION["perfil"] != 2 && $_SESSION["perfil"] != 161 && $_SESSION["perfil"] != 162 && $_SESSION["perfil"] != 163) {
    die("<h1>No esta autorizado para ver esta informacion</h1>");
}

// Load modular layers
require_once('campanha_config.php');
require_once('campanha_service.php');
require_once('campanha_controller.php');
require_once('campanha_form.php');

// Initialize controller
$controller = new CampanaController();

// Determine operation from URL parameters
$opc = intval($_GET['opc'] ?? 2);
$id = intval($_GET['id'] ?? 0);
$pagina = intval($_GET['pagina'] ?? 1);

// Build filters array from GET parameters
$filters = [
    'nombre' => $_GET['nombre'] ?? '',
    'tipoCampana' => $_GET['tipoCampana'] ?? '',
    'estado' => $_GET['estado'] ?? '',
    'fechaInicial' => $_GET['fechaInicial'] ?? '',
    'fechaFinal' => $_GET['fechaFinal'] ?? '',
];

// Route to appropriate handler based on operation
try {
    if ($opc == 1) {
        $result = $controller->handleCreate($_POST);
    } elseif ($opc == 2 && !isset($_POST['id'])) {
        $result = $controller->handleList($pagina, $filters);
    } elseif (isset($_POST['id']) && isset($_POST['funcion'])) {
        $id = intval($_POST['id']);
        error_log("Processing POST: id=$id, funcion=" . $_POST['funcion']);

        if ($_POST['funcion'] == 'eliminar') {
            $deleteResult = $controller->handleDelete($id);
            error_log("Delete result: " . json_encode($deleteResult));

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $deleteResult['mode'] === 'delete_success',
                    'message' => $deleteResult['message'] ?? $deleteResult['error'] ?? 'Campaña eliminada'
                ]);
                exit;
            } else {
                header("Location: index.php?doc=campanha&opc=2");
                exit;
            }
        } else if ($_POST['funcion'] == 'actualizar') {
            error_log("Calling handleEdit with id=$id");
            $result = $controller->handleEdit($id, $_POST);
            error_log("handleEdit result mode: " . ($result['mode'] ?? 'UNDEFINED'));

            if (isset($result['mode']) && $result['mode'] === 'edit_success') {
                error_log("Edit successful, loading list view");
                // Instead of redirecting, load the list directly
                $result = $controller->handleList(1, $filters);
            } else {
                error_log("Edit did not succeed, mode=" . ($result['mode'] ?? 'UNKNOWN'));
            }
        } else {
            error_log("Unknown funcion: " . $_POST['funcion']);
        }
    } elseif ($id > 0 && isset($_GET['id'])) {
        if (isset($_POST['funcion']) && $_POST['funcion'] == 'eliminar') {
            $controller->handleDelete($id);
            header("Location: index.php?doc=campanha&opc=2");
            exit;
        } else {
            $result = $controller->handleEdit($id, $_POST);
        }
    } else {
        $result = $controller->handleList($pagina, $filters);
    }
} catch (Exception $e) {
    error_log("Exception in campanha.php: " . $e->getMessage());
    $result = [
        'mode' => 'error',
        'error' => 'Error del sistema: ' . $e->getMessage()
    ];
}

$mode = $result['mode'] ?? 'list';
$successMessage = null;

// Check for success message from edit
if ($mode === 'edit_success') {
    $successMessage = MESSAGES['success_update'] ?? 'Campaña actualizada correctamente';
    $mode = 'list'; // Change mode to list and show message
}

echo '<link rel="stylesheet" href="campanha.css?v=2.1">' . "\n";

if ($successMessage) {
    showToastMessage($successMessage, 'success');
}

if ($mode == 'create') {
    renderCampaignForm($result['posted_data'] ?? [], 'create', $result['errors'] ?? [], $result['categories'] ?? []);
} elseif ($mode == 'create_success') {
    showToastMessage($result['message'], 'success');
    echo '<script>setTimeout(function() { window.location.href="index.php?doc=campanha&opc=2&id=' . $result['campaign_id'] . '"; }, 1500);</script>';
} elseif ($mode == 'edit') {
    renderCampaignForm($result['campaign'] ?? [], 'edit', $result['errors'] ?? [], $result['categories'] ?? []);
} elseif ($mode == 'delete_success') {
    showToastMessage($result['message'], 'success');
    echo '<script>setTimeout(function() { window.location.href="index.php?doc=campanha&opc=2"; }, 1000);</script>';
} elseif ($mode == 'error') {
    showToastMessage($result['error'] ?? 'Error desconocido', 'danger');
    renderFilterForm($filters, []);
    $result = $controller->handleList($pagina, $filters);
    renderListTable($result['campaigns'] ?? [], $result['total_records'] ?? 0, $result['current_page'] ?? 1, $result['records_per_page'] ?? 50);
    renderPagination($result['total_pages'] ?? 1, $result['current_page'] ?? 1, $result['filters'] ?? []);
    renderEditModal();
} else {
    renderFilterForm($result['filters'] ?? [], $result['categories'] ?? []);
    renderListTable($result['campaigns'] ?? [], $result['total_records'] ?? 0, $result['current_page'] ?? 1, $result['records_per_page'] ?? 50);
    renderPagination($result['total_pages'] ?? 1, $result['current_page'] ?? 1, $result['filters'] ?? []);
    renderEditModal();
}

echo '<script src="js/campanha.js"></script>' . "\n";
?>

