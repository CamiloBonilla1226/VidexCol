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
use Gibbon\Domain\Staff\StaffGateway;

if (isActionAccessible($guid, $connection2, '/modules/Informes Escolares/reportEntryNotes.php') == false) {
    // Access denied
	$page->addError(__('You do not have access to this action.'));
} else {
	$search = $_GET['search'] ?? '';
	$viewMode = isset($_REQUEST['format']) ? $_REQUEST['format'] : '';
	$gibbonSchoolYearTermID = (isset($_GET['gibbonSchoolYearTermID']) ? $_GET['gibbonSchoolYearTermID'] : null);

	// QUERY
    $staffGateway = $container->get(StaffGateway::class);
    $criteria = $staffGateway->newQueryCriteria(true)
        ->searchBy($staffGateway->getSearchableColumns(), $search)
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromArray($_POST);

	$form = Form::create('filters', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Search'));

    $form->setClass('noIntBorder fullWidth');
    $form->addHiddenValue('q', '/modules/'.$gibbon->session->get('module').'/reportEntryNotes.php');

     $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

    $sql = 'SELECT gibbonSchoolYearTerm.gibbonSchoolYearTermID as value, gibbonSchoolYearTerm.name as name FROM gibbonSchoolYearTerm LEFT JOIN gibbonSchoolYear ON gibbonSchoolYear.gibbonSchoolYearID = gibbonSchoolYearTerm.gibbonSchoolYearID WHERE gibbonSchoolYear.status = "Current"';

    $row = $form->addRow();
	$row->addLabel('gibbonSchoolYearTermID', __('Period'));
    $row->addSelect('gibbonSchoolYearTermID')->fromQuery($pdo, $sql)->selected($gibbonSchoolYearTermID)->required()->placeholder();

    $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session, 'Clear Filters', ['view', 'sidebar']);

    echo $form->getOutput();

    

    if (empty($gibbonSchoolYearTermID)) return;
    	
    // QUERY
   	$dataSet = $staffGateway->queryReportEntryNotesData($criteria, $gibbonSchoolYearTermID);


   	// DATA TABLE
    $table = ReportTable::createPaginated('reportEntryNotes', $criteria)->setViewMode($viewMode, $gibbon->session);

    $table->setTitle(__('Report Data'));
    
   // COLUMNS
	$table->addColumn('image_240', __('Photo'))
	    ->context('primary')
	    ->width('10%')
	    ->notSortable()
	    ->format(Format::using('userPhoto', ['image_240', 'sm']));

	$table->addColumn('name', __('Name'))
	        ->sortable(['surname', 'preferredName'])
	        ->format(function ($person) {
	            return Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true);
	        });

	$table->addColumn('nameShort', __('Class'));

	$table->addColumn('Ingreso de Notas', __('Ingreso de Notas'))
	        ->format(function ($data) {

	        	$text = 'Completado';

	        	if($data['total'] < 0){
	        		$text = 'Pendiente';
	        	}

	            return $text;
	        });



	echo $table->render($dataSet);
}