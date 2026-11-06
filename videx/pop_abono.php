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
$sql = "SELECT cotizacion.id, cotizacion.estado, cotizacion.idCliente, cotizacion.contactoNombre";
$sql.=" FROM cotizacion ";
//$sql.=" LEFT JOIN usuario as cliente ON cliente.id = estudiante.idColegio";
$sql.=" WHERE cotizacion.id = ".$varMiId;
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

	if($_POST["funcion"] == "insertarAbono" && eliminarInvalidos($_POST["valor"]) != "")
	{
		/*
		*	DEBEMOS OBTENER VALORES DE LOS CORREOS DEL AFILIADO
		*/
		/*$sqlc = "SELECT usuario.id, usuario.prefijo, usuario.email, estudiante.id as consecutivo ";
		$sqlc.=" FROM estudiante, usuario ";
		$sqlc.=" WHERE usuario.id = estudiante.idColegio and estudiante.id = ".$varMiId;
		$PSN1->query($sqlc);
		$numero=$PSN1->num_rows();
		if($numero > 0)
		{
			$PSN1->next_record();
			$prefijo = $PSN1->f('prefijo');
			$consecutivo = $PSN1->f('consecutivo');
			$idcliente = $PSN1->f('id');
			if(trim($PSN1->f('email')) != "")
			{
				$correosEnviar[] = $PSN1->f('email');
			}

			$sqlc = "SELECT email ";
			$sqlc.=" FROM usuario ";
			$sqlc.=" WHERE lider = 1 AND idColegio = ".$idcliente;
			$PSN1->query($sqlc);
			$numero=$PSN1->num_rows();
			if($numero > 0)
			{
				while($PSN1->next_record())
				{
					if(trim($PSN1->f('email')) != "")
					{
						$correosEnviar[] = $PSN1->f('email');
					}
				}
			}
		}
		*/

		$nombre_archivo = $_FILES['archivo']['name'];
		$tipo_archivo = $_FILES['archivo']['type'];
		$tamano_archivo = $_FILES['archivo']['size'];
		$ext = extension_archivo($nombre_archivo);		
		
		/*echo "<strong>Resultado archivo: </strong>";
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
		
		$sql = 'insert into abonos (
					idCotizacion, 
					idUsuario, 
					idCliente,
					tipo, 
					valor,
					observacion, 
					fechaCreacion, 
					fechaAuditoria,
					ext
				) ';		
		$sql .= 'values ('.$varMiId.', '.$_SESSION["id"].', '.$idClienteOk.', "'.soloNumeros($_POST["tipo"]).'", "'.soloNumeros($_POST["valor"]).'", "'.eliminarInvalidos($_POST["observacion"]).'", "'.eliminarInvalidos($_POST["fechaCreacion"]).'", NOW(), "'.$ext.'")';

		$ultimoQuery = $PSN1->query($sql);		
        $ultimoId =  $PSN1->ultimoId();
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
		if(move_uploaded_file($_FILES['archivo']['tmp_name'], "archivos/abn".$ultimoId.".".$ext))
		{
			echo "Se movio...";
		}
		else
		{
			echo "NO se movio...";
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

?><div class="container">
<form method="post" enctype="multipart/form-data" name="form1" id="form1" action="pop_up.php?doc=pop_abono&id=<?=$varMiId; ?>" class="form">
	
    <div class="form-group">
        <h3 class="text-center well">.INSERTAR ABONO.</h3>
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
        <label class="form-label" for="fechaCreacion">Fecha del abono</label>
        <input name="fechaCreacion" type="date"  placeholder="AAAA-MM-DD" value="<?
			if(!isset($_POST['fechaCreacion'])){
				echo date("Y-m-d");
			}
			else
			{
				eliminarInvalidos($_POST['fechaCreacion']); 
			}

		?>" class="form-control" /></div>
	</div>

		
    <div class="form-group">
        <label class="form-label" for="valor">Valor del abono</label>
        <input type="number" name="valor" id="valor" class="form-control" />

		
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
</div>	
<script language="javascript">
function generarForm(){
	if(confirm("Esta accion insertara un abono a la cotizacion seleccionada, esta seguro que desea continuar?"))
	{
		if(document.getElementById('valor').value != "")
		{
			document.getElementById('funcion').value = "insertarAbono";
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