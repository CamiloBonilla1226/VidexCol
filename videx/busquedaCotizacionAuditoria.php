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
$Database = "twsselvr_videx_app";
$User     = "twsselvr_root";
$Password = 'Videx@2024*.';


$Link_ID = mysqli_connect($Host, $User, $Password, $Database);



$idCliente = eliminarInvalidos($_POST["idCliente"]);

if(isset($idCliente)){

	$query = "SELECT id, descripcion FROM cotizacion WHERE estado =65 and  idCliente = '".$idCliente."'ORDER BY id ASC";
	$result = mysqli_query($Link_ID,$query);
	$count = mysqli_num_rows($result);

if(mysqli_num_rows($result)>0){
echo  '<option selected="selected">Elige la descripcion de la cotizacion</option>';

while($obj = mysqli_fetch_array($result)) {
		echo "<option value=".$obj['id'].">".utf8_encode($obj['descripcion'])."</option>";

		//echo '<option value='.$mostrar['id'].'>'.eliminarInvalidos($mostrar['nombres']).'</option>';
	}

}


}
?>