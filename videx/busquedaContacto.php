<?
session_start();
function eliminarInvalidos($texto){
	strip_tags($texto);
	addslashes($texto);
	trim($texto);
	static $acentos = "áéíóúÁÉÍÓÚàèìòùÀÈÌÒÙâêîôûÂÊÎÔÛäëïöüÄËÏÖÜ";
	static $validos = "aeiouAEIOUaeiouAEIOUaeiouAEIOUaeiouAEIOU";
	$texto = strtr($texto, $acentos, $validos);
	return $texto;

}
#
# Example PHP server-side script for generating
# responses suitable for use with jquery-tokeninput
#
# Connect to the database
$Host     = "localhost";
$Database = "playhhwa_videx2020";
$User     = "playhhwa_videx";
$Password = 'Sms-app2020*';


$Link_ID = mysqli_connect($Host, $User, $Password, $Database);

# Perform the query
$id = eliminarInvalidos($_REQUEST["id"]);
$texto = eliminarInvalidos($_REQUEST["term"]);
$idCliente = eliminarInvalidos($_REQUEST["idCliente"]);
$request = $_REQUEST["request"];

// Get username list
if($request == 1){
	$query = "SELECT id, nombres, email, telfijo, celular FROM sms_usuarios WHERE idCliente = '".$idCliente."' AND nombres LIKE '%".$texto."%' ORDER BY nombres ASC";

	if(strlen($texto) < 5){
		$query .= " LIMIT 10";//dsdsdsds
	}

	$arr = array();
    $rs = mysqli_query($Link_ID, $query);

	//echo $query;
	# Collect the results
	while($obj = mysqli_fetch_object($rs)) {
		$id_temp = $obj->id;
		$name_temp =  utf8_encode($obj->nombres);
		$arr[] =  array(
				"id" => $id_temp,
				"label" => $name_temp,
				"value" => $id_temp
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
}
else if($request == 2)
{
	$query = "SELECT id, nombres, email, telfijo, celular FROM sms_usuarios WHERE idCliente = '".$idCliente."' AND id = '".$id."'";
	//
	$arr = array();
    $rs = mysqli_query($Link_ID, $query);
	//
	# Collect the results
	if($obj = mysqli_fetch_object($rs)) {
		$id_temp = $obj->id;
		$nombres =  utf8_encode($obj->nombres);
		$telfijo =  utf8_encode($obj->telfijo);
		$celular =  utf8_encode($obj->celular);
		$email =  utf8_encode($obj->email);
		$arr[] =  array(
			"id" => $id_temp,
			"contactonombre" => $nombres,
			"contactotelefono" => $telfijo,
			"contactocelular" => $celular,
			"contactoemail" => $email
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
}
else if($request == 3)
{
	$query = "SELECT id, nombres, email, telfijo, celular FROM sms_usuarios WHERE idCliente = '".$idCliente."' LIMIT 1";
	//
	$arr = array();
    $rs = mysqli_query($Link_ID, $query);
	//
	# Collect the results
	if($obj = mysqli_fetch_object($rs)) {
		$id_temp = $obj->id;
		$nombres =  utf8_encode($obj->nombres);
		$telfijo =  utf8_encode($obj->telfijo);
		$celular =  utf8_encode($obj->celular);
		$email =  utf8_encode($obj->email);
		$arr[] =  array(
			"id" => $id_temp,
			"contactonombre" => $nombres,
			"contactotelefono" => $telfijo,
			"contactocelular" => $celular,
			"contactoemail" => $email
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
}
?>