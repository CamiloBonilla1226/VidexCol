<?php
/**
 * Cliente Module - Main Wrapper
 * Thin router that handles permission checks and delegates to controller/views
 *
 * Refactored from 948 lines to 70 lines
 * All business logic moved to service/controller/view layers
 *
 * @package VIDEX
 * @version 1.0
 */

// Authorization check
if ($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 4 || $_SESSION["perfil"] == 160) {
    die("<h1>No está autorizado para ver esta información</h1>");
}

// Load configuration and modules
require_once('cliente_config.php');
require_once('cliente_service.php');
require_once('cliente_controller.php');
require_once('cliente_form.php');

// Determine operation
$opc = eliminarInvalidos($_GET["opc"] ?? "2");
$id = intval($_GET["id"] ?? 0);
$pag = intval($_GET["pag"] ?? 1);

// Check read-only access for create operations
if ($opc == 1 && $_SESSION["acceso_solo_lectura"] == 1) {
    ?>
    <div class="alert alert-error">
        <strong>Atención:</strong> Su usuario es de solo lectura, no puede realizar modificaciones.
    </div>
    <?php
    exit;
}

// Initialize controller
$controller = new ClienteController();

// Check authorization
if (!$controller->authorize($_SESSION["perfil"])) {
    die("<h1>No está autorizado</h1>");
}

// Apply commercial restriction (if user is comercial/162, only see own clients)
$comercial_restriction = null;
if ($_SESSION["perfil"] == 162) {
    $comercial_restriction = $_SESSION["id"];
}

// Get dropdown data
$dropdown_data = $controller->getDropdownData($comercial_restriction);
$tipos = $dropdown_data['tipos'];
$comerciales = $dropdown_data['comerciales'];

// Handle POST - Create/Update/Delete
if ($_POST && $opc == 1) {
    // CREATE
    $result = $controller->handleCreate($_POST, $_SESSION["id"]);
    if ($result['success']) {
        ?>
        <script>
            alert('<?php echo $result['message']; ?>');
            window.location.href = 'index.php?doc=cliente&opc=2&id=<?php echo $result['id']; ?>';
        </script>
        <?php
        exit;
    } else {
        $mode = 'create';
        $client = $_POST;
        $errors = $result['errors'] ?? [];
        if (isset($result['error'])) {
            $errors['_general'] = $result['error'];
        }
    }
} elseif ($_POST && $id > 0) {
    // UPDATE/DELETE
    $funcion = $_POST['funcion'] ?? '';
    if ($funcion === 'eliminar') {
        $result = $controller->handleDelete($id);
        ?>
        <script>
            alert('<?php echo $result['message'] ?? 'Operación completada'; ?>');
            window.location.href = 'index.php?doc=cliente&opc=2';
        </script>
        <?php
        exit;
    } else {
        $result = $controller->handleEdit($id, $_POST, $_SESSION["id"]);
        if ($result['success']) {
            ?>
            <script>
                alert('<?php echo $result['message']; ?>');
                window.location.href = 'index.php?doc=cliente&opc=2';
            </script>
            <?php
            exit;
        } else {
            $mode = 'edit';
            $service = new ClienteService();
            $client = $service->getClientById($id) ?? [];
            $errors = $result['errors'] ?? [];
            if (isset($result['error'])) {
                $errors['_general'] = $result['error'];
            }
        }
    }
} elseif ($opc == 1) {
    // GET CREATE FORM
    $mode = 'create';
    $client = [];
    $errors = [];
} elseif ($id > 0) {
    // GET EDIT FORM
    $mode = 'edit';
    $service = new ClienteService();
    $client = $service->getClientById($id) ?? [];
    $errors = [];
    if (empty($client)) {
        ?>
        <div class="alert alert-error">
            <strong>Error:</strong> Cliente no encontrado
        </div>
        <?php
        exit;
    }
} else {
    // LIST
    $mode = 'list';
    $filters = [
        'nombre' => $_GET['nombre'] ?? '',
        'nit' => $_GET['nit'] ?? '',
        'tipoCliente' => $_GET['tipoCliente'] ?? '',
        'idComercial' => $_GET['idComercial'] ?? '',
        'estado' => $_GET['estado'] ?? '',
        'interno' => $_GET['interno'] ?? ''
    ];
}

// Render
if ($mode === 'create' || $mode === 'edit') {
    renderClientForm($client, $mode, $errors, $tipos, $comerciales);
} else {
    // LIST MODE
    ?>
    <div class="cliente-container">
        <?php
        renderFilterForm($filters, $tipos, $comerciales);

        $list_result = $controller->handleList($pag, $filters, $comercial_restriction);

        // Show table on desktop, cards on mobile/tablet (CSS handles display)
        renderClientTable(
            $list_result['clients'],
            $list_result['total'],
            $list_result['page'],
            $list_result['per_page'],
            $filters
        );

        // Show cards list (hidden on desktop via CSS)
        renderClientCardsList(
            $list_result['clients'],
            $list_result['total'],
            $list_result['page'],
            $list_result['per_page'],
            $filters
        );
        ?>
    </div>

    <!-- Modal for editing clients (populated by AJAX) -->
    <?php renderClientModal([], $tipos, $comerciales); ?>
    <?php
}
