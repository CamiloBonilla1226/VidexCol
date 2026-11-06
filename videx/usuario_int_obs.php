<?php
if($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 4 || $_SESSION["perfil"] == 160)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

if(isset($_GET["id"]))
{
	$idUsuario = soloNumeros($_GET["id"]);
}
else
{
	die("Debe especificarse un ID de solicitud.");
}

/*if($_SESSION["perfil"] != 2)
{
	die("No esta autorizado para estar aqui.");
}*/
// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;
// Array que nos servira para ir llevando cuenta de los requerimientos.
$arrayRequerimientos = array();


//
// COMPROBAMOS QUE EL USUARIO PERTENEZCA A ESTE REPORTE O SEA LIDER/ADMINISTRADOR
//
$sql = "SELECT usuario.id";
$sql.=" FROM usuario ";
$sql.=" WHERE usuario.id = '".$idUsuario."'";
//
$PSN1->query($sql);
$numero=$PSN1->num_rows();
if($numero == 0)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}
//
//	FIN COMPROBACION
//

if(isset($_POST["funcion"]))
{
	if($_POST["funcion"] == "insertarObservacion" && eliminarInvalidos($_POST["observaciones"]) != "")
	{
        $observaciones = eliminarInvalidos($_POST["observaciones"]);
        $tipo = soloNumeros($_POST["tipo"]);

        
        $nombre_archivo = $_FILES['archivo']['name'];
        $temp_location = $_FILES['archivo']['tmp_name'];
        $temp_ext = extension_archivo($nombre_archivo);
        $temp_nombreFile = strtotime("now").".".$temp_ext;
        $archivo = "";

        if(move_uploaded_file($temp_location, "archivos/usuarios/".$temp_nombreFile))
        {
            $archivo = $temp_nombreFile;
        }
        
        
		$sql = 'INSERT INTO usuario_obs (
					idUsuario, 
					tipo, 
					observacion, 
                    usuarioCreacion,
					fechaCreacion, 
					archivo
				) ';
		$sql .= 'VALUES (
                    '.$idUsuario.',
                    "'.$tipo.'", 
                    "'.$observaciones.'", 
                    '.$_SESSION["id"].', 
                    NOW(), 
                    "'.$archivo.'"
        )';

		$ultimoQuery = $PSN1->query($sql);		
        $ultimoId =  $PSN1->ultimoId();
		$varExito = 1;

		//DEBEMOS ACTUALIZAR EL REPORTE PARA SABER QUIEN FUE LA ULTIMA PERSONA EN ESCRIBIR
		//YA SEA UNA MODIFICACIÓN A LA INFORMACIÓN, UNA OBSERVACIÓN, ETC.
        if($ultimoId > 0){
            $sql = "UPDATE usuario SET ";
                $sql .= "modUsuario = '".$_SESSION["id"]."', ";
                $sql .= "modFecha = NOW()";
            $sql .= " WHERE id = '".$idUsuario."'";
            $PSN1->query($sql);
        }
		//FIN ACTUALIZACIÓN DE REGISTROS
	}
}




/*
*	TRAEMOS LOS RECURSOS
DATE_SUB( observacion.fechaCreacion, INTERVAL 0 MINUTE )
*/
$sql = "SELECT 
            usuario.nombre, 
            usuario_obs.id as idObservacion, 
            usuario_obs.observacion, 
            usuario_obs.archivo, 
            usuario_obs.fechaCreacion,
            usuario_obs.usuarioCreacion,
            categorias.descripcion as tipoDescripcion ";
$sql.=" FROM usuario, usuario_obs ";
$sql.=" LEFT JOIN  categorias ON categorias.id = usuario_obs.tipo";
$sql.=" WHERE usuario_obs.idUsuario = '".$idUsuario."' AND usuario.id = usuario_obs.usuarioCreacion";
$sql.=" ORDER BY usuario_obs.fechaCreacion ASC";
//
$PSN1->query($sql);
$numero=$PSN1->num_rows();
//
?><div class="container">
    <div class="row">
                <h3 class="text-center well">.OBSERVACIONES.</h3>
    </div>

<div class="row">

<div class="col-md-6 col-md-offset-3">
<table border="0" cellspacing="12" cellpadding="12"  align="center" class="table table-bordered" style="font-size:12px">
<tbody>
<?
if($numero > 0)
{
	$contador = 0;
	while($PSN1->next_record())
	{
		$tipoDescripcion = $PSN1->f('tipoDescripcion');
		$nombreUsuarioCreador = $PSN1->f('nombre');
		$usuarioCreacion = $PSN1->f('usuarioCreacion');
		$fechaCreacion = $PSN1->f('fechaCreacion');
		$observacion = $PSN1->f('observacion');
		$archivo = $PSN1->f('archivo');
        
		?><tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>> 
			<td rowspan="2"><?
            if(file_exists("images/usuarios/".$usuarioCreacion.".jpg"))
            {
                ?><img src="images/usuarios/<?=$usuarioCreacion;?>.jpg" align="middle" width="40" height="40"><?
            }
            else
            {
                ?><img src="images/consultores/desconocido.jpg" align="middle" width="40" height="40"><?
            }	
			?></td>
			<td><strong><?=$tipoDescripcion; ?></strong> - <i><?=$nombreUsuarioCreador." - ".date("d-m-Y H:i a", strtotime($fechaCreacion)); ?> </i> <!--<i>[<a href="javascript:asignarObservacion(<?=$varIdSeguimiento; ?>);void(0);">editar</a>]</i>//--></td>
		</tr>
		<tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>> 
			<td><?=$observacion; ?><?

			//Compruebo si las características del archivo son las que deseo 25797
			if($archivo != "")
			{
				?><br /><a href="descarga_usuario.php?&archivo=<?=$archivo; ?>" target="_blank"><img src="images/adjunto.jpg" align="middle" border="0"></a><i><strong>Contiene archivo adjunto:</strong> <a href="descarga_usuario.php?&archivo=<?=$archivo; ?>">Bajar Archivo</a></i><?
			}
			?></td>
		</tr>
		<?
		$contador++;
	}
}
else
{
	?>
	<tr><td>Sin observaciones.</td></tr>
	<?
}
?>
	</tbody>
</table>
</div>
</div>


<form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
<input type="hidden" name="id" value="<?=$idUsuario; ?>" />
    
    <div class="panel panel-warning">
      <div class="panel-heading text-center">AGREGAR NUEVA OBSERVACIÓN</div>
      <div class="panel-body"><div class="form-group">
        <label class="form-label" for="tipo"><strong>Tipo:</strong></label>
        <select name="tipo" class="form-control">
            <?
            /*
            *	TRAEMOS LOS TIPOS DE PERSONA JURIDICA O NATURAL
            */
            $sql = "SELECT * ";
            $sql.=" FROM categorias ";
            $sql.=" WHERE idSec = 30 ORDER BY descripcion asc";

            $PSN1->query($sql);
            $numero=$PSN1->num_rows();
            if($numero > 0)
                {
                while($PSN1->next_record())
                {
                    ?><option value="<?=$PSN1->f('id'); ?>" <?
                    if($PSN1->f('id') == 174)
                    {
                        ?>selected="selected"<?
                    }
                    ?>><?=$PSN1->f('descripcion'); ?></option><?
                }
            }
            ?>
        </select></div>
        
        <label class="form-label" for="observaciones"><strong>Observaciones</strong></label>
        <textarea name="observaciones" id="observaciones" class="form-control" required></textarea></div>
        
        <label class="form-label" for="archivo"><strong>Adjunto</strong></label>
        <input name="archivo" type="file" id="archivo"  class="form-control" /></div>
        
        <input type="submit" name="button" value="Enviar observación" class="btn btn-success"></div>
    </div>
    </div>
    </div>
    <input type="hidden" name="funcion" id="funcion" value="" />
</form>    

</div>
<a name="final">&nbsp;</a>
    
<script language="javascript">
function generarForm(){
        if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?"))
        {
            if(document.getElementById('observaciones').value != "" )
            {
                //Todo bien
                document.getElementById('funcion').value = "insertarObservacion";                
            }
            else
            {
                alert("La informacion es primordial para brindarle un excelente servicio, por favor digite el campo de OBSERVACIONES");
                return false;
            }
        }else{
            return false;
        }
        return true;
}
//
function init(){
    document.getElementById('form1').onsubmit = function(){
        return generarForm();
    }
}

window.onload = function(){
    init();
}

</script>