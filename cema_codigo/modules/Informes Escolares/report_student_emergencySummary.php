<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Domain\System\SettingGateway;
use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\Students\StudentReportGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Informes Escolares/report_student_emergencySummary.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $choices = $_POST['gibbonPersonID'] ?? [];
    //If $choices is blank, check to see if session is being used to inject gibbonPersonID list
    if (count((array) $choices) == 0 && $session->has('report_student_emergencySummary.php_choices')) {
        $choices = $session->get('report_student_emergencySummary.php_choices');
    }
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    if (isset($_GET['gibbonPersonIDList'])) {
        $choices = explode(',', $_GET['gibbonPersonIDList']);
    }

    // Solo IDs numéricos, sin repetidos. Se dejan como texto: los IDs de Gibbon llevan ceros a la izquierda (0000001234)
    $choices = array_values(array_unique(array_filter(array_map('strval', (array) $choices), 'ctype_digit')));
    $_GET['gibbonPersonIDList'] = implode(',', $choices);

    // Curso y grupo de cada estudiante activo del año escolar (para el filtro y para las tablas)
    $sqlStudents = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName,
            gibbonYearGroup.gibbonYearGroupID, gibbonYearGroup.name AS yearGroup,
            gibbonFormGroup.gibbonFormGroupID, gibbonFormGroup.name AS formGroup
        FROM gibbonPerson
        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
        JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
        JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
        AND gibbonPerson.status='Full'
        AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)
        AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)
        ORDER BY gibbonYearGroup.sequenceNumber, gibbonFormGroup.name, gibbonPerson.surname, gibbonPerson.preferredName";
    $studentList = $pdo->select($sqlStudents, ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'today' => date('Y-m-d')])->fetchAll();

    $enrolment = [];
    foreach ($studentList as $s) {
        $enrolment[$s['gibbonPersonID']] = $s;
    }

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Student Emergency Data Summary'));

        echo '<p>';
        echo __('This report prints a summary of emergency data for the selected students. In case of emergency, please try to contact parents first, and if they cannot be reached then contact the listed emergency contacts.');
        echo '</p>';

        $h = function ($text) {
            return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
        };

        // Listas para los desplegables de curso y grupo
        $yearGroups = [];
        $formGroups = [];
        foreach ($studentList as $s) {
            $yearGroups[$s['gibbonYearGroupID']] = $s['yearGroup'];
            $formGroups[$s['gibbonFormGroupID']] = ['name' => $s['formGroup'], 'year' => $s['gibbonYearGroupID']];
        }

        $url = $session->get('absoluteURL').'/index.php?q=/modules/Informes Escolares/report_student_emergencySummary.php';

        echo '<style>
            .es-card { border: 1px solid #3575EF; border-radius: 8px; background: #fff; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(53,117,239,.15); overflow: hidden; }
            .es-head { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #3575EF; color: #fff; }
            .es-head strong { font-size: 1.1em; letter-spacing: .2px; }
            .es-body { padding: 16px; background: #F5F8FF; }
            .es-filters { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 12px; }
            .es-filters label { display: block; font-size: 0.85em; font-weight: bold; margin-bottom: 4px; color: #1E3A8A; }
            .es-filters input, .es-filters select { width: 100%; min-width: 180px; border: 1px solid #9DB8F5; border-radius: 6px; background: #fff; }
            .es-filters input:focus, .es-filters select:focus { outline: 2px solid #3575EF; outline-offset: 0; border-color: #3575EF; }
            .es-filters > div { flex: 1 1 200px; }
            .es-tools { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 10px; }
            .es-btn { border: 1px solid #3575EF; background: #fff; color: #2555B8; border-radius: 6px; padding: 6px 14px; cursor: pointer; font-size: 0.9em; font-weight: 600; }
            .es-btn:hover { background: #E3ECFF; }
            .es-btn-main { background: #3575EF; color: #fff; }
            .es-btn-main:hover { background: #2B60CF; }
            .es-min { width: 30px; height: 30px; padding: 0; font-size: 1.3em; line-height: 1; font-weight: bold; background: transparent; color: #fff; border-color: #fff; }
            .es-min:hover { background: rgba(255,255,255,.25); }
            .es-count { margin-left: auto; font-weight: bold; color: #1E3A8A; background: #DCE7FD; border-radius: 999px; padding: 4px 14px; }
            .es-list { max-height: 320px; overflow-y: auto; border: 1px solid #9DB8F5; border-radius: 6px; background: #fff; }
            .es-group { background: #DCE7FD; color: #1E3A8A; font-weight: bold; padding: 5px 12px; position: sticky; top: 0; border-bottom: 1px solid #9DB8F5; }
            .es-row { display: flex; align-items: center; gap: 10px; padding: 6px 12px; border-bottom: 1px solid #EEF2FA; cursor: pointer; color: #1F2937; }
            .es-row:hover { background: #EEF4FF; }
            .es-row input { width: auto; min-width: 0; margin: 0; accent-color: #3575EF; }
            .es-row small { color: #4B5563; margin-left: auto; }
            .es-empty { padding: 14px; text-align: center; color: #4B5563; display: none; }
            .es-row.es-selected { background: #c3c8d0; font-weight: 600; box-shadow: inset 5px 0 0 #3575EF; }
            .es-row.es-selected:hover { background: #b4bac4; }
            .es-hint { margin-top: 10px; color: #4B5563; font-size: 0.9em; }
            #es-result { transition: opacity .2s; }
        </style>';

        echo '<div class="es-card">';
        echo '<div class="es-head"><strong>'.__('Choose Students').'</strong>';
        echo '<button type="button" class="es-btn es-min" id="es-toggle" title="Minimizar" aria-label="Minimizar o expandir">&minus;</button></div>';
        echo '<div class="es-body" id="es-body">';
        echo '<form method="post" action="'.$h($url).'" id="es-form">';

        echo '<div class="es-filters">';
        echo '<div><label for="es-search">Buscar estudiante</label><input type="text" id="es-search" placeholder="Escriba un nombre o apellido" autocomplete="off"></div>';
        echo '<div><label for="es-year">Curso</label><select id="es-year"><option value="">Todos los cursos</option>';
        foreach ($yearGroups as $id => $name) {
            echo '<option value="'.$h($id).'">'.$h($name).'</option>';
        }
        echo '</select></div>';
        echo '<div><label for="es-group">Grupo</label><select id="es-group"><option value="" data-year="">Todos los grupos</option>';
        foreach ($formGroups as $id => $g) {
            echo '<option value="'.$h($id).'" data-year="'.$h($g['year']).'">'.$h($g['name']).'</option>';
        }
        echo '</select></div>';
        echo '</div>';

        echo '<div class="es-tools">';
        echo '<button type="button" class="es-btn es-btn-main" id="es-all">Seleccionar todo</button>';
        echo '<button type="button" class="es-btn" id="es-clear">Limpiar selección</button>';
        echo '<span class="es-count" id="es-count">0 seleccionados</span>';
        echo '</div>';

        echo '<div class="es-list" id="es-list">';
        $lastGroup = null;
        foreach ($studentList as $s) {
            if ($lastGroup !== $s['gibbonFormGroupID']) {
                $lastGroup = $s['gibbonFormGroupID'];
                echo '<div class="es-group" data-year="'.$h($s['gibbonYearGroupID']).'" data-group="'.$h($s['gibbonFormGroupID']).'">'.$h($s['yearGroup']).' - '.$h($s['formGroup']).'</div>';
            }
            $fullName = $s['surname'].', '.$s['preferredName'];
            echo '<label class="es-row" data-year="'.$h($s['gibbonYearGroupID']).'" data-group="'.$h($s['gibbonFormGroupID']).'" data-name="'.$h(mb_strtolower($fullName, 'UTF-8')).'">';
            echo '<input type="checkbox" name="gibbonPersonID[]" value="'.$h($s['gibbonPersonID']).'"'.(in_array((string) $s['gibbonPersonID'], $choices, true) ? ' checked' : '').'>';
            echo '<span>'.$h($fullName).'</span><small>'.$h($s['formGroup']).'</small>';
            echo '</label>';
        }
        echo '<div class="es-empty" id="es-empty">No hay estudiantes con esos filtros.</div>';
        echo '</div>';

        echo '<div class="es-hint">El reporte se actualiza solo al marcar o desmarcar estudiantes.</div>';
        echo '</form></div></div>';

        echo <<<'JS'
<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var rows = Array.prototype.slice.call(document.querySelectorAll('#es-list .es-row'));
    var heads = Array.prototype.slice.call(document.querySelectorAll('#es-list .es-group'));
    var groupOptions = Array.prototype.slice.call($('es-group').options);

    function isVisible(row) { return row.style.display !== 'none'; }

    function updateCount() {
        var n = rows.filter(function (r) { return r.querySelector('input').checked; }).length;
        $('es-count').textContent = n + (n === 1 ? ' seleccionado' : ' seleccionados');
        rows.forEach(function (r) { r.classList.toggle('es-selected', r.querySelector('input').checked); });
    }

    // Actualiza el reporte sin recargar la página
    var timer = null, controller = null;
    function refresh() {
        var box = $('es-result');
        if (controller) controller.abort();
        controller = window.AbortController ? new AbortController() : null;
        box.style.opacity = '0.5';
        fetch($('es-form').action, {
            method: 'POST',
            body: new FormData($('es-form')),
            credentials: 'same-origin',
            signal: controller ? controller.signal : undefined
        }).then(function (r) { return r.text(); }).then(function (html) {
            var res = new DOMParser().parseFromString(html, 'text/html').getElementById('es-result');
            if (!res) throw new Error('sin resultado');
            box.innerHTML = res.innerHTML;
            // Los scripts insertados con innerHTML no se ejecutan: se recrean para que la tabla funcione
            Array.prototype.forEach.call(box.querySelectorAll('script'), function (old) {
                var sc = document.createElement('script');
                sc.text = old.text;
                old.parentNode.replaceChild(sc, old);
            });
            box.style.opacity = '';
        }).catch(function (e) {
            if (e && e.name === 'AbortError') return;
            box.style.opacity = '';
            box.innerHTML = '<p style="color:#b91c1c">No se pudo actualizar el reporte. Recargue la página e intente de nuevo.</p>';
        });
    }
    function changed() {
        updateCount();
        clearTimeout(timer);
        timer = setTimeout(refresh, 600);
    }

    function applyFilters() {
        var q = $('es-search').value.toLowerCase().trim();
        var year = $('es-year').value;
        var group = $('es-group').value;
        var shown = 0;
        rows.forEach(function (r) {
            var ok = (!q || r.getAttribute('data-name').indexOf(q) !== -1)
                && (!year || r.getAttribute('data-year') === year)
                && (!group || r.getAttribute('data-group') === group);
            r.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });
        heads.forEach(function (hd) {
            var any = rows.some(function (r) { return isVisible(r) && r.getAttribute('data-group') === hd.getAttribute('data-group'); });
            hd.style.display = any ? '' : 'none';
        });
        $('es-empty').style.display = shown ? 'none' : 'block';
    }

    // Los grupos disponibles dependen del curso elegido
    $('es-year').addEventListener('change', function () {
        var year = this.value;
        groupOptions.forEach(function (o) {
            var show = !o.value || !year || o.getAttribute('data-year') === year;
            o.hidden = !show;
            o.disabled = !show;
        });
        $('es-group').value = '';
        applyFilters();
    });
    $('es-group').addEventListener('change', applyFilters);
    $('es-search').addEventListener('input', applyFilters);

    // Selecciona todos los estudiantes que se ven con los filtros actuales
    $('es-all').addEventListener('click', function () {
        rows.forEach(function (r) { if (isVisible(r)) r.querySelector('input').checked = true; });
        changed();
    });
    $('es-clear').addEventListener('click', function () {
        rows.forEach(function (r) { r.querySelector('input').checked = false; });
        changed();
    });
    $('es-list').addEventListener('change', changed);
    // Sin JavaScript de envío: si alguien pulsa Enter en el buscador no se recarga la página
    $('es-form').addEventListener('submit', function (e) { e.preventDefault(); });

    $('es-toggle').addEventListener('click', function () {
        var body = $('es-body');
        var hidden = body.style.display === 'none';
        body.style.display = hidden ? '' : 'none';
        this.innerHTML = hidden ? '&minus;' : '+';
        this.title = hidden ? 'Minimizar' : 'Expandir';
    });

    updateCount();
})();
</script>
JS;
    }

    if (empty($choices)) {
        if (empty($viewMode)) {
            echo '<div id="es-result"></div>';
        }
        return;
    }

    $cutoffDate = $container->get(SettingGateway::class)->getSettingByScope('Data Updater', 'cutoffDate');
    if (empty($cutoffDate)) $cutoffDate = Format::dateFromTimestamp(time() - (604800 * 26));

    $reportGateway = $container->get(StudentReportGateway::class);
    $familyGateway = $container->get(FamilyGateway::class);

    // CRITERIA
    $criteria = $reportGateway->newQueryCriteria(true)
        ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();

    $students = $reportGateway->queryStudentDetails($criteria, $choices);

    // Join a set of family adults per student
    $people = $students->getColumn('gibbonPersonID');
    $familyAdults = $familyGateway->selectFamilyAdultsByStudent($people, true)->fetchGrouped();
    $students->joinColumn('gibbonPersonID', 'familyAdults', $familyAdults);

    // DATA TABLE
    $table = ReportTable::createPaginated('studentEmergencySummary', $criteria)->setViewMode($viewMode, $session);
    $table->setTitle(__('Student Emergency Data Summary'));
    $table->addMetaData('filename', 'ResumenEmergencia_'.date('Y-m-d'));

    $table->addMetaData('post', ['gibbonPersonID' => $choices]);

    if ($viewMode == 'export') {
        // EXCEL: diseño propio (título, bandas de color por sección, filtros, paneles fijos, impresión horizontal)
        if (!class_exists('EmergencySpreadsheetRenderer')) {
            class EmergencySpreadsheetRenderer extends \Gibbon\Tables\Renderer\SpreadsheetRenderer
            {
                protected function groupOf($id)
                {
                    if (preg_match('/^adult\w+(\d)$/', $id, $m)) return 'Acudiente '.($m[1] + 1);
                    if (preg_match('/^emergency(\d)/', $id, $m)) return 'Emergencia '.$m[1];
                    return 'Estudiante';
                }

                protected function fill($rgb)
                {
                    return ['fillType' => 'solid', 'startColor' => ['rgb' => $rgb]];
                }

                public function renderTable(\Gibbon\Tables\DataTable $table, \Gibbon\Domain\DataSet $dataSet)
                {
                    $creator = $table->getMetaData('creator');
                    $this->excel->getProperties()->setCreator($creator)->setLastModifiedBy($creator)
                        ->setTitle($table->getTitle())
                        ->setDescription('Información confidencial. Generado por Gibbon.');
                    $this->sheet->setTitle('Emergencia');

                    $columns = [];
                    foreach ($table->getColumns() as $id => $column) {
                        if ($column instanceof \Gibbon\Tables\Columns\ActionColumn || $column instanceof \Gibbon\Tables\Columns\ExpandableColumn) continue;
                        $columns[$column->getID()] = $column;
                    }
                    if (empty($columns) || $dataSet->count() == 0) {
                        $this->sheet->setCellValue('A1', 'La consulta no devolvió estudiantes.');
                        $this->save($table);
                        return;
                    }

                    // Colores por sección: [banda oscura, encabezado claro]
                    $palette = [
                        'Estudiante'   => ['1F4E78', 'D9E2F3'],
                        'Acudiente 1'  => ['2E75B6', 'DDEBF7'],
                        'Acudiente 2'  => ['548235', 'E2EFDA'],
                        'Emergencia 1' => ['C55A11', 'FCE4D6'],
                        'Emergencia 2' => ['7F6000', 'FFF2CC'],
                    ];
                    $border = ['borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'C9D2E0']]]];
                    $ids = array_keys($columns);
                    $lastCol = $this->num2alpha(count($columns) - 1);
                    $sheet = $this->sheet;

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
                    $sheet->setCellValue('A2', 'Generado el '.date('d/m/Y H:i').' por '.$creator.'   |   '.$dataSet->count().' estudiante(s)   |   Las fechas en rojo indican datos sin actualizar');
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
                        $group = $this->groupOf($id);
                        $alpha = $this->num2alpha($i);
                        $colors = $palette[$group];

                        $width = intval($columns[$id]->getWidth());
                        $sheet->getColumnDimension($alpha)->setWidth($width > 0 ? $width : 18);

                        $sheet->setCellValue($alpha.'5', $columns[$id]->getLabel());
                        $sheet->getStyle($alpha.'5')->applyFromArray($border + [
                            'fill' => $this->fill($colors[1]),
                            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1F1F1F']],
                            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                        ]);

                        // Cierra la banda cuando cambia la sección
                        $next = isset($ids[$i + 1]) ? $this->groupOf($ids[$i + 1]) : null;
                        if ($next !== $group) {
                            $first = $this->num2alpha($groupStart);
                            $sheet->setCellValue($first.'4', $group);
                            if ($groupStart < $i) $sheet->mergeCells($first.'4:'.$alpha.'4');
                            $sheet->getStyle($first.'4:'.$alpha.'4')->applyFromArray($border + [
                                'fill' => $this->fill($colors[0]),
                                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                            ]);
                            $groupStart = $i + 1;
                        }
                    }
                    $sheet->getRowDimension(4)->setRowHeight(22);
                    $sheet->getRowDimension(5)->setRowHeight(30);

                    // Filas de datos
                    $cutoff = $table->getMetaData('cutoffDate');
                    $updatePos = array_search('lastUpdate', $ids);
                    $row = 6;
                    foreach ($dataSet as $data) {
                        foreach (array_values($columns) as $i => $column) {
                            $value = $this->stripTags($column->getOutput($data, false));
                            $sheet->setCellValueExplicit($this->num2alpha($i).$row, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        }

                        $range = 'A'.$row.':'.$lastCol.$row;
                        $sheet->getStyle($range)->applyFromArray($border + [
                            'font' => ['size' => 11],
                            'alignment' => ['vertical' => 'center', 'wrapText' => true],
                        ]);
                        if ($row % 2 == 1) {
                            $sheet->getStyle($range)->applyFromArray(['fill' => $this->fill('F5F8FC')]);
                        }

                        // Fecha de actualización en rojo si está vencida o no existe
                        if ($updatePos !== false && (empty($data['lastPersonalUpdate']) || (!empty($cutoff) && $data['lastPersonalUpdate'] < $cutoff))) {
                            $sheet->getStyle($this->num2alpha($updatePos).$row)->applyFromArray([
                                'fill' => $this->fill('FDE9E7'),
                                'font' => ['bold' => true, 'color' => ['rgb' => 'C00000']],
                            ]);
                        }
                        $row++;
                    }

                    // Nombre y apellido siempre visibles, filtros y opciones de impresión
                    $sheet->freezePane('C6');
                    $sheet->setAutoFilter('A5:'.$lastCol.($row - 1));
                    $setup = $sheet->getPageSetup();
                    $setup->setOrientation('landscape');
                    $setup->setFitToWidth(1);
                    $setup->setFitToHeight(0);
                    $setup->setRowsToRepeatAtTopByStartAndEnd(4, 5);
                    $sheet->getPageSetup()->setFitToPage(true);
                    $sheet->getHeaderFooter()->setOddFooter('&L&F&RPágina &P de &N');
                    $sheet->getSheetView()->setZoomScale(90);

                    if ($table->getMetaData('tableCount') <= 1) {
                        $this->save($table);
                    }
                }
            }
        }
        $table->setRenderer(new EmergencySpreadsheetRenderer());
        $table->addMetaData('cutoffDate', $cutoffDate);

        // EXCEL: una columna por dato (sin texto apilado) para que se lea bien en la hoja de cálculo
        $phones = function ($person) {
            $list = [];
            foreach ([1, 2, 3, 4] as $i) {
                if (!empty($person['phone'.$i])) {
                    $list[] = Format::phone($person['phone'.$i], $person['phone'.$i.'CountryCode']);
                }
            }
            return implode(' / ', $list);
        };

        // Para el adulto 2 en adelante se juntan los datos, separados por " | "
        $adultField = function ($student, $index, $field) use ($phones) {
            $adults = array_values($student['familyAdults'] ?? []);
            $selected = ($index == 0) ? array_slice($adults, 0, 1) : array_slice($adults, 1);
            $values = [];
            foreach ($selected as $adult) {
                switch ($field) {
                    case 'name':  $values[] = Format::name('', $adult['preferredName'], $adult['surname'], 'Parent', false, true); break;
                    case 'rel':   $values[] = $adult['relationship'] ?? ''; break;
                    case 'phone': $values[] = $phones($adult); break;
                    case 'email': $values[] = $adult['email'] ?? ''; break;
                }
            }
            return implode(' | ', array_filter($values));
        };

        $table->addColumn('surname', 'Apellidos')->width('20');
        $table->addColumn('preferredName', 'Nombres')->width('20');
        $table->addColumn('yearGroup', 'Curso')->width('14')
            ->format(function ($student) use ($enrolment) {
                return $enrolment[$student['gibbonPersonID']]['yearGroup'] ?? '';
            });
        $table->addColumn('formGroup', 'Grupo')->width('12')
            ->format(function ($student) use ($enrolment) {
                return $enrolment[$student['gibbonPersonID']]['formGroup'] ?? '';
            });
        $table->addColumn('lastUpdate', 'Última actualización')->width('20')
            ->format(function ($student) {
                return !empty($student['lastPersonalUpdate']) ? Format::date($student['lastPersonalUpdate']) : __('N/A');
            });
        $table->addColumn('email', 'Correo electrónico')->width('30');
        $table->addColumn('phone', 'Teléfono')->width('20')
            ->format(function ($student) use ($phones) {
                return $phones($student);
            });

        foreach ([0 => 'Acudiente 1', 1 => 'Acudiente 2'] as $index => $label) {
            $table->addColumn('adultName'.$index, 'Nombre')->width('28')
                ->format(function ($student) use ($adultField, $index) { return $adultField($student, $index, 'name'); });
            $table->addColumn('adultRel'.$index, 'Parentesco')->width('18')
                ->format(function ($student) use ($adultField, $index) { return $adultField($student, $index, 'rel'); });
            $table->addColumn('adultPhone'.$index, 'Teléfonos')->width('30')
                ->format(function ($student) use ($adultField, $index) { return $adultField($student, $index, 'phone'); });
            $table->addColumn('adultEmail'.$index, 'Correo')->width('30')
                ->format(function ($student) use ($adultField, $index) { return $adultField($student, $index, 'email'); });
        }

        foreach ([1 => 'Emergencia 1', 2 => 'Emergencia 2'] as $n => $label) {
            $table->addColumn('emergency'.$n.'Name', 'Nombre')->width('28')
                ->format(function ($student) use ($n) { return $student['emergency'.$n.'Name'] ?? ''; });
            $table->addColumn('emergency'.$n.'Rel', 'Parentesco')->width('18')
                ->format(function ($student) use ($n) { return $student['emergency'.$n.'Relationship'] ?? ''; });
            $table->addColumn('emergency'.$n.'Num1', 'Teléfono 1')->width('20')
                ->format(function ($student) use ($n) {
                    return !empty($student['emergency'.$n.'Number1']) ? Format::phone($student['emergency'.$n.'Number1']) : '';
                });
            $table->addColumn('emergency'.$n.'Num2', 'Teléfono 2')->width('20')
                ->format(function ($student) use ($n) {
                    return !empty($student['emergency'.$n.'Number2']) ? Format::phone($student['emergency'.$n.'Number2']) : '';
                });
        }

        echo $table->render($students);
        return;
    }

    $table->addColumn('student', __('Student'))
        ->description(__('Last Update'))
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->format(function ($student) use ($cutoffDate, $enrolment) {
            $output = Format::name('', $student['preferredName'], $student['surname'], 'Student', true, true).'<br/>';

            if (!empty($enrolment[$student['gibbonPersonID']])) {
                $e = $enrolment[$student['gibbonPersonID']];
                $output .= '<span style="color: #6b7280">'.htmlspecialchars($e['yearGroup'].' - '.$e['formGroup']).'</span><br/>';
            }

            $output .= ($student['lastPersonalUpdate'] < $cutoffDate) ? '<span style="color: #ff0000; font-weight: bold"><i>' : '<span><i>';
            $output .= !empty($student['lastPersonalUpdate']) ? Format::date($student['lastPersonalUpdate']) : __('N/A');
            $output .= '</i></span>';

            $output .= '<br/><br/>';
            $output .= '<i>'.__('Email').'</i>: '.$student['email'].'<br/>';
            $output .= Format::phone($student['phone1'], $student['phone1CountryCode'], '<i>'.$student['phone1Type'].'<i>');

            return $output;
        });

    $view = new View($container->get('twig'));
    $table->addColumn('contacts', __('Parents'))
        ->width('25%')
        ->notSortable()
        ->format(function ($student) use ($view) {
            return $view->fetchFromTemplate(
                'formats/familyContacts.twig.html',
                ['familyAdults' => $student['familyAdults'], 'includePhoneNumbers' => true]
            );
        });

    $table->addColumn('emergency1', __('Emergency Contact 1'))
        ->width('25%')
        ->sortable('emergency1Name')
        ->format(function ($student) use ($view) {
            return $view->fetchFromTemplate(
                'formats/emergencyContact.twig.html',
                [
                    'name'         => $student['emergency1Name'],
                    'number1'      => $student['emergency1Number1'],
                    'number2'      => $student['emergency1Number2'],
                    'relationship' => $student['emergency1Relationship'],
                ]
            );
        });

    $table->addColumn('emergency2', __('Emergency Contact 2'))
        ->width('25%')
        ->sortable('emergency2Name')
        ->format(function ($student) use ($view) {
            return $view->fetchFromTemplate(
                'formats/emergencyContact.twig.html',
                [
                    'name'         => $student['emergency2Name'],
                    'number1'      => $student['emergency2Number1'],
                    'number2'      => $student['emergency2Number2'],
                    'relationship' => $student['emergency2Relationship'],
                ]
            );
        });

    if (empty($viewMode)) {
        echo '<div id="es-result">';
    }
    echo $table->render($students);
    if (empty($viewMode)) {
        echo '</div>';
    }
}
