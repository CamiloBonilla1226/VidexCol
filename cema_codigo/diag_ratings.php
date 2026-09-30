<?php
if (!isset($_GET['token']) || $_GET['token'] !== 'diag2026') die('no');

$databaseServer   = 'localhost';
$databaseUsername = 'zemfzeav_cema_22';
$databasePassword = '@Lbi[!ZpIQ=.';
$databaseName     = 'zemfzeav_cema_30';

$pdo = new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8",
               $databaseUsername, $databasePassword,
               [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "<style>body{font-family:monospace;font-size:13px} .ok{color:green} .err{color:red} .warn{color:orange} table{border-collapse:collapse} td,th{border:1px solid #ccc;padding:4px 8px}</style>";

// 1. Estado del módulo
echo "<h2>1. Estado del módulo Ratings en gibbonModule</h2>";
$stmt = $pdo->query("SELECT gibbonModuleID, name, active, type, entryURL FROM gibbonModule WHERE name='Ratings'");
$mod = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mod) { echo "<span class='err'>❌ Módulo NO encontrado en gibbonModule</span>"; die(); }
echo "<table><tr><th>ID</th><th>name</th><th>active</th><th>type</th><th>entryURL</th></tr>";
echo "<tr><td>{$mod['gibbonModuleID']}</td><td>{$mod['name']}</td>";
echo "<td class='".($mod['active']=='Y'?'ok':'err')."'>{$mod['active']}</td>";
echo "<td>{$mod['type']}</td><td>{$mod['entryURL']}</td></tr></table>";
$moduleID = $mod['gibbonModuleID'];

// 2. Acciones registradas
echo "<h2>2. Acciones en gibbonAction</h2>";
$stmt = $pdo->prepare("SELECT gibbonActionID, name, entryURL, URLList, menuShow, entrySidebar FROM gibbonAction WHERE gibbonModuleID=:id ORDER BY precedence DESC");
$stmt->execute([':id' => $moduleID]);
$actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table><tr><th>ID</th><th>name</th><th>entryURL</th><th>URLList</th><th>menu</th><th>sidebar</th></tr>";
foreach ($actions as $a) {
    $urlOk = (strpos($a['URLList'], $a['entryURL']) !== false) ? '✅' : '⚠️';
    echo "<tr><td>{$a['gibbonActionID']}</td><td>{$a['name']}</td>";
    echo "<td>{$a['entryURL']} $urlOk</td>";
    echo "<td style='font-size:11px'>{$a['URLList']}</td>";
    echo "<td>{$a['menuShow']}</td><td>{$a['entrySidebar']}</td></tr>";
}
echo "</table>";

// 3. Permisos por rol
echo "<h2>3. Permisos en gibbonPermission (roles que pueden acceder)</h2>";
$stmt = $pdo->prepare("
    SELECT ga.name as action, gr.name as role
    FROM gibbonPermission gp
    JOIN gibbonAction ga ON ga.gibbonActionID = gp.gibbonActionID
    JOIN gibbonRole gr ON gr.gibbonRoleID = gp.gibbonRoleID
    WHERE ga.gibbonModuleID = :id
    ORDER BY ga.name, gr.name
");
$stmt->execute([':id' => $moduleID]);
$perms = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($perms)) {
    echo "<span class='err'>❌ NO hay permisos registrados en gibbonPermission para este módulo</span>";
} else {
    echo "<table><tr><th>Acción</th><th>Rol</th></tr>";
    foreach ($perms as $p) echo "<tr><td>{$p['action']}</td><td>{$p['role']}</td></tr>";
    echo "</table>";
}

// 4. Verificar archivos PHP en servidor
echo "<h2>4. Archivos PHP del módulo en el servidor</h2>";
$modPath = __DIR__ . '/modules/Ratings/';
$required = [
    'index.php','manifest.php','reportConfig.php','admin_notes.php',
    'admin_weight.php','admin_periodweight.php','admin_configperiodweight.php',
    'admin_configperiod.php','reportCalcPeriod.php','reportCalc.php',
    'reportView.php','reportView_print.php','reportInsertNotes.php',
    'reportInsertNotes_process.php','reportInsertNotesMass.php',
    'reportInsertNotes_processMass.php','reportStudentsTeacher.php',
    'reportTeacherClass.php','reportTotalLost.php','reportTotalLostByCourse.php',
    'function.php','admin_function.php','report_function.php','insertNotesFunctions.php'
];
echo "<table><tr><th>Archivo</th><th>Existe</th><th>Tamaño</th></tr>";
foreach ($required as $f) {
    $fp = $modPath . $f;
    $exists = file_exists($fp);
    $size = $exists ? filesize($fp) . ' bytes' : '-';
    $cls = $exists ? 'ok' : 'err';
    echo "<tr><td>$f</td><td class='$cls'>".($exists?'✅':'❌')."</td><td>$size</td></tr>";
}
echo "</table>";

// 5. Comprobar errores PHP en archivos clave (lint)
echo "<h2>5. Sintaxis PHP (lint) de archivos clave</h2>";
$toCheck = ['reportConfig.php','reportInsertNotes.php','reportView.php','index.php','function.php'];
foreach ($toCheck as $f) {
    $fp = $modPath . $f;
    if (!file_exists($fp)) { echo "⚠️ $f no encontrado<br>"; continue; }
    $out = shell_exec("php -l " . escapeshellarg($fp) . " 2>&1");
    $ok = strpos($out, 'No syntax errors') !== false;
    echo "<span class='".($ok?'ok':'err')."'>".($ok?'✅':'❌')." $f: $out</span><br>";
}

echo "<hr><small>Script ejecutado: ".date('Y-m-d H:i:s')."</small>";
