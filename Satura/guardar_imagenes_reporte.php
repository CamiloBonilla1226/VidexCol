<?php
/**
 * guardar_imagenes_reporte.php
 * Guarda evidencias fotograficas de un reporte en tbl_adjuntos.
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

    $idFacilitador = (int)$_SESSION['id'];
    $reporteId = (int)($_POST['reporteId'] ?? 0);

    if ($reporteId <= 0) {
        throw new Exception('Reporte invalido');
    }

    if (!isset($_FILES['imagenes'])) {
        throw new Exception('No se recibieron imagenes');
    }

    $PSN1 = new DBbase_Sql;
    $sqlReporte = "
        SELECT id
        FROM sat_reportes
        WHERE id = " . (int)$reporteId . "
          AND idUsuario = " . (int)$idFacilitador . "
        LIMIT 1
    ";
    $PSN1->query($sqlReporte);

    if (!$PSN1->next_record()) {
        throw new Exception('No se encontro el reporte indicado');
    }

    $allowedTypes = array(
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    );
    $maxSize = 5 * 1024 * 1024;
    $uploadDir = 'archivos/reportes';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        throw new Exception('No se pudo crear la carpeta de evidencias');
    }

    $imagesCount = 0;
    $imagenes = $_FILES['imagenes'];
    $total = is_array($imagenes['name']) ? count($imagenes['name']) : 0;

    if ($total > 3) {
        throw new Exception('Solo se permiten maximo 3 imagenes por reporte');
    }

    for ($i = 0; $i < $total; $i++) {
        if ($imagenes['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        if ($imagenes['size'][$i] > $maxSize) {
            continue;
        }

        $tmpName = $imagenes['tmp_name'][$i];
        $mime = mime_content_type($tmpName);

        if (!isset($allowedTypes[$mime])) {
            continue;
        }

        $ext = $allowedTypes[$mime];
        $originalName = addslashes((string)$imagenes['name'][$i]);
        $fileName = 'reporte_' . $reporteId . '_' . time() . '_' . $i . '.' . $ext;
        $relativePath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $relativePath)) {
            continue;
        }

        $sqlAdjunto = "INSERT INTO tbl_adjuntos (
            adj_nom,
            adj_url,
            adj_fec,
            adj_can,
            adj_rep_fk
        ) VALUES (
            '$originalName',
            '" . addslashes($relativePath) . "',
            '" . date('Y-m-d') . "',
            0,
            " . (int)$reporteId . "
        )";

        $PSN1->query($sqlAdjunto);
        $imagesCount++;
    }

    if ($imagesCount === 0) {
        throw new Exception('No se pudo guardar ninguna imagen valida');
    }

    echo json_encode(array(
        'success' => true,
        'imagesCount' => $imagesCount
    ));
} catch (Exception $e) {
    error_log('ERROR en guardar_imagenes_reporte.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ));
}
?>
