<?php
/**
 * actualizar_grupo_consolidado.php
 * Actualiza el grupo base y sus reportes hijos.
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    include_once('funciones.php');
    include_once('config.php');

    if (!isset($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(array('success' => false, 'message' => 'No autorizado'));
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new Exception('Datos invalidos para actualizar el grupo');
    }

    $idFacilitador = (int)$_SESSION['id'];
    $idGrupo = (int)($data['idGrupo'] ?? 0);
    if ($idGrupo <= 0) {
        throw new Exception('No se pudo identificar el grupo base');
    }

    $nombreGrupo = preg_replace('/\s+/', ' ', trim((string)($data['nombre_exacto'] ?? '')));
    if ($nombreGrupo === '') {
        throw new Exception('El nombre del grupo es obligatorio');
    }

    $nombreGrupoLongitud = function_exists('mb_strlen') ? mb_strlen($nombreGrupo, 'UTF-8') : strlen($nombreGrupo);
    if ($nombreGrupoLongitud < 7) {
        throw new Exception('El nombre del grupo debe tener minimo 7 caracteres');
    }

    if (!preg_match('/[A-Za-z0-9\p{L}]/u', $nombreGrupo)) {
        throw new Exception('El nombre del grupo debe ser alfabetico o alfanumerico');
    }

    $lideresEntrada = $data['lider'] ?? array();
    if (!is_array($lideresEntrada)) {
        $lideresEntrada = array($lideresEntrada);
    }

    $lideresNormalizados = array();
    foreach ($lideresEntrada as $liderItem) {
        $lider = preg_replace('/\s+/', ' ', trim((string)$liderItem));
        if ($lider === '') {
            continue;
        }

        $liderLongitud = function_exists('mb_strlen') ? mb_strlen($lider, 'UTF-8') : strlen($lider);
        if ($liderLongitud < 10) {
            throw new Exception('El nombre del lider debe tener minimo 10 caracteres');
        }

        if (!preg_match('/^[\p{L} ]+$/u', $lider)) {
            throw new Exception('El nombre del lider solo debe contener letras y espacios');
        }

        if (!in_array($lider, $lideresNormalizados, true)) {
            $lideresNormalizados[] = $lider;
        }
    }

    if (count($lideresNormalizados) === 0) {
        throw new Exception('Debe agregar al menos un lider capacitador');
    }

    $plantador = implode(', ', $lideresNormalizados);
    $ciudad = preg_replace('/\s+/', ' ', trim((string)($data['ciudad'] ?? '')));
    $barrio = preg_replace('/\s+/', ' ', trim((string)($data['barrio'] ?? '')));
    $direccion = preg_replace('/\s+/', ' ', trim((string)($data['direccion'] ?? '')));

    $nombreGrupoSql = addslashes($nombreGrupo);
    $plantadorSql = addslashes($plantador);
    $ciudadSql = addslashes($ciudad);
    $barrioSql = addslashes($barrio);
    $direccionSql = addslashes($direccion);

    $PSN1 = new DBbase_Sql;
    $sqlValidacion = "
        SELECT id
        FROM sat_reportes
        WHERE id = " . (int)$idGrupo . "
          AND idUsuario = " . (int)$idFacilitador . "
          AND (id_grupo IS NULL OR id_grupo = 0)
        LIMIT 1
    ";
    $PSN1->query($sqlValidacion);

    if (!$PSN1->next_record()) {
        throw new Exception('No se encontro el grupo indicado');
    }

    $sqlUpdate = "
        UPDATE sat_reportes
        SET
            nombreGrupo_txt = '" . $nombreGrupoSql . "',
            sitioReunion = '" . $nombreGrupoSql . "',
            plantador = '" . $plantadorSql . "',
            ciudad = '" . $ciudadSql . "',
            barrio = '" . $barrioSql . "',
            direccion = '" . $direccionSql . "'
        WHERE idUsuario = " . (int)$idFacilitador . "
          AND (id = " . (int)$idGrupo . " OR id_grupo = " . (int)$idGrupo . ")
    ";

    $result = $PSN1->query($sqlUpdate);
    if (!$result) {
        throw new Exception('No se pudo actualizar el grupo: ' . $PSN1->Error);
    }

    $sqlConteo = "
        SELECT COUNT(id) AS total_actualizados
        FROM sat_reportes
        WHERE idUsuario = " . (int)$idFacilitador . "
          AND (id = " . (int)$idGrupo . " OR id_grupo = " . (int)$idGrupo . ")
    ";
    $PSN1->query($sqlConteo);
    $totalActualizados = 0;
    if ($PSN1->next_record()) {
        $totalActualizados = (int)$PSN1->f('total_actualizados');
    }

    echo json_encode(array(
        'success' => true,
        'reportes_actualizados' => $totalActualizados
    ));
} catch (Exception $e) {
    error_log('ERROR en actualizar_grupo_consolidado.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ));
}
?>
