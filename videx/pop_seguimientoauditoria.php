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
		
		
		
		$sql = 'insert into seguimientoauditoria (
					idProyecto, 
					idUsuario, 
					idCliente,
					idPersona,
					tipo, 
					observacion, 
					fechaCreacion, 
					ext
				) ';		
		$sql .= 'values ('.$varMiId.', '.$_SESSION["id"].', '.$idClienteOk.',"'.soloNumeros($_POST["Persona"]).'"  ,"'.soloNumeros($_POST["tipo"]).'", "'.eliminarInvalidos($_POST["observacion"]).'", NOW(), "'.$ext.'")';

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
		
	}
}

?>
<form method="post" enctype="multipart/form-data" name="form1" id="form1" action="pop_up.php?doc=pop_seguimientoauditoria&id=<?=$varMiId; ?>&rtn=<?=$_GET["rtn"]; ?>" class="form">
	
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
        <label class="form-label" for="Persona">Persona</label>
        <select name="Persona" class="form-control">
			<?
			/*
			*	TRAEMOS EL PERSONAL
			*/
			
			
			$sql = "select a.persona, b.nombres from (\n"
			. "SELECT `idPersonaUno` as persona FROM `proyecto_auditoria` WHERE `id`=".$varMiId."\n"		
			. "union \n"		
			. "SELECT `idPersonaDos` as persona FROM `proyecto_auditoria` WHERE `id`=".$varMiId."\n"		
			. "union\n"		
			. "SELECT `idPersonaTres` as persona FROM `proyecto_auditoria` WHERE `id`=".$varMiId."\n"		
			. "    ) a left join (SELECT * from personal)b on b.cc=a.persona";


			$PSN1->query($sql);
			$numero=$PSN1->num_rows();
			if($numero > 0)
			{
				while($PSN1->next_record())
				{
					?><option value="<?=$PSN1->f('persona'); ?>" <?
					if(soloNumeros($_POST["Persona"]) == $PSN1->f('persona'))
					{
						?>selected="selected"<?
					}
					?>><?=$PSN1->f('nombres'); ?></option><?
				}
			}
			?>
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
	if(confirm("Esta accion insertara una observacion al seguimiento de la auditoria seleccionada, esta seguro que desea continuar?"))
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

function cerrarYRefrescar(){
	var destino = 'index.php?doc=seguimiento_auditoria&id=<?=$varMiId; ?>';
	try {
		if(window.top && window.top !== window) {
			window.top.postMessage({ type: 'cotizacion-modal-close-refresh', url: destino }, '*');
			return;
		}
	} catch (e) {}
	try {
		if(window.parent && window.parent !== window && typeof window.parent.closeCotizacionModal === 'function') {
			window.parent.closeCotizacionModal();
			window.parent.location.reload();
			return;
		}
	} catch (e) {}
	try {
		if(window.opener && window.opener.parent) {
			window.opener.parent.location.reload();
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