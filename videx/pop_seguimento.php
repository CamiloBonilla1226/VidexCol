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

if($_SESSION["perfil"] != 4 && $_SESSION["perfil"] != 5 && $_SESSION["per_aval_observaciones_crear"] != 1)
{
	die("No esta autorizado para realizar observaciones.");
}

	if($_SESSION["acceso_solo_lectura"] == 1){
		?><div class="row">
			<h5 class="alert alert-danger text-center"><strong>Atención:</strong> Su usuario es de solo lectura, no puede realizar modificaciones.</h5>
		</div><?
		exit;
	}
	//


/*if($_SESSION["perfil"] != 2)
{
	die("No esta autorizado para estar aqui.");
}*/
// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;


//
// COMPROBAMOS QUE EL USUARIO PERTENEZCA A ESTE REPORTE O SEA LIDER/ADMINISTRADOR
//
$sql = "SELECT estudiante.id, estudiante.estado, estudiante.nombre_deudor";
$sql.=" FROM estudiante ";
$sql.=" LEFT JOIN usuario as colegio ON colegio.id = estudiante.idColegio";
$sql.=" WHERE estudiante.id = ".$varMiId;
//
if($_SESSION["perfil"] == 5)
{
	$sql .=" and estudiante.idColegio = ".$_SESSION["idColegio"];
}
else if ($_SESSION["perfil"] == 4)
{
	$sql .=" and estudiante.idColegio = ".$_SESSION["id"];
}
else if($_SESSION["superusuario"] != 1)
{
	$sql.=" AND colegio.idSucursal = ".$_SESSION["idSucursal"];
}

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
		$nombre_deudor = $PSN1->f('nombre_deudor');
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
		/*
		*	DEBEMOS OBTENER VALORES DE LOS CORREOS DEL AFILIADO
		*/
		$sqlc = "SELECT usuario.id, usuario.prefijo, usuario.email, estudiante.id as consecutivo ";
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

			/*
			*	Obtenemos correos de los lideres.
			*/
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

		$nombre_archivo = $_FILES['archivo']['name'];
		$tipo_archivo = $_FILES['archivo']['type'];
		$tamano_archivo = $_FILES['archivo']['size'];
		$ext = extension_archivo($nombre_archivo);
		
		
		if($_SESSION["superusuario"] == 1){
			echo "<strong>Error archivo: </strong>";
	
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
			echo $_FILES['archivo']['tmp_name'];
		}
		
		if($_SESSION["perfil"] == 4)
		{
			$sql = 'insert into observacion (idEstudiante, idUsuario, idColegio, observacion, fechaCreacion, ext) ';		
			$sql .= 'values ('.$varMiId.', '.$_SESSION["id"].', '.$_SESSION["id"].', "'.eliminarInvalidos($_POST["observacion"]).'", NOW(), "'.$ext.'")';
		}
		else
		{
			$sql = 'insert into observacion (idEstudiante, idUsuario, observacion, fechaCreacion, ext) ';		
			$sql .= 'values ('.$varMiId.', '.$_SESSION["id"].', "'.eliminarInvalidos($_POST["observacion"]).'", NOW(), "'.$ext.'")';
		}

		$ultimoQuery = $PSN1->query($sql);		
		$ultimoId = mysql_insert_id();
		$varExito = 1;

		if($_SESSION["perfil"] == 5 || $_SESSION["perfil"] == 4){
			/*
			*	CAMBIAMOS EL ESTADO SI ESTABAMOS ESPERANDO RESPUESTA DE PARTE DEL CLIENTE.
			*/
			$sqlc = "SELECT id ";
			$sqlc.=" FROM estudiante WHERE id = ".$varMiId;
			$sqlc.=" AND estado = 3";
			$PSN1->query($sqlc);
			$numero=$PSN1->num_rows();
			if($numero > 0)
			{
				$sql = "UPDATE estudiante SET ";
				$sql .= "estado = 4";
				$sql .= ",usuarioModificacion = '".$_SESSION["id"]."'";
				$sql .= ", fechaModificacion = NOW()";
				$sql .= " WHERE id = '".$varMiId."'";
				$PSN1->query($sql);
			}
		}

		
		//DEBEMOS ACTUALIZAR EL REPORTE PARA SABER QUIEN FUE LA ULTIMA PERSONA EN ESCRIBIR
		//YA SEA UNA MODIFICACIÓN A LA INFORMACIÓN, UNA OBSERVACIÓN, ETC.
		$sql = "UPDATE estudiante SET ";
		$sql .= "usuarioModificacion = '".$_SESSION["id"]."'";
		$sql .= ", fechaModificacion = NOW()";
		if(soloNumeros($_POST["respuestacliente"]) == 1 && $varEstado != 1 && $varEstado != 2){
			$sql .= ", estado = 3";
		}
		$sql .= " WHERE id = '".$varMiId."'";
		$PSN1->query($sql);
		//FIN ACTUALIZACIÓN DE REGISTROS
		//
	
		//Compruebo si las características del archivo son las que deseo 25797
		if($varMiId > 25797){
			if(move_uploaded_file($_FILES['archivo']['tmp_name'], "archivos2/obs".$ultimoId.".".$ext))
			{
			}
		}
		else
		{
			if(move_uploaded_file($_FILES['archivo']['tmp_name'], "archivos/obs".$ultimoId.".".$ext))
			{
			}
		}

		//
		//
		if(soloNumeros($_POST["respuestacliente"]) == 1 && $varEstado != 1 && $varEstado != 2){
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
		}
	}
}

?><form method="post" enctype="multipart/form-data" name="form1" id="form1" action="pop_up.php?doc=pop_observacion&id=<?=$varMiId; ?>&rtn=<?=$_GET["rtn"]; ?>">
<table width="80%" border="0" cellspacing="0" cellpadding="2"  align="center">
<thead>
	<tr> 
	<th colspan="2">.INSERTAR SEGUIMIENTO.</th>
	</tr>
</thead>
<tbody>
	<tr> 
		<td colspan="2"><textarea name="observacion" id="observacion" cols="80" rows="5"></textarea></td>
	</tr>
	<tr>
		<td><strong>Archivo Anexo:</strong></td>
		<td><input name="archivo" type="file" id="archivo" /></td>
	</tr>
    <?
	//Si es usuario interno y el estado NO es aprobado ni rechazado.
    if($_SESSION["perfil"] != 4 && $_SESSION["perfil"] != 5 && $_SESSION["per_aval_observaciones_crear"] == 1 && $varEstado != 1 && $varEstado != 2 && $varEstado != 5 && $varEstado != 6){
		?>
        <tr>
            <td colspan="2"><input type="checkbox" name="respuestacliente" id="respuestacliente" value="1" /><strong>Marque esta casilla para solicitar respuesta por parte del cliente lo cual cambiara el estado de la solicitud.</strong></td>
        </tr>
        <?
	}
	?>

    
	</tbody>
</table>
<input type="hidden" name="funcion" id="funcion" value="" />
<br />
<hr color="#0000FF" width="800px" />
<br />
<center><input type="button" name="button" onclick="generarForm()" value="Insertar Observacion" style="background-color:#FFFFFF;border-color:#0000FF;color:#000066;font-weight:bold"> </center>
</form><script language="javascript">
function generarForm(){
	if(confirm("Esta accion insertara una observacion a la solicitud seleccionada, Esta seguro que desea continuar?"))
	{
		if(document.getElementById('observacion').value != "")
		{
			document.getElementById('funcion').value = "insertarObservacion";
			document.getElementById('form1').submit();
		}
		else
		{
			alert("Debe escribir algo.");
		}
	}
}

function init(){
	document.getElementById('form1').onsubmit = function(){
			return false;
	}
	<?
	if($varExito == 1)
	{
		if($_SESSION["perfil"] == 2)
		{
			?>opener.location.href = 'index.php?doc=cotizacion&id=<?=$varMiId; ?>';<?
		}
		else
		{
			?>opener.location.href = 'index.php?doc=cotizacion<?=$_GET["rtn"]; ?>&id=<?=$varMiId; ?>';<?
		}
		?>window.close();<?
	}
	?>
}

window.onload = function(){
	init();
}
</script>