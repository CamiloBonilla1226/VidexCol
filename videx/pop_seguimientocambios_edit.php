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
$sql = "SELECT proyecto_auditoria.id, seguimientocambios.estado, proyecto_auditoria.id_prospecto, proyecto_auditoria.nomb_proyecto, seguimientocambios.tipo,seguimientocambios.fecha_solicitud,seguimientocambios.fecha_entrega, seguimientocambios.observacion";
$sql.=" FROM proyecto_auditoria, seguimientocambios ";
$sql.=" WHERE proyecto_auditoria.id = ".$varMiId." 
AND seguimientocambios.idProyecto = proyecto_auditoria.id AND seguimientocambios.id = '".$varMiSeg."'";
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
		$fecha_solicitud = $PSN1->f('fecha_solicitud');
		$fecha_entrega =  $PSN1->f('fecha_entrega');
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
		
		
		$sql = 'UPDATE seguimientocambios SET 
					tipo = "'.soloNumeros($_POST["tipo"]).'",
					fecha_solicitud = "'.$_POST["fecha_solicitud"].'",
					fecha_entrega = "'.$_POST["fecha_entrega"].'",
					estado = "'.$_POST["estado"].'",
					observacion = "'.eliminarInvalidos($_POST["observacion"]).'" 
				WHERE 
					id = '.$varMiSeg.'
				';

		$ultimoQuery = $PSN1->query($sql);		
		$varExito = 1;

		
	}
}

?><form method="post" enctype="multipart/form-data" name="form1" id="form1" action="pop_up.php?doc=pop_seguimientocambios_edit&id=<?=$varMiId; ?>&seg=<?=$varMiSeg; ?>&rtn=<?=$_GET["rtn"]; ?>" class="form">

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
        <label class="form-label" for="fecha_solicitud">Fecha Solicitud</label>
        <input type='date' name="fecha_solicitud" id="fecha_solicitud" value="<?=$fecha_solicitud; ?>" class="form-control"/></div>
	</div>

	<div class="form-group">
        <label class="form-label" for="fecha_entrega">Fecha Entrega</label>
        <input type='date' name="fecha_entrega" id="fecha_entrega" value="<?=$fecha_entrega; ?>" class="form-control"/></div>
	</div>

	<div class="form-group">
	<label class="form-label" for="estado">Tipo</label>
        <div class="col-sm-10">
		<select name="estado" id="estado" class="form-control">

		<? if($varEstado == "Relajado") { ?> 
		<option selected="selected" value="Relajado">Relajado</option>
		<option value="Caminando">Caminando</option>
		<option value="Corriendo">Corriendo</option>
		<? } elseif ($varEstado == "Caminando") { ?>
			<option  value="Relajado">Relajado</option>
			<option selected="selected" value="Caminando">Caminando</option>
			<option value="Corriendo">Corriendo</option>
		
		<? } else { ?>
			<option  value="Relajado">Relajado</option>
			<option value="Caminando">Caminando</option>
		<option selected="selected" value="Corriendo">Corriendo</option>
		
		
		
		<? }?>
	
	</select>	






</div>	






    <div class="form-group">
        <label class="form-label" for="observacion">Observaci&oacute;n</label>
        <textarea name="observacion" id="observacion" class="form-control" cols="80" rows="5"><?=$obsObservacion; ?></textarea>
    </div>

    <div class="row text-center">
        <input type="hidden" name="funcion" id="funcion" value="" />
        <input type="submit" value="Guardar cambios" class="btn btn-success" /> <a href="javascript:window.close();void(0)" class="btn btn-danger">Cerrar</a>
    </div>

</form>
<script language="javascript">
function generarForm(){
	if(confirm("Esta accion actualizara la observacion del seguimiento de la gestion de cambios seleccionada, esta seguro que desea continuar?"))
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

function init(){
	document.getElementById('form1').onsubmit = function(){
			return generarForm();
	}
	<?
	if($varExito == 1)
	{
		?>opener.parent.location.href = 'index.php?doc=seguimiento_auditoria&id=<?=$varMiId; ?>';
		window.close();<?
	}
	?>
}

window.onload = function(){
	init();
}
</script>