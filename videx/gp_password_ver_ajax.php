<?php
session_start();
include_once('funciones.php');
require_once('gp_config.php');
require_once('gp_crypto.php');

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado.']);
    exit;
}

if (!gp_puede_ver_credenciales()) {
    echo json_encode(['ok' => false, 'error' => 'No tiene permiso para ver credenciales.']);
    exit;
}

$tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$passwordActual = isset($_POST['password_actual']) ? (string) $_POST['password_actual'] : '';

if (!gp_verificar_password_propia($_SESSION['id'], $passwordActual)) {
    echo json_encode(['ok' => false, 'error' => 'Contraseña incorrecta.']);
    exit;
}

$tablasPermitidas = [
    'proveedor' => ['tabla' => 'gp_proveedor', 'columna' => 'pass_cifrado'],
    'dom_proveedor' => ['tabla' => 'gp_dom_proveedor', 'columna' => 'pass_cifrado'],
    'proveedor_credencial' => ['tabla' => 'gp_proveedor_credencial', 'columna' => 'pass_cifrado'],
    'servicio_credencial' => ['tabla' => 'gp_servicio_credencial', 'columna' => 'pass_cifrado'],
    'proyecto_credencial' => ['tabla' => 'gp_proyecto_credencial', 'columna' => 'pass_cifrado'],
];

if (!isset($tablasPermitidas[$tipo]) || $id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Solicitud inválida.']);
    exit;
}

$conn = mysqli_connect('localhost', 'twsselvr_root', 'Videx@2024*.', 'twsselvr_videx_app');
if (!$conn) {
    echo json_encode(['ok' => false, 'error' => 'Error de conexión.']);
    exit;
}

$def = $tablasPermitidas[$tipo];
$r = mysqli_query($conn, "SELECT {$def['columna']} AS valor FROM {$def['tabla']} WHERE id = $id");
$row = $r ? mysqli_fetch_assoc($r) : null;
mysqli_close($conn);

if (!$row) {
    echo json_encode(['ok' => false, 'error' => 'Registro no encontrado.']);
    exit;
}

echo json_encode(['ok' => true, 'valor' => gp_decrypt($row['valor'])]);
