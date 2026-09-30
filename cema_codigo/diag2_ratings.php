<?php
if (!isset($_GET['token']) || $_GET['token'] !== 'diag2026b') die('no');

echo "<style>body{font-family:monospace;font-size:12px;padding:10px} .ok{color:green} .err{color:red} pre{background:#f5f5f5;padding:8px;border:1px solid #ccc}</style>";

// 1. Ultimas lineas del error_log
echo "<h2>1. Últimas 30 líneas del error_log</h2><pre>";
$errLog = __DIR__ . '/error_log';
if (file_exists($errLog)) {
    $lines = [];
    $fp = fopen($errLog, 'r');
    fseek($fp, -8000, SEEK_END);
    fgets($fp); // descartar linea parcial
    while (!feof($fp)) $lines[] = fgets($fp);
    fclose($fp);
    $last30 = array_slice($lines, -30);
    foreach ($last30 as $l) echo htmlspecialchars($l);
} else echo "No se encontró error_log";
echo "</pre>";

// 2. Intentar incluir function.php y detectar errores
echo "<h2>2. Test de carga de function.php</h2>";
ob_start();
try {
    // Simular variables que Gibbon define
    $guid = 'test';
    $_SESSION[$guid] = ['absoluteURL' => 'https://cema.pe.edu.co'];
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    include __DIR__ . '/modules/Ratings/function.php';
    echo "<span class='ok'>✅ function.php cargado sin errores fatales</span>";
} catch (Throwable $e) {
    echo "<span class='err'>❌ Error: " . htmlspecialchars($e->getMessage()) . " en línea " . $e->getLine() . "</span>";
}
$out = ob_get_clean();
echo $out;

// 3. Verificar que $container existe en el contexto de Gibbon
echo "<h2>3. Variables globales disponibles</h2><pre>";
$vars = ['guid', 'connection2', 'container', 'page', 'session'];
foreach ($vars as $v) {
    $exists = isset($$v) || array_key_exists($v, $GLOBALS);
    echo ($exists ? "✅" : "❌") . " \$$v " . ($exists ? "existe" : "NO existe") . "\n";
}
echo "</pre>";

// 4. Check del URL de acceso y cómo lo interpreta Gibbon
echo "<h2>4. URL actual del request</h2><pre>";
echo "REQUEST_URI: " . htmlspecialchars($_SERVER['REQUEST_URI']) . "\n";
echo "SCRIPT_NAME: " . htmlspecialchars($_SERVER['SCRIPT_NAME']) . "\n";
echo "_GET[q]: " . htmlspecialchars($_GET['q'] ?? 'no definido') . "\n";
echo "</pre>";

// 5. Verificar include de insertNotesFunctions.php
echo "<h2>5. Test de insertNotesFunctions.php</h2>";
$f = __DIR__ . '/modules/Ratings/insertNotesFunctions.php';
if (file_exists($f)) {
    $content = file_get_contents($f);
    echo "<span class='ok'>✅ Existe (".strlen($content)." bytes)</span><br>";
    // Ver primeras 5 líneas
    $lines = array_slice(explode("\n", $content), 0, 8);
    echo "<pre>" . htmlspecialchars(implode("\n", $lines)) . "</pre>";
} else echo "<span class='err'>❌ No encontrado</span>";

// 6. Ver primeras líneas de index.php del módulo
echo "<h2>6. Contenido de modules/Ratings/index.php</h2>";
echo "<pre>" . htmlspecialchars(file_get_contents(__DIR__ . '/modules/Ratings/index.php')) . "</pre>";
