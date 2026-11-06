<?php
//Id de estudiante a partir del 20 de Marzo de 2017 - 25797
if(isset($_GET["id"]))
{
	$varMiId = $_GET["id"];
}
else
{
	die("Debe especificarse un ID de Solicitud.");
}
	if($_SESSION["acceso_solo_lectura"] == 1){
		?><div class="row">
			<h5 class="alert alert-danger text-center"><strong>Atención:</strong> Su usuario es de solo lectura, no puede realizar modificaciones.</h5>
		</div><?
		exit;
	}
	//

/*if($_SESSION["perfil"] != 4 && $_SESSION["perfil"] != 5 && $_SESSION["per_aval_observaciones_crear"] != 1)
{
	die("No esta autorizado para realizar observaciones.");
}*/


/*if($_SESSION["perfil"] != 2)
{
	die("No esta autorizado para estar aqui.");
}*/
// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;


//
// COMPROBAMOS QUE EL USUARIO PERTENEZCA A ESTE REPORTE O SEA LIDER/ADMINISTRADOR
//
$sql = "SELECT proyecto_auditoria.id, proyecto_auditoria.estado, proyecto_auditoria.id_prospecto, proyecto_auditoria.nomb_proyecto";
$sql.=" FROM proyecto_auditoria ";
//$sql.=" LEFT JOIN usuario as cliente ON cliente.id = estudiante.idColegio";
$sql.=" WHERE proyecto_auditoria.id = ".$varMiId;
//
$PSN1->query($sql);
$numero=$PSN1->num_rows();
if($numero == 0)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}
else
{
	if($PSN1->next_record())
	{
		$varEstado = $PSN1->f('estado');
		$idClienteOk = $PSN1->f('id_prospecto');
		$contactoNombre = $PSN1->f('nomb_proyecto');
	}
}
//
//	FIN COMPROBACION
//

// Array que nos servira para ir llevando cuenta de los requerimientos.
$arrayRequerimientos = array();
if(isset($_POST["funcion"]))
{
	if($_POST["funcion"] == "insertarObservacion" && eliminarInvalidos($_POST["observacion"]) != "")
	{
		

		$nombre_archivo = $_FILES['archivo']['name'];
		$tipo_archivo = $_FILES['archivo']['type'];
		$tamano_archivo = $_FILES['archivo']['size'];
		$ext = extension_archivo($nombre_archivo);		
		
		
		
		$sql = 'insert into seguimientocambios (
					idProyecto, 
					idUsuario, 
					idCliente,
					tipo, 
					fecha_solicitud,
					fecha_entrega,
					estado,
					observacion, 
					fechaCreacion, 
					ext
				) ';		
		$sql .= 'values ('.$varMiId.', '.$_SESSION["id"].', '.$idClienteOk.', 
		"'.soloNumeros($_POST["tipo"]).'",
		"'.$_POST["fecha_solicitud"].'" ,
		"'.$_POST["fecha_entrega"].'" ,
		"'.$_POST["estado"].'" ,
		"'.eliminarInvalidos($_POST["observacion"]).'", NOW(), "'.$ext.'")';

		$ultimoQuery = $PSN1->query($sql);		
        $ultimoId =  $PSN1->ultimoId();
		$varExito = 1;

		
	
		//Compruebo si las características del archivo son las que deseo 
		if(move_uploaded_file($_FILES['archivo']['tmp_name'], "archivos/obs".$ultimoId.".".$ext))
		{
			//echo "Se movio...";
		}
		else
		{
			//echo "NO se movio...";
		}
		//
		//
		/*if(soloNumeros($_POST["respuestacliente"]) == 1 && $varEstado != 1 && $varEstado != 2){
			$correosEnviar[] = $_SESSION["email"];
			$correosEnviar = array_unique($correosEnviar);
			$correosEnviar = array_values($correosEnviar);
			$emails = implode(",", $correosEnviar);
			//
			$mensaje = "El presente es para informarle que se ha realizado una OBSERVACION  y <strong>se requiere su ATENCION INMEDIATA</strong> a la situacion de la solicitud de <b><i>".$nombre_deudor."</i></b> para poder continuar con el proceso de AVAL, la solicitud es la ".str_pad($varMiId, 6, "0", STR_PAD_LEFT)." en el sistema de AVAL de FINAVAL S.A.
<br />
<br />
Informacion de la Observacion:<br /><br />
<b>Observaciones:</b> ".eliminarInvalidos($_POST["observacion"])."
<br />
<br />
Para verificar el detalle completo de la solicitud y realizar el respectivo seguimiento por favor dirigase a nuestra plataforma dando clic en el siguiente enalce: <a href='http://www.goafinaval.com/gilberto_aval/index.php?doc=os_mainc_new&id=".$varMiId."'>http://www.goafinaval.com/gilberto_aval/index.php?doc=os_mainc_new&id=".$varMiId."</a>";
			//
			enviarEmail($emails, $mensaje, "ACTUALIZACION de la SOLICITUD: ".str_pad($varMiId, 6, "0", STR_PAD_LEFT));
			//enviarEmail("jlmflash@gmail.com", $mensaje."<br />OBSERVACION<br />".$emails, "AVAL - ACTUALIZACION de Solicitud: ".str_pad($varMiidEst, 6, "0", STR_PAD_LEFT));
		}*/
	}
}

?>
<form method="post" enctype="multipart/form-data" name="form1" id="form1" action="pop_up.php?doc=pop_seguimientocambios&id=<?=$varMiId; ?>&rtn=<?=$_GET["rtn"]; ?>" class="form">
	
    <div class="form-group">
        <h3 class="text-center well">.INSERTAR SEGUIMIENTO.</h3>
    </div>






    <div class="form-group">
        <label class="form-label" for="tipo">Tipo</label>
        <select name="tipo" class="form-control">
			<?
			/*
			*	TRAEMOS LOS TIPOS DE SEGUIMIENTO
			*/
			$sql = "SELECT * ";
			$sql.=" FROM categorias ";
			$sql.=" WHERE idSec = 21 ORDER BY descripcion asc";

			$PSN1->query($sql);
			$numero=$PSN1->num_rows();
			if($numero > 0)
			{
				while($PSN1->next_record())
				{
					?><option value="<?=$PSN1->f('id'); ?>" <?
					if(soloNumeros($_POST["tipo"]) == $PSN1->f('id'))
					{
						?>selected="selected"<?
					}
					?>><?=$PSN1->f('descripcion'); ?></option><?
				}
			}
			?>
			</select>
	
</div>
	
	<div class="form-group">
        <label class="form-label" for="fecha_solicitud">Fecha Solicitud</label>
        <input type='date' name="fecha_solicitud" id="fecha_solicitud" class="form-control"/>

</div>

	<div class="form-group">
        <label class="form-label" for="fecha_entrega">Fecha Entrega</label>
        <input type='date' name="fecha_entrega" id="fecha_entrega" class="form-control"/>

</div>

	<div class="form-group">
	<label class="form-label" for="estado">Tipo</label>
        <div class="col-sm-10">
		<select name="estado" id="estado" class="form-control">
		<option value="Relajado">Relajado</option>
		<option value="Caminando">Caminando</option>
		<option value="Corriendo">Corriendo</option>
		</select>	

		



</div>	

		



    <div class="form-group">
        <label class="form-label" for="observacion">Observaci&oacute;n</label>
        <textarea name="observacion" id="observacion" cols="80" rows="5" class="form-control"></textarea>
	</div>

    <div class="form-group">
        <label class="form-label" for="archivo">Archivo anexo</label>
        <input name="archivo" type="file" id="archivo" class="form-control" />

</div>

    <div class="row text-center">
        <input type="hidden" name="funcion" id="funcion" value="" />
        <input type="submit" value="Guardar cambios" class="btn btn-success" /> <a href="javascript:window.close();void(0)" class="btn btn-danger">Cerrar</a>
    </div>
    
	</form>
	<script language="javascript">
function generarForm(){
	if(confirm("Esta accion insertara una observacion a la cotizacion seleccionada, esta seguro que desea continuar?"))
	{
		if(document.getElementById('observacion').value != "")
		{
			document.getElementById('funcion').value = "insertarObservacion";
			return true;
		}
		else
		{
			alert("Debe escribir algo.");
		}
	}
    return false;
}

function init(){
	document.getElementById('form1').onsubmit = function(){
			return generarForm();
	}
	<?
	if($varExito == 1)
	{
		?>opener.location.href = 'index.php?doc=seguimiento_auditoria&id=<?=$varMiId; ?>';
		window.close();<?
	}
	?>
}

window.onload = function(){
	init();
}
</script>