<?php
/**
 * Check documents in sms_usuarios_documentos_add for contact 50
 */

$host = "localhost";
$user = "twsselvr_root";
$pass = "Videx@2024*.";
$db = "twsselvr_videx_app";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}

echo "<h1>📄 Documents for Contact 50 in sms_usuarios_documentos_add</h1>";

$query = "SELECT * FROM sms_usuarios_documentos_add WHERE idUsuario = 50";
$result = $conn->query($query);

if ($result) {
    echo "<p><strong>Found: " . $result->num_rows . " record(s)</strong></p>";

    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #2563eb; color: white;'>";
    echo "<th>ID</th>";
    echo "<th>idUsuario</th>";
    echo "<th>Descripción</th>";
    echo "<th>Archivo (Path/Name)</th>";
    echo "</tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['idUsuario']) . "</td>";
        echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
        echo "<td><code>" . htmlspecialchars($row['archivo']) . "</code></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Query error: " . $conn->error;
}

$conn->close();
?>
