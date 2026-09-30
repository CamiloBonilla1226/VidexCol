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

        $hasReport = !empty($choices);
        $url = $session->get('absoluteURL').'/index.php?q=/modules/Informes Escolares/report_student_emergencySummary.php';

        echo '<style>
            .es-card { border: 1px solid #d1d5db; border-radius: 6px; background: #fff; margin-bottom: 16px; }
            .es-head { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #f3f4f6; border-radius: 6px 6px 0 0; }
            .es-head strong { font-size: 1.05em; }
            .es-body { padding: 14px; }
            .es-filters { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 10px; }
            .es-filters label { display: block; font-size: 0.85em; font-weight: bold; margin-bottom: 3px; }
            .es-filters input, .es-filters select { width: 100%; min-width: 180px; }
            .es-filters > div { flex: 1 1 200px; }
            .es-tools { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 8px; }
            .es-btn { border: 1px solid #9ca3af; background: #fff; border-radius: 4px; padding: 4px 10px; cursor: pointer; font-size: 0.9em; }
            .es-btn:hover { background: #e5e7eb; }
            .es-min { width: 30px; height: 30px; padding: 0; font-size: 1.3em; line-height: 1; font-weight: bold; }
            .es-btn-main { background: #2563eb; border-color: #2563eb; color: #fff; padding: 7px 18px; font-size: 1em; }
            .es-btn-main:hover { background: #1d4ed8; }
            .es-count { margin-left: auto; font-weight: bold; }
            .es-list { max-height: 320px; overflow-y: auto; border: 1px solid #d1d5db; border-radius: 4px; }
            .es-group { background: #e5e7eb; font-weight: bold; padding: 4px 10px; position: sticky; top: 0; }
            .es-row { display: flex; align-items: center; gap: 8px; padding: 4px 10px; border-bottom: 1px solid #f3f4f6; cursor: pointer; }
            .es-row:hover { background: #eff6ff; }
            .es-row input { width: auto; min-width: 0; margin: 0; }
            .es-row small { color: #6b7280; margin-left: auto; }
            .es-empty { padding: 14px; text-align: center; color: #6b7280; display: none; }
            .es-actions { margin-top: 12px; }
            .es-warn { color: #b91c1c; margin-left: 10px; display: none; }
        </style>';

        echo '<div class="es-card">';
        echo '<div class="es-head"><strong>'.__('Choose Students').'</strong>';
        echo '<button type="button" class="es-btn es-min" id="es-toggle" title="'.($hasReport ? 'Expandir' : 'Minimizar').'" aria-label="Minimizar o expandir">'.($hasReport ? '+' : '&minus;').'</button></div>';
        echo '<div class="es-body" id="es-body"'.($hasReport ? ' style="display:none"' : '').'>';
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
        echo '<button type="button" class="es-btn" id="es-all">Seleccionar visibles</button>';
        echo '<button type="button" class="es-btn" id="es-none">Quitar visibles</button>';
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

        echo '<div class="es-actions"><button type="submit" class="es-btn es-btn-main">Generar reporte</button>';
        echo '<span class="es-warn" id="es-warn">Seleccione al menos un estudiante.</span></div>';
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

    function setVisible(value) {
        rows.forEach(function (r) { if (isVisible(r)) r.querySelector('input').checked = value; });
        updateCount();
    }
    $('es-all').addEventListener('click', function () { setVisible(true); });
    $('es-none').addEventListener('click', function () { setVisible(false); });
    $('es-clear').addEventListener('click', function () {
        rows.forEach(function (r) { r.querySelector('input').checked = false; });
        updateCount();
    });
    $('es-list').addEventListener('change', updateCount);

    $('es-form').addEventListener('submit', function (e) {
        var n = rows.filter(function (r) { return r.querySelector('input').checked; }).length;
        $('es-warn').style.display = n ? 'none' : 'inline';
        if (!n) e.preventDefault();
    });

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

        $table->addColumn('surname', __('Surname'))->width('20');
        $table->addColumn('preferredName', __('Preferred Name'))->width('20');
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
        $table->addColumn('email', __('Email'))->width('30');
        $table->addColumn('phone', 'Teléfono estudiante')->width('20')
            ->format(function ($student) use ($phones) {
                return $phones($student);
            });

        foreach ([0 => 'Acudiente 1', 1 => 'Acudiente 2'] as $index => $label) {
            $table->addColumn('adultName'.$index, $label)->width('28')
                ->format(function ($student) use ($adultField, $index) { return $adultField($student, $index, 'name'); });
            $table->addColumn('adultRel'.$index, $label.' - Parentesco')->width('18')
                ->format(function ($student) use ($adultField, $index) { return $adultField($student, $index, 'rel'); });
            $table->addColumn('adultPhone'.$index, $label.' - Teléfonos')->width('30')
                ->format(function ($student) use ($adultField, $index) { return $adultField($student, $index, 'phone'); });
            $table->addColumn('adultEmail'.$index, $label.' - Correo')->width('30')
                ->format(function ($student) use ($adultField, $index) { return $adultField($student, $index, 'email'); });
        }

        foreach ([1 => 'Emergencia 1', 2 => 'Emergencia 2'] as $n => $label) {
            $table->addColumn('emergency'.$n.'Name', $label)->width('28')
                ->format(function ($student) use ($n) { return $student['emergency'.$n.'Name'] ?? ''; });
            $table->addColumn('emergency'.$n.'Rel', $label.' - Parentesco')->width('18')
                ->format(function ($student) use ($n) { return $student['emergency'.$n.'Relationship'] ?? ''; });
            $table->addColumn('emergency'.$n.'Num1', $label.' - Teléfono 1')->width('20')
                ->format(function ($student) use ($n) {
                    return !empty($student['emergency'.$n.'Number1']) ? Format::phone($student['emergency'.$n.'Number1']) : '';
                });
            $table->addColumn('emergency'.$n.'Num2', $label.' - Teléfono 2')->width('20')
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

    echo $table->render($students);
}
