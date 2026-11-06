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
?><html>
<head>
	<title><?=$gloPrograma; ?> - <?=$gloEmpresa	; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="scripts/calendario.css" />
<link rel="stylesheet" type="text/css" href="estilos.css" />
<link rel="stylesheet" href="menu/menu_style.css" type="text/css" />
<script language="javascript" type="text/javascript" src="scripts/calendario.js"></script>
<script language="javascript" type="text/javascript" src="scripts/prototype.js"></script>
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
	$docu = $_GET["doc"];
	if(trim($docu) == "")
	{
		$docu = "main";
	}
	else
	{
		include_once("$docu.php");
	}	
}
else
{
	echo "Accion invalida";
}
?>
</body></html>