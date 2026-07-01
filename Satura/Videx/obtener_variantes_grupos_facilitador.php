<?php
/**
 * obtener_variantes_grupos_facilitador.php
 * Lista los grupos base del facilitador con conteo solo de reportes hijos.
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
    $idFacilitador = (int)($_SESSION['id'] ?? 0);
    $idFacilitadorSolicitud = (int)($data['idFacilitador'] ?? $idFacilitador);

    if ($idFacilitador <= 0 || $idFacilitadorSolicitud !== $idFacilitador) {
        throw new Exception('Solicitud invalida');
    }

    $PSN1 = new DBbase_Sql;
    $sql = "
        SELECT
            base.id AS id_grupo_base,
            base.id AS id_unico,
            base.nombreGrupo_txt AS nombre_exacto,
            base.plantador AS lider,
            base.ciudad,
            base.barrio,
            base.direccion,
            base.grupoMadre_txt AS grupo_madre,
            base.generacionNumero AS generacion,
            COUNT(DISTINCT hijo.id) AS reportes,
            GROUP_CONCAT(DISTINCT hijo.id ORDER BY hijo.fechaInicio DESC, hijo.id DESC SEPARATOR ',') AS reportes_ids_csv
        FROM sat_reportes base
        LEFT JOIN sat_reportes hijo
            ON hijo.id_grupo = base.id
           AND hijo.idUsuario = base.idUsuario
           AND hijo.id_grupo <> 0
        WHERE base.idUsuario = " . (int)$idFacilitador . "
          AND (base.id_grupo IS NULL OR base.id_grupo = 0)
        GROUP BY
            base.id,
            base.nombreGrupo_txt,
            base.plantador,
            base.ciudad,
            base.barrio,
            base.direccion,
            base.grupoMadre_txt,
            base.generacionNumero
        ORDER BY base.nombreGrupo_txt ASC, base.id DESC
    ";

    $PSN1->query($sql);

    $grupos = array();
    while ($PSN1->next_record()) {
        $ciudad = trim((string)$PSN1->f('ciudad'));
        $barrio = trim((string)$PSN1->f('barrio'));
        $ubicacion = $ciudad;
        if ($barrio !== '') {
            $ubicacion = $ubicacion !== '' ? ($ciudad . ', ' . $barrio) : $barrio;
        }
        $reportesIdsCsv = trim((string)$PSN1->f('reportes_ids_csv'));
        $reportesIds = array();
        if ($reportesIdsCsv !== '') {
            foreach (explode(',', $reportesIdsCsv) as $reporteId) {
                $reporteId = (int)$reporteId;
                if ($reporteId > 0) {
                    $reportesIds[] = $reporteId;
                }
            }
        }

        $grupos[] = array(
            'id_unico' => (int)$PSN1->f('id_unico'),
            'id_grupo_base' => (int)$PSN1->f('id_grupo_base'),
            'idGrupoBase' => (int)$PSN1->f('id_grupo_base'),
            'idGrupoSeleccionado' => (int)$PSN1->f('id_grupo_base'),
            'nombre_exacto' => $PSN1->f('nombre_exacto'),
            'lider' => $PSN1->f('lider'),
            'ciudad' => $ciudad,
            'barrio' => $barrio,
            'direccion' => $PSN1->f('direccion'),
            'grupo_madre' => $PSN1->f('grupo_madre'),
            'generacion' => (int)$PSN1->f('generacion'),
            'ubicacion' => $ubicacion,
            'reportes' => (int)$PSN1->f('reportes'),
            'reportes_ids' => $reportesIds
        );
    }

    echo json_encode(array(
        'success' => true,
        'grupos' => $grupos
    ));
} catch (Exception $e) {
    error_log('ERROR en obtener_variantes_grupos_facilitador.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ));
}
?>
