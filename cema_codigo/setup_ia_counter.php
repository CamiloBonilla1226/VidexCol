<?php
include __DIR__ . '/config.php';
try {
    $pdo = new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8",
                   $databaseUsername, $databasePassword);
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS gibbonContadorGlobalIA (
            fecha     DATE        NOT NULL,
            consultas INT         NOT NULL DEFAULT 0,
            PRIMARY KEY (fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");
    echo "✅ Tabla gibbonContadorGlobalIA creada/verificada.<br>";
    $row = $pdo->query("SELECT consultas FROM gibbonContadorGlobalIA WHERE fecha = CURDATE()")->fetch();
    $usadas = $row ? (int)$row['consultas'] : 0;
    echo "Consultas usadas hoy: $usadas / 30. Restantes: " . (30 - $usadas);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
