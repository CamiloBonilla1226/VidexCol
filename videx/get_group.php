<?php
// AJAX Endpoint - Get SMS Group Data
// Direct database query for group modal

@ob_start();
@error_reporting(E_ALL);
@ini_set('display_errors', 0);

// Start session FIRST
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Clear ALL output
@ob_clean();

// JSON header IMMEDIATELY
@header('Content-Type: application/json; charset=utf-8');

// Verify auth
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

// Verify params
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid ID']));
}

try {
    // Direct mysqli connection
    $db = @mysqli_connect('localhost', 'twsselvr_root', 'Videx@2024*.', 'twsselvr_videx_app');

    if (!$db) {
        throw new Exception('DB connection failed');
    }

    @mysqli_set_charset($db, 'utf8');

    // Query with prepared statement
    $query = "SELECT id, nombre, descripcion
              FROM sms_grupos WHERE id = ? LIMIT 1";

    $stmt = @mysqli_prepare($db, $query);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . @mysqli_error($db));
    }

    @mysqli_stmt_bind_param($stmt, 'i', $id);
    @mysqli_stmt_execute($stmt);
    $result = @mysqli_stmt_get_result($stmt);
    $group = $result ? @mysqli_fetch_assoc($result) : null;
    @mysqli_stmt_close($stmt);

    if (!$group) {
        @mysqli_close($db);
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Not found']));
    }

    // Get user count
    $count_query = "SELECT COUNT(id_usuario) as conteo FROM sms_asociacion WHERE id_grupo = ?";
    $count_stmt = @mysqli_prepare($db, $count_query);
    @mysqli_stmt_bind_param($count_stmt, 'i', $id);
    @mysqli_stmt_execute($count_stmt);
    $count_result = @mysqli_stmt_get_result($count_stmt);
    $count_row = @mysqli_fetch_assoc($count_result);
    $group['conteo'] = intval($count_row['conteo']);
    @mysqli_stmt_close($count_stmt);

    @mysqli_close($db);

    // Return JSON response
    http_response_code(200);
    die(json_encode([
        'success' => true,
        'group' => $group
    ]));

} catch (Exception $e) {
    @ob_clean();
    @header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => $e->getMessage()]));
}
?>
