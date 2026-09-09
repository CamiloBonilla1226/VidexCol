<?php
/*
 * ajax_cantones_por_provincia.php
 * Endpoint JSON usado por reportar_capacitador.php / editar_capacitador.php
 * para cargar el <select> de cantones (dane_municipios) según la provincia
 * (dane_departamentos) elegida.
 */
session_start();
include_once('funciones.php');

header('Content-Type: application/json; charset=utf-8');

$idUsuarioSesion = isset($_SESSION["id"]) ? intval($_SESSION["id"]) : 0;
$idProvincia = isset($_REQUEST["provincia_id"]) ? intval($_REQUEST["provincia_id"]) : 0;

if($idUsuarioSesion == 0){
    http_response_code(401);
    echo json_encode(array());
    exit;
}

$lista = array();

if($idProvincia > 0){
    $PSN1 = new DBbase_Sql;
    $PSN1->connect();
    $PSN1->query("SELECT id_municipio, municipio FROM dane_municipios WHERE departamento_id = ".$idProvincia." ORDER BY municipio ASC");
    while($PSN1->next_record()){
        $lista[] = array(
            "id"     => intval($PSN1->f("id_municipio")),
            "nombre" => $PSN1->f("municipio"),
        );
    }
}

echo json_encode($lista, JSON_UNESCAPED_UNICODE);
