<?php
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;
$PSN = new DBbase_Sql;
$webArchivo = "preoperacional";
$temp_letrero = "INSTITUTO BIBLICO";


// Compress image
function compressImage($source, $destination, $quality) {
  $info = getimagesize($source);
  if($info['mime'] == 'image/jpeg'){
        $image = imagecreatefromjpeg($source);
  }
  elseif ($info['mime'] == 'image/gif'){
        $image = imagecreatefromgif($source);
  }
  elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
  }
  imagejpeg($image, $destination, $quality);
}



/*
*   VERIFICAMOS CON QUE GENERACIÓN NOS ESTAMOS ENFRENTANDO ACTUALMENTE.
*/
$preguntarGeneracion = 0;
if(isset($_REQUEST["generacion"]) && $_REQUEST["generacion"] != ""){
    $generacionActual = eliminarInvalidos($_REQUEST["generacion"]);
}else{
    $generacionActual = "IB";
}


/*
*   Comprobamos si viene en modo de actualización o de insersión.
*/
if(isset($_REQUEST["id"]) && $_REQUEST["id"] != ""){
    $idReporteActual = soloNumeros($_REQUEST["id"]);
    if ($_SESSION["perfil"] == 162 || $_SESSION["perfil"] == 163) {
        $sql = "UPDATE  sat_reportes SET 
                    mapeo_fecha = '".date('Y-m-d')."'";
    
        $sql .= "WHERE id = '".$idReporteActual."'";
        $PSN1->query($sql);
    }  
}else{
    $idReporteActual = 0;
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
    //
    if($_POST["funcion"] == "insertar"){
        //die("Insertar");
        /*
        *   PESTAÑA GENERAL
        */
        $fechaReporte = eliminarInvalidos($_REQUEST["fechaReporte"]);
        $fechaInicio = eliminarInvalidos($_REQUEST["fechaInicio"]);
        $fechaFinal = eliminarInvalidos($_REQUEST["fechaFinal"]);        
        if (isset($_REQUEST['sitioReunion'])) {
            $sitioReunion = soloNumeros($_REQUEST["sitioReunion"]);
        }else{
            $sitioReunion = 0;
        }  
        $grupoMadre_txt = eliminarInvalidos($_REQUEST["grupoMadre_txt"]);
        $nombreGrupo_txt = eliminarInvalidos($_REQUEST["nombreGrupo_txt"]);
        
        $pabellon = eliminarInvalidos($_REQUEST["pabellon"]);
        $direccion = eliminarInvalidos($_REQUEST["direccion"]);
        if (isset($_REQUEST["municipio"])) {
            $ciudad = soloNumeros($_REQUEST["municipio"]);
        }else{
            $ciudad = 0;
        }
                    
        
        $capacitacion_txt = eliminarInvalidos($_REQUEST["capacitacion_txt"]);        
        $idGrupoMadre = soloNumeros($_REQUEST["idGrupoMadre"]);
        $generacionNumero = soloNumeros($_REQUEST["generacionNumero"]);
        

        $asistencia_hom = soloNumeros($_REQUEST["asistencia_hom"]);
        $asistencia_muj = soloNumeros($_REQUEST["asistencia_muj"]);
        $asistencia_jov = soloNumeros($_REQUEST["asistencia_jov"]);

        $asistencia_nin = soloNumeros($_REQUEST["total"]);
        $bautizados = soloNumeros($_REQUEST["total2"]);
        $desiciones  = soloNumeros($_REQUEST["total3"]);

        $discipulado  = soloNumeros($_REQUEST["discipulado"]);
        $comentario  = eliminarInvalidos($_REQUEST["rep_text1"]);

        $nombre_archivo = $_FILES['archivo1']['name'];
        $archivo1 = extension_archivo($nombre_archivo);
        
        $nombre_archivo = $_FILES['archivo2']['name'];
        $archivo2 = extension_archivo($nombre_archivo);
        
        $nombre_archivo = $_FILES['archivo3']['name'];
        $archivo3 = extension_archivo($nombre_archivo);

        $mapeo_cuarto = soloNumeros($_REQUEST["mapeo_cuarto"]);
        $mapeo_fecha = eliminarInvalidos($_REQUEST["mapeo_fecha"]);
        $mapeo_comprometido = soloNumeros($_REQUEST["mapeo_comprometido"]);
        
        $mapeo_oracion = soloNumeros($_REQUEST["mapeo_oracion"]);        
        $mapeo_companerismo = soloNumeros($_REQUEST["mapeo_companerismo"]);        
        $mapeo_adoracion = soloNumeros($_REQUEST["mapeo_adoracion"]);        
        $mapeo_biblia = soloNumeros($_REQUEST["mapeo_biblia"]);        
        $mapeo_evangelizar = soloNumeros($_REQUEST["mapeo_evangelizar"]);        
        $mapeo_cena = soloNumeros($_REQUEST["mapeo_cena"]);        
        $mapeo_dar = soloNumeros($_REQUEST["mapeo_dar"]);        
        $mapeo_bautizar = soloNumeros($_REQUEST["mapeo_bautizar"]);        
        $mapeo_trabajadores = soloNumeros($_REQUEST["mapeo_trabajadores"]);   
        
        
        //Calculados:
        $asistencia_total  = soloNumeros($_REQUEST["asistencia_total"]);
        
        
        $rep_tip  = 317;
        
        $rep_ndis  = soloNumeros($_REQUEST["rep_ndis"]);
        $iglesias_reconocidas = 0;
        //        

        if($error_datos == 0){
            
            /*
            *   DEBEMOS INSERTAR LA INFORMACION DEL REPORTE SEGUN CORRESPONDA.
            */
            $sql = 'INSERT INTO sat_reportes (
                idUsuario,
                comentario,
                plantador,
                rep_entr,
                fechaReporte,
                fechaInicio,
                sitioReunion,
                grupoMadre_txt,
                nombreGrupo_txt,
                capacitacion_txt,
                idGrupoMadre,
                generacionNumero,
                
                pabellon,
                direccion,
                ciudad,
                
                    asistencia_hom,
                    asistencia_muj,
                    asistencia_jov,
                    asistencia_nin,

                bautizados,
                bautizadosPeriodo,

                asistencia_total,
                discipulado,
                desiciones,
                rep_ndis,
                preparandose,
                
                creacionFecha,
                creacionUsuario,
                ext1,
                ext2,
                
                    mapeo_fecha,
                    mapeo_comprometido,

                        mapeo_oracion,
                        mapeo_companerismo,
                        mapeo_adoracion,
                        mapeo_biblia,
                        mapeo_evangelizar,
                        mapeo_cena,
                        mapeo_dar,
                        mapeo_bautizar,
                        mapeo_trabajadores,                
                
                mapeo_anho,
                mapeo_cuarto,
                ext3,
                rep_tip
                )';
            
            $sql .= ' VALUES 
                (
                "'.$_SESSION["id"].'",
                "'.$comentario.'", 
                "'.$plantador.'",
                "'.$entrenador.'", 
                "'.$fechaReporte.'", 
                "'.$fechaInicio.'", 
                '.$sitioReunion.', 
                "'.$grupoMadre_txt.'", 
                "'.$nombreGrupo_txt.'",                 
                "'.$capacitacion_txt.'", 
                "'.$idGrupoMadre.'", 
                "'.$generacionNumero.'", 
                
                "'.$pabellon.'", 
                "'.$direccion.'", 
                '.$ciudad.', 
                

                    "'.$asistencia_hom.'", 
                    "'.$asistencia_muj.'", 
                    "'.$asistencia_jov.'", 
                    "'.$asistencia_nin.'", 
                    
                "'.$bautizados.'", 
                "'.$bautizadosPeriodo.'", 
                
                
                "'.$asistencia_total.'", 
                "'.$discipulado.'", 
                "'.$desiciones.'",
                '.$rep_ndis.', 
                "'.$preparandose.'",

                NOW(), 
                "'.$_SESSION["id"].'",

                "'.$archivo1.'",
                "'.$archivo2.'",
                
                    "'.$fechaFinal.'",
                    "'.$mapeo_comprometido.'",

                    "'.$mapeo_oracion.'",
                    "'.$mapeo_companerismo.'",
                    "'.$mapeo_adoracion.'",
                    "'.$mapeo_biblia.'",
                    "'.$mapeo_evangelizar.'",
                    "'.$mapeo_cena.'",
                    "'.$mapeo_dar.'",
                    "'.$mapeo_bautizar.'",
                    "'.$mapeo_trabajadores.'",
                        
                    "'.$mapeo_anho.'",
                    "'.$mapeo_cuarto.'",                    
                "'.$archivo3.'",
                '.$rep_tip.'
            )';
            
            //
            //
            //echo $sql;
            $ultimoQuery = $PSN1->query($sql);
            $ultimoId =  $PSN1->ultimoId();
                //
                if($archivo1 != ""){
                    $extArchivo = $archivo1;
                    if($extArchivo == "png" || $extArchivo == "jpg" || $extArchivo == "jpeg" || $extArchivo == "gif"){
                        $rutaOrigen = $_FILES['archivo1']['tmp_name'];
                        $rutaDestino = "archivos/evi_".$ultimoId."_1.".$archivo1;
                        compressImage($rutaOrigen, $rutaDestino, 80);
                    }
                    else{
                        if(move_uploaded_file($_FILES['archivo1']['tmp_name'], "archivos/evi_".$ultimoId."_1.".$archivo1))
                        {
                        }            
                    }
                }
            

                if($archivo2 != ""){
                    $extArchivo = $archivo2;
                    if($extArchivo == "png" || $extArchivo == "jpg" || $extArchivo == "jpeg" || $extArchivo == "gif"){
                        $rutaOrigen = $_FILES['archivo2']['tmp_name'];
                        $rutaDestino = "archivos/evi_".$ultimoId."_2.".$archivo2;
                        compressImage($rutaOrigen, $rutaDestino, 80);
                    }
                    else{
                        if(move_uploaded_file($_FILES['archivo2']['tmp_name'], "archivos/evi_".$ultimoId."_2.".$archivo2))
                        {
                        }            
                    }
                }

                //--------------------ADJUNTOS------------
                


                //-----------FIN ADJUNTOS-------------------
            if ($asistencia_nin > 0) {
                $act_grad_hv = $_FILES["act_grad_hv"];
                $act_grad_nom = $_REQUEST["act_grad_nom"];
                $act_grad_tar = $_REQUEST['act_grad_tar'];

                $sql = 'INSERT INTO tbl_adjuntos (
                    adj_nom,
                    adj_url,
                    adj_fec,
                    adj_can,
                    adj_tip, 
                    adj_rep_fk)';
                $sql .= 'VALUES';
                for ($i=0; $i < sizeof($act_grad_tar); $i++) { 
                    $tp_arch = extension_archivo($act_grad_hv['name'][$i]);
                    $sql .= "('".$act_grad_nom[$i]."','".$act_grad_tar[$i]."','".date('Y-m-d')."','archivos/hv_".$ultimoId."_".$i.".".$tp_arch."',1,".$ultimoId."),";
                    $extArchivo = $tp_arch;
                    if($extArchivo == "png" || $extArchivo == "jpg" || $extArchivo == "jpeg" || $extArchivo == "gif"){
                        $rutaOr = $act_grad_hv['tmp_name'][$i];
                        $rutaDe = "archivos/hv_".$ultimoId."_".$i.".".$tp_arch;
                        compressImage($rutaOr, $rutaDe, 80);
                    }else{
                        if(move_uploaded_file($act_grad_hv['tmp_name'][$i], "archivos/hv_".$ultimoId."_".$i.".".$tp_arch)){
                        }            
                    }
                }
                $sql = substr($sql, 0, -1);
                //echo $sql;
                $ultimoQuery = $PSN1->query($sql);
            } 
                  
            $varExitoREP = 1;
        }
    }else if($_POST["funcion"] == "eliminar"){
        $sql = 'DELETE from sat_reportes WHERE id = "'.$idReporteActual.'"';
        $PSN1->query($sql);
    }
    else if($_POST["funcion"] == "actualizar"){
       // die("Actualizar");
        //
        /*
        *   PESTAÑA GENERAL
        */
        $entrenador = eliminarInvalidos($_REQUEST["entrenador"]);
        $plantador = eliminarInvalidos($_REQUEST["plantador"]);
        $comentario = eliminarInvalidos($_REQUEST["rep_text1"]);
        $fechaReporte = eliminarInvalidos($_REQUEST["fechaReporte"]);
        $fechaInicio = eliminarInvalidos($_REQUEST["fechaInicio"]);
        if (isset($_REQUEST['sitioReunion'])) {
            $sitioReunion = soloNumeros($_REQUEST["sitioReunion"]);
        }else{
            $sitioReunion = 0;
        }  
        
        $grupoMadre_txt = eliminarInvalidos($_REQUEST["grupoMadre_txt"]);
        $nombreGrupo_txt = eliminarInvalidos($_REQUEST["nombreGrupo_txt"]);
        
        if (!empty($_REQUEST["inactivo"])) {
            $inactivo = soloNumeros($_REQUEST["inactivo"]);
        }else{
            $inactivo = 0;
        }
        
        
        
        $capacitacion_txt = eliminarInvalidos($_REQUEST["capacitacion_txt"]);        
        $idGrupoMadre = soloNumeros($_REQUEST["idGrupoMadre"]);
        $generacionNumero = soloNumeros($_REQUEST["generacionNumero"]);
        
        $pabellon = eliminarInvalidos($_REQUEST["pabellon"]);
        $direccion = eliminarInvalidos($_REQUEST["direccion"]);
        if (!empty($_REQUEST["municipio"])) {
            $ciudad = soloNumeros($_REQUEST["municipio"]);
        }else{
            $ciudad = 0;
        }

        $asistencia_hom = soloNumeros($_REQUEST["asistencia_hom"]);
        $asistencia_muj = soloNumeros($_REQUEST["asistencia_muj"]);
        $asistencia_jov = soloNumeros($_REQUEST["asistencia_jov"]);
        $asistencia_nin = soloNumeros($_REQUEST["total"]);

        $bautizados = soloNumeros($_REQUEST["total2"]);        
        $bautizadosPeriodo = soloNumeros($_REQUEST["bautizadosPeriodo"]);
        

        //Calculados:
        $asistencia_total  = soloNumeros($_REQUEST["asistencia_total"]);
        $discipulado  = soloNumeros($_REQUEST["discipulado"]);
        $desiciones  = soloNumeros($_REQUEST["total3"]);
        $rep_ndis  = soloNumeros($_REQUEST["rep_ndis"]);
        $preparandose  = soloNumeros($_REQUEST["preparandose"]);
        $iglesias_reconocidas = 0;
        
        
        $mapeo_anho = soloNumeros($_REQUEST["mapeo_anho"]);
        $mapeo_cuarto = soloNumeros($_REQUEST["mapeo_cuarto"]);
        
        
        $nombre_archivo = $_FILES['archivo1']['name'];
        $archivo1 = extension_archivo($nombre_archivo);
        
        $nombre_archivo = $_FILES['archivo2']['name'];
        $archivo2 = extension_archivo($nombre_archivo);
        
        $nombre_archivo = $_FILES['archivo3']['name'];
        $archivo3 = extension_archivo($nombre_archivo);

        
        
        $fechaFinal = eliminarInvalidos($_REQUEST["fechaFinal"]);
        $mapeo_comprometido = soloNumeros($_REQUEST["mapeo_comprometido"]);
        
        $mapeo_oracion = soloNumeros($_REQUEST["mapeo_oracion"]);        
        $mapeo_companerismo = soloNumeros($_REQUEST["mapeo_companerismo"]);        
        $mapeo_adoracion = soloNumeros($_REQUEST["mapeo_adoracion"]);        
        $mapeo_biblia = soloNumeros($_REQUEST["mapeo_biblia"]);        
        $mapeo_evangelizar = soloNumeros($_REQUEST["mapeo_evangelizar"]);        
        $mapeo_cena = soloNumeros($_REQUEST["mapeo_cena"]);        
        $mapeo_dar = soloNumeros($_REQUEST["mapeo_dar"]);        
        $mapeo_bautizar = soloNumeros($_REQUEST["mapeo_bautizar"]);        
        $mapeo_trabajadores = soloNumeros($_REQUEST["mapeo_trabajadores"]);        
        
        //
        $sql = 'UPDATE  sat_reportes SET 
                    inactivo = '.$inactivo.', 
                    comentario = "'.$comentario.'", 
                    rep_entr = "'.$entrenador.'", 
                    plantador = "'.$plantador.'", 
                    fechaInicio = "'.$fechaInicio.'", 
                    sitioReunion = '.$sitioReunion.', 
                    grupoMadre_txt = "'.$grupoMadre_txt.'", 
                    nombreGrupo_txt = "'.$nombreGrupo_txt.'",                     
                    capacitacion_txt = "'.$capacitacion_txt.'", 
                    generacionNumero = "'.$generacionNumero.'", 

                    pabellon = "'.$pabellon.'", 
                    direccion = "'.$direccion.'", 
                    ciudad = '.$ciudad.', 

                        asistencia_hom = "'.$asistencia_hom.'", 
                        asistencia_muj = "'.$asistencia_muj.'", 
                        asistencia_jov = "'.$asistencia_jov.'", 
                        asistencia_nin =  "'.$asistencia_nin.'", 

                    bautizados =  "'.$bautizados.'", 
                    bautizadosPeriodo = "'.$bautizadosPeriodo.'", 

                    asistencia_total = "'.$asistencia_total.'", 
                    discipulado = "'.$discipulado.'", 
                    desiciones =  "'.$desiciones.'",
                    rep_ndis =  "'.$rep_ndis.'", 
                    preparandose = "'.$preparandose.'",


                    mapeo_fecha = "'.$fechaFinal.'",
                    mapeo_comprometido = "'.$mapeo_comprometido.'",

                        mapeo_oracion = "'.$mapeo_oracion.'",
                        mapeo_companerismo = "'.$mapeo_companerismo.'",
                        mapeo_adoracion = "'.$mapeo_adoracion.'",
                        mapeo_biblia = "'.$mapeo_biblia.'",
                        mapeo_evangelizar = "'.$mapeo_evangelizar.'",
                        mapeo_cena = "'.$mapeo_cena.'",
                        mapeo_dar = "'.$mapeo_dar.'",
                        mapeo_bautizar = "'.$mapeo_bautizar.'",
                        mapeo_trabajadores = "'.$mapeo_trabajadores.'",

                    mapeo_anho = "'.$mapeo_anho.'",
                    mapeo_cuarto = "'.$mapeo_cuarto.'"';

    
                if($archivo1 != ""){
                    $sql .= ', ext1 = "'.$archivo1.'"';
                }

        
                if($archivo2 != ""){
                    $sql .= ', ext2 = "'.$archivo2.'"';
                }

        
                if($archivo3 != ""){
                    $sql .= ', ext3 = "'.$archivo3.'"';
                }


        $sql .= '   ,modificacionFecha = NOW(),
                    modificacionUsuario = "'.$_SESSION["id"].'"
                WHERE id = "'.$idReporteActual.'"';
                //echo $sql;
        $PSN1->query($sql);
        $num_grad_ant = 0;
        $act_grad_hv = $_FILES["act_grad_hv"];
        $act_grad_id = $_REQUEST['act_grad_id'];
        $act_grad_nom = $_REQUEST['act_grad_nom'];
        $act_grad_tar = $_REQUEST['act_grad_tar'];
        $num_grad_ant = $_REQUEST['grad_regist'];
        $num_grad_nue = $_REQUEST['total'];
        $sqlDel = "DELETE FROM tbl_adjuntos WHERE adj_rep_fk = ".$idReporteActual." AND adj_tip = 1 ";
        $PSN1->query($sqlDel);
        for ($i=0; $i < $num_grad_nue; $i++) {
            $tp_arch = extension_archivo($act_grad_hv['name'][$i]);
            $sqlA = "REPLACE INTO tbl_adjuntos (adj_id,adj_nom,adj_url,adj_fec,adj_can, adj_tip,adj_rep_fk)";
                $sqlA .= "VALUES (0".$act_grad_id[$i].",'".$act_grad_nom[$i]."','".$act_grad_tar[$i]."','".date('Y-m-d')."','archivos/hv_".$idReporteActual."_".$i.".".$tp_arch."',1,".$idReporteActual."); ";
            $extArchivo = $tp_arch;
                if($extArchivo == "png" || $extArchivo == "jpg" || $extArchivo == "jpeg" || $extArchivo == "gif"){
                    $rutaOr = $act_grad_hv['tmp_name'][$i];
                    $rutaDe = "archivos/hv_".$ultimoId."_".$i.".".$tp_arch;
                    compressImage($rutaOr, $rutaDe, 80);
                }else{
                    if(move_uploaded_file($act_grad_hv['tmp_name'][$i], "archivos/hv_".$idReporteActual."_".$i.".".$tp_arch)){
                    }            
                }
            //echo $sqlA;
            $PSN1->query($sqlA);
        }
        
        $varExitoREP_UPD = 1;
        //
        //
        //if($generacionNumero > 0){
                // Compress Image
                $ultimoId = $idReporteActual;
                //
                if($archivo1 != ""){
                    $extArchivo = $archivo1;
                    if($extArchivo == "png" || $extArchivo == "jpg" || $extArchivo == "jpeg" || $extArchivo == "gif"){
                        $rutaOrigen = $_FILES['archivo1']['tmp_name'];
                        $rutaDestino = "archivos/evi_".$ultimoId."_1.".$archivo1;
                        compressImage($rutaOrigen, $rutaDestino, 80);
                    }
                    else{
                        if(move_uploaded_file($_FILES['archivo1']['tmp_name'], "archivos/evi_".$ultimoId."_1.".$archivo1))
                        {
                        }            
                    }
                }
            

                if($archivo2 != ""){
                    $extArchivo = $archivo2;
                    if($extArchivo == "png" || $extArchivo == "jpg" || $extArchivo == "jpeg" || $extArchivo == "gif"){
                        $rutaOrigen = $_FILES['archivo2']['tmp_name'];
                        $rutaDestino = "archivos/evi_".$ultimoId."_2.".$archivo2;
                        compressImage($rutaOrigen, $rutaDestino, 80);
                    }
                    else{
                        if(move_uploaded_file($_FILES['archivo2']['tmp_name'], "archivos/evi_".$ultimoId."_2.".$archivo2))
                        {
                        }            
                    }
                }


                if($archivo3 != ""){
                    $extArchivo = $archivo3;
                    if($extArchivo == "png" || $extArchivo == "jpg" || $extArchivo == "jpeg" || $extArchivo == "gif"){
                        $rutaOrigen = $_FILES['archivo3']['tmp_name'];
                        $rutaDestino = "archivos/evi_".$ultimoId."_3.".$archivo3;
                        compressImage($rutaOrigen, $rutaDestino, 80);
                    }
                    else{
                        if(move_uploaded_file($_FILES['archivo3']['tmp_name'], "archivos/evi_".$ultimoId."_3.".$archivo3))
                        {
                        }            
                        else{
                            echo "Error";

                        }
                    }
                }
                //
            //}        
        
        //
    }
}


switch($error_datos){
    case 1:
        $texto_error = "Datos requeridos.";
        break;
    case 2:
        $texto_error = "Error no especificado.";
        break;
    case 3:
        $texto_error = "Ese REPORTE ya existe en el sistema para el grupo y lugar seleccionado.";
        break;
    default:
        break;
}

if($idReporteActual > 0){
    /*
    *   TRAEMOS LOS DATOS PRINCIPALES DEL USUARIO
    */
    $sql = "SELECT CA.descripcion AS zona,C.descripcion AS regional, U.nombre AS coordinador, U.id as id_coordinador, sat_reportes.*, sat_grupos.nombre, D.id_departamento,M.id_municipio FROM sat_reportes"; 
    $sql.=" LEFT JOIN sat_grupos ON sat_grupos.id = sat_reportes.idGrupoMadre 
LEFT JOIN dane_municipios AS M ON sat_reportes.ciudad = M.id_municipio 
LEFT JOIN dane_departamentos AS D ON M.departamento_id = D.id_departamento 
LEFT JOIN usuario AS U ON U.id = sat_reportes.idUsuario
LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
LEFT JOIN categorias AS C ON C.id = UE.empresa_pd
LEFT JOIN categorias AS CA ON CA.id = C.idSec";
    $sql.=" WHERE sat_reportes.id = '".$idReporteActual."'";
    $sql.=" GROUP BY sat_reportes.id";
    $PSN1->query($sql);
    //echo $sql;
    if($PSN1->num_rows() > 0)
    {
        if($PSN1->next_record())
        {
            $zona = $PSN1->f("zona");
            $regional = $PSN1->f("regional");
            $inactivo = $PSN1->f("inactivo");
            $regional = $PSN1->f("regional");
            $comentario = $PSN1->f("comentario");
            $plantador = $PSN1->f("plantador");
            $entrenador = $PSN1->f("rep_entr");
            $coordinador = $PSN1->f("coordinador");
            $id_coordinador = $PSN1->f("id_coordinador");
            $fechaReporte = $PSN1->f("fechaReporte");
            $fechaInicio = $PSN1->f("fechaInicio");        
            $sitioReunion = $PSN1->f("sitioReunion");
            $grupoMadre_txt = $PSN1->f("grupoMadre_txt");
            $nombreGrupo_txt = $PSN1->f("nombreGrupo_txt");
            
            $capacitacion_txt = $PSN1->f("capacitacion_txt");

            $pabellon = $PSN1->f("pabellon");
            $direccion = $PSN1->f("direccion");
            $municipio = $PSN1->f("ciudad");
            $departamento = $PSN1->f("id_departamento");
            $_SESSION['muni'] = $PSN1->f("ciudad");
            
            $ext1 = $PSN1->f("ext1");
            $ext2 = $PSN1->f("ext2");
            $ext3 = $PSN1->f("ext3");
            
            $idGrupoMadre = $PSN1->f("idGrupoMadre");
            $generacionNumero = $PSN1->f("generacionNumero");

            $asistencia_hom = $PSN1->f("asistencia_hom");
            $asistencia_muj = $PSN1->f("asistencia_muj");
            $asistencia_jov = $PSN1->f("asistencia_jov");
            $asistencia_nin = $PSN1->f("asistencia_nin");

            $bautizados = $PSN1->f("bautizados");
            $bautizadosPeriodo = $PSN1->f("bautizadosPeriodo");
            

            //Calculados:
            $asistencia_total  = $PSN1->f("asistencia_total");
            $discipulado  = $PSN1->f("discipulado");
            $desiciones  = $PSN1->f("desiciones");
            $rep_ndis  = $PSN1->f("rep_ndis");
            
            $preparandose  = $PSN1->f("preparandose");
            $iglesias_reconocidas = $PSN1->f("iglesias_reconocidas");  
            
            
            $fechaFinal = $PSN1->f("mapeo_fecha");
            $mapeo_cuarto = $PSN1->f("mapeo_cuarto");  
            $mapeo_comprometido = $PSN1->f("mapeo_comprometido");  
            
            $mapeo_oracion = $PSN1->f("mapeo_oracion");  
            $mapeo_companerismo = $PSN1->f("mapeo_companerismo");  
            $mapeo_adoracion = $PSN1->f("mapeo_adoracion");  
            $mapeo_biblia = $PSN1->f("mapeo_biblia");  
            $mapeo_evangelizar = $PSN1->f("mapeo_evangelizar");  
            $mapeo_cena = $PSN1->f("mapeo_cena");  
            $mapeo_dar = $PSN1->f("mapeo_dar");  
            $mapeo_bautizar = $PSN1->f("mapeo_bautizar");  
            $mapeo_trabajadores = $PSN1->f("mapeo_trabajadores");  
            
            
            //
        }//chequear el registro
    }else{
        ?><div class="row">
            <h3 class="alert alert-info text-center">Registro eliminado</h3>
        </div>
        <div class="form-group">
            <center><input type="button" onClick="window.location.href='index.php?doc=consultar-sub-programa-instituto-biblico'" name="previous" class="previous btn btn-danger" value="Cerrar" /> <br />
        </div>
        <?php
        exit;
    }
    $sql = "SELECT SUM(adj_can) as suma";
    $sql.=" FROM tbl_adjuntos ";
    $sql.=" WHERE adj_rep_fk = '".$idReporteActual."'";
    $PSN1->query($sql);
    if($PSN1->num_rows() > 0){
        if($PSN1->next_record()){
            $sum_baut = $PSN1->f("suma");
        }
    }
    ?><div class="container">
    <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form-horizontal">
        <h3 class="alert alert-info text-center"><?php
            if($idReporteActual == 0){
                echo "REPORTE";
            }else{
                echo "VISUALIZACIÓN";
                $sqlU = "SELECT SR.id FROM sat_reportes AS SR
                LEFT JOIN usuario AS U ON U.id = SR.idUsuario
                LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
                LEFT JOIN categorias AS C ON C.id = UE.empresa_pd
                WHERE SR.id = (SELECT MAX(STR.id)FROM sat_reportes AS STR WHERE STR.id < ".$idReporteActual.") ";
                    if ($_SESSION["empresa_pd"]!="" && $_SESSION["empresa_pd"]!=0) {
                        $sqlU .= "AND UE.empresa_pd = ".$_SESSION["empresa_pd"]." ";
                    }
                    $sqlU .= "AND SR.rep_tip = 317";
                $PSN1->query($sqlU); 
                if($PSN1->num_rows() > 0){
                    if($PSN1->next_record()){
                    $antId  = $PSN1->f('id');
                    }
                }else{
                   $antId  = 0; 
                }
                $sqlU = "SELECT SR.id FROM sat_reportes AS SR
                LEFT JOIN usuario AS U ON U.id = SR.idUsuario
                LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
                LEFT JOIN categorias AS C ON C.id = UE.empresa_pd
                WHERE SR.id = (SELECT MIN(STR.id)FROM sat_reportes AS STR WHERE STR.id > ".$idReporteActual.") ";
                    if ($_SESSION["empresa_pd"]!="" && $_SESSION["empresa_pd"]!=0) {
                        $sqlU .= "AND UE.empresa_pd = ".$_SESSION["empresa_pd"]." ";
                    }
                    $sqlU .= "AND SR.rep_tip = 317";
                $PSN1->query($sqlU);
                //echo  $sqlU;
                if($PSN1->num_rows() > 0){
                    if($PSN1->next_record()){
                    $sigId  = $PSN1->f('id');
                    }
                }else{
                   $sigId  = 0; 
                }              
            }
            
            ?> DE <?=$temp_letrero; ?></h3>
            <?php //if ($_SESSION["perfil"] == 162 || $_SESSION["perfil"] == 2){ ?>
            <div class="cont-btn cont-flex fl-sbet">
                <div class="item-btn">
                    <?php
                    if ($antId != 0) {?>
                    <a href="index.php?doc=gestionar-sub-programa-instituto-biblico&id=<?=$antId ?>" name="previous" class="previous btn btn-info">Anterior reporte <?=$antId ?></a>
                    <?php } ?>
                </div>
                <div class="item-btn">
                    <a href="index.php?doc=consultar-sub-programa-instituto-biblico" name="previous" class="btn btn-warning">Todos los reportes</a>
                </div>
                <div class="item-btn">
                    <?php
                    if ($sigId != 0) {?>
                    <a href="index.php?doc=gestionar-sub-programa-instituto-biblico&id=<?=$sigId ?>" name="previous" class="previous btn btn-info">Siguiente reporte <?=$sigId ?></a>
                    <?php } ?>
                </div>
            </div>
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center">INFORMACIÓN GENERAL</h3>
                <h5>REGISTRO ID: <?=str_pad($idReporteActual, 6, "0", STR_PAD_LEFT); ?></h5>
            </div>
            <div class="hr"><hr></div>
        </div> 
        <?php 
            $fecha_actual = date("Y-m-d");
            $fechLimite = date("Y-m-d",strtotime($fecha_actual."- 90 days"));
            //echo $fechLimite ." - ". $fechaReporte;
        ?>      
        <div class="form-group">
            <div class="col-sm-1"></div>
            <div class="col-sm-2">
                <strong>Fecha del registro:</strong>
                <input name="fechaReporte" type="date" id="fechaReporte" maxlength="250" value="<?=$fechaReporte; ?>" class="form-control" required readonly  />
            </div>
            <div class="col-sm-2">
                <strong>Fecha de inicio del diplomado:</strong>
                <input name="fechaInicio" type="date" id="fechaInicio" max='<?=date("Y-m-d"); ?>' class="form-control" value="<?=$fechaInicio; ?>" required  />
            </div>
            <div class="col-sm-2">
                <strong>Fecha final del diplomado:</strong>
                <input name="fechaFinal" type="date" id="fechaFinal" max='<?=date("Y-m-d"); ?>' class="form-control" value="<?=$fechaFinal; ?>" required  />
            </div>
            <div class="col-sm-4">
                <strong>Diplomado:</strong>
                <select name="rep_ndis" class="form-control" required>
                    <option value="">Seleccione el diplomado</option>
                    <?php
                        $sql = "SELECT * ";
                        $sql.=" FROM categorias AS C";
                        $sql.=" WHERE C.idSec = 78 ";
                        $PSN2->query($sql);
                        $numero_cat=$PSN2->num_rows();
                        if($numero_cat > 0){
                            while($PSN2->next_record()){
                                ?><option value="<?=$PSN2->f('id'); ?>" <?php echo($PSN2->f('id')==$rep_ndis)?"selected":""; ?> >
                                    <?=$PSN2->f('descripcion'); ?></option>
                            <?php }
                        }
                        ?>
                    
                </select>
            </div>
        </div>   
        <div class="form-group">
            <div class="col-sm-1"></div>
            <div class="col-sm-3">
                <strong>Profesor:</strong>
                <select required readonly name="usua_id" id="usua_id" class="form-control">
                    <option value="<?=$id_coordinador; ?>"><?=$coordinador; ?></option>
                </select>
            </div> 
            <div class="col-sm-2">
                <strong>Zona:</strong>
                <input name="zona" type="text" id="zona" maxlength="250" value="<?=$zona; ?>" class="form-control" readonly required />
            </div>
            <div class="col-sm-2">
                <strong>Regional:</strong>
                <input name="regional" type="text" id="regional" maxlength="250" value="<?=$regional; ?>" class="form-control" readonly required />
            </div>      
            <div class="col-sm-3">
                <strong>Cárcel ubicación: </strong>
                <select required name="sitioReunion" id="rep_carcel" class="form-control">        
                    <?php
                    /*
                    *   TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                    */
                    if ($_SESSION['empresa_pd'] != "") {
                        echo '<option value="">Sin especificar</option>';
                        $sql = "SELECT * ";
                       $sql.=" FROM tbl_regional_ubicacion ";
                        if($_SESSION['empresa_pd'] != 0){
                            $sql.=" WHERE reub_reg_fk = ".$_SESSION['empresa_pd'];
                        }
                        $sql.=" ORDER BY reub_reg_fk asc";

                        $PSN1->query($sql);
                        $numero=$PSN1->num_rows();
                        if($numero > 0){
                            while($PSN1->next_record()){
                                ?><option value="<?=$PSN1->f('reub_id'); ?>" <?php
                                if($sitioReunion == $PSN1->f('reub_id'))
                                {
                                    ?>selected="selected"<?php
                                }
                                ?>><?=$PSN1->f('reub_nom'); ?></option><?php
                            }
                        }
                    }else{
                        echo '<option value="">Sin regional asignada</option>';
                    }
                    ?>
                </select>
            </div>     
        </div>
        <div class="form-group">
            <div class="col-sm-1"></div>              
            <div id="ubicacion"></div>
            <div class="col-sm-3">
                <strong>Patio en el que se realizó el diplomado:</strong>
                <input name="pabellon" type="number" id="pabellon" maxlength="250" value="<?=$pabellon; ?>" class="form-control" required />
            </div> 
        </div>
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3>INFORMACIÓN DEL CURSO</h3>
            </div>
            <div class="hr"><hr></div>
        </div>
        <div class="form-group">
            <div class="col-sm-1"></div> 
            <div class="col-sm-3">
                <strong>Número de prisioneros invitados al diplomado:</strong>
                <input name="asistencia_total" type="number" id="asistencia_total" value="<?=$asistencia_total; ?>" class="form-control" required />
            </div>
            <div class="col-sm-4">
                <strong>Número de prisioneros inscritos en el diplomado:</strong>
                <input name="asistencia_hom" type="number" id="asistencia_hom" value="<?=$asistencia_hom; ?>" class="form-control" required  />
            </div>
            <div class="col-sm-3">
                <strong>Número de prisioneros que iniciaron el diplomado:</strong>
                <input name="asistencia_muj" type="number" id="asistencia_muj" value="<?=$asistencia_muj; ?>" class="form-control" required />
            </div>
        </div>
        <!--MODIFICAR REGISTRO DE GRADUADOS--->
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center">INFORMACIÓN DE GRADUADOS</h3>
                <p>A continuación por favor ingrese los datos requeridos</p>               
            </div>
            <div class="hr"><hr></div>
        </div>
        <div class="form-group">
            <div class="col-sm-1"></div>
            <div class="col-sm-10">
                <script>
                        $(function(){
                            var total = <?= $asistencia_nin; ?>;
                            var tar = $(".act_grad_tar").val();
                            var nom = $(".act_grad_nom").val();

                            //$("#asistencia_total").prop('required',true);

                            if (tar == "" || nom == "") {
                                $("#adicionarAdd").prop( "disabled", true );
                            }else{
                                <?php if($_SESSION['perfil']=="168" || $fechLimite > $fechaReporte){ ?>
                                $("#adicionarAdd").prop( "disabled", true );
                            <?php }else{ ?>
                                $("#adicionarAdd").prop( "disabled", false );
                            <?php } ?>
                            }
                            var vtotal = $("#asistencia_total").val();
                            $("#asistencia_hom").attr('max', (vtotal));

                            var vtotal = $("#asistencia_hom").val();
                            $("#asistencia_muj").attr('max', vtotal);

                            $("#asistencia_hom").change(function(){
                                var vtotal = $("#asistencia_hom").val();
                                $("#asistencia_muj").attr('max', vtotal);
                            });
                            $("#asistencia_total").change(function(){
                                var vtotal = $("#asistencia_total").val();
                                $("#asistencia_hom").attr('max', (vtotal));
                            });
                            $("#asistencia_muj").change(function(){
                                var vtotal = $("#asistencia_muj").val();
                                if (total >= vtotal) {
                                    $("#adicionarAdd").prop( "disabled", true );
                                }else{
                                    $("#adicionarAdd").prop( "disabled", false );
                                }
                            });
                            
                            $(".act_grad_nom").change(function(){
                                var vtotal = $("#asistencia_muj").val();
                                var tar3 = $(".act_grad_tar").val();
                                var nom3 = $(".act_grad_nom").val();
                                if (tar3 != "" && nom3 !="") {
                                    if (total < 1) {
                                        total = total + 1;
                                    }
                                    $("#adicionarAdd").prop( "disabled", false );
                                }else if (tar3 == "" && nom3 =="") {
                                    if (total > 0) {
                                        total = total - 1;
                                        $(".act_grad_nom").prop('required',false);
                                        $(".act_grad_tar").prop('required',false);
                                    }
                                }else{
                                    $("#adicionarAdd").prop( "disabled", true );
                                }
                                $('#total').val(total);
                            });
                            $(".act_grad_tar").change(function(){
                                var vtotal = $("#asistencia_muj").val();
                                var nom2 = $(".act_grad_nom").val();
                                var tar2 = $(".act_grad_tar").val();
                                if (nom2 != ""&& tar2 != "") {
                                    if (total < 1) {
                                        total = total + 1;
                                    }
                                    $("#adicionarAdd").prop( "disabled", false );
                                }else if (tar3 == "" && nom3 =="") {
                                    if (total > 0) {
                                        total = total - 1;
                                        $(".act_grad_nom").prop('required',false);
                                        $(".act_grad_tar").prop('required',false);
                                    }
                                }else{
                                    $("#adicionarAdd").prop( "disabled", true );
                                }
                                $('#total').val(total);
                            });

                            $("#adicionarAdd").on('click',function(){
                                $("#tablaAdd tbody tr:last").clone().removeClass('fila-fijaAdd').appendTo("#tablaAdd");
                                $("#tablaAdd tbody tr input.act_grad_nom:last").val('');
                                $("#tablaAdd tbody tr input.act_grad_tar:last").val('');
                                $("#tablaAdd tbody tr input.act_grad_hv:last").val('');
                                var vtotal = $("#asistencia_muj").val();
                                var tar2 = $(".act_grad_tar").val();
                                var nom2 = $(".act_grad_nom").val();
                                if (tar2!="" && nom2!="") {
                                    total = total + 1;
                                }
                                if (total >= vtotal) {
                                    $("#adicionarAdd").prop( "disabled", true );
                                }else{
                                    $("#adicionarAdd").prop( "disabled", false );
                                }
                                $(".act_grad_nom").prop('required',true);
                                $(".act_grad_tar").prop('required',true);
                                $('#total').val(total);
                            });
                            $(document).on("click",".eliminarAdd",function(){
                                var vtotal = $("#asistencia_muj").val();
                                var parent = $(this).parents().get(0);
                                $(parent).remove();
                                total = total - 1;
                                $('#total').val(total);
                                if (total >= vtotal) {
                                    $("#adicionarAdd").prop( "disabled", true );
                                }else{
                                    $("#adicionarAdd").prop( "disabled", false );
                                }
                            });
                            
                        });
                    </script>
                <table id="tablaAdd">
                    <?php 
                    $sql = "SELECT * ";
                    $sql.=" FROM tbl_adjuntos ";
                    $sql.=" WHERE adj_rep_fk = '".$idReporteActual."' AND adj_tip = 1 ";
                    $PSN1->query($sql);
                    $numero=$PSN1->num_rows();
                    $cont = 0;
                    echo '<input type="hidden" name="grad_regist" value="'.$numero.'" placeholder="">';
                    if($numero > 0){
                        while($PSN1->next_record()){ ?>
                            <input type="hidden" name="act_grad_id[]" value="<?= $PSN1->f("adj_id");  ?>">
                            <tr <?php echo($cont==0)?'class="fila-fijaAdd"':''; ?>>
                                <td class="col-sm-7">
                                   
                                    <strong>Nombre completo del graduado:</strong>
                                    <input name="act_grad_nom[]" type="text" id="act_grad_nom" class="act_grad_nom form-control" value="<?=$PSN1->f("adj_nom"); ?>" required />
                                </td>
                                <td class="col-sm-4">
                                    <strong>Tarjeta dactilar / N° identificación:</strong>
                                    <input name="act_grad_tar[]" type="text" id="act_grad_tar" min="0" class="act_grad_tar form-control" value="<?=$PSN1->f("adj_url"); ?>" required />
                                </td>
                                <td class="col-sm-4">
                                    <strong>Hoja de vida:</strong>
                                    <?php
                                    if($PSN1->f("adj_can") == ""){
                                        ?><div class='alert alert-danger' style="margin-bottom: 0px !important;">Sin archivo adjunto</div><?php
                                    }else{?>
                                        <a href="<?=$PSN1->f("adj_can"); ?>" target="_blank"><i class="fas fa-file-pdf"></i> Ver hoja de vida</a>
                                    <?php }?><br>
                                    <strong>Adjuntar archivo:</strong>
                                    <input name="act_grad_hv[]" type="file" id="act_grad_hv" min="0" class="act_grad_hv form-control"/>
                                </td>
                                <td class="eliminarAdd"><br><button type="button" class="btn btn-cir-uno usua-col"><i class="fa fa-times"></i></button></td>
                            </tr>
                        <?php $cont++;
                        }
                    }else{ ?>
                        
                        <tr class="fila-fijaAdd">
                            <td class="col-sm-7">
                                <strong>Nombre completo del graduado:</strong>
                                <input name="act_grad_nom[]" type="text" id="act_grad_nom" class="act_grad_nom form-control"  />
                            </td>
                            <td class="col-sm-4">
                                <strong>Tarjeta dactilar / N° identificación:</strong>
                                <input name="act_grad_tar[]" type="text" id="act_grad_tar" min="0" class="act_grad_tar form-control" />
                            </td>
                            <td class="col-sm-4">
                                    <strong>Hoja de vida:</strong>
                                    <input name="act_grad_hv[]" type="file" id="act_grad_hv" min="0" class="act_grad_hv form-control"  />
                                </td>
                            <td class="eliminarAdd"><br><button type="button" class="btn btn-cir-uno usua-col"><i class="fa fa-times"></i></button></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
            <div class="col-sm-2"></div>
        </div>
        <div class="form-group">
            <div class="col-sm-2"></div>
            <div class="col-sm-8">
                <div class="col-sm-4"><strong>Número total de graduados:</strong> </div>
                <div class="col-sm-2">
                    <input type="text" name="total" id="total" class="form-control" value="<?=$asistencia_nin; ?>" readonly>
                </div>
                <div class="col-sm-4"></div>
                <div class="col-sm-2">
                    <center>
                        <button id="adicionarAdd" class="btn btn-success" type="button" class="boton"><i class="fas fa-plus"></i>  Adicionar</button>
                    </center>
                </div>
            </div>
        </div>
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center">Método de verificación</h3>
                <h5>FOTO Y TESTIMONIO</h5>
            </div>
            <div class="hr"><hr></div>
        </div>
        <div class="form-group">
            <div class="col-sm-3"></div>
            <div class="col-sm-6">
                <strong>Foto:</strong>
                <?php if ($ext1!=""){?>
                    <center><img src="archivos/evi_<?=$idReporteActual; ?>_1.<?=$ext1; ?>" style="max-height:250px; max-width: 100%; "></center><br>  
                <?php } ?>                
                <input name="archivo1" type="file" id="archivo1" class="form-control" />
            </div>
            <div class="col-sm-3"></div>
        </div>
        <div class="form-group">
            <div class="col-sm-3"></div>
            <div class="col-sm-6">
                <strong>Testimonio:</strong>
                <textarea name="rep_text1" id="rep_text1" style="width: 100%; max-width: 100%; min-width: 100%;" class="form-control"><?php echo $comentario; ?></textarea>
            </div>
            <div class="col-sm-3"></div>
        </div>
        <?php if ($_SESSION['perfil']!="168") {?>
        <div class="cont-btn cont-flex fl-sbet">
            <div class="item-btn">
                <input type="button" onClick="window.location.href='index.php?doc=consultar-sub-programa-instituto-biblico'" name="previous" class="previous btn btn-info" value="Cerrar" />
            </div>
            <div class="item-btn">
                <input type="submit" name="button" value="Guardar cambios" class="btn btn-success" id="guarda_rep">
            </div>
            <div class="item-btn">
                <input type="button" onClick="eliminarRegistro()" name="button" value="Eliminar" class="btn btn-danger">
            </div>
        </div>
        <?php } ?>            
        <input type="hidden" name="funcion" id="funcion" value="actualizar" />
        <input type="hidden" name="generacion" id="generacion" value="<?=$generacionActual; ?>" />
    </form>
    <script language="javascript">          
        
        function eliminarRegistro(){
            if(confirm("Esta seguro que desea eliminar este registro, esta acción NO se puede deshacer.")){
                document.getElementById('funcion').value = "eliminar";
                document.getElementById('form1').submit();
            }                
        }
        
        function generarForm(generacion){
            //sumar();
            <?php
            //if($_SESSION["perfil"] == 163){
                ?>
                $(':input[type="submit"]').prop('disabled', true);
                document.getElementById('funcion').value = "actualizar";
                //Completo el formulario  
                //document.getElementById('form1').submit();
                return true;
            <?php
            //}
            //else{
            //    /* //return false; */
            //}
            ?>
        }            

        function init(){
            document.getElementById('form1').onsubmit = function(){
                    return generarForm();
            }

        }        
        //
        window.onload = function(){
            init();
        }
    </script>
<?php }else if($preguntarGeneracion == 1){?>
    <script language="javascript">
        generarForm('IB');
        function generarForm(generacion){
            if(generacion == "IB"){
                document.getElementById('generacion').value = "IB";
            }
            //Completo el formulario  
          document.getElementById('form1').submit();
        }            
        
        function init(){
            document.getElementById('form1').onsubmit = function(){
                    return generarForm();
            }
        }        
        window.onload = function(){
            init();
        }
    </script>        
<?php }else if(!isset($_REQUEST["id"])){
    $temp_accionForm = "insertar";
    $idGrupoMadre = soloNumeros($_REQUEST["idGrupoMadre"]);
    //
    if(!isset($_REQUEST["fechaReporte"])){
        $fechaReporte = date("Y-m-d");        
    }else{
        $fechaReporte = eliminarInvalidos($_REQUEST["fechaReporte"]);
    }
    //
    $sql = "SELECT sat_grupos.nombre ";
    $sql.=" FROM sat_grupos ";
    $sql.=" WHERE sat_grupos.id = '".$idGrupoMadre."'";
    $sql.=" GROUP BY sat_grupos.id";
    $PSN1->query($sql);
    if($PSN1->num_rows() > 0)
    {
        if($PSN1->next_record())
        {
            $nombreGrupoMadre =  $PSN1->f("nombre");
        }//chequear el registro
    }//chequear el numero
}else{
    $temp_accionForm = "actualizar";
    //  ID del usuario actual
    $idReporteActual = soloNumeros($_REQUEST["id"]);   
}
/*
*   SI SE INSERTO REGISTRO SE REDIRIGE
*/
if($idReporteActual > 0){
    //No hacemos nada.
}else if($varExitoREP == 1){?>
    <div class="container">
        <div class="row">
            <h2 class="alert alert-info text-center"><?php
            if($idReporteActual == 0){
                echo "REPORTE";
            }
            else{
                echo "ACTUALIZACIÓN";
            }
            ?> DE <?=$temp_letrero; ?></h2>
        </div>

        <div class="row">
            <h2 class="alert alert-success text-center"><a href="index.php?doc=gestionar-sub-programa-instituto-biblico&opc=2&id=<?=$ultimoId; ?>" class="h2">Se ha <?php
            if($idReporteActual == 0){
                echo "creado";
            }
            else{
                echo "actualizado";
            }
            ?> correctamente el registro, para ver el reporte de clic aquí</a>.</h2>
        </div>
    </div>   
<?php }else if($idReporteActual == 0){?>
    <style type="text/css">
        #form1 fieldset:not(:first-of-type){
            display: none;
        }
    </style>
<div class="container">
    <div class="row">
        <h3 class="alert alert-info text-center"><?php
            if($idReporteActual == 0){
                echo "REPORTE";
            }else{
                echo "ACTUALIZACIÓN";
            }?> DE <?=$temp_letrero; ?></h3>
    </div>

    <?php
    //
    if($varExitoREP_UPD == 1){
        ?><div class="row">
            <h5 class="alert alert-warning text-center">Se ha actualizado correctamente el registro.</h5>
        </div><?php
    }
    //
    if($texto_error != ""){
        ?><div class="row">
            <h5 class="alert alert-danger text-center"><?=$texto_error; ?></h5>
        </div><?php
    }

    //
    if($errorLogueo == 1){
        ?><div class="row"><h1><font color="red"><u>ATENCION:</u> NO SE CREO EL INFORME<BR /><u>MOTIVO:</u> YA EXISTE UN INFORME CON ESE VEHÍCULO Y FECHA.<br />POR FAVOR VERIFIQUE.</font></h1></div><?php
    }
    //
    if($error_fatal == 1){
        //No hacer nada.
    }else{?>
    <!-----FORMULARIO DE REGISTO INSTITUTO BIBLICO---->
        <div class="progress">
            <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form-horizontal">
            <input name="fechaReporte" type="hidden" id="fechaReporte" value="<?=$fechaReporte; ?>" />
            <!--<fieldset>--><div class="col-sm-12">
                <div class="cont-tit">
                    <div class="hr"><hr></div>
                    <div class="tit-cen">
                        <h3 class="text-center">Información general</h3>
                    <p>A continuación por favor ingrese los datos requeridos</p>
                    </div>
                    <div class="hr"><hr></div>
                </div> 
                <div class="form-group">
                    <div class="col-sm-1"></div>
                    <div class="col-sm-2">
                        <strong>Fecha del registro:</strong>
                        <input name="fechaReporte" type="date" id="fechaReporte" maxlength="250" value="<?=date("Y-m-d"); ?>" max='<?=date("Y-m-d"); ?>' class="form-control"  required readonly  />
                    </div>
                    <div class="col-sm-2">
                        <strong>Fecha de inicio del diplomado:</strong>
                        <input name="fechaInicio" type="date" id="fechaInicio" max='<?=date("Y-m-d"); ?>' class="form-control"  required  />
                    </div>
                    <div class="col-sm-2">
                        <strong>Fecha final del diplomado:</strong>
                        <input name="fechaFinal" type="date" id="fechaFinal" max='<?=date("Y-m-d"); ?>' class="form-control"  required  />
                    </div>
                    <div class="col-sm-4">
                        <strong>Diplomado:</strong>
                        <select name="rep_ndis" class="form-control" required>
                            <option value="">Seleccione el diplomado</option>
                            <?php
                                $sql = "SELECT * ";
                                $sql.=" FROM categorias AS C";
                                $sql.=" WHERE C.idSec = 78 ";
                                $PSN2->query($sql);
                                $numero_cat=$PSN2->num_rows();
                                if($numero_cat > 0){
                                    while($PSN2->next_record()){
                                        ?><option value="<?=$PSN2->f('id'); ?>" <?php echo($PSN2->f('id')==$PSN1->f("adj_can"))?"selected":""; ?> >
                                            <?=$PSN2->f('descripcion'); ?></option>
                                    <?php }
                                }
                                ?>
                            
                        </select>
                    </div>
                </div> 
                <div class="form-group">
                    <div class="col-sm-1"></div>
                    <div class="col-sm-3">
                        <strong>Profesor:</strong>
                        <select required name="usua_id" id="usua_id" class="form-control">
                            <option value="<?=$_SESSION["id"]; ?>"><?=$_SESSION["nombre"]; ?></option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <strong>Zona:</strong>
                        <input name="zona" type="text" id="zona" maxlength="250" value="<?=$_SESSION["usua_zona"]; ?>" class="form-control" readonly required />
                    </div>
                    <div class="col-sm-3">
                        <strong>Regional:</strong>
                        <input name="regional" type="text" id="regional" maxlength="250" value="<?=$_SESSION["usua_regional"]; ?>" class="form-control" readonly required />
                    </div>
                    <div class="col-sm-2">
                        <strong>Cárcel ubicación:</strong>
                        <select required name="sitioReunion" id="rep_carcel" class="form-control">
                                        
                            <?php
                            /*
                            *   TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                            */
                            if ($_SESSION['empresa_pd'] != "") {
                                echo '<option value="">Sin especificar</option>';
                                $sql = "SELECT * ";
                                $sql.=" FROM tbl_regional_ubicacion ";
                                if($_SESSION['empresa_pd'] != 0){
                                    $sql.=" WHERE reub_reg_fk = ".$_SESSION['empresa_pd'];
                                }
                                $sql.=" ORDER BY reub_reg_fk asc";

                                $PSN1->query($sql);
                                $numero=$PSN1->num_rows();
                                if($numero > 0){
                                    while($PSN1->next_record()){
                                        ?><option value="<?=$PSN1->f('reub_id'); ?>" <?php
                                        if($cliente_servicio1 == $PSN1->f('reub_id'))
                                        {
                                            ?>selected="selected"<?php
                                        }
                                        ?>><?=$PSN1->f('reub_nom'); ?></option><?php
                                    }
                                }
                            }else{
                                echo '<option value="">Sin regional asignada</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-1"></div>
                    <div id="ubicacion"></div>
                    <div class="col-sm-3">
                        <strong>Patio en el que se realizó el diplomado:</strong>
                        <input name="pabellon" type="number" id="pabellon" maxlength="250" value="<?=$pabellon; ?>" class="form-control" required />
                    </div> 
                </div>
                <!--<div class="cont-btn cont-flex fl-sbet">
                    <div class="item-btn"></div>
                    <div class="item-btn">
                        <input type="button" name="next" class="next btn btn-success" id="secc-1" value="Siguiente" />
                    </div>
                </div>          
            </fieldset>--></div>
        <!--INFORMACIÓN DE LA PRISION-->
            <!--<fieldset>--><div class="col-sm-12">
                
                <div class="cont-tit">
                    <div class="hr"><hr></div>
                    <div class="tit-cen">
                        <h3 class="text-center">Información de graduados</h3>
                        <p>A continuación por favor ingrese los datos requeridos</p>
                    </div>
                    <div class="hr"><hr></div>
                </div>
                <div class="form-group">
                    <div class="col-sm-1"></div>
                    <div class="col-sm-3">
                        <strong>Número de prisioneros invitados al diplomado:</strong>
                        <input name="asistencia_total"  type="number" id="asistencia_total" min="0" value="" class="form-control" />
                    </div> 
                    <div class="col-sm-3">
                        <strong>Número de prisioneros inscritos en el diplomado:</strong>
                        <input name="asistencia_hom" type="number" id="asistencia_hom" min="0" value="" class="form-control"  />
                    </div>
                    <div class="col-sm-3">
                        <strong>Número de prisioneros que iniciaron el diplomado:</strong>
                        <input name="asistencia_muj" type="number" id="asistencia_muj" min="0" value="" class="form-control" />
                    </div>    
                </div>
                <!--<div class="cont-btn cont-flex fl-sbet">
                    <div class="item-btn">
                        <input type="button" name="previous" class="previous btn btn-info" value="Anterior" />
                    </div>
                    <div class="item-btn">
                        <input type="button" name="next" id="archivo1_sig2" class="next btn btn-success" value="Siguiente" />
                    </div>
                </div>
            </fieldset>--></div>
        <!--REGISTRO DE GRADUADOS--->
            <!--<fieldset>--><div class="col-sm-12">
                <div class="cont-tit col-sm-12">
                    <div class="hr"><hr></div>
                    <div class="tit-cen">
                        <h3 class="text-center">REGISTRO DE GRADUADOS</h3>
                        <p>A continuación por favor ingrese los datos requeridos</p>
                    </div>
                    <div class="hr"><hr></div>
                </div>
                <div class="form-group">
                    <div class="col-sm-1"></div>
                    <div class="col-sm-10">
                        <script>
                            $(function(){
                                var total = 0;
                                var tar = $(".act_grad_tar").val();
                                var nom = $(".act_grad_nom").val();
                                if (tar == "" || nom == "") {
                                    $("#adicionarAdd").prop( "disabled", true );
                                }else{
                                    $("#adicionarAdd").prop( "disabled", false );
                                }
                                
                                $("#asistencia_hom").change(function(){
                                    var vtotal = $("#asistencia_hom").val();
                                    $("#asistencia_muj").attr('max', vtotal);
                                });
                                $("#asistencia_total").change(function(){
                                    var vtotal = $("#asistencia_total").val();
                                    $("#asistencia_hom").attr('max', (vtotal));
                                });
                                
                                $("#asistencia_muj").change(function(){
                                    var vtotal = $("#asistencia_muj").val();
                                    if (total >= vtotal) {
                                        $("#adicionarAdd").prop( "disabled", true );
                                    }else{
                                        $("#adicionarAdd").prop( "disabled", false );
                                    }
                                });
                                
                                
                                $(".act_grad_tar").change(function(){
                                    var vtotal = $("#asistencia_muj").val();
                                    var nom2 = $(".act_grad_nom").val();
                                    var tar2 = $(".act_grad_tar").val();
                                    if (nom2 != "" && tar2 != "") {
                                        if (total < 1) {
                                            total = total + 1;
                                        }
                                        $("#adicionarAdd").prop( "disabled", false );
                                    }else if (tar2 == "" || nom2 =="") {
                                        if (total > 0) {
                                            total = total - 1;
                                            $(".act_grad_nom").prop('required',false);
                                            $(".act_grad_tar").prop('required',false);
                                        }
                                        $("#adicionarAdd").prop( "disabled", true );
                                    }
                                    $('#total').val(total);
                                });
                                $(".act_grad_nom").change(function(){
                                    var vtotal = $("#asistencia_muj").val();
                                    var tar3 = $(".act_grad_tar").val();
                                    var nom3 = $(".act_grad_nom").val();
                                    if (tar3 != "" && nom3 != "") {
                                        if (total < 1) {
                                            total = total + 1;
                                        }
                                        $("#adicionarAdd").prop( "disabled", false );
                                    }else if (tar3 == "" || nom3 == "") {
                                        if (total > 0) {
                                            total = total - 1;
                                            $(".act_grad_nom").prop('required',false);
                                            $(".act_grad_tar").prop('required',false);
                                        }
                                        $("#adicionarAdd").prop( "disabled", true );
                                    }
                                    $('#total').val(total);
                                });

                                $("#adicionarAdd").on('click',function(){
                                    $("#tablaAdd tbody tr:last").clone().removeClass('fila-fijaAdd').appendTo("#tablaAdd");
                                    $("#tablaAdd tbody tr input.act_grad_nom:last").val('');
                                    $("#tablaAdd tbody tr input.act_grad_tar:last").val('');
                                    $("#tablaAdd tbody tr input.act_grad_hv:last").val('');
                                    var vtotal = $("#asistencia_muj").val();
                                    var tar2 = $(".act_grad_tar").val();
                                    var nom2 = $(".act_grad_nom").val();

                                    if (tar2!="" && nom2!="") {
                                        total = total + 1;
                                    }
                                    if (total >= vtotal) {
                                        $("#adicionarAdd").prop( "disabled", true );
                                    }else{
                                        $("#adicionarAdd").prop( "disabled", false );
                                    }
                                    $(".act_grad_nom").prop('required',true);
                                    $(".act_grad_tar").prop('required',true);
                                    $('#total').val(total);
                                });
                                $(document).on("click",".eliminarAdd",function(){
                                    var vtotal = $("#asistencia_muj").val();
                                    var parent = $(this).parents().get(0);
                                    $(parent).remove();
                                    if (total > 0) {
                                        total = total - 1;
                                    }
                                    $('#total').val(total);
                                    if (total >= vtotal) {
                                        $("#adicionarAdd").prop( "disabled", true );
                                    }else{
                                        $("#adicionarAdd").prop( "disabled", false );
                                    }
                                });
                                
                            });
                        </script>
                        <table id="tablaAdd">
                            <tr class="fila-fijaAdd">
                                <td class="col-sm-7">
                                    <strong>Nombre completo del graduado:</strong>
                                    <input name="act_grad_nom[]" type="text" id="act_grad_nom" class="act_grad_nom form-control" />
                                </td>
                                <td class="col-sm-4">
                                    <strong>Tarjeta dactilar / N° identificación:</strong>
                                    <input name="act_grad_tar[]" type="text" id="act_grad_tar" min="0" class="act_grad_tar form-control" />
                                </td>
                                <td class="col-sm-4">
                                    <strong>Hoja de vida:</strong>
                                    <input name="act_grad_hv[]" type="file" id="act_grad_hv" min="0" class="act_grad_hv form-control" />
                                </td>
                                <td class="eliminarAdd"><br><button type="button" class="btn btn-cir-uno usua-col"><i class="fa fa-times"></i></button></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-sm-2"></div>
                </div>
                <div class="form-group">
                    <div class="col-sm-2"></div>
                    <div class="col-sm-8">
                        <div class="col-sm-4"><strong>Número total de graduados:</strong> </div>
                        <div class="col-sm-2">
                            <input type="text" name="total" id="total" class="form-control" value="" readonly>
                        </div>
                        <div class="col-sm-4"></div>
                        <div class="col-sm-2">
                            <center>
                                <button id="adicionarAdd" class="btn btn-success" type="button" class="boton"><i class="fas fa-plus"></i>  Adicionar</button>
                            </center>
                        </div>
                    </div>
                <!--<div class="cont-btn cont-flex fl-sbet">
                    <div class="item-btn">
                        <input type="button" name="previous" class="previous btn btn-info" value="Anterior" />
                    </div>
                    <div class="item-btn">
                        <input type="button" name="next" id="archivo1_sig" class="next btn btn-success" value="Siguiente" />
                    </div>
                </div>
            </fieldset>--></div>
        
        <!---TESTIMONIO--->
            <!--<fieldset>--><div class="col-sm-12">
                <div class="cont-tit col-sm-12">
                    <div class="hr"><hr></div>
                    <div class="tit-cen">
                        <h3 class="text-center">Método de verificación</h3>
                        <h5>Foto y resumen</h5>
                    </div>
                    <div class="hr"><hr></div>
                </div>
                <div class="form-group">
                    <div class="col-sm-4"></div>
                    <div class="col-sm-4">
                        <strong>Foto:</strong>
                        <input name="archivo1" type="file" id="archivo1" class="form-control" />
                    </div>
                    <div class="col-sm-4"></div>
                </div>
                <div class="form-group">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6">
                        <strong>Resumen de su experiencia con este grupo:</strong>
                        <textarea name="rep_text1" id="rep_text1" style="width: 100%; max-width: 100%; min-width: 100%;" class="form-control"></textarea>
                    </div>
                    <div class="col-sm-3"></div>
                </div>
                <div class="cont-btn cont-flex fl-cent">
                    <!--<div class="item-btn">
                        <input type="button" name="previous" class="previous btn btn-info" value="Anterior" />
                    </div>--> 
                    <div class="item-btn">
                        <input type="submit" name="button" value="Guardar" class="btn btn-success">
                    </div>
                </div>
            <!--</fieldset>--></div> 
            
            <input type="submit" name="button-hidden" id="button-hidden" style="display:none">
            <input type="hidden" name="funcion" id="funcion" value="insertar" />
            <input type="hidden" name="generacion" id="generacion" value="<?=$generacionActual; ?>" />
        </form>
    <script language="javascript">
        var current = 1,current_step,next_step,steps;
        //
        function generarForm(){
            //Completo el formulario  
            if(true){

                <?php
                
                if($generacionActual == "IB" ){
                    ?>
                    
                    
                    if(parseInt(document.getElementById("final_asistencia_total").value) < 3){
                        alert("La asistencia total no puede ser menor a 3 personas");
                        return false;
                    }
                    

                    if(parseInt(inputs_null) ==0){
                        if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?"))
                        {
                            $(':input[type="submit"]').prop('disabled', true);
                            document.getElementById('funcion').value = "<?=$temp_accionForm; ?>";
                        }else{
                            return false;
                        }
                        return true;
                    }                    else{
                        alert("Por favor verifique la información, debe llenar todo el mapeo.");
                        return false;
                    }
                    <?php
                }else{
                    ?>
                    var inputs_null;
                    inputs_null = 0;
                    var inp_1 = document.getElementById("asistencia_total").value;
                    if (inp_1=="") {
                        inputs_null++;
                    }
                    var inp_2 = document.getElementById("asistencia_muj").value;
                    if (inp_2=="") {
                        inputs_null++;
                    }
                    var inp_3 = document.getElementById("asistencia_hom").value;
                    if (inp_3=="") {
                        inputs_null++;
                    }
                    var inp_4 = document.getElementById("rep_ndis").value;
                    if (inp_4=="") {
                        inputs_null++;
                    }
                    var inp_5 = document.getElementById("act_grad_nom").value;
                    if (inp_5=="") {
                        inputs_null++;
                    }
                    var inp_6 = document.getElementById("act_grad_tar").value;
                    if (inp_6=="") {
                        inputs_null++;
                    }
                    
                    
                    if(parseInt(inputs_null) > 0){
                        alert("Debe llenar todos los datos requeridos.");
                        return false;
                    }else{
                        if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?")){
                        $(':input[type="submit"]').prop('disabled', true);
                        document.getElementById('funcion').value = "<?=$temp_accionForm; ?>";
                        }else{
                            return false;
                        }
                        return true;
                    }
                    
                    <?php
                }
                ?>
            }else{
                return false;
            }
        }
        
        //
        function init(){
            document.getElementById('form1').onsubmit = function(){
                    return generarForm();
            }

            steps = $("fieldset").length;
            $(".next").click(function(){
                //current_step = $(this).parent();
                //$(this).closest(“fieldset”)
                //next_step = $(this).parent().next();
                if (!$("#form1")[0].checkValidity()) { 
                    document.getElementById('button-hidden').click();
                }else{
                    current_step = $(this).closest("fieldset");      //
                    next_step = $(this).closest("fieldset").next();
                    next_step.show();
                    current_step.hide();
                    setProgressBar(++current);
                }
            });

            $(".previous").click(function(){
                //current_step = $(this).parent();
                //next_step = $(this).parent().prev();
                current_step = $(this).closest("fieldset");      //
                next_step = $(this).closest("fieldset").prev();
                next_step.show();
                current_step.hide();
                setProgressBar(--current);
            });

            setProgressBar(current);
            // Change progress bar action
            function setProgressBar(curStep){
                var percent = parseFloat(100 / steps) * curStep;
                percent = percent.toFixed();
                $(".progress-bar")
                .css("width",percent+"%")
                .html(percent+"%"); 
                
                //sumar();
            }
            
            /*function sumar(){
                var asistencia_hom = 0;
                var asistencia_muj = 0;
                var asistencia_jov = 0;
                var asistencia_nin = 0;
                var desiciones = 0;
                //
                if(document.getElementById("asistencia_hom").value != ""){
                    var asistencia_hom = document.getElementById("asistencia_hom").value;
                }
                if(document.getElementById("asistencia_muj").value != ""){
                    var asistencia_muj = document.getElementById("asistencia_muj").value;
                }
                if(document.getElementById("asistencia_muj").value != ""){
                    var asistencia_muj = document.getElementById("asistencia_muj").value;
                }
                //
                if(document.getElementById("asistencia_jov").value != ""){
                    var asistencia_jov = document.getElementById("asistencia_jov").value;
                }
                if(document.getElementById("asistencia_nin").value != ""){
                    var asistencia_nin = document.getElementById("asistencia_nin").value;
                }
                if(document.getElementById("asistencia_total").value != ""){
                    var asistencia_total = document.getElementById("asistencia_total").value;
                }
                
                document.getElementById("final_asistencia_total").value = parseInt(asistencia_total);
                //
                

                document.getElementById("final_asistencia_hom").value = parseInt(asistencia_hom);
                document.getElementById("final_asistencia_muj").value = parseInt(asistencia_muj);
                document.getElementById("final_asistencia_jov").value = parseInt(asistencia_jov);
                document.getElementById("final_asistencia_nin").value = parseInt(asistencia_nin);
                
                
                
                document.getElementById("final_bautizados").value = parseInt(bautizados) + 1;
                document.getElementById("final_discipulado").value = parseInt(var_suma) - 1;
                //
                document.getElementById("final_bautizadosPeriodo").value = parseInt(bautizadosPeriodo);
                
                //document.getElementById("final_desiciones").value = parseInt(var_suma) - 1; //Antigua logica
                document.getElementById("final_desiciones").value = parseInt(desiciones);
                document.getElementById("final_preparandose").value = parseInt(var_suma) - 1 - parseInt(bautizadosPeriodo);                
            }*/
            
            <?php
            if($varExitoREP == 1)
            {
                ?>alert("Se ha colocado correctamente el ACCESO, espere mientras es dirigido.");
                window.location.href = "index.php?doc=admin_usu4&id=<?=$ultimoId;?>";<?php
            }
            ?>
        }
        

        window.onload = function(){
            init();
        }
    </script>

        
        <?php
    }
}   //FIN DEL IF DE REDIRIGIR SI YA INSERTO EL REGISTRO
else{
    echo "No deberia estar aquí.";
}
?>
<?php if ($_SESSION['perfil']=="168" || $fechLimite > $fechaReporte) {?>
    <script type="text/javascript">
        $("input").attr('disabled','disabled');
        $("textarea").attr('disabled','disabled');
        $("select").attr('disabled','disabled');
        $(".eliminarAdd").prop('disabled','disabled');
        $("#btn-check").prop('disabled', false);
    </script>
<?php } ?>
<script type="text/javascript">
    $(document).ready(function(){
        recargaLista();
        $('#rep_carcel').change(function(){
            recargaLista();
        });
        recargaListaDpto();
        $('#departamento').change(function(){
            recargaListaDpto();
        });
        /*$('#asistencia_muj').change(function(){

            var cursos = $('#asistencia_muj').val();
            var resul = cursos/12;
            var mod = resul%2;
            if (mod != 0) {
                resul = Math.trunc(resul)+1;
            }
            if (cursos<=12) {
                resul = 1;
            }
            $('#asistencia_jov').val(resul);
        });*/
    })
</script>
<script type="text/javascript">
    function recargaListaDpto(){
        $.ajax({
            type: "POST",
            url: "datos_ubicacion.php",
            data: "id_depa=" + $('#departamento').val(),
            success: function(r){
                $('#municipio').html(r);
            }
        })
    }
</script>
<script type="text/javascript">
    function recargaLista(){
        $.ajax({
            type: "POST",
            url: "datos_carcel_ubicacion.php",
            data: "id_carcel=" + $('#rep_carcel').val(),
            success: function(r){
                $('#ubicacion').html(r);
            }
        })
    }
</script>