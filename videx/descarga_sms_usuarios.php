<?
session_start();
ignore_user_abort(true);
set_time_limit(0); // disable the time limit for this script
//session_register("SESSION");
include_once('funciones.php');
//
$temp_path = "archivos/contactos/";
//
if(isset($_GET["archivo"]) && !empty($_GET["archivo"]) && eliminarInvalidos($_GET["archivo"]) != "" && is_logged_in() && $_SESION["perfil"] != 3 && $_SESION["perfil"] != 4 && $_SESION["perfil"] != 160)
{
    // Objeto de Base de Datos
    $PSN1 = new DBbase_Sql;
    //
    //
    $nombreArchivoIni = eliminarInvalidos($_GET["archivo"]);
    $extArchivo = extension_archivo($nombreArchivoIni);
    //
    $pathArchivo = $temp_path.$nombreArchivoIni;
    //echo $pathArchivo;
    //
    if(file_exists($pathArchivo))
    {
        //echo "Existe";
        $nombre_temporal = strtotime("now").".".$extArchivo;
        $archivo = $nombreArchivoIni;//strtotime("now").".".$extArchivo;
        $path = $temp_path;
        //$path = "/absolute_path_to_your_files/"; // change the path to fit your websites document structure
        //
        $dl_file = preg_replace("([^\w\s\d\-_~,;:\[\]\(\).]|[\.]{2,})", '', $archivo); // simple file name validation
        $dl_file = filter_var($dl_file, FILTER_SANITIZE_URL); // Remove (more) invalid characters
        $fullPath = $path.$dl_file;

        if($fd = fopen($fullPath, "r")) {
            $fsize = filesize($fullPath);
            $path_parts = pathinfo($fullPath);
            $ext = strtolower($path_parts["extension"]);
            switch ($ext) {
                case "pdf":
                header("Content-type: application/pdf");
                header("Content-Disposition: inline; filename=\"".$nombre_temporal."\""); // use 'attachment' to force a file download      $path_parts["basename"]
                break;
                case "html":
                header("Content-type: text/html");
                header("Content-Disposition: inline; filename=\"".$nombre_temporal."\""); // use 'attachment' to force a file download
                break;
                // add more headers for other content types here
                default;
                header("Content-type: application/octet-stream");
                header("Content-Disposition: inline; filename=\"".$nombre_temporal."\"");
                break;
            }
            header("Content-length: $fsize");
            header("Cache-control: private"); //use this to open files directly
            while(!feof($fd)) {
                $buffer = fread($fd, 2048);
                echo $buffer;
            }
        }else{
            echo "No pudo abrir";
        }
        fclose ($fd);
    }
    exit;
}
else
{
	die("No esta identificado.");
}
?>