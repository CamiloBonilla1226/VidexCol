<?php
// Variables básicas del módulo
$name = 'Graficas';
$description = 'Dashboard de gráficas de estudiantes, docentes y familias';
$entryURL = 'estudiantes.php';
$type = 'Additional';
$category = 'Informes';
$version = '1.0.0';
$author = 'Equipo CEMA';
$url = 'https://cema.pe.edu.co';

$moduleTables = array();
$gibbonSetting = array();

$acciones = array(
    array('Graficas de estudiantes', 'estudiantes.php'),
    array('Graficas docentes', 'docentes.php'),
    array('Graficas familias', 'familias.php'),
);

$actionRows = array();
foreach ($acciones as $i => $accion) {
    $actionRows[$i]['name'] = $accion[0];
    $actionRows[$i]['precedence'] = '0';
    $actionRows[$i]['category'] = 'Dashboard';
    $actionRows[$i]['description'] = $accion[0];
    $actionRows[$i]['URLList'] = $accion[1];
    $actionRows[$i]['entryURL'] = $accion[1];
    $actionRows[$i]['entrySidebar'] = 'Y';
    $actionRows[$i]['menuShow'] = 'Y';
    $actionRows[$i]['defaultPermissionAdmin'] = 'Y';
    $actionRows[$i]['defaultPermissionTeacher'] = 'N';
    $actionRows[$i]['defaultPermissionStudent'] = 'N';
    $actionRows[$i]['defaultPermissionParent'] = 'N';
    $actionRows[$i]['defaultPermissionSupport'] = 'N';
    $actionRows[$i]['categoryPermissionStaff'] = 'Y';
    $actionRows[$i]['categoryPermissionStudent'] = 'N';
    $actionRows[$i]['categoryPermissionParent'] = 'N';
    $actionRows[$i]['categoryPermissionOther'] = 'N';
}

$hooks = array();
