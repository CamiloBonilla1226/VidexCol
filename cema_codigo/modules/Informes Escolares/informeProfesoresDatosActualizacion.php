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
use Gibbon\Domain\Students\StudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Informes Escolares/informeProfesoresDatosActualizacion.php') == false) {
    // Access denied
	$page->addError(__('You do not have access to this action.'));
} else {

	$search = $_GET['search'] ?? '';
	$viewMode = $_REQUEST['format'] ?? '';
	$gibbonFormGroupID = (isset($_GET['gibbonFormGroupID']) ? $_GET['gibbonFormGroupID'] : null);
	$viewMode = isset($_REQUEST['format']) ? $_REQUEST['format'] : '';

    $reportStudents = $container->get(StudentGateway::class);
    $criteria = $reportStudents->newQueryCriteria(true)
        ->searchBy($reportStudents->getSearchableColumns(), $search)
        ->sortBy(['surname', 'preferredName', 'username'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromArray($_POST);


	$form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');
	$form->setTitle(__('Choose Form Group'))
	->setFactory(DatabaseFormFactory::create($pdo))
	->setClass('noIntBorder fullWidth');

	$form->addHiddenValue('q', '/modules/'.$gibbon->session->get('module').'/informeProfesoresDatosActualizacion.php');

	$row = $form->addRow();
	$row->addLabel('gibbonFormGroupID', __('Form Group'));
	$row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'), true)->selected($gibbonFormGroupID)->placeholder()->required();

	$row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

	$row = $form->addRow();
	$row->addSearchSubmit($gibbon->session, __('Clear Search'));

	echo $form->getOutput();

	$studentGateway = $container->get(StudentGateway::class);


	if($gibbonFormGroupID !== null) {

		// QUERY
    	$dataSet = $reportStudents->queryStudentsData_3($criteria, $gibbonFormGroupID);


    	// DATA TABLE
    	$table = ReportTable::createPaginated('reportStudents', $criteria)->setViewMode($viewMode, $gibbon->session);

    	$table->setTitle(__('Report Data'));

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

//	    $table->addColumn('religion', __('Religion'))->context('primary');
//	    $table->addColumn('canLogin', __('Acceso'))->context('primary');
//	    $table->addColumn('phone1', __('Tel.1'))
//	        ->format(function ($person) {
//	            return '+'.$person['phone1CountryCode'].' '.$person['phone1'];
//	        });

//	    $table->addColumn('username', __('User'))
//	        ->format(function ($person) {
//	        	if($person['username']){
//	        		return 'Y';
//	        	} 
//	        });
	    $table->addColumn('religion', __('Religión'))->context('primary');
//	    $table->addColumn('country', __('Nacionalidad'))->context('primary');
//	    $table->addColumn('email_father', __('Email papá'))->context('primary');
//	    $table->addColumn('email_mother', __('Email mamá'))->context('primary');
//	    $table->addColumn('homeAddress', __('Dir Casa'))->context('primary');

	    echo $table->render($dataSet);
	}
}