<?php

use PHPMailer\PHPMailer\PHPMailer;

use PHPMailer\PHPMailer\SMTP;

use PHPMailer\PHPMailer\Exception;

//

error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');  

//

if($_GET["doc"] == "mail_envio"){

    // Ignore user aborts and allow the script

    // to run forever

    ignore_user_abort(true);

    set_time_limit(0);

    //

    include_once('phpmailer/src/PHPMailer.php');

    include_once('phpmailer/src/SMTP.php');

    include_once('phpmailer/src/Exception.php');



    //$mail = new PHPMailer\PHPMailer\PHPMailer();

    $mail = new PHPMailer();

    //$Exception = new PHPMailer\PHPMailer\Exception;

}



//


$array_meses = array(

"Enero",

"Enero",

"Febrero",

"Marzo",

"Abril",

"Mayo",

"Junio",

"Julio",

"Agosto",

"Septiembre",

"Octubre",

"Noviembre",

"Diciembre"

);



$array_semana = array(

"Mon" => "Lunes",

"Tue" => "Martes", 

"Wed" => "Miercoles", 

"Thu" => "Jueves", 

"Fri" => "Viernes", 

"Sat" => "S&aacute;bado", 

"Sun" => "Domingo",

); 



$mesactual = intval(date("m"));

$anhoactual = intval(date("Y"));

$diaactual = intval(date("d"));

$semanaactual = date("D"); 





//session_register("SESSION");
include_once('funciones.php');

//Include Configuration File
include('config.php');


if(!isset($_GET["salir"]) && $_SESSION["id"] != "" && $_SESSION["id"] != 0 && $_SESSION["sistema"] != "videx")

{

	header("Location: index.php?salir=");

	exit;

}



if(isset($_GET[salir]))

{

	session_unset();

	redirect('index.php');

}



if(isset($_POST["logueo"]) && trim($_POST["logueo"]) != "")

{

	$PSN = new DBbase_Sql;

	$logueo = eliminarInvalidos($_POST["logueo"]);

	$pass = eliminarInvalidos($_POST["passwordlogueo"]);

	$error = 0;

	

	$sql= "SELECT usuario.*, usuario_empresa.empresa_socio ";

	$sql.=" FROM usuario ";

	   $sql.=" LEFT JOIN usuario_empresa ";

	   $sql.=" ON idUsuario = usuario.id ";

	$sql.=" WHERE acceso = 1 AND identificacion='".$logueo."'";



	$PSN->query($sql);

		

	if($PSN->next_record())

	{

		if(md5($pass) == $PSN->f('password'))

		{

			$_SESSION["empresa_socio"] = $PSN->f('empresa_socio');

            //            

			$_SESSION["administrador"] = "admin";

			$_SESSION["sistema"] = "videx";

			$_SESSION["nombre"] = $PSN->f('nombre');

			$_SESSION["identificacion"] = $PSN->f('identificacion');

			$_SESSION["direccion"] = $PSN->f('direccion');

			$_SESSION["telefono1"] = $PSN->f('telefono1');

			$_SESSION["telefono2"] = $PSN->f('telefono2');

			$_SESSION["celular"] = $PSN->f('celular');

			$_SESSION["email"] = $PSN->f('email');

			$_SESSION["youtube"] = $PSN->f('url');

			$_SESSION["drive"] = $PSN->f('url2');

			$_SESSION["id"] = $PSN->f('id');

			$_SESSION["superusuario"] = $PSN->f('superusuario');

            $_SESSION["menu_graphs"] = $PSN->f('acceso_graphs');

			//

            //                

			$_SESSION['KCFINDER'] = array(

    			'disabled' => false

			);

            

            /*

            *

            */			

			$_SESSION["perfil"] = $PSN->f('tipo');

            //

            if($_SESSION["perfil"] == 160){

                //

                $_SESSION["tipo_user_cli"] = $PSN->f('tipo_user_cli');

                //                

                $sql= "SELECT usuario_relacion.idUsuario2 ";

                $sql.=" FROM usuario_relacion, usuario ";

                $sql.=" WHERE idUsuario1 = '".$_SESSION["id"]."' AND usuario.id = usuario_relacion.idUsuario2 AND usuario.tipo = 3";

                $PSN->query($sql);

                if($PSN->next_record())

                {

                    $_SESSION["micliente"] = $PSN->f('idUsuario2');

                    //$_SESSION["micliente"] = $PSN->f('idUsuario2');

                    

                    //die("Encontrado ".$_SESSION["micliente"]);

                }else{

                    //die("NO Encontrado ".$sql);

                }

                

            }

            else if($_SESSION["perfil"] == 3){

                $_SESSION["micliente"] = $PSN->f('id');

            }

			//

			//

			if($_SESSION["redireccion"] != "")

			{

				redirect(eliminarInvalidos($_SESSION["redireccion"]));

			}

			else

			{

				redirect('index.php?doc=main');

			}

		}

		else

		{

			$error = 2;

		}

	}

	else

	{

		$error = 1;

	}

}



if(trim($_SESSION["imagen"]) == "")

{

	$_SESSION["imagen"] = "LogoWeb.jpg";				

}


//index.php


if(isset($_GET["code"])) {
    $token = $google_client->fetchAccessTokenWithAuthCode($_GET["code"]);
    if(!isset($token['error'])) {
        $google_client->setAccessToken($token['access_token']);
        $_SESSION['access_token'] = $token['access_token'];
        $google_service = new Google_Service_Oauth2($google_client);
        $data = $google_service->userinfo->get();

        $email = $data['email'];

        // Consulta para verificar si el usuario existe en la base de datos
        $PSN = new DBbase_Sql;
        $sql = "SELECT usuario.*, usuario_empresa.empresa_socio FROM usuario LEFT JOIN usuario_empresa ON idUsuario = usuario.id WHERE acceso = 1 AND email='".$email."'";
        $PSN->query($sql);

        if($PSN->next_record()) {
            // El usuario existe en la base de datos, inicializar variables de sesión
            $_SESSION["empresa_socio"] = $PSN->f('empresa_socio');
            $_SESSION["administrador"] = "admin";
            $_SESSION["sistema"] = "videx";
            $_SESSION["nombre"] = $PSN->f('nombre');
            $_SESSION["identificacion"] = $PSN->f('identificacion');
            $_SESSION["direccion"] = $PSN->f('direccion');
            $_SESSION["telefono1"] = $PSN->f('telefono1');
            $_SESSION["telefono2"] = $PSN->f('telefono2');
            $_SESSION["celular"] = $PSN->f('celular');
            $_SESSION["email"] = $PSN->f('email');
            $_SESSION["youtube"] = $PSN->f('url');
            $_SESSION["drive"] = $PSN->f('url2');
            $_SESSION["id"] = $PSN->f('id');
            $_SESSION["superusuario"] = $PSN->f('superusuario');
            $_SESSION["menu_graphs"] = $PSN->f('acceso_graphs');
            $_SESSION['KCFINDER'] = array('disabled' => false);
            $_SESSION["perfil"] = $PSN->f('tipo');
            
            if($_SESSION["perfil"] == 160) {
                $_SESSION["tipo_user_cli"] = $PSN->f('tipo_user_cli');
                $sql = "SELECT usuario_relacion.idUsuario2 FROM usuario_relacion, usuario WHERE idUsuario1 = '".$_SESSION["id"]."' AND usuario.id = usuario_relacion.idUsuario2 AND usuario.tipo = 3";
                $PSN->query($sql);
                if($PSN->next_record()) {
                    $_SESSION["micliente"] = $PSN->f('idUsuario2');
                }
            } else if($_SESSION["perfil"] == 3) {
                $_SESSION["micliente"] = $PSN->f('id');
            }

            if($_SESSION["redireccion"] != "") {
                redirect(eliminarInvalidos($_SESSION["redireccion"]));
            } else {
                redirect('index.php?doc=main');
            }
        } else {
            // El usuario no existe en la base de datos, maneja el caso según tu lógica
            // Podrías crear un nuevo usuario o mostrar un mensaje de error
            // Aquí se muestra un ejemplo simple de redirección a una página de error
            redirect('index.php?doc=error&mensaje=Usuario no encontrado');
        }
    }
}



if(!isset($_SESSION['access_token']))
{

 $login_button = '<a href="'.$google_client->createAuthUrl().'"> <div class="casilla">
        <img src="R.png" alt="Descripción de la imagen">
    </div> 
                                                                    
                                                                    </a>';
                                                                    
}



if(isset($_GET["excelX"]))

{

	header('Content-type: application/vnd.ms-excel; charset=utf-8');

	header("Content-Disposition: attachment; filename=archivo".date("Ymd_His").".xls");

	header("Pragma: no-cache");

	header("Expires: 0");

	$docu = eliminarInvalidos($_GET["doc"]);

	include_once($docu.".php");

	exit;

}

else if(isset($_GET["excelXML"])){

	header('Content-type: application/vnd.ms-excel; charset=iso-8859-1');

	header("Content-Disposition: attachment; filename=archivo".date("Ymd_His").".xls");

	header("Pragma: no-cache");

	header("Expires: 0");

    ?><?php echo "<?"; ?>xml version="1.0" encoding="UTF-8" <?php echo "?>"; ?>



    <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">



    <Styles>

        <Style ss:ID="Default" ss:Name="Normal">

            <Alignment ss:Vertical="Bottom"/>

            <Borders/>

            <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>

            <Interior/>

            <Protection/>

        </Style>

        <Style ss:ID="s66">

           <NumberFormat ss:Format="dd\-mm\-yyyy"/>

          </Style>

          <Style ss:ID="s68">

           <NumberFormat ss:Format="Short Date"/>

          </Style>

      <Style ss:ID="amarillo">

       <Interior ss:Color="#FFFF00" ss:Pattern="Solid"/>

      </Style>

         

      <Style ss:ID="verdoso">

       <Interior ss:Color="#C8FFFF" ss:Pattern="Solid"/>

      </Style>

         

      <Style ss:ID="verdosoBold">

       <Interior ss:Color="#C8FFFF" ss:Pattern="Solid"/>

       <Font ss:Bold="1"/>

      </Style>

         

     </Styles><?php

	$docu = eliminarInvalidos($_GET["doc"]);

	include_once($docu.".php");

    echo "</Workbook>";

	exit;

}



?>



<!DOCTYPE html>

<html>

<head><meta charset="gb18030">

	

	<title><?=$gloPrograma; ?> - <?=$gloEmpresa	; ?></title>

	<meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="icon" href="./images/favico.png" sizes="32x32">
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0033a0"/>
    <link rel="apple-touch-icon" href="iconapp.jpeg">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Satura Colombia">
    
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link rel="stylesheet" type="text/css" href="/estilos_chart.css" />
    <!--<?php
    if($_GET["doc"] == "vehiculo_graph_cli1" || $_SESSION["menu_graphs"] == 1){
            $mostrar_dashboard = 1;
            ?>
            <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
            <link rel="stylesheet" type="text/css" href="estilos_chart.css" />
            <?php
    }
    ?>-->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.13.0/css/all.css">
    <link rel="stylesheet" type="text/css" href="/estilos_chart.css" />
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
   <meta name="viewport" content="width=device-width, initial-scale=1">
 
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
  
    
    

    
    <style>
        .casilla {
            width: 100%; /* O el tamaño deseado de la casilla */
            border: 1px solid #000; /* Opcional: para visualizar la casilla */
            box-sizing: border-box; /* Asegura que el padding y el border se incluyan en el ancho total */
        }

        .casilla img {
            width: 100px;
            height: 50px;
            object-fit: cover; /* Ajusta cómo la imagen se adapta al contenedor */
        }



    .botonsubmit {
    height: 50px;
    border: 2px solid;
    background-color: green;
    color: white;
    width: 100%
}
    
    .navbar-default {

        background-color: #425fa5;

        border-color: #425fa5;

        border-radius: 0;

        box-shadow: 0px 3px 3px rgba(0, 0, 0, 0.3);

        border-radius: 25px;

        margin-top: 5px;

    }



    .navbar-default .navbar-brand,

    .navbar-default .navbar-brand:hover,

    .navbar-default .navbar-brand:focus {

        color: #FFF;

    }



    .navbar-default .navbar-nav > li > a {

        color: #FFF;

    }



    .navbar-default .navbar-nav > li > a:hover,

    .navbar-default .navbar-nav > li > a:focus {

        background-color: #385FBE;

        color: #FFF;

    }



    .navbar-default .navbar-nav > .active > a,

    .navbar-default .navbar-nav > .active > a:hover,

    .navbar-default .navbar-nav > .active > a:focus {

        color: #FFF;

        background-color: #385FBE;

    }



    .navbar-default .navbar-text {

        color: #FFF;

    }



    .navbar-default .navbar-toggle {

        border-color: #385FBE;

        color: #FFF;

    }



    .navbar-default .navbar-toggle:hover,

    .navbar-default .navbar-toggle:focus {

        background-color: #385FBE;

        color: #FFF;

    }



    .navbar-default .navbar-toggle .icon-bar {

        background-color: #FFF;

    }   

    </style>

    <?php



    if($_GET["doc"] == "graphs_007"){

        ?><style>

            .funnel_outer{width:100%;float: left;position: relative;padding:0 10%;}

            .funnel_outer *{box-sizing:border-box}

                .funnel_outer ul{margin:0;padding:0;}

                .funnel_outer ul li{float: left;position: relative;margin:2px 0;height: 150px;clear: both;text-align: center;vertical-align: middle;width:100%;list-style:none}

                .funnel_outer li span{ border-top-width: 150px;border-top-style:  solid; border-left: 25px solid transparent; border-right:25px solid transparent; height: 0;display: inline-block;vertical-align: middle; } 

                .funnel_step_1 span{width:100%;border-top-color: #8080b6;}

                .funnel_step_2 span{width:calc(100% - 50px);border-top-color: #8E44AD}

                .funnel_step_3 span{width:calc(100% - 100px);border-top-color: #2C3E50}

                .funnel_step_4 span{width:calc(100% - 150px);border-top-color: #2ECC71}

                .funnel_step_5 span{width:calc(100% - 200px);border-top-color: #8E44AD}

                .funnel_step_6 span{width:calc(100% - 250px);border-top-color: #2C3E50}

                .funnel_step_7 span{width:calc(100% - 300px);border-top-color: #3498DB;}

            .funnel_outer ul li:last-child span{border-left: 0;border-right: 0;border-top-width: 40px;}

            .funnel_outer ul li.not_last span{border-left: 5px  solid transparent;border-right:5px  solid transparent;border-top-width:150px;}

              .funnel_outer ul li span p{margin-top: -30px;color:#fff;font-weight: bold;text-align: center;}

        </style><?php

    }

    

    

    if($_GET["doc"] != "sms_envio")

    {

        /*?>

        <!-- Script -->

        <!-- <script src="scripts/jquery-1.12.4.js"></script> //-->

        <!-- jQuery UI -->

        <link rel="stylesheet" href="scripts/jquery-ui.css">

        <script src="scripts/jquery-ui.js"></script><?*/

    }	

?>

	

<style>

* {

   font-size: 12px;

   line-height: 1.428;

}	

    

@media (max-width: 767px) {

  .navbar-default .navbar-nav .open .dropdown-menu>li>a,

  .navbar.navbar-nav li.dropdown .dropdown-menu > li > a {

    color: #fff; !important

  }

  /***Dropdown-menu Color Hover and Focus State***/

  .navbar-default .navbar-nav .open .dropdown-menu>li>a:hover,

  .navbar-default .navbar-nav .open .dropdown-menu>li>a:focus,

  .navbar.navbar-nav li.dropdown .dropdown-menu > li > a:hover,

  .navbar.navbar-nav li.dropdown .dropdown-menu > li > a:focus {

    color: #fff; !important

  }

}    

    

</style>

	

	

<script type="text/javascript">


function getfocus(id){

	if(document.getElementById(id))

    {

		document.getElementById(id).focus()

	}

}

function salirSistema()

{

	if(confirm("Desea salir del sistema?"))

	{

		window.location.href = "index.php?salir=";

	}

}

function cambiar(url)

{

		window.location.href = url.value;

}


</script>

</head>

<body <?php if($mostrar_dashboard == 1){ ?>onresize="drawChart()"<?php } ?>>



<?php

/*

*	AQUI VA EL CONTENIDO.

*/



if(!isset($_GET["doc"]) || $_GET["doc"] == ""){

	$_GET["doc"] = "main";

}

	

if(isset($_GET["doc"]) && !empty( $_GET["doc"]) && is_logged_in())

{?>

<div class="container">

    <div class="cont-menu cont-flex-2 fl-sbet post-rela">

		<div class="navbar-header" style="width: 90px;">

			<a href="index.php?doc=main" style="background-color:transparent;"><img src="images/logo.png" width="90px" /></a>

		</div>

        <div class="navbar-header">

            <input type="checkbox" name="btn-check" id="btn-check" value="1" class="btn-check">

            <label for="btn-check">

                <i class="abr fas fa-bars"></i>

                <i class="cer fas fa-times"></i>

            </label>

        

        <nav class="navbar navbar-default">

            <div class="container-fluid">

                <ul class="nav navbar-nav">

    		<?php

            if($_SESSION["perfil"] == 4){

                /*?><li class="dropdown">

                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">Mantenimiento de flota<span class="caret"></span></a>

                    <ul class="dropdown-menu">

                        <li>

                        <a href="index.php?doc=mant_buscar" target="_self" title="Consulta de solicitudes de mantenimiento"><img  src="images/png/search.png" border="0" height="10px" align="left" /> Consulta de solicitudes de mantenimiento</a>

                        </li>

                        <li>

                        <a href="index.php?doc=mant_buscar_cot" target="_self" title="Consulta de cotizaciones"><img  src="images/png/search.png" border="0" height="10px" align="left" /> Consulta de cotizaciones</a>

                        </li>

                        <li>

                        <a href="index.php?doc=mant_buscar_ot" target="_self" title="Solicitudes de ordenes de trabajo"><img  src="images/png/search.png" border="0" height="10px" align="left" /> Consulta de ordenes de trabajo</a>

                        </li>

                    </ul>

                </li>    <?*/

                ?>

                <li class="dropdown">

                    <a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="fas fa-user"></i> Mi cuenta<span class="caret"></span></a>

                    <ul class="dropdown-menu">

                <?php      

                

            }

            else{

                $PSNMenu = new DBbase_Sql;

                $sqlMenu = "SELECT menu.*";

                $sqlMenu.=" FROM menu, usuarios_menu WHERE menu.id =  usuarios_menu.idMenu AND  usuarios_menu.idUsuario = ".$_SESSION["id"];

                $sqlMenu.=" AND menu.estado = 1 ORDER BY principal, orden asc";

                $PSNMenu->query($sqlMenu);

                if($PSNMenu->num_rows() > 0)

                {

                    $principal_old = 0;

                    while($PSNMenu->next_record())

                    { 

                        if($principal_old != $PSNMenu->f("principal"))

                        {

                            if($principal_old != 0){

                                ?></ul>

                                </li><?php

                            }

                            $principal_old = $PSNMenu->f("principal");

                            

                                                    

                            //

                            ?><li class="dropdown" >

                                <a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php

                                switch($PSNMenu->f("principal")){

                                    case 1:

                                        echo "<i class='fas fa-sliders-h'></i> Administración del Sistema";

                                        break;

                                    case 2:

                                        echo "SMS + Emailing";

                                        break;

                                    case 3:

                                        echo "Cotizaciones";

                                        break;

                                    case 4:

                                        echo "<i class='fas fa-file-invoice'></i> Reportes";

                                        break;

                                    case 5:

                                        echo "Mapeo de iglesias";

                                        break;

                                    case 99:

                                        echo "<i class='fas fa-user'></i> Mi cuenta";

                                        break;

                                    default:

                                        echo "Otras opciones";

                                        break;

                                }

                                ?><span class="caret"></span></a>

                            <ul class="dropdown-menu"><?php					

                        }



                        

                        

                        ?><li><?php			

                        ?><a href="<?php

                             if($PSNMenu->f("directo") == 1){

                                echo $PSNMenu->f("php");

                             }

                             else{

                                ?>index.php?doc=<?=$PSNMenu->f("php"); ?><?php

                            }    

                             if($PSNMenu->f("opc") > 0){

                                 ?>&opc=<?php

                                 echo $PSNMenu->f("opc");

                             }

                             //  Extra

                             if($PSNMenu->f("extra") != ""){

                                 ?>&<?php

                                 echo $PSNMenu->f("extra");

                             }

                             ?>" title="<?=$PSNMenu->f("nombre"); ?>"<?php

                             if($PSNMenu->f("directo") == 1){

                                 ?> target="_blank"<?php

                             }else{

                                 ?> target="_self"<?php

                             }

                             ?>><?=$PSNMenu->f("imagen"); ?> <?=$PSNMenu->f("nombre"); ?></a><?php



                        ?></li><?php

                    }

                    

                    if($principal_old != 0){

                        ?></ul>

                        </li><?php

                    }

                }

                //

                /*

                *   MENU GRAPHS

                */

                //echo "Menu: ".$temp_menu_graphs;

                //

                if($_SESSION["menu_graphs"] == 1){

                    $PSNMenu2 = new DBbase_Sql;

                    $sqlMenu = "SELECT menu_graphs.*";

                    $sqlMenu.=" FROM menu_graphs, usuarios_menu_graphs WHERE menu_graphs.estado = 1 AND menu_graphs.id =  usuarios_menu_graphs.idMenu AND  usuarios_menu_graphs.idUsuario = ".$_SESSION["id"];

                    $sqlMenu.=" ORDER BY principal, orden asc";

                    $PSNMenu2->query($sqlMenu);

                    if($PSNMenu2->num_rows() > 0)

                    {

                        ?><li class="dropdown">

                        <a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="fas fa-chart-pie"></i> Gráficas<span class="caret"></span></a>

                        <ul class="dropdown-menu"><?php

                        while($PSNMenu2->next_record())

                        { 

                            ?><li><?php			

                            ?><a href="index.php?doc=<?=$PSNMenu2->f("php"); ?><?php

                                 if($PSNMenu2->f("opc") > 0){

                                     ?>&opc=<?php

                                     echo $PSNMenu2->f("opc");

                                 }

                                 //  Extra

                                 if($PSNMenu2->f("extra") != ""){

                                     ?>&<?php

                                     echo $PSNMenu2->f("extra");

                                 }

                                 ?>" target="_self" title="<?=$PSNMenu2->f("nombre"); ?>"><?=$PSNMenu2->f("imagen"); ?> <?=$PSNMenu2->f("nombre"); ?></a><?php



                            ?></li><?php

                        }

                        ?></ul>

                        </li><?php

                    }

                }   //FIN MENU GRAPHS            

                

            }

        

            if($_SESSION["drive"] != ""){

                ?><li class="dropdown">

                        <a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="fab fa-google-drive"></i> C&M<span class="caret"></span></a>

                        <ul class="dropdown-menu">

                        <li><?php				

                        ?><a href="<?=$_SESSION["drive"]; ?>" target="_blank" title="Lista de estudios pastorales y descargas"><img  src="images/png/download-from-cloud.png" border="0" height="10px" align="left" /> Lista de estudios pastorales y descargas</a><?php



                        ?></li>

            </ul>

            </li><?php

        }

    

        /*if($_SESSION["perfil"] == 2 || $_SESSION["perfil"] == 161){

            ?><li><a href="index.php?doc=videos" target="_self" title="Graficas"><img  src="images/png/sms-message.png" border="0" height="10px" align="left" /> Video tutorial</a></li><?

        }*/

        ?>

                 <li><a href="index.php?doc=mis_documentos" target="_self" title="Ver mis documentos"><i class="fas fa-cloud-download-alt"></i> Tutoriales / Videos</a></li>

            

        <li><a href="javascript:salirSistema();void(0);" target="_self" title="Salir del Sistema"><i class="fas fa-power-off"></i> Cerrar sesión</a></li>



        <!--<?php

        if($_SESSION["superusuario"] == 1){

            ?><li class="dropdown">

                <a class="dropdown-toggle" data-toggle="dropdown" href="#">Superusuario<span class="caret"></span></a>

                <ul class="dropdown-menu">

                    <li><a href="index.php?doc=sms_saldo" target="_self" title="Agregar Saldo"><img  src="images/png/sms-message.png" border="0" height="10px" align="left" /> Agregar Saldo SMS</a></li>

                </ul>

            </li><?php

        }

        ?>-->

                    </ul>

                </div>

    	   </nav>

        </div>   

        <div class="navbar-header" style="width: 90px;">

            <a href="index.php?doc=main" style="background-color:transparent;"><img src="images/logoV.png" width="90px" /></a>

        </div>

    </div>

		

    

    <?php

    alertaDiasFaltantes();



    /*

	* FIN DEL MENU - MENU - MENU

	*/

	$docu = eliminarInvalidos($_GET["doc"]);

	if(trim($docu) == "")

	{

		$docu = "main";

	}

	include_once($docu.".php");

	

	?></center>

    <div id="footer">

        <center>

            <hr color="#0000FF">

        </center>

        <div class="cont-flex" style="width: 100%;">

            <div style="width: 50%;">

                Bienvenido <?=$_SESSION["nombre"]; ?>.<br />

                Hoy es <?=$array_semana[$semanaactual]; ?> <?=$diaactual; ?> de <?=$array_meses[$mesactual]; ?> del <?=$anhoactual; ?><br><br>

            </div>

            <div style="width: 50%; text-align: right;">

                Diagonal 55 #37-41 Oficina 425, Bello, Antioquia, Colombia

                <br />

                Copyright 2019 - <?=date("Y"); ?> <a href="http://Videx.online/">Videx.online</a> desarrollado para <a href="http://www.saturacolombia.org/"><?=$gloEmpresa; ?></a><br><br>

            </div>

        </div>

	</div>

	<?php

}else{

	//Sin loguear

	?>

	<div class="cont-menu cont-flex-2 fl-sbet post-rela" style="padding: 10px 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); align-items: center;">
        <div class="navbar-header" style="width: 90px; display: flex; align-items: center; justify-content: center;">	
            <a href="https://www.saturacolombia.org/" style="background-color:transparent;"><img src="images/logo.png" height="50px" style="max-width: 100%; object-fit: contain;" /></a>
        </div>

        <div class="navbar-header">
            <nav class="navbar navbar-default" style="margin-bottom: 0; border: none; background: #3498db; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 4px;">
                <ul class="nav navbar-nav navbar-center">
                    <li><a href="index.php?doc=main" style="font-size: 18px; color: white; padding: 10px 20px; transition: all 0.3s ease;"><strong><?=$gloPrograma; ?></strong></a></li>
                </ul>
            </nav>
        </div>
        <style>
            .navbar-default .navbar-nav > li > a:hover {
                background-color: #2980b9;
                color: white;
            }
            @media (max-width: 767px) {
                .cont-menu.cont-flex-2 {
                    flex-direction: column;
                    gap: 10px;
                }
                .navbar-header {
                    width: auto !important;
                    margin: 5px 0;
                }
                .social {
                    justify-content: center;
                }
            }
        </style>          

        <div class="navbar-header" style="width: 90px; display: flex; align-items: center; justify-content: center;">
            <a href="https://www.saturacolombia.org/" style="background-color:transparent;"><img src="images/logoV.png" height="50px" style="max-width: 100%; object-fit: contain;" /></a>
        </div>
    </div>	

	<div class="cont-flex fl-cent" style="min-height: 590px; align-items: center;">

        <div class="col-sm-2 col-xs-0"></div>

        <div class="col-sm-8 col-xs-12" style="padding: 0 15px;">

            <div class="col-sm-7 col-xs-12" style="padding: 20px;">
                <div style="margin-top: 20px;">
                    <strong style="font-size: 24px; color: #333;">BIENVENIDO</strong>
                    <h2 style="margin-top: -5px; font-size: 26px; color: #444;"><?=$gloPrograma; ?> DE</h2>
                    <h1 style="margin-top: -10px; font-size: 50px; color: #222; font-weight: 600;">SATURA NACIONES</h1>
                    <p style="font-size: 16px; line-height: 1.5; color: #555; margin-top: 15px;">Una herramienta diseñada para que compartas lo que Dios está haciendo en tu región, acompañes tu plan maestro y ¡Celebremos juntos!. Recuerda, puedes acceder desde tu pc, tablet y celular.</p>
                    <ul class="social" style="padding: 0; margin-top: 20px; display: flex; list-style: none; flex-wrap: wrap;">
                        <li class="social-item" style="margin-right: 15px; margin-bottom: 10px;"><a href="https://saturacolombia.org/" target="_blank" style="color: #3498db; font-size: 22px;"><i class="fas fa-globe-americas"></i></a></li>
                        <li class="social-item" style="margin-right: 15px; margin-bottom: 10px;"><a href="https://www.facebook.com/saturacolombia/" target="_blank" style="color: #3b5998; font-size: 22px;"><i class="fab fa-facebook-f"></i></a></li>
                        <li class="social-item" style="margin-right: 15px; margin-bottom: 10px;"><a href="https://www.instagram.com/saturacolombia/" target="_blank" style="color: #e4405f; font-size: 22px;"><i class="fab fa-instagram"></i></a></li>
                        <li class="social-item" style="margin-right: 15px; margin-bottom: 10px;"><a href="https://www.youtube.com/user/SaturaColombia" target="_blank" style="color: #ff0000; font-size: 22px;"><i class="fab fa-youtube"></i></a></li>
                        <li class="social-item" style="margin-bottom: 10px;"><a href="https://twitter.com/saturacolombia" target="_blank" style="color: #1da1f2; font-size: 22px;"><i class="fab fa-twitter"></i></a></li>
                    </ul>
                </div>
            </div>

    		<div class="col-sm-5 col-xs-12">
                <div style="padding: 20px; background-color: #fff; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-top: 20px;">
                    <!-- Imagen con sombra y borde sutil -->
                    <div style="margin-bottom: 25px; text-align: center;">
                        <img src="images/titulo.png" width="100%" alt="" style="box-shadow: 0 3px 6px rgba(0,0,0,0.1); border: 1px solid #f0f0f0; border-radius: 6px; max-width: 90%; margin: 0 auto;">
                    </div>
                    
                    <form name="form1" method="post" action="" class="form-horizontal">
                    <?php
                    if(isset($_GET["doc"]))
                    {
                        ?>
                        <input type="hidden" name="redireccion" value="<?=$_SERVER['REQUEST_URI']; ?>" />
                        <?php
                    }
                    ?>
                        
                    <?php
                    if ($error == 1)
                    {
                        ?><div class="form-group">
                            <div class="col-sm-12"><div class="alert alert-danger" style="border-radius: 4px;">
                            <strong>ERROR:</strong> LOGIN INCORRECTO.
                                </div></div></div><?php
                    }
                    else if ($error == 2)
                    {
                        ?><div class="form-group">
                            <div class="col-sm-12"><div class="alert alert-danger" style="border-radius: 4px;">
                                <strong>ERROR:</strong> PASSWORD INCORRECTO.
                                </div></div>
                        </div><?php
                    }
                    ?>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <div class="col-sm-12">
                            <strong style="color: #444; font-size: 14px;">Usuario:</strong>
                        </div>
                        <div class="col-sm-12">
                            <input name="logueo" type="text" id="logueo" value="<?=eliminarInvalidos($_POST["logueo"]); ?>" class="form-control" placeholder="Ingrese su usuario" required autofocus style="border-radius: 6px; padding: 8px 12px; border: 1px solid #ddd; box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);" />
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <div class="col-sm-12">
                            <strong style="color: #444; font-size: 14px;">Contraseña:</strong>
                        </div>
                        <div class="col-sm-12">
                            <input name="passwordlogueo" type="password" id="passwordlogueo" class="form-control" placeholder="Ingrese su contraseña" required style="border-radius: 6px; padding: 8px 12px; border: 1px solid #ddd; box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12 text-center" style="margin-top: 20px;">
                            <input type="submit" value="Ingresar" class="botonsubmit" style="padding: 10px 25px; border-radius: 6px; font-weight: 600; transition: all 0.3s ease;" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12 text-center">
                            <?php
                            if($login_button == '') {
                                echo '<div class="panel-heading text-center">Welcome User</div><div class="panel-body text-center">';
                                echo '<img src="'.$_SESSION["user_image"].'" class="img-responsive img-circle img-thumbnail" style="box-shadow: 0 2px 5px rgba(0,0,0,0.1);" />';
                                echo '<h3><b>Name :</b> '.$_SESSION['user_first_name'].' '.$_SESSION['user_last_name'].'</h3>';
                                echo '<h3><b>Email :</b> '.$_SESSION['user_email_address'].'</h3>';
                                echo '<a href="logout.php" class="btn btn-danger login-button">Logout</a></div>';
                            } else {
                                echo '<div align="center">'.$login_button . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                    </form>
                </div>
    		</div>
    	


             
 
        </div>
      
          
  

        <div class="col-sm-2 col-xs-0"></div>

        <div class="col-sm-12 col-xs-12">
            <hr style="border-top: 1px solid #3498db; margin: 30px 0 20px;">
            <span id="footer" style="display: block; padding: 0 15px;">
                <div class="col-sm-6 col-xs-12" style="text-align: left; color: #555; font-size: 13px; margin-bottom: 10px;">
                    Diagonal 55 #37-41 Oficina 425, Bello, Antioquia, Colombia
                </div>
                <div class="col-sm-6 col-xs-12" style="text-align: right; color: #555; font-size: 13px; margin-bottom: 10px;">
                    Copyright 2019 - <?=date("Y"); ?> <a href="http://Videx.online/" style="color: #3498db;">Videx.online</a> desarrollado para <a href="http://www.saturacolombia.org/" style="color: #3498db;"><?=$gloEmpresa; ?></a>
                </div>
            </span>

		</div>	

	</div><?php } ?>
	

<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
      navigator.serviceWorker.register('/sw.js').then(function(registration) {
        console.log('ServiceWorker registration successful with scope: ', registration.scope);
      }, function(err) {
        console.log('ServiceWorker registration failed: ', err);
      });
    });
  }
</script>
</body>

</html>


