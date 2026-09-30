<?php
include __DIR__ . '/config.php';
try {
    $pdo = new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $r1 = $pdo->exec("UPDATE gibbonModule SET category='Pastoral' WHERE category='SEGUIMIENTO'");
    $r2 = $pdo->exec("UPDATE gibbonSetting SET value=REPLACE(value,'SEGUIMIENTO','Pastoral') WHERE scope='System' AND name='mainMenuCategoryOrder'");

    echo "Revertido: módulos=$r1, setting=$r2";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
