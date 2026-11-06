<?php
// Debug script for campaña form submission

$debugLog = [];

// Log POST data
$debugLog['post'] = $_POST;
$debugLog['get'] = $_GET;
$debugLog['timestamp'] = date('Y-m-d H:i:s');

// Check what came in
if (isset($_POST['id']) && isset($_POST['funcion'])) {
    $debugLog['action_detected'] = 'POST detected with id=' . $_POST['id'] . ' and funcion=' . $_POST['funcion'];
} else {
    $debugLog['warning'] = 'Missing id or funcion in POST';
    if (!isset($_POST['id'])) $debugLog['missing_id'] = true;
    if (!isset($_POST['funcion'])) $debugLog['missing_funcion'] = true;
}

// Write to debug file
$debugPath = __DIR__ . '/debug_campaña_modal.log';
file_put_contents($debugPath, json_encode($debugLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n", FILE_APPEND);

// Return JSON response for AJAX
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'debug' => $debugLog,
    'message' => 'Debug logged to ' . $debugPath
]);
?>
