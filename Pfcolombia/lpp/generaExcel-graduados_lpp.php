<?php 
session_start();
//session_register("SESSION");
include_once('funciones.php');

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
///use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;


if(!isset($_REQUEST["fechaInicial"]) || eliminarInvalidos($_REQUEST["fechaInicial"]) == ""){
    $_REQUEST["fechaInicial"] = date("2021-02-01");
}
if(!isset($_REQUEST["fechaFinal"]) || eliminarInvalidos($_REQUEST["fechaFinal"]) == ""){
    $siguiente_anho = date("Y", strtotime("+1 year"));
    //$_REQUEST["fechaFinal"] = $siguiente_anho."-01-31";
    $_REQUEST["fechaFinal"] = date("Y-m-d");
}
$tip_reporte = $_REQUEST['rep_tip'];  
if (!empty($_REQUEST['rep_ani'])) {
    $anio = $_REQUEST['rep_ani'];
}else{
    $anio = date('Y');
}
if (!empty($_REQUEST['rep_qua'])) {
    $q = $_REQUEST['rep_qua'];
    $iniQ = $anio.'-'.$q.'-01';
    $iniQ = date("Y-m-d", strtotime($iniQ));
    if ($_REQUEST['rep_qua']==1) {
        $finQ = $anio.'-'.($q+2).'-31';
    }else if ($_REQUEST['rep_qua']==10) {
        $finQ = $anio.'-'.($q+2).'-31';
    }else{
        $finQ = $anio.'-'.($q+2).'-30';
    }
    $finQ = date("Y-m-d", strtotime($finQ));
}else{
   $iniQ = $_REQUEST["fechaInicial"];
   $finQ = $_REQUEST["fechaFinal"];
}
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;
$sqlFiltro = "";

//  YA GENERACION 0 NO CUENTA
/*$sqlFiltro .= " AND SR.generacionNumero != 0";*/

if($_SESSION["perfil"] == 163){
    $_REQUEST["idUsuario"] = $_SESSION["id"];
}
//    
$empresa_paisid_txt = "Confraternidad Carcelaria de Colombia";
if(isset($_REQUEST["empresa_paisid"]) && soloNumeros($_REQUEST["empresa_paisid"]) != "" && soloNumeros($_REQUEST["empresa_paisid"]) != "0"){
    $empresa_paisid = soloNumeros($_REQUEST["empresa_paisid"]);
    $sqlFiltro .= " AND UE.empresa_paisid = '".$empresa_paisid."'";
    
    /*
    *   TRAEMOS LOS TIPOS DE CLIENTE/EMPRESA (15)
    */
    $sql = "SELECT * ";
    $sql.=" FROM categorias ";
    $sql.=" WHERE idSec = 37 ORDER BY descripcion asc";
    $PSN2->query($sql);
    $numero=$PSN2->num_rows();
    if($numero > 0)
    {
        while($PSN2->next_record())
        {
            $empresa_paisid_txt = "".$PSN2->f('descripcion');
        }
    }        
    
}


if(isset($_REQUEST["idUsuario"]) && soloNumeros($_REQUEST["idUsuario"]) != "" && soloNumeros($_REQUEST["idUsuario"]) != "0"){
    $buscar_idUsuario = soloNumeros($_REQUEST["idUsuario"]);
    $sqlFiltro .= " AND SR.idUsuario = '".$buscar_idUsuario."'";
}


//
if(isset($_REQUEST["empresa_pd"]) && soloNumeros($_REQUEST["empresa_pd"]) != "" && soloNumeros($_REQUEST["empresa_pd"]) != "0"){
    $buscar_empresa_pd = soloNumeros($_REQUEST["empresa_pd"]);
    $sqlFiltro .= " AND UE.empresa_pd = '".$buscar_empresa_pd."'";
}

//
if(isset($_REQUEST["nombre"]) && eliminarInvalidos($_REQUEST["nombre"]) != ""){
    $buscar_nombre = eliminarInvalidos($_REQUEST["nombre"]);
    $sqlFiltro .= " AND SR.plantador LIKE '%".$buscar_nombre."%'";
}

if(isset($_REQUEST["sitioReunion"]) && eliminarInvalidos($_REQUEST["sitioReunion"]) != ""){
    $prision = eliminarInvalidos($_REQUEST["sitioReunion"]);
    $sqlFiltro .= " AND SR.sitioReunion = ".$prision." ";   
}

if(isset($_REQUEST["fechaInicial"]) && eliminarInvalidos($_REQUEST["fechaInicial"]) != ""){
    $fechaInicial = eliminarInvalidos($_REQUEST["fechaInicial"]);
    $sqlFiltro .= " AND SR.fechaReporte >= '".$fechaInicial."'";
}

//
if(isset($_REQUEST["fechaFinal"]) && eliminarInvalidos($_REQUEST["fechaFinal"]) != ""){
    $fechaFinal = eliminarInvalidos($_REQUEST["fechaFinal"]);
    $sqlFiltro .= " AND SR.fechaReporte <= '".$fechaFinal."'";
}
                
$sql = "SELECT AD.adj_id, UPPER(AD.adj_nom) nom_gra, AD.adj_url, AD.adj_fec, SR.id, SR.rep_tip, SR.fechaInicio, SR.fechaReporte, RU.reub_nom AS prision, C.descripcion AS regional";
$sql .= " FROM tbl_adjuntos AS AD LEFT JOIN sat_reportes AS SR ON AD.adj_rep_fk = SR.id LEFT JOIN tbl_regional_ubicacion AS RU ON RU.reub_id = SR.sitioReunion LEFT JOIN categorias AS C ON C.id = RU.reub_reg_fk LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = SR.idUsuario
WHERE SR.rep_tip = ".$tip_reporte." AND AD.adj_tip = 1 ".$sqlFiltro." GROUP BY AD.adj_id ORDER BY AD.adj_nom ASC";
$reportCSV = $PSN1->query($sql);
$numero=$PSN1->num_rows();
//
//

$sql = "SELECT usuario.*, usuario_empresa.* FROM usuario LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = usuario.id WHERE usuario.id = '".$_SESSION["id"]."'";
$PSN2->query($sql);
if($PSN2->num_rows() > 0)
{
    if($PSN2->next_record())
    {
        $empresa_pais = $PSN2->f('empresa_pais');
        $empresa_sitio_cor = $PSN2->f('empresa_sitio_cor');
        $empresa_socio = $PSN2->f('empresa_socio');   
        $empresa_rm = $PSN2->f('empresa_rm');
    }
}
$ubi_regist = $numero+10;
$mergeacross = 30;
$spreadsheet = new SpreadSheet();
$spreadsheet->getProperties()->setCreator("Andres Torres")->setTitle("Informe de Satura");
$spreadsheet->setActiveSheetIndex(0);
/*$spreadsheet->getActiveSheet()->getStyle('C')
        ->getNumberFormat()
        ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);*/
$hojaActiva = $spreadsheet->getActiveSheet();
$spreadsheet->getActiveSheet()->getStyle('A1:AI10')->getFill()
    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFE063');
$spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(50);
$spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(30);
$spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(50);
$spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
$spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(12);
$spreadsheet->getActiveSheet()->mergeCells('A2:D2');
$spreadsheet->getActiveSheet()->getStyle('A9:AI10')->getFont()->setBold(true);
$hojaActiva->setCellValue('A2','INFORME DE GRADUADOS - TOTAL:'.$numero);
$hojaActiva->setCellValue('A3','INFORMACIÓN DESDE:');
$spreadsheet->getActiveSheet()->getStyle('A2:A8')->getFont()->setBold(true);
$spreadsheet->getActiveSheet()->getStyle('C3:C4')->getFont()->setBold(true);
$spreadsheet->getActiveSheet()->getStyle('E4')->getFont()->setBold(true);
$hojaActiva->setCellValue('B3',Date::PHPtoExcel(date("d/m/Y",strtotime($fechaInicial))));
    $spreadsheet->getActiveSheet()
        ->getStyle('B3'.$fila)
        ->getNumberFormat()
        ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
$hojaActiva->setCellValue('C3','HASTA:');
$hojaActiva->setCellValue('D3',Date::PHPtoExcel(date("d/m/Y",strtotime($fechaFinal))));
$spreadsheet->getActiveSheet()
        ->getStyle('D3'.$fila)
        ->getNumberFormat()
        ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
$hojaActiva->setCellValue('A4','PERIODO Q DESDE:');
$hojaActiva->setCellValue('B4', Date::PHPtoExcel(date("d/m/Y", strtotime($iniQ))));
$spreadsheet->getActiveSheet()
        ->getStyle('B4'.$fila)
        ->getNumberFormat()
        ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
$hojaActiva->setCellValue('C4','HASTA:');
$hojaActiva->setCellValue('D4', Date::PHPtoExcel(date("d/m/Y", strtotime($finQ))));
$spreadsheet->getActiveSheet()
        ->getStyle('D4'.$fila)
        ->getNumberFormat()
        ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
$q = "";
switch ($_REQUEST['rep_qua']) {
    case '1':
        $q = "1";
        break;
    case '4':
        $q = "2";
        break;
    case '7':
        $q = "3";
        break;
    case '10':
        $q = "4";
        break;
    default:
        // code...
        break;
}
$hojaActiva->setCellValue('E4','Q:'.$q);
$spreadsheet->getActiveSheet()->mergeCells('A5:B5');
$hojaActiva->setCellValue('A5','NOMBRE DEL SOCIO:');

$spreadsheet->getActiveSheet()->mergeCells('C5:F5');
$hojaActiva->setCellValue('C5','Confraternidad Carcelaria de Colombia');

$hojaActiva->setCellValue('A6','USUARIO:');
$spreadsheet->getActiveSheet()->mergeCells('B6:C6');
$hojaActiva->setCellValue('B6',$_SESSION["nombre"]);
$hojaActiva->setCellValue('A7','PROCESO:');
$spreadsheet->getActiveSheet()->mergeCells('B7:C7');
if ($tip_reporte=='319') {
    $hojaActiva->setCellValue('B7',"PROYECTO FELIPE");
}else if ($tip_reporte == '307') {
    $hojaActiva->setCellValue('B7',"LA PEREGRINACIÓN DEL PRISIONERO (LLP)");}

$spreadsheet->getActiveSheet()->mergeCells('A9:A10');
$hojaActiva->setCellValue('A9',"Nombre del graduado");
$spreadsheet->getActiveSheet()->mergeCells('B9:B10');
$hojaActiva->setCellValue('B9',"Tarjeta dactilar / N° identificación");
$spreadsheet->getActiveSheet()->mergeCells('C9:C10');
$hojaActiva->setCellValue('C9',"Nombre de prisión");
$spreadsheet->getActiveSheet()->mergeCells('D9:D10');
$hojaActiva->setCellValue('D9',"Regional");
$spreadsheet->getActiveSheet()->mergeCells('E9:E10');
$hojaActiva->setCellValue('E9',"Fecha de registro");

$spreadsheet->getActiveSheet()->mergeCells('B7:C7');
if($numero > 0){
    $fila = 11;
    while($PSN1->next_record()){
        $hojaActiva->setCellValue('A'.$fila,$PSN1->f('nom_gra'));
        $hojaActiva->setCellValue('B'.$fila,$PSN1->f('adj_url'));
        $hojaActiva->setCellValue('C'.$fila,$PSN1->f('prision'));
        $hojaActiva->setCellValue('D'.$fila,$PSN1->f('regional'));
        $hojaActiva->setCellValue('E'.$fila,Date::PHPtoExcel(date("d/m/Y", strtotime($PSN1->f('adj_fec')))));
        $spreadsheet->getActiveSheet()
        ->getStyle('E'.$fila)
        ->getNumberFormat()
        ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        $fila++;

    }
}
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Archivo_'.date("Ymd_His").'.xlsx"');
header('Cache-Control: max-age=0');
$spreadsheet->setActiveSheetIndex(0);
$hojaActiva = $spreadsheet->getActiveSheet();

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
?>