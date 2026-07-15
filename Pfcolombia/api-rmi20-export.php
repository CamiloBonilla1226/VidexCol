<?php
session_start();
require_once('funciones.php');

// Establecer headers para JSON
header('Content-Type: application/json; charset=utf-8');

$PSN1 = new DBbase_Sql;
$PSN3 = new DBbase_Sql;

$limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 1000;
$offset = isset($_REQUEST['offset']) ? intval($_REQUEST['offset']) : 0;

$sqlFiltro = "";
$sqlFiltro .= " AND RC.generacion != 0";

if(isset($_REQUEST["idUsuario"]) && trim($_REQUEST["idUsuario"]) != "" && soloNumeros($_REQUEST["idUsuario"]) != ""){
    $buscar_idUsuario = soloNumeros($_REQUEST["idUsuario"]);
    $sqlFiltro .= " AND RC.usuario_id = '".$buscar_idUsuario."'";
}

if(isset($_REQUEST["sitioReunion"]) && trim($_REQUEST["sitioReunion"]) != "" && soloNumeros($_REQUEST["sitioReunion"]) != ""){
    $buscar_prision = soloNumeros($_REQUEST["sitioReunion"]);
    $sqlFiltro .= " AND RC.carcel_id = ".$buscar_prision."";
}

if(isset($_REQUEST["fechaInicial"]) && eliminarInvalidos($_REQUEST["fechaInicial"]) != ""){
    $fechaInicial = eliminarInvalidos($_REQUEST["fechaInicial"]);
    $sqlFiltro .= " AND RC.fecha_reporte >= '".$fechaInicial."'";
}else{
    $fechaInicial = '';
}

if(isset($_REQUEST["fechaFinal"]) && eliminarInvalidos($_REQUEST["fechaFinal"]) != ""){
    $fechaFinal = eliminarInvalidos($_REQUEST["fechaFinal"]);
    $sqlFiltro .= " AND RC.fecha_reporte <= '".$fechaFinal."'";
}else{
    $fechaFinal = '';
}

$response = [
    'success' => true,
    'total_registros' => 0,
    'data' => []
];

$sql = "SELECT RC.*, U.nombre as nombreUsuario, U.direccion as direccionUsuario, U.identificacion as identificacionUsuario,
        UE.empresa_rm
        FROM reporte_cm AS RC
        LEFT JOIN usuario AS U ON U.id = RC.usuario_id
        LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = RC.usuario_id
        WHERE 1 ".$sqlFiltro."
        ORDER BY RC.fecha_reporte DESC, RC.id_cm DESC
        LIMIT ".$offset.", ".$limit;

$PSN1->query($sql);
$numero = $PSN1->num_rows();
$response['total_registros'] = $numero;

if($numero > 0){
    while($PSN1->next_record()){
        $suma = intval($PSN1->f('mapeo_oracion')) + intval($PSN1->f('mapeo_companerismo')) +
               intval($PSN1->f('mapeo_adoracion')) + intval($PSN1->f('mapeo_biblia')) +
               intval($PSN1->f('mapeo_evangelizar')) + intval($PSN1->f('mapeo_cena')) +
               intval($PSN1->f('mapeo_dar')) + intval($PSN1->f('mapeo_bautizar')) +
               intval($PSN1->f('mapeo_trabajadores'));

        $promedio = $suma > 0 ? round($suma/9, 2) : 0;

        $tipo = $PSN1->f('tipo');
        $carcel_id = $PSN1->f('carcel_id');
        $municipio_id = $PSN1->f('municipio_id');
        $usuario_id = $PSN1->f('usuario_id');

        // Ubicación del grupo: INTRA -> prisión, EXTRA -> municipio/dirección propia
        $groupLocation = '';
        $ministryPartner = '';
        if($tipo == 'INTRA' && $carcel_id){
            $PSN3->query("SELECT RUU.reub_nom AS prision, RUU.reub_dir AS dire_prision,
                         CA.descripcion AS rgal_prision, MU.municipio AS mnpo_prision, DE.departamento AS dpto_prision
                         FROM tbl_regional_ubicacion AS RUU
                         LEFT JOIN categorias AS CA ON CA.id = RUU.reub_reg_fk
                         LEFT JOIN dane_municipios AS MU ON MU.id_municipio = RUU.reub_mun_fk
                         LEFT JOIN dane_departamentos AS DE ON DE.id_departamento = MU.departamento_id
                         WHERE RUU.reub_id = " . $carcel_id);
            if($PSN3->next_record()){
                $groupLocation = trim($PSN3->f('prision').' / '.$PSN3->f('dpto_prision').' / '.$PSN3->f('mnpo_prision').' / '.$PSN3->f('dire_prision'), ' /');
                $ministryPartner = $PSN3->f('rgal_prision');
            }
        }else if($municipio_id){
            $PSN3->query("SELECT M.municipio AS mnpo_extra, D.departamento AS dpto_extra
                         FROM dane_municipios AS M
                         LEFT JOIN dane_departamentos AS D ON D.id_departamento = M.departamento_id
                         WHERE M.id_municipio = " . $municipio_id);
            if($PSN3->next_record()){
                $groupLocation = trim($PSN3->f('dpto_extra').' / '.$PSN3->f('mnpo_extra').' / '.$PSN1->f('direccion'), ' /');
            }
        }

        // Ubicación y regional del entrenador/coordinador
        $trainerLocation = '';
        if(!$ministryPartner){
            // Si no vino de la prisión (EXTRA o sin carcel_id), se toma del usuario
            if($usuario_id){
                $PSN3->query("SELECT C.descripcion AS rgal_usuario
                             FROM usuario_empresa
                             LEFT JOIN categorias AS C ON C.id = usuario_empresa.empresa_pd
                             WHERE usuario_empresa.idUsuario = " . $usuario_id);
                if($PSN3->next_record()){
                    $ministryPartner = $PSN3->f('rgal_usuario');
                }
            }
        }
        if($usuario_id){
            $PSN3->query("SELECT DM.municipio AS mnpo_usuario, DD.departamento AS dpto_usuario
                         FROM usuario
                         LEFT JOIN dane_municipios AS DM ON DM.id_municipio = usuario.usua_muni
                         LEFT JOIN dane_departamentos AS DD ON DD.id_departamento = DM.departamento_id
                         WHERE usuario.id = " . $usuario_id);
            if($PSN3->next_record()){
                $trainerLocation = trim($PSN3->f('dpto_usuario').' / '.$PSN3->f('mnpo_usuario').' / '.$PSN1->f('direccionUsuario'), ' /');
            }
        }

        $response['data'][] = [
            'Team' => $PSN1->f('empresa_rm'),
            'leaderName' => $PSN1->f('nombreUsuario'),
            'groupName' => $PSN1->f('nombre_grupo_iglesia'),
            'dateStart' => $PSN1->f('fecha_inicio_confraternidad'),
            'Generation' => intval($PSN1->f('generacion')),
            'groupLocation' => $groupLocation,
            'motherGroup' => $PSN1->f('grupo_madre'),
            'groupAttendance' => intval($PSN1->f('asistencia_total')),
            'totalBelievers' => intval($PSN1->f('en_discipulado')),
            'newBelievers' => intval($PSN1->f('decisiones_cristo')),
            'totalBaptized' => intval($PSN1->f('miembros_bautizados')),
            'newBaptized' => intval($PSN1->f('bautizados_periodo')),
            'Prayer' => intval($PSN1->f('mapeo_oracion')),
            'Fellowship' => intval($PSN1->f('mapeo_companerismo')),
            'Worship' => intval($PSN1->f('mapeo_adoracion')),
            'applyBible' => intval($PSN1->f('mapeo_biblia')),
            'Evangelism' => intval($PSN1->f('mapeo_evangelizar')),
            'LordsSupper' => intval($PSN1->f('mapeo_cena')),
            'Giving' => intval($PSN1->f('mapeo_dar')),
            'Baptism' => intval($PSN1->f('mapeo_bautizar')),
            'Workers' => intval($PSN1->f('mapeo_trabajadores')),
            // Sin fuente en el modelo actual, se dejan vacíos hasta que exista el dato
            'userDef1' => '',
            'userDef2' => '',
            'userDef3' => '',
            'userDef4' => '',
            'extra' => '',
            'trainerLocation' => $trainerLocation,
            'Coach' => $PSN1->f('entrenador'),
            'groupID' => $PSN1->f('id_cm'),
            'isChurch' => '',
            'healthSum' => $suma,
            'healthAvg' => $promedio,
            'dateFrom' => $fechaInicial,
            'dateTo' => $fechaFinal,
            'dateCollected' => $PSN1->f('mapeo_fecha'),
            'ministryPartner' => $ministryPartner,
            'Denomination' => '',
            'lat' => '',
            'lon' => ''
        ];
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
