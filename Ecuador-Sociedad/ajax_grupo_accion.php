<?php
/*
 * ajax_grupo_accion.php
 * Endpoint JSON usado por gestionar-facilitador.php para editar el nombre
 * de un grupo o eliminarlo (ecu_grupos), sin recargar la página.
 */
session_start();
include_once('funciones.php');

header('Content-Type: application/json; charset=utf-8');

$respuesta = array("ok" => false);

$idUsuarioSesion = isset($_SESSION["id"]) ? intval($_SESSION["id"]) : 0;
$idGrupo = isset($_POST["idgrupo"]) ? intval($_POST["idgrupo"]) : 0;
$accion = isset($_POST["accion"]) ? trim($_POST["accion"]) : "";

if($idUsuarioSesion == 0){
    http_response_code(401);
    $respuesta["mensaje"] = "Debe iniciar sesión para continuar.";
    echo json_encode($respuesta);
    exit;
}

if($idGrupo <= 0){
    http_response_code(400);
    $respuesta["mensaje"] = "Grupo no especificado.";
    echo json_encode($respuesta);
    exit;
}

$PSN1 = new DBbase_Sql;
$PSN1->connect();

/*
*   El grupo debe pertenecer al usuario de sesión y no ser generación 0 ni 1
*   (misma regla aplicada en gestionar-facilitador.php).
*/
$sqlGrupo = "SELECT id_grupo FROM ecu_grupos ";
$sqlGrupo .= "WHERE id_grupo = ".$idGrupo." AND id_usuario = ".$idUsuarioSesion." AND generacion NOT IN (0,1) LIMIT 1";
$PSN1->query($sqlGrupo);

if($PSN1->num_rows() == 0){
    http_response_code(404);
    $respuesta["mensaje"] = "Grupo no encontrado.";
    echo json_encode($respuesta);
    exit;
}

if($accion == "editar_nombre"){

    $nombreNuevo = isset($_POST["nombre_grupo"]) ? trim($_POST["nombre_grupo"]) : "";

    if($nombreNuevo == ""){
        $respuesta["mensaje"] = "El nombre del grupo es obligatorio.";
        echo json_encode($respuesta);
        exit;
    }

    $nombreEscapado = mysqli_real_escape_string($PSN1->Link_ID, $nombreNuevo);
    $sqlUpdate = "UPDATE ecu_grupos SET nombre_grupo = '".$nombreEscapado."' ";
    $sqlUpdate .= "WHERE id_grupo = ".$idGrupo." AND id_usuario = ".$idUsuarioSesion;
    $PSN1->query($sqlUpdate);

    $respuesta["ok"] = true;
    $respuesta["id_grupo"] = $idGrupo;
    $respuesta["nombre_grupo"] = $nombreNuevo;
    echo json_encode($respuesta);
    exit;

}else if($accion == "eliminar"){

    /*
    *   No se puede eliminar si el grupo es "grupo_anterior" de otro grupo
    *   (es grupo madre de otro) ni si ya tiene reportes asociados en
    *   ecu_reportes. En cualquiera de esos casos se informa que hay que
    *   contactar al administrador.
    */
    $PSN2 = new DBbase_Sql;
    $sqlHijos = "SELECT COUNT(*) AS total FROM ecu_grupos WHERE grupo_anterior = ".$idGrupo;
    $PSN2->query($sqlHijos);
    $tieneHijos = 0;
    if($PSN2->num_rows() > 0){
        $PSN2->next_record();
        $tieneHijos = intval($PSN2->f("total"));
    }

    $PSN3 = new DBbase_Sql;
    $sqlReportes = "SELECT COUNT(*) AS total FROM ecu_reportes WHERE idgrupo = ".$idGrupo;
    $PSN3->query($sqlReportes);
    $tieneReportes = 0;
    if($PSN3->num_rows() > 0){
        $PSN3->next_record();
        $tieneReportes = intval($PSN3->f("total"));
    }

    if($tieneHijos > 0 && $tieneReportes > 0){
        $respuesta["mensaje"] = "No es posible eliminar este grupo: ya tiene reportes registrados y además otros grupos fueron creados a partir de él. Si de verdad necesitas eliminarlo, comunícate con el administrador del sistema.";
        echo json_encode($respuesta);
        exit;
    }else if($tieneHijos > 0){
        $respuesta["mensaje"] = "No es posible eliminar este grupo porque otros grupos fueron creados a partir de él (es su grupo de origen). Si de verdad necesitas eliminarlo, comunícate con el administrador del sistema.";
        echo json_encode($respuesta);
        exit;
    }else if($tieneReportes > 0){
        $respuesta["mensaje"] = "No es posible eliminar este grupo porque ya tiene reportes registrados. Si de verdad necesitas eliminarlo, comunícate con el administrador del sistema.";
        echo json_encode($respuesta);
        exit;
    }

    $sqlDelete = "DELETE FROM ecu_grupos WHERE id_grupo = ".$idGrupo." AND id_usuario = ".$idUsuarioSesion;
    $PSN1->query($sqlDelete);

    $respuesta["ok"] = true;
    echo json_encode($respuesta);
    exit;

}else{
    http_response_code(400);
    $respuesta["mensaje"] = "Acción no reconocida.";
    echo json_encode($respuesta);
    exit;
}
