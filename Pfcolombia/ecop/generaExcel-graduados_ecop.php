<?php
session_start();
include_once('funciones.php');

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

if(!isset($_SESSION["id"]) || $_SESSION["id"] == ""){
    http_response_code(403);
    die("<h1>No autorizado</h1>");
}

if(!isset($_REQUEST["fechaInicial"]) || eliminarInvalidos($_REQUEST["fechaInicial"]) == ""){
    $_REQUEST["fechaInicial"] = "2024-01-01";
}
if(!isset($_REQUEST["fechaFinal"]) || eliminarInvalidos($_REQUEST["fechaFinal"]) == ""){
    $_REQUEST["fechaFinal"] = date("Y-m-d");
}

$tip_reporte = 347;

$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;
$sqlFiltro = "";

if($_SESSION["perfil"] == 163){
    $_REQUEST["idUsuario"] = $_SESSION["id"];
}

if(isset($_REQUEST["idUsuario"]) && soloNumeros($_REQUEST["idUsuario"]) != "" && soloNumeros($_REQUEST["idUsuario"]) != "0"){
    $buscar_idUsuario = soloNumeros($_REQUEST["idUsuario"]);
    $sqlFiltro .= " AND sat_reportes.idUsuario = '".$buscar_idUsuario."'";
}

if(isset($_REQUEST["empresa_sitio_cor"]) && soloNumeros($_REQUEST["empresa_sitio_cor"]) != "" && soloNumeros($_REQUEST["empresa_sitio_cor"]) != "0"){
    $buscar_zona = soloNumeros($_REQUEST["empresa_sitio_cor"]);
    $sqlFiltro .= " AND EXISTS (SELECT 1 FROM usuario_empresa UE LEFT JOIN categorias C ON C.id = UE.empresa_pd WHERE UE.idUsuario = sat_reportes.idUsuario AND C.idSec = '".$buscar_zona."')";
} else if (!isset($_REQUEST["empresa_sitio_cor"]) && $_SESSION["id_zona"] != "" && $_SESSION["id_zona"] != 0){
    $sqlFiltro .= " AND EXISTS (SELECT 1 FROM usuario_empresa UE LEFT JOIN categorias C ON C.id = UE.empresa_pd WHERE UE.idUsuario = sat_reportes.idUsuario AND C.idSec = '".$_SESSION["id_zona"]."')";
}

if(isset($_REQUEST["empresa_pd"]) && soloNumeros($_REQUEST["empresa_pd"]) != "" && soloNumeros($_REQUEST["empresa_pd"]) != "0"){
    $buscar_empresa_pd = soloNumeros($_REQUEST["empresa_pd"]);
    $sqlFiltro .= " AND EXISTS (SELECT 1 FROM usuario_empresa UE WHERE UE.idUsuario = sat_reportes.idUsuario AND UE.empresa_pd = '".$buscar_empresa_pd."')";
} else if (!isset($_REQUEST["empresa_pd"]) && $_SESSION["empresa_pd"] != "" && $_SESSION["empresa_pd"] != 0){
    $buscar_empresa_pd = soloNumeros($_SESSION["empresa_pd"]);
    $sqlFiltro .= " AND EXISTS (SELECT 1 FROM usuario_empresa UE WHERE UE.idUsuario = sat_reportes.idUsuario AND UE.empresa_pd = '".$buscar_empresa_pd."')";
    $_REQUEST["empresa_pd"] = $_SESSION["empresa_pd"];
}

if(isset($_REQUEST["sitioReunion"]) && $_REQUEST["sitioReunion"] != "" && soloNumeros($_REQUEST["sitioReunion"]) != ""){
    $buscar_prision = soloNumeros($_REQUEST["sitioReunion"]);
    $sqlFiltro .= " AND sat_reportes.sitioReunion = ".$buscar_prision."";
}

if(isset($_REQUEST["rep_qua"]) && $_REQUEST["rep_qua"] != "" && soloNumeros($_REQUEST["rep_qua"]) != ""){
    $buscar_periodo = soloNumeros($_REQUEST["rep_qua"]);
    $sqlFiltro .= " AND sat_reportes.mapeo_cuarto = '".$buscar_periodo."'";
}

if(isset($_REQUEST["rep_inex"]) && eliminarInvalidos($_REQUEST["rep_inex"]) != ""){
    $tipoInex = eliminarInvalidos($_REQUEST["rep_inex"]);
    if($tipoInex == 2){
        $sqlFiltro .= " AND sat_reportes.sitioReunion = 0 ";
    }else{
        $sqlFiltro .= " AND sat_reportes.sitioReunion <> 0 ";
    }
}

if(isset($_REQUEST["nombre"]) && eliminarInvalidos($_REQUEST["nombre"]) != ""){
    $buscar_nombre = eliminarInvalidos($_REQUEST["nombre"]);
    $sqlFiltro .= " AND A.adj_nom LIKE '%".$buscar_nombre."%'";
}

$fechaInicial = eliminarInvalidos($_REQUEST["fechaInicial"]);
$sqlFiltro .= " AND sat_reportes.fechaReporte >= '".$fechaInicial."'";

$fechaFinal = eliminarInvalidos($_REQUEST["fechaFinal"]);
$sqlFiltro .= " AND sat_reportes.fechaReporte <= '".$fechaFinal."'";

$sql = "SELECT A.adj_id, UPPER(A.adj_nom) AS nom_gra, A.adj_url AS identificacion, A.adj_fec AS adj_fec, sat_reportes.id AS id, sat_reportes.rep_tip AS rep_tip, sat_reportes.fechaReporte AS fechaReporte, RU.reub_nom AS prision, C.descripcion AS regional";
$sql .= " FROM tbl_adjuntos AS A LEFT JOIN sat_reportes ON A.adj_rep_fk = sat_reportes.id LEFT JOIN tbl_regional_ubicacion AS RU ON RU.reub_id = sat_reportes.sitioReunion LEFT JOIN categorias AS C ON C.id = RU.reub_reg_fk";
$sql .= " WHERE sat_reportes.rep_tip = ".$tip_reporte." AND A.adj_tip = 1 ".$sqlFiltro." ORDER BY A.adj_nom ASC";
$PSN1->query($sql);
$numero = $PSN1->num_rows();

$spreadsheet = new SpreadSheet();
$spreadsheet->getProperties()->setCreator($_SESSION["nombre"])->setTitle("Informe ECOP");
$spreadsheet->setActiveSheetIndex(0);
$hojaActiva = $spreadsheet->getActiveSheet();
$spreadsheet->getActiveSheet()->getStyle('A1:E10')->getFill()
    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFE063');
$spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(50);
$spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(30);
$spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(50);
$spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(30);
$spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(15);
$spreadsheet->getActiveSheet()->mergeCells('A2:D2');
$spreadsheet->getActiveSheet()->getStyle('A9:E10')->getFont()->setBold(true);

$hojaActiva->setCellValue('A2', 'INFORME ECOP - PARTICIPANTES - TOTAL:'.$numero);
$hojaActiva->setCellValue('A3', 'INFORMACIÓN DESDE:');
$spreadsheet->getActiveSheet()->getStyle('A2:A7')->getFont()->setBold(true);
$spreadsheet->getActiveSheet()->getStyle('C3')->getFont()->setBold(true);

$hojaActiva->setCellValue('B3', Date::PHPtoExcel(date("d/m/Y", strtotime($fechaInicial))));
$spreadsheet->getActiveSheet()->getStyle('B3')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
$hojaActiva->setCellValue('C3', 'HASTA:');
$hojaActiva->setCellValue('D3', Date::PHPtoExcel(date("d/m/Y", strtotime($fechaFinal))));
$spreadsheet->getActiveSheet()->getStyle('D3')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);

$spreadsheet->getActiveSheet()->mergeCells('A5:B5');
$hojaActiva->setCellValue('A5', 'NOMBRE DEL SOCIO:');
$spreadsheet->getActiveSheet()->mergeCells('C5:F5');
$hojaActiva->setCellValue('C5', 'Confraternidad Carcelaria de Colombia');

$hojaActiva->setCellValue('A6', 'USUARIO:');
$spreadsheet->getActiveSheet()->mergeCells('B6:C6');
$hojaActiva->setCellValue('B6', $_SESSION["nombre"]);
$hojaActiva->setCellValue('A7', 'PROCESO:');
$spreadsheet->getActiveSheet()->mergeCells('B7:C7');
$hojaActiva->setCellValue('B7', 'ECOP');

$spreadsheet->getActiveSheet()->mergeCells('A9:A10');
$hojaActiva->setCellValue('A9', 'Nombre del participante');
$spreadsheet->getActiveSheet()->mergeCells('B9:B10');
$hojaActiva->setCellValue('B9', 'Tarjeta dactilar / N° identificación');
$spreadsheet->getActiveSheet()->mergeCells('C9:C10');
$hojaActiva->setCellValue('C9', 'Nombre de prisión');
$spreadsheet->getActiveSheet()->mergeCells('D9:D10');
$hojaActiva->setCellValue('D9', 'Regional');
$spreadsheet->getActiveSheet()->mergeCells('E9:E10');
$hojaActiva->setCellValue('E9', 'Fecha de registro');

if($numero > 0){
    $fila = 11;
    while($PSN1->next_record()){
        $hojaActiva->setCellValue('A'.$fila, $PSN1->f('nom_gra'));
        $hojaActiva->setCellValue('B'.$fila, $PSN1->f('identificacion'));
        $hojaActiva->setCellValue('C'.$fila, $PSN1->f('prision'));
        $hojaActiva->setCellValue('D'.$fila, $PSN1->f('regional'));
        $hojaActiva->setCellValue('E'.$fila, Date::PHPtoExcel(date("d/m/Y", strtotime($PSN1->f('adj_fec')))));
        $spreadsheet->getActiveSheet()->getStyle('E'.$fila)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        $fila++;
    }
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="ECOP_Participantes_'.date("Ymd_His").'.xlsx"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
