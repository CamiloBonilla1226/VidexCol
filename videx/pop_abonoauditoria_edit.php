<?php
//Id de estudiante a partir del 20 de Marzo de 2017 - 25797
if(isset($_GET["id"]) && isset($_GET["abn"]))
{
	$varMiId = soloNumeros($_GET["id"]);
	$varMiAbn = soloNumeros($_GET["abn"]);
}
else
{
	die("Debe especificarse un ID de Solicitud.");
}

// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;


//
// COMPROBAMOS QUE EL USUARIO PERTENEZCA A ESTE REPORTE O SEA LIDER/ADMINISTRADOR
//
$sql = "SELECT proyecto_auditoria.id, proyecto_auditoria.estado, proyecto_auditoria.id_prospecto, proyecto_auditoria.nomb_proyecto, abonosauditoria.tipo, abonosauditoria.observacion, abonosauditoria.fechaCreacion, abonosauditoria.valor,abonosauditoria.idPersona";
$sql.=" FROM proyecto_auditoria, abonosauditoria ";
//$sql.=" LEFT JOIN usuario as cliente ON cliente.id = estudiante.idColegio";
$sql.=" WHERE proyecto_auditoria.id = ".$varMiId." AND abonosauditoria.idProyecto = proyecto_auditoria.id AND abonosauditoria.id = '".$varMiAbn."'";
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
		$idPersonas = $PSN1->f('idPersona');

		$fechaCreacion = $PSN1->f('fechaCreacion');
		$abonoTipo = $PSN1->f('tipo');
		$abonoValor = $PSN1->f('valor');
		$abonoObservacion = $PSN1->f('observacion');

	}
}
//
//	FIN COMPROBACION
//
	if($_SESSION["acceso_solo_lectura"] == 1){
		?><div class="row">
			<h5 class="alert alert-danger text-center"><strong>Atención:</strong> Su usuario es de solo lectura, no puede realizar modificaciones.</h5>
		</div><?
		exit;
	}
	//

// Array que nos servira para ir llevando cuenta de los requerimientos.
$arrayRequerimientos = array();
if(isset($_POST["funcion"]))
{
	if($_POST["funcion"] == "updateAbono" && eliminarInvalidos($_POST["valor"]) != "")
	{
		
		
		$sql = 'UPDATE abonosauditoria SET 
					tipo = "'.soloNumeros($_POST["tipo"]).'", 
					valor = "'.soloNumeros($_POST["valor"]).'",
					fechaCreacion = "'.eliminarInvalidos($_POST["fechaCreacion"]).'",
					idPersona ="'.soloNumeros($_POST["Persona"]).'", 
					observacion = "'.eliminarInvalidos($_POST["observacion"]).'" 
				WHERE 
					id = "'.$varMiAbn.'"
				';		
		$ultimoQuery = $PSN1->query($sql);		
		$varExito = 1;

		
	}
}

if($varExito != 1){	
?><form method="post" enctype="multipart/form-data" name="form1" id="form1" action="pop_up.php?doc=pop_abonoauditoria_edit&id=<?=$varMiId; ?>&abn=<?=$varMiAbn ; ?>" class="form">
	
    <div class="form-group">
        <h3 class="text-center well">.EDITAR ABONO.</h3>
    </div>

    <div class="form-group">
        <label class="form-label" for="tipo">Tipo</label>
        <select name="tipo" class="form-control">
		<?
		/*
		*	TRAEMOS LOS TIPOS DE ABONO
		*/
		$sql = "SELECT * ";
		$sql.=" FROM categorias ";
		$sql.=" WHERE idSec = 22 ORDER BY descripcion asc";
		
		$PSN1->query($sql);
		$numero=$PSN1->num_rows();
		if($numero > 0)
		{
			while($PSN1->next_record())
			{
				?><option value="<?=$PSN1->f('id'); ?>" <?
				if($abonoTipo == $PSN1->f('id'))
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
					if($idPersonas == $PSN1->f('persona'))
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
        <label class="form-label" for="fechaCreacion">Fecha del abono</label>
        <input name="fechaCreacion" type="date"  placeholder="AAAA-MM-DD" value="<?=$fechaCreacion; ?>" class="form-control" /></div>
	</div>

    <div class="form-group">
        <label class="form-label" for="valor">Abono</label>
        <input type="number" name="valor" id="valor" value="<?=$abonoValor; ?>" /></div>
	</div>
	
    <div class="form-group">
        <label class="form-label" for="tipo">Observaci&oacute;n</label>
        <textarea name="observacion" id="observacion" cols="80" rows="5" class="form-control"><?=$abonoObservacion; ?></textarea>
	</div>

	<?php render_archivo_adjuntado('abn', $varMiAbn, 'Archivo adjunto'); ?>

    <div class="row text-center">
        <input type="hidden" name="funcion" id="funcion" value="" />
        <input type="submit" value="Guardar cambios" class="btn btn-success" /> <a href="javascript:window.close();void(0)" class="btn btn-danger">Cerrar</a>
    </div>


</form><script language="javascript">
function generarForm(){
	if(confirm("Esta accion actualizara el abono del proyecto de auditoria seleccionada, esta seguro que desea continuar?"))
	{
		if(document.getElementById('valor').value != "")
		{
			document.getElementById('funcion').value = "updateAbono";
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
</script><?
}
else
{
	?><script language="javascript">
	function init(){
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
</script><?
}
?>