<?
/*
*	LOGUEO
*/
//Si es un usuario externo o cliente o proveedor NO mostrar.
if($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 4 || $_SESSION["perfil"] == 160)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

if(!isset($_GET["opc"]))
{
	$opc = 2;
}
else
{
	$opc = eliminarInvalidos($_GET["opc"]);
}

if(!isset($_GET["activos"]))
{
	$activos = 0;
}
else{
	$activos = soloNumeros($_GET["activos"]);
}


//
//	PARAMETROS CONFIGURABLES
//
$PSN = new DBbase_Sql;
$PSN1 = new DBbase_Sql;


$busquedaFechaIni = date("Y-m-01");
if(isset($_REQUEST["fechaInicial"]) && soloNumeros($_REQUEST["fechaInicial"]) != ""){
	$busquedaFechaIni = eliminarInvalidos($_REQUEST["fechaInicial"]);
}
$busquedaFechaFin = date("Y-m-d");
if(isset($_REQUEST["fechaFinal"]) && soloNumeros($_REQUEST["fechaFinal"]) != ""){
	$busquedaFechaFin = eliminarInvalidos($_REQUEST["fechaFinal"]);
}

$idCliente = intval($_REQUEST["idCliente"]);

$PSNTEMP = new DBbase_Sql;
$sql= " SELECT cliente.id as id, cliente.nombre  as nombre FROM cliente where id = ".$idCliente;
$PSNTEMP->query($sql);
if($PSNTEMP->num_rows() > 0)
{
	$PSNTEMP->next_record();
	$nom_cliente = $PSNTEMP->f('nombre');
}
else{
	$nom_cliente = "TODOS LOS PROSPECTOS";
}

?>
<h3>Mostrando costos y gastos del prospecto <?=utf8_decode($nom_cliente); ?> entre el <?=$busquedaFechaIni; ?> y el <?=$busquedaFechaFin; ?>.</h3>
<br />
<br />
<?
//
//	GASTOS DE AUDITORIA
//
$sql = "SELECT personal.nombres as Nombres, gastosauditoria.*, categorias.descripcion as tipo_gasto ";
$sql .= " FROM gastosauditoria";
$sql .= " INNER JOIN personal ON gastosauditoria.idPersona = personal.cc";
$sql .= " LEFT JOIN categorias ON categorias.id = gastosauditoria.tipo";
$sql .= " WHERE gastosauditoria.fechaCreacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
if($idCliente > 0){
	$sql .= " AND gastosauditoria.idCliente = " .$idCliente;
}
$sql .= " ORDER BY gastosauditoria.fechaCreacion ASC";
//
$PSN->query($sql);
$num=$PSN->num_rows();
?>

<table>
<tr>
	<th colspan="7" bgcolor="#FF898B">.GASTOS DE AUDITORIA - <?=$num; ?> REGISTROS ENCONTRADOS</th>
</tr>
				 
<tr>
	<th align="center">No</th>
	<th align="center">ID</th>
	<th align="center">Tipo</th>
	<th align="center">Observaci&oacute;n</th>
	<th align="center">Fecha</th>
	<th align="center">Valor</th>
	<th align="center">Persona</th>
</tr><?
	if($num > 0)
	{
		$contador = 1;
		$total = 0;
		while($PSN->next_record())
		{
			$total += $PSN->f('valor');
			?>
			<tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>>
				<td><?=$contador; ?></td>
				<td><?=$PSN->f('id');?></td>
				<td align="center"><?=utf8_decode($PSN->f('tipo_gasto'));?></td>
				<td><?=utf8_decode($PSN->f('observacion'));?></td>
				<td><?=$PSN->f('fechaCreacion');?></td>
				<td align="right"><?=$PSN->f('valor');?></td>
				<td><?=utf8_decode($PSN->f('Nombres'));?></td>
			</tr>
			<?
			$contador++;
		}
		$total_gastos_audit = $total;
		?>
		<tr>
			<td colspan="5" align="right" bgcolor="#FF898B"><strong>Total gastos de auditoria:</strong></td>
			<td align="right" bgcolor="#FF898B"><strong><?=$total; ?></strong></td>
		</tr>
		<?
	}		
	else
	{
		?><tr>
		  <td colspan="10" align="center">.No hay registros de GASTOS DE AUDITORIA.</td>
		</tr><?
	}	
	?>  
</table>

<br />
<br />
<?
//
//	GASTOS
//
$sql = "SELECT gastos.*, categorias.descripcion as tipo_gasto ";
$sql .= " FROM gastos";
$sql .= " LEFT JOIN categorias ON categorias.id = gastos.tipo";
$sql .= " WHERE gastos.fechaCreacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
if($idCliente > 0){
	$sql .= " AND gastos.idCliente = " .$idCliente;
}
$sql .= " ORDER BY gastos.fechaCreacion ASC";
//
//$sql.=" GROUP BY personal.nombres ";
//
$PSN->query($sql);
$num=$PSN->num_rows();
?>

<table>
<tr>
	<th colspan="6" bgcolor="#FF898B">.GASTOS - <?=$num; ?> REGISTROS ENCONTRADOS</th>
</tr>
				 
<tr>
	<th align="center">No</th>
	<th align="center">ID</th>
	<th align="center">Tipo</th>
	<th align="center">Observaci&oacute;n</th>
	<th align="center">Fecha</th>
	<th align="center">Valor</th>
</tr><?
	if($num > 0)
	{
		$contador = 1;
		$total = 0;
		while($PSN->next_record())
		{
			$total += $PSN->f('valor');
			?>
			<tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>>
				<td><?=$contador; ?></td>
				<td><?=$PSN->f('id');?></td>
				<td align="center"><?=utf8_decode($PSN->f('tipo_gasto'));?></td>
				<td><?=utf8_decode($PSN->f('observacion'));?></td>
				<td><?=$PSN->f('fechaCreacion');?></td>
				<td align="right"><?=$PSN->f('valor');?></td>
			</tr>
			<?
			$contador++;
		}
		$total_gastos = $total;
		?>
		<tr>
			<td colspan="5" align="right" bgcolor="#FF898B"><strong>Total gastos:</strong></td>
			<td align="right" bgcolor="#FF898B"><strong><?=$total; ?></strong></td>
		</tr>
		<?
	}		
	else
	{
		?><tr>
		  <td colspan="10" align="center">.No hay registros de GASTOS.</td>
		</tr><?
	}	
	?>  
</table>

<br />
<br />

<?
//
//	ABONOS DE AUDITORIA
//
$sql = "SELECT personal.nombres as Nombres, abonosauditoria.*, categorias.descripcion as tipo_abono ";
$sql .= " FROM abonosauditoria";
$sql .= " INNER JOIN personal ON abonosauditoria.idPersona = personal.cc";
$sql .= " LEFT JOIN categorias ON categorias.id = abonosauditoria.tipo";
$sql .= " WHERE abonosauditoria.fechaCreacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
if($idCliente > 0){
	$sql .= " AND abonosauditoria.idCliente = " .$idCliente;
}
$sql .= " ORDER BY abonosauditoria.fechaCreacion ASC";

//
//$sql.=" GROUP BY personal.nombres ";
//
$PSN->query($sql);
$num=$PSN->num_rows();
?>

<table>
<tr>
	<th colspan="6" bgcolor="#89FF9D">.ABONOS DE AUDITORIA - <?=$num; ?> REGISTROS ENCONTRADOS</th>
</tr>
				 
<tr>
	<th align="center">No</th>
	<th align="center">ID</th>
	<th align="center">Tipo</th>
	<th align="center">Observaci&oacute;n</th>
	<th align="center">Fecha</th>
	<th align="center">Valor</th>
</tr><?
	if($num > 0)
	{
		$contador = 1;
		$total = 0;
		while($PSN->next_record())
		{
			$total += $PSN->f('valor');
			?>
			<tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>>
				<td><?=$contador; ?></td>
				<td><?=$PSN->f('id');?></td>
				<td align="center"><?=utf8_decode($PSN->f('tipo_abono'));?></td>
				<td><?=utf8_decode($PSN->f('observacion'));?></td>
				<td><?=$PSN->f('fechaCreacion');?></td>
				<td align="right"><?=$PSN->f('valor');?></td>
			</tr>
			<?
			$contador++;
		}
		$total_abonos_audit = $total;
		?>
		<tr>
			<td colspan="5" align="right" bgcolor="#89FF9D"><strong>Total gastos:</strong></td>
			<td align="right" bgcolor="#89FF9D"><strong><?=$total; ?></strong></td>
		</tr>
		<?		
	}		
	else
	{
		?><tr>
		  <td colspan="10" align="center">.No hay registros de ABONOS DE AUDITORIA.</td>
		</tr><?
	}	
	?>  
</table>
<br />
<br />

<?
//
//	ABONOS RECIBIDOS
//
$sql = "SELECT abonos.*, categorias.descripcion as tipo_abono ";
$sql .= " FROM abonos";
$sql .= " LEFT JOIN categorias ON categorias.id = abonos.tipo";
$sql .= " WHERE abonos.fechaCreacion BETWEEN '".$busquedaFechaIni."' AND '".$busquedaFechaFin."'";
if($idCliente > 0){
	$sql .= " AND abonos.idCliente = " .$idCliente;
}
$sql .= " ORDER BY abonos.fechaCreacion ASC";

//
//$sql.=" GROUP BY personal.nombres ";
//
$PSN->query($sql);
$num=$PSN->num_rows();
?>

<table>
<tr>
	<th colspan="6">.ABONOS - <?=$total_registros; ?> REGISTROS ENCONTRADOS</th>
</tr>
				 
<tr>
	<th align="center">No</th>
	<th align="center">ID</th>
	<th align="center">Tipo</th>
	<th align="center">Observaci&oacute;n</th>
	<th align="center">Fecha</th>
	<th align="center">Valor</th>
</tr><?
	if($num > 0)
	{
		$contador = 1;
		$total = 0;
		while($PSN->next_record())
		{
			$total += $PSN->f('valor');
			?>
			<tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>>
				<td><?=$contador; ?></td>
				<td><?=$PSN->f('id');?></td>
				<td align="center"><?=utf8_decode($PSN->f('tipo_abono'));?></td>
				<td><?=utf8_decode($PSN->f('observacion'));?></td>
				<td><?=$PSN->f('fechaCreacion');?></td>
				<td align="right"><?=$PSN->f('valor');?></td>
			</tr>
			<?
			$contador++;
		}
		$total_abonos = $total;
		?>
		<tr>
			<td colspan="5" align="right" bgcolor="#89FF9D"><strong>Total abonos:</strong></td>
			<td align="right" bgcolor="#89FF9D"><strong><?=$total; ?></strong></td>
		</tr>
		<?				
	}		
	else
	{
		?><tr>
		  <td colspan="10" align="center">.No hay registros de ABONOS.</td>
		</tr><?
	}	
	?>  
</table>
<br />
<br />
<table>
<tr>
	<th colspan="6" bgcolor="#FFE476">.RESULTADOS DEL EJERCICIO.</th>
</tr>
	<tr>
	<th colspan="5" align="center">Tipo</th>
	<th align="center">Valor total</th>
</tr>
<tr>
	<td colspan="5" bgcolor="#89FF9D">GASTOS</td><td bgcolor="#89FF9D"><?=$total_gastos_audit+$total_gastos; ?></td>
</tr>
<tr>
	<td colspan="5" bgcolor="#89FF9D">ABONOS</td><td bgcolor="#89FF9D"><?=$total_abonos_audit+$total_abonos; ?></td>
</tr>
<tr>	
	<td colspan="5" bgcolor="#FFE476">SALDO</td><td bgcolor="#FFE476"><?=$total_abonos+$total_abonos_audit-$total_gastos-$total_gastos_audit; ?></td>
</tr>	
<tr>	
	<?
	//
	$Utilidad = 0;
	$Perdida = 0;
	$DiffU = 0;
	$DiffP = 0;
	$TotalAbono = ($total_abonos+$total_abonos_audit);
	$TotalGasto = ($total_gastos+$total_gastos_audit);
	//
	$TA = number_format(($total_abonos+$total_abonos_audit), 0);
	$TG = number_format(($total_gastos+$total_gastos_audit), 0);
	$total = $TotalAbono - $TotalGasto;
	$Totales = 'Total Abono: $'.$TA.'  -  Total Gasto: $'.$TG;
	if ($total > 0){
		$Utilidad =  ($total * 100 ) / $TotalAbono;
		$label = "<td colspan='5'>UTILIDAD</td><td>". round($Utilidad, 2).'%</td>';
		$DiffU = $total;
		$colores = '\'blue\'';
	}else{
		$Perdida = ($total * (-1) * 100 ) / $TotalAbono;
		$label = "<td colspan='5'>PERDIDA</td><td>". round($Perdida, 2).'%</td>';
		$DiffP = $total*(-1);
		$colores = '\'red\'';
	}

	echo $label;
	?>
</tr>
</table>