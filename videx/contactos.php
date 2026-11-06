<?php
/**
 * Contactos Module - Main Wrapper
 * Thin router that handles permission checks and delegates to controller/views
 *
 * Refactored from 1118 lines to ~80 lines
 * All business logic moved to service/controller/view layers
 *
 * @package VIDEX
 * @version 1.0
 */

// Authorization check - block unauthorized profiles
if ($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 4 || $_SESSION["perfil"] == 160) {
    die("<h1>No está autorizado para ver esta información</h1>");
}

// Load configuration and modules
require_once('contactos_config.php');
require_once('contactos_service.php');
require_once('contactos_controller.php');
require_once('contactos_form.php');

// Determine operation
$opc = eliminarInvalidos($_GET["opc"] ?? "2");
$id = intval($_GET["id"] ?? 0);
$pag = intval($_GET["pagina"] ?? 1);

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
$controller = new ContactosController();

// Check authorization
if (!$controller->authorize($_SESSION["perfil"])) {
    die("<h1>No está autorizado</h1>");
}

// Apply commercial restriction (if user is comercial/162, only see own clients)
$comercial_restriction = null;
if ($_SESSION["perfil"] == 162) {
    $comercial_restriction = $_SESSION["id"];
}

// Get dropdown data (clients, identification types, groups)
$dropdown_data = $controller->getDropdownData($comercial_restriction);

// Handle POST - Create/Update/Delete
if ($_POST && $opc == 1) {
    // CREATE
    $result = $controller->handleCreate($_POST, $_SESSION["id"]);
    if ($result['success']) {
        ?>
        <script>
            alert('<?php echo $result['message']; ?>');
            window.location.href = 'index.php?doc=contactos&opc=2';
        </script>
        <?php
        exit;
    } else {
        $mode = 'create';
        $contact = $_POST;
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
            alert('<?php echo $result['message'] ?? 'Contacto eliminado'; ?>');
            window.location.href = 'index.php?doc=contactos&opc=2';
        </script>
        <?php
        exit;
    } else {
        $result = $controller->handleEdit($id, $_POST, $_SESSION["id"]);
        if ($result['success']) {
            ?>
            <script>
                alert('<?php echo $result['message']; ?>');
                window.location.href = 'index.php?doc=contactos&opc=2';
            </script>
            <?php
            exit;
        } else {
            $mode = 'edit';
            $service = new ContactosService();
            $contact = $service->getContactById($id);
            if ($contact) {
                $contact['groups'] = $service->getContactGroups($id);
            }
            $contact = $contact ?? [];
            $errors = $result['errors'] ?? [];
            if (isset($result['error'])) {
                $errors['_general'] = $result['error'];
            }
        }
    }
} elseif ($opc == 1) {
    // GET CREATE FORM
    $mode = 'create';
    $contact = [];
    $errors = [];
} elseif ($id > 0) {
    // GET EDIT FORM
    $mode = 'edit';
    $service = new ContactosService();
    $contact = $service->getContactById($id);
    if ($contact) {
        $contact['groups'] = $service->getContactGroups($id);
    }
    $contact = $contact ?? [];
    $errors = [];
    if (empty($contact)) {
        ?>
        <div class="alert alert-error">
            <strong>Error:</strong> Contacto no encontrado
        </div>
        <?php
        exit;
    }
} else {
    // LIST
    $mode = 'list';
    $filters = [
        'bus_nombres' => $_GET['bus_nombres'] ?? '',
        'bus_email' => $_GET['bus_email'] ?? '',
        'bus_celular' => $_GET['bus_celular'] ?? '',
        'idCliente' => $_GET['idCliente'] ?? '',
        'bus_grupo' => $_GET['bus_grupo'] ?? ''
    ];
}

// Render
if ($mode === 'create' || $mode === 'edit') {
    renderContactForm($contact, $mode, $errors, $dropdown_data);
} else {
    // LIST MODE
    renderFilterForm($filters, $dropdown_data);

    $list_result = $controller->handleList($pag, $filters, $comercial_restriction);
    renderContactTable(
        $list_result['contacts'],
        $list_result['total'],
        $list_result['page'],
        $list_result['per_page'],
        $filters
    );
}
?>
