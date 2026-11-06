<?php
/**
 * Inventario de Dominios - endpoint AJAX (crear/editar/eliminar dominios).
 * Requiere sesion, permiso de modificacion de Gestion Proyectos y token CSRF.
 */
session_start();
include_once('funciones.php');
require_once('gp_config.php');
require_once('gp_dom_service.php');

header('Content-Type: application/json; charset=utf-8');

function gpd_responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

if (!is_logged_in()) {
    gpd_responder(['error' => 'No autorizado.'], 401);
}
if (!gp_puede_consultar() && !gp_puede_modificar()) {
    gpd_responder(['error' => 'No autorizado.'], 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gpd_responder(['error' => 'Método no permitido.'], 405);
}
if (empty($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
    gpd_responder(['error' => 'Sesión expirada. Recargue la página.'], 403);
}
// Toda escritura exige el permiso de modificacion del modulo (verificacion real de backend).
gp_requerir_modificacion();

$service = new GpDomService();
$accion = $_POST['accion'] ?? '';
$idUsuario = (int) $_SESSION['id'];

if ($accion === 'guardar') {
    $id = (int) ($_POST['id'] ?? 0);
    $proveedorId = (int) ($_POST['proveedor_id'] ?? 0);
    if (!$service->getProveedor($proveedorId)) {
        gpd_responder(['error' => 'Proveedor no encontrado.'], 404);
    }
    if ($id > 0) {
        $existente = $service->getDominio($id);
        if (!$existente || (int) $existente['proveedor_id'] !== $proveedorId) {
            gpd_responder(['error' => 'Dominio no encontrado.'], 404);
        }
    }
    $dominio = GpDomService::normalizarDominio($_POST['dominio'] ?? '');
    if ($dominio === '') {
        gpd_responder(['error' => 'El dominio es obligatorio.'], 422);
    }
    if (!GpDomService::dominioValido($dominio)) {
        gpd_responder(['error' => 'El dominio no es válido (ej: midominio.com).'], 422);
    }
    $en = $service->dominioYaRegistrado($dominio, $id);
    if ($en !== null) {
        gpd_responder(['error' => 'Ese dominio ya está registrado en el proveedor "' . $en . '".'], 409);
    }
    $registro = trim((string) ($_POST['fecha_registro'] ?? ''));
    $vence = trim((string) ($_POST['fecha_vencimiento'] ?? ''));
    if ($registro !== '' && $vence !== '' && $vence < $registro) {
        gpd_responder(['error' => 'La fecha de vencimiento no puede ser anterior a la de registro.'], 422);
    }
    // SSL es opcional: el dominio puede no tener certificado registrado aun.
    $sslVence = trim((string) ($_POST['ssl_fecha_vencimiento'] ?? ''));
    if ($sslVence !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sslVence)) {
        gpd_responder(['error' => 'La fecha de vencimiento del SSL no es válida.'], 422);
    }
    $resultado = $service->guardarDominio($id, [
        'proveedor_id' => $proveedorId,
        'dominio' => $dominio,
        'fecha_registro' => $registro,
        'fecha_vencimiento' => $vence,
        'renovacion_automatica' => !empty($_POST['renovacion_automatica']),
        'estado' => !empty($_POST['estado']),
        'notas' => trim((string) ($_POST['notas'] ?? '')),
        'ssl_fecha_vencimiento' => $sslVence,
        'ssl_proveedor' => trim((string) ($_POST['ssl_proveedor'] ?? '')),
        'ssl_notas' => trim((string) ($_POST['ssl_notas'] ?? '')),
    ], $idUsuario);
    $resultado ? gpd_responder(['ok' => true, 'id' => (int) $resultado]) : gpd_responder(['error' => 'No se pudo guardar.'], 500);
}

if ($accion === 'eliminar') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0 || !$service->getDominio($id)) {
        gpd_responder(['error' => 'Dominio no encontrado.'], 404);
    }
    $service->eliminarDominio($id, $idUsuario) ? gpd_responder(['ok' => true]) : gpd_responder(['error' => 'No se pudo eliminar.'], 500);
}

gpd_responder(['error' => 'Acción inválida.'], 400);
