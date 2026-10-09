<?php
//Si es un usuario externo o cliente o proveedor NO mostrar.
if ($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 4 || $_SESSION["perfil"] == 160) {
    die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;
$PSN = new DBbase_Sql;
$webArchivo = "usuario";

/*
*   AFECTA FORMULARIO Y ACTUAR DE LA PÁGINA
    1   USUARIO INTERNO
    2   CLIENTE
    3   PROVEEDOR
    4   USUARIO CLIENTE
*/
if (!isset($_REQUEST["ctrl"]) || soloNumeros($_REQUEST["ctrl"]) == "" || soloNumeros($_REQUEST["ctrl"]) == "0") {
    $ctrl = 1;
} else {
    $ctrl = soloNumeros($_REQUEST["ctrl"]);
}

$soloLecturaFacilitador = (isset($_SESSION["perfil"]) && soloNumeros($_SESSION["perfil"]) == 163);

// Array que nos servira para ir llevando cuenta de los requerimientos.
$arrayRequerimientos = array();
if (isset($_POST["funcion"])) {
    if ($soloLecturaFacilitador) {
        $camposSoloLecturaFacilitador = array(
            "acceso" => 0,
            "acceso_graphs" => 0,
            "excluido_reportes" => 0,
            "empresa_tipo" => 0,
            "empresa_representante" => "",
            "empresa_contacto" => "",
            "empresa_direccion" => "",
            "empresa_url" => "",
            "empresa_telefono1" => "",
            "empresa_telefono2" => "",
            "empresa_celular1" => "",
            "empresa_celular2" => "",
            "empresa_email1" => "",
            "empresa_email2" => "",
            "empresa_cargo" => "",
            "empresa_aprobacion" => 0,
            "empresa_paisid" => 0,
            "empresa_pais" => "",
            "empresa_socio" => "",
            "empresa_proceso" => "",
            "empresa_pd" => "",
            "empresa_sitio_cor" => "",
            "empresa_sitio" => "",
            "empresa_rm" => "",
            "empresa_circuito" => 0
        );

        foreach ($camposSoloLecturaFacilitador as $campoSoloLectura => $valorSoloLectura) {
            if (!isset($_POST[$campoSoloLectura])) {
                $_POST[$campoSoloLectura] = $valorSoloLectura;
            }
        }
    }
    /*
    *   Para verificar errores a futuro.
        1   Campos requeridos en BLANCO (Nombre, identificacion, password)
        2   Password no coincide
        3   Identificacion YA existente
    */
    $error_datos = 0;

    if ($_POST["funcion"] == "insertar") {
        /*
         *   PESTAÑA GENERAL
         */
        $general_nombre = eliminarInvalidos($_POST["nombre"]);
        $general_tipo = soloNumeros($_POST["tipo"]);
        $general_tipo_user_cli = soloNumeros($_POST["tipo_user_cli"]);
        if ($general_tipo_user_cli == "")
            $general_tipo_user_cli = "0";
        $general_identificacion = eliminarInvalidos($_POST["identificacion"]);
        if (empty($_POST["municipio"])) {
            $general_municipio = 0;
        } else {
            $general_municipio = soloNumeros($_POST["municipio"]);
            if ($general_municipio == "")
                $general_municipio = "NULL";
            if ($general_municipio == "")
                $general_municipio = "NULL";
        }
        $general_tipoIdentificacion = soloNumeros($_POST["tipoIdentificacion"]);
        $general_direccion = eliminarInvalidos($_POST["direccion"]);
        $general_telefono1 = soloNumeros($_POST["telefono1"]);
        // TODO:ELIMINAR ESTE TELEFONO2
        $general_telefono2 = soloNumeros($_POST["telefono2"]);
        $general_celular = soloNumeros($_POST["celular"]);
        // TODO:Eliminar ESTE CELULAR 2
        $general_celular2 = soloNumeros($_POST["celular2"]);
        $general_email = eliminarInvalidos($_POST["email"]);
        $general_url = eliminarInvalidos($_POST["url"]);
        $general_url2 = eliminarInvalidos($_POST["url2"]);
        $general_observaciones = eliminarInvalidos($_POST["observaciones"]);
        $general_password = eliminarInvalidos($_POST["password"]);
        $general_acceso = eliminarInvalidos($_POST["acceso"]);
        $general_acceso_graphs = eliminarInvalidos($_POST["acceso_graphs"]);
        $general_excluido_reportes = isset($_POST["excluido_reportes"]) ? 1 : 0;
        if ($soloLecturaFacilitador) {
            $general_acceso = 0;
            $general_acceso_graphs = 0;
            $general_excluido_reportes = 0;
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


        if ($general_nombre == "" || $general_identificacion == "") {
            $error_datos = 1; //Datos requeridos en blanco
        }

        if ($general_password != "" && $general_password != $temp_password_check) {
            $error_datos = 2;   //Password diferente
        }

        /*
         *   COMPROBAMOS QUE IDENTIFICACION NO EXISTA
         */
        $sql = "SELECT id ";
        $sql .= " FROM usuario";
        $sql .= " WHERE identificacion = '" . $general_identificacion . "'";
        $PSN->query($sql);
        if ($PSN->next_record()) {
            $error_datos = 3;   //Identificacion ya existe
        }


        if ($error_datos == 0) {
            /*
             *	DEBEMOS INSERTAR LA INFORMACION DEL CLIENTE/USUARIO SEGUN CORRESPONDA.
             */
            $sql = 'INSERT INTO usuario (
                nombre,
                tipo,
                tipo_user_cli,
                identificacion,
                tipoIdentificacion,
                direccion,
                usua_muni,
                telefono1,
                telefono2,
                celular,
                celular2,
                email,
                url,
                url2,
                observaciones,
                acceso,
                acceso_graphs,
                excluido_reportes,
                password,
                creacionUsuario,
                creacionFecha
            ) ';
            $sql .= ' values 
                (
                "' . $general_nombre . '", 
                "' . $general_tipo . '", 
                ' . $general_tipo_user_cli . ', 
                "' . $general_identificacion . '", 
                "' . $general_tipoIdentificacion . '", 
                "' . $general_direccion . '", 
                ' . $general_municipio . ',
                "' . $general_telefono1 . '", 
                "' . $general_telefono2 . '", 
                "' . $general_celular . '", 
                "' . $general_celular2 . '", 
                "' . $general_email . '", 
                "' . $general_url . '", 
                "' . $general_url2 . '", 
                "' . $general_observaciones . '", 
                "' . $general_acceso . '", 
                "' . $general_acceso_graphs . '", 
                "' . $general_excluido_reportes . '", 
                "' . md5($general_password) . '",
                "' . $_SESSION["id"] . '",
                NOW()
            ) ';
            $ultimoQuery = $PSN1->query($sql);
            $ultimoId = $PSN1->ultimoId();

            //echo $ultimoId ;

            /*
             *   SE INSERTO EL USUARIO CORRECTAMENTE.
             */
            if ($ultimoId > 0) {
                /*
                 *   INSERTAMOS INFORMACIÓN EMPRESARIAL
                 */
                $empresa_nombre = "";
                $empresa_nit = "";
                $empresa_tipo = soloNumeros($_POST["empresa_tipo"]);
                if ($empresa_tipo == "")
                    $empresa_tipo = "0";
                //$empresa_nombre = eliminarInvalidos($_POST["empresa_nombre"]);
                //$empresa_nit = eliminarInvalidos($_POST["empresa_nit"]);
                $empresa_representante = eliminarInvalidos($_POST["empresa_representante"]);
                $empresa_contacto = eliminarInvalidos($_POST["empresa_contacto"]);
                $empresa_direccion = eliminarInvalidos($_POST["empresa_direccion"]);
                $empresa_url = eliminarInvalidos($_POST["empresa_url"]);
                $empresa_telefono1 = eliminarInvalidos($_POST["empresa_telefono1"]);
                // TODO:DEJAR PENDIENTE PARA QUITAR
                $empresa_telefono2 = eliminarInvalidos($_POST["empresa_telefono2"]);
                $empresa_celular1 = eliminarInvalidos($_POST["empresa_celular1"]);
                //   TODO:DEJAR PENDIENTE PARA QUITAR
                $empresa_celular2 = eliminarInvalidos($_POST["empresa_celular2"]);
                $empresa_email1 = eliminarInvalidos($_POST["empresa_email1"]);
                //   TODO:DEJAR PENDIENTE PARA QUITAR
                $empresa_email2 = eliminarInvalidos($_POST["empresa_email2"]);
                $empresa_cargo = eliminarInvalidos($_POST["empresa_cargo"]);
                $empresa_aprobacion = soloNumeros($_POST["empresa_aprobacion"]);
                if ($empresa_aprobacion == "")
                    $empresa_aprobacion = "0";


                $empresa_paisid = soloNumeros($_POST["empresa_paisid"]);

                $empresa_pais = eliminarInvalidos($_POST["empresa_pais"]);
                $empresa_socio = eliminarInvalidos($_POST["empresa_socio"]);
                $empresa_proceso = eliminarInvalidos($_POST["empresa_proceso"]);
                $empresa_pd = eliminarInvalidos($_POST["empresa_pd"]);
                $empresa_sitio_cor = eliminarInvalidos($_POST["empresa_sitio_cor"]);
                $empresa_sitio = eliminarInvalidos($_POST["empresa_sitio"]);
                $empresa_rm = eliminarInvalidos($_POST["empresa_rm"]);
                $empresa_circuito = soloNumeros($_POST["empresa_circuito"]);
                if ($empresa_circuito == "")
                    $empresa_circuito = "0";

                if ($soloLecturaFacilitador) {
                    $empresa_tipo = "0";
                    $empresa_representante = "";
                    $empresa_contacto = "";
                    $empresa_direccion = "";
                    $empresa_url = "";
                    $empresa_telefono1 = "";
                    $empresa_telefono2 = "";
                    $empresa_celular1 = "";
                    $empresa_celular2 = "";
                    $empresa_email1 = "";
                    $empresa_email2 = "";
                    $empresa_cargo = "";
                    $empresa_aprobacion = "0";
                    $empresa_paisid = "0";
                    $empresa_pais = "";
                    $empresa_socio = "";
                    $empresa_proceso = "";
                    $empresa_pd = "";
                    $empresa_sitio_cor = "";
                    $empresa_sitio = "";
                    $empresa_rm = "";
                    $empresa_circuito = "0";
                }





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
                    "' . $ultimoId . '", 
                    "' . $empresa_tipo . '",
                    "' . $empresa_nombre . '",
                    "' . $empresa_nit . '",
                    "' . $empresa_representante . '",
                    "' . $empresa_contacto . '",
                    "' . $empresa_direccion . '",
                    "' . $empresa_url . '",
                    "' . $empresa_telefono1 . '",
                    "' . $empresa_telefono2 . '",
                    "' . $empresa_celular1 . '",
                    "' . $empresa_celular2 . '",
                    "' . $empresa_email1 . '",
                    "' . $empresa_email2 . '",
                    "' . $empresa_cargo . '",
                    "' . $empresa_aprobacion . '",
                        "' . $empresa_paisid . '", 
                        "' . $empresa_pais . '", 
                        "' . $empresa_socio . '", 
                        "' . $empresa_proceso . '", 
                        "' . $empresa_pd . '", 
                        "' . $empresa_sitio_cor . '", 
                        "' . $empresa_sitio . '",
                        "' . $empresa_rm . '",
                        "' . $empresa_circuito . '"                                                
                ) ';
                $ultimoQuery = $PSN1->query($sql);

                /*
                 *   CARGUE DE PESTAÑA CLIENTE
                 */
                if ($ctrl == 2) {
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
                        "' . $ultimoId . '", 
                        "' . $cliente_tipo1 . '",
                        "' . $cliente_servicio1 . '",
                        "' . $cliente_observaciones . '",
                        "' . $cliente_valor1 . '",
                        "' . $cliente_diaPago . '",
                        "' . $cliente_fechaAprob . '",
                        "' . $cliente_fechaAprobCont . '",
                        "' . $cliente_fechaInicial . '",
                        "' . $cliente_fechaFinal . '",
                        "' . $cliente_tipoPersona . '"
                    ) ';
                    $ultimoQuery = $PSN1->query($sql);
                }

                /*
                 *   CARGUE DE PESTAÑA PROVEEDOR
                 */
                if ($ctrl == 3) {
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
                        "' . $ultimoId . '", 
                        "' . $servicios_tipo1 . '",
                        "' . $servicios_tipo2 . '",
                        "' . $servicios_contrato1 . '",
                        "' . $servicios_contrato2 . '",
                        "' . $servicios_observaciones . '",
                        "' . $servicios_fechaInicio . '",
                        "' . $servicios_fechaFin . '",
                        "' . $servicios_tipoPersona . '",
                        "' . $servicios_porcentaje . '"
                        
                    ) ';
                    $ultimoQuery = $PSN1->query($sql);
                }

                /*
                 *   CARGUE DE RELACION USUARIO-CLIENTE
                 */
                if ($ctrl == 4 && $idCliente != 0 && $idCliente != "") {
                    //
                    $sql = 'INSERT INTO usuario_relacion (
                        idUsuario1,
                        idUsuario2
                    ) ';
                    $sql .= ' values 
                        (
                        "' . $ultimoId . '", 
                        "' . $idCliente . '"
                    ) ';
                    $ultimoQuery = $PSN1->query($sql);
                }

                /*
                 *   INSERTAMOS ACCESOS AL SISTEMA.
                 */
                if (!$soloLecturaFacilitador && isset($_POST["menu"]) && is_array($_POST["menu"])) {
                    foreach ($_POST["menu"] as $menuopc) {                //
                        $sql = "REPLACE INTO usuarios_menu (idUsuario, idMenu) VALUES (" . $ultimoId . ", " . soloNumeros($menuopc) . ")";
                        $PSN1->query($sql);
                    }
                }

                /*
                 *   INSERTAMOS ACCESOS DE GRAFICAS AL SISTEMA.
                 */
                if (!$soloLecturaFacilitador && isset($_POST["menu_graphs"]) && is_array($_POST["menu_graphs"])) {
                    foreach ($_POST["menu_graphs"] as $menuopc) {                //
                        $sql = "REPLACE INTO usuarios_menu_graphs (idUsuario, idMenu) VALUES (" . $ultimoId . ", " . soloNumeros($menuopc) . ")";
                        $PSN1->query($sql);
                    }
                }

                //Compruebo si las características del archivo son las que deseo
                if (move_uploaded_file($_FILES['archivo']['tmp_name'], "images/usuarios/" . $ultimoId . ".jpg")) {
                }

                /*
                 *   GENERAMOS PREVENTIVAMENTE EL REGISTRO DE LOS DOCUMENTOS
                 */
                $sql = "INSERT INTO usuario_documentos (idUsuario) VALUES (" . $ultimoId . ")";
                $PSN1->query($sql);

                //
                //  Documento de IDENTIFICACIÓN
                //
                $nombre_archivo = $_FILES['documento_identificacion']['name'];
                $temp_location = $_FILES['documento_identificacion']['tmp_name'];
                $temp_ext = extension_archivo($nombre_archivo);
                $temp_nombreFile = "id" . $ultimoId . "." . $temp_ext;


                if (move_uploaded_file($temp_location, "archivos/usuarios/" . $temp_nombreFile)) {
                    $sql = "UPDATE usuario_documentos SET 
                        documento_identificacion = '" . $temp_nombreFile . "'
                        WHERE idUsuario = '" . $ultimoId . "'";
                    $PSN1->query($sql);
                }

                //
                //  Documento de RUT
                //
                $nombre_archivo = $_FILES['documento_rut']['name'];
                $temp_location = $_FILES['documento_rut']['tmp_name'];
                $temp_ext = extension_archivo($nombre_archivo);
                $temp_nombreFile = "rut" . $ultimoId . "." . $temp_ext;

                if (move_uploaded_file($temp_location, "archivos/usuarios/" . $temp_nombreFile)) {
                    $sql = "UPDATE usuario_documentos SET 
                        documento_rut = '" . $temp_nombreFile . "'
                        WHERE idUsuario = '" . $ultimoId . "'";
                    $PSN1->query($sql);
                }

                //
                //  Documento de CONSTITUCION
                //
                $nombre_archivo = $_FILES['documento_constitucion']['name'];
                $temp_location = $_FILES['documento_constitucion']['tmp_name'];
                $temp_ext = extension_archivo($nombre_archivo);
                $temp_nombreFile = "cons" . $ultimoId . "." . $temp_ext;

                if (move_uploaded_file($temp_location, "archivos/usuarios/" . $temp_nombreFile)) {
                    $sql = "UPDATE usuario_documentos SET 
                        documento_constitucion = '" . $temp_nombreFile . "'
                        WHERE idUsuario = '" . $ultimoId . "'";
                    $PSN1->query($sql);
                }

                //
                //  Documento de CONTRATO
                //
                $nombre_archivo = $_FILES['documento_contrato']['name'];
                $temp_location = $_FILES['documento_contrato']['tmp_name'];
                $temp_ext = extension_archivo($nombre_archivo);
                $temp_nombreFile = "contrato" . $ultimoId . "." . $temp_ext;

                if (move_uploaded_file($temp_location, "archivos/usuarios/" . $temp_nombreFile)) {
                    $sql = "UPDATE usuario_documentos SET 
                        documento_contrato = '" . $temp_nombreFile . "'
                        WHERE idUsuario = '" . $ultimoId . "'";
                    $PSN1->query($sql);
                }

                //
                //  Documento ADICIONAL
                //
                $nombre_archivo = $_FILES['documento_adicional_file']['name'];
                $temp_location = $_FILES['documento_adicional_file']['tmp_name'];
                $temp_ext = extension_archivo($nombre_archivo);
                $temp_nombreFile = strtotime("now") . "." . $temp_ext;

                if (move_uploaded_file($temp_location, "archivos/usuarios/" . $temp_nombreFile)) {
                    $sql = "INSERT INTO usuario_documentos_add 
                            (
                            idUsuario, 
                            descripcion, 
                            archivo
                        ) 
                        VALUES 
                            (
                            " . $ultimoId . ", 
                            '" . eliminarInvalidos($_REQUEST["documento_adicional_nom"]) . "', 
                            '" . $temp_nombreFile . "'
                    )";
                    $PSN1->query($sql);
                    //
                }
            }
            $varExitoUSU = 1;
        }
    } //Fin del IF de insertar
    // TODO:ACTUALIZAR
    else if ($_POST["funcion"] == "actualizar") {
        $idUsuarioActual = soloNumeros($_REQUEST["id"]);
        if ($soloLecturaFacilitador) {
            $bloqueado_general_acceso = 0;
            $bloqueado_general_acceso_graphs = 0;
            $bloqueado_general_excluido_reportes = 0;
            $bloqueado_empresa_tipo = "0";
            $bloqueado_empresa_representante = "";
            $bloqueado_empresa_contacto = "";
            $bloqueado_empresa_direccion = "";
            $bloqueado_empresa_url = "";
            $bloqueado_empresa_telefono1 = "";
            $bloqueado_empresa_telefono2 = "";
            $bloqueado_empresa_celular1 = "";
            $bloqueado_empresa_celular2 = "";
            $bloqueado_empresa_email1 = "";
            $bloqueado_empresa_email2 = "";
            $bloqueado_empresa_cargo = "";
            $bloqueado_empresa_aprobacion = "0";
            $bloqueado_empresa_paisid = "0";
            $bloqueado_empresa_pais = "";
            $bloqueado_empresa_socio = "";
            $bloqueado_empresa_proceso = "";
            $bloqueado_empresa_pd = "";
            $bloqueado_empresa_sitio_cor = "";
            $bloqueado_empresa_sitio = "";
            $bloqueado_empresa_rm = "";
            $bloqueado_empresa_circuito = "0";

            $sql = "SELECT usuario.acceso, usuario.acceso_graphs, usuario.excluido_reportes, usuario_empresa.* ";
            $sql .= " FROM usuario ";
            $sql .= " LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = usuario.id ";
            $sql .= " WHERE usuario.id = '" . $idUsuarioActual . "'";
            $PSN->query($sql);
            if ($PSN->next_record()) {
                $bloqueado_general_acceso = $PSN->f("acceso");
                $bloqueado_general_acceso_graphs = $PSN->f("acceso_graphs");
                $bloqueado_general_excluido_reportes = $PSN->f("excluido_reportes");
                $bloqueado_empresa_tipo = $PSN->f("empresa_tipo");
                $bloqueado_empresa_representante = $PSN->f("empresa_representante");
                $bloqueado_empresa_contacto = $PSN->f("empresa_contacto");
                $bloqueado_empresa_direccion = $PSN->f("empresa_direccion");
                $bloqueado_empresa_url = $PSN->f("empresa_url");
                $bloqueado_empresa_telefono1 = $PSN->f("empresa_telefono1");
                $bloqueado_empresa_telefono2 = $PSN->f("empresa_telefono2");
                $bloqueado_empresa_celular1 = $PSN->f("empresa_celular1");
                $bloqueado_empresa_celular2 = $PSN->f("empresa_celular2");
                $bloqueado_empresa_email1 = $PSN->f("empresa_email1");
                $bloqueado_empresa_email2 = $PSN->f("empresa_email2");
                $bloqueado_empresa_cargo = $PSN->f("empresa_cargo");
                $bloqueado_empresa_aprobacion = $PSN->f("empresa_aprobacion");
                $bloqueado_empresa_paisid = $PSN->f("empresa_paisid");
                $bloqueado_empresa_pais = $PSN->f("empresa_pais");
                $bloqueado_empresa_socio = $PSN->f("empresa_socio");
                $bloqueado_empresa_proceso = $PSN->f("empresa_proceso");
                $bloqueado_empresa_pd = $PSN->f("empresa_pd");
                $bloqueado_empresa_sitio_cor = $PSN->f("empresa_sitio_cor");
                $bloqueado_empresa_sitio = $PSN->f("empresa_sitio");
                $bloqueado_empresa_rm = $PSN->f("empresa_rm");
                $bloqueado_empresa_circuito = $PSN->f("empresa_circuito");
            }
        }
        /*
         *   PESTAÑA GENERAL
         */
        $general_nombre = eliminarInvalidos($_POST["nombre"]);
        $general_tipo = soloNumeros($_POST["tipo"]);
        $general_tipo_user_cli = soloNumeros($_POST["tipo_user_cli"]);
        if ($general_tipo_user_cli == "")
            $general_tipo_user_cli = "0";
        $general_identificacion = eliminarInvalidos($_POST["identificacion"]);
        $general_tipoIdentificacion = soloNumeros($_POST["tipoIdentificacion"]);
        $general_direccion = eliminarInvalidos($_POST["direccion"]);
        $general_departamento = soloNumeros($_POST["departamento"]);
        $general_municipio = soloNumeros($_POST["municipio"]);
        if ($general_municipio == "")
            $general_municipio = "NULL";
        if ($general_municipio == "")
            $general_municipio = "NULL";
        $general_lat = substr($_POST["lat"], 0, 10);
        $general_lon = substr($_POST["lon"], 0, 10);
        $general_aviso = $_POST["aviso"];
        $general_telefono1 = soloNumeros($_POST["telefono1"]);
        // TODO:ELIMINAR ESTE TELEFONO 2
        $general_telefono2 = soloNumeros($_POST["telefono2"]);
        $general_celular = soloNumeros($_POST["celular"]);
        //   TODO:ELIMINAR ESTE CELULAR 2
        $general_celular2 = soloNumeros($_POST["celular2"]);
        $general_email = eliminarInvalidos($_POST["email"]);
        $general_url = eliminarInvalidos($_POST["url"]);
        $general_url2 = eliminarInvalidos($_POST["url2"]);
        $general_observaciones = eliminarInvalidos($_POST["observaciones"]);
        $general_password = eliminarInvalidos($_POST["password"]);
        $general_acceso = eliminarInvalidos($_POST["acceso"]);
        $general_acceso_graphs = eliminarInvalidos($_POST["acceso_graphs"]);
        $general_excluido_reportes = isset($_POST["excluido_reportes"]) ? 1 : 0;
        if ($soloLecturaFacilitador) {
            $general_acceso = $bloqueado_general_acceso;
            $general_acceso_graphs = $bloqueado_general_acceso_graphs;
            $general_excluido_reportes = $bloqueado_general_excluido_reportes;
        }

        //
        $temp_password_check = eliminarInvalidos($_POST["password_check"]);
        /*
         *   ARCHIVO FOTO
         */
        $nombre_archivo = $_FILES['archivo']['name'];
        $tipo_archivo = $_FILES['archivo']['type'];
        $tamano_archivo = $_FILES['archivo']['size'];


        if ($general_nombre == "" || $general_identificacion == "") {
            $error_datos = 1; //Datos requeridos en blanco
        }

        if ($general_password != "" && $general_password != $temp_password_check) {
            $error_datos = 2;   //Password diferente
        }

        /*
         *   COMPROBAMOS QUE IDENTIFICACION NO EXISTA
         */
        $sql = "SELECT id ";
        $sql .= " FROM usuario";
        $sql .= " WHERE identificacion = '" . $general_identificacion . "' AND id != '" . $idUsuarioActual . "'";
        $PSN->query($sql);
        if ($PSN->next_record()) {
            $error_datos = 3;   //Identificacion ya existe
        }


        if ($error_datos == 0) {
            /*
             *	DEBEMOS INSERTAR LA INFORMACION DEL CLIENTE/USUARIO SEGUN CORRESPONDA.
             */
            $sql = 'UPDATE usuario SET 
                nombre = "' . $general_nombre . '",
                tipo = "' . $general_tipo . '",
                tipo_user_cli = ' . $general_tipo_user_cli . ',
                identificacion = "' . $general_identificacion . '",
                tipoIdentificacion =  "' . $general_tipoIdentificacion . '",
                direccion = "' . $general_direccion . '",
                usua_muni = ' . $general_municipio . ',
                lat = "' . $general_lat . '",
                lon = "' . $general_lon . '",
                aviso = "' . $general_aviso . '",
                telefono1 = "' . $general_telefono1 . '", 
                telefono2 = "' . $general_telefono2 . '", 
                celular = "' . $general_celular . '", 
                celular2 =  "' . $general_celular2 . '", 
                email = "' . $general_email . '", 
                url = "' . $general_url . '", 
                url2 = "' . $general_url2 . '", 
                observaciones = "' . $general_observaciones . '", 
                acceso = "' . $general_acceso . '", 
                acceso_graphs = "' . $general_acceso_graphs . '", 
                excluido_reportes = "' . $general_excluido_reportes . '", 
                modUsuario = "' . $_SESSION["id"] . '",
                modFecha = NOW()
            ';

            if ($general_password != "") {
                $sql .= ', password = "' . md5($general_password) . '"';
            }

            $sql .= ' WHERE id = "' . $idUsuarioActual . '"';
            $ultimoQuery = $PSN1->query($sql);

            /*
             *   INSERTAMOS INFORMACIÓN EMPRESARIAL
             */
            $empresa_nombre = "";
            $empresa_nit = "";
            $empresa_tipo = soloNumeros($_POST["empresa_tipo"]);
            if ($empresa_tipo == "")
                $empresa_tipo = "0";
            //$empresa_nombre = eliminarInvalidos($_POST["empresa_nombre"]);
            //$empresa_nit = eliminarInvalidos($_POST["empresa_nit"]);
            $empresa_representante = eliminarInvalidos($_POST["empresa_representante"]);
            $empresa_contacto = eliminarInvalidos($_POST["empresa_contacto"]);
            $empresa_direccion = eliminarInvalidos($_POST["empresa_direccion"]);
            $empresa_url = eliminarInvalidos($_POST["empresa_url"]);
            $empresa_telefono1 = eliminarInvalidos($_POST["empresa_telefono1"]);
            // TODO: DEJAR PENDIENTE PARA ELIMIAR ESTE TELEFONO 2
            $empresa_telefono2 = eliminarInvalidos($_POST["empresa_telefono2"]);
            $empresa_celular1 = eliminarInvalidos($_POST["empresa_celular1"]);
            //  TODO: DEJAR PENDIENTE PARA ELIMIAR ESTE CELULAR 2
            $empresa_celular2 = eliminarInvalidos($_POST["empresa_celular2"]);
            $empresa_email1 = eliminarInvalidos($_POST["empresa_email1"]);
            $empresa_email2 = eliminarInvalidos($_POST["empresa_email2"]);
            $empresa_cargo = eliminarInvalidos($_POST["empresa_cargo"]);
            $empresa_aprobacion = soloNumeros($_POST["empresa_aprobacion"]);
            if ($empresa_aprobacion == "")
                $empresa_aprobacion = "0";

            $empresa_paisid = soloNumeros($_POST["empresa_paisid"]);
            if ($empresa_paisid == "")
                $empresa_paisid = "0";
            $empresa_pais = eliminarInvalidos($_POST["empresa_pais"]);

            $empresa_socio = eliminarInvalidos($_POST["empresa_socio"]);
            $empresa_proceso = eliminarInvalidos($_POST["empresa_proceso"]);
            $empresa_pd = eliminarInvalidos($_POST["empresa_pd"]);
            $empresa_sitio_cor = eliminarInvalidos($_POST["empresa_sitio_cor"]);
            $empresa_sitio = eliminarInvalidos($_POST["empresa_sitio"]);
            $empresa_rm = eliminarInvalidos($_POST["empresa_rm"]);
            $empresa_circuito = soloNumeros($_POST["empresa_circuito"]);
            if ($empresa_circuito == "")
                $empresa_circuito = "0";

            if ($soloLecturaFacilitador) {
                $empresa_tipo = $bloqueado_empresa_tipo;
                $empresa_representante = $bloqueado_empresa_representante;
                $empresa_contacto = $bloqueado_empresa_contacto;
                $empresa_direccion = $bloqueado_empresa_direccion;
                $empresa_url = $bloqueado_empresa_url;
                $empresa_telefono1 = $bloqueado_empresa_telefono1;
                $empresa_telefono2 = $bloqueado_empresa_telefono2;
                $empresa_celular1 = $bloqueado_empresa_celular1;
                $empresa_celular2 = $bloqueado_empresa_celular2;
                $empresa_email1 = $bloqueado_empresa_email1;
                $empresa_email2 = $bloqueado_empresa_email2;
                $empresa_cargo = $bloqueado_empresa_cargo;
                $empresa_aprobacion = $bloqueado_empresa_aprobacion;
                $empresa_paisid = $bloqueado_empresa_paisid;
                $empresa_pais = $bloqueado_empresa_pais;
                $empresa_socio = $bloqueado_empresa_socio;
                $empresa_proceso = $bloqueado_empresa_proceso;
                $empresa_pd = $bloqueado_empresa_pd;
                $empresa_sitio_cor = $bloqueado_empresa_sitio_cor;
                $empresa_sitio = $bloqueado_empresa_sitio;
                $empresa_rm = $bloqueado_empresa_rm;
                $empresa_circuito = $bloqueado_empresa_circuito;
            }



            $sql = 'UPDATE usuario_empresa SET 
                empresa_tipo = ' . $empresa_tipo . ', 
                empresa_nombre =  "' . $empresa_nombre . '",
                empresa_nit = "' . $empresa_nit . '",
                empresa_representante = "' . $empresa_representante . '",
                empresa_contacto = "' . $empresa_contacto . '",
                empresa_direccion =  "' . $empresa_direccion . '",
                empresa_url =  "' . $empresa_url . '",
                empresa_telefono1 = "' . $empresa_telefono1 . '",
                empresa_telefono2 =  "' . $empresa_telefono2 . '",
                empresa_celular1 = "' . $empresa_celular1 . '",
                empresa_celular2 =  "' . $empresa_celular2 . '",
                empresa_email1 = "' . $empresa_email1 . '",
                empresa_email2 =  "' . $empresa_email2 . '",
                empresa_cargo =  "' . $empresa_cargo . '",
                empresa_aprobacion =  ' . $empresa_aprobacion . ',
                empresa_paisid = ' . $empresa_paisid . ',
                empresa_pais = "' . $empresa_pais . '",
                
                empresa_socio = "' . $empresa_socio . '",
                empresa_proceso = "' . $empresa_proceso . '",
                empresa_pd = "' . $empresa_pd . '",
                empresa_sitio_cor = "' . $empresa_sitio_cor . '",
                empresa_sitio = "' . $empresa_sitio . '",
                empresa_rm = "' . $empresa_rm . '",
                empresa_circuito = ' . $empresa_circuito . '
            ';
            //
            $sql .= ' WHERE idUsuario = "' . $idUsuarioActual . '"';
            $ultimoQuery = $PSN1->query($sql);

            /*
             *   CARGUE DE PESTAÑA CLIENTE
             */
            if ($ctrl == 2) {
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
                    cliente_tipo1 = "' . $cliente_tipo1 . '",
                    cliente_servicio1 = "' . $cliente_servicio1 . '",
                    cliente_observaciones =  "' . $cliente_observaciones . '",
                    cliente_valor1 =  "' . $cliente_valor1 . '",
                    cliente_diaPago = "' . $cliente_diaPago . '",
                    cliente_fechaAprob = "' . $cliente_fechaAprob . '",
                    cliente_fechaAprobCont = "' . $cliente_fechaAprobCont . '",
                    cliente_fechaInicial = "' . $cliente_fechaInicial . '",
                    cliente_fechaFinal = "' . $cliente_fechaFinal . '",
                    cliente_tipoPersona = "' . $cliente_tipoPersona . '"
                ';
                //
                $sql .= ' WHERE idUsuario = "' . $idUsuarioActual . '"';
                $ultimoQuery = $PSN1->query($sql);
            }

            /*
             *   CARGUE DE PESTAÑA PROVEEDOR
             */
            if ($ctrl == 3) {
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
                    servicios_tipo1 = "' . $servicios_tipo1 . '",
                    servicios_tipo2 = "' . $servicios_tipo2 . '",
                    servicios_contrato1 = "' . $servicios_contrato1 . '",
                    servicios_contrato2 = "' . $servicios_contrato2 . '",
                    servicios_observaciones =  "' . $servicios_observaciones . '",
                    servicios_fechaInicio = "' . $servicios_fechaInicio . '",
                    servicios_fechaFin = "' . $servicios_fechaFin . '",
                    servicios_tipoPersona = "' . $servicios_tipoPersona . '",
                    servicios_porcentaje = "' . $servicios_porcentaje . '"
                    
                ';
                $sql .= ' WHERE idUsuario = "' . $idUsuarioActual . '"';
                $ultimoQuery = $PSN1->query($sql);
            }

            /*
             *   INSERTAMOS ACCESOS AL SISTEMA.
             */
            if (!$soloLecturaFacilitador) {
                $sql = "DELETE FROM usuarios_menu WHERE idUsuario = " . $idUsuarioActual;
                $PSN1->query($sql);
                if (isset($_POST["menu"]) && is_array($_POST["menu"])) {
                    foreach ($_POST["menu"] as $menuopc) {                //
                        $sql = "REPLACE INTO usuarios_menu (idUsuario, idMenu) VALUES (" . $idUsuarioActual . ", " . soloNumeros($menuopc) . ")";
                        $PSN1->query($sql);
                    }
                }
            }

            /*
             *   INSERTAMOS ACCESOS A GRAFICAS AL SISTEMA.
             */
            if (!$soloLecturaFacilitador) {
                $sql = "DELETE FROM usuarios_menu_graphs WHERE idUsuario = " . $idUsuarioActual;
                $PSN1->query($sql);
                if (isset($_POST["menu_graphs"]) && is_array($_POST["menu_graphs"])) {
                    foreach ($_POST["menu_graphs"] as $menuopc) {                //
                        $sql = "REPLACE INTO usuarios_menu_graphs (idUsuario, idMenu) VALUES (" . $idUsuarioActual . ", " . soloNumeros($menuopc) . ")";
                        $PSN1->query($sql);
                    }
                }
            }

            //Compruebo si las características del archivo son las que deseo
            if (move_uploaded_file($_FILES['archivo']['tmp_name'], "images/usuarios/" . $idUsuarioActual . ".jpg")) {
            }

            //
            //  Documento de IDENTIFICACIÓN
            //
            $nombre_archivo = $_FILES['documento_identificacion']['name'];
            $temp_location = $_FILES['documento_identificacion']['tmp_name'];
            $temp_ext = extension_archivo($nombre_archivo);
            $temp_nombreFile = "id" . $idUsuarioActual . "." . $temp_ext;

            /*echo "<br />Temp location: ".$temp_location;
            echo "<br />Temp temp_ext: ".$temp_ext;
            echo "<br />Temp temp_nombreFile: ".$temp_nombreFile;*/

            if (move_uploaded_file($temp_location, "archivos/usuarios/" . $temp_nombreFile)) {
                $sql = "UPDATE usuario_documentos SET 
                    documento_identificacion = '" . $temp_nombreFile . "'
                    WHERE idUsuario = '" . $idUsuarioActual . "'";
                $PSN1->query($sql);
            }

            //
            //  Documento de RUT
            //
            $nombre_archivo = $_FILES['documento_rut']['name'];
            $temp_location = $_FILES['documento_rut']['tmp_name'];
            $temp_ext = extension_archivo($nombre_archivo);
            $temp_nombreFile = "rut" . $idUsuarioActual . "." . $temp_ext;

            if (move_uploaded_file($temp_location, "archivos/usuarios/" . $temp_nombreFile)) {
                $sql = "UPDATE usuario_documentos SET 
                    documento_rut = '" . $temp_nombreFile . "'
                    WHERE idUsuario = '" . $idUsuarioActual . "'";
                $PSN1->query($sql);
            }

            //
            //  Documento de CONSTITUCION
            //
            $nombre_archivo = $_FILES['documento_constitucion']['name'];
            $temp_location = $_FILES['documento_constitucion']['tmp_name'];
            $temp_ext = extension_archivo($nombre_archivo);
            $temp_nombreFile = "cons" . $idUsuarioActual . "." . $temp_ext;

            if (move_uploaded_file($temp_location, "archivos/usuarios/" . $temp_nombreFile)) {
                $sql = "UPDATE usuario_documentos SET 
                    documento_constitucion = '" . $temp_nombreFile . "'
                    WHERE idUsuario = '" . $idUsuarioActual . "'";
                $PSN1->query($sql);
            }

            //
            //  Documento de CONTRATO
            //
            $nombre_archivo = $_FILES['documento_contrato']['name'];
            $temp_location = $_FILES['documento_contrato']['tmp_name'];
            $temp_ext = extension_archivo($nombre_archivo);
            $temp_nombreFile = "contrato" . $idUsuarioActual . "." . $temp_ext;

            if (move_uploaded_file($temp_location, "archivos/usuarios/" . $temp_nombreFile)) {
                $sql = "UPDATE usuario_documentos SET 
                    documento_contrato = '" . $temp_nombreFile . "'
                    WHERE idUsuario = '" . $idUsuarioActual . "'";
                $PSN1->query($sql);
            }

            //
            //  Documento ADICIONAL
            //
            $nombre_archivo = $_FILES['documento_adicional_file']['name'];
            $temp_location = $_FILES['documento_adicional_file']['tmp_name'];
            $temp_ext = extension_archivo($nombre_archivo);
            $temp_nombreFile = strtotime("now") . "." . $temp_ext;

            if (move_uploaded_file($temp_location, "archivos/usuarios/" . $temp_nombreFile)) {
                $sql = "INSERT INTO usuario_documentos_add 
                        (
                        idUsuario, 
                        descripcion, 
                        archivo
                    ) 
                    VALUES 
                        (
                        " . $idUsuarioActual . ", 
                        '" . eliminarInvalidos($_REQUEST["documento_adicional_nom"]) . "', 
                        '" . $temp_nombreFile . "'
                )";
                $PSN1->query($sql);
                //
            }
            $varExitoUSU_UPD = 1;
        }
    }
}


switch ($error_datos) {
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


if (!isset($_REQUEST["id"])) {
    $temp_accionForm = "insertar";
    $idUsuarioActual = 0;
    /*
     *   Cargue de datos iniciales
     */
    $general_nombre = eliminarInvalidos($_POST["nombre"]);
    $general_tipo = soloNumeros($_POST["tipo"]);
    $general_tipo_user_cli = soloNumeros($_POST["tipo_user_cli"]);
    $general_identificacion = eliminarInvalidos($_POST["identificacion"]);
    $general_tipoIdentificacion = soloNumeros($_POST["tipoIdentificacion"]);
    $general_direccion = eliminarInvalidos($_POST["direccion"]);
    $general_departamento = soloNumeros($_POST["departamento"]);
    $general_aviso = $_POST["aviso"];
    $general_municipio = soloNumeros($_POST["municipio"]);
    if ($general_municipio == "")
        $general_municipio = "NULL";
    $general_telefono1 = soloNumeros($_POST["telefono1"]);
    // TODO:DEJAR PENDIENTE PARA ELIMIAR TELEFONO 2
    $general_telefono2 = soloNumeros($_POST["telefono2"]);
    $general_celular = soloNumeros($_POST["celular"]);
    // TODO:DEJAR PENDIENTE PARA ELIMIAR CELULAR 2
    $general_celular2 = soloNumeros($_POST["celular2"]);
    $general_email = eliminarInvalidos($_POST["email"]);
    $general_url = eliminarInvalidos($_POST["url"]);
    $general_url2 = eliminarInvalidos($_POST["url2"]);
    $general_observaciones = eliminarInvalidos($_POST["observaciones"]);
    $general_password = eliminarInvalidos($_POST["password"]);
    $general_acceso = eliminarInvalidos($_POST["acceso"]);
    $general_acceso_graphs = eliminarInvalidos($_POST["acceso_graphs"]);
    $general_excluido_reportes = eliminarInvalidos($_POST["excluido_reportes"]);

    //
    //
    $empresa_tipo = soloNumeros($_POST["empresa_tipo"]);
    if ($empresa_tipo == "")
        $empresa_tipo = "0";
    //
    //
    $empresa_representante = eliminarInvalidos($_POST["empresa_representante"]);
    $empresa_contacto = eliminarInvalidos($_POST["empresa_contacto"]);
    $empresa_direccion = eliminarInvalidos($_POST["empresa_direccion"]);
    $empresa_url = eliminarInvalidos($_POST["empresa_url"]);
    $empresa_telefono1 = eliminarInvalidos($_POST["empresa_telefono1"]);
    // TODO:TAMBIEN QUITAR ESTA VALIDACION DE TELEFONO 2
    $empresa_telefono2 = eliminarInvalidos($_POST["empresa_telefono2"]);
    $empresa_celular1 = eliminarInvalidos($_POST["empresa_celular1"]);
    // TODO:TAMBIEN QUITAR ESTA VALIDACION DE CELULAR 2
    $empresa_celular2 = eliminarInvalidos($_POST["empresa_celular2"]);
    $empresa_email1 = eliminarInvalidos($_POST["empresa_email1"]);
    $empresa_email2 = eliminarInvalidos($_POST["empresa_email2"]);
    $empresa_cargo = eliminarInvalidos($_POST["empresa_cargo"]);
    $empresa_aprobacion = soloNumeros($_POST["empresa_aprobacion"]);
    if ($empresa_aprobacion == "")
        $empresa_aprobacion = "0";


    $empresa_paisid = soloNumeros($_POST["empresa_paisid"]);
    if ($empresa_paisid == "")
        $empresa_paisid = "0";
    $empresa_pais = eliminarInvalidos($_POST["empresa_pais"]);
    $empresa_socio = eliminarInvalidos($_POST["empresa_socio"]);
    $empresa_proceso = eliminarInvalidos($_POST["empresa_proceso"]);
    $empresa_pd = eliminarInvalidos($_POST["empresa_pd"]);
    $empresa_sitio_cor = eliminarInvalidos($_POST["empresa_sitio_cor"]);
    $empresa_sitio = eliminarInvalidos($_POST["empresa_sitio"]);
    $empresa_rm = eliminarInvalidos($_POST["empresa_rm"]);
    $empresa_circuito = soloNumeros($_POST["empresa_circuito"]);
    if ($empresa_circuito == "")
        $empresa_circuito = "0";



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
} else {
    $temp_accionForm = "actualizar";
    //  ID del usuario actual
    $idUsuarioActual = soloNumeros($_REQUEST["id"]);

    if (isset($_REQUEST["deldoc"]) && $_REQUEST["deldoc_name"] != "") {
        unlink("archivos/usuarios/" . $_REQUEST["deldoc_name"]);
        //
        if ($_REQUEST["deldoc"] == "contrato") {
            $sql = "UPDATE usuario_documentos SET 
                documento_contrato = ''
                WHERE idUsuario = '" . $idUsuarioActual . "'";
            $PSN1->query($sql);
        } else if ($_REQUEST["deldoc"] == "constitucion") {
            $sql = "UPDATE usuario_documentos SET 
                documento_constitucion = ''
                WHERE idUsuario = '" . $idUsuarioActual . "'";
            $PSN1->query($sql);
        } else if ($_REQUEST["deldoc"] == "rut") {
            $sql = "UPDATE usuario_documentos SET 
                documento_rut = ''
                WHERE idUsuario = '" . $idUsuarioActual . "'";
            $PSN1->query($sql);
        } else if ($_REQUEST["deldoc"] == "identificacion") {
            $sql = "UPDATE usuario_documentos SET 
                documento_identificacion = ''
                WHERE idUsuario = '" . $idUsuarioActual . "'";
            $PSN1->query($sql);
        } else if (soloNumeros($_REQUEST["deldoc"]) != "" && soloNumeros($_REQUEST["deldoc"]) != "0") {
            $sql = "DELETE FROM usuario_documentos_add 
                    WHERE id = '" . soloNumeros($_REQUEST["deldoc"]) . "' 
                    AND idUsuario = '" . $idUsuarioActual . "'";
            $PSN1->query($sql);
        }
    }


    // TODO:HICIMOS CAMBIOS EN LA CONSULTA 

    // Inicializar variables por defecto
    $general_excluido_reportes = 0;

    /*
     *	TRAEMOS LOS DATOS PRINCIPALES DEL USUARIO
     */
    $sql = "SELECT usuario.*, cliente.id as idCliente, cliente.nombre as nomcliente ,dane_municipios.*,dane_departamentos.* FROM usuario";
    $sql .= " LEFT JOIN usuario_relacion ON usuario_relacion.idUsuario1 = usuario.id ";
    $sql .= "LEFT JOIN dane_municipios ON dane_municipios.id_municipio = usuario.usua_muni LEFT JOIN dane_departamentos ON dane_departamentos.id_departamento = dane_municipios.departamento_id";
    $sql .= " LEFT JOIN usuario as cliente ON cliente.id = usuario_relacion.idUsuario2 AND cliente.tipo = 3";
    $sql .= " WHERE usuario.id = '" . $idUsuarioActual . "'";
    $sql .= " GROUP BY usuario.id";
    $PSN1->query($sql);
    if ($PSN1->num_rows() > 0) {
        if ($PSN1->next_record()) {
            $general_nombre = $PSN1->f("nombre");
            $general_tipo = $PSN1->f("tipo");
            if ($general_tipo == 3) {
                $ctrl = 2;
            } else if ($general_tipo == 4) {
                $ctrl = 3;
            } else if ($general_tipo == 160) {
                $ctrl = 4;
                $idCliente = $PSN1->f("idCliente");
            }

            $general_tipo_user_cli = $PSN1->f("tipo_user_cli");
            //
            $general_identificacion = $PSN1->f("identificacion");
            $general_tipoIdentificacion = $PSN1->f("tipoIdentificacion");
            $general_aviso = $PSN1->f("aviso");
            $general_direccion = $PSN1->f("direccion");
            // TODO:ESTE DEBES ELIMINARLO TANTO ACA  COMO EN LA BASE DE DATOS
            // $general_departamento = $PSN1->f("departamento");
            $general_departamento = $PSN1->f("id_departamento");
            $general_municipio = $PSN1->f("id_municipio");
            // TODO:ESTE DEBES ELIMINARLO TANTO ACA  COMO EN LA BASE DE DATOS
            // $general_municipio = $PSN1->f("municipio");
            $general_telefono1 = $PSN1->f("telefono1");
            #TODO:QUITAR ESTE TELEFONO 2
            $general_telefono2 = $PSN1->f("telefono2");
            $general_celular = $PSN1->f("celular");
            #TODO:QUITAR ESTE CELULAR 2
            $general_celular2 = $PSN1->f("celular2");
            $general_email = $PSN1->f("email");
            $general_url = $PSN1->f("url");
            $general_url2 = $PSN1->f("url2");
            $general_observaciones = $PSN1->f("observaciones");
            $general_password = $PSN1->f("password");
            $general_acceso = $PSN1->f("acceso");
            $general_acceso_graphs = $PSN1->f("acceso_graphs");
            $general_excluido_reportes = $PSN1->f("excluido_reportes");

            /*
             *	TRAEMOS LOS DATOS EMPRESARIALES
             */
            $sql = "SELECT * ";
            $sql .= " FROM usuario_empresa ";
            $sql .= " WHERE idUsuario = '" . $idUsuarioActual . "'";
            $PSN1->query($sql);
            if ($PSN1->num_rows() > 0) {
                if ($PSN1->next_record()) {
                    $empresa_tipo = $PSN1->f("empresa_tipo");
                    $empresa_nombre = $PSN1->f("empresa_nombre");
                    $empresa_nit = $PSN1->f("empresa_nit");
                    $empresa_representante = $PSN1->f("empresa_representante");
                    $empresa_contacto = $PSN1->f("empresa_contacto");
                    $empresa_direccion = $PSN1->f("empresa_direccion");
                    $empresa_url = $PSN1->f("empresa_url");
                    $empresa_telefono1 = $PSN1->f("empresa_telefono1");
                    #TODO:QUITAR ESTE TELEFONO 2
                    $empresa_telefono2 = $PSN1->f("empresa_telefono2");
                    $empresa_celular1 = $PSN1->f("empresa_celular1");
                    #TODO:QUITAR ESTE CELULAR 2
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
            $sql .= " FROM usuario_servicios ";
            $sql .= " WHERE idUsuario = '" . $idUsuarioActual . "'";
            $PSN1->query($sql);
            if ($PSN1->num_rows() > 0) {
                if ($PSN1->next_record()) {
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
            $sql .= " FROM usuario_cliente ";
            $sql .= " WHERE idUsuario = '" . $idUsuarioActual . "'";
            $PSN1->query($sql);
            if ($PSN1->num_rows() > 0) {
                if ($PSN1->next_record()) {
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
            $sql .= " FROM usuario_documentos ";
            $sql .= " WHERE idUsuario = '" . $idUsuarioActual . "'";
            $PSN1->query($sql);
            if ($PSN1->num_rows() > 0) {
                if ($PSN1->next_record()) {
                    $documento_identificacion = $PSN1->f("documento_identificacion");
                    $documento_rut = $PSN1->f("documento_rut");
                    $documento_constitucion = $PSN1->f("documento_constitucion");
                    $documento_contrato = $PSN1->f("documento_contrato");
                }
            }
        } //chequear el registro
    } //chequear el numero
}



/*
 *   VALIDACIONES DE USUARIO AUTORIZADO DEL CLIENTE
 */
if ($ctrl == 4) {
    $error_cliente = 0; //
    if (
        !isset($idCliente) && (!isset($_REQUEST["idCliente"]) ||
            soloNumeros($_REQUEST["idCliente"]) == "" ||
            soloNumeros($_REQUEST["idCliente"]) == "0")
    ) {
        $error_cliente = 1; //  Cliente vacio
    } else {
        //  ID del cliente.
        if (!isset($idCliente) && $idCliente == 0 && $idCliente == "") {
            $idCliente = soloNumeros($_REQUEST["idCliente"]);
        }
        $error_cliente = 2; //  Cliente NO existente
        /*
         *	TRAEMOS EL CLIENTE ASOCIADO
         */
        $sql = "SELECT id, nombre ";
        $sql .= " FROM usuario ";
        $sql .= " WHERE id = '" . $idCliente . "' AND tipo = 3";
        $PSN1->query($sql);
        $numero = $PSN1->num_rows();
        if ($numero > 0) {
            if ($PSN1->next_record()) {
                $temp_nombreCliente = $PSN1->f("nombre");
                $temp_letrero .= "<br />" . $temp_nombreCliente;
                $error_cliente = 0;
            }
        }
    }
    //
    switch ($error_cliente) {
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

switch ($ctrl) {
    case 1:
        $temp_tiposUsuario = "2, 163, 162, 164";
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
        $temp_letrero = "AUTORIZADO DEL CLIENTE:<br />" . $temp_nombreCliente;
        break;
    default:
        $temp_letrero = "SIN DEFINIR";
        break;
}

/*
 *   SI SE INSERTO REGISTRO SE REDIRIGE
 */
if ($varExitoUSU == 1) {
    ?>
    <div class="container">
        <div class="row">
            <h2 class="alert alert-info text-center"><?php
            if ($idUsuarioActual == 0) {
                echo "CREACION";
            } else {
                echo "ACTUALIZACIÓN";
            }
            ?> DE <?= $temp_letrero; ?></h2>
        </div>

        <div class="row">
            <h5 class="alert alert-warning text-center">Se ha <?php
            if ($idUsuarioActual == 0) {
                echo "creado";
            } else {
                echo "actualizado";
            }
            ?> correctamente el registro, en breve será redirigido,
                si no es redirigido de <a href="index.php?doc=<?= $webArchivo; ?>&opc=2&id=<?= $ultimoId; ?>">clic aquí</a>.
            </h5>
        </div>
    </div>
    <SCRIPT LANGUAGE="JavaScript">
        alert("Se ha creado correctamente el registro.");
        window.location.href = "index.php?doc=<?= $webArchivo; ?>&opc=2&id=<?= $ultimoId; ?>";
    </script>
    <?php
} else {
    ?>
    <div class="container">

        <div class="row">
            <h2 class="alert alert-info text-center">.<?php
            if ($idUsuarioActual == 0) {
                echo "CREACION";
            } else {
                echo "ACTUALIZACIÓN";
            }
            ?> DE <?= $temp_letrero; ?>.</h2>
        </div>

        <?php
        if ($varExitoUSU_UPD == 1) {
            ?>
            <div class="row">
                <h5 class="alert alert-warning text-center">Se ha actualizado correctamente el registro.</h5>
            </div><?php
        }
        //
        if ($texto_error != "") {
            ?>
            <div class="row">
                <h5 class="alert alert-danger text-center"><?= $texto_error; ?></h5>
            </div><?php
        }

        //
        if ($errorLogueo == 1) {
            ?>
            <div class="row">
                <h1>
                    <font color="red"><u>ATENCION:</u> NO SE CREO EL ACCESO<BR /><u>MOTIVO:</u> YA EXISTE UN ACCESO CON ESE
                        MISMO "LOGIN".<br />POR FAVOR CAMBIE EL "LOGIN".</font>
                </h1>
            </div><?php
        }
        ?>


        <?php
        if ($error_fatal == 1) {
            //No hacer nada.
        } else {
            ?>
            <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form-horizontal">
                <input type="hidden" name="ctrl" id="ctrl" value="<?= $ctrl; ?>" />

                <ul class="nav nav-tabs">
                    <li class="active"><a data-toggle="tab" href="#general">General</a></li>
                    <li><a data-toggle="tab" href="#empresa">Ministerial</a></li>
                    <?php
                    //  USUARIO PROVEEDOR
                    if ($ctrl == 3) {
                        ?>
                        <li><a data-toggle="tab" href="#servicios">Proveedor</a></li><?php
                    }
                    //  USUARIO cliente
                    if ($ctrl == 2) {
                        ?>
                        <li><a data-toggle="tab" href="#cliente">Cliente</a></li>
                        <?php
                    }
                    //  USUARIO cliente o proveedor
                    if ($ctrl == 1 || $ctrl == 2 || $ctrl == 3) {
                        ?>
                        <li><a data-toggle="tab" href="#archivos">Documentos</a></li>
                        <?php
                    }

                    //  OBSERVACIONES para todos los clientes
                    if ($idUsuarioActual != 0) {
                        ?>
                        <li><a data-toggle="tab" href="#tab_observaciones">Coach</a></li>
                        <?php
                        ?>
                        <li><a data-toggle="tab" href="#tab_metas">Metas</a></li>
                        <?php
                    }
                    ?>
                    <li><a data-toggle="tab" href="#accesos">Acceso al sistema</a></li>
                    <?php
                    //
                    //  USUARIO interno y CLIENTE y Usuario CLIENTE
                    //
                    if ($ctrl == 1 || $ctrl == 2 || $ctrl == 4) {
                        ?>
                        <li><a data-toggle="tab" href="#graficas">Acceso a Graficas</a></li><?php
                    }
                    ?>
                </ul>


                <div class="row">
                    <div class="tab-content">

                        <div id="general" class="tab-pane fade in active">

                            <div class="row">
                                <h3 class="text-center well">.INFORMACIÓN GENERAL.</h3>
                            </div>
                            <?php
                            if ($ctrl == 4) {
                                ?>
                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="idCliente"><strong>Autorizados del
                                            cliente:</strong></label>
                                    <div class="col-sm-4"><?php
                                    ?><select name="idCliente" class="form-control"><?php
                                    /*
                                     *	TRAEMOS LOS CLIENTES
                                     */
                                    $sql = "SELECT usuario.id, usuario.nombre ";
                                    $sql .= " FROM usuario ";
                                    $sql .= " WHERE tipo = 3 AND id = '" . $idCliente . "'";
                                    $sql .= " ORDER BY nombre asc";
                                    //
                                    $PSN1->query($sql);
                                    $numero = $PSN1->num_rows();
                                    if ($numero > 0) {
                                        while ($PSN1->next_record()) {
                                            ?>
                                                    <option value="<?= $PSN1->f('id'); ?>"><?= $PSN1->f('nombre'); ?></option>
                                                    <?php
                                        }
                                    }
                                    ?>
                                        </select>
                                    </div>

                                    <label class="control-label col-sm-2" for="tipo_user_cli"><strong>Tipo de
                                            autorizado:</strong></label>
                                    <div class="col-sm-4"><?php
                                    ?><select name="tipo_user_cli" class="form-control">
                                            <option value="">Sin especificar</option>
                                            <?php
                                            /*
                                             *	TRAEMOS LOS TIPOS DE USUARIO (1)
                                             */
                                            $sql = "SELECT * ";
                                            $sql .= " FROM categorias ";
                                            $sql .= " WHERE idSec = 32 ORDER BY descripcion asc";
                                            $PSN1->query($sql);
                                            $numero = $PSN1->num_rows();
                                            if ($numero > 0) {
                                                while ($PSN1->next_record()) {
                                                    ?>
                                                    <option value="<?= $PSN1->f('id'); ?>" <?php
                                                      if ($general_tipo_user_cli == $PSN1->f('id')) {
                                                          ?>selected="selected" <?php
                                                      }
                                                      ?>><?= $PSN1->f('descripcion'); ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>

                                </div><?php
                            }
                            ?>
                            <div
                                style="display: flex;flex-direction: column;align-items: center;justify-content: center; padding-top:1em; padding-bottom:1em;">
                                <input style="font-weight:900; font-size:1.3em; width: 60%;border:none; text-align:center;"
                                    readonly type="<?php echo ($general_aviso !== "") ? 'text' : 'hidden'; ?>"
                                    class="<?php echo ($general_aviso !== "¡Ubicación exacta encontrada!") ? 'text-danger' : 'text-success'; ?>"
                                    name="aviso" id="aviso" maxlength="250" value="<?= $general_aviso; ?>" />
                            </div>

                            <div class="form-group">
                                <div class="col-sm-1"></div>
                                <div class="col-sm-3">
                                    <strong>Nombre:</strong><input name="nombre" required type="text" id="nombre"
                                        maxlength="250" value="<?= $general_nombre; ?>" class="form-control" required
                                        autofocus />
                                </div>
                                <div class="col-sm-3">
                                    <strong>Tipo de usuario:</strong><select name="tipo" required class="form-control">
                                        <?php
                                        /*
                                         *	TRAEMOS LOS TIPOS DE USUARIO (1)
                                         */
                                        $sql = "SELECT * ";
                                        $sql .= " FROM categorias ";
                                        $sql .= " WHERE idSec = 1 AND id != 1 AND id IN (" . $temp_tiposUsuario . ") ORDER BY descripcion asc";

                                        $PSN1->query($sql);
                                        $numero = $PSN1->num_rows();
                                        if ($numero > 0) {
                                            while ($PSN1->next_record()) {
                                                ?>
                                                <option value="<?= $PSN1->f('id'); ?>" <?php
                                                  if ($general_tipo == $PSN1->f('id')) {
                                                      ?>selected="selected" <?php
                                                  }
                                                  ?>><?= $PSN1->f('descripcion'); ?></option>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <strong>Tipo de identificación:</strong><select required name="tipoIdentificacion"
                                        class="form-control">
                                        <option value="">Sin especificar</option>
                                        <?php
                                        /*
                                         *   TRAEMOS LOS TIPOS DE IDENTIFICACION (2)
                                         */
                                        $sql = "SELECT * ";
                                        $sql .= " FROM categorias ";
                                        $sql .= " WHERE idSec = 2 ORDER BY descripcion asc";


                                        $PSN1->query($sql);
                                        $numero = $PSN1->num_rows();
                                        if ($numero > 0) {
                                            while ($PSN1->next_record()) {
                                                ?>
                                                <option value="<?= $PSN1->f('id'); ?>" <?php
                                                  if ($general_tipoIdentificacion == $PSN1->f('id')) {
                                                      ?>selected="selected" <?php
                                                  }
                                                  ?>>
                                                    <?= $PSN1->f('descripcion'); ?></option>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-sm-2"><strong>Identificación</strong><input required name="identificacion"
                                        type="text" id="identificacion" maxlength="250" value="<?= $general_identificacion; ?>"
                                        class="form-control" required autofocus /></div>
                            </div>
                            <div class="form-group">

                                <div class="col-sm-1"></div>
                                <div class="col-sm-2">
                                    <strong>País</strong>
                                    <select name="pais" required class="form-control">
                                        <option value="">Sin especificar</option>
                                        <option value="57" selected>Colombia</option>
                                        option
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <strong>Departamento</strong>
                                    <select required name="departamento" id="departamento" style="text-transform: capitalize;"
                                        class="form-control">
                                        <option value="">Sin especificar</option>
                                        <?php
                                        /*
                                         *   TRAEMOS LOS TIPOS DE IDENTIFICACION (2)
                                         */
                                        $sql = "SELECT id_departamento,lower(departamento) as departamento ";
                                        $sql .= " FROM dane_departamentos ";
                                        $sql .= " ORDER BY departamento asc";
                                        $PSN1->query($sql);
                                        $numero = $PSN1->num_rows();
                                        if ($numero > 0) {
                                            while ($PSN1->next_record()) {
                                                ?>
                                                <option style="text-transform: capitalize;" value="<?= $PSN1->f('id_departamento'); ?>"
                                                    <?php
                                                    if ($general_departamento == $PSN1->f('id_departamento')) {
                                                        ?>selected="selected" <?php
                                                    }
                                                    ?>><?= $PSN1->f('departamento'); ?></option>
                                                <?php
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
                                    <strong>Dirección</strong><input required name="direccion" type="text" id="direccion"
                                        value="<?= $general_direccion; ?>" class="form-control" />
                                </div>

                                <!-- TODO:TELEFONO 2 Y TELEFONO 1 DE EMPRESA -->
                                <!-- <div class="col-sm-2">
                <strong>Teléfono 2</strong><input name="telefono2" type="tel" id="telefono2" maxlength="250" value="<?= $general_telefono2; ?>" class="form-control" />
            </div> -->
                                <!-- <div class="col-sm-2">
                <strong>Teléfono 3:</strong><input name="empresa_telefono1" type="text" id="empresa_telefono1" maxlength="255" value="<?= $empresa_telefono1; ?>" class="form-control" />
            </div> -->

                            </div>
                            <div class="form-group">
                                <div class="col-sm-1"></div>
                                <!-- <div class="col-sm-2">
                <strong>Teléfono 4:</strong><input name="empresa_telefono2" type="text" id="empresa_telefono2" maxlength="255" value="<?= $empresa_telefono2; ?>" class="form-control" />
            </div> -->
                                <div class="col-sm-2">
                                    <strong>Teléfono Personal</strong><input name="telefono1" type="tel" id="telefono1"
                                        maxlength="250" value="<?= $general_telefono1; ?>" class="form-control" />
                                </div>
                                <div class="col-sm-2">
                                    <strong>Celular Personal</strong><input name="celular" type="tel" id="celular"
                                        maxlength="250" value="<?= $general_celular; ?>" class="form-control" />
                                </div>
                                <div class="col-sm-2">
                                    <strong>Teléfono Empresa</strong><input name="telefono2" type="tel" id="telefono2"
                                        maxlength="250" value="<?= $general_telefono2; ?>" class="form-control" />
                                </div>
                                <div class="col-sm-2">
                                    <strong>Celular Empresa</strong><input name="empresa_celular1" type="text"
                                        id="empresa_celular1" maxlength="255" value="<?= $empresa_celular1; ?>"
                                        class="form-control" />
                                </div>
                                <!-- TODO:CELULAR 2 Y CELULAR 3 Y 3 DE EMPRESA -->
                                <!-- <div class="col-sm-2">
                <strong>Celular 2</strong><input name="celular2" type="tel" id="celular2" maxlength="250" value="<?= $general_celular; ?>"  class="form-control"  />
            </div>

            <div class="col-sm-2">
                <strong>Celular 4:</strong><input name="empresa_celular2" type="text" id="empresa_celular2" maxlength="255" value="<?= $empresa_celular2; ?>" class="form-control" />
            </div> -->
                            </div>

                            <div class="form-group">
                                <div class="col-sm-1"></div>
                                <div class="col-sm-4">
                                    <strong>Email 1</strong><input name="email" type="email" id="email" maxlength="250"
                                        value="<?= $general_email; ?>" class="form-control" />
                                </div>
                                <div class="col-sm-3">
                                    <strong>Email 2:</strong><input name="empresa_email1" type="text" id="empresa_email1"
                                        maxlength="255" value="<?= $empresa_email1; ?>" class="form-control" />
                                </div>
                                <!-- TODO: email 2 de empresa -->
                                <!-- <div class="col-sm-3">
                <strong>Email 3:</strong><input name="empresa_email2" type="text" id="empresa_email2" maxlength="255" value="<?= $empresa_email2; ?>" class="form-control" />
            </div> -->
                            </div>

                            <div class="form-group">
                                <div class="col-sm-1"></div>
                                <div class="col-sm-2">
                                    <strong>Codígo vídeo de YouTube</strong><input name="url" type="text" id="url"
                                        maxlength="250" value="<?= $general_url; ?>" class="form-control" />
                                </div>
                                <div class="col-sm-4">
                                    <strong>Enlace de Website</strong><input name="url2" type="text" id="url2"
                                        value="<?= $general_url2; ?>" class="form-control" />
                                </div>
                                <div class="col-sm-3">
                                    <strong>Foto (200*200 pixeles - .jpg)</strong><input name="archivo" type="file" id="archivo"
                                        class="form-control" />
                                </div>
                                <div class="col-sm-2"><?php
                                if (file_exists("images/usuarios/" . $idUsuarioActual . ".jpg")) {
                                    ?><img src="images/usuarios/<?= $idUsuarioActual; ?>.jpg"
                                            align="middle" width="80"
                                            height="80"><?php
                                } else {
                                    ?><img
                                            src="images/consultores/desconocido.jpg" align="middle" width="80"
                                            height="80"><?php
                                }
                                ?>
                                </div>
                            </div>



                            <div class="form-group">
                                <div class="col-sm-1"></div>
                                <div class="col-sm-10">
                                    <strong>Observaciones</strong><textarea name="observaciones" id="observaciones"
                                        class="form-control"><?= $general_observaciones; ?></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2" for="email">&nbsp;</label>
                                <div class="col-sm-4">&nbsp;</div>
                            </div>
                        </div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "GENERAL" //-->

                        <div id="empresa" class="tab-pane fade">
                            <?php if ($soloLecturaFacilitador) { ?>
                                <div class="alert alert-info">Solo tiene permiso de visualizacion en la seccion Ministerial.</div>
                                <fieldset disabled="disabled">
                            <?php } ?>

                            <div class="row">
                                <h3 class="text-center well">.INFORMACIÓN MINISTERIAL.</h3>
                            </div>

                            <?php
                            //  USUARIO INTERNO NO NECESITA TODOS LOS CAMPOS NI TAMPOCO EL AUTORIZADO
                            if ($ctrl != 1 && $ctrl != 4) {
                                ?>
                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="empresa_tipo"><strong>Tipo de
                                            ministerio:</strong></label>
                                    <div class="col-sm-10"><select name="empresa_tipo" class="form-control">
                                            <option value="">Sin especificar</option>
                                            <?php
                                            /*
                                             *	TRAEMOS LOS TIPOS DE CLIENTE/EMPRESA (15)
                                             */
                                            $sql = "SELECT * ";
                                            $sql .= " FROM categorias ";
                                            $sql .= " WHERE idSec = 15 ORDER BY descripcion asc";


                                            $PSN1->query($sql);
                                            $numero = $PSN1->num_rows();
                                            if ($numero > 0) {
                                                while ($PSN1->next_record()) {
                                                    ?>
                                                    <option value="<?= $PSN1->f('id'); ?>" <?php
                                                      if ($empresa_tipo == $PSN1->f('id')) {
                                                          ?>selected="selected" <?php
                                                      }
                                                      ?>><?= $PSN1->f('descripcion'); ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select></div>
                                </div>

                                <?php /*<div class="form-group">
                   <label class="control-label col-sm-2" for="empresa_nombre"><strong>Nombre empresa:</strong></label>
                   <div class="col-sm-4"><input name="empresa_nombre" type="text" id="empresa_nombre" maxlength="255" value="<?=$empresa_nombre; ?>" class="form-control" /></div>

                   <label class="control-label col-sm-2" for="empresa_nit"><strong>NIT:</strong></label>
                   <div class="col-sm-4"><input name="empresa_nit" type="text" id="empresa_nit" maxlength="50" value="<?=$empresa_nit; ?>" class="form-control" /></div>		
               </div>*/ ?>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="empresa_representante"><strong>Representante
                                            legal:</strong></label>
                                    <div class="col-sm-4"><input name="empresa_representante" type="text" id="empresa_representante"
                                            maxlength="255" value="<?= $empresa_representante; ?>" class="form-control" /></div>

                                    <label class="control-label col-sm-2" for="empresa_contacto"><strong>Nombre
                                            contacto:</strong></label>
                                    <div class="col-sm-4"><input name="empresa_contacto" type="text" id="empresa_contacto"
                                            maxlength="255" value="<?= $empresa_contacto; ?>" class="form-control" /></div>
                                </div>

                                <?php
                                //  Campos NO aplican para cliente ni para proveedor
                                if ($ctrl != 2 && $ctrl != 3 && $ctrl != 1 && $ctrl != 4) {
                                    ?>
                                    <div class="form-group">
                                        <label class="control-label col-sm-2"
                                            for="empresa_direccion"><strong>Dirección:</strong></label>
                                        <div class="col-sm-4"><input name="empresa_direccion" type="text" id="empresa_direccion"
                                                maxlength="255" value="<?= $empresa_direccion; ?>" class="form-control" /></div>

                                        <label class="control-label col-sm-2" for="empresa_url"><strong>Página Web:</strong></label>
                                        <div class="col-sm-4"><input name="empresa_url" type="text" id="empresa_url" maxlength="255"
                                                value="<?= $empresa_url; ?>" class="form-control" /></div>
                                    </div><?php
                                }
                            }


                            //  Campos NO aplican para cliente ni para proveedor
                            if ($ctrl != 2 && $ctrl != 3) {
                                //
                                ?>
                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="empresa_paisid"><strong>Nombre del pais
                                            (nuevo):</strong></label>
                                    <div class="col-sm-4"><select name="empresa_paisid" class="form-control">
                                            <option value="">Sin especificar</option>
                                            <?php
                                            /*
                                             *	TRAEMOS LOS TIPOS DE CLIENTE/EMPRESA (15)
                                             */
                                            $sql = "SELECT * ";
                                            $sql .= " FROM categorias ";
                                            $sql .= " WHERE idSec = 37 ORDER BY descripcion asc";


                                            $PSN1->query($sql);
                                            $numero = $PSN1->num_rows();
                                            if ($numero > 0) {
                                                while ($PSN1->next_record()) {
                                                    ?>
                                                    <option value="<?= $PSN1->f('id'); ?>" <?php
                                                      if ($empresa_paisid == $PSN1->f('id')) {
                                                          ?>selected="selected" <?php
                                                      }
                                                      ?>><?= $PSN1->f('descripcion'); ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select></div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="empresa_pais"><strong>Nombre del
                                            pais:</strong></label>
                                    <div class="col-sm-4"><input name="empresa_pais" type="text" id="empresa_pais" maxlength="255"
                                            value="<?= $empresa_pais; ?>" class="form-control" /></div>

                                    <label class="control-label col-sm-2" for="empresa_socio"><strong>Nombre del
                                            socio:</strong></label>
                                    <div class="col-sm-4"><input name="empresa_socio" type="text" id="empresa_socio" maxlength="255"
                                            value="<?= $empresa_socio; ?>" class="form-control" /></div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="empresa_proceso"><strong>Proceso:</strong></label>
                                    <div class="col-sm-4"><input name="empresa_proceso" type="text" id="empresa_proceso"
                                            maxlength="255" value="<?= $empresa_proceso; ?>" class="form-control" /></div>

                                    <!--<label class="control-label col-sm-2" for="empresa_pd"><strong>PD:</strong></label>
                <div class="col-sm-4"><input name="empresa_pd" type="text" id="empresa_pd" maxlength="255" value="<?= $empresa_pd; ?>" class="form-control" /></div>
            </div>
        
        
            <div class="form-group">
                <label class="control-label col-sm-2" for="empresa_circuito"><strong>Circuito:</strong></label>
                <div class="col-sm-4"><?php
                ?><select name="empresa_circuito" class="form-control">
                    <option value="">Sin especificar</option>
                    <?php
                    /*
                     *	TRAEMOS LOS TIPOS DE USUARIO (1)
                     */
                    $sql = "SELECT * ";
                    $sql .= " FROM categorias ";
                    $sql .= " WHERE idSec = 36 ORDER BY descripcion asc";
                    $PSN1->query($sql);
                    $numero = $PSN1->num_rows();
                    if ($numero > 0) {
                        while ($PSN1->next_record()) {
                            ?><option value="<?= $PSN1->f('id'); ?>" <?php
                              if ($empresa_circuito == $PSN1->f('id')) {
                                  ?>selected="selected"<?php
                              }
                              ?>><?= $PSN1->f('descripcion'); ?></option><?php
                        }
                    }
                    ?>
                    </select>
                </div>//-->
                                </div>



                                <div class="row">
                                    <h5 class="text-center well">.INFORMACIÓN COORDINADOR.</h5>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="empresa_sitio_cor"><strong>Sitio del
                                            coordinador:</strong></label>
                                    <div class="col-sm-4"><input name="empresa_sitio_cor" type="text" id="empresa_sitio_cor"
                                            maxlength="255" value="<?= $empresa_sitio_cor; ?>" class="form-control" /></div>

                                </div>

                                <div class="row">
                                    <h5 class="text-center well">.INFORMACIÓN FACILITADOR.</h5>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="empresa_sitio"><strong>Sitio del
                                            facilitador:</strong></label>
                                    <div class="col-sm-4"><input name="empresa_sitio" type="text" id="empresa_sitio" maxlength="255"
                                            value="<?= $empresa_sitio; ?>" class="form-control" /></div>

                                    <label class="control-label col-sm-2" for="empresa_rm"><strong>RM:</strong><br /><i>Seleccione
                                            'Si' o "No' para indicar si el Facilitador recibe fondos de la OMS/ECC</i></label>

                                    <label class="control-label col-sm-1" for="empresa_rm_no"><strong>NO</strong></label>
                                    <div class="col-sm-1"><input name="empresa_rm" type="radio" id="empresa_rm_no" value="NO" <?php if ($empresa_rm == "NO") { ?>checked<?php }
                                    ; ?> class="form-control" /></div>

                                    <label class="control-label col-sm-1" for="empresa_rm_si"><strong>SI</strong></label>
                                    <div class="col-sm-1"><input name="empresa_rm" type="radio" id="empresa_rm_si" value="SI" <?php if ($empresa_rm == "SI") { ?>checked<?php }
                                    ; ?> class="form-control" /></div>

                                </div>

                                <div class="row">
                                    <h5 class="text-center well">.GENERAL.</h5>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="empresa_cargo"><strong>Cargo:</strong></label>
                                    <div class="col-sm-10"><input name="empresa_cargo" type="text" id="empresa_cargo"
                                            maxlength="255" value="<?= $empresa_cargo; ?>" class="form-control" /></div>
                                </div>

                                <?php
                            }
                            /*
                             *   USUARIO AUTORIZADO - MONTO DE APROBACIÓN DE COTIZACIONES
                             */
                            if ($ctrl == 4 || $ctrl == 2) {
                                ?>
                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="empresa_aprobacion"><strong>Monto de
                                            aprobaci&oacute;n:</strong></label>
                                    <div class="col-sm-4"><input name="empresa_aprobacion" type="text" id="empresa_aprobacion"
                                            maxlength="255" value="<?= $empresa_aprobacion; ?>" class="form-control" /></div>
                                </div>
                                <?php
                            }


                            ?>
                        </div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "EMPRESA" //-->

                        <?php
                        //  USUARIO PROVEEDOR
                        if ($ctrl == 3) {
                            ?>
                            <div id="servicios" class="tab-pane fade">

                                <div class="row">
                                    <h3 class="text-center well">INFORMACIÓN DE PROVEEDOR</h3>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="servicios_tipoPersona"><strong>Tipo de
                                            persona:</strong></label>
                                    <div class="col-sm-10"><select name="servicios_tipoPersona" class="form-control">
                                            <option value="">Sin especificar</option>
                                            <?php
                                            /*
                                             *	TRAEMOS LOS TIPOS DE PERSONA JURIDICA O NATURAL
                                             */
                                            $sql = "SELECT * ";
                                            $sql .= " FROM categorias ";
                                            $sql .= " WHERE idSec = 29 ORDER BY descripcion asc";

                                            $PSN1->query($sql);
                                            $numero = $PSN1->num_rows();
                                            if ($numero > 0) {
                                                while ($PSN1->next_record()) {
                                                    ?>
                                                    <option value="<?= $PSN1->f('id'); ?>" <?php
                                                      if ($servicios_tipoPersona == $PSN1->f('id')) {
                                                          ?>selected="selected" <?php
                                                      }
                                                      ?>><?= $PSN1->f('descripcion'); ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select></div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="servicios_tipo1"><strong>Tipo de
                                            servicio:</strong></label>
                                    <div class="col-sm-4"><select name="servicios_tipo1" class="form-control">
                                            <option value="">Sin especificar</option>
                                            <?php
                                            /*
                                             *	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                                             */
                                            $sql = "SELECT * ";
                                            $sql .= " FROM categorias ";
                                            $sql .= " WHERE idSec = 25 ORDER BY descripcion asc";

                                            $PSN1->query($sql);
                                            $numero = $PSN1->num_rows();
                                            if ($numero > 0) {
                                                while ($PSN1->next_record()) {
                                                    ?>
                                                    <option value="<?= $PSN1->f('id'); ?>" <?php
                                                      if ($servicios_tipo1 == $PSN1->f('id')) {
                                                          ?>selected="selected" <?php
                                                      }
                                                      ?>><?= $PSN1->f('descripcion'); ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select></div>

                                    <label class="control-label col-sm-2" for="servicios_tipo2"><strong>Tipo de servicio
                                            2:</strong></label>
                                    <div class="col-sm-4"><select name="servicios_tipo2" class="form-control">
                                            <option value="">Sin especificar</option>
                                            <?php
                                            /*
                                             *	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                                             */

                                            $sql = "SELECT * ";
                                            $sql .= " FROM categorias ";
                                            $sql .= " WHERE idSec = 25 ORDER BY descripcion asc";

                                            $PSN1->query($sql);
                                            $numero = $PSN1->num_rows();
                                            if ($numero > 0) {
                                                while ($PSN1->next_record()) {
                                                    ?>
                                                    <option value="<?= $PSN1->f('id'); ?>" <?php
                                                      if ($servicios_tipo2 == $PSN1->f('id')) {
                                                          ?>selected="selected" <?php
                                                      }
                                                      ?>><?= $PSN1->f('descripcion'); ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select></div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="servicios_contrato1"><strong>Tipo de
                                            contrato:</strong></label>
                                    <div class="col-sm-4"><select name="servicios_contrato1" class="form-control">
                                            <option value="">Sin especificar</option>
                                            <?php
                                            /*
                                             *	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                                             */
                                            $sql = "SELECT * ";
                                            $sql .= " FROM categorias ";
                                            $sql .= " WHERE idSec = 26 ORDER BY descripcion asc";

                                            $PSN1->query($sql);
                                            $numero = $PSN1->num_rows();
                                            if ($numero > 0) {
                                                while ($PSN1->next_record()) {
                                                    ?>
                                                    <option value="<?= $PSN1->f('id'); ?>" <?php
                                                      if ($servicios_contrato1 == $PSN1->f('id')) {
                                                          ?>selected="selected" <?php
                                                      }
                                                      ?>><?= $PSN1->f('descripcion'); ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select></div>

                                    <label class="control-label col-sm-2" for="servicios_contrato2"><strong>Tipo de contrato
                                            2:</strong></label>
                                    <div class="col-sm-4"><select name="servicios_contrato2" class="form-control">
                                            <option value="">Sin especificar</option>
                                            <?php
                                            /*
                                             *	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                                             */
                                            $sql = "SELECT * ";
                                            $sql .= " FROM categorias ";
                                            $sql .= " WHERE idSec = 26 ORDER BY descripcion asc";

                                            $PSN1->query($sql);
                                            $numero = $PSN1->num_rows();
                                            if ($numero > 0) {
                                                while ($PSN1->next_record()) {
                                                    ?>
                                                    <option value="<?= $PSN1->f('id'); ?>" <?php
                                                      if ($servicios_contrato2 == $PSN1->f('id')) {
                                                          ?>selected="selected" <?php
                                                      }
                                                      ?>><?= $PSN1->f('descripcion'); ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select></div>
                                </div>


                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="servicios_observaciones"><strong>Ampliación de los
                                            servicios prestados:</strong></label>
                                    <div class="col-sm-10"><textarea name="servicios_observaciones" id="servicios_observaciones"
                                            class="form-control"><?= $servicios_observaciones; ?></textarea></div>
                                </div>


                                <div class="row">
                                    <h3 class="text-center well">FECHAS DE VIGENCIA</h3>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="servicios_fechaInicio"><strong>Fecha de
                                            inicio:</strong></label>
                                    <div class="col-sm-4"><input name="servicios_fechaInicio" type="date" placeholder="AAAA-MM-DD"
                                            id="servicios_fechaInicio" value="<?= $servicios_fechaInicio; ?>"
                                            class="form-control" /></div>

                                    <label class="control-label col-sm-2" for="servicios_fechaFin"><strong>Fecha
                                            final:</strong></label>
                                    <div class="col-sm-4"><input name="servicios_fechaFin" type="date" placeholder="AAAA-MM-DD"
                                            id="servicios_fechaFin" value="<?= $servicios_fechaFin; ?>" class="form-control" />
                                    </div>
                                </div>

                                <div class="row">
                                    <h3 class="text-center well">DESCUENTO</h3>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="servicios_porcentaje"><strong>Porcentaje de
                                            descuento:</strong></label>
                                    <div class="col-sm-4"><input name="servicios_porcentaje" type="number" id="servicios_porcentaje"
                                            value="<?= $servicios_porcentaje; ?>" class="form-control" /></div>
                                </div>


                            <?php if ($soloLecturaFacilitador) { ?>
                                </fieldset>
                            <?php } ?>
                            </div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "SERVICOS" //-->
                            <?php
                        }

                        //  USUARIO cliente
                        if ($ctrl == 2) {
                            ?>
                            <div id="cliente" class="tab-pane fade">

                                <div class="row">
                                    <h3 class="text-center well">INFORMACIÓN DE CLIENTE</h3>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="cliente_tipoPersona"><strong>Tipo de
                                            persona:</strong></label>
                                    <div class="col-sm-10"><select name="cliente_tipoPersona" class="form-control">
                                            <option value="">Sin especificar</option>
                                            <?php
                                            /*
                                             *	TRAEMOS LOS TIPOS DE PERSONA JURIDICA O NATURAL
                                             */
                                            $sql = "SELECT * ";
                                            $sql .= " FROM categorias ";
                                            $sql .= " WHERE idSec = 29 ORDER BY descripcion asc";

                                            $PSN1->query($sql);
                                            $numero = $PSN1->num_rows();
                                            if ($numero > 0) {
                                                while ($PSN1->next_record()) {
                                                    ?>
                                                    <option value="<?= $PSN1->f('id'); ?>" <?php
                                                      if ($cliente_tipoPersona == $PSN1->f('id')) {
                                                          ?>selected="selected" <?php
                                                      }
                                                      ?>><?= $PSN1->f('descripcion'); ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select></div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="cliente_tipo1"><strong>Tipo de
                                            servicio:</strong></label>
                                    <div class="col-sm-4"><select name="cliente_tipo1" class="form-control">
                                            <option value="">Sin especificar</option>
                                            <?php
                                            /*
                                             *	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                                             */
                                            $sql = "SELECT * ";
                                            $sql .= " FROM categorias ";
                                            $sql .= " WHERE idSec = 27 ORDER BY descripcion asc";

                                            $PSN1->query($sql);
                                            $numero = $PSN1->num_rows();
                                            if ($numero > 0) {
                                                while ($PSN1->next_record()) {
                                                    ?>
                                                    <option value="<?= $PSN1->f('id'); ?>" <?php
                                                      if ($cliente_tipo1 == $PSN1->f('id')) {
                                                          ?>selected="selected" <?php
                                                      }
                                                      ?>><?= $PSN1->f('descripcion'); ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select></div>

                                    <label class="control-label col-sm-2" for="cliente_servicio1"><strong>Tipo de
                                            contrato:</strong></label>
                                    <div class="col-sm-4"><select name="cliente_servicio1" class="form-control">
                                            <option value="">Sin especificar</option>
                                            <?php
                                            /*
                                             *	TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                                             */
                                            $sql = "SELECT * ";
                                            $sql .= " FROM categorias ";
                                            $sql .= " WHERE idSec = 28 ORDER BY descripcion asc";

                                            $PSN1->query($sql);
                                            $numero = $PSN1->num_rows();
                                            if ($numero > 0) {
                                                while ($PSN1->next_record()) {
                                                    ?>
                                                    <option value="<?= $PSN1->f('id'); ?>" <?php
                                                      if ($cliente_servicio1 == $PSN1->f('id')) {
                                                          ?>selected="selected" <?php
                                                      }
                                                      ?>><?= $PSN1->f('descripcion'); ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select></div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="cliente_observaciones"><strong>Ampliación de los
                                            servicios ofrecidos:</strong></label>
                                    <div class="col-sm-10"><textarea name="cliente_observaciones" id="cliente_observaciones"
                                            class="form-control"><?= $cliente_observaciones; ?></textarea></div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="cliente_valor1"><strong>Valor del
                                            contrato:</strong></label>
                                    <div class="col-sm-4"><input name="cliente_valor1" type="text" id="cliente_valor1"
                                            maxlength="255" value="<?= $cliente_valor1; ?>" class="form-control" /></div>

                                    <label class="control-label col-sm-2" for="cliente_diaPago"><strong>Día de
                                            pago:</strong></label>
                                    <div class="col-sm-4"><input name="cliente_diaPago" type="number" id="cliente_diaPago"
                                            value="<?= $cliente_diaPago; ?>" class="form-control" /></div>
                                </div>

                                <div class="row">
                                    <h3 class="text-center well">FECHAS DE VIGENCIA</h3>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="cliente_fechaAprob"><strong>Fecha de aprobación
                                            cliente:</strong></label>
                                    <div class="col-sm-4"><input name="cliente_fechaAprob" type="date" placeholder="AAAA-MM-DD"
                                            id="cliente_fechaAprob" value="<?= $cliente_fechaAprob; ?>" class="form-control" />
                                    </div>

                                    <label class="control-label col-sm-2" for="cliente_fechaAprobCont"><strong>Fecha aprobación
                                            contrato:</strong></label>
                                    <div class="col-sm-4"><input name="cliente_fechaAprobCont" type="date" placeholder="AAAA-MM-DD"
                                            id="cliente_fechaAprobCont" value="<?= $cliente_fechaAprobCont; ?>"
                                            class="form-control" /></div>
                                </div>


                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="cliente_fechaInicial"><strong>Fecha de inicio
                                            contrato:</strong></label>
                                    <div class="col-sm-4"><input name="cliente_fechaInicial" type="date" placeholder="AAAA-MM-DD"
                                            id="cliente_fechaInicial" value="<?= $cliente_fechaInicial; ?>" class="form-control" />
                                    </div>

                                    <label class="control-label col-sm-2" for="cliente_fechaFinal"><strong>Fecha final
                                            contrato:</strong></label>
                                    <div class="col-sm-4"><input name="cliente_fechaFinal" type="date" placeholder="AAAA-MM-DD"
                                            id="cliente_fechaFinal" value="<?= $cliente_fechaFinal; ?>" class="form-control" />
                                    </div>
                                </div>


                            </div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "SERVICOS" //-->
                            <?php
                        }
                        //  USUARIO cliente o proveedor
                        if ($ctrl == 1 || $ctrl == 2 || $ctrl == 3) {
                            ?>
                            <div id="archivos" class="tab-pane fade">

                                <div class="row">
                                    <h3 class="text-center well">DOCUMENTOS</h3>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2"
                                        for="documento_identificacion"><strong>Identificación:</strong></label>
                                    <div class="col-sm-4"><input name="documento_identificacion" type="file"
                                            id="documento_identificacion" class="form-control" /></div>

                                    <label class="control-label col-sm-2"
                                        for="documento_contrato"><strong>Contrato:</strong></label>
                                    <div class="col-sm-4"><input name="documento_contrato" type="file" id="documento_contrato"
                                            class="form-control" /></div>
                                </div>
                                <?php
                                if ($ctrl == 2 || $ctrl == 3) {
                                    ?>
                                    <div class="form-group">
                                        <label class="control-label col-sm-2" for="documento_rut"><strong>RUT:</strong></label>
                                        <div class="col-sm-4"><input name="documento_rut" type="file" id="documento_rut"
                                                class="form-control" /></div>

                                        <label class="control-label col-sm-2"
                                            for="documento_constitucion"><strong>Constitución:</strong></label>
                                        <div class="col-sm-4"><input name="documento_constitucion" type="file"
                                                id="documento_constitucion" class="form-control" /></div>
                                    </div>
                                    <?php
                                }
                                ?>


                                <div class="row">
                                    <h3 class="text-center well">AGREGAR DOCUMENTOS ADICIONALES</h3>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-sm-2" for="documento_adicional_nom"><strong>Descripción del
                                            documento:</strong></label>
                                    <div class="col-sm-4"><input name="documento_adicional_nom" type="text"
                                            id="documento_adicional_nom" placeholder="Descripción del documento que va a agregar"
                                            class="form-control" /></div>

                                    <label class="control-label col-sm-2"
                                        for="documento_adicional_file"><strong>Archivo:</strong></label>
                                    <div class="col-sm-4"><input name="documento_adicional_file" type="file"
                                            id="documento_adicional_file" class="form-control" /></div>
                                </div>

                                <?php
                                if ($idUsuarioActual > 0) {
                                    ?>
                                    <div class="row">
                                        <h3 class="text-center well">.DOCUMENTOS CARGADOS.</h3>
                                    </div>

                                    <table class="table table-striped table-hover">
                                        <?php
                                        if ($documento_identificacion != "") {
                                            ?>
                                            <tr>
                                                <td><a href='descarga_usuario.php?&archivo=<?= $documento_identificacion; ?>'>Documento de
                                                        Identificación</a></td>
                                                <td><a
                                                        href='index.php?&doc=usuario&deldoc=identificacion&id=<?= $idUsuarioActual; ?>&deldoc_name=<?= $documento_identificacion; ?>'>[BORRAR]</a>
                                                </td>
                                            </tr><?php
                                        }

                                        if ($documento_rut != "") {
                                            ?>
                                            <tr>
                                                <td><a href='descarga_usuario.php?&archivo=<?= $documento_rut; ?>'>RUT</a></td>
                                                <td><a
                                                        href='index.php?&doc=usuario&deldoc=rut&id=<?= $idUsuarioActual; ?>&deldoc_name=<?= $documento_rut; ?>'>[BORRAR]</a>
                                                </td>
                                            </tr><?php
                                        }

                                        if ($documento_constitucion != "") {
                                            ?>
                                            <tr>
                                                <td><a href='descarga_usuario.php?&archivo=<?= $documento_constitucion; ?>'>Constitución</a>
                                                </td>
                                                <td><a
                                                        href='index.php?&doc=usuario&deldoc=constitucion&id=<?= $idUsuarioActual; ?>&deldoc_name=<?= $documento_constitucion; ?>'>[BORRAR]</a>
                                                </td>
                                            </tr><?php
                                        }

                                        if ($documento_contrato != "") {
                                            ?>
                                            <tr>
                                                <td><a href='descarga_usuario.php?&archivo=<?= $documento_contrato; ?>'>Contrato</a></td>
                                                <td><a
                                                        href='index.php?&doc=usuario&deldoc=contrato&id=<?= $idUsuarioActual; ?>&deldoc_name=<?= $documento_contrato; ?>'>[BORRAR]</a>
                                                </td>
                                            </tr><?php
                                        }

                                        /*
                                         *	TRAEMOS LOS DOCUMENTOS ADICIONALES
                                         */
                                        $sql = "SELECT * ";
                                        $sql .= " FROM usuario_documentos_add	 ";
                                        $sql .= " WHERE idUsuario = '" . $idUsuarioActual . "' ORDER BY descripcion asc";
                                        //
                                        $PSN1->query($sql);
                                        $numero = $PSN1->num_rows();
                                        if ($numero > 0) {
                                            while ($PSN1->next_record()) {
                                                ?>
                                                <tr>
                                                    <td><a href='descarga_usuario.php?&archivo=<?= $PSN1->f('archivo'); ?>'>Adicional -
                                                            <?= $PSN1->f('descripcion'); ?></a></td>
                                                    <td><a
                                                            href='index.php?&doc=usuario&deldoc=<?= $PSN1->f('id'); ?>&id=<?= $idUsuarioActual; ?>&deldoc_name=<?= $PSN1->f('archivo'); ?>'>[BORRAR]</a>
                                                    </td>
                                                </tr><?php
                                            }
                                        }
                                        ?>
                                    </table>
                                    <?php
                                }
                                ?>
                            </div> <!-- FIN DEL TAB DE INFORMACION INICAL DE "DOCUMENTOS" //--><?php
                        }

                        //  OBSERVACIONES para todos los clientes
                        if ($idUsuarioActual != 0) {
                            ?>
                            <div id="tab_observaciones" class="tab-pane fade">
                                <?php if ($soloLecturaFacilitador) { ?>
                                    <div class="alert alert-info">Solo tiene permiso de visualizacion en la seccion Coach.</div>
                                <?php } else { ?>
                                    <div class="row">
                                        <div class="panel panel-default">
                                            <div class="panel-body"><iframe
                                                    src="int_new.php?doc=usuario_int_obs&id=<?= $idUsuarioActual; ?>#final"
                                                    name="frameObs" id="frameObs" width="100%" height="460px" frameborder="0"
                                                    marginheight="0" marginwidth="0"></iframe>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>

                            </div>

                            <div id="tab_metas" class="tab-pane fade">


                                <div class="row">
                                    <div class="panel panel-default">
                                        <div class="panel-body"><iframe
                                                src="int_new.php?doc=usuario_int_met&id=<?= $idUsuarioActual; ?>#final"
                                                name="frameMet" id="frameMet" width="100%" height="460px" frameborder="0"
                                                marginheight="0" marginwidth="0"></iframe>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <?php
                        }
                        ?>

                        <div id="accesos" class="tab-pane fade">
                            <?php if ($soloLecturaFacilitador) { ?>
                                <div class="alert alert-info">Solo tiene permiso de visualizacion en Acceso al sistema.</div>
                                <fieldset disabled="disabled">
                            <?php } ?>
                            <div class="row">
                                <h3 class="text-center well">ACCESO AL SISTEMA</h3>
                            </div>

                            <div class="row">
                                <h5 class="alert alert-warning text-center">Su login será su identificación de la pestaña
                                    general</h5>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-2" for="acceso"><strong>Acceso al sistema:</strong></label>
                                <div class="col-sm-4"><input name="acceso" type="checkbox" id="acceso" value="1" <?php if ($general_acceso == 1) { ?>checked="checked" <?php } ?> class="form-control" /></div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-2" for="excluido_reportes"><strong>Excluido de
                                        reportes:</strong></label>
                                <div class="col-sm-4"><input name="excluido_reportes" type="checkbox" id="excluido_reportes"
                                        value="1" <?php if ($general_excluido_reportes == 1) { ?>checked="checked" <?php } ?>
                                        class="form-control" /></div>
                                <div class="col-sm-6"><small class="text-muted">Si está marcado, este facilitador no aparecerá
                                        en los reportes de promedios por defecto</small></div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-2" for="password"><strong>Password</strong></label>
                                <div class="col-sm-4"><input name="password" type="password" id="password" class="form-control"
                                        minlength="4" /></div>
                                <label class="control-label col-sm-2" for="password_check"><strong>Repita el
                                        password</strong></label>
                                <div class="col-sm-4"><input name="password_check" type="password" id="password_check"
                                        maxlength="250" value="" class="form-control" minlength="4" /></div>
                            </div>
                            <?php
                            //
                            //  USUARIO interno
                            //
                            if ($ctrl == 1 || $ctrl == 4 || $ctrl == 2) {
                                ?>
                                <div class="row">
                                    <h3 class="text-center well">ACCESOS AL MENU</h3>
                                </div>

                                <?php
                                if ($idUsuarioActual != 0) {
                                    /*
                                     *	ITEMS DEL MENU
                                     */
                                    /*
                                     *	ITEMS DEL MENU
                                     */
                                    $sql = "SELECT menu.*, usuarios_menu.idUsuario ";
                                    $sql .= " FROM menu ";
                                    $sql .= " LEFT JOIN usuarios_menu ON
                            usuarios_menu.idMenu = menu.id AND 
                            usuarios_menu.idUsuario = " . $idUsuarioActual;
                                    //Usuario autorizado cliente o cliente                
                                    if ($ctrl == 4 || $ctrl == 2) {
                                        $sql .= " WHERE menu.paracliente = 1 AND menu.estado = 1";
                                    } else {
                                        $sql .= " WHERE menu.estado = 1";
                                    }
                                    //                    
                                    $sql .= " ORDER BY principal, orden asc";
                                } else {
                                    /*
                                     *	ITEMS DEL MENU
                                     */
                                    $sql = "SELECT * ";
                                    $sql .= " FROM menu ";
                                    //                
                                    if ($ctrl == 4 || $ctrl == 2) {
                                        $sql .= " WHERE menu.paracliente = 1 AND menu.estado = 1";
                                    } else {
                                        $sql .= " WHERE menu.estado = 1";
                                    }
                                    //                    
                                    $sql .= " ORDER BY principal, orden asc";
                                }
                                //
                                $PSN1->query($sql);
                                $numero = $PSN1->num_rows();
                                if ($numero > 0) {
                                    $cont = 0;

                                    $principal_old = 0;
                                    while ($PSN1->next_record()) {
                                        if ($cont == 2) {
                                            ?>
                                        </div><!-- CLOSE INSIDE //--><?php
                                        }

                                        if ($principal_old != $PSN1->f("principal")) {

                                            if ($principal_old != 0 && $cont != 2) {
                                                ?>
                                        </div><!-- CLOSE INSIDE //--><?php
                                            }

                                            ?><!-- OPEN //-->
                                    <div class="form-group">
                                        <div class="col-sm-12">
                                            <h5 class="alert alert-info"><?php

                                            $principal_old = $PSN1->f("principal");
                                            //
                                            switch ($PSN1->f("principal")) {
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
                                                    echo "Mapeo de iglesias";
                                                    break;
                                                case 99:
                                                    echo "Mi cuenta";
                                                    break;
                                                default:
                                                    echo "Otras opciones";
                                                    break;
                                            }
                                            ?></h5>
                                        </div>
                                    </div><!-- CLOSE INSIDE //-->


                                    <!-- OPEN INSIDE //-->
                                    <div class="form-group"><?php
                                    $cont = 0;
                                        }


                                        if ($cont == 2) {
                                            ?><!-- OPEN INSIDE //-->
                                        <div class="form-group"><?php
                                        $cont = 0;
                                        }

                                        ?>
                                        <label class="control-label col-sm-2" for="menu_<?= $PSN1->f('id'); ?>"><?= $PSN1->f('imagen'); ?>
                                            <strong><?= $PSN1->f('nombre'); ?></strong></label>
                                        <div class="col-sm-4"><input type="checkbox" id="menu_<?= $PSN1->f('id'); ?>" name="menu[]" value="<?= $PSN1->f('id'); ?>"
                                                class="form-control" <?php
                                                if ($PSN1->f('idUsuario') != "" && $PSN1->f('idUsuario') != 0) {
                                                    ?>checked="checked" <?php
                                                }
                                                ?> /></div>
                                        <?php
                                        $cont++;
                                    }
                                    ?>
                                </div><?php
                                }
                                ?>
                            <?php
                            }
                            ?>
                            <?php if ($soloLecturaFacilitador) { ?>
                                </fieldset>
                            <?php } ?>
                </div> <!-- FIN TAB DE ACCESOS //-->

                    <?php
                    //
                    //  USUARIO interno
                    //
                    if ($ctrl == 1 || $ctrl == 2 || $ctrl == 4) {
                        ?>
                    <div id="graficas" class="tab-pane fade">
                        <?php if ($soloLecturaFacilitador) { ?>
                            <div class="alert alert-info">Solo tiene permiso de visualizacion en Acceso a graficas.</div>
                            <fieldset disabled="disabled">
                        <?php } ?>
                            <div class="row">
                                <h3 class="text-center well">ACCESO A GRAFICAS</h3>
                            </div>


                            <div class="form-group">
                                <label class="control-label col-sm-2" for="acceso_graphs"><strong>Acceso a
                                        graficas:</strong></label>
                                <div class="col-sm-4"><input name="acceso_graphs" type="checkbox" id="acceso_graphs" value="1" <?php if ($general_acceso_graphs == 1) { ?>checked="checked" <?php } ?> class="form-control" />
                                </div>
                            </div>

                            <div class="row">
                                <h3 class="text-center well">.ACCESOS.</h3>
                            </div>
                            <?php
                            if ($idUsuarioActual != 0) {
                                /*
                                 *	ITEMS DEL MENU
                                 */
                                $sql = "SELECT menu_graphs.*, usuarios_menu_graphs.idUsuario ";
                                $sql .= " FROM menu_graphs ";
                                $sql .= " LEFT JOIN usuarios_menu_graphs ON
                            usuarios_menu_graphs.idMenu = menu_graphs.id AND 
                            usuarios_menu_graphs.idUsuario = " . $idUsuarioActual;
                                //Usuario autorizado cliente o cliente                
                                if ($ctrl == 4 || $ctrl == 2) {
                                    $sql .= " WHERE menu_graphs.paracliente = 1 AND menu_graphs.estado = 1";
                                } else {
                                    $sql .= " WHERE menu_graphs.estado = 1";
                                }
                                //
                                $sql .= " ORDER BY principal, orden asc";
                            } else {
                                /*
                                 *	ITEMS DEL MENU
                                 */
                                $sql = "SELECT * ";
                                $sql .= " FROM menu_graphs ";
                                //                
                                if ($ctrl == 4 || $ctrl == 2) {
                                    $sql .= " WHERE menu_graphs.paracliente = 1 AND menu_graphs.estado = 1";
                                } else {
                                    $sql .= " WHERE menu_graphs.estado = 1";
                                }
                                //
                                $sql .= " ORDER BY principal, orden asc";
                            }
                            //
                            $PSN1->query($sql);
                            $numero = $PSN1->num_rows();
                            if ($numero > 0) {
                                $cont = 0;

                                $principal_old = 0;
                                ?>
                                <div class="form-group"><?php
                                while ($PSN1->next_record()) {
                                    if ($cont == 2) {
                                        ?></div><!-- CLOSE INSIDE //--><?php
                                    }

                                    if ($cont == 2) {
                                        ?><!-- OPEN INSIDE //-->
                                        <div class="form-group"><?php
                                        $cont = 0;
                                    }

                                    ?>
                                        <label class="control-label col-sm-2" for="menu_graphs_<?= $PSN1->f('id'); ?>"><?= $PSN1->f('imagen'); ?>
                                            <strong><?= $PSN1->f('nombre'); ?></strong></label>
                                        <div class="col-sm-4"><input type="checkbox" id="menu_graphs_<?= $PSN1->f('id'); ?>" name="menu_graphs[]" value="<?= $PSN1->f('id'); ?>"
                                                class="form-control" <?php
                                                if ($PSN1->f('idUsuario') != "" && $PSN1->f('idUsuario') != 0) {
                                                    ?>checked="checked" <?php
                                                }
                                                ?> /></div>
                                        <?php
                                        $cont++;
                                }
                                ?>
                                </div><?php
                            }
                            ?>
                        <?php if ($soloLecturaFacilitador) { ?>
                            </fieldset>
                        <?php } ?>
                    </div> <!-- FIN TAB DE ACCESOS GRAFICOS //-->
                        <?php
                    }
                    ?>

                </div> <!-- FIN TABS DIV //-->
        </div> <!-- FIN CONTENEDOR DE TABS //-->

        <input type="hidden" name="funcion" id="funcion" value="" />
        <input type="hidden" name="lat" id="lat" value="">
        <input type="hidden" name="lon" id="lon" value="">

        <center><input id="send" type="button" name="button" value="Guardar cambios" class="btn btn-success"> <a
                href="index.php?doc=main" class="btn btn-danger">Cerrar</a> </center>

        </form>
        </div>

        <?php
        if ($idUsuarioActual != 0 && $ctrl == 2) {
            ?>
            <center>
                <a href="index.php?doc=usuario_buscar&ctrl=4&cliente=<?= $idUsuarioActual; ?>" target="_blank"
                    class="btn btn-primary">Ver autorizados</a>
                <a href="index.php?doc=usuario&ctrl=4&idCliente=<?= $idUsuarioActual; ?>" class="btn btn-primary">Crear
                    autorizado</a>
            </center>

            <br />
            <center>
                <a href="index.php?doc=vehiculo_buscar&idCliente=<?= $idUsuarioActual; ?>" target="_blank" class="btn btn-info">Ver
                    vehículos</a>
                <a href="index.php?doc=vehiculo&idCliente=<?= $idUsuarioActual; ?>" class="btn btn-info">Crear vehículo</a>
            </center>
            <?php
        }
        ?>

        <script type="text/javascript">
            const btnSend = document.getElementById('send')
            const esFacilitadorSoloLectura = <?= $soloLecturaFacilitador ? 'true' : 'false'; ?>;
            const tabsBloqueadasFacilitador = ['#empresa', '#tab_observaciones', '#accesos', '#graficas'];

            function actualizarEstadoBotonGuardar() {
                if (!btnSend) {
                    return false;
                }

                const pestañaActiva = document.querySelector('.nav-tabs li.active a[data-toggle="tab"]');
                const tabActiva = document.querySelector('.tab-content .tab-pane.active');
                const tabActivaId = pestañaActiva
                    ? pestañaActiva.getAttribute('href')
                    : (tabActiva ? `#${tabActiva.id}` : '');
                const debeBloquear = esFacilitadorSoloLectura && tabsBloqueadasFacilitador.includes(tabActivaId);

                btnSend.disabled = debeBloquear;
                if (debeBloquear) {
                    btnSend.title = 'Los facilitadores no pueden guardar cambios en esta seccion.';
                } else {
                    btnSend.removeAttribute('title');
                }

                return debeBloquear;
            }

            actualizarEstadoBotonGuardar();

            document.querySelectorAll('.nav-tabs a[data-toggle="tab"]').forEach((tabLink) => {
                tabLink.addEventListener('shown.bs.tab', actualizarEstadoBotonGuardar);
                tabLink.addEventListener('click', () => {
                    setTimeout(actualizarEstadoBotonGuardar, 50);
                });
            });

            if (window.jQuery) {
                window.jQuery('.nav-tabs a[data-toggle="tab"]').on('shown.bs.tab', actualizarEstadoBotonGuardar);
            }

            btnSend.addEventListener('click', async () => {
                if (actualizarEstadoBotonGuardar()) {
                    return;
                }
                try {
                    let {
                        data,
                        ubicacion
                    } = await getUbicacionUsuario();
                    console.log({
                        data: data.results[0].geometry.location,
                        ubicacion
                    });

                    if (data.results.length === 0) {
                        document.getElementById('aviso').value = "¡Ups! no pudimos encontrar tu ubicación exacta; Ingresa una dirección con un formato legible."

                        if (generarForm()) {
                            document.getElementById('form1').submit();
                        }
                    } else {

                        document.getElementById('lon').value = data.results[0].geometry.location.lng;
                        document.getElementById('lat').value = data.results[0].geometry.location.lat;

                        document.getElementById('aviso').value = "¡Ubicación exacta encontrada!"
                        if (generarForm()) {
                            document.getElementById('form1').submit();
                        }
                    }
                } catch (error) {
                    console.error(error);
                    document.getElementById('aviso').value = "¡Ups! hubo un error de conexión al intentar encontrar tu ubicación exacta intentalo de nuevo mas tarde.";
                    if (generarForm()) {
                        document.getElementById('form1').submit();
                    }

                }
            })
            async function getLatLonUsuario(ubicacion) {
                try {
                    const url_key = "AIzaSyApb0SrRC3HCwPRdmAbfNgygseP4acYdeY";
                    const url = `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(ubicacion)}&key=${url_key}`;
                    const res = await fetch(url);
                    const data = await res.json();
                    return {
                        data,
                        ubicacion
                    };
                } catch (error) {
                    throw new Error(error);
                }
            }

            async function getUbicacionUsuario() {

                const direccion = document.getElementById('direccion').value;
                const elementoDepartamento = document.getElementById('departamento');
                const elementoMunicipio = document.getElementById('municipioselect');

                let departamento = elementoDepartamento.options[elementoDepartamento.selectedIndex].textContent;
                let municipio = elementoMunicipio.options[elementoMunicipio.selectedIndex].textContent;

                try {
                    let ubicacionLatLon = await getLatLonUsuario(`${direccion} ${departamento} ${municipio}`);
                    return ubicacionLatLon;
                } catch (error) {

                    return error;

                }

            }

            function generarForm() {
                if (confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?")) {
                    if (document.getElementById('nombre').value != "" &&
                        document.getElementById('identificacion').value != ""
                    ) {
                        if (document.getElementById('password').value != "") {
                            if (document.getElementById('password').value != document.getElementById('password_check').value) {
                                alert("Password no coincide");
                                return false;
                            }
                        }

                        document.getElementById('funcion').value = "<?= $temp_accionForm; ?>";
                    } else {
                        alert("La informacion es primordial para brindarle un excelente servicio, por favor digite al menos los campos de NOMBRE e IDENTIFICACIÓN");
                        return false;
                    }
                } else {
                    return false;
                }
                return true;
            }

            function recargaLista() {
                $.ajax({
                    type: "POST",
                    url: "/get_muni.php",
                    data: "id_depa=" + $('#departamento').val(),
                    success: function (r) {
                        $('#municipio').html(r);
                        // getUbicacionUsuario().then(res => console.log(res)).catch(err => console.error(err))
                    }
                })
            }
            $(document).ready(function () {
                recargaLista();


                $('#departamento').change(function () {
                    recargaLista();
                })
            })

            // function init() {
            //     document.getElementById('form1').onsubmit = function() {
            //         return generarForm();
            //     }




            <?php
            if ($varExitoUSU == 1) {
                ?>alert("Se ha colocado correctamente el ACCESO, espere mientras es dirigido.");
                window.location.href = "index.php?doc=admin_usu4&id=<?= $ultimoId; ?>";
                <?php
            }
            ?>


            jQuery(document).ready(function ($) {
                $(".clickable-row").click(function () {
                    window.location.href = $(this).data("href");
                });
            });


            // window.onload = function() {
            //     init();

            // }
        </script>

        <?php
        }
}   //FIN DEL IF DE REDIRIGIR SI YA INSERTO EL REGISTRO
?>
