<?php
/*
 * ajax_eliminar_foto_reporte.php
 * Endpoint JSON usado por editar_facilitador.php para eliminar una de las
 * (hasta 2) fotos de un reporte de Facilitadores. Solo se permite si el
 * reporte tiene las 2 fotos (nunca se puede dejar sin ninguna foto), y solo
 * el administrador puede usarlo (mismo criterio que editar/eliminar
 * reporte).
 */
session_start();
include_once('funciones.php');

header('Content-Type: application/json; charset=utf-8');

$respuesta = array("ok" => false);

$idUsuarioSesion = isset($_SESSION["id"]) ? intval($_SESSION["id"]) : 0;
$idReporte = isset($_POST["idreporte"]) ? intval($_POST["idreporte"]) : 0;
$slot = isset($_POST["slot"]) ? intval($_POST["slot"]) : 0;

if($idUsuarioSesion == 0){
    http_response_code(401);
    $respuesta["mensaje"] = "Debe iniciar sesión para continuar.";
    echo json_encode($respuesta);
    exit;
}

if($idReporte <= 0 || ($slot != 1 && $slot != 2)){
    http_response_code(400);
    $respuesta["mensaje"] = "Solicitud inválida.";
    echo json_encode($respuesta);
    exit;
}

$PSN1 = new DBbase_Sql;
$PSN1->connect();

$usuarioTipo = 0;
$PSN1->query("SELECT tipo FROM usuario WHERE id = ".$idUsuarioSesion." LIMIT 1");
if($PSN1->num_rows() > 0){
    $PSN1->next_record();
    $usuarioTipo = intval($PSN1->f("tipo"));
}

if($usuarioTipo != 2){
    http_response_code(403);
    $respuesta["mensaje"] = "Solo un administrador puede eliminar fotos de un reporte.";
    echo json_encode($respuesta);
    exit;
}

$PSN1->query("SELECT foto, foto2 FROM ecu_reportes WHERE idreporte = ".$idReporte." AND tipo_reporte = 318 LIMIT 1");
if($PSN1->num_rows() == 0){
    http_response_code(404);
    $respuesta["mensaje"] = "Reporte no encontrado.";
    echo json_encode($respuesta);
    exit;
}

$PSN1->next_record();
$foto = $PSN1->f("foto");
$foto2 = $PSN1->f("foto2");

$fotoDelSlot = ($slot == 1) ? $foto : $foto2;
$otraFoto = ($slot == 1) ? $foto2 : $foto;

if($fotoDelSlot == ""){
    $respuesta["mensaje"] = "Ese slot ya no tiene foto.";
    echo json_encode($respuesta);
    exit;
}

/*
*   Nunca se puede dejar el reporte sin ninguna foto.
*/
if($otraFoto == ""){
    $respuesta["mensaje"] = "El reporte debe conservar al menos una foto. Suba otra antes de eliminar esta.";
    echo json_encode($respuesta);
    exit;
}

$rutaFoto = ($slot == 1)
    ? "archivos/facilitador_".$idReporte.".".$fotoDelSlot
    : "archivos/facilitador_".$idReporte."_2.".$fotoDelSlot;

if(file_exists($rutaFoto)){
    unlink($rutaFoto);
}

$columna = ($slot == 1) ? "foto" : "foto2";
$PSN1->query("UPDATE ecu_reportes SET ".$columna." = NULL WHERE idreporte = ".$idReporte);

$respuesta["ok"] = true;
echo json_encode($respuesta);
