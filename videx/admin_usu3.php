<?php
if($_SESSION["perfil"] != 1)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}
/*
*	$PSN = new DBbase_Sql;
*/
// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;
$PSN = new DBbase_Sql;

/*
*   AFECTA FORMULARIO Y ACTUAR DE LA PÁGINA
    1   USUARIO INTERNO
    2   CLIENTE
    3   PROVEEDOR
    4   USUARIO CLIENTE
*/
if(!isset($_REQUEST["ctrl"]) || soloNumeros($_REQUEST["ctrl"]) == "" || soloNumeros($_REQUEST["ctrl"]) == "0"){
    $ctrl = 1;
}
else{
    $ctrl = soloNumeros($_REQUEST["ctrl"]);
}

// Array que nos servira para ir llevando cuenta de los requerimientos.
$arrayRequerimientos = array();
if(isset($_POST["funcion"]))
{
    /*
    *   Para verificar errores a futuro.
        1   Campos requeridos en BLANCO (Nombre, identificacion, password)
        2   Password no coincide
        3   Identificacion YA existente
    */
    $error_datos = 0;

	if($_POST["funcion"] == "insertar")
	{
        /*
        *   PESTAÑA GENERAL
        */
        $general_nombre = eliminarInvalidos($_POST["nombre"]);
        $general_tipo = soloNumeros($_POST["tipo"]);
        $general_identificacion = eliminarInvalidos($_POST["identificacion"]);
        $general_tipoIdentificacion = soloNumeros($_POST["tipoIdentificacion"]);
        $general_direccion = eliminarInvalidos($_POST["direccion"]); 
        $general_telefono1 = soloNumeros($_POST["telefono1"]);
        $general_telefono2 = soloNumeros($_POST["telefono2"]);
        $general_celular = soloNumeros($_POST["celular"]);
        $general_celular2 = soloNumeros($_POST["celular2"]);
        $general_email = eliminarInvalidos($_POST["email"]);
        $general_url = eliminarInvalidos($_POST["url"]);
        $general_observaciones = eliminarInvalidos($_POST["observaciones"]);
        $general_password = eliminarInvalidos($_POST["password"]);
        $general_acceso = eliminarInvalidos($_POST["acceso"]);
        //
        $temp_password_check = eliminarInvalidos($_POST["password_check"]);
        /*
        *   ARCHIVO FOTO
        */
		$nombre_archivo = $_FILES['archivo']['name'];
		$tipo_archivo = $_FILES['archivo']['type'];
		$tamano_archivo = $_FILES['archivo']['size'];

        
        if($general_nombre == "" || $general_identificacion == "" || $general_password == ""){
            $error_datos = 1; //Datos requeridos en blanco
        }
        
        if($general_password != $temp_password_check){
            $error_datos = 2;   //Password diferente
        }
        
        /*
        *   COMPROBAMOS QUE IDENTIFICACION NO EXISTA
        */
        $sql= "SELECT id ";
        $sql.=" FROM usuario";
        $sql.=" WHERE identificacion = '".$general_identificacion."'";
        $PSN->query($sql);
        if($PSN->next_record())
        {
            $error_datos = 3;   //Identificacion ya existe
        }
        
        
        if($error_datos == 0){
            /*
            *	DEBEMOS INSERTAR LA INFORMACION DEL CLIENTE/USUARIO SEGUN CORRESPONDA.
            */
            $sql = 'INSERT INTO usuario (
                nombre,
                tipo,
                identificacion,
                tipoIdentificacion,
                direccion,
                telefono1,
                telefono2,
                celular,
                celular2,
                email,
                url,
                observaciones,
                acceso,
                password,
                creacionUsuario,
                creacionFecha
            ) ';
            $sql .= ' values 
                (
                "'.$general_nombre.'", 
                "'.$general_tipo.'", 
                "'.$general_identificacion.'", 
                "'.$general_tipoIdentificacion.'", 
                "'.$general_direccion.'", 
                "'.$general_telefono1.'", 
                "'.$general_telefono2.'", 
                "'.$general_celular.'", 
                "'.$general_celular2.'", 
                "'.$general_email.'", 
                "'.$general_url.'", 
                "'.$general_observaciones.'", 
                "'.$general_acceso.'", 
                "'.md5($general_password).'",
                "'.$_SESSION["id"].'",
                NOW()
            ) ';
            $ultimoQuery = $PSN1->query($sql);
            $ultimoId = mysql_insert_id();

            /*
            *   SE INSERTO EL USUARIO CORRECTAMENTE.
            */
            if($ultimoId > 0){
                /*
                *   INSERTAMOS INFORMACIÓN DEL CLIENTE
                */
                if($ctrl == 2){
                    
                }                   
                
                /*
                *   INSERTAMOS ACCESOS AL SISTEMA.
                */
                foreach($_POST["menu"] as $menuopc){				//
                    $sql ="INSERT INTO usuarios_menu (idUsuario, idMenu) VALUES (".$ultimoId.", ".soloNumeros($menuopc).")";
                    $PSN1->query($sql);
                }

                //Compruebo si las características del archivo son las que deseo
                if(move_uploaded_file($_FILES['archivo']['tmp_name'], "images/usuarios/".$ultimoId.".jpg"))
                {
                }
            }
            $varExitoUSU = 1;
        }
	}
}
/*
*   DETECTAMOS EL TIPO DE FORMULARIO QUE VAMOS A MOSTRAR.
*/
switch($ctrl){
    case 1:
        $temp_tiposUsuario = "2, 161, 162, 163";
        $temp_letrero = "USUARIO INTERNO";
        break;
    case 2:
        $temp_tiposUsuario = "3";
        $temp_letrero = "CLIENTE";
        break;
    case 3:
        $temp_tiposUsuario = "4";
        $temp_letrero = "PROVEEDOR";
        break;
    case 4:
        $temp_tiposUsuario = "160";
        $temp_letrero = "AUTORIZADO DEL CLIENTE:";
        break;
    default:
        $temp_letrero = "SIN DEFINIR";
        break;
}

/*
*   VALIDACIONES DE USUARIO AUTORIZADO DEL CLIENTE
*/
if($ctrl == 4){
    $error_cliente = 0; //
    if(
        !isset($_REQUEST["idCliente"]) || 
        soloNumeros($_REQUEST["idCliente"]) == "" || 
        soloNumeros($_REQUEST["idCliente"]) == "0"
    ){
        $error_cliente = 1; //  Cliente vacio
    }
    else{
        //  ID del cliente.
        $idCliente = soloNumeros($_REQUEST["idCliente"]);
        $error_cliente = 2; //  Cliente NO existente
        /*
        *	TRAEMOS LOS TIPOS DE USUARIO (1)
        */
        $sql = "SELECT id, nombre ";
        $sql.=" FROM usuario ";
        $sql.=" WHERE id = '".$idCliente."' AND tipo = 3";
        $PSN1->query($sql);
        $numero=$PSN1->num_rows();
        if($numero > 0)
        {
            if($PSN1->next_record())
            {
                $temp_nombreCliente = $PSN1->f("nombre");
                $temp_letrero .= "<br />".$temp_nombreCliente;
                $error_cliente = 0;
            }
        }
        
    }
    //
    switch($error_cliente){
        case 1:
            $texto_error = "Debe especificar un CLIENTE para poder crear un usuario autorizado del cliente.";
            $error_fatal = 1;
            break;
        case 2:
            $texto_error = "El ID especificado no corresponde a un CLIENTE.";
            $error_fatal = 1;
            break;
        default:
            break;
    }
}


switch($error_datos){
    case 1:
        $texto_error = "Datos requeridos de NOMBRE e IDENTIFICACIÓN.";
        break;
    case 2:
        $texto_error = "El password digitado no coincide.";
        break;
    case 3:
        $texto_error = "Ese numero de identificación ya existe en el sistema.";
        break;
    default:
        break;
}



?><div class="container">

<div class="row">
    <h2 class="alert alert-info text-center">.CREACION DE <?=$temp_letrero; ?>.</h2>
</div>

<?
//
if($texto_error != ""){
    ?><div class="row">
        <h5 class="alert alert-danger text-center"><?=$texto_error; ?></h5>
    </div><?
}

//
if($errorLogueo == 1)
{
    ?><div class="row"><h1><font color="red"><u>ATENCION:</u> NO SE CREO EL ACCESO<BR /><u>MOTIVO:</u> YA EXISTE UN ACCESO CON ESE MISMO "LOGIN".<br />POR FAVOR CAMBIE EL "LOGIN".</font></h1></div><?
}
?>

    
<?
if($error_fatal == 1){
    //No hacer nada.
}
else{
    ?><form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
<input type="hidden" name="control_acceso" id="control_acceso" value="<?=$ctrl; ?>" />
    
    
<ul class="nav nav-tabs">
	<li class="active"><a data-toggle="tab" href="#general">General</a></li>
    <li><a data-toggle="tab" href="#empresa">Empresarial</a></li>
    <?
    //  USUARIO PROVEEDOR
    if($ctrl == 3){
        ?><li><a data-toggle="tab" href="#servicios">Proveedor</a></li><?
    }
    //  USUARIO cliente
    if($ctrl == 2){
        ?><li><a data-toggle="tab" href="#cliente">Cliente</a></li><?
    }
    //  USUARIO cliente o proveedor
    if($ctrl == 1 || $ctrl == 2 || $ctrl == 3){
        ?><li><a data-toggle="tab" href="#archivos">Documentos</a></li><?
    }
    ?>
    <li><a data-toggle="tab" href="#accesos">Acceso al sistema</a></li>
</ul>


<div class="row">
<div class="tab-content">
	
<div id="general" class="tab-pane fade in active">

	<div class="row">
		<h3 class="text-center well">.INFORMACIÓN GENERAL.</h3>
	</div>

	<div class="form-group">
		<label class="form-label" for="nombre"><strong>Nombre:</strong></label>
		<input name="nombre" type="text" id="nombre" maxlength="250" value="<?=$general_nombre; ?>" class="form-control" required autofocus />
        </div>

                
		<label class="form-label" for="tipo"><strong>Tipo de usuario:</strong></label>
		<select name="tipo" class="form-control">
			<?
			/*
			*	TRAEMOS LOS TIPOS DE USUARIO (1)
			*/
			$sql = "SELECT * ";
			$sql.=" FROM categorias ";
			$sql.=" WHERE idSec = 1 AND id != 1 AND id IN (".$temp_tiposUsuario.") ORDER BY descripcion asc";

			$PSN1->query($sql);
			$numero=$PSN1->num_rows();
			if($numero > 0)
			{
                while($PSN1->next_record())
                {
                    ?><option value="<?=$PSN1->f('id'); ?>" <?
                    if($general_tipo == $PSN1->f('id'))
                    {
                        ?>selected="selected"<?
                    }
                    ?>><?=$PSN1->f('id'); ?> - <?=$PSN1->f('descripcion'); ?></option><?
                }
			}
			?>
			</select>		
	</div>


	<div class="form-group">
		<label class="form-label" for="identificacion"><strong>Identificaci&oacute;n</strong></label>
		<input name="identificacion" type="text" id="identificacion" maxlength="250" value="<?=$general_identificacion; ?>" class="form-control" required autofocus /></div>
		
		<label class="form-label" for="tipoIdentificacion"><strong>Tipo de identificaci&oacute;n:</strong></label>
		<select name="tipoIdentificacion" class="form-control">
            <option value="">Sin especificar</option>
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
					if($general_tipoIdentificacion == $PSN1->f('id'))
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
		<label class="form-label" for="direccion"><strong>Direcci&oacute;n</strong></label>
		<input name="direccion" type="text" id="direccion" value="<?=$general_direccion; ?>" class="form-control" /></div>	
	</div>
	

	
	<div class="form-group">
		<label class="form-label" for="telefono1"><strong>Tel&eacute;fono 1</strong></label>
		<input name="telefono1" type="tel" id="telefono1" maxlength="250" value="<?=$general_telefono1; ?>" class="form-control" /></div>

		<label class="form-label" for="telefono2"><strong>Tel&eacute;fono 2</strong></label>
		<input name="telefono2" type="tel" id="telefono2" maxlength="250" value="<?=$general_telefono2; ?>" class="form-control" /></div>
    </div>
	

	
	<div class="form-group">
		<label class="form-label" for="celular"><strong>Celular</strong></label>
		<input name="celular" type="tel" id="celular" maxlength="250" value="<?=$general_celular; ?>"  class="form-control"  /></div>	

		<label class="form-label" for="celular2"><strong>Celular 2</strong></label>
		<input name="celular2" type="tel" id="celular2" maxlength="250" value="<?=$general_celular; ?>"  class="form-control"  /></div>	
    </div>
			
	
	<div class="form-group">
		<label class="form-label" for="email"><strong>Email</strong></label>
		<input name="email" type="email" id="email" maxlength="250" value="<?=$general_email; ?>" class="form-control" /></div>

		<label class="form-label" for="url"><strong>P&aacute;gina</strong></label>
		<input name="url" type="text" id="url" maxlength="250" value="<?=$general_url; ?>" class="form-control" /></div>	
	</div>
		
	
	<div class="form-group">
		<label class="form-label" for="email"><strong>Observaciones</strong></label>
		<textarea name="observaciones" id="observaciones" class="form-control"  ><?=$general_observaciones; ?></textarea></div>

		<label class="form-label" for="archivo"><strong>Foto (200*200 pixeles - .jpg)</strong></label>
		<input name="archivo" type="file" id="archivo" class="form-control" />	
	</div>	

</div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "GENERAL" //-->

<div id="empresa" class="tab-pane fade">

	<div class="row">
		<h3 class="text-center well">.INFORMACIÓN EMPRESARIAL.</h3>
	</div>
    
    <?
    //  USUARIO INTERNO NO NECESITA TODOS LOS CAMPOS
    if($ctrl != 1){
        ?>
    <div class="form-group">
		<label class="form-label" for="empresa_tipo"><strong>Tipo de empresa:</strong></label>
		<select name="empresa_tipo" class="form-control">
            <option value="">Sin especificar</option>
			<?
			/*
			*	TRAEMOS LOS TIPOS DE CLIENTE/EMPRESA (15)
			*/
			$sql = "SELECT * ";
			$sql.=" FROM categorias ";
			$sql.=" WHERE idSec = 15 ORDER BY descripcion asc";


			$PSN1->query($sql);
			$numero=$PSN1->num_rows();
			if($numero > 0)
                {
                while($PSN1->next_record())
                {
                    ?><option value="<?=$PSN1->f('id'); ?>" <?
                    if($empresa_tipo == $PSN1->f('id'))
                    {
                        ?>selected="selected"<?
                    }
                    ?>><?=$PSN1->f('descripcion'); ?></option><?
                }
			}
			?>
			</select>		
	</div>

	<? /*<div class="form-group">
		<label class="form-label" for="empresa_nombre"><strong>Nombre empresa:</strong></label>
		<input name="empresa_nombre" type="text" id="empresa_nombre" maxlength="255" value="<?=$empresa_nombre; ?>" class="form-control" /></div>

		<label class="form-label" for="empresa_nit"><strong>NIT:</strong></label>
		<input name="empresa_nit" type="text" id="empresa_nit" maxlength="50" value="<?=$empresa_nit; ?>" class="form-control" /></div>		
	</div>*/ ?>
			
	<div class="form-group">
		<label class="form-label" for="empresa_representante"><strong>Representante legal:</strong></label>
		<input name="empresa_representante" type="text" id="empresa_representante" maxlength="255" value="<?=$empresa_representante; ?>" class="form-control" /></div>

		<label class="form-label" for="empresa_contacto"><strong>Nombre contacto:</strong></label>
		<input name="empresa_contacto" type="text" id="empresa_contacto" maxlength="255" value="<?=$empresa_contacto; ?>" class="form-control" /></div>		
	</div>
						
	<div class="form-group">
		<label class="form-label" for="empresa_direccion"><strong>Dirección:</strong></label>
		<input name="empresa_direccion" type="text" id="empresa_direccion" maxlength="255" value="<?=$empresa_direccion; ?>" class="form-control" /></div>

		<label class="form-label" for="empresa_url"><strong>Página Web:</strong></label>
		<input name="empresa_url" type="text" id="empresa_url" maxlength="255" value="<?=$empresa_url; ?>" class="form-control" /></div>
	</div>
    <?
    }
    ?>
    
	<div class="form-group">
		<label class="form-label" for="empresa_cargo"><strong>Cargo:</strong></label>
		<input name="empresa_cargo" type="text" id="empresa_cargo" maxlength="255" value="<?=$empresa_cargo; ?>" class="form-control" /></div>
	</div>
    
	
	<div class="form-group">
		<label class="form-label" for="empresa_telefono1"><strong>Tel&eacute;fono 1:</strong></label>
		<input name="empresa_telefono1" type="text" id="empresa_telefono1" maxlength="255" value="<?=$empresa_telefono1; ?>" class="form-control" required /></div>

		<label class="form-label" for="empresa_telefono2"><strong>Tel&eacute;fono 2:</strong></label>
		<input name="empresa_telefono2" type="text" id="empresa_telefono2" maxlength="255" value="<?=$empresa_telefono2; ?>" class="form-control" /></div>		
	</div>
  			
	<div class="form-group">
		<label class="form-label" for="empresa_celular1"><strong>Celular 1:</strong></label>
		<input name="empresa_celular1" type="text" id="empresa_celular1" maxlength="255" value="<?=$empresa_celular1; ?>" class="form-control" /></div>

		<label class="form-label" for="empresa_celular2"><strong>Celular 2:</strong></label>
		<input name="empresa_celular2" type="text" id="empresa_celular2" maxlength="255" value="<?=$empresa_celular2; ?>" class="form-control" /></div>		
	</div>  		
    
	<div class="form-group">
		<label class="form-label" for="empresa_email1"><strong>Email 1:</strong></label>
		<input name="empresa_email1" type="text" id="empresa_email1" maxlength="255" value="<?=$empresa_email1; ?>" class="form-control" /></div>

		<label class="form-label" for="empresa_email2"><strong>Email 2:</strong></label>
		<input name="empresa_email2" type="text" id="empresa_email2" maxlength="255" value="<?=$empresa_email2; ?>" class="form-control" /></div>		
	</div>
    
			    
    
</div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "EMPRESA" //-->
    
    
<?
//  USUARIO PROVEEDOR
if($ctrl == 3){
    ?><div id="servicios" class="tab-pane fade">

	<div class="row">
		<h3 class="text-center well">.INFORMACIÓN DE PROVEEDOR.</h3>
	</div>

    <div class="form-group">
        <label class="form-label" for="servicios_tipoPersona"><strong>Tipo de persona:</strong></label>
        <select name="servicios_tipoPersona" class="form-control">
            <option value="">Sin especificar</option>
            <?
            /*
            *	TRAEMOS LOS TIPOS DE PERSONA JURIDICA O NATURAL
            */
            $sql = "SELECT * ";
            $sql.=" FROM categorias ";
            $sql.=" WHERE idSec = 29 ORDER BY descripcion asc";

            $PSN1->query($sql);
            $numero=$PSN1->num_rows();
            if($numero > 0)
                {
                while($PSN1->next_record())
                {
                    ?><option value="<?=$PSN1->f('id'); ?>" <?
                    if($servicios_tipoPersona == $PSN1->f('id'))
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
		<label class="form-label" for="servicios_tipo1"><strong>Tipo de servicio:</strong></label>
		<select name="servicios_tipo1" class="form-control">
            <option value="">Sin especificar</option>
			<?
			/*
			*	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
			*/
			$sql = "SELECT * ";
			$sql.=" FROM categorias ";
			$sql.=" WHERE idSec = 25 ORDER BY descripcion asc";

			$PSN1->query($sql);
			$numero=$PSN1->num_rows();
			if($numero > 0)
                {
                while($PSN1->next_record())
                {
                    ?><option value="<?=$PSN1->f('id'); ?>" <?
                    if($servicios_tipo1 == $PSN1->f('id'))
                    {
                        ?>selected="selected"<?
                    }
                    ?>><?=$PSN1->f('descripcion'); ?></option><?
                }
			}
			?>
			</select></div>	
        
            <label class="form-label" for="servicios_tipo2"><strong>Tipo de servicio 2:</strong></label>
            <select name="servicios_tipo2" class="form-control">
                <option value="">Sin especificar</option>
                <?
                /*
                *	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                */
                $sql = "SELECT * ";
                $sql.=" FROM categorias ";
                $sql.=" WHERE idSec = 25 ORDER BY descripcion asc";

                $PSN1->query($sql);
                $numero=$PSN1->num_rows();
                if($numero > 0)
                    {
                    while($PSN1->next_record())
                    {
                        ?><option value="<?=$PSN1->f('id'); ?>" <?
                        if($servicios_tipo2 == $PSN1->f('id'))
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
		<label class="form-label" for="servicios_contrato1"><strong>Tipo de contrato:</strong></label>
		<select name="servicios_contrato1" class="form-control">
            <option value="">Sin especificar</option>            
			<?
			/*
			*	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
			*/
			$sql = "SELECT * ";
			$sql.=" FROM categorias ";
			$sql.=" WHERE idSec = 26 ORDER BY descripcion asc";

			$PSN1->query($sql);
			$numero=$PSN1->num_rows();
			if($numero > 0)
                {
                while($PSN1->next_record())
                {
                    ?><option value="<?=$PSN1->f('id'); ?>" <?
                    if($servicios_contrato1 == $PSN1->f('id'))
                    {
                        ?>selected="selected"<?
                    }
                    ?>><?=$PSN1->f('descripcion'); ?></option><?
                }
			}
			?>
			</select></div>	
        
            <label class="form-label" for="servicios_contrato2"><strong>Tipo de contrato 2:</strong></label>
            <select name="servicios_contrato2" class="form-control">
                <option value="">Sin especificar</option>
                <?
                /*
                *	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                */
                $sql = "SELECT * ";
                $sql.=" FROM categorias ";
                $sql.=" WHERE idSec = 26 ORDER BY descripcion asc";

                $PSN1->query($sql);
                $numero=$PSN1->num_rows();
                if($numero > 0)
                    {
                    while($PSN1->next_record())
                    {
                        ?><option value="<?=$PSN1->f('id'); ?>" <?
                        if($servicios_contrato2 == $PSN1->f('id'))
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
		<label class="form-label" for="servicios_observaciones"><strong>Ampliacion de los servicios prestados:</strong></label>
		<textarea name="servicios_observaciones" id="servicios_observaciones" class="form-control"  ><?=$servicios_observaciones; ?></textarea>
    </div>
    
    
	<div class="row">
		<h3 class="text-center well">.FECHAS DE VIGENCIA.</h3>
	</div>

	<div class="form-group">
		<label class="form-label" for="servicios_fechaInicio"><strong>Fecha de inicio:</strong></label>
		<input name="servicios_fechaInicio"  type="date"  placeholder="AAAA-MM-DD" id="servicios_fechaInicio" value="<?=$servicios_fechaInicio; ?>" class="form-control" /></div>

		<label class="form-label" for="servicios_fechaFin"><strong>Fecha final:</strong></label>
		<input name="servicios_fechaFin"  type="date" placeholder="AAAA-MM-DD" id="servicios_fechaFin"  value="<?=$servicios_fechaFin; ?>" class="form-control" /></div>		
	</div>    


    </div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "SERVICOS" //-->
<? 
}    

//  USUARIO cliente
if($ctrl == 2){
    ?><div id="cliente" class="tab-pane fade">

	<div class="row">
		<h3 class="text-center well">.INFORMACIÓN DE CLIENTE.</h3>
	</div>

     <div class="form-group">
		<label class="form-label" for="cliente_tipoPersona"><strong>Tipo de persona:</strong></label>
		<select name="cliente_tipoPersona" class="form-control">
            <option value="">Sin especificar</option>
			<?
			/*
			*	TRAEMOS LOS TIPOS DE PERSONA JURIDICA O NATURAL
			*/
			$sql = "SELECT * ";
			$sql.=" FROM categorias ";
			$sql.=" WHERE idSec = 29 ORDER BY descripcion asc";

			$PSN1->query($sql);
			$numero=$PSN1->num_rows();
			if($numero > 0)
                {
                while($PSN1->next_record())
                {
                    ?><option value="<?=$PSN1->f('id'); ?>" <?
                    if($cliente_tipoPersona == $PSN1->f('id'))
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
		<label class="form-label" for="cliente_tipo1"><strong>Tipo de servicio:</strong></label>
		<select name="cliente_tipo1" class="form-control">
            <option value="">Sin especificar</option>
			<?
			/*
			*	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
			*/
			$sql = "SELECT * ";
			$sql.=" FROM categorias ";
			$sql.=" WHERE idSec = 27 ORDER BY descripcion asc";

			$PSN1->query($sql);
			$numero=$PSN1->num_rows();
			if($numero > 0)
                {
                while($PSN1->next_record())
                {
                    ?><option value="<?=$PSN1->f('id'); ?>" <?
                    if($cliente_tipo1 == $PSN1->f('id'))
                    {
                        ?>selected="selected"<?
                    }
                    ?>><?=$PSN1->f('descripcion'); ?></option><?
                }
			}
			?>
			</select></div>	

		<label class="form-label" for="cliente_servicio1"><strong>Tipo de contrato:</strong></label>
		<select name="cliente_servicio1" class="form-control">
            <option value="">Sin especificar</option>            
			<?
			/*
			*	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
			*/
			$sql = "SELECT * ";
			$sql.=" FROM categorias ";
			$sql.=" WHERE idSec = 28 ORDER BY descripcion asc";

			$PSN1->query($sql);
			$numero=$PSN1->num_rows();
			if($numero > 0)
                {
                while($PSN1->next_record())
                {
                    ?><option value="<?=$PSN1->f('id'); ?>" <?
                    if($cliente_servicio1 == $PSN1->f('id'))
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
		<label class="form-label" for="cliente_observaciones"><strong>Ampliacion de los servicios ofrecidos:</strong></label>
		<textarea name="cliente_observaciones" id="cliente_observaciones" class="form-control"  ><?=$cliente_observaciones; ?></textarea>
    </div>
    
	<div class="form-group">
		<label class="form-label" for="cliente_valor1"><strong>Valor del contrato:</strong></label>
		<input name="cliente_valor1" type="text" id="cliente_valor1" maxlength="255" value="<?=$cliente_valor1; ?>" class="form-control" /></div>

		<label class="form-label" for="cliente_diaPago"><strong>Día de pago:</strong></label>
		<input name="cliente_diaPago" type="number" id="cliente_diaPago" value="<?=$cliente_diaPago; ?>" class="form-control" /></div>		
	</div>
    
	<div class="row">
		<h3 class="text-center well">.FECHAS DE VIGENCIA.</h3>
	</div>

	<div class="form-group">
		<label class="form-label" for="cliente_fechaAprob"><strong>Fecha de aprobación cliente:</strong></label>
		<input name="cliente_fechaAprob"  type="date"  placeholder="AAAA-MM-DD" id="cliente_fechaAprob" value="<?=$cliente_fechaAprob; ?>" class="form-control" /></div>

		<label class="form-label" for="cliente_fechaAprobCont"><strong>Fecha aprobación contrato:</strong></label>
		<input name="cliente_fechaAprobCont"  type="date" placeholder="AAAA-MM-DD" id="cliente_fechaAprobCont"  value="<?=$cliente_fechaAprobCont; ?>" class="form-control" /></div>		
	</div>    
    
    
	<div class="form-group">
		<label class="form-label" for="cliente_fechaInicial"><strong>Fecha de inicio contrato:</strong></label>
		<input name="cliente_fechaInicial"  type="date"  placeholder="AAAA-MM-DD" id="cliente_fechaInicial" value="<?=$cliente_fechaInicial; ?>" class="form-control" /></div>

		<label class="form-label" for="cliente_fechaFinal"><strong>Fecha final contrato:</strong></label>
		<input name="cliente_fechaFinal"  type="date" placeholder="AAAA-MM-DD" id="cliente_fechaFinal"  value="<?=$cliente_fechaFinal; ?>" class="form-control" /></div>		
	</div>    
    

</div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "SERVICOS" //-->    
<?
}    
//  USUARIO cliente o proveedor
if($ctrl == 1 || $ctrl == 2 || $ctrl == 3){
    ?><div id="archivos" class="tab-pane fade">

	<div class="row">
		<h3 class="text-center well">.DOCUMENTOS.</h3>
	</div>
    
	<div class="form-group">
		<label class="form-label" for="documento_identificacion"><strong>Identificación:</strong></label>
		<input name="documento_identificacion" type="file" id="documento_identificacion"  class="form-control" /></div>

		<label class="form-label" for="documento_contrato"><strong>Contrato:</strong></label>
		<input name="documento_contrato" type="file" id="documento_contrato"  class="form-control" />		
	</div>    
    <?
    if($ctrl == 2 || $ctrl == 3){
    ?>
	<div class="form-group">
		<label class="form-label" for="documento_rut"><strong>RUT:</strong></label>
		<input name="documento_rut" type="file" id="documento_rut"  class="form-control" /></div>		

        <label class="form-label" for="documento_constitucion"><strong>Constitución:</strong></label>
		<input name="documento_constitucion" type="file" id="documento_constitucion"  class="form-control" />
	</div>
    <?
    }
    ?>

    
	<div class="row">
		<h3 class="text-center well">.AGREGAR DOCUMENTOS ADICIONALES.</h3>
	</div>

	<div class="form-group">
		<label class="form-label" for="documento_adicional_nom"><strong>Descripción del documento:</strong></label>
		<input name="documento_adicional_nom" type="text" id="documento_adicional_nom"  placeholder="Descripción del documento que va a agregar" class="form-control" /></div>

		<label class="form-label" for="documento_adicional_file"><strong>Archivo:</strong></label>
		<input name="documento_adicional_file" type="file" id="documento_adicional_file"  class="form-control" />
	</div>
    

    <div class="row">
		<h3 class="text-center well">.DOCUMENTOS CARGADOS.</h3>
	</div>
    
    <table class="table table-striped table-hover">
        <tr class='clickable-row' data-href='descarga_usuario.php?id=&archivo=1'>
            <td>Documento de Identificación</td>
        </tr>
        <tr class='clickable-row' data-href='descarga_usuario.php?id=&archivo=2'>
            <td>RUT</td>
        </tr>
        <tr class='clickable-row' data-href='descarga_usuario.php?id=&archivo=3'>
            <td>Constitución</td>
        </tr>
        <tr class='clickable-row' data-href='descarga_usuario.php?id=&archivo=4'>
            <td>Contrato</td>
        </tr>
        <tr class='clickable-row' data-href='descarga_usuario.php?id=&archivo=999999'>
            <td>Adicional - Fotocopia de personeria</td>
        </tr>
    </table>

</div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "DOCUMENTOS" //-->    
    <?
}

?>
<div id="accesos" class="tab-pane fade">

			
	<div class="row">
		<h3 class="text-center well">.ACCESO AL SISTEMA.</h3>
	</div>
	
	<div class="row">
        <h5 class="alert alert-warning text-center">Su login será su identificación de la pestaña general</h5>
    </div>

	<div class="form-group">
        <label class="form-label" for="password"><strong>Acceso al sistema:</strong></label>
        <input name="acceso" type="checkbox" id="acceso" value="1" class="form-control" />	
    </div>
    
	<div class="form-group">
		<label class="form-label" for="password"><strong>Password</strong></label>
		<input name="password" type="password" id="password" class="form-control" minlength="8" /></div>	
		<label class="form-label" for="password_check"><strong>Repita el password</strong></label>
		<input name="password_check" type="password" id="password_check" maxlength="250" value="" class="form-control" minlength="8" />
	</div>
<?
//
//  USUARIO interno
//
if($ctrl == 1){
    ?><div class="row">
		<h3 class="text-center well">.ACCESOS AL MENU.</h3>
	</div>
	
		<?
		/*
		*	ITEMS DEL MENU
		*/
		$sql = "SELECT * ";
		$sql.=" FROM menu ";
		$sql.=" ORDER BY principal, orden asc";
		//
		$PSN1->query($sql);
		$numero=$PSN1->num_rows();
		if($numero > 0)
		{
			$cont = 0;
			
			$principal_old = 0;
			while($PSN1->next_record())
			{
				if($cont == 2){
					?></div><!-- CLOSE INSIDE //--><?
				}
                
				if($principal_old != $PSN1->f("principal"))
				{
                    
                    if($principal_old != 0 && $cont != 2){
                        ?></div><!-- CLOSE INSIDE //--><?
                    }

                    ?><!-- OPEN //--><div class="form-group">
                    <h5 class="alert alert-info"><?
                    
					$principal_old = $PSN1->f("principal");
					//
					switch($PSN1->f("principal")){
						case 1:
							echo "Administración del Sistema";
							break;
						case 2:
							echo "SMS";
							break;
                        case 20:
							echo "Emailing";
							break;
						case 3:
							echo "Cotizaciones";
							break;
						case 99:
							echo "Mi cuenta";
							break;
						default:
							echo "Otras opciones";
							break;
					}
                   ?></h5></div></div><!-- CLOSE INSIDE //-->
		
											
					<!-- OPEN INSIDE //--><div class="form-group"><?
                    $cont = 0;
				}			
				
				
                if($cont == 2){
					?><!-- OPEN INSIDE //--><div class="form-group"><?
					$cont = 0;
				}

				?>
				<label class="form-label" for="login"><img  src="images/png/<?=$PSN1->f('imagen'); ?>" border="0" height="20px" align="left" /> <strong><?=$PSN1->f('nombre'); ?></strong></label>
				<input type="checkbox" name="menu[]" value="<?=$PSN1->f('id'); ?>" class="form-control" /></div>
				<?
				$cont++;
			}
			?></div><?
		}
		?>
    </div> <!-- FIN TAB DE ACCESOS //-->
<?
}
?>

</div> <!-- FIN TABS DIV //-->
</div> <!-- FIN CONTENEDOR DE TABS //-->
	
    <input type="hidden" name="funcion" id="funcion" value="" />
    <center><input type="submit" name="button" value="Guardar cambios" class="btn btn-success"></center>
		
</form>
</div>
    
<script language="javascript">
	function generarForm(){
			if(confirm("Esta accion generara el ACCESO en el sistema, ¿esta seguro que desea continuar?"))
			{
				if(document.getElementById('nombre').value != "" 
				&& document.getElementById('identificacion').value != ""
				)
				{
                    if(document.getElementById('password').value != "")
                    {
                        if(document.getElementById('password').value != document.getElementById('password_check').value)
                        {
                            $("#password").focus();
                            alert("Password no coincide");
                            return false;
                        }
                        else{
                            //document.getElementById('form1').submit();
                        }
                    }
                    document.getElementById('funcion').value = "insertar";
				}
				else
				{
					alert("La informacion es primordial para brindarle un excelente servicio, por favor digite al menos los campos de NOMBRE e IDENTIFICACIÓN");
                    return false;
				}
			}else{
                return false;
            }
            return true;
	}
	function init(){
		document.getElementById('form1').onsubmit = function(){
				return generarForm();
		}

		<?
		if($varExitoUSU == 1)
		{
			?>alert("Se ha colocado correctamente el ACCESO, espere mientras es dirigido.");
			window.location.href = "index.php?doc=admin_usu4&id=<?=$ultimoId;?>";<?
		}
		?>
	}

    jQuery(document).ready(function($) {
        $(".clickable-row").click(function() {
            window.open($(this).data("href"), "_blank");
        });
    });    
    
	window.onload = function(){
		init();
	}
    </script><?
}
?>