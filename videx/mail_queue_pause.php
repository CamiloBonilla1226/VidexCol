<?php
/**
 * mail_queue_pause.php
 * AJAX endpoint: pausa o reanuda el procesamiento de la cola de correos.
 * POST: action = 'pause' | 'resume'
 */
session_start();
include_once(__DIR__ . '/funciones.php');

header('Content-Type: application/json');

if (
    empty($_SESSION['id']) ||
    ($_SESSION['perfil'] != 1 && $_SESSION['perfil'] != 2 &&
     $_SESSION['perfil'] != 161 && $_SESSION['perfil'] != 162 && $_SESSION['perfil'] != 163)
) {
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}

$action = ($_POST['action'] ?? '') === 'resume' ? 0 : 1;
$userId = (int)$_SESSION['id'];

$db = new DBbase_Sql;
$db->query(
    "UPDATE mail_queue_control
     SET paused    = '$action',
         paused_by = '$userId',
         paused_at = NOW()
     WHERE id = 1"
);

echo json_encode(['ok' => true, 'paused' => $action]);
exit;
?>
