<?php
/**
 * Database Document Debug
 * Check all tables for documents related to contact 50
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "twsselvr_root";
$pass = "Videx@2024*.";
$db = "twsselvr_videx_app";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>DB Document Debug</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .section { margin: 20px 0; padding: 20px; border: 1px solid #ccc; border-radius: 4px; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #2563eb; color: white; }
        tr:nth-child(even) { background: #f9fafb; }
        .error { color: #dc2626; }
        .success { color: #10b981; }
        pre { background: #f3f4f6; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>

<h1>🔍 Database Document Debug - Contact ID: 50</h1>

<?php

echo '<div class="section">';
echo '<h2>📊 Available Tables</h2>';

$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

echo '<p>Total tables: ' . count($tables) . '</p>';
echo '<pre>';
print_r($tables);
echo '</pre>';

// Look for document-related tables
$document_tables = [];
foreach ($tables as $table) {
    if (stripos($table, 'document') !== false ||
        stripos($table, 'archivo') !== false ||
        stripos($table, 'file') !== false ||
        stripos($table, 'attach') !== false) {
        $document_tables[] = $table;
    }
}

if (!empty($document_tables)) {
    echo '<h3 class="success">✓ Found document-related tables:</h3>';
    echo '<ul>';
    foreach ($document_tables as $table) {
        echo '<li><strong>' . htmlspecialchars($table) . '</strong></li>';
    }
    echo '</ul>';
} else {
    echo '<p class="error">❌ No obvious document-related tables found</p>';
}

echo '</div>';

// Check sms_usuarios table structure
echo '<div class="section">';
echo '<h2>📋 sms_usuarios Table Structure</h2>';

$result = $conn->query("DESCRIBE sms_usuarios");
echo '<table>';
echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
while ($row = $result->fetch_assoc()) {
    echo '<tr>';
    echo '<td><strong>' . htmlspecialchars($row['Field']) . '</strong></td>';
    echo '<td>' . htmlspecialchars($row['Type']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Null']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Key']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Default']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Extra']) . '</td>';
    echo '</tr>';
}
echo '</table>';

echo '</div>';

// Check for contact 50 in sms_usuarios
echo '<div class="section">';
echo '<h2>👤 Contact ID 50 in sms_usuarios</h2>';

$result = $conn->query("SELECT * FROM sms_usuarios WHERE id = 50");
if ($result && $result->num_rows > 0) {
    $contact = $result->fetch_assoc();
    echo '<table>';
    foreach ($contact as $key => $value) {
        echo '<tr><td><strong>' . htmlspecialchars($key) . '</strong></td><td>' . htmlspecialchars(substr($value, 0, 200)) . '</td></tr>';
    }
    echo '</table>';
} else {
    echo '<p class="error">❌ Contact ID 50 not found in sms_usuarios</p>';
}

echo '</div>';

// Check each document-related table for records
foreach ($document_tables as $table) {
    echo '<div class="section">';
    echo '<h2>📄 Table: ' . htmlspecialchars($table) . '</h2>';

    $result = $conn->query("DESCRIBE " . $table);
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    echo '<h3>Columns: ' . implode(', ', $columns) . '</h3>';

    // Try different ID column names
    $id_columns = ['usuario_id', 'contacto_id', 'contact_id', 'user_id', 'id_usuario', 'id_contacto'];
    $found = false;

    foreach ($id_columns as $id_col) {
        if (in_array($id_col, $columns)) {
            $query = "SELECT * FROM " . $table . " WHERE " . $id_col . " = 50 LIMIT 10";
            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
                echo '<h4 class="success">✓ Found ' . $result->num_rows . ' record(s) with ' . $id_col . ' = 50:</h4>';
                echo '<table>';
                echo '<tr>';
                foreach ($columns as $col) {
                    echo '<th>' . htmlspecialchars($col) . '</th>';
                }
                echo '</tr>';

                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    foreach ($row as $value) {
                        echo '<td>' . htmlspecialchars(substr($value, 0, 100)) . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table>';
                $found = true;
                break;
            }
        }
    }

    if (!$found) {
        echo '<p class="error">No records found for contact 50 in this table</p>';
    }

    echo '</div>';
}

// Check all tables for any contact 50 references
echo '<div class="section">';
echo '<h2>🔎 Search All Tables for Contact 50</h2>';

$found_any = false;
foreach ($tables as $table) {
    $result = $conn->query("DESCRIBE " . $table);
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    $id_columns = ['usuario_id', 'contacto_id', 'contact_id', 'user_id', 'id_usuario', 'id_contacto', 'id'];

    foreach ($id_columns as $id_col) {
        if (in_array($id_col, $columns)) {
            $query = "SELECT COUNT(*) as cnt FROM " . $table . " WHERE " . $id_col . " = 50";
            $result = $conn->query($query);
            $row = $result->fetch_assoc();

            if ($row['cnt'] > 0) {
                echo '<p><strong>' . htmlspecialchars($table) . '.' . htmlspecialchars($id_col) . '</strong>: ' . $row['cnt'] . ' record(s)</p>';
                $found_any = true;
            }
        }
    }
}

if (!$found_any) {
    echo '<p class="error">❌ Contact 50 not found in any table</p>';
}

echo '</div>';

$conn->close();
?>

</body>
</html>
