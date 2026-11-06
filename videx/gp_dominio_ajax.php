<?php
session_start();
include_once('funciones.php');
require_once('gp_config.php');
require_once('gp_service.php');

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['error' => 'No autorizado.']);
    exit;
}
if (!gp_puede_consultar() && !gp_puede_modificar()) {
    echo json_encode(['error' => 'No autorizado.']);
    exit;
}

$service = new GpService();
$accion = isset($_REQUEST['accion']) ? $_REQUEST['accion'] : '';

if ($accion === 'listar') {
    $servicioId = (int) ($_GET['servicio_id'] ?? 0);
    echo json_encode(['ok' => true, 'items' => $service->listarDominiosPorServicio($servicioId)]);
    exit;
}

if ($accion === 'proyectos_disponibles') {
    // Solo proyectos del mismo servicio (alcance confirmado con el usuario)
    $servicioId = (int) ($_GET['servicio_id'] ?? 0);
    echo json_encode(['ok' => true, 'items' => $service->listarProyectosPorServicio($servicioId)]);
    exit;
}

if ($accion === 'guardar') {
    gp_requerir_modificacion();
    $id = (int) ($_POST['id'] ?? 0);
    $data = [
        'servicio_id' => (int) ($_POST['servicio_id'] ?? 0),
        'dominio' => trim($_POST['dominio'] ?? ''),
        'descripcion' => trim($_POST['descripcion'] ?? ''),
        'carpeta_destino' => trim($_POST['carpeta_destino'] ?? ''),
        'proyecto_id' => (int) ($_POST['proyecto_id'] ?? 0),
        'estado' => (int) ($_POST['estado'] ?? 1),
    ];
    if ($data['dominio'] === '') {
        echo json_encode(['error' => 'El dominio es obligatorio.']);
        exit;
    }
    $resultado = $service->guardarDominio($id, $data, $_SESSION['id']);
    echo $resultado ? json_encode(['ok' => true, 'id' => $resultado]) : json_encode(['error' => 'No se pudo guardar.']);
    exit;
}

if ($accion === 'eliminar') {
    gp_requerir_modificacion();
    $id = (int) ($_POST['id'] ?? 0);
    echo json_encode(['ok' => (bool) $service->eliminarDominio($id)]);
    exit;
}

echo json_encode(['error' => 'Acción inválida.']);
