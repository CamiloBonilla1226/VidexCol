<?php
// Simple test file to verify get_campaign.php works
// Testing: https://videx.sms-app.co/test_campaign_api.php?id=42

header('Content-Type: text/html; charset=utf-8');

// Check if session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Campaign API Test</h1>";
echo "<pre>";

// Simulate the GET request to get_campaign.php
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Manually set session data for testing (normally would be from login)
    if (!isset($_SESSION['id'])) {
        $_SESSION['id'] = 1;
        $_SESSION['perfil'] = 1;
        echo "Session created for testing\n\n";
    }

    echo "Testing get_campaign.php with ID: $id\n";
    echo "Session: id={$_SESSION['id']}, perfil={$_SESSION['perfil']}\n\n";

    // Try connection directly
    $mysqli = new mysqli('localhost', 'twsselvr_root', 'Videx@2024*.', 'twsselvr_videx_app');

    if ($mysqli->connect_error) {
        echo "ERROR: Connection failed: " . $mysqli->connect_error;
    } else {
        echo "✓ Database connected\n\n";

        // Query campaign
        $query = "SELECT c.id, c.nombre, c.tipoCampana, c.estado, c.fechaInicial, c.fechaFinal
                  FROM campana c WHERE c.id = $id LIMIT 1";

        $result = $mysqli->query($query);
        if ($result) {
            $campaign = $result->fetch_assoc();
            if ($campaign) {
                echo "✓ Campaign found:\n";
                echo json_encode($campaign, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
            } else {
                echo "ERROR: Campaign ID $id not found\n";
            }
        } else {
            echo "ERROR: Query failed: " . $mysqli->error . "\n";
        }

        $mysqli->close();
    }
} else {
    echo "Usage: test_campaign_api.php?id=42\n";
}

echo "</pre>";
?>
