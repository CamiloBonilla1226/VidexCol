<?php
/**
 * Alertas de vencimiento - Envio de notificaciones por correo.
 * Reutiliza PHPMailer + la config SMTP "VidEx" ya guardada en mail_config
 * (id=13), mismo patron que gp_ticket_mail.php / mail_test_send.php.
 */
require_once(__DIR__ . '/phpmailer/src/PHPMailer.php');
require_once(__DIR__ . '/phpmailer/src/SMTP.php');
require_once(__DIR__ . '/phpmailer/src/Exception.php');

const GP_ALERTAS_MAIL_CONFIG_ID = 13;

function gp_alertas_enviar_correo($destinatarios, $asunto, $cuerpoHtml) {
    $destinatarios = array_values(array_filter(array_unique((array) $destinatarios), function ($d) {
        return filter_var($d, FILTER_VALIDATE_EMAIL);
    }));
    if (empty($destinatarios)) {
        return false;
    }

    $db = new DBbase_Sql();
    $db->connect();
    if (!isset($db->Link_ID) || !$db->Link_ID) {
        return false;
    }
    $cfgId = (int) GP_ALERTAS_MAIL_CONFIG_ID;
    $res = mysqli_query($db->Link_ID, "SELECT * FROM mail_config WHERE id = $cfgId");
    $cfg = $res ? mysqli_fetch_assoc($res) : null;
    if (!$cfg) {
        error_log('gp_alertas_enviar_correo: no se encontro mail_config id=' . $cfgId);
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
        $enc = trim((string) $cfg['encriptacion']);
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
        error_log('gp_alertas_enviar_correo: ' . $mail->ErrorInfo);
        return false;
    }
}

/** Plantilla de alerta de vencimiento, con la urgencia visual segun los dias restantes. */
function gp_alertas_plantilla_correo($tipoLegible, $etiqueta, $contexto, $dias, $fechaVencimiento) {
    $vencido = $dias < 0;
    $color = $vencido ? '#b91c1c' : ($dias <= 1 ? '#dc2626' : ($dias <= 3 ? '#d97706' : '#2563eb'));
    $tituloUrgencia = $vencido
        ? '¡YA VENCIÓ! Renovar de inmediato'
        : ($dias === 0 ? '¡Vence HOY! Renovar de inmediato' : 'Vence en ' . $dias . ' día' . ($dias == 1 ? '' : 's') . ' — Renovar pronto');
    $fecha = $fechaVencimiento ? date('d/m/Y', strtotime($fechaVencimiento)) : '—';
    $diasTexto = $vencido ? ('Vencido hace ' . abs($dias) . ' día' . (abs($dias) == 1 ? '' : 's')) : $tituloUrgencia;

    return '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;padding:24px;background:#f8fafc;">'
        . '<div style="background:' . $color . ';color:#fff;padding:14px 18px;border-radius:8px 8px 0 0;font-size:16px;font-weight:bold;">'
        . '⚠️ ' . htmlspecialchars($tituloUrgencia) . '</div>'
        . '<div style="background:#fff;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;padding:18px;color:#334155;font-size:14px;line-height:1.6;">'
        . '<p style="margin:0 0 10px;"><strong>' . htmlspecialchars($tipoLegible) . ':</strong> ' . htmlspecialchars($etiqueta) . '</p>'
        . ($contexto ? '<p style="margin:0 0 10px;"><strong>Proveedor:</strong> ' . htmlspecialchars($contexto) . '</p>' : '')
        . '<p style="margin:0 0 10px;"><strong>Fecha de vencimiento:</strong> ' . htmlspecialchars($fecha) . '</p>'
        . '<p style="margin:0;color:' . $color . ';font-weight:bold;">' . htmlspecialchars($diasTexto) . '</p>'
        . '</div>'
        . '<p style="margin-top:14px;font-size:12.5px;color:#64748b;">Este aviso se repetirá todos los días hasta que el vencimiento se actualice en VidEx.</p>'
        . '<hr style="border:none;border-top:1px solid #e2e8f0;margin:16px 0;"/>'
        . '<small style="color:#94a3b8;">VidEx — Gestión Proyectos / Alertas de vencimiento</small>'
        . '</div>';
}
