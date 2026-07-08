<?php
session_start();
require_once('funciones.php');

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Obtener datos del API (reporte_cm)
ob_start();
$_GET = $_REQUEST;
include('api-informe-coordinador-ecc.php');
$json_data = ob_get_clean();

$data = json_decode($json_data, true);

if (!$data || !$data['success']) {
    echo "Error al obtener datos del informe<br>";
    echo "Respuesta recibida: <pre>";
    print_r($json_data);
    echo "</pre>";
    die();
}

$fechaInicial = $data['fechaInicial'];
$fechaFinal = $data['fechaFinal'];
$usuario = $data['usuario'];
$numero = $data['total_registros'];
$registros = $data['data'];

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()->setCreator("Sistema PF Colombia")->setTitle("Informe Coordinador C&M");
$hojaActiva = $spreadsheet->getActiveSheet();
$hojaActiva->setTitle('Informe C&M');

$colorEncabezado = '2E5B8A';
$colorTituloFondo = 'DCE6F1';

// ---------- Bloque de título / filtros ----------
$hojaActiva->mergeCells('A1:F1');
$hojaActiva->setCellValue('A1', 'INFORME DE COORDINADOR - CAPACITAR Y MULTIPLICAR (C&M)');
$hojaActiva->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$hojaActiva->getStyle('A1:F1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($colorTituloFondo);
$hojaActiva->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

$hojaActiva->setCellValue('A2', 'Generado por:');
$hojaActiva->setCellValue('B2', $usuario);
$hojaActiva->setCellValue('A3', 'Rango de fechas:');
$hojaActiva->setCellValue('B3', date("d/m/Y", strtotime($fechaInicial)).'  al  '.date("d/m/Y", strtotime($fechaFinal)));
$hojaActiva->setCellValue('A4', 'Total de reportes:');
$hojaActiva->setCellValue('B4', $numero);
$hojaActiva->getStyle('A2:A4')->getFont()->setBold(true);

// ---------- Encabezados de columnas ----------
$filaEncabezado = 6;
$columnas = [
    'A' => 'Coordinador',
    'B' => 'Entrenador',
    'C' => 'Facilitador',
    'D' => 'Regional',
    'E' => 'Zona',
    'F' => 'Grupo / Iglesia',
    'G' => 'Grupo madre',
    'H' => 'Tipo',
    'I' => 'Ubicación',
    'J' => 'Fecha reporte',
    'K' => 'Fecha inicio',
    'L' => 'Generación',
    'M' => 'Asist. hombres',
    'N' => 'Asist. mujeres',
    'O' => 'Asist. jóvenes',
    'P' => 'Asist. niños',
    'Q' => 'Asistencia total',
    'R' => 'Bautizados (total)',
    'S' => 'Bautizados en el período',
    'T' => 'En discipulado',
    'U' => 'Decisiones para Cristo',
    'V' => 'Preparándose para bautismo',
    'W' => 'Graduados en el período',
    'X' => 'Curso de graduación',
    'Y' => 'Oración',
    'Z' => 'Compañerismo',
    'AA' => 'Adoración',
    'AB' => 'Aplicar la Biblia',
    'AC' => 'Evangelizar',
    'AD' => 'Cena del Señor',
    'AE' => 'Dar',
    'AF' => 'Bautizar',
    'AG' => 'Entrenar líderes',
    'AH' => 'Comprometido',
    'AI' => 'Promedio mapeo',
    'AJ' => 'Fecha mapeo',
];

foreach($columnas as $col => $titulo){
    $hojaActiva->setCellValue($col.$filaEncabezado, $titulo);
}
$ultimaColumna = array_key_last($columnas);
$hojaActiva->getStyle('A'.$filaEncabezado.':'.$ultimaColumna.$filaEncabezado)
    ->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$hojaActiva->getStyle('A'.$filaEncabezado.':'.$ultimaColumna.$filaEncabezado)
    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($colorEncabezado);
$hojaActiva->getStyle('A'.$filaEncabezado.':'.$ultimaColumna.$filaEncabezado)
    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
$hojaActiva->getRowDimension($filaEncabezado)->setRowHeight(30);

// ---------- Datos ----------
$fila = $filaEncabezado + 1;
if($numero > 0){
    foreach($registros as $registro){
        $hojaActiva->setCellValue('A'.$fila, $registro['nombreUsuario']);
        $hojaActiva->setCellValue('B'.$fila, $registro['entrenador']);
        $hojaActiva->setCellValue('C'.$fila, $registro['siervo_facilitador']);
        $hojaActiva->setCellValue('D'.$fila, $registro['regional_nombre']);
        $hojaActiva->setCellValue('E'.$fila, $registro['zona_nombre']);
        $hojaActiva->setCellValue('F'.$fila, $registro['nombre_grupo_iglesia']);
        $hojaActiva->setCellValue('G'.$fila, $registro['grupo_madre']);
        $hojaActiva->setCellValue('H'.$fila, $registro['tipo'] == 'INTRA' ? 'Intramuros' : 'Extramuros');
        $hojaActiva->setCellValue('I'.$fila, $registro['ubicacion']);

        if($registro['fecha_reporte']){
            $hojaActiva->setCellValue('J'.$fila, Date::PHPtoExcel(strtotime($registro['fecha_reporte'])));
            $hojaActiva->getStyle('J'.$fila)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        }
        if($registro['fecha_inicio_confraternidad']){
            $hojaActiva->setCellValue('K'.$fila, Date::PHPtoExcel(strtotime($registro['fecha_inicio_confraternidad'])));
            $hojaActiva->getStyle('K'.$fila)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        }

        $hojaActiva->setCellValue('L'.$fila, $registro['generacion']);

        $hojaActiva->setCellValue('M'.$fila, $registro['asistencia_hombres']);
        $hojaActiva->setCellValue('N'.$fila, $registro['asistencia_mujeres']);
        $hojaActiva->setCellValue('O'.$fila, $registro['asistencia_jovenes']);
        $hojaActiva->setCellValue('P'.$fila, $registro['asistencia_ninos']);
        $hojaActiva->setCellValue('Q'.$fila, $registro['asistencia_total']);

        $hojaActiva->setCellValue('R'.$fila, $registro['miembros_bautizados']);
        $hojaActiva->setCellValue('S'.$fila, $registro['bautizados_periodo']);
        $hojaActiva->setCellValue('T'.$fila, $registro['en_discipulado']);
        $hojaActiva->setCellValue('U'.$fila, $registro['decisiones_cristo']);
        $hojaActiva->setCellValue('V'.$fila, $registro['preparandose_bautismo']);
        $hojaActiva->setCellValue('W'.$fila, $registro['graduados_periodo']);
        $hojaActiva->setCellValue('X'.$fila, $registro['curso_graduacion']);

        $hojaActiva->setCellValue('Y'.$fila, $registro['mapeo_oracion']);
        $hojaActiva->setCellValue('Z'.$fila, $registro['mapeo_companerismo']);
        $hojaActiva->setCellValue('AA'.$fila, $registro['mapeo_adoracion']);
        $hojaActiva->setCellValue('AB'.$fila, $registro['mapeo_biblia']);
        $hojaActiva->setCellValue('AC'.$fila, $registro['mapeo_evangelizar']);
        $hojaActiva->setCellValue('AD'.$fila, $registro['mapeo_cena']);
        $hojaActiva->setCellValue('AE'.$fila, $registro['mapeo_dar']);
        $hojaActiva->setCellValue('AF'.$fila, $registro['mapeo_bautizar']);
        $hojaActiva->setCellValue('AG'.$fila, $registro['mapeo_trabajadores']);
        $hojaActiva->setCellValue('AH'.$fila, $registro['mapeo_comprometido']);
        $hojaActiva->setCellValue('AI'.$fila, $registro['mapeo_promedio']);

        if($registro['mapeo_fecha']){
            $hojaActiva->setCellValue('AJ'.$fila, Date::PHPtoExcel(strtotime($registro['mapeo_fecha'])));
            $hojaActiva->getStyle('AJ'.$fila)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        }

        // Filas alternadas para facilitar la lectura
        if(($fila - $filaEncabezado) % 2 == 0){
            $hojaActiva->getStyle('A'.$fila.':'.$ultimaColumna.$fila)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F6FB');
        }

        $fila++;
    }

    // Fila de totales para las columnas numéricas de asistencia y ministerios
    $ultimaFilaDatos = $fila - 1;
    $hojaActiva->setCellValue('A'.$fila, 'TOTALES');
    $hojaActiva->getStyle('A'.$fila)->getFont()->setBold(true);
    foreach(['M','N','O','P','Q','R','S','T','U','V','W'] as $col){
        $hojaActiva->setCellValue($col.$fila, '=SUM('.$col.($filaEncabezado+1).':'.$col.$ultimaFilaDatos.')');
        $hojaActiva->getStyle($col.$fila)->getFont()->setBold(true);
    }
    $hojaActiva->getStyle('A'.$fila.':'.$ultimaColumna.$fila)
        ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
}

// ---------- Formato general ----------
foreach(array_keys($columnas) as $col){
    $hojaActiva->getColumnDimension($col)->setAutoSize(true);
}
$hojaActiva->getColumnDimension('F')->setAutoSize(false)->setWidth(30);
$hojaActiva->getColumnDimension('I')->setAutoSize(false)->setWidth(35);

$hojaActiva->setAutoFilter('A'.$filaEncabezado.':'.$ultimaColumna.$filaEncabezado);
$hojaActiva->freezePane('A'.($filaEncabezado + 1));

if($numero > 0){
    $hojaActiva->getStyle('A'.$filaEncabezado.':'.$ultimaColumna.($fila - 1))
        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CCCCCC'));
}

// ---------- Generar archivo ----------
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Informe_CyM_'.date("Ymd_His").'.xlsx"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
?>
