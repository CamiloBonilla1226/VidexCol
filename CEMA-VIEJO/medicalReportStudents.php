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

if (isActionAccessible($guid, $connection2, '/modules/Informes Escolares/medicalReportStudents.php') == false) {
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
	$form->setTitle(__('Seleccione curso a consultar'))
	->setFactory(DatabaseFormFactory::create($pdo))
	->setClass('noIntBorder fullWidth');

	$form->addHiddenValue('q', '/modules/'.$gibbon->session->get('module').'/medicalReportStudents.php');

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
 /*       $Host       = 'localhost';
        $User       = 'zemfzeav_cema';
        $Password   = '@Lbi[!ZpIQ=.';
        $database   = 'zemfzeav_cema';
        
        if ($criteria->getSearchText() != "") {
            $busquedaNombre = " AND firstName = " . "'" . $criteria->getSearchText() . "'";
        } else {
            $busquedaNombre = "";
        }

//      Create connection
        $conn = new mysqli($Host, $User, $Password, $database);
    
        $sql_per = "SELECT * FROM gibbonPerson INNER JOIN gibbonStudentEnrolment ON gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID
                    INNER JOIN gibbonFormGroup ON gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID
                    WHERE gibbonFormGroup.gibbonFormGroupID = ". "'".$gibbonFormGroupID."'" . $busquedaNombre . " ORDER BY officialName";

        $result = $conn->query($sql_per);
    
        if ($result->num_rows > 0) {
            ?>
            
            <head>
            <style>
            table {
                border-collapse: collapse;
                width: 100%;
            }

            th, td {
                text-align: left;
                padding: 8px;
            }

            tr:nth-child(even) {
              background-color: #D6EEEE;
            }
            </style>
            </head>
            <body>
            
            
            <table style="width:100%;
                        border: 1px solid black;">
                <tr border: 0.5px solid black;>
                    <th style="width:19%; border: 0.5px solid black;">Nombre</th>
                    <th style="width:2%; border: 0.5px solid black;">RH</th>
                    <th style="width:10%; border: 0.5px solid black;">MLT</th>
                    <th style="width:10%; border: 0.5px solid black;">LTMD</th>
                    <th style="width:5%; border: 0.5px solid black;">V_10_Y</th>
                    <th style="width:15%; border: 0.5px solid black;">Comentarios</th>
                    <th style="width:10%; border: 0.5px solid black;">Contacto 1 emergencia</th>
                    <th style="width:8%; border: 0.5px solid black;">Contacto emergencia 1</th>
                    <th style="width:10%; border: 0.5px solid black;">Contacto emergencia 1</th>
                    <th style="width:8%; border: 0.5px solid black;">Parentesco</th>
                    <th style="width:8%; border: 0.5px solid black;">Contacto 2 emergencia</th>
                    <th style="width:10%; border: 0.5px solid black;">Contacto emergencia 2</th>
                    <th style="width:8%; border: 0.5px solid black;">Contacto emergencia 2</th>
                </tr> <?

            while($row = $result->fetch_assoc()) {
                $gPID = $row["gibbonPersonID"];
                $officialName = $row["officialName"];
                $emergency1Name = $row["emergency1Name"];
                $emergency1Number1 = $row["emergency1Number1"];
                $emergency1Number2 = $row["emergency1Number2"];
                $emergency1Relationship = $row["emergency1Relationship"];
                $emergency2Name = $row["emergency2Name"];
                $emergency2Number1 = $row["emergency2Number1"];
                $emergency2Number2 = $row["emergency2Number2"];
            
                $sql_md = "SELECT * FROM gibbonPersonMedical WHERE gibbonPersonID = ". "'".$gPID."'";
                $result_md = $conn->query($sql_md);
                if ($result_md->num_rows > 0) {
                    while($row = $result_md->fetch_assoc()) {
                        $rh = $row["bloodType"];
                        $lTM = $row["longTermMedication"];
                        $lTMD = $row["longTermMedicationDetails"];
                        $v10Y = $row["vacunas10Years"];
                        $comment = $row["comment"];
                        $fields = $row["fields"];
                    }
                }
                ?><tr>
                    <th style="border: 0.5px solid black;"><?echo $officialName?></th>
                    <th style="border: 0.5px solid black;"><?echo $rh?></th>
                    <th style="border: 0.5px solid black;"><?echo $lTM?></th>
                    <th style="border: 0.5px solid black;"><?echo $lTMD?></th>
                    <th style="border: 0.5px solid black;"><?echo $v10Y?></th>
                    <th style="border: 0.5px solid black;"><?echo $comment?></th>
                    <th style="border: 0.5px solid black;"><?echo $emergency1Name?></th>
                    <th style="border: 0.5px solid black;"><?echo $emergency1Number1?></th>
                    <th style="border: 0.5px solid black;"><?echo $emergency1Number2?></th>
                    <th style="border: 0.5px solid black;"><?echo $emergency1Relationship?></th>
                    <th style="border: 0.5px solid black;"><?echo $emergency2Name?></th>
                    <th style="border: 0.5px solid black;"><?echo $emergency2Number1?></th>
                    <th style="border: 0.5px solid black;"><?echo $emergency2Number2?></th>
                </tr><?
                $rh = "";
                $lTM = "";
                $lTMD = "";
                $v10Y = "";
                $comment = "";
                $fields = "";        }
            }
        $conn->close();   
    
        ?></table><? */


		// QUERY
    	$dataSet = $reportStudents->queryStudentsData_3($criteria, $gibbonFormGroupID);


    	// DATA TABLE
    	$table = ReportTable::createPaginated('medicalReportStudents', $criteria)->setViewMode($viewMode, $gibbon->session);

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


	    echo $table->render($dataSet);
	}
}