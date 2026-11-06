<?
/*
*   AJAX PARA CARGUE DE USUARIOS
*/
session_start();
//session_register("SESSION");
include_once('funciones.php');

if(
    isset($_REQUEST["idCliente"]) && 
    !empty($_REQUEST["idCliente"]) && 
    soloNumeros($_REQUEST["idCliente"]) != "" && 
    is_logged_in() && 
    $_SESION["perfil"] != 3 && 
    $_SESION["perfil"] != 4 && 
    $_SESION["perfil"] != 160)
{
    //Todo bienb
}else{
    die ("Forbidden");
}

//
$PSN1 = new DBbase_Sql;
//
/*
*	TRAEMOS LOS USUARIOS AUTORIZADOS DEL CLIENTE PARA ESTE VEHÍCULO
*/
if(isset($_REQUEST["tipo_user_cli"]) && isset($_REQUEST["idCliente"])){
    $idCliente = soloNumeros($_REQUEST["idCliente"]);
    $tipo_user_cli =  soloNumeros($_REQUEST["tipo_user_cli"]);
    //
    $sql = "SELECT usuario.id, usuario.nombre ";
    $sql.=" FROM usuario ";
    $sql .= " LEFT JOIN usuario_relacion ON usuario_relacion.idUsuario1 = usuario.id ";
    $sql .= " LEFT JOIN usuario as cliente ON cliente.id = usuario_relacion.idUsuario2 AND cliente.tipo = 3";
    $sql.=" WHERE usuario.tipo = 160 AND usuario.tipo_user_cli = '".$tipo_user_cli."' AND cliente.id = '".$idCliente."'";
    $sql.=" ORDER BY usuario.nombre asc";
    //
    $PSN1->query($sql);
    $numero=$PSN1->num_rows();
    if($numero > 0)
    {
        ?><option value="">Sin especificar</option><?
        while($PSN1->next_record())
        {
            ?><option value="<?=$PSN1->f('id'); ?>"><?=$PSN1->f('nombre'); ?></option><?
        }
    }
    else{
        ?><option value="">No se encontraron registros</option><?
    }
}
else{
    ?><option value="">Error de configuración</option><?
}
?>