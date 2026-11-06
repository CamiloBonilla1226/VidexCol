<?
session_start();
//session_register("SESSION");
include_once('funciones.php');

?><html>
    
<meta name="viewport" content="width=device-width,maximum-scale=1.0">
<head>
	<title><?=$gloPrograma; ?> - <?=$gloEmpresa	; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<style>
@page { margin: 0; }

@media print {
    @page { margin: 20px; }
}
    
* {
   font-size: 12px;
}	

table {
border-collapse: collapse;
}

table, th, td {
border: 1px solid black;
}
</style>
    
<style type="text/css" media="screen"></style>

<style type="text/css" media="print">
 
/* @page {size:landscape}  */   
body {
    page-break-before: avoid;
    width:96%;
    height:96%;
}
</style>    
    
<body  onload="impresion()">
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
<script>
function impresion(){
    window.print();
    window.close();
}
//setTimeout(impresion, 3000);
</script>
</body></html>