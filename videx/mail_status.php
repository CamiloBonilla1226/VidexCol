<?php
/**
 * mail_status.php
 * Endpoint AJAX — retorna JSON con contadores de la cola de email.
 * Llamado por la barra de progreso en mail_envio.php (opc=3).
 */
session_start();
include_once(__DIR__ . '/funciones.php');

// Verificacion de sesion — mismos perfiles que mail_config.php y mail_envio.php
if (
    empty($_SESSION['id']) ||
    (
        $_SESSION['perfil'] != 1 &&
        $_SESSION['perfil'] != 2 &&
        $_SESSION['perfil'] != 161 &&
        $_SESSION['perfil'] != 162 &&
        $_SESSION['perfil'] != 163
    )
) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

// Pendientes: correos aun en la cola de trabajo
$PSN1 = new DBbase_Sql;
$PSN1->query("SELECT COUNT(id) AS total FROM mail_workqueue");
$pendientes = 0;
if ($PSN1->num_rows() > 0 && $PSN1->next_record()) {
    $pendientes = (int) $PSN1->f('total');
}

// Enviados hoy: suma de la columna correos en mail_historico (dia actual)
$PSN2 = new DBbase_Sql;
$PSN2->query("SELECT COALESCE(SUM(correos), 0) AS total FROM mail_historico WHERE fecha = '" . date('Y-m-d') . "'");
$enviados = 0;
if ($PSN2->num_rows() > 0 && $PSN2->next_record()) {
    $enviados = (int) $PSN2->f('total');
}

// Errores hoy: suma de la columna error en mail_historico (dia actual)
$PSN3 = new DBbase_Sql;
$PSN3->query("SELECT COALESCE(SUM(error), 0) AS total FROM mail_historico WHERE fecha = '" . date('Y-m-d') . "'");
$errores = 0;
if ($PSN3->num_rows() > 0 && $PSN3->next_record()) {
    $errores = (int) $PSN3->f('total');
}

// Contadores de auditoría por estado
$PSNAudit = new DBbase_Sql;
$PSNAudit->query("SELECT estado, COUNT(*) as cnt FROM mail_audit GROUP BY estado");
$auditS = ['enviado' => 0, 'error' => 0, 'rebote' => 0, 'invalido' => 0, 'duplicado' => 0];
while ($PSNAudit->num_rows() > 0 && $PSNAudit->next_record()) {
    $k = $PSNAudit->f('estado');
    if (array_key_exists($k, $auditS)) $auditS[$k] = (int)$PSNAudit->f('cnt');
}

// Estado de pausa de la cola
$PSNCtrl = new DBbase_Sql;
$PSNCtrl->query("SELECT paused FROM mail_queue_control WHERE id = 1");
$paused = ($PSNCtrl->num_rows() > 0 && $PSNCtrl->next_record()) ? (int)$PSNCtrl->f('paused') : 0;

echo json_encode([
    'enviados'         => $enviados,
    'pendientes'       => $pendientes,
    'errores'          => $errores,
    'audit_enviados'   => $auditS['enviado'],
    'audit_errores'    => $auditS['error'],
    'audit_rebotes'    => $auditS['rebote'],
    'audit_invalidos'  => $auditS['invalido'],
    'audit_duplicados' => $auditS['duplicado'],
    'paused'           => $paused,
    'fecha'            => date('Y-m-d H:i:s'),
]);
exit;
