<?php

namespace Modules\InformesEscolares;

use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\Columns\ActionColumn;
use Gibbon\Tables\Columns\ExpandableColumn;
use Gibbon\Tables\Renderer\SpreadsheetRenderer;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Exporta una tabla a Excel con un diseño más cuidado que el predeterminado de Gibbon:
 * título, datos de generación, bandas de color por sección, filas alternadas, paneles
 * fijos, filtros en los encabezados e impresión horizontal.
 *
 * Metadatos de la tabla que usa:
 *  - groups:        [idColumna => 'Nombre de la sección'] (las secciones se colorean en orden)
 *  - freezeColumns: cantidad de columnas que quedan fijas al desplazarse (por defecto 2)
 *  - sheetTitle:    nombre de la hoja (por defecto 'Reporte')
 *  - subtitle:      texto que se agrega a la segunda línea (por ejemplo, el período consultado)
 *  - countLabel:    texto después del total de filas (por defecto 'estudiante(s)'); false para ocultarlo
 *  - numericColumns:[idColumna => 'formato Excel'] columnas que se guardan como número (ej. '0', '0.0')
 *  - Si una fila trae el dato '_isTotal' => true se resalta como fila de totales
 *  - creator, filename: los define ReportTable::setViewMode()
 */
class StyledSpreadsheetRenderer extends SpreadsheetRenderer
{
    // [banda oscura, encabezado claro]
    protected $palette = [
        ['1F4E78', 'D9E2F3'],
        ['2E75B6', 'DDEBF7'],
        ['548235', 'E2EFDA'],
        ['C55A11', 'FCE4D6'],
        ['7F6000', 'FFF2CC'],
    ];

    protected function fill($rgb)
    {
        return ['fillType' => 'solid', 'startColor' => ['rgb' => $rgb]];
    }

    public function renderTable(DataTable $table, DataSet $dataSet)
    {
        $creator = $table->getMetaData('creator');
        $sheet = $this->sheet;

        $this->excel->getProperties()->setCreator($creator)->setLastModifiedBy($creator)
            ->setTitle($table->getTitle())
            ->setDescription('Información confidencial. Generado por Gibbon.');
        $sheet->setTitle($table->getMetaData('sheetTitle', 'Reporte'));

        $columns = [];
        foreach ($table->getColumns() as $id => $column) {
            if ($column instanceof ActionColumn || $column instanceof ExpandableColumn) continue;
            $columns[$column->getID()] = $column;
        }

        if (empty($columns) || $dataSet->count() == 0) {
            $sheet->setCellValue('A1', 'La consulta no devolvió estudiantes.');
            $this->save($table);
            return;
        }

        $groups = $table->getMetaData('groups', []);
        $ids = array_keys($columns);
        $lastCol = $this->num2alpha(count($ids) - 1);
        $border = ['borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'C9D2E0']]]];

        // Color de cada sección, según el orden en que aparece
        $colors = [];
        foreach ($ids as $id) {
            $group = $groups[$id] ?? 'General';
            if (!isset($colors[$group])) {
                $colors[$group] = $this->palette[count($colors) % count($this->palette)];
            }
        }

        // Fila 1: título
        $sheet->setCellValue('A1', $table->getTitle());
        $sheet->mergeCells('A1:'.$lastCol.'1');
        $sheet->getStyle('A1:'.$lastCol.'1')->applyFromArray([
            'fill' => $this->fill('1F4E78'),
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['vertical' => 'center', 'indent' => 1],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(34);

        // Fila 2: datos de la generación
        $countLabel = $table->getMetaData('countLabel', 'estudiante(s)');
        $info = 'Generado el '.date('d/m/Y H:i').' por '.$creator;
        if ($countLabel !== false) $info .= '   |   '.$dataSet->count().' '.$countLabel;
        if ($table->getMetaData('subtitle')) $info .= '   |   '.$table->getMetaData('subtitle');
        $sheet->setCellValue('A2', $info);
        $sheet->mergeCells('A2:'.$lastCol.'2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '595959']],
            'alignment' => ['vertical' => 'center', 'indent' => 1],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(6);

        // Fila 4: bandas de sección | Fila 5: encabezados de columna
        $groupStart = 0;
        foreach ($ids as $i => $id) {
            $group = $groups[$id] ?? 'General';
            $alpha = $this->num2alpha($i);

            $width = intval($columns[$id]->getWidth());
            $sheet->getColumnDimension($alpha)->setWidth($width > 0 ? $width : 18);

            $sheet->setCellValue($alpha.'5', $columns[$id]->getLabel());
            $sheet->getStyle($alpha.'5')->applyFromArray($border + [
                'fill' => $this->fill($colors[$group][1]),
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1F1F1F']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            ]);

            // Cierra la banda cuando cambia la sección
            $next = isset($ids[$i + 1]) ? ($groups[$ids[$i + 1]] ?? 'General') : null;
            if ($next !== $group) {
                $first = $this->num2alpha($groupStart);
                $sheet->setCellValue($first.'4', $group);
                if ($groupStart < $i) $sheet->mergeCells($first.'4:'.$alpha.'4');
                $sheet->getStyle($first.'4:'.$alpha.'4')->applyFromArray($border + [
                    'fill' => $this->fill($colors[$group][0]),
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                ]);
                $groupStart = $i + 1;
            }
        }
        $sheet->getRowDimension(4)->setRowHeight(22);
        $sheet->getRowDimension(5)->setRowHeight(30);

        // Filas de datos
        $numeric = $table->getMetaData('numericColumns', []);
        $row = 6;
        foreach ($dataSet as $data) {
            foreach (array_values($ids) as $i => $id) {
                $value = $this->stripTags($columns[$id]->getOutput($data, false));
                $cell = $this->num2alpha($i).$row;
                if (isset($numeric[$id]) && is_numeric($value)) {
                    $sheet->setCellValueExplicit($cell, $value + 0, DataType::TYPE_NUMERIC);
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($numeric[$id]);
                    $sheet->getStyle($cell)->getAlignment()->setHorizontal('center');
                } else {
                    $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
                }
            }

            $range = 'A'.$row.':'.$lastCol.$row;
            $sheet->getStyle($range)->applyFromArray($border + [
                'font' => ['size' => 11],
                'alignment' => ['vertical' => 'center', 'wrapText' => true],
            ]);
            if ($row % 2 == 1) {
                $sheet->getStyle($range)->applyFromArray(['fill' => $this->fill('F5F8FC')]);
            }
            if (!empty($data['_isTotal'])) {
                $sheet->getStyle($range)->applyFromArray([
                    'fill' => $this->fill('DCE6F1'),
                    'font' => ['bold' => true, 'size' => 11],
                    'borders' => ['top' => ['borderStyle' => 'medium', 'color' => ['rgb' => '1F4E78']]],
                ]);
            }
            $row++;
        }

        // Columnas fijas, filtros y opciones de impresión
        $freeze = intval($table->getMetaData('freezeColumns', 2));
        $sheet->freezePane($this->num2alpha($freeze).'6');
        // La fila de totales (si existe, es la última) queda fuera del filtro para que no se ordene con los datos
        $filterEnd = (!empty($data['_isTotal']) && $row - 2 > 5) ? $row - 2 : $row - 1;
        $sheet->setAutoFilter('A5:'.$lastCol.$filterEnd);
        $setup = $sheet->getPageSetup();
        $setup->setOrientation('landscape');
        $setup->setFitToWidth(1);
        $setup->setFitToHeight(0);
        $setup->setFitToPage(true);
        $setup->setRowsToRepeatAtTopByStartAndEnd(4, 5);
        $sheet->getHeaderFooter()->setOddFooter('&L&F&RPágina &P de &N');
        $sheet->getSheetView()->setZoomScale(90);

        if ($table->getMetaData('tableCount') <= 1) {
            $this->save($table);
        }
    }
}
