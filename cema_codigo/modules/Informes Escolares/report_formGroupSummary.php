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

use Gibbon\Forms\Form;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Students\StudentReportGateway;
use Modules\InformesEscolares\StyledSpreadsheetRenderer;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Informes Escolares/report_formGroupSummary.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $today = time();
    $dateFrom = $_GET['dateFrom'] ?? '';
    $dateTo = $_GET['dateTo'] ?? '';
    $dateFormatPHP = $session->get('i18n')['dateFormatPHP'];

    // Si solo se escribe una fecha se completa la otra. Se hace fuera del formulario para que
    // la pantalla, la impresión y el Excel usen exactamente las mismas fechas.
    if (empty($dateFrom) && !empty($dateTo)) {
        $dateFrom = date($dateFormatPHP);
    }
    if (empty($dateTo) && !empty($dateFrom)) {
        $dateTo = (Format::timestamp(Format::dateConvert($dateFrom)) > $today) ? $dateFrom : date($dateFormatPHP);
    }
    $hasDates = !empty($dateFrom) || !empty($dateTo);

    if (empty($viewMode)) {
        $page->breadcrumbs->add('Reporte de edad promedio / sexo');

        echo '<h2>Elegir opciones</h2>';

        echo '<p>';
        echo 'Este reporte muestra, por cada grupo, la edad promedio y la cantidad de estudiantes por sexo. ';
        echo 'Por defecto cuenta a los estudiantes matriculados en el año escolar actual cuyo estado es <b>Activo</b>.';
        echo '</p>';
        echo '<p>';
        echo 'Si escribe fechas, el reporte muestra cómo estaba el colegio en ese período: cuenta a los estudiantes cuya fecha de inicio es ';
        echo 'anterior o igual a la fecha <b>Desde</b> y cuya fecha de salida es posterior o igual a la fecha <b>Hasta</b> ';
        echo '(o que no tienen esas fechas), sin importar su estado actual. La edad promedio siempre se calcula con la fecha de hoy.';
        echo '</p>';

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$session->get('module')."/report_formGroupSummary.php");

        $row = $form->addRow();
            $row->addLabel('dateFrom', 'Desde')->description('La fecha de inicio del estudiante debe ser anterior o igual a esta fecha.')->append('<br/>')->append('Formato: ')->append($session->get('i18n')['dateFormat']);
            $row->addDate('dateFrom')->setValue($dateFrom);

        $row = $form->addRow();
            $row->addLabel('dateTo', 'Hasta')->description('La fecha de salida del estudiante debe ser posterior o igual a esta fecha.')->append('<br/>')->append('Formato: ')->append($session->get('i18n')['dateFormat']);
            $row->addDate('dateTo')->setValue($dateTo);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session, 'Limpiar filtros');

        echo $form->getOutput();

        echo '<p><i>';
        echo $hasDates
            ? 'Período consultado: del '.htmlspecialchars($dateFrom).' al '.htmlspecialchars($dateTo).'.'
            : 'Mostrando los estudiantes activos del año escolar actual.';
        echo '</i></p>';
    }

    $reportGateway = $container->get(StudentReportGateway::class);

    // CRITERIA (solo para la tabla; los datos se consultan abajo)
    $criteria = $reportGateway->newQueryCriteria();

    // CONSULTA: estudiantes por grupo. Los filtros de fecha usan parámetros distintos (:dateFrom y :dateTo).
    $where = ['gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID'];
    $params = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
    if (!$hasDates) {
        $where[] = "gibbonPerson.status='Full'";
    } else {
        if (!empty($dateFrom)) {
            $where[] = '(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:dateFrom)';
            $params['dateFrom'] = Format::dateConvert($dateFrom);
        }
        if (!empty($dateTo)) {
            $where[] = '(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:dateTo)';
            $params['dateTo'] = Format::dateConvert($dateTo);
        }
    }

    $columns = "ROUND(AVG(DATEDIFF(CURDATE(), gibbonPerson.dob))/365.2422, 1) AS meanAge,
        COUNT(DISTINCT gibbonPerson.gibbonPersonID) AS total,
        COUNT(DISTINCT CASE WHEN gibbonPerson.gender='M' THEN gibbonPerson.gibbonPersonID END) AS totalMale,
        COUNT(DISTINCT CASE WHEN gibbonPerson.gender='F' THEN gibbonPerson.gibbonPersonID END) AS totalFemale";
    $from = "FROM gibbonFormGroup
        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
        JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
        JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
        WHERE ".implode(' AND ', $where);

    $groupRows = $pdo->select("SELECT gibbonFormGroup.name AS formGroup, gibbonFormGroup.nameShort, MIN(gibbonYearGroup.sequenceNumber) AS sequenceNumber, $columns
        $from
        GROUP BY gibbonFormGroup.gibbonFormGroupID, gibbonFormGroup.name, gibbonFormGroup.nameShort
        ORDER BY sequenceNumber, gibbonFormGroup.nameShort", $params)->fetchAll();

    // El promedio general se calcula con todos los estudiantes (no como promedio de promedios)
    $overall = $pdo->select("SELECT $columns $from", $params)->fetch();

    // Otros = estudiantes cuyo sexo no es M ni F (para que Hombres + Mujeres + Otros = Total)
    $formGroupsData = [];
    foreach ($groupRows as $group) {
        $formGroupsData[] = [
            'formGroup'   => $group['formGroup'],
            'meanAge'     => $group['meanAge'],
            'totalMale'   => (int) $group['totalMale'],
            'totalFemale' => (int) $group['totalFemale'],
            'totalOther'  => (int) $group['total'] - (int) $group['totalMale'] - (int) $group['totalFemale'],
            'total'       => (int) $group['total'],
        ];
    }

    if (!empty($formGroupsData)) {
        $formGroupsData[] = [
            'formGroup'   => 'Todos los grupos',
            'meanAge'     => $overall['meanAge'],
            'totalMale'   => (int) $overall['totalMale'],
            'totalFemale' => (int) $overall['totalFemale'],
            'totalOther'  => (int) $overall['total'] - (int) $overall['totalMale'] - (int) $overall['totalFemale'],
            'total'       => (int) $overall['total'],
            '_isTotal'    => true,
        ];
    }
    $showOther = array_sum(array_column($formGroupsData, 'totalOther')) > 0;

    // DATA TABLE
    $table = ReportTable::createPaginated('formGroupSummary', $criteria)->setViewMode($viewMode, $session);
    $table->setTitle('Reporte de edad promedio / sexo por grupo');

    $table->modifyRows(function ($formGroup, $row) {
        if (!empty($formGroup['_isTotal'])) $row->addClass('dull');
        return $row;
    });

    $table->addColumn('formGroup', 'Grupo')->width('24');
    $table->addColumn('meanAge', 'Edad promedio (años)')->width('20');
    $table->addColumn('totalMale', 'Hombres')->width('14');
    $table->addColumn('totalFemale', 'Mujeres')->width('14');
    if ($showOther) {
        $table->addColumn('totalOther', 'Otros / No especificado')->width('22');
    }
    $table->addColumn('total', 'Total')->width('14');

    if ($viewMode == 'export') {
        // EXCEL: diseño propio
        // Solo se necesita para exportar: si el archivo falta, el resto de la página sigue funcionando
        require_once __DIR__ . '/src/StyledSpreadsheetRenderer.php';

        $table->setRenderer(new StyledSpreadsheetRenderer());
        $table->addMetaData('filename', 'EdadPromedio_Sexo_'.date('Y-m-d'));
        $table->addMetaData('sheetTitle', 'Edad y sexo');
        $table->addMetaData('freezeColumns', 1);
        $table->addMetaData('countLabel', false);
        $table->addMetaData('subtitle', $hasDates
            ? 'Período: del '.$dateFrom.' al '.$dateTo
            : 'Estudiantes activos del año escolar actual');
        $table->addMetaData('groups', [
            'formGroup' => 'Grupo', 'meanAge' => 'Edad',
            'totalMale' => 'Estudiantes por sexo', 'totalFemale' => 'Estudiantes por sexo', 'totalOther' => 'Estudiantes por sexo',
            'total' => 'Total',
        ]);
        $table->addMetaData('numericColumns', [
            'meanAge' => '0.0', 'totalMale' => '0', 'totalFemale' => '0', 'totalOther' => '0', 'total' => '0',
        ]);
    }

    echo $table->render(new DataSet($formGroupsData));
}
