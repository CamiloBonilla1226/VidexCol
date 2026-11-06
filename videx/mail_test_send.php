<?php
/**
 * mail_test_send.php
 * AJAX endpoint: envía un correo de prueba usando una config SMTP guardada.
 * POST: idConfig, to, asunto
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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once(__DIR__ . '/phpmailer/src/PHPMailer.php');
include_once(__DIR__ . '/phpmailer/src/SMTP.php');
include_once(__DIR__ . '/phpmailer/src/Exception.php');

$idConfig = soloNumeros($_POST['idConfig'] ?? '0');
$to       = trim($_POST['to'] ?? '');
$asunto   = trim($_POST['asunto'] ?? 'Correo de prueba VIDEX');

if (!$idConfig || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'msg' => 'Parámetros inválidos: cuenta o correo destino incorrecto.']);
    exit;
}

$db = new DBbase_Sql;
$db->query("SELECT * FROM mail_config WHERE id = '$idConfig'");
if ($db->num_rows() === 0 || !$db->next_record()) {
    echo json_encode(['ok' => false, 'msg' => 'Cuenta SMTP no encontrada.']);
    exit;
}

$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug = 0;
    $mail->isSMTP();
    $mail->Host     = $db->f('host');
    $mail->SMTPAuth = true;
    $mail->Username = $db->f('usuario');
    $mail->Password = $db->f('password');
    $mail->Port     = $db->f('puerto');

    $enc = $db->f('encriptacion');
    if (!in_array($enc, ['ssl', 'tls'])) {
        $enc = ($db->f('puerto') == 587) ? 'tls' : 'ssl';
    }
    $mail->SMTPSecure = ($enc === 'tls') ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;

    $mail->setFrom($db->f('correo'), $db->f('nombre'));
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = $asunto;
    $mail->Body    = '<div style="font-family:sans-serif;padding:24px;background:#f8fafc;">'
                   . '<h2 style="color:#1d4ed8;">Correo de prueba — VIDEX</h2>'
                   . '<p>Este es un correo de prueba enviado desde la plataforma <strong>VIDEX</strong>.</p>'
                   . '<p>Si lo recibes, la configuración SMTP está funcionando correctamente.</p>'
                   . '<hr style="border:none;border-top:1px solid #e2e8f0;margin:16px 0;"/>'
                   . '<small style="color:#94a3b8;">Enviado por: ' . htmlspecialchars($db->f('correo')) . '</small>'
                   . '</div>';
    $mail->AltBody = 'Correo de prueba VIDEX — configuración SMTP verificada.';

    $mail->send();
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    $errorMsg = substr($mail->ErrorInfo, 0, 300);
    echo json_encode(['ok' => false, 'msg' => $errorMsg]);
}
exit;
?>
