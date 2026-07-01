<?php
/**
 * obtener_imagenes_reportes.php
 * Devuelve imagenes e informacion basica de reportes.
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
        throw new Exception('Datos invalidos para consultar imagenes');
    }

    $ids = array();
    foreach (($data['reporteIds'] ?? array()) as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }

    if (count($ids) === 0) {
        echo json_encode(array('success' => true, 'imagenes' => array(), 'reportes' => array()));
        exit();
    }

    $idFacilitador = (int)$_SESSION['id'];
    $idsSql = implode(',', array_values($ids));
    $PSN1 = new DBbase_Sql;

    $reportes = array();
    $sqlReportes = "
        SELECT
            id,
            id_grupo,
            id_actividad,
            idGrupoMadre,
            generacionNumero,
            fechaInicio,
            asistencia_total,
            mapeo_oracion,
            mapeo_companerismo,
            mapeo_adoracion,
            mapeo_biblia,
            mapeo_evangelizar,
            mapeo_cena,
            mapeo_dar,
            mapeo_bautizar,
            mapeo_trabajadores
        FROM sat_reportes
        WHERE idUsuario = " . (int)$idFacilitador . "
          AND id IN (" . $idsSql . ")
    ";
    $PSN1->query($sqlReportes);

    while ($PSN1->next_record()) {
        $reportes[] = array(
            'id' => (int)$PSN1->f('id'),
            'id_grupo' => (int)$PSN1->f('id_grupo'),
            'id_actividad' => (int)$PSN1->f('id_actividad'),
            'idGrupoMadre' => (int)$PSN1->f('idGrupoMadre'),
            'generacionNumero' => (int)$PSN1->f('generacionNumero'),
            'fechaInicio' => $PSN1->f('fechaInicio'),
            'asistencia_total' => (int)$PSN1->f('asistencia_total'),
            'mapeo_oracion' => (int)$PSN1->f('mapeo_oracion'),
            'mapeo_companerismo' => (int)$PSN1->f('mapeo_companerismo'),
            'mapeo_adoracion' => (int)$PSN1->f('mapeo_adoracion'),
            'mapeo_biblia' => (int)$PSN1->f('mapeo_biblia'),
            'mapeo_evangelizar' => (int)$PSN1->f('mapeo_evangelizar'),
            'mapeo_cena' => (int)$PSN1->f('mapeo_cena'),
            'mapeo_dar' => (int)$PSN1->f('mapeo_dar'),
            'mapeo_bautizar' => (int)$PSN1->f('mapeo_bautizar'),
            'mapeo_trabajadores' => (int)$PSN1->f('mapeo_trabajadores')
        );
    }

    $imagenes = array();
    $sqlImagenes = "
        SELECT adj_id, adj_nom, adj_url, adj_rep_fk
        FROM tbl_adjuntos
        WHERE adj_rep_fk IN (" . $idsSql . ")
        ORDER BY adj_id ASC
    ";
    $PSN1->query($sqlImagenes);

    while ($PSN1->next_record()) {
        $ruta = $PSN1->f('adj_url');
        $imagenes[] = array(
            'id' => (int)$PSN1->f('adj_id'),
            'nombre' => $PSN1->f('adj_nom'),
            'ruta' => $ruta,
            'rutaThumbnail' => $ruta,
            'reporte_id' => (int)$PSN1->f('adj_rep_fk')
        );
    }

    echo json_encode(array(
        'success' => true,
        'imagenes' => $imagenes,
        'reportes' => $reportes
    ));
} catch (Exception $e) {
    error_log('ERROR en obtener_imagenes_reportes.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ));
}
?>
