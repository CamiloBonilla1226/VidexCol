<?php
/*
 * ajax_info_grupo.php
 * Endpoint JSON usado por gestionar-facilitador.php para mostrar la
 * información de un grupo (ecu_grupos) sin recargar la página.
 */
session_start();
include_once('funciones.php');

header('Content-Type: application/json; charset=utf-8');

$respuesta = array("ok" => false);

$idUsuarioSesion = isset($_SESSION["id"]) ? intval($_SESSION["id"]) : 0;
$idGrupo = isset($_GET["idgrupo"]) ? intval($_GET["idgrupo"]) : 0;

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
$sql = "SELECT id_grupo, nombre_grupo, generacion, grupo_anterior, fecha_creacion, id_usuario FROM ecu_grupos ";
$sql .= "WHERE id_grupo = ".$idGrupo." AND id_usuario = ".$idUsuarioSesion." AND generacion NOT IN (0,1) LIMIT 1";
$PSN1->query($sql);

if($PSN1->num_rows() == 0){
    http_response_code(404);
    $respuesta["mensaje"] = "Grupo no encontrado.";
    echo json_encode($respuesta);
    exit;
}

$PSN1->next_record();
$nombreGrupo = $PSN1->f("nombre_grupo");
$generacion = intval($PSN1->f("generacion"));
$grupoAnteriorId = intval($PSN1->f("grupo_anterior"));
$fechaCreacion = $PSN1->f("fecha_creacion");
$idUsuarioCreador = intval($PSN1->f("id_usuario"));

$nombreCreador = "";
$PSN2 = new DBbase_Sql;
$sqlCreador = "SELECT nombre FROM usuario WHERE id = ".$idUsuarioCreador." LIMIT 1";
$PSN2->query($sqlCreador);
if($PSN2->num_rows() > 0){
    $PSN2->next_record();
    $nombreCreador = $PSN2->f("nombre");
}

$totalReportes = 0;
$PSN3 = new DBbase_Sql;
$sqlConteo = "SELECT COUNT(*) AS total FROM ecu_reportes WHERE idgrupo = ".$idGrupo;
$PSN3->query($sqlConteo);
if($PSN3->num_rows() > 0){
    $PSN3->next_record();
    $totalReportes = intval($PSN3->f("total"));
}

/*
*   Grupo/generación anterior:
*   - Generación 2: no tiene "grupo_anterior" en ecu_grupos (la generación 1
*     no vive en esta tabla). Se calcula a partir de
*     usuario_empresa.empresa_proceso del usuario que creó el grupo.
*   - Generación 3 en adelante: se busca directamente el nombre del grupo
*     referenciado por grupo_anterior.
*/
$generacionAnteriorNombre = null;
$generacionAnteriorMensaje = null;

if($generacion == 2){
    $PSN4 = new DBbase_Sql;
    $sqlProceso = "SELECT empresa_proceso FROM usuario_empresa WHERE idUsuario = ".$idUsuarioCreador." LIMIT 1";
    $PSN4->query($sqlProceso);
    $empresaProcesoId = 0;
    if($PSN4->num_rows() > 0){
        $PSN4->next_record();
        $empresaProcesoId = intval($PSN4->f("empresa_proceso"));
    }
    if($empresaProcesoId > 0){
        $PSN5 = new DBbase_Sql;
        $sqlCategoria = "SELECT descripcion FROM categorias WHERE id = ".$empresaProcesoId." LIMIT 1";
        $PSN5->query($sqlCategoria);
        if($PSN5->num_rows() > 0){
            $PSN5->next_record();
            $generacionAnteriorNombre = $PSN5->f("descripcion");
        }
    }
    if($generacionAnteriorNombre === null){
        $generacionAnteriorMensaje = "El usuario actualmente no pertenece a un grupo de generación 1.";
    }
}else{
    if($grupoAnteriorId > 0){
        $PSN4 = new DBbase_Sql;
        $sqlPadreNombre = "SELECT nombre_grupo FROM ecu_grupos WHERE id_grupo = ".$grupoAnteriorId." LIMIT 1";
        $PSN4->query($sqlPadreNombre);
        if($PSN4->num_rows() > 0){
            $PSN4->next_record();
            $generacionAnteriorNombre = $PSN4->f("nombre_grupo");
        }
    }
    if($generacionAnteriorNombre === null){
        $generacionAnteriorMensaje = "Este grupo no tiene un grupo antecesor registrado.";
    }
}

$respuesta["ok"] = true;
$respuesta["id_grupo"] = $idGrupo;
$respuesta["nombre_grupo"] = $nombreGrupo;
$respuesta["generacion"] = $generacion;
$respuesta["fecha_creacion"] = date("d/m/Y", strtotime($fechaCreacion));
$respuesta["creado_por"] = $nombreCreador;
$respuesta["total_reportes"] = $totalReportes;
$respuesta["generacion_anterior_nombre"] = $generacionAnteriorNombre;
$respuesta["generacion_anterior_mensaje"] = $generacionAnteriorMensaje;

echo json_encode($respuesta);
