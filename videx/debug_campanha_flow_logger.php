<?php
// Debug: Log all POST/GET submissions to campaign module

$debugData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'GET' => $_GET,
    'POST_keys' => array_keys($_POST),
    'POST_data' => [
        'id' => $_POST['id'] ?? 'MISSING',
        'funcion' => $_POST['funcion'] ?? 'MISSING',
        'nombre' => $_POST['nombre'] ?? 'MISSING',
        'tipoCampana' => $_POST['tipoCampana'] ?? 'MISSING'
    ]
];

// Log to file
$logFile = __DIR__ . '/debug_campanha_flow.log';
file_put_contents($logFile, json_encode($debugData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Determine what path was taken
$opc = intval($_GET['opc'] ?? 2);
$decision = [];

if ($opc == 1) {
    $decision['taken'] = 'handleCreate';
} elseif ($opc == 2 && !isset($_POST['id'])) {
    $decision['taken'] = 'handleList (opc=2 and no POST id)';
} elseif (isset($_POST['id']) && isset($_POST['funcion'])) {
    $decision['taken'] = 'handleEdit or Delete (POST id and funcion present)';
    if ($_POST['funcion'] == 'eliminar') {
        $decision['operation'] = 'DELETE';
    } else if ($_POST['funcion'] == 'actualizar') {
        $decision['operation'] = 'UPDATE';
    }
} else {
    $decision['taken'] = 'handleList (default)';
}

file_put_contents($logFile, "Decision: " . json_encode($decision, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
?>
