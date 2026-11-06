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



$idCliente = eliminarInvalidos($_POST["idPerfil"]);

if(isset($idCliente)){

	$query = "select * from personal where id_tipo_personal = '".$idCliente."'ORDER BY nombres ASC";
	$result = mysqli_query($Link_ID,$query);
	$count = mysqli_num_rows($result);

if(mysqli_num_rows($result)>0){
echo  '<option selected="selected">Elige su Personal</option>';

while($obj = mysqli_fetch_array($result)) {
		echo "<option value=".$obj['cc'].">".utf8_encode($obj['nombres'])."</option>";

		//echo '<option value='.$mostrar['id'].'>'.eliminarInvalidos($mostrar['nombres']).'</option>';
	}

}


}
?>