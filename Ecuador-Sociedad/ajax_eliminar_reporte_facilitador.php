<?php
/*
 * ajax_eliminar_reporte_facilitador.php
 * Endpoint JSON usado por editar_facilitador.php para eliminar un reporte
 * de Facilitadores (ecu_reportes, tipo_reporte = 318), incluida su foto
 * física si tiene una.
 */
session_start();
include_once('funciones.php');

header('Content-Type: application/json; charset=utf-8');

$respuesta = array("ok" => false);

$idUsuarioSesion = isset($_SESSION["id"]) ? intval($_SESSION["id"]) : 0;
$idReporte = isset($_POST["idreporte"]) ? intval($_POST["idreporte"]) : 0;

if($idUsuarioSesion == 0){
    http_response_code(401);
    $respuesta["mensaje"] = "Debe iniciar sesión para continuar.";
    echo json_encode($respuesta);
    exit;
}

if($idReporte <= 0){
    http_response_code(400);
    $respuesta["mensaje"] = "Reporte no especificado.";
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
$esAdmin = ($usuarioTipo == 2);

$sqlReporte = "SELECT foto, foto2 FROM ecu_reportes WHERE idreporte = ".$idReporte." AND tipo_reporte = 318 LIMIT 1";
$PSN1->query($sqlReporte);

if($PSN1->num_rows() == 0){
    http_response_code(404);
    $respuesta["mensaje"] = "Reporte no encontrado.";
    echo json_encode($respuesta);
    exit;
}

$PSN1->next_record();
$foto = $PSN1->f("foto");
$foto2 = $PSN1->f("foto2");

/*
*   Solo el administrador puede eliminar reportes, sin importar de quién
*   sean (ver editar_facilitador.php: los demás usuarios solo pueden
*   consultar los suyos, no editarlos ni eliminarlos).
*/
if(!$esAdmin){
    http_response_code(403);
    $respuesta["mensaje"] = "Solo un administrador puede eliminar reportes.";
    echo json_encode($respuesta);
    exit;
}

$PSN1->query("DELETE FROM ecu_reportes WHERE idreporte = ".$idReporte);

if($foto != ""){
    $rutaFoto = "archivos/facilitador_".$idReporte.".".$foto;
    if(file_exists($rutaFoto)){
        unlink($rutaFoto);
    }
}
if($foto2 != ""){
    $rutaFoto2 = "archivos/facilitador_".$idReporte."_2.".$foto2;
    if(file_exists($rutaFoto2)){
        unlink($rutaFoto2);
    }
}

$respuesta["ok"] = true;
echo json_encode($respuesta);
