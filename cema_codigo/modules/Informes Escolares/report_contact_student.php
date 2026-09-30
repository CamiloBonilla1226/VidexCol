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

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Students\StudentReportGateway;
use Modules\InformesEscolares\StyledSpreadsheetRenderer;


//Module includes
require_once __DIR__ . '/moduleFunctions.php';
require_once __DIR__ . '/src/StyledSpreadsheetRenderer.php';

if (isActionAccessible($guid, $connection2, '/modules/Informes Escolares/report_contact_student.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Directory'));
    }

    $reportGateway = $container->get(StudentReportGateway::class);
    $familyGateway = $container->get(FamilyGateway::class);

    // CRITERIA
    $criteria = $reportGateway->newQueryCriteria(true)
        ->sortBy(['gibbonPerson.transport', 'gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();

    $transport = $reportGateway->queryStudentTransport($criteria, $gibbonSchoolYearID);

    // Join a set of family data per student
    $people = $transport->getColumn('gibbonPersonID');
    $familyData = $familyGateway->selectFamiliesByStudent($people)->fetchGrouped();
    $transport->joinColumn('gibbonPersonID', 'families', $familyData);

    // Join a set of family adults per student
    $familyAdults = $familyGateway->selectFamilyAdultsByStudent($people)->fetchGrouped();
    $transport->joinColumn('gibbonPersonID', 'familyAdults', $familyAdults);

    // DATA TABLE
    $table = ReportTable::createPaginated('studentTransport', $criteria)->setViewMode($viewMode, $gibbon->session);
    $table->setTitle(__('Directorio'));

    if ($viewMode == 'export') {
        // EXCEL: columnas planas con diseño propio
        $table->addMetaData('filename', 'Directorio_'.date('Y-m-d'));
        $table->addMetaData('sheetTitle', 'Directorio');
        $table->addMetaData('freezeColumns', 3);
        $groups = ['formGroup' => 'Estudiante', 'surname' => 'Estudiante', 'preferredName' => 'Estudiante', 'address' => 'Estudiante'];
        $table->setRenderer(new StyledSpreadsheetRenderer());

        // Dirección de la familia (o la del estudiante si no tiene familia), en una sola línea
        $address = function ($student) {
            $lines = [];
            foreach (($student['families'] ?? []) as $family) {
                $lines[] = Format::address($family['homeAddress'], $family['homeAddressDistrict'], $family['homeAddressCountry']);
            }
            if (empty($lines)) {
                $lines[] = Format::address($student['address1'], $student['address1District'], $student['address1Country']);
            }
            $text = array_map(function ($line) {
                return trim(strip_tags(str_replace(['<br/>', '<br>', '<br />'], ', ', $line)), ' ,');
            }, $lines);
            return implode(' | ', array_filter($text));
        };

        // Datos del acudiente 1 y, en la segunda columna, de los demás acudientes separados por " | "
        $adultField = function ($student, $index, $field) {
            $adults = array_values($student['familyAdults'] ?? []);
            $selected = ($index == 0) ? array_slice($adults, 0, 1) : array_slice($adults, 1);
            $values = [];
            foreach ($selected as $adult) {
                switch ($field) {
                    case 'name':
                        $values[] = Format::name('', $adult['preferredName'], $adult['surname'], 'Parent', false, true);
                        break;
                    case 'rel':
                        $values[] = $adult['relationship'] ?? '';
                        break;
                    case 'phone':
                        $list = [];
                        foreach ([1, 2, 3, 4] as $i) {
                            if (!empty($adult['phone'.$i])) {
                                $list[] = Format::phone($adult['phone'.$i], $adult['phone'.$i.'CountryCode'], $adult['phone'.$i.'Type']);
                            }
                        }
                        $values[] = implode(' / ', $list);
                        break;
                    case 'email':
                        $values[] = $adult['email'] ?? '';
                        break;
                }
            }
            return implode(' | ', array_filter($values));
        };

        $table->addColumn('formGroup', 'Grupo')->width('12');
        $table->addColumn('surname', 'Apellidos')->width('22');
        $table->addColumn('preferredName', 'Nombres')->width('22');
        $table->addColumn('address', 'Dirección')->width('42')
            ->format(function ($student) use ($address) { return $address($student); });

        foreach ([0 => 'Acudiente 1', 1 => 'Acudiente 2'] as $index => $label) {
            foreach (['name' => ['Nombre', 28], 'rel' => ['Parentesco', 18], 'phone' => ['Teléfonos', 34], 'email' => ['Correo', 32]] as $field => $info) {
                $id = 'adult'.ucfirst($field).$index;
                $groups[$id] = $label;
                $table->addColumn($id, $info[0])->width((string) $info[1])
                    ->format(function ($student) use ($adultField, $index, $field) { return $adultField($student, $index, $field); });
            }
        }

        $table->addMetaData('groups', $groups);
        echo $table->render($transport);
        return;
    }

//    $table->addColumn('transport', __('Transport'))
//        ->context('primary');
    $table->addColumn('formGroup', __('Form Group'))
        ->context('secondary')
        ->width('10%');
    $table->addColumn('student', __('Student'))
        ->context('primary')
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', true]));
    
    $view = new View($container->get('twig'));

    $table->addColumn('address1', __('Address'))
        ->width('30%')
        ->notSortable()
        ->format(function ($student) use ($view) {
            return $view->fetchFromTemplate(
                'formats/familyAddresses.twig.html',
                ['families' => $student['families'], 'person' => $student]
            );
        });

    $table->addColumn('contacts', __('Parental Contacts'))
        ->context('secondary')
        ->width('30%')
        ->notSortable()
        ->format(function ($student) use ($view) {
            return $view->fetchFromTemplate(
                'formats/familyContacts.twig.html',
                ['familyAdults' => $student['familyAdults'], 'includePhoneNumbers' => true]
            );
        });

    echo $table->render($transport);
}
