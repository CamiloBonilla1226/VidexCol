<?php
/**
 * actualizar_grupo_consolidado.php
 * Actualiza el grupo base y sus reportes hijos.
 * El grupo madre se identifica y valida SIEMPRE por id (nunca por nombre).
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

$PSN1 = null;
$transaccionAbierta = false;

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

    // --- Grupo madre: identificado y validado SIEMPRE por id ---
    $tieneGrupoMadre = ($data['tieneGrupoMadre'] ?? '') === 'si';
    $grupoMadreIdEntrada = trim((string)($data['grupoMadreId'] ?? ''));

    if ($tieneGrupoMadre && (!ctype_digit($grupoMadreIdEntrada) || (int)$grupoMadreIdEntrada <= 0)) {
        throw new Exception('Debe seleccionar un grupo madre valido');
    }

    $grupoMadreIdSolicitado = $tieneGrupoMadre ? (int)$grupoMadreIdEntrada : 0;

    if ($tieneGrupoMadre && $grupoMadreIdSolicitado === $idGrupo) {
        throw new Exception('Un grupo no puede ser su propio grupo madre');
    }

    $nombreGrupoSql = addslashes($nombreGrupo);
    $plantadorSql = addslashes($plantador);
    $ciudadSql = addslashes($ciudad);
    $barrioSql = addslashes($barrio);
    $direccionSql = addslashes($direccion);

    $PSN1 = new DBbase_Sql;

    // Cargar en memoria TODOS los grupos (id_grupo = 0) de este facilitador,
    // necesarios para: validar el grupo madre elegido, detectar ciclos, y
    // recalcular la generacion en cascada sobre toda la descendencia.
    $grupos = array();
    $PSN1->query("
        SELECT id, idGrupoMadre, generacionNumero, nombreGrupo_txt, grupoMadre_txt
        FROM sat_reportes
        WHERE idUsuario = " . (int)$idFacilitador . "
          AND (id_grupo = 0 OR id_grupo IS NULL)
    ");
    while ($PSN1->next_record()) {
        $gid = (int)$PSN1->f('id');
        $grupos[$gid] = array(
            'id' => $gid,
            'idGrupoMadre' => (int)$PSN1->f('idGrupoMadre'),
            'generacionNumero' => (int)$PSN1->f('generacionNumero'),
            'nombreGrupo_txt' => (string)$PSN1->f('nombreGrupo_txt'),
            'grupoMadre_txt' => (string)$PSN1->f('grupoMadre_txt'),
        );
    }

    if (!isset($grupos[$idGrupo])) {
        throw new Exception('No se encontro el grupo indicado');
    }

    // Mapa madre -> hijos, para recorrer el arbol.
    $hijosPorMadre = array();
    foreach ($grupos as $gid => $g) {
        if ($g['idGrupoMadre'] > 0) {
            $hijosPorMadre[$g['idGrupoMadre']][] = $gid;
        }
    }

    // Descendientes de idGrupo (para prohibir ciclos: no se puede elegir
    // como grupo madre a un hijo, nieto, etc. del propio grupo).
    $descendientes = array();
    $cola = isset($hijosPorMadre[$idGrupo]) ? $hijosPorMadre[$idGrupo] : array();
    while (count($cola) > 0) {
        $actualId = array_shift($cola);
        if (isset($descendientes[$actualId])) {
            continue;
        }
        $descendientes[$actualId] = true;
        if (isset($hijosPorMadre[$actualId])) {
            foreach ($hijosPorMadre[$actualId] as $hijoId) {
                $cola[] = $hijoId;
            }
        }
    }

    $nuevaGeneracion = 0;
    $nuevoGrupoMadreTxt = '';
    $nuevoIdGrupoMadre = 0;

    if ($tieneGrupoMadre) {
        if (!isset($grupos[$grupoMadreIdSolicitado])) {
            throw new Exception('No se encontro el grupo madre seleccionado');
        }

        if (isset($descendientes[$grupoMadreIdSolicitado])) {
            throw new Exception('No se puede seleccionar un grupo descendiente como grupo madre');
        }

        $madre = $grupos[$grupoMadreIdSolicitado];
        $nuevaGeneracion = $madre['generacionNumero'] + 1;

        if ($nuevaGeneracion > 5) {
            throw new Exception('No se puede asignar ese grupo madre: la generacion resultante seria mayor a 5');
        }

        $nuevoGrupoMadreTxt = $madre['nombreGrupo_txt'];
        $nuevoIdGrupoMadre = $madre['id'];
    }

    // Recalcular (en memoria) la generacion y el texto de grupo madre para
    // idGrupo y para TODA su descendencia, y validar que ningun descendiente
    // supere la generacion maxima permitida antes de escribir nada.
    $nuevosValores = array();
    $nuevosValores[$idGrupo] = array(
        'generacionNumero' => $nuevaGeneracion,
        'grupoMadre_txt' => $nuevoGrupoMadreTxt,
        'idGrupoMadre' => $nuevoIdGrupoMadre,
        'nombreGrupo_txt' => $nombreGrupo,
    );

    $colaDescendencia = isset($hijosPorMadre[$idGrupo]) ? $hijosPorMadre[$idGrupo] : array();
    while (count($colaDescendencia) > 0) {
        $actualId = array_shift($colaDescendencia);
        if (isset($nuevosValores[$actualId])) {
            continue;
        }

        $padreId = $grupos[$actualId]['idGrupoMadre'];
        $padreNuevo = $nuevosValores[$padreId];
        $generacionCalculada = $padreNuevo['generacionNumero'] + 1;

        if ($generacionCalculada > 5) {
            throw new Exception('No se puede aplicar el cambio: un grupo descendiente superaria la generacion maxima permitida (5)');
        }

        $nuevosValores[$actualId] = array(
            'generacionNumero' => $generacionCalculada,
            'grupoMadre_txt' => $padreNuevo['nombreGrupo_txt'],
            'idGrupoMadre' => $padreId,
            'nombreGrupo_txt' => $grupos[$actualId]['nombreGrupo_txt'],
        );

        if (isset($hijosPorMadre[$actualId])) {
            foreach ($hijosPorMadre[$actualId] as $hijoId) {
                $colaDescendencia[] = $hijoId;
            }
        }
    }

    // --- A partir de aqui se escribe en la base de datos ---
    $PSN1->query('START TRANSACTION');
    $transaccionAbierta = true;

    // Actualizar el grupo editado (datos propios + vinculo con su grupo madre).
    $nuevoGrupoMadreTxtSql = addslashes($nuevosValores[$idGrupo]['grupoMadre_txt']);
    $sqlUpdateGrupo = "
        UPDATE sat_reportes
        SET
            nombreGrupo_txt = '" . $nombreGrupoSql . "',
            sitioReunion = '" . $nombreGrupoSql . "',
            plantador = '" . $plantadorSql . "',
            ciudad = '" . $ciudadSql . "',
            barrio = '" . $barrioSql . "',
            direccion = '" . $direccionSql . "',
            idGrupoMadre = " . (int)$nuevosValores[$idGrupo]['idGrupoMadre'] . ",
            grupoMadre_txt = '" . $nuevoGrupoMadreTxtSql . "',
            generacionNumero = " . (int)$nuevosValores[$idGrupo]['generacionNumero'] . "
        WHERE id = " . (int)$idGrupo . "
          AND idUsuario = " . (int)$idFacilitador . "
    ";
    $result = $PSN1->query($sqlUpdateGrupo);
    if (!$result) {
        throw new Exception('No se pudo actualizar el grupo: ' . $PSN1->Error);
    }

    // Actualizar cada grupo descendiente cuya generacion/texto de madre cambio.
    foreach ($nuevosValores as $gid => $valores) {
        if ($gid === $idGrupo) {
            continue;
        }

        $grupoMadreTxtSql = addslashes($valores['grupoMadre_txt']);
        $sqlUpdateDescendiente = "
            UPDATE sat_reportes
            SET
                generacionNumero = " . (int)$valores['generacionNumero'] . ",
                grupoMadre_txt = '" . $grupoMadreTxtSql . "'
            WHERE id = " . (int)$gid . "
              AND idUsuario = " . (int)$idFacilitador . "
        ";
        $result = $PSN1->query($sqlUpdateDescendiente);
        if (!$result) {
            throw new Exception('No se pudo actualizar un grupo descendiente: ' . $PSN1->Error);
        }
    }

    // Propagar los datos vigentes de cada grupo (idGrupo + descendientes) a
    // todos sus reportes hijos, para que sigan heredando correctamente.
    $totalActualizados = 0;
    foreach ($nuevosValores as $gid => $valores) {
        $nombreGrupoHijoSql = addslashes($valores['nombreGrupo_txt']);
        $grupoMadreTxtHijoSql = addslashes($valores['grupoMadre_txt']);

        $sqlReportes = "
            UPDATE sat_reportes
            SET
                idGrupoMadre = " . (int)$valores['idGrupoMadre'] . ",
                generacionNumero = " . (int)$valores['generacionNumero'] . ",
                grupoMadre_txt = '" . $grupoMadreTxtHijoSql . "',
                nombreGrupo_txt = '" . $nombreGrupoHijoSql . "'
            WHERE id_grupo = " . (int)$gid . "
              AND idUsuario = " . (int)$idFacilitador . "
        ";
        $result = $PSN1->query($sqlReportes);
        if (!$result) {
            throw new Exception('No se pudo actualizar los reportes del grupo: ' . $PSN1->Error);
        }
    }

    $sqlConteo = "
        SELECT COUNT(id) AS total_actualizados
        FROM sat_reportes
        WHERE idUsuario = " . (int)$idFacilitador . "
          AND (id = " . (int)$idGrupo . " OR id_grupo = " . (int)$idGrupo . ")
    ";
    $PSN1->query($sqlConteo);
    if ($PSN1->next_record()) {
        $totalActualizados = (int)$PSN1->f('total_actualizados');
    }

    $PSN1->query('COMMIT');
    $transaccionAbierta = false;

    echo json_encode(array(
        'success' => true,
        'reportes_actualizados' => $totalActualizados,
        'generacion' => $nuevosValores[$idGrupo]['generacionNumero'],
        'grupos_descendientes_actualizados' => count($nuevosValores) - 1
    ));
} catch (Exception $e) {
    if ($transaccionAbierta && $PSN1) {
        $PSN1->query('ROLLBACK');
    }
    error_log('ERROR en actualizar_grupo_consolidado.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ));
}
?>
