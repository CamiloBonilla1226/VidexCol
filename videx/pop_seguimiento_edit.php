<?php
//Id de estudiante a partir del 20 de Marzo de 2017 - 25797
if(isset($_GET["id"]) && isset($_GET["seg"]))
{
	$varMiId = soloNumeros($_GET["id"]);
	$varMiSeg = soloNumeros($_GET["seg"]);
}
else
{
	die("Debe especificarse un ID de Solicitud.");
}

/*if($_SESSION["superusuario"] != 1)
{
	die("No esta autorizado para estar aqui.");
}*/

	if($_SESSION["acceso_solo_lectura"] == 1){
		?><div class="row">
			<h5 class="alert alert-danger text-center"><strong>Atención:</strong> Su usuario es de solo lectura, no puede realizar modificaciones.</h5>
		</div><?
		exit;
	}
	//


// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;

//
// COMPROBAMOS QUE EL USUARIO PERTENEZCA A ESTE REPORTE O SEA LIDER/ADMINISTRADOR
//
$sql = "SELECT cotizacion.id, cotizacion.estado, cotizacion.idCliente, cotizacion.contactoNombre, seguimiento.tipo, seguimiento.observacion";
$sql.=" FROM cotizacion, seguimiento ";
$sql.=" WHERE cotizacion.id = ".$varMiId." AND seguimiento.idCotizacion = cotizacion.id AND seguimiento.id = '".$varMiSeg."'";
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
		$idClienteOk = $PSN1->f('idCliente');
		$contactoNombre = $PSN1->f('contactoNombre');
		
		$obsTipo = $PSN1->f('tipo');
		$obsObservacion = $PSN1->f('observacion');
	}
}
//
//	FIN COMPROBACION
//

// Array que nos servira para ir llevando cuenta de los requerimientos.
$arrayRequerimientos = array();
if(isset($_POST["funcion"]))
{
	if($_POST["funcion"] == "updateObservacion" && eliminarInvalidos($_POST["observacion"]) != "")
	{
		/*
		*	DEBEMOS OBTENER VALORES DE LOS CORREOS DEL AFILIADO
		*/
		/*$nombre_archivo = $_FILES['archivo']['name'];
		$tipo_archivo = $_FILES['archivo']['type'];
		$tamano_archivo = $_FILES['archivo']['size'];
		$ext = extension_archivo($nombre_archivo);		
		
		echo "<strong>Resultado archivo: </strong>";
		switch($_FILES['archivo']['error'])
		{
			case UPLOAD_ERR_OK:
				//$messageErr = false;;
				echo "UPLOAD_ERR_OK";
				break;
			case UPLOAD_ERR_INI_SIZE:
				//echo ' - file too large (limit of '.get_max_upload().' bytes).';
				echo "UPLOAD_ERR_INI_SIZE";
				break;
			case UPLOAD_ERR_FORM_SIZE:
				echo ' - file too large (limit of '.get_max_upload().' bytes).';
				break;
			case UPLOAD_ERR_PARTIAL:
				echo ' - file upload was not completed.';
				break;
			case UPLOAD_ERR_NO_FILE:
				echo ' - zero-length file uploaded.';
				break;
			default:
				echo ' - internal error #'.$_FILES['archivo']['error'];
				break;
		}
		echo "<br />";
		echo "<strong>Nombre archivo: </strong>";
		echo $nombre_archivo;
		echo "<br />";
		echo "<strong>Tipo de archivo: </strong>";
		echo $tipo_archivo;
		echo "<br />";
		echo "<strong>Tamaño de archivo: </strong>";
		echo $tamano_archivo;
		echo "<br />";
		echo "<strong>Ext de archivo: </strong>";
		echo $ext;
		echo "<br />";
		echo "<strong>Ubicacion de archivo: </strong>";
		echo $_FILES['archivo']['tmp_name'];*/
		
		$sql = 'UPDATE seguimiento SET 
					tipo = "'.soloNumeros($_POST["tipo"]).'", 
					observacion = "'.eliminarInvalidos($_POST["observacion"]).'" 
				WHERE 
					id = '.$varMiSeg.'
				';

		$ultimoQuery = $PSN1->query($sql);		
		$varExito = 1;

		//DEBEMOS ACTUALIZAR EL REPORTE PARA SABER QUIEN FUE LA ULTIMA PERSONA EN ESCRIBIR
		//YA SEA UNA MODIFICACIÓN A LA INFORMACIÓN, UNA OBSERVACIÓN, ETC.
		$sql = "UPDATE cotizacion SET ";
			$sql .= "modUsuario = '".$_SESSION["id"]."'";
			$sql .= ", modFecha = NOW()";
		$sql .= " WHERE id = '".$varMiId."'";
		$PSN1->query($sql);
		//FIN ACTUALIZACIÓN DE REGISTROS
	
		//Compruebo si las características del archivo son las que deseo 
		/*if(move_uploaded_file($_FILES['archivo']['tmp_name'], "archivos/obs".$ultimoId.".".$ext))
		{
			echo "Se movio...";
		}
		else
		{
			echo "NO se movio...";
		}*/
	}
}

?><form method="post" enctype="multipart/form-data" name="form1" id="form1" action="pop_up.php?doc=pop_seguimiento_edit&id=<?=$varMiId; ?>&seg=<?=$varMiSeg; ?>&rtn=<?=$_GET["rtn"]; ?>" class="form">

    <div class="form-group">
        <h3 class="text-center well">.ACTUALIZAR SEGUIMIENTO.</h3>
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
				if($obsTipo == $PSN1->f('id'))
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
        <label class="form-label" for="observacion">Observaci&oacute;n</label>
        <textarea name="observacion" id="observacion" class="form-control" cols="80" rows="5"><?=$obsObservacion; ?></textarea>
    </div>

	<?php render_archivo_adjuntado('obs', $varMiSeg, 'Archivo adjunto'); ?>

    <div class="row text-center">
        <input type="hidden" name="funcion" id="funcion" value="" />
        <input type="submit" value="Guardar cambios" class="btn btn-success" /> <a href="javascript:window.close();void(0)" class="btn btn-danger">Cerrar</a>
    </div>

</form>
<script language="javascript">
function generarForm(){
	if(confirm("Esta accion actualizara la observacion a la cotizacion seleccionada, esta seguro que desea continuar?"))
	{
		if(document.getElementById('observacion').value != "")
		{
			document.getElementById('funcion').value = "updateObservacion";
			return true;
		}
		else
		{
			alert("Debe escribir algo.");
		}
	}
    return false;
}

function cerrarYRefrescar(){
	var destino = 'index.php?doc=cotizacion&id=<?=$varMiId; ?>';
	try {
		if(window.top && window.top !== window) {
			window.top.postMessage({ type: 'cotizacion-modal-close-refresh', url: destino }, '*');
			return;
		}
	} catch (e) {}
	try {
		if(window.parent && window.parent !== window && typeof window.parent.closeCotizacionModal === 'function') {
			window.parent.closeCotizacionModal();
			window.parent.location.href = destino;
			return;
		}
	} catch (e) {}
	try {
		if(window.opener && window.opener.parent) {
			window.opener.parent.location.href = destino;
			window.close();
			return;
		}
	} catch (e) {}
	window.location.href = destino;
}

function init(){
	document.getElementById('form1').onsubmit = function(){
			return generarForm();
	}
	<?
	if($varExito == 1)
	{
		?>cerrarYRefrescar();<?
	}
	?>
}

window.onload = function(){
	init();
}
</script>