<?php
/**
 * Tickets/Tareas - Informe diario (cron).
 *
 * Pensado para ejecutarse por CLI real de cPanel (Cron Job -> `php /ruta/gp_ticket_informe_diario.php`),
 * todos los dias a las 9:00 AM hora Colombia (el servidor ya usa America/Bogota
 * via date_default_timezone_set() en funciones.php, asi que no hace falta
 * conversion de zona horaria: programar el cron directamente a las 9:00).
 *
 * Tambien se puede disparar por URL (ej. si el hosting no permite cron real y
 * se usa un servicio externo tipo cron-job.org) pasando ?s=SECRETO.
 */
const GP_TICKET_INFORME_SECRETO = 'InformeTk_9am_2026_x7Q';

$esCli = (php_sapi_name() === 'cli');
$secretoValido = isset($_GET['s']) && $_GET['s'] === GP_TICKET_INFORME_SECRETO;
if (!$esCli && !$secretoValido) {
    http_response_code(403);
    die('No autorizado.');
}

require_once(__DIR__ . '/funciones.php');
require_once(__DIR__ . '/gp_tickets_config.php');
require_once(__DIR__ . '/gp_ticket_service.php');
require_once(__DIR__ . '/gp_ticket_mail.php');

$service = new GpTicketService();
$tickets = $service->listarTicketsActivosParaInforme();

$porUsuario = [];
foreach ($tickets as $t) {
    foreach (['solicitado_por', 'asignado_a'] as $rolCampo) {
        $uid = (int) $t[$rolCampo];
        if ($uid <= 0) { continue; }
        $nombre = $t[$rolCampo . '_nombre'];
        $email = $t[$rolCampo . '_email'];
        if (!$email) { continue; }
        if (!isset($porUsuario[$uid])) {
            $porUsuario[$uid] = ['nombre' => $nombre, 'email' => $email, 'tickets' => []];
        }
        // Evitar duplicar el mismo ticket si el usuario es solicitante Y asignado.
        $yaEsta = false;
        foreach ($porUsuario[$uid]['tickets'] as $existente) {
            if ($existente['id'] == $t['id']) { $yaEsta = true; break; }
        }
        if (!$yaEsta) {
            $porUsuario[$uid]['tickets'][] = $t;
        }
    }
}

$enviados = 0;
foreach ($porUsuario as $uid => $info) {
    $filas = '';
    foreach ($info['tickets'] as $t) {
        $filas .= '<tr>'
            . '<td style="padding:8px;border-bottom:1px solid #e2e8f0;">' . htmlspecialchars($t['titulo']) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #e2e8f0;">' . htmlspecialchars(GP_TICKET_ESTADOS[$t['estado']] ?? $t['estado']) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #e2e8f0;">' . htmlspecialchars($t['proyecto_nombre'] ?? '—') . '</td>'
            . '</tr>';
    }
    $cuerpo = '<p>Hola ' . htmlspecialchars($info['nombre']) . ', este es tu resumen diario de tareas activas (no incluye Solicitado, ni Finalizadas de más de un día):</p>'
        . '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
        . '<thead><tr><th style="text-align:left;padding:8px;border-bottom:2px solid #cbd5e1;">Título</th><th style="text-align:left;padding:8px;border-bottom:2px solid #cbd5e1;">Estado</th><th style="text-align:left;padding:8px;border-bottom:2px solid #cbd5e1;">Proyecto</th></tr></thead>'
        . '<tbody>' . $filas . '</tbody></table>';

    $ok = gp_ticket_enviar_correo(
        $info['email'],
        'Resumen diario de tickets (' . count($info['tickets']) . ')',
        gp_ticket_plantilla_correo('Resumen diario de tickets', $cuerpo, 'https://videx.sms-app.co/index.php?doc=gp_tickets')
    );
    if ($ok) { $enviados++; }
}

echo date('Y-m-d H:i:s') . " - Informe diario de tickets: " . $enviados . " correo(s) enviado(s) de " . count($porUsuario) . " usuario(s) con tareas activas.\n";
