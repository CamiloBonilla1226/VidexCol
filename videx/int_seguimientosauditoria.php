<?php

if(isset($_GET["id"]))
{
	$varMiIdOs = $_GET["id"];
	
}
else
{
	die("Debe especificarse un ID de solicitud.");
}


// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;
// Array que nos servira para ir llevando cuenta de los requerimientos.
$arrayRequerimientos = array();



//
// COMPROBAMOS QUE EL USUARIO PERTENEZCA A ESTE REPORTE O SEA LIDER/ADMINISTRADOR
//
$sql = "SELECT proyecto_auditoria.id";
$sql.=" FROM proyecto_auditoria ";
$sql.=" LEFT JOIN usuario as cliente ON cliente.id = proyecto_auditoria.id_prospecto";
$sql.=" WHERE proyecto_auditoria.id = ".$varMiIdOs;
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


//	TRAEMOS LOS RECURSOS
//DATE_SUB( observacion.fechaCreacion, INTERVAL 0 MINUTE )

$sql = "SELECT usuario.*, seguimientoauditoria.idProyecto, seguimientoauditoria.fechaCreacion as fechaCreacion2, seguimientoauditoria.observacion, seguimientoauditoria.fechaCreacion, seguimientoauditoria.id as idObservacion, seguimientoauditoria.ext, seguimientoauditoria.idCliente as idCliente2, seguimientoauditoria.idUsuario as idUsuario2,  personal.nombres as idPersona,categorias.descripcion as tipoDescripcion ";
$sql.=" FROM usuario, seguimientoauditoria ";
$sql.=" LEFT JOIN  categorias ON categorias.id = seguimientoauditoria.tipo";
$sql.=" LEFT JOIN  personal ON personal.cc = seguimientoauditoria.idPersona";
$sql.=" WHERE seguimientoauditoria.idUsuario = usuario.id and seguimientoauditoria.idProyecto = ".$varMiIdOs;
$sql.=" GROUP BY seguimientoauditoria.id ORDER BY seguimientoauditoria.fechaCreacion ASC";
//echo $sql;
$PSN1->query($sql);
$numero=$PSN1->num_rows();


?>
<table width="100%" border="0" cellspacing="2" cellpadding="2"  align="center" class="seguimientos">
<tbody>
<?
if($numero > 0)
{
	$contador = 0;
	while($PSN1->next_record())
	{
		$varIdRecurso = $PSN1->f('id');
		$varIdSeguimiento = $PSN1->f('idObservacion');
		$varidProyecto = $PSN1->f('idProyecto');
		$varExt = $PSN1->f('ext');
		$tipoDescripcion = $PSN1->f('tipoDescripcion');
		$idPersona = $PSN1->f('idPersona');
		
		$varObservacionObservacion = $PSN1->f('observacion');
		$varFechaObservacion = $PSN1->f('fechaCreacion2');
		$varNombreObservacion = $PSN1->f('nombre');
		$varIdUsuario = $PSN1->f('idUsuario2');
		$varExiste = $PSN1->f('idReq');
		?><tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>> 
			<td rowspan="2"><a href="javascript:asignarObservacion(<?=$varIdSeguimiento; ?>);void(0);"><?
			if($varColegio2 > 0)
			{
				if(file_exists("images/clientes/".$varColegio2.".jpg"))
				{
					?><img src="images/clientes/<?=$varColegio2;?>.jpg" align="middle" width="40" height="40"><?
				}
				else
				{
					?><img src="images/clientes/desconocido.jpg" align="middle" width="40" height="40"><?
				}	
			}
			else
			{
				if(file_exists("images/usuarios/".$varIdUsuario.".jpg"))
				{
					?><img src="images/usuarios/<?=$varIdUsuario;?>.jpg" align="middle" width="40" height="40"><?
				}
				else
				{
					?><img src="images/consultores/desconocido.jpg" align="middle" width="40" height="40"><?
				}	 
			}
			?></a></td>
			<td><i><?=$varNombreObservacion." - ".date("d-m-Y H:i a", strtotime($varFechaObservacion)); ?> <strong><?=$tipoDescripcion; ?></strong></i> <i>[<a href="javascript:asignarObservacion(<?=$varIdSeguimiento; ?>);void(0);">editar</a>]</i></td>
		</tr>
		<tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>> 
			<td>
			PERSONAL: <?= $idPersona ?>
			</br>
			<?=$varObservacionObservacion; ?><?

			//Compruebo si las características del archivo son las que deseo 25797
			if($varIdEstudiante > 25797){
				$pathArchivo = "archivos2/obs";
			}
			else
			{
				$pathArchivo = "archivos/obs";
			}			

			if(file_exists($pathArchivo.$varIdSeguimiento.".".$varExt))
			{
				?><br /><a href="<?=$pathArchivo; ?><?=$varIdSeguimiento; ?>.<?=$varExt; ?>" target="_blank"><img src="images/adjunto.jpg" align="middle" border="0"></a><i><strong>Contiene archivo adjunto:</strong> <a href="<?=$pathArchivo; ?><?=$varIdSeguimiento; ?>.<?=$varExt; ?>" target="_blank">Bajar Archivo</a></i><?
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
<a name="final"></a>
<script language="javascript">
function asignarObservacion(idd){
	window.open("pop_up.php?doc=pop_seguimientoauditoria_edit&id=<?=soloNumeros($_GET["id"]); ?>&seg="+idd, "seguimientoauditoria", "status=1, scrollbars=1, height=500, width=840");
}

window.scrollTo(0, 9999999);
</script>