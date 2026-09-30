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


if (isActionAccessible($guid, $connection2, '/modules/Informes Escolares/reportGradesByCourse.php') == false) {
    // Access denied
	$page->addError(__('You do not have access to this action.'));
} else {

	$viewMode = $_REQUEST['format'] ?? '';
	$gibbonFormGroupID = (isset($_GET['gibbonFormGroupID']) ? $_GET['gibbonFormGroupID'] : null);

	$today = date('Y-m-d');
	$fecha_inicial = isset($_GET['fecha_inicial'])? Format::dateConvert($_GET['fecha_inicial']) : $today;
    $fecha_final = isset($_GET['fecha_final'])? Format::dateConvert($_GET['fecha_final']) : $today;

	$form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');
	$form->setTitle(__('Choose Form Group'))
	->setFactory(DatabaseFormFactory::create($pdo))
	->setClass('noIntBorder fullWidth');

	$form->addHiddenValue('q', '/modules/'.$gibbon->session->get('module').'/reportGradesByCourse.php');

	$row = $form->addRow();
	$row->addLabel('gibbonFormGroupID', __('Form Group'));
	$row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'), true)->selected($gibbonFormGroupID)->placeholder()->required();

	$row = $form->addRow();
            $row->addLabel('fecha_inicial', 'Fecha inicial')->description(__m('Fecha inicial'));
            $row->addDate('fecha_inicial')->setValue(Format::date($fecha_inicial));

    $row = $form->addRow();
            $row->addLabel('fecha_final', 'Fecha final')->description(__m('Fecha final'));
            $row->addDate('fecha_final')->setValue(Format::date($fecha_final));

	$row = $form->addRow();
	$row->addSearchSubmit($gibbon->session, __('Clear Search'));

	echo $form->getOutput();

	$studentGateway = $container->get(StudentGateway::class);

	$criteria = $studentGateway->newQueryCriteria(true)
	->sortBy(['surname', 'preferredName'])
	->pageSize(!empty($viewMode) ? 0 : 50)
	->fromArray($_POST);


	if($gibbonFormGroupID !== null) {


		$params = array('gibbonFormGroupID' => $gibbonFormGroupID);
		$sql = "SELECT nameShort FROM gibbonFormGroup WHERE gibbonFormGroupID = :gibbonFormGroupID";
		$group = $connection2->prepare($sql);
        $group->execute($params);
        $grupo = $group->fetch();

		// QUERY
	    $gradesByCourse = $studentGateway->queryGradesByCourse($gibbonFormGroupID, $fecha_inicial, $fecha_final);
	    $data = $gradesByCourse->fetchAll();

	    // DATA TABLE
		$table = ReportTable::createPaginated('gradesByCourse', $criteria)->setViewMode($viewMode, $gibbon->session);
	    $table->setTitle(__('Notas X Curso'));

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


	     
	    if($group->rowCount() < 1){
	    	echo "<div class='error'>";
	        echo __('There are no records to display.');
	        echo '</div>';

	        return;
	    }


	    $intGroup = (int)filter_var($grupo['nameShort'], FILTER_SANITIZE_NUMBER_INT);

	    $table->addColumn('PILEO', 'PILEO')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['1 PILEO'];
	    		} else if($intGroup == 2){
	    			return $data['PILEO 2'];
	    		} else if($intGroup == 3){
	    			return $data['PILEO 3'];
	    		} else if($intGroup == 4){
	    			return $data['4 PILEO'];
	    		} else if($intGroup == 5){
	    			return $data['5 PILEO'];
	    		} else if($intGroup == 6){
	    			return $data['6 PILEO'];
	    		} else if($intGroup == 7){
	    			return $data['PILEO 2'];
	    		} else if($intGroup == 8){
	    			return $data['PILEO 8'];
	    		} else if($intGroup == 9){
	    			return $data['9 PILEO'];
	    		} else if($intGroup == 10){
	    			return $data['10 PILEO'];
	    		} else if($intGroup == 11){
	    			return $data['11 PILEO'];
	    		}
	    	});

	    if($intGroup == 1 || $intGroup == 2 || $intGroup == 3 || $intGroup == 4 || $intGroup == 5){
	    	$table->addColumn('ECO', 'ECO')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['1 ECO'];
	    		} else if($intGroup == 2){
	    			return $data['2 Ecología'];
	    		} else if($intGroup == 3){
	    			return $data['3 ECO'];
	    		} else if($intGroup == 4){
	    			return $data['ECO 4'];
	    		} else if($intGroup == 5){
	    			return $data['5 ECO'];
	    		}
	    	});

	    	$table->addColumn('TEC', 'TEC')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['1Tecnología'];
	    		} else if($intGroup == 2){
	    			return $data['2 Tecnología'];
	    		} else if($intGroup == 3){
	    			return $data['3 Tecnología'];
	    		} else if($intGroup == 4){
	    			return $data['4 Tecnología'];
	    		} else if($intGroup == 5){
	    			return $data['5 Tecnología'];
	    		}
	    	});


	    	$table->addColumn('NAT', 'NAT')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['Nat 1'];
	    		} else if($intGroup == 2){
	    			return $data['Nat 2'];
	    		} else if($intGroup == 3){
	    			return $data['Nat 3'];
	    		} else if($intGroup == 4){
	    			return $data['Nat 4'];
	    		} else if($intGroup == 5){
	    			return $data['Nat 5'];
	    		}
	    	});

	    	$table->addColumn('SOC', 'SOC')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['Soc 1'];
	    		} else if($intGroup == 2){
	    			return $data['Soc 2'];
	    		} else if($intGroup == 3){
	    			return $data['Soc 3'];
	    		} else if($intGroup == 4){
	    			return $data['Soc 4'];
	    		} else if($intGroup == 5){
	    			return $data['Soc 5'];
	    		}
	    	});
	    }
	   	

	   	$table->addColumn('ARTES', 'ARTES')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['Artes 1'];
	    		} else if($intGroup == 2){
	    			return $data['Artes 2'];
	    		} else if($intGroup == 3){
	    			return $data['Artes 3'];
	    		} else if($intGroup == 4){
	    			return $data['Artes 4 '];
	    		} else if($intGroup == 5){
	    			return $data['Artes 5 '];
	    		} else if($intGroup == 6){
	    			return $data['Artes 6 '];
	    		} else if($intGroup == 7){
	    			return $data['Artes 7'];
	    		} else if($intGroup == 8){
	    			return $data['Artes 8 '];
	    		} else if($intGroup == 9){
	    			return $data['Artes 9'];
	    		} else if($intGroup == 10){
	    			return $data['Artes 10'];
	    		} else if($intGroup == 11){
	    			return $data['Artes 11'];
	    		}
	    	});


	    if($intGroup == 6 || $intGroup == 7 || $intGroup == 8 || $intGroup == 9){
	    	$table->addColumn('BIOLG', 'BIOLG')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 6) {
	    			return $data['Biolg 6'];
	    		} else if($intGroup == 7){
	    			return $data['Biolg 7'];
	    		} else if($intGroup == 8){
	    			return $data['Biolg 8'];
	    		} else if($intGroup == 9){
	    			return $data['Biolg 9'];
	    		}
	    	});

	    	$table->addColumn('Const', 'Const')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 6) {
	    			return $data['Const 6'];
	    		} else if($intGroup == 7){
	    			return $data['Const 7'];
	    		} else if($intGroup == 8){
	    			return $data['Const 8'];
	    		} else if($intGroup == 9){
	    			return $data['Const 9'];
	    		}
	    	});

	    	$table->addColumn('FisQ', 'FisQ')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 6) {
	    			return $data['FisQ 6'];
	    		} else if($intGroup == 7){
	    			return $data['FisQ 7'];
	    		} else if($intGroup == 8){
	    			return $data['FisQ 8'];
	    		} else if($intGroup == 9){
	    			return $data['FisQ 9'];
	    		}
	    	});

	    	// $table->addColumn('METOD', 'METOD')
	    	// ->format(function ($data) {
	    	// 	$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    	// 	if($intGroup == 6) {
	    	// 		return $data['Metod 6'];
	    	// 	} else if($intGroup == 7){
	    	// 		return $data['Metod 7'];
	    	// 	} else if($intGroup == 8){
	    	// 		return $data['Metod 8'];
	    	// 	} else if($intGroup == 9){
	    	// 		return $data['Metod 9'];
	    	// 	}
	    	// });

	    	$table->addColumn('SOC', 'SOC')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 6) {
	    			return $data['Soc 6'];
	    		} else if($intGroup == 7){
	    			return $data['Soc 7'];
	    		} else if($intGroup == 8){
	    			return $data['Soc 8'];
	    		} else if($intGroup == 9){
	    			return $data['Soc 9'];
	    		}
	    	});
	    }

	    if($intGroup == 10 || $intGroup == 11){
	     	$table->addColumn('C.E.S.P', 'C.E.S.P')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 10) {
	    			return $data['C.E.S.P 10'];
	    		} else if($intGroup == 11){
	    			return $data['C.E.S.P 11'];
	    		}
	    	});

	    	$table->addColumn('FILO', 'FILO')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 10) {
	    			return $data['Filo 10'];
	    		} else if($intGroup == 11){
	    			return $data['Filo 11'];
	    		}
	    	});

	    	$table->addColumn('FISC', 'FISC')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 10) {
	    			return $data['Fisc 10'];
	    		} else if($intGroup == 11){
	    			return $data['Fisc 11'];
	    		}
	    	});


	    	$table->addColumn('QUIM', 'QUIM')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 10) {
	    			return $data['Quim 10'];
	    		} else if($intGroup == 11){
	    			return $data['Quim 11'];
	    		}
	    	});


	    }

	    $table->addColumn('COM', 'COM')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['Com 1'];
	    		} else if($intGroup == 2){
	    			return $data['Com 2'];
	    		} else if($intGroup == 3){
	    			return $data['Com 3'];
	    		} else if($intGroup == 4){
	    			return $data['Com 4'];
	    		} else if($intGroup == 5){
	    			return $data['Com 5'];
	    		} else if($intGroup == 6){
	    			return $data['Com 6'];
	    		} else if($intGroup == 7){
	    			return $data['Com 7'];
	    		} else if($intGroup == 8){
	    			return $data['Com 8'];
	    		} else if($intGroup == 9){
	    			return $data['Com 9'];
	    		} else if($intGroup == 10){
	    			return $data['Com 10'];
	    		} else if($intGroup == 11){
	    			return $data['Com 11'];
	    		}
	    	});

	    $table->addColumn('EDU F', 'EDU F')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['Edu F 1'];
	    		} else if($intGroup == 2){
	    			return $data['Edu F 2'];
	    		} else if($intGroup == 3){
	    			return $data['Edu F 3'];
	    		} else if($intGroup == 4){
	    			return $data['Edu F 4'];
	    		} else if($intGroup == 5){
	    			return $data['Edu F 5'];
	    		} else if($intGroup == 6){
	    			return $data['Edu F 6'];
	    		} else if($intGroup == 7){
	    			return $data['Edu F 7'];
	    		} else if($intGroup == 8){
	    			return $data['Edu F 8'];
	    		} else if($intGroup == 9){
	    			return $data['Edu F 9'];
	    		} else if($intGroup == 10){
	    			return $data['Edu F 10'];
	    		} else if($intGroup == 11){
	    			return $data['Edu F 11'];
	    		}
	    	});

	    // if($intGroup == 2 || $intGroup == 3 || $intGroup == 4 || $intGroup == 5 || $intGroup == 6 || $intGroup == 7 || $intGroup == 8 || $intGroup == 9 || $intGroup == 10 || $intGroup == 11){
	    // 	$table->addColumn('ENF', 'ENF')
	    // 	->format(function ($data) {
	    // 		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
		// 		if($intGroup == 2){
	    // 			return $data['ENF 2'];
	    // 		} else if($intGroup == 3){
	    // 			return $data['ENF 3'];
	    // 		} else if($intGroup == 4){
	    // 			return $data['ENF 4'];
	    // 		} else if($intGroup == 5){
	    // 			return $data['ENF 5'];
	    // 		} else if($intGroup == 6){
	    // 			return $data['ENF 6'];
	    // 		} else if($intGroup == 7){
	    // 			return $data['ENF 7'];
	    // 		} else if($intGroup == 8){
	    // 			return $data['ENF 8'];
	    // 		} else if($intGroup == 9){
	    // 			return $data['ENF 9'];
	    // 		} else if($intGroup == 10){
	    // 			return $data['ENF 10'];
	    // 		} else if($intGroup == 11){
	    // 			return $data['ENF 11'];
	    // 		}
	    // 	});
	    // }



	    $table->addColumn('Ética+V', 'Ética+V')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['Ética+V 1'];
	    		} else if($intGroup == 2){
	    			return $data['Ética+V 2'];
	    		} else if($intGroup == 3){
	    			return $data['Ética+V 3'];
	    		} else if($intGroup == 4){
	    			return $data['Ética+V 4'];
	    		} else if($intGroup == 5){
	    			return $data['Ética+V 5'];
	    		} else if($intGroup == 6){
	    			return $data['Ética+V 6'];
	    		} else if($intGroup == 7){
	    			return $data['Ética+V 7'];
	    		} else if($intGroup == 8){
	    			return $data['Ética+V 8'];
	    		} else if($intGroup == 9){
	    			return $data['Ética+V 9'];
	    		} else if($intGroup == 10){
	    			return $data['Ética+V 10'];
	    		} else if($intGroup == 11){
	    			return $data['Ética+V 11'];
	    		}
	    	});

	    $table->addColumn('GEOE', 'GEOE')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['GeoE 1'];
	    		} else if($intGroup == 2){
	    			return $data['GeoE 2'];
	    		} else if($intGroup == 3){
	    			return $data['GeoE 3'];
	    		} else if($intGroup == 4){
	    			return $data['GeoE 4'];
	    		} else if($intGroup == 5){
	    			return $data['GeoE 5'];
	    		} else if($intGroup == 6){
	    			return $data['GeoE 6'];
	    		} else if($intGroup == 7){
	    			return $data['GeoE 7'];
	    		} else if($intGroup == 8){
	    			return $data['GeoE 8'];
	    		} else if($intGroup == 9){
	    			return $data['GeoE 9'];
	    		} else if($intGroup == 10){
	    			return $data['GeoE 10'];
	    		} else if($intGroup == 11){
	    			return $data['GeoE 11'];
	    		}
	    	});


	    $table->addColumn('INFOR', 'INFOR')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['INFOR 1'];
	    		} else if($intGroup == 2){
	    			return $data['Infor 2'];
	    		} else if($intGroup == 3){
	    			return $data['Infor 3'];
	    		} else if($intGroup == 4){
	    			return $data['Infor 4'];
	    		} else if($intGroup == 5){
	    			return $data['Infor 5'];
	    		} else if($intGroup == 6){
	    			return $data['Infor 6'];
	    		} else if($intGroup == 7){
	    			return $data['Infor 7'];
	    		} else if($intGroup == 8){
	    			return $data['Infor 8'];
	    		} else if($intGroup == 9){
	    			return $data['Infor 9'];
	    		} else if($intGroup == 10){
	    			return $data['Infor 10'];
	    		} else if($intGroup == 11){
	    			return $data['Infor 11'];
	    		}
	    	});

	    $table->addColumn('ING', 'ING')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['Ing 1'];
	    		} else if($intGroup == 2){
	    			return $data['Ing 2'];
	    		} else if($intGroup == 3){
	    			return $data['Ing 3'];
	    		} else if($intGroup == 4){
	    			return $data['Ing 4'];
	    		} else if($intGroup == 5){
	    			return $data['Ing 5'];
	    		} else if($intGroup == 6){
	    			return $data['Ing 6'];
	    		} else if($intGroup == 7){
	    			return $data['Ing 7'];
	    		} else if($intGroup == 8){
	    			return $data['Ing 8'];
	    		} else if($intGroup == 9){
	    			return $data['Ing 9'];
	    		} else if($intGroup == 10){
	    			return $data['Ing 10'];
	    		} else if($intGroup == 11){
	    			return $data['Ing 11'];
	    		}
	    	});

	   	$table->addColumn('MAT', 'MAT')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['Mat 1'];
	    		} else if($intGroup == 2){
	    			return $data['Mat 2'];
	    		} else if($intGroup == 3){
	    			return $data['Mat 3'];
	    		} else if($intGroup == 4){
	    			return $data['Mat 4 '];
	    		} else if($intGroup == 5){
	    			return $data['Mat 5'];
	    		} else if($intGroup == 6){
	    			return $data['Mat 6'];
	    		} else if($intGroup == 7){
	    			return $data['Mat 7'];
	    		} else if($intGroup == 8){
	    			return $data['Mat 8'];
	    		} else if($intGroup == 9){
	    			return $data['Mat 9'];
	    		} else if($intGroup == 10){
	    			return $data['Mat 10'];
	    		} else if($intGroup == 11){
	    			return $data['Mat 11'];
	    		}
	    	});

	    $table->addColumn('Religión', 'Religión')
	    	->format(function ($data) {
	    		$intGroup = (int)filter_var($data['grupo'], FILTER_SANITIZE_NUMBER_INT);
	    		if($intGroup == 1) {
	    			return $data['Religión 1'];
	    		} else if($intGroup == 2){
	    			return $data['Religión 2'];
	    		} else if($intGroup == 3){
	    			return $data['Religión 3'];
	    		} else if($intGroup == 4){
	    			return $data['Religión 4'];
	    		} else if($intGroup == 5){
	    			return $data['Religión 5'];
	    		} else if($intGroup == 6){
	    			return $data['Religión 6'];
	    		} else if($intGroup == 7){
	    			return $data['Religión 7'];
	    		} else if($intGroup == 8){
	    			return $data['Religión 8'];
	    		} else if($intGroup == 9){
	    			return $data['Religión 9'];
	    		} else if($intGroup == 10){
	    			return $data['Religión 10'];
	    		} else if($intGroup == 11){
	    			return $data['Religión 11'];
	    		}
	    	});


	    echo $table->render($data);
	}

	
}
?>