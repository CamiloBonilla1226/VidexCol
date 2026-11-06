<?php
/**
 * Proyecto de Inversion - endpoint AJAX (crear/editar/eliminar proyectos,
 * cuentas, ingresos y egresos, y subir sus anexos).
 * Requiere sesion, permiso de escritura del modulo y token CSRF.
 */
session_start();
include_once('funciones.php');
require_once('gp_inversion_config.php');
require_once('gp_inversion_service.php');

header('Content-Type: application/json; charset=utf-8');

function gpi_responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

if (!is_logged_in()) {
    gpi_responder(['error' => 'No autorizado.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gpi_responder(['error' => 'Método no permitido.'], 405);
}
if (empty($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
    gpi_responder(['error' => 'Sesión expirada. Recargue la página.'], 403);
}
// Toda escritura exige el permiso de modificacion del modulo (verificacion real de backend).
gp_inv_requerir_escritura();

$service = new GpInversionService();
$accion = $_POST['accion'] ?? '';
$idUsuario = (int) $_SESSION['id'];

function gpi_guardar_anexos(GpInversionService $service, $movimientoId, $idUsuario) {
    for ($slot = 1; $slot <= 3; $slot++) {
        if (!isset($_FILES['anexo' . $slot])) {
            continue;
        }
        $resultado = $service->guardarAnexo($movimientoId, $slot, $_FILES['anexo' . $slot], $idUsuario);
        if (is_array($resultado) && isset($resultado['error'])) {
            gpi_responder(['error' => 'Anexo ' . $slot . ': ' . $resultado['error']], 422);
        }
    }
}

if ($accion === 'guardar_proyecto') {
    $clienteId = (int) ($_POST['cliente_id'] ?? 0);
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    if ($clienteId <= 0 || $nombre === '') {
        gpi_responder(['error' => 'Cliente y nombre del proyecto son obligatorios.'], 422);
    }
    $resultado = $service->guardarProyecto(0, [
        'cliente_id' => $clienteId,
        'nombre' => $nombre,
        'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
        'estado' => 1,
    ], $idUsuario);
    $resultado ? gpi_responder(['ok' => true, 'id' => (int) $resultado]) : gpi_responder(['error' => 'No se pudo crear el proyecto.'], 500);
}

if ($accion === 'guardar_cuenta') {
    $proyectoId = (int) ($_POST['proyecto_id'] ?? 0);
    $banco = trim((string) ($_POST['banco'] ?? ''));
    if ($proyectoId <= 0 || !$service->getProyecto($proyectoId)) {
        gpi_responder(['error' => 'Proyecto no encontrado.'], 404);
    }
    if ($banco === '') {
        gpi_responder(['error' => 'El banco es obligatorio.'], 422);
    }
    $resultado = $service->guardarCuenta(0, [
        'proyecto_id' => $proyectoId,
        'banco' => $banco,
        'tipo_cuenta' => trim((string) ($_POST['tipo_cuenta'] ?? '')),
        'numero_alias' => trim((string) ($_POST['numero_alias'] ?? '')),
    ], $idUsuario);
    $resultado ? gpi_responder(['ok' => true, 'id' => (int) $resultado]) : gpi_responder(['error' => 'No se pudo guardar la cuenta.'], 500);
}

if ($accion === 'guardar_ingreso' || $accion === 'guardar_egreso') {
    $id = (int) ($_POST['id'] ?? 0);
    $proyectoId = (int) ($_POST['proyecto_id'] ?? 0);
    $proyecto = $service->getProyecto($proyectoId);
    if (!$proyecto) {
        gpi_responder(['error' => 'Proyecto no encontrado.'], 404);
    }
    if ($id > 0) {
        $existente = $service->getMovimiento($id);
        if (!$existente || (int) $existente['proyecto_id'] !== $proyectoId) {
            gpi_responder(['error' => 'Movimiento no encontrado.'], 404);
        }
        if (!gp_inv_puede_editar_movimiento($existente['creacion_fecha'])) {
            gpi_responder(['error' => 'Ya pasó la ventana de edición permitida (' . GP_INV_HORAS_GRACIA_EDICION . ' horas) para este movimiento.'], 403);
        }
    }
    $fecha = trim((string) ($_POST['fecha'] ?? ''));
    $valor = (float) str_replace([',', '$', ' '], '', (string) ($_POST['valor'] ?? '0'));
    if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        gpi_responder(['error' => 'La fecha es obligatoria.'], 422);
    }
    if ($valor <= 0) {
        gpi_responder(['error' => 'El valor debe ser mayor a cero.'], 422);
    }

    $datos = [
        'fecha' => $fecha,
        'valor' => $valor,
        'moneda' => 'COP',
        'cuenta_id' => $_POST['cuenta_id'] ?? '',
        'concepto' => trim((string) ($_POST['concepto'] ?? '')),
    ];

    if ($accion === 'guardar_ingreso') {
        $tipoIngreso = $_POST['tipo_ingreso'] ?? '';
        if (!isset(GP_INV_TIPOS_INGRESO[$tipoIngreso])) {
            gpi_responder(['error' => 'El tipo de ingreso no es válido.'], 422);
        }
        $datos['tipo_ingreso'] = $tipoIngreso;
        $datos['origen_fondos'] = trim((string) ($_POST['origen_fondos'] ?? ''));
        $resultado = $service->guardarIngreso($id, array_merge($datos, ['proyecto_id' => $proyectoId]), $idUsuario);
    } else {
        $clasificacion = $_POST['clasificacion_egreso'] ?? '';
        if (!isset(GP_INV_CLASIFICACION_EGRESO[$clasificacion])) {
            gpi_responder(['error' => 'La clasificación del egreso no es válida.'], 422);
        }
        $datos['clasificacion_egreso'] = $clasificacion;
        $datos['destino_fondo'] = trim((string) ($_POST['destino_fondo'] ?? ''));
        $resultado = $service->guardarEgreso($id, array_merge($datos, ['proyecto_id' => $proyectoId]), $idUsuario);
    }

    if (!$resultado) {
        gpi_responder(['error' => 'No se pudo guardar el movimiento.'], 500);
    }
    gpi_guardar_anexos($service, (int) $resultado, $idUsuario);
    gpi_responder(['ok' => true, 'id' => (int) $resultado]);
}

if ($accion === 'eliminar_proyecto') {
    if (!gp_inv_puede_eliminar()) {
        gpi_responder(['error' => 'No tiene permiso para eliminar proyectos de inversión.'], 403);
    }
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0 || !$service->getProyecto($id)) {
        gpi_responder(['error' => 'Proyecto no encontrado.'], 404);
    }
    $service->eliminarProyecto($id, $idUsuario) ? gpi_responder(['ok' => true]) : gpi_responder(['error' => $service->getLastError() ?: 'No se pudo eliminar.'], 500);
}

if ($accion === 'eliminar_movimiento') {
    if (!gp_inv_puede_eliminar()) {
        gpi_responder(['error' => 'No tiene permiso para eliminar movimientos.'], 403);
    }
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0 || !$service->getMovimiento($id)) {
        gpi_responder(['error' => 'Movimiento no encontrado.'], 404);
    }
    $service->eliminarMovimiento($id, $idUsuario) ? gpi_responder(['ok' => true]) : gpi_responder(['error' => 'No se pudo eliminar.'], 500);
}

gpi_responder(['error' => 'Acción inválida.'], 400);
