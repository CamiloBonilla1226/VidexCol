<?php
/**
 * Entrega un anexo almacenado fuera del docroot, verificando que el usuario
 * tenga acceso al proyecto de inversion dueño del movimiento. Nunca hay una
 * URL publica directa al archivo: todo pasa por aqui.
 */
session_start();
include_once('funciones.php');
require_once('gp_inversion_config.php');
require_once('gp_inversion_service.php');

if (!is_logged_in()) {
    http_response_code(401);
    die('No autorizado.');
}

$service = new GpInversionService();
$id = (int) ($_GET['id'] ?? 0);
$anexo = $id > 0 ? $service->getAnexo($id) : null;
if (!$anexo) {
    http_response_code(404);
    die('Anexo no encontrado.');
}
$movimiento = $service->getMovimiento($anexo['movimiento_id']);
if (!$movimiento) {
    http_response_code(404);
    die('Anexo no encontrado.');
}
$proyecto = $service->getProyecto($movimiento['proyecto_id']);
if (!$proyecto) {
    http_response_code(404);
    die('Anexo no encontrado.');
}
$restriccion = gp_inv_cliente_restriccion();
$tieneAccesoStaff = gp_inv_puede_consultar();
if (!$tieneAccesoStaff && ($restriccion === null || (int) $proyecto['cliente_id'] !== $restriccion)) {
    http_response_code(403);
    die('No tiene acceso a este archivo.');
}

$ruta = $service->rutaFisicaAnexo($anexo);
if (!is_file($ruta)) {
    http_response_code(404);
    die('El archivo ya no está disponible.');
}

// ?dl=1 fuerza la descarga; sin el, el navegador lo muestra en linea (vista previa de PDF/imagenes).
$disposicion = isset($_GET['dl']) ? 'attachment' : 'inline';
$nombreSeguro = str_replace(['"', "\r", "\n", '\\'], '_', (string) $anexo['nombre_original']);
header('Content-Type: ' . $anexo['mime_tipo']);
header('Content-Disposition: ' . $disposicion . '; filename="' . $nombreSeguro . '"; filename*=UTF-8\'\'' . rawurlencode((string) $anexo['nombre_original']));
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');
readfile($ruta);
exit;
