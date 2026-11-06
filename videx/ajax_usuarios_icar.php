<?
/*
*   AJAX PARA CARGUE DE USUARIOS
*/
session_start();
//session_register("SESSION");
include_once('funciones.php');
//
if(
    isset($_REQUEST["tipo_user"]) && 
    !empty($_REQUEST["tipo_user"]) && 
    soloNumeros($_REQUEST["tipo_user"]) != "" && 
    is_logged_in())
{
    //Todo bienb
}
else
{
    die ("Forbidden");
}

//
$PSN1 = new DBbase_Sql;
//
/*
*	TRAEMOS LOS USUARIOS AUTORIZADOS DEL CLIENTE PARA ESTE VEHÍCULO
*/
if(isset($_REQUEST["tipo_user"])){
    $tipo_user =  soloNumeros($_REQUEST["tipo_user"]);
    //
    if(isset($_REQUEST["idVehiculo"])){
        $idVehiculo =  soloNumeros($_REQUEST["idVehiculo"]);
        $sql = "SELECT usuario.id, usuario.nombre, vehiculo_usuarios.idVehiculo ";
    }
    else{
        $sql = "SELECT usuario.id, usuario.nombre ";
    }
    $sql.=" FROM usuario ";
    //
    if(isset($_REQUEST["idVehiculo"])){
        $sql .= " LEFT JOIN vehiculo_usuarios ON 
                            vehiculo_usuarios.idUsuario = usuario.id AND 
                            vehiculo_usuarios.idVehiculo = '".$idVehiculo."'";
    }
    //
    $sql.=" WHERE usuario.tipo = '".$tipo_user."'";
    $sql.=" GROUP BY usuario.id ORDER BY usuario.nombre asc";
    //
    $PSN1->query($sql);
    $numero=$PSN1->num_rows();
    if($numero > 0)
    {
        $cont = 0;
        while($PSN1->next_record())
        {
            $idUsuarioActual = $PSN1->f('id');
            if($cont == 2){
                ?></div><!-- CLOSE INSIDE //--><?
                $cont = 0;
            }
            //  
            if($cont == 0){
                ?><!-- OPEN INSIDE //--><div class="form-group"><?
            }
            //
            ?>
            <label class="control-label col-sm-4" for="login"><?
            if(file_exists("images/usuarios/".$idUsuarioActual.".jpg"))
            {
                ?><img src="images/usuarios/<?=$idUsuarioActual;?>.jpg" align="middle" width="20" height="20" align="left" /><?
            }
            else
            {
                ?><img src="images/consultores/desconocido.jpg" align="middle" width="20" height="20" align="left" /><?
            }	
            ?> <strong><?=$PSN1->f('nombre'); ?></strong></label>
            <div class="col-sm-2"><input type="checkbox" name="usu_relaciones[]" value="<?=$idUsuarioActual; ?>" class="form-control" <?
            if(isset($_REQUEST["idVehiculo"])){
                if($PSN1->f('idVehiculo') != "" && $PSN1->f('idVehiculo') != 0){
                    ?>checked="checked"<?
                }
            }
            ?> /></div>
            <?
            $cont++;
        }
        ?></div><?
    }
    else{
        ?><div class="row"><h5 class="alert alert-warning text-center">No se encontraron registros.</h5></div><?
    }
}
else{
        ?><div class="row"><h5 class="alert alert-warning text-center">Error de configuración.</h5></div><?
}
?>