<?php

/**
 * Usuario Module - Main Entry Point
 *
 * Minimal wrapper that routes to the controller
 * Replaced 2,347-line monolithic file with modular architecture
 */

// Permission check
if ($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 4 || $_SESSION["perfil"] == 160) {
    die("<h1>No está autorizado para ver esta información</h1>");
}

// Include required files
require_once('usuario_config.php');
require_once('usuario_service.php');
require_once('usuario_controller.php');
require_once('usuario_form.php');

$debugMode = isset($_GET['debug']) && ($_GET['debug'] === '1' || $_GET['debug'] === 'true');
if (!$debugMode && isset($_POST['debug'])) {
    $debugMode = ($_POST['debug'] === '1' || $_POST['debug'] === 'true');
}

if ($debugMode) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Get request parameters
$opc = isset($_GET['opc']) ? (int)$_GET['opc'] : (isset($_POST['opc']) ? (int)$_POST['opc'] : 0);
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

// Auto-detect mode: if ID provided, it's edit mode (opc=2)
if ($id > 0 && $opc == 0) {
    $opc = 2;
}
// Default to create mode if no operation specified
if ($opc == 0) {
    $opc = 1;
}

// Instantiate controller
$controller = new UsuarioController();

// Handle request based on method and operation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('Error de validación CSRF');
    }

    // Fallback: algunos navegadores/flujos no envian el name del boton submit.
    $accion = isset($_POST['funcion']) ? trim((string)$_POST['funcion']) : '';
    if ($accion === '') {
        $accion = (($id > 0) || ($opc === 2)) ? 'actualizar' : 'insertar';
    }

    if ($debugMode) {
        echo '<div class="alert alert-info" style="white-space:pre-wrap; color:#000;">';
        echo '<strong>DEBUG POST RECEIVED</strong>' . "\n";
        echo 'accion=' . htmlspecialchars($accion) . "\n";
        echo 'id=' . (int)$id . ' opc=' . (int)$opc . "\n";
        echo 'keys=' . htmlspecialchars(implode(', ', array_keys($_POST))) . "\n";
        echo '</div>';
    }

    try {
        switch ($accion) {
            case 'insertar':
                $controller->handleUserCreate($_POST);
                break;
            case 'actualizar':
                $controller->handleUserUpdate($_POST);
                break;
            default:
                echo '<div class="alert alert-danger">Acción de formulario inválida.</div>';
                $controller->getUserForm($opc, $id);
        }
    } catch (Throwable $e) {
        if ($debugMode) {
            echo '<div class="alert alert-danger" style="white-space:pre-wrap; color:#000;">';
            echo '<strong>Error backend:</strong> ' . htmlspecialchars($e->getMessage()) . "\n";
            echo 'Archivo: ' . htmlspecialchars($e->getFile()) . ' Línea: ' . (int)$e->getLine() . "\n\n";
            echo htmlspecialchars($e->getTraceAsString());
            echo '</div>';
        } else {
            echo '<div class="alert alert-danger">Error inesperado al guardar usuario.</div>';
        }
        $controller->getUserForm($opc, $id);
    }
} else {
    $controller->getUserForm($opc, $id);
}
?>

