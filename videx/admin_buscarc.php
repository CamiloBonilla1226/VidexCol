<?php
if($_SESSION["perfil"] != 1 && $_SESSION["perfil"] != 2)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

/*
*	$PSN = new DBbase_Sql;
*/
// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;
// Array que nos servira para ir llevando cuenta de los requerimientos.
if(isset($_GET["del"]))
{
	$sql = 'delete from usuario where id = "'.$_GET["del"].'"';
	$ultimoQuery = $PSN1->query($sql);
	$varExitoDEL = 1;
}


/*
*	TRAEMOS LOS CLIENTES.
*/
$sql = "SELECT usuario.*, sucursales.nombre as nombreSucursal, creador.nombre as nombreCreador ";
$sql.=" FROM usuario left join usuario as creador ON creador.id = usuario.id";
$sql.=" LEFT JOIN sucursales  ON sucursales.id = usuario.idSucursal";
//
if($_SESSION["superusuario"] != 1)
{
	$sql.=" WHERE usuario.tipo = 4 AND usuario.idSucursal = ".$_SESSION["idSucursal"];
}
else
{
	$sql.=" WHERE usuario.tipo = 4 ";
}
$sql.=" ORDER BY nombre ASC";


$PSN1->query($sql);
$numero=$PSN1->num_rows();
?>
<script language="javascript">
function borrarRegistro(registro){
		if(confirm("Esta accion BORRARA el CLIENTE de el sistema, ¿esta seguro que desea continuar?"))
		{
			if(confirm("Recuerde que si el CLIENTE tiene Ordenes de Servicio activas esto puede causar perdida de integridad en los datos, esta seguro que desea eliminar este CLIENTE?"))
			{
				window.location.href = "index.php?doc=admin_buscarc&del="+registro;
			}
		}
}
function init(){
	<?
	if($varExitoDEL == 1)
	{
		?>alert("Se ha BORRADO correctamente el CLIENTE, espere mientras es dirigido de nuevo a la busqueda.");
		window.location.href = "index.php?doc=admin_buscarc";<?
	}
	?>
}
window.onload = function(){
	init();
}
</script>
<table width="100%" border="0" cellspacing="0" cellpadding="2"  align="center">
<thead>
	<tr> 
	<th colspan="<?
		if($_SESSION["superusuario"] == 1)
		{
			?>11<?
		}		
		else
		{
			?>10<?
		}
		?>">.CLIENTES.</th>
	</tr>
	<tr> 
		<th>&nbsp;</th>
		<th>Id</th>
		<?
		if($_SESSION["superusuario"] == 1)
		{
			?><th>Sucursal</th><?
		}		
		?>
		<th>Nombre</th>
		<th>Razon Social</th>
		<th>NIT</th>
		<th>Telefono</th>
		<th>E-Mail</th>
		<th>Quien Lo Creo?</th>
		<th>Estado</th>
		<th>Opciones</th>
	</tr>
</thead>
<tbody>
<?
if($numero > 0)
{
	$contador = 0;
	while($PSN1->next_record())
	{
		//Solo si no se ha modificado ya el formulario.
		$id = $PSN1->f('id');
		$nombre = $PSN1->f('nombre');
		$razon = $PSN1->f('razon');
		$identificacion = $PSN1->f('identificacion');
		$tipoIdentificacion = $PSN1->f('tipoIdentificacion');
		$tipoContribuyente = $PSN1->f('tipoContribuyente');
		$regimenDian = $PSN1->f('regimenDian');
		$categoriaDian = $PSN1->f('categoriaDian');
		$clasificacionIndustria = $PSN1->f('clasificacionIndustria');
		$representanteLegal = $PSN1->f('representanteLegal');
		$pais = $PSN1->f('pais');
		$departamento = $PSN1->f('departamento');
		$ciudad = $PSN1->f('ciudad');
		$direccion = $PSN1->f('direccion');
		$telefono1 = $PSN1->f('telefono1');
		$telefono2 = $PSN1->f('telefono2');
		$celular = $PSN1->f('celular');
		$email = $PSN1->f('email');
		$url = $PSN1->f('url');
		$observaciones = $PSN1->f('observaciones');
		$login = $PSN1->f('login');
		$password = $PSN1->f('password');
		$prefijo = $PSN1->f('prefijo');
		$consecutivo = $PSN1->f('consecutivo');
		$nombreCreador = $PSN1->f('nombreCreador');
		$nombreSucursal = $PSN1->f('nombreSucursal');
		$inactivo = $PSN1->f('inactivo');

		?><tr <? 
		
		if($contador%2==0)
		{
			if($inactivo == 1)
			{
				?>bgcolor="#CC0000"<? 
			}
			else
			{
				?>bgcolor="#EEEEEE"<? 
			}
		}
		else
		{
			if($inactivo == 1)
			{
				?>bgcolor="#FF0000"<? 
			}
		}
		?>>
		<td><?
		if(file_exists("images/clientes/".$id.".jpg"))
		{
			?><img src="images/clientes/<?=$id;?>.jpg" align="middle" width="60px" height="25px" style="border-color:#0000FF"><?
		}
		else
		{
			echo "&nbsp;";
		}	
		?></td>
        <td><a href="index.php?doc=admin_usu2&id=<?=$id; ?>"><?=str_pad($id, 6, "0", STR_PAD_LEFT); ?></a></td>
		<?
		if($_SESSION["superusuario"] == 1)
		{
			?><td><a href="index.php?doc=admin_usu2&id=<?=$id; ?>"><?=$nombreSucursal; ?></a></td><?
		}
		?>
		<td><a href="index.php?doc=admin_usu2&id=<?=$id; ?>"><?=$nombre; ?></a></td>
		<td><?=$razon; ?></td>
		<td><?=$identificacion; ?></td>
		<td><?=$telefono1; ?></td>
		<td><?=$email; ?></td>
        <td><?=$nombreCreador; ?></td>
        <td><?
        if($inactivo == 1)
		{
			echo "Inactivo";
		}
		else
		{
			echo "Activo";
		}
		
		?></td>
		<td>[<a href="index.php?doc=os_buscarl&colegio=<?=$id; ?>">DEUDORES</a>] <!--[<a href="javascript:borrarRegistro('<?=$id; ?>');void(0);">BORRAR</a>]//--></td>
		</tr>
		<?
		$contador++;
	}
}
?>
</tbody>
</table>