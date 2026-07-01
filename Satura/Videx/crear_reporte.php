<?php
/**
 * crear_reporte.php
 * Crea un reporte ligado a un grupo existente en sat_reportes.
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
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    error_log('DEBUG: crear_reporte.php - datos recibidos: ' . json_encode($data));

    if (!is_array($data)) {
        throw new Exception('Datos invalidos para crear el reporte');
    }

    $idFacilitador = (int)$_SESSION['id'];
    $idGrupo = (int)($data['idGrupo'] ?? 0);

    if ($idGrupo <= 0) {
        throw new Exception('Debe seleccionar un grupo valido');
    }

    $tipoActividad = (string)($data['tipoActividad'] ?? '');
    $actividades = array(
        'evangelismo' => 77,
        'bautizo' => 99,
        'gran_celebracion' => 8,
        'reunion_cotidiana' => 1,
        'siembra_abundante' => 10,
        'caminata_oracion' => 11,
        'identificar_hijo_paz' => 12,
        'oracion_exp_ferviente' => 13,
        'taller' => 14,
        'otra_actividad' => 5,
        'capacitacion' => 100
    );

    $actividadesTipoEvangelismo = array(
        'evangelismo',
        'siembra_abundante',
        'caminata_oracion',
        'identificar_hijo_paz',
        'oracion_exp_ferviente',
        'taller',
        'otra_actividad',
        'capacitacion'
    );

    if (!isset($actividades[$tipoActividad])) {
        throw new Exception('Tipo de actividad no valido');
    }

    $idActividad = (int)$actividades[$tipoActividad];
    $fechaActividad = trim((string)($data['fechaActividad'] ?? ''));

    if ($fechaActividad === '') {
        throw new Exception('La fecha de la actividad es obligatoria');
    }

    $asistencia_hom = intval($data['asistencia_hom'] ?? 0);
    $asistencia_muj = intval($data['asistencia_muj'] ?? 0);
    $asistencia_jov = intval($data['asistencia_jov'] ?? 0);
    $asistencia_nin = intval($data['asistencia_nin'] ?? 0);
    $asistencia_total = $asistencia_hom + $asistencia_muj + $asistencia_jov + $asistencia_nin;

    if ($asistencia_total < 1) {
        throw new Exception('La asistencia total debe ser minimo 1');
    }

    $bautizados = max(0, intval($data['bautizados'] ?? 0));
    $discipulado = max(0, intval($data['discipulado'] ?? 0));
    $desiciones = max(0, intval($data['desiciones'] ?? 0));
    $preparandose = max(0, intval($data['preparandose'] ?? 0));
    $comentario = addslashes((string)($data['comentario'] ?? ''));

    if (in_array($tipoActividad, $actividadesTipoEvangelismo, true)) {
        $bautizados = 0;
        $discipulado = 0;
        $preparandose = 0;
    } elseif ($tipoActividad === 'gran_celebracion') {
        $bautizados = 0;
        $discipulado = 0;
        $desiciones = 0;
        $preparandose = 0;
    } elseif ($tipoActividad === 'bautizo') {
        $discipulado = 0;
        $desiciones = 0;
        $preparandose = 0;
    } else {
        $bautizados = 0;
    }

    if ($bautizados > $asistencia_total) {
        throw new Exception('Cantidad de Bautizados no puede ser mayor a la asistencia total');
    }

    if ($discipulado > $asistencia_total) {
        throw new Exception('Discipulado no puede ser mayor a la asistencia total');
    }

    if ($desiciones > $asistencia_total) {
        throw new Exception('Decisiones de Fé no puede ser mayor a la asistencia total');
    }

    if ($preparandose > $asistencia_total) {
        throw new Exception('Preparandose no puede ser mayor a la asistencia total');
    }

    $mapeo_oracion = intval($data['mapeo_oracion'] ?? 0);
    $mapeo_companerismo = intval($data['mapeo_companerismo'] ?? 0);
    $mapeo_adoracion = intval($data['mapeo_adoracion'] ?? 0);
    $mapeo_biblia = intval($data['mapeo_biblia'] ?? 0);
    $mapeo_evangelizar = intval($data['mapeo_evangelizar'] ?? 0);
    $mapeo_cena = intval($data['mapeo_cena'] ?? 0);
    $mapeo_dar = intval($data['mapeo_dar'] ?? 0);
    $mapeo_bautizar = intval($data['mapeo_bautizar'] ?? 0);
    $mapeo_trabajadores = intval($data['mapeo_trabajadores'] ?? 0);
    $mapeo_comprometido = intval($data['mapeo_comprometido'] ?? 0);

    if ($idActividad !== 1) {
        $mapeo_comprometido = 0;
        $mapeo_oracion = 0;
        $mapeo_companerismo = 0;
        $mapeo_adoracion = 0;
        $mapeo_biblia = 0;
        $mapeo_evangelizar = 0;
        $mapeo_cena = 0;
        $mapeo_dar = 0;
        $mapeo_bautizar = 0;
        $mapeo_trabajadores = 0;
    } else {
        $camposMapeoCoach = array(
            'Oracion' => $mapeo_oracion,
            'Companerismo' => $mapeo_companerismo,
            'Adoracion' => $mapeo_adoracion,
            'Aplicar la biblia' => $mapeo_biblia,
            'Evangelizar' => $mapeo_evangelizar,
            'Cena del Senor' => $mapeo_cena,
            'Dar' => $mapeo_dar,
            'Bautizar' => $mapeo_bautizar,
            'Entrenar nuevos lideres' => $mapeo_trabajadores
        );
        $mapeosFaltantes = array();
        foreach ($camposMapeoCoach as $etiqueta => $valor) {
            if ($valor < 1 || $valor > 4) {
                $mapeosFaltantes[] = $etiqueta;
            }
        }

        if (count($mapeosFaltantes) > 0) {
            throw new Exception('Debe completar todos los campos de mapeo del formulario de coach. Faltan: ' . implode(', ', $mapeosFaltantes) . '.');
        }

        if ($mapeo_comprometido !== 3 && $mapeo_comprometido !== 4) {
            throw new Exception('Debe seleccionar si este grupo esta comprometido como iglesia.');
        }
    }

    $PSN1 = new DBbase_Sql;

    $sqlGrupo = "
        SELECT
            id,
            idGrupoMadre,
            generacionNumero,
            plantador,
            sitioReunion,
            grupoMadre_txt,
            nombreGrupo_txt,
            capacitacion_txt,
            barrio,
            direccion,
            ciudad
        FROM sat_reportes
        WHERE id = " . (int)$idGrupo . "
          AND idUsuario = " . (int)$idFacilitador . "
          AND (id_grupo IS NULL OR id_grupo = 0)
        LIMIT 1
    ";

    error_log('DEBUG: Buscando grupo para reporte: ' . $sqlGrupo);
    $PSN1->query($sqlGrupo);

    if (!$PSN1->next_record()) {
        throw new Exception('No se encontro el grupo seleccionado');
    }

    $idGrupoMadre = (int)$PSN1->f('idGrupoMadre');
    $generacionNumero = (int)$PSN1->f('generacionNumero');
    $plantador = addslashes((string)$PSN1->f('plantador'));
    $sitioReunion = addslashes((string)$PSN1->f('sitioReunion'));
    $grupoMadre_txt = addslashes((string)$PSN1->f('grupoMadre_txt'));
    $nombreGrupo_txt = addslashes((string)$PSN1->f('nombreGrupo_txt'));
    $capacitacion_txt = addslashes((string)$PSN1->f('capacitacion_txt'));
    $barrio = addslashes((string)$PSN1->f('barrio'));
    $direccion = addslashes((string)$PSN1->f('direccion'));
    $ciudad = addslashes((string)$PSN1->f('ciudad'));
    $fechaActividad = addslashes($fechaActividad);

    $hoy = date('Y-m-d');
    $ahora = date('Y-m-d H:i:s');
    $bautizadosPeriodo = ($idActividad === 99) ? $bautizados : 0;

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
        " . (int)$idGrupo . ",
        " . (int)$idActividad . ",
        0,
        " . (int)$idGrupoMadre . ",
        " . (int)$generacionNumero . ",
        '$plantador',
        '$hoy',
        '$fechaActividad',
        '$sitioReunion',
        '$grupoMadre_txt',
        '$nombreGrupo_txt',
        '$capacitacion_txt',
        '$barrio',
        '$direccion',
        '$ciudad',
        " . (int)$asistencia_total . ",
        " . (int)$asistencia_hom . ",
        " . (int)$asistencia_muj . ",
        " . (int)$asistencia_jov . ",
        " . (int)$asistencia_nin . ",
        " . (int)$bautizados . ",
        " . (int)$discipulado . ",
        " . (int)$desiciones . ",
        " . (int)$preparandose . ",
        " . (int)$bautizadosPeriodo . ",
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
        " . (int)$mapeo_comprometido . ",
        " . (int)$mapeo_oracion . ",
        " . (int)$mapeo_companerismo . ",
        " . (int)$mapeo_adoracion . ",
        " . (int)$mapeo_biblia . ",
        " . (int)$mapeo_evangelizar . ",
        " . (int)$mapeo_cena . ",
        " . (int)$mapeo_dar . ",
        " . (int)$mapeo_bautizar . ",
        " . (int)$mapeo_trabajadores . ",
        '$comentario'
    )";

    error_log('DEBUG: INSERT query para nuevo reporte: ' . $sqlInsert);
    $result = $PSN1->query($sqlInsert);

    if (!$result) {
        error_log('ERROR BD: ' . $PSN1->Error);
        throw new Exception('Error al crear el reporte: ' . $PSN1->Error);
    }

    $nuevoReporteId = $PSN1->ultimoId();

    echo json_encode(array(
        'success' => true,
        'message' => 'Reporte creado exitosamente',
        'nuevoReporteId' => $nuevoReporteId,
        'idGrupo' => $idGrupo,
        'idActividad' => $idActividad
    ));
} catch (Exception $e) {
    error_log('ERROR en crear_reporte.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ));
}
?>
