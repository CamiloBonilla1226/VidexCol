<?php
// Ultra simple test - test connection and query directly
@header('Content-Type: application/json');
@ob_start();
@error_reporting(0);
@ini_set('display_errors', 0);

// Simple logging - write to file instead of echoing
$log = [];

try {
    $log[] = 'Started';

    $db = @mysqli_connect('localhost', 'twsselvr_root', 'Videx@2024*.', 'twsselvr_videx_app');
    if (!$db) {
        throw new Exception('Connection failed: ' . @mysqli_connect_error());
    }
    $log[] = 'Connected to DB';

    @mysqli_set_charset($db, 'utf8');

    // Test query
    $result = @mysqli_query($db, "SELECT id, nombre, tipoCampana FROM campana WHERE id = 42 LIMIT 1");
    if (!$result) {
        throw new Exception('Query failed: ' . @mysqli_error($db));
    }

    $row = @mysqli_fetch_assoc($result);
    if (!$row) {
        throw new Exception('Campaign 42 not found');
    }

    $log[] = 'Campaign found';
    @mysqli_close($db);

    @ob_clean();
    @header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'log' => $log,
        'campaign' => $row
    ]);

} catch (Exception $e) {
    @ob_clean();
    @header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'log' => $log
    ]);
}
?>
