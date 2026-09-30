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

use Gibbon\Services\Format;

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\ReportTable;
use Modules\InformesEscolares\InformesEscolaresGateway;
use Modules\InformesEscolares\StyledSpreadsheetRenderer;

require_once __DIR__ . '/src/InformesEscolaresGateway.php';

if (isActionAccessible($guid, $connection2, '/modules/Informes Escolares/medicalReportStudents.php') == false) {
    // Access denied
	$page->addError(__('You do not have access to this action.'));
} else {

	$search = $_GET['search'] ?? '';
	$viewMode = $_REQUEST['format'] ?? '';
	$gibbonFormGroupID = (isset($_GET['gibbonFormGroupID']) ? $_GET['gibbonFormGroupID'] : null);
	$viewMode = isset($_REQUEST['format']) ? $_REQUEST['format'] : '';

    $reportStudents = $container->get(InformesEscolaresGateway::class);
    $criteria = $reportStudents->newQueryCriteria(true)
        ->searchBy($reportStudents->getSearchableColumns(), $search)
        ->sortBy(['surname', 'preferredName', 'username'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromArray($_POST);


	if (empty($viewMode)) {
	$form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');
	$form->setTitle(__('Seleccione curso a consultar'))
	->setFactory(DatabaseFormFactory::create($pdo))
	->setClass('noIntBorder fullWidth');

	$form->addHiddenValue('q', '/modules/'.$session->get('module').'/medicalReportStudents.php');

	$row = $form->addRow();
	$row->addLabel('gibbonFormGroupID', __('Form Group'));
	$row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'), true)->selected($gibbonFormGroupID)->placeholder()->required();

	$row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

	$row = $form->addRow();
	$row->addSearchSubmit($session, __('Clear Search'));

	echo $form->getOutput();
	}

	if($gibbonFormGroupID !== null) {

		// QUERY
    	$dataSet = $reportStudents->queryStudentsData_3($criteria, $gibbonFormGroupID);


    	// DATA TABLE
    	$table = ReportTable::createPaginated('medicalReportStudents', $criteria)->setViewMode($viewMode, $session);

    	$table->setTitle(__('Report Data'));

	    if ($viewMode == 'export') {
	        // Solo se necesita para exportar: si el archivo falta, el resto de la página sigue funcionando
	        require_once __DIR__ . '/src/StyledSpreadsheetRenderer.php';

	        $groupName = $pdo->select('SELECT name FROM gibbonFormGroup WHERE gibbonFormGroupID=:id', ['id' => $gibbonFormGroupID])->fetchColumn();
	        $table->setTitle('Reporte médico'.(!empty($groupName) ? ' - Grupo '.$groupName : ''));
	        $table->addMetaData('filename', 'ReporteMedico_'.preg_replace('/[^A-Za-z0-9_-]/', '', (string) $groupName).'_'.date('Y-m-d'));
	        $table->addMetaData('sheetTitle', 'Médico');
	        $table->addMetaData('freezeColumns', 2);
	        $table->addMetaData('groups', [
	            'surname' => 'Estudiante', 'preferredName' => 'Estudiante',
	            'bloodType' => 'Salud', 'longTermMedication' => 'Salud', 'longTermMedicationDetails' => 'Salud', 'vacunas10Years' => 'Salud', 'comment' => 'Salud',
	            'emergency1Name' => 'Contacto de emergencia 1', 'emergency1Relationship' => 'Contacto de emergencia 1', 'emergency1Number1' => 'Contacto de emergencia 1', 'emergency1Number2' => 'Contacto de emergencia 1',
	            'emergency2Name' => 'Contacto de emergencia 2', 'emergency2Relationship' => 'Contacto de emergencia 2', 'emergency2Number1' => 'Contacto de emergencia 2', 'emergency2Number2' => 'Contacto de emergencia 2',
	        ]);
	        $table->setRenderer(new StyledSpreadsheetRenderer());

	        // Sí / No para los campos que guardan Y / N; los celulares con formato
	        $yesNo = function ($field) {
	            return function ($person) use ($field) {
	                $value = $person[$field] ?? '';
	                return $value == 'Y' ? 'Sí' : ($value == 'N' ? 'No' : $value);
	            };
	        };
	        $phone = function ($field) {
	            return function ($person) use ($field) {
	                return !empty($person[$field]) ? Format::phone($person[$field]) : '';
	            };
	        };

	        $table->addColumn('surname', 'Apellidos')->width('22');
	        $table->addColumn('preferredName', 'Nombres')->width('22');
	        $table->addColumn('bloodType', 'RH')->width('8');
	        $table->addColumn('longTermMedication', 'Medicación permanente')->width('16')->format($yesNo('longTermMedication'));
	        $table->addColumn('longTermMedicationDetails', 'Detalles de la medicación')->width('36');
	        $table->addColumn('vacunas10Years', 'Vacunas 10 años')->width('16')->format($yesNo('vacunas10Years'));
	        $table->addColumn('comment', 'Comentarios')->width('40');
	        foreach ([1, 2] as $n) {
	            $table->addColumn('emergency'.$n.'Name', 'Nombre')->width('28');
	            $table->addColumn('emergency'.$n.'Relationship', 'Parentesco')->width('16');
	            $table->addColumn('emergency'.$n.'Number1', 'Celular 1')->width('18')->format($phone('emergency'.$n.'Number1'));
	            $table->addColumn('emergency'.$n.'Number2', 'Celular 2')->width('18')->format($phone('emergency'.$n.'Number2'));
	        }

	        echo $table->render($dataSet);
	        return;
	    }

	    // COLUMNS
	    $table->addColumn('image_240', __('Photo'))
	        ->context('primary')
	        ->width('10%')
	        ->notSortable()
	        ->format(Format::using('userPhoto', ['image_240', 'sm']));

	    $table->addColumn('student', __('Student'))
	        ->sortable(['surname', 'preferredName'])
	        ->format(function ($person) {
	            return Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true) . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
	        });
	    $table->addColumn('bloodType', __('RH'))->context('primary');
//	    $table->addColumn('bloodType', __('Tipo de Sangre'))->context('primary');
	    $table->addColumn('longTermMedication', __('Medica- ción'))->context('primary');
	    $table->addColumn('longTermMedicationDetails', __('Detalles medicación permanente'))->context('primary');
	    $table->addColumn('vacunas10Years', __('Vacunas 10 años'))->context('primary');
	    $table->addColumn('comment', __('Comentarios'))->context('primary');
	    $table->addColumn('emergency1Name', __('Contacto emergencia 1'))->context('primary');
	    $table->addColumn('emergency1Number1', __('Celular 1 contacto 1'))->context('primary');
	    $table->addColumn('emergency1Number2', __('Celular 2 contacto 1'))->context('primary');
	    $table->addColumn('emergency1Relationship', __('Parentesco'))->context('primary');
	    $table->addColumn('emergency2Name', __('Contacto emergencia 2'))->context('primary');
	    $table->addColumn('emergency2Number1', __('Celular 1 contacto 2'))->context('primary');
	    $table->addColumn('emergency2Number2', __('Celular 2 contacto 2'))->context('primary');


/*                'gibbonPerson.emergency1Name',
                'gibbonPerson.emergency1Number1',
                'gibbonPerson.emergency1Number2',
                'gibbonPerson.emergency1Relationship',
                'gibbonPerson.emergency2Name',
                'gibbonPerson.emergency2Number1',
                'gibbonPerson.emergency2Number2' */



/*	    $table->addColumn('phone1', __('Tel. Contacto 1'))
	        ->format(function ($person) {
	            return '+'.$person['phone1CountryCode'].' '.$person['phone1'];
	        }); */

//	    $table->addColumn('gibbonPersonMedicalID', __('D. Medicos'))
//	        ->format(function ($person) {
//	        	if($person['gibbonPersonMedicalID']){
//	        		return 'Y';
//	        	} 
//	        });


	    // La tabla conserva todas sus columnas. Si no cabe, el scroll horizontal queda dentro de este recuadro (la página
	    // no se ensancha), la foto y el nombre del estudiante quedan fijos a la izquierda y las celdas son más compactas.
	    echo '<style>
	        #medicalReportStudents table { table-layout: auto; }
	        #medicalReportStudents th, #medicalReportStudents td { padding: 4px 8px; font-size: 0.875rem; }
	        #medicalReportStudents th { white-space: normal; max-width: 120px; vertical-align: bottom; }
	        #medicalReportStudents th:nth-child(1), #medicalReportStudents td:nth-child(1) { position: sticky; left: 0; z-index: 2; background: #fff; width: 72px; min-width: 72px; }
	        #medicalReportStudents th:nth-child(2), #medicalReportStudents td:nth-child(2) { position: sticky; left: 72px; z-index: 2; background: #fff; min-width: 180px; box-shadow: 2px 0 3px -1px rgba(0,0,0,.25); }
	        @media print {
	            .medical-scroll { overflow: visible !important; }
	            #medicalReportStudents table { table-layout: auto; }
	            #medicalReportStudents th, #medicalReportStudents td { position: static !important; box-shadow: none !important; }
	        }
	    </style>';
	    echo '<div class="medical-scroll" style="width:100%; max-width:100%; min-width:0; overflow-x:auto; -webkit-overflow-scrolling:touch;">';
	    echo $table->render($dataSet);
	    echo '</div>';
	}
}