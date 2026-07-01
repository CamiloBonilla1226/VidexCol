<?php
/**
 * crear_grupo.php
 * Crea un nuevo grupo registrando un reporte de generación 0 con datos iniciales
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    // Iniciar sesión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Incluir funciones y config
    include_once('funciones.php');
    include_once('config.php');

    // Verificar autenticación
    if (!isset($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit();
    }

    // Obtener datos del POST
    $data = json_decode(file_get_contents('php://input'), true);
    error_log('DEBUG: crear_grupo.php - datos recibidos: ' . json_encode($data));

    if (!is_array($data)) {
        throw new Exception('Datos invalidos para crear el grupo');
    }

    // Validar datos obligatorios del grupo
    $nombreGrupoOriginal = preg_replace('/\s+/', ' ', trim((string)($data['nombre'] ?? '')));
    if ($nombreGrupoOriginal === '') {
        throw new Exception('El nombre del grupo es obligatorio');
    }

    $nombreGrupoLongitud = function_exists('mb_strlen') ? mb_strlen($nombreGrupoOriginal, 'UTF-8') : strlen($nombreGrupoOriginal);
    if ($nombreGrupoLongitud < 7) {
        throw new Exception('El nombre del grupo debe tener minimo 7 caracteres');
    }

    if (!preg_match('/[A-Za-z0-9\p{L}]/u', $nombreGrupoOriginal)) {
        throw new Exception('El nombre del grupo debe ser alfabetico o alfanumerico');
    }

    $idFacilitador = $_SESSION['id'];
    $nombre = $nombreGrupoOriginal;
    $descripcion = trim((string)($data['descripcion'] ?? ''));
    $ciudad = preg_replace('/\s+/', ' ', trim((string)($data['ciudad'] ?? '')));
    $barrio = preg_replace('/\s+/', ' ', trim((string)($data['barrio'] ?? '')));
    $direccion = preg_replace('/\s+/', ' ', trim((string)($data['direccion'] ?? '')));

    if ($ciudad === '') {
        throw new Exception('La ciudad es obligatoria');
    }

    if ($barrio === '') {
        throw new Exception('El barrio es obligatorio');
    }

    if ($direccion === '') {
        throw new Exception('La direccion es obligatoria');
    }

    $lideresEntrada = $data['lideres'] ?? array();
    if (!is_array($lideresEntrada)) {
        $lideresEntrada = array($data['lider'] ?? '');
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
        throw new Exception('Debe agregar al menos un lider');
    }

    $lider = implode(', ', $lideresNormalizados);

    $tieneGrupoMadre = ($data['tieneGrupoMadre'] ?? '') === 'si';
    $grupoMadreId = trim((string)($data['grupoMadreId'] ?? ''));
    $grupoMadreHash = trim((string)($data['grupoMadreHash'] ?? $grupoMadreId));

    if ($tieneGrupoMadre && $grupoMadreId === '' && $grupoMadreHash === '') {
        throw new Exception('Debe seleccionar un grupo madre');
    }

    // Datos del primer reporte
    $tipoActividad = $data['tipoActividad'] ?? 'reunion_cotidiana';
    $fechaActividad = trim((string)($data['fechaActividad'] ?? ''));
    if ($fechaActividad === '') {
        throw new Exception('La fecha del primer encuentro es obligatoria');
    }
    $asistencia_hom = intval($data['asistencia_hom'] ?? 0);
    $asistencia_muj = intval($data['asistencia_muj'] ?? 0);
    $asistencia_jov = intval($data['asistencia_jov'] ?? 0);
    $asistencia_nin = intval($data['asistencia_nin'] ?? 0);
    $discipulado = 0;
    $desiciones = 0;
    $preparandose = 0;
    
    // El primer reporte de un nuevo IPG debe iniciar el mapeo en nivel 2.
    $mapeo_oracion = 2;
    $mapeo_companerismo = 2;
    $mapeo_adoracion = 2;
    $mapeo_biblia = 2;
    $mapeo_evangelizar = 2;
    $mapeo_cena = 2;
    $mapeo_dar = 2;
    $mapeo_bautizar = 2;
    $mapeo_trabajadores = 2;

    // Conectar a BD
    $PSN1 = new DBbase_Sql;

    $generacionNumero = 0;
    $idGrupoMadre = 0;
    $grupoMadre_txt = '';

    // Si tiene grupo madre, obtener información y calcular generación
    if ($tieneGrupoMadre && ($grupoMadreId !== '' || $grupoMadreHash !== '')) {
        $grupoMadreEncontrado = false;

        if (ctype_digit($grupoMadreId) && strlen($grupoMadreId) <= 11 && (int)$grupoMadreId > 0) {
            $queryGrupoPorId = "
                SELECT id, nombreGrupo_txt, plantador, ciudad, barrio, generacionNumero, grupoMadre_txt, direccion
                FROM sat_reportes
                WHERE id = " . (int)$grupoMadreId . "
                  AND idUsuario = " . (int)$idFacilitador . "
                LIMIT 1
            ";

            error_log('DEBUG: Buscando grupo madre por id con query: ' . $queryGrupoPorId);
            $PSN1->query($queryGrupoPorId);

            if ($PSN1->next_record()) {
                $hashCoincide = true;

                if ($grupoMadreHash !== '' && !ctype_digit($grupoMadreHash)) {
                    $ubicacionGrupo = ($PSN1->f('ciudad') ?? '') . ($PSN1->f('barrio') ? ', ' . $PSN1->f('barrio') : '');
                    $direccionGrupo = $PSN1->f('direccion') ?? '';
                    $md5Test = md5($PSN1->f('nombreGrupo_txt') . '|' . $PSN1->f('plantador') . '|' . $ubicacionGrupo . '|' . ($PSN1->f('grupoMadre_txt') ?? '') . '|' . $direccionGrupo);
                    $hashCoincide = (substr($md5Test, 0, 8) === substr($grupoMadreHash, 0, 8));
                }

                if ($hashCoincide) {
                    $generacionNumero = (int)$PSN1->f('generacionNumero') + 1;
                    $grupoMadre_txt = $PSN1->f('nombreGrupo_txt');
                    $idGrupoMadre = (int)$PSN1->f('id');
                    $grupoMadreEncontrado = true;
                    error_log('DEBUG: Grupo madre encontrado por id: ' . $idGrupoMadre . ' con gen: ' . $generacionNumero);
                } else {
                    error_log('DEBUG: El id de grupo madre no coincide con el hash seleccionado. Se intentara por hash.');
                }
            }
        }

        if (!$grupoMadreEncontrado) {
            // Buscar todos los grupos del facilitador para encontrar el grupo madre por hash.
            $queryGrupos = "
                SELECT
                    MIN(id) AS idGrupoMadreSeleccionado,
                    nombreGrupo_txt,
                    plantador,
                    ciudad,
                    barrio,
                    generacionNumero,
                    grupoMadre_txt,
                    direccion
                FROM sat_reportes
                WHERE idUsuario = " . (int)$idFacilitador . "
                GROUP BY nombreGrupo_txt, plantador, ciudad, barrio, generacionNumero, grupoMadre_txt, direccion
                ORDER BY generacionNumero DESC, idGrupoMadreSeleccionado DESC
            ";

            error_log('DEBUG: Buscando grupo madre con query: ' . $queryGrupos);

            $PSN1->query($queryGrupos);

            while ($PSN1->next_record()) {
                $ubicacionGrupo = ($PSN1->f('ciudad') ?? '') . ($PSN1->f('barrio') ? ', ' . $PSN1->f('barrio') : '');
                $direccionGrupo = $PSN1->f('direccion') ?? '';
                $md5Test = md5($PSN1->f('nombreGrupo_txt') . '|' . $PSN1->f('plantador') . '|' . $ubicacionGrupo . '|' . ($PSN1->f('grupoMadre_txt') ?? '') . '|' . $direccionGrupo);

                error_log('DEBUG: Comparando hash: ' . substr($md5Test, 0, 8) . ' vs ' . substr($grupoMadreHash, 0, 8));

                // Comparar primeros 8 caracteres del hash
                if (substr($md5Test, 0, 8) === substr($grupoMadreHash, 0, 8)) {
                    $generacionNumero = (int)$PSN1->f('generacionNumero') + 1;
                    $grupoMadre_txt = $PSN1->f('nombreGrupo_txt');
                    $idGrupoMadre = (int)$PSN1->f('idGrupoMadreSeleccionado');
                    $grupoMadreEncontrado = true;
                    error_log('DEBUG: Grupo madre encontrado con id: ' . $idGrupoMadre . ' y gen: ' . $generacionNumero);
                    break;
                }
            }
        }

        if (!$grupoMadreEncontrado) {
            error_log('ERROR: Grupo madre no encontrado con id: ' . $grupoMadreId);
            throw new Exception('No se encontró el grupo madre seleccionado');
        }

        if ($generacionNumero > 5) {
            throw new Exception('No se puede crear un grupo de generación mayor a 5');
        }
    }

    // Sanitizar strings
    $nombre = addslashes($nombre);
    $descripcion = addslashes($descripcion);
    $ciudad = addslashes($ciudad);
    $barrio = addslashes($barrio);
    $direccion = addslashes($direccion);
    $lider = addslashes($lider);
    $grupoMadre_txt = addslashes($grupoMadre_txt);
    $fechaActividad = addslashes($fechaActividad);

    // Calcular asistencia total
    $asistencia_total = $asistencia_hom + $asistencia_muj + $asistencia_jov + $asistencia_nin;

    if ($asistencia_total < 1) {
        throw new Exception('La asistencia total debe ser mínimo 1');
    }

    // Crear reporte de generación 0 (creación del grupo)
    $hoy = date('Y-m-d');
    $ahora = date('Y-m-d H:i:s');

    $sqlInsert = "INSERT INTO sat_reportes (
        idUsuario,
        id_grupo,
        id_actividad,
        inactivo,
        idGrupoMadre,
        generacionNumero,
        plantador,
        fechaReporte,
        fechaInicio,
        sitioReunion,
        grupoMadre_txt,
        nombreGrupo_txt,
        capacitacion_txt,
        barrio,
        direccion,
        ciudad,
        asistencia_total,
        asistencia_hom,
        asistencia_muj,
        asistencia_jov,
        asistencia_nin,
        bautizados,
        discipulado,
        desiciones,
        preparandose,
        bautizadosPeriodo,
        iglesias_reconocidas,
        creacionFecha,
        creacionUsuario,
        modificacionFecha,
        modificacionUsuario,
        ext1,
        ext2,
        mapeo_anho,
        mapeo_cuarto,
        ext3,
        mapeo_fecha,
        mapeo_comprometido,
        mapeo_oracion,
        mapeo_companerismo,
        mapeo_adoracion,
        mapeo_biblia,
        mapeo_evangelizar,
        mapeo_cena,
        mapeo_dar,
        mapeo_bautizar,
        mapeo_trabajadores,
        comentario
    ) VALUES (
        " . (int)$idFacilitador . ",
        0,
        2,
        0,
        " . (int)$idGrupoMadre . ",
        " . (int)$generacionNumero . ",
        '$lider',
        '$hoy',
        '$fechaActividad',
        '$nombre',
        '$grupoMadre_txt',
        '$nombre',
        '$descripcion',
        '$barrio',
        '$direccion',
        '$ciudad',
        " . (int)$asistencia_total . ",
        " . (int)$asistencia_hom . ",
        " . (int)$asistencia_muj . ",
        " . (int)$asistencia_jov . ",
        " . (int)$asistencia_nin . ",
        0,
        " . (int)$discipulado . ",
        " . (int)$desiciones . ",
        " . (int)$preparandose . ",
        0,
        0,
        '$ahora',
        " . (int)$idFacilitador . ",
        '$hoy',
        " . (int)$idFacilitador . ",
        '', '',
        YEAR(NOW()),
        QUARTER(NOW()),
        '',
        NOW(),
        0,
        " . (int)$mapeo_oracion . ",
        " . (int)$mapeo_companerismo . ",
        " . (int)$mapeo_adoracion . ",
        " . (int)$mapeo_biblia . ",
        " . (int)$mapeo_evangelizar . ",
        " . (int)$mapeo_cena . ",
        " . (int)$mapeo_dar . ",
        " . (int)$mapeo_bautizar . ",
        " . (int)$mapeo_trabajadores . ",
        '$descripcion'
    )";

    error_log('DEBUG: INSERT query para nuevo grupo: ' . $sqlInsert);

    // Ejecutar la query usando el método query de DBbase_Sql
    $result = $PSN1->query($sqlInsert);

    if (!$result) {
        error_log('ERROR BD: ' . $PSN1->Error);
        throw new Exception('Error al crear el grupo: ' . $PSN1->Error);
    }

    $nuevoReporteId = $PSN1->ultimoId();

    error_log('DEBUG: Grupo creado exitosamente con ID: ' . $nuevoReporteId);

    echo json_encode([
        'success' => true,
        'message' => 'Grupo creado exitosamente',
        'nuevoGrupoId' => $nuevoReporteId,
        'generacion' => $generacionNumero
    ]);

} catch (Exception $e) {
    error_log('ERROR en crear_grupo.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
