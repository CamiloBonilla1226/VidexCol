<style type="text/css">

a.reportes {
    background-color: #87BBF5;
    color:#fff;
}

.nav-pills > li.active > a.btnEntrada {
    color: #fff;
    background-color: #449d44;
    border-color: #398439;
}
.nav-pills > li.active > a.btnSalida {
    color: #fff;
    background-color: #d9534f;
    border-color: #d43f3a;
}
.nav-pills > li.active > a.btnEvangelismo {
    color: #fff;
    background-color: #FFCD00;
    border-color: #d43f3a;
}
.nav-pills > li.active > a.btnPrimeraEntrega {
    color: #fff;
    background-color: #337ab7;
    border-color: #2e6da4;
}
.nav-pills > li.active > a.btnOtrasEntregas {
    color: #fff;
    background-color: #d9534f;
    border-color: #d43f3a;
}

/* Estilos para tablas */
.styled-table {
    width: 80%; /* Ancho de la tabla */
    margin: 20px auto; /* Centrar la tabla y añadir margen vertical */
    border-collapse: collapse;
    font-family: Arial, sans-serif;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
}

.styled-table th, .styled-table td {
    border: 1px solid #dddddd;
    padding: 12px 15px;
    text-align: center;
}

.styled-table thead tr {
    background-color: #009879;
    color: #ffffff;
    text-align: center;
}

.styled-table tbody tr:nth-of-type(even) {
    background-color: #f3f3f3;
}

.styled-table tbody tr:hover {
    background-color: #f1f1f1;
    cursor: pointer;
}

.styled-table tbody tr.active-row {
    font-weight: bold;
    color: #009879;
}

/* Estilos generales para todas las tablas, si no tienen .styled-table */
table, th, td {
    border: 1px solid black;
    border-collapse: collapse;
}

td, th {
    padding: 5px;
}

th {
    text-align: center;
}

.detalle-actividad-77 {
    margin-top: 20px;
}

.detalle-actividad-77 .detalle-card {
    background: #f8fbff;
    border: 1px solid #d7e6f2;
    border-radius: 12px;
    padding: 18px 15px 8px;
    margin-bottom: 22px;
}

.detalle-actividad-77 .detalle-card .form-group:last-child {
    margin-bottom: 0;
}

.detalle-actividad-77 .form-control[readonly],
.detalle-actividad-77 textarea.form-control[readonly] {
    background-color: #ffffff;
}

.detalle-actividad-77 .cont-item {
    margin-bottom: 20px;
}

</style>

<?php

if (!function_exists('reportar_escape_attr')) {
    function reportar_escape_attr($valor)
    {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('reportar_normalizar_plantador')) {
    function reportar_normalizar_plantador($valor)
    {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return '';
        }

        $plantadores = json_decode($valor, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($plantadores)) {
            $nombres = array();
            foreach ($plantadores as $plantador) {
                if (!is_scalar($plantador)) {
                    continue;
                }

                $plantador = trim((string)$plantador);
                if ($plantador !== '' && !in_array($plantador, $nombres, true)) {
                    $nombres[] = $plantador;
                }
            }

            if (count($nombres) > 0) {
                return implode(', ', $nombres);
            }
        }

        return $valor;
    }
}

if (!function_exists('reportar_obtener_campos_mapeo')) {
    function reportar_obtener_campos_mapeo()
    {
        return array(
            "mapeo_oracion" => "Orar",
            "mapeo_companerismo" => "Companerismo",
            "mapeo_adoracion" => "Adorar",
            "mapeo_biblia" => "Aplicar la biblia",
            "mapeo_evangelizar" => "Evangelizar",
            "mapeo_cena" => "Cena del Senor",
            "mapeo_dar" => "Dar",
            "mapeo_bautizar" => "Bautizar",
            "mapeo_trabajadores" => "Entrenar nuevos lideres"
        );
    }
}

if (!function_exists('reportar_obtener_mapeos_faltantes')) {
    function reportar_obtener_mapeos_faltantes($request)
    {
        $faltantes = array();
        foreach (reportar_obtener_campos_mapeo() as $campo => $etiqueta) {
            if (!isset($request[$campo]) || trim((string)$request[$campo]) === '') {
                $faltantes[$campo] = $etiqueta;
            }
        }

        return $faltantes;
    }
}

if (!function_exists('reportar_mapeo_comprometido_es_valido')) {
    function reportar_mapeo_comprometido_es_valido($valor)
    {
        $valor = (int)$valor;
        return $valor === 3 || $valor === 4;
    }
}

// QUITAR
//print_r( $_SESSION );
?> <br/>
<?php
//print_r( $_POST );
?> <br/>
<?php
//echo ($_SESSION[id]);
?> <br/>
<?php

// QUITAR
$Id = $_SESSION[id];
$Donador = $_POST["SendOpcion"];

//print_r( $GLOBALS);
//print_r( $_SERVER);
//print_r( $_REQUEST);
//print_r( $_POST); 
//print_r( $_GET);
//print_r( $_FILES);
//print_r( $_ENV);
//print_r( $_COOKIE);
//print_r( $_SESSION);




if(isset($_POST["SendOpcion"])){
    $PSN_Save = new DBbase_Sql;
    $PSN_Save01 = new DBbase_Sql;
    $DataSave = 0;

    if($_POST["SendOpcion"]=="1a"){
        if ($_POST[Donante] == "EntChkPD") {
            $Donante1 = 1;        
        }else{
            $Donante1 = 0; 
        }
        if ($_POST[Donante] == "EntChkSP"){
            $Donante2 = 1;        
        }else{
            $Donante2 = 0; 
        }
        if (isset($_POST["EntradaMix"]) && !empty($_POST["EntradaMix"])){
            $EntradaMix = $_POST["EntradaMix"];        
        }else{
            $EntradaMix = 0; 
        }
        if (isset($_POST["EntradaNab"]) && !empty($_POST["EntradaNab"]) ){
            $EntradaNab = $_POST["EntradaNab"];        
        }else{
            $EntradaNab = 0; 
        }
        $sql = ' insert into inventario (IdUsuario,Tipo,Donante1,Donante2,TipoSopa1,TipoSopa2,Fecha,Responsable,Facilitador,Pais,Departamento,Municipio, Barrio, TotalBeneficiados, Beneficiario) values(';
        $sql .= "$Id".","."1".",".$Donante1.",".$Donante2.",".$EntradaMix.",".$EntradaNab.",'".$_POST['fechaEntrada']."','".$_POST["ResponsableEnt"]."','',".$_POST['pais'].",".$_POST['departamento'].",".$_POST['ciudad'].",'',"."0,0".")";
//        $sql = ' insert into inventario (Tipo,Donante1,Donante2,TipoSopa1,TipoSopa2,Fecha,Responsable,Facilitador,Pais,Departamento,Municipio, Barrio, TotalBeneficiados, Beneficiario) values(';
//        $sql .= "1".",".$Donante1.",".$Donante2.",".$EntradaMix.",".$EntradaNab.",'".$_POST['fechaEntrada']."','".$_POST["ResponsableEnt"]."','',".$_POST['pais'].",".$_POST['departamento'].",".$_POST['ciudad'].",'',"."0,0".")";
    }
    else if($_POST["SendOpcion"]=="2a"){
        if ($_POST[Donante] == "salDon1"){
            $Donante1 = 1;        
        }else{
            $Donante1 = 0; 
        }
        if ($_POST[Donante] == "salDon2"){
            $Donante2 = 1;        
        }else{
            $Donante2 = 0; 
        }
        if (isset($_POST["SalidaMix"]) && !empty($_POST["SalidaMix"])){
            $SalidaMix = $_POST["SalidaMix"];        
        }else{
            $SalidaMix = 0; 
        }
        if (isset($_POST["SalidaNab"]) && !empty($_POST["SalidaNab"]) ){
            $SalidaNab = $_POST["SalidaNab"];        
        }else{
            $SalidaNab = 0; 
        }
        $sql = ' insert into inventario (IdUsuario,Tipo,Donante1,Donante2,TipoSopa1,TipoSopa2,Fecha,Responsable,Facilitador,Pais,Departamento,Municipio, Barrio, TotalBeneficiados,Beneficiario) values(';
        $sql .= "$Id".","."2".",".$Donante1.",".$Donante2.",".$SalidaMix.",".$SalidaNab.",'".$_POST['fechaSalida']."','".$_POST["ResSalida"]."','".$_POST["SalFacilitador"]."',".$_POST['salpais'].",".$_POST['saldepartamento'].",".$_POST['salciudad'].",'',"."0,0".")";
//        $sql = ' insert into inventario (Tipo,Donante1,Donante2,TipoSopa1,TipoSopa2,Fecha,Responsable,Facilitador,Pais,Departamento,Municipio, Barrio, TotalBeneficiados,Beneficiario) values(';
//        $sql .= "2".",".$Donante1.",".$Donante2.",".$SalidaMix.",".$SalidaNab.",'".$_POST['fechaSalida']."','".$_POST["ResSalida"]."','".$_POST["SalFacilitador"]."',".$_POST['salpais'].",".$_POST['saldepartamento'].",".$_POST['salciudad'].",'',"."0,0".")";
    } 
    else if($_POST["SendOpcion"]=="1aa"){
//        if (($_POST[Donante] == "EvaChkPD")){
            $Donante1 = 1;        
//        }else{
//            $Donante1 = 0; 
//        }
//        if (($_POST[Donante] == "EvaChkSP")){
//            $Donante2 = 1;        
//        }else{
            $Donante2 = 0; 
//        }
        if (isset($_POST["EvaMix"]) && !empty($_POST["EvaMix"])){
            $EvaMix = $_POST["EvaMix"];        
        }else{
            $EvaMix = 0; 
        }
        if (isset($_POST["EvaNab"]) && !empty($_POST["EvaNab"]) ){
            $EvaNab = $_POST["EvaNab"];        
        }else{
            $EvaNab = 0; 
        }
        $sql = ' insert into inventario (IdUsuario,Tipo,Donante1,Donante2,TipoSopa1,TipoSopa2,Fecha,Responsable,Facilitador,Pais,Departamento,Municipio, Barrio, TotalBeneficiados, Beneficiario ) values(';
        $sql .= "$Id".","."3".",".$Donante1.",".$Donante2.",".$EvaMix.",".$EvaNab.",'".$_POST['fechaEva']."','".$_POST["ResEva"]."','',".$_POST['evapais'].",".$_POST['evadepartamento'].",".$_POST['evaciudad'].",'".$_POST['barrioEv']."',".$_POST['PerBeneficiadas'].",0)";
//        $sql = ' insert into inventario (Tipo,Donante1,Donante2,TipoSopa1,TipoSopa2,Fecha,Responsable,Facilitador,Pais,Departamento,Municipio, Barrio, TotalBeneficiados, Beneficiario ) values(';
//        $sql .= "3".",".$Donante1.",".$Donante2.",".$EvaMix.",".$EvaNab.",'".$_POST['fechaEva']."','".$_POST["ResEva"]."','',".$_POST['evapais'].",".$_POST['evadepartamento'].",".$_POST['evaciudad'].",'".$_POST['barrioEv']."',".$_POST['PerBeneficiadas'].",0)";
    } 
    else if($_POST["SendOpcion"]=="2aa"){
//        if (($_POST[Donante] == "PEChkPD")){
            $Donante1 = 1;        
//        }else{
//            $Donante1 = 0; 
//        }
//        if (($_POST[Donante] == "PEChkSP")) {
//            $Donante2 = 1;        
//        }else{
            $Donante2 = 0; 
//        }
        if (isset($_POST["PEMix"]) && !empty($_POST["PEMix"])){
            $PEMix = $_POST["PEMix"];        
        }else{
            $PEMix = 0; 
        }
        if (isset($_POST["PENab"]) && !empty($_POST["PENab"]) ){
            $PENab = $_POST["PENab"];        
        }else{
            $PENab = 0; 
        }
        $Discapacidades="";
        if (isset($_POST["ChkMovilidad"]) && !empty($_POST["ChkMovilidad"]) ){
            $Discapacidades .= "Movilidad".",";        
        }
        if (isset($_POST["ChkMental"]) && !empty($_POST["ChkMental"]) ){
            $Discapacidades .= "Mental".",";        
        }
        if (isset($_POST["ChkAuditiva"]) && !empty($_POST["ChkAuditiva"]) ){
            $Discapacidades .= "Auditiva".",";        
        }
        if (isset($_POST["ChkVisual"]) && !empty($_POST["ChkVisual"]) ){
            $Discapacidades .= "Visual".",";        
        }
        if (isset($_POST["ChkOtras"]) && !empty($_POST["ChkOtras"]) ){
            $Discapacidades .= "Otras".",";        
        }
        if (isset($_POST["ChkNingunaDis"]) && !empty($_POST["ChkNingunaDis"]) ){
            $Discapacidades .= "Ninguna".",";        
        }
        $Ingresos = 0;
        if ($_POST["OptIngresos"] == "Opt01"){
            $Ingresos= 1;
        }else if ($_POST["OptIngresos"] == "Opt02"){
            $Ingresos= 2;
        }
        else if ($_POST["OptIngresos"] == "Opt03"){
            $Ingresos= 3;
        }
        else if ($_POST["OptIngresos"] == "Opt04"){
            $Ingresos= 4;
        }
        else if ($_POST["OptIngresos"] == "Opt05"){
            $Ingresos= 5;
        }
        $ComidaNoConsumida = "";
        if (isset($_POST["ChkDesayuno"]) && !empty($_POST["ChkDesayuno"]) ){
            $ComidaNoConsumida.= "Desayuno".",";        
        }
        if (isset($_POST["ChkAlmuerzo"]) && !empty($_POST["ChkAlmuerzo"]) ){
            $ComidaNoConsumida .= "Almuerzo".",";        
        }
        if (isset($_POST["ChkCena"]) && !empty($_POST["ChkCena"]) ){
            $ComidaNoConsumida .= "Cena".",";        
        }
        if (isset($_POST["ChkTodas"]) && !empty($_POST["ChkTodas"]) ){
            $ComidaNoConsumida .= "Todas".",";        
        }

        $Situaciones="";
        if (isset($_POST["ChkSuicidio"]) && !empty($_POST["ChkSuicidio"]) ){
            $Situaciones .= "Suicidio".",";        
        }
        if (isset($_POST["ChkViolencia"]) && !empty($_POST["ChkViolencia"]) ){
            $Situaciones .= "Violencia".",";        
        }
        if (isset($_POST["ChkAbuso"]) && !empty($_POST["ChkAbuso"]) ){
            $Situaciones .= "Abuso".",";        
        }
        if (isset($_POST["ChkDesaparicion"]) && !empty($_POST["ChkDesaparicion"]) ){
            $Situaciones .= "Desaparicion".",";        
        }
        if (isset($_POST["ChkDesplazamiento"]) && !empty($_POST["ChkDesplazamiento"]) ){
            $Situaciones .= "Desplazamiento".",";        
        }
        if (isset($_POST["ChkTrafico"]) && !empty($_POST["ChkTrafico"]) ){
            $Situaciones .= "Trafico".",";        
        }
        if (isset($_POST["ChkNingunaSit"]) && !empty($_POST["ChkNingunaSit"]) ){
            $Situaciones .= "Ninguna".",";        
        }
        $Discapacidades = trim($Discapacidades, ',');
        $ComidaNoConsumida  = trim($ComidaNoConsumida , ',');
        $Situaciones = trim($Situaciones, ',');

        $sql01 = 'insert into beneficiarios ( Nombre,telefono,TotalPersonas,TotalNinos,TotalNinosBeneficiados,TotalAdolescentes,TotalAdultos,TotalDiscapacitados,Discapacidad,Ingresos,ComidaNoConsumida, Situaciones) values(';
        $sql01 .="'".$_POST['nombre']."','".$_POST['telefono']."',".$_POST['PersonasCasa'].",".$_POST['NinosCasa'].",".$_POST['SoySatura'].",".$_POST['AdoCasa'].",".$_POST['Adultos'].",".$_POST['Discapacidad'].",'".$Discapacidades."',".$Ingresos.",'".$ComidaNoConsumida."','".$Situaciones."')";
        $PSN_Save01->query($sql01);
        $SetBen = $PSN_Save01->ultimoId();
        
        $sql = ' insert into inventario (IdUsuario,Tipo,Donante1,Donante2,TipoSopa1,TipoSopa2,Fecha,Responsable,Facilitador,Pais,Departamento,Municipio, Barrio, TotalBeneficiados, Beneficiario ) values(';
        $sql .= "$Id".","."4".",".$Donante1.",".$Donante2.",".$PEMix.",".$PENab.",'".$_POST['fechaPE']."','".$_POST["ResPE"]."','',".$_POST['pepais'].",".$_POST['pedepartamento'].",".$_POST['peciudad'].",'".$_POST['barrioPE']."',0,".$SetBen.")";
//        $sql = ' insert into inventario (Tipo,Donante1,Donante2,TipoSopa1,TipoSopa2,Fecha,Responsable,Facilitador,Pais,Departamento,Municipio, Barrio, TotalBeneficiados, Beneficiario ) values(';
//        $sql .= "4".",".$Donante1.",".$Donante2.",".$PEMix.",".$PENab.",'".$_POST['fechaPE']."','".$_POST["ResPE"]."','',".$_POST['pepais'].",".$_POST['pedepartamento'].",".$_POST['peciudad'].",'".$_POST['barrioPE']."',0,".$SetBen.")";
    } 
    else if($_POST["SendOpcion"]=="3aa"){
        
//        if (($_POST[Donante] == "OEChkPD")) {
            $Donante1 = 1;        
//        }else{
//            $Donante1 = 0; 
//        }
//        if (($_POST[Donante] == "OEChkSP")) {
//            $Donante2 = 1;        
//        }else{
            $Donante2 = 0; 
//        }
        if (isset($_POST["OEMix"]) && !empty($_POST["OEMix"])){
            $OEMix = $_POST["OEMix"];        
        }else{
            $OEMix = 0; 
        }
        if (isset($_POST["OENab"]) && !empty($_POST["OENab"]) ){
            $OENab = $_POST["OENab"];        
        }else{
            $OENab = 0; 
        }

        // Validate that a beneficiary has been selected before proceeding.
        if (isset($_POST['SelBeneficiario']) && !empty($_POST['SelBeneficiario']) && is_numeric($_POST['SelBeneficiario'])) {
            $idBeneficiado = (int)$_POST['SelBeneficiario'];

            // Update the beneficiary's IPG if provided.
            if (isset($_POST["IPGAsiste"]) && !empty($_POST["IPGAsiste"])) {
                $IPG = $_POST["IPGAsiste"]; // This value should be sanitized.
                $sql01 = "UPDATE beneficiarios SET IPG = '" . $IPG . "' WHERE IdBeneficiado = " . $idBeneficiado;
                $PSN_Save01->query($sql01);
            }

            // Build the INSERT query for the inventory.
            $sql = ' insert into inventario (IdUsuario,Tipo,Donante1,Donante2,TipoSopa1,TipoSopa2,Fecha,Responsable,Facilitador,Pais,Departamento,Municipio, Barrio, TotalBeneficiados, Beneficiario ) values(';
            $sql .= "$Id".","."5".",".$Donante1.",".$Donante2.",".$OEMix.",".$OENab.",'".$_POST['fechaOE']."','".$_POST["ResOE"]."','',0,0,0,0,0,".$idBeneficiado.")";
        } else {
            // If no beneficiary is selected, do not attempt to run the query.
            $sql = ""; 
        }
   
    }
    
// Desde aquí

        $wresMixVeg1lb = $_SESSION["wresMixVeg1lb"];
        $wresMixVeg3lb = $_SESSION["wresMixVeg3lb"];
        
        echo "1 lb - " . $wresMixVeg1lb . " - " . $EvaMix . "<br>" ;
        echo "3 lb - " . $wresMixVeg3lb . " - " . $EvaNab . "<br>";

//print_r( $GLOBALS);
//print_r( $_SERVER);
//print_r( $_REQUEST);
//print_r( $_POST); 
//print_r( $_GET);
//print_r( $_FILES);
//print_r( $_ENV);
//print_r( $_COOKIE);
//print_r( $_SESSION);

// Hasta aquí

    if (( ($wresMixVeg1lb - $EvaMix) < 0 ) || ($wresMixVeg3lb - $EvaNab) < 0){
        ?>
        <hr>
            <div class="col-md-10 col-md-offset-1">
                <div class="row">
                    <h3 class="alert alert-success text-center">La cantidad reportada por entregar, supera inventario.</h3>
                </div>
                <div class="row" >
                    <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form-horizontal">
                    <div class="cont-btn cont-flex fl-sbet"> <div class="item-btn">
                            <input type="submit" value="Volver" class="btn btn-success">
                        </div></div>
                    </form>
                </div>
                <br><br>   <hr>
            </div>
        <?php
    } else {
        if (!empty($sql)) {
            $DataSave = $PSN_Save->query($sql); 
            if ($DataSave > 0){
            ?>
            <hr>
                <div class="col-md-10 col-md-offset-1">
                    <div class="row">
                        <h3 class="alert alert-success text-center">Datos almacenados con exito.</h3>
                    </div>
                    <div class="row" >
                        <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form-horizontal">
                        <div class="cont-btn cont-flex fl-sbet"> <div class="item-btn">
                                <input type="submit" value="Volver" class="btn btn-success">
                            </div></div>
                        </form>
                    </div>
                    <br><br>   <hr>
                </div>
            <?php
            } else {
                ?>
            <hr>
                <div class="col-md-10 col-md-offset-1">
                    <div class="row">
                        <h3 class="alert alert-danger text-center">Imposible almacenar los datos, por favor intente de nuevo.</h3>
                    </div>
                    <div class="row" >
                        <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form-horizontal">
                        <div class="cont-btn cont-flex fl-sbet"> <div class="item-btn">
                                <input type="submit" value="Volver" class="btn btn-success">
                            </div></div>
                        </form>
                    </div>
                    <br><br>   <hr>
                </div>
            <?php
            }
        } else if ($_POST["SendOpcion"] == "3aa") {
            // This case happens when the "3aa" option is chosen but no valid beneficiary is selected.
            ?>
            <hr>
            <div class="col-md-10 col-md-offset-1">
                <div class="row">
                    <h3 class="alert alert-danger text-center">No se pudo guardar el registro. Por favor, seleccione un beneficiario válido de la lista.</h3>
                </div>
                <div class="row" >
                    <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form-horizontal">
                    <div class="cont-btn cont-flex fl-sbet"> <div class="item-btn">
                            <input type="submit" value="Volver" class="btn btn-success">
                        </div></div>
                    </form>
                </div>
                <br><br>   <hr>
            </div>
            <?php
        }
    }

        
    }

$PSN1 = new DBbase_Sql;
$PSN = new DBbase_Sql;
$webArchivo = "preoperacional";
$temp_letrero = "REPORTE MENSUAL";

// Compress image
function compressImage($source, $destination, $quality) {
  $info = @getimagesize($source);
  if(!$info || empty($info['mime'])){
        return false;
  }

  if($info['mime'] == 'image/jpeg'){
        $image = imagecreatefromjpeg($source);
        $resultado = imagejpeg($image, $destination, $quality);
  }
  elseif ($info['mime'] == 'image/gif'){
        $image = imagecreatefromgif($source);
        $resultado = imagegif($image, $destination);
  }
  elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
        imagesavealpha($image, true);
        $pngQuality = (int) round((100 - max(0, min(100, $quality))) / 10);
        $resultado = imagepng($image, $destination, max(0, min(9, $pngQuality)));
  }else{
        return move_uploaded_file($source, $destination);
  }

  if(isset($image) && (is_resource($image) || is_object($image))){
        imagedestroy($image);
  }

  return $resultado;
}

function obtenerRutaImagenReporte($idReporte, $numeroFoto, $extension = ""){
    $baseRelativa = "archivos/evi_".$idReporte."_".$numeroFoto;
    $baseAbsoluta = __DIR__.DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, $baseRelativa);

    if($extension != ""){
        $rutaRelativa = $baseRelativa.".".$extension;
        $rutaAbsoluta = $baseAbsoluta.".".$extension;
        if(file_exists($rutaAbsoluta)){
            return $rutaRelativa;
        }
    }

    $coincidencias = glob($baseAbsoluta.".*");
    if($coincidencias && isset($coincidencias[0])){
        $rutaRelativa = str_replace(__DIR__.DIRECTORY_SEPARATOR, "", $coincidencias[0]);
        return str_replace("\\", "/", $rutaRelativa);
    }

    return "";
}

function reportar_guardar_fotos_coach_adjuntos($idReporte, $fechaReferencia = "")
{
    $idReporte = (int)$idReporte;
    if ($idReporte <= 0) {
        return;
    }

    $uploadDir = 'archivos/reportes';
    $uploadDirAbsolute = __DIR__ . DIRECTORY_SEPARATOR . 'archivos' . DIRECTORY_SEPARATOR . 'reportes';
    if (!is_dir($uploadDirAbsolute)) {
        @mkdir($uploadDirAbsolute, 0775, true);
    }

    $fechaAdjunto = trim((string)$fechaReferencia);
    if ($fechaAdjunto === '') {
        $fechaAdjunto = date('Y-m-d');
    }

    $dbAdj = new DBbase_Sql;
    $adjuntosExistentes = array();
    $sqlAdj = "SELECT adj_id, adj_url, adj_can FROM tbl_adjuntos WHERE adj_rep_fk = " . $idReporte . " ORDER BY adj_id ASC";
    $dbAdj->query($sqlAdj);
    while ($dbAdj->next_record()) {
        $adjuntosExistentes[] = array(
            'id' => (int)$dbAdj->f('adj_id'),
            'url' => trim((string)$dbAdj->f('adj_url')),
            'slot' => (int)$dbAdj->f('adj_can')
        );
    }

    $adjuntosPorSlot = array();
    $adjuntosSecuenciales = array();
    foreach ($adjuntosExistentes as $adjuntoExistente) {
        if ($adjuntoExistente['slot'] >= 1 && $adjuntoExistente['slot'] <= 3 && !isset($adjuntosPorSlot[$adjuntoExistente['slot']])) {
            $adjuntosPorSlot[$adjuntoExistente['slot']] = $adjuntoExistente;
        } else {
            $adjuntosSecuenciales[] = $adjuntoExistente;
        }
    }

    foreach ($adjuntosSecuenciales as $adjuntoSecuencial) {
        for ($slotSec = 1; $slotSec <= 3; $slotSec++) {
            if (!isset($adjuntosPorSlot[$slotSec])) {
                $adjuntosPorSlot[$slotSec] = $adjuntoSecuencial;
                break;
            }
        }
    }

    for ($slot = 1; $slot <= 3; $slot++) {
        $campo = 'archivo' . $slot;
        if (
            !isset($_FILES[$campo]) ||
            !isset($_FILES[$campo]['error']) ||
            $_FILES[$campo]['error'] !== UPLOAD_ERR_OK ||
            trim((string)$_FILES[$campo]['name']) === ''
        ) {
            continue;
        }

        $nombreOriginal = trim((string)$_FILES[$campo]['name']);
        $extension = strtolower((string)extension_archivo($nombreOriginal));
        if ($extension === '') {
            continue;
        }

        $fileName = 'reporte_' . $idReporte . '_' . time() . '_' . $slot . '.' . $extension;
        $relativePath = $uploadDir . '/' . $fileName;
        $absolutePath = $uploadDirAbsolute . DIRECTORY_SEPARATOR . $fileName;
        $tmpName = $_FILES[$campo]['tmp_name'];

        $guardado = false;
        if (in_array($extension, array('png', 'jpg', 'jpeg', 'gif', 'webp'), true)) {
            $guardado = compressImage($tmpName, $absolutePath, 80);
        } else {
            $guardado = move_uploaded_file($tmpName, $absolutePath);
        }

        if (!$guardado) {
            continue;
        }

        $nombreOriginalSql = addslashes($nombreOriginal);
        $relativePathSql = addslashes(str_replace("\\", "/", $relativePath));

        if (isset($adjuntosPorSlot[$slot])) {
            $adjuntoActual = $adjuntosPorSlot[$slot];
            $rutaAnterior = trim((string)$adjuntoActual['url']);
            if ($rutaAnterior !== '') {
                $rutaAnteriorAbsoluta = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $rutaAnterior);
                if (is_file($rutaAnteriorAbsoluta) && str_replace("\\", "/", realpath($rutaAnteriorAbsoluta) ?: '') !== str_replace("\\", "/", realpath($absolutePath) ?: '')) {
                    @unlink($rutaAnteriorAbsoluta);
                }
            }

            $sqlUpdateAdj = "UPDATE tbl_adjuntos SET
                adj_nom = '" . $nombreOriginalSql . "',
                adj_url = '" . $relativePathSql . "',
                adj_fec = '" . addslashes($fechaAdjunto) . "',
                adj_can = " . (int)$slot . "
                WHERE adj_id = " . (int)$adjuntoActual['id'];
            $dbAdj->query($sqlUpdateAdj);
        } else {
            $sqlInsertAdj = "INSERT INTO tbl_adjuntos (
                adj_nom,
                adj_url,
                adj_fec,
                adj_can,
                adj_rep_fk
            ) VALUES (
                '" . $nombreOriginalSql . "',
                '" . $relativePathSql . "',
                '" . addslashes($fechaAdjunto) . "',
                " . (int)$slot . ",
                " . $idReporte . "
            )";
            $dbAdj->query($sqlInsertAdj);
        }
    }
}



/*
*   VERIFICAMOS CON QUE GENERACIÓN NOS ESTAMOS ENFRENTANDO ACTUALMENTE.
*/
$preguntarGeneracion = 0;
if(isset($_REQUEST["generacion"]) && $_REQUEST["generacion"] != ""){
    $generacionActual = eliminarInvalidos($_REQUEST["generacion"]);
}else{
    $preguntarGeneracion = 1;
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

$idActividadReporteActual = 0;
if($idReporteActual > 0){
    $PSNActividadReporte = new DBbase_Sql;
    $sqlActividadReporte = "SELECT id_actividad FROM sat_reportes WHERE id = '".$idReporteActual."' LIMIT 1";
    $PSNActividadReporte->query($sqlActividadReporte);
    if($PSNActividadReporte->next_record()){
        $idActividadReporteActual = (int)$PSNActividadReporte->f("id_actividad");
    }
}

$soloLecturaReporteFacilitador = (isset($_SESSION["perfil"]) && $_SESSION["perfil"] == 163 && $idReporteActual != 0);
$bloqueoEdicionReporteFacilitador = 0;
$error_datos = 0;
$texto_error = "";
$mensaje_error = "";
$campos_mapeo_faltantes = array();
$campos_mapeo_labels = reportar_obtener_campos_mapeo();


// Array que nos servira para ir llevando cuenta de los requerimientos.
$arrayRequerimientos = array();
if(isset($_POST["funcion"]) && $soloLecturaReporteFacilitador && ($_POST["funcion"] == "actualizar" || $_POST["funcion"] == "eliminar")){
    $bloqueoEdicionReporteFacilitador = 1;
}
else if(isset($_POST["funcion"])){
    /*
    *   Para verificar errores a futuro.
        1   Campos requeridos en BLANCO (Nombre, identificacion, password)
        2   Password no coincide
        3   Identificacion YA existente
    */
    //
    if($_POST["funcion"] == "insertar"){
        //die("Insertar");
        /*
        *   PESTAÑA GENERAL
        */
        $comentario = eliminarInvalidos($_REQUEST["final_comentarios"]);
        $plantador = eliminarInvalidos($_REQUEST["plantador"]);
        $fechaReporte = eliminarInvalidos($_REQUEST["fechaReporte"]);
        $fechaInicio = eliminarInvalidos($_REQUEST["fechaInicio"]);        
        $sitioReunion = eliminarInvalidos($_REQUEST["sitioReunion"]);
        $grupoMadre_txt = eliminarInvalidos($_REQUEST["grupoMadre_txt"]);
        $nombreGrupo_txt = eliminarInvalidos($_REQUEST["nombreGrupo_txt"]);
        
        $barrio = eliminarInvalidos($_REQUEST["barrio"]);
        $direccion = eliminarInvalidos($_REQUEST["direccion"]);
        $ciudad = eliminarInvalidos($_REQUEST["ciudad"]);

        $capacitacion_txt = eliminarInvalidos($_REQUEST["capacitacion_txt"]);
        $idGrupoMadre = soloNumeros($_REQUEST["idGrupoMadre"]);
        if(empty($idGrupoMadre)) $idGrupoMadre = 0;
        $generacionNumero = soloNumeros($_REQUEST["generacionNumero"]);
        if(empty($generacionNumero)) $generacionNumero = 0;

        $asistencia_hom = soloNumeros($_REQUEST["asistencia_hom"]);
        if(empty($asistencia_hom)) $asistencia_hom = 0;
        $asistencia_muj = soloNumeros($_REQUEST["asistencia_muj"]);
        if(empty($asistencia_muj)) $asistencia_muj = 0;
        $asistencia_jov = soloNumeros($_REQUEST["asistencia_jov"]);
        if(empty($asistencia_jov)) $asistencia_jov = 0;
        $asistencia_nin = soloNumeros($_REQUEST["asistencia_nin"]);
        if(empty($asistencia_nin)) $asistencia_nin = 0;

        $bautizados = soloNumeros($_REQUEST["final_bautizados"]);
        if(empty($bautizados)) $bautizados = 0;
        $bautizadosPeriodo = soloNumeros($_REQUEST["final_bautizadosPeriodo"]);
        if(empty($bautizadosPeriodo)) $bautizadosPeriodo = 0;

        $mapeo_anho = soloNumeros($_REQUEST["mapeo_anho"]);
        if(empty($mapeo_anho)) $mapeo_anho = 0;
        $mapeo_cuarto = soloNumeros($_REQUEST["mapeo_cuarto"]);
        if(empty($mapeo_cuarto)) $mapeo_cuarto = 0;
        
        
        $nombre_archivo = $_FILES['archivo1']['name'];
        $archivo1 = extension_archivo($nombre_archivo);
        
        $nombre_archivo = $_FILES['archivo2']['name'];
        $archivo2 = extension_archivo($nombre_archivo);
        
        $nombre_archivo = $_FILES['archivo3']['name'];
        $archivo3 = extension_archivo($nombre_archivo);

        
        
        $mapeo_fecha = eliminarInvalidos($_REQUEST["mapeo_fecha"]);
        if(empty($mapeo_fecha)) $mapeo_fecha = date("Y-m-d");
        $mapeo_comprometido = soloNumeros($_REQUEST["mapeo_comprometido"]);
        if(empty($mapeo_comprometido)) $mapeo_comprometido = 0;
        
        // Asignar valores de mapeo solo para reportes tipo OTRA (generaciones 1-5)
        if($generacionActual == "OTRA") {
            if(!reportar_mapeo_comprometido_es_valido($mapeo_comprometido)){
                $error_datos = 1;
                if(empty($mensaje_error)){
                    $mensaje_error = "Debe seleccionar si este grupo esta comprometido como iglesia.";
                }
            }
            $campos_mapeo_faltantes = reportar_obtener_mapeos_faltantes($_REQUEST);
            if(count($campos_mapeo_faltantes) > 0){
                $error_datos = 1;
                $mensaje_error = "Debe completar todos los campos de mapeo del formulario de coach. Faltan: ".implode(", ", array_values($campos_mapeo_faltantes)).".";
            }
            $mapeo_oracion = soloNumeros($_REQUEST["mapeo_oracion"]);
            if(empty($mapeo_oracion)) $mapeo_oracion = 0;
            $mapeo_companerismo = soloNumeros($_REQUEST["mapeo_companerismo"]);
            if(empty($mapeo_companerismo)) $mapeo_companerismo = 0;
            $mapeo_adoracion = soloNumeros($_REQUEST["mapeo_adoracion"]);
            if(empty($mapeo_adoracion)) $mapeo_adoracion = 0;
            $mapeo_biblia = soloNumeros($_REQUEST["mapeo_biblia"]);
            if(empty($mapeo_biblia)) $mapeo_biblia = 0;
            $mapeo_evangelizar = soloNumeros($_REQUEST["mapeo_evangelizar"]);
            if(empty($mapeo_evangelizar)) $mapeo_evangelizar = 0;
            $mapeo_cena = soloNumeros($_REQUEST["mapeo_cena"]);
            if(empty($mapeo_cena)) $mapeo_cena = 0;
            $mapeo_dar = soloNumeros($_REQUEST["mapeo_dar"]);
            if(empty($mapeo_dar)) $mapeo_dar = 0;
            $mapeo_bautizar = soloNumeros($_REQUEST["mapeo_bautizar"]);
            if(empty($mapeo_bautizar)) $mapeo_bautizar = 0;
            $mapeo_trabajadores = soloNumeros($_REQUEST["mapeo_trabajadores"]);
            if(empty($mapeo_trabajadores)) $mapeo_trabajadores = 0;
        } else {
            // Para reportes sin mapeo (CERO, EVAN, GCEL), asignar 0
            $mapeo_oracion = 0;
            $mapeo_companerismo = 0;
            $mapeo_adoracion = 0;
            $mapeo_biblia = 0;
            $mapeo_evangelizar = 0;
            $mapeo_cena = 0;
            $mapeo_dar = 0;
            $mapeo_bautizar = 0;
            $mapeo_trabajadores = 0;
        }

        //Calculados:
        $asistencia_total  = $asistencia_hom+$asistencia_muj+$asistencia_jov+$asistencia_nin;
        
        // Validar asistencia total >= 1
        if($asistencia_total < 1){
            $error_datos = 1;
            $mensaje_error = "La asistencia total debe ser mínimo 1 persona";
        }
        
        $discipulado  = soloNumeros($_REQUEST["final_discipulado"]);
        if(empty($discipulado)) $discipulado = 0;
        $desiciones  = soloNumeros($_REQUEST["final_desiciones"]);
        if(empty($desiciones)) $desiciones = 0;
        $preparandose  = soloNumeros($_REQUEST["final_preparandose"]);
        if(empty($preparandose)) $preparandose = 0;
        if($generacionActual == "EVAN" || (int)$generacionNumero == 77){
            $discipulado = 0;
            $preparandose = 0;
        }
        if($generacionActual == "BAUT"){
            $discipulado = 0;
            $desiciones = 0;
            $preparandose = 0;
            if($bautizados > $asistencia_total){
                $error_datos = 1;
                $mensaje_error = "Bautizados no puede ser mayor a la asistencia total";
            }
        }
        if($generacionActual == "GCEL" || (int)$generacionNumero == 8){
            $discipulado = 0;
            $desiciones = 0;
            $preparandose = 0;
        }
        $iglesias_reconocidas = 0;
        //        

        if($error_datos == 0){
            
            /*if($generacionActual == "CERO"){
                $sql = 'INSERT INTO sat_grupos (
                idUsuario,
                fechaInicio,
                nombre,
                descripcion,
                    creacionFecha,
                    creacionUsuario
                )';
            
                $sql .= ' VALUES 
                    (
                    "'.$_SESSION["id"].'", 
                    "'.$fechaInicio.'", 
                    "'.$grupoMadre_txt.'", 
                    "'.$grupoMadre_txt.'", 
                        NOW(), 
                        "'.$_SESSION["id"].'"
                    )';
                //
                //echo "Insertar sat_grupos: ".$sql;
                $ultimoQuery = $PSN1->query($sql);
                $idGrupoMadre =  $PSN1->ultimoId();
            }
            else{
                $fechaInicio = date("Y-m-d");
                $sql = 'SELECT fechaInicio FROM sat_grupos ';
                $sql .= ' WHERE id = "'.$idGrupoMadre.'"';
                $PSN1->query($sql);
                if($PSN1->num_rows() > 0)
                {
                    if($PSN1->next_record())
                    {
                        $fechaInicio = $PSN1->f("fechaInicio");
                    }
                }
            }*/
            
            
            /*
            *   DEBEMOS INSERTAR LA INFORMACION DEL REPORTE SEGUN CORRESPONDA.
            */
            $sql = 'INSERT INTO sat_reportes (
                idUsuario,
                inactivo,
                idGrupoMadre,
                generacionNumero,
                plantador,
                fechaReporte,
                fechaInicio,
                sitioReunion,
                grupoMadre_txt,
                nombreGrupo_txt,
                capacitacion_txt,
                barrio,
                direccion,
                ciudad,
                asistencia_total,
                asistencia_hom,
                asistencia_muj,
                asistencia_jov,
                asistencia_nin,
                bautizados,
                discipulado,
                desiciones,
                preparandose,
                bautizadosPeriodo,
                iglesias_reconocidas,
                creacionFecha,
                creacionUsuario,
                modificacionFecha,
                modificacionUsuario,
                ext1,
                ext2,
                mapeo_anho,
                mapeo_cuarto,
                ext3,
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
                comentario
                )';
            
            $sql .= ' VALUES
                (
                "'.$_SESSION["id"].'",
                0,
                "'.$idGrupoMadre.'",
                "'.$generacionNumero.'",
                "'.$plantador.'",
                "'.$fechaReporte.'",
                "'.$fechaInicio.'",
                "'.$sitioReunion.'",
                "'.$grupoMadre_txt.'",
                "'.$nombreGrupo_txt.'",
                "'.$capacitacion_txt.'",
                "'.$barrio.'",
                "'.$direccion.'",
                "'.$ciudad.'",
                "'.$asistencia_total.'",
                "'.$asistencia_hom.'",
                "'.$asistencia_muj.'",
                "'.$asistencia_jov.'",
                "'.$asistencia_nin.'",
                "'.$bautizados.'",
                "'.$discipulado.'",
                "'.$desiciones.'",
                "'.$preparandose.'",
                "'.$bautizadosPeriodo.'",
                0,
                NOW(),
                "'.$_SESSION["id"].'",
                NOW(),
                "'.$_SESSION["id"].'",
                "'.$archivo1.'",
                "'.$archivo2.'",
                "'.$mapeo_anho.'",
                "'.$mapeo_cuarto.'",
                "'.$archivo3.'",
                "'.$mapeo_fecha.'",
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
                "'.$comentario.'"

                )';
            
            //
            //
            //echo "Insertar sat_reportes: ".$sql;
            $ultimoQuery = $PSN1->query($sql);
            $ultimoId =  $PSN1->ultimoId();
            if ($bautizadosPeriodo>0) {
                $act_bau_img = $_FILES["act_bau_img"];
                $act_bau_fec = $_REQUEST['act_bau_fec'];
                $act_bau_can = $_REQUEST['act_bau_can'];

                $sql = 'INSERT INTO tbl_adjuntos (
                    adj_nom,
                    adj_url,
                    adj_fec,
                    adj_can, 
                    adj_rep_fk)';
                $sql .= 'VALUES';
                for ($i=0; $i < sizeof($act_bau_fec); $i++) { 
                    $tp_arch = extension_archivo($act_bau_img['name'][$i]);
                    $sql .= "('".$act_bau_img['name'][$i]."','archivos/evi_".$ultimoId."_".$i.".".$tp_arch."','".$act_bau_fec[$i]."',".$act_bau_can[$i].",".$ultimoId."),";
                    $extArchivo = $tp_arch;
                    if($extArchivo == "png" || $extArchivo == "jpg" || $extArchivo == "jpeg" || $extArchivo == "gif"){
                        $rutaOr = $act_bau_img['tmp_name'][$i];
                        $rutaDe = "archivos/evi_".$ultimoId."_".$i.".".$tp_arch;
                        compressImage($rutaOr, $rutaDe, 80);
                    }else{
                        if(move_uploaded_file($act_bau_img['tmp_name'][$i], "archivos/evi_".$i.".".$tp_arch)){
                        }            
                    }
                }
                $sql = substr($sql, 0, -1);
                //echo $sql;
                $ultimoQuery = $PSN1->query($sql);
            }
            if (((isset($idActividad) && ((int)$idActividad === 1 || (int)$idActividad === 99 || (int)$idActividad === 8)) || $idActividadReporteActual === 1 || $idActividadReporteActual === 99 || $idActividadReporteActual === 8)) {
                reportar_guardar_fotos_coach_adjuntos($ultimoId, $fechaReporte);
            }
            //      
            //if($generacionNumero > 0){
                // Compress Image
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
                }
            //}
            //            
            $varExitoREP = 1;
        }
    }//Fin del IF de insertar
    else if($_POST["funcion"] == "eliminar"){
        $sql = 'DELETE from sat_reportes WHERE id = "'.$idReporteActual.'"';
        $PSN1->query($sql);
    }
    else if($_POST["funcion"] == "actualizar"){
       // die("Actualizar");
        //
        /*
        *   PESTAÑA GENERAL
        */
        $plantador = eliminarInvalidos($_REQUEST["plantador"]);
        $comentario = eliminarInvalidos($_REQUEST["final_comentarios"]);
        $fechaReporte = eliminarInvalidos($_REQUEST["fechaReporte"]);
        $fechaInicio = eliminarInvalidos($_REQUEST["fechaInicio"]);        
        $sitioReunion = eliminarInvalidos($_REQUEST["sitioReunion"]);
        $grupoMadre_txt = eliminarInvalidos($_REQUEST["grupoMadre_txt"]);
        $nombreGrupo_txt = eliminarInvalidos($_REQUEST["nombreGrupo_txt"]);
        
        
        $inactivo = soloNumeros($_REQUEST["inactivo"]);
        
        
        $capacitacion_txt = eliminarInvalidos($_REQUEST["capacitacion_txt"]);        
        $idGrupoMadre = soloNumeros($_REQUEST["idGrupoMadre"]);
        $generacionNumero = soloNumeros($_REQUEST["generacionNumero"]);
        
        $barrio = eliminarInvalidos($_REQUEST["barrio"]);
        $direccion = eliminarInvalidos($_REQUEST["direccion"]);
        $ciudad = eliminarInvalidos($_REQUEST["ciudad"]);

        $asistencia_hom = soloNumeros($_REQUEST["final_asistencia_hom"]);
        $asistencia_muj = soloNumeros($_REQUEST["final_asistencia_muj"]);
        $asistencia_jov = soloNumeros($_REQUEST["final_asistencia_jov"]);
        $asistencia_nin = soloNumeros($_REQUEST["final_asistencia_nin"]);

        $bautizados = soloNumeros($_REQUEST["final_bautizados"]);        
        $bautizadosPeriodo = soloNumeros($_REQUEST["final_bautizadosPeriodo"]);
        

        //Calculados:
        $asistencia_total  = $asistencia_hom+$asistencia_muj+$asistencia_jov+$asistencia_nin;
        
        // Validar asistencia total >= 1
        if($asistencia_total < 1){
            $error_datos = 1;
            $mensaje_error = "La asistencia total debe ser mínimo 1 persona";
        }
        
        $discipulado  = soloNumeros($_REQUEST["final_discipulado"]);
        $desiciones  = soloNumeros($_REQUEST["final_desiciones"]);
        $preparandose  = soloNumeros($_REQUEST["final_preparandose"]);
        if($generacionActual == "EVAN" || (int)$generacionNumero == 77){
            $discipulado = 0;
            $preparandose = 0;
        }
        if($generacionActual == "BAUT"){
            $discipulado = 0;
            $desiciones = 0;
            $preparandose = 0;
            if($bautizados > $asistencia_total){
                $error_datos = 1;
                $mensaje_error = "Bautizados no puede ser mayor a la asistencia total";
            }
        }
        if($generacionActual == "GCEL" || (int)$generacionNumero == 8){
            $discipulado = 0;
            $desiciones = 0;
            $preparandose = 0;
        }
        $iglesias_reconocidas = 0;
        
        
        $mapeo_anho = soloNumeros($_REQUEST["mapeo_anho"]);
        $mapeo_cuarto = soloNumeros($_REQUEST["mapeo_cuarto"]);
        
        
        $nombre_archivo = $_FILES['archivo1']['name'];
        $archivo1 = extension_archivo($nombre_archivo);
        
        $nombre_archivo = $_FILES['archivo2']['name'];
        $archivo2 = extension_archivo($nombre_archivo);
        
        $nombre_archivo = $_FILES['archivo3']['name'];
        $archivo3 = extension_archivo($nombre_archivo);

        
        
        $mapeo_fecha = eliminarInvalidos($_REQUEST["mapeo_fecha"]);
        if(empty($mapeo_fecha)) $mapeo_fecha = date("Y-m-d");
        $mapeo_comprometido = soloNumeros($_REQUEST["mapeo_comprometido"]);
        if(empty($mapeo_comprometido)) $mapeo_comprometido = 0;
        
        // Asignar valores de mapeo solo para reportes tipo OTRA (generaciones 1-5)
        if($generacionActual == "OTRA") {
            if(!reportar_mapeo_comprometido_es_valido($mapeo_comprometido)){
                $error_datos = 1;
                if(empty($mensaje_error)){
                    $mensaje_error = "Debe seleccionar si este grupo esta comprometido como iglesia.";
                }
            }
            $campos_mapeo_faltantes = reportar_obtener_mapeos_faltantes($_REQUEST);
            if(count($campos_mapeo_faltantes) > 0){
                $error_datos = 1;
                $mensaje_error = "Debe completar todos los campos de mapeo del formulario de coach. Faltan: ".implode(", ", array_values($campos_mapeo_faltantes)).".";
            }
            $mapeo_oracion = soloNumeros($_REQUEST["mapeo_oracion"]);
            if(empty($mapeo_oracion)) $mapeo_oracion = 0;
            $mapeo_companerismo = soloNumeros($_REQUEST["mapeo_companerismo"]);
            if(empty($mapeo_companerismo)) $mapeo_companerismo = 0;
            $mapeo_adoracion = soloNumeros($_REQUEST["mapeo_adoracion"]);
            if(empty($mapeo_adoracion)) $mapeo_adoracion = 0;
            $mapeo_biblia = soloNumeros($_REQUEST["mapeo_biblia"]);
            if(empty($mapeo_biblia)) $mapeo_biblia = 0;
            $mapeo_evangelizar = soloNumeros($_REQUEST["mapeo_evangelizar"]);
            if(empty($mapeo_evangelizar)) $mapeo_evangelizar = 0;
            $mapeo_cena = soloNumeros($_REQUEST["mapeo_cena"]);
            if(empty($mapeo_cena)) $mapeo_cena = 0;
            $mapeo_dar = soloNumeros($_REQUEST["mapeo_dar"]);
            if(empty($mapeo_dar)) $mapeo_dar = 0;
            $mapeo_bautizar = soloNumeros($_REQUEST["mapeo_bautizar"]);
            if(empty($mapeo_bautizar)) $mapeo_bautizar = 0;
            $mapeo_trabajadores = soloNumeros($_REQUEST["mapeo_trabajadores"]);
            if(empty($mapeo_trabajadores)) $mapeo_trabajadores = 0;
        } else {
            // Para reportes sin mapeo (CERO, EVAN, GCEL), asignar NULL
            $mapeo_oracion = null;        
            $mapeo_companerismo = null;        
            $mapeo_adoracion = null;        
            $mapeo_biblia = null;        
            $mapeo_evangelizar = null;        
            $mapeo_cena = null;        
            $mapeo_dar = null;        
            $mapeo_bautizar = null;        
            $mapeo_trabajadores = null;
        }
        
        if($error_datos == 0){
        //
        $sql = 'UPDATE  sat_reportes SET 
                    inactivo = "'.$inactivo.'", 
                    comentario = "'.$comentario.'", 
                    plantador = "'.$plantador.'", 
                    fechaInicio = "'.$fechaInicio.'", 
                    sitioReunion = "'.$sitioReunion.'", 
                    grupoMadre_txt = "'.$grupoMadre_txt.'", 
                    nombreGrupo_txt = "'.$nombreGrupo_txt.'",                     
                    capacitacion_txt = "'.$capacitacion_txt.'", 
                    generacionNumero = "'.$generacionNumero.'", 

                    barrio = "'.$barrio.'", 
                    direccion = "'.$direccion.'", 
                    ciudad = "'.$ciudad.'", 

                        asistencia_hom = "'.$asistencia_hom.'", 
                        asistencia_muj = "'.$asistencia_muj.'", 
                        asistencia_jov = "'.$asistencia_jov.'", 
                        asistencia_nin =  "'.$asistencia_nin.'", 

                    bautizados =  "'.$bautizados.'", 
                    bautizadosPeriodo = "'.$bautizadosPeriodo.'", 

                    asistencia_total = "'.$asistencia_total.'", 
                    discipulado = "'.$discipulado.'", 
                    desiciones =  "'.$desiciones.'", 
                    preparandose = "'.$preparandose.'",


                    mapeo_fecha = "'.$mapeo_fecha.'",
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
        $PSN1->query($sql);
        $act_bau_id = array();
        if (isset($_REQUEST['act_bau_id']) && isset($_REQUEST['act_bau_fec']) && isset($_REQUEST['act_bau_can'])) {
            $act_bau_img = $_FILES["act_bau_img"];
            $act_bau_imgAn = $_REQUEST["act_bau_img_an"];
            $act_bau_fec = $_REQUEST['act_bau_fec'];
            $act_bau_can = $_REQUEST['act_bau_can'];
            $act_bau_id = $_REQUEST['act_bau_id'];
            //echo "Si hay antiguos a modificar: ".sizeof($act_bau_id);
            //var_dump($act_bau_id);
            for ($i=0; $i < sizeof($act_bau_id); $i++) {
                
                $sqlA = "UPDATE  tbl_adjuntos SET ";
                if (!empty($act_bau_img['name'][$i])) {
                    $tp_arch = extension_archivo($act_bau_img['name'][$i]);
                    $sqlA .= "adj_nom = '".$act_bau_img['name'][$i]."', adj_url = 'archivos/evi_".$idReporteActual."_".$i.".".$tp_arch."',";
                    if($extArchivo == "png" || $extArchivo == "jpg" || $extArchivo == "jpeg" || $extArchivo == "gif"){
                        //echo "No elimina";
                        $rutaOr = $act_bau_img['tmp_name'][$i];
                        $rutaDe = "archivos/evi_".$idReporteActual."_".$i.".".$tp_arch;

                        compressImage($rutaOr, $rutaDe, 80);
                    }else{
                        //echo "Si elimina: ".$act_bau_imgAn[$i];
                        unlink("./".$act_bau_imgAn[$i]);
                        if(move_uploaded_file($act_bau_img['tmp_name'][$i], "archivos/evi_".$idReporteActual."_".$i.".".$tp_arch)){
                        }            
                    }
                }
                    $sqlA .= "adj_fec = '".$act_bau_fec[$i]."', 
                    adj_can = ".$act_bau_can[$i].",
                    adj_rep_fk = ".$idReporteActual."
                    WHERE adj_id = ".$act_bau_id[$i]." ";
                //echo $sqlA;
                $PSN1->query($sqlA);
            }
        }
        $act_bau_can = isset($_REQUEST['act_bau_can']) && is_array($_REQUEST['act_bau_can']) ? $_REQUEST['act_bau_can'] : array();
        $act_bau_fec = isset($_REQUEST['act_bau_fec']) && is_array($_REQUEST['act_bau_fec']) ? $_REQUEST['act_bau_fec'] : array();
        $totalReg= 0;
        //var_dump($act_bau_can);
        for ($i=0; $i < sizeof($act_bau_can); $i++) { 
            if (!empty($act_bau_can[$i])&& !empty($act_bau_fec[$i])) {
                $totalReg++;
            }
        }
        //echo $totalReg;
        $nuevos = $totalReg-sizeof($act_bau_id );
        //echo "Total de registros: ".$totalReg." nuevos: ".$nuevos;
        if ($nuevos>0) {
            //echo "Si hay nuevos a crear: ".$nuevos;
            $act_bau_img = $_FILES["act_bau_img"];                
            $sql = 'INSERT INTO tbl_adjuntos (
                adj_nom,
                adj_url,
                adj_fec,
                adj_can, 
                adj_rep_fk)';
            $sql .= 'VALUES';
            for ($i=(sizeof($act_bau_fec)-$nuevos); $i < sizeof($act_bau_fec); $i++) { 
                $tp_arch = extension_archivo($act_bau_img['name'][$i]);
                $sql .= "('".$act_bau_img['name'][$i]."','archivos/evi_".$idReporteActual."_".$i.".".$tp_arch."','".$act_bau_fec[$i]."',".$act_bau_can[$i].",".$idReporteActual."),";
                $extArchivo = $tp_arch;
                if($extArchivo == "png" || $extArchivo == "jpg" || $extArchivo == "jpeg" || $extArchivo == "gif"){
                    $rutaOr = $act_bau_img['tmp_name'][$i];
                    $rutaDe = "archivos/evi_".$idReporteActual."_".$i.".".$tp_arch;
                    compressImage($rutaOr, $rutaDe, 80);
                }else{
                    if(move_uploaded_file($act_bau_img['tmp_name'][$i], "archivos/evi_".$idReporteActual."_".$i.".".$tp_arch)){
                    }            
                }
            }
            $sql = substr($sql, 0, -1);
            //echo $sql;
            $ultimoQuery = $PSN1->query($sql);
        }
        $varExitoREP_UPD = 1;
        }
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
                if (((isset($idActividad) && ((int)$idActividad === 1 || (int)$idActividad === 99 || (int)$idActividad === 8)) || $idActividadReporteActual === 1 || $idActividadReporteActual === 99 || $idActividadReporteActual === 8)) {
                    reportar_guardar_fotos_coach_adjuntos($idReporteActual, $fechaReporte);
                }
                //
            //}        
        
        //
    }
}


switch($error_datos){
    case 1:
        $texto_error = ($mensaje_error != "") ? $mensaje_error : "Datos requeridos.";
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
    $sql = "SELECT sat_reportes.*, sat_grupos.nombre, grupo_base.id AS idGrupoBaseReporte, grupo_base.nombreGrupo_txt AS nombreGrupoBaseReporte, grupo_base.grupoMadre_txt AS grupoMadreBaseReporte, grupo_base.generacionNumero AS generacionGrupoBaseReporte ";
    $sql.=" FROM sat_reportes LEFT JOIN sat_grupos ON sat_grupos.id = sat_reportes.idGrupoMadre ";
    $sql.=" LEFT JOIN sat_reportes AS grupo_base ON grupo_base.id = sat_reportes.id_grupo ";
    $sql.=" WHERE sat_reportes.id = '".$idReporteActual."'";
    $sql.=" GROUP BY sat_reportes.id";
    $PSN1->query($sql);
    if($PSN1->num_rows() > 0)
    {
        if($PSN1->next_record())
        {
            $inactivo = $PSN1->f("inactivo");
            $comentario = $PSN1->f("comentario");
            $plantador = reportar_normalizar_plantador($PSN1->f("plantador"));
            $fechaReporte = $PSN1->f("fechaReporte");
            $fechaInicio = $PSN1->f("fechaInicio");        
            $sitioReunion = $PSN1->f("sitioReunion");
            $grupoMadre_txt = $PSN1->f("grupoMadre_txt");
            $nombreGrupo_txt = $PSN1->f("nombreGrupo_txt");
            
            $capacitacion_txt = $PSN1->f("capacitacion_txt");

            $barrio = $PSN1->f("barrio");
            $direccion = $PSN1->f("direccion");
            $ciudad = $PSN1->f("ciudad");
            
            $ext1 = $PSN1->f("ext1");
            $ext2 = $PSN1->f("ext2");
            $ext3 = $PSN1->f("ext3");
            
            $idGrupoMadre = $PSN1->f("idGrupoMadre");
            $idGrupoReporte = $PSN1->f("id_grupo");
            $generacionNumeroOriginal = $PSN1->f("generacionNumero");
            $generacionNumero = $generacionNumeroOriginal;
            $idActividad = $PSN1->f("id_actividad");
            $nombreGrupoPertenece = trim($PSN1->f("nombreGrupoBaseReporte"));
            if($nombreGrupoPertenece == ""){
                $nombreGrupoPertenece = trim($nombreGrupo_txt);
            }
            $nombreGrupoMadreDetalle = trim($PSN1->f("grupoMadreBaseReporte"));
            if($nombreGrupoMadreDetalle == ""){
                $nombreGrupoMadreDetalle = trim($grupoMadre_txt);
            }
            $generacionPertenece = trim($PSN1->f("generacionGrupoBaseReporte"));
            if($generacionPertenece === ""){
                $generacionPertenece = $generacionNumeroOriginal;
            }
            if(in_array((int)$idActividad, array(77, 5, 10, 11, 12, 13, 14, 100), true)){
                $generacionNumero = 77;
            }else if((int)$idActividad == 8){
                $generacionNumero = 8;
            }else if((int)$idActividad == 1){
                $generacionNumero = 1;
            }
            if((int)$generacionNumero == 77){
                $generacionActual = "EVAN";
            }else if((int)$generacionNumero == 8){
                $generacionActual = "GCEL";
            }else if((int)$generacionNumero == 0){
                $generacionActual = "CERO";
            }else{
                $generacionActual = "OTRA";
            }
            if((int)$idActividad == 99){
                $generacionActual = "BAUT";
            }

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
            $preparandose  = $PSN1->f("preparandose");
            $iglesias_reconocidas = $PSN1->f("iglesias_reconocidas");  
            
            
            $mapeo_fecha = $PSN1->f("mapeo_fecha");  
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

            $esActividadEvangelismo = in_array((int)$idActividad, array(77, 5, 10, 11, 12, 13, 14, 100), true);
            $esActividadGranCelebracion = ((int)$idActividad == 8);
            $esActividadCoach = ((int)$idActividad == 1);
            $esActividadBautizo = ((int)$idActividad == 99);
            $esActividadResumen = ($esActividadEvangelismo || $esActividadBautizo);
            $usaFormatoSimple = ((int)$generacionNumero == 0 || (int)$generacionNumero == 77 || (int)$generacionNumero == 8 || $esActividadBautizo);
            $rutaFoto1 = obtenerRutaImagenReporte($idReporteActual, 1, $ext1);
            $rutaFoto2 = obtenerRutaImagenReporte($idReporteActual, 2, $ext2);
            $rutaFoto3 = obtenerRutaImagenReporte($idReporteActual, 3, $ext3);
            
            
            //
        }//chequear el registro
    }else{
        ?><div class="row">
            <h3 class="alert alert-info text-center">Registro eliminado</h3>
        </div>
        <div class="form-group">
            <center><input type="button" onClick="window.location.href='index.php?doc=reportar_buscar'" name="previous" class="previous btn btn-danger" value="Cerrar" /> <br />
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
    $adjuntosReporte = array();
    $PSNAdj = new DBbase_Sql;
    $sqlAdj = "SELECT adj_id, adj_nom, adj_url, adj_fec, adj_can ";
    $sqlAdj.= "FROM tbl_adjuntos ";
    $sqlAdj.= "WHERE adj_rep_fk = '".$idReporteActual."' ";
    $sqlAdj.= "ORDER BY adj_id ASC";
    $PSNAdj->query($sqlAdj);
    if($PSNAdj->num_rows() > 0){
        while($PSNAdj->next_record()){
            $adjUrl = trim($PSNAdj->f("adj_url"));
            if($adjUrl != ""){
                $adjuntosReporte[] = array(
                    "id" => $PSNAdj->f("adj_id"),
                    "nombre" => $PSNAdj->f("adj_nom"),
                    "url" => str_replace("\\", "/", $adjUrl),
                    "fecha" => $PSNAdj->f("adj_fec"),
                    "cantidad" => $PSNAdj->f("adj_can")
                );
            }
        }
    }
    $coachFotosReporte = array();
    if($esActividadCoach){
        foreach($adjuntosReporte as $adjuntoCoach){
            $slotCoach = (int)$adjuntoCoach["cantidad"];
            if($slotCoach >= 1 && $slotCoach <= 3){
                $coachFotosReporte[$slotCoach - 1] = $adjuntoCoach;
            }
        }
        if(count($coachFotosReporte) === 0){
            $adjuntosCoachSecuenciales = array_slice($adjuntosReporte, 0, 3);
            foreach($adjuntosCoachSecuenciales as $indiceCoachSec => $adjuntoCoachSec){
                $coachFotosReporte[$indiceCoachSec] = $adjuntoCoachSec;
            }
        }
        ksort($coachFotosReporte);
        if(count($coachFotosReporte) === 0){
            $rutasCoachFallback = array($rutaFoto1, $rutaFoto2, $rutaFoto3);
            foreach($rutasCoachFallback as $indiceCoach => $rutaCoachFallback){
                if(trim((string)$rutaCoachFallback) !== ""){
                    $coachFotosReporte[] = array(
                        "id" => 0,
                        "nombre" => "Foto ".($indiceCoach + 1),
                        "url" => str_replace("\\", "/", $rutaCoachFallback),
                        "fecha" => $fechaReporte,
                        "cantidad" => 0
                    );
                }
            }
        }
    }
    $bautizoFotosReporte = array();
    if($esActividadBautizo){
        foreach($adjuntosReporte as $adjuntoBautizo){
            $slotBautizo = (int)$adjuntoBautizo["cantidad"];
            if($slotBautizo >= 1 && $slotBautizo <= 3 && !isset($bautizoFotosReporte[$slotBautizo - 1])){
                $bautizoFotosReporte[$slotBautizo - 1] = $adjuntoBautizo;
            }
        }
        if(count($bautizoFotosReporte) === 0){
            $adjuntosBautizoSecuenciales = array_slice($adjuntosReporte, 0, 3);
            foreach($adjuntosBautizoSecuenciales as $indiceBautizoSec => $adjuntoBautizoSec){
                $bautizoFotosReporte[$indiceBautizoSec] = $adjuntoBautizoSec;
            }
        }
        ksort($bautizoFotosReporte);
        if(count($bautizoFotosReporte) === 0){
            $rutasBautizoFallback = array($rutaFoto1, $rutaFoto2, $rutaFoto3);
            foreach($rutasBautizoFallback as $indiceBautizo => $rutaBautizoFallback){
                if(trim((string)$rutaBautizoFallback) !== ""){
                    $bautizoFotosReporte[$indiceBautizo] = array(
                        "id" => 0,
                        "nombre" => "Foto ".($indiceBautizo + 1),
                        "url" => str_replace("\\", "/", $rutaBautizoFallback),
                        "fecha" => $fechaReporte,
                        "cantidad" => $indiceBautizo + 1
                    );
                }
            }
        }
    }
    $granCelebracionFotosReporte = array();
    if($esActividadGranCelebracion){
        foreach($adjuntosReporte as $adjuntoGranCelebracion){
            $slotGranCelebracion = (int)$adjuntoGranCelebracion["cantidad"];
            if($slotGranCelebracion >= 1 && $slotGranCelebracion <= 3 && !isset($granCelebracionFotosReporte[$slotGranCelebracion - 1])){
                $granCelebracionFotosReporte[$slotGranCelebracion - 1] = $adjuntoGranCelebracion;
            }
        }
        if(count($granCelebracionFotosReporte) === 0){
            $adjuntosGranCelebracionSecuenciales = array_slice($adjuntosReporte, 0, 3);
            foreach($adjuntosGranCelebracionSecuenciales as $indiceGranCelebracionSec => $adjuntoGranCelebracionSec){
                $granCelebracionFotosReporte[$indiceGranCelebracionSec] = $adjuntoGranCelebracionSec;
            }
        }
        ksort($granCelebracionFotosReporte);
        if(count($granCelebracionFotosReporte) === 0){
            $rutasGranCelebracionFallback = array($rutaFoto1, $rutaFoto2, $rutaFoto3);
            foreach($rutasGranCelebracionFallback as $indiceGranCelebracion => $rutaGranCelebracionFallback){
                if(trim((string)$rutaGranCelebracionFallback) !== ""){
                    $granCelebracionFotosReporte[$indiceGranCelebracion] = array(
                        "id" => 0,
                        "nombre" => "Foto ".($indiceGranCelebracion + 1),
                        "url" => str_replace("\\", "/", $rutaGranCelebracionFallback),
                        "fecha" => $fechaReporte,
                        "cantidad" => $indiceGranCelebracion + 1
                    );
                }
            }
        }
    }
    ?><div class="container">
    <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form-horizontal">
        <div id="form-inline-error" class="alert alert-danger text-center" <?php if($texto_error == ""){ ?>style="display:none;"<?php } ?>><?=reportar_escape_attr($texto_error); ?></div>
        <h2 class="alert alert-info text-center"><?php
            if($idReporteActual == 0){
                echo "CREACIÓN";
            }else{
                echo "VISUALIZACIÓN";
                $sqlU = "SELECT id FROM sat_reportes WHERE id = (SELECT MAX(id)FROM sat_reportes        WHERE id < ".$idReporteActual.");";
                $PSN1->query($sqlU); 
                if($PSN1->num_rows() > 0){
                    if($PSN1->next_record()){
                    $antId  = $PSN1->f('id');
                    }
                }else{
                   $antId  = 0; 
                }
                $sqlU = "SELECT id FROM sat_reportes WHERE id = (SELECT MIN(id)FROM sat_reportes        WHERE id > ".$idReporteActual.");";
                $PSN1->query($sqlU); 
                if($PSN1->num_rows() > 0){
                    if($PSN1->next_record()){
                    $sigId  = $PSN1->f('id');
                    }
                }else{
                   $sigId  = 0; 
                }              
            }
            
            ?> DE <?=$temp_letrero; ?></h2>
            <?php if ($soloLecturaReporteFacilitador || $bloqueoEdicionReporteFacilitador) { ?>
            <div class="row">
                <h5 class="alert alert-warning text-center">Este reporte está en modo solo lectura para facilitadores.</h5>
            </div>
            <?php } ?>
            <?php if ($_SESSION["perfil"] == 162 || $_SESSION["perfil"] == 2){ ?>
            <div class="cont-btn cont-flex fl-sbet">
                <div class="item-btn">
                    <?php
                    if ($antId != 0) {?>
                    <a href="index.php?doc=reportar&id=<?=$antId ?>" name="previous" class="previous btn btn-info">Anterior reporte <?=$antId ?></a>
                    <?php } ?>
                </div>
                <div class="item-btn">
                    <a href="index.php?doc=reportar_buscar" name="previous" class="btn btn-warning">Todos los reportes</a>
                </div>
                <div class="item-btn">
                    <?php
                    if ($sigId != 0) {?>
                    <a href="index.php?doc=reportar&id=<?=$sigId ?>" name="previous" class="previous btn btn-info">Siguiente reporte <?=$sigId ?></a>
                    <?php } ?>
                </div>
            </div>
    <?php } ?>
        <?php if ($soloLecturaReporteFacilitador) { ?>
        <fieldset id="reporte_solo_lectura" disabled="disabled">
        <?php } ?>
        <?php if(!$esActividadBautizo){ ?>
        <div class="cont-tit" <?php if($esActividadEvangelismo){ ?>style="display:none;"<?php } ?>>
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center">INFORMACIÓN GENERAL</h3>
                <h5>REGISTRO ID: <?=str_pad($idReporteActual, 6, "0", STR_PAD_LEFT); ?></h5>
            </div>
            <div class="hr"><hr></div>
        </div>
        <?php } ?> 
        
        <?php if($esActividadResumen){ ?>
        <?php if(!$esActividadEvangelismo && !$esActividadBautizo){ ?>
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center">DETALLE DEL REPORTE</h3>
                <h5><?php echo $esActividadEvangelismo ? "ACTIVIDAD DE EVANGELISMO" : "ACTIVIDAD DE BAUTIZO"; ?></h5>
            </div>
            <div class="hr"><hr></div>
        </div>
        <?php } ?>
        <div class="detalle-actividad-77">
        <?php } ?>
        <?php if(false && (int)$idActividad == 77){ ?>
        <div style="display:none;">
        <div class="form-group">
            <div class="col-sm-4">
                <strong>Plantador:</strong>
                <input type="text" value="<?=reportar_escape_attr($plantador); ?>" class="form-control" readonly />
            </div>
            <div class="col-sm-2">
                <strong>Fecha reporte:</strong>
                <input type="text" value="<?=$fechaReporte; ?>" class="form-control" readonly />
            </div>
            <div class="col-sm-2">
                <strong>Fecha inicio:</strong>
                <input type="text" value="<?=$fechaInicio; ?>" class="form-control" readonly />
            </div>
            <div class="col-sm-4">
                <strong>Nombre del grupo:</strong>
                <input type="text" value="<?=$nombreGrupoPertenece; ?>" class="form-control" readonly />
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-3">
                <strong>Barrio:</strong>
                <input type="text" value="<?=$barrio; ?>" class="form-control" readonly />
            </div>
            <div class="col-sm-4">
                <strong>Direccion:</strong>
                <input type="text" value="<?=$direccion; ?>" class="form-control" readonly />
            </div>
            <div class="col-sm-3">
                <strong>Ciudad:</strong>
                <input type="text" value="<?=$ciudad; ?>" class="form-control" readonly />
            </div>
            <div class="col-sm-2">
                <strong>Generacion:</strong>
                <input type="text" value="<?=$generacionPertenece; ?>" class="form-control" readonly />
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-6">
                <strong>Grupo madre:</strong>
                <input type="text" value="<?php if(trim($nombreGrupoMadreDetalle) != ""){ echo $nombreGrupoMadreDetalle; }else{ echo "Sin grupo madre"; } ?>" class="form-control" readonly />
            </div>
            <div class="col-sm-1">
                <strong>Hombres:</strong>
                <input type="text" value="<?=$asistencia_hom; ?>" class="form-control" readonly />
            </div>
            <div class="col-sm-1">
                <strong>Mujeres:</strong>
                <input type="text" value="<?=$asistencia_muj; ?>" class="form-control" readonly />
            </div>
            <div class="col-sm-1">
                <strong>Jovenes:</strong>
                <input type="text" value="<?=$asistencia_jov; ?>" class="form-control" readonly />
            </div>
            <div class="col-sm-1">
                <strong>Niños:</strong>
                <input type="text" value="<?=$asistencia_nin; ?>" class="form-control" readonly />
            </div>
            <div class="col-sm-2">
                <strong>Asistencia total:</strong>
                <input type="text" value="<?=$asistencia_total; ?>" class="form-control" readonly />
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-3">
                <strong>Decisiones de fe:</strong>
                <input type="text" value="<?=$desiciones; ?>" class="form-control" readonly />
            </div>
            <div class="col-sm-9">
                <strong>Comentarios:</strong>
                <textarea class="form-control" rows="3" readonly><?php echo $comentario; ?></textarea>
            </div>
        </div>
        <div class="cont-tit" <?php if($esActividadEvangelismo){ ?>style="display:none;"<?php } ?>>
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center">FOTOGRAFIAS DEL REPORTE</h3>
                <h5>EVIDENCIA CARGADA EN EL FORMULARIO</h5>
            </div>
            <div class="hr"><hr></div>
        </div>
        <div class="cont-flex fl-sard">
            <div class="cont-item col-sm-4">
                <div class="form-group">
                    <div class="col-sm-12">
                        <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto 1:</strong>
                        <?php
                        if($ext1 == "" || !file_exists("archivos/evi_".$idReporteActual."_1.".$ext1)){
                            echo "<div class='alert alert-danger'>Sin foto cargada</div>";
                        }else{?>
                            <a href="archivos/evi_<?=$idReporteActual; ?>_1.<?=$ext1; ?>" target="_blank"><img src="archivos/evi_<?=$idReporteActual; ?>_1.<?=$ext1; ?>" width="100%" /></a>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="cont-item col-sm-4">
                <div class="form-group">
                    <div class="col-sm-12">
                        <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto 2:</strong>
                        <?php
                        if($ext2 == "" || !file_exists("archivos/evi_".$idReporteActual."_2.".$ext2)){
                            echo "<div class='alert alert-danger'>Sin foto cargada</div>";
                        }else{?>
                            <a href="archivos/evi_<?=$idReporteActual; ?>_2.<?=$ext2; ?>" target="_blank"><img src="archivos/evi_<?=$idReporteActual; ?>_2.<?=$ext2; ?>" width="100%" /></a>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="cont-item col-sm-4">
                <div class="form-group">
                    <div class="col-sm-12">
                        <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto 3:</strong>
                        <?php
                        if($ext3 == "" || !file_exists("archivos/evi_".$idReporteActual."_3.".$ext3)){
                            echo "<div class='alert alert-danger'>Sin foto cargada</div>";
                        }else{?>
                            <a href="archivos/evi_<?=$idReporteActual; ?>_3.<?=$ext3; ?>" target="_blank"><img src="archivos/evi_<?=$idReporteActual; ?>_3.<?=$ext3; ?>" width="100%" /></a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <?php } ?>

        <?php
        if($generacionNumero == 0){
            /*?><div class="form-group">
                <label class="control-label col-sm-2" for="capacitacion_txt"><strong>¿Qué capacitación?:</strong></label>
                <div class="col-sm-10"><input name="capacitacion_txt" type="text" id="capacitacion_txt" maxlength="250" value="<?=$capacitacion_txt; ?>" class="form-control" required />
                </div>
            </div><?*/
        }
        ?>        
        <?php if($esActividadResumen){ ?>
        <div class="detalle-card">
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">INFORMACION GENERAL</h3>
                    <h5><?php echo $esActividadEvangelismo ? "RESUMEN DEL REPORTE DE EVANGELISMO" : "RESUMEN DEL REPORTE DE BAUTIZO"; ?></h5>
                    <p><strong>ID del registro:</strong> <?=str_pad($idReporteActual, 6, "0", STR_PAD_LEFT); ?></p>
                </div>
                <div class="hr"><hr></div>
            </div>
            <div class="form-group">
                <?php if($esActividadEvangelismo){ ?>
                <div class="col-sm-3">
                    <strong>Plantador:</strong>
                    <input name="plantador" type="text" id="plantador" maxlength="250" value="<?=reportar_escape_attr($plantador); ?>" class="form-control" required />
                </div>
                <?php } ?>
                <div class="col-sm-2">
                    <strong>Fecha del reporte:</strong>
                    <input name="fechaReporte" type="date" id="fechaReporte" maxlength="250" value="<?=$fechaReporte; ?>" class="form-control" required readonly />
                </div>
                <div class="col-sm-2">
                    <strong>Fecha de inicio:</strong>
                    <input name="fechaInicio" type="date" id="fechaInicio" maxlength="250" value="<?=$fechaInicio; ?>" max='<?=date("Y-m-d"); ?>' class="form-control" required />
                </div>
                <div class="col-sm-<?php echo $esActividadEvangelismo ? "5" : "8"; ?>">
                    <strong>Nombre del grupo al que pertenece:</strong>
                    <input type="text" value="<?=$nombreGrupoPertenece; ?>" class="form-control" readonly />
                    <input name="nombreGrupo_txt" type="hidden" id="nombreGrupo_txt" value="<?=$nombreGrupo_txt; ?>" />
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-2">
                    <strong>Barrio:</strong>
                    <input name="barrio" type="text" id="barrio" maxlength="250" value="<?=$barrio; ?>" class="form-control" required placeholder="Barrio" />
                </div>
                <div class="col-sm-3">
                    <strong><?php echo $esActividadEvangelismo ? "Metodo de evangelismo" : "Direccion"; ?>:</strong>
                    <input name="direccion" type="text" id="direccion" maxlength="250" value="<?=$direccion; ?>" class="form-control" required />
                </div>
                <div class="col-sm-2">
                    <strong>Ciudad:</strong>
                    <input name="ciudad" type="text" id="ciudad" maxlength="250" value="<?=$ciudad; ?>" class="form-control" required />
                </div>
                <div class="col-sm-5">
                    <strong>Grupo madre:</strong>
                    <input name="grupoMadre_txt" type="text" id="grupoMadre_txt" value="<?=$grupoMadre_txt; ?>" class="form-control" placeholder="Sin grupo madre" />
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-3">
                    <strong>Generacion:</strong>
                    <input name="temporal_solotxt" type="text" id="temporal_solotxt" value="<?=$generacionPertenece; ?>" readonly class="form-control" />
                    <input name="generacionNumero" type="hidden" id="generacionNumero" value="<?=$generacionNumeroOriginal; ?>" readonly class="form-control" required />
                </div>
            </div>
        </div>
        <?php }else{ ?>
        <div class="form-group">
            <div class="col-sm-3">
                <strong>Plantador/Pastor/Lider:</strong>
                <input name="plantador" type="text" id="plantador" maxlength="250" value="<?=reportar_escape_attr($plantador); ?>" class="form-control" required  />
            </div>
            <div class="col-sm-2">
                <strong>Fecha del reporte:</strong>
                <input name="fechaReporte" type="date" id="fechaReporte" maxlength="250" value="<?=$fechaReporte; ?>" class="form-control" required readonly  />
            </div>
            <!--<label class="control-label col-sm-2" for="sitioReunion"><strong>Sitio de la reunión:</strong></label>
            <div class="col-sm-4"><input name="sitioReunion" type="text" id="sitioReunion" maxlength="250" value="<?=$sitioReunion; ?>" class="form-control" required  />
            </div>//-->

            <div class="col-sm-2">
                <strong>Fecha de inicio:</strong>
                <input name="fechaInicio" type="date" id="fechaInicio" maxlength="250" value="<?=$fechaInicio; ?>" max='<?=date("Y-m-d"); ?>' class="form-control" required  />
            </div>
            <div class="col-sm-2">
                <strong>Barrio (Evento):</strong>
                <input name="barrio" type="text" id="barrio" maxlength="250" value="<?=$barrio; ?>" class="form-control" required  placeholder = "Barrio" />
            </div>
            <div class="col-sm-3">
                <strong><?php if($generacionNumero == 77){ echo "Método de evangelismo"; }else { ?>Dirección (Evento)<?php } ?>:</strong>
                <input name="direccion" type="text" id="direccion" maxlength="250" value="<?=$direccion; ?>" class="form-control" required />
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-2">
                <strong>Ciudad (Evento):</strong>
                <input name="ciudad" type="text" id="ciudad" maxlength="250" value="<?=$ciudad; ?>" class="form-control" required />
            </div>        
            <div class="col-sm-5">
                <strong>Grupo madre / Denominación / Organización / Red de Iglesias de Pequeño Grupo:</strong>
                <input name="grupoMadre_txt" type="text" id="grupoMadre_txt" value="<?=$grupoMadre_txt; ?>" class="form-control" />
            </div>
            
            <?php
            if($generacionNumero == 0){
                ?><div class="col-sm-4">
                    <strong>Generación:</strong>
                    <input name="temporal_solotxt" type="text" id="temporal_solotxt" value="0" readonly class="form-control"  /><input name="generacionNumero" type="hidden" id="generacionNumero" value="<?=$generacionNumero; ?>" readonly class="form-control" required /></div><?php
            }else if ($generacionNumero == 77){?>
                <div class="col-sm-4">
                    <strong>Generación:</strong>
                    <input name="temporal_solotxt" type="text" id="temporal_solotxt" value="<?=$generacionPertenece; ?>" readonly class="form-control"  />
                    <input name="generacionNumero" type="hidden" id="generacionNumero" value="<?=$generacionNumero; ?>" readonly class="form-control" required />
                </div>
            <?php  }else if ($generacionNumero == 8){?>
                <div class="col-sm-4">
                    <strong>Generación:</strong>
                    <input name="temporal_solotxt" type="text" id="temporal_solotxt" value="GRAN CELEBRACIÓN" readonly class="form-control"  />
                    <input name="generacionNumero" type="hidden" id="generacionNumero" value="<?=$generacionNumero; ?>" readonly class="form-control" required />
                </div>
            <?php  }else if ($esActividadCoach){?>
                <div class="col-sm-4">
                    <strong>GeneraciÃ³n:</strong>
                    <input name="temporal_solotxt" type="text" id="temporal_solotxt" value="1" readonly class="form-control"  />
                    <input name="generacionNumero" type="hidden" id="generacionNumero" value="1" readonly class="form-control" required />
                </div>
            <?php  } ?>            
        </div>
        <?php if($esActividadCoach){ ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var plantadorInput = document.getElementById('plantador');
                if (plantadorInput && plantadorInput.previousElementSibling) {
                    plantadorInput.previousElementSibling.textContent = 'Plantador:';
                }

                var grupoMadreInput = document.getElementById('grupoMadre_txt');
                if (grupoMadreInput && grupoMadreInput.previousElementSibling) {
                    grupoMadreInput.previousElementSibling.textContent = 'Grupo madre:';
                }

                var generacionInput = document.getElementById('temporal_solotxt');
                if (generacionInput && generacionInput.previousElementSibling) {
                    generacionInput.previousElementSibling.textContent = 'Generación:';
                }

                if (generacionInput && generacionInput.previousElementSibling) {
                    generacionInput.previousElementSibling.textContent = 'Generación:';
                }

                if (generacionInput && generacionInput.previousElementSibling) {
                    generacionInput.previousElementSibling.textContent = 'Generaci\u00F3n:';
                }

                var disciplinaInput = document.getElementById('final_discipulado');
                if (disciplinaInput) {
                    var resumenRow = disciplinaInput.closest('.form-group');
                    if (resumenRow) {
                        resumenRow.style.textAlign = 'center';
                    }
                }

                ['final_discipulado', 'final_desiciones', 'final_preparandose'].forEach(function (fieldId) {
                    var input = document.getElementById(fieldId);
                    if (!input) {
                        return;
                    }

                    var column = input.parentElement;
                    if (!column) {
                        return;
                    }

                    column.style.cssFloat = 'none';
                    column.style.display = 'inline-block';
                    column.style.verticalAlign = 'top';
                    column.style.textAlign = 'left';
                    column.style.width = '30%';
                });
            });
        </script>
        <?php } ?>
        <?php } ?>
        <?php  if($generacionNumero != 0 && $generacionNumero != 77 && $generacionNumero != 8 && !$esActividadCoach && !$esActividadBautizo){?>            
            <div class="col-sm-12" style="clear: both; margin-top: 15px;">
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">GENERACIÓN</h3>
                    <h5>seleccione una opción</h5>
                </div>
                <div class="hr"><hr></div>
            </div>         
            <div class="form-group">
                <label class="control-label col-sm-1" for="inlineRadio1">1</label>
                <div class="col-sm-1"><input type="radio" name="generacionNumero" id="inlineRadio1" value="1" <?php if($generacionNumero == 1){ ?>checked<?php } ?> required class="form-control" />
                </div>
            
                <label class="control-label col-sm-1" for="inlineRadio2">2</label>
                <div class="col-sm-1"><input type="radio" name="generacionNumero" id="inlineRadio1" value="2" <?php if($generacionNumero == 2){ ?>checked<?php } ?> required class="form-control" />
                </div>

                <label class="control-label col-sm-1" for="inlineRadio3">3</label>
                <div class="col-sm-1"><input type="radio" name="generacionNumero" id="inlineRadio1" value="3" <?php if($generacionNumero == 3){ ?>checked<?php } ?> required class="form-control" />
                </div>

                <label class="control-label col-sm-1" for="inlineRadio4">4</label>
                <div class="col-sm-1"><input type="radio" name="generacionNumero" id="inlineRadio1" value="4" <?php if($generacionNumero == 4){ ?>checked<?php } ?> required class="form-control" />
                </div>

                <label class="control-label col-sm-1" for="inlineRadio5">5</label>
                <div class="col-sm-1"><input type="radio" name="generacionNumero" id="inlineRadio1" value="5" <?php if($generacionNumero == 5){ ?>checked<?php } ?> required class="form-control" />
                </div>
            </div>
        <?php
        }
        ?>
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3><?php if($esActividadEvangelismo){ echo "ALCANZADOS"; }else{ echo "ASISTENCIA"; } ?></h3>
                <?php if($esActividadEvangelismo){ ?><h5>DISTRIBUCION DE LA ASISTENCIA REPORTADA</h5><?php } ?>
            </div>
            <div class="hr"><hr></div>
        </div>
        <div class="form-group">
            <div class="col-sm-3"></div>
            <?php if($generacionNumero == 0 || $esActividadEvangelismo || $esActividadBautizo){?>
                <div class="col-sm-1">
                    <strong>Hombres:</strong>
                    <input name="final_asistencia_hom" type="number" id="final_asistencia_hom" value="<?=$asistencia_hom; ?>" class="form-control" onChange="sumar()"  />
                </div>
                <div class="col-sm-1">
                    <strong>Mujeres:</strong>
                    <input name="final_asistencia_muj" type="number" id="final_asistencia_muj" value="<?=$asistencia_muj; ?>" class="form-control"  onChange="sumar()" />
                </div>
                <div class="col-sm-1">
                    <strong>Jóvenes:</strong>
                    <input name="final_asistencia_jov" type="number" id="final_asistencia_jov" value="<?=$asistencia_jov; ?>" class="form-control" onChange="sumar()"  />
                </div>
                <div class="col-sm-1">
                    <strong>Niños:</strong>
                    <input name="final_asistencia_nin" type="number" id="final_asistencia_nin" value="<?=$asistencia_nin; ?>" class="form-control" onChange="sumar()"  />
                </div>
            <?php }else{?>
                <div class="col-sm-1">
                    <strong>Hombres:</strong>
                    <input name="final_asistencia_hom" type="number" id="final_asistencia_hom" value="<?=$asistencia_hom; ?>" class="form-control" onChange="sumar()"  />
                </div>
                <div class="col-sm-1">
                    <strong>Mujeres:</strong>
                    <input name="final_asistencia_muj" type="number" id="final_asistencia_muj" value="<?=$asistencia_muj; ?>" class="form-control" onChange="sumar()"  />
                </div>
                <div class="col-sm-1">
                    <strong>Jóvenes:</strong>
                    <input name="final_asistencia_jov" type="number" id="final_asistencia_jov" value="<?=$asistencia_jov; ?>" class="form-control" onChange="sumar()"  />
                </div>
                <div class="col-sm-1">
                    <strong>Niños:</strong>
                    <input name="final_asistencia_nin" type="number" id="final_asistencia_nin" value="<?=$asistencia_nin; ?>" class="form-control" onChange="sumar()"  />
                </div>
            
            <?php } ?>
            <div class="col-sm-2">
                <strong><?php if($esActividadEvangelismo){ echo "Alcanzados"; }else{ echo "Asistencia"; } ?> total:</strong>
                <input name="final_asistencia_total" type="number" id="final_asistencia_total" value="<?=$asistencia_total; ?>" readonly class="form-control"  />
            </div>
            <div class="col-sm-3"></div>
        </div>
            
        <?php if($usaFormatoSimple){?>
            <?php if($esActividadBautizo){ ?>
                <div class="form-group">
                    <div class="col-sm-5"></div>
                    <div class="col-sm-2">
                        <strong>Bautizados:</strong>
                        <input name="final_bautizados" type="number" id="final_bautizados" value="<?=$bautizados; ?>" class="form-control" onChange="sumar()" />
                    </div>
                    <div class="col-sm-5"></div>
                </div>
                <input name="final_comentarios" type="hidden" id="final_comentarios" value="<?=reportar_escape_attr($comentario); ?>" />
            <?php }else{ ?>
            <input name="final_bautizados" type="hidden" id="final_bautizados"  value="0" class="form-control" readonly />
            <?php } ?>
            <input name="final_discipulado" type="hidden" id="final_discipulado" value="0" class="form-control" readonly />
            <?php if($esActividadEvangelismo){ ?>
                <div class="form-group">
                    <div class="col-sm-5"></div>
                    <div class="col-sm-2">
                        <strong>Decisiones de Fé:</strong>
                        <input name="final_desiciones" type="number" id="final_desiciones" value="<?=$desiciones; ?>" class="form-control" onChange="sumar()" />
                    </div>
                    <div class="col-sm-5"></div>
                </div>
            <?php }else{ ?>
                <input name="final_desiciones" type="hidden" id="final_desiciones" value="0" class="form-control" readonly />
            <?php } ?>
            <input name="final_preparandose" type="hidden" id="final_preparandose" value="0" class="form-control" readonly />
            <input name="final_bautizadosPeriodo" type="hidden" id="final_bautizadosPeriodo" value="<?php if($esActividadBautizo){ echo $bautizadosPeriodo; }else{ ?>0<?php } ?>" class="form-control" readonly />
            <?php if($esActividadEvangelismo || $esActividadGranCelebracion){ ?>
            <div class="col-sm-12">
                <div class="cont-tit">
                    <div class="hr"><hr></div>
                    <div class="tit-cen">
                        <h3 class="text-center"><?php if($esActividadEvangelismo){ echo "COMENTARIOS DEL REPORTE"; }else{ echo "OTROS DATOS DEL PROCESO"; } ?></h3>
                        <h5><?php if($esActividadEvangelismo){ echo "OBSERVACIONES GENERALES"; }else{ echo "COMENTARIOS"; } ?></h5>
                    </div>
                    <div class="hr"><hr></div>
                </div>
                <div class="form-group">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6">
                        <textarea name="final_comentarios" id="final_comentarios" style="width: 100%;"><?php echo $comentario; ?></textarea>
                    </div>
                    <div class="col-sm-3"></div>
                </div>
            </div>
            <?php } ?>

            <div class="col-sm-12" style="clear: both; margin-top: 15px;">
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center"><?php if($esActividadEvangelismo || $esActividadBautizo){ echo "EVIDENCIA FOTOGRAFICA"; }else{ echo "Método DE VERIFICACIÓN"; } ?></h3>
                    <h5><?php if($esActividadEvangelismo || $esActividadBautizo){ echo "FOTOS CARGADAS EN EL FORMULARIO"; }else{ echo "Fotografias"; } ?></h5>
                </div>
                <div class="hr"><hr></div>
            </div> 
            <div class="cont-flex fl-sard">
                <?php if($esActividadEvangelismo && count($adjuntosReporte) > 0){ ?>
                    <?php foreach($adjuntosReporte as $indiceAdj => $adjuntoActual){ ?>
                    <div class="cont-item col-sm-4">
                        <div class="form-group">
                            <div class="col-sm-12">
                                <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto <?=($indiceAdj + 1); ?>:</strong>
                                <a href="<?=$adjuntoActual["url"]; ?>" target="_blank"><img src="<?=$adjuntoActual["url"]; ?>" width="100%" /></a>
                                <?php if(trim($adjuntoActual["nombre"]) != ""){ ?>
                                    <small style="display:block; margin-top:8px; color:#666;"><?=$adjuntoActual["nombre"]; ?></small>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                <?php }else if($esActividadBautizo){ ?>
                <?php for($indiceBautizoFoto = 0; $indiceBautizoFoto < 3; $indiceBautizoFoto++){ ?>
                <?php $fotoBautizoActual = isset($bautizoFotosReporte[$indiceBautizoFoto]) ? $bautizoFotosReporte[$indiceBautizoFoto] : null; ?>
                <div class="cont-item col-sm-4">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto <?=($indiceBautizoFoto + 1); ?>:</strong>
                            <?php if(!$fotoBautizoActual || trim((string)$fotoBautizoActual["url"]) === ""){ ?>
                                <div class='alert alert-danger'>Sin foto cargada</div>
                            <?php }else{ ?>
                                <a href="<?=$fotoBautizoActual["url"]; ?>" target="_blank" style="display:flex; align-items:center; justify-content:center; width:100%; min-height:260px; border-radius:8px; background:#f5f7fa; border:1px solid #dfe6ee; padding:10px;">
                                    <img src="<?=$fotoBautizoActual["url"]; ?>" style="display:block; max-width:100%; max-height:240px; width:auto; height:auto; object-fit:contain;" />
                                </a>
                                <?php if(trim((string)$fotoBautizoActual["nombre"]) !== ""){ ?><small style="display:block; margin-top:8px; color:#666;"><?=$fotoBautizoActual["nombre"]; ?></small><?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong>Cargar foto <?=($indiceBautizoFoto + 1); ?>:</strong>
                            <input name="archivo<?=($indiceBautizoFoto + 1); ?>" type="file" id="archivo<?=($indiceBautizoFoto + 1); ?>" class="form-control" />
                        </div>
                    </div>
                </div>
                <?php } ?>
                <?php }else if($esActividadGranCelebracion){ ?>
                <?php for($indiceGranCelebracionFoto = 0; $indiceGranCelebracionFoto < 3; $indiceGranCelebracionFoto++){ ?>
                <?php $fotoGranCelebracionActual = isset($granCelebracionFotosReporte[$indiceGranCelebracionFoto]) ? $granCelebracionFotosReporte[$indiceGranCelebracionFoto] : null; ?>
                <div class="cont-item col-sm-4">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto <?=($indiceGranCelebracionFoto + 1); ?>:</strong>
                            <?php if(!$fotoGranCelebracionActual || trim((string)$fotoGranCelebracionActual["url"]) === ""){ ?>
                                <div class='alert alert-danger'>Sin foto cargada</div>
                            <?php }else{ ?>
                                <a href="<?=$fotoGranCelebracionActual["url"]; ?>" target="_blank" style="display:flex; align-items:center; justify-content:center; width:100%; min-height:260px; border-radius:8px; background:#f5f7fa; border:1px solid #dfe6ee; padding:10px;">
                                    <img src="<?=$fotoGranCelebracionActual["url"]; ?>" style="display:block; max-width:100%; max-height:240px; width:auto; height:auto; object-fit:contain;" />
                                </a>
                                <?php if(trim((string)$fotoGranCelebracionActual["nombre"]) !== ""){ ?><small style="display:block; margin-top:8px; color:#666;"><?=$fotoGranCelebracionActual["nombre"]; ?></small><?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong>Cargar foto <?=($indiceGranCelebracionFoto + 1); ?>:</strong>
                            <input name="archivo<?=($indiceGranCelebracionFoto + 1); ?>" type="file" id="archivo<?=($indiceGranCelebracionFoto + 1); ?>" class="form-control" />
                        </div>
                    </div>
                </div>
                <?php } ?>
                <?php }else{ ?>
                <div class="cont-item col-sm-3">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto 1:</strong>
                            <?php
                            if($rutaFoto1 == ""){
                                echo "<div class='alert alert-danger' >Sin foto cargada</div>";
                            }else{?>
                                <a href="<?=$rutaFoto1; ?>" target="_blank"><img src="<?=$rutaFoto1; ?>" width="100%" /></a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong>Cargar foto 1:</strong>
                            <input name="archivo1" type="file" id="archivo1" class="form-control" />
                        </div>
                    </div>
                </div>
                <div class="cont-item col-sm-3">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto 2:</strong>
                            <?php
                            if($rutaFoto2 == ""){
                                echo "<div class='alert alert-danger' >Sin foto cargada</div>";
                            }else{?>
                                <a href="<?=$rutaFoto2; ?>" target="_blank"><img src="<?=$rutaFoto2; ?>" width="100%" /></a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong>Cargar foto 2:</strong>
                            <input name="archivo2" type="file" id="archivo2" class="form-control" />
                        </div>
                    </div>
                </div>
                <div class="cont-item col-sm-3">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto 3:</strong>
                            <?php
                            if($rutaFoto3 == ""){
                                echo "<div class='alert alert-danger' >Sin foto cargada</div>";
                            }else{?>
                                <a href="<?=$rutaFoto3; ?>" target="_blank"><img src="<?=$rutaFoto3; ?>" width="100%" /></a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong>Cargar foto 3:</strong>
                            <input name="archivo3" type="file" id="archivo3" class="form-control" />
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
            </div>
                
        <?php }else{ ?>
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">OTROS DATOS</h3>
                    <h5>DEL PROCESO</h5>
                </div>
                <div class="hr"><hr></div>
            </div> 
            <div class="form-group">
                <div class="col-sm-1"></div>
                <?php if($esActividadCoach){ ?>
                    <input name="final_bautizados" type="hidden" id="final_bautizados" value="<?=$bautizados; ?>" />
                <?php }else{ ?>
                <div class="col-sm-2">
                    <strong>Miembros Bautizados:</strong>
                    <input name="final_bautizados" type="number" id="final_bautizados" readonly value="<?=$bautizados; ?>" class="form-control"  />
                </div>
                <?php } ?>
                <div class="<?php if($esActividadCoach){ ?>col-sm-3<?php }else{ ?>col-sm-2<?php } ?>">
                    <strong>En discipulado:</strong>
                    <input name="final_discipulado" type="number" id="final_discipulado" readonly value="<?=$discipulado; ?>" class="form-control"  />
                </div>
                <div class="col-sm-2">
                    <strong>Decisiones de Fé:</strong>
                    <input name="final_desiciones" type="number" id="final_desiciones" value="<?=$desiciones; ?>" class="form-control"  />
                </div>
                <div class="col-sm-2">
                    <strong>Preparándose para bautismo:</strong>
                <input name="final_preparandose" type="number" id="final_preparandose" readonly value="<?=$preparandose; ?>" class="form-control"  /></div>
                <?php if($esActividadCoach){ ?>
                    <input name="final_bautizadosPeriodo" type="hidden" id="final_bautizadosPeriodo" value="<?=$bautizadosPeriodo; ?>" />
                <?php }else{ ?>
                <div class="col-sm-2">
                    <strong>Bautizados este periodo:</strong>
                    <input readonly name="final_bautizadosPeriodo" type="number" id="final_bautizadosPeriodo" value="<?=$bautizadosPeriodo;  ?>" class="form-control"  onChange="sumar()" />
                </div>
                <?php } ?>
                <div class="col-sm-1"></div>
            </div>        

            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">MAPEO DE LA IGLESIA (no se incluya usted en eL reporte)</h3>
                    <h5></h5>
                </div>
                <div class="hr"><hr></div>
            </div> 

            <div class="form-group">
                <div class="col-sm-4">
                    <strong>Fecha de mapeo:</strong>
                    <input required name="mapeo_fecha" type="date" id="mapeo_fecha" value="<?=$mapeo_fecha; ?>" max='<?=date("Y-m-d"); ?>' class="form-control" />
                </div>
                <div class="col-sm-4">
                    <strong>¿Este grupo está comprometido como iglesia?:</strong>
                    <select required name="mapeo_comprometido" id="mapeo_comprometido" class="form-control">
                        <option value="">Sin seleccionar</option>
                        <option value="3" <?php if($mapeo_comprometido == 3){ ?>selected="selected"<?php } ?>>NO comprometido</option>
                        <option value="4" <?php if($mapeo_comprometido == 4){ ?>selected="selected"<?php } ?>>SI comprometido como iglesia</option>
                    </select>
                </div>
                <div class="col-sm-4">
                    <strong>Nombre grupo/iglesia:</strong>
                    <input required name="nombreGrupo_txt" type="text" id="nombreGrupo_txt" value="<?=$nombreGrupo_txt; ?>" class="form-control" />
                </div>
            </div>
            <div style="display: flex;flex-wrap: wrap; justify-content: space-between;">
            <?php
            $array_campos = array(
                "mapeo_oracion",
                "mapeo_companerismo",
                "mapeo_adoracion",
                "mapeo_biblia",
                "mapeo_evangelizar",
                "mapeo_cena",
                "mapeo_dar",
                "mapeo_bautizar",
                "mapeo_trabajadores"
            );
            $array_campos_valor = array(
                $mapeo_oracion,
                $mapeo_companerismo,
                $mapeo_adoracion,
                $mapeo_biblia,
                $mapeo_evangelizar,
                $mapeo_cena,
                $mapeo_dar,
                $mapeo_bautizar,
                $mapeo_trabajadores
            );            
            $array_campos_txt = array(
                "Orar",
                "Compañerismo",
                "Adorar",
                "Aplicar la biblia",
                "Evangelizar",
                "Cena del Señor",
                "Dar",
                "Bautizar",
                "Entrenar nuevos lideres"
            );
            $total_campos = count($array_campos);
            for($i=0; $i<$total_campos;$i++){
                $total_valor += $array_campos_valor[$i];
                ?>
                <div class="row col-sm-6">
                    <h4 class="alert alert-warning"><?=$array_campos_txt[$i]; ?></h4>
                    <div class="form-group cont-mapeo">
                        <div class="map-chec"><input type="radio" name="<?=$array_campos[$i]; ?>" required <?php
                        if($array_campos_valor[$i] == 1){
                            ?>checked="checked"<?php
                        }
                        ?> value="1" class="form-control" /></div>
                        <div class="map-text" style="display: flex;align-items: center;"><img src="mapeo_img/<?=$array_campos[$i]; ?>1.png" class="img-responsive" /> NO REALIZA LA TAREA</div>
                    </div>
                    <div class="form-group cont-mapeo">
                        <div class="map-chec"><input type="radio" name="<?=$array_campos[$i]; ?>" <?php
                        if($array_campos_valor[$i] == 2){
                            ?>checked="checked"<?php
                        }
                        ?> value="2" class="form-control" /></div>
                        <div class="map-text"><img width="40" src="mapeo_img/<?=$array_campos[$i]; ?>2.png" class="img-responsive" /> REALIZA LA TAREA EN COMPAÑIA DEL FACILITADOR</div>
                    </div>
                    <div class="form-group cont-mapeo">
                        <div class="map-chec"><input type="radio" name="<?=$array_campos[$i]; ?>" <?php
                        if($array_campos_valor[$i] == 3){
                            ?>checked="checked"<?php
                        }
                        ?> value="3" class="form-control"  /></div>
                        <div class="map-text"><img src="mapeo_img/<?=$array_campos[$i]; ?>3.png" class="img-responsive" /> REALIZA LA TAREA PERO ESTE MES NO LO HIZO</div>
                    </div>
                    <div class="form-group cont-mapeo">
                        <div class="map-chec"><input type="radio" name="<?=$array_campos[$i]; ?>" <?php
                        if($array_campos_valor[$i] == 4){
                            ?>checked="checked"<?php
                        }
                        ?> value="4" class="form-control"  /></div>
                        <div class="map-text"><img width="40" src="mapeo_img/<?=$array_campos[$i]; ?>4.png" class="img-responsive" /> REALIZA LA TAREA AUTONOMAMENTE</div>
                    </div>
                </div>
            <?php } ?>
            
                <div class="row col-sm-6">
                    <h5 class="alert alert-info text-center">IMAGEN DEL MAPEO</h5>
                    <div class="form-group">
                        <div class="col-sm-3">&nbsp;</div>
                        <div class="col-sm-6"><img src="mapeo_img.php?id=<?=$idReporteActual; ?>&time=<?=strtotime("now"); ?>" class="img-responsive" /></div>
                        <div class="col-sm-3">&nbsp;</div>
                    </div>
                </div>     
            </div>

            <?php if($esActividadCoach){ ?>
                <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">EVIDENCIA FOTOGRAFICA</h3>
                    <h5>MAXIMO 3 FOTOS</h5>
                    <p>Valide o actualice las fotografias del reporte</p>
                </div>
                <div class="hr"><hr></div>
            </div>
            <div class="cont-flex fl-sard">
                <div class="cont-item col-sm-4">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto 1:</strong>
                            <?php if(!isset($coachFotosReporte[0]["url"]) || trim((string)$coachFotosReporte[0]["url"]) === ""){ ?>
                                <div class='alert alert-danger'>Sin foto cargada</div>
                            <?php }else{ ?>
                                <a href="<?=$coachFotosReporte[0]["url"]; ?>" target="_blank"><img src="<?=$coachFotosReporte[0]["url"]; ?>" style="width: 100%; height: 220px; object-fit: contain; border-radius: 8px; background: #f5f7fa; border: 1px solid #dfe6ee; padding: 6px;" /></a>
                                <?php if(trim((string)$coachFotosReporte[0]["nombre"]) !== ""){ ?><small style="display:block; margin-top:8px; color:#666;"><?=$coachFotosReporte[0]["nombre"]; ?></small><?php } ?>
                            <?php } ?>
                            <br>
                            <strong>Cargar foto 1:</strong>
                            <input name="archivo1" type="file" id="archivo1" class="form-control" />
                        </div>
                    </div>
                </div>
                <div class="cont-item col-sm-4">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto 2:</strong>
                            <?php if(!isset($coachFotosReporte[1]["url"]) || trim((string)$coachFotosReporte[1]["url"]) === ""){ ?>
                                <div class='alert alert-danger'>Sin foto cargada</div>
                            <?php }else{ ?>
                                <a href="<?=$coachFotosReporte[1]["url"]; ?>" target="_blank"><img src="<?=$coachFotosReporte[1]["url"]; ?>" style="width: 100%; height: 220px; object-fit: contain; border-radius: 8px; background: #f5f7fa; border: 1px solid #dfe6ee; padding: 6px;" /></a>
                                <?php if(trim((string)$coachFotosReporte[1]["nombre"]) !== ""){ ?><small style="display:block; margin-top:8px; color:#666;"><?=$coachFotosReporte[1]["nombre"]; ?></small><?php } ?>
                            <?php } ?>
                            <br>
                            <strong>Cargar foto 2:</strong>
                            <input name="archivo2" type="file" id="archivo2" class="form-control" />
                        </div>
                    </div>
                </div>
                <div class="cont-item col-sm-4">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto 3:</strong>
                            <?php if(!isset($coachFotosReporte[2]["url"]) || trim((string)$coachFotosReporte[2]["url"]) === ""){ ?>
                                <div class='alert alert-danger'>Sin foto cargada</div>
                            <?php }else{ ?>
                                <a href="<?=$coachFotosReporte[2]["url"]; ?>" target="_blank"><img src="<?=$coachFotosReporte[2]["url"]; ?>" style="width: 100%; height: 220px; object-fit: contain; border-radius: 8px; background: #f5f7fa; border: 1px solid #dfe6ee; padding: 6px;" /></a>
                                <?php if(trim((string)$coachFotosReporte[2]["nombre"]) !== ""){ ?><small style="display:block; margin-top:8px; color:#666;"><?=$coachFotosReporte[2]["nombre"]; ?></small><?php } ?>
                            <?php } ?>
                            <br>
                            <strong>Cargar foto 3:</strong>
                            <input name="archivo3" type="file" id="archivo3" class="form-control" />
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="inactivo" id="inactivo" value="<?=$inactivo; ?>" />
            <?php }else if ($generacionNumero != 0 && $generacionNumero != 77 && $generacionNumero != 8 ) {?>
                <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">Método DE VERIFICACIÓN</h3>
                    <h5>BAUTIZOS</h5>
                    
                </div>
                <div class="hr"><hr></div>
            </div>
            <?php if ($_POST["funcion"]=="actualizar") {
                        echo "<div class='alert alert-danger text-center' >Si alguna actulización no se ve reflajada al instante, puede ser cache!</div>";
                    } ?>
            <div class="cont-flex fl-sard">
                <script>
                       $(function(){
                        $("#adicionarAdd").on('click',function(){
                            $("#tablaAdd tbody tr:eq(0)").clone().removeClass('fila-fijaAdd').appendTo("#tablaAdd");
                            var total = 0;
                            $('.subtotal').each(function(){
                                 total = total + Number($(this).val());
                            });
                            $('#final_bautizadosPeriodo').val(total);
                            $('#final_bautizados').val(total);
                        });
                        $(document).on("click",".eliminarAdd",function(){
                            var parent = $(this).parents().get(0);
                            $(parent).remove();
                        });

                        $(document).on("click","#act_bau_can",function(){
                            var total = 0;
                            $('.subtotal').each(function(){
                                 total = total + Number($(this).val());
                            });
                            $('#final_bautizadosPeriodo').val(total);
                            $('#final_bautizados').val(total);
                        });
                        $(document).on("click","#guarda_rep",function(){
                            var total = 0;
                            $('.subtotal').each(function(){
                                 total = total + Number($(this).val());
                            });
                            $('#final_bautizadosPeriodo').val(total);
                            $('#final_bautizados').val(total);
                        });
                        
                    });
                    </script>
                <?php 
                $sql = "SELECT * ";
                $sql.=" FROM tbl_adjuntos ";
                $sql.=" WHERE adj_rep_fk = '".$idReporteActual."'";
                $PSN1->query($sql);
                if($PSN1->num_rows() > 0){
                    while($PSN1->next_record()){ ?>
                        <div class="cont-item col-sm-3">
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <input type="hidden" name="act_bau_id[]" value="<?= $PSN1->f("adj_id");  ?>" placeholder="">
                                    <strong style="font-size: 18px;display: block;margin-top: 10px;">Foto:</strong>
                                    <?php
                                    if(empty($PSN1->f("adj_url"))){
                                        echo "<div class='alert alert-danger' >Sin foto cargada</div>";
                                    }else{?>
                                        <a href="<?=$PSN1->f("adj_url"); ?>" target="_blank"><img src="<?=$PSN1->f("adj_url"); ?>" width="100%" /></a>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <strong>Cargar foto:</strong>
                                    <input multiple name="act_bau_img[]" type="file" id="act_bau_img[]" class="form-control" value="<?=$PSN1->f("adj_url"); ?>" />
                                    <input type="hidden" name="act_bau_img_an[]" value="<?=$PSN1->f("adj_url"); ?>" placeholder="">
                                </div>
                                <div class="col-sm-7">
                                    <strong>Fecha:</strong>
                                    <input name="act_bau_fec[]" type="date" id="act_bau_fec" class="form-control" value="<?=$PSN1->f("adj_fec"); ?>" />
                                </div>
                                <div class="col-sm-5">
                                    <strong>Cantidad:</strong>
                                    <input name="act_bau_can[]" type="number" id="act_bau_can" min="0" class="subtotal form-control" value="<?php echo $PSN1->f("adj_can"); ?>" />
                                </div>
                            </div>
                        </div>
                        
                    <?php }
                }
                if ($PSN1->num_rows()<3) {?>
                    

                    <div class="form-group col-sm-12"><br>
                    <table id="tablaAdd">
                        <tr class="fila-fijaAdd">
                            <td class="col-sm-4">
                                <strong>Foto:</strong>
                                <input multiple name="act_bau_img[]" type="file" id="act_bau_img" class="form-control" />
                            </td>
                            <td class="col-sm-3">
                                <strong>Fecha:</strong>
                                <input name="act_bau_fec[]" type="date" id="act_bau_fec" class="form-control" />
                            </td>
                            <td class="col-sm-2">
                                <strong>Cantidad bautizados:</strong>
                                <input name="act_bau_can[]" type="number" id="act_bau_can" min="0" class="subtotal form-control" />
                            </td>
                            <td class="eliminarAdd"><button type="button" class="btn btn-cir-uno usua-col"><i class="fa fa-times"></i></button></td>
                        </tr>
                    </table>
                </div>
                <div class="col-sm-12">
                    <div class="col-sm-5">
                        <input type="hidden" name="total" id="total">
                    </div>
                    <div class="col-sm-2">
                        <center>
                            <button id="adicionarAdd" class="btn btn-success" type="button" class="boton"><i class="fas fa-plus"></i>  Adicionar</button>
                        </center>
                    </div>
                    <div class="col-sm-5"></div>
                </div>
             <?php } ?>
            </div>

            <?php } ?>
            <?php if(!$esActividadCoach){ ?>
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">Método DE VERIFICACIÓN</h3>
                    <h5>Foto del grupo</h5>
                    <p>Valide la información correspondiente</p>
                </div>
                <div class="hr"><hr></div>
            </div>
            <div class="form-group">
                <div class="col-sm-4"></div>
                <div class="col-sm-4">
                    <strong>Foto:</strong>
                    <?php
                    if($rutaFoto1 == ""){
                        ?><div class='alert alert-danger' style="margin-bottom: 0px !important;">Sin foto cargada</div><?php
                    }else{?>
                        <a href="<?=$rutaFoto1; ?>" target="_blank"><img src="<?=$rutaFoto1; ?>" width="100%" /></a>
                    <?php }?><br>
                    <strong>Cargar foto:</strong>
                    <input name="archivo1" type="file" id="archivo1" class="form-control" />
                </div>
                <div class="col-sm-4"></div>
            </div>

            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">ESTADO DEL REPORTE</h3>
                    <h5>ACTIVO/INACTIVO</h5>
                </div>
                <div class="hr"><hr></div>
            </div>
            <div class="form-group">
                <div class="col-sm-4"></div>
                <div class="col-sm-4">
                    <strong>Seleccione si el grupo dejó de reunirse y/o no continua en el proceso:</strong>
                    <input type="checkbox" name="inactivo" id="inactivo" value="1" <?php if($inactivo == 1){ ?>checked="checked"<?php } ?> class="form-control" />
                </div>
                <div class="col-sm-4"></div>
            </div>
            <?php } ?>
        <?php } ?>
        <?php if (($generacionNumero == 8 || $generacionNumero == 77) && !$esActividadEvangelismo && !$esActividadGranCelebracion && !$esActividadBautizo) {?>
            <div class="col-sm-12">
                <div class="cont-tit">
                    <div class="hr"><hr></div>
                    <div class="tit-cen">
                        <h3 class="text-center"><?php if($generacionNumero == 77){ echo "COMENTARIOS DEL REPORTE"; }else{ echo "OTROS DATOS DEL PROCESO"; } ?></h3>
                        <h5><?php if($generacionNumero == 77){ echo "OBSERVACIONES GENERALES"; }else{ echo "COMENTARIOS"; } ?></h5>
                    </div>
                    <div class="hr"><hr></div>
                </div>
                <div class="form-group">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6">
                        <textarea name="final_comentarios" id="final_comentarios" style="width: 100%;"><?php echo $comentario; ?></textarea>
                    </div>
                    <div class="col-sm-3"></div>
                </div>
            </div>
        <?php } ?>
        <?php if($esActividadResumen){ ?>
        </div>
        <?php } ?>
        <?php if ($soloLecturaReporteFacilitador) { ?>
        </fieldset>
        <?php } ?>
        <div class="cont-btn cont-flex fl-sbet">
                <div class="item-btn">
                    <input type="button" onClick="window.location.href='index.php?doc=reportar_buscar'" name="previous" class="previous btn btn-info" value="Cerrar" />
                </div>
                <div class="item-btn">
                    <input type="submit" name="button" value="Guardar cambios" class="btn btn-success" id="guarda_rep" <?php if ($soloLecturaReporteFacilitador) { ?>disabled="disabled" title="Los facilitadores no pueden modificar reportes existentes."<?php } ?>>
                </div>
                <div class="item-btn">
                    <input type="button" onClick="eliminarRegistro()" name="button" value="Eliminar" class="btn btn-danger" <?php if ($soloLecturaReporteFacilitador) { ?>disabled="disabled" title="Los facilitadores no pueden modificar reportes existentes."<?php } ?>>
                </div>
            </div>            
    <input type="hidden" name="funcion" id="funcion" value="" />
    <input type="hidden" name="generacion" id="generacion" value="<?=$generacionActual; ?>" />
    </form>

        <script language="javascript">
        //
            function sumar(){
                var asistencia_hom = 0;
                var asistencia_muj = 0;
                var asistencia_jov = 0;
                var asistencia_nin = 0;
                
                if(document.getElementById("final_asistencia_hom").value != ""){
                    var asistencia_hom = document.getElementById("final_asistencia_hom").value;
                }
                if(document.getElementById("final_asistencia_muj").value != ""){
                    var asistencia_muj = document.getElementById("final_asistencia_muj").value;
                }
                if(document.getElementById("final_asistencia_jov").value != ""){
                    var asistencia_jov = document.getElementById("final_asistencia_jov").value;
                }
                if(document.getElementById("final_asistencia_nin").value != ""){
                    var asistencia_nin = document.getElementById("final_asistencia_nin").value;
                }
                
                <?php
                if($esActividadEvangelismo){
                    ?>               
                    var bautizados = 0;
                    var bautizadosPeriodo = 0;
                    var desiciones = 0;
                    if(document.getElementById("final_desiciones").value != ""){
                        var desiciones = document.getElementById("final_desiciones").value;
                    }
                    <?php
                }
                else if($esActividadBautizo){
                    ?>
                    var bautizados = 0;
                    var bautizadosPeriodo = 0;
                    var desiciones = 0;
                    if(document.getElementById("final_bautizados").value != ""){
                        var bautizados = document.getElementById("final_bautizados").value;
                    }
                    if(document.getElementById("final_bautizadosPeriodo").value != ""){
                        var bautizadosPeriodo = document.getElementById("final_bautizadosPeriodo").value;
                    }
                    <?php
                }
                else if($generacionNumero == 0 || $generacionNumero == 77 || $generacionNumero == 8){
                    ?>               
                    var bautizados = 0;
                    var bautizadosPeriodo = 0;
                    var desiciones = 0;
                    <?php
                }
                else if($esActividadCoach){
                    ?>
                    var bautizados = 0;
                    var bautizadosPeriodo = 0;
                    var desiciones = 0;
                    if(document.getElementById("final_desiciones").value != ""){
                        var desiciones = document.getElementById("final_desiciones").value;
                    }
                    <?php
                }
                else{
                    ?>
                    var bautizados = 0;
                    var bautizadosPeriodo = 0;
                    var desiciones = 0;
                    //
                    if(document.getElementById("final_bautizadosPeriodo").value != ""){
                        var bautizados = document.getElementById("final_bautizadosPeriodo").value;
                    }
                    if(document.getElementById("final_bautizadosPeriodo").value != ""){
                        var bautizadosPeriodo = document.getElementById("final_bautizadosPeriodo").value;
                    }
                    if(document.getElementById("final_desiciones").value != ""){
                        var desiciones = document.getElementById("final_desiciones").value;
                    }
                    <?php
                }
                ?>
                var var_suma = parseInt(asistencia_hom) + parseInt(asistencia_muj) + parseInt(asistencia_jov) + parseInt(asistencia_nin);
                document.getElementById("final_asistencia_total").value = parseInt(var_suma);
                //
                document.getElementById("final_asistencia_hom").value = parseInt(asistencia_hom);
                document.getElementById("final_asistencia_muj").value = parseInt(asistencia_muj);
                document.getElementById("final_asistencia_jov").value = parseInt(asistencia_jov);
                document.getElementById("final_asistencia_nin").value = parseInt(asistencia_nin);
                
                
                
                <?php if($esActividadEvangelismo){ ?>
                    document.getElementById("final_bautizados").value = 0;
                    document.getElementById("final_discipulado").value = 0;
                    document.getElementById("final_bautizadosPeriodo").value = 0;
                    document.getElementById("final_desiciones").value = parseInt(desiciones);
                    document.getElementById("final_preparandose").value = 0;
                <?php }else if($esActividadBautizo){ ?>
                    document.getElementById("final_bautizados").value = parseInt(bautizados) || 0;
                    document.getElementById("final_discipulado").value = 0;
                    document.getElementById("final_bautizadosPeriodo").value = parseInt(bautizadosPeriodo) || 0;
                    document.getElementById("final_desiciones").value = 0;
                    document.getElementById("final_preparandose").value = 0;
                <?php }else if($generacionNumero == 0 || $generacionNumero == 8){ ?>
                    document.getElementById("final_bautizados").value = 0;
                    document.getElementById("final_discipulado").value = 0;
                    document.getElementById("final_bautizadosPeriodo").value = 0;
                    document.getElementById("final_desiciones").value = 0;
                    document.getElementById("final_preparandose").value = 0;
                <?php }else if($esActividadCoach){ ?>
                    document.getElementById("final_bautizados").value = 0;
                    document.getElementById("final_discipulado").value = parseInt(var_suma);
                    document.getElementById("final_bautizadosPeriodo").value = 0;
                    document.getElementById("final_desiciones").value = parseInt(desiciones);
                    document.getElementById("final_preparandose").value = parseInt(var_suma);
                <?php }else{ ?>
                    document.getElementById("final_bautizados").value = parseInt(bautizados);
                    document.getElementById("final_discipulado").value = parseInt(var_suma);
                    document.getElementById("final_bautizadosPeriodo").value = parseInt(bautizadosPeriodo);
                    document.getElementById("final_desiciones").value = parseInt(desiciones);
                    document.getElementById("final_preparandose").value = parseInt(var_suma) - parseInt(bautizadosPeriodo);
                <?php } ?>
            }            
            
            function eliminarRegistro(){
                if(<?= $soloLecturaReporteFacilitador ? 'true' : 'false'; ?>){
                    return;
                }
                if(confirm("Esta seguro que desea eliminar este registro, esta acción NO se puede deshacer.")){
                    document.getElementById('funcion').value = "eliminar";
                    document.getElementById('form1').submit();
                }                
            }
            
            function generarForm(generacion){
                if(<?= $soloLecturaReporteFacilitador ? 'true' : 'false'; ?>){
                    return false;
                }
                sumar();
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

            document.getElementById('SelBeneficiarioDisplay').addEventListener('input', function() {
                var input = this.value;
                var datalist = document.getElementById('beneficiariosList');
                var options = datalist.options;
                var hiddenInput = document.getElementById('SelBeneficiario');
                hiddenInput.value = ''; // Clear hidden input initially

                for (var i = 0; i < options.length; i++) {
                    var option = options[i];
                    if (option.value === input) {
                        hiddenInput.value = option.getAttribute('data-id');
                        break;
                    }
                }
            });
            </script>           
        
    <?php
}
else if($preguntarGeneracion == 1 && !isset($_POST["SendOpcion"]) ){
    ?><div class="container">
    <div class="row">
        <h3 class="alert alert-info text-center">CREACIÓN DE REPORTE MENSUAL</h3>
    </div>

    <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form-horizontal">

    <fieldset>
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center">REPORTES MENSUALES</h3>
                <p>Escoja una de las siguientes opciones</p>
            </div>
            <div class="hr"><hr></div>
        </div>
        <style>
            .btn-reportes-container {
                display: flex;
                justify-content: center;
                align-items: stretch;
                gap: 30px;
                flex-wrap: wrap;
                padding: 20px;
            }
            .btn-reporte-card {
                position: relative;
                min-width: 280px;
                padding: 30px 40px;
                font-size: 18px;
                font-weight: 600;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transition: all 0.3s ease;
            }
            .btn-reporte-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.25);
            }
            .btn-reporte-card .btn-icon {
                font-size: 36px;
                display: block;
                margin-bottom: 10px;
            }
            .btn-reporte-card .btn-desc {
                display: block;
                font-size: 14px;
                font-weight: 400;
                margin-top: 8px;
                opacity: 0.9;
            }
            .badge-nuevo {
                position: absolute;
                top: -10px;
                right: -10px;
                background: #d9534f;
                color: white;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 8px rgba(217,83,79,0.5);
                animation: pulse-badge 2s infinite;
            }
            @keyframes pulse-badge {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
        </style>

        <div class="btn-reportes-container">
                <!-- Botones de generaciones comentados - usar módulo de Grupos en su lugar -->
                <!-- <button type="button" onClick="generarForm('cero')" name="generacionCero" class="btn-mar btn btn-primary ">GENERACIÓN 0<br><span class="btn-desc">(Capacitación)</span></button> -->
                <!-- <button type="button" onClick="generarForm('otra')" name="generacionOtra" class="btn-mar btn btn-danger">GENERACIÓN 1 A LA 5<br><span class="btn-desc">(Iglesias de pequeños grupos)</span></button> -->
                <!-- <button type="button" onClick="generarForm('evan')" name="generacionEvangelismo" class="btn-mar btn btn-success">ACTIVIDADES<br><span class="btn-desc">DE EVANGELISMO</span></button> -->
                <!-- <button type="button" onClick="generarForm('gcel')" name="granCelebracion" class="btn-mar btn btn-warning">GRAN CELEBRACIÓN<br><span class="btn-desc">(REPORTE)</span></button> -->

                <button type="button" onClick="window.location.href='index.php?doc=grupos'" class="btn btn-primary btn-reporte-card">
                    <span class="badge-nuevo">✨ NUEVO</span>
                    <span class="btn-icon">👥</span>
                    IPG
                    <span class="btn-desc">Reportes de Generaciones<br>Capacitación y Gestión</span>
                </button>

                <button type="button" onClick="generarForm('sopa')" name="sopas" class="btn btn-info btn-reporte-card">
                    <span class="btn-icon">📦</span>
                    DESHIDRATADOS
                    <span class="btn-desc">Inventario y Entrega<br>de Suministros</span>
                </button>
        </div><br><br>
        
        <input type="hidden" name="funcion" id="funcion" value="" />
        <input type="hidden" name="generacion" id="generacion" value="<?=$idVehiculo; ?>" />
    </form>


    <script language="javascript">
        function generarForm(generacion){
            if(generacion == "cero"){
                document.getElementById('generacion').value = "CERO";
            }else if(generacion == "evan"){
                document.getElementById('generacion').value = "EVAN";
            }else if(generacion == "gcel"){
                document.getElementById('generacion').value = "GCEL";                
            }else if(generacion == "sopa"){
                document.getElementById('generacion').value = "SOPA";   
            }else{
                document.getElementById('generacion').value = "OTRA";                
            }
            //Completo el formulario  
          document.getElementById('form1').submit();
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
    <?php
    
}
else if(!isset($_REQUEST["id"])){
    $temp_accionForm = "insertar";
    $idGrupoMadre = soloNumeros($_REQUEST["idGrupoMadre"]);
    //
    if(!isset($_REQUEST["fechaReporte"])){
        $fechaReporte = date("Y-m-d");        
    }else{
        $fechaReporte = eliminarInvalidos($_REQUEST["fechaReporte"]);
    }
    //
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
}
else{
    $temp_accionForm = "actualizar";
    //  ID del usuario actual
    $idReporteActual = soloNumeros($_REQUEST["id"]);    
}



/*
*   SI SE INSERTO REGISTRO SE REDIRIGE
*/
if($idReporteActual > 0){
    //No hacemos nada.
    
}
else if($preguntarGeneracion == 1){
    //No sabemos aún la GENERACIÓN.
}
else if($varExitoREP == 1){
    ?><div class="container">
        <div class="row">
            <h2 class="alert alert-info text-center"><?php
            if($idReporteActual == 0){
                echo "CREACIÓN";
            }
            else{
                echo "ACTUALIZACIÓN";
            }
            ?> DE <?=$temp_letrero; ?></h2>
        </div>

        <div class="row">
            <h2 class="alert alert-success text-center"><a href="index.php?doc=reportar&opc=2&id=<?=$ultimoId; ?>" class="h2">Se ha <?php
            if($idReporteActual == 0){
                echo "creado";
            }
            else{
                echo "actualizado";
            }
            ?> correctamente el registro, para ver el reporte de clic aquí</a>.</h2>
        </div>
    </div>
        
    <script LANGUAGE="JavaScript">
        
        //alert("Se ha creado correctamente el registro.");
        //window.location.href= "index.php?doc=reportar&opc=2&id=<?=$ultimoId; ?>";
    </script>
    <?php
}
else if($idReporteActual == 0){
    ?><style type="text/css">
          #form1 fieldset:not(:first-of-type) {
            display: none;
          }
      </style>

<div class="container">
    <div class="row">
        <h3 class="alert alert-info text-center"><?php
            if($idReporteActual == 0){
                echo "CREACIÓN";
            }
            else{
                echo "ACTUALIZACIÓN";
            }
            ?> DE <?=$temp_letrero; ?></h3>
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
    if($errorLogueo == 1)
    {
        ?><div class="row"><h1><font color="red"><u>ATENCION:</u> NO SE CREO EL INFORME<BR /><u>MOTIVO:</u> YA EXISTE UN INFORME CON ESE VEHÍCULO Y FECHA.<br />POR FAVOR VERIFIQUE.</font></h1></div><?php
    }
    //
    //
    if($error_fatal == 1){
        //No hacer nada.
    }
    else{
        ?><div class="progress">
            <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    
    <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form-horizontal">
    <div id="form-inline-error" class="alert alert-danger text-center" <?php if($texto_error == ""){ ?>style="display:none;"<?php } ?>><?=reportar_escape_attr($texto_error); ?></div>
    <input name="fechaReporte" type="hidden" id="fechaReporte" value="<?=$fechaReporte; ?>" />
    <fieldset>
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center">Información general</h3>
            <h5><?php 
                if($generacionActual == "EVAN"){echo "evangelismo";}
                else if($generacionActual == "CERO"){echo "generación 0";}
                else if($generacionActual == "OTRA"){echo "generación 1 a la 5";}
                else if($generacionActual == "GCEL"){echo "gran celebración";}
                else if($generacionActual == "SOPA"){echo "Deshidratados";}
                ?></h5>
            <p>A continuación por favor ingrese los datos requeridos</p>
            </div>
            <div class="hr"><hr></div>
        </div>  
        <?php        
        if($generacionActual != "SOPA" && $generacionActual != "OTRA"){
            ?>
        <div class="form-group">
            <div class="col-sm-3">
                <strong>Plantador/Pastor/Lider:</strong>
                <input name="plantador" type="text" id="plantador" maxlength="250" value="<?=reportar_escape_attr($plantador); ?>" class="form-control" required />
            </div>
            <div class="col-sm-3">
                <strong>Barrio (Evento):</strong></label>
            <input name="barrio" type="text" id="barrio" maxlength="250" value="<?=$barrio; ?>" class="form-control" required placeholder = "Barrio" />
            </div>            
            <div class="col-sm-3">
                <strong><?php if($generacionActual == "EVAN"){ echo "Método de evangelismo"; }else{ ?>Dirección (Evento)<?php } ?>:</strong>
                <input name="direccion" type="text" id="direccion" maxlength="250" value="<?=$direccion; ?>" class="form-control" required />
            </div>
            <div class="col-sm-3">
                <strong>Ciudad (Evento):</strong></label>
                <input name="ciudad" type="text" id="ciudad" maxlength="250" value="<?=$ciudad; ?>" class="form-control" required />
            </div>
        </div>
        <?php
        }
        
        // Campos para generaciones OTRA (1-5) - PASO 1: Información Básica siguiendo disparadores
        if($generacionActual == "OTRA"){
            ?>
            <!-- PASO 1: Información Básica del Evento (según flujo de disparadores) -->
            
            <!-- Selección de Generación (primero según lógica de negocio) -->
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">Generación</h3>
                    <h5>de la actividad</h5>
                </div>
                <div class="hr"><hr></div>
            </div> 
            <div class="form-group cont-flex fl-sard">
                <div class="cont-flex">
                    <label class="control-label col-sm-1" for="inlineRadio1">1</label>
                    <input type="radio" name="generacionNumero" id="inlineRadio1" value="1" checked required class="form-control" onchange="onGeneracionChange()" />
                </div>
                <div class="cont-flex">
                    <label class="control-label col-sm-1" for="inlineRadio2">2</label>
                    <input type="radio" name="generacionNumero" id="inlineRadio2" value="2" required class="form-control" onchange="onGeneracionChange()" />
                </div>
                <div class="cont-flex">
                    <label class="control-label col-sm-1" for="inlineRadio3">3</label>
                    <input type="radio" name="generacionNumero" id="inlineRadio3" value="3" required class="form-control" onchange="onGeneracionChange()" />
                </div>
                <div class="cont-flex">
                    <label class="control-label col-sm-1" for="inlineRadio4">4</label>
                    <input type="radio" name="generacionNumero" id="inlineRadio4" value="4" required class="form-control" onchange="onGeneracionChange()" />
                </div>
                <div class="cont-flex">
                    <label class="control-label col-sm-1" for="inlineRadio5">5</label>
                    <input type="radio" name="generacionNumero" id="inlineRadio5" value="5" required class="form-control" onchange="onGeneracionChange()" />
                </div>
            </div>
            
            <!-- Información del Grupo -->
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">Información del Grupo</h3>
                    <h5>datos básicos de la iglesia</h5>
                </div>
                <div class="hr"><hr></div>
            </div>

            <div class="form-group">
                <div class="col-sm-5">
                    <strong>Selecciona el grupo/iglesia:</strong>
                    <input name="nombreGrupo_txt" type="text" id="nombreGrupo_txt" maxlength="250" value="<?=$nombreGrupo_txt; ?>"
                           class="form-control" list="grupos-facilitador-list" autocomplete="off"
                           oninput="onGrupoSeleccionado()" onchange="onGrupoSeleccionado()" required />
                    <datalist id="grupos-facilitador-list">
                        <!-- Las opciones se llenan dinámicamente con JavaScript según la generación -->
                    </datalist>
                    <small class="text-muted">Solo se muestran grupos con reportes en la generación anterior</small>
                    <!-- Campo oculto para almacenar el grupo madre -->
                    <input type="hidden" name="grupoMadre_txt" id="grupoMadre_txt" value="<?=$grupoMadre_txt; ?>" />
                </div>
                <div class="col-sm-3">
                    <strong>Fecha de inicio:</strong>
                    <input name="fechaInicio" type="date" id="fechaInicio" maxlength="250" value="<?=$fechaInicio; ?>" max='<?=date("Y-m-d"); ?>' class="form-control" required />
                </div>
                <div class="col-sm-4">
                    <strong>¿Este grupo está comprometido como iglesia?:</strong>
                    <select required name="mapeo_comprometido" id="mapeo_comprometido" class="form-control">
                        <option value="">Sin seleccionar</option>
                        <option value="3" <?php if($mapeo_comprometido == 3){ ?>selected="selected"<?php } ?>>NO comprometido</option>
                        <option value="4" <?php if($mapeo_comprometido == 4){ ?>selected="selected"<?php } ?>>SI comprometido como iglesia</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group" id="plantador-section">
                <div class="col-sm-12">
                    <strong>Plantador/Pastor/Líder:</strong>
                    <input name="plantador" type="text" id="plantador" maxlength="250" value="<?=reportar_escape_attr($plantador); ?>" 
                           class="form-control" list="plantadores-list" autocomplete="off" required />
                    <datalist id="plantadores-list">
                        <!-- Las opciones se llenan dinámicamente con JavaScript -->
                    </datalist>
                    <small class="text-muted">Selecciona de la lista o escribe un nombre nuevo</small>
                </div>
            </div>
            
            <!-- Ubicación del Grupo -->
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">Ubicación</h3>
                    <h5>lugar donde se reúne el grupo</h5>
                </div>
                <div class="hr"><hr></div>
            </div>
            
            <div class="form-group">
                <div class="col-sm-4">
                    <strong>Barrio:</strong>
                    <input name="barrio" type="text" id="barrio" maxlength="250" value="<?=$barrio; ?>" class="form-control" required placeholder="Barrio" />
                </div>
                <div class="col-sm-4">
                    <strong>Ciudad:</strong>
                    <input name="ciudad" type="text" id="ciudad" maxlength="250" value="<?=$ciudad; ?>" class="form-control" required />
                </div>
                <div class="col-sm-4">
                    <strong>Dirección:</strong>
                    <input name="direccion" type="text" id="direccion" maxlength="250" value="<?=$direccion; ?>" class="form-control" required />
                </div>
            </div>
        <?php
        }else if($generacionActual == "SOPA"){
            // Obtener fechas de filtro si existen
            $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
            $fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

            // CALCULAR SALDO INICIAL (todo lo histórico ANTES de fecha_inicio)
            $saldoInicialDon1Tp1 = 0;
            $saldoInicialDon1Tp2 = 0;
            $saldoInicialDon2Tp1 = 0;
            $saldoInicialDon2Tp2 = 0;

            if (!empty($fechaInicio)) {
                $sqlSaldoInicial = "SELECT * FROM inventario WHERE Estado IN (0,1) AND Fecha < '".addslashes($fechaInicio)."'";
                $resultSaldoInicial = $PSN1->query($sqlSaldoInicial);

                while($rowSaldo = $resultSaldoInicial->fetch_assoc()) {
                    $tipo = $rowSaldo["Tipo"];
                    $don1 = $rowSaldo["Donante1"];
                    $don2 = $rowSaldo["Donante2"];
                    $tp1  = $rowSaldo["TipoSopa1"];
                    $tp2  = $rowSaldo["TipoSopa2"];
                    $Fac    = $rowSaldo["Facilitador"];
                    $IdUsu  = $rowSaldo["IdUsuario"];

                    // Entradas del donante 1 de Satura
                    if (($tipo == '1') AND ($don1 == '1') AND ($IdUsu == $Id)) {
                        $saldoInicialDon1Tp1 += (int)$tp1;
                        $saldoInicialDon1Tp2 += (int)$tp2;
                    }
                    // Entradas del donante 2 (otros)
                    if (($tipo == '1') AND ($don2 == '1') AND ($IdUsu == $Id)) {
                        $saldoInicialDon2Tp1 += (int)$tp1;
                        $saldoInicialDon2Tp2 += (int)$tp2;
                    }
                    // Entradas a este facilitador registradas como tipo 2
                    if (($tipo == '2') AND ($don1 == '1') AND ($Fac == $Id)) {
                        $saldoInicialDon1Tp1 += (int)$tp1;
                        $saldoInicialDon1Tp2 += (int)$tp2;
                    }
                    if (($tipo == '2') AND ($don2 == '1') AND ($Fac == $Id)) {
                        $saldoInicialDon2Tp1 += (int)$tp1;
                        $saldoInicialDon2Tp2 += (int)$tp2;
                    }
                    // Salidas (restar)
                    if (($tipo == '2') AND ($don1 == '1') AND ($IdUsu == $Id)) {
                        $saldoInicialDon1Tp1 -= (int)$tp1;
                        $saldoInicialDon1Tp2 -= (int)$tp2;
                    }
                    if (($tipo == '2') AND ($don2 == '1') AND ($IdUsu == $Id)) {
                        $saldoInicialDon2Tp1 -= (int)$tp1;
                        $saldoInicialDon2Tp2 -= (int)$tp2;
                    }
                    if ((($tipo == '3') OR ($tipo == '4') OR ($tipo == '5')) AND ($don1 == '1') AND ($IdUsu == $Id)) {
                        $saldoInicialDon1Tp1 -= (int)$tp1;
                        $saldoInicialDon1Tp2 -= (int)$tp2;
                    }
                    if ((($tipo == '3') OR ($tipo == '4') OR ($tipo == '5')) AND ($don2 == '1') AND ($IdUsu == $Id)) {
                        $saldoInicialDon2Tp1 -= (int)$tp1;
                        $saldoInicialDon2Tp2 -= (int)$tp2;
                    }
                    if (($tipo == '7') AND ($IdUsu == $Id)) {
                        $saldoInicialDon1Tp1 -= (int)$tp1;
                        $saldoInicialDon1Tp2 -= (int)$tp2;
                    }
                    if (($tipo == '8') AND ($IdUsu == $Id)) {
                        $saldoInicialDon1Tp1 += (int)$tp1;
                        $saldoInicialDon1Tp2 += (int)$tp2;
                    }
                }
            }

            // CONSULTA DEL PERÍODO
            $sql = "SELECT *";
            $sql.=" FROM inventario";

            // Agregar filtros de usuario y fecha
            $whereConditions = array();

            // Filtro de usuario: registros donde el usuario es IdUsuario O Facilitador O es una transferencia que recibe
            $whereConditions[] = "inventario.Estado IN (0,1)";
            $whereConditions[] = "(inventario.IdUsuario = '".$Id."' OR inventario.Facilitador = '".$Id."')";

            if (!empty($fechaInicio)) {
                $whereConditions[] = "Fecha >= '".addslashes($fechaInicio)."'";
            }
            if (!empty($fechaFin)) {
                $whereConditions[] = "Fecha <= '".addslashes($fechaFin)."'";
            }

            if (count($whereConditions) > 0) {
                $sql .= " WHERE " . implode(" AND ", $whereConditions);
            }

            $result = $PSN1->query($sql);
            ?>
            <table class="styled-table" style="margin-bottom: 20px;">
                <thead>
                    <tr>
                        <th colspan="6"><h3>Filtros de Inventario</h3></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="2">
                            <strong>Fecha inicio:</strong><br/>
                            <input type="date" id="fecha_inicio_inventario" class="form-control" value="<?php echo $fechaInicio; ?>" style="margin-top: 5px;" />
                        </td>
                        <td colspan="2">
                            <strong>Fecha fin:</strong><br/>
                            <input type="date" id="fecha_fin_inventario" class="form-control" value="<?php echo $fechaFin; ?>" style="margin-top: 5px;" />
                        </td>
                        <td colspan="2">
                            <button type="button" onclick="aplicarFiltrosFecha()" class="btn btn-primary">Aplicar</button>
                            <button type="button" onclick="limpiarFiltrosFecha()" class="btn btn-default">Limpiar</button>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 10px;">
                            <strong style="margin-right: 10px;">Filtros Rápidos:</strong>
                            <button type="button" onclick="filtroRapidoSemana()" class="btn btn-info btn-sm">Esta Semana</button>
                            <button type="button" onclick="filtroRapidoMes()" class="btn btn-info btn-sm">Este Mes</button>
                            <button type="button" onclick="filtroRapidoTrimestre()" class="btn btn-info btn-sm">Trimestre</button>
                            <button type="button" onclick="filtroRapidoAnio()" class="btn btn-info btn-sm">Año <?php echo date('Y'); ?></button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- SALDO INICIAL DEL PERÍODO -->
            <table class="styled-table" style="margin-bottom: 20px;">
                <tr>
                    <th colspan="3"><h3>Saldo Inicial del Período</h3><p style="font-size: 12px; margin: 5px 0 0 0;">(Antes del <?php echo $fechaInicio ?: 'inicio de los registros'; ?>)</p></th>
                </tr>
                <tr>
                    <th><h4>Donante</h4></th>
                    <th><h4>Mix de vegetales 1 lb</h4></th>
                    <th><h4>Mix de vegetales 3 lb</h4></th>
                </tr>
                <tr>
                    <td>Satura Colombia</td>
                    <td><?php echo $saldoInicialDon1Tp1; ?></td>
                    <td><?php echo $saldoInicialDon1Tp2; ?></td>
                </tr>
                <tr>
                    <td>Otros</td>
                    <td><?php echo $saldoInicialDon2Tp1; ?></td>
                    <td><?php echo $saldoInicialDon2Tp2; ?></td>
                </tr>
            </table>

            <?php
            if ($Id == '22' || $Id == '9') {
                $sumDon1Tp1 = 0;
                $sumDon1Tp2 = 0;
                $sumDon2Tp1 = 0;
                $sumDon2Tp2 = 0;
                $resDon1Tp1 = 0;
                $resDon1Tp2 = 0;
                $resDon2Tp1 = 0;
                $resDon2Tp2 = 0;
                while($row = $result->fetch_assoc()) {
                    $idArchivo = $row["IdUsuario"];
                    $tipo = $row["Tipo"];
                                 $facilitador = $row["Facilitador"]; // Nuevo: para transferencias
                    $don1 = $row["Donante1"];
                    $don2 = $row["Donante2"];
                    $tp1  = $row["TipoSopa1"];
                    $tp2  = $row["TipoSopa2"];
                    if (($tipo == '1') AND ($don1 == '1') AND ($idArchivo == '22')) {
                        $sumDon1Tp1 = $sumDon1Tp1 + (int)$tp1; 
                        $sumDon1Tp2 = $sumDon1Tp2 + (int)$tp2; 
                    } else {
                        if (($tipo == '1') AND ($don2 == '1') AND ($idArchivo == '22')) {
                            $sumDon2Tp1 = $sumDon2Tp1 + (int)$tp1; 
                            $sumDon2Tp2 = $sumDon2Tp2 + (int)$tp2; 
                        } else {
                            if (($tipo == '2') AND ($don1 == '1') AND ($idArchivo == '22')) {
                                $resDon1Tp1 = $resDon1Tp1 + (int)$tp1; 
                                $resDon1Tp2 = $resDon1Tp2 + (int)$tp2; 
                            } else {
                                if (($tipo == '2') AND ($don2 == '1') AND ($idArchivo == '22')) {
                                    $resDon2Tp1 = $resDon2Tp1 + (int)$tp1; 
                                    $resDon2Tp2 = $resDon2Tp2 + (int)$tp2; 
                                } else {
                                    // Nuevos tipos de transacción para Ximena
                                    if (($tipo == '7') AND ($idArchivo == '22')) { // Salida por transferencia
                                        $resDon1Tp1 = $resDon1Tp1 + (int)$tp1; 
                                        $resDon1Tp2 = $resDon1Tp2 + (int)$tp2; 
                                    } else if (($tipo == '8') AND ($facilitador == '22')) { // Entrada por transferencia
                                        $sumDon1Tp1 = $sumDon1Tp1 + (int)$tp1; 
                                        $sumDon1Tp2 = $sumDon1Tp2 + (int)$tp2; 
                                    }
                                }
                            } 
                        }
                    }
                }
                
                ?>    
                <table class="styled-table">   
                    <tr>
                        <th colspan="5"><h3>Inventario</h3></th>
                    </tr>
                    <tr>
                        <th><h4>Donante</h4></th>
                        <th><h4>Tipo deshidratado</h4></th>
                        <th><h4>Entradas</h4></th>
                        <th><h4>Entregado</h4></th>
                        <th><h4>Existencias</h4></th>
                    </tr>
                    <tr>
                        <td>Satura Colombia</td>
                        <td>Mix de vegetales 1 lb</td>
                        <td><?php echo $sumDon1Tp1 ?></td>
                        <td><?php echo $resDon1Tp1 ?></td>
                        <td><?php echo ($sumDon1Tp1-$resDon1Tp1) ?></td>
                    </tr>
                    <tr>
                        <td>Satura Colombia</td>
                        <td>Mix de vegetales 3 lb</td>
                        <td><?php echo $sumDon1Tp2 ?></td>
                        <td><?php echo $resDon1Tp2 ?></td>
                        <td><?php echo ($sumDon1Tp2-$resDon1Tp2) ?></td>
                    </tr>
                    <tr>
                        <td>Otros</td>
                        <td>Mix de vegetales 1 lb</td>
                        <td><?php echo $sumDon2Tp1 ?></td>
                        <td><?php echo $resDon2Tp1 ?></td>
                        <td><?php echo ($sumDon2Tp1-$resDon2Tp1) ?></td>
                    </tr>
                    <tr>
                        <td>Otros</td>
                        <td>Mix de vegetales 3 lb</td>
                        <td><?php echo $sumDon2Tp2 ?></td>
                        <td><?php echo $resDon2Tp2 ?></td>
                        <td><?php echo ($sumDon2Tp2-$resDon2Tp2) ?></td>
                    </tr>
                </table>
                </br>
                <table class="styled-table">
                    <tr>
                        <th colspan="9"><h3>Inventario por facilitador</h3></th>
                    </tr>
                    <tr>
                        <td rowspan = "2"><b><center>Facilitador</center></b></td>
                        <td colspan = "3"><b><center>Mix de vegetales 1 lb</center></b></td>


                        <td colspan = "3"><b><center>Mix de vegetales 3 lb</center></b></td>

                    </tr>
                    <tr>

                        <td><b>Recibido</b></td>
                        <td><b>Entregado</b></td>
                        <td><b>Existencias</b></td>

                        <td><b>Recibido</b></td>
                        <td><b>Entregado</b></td>
                        <td><b>Existencias</b></td>
                    </tr>
                    
                    <?php
                        $sql_usuario = "SELECT 	usuario.id as id, 
	                                        usuario.nombre as nombre
                                        FROM `usuario`
                                        WHERE tipo = '163' order by nombre";
                        $result_usuario = $PSN1->query($sql_usuario);
                        while($row_usuario = $result_usuario->fetch_assoc()) {
                            $wid = $row_usuario["id"];
                            $sql_inventario = "SELECT *";
                            $sql_inventario.=" FROM inventario";

                            // Aplicar SOLO los filtros de fecha (NO el filtro de usuario)
                            // porque esta tabla muestra todos los facilitadores
                            $whereConditionsFacilitador = array();
                            $whereConditionsFacilitador[] = "Estado IN (0,1)";
                            if (!empty($fechaInicio)) {
                                $whereConditionsFacilitador[] = "Fecha >= '".addslashes($fechaInicio)."'";
                            }
                            if (!empty($fechaFin)) {
                                $whereConditionsFacilitador[] = "Fecha <= '".addslashes($fechaFin)."'";
                            }

                            if (count($whereConditionsFacilitador) > 0) {
                                $sql_inventario .= " WHERE " . implode(" AND ", $whereConditionsFacilitador);
                            }

                            $result_inventario = $PSN1->query($sql_inventario);
                            $sumDon1Tp1 = 0;
                            $sumDon1Tp2 = 0;
                            $sumDon2Tp1 = 0;
                            $sumDon2Tp2 = 0;
                            $resDon1Tp1 = 0;
                            $resDon1Tp2 = 0;
                            $resDon2Tp1 = 0;
                            $resDon2Tp2 = 0;
                            while($row_inventario = $result_inventario->fetch_assoc()) {
                                $tipo = $row_inventario["Tipo"];
                                $don1 = $row_inventario["Donante1"];
                                $don2 = $row_inventario["Donante2"];
                                $tp1  = $row_inventario["TipoSopa1"];
                                $tp2  = $row_inventario["TipoSopa2"];
                                $Fac    = $row_inventario["Facilitador"];
                                $IdUsu  = $row_inventario["IdUsuario"];
                                if (($tipo == '2') AND ($don1 == '1') AND ($Fac == $wid)) {
                                    $sumDon1Tp1 = $sumDon1Tp1 + (int)$tp1; 
                                    $sumDon1Tp2 = $sumDon1Tp2 + (int)$tp2; 
                                } else {
                                    if (($tipo == '2') AND ($don2 == '1') AND ($Fac == $wid)) {
                                        $sumDon2Tp1 = $sumDon2Tp1 + (int)$tp1; 
                                        $sumDon2Tp2 = $sumDon2Tp2 + (int)$tp2; 
                                    } else {
                                        // Transferencias entrantes (Tipo 8) - se suman al recibido
                                        if (($tipo == '8') AND ($IdUsu == $wid)) {
                                            $sumDon1Tp1 = $sumDon1Tp1 + (int)$tp1; 
                                            $sumDon1Tp2 = $sumDon1Tp2 + (int)$tp2; 
                                        } else {
                                            // Transferencias salientes (Tipo 7) - se suman al entregado
                                            if (($tipo == '7') AND ($IdUsu == $wid)) {
                                                $resDon1Tp1 = $resDon1Tp1 + (int)$tp1; 
                                                $resDon1Tp2 = $resDon1Tp2 + (int)$tp2; 
                                            } else {
                                                // Entregas normales (Tipos 3, 4, 5)
                                                if ((($tipo == '3') OR ($tipo == '4') OR ($tipo == '5')) AND ($don1 == '1') AND ($IdUsu == $wid)) {
                                                    $resDon1Tp1 = $resDon1Tp1 + (int)$tp1; 
                                                    $resDon1Tp2 = $resDon1Tp2 + (int)$tp2; 
                                                } else {
                                                    if ((($tipo == '3') OR ($tipo == '4') OR ($tipo == '5'))  AND ($don2 == '1') AND ($IdUsu == $wid)) {
                                                        $resDon2Tp1 = $resDon2Tp1 + (int)$tp1; 
                                                        $resDon2Tp2 = $resDon2Tp2 + (int)$tp2; 
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $wresMixVeg1lb = ($sumDon1Tp1-$resDon1Tp1)+($sumDon2Tp1-$resDon2Tp1);
                            $wresMixVeg3lb = ($sumDon1Tp2-$resDon1Tp2)+($sumDon2Tp2-$resDon2Tp2);
                            $wentMixVeg1lb = ($resDon1Tp1)+($resDon2Tp1);
                            $wentMixVeg3lb = ($resDon1Tp2)+($resDon2Tp2);
                            $wr1 = ($sumDon1Tp1 + $sumDon2Tp1);
                            $wr2 = ($resDon1Tp1 + $resDon2Tp1);
                            $wr3 = ($sumDon1Tp1 + $sumDon2Tp1) - ($resDon1Tp1 + $resDon2Tp1);
                            $wr4 = ($sumDon1Tp2 + $sumDon2Tp2);
                            $wr5 = ($resDon1Tp2 + $resDon2Tp2);
                            $wr6 = ($sumDon1Tp2 + $sumDon2Tp2) - ($resDon1Tp2 + $resDon2Tp2);
                            if (($wr1 > 0) || ($wr2 > 0) || ($wr4 > 0) || ($wr5 > 0) || ($wr3 > 0) || ($wr6 > 0)) {
                                
                            ?>
                            <tr>
                                <td><?php echo $row_usuario["nombre"];?></td>

                                <td><?php echo ($wr1);?></td>
                                <td><?php echo ($wr2);?></td>
                                <td><?php echo ($wr3);?></td>

                                <td><?php echo ($wr4);?></td>
                                <td><?php echo ($wr5);?></td>
                                <td><?php echo ($wr6);?></td>
                            </tr>
                            <?php
                            }
                        }
                        ?>
                    </tr>
                </table>
                </br>
                <?php
            } else {
                $sumDon1Tp1 = 0;
                $sumDon1Tp2 = 0;
                $sumDon2Tp1 = 0;
                $sumDon2Tp2 = 0;
                $resDon1Tp1 = 0;
                $resDon1Tp2 = 0;
                $resDon2Tp1 = 0;
                $resDon2Tp2 = 0;
                $transferSalienteTp1 = 0;
                $transferSalienteTp2 = 0;
                $transferEntranteTp1 = 0;
                $transferEntranteTp2 = 0;
                while($row = $result->fetch_assoc()) {
                    $tipo = $row["Tipo"];
                    $don1 = $row["Donante1"];
                    $don2 = $row["Donante2"];
                    $tp1  = $row["TipoSopa1"];
                    $tp2  = $row["TipoSopa2"];
                    $Fac    = $row["Facilitador"];
                    $IdUsu  = $row["IdUsuario"];

                    // Tipo 1: Donaciones entrantes (recibido)
                    if (($tipo == '1') AND ($don1 == '1') AND ($IdUsu == $Id)) {
                        $sumDon1Tp1 = $sumDon1Tp1 + (int)$tp1;
                        $sumDon1Tp2 = $sumDon1Tp2 + (int)$tp2;
                    }
                    if (($tipo == '1') AND ($don2 == '1') AND ($IdUsu == $Id)) {
                        $sumDon2Tp1 = $sumDon2Tp1 + (int)$tp1;
                        $sumDon2Tp2 = $sumDon2Tp2 + (int)$tp2;
                    }
                    // Tipo 2: Entradas recibidas por este facilitador
                    if (($tipo == '2') AND ($don1 == '1') AND ($Fac == $Id)) {
                        $sumDon1Tp1 = $sumDon1Tp1 + (int)$tp1;
                        $sumDon1Tp2 = $sumDon1Tp2 + (int)$tp2;
                    }
                    if (($tipo == '2') AND ($don2 == '1') AND ($Fac == $Id)) {
                        $sumDon2Tp1 = $sumDon2Tp1 + (int)$tp1;
                        $sumDon2Tp2 = $sumDon2Tp2 + (int)$tp2;
                    }
                    // Tipo 2: Donaciones salidas del facilitador actual (entregado)
                    if (($tipo == '2') AND ($don1 == '1') AND ($IdUsu == $Id)) {
                        $resDon1Tp1 = $resDon1Tp1 + (int)$tp1;
                        $resDon1Tp2 = $resDon1Tp2 + (int)$tp2;
                    }
                    if (($tipo == '2') AND ($don2 == '1') AND ($IdUsu == $Id)) {
                        $resDon2Tp1 = $resDon2Tp1 + (int)$tp1;
                        $resDon2Tp2 = $resDon2Tp2 + (int)$tp2;
                    }
                    // Tipo 8: Transferencias entrantes (recibido)
                    if (($tipo == '8') AND ($IdUsu == $Id)) {
                        $transferEntranteTp1 = $transferEntranteTp1 + (int)$tp1;
                        $transferEntranteTp2 = $transferEntranteTp2 + (int)$tp2;
                    }
                    // Tipo 7: Transferencias salientes (transferido)
                    if (($tipo == '7') AND ($IdUsu == $Id)) {
                        $transferSalienteTp1 = $transferSalienteTp1 + (int)$tp1;
                        $transferSalienteTp2 = $transferSalienteTp2 + (int)$tp2;
                    }
                    // Tipos 3, 4, 5: Entregas a beneficiarios (entregado)
                    if ((($tipo == '3') OR ($tipo == '4') OR ($tipo == '5')) AND ($don1 == '1') AND ($IdUsu == $Id)) {
                        $resDon1Tp1 = $resDon1Tp1 + (int)$tp1;
                        $resDon1Tp2 = $resDon1Tp2 + (int)$tp2;
                    }
                    if ((($tipo == '3') OR ($tipo == '4') OR ($tipo == '5'))  AND ($don2 == '1') AND ($IdUsu == $Id)) {
                        $resDon2Tp1 = $resDon2Tp1 + (int)$tp1;
                        $resDon2Tp2 = $resDon2Tp2 + (int)$tp2;
                    }
                }
                // Calcular totales para cada tipo
                $wrecMixVeg1lb = ($sumDon1Tp1 + $sumDon2Tp1 + $transferEntranteTp1);
                $wrecMixVeg3lb = ($sumDon1Tp2 + $sumDon2Tp2 + $transferEntranteTp2);
                $wentMixVeg1lb = ($resDon1Tp1 + $resDon2Tp1);
                $wentMixVeg3lb = ($resDon1Tp2 + $resDon2Tp2);
                $wtransMixVeg1lb = $transferSalienteTp1;
                $wtransMixVeg3lb = $transferSalienteTp2;

                // Existencias = Saldo Inicial + Recibido en período - Entregado en período - Transferido
                $wresMixVeg1lb = ($saldoInicialDon1Tp1 + $saldoInicialDon2Tp1) + $wrecMixVeg1lb - $wentMixVeg1lb - $wtransMixVeg1lb;
                $wresMixVeg3lb = ($saldoInicialDon1Tp2 + $saldoInicialDon2Tp2) + $wrecMixVeg3lb - $wentMixVeg3lb - $wtransMixVeg3lb;
                $_SESSION["wresMixVeg1lb"] = $wresMixVeg1lb;
                $_SESSION["wresMixVeg3lb"] = $wresMixVeg3lb;
                ?>
                <table class="styled-table">
                    <tr>
                        <th colspan="5"><h3>Inventario</h3></th>
                    </tr>
                    <tr>
                        <th><h4>Tipo deshidratado</h4></th>
                        <th><h4>Recibido</h4></th>
                        <th><h4>Entregado</h4></th>
                        <th><h4>Transferido</h4></th>
                        <th><h4>Existencias</h4></th>
                    </tr>
                    <tr>
                        <td>Mix de vegetales 1 lb</td>
                        <td><?php echo $wrecMixVeg1lb ?></td>
                        <td><?php echo $wentMixVeg1lb ?></td>
                        <td><?php echo $wtransMixVeg1lb ?></td>
                        <td><?php echo $wresMixVeg1lb ?></td>
                    </tr>
                    <tr>
                        <td>Mix de vegetales 3 lb</td>
                        <td><?php echo $wrecMixVeg3lb ?></td>
                        <td><?php echo $wentMixVeg3lb ?></td>
                        <td><?php echo $wtransMixVeg3lb ?></td>
                        <td><?php echo $wresMixVeg3lb ?></td>
                    </tr>
                </table>
                </br>
                <?php
            }
            // Las pestañas de entrada/salida solo para SOPA
            if($generacionActual == "SOPA"){
            ?>
<div class="col-md-10 col-md-offset-1">
    <ul class="nav nav-pills nav-justified " role="tablist" >
        <?php if ($Id == '22') { ?>
            <li role="presentation" class="active"><a class="reportes" id="LiInv" href="#Inventario" aria-controls="Inventario" role="tab" data-toggle="tab">Entrada y Salida (Inventario)</a></li>
        <?php } ?>
        <li role="presentation"><a class="reportes" id="LiES" href="#EntregaSopas" aria-controls="EntregaSopas" role="tab" data-toggle="tab">Entrega Deshidratados</a></li>
    </ul>            
    <div class="tab-content" id="tabSopasContent">
<!-- aquí comienza -->
        <?php if ($Id == '22') { ?>
        <div role="tabpanel" class="tab-pane fade in active" id="Inventario">
            <br/>
            <ul class="nav nav-pills nav-justified">
                <li class="active"><a id="Ent" class="btnEntrada reportes"  href="#1a" data-toggle="tab">Entrada</a></li>
                <li><a id="Sal" class="btnSalida reportes" href="#2a" data-toggle="tab">Salida</a></li>  
                <li><a id="Transfer" class="btnTransferencia reportes" href="#3a" data-toggle="tab">Transferir</a></li>
            </ul>
            <div class="tab-content clearfix">
                <hr/>
<!-- Inventario - Entrada de deshidratados / Inicio-->
                <div class="tab-pane fade in active btnEntrada" id="1a">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Seleccionar Donante:</strong>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="EntChkPD" name="Donante" value="EntChkPD">
                                    <label class="form-check-label" for="EntChkPD">Satura Colombia</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="EntChkSP"  name="Donante" value="EntChkSP">
                                    <label class="form-check-label" for="EntChkSP">Otros</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Tipo de Deshidratados y cantidad:</strong>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <input type="checkbox" aria-label=".." id="ChkEntradaMix" name="ChkEntradaMix">
                                        <label>Mix de vegetales 1 lb</label>
                                    </span>
                                    <input type="text" class="form-control" id="EntradaMix" name="EntradaMix" maxlength="6" readonly>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-addon"> 
                                        <input type="checkbox" aria-label=".." id="ChkEntradaNab" name="ChkEntradaNab">
                                        <label>Mix de vegetales 3 lbs&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                                    </span> 
                                    <input type="text" class="form-control" id="EntradaNab" name="EntradaNab" maxlength="6" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>Fecha de Entrada:</strong>
                                <input name="fechaEntrada" type="date" id="fechaEntrada" maxlength="250" value="<?=date("Y-m-d"); ?>" max='<?=date("Y-m-d"); ?>' class="form-control" required  />
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong> Responsable de la Entrada:</strong>
                                <input name="ResponsableEnt" type="text" id="ResponsableEnt" readonly maxlength="250" value="<?=$_SESSION['nombre'];  ; ?>" class="form-control" required />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <strong>Lugar de Entrada:</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <select name="pais" id="pais" class="form-control">
                                    <option value="0">Seleccione el Pais</option>
                                    <option value="1">Colombia</option>
                                    <option value="2">Venezuela</option>
                                </select>
                            </div>
                       </div>
                       <div class="col-md-4">
                            <div class="form-group">
                                <select name="departamento" id="departamento" class="form-control">                                
                                    <option value="0">...</option>                                   
                                </select>
                            </div>
                       </div>
                       <div class="col-md-4">
                            <div class="form-group">
                                <select name="ciudad" id="ciudad" class="form-control">                            
                                    <option value="0">...</option> 
                                </select>
                            </div>
                       </div>
                    </div>
                    <div class="row">
                        <div class="cont-btn cont-flex fl-sbet">
                            <div class="item-btn"></div>
                            <div class="item-btn">
                                <input type="button" name="Btn1a" onclick="SendData('1a')" class="next btn btn-success" value="Guardar" />
                            </div>
                        </div> 
                    </div>    
                </div>
<!-- Inventario - Entrada de deshidratados / Fin
     Inventario - Salida de deshidratados / Inicio -->
                <div class="tab-pane fade btnSalida" id="2a">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Seleccionar Donante:</strong>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="salDon1" name="Donante" value="salDon1" >
                                    <label class="form-check-label" for="salDon1">Satura Colombia</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="salDon2" name="Donante" value="salDon2">
                                    <label class="form-check-label" for="salDon2">Otras</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Tipo de Deshidratados y Cantidad:</strong>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <input type="checkbox" aria-label=".." id="ChkSalidaMix" name="ChkSalidaMix">
                                        <label>Mix de vegetales 1 lb</label>
                                    </span>
                                    <input type="text" class="form-control" id="SalidaMix" name="SalidaMix" maxlength="6" readonly>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-addon"> 
                                        <input type="checkbox" aria-label=".." id="ChkSalidaNab" name="ChkSalidaNab">
                                        <label>Mix de vegetales 3 lbs&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                                    </span> 
                                    <input type="text" class="form-control" id="SalidaNab" name="SalidaNab" maxlength="6" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>Fecha de Salida:</strong>
                                <input name="fechaSalida" type="date" id="fechaSalida" maxlength="250" value="<?=date("Y-m-d"); ?>" max='<?=date("Y-m-d"); ?>' class="form-control" required  />
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong> Responsable de la Entrega:</strong>
                                <input name="ResSalida" type="text" id="ResSalida" readonly maxlength="250" value="<?=$_SESSION['nombre'];  ; ?>" class="form-control" required />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group" >
                            <strong>  Facilitador que Recibe:</strong>
                                <select name="SalFacilitador" id="SalFacilitador" class="form-control">
                                <?php
                                    $PSN_FAC = new DBbase_Sql;
                                    $sql = "SELECT id, nombre FROM satukhvt_smsapp.usuario where ((acceso > 0) and (id != '22') and (id != '1') and (id != '9') and (id != '45') and (id != '37'))  order by nombre";//tipo = 163 and
                                    $PSN_FAC->query($sql);
                                    $PSN->query($sql);
                                    if($PSN_FAC->num_rows() > 0){
                                        while($PSN_FAC->next_record()){
                                            ?>
                                            <option value="<?=$PSN_FAC->f("id");?>"><?=$PSN_FAC->f("nombre");?></option>  
                                            <?php
                                        }
                                    }
                                ?>                                                                    
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <strong>Lugar de Llegada:</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <select name="salpais" id="salpais" class="form-control">
                                    <option value="0">Seleccione el Pais</option>
                                    <option value="1">Colombia</option>
                                    <option value="2">Venezuela</option>
                                </select>
                            </div>
                       </div>
                       <div class="col-md-4">
                            <div class="form-group">
                                <select name="saldepartamento" id="saldepartamento" class="form-control">                                
                                    <option value="1">...</option>                                   
                                </select>
                            </div>
                       </div>
                       <div class="col-md-4">
                            <div class="form-group">
                                <select name="salciudad" id="salciudad" class="form-control">                            
                                    <option value="1">...</option> 
                                </select>
                            </div>
                       </div>
                    </div>
                    <div class="row">
                        <div class="cont-btn cont-flex fl-sbet">
                            <div class="item-btn"></div>
                            <div class="item-btn">
                                <input type="button" name="Btn2a" onclick="SendData('2a')" class="next btn btn-success" value="Guardar" />
                            </div>
                        </div> 
                    </div>
                </div>
                <div class="tab-pane fade" id="3a">
                    <br/>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Facilitador de Origen:</strong>
                                <select name="FacilitadorOrigen" id="FacilitadorOrigen" class="form-control">
                                    <option value="">Seleccione Facilitador</option>
                                    <?php
                                        $PSN_FAC_ORIGEN = new DBbase_Sql;
                                        $sql_origen = "SELECT id, nombre FROM usuario WHERE acceso > 0 AND tipo = 163 ORDER BY nombre"; // Asumiendo tipo 163 es facilitador
                                        $PSN_FAC_ORIGEN->query($sql_origen);
                                        while($PSN_FAC_ORIGEN->next_record()){
                                            ?>
                                            <option value="<?=$PSN_FAC_ORIGEN->f("id");?>"><?=$PSN_FAC_ORIGEN->f("nombre");?></option>  
                                            <?php
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Facilitador de Destino:</strong>
                                <select name="FacilitadorDestino" id="FacilitadorDestino" class="form-control">
                                    <option value="">Seleccione Facilitador</option>
                                    <?php
                                        $PSN_FAC_DESTINO = new DBbase_Sql;
                                        $sql_destino = "SELECT id, nombre FROM usuario WHERE acceso > 0 AND tipo = 163 ORDER BY nombre"; // Asumiendo tipo 163 es facilitador
                                        $PSN_FAC_DESTINO->query($sql_destino);
                                        while($PSN_FAC_DESTINO->next_record()){
                                            ?>
                                            <option value="<?=$PSN_FAC_DESTINO->f("id");?>"><?=$PSN_FAC_DESTINO->f("nombre");?></option>  
                                            <?php
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Cantidad Mix de vegetales 1 lb:</strong>
                                <input type="number" class="form-control" id="TransferMix" name="TransferMix" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Cantidad Mix de vegetales 3 lbs:</strong>
                                <input type="number" class="form-control" id="TransferNab" name="TransferNab" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Fecha de Transferencia:</strong>
                                <input name="fechaTransferencia" type="date" id="fechaTransferencia" maxlength="250" value="<?=date("Y-m-d"); ?>" max='<?=date("Y-m-d"); ?>' class="form-control" required />
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Responsable de la Transferencia:</strong>
                                <input name="ResTransferencia" type="text" id="ResTransferencia" readonly maxlength="250" value="<?=$_SESSION['nombre']; ?>" class="form-control" required />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="cont-btn cont-flex fl-sbet">
                            <div class="item-btn">
                                </div>
                                <div class="item-btn">
                                    <input type="button" name="BtnTransferencia" onclick="return confirmTransfer(event)" class="btn btn-success" value="Transferir" />
                                    <input type="button" onclick="openTransfersModal()" class="btn btn-info" value="Ver Transferencias" />

<script>
function confirmTransfer(event) {
    // Prevenir comportamiento por defecto
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    if (confirm('¿Está seguro de que desea realizar esta transferencia?')) {
        performTransfer();
    }
    
    return false; // Prevenir cualquier acción adicional
}

function performTransfer() {
    // Mostrar indicador de carga
    const transferBtn = document.querySelector('input[name="BtnTransferencia"]');
    const originalValue = transferBtn.value;
    transferBtn.value = 'Procesando...';
    transferBtn.disabled = true;
    
    // Recopilar datos del formulario
    const formData = new FormData();
    formData.append('FacilitadorOrigen', document.getElementById('FacilitadorOrigen').value);
    formData.append('FacilitadorDestino', document.getElementById('FacilitadorDestino').value);
    formData.append('TransferMix', document.getElementById('TransferMix').value || 0);
    formData.append('TransferNab', document.getElementById('TransferNab').value || 0);
    formData.append('fechaTransferencia', document.getElementById('fechaTransferencia').value);
    formData.append('ResTransferencia', document.getElementById('ResTransferencia').value);
    
    // Realizar petición AJAX
    fetch('transfer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Restaurar botón
        transferBtn.value = originalValue;
        transferBtn.disabled = false;
        
        if (data.success) {
            // Mostrar mensaje de éxito
            showMessage(data.message, 'success');
            // Limpiar formulario
            clearTransferForm();
        } else {
            // Mostrar mensaje de error
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        transferBtn.value = originalValue;
        transferBtn.disabled = false;
        showMessage('Error de conexión. Por favor, intente de nuevo.', 'error');
    });
}

function showMessage(message, type) {
    // Crear elemento de mensaje
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const messageHtml = `
        <div class="alert ${alertClass} alert-dismissible" style="margin: 20px 0;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            ${message}
        </div>
    `;
    
    // Insertar mensaje al inicio del contenido
    const container = document.querySelector('.col-md-10.col-md-offset-1') || document.body;
    container.insertAdjacentHTML('afterbegin', messageHtml);
    
    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

function clearTransferForm() {
    document.getElementById('FacilitadorOrigen').selectedIndex = 0;
    document.getElementById('FacilitadorDestino').selectedIndex = 0;
    document.getElementById('TransferMix').value = '';
    document.getElementById('TransferNab').value = '';
    document.getElementById('fechaTransferencia').value = '<?=date("Y-m-d"); ?>';
    // No limpiar ResTransferencia ya que siempre debe mantener el usuario logueado
}

// Modal de transferencias
function openTransfersModal() {
    document.getElementById('transfersModal').style.display = 'flex';
    loadTransfers(1, true);
}

function closeTransfersModal(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    document.getElementById('transfersModal').style.display = 'none';
    return false;
}

function loadTransfers(page = 1, isInitialLoad = false) {
    const loadingDiv = document.getElementById('transfersLoading');
    const contentDiv = document.getElementById('transfersContent');
    const errorDiv = document.getElementById('transfersError');
    const tableBody = document.getElementById('transfersTableBody');
    const paginationDiv = document.getElementById('transfersPagination');
    
    if (isInitialLoad) {
        // Carga inicial: mostrar loading completo
        loadingDiv.style.display = 'block';
        contentDiv.style.display = 'none';
        errorDiv.style.display = 'none';
    } else {
        // Cambio de página: mostrar indicador sutil en la tabla
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="table-loading">
                        <div class="mini-spinner"></div>
                        Cargando página ${page}...
                    </td>
                </tr>
            `;
        }
        
        // Deshabilitar botones de paginación temporalmente
        const pageButtons = paginationDiv.querySelectorAll('.page-btn:not(.disabled)');
        pageButtons.forEach(btn => {
            btn.style.opacity = '0.5';
            btn.style.pointerEvents = 'none';
        });
    }
    
    // Hacer petición
    fetch(`list_transfers.php?page=${page}&limit=10`)
        .then(response => response.json())
        .then(data => {
            if (isInitialLoad) {
                loadingDiv.style.display = 'none';
            }
            
            if (data.success) {
                renderTransfers(data.data.transfers, data.data.pagination);
                contentDiv.style.display = 'block';
                
                // Restaurar botones de paginación
                if (!isInitialLoad) {
                    const pageButtons = paginationDiv.querySelectorAll('.page-btn');
                    pageButtons.forEach(btn => {
                        btn.style.opacity = '1';
                        btn.style.pointerEvents = 'auto';
                    });
                }
            } else {
                if (isInitialLoad) {
                    showTransfersError(data.message);
                } else {
                    // En cambio de página, mostrar error en la tabla
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="table-error">
                                ⚠️ Error: ${data.message}
                                <br><button class="retry-mini-btn" onclick="loadTransfers(${page})">Reintentar</button>
                            </td>
                        </tr>
                    `;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (isInitialLoad) {
                loadingDiv.style.display = 'none';
                showTransfersError('Error de conexión');
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="table-error">
                            ⚠️ Error de conexión
                            <br><button class="retry-mini-btn" onclick="loadTransfers(${page})">Reintentar</button>
                        </td>
                    </tr>
                `;
            }
        });
}

function renderTransfers(transfers, pagination) {
    const tbody = document.getElementById('transfersTableBody');
    const paginationDiv = document.getElementById('transfersPagination');
    
    // Limpiar tabla
    tbody.innerHTML = '';
    
    if (transfers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #666;">No se encontraron transferencias</td></tr>';
    } else {
        transfers.forEach(transfer => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${transfer.fecha}</td>
                <td class="transfer-flow">
                    <div class="transfer-participants">
                        <span class="transfer-origin">${transfer.origen_nombre}</span>
                        <span class="transfer-arrow">→</span>
                        <span class="transfer-destination">${transfer.destino_nombre}</span>
                    </div>
                </td>
                <td>
                    <div class="transfer-quantities">
                        ${transfer.cantidad_mix > 0 ? `<span class="qty-mix">${transfer.cantidad_mix} Mix</span>` : ''}
                        ${transfer.cantidad_nab > 0 ? `<span class="qty-nab">${transfer.cantidad_nab} Nab</span>` : ''}
                    </div>
                </td>
                <td>${transfer.responsable}</td>
                <td><span class="status-success">Completada</span></td>
                <td>
                    <span class="btn-ios-detail" onclick="event.preventDefault(); event.stopPropagation(); viewTransferDetail(${transfer.id}); return false;">
                        <i class="icon-detail">ⓘ</i>
                    </span>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    // Renderizar paginación
    renderPagination(pagination, paginationDiv);
}

function renderPagination(pagination, container) {
    let paginationHtml = '<div class="ios-pagination">';
    
    // Botón anterior
    if (pagination.has_prev) {
        paginationHtml += `<span class="page-btn" onclick="event.preventDefault(); event.stopPropagation(); loadTransfers(${pagination.current_page - 1}); return false;">‹</span>`;
    } else {
        paginationHtml += '<span class="page-btn disabled">‹</span>';
    }
    
    // Páginas
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === pagination.current_page) {
            paginationHtml += `<span class="page-btn active">${i}</span>`;
        } else {
            paginationHtml += `<span class="page-btn" onclick="event.preventDefault(); event.stopPropagation(); loadTransfers(${i}); return false;">${i}</span>`;
        }
    }
    
    // Botón siguiente
    if (pagination.has_next) {
        paginationHtml += `<span class="page-btn" onclick="event.preventDefault(); event.stopPropagation(); loadTransfers(${pagination.current_page + 1}); return false;">›</span>`;
    } else {
        paginationHtml += '<span class="page-btn disabled">›</span>';
    }
    
    paginationHtml += '</div>';
    paginationHtml += `<div class="pagination-info">Página ${pagination.current_page} de ${pagination.total_pages} (${pagination.total_records} registros)</div>`;
    
    container.innerHTML = paginationHtml;
}

function showTransfersError(message) {
    const errorDiv = document.getElementById('transfersError');
    errorDiv.querySelector('.error-message').textContent = message;
    errorDiv.style.display = 'block';
}

function viewTransferDetail(id) {
    alert(`Ver detalles de transferencia ID: ${id}`);
}

function exportTransfersToCSV() {
    // Crear un enlace temporal para descargar
    const link = document.createElement('a');
    link.href = 'export_transfers_csv.php';
    link.download = '';
    
    // Simular click para iniciar descarga
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Mostrar mensaje de confirmación
    showMessage('Descargando archivo CSV (compatible con Excel)...', 'success');
}

// Cerrar modal al hacer click fuera
window.onclick = function(event) {
    const modal = document.getElementById('transfersModal');
    if (event.target == modal) {
        event.preventDefault();
        event.stopPropagation();
        closeTransfersModal();
        return false;
    }
}
</script>

<!-- Modal de Transferencias -->
<div id="transfersModal" class="ios-modal">
    <div class="ios-modal-content">
        <div class="ios-modal-header">
            <h3>Historial de Transferencias</h3>
            <div class="modal-header-actions">
                <button class="export-excel-btn" onclick="event.preventDefault(); event.stopPropagation(); exportTransfersToCSV(); return false;">
                    📊 Exportar CSV
                </button>
                <span class="ios-close-btn" onclick="event.preventDefault(); event.stopPropagation(); closeTransfersModal(); return false;">✕</span>
            </div>
        </div>
        
        <div class="ios-modal-body">
            <!-- Loading -->
            <div id="transfersLoading" class="ios-loading">
                <div class="spinner"></div>
                <p>Cargando transferencias...</p>
            </div>
            
            <!-- Error -->
            <div id="transfersError" class="ios-error" style="display: none;">
                <div class="error-icon">⚠️</div>
                <p class="error-message">Error al cargar las transferencias</p>
                <button class="retry-btn" onclick="loadTransfers(1, true)">Reintentar</button>
            </div>
            
            <!-- Content -->
            <div id="transfersContent" style="display: none;">
                <div class="ios-table-container">
                    <table class="ios-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Transferencia</th>
                                <th>Cantidades</th>
                                <th>Responsable</th>
                                <th>Estado</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody id="transfersTableBody">
                        </tbody>
                    </table>
                </div>
                
                <div id="transfersPagination" class="ios-pagination-container">
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos iOS para el modal */
.ios-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(10px);
    justify-content: center;
    align-items: center;
}

.ios-modal-content {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    width: 90%;
    max-width: 900px;
    max-height: 80vh;
    overflow: hidden;
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.ios-modal-header {
    background: linear-gradient(135deg, #007AFF, #5856D6);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.ios-modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.ios-close-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 15px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    user-select: none;
}

.ios-close-btn:hover {
    background: rgba(255,255,255,0.3);
}

.export-excel-btn {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 8px 15px;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 5px;
    user-select: none;
}

.export-excel-btn:hover {
    background: rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.5);
    transform: translateY(-1px);
}

.ios-modal-body {
    padding: 0;
    max-height: calc(80vh - 80px);
    overflow-y: auto;
}

.ios-loading {
    text-align: center;
    padding: 40px;
    color: #666;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #007AFF;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.ios-error {
    text-align: center;
    padding: 40px;
    color: #FF3B30;
}

.error-icon {
    font-size: 30px;
    margin-bottom: 10px;
}

.retry-btn {
    background: #007AFF;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 15px;
}

.ios-table-container {
    overflow-x: auto;
}

.ios-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.ios-table th {
    background: #F2F2F7;
    color: #1D1D1F;
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 1px solid #D1D1D6;
    position: sticky;
    top: 0;
}

.ios-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #F2F2F7;
    color: #1D1D1F;
}

.ios-table tbody tr:hover {
    background: #F9F9F9;
}

.transfer-flow {
    font-size: 13px;
}

.transfer-participants {
    display: flex;
    align-items: center;
    gap: 8px;
}

.transfer-origin {
    color: #FF9500;
    font-weight: 500;
}

.transfer-arrow {
    color: #007AFF;
    font-weight: bold;
}

.transfer-destination {
    color: #34C759;
    font-weight: 500;
}

.transfer-quantities {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.qty-mix, .qty-nab {
    background: #E5F3FF;
    color: #007AFF;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.qty-nab {
    background: #E8F5E8;
    color: #34C759;
}

.status-success {
    background: #E8F5E8;
    color: #34C759;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.btn-ios-detail {
    background: #F2F2F7;
    border: none;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    color: #007AFF;
    transition: background 0.2s;
    display: inline-block;
    user-select: none;
}

.btn-ios-detail:hover {
    background: #E5E5EA;
}

.ios-pagination-container {
    padding: 20px;
    border-top: 1px solid #F2F2F7;
    background: #FAFAFA;
}

.ios-pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-bottom: 10px;
}

.page-btn {
    background: #F2F2F7;
    border: none;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    color: #007AFF;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-block;
    user-select: none;
}

.page-btn:hover:not(.disabled) {
    background: #E5E5EA;
}

.page-btn.active {
    background: #007AFF;
    color: white;
}

.page-btn.disabled {
    color: #C7C7CC;
    cursor: not-allowed;
}

.pagination-info {
    text-align: center;
    color: #8E8E93;
    font-size: 12px;
}

/* Estados de carga y error en la tabla */
.table-loading {
    text-align: center;
    padding: 30px !important;
    color: #666;
    background: #FAFAFA;
}

.table-error {
    text-align: center;
    padding: 30px !important;
    color: #FF3B30;
    background: #FFF5F5;
}

.mini-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #007AFF;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 10px;
    display: inline-block;
}

.retry-mini-btn {
    background: #007AFF;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    margin-top: 8px;
}

.retry-mini-btn:hover {
    background: #0056CC;
}

/* Transiciones suaves para cambios de estado */
.ios-table tbody {
    transition: opacity 0.2s ease;
}

.page-btn {
    transition: all 0.2s ease;
}
</style>
                            </div>
                        </div> 
                    </div>
                </div>

<!-- Inventario - Salida de deshidratados / Fin -->
            </div>
        </div>
        <?php } ?>
<!-- Aquí termina -->
        <div role="tabpanel" class="tab-pane fade" id="EntregaSopas">
            <br/>
            <ul class="nav nav-pills nav-justified">
                <li class="active"><a id="LiEv" class="btnEvangelismo reportes" href="#1aa" data-toggle="tab">Evangelismo</a></li>
                <li><a id="LiPE"  class="btnPrimeraEntrega reportes" href="#2aa" data-toggle="tab">Primera Entrega</a></li>  
                <li><a id="LiOE"  class="btnOtrasEntregas reportes" href="#3aa" data-toggle="tab">Otras entregas</a></li>               
            </ul>
            <div class="tab-content clearfix">
                <hr/>
<!-- Inventario - Salida de deshidratados Evangelismo / Inicio -->
                <div class="tab-pane fade in active btnEvangelismo" id="1aa">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Seleccionar Donante:</strong>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="EvaChkPD" name="Donante1" value="EvaChkPD" checked>
                                    <label class="form-check-label" for="EvaChkPD">Satura Colombia</label>
                                </div>
<!--                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="EvaChkSP" name="Donante" value="EvaChkSP">
                                    <label class="form-check-label" for="EvaChkSP">Otros</label>
                                </div> -->
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Tipo de Deshidratados y cantidad de Bolsas Usadas:</strong>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <input type="checkbox" aria-label=".." id="ChkEvaMix" name="ChkEvaMix">
                                        <label>Mix de vegetales 1 lb</label>
                                    </span>
                                    <input type="text" class="form-control" id="EvaMix" name="EvaMix" maxlength="6" readonly>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-addon"> 
                                        <input type="checkbox" aria-label=".." id="ChkEvaNab" name="ChkEvaNab">
                                        <label>Mix de vegetales 3 lbs&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                                    </span> 
                                    <input type="text" class="form-control" id="EvaNab" name="EvaNab" maxlength="6" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>Fecha de la Actividad:</strong>
                                <input name="fechaEva" type="date" id="fechaEva" maxlength="250" value="<?=date("Y-m-d"); ?>" max='<?=date("Y-m-d"); ?>' class="form-control" required  />
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong> Responsable de la Actividad</strong>
                                <input name="ResEva" type="text" id="ResEva" readonly maxlength="250" value="<?=$_SESSION['nombre'];  ; ?>" class="form-control" required />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <strong>Lugar de la Actividad:</strong>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <select name="evapais" id="evapais" class="form-control">
                                    <option value="0">Seleccione el Pais</option>
                                    <option value="1">Colombia</option>
                                    <option value="2">Venezuela</option>
                                </select>
                            </div>
                       </div>
                       <div class="col-md-4">
                            <div class="form-group">
                                <select name="evadepartamento" id="evadepartamento" class="form-control">                                
                                    <option value="1">...</option>                                   
                                </select>
                            </div>
                       </div>
                       <div class="col-md-4">
                            <div class="form-group">
                                <select name="evaciudad" id="evaciudad" class="form-control">                            
                                    <option value="1">...</option> 
                                </select>
                            </div>
                       </div>
                       <div class="col-md-2">
                            <div class="form-group">
                            <input name="barrioEv" type="text" id="barrioEv" maxlength="250" value=""  class="form-control"  placeholder = "Barrio"  />
                            </div>
                       </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>Total Personas Beneficiadas:</strong>
                                <input name="PerBeneficiadas" type="text" id="PerBeneficiadas" maxlength="4" value=""  class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                        </div>
                    </div>
                    <div class="row">
                        <div class="cont-btn cont-flex fl-sbet">
                            <div class="item-btn"></div>
                            <div class="item-btn">
                                <input type="button" name="Btn1aa" onclick="SendData('1aa')" class="next btn btn-success" value="Guardar" />
                            </div>
                        </div> 
                    </div>
                </div>
<!-- Fin Evangelismo / Inicio Primera entrega -->
                <div class="tab-pane fade btnPrimeraEntrega" id="2aa">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Seleccionar Donante:</strong>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="PEChkPD" name="Donante2" value="PEChkPD" checked>
                                    <label class="form-check-label" for="PEChkPD">Satura Colombia</label>
                                </div>
<!--                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="PEChkSP" name="Donante" value="PEChkSP">
                                    <label class="form-check-label" for="PEChkSP">Otros</label>
                                </div> -->
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Tipo de Deshidratados y cantidad de Bolsas Usadas:</strong>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <input type="checkbox" aria-label=".." id="ChkPEMix" name="ChkPEMix">
                                        <label>Mix de vegetales 1 lb</label>
                                    </span>
                                    <input type="text" class="form-control" id="PEMix" name="PEMix" maxlength="6" readonly>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-addon"> 
                                        <input type="checkbox" aria-label=".." id="ChkPENab" name="ChkPENab">
                                        <label>Mix de vegetales 3 lbs&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                                    </span> 
                                    <input type="text" class="form-control" id="PENab" name="PENab" maxlength="6" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>Fecha de la Entrega:</strong>
                                <input name="fechaPE" type="date" id="fechaPE" maxlength="250" value="<?=date("Y-m-d"); ?>" max='<?=date("Y-m-d"); ?>' class="form-control"  />
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong> Responsable de la Entrega</strong>
                                <input name="ResPE" type="text" id="ResPE" maxlength="250" value="<?=$_SESSION['nombre'];  ; ?>" class="form-control"readonly />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>Nombre y Apellido de quien recibe:</strong>
                                <input name="nombre" type="text" id="nombre" maxlength="250" value=""  class="form-control"/>
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Teléfono de Contacto:</strong>
                                <input name="telefono" type="text" id="telefono" maxlength="12" value="" class="form-control"/>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <strong>Ubicacion Beneficiario:</strong>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <select name="pepais" id="pepais" class="form-control">
                                    <option value="0">Seleccione el Pais</option>
                                    <option value="1">Colombia</option>
                                    <option value="2">Venezuela</option>
                                </select>
                            </div>
                       </div>
                       <div class="col-md-4">
                            <div class="form-group">
                                <select name="pedepartamento" id="pedepartamento" class="form-control">                                
                                    <option value="1">...</option>                                   
                                </select>
                            </div>
                       </div>
                       <div class="col-md-4">
                            <div class="form-group">
                                <select name="peciudad" id="peciudad" class="form-control">                            
                                    <option value="1">...</option> 
                                </select>
                            </div>
                       </div>
                       <div class="col-md-2">
                            <div class="form-group">
                            <input name="barrioPE" type="text" id="barrioPE" maxlength="250" value=""  class="form-control"  placeholder = "Barrio"  />
                            </div>
                       </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>¿Cuántas personas viven en la casa?:</strong>
                                <input name="PersonasCasa" type="text" id="PersonasCasa" maxlength="3" value=""  class="form-control"  />
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>¿Cuántos niños hay? (de 0 a 10 años): </strong>
                                <input name="NinosCasa" type="text" id="NinosCasa"  maxlength="3" value="" class="form-control" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>¿Cuántos niños son beneficiarios de Soy Satura?:</strong>
                                <input name="SoySatura" type="text" id="SoySatura" maxlength="3" value=""  class="form-control"  />
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>¿Cuántos adolescentes hay? (de 11 a 18 años):  </strong>
                                <input name="AdoCasa" type="text" id="AdoCasa" maxlength="3" value="" class="form-control" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>¿Cuántos adultos mayores hay? (de 60 años en adelante): </strong>
                                <input name="Adultos" type="text" id="Adultos" maxlength="3" value=""  class="form-control"  />
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>¿Cuántas personas en condición de discapacidad hay?: </strong>
                                <input name="Discapacidad" type="text" id="Discapacidad" maxlength="3" value="" class="form-control"/>
                            </div>
                        </div>
                    </div>                
                    <div class="row">
                        <div class="col-md-5">
                           
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>¿Qué tipo de discapacidad?: </strong>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkMovilidad" name="ChkMovilidad">
                                    <label class="form-check-label" for="ChkMovilidad">Movilidad</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkMental" Name="ChkMental">
                                    <label class="form-check-label" for="ChkMental">Mental</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkAuditiva" name="ChkAuditiva">
                                    <label class="form-check-label" for="ChkAuditiva">Auditiva</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkVisual" name="ChkVisual" >
                                    <label class="form-check-label" for="ChkVisual">Visual</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkOtras" name="ChkOtras">
                                    <label class="form-check-label" for="ChkOtras">Otras</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkNingunaDis" name="ChkNingunaDis">
                                    <label class="form-check-label" for="ChkNingunaDis">Ninguna</label>
                                </div>
                            </div>   
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>¿Cuánto suman los ingresos mensuales de su grupo familiar?: </strong>
                                <div class="radio">
                                    <label class="radio" >
                                        <input type="radio" name="OptIngresos" id="Opt1" value="Opt01">Actualmente no recibe ningún ingreso
                                    </label>
                                    <label class="radio">
                                        <input type="radio" name="OptIngresos" id="Opt2" value="Opt02">Menos de un salario mínimo
                                    </label>   
                                    <label class="radio">
                                        <input type="radio" name="OptIngresos" id="Opt3" value="Opt03">Un salario mínimo
                                    </label>   
                                    <label class="radio">
                                        <input type="radio" name="OptIngresos" id="Opt4" value="Opt04">Más de un salario mínimo
                                    </label>   
                                    <label class="radio">
                                        <input type="radio" name="OptIngresos" id="Opt5" value="Opt05">No sabe
                                    </label>                                   
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>Usted como cabeza del grupo familiar regularmente: ¿Cuál de las siguientes comidas No consume?: </strong>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkDesayuno" name="ChkDesayuno">
                                    <label class="form-check-label" for="ChkDesayuno">Desayuno</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkAlmuerzo" name="ChkAlmuerzo">
                                    <label class="form-check-label" for="ChkAlmuerzo">Almuerzo</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkCena" name="ChkCena">
                                    <label class="form-check-label" for="ChkCena">Cena</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkTodas" name="ChkTodas">
                                    <label class="form-check-label" for="ChkTodas">Todas</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>¿Dentro de su grupo familiar se ha presentado alguna de las siguientes situaciones?: </strong>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkSuicidio" name="ChkSuicidio">
                                    <label class="form-check-label" for="ChkSuicidio">Suicidio</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkViolencia" name="ChkViolencia">
                                    <label class="form-check-label" for="ChkViolencia">Violencia Intrafamiliar</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkAbuso" name="ChkAbuso">
                                    <label class="form-check-label" for="ChkAbuso">Abuso sexual a menores</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkDesaparicion" name="ChkDesaparicion">
                                    <label class="form-check-label" for="ChkDesaparicion">Desaparición forzosa</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkDesplazamiento" name="ChkDesplazamiento">
                                    <label class="form-check-label" for="ChkDesplazamiento">Desplazamiento</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkTrafico" name="ChkTrafico">
                                    <label class="form-check-label" for="ChkTrafico">Tráfico de personas</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="ChkNingunaSit" name="ChkNingunaSit">
                                    <label class="form-check-label" for="ChkNingunaSit">Ninguna</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="cont-btn cont-flex fl-sbet">
                            <div class="item-btn"></div>
                            <div class="item-btn">
                                <input type="button" name="Btn2aa" onclick="SendData('2aa')" class="next btn btn-success" value="Guardar" />
                            </div>
                        </div> 
                    </div>
                </div>
<!-- Fin primera entrega / inicio otras entregas -->
                <div class="tab-pane fade btnOtrasEntregas" id="3aa">
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>Seleccionar Beneficiario:</strong>
                                <input type="text" name="SelBeneficiarioDisplay" id="SelBeneficiarioDisplay" list="beneficiariosList" class="form-control" placeholder="Buscar beneficiario...">
                                <input type="hidden" name="SelBeneficiario" id="SelBeneficiario">
                                <datalist id="beneficiariosList">
                                    <?php
                                        $PSN_BEN = new DBbase_Sql;
                                        $sql_Ben = "SELECT DISTINCT b.IdBeneficiado, CONCAT(b.Nombre, ' (', b.telefono, ')') as NombreCompleto 
                                                    FROM beneficiarios b 
                                                    INNER JOIN inventario i ON b.IdBeneficiado = i.Beneficiario 
                                                    WHERE b.estado = 'activo' 
                                                    AND i.Tipo = 4 
                                                    AND i.IdUsuario = " . $_SESSION["id"] . " 
                                                    ORDER BY b.Nombre";
                                        $PSN_BEN->query($sql_Ben);
                                        if($PSN_BEN->num_rows() > 0){
                                            while($PSN_BEN->next_record()){
                                                ?>
                                                <option value="<?=$PSN_BEN->f("NombreCompleto");?>" data-id="<?=$PSN_BEN->f("IdBeneficiado");?>"></option>  
                                                <?php
                                            }
                                        }
                                    ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Seleccionar Donante:</strong>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="Radio" id="OEChkPD" name="Donante3" value="OEChkPD" checked>
                                    <label class="form-check-label" for="OEChkPD">Satura Colombia</label>
                                </div>
<!--                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="Radio" id="OEChkSP" name="Donante" value="OEChkSP">
                                    <label class="form-check-label" for="OEChkSP">Otros</label>
                                </div> -->
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong>Tipo de Deshidratados y cantidad de Bolsas Entregadas:</strong>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <input type="checkbox" aria-label=".." id="ChkOEMix" name="ChkOEMix">
                                        <label>Mix de vegetales 1 lb</label>
                                    </span>
                                    <input type="text" class="form-control" id="OEMix" name="OEMix" maxlength="6" readonly>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-addon"> 
                                        <input type="checkbox" aria-label=".." id="ChkOENab" name="ChkOENab">
                                        <label>Mix de vegetales 3 lbs&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                                    </span> 
                                    <input type="text" class="form-control" id="OENab" name="OENab" maxlength="6" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>Fecha de la Entrega:</strong>
                                <input name="fechaOE" type="date" id="fechaOE" maxlength="250" value="<?=date("Y-m-d"); ?>" max='<?=date("Y-m-d"); ?>' class="form-control" required  />
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <strong> Responsable de la Entrega</strong>
                                <input name="ResOE" type="text" id="ResOE" maxlength="250" value="<?=$_SESSION['nombre'];  ; ?>" class="form-control"  readonly/>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">   
                            <div class="form-group">
                                <strong>¿Asiste a una IPG?</strong>
                                <div class="btn-group">
                                    <label class=" " >
                                        <input type="radio" name="options" id="optOESi" autocomplete="off" onclick="ChangeOption(event);"> Si
                                    </label>
                                    <label class="">
                                        <input type="radio" name="options" id="optOENo" autocomplete="off" checked onclick="ChangeOption(event);">No
                                    </label>                                   
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-5">
                            <div class="form-group" id="IPG">
                                <strong> ¿A Cual IPG Asiste?</strong>
                                <input name="IPGAsiste" type="text" id="IPGAsiste" maxlength="250" value="" class="form-control" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="cont-btn cont-flex fl-sbet">
                            <div class="item-btn"></div>
                            <div class="item-btn">
                                <input type="button" name="Btn3aa" onclick="SendData('3aa')" class="next btn btn-success" value="Guardar" />
                            </div>
                        </div> 
                    </div>
                </div>
<!-- Fin otras entregas -->
            </div>      
        </div>
        <input type="hidden" name="MensajeError" id="MensajeError" value="" />
        <input type="hidden" name="SendOpcion" id="SendOpcion" value="" />
    </div>
</div>
<?php
    }
    } // Cierre del if($generacionActual == "SOPA") iniciado en línea 2658
            ?>


        <?php   
        if($generacionActual != "SOPA"){    
        if($generacionActual == "CERO" || $generacionActual == "EVAN" || $generacionActual == "GCEL"){
            ?>
            <div class="form-group">
                <div class="col-sm-5">
                    <strong>Grupo madre / Denominación / Organización / Red de Iglesias de Pequeño Grupo:</strong>
                    <input name="grupoMadre_txt" type="text" id="grupoMadre_txt" maxlength="250" value="<?=$grupoMadre_txt; ?>" class="form-control" required /><input name="generacionNumero" type="hidden" id="generacionNumero" value="<?php if($generacionActual == "EVAN"){ echo "77"; }else if($generacionActual == "GCEL"){echo "8";}else{ echo "0"; } ?>" class="form-control" required />
                </div>
                <div class="col-sm-3">
                    <strong><?php if($generacionActual == "EVAN" || $generacionActual == "CERO" || $generacionActual == "GCEL"){ echo "Fecha del evento:"; }else{ echo "Fecha de inicio:"; } ?></strong>
                    <input name="fechaInicio" type="date" id="fechaInicio" maxlength="250" value="<?=$fechaInicio; ?>" max='<?=date("Y-m-d"); ?>' class="form-control" required />
                </div>
                <div class="col-sm-4">
                    <strong>Nombre grupo/iglesia:</strong>
                    <input name="nombreGrupo_txt" type="text" id="nombreGrupo_txt" maxlength="250" value="<?=$nombreGrupo_txt; ?>" class="form-control"  />
                </div>
            </div>
            <?php                        
        }
        ?>
        <div class="cont-btn cont-flex fl-sbet">
            <div class="item-btn"></div>
            <div class="item-btn">
                <input type="button" name="next" class="next btn btn-success" value="Siguiente" />
            </div>
        </div> 
        <?php                        
        }    
        ?>   
    </fieldset>
    <?php
    if($generacionActual != "SOPA"){ 
    if($generacionActual == "OTRA"){
        
        ?>
        <fieldset>
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">MAPEO DE LA IGLESIA (no se incluya usted en eL reporte)</h3>
                    <h5></h5>
                    <p>A continuación por favor ingrese los datos requeridos</p>
                </div>
                <div class="hr"><hr></div>
            </div>
            <div class="form-group">
                <div class="col-sm-6">
                    <strong>Fecha de mapeo:</strong>
                    <input required name="mapeo_fecha" type="date" id="mapeo_fecha" value="<?=date("Y-m-d"); ?>" max='<?=date("Y-m-d"); ?>' class="form-control" />
                </div>
                <div class="col-sm-6">
                    <strong>Este grupo esta comprometido como iglesia?:</strong>
                    <select required name="mapeo_comprometido" id="mapeo_comprometido" class="form-control">
                        <option value="">Sin seleccionar</option>
                        <option value="3" <?php if($mapeo_comprometido == 3){ ?>selected="selected"<?php } ?>>NO comprometido</option>
                        <option value="4" <?php if($mapeo_comprometido == 4){ ?>selected="selected"<?php } ?>>SI comprometido como iglesia</option>
                    </select>
                </div>
            </div>
            <div class="cont-btn cont-flex fl-sbet">
                <div class="item-btn">
                    <input type="button" name="previous" class="previous btn btn-info" value="Anterior" />
                </div>
                <div class="item-btn">
                    <input type="button" name="next" id="archivo1_sig" class="next btn btn-success" value="Siguiente" />
                </div>
            </div>
        </fieldset>
        <?php
        $array_campos = array(
            "mapeo_oracion",
            "mapeo_companerismo",
            "mapeo_adoracion",
            "mapeo_biblia",
            "mapeo_evangelizar",
            "mapeo_cena",
            "mapeo_dar",
            "mapeo_bautizar",
            "mapeo_trabajadores"
        );
        $array_campos_valor = array(
            $mapeo_oracion,
            $mapeo_companerismo,
            $mapeo_adoracion,
            $mapeo_biblia,
            $mapeo_evangelizar,
            $mapeo_cena,
            $mapeo_dar,
            $mapeo_bautizar,
            $mapeo_trabajadores
        );            
        $array_campos_txt = array(
            "Orar",
            "Compañerismo",
            "Adorar",
            "Aplicar la biblia",
            "Evangelizar",
            "Cena del Señor",
            "Dar",
            "Bautizar",
            "Entrenar nuevos lideres"
        );
        $total_campos = count($array_campos);
        for($i=0; $i<$total_campos;$i++){
            $total_valor += $array_campos_valor[$i];
            ?>
            <fieldset>
                <div class="cont-tit">
                    <div class="hr"><hr></div>
                    <div class="tit-cen">
                        <h3 class="text-center">Método de verificación</h3>
                        <h5><?=$array_campos_txt[$i]; ?></h5>
                        <p>A continuación por favor ingrese los datos requeridos</p>
                    </div>
                    <div class="hr"><hr></div>
                </div>
                <div class="form-group">
                    <div class="col-sm-4"></div>
                    <div class="col-sm-5 cont-flex-2 vl-cent fl-sbet">
                        <div class="cont-flex-2 vl-cent">
                            <img style="margin-right: 15px" width="35px" src="mapeo_img/<?=$array_campos[$i]; ?>1.png" class="img-responsive" />
                            <h5>NO REALIZA LA TAREA</h5>
                        </div>
                        <input style="height: 30px; width: 30px; box-shadow: none;" type="radio" name="<?=$array_campos[$i]; ?>" required value="1" <?php
                    if($array_campos_valor[$i] == 1){
                        ?>checked="checked"<?php
                    }
                    ?> class="form-control"  /></div>
                    <div class="col-sm-3"></div>
                </div>

                <div class="form-group">
                    <div class="col-sm-4"></div>
                    <div class="col-sm-5 cont-flex-2 vl-cent fl-sbet">
                        <div class="cont-flex-2 vl-cent">
                            <img style="margin-right: 15px" width="35px" src="mapeo_img/<?=$array_campos[$i]; ?>2.png" class="img-responsive" />
                            <h5>REALIZA LA TAREA EN COMPAÑIA DEL FACILITADOR</h5>
                        </div>
                        <input style="height: 30px; width: 30px; box-shadow: none;" type="radio" name="<?=$array_campos[$i]; ?>" <?php
                    if($array_campos_valor[$i] == 2){
                        ?>checked="checked"<?php
                    }
                    ?> value="2" class="form-control"  /></div>
                    <div class="col-sm-3"></div>
                </div>

                <div class="form-group">
                    <div class="col-sm-4"></div>
                    <div class="col-sm-5 cont-flex-2 vl-cent fl-sbet">
                        <div class="cont-flex-2 vl-cent">
                            <img style="margin-right: 15px" width="35px" src="mapeo_img/<?=$array_campos[$i]; ?>3.png" class="img-responsive" />
                            <h5>REALIZA LA TAREA PERO ESTE MES NO LO HIZO</h5>
                        </div>
                        <input style="height: 30px; width: 30px; box-shadow: none;" type="radio" name="<?=$array_campos[$i]; ?>" <?php
                    if($array_campos_valor[$i] == 3){
                        ?>checked="checked"<?php
                    }
                    ?> value="3" class="form-control"  /></div>
                    <div class="col-sm-3"></div>
                </div>
                <div class="form-group">
                    <div class="col-sm-4"></div>
                    <div class="col-sm-5 cont-flex-2 vl-cent fl-sbet">
                        <div class="cont-flex-2 vl-cent">
                            <img style="margin-right: 15px" width="35px" src="mapeo_img/<?=$array_campos[$i]; ?>4.png" class="img-responsive" /><h5>REALIZA LA TAREA AUTONOMAMENTE</h5>
                        </div>
                        <input style="height: 30px; width: 30px; box-shadow: none;" type="radio" name="<?=$array_campos[$i]; ?>" <?php
                    if($array_campos_valor[$i] == 4){
                        ?>checked="checked"<?php
                    }
                    ?> value="4" class="form-control"  /></div>
                    <div class="col-sm-3"></div>
                </div>  
                <div class="cont-btn cont-flex fl-sbet">
                    <div class="item-btn">
                        <input type="button" name="previous" class="previous btn btn-info" value="Anterior" />
                    </div>
                    <div class="item-btn">
                        <input type="button" name="next" id="archivo1_sig" class="next btn btn-success" value="Siguiente" />
                    </div>
                </div>         
            </fieldset>            
        <?php } ?>
        <fieldset>
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">Método de verificación</h3>
                    <h5>Bautizos</h5>
                    <p>A continuación por favor ingrese los datos requeridos</p>
                </div>
                <div class="hr"><hr></div>
            </div>
            <div class="form-group col-sm-12">
                <script>
                    $(function(){
                        $("#adicionarAdd").on('click',function(){
                            $("#tablaAdd tbody tr:eq(0)").clone().removeClass('fila-fijaAdd').appendTo("#tablaAdd");
                            var total = 0;
                            $('.subtotal').each(function(){
                                 total = total + Number($(this).val());
                            });
                            $('#total').val(total);
                        });
                        $(document).on("click",".eliminarAdd",function(){
                            var parent = $(this).parents().get(0);
                            $(parent).remove();
                        });

                        $(document).on("click","#archivo1_sig",function(){
                            var total = 0;
                            $('.subtotal').each(function(){
                                 total = total + Number($(this).val());
                            });
                            $('#total').val(total);
                        });
                        
                    });
                    
                </script>
                <table id="tablaAdd">
                    <tr class="fila-fijaAdd">
                        <td class="col-sm-4">
                            <strong>Foto:</strong>
                            <input multiple name="act_bau_img[]" type="file" id="act_bau_img" class="form-control" />
                        </td>
                        <td class="col-sm-3">
                            <strong>Fecha:</strong>
                            <input name="act_bau_fec[]" type="date" id="act_bau_fec" class="form-control" />
                        </td>
                        <td class="col-sm-2">
                            <strong>Cantidad bautizados:</strong>
                            <input name="act_bau_can[]" type="number" id="act_bau_can" min="0" class="subtotal form-control" />
                        </td>
                        <td class="eliminarAdd"><button type="button" class="btn btn-cir-uno usua-col"><i class="fa fa-times"></i></button></td>
                    </tr>
                </table>
            </div>
            <div class="form-group col-sm-12">
                <div class="col-sm-5">
                    <input type="hidden" name="total" id="total">
                </div>
                <div class="col-sm-2">
                    <center>
                        <button id="adicionarAdd" class="btn btn-success" type="button" class="boton"><i class="fas fa-plus"></i>  Adicionar</button>
                    </center>
                </div>
                <div class="col-sm-5"></div>
            </div>
            <div class="cont-btn cont-flex fl-sbet">
                <div class="item-btn">
                    <input type="button" name="previous" class="previous btn btn-info" value="Anterior" />
                </div>
                <div class="item-btn">
                    <input type="button" name="next" id="archivo1_sig" class="next btn btn-success" value="Siguiente" />
                </div>
            </div>
        </fieldset>
        <fieldset>
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">Método de verificación</h3>
                    <h5>Foto del grupo</h5>
                    <p>A continuación por favor ingrese los datos requeridos</p>
                </div>
                <div class="hr"><hr></div>
            </div>
            <div class="form-group">
                <div class="col-sm-3"></div>
                <div class="col-sm-6">
                    <input name="archivo1" type="file" id="archivo1" class="form-control" />
                </div>
                <div class="col-sm-3"></div>
            </div>
            <div class="cont-btn cont-flex fl-sbet">
                <div class="item-btn">
                    <input type="button" name="previous" class="previous btn btn-info" value="Anterior" />
                </div>
                <div class="item-btn">
                    <input type="button" name="next" id="archivo1_sig" class="next btn btn-success" value="Siguiente" />
                </div>
            </div>
        </fieldset>
        <!--<fieldset>
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">Método de verificación 4</h3>
                    <h5>Testimonio</h5>
                    <p>A continuación por favor ingrese los datos requeridos</p>
                </div>
                <div class="hr"><hr></div>
            </div>
            <div class="form-group">
                <div class="col-sm-3"></div>
                <div class="col-sm-6">
                    <strong>Testimonio:</strong>
                    <input name="archivo2" type="file" id="archivo2" class="form-control" />
                </div>
                <div class="col-sm-3"></div>
            </div>
            <div class="form-group">
                <div class="col-sm-3">
                    <center>
                        <input type="button" name="previous" class="previous btn btn-info" value="Anterior" />
                    </center>
                    
                </div>
                <div class="col-sm-6"></div>
                <div class="col-sm-3">
                    <center>
                        <input type="button" name="next" id="archivo1_sig" class="next btn btn-success" value="Siguiente" />
                    </center>
                </div>
            </div>
        </fieldset>
        -->
    <?php }else{
        ?><fieldset>
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">Método de verificación</h3>
                    <h5>Fotográfias</h5>
                    <p>A continuación por favor ingrese los datos requeridos</p>
                </div>
                <div class="hr"><hr></div>
            </div>
            <div class="form-group">
                <div class="col-sm-4">
                    <strong>Foto 1:</strong>
                    <input name="archivo1" type="file" id="archivo1" class="form-control foto-input" />
                </div>
                <div class="col-sm-4" id="div-foto2" style="display:none;">
                    <strong>Foto 2:</strong>
                    <input name="archivo2" type="file" id="archivo2" class="form-control foto-input" />
                </div>
                <div class="col-sm-4" id="div-foto3" style="display:none;">
                    <strong>Foto 3:</strong>
                    <input name="archivo3" type="file" id="archivo3" class="form-control foto-input" />
                </div>
            </div>
            <div class="form-group" id="btn-agregar-foto-container">
                <div class="col-sm-12">
                    <button type="button" id="btn-agregar-foto" class="btn btn-primary">+ Agregar otra foto</button>
                </div>
            </div>

            <div class="cont-btn cont-flex fl-sbet">
                <div class="item-btn">
                    <input type="button" name="previous" class="previous btn btn-info" value="Anterior" />
                </div>
                <div class="item-btn">
                    <input type="button" name="next" id="archivo1_sig" class="next btn btn-success" value="Siguiente" />
                </div>
            </div>
        </fieldset><?php
    }
}    
        
        
$campos = array();
if($generacionActual != "SOPA"){ 
if($generacionActual == "CERO"){
    $campos[] = array("ASISTENCIA: HOMBRES", "asistencia_hom");
    $campos[] = array("ASISTENCIA: MUJERES", "asistencia_muj");
    $campos[] = array("ASISTENCIA: JÓVENES", "asistencia_jov");
    $campos[] = array("ASISTENCIA: NIÑOS", "asistencia_nin");
}else if($generacionActual == "EVAN"){
    $campos[] = array("ALCANZADOS: HOMBRES", "asistencia_hom");
    $campos[] = array("ALCANZADOS: MUJERES", "asistencia_muj");
    $campos[] = array("ALCANZADOS: JÓVENES", "asistencia_jov");
    $campos[] = array("ALCANZADOS: NIÑOS", "asistencia_nin");
    $campos[] = array("DECISIONES DE FÉ", "desiciones");
}else if($generacionActual == "GCEL"){
    $campos[] = array("ASISTENCIA: HOMBRES", "asistencia_hom");
    $campos[] = array("ASISTENCIA: MUJERES", "asistencia_muj");
    $campos[] = array("ASISTENCIA: JÓVENES", "asistencia_jov");
    $campos[] = array("ASISTENCIA: NIÑOS", "asistencia_nin");
}else{
    $campos[] = array("ASISTENCIA: HOMBRES", "asistencia_hom");
    $campos[] = array("ASISTENCIA: MUJERES", "asistencia_muj");
    $campos[] = array("ASISTENCIA: JÓVENES", "asistencia_jov");
    $campos[] = array("ASISTENCIA: NIÑOS", "asistencia_nin");
    
    $campos[] = array("DECISIONES DE FÉ", "desiciones");
    //$campos[] = array("BAUTIZADOS ESTE PERIODO", "bautizadosPeriodo");   
}

foreach($campos as $campo_actual){?>
    <fieldset>
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center">Método de verificación</h3>
                <h5><?=$campo_actual[0]; ?></h5>
                <p>A continuación por favor ingrese los datos requeridos</p>
            </div>
            <div class="hr"><hr></div>
        </div>       
        <div class="form-group">
            <div class="col-sm-3"></div>
            <div class="col-sm-6">
                <strong>Cantidad:</strong>
                <input name="<?=$campo_actual[1]; ?>" type="number" id="<?=$campo_actual[1]; ?>" maxlength="255" value="<?=$_REQUEST[$campo_actual[1]]; ?>" class="form-control" />
            </div>
            <div class="col-sm-3"></div>
        </div>
        <div class="cont-btn cont-flex fl-sbet">
            <div class="item-btn">
                <input type="button" name="previous" class="previous btn btn-info" value="Anterior" />
            </div>
            <div class="item-btn">
                <input type="button" name="next" id="archivo1_sig" class="next btn btn-success" value="Siguiente" />
            </div>
        </div>
    </fieldset>
<?php } 
if ($generacionActual == "GCEL" || $generacionActual == "EVAN") {?>
    <fieldset>
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center">Otros datos del proceso</h3>
                <h5>Comentarios</h5>
                <p>Ingrese comentarios sobre la actividad realizada</p>
            </div>
            <div class="hr"><hr></div>
        </div>
        <div class="form-group">
            <div class="col-sm-3"></div>
            <div class="col-sm-6">
                <textarea name="comentario" id="comentario" style="width: 100%;"></textarea>
            </div>
            <div class="col-sm-3"></div>
        </div>
        <div class="cont-btn cont-flex fl-sbet">
            <div class="item-btn">
                <input type="button" name="previous" class="previous btn btn-info" value="Anterior" />
            </div>
            <div class="item-btn">
                <input type="button" name="next" id="archivo1_sig" class="next btn btn-success" value="Siguiente" />
            </div>
        </div>
    </fieldset>
<?php }?>

    <fieldset>
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center"><?php if($generacionActual == "EVAN"){ echo "ALCANZADOS"; }else{ ?>ASISTENCIA<?php } ?></h3>
                <h5><?php 
                    if($generacionActual == "EVAN"){echo "evangelismo";}
                    else if($generacionActual == "CERO"){echo "generación 0";}
                    else if($generacionActual == "OTRA"){echo "generación 1 al 5";}
                    else if($generacionActual == "GCEL"){echo "gran celebración";}
                    else if($generacionActual == "SOPA"){echo "Entrada y Salida (Inventario) y Entrega de Deshidratados";}
                    ?></h5>
                <p>A continuación se muestra un resumen del evento</p>
            </div>
            <div class="hr"><hr></div>
        </div> 
        <div class="form-group">
            <div class="col-sm-4"></div>
            <label class="control-label col-sm-1" for="final_asistencia_hom">
                <strong>Hombres:</strong>
            </label>
            <div class="col-sm-1">
                <input name="final_asistencia_hom" type="number" id="final_asistencia_hom" value="0" class="form-control" readonly />
            </div>
            
            <label class="control-label col-sm-1" for="final_asistencia_muj">
                <strong>Mujeres:</strong>
            </label>
            <div class="col-sm-1">
                <input name="final_asistencia_muj" type="number" id="final_asistencia_muj" value="0" class="form-control" readonly />
            </div>
            <div class="col-sm-4"></div>
        </div>
        <div class="form-group">
            <div class="col-sm-4"></div>
            <label class="control-label col-sm-1" for="final_asistencia_jov">
                <strong>Jóvenes:</strong>
            </label>
            <div class="col-sm-1">
                <input name="final_asistencia_jov" type="number" id="final_asistencia_jov" value="0" class="form-control" readonly />
            </div>

            <label class="control-label col-sm-1" for="final_asistencia_nin">
                <strong>Niños:</strong>
            </label>
            <div class="col-sm-1">
                <input name="final_asistencia_nin" type="number" id="final_asistencia_nin" value="0" class="form-control" readonly />
            </div>
            <div class="col-sm-4"></div>
        </div>
        <div class="form-group">
            <div class="col-sm-4"></div>
            <label class="control-label col-sm-2" for="final_asistencia_total"><strong><?php if($generacionActual == "EVAN"){ echo "Alcanzados"; }else{ ?>Asistencia<?php } ?> total:</strong></label>
            <div class="col-sm-1"><input name="final_asistencia_total" type="number" id="final_asistencia_total" value="0" class="form-control" readonly /></div>
            <div class="col-sm-5"></div>
        </div>
        <?php
        if($generacionActual == "CERO" || $generacionActual == "EVAN" || $generacionActual == "GCEL"){
            ?>
                <input name="final_bautizados" type="hidden" id="final_bautizados" value="0" class="form-control" readonly />
                <input name="final_discipulado" type="hidden" id="final_discipulado" value="0" class="form-control" readonly />
                <input name="final_desiciones" type="hidden" id="final_desiciones" value="0" class="form-control" readonly />
                <input name="final_preparandose" type="hidden" id="final_preparandose" value="0" class="form-control" readonly />
                <input name="final_bautizadosPeriodo" type="hidden" id="final_bautizadosPeriodo" value="0" class="form-control" readonly />
            <?php
        }
        if($generacionActual == "EVAN" || $generacionActual == "GCEL"){
            ?>
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">OTROS DATOS DEL PROCESO</h3>
                    <h5>COMENTARIOS</h5>
                    <p>A continuación se muestra otros datos</p>
                </div>
                <div class="hr"><hr></div>
            </div>  
            <div class="form-group">
                <div class="col-sm-3"></div>
                    <div class="col-sm-6">
                        <textarea style="width: 100%;" name="final_comentarios" id="final_comentarios" readonly></textarea>
                    </div>
                <div class="col-sm-3"></div>
            </div>
            <?php
        }else if($generacionActual != "CERO"){
            ?>
            <div class="cont-tit">
                <div class="hr"><hr></div>
                <div class="tit-cen">
                    <h3 class="text-center">OTROS DATOS DEL PROCESO</h3>
                    <h5>COMENTARIOS</h5>
                    <p>A continuación se muestra otros datos</p>
                </div>
                <div class="hr"><hr></div>
            </div>  
                <div class="form-group">
                    <?php if(isset($esActividadCoach) && $esActividadCoach){ ?>
                        <input name="final_bautizados" type="hidden" id="final_bautizados" value="0" />
                    <?php }else{ ?>
                    <div class="col-sm-2">
                        <strong>Miembros Bautizados:</strong>
                        <input name="final_bautizados" type="number" id="final_bautizados" value="0" class="form-control" readonly />
                    </div>
                    <?php } ?>
                    <div class="col-sm-2">
                        <strong>En discipulado:</strong>
                        <input name="final_discipulado" type="number" id="final_discipulado" value="0" class="form-control" readonly />
                    </div>
                    <div class="col-sm-3">
                        <strong>Decisiones de Fé:</strong>
                        <input name="final_desiciones" type="number" id="final_desiciones" value="0" class="form-control" readonly />
                    </div>
                    <div class="col-sm-3">
                        <strong>Preparándose para bautismo:</strong>
                        <input name="final_preparandose" type="number" id="final_preparandose" value="0" class="form-control" readonly />
                    </div>
                    <?php if(isset($esActividadCoach) && $esActividadCoach){ ?>
                        <input name="final_bautizadosPeriodo" type="hidden" id="final_bautizadosPeriodo" value="0" />
                    <?php }else{ ?>
                    <div class="col-sm-2">
                        <strong>Bautizados este periodo:</strong>
                        <input name="final_bautizadosPeriodo" type="number" id="final_bautizadosPeriodo" value="0" class="form-control" readonly />
                    </div>
                    <?php } ?>
                </div>
                <?php
        }
        ?>
        <div class="cont-btn cont-flex fl-sbet">
                <div class="item-btn">
                    <input type="button" name="previous" class="previous btn btn-info" value="Anterior" />
                </div>
                <div class="item-btn">
                    <input type="submit" name="button" value="Guardar" class="btn btn-success">
                </div>
            </div>
    </fieldset>

        
    <input type="submit" name="button-hidden" id="button-hidden" style="display:none">
    <input type="hidden" name="funcion" id="funcion" value="<?=$MyFuncion; ?>" />
    <input type="hidden" name="generacion" id="generacion" value="<?=$generacionActual; ?>" />
    <?php

}
?>
    </form>
   

    <script language="javascript">
        jQuery(document).ready(function(){
			jQuery("#EntradaMix").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#EntradaNab").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#SalidaMix").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#SalidaNab").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#EvaMix").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#EvaNab").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#OEMix").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#OENab").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#PEMix").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#PENab").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#PerBeneficiadas").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#telefono").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#PersonasCasa").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#NinosCasa").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#SoySatura").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#AdoCasa").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#Adultos").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});
            jQuery("#Discapacidad").on('input', function (evt) {
				jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			});

            $("#pais").on('change', function () {
                $("#pais option:selected").each(function () {
                    pais=$(this).val();
                    $.post("paises.php", { pais: pais }, function(data){
                        $("#departamento").html(data);
                        $("#ciudad").html("\'<option value='0'>...</option>\'");
                    });			
                });
            });
            $("#departamento").on('change', function () {
                $("#departamento option:selected").each(function () {
                    depto=$(this).val();
                    $.post("paises.php", { depto: depto }, function(data){
                        $("#ciudad").html(data);
                    });			
                });
            });

            $("#salpais").on('change', function () {
                $("#salpais option:selected").each(function () {
                    pais=$(this).val();
                    $.post("paises.php", { pais: pais }, function(data){
                        $("#saldepartamento").html(data);
                        $("#salciudad").html("\'<option value='0'>...</option>\'");
                    });			
                });
            });
            $("#saldepartamento").on('change', function () {
                $("#saldepartamento option:selected").each(function () {
                    depto=$(this).val();
                    $.post("paises.php", { depto: depto }, function(data){
                        $("#salciudad").html(data);
                    });			
                });
            });
            $("#evapais").on('change', function () {
                $("#evapais option:selected").each(function () {
                    pais=$(this).val();
                    $.post("paises.php", { pais: pais }, function(data){
                        $("#evadepartamento").html(data);
                        $("#evaciudad").html("\'<option value='0'>...</option>\'");
                    });			
                });
            });
            $("#evadepartamento").on('change', function () {
                $("#evadepartamento option:selected").each(function () {
                    depto=$(this).val();
                    $.post("paises.php", { depto: depto }, function(data){
                        $("#evaciudad").html(data);
                    });			
                });
            });

            $("#pepais").on('change', function () {
                $("#pepais option:selected").each(function () {
                    pais=$(this).val();
                    $.post("paises.php", { pais: pais }, function(data){
                        $("#pedepartamento").html(data);
                        $("#peciudad").html("\'<option value='0'>...</option>\'");
                    });			
                });
            });
            $("#pedepartamento").on('change', function () {
                $("#pedepartamento option:selected").each(function () {
                    depto=$(this).val();
                    $.post("paises.php", { depto: depto }, function(data){
                        $("#peciudad").html(data);
                    });			
                });
            });

            
		});
        $("#ChkEntradaMix").change(function(){
            if( $('#ChkEntradaMix').prop('checked') ) {
                $("#EntradaMix").removeAttr("readonly");
                $("#EntradaMix").focus();
            }else{
                $('#EntradaMix').val('');
                $("#EntradaMix").attr("readonly","readonly");
            }
        });
        $("#ChkEntradaNab").change(function(){
            if( $('#ChkEntradaNab').prop('checked') ) {
                $("#EntradaNab").removeAttr("readonly");
                $("#EntradaNab").focus();
            }else{
                $('#EntradaNab').val('');
                $("#EntradaNab").attr("readonly","readonly");
            }
        });

        $("#ChkSalidaMix").change(function(){
            if( $('#ChkSalidaMix').prop('checked') ) {
                $("#SalidaMix").removeAttr("readonly");
                $("#SalidaMix").focus();
            }else{
                $('#SalidaMix').val('');
                $("#SalidaMix").attr("readonly","readonly");
            }
        });
        $("#ChkSalidaNab").change(function(){
            if( $('#ChkSalidaNab').prop('checked') ) {
                $("#SalidaNab").removeAttr("readonly");
                $("#SalidaNab").focus();
            }else{
                $('#SalidaNab').val('');
                $("#SalidaNab").attr("readonly","readonly");
            }
        });
        $("#ChkEvaMix").change(function(){
            if( $('#ChkEvaMix').prop('checked') ) {
                $("#EvaMix").removeAttr("readonly");
                $("#EvaMix").focus();
            }else{
                $('#EvaMix').val('');
                $("#EvaMix").attr("readonly","readonly");
            }
        });
        $("#ChkEvaNab").change(function(){
            if( $('#ChkEvaNab').prop('checked') ) {
                $("#EvaNab").removeAttr("readonly");
                $("#EvaNab").focus();
            }else{
                $('#EvaNab').val('');
                $("#EvaNab").attr("readonly","readonly");
            }
        });
        $("#ChkPEMix").change(function(){
            if( $('#ChkPEMix').prop('checked') ) {
                $("#PEMix").removeAttr("readonly");
                $("#PEMix").focus();
            }else{
                $('#PEMix').val('');
                $("#PEMix").attr("readonly","readonly");
            }
        });
        $("#ChkPENab").change(function(){
            if( $('#ChkPENab').prop('checked') ) {
                $("#PENab").removeAttr("readonly");
                $("#PENab").focus();
            }else{
                $('#PENab').val('');
                $("#PENab").attr("readonly","readonly");
            }
        });
        $("#ChkOEMix").change(function(){
            if( $('#ChkOEMix').prop('checked') ) {
                $("#OEMix").removeAttr("readonly");
                $("#OEMix").focus();
            }else{
                $('#OEMix').val('');
                $("#OEMix").attr("readonly","readonly");
            }
        });
        $("#ChkOENab").change(function(){
            if( $('#ChkOENab').prop('checked') ) {
                $("#OENab").removeAttr("readonly");
                $("#OENab").focus();
            }else{
                $('#OENab').val('');
                $("#OENab").attr("readonly","readonly");
            }
        });      


        
        var current = 1,current_step,next_step,steps;
        $('#IPG').attr('hidden', true );
        function SendData(Opc){
            // Ensure the hidden field is updated with the correct ID before submitting.
            var displayInput = document.getElementById('SelBeneficiarioDisplay');
            var hiddenInput = document.getElementById('SelBeneficiario');
            var datalist = document.getElementById('beneficiariosList');
            var options = datalist.options;
            hiddenInput.value = ''; // Clear it first to be safe.
            for (var i = 0; i < options.length; i++) {
                if (options[i].value === displayInput.value) {
                    hiddenInput.value = options[i].getAttribute('data-id');
                    break;
                }
            }

            var Mensaje = "";
            if (Opc == "1a"){
                if(!$('#EntChkPD').is(':checked') && !$('#EntChkSP').is(':checked') ){
                    Mensaje += "Debe seleccionar un donante\n"
                }
                if($('#EntradaMix').val().length < 1  && $('#EntradaNab').val().length < 1){
                    Mensaje += "Debe ingresar cantidad y tipo de Deshidratados\n"
                }
                if($('#EntradaMix').val().length >= 1 && ($('#EntradaMix').val() == 0 )){
                    Mensaje += "Debe ingresar cantidad mayor a 0 para Mix de Vegetales\n"
                }
                if($('#EntradaNab').val().length >= 1 && ($('#EntradaNab').val() == 0 )){
                    Mensaje += "Debe ingresar cantidad mayor a 0 para Nabo Suizo\n"
                }
                if($('#pais').val() < 1 ){
                    Mensaje += "Debe seleccionar un pais\n"
                }
                if($('#departamento').val() < 1){
                    Mensaje += "Debe seleccionar un departamento\n"
                }
            }
            if (Opc == "2a"){
                if(!$('#salDon1').is(':checked') && !$('#salDon2').is(':checked') ){
                    Mensaje += "Debe seleccionar un donante\n"
                }
                if($('#SalidaMix').val().length < 1  && $('#SalidaNab').val().length < 1){
                    Mensaje += "Debe ingresar cantidad y tipo de Deshidratados\n"
                }
                if($('#SalidaMix').val().length >= 1 && ($('#SalidaMix').val() == 0 )){
                    Mensaje += "Debe ingresar cantidad mayor a 0 para Mix de Vegetales\n"
                }
                if($('#SalidaNab').val().length >= 1 && ($('#SalidaNab').val() == 0 )){
                    Mensaje += "Debe ingresar cantidad mayor a 0 para Nabo Suizo\n"
                }
                if($('#salpais').val() < 1 ){
                    Mensaje += "Debe seleccionar un pais\n"
                }
                if($('#saldepartamento').val() < 1){
                    Mensaje += "Debe seleccionar un departamento\n"
                }
            }
            if (Mensaje.length >0){
alert(Mensaje);
            }
            $('#MensajeError').val(Mensaje);
            $('#SendOpcion').val(Opc);
            $('#form1').submit();
        }

        function ChangeOption(event){
            if ($('#optOESi').is(":checked")){             
                $('#IPG').attr('hidden', false);              
            }else if ($('#optOENo').is(":checked")){             
                $('#IPG').attr('hidden', true );
            }


            if ($('#optEva').is(":checked")){             
                $('#Eva').attr('hidden', false);
                $('#Ing').attr('hidden', true);
                $('#Seg').attr('hidden', true);
            }else if ($('#optIng').is(":checked")){             
                $('#Eva').attr('hidden', true );
                $('#Ing').attr('hidden', false);
                $('#Seg').attr('hidden', true);
            }else if ($('#optSeg').is(":checked")){             
                $('#Eva').attr('hidden', true );
                $('#Ing').attr('hidden', true);
                $('#Seg').attr('hidden', false);
            }
        }
       
        var mapeoLabels = <?=json_encode($campos_mapeo_labels); ?>;

        function limpiarErrorFormulario(){
            var errorBox = document.getElementById('form-inline-error');
            if(errorBox){
                errorBox.style.display = 'none';
                errorBox.textContent = '';
            }
        }

        function mostrarErrorFormulario(mensaje, selectorObjetivo){
            var errorBox = document.getElementById('form-inline-error');
            if(errorBox){
                errorBox.textContent = mensaje;
                errorBox.style.display = 'block';
            }

            var objetivo = errorBox;
            if(selectorObjetivo){
                objetivo = document.querySelector(selectorObjetivo) || document.getElementById(selectorObjetivo) || errorBox;
            }

            if(objetivo && typeof objetivo.scrollIntoView === 'function'){
                objetivo.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function obtenerMapeosFaltantes(){
            var faltantes = [];
            for(var campo in mapeoLabels){
                if(!Object.prototype.hasOwnProperty.call(mapeoLabels, campo)){
                    continue;
                }

                var radioButtons = document.getElementsByName(campo);
                var seleccionado = false;
                for(var j = 0; j < radioButtons.length; j++) {
                    if(radioButtons[j].checked) {
                        seleccionado = true;
                        break;
                    }
                }

                if(!seleccionado){
                    faltantes.push({
                        campo: campo,
                        etiqueta: mapeoLabels[campo]
                    });
                }
            }

            return faltantes;
        }

        //
        function generarForm(){
            //Completo el formulario  
            if(current == steps){
                limpiarErrorFormulario();
                <?php if($esActividadBautizo){ ?>
                var asistenciaTotalBautizo = parseInt(document.getElementById("final_asistencia_total").value) || 0;
                var totalBautizadosBautizo = parseInt(document.getElementById("final_bautizados").value) || 0;
                if(asistenciaTotalBautizo < 1){
                    mostrarErrorFormulario("La asistencia total no puede ser menor a 1 persona", "#final_asistencia_total");
                    return false;
                }
                if(totalBautizadosBautizo > asistenciaTotalBautizo){
                    mostrarErrorFormulario("Bautizados no puede ser mayor a la asistencia total", "#final_bautizados");
                    return false;
                }
                <?php } ?>
                <?php
                 if($generacionActual != "SOPA"){
                if($generacionActual != "CERO" && $generacionActual != "EVAN" && $generacionActual != "GCEL"){
                    ?>
                    var mapeosFaltantes = obtenerMapeosFaltantes();
                    if(mapeosFaltantes.length > 0){
                        mostrarErrorFormulario("Debe completar todos los campos de mapeo del formulario de coach. Faltan: " + mapeosFaltantes.map(function(item){ return item.etiqueta; }).join(", ") + ".", 'input[name=\"' + mapeosFaltantes[0].campo + '\"]');
                        return false;
                    }
                    var checks_total_seleccionados;
                    checks_total_seleccionados = 0;
                    <?php
                    for($i=0; $i<$total_campos;$i++){
                        ?>
                        //
                        var radios<?=$i; ?> = document.getElementsByName("<?=$array_campos[$i]; ?>");
                        for(var i = 0, len = radios<?=$i; ?>.length; i < len; i++) {
                              if (radios<?=$i; ?>[i].checked) {
                                  checks_total_seleccionados++;
                              }
                         }
                         <?php
                    }
                    ?>

                    if(parseInt(checks_total_seleccionados) < 9){
                        mostrarErrorFormulario("Debe completar todos los campos de mapeo del formulario de coach.", "#mapeo_fecha");
                        return false;
                    }
                    
                    if(parseInt(document.getElementById("final_asistencia_total").value) < 1){
                        mostrarErrorFormulario("La asistencia total no puede ser menor a 1 persona", "#final_asistencia_total");
                        return false;
                    }
                    
                    var e = document.getElementById("mapeo_comprometido");
                    var value = e.options[e.selectedIndex].value;
                    

                    if(document.getElementById("mapeo_fecha").value != "" && value != "" && document.getElementById("nombreGrupo_txt").value != ""){
                        if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?"))
                        {
                            $(':input[type="submit"]').prop('disabled', true);
                            document.getElementById('funcion').value = "<?=$temp_accionForm; ?>";
                        }else{
                            return false;
                        }
                        return true;
                    }
                    else{
                        mostrarErrorFormulario("Por favor verifique la informacion del mapeo antes de guardar.", "#mapeo_fecha");
                        return false;
                    }
                    <?php
                }
                else{
                    ?>
                    if(confirm("Esta accion guardara los cambios en el sistema, ¿esta seguro que desea continuar?"))
                    {
                        $(':input[type="submit"]').prop('disabled', true);
                        document.getElementById('funcion').value = "<?=$temp_accionForm; ?>";
                    }else{
                        return false;
                    }
                    return true;
                    <?php
                }
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
                //$(this).closest("fieldset")
                //next_step = $(this).parent().next();
                
                current_step = $(this).closest("fieldset");
                limpiarErrorFormulario();
                
                <?php if($generacionActual == "OTRA"): ?>
                // Validación específica para fieldsets de mapeos (solo para generaciones 1-5)
                
                // MODO 1: Fieldset con todos los mapeos (modo compacto)
                if (current_step.find('input[name="mapeo_oracion"]').length > 0 && current_step.find('input[name="mapeo_companerismo"]').length > 0) {
                    var mapeosCampos = [
                        'mapeo_oracion', 'mapeo_companerismo', 'mapeo_adoracion', 
                        'mapeo_biblia', 'mapeo_evangelizar', 'mapeo_cena', 
                        'mapeo_dar', 'mapeo_bautizar', 'mapeo_trabajadores'
                    ];
                    
                    var mapeosCompletos = 0;
                    var camposFaltantes = [];
                    
                    for (var i = 0; i < mapeosCampos.length; i++) {
                        var radioButtons = document.getElementsByName(mapeosCampos[i]);
                        var seleccionado = false;
                        for (var j = 0; j < radioButtons.length; j++) {
                            if (radioButtons[j].checked) {
                                seleccionado = true;
                                break;
                            }
                        }
                        if (seleccionado) {
                            mapeosCompletos++;
                        } else {
                            var nombreCampo = mapeosCampos[i].replace('mapeo_', '').replace('_', ' ');
                            camposFaltantes.push(nombreCampo);
                        }
                    }
                    
                    if (mapeosCompletos < 9) {
                        var primerCampoFaltante = obtenerMapeosFaltantes();
                        mostrarErrorFormulario(
                            "Debe completar el diagnostico de mapeo para todos los 9 elementos antes de continuar. Faltan: " + camposFaltantes.join(", ") + ".",
                            (primerCampoFaltante.length > 0) ? 'input[name=\"' + primerCampoFaltante[0].campo + '\"]' : '#form-inline-error'
                        );
                        return false;
                    }
                }
                // MODO 2: Fieldset individual por cada mapeo
                else {
                    var mapeosCampos = [
                        'mapeo_oracion', 'mapeo_companerismo', 'mapeo_adoracion', 
                        'mapeo_biblia', 'mapeo_evangelizar', 'mapeo_cena', 
                        'mapeo_dar', 'mapeo_bautizar', 'mapeo_trabajadores'
                    ];
                    
                    // Verificar si el fieldset actual contiene algún campo de mapeo individual
                    for (var i = 0; i < mapeosCampos.length; i++) {
                        if (current_step.find('input[name="' + mapeosCampos[i] + '"]').length > 0) {
                            // Este fieldset contiene un mapeo, verificar si está seleccionado
                            var radioButtons = current_step.find('input[name="' + mapeosCampos[i] + '"]');
                            var seleccionado = false;
                            
                            for (var j = 0; j < radioButtons.length; j++) {
                                if (radioButtons[j].checked) {
                                    seleccionado = true;
                                    break;
                                }
                            }
                            
                            if (!seleccionado) {
                                var nombreCampo = mapeoLabels[mapeosCampos[i]] || mapeosCampos[i];
                                mostrarErrorFormulario("Debe seleccionar una opcion para: " + nombreCampo + ".", 'input[name=\"' + mapeosCampos[i] + '\"]');
                                return false;
                            }
                            break; // Solo validar el campo de este fieldset
                        }
                    }
                }
                <?php endif; ?>
                
                // Validación HTML5 estándar para otros campos (sin radio buttons problemáticos)
                var formValid = true;
                current_step.find('input, select, textarea').not('input[type="radio"]').each(function() {
                    if ($(this).prop('required') && !$(this)[0].checkValidity()) {
                        formValid = false;
                        $(this)[0].reportValidity();
                        return false;
                    }
                });
                
                if (!formValid) {
                    return false;
                }
                
                next_step = current_step.next();
                next_step.show();
                current_step.hide();
                setProgressBar(++current);
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
                
                sumar();
            }
            
            function sumar(){
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
                //
                if(document.getElementById("asistencia_jov").value != ""){
                    var asistencia_jov = document.getElementById("asistencia_jov").value;
                }
                if(document.getElementById("asistencia_nin").value != ""){
                    var asistencia_nin = document.getElementById("asistencia_nin").value;
                }
                
                
                <?php
                if($generacionActual == "CERO" || $generacionActual == "GCEL"){
                    ?>               
                    var bautizados = 0;
                    var bautizadosPeriodo = 0;
                    var desiciones = 0;
                    <?php
                    if ($generacionActual == "GCEL") {?>
                        document.getElementById("final_comentarios").value = document.getElementById("comentario").value;
                    <?php
                    }
                }else if($generacionActual == "EVAN"){
                    ?>
                    var bautizados = 0;
                    var bautizadosPeriodo = 0;
                    if(document.getElementById("desiciones").value != ""){
                        var desiciones = document.getElementById("desiciones").value;
                    }
                    document.getElementById("final_comentarios").value = document.getElementById("comentario").value;
                    <?php
                }else{
                    ?>
                    if(document.getElementById("desiciones").value != ""){
                        var desiciones = document.getElementById("desiciones").value;
                    }

                    var bautizados = 0;
                    var bautizadosPeriodo = 0;
                    if(document.getElementById("total").value != ""){
                        var bautizados = document.getElementById("total").value;
                    }
                    if(document.getElementById("total").value != ""){
                        var bautizadosPeriodo = document.getElementById("total").value;
                    }
                    <?php
                }
                ?>
                var var_suma = parseInt(asistencia_hom) + parseInt(asistencia_muj) + parseInt(asistencia_jov) + parseInt(asistencia_nin);
                document.getElementById("final_asistencia_total").value = parseInt(var_suma);
                //
                

                document.getElementById("final_asistencia_hom").value = parseInt(asistencia_hom);
                document.getElementById("final_asistencia_muj").value = parseInt(asistencia_muj);
                document.getElementById("final_asistencia_jov").value = parseInt(asistencia_jov);
                document.getElementById("final_asistencia_nin").value = parseInt(asistencia_nin);
                
                
                
                <?php if($generacionActual == "EVAN"){ ?>
                    document.getElementById("final_bautizados").value = 0;
                    document.getElementById("final_discipulado").value = 0;
                    document.getElementById("final_bautizadosPeriodo").value = 0;
                    document.getElementById("final_desiciones").value = parseInt(desiciones);
                    document.getElementById("final_preparandose").value = 0;
                <?php }else if($generacionActual == "GCEL" || $generacionActual == "CERO"){ ?>
                    document.getElementById("final_bautizados").value = 0;
                    document.getElementById("final_discipulado").value = 0;
                    document.getElementById("final_bautizadosPeriodo").value = 0;
                    document.getElementById("final_desiciones").value = 0;
                    document.getElementById("final_preparandose").value = 0;
                <?php }else{ ?>
                    document.getElementById("final_bautizados").value = parseInt(bautizados);
                    document.getElementById("final_discipulado").value = parseInt(var_suma);
                    document.getElementById("final_bautizadosPeriodo").value = parseInt(bautizadosPeriodo);
                    document.getElementById("final_desiciones").value = parseInt(desiciones);
                    document.getElementById("final_preparandose").value = parseInt(var_suma) - parseInt(bautizadosPeriodo);
                <?php } ?>
            }
            
            <?php
            if($varExitoREP == 1)
            {
                ?>alert("Se ha colocado correctamente el ACCESO, espere mientras es dirigido.");
                window.location.href = "index.php?doc=admin_usu4&id=<?=$ultimoId;?>";<?php
            }
            ?>
        }
        
        // Función para obtener plantadores automáticamente cuando se selecciona grupo madre
        function obtenerPlantador() {
            var grupoMadre = document.getElementById('grupoMadre_txt').value;
            var generacionSeleccionada = 1; // Default
            
            // Obtener la generación seleccionada
            var radios = document.getElementsByName('generacionNumero');
            for(var i = 0; i < radios.length; i++) {
                if(radios[i].checked) {
                    generacionSeleccionada = parseInt(radios[i].value);
                    break;
                }
            }
            
            if(grupoMadre === '') {
                document.getElementById('plantador').value = '';
                // Limpiar datalist
                document.getElementById('plantadores-list').innerHTML = '';
                return;
            }
            
            // Crear solicitud AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'obtener_plantador.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log('Response received:', xhr.responseText);
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if(response.success) {
                            // Llenar el datalist con las opciones
                            var datalist = document.getElementById('plantadores-list');
                            datalist.innerHTML = '';
                            
                            if(response.plantadores && response.plantadores.length > 0) {
                                response.plantadores.forEach(function(plantador) {
                                    var option = document.createElement('option');
                                    option.value = plantador;
                                    datalist.appendChild(option);
                                });
                            }
                            
                            // Establecer el valor sugerido
                            if(response.sugerido) {
                                document.getElementById('plantador').value = response.sugerido;
                            }
                            
                            console.log('Found plantadores:', response.plantadores);
                            console.log('Suggested:', response.sugerido);
                            if(response.debug) console.log('Debug:', response.debug);
                        } else {
                            console.error('Error:', response.error);
                            if(response.debug) console.error('Debug:', response.debug);
                        }
                    } catch(e) {
                        console.error('Error parsing JSON:', e);
                        console.error('Raw response:', xhr.responseText);
                    }
                } else if (xhr.readyState === 4) {
                    console.error('HTTP Error:', xhr.status, xhr.statusText);
                }
            };
            
            xhr.send('grupoMadre_txt=' + encodeURIComponent(grupoMadre) + '&generacion=' + generacionSeleccionada);
        }
        
        // Función para actualizar grupos cuando cambia la generación
        function onGeneracionChange() {
            // Cargar los grupos del facilitador para la nueva generación
            cargarGruposFacilitador();
        }

        // Función para cargar grupos del facilitador según la generación seleccionada
        function cargarGruposFacilitador() {
            var generacionSeleccionada = 1; // Default

            // Obtener la generación seleccionada
            var radios = document.getElementsByName('generacionNumero');
            for(var i = 0; i < radios.length; i++) {
                if(radios[i].checked) {
                    generacionSeleccionada = parseInt(radios[i].value);
                    break;
                }
            }

            console.log('Cargando grupos para generación:', generacionSeleccionada);

            // Crear solicitud AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'obtener_grupos_facilitador.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log('Response received (grupos facilitador):', xhr.responseText);
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if(response.success) {
                            // Llenar el datalist con las opciones
                            var datalist = document.getElementById('grupos-facilitador-list');
                            datalist.innerHTML = '';

                            // Guardar grupos en variable global para acceso posterior
                            window.gruposFacilitador = {};

                            if(response.grupos && response.grupos.length > 0) {
                                response.grupos.forEach(function(grupo) {
                                    var option = document.createElement('option');
                                    option.value = grupo.displayLabel;
                                    datalist.appendChild(option);

                                    // Guardar datos del grupo para posterior uso
                                    window.gruposFacilitador[grupo.displayLabel] = grupo;
                                });

                                console.log('Grupos cargados:', response.grupos.length);
                            } else {
                                console.log('No hay grupos con reportes en generación anterior');
                            }

                            if(response.debug) console.log('Debug:', response.debug);
                        } else {
                            console.error('Error:', response.error);
                            if(response.debug) console.error('Debug:', response.debug);
                        }
                    } catch(e) {
                        console.error('Error parsing JSON:', e);
                        console.error('Raw response:', xhr.responseText);
                    }
                } else if (xhr.readyState === 4) {
                    console.error('HTTP Error:', xhr.status, xhr.statusText);
                }
            };

            xhr.send('generacion=' + generacionSeleccionada);
        }

        // Función que se ejecuta cuando el usuario selecciona un grupo
        function onGrupoSeleccionado() {
            var grupoSeleccionado = document.getElementById('nombreGrupo_txt').value;

            console.log('Grupo seleccionado:', grupoSeleccionado);

            // Buscar el grupo en los datos cargados
            if(window.gruposFacilitador && window.gruposFacilitador[grupoSeleccionado]) {
                var grupo = window.gruposFacilitador[grupoSeleccionado];

                // Autocompletar campos
                document.getElementById('grupoMadre_txt').value = grupo.grupoMadre || '';
                document.getElementById('plantador').value = grupo.plantador || '';
                document.getElementById('barrio').value = grupo.barrio || '';
                document.getElementById('ciudad').value = grupo.ciudad || '';
                document.getElementById('direccion').value = grupo.direccion || '';

                console.log('Datos autocompletados:', grupo);
            } else {
                // Si el usuario escribe un nombre nuevo, limpiar campos
                console.log('Grupo no encontrado en lista, limpiando campos auxiliares');
                document.getElementById('grupoMadre_txt').value = '';
                document.getElementById('plantador').value = '';
                document.getElementById('barrio').value = '';
                document.getElementById('ciudad').value = '';
                document.getElementById('direccion').value = '';
            }
        }
        
        // Función para obtener nombres de grupos automáticamente cuando se selecciona grupo madre
        function obtenerNombresGrupos() {
            var grupoMadre = document.getElementById('grupoMadre_txt').value.trim();
            var generacionSeleccionada = 1; // Default
            
            console.log('obtenerNombresGrupos called with grupo:', grupoMadre);
            
            // Obtener la generación seleccionada
            var radios = document.getElementsByName('generacionNumero');
            for(var i = 0; i < radios.length; i++) {
                if(radios[i].checked) {
                    generacionSeleccionada = parseInt(radios[i].value);
                    break;
                }
            }
            
            console.log('Generacion seleccionada:', generacionSeleccionada);
            
            if(grupoMadre === '') {
                document.getElementById('nombreGrupo_txt').value = '';
                // Limpiar datalist
                document.getElementById('nombres-grupos-list').innerHTML = '';
                console.log('Grupo madre vacío, limpiando datalist');
                return;
            }
            
            // Crear solicitud AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'obtener_nombres_grupos.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log('Response received (nombres grupos):', xhr.responseText);
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if(response.success) {
                            // Llenar el datalist con las opciones
                            var datalist = document.getElementById('nombres-grupos-list');
                            datalist.innerHTML = ''; // Limpiar primero
                            
                            console.log('Limpiando datalist y agregando nuevos nombres...');
                            
                            if(response.nombresGrupos && response.nombresGrupos.length > 0) {
                                response.nombresGrupos.forEach(function(nombreGrupo) {
                                    var option = document.createElement('option');
                                    option.value = nombreGrupo;
                                    datalist.appendChild(option);
                                    console.log('Added option:', nombreGrupo);
                                });
                            } else {
                                console.log('No nombres grupos found for this grupo madre');
                            }
                            
                            // Limpiar el campo nombreGrupo_txt cuando cambia el grupo madre
                            document.getElementById('nombreGrupo_txt').value = '';
                            
                            // Establecer el valor sugerido si existe
                            if(response.sugerido) {
                                document.getElementById('nombreGrupo_txt').value = response.sugerido;
                                console.log('Set suggested value:', response.sugerido);
                            }
                            
                            console.log('Found nombres grupos:', response.nombresGrupos);
                            console.log('Suggested:', response.sugerido);
                            if(response.debug) console.log('Debug:', response.debug);
                            
                            // DEBUG DETALLADO
                            if(response.debugLog && response.debugLog.length > 0) {
                                console.log('=== DEBUG DETALLADO OBTENER_NOMBRES_GRUPOS ===');
                                response.debugLog.forEach(function(logLine) {
                                    console.log(logLine);
                                });
                                console.log('=== FIN DEBUG DETALLADO ===');
                            }
                        } else {
                            console.error('Error:', response.error);
                            if(response.debug) console.error('Debug:', response.debug);
                        }
                    } catch(e) {
                        console.error('Error parsing JSON:', e);
                        console.error('Raw response:', xhr.responseText);
                    }
                } else if (xhr.readyState === 4) {
                    console.error('HTTP Error:', xhr.status, xhr.statusText);
                }
            };
            
            xhr.send('grupoMadre_txt=' + encodeURIComponent(grupoMadre) + '&generacion=' + generacionSeleccionada);
        }
        
        // Función para obtener ubicación automáticamente cuando se selecciona un nombre de grupo
        function obtenerUbicacionGrupo() {
            var nombreGrupo = document.getElementById('nombreGrupo_txt').value;
            var grupoMadre = document.getElementById('grupoMadre_txt').value;
            
            if(nombreGrupo === '') {
                // Si no hay nombre de grupo, limpiar campos de ubicación
                document.getElementById('barrio').value = '';
                document.getElementById('direccion').value = '';
                document.getElementById('ciudad').value = '';
                return;
            }
            
            // Crear solicitud AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'obtener_ubicacion_grupo.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log('Response received (ubicacion):', xhr.responseText);
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if(response.success && response.ubicacion) {
                            var ubicacion = response.ubicacion;
                            
                            // Sobrescribir siempre los datos de ubicación cuando se encuentren
                            if(ubicacion.found) {
                                document.getElementById('barrio').value = ubicacion.barrio || '';
                                document.getElementById('direccion').value = ubicacion.direccion || '';
                                document.getElementById('ciudad').value = ubicacion.ciudad || '';
                            }
                            
                            console.log('Found ubicacion:', ubicacion);
                            console.log('Source:', ubicacion.source);
                            if(response.debug) console.log('Debug:', response.debug);
                        } else {
                            console.log('No ubicacion data found');
                            if(response.debug) console.log('Debug:', response.debug);
                        }
                    } catch(e) {
                        console.error('Error parsing JSON (ubicacion):', e);
                        console.error('Raw response:', xhr.responseText);
                    }
                } else if (xhr.readyState === 4) {
                    console.error('HTTP Error (ubicacion):', xhr.status, xhr.statusText);
                }
            };
            
            xhr.send('nombreGrupo_txt=' + encodeURIComponent(nombreGrupo) + '&grupoMadre_txt=' + encodeURIComponent(grupoMadre));
        }

        window.onload = function(){
            init();

            // Cargar grupos del facilitador al iniciar (para generaciones 1-5)
            <?php if($generacionActual == "OTRA"): ?>
            cargarGruposFacilitador();
            <?php endif; ?>
        }

        // Funciones para filtros de fecha del inventario
        function aplicarFiltrosFecha() {
            var fechaInicio = document.getElementById('fecha_inicio_inventario').value;
            var fechaFin = document.getElementById('fecha_fin_inventario').value;

            // Construir URL con parámetros GET
            var url = window.location.href.split('?')[0] + '?doc=reportar&generacion=SOPA';

            if (fechaInicio) {
                url += '&fecha_inicio=' + encodeURIComponent(fechaInicio);
            }
            if (fechaFin) {
                url += '&fecha_fin=' + encodeURIComponent(fechaFin);
            }

            // Redirigir con los parámetros
            window.location.href = url;
        }

        function limpiarFiltrosFecha() {
            // Redirigir sin parámetros de fecha
            window.location.href = window.location.href.split('?')[0] + '?doc=reportar&generacion=SOPA';
        }

        // Función auxiliar para formatear fecha a YYYY-MM-DD
        function formatearFecha(fecha) {
            var year = fecha.getFullYear();
            var month = String(fecha.getMonth() + 1).padStart(2, '0');
            var day = String(fecha.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        }

        // Filtro rápido: Esta semana (desde el lunes hasta hoy)
        function filtroRapidoSemana() {
            var hoy = new Date();
            var diaSemana = hoy.getDay(); // 0 = domingo, 1 = lunes, ...

            // Calcular el lunes de esta semana
            var lunes = new Date(hoy);
            var diasDesdeElLunes = diaSemana === 0 ? 6 : diaSemana - 1; // Si es domingo, retroceder 6 días
            lunes.setDate(hoy.getDate() - diasDesdeElLunes);

            document.getElementById('fecha_inicio_inventario').value = formatearFecha(lunes);
            document.getElementById('fecha_fin_inventario').value = formatearFecha(hoy);
            aplicarFiltrosFecha();
        }

        // Filtro rápido: Este mes (desde el día 1 del mes hasta hoy)
        function filtroRapidoMes() {
            var hoy = new Date();
            var primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

            document.getElementById('fecha_inicio_inventario').value = formatearFecha(primerDiaMes);
            document.getElementById('fecha_fin_inventario').value = formatearFecha(hoy);
            aplicarFiltrosFecha();
        }

        // Filtro rápido: Trimestre actual (desde el día 1 del trimestre hasta hoy)
        function filtroRapidoTrimestre() {
            var hoy = new Date();
            var mesActual = hoy.getMonth(); // 0-11

            // Calcular el primer mes del trimestre
            var primerMesTrimestre = Math.floor(mesActual / 3) * 3;
            var primerDiaTrimestre = new Date(hoy.getFullYear(), primerMesTrimestre, 1);

            document.getElementById('fecha_inicio_inventario').value = formatearFecha(primerDiaTrimestre);
            document.getElementById('fecha_fin_inventario').value = formatearFecha(hoy);
            aplicarFiltrosFecha();
        }

        // Filtro rápido: Año actual (desde el 1 de enero del año en curso hasta hoy)
        function filtroRapidoAnio() {
            var hoy = new Date();
            var primerDiaAnio = new Date(hoy.getFullYear(), 0, 1);

            document.getElementById('fecha_inicio_inventario').value = formatearFecha(primerDiaAnio);
            document.getElementById('fecha_fin_inventario').value = formatearFecha(hoy);
            aplicarFiltrosFecha();
        }
        </script><?php
    }
}   //FIN DEL IF DE REDIRIGIR SI YA INSERTO EL REGISTRO
else{
    echo "No deberia estar aquí.";
}
?>
