<?php
/**
 * Contacto Document Handler - AJAX Endpoint
 * Reads documents from sms_usuarios_documentos_add table
 */

error_reporting(0);
ini_set('display_errors', 0);

session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';
$contacto_id = intval($_GET['contacto_id'] ?? $_POST['contacto_id'] ?? 0);

if ($contacto_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Contact ID inválido']);
    exit;
}

// Database connection
$host = "localhost";
$user = "twsselvr_root";
$pass = "Videx@2024*.";
$db = "twsselvr_videx_app";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB Error: ' . $conn->connect_error]);
    exit;
}

// Handle different actions
if ($accion === 'upload') {
    handleUpload($contacto_id, $conn);
} elseif ($accion === 'list') {
    handleList($contacto_id, $conn);
} elseif ($accion === 'delete') {
    handleDelete($contacto_id, $conn);
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

$conn->close();

/**
 * Handle file upload - saves to database
 */
function handleUpload($contacto_id, $conn) {
    if (!isset($_FILES['documento'])) {
        echo json_encode(['success' => false, 'message' => 'No se seleccionó archivo']);
        return;
    }

    $file = $_FILES['documento'];

    if ($file['error'] != UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Archivo demasiado grande',
            UPLOAD_ERR_FORM_SIZE => 'Archivo demasiado grande',
            UPLOAD_ERR_PARTIAL => 'Carga incompleta',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Error temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error de escritura',
            UPLOAD_ERR_EXTENSION => 'Extensión no permitida'
        ];
        $msg = $errors[$file['error']] ?? 'Error desconocido';
        echo json_encode(['success' => false, 'message' => $msg]);
        return;
    }

    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'Archivo demasiado grande (máx 5MB)']);
        return;
    }

    $allowed_types = ['application/pdf', 'application/msword',
                      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                      'image/jpeg', 'image/png', 'image/jpg'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
        return;
    }

    // Create upload directory (shared folder, not per-user)
    $upload_dir = __DIR__ . '/archivos/contactos/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename using timestamp
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = time() . '.' . $ext;
    $filepath = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'message' => 'Error guardando archivo']);
        return;
    }

    // Get description from POST or use original filename
    $descripcion = $_POST['descripcion'] ?? basename($file['name'], '.' . $ext);

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO sms_usuarios_documentos_add (idUsuario, descripcion, archivo) VALUES (?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error en consulta: ' . $conn->error]);
        return;
    }

    $stmt->bind_param("iss", $contacto_id, $descripcion, $filename);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Documento subido correctamente',
            'nombre' => $descripcion,
            'archivo' => $filename,
            'debug' => [
                'contacto_id' => $contacto_id,
                'descripcion' => $descripcion,
                'archivo' => $filename,
                'ruta_guardada' => $filepath,
                'id_insertado' => $stmt->insert_id
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error guardando en base de datos: ' . $stmt->error]);
    }

    $stmt->close();
}

/**
 * Handle document list - reads from database
 */
function handleList($contacto_id, $conn) {
    $documentos = [];

    $query = "SELECT id, descripcion, archivo FROM sms_usuarios_documentos_add WHERE idUsuario = ? ORDER BY id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $contacto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $documentos[] = [
            'id' => strval($row['id']),
            'nombre' => $row['descripcion'],
            'archivo' => $row['archivo'],
            'url' => 'archivos/contactos/' . urlencode($row['archivo'])
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'documentos' => $documentos,
        'total' => count($documentos),
        'source' => 'sms_usuarios_documentos_add'
    ]);
}

/**
 * Handle document deletion - removes from database and filesystem
 */
function handleDelete($contacto_id, $conn) {
    $documento_id = $_POST['documento_id'] ?? '';

    if (empty($documento_id)) {
        echo json_encode(['success' => false, 'message' => 'ID de documento inválido']);
        return;
    }

    // Get file info before deleting
    $query = "SELECT archivo FROM sms_usuarios_documentos_add WHERE id = ? AND idUsuario = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $documento_id, $contacto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Documento no encontrado']);
        $stmt->close();
        return;
    }

    $row = $result->fetch_assoc();
    $archivo = $row['archivo'];
    $stmt->close();

    // Delete from database
    $delete_query = "DELETE FROM sms_usuarios_documentos_add WHERE id = ? AND idUsuario = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $documento_id, $contacto_id);

    if ($delete_stmt->execute()) {
        // Try to delete file from filesystem if it exists
        $filepath = __DIR__ . '/archivos/contactos/' . $archivo;
        @unlink($filepath);

        echo json_encode(['success' => true, 'message' => 'Documento eliminado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar documento']);
    }

    $delete_stmt->close();
}
?>
