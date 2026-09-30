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
use Gibbon\Tables\Prefab\ReportTable;
use Modules\InformesEscolares\InformesEscolaresGateway;

require_once __DIR__ . '/src/InformesEscolaresGateway.php';

if (isActionAccessible($guid, $connection2, '/modules/Informes Escolares/reportPlanning.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {

	$search = $_GET['search'] ?? '';
    $today = date('Y-m-d');
    $fecha_inicial = isset($_GET['fecha_inicial'])? Format::dateConvert($_GET['fecha_inicial']) : $today;
    $fecha_final = isset($_GET['fecha_final'])? Format::dateConvert($_GET['fecha_final']) : $today;
    $viewMode = isset($_REQUEST['format']) ? $_REQUEST['format'] : '';


    $reportPlanning = $container->get(InformesEscolaresGateway::class);
    $criteria = $reportPlanning->newQueryCriteria(true)
        ->searchBy($reportPlanning->getSearchableColumns(), $search)
        ->sortBy(['surname', 'preferredName'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromArray($_POST);

    $form = Form::create('filter', $gibbon->session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Search'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$gibbon->session->get('module').'/reportPlanning.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($criteria->getSearchText());


    $row = $form->addRow();
            $row->addLabel('fecha_inicial', 'Fecha inicial')->description(__m('Fecha inicial'));
            $row->addDate('fecha_inicial')->setValue(Format::date($fecha_inicial));

    $row = $form->addRow();
            $row->addLabel('fecha_final', 'Fecha final')->description(__m('Fecha final'));
            $row->addDate('fecha_final')->setValue(Format::date($fecha_final));

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();


    // QUERY
    $dataSet = $reportPlanning->queryPlanning($criteria, $fecha_inicial, $fecha_final);


    // DATA TABLE
    $table = ReportTable::createPaginated('reportPlanning', $criteria)->setViewMode($viewMode, $gibbon->session);


    $table->setTitle(__('Report Data'));

    // COLUMNS
    $table->addColumn('image_240', __('Photo'))
        ->width('10%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'sm']));

    $table->addColumn('name', __('Name'))
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Staff', true,]));

    $table->addColumn('numero_planning', __('Número de Observaciones'));


    echo $table->render($dataSet);

}