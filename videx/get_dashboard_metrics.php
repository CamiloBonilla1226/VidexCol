<?php
/**
 * Dashboard Metrics - AJAX Endpoint
 * Returns real-time statistics for dashboard widgets
 */

// Prevent any output before JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

try {
    // Security check
    session_start();
    if (!isset($_SESSION['id'])) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'No sesión']);
        exit;
    }

    // Database connection
    $host = "localhost";
    $user = "twsselvr_root";
    $pass = "Videx@2024*.";
    $db = "twsselvr_videx_app";

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'DB Error: ' . $conn->connect_error]);
        exit;
    }

    $metrics = [
        'mensajes_hoy' => 0,
        'campanas_activas' => 0,
        'contactos_total' => 0,
        'entregas_total' => 0,
        'success' => true
    ];

    // 1. Mensajes Hoy
    $result = $conn->query("SELECT COALESCE(SUM(correos), 0) AS total FROM mail_historico WHERE DATE(fecha) = CURDATE()");
    if ($result && $row = $result->fetch_assoc()) {
        $metrics['mensajes_hoy'] = intval($row['total']);
    }

    // 2. Campañas Activas
    $result = $conn->query("SELECT COUNT(*) AS total FROM campana WHERE estado = 1");
    if ($result && $row = $result->fetch_assoc()) {
        $metrics['campanas_activas'] = intval($row['total']);
    }

    // 3. Contactos Total
    $result = $conn->query("SELECT COUNT(*) AS total FROM sms_usuarios");
    if ($result && $row = $result->fetch_assoc()) {
        $metrics['contactos_total'] = intval($row['total']);
    }

    // 4. Entregas Total - SUM correos (not enviados)
    $result = $conn->query("SELECT COALESCE(SUM(correos), 0) AS total FROM mail_historico");
    if ($result && $row = $result->fetch_assoc()) {
        $metrics['entregas_total'] = intval($row['total']);
    }

    $conn->close();

    // Clear buffer and send JSON
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($metrics);

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'mensajes_hoy' => 0,
        'campanas_activas' => 0,
        'contactos_total' => 0,
        'entregas_total' => 0
    ]);
}
?>
