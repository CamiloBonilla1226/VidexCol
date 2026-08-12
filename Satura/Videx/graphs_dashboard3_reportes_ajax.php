<?php
/**
 * graphs_dashboard3_reportes_ajax.php
 * Endpoint AJAX: lista los reportes (sat_reportes con id_grupo > 0)
 * que pertenecen a un grupo puntual del árbol de graphs_dashboard3_arbol.php.
 *
 * A diferencia de obtener_reportes_grupo.php (que solo devuelve reportes
 * cuyo idUsuario coincide con el usuario logueado), aquí el acceso se
 * controla igual que el resto del dashboard de genealogía: por el menú
 * de permisos (idMenu 23), no por dueño del reporte — así un
 * administrador puede ver los reportes de cualquier facilitador.
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

    $PSN1 = new DBbase_Sql;

    $sqlAuth = "SELECT idMenu
                FROM usuarios_menu_graphs
                WHERE idMenu = 23
                  AND idUsuario = '".$_SESSION["id"]."'";
    $PSN1->query($sqlAuth);
    if ($PSN1->num_rows() == 0) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'message' => 'No autorizado'));
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new Exception('Datos invalidos');
    }

    $idGrupo = (int)($data['idGrupo'] ?? 0);
    if ($idGrupo <= 0) {
        throw new Exception('Debe indicar un grupo valido');
    }

    $sql = "SELECT
                id,
                fechaReporte,
                id_actividad,
                plantador,
                asistencia_total,
                asistencia_hom,
                asistencia_muj,
                asistencia_jov,
                asistencia_nin,
                bautizados,
                discipulado,
                desiciones,
                preparandose,
                comentario
            FROM sat_reportes
            WHERE id_grupo = ".$idGrupo."
            ORDER BY fechaReporte DESC, id DESC";
    $PSN1->query($sql);

    $reportes = array();
    while ($PSN1->next_record()) {
        $fechaFmt = '';
        $fecha = $PSN1->f('fechaReporte');
        if (!empty($fecha) && $fecha != '0000-00-00') {
            $ts = strtotime($fecha);
            if ($ts) $fechaFmt = date('d/m/Y', $ts);
        }
        $reportes[] = array(
            'id'               => (int)$PSN1->f('id'),
            'fecha'            => $fechaFmt,
            'id_actividad'     => (int)$PSN1->f('id_actividad'),
            'plantador'        => trim((string)$PSN1->f('plantador')),
            'asistencia_total' => (int)$PSN1->f('asistencia_total'),
            'asistencia_hom'   => (int)$PSN1->f('asistencia_hom'),
            'asistencia_muj'   => (int)$PSN1->f('asistencia_muj'),
            'asistencia_jov'   => (int)$PSN1->f('asistencia_jov'),
            'asistencia_nin'   => (int)$PSN1->f('asistencia_nin'),
            'bautizados'       => (int)$PSN1->f('bautizados'),
            'discipulado'      => (int)$PSN1->f('discipulado'),
            'desiciones'       => (int)$PSN1->f('desiciones'),
            'preparandose'     => (int)$PSN1->f('preparandose'),
            'comentario'       => trim((string)$PSN1->f('comentario')),
        );
    }

    echo json_encode(array(
        'success' => true,
        'idGrupo' => $idGrupo,
        'reportes' => $reportes,
    ));
} catch (Exception $e) {
    error_log('ERROR en graphs_dashboard3_reportes_ajax.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
    ));
}
