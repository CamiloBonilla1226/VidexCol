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
            $columns[$id] = $column;
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
        $sheet->setCellValue('A2', 'Generado el '.date('d/m/Y H:i').' por '.$creator.'   |   '.$dataSet->count().' estudiante(s)');
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
        $row = 6;
        foreach ($dataSet as $data) {
            foreach (array_values($columns) as $i => $column) {
                $value = $this->stripTags($column->getOutput($data, false));
                $sheet->setCellValueExplicit($this->num2alpha($i).$row, $value, DataType::TYPE_STRING);
            }

            $range = 'A'.$row.':'.$lastCol.$row;
            $sheet->getStyle($range)->applyFromArray($border + [
                'font' => ['size' => 11],
                'alignment' => ['vertical' => 'center', 'wrapText' => true],
            ]);
            if ($row % 2 == 1) {
                $sheet->getStyle($range)->applyFromArray(['fill' => $this->fill('F5F8FC')]);
            }
            $row++;
        }

        // Columnas fijas, filtros y opciones de impresión
        $freeze = intval($table->getMetaData('freezeColumns', 2));
        $sheet->freezePane($this->num2alpha($freeze).'6');
        $sheet->setAutoFilter('A5:'.$lastCol.($row - 1));
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
