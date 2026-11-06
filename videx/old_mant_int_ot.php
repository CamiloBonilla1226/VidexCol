<?php
if(isset($_GET["id"]))
{
	$idSolicitud = soloNumeros($_GET["id"]);
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
$PSN2 = new DBbase_Sql;
// Array que nos servira para ir llevando cuenta de los requerimientos.
$arrayRequerimientos = array();


if($_SESSION["perfil"] == 4){
    $form_readonly = 1;
    $sql_filtro = " AND veh_solicitudes_cot.idProveedor = ".$_SESSION["id"];
    $_REQUEST["ctrl_pro"] = $_SESSION["id"];
    $idProveedor = $_SESSION["id"];
}
else if($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 160){
    $form_readonly = 1;
    $sql_filtro = " AND vehiculo.idCliente = ".$_SESSION["micliente"];
}



//
// COMPROBAMOS QUE EL USUARIO PERTENEZCA A ESTE REPORTE O SEA LIDER/ADMINISTRADOR
//
$sql = "SELECT veh_solicitudes_cot.id";
$sql.=" FROM veh_solicitudes_cot, vehiculo ";
$sql.=" WHERE veh_solicitudes_cot.id = '".$idSolicitud."' AND vehiculo.id = veh_solicitudes_cot.idVehiculo ";
$sql.=$sql_filtro;
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
if(isset($_POST["funcion"]))
{
	if($_POST["funcion"] == "pago_cli_actualizarOT" && eliminarInvalidos($_POST["pago_cli_fechaFactura"]) != "")
	{
        $idLineaOT = eliminarInvalidos($_POST["idLineaOT"]);
        //
        $pago_cli_estado  = soloNumeros($_POST["pago_cli_estado"]);
        //
        $pago_cli_fechaFactura = eliminarInvalidos($_POST["pago_cli_fechaFactura"]);
        $pago_cli_observaciones = eliminarInvalidos($_POST["pago_cli_observaciones"]);
        //
        $pago_cli_fechaPago = eliminarInvalidos($_POST["pago_cli_fechaPago"]);
        $pago_cli_fechaPagoReal = eliminarInvalidos($_POST["pago_cli_fechaPagoReal"]);
        //
        if($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 160){
            //
            $sql = 'UPDATE veh_solicitudes_cot_pro 
                        SET 
                            modUsuario = "'.$_SESSION["id"].'",
                            modFecha = NOW() 
                            ';
            $sql .= ', pago_cli_fechaPago = "'.$pago_cli_fechaPago.'"';
            $sql .= ', pago_cli_estado = "'.$pago_cli_estado.'"';
            $sql .= ' WHERE  id = "'.$idLineaOT.'"';
            //
            $ultimoQuery = $PSN1->query($sql);
            //
            
        }else{
            //
            $nombre_archivo = $_FILES['pago_cli_archivo']['name'];
            $temp_location = $_FILES['pago_cli_archivo']['tmp_name'];
            $temp_ext = extension_archivo($nombre_archivo);
            $temp_nombreFile = "cli_fact_ot".strtotime("now").".".$temp_ext;
            $archivo = "";

            //
            if(move_uploaded_file($temp_location, "archivos/solicitudes/".$temp_nombreFile))
            {
                $archivo = $temp_nombreFile;
            }
            //
            $sql = 'UPDATE veh_solicitudes_cot_pro 
                        SET 
                            modUsuario = "'.$_SESSION["id"].'",
                            modFecha = NOW() 
                            ';
            $sql .= ', pago_cli_fechaPago = "'.$pago_cli_fechaPago.'"';
            $sql .= ', pago_cli_fechaPagoReal = "'.$pago_cli_fechaPagoReal.'"';
            $sql .= ', pago_cli_fechaFactura = "'.$pago_cli_fechaFactura.'"
                    , pago_cli_estado = "'.$pago_cli_estado.'"
                    , pago_cli_observaciones = "'.$pago_cli_observaciones.'"';

            //Solo actualizamos el archivo si no está en blanco para impedir en caso de actualización que borremos el archivo anterior.
            if($archivo != ""){
                $sql .= ', pago_cli_archivo = "'.$archivo.'"';
            }
            //
            $sql .= '
                    WHERE 
                        id = "'.$idLineaOT.'"
                    ';
            //
            $ultimoQuery = $PSN1->query($sql);            
        }
		$varExito = 1;
        //
	}  
	else if($_POST["funcion"] == "pago_prov_actualizarOT" && eliminarInvalidos($_POST["pago_pro_fechaFactura"]) != "")
	{
        $idLineaOT = eliminarInvalidos($_POST["idLineaOT"]);
        //
        $pago_pro_estado = 10;
        $pago_pro_fechaFactura = eliminarInvalidos($_POST["pago_pro_fechaFactura"]);
        $pago_pro_observaciones = eliminarInvalidos($_POST["pago_pro_observaciones"]);
        //
        $pago_pro_fechaPago = eliminarInvalidos($_POST["pago_pro_fechaPago"]);
        $pago_pro_fechaPagoReal = eliminarInvalidos($_POST["pago_pro_fechaPagoReal"]);
        //
        $nombre_archivo = $_FILES['pago_pro_archivo']['name'];
        $temp_location = $_FILES['pago_pro_archivo']['tmp_name'];
        $temp_ext = extension_archivo($nombre_archivo);
        $temp_nombreFile = "pro_fact_ot".strtotime("now").".".$temp_ext;
        $archivo = "";

        //
        if(move_uploaded_file($temp_location, "archivos/solicitudes/".$temp_nombreFile))
        {
            $archivo = $temp_nombreFile;
        }
        //
        if($_SESSION["perfil"] != 4){
            $pago_pro_estado = soloNumeros($_POST["pago_pro_estado"]);
        }
        //        
		$sql = 'UPDATE veh_solicitudes_cot_pro 
					SET 
                        modUsuario = "'.$_SESSION["id"].'",
                        modFecha = NOW() 
                        ';
        /*
        *
        */
        if($_SESSION["perfil"] != 4){
            $sql .= ', pago_pro_fechaPago = "'.$pago_pro_fechaPago.'"';
            $sql .= ', pago_pro_estado = "'.$pago_pro_estado.'"';
            $sql .= ', pago_pro_fechaPagoReal = "'.$pago_pro_fechaPagoReal.'"';
        }
        else{
            $sql .= ', pago_pro_fechaFactura = "'.$pago_pro_fechaFactura.'"
                    , pago_pro_estado = "'.$pago_pro_estado.'" 
                    , pago_pro_observaciones = "'.$pago_pro_observaciones.'"';
        }

        //Solo actualizamos el archivo si no está en blanco para impedir en caso de actualización que borremos el archivo anterior.
        if($archivo != ""){
            $sql .= ', pago_pro_archivo = "'.$archivo.'"';
        }
        //
        $sql .= '
                WHERE 
                    id = "'.$idLineaOT.'"
                ';
        //
		$ultimoQuery = $PSN1->query($sql);
		$varExito = 1;
        //
	}     
	else if($_POST["funcion"] == "actualizarOT" && eliminarInvalidos($_POST["ot_fechaEntrega"]) != "")
	{
        $idLineaOT = eliminarInvalidos($_POST["idLineaOT"]);
        $ot_fechaEntrega = eliminarInvalidos($_POST["ot_fechaEntrega"]);
        $ot_fechaEntregaReal = eliminarInvalidos($_POST["ot_fechaEntregaReal"]);
        
        $ot_estado = soloNumeros($_POST["ot_estado"]);
        //        
		$sql = 'UPDATE veh_solicitudes_cot_pro 
					SET 
                        ot_fechaEntrega = "'.$ot_fechaEntrega.'",
                        ot_estado = "'.$ot_estado.'",
                        modUsuario = "'.$_SESSION["id"].'",
                        modFecha = NOW() 
                        ';
        //
        if($_SESSION["perfil"] != 4){
            $sql .= ', ot_fechaEntregaReal = "'.$ot_fechaEntregaReal.'"';
        }
        //
        $sql .= '
                WHERE 
                    id = "'.$idLineaOT.'"
                ';
        //
		$ultimoQuery = $PSN1->query($sql);
		$varExito = 1;
        //
	}
	else if($_POST["funcion"] == "facturarOT" && eliminarInvalidos($_POST["factura_fecha"]) != "")
	{
        $factura_fecha = eliminarInvalidos($_POST["factura_fecha"]);
        $factura_obs = eliminarInvalidos($_POST["factura_obs"]);
        //
        //        
        $nombre_archivo = $_FILES['factura_archivo']['name'];
        $temp_location = $_FILES['factura_archivo']['tmp_name'];
        $temp_ext = extension_archivo($nombre_archivo);
        $factura_ext = $temp_ext;
        //
        $temp_nombreFile = "fact".$idSolicitud.".".$temp_ext;
        $archivo = "";
        //
        if(move_uploaded_file($temp_location, "archivos/solicitudes/".$temp_nombreFile))
        {
            $archivo = $temp_nombreFile;
        }
        //        
        //        
		$sql = 'UPDATE veh_solicitudes_cot SET 
                    factura_fecha = "'.$factura_fecha.'",
                    factura_obs = "'.$factura_obs.'",
                    factura_ext = "'.$factura_ext.'",
                    modUsuario = "'.$_SESSION["id"].'",
                    modFecha = NOW() 
                WHERE 
                    id = "'.$idSolicitud.'"
                ';
        //
		$ultimoQuery = $PSN1->query($sql);
		$varExito = 1;
        //
	}
    else if($_POST["funcion"] == "actualizarCotizacion" && eliminarInvalidos($_POST["observaciones"]) != "")
	{
        
        $idLinea = soloNumeros($_POST["idLinea"]);
        
        $observaciones = eliminarInvalidos($_POST["observaciones"]);
        $fechaVencimiento = eliminarInvalidos($_POST["fechaVencimiento"]);
        $valor = soloNumeros($_POST["valor"]);
        //        
        $nombre_archivo = $_FILES['archivo']['name'];
        $temp_location = $_FILES['archivo']['tmp_name'];
        $temp_ext = extension_archivo($nombre_archivo);
        $temp_nombreFile = "cot".strtotime("now").".".$temp_ext;
        $archivo = "";

        if(move_uploaded_file($temp_location, "archivos/solicitudes/".$temp_nombreFile))
        {
            $archivo = $temp_nombreFile;
        }
        
		$sql = 'UPDATE veh_solicitudes_cot_pro SET 
                    valor = "'.$valor.'", 
                    observacion = "'.$observaciones.'", 
                    fechaVencimiento = "'.$fechaVencimiento.'"';
        
        if($archivo != ""){
            $sql .= ', archivo = "'.$archivo.'"';
        }
        
        $sql .= ' WHERE id = "'.$idLinea.'" AND idSolicitud = "'.$idSolicitud.'" AND cot_pro_estado = 0';
        $PSN1->query($sql);
		$varExito = 1; 
        $idLinea = "";
        $_POST["idLinea"] = "";
        $_REQUEST["idLinea"] = "";
    }
    else if($_POST["funcion"] == "aprobarCotizacion" && eliminarInvalidos($_POST["aprobacion_observaciones"]) != "")
	{
        $idLinea = soloNumeros($_POST["idLinea"]);        
        $aprobacion_observaciones = eliminarInvalidos($_POST["aprobacion_observaciones"]);
        $cot_pro_estado = soloNumeros($_POST["cot_pro_estado"]);
        //        
		$sql = 'UPDATE veh_solicitudes_cot_pro SET 
                    cot_pro_estado = "'.$cot_pro_estado.'",
                    aprobacion_fecha = NOW(), 
                    aprobacion_usuario = "'.$_SESSION["id"].'", 
                    aprobacion_observaciones = "'.$aprobacion_observaciones.'"';
        $sql .= ' WHERE id = "'.$idLinea.'" AND idSolicitud = "'.$idSolicitud.'" AND cot_pro_estado = 0';
        $PSN1->query($sql);
		$varExito = 1; 
        $idLinea = "";
        $_POST["idLinea"] = "";
        $_REQUEST["idLinea"] = "";
        $_POST["estado"] = "";
        $_REQUEST["estado"] = "";
    }    
}

//$varExito = 1;
/*
*
*/
if($varExito == 1 || !isset($_REQUEST["idLinea"])){
    //
    if($varExito == 1){
        ?><div class="container">
            <div class="row">¡REGISTRO ACTUALIZADO!</h2>
            </div>

            <div class="row">
                <h5 class="alert alert-warning text-center">Se ha actualizado correctamente el registro, en breve será redirigido, si no es redirigido de <a href="int_new.php?doc=mant_int_ot&id=<?=$idSolicitud; ?>">clic aquí</a>.</h5>
            </div>
        </div>
        <SCRIPT LANGUAGE="JavaScript">
        alert("Se ha creado correctamente el registro.");
        window.location.href= "int_new.php?doc=mant_int_ot&id=<?=$idSolicitud; ?>";
        </script>
        <?
    }

    
    
    /*
    *	TRAEMOS LOS RECURSOS
    DATE_SUB( observacion.fechaCreacion, INTERVAL 0 MINUTE )
    */
    $sql = "SELECT 
                veh_solicitudes_cot_pro.*
                ";
    $sql.=" FROM veh_solicitudes_cot_pro ";
    $sql.=" WHERE veh_solicitudes_cot_pro.idSolicitud = '".$idSolicitud."'";
    $sql.=" AND cot_pro_estado = 1";
    $sql.=" ORDER BY veh_solicitudes_cot_pro.fechaCreacion ASC";
    //
    $PSN1->query($sql);
    $numero=$PSN1->num_rows();
    //
    ?><div class="container-fluid">
       <!-- <div class="row">
            <h3 class="text-center well">.OBSERVACIONES.</h3>
        </div> //-->

    <div class="row">
    <table border="0" cellspacing="12" cellpadding="12"  align="center" class="table table-bordered" style="font-size:12px">
        <thead>
            <th style="text-align:center">No.</th>
            <th style="text-align:center">Descripción</th>
            <th style="text-align:center">Estado<br />OT</th>
            <th style="text-align:center">Fecha aprobación</th>
            <th style="text-align:center">Fecha entrega<br />programada</th>
            <th style="text-align:center">Fecha entrega<br />real</th>
            <th style="text-align:center">Valor total<br />(IVA Incluido)</th>
            <?
            /*
            //El cliente NO ve esta parte
            if($_SESSION["perfil"] != 3 && $_SESSION["perfil"] != 160){
                ?><th style="text-align:center">Estado<br />factura proveeedor</th><?
            }
            //El proveedor no ve lo que se hace con el cliente
            if($_SESSION["perfil"] != 4){
                ?><th style="text-align:center">Estado<br />factura cliente</th><?
            }
            */
            ?>
            <th style="text-align:center">Opciones</th>
        </thead>
    <tbody>
    <?
    if($numero > 0)
    {
        $contador = 0;
        $temp_impresionOT = 0;
        while($PSN1->next_record())
        {
        
            $idLineaCot = $PSN1->f('id');
            $cot_pro_estado = $PSN1->f('cot_pro_estado');
            $observacion = $PSN1->f('observacion');
            $cantidad = intval($PSN1->f('cantidad'));
            $valorUnitario = intval($PSN1->f('valorUnitario'));
            $ivaPorcentaje = intval($PSN1->f('ivaPorcentaje'));
            $ivaValor = intval($PSN1->f('ivaValor'));
            $valor = $PSN1->f('valor');
            $fechaVencimiento = $PSN1->f('fechaVencimiento');
            $archivo = $PSN1->f('archivo');
            $fechaCreacion = $PSN1->f('fechaCreacion');
            $usuarioCreacion = $PSN1->f('usuarioCreacion');
            //
            //
            $ot_estado = $PSN1->f('ot_estado');
            $ot_fechaEntrega = $PSN1->f('ot_fechaEntrega');
            $ot_fechaEntregaReal = $PSN1->f('ot_fechaEntregaReal');
            //
            $pago_pro_estado = $PSN1->f('pago_pro_estado');
            $pago_pro_archivo = $PSN1->f('pago_pro_archivo');
            //
            $pago_cli_estado = $PSN1->f('pago_cli_estado');
            $pago_cli_fechaFactura = $PSN1->f('pago_cli_fechaFactura');
            $pago_cli_observaciones = $PSN1->f('pago_cli_observaciones');
            $pago_cli_fechaPagoReal = $PSN1->f('pago_cli_fechaPagoReal');
            $pago_cli_fechaPago = $PSN1->f('pago_cli_fechaPago');
            $pago_cli_archivo = $PSN1->f('pago_cli_archivo');
            
            //
            $aprobacion_observaciones = $PSN1->f('aprobacion_observaciones');
            $aprobacion_fecha = $PSN1->f('aprobacion_fecha');
            //
            $obs_num = 0;
            $sql = "SELECT COUNT(veh_solicitudes_cot_pro_ot_obs.id) as conteo";
            $sql.=" FROM veh_solicitudes_cot_pro_ot_obs ";
            $sql.=" WHERE veh_solicitudes_cot_pro_ot_obs.idLinea = '".$idLineaCot."'";
            $PSN2->query($sql);
            $numero=$PSN2->num_rows();
            if($numero > 0)
            {
                if($PSN2->next_record()){
                    $obs_num = $PSN2->f('conteo');
                }
            }

            $ot_estado_nom = "";
            switch($ot_estado){
                case 0:
                    $ot_estado_nom = "Aprobada";
                    break;
                case 1:
                    $ot_estado_nom = "En proceso";
                    break;
                case 10:
                    $ot_estado_nom = "Terminado";
                    break;
                case 20:
                    $ot_estado_nom = "Entregado";
                    break;
                case 90:
                    $ot_estado_nom = "Cancelado";
                    break;
                default:
                    $ot_estado_nom = "SIN DEFINIR ".$ot_estado;
                    break;
            }            
            
            $pago_pro_estadoNom = "";
            switch($pago_pro_estado){
                case 0:
                    $pago_pro_estadoNom = "Sin facturar";
                    break;
                case 10:
                    $pago_pro_estadoNom = "Facturada";
                    break;
                case 20:
                    $pago_pro_estadoNom = "En proceso de pago";
                    break;
                case 30:
                    $pago_pro_estadoNom = "Pagada";
                    break;
                case 90:
                    $pago_pro_estadoNom = "Reversado";
                    break;
                default:
                    $pago_pro_estadoNom = "SIN DEFINIR ".$ot_estado;
                    break;
            }

            $pago_cli_estadoNom = "";
            switch($pago_cli_estado){
                case 0:
                    $pago_cli_estadoNom = "Sin facturar";
                    break;
                case 10:
                    $pago_cli_estadoNom = "Facturada";
                    break;
                case 20:
                    $pago_cli_estadoNom= "En proceso de pago";
                    break;
                case 30:
                    $pago_cli_estadoNom = "Pagada";
                    break;
                case 90:
                    $pago_cli_estadoNom = "Reversado";
                    break;
                default:
                    $pago_cli_estadoNom = "SIN DEFINIR ".$ot_estado;
                    break;
            }
            
            if($ot_estado == 10 ||$ot_estado == 20){
                if($temp_impresionOT == 0){
                    $temp_impresionOT = 1;
                }
            }else if($ot_estado != 90){
                $temp_impresionOT = 99;
            }
            //
            if($ot_estado == 20){
                if($temp_facturarOT == 0){
                    $temp_facturarOT = 1;
                }
            }else if($ot_estado != 90){
                $temp_facturarOT = 99;
            }

            ?><tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>> 
                <td><?=$contador+1; ?></td>
                <td><?
                    echo $observacion;
                    ?><br />
                    <i>
                    <strong>Cantidad: </strong><?=$cantidad; ?> 
                    <strong>Valor unitario: </strong>$<?=number_format($valorUnitario, 0, "," , "."); ?> 
                    <strong>IVA: </strong><?=$ivaPorcentaje; ?> %
                    <strong>Valor IVA: </strong>$<?=number_format($ivaValor, 0, "," , "."); ?> 
                    </i>
                    <?
                    if($archivo != "")
                    {
                        ?><br /><a href="descarga_mant.php?&archivo=<?=$archivo; ?>" target="_blank"><img src="images/adjunto.jpg" align="middle" border="0"></a><i><strong>Contiene archivo adjunto:</strong> <a href="descarga_mant.php?&archivo=<?=$archivo; ?>">Bajar Archivo</a></i><?
                    }

                    if($cot_pro_estado == 1){
                        ?><div class="alert alert-success">
                          <strong>Aprobada!</strong> el <strong><?=$aprobacion_fecha; ?></strong>: <?=$aprobacion_observaciones; ?>.
                        </div><?
                    }
                    else if($cot_pro_estado == 90){
                        ?><div class="alert alert-danger">
                          <strong>Rechazada!</strong> el <strong><?=$aprobacion_fecha; ?></strong>: <?=$aprobacion_observaciones; ?>.
                        </div><?
                    }
                    else if($cot_pro_estado == 0 && strtotime("now") > strtotime($fechaVencimiento." 23:59:59")){
                        ?><div class="alert alert-warning">
                          <strong>Vencida!</strong> se pasó la fecha de vigencia.
                        </div><?
                    }
                ?></td>

                <td align="center"><?
                
                echo $ot_estado_nom;
                
            
                ?></td>

                <td align="center"><?=$aprobacion_fecha; ?></td>
                <td align="center"><?=$ot_fechaEntrega; ?></td>
                <td align="center"><?=$ot_fechaEntregaReal; ?></td>
                <td align="right">$<?=number_format($valor, 0, "," , ".");?></td>
                <?
                //El cliente NO ve esta parte
                /*
                if($_SESSION["perfil"] != 3 && $_SESSION["perfil"] != 160){
                    ?><td align="center"><?=$pago_pro_estadoNom; 
                    //
                    if($pago_pro_archivo != "")
                    {
                        ?><br /><a href="descarga_mant.php?&archivo=<?=$pago_pro_archivo; ?>" target="_blank"><img src="images/adjunto.jpg" align="middle" border="0"></a><?
                    }                    

                    ?></td><?
                }
                //El proveedor no ve lo que se hace con el cliente
                if($_SESSION["perfil"] != 4){
                    ?><td align="center"><?=$pago_cli_estadoNom; 
                    //
                    if($pago_cli_archivo != "")
                    {
                        ?><br /><a href="descarga_mant.php?&archivo=<?=$pago_cli_archivo; ?>" target="_blank"><img src="images/adjunto.jpg" align="middle" border="0"></a><?
                    }                    

                    ?></td><?
                }
                */
                ?>
                
                <td><center><?
                /*
                *   PERFIL PROVEEDOR
                */
                if($_SESSION["perfil"] == 4){
                    if($ot_estado < 10){
                        ?>
                        <a href="int_new.php?doc=mant_int_ot&id=<?=$idSolicitud; ?>&idLinea=<?=$idLineaCot; ?>" class="btn btn-primary" style="color: #fff;margin-top:3px">Editar</a>
                        <?
                    }
                    /*
                    else if($ot_estado == 20 && $pago_pro_estado == 0){
                        ?> 
                        <a href="int_new.php?doc=mant_int_ot&id=<?=$idSolicitud; ?>&idLinea=<?=$idLineaCot; ?>&fact=1" class="btn btn-info" style="color: #fff;margin-top:3px">Facturar</a>
                        <?
                    }
                    else if($ot_estado == 20 && $pago_pro_estado > 0){
                        ?> 
                        <a href="int_new.php?doc=mant_int_ot&id=<?=$idSolicitud; ?>&idLinea=<?=$idLineaCot; ?>&fact=1" class="btn btn-info" style="color: #fff;margin-top:3px">Facturación</a>
                        <?
                    }*/
                }
                /*
                *   PERFIL CLIENTE Y USUARIO CLIENTE
                */
                else if(($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 160)){
                    /*if($ot_estado == 10){
                        ?>
                        <a href="int_new.php?doc=mant_int_ot&id=<?=$idSolicitud; ?>&idLinea=<?=$idLineaCot; ?>" class="btn btn-primary" style="color: #fff;margin-top:3px">Recibir</a>
                        <?                        
                    }
                    
                    if($pago_cli_estado > 0){
                        ?><a href="int_new.php?doc=mant_int_ot&id=<?=$idSolicitud; ?>&idLinea=<?=$idLineaCot; ?>&fact_cli=1" class="btn btn-warning" style="color: #fff;margin-top:3px">Facturación</a><?
                    }*/
                }
                /*
                *   PERFILES INTERNOS
                */
                else if($_SESSION["perfil"] != 3 && $_SESSION["perfil"] != 4 && $_SESSION["perfil"] != 160){
                    ?>
                    <a href="int_new.php?doc=mant_int_ot&id=<?=$idSolicitud; ?>&idLinea=<?=$idLineaCot; ?>" class="btn btn-primary" style="color: #fff;margin-top:3px">Editar OT</a>
                    <?
                    /*if($pago_pro_estado > 0){
                        ?>
                        <br />
                        <a href="int_new.php?doc=mant_int_ot&id=<?=$idSolicitud; ?>&idLinea=<?=$idLineaCot; ?>&fact=1" class="btn btn-info" style="color: #fff;margin-top:3px">Fact. Proveedor</a>
                        <?
                    }
                    if($ot_estado == 20){
                        ?>
                        <br />
                        <a href="int_new.php?doc=mant_int_ot&id=<?=$idSolicitud; ?>&idLinea=<?=$idLineaCot; ?>&fact_cli=1" class="btn btn-warning" style="color: #fff;margin-top:3px">Fact. Cliente</a><br />
                        <?                        
                    }*/
                }
                ?>
                <a href="int_new.php?doc=mant_int_ot&id=<?=$idSolicitud; ?>&idLinea=<?=$idLineaCot; ?>&obs=1" class="btn btn-info" style="color: #fff;margin-top:3px">Obs (<?=intval($obs_num); ?>)</a>
                </center></td>
            </tr>
            <?
            $contador++;
        }
    }
    else
    {
        ?>
        <tr><td colspan="8">Sin lineas de OT.</td></tr>
        <?
    }
    ?>
        </tbody>
    </table>
    <?
    if($temp_impresionOT == 1 && $_SESSION["perfil"] == 4){
        ?><center><a href="imprimir.php?doc=mant_int_ot_imp&id=<?=$idSolicitud; ?>" class="btn btn-primary" style="color: #fff;margin-top:3px" target="_blank">Impresi&oacute;n OT</a></center><?
    }
    
    if($temp_facturarOT == 1 && ($_SESSION["perfil"] == 4 || ($_SESSION["perfil"] != 3 && $_SESSION["perfil"] != 160))){
        //        
        $sql = "SELECT veh_solicitudes_cot.factura_fecha, veh_solicitudes_cot.factura_obs, veh_solicitudes_cot.factura_ext";
        $sql.=" FROM veh_solicitudes_cot ";
        $sql.=" WHERE veh_solicitudes_cot.id = '".$idSolicitud."' ";
        //
        $PSN1->query($sql);
        $pedirFactura = 1;
        if($PSN1->num_rows() > 0)
        {
            if($PSN1->next_record())
            {
                //
                $factura_fecha = $PSN1->f('factura_fecha');
                $factura_obs = $PSN1->f('factura_obs');
                $factura_ext = $PSN1->f('factura_ext');
                //
                if($factura_ext != ""){
                    $pedirFactura = 0;
                    ?>
                    <div class="row">
                        <h5 class="text-center alert alert-warning">.FACTURA OT.</h5>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="factura_fecha"><strong>Fecha de la factura</strong></label>
                        <input type="date" name="factura_fecha" id="factura_fecha" value="<?=$factura_fecha; ?>" class="form-control" readonly /></div>

                        <label class="form-label" for="factura_obs"><strong>Observaciones</strong></label>
                        <textarea name="factura_obs" id="factura_obs" class="form-control" readonly><?=$factura_obs; ?></textarea></div>


                        <label class="form-label" for="factura_archivo"><strong>Archivo de la factura</strong></label>
                        <?if($factura_ext != "")
                        {
                            ?><br /><a href="descarga_mant.php?&archivo=fact<?=$idSolicitud.".".$factura_ext; ?>" target="_blank"><img src="images/adjunto.jpg" align="middle" border="0"></a><i> <a href="descarga_mant.php?&archivo=fact<?=$idSolicitud.".".$factura_ext; ?>" target="_blank">Bajar Archivo</a></i><?
                        }?></div>
                    </div><?
                }
            }
        }
        //        
        //
        if($pedirFactura == 1 && $_SESSION["perfil"] == 4){
            $factura_fecha = date("Y-m-d");
            ?>
            <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
            <div class="row">
                <h5 class="text-center alert alert-warning">.FACTURAR OT.</h5>
            </div>

            <div class="form-group">
                <label class="form-label" for="factura_fecha"><strong>Fecha de la factura</strong></label>
                <input type="date" name="factura_fecha" id="factura_fecha" value="<?=$factura_fecha; ?>" class="form-control" required /></div>

                <label class="form-label" for="factura_obs"><strong>Observaciones</strong></label>
                <textarea name="factura_obs" id="factura_obs" class="form-control"><?=$factura_obs; ?></textarea></div>


                <label class="form-label" for="factura_archivo"><strong>Adjunte archivo de la factura</strong></label>
                <input name="factura_archivo" type="file" id="factura_archivo"  class="form-control" required />
            </div>                      

            <div class="row">
                <center><input type="submit" name="button" value="Guardar cambios" class="btn btn-success"></center></div>
            </div>

            <input type="hidden" name="funcion" id="funcion" value="" />
            </form>

            <script language="javascript">
            function generarForm(){
                if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?"))
                {
                    if(document.getElementById('factura_fecha').value != "")
                    {
                        //Todo bien
                        document.getElementById('funcion').value = "facturarOT";
                    }
                    else
                    {
                        alert("La informacion es primordial para brindarle un excelente servicio, por favor digite todos los campos incluida la fecha de factura");
                        return false;
                    }
                }else{
                    return false;
                }
                return true;
            }
            //
            function init(){
                document.getElementById('form1').onsubmit = function(){
                    return generarForm();
                }
            }
            //
            window.onload = function(){
                init();
            }
            </script><?
        }
    }    
    ?>
        
    </div>
<?
}
else{
    ?><a href="int_new.php?doc=mant_int_ot&id=<?=$idSolicitud; ?>" class="btn btn-danger">Regresar</a><?

}

/*
*   OBSERVACIONES DISPONIBLES PARA TODOS LOS USUARIOS.
*/
if(isset($_REQUEST["idLinea"]) && soloNumeros($_REQUEST["idLinea"]) != "" && soloNumeros($_REQUEST["idLinea"]) != "0" && isset($_REQUEST["obs"]) && soloNumeros($_REQUEST["obs"]) == 1){
    //
    $idLinea = soloNumeros($_REQUEST["idLinea"]);
    //
    $sql = "SELECT 
                veh_solicitudes_cot_pro.* ";
    $sql.=" FROM veh_solicitudes_cot_pro ";
    $sql.=" WHERE veh_solicitudes_cot_pro.idSolicitud = '".$idSolicitud."'";
        $sql.=" AND veh_solicitudes_cot_pro.id = '".$idLinea."'";
    $sql.=" ORDER BY veh_solicitudes_cot_pro.fechaCreacion ASC";
    //
    $PSN1->query($sql);
    if($PSN1->num_rows() > 0)
    {
        $contador = 0;
        if($PSN1->next_record())
        {
            $cot_pro_estado = $PSN1->f('cot_pro_estado');
            $observacion = $PSN1->f('observacion');
            $cantidad = intval($PSN1->f('cantidad'));
            $valorUnitario = intval($PSN1->f('valorUnitario'));
            $ivaPorcentaje = intval($PSN1->f('ivaPorcentaje'));
            $ivaValor = intval($PSN1->f('ivaValor'));
            $valor = $PSN1->f('valor');
            $fechaVencimiento = $PSN1->f('fechaVencimiento');
            $archivo = $PSN1->f('archivo');
            $fechaCreacion = $PSN1->f('fechaCreacion');
            $usuarioCreacion = $PSN1->f('usuarioCreacion');
            //
            $ot_estado = $PSN1->f('ot_estado');
            $ot_fechaEntrega = $PSN1->f('ot_fechaEntrega');
            $ot_fechaPago = $PSN1->f('ot_fechaPago');
            //
            //
            $aprobacion_observaciones = $PSN1->f('aprobacion_observaciones');
            $aprobacion_fecha = $PSN1->f('aprobacion_fecha');
            //            
            ?>
                <div class="panel panel-info">
                  <div class="panel-heading text-center">LINEA DE OT</div>
                  <div class="panel-body">
                    <form/ method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">

                    <div class="row">
                        <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA COTIZACIÓN.</h5>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="observaciones"><strong>Cotización</strong></label>
                        <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$observacion; ?></textarea>
                    </div>

                <div class="form-group">
                    <label class="control-label col-sm-2 text-center" for="cantidad"><center><strong>Cantidad</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valorUnitario"><center><strong>Valor UNITARIO</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaPorcentaje"><center><strong>% de IVA</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaValor"><center><strong>Valor IVA</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valor"><center><strong>Valor total (IVA incluido)</strong></center></label>
              </div>
                        
                    <div class="form-group">
                        <input type="number" name="cantidad" id="cantidad" class="form-control" value="<?=$cantidad; ?>" style="text-align: right;"  readonly /></div>
                        
                        <input type="number" name="valorUnitario" id="valorUnitario" class="form-control" value="<?=$valorUnitario; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaPorcentaje" id="ivaPorcentaje" class="form-control" value="<?=$ivaPorcentaje; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaValor" id="ivaValor" class="form-control" value="<?=$ivaValor; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="valor" id="valor" class="form-control" value="<?=$valor; ?>" style="text-align: right;"  readonly /></div>
                    </div>
                      

                    <div class="form-group">
                        <label class="form-label" for="fechaVencimiento"><strong>Fecha vigencia</strong></label>
                        <input type="date" name="fechaVencimiento" id="fechaVencimiento" value="<?=$fechaVencimiento; ?>" class="form-control"  readonly /></div>

                        <label class="form-label" for="archivo"><strong>Adjunto</strong></label>
                        <? if($archivo != "")
                        {
                            ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$archivo; ?>">Bajar Archivo</a></label></i><?
                        }
                        else{
                            echo "Sin archivo.";
                        }
                            ?></div>
                    </div>
                      
                    <div class="row">
                        <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA APROBACIÓN.</h5>
                    </div>
                      
                    <div class="form-group">
                        <label class="form-label" for="observaciones"><strong>Aprobación</strong></label>
                        <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$aprobacion_observaciones; ?></textarea></div>
                        
                        <label class="form-label" for="aprobacion_fecha"><strong>Fecha aprobación</strong></label>
                        <input type="date" name="aprobacion_fecha" id="aprobacion_fecha" value="<?=$aprobacion_fecha; ?>" class="form-control"  readonly /></div>
                    </div>
                      
                    </form>
                    
                    <div class="row">
                        <h5 class="text-center alert alert-info">.OBSERVACIONES.</h5>
                    </div>
                    
                    <?
                    include("mant_int_ot_obs.php");
                    ?>

                </div>
                </div>
                <a name="final">&nbsp;</a><? 
        }
    }
}
/*
*   USUARIO PROVEEDOR.
*/
else if(isset($_REQUEST["idLinea"]) && soloNumeros($_REQUEST["idLinea"]) != "" && soloNumeros($_REQUEST["idLinea"]) != "0" && $_SESSION["perfil"] == 4 && $varExito != 1){
    /*
    *   USUARIO PROVEEDOR - FACTURAR
    */
    if(isset($_REQUEST["fact"])){
        //
        $idLinea = soloNumeros($_REQUEST["idLinea"]);
        //
        $sql = "SELECT 
                    veh_solicitudes_cot_pro.* ";
        $sql.=" FROM veh_solicitudes_cot_pro ";
        $sql.=" WHERE veh_solicitudes_cot_pro.idSolicitud = '".$idSolicitud."'";
            $sql.=" AND veh_solicitudes_cot_pro.id = '".$idLinea."'";
                $sql.=" AND veh_solicitudes_cot_pro.ot_estado = 20";
        $sql.=" ORDER BY veh_solicitudes_cot_pro.fechaCreacion ASC";
        //
        $PSN1->query($sql);
        if($PSN1->num_rows() > 0)
        {
            $contador = 0;
            if($PSN1->next_record())
            {
                $cot_pro_estado = $PSN1->f('cot_pro_estado');
                $observacion = $PSN1->f('observacion');
                $cantidad = intval($PSN1->f('cantidad'));
                $valorUnitario = intval($PSN1->f('valorUnitario'));
                $ivaPorcentaje = intval($PSN1->f('ivaPorcentaje'));
                $ivaValor = intval($PSN1->f('ivaValor'));
                $valor = $PSN1->f('valor');
                $fechaVencimiento = $PSN1->f('fechaVencimiento');
                $archivo = $PSN1->f('archivo');
                $fechaCreacion = $PSN1->f('fechaCreacion');
                $usuarioCreacion = $PSN1->f('usuarioCreacion');
                //
                $ot_estado = $PSN1->f('ot_estado');
                $ot_fechaEntrega = $PSN1->f('ot_fechaEntrega');
                $ot_fechaEntregaReal = $PSN1->f('ot_fechaEntregaReal');
                $ot_fechaPago = $PSN1->f('ot_fechaPago');
                //
                $aprobacion_observaciones = $PSN1->f('aprobacion_observaciones');
                $aprobacion_fecha = $PSN1->f('aprobacion_fecha');
                //
                $pago_pro_estado = $PSN1->f('pago_pro_estado');
                $pago_pro_fechaFactura = $PSN1->f('pago_pro_fechaFactura');
                $pago_pro_observaciones = $PSN1->f('pago_pro_observaciones');
                $pago_pro_fechaPagoReal = $PSN1->f('pago_pro_fechaPagoReal');
                $pago_pro_fechaPago = $PSN1->f('pago_pro_fechaPago');
                $pago_pro_archivo = $PSN1->f('pago_pro_archivo');       

                if($pago_pro_fechaFactura == "0000-00-00"){
                    $pago_pro_fechaFactura = date("Y-m-d");
                }
                //            
                ?>
                    <div class="panel panel-info">
                      <div class="panel-heading text-center">LINEA DE OT</div>
                      <div class="panel-body">
                        <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
                        <input type="hidden" name="id" value="<?=$idSolicitud; ?>" />
                        <input type="hidden" name="idLineaOT" value="<?=$idLinea; ?>" />

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA COTIZACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Cotización</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$observacion; ?></textarea>
                        </div>

                <div class="form-group">
                    <label class="control-label col-sm-2 text-center" for="cantidad"><center><strong>Cantidad</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valorUnitario"><center><strong>Valor UNITARIO</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaPorcentaje"><center><strong>% de IVA</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaValor"><center><strong>Valor IVA</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valor"><center><strong>Valor total (IVA incluido)</strong></center></label>
              </div>
                        
                    <div class="form-group">
                        <input type="number" name="cantidad" id="cantidad" class="form-control" value="<?=$cantidad; ?>" style="text-align: right;"  readonly /></div>
                        
                        <input type="number" name="valorUnitario" id="valorUnitario" class="form-control" value="<?=$valorUnitario; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaPorcentaje" id="ivaPorcentaje" class="form-control" value="<?=$ivaPorcentaje; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaValor" id="ivaValor" class="form-control" value="<?=$ivaValor; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="valor" id="valor" class="form-control" value="<?=$valor; ?>" style="text-align: right;"  readonly /></div>
                    </div>
                      

                    <div class="form-group">
                            <label class="form-label" for="fechaVencimiento"><strong>Fecha vigencia</strong></label>
                            <input type="date" name="fechaVencimiento" id="fechaVencimiento" value="<?=$fechaVencimiento; ?>" class="form-control"  readonly /></div>

                            <label class="form-label" for="archivo"><strong>Adjunto</strong></label>
                            <? if($archivo != "")
                            {
                                ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$archivo; ?>">Bajar Archivo</a></label></i><?
                            }
                            else{
                                echo "Sin archivo.";
                            }
                                ?></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA APROBACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Aprobación</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$aprobacion_observaciones; ?></textarea></div>

                            <label class="form-label" for="aprobacion_fecha"><strong>Fecha aprobación</strong></label>
                            <input type="date" name="aprobacion_fecha" id="aprobacion_fecha" value="<?=$aprobacion_fecha; ?>" class="form-control"  readonly /></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA OT.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_fechaEntrega"><strong>Fecha de entrega programada</strong></label>
                            <input type="date" name="ot_fechaEntrega" id="ot_fechaEntrega" value="<?=$ot_fechaEntrega; ?>" class="form-control" readonly /></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_fechaEntregaReal"><strong>Fecha de entrega real</strong></label>
                            <input type="date" name="ot_fechaEntregaReal" id="ot_fechaEntregaReal" value="<?=$ot_fechaEntregaReal; ?>" class="form-control" readonly /></div>

                            <label class="form-label" for="ot_estado"><strong>Estado del trabajo:</strong></label>
                            <select name="ot_estado" id="ot_estado" class="form-control" disabled>
                                    <option value="20" <? if($ot_estado == 10){ ?>selected="selected"<? } ?>>Entregado</option>
                            </select>
                        </div>

                        <?
                        if($pago_pro_estado == 0)
                        { 
                            ?>
                            <div class="row">
                                <h5 class="text-center alert alert-warning">.FACTURAR OT.</h5>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="pago_pro_fechaFactura"><strong>Fecha de la factura</strong></label>
                                <input type="date" name="pago_pro_fechaFactura" id="pago_pro_fechaFactura" value="<?=$pago_pro_fechaFactura; ?>" class="form-control" required /></div>

                                <label class="form-label" for="pago_pro_observaciones"><strong>Observaciones</strong></label>
                                <textarea name="pago_pro_observaciones" id="pago_pro_observaciones" class="form-control"><?=$pago_pro_observaciones; ?></textarea></div>


                                <label class="form-label" for="pago_pro_archivo"><strong>Adjunte archivo de la factura</strong></label>
                                <input name="pago_pro_archivo" type="file" id="pago_pro_archivo"  class="form-control" required />
                            </div>                      

                            <div class="row">
                                <center><input type="submit" name="button" value="Guardar cambios" class="btn btn-success"></center></div>
                            </div>
                            <?
                        }
                        else{
                            /*
                            *   SOLO VISUALIZAR
                            */
                            ?>
                            <div class="row">
                                <h5 class="text-center alert alert-info">.OT - FACTURACIÓN DEL PROVEEDOR.</h5>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="pago_pro_fechaFactura"><strong>Fecha de la factura</strong></label>
                                <input type="date" name="pago_pro_fechaFactura" id="pago_pro_fechaFactura" value="<?=$pago_pro_fechaFactura; ?>" class="form-control" readonly /></div>

                                <label class="form-label" for="pago_pro_observaciones"><strong>Observaciones</strong></label>
                                <textarea name="pago_pro_observaciones" id="pago_pro_observaciones" class="form-control" readonly><?=$pago_pro_observaciones; ?></textarea></div>


                                <label class="form-label" for="pago_pro_archivo"><strong>Archivo adjunto de la factura</strong></label>
                                <? if($pago_pro_archivo != "")
                                {
                                    ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$pago_pro_archivo; ?>">Bajar Archivo</a></label></i><?
                                }
                                else{
                                    echo "Sin archivo.";
                                }
                                ?></div>
                            </div>                      

                            <div class="form-group">
                                <label class="form-label" for="pago_pro_fechaPago"><strong>Fecha de pago programada</strong></label>
                                <input type="date" name="pago_pro_fechaPago" id="pago_pro_fechaPago" value="<?=$pago_pro_fechaPago; ?>" class="form-control" readonly /></div>
                                
                                <label class="form-label" for="pago_pro_fechaPagoReal"><strong>Fecha de pago real</strong></label>
                                <input type="date" name="pago_pro_fechaPagoReal" id="pago_pro_fechaPagoReal" value="<?=$pago_pro_fechaPagoReal; ?>" class="form-control" readonly /></div>
                                
                                
                                <label class="form-label" for="pago_pro_estado"><strong>Estado factura</strong></label>
                                <select name="pago_pro_estado" id="pago_pro_estado" class="form-control" disabled>
                                    <option value="0" <? if($pago_pro_estado == 0){ ?>selected="selected"<? } ?>>Listo para facturar</option>
                                    <option value="10" <? if($pago_pro_estado == 10){ ?>selected="selected"<? } ?>>Facturada</option>
                                    <option value="20" <? if($pago_pro_estado == 20){ ?>selected="selected"<? } ?>>En proceso de pago</option>
                                    <option value="30" <? if($pago_pro_estado == 30){ ?>selected="selected"<? } ?>>Pagada</option>
                                    <option value="90" <? if($pago_pro_estado == 90){ ?>selected="selected"<? } ?>>Reversada</option>
                                </select>
                                
                            </div>
                            <?
                        }
                    ?>
                    <input type="hidden" name="funcion" id="funcion" value="" />
                    </form>

                    </div>
                    </div>

                    <script language="javascript">
                    <? if($pago_pro_estado == 0){ ?>
                        function generarForm(){
                                if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?"))
                                {
                                    if(document.getElementById('pago_pro_fechaFactura').value != "")
                                    {
                                        //Todo bien
                                        document.getElementById('funcion').value = "pago_prov_actualizarOT";
                                    }
                                    else
                                    {
                                        alert("La informacion es primordial para brindarle un excelente servicio, por favor digite todos los campos incluida la fecha de entrega");
                                        return false;
                                    }
                                }else{
                                    return false;
                                }
                                return true;
                        }
                        //
                        function init(){
                            document.getElementById('form1').onsubmit = function(){
                                return generarForm();
                            }
                        }

                        window.onload = function(){
                            init();
                        }
                    <? } ?>

                    </script>

                    <? 
            }
        }
        else{
            echo "Estado de OT no valido.";
        }
        //FIN DE LA LOGICA DENTRO DEL IF DE FACTURACIÓN DEL PROVEEDOR
    }
    /*
    *   USUARIO PROVEEDOR - AVANCE DE ESTADO, PRORGAMAR FECHA DE ENTREGA, ETC.
    */
    else{
        //
        $idLinea = soloNumeros($_REQUEST["idLinea"]);
        //
        $sql = "SELECT 
                    veh_solicitudes_cot_pro.* ";
        $sql.=" FROM veh_solicitudes_cot_pro ";
        $sql.=" WHERE veh_solicitudes_cot_pro.idSolicitud = '".$idSolicitud."'";
            $sql.=" AND veh_solicitudes_cot_pro.id = '".$idLinea."'";
                $sql.=" AND veh_solicitudes_cot_pro.ot_estado < 10";
        $sql.=" ORDER BY veh_solicitudes_cot_pro.fechaCreacion ASC";
        //
        $PSN1->query($sql);
        if($PSN1->num_rows() > 0)
        {
            $contador = 0;
            if($PSN1->next_record())
            {
                $cot_pro_estado = $PSN1->f('cot_pro_estado');
                $observacion = $PSN1->f('observacion');
            $cantidad = intval($PSN1->f('cantidad'));
            $valorUnitario = intval($PSN1->f('valorUnitario'));
            $ivaPorcentaje = intval($PSN1->f('ivaPorcentaje'));
            $ivaValor = intval($PSN1->f('ivaValor'));
                
                $valor = $PSN1->f('valor');
                $fechaVencimiento = $PSN1->f('fechaVencimiento');
                $archivo = $PSN1->f('archivo');
                $fechaCreacion = $PSN1->f('fechaCreacion');
                $usuarioCreacion = $PSN1->f('usuarioCreacion');
                //
                $ot_estado = $PSN1->f('ot_estado');
                $ot_fechaEntrega = $PSN1->f('ot_fechaEntrega');
                $ot_fechaPago = $PSN1->f('ot_fechaPago');
                //
                //
                $aprobacion_observaciones = $PSN1->f('aprobacion_observaciones');
                $aprobacion_fecha = $PSN1->f('aprobacion_fecha');
                //            
                ?>
                    <div class="panel panel-info">
                      <div class="panel-heading text-center">LINEA DE OT</div>
                      <div class="panel-body">
                        <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
                        <input type="hidden" name="id" value="<?=$idSolicitud; ?>" />
                        <input type="hidden" name="idLineaOT" value="<?=$idLinea; ?>" />

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA COTIZACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Cotización</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$observacion; ?></textarea>
                        </div>

                <div class="form-group">
                    <label class="control-label col-sm-2 text-center" for="cantidad"><center><strong>Cantidad</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valorUnitario"><center><strong>Valor UNITARIO</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaPorcentaje"><center><strong>% de IVA</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaValor"><center><strong>Valor IVA</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valor"><center><strong>Valor total (IVA incluido)</strong></center></label>
              </div>
                        
                    <div class="form-group">
                        <input type="number" name="cantidad" id="cantidad" class="form-control" value="<?=$cantidad; ?>" style="text-align: right;"  readonly /></div>
                        
                        <input type="number" name="valorUnitario" id="valorUnitario" class="form-control" value="<?=$valorUnitario; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaPorcentaje" id="ivaPorcentaje" class="form-control" value="<?=$ivaPorcentaje; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaValor" id="ivaValor" class="form-control" value="<?=$ivaValor; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="valor" id="valor" class="form-control" value="<?=$valor; ?>" style="text-align: right;"  readonly /></div>
                    </div>
                            
                    <div class="form-group">
                            <label class="form-label" for="fechaVencimiento"><strong>Fecha vigencia</strong></label>
                            <input type="date" name="fechaVencimiento" id="fechaVencimiento" value="<?=$fechaVencimiento; ?>" class="form-control"  readonly /></div>

                            <label class="form-label" for="archivo"><strong>Adjunto</strong></label>
                            <? if($archivo != "")
                            {
                                ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$archivo; ?>">Bajar Archivo</a></label></i><?
                            }
                            else{
                                echo "Sin archivo.";
                            }
                                ?></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA APROBACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Aprobación</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$aprobacion_observaciones; ?></textarea></div>

                            <label class="form-label" for="aprobacion_fecha"><strong>Fecha aprobación</strong></label>
                            <input type="date" name="aprobacion_fecha" id="aprobacion_fecha" value="<?=$aprobacion_fecha; ?>" class="form-control"  readonly /></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-warning">.ACTUALIZAR INFORMACIÓN DE LA OT.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_fechaEntrega"><strong>Fecha de entrega programada</strong></label>
                            <input type="date" name="ot_fechaEntrega" id="ot_fechaEntrega" value="<?=$ot_fechaEntrega; ?>" class="form-control"  <?
                            if($ot_estado > 0){
                                ?>readonly<?
                            }
                            ?> /></div>

                            <label class="form-label" for="ot_estado"><strong>Estado del trabajo:</strong></label>
                            <select name="ot_estado" id="ot_estado" class="form-control">
                                    <option value="1" <? if($ot_estado == 1){ ?>selected="selected"<? } ?>>En proceso</option>
                                    <option value="10" <? if($ot_estado == 10){ ?>selected="selected"<? } ?>>Terminado</option>
                            </select>
                        </div>

                        <div class="row">
                            <center><input type="submit" name="button" value="Guardar cambios" class="btn btn-success"></center></div>
                        </div>

                    <input type="hidden" name="funcion" id="funcion" value="" />
                    </form>

                    </div>
                    </div>

                    <script language="javascript">
                    function generarForm(){
                            if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?"))
                            {
                                if(document.getElementById('ot_fechaEntrega').value != "")
                                {
                                    //Todo bien
                                    document.getElementById('funcion').value = "actualizarOT";
                                }
                                else
                                {
                                    alert("La informacion es primordial para brindarle un excelente servicio, por favor digite todos los campos incluida la fecha de entrega");
                                    return false;
                                }
                            }else{
                                return false;
                            }
                            return true;
                    }
                    //
                    function init(){
                        document.getElementById('form1').onsubmit = function(){
                            return generarForm();
                        }
                    }

                    window.onload = function(){
                        init();
                    }

                    </script>

                    <? 
            }
        }
        else{
            echo "Estado de OT no valido.";
        }
        //FIN DE LA LOGICA DENTRO DEL IF DE PROGRAMAR FECHAS DE ENTREGAS, TERMINAR EL TRABAJO, ETC.
    }
}
/*
*   USUARIO CLIENTE Y USUARIO CLIENTE AUTORIZADO - RECEPCIÓN DE LA ORDEN DE COMPRA PARA PODER FACTURAR
*/
else if(isset($_REQUEST["idLinea"]) && soloNumeros($_REQUEST["idLinea"]) != "" && soloNumeros($_REQUEST["idLinea"]) != "0" && $_SESSION["perfil"] != 4 && ($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 160) && $varExito != 1){
    
    if(isset($_REQUEST["fact_cli"])){
        //
        $idLinea = soloNumeros($_REQUEST["idLinea"]);
        //
        $sql = "SELECT 
                    veh_solicitudes_cot_pro.* ";
        $sql.=" FROM veh_solicitudes_cot_pro ";
        $sql.=" WHERE veh_solicitudes_cot_pro.idSolicitud = '".$idSolicitud."'";
            $sql.=" AND veh_solicitudes_cot_pro.id = '".$idLinea."'";
            $sql.=" AND veh_solicitudes_cot_pro.ot_estado = 20";
        $sql.=" ORDER BY veh_solicitudes_cot_pro.fechaCreacion ASC";
        //
        $PSN1->query($sql);
        if($PSN1->num_rows() > 0)
        {
            $contador = 0;
            if($PSN1->next_record())
            {
                $cot_pro_estado = $PSN1->f('cot_pro_estado');
                $observacion = $PSN1->f('observacion');
                $cantidad = intval($PSN1->f('cantidad'));
                $valorUnitario = intval($PSN1->f('valorUnitario'));
                $ivaPorcentaje = intval($PSN1->f('ivaPorcentaje'));
                $ivaValor = intval($PSN1->f('ivaValor'));
                $valor = $PSN1->f('valor');
                $fechaVencimiento = $PSN1->f('fechaVencimiento');
                $archivo = $PSN1->f('archivo');
                $fechaCreacion = $PSN1->f('fechaCreacion');
                $usuarioCreacion = $PSN1->f('usuarioCreacion');
                //
                $ot_estado = $PSN1->f('ot_estado');
                $ot_fechaEntrega = $PSN1->f('ot_fechaEntrega');
                $ot_fechaEntregaReal = $PSN1->f('ot_fechaEntregaReal');
                $ot_fechaPago = $PSN1->f('ot_fechaPago');
                //
                $aprobacion_observaciones = $PSN1->f('aprobacion_observaciones');
                $aprobacion_fecha = $PSN1->f('aprobacion_fecha');
                //
                $pago_pro_estado = $PSN1->f('pago_pro_estado');
                $pago_pro_fechaFactura = $PSN1->f('pago_pro_fechaFactura');
                $pago_pro_observaciones = $PSN1->f('pago_pro_observaciones');
                $pago_pro_fechaPagoReal = $PSN1->f('pago_pro_fechaPagoReal');
                $pago_pro_fechaPago = $PSN1->f('pago_pro_fechaPago');
                $pago_pro_archivo = $PSN1->f('pago_pro_archivo');       
                //
                $pago_cli_estado = $PSN1->f('pago_cli_estado');
                $pago_cli_fechaFactura = $PSN1->f('pago_cli_fechaFactura');
                $pago_cli_observaciones = $PSN1->f('pago_cli_observaciones');
                $pago_cli_fechaPagoReal = $PSN1->f('pago_cli_fechaPagoReal');
                $pago_cli_fechaPago = $PSN1->f('pago_cli_fechaPago');
                $pago_cli_archivo = $PSN1->f('pago_cli_archivo');
                //
                if($pago_pro_fechaFactura == "0000-00-00"){
                    $pago_pro_fechaFactura = date("Y-m-d");
                }
                //
                ?>
                    <div class="panel panel-info">
                      <div class="panel-heading text-center">LINEA DE OT</div>
                      <div class="panel-body">
                        <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
                        <input type="hidden" name="id" value="<?=$idSolicitud; ?>" />
                        <input type="hidden" name="idLineaOT" value="<?=$idLinea; ?>" />

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA COTIZACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Cotización</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$observacion; ?></textarea>
                        </div>

                <div class="form-group">
                    <label class="control-label col-sm-2 text-center" for="cantidad"><center><strong>Cantidad</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valorUnitario"><center><strong>Valor UNITARIO</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaPorcentaje"><center><strong>% de IVA</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaValor"><center><strong>Valor IVA</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valor"><center><strong>Valor total (IVA incluido)</strong></center></label>
              </div>
                        
                    <div class="form-group">
                        <input type="number" name="cantidad" id="cantidad" class="form-control" value="<?=$cantidad; ?>" style="text-align: right;"  readonly /></div>
                        
                        <input type="number" name="valorUnitario" id="valorUnitario" class="form-control" value="<?=$valorUnitario; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaPorcentaje" id="ivaPorcentaje" class="form-control" value="<?=$ivaPorcentaje; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaValor" id="ivaValor" class="form-control" value="<?=$ivaValor; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="valor" id="valor" class="form-control" value="<?=$valor; ?>" style="text-align: right;"  readonly /></div>
                    </div>
                            
                    <div class="form-group">
                            <label class="form-label" for="fechaVencimiento"><strong>Fecha vigencia</strong></label>
                            <input type="date" name="fechaVencimiento" id="fechaVencimiento" value="<?=$fechaVencimiento; ?>" class="form-control"  readonly /></div>

                            <label class="form-label" for="archivo"><strong>Adjunto</strong></label>
                            <? if($archivo != "")
                            {
                                ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$archivo; ?>">Bajar Archivo</a></label></i><?
                            }
                            else{
                                echo "Sin archivo.";
                            }
                                ?></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA APROBACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Aprobación</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$aprobacion_observaciones; ?></textarea></div>

                            <label class="form-label" for="aprobacion_fecha"><strong>Fecha aprobación</strong></label>
                            <input type="date" name="aprobacion_fecha" id="aprobacion_fecha" value="<?=$aprobacion_fecha; ?>" class="form-control"  readonly /></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA OT.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_fechaEntrega"><strong>Fecha de entrega programada</strong></label>
                            <input type="date" name="ot_fechaEntrega" id="ot_fechaEntrega" value="<?=$ot_fechaEntrega; ?>" class="form-control" readonly /></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_fechaEntregaReal"><strong>Fecha de entrega real</strong></label>
                            <input type="date" name="ot_fechaEntregaReal" id="ot_fechaEntregaReal" value="<?=$ot_fechaEntregaReal; ?>" class="form-control" readonly /></div>

                            <label class="form-label" for="ot_estado"><strong>Estado del trabajo:</strong></label>
                            <select name="ot_estado" id="ot_estado" class="form-control" disabled>
                                    <option value="20" <? if($ot_estado == 10){ ?>selected="selected"<? } ?>>Entregado</option>
                            </select>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-warning">.OT - FACTURACIÓN AL CLIENTE.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="pago_cli_fechaFactura"><strong>Fecha de la factura</strong></label>
                            <input type="date" name="pago_cli_fechaFactura" id="pago_cli_fechaFactura" value="<?=$pago_cli_fechaFactura; ?>" class="form-control" readonly /></div>

                            <label class="form-label" for="pago_cli_observaciones"><strong>Observaciones</strong></label>
                            <textarea name="pago_cli_observaciones" id="pago_cli_observaciones" class="form-control" readonly><?=$pago_cli_observaciones; ?></textarea></div>


                            <label class="form-label" for="pago_cli_archivo"><strong>Adjunto</strong></label>
                            <? if($pago_cli_archivo != "")
                            {
                                ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$pago_cli_archivo; ?>">Bajar Archivo</a></label></i><?
                            }
                            else{
                                echo "Sin archivo.";
                            }
                            ?></div>
                        </div>                      
                        
                        <?
                        if($pago_cli_estado == 10){
                            ?><div class="form-group">
                                <label class="form-label" for="pago_cli_fechaPago"><strong>Fecha de pago programada</strong></label>
                                <input type="date" name="pago_cli_fechaPago" id="pago_cli_fechaPago" value="<?=$pago_cli_fechaPago; ?>" class="form-control" /></div>


                                <label class="form-label" for="pago_cli_estado"><strong>Estado factura</strong></label>
                                <select name="pago_cli_estado" id="pago_cli_estado" class="form-control">
                                    <option value="20" <? if($pago_cli_estado == 20){ ?>selected="selected"<? } ?>>En proceso de pago</option>
                                </select>
                            </div><? 
                        }
                        else{
                            ?><div class="form-group">
                                <label class="form-label" for="pago_cli_fechaPago"><strong>Fecha de pago programada</strong></label>
                                <input type="date" name="pago_cli_fechaPago" id="pago_cli_fechaPago" value="<?=$pago_cli_fechaPago; ?>" class="form-control" readonly /></div>

                                <label class="form-label" for="pago_cli_fechaPagoReal"><strong>Fecha de pago real</strong></label>
                                <input type="date" name="pago_cli_fechaPagoReal" id="pago_cli_fechaPagoReal" value="<?=$pago_cli_fechaPagoReal; ?>" class="form-control" readonly /></div>


                                <label class="form-label" for="pago_cli_estado"><strong>Estado factura</strong></label>
                                <select name="pago_cli_estado" id="pago_cli_estado" class="form-control" disabled>
                                    <option value="0" <? if($pago_cli_estado == 0){ ?>selected="selected"<? } ?>>Sin facturar</option>
                                    <option value="10" <? if($pago_cli_estado == 10){ ?>selected="selected"<? } ?>>Facturada</option>
                                    <option value="20" <? if($pago_cli_estado == 20){ ?>selected="selected"<? } ?>>En proceso de pago</option>
                                    <option value="30" <? if($pago_cli_estado == 30){ ?>selected="selected"<? } ?>>Pagada</option>
                                    <? /* ?><option value="90" <? if($pago_pro_estado == 90){ ?>selected="selected"<? } ?>>Reversada</option><? */ ?>
                                </select>

                            </div><?
                        }
                        ?>

                        <?
                        if($pago_cli_estado == 10){
                            ?><div class="row">
                                <center><input type="submit" name="button" value="Guardar cambios" class="btn btn-success"></center></div>
                            </div><?
                        }
                        ?>
                        
                    <input type="hidden" name="funcion" id="funcion" value="" />
                    </form>

                    </div>
                    </div>

                    <?
                    if($pago_cli_estado == 10){
                        ?><script language="javascript">
                        function generarForm(){
                                if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?"))
                                {
                                    if(document.getElementById('pago_cli_fechaPago').value != "")
                                    {
                                        //Todo bien
                                        document.getElementById('funcion').value = "pago_cli_actualizarOT";
                                    }
                                    else
                                    {
                                        alert("La informacion es primordial para brindarle un excelente servicio, por favor digite todos los campos incluida la fecha de pago");
                                        return false;
                                    }
                                }else{
                                    return false;
                                }
                                return true;
                        }
                        //
                        function init(){
                            document.getElementById('form1').onsubmit = function(){
                                return generarForm();
                            }
                        }

                        window.onload = function(){
                            init();
                        }
                    </script><?
                    }
            }
        }
        else{
            echo "Estado de OT no valido.";
        }
        //FIN DE LA LOGICA DENTRO DEL IF DE FACTURACIÓN DEL PROVEEDOR        
    }
    else{
        /*
        *   USUARIO DEL CLIENTE O AUTORIZADO
        */
        //
        $idLinea = soloNumeros($_REQUEST["idLinea"]);
        //
        $sql = "SELECT 
                    veh_solicitudes_cot_pro.* ";
        $sql.=" FROM veh_solicitudes_cot_pro ";
        $sql.=" WHERE veh_solicitudes_cot_pro.idSolicitud = '".$idSolicitud."'";
            $sql.=" AND veh_solicitudes_cot_pro.id = '".$idLinea."'";
            $sql.=" AND veh_solicitudes_cot_pro.ot_estado = 10";
        $sql.=" ORDER BY veh_solicitudes_cot_pro.fechaCreacion ASC";
        //
        $PSN1->query($sql);
        if($PSN1->num_rows() > 0)
        {
            $contador = 0;
            if($PSN1->next_record())
            {
                $cot_pro_estado = $PSN1->f('cot_pro_estado');
                $observacion = $PSN1->f('observacion');
                $cantidad = intval($PSN1->f('cantidad'));
                $valorUnitario = intval($PSN1->f('valorUnitario'));
                $ivaPorcentaje = intval($PSN1->f('ivaPorcentaje'));
                $ivaValor = intval($PSN1->f('ivaValor'));
                $valor = $PSN1->f('valor');
                $fechaVencimiento = $PSN1->f('fechaVencimiento');
                $archivo = $PSN1->f('archivo');
                $fechaCreacion = $PSN1->f('fechaCreacion');
                $usuarioCreacion = $PSN1->f('usuarioCreacion');
                //
                $ot_estado = $PSN1->f('ot_estado');
                $ot_fechaEntrega = $PSN1->f('ot_fechaEntrega');
                $ot_fechaEntregaReal = $PSN1->f('ot_fechaEntregaReal');
                if($ot_fechaEntregaReal == "" || $ot_fechaEntregaReal == "0000-00-00"){
                    $ot_fechaEntregaReal = date("Y-m-d");
                }

                $ot_fechaPago = $PSN1->f('ot_fechaPago');
                //
                //
                $aprobacion_observaciones = $PSN1->f('aprobacion_observaciones');
                $aprobacion_fecha = $PSN1->f('aprobacion_fecha');
                //            
                ?>
                    <div class="panel panel-info">
                      <div class="panel-heading text-center">LINEA DE OT</div>
                      <div class="panel-body">
                        <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
                        <input type="hidden" name="id" value="<?=$idSolicitud; ?>" />
                        <input type="hidden" name="idLineaOT" value="<?=$idLinea; ?>" />

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA COTIZACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Cotización</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$observacion; ?></textarea>
                        </div>

         
                <div class="form-group">
                    <label class="control-label col-sm-2 text-center" for="cantidad"><center><strong>Cantidad</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valorUnitario"><center><strong>Valor UNITARIO</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaPorcentaje"><center><strong>% de IVA</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaValor"><center><strong>Valor IVA</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valor"><center><strong>Valor total (IVA incluido)</strong></center></label>
              </div>
                        
                    <div class="form-group">
                        <input type="number" name="cantidad" id="cantidad" class="form-control" value="<?=$cantidad; ?>" style="text-align: right;"  readonly /></div>
                        
                        <input type="number" name="valorUnitario" id="valorUnitario" class="form-control" value="<?=$valorUnitario; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaPorcentaje" id="ivaPorcentaje" class="form-control" value="<?=$ivaPorcentaje; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaValor" id="ivaValor" class="form-control" value="<?=$ivaValor; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="valor" id="valor" class="form-control" value="<?=$valor; ?>" style="text-align: right;"  readonly /></div>
                    </div>
                      
                    <div class="form-group">
                            <label class="form-label" for="fechaVencimiento"><strong>Fecha vigencia</strong></label>
                            <input type="date" name="fechaVencimiento" id="fechaVencimiento" value="<?=$fechaVencimiento; ?>" class="form-control"  readonly /></div>

                            <label class="form-label" for="archivo"><strong>Adjunto</strong></label>
                            <? if($archivo != "")
                            {
                                ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$archivo; ?>">Bajar Archivo</a></label></i><?
                            }
                            else{
                                echo "Sin archivo.";
                            }
                                ?></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA APROBACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Aprobación</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$aprobacion_observaciones; ?></textarea></div>

                            <label class="form-label" for="aprobacion_fecha"><strong>Fecha aprobación</strong></label>
                            <input type="date" name="aprobacion_fecha" id="aprobacion_fecha" value="<?=$aprobacion_fecha; ?>" class="form-control"  readonly /></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-warning">.ACTUALIZAR INFORMACIÓN DE LA OT.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_fechaEntrega"><strong>Fecha de entrega programada</strong></label>
                            <input type="date" name="ot_fechaEntrega" id="ot_fechaEntrega" value="<?=$ot_fechaEntrega; ?>" class="form-control"  <?
                            if($ot_estado > 0){
                                ?>readonly<?
                            }
                            ?> /></div>
                          </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_fechaEntregaReal"><strong>Fecha de entrega real</strong></label>
                            <input type="date" name="ot_fechaEntregaReal" id="ot_fechaEntregaReal" value="<?=$ot_fechaEntregaReal; ?>" class="form-control"  <?
                            if($ot_estado > 10){
                                ?>readonly<?
                            }
                            ?> /></div>

                            <label class="form-label" for="ot_estado"><strong>Estado del trabajo:</strong></label>
                            <select name="ot_estado" id="ot_estado" class="form-control">
                                    <option value="20" <? if($ot_estado == 20){ ?>selected="selected"<? } ?>>Entregado</option>
                            </select>
                        </div>

                        <div class="row">
                            <center><input type="submit" name="button" value="Guardar cambios" class="btn btn-success"></center></div>
                        </div>

                    <input type="hidden" name="funcion" id="funcion" value="" />
                    </form>

                    </div>
                    </div>

                    <script language="javascript">
                    function generarForm(){
                            if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?"))
                            {
                                if(document.getElementById('ot_fechaEntrega').value != "")
                                {
                                    //Todo bien
                                    document.getElementById('funcion').value = "actualizarOT";
                                }
                                else
                                {
                                    alert("La informacion es primordial para brindarle un excelente servicio, por favor digite todos los campos incluida la fecha");
                                    return false;
                                }
                            }else{
                                return false;
                            }
                            return true;
                    }
                    //
                    function init(){
                        document.getElementById('form1').onsubmit = function(){
                            return generarForm();
                        }
                    }

                    window.onload = function(){
                        init();
                    }

                    </script>

                    <? 
            }
        }
        else{
            echo "Estado de OT no valido.";
        }
    }    
}
/*
*   USUARIO INTERNO DE ICAR SOLUCIONES PUEDE MODIFICAR TODA LA ORDEN.
*/
else if(isset($_REQUEST["idLinea"]) && soloNumeros($_REQUEST["idLinea"]) != "" && soloNumeros($_REQUEST["idLinea"]) != "0" && $_SESSION["perfil"] != 4 && $_SESSION["perfil"] != 3 && $_SESSION["perfil"] != 160 && $varExito != 1){

    /*
    *      FACTURACIÓN DEL PROVEEDOR
    */
    if(isset($_REQUEST["fact_cli"])){
        //
        $idLinea = soloNumeros($_REQUEST["idLinea"]);
        //
        $sql = "SELECT 
                    veh_solicitudes_cot_pro.* ";
        $sql.=" FROM veh_solicitudes_cot_pro ";
        $sql.=" WHERE veh_solicitudes_cot_pro.idSolicitud = '".$idSolicitud."'";
            $sql.=" AND veh_solicitudes_cot_pro.id = '".$idLinea."'";
            $sql.=" AND veh_solicitudes_cot_pro.ot_estado = 20";
        $sql.=" ORDER BY veh_solicitudes_cot_pro.fechaCreacion ASC";
        //
        $PSN1->query($sql);
        if($PSN1->num_rows() > 0)
        {
            $contador = 0;
            if($PSN1->next_record())
            {
                $cot_pro_estado = $PSN1->f('cot_pro_estado');
                $observacion = $PSN1->f('observacion');
                $cantidad = intval($PSN1->f('cantidad'));
                $valorUnitario = intval($PSN1->f('valorUnitario'));
                $ivaPorcentaje = intval($PSN1->f('ivaPorcentaje'));
                $ivaValor = intval($PSN1->f('ivaValor'));
                $valor = $PSN1->f('valor');
                $fechaVencimiento = $PSN1->f('fechaVencimiento');
                $archivo = $PSN1->f('archivo');
                $fechaCreacion = $PSN1->f('fechaCreacion');
                $usuarioCreacion = $PSN1->f('usuarioCreacion');
                //
                $ot_estado = $PSN1->f('ot_estado');
                $ot_fechaEntrega = $PSN1->f('ot_fechaEntrega');
                $ot_fechaEntregaReal = $PSN1->f('ot_fechaEntregaReal');
                $ot_fechaPago = $PSN1->f('ot_fechaPago');
                //
                $aprobacion_observaciones = $PSN1->f('aprobacion_observaciones');
                $aprobacion_fecha = $PSN1->f('aprobacion_fecha');
                //
                $pago_pro_estado = $PSN1->f('pago_pro_estado');
                $pago_pro_fechaFactura = $PSN1->f('pago_pro_fechaFactura');
                $pago_pro_observaciones = $PSN1->f('pago_pro_observaciones');
                $pago_pro_fechaPagoReal = $PSN1->f('pago_pro_fechaPagoReal');
                $pago_pro_fechaPago = $PSN1->f('pago_pro_fechaPago');
                $pago_pro_archivo = $PSN1->f('pago_pro_archivo');       
                //
                $pago_cli_estado = $PSN1->f('pago_cli_estado');
                $pago_cli_fechaFactura = $PSN1->f('pago_cli_fechaFactura');
                $pago_cli_observaciones = $PSN1->f('pago_cli_observaciones');
                $pago_cli_fechaPagoReal = $PSN1->f('pago_cli_fechaPagoReal');
                $pago_cli_fechaPago = $PSN1->f('pago_cli_fechaPago');
                $pago_cli_archivo = $PSN1->f('pago_cli_archivo');
                    

                if($pago_pro_fechaFactura == "0000-00-00"){
                    $pago_pro_fechaFactura = date("Y-m-d");
                }
                //            
                ?>
                    <div class="panel panel-info">
                      <div class="panel-heading text-center">LINEA DE OT</div>
                      <div class="panel-body">
                        <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
                        <input type="hidden" name="id" value="<?=$idSolicitud; ?>" />
                        <input type="hidden" name="idLineaOT" value="<?=$idLinea; ?>" />

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA COTIZACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Cotización</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$observacion; ?></textarea>
                        </div>

         
                <div class="form-group">
                    <label class="control-label col-sm-2 text-center" for="cantidad"><center><strong>Cantidad</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valorUnitario"><center><strong>Valor UNITARIO</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaPorcentaje"><center><strong>% de IVA</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaValor"><center><strong>Valor IVA</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valor"><center><strong>Valor total (IVA incluido)</strong></center></label>
              </div>
                        
                    <div class="form-group">
                        <input type="number" name="cantidad" id="cantidad" class="form-control" value="<?=$cantidad; ?>" style="text-align: right;"  readonly /></div>
                        
                        <input type="number" name="valorUnitario" id="valorUnitario" class="form-control" value="<?=$valorUnitario; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaPorcentaje" id="ivaPorcentaje" class="form-control" value="<?=$ivaPorcentaje; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaValor" id="ivaValor" class="form-control" value="<?=$ivaValor; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="valor" id="valor" class="form-control" value="<?=$valor; ?>" style="text-align: right;"  readonly /></div>
                    </div>
                      
                    <div class="form-group">
                            <label class="form-label" for="fechaVencimiento"><strong>Fecha vigencia</strong></label>
                            <input type="date" name="fechaVencimiento" id="fechaVencimiento" value="<?=$fechaVencimiento; ?>" class="form-control"  readonly /></div>

                            <label class="form-label" for="archivo"><strong>Adjunto</strong></label>
                            <? if($archivo != "")
                            {
                                ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$archivo; ?>">Bajar Archivo</a></label></i><?
                            }
                            else{
                                echo "Sin archivo.";
                            }
                                ?></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA APROBACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Aprobación</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$aprobacion_observaciones; ?></textarea></div>

                            <label class="form-label" for="aprobacion_fecha"><strong>Fecha aprobación</strong></label>
                            <input type="date" name="aprobacion_fecha" id="aprobacion_fecha" value="<?=$aprobacion_fecha; ?>" class="form-control"  readonly /></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA OT.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_fechaEntrega"><strong>Fecha de entrega programada</strong></label>
                            <input type="date" name="ot_fechaEntrega" id="ot_fechaEntrega" value="<?=$ot_fechaEntrega; ?>" class="form-control" readonly /></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_fechaEntregaReal"><strong>Fecha de entrega real</strong></label>
                            <input type="date" name="ot_fechaEntregaReal" id="ot_fechaEntregaReal" value="<?=$ot_fechaEntregaReal; ?>" class="form-control" readonly /></div>

                            <label class="form-label" for="ot_estado"><strong>Estado del trabajo:</strong></label>
                            <select name="ot_estado" id="ot_estado" class="form-control" disabled>
                                    <option value="20" <? if($ot_estado == 10){ ?>selected="selected"<? } ?>>Entregado</option>
                            </select>
                        </div>


                        <div class="row">
                            <h5 class="text-center alert alert-info">.OT - FACTURACIÓN DEL PROVEEDOR.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="pago_pro_fechaFactura"><strong>Fecha de la factura</strong></label>
                            <input type="date" name="pago_pro_fechaFactura" id="pago_pro_fechaFactura" value="<?=$pago_pro_fechaFactura; ?>" class="form-control" readonly /></div>

                            <label class="form-label" for="pago_pro_observaciones"><strong>Observaciones</strong></label>
                            <textarea name="pago_pro_observaciones" id="pago_pro_observaciones" class="form-control" readonly><?=$pago_pro_observaciones; ?></textarea></div>


                            <label class="form-label" for="pago_pro_archivo"><strong>Archivo adjunto de la FACTURA</strong></label>
                            <? if($pago_pro_archivo != "")
                            {
                                ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$pago_pro_archivo; ?>">Bajar Archivo</a></label></i><?
                            }
                            else{
                                echo "Sin archivo.";
                            }
                            ?></div>
                        </div>                      

                        <div class="form-group">
                            <label class="form-label" for="pago_pro_fechaPago"><strong>Fecha de pago programada</strong></label>
                            <input type="date" name="pago_pro_fechaPago" id="pago_pro_fechaPago" value="<?=$pago_pro_fechaPago; ?>" class="form-control" readonly /></div>

                            <label class="form-label" for="pago_pro_fechaPagoReal"><strong>Fecha de pago real</strong></label>
                            <input type="date" name="pago_pro_fechaPagoReal" id="pago_pro_fechaPagoReal" value="<?=$pago_pro_fechaPagoReal; ?>" class="form-control" readonly /></div>


                            <label class="form-label" for="pago_pro_estado"><strong>Estado factura</strong></label>
                            <select name="pago_pro_estado" id="pago_pro_estado" class="form-control" disabled>
                                <option value="0" <? if($pago_pro_estado == 0){ ?>selected="selected"<? } ?>>Listo para facturar</option>
                                <option value="10" <? if($pago_pro_estado == 10){ ?>selected="selected"<? } ?>>Facturada</option>
                                <option value="20" <? if($pago_pro_estado == 20){ ?>selected="selected"<? } ?>>En proceso de pago</option>
                                <option value="30" <? if($pago_pro_estado == 30){ ?>selected="selected"<? } ?>>Pagada</option>
                                <? /* ?><option value="90" <? if($pago_pro_estado == 90){ ?>selected="selected"<? } ?>>Reversada</option><? */ ?>
                            </select>

                        </div>
                        
                        <div class="row">
                            <h5 class="text-center alert alert-warning">.OT - FACTURACIÓN AL CLIENTE.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="pago_cli_fechaFactura"><strong>Fecha de la factura</strong></label>
                            <input type="date" name="pago_cli_fechaFactura" id="pago_cli_fechaFactura" value="<?=$pago_cli_fechaFactura; ?>" class="form-control" /></div>

                            <label class="form-label" for="pago_cli_observaciones"><strong>Observaciones</strong></label>
                            <textarea name="pago_cli_observaciones" id="pago_cli_observaciones" class="form-control"><?=$pago_cli_observaciones; ?></textarea></div>


                            <label class="form-label" for="pago_cli_archivo"><strong>Adjunto</strong></label>
                            <input name="pago_cli_archivo" type="file" id="pago_cli_archivo"  class="form-control" />
                            <? if($pago_cli_archivo != "")
                            {
                                ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$pago_cli_archivo; ?>">Bajar Archivo</a></label></i><?
                            }
                            else{
                                echo "Sin archivo.";
                            }
                            ?></div>
                        </div>                      

                        <div class="form-group">
                            <label class="form-label" for="pago_cli_fechaPago"><strong>Fecha de pago programada</strong></label>
                            <input type="date" name="pago_cli_fechaPago" id="pago_cli_fechaPago" value="<?=$pago_cli_fechaPago; ?>" class="form-control" /></div>

                            <label class="form-label" for="pago_cli_fechaPagoReal"><strong>Fecha de pago real</strong></label>
                            <input type="date" name="pago_cli_fechaPagoReal" id="pago_cli_fechaPagoReal" value="<?=$pago_cli_fechaPagoReal; ?>" class="form-control" /></div>


                            <label class="form-label" for="pago_cli_estado"><strong>Estado factura</strong></label>
                            <select name="pago_cli_estado" id="pago_cli_estado" class="form-control">
                                <option value="0" <? if($pago_cli_estado == 0){ ?>selected="selected"<? } ?>>Sin facturar</option>
                                <option value="10" <? if($pago_cli_estado == 10){ ?>selected="selected"<? } ?>>Facturada</option>
                                <option value="20" <? if($pago_cli_estado == 20){ ?>selected="selected"<? } ?>>En proceso de pago</option>
                                <option value="30" <? if($pago_cli_estado == 30){ ?>selected="selected"<? } ?>>Pagada</option>
                                <? /* ?><option value="90" <? if($pago_pro_estado == 90){ ?>selected="selected"<? } ?>>Reversada</option><? */ ?>
                            </select>

                        </div>


                        <div class="row">
                            <center><input type="submit" name="button" value="Guardar cambios" class="btn btn-success"></center></div>
                        </div>
                        
                    <input type="hidden" name="funcion" id="funcion" value="" />
                    </form>

                    </div>
                    </div>

                    <script language="javascript">
                        function generarForm(){
                                if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?"))
                                {
                                    if(document.getElementById('pago_cli_fechaFactura').value != "")
                                    {
                                        //Todo bien
                                        document.getElementById('funcion').value = "pago_cli_actualizarOT";
                                    }
                                    else
                                    {
                                        alert("La informacion es primordial para brindarle un excelente servicio, por favor digite todos los campos incluida la fecha de factura");
                                        return false;
                                    }
                                }else{
                                    return false;
                                }
                                return true;
                        }
                        //
                        function init(){
                            document.getElementById('form1').onsubmit = function(){
                                return generarForm();
                            }
                        }

                        window.onload = function(){
                            init();
                        }
                    </script>

                    <? 
            }
        }
        else{
            echo "Estado de OT no valido.";
        }
        //FIN DE LA LOGICA DENTRO DEL IF DE FACTURACIÓN DEL PROVEEDOR
    }    
    /*
    *      FACTURACIÓN DEL PROVEEDOR
    */
    else if(isset($_REQUEST["fact"])){
        //
        $idLinea = soloNumeros($_REQUEST["idLinea"]);
        //
        $sql = "SELECT 
                    veh_solicitudes_cot_pro.* ";
        $sql.=" FROM veh_solicitudes_cot_pro ";
        $sql.=" WHERE veh_solicitudes_cot_pro.idSolicitud = '".$idSolicitud."'";
            $sql.=" AND veh_solicitudes_cot_pro.id = '".$idLinea."'";
                $sql.=" AND veh_solicitudes_cot_pro.ot_estado = 20";
        $sql.=" ORDER BY veh_solicitudes_cot_pro.fechaCreacion ASC";
        //
        $PSN1->query($sql);
        if($PSN1->num_rows() > 0)
        {
            $contador = 0;
            if($PSN1->next_record())
            {
                $cot_pro_estado = $PSN1->f('cot_pro_estado');
                $observacion = $PSN1->f('observacion');
                $valor = $PSN1->f('valor');
                $fechaVencimiento = $PSN1->f('fechaVencimiento');
                $cantidad = intval($PSN1->f('cantidad'));
                $valorUnitario = intval($PSN1->f('valorUnitario'));
                $ivaPorcentaje = intval($PSN1->f('ivaPorcentaje'));
                $ivaValor = intval($PSN1->f('ivaValor'));
                $archivo = $PSN1->f('archivo');
                $fechaCreacion = $PSN1->f('fechaCreacion');
                $usuarioCreacion = $PSN1->f('usuarioCreacion');
                //
                $ot_estado = $PSN1->f('ot_estado');
                $ot_fechaEntrega = $PSN1->f('ot_fechaEntrega');
                $ot_fechaEntregaReal = $PSN1->f('ot_fechaEntregaReal');
                $ot_fechaPago = $PSN1->f('ot_fechaPago');
                //
                $aprobacion_observaciones = $PSN1->f('aprobacion_observaciones');
                $aprobacion_fecha = $PSN1->f('aprobacion_fecha');
                //
                $pago_pro_estado = $PSN1->f('pago_pro_estado');
                $pago_pro_fechaFactura = $PSN1->f('pago_pro_fechaFactura');
                $pago_pro_observaciones = $PSN1->f('pago_pro_observaciones');
                $pago_pro_fechaPagoReal = $PSN1->f('pago_pro_fechaPagoReal');
                $pago_pro_fechaPago = $PSN1->f('pago_pro_fechaPago');
                $pago_pro_archivo = $PSN1->f('pago_pro_archivo');       

                if($pago_pro_fechaFactura == "0000-00-00"){
                    $pago_pro_fechaFactura = date("Y-m-d");
                }
                //            
                ?>
                    <div class="panel panel-info">
                      <div class="panel-heading text-center">LINEA DE OT</div>
                      <div class="panel-body">
                        <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
                        <input type="hidden" name="id" value="<?=$idSolicitud; ?>" />
                        <input type="hidden" name="idLineaOT" value="<?=$idLinea; ?>" />

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA COTIZACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Cotización</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$observacion; ?></textarea>
                        </div>

             <div class="form-group">
                    <label class="control-label col-sm-2 text-center" for="cantidad"><center><strong>Cantidad</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valorUnitario"><center><strong>Valor UNITARIO</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaPorcentaje"><center><strong>% de IVA</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaValor"><center><strong>Valor IVA</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valor"><center><strong>Valor total (IVA incluido)</strong></center></label>
              </div>
                        
                    <div class="form-group">
                        <input type="number" name="cantidad" id="cantidad" class="form-control" value="<?=$cantidad; ?>" style="text-align: right;"  readonly /></div>
                        
                        <input type="number" name="valorUnitario" id="valorUnitario" class="form-control" value="<?=$valorUnitario; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaPorcentaje" id="ivaPorcentaje" class="form-control" value="<?=$ivaPorcentaje; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaValor" id="ivaValor" class="form-control" value="<?=$ivaValor; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="valor" id="valor" class="form-control" value="<?=$valor; ?>" style="text-align: right;"  readonly /></div>
                    </div>
                      

                    <div class="form-group">
                            <label class="form-label" for="fechaVencimiento"><strong>Fecha vigencia</strong></label>
                            <input type="date" name="fechaVencimiento" id="fechaVencimiento" value="<?=$fechaVencimiento; ?>" class="form-control"  readonly /></div>

                            <label class="form-label" for="archivo"><strong>Adjunto</strong></label>
                            <? if($archivo != "")
                            {
                                ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$archivo; ?>">Bajar Archivo</a></label></i><?
                            }
                            else{
                                echo "Sin archivo.";
                            }
                                ?></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA APROBACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Aprobación</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$aprobacion_observaciones; ?></textarea></div>

                            <label class="form-label" for="aprobacion_fecha"><strong>Fecha aprobación</strong></label>
                            <input type="date" name="aprobacion_fecha" id="aprobacion_fecha" value="<?=$aprobacion_fecha; ?>" class="form-control"  readonly /></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA OT.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_fechaEntrega"><strong>Fecha de entrega programada</strong></label>
                            <input type="date" name="ot_fechaEntrega" id="ot_fechaEntrega" value="<?=$ot_fechaEntrega; ?>" class="form-control" readonly /></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_fechaEntregaReal"><strong>Fecha de entrega real</strong></label>
                            <input type="date" name="ot_fechaEntregaReal" id="ot_fechaEntregaReal" value="<?=$ot_fechaEntregaReal; ?>" class="form-control" readonly /></div>

                            <label class="form-label" for="ot_estado"><strong>Estado del trabajo:</strong></label>
                            <select name="ot_estado" id="ot_estado" class="form-control" disabled>
                                    <option value="20" <? if($ot_estado == 10){ ?>selected="selected"<? } ?>>Entregado</option>
                            </select>
                        </div>


                        <div class="row">
                            <h5 class="text-center alert alert-warning">.OT - FACTURACIÓN DEL PROVEEDOR.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="pago_pro_fechaFactura"><strong>Fecha de la factura</strong></label>
                            <input type="date" name="pago_pro_fechaFactura" id="pago_pro_fechaFactura" value="<?=$pago_pro_fechaFactura; ?>" class="form-control" readonly /></div>

                            <label class="form-label" for="pago_pro_observaciones"><strong>Observaciones</strong></label>
                            <textarea name="pago_pro_observaciones" id="pago_pro_observaciones" class="form-control" readonly><?=$pago_pro_observaciones; ?></textarea></div>


                            <label class="form-label" for="pago_pro_archivo"><strong>Archivo adjunto de la factura</strong></label>
                            <? if($pago_pro_archivo != "")
                            {
                                ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$pago_pro_archivo; ?>">Bajar Archivo</a></label></i><?
                            }
                            else{
                                echo "Sin archivo.";
                            }
                            ?></div>
                        </div>                      

                        <div class="form-group">
                            <label class="form-label" for="pago_pro_fechaPago"><strong>Fecha de pago programada</strong></label>
                            <input type="date" name="pago_pro_fechaPago" id="pago_pro_fechaPago" value="<?=$pago_pro_fechaPago; ?>" class="form-control" /></div>

                            <label class="form-label" for="pago_pro_fechaPagoReal"><strong>Fecha de pago real</strong></label>
                            <input type="date" name="pago_pro_fechaPagoReal" id="pago_pro_fechaPagoReal" value="<?=$pago_pro_fechaPagoReal; ?>" class="form-control" /></div>


                            <label class="form-label" for="pago_pro_estado"><strong>Estado factura</strong></label>
                            <select name="pago_pro_estado" id="pago_pro_estado" class="form-control">
                                <option value="0" <? if($pago_pro_estado == 0){ ?>selected="selected"<? } ?>>Listo para facturar</option>
                                <option value="10" <? if($pago_pro_estado == 10){ ?>selected="selected"<? } ?>>Facturada</option>
                                <option value="20" <? if($pago_pro_estado == 20){ ?>selected="selected"<? } ?>>En proceso de pago</option>
                                <option value="30" <? if($pago_pro_estado == 30){ ?>selected="selected"<? } ?>>Pagada</option>
                                <? /* ?><option value="90" <? if($pago_pro_estado == 90){ ?>selected="selected"<? } ?>>Reversada</option><? */ ?>
                            </select>

                        </div>

                        <div class="row">
                            <center><input type="submit" name="button" value="Guardar cambios" class="btn btn-success"></center></div>
                        </div>
                    <input type="hidden" name="funcion" id="funcion" value="" />
                    </form>

                    </div>
                    </div>

                    <script language="javascript">
                        function generarForm(){
                                if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?"))
                                {
                                    if(document.getElementById('pago_pro_fechaFactura').value != "")
                                    {
                                        //Todo bien
                                        document.getElementById('funcion').value = "pago_prov_actualizarOT";
                                    }
                                    else
                                    {
                                        alert("La informacion es primordial para brindarle un excelente servicio, por favor digite todos los campos incluida la fecha de factura");
                                        return false;
                                    }
                                }else{
                                    return false;
                                }
                                return true;
                        }
                        //
                        function init(){
                            document.getElementById('form1').onsubmit = function(){
                                return generarForm();
                            }
                        }

                        window.onload = function(){
                            init();
                        }
                    </script>

                    <? 
            }
        }
        else{
            echo "Estado de OT no valido.";
        }

        //FIN DE LA LOGICA DENTRO DEL IF DE FACTURACIÓN DEL PROVEEDOR
    }        
    else
    {
        /*
        *   USUARIO DE ICAR QUE PUEDE ACTUALIZAR TODA LA INFORMACIÓN DE LA OT
        */
        //
        $idLinea = soloNumeros($_REQUEST["idLinea"]);
        //
        $sql = "SELECT 
                    veh_solicitudes_cot_pro.* ";
        $sql.=" FROM veh_solicitudes_cot_pro ";
        $sql.=" WHERE veh_solicitudes_cot_pro.idSolicitud = '".$idSolicitud."'";
            $sql.=" AND veh_solicitudes_cot_pro.id = '".$idLinea."'";
        $sql.=" ORDER BY veh_solicitudes_cot_pro.fechaCreacion ASC";
        //
        $PSN1->query($sql);
        if($PSN1->num_rows() > 0)
        {
            $contador = 0;
            if($PSN1->next_record())
            {
                $cot_pro_estado = $PSN1->f('cot_pro_estado');
                $observacion = $PSN1->f('observacion');
                $cantidad = intval($PSN1->f('cantidad'));
                $valorUnitario = intval($PSN1->f('valorUnitario'));
                $ivaPorcentaje = intval($PSN1->f('ivaPorcentaje'));
                $ivaValor = intval($PSN1->f('ivaValor'));                
                $valor = $PSN1->f('valor');
                $fechaVencimiento = $PSN1->f('fechaVencimiento');
                $archivo = $PSN1->f('archivo');
                $fechaCreacion = $PSN1->f('fechaCreacion');
                $usuarioCreacion = $PSN1->f('usuarioCreacion');
                //
                $ot_estado = $PSN1->f('ot_estado');
                $ot_fechaEntrega = $PSN1->f('ot_fechaEntrega');
                $ot_fechaEntregaReal = $PSN1->f('ot_fechaEntregaReal');
                if($ot_fechaEntregaReal == "" || $ot_fechaEntregaReal == "0000-00-00"){
                    $ot_fechaEntregaReal = date("Y-m-d");
                }

                $ot_fechaPago = $PSN1->f('ot_fechaPago');
                //
                //
                $aprobacion_observaciones = $PSN1->f('aprobacion_observaciones');
                $aprobacion_fecha = $PSN1->f('aprobacion_fecha');
                //            
                ?>
                    <div class="panel panel-info">
                      <div class="panel-heading text-center">LINEA DE OT</div>
                      <div class="panel-body">
                        <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
                        <input type="hidden" name="id" value="<?=$idSolicitud; ?>" />
                        <input type="hidden" name="idLineaOT" value="<?=$idLinea; ?>" />

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA COTIZACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Cotización</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$observacion; ?></textarea>
                        </div>

             <div class="form-group">
                    <label class="control-label col-sm-2 text-center" for="cantidad"><center><strong>Cantidad</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valorUnitario"><center><strong>Valor UNITARIO</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaPorcentaje"><center><strong>% de IVA</strong></center></label>

                    <label class="control-label col-sm-2 text-center" for="ivaValor"><center><strong>Valor IVA</strong></center></label>

                    <label class="control-label col-sm-3 text-center" for="valor"><center><strong>Valor total (IVA incluido)</strong></center></label>
              </div>
                        
                    <div class="form-group">
                        <input type="number" name="cantidad" id="cantidad" class="form-control" value="<?=$cantidad; ?>" style="text-align: right;"  readonly /></div>
                        
                        <input type="number" name="valorUnitario" id="valorUnitario" class="form-control" value="<?=$valorUnitario; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaPorcentaje" id="ivaPorcentaje" class="form-control" value="<?=$ivaPorcentaje; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="ivaValor" id="ivaValor" class="form-control" value="<?=$ivaValor; ?>" style="text-align: right;"  readonly /></div>

                        <input type="number" name="valor" id="valor" class="form-control" value="<?=$valor; ?>" style="text-align: right;"  readonly /></div>
                    </div>
                      

                    <div class="form-group">
                        <label class="form-label" for="fechaVencimiento"><strong>Fecha vigencia</strong></label>
                            <input type="date" name="fechaVencimiento" id="fechaVencimiento" value="<?=$fechaVencimiento; ?>" class="form-control"  readonly /></div>

                            <label class="form-label" for="archivo"><strong>Adjunto</strong></label>
                            <? if($archivo != "")
                            {
                                ?><label  class="control-label"><a href="descarga_mant.php?&archivo=<?=$archivo; ?>">Bajar Archivo</a></label></i><?
                            }
                            else{
                                echo "Sin archivo.";
                            }
                                ?></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-info">.INFORMACIÓN DE LA APROBACIÓN.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="observaciones"><strong>Aprobación</strong></label>
                            <textarea name="observaciones" id="observaciones" class="form-control" readonly><?=$aprobacion_observaciones; ?></textarea></div>

                            <label class="form-label" for="aprobacion_fecha"><strong>Fecha aprobación</strong></label>
                            <input type="date" name="aprobacion_fecha" id="aprobacion_fecha" value="<?=$aprobacion_fecha; ?>" class="form-control"  readonly /></div>
                        </div>

                        <div class="row">
                            <h5 class="text-center alert alert-warning">.ACTUALIZAR INFORMACIÓN DE LA OT.</h5>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_fechaEntrega"><strong>Fecha de entrega programada</strong></label>
                            <input type="date" name="ot_fechaEntrega" id="ot_fechaEntrega" value="<?=$ot_fechaEntrega; ?>" class="form-control" /></div>

                            <label class="form-label" for="ot_fechaEntregaReal"><strong>Fecha de entrega real</strong></label>
                            <input type="date" name="ot_fechaEntregaReal" id="ot_fechaEntregaReal" value="<?=$ot_fechaEntregaReal; ?>" class="form-control" /></div>
                      </div>

                        <div class="form-group">
                            <label class="form-label" for="ot_estado"><strong>Estado del trabajo:</strong></label>
                            <select name="ot_estado" id="ot_estado" class="form-control">
                                    <option value="0" <? if($ot_estado == 0 || $ot_estado == ""){ ?>selected="selected"<? } ?>>Aprobada</option>
                                    <option value="1" <? if($ot_estado == 1){ ?>selected="selected"<? } ?>>En proceso</option>
                                    <option value="10" <? if($ot_estado == 10){ ?>selected="selected"<? } ?>>Terminado</option>
                                    <option value="20" <? if($ot_estado == 20){ ?>selected="selected"<? } ?>>Entregado</option>
                                    <? /*<option value="90" <? if($ot_estado == 90){ ?>selected="selected"<? } ?>>Cancelado</option>*/ ?>
                            </select>
                        </div>

                        <div class="row">
                            <center><input type="submit" name="button" value="Guardar cambios" class="btn btn-success"></center></div>
                        </div>

                    <input type="hidden" name="funcion" id="funcion" value="" />
                    </form>

                    </div>
                    </div>

                    <script language="javascript">
                    function generarForm(){
                            if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?"))
                            {
                                if(document.getElementById('ot_fechaEntrega').value != "")
                                {
                                    //Todo bien
                                    document.getElementById('funcion').value = "actualizarOT";
                                }
                                else
                                {
                                    alert("La informacion es primordial para brindarle un excelente servicio, por favor digite todos los campos incluida la fecha de entrega");
                                    return false;
                                }
                            }else{
                                return false;
                            }
                            return true;
                    }
                    //
                    function init(){
                        document.getElementById('form1').onsubmit = function(){
                            return generarForm();
                        }
                    }

                    window.onload = function(){
                        init();
                    }

                    </script>

                    <? 
            }
        }
        else{
            echo "Estado de OT no valido.";
        }
    }
}
?>