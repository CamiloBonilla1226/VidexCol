<?php
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="reporte-rmi20-' . date('Y-m-d-His') . '.xls"');

// Obtener parámetros
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$fechaInicial = isset($_GET['fechaInicial']) ? $_GET['fechaInicial'] : '';
$fechaFinal = isset($_GET['fechaFinal']) ? $_GET['fechaFinal'] : '';
$idUsuario = isset($_GET['idUsuario']) ? $_GET['idUsuario'] : '';
$sitioReunion = isset($_GET['sitioReunion']) ? $_GET['sitioReunion'] : '';

// Construir URL de consulta al API interno
// Usar la URL del servidor actual
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$apiUrl = $protocol . $host . '/api-rmi20-export.php?';

$params = array(
    'limit=' . $limit,
    'offset=' . $offset
);

if (!empty($fechaInicial)) {
    $params[] = 'fechaInicial=' . urlencode($fechaInicial);
}
if (!empty($fechaFinal)) {
    $params[] = 'fechaFinal=' . urlencode($fechaFinal);
}
if (!empty($idUsuario)) {
    $params[] = 'idUsuario=' . urlencode($idUsuario);
}
if (!empty($sitioReunion)) {
    $params[] = 'sitioReunion=' . urlencode($sitioReunion);
}

$apiUrl .= implode('&', $params);

// Consultar el API
$jsonResponse = @file_get_contents($apiUrl);

if ($jsonResponse === false) {
    http_response_code(500);
    die('Error al consultar el API interno');
}

$data = json_decode($jsonResponse, true);

if (!$data || !$data['success']) {
    http_response_code(500);
    die('Error en la respuesta del API');
}

// Crear Excel en formato XML (compatible con Excel)
createExcelManual($data);

function createExcelManual($data) {
    // Generar archivo Excel en formato XML (más simple)
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
    $xml .= '<Styles>' . "\n";
    $xml .= '<Style ss:ID="header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#366092" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>' . "\n";
    $xml .= '<Style ss:ID="data"><Alignment ss:Horizontal="Left" ss:Vertical="Top" ss:WrapText="1"/></Style>' . "\n";
    $xml .= '</Styles>' . "\n";
    $xml .= '<Worksheet ss:Name="RMI20">' . "\n";
    $xml .= '<Table>' . "\n";

    // Mapeo de encabezados: inglés => español
    $headerTranslation = array(
        'Team' => 'Equipo',
        'leaderName' => 'Nombre del Líder',
        'groupName' => 'Nombre del Grupo',
        'dateStart' => 'Fecha de Inicio',
        'Generation' => 'Generación',
        'groupLocation' => 'Ubicación del Grupo',
        'motherGroup' => 'Grupo Madre',
        'groupAttendance' => 'Asistencia del Grupo',
        'totalBelievers' => 'Total de Creyentes',
        'newBelievers' => 'Nuevos Creyentes',
        'totalBaptized' => 'Total de Bautizados',
        'newBaptized' => 'Nuevos Bautizados',
        'Prayer' => 'Oración',
        'Fellowship' => 'Compañerismo',
        'Worship' => 'Adoración',
        'applyBible' => 'Aplicar la Biblia',
        'Evangelism' => 'Evangelismo',
        'LordsSupper' => 'Santa Cena',
        'Giving' => 'Dar',
        'Baptism' => 'Bautismo',
        'Workers' => 'Trabajadores',
        'userDef1' => 'Campo 1',
        'userDef2' => 'Campo 2',
        'userDef3' => 'Campo 3',
        'userDef4' => 'Campo 4',
        'extra' => 'Informe',
        'trainerLocation' => 'Ubicación del Entrenador',
        'Coach' => 'Coordinador',
        'groupID' => 'Identificación',
        'isChurch' => 'Es Iglesia',
        'healthSum' => 'Suma de Salud',
        'healthAvg' => 'Promedio de Salud',
        'dateFrom' => 'Desde',
        'dateTo' => 'Hasta',
        'dateCollected' => 'Reunido',
        'ministryPartner' => 'Socio de Ministerio',
        'Denomination' => 'Denominación',
        'lat' => 'Latitud',
        'lon' => 'Longitud'
    );

    $headers = array(
        'Team', 'leaderName', 'groupName', 'dateStart', 'Generation',
        'groupLocation', 'motherGroup', 'groupAttendance', 'totalBelievers', 'newBelievers',
        'totalBaptized', 'newBaptized', 'Prayer', 'Fellowship', 'Worship',
        'applyBible', 'Evangelism', 'LordsSupper', 'Giving', 'Baptism', 'Workers',
        'userDef1', 'userDef2', 'userDef3', 'userDef4', 'extra',
        'trainerLocation', 'Coach', 'groupID', 'isChurch',
        'healthSum', 'healthAvg', 'dateFrom', 'dateTo', 'dateCollected',
        'ministryPartner', 'Denomination', 'lat', 'lon'
    );

    // Fila de encabezados en español
    $xml .= '<Row ss:StyleID="header">' . "\n";
    foreach ($headers as $header) {
        $headerLabel = isset($headerTranslation[$header]) ? $headerTranslation[$header] : $header;
        $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($headerLabel) . '</Data></Cell>' . "\n";
    }
    $xml .= '</Row>' . "\n";

    // Filas de datos
    if (isset($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as $row) {
            $xml .= '<Row ss:StyleID="data">' . "\n";
            foreach ($headers as $header) {
                $value = isset($row[$header]) ? $row[$header] : '';
                $type = is_numeric($value) && $header !== 'groupID' ? 'Number' : 'String';
                $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($value) . '</Data></Cell>' . "\n";
            }
            $xml .= '</Row>' . "\n";
        }
    }

    $xml .= '</Table>' . "\n";
    $xml .= '</Worksheet>' . "\n";
    $xml .= '</Workbook>';

    echo $xml;
}

function createExcelWithPhpSpreadsheet($data) {
    require 'vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('RMI20');

    // Mapeo de encabezados: inglés => español
    $headerTranslation = array(
        'Team' => 'Equipo',
        'leaderName' => 'Nombre del Líder',
        'groupName' => 'Nombre del Grupo',
        'dateStart' => 'Fecha de Inicio',
        'Generation' => 'Generación',
        'groupLocation' => 'Ubicación del Grupo',
        'motherGroup' => 'Grupo Madre',
        'groupAttendance' => 'Asistencia del Grupo',
        'totalBelievers' => 'Total de Creyentes',
        'newBelievers' => 'Nuevos Creyentes',
        'totalBaptized' => 'Total de Bautizados',
        'newBaptized' => 'Nuevos Bautizados',
        'Prayer' => 'Oración',
        'Fellowship' => 'Compañerismo',
        'Worship' => 'Adoración',
        'applyBible' => 'Aplicar la Biblia',
        'Evangelism' => 'Evangelismo',
        'LordsSupper' => 'Santa Cena',
        'Giving' => 'Dar',
        'Baptism' => 'Bautismo',
        'Workers' => 'Trabajadores',
        'userDef1' => 'Campo 1',
        'userDef2' => 'Campo 2',
        'userDef3' => 'Campo 3',
        'userDef4' => 'Campo 4',
        'extra' => 'Informe',
        'trainerLocation' => 'Ubicación del Entrenador',
        'Coach' => 'Coordinador',
        'groupID' => 'Identificación',
        'isChurch' => 'Es Iglesia',
        'healthSum' => 'Suma de Salud',
        'healthAvg' => 'Promedio de Salud',
        'dateFrom' => 'Desde',
        'dateTo' => 'Hasta',
        'dateCollected' => 'Reunido',
        'ministryPartner' => 'Socio de Ministerio',
        'Denomination' => 'Denominación',
        'lat' => 'Latitud',
        'lon' => 'Longitud'
    );

    $headers = array(
        'Team', 'leaderName', 'groupName', 'dateStart', 'Generation',
        'groupLocation', 'motherGroup', 'groupAttendance', 'totalBelievers', 'newBelievers',
        'totalBaptized', 'newBaptized', 'Prayer', 'Fellowship', 'Worship',
        'applyBible', 'Evangelism', 'LordsSupper', 'Giving', 'Baptism', 'Workers',
        'userDef1', 'userDef2', 'userDef3', 'userDef4', 'extra',
        'trainerLocation', 'Coach', 'groupID', 'isChurch',
        'healthSum', 'healthAvg', 'dateFrom', 'dateTo', 'dateCollected',
        'ministryPartner', 'Denomination', 'lat', 'lon'
    );

    // Escribir encabezados
    $col = 1;
    foreach ($headers as $header) {
        $sheet->setCellValueByColumnAndRow($col, 1, $header);

        // Aplicar estilo a encabezados
        $cell = $sheet->getCellByColumnAndRow($col, 1);
        $cell->getFont()->setBold(true)->setColor(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
        $cell->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\PatternFill::FILL_SOLID)->getStartColor()->setARGB('FF366092');
        $cell->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $col++;
    }

    // Escribir datos
    if (isset($data['data']) && is_array($data['data'])) {
        $row = 2;
        foreach ($data['data'] as $record) {
            $col = 1;
            foreach ($headers as $header) {
                $value = isset($record[$header]) ? $record[$header] : '';
                $sheet->setCellValueByColumnAndRow($col, $row, $value);

                // Ajustar ancho de columna
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);

                $col++;
            }
            $row++;
        }
    }

    // Congelar primera fila
    $sheet->freezePane('A2');

    // Escribir archivo
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
}
?>
