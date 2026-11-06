<?
session_start();

#
# Example PHP server-side script for generating
# responses suitable for use with jquery-tokeninput
#
# Connect to the database
$Host     = "localhost";
$Database = "twsselvr_videx_app";
$User     = "twsselvr_root";
$Password = 'Videx@2024*.';



$Link_ID = mysqli_connect($Host, $User, $Password, $Database);

# Perform the query
$texto = $_GET["q"];
strip_tags($texto);
addslashes($texto);
trim($texto);
static $acentos = "áéíóúÁÉÍÓÚàèìòùÀÈÌÒÙâêîôûÂÊÎÔÛäëïöüÄËÏÖÜ";
static $validos = "aeiouAEIOUaeiouAEIOUaeiouAEIOUaeiouAEIOU";
$texto = strtr($texto, $acentos, $validos);


$query = "SELECT id, concat(nombres,' - ', email) as name FROM sms_usuarios WHERE nombres LIKE '%".$texto."%' AND email != '' ORDER BY nombres ASC";

if(strlen($texto) < 5){
	$query .= " LIMIT 10";//dsdsdsds
}

$arr = array();
$rs = mysqli_query($Link_ID, $query);

//echo $query;

# Collect the results
while($obj = mysqli_fetch_object($rs)) {
	$id_temp = $obj->id;
    $name_temp =  utf8_encode($obj->name);
//    $arr[] =  $obj;
    $arr[] =  array(
			"id" => $id_temp,
			"name" => $name_temp
			);
}

# JSON-encode the response
$json_response = json_encode($arr);

# Optionally: Wrap the response in a callback function for JSONP cross-domain support
if($_GET["callback"]) {
    $json_response = $_GET["callback"] . "(" . $json_response . ")";
}

# Return the response
echo $json_response;

?>