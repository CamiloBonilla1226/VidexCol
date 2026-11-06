<?php
/**
 * Claves (Inventario) - endpoint AJAX. Toda validacion de seguridad es de
 * backend; el frontend solo oculta controles por comodidad.
 *
 *  - Requiere sesion + permiso especial gp_claves (consultado en vivo en la BD).
 *  - Todas las acciones son POST con token CSRF.
 *  - Cada clave solo es accesible por su duenio (usuario_id).
 *  - revelar: exige reingresar la contraseña del usuario (con limite de
 *    intentos y bitacora). Al confirmarla queda "desbloqueada" esa clave
 *    durante GP_CLAVES_REAUTH_SEGUNDOS para poder editarla/eliminarla.
 *  - guardar (editar) y eliminar: exigen ese desbloqueo reciente; no basta
 *    con tener la sesion abierta.
 */
session_start();
include_once('funciones.php');
require_once('gp_crypto.php'); // solo para gp_verificar_password_propia()
require_once('gp_claves_config.php');
require_once('gp_claves_service.php');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function gpc_responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

if (!is_logged_in()) {
    gpc_responder(['ok' => false, 'error' => 'No autorizado.'], 401);
}
gp_claves_requerir_acceso_json();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gpc_responder(['ok' => false, 'error' => 'Método no permitido.'], 405);
}
if (empty($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
    gpc_responder(['ok' => false, 'error' => 'Sesión expirada. Recargue la página.'], 403);
}

$idUsuario = (int) $_SESSION['id'];
$accion = $_POST['accion'] ?? '';
$id = (int) ($_POST['id'] ?? 0);

try {
    $service = new GpClaveService();
} catch (Exception $e) {
    gpc_responder(['ok' => false, 'error' => 'El módulo de claves no está disponible.'], 500);
}

/** Marca una clave como desbloqueada (reautenticada) por un tiempo limitado. */
function gpc_desbloquear($id) {
    $_SESSION['claves_desbloqueadas'][(int) $id] = time();
}

function gpc_esta_desbloqueada($id) {
    $t = $_SESSION['claves_desbloqueadas'][(int) $id] ?? 0;
    return $t > 0 && (time() - $t) <= GP_CLAVES_REAUTH_SEGUNDOS;
}

if ($accion === 'revelar') {
    if ($id <= 0) {
        gpc_responder(['ok' => false, 'error' => 'Solicitud inválida.'], 400);
    }
    if ($service->estaBloqueado($idUsuario)) {
        gpc_responder(['ok' => false, 'error' => 'Demasiados intentos fallidos. Espere ' . GP_CLAVES_VENTANA_MINUTOS . ' minutos.'], 429);
    }
    $passwordActual = (string) ($_POST['password_actual'] ?? '');
    if (!gp_verificar_password_propia($idUsuario, $passwordActual)) {
        $service->registrarAcceso($idUsuario, $id, 'reauth_fallida');
        gpc_responder(['ok' => false, 'error' => 'Contraseña incorrecta.'], 403);
    }
    $item = $service->revelar($id, $idUsuario);
    if ($item === null) {
        gpc_responder(['ok' => false, 'error' => 'Clave no encontrada.'], 404);
    }
    if ($item === false) {
        gpc_responder(['ok' => false, 'error' => 'No se pudo descifrar esta clave (llave de cifrado distinta o dato alterado).'], 500);
    }
    gpc_desbloquear($id);
    $service->registrarAcceso($idUsuario, $id, 'revelar');
    gpc_responder(['ok' => true, 'item' => $item]);
}

if ($accion === 'guardar') {
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $data = [
        'titulo' => mb_substr($titulo, 0, 150),
        'categoria' => mb_substr(trim((string) ($_POST['categoria'] ?? '')), 0, 60),
        'sitio' => mb_substr(trim((string) ($_POST['sitio'] ?? '')), 0, 255),
        'usuario' => (string) ($_POST['usuario'] ?? ''),
        'clave' => (string) ($_POST['clave'] ?? ''),
        'notas' => (string) ($_POST['notas'] ?? ''),
    ];
    if ($titulo === '') {
        gpc_responder(['ok' => false, 'error' => 'El título es obligatorio.'], 422);
    }
    if ($id > 0) {
        if (!$service->existe($id, $idUsuario)) {
            gpc_responder(['ok' => false, 'error' => 'Clave no encontrada.'], 404);
        }
        if (!gpc_esta_desbloqueada($id)) {
            gpc_responder(['ok' => false, 'error' => 'Confirme su contraseña nuevamente para editar esta clave.'], 403);
        }
    }
    $resultado = $service->guardar($id, $data, $idUsuario);
    if (!$resultado) {
        gpc_responder(['ok' => false, 'error' => 'No se pudo guardar la clave.'], 500);
    }
    $service->registrarAcceso($idUsuario, $resultado, $id > 0 ? 'editar' : 'crear');
    if ($id === 0) {
        gpc_desbloquear($resultado);
    }
    gpc_responder(['ok' => true, 'id' => (int) $resultado]);
}

if ($accion === 'eliminar') {
    if ($id <= 0 || !$service->existe($id, $idUsuario)) {
        gpc_responder(['ok' => false, 'error' => 'Clave no encontrada.'], 404);
    }
    if (!gpc_esta_desbloqueada($id)) {
        gpc_responder(['ok' => false, 'error' => 'Confirme su contraseña nuevamente para eliminar esta clave.'], 403);
    }
    if (!$service->eliminar($id, $idUsuario)) {
        gpc_responder(['ok' => false, 'error' => 'No se pudo eliminar.'], 500);
    }
    unset($_SESSION['claves_desbloqueadas'][$id]);
    $service->registrarAcceso($idUsuario, $id, 'eliminar');
    gpc_responder(['ok' => true]);
}

gpc_responder(['ok' => false, 'error' => 'Acción inválida.'], 400);
