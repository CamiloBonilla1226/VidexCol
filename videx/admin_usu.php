












in<?php
if($_SESSION["perfil"] != 1)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

//
// Objeto de Base de Datos
//
$PSN1 = new DBbase_Sql;
//Objeto de EDUCATIVO
$PSN_Edu = new DBbase_SqlC;
//Objeto de REAL
$PSN_Real = new DBbase_SqlD;
//
// Array que nos servira para ir llevando cuenta de los requerimientos.
//
$arrayRequerimientos = array();

// *-*-*-*-*-*-*-*-*-*-*
// *-*-*-*-*-*-*-*-*-*-*
// 
// *-*-*-*-*-*-*-*-*-*-*
// *-*-*-*-*-*-*-*-*-*-*
if(isset($_POST["funcion"]))
{
	/*
	* AQUI OBTENEMOS "N" CANTIDAD DE REQUERIMIENTOS SEGUN LO DIGITADO POR EL USUARIO ANTERIORMENTE.
	*/
	$sql= "SELECT * ";
	$sql.=" FROM usuario";
	$sql.=" WHERE login = '".eliminarInvalidos($_POST["login"])."'";
	$PSN->query($sql);		
	if($PSN->next_record())
	{
		$errorLogueo = 1;
	}
	else if($_POST["funcion"] == "insertar")
	{
		//
		//
		//
		$nombre_archivo = $_FILES['archivo']['name'];
		$tipo_archivo = $_FILES['archivo']['type'];
		$tamano_archivo = $_FILES['archivo']['size'];

		/*
		*	DEBEMOS INSERTAR LA INFORMACION DEL CLIENTE/USUARIO SEGUN CORRESPONDA.
		*/
		//$sql = 'insert into usuario (tipo, idCliente, nombre, razon, identificacion, tipoIdentificacion, tipoContribuyente, regimenDian, categoriaDian, clasificacionIndustria, representanteLegal, pais, departamento, ciudad, direccion, telefono1, telefono2, celular, email, cargo, genero, lider, url, observaciones, login, password, prefijo, consecutivo) ';
		$sql = 'insert into usuario (idEducativo, idReal, tipo, nombre, razon, identificacion, tipoIdentificacion, tipoContribuyente, regimenDian, categoriaDian, clasificacionIndustria, representanteLegal, pais, departamento, ciudad, direccion, telefono1, telefono2, celular, email, url, observaciones, login, password, prefijo, consecutivo, porcentaje1, porcentaje2, porcentaje3, porcentaje4, porcentaje5, porcentaje6, porcentaje7, porcentaje8, porcentaje9, porcentaje10, valorfijo1, valorfijo2, valorfijo3, valorfijo4, dia_vencimiento, cuota, codigoEstablecimiento
		, valor_cuota
		, titulo_valor
		, chequesGiradosA
		, asesorComercial
		, tarifa0
		, tarifa1
		, tarifa2
		, tarifa3
		, tarifa4
		, tarifa5
		, tarifa6
		, tarifa7
		, tarifa8
		, tarifa9
		, tarifa10
		, tarifa11
		, tarifa12
		, tarifa13
		, tarifa14
		, tarifa15
		, tarifa16
		, tarifa17
		, tarifa18
		, tarifa19
		, tarifa20
		, tarifa21
		, tarifa22
		, tarifa23
		, tarifa24
		, tarifa36
		, tarifa42
		, tarifa48
		, tarifa60
		, tipoTarifa
		, btarifa1
		, btarifa2
		, btarifa3
		, btarifa4
		, btarifa5
		, btarifa6
		, btarifa7
		, btarifa8
		, btarifa9
		, btarifa10
		, btarifa11
		, btarifa12
		, btarifa13
		, btarifa14
		, btarifa15
		, btarifa16
		, btarifa17
		, btarifa18
		, btarifa19
		, btarifa20
		, btarifa21
		, btarifa22
		, btarifa23
		, btarifa24
		, btarifa36
		, btarifa42
		, btarifa48
		, btarifa60
		, crtarifa1
		, crtarifa2
		, crtarifa3
		, crtarifa4
		, crtarifa5
		, crtarifa6
		, crtarifa7
		, crtarifa8
		, crtarifa9
		, crtarifa10
		, crtarifa11
		, crtarifa12
		, crtarifa13
		, crtarifa14
		, crtarifa15
		, crtarifa16
		, crtarifa17
		, crtarifa18
		, crtarifa19
		, crtarifa20
		, crtarifa21
		, crtarifa22
		, crtarifa23
		, crtarifa24
		, crtarifa30
		, crtarifa36
		, crtarifa42
		, crtarifa48
		, crtarifa60
		, referencia_nombre1
		, referencia_telefono1
		, referencia_contacto1
		, referencia_nombre2
		, referencia_telefono2
		, referencia_contacto2
		, usuarioCreador
		, idSucursal
		)';
		
		$sql .= ' values (
			 "'.eliminarInvalidos($_POST["idEducativo"]).'"
			, "'.eliminarInvalidos($_POST["idReal"]).'"
			,4
			, "'.eliminarInvalidos($_POST["nombre"]).'"
			, "'.eliminarInvalidos($_POST["razon"]).'"
			, "'.eliminarInvalidos($_POST["identificacion"]).'"
			, "'.eliminarInvalidos($_POST["tipoIdentificacion"]).'"
			, "'.eliminarInvalidos($_POST["tipoContribuyente"]).'"
			, "'.eliminarInvalidos($_POST["regimenDian"]).'"
			, "'.eliminarInvalidos($_POST["categoriaDian"]).'"
			, "'.eliminarInvalidos($_POST["clasificacionIndustria"]).'"
			, "'.eliminarInvalidos($_POST["representanteLegal"]).'"
			, "'.eliminarInvalidos($_POST["pais"]).'"
			, "'.eliminarInvalidos($_POST["departamento"]).'"
			, "'.eliminarInvalidos($_POST["ciudad"]).'"
			, "'.eliminarInvalidos($_POST["direccion"]).'"
			, "'.eliminarInvalidos($_POST["telefono1"]).'"
			, "'.eliminarInvalidos($_POST["telefono2"]).'"
			, "'.eliminarInvalidos($_POST["celular"]).'"
			, "'.eliminarInvalidos($_POST["email"]).'"
			, "'.eliminarInvalidos($_POST["url"]).'"
			, "'.eliminarInvalidos($_POST["observaciones"]).'"
			, "'.eliminarInvalidos($_POST["login"]).'"
			, "'.eliminarInvalidos($_POST["password"]).'"
			, "'.eliminarInvalidos($_POST["prefijo"]).'"
			, "'.eliminarInvalidos($_POST["consecutivo"]).'"
			, "'.eliminarInvalidos($_POST["porcentaje1"]).'"
			, "'.eliminarInvalidos($_POST["porcentaje2"]).'"
			, "'.eliminarInvalidos($_POST["porcentaje3"]).'"
			, "'.eliminarInvalidos($_POST["porcentaje4"]).'"
			, "'.eliminarInvalidos($_POST["porcentaje5"]).'"
			, "'.eliminarInvalidos($_POST["porcentaje6"]).'"
			, "'.eliminarInvalidos($_POST["porcentaje7"]).'"
			, "'.eliminarInvalidos($_POST["porcentaje8"]).'"
			, "'.eliminarInvalidos($_POST["porcentaje9"]).'"
			, "'.eliminarInvalidos($_POST["porcentaje10"]).'"
			, "'.eliminarInvalidos($_POST["valorfijo1"]).'"
			, "'.eliminarInvalidos($_POST["valorfijo2"]).'"
			, "'.eliminarInvalidos($_POST["valorfijo3"]).'"
			, "'.eliminarInvalidos($_POST["valorfijo4"]).'"
			, "'.eliminarInvalidos($_POST["dia_vencimiento"]).'"
			, "'.eliminarInvalidos($_POST["cuota"]).'"
			, "'.eliminarInvalidos($_POST["codigoEstablecimiento"]).'"
			, "'.eliminarInvalidos($_POST["valor_cuota"]).'"
			, "'.eliminarInvalidos($_POST["titulo_valor"]).'"
			, "'.eliminarInvalidos($_POST["chequesGiradosA"]).'"
			, "'.eliminarInvalidos($_POST["asesorComercial"]).'"
			, "'.eliminarInvalidos($_POST["tarifa0"]).'"
			, "'.eliminarInvalidos($_POST["tarifa1"]).'"
			, "'.eliminarInvalidos($_POST["tarifa2"]).'"
			, "'.eliminarInvalidos($_POST["tarifa3"]).'"
			, "'.eliminarInvalidos($_POST["tarifa4"]).'"
			, "'.eliminarInvalidos($_POST["tarifa5"]).'"
			, "'.eliminarInvalidos($_POST["tarifa6"]).'"
			, "'.eliminarInvalidos($_POST["tarifa7"]).'"
			, "'.eliminarInvalidos($_POST["tarifa8"]).'"
			, "'.eliminarInvalidos($_POST["tarifa9"]).'"
			, "'.eliminarInvalidos($_POST["tarifa10"]).'"
			, "'.eliminarInvalidos($_POST["tarifa12"]).'"
			, "'.eliminarInvalidos($_POST["tarifa13"]).'"
			, "'.eliminarInvalidos($_POST["tarifa14"]).'"
			, "'.eliminarInvalidos($_POST["tarifa15"]).'"
			, "'.eliminarInvalidos($_POST["tarifa16"]).'"
			, "'.eliminarInvalidos($_POST["tarifa17"]).'"
			, "'.eliminarInvalidos($_POST["tarifa18"]).'"
			, "'.eliminarInvalidos($_POST["tarifa19"]).'"
			, "'.eliminarInvalidos($_POST["tarifa20"]).'"
			, "'.eliminarInvalidos($_POST["tarifa21"]).'"
			, "'.eliminarInvalidos($_POST["tarifa22"]).'"
			, "'.eliminarInvalidos($_POST["tarifa23"]).'"
			, "'.eliminarInvalidos($_POST["tarifa24"]).'"
			, "'.eliminarInvalidos($_POST["tarifa30"]).'"
			, "'.eliminarInvalidos($_POST["tarifa36"]).'"
			, "'.eliminarInvalidos($_POST["tarifa42"]).'"
			, "'.eliminarInvalidos($_POST["tarifa48"]).'"
			, "'.eliminarInvalidos($_POST["tarifa60"]).'"

			, "'.eliminarInvalidos($_POST["tipoTarifa"]).'"

			, "'.eliminarInvalidos($_POST["btarifa1"]).'"
			, "'.eliminarInvalidos($_POST["btarifa2"]).'"
			, "'.eliminarInvalidos($_POST["btarifa3"]).'"
			, "'.eliminarInvalidos($_POST["btarifa4"]).'"
			, "'.eliminarInvalidos($_POST["btarifa5"]).'"
			, "'.eliminarInvalidos($_POST["btarifa6"]).'"
			, "'.eliminarInvalidos($_POST["btarifa7"]).'"
			, "'.eliminarInvalidos($_POST["btarifa8"]).'"
			, "'.eliminarInvalidos($_POST["btarifa9"]).'"
			, "'.eliminarInvalidos($_POST["btarifa10"]).'"
			, "'.eliminarInvalidos($_POST["btarifa12"]).'"
			, "'.eliminarInvalidos($_POST["btarifa13"]).'"
			, "'.eliminarInvalidos($_POST["btarifa14"]).'"
			, "'.eliminarInvalidos($_POST["btarifa15"]).'"
			, "'.eliminarInvalidos($_POST["btarifa16"]).'"
			, "'.eliminarInvalidos($_POST["btarifa17"]).'"
			, "'.eliminarInvalidos($_POST["btarifa18"]).'"
			, "'.eliminarInvalidos($_POST["btarifa19"]).'"
			, "'.eliminarInvalidos($_POST["btarifa20"]).'"
			, "'.eliminarInvalidos($_POST["btarifa21"]).'"
			, "'.eliminarInvalidos($_POST["btarifa22"]).'"
			, "'.eliminarInvalidos($_POST["btarifa23"]).'"
			, "'.eliminarInvalidos($_POST["btarifa24"]).'"
			, "'.eliminarInvalidos($_POST["btarifa30"]).'"
			, "'.eliminarInvalidos($_POST["btarifa36"]).'"
			, "'.eliminarInvalidos($_POST["btarifa42"]).'"
			, "'.eliminarInvalidos($_POST["btarifa48"]).'"
			, "'.eliminarInvalidos($_POST["btarifa60"]).'"

			, "'.eliminarInvalidos($_POST["crtarifa1"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa2"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa3"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa4"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa5"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa6"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa7"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa8"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa9"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa10"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa11"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa12"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa13"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa14"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa15"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa16"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa17"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa18"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa19"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa20"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa21"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa22"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa23"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa24"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa30"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa36"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa42"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa48"]).'"
			, "'.eliminarInvalidos($_POST["crtarifa60"]).'"

			, "'.eliminarInvalidos($_POST["referencia_nombre1"]).'"
			, "'.eliminarInvalidos($_POST["referencia_telefono1"]).'"
			, "'.eliminarInvalidos($_POST["referencia_contacto1"]).'"
			, "'.eliminarInvalidos($_POST["referencia_nombre2"]).'"
			, "'.eliminarInvalidos($_POST["referencia_telefono2"]).'"
			, "'.eliminarInvalidos($_POST["referencia_contacto2"]).'"
			, "'.$_SESSION["id"].'"
			, "'.eliminarInvalidos($_POST["idSucursal"]).'"
		) ';

		$ultimoQuery = $PSN1->query($sql);
		$ultimoId = mysql_insert_id();

		//Compruebo si las características del archivo son las que deseo 
		if(move_uploaded_file($_FILES['archivo']['tmp_name'], "images/clientes/".$ultimoId.".jpg"))
		{
		}
		$varExitoUSU = 1;
	}	
}


$numRows = 1;
while($numRows > 0)
{
	$sufijo = mt_rand(0, 9);
	$numero = mt_rand(10000, 99999);
	$codigoEstablecimiento = $numero."-".$sufijo;	
	//
	$sql = "SELECT * ";
	$sql.=" FROM usuario ";
	$sql.=" WHERE codigoEstablecimiento = '".$codigoEstablecimiento."'";
	$PSN1->query($sql);
	$numRows = $PSN1->num_rows();
}

//echo $codigoEstablecimiento;

?><br /><form method="post" enctype="multipart/form-data" name="form1" id="form1">
<table width="800px" border="0" cellspacing="0" cellpadding="2"  align="center">
<thead>
	<tr> 
	<th colspan="4">.CREACION DE CLIENTES.</th>
	</tr>
	<?
	if($errorLogueo == 1)
	{
		?>
		<tr>
		<th colspan="4" bgcolor="#ffffff" style="background-color:#ffffff"><h1><font color="red"><u>ATENCION:</u> NO SE CREO EL CLIENTE<BR /><u>MOTIVO:</u> YA EXISTE UN USUARIO CON ESE MISMO "LOGIN".<br />POR FAVOR CAMBIE EL "LOGIN".</font></h1></th>
		</tr>
		<?
	}
	?>
</thead>
<tbody>
	<tr> 
		<td><strong>Nombre:</strong></td>
		<td><input name="nombre" type="text" id="nombre" maxlength="250" value="<?=eliminarInvalidos($_POST["nombre"]); ?>" /></td>
		<td><strong>Razon Social:</strong></td>
		<td><input name="razon" type="text" id="razon" maxlength="250" value="<?=eliminarInvalidos($_POST["razon"]); ?>" /></td>
	</tr>
	<tr>
		<td><strong>Identificacion:</strong></td>
		<td><input name="identificacion" type="text" id="identificacion" maxlength="250" value="<?=eliminarInvalidos($_POST["identificacion"]); ?>" /></td>
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
		if($_POST["tipoIdentificacion"] == $PSN1->f('id'))
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
				if($_POST["tipoContribuyente"] == $PSN1->f('id'))
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
				if($_POST["regimenDian"] == $PSN1->f('id'))
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
				if($_POST["categoriaDian"] == $PSN1->f('id'))
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
				if($_POST["clasificacionIndustria"] == $PSN1->f('id'))
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
		<td><input name="representanteLegal" type="text" id="representanteLegal" maxlength="250" value="<?=eliminarInvalidos($_POST["representanteLegal"]); ?>" /></td>
		<td><strong>Pais:</strong></td>
		<td><select name="pais">
			<option>Argentina</option>
			<option>Aruba</option>
			<option>Australia</option>
			<option>Barbados</option>
			<option>Belarus</option>
			<option>Brazil</option>
			<option>Canada</option>
			<option>Chile</option>
			<option selected="selected">Colombia</option>
			<option>Costa Rica</option>
			<option>Cuba</option>
			<option>Dominican Republic</option>
			<option>Ecuador</option>
			<option>El Salvador</option>
			<option>Guatemala</option>
			<option>Guyana</option>
			<option>Haiti</option>
			<option>Honduras</option>
			<option>India</option>
			<option>Italy</option>
			<option>Jamaica</option>
			<option>Mexico</option>
			<option>Nicaragua</option>
			<option>Panama</option>
			<option>Paraguay</option>
			<option>Peru</option>
			<option>United States</option>
			<option>Venezuela</option>
		</select></td>
	</tr>
	<tr>
		<td><strong>Departamento:</strong></td>
		<td><input name="departamento" type="text" id="departamento" maxlength="250" value="<?=eliminarInvalidos($_POST["departamento"]); ?>" /></td>
		<td><strong>Ciudad:</strong></td>
		<td><input name="ciudad" type="text" id="ciudad" maxlength="250" value="<?=eliminarInvalidos($_POST["ciudad"]); ?>" /></td>
	</tr>
	<tr>
		<td><strong>Direccion:</strong></td>
		<td><input name="direccion" type="text" id="direccion" maxlength="250" value="<?=eliminarInvalidos($_POST["direccion"]); ?>" /></td>
		<td><strong>Telefono:</strong></td>
		<td><input name="telefono1" type="text" id="telefono1" maxlength="250" value="<?=eliminarInvalidos($_POST["telefono1"]); ?>" /></td>
	</tr>
	<tr>
		<td><strong>Fax:</strong></td>
		<td><input name="telefono2" type="text" id="telefono2" maxlength="250" value="<?=eliminarInvalidos($_POST["telefono2"]); ?>" /></td>
		<td><strong>Celular:</strong></td>
		<td><input name="celular" type="text" id="celular" maxlength="250" value="<?=eliminarInvalidos($_POST["celular"]); ?>" /></td>
	</tr>
	<tr>
		<td><strong>E-mail:</strong></td>
		<td><input name="email" type="text" id="email" maxlength="250" value="<?=eliminarInvalidos($_POST["email"]); ?>" /></td>
		<td><strong>Pagina Web:</strong></td>
		<td><input name="url" type="text" id="url" maxlength="250" value="<?=eliminarInvalidos($_POST["url"]); ?>" /></td>
	</tr>
	<tr>
		<td><strong>Login:</strong></td>
		<td><input name="login" type="text" id="login" maxlength="250" value="<?=eliminarInvalidos($_POST["login"]); ?>" /></td>
		<td><strong>Password:</strong></td>
		<td><input name="password" type="text" id="password" maxlength="250" value="<?=eliminarInvalidos($_POST["password"]); ?>" /></td>
	</tr>
	<tr>
		<td><strong>Cuota:</strong></td>
		<td><select name="cuota">
			<option value="1">Mes</option>
			<option value="2">Anho</option>
			<option value="3">Unica</option>
		</select></td>
		<td><strong>Valor Cuota:</strong></td>
		<td><input name="valor_cuota" type="text" id="valor_cuota" maxlength="250" value="<?=eliminarInvalidos($_POST["valor_cuota"]); ?>" /></td>
	</tr>
    <tr>
		<td><strong>Titulo Valor:</strong></td>
		<td><select name="titulo_valor">
			<option value="1">Cheque</option>
			<option value="2">Cheque - Letra</option>
			<option value="3">Cheque - Pagare</option>
            <option value="4">Cheque - Factura</option>
            <option value="5">Cheque - Letra - Pagare</option>
            <option value="6">Cheque - Letra - Factura</option>
            <option value="7">Cheque - Pagare - Factura</option>
    </select></td>
		<td><strong>Cheque Respsaldados Girados A:</strong></td>
		<td><input name="chequesGiradosA" type="text" id="chequesGiradosA" maxlength="255" value="<?=eliminarInvalidos($_POST["chequesGiradosA"]); ?>" /></td>
	</tr>
    
	<tr>
		<td><strong>Asesor Comercial:</strong></td>
		<td><input name="asesorComercial" type="text" id="asesorComercial" maxlength="255" value="<?=eliminarInvalidos($_POST["asesorComercial"]); ?>" /></td>
		<td><strong>Codigo Establecimiento:</strong></td>
		<td><input name="codigoEstablecimiento" type="text" id="codigoEstablecimiento" maxlength="20" value="<?=$codigoEstablecimiento; ?>" readonly /></td>
    </tr>

<!-- 	<tr>
		<td><strong>Prefijo:</strong></td>
		<td><input name="prefijo" type="text" id="prefijo" maxlength="250" value="<?=eliminarInvalidos($_POST["prefijo"]); ?>" /></td>
		<td><strong>Consecutivo:</strong></td>
		<td><input name="consecutivo" type="text" id="consecutivo" maxlength="250" value="<?=eliminarInvalidos($_POST["consecutivo"]); ?>" /></td>
	</tr> //-->
	<tr>
		<td><strong>Observaciones:</strong></td>
		<td colspan="3"><textarea name="observaciones" id="observaciones" cols="70" rows="5"  ><?=eliminarInvalidos($_POST["observaciones"]); ?></textarea></td>
	</tr>
	<tr>
		<td><strong>Logotipo (.jpg):</strong></td>
		<td><input name="archivo" type="file" id="archivo" /></td>
		<?
		if($_SESSION["superusuario"] == 1)
		{
			?>
			<td><strong>Sucursal:</strong></td>
			<td><select name="idSucursal">
			<?
			/*
			*	TRAEMOS LAS SUCURSALES
			*/
			$sql = "SELECT * ";
			$sql.=" FROM sucursales ";
			$sql.=" ORDER BY nombre asc";
						
			$PSN1->query($sql);
			$numero=$PSN1->num_rows();
			if($numero > 0)
			{
				while($PSN1->next_record())
				{
					if($PSN1->f('id') != 4)
					{
						?><option value="<?=$PSN1->f('id'); ?>" <?
						if($_POST["idSucursal"] == $PSN1->f('id'))
						{
							?>selected="selected"<?
						}
						?>><?=$PSN1->f('nombre'); ?></option><?
					}		
				}
			}
			?>
		</select></td>	
		<?
		}
		else
		{
			$sql = "SELECT * ";
			$sql.=" FROM sucursales ";
			$sql.=" WHERE id = ".$_SESSION["idSucursal"];
			$sql.=" ORDER BY nombre asc";
			//
			//			
			$PSN1->query($sql);
			$numero=$PSN1->num_rows();
			if($numero > 0)
			{
				while($PSN1->next_record())
				{
					if($PSN1->f('id') != 4)
					{
						$nombreSucursal = $PSN1->f('nombre');
					}		
				}
			}		
			?><td><input type="hidden" name="idSucursal" id="idSucursal" value="<?=$_SESSION["idSucursal"]; ?>" /><?=$nombreSucursal ; ?></td><?
		}
		?>
		
		</tr>

	<tr> 
		<th colspan="4">.TARIFAS DE CONFIRMACION POR OPERACIONES AVALADAS <u>EN CHEQUE</u>.</th>
	</tr>
	<tr>
		<td><strong>CHEQUE AL DIA (3 dias):</strong></td>
		<td><input name="tarifa0" type="text" id="tarifa0" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa1"]); ?>" /></td>
    </tr>
	<tr>
		<td><strong>1 Mes:</strong></td>
		<td><input name="tarifa1" type="text" id="tarifa1" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa1"]); ?>" /></td>
		<td><strong>2 Meses:</strong></td>
		<td><input name="tarifa2" type="text" id="tarifa2" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa2"]); ?>" /></td>
	</tr>

    <tr>
	    <td><strong>3 Meses:</strong></td>
		<td><input name="tarifa3" type="text" id="tarifa3" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa3"]); ?>" /></td>
		<td><strong>4 Meses:</strong></td>
		<td><input name="tarifa4" type="text" id="tarifa4" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa4"]); ?>" /></td>
	</tr>
   
    <tr>
	    <td><strong>5 Meses:</strong></td>
		<td><input name="tarifa5" type="text" id="tarifa6" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa5"]); ?>" /></td>
		<td><strong>6 Meses:</strong></td>
		<td><input name="tarifa6" type="text" id="tarifa6" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa6"]); ?>" /></td>
	</tr>

	<tr>
		<td><strong>7 Meses:</strong></td>
		<td><input name="tarifa7" type="text" id="tarifa7" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa7"]); ?>" /></td>
		<td><strong>8 Meses:</strong></td>
		<td><input name="tarifa8" type="text" id="tarifa8" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa8"]); ?>" /></td>
	</tr>

    <tr>
	    <td><strong>9 Meses:</strong></td>
		<td><input name="tarifa9" type="text" id="tarifa9" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa9"]); ?>" /></td>
		<td><strong>10 Meses:</strong></td>
		<td><input name="tarifa10" type="text" id="tarifa10" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa10"]); ?>" /></td>
	</tr>

        <tr>
		<td><strong>11 Meses:</strong></td>
		<td><input name="tarifa11" type="text" id="tarifa11" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa11"]); ?>" /></td>
		<td><strong>12 Meses:</strong></td>
		<td><input name="tarifa12" type="text" id="tarifa12" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa12"]); ?>" /></td>
	</tr>
<?
for($i=11;$i<25;$i+=2)
{
  ?>
        <tr>
		<td><strong><?=$i; ?> Meses:</strong></td>
		<td><input name="tarifa<?=$i; ?>" type="text" id="tarifa<?=$i; ?>" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa".$i]); ?>" /></td>
		<td><strong><?=$i+1; ?> Meses:</strong></td>
		<td><input name="tarifa<?=$i+1; ?>" type="text" id="tarifa<?=$i+1; ?>" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa".($i+1)]); ?>" /></td>
	</tr>
<?
}
?>
        <tr>
		<td><strong>30 Meses:</strong></td>
		<td><input name="tarifa30" type="text" id="tarifa30" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa30"]); ?>" /></td>
		<td><strong>36 Meses:</strong></td>
		<td><input name="tarifa36" type="text" id="tarifa36" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa36"]); ?>" /></td>
	</tr>
        <tr>
		<td><strong>42 Meses:</strong></td>
		<td><input name="tarifa42" type="text" id="tarifa42" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa42"]); ?>" /></td>
		<td><strong>48 Meses:</strong></td>
		<td><input name="tarifa48" type="text" id="tarifa48" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa48"]); ?>" /></td>
	</tr>
        <tr>
		<td><strong>60 Meses:</strong></td>
		<td><input name="tarifa60" type="text" id="tarifa60" maxlength="250" value="<?=eliminarInvalidos($_POST["tarifa60"]); ?>" /></td>
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
		<td><input name="btarifa<?=$i; ?>" type="text" id="btarifa<?=$i; ?>" maxlength="250" value="<?=eliminarInvalidos($_POST["btarifa".$i]); ?>" /></td>
		<td><strong><?=$i+1; ?> Meses:</strong></td>
		<td><input name="btarifa<?=$i+1; ?>" type="text" id="btarifa<?=$i+1; ?>" maxlength="250" value="<?=eliminarInvalidos($_POST["btarifa".($i+1)]); ?>" /></td>
	</tr>
<?
}
?>
        <tr>
		<td><strong>30 Meses:</strong></td>
		<td><input name="btarifa30" type="text" id="btarifa30" maxlength="250" value="<?=eliminarInvalidos($_POST["btarifa30"]); ?>" /></td>
		<td><strong>36 Meses:</strong></td>
		<td><input name="btarifa36" type="text" id="btarifa36" maxlength="250" value="<?=eliminarInvalidos($_POST["btarifa36"]); ?>" /></td>
	</tr>
        <tr>
		<td><strong>42 Meses:</strong></td>
		<td><input name="btarifa42" type="text" id="btarifa42" maxlength="250" value="<?=eliminarInvalidos($_POST["btarifa42"]); ?>" /></td>
		<td><strong>48 Meses:</strong></td>
		<td><input name="btarifa48" type="text" id="btarifa48" maxlength="250" value="<?=eliminarInvalidos($_POST["btarifa48"]); ?>" /></td>
	</tr>
        <tr>
		<td><strong>60 Meses:</strong></td>
		<td><input name="btarifa60" type="text" id="btarifa60" maxlength="250" value="<?=eliminarInvalidos($_POST["btarifa60"]); ?>" /></td>
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
		<td><input name="crtarifa<?=$i; ?>" type="text" id="crtarifa<?=$i; ?>" maxlength="250" value="<?=eliminarInvalidos($_POST["crtarifa".$i]); ?>" /></td>
		<td><strong><?=$i+1; ?> Meses:</strong></td>
		<td><input name="crtarifa<?=$i+1; ?>" type="text" id="crtarifa<?=$i+1; ?>" maxlength="250" value="<?=eliminarInvalidos($_POST["crtarifa".($i+1)]); ?>" /></td>
	</tr>
<?
}
?>
        <tr>
		<td><strong>30 Meses:</strong></td>
		<td><input name="crtarifa30" type="text" id="crtarifa30" maxlength="250" value="<?=eliminarInvalidos($_POST["crtarifa30"]); ?>" /></td>
		<td><strong>36 Meses:</strong></td>
		<td><input name="crtarifa36" type="text" id="crtarifa36" maxlength="250" value="<?=eliminarInvalidos($_POST["crtarifa36"]); ?>" /></td>
	</tr>
        <tr>
		<td><strong>42 Meses:</strong></td>
		<td><input name="crtarifa42" type="text" id="crtarifa42" maxlength="250" value="<?=eliminarInvalidos($_POST["crtarifa42"]); ?>" /></td>
		<td><strong>48 Meses:</strong></td>
		<td><input name="crtarifa48" type="text" id="crtarifa48" maxlength="250" value="<?=eliminarInvalidos($_POST["crtarifa48"]); ?>" /></td>
	</tr>
        <tr>
		<td><strong>60 Meses:</strong></td>
		<td><input name="crtarifa60" type="text" id="crtarifa60" maxlength="250" value="<?=eliminarInvalidos($_POST["crtarifa60"]); ?>" /></td>
	</tr>


	<tr> 
		<th colspan="4">.TIPO DE TARIFA.</th>
	</tr>
        <tr>
		<td><strong>Tarifa Plena:</strong></td>
		<td><input name="tipoTarifa" type="checkbox" id="tipoTarifa" value="1" /></td>
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
		<td><input name="referencia_nombre1" type="text" id="referencia_nombre1" maxlength="250" value="<?=eliminarInvalidos($_POST["referencia_nombre1"]); ?>" /></td>
        <td><input name="referencia_telefono1" type="text" id="referencia_telefono1" maxlength="250" value="<?=eliminarInvalidos($_POST["referencia_telefono1"]); ?>" /></td>
	        <td><input name="referencia_contacto1" type="text" id="referencia_contacto1" maxlength="250" value="<?=eliminarInvalidos($_POST["referencia_contacto1"]); ?>" /></td>
	</tr>

	<tr>
		<td><strong>Segunda:</strong></td>
		<td><input name="referencia_nombre2" type="text" id="referencia_nombre2" maxlength="250" value="<?=eliminarInvalidos($_POST["referencia_nombre2"]); ?>" /></td>
        <td><input name="referencia_telefono2" type="text" id="referencia_telefono2" maxlength="250" value="<?=eliminarInvalidos($_POST["referencia_telefono2"]); ?>" /></td>
	        <td><input name="referencia_contacto2" type="text" id="referencia_contacto2" maxlength="250" value="<?=eliminarInvalidos($_POST["referencia_contacto2"]); ?>" /></td>
	</tr>



<!--
	<tr> 
		<th colspan="4">.PORCENTAJES PARA LOS TITULOS.</th>
	</tr>
	<tr>
		<td><strong>Porcentaje 1:</strong></td>
		<td><input name="porcentaje1" type="text" id="porcentaje1" maxlength="250" value="<?=eliminarInvalidos($_POST["porcentaje1"]); ?>" /></td>
		<td><strong>Porcentaje 2:</strong></td>
		<td><input name="porcentaje2" type="text" id="porcentaje2" maxlength="250" value="<?=eliminarInvalidos($_POST["porcentaje2"]); ?>" /></td>
	</tr>
	<tr>
		<td><strong>Porcentaje 3:</strong></td>
		<td><input name="porcentaje3" type="text" id="porcentaje3" maxlength="250" value="<?=eliminarInvalidos($_POST["porcentaje3"]); ?>" /></td>
		<td><strong>Porcentaje 4:</strong></td>
		<td><input name="porcentaje4" type="text" id="porcentaje4" maxlength="250" value="<?=eliminarInvalidos($_POST["porcentaje4"]); ?>" /></td>
	</tr>
	<tr>
		<td><strong>Porcentaje 5:</strong></td>
		<td><input name="porcentaje5" type="text" id="porcentaje5" maxlength="250" value="<?=eliminarInvalidos($_POST["porcentaje5"]); ?>" /></td>
		<td><strong>Porcentaje 6:</strong></td>
		<td><input name="porcentaje6" type="text" id="porcentaje6" maxlength="250" value="<?=eliminarInvalidos($_POST["porcentaje6"]); ?>" /></td>
	</tr>

	<tr>
		<td><strong>Porcentaje 7:</strong></td>
		<td><input name="porcentaje7" type="text" id="porcentaje7" maxlength="250" value="<?=eliminarInvalidos($_POST["porcentaje7"]); ?>" /></td>
		<td><strong>Porcentaje 8:</strong></td>
		<td><input name="porcentaje8" type="text" id="porcentaje8" maxlength="250" value="<?=eliminarInvalidos($_POST["porcentaje8"]); ?>" /></td>
	</tr>

	<tr>
		<td><strong>Porcentaje 9:</strong></td>
		<td><input name="porcentaje9" type="text" id="porcentaje9" maxlength="250" value="<?=eliminarInvalidos($_POST["porcentaje9"]); ?>" /></td>
		<td><strong>Porcentaje 10:</strong></td>
		<td><input name="porcentaje10" type="text" id="porcentaje10" maxlength="250" value="<?=eliminarInvalidos($_POST["porcentaje10"]); ?>" /></td>
	</tr>


	<tr> 
	<th colspan="4">.VALORES FIJOS PARA EL TOTAL.</th>
	</tr>
	<tr>
		<td><strong>Valor 1:</strong></td>
		<td><input name="valorfijo1" type="text" id="valorfijo1" maxlength="250" value="<?=eliminarInvalidos($_POST["valorfijo1"]); ?>" /></td>
		<td><strong>Valor 2:</strong></td>
		<td><input name="valorfijo2" type="text" id="valorfijo2" maxlength="250" value="<?=eliminarInvalidos($_POST["valorfijo2"]); ?>" /></td>
	</tr>

	<tr>
		<td><strong>Valor 3:</strong></td>
		<td><input name="valorfijo3" type="text" id="valorfijo3" maxlength="250" value="<?=eliminarInvalidos($_POST["valorfijo3"]); ?>" /></td>
		<td><strong>Valor 4:</strong></td>
		<td><input name="valorfijo4" type="text" id="valorfijo4" maxlength="250" value="<?=eliminarInvalidos($_POST["valorfijo4"]); ?>" /></td>
	</tr>

<?
/*	<tr> 
	<th colspan="4">.VENCIMIENTO.</th>
	</tr>
	<tr>
		<td><strong>Dia Vencimiento:</strong></td>
		<td colspan="3"><input name="dia_vencimiento" type="text" id="dia_vencimiento" maxlength="250" value="<?=eliminarInvalidos($_POST["dia_vencimiento"]); ?>" /></td>
	</tr>
*/
?>
//-->
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
		
		
		$PSN_Edu->query($sql);
		$numero = $PSN_Edu->num_rows();
		if($numero > 0)
		{
			while($PSN_Edu->next_record())
			{
				?><option value="<?=$PSN_Edu->f('id'); ?>" <?
				if($_POST["idEducativo"] == $PSN_Edu->f('id'))
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
				if($_POST["idReal"] == $PSN_Real->f('id'))
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
<br />
<hr color="#0000FF" width="800px" />
<br />
<center><input type="button" name="button" onclick="generarForm()" value="Crear Cliente" style="background-color:#FFFFFF;border-color:#0000FF;color:#006600;font-weight:bold"></center>
</form><script language="javascript">
/*
**************************************************************
**  														**
**************************************************************
*/
function generarForm(){
		if(confirm("Esta accion generara el CLIENTE en el sistema, ¿esta seguro que desea continuar?"))
		{
			if(document.getElementById('nombre').value != "" 
			&& document.getElementById('identificacion').value != ""
			&& document.getElementById('email').value != ""
			&& document.getElementById('login').value != ""
			&& document.getElementById('password').value != ""
			)
			{
				document.getElementById('funcion').value = "insertar";
				document.getElementById('form1').submit();
			}
			else
			{
				alert("La informacion es primordial para brindarle un excelente servicio, por favor digite al menos los campos de NOMBRE, NIT, E-MAIL, LOGIN, PASSWORD");
			}
		}
}

function init(){
	document.getElementById('form1').onsubmit = function(){
			return false;
	}
	<?
	if($varExitoUSU == 1)
	{
		?>alert("Se ha colocado correctamente el CLIENTE, espere mientras es dirigido.");
		window.location.href = "index.php?doc=admin_usu2&id=<?=$ultimoId;?>";<?
	}
	?>
}

window.onload = function(){
	init();
}

</script>