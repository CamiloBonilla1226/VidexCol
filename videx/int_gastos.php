<?php
if(isset($_GET["id"]))
{
	$varMiIdOs = $_GET["id"];
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
$sql = "SELECT cotizacion.id, cotizacion.valorCotizado, cotizacion.valorCobrado";
$sql.=" FROM cotizacion ";
$sql.=" LEFT JOIN usuario as cliente ON cliente.id = cotizacion.idCliente";
$sql.=" WHERE cotizacion.id = ".$varMiIdOs;
//
$PSN1->query($sql);
$numero=$PSN1->num_rows();
if($numero == 0)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}else
{
	if($PSN1->next_record()){
		$valorCotizado = $PSN1->f('valorCotizado');
		$valorCobrado = $PSN1->f('valorCobrado');
	}
}
//
//	FIN COMPROBACION
//

/*
*	TRAEMOS LOS RECURSOS
DATE_SUB( observacion.fechaCreacion, INTERVAL 0 MINUTE )
*/
$sql = "SELECT usuario.*, gastos.idCotizacion, gastos.fechaCreacion as fechaCreacion2, gastos.observacion, gastos.fechaCreacion, gastos.id as idObservacion, gastos.ext, gastos.valor, gastos.idUsuario as idUsuario2, categorias.descripcion as tipoDescripcion ";
$sql.=" FROM usuario, gastos ";
$sql.=" LEFT JOIN  categorias ON categorias.id = gastos.tipo";
$sql.=" WHERE gastos.idUsuario = usuario.id and gastos.idCotizacion = ".$varMiIdOs;
$sql.=" ORDER BY gastos.fechaCreacion ASC";

$PSN1->query($sql);
$numero=$PSN1->num_rows();

?><table width="100%" border="0" cellspacing="2" cellpadding="2"  align="center" class="seguimientos">
<tbody>
<?
if($numero > 0)
{
	$contador = 0;
	while($PSN1->next_record())
	{
		$varIdRecurso = $PSN1->f('id');
		$varIdSeguimiento = $PSN1->f('idObservacion');
		$varIdCotizacion = $PSN1->f('idCotizacion');
		$varValor = $PSN1->f('valor');
		$totalValor += $varValor;
		$varExt = $PSN1->f('ext');
		$tipoDescripcion = $PSN1->f('tipoDescripcion');
		
		$varObservacionObservacion = $PSN1->f('observacion');
		$varFechaObservacion = $PSN1->f('fechaCreacion2');
		$varNombreObservacion = $PSN1->f('nombre');
		$idUsuario2 = $PSN1->f('idUsuario2');
		$varExiste = $PSN1->f('idReq');
		?><tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>> 
			<td rowspan="2"><a href="javascript:cotizacionEditarGasto(<?=$varIdSeguimiento; ?>);void(0);"><?
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
				if(file_exists("images/usuarios/".$idUsuario2.".jpg"))
				{
					?><img src="images/usuarios/<?=$idUsuario2;?>.jpg" align="middle" width="40" height="40"><?
				}
				else
				{
					?><img src="images/consultores/desconocido.jpg" align="middle" width="40" height="40"><?
				}	
			}
				?></a></td>
			<td><i><?=$varNombreObservacion." - ".date("d-m-Y H:i a", strtotime($varFechaObservacion)); ?> <strong><?=$tipoDescripcion; ?> | $<?=number_format($varValor, 0, "," , "."); ?></strong></i> <i>[<a href="javascript:cotizacionEditarGasto(<?=$varIdSeguimiento; ?>);void(0);">editar</a>]</i></td>
		</tr>
		<tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>> 
			<td><?=$varObservacionObservacion; ?><?

			//Compruebo si las características del archivo son las que deseo 25797
			$pathArchivo = "archivos/abn";

			//
			//		
			if(file_exists($pathArchivo.$varIdSeguimiento.".".$varExt))
			{
				?><br /><a href="<?=$pathArchivo; ?><?=$varIdSeguimiento; ?>.<?=$varExt; ?>" target="_blank"><img src="images/adjunto.jpg" align="middle" border="0"></a><i><strong>Contiene archivo adjunto:</strong> <a href="<?=$pathArchivo; ?><?=$varIdSeguimiento; ?>.<?=$varExt; ?>" target="_blank">Bajar Archivo</a></i><?
			}
			?></td>
		</tr>
		<?
		$contador++;
	}
	?><tr>
	<th colspan="2">SE HAN PAGADO $<?=number_format($totalValor, 0, "," , "."); ?></th>
	</tr><?
}
else
{
	?>
	<tr><td>Sin gastos.</td></tr>
	<?
}
?>
	</tbody>
</table>
<a name="final"></a>
<script language="javascript">
function cotizacionEditarGasto(idd){
	if(typeof window.asignarGasto === 'function'){
		window.asignarGasto(idd);
		return;
	}
	if(idd){
		window.open("pop_up.php?doc=pop_gasto_edit&id=<?=soloNumeros($_GET["id"]); ?>&abn="+idd, "gasto", "status=1, scrollbars=1, height=500, width=840");
		return;
	}
	window.open("pop_up.php?doc=pop_gasto&id=<?=soloNumeros($_GET["id"]); ?>", "gasto", "status=1, scrollbars=1, height=600, width=840");
}

	
window.scrollTo(0, 9999999);
</script>