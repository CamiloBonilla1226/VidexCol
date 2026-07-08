<?php
session_start();
require_once('funciones.php');

// Establecer headers para JSON
header('Content-Type: application/json; charset=utf-8');

$PSN1 = new DBbase_Sql;

// Validar fechas
if(!isset($_REQUEST["fechaInicial"]) || eliminarInvalidos($_REQUEST["fechaInicial"]) == ""){
    $_REQUEST["fechaInicial"] = '2000-01-01';
}
if(!isset($_REQUEST["fechaFinal"]) || eliminarInvalidos($_REQUEST["fechaFinal"]) == ""){
    $_REQUEST["fechaFinal"] = date("Y-m-d");
}
$fechaInicial = eliminarInvalidos($_REQUEST["fechaInicial"]);
$fechaFinal = eliminarInvalidos($_REQUEST["fechaFinal"]);

// Manejar período trimestral
if (!empty($_REQUEST['rep_ani'])) {
    $anio = $_REQUEST['rep_ani'];
}else{
    $anio = date('Y');
}
if (!empty($_REQUEST['rep_qua'])) {
    $q = $_REQUEST['rep_qua'];
    $iniQ = $anio.'-'.$q.'-01';
    $iniQ = date("Y-m-d", strtotime($iniQ));
    if ($_REQUEST['rep_qua']==1) {
        $finQ = $anio.'-'.($q+2).'-31';
    }else if ($_REQUEST['rep_qua']==10) {
        $finQ = $anio.'-'.($q+2).'-31';
    }else{
        $finQ = $anio.'-'.($q+2).'-30';
    }
    $finQ = date("Y-m-d", strtotime($finQ));
}else{
   $iniQ = $fechaInicial;
   $finQ = $fechaFinal;
}

// Construir filtros SQL (todos sobre reporte_cm, alias RC)
$sqlFiltro = "";
$sqlFiltro .= " AND RC.fecha_reporte >= '".$fechaInicial."'";
$sqlFiltro .= " AND RC.fecha_reporte <= '".$fechaFinal."'";

if(isset($_REQUEST["idUsuario"]) && trim($_REQUEST["idUsuario"]) != "" && soloNumeros($_REQUEST["idUsuario"]) != ""){
    $buscar_idUsuario = soloNumeros($_REQUEST["idUsuario"]);
    $sqlFiltro .= " AND RC.usuario_id = '".$buscar_idUsuario."'";
}

if(isset($_REQUEST["empresa_pd"]) && trim($_REQUEST["empresa_pd"]) != "" && soloNumeros($_REQUEST["empresa_pd"]) != ""){
    $buscar_regional = soloNumeros($_REQUEST["empresa_pd"]);
    $sqlFiltro .= " AND UE.empresa_pd = '".$buscar_regional."'";
}

if(isset($_REQUEST["sitioReunion"]) && trim($_REQUEST["sitioReunion"]) != "" && soloNumeros($_REQUEST["sitioReunion"]) != ""){
    $buscar_prision = soloNumeros($_REQUEST["sitioReunion"]);
    $sqlFiltro .= " AND RC.carcel_id = ".$buscar_prision."";
}

if(isset($_REQUEST["empresa_sitio_cor"]) && trim($_REQUEST["empresa_sitio_cor"]) != "" && soloNumeros($_REQUEST["empresa_sitio_cor"]) != ""){
    $buscar_zona = soloNumeros($_REQUEST["empresa_sitio_cor"]);
    $sqlFiltro .= " AND CA.id = '".$buscar_zona."'";
}

if(isset($_REQUEST["rep_qua"]) && trim($_REQUEST["rep_qua"]) != "" && soloNumeros($_REQUEST["rep_qua"]) != ""){
    $buscar_periodo = soloNumeros($_REQUEST["rep_qua"]);
    $sqlFiltro .= " AND RC.mapeo_cuarto = '".$buscar_periodo."'";
}

if(isset($_REQUEST["rep_inex"]) && eliminarInvalidos($_REQUEST["rep_inex"]) != ""){
    $tipo = eliminarInvalidos($_REQUEST["rep_inex"]);
    if ($tipo == "INTRA" || $tipo == "EXTRA") {
        $sqlFiltro .= " AND RC.tipo = '".$tipo."'";
    }
}

if(isset($_REQUEST["empresa_paisid"]) && soloNumeros($_REQUEST["empresa_paisid"]) != ""){
    $empresa_paisid = soloNumeros($_REQUEST["empresa_paisid"]);
    $sqlFiltro .= " AND UE.empresa_paisid = '".$empresa_paisid."'";
}

// Datos para respuesta
$response = [
    'success' => true,
    'fechaInicial' => $fechaInicial,
    'fechaFinal' => $fechaFinal,
    'periodoInicio' => $iniQ,
    'periodoFin' => $finQ,
    'usuario' => $_SESSION["nombre"],
    'total_registros' => 0,
    'data' => []
];

// Consulta única con los joins necesarios (sin N+1): ubicación de la cárcel,
// ubicación extramuro (departamento/municipio), regional/zona del usuario y curso de graduación.
$sql = "SELECT RC.*,
            U.nombre AS nombreUsuario, U.identificacion AS identificacionUsuario,
            UE.empresa_sitio, UE.empresa_rm, UE.empresa_proceso,
            RU.reub_nom AS nombre_prision, RU.reub_dir AS direccion_prision,
            DM.municipio AS municipio_nombre, DD.departamento AS departamento_nombre,
            C.descripcion AS regional_nombre, CA.descripcion AS zona_nombre,
            CUR.descripcion AS curso_graduacion
        FROM reporte_cm AS RC
        LEFT JOIN usuario AS U ON U.id = RC.usuario_id
        LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = RC.usuario_id
        LEFT JOIN categorias AS C ON C.id = UE.empresa_pd
        LEFT JOIN categorias AS CA ON CA.id = C.idSec
        LEFT JOIN tbl_regional_ubicacion AS RU ON RU.reub_id = RC.carcel_id
        LEFT JOIN dane_municipios AS DM ON DM.id_municipio = RC.municipio_id
        LEFT JOIN dane_departamentos AS DD ON DD.id_departamento = RC.departamento_id
        LEFT JOIN categorias AS CUR ON CUR.id = RC.graduacion_curso_id
        WHERE 1 ".$sqlFiltro."
        ORDER BY CA.descripcion ASC, U.nombre ASC, RC.fecha_reporte ASC";

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

        // Ubicación legible según el tipo de confraternidad
        if($PSN1->f('tipo') == 'INTRA'){
            $ubicacion = trim($PSN1->f('nombre_prision').($PSN1->f('pabellon') != '' ? ' - Pabellón '.$PSN1->f('pabellon') : ''));
        } else {
            $ubicacion = trim($PSN1->f('departamento_nombre').' / '.$PSN1->f('municipio_nombre').' / '.$PSN1->f('direccion'), ' /');
        }

        $response['data'][] = [
            'id_cm' => $PSN1->f('id_cm'),
            'usuario_id' => $PSN1->f('usuario_id'),
            'nombreUsuario' => $PSN1->f('nombreUsuario'),
            'identificacionUsuario' => $PSN1->f('identificacionUsuario'),

            'empresa_sitio' => $PSN1->f('empresa_sitio'),
            'empresa_rm' => $PSN1->f('empresa_rm'),
            'empresa_proceso' => $PSN1->f('empresa_proceso'),
            'regional_nombre' => $PSN1->f('regional_nombre'),
            'zona_nombre' => $PSN1->f('zona_nombre'),

            'entrenador' => $PSN1->f('entrenador'),
            'siervo_facilitador' => $PSN1->f('siervo_facilitador'),
            'tipo' => $PSN1->f('tipo'),
            'fecha_reporte' => $PSN1->f('fecha_reporte'),
            'fecha_inicio_confraternidad' => $PSN1->f('fecha_inicio_confraternidad'),
            'generacion' => $PSN1->f('generacion'),
            'nombre_grupo_iglesia' => $PSN1->f('nombre_grupo_iglesia'),
            'grupo_madre' => $PSN1->f('grupo_madre'),
            'ubicacion' => $ubicacion,

            'asistencia_hombres' => intval($PSN1->f('asistencia_hombres')),
            'asistencia_mujeres' => intval($PSN1->f('asistencia_mujeres')),
            'asistencia_jovenes' => intval($PSN1->f('asistencia_jovenes')),
            'asistencia_ninos' => intval($PSN1->f('asistencia_ninos')),
            'asistencia_total' => intval($PSN1->f('asistencia_total')),

            'miembros_bautizados' => intval($PSN1->f('miembros_bautizados')),
            'bautizados_periodo' => intval($PSN1->f('bautizados_periodo')),
            'en_discipulado' => intval($PSN1->f('en_discipulado')),
            'decisiones_cristo' => intval($PSN1->f('decisiones_cristo')),
            'preparandose_bautismo' => intval($PSN1->f('preparandose_bautismo')),
            'graduados_periodo' => intval($PSN1->f('graduados_periodo')),
            'curso_graduacion' => $PSN1->f('curso_graduacion'),

            'mapeo_oracion' => intval($PSN1->f('mapeo_oracion')),
            'mapeo_companerismo' => intval($PSN1->f('mapeo_companerismo')),
            'mapeo_adoracion' => intval($PSN1->f('mapeo_adoracion')),
            'mapeo_biblia' => intval($PSN1->f('mapeo_biblia')),
            'mapeo_evangelizar' => intval($PSN1->f('mapeo_evangelizar')),
            'mapeo_cena' => intval($PSN1->f('mapeo_cena')),
            'mapeo_dar' => intval($PSN1->f('mapeo_dar')),
            'mapeo_bautizar' => intval($PSN1->f('mapeo_bautizar')),
            'mapeo_trabajadores' => intval($PSN1->f('mapeo_trabajadores')),
            'mapeo_comprometido' => intval($PSN1->f('mapeo_comprometido')),
            'mapeo_fecha' => $PSN1->f('mapeo_fecha'),
            'mapeo_suma' => $suma,
            'mapeo_promedio' => $promedio,
        ];
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
