<?php
/**
 * Session & IP Tracker
 * Correlaciona gibbonSession con gibbonLog para ver IPs por usuario
 * 
 * @version 1.0
 * @author Admin
 * @date 2026-05-13
 */

// Usar credenciales del config central de Gibbon
require_once __DIR__ . '/config.php';

try {
    $dsn = isset($databasePort)
        ? "mysql:host={$databaseServer};port={$databasePort};dbname={$databaseName}"
        : "mysql:host={$databaseServer};dbname={$databaseName}";

    $pdo = new PDO(
        $dsn,
        $databaseUsername,
        $databasePassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener el gibbonPersonID seleccionado
$selectedPersonID = $_GET['gibbonPersonID'] ?? null;
$sessionData = [];
$personName = '';

// Consultar todos los usuarios disponibles
$queryUsers = "SELECT gibbonPersonID, CONCAT(firstName, ' ', surname) as fullName, username, status
              FROM gibbonPerson
              WHERE status != 'Left'
              ORDER BY surname, firstName";
$stmtUsers = $pdo->query($queryUsers);
$users = $stmtUsers->fetchAll();

// Si se selecciona un usuario, obtener sus sesiones e IPs
if ($selectedPersonID) {
    // Validar que el ID sea numérico
    $selectedPersonID = intval($selectedPersonID);
    
    // Obtener nombre del usuario
    $queryName = "SELECT CONCAT(firstName, ' ', surname) as fullName, username 
                 FROM gibbonPerson 
                 WHERE gibbonPersonID = :personID";
    $stmtName = $pdo->prepare($queryName);
    $stmtName->execute([':personID' => $selectedPersonID]);
    $person = $stmtName->fetch();
    
    if ($person) {
        $personName = $person['fullName'] . " ({$person['username']})";
        
        $querySession = "
            SELECT
                s.gibbonSessionID,
                s.gibbonPersonID,
                s.gibbonActionID,
                s.sessionStatus,
                s.timestampCreated as sessionCreated,
                s.timestampModified as sessionModified,
                l.ip,
                l.title as actionTitle,
                l.timestamp as logTimestamp,
                a.name as actionName,
                m.name as moduleName
            FROM gibbonSession s
            LEFT JOIN gibbonLog l ON l.gibbonLogID = (
                SELECT gibbonLogID FROM gibbonLog
                WHERE gibbonPersonID = s.gibbonPersonID
                  AND title LIKE '%Login%'
                  AND title NOT LIKE '%Fail%'
                  AND timestamp <= DATE_ADD(s.timestampCreated, INTERVAL 2 MINUTE)
                ORDER BY timestamp DESC
                LIMIT 1
            )
            LEFT JOIN gibbonAction a ON s.gibbonActionID = a.gibbonActionID
            LEFT JOIN gibbonModule m ON a.gibbonModuleID = m.gibbonModuleID
            WHERE s.gibbonPersonID = :personID
            ORDER BY s.timestampCreated DESC
            LIMIT 50
        ";
        
        $stmtSession = $pdo->prepare($querySession);
        $stmtSession->execute([':personID' => $selectedPersonID]);
        $sessionData = $stmtSession->fetchAll();

        // Historial completo de logins con IP desde gibbonLog
        $queryLogins = "
            SELECT gibbonLogID, title, ip, timestamp
            FROM gibbonLog
            WHERE gibbonPersonID = :personID
              AND title LIKE '%Login%'
            ORDER BY timestamp DESC
            LIMIT 100
        ";
        $stmtLogins = $pdo->prepare($queryLogins);
        $stmtLogins->execute([':personID' => $selectedPersonID]);
        $loginHistory = $stmtLogins->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session & IP Tracker - Gibbon</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        
        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #333;
        }
        
        .filter-group select {
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            min-width: 300px;
            transition: border-color 0.3s;
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }
        
        .filter-group button {
            padding: 10px 25px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .filter-group button:hover {
            background: #764ba2;
        }
        
        .results-section {
            display: none;
        }
        
        .results-section.active {
            display: block;
        }
        
        .user-info {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        
        .user-info strong {
            color: #2196F3;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
        }
        
        .table-section {
            margin-top: 25px;
        }
        
        .table-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #ddd;
        }
        
        table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .ip-cell {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 5px 8px;
            border-radius: 3px;
            font-weight: 600;
        }
        
        .time-cell {
            color: #666;
            font-size: 12px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-state svg {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Session & IP Tracker</h1>
            <p>Correlaciona sesiones y registros de acceso con IPs por usuario</p>
        </div>
        
        <div class="content">
            <!-- Sección de Filtro -->
            <div class="filter-section">
                <form method="GET" class="filter-group">
                    <label for="userSelect">Selecciona un usuario:</label>
                    <select name="gibbonPersonID" id="userSelect" onchange="this.form.submit()">
                        <option value="">-- Selecciona un usuario --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['gibbonPersonID']; ?>" 
                                    <?php echo $selectedPersonID == $user['gibbonPersonID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['fullName']) . ' (' . htmlspecialchars($user['username']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <!-- Sección de Resultados -->
            <?php if ($selectedPersonID && $personName): ?>
            <div class="results-section active">
                <!-- Información del Usuario -->
                <div class="user-info">
                    <strong>Usuario Seleccionado:</strong> <?php echo htmlspecialchars($personName); ?>
                </div>
                
                <!-- Estadísticas -->
                <?php 
                $uniqueSessions = count(array_unique(array_column($sessionData, 'gibbonSessionID')));
                $uniqueIPs = count(array_unique(array_filter(array_column($sessionData, 'ip'))));
                $totalLogs = count(array_filter(array_column($sessionData, 'gibbonLogID')));
                ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Sesiones Activas</h3>
                        <div class="number"><?php echo $uniqueSessions; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>IPs Únicas</h3>
                        <div class="number"><?php echo $uniqueIPs; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Registros de Acceso</h3>
                        <div class="number"><?php echo $totalLogs; ?></div>
                    </div>
                </div>
                
                <!-- Tabla de Sesiones e IPs -->
                <div class="table-section">
                    <h3 class="table-title">📋 Sesiones y Accesos</h3>
                    
                    <?php if (!empty($sessionData)): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Sesión</th>
                                    <th>IP Address</th>
                                    <th>Acción</th>
                                    <th>Módulo</th>
                                    <th>Estado Sesión</th>
                                    <th>Fecha Inicio Sesión</th>
                                    <th>Fecha Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessionData as $row): ?>
                                <tr>
                                    <td>
                                        <code style="font-size: 11px; background: #f5f5f5; padding: 3px 5px; border-radius: 3px;">
                                            <?php echo substr($row['gibbonSessionID'], 0, 12) . '...'; ?>
                                        </code>
                                    </td>
                                    <td>
                                        <?php if ($row['ip']): ?>
                                            <span class="ip-cell"><?php echo htmlspecialchars($row['ip']); ?></span>
                                        <?php else: ?>
                                            <span style="color: #ccc;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($row['actionName']) {
                                            echo htmlspecialchars($row['actionName']);
                                        } else if ($row['actionTitle']) {
                                            echo htmlspecialchars($row['actionTitle']);
                                        } else {
                                            echo '<span style="color: #ccc;">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $row['moduleName'] ? htmlspecialchars($row['moduleName']) : '<span style="color: #ccc;">-</span>'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $row['sessionStatus'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $row['sessionStatus'] ?? 'Active'; ?>
                                        </span>
                                    </td>
                                    <td class="time-cell">
                                        <?php echo $row['sessionCreated'] ? date('d/m/Y H:i:s', strtotime($row['sessionCreated'])) : '-'; ?>
                                    </td>
                                    <td class="time-cell">
                                        <?php echo $row['logTimestamp'] ? date('d/m/Y H:i:s', strtotime($row['logTimestamp'])) : '-'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <p>No hay datos de sesiones o accesos para este usuario</p>
                    </div>
                    <?php endif; ?>
                <!-- Historial de Logins con IP -->
                <div class="table-section" style="margin-top: 35px;">
                    <h3 class="table-title">🌐 Historial de Logins con IP</h3>
                    <?php if (!empty($loginHistory)): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>IP Address</th>
                                    <th>Evento</th>
                                    <th>Fecha y Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loginHistory as $i => $log): ?>
                                <tr>
                                    <td style="color:#999; font-size:12px;"><?php echo $i + 1; ?></td>
                                    <td>
                                        <?php if ($log['ip']): ?>
                                            <span class="ip-cell"><?php echo htmlspecialchars($log['ip']); ?></span>
                                        <?php else: ?>
                                            <span style="color:#ccc;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-size:12px; padding:3px 8px; border-radius:12px; background:<?php echo strpos($log['title'], 'Fail') !== false ? '#fde8e8' : '#e8f5e9'; ?>; color:<?php echo strpos($log['title'], 'Fail') !== false ? '#c0392b' : '#2e7d32'; ?>;">
                                            <?php echo htmlspecialchars($log['title']); ?>
                                        </span>
                                    </td>
                                    <td class="time-cell"><?php echo date('d/m/Y H:i:s', strtotime($log['timestamp'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><p>No hay registros de login para este usuario</p></div>
                    <?php endif; ?>
                </div>

                </div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <p>Selecciona un usuario de la lista para ver sus sesiones e IPs</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>🔐 Session & IP Tracker v1.0 | Correlaciona gibbonSession + gibbonLog | Úlima actualización: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
