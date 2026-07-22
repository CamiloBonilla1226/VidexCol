    <?php
//Si es un usuario externo o cliente o proveedor NO mostrar.
if($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 4 || $_SESSION["perfil"] == 160){
	die("<h1>No esta autorizado para ver esta información</h1>");
}

// Objeto de Base de Datos
$PSN2 = new DBbase_Sql;
$PSN1 = new DBbase_Sql;
$PSN = new DBbase_Sql;
$webArchivo = "usuario";
  
function eliminar_tildes($cadena){
    $originales = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ';
    $modificadas = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyyby';
    $cadena = utf8_decode($cadena);
    $cadena = strtr($cadena, utf8_decode($originales), $modificadas);
    return utf8_encode($cadena);
}
/*
*   AFECTA FORMULARIO Y ACTUAR DE LA PÁGINA
    1   USUARIO INTERNO
    2   CLIENTE
    3   PROVEEDOR
    4   USUARIO CLIENTE
*/
if(!isset($_REQUEST["ctrl"]) || soloNumeros($_REQUEST["ctrl"]) == "" || soloNumeros($_REQUEST["ctrl"]) == "0"){
    $ctrl = 1;
}else{
    $ctrl = soloNumeros($_REQUEST["ctrl"]);
}

// Array que nos servira para ir llevando cuenta de los requerimientos.
$arrayRequerimientos = array();
if(isset($_POST["funcion"])){
    /*
    *   Para verificar errores a futuro.
        1   Campos requeridos en BLANCO (Nombre, identificacion, password)
        2   Password no coincide
        3   Identificacion YA existente
    */
    $error_datos = 0;

	if($_POST["funcion"] == "insertar"){
        /*
        *   PESTAÑA GENERAL
        */
        $general_nombre = eliminarInvalidos($_POST["nombre"]);
        $general_tipo = soloNumeros($_POST["tipo"]);
        if (!empty($_POST["tipo_user_cli"])) {
            $general_tipo_user_cli = soloNumeros($_POST["tipo_user_cli"]);
        }else{
            $general_tipo_user_cli = 0;
        }
        
        //
        //
        $general_identificacion = eliminarInvalidos($_POST["identificacion"]);
        $general_tipoIdentificacion = soloNumeros($_POST["tipoIdentificacion"]);
        if (empty($_POST["municipio"])) {
            $general_municipio = 0;
        }else{
            $general_municipio = soloNumeros($_POST["municipio"]);
        }
        
        $general_direccion = eliminarInvalidos($_POST["direccion"]); 
        $general_telefono1 = soloNumeros($_POST["telefono1"]);
        $general_celular = soloNumeros($_POST["celular"]);

        $general_email = eliminarInvalidos($_POST["email"]);
        $general_url = eliminarInvalidos($_POST["url"]);
        $general_url2 = eliminarInvalidos($_POST["url2"]);
        $general_observaciones = eliminarInvalidos($_POST["observaciones"]);
        $general_password = eliminarInvalidos($_POST["password"]);
        if (!empty($_POST["acceso"])) {
            $general_acceso = eliminarInvalidos($_POST["acceso"]);
        }else{
            $general_acceso = 1;
        }
        
        if (!empty($_POST["acceso_graphs"])) {
            $general_acceso_graphs = eliminarInvalidos($_POST["acceso_graphs"]);
        }else{
            $general_acceso_graphs = 1;
        }
        
        //
        $temp_password_check = eliminarInvalidos($_POST["password_check"]);
        
        //
        $idCliente = soloNumeros($_POST["idCliente"]);

        /*
        *   ARCHIVO FOTO
        */
		$nombre_archivo = $_FILES['archivo']['name'];
		$tipo_archivo = $_FILES['archivo']['type'];
		$tamano_archivo = $_FILES['archivo']['size'];

        
        if($general_nombre == "" || $general_identificacion == ""){
            $error_datos = 1; //Datos requeridos en blanco
        }
        
        if($general_password != "" && $general_password != $temp_password_check){
            $error_datos = 2;   //Password diferente
        }
        
        /*
        *   COMPROBAMOS QUE IDENTIFICACION NO EXISTA
        */
        $sql= "SELECT id ";
        $sql.=" FROM usuario";
        $sql.=" WHERE identificacion = '".$general_identificacion."'";
        $PSN->query($sql);
        if($PSN->next_record()){
            $error_datos = 3;   //Identificacion ya existe
        }
        
        if($error_datos == 0){
            /*
            *	DEBEMOS INSERTAR LA INFORMACION DEL CLIENTE/USUARIO SEGUN CORRESPONDA.
            */
            $sql = 'INSERT INTO usuario (
                nombre,
                tipo,
                tipo_user_cli,
                identificacion,
                tipoIdentificacion,
                usua_pais,
                usua_muni,
                direccion,
                telefono1,
                celular,
                email,
                url,
                url2,
                observaciones,
                acceso,
                acceso_graphs,
                password,
                creacionUsuario,
                creacionFecha
            ) ';
            $sql .= ' values 
                (
                "'.$general_nombre.'", 
                "'.$general_tipo.'", 
                '.$general_tipo_user_cli.', 
                "'.$general_identificacion.'", 
                '.$general_tipoIdentificacion.',
                "'.$general_pais.'",
                '.$general_municipio.', 
                "'.$general_direccion.'", 
                "'.$general_telefono1.'", 
                "'.$general_celular.'", 
                "'.$general_email.'", 
                "'.$general_url.'", 
                "'.$general_url2.'", 
                "'.$general_observaciones.'", 
                "'.$general_acceso.'", 
                "'.$general_acceso_graphs.'", 
                "'.md5($general_password).'",
                "'.$_SESSION["id"].'",
                NOW()
            ) ';
            $ultimoQuery = $PSN1->query($sql);
            $ultimoId =  $PSN1->ultimoId();
            
            //echo $ultimoId ;

            /*
            *   SE INSERTO EL USUARIO CORRECTAMENTE.
            */
            if($ultimoId > 0){
                /*
                *   INSERTAMOS INFORMACIÓN EMPRESARIAL
                */
                $empresa_tipo = soloNumeros($_POST["empresa_tipo"]);
                //$empresa_nombre = eliminarInvalidos($_POST["empresa_nombre"]);
                //$empresa_nit = eliminarInvalidos($_POST["empresa_nit"]);
                $empresa_representante = eliminarInvalidos($_POST["empresa_representante"]);
                $empresa_contacto = eliminarInvalidos($_POST["empresa_contacto"]);
                $empresa_direccion = eliminarInvalidos($_POST["empresa_direccion"]);
                $empresa_url = eliminarInvalidos($_POST["empresa_url"]);
                $empresa_telefono1 = eliminarInvalidos($_POST["empresa_telefono1"]);
                $empresa_telefono2 = eliminarInvalidos($_POST["empresa_telefono2"]);
                $empresa_celular1 = eliminarInvalidos($_POST["empresa_celular1"]);
                $empresa_celular2 = eliminarInvalidos($_POST["empresa_celular2"]);
                $empresa_email1 = eliminarInvalidos($_POST["empresa_email1"]);
                $empresa_email2 = eliminarInvalidos($_POST["empresa_email2"]);
                $empresa_cargo = eliminarInvalidos($_POST["empresa_cargo"]);                
                $empresa_aprobacion = soloNumeros($_POST["empresa_aprobacion"]);                
                
                
                    $empresa_paisid = soloNumeros($_POST["empresa_paisid"]);
                
                    $empresa_pais = eliminarInvalidos($_POST["empresa_pais"]);
                    $empresa_socio = eliminarInvalidos($_POST["empresa_socio"]);
                    $empresa_proceso = eliminarInvalidos($_POST["empresa_proceso"]);
                    $empresa_pd = soloNumeros($_POST["empresa_pd"]);
                    $empresa_sitio_cor = eliminarInvalidos($_POST["empresa_sitio_cor"]);
                    $empresa_sitio = eliminarInvalidos($_POST["empresa_sitio"]);
                    $empresa_rm = eliminarInvalidos($_POST["empresa_rm"]);
                    $empresa_circuito = eliminarInvalidos($_POST["empresa_circuito"]);
                

                
                
                
                $sql = 'INSERT INTO usuario_empresa (
                    idUsuario,
                    empresa_tipo,
                    empresa_nombre,
                    empresa_nit,
                    empresa_representante,
                    empresa_contacto,
                    empresa_direccion,
                    empresa_url,
                    empresa_telefono1,
                    empresa_telefono2,
                    empresa_celular1,
                    empresa_celular2,
                    empresa_email1,
                    empresa_email2,
                    empresa_cargo,
                    empresa_aprobacion,
                        empresa_paisid,
                        empresa_pais,
                        empresa_socio,
                        empresa_proceso,
                        empresa_pd,
                        empresa_sitio_cor,
                        empresa_sitio,
                        empresa_rm,
                        empresa_circuito
                ) ';
                $sql .= ' values 
                    (
                    "'.$ultimoId.'", 
                    "'.$empresa_tipo.'",
                    "'.$empresa_nombre.'",
                    "'.$empresa_nit.'",
                    "'.$empresa_representante.'",
                    "'.$empresa_contacto.'",
                    "'.$empresa_direccion.'",
                    "'.$empresa_url.'",
                    "'.$empresa_telefono1.'",
                    "'.$empresa_telefono2.'",
                    "'.$empresa_celular1.'",
                    "'.$empresa_celular2.'",
                    "'.$empresa_email1.'",
                    "'.$empresa_email2.'",
                    "'.$empresa_cargo.'",
                    "'.$empresa_aprobacion.'",
                        "'.$empresa_paisid.'", 
                        "'.$empresa_pais.'", 
                        "'.$empresa_socio.'", 
                        "'.$empresa_proceso.'", 
                        '.$empresa_pd.', 
                        "'.$empresa_sitio_cor.'", 
                        "'.$empresa_sitio.'",
                        "'.$empresa_rm.'",
                        "'.$empresa_circuito.'"                                                
                ) ';
                $ultimoQuery = $PSN1->query($sql);
                
                /*
                *   CARGUE DE PESTAÑA CLIENTE
                */
                if($ctrl == 2){
                    $cliente_tipo1 = soloNumeros($_POST["cliente_tipo1"]);
                    $cliente_servicio1 = soloNumeros($_POST["cliente_servicio1"]);
                    $cliente_observaciones = eliminarInvalidos($_POST["cliente_observaciones"]);
                    $cliente_valor1 = soloNumeros($_POST["cliente_valor1"]);
                    $cliente_diaPago = soloNumeros($_POST["cliente_diaPago"]);
                    $cliente_fechaAprob = eliminarInvalidos($_POST["cliente_fechaAprob"]);
                    $cliente_fechaAprobCont = eliminarInvalidos($_POST["cliente_fechaAprobCont"]);
                    $cliente_fechaInicial = eliminarInvalidos($_POST["cliente_fechaInicial"]);
                    $cliente_fechaFinal = eliminarInvalidos($_POST["cliente_fechaFinal"]);
                    $cliente_tipoPersona = soloNumeros($_POST["cliente_tipoPersona"]);
                    //
                    //
                    $sql = 'INSERT INTO usuario_cliente (
                        idUsuario,
                        cliente_tipo1,
                        cliente_servicio1,
                        cliente_observaciones,
                        cliente_valor1,
                        cliente_diaPago,
                        cliente_fechaAprob,
                        cliente_fechaAprobCont,
                        cliente_fechaInicial,
                        cliente_fechaFinal,
                        cliente_tipoPersona
                    ) ';
                    $sql .= ' values 
                        (
                        "'.$ultimoId.'", 
                        "'.$cliente_tipo1.'",
                        "'.$cliente_servicio1.'",
                        "'.$cliente_observaciones.'",
                        "'.$cliente_valor1.'",
                        "'.$cliente_diaPago.'",
                        "'.$cliente_fechaAprob.'",
                        "'.$cliente_fechaAprobCont.'",
                        "'.$cliente_fechaInicial.'",
                        "'.$cliente_fechaFinal.'",
                        "'.$cliente_tipoPersona.'"
                    ) ';
                    $ultimoQuery = $PSN1->query($sql);
                }

                /*
                *   CARGUE DE PESTAÑA PROVEEDOR
                */
                if($ctrl == 3){
                    $servicios_tipo1 = soloNumeros($_POST["servicios_tipo1"]);
                    $servicios_tipo2 = soloNumeros($_POST["servicios_tipo2"]);
                    $servicios_contrato1 = soloNumeros($_POST["servicios_contrato1"]);
                    $servicios_contrato2 = soloNumeros($_POST["servicios_contrato2"]);
                    $servicios_observaciones = eliminarInvalidos($_POST["servicios_observaciones"]);
                    $servicios_fechaInicio = eliminarInvalidos($_POST["servicios_fechaInicio"]);
                    $servicios_fechaFin = eliminarInvalidos($_POST["servicios_fechaFin"]);
                    $servicios_tipoPersona = soloNumeros($_POST["servicios_tipoPersona"]);
                    $servicios_porcentaje = soloNumeros($_POST["servicios_porcentaje"]);
                    
                    //
                    //
                    $sql = 'INSERT INTO usuario_servicios (
                        idUsuario,
                        servicios_tipo1,
                        servicios_tipo2,
                        servicios_contrato1,
                        servicios_contrato2,
                        servicios_observaciones,
                        servicios_fechaInicio,
                        servicios_fechaFin,
                        servicios_tipoPersona,
                        servicios_porcentaje
                    ) ';
                    $sql .= ' values 
                        (
                        "'.$ultimoId.'", 
                        "'.$servicios_tipo1.'",
                        "'.$servicios_tipo2.'",
                        "'.$servicios_contrato1.'",
                        "'.$servicios_contrato2.'",
                        "'.$servicios_observaciones.'",
                        "'.$servicios_fechaInicio.'",
                        "'.$servicios_fechaFin.'",
                        "'.$servicios_tipoPersona.'",
                        "'.$servicios_porcentaje.'"
                        
                    ) ';
                    $ultimoQuery = $PSN1->query($sql);
                }
                
                /*
                *   CARGUE DE RELACION USUARIO-CLIENTE
                */
                if($ctrl == 4 && $idCliente != 0 && $idCliente != ""){
                    //
                    $sql = 'INSERT INTO usuario_relacion (
                        idUsuario1,
                        idUsuario2
                    ) ';
                    $sql .= ' values 
                        (
                        "'.$ultimoId.'", 
                        "'.$idCliente.'"
                    ) ';
                    $ultimoQuery = $PSN1->query($sql);
                }                
                
                /*
                *   INSERTAMOS ACCESOS AL SISTEMA.
                */
                foreach($_POST["menu"] as $menuopc){				//
                    $sql ="REPLACE INTO usuarios_menu (idUsuario, idMenu) VALUES (".$ultimoId.", ".soloNumeros($menuopc).")";
                    $PSN1->query($sql);
                }
                
                /*
                *   INSERTAMOS ACCESOS DE GRAFICAS AL SISTEMA.
                */
                foreach($_POST["menu_graphs"] as $menuopc){				//
                    $sql ="REPLACE INTO usuarios_menu_graphs (idUsuario, idMenu) VALUES (".$ultimoId.", ".soloNumeros($menuopc).")";
                    $PSN1->query($sql);
                }
                
                //Compruebo si las características del archivo son las que deseo
                if(move_uploaded_file($_FILES['archivo']['tmp_name'], "images/usuarios/".$ultimoId.".jpg"))
                {                    
                }

                /*
                *   GENERAMOS PREVENTIVAMENTE EL REGISTRO DE LOS DOCUMENTOS
                */
                $sql ="INSERT INTO usuario_documentos (idUsuario) VALUES (".$ultimoId.")";
                $PSN1->query($sql);

                //
                //  Documento de IDENTIFICACIÓN
                //
                $nombre_archivo = $_FILES['documento_identificacion']['name'];
                $temp_location = $_FILES['documento_identificacion']['tmp_name'];
                $temp_ext = extension_archivo($nombre_archivo);
                $temp_nombreFile = "id".$ultimoId.".".$temp_ext;
                

                if(move_uploaded_file($temp_location, "archivos/usuarios/".$temp_nombreFile))
                {
                    $sql ="UPDATE usuario_documentos SET 
                        documento_identificacion = '".$temp_nombreFile."'
                        WHERE idUsuario = '".$ultimoId."'";
                    $PSN1->query($sql);
                }

                //
                //  Documento de RUT
                //
                $nombre_archivo = $_FILES['documento_rut']['name'];
                $temp_location = $_FILES['documento_rut']['tmp_name'];
                $temp_ext = extension_archivo($nombre_archivo);
                $temp_nombreFile = "rut".$ultimoId.".".$temp_ext;
                
                if(move_uploaded_file($temp_location, "archivos/usuarios/".$temp_nombreFile))
                {
                    $sql ="UPDATE usuario_documentos SET 
                        documento_rut = '".$temp_nombreFile."'
                        WHERE idUsuario = '".$ultimoId."'";
                    $PSN1->query($sql);
                }

                //
                //  Documento de CONSTITUCION
                //
                $nombre_archivo = $_FILES['documento_constitucion']['name'];
                $temp_location = $_FILES['documento_constitucion']['tmp_name'];
                $temp_ext = extension_archivo($nombre_archivo);
                $temp_nombreFile = "cons".$ultimoId.".".$temp_ext;
                
                if(move_uploaded_file($temp_location, "archivos/usuarios/".$temp_nombreFile))
                {
                    $sql ="UPDATE usuario_documentos SET 
                        documento_constitucion = '".$temp_nombreFile."'
                        WHERE idUsuario = '".$ultimoId."'";
                    $PSN1->query($sql);
                }

                //
                //  Documento de CONTRATO
                //
                $nombre_archivo = $_FILES['documento_contrato']['name'];
                $temp_location = $_FILES['documento_contrato']['tmp_name'];
                $temp_ext = extension_archivo($nombre_archivo);
                $temp_nombreFile = "contrato".$ultimoId.".".$temp_ext;
                
                if(move_uploaded_file($temp_location, "archivos/usuarios/".$temp_nombreFile))
                {
                    $sql ="UPDATE usuario_documentos SET 
                        documento_contrato = '".$temp_nombreFile."'
                        WHERE idUsuario = '".$ultimoId."'";
                    $PSN1->query($sql);
                }

                //
                //  Documento ADICIONAL
                //
                $nom_doc = str_replace(' ', '',$_REQUEST["documento_adicional_nom"]);
                $usuari_spa = str_replace(' ', '-',$general_nombre);
                $nom_usu = eliminar_tildes($usuari_spa);
                $nombre_archivo = $_FILES['documento_adicional_file']['name'];
                $temp_location = $_FILES['documento_adicional_file']['tmp_name'];
                $temp_ext = extension_archivo($nombre_archivo);
                $temp_nombreFile = $nom_doc."_".$nom_usu."-".$general_identificacion.".".$temp_ext;
                
                if(move_uploaded_file($temp_location, "archivos/usuarios/".$temp_nombreFile))
                {
                    $sql ="INSERT INTO usuario_documentos_add 
                            (
                            idUsuario, 
                            descripcion, 
                            archivo
                        ) 
                        VALUES 
                            (
                            ".$ultimoId.", 
                            '".eliminarInvalidos($_REQUEST["documento_adicional_nom"])."', 
                            '".$temp_nombreFile."'
                    )";
                    $PSN1->query($sql);
                    //
                }
            }
            $varExitoUSU = 1;
        }
	}else if($_POST["funcion"] == "actualizar"){
        $idUsuarioActual = soloNumeros($_REQUEST["id"]);
        /*
        *   PESTAÑA GENERAL
        */
        $general_nombre = eliminarInvalidos($_POST["nombre"]);
        $general_tipo = soloNumeros($_POST["tipo"]);
        if (!empty($_POST["tipo_user_cli"])) {
            $general_tipo_user_cli = soloNumeros($_POST["tipo_user_cli"]);
        }else{
            $general_tipo_user_cli = 0;
        }
        $general_identificacion = eliminarInvalidos($_POST["identificacion"]);
        $general_tipoIdentificacion = soloNumeros($_POST["tipoIdentificacion"]);
        $general_pais = eliminarInvalidos($_POST["pais"]);
        $general_departamento = soloNumeros($_POST["departamento"]);
        $general_municipio = soloNumeros($_POST["municipio"]);
        $general_direccion = eliminarInvalidos($_POST["direccion"]); 
        $general_telefono1 = soloNumeros($_POST["telefono1"]);
        $general_celular = soloNumeros($_POST["celular"]);

        $general_email = eliminarInvalidos($_POST["email"]);
        $general_url = eliminarInvalidos($_POST["url"]);
        $general_url2 = eliminarInvalidos($_POST["url2"]);
        $general_observaciones = eliminarInvalidos($_POST["observaciones"]);
        $general_password = eliminarInvalidos($_POST["password"]);
        if (!empty($_POST["acceso"])) {
            $general_acceso = eliminarInvalidos($_POST["acceso"]);
        }else{
            $general_acceso = 1;
        }
        $general_acceso_graphs = eliminarInvalidos($_POST["acceso_graphs"]);
        
        
        
        //
        $temp_password_check = eliminarInvalidos($_POST["password_check"]);
        /*
        *   ARCHIVO FOTO
        */
		$nombre_archivo = $_FILES['archivo']['name'];
		$tipo_archivo = $_FILES['archivo']['type'];
		$tamano_archivo = $_FILES['archivo']['size'];

        
        if($general_nombre == "" || $general_identificacion == ""){
            $error_datos = 1; //Datos requeridos en blanco
        }
        
        if($general_password != "" && $general_password != $temp_password_check){
            $error_datos = 2;   //Password diferente
        }
        
        /*
        *   COMPROBAMOS QUE IDENTIFICACION NO EXISTA
        */
        $sql= "SELECT id ";
        $sql.=" FROM usuario";
        $sql.=" WHERE identificacion = '".$general_identificacion."' AND id != '".$idUsuarioActual."'";
        $PSN->query($sql);
        if($PSN->next_record())
        {
            $error_datos = 3;   //Identificacion ya existe
        }
        
        
        if($error_datos == 0){
            /*
            *	DEBEMOS INSERTAR LA INFORMACION DEL CLIENTE/USUARIO SEGUN CORRESPONDA.
            */
            $sql = 'UPDATE usuario SET 
                nombre = "'.$general_nombre.'",
                tipo = "'.$general_tipo.'",
                tipo_user_cli = '.$general_tipo_user_cli.',
                identificacion = "'.$general_identificacion.'",
                tipoIdentificacion =  '.$general_tipoIdentificacion.',
                usua_pais = "'.$general_pais.'",
                usua_muni = '.$general_municipio.',
                direccion = "'.$general_direccion.'",
                telefono1 = "'.$general_telefono1.'", 
                celular = "'.$general_celular.'",  
                email = "'.$general_email.'", 
                url = "'.$general_url.'", 
                url2 = "'.$general_url2.'", 
                observaciones = "'.$general_observaciones.'", 
                acceso = "'.$general_acceso.'", 
                acceso_graphs = "'.$general_acceso_graphs.'", 
                modUsuario = "'.$_SESSION["id"].'",
                modFecha = NOW()
            ';
            
            if($general_password != ""){
                $sql .= ',password = "'.md5($general_password).'"';            
            }

            $sql .= ' WHERE id = "'.$idUsuarioActual.'"';
            $ultimoQuery = $PSN1->query($sql);
            
            /*
            *   INSERTAMOS INFORMACIÓN EMPRESARIAL
            */
            $empresa_tipo = soloNumeros($_POST["empresa_tipo"]);
            //$empresa_nombre = eliminarInvalidos($_POST["empresa_nombre"]);
            //$empresa_nit = eliminarInvalidos($_POST["empresa_nit"]);
            $empresa_representante = eliminarInvalidos($_POST["empresa_representante"]);
            $empresa_contacto = eliminarInvalidos($_POST["empresa_contacto"]); 
            $empresa_direccion = eliminarInvalidos($_POST["empresa_direccion"]);
            $empresa_url = eliminarInvalidos($_POST["empresa_url"]);
            $empresa_telefono1 = eliminarInvalidos($_POST["empresa_telefono1"]);
            $empresa_telefono2 = eliminarInvalidos($_POST["empresa_telefono2"]);
            $empresa_celular1 = eliminarInvalidos($_POST["empresa_celular1"]);
            $empresa_celular2 = eliminarInvalidos($_POST["empresa_celular2"]);
            $empresa_email1 = eliminarInvalidos($_POST["empresa_email1"]);
            $empresa_email2 = eliminarInvalidos($_POST["empresa_email2"]);
            $empresa_cargo = eliminarInvalidos($_POST["empresa_cargo"]); 
            $empresa_aprobacion = soloNumeros($_POST["empresa_aprobacion"]); 

                    $empresa_paisid = eliminarInvalidos($_POST["empresa_paisid"]);
                    $empresa_pais = eliminarInvalidos($_POST["empresa_pais"]);
            
                    $empresa_socio = eliminarInvalidos($_POST["empresa_socio"]);
                    $empresa_proceso = eliminarInvalidos($_POST["empresa_proceso"]);
                    $empresa_pd = soloNumeros($_POST["empresa_pd"]);
                    $empresa_sitio_cor = eliminarInvalidos($_POST["empresa_sitio_cor"]);
                    $empresa_sitio = eliminarInvalidos($_POST["empresa_sitio"]);
                    $empresa_rm = eliminarInvalidos($_POST["empresa_rm"]);
                    $empresa_circuito = eliminarInvalidos($_POST["empresa_circuito"]);
            
            

            $sql = 'UPDATE usuario_empresa SET 
                empresa_tipo = "'.$empresa_tipo.'", 
                empresa_nombre =  "'.$empresa_nombre.'",
                empresa_nit = "'.$empresa_nit.'",
                empresa_representante = "'.$empresa_representante.'",
                empresa_contacto = "'.$empresa_contacto.'",
                empresa_direccion =  "'.$empresa_direccion.'",
                empresa_url =  "'.$empresa_url.'",
                empresa_telefono1 = "'.$empresa_telefono1.'",
                empresa_telefono2 =  "'.$empresa_telefono2.'",
                empresa_celular1 = "'.$empresa_celular1.'",
                empresa_celular2 =  "'.$empresa_celular2.'",
                empresa_email1 = "'.$empresa_email1.'",
                empresa_email2 =  "'.$empresa_email2.'",
                empresa_cargo =  "'.$empresa_cargo.'",
                empresa_aprobacion =  "'.$empresa_aprobacion.'",
                empresa_paisid = "'.$empresa_paisid.'",
                empresa_pais = "'.$empresa_pais.'",
                
                empresa_socio = "'.$empresa_socio.'",
                empresa_proceso = "'.$empresa_proceso.'",
                empresa_pd = '.$empresa_pd.',
                empresa_sitio_cor = "'.$empresa_sitio_cor.'",
                empresa_sitio = "'.$empresa_sitio.'",
                empresa_rm = "'.$empresa_rm.'",
                empresa_circuito = "'.$empresa_circuito.'"
            ';
            //
            $sql .= ' WHERE idUsuario = "'.$idUsuarioActual.'"';
            $ultimoQuery = $PSN1->query($sql);

            /*
            *   CARGUE DE PESTAÑA CLIENTE
            */
            if($ctrl == 2){
                $cliente_tipo1 = soloNumeros($_POST["cliente_tipo1"]);
                $cliente_servicio1 = soloNumeros($_POST["cliente_servicio1"]);
                $cliente_observaciones = eliminarInvalidos($_POST["cliente_observaciones"]);
                $cliente_valor1 = soloNumeros($_POST["cliente_valor1"]);
                $cliente_diaPago = soloNumeros($_POST["cliente_diaPago"]);
                $cliente_fechaAprob = eliminarInvalidos($_POST["cliente_fechaAprob"]);
                $cliente_fechaAprobCont = eliminarInvalidos($_POST["cliente_fechaAprobCont"]);
                $cliente_fechaInicial = eliminarInvalidos($_POST["cliente_fechaInicial"]);
                $cliente_fechaFinal = eliminarInvalidos($_POST["cliente_fechaFinal"]);
                $cliente_tipoPersona = soloNumeros($_POST["cliente_tipoPersona"]);
                //
                //
                $sql = 'UPDATE usuario_cliente SET 
                    cliente_tipo1 = "'.$cliente_tipo1.'",
                    cliente_servicio1 = "'.$cliente_servicio1.'",
                    cliente_observaciones =  "'.$cliente_observaciones.'",
                    cliente_valor1 =  "'.$cliente_valor1.'",
                    cliente_diaPago = "'.$cliente_diaPago.'",
                    cliente_fechaAprob = "'.$cliente_fechaAprob.'",
                    cliente_fechaAprobCont = "'.$cliente_fechaAprobCont.'",
                    cliente_fechaInicial = "'.$cliente_fechaInicial.'",
                    cliente_fechaFinal = "'.$cliente_fechaFinal.'",
                    cliente_tipoPersona = "'.$cliente_tipoPersona.'"
                ';
                //
                $sql .= ' WHERE idUsuario = "'.$idUsuarioActual.'"';
                $ultimoQuery = $PSN1->query($sql);
            }

            /*
            *   CARGUE DE PESTAÑA PROVEEDOR
            */
            if($ctrl == 3){
                $servicios_tipo1 = soloNumeros($_POST["servicios_tipo1"]);
                $servicios_tipo2 = soloNumeros($_POST["servicios_tipo2"]);
                $servicios_contrato1 = soloNumeros($_POST["servicios_contrato1"]);
                $servicios_contrato2 = soloNumeros($_POST["servicios_contrato2"]);
                $servicios_observaciones = eliminarInvalidos($_POST["servicios_observaciones"]);
                $servicios_fechaInicio = eliminarInvalidos($_POST["servicios_fechaInicio"]);
                $servicios_fechaFin = eliminarInvalidos($_POST["servicios_fechaFin"]);
                $servicios_tipoPersona = soloNumeros($_POST["servicios_tipoPersona"]);
                $servicios_porcentaje = soloNumeros($_POST["servicios_porcentaje"]);
                
                //
                //
                $sql = 'UPDATE usuario_servicios SET 
                    servicios_tipo1 = "'.$servicios_tipo1.'",
                    servicios_tipo2 = "'.$servicios_tipo2.'",
                    servicios_contrato1 = "'.$servicios_contrato1.'",
                    servicios_contrato2 = "'.$servicios_contrato2.'",
                    servicios_observaciones =  "'.$servicios_observaciones.'",
                    servicios_fechaInicio = "'.$servicios_fechaInicio.'",
                    servicios_fechaFin = "'.$servicios_fechaFin.'",
                    servicios_tipoPersona = "'.$servicios_tipoPersona.'",
                    servicios_porcentaje = "'.$servicios_porcentaje.'"
                    
                ';
                $sql .= ' WHERE idUsuario = "'.$idUsuarioActual.'"';
                $ultimoQuery = $PSN1->query($sql);
            }

            /*
            *   INSERTAMOS ACCESOS AL SISTEMA.
            */
            $sql ="DELETE FROM usuarios_menu WHERE idUsuario = ".$idUsuarioActual;
            $PSN1->query($sql);
            //            
            foreach($_POST["menu"] as $menuopc){				//
                $sql ="REPLACE INTO usuarios_menu (idUsuario, idMenu) VALUES (".$idUsuarioActual.", ".soloNumeros($menuopc).")";
                $PSN1->query($sql);
            }

            /*
            *   INSERTAMOS ACCESOS A GRAFICAS AL SISTEMA.
            */
            $sql ="DELETE FROM usuarios_menu_graphs WHERE idUsuario = ".$idUsuarioActual;
            $PSN1->query($sql);
            //            
            foreach($_POST["menu_graphs"] as $menuopc){				//
                $sql ="REPLACE INTO usuarios_menu_graphs (idUsuario, idMenu) VALUES (".$idUsuarioActual.", ".soloNumeros($menuopc).")";
                $PSN1->query($sql);
            }

            //Compruebo si las características del archivo son las que deseo
            if(move_uploaded_file($_FILES['archivo']['tmp_name'], "images/usuarios/".$idUsuarioActual.".jpg"))
            {                    
            }

            //
            //  Documento de IDENTIFICACIÓN
            //
            $nombre_archivo = $_FILES['documento_identificacion']['name'];
            $temp_location = $_FILES['documento_identificacion']['tmp_name'];
            $temp_ext = extension_archivo($nombre_archivo);
            $temp_nombreFile = "id".$idUsuarioActual.".".$temp_ext;
            
            echo $nombre_archivo;
            
            /*echo "<br />Temp location: ".$temp_location;
            echo "<br />Temp temp_ext: ".$temp_ext;
            echo "<br />Temp temp_nombreFile: ".$temp_nombreFile;*/

            if(move_uploaded_file($temp_location, "archivos/usuarios/".$temp_nombreFile))
            {
                $sql ="UPDATE usuario_documentos SET 
                    documento_identificacion = '".$temp_nombreFile."'
                    WHERE idUsuario = '".$idUsuarioActual."'";
                $PSN1->query($sql);
            }

            //
            //  Documento de RUT
            //
            $nombre_archivo = $_FILES['documento_rut']['name'];
            $temp_location = $_FILES['documento_rut']['tmp_name'];
            $temp_ext = extension_archivo($nombre_archivo);
            $temp_nombreFile = "rut".$idUsuarioActual.".".$temp_ext;

            if(move_uploaded_file($temp_location, "archivos/usuarios/".$temp_nombreFile))
            {
                $sql ="UPDATE usuario_documentos SET 
                    documento_rut = '".$temp_nombreFile."'
                    WHERE idUsuario = '".$idUsuarioActual."'";
                $PSN1->query($sql);
            }

            //
            //  Documento de CONSTITUCION
            //
            $nombre_archivo = $_FILES['documento_constitucion']['name'];
            $temp_location = $_FILES['documento_constitucion']['tmp_name'];
            $temp_ext = extension_archivo($nombre_archivo);
            $temp_nombreFile = "cons".$idUsuarioActual.".".$temp_ext;

            if(move_uploaded_file($temp_location, "archivos/usuarios/".$temp_nombreFile))
            {
                $sql ="UPDATE usuario_documentos SET 
                    documento_constitucion = '".$temp_nombreFile."'
                    WHERE idUsuario = '".$idUsuarioActual."'";
                $PSN1->query($sql);
            }

            //
            //  Documento de CONTRATO
            //
            $nombre_archivo = $_FILES['documento_contrato']['name'];
            $temp_location = $_FILES['documento_contrato']['tmp_name'];
            $temp_ext = extension_archivo($nombre_archivo);
            $temp_nombreFile = "contrato".$idUsuarioActual.".".$temp_ext;

            if(move_uploaded_file($temp_location, "archivos/usuarios/".$temp_nombreFile))
            {
                $sql ="UPDATE usuario_documentos SET 
                    documento_contrato = '".$temp_nombreFile."'
                    WHERE idUsuario = '".$idUsuarioActual."'";
                $PSN1->query($sql);
            }

            //
            //  Documento ADICIONAL
            //
            /*$nombre_archivo = $_FILES['documento_adicional_file']['name'];
            $temp_location = $_FILES['documento_adicional_file']['tmp_name'];
            $temp_ext = extension_archivo($nombre_archivo);
            $temp_nombreFile = strtotime("now").".".$temp_ext;*/

            $nom_doc = str_replace(' ', '_',$_REQUEST["documento_adicional_nom"]);
            $usuari_spa = str_replace(' ', '-',$general_nombre);
            $nom_usu = eliminar_tildes($usuari_spa);
            $nombre_archivo = $_FILES['documento_adicional_file']['name'];
            $temp_location = $_FILES['documento_adicional_file']['tmp_name'];
            $temp_ext = extension_archivo($nombre_archivo);
            $temp_nombreFile = $nom_doc."_".$nom_usu."-".$general_identificacion.".".$temp_ext;

            if(move_uploaded_file($temp_location, "archivos/usuarios/".$temp_nombreFile))
            {
                $sql ="INSERT INTO usuario_documentos_add 
                        (
                        idUsuario, 
                        descripcion, 
                        archivo
                    ) 
                    VALUES 
                        (
                        ".$idUsuarioActual.", 
                        '".eliminarInvalidos($_REQUEST["documento_adicional_nom"])."', 
                        '".$temp_nombreFile."'
                )";
                $PSN1->query($sql);
                //
            }
            $varExitoUSU_UPD = 1;
        }
	}else if($_POST["funcion"] == "eliminar"){
        if($_SESSION["id"] != 1){
            die("<h1>No esta autorizado para eliminar usuarios</h1>");
        }
        //echo "Estas eliminando el usuario: ".$_REQUEST['id'];
        $id_usuario_eliminar = soloNumeros($_REQUEST['id_usuario']);
        $sql ="DELETE FROM usuario WHERE id = ".$id_usuario_eliminar;
        $PSN1->query($sql);
        //header('Location: index.php?doc=usuario_buscar&ctrl=1');
        $varExitoUSU = 2;
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


if(!isset($_REQUEST["id"])){
    $temp_accionForm = "insertar";
    $idUsuarioActual = 0;
    /*
    *   Cargue de datos iniciales
    */
    $general_nombre = eliminarInvalidos($_POST["nombre"]);
    $general_tipo = soloNumeros($_POST["tipo"]);
    if (!empty($_POST["tipo_user_cli"])) {
        $general_tipo_user_cli = soloNumeros($_POST["tipo_user_cli"]);
    }else{
        $general_tipo_user_cli = 0;
    }
    $general_identificacion = eliminarInvalidos($_POST["identificacion"]);
    $general_tipoIdentificacion = soloNumeros($_POST["tipoIdentificacion"]);
    $general_pais = eliminarInvalidos($_POST["pais"]);  
    $general_departamento = soloNumeros($_POST["departamento"]);
    $general_municipio = soloNumeros($_POST["municipio"]);
    $general_direccion = eliminarInvalidos($_POST["direccion"]); 
    $general_telefono1 = soloNumeros($_POST["telefono1"]);
    $general_celular = soloNumeros($_POST["celular"]);
    $general_email = eliminarInvalidos($_POST["email"]);
    $general_url = eliminarInvalidos($_POST["url"]);
    $general_url2 = eliminarInvalidos($_POST["url2"]);
    $general_observaciones = eliminarInvalidos($_POST["observaciones"]);
    $general_password = eliminarInvalidos($_POST["password"]);
    if (!empty($_POST["acceso"])) {
            $general_acceso = eliminarInvalidos($_POST["acceso"]);
        }else{
            $general_acceso = 1;
        }
    if (!empty($_POST["acceso_graphs"])) {
            $general_acceso_graphs = eliminarInvalidos($_POST["acceso_graphs"]);
        }else{
            $general_acceso_graphs = 1;
        }
        
    
    //
    //
    $empresa_tipo = soloNumeros($_POST["empresa_tipo"]);
    //
    //
    $empresa_representante = eliminarInvalidos($_POST["empresa_representante"]);
    $empresa_contacto = eliminarInvalidos($_POST["empresa_contacto"]);
    $empresa_direccion = eliminarInvalidos($_POST["empresa_direccion"]);
    $empresa_url = eliminarInvalidos($_POST["empresa_url"]);
    $empresa_telefono1 = eliminarInvalidos($_POST["empresa_telefono1"]);
    $empresa_telefono2 = eliminarInvalidos($_POST["empresa_telefono2"]);
    $empresa_celular1 = eliminarInvalidos($_POST["empresa_celular1"]);
    $empresa_celular2 = eliminarInvalidos($_POST["empresa_celular2"]);
    $empresa_email1 = eliminarInvalidos($_POST["empresa_email1"]);
    $empresa_email2 = eliminarInvalidos($_POST["empresa_email2"]);
    $empresa_cargo = eliminarInvalidos($_POST["empresa_cargo"]);
    $empresa_aprobacion = soloNumeros($_POST["empresa_aprobacion"]);
    
    
        $empresa_paisid = eliminarInvalidos($_POST["empresa_paisid"]);
        $empresa_pais = eliminarInvalidos($_POST["empresa_pais"]);
        $empresa_socio = eliminarInvalidos($_POST["empresa_socio"]);
        $empresa_proceso = eliminarInvalidos($_POST["empresa_proceso"]);
        $empresa_pd = soloNumeros($_POST["empresa_pd"]);
        $empresa_sitio_cor = eliminarInvalidos($_POST["empresa_sitio_cor"]);
        $empresa_sitio = eliminarInvalidos($_POST["empresa_sitio"]);
        $empresa_rm = eliminarInvalidos($_POST["empresa_rm"]);
        $empresa_circuito = eliminarInvalidos($_POST["empresa_circuito"]);
    
    
    
    //
    //
    $servicios_tipo1 = soloNumeros($_POST["servicios_tipo1"]);
    $servicios_tipo2 = soloNumeros($_POST["servicios_tipo2"]);
    $servicios_contrato1 = soloNumeros($_POST["servicios_contrato1"]);
    $servicios_contrato2 = soloNumeros($_POST["servicios_contrato2"]);
    $servicios_observaciones = eliminarInvalidos($_POST["servicios_observaciones"]);
    $servicios_fechaInicio = eliminarInvalidos($_POST["servicios_fechaInicio"]);
    $servicios_fechaFin = eliminarInvalidos($_POST["servicios_fechaFin"]);
    $servicios_tipoPersona = soloNumeros($_POST["servicios_tipoPersona"]);
    $servicios_porcentaje = soloNumeros($_POST["servicios_porcentaje"]);
    //
    //
    $cliente_tipo1 = soloNumeros($_POST["cliente_tipo1"]);
    $cliente_servicio1 = soloNumeros($_POST["cliente_servicio1"]);
    $cliente_observaciones = eliminarInvalidos($_POST["cliente_observaciones"]);
    $cliente_valor1 = soloNumeros($_POST["cliente_valor1"]);
    $cliente_diaPago = soloNumeros($_POST["cliente_diaPago"]);
    $cliente_fechaAprob = eliminarInvalidos($_POST["cliente_fechaAprob"]);
    $cliente_fechaAprobCont = eliminarInvalidos($_POST["cliente_fechaAprobCont"]);
    $cliente_fechaInicial = eliminarInvalidos($_POST["cliente_fechaInicial"]);
    $cliente_fechaFinal = eliminarInvalidos($_POST["cliente_fechaFinal"]);
    $cliente_tipoPersona = soloNumeros($_POST["cliente_tipoPersona"]);
}else{
    $temp_accionForm = "actualizar";    
    //  ID del usuario actual
    $idUsuarioActual = soloNumeros($_REQUEST["id"]);
    
    if(isset($_REQUEST["deldoc"]) && $_REQUEST["deldoc_name"] != ""){
        unlink("archivos/usuarios/".$_REQUEST["deldoc_name"]);
        //
        if($_REQUEST["deldoc"] == "contrato"){
            $sql ="UPDATE usuario_documentos SET 
                documento_contrato = ''
                WHERE idUsuario = '".$idUsuarioActual."'";
            $PSN1->query($sql);
        }
        else if($_REQUEST["deldoc"] == "constitucion"){
            $sql ="UPDATE usuario_documentos SET 
                documento_constitucion = ''
                WHERE idUsuario = '".$idUsuarioActual."'";
            $PSN1->query($sql);
        }
        else if($_REQUEST["deldoc"] == "rut"){
            $sql ="UPDATE usuario_documentos SET 
                documento_rut = ''
                WHERE idUsuario = '".$idUsuarioActual."'";
            $PSN1->query($sql);
        }
        else if($_REQUEST["deldoc"] == "identificacion"){
            $sql ="UPDATE usuario_documentos SET 
                documento_identificacion = ''
                WHERE idUsuario = '".$idUsuarioActual."'";
            $PSN1->query($sql);
        }
        else if(soloNumeros($_REQUEST["deldoc"]) != "" && soloNumeros($_REQUEST["deldoc"]) != "0"){
            $sql ="DELETE FROM usuario_documentos_add 
                    WHERE id = '".soloNumeros($_REQUEST["deldoc"])."' 
                    AND idUsuario = '".$idUsuarioActual."'";
            $PSN1->query($sql);
        }
    }
    
    /*
    *	TRAEMOS LOS DATOS PRINCIPALES DEL USUARIO
    */
    $sql = "SELECT CA.id AS zona, C.id AS regional, usuario.*, U.id as idCliente, U.nombre as nomcliente, dane_municipios.*,dane_departamentos.* FROM usuario ";
    $sql .= "LEFT JOIN usuario_relacion ON usuario_relacion.idUsuario1 = usuario.id 
        LEFT JOIN dane_municipios ON dane_municipios.id_municipio = usuario.usua_muni 
        LEFT JOIN dane_departamentos ON dane_departamentos.id_departamento = dane_municipios.departamento_id 
        LEFT JOIN usuario as U ON U.id = usuario_relacion.idUsuario2 AND U.tipo = 3
        LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = usuario.id 
        LEFT JOIN categorias AS C ON C.id = UE.empresa_pd
        LEFT JOIN categorias AS CA ON CA.id = C.idSec
        WHERE usuario.id = '".$idUsuarioActual."' GROUP BY usuario.id;";
    $PSN1->query($sql);
    //echo $sql;
    if($PSN1->num_rows() > 0){
        if($PSN1->next_record()){
            $general_nombre = $PSN1->f("nombre");
            $general_tipo = $PSN1->f("tipo");
            if($general_tipo == 3){
                $ctrl = 2;
            }
            else if($general_tipo == 4){
                $ctrl = 3;
            }
            else if($general_tipo == 160){
                $ctrl = 4;
                $idCliente = $PSN1->f("idCliente");
            }
            $general_id_usuario = $PSN1->f("id");
            $general_tipo_user_cli = $PSN1->f("tipo_user_cli");
            //
            $general_identificacion = $PSN1->f("identificacion");
            $general_tipoIdentificacion = $PSN1->f("tipoIdentificacion");
            $general_pais = $PSN1->f("pais"); 
            $general_departamento = $PSN1->f("id_departamento"); 
            $general_municipio = $PSN1->f("id_municipio");
            $general_zona = $PSN1->f("zona");
            $general_regional = $PSN1->f("regional"); 
            $general_direccion = $PSN1->f("direccion"); 
            $general_telefono1 = $PSN1->f("telefono1");
            $general_celular = $PSN1->f("celular");
            $general_email = $PSN1->f("email");
            $general_url = $PSN1->f("url");
            $general_url2 = $PSN1->f("url2");
            $general_observaciones = $PSN1->f("observaciones");
            $general_password = $PSN1->f("password");
            $general_acceso = $PSN1->f("acceso");
            $general_acceso_graphs = $PSN1->f("acceso_graphs");

            /*
            *	TRAEMOS LOS DATOS EMPRESARIALES
            */
            $sql = "SELECT * ";
            $sql.=" FROM usuario_empresa ";
            $sql.=" WHERE idUsuario = '".$idUsuarioActual."'";
            $PSN1->query($sql);
            if($PSN1->num_rows() > 0){
                if($PSN1->next_record()){
                    $empresa_tipo = $PSN1->f("empresa_tipo");
                    $empresa_nombre = $PSN1->f("empresa_nombre");
                    $empresa_nit = $PSN1->f("empresa_nit");
                    $empresa_representante = $PSN1->f("empresa_representante");
                    $empresa_contacto = $PSN1->f("empresa_contacto");
                    $empresa_direccion = $PSN1->f("empresa_direccion");
                    $empresa_url = $PSN1->f("empresa_url");
                    $empresa_telefono1 = $PSN1->f("empresa_telefono1");
                    $empresa_telefono2 = $PSN1->f("empresa_telefono2");
                    $empresa_celular1 = $PSN1->f("empresa_celular1");
                    $empresa_celular2 = $PSN1->f("empresa_celular2");
                    $empresa_email1 = $PSN1->f("empresa_email1");
                    $empresa_email2 = $PSN1->f("empresa_email2");
                    $empresa_cargo = $PSN1->f("empresa_cargo");
                    $empresa_aprobacion = $PSN1->f("empresa_aprobacion");
                    $empresa_paisid = $PSN1->f("empresa_paisid");
                    $empresa_pais = $PSN1->f("empresa_pais");
                    $empresa_socio = $PSN1->f("empresa_socio");
                    $empresa_proceso = $PSN1->f("empresa_proceso");
                    $empresa_pd = $PSN1->f("empresa_pd");
                    $empresa_sitio_cor = $PSN1->f("empresa_sitio_cor");
                    $empresa_sitio = $PSN1->f("empresa_sitio");
                    $empresa_rm = $PSN1->f("empresa_rm");
                    $empresa_circuito = $PSN1->f("empresa_circuito"); 
                }
            }

            /*
            *	TRAEMOS LOS DATOS DE PROVEEDOR
            */
            $sql = "SELECT * ";
            $sql.=" FROM usuario_servicios ";
            $sql.=" WHERE idUsuario = '".$idUsuarioActual."'";
            $PSN1->query($sql);
            if($PSN1->num_rows() > 0)
            {
                if($PSN1->next_record())
                {
                    $servicios_tipo1 = $PSN1->f("servicios_tipo1");
                    $servicios_tipo2 = $PSN1->f("servicios_tipo2");
                    $servicios_contrato1 = $PSN1->f("servicios_contrato1");
                    $servicios_contrato2 = $PSN1->f("servicios_contrato2");
                    $servicios_observaciones = $PSN1->f("servicios_observaciones");
                    $servicios_fechaInicio = $PSN1->f("servicios_fechaInicio");
                    $servicios_fechaFin = $PSN1->f("servicios_fechaFin");
                    $servicios_tipoPersona = $PSN1->f("servicios_tipoPersona");
                    $servicios_porcentaje = $PSN1->f("servicios_porcentaje");
                    
                }
            }
            
            /*
            *	TRAEMOS LOS DATOS DE CLIENTE
            */
            $sql = "SELECT * ";
            $sql.=" FROM usuario_cliente ";
            $sql.=" WHERE idUsuario = '".$idUsuarioActual."'";
            $PSN1->query($sql);
            if($PSN1->num_rows() > 0){
                if($PSN1->next_record()){
                    $cliente_tipo1 = $PSN1->f("cliente_tipo1");
                    $cliente_servicio1 = $PSN1->f("cliente_servicio1");
                    $cliente_observaciones = $PSN1->f("cliente_observaciones");
                    $cliente_valor1 = $PSN1->f("cliente_valor1");
                    $cliente_diaPago = $PSN1->f("cliente_diaPago");
                    $cliente_fechaAprob = $PSN1->f("cliente_fechaAprob");
                    $cliente_fechaAprobCont = $PSN1->f("cliente_fechaAprobCont");
                    $cliente_fechaInicial = $PSN1->f("cliente_fechaInicial");
                    $cliente_fechaFinal = $PSN1->f("cliente_fechaFinal");
                    $cliente_tipoPersona = $PSN1->f("cliente_tipoPersona");
                }
            }
            
            /*
            *	TRAEMOS LOS DATOS DE DOCUMENTOS PRINCIPALES
            */
            $sql = "SELECT * ";
            $sql.=" FROM usuario_documentos ";
            $sql.=" WHERE idUsuario = '".$idUsuarioActual."'";
            $PSN1->query($sql);
            if($PSN1->num_rows() > 0){
                if($PSN1->next_record()){
                    $documento_identificacion = $PSN1->f("documento_identificacion");
                    $documento_rut = $PSN1->f("documento_rut");
                    $documento_constitucion = $PSN1->f("documento_constitucion");
                    $documento_contrato = $PSN1->f("documento_contrato");
                }
            }  
        }//chequear el registro
    }//chequear el numero
}



/*
*   VALIDACIONES DE USUARIO AUTORIZADO DEL CLIENTE
*/
if($ctrl == 4){
    $error_cliente = 0; //
    if(!isset($idCliente) && (!isset($_REQUEST["idCliente"]) || 
        soloNumeros($_REQUEST["idCliente"]) == "" || 
        soloNumeros($_REQUEST["idCliente"]) == "0")){
        $error_cliente = 1; //  Cliente vacio
    }else{
        //  ID del cliente.
        if(!isset($idCliente) && $idCliente == 0 && $idCliente == ""){
            $idCliente = soloNumeros($_REQUEST["idCliente"]);
        }
        $error_cliente = 2; //  Cliente NO existente
        /*
        *	TRAEMOS EL CLIENTE ASOCIADO
        */
        $sql = "SELECT id, nombre";
        $sql.=" FROM usuario ";
        $sql.=" WHERE id = '".$idCliente."' AND tipo = 3";
        $PSN1->query($sql);
        $numero=$PSN1->num_rows();
        if($numero > 0){
            if($PSN1->next_record()){
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

/*
*   DETECTAMOS EL TIPO DE FORMULARIO QUE VAMOS A MOSTRAR.
*/

switch($ctrl){
    case 1:
        $temp_tiposUsuario = "162, 163, 164, 165, 166, 167,168";
        if ($general_tipo == 1 || $general_tipo == 2) {
            $temp_tiposUsuario .= ", 1, 2";
        }
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
        $temp_letrero = "AUTORIZADO DEL CLIENTE:<br />".$temp_nombreCliente;
        break;
    default:
        $temp_letrero = "SIN DEFINIR";
        break;
}

/*
*   SI SE INSERTO REGISTRO SE REDIRIGE
*/
if($varExitoUSU == 1){?>
    <div class="container">
        <div class="row">
            <h2 class="alert alert-info text-center"><?php
            if($idUsuarioActual == 0){
                echo "CREACION";
            }else{
                echo "ACTUALIZACIÓN";
            }?> DE <?=$temp_letrero; ?></h2>
        </div>
        <div class="row">
            <h5 class="alert alert-warning text-center">Se ha <?php
            if($idUsuarioActual == 0){
                echo "creado";
            }else{
                echo "actualizado";
            }?> correctamente el registro, en breve será redirigido, si no es redirigido de <a href="index.php?doc=<?=$webArchivo; ?>&opc=2&id=<?=$ultimoId; ?>">clic aquí</a>.</h5>
        </div>
    </div>
    <script LANGUAGE="JavaScript">
        alert("Se ha creado correctamente el registro.");
        window.location.href= "index.php?doc=<?=$webArchivo; ?>&opc=2&id=<?=$ultimoId; ?>";
    </script>
    <?php
}else if($varExitoUSU == 2){
    ?><div class="container">
        <div class="row">
            <h5 class="alert alert-warning text-center">Se ha eliminador correctamente el registro, en breve será redirigido, si no es redirigido de <a href="<?=$_SERVER['HOME'];  ?>/index.php?ctrl=1?doc=usuario_buscar&ctrl=1">clic aquí</a>.</h5>
        </div>
    </div>
    <script>
        //alert("Se ha eliminado correctamente el registro.");
        setTimeout(function () {
            window.location.href="index.php?doc=usuario_buscar&ctrl=1"; // the redirect goes here
        },3000);
        //window.location.href= "index.php?doc=usuario_buscar&ctrl=1";*/
    </script>
    <?php
}else{
    if($idUsuarioActual != 0){
        $sqlU = "SELECT U.id FROM usuario AS U ";
        $sqlU .= "WHERE U.id = (SELECT MAX(US.id)FROM usuario AS US WHERE US.id < ".$idUsuarioActual.") ";
        $PSN1->query($sqlU);
        if($PSN1->num_rows() > 0){
            if($PSN1->next_record()){
            $antId  = $PSN1->f('id');
            }
        }else{
           $antId  = 0; 
        }
        $sqlU = "SELECT U.id FROM usuario AS U ";
        $sqlU .= "WHERE U.id = (SELECT MIN(US.id)FROM usuario AS US WHERE US.id > ".$idUsuarioActual.") ";
        $PSN1->query($sqlU);
        //echo  $sqlU;
        if($PSN1->num_rows() > 0){
            if($PSN1->next_record()){
                $sigId  = $PSN1->f('id');
            }
        }else{
           $sigId  = 0; 
        }              
    }?>
    <div class="container">
        <div class="row">
            <h3 class="alert alert-info text-center">
                <?php echo ($idUsuarioActual == 0)?"CREACION":"ACTUALIZACIÓN"; ?> DE <?=$temp_letrero; ?>
            </h3>
        </div>
        <?php if($idUsuarioActual != 0){ ?>
            <div class="cont-btn cont-flex fl-sbet">
                <div class="item-btn">
                    <?php
                    if ($antId != 0) {?>
                    <a href="index.php?doc=usuario&id=<?=$antId ?>" name="previous" class="previous btn btn-info">Anterior usuario <?=$antId ?></a>
                    <?php } ?>
                </div>
                <div class="item-btn">
                    <a href="index.php?doc=usuario_buscar" name="previous" class="btn btn-warning">Todos los usuarios</a>
                </div>
                <div class="item-btn">
                    <?php
                    if ($sigId != 0) {?>
                    <a href="index.php?doc=usuario&id=<?=$sigId ?>" name="previous" class="previous btn btn-info">Siguiente usuario <?=$sigId ?></a>
                    <?php } ?>
                </div>
            </div><br>
        <?php } 
        if($varExitoUSU_UPD == 1){?>
            <div class="row">
                <h5 class="alert alert-warning text-center">Se ha actualizado correctamente el registro.</h5>
            </div>
        <?php }
        if($texto_error != ""){?>
            <div class="row">
                <h5 class="alert alert-danger text-center"><?=$texto_error; ?></h5>
            </div>
        <?php }
        if($errorLogueo == 1){?>
            <div class="row"><h1><font color="red"><u>ATENCION:</u> NO SE CREO EL ACCESO<BR /><u>MOTIVO:</u> YA EXISTE UN ACCESO CON ESE MISMO "LOGIN".<br />POR FAVOR CAMBIE EL "LOGIN".</font></h1></div>
        <?php }
        if($error_fatal == 1){
            //No hacer nada.
        }else{?>
            <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form-horizontal">
                <input type="hidden" name="ctrl" id="ctrl" value="<?=$ctrl; ?>" />
                <ul class="nav nav-tabs">
                    <li class="active"><a data-toggle="tab" href="#general">Información básica</a></li>
                    <li><a data-toggle="tab" href="#empresa">Información Organizacional</a></li>
                    <?php
                    //  USUARIO PROVEEDOR
                    if($ctrl == 3){
                        ?><li><a data-toggle="tab" href="#servicios">Proveedor</a></li><?php
                    }
                    //  USUARIO cliente
                    if($ctrl == 2){
                        ?><li><a data-toggle="tab" href="#cliente">Cliente</a></li><?php
                    }
                    //  USUARIO cliente o proveedor
                    if($ctrl == 1 || $ctrl == 2 || $ctrl == 3){
                        ?><li><a data-toggle="tab" href="#archivos">Documentos de usuario</a></li><?php
                    }
                    
                    //  OBSERVACIONES para todos los clientes
                    /*if($idUsuarioActual != 0){
                        echo '<li><a data-toggle="tab" href="#tab_observaciones">Observaciones</a></li>
                        <li><a data-toggle="tab" href="#tab_metas">Metas</a></li>';'
                    }*/
                    ?>
                    <li><a data-toggle="tab" href="#accesos">Acceso al sistema</a></li>
                    <?php
                    //
                    //  USUARIO interno y CLIENTE y Usuario CLIENTE
                    //
                    if($ctrl == 1 || $ctrl == 2 || $ctrl == 4){
                        ?><li><a data-toggle="tab" href="#graficas">Acceso a gráficas</a></li><?php
                    }
                    ?>
                </ul>
                <div class="tab-content">
                    <div id="general" class="tab-pane fade in active">
                        <div class="cont-tit">
                            <div class="hr"><hr></div>
                            <div class="tit-cen">
                                <h3 class="text-center">INFORMACIÓN GENERAL</h3>
                                <h5>Información básica del usuario</h5>
                            </div>
                            <div class="hr"><hr></div>
                        </div>
                        <?php if($ctrl == 4){?>
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="idCliente"><strong>Autorizados del cliente:</strong></label>
                                <div class="col-sm-4">
                                    <select name="idCliente" class="form-control"><?php
                                    /*
                                    *	TRAEMOS LOS CLIENTES
                                    */
                                    $sql = "SELECT usuario.id, usuario.nombre ";
                                    $sql.=" FROM usuario ";
                                    $sql.=" WHERE tipo = 3 AND id = '".$idCliente."'";
                                    $sql.=" ORDER BY nombre asc";
                                    //
                                    $PSN1->query($sql);
                                    $numero=$PSN1->num_rows();
                                    if($numero > 0){
                                        while($PSN1->next_record())
                                        {
                                            ?><option value="<?=$PSN1->f('id'); ?>"><?=$PSN1->f('nombre'); ?></option><?php
                                        }
                                    }
                                    ?></select>
                                </div>
                                <label class="control-label col-sm-2" for="tipo_user_cli"><strong>Tipo de autorizado:</strong></label>
                                <div class="col-sm-4">
                                    <select name="tipo_user_cli" class="form-control">
                                        <option value="">Sin especificar</option>
                                        <?php
                                        /*
                                        *	TRAEMOS LOS TIPOS DE USUARIO (1)
                                        */
                                        $sql = "SELECT * ";
                                        $sql.=" FROM categorias ";
                                        $sql.=" WHERE idSec = 32 ORDER BY descripcion asc";
                                        $PSN1->query($sql);
                                        $numero=$PSN1->num_rows();
                                        if($numero > 0)
                                        {
                                            while($PSN1->next_record())
                                            {
                                                ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                                if($general_tipo_user_cli == $PSN1->f('id'))
                                                {
                                                    ?>selected="selected"<?php
                                                }
                                                ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        <?php }?>
                        <div class="form-group">
                            <div class="col-sm-1"></div>
                            <div class="col-sm-3">
                                <strong>Nombre completo:</strong>
                                <input name="nombre" type="text" id="nombre" maxlength="250" value="<?=$general_nombre; ?>" class="form-control" required autofocus />
                            </div>	
                            <div class="col-sm-2">
                                <strong>Tipo de identificación:</strong>
                                <select name="tipoIdentificacion" class="form-control">
                                <option value="">Sin especificar</option>
                                <?php
                                /*
                                *   TRAEMOS LOS TIPOS DE IDENTIFICACION (2)
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
                                        ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                        if($general_tipoIdentificacion == $PSN1->f('id'))
                                        {
                                            ?>selected="selected"<?php
                                        }
                                        ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                    }
                                }
                                ?>
                                </select>
                            </div>
                            <div class="col-sm-2">
                                <strong>N° Identificación</strong><input name="identificacion" type="text" id="identificacion" maxlength="250" value="<?=$general_identificacion; ?>" class="form-control" required autofocus />
                                <input name="id_usuario" type="hidden" id="id_usuario"  maxlength="250" value="<?=$general_id_usuario; ?>" class="form-control" />
                            </div>
                            <div class="col-sm-3">
                                <strong>Tipo de usuario:</strong>
                                <select name="tipo" class="form-control">
                                    <option value="">Seleccione un tipo</option>
                                <?php
                                /*
                                *   TRAEMOS LOS TIPOS DE USUARIO (1)
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
                                        ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                        if($general_tipo == $PSN1->f('id'))
                                        {
                                            ?>selected="selected"<?php
                                        }
                                        ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                    }
                                }
                                ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-1"></div>
                            <div class="col-sm-2">
                                <strong>País</strong>
                                <select name="pais" class="form-control">
                                    <option value="">Sin especificar</option>
                                    <option value="57" selected >Colombia</option>}
                                    option
                                </select>
                            </div>
                            <div class="col-sm-2">
                                <strong>Departamento</strong>
                                <select required name="departamento" id="departamento" style="text-transform: capitalize;" class="form-control">
                                    <option value="">Sin especificar</option>
                                    <?php
                                    /*
                                    *   TRAEMOS LOS TIPOS DE IDENTIFICACION (2)
                                    */
                                    $sql = "SELECT id_departamento,lower(departamento) as departamento ";
                                    $sql.=" FROM dane_departamentos ";
                                    $sql.=" ORDER BY departamento asc";
                                    $PSN1->query($sql);
                                    $numero=$PSN1->num_rows();
                                    if($numero > 0){
                                        while($PSN1->next_record()){
                                            ?><option style="text-transform: capitalize;" value="<?=$PSN1->f('id_departamento'); ?>" <?php
                                            if($general_departamento == $PSN1->f('id_departamento'))
                                            {
                                                ?>selected="selected" <?php
                                            }
                                            ?> ><?=$PSN1->f('departamento'); ?></option><?php
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-sm-2">
                                <?php $_SESSION['muni'] = $general_municipio; ?>
                                <div id="municipio"></div>
                            </div>
                            <div class="col-sm-4">
                                <strong>Dirección</strong><input name="direccion" type="text" id="direccion" value="<?=$general_direccion; ?>" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-1"></div>
                            <div class="col-sm-2">
                                <strong>Teléfono</strong><input name="telefono1" type="tel" id="telefono1" maxlength="11" value="<?=$general_telefono1; ?>" class="form-control" />
                            </div>
                            <div class="col-sm-2">
                                <strong>Celular</strong><input name="celular" type="tel" id="celular" maxlength="250" value="<?=$general_celular; ?>"  class="form-control"  />
                            </div>  
                            <div class="col-sm-3">
                                <strong>Correo electrónico</strong><input name="email" type="email" id="email" maxlength="250" value="<?=$general_email; ?>" class="form-control" />
                            </div>  
                            <div class="col-sm-3">
                                <strong>Codígo vídeo de YouTube</strong><input name="url" type="text" id="url" maxlength="250" value="<?=$general_url; ?>" class="form-control" />
                            </div>          
                        </div>
                        <div class="form-group">
                            <div class="col-sm-1"></div>
                            
                            <div class="col-sm-4">
                                <strong>Enlace de Drive</strong><input name="url2" type="text" id="url2" value="<?=$general_url2; ?>" class="form-control" />
                            </div>
                            <div class="col-sm-3">
                                <strong>Foto (200*200 pixeles - .jpg)</strong><input name="archivo" type="file" id="archivo" class="form-control" />
                            </div>
                            <div class="col-sm-1"><?php
                            if(file_exists("images/usuarios/".$idUsuarioActual.".jpg"))
                            {
                                ?><img src="images/usuarios/<?=$idUsuarioActual;?>.jpg" align="middle" width="80" height="80"><?php
                            }else{
                                ?><img src="images/consultores/desconocido.jpg" align="middle" width="80" height="80"><?php
                            }   
                            ?></div>
                        </div>
                    </div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "GENERAL" //-->
                    <div id="empresa" class="tab-pane fade">
                        <div class="cont-tit">
                            <div class="hr"><hr></div>
                            <div class="tit-cen">
                                <h3 class="text-center">INFORMACIÓN ORGANIZACIONAL</h3>
                                <h5>Información organizacional del usuario</h5>
                            </div>
                            <div class="hr"><hr></div>
                        </div>
                        <?php
                        //  USUARIO INTERNO NO NECESITA TODOS LOS CAMPOS NI TAMPOCO EL AUTORIZADO
                        if($ctrl != 1 && $ctrl != 4){?>
                            <div class="form-group">
                                <div class="col-sm-1"></div>
                                <div class="col-sm-3">
                                    <strong>Tipo de ministerio:</strong>
                                    <select name="empresa_tipo" class="form-control">
                                        <option value="">Sin especificar</option>
                                        <?php
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
                                                ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                                if($empresa_tipo == $PSN1->f('id'))
                                                {
                                                    ?>selected="selected"<?php
                                                }
                                                ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-sm-3">	
                                    <strong>Representante legal:</strong>
                                    <input name="empresa_representante" type="text" id="empresa_representante" maxlength="255" value="<?=$empresa_representante; ?>" class="form-control" />
                                </div>
                                <div class="col-sm-4">
                                    <strong>Nombre contacto:</strong>
                                    <input name="empresa_contacto" type="text" id="empresa_contacto" maxlength="255" value="<?=$empresa_contacto; ?>" class="form-control" />
                                </div>		
                            </div>
                            <?php
                            //  Campos NO aplican para cliente ni para proveedor
                            if($ctrl != 2 && $ctrl != 3 && $ctrl != 1 && $ctrl != 4){?>
                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="empresa_direccion"><strong>Dirección:</strong></label>
                                    <div class="col-sm-4"><input name="empresa_direccion" type="text" id="empresa_direccion" maxlength="255" value="<?=$empresa_direccion; ?>" class="form-control" /></div>

                                    <label class="control-label col-sm-2" for="empresa_url"><strong>Página Web:</strong></label>
                                    <div class="col-sm-4"><input name="empresa_url" type="text" id="empresa_url" maxlength="255" value="<?=$empresa_url; ?>" class="form-control" /></div>
                                </div><?php
                            }
                        }
                        //  Campos NO aplican para cliente ni para proveedor
                        if($ctrl != 2 && $ctrl != 3){ ?>
                            <div class="form-group">
                                <div class="col-sm-1"></div>
                                <div class="col-sm-2">
                                    <strong>Nombre del país:</strong>
                                    <select name="empresa_paisid" class="form-control">
                                        <option value="">Sin especificar</option>
                                        <option value="282" selected>Colombia</option>
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <strong>Zona a la que pertenece:</strong>
                                    <select name="empresa_sitio_cor" id="zona" class="form-control" required>
                                        <option value="">Sin especificar</option>
                                        <option value="0" <?php if($general_zona == 0){?>
                                                    selected="selected" <?php
                                                } ?>>Todas la zonas</option>
                                        <?php
                                        /*
                                        *   TRAEMOS LOS TIPOS DE CLIENTE/EMPRESA (15)
                                        */
                                        $sql = "SELECT * ";
                                        $sql.=" FROM categorias ";
                                        $sql.=" WHERE idSec = 85 ORDER BY descripcion asc";


                                        $PSN1->query($sql);
                                        $numero=$PSN1->num_rows();
                                        if($numero > 0){
                                            while($PSN1->next_record()){
                                                ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                                if($general_zona == $PSN1->f('id')){?>
                                                    selected="selected" <?php
                                                }
                                                ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                            }
                                        }?>
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <?php $_SESSION['regional'] = $general_regional; ?>
                                    <div id="regional"></div>
                                </div>
                                <div class="col-sm-3">
                                    <strong>Nombre del socio:</strong>
                                    <input name="empresa_socio" readonly type="text" id="empresa_socio" maxlength="50" value="Confraternidad Carcelaria de Colombia" class="form-control" />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-1"></div>
                                <div class="col-sm-3">
                                    <strong>Programa al que pertenece:</strong>
                                    <select name="empresa_proceso" class="form-control">
                                        <option value="">Sin especificar</option>
                                        <?php
                                        /*
                                        *   TRAEMOS LOS TIPOS DE CLIENTE/EMPRESA (15)
                                        */
                                        $sql = "SELECT * ";
                                        $sql.=" FROM categorias ";
                                        $sql.=" WHERE idSec = 38 ORDER BY descripcion asc";
                                        $PSN1->query($sql);
                                        $numero=$PSN1->num_rows();
                                        if($numero > 0){
                                            while($PSN1->next_record()){
                                                ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                                if($empresa_proceso == $PSN1->f('id'))
                                                {
                                                    ?>selected="selected"<?php
                                                }
                                                ?> ><?=$PSN1->f('descripcion'); ?></option><?php
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-sm-2 cont-flex-2">
                                    <strong>¿Recibe Apoyo Financiero? </strong>
                                </div>
                                <div class="col-sm-1 cont-flex-2">
                                   <strong>NO</strong>
                                    <input name="empresa_rm" type="radio" id="empresa_rm" value="NO" <?php if($empresa_rm == "NO"){ ?>checked<?php }; ?> class="form-control" /> 
                                </div>
                                <div class="col-sm-1 cont-flex-2">
                                    <strong>SI</strong>
                                    <input name="empresa_rm" type="radio" id="empresa_rm" value="SI"  <?php if($empresa_rm == "SI"){ ?>checked<?php }; ?> class="form-control" />
                                </div>
                            </div>
                        <?php }
                        /*
                        *   USUARIO AUTORIZADO - MONTO DE APROBACIÓN DE COTIZACIONES
                        */
                        if($ctrl == 4 || $ctrl == 2){?>
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="empresa_aprobacion"><strong>Monto de aprobación:</strong></label>
                                <div class="col-sm-4">
                                    <input name="empresa_aprobacion" type="text" id="empresa_aprobacion" maxlength="255" value="<?=$empresa_aprobacion; ?>" class="form-control" />
                                </div>
                            </div>
                        <?php } ?>
                    </div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "EMPRESA" //-->
                    <?php
                    //  USUARIO PROVEEDOR
                    if($ctrl == 3){?>
                        <div id="servicios" class="tab-pane fade">
                            <div class="row">
                                <h3 class="text-center well">INFORMACIÓN DE PROVEEDOR</h3>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="servicios_tipoPersona"><strong>Tipo de persona:</strong></label>
                                <div class="col-sm-10">
                                    <select name="servicios_tipoPersona" class="form-control">
                                        <option value="">Sin especificar</option>
                                        <?php
                                        /*
                                        *	TRAEMOS LOS TIPOS DE PERSONA JURIDICA O NATURAL
                                        */
                                        $sql = "SELECT * ";
                                        $sql.=" FROM categorias ";
                                        $sql.=" WHERE idSec = 29 ORDER BY descripcion asc";

                                        $PSN1->query($sql);
                                        $numero=$PSN1->num_rows();
                                        if($numero > 0){
                                            while($PSN1->next_record()){
                                                ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                                if($servicios_tipoPersona == $PSN1->f('id'))
                                                {
                                                    ?>selected="selected"<?php
                                                }
                                                ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>	  
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="servicios_tipo1"><strong>Tipo de servicio:</strong></label>
                                <div class="col-sm-4">
                                    <select name="servicios_tipo1" class="form-control">
                                        <option value="">Sin especificar</option>
                                        <?php
                                        /*
                                        *	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                                        */
                                        $sql = "SELECT * ";
                                        $sql.=" FROM categorias ";
                                        $sql.=" WHERE idSec = 25 ORDER BY descripcion asc";

                                        $PSN1->query($sql);
                                        $numero=$PSN1->num_rows();
                                        if($numero > 0){
                                            while($PSN1->next_record()){
                                                ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                                if($servicios_tipo1 == $PSN1->f('id'))
                                                {
                                                    ?>selected="selected"<?php
                                                }
                                                ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-sm-4">	
                                    <strong>Tipo de servicio 2:</strong>
                                    <select name="servicios_tipo2" class="form-control">
                                        <option value="">Sin especificar</option>
                                        <?php
                                        /*
                                        *	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                                        */

                                        $sql = "SELECT * ";
                                        $sql.=" FROM categorias ";
                                        $sql.=" WHERE idSec = 25 ORDER BY descripcion asc";

                                        $PSN1->query($sql);
                                        $numero=$PSN1->num_rows();
                                        if($numero > 0){
                                            while($PSN1->next_record()){
                                                ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                                if($servicios_tipo2 == $PSN1->f('id'))
                                                {
                                                    ?>selected="selected"<?php
                                                }
                                                ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                            }
                                        }  ?>
                                    </select>
                                </div>	        
                            </div>    
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="servicios_contrato1">
                                    <strong>Tipo de contrato:</strong></label>
                                <div class="col-sm-4">
                                    <select name="servicios_contrato1" class="form-control">
                                        <option value="">Sin especificar</option>            
                                        <?php
                                        /*
                                        *	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                                        */
                                        $sql = "SELECT * ";
                                        $sql.=" FROM categorias ";
                                        $sql.=" WHERE idSec = 26 ORDER BY descripcion asc";

                                        $PSN1->query($sql);
                                        $numero=$PSN1->num_rows();
                                        if($numero > 0){
                                            while($PSN1->next_record()){
                                                ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                                if($servicios_contrato1 == $PSN1->f('id'))
                                                {
                                                    ?>selected="selected"<?php
                                                }
                                                ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                            }
                                        }
                                        ?>
                                        </select>
                                    </div>	
                                    <label class="control-label col-sm-2" for="servicios_contrato2"><strong>Tipo de contrato 2:</strong></label>
                                    <div class="col-sm-4"><select name="servicios_contrato2" class="form-control">
                                        <option value="">Sin especificar</option>
                                        <?php
                                        /*
                                        *	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                                        */
                                        $sql = "SELECT * ";
                                        $sql.=" FROM categorias ";
                                        $sql.=" WHERE idSec = 26 ORDER BY descripcion asc";

                                        $PSN1->query($sql);
                                        $numero=$PSN1->num_rows();
                                        if($numero > 0){
                                            while($PSN1->next_record()){
                                                ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                                if($servicios_contrato2 == $PSN1->f('id'))
                                                {
                                                    ?>selected="selected"<?php
                                                }
                                                ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>    
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="servicios_observaciones"><strong>Ampliación de los servicios prestados:</strong></label>
                                <div class="col-sm-10">
                                    <textarea name="servicios_observaciones" id="servicios_observaciones" class="form-control"  ><?=$servicios_observaciones; ?></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <h3 class="text-center well">FECHAS DE VIGENCIA</h3>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="servicios_fechaInicio"><strong>Fecha de inicio:</strong></label>
                                <div class="col-sm-4">
                                    <input name="servicios_fechaInicio"  type="date"  placeholder="AAAA-MM-DD" id="servicios_fechaInicio" value="<?=$servicios_fechaInicio; ?>" class="form-control" />
                                </div>
                                <label class="control-label col-sm-2" for="servicios_fechaFin"><strong>Fecha final:</strong></label>
                                <div class="col-sm-4">
                                    <input name="servicios_fechaFin"  type="date" placeholder="AAAA-MM-DD" id="servicios_fechaFin"  value="<?=$servicios_fechaFin; ?>" class="form-control" />
                                </div>		
                            </div>    
                            <div class="row">
                                <h3 class="text-center well">DESCUENTO</h3>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="servicios_porcentaje"><strong>Porcentaje de descuento:</strong></label>
                                <div class="col-sm-4">
                                    <input name="servicios_porcentaje"  type="number"  id="servicios_porcentaje" value="<?=$servicios_porcentaje; ?>" class="form-control" />
                                </div>
                            </div>    
                        </div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "SERVICOS" //-->
                    <?php 
                    } 
                    //  USUARIO cliente
                    if($ctrl == 2){?>
                        <div id="cliente" class="tab-pane fade">
                            <div class="row">
                                <h3 class="text-center well">INFORMACIÓN DE CLIENTE</h3>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="cliente_tipoPersona"><strong>Tipo de persona:</strong></label>
                                <div class="col-sm-10">
                                    <select name="cliente_tipoPersona" class="form-control">
                                        <option value="">Sin especificar</option>
                                        <?php
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
                                                ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                                if($cliente_tipoPersona == $PSN1->f('id'))
                                                {
                                                    ?>selected="selected"<?php
                                                }
                                                ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>	  
                            </div>	      
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="cliente_tipo1"><strong>Tipo de servicio:</strong></label>
                                <div class="col-sm-4">
                                    <select name="cliente_tipo1" class="form-control">
                                        <option value="">Sin especificar</option>
                                        <?php
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
                                                ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                                if($cliente_tipo1 == $PSN1->f('id'))
                                                {
                                                    ?>selected="selected"<?php
                                                }
                                                ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>	
                                <label class="control-label col-sm-2" for="cliente_servicio1"><strong>Tipo de contrato:</strong></label>
                                <div class="col-sm-4">
                                    <select name="cliente_servicio1" class="form-control">
                                        <option value="">Sin especificar</option>            
                                        <?php
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
                                                ?><option value="<?=$PSN1->f('id'); ?>" <?php
                                                if($cliente_servicio1 == $PSN1->f('id'))
                                                {
                                                    ?>selected="selected"<?php
                                                }
                                                ?>><?=$PSN1->f('descripcion'); ?></option><?php
                                            }
                                        }                                    ?>
                                    </select>
                                </div>	
                            </div>    
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="cliente_observaciones"><strong>Ampliación de los servicios ofrecidos:</strong></label>
                                <div class="col-sm-10">
                                    <textarea name="cliente_observaciones" id="cliente_observaciones" class="form-control"  ><?=$cliente_observaciones; ?></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="cliente_valor1"><strong>Valor del contrato:</strong></label>
                                <div class="col-sm-4">
                                    <input name="cliente_valor1" type="text" id="cliente_valor1" maxlength="255" value="<?=$cliente_valor1; ?>" class="form-control" />
                                </div>
                                <label class="control-label col-sm-2" for="cliente_diaPago"><strong>Día de pago:</strong></label>
                                <div class="col-sm-4">
                                    <input name="cliente_diaPago" type="number" id="cliente_diaPago" value="<?=$cliente_diaPago; ?>" class="form-control" />
                                </div>		
                            </div>
                            <div class="row">
                                <h3 class="text-center well">FECHAS DE VIGENCIA</h3>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="cliente_fechaAprob"><strong>Fecha de aprobación cliente:</strong></label>
                                <div class="col-sm-4">
                                    <input name="cliente_fechaAprob"  type="date"  placeholder="AAAA-MM-DD" id="cliente_fechaAprob" value="<?=$cliente_fechaAprob; ?>" class="form-control" />
                                </div>
                                <label class="control-label col-sm-2" for="cliente_fechaAprobCont"><strong>Fecha aprobación contrato:</strong></label>
                                <div class="col-sm-4">
                                    <input name="cliente_fechaAprobCont"  type="date" placeholder="AAAA-MM-DD" id="cliente_fechaAprobCont"  value="<?=$cliente_fechaAprobCont; ?>" class="form-control" />
                                </div>		
                            </div>    
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="cliente_fechaInicial"><strong>Fecha de inicio contrato:</strong></label>
                                <div class="col-sm-4">
                                    <input name="cliente_fechaInicial"  type="date"  placeholder="AAAA-MM-DD" id="cliente_fechaInicial" value="<?=$cliente_fechaInicial; ?>" class="form-control" />
                                </div>
                                <label class="control-label col-sm-2" for="cliente_fechaFinal"><strong>Fecha final contrato:</strong></label>
                                <div class="col-sm-4">
                                    <input name="cliente_fechaFinal"  type="date" placeholder="AAAA-MM-DD" id="cliente_fechaFinal"  value="<?=$cliente_fechaFinal; ?>" class="form-control" />
                                </div>		
                            </div>    
                        </div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "SERVICOS" //-->    
                    <?php
                    }    
                    //  USUARIO cliente o proveedor
                    if($ctrl == 1 || $ctrl == 2 || $ctrl == 3){?>
                        <div id="archivos" class="tab-pane fade">
                            <div class="cont-tit">
                                <div class="hr"><hr></div>
                                <div class="tit-cen">
                                    <h3 class="text-center">DOCUMENTOS DEL USUARIO</h3>
                                    <h5>Cargue la documentación del usuario</h5>
                                </div>
                                <div class="hr"><hr></div>
                            </div>  
                            <?php if($ctrl == 2 || $ctrl == 3){?>
                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="documento_rut"><strong>RUT:</strong></label>
                                    <div class="col-sm-4">
                                        <input name="documento_rut" type="file" id="documento_rut"  class="form-control" />
                                    </div>
                                    <label class="control-label col-sm-2" for="documento_constitucion"><strong>Constitución:</strong></label>
                                    <div class="col-sm-4">
                                        <input name="documento_constitucion" type="file" id="documento_constitucion"  class="form-control" />
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="form-group">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-4">
                                    <strong>Descripción del documento:</strong>
                                    <input name="documento_adicional_nom" type="text" id="documento_adicional_nom"  placeholder="Descripción del documento que va a agregar" class="form-control" />
                                </div>
                                <div class="col-sm-4">
                                    <strong>Adjuntar archivo:</strong>
                                    <input name="documento_adicional_file" type="file" id="documento_adicional_file"  class="form-control" />
                                </div>
                                <div class="col-sm-2"></div>
                            </div>
                            <?php if($idUsuarioActual  > 0){ ?>
                                <div class="cont-tit">
                                    <div class="hr"><hr></div>
                                    <div class="tit-cen">
                                        <h3 class="text-center">DOCUMENTOS CARGADOS</h3>
                                        <h5>Aqui encontrara la documentación del usuario</h5>
                                    </div>
                                    <div class="hr"><hr></div>
                                </div> 
                                <div class="form-group">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-8">
                                        <table class="table table-striped table-hover">
                                            <?php
                                            if($documento_identificacion != ""){?>
                                                <tr>
                                                    <td><a href='descarga_usuario.php?&archivo=<?=$documento_identificacion; ?>'>Documento de Identificación</a></td>
                                                    <td><a href='index.php?&doc=usuario&deldoc=identificacion&id=<?=$idUsuarioActual; ?>&deldoc_name=<?=$documento_identificacion; ?>'>[BORRAR]</a></td>
                                                </tr><?php
                                            }

                                            if($documento_rut != ""){?>
                                                <tr>
                                                    <td><a href='descarga_usuario.php?&archivo=<?=$documento_rut; ?>'>RUT</a></td>
                                                    <td><a href='index.php?&doc=usuario&deldoc=rut&id=<?=$idUsuarioActual; ?>&deldoc_name=<?=$documento_rut; ?>'>[BORRAR]</a></td>                    
                                                </tr><?php
                                            }

                                            if($documento_constitucion != ""){?>
                                                <tr>
                                                    <td><a href='descarga_usuario.php?&archivo=<?=$documento_constitucion; ?>'>Constitución</a></td>
                                                    <td><a href='index.php?&doc=usuario&deldoc=constitucion&id=<?=$idUsuarioActual; ?>&deldoc_name=<?=$documento_constitucion; ?>'>[BORRAR]</a></td>                    
                                                </tr><?php
                                            }

                                            if($documento_contrato != ""){?>
                                                <tr>
                                                    <td><a href='descarga_usuario.php?&archivo=<?=$documento_contrato; ?>'>Contrato</a></td>
                                                    <td><a href='index.php?&doc=usuario&deldoc=contrato&id=<?=$idUsuarioActual; ?>&deldoc_name=<?=$documento_contrato; ?>'>[BORRAR]</a></td>                    
                                                </tr><?php
                                            }

                                            /*
                                            *	TRAEMOS LOS DOCUMENTOS ADICIONALES
                                            */
                                            $sql = "SELECT * ";
                                            $sql.=" FROM usuario_documentos_add	 ";
                                            $sql.=" WHERE idUsuario = '".$idUsuarioActual."' ORDER BY descripcion asc";
                                            //
                                            $PSN1->query($sql);
                                            $numero=$PSN1->num_rows();
                                            if($numero > 0){
                                                while($PSN1->next_record()){?>
                                                    <tr>
                                                        <td width="90%" style="vertical-align: middle;"><a href='descarga_usuario.php?&archivo=<?=$PSN1->f('archivo');?>' target="_blank"><i class="fas fa-file-pdf" style="font-size: 18px;"></i> <?=$PSN1->f('descripcion')." - ".$general_nombre; ?></a></td>
                                                        <td width="10%"><a href='index.php?&doc=usuario&deldoc=<?=$PSN1->f('id'); ?>&id=<?=$idUsuarioActual; ?>&deldoc_name=<?=$PSN1->f('archivo'); ?>' class="btn btn-danger">Eliminar archivo</a></td>
                                                    </tr><?php                        
                                                }
                                            }else{?> 
                                                <tr>
                                                    <td>
                                                        <div>
                                                          <i class="far fa-file-alt"></i> No se encontraron archivos cargados en el sistema  
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php } ?>            
                                        </table>
                                    </div>
                                    <div class="col-sm-2"></div>
                                </div>
                            <?php  } ?>
                        </div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "DOCUMENTOS" //--><?php
                    }?>
                    <div id="accesos" class="tab-pane fade">
                        <div class="cont-tit">
                            <div class="hr"><hr></div>
                            <div class="tit-cen">
                                <h3 class="text-center">ACCESO AL SISTEMA</h3>
                                <h5>Configuración de acceso al sistema</h5>
                            </div>
                            <div class="hr"><hr></div>
                        </div>
                        <div class="form-group">
                            <?php if (!empty($general_identificacion)) {?>
                                <div class="col-sm-1"></div>
                                <div class="col-sm-2">
                                    <strong>Usuario:</strong>
                                    <input readonly name="usuario" type="text" id="usuario" class="form-control" minlength="4" value="<?=$general_identificacion; ?>" />
                                </div>
                            <?php }else{?>
                                <div class="col-sm-3"></div>
                            <?php } ?>
                            <div class="col-sm-2">
                                <strong>Contraseña:</strong>
                                <input name="password" type="password" id="password" class="form-control" minlength="4" />
                            </div>
                            <div class="col-sm-2">
                                <strong>Repita la contraseña:</strong>
                                <input name="password_check" type="password" id="password_check" maxlength="250" value="" class="form-control" minlength="4" />
                            </div>
                            <div class="col-sm-3 ">
                                <strong>¿Puede acceder al sistema?:</strong><br>
                                <label>
                                    <input width="30px" name="acceso" type="checkbox" id="acceso" value="1" <?php if($general_acceso == 1){ ?>checked="checked"<?php } ?> />
                                    <span class="check"></span>
                                </label>                            
                            </div>
                        </div>
                        <?php if($ctrl == 1 || $ctrl == 4 || $ctrl == 2){?>
                            <div class="cont-tit">
                                <div class="hr"><hr></div>
                                <div class="tit-cen">
                                    <h3 class="text-center">ACCESO AL MENÚ</h3>
                                    <h5>Configuración de acceso al sistema</h5>
                                </div>
                                <div class="hr"><hr></div>
                            </div>
                            <div class="form-group">
                                <?php if($idUsuarioActual != 0){
                                    $sql = "SELECT menu.*, usuarios_menu.idUsuario ";
                                    $sql.=" FROM menu ";
                                    $sql.=" LEFT JOIN usuarios_menu ON
                                                usuarios_menu.idMenu = menu.id AND 
                                                usuarios_menu.idUsuario = ".$idUsuarioActual;
                                    //Usuario autorizado cliente o cliente                
                                    if($ctrl == 4 || $ctrl == 2){
                                        $sql.=" WHERE menu.paracliente = 1 AND menu.estado = 1";
                                    }else{
                                        $sql.=" WHERE menu.estado = 1";                    
                                    }
                                    //                    
                                    $sql.=" ORDER BY principal, orden asc";
                                }else{
                                    $sql = "SELECT * ";
                                    $sql.=" FROM menu ";
                                    //                
                                    if($ctrl == 4 || $ctrl == 2){
                                        $sql.=" WHERE menu.paracliente = 1 AND menu.estado = 1";
                                    }else{
                                        $sql.=" WHERE menu.estado = 1";                    
                                    }
                                    //                    
                                    $sql.=" ORDER BY principal, orden asc";
                                }
                                //
                                $PSN2->query($sql);
                                $numero=$PSN2->num_rows();
                                if($numero > 0){
                                    $cont = 0;
                                    $principal_old = 0;
                                    while($PSN2->next_record()){
                                        //echo "Registro: ". $PSN2->f("principal");
                                        if($principal_old != $PSN2->f("principal")){
                                            echo '</div>';?>
                                            <div class="form-group">
                                                <h5 style="margin-bottom: 0px;" class="alert alert-info"><?php
                                                    $principal_old = $PSN2->f("principal");
                                                    switch($PSN2->f("principal")){
                                                        case 1:
                                                            echo "Administración del Sistema";
                                                            break;
                                                        case 2:
                                                            echo "SMS + Emailing";
                                                            break;
                                                        case 3:
                                                            echo "Cotización inicial";
                                                            break;
                                                        case 4:
                                                            echo "Reportes";
                                                            break;
                                                        case 5:
                                                            echo "Evangelistas";
                                                            break;
                                                        case 6:
                                                            echo "La peregrinación del prisionero - LPP";
                                                            break;
                                                        case 7:
                                                            echo "Capacitar y multiplicar - C&M";
                                                            break;
                                                        case 8:
                                                            echo "Proyecto Felipe";
                                                            break;
                                                        case 9:
                                                            echo "Instituto Biblico";
                                                            break;
                                                        case 99:
                                                            echo "Mi cuenta";
                                                            break;
                                                        default:
                                                            echo "Otras opciones";
                                                            break;
                                                    }?>
                                                </h5>
                                            </div>
                                            <?php $cont = 0;
                                        }
                                        if ($cont==0) {
                                            echo '<div class="form-group cont-flex">';
                                        }?>                                   
                                        <label class="col-sm-2 ico-list">
                                            <?=$PSN2->f('imagen'); ?> 
                                            <strong><?=$PSN2->f('nombre'); ?></strong>
                                        </label>
                                        <div class="col-sm-1">
                                            <label>
                                                <input type="checkbox" name="menu[]" value="<?=$PSN2->f('id'); ?>"  <?php
                                                if($PSN2->f('idUsuario') != "" && $PSN2->f('idUsuario') != 0){
                                                ?>checked="checked"<?php }?> />
                                                <span class="check"></span>
                                            </label>
                                        </div>
                                        <?php 
                                        //$principal_old = $PSN2->f("principal"); 
                                        $cont++;                                
                                    }              
                                }?>
                            </div><?php  
                        } ?>    
                    </div> <!-- FIN TAB DE ACCESOS //-->
                    <?php
                    //
                    //
                    if($ctrl == 1 || $ctrl == 2 || $ctrl == 4){?>
                        <div id="graficas" class="tab-pane fade">
                            <div class="cont-tit">
                                <div class="hr"><hr></div>
                                <div class="tit-cen">
                                    <h3 class="text-center">ACCESO GENERAL A GRÁFICAS</h3>
                                    <h5>Configuración de acceso general a gráficas</h5>
                                </div>
                                <div class="hr"><hr></div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-4"></div>
                                <label class="control-label col-sm-2" for="acceso_graphs"><strong>Acceso a gráficas:</strong></label>
                                <div class="col-sm-1">
                                    <label>
                                        <input name="acceso_graphs" type="checkbox" id="acceso_graphs" value="1" <?php if($general_acceso_graphs == "1"){ ?>checked="checked"<?php } ?> class="form-control" />
                                        <span class="check"></span>
                                    </label>
                                </div>	
                            </div>
                            <div class="cont-tit">
                                <div class="hr"><hr></div>
                                <div class="tit-cen">
                                    <h3 class="text-center">ACCESO A GRÁFICAS</h3>
                                    <h5>Configuración de acceso a gráficas</h5>
                                </div>
                                <div class="hr"><hr></div>
                            </div>
                            <div class="form-group">
                                <?php
                                if($idUsuarioActual != 0){
                                    /*
                                    *	ITEMS DEL MENU
                                    */
                                    $sql = "SELECT menu_graphs.*, usuarios_menu_graphs.idUsuario ";
                                    $sql.=" FROM menu_graphs ";
                                    $sql.=" LEFT JOIN usuarios_menu_graphs ON
                                                usuarios_menu_graphs.idMenu = menu_graphs.id AND 
                                                usuarios_menu_graphs.idUsuario = ".$idUsuarioActual;
                                    //Usuario autorizado cliente o cliente                
                                    if($ctrl == 4 || $ctrl == 2){
                                        $sql.=" WHERE menu_graphs.paracliente = 1 AND menu_graphs.estado = 1";
                                    }else{
                                        $sql .=" WHERE menu_graphs.estado = 1";
                                    }
                                    //
                                    $sql.=" ORDER BY principal, orden asc";
                                }else{
                                    /*
                                    *	ITEMS DEL MENU
                                    */
                                    $sql = "SELECT * ";
                                    $sql.=" FROM menu_graphs ";
                                    //                
                                    if($ctrl == 4 || $ctrl == 2){
                                        $sql.=" WHERE menu_graphs.paracliente = 1 AND menu_graphs.estado = 1";
                                    }else{
                                        $sql .=" WHERE menu_graphs.estado = 1";
                                    }
                                    //
                                    $sql.=" ORDER BY principal, orden asc";
                                }
                                //
                                $PSN1->query($sql);
                                $numero=$PSN1->num_rows();
                                if($numero > 0){
                                    $cont = 0;
                                    $principal_old = 0;
                                    while($PSN1->next_record()){?>
                                        <label class="control-label col-sm-2 ico-list" for="menu_graphs"><?=$PSN1->f('imagen'); ?> <strong><?=$PSN1->f('nombre'); ?></strong></label>
                                        <div class="col-sm-1">
                                            <label>
                                                <input type="checkbox" name="menu_graphs[]" value="<?=$PSN1->f('id'); ?>" class="form-control" <?php
                                                if($PSN1->f('idUsuario') != "" && $PSN1->f('idUsuario') != 0){
                                                    ?>checked="checked"<?php
                                                }
                                                ?> />
                                                <span class="check"></span>
                                            </label>
                                        </div>
                                        <?php
                                        $cont++;
                                    }   
                                }?>
                            </div>
                        </div> <!-- FIN TAB DE ACCESOS GRAFICOS //--> 
                    <?php }?>
                </div> <!-- FIN CONTENEDOR DE TABS //-->
                <input type="hidden" name="funcion" id="funcion" value="" />
                <div class="row">
                    <center>
                        <input type="submit" name="button" value="Guardar cambios" class="btn btn-success">
                        <?php if($_SESSION["id"] == 1){ ?>
                        <input type="button" name="button" onclick="generarFormDel()" class="btn btn-danger" value="Eliminar usuario" />
                        <?php } ?>
                        <a href="index.php?doc=main" class="btn btn-info">Cerrar</a>
                    </center>
                </div>
            </form>
            <?php
            if($idUsuarioActual != 0 && $ctrl == 2){?>
                <center>
                    <a href="index.php?doc=usuario_buscar&ctrl=4&cliente=<?=$idUsuarioActual; ?>" target="_blank" class="btn btn-primary">Ver autorizados</a> 
                    <a href="index.php?doc=usuario&ctrl=4&idCliente=<?=$idUsuarioActual; ?>" class="btn btn-primary">Crear autorizado</a>
                </center>
                <br />
                <center>
                    <a href="index.php?doc=vehiculo_buscar&idCliente=<?=$idUsuarioActual; ?>" target="_blank" class="btn btn-info">Ver vehículos</a> 
                    <a href="index.php?doc=vehiculo&idCliente=<?=$idUsuarioActual; ?>" class="btn btn-info">Crear vehículo</a>
                </center>
                <?php
            }?>
            <script language="javascript">
                function generarForm(){
                    if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?")){
                        if(document.getElementById('nombre').value != "" 
                        && document.getElementById('identificacion').value != ""){
                            if(document.getElementById('password').value != ""){
                                if(document.getElementById('password').value != document.getElementById('password_check').value){
                                    alert("Password no coincide");
                                    return false;
                                }else{
                                    //document.getElementById('form1').submit();
                                }
                            }
                            document.getElementById('funcion').value = "<?=$temp_accionForm; ?>";
                        }else
                        {
                            alert("La informacion es primordial para brindarle un excelente servicio, por favor digite al menos los campos de NOMBRE e IDENTIFICACIÓN");
                            return false;
                        }
                    }else{
                        return false;
                    }
                    return true;
                }
                function generarFormDel(){
                    if(confirm("Esta accion eliminara el usuario, Esta seguro que desea continuar?")){
                        if(document.getElementById('id_usuario').value != "")
                        {
                            document.getElementById('funcion').value = "eliminar";
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
                            return generarForm();
                    }

                    <?php
                    if($varExitoUSU == 1)
                    {
                        ?>alert("Se ha colocado correctamente el ACCESO, espere mientras es dirigido.");
                        window.location.href = "index.php?doc=admin_usu4&id=<?=$ultimoId;?>";<?php
                    }
                    ?>
                }

                jQuery(document).ready(function($) {
                    $(".clickable-row").click(function() {
                       window.location.href = $(this).data("href");
                    });
                });    

                window.onload = function(){
                    init();
                }
            </script><?php
        }?>
    </div><?php
}   //FIN DEL IF DE REDIRIGIR SI YA INSERTO EL REGISTRO ?>
<script type="text/javascript">
    $(document).ready(function(){
        recargaLista();
        $('#departamento').change(function(){
            recargaLista();
        });
        recargaListaZona();
        $('#zona').change(function(){
            recargaListaZona();
        });
    })
</script>
<script type="text/javascript">
    function recargaLista(){
        $.ajax({
            type: "POST",
            url: "datos_ubicacion.php",
            data: "id_depa=" + $('#departamento').val(),
            success: function(r){
                $('#municipio').html(r);
            }
        })
    }
    function recargaListaZona(){
        $.ajax({
            type: "POST",
            url: "datos_zona.php",
            data: "id_zona=" + $('#zona').val(),
            success: function(r){
                $('#regional').html(r);
            }
        })
    }
</script>