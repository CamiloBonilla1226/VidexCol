<?php
/**
 * Tickets/Tareas - Envio de notificaciones por correo.
 * Reutiliza PHPMailer + la config SMTP "VidEx" ya guardada en mail_config
 * (id=13), igual patron que mail_test_send.php.
 */
require_once(__DIR__ . '/phpmailer/src/PHPMailer.php');
require_once(__DIR__ . '/phpmailer/src/SMTP.php');
require_once(__DIR__ . '/phpmailer/src/Exception.php');

const GP_TICKET_MAIL_CONFIG_ID = 13;

function gp_ticket_enviar_correo($destinatarios, $asunto, $cuerpoHtml) {
    $destinatarios = array_filter(array_unique((array) $destinatarios), function ($d) {
        return filter_var($d, FILTER_VALIDATE_EMAIL);
    });
    if (empty($destinatarios)) {
        return false;
    }

    $conn = mysqli_connect('localhost', 'twsselvr_root', 'Videx@2024*.', 'twsselvr_videx_app');
    if (!$conn) {
        return false;
    }
    $cfgId = (int) GP_TICKET_MAIL_CONFIG_ID;
    $res = mysqli_query($conn, "SELECT * FROM mail_config WHERE id = $cfgId");
    $cfg = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_close($conn);
    if (!$cfg) {
        return false;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = trim($cfg['host']);
        $mail->SMTPAuth = true;
        $mail->Username = $cfg['usuario'];
        $mail->Password = $cfg['password'];
        $mail->Port = (int) $cfg['puerto'];
        $enc = trim($cfg['encriptacion']);
        $mail->SMTPSecure = ($enc === 'tls') ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;

        $mail->setFrom($cfg['correo'], $cfg['nombre']);
        foreach ($destinatarios as $d) {
            $mail->addAddress($d);
        }
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $cuerpoHtml;
        $mail->AltBody = strip_tags($cuerpoHtml);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('gp_ticket_enviar_correo: ' . $mail->ErrorInfo);
        return false;
    }
}

function gp_ticket_plantilla_correo($titulo, $cuerpo, $urlTicket) {
    return '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;padding:24px;background:#f8fafc;">'
        . '<h2 style="color:#1d4ed8;margin:0 0 12px;">' . htmlspecialchars($titulo) . '</h2>'
        . '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px;color:#334155;font-size:14px;line-height:1.5;">' . $cuerpo . '</div>'
        . '<p style="margin-top:18px;"><a href="' . htmlspecialchars($urlTicket) . '" style="background:#2563eb;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:13px;">Ver ticket</a></p>'
        . '<hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0;"/>'
        . '<small style="color:#94a3b8;">VidEx — Gestión Proyectos / Tickets</small>'
        . '</div>';
}
