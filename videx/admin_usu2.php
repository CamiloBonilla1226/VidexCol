<?php
if($_SESSION["perfil"] != 1)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

if(isset($_GET["id"]))
{
	$varMiId = $_GET["id"];
}
else
{
	die("Debe especificarse un ID.");
}
/*
*	$PSN = new DBbase_Sql;
*/
// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;
$PSN3 = new DBbase_Sql;
//Objeto de EDUCATIVO
$PSN_Edu = new DBbase_SqlC;
//Objeto de REAL
$PSN_Real = new DBbase_SqlD;
// Array que nos servira para ir llevando cuenta de los requerimientos.
$arrayRequerimientos = array();

if($_GET["del"] != "")
{
	$sql = 'delete from usuario where tipo = 5 AND idColegio = "'.$varMiId .'" AND id = '.$_GET["del"];
	if($_SESSION["superusuario"] != 1)
	{
		$sql.=" AND usuario.idSucursal = ".$_SESSION["idSucursal"];
	}
	$PSN1->query($sql);
}
if(isset($_POST["funcion"]))
{
	$sql= "SELECT * ";
	$sql.=" FROM usuario";
	$sql.=" WHERE id != '".$varMiId."' and login = '".eliminarInvalidos($_POST["login"])."'";
	$PSN->query($sql);
	if($PSN->next_record())
	{
		$errorLogueo = 1;
	}
	else if($_POST["funcion"] == "curso")
	{
		$sql= "SELECT * ";
		$sql.=" FROM usuario";
		$sql.=" WHERE id != '".$varMiId."' and login = '".eliminarInvalidos($_POST["curso_login"])."'";
		$PSN->query($sql);
		if($PSN->next_record())
		{
			$errorLogueoB = 1;
		}
		else
		{
			//$sql = 'insert into cursos (idColegio, nombre) values ('.$varMiId.', "'.$_POST["curso"]).'")';
			//$ultimoQuery = $PSN1->query($sql);
			//
			//
			$sql = 'insert into usuario (tipo, idColegio, nombre, identificacion, tipoIdentificacion, pais, departamento, ciudad, direccion, telefono1, telefono2, celular, email, cargo, genero, lider, url, observaciones, login, password, usuarioCreador, idSucursal) ';
			//
			//
			$sql .= ' values (5, "'.$varMiId.'", "'.eliminarInvalidos($_POST["curso"]).'", "'.eliminarInvalidos($_POST["curso_cedula"]).'", "'.eliminarInvalidos($_POST["curso_tipoIdentificacion"]).'", "", "", "", "'.eliminarInvalidos($_POST["curso_direccion"]).'", "'.eliminarInvalidos($_POST["curso_telefono"]).'", "", "", "'.eliminarInvalidos($_POST["curso_email"]).'", "", "", "", "", "", "'.eliminarInvalidos($_POST["curso_login"]).'", "'.eliminarInvalidos($_POST["curso_pass"]).'", "'.$_SESSION["id"].'", "'.$_SESSION["idSucursal"].'") ';

			$ultimoQuery = $PSN1->query($sql);
		}
	}
	else if($_POST["funcion"] == "actualizar")
	{
		$nombre_archivo = $_FILES['archivo']['name'];
		$tipo_archivo = $_FILES['archivo']['type'];
		$tamano_archivo = $_FILES['archivo']['size'];

		/*
		*	DEBEMOS INSERTAR LA INFORMACION DEL CLIENTE/USUARIO SEGUN CORRESPONDA.
		*/
		//$sql = 'insert into usuario (tipo, idCliente, nombre, razon, identificacion, tipoIdentificacion, tipoContribuyente, regimenDian, categoriaDian, clasificacionIndustria, representanteLegal, pais, departamento, ciudad, direccion, telefono1, telefono2, celular, email, cargo, genero, lider, url, observaciones, login, password, prefijo, consecutivo) ';
		$sql = 'update usuario set nombre = "'.eliminarInvalidos($_POST["nombre"]).'", razon = "'.eliminarInvalidos($_POST["razon"]).'", identificacion = "'.eliminarInvalidos($_POST["identificacion"]).'", tipoIdentificacion = "'.eliminarInvalidos($_POST["tipoIdentificacion"]).'", tipoContribuyente = "'.eliminarInvalidos($_POST["tipoContribuyente"]).'", regimenDian = "'.eliminarInvalidos($_POST["regimenDian"]).'", categoriaDian = "'.eliminarInvalidos($_POST["categoriaDian"]).'", clasificacionIndustria = "'.eliminarInvalidos($_POST["clasificacionIndustria"]).'", representanteLegal = "'.eliminarInvalidos($_POST["representanteLegal"]).'", pais = "'.eliminarInvalidos($_POST["pais"]).'", departamento = "'.eliminarInvalidos($_POST["departamento"]).'", ciudad = "'.eliminarInvalidos($_POST["ciudad"]).'", direccion = "'.eliminarInvalidos($_POST["direccion"]).'", telefono1 = "'.eliminarInvalidos($_POST["telefono1"]).'", telefono2 = "'.eliminarInvalidos($_POST["telefono2"]).'", celular = "'.eliminarInvalidos($_POST["celular"]).'", email = "'.eliminarInvalidos($_POST["email"]).'", url = "'.eliminarInvalidos($_POST["url"]).'", observaciones = "'.eliminarInvalidos($_POST["observaciones"]).'", login = "'.eliminarInvalidos($_POST["login"]).'", password = "'.eliminarInvalidos($_POST["password"]).'", prefijo = "'.eliminarInvalidos($_POST["prefijo"]).'", consecutivo = "'.eliminarInvalidos($_POST["consecutivo"]).'", porcentaje1 = "'.eliminarInvalidos($_POST["porcentaje1"]).'", porcentaje2 = "'.eliminarInvalidos($_POST["porcentaje2"]).'", porcentaje3 = "'.eliminarInvalidos($_POST["porcentaje3"]).'", porcentaje4 = "'.eliminarInvalidos($_POST["porcentaje4"]).'", porcentaje5 = "'.eliminarInvalidos($_POST["porcentaje5"]).'", porcentaje6 = "'.eliminarInvalidos($_POST["porcentaje6"]).'", porcentaje7 = "'.eliminarInvalidos($_POST["porcentaje7"]).'", porcentaje8 = "'.eliminarInvalidos($_POST["porcentaje8"]).'", porcentaje9 = "'.eliminarInvalidos($_POST["porcentaje9"]).'", porcentaje10 = "'.eliminarInvalidos($_POST["porcentaje10"]).'", valorfijo1 = "'.eliminarInvalidos($_POST["valorfijo1"]).'", valorfijo2 = "'.eliminarInvalidos($_POST["valorfijo2"]).'", valorfijo3 = "'.eliminarInvalidos($_POST["valorfijo3"]).'", valorfijo4 = "'.eliminarInvalidos($_POST["valorfijo4"]).'", dia_vencimiento = "'.eliminarInvalidos($_POST["dia_vencimiento"]).'", cuota = "'.eliminarInvalidos($_POST["cuota"]).'", inactivo = "'.eliminarInvalidos($_POST["inactivo"]).'", tarifa0	= "'.eliminarInvalidos($_POST["tarifa0"]).'"
		
		, idReal = "'.eliminarInvalidos($_POST["idReal"]).'"
		, idEducativo = "'.eliminarInvalidos($_POST["idEducativo"]).'"

		, cuporotativo = "'.eliminarInvalidos($_POST["cuporotativo"]).'"
		, valor_cuota = "'.eliminarInvalidos($_POST["valor_cuota"]).'"
		, titulo_valor = "'.eliminarInvalidos($_POST["titulo_valor"]).'"
		, chequesGiradosA = "'.eliminarInvalidos($_POST["chequesGiradosA"]).'"
		, asesorComercial = "'.eliminarInvalidos($_POST["asesorComercial"]).'"';

		for($i=1;$i<25;$i+=2)
		{
			$sql .= ', tarifa'.$i.' = "'.eliminarInvalidos($_POST["tarifa".$i]).'"
			, tarifa'.($i+1).' = "'.eliminarInvalidos($_POST["tarifa".($i+1)]).'"';
		}
		$sql .= ', tarifa30		= "'.eliminarInvalidos($_POST["tarifa30"]).'"
				, tarifa36 = "'.eliminarInvalidos($_POST["tarifa36"]).'"
				, tarifa42 = "'.eliminarInvalidos($_POST["tarifa42"]).'"
				, tarifa48 = "'.eliminarInvalidos($_POST["tarifa48"]).'"
				, tarifa60 = "'.eliminarInvalidos($_POST["tarifa60"]).'"';

		$sql .= ', tipoTarifa = "'.eliminarInvalidos($_POST["tipoTarifa"]).'"';
		
		for($i=1;$i<25;$i+=2)
		{
			$sql .= ', btarifa'.$i.' = "'.eliminarInvalidos($_POST["btarifa".$i]).'"
			, btarifa'.($i+1).' = "'.eliminarInvalidos($_POST["btarifa".($i+1)]).'"';
		}
		$sql .= ', btarifa30		= "'.eliminarInvalidos($_POST["btarifa30"]).'"
				, btarifa36 = "'.eliminarInvalidos($_POST["btarifa36"]).'"
				, btarifa42 = "'.eliminarInvalidos($_POST["btarifa42"]).'"
				, btarifa48 = "'.eliminarInvalidos($_POST["btarifa48"]).'"
				, btarifa60 = "'.eliminarInvalidos($_POST["btarifa60"]).'"';

				
		
		for($i=1;$i<25;$i+=2)
		{
			$sql .= ', crtarifa'.$i.' = "'.eliminarInvalidos($_POST["crtarifa".$i]).'"
			, crtarifa'.($i+1).' = "'.eliminarInvalidos($_POST["crtarifa".($i+1)]).'"';
		}
		$sql .= ', crtarifa30		= "'.eliminarInvalidos($_POST["crtarifa30"]).'"
				, crtarifa36 = "'.eliminarInvalidos($_POST["crtarifa36"]).'"
				, crtarifa42 = "'.eliminarInvalidos($_POST["crtarifa42"]).'"
				, crtarifa48 = "'.eliminarInvalidos($_POST["crtarifa48"]).'"
				, crtarifa60 = "'.eliminarInvalidos($_POST["crtarifa60"]).'"';

				
				
		$sql .= ', referencia_nombre1 = "'.eliminarInvalidos($_POST["referencia_nombre1"]).'"
		, referencia_telefono1 = "'.eliminarInvalidos($_POST["referencia_telefono1"]).'"
		, referencia_contacto1 = "'.eliminarInvalidos($_POST["referencia_contacto1"]).'"
		, referencia_nombre2 = "'.eliminarInvalidos($_POST["referencia_nombre2"]).'"
		, referencia_telefono2 = "'.eliminarInvalidos($_POST["referencia_telefono2"]).'"
		, referencia_contacto2 = "'.eliminarInvalidos($_POST["referencia_contacto2"]).'"

		
		';
		
		$sql .= " where id = ".$varMiId;

		$ultimoQuery = $PSN1->query($sql);

		if(trim($nombre_archivo) != "")
		{
			//Compruebo si las características del archivo son las que deseo 
			if(move_uploaded_file($_FILES['archivo']['tmp_name'], "images/clientes/".$varMiId.".jpg"))
			{
			}
		}
		$varExitoUSU = 1;
	}	
}

/*
*	TRAEMOS EL CLIENTE
*/
$sql = "SELECT usuario.*, creador.nombre as nombreCreador ";
$sql.=" FROM usuario left join usuario as creador ON creador.id = usuario.id ";
$sql.=" WHERE usuario.id = ".$varMiId;
if($_SESSION["superusuario"] != 1)
{
	$sql.=" AND usuario.idSucursal = ".$_SESSION["idSucursal"];
}

$PSN3->query($sql);
$numero=$PSN3->num_rows();
if($numero > 0)
{
	$PSN3->next_record();
	//Solo si no se ha modificado ya el formulario.
	$idEducativo = $PSN3->f('idEducativo');
	$idReal = $PSN3->f('idReal');
	$nombre = $PSN3->f('nombre');
	$razon = $PSN3->f('razon');
	$identificacion = $PSN3->f('identificacion');
	$tipoIdentificacion = $PSN3->f('tipoIdentificacion');
	$tipoContribuyente = $PSN3->f('tipoContribuyente');
	$regimenDian = $PSN3->f('regimenDian');
	$categoriaDian = $PSN3->f('categoriaDian');
	$clasificacionIndustria = $PSN3->f('clasificacionIndustria');
	$representanteLegal = $PSN3->f('representanteLegal');
	$pais = $PSN3->f('pais');
	$departamento = $PSN3->f('departamento');
	$ciudad = $PSN3->f('ciudad');
	$direccion = $PSN3->f('direccion');
	$telefono1 = $PSN3->f('telefono1');
	$telefono2 = $PSN3->f('telefono2');
	$celular = $PSN3->f('celular');
	$email = $PSN3->f('email');
	$url = $PSN3->f('url');
	
	$observaciones = $PSN3->f('observaciones');
	$cuporotativo = $PSN3->f('cuporotativo');
	$login = $PSN3->f('login');
	$password = $PSN3->f('password');
	$prefijo = $PSN3->f('prefijo');
	$consecutivo = $PSN3->f('consecutivo');
	$cuota = $PSN3->f('cuota');
	$codigoEstablecimiento = $PSN3->f('codigoEstablecimiento');

	$porcentaje1 = $PSN3->f('porcentaje1');
	$porcentaje2 = $PSN3->f('porcentaje2');
	$porcentaje3 = $PSN3->f('porcentaje3');
	$porcentaje4 = $PSN3->f('porcentaje4');
	$porcentaje5 = $PSN3->f('porcentaje5');
	$porcentaje6 = $PSN3->f('porcentaje6');
	$porcentaje7 = $PSN3->f('porcentaje7');
	$porcentaje8 = $PSN3->f('porcentaje8');
	$porcentaje9 = $PSN3->f('porcentaje9');
	$porcentaje10 = $PSN3->f('porcentaje10');

	$valorfijo1 = $PSN3->f('valorfijo1');
	$valorfijo2 = $PSN3->f('valorfijo2');
	$valorfijo3 = $PSN3->f('valorfijo3');
	$valorfijo4 = $PSN3->f('valorfijo4');
	
	$dia_vencimiento = $PSN3->f('dia_vencimiento');

	
$valor_cuota = $PSN3->f('valor_cuota');
$titulo_valor = $PSN3->f('titulo_valor');
$chequesGiradosA = $PSN3->f('chequesGiradosA');
$asesorComercial = $PSN3->f('asesorComercial');
$tarifa0 = $PSN3->f('tarifa0');
$tarifa1 = $PSN3->f('tarifa1');
$tarifa2 = $PSN3->f('tarifa2');
$tarifa3 = $PSN3->f('tarifa3');
$tarifa4 = $PSN3->f('tarifa4');
$tarifa5 = $PSN3->f('tarifa5');
$tarifa6 = $PSN3->f('tarifa6');
$tarifa7 = $PSN3->f('tarifa7');
$tarifa8 = $PSN3->f('tarifa8');
$tarifa9 = $PSN3->f('tarifa9');
$tarifa10 = $PSN3->f('tarifa10');
$tarifa11 = $PSN3->f('tarifa10');
$referencia_nombre1 = $PSN3->f('referencia_nombre1');
$referencia_telefono1 = $PSN3->f('referencia_telefono1');
$referencia_contacto1 = $PSN3->f('referencia_contacto1');
$referencia_nombre2 = $PSN3->f('referencia_nombre2');
$referencia_telefono2 = $PSN3->f('referencia_telefono2');
$referencia_contacto2 = $PSN3->f('referencia_contacto2');
$usuarioCreador  = $PSN3->f('nombreCreador');
$inactivo = $PSN3->f('inactivo');

}
else
{
	die("<h1>No esta autorizado para ver este perfil.</h1>");
}
$numRows = 1;
while($numRows > 0)
{
	$sufijo = mt_rand(0, 9);
	$numero = mt_rand(1000, 9999);
	$codigoUsuario = $numero."-".$sufijo;	
	//
	$sql = "SELECT * ";
	$sql.=" FROM usuario ";
	$sql.=" WHERE codigoEstablecimiento = '".$codigoUsuario."'";
	$PSN2->query($sql);
	$numRows = $PSN2->num_rows();
}



/*
*	TRAEMOS LAS ORDENES DE SERVICIO DEL USUARIO ACTUAL.
*/
$sql = "SELECT * ";
$sql.=" FROM usuario ";
$sql.=" WHERE tipo = 5 AND idColegio = ".$varMiId;

$PSN2->query($sql);
$numero2=$PSN2->num_rows();


?><br /><form method="post" enctype="multipart/form-data" name="form1" id="form1">
<table width="800px" border="0" cellspacing="0" cellpadding="2"  align="center">
<thead>
	<tr> 
	<th colspan="4">.MODIFICACION DE CLIENTE.</th>
	</tr>
	<?
	if($errorLogueo == 1)
	{
		?>
		<tr>
		<th colspan="4" bgcolor="#ffffff" style="background-color:#ffffff"><h1><font color="red"><u>ATENCION:</u> NO SE ACTUALIZO EL CLIENTE<BR /><u>MOTIVO:</u> YA EXISTE UN USUARIO CON ESE MISMO "LOGIN".<br />POR FAVOR CAMBIE EL "LOGIN".</font></h1></th>
		</tr>
		<?
	}
	?>
	<?
	if($errorLogueoB == 1)
	{
		?>
		<tr>
		<th colspan="4" bgcolor="#ffffff" style="background-color:#ffffff"><h1><font color="red"><u>ATENCION:</u> NO SE AGREGO EL AUTORIZADO<BR /><u>MOTIVO:</u> YA EXISTE UN USUARIO CON ESE MISMO "LOGIN".<br />POR FAVOR CAMBIE EL "LOGIN".</font></h1></th>
		</tr>
		<?
	}
	?>
</thead>
<tbody>
        <?
		
		if($inactivo == 1)
		{
			?><tr height="25px"><td colspan="4"><center>ESTE CLIENTE ESTA INACTIVO</td></tr>
            <tr height="25px"><td colspan="4"><center><input type="button" name="button" onclick="inactivar(0)" value="Activar" style="background-color:#FFFFFF;border-color:#0000FF;color:#006600;font-weight:bold"></td></tr><?
		}
		else
		{
			?><tr height="25px"><td colspan="4"><center>ESTE CLIENTE ESTA ACTIVO</td></tr>
            <tr height="25px"><td colspan="4"><center><input type="button" name="button" onclick="inactivar(1)" value="Inactivar" style="background-color:#FFFFFF;border-color:#0000FF;color:#006600;font-weight:bold"></td></tr><?
		}
		?>
    </tr>
    
    
    
	<tr> 
		<td><strong>Nombre:</strong></td>
		<td><input name="nombre" type="text" id="nombre" maxlength="250" value="<?=$nombre; ?>" /></td>
		<td><strong>Razon Social:</strong></td>
		<td><input name="razon" type="text" id="razon" maxlength="250" value="<?=$razon; ?>" /></td>
	</tr>
	<tr>
		<td><strong>Identificacion:</strong></td>
		<td><input name="identificacion" type="text" id="identificacion" maxlength="250" value="<?=$identificacion; ?>" /></td>
		<td><strong>Tipo de Identificacion:</strong></td>
		<td><select name="tipoIdentificacion">
		<?
		/*
		*	TRAEMOS LOS TIPOS DE IDENTIFICACION (2)
		*/
		$sql = "SELECT * ";
		$sql.=" FROM categorias ";
		$sql.=" WHERE idSec = 2 ORDER BY descripcion asc";
		
		
		$PSN1->query($sql);
		$numero=$PSN1->num_rows();
		if($numero > 0)
		{
			while($PSN1->next_record())
			{
				?><option value="<?=$PSN1->f('id'); ?>" <?
				if($tipoIdentificacion == $PSN1->f('id'))
				{
					?>selected="selected"<?
				}
				?>><?=$PSN1->f('descripcion'); ?></option><?
			}
		}
		?>
		</select></td>
	</tr>
	<tr>
		<td><strong>Tipo de Contribuyente:</strong></td>
		<td><select name="tipoContribuyente">
		<?
		/*
		*	TRAEMOS LOS TIPOS DE CONTRIBUYENTE (6)
		*/
		$sql = "SELECT * ";
		$sql.=" FROM categorias ";
		$sql.=" WHERE idSec = 6 ORDER BY descripcion asc";
		
		
		$PSN1->query($sql);
		$numero=$PSN1->num_rows();
		if($numero > 0)
		{
			while($PSN1->next_record())
			{
				?><option value="<?=$PSN1->f('id'); ?>" <?
				if($tipoContribuyente == $PSN1->f('id'))
				{
					?>selected="selected"<?
				}
				?>><?=$PSN1->f('descripcion'); ?></option><?
			}
		}
		?>
		</select></td>
		<!--<td><strong>Regimen Dian:</strong></td>
		<td><select name="regimenDian">
		<?
		/*
		*	TRAEMOS LOS TIPOS DE REGIMEN DIAN (7)
		*/
		$sql = "SELECT * ";
		$sql.=" FROM categorias ";
		$sql.=" WHERE idSec = 7 ORDER BY descripcion asc";
		
		
		$PSN1->query($sql);
		$numero=$PSN1->num_rows();
		if($numero > 0)
		{
			while($PSN1->next_record())
			{
				?><option value="<?=$PSN1->f('id'); ?>" <?
				if($regimenDian == $PSN1->f('id'))
				{
					?>selected="selected"<?
				}
				?>><?=$PSN1->f('descripcion'); ?></option><?
			}
		}
		?>
		</select></td>
	</tr>
	<tr>
		<td><strong>Categoria Dian:</strong></td>
		<td><select name="categoriaDian">
		<?
		/*
		*	TRAEMOS CATEGORIA DIAN (8)
		*/
		$sql = "SELECT * ";
		$sql.=" FROM categorias ";
		$sql.=" WHERE idSec = 8 ORDER BY descripcion asc";
		
		
		$PSN1->query($sql);
		$numero=$PSN1->num_rows();
		if($numero > 0)
		{
			while($PSN1->next_record())
			{
				?><option value="<?=$PSN1->f('id'); ?>" <?
				if($categoriaDian == $PSN1->f('id'))
				{
					?>selected="selected"<?
				}
				?>><?=$PSN1->f('descripcion'); ?></option><?
			}
		}
		?>
		</select></td>//-->
		<td><strong>Actividad Comercial:</strong></td>
		<td><select name="clasificacionIndustria">
		<?
		/*
		*	TRAEMOS CLASIFICACION INDUSTRIA (9)
		*/
		$sql = "SELECT * ";
		$sql.=" FROM categorias ";
		$sql.=" WHERE idSec = 9 ORDER BY descripcion asc";
		
		
		$PSN1->query($sql);
		$numero=$PSN1->num_rows();
		if($numero > 0)
		{
			while($PSN1->next_record())
			{
				?><option value="<?=$PSN1->f('id'); ?>" <?
				if($clasificacionIndustria == $PSN1->f('id'))
				{
					?>selected="selected"<?
				}
				?>><?=$PSN1->f('descripcion'); ?></option><?
			}
		}
		?>
		</select></td>
	</tr>
	<tr>
		<td><strong>Representante Legal:</strong></td>
		<td><input name="representanteLegal" type="text" id="representanteLegal" maxlength="250" value="<?=$representanteLegal; ?>" /></td>
		<td><strong>Pais:</strong></td>
		<td><select name="pais">
			<option <? if($pais == "Argentina"){ ?>selected="selected"<? } ?>>Argentina</option>
			<option <? if($pais == "Aruba"){ ?>selected="selected"<? } ?>>Aruba</option>
			<option <? if($pais == "Australia"){ ?>selected="selected"<? } ?>>Australia</option>
			<option <? if($pais == "Barbados"){ ?>selected="selected"<? } ?>>Barbados</option>
			<option <? if($pais == "Belarus"){ ?>selected="selected"<? } ?>>Belarus</option>
			<option <? if($pais == "Brazil"){ ?>selected="selected"<? } ?>>Brazil</option>
			<option <? if($pais == "Canada"){ ?>selected="selected"<? } ?>>Canada</option>
			<option <? if($pais == "Chile"){ ?>selected="selected"<? } ?>>Chile</option>
			<option <? if($pais == "Colombia"){ ?>selected="selected"<? } ?>>Colombia</option>
			<option <? if($pais == "Costa Rica"){ ?>selected="selected"<? } ?>>Costa Rica</option>
			<option <? if($pais == "Cuba"){ ?>selected="selected"<? } ?>>Cuba</option>
			<option <? if($pais == "Dominican Republic"){ ?>selected="selected"<? } ?>>Dominican Republic</option>
			<option <? if($pais == "Ecuador"){ ?>selected="selected"<? } ?>>Ecuador</option>
			<option <? if($pais == "El Salvador"){ ?>selected="selected"<? } ?>>El Salvador</option>
			<option <? if($pais == "Guatemala"){ ?>selected="selected"<? } ?>>Guatemala</option>
			<option <? if($pais == "Guyana"){ ?>selected="selected"<? } ?>>Guyana</option>
			<option <? if($pais == "Haiti"){ ?>selected="selected"<? } ?>>Haiti</option>
			<option <? if($pais == "Honduras"){ ?>selected="selected"<? } ?>>Honduras</option>
			<option <? if($pais == "India"){ ?>selected="selected"<? } ?>>India</option>
			<option <? if($pais == "Italy"){ ?>selected="selected"<? } ?>>Italy</option>
			<option <? if($pais == "Jamaica"){ ?>selected="selected"<? } ?>>Jamaica</option>
			<option <? if($pais == "Mexico"){ ?>selected="selected"<? } ?>>Mexico</option>
			<option <? if($pais == "Nicaragua"){ ?>selected="selected"<? } ?>>Nicaragua</option>
			<option <? if($pais == "Panama"){ ?>selected="selected"<? } ?>>Panama</option>
			<option <? if($pais == "Paraguay"){ ?>selected="selected"<? } ?>>Paraguay</option>
			<option <? if($pais == "Peru"){ ?>selected="selected"<? } ?>>Peru</option>
			<option <? if($pais == "United States"){ ?>selected="selected"<? } ?>>United States</option>
			<option <? if($pais == "Venezuela"){ ?>selected="selected"<? } ?>>Venezuela</option>
		</select></td>
	</tr>
	<tr>
		<td><strong>Departamento:</strong></td>
		<td><input name="departamento" type="text" id="departamento" maxlength="250" value="<?=$departamento; ?>" /></td>
		<td><strong>Ciudad:</strong></td>
		<td><input name="ciudad" type="text" id="ciudad" maxlength="250" value="<?=$ciudad; ?>" /></td>
	</tr>
	<tr>
		<td><strong>Direccion:</strong></td>
		<td><input name="direccion" type="text" id="direccion" maxlength="250" value="<?=$direccion; ?>" /></td>
		<td><strong>Telefono:</strong></td>
		<td><input name="telefono1" type="text" id="telefono1" maxlength="250" value="<?=$telefono1; ?>" /></td>
	</tr>
	<tr>
		<td><strong>Fax:</strong></td>
		<td><input name="telefono2" type="text" id="telefono2" maxlength="250" value="<?=$telefono2; ?>" /></td>
		<td><strong>Celular:</strong></td>
		<td><input name="celular" type="text" id="celular" maxlength="250" value="<?=$celular; ?>" /></td>
	</tr>
	<tr>
		<td><strong>E-mail:</strong></td>
		<td><input name="email" type="text" id="email" maxlength="250" value="<?=$email; ?>" /></td>
		<td><strong>Pagina Web:</strong></td>
		<td><input name="url" type="text" id="url" maxlength="250" value="<?=$url; ?>" /></td>
	</tr>
	<tr>
		<td><strong>Login:</strong></td>
		<td><input name="login" type="text" id="login" maxlength="250" value="<?=$login; ?>" /></td>
		<td><strong>Password:</strong></td>
		<td><input name="password" type="text" id="password" maxlength="250" value="<?=$password; ?>" /></td>
	</tr>
	<tr>
		<td><strong>Cuota:</strong></td>
		<td><select name="cuota">
			<option value="1" <? if($cuota == "1"){ ?>selected="selected"<? } ?>>Mes</option>
			<option value="2" <? if($cuota == "2"){ ?>selected="selected"<? } ?>>Anho</option>
			<option value="3" <? if($cuota == "3"){ ?>selected="selected"<? } ?>>Unica</option>
		</select></td>

		<td><strong>Valor Cuota:</strong></td>
		<td><input name="valor_cuota" type="text" id="valor_cuota" maxlength="250" value="<?=$valor_cuota; ?>" /></td>
	</tr>
    <tr>
		<td><strong>Titulo Valor:</strong></td>
		<td><select name="titulo_valor">
			<option value="1" <? if($titulo_valor == "1"){ ?>selected="selected"<? } ?>>Cheque</option>
			<option value="2" <? if($titulo_valor == "2"){ ?>selected="selected"<? } ?>>Cheque - Letra</option>
			<option value="3" <? if($titulo_valor == "3"){ ?>selected="selected"<? } ?>>Cheque - Pagare</option>
            <option value="4" <? if($titulo_valor == "4"){ ?>selected="selected"<? } ?>>Cheque - Factura</option>
            <option value="5" <? if($titulo_valor == "5"){ ?>selected="selected"<? } ?>>Cheque - Letra - Pagare</option>
            <option value="6" <? if($titulo_valor == "6"){ ?>selected="selected"<? } ?>>Cheque - Letra - Factura</option>
            <option value="7" <? if($titulo_valor == "7"){ ?>selected="selected"<? } ?>>Cheque - Pagare - Factura</option>
    </select></td>
		<td><strong>Cheque Respsaldados Girados A:</strong></td>
		<td><input name="chequesGiradosA" type="text" id="chequesGiradosA" maxlength="255" value="<?=$chequesGiradosA; ?>" /></td>
	</tr>
    
	<tr>
		<td><strong>Asesor Comercial:</strong></td>
		<td><input name="asesorComercial" type="text" id="asesorComercial" maxlength="255" value="<?=$asesorComercial; ?>" /></td>


		<td><strong>Codigo Establecimiento:</strong></td>
		<td><input name="codigoEstablecimiento" type="text" id="codigoEstablecimiento" maxlength="20" value="<?=$codigoEstablecimiento; ?>" readonly /></td>
	</tr>
<!--	<tr>
		<td><strong>Prefijo:</strong></td>
		<td><input name="prefijo" type="text" id="prefijo" maxlength="250" value="<?=$prefijo; ?>" /></td>
		<td><strong>Consecutivo:</strong></td>
		<td><input name="consecutivo" type="text" id="consecutivo" maxlength="250" value="<?=$consecutivo; ?>" /></td>
	</tr>//-->
	<tr>
		<td><strong>Observaciones:</strong></td>
		<td><input name="observaciones" type="text" id="observaciones" maxlength="250" value="<?=$observaciones; ?>" /></td>
		<td><strong>Activar Cupo Rotativo:</strong></td>
		<td><input name="cuporotativo" type="checkbox" id="cuporotativo" value="1" <?
        if($cuporotativo == 1)
		{
			?>checked="checked"<?
		}
		?> /></td>
	</tr>		
	<tr>
		<td><strong>Logotipo (.jpg):</strong></td>
		<td><input name="archivo" type="file" id="archivo" /></td>
		<td colspan="2" align="center"><?
		if(file_exists("images/clientes/".$varMiId.".jpg"))
		{
			?><img src="images/clientes/<?=$varMiId;?>.jpg" align="middle" width="200" style="border-color:#0000FF" border="1"><?
		}
		else
		{
			echo "&nbsp;";
		}	
		?></td>
	</tr>
	<tr> 
		<td><strong>Usuario que lo creo:</strong></td>
		<td colspan="3"><?=$nombreCreador; ?></td>
	</tr>

	<!--
	<tr> 
		<th colspan="4">.TARIFAS DE CONFIRMACION POR OPERACIONES AVALADAS.</th>
	</tr>
	<tr>
		<td><strong>1 Mes:</strong></td>
		<td><input name="tarifa1" type="text" id="tarifa1" maxlength="250" value="<?=$tarifa1; ?>" /></td>
		<td><strong>2 Meses:</strong></td>
		<td><input name="tarifa2" type="text" id="tarifa2" maxlength="250" value="<?=$tarifa2; ?>" /></td>
	</tr>

    <tr>
	    <td><strong>3 Meses:</strong></td>
		<td><input name="tarifa3" type="text" id="tarifa3" maxlength="250" value="<?=$tarifa3; ?>" /></td>
		<td><strong>4 Meses:</strong></td>
		<td><input name="tarifa4" type="text" id="tarifa4" maxlength="250" value="<?=$tarifa4; ?>" /></td>
	</tr>
   
    <tr>
	    <td><strong>5 Meses:</strong></td>
		<td><input name="tarifa5" type="text" id="tarifa6" maxlength="250" value="<?=$tarifa5; ?>" /></td>
		<td><strong>6 Meses:</strong></td>
		<td><input name="tarifa6" type="text" id="tarifa6" maxlength="250" value="<?=$tarifa6; ?>" /></td>
	</tr>

	<tr>
		<td><strong>12 Meses:</strong></td>
		<td><input name="tarifa7" type="text" id="tarifa7" maxlength="250" value="<?=$tarifa7; ?>" /></td>
		<td><strong>18 Meses:</strong></td>
		<td><input name="tarifa8" type="text" id="tarifa8" maxlength="250" value="<?=$tarifa8; ?>" /></td>
	</tr>

    <tr>
	    <td><strong>24 Meses:</strong></td>
		<td><input name="tarifa9" type="text" id="tarifa9" maxlength="250" value="<?=$tarifa9; ?>" /></td>
		<td><strong>36 Meses:</strong></td>
		<td><input name="tarifa10" type="text" id="tarifa10" maxlength="250" value="<?=$tarifa10; ?>" /></td>
	</tr>

   <tr>
		<td><strong>48 Meses:</strong></td>
		<td><input name="tarifa11" type="text" id="tarifa11" maxlength="250" value="<?=$tarifa11; ?>" /></td>
	</tr>
//-->
	
	<tr> 
		<th colspan="4">.TARIFAS DE CONFIRMACION POR OPERACIONES AVALADAS <u>EN CHEQUE</u>.</th>
	</tr>
    <tr>
        <td><strong>CHEQUE al DIA (3 dias):</strong></td>
        <td><input name="tarifa0" type="text" id="tarifa0" maxlength="250" value="<?=$PSN3->f("tarifa0"); ?>" /></td>
    </tr>
<?
for($i=1;$i<25;$i+=2)
{
  ?>
        <tr>
		<td><strong><?=$i; ?> Meses:</strong></td>
		<td><input name="tarifa<?=$i; ?>" type="text" id="tarifa<?=$i; ?>" maxlength="250" value="<?=$PSN3->f("tarifa".$i); ?>" /></td>
		<td><strong><?=$i+1; ?> Meses:</strong></td>
		<td><input name="tarifa<?=$i+1; ?>" type="text" id="tarifa<?=$i+1; ?>" maxlength="250"  value="<?=$PSN3->f("tarifa".($i+1)); ?>" /></td>
		</tr>
<?
}
?>
        <tr>
		<td><strong>30 Meses:</strong></td>
		<td><input name="tarifa30" type="text" id="tarifa30" maxlength="250" value="<?=$PSN3->f("tarifa30"); ?>" /></td>
		<td><strong>36 Meses:</strong></td>
		<td><input name="tarifa36" type="text" id="tarifa36" maxlength="250" value="<?=$PSN3->f("tarifa36"); ?>" /></td>
	</tr>
        <tr>
		<td><strong>42 Meses:</strong></td>
		<td><input name="tarifa42" type="text" id="tarifa42" maxlength="250" value="<?=$PSN3->f("tarifa42"); ?>" /></td>
		<td><strong>48 Meses:</strong></td>
		<td><input name="tarifa48" type="text" id="tarifa48" maxlength="250" value="<?=$PSN3->f("tarifa48"); ?>" /></td>
	</tr>
        <tr>
		<td><strong>60 Meses:</strong></td>
		<td><input name="tarifa60" type="text" id="tarifa60" maxlength="250" value="<?=$PSN3->f("tarifa60"); ?>" /></td>
	</tr>
	

		<tr> 
		<th colspan="4">.TIPO DE TARIFA.</th>
	</tr>
        <tr>
		<td><strong>Tarifa Nominal Mensual:</strong></td>
		<td><input name="tipoTarifa" type="checkbox" id="tipoTarifa" value="1" <?
		if($PSN3->f("tipoTarifa") == 1)
		{
			?>checked="checked"<?
		}
		?> /></td>
	</tr>


	
	<tr> 
		<th colspan="4">.TARIFAS DE CONFIRMACION POR OPERACIONES AVALADAS <u>EN OTROS TITULOS</u>.</th>
	</tr>
<?
for($i=1;$i<25;$i+=2)
{
  ?>
        <tr>
		<td><strong><?=$i; ?> Meses:</strong></td>
		<td><input name="btarifa<?=$i; ?>" type="text" id="btarifa<?=$i; ?>" maxlength="250" value="<?=$PSN3->f("btarifa".$i); ?>" /></td>
		<td><strong><?=$i+1; ?> Meses:</strong></td>
		<td><input name="btarifa<?=$i+1; ?>" type="text" id="btarifa<?=$i+1; ?>" maxlength="250"  value="<?=$PSN3->f("btarifa".($i+1)); ?>" /></td>
		</tr>
<?
}
?>
        <tr>
		<td><strong>30 Meses:</strong></td>
		<td><input name="btarifa30" type="text" id="btarifa30" maxlength="250" value="<?=$PSN3->f("btarifa30"); ?>" /></td>
		<td><strong>36 Meses:</strong></td>
		<td><input name="btarifa36" type="text" id="btarifa36" maxlength="250" value="<?=$PSN3->f("btarifa36"); ?>" /></td>
	</tr>
        <tr>
		<td><strong>42 Meses:</strong></td>
		<td><input name="btarifa42" type="text" id="btarifa42" maxlength="250" value="<?=$PSN3->f("btarifa42"); ?>" /></td>
		<td><strong>48 Meses:</strong></td>
		<td><input name="btarifa48" type="text" id="btarifa48" maxlength="250" value="<?=$PSN3->f("btarifa48"); ?>" /></td>
	</tr>
        <tr>
		<td><strong>60 Meses:</strong></td>
		<td><input name="btarifa60" type="text" id="btarifa60" maxlength="250" value="<?=$PSN3->f("btarifa60"); ?>" /></td>
	</tr>
	


	<tr> 
		<th colspan="4">.TARIFAS DE CONFIRMACION POR OPERACIONES AVALADAS <u>EN CUPO ROTATIVO</u>.</th>
	</tr>
	<?
    for($i=1;$i<25;$i+=2)
    {
      ?>
            <tr>
            <td><strong><?=$i; ?> Meses:</strong></td>
            <td><input name="crtarifa<?=$i; ?>" type="text" id="crtarifa<?=$i; ?>" maxlength="250" value="<?=$PSN3->f("crtarifa".$i); ?>" /></td>
            <td><strong><?=$i+1; ?> Meses:</strong></td>
            <td><input name="crtarifa<?=$i+1; ?>" type="text" id="crtarifa<?=$i+1; ?>" maxlength="250" value="<?=$PSN3->f("crtarifa".($i+1)); ?>" /></td>
        </tr>
    <?
    }
    ?>
        <tr>
		<td><strong>30 Meses:</strong></td>
		<td><input name="crtarifa30" type="text" id="crtarifa30" maxlength="250" value="<?=$PSN3->f("crtarifa30"); ?>" /></td>
		<td><strong>36 Meses:</strong></td>
		<td><input name="crtarifa36" type="text" id="crtarifa36" maxlength="250" value="<?=$PSN3->f("crtarifa36"); ?>" /></td>
	</tr>
        <tr>
		<td><strong>42 Meses:</strong></td>
		<td><input name="crtarifa42" type="text" id="crtarifa42" maxlength="250" value="<?=$PSN3->f("crtarifa42"); ?>" /></td>
		<td><strong>48 Meses:</strong></td>
		<td><input name="crtarifa48" type="text" id="crtarifa48" maxlength="250" value="<?=$PSN3->f("crtarifa48"); ?>" /></td>
	</tr>
        <tr>
		<td><strong>60 Meses:</strong></td>
		<td><input name="crtarifa60" type="text" id="crtarifa60" maxlength="250" value="<?=$PSN3->f("crtarifa60"); ?>" /></td>
	</tr>




	<tr> 
		<th colspan="4">.REFERENCIAS COMERCIALES.</th>
	</tr>
    <tr> 
	    <th>Numero</th>
		    <th>Nombre</th>
		    <th>Telefono</th>
		    <th>Contacto</th>
	</tr>
	<tr>
		<td><strong>Primera:</strong></td>
		<td><input name="referencia_nombre1" type="text" id="referencia_nombre1" maxlength="250" value="<?=$referencia_nombre1; ?>" /></td>
        <td><input name="referencia_telefono1" type="text" id="referencia_telefono1" maxlength="250" value="<?=$referencia_telefono1; ?>" /></td>
	        <td><input name="referencia_contacto1" type="text" id="referencia_contacto1" maxlength="250" value="<?=$referencia_contacto1; ?>" /></td>
	</tr>

	<tr>
		<td><strong>Segunda:</strong></td>
		<td><input name="referencia_nombre2" type="text" id="referencia_nombre2" maxlength="250" value="<?=$referencia_nombre2; ?>" /></td>
        <td><input name="referencia_telefono2" type="text" id="referencia_telefono2" maxlength="250" value="<?=$referencia_telefono2; ?>" /></td>
	        <td><input name="referencia_contacto2" type="text" id="referencia_contacto2" maxlength="250" value="<?=$referencia_contacto2; ?>" /></td>
	</tr>


<!--
	<tr> 
	<th colspan="4">.PORCENTAJES PARA LOS TITULOS.</th>
	</tr>
	<tr>
		<td><strong>Porcentaje 1:</strong></td>
		<td><input name="porcentaje1" type="text" id="porcentaje1" maxlength="250" value="<?=$porcentaje1; ?>" /></td>
		<td><strong>Porcentaje 2:</strong></td>
		<td><input name="porcentaje2" type="text" id="porcentaje2" maxlength="250" value="<?=$porcentaje2; ?>" /></td>
	</tr>

	<tr>
		<td><strong>Porcentaje 3:</strong></td>
		<td><input name="porcentaje3" type="text" id="porcentaje3" maxlength="250" value="<?=$porcentaje3; ?>" /></td>
		<td><strong>Porcentaje 4:</strong></td>
		<td><input name="porcentaje4" type="text" id="porcentaje4" maxlength="250" value="<?=$porcentaje4; ?>" /></td>
	</tr>
    

	<tr>
		<td><strong>Porcentaje 5:</strong></td>
		<td><input name="porcentaje5" type="text" id="porcentaje5" maxlength="250" value="<?=$porcentaje5; ?>" /></td>
		<td><strong>Porcentaje 6:</strong></td>
		<td><input name="porcentaje6" type="text" id="porcentaje6" maxlength="250" value="<?=$porcentaje6; ?>" /></td>
	</tr>

	<tr>
		<td><strong>Porcentaje 7:</strong></td>
		<td><input name="porcentaje7" type="text" id="porcentaje7" maxlength="250" value="<?=$porcentaje7; ?>" /></td>
		<td><strong>Porcentaje 8:</strong></td>
		<td><input name="porcentaje8" type="text" id="porcentaje8" maxlength="250" value="<?=$porcentaje8; ?>" /></td>
	</tr>

	<tr>
		<td><strong>Porcentaje 9:</strong></td>
		<td><input name="porcentaje9" type="text" id="porcentaje9" maxlength="250" value="<?=$porcentaje9; ?>" /></td>
		<td><strong>Porcentaje 10:</strong></td>
		<td><input name="porcentaje10" type="text" id="porcentaje10" maxlength="250" value="<?=$porcentaje10; ?>" /></td>
	</tr>


	<tr> 
	<th colspan="4">.VALORES FIJOS PARA EL TOTAL.</th>
	</tr>
	<tr>
		<td><strong>Valor 1:</strong></td>
		<td><input name="valorfijo1" type="text" id="valorfijo1" maxlength="250" value="<?=$valorfijo1; ?>" /></td>
		<td><strong>Valor 2:</strong></td>
		<td><input name="valorfijo2" type="text" id="valorfijo2" maxlength="250" value="<?=$valorfijo2; ?>" /></td>
	</tr>

	<tr>
		<td><strong>Valor 3:</strong></td>
		<td><input name="valorfijo3" type="text" id="valorfijo3" maxlength="250" value="<?=$valorfijo3; ?>" /></td>
		<td><strong>Valor 4:</strong></td>
		<td><input name="valorfijo4" type="text" id="valorfijo4" maxlength="250" value="<?=$valorfijo4; ?>" /></td>
	</tr>

	<tr> 
	<th colspan="4">.VENCIMIENTO.</th>
	</tr>
	<tr>
		<td><strong>Dia Vencimiento:</strong></td>
		<td colspan="3"><input name="dia_vencimiento" type="text" id="dia_vencimiento" maxlength="250" value="<?=$dia_vencimiento; ?>" /></td>
	</tr>
//-->
	<tr> 
	<th colspan="4">.AUTORIZADOS A HACER SOLICITUDES.</th>
	</tr>
    <tr>
    <td colspan="4">
    
	<table width="100%" border="0">
    <tr>
    	<th>Nombre</th>
    	<th>Identificacion</th>
    	<th>Direccion</th>
    	<th>Telefono</th>
    	<th>Usuario</th>
    	<th>Password</th>
    	<th>Opciones</th>
	<?
		if($numero2 > 0)
		{
			while($PSN2->next_record())
			{
				?><tr>
					<td><a href="index.php?doc=admin_usu4&id=<?=$PSN2->f('id'); ?>"><?=$PSN2->f('nombre'); ?></a></td>
					<td><?=$PSN2->f('identificacion'); ?></td>
					<td><?=$PSN2->f('direccion'); ?></td>
					<td><?=$PSN2->f('telefono'); ?></td>
					<td><?=$PSN2->f('login'); ?></td>
					<td><?=$PSN2->f('password'); ?></td>
					<td>[<a href="index.php?doc=admin_usu2&id=<?=$varMiId; ?>&del=<?=$PSN2->f('id'); ?>">BORRAR</a>]</td>
                </tr><?
			}
		}
	?>
    </table>


    </td>
    </tr>

    	<tr>
		<th colspan="4"><center>AGREGAR AUTORIZADO</center></th>
        </tr>
		<?
		if($errorLogueoB == 1)
		{
			?>
			<tr>
			<th colspan="4" bgcolor="#ffffff" style="background-color:#ffffff"><h1><font color="red"><u>ATENCION:</u> NO SE AGREGO EL AUTORIZADO<BR /><u>MOTIVO:</u> YA EXISTE UN USUARIO CON ESE MISMO "LOGIN".<br />POR FAVOR CAMBIE EL "LOGIN".</font></h1></th>
			</tr>
			<?
		}
		?>
		<tr>
			<td><strong>Nombre:</strong></td>
			<td><input name="curso" type="text" id="curso" maxlength="250" /></td>
        </tr>
        <tr>
	        <td><strong>Numero Identificacion:</strong></td>
			<td><input name="curso_cedula" type="text" id="curso_cedula" maxlength="250" /></td>
            <td><strong>Tipo de Identificacion:</strong></td>
            <td><select name="curso_tipoIdentificacion">
            <?
            /*
            *	TRAEMOS LOS TIPOS DE IDENTIFICACION (2)
            */
            $sql = "SELECT * ";
            $sql.=" FROM categorias ";
            $sql.=" WHERE idSec = 2 ORDER BY descripcion asc";
            
            
            $PSN1->query($sql);
            $numero=$PSN1->num_rows();
            if($numero > 0)
            {
                while($PSN1->next_record())
                {
                    ?><option value="<?=$PSN1->f('id'); ?>" <?
                    if($tipoIdentificacion == $PSN1->f('id'))
                    {
                        ?>selected="selected"<?
                    }
                    ?>><?=$PSN1->f('descripcion'); ?></option><?
                }
            }
            ?>
            </select></td>
        </tr>
		<tr>
			<td><strong>Direcci&oacute;n:</strong></td>
			<td><input name="curso_direccion" type="text" id="curso_direccion" maxlength="250" /></td>
			<td><strong>Telefono:</strong></td>
			<td><input name="curso_telefono" type="text" id="curso_telefono" maxlength="250" /></td>
        </tr>
		<tr>
			<td><strong>Login:</strong></td>
			<td><input name="curso_login" type="text" id="curso_login" maxlength="250" /></td>
			<td><strong>Password:</strong></td>
			<td><input name="curso_pass" type="text" id="curso_pass" maxlength="250" /></td>
        </tr>
        <tr>
			<td><strong>E-Mail:</strong></td>
			<td><input name="curso_email" type="text" id="curso_email" maxlength="250" /></td>
            <td><strong>Codigo Usuario Establecimiento:</strong></td>
            <td><input name="curso_codigoEstablecimiento" type="text" id="curso_codigoEstablecimiento" maxlength="20" value="<?=$codigoUsuario; ?>" readonly /></td>
        </tr>
        <tr>
            <td colspan="4"><input type="button" name="button" onclick="generarFormC()" value="Agregar!" style="background-color:#FFFFFF;border-color:#0000FF;color:#006600;font-weight:bold"></td>
		</tr>
        <tr>
            <th colspan="4"><strong>Usuario Equivalente en SECTOR EDUCATIVO:</strong></th>
        </tr>
        <tr>
            <td colspan="4"><select name="idEducativo">
            <?
            /*
            *	TRAEMOS LOS TIPOS DE IDENTIFICACION (2)
            */
            $sql = "SELECT id, nombre ";
            $sql.=" FROM usuario ";
            $sql.=" WHERE tipo = 4 ORDER BY nombre asc";
			//
            $PSN_Edu->query($sql);
            $numero = $PSN_Edu->num_rows();
            if($numero > 0)
            {
                while($PSN_Edu->next_record())
                {
                    ?><option value="<?=$PSN_Edu->f('id'); ?>" <?
                    if($idEducativo == $PSN_Edu->f('id'))
                    {
                            ?>selected="selected"<?
                    }
                    ?>><?=$PSN_Edu->f('nombre')." (".$PSN_Edu->f('nombre').")"; ?></option><?
                }
            }
            ?>
            </select></td>
        </tr>
        <tr>
            <th colspan="4"><strong>Usuario Equivalente en SECTOR REAL:</strong></th>
        </tr>
        <tr>
            <td colspan="4"><select name="idReal">
            <?
            /*
            *	TRAEMOS LOS TIPOS DE IDENTIFICACION (2)
            */
            $sql = "SELECT id, nombre ";
            $sql.=" FROM usuario ";
            $sql.=" WHERE tipo = 4 ORDER BY nombre asc";
            
            
            $PSN_Real->query($sql);
            $numero = $PSN_Real->num_rows();
            if($numero > 0)
            {
                while($PSN_Real->next_record())
                {
                    ?><option value="<?=$PSN_Real->f('id'); ?>" <?
                    if($idReal == $PSN_Real->f('id'))
                    {
                            ?>selected="selected"<?
                    }
                    ?>><?=$PSN_Real->f('nombre')." (".$PSN_Real->f('nombre').")"; ?></option><?
                }
            }
            ?>
            </select></td>
        </tr>
	</tbody>
</table>
<input type="hidden" name="funcion" id="funcion" value="" />
<input type="hidden" name="inactivo" id="inactivo" value="<?=$inactivo; ?>" />
<br />
<hr color="#0000FF" width="800px" />
<br />
<center><input type="button" name="button" onclick="generarForm()" value="Actualizar Cliente" style="background-color:#FFFFFF;border-color:#0000FF;color:#006600;font-weight:bold"> <input type="button" name="button" onclick="regresar()" value="Cancelar" style="background-color:#FFFFFF;border-color:#0000FF;color:#006600;font-weight:bold"> <input type="button" name="button" onclick="imprimirOperacion()" value="Imprimir" style="background-color:#FFFFFF;border-color:#0000FF;color:#006600;font-weight:bold"></center>
</form><script language="javascript">
	function generarForm(){
			if(confirm("Esta accion actualizara informacion del CLIENTE en el sistema, ¿esta seguro que desea continuar?"))
			{
				if(document.getElementById('nombre').value != "" 
				&& document.getElementById('identificacion').value != ""
				&& document.getElementById('email').value != ""
				&& document.getElementById('login').value != ""
				&& document.getElementById('password').value != ""
				)
				{
					document.getElementById('funcion').value = "actualizar";
					document.getElementById('form1').submit();
				}
				else
				{
					alert("La informacion es primordial para brindarle un excelente servicio, por favor digite al menos los campos de NOMBRE, NIT, E-MAIL, LOGIN, PASSWORDs");
				}
			}
	}


	function generarFormC(){
			if(confirm("Esta accion agregara una PERSONA AUTORIZADA del CLIENTE en el sistema, ¿esta seguro que desea continuar?"))
			{
				if(document.getElementById('curso').value != "")
				{
					document.getElementById('funcion').value = "curso";
					document.getElementById('form1').submit();
				}
				else
				{
					alert("La informacion es primordial para brindarle un excelente servicio, por favor digite al menos los campos de PERSONA AUTORIZADA");
				}
			}
	}


	function inactivar(valor){
			if(confirm("Esta accion INACTIVARA AL CLIENTE y todos los usuarios AUTORIZADOS DE ESTE CLIENTE en el sistema, ¿esta seguro que desea continuar?"))
			{
				document.getElementById('funcion').value = "actualizar";
				document.getElementById('inactivo').value = valor;
				document.getElementById('form1').submit();
			}
	}

	function imprimirOperacion(){
		window.open("impresion.php?doc=imp_cliente&id=<?=$varMiId; ?>&rtn=l", "titulo", "status=1, scrollbars=1, height=800, width=800");
	}

	function regresar()
	{
		window.location.href = "index.php?doc=admin_buscarc";
	}
	function init(){
		document.getElementById('form1').onsubmit = function(){
				return false;
		}
		<?
		if($varExitoUSU == 1)
		{
			?>alert("Se ha actualizado correctamente el CLIENTE.");<?
		}
		?>
	}
	window.onload = function(){
		init();
	}
	</script>