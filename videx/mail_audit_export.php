<?php
/**
 * mail_audit_export.php
 * Descarga la tabla mail_audit como CSV con filtros opcionales.
 */
session_start();
include_once(__DIR__ . '/funciones.php');

if (
    empty($_SESSION['id']) ||
    ($_SESSION['perfil'] != 1 && $_SESSION['perfil'] != 2 &&
     $_SESSION['perfil'] != 161 && $_SESSION['perfil'] != 162 && $_SESSION['perfil'] != 163)
) {
    http_response_code(403);
    die('Acceso denegado.');
}

$filtro_estado  = in_array($_GET['filtro_estado'] ?? '', ['enviado','error','rebote','invalido','duplicado','']) ? ($_GET['filtro_estado'] ?? '') : '';
$filtro_campana = soloNumeros($_GET['filtro_campana'] ?? '');
$filtro_fecha   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['filtro_fecha'] ?? '') ? $_GET['filtro_fecha'] : '';

$whereAudit = "WHERE 1";
if ($filtro_estado  !== '') $whereAudit .= " AND ma.estado='"  . addslashes($filtro_estado)  . "'";
if ($filtro_campana !== '') $whereAudit .= " AND ma.idCampana='$filtro_campana'";
if ($filtro_fecha   !== '') $whereAudit .= " AND DATE(ma.fecha)='" . addslashes($filtro_fecha) . "'";

$db = new DBbase_Sql;
$db->query(
    "SELECT ma.id, ma.fecha, ma.estado, ma.correo, ma.nombre AS audit_nombre,
            ma.asunto, c.nombre AS campana_nombre, ma.remitente,
            ma.razon, ma.retry_count
     FROM mail_audit ma
     LEFT JOIN campana c ON c.id = ma.idCampana
     $whereAudit
     ORDER BY ma.fecha DESC
     LIMIT 50000"
);

$filename = 'auditoria_correos_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

// BOM UTF-8 para compatibilidad con Excel
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['#', 'Fecha', 'Estado', 'Correo', 'Nombre', 'Asunto', 'Campaña', 'Remitente', 'Razón / Error', 'Reintentos']);

$n = 1;
while ($db->num_rows() > 0 && $db->next_record()) {
    fputcsv($out, [
        $n++,
        $db->f('fecha'),
        $db->f('estado'),
        $db->f('correo'),
        $db->f('audit_nombre'),
        $db->f('asunto'),
        $db->f('campana_nombre'),
        $db->f('remitente'),
        $db->f('razon'),
        $db->f('retry_count'),
    ]);
}

fclose($out);
exit;
?>
