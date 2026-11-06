<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

ignore_user_abort(true);
set_time_limit(0);
ini_set('max_execution_time', 1200);

include_once(__DIR__ . '/phpmailer/src/PHPMailer.php');
include_once(__DIR__ . '/phpmailer/src/SMTP.php');
include_once(__DIR__ . '/phpmailer/src/Exception.php');
include_once(__DIR__ . '/funciones.php');

// ── Helpers ──────────────────────────────────────────────────────────────────

function mq_insertAudit($estado, $correo, $nombre, $idCampana, $remitente, $asunto, $razon, $idUsuario, $retry = 0)
{
    $db = new DBbase_Sql;
    $db->query(
        "INSERT INTO mail_audit
            (idCampana, correo, nombre, estado, razon, asunto, remitente, fecha, idUsuario, retry_count)
         VALUES (
            '" . (int)$idCampana . "',
            '" . addslashes(substr($correo,    0, 255)) . "',
            '" . addslashes(substr($nombre,    0, 255)) . "',
            '" . $estado . "',
            '" . addslashes(substr($razon,     0, 1000)) . "',
            '" . addslashes(substr($asunto,    0, 500)) . "',
            '" . addslashes(substr($remitente, 0, 255)) . "',
            NOW(),
            '" . (int)$idUsuario . "',
            '" . (int)$retry . "'
         )"
    );
}

function mq_addBlacklist($correo, $razon, $idCampana)
{
    $db = new DBbase_Sql;
    $db->query(
        "INSERT INTO mail_blacklist (correo, razon, idCampana, fecha)
         VALUES (
            '" . addslashes(substr($correo, 0, 255)) . "',
            '" . addslashes(substr($razon,  0, 1000)) . "',
            '" . (int)$idCampana . "',
            NOW()
         )
         ON DUPLICATE KEY UPDATE razon = VALUES(razon), fecha = NOW()"
    );
}

function mq_isBounce($smtp_code, $error_info)
{
    if ($smtp_code !== '' && isset($smtp_code[0]) && $smtp_code[0] === '5') {
        return true;
    }
    $lower = strtolower($error_info);
    $patterns = [
        'user unknown', 'no such user', 'does not exist', 'invalid address',
        'address rejected', 'mailbox not found', 'domain not found',
        'bad destination mailbox', 'invalid mailbox', 'recipient rejected',
        '550 ', '551 ', '552 ', '553 ', '554 ', '556 ',
    ];
    foreach ($patterns as $p) {
        if (strpos($lower, $p) !== false) {
            return true;
        }
    }
    return false;
}

// ── 1. Verificar pausa ───────────────────────────────────────────────────────

$PSNCtrl = new DBbase_Sql;
$PSNCtrl->query("SELECT paused FROM mail_queue_control WHERE id = 1");
if ($PSNCtrl->num_rows() > 0 && $PSNCtrl->next_record() && (int)$PSNCtrl->f('paused') === 1) {
    echo "Cola en pausa. No se procesan correos.";
    exit;
}

// ── 2. Determinar batch size según total pendiente ───────────────────────────

$PSNCount = new DBbase_Sql;
$PSNCount->query("SELECT COUNT(id) AS total FROM mail_workqueue");
$total_pendiente = 0;
if ($PSNCount->num_rows() > 0 && $PSNCount->next_record()) {
    $total_pendiente = (int)$PSNCount->f('total');
}

if ($total_pendiente === 0) {
    echo "No hay nada en la cola...";
    exit;
}

// >300 pendientes → modo lento (5/min) para respetar tope 300/hora
// ≤300 pendientes → modo normal (30/min)
$max_per_batch = ($total_pendiente > 300) ? 5 : 30;

// ── 3. Verificar tope de 300 correos por hora ────────────────────────────────

$PSNHora = new DBbase_Sql;
$PSNHora->query(
    "SELECT COUNT(id) AS cnt FROM mail_audit
     WHERE estado = 'enviado'
     AND fecha >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
);
$enviados_hora = 0;
if ($PSNHora->num_rows() > 0 && $PSNHora->next_record()) {
    $enviados_hora = (int)$PSNHora->f('cnt');
}

if ($enviados_hora >= 300) {
    echo "Límite de 300 correos/hora alcanzado. Espera unos minutos.";
    exit;
}

// Ajustar batch al espacio restante en la hora
$espacio_hora = 300 - $enviados_hora;
$batch_size   = min($max_per_batch, $espacio_hora);

// ── 4. Obtener correos de la cola ────────────────────────────────────────────

$batch_id = sha1(uniqid('mq_', true));

$PSN  = new DBbase_Sql;
$PSNT = new DBbase_Sql;
$PSN->query(
    "SELECT * FROM mail_workqueue
     ORDER BY retry_count ASC, id ASC
     LIMIT " . $batch_size
);

$contadorOk    = 0;
$contadorError = 0;

// ── 5. Procesar cada correo ──────────────────────────────────────────────────

$X = 0;
while ($PSN->next_record()) {
    $X++;

    $idHistorico  = $PSN->f('id');
    $idCampana    = $PSN->f('idCampana');
    $idUsuario    = $PSN->f('idUsuario');
    $retry_count  = (int)$PSN->f('retry_count');
    $remite_correo = $PSN->f('remite_correo');
    $remite_nombre = utf8_decode($PSN->f('remite_nombre'));
    $mail_email   = $PSN->f('mail_email');
    $mail_nombres = utf8_decode($PSN->f('mail_nombres'));
    $asunto_envio = utf8_decode(stripslashes($PSN->f('subject')));

    $audit_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';

    echo "N° $X — $mail_email";

    // ── 5a. Verificar lista negra ────────────────────────────────────────────
    $PSNBl = new DBbase_Sql;
    $PSNBl->query("SELECT id FROM mail_blacklist WHERE correo = '" . addslashes($mail_email) . "'");
    if ($PSNBl->num_rows() > 0) {
        echo " → LISTA NEGRA (rebote previo)\n";
        mq_insertAudit('rebote', $mail_email, $mail_nombres, $idCampana, $remite_correo, $asunto_envio, 'En lista negra (rebote previo)', $idUsuario, $retry_count);
        $PSNT->query("DELETE FROM mail_workqueue WHERE id = '" . $idHistorico . "'");
        $contadorError++;
        continue;
    }

    // ── 5b. Crear instancia PHPMailer por correo (evita estado sucio) ────────
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host     = $PSN->f('host');
        $mail->SMTPAuth = true;
        $mail->Username = $PSN->f('username');
        $mail->Password = $PSN->f('password');
        $mail->Port     = $PSN->f('port');

        $enc = $PSN->f('encriptacion');
        if (!in_array($enc, ['ssl', 'tls'])) {
            $enc = ($PSN->f('port') == 587) ? 'tls' : 'ssl';
        }
        $mail->SMTPSecure = ($enc === 'tls')
            ? PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer::ENCRYPTION_SMTPS;

        $mail->setFrom($remite_correo, $remite_nombre);
        $mail->addAddress($mail_email, $mail_nombres);

        // Adjuntos
        if ($PSN->f('attachment_ruta') != '') {
            $rutas  = explode(',', $PSN->f('attachment_ruta'));
            $nombres = explode(',', $PSN->f('attachment_nombre'));
            foreach ($rutas as $i => $ruta) {
                $ruta = trim($ruta);
                if ($ruta !== '' && file_exists(__DIR__ . '/' . $ruta)) {
                    $mail->addAttachment(__DIR__ . '/' . $ruta, trim($nombres[$i] ?? ''));
                }
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $asunto_envio;
        $mail->Body    = utf8_decode(stripslashes($PSN->f('message')));
        $mail->AltBody = 'Tu correo no admite mensajes en formato HTML.';

        // ── 5c. Intentar envío ───────────────────────────────────────────────
        if (!$mail->send()) {
            $error_info = substr($mail->ErrorInfo, 0, 1000);
            $smtp_inst  = $mail->getSMTPInstance();
            $smtp_err   = ($smtp_inst !== null) ? $smtp_inst->getError() : [];
            $smtp_code  = (string)($smtp_err['smtp_code'] ?? '');
            $is_bounce  = mq_isBounce($smtp_code, $error_info);

            if ($is_bounce) {
                echo " → REBOTE permanente\n";
                mq_insertAudit('rebote', $mail_email, $mail_nombres, $idCampana, $remite_correo, $asunto_envio, $error_info, $idUsuario, $retry_count);
                mq_addBlacklist($mail_email, $error_info, $idCampana);
                $PSNT->query("DELETE FROM mail_workqueue WHERE id = '" . $idHistorico . "'");
                $contadorError++;
            } elseif ($retry_count >= 3) {
                echo " → ERROR (máx. reintentos)\n";
                mq_insertAudit('error', $mail_email, $mail_nombres, $idCampana, $remite_correo, $asunto_envio, 'Máx. reintentos alcanzados. Último error: ' . $error_info, $idUsuario, $retry_count);
                $PSNT->query("DELETE FROM mail_workqueue WHERE id = '" . $idHistorico . "'");
                $contadorError++;
            } else {
                echo " → fallo temporal (reintento " . ($retry_count + 1) . "/3)\n";
                $PSNT->query("UPDATE mail_workqueue SET retry_count = retry_count + 1, batch_id = '" . addslashes($batch_id) . "' WHERE id = '" . $idHistorico . "'");
            }

            // Compatibilidad con mail_historico agregado existente
            $detalle_err   = addslashes(substr($mail->ErrorInfo, 0, 500));
            $asunto_esc    = addslashes(substr($asunto_envio, 0, 500));
            $contenido_esc = addslashes(substr($PSN->f('message'), 0, 65535));
            $sql_hist = "INSERT INTO mail_historico (idCampana, mhi_cuent_fk, error, fecha, audit_usuario, audit_fecha, audit_ip)
                         VALUES ('" . $idCampana . "', '" . $remite_correo . "', 1, '" . date('Y-m-d') . "', '" . $idUsuario . "', NOW(), '" . $audit_ip . "')
                         ON DUPLICATE KEY UPDATE error = (error + 1)";
            $PSNT->query($sql_hist);
            $chkHist = new DBbase_Sql;
            $chkHist->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_historico' AND COLUMN_NAME = 'detalle_error'");
            if ($chkHist->num_rows() > 0) {
                $PSNT->query("UPDATE mail_historico SET detalle_error = '$detalle_err', asunto = '$asunto_esc' WHERE mhi_cuent_fk = '$remite_correo' AND fecha = '" . date('Y-m-d') . "' AND idCampana = '$idCampana'");
            }

        } else {
            // ── 5d. Éxito ────────────────────────────────────────────────────
            echo " → ENVIADO ✓\n";
            mq_insertAudit('enviado', $mail_email, $mail_nombres, $idCampana, $remite_correo, $asunto_envio, '', $idUsuario, $retry_count);
            $PSNT->query("DELETE FROM mail_workqueue WHERE id = '" . $idHistorico . "'");
            $contadorOk++;

            // Compatibilidad con mail_historico
            $asunto_esc    = addslashes(substr($asunto_envio, 0, 500));
            $contenido_esc = addslashes(substr($PSN->f('message'), 0, 65535));
            $sql_hist = "INSERT INTO mail_historico (idCampana, mhi_cuent_fk, correos, fecha, audit_usuario, audit_fecha, audit_ip)
                         VALUES ('" . $idCampana . "', '" . $remite_correo . "', 1, '" . date('Y-m-d') . "', '" . $idUsuario . "', NOW(), '" . $audit_ip . "')
                         ON DUPLICATE KEY UPDATE correos = (correos + 1)";
            $PSNT->query($sql_hist);
            $chkHist2 = new DBbase_Sql;
            $chkHist2->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_historico' AND COLUMN_NAME = 'asunto'");
            if ($chkHist2->num_rows() > 0) {
                $PSNT->query("UPDATE mail_historico SET asunto = '$asunto_esc' WHERE mhi_cuent_fk = '$remite_correo' AND fecha = '" . date('Y-m-d') . "' AND idCampana = '$idCampana'");
            }
            $chkHist3 = new DBbase_Sql;
            $chkHist3->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mail_historico' AND COLUMN_NAME = 'contenido'");
            if ($chkHist3->num_rows() > 0) {
                $PSNT->query("UPDATE mail_historico SET contenido = '$contenido_esc' WHERE mhi_cuent_fk = '$remite_correo' AND fecha = '" . date('Y-m-d') . "' AND idCampana = '$idCampana'");
            }
        }

    } catch (Exception $e) {
        $error_info = substr($mail->ErrorInfo, 0, 1000);
        echo " → EXCEPCIÓN: $error_info\n";

        if (mq_isBounce('', $error_info) || $retry_count >= 3) {
            mq_insertAudit('rebote', $mail_email, $mail_nombres, $idCampana, $remite_correo, $asunto_envio, $error_info, $idUsuario, $retry_count);
            mq_addBlacklist($mail_email, $error_info, $idCampana);
            $PSNT->query("DELETE FROM mail_workqueue WHERE id = '" . $idHistorico . "'");
            $contadorError++;
        } else {
            $PSNT->query("UPDATE mail_workqueue SET retry_count = retry_count + 1 WHERE id = '" . $idHistorico . "'");
        }
    }

} // fin while

// ── 6. Resumen ───────────────────────────────────────────────────────────────

if ($contadorOk > 0)    echo "\n\nMENSAJES ENVIADOS CON ÉXITO: $contadorOk";
if ($contadorError > 0) echo "\n\nMENSAJES CON ERROR: $contadorError";
echo "\n\nBATCH: $batch_size correos procesados (pendientes antes: $total_pendiente, enviados última hora: $enviados_hora)";
?>
