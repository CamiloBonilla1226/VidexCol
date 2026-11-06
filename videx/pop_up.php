<?
session_start();
//session_register("SESSION");
include_once('funciones.php');

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

if(trim($_SESSION["imagen"]) == "")
{
	$_SESSION["imagen"] = "LogoWeb.jpg";				
}				

$isEmbed = (isset($_GET["embed"]) && trim($_GET["embed"]) == "1");
?><html>
<head>
<title><?=$gloPrograma; ?> - <?=$gloEmpresa	; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<? /*<link rel="stylesheet" type="text/css" href="estilos.css" />
<link rel="stylesheet" href="menu/menu_style.css" type="text/css" />
<script language="javascript" type="text/javascript" src="scripts/prototype.js"></script> */ ?>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<!-- Latest compiled JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

<? if($isEmbed){ ?>
<style type="text/css">
body{
	margin:0;
	padding:0;
	background:linear-gradient(180deg,#f6fbff 0%,#ffffff 100%);
	color:#0f172a;
	font-family:"Segoe UI",Tahoma,Arial,sans-serif;
}

.embed-shell{
	max-width:980px;
	margin:0 auto;
	padding:14px;
}

.embed-shell .form{
	background:#ffffff;
	border:1px solid #dbe7f3;
	border-radius:16px;
	padding:16px;
	box-shadow:0 10px 28px rgba(15,23,42,0.08);
}

.embed-shell .well{
	background:#eff6ff;
	border:1px solid #c9def6;
	color:#0f172a;
	font-weight:700;
	border-radius:12px;
}

.embed-shell .form-control{
	border:1px solid #c7d7eb;
	border-radius:10px;
	box-shadow:none;
}

.embed-shell .btn-success{
	background:linear-gradient(180deg,#14b8a6 0%,#0f766e 100%);
	border:0;
	border-radius:10px;
}

.embed-shell .btn-danger{
	border-radius:10px;
}
</style>
<? } ?>

    
<script type="text/javascript">
function getfocus(id){
	if(document.getElementById(id))
    {
		document.getElementById(id).focus()
	}
}
</script>
</head>
<body>
<?
/*
*	AQUI VA EL CONTENIDO.
*/
if(isset($_GET["doc"]) && !empty( $_GET["doc"]) && is_logged_in())
{
	if(!$isEmbed){
		/*
		* MENU - MENU - MENU
		*/
		?><div class="container-fluid">
        <nav class="navbar navbar-default">
            <div class="container-fluid">
            <div class="navbar-header">
                <a href="index.php?doc=main"><img src="images/logoanglo.png" height="50px" /></a>
            </div>
                
            <ul class="nav navbar-nav navbar-right">
              <li><a href="javascript:window.close();void(0);">Cerrar</a></li>
            </ul>
        </nav>
		<?
		/*
		* FIN DEL MENU - MENU - MENU
		*/
	}
	else
	{
		?><div class="embed-shell"><?
	}

	$docu = eliminarInvalidos($_GET["doc"]);
	if(trim($docu) == "")
	{
	}
	else
	{
		include_once("$docu.php");
	}
	if(!$isEmbed){
		?><span id="footer"><center>
	<hr color="#0000FF">
	<font size="1"><?=$gloPrograma; ?> - <a href="https://<?=$gloEmpresa; ?>" target="_blank" style="color: #0000FF; text-decoration: none;"><?=$gloEmpresa; ?></a>
	<br />
	Copyright 2018 - <?=date("Y"); ?> <a href="https://<?=$gloEmpresa; ?>" target="_blank" style="color: #0000FF; text-decoration: none;"><?=$gloEmpresa; ?></a></font></center></span><?
	}
	?></div><?
}
else
{
	?>DEBE ESTAR LOGUEADO PARA ACCEDER A ESTA APLICACION
	<br />
	<br />
	<span id="footer"><center>
	<hr color="#0000FF">
	<font size="1"><?=$gloPrograma; ?> - <a href="https://<?=$gloEmpresa; ?>" target="_blank" style="color: #0000FF; text-decoration: none;"><?=$gloEmpresa; ?></a>
	<br />
	Copyright 2018 - <?=date("Y"); ?> <a href="https://<?=$gloEmpresa; ?>" target="_blank" style="color: #0000FF; text-decoration: none;"><?=$gloEmpresa; ?></a></font></center></span><?
}
?>
</body></html>