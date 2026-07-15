<?php 
session_start();
require_once('funciones.php');

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Obtener datos del API
ob_start();
$_GET = $_REQUEST;
include('api-informe-coordinador-ecc.php');
$json_data = ob_get_clean();

$data = json_decode($json_data, true);

if (!$data || !$data['success']) {
    echo "Error al obtener datos del API<br>";
    echo "JSON recibido: <pre>";
    print_r($json_data);
    echo "</pre>";
    die();
}

// Extraer información del API
$fechaInicial = $data['fechaInicial'];
$fechaFinal = $data['fechaFinal'];
$iniQ = $data['periodoInicio'];
$finQ = $data['periodoFin'];
$usuario = $data['usuario'];
$numero = $data['total_registros'];
$registros = $data['data'];

// Determinar tipo de reporte
$tipo = 0;
if(isset($_REQUEST["rep_inex"]) && eliminarInvalidos($_REQUEST["rep_inex"]) != ""){
    $tipo = eliminarInvalidos($_REQUEST["rep_inex"]);
}

// Crear Excel con la estructura original
$ubi_regist = $numero + 10;
$spreadsheet = new SpreadSheet();
$spreadsheet->getProperties()->setCreator("Sistema PF Colombia")->setTitle("Informe Coordinador");
$spreadsheet->setActiveSheetIndex(0);
$hojaActiva = $spreadsheet->getActiveSheet();

// Estilos
$spreadsheet->getActiveSheet()->getStyle('A1:AI10')->getFill()
    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB('C8FFFF');

// Anchos de columna
$spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(25);
$spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(35);
$spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(50);
$spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(35);
$spreadsheet->getActiveSheet()->getColumnDimension('Z')->setWidth(35);
$spreadsheet->getActiveSheet()->getColumnDimension('AA')->setWidth(25);
$spreadsheet->getActiveSheet()->getColumnDimension('AB')->setWidth(20);
$spreadsheet->getActiveSheet()->getColumnDimension('AI')->setWidth(35);

// Encabezado
$spreadsheet->getActiveSheet()->mergeCells('A2:D2');
$hojaActiva->setCellValue('A2','INFORME DE COORDINADOR - REPORTES: '.$numero);
$hojaActiva->setCellValue('A3','INFORMACIÓN DESDE:');
$spreadsheet->getActiveSheet()->getStyle('A2:A8')->getFont()->setBold(true);
$spreadsheet->getActiveSheet()->getStyle('C3:C4')->getFont()->setBold(true);
$spreadsheet->getActiveSheet()->getStyle('E4')->getFont()->setBold(true);

$hojaActiva->setCellValue('B3', Date::PHPtoExcel(date("d/m/Y", strtotime($fechaInicial))));
$spreadsheet->getActiveSheet()->getStyle('B3')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);

$hojaActiva->setCellValue('C3','HASTA:');
$hojaActiva->setCellValue('D3', Date::PHPtoExcel(date("d/m/Y", strtotime($fechaFinal))));
$spreadsheet->getActiveSheet()->getStyle('D3')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);

$hojaActiva->setCellValue('A4','PERIODO Q DESDE:');
$hojaActiva->setCellValue('B4', Date::PHPtoExcel(date("d/m/Y", strtotime($iniQ))));
$spreadsheet->getActiveSheet()->getStyle('B4')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);

$hojaActiva->setCellValue('C4','HASTA:');
$hojaActiva->setCellValue('D4', Date::PHPtoExcel(date("d/m/Y", strtotime($finQ))));
$spreadsheet->getActiveSheet()->getStyle('D4')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);

$q = "";
if (!empty($_REQUEST['rep_qua'])) {
    switch ($_REQUEST['rep_qua']) {
        case '1': $q = "1"; break;
        case '4': $q = "2"; break;
        case '7': $q = "3"; break;
        case '10': $q = "4"; break;
    }
}
$hojaActiva->setCellValue('E4','Q:'.$q);

$spreadsheet->getActiveSheet()->mergeCells('A5:B5');
$hojaActiva->setCellValue('A5','NOMBRE DEL SOCIO:');
$spreadsheet->getActiveSheet()->mergeCells('C5:F5');
$hojaActiva->setCellValue('C5','Confraternidad Carcelaria de Colombia');

$hojaActiva->setCellValue('A6','USUARIO:');
$spreadsheet->getActiveSheet()->mergeCells('B6:C6');
$hojaActiva->setCellValue('B6', $usuario);

$hojaActiva->setCellValue('A7','PROCESO:');
$spreadsheet->getActiveSheet()->mergeCells('B7:C7');
$hojaActiva->setCellValue('B7',"CAPACITAR Y MULTPLICAR (C&M)");

// Cabeceras de columnas (estructura original)
$spreadsheet->getActiveSheet()->getStyle('A9:AI10')->getFont()->setBold(true);

$spreadsheet->getActiveSheet()->mergeCells('A9:A10');
$hojaActiva->setCellValue('A9',"Nombre del lider");
$spreadsheet->getActiveSheet()->mergeCells('B9:B10');
$hojaActiva->setCellValue('B9',"Nombre del grupo / iglesia");
$spreadsheet->getActiveSheet()->mergeCells('C9:C10');
$hojaActiva->setCellValue('C9',"Fecha de Inicio");
$spreadsheet->getActiveSheet()->mergeCells('D9:D10');
$hojaActiva->setCellValue('D9',"Generación");
$spreadsheet->getActiveSheet()->mergeCells('E9:E10');
$hojaActiva->setCellValue('E9',"Ubicación");
$spreadsheet->getActiveSheet()->mergeCells('F9:F10');
$hojaActiva->setCellValue('F9',"Grupo Madre / Iglesia");
$hojaActiva->setCellValue('G9',"Asistencia del grupo");
$hojaActiva->setCellValue('G10','=SUM(G11:G'.$ubi_regist.')');
$hojaActiva->setCellValue('H9',"Total de creyentes en el grupo");
$hojaActiva->setCellValue('H10','=SUM(H11:H'.$ubi_regist.')');
$hojaActiva->setCellValue('I9',"Nuevos creyentes en el grupo en este periodo");
$hojaActiva->setCellValue('I10','=SUM(I11:I'.$ubi_regist.')');
$hojaActiva->setCellValue('J9',"Total de bautizados en el grupo");
$hojaActiva->setCellValue('J10','=SUM(J11:J'.$ubi_regist.')');
$hojaActiva->setCellValue('K9',"Nuevos bautizados en el grupo en este periodo");
$hojaActiva->setCellValue('K10','=SUM(K11:K'.$ubi_regist.')');
$hojaActiva->setCellValue('L9',"Orar");
$hojaActiva->setCellValue('L10','=SUM(L11:L'.$ubi_regist.')');
$hojaActiva->setCellValue('M9',"Companerismo");
$hojaActiva->setCellValue('M10','=SUM(M11:M'.$ubi_regist.')');
$hojaActiva->setCellValue('N9',"Adorar");
$hojaActiva->setCellValue('N10','=SUM(N11:N'.$ubi_regist.')');
$hojaActiva->setCellValue('O9',"Aplicar la biblia");
$hojaActiva->setCellValue('O10','=SUM(O11:O'.$ubi_regist.')');
$hojaActiva->setCellValue('P9',"Evangelizar");
$hojaActiva->setCellValue('P10','=SUM(P11:P'.$ubi_regist.')');
$hojaActiva->setCellValue('Q9',"Cena del Señor");
$hojaActiva->setCellValue('Q10','=SUM(Q11:Q'.$ubi_regist.')');
$hojaActiva->setCellValue('R9',"Dar");
$hojaActiva->setCellValue('R10','=SUM(R11:R'.$ubi_regist.')');
$hojaActiva->setCellValue('S9',"Bautizar");
$hojaActiva->setCellValue('S10','=SUM(S11:S'.$ubi_regist.')');
$hojaActiva->setCellValue('T9',"Entrenar nuevos lideres");
$hojaActiva->setCellValue('T10','=SUM(T11:T'.$ubi_regist.')');
$hojaActiva->setCellValue('U9',"1");
$hojaActiva->setCellValue('U10','=SUM(U11:U'.$ubi_regist.')');
$hojaActiva->setCellValue('V9',"2");
$hojaActiva->setCellValue('V10','=SUM(V11:V'.$ubi_regist.')');
$hojaActiva->setCellValue('W9',"3");
$hojaActiva->setCellValue('W10','=SUM(W11:W'.$ubi_regist.')');
$hojaActiva->setCellValue('X9',"4");
$hojaActiva->setCellValue('X10','=SUM(X11:X'.$ubi_regist.')');
$hojaActiva->setCellValue('Z10',"Ubicacion del entrenador");
$hojaActiva->setCellValue('AA10','Entrenador');
$hojaActiva->setCellValue('AB10','Carnet de identidad');
$hojaActiva->setCellValue('AC10','Ch');
$hojaActiva->setCellValue('AD10','Suma');
$hojaActiva->setCellValue('AE10','Promedio');
$hojaActiva->setCellValue('AF10','Desde');
$hojaActiva->setCellValue('AG10','Hasta');
$hojaActiva->setCellValue('AH10','Reunido');
$hojaActiva->setCellValue('AI10','Nombre del socio');

// Llenar datos (usando todos los datos del API actualizado)
if($numero > 0){
    $fila = 11;
    foreach($registros as $registro){
        // Datos exactos como el Excel original
        $hojaActiva->setCellValue('A'.$fila, $registro['nombreUsuario']."/".(isset($registro['rep_entr']) ? $registro['rep_entr'] : ''));
        $hojaActiva->setCellValue('B'.$fila, $registro['nombreGrupo_txt']);
        
        // Fecha de inicio
        if($registro['fechaInicio']) {
            $hojaActiva->setCellValue('C'.$fila, Date::PHPtoExcel(date("d/m/Y", strtotime($registro['fechaInicio']))));
            $spreadsheet->getActiveSheet()->getStyle('C'.$fila)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        }
        
        $hojaActiva->setCellValue('D'.$fila, $registro['generacionNumero']);
        
        // Ubicación (igual que en el Excel original)
        if($tipo == 2){
            $ubicacion = $registro['prision'] . " / " . $registro['dpto_prision'] . " / " . $registro['mnpo_prision'] . " / " . $registro['dire_prision'];
        } else {
            $ubicacion = $registro['dpto_prision_extra'] . " / " . $registro['mnpo_prision_extra'] . " / " . $registro['direccion'];
        }
        $hojaActiva->setCellValue('E'.$fila, $ubicacion);
        
        $hojaActiva->setCellValue('F'.$fila, $registro['grupoMadre_txt']);
        $hojaActiva->setCellValue('G'.$fila, intval($registro['asistencia_total']));
        $hojaActiva->setCellValue('H'.$fila, intval($registro['discipulado']));
        
        // Verificar si está en el período para decisiones
        $fecRep = date("Y-m-d", strtotime($registro['fechaInicio']));
        if ($iniQ <= $fecRep && $fecRep <= $finQ) {
            $desici = intval($registro['desiciones']);
        } else {
            $desici = 0;
        }
        $hojaActiva->setCellValue('I'.$fila, $desici);
        
        $hojaActiva->setCellValue('J'.$fila, intval($registro['bautizados']));
        
        // Verificar si está en el período para bautizados
        if ($iniQ <= $fecRep && $fecRep <= $finQ) {
            $bautiP = intval($registro['bautizadosPeriodo']);
        } else {
            $bautiP = 0;
        }
        $hojaActiva->setCellValue('K'.$fila, $bautiP);
        
        // Mapeos espirituales (ahora sí disponibles desde el API)
        $hojaActiva->setCellValue('L'.$fila, intval($registro['mapeo_oracion']));
        $hojaActiva->setCellValue('M'.$fila, intval($registro['mapeo_companerismo']));
        $hojaActiva->setCellValue('N'.$fila, intval($registro['mapeo_adoracion']));
        $hojaActiva->setCellValue('O'.$fila, intval($registro['mapeo_biblia']));
        $hojaActiva->setCellValue('P'.$fila, intval($registro['mapeo_evangelizar']));
        $hojaActiva->setCellValue('Q'.$fila, intval($registro['mapeo_cena']));
        $hojaActiva->setCellValue('R'.$fila, intval($registro['mapeo_dar']));
        $hojaActiva->setCellValue('S'.$fila, intval($registro['mapeo_bautizar']));
        $hojaActiva->setCellValue('T'.$fila, intval($registro['mapeo_trabajadores']));
        $hojaActiva->setCellValue('U'.$fila, 0);
        $hojaActiva->setCellValue('V'.$fila, 0);
        $hojaActiva->setCellValue('W'.$fila, 0);
        $hojaActiva->setCellValue('X'.$fila, 0);
        
        // Ubicación del entrenador
        $ubicacionEntrenador = $registro['dpto_usuario'] . " / " . $registro['mnpo_usuario'] . " / " . $registro['direccionUsuario'];
        $hojaActiva->setCellValue('Z'.$fila, $ubicacionEntrenador);
        $hojaActiva->setCellValue('AA'.$fila, $registro['nombreUsuario']);
        $hojaActiva->setCellValue('AB'.$fila, $registro['identificacionUsuario']);
        $hojaActiva->setCellValue('AC'.$fila, $registro['mapeo_comprometido']);
        
        // Suma y promedio de mapeos
        $hojaActiva->setCellValue('AD'.$fila, $registro['mapeo_suma']);
        $hojaActiva->setCellValue('AE'.$fila, $registro['mapeo_promedio']);
        
        // Fechas del período
        $hojaActiva->setCellValue('AF'.$fila, Date::PHPtoExcel(date("d/m/Y", strtotime($iniQ))));
        $spreadsheet->getActiveSheet()->getStyle('AF'.$fila)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        $hojaActiva->setCellValue('AG'.$fila, Date::PHPtoExcel(date("d/m/Y", strtotime($finQ))));
        $spreadsheet->getActiveSheet()->getStyle('AG'.$fila)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        
        // Fecha reunido
        if($registro['mapeo_fecha']) {
            $hojaActiva->setCellValue('AH'.$fila, Date::PHPtoExcel(date("d/m/Y", strtotime($registro['mapeo_fecha']))));
            $spreadsheet->getActiveSheet()->getStyle('AH'.$fila)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        }
        
        // Nombre del socio
        if($tipo == 2){
            $socio = $registro['rgal_prision'];
        } else {
            $socio = $registro['rgal_usuario'];
        }
        $hojaActiva->setCellValue('AI'.$fila, $socio);
        
        $fila++;
    }
}

// Generar archivo
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Archivo_'.date("Ymd_His").'.xlsx"');
header('Cache-Control: max-age=0');

$spreadsheet->setActiveSheetIndex(0);
$hojaActiva = $spreadsheet->getActiveSheet();

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
?>