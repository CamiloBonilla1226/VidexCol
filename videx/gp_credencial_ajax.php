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
$padre = isset($_REQUEST['padre']) ? $_REQUEST['padre'] : ''; // 'proveedor' | 'servicio' | 'proyecto'
$accion = isset($_REQUEST['accion']) ? $_REQUEST['accion'] : '';

if (!in_array($padre, ['proveedor', 'servicio', 'proyecto'], true)) {
    echo json_encode(['error' => 'Padre inválido.']);
    exit;
}

function gp_metodo_por_padre($padre, $sufijo) {
    $mapa = ['proveedor' => 'Proveedor', 'servicio' => 'Servicio', 'proyecto' => 'Proyecto'];
    return $sufijo . $mapa[$padre];
}

if ($accion === 'listar') {
    $padreId = (int) ($_GET['padre_id'] ?? 0);
    $metodo = gp_metodo_por_padre($padre, 'listarCredenciales');
    echo json_encode(['ok' => true, 'items' => $service->$metodo($padreId)]);
    exit;
}

if ($accion === 'guardar') {
    gp_requerir_modificacion();
    $id = (int) ($_POST['id'] ?? 0);
    $data = [
        'tipo' => trim($_POST['tipo'] ?? ''),
        'user' => trim($_POST['user'] ?? ''),
        'pass' => $_POST['pass'] ?? '',
        'host' => trim($_POST['host'] ?? ''),
        'puerto' => trim($_POST['puerto'] ?? ''),
        'destino' => trim($_POST['destino'] ?? ''),
    ];
    if ($data['tipo'] === '') {
        echo json_encode(['error' => 'El tipo es obligatorio.']);
        exit;
    }
    if ($padre === 'proyecto') {
        $data['persona_asignada'] = trim($_POST['persona_asignada'] ?? '');
        if ($data['persona_asignada'] === '') {
            echo json_encode(['error' => 'La persona asignada es obligatoria.']);
            exit;
        }
    }
    $data[$padre . '_id'] = (int) ($_POST['padre_id'] ?? 0);
    $metodo = gp_metodo_por_padre($padre, 'guardarCredencial');
    $resultado = $service->$metodo($id, $data, $_SESSION['id']);
    echo $resultado ? json_encode(['ok' => true, 'id' => $resultado]) : json_encode(['error' => 'No se pudo guardar.']);
    exit;
}

if ($accion === 'eliminar') {
    gp_requerir_modificacion();
    $id = (int) ($_POST['id'] ?? 0);
    $metodo = gp_metodo_por_padre($padre, 'eliminarCredencial');
    echo json_encode(['ok' => (bool) $service->$metodo($id)]);
    exit;
}

echo json_encode(['error' => 'Acción inválida.']);
