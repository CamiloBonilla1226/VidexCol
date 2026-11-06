<?php
/**
 * Cron diario de alertas de vencimiento (Dominios, SSL y Servicios).
 *
 * Que hace: por cada item (dominio / certificado SSL / servicio) a
 * GP_ALERTAS_DIAS_ANTICIPACION dias o menos de vencer (incluye los ya
 * vencidos: sigue avisando hasta que se renueve), envia UN correo a cada
 * destinatario activo. Si ya se envio hoy para ese item especifico, no lo
 * repite (tabla gp_alertas_enviadas, UNIQUE por tipo+referencia+dia) - por
 * eso es seguro programar este cron cada hora o cada dia, da igual cuantas
 * veces corra, nunca duplica el correo del mismo dia.
 *
 * Como se activa: este archivo NO se ejecuta solo. Hay que crear un cron job
 * en cPanel (Cron Jobs) que corra UNA VEZ AL DIA, por ejemplo:
 *   php /home/<usuario_cpanel>/videx.sms-app.co/gp_alertas_cron.php
 * o, si el panel solo permite URLs:
 *   curl -s "https://videx.sms-app.co/gp_alertas_cron.php?key=LA_CLAVE_DE_ABAJO" > /dev/null
 *
 * Seguridad: a diferencia de mail_cron.php (que no necesita proteccion
 * porque solo vacia una cola ya validada), este script SI envia correos
 * nuevos cada vez que encuentra items pendientes, asi que si se llama por
 * HTTP exige esta clave para que no cualquiera en internet lo dispare.
 * Por CLI (el cron real de cPanel) no se exige: php-cli no tiene forma de
 * que un tercero lo invoque.
 */
define('GP_ALERTAS_CRON_CLAVE', 'videx_alertas_2026_cambia_esta_clave');

$esCli = (php_sapi_name() === 'cli');
if (!$esCli) {
    if (($_GET['key'] ?? '') !== GP_ALERTAS_CRON_CLAVE) {
        http_response_code(403);
        die('No autorizado.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require_once(__DIR__ . '/funciones.php');
require_once(__DIR__ . '/gp_alertas_service.php');
require_once(__DIR__ . '/gp_alertas_mail.php');

$service = new GpAlertasService();
$destinatarios = $service->listarDestinatariosActivos();

$etiquetasTipo = ['dominio' => 'Dominio', 'ssl' => 'Certificado SSL', 'servicio' => 'Servicio'];

echo "=== Alertas de vencimiento — " . date('Y-m-d H:i:s') . " ===\n";

if (empty($destinatarios)) {
    echo "Sin destinatarios activos configurados (index.php?doc=gp_alertas_email). No se envia nada.\n";
    exit;
}

$items = $service->listarItemsEnVentanaDeAlerta();
if (empty($items)) {
    echo "Nada vence en los proximos " . GP_ALERTAS_DIAS_ANTICIPACION . " dias. No hay nada que avisar hoy.\n";
    exit;
}

$enviados = 0;
$omitidos = 0;

foreach ($items as $it) {
    if ($service->yaEnviadoHoy($it['tipo'], $it['id'])) {
        echo "- [" . $it['tipo'] . "#" . $it['id'] . "] " . $it['etiqueta'] . " -> ya se avisó hoy, se omite.\n";
        $omitidos++;
        continue;
    }

    $dias = gp_dias_para_vencer($it['vencimiento']);
    $tipoLegible = $etiquetasTipo[$it['tipo']] ?? $it['tipo'];
    $asunto = '⚠️ ' . ($dias !== null && $dias < 0 ? 'VENCIDO' : 'Vence pronto') . ': ' . $tipoLegible . ' "' . $it['etiqueta'] . '"';
    $cuerpo = gp_alertas_plantilla_correo($tipoLegible, $it['etiqueta'], $it['contexto'], $dias, $it['vencimiento']);

    $ok = gp_alertas_enviar_correo($destinatarios, $asunto, $cuerpo);
    if ($ok) {
        $service->registrarEnvio($it['tipo'], $it['id'], $it['vencimiento']);
        echo "- [" . $it['tipo'] . "#" . $it['id'] . "] " . $it['etiqueta'] . " -> enviado a " . count($destinatarios) . " destinatario(s).\n";
        $enviados++;
    } else {
        echo "- [" . $it['tipo'] . "#" . $it['id'] . "] " . $it['etiqueta'] . " -> ERROR al enviar (revisar error_log). Se reintentará en la próxima corrida.\n";
    }
}

echo "Listo. Enviados: $enviados. Omitidos (ya avisados hoy): $omitidos. Total en ventana: " . count($items) . ".\n";
