<?php
session_start();
require_once('funciones.php');

// Establecer headers para JSON
header('Content-Type: application/json; charset=utf-8');

$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;
$PSN3 = new DBbase_Sql;

// Validar fechas
if(!isset($_REQUEST["fechaInicial"]) || eliminarInvalidos($_REQUEST["fechaInicial"]) == ""){
    $_REQUEST["fechaInicial"] = '2000-01-01';
}
if(!isset($_REQUEST["fechaFinal"]) || eliminarInvalidos($_REQUEST["fechaFinal"]) == ""){
    $_REQUEST["fechaFinal"] = date("Y-m-d");
}

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
   $iniQ = $_REQUEST["fechaInicial"];
   $finQ = $_REQUEST["fechaFinal"];
}

// Construir filtros SQL
$sqlFiltro = "";
$sqlFiltro .= " AND RC.generacion != 0";

if(isset($_REQUEST["idUsuario"]) && trim($_REQUEST["idUsuario"]) != "" && soloNumeros($_REQUEST["idUsuario"]) != ""){
    $buscar_idUsuario = soloNumeros($_REQUEST["idUsuario"]);
    $sqlFiltro .= " AND RC.usuario_id = '".$buscar_idUsuario."'";
}

if(isset($_REQUEST["empresa_pd"]) && trim($_REQUEST["empresa_pd"]) != "" && soloNumeros($_REQUEST["empresa_pd"]) != ""){
    $buscar_regional = soloNumeros($_REQUEST["empresa_pd"]);
    $sqlFiltro .= " AND RU.reub_reg_fk = '".$buscar_regional."'";
}

if(isset($_REQUEST["sitioReunion"]) && trim($_REQUEST["sitioReunion"]) != "" && soloNumeros($_REQUEST["sitioReunion"]) != ""){
    $buscar_prision = soloNumeros($_REQUEST["sitioReunion"]);
    $sqlFiltro .= " AND RC.carcel_id = ".$buscar_prision."";
}

if(isset($_REQUEST["empresa_sitio_cor"]) && trim($_REQUEST["empresa_sitio_cor"]) != "" && soloNumeros($_REQUEST["empresa_sitio_cor"]) != ""){
    $buscar_zona = soloNumeros($_REQUEST["empresa_sitio_cor"]);
    $sqlFiltro .= " AND C.idSec = '".$buscar_zona."'";
}

if(isset($_REQUEST["rep_qua"]) && trim($_REQUEST["rep_qua"]) != "" && soloNumeros($_REQUEST["rep_qua"]) != ""){
    $buscar_periodo = soloNumeros($_REQUEST["rep_qua"]);
    $sqlFiltro .= " AND RC.mapeo_cuarto = '".$buscar_periodo."'";
}

if(isset($_REQUEST["rep_inex"]) && eliminarInvalidos($_REQUEST["rep_inex"]) != ""){
    $tipo = eliminarInvalidos($_REQUEST["rep_inex"]);
    if ($tipo == 2) {
        $sqlFiltro .= " AND RC.tipo = 'EXTRA' ";
    }else{
        $sqlFiltro .= " AND RC.tipo = 'INTRA' ";
    }
}

if(isset($_REQUEST["fechaInicial"]) && eliminarInvalidos($_REQUEST["fechaInicial"]) != ""){
    $fechaInicial = eliminarInvalidos($_REQUEST["fechaInicial"]);
    $sqlFiltro .= " AND RC.fecha_reporte >= '".$fechaInicial."'";
}

if(isset($_REQUEST["fechaFinal"]) && eliminarInvalidos($_REQUEST["fechaFinal"]) != ""){
    $fechaFinal = eliminarInvalidos($_REQUEST["fechaFinal"]);
    $sqlFiltro .= " AND RC.fecha_reporte <= '".$fechaFinal."'";
}

if(isset($_REQUEST["empresa_paisid"]) && soloNumeros($_REQUEST["empresa_paisid"]) != ""){
    $empresa_paisid = soloNumeros($_REQUEST["empresa_paisid"]);
    $sqlFiltro .= " AND usuario_empresa.empresa_paisid = '".$empresa_paisid."'";
}

// Determinar si solo hay filtros básicos
$only_basic_filters = (
    strpos($sqlFiltro, 'RU.reub_reg_fk') === false &&
    strpos($sqlFiltro, 'C.idSec') === false &&
    strpos($sqlFiltro, 'usuario_empresa.empresa_paisid') === false
);

// Paso 1: Obtener IDs de usuarios
if ($only_basic_filters) {
    $sql_user_ids = "SELECT DISTINCT RC.usuario_id FROM reporte_cm AS RC
    WHERE 1 ".$sqlFiltro."
    ORDER BY RC.usuario_id";
} else {
    $sql_user_ids = "SELECT DISTINCT RC.usuario_id FROM reporte_cm AS RC";

    $needs_regional_join = (strpos($sqlFiltro, 'RU.reub_reg_fk') !== false);
    $needs_categoria_join = (strpos($sqlFiltro, 'C.idSec') !== false);
    $needs_empresa_join = (strpos($sqlFiltro, 'usuario_empresa.empresa_paisid') !== false);

    if ($needs_regional_join || $needs_categoria_join) {
        $sql_user_ids .= " LEFT JOIN tbl_regional_ubicacion AS RU ON RU.reub_id = RC.carcel_id";
    }
    if ($needs_categoria_join) {
        $sql_user_ids .= " LEFT JOIN categorias AS C ON C.id = RU.reub_reg_fk";
    }
    if ($needs_empresa_join) {
        $sql_user_ids .= " LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = RC.usuario_id";
    }

    $sql_user_ids .= " WHERE 1 ".$sqlFiltro."
    ORDER BY RC.usuario_id";
}

$PSN_user_ids = new DBbase_Sql;
$PSN_user_ids->query($sql_user_ids);
$user_ids = [];
while($PSN_user_ids->next_record()){
    $user_ids[] = $PSN_user_ids->f('usuario_id');
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

// Paso 2: Obtener datos detallados (no agrupados) para mantener la estructura original
if (count($user_ids) > 0) {
    $sql = "SELECT RC.*, usuario.nombre as nombreUsuario, usuario.direccion as direccionUsuario, usuario.identificacion as identificacionUsuario, ";
    $sql .= " usuario_empresa.empresa_sitio, usuario_empresa.empresa_rm, usuario_empresa.empresa_proceso, usuario_empresa.empresa_paisid ";
    $sql .= " FROM reporte_cm AS RC ";
    $sql .= " LEFT JOIN usuario ON usuario.id = RC.usuario_id";
    $sql .= " LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = RC.usuario_id";
    $sql .= " WHERE RC.usuario_id IN (" . implode(',', $user_ids) . ") ".$sqlFiltro;
    $sql .= " ORDER BY usuario_empresa.empresa_paisid ASC, usuario.nombre ASC";

    $PSN1->query($sql);
    $numero = $PSN1->num_rows();
    $response['total_registros'] = $numero;

    if($numero > 0) {
        while($PSN1->next_record()) {
            // Calcular suma de mapeos para promedio
            $suma = intval($PSN1->f('mapeo_oracion')) + intval($PSN1->f('mapeo_companerismo')) +
                   intval($PSN1->f('mapeo_adoracion')) + intval($PSN1->f('mapeo_biblia')) +
                   intval($PSN1->f('mapeo_evangelizar')) + intval($PSN1->f('mapeo_cena')) +
                   intval($PSN1->f('mapeo_dar')) + intval($PSN1->f('mapeo_bautizar')) +
                   intval($PSN1->f('mapeo_trabajadores'));

            $promedio = $suma > 0 ? round($suma/9, 2) : 0;

            // El nombre del grupo/iglesia viene directamente de reporte_cm
            $grupoMadre_txt = $PSN1->f('grupo_madre');

            // Obtener datos de ubicación con consultas separadas para evitar problemas con JOINs
            $dpto_usuario = '';
            $mnpo_usuario = '';
            $rgal_usuario = '';

            // Consulta para datos del usuario
            if($PSN1->f('usuario_id')) {
                $PSN3->query("SELECT DM.municipio AS mnpo_usuario, DD.departamento AS dpto_usuario, C.descripcion AS rgal_usuario
                             FROM usuario
                             LEFT JOIN dane_municipios AS DM ON DM.id_municipio = usuario.usua_muni
                             LEFT JOIN dane_departamentos AS DD ON DD.id_departamento = DM.departamento_id
                             LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = usuario.id
                             LEFT JOIN categorias AS C ON C.id = usuario_empresa.empresa_pd
                             WHERE usuario.id = " . $PSN1->f('usuario_id'));
                if($PSN3->next_record()) {
                    $dpto_usuario = $PSN3->f('dpto_usuario');
                    $mnpo_usuario = $PSN3->f('mnpo_usuario');
                    $rgal_usuario = $PSN3->f('rgal_usuario');
                }
            }

            // Datos de prisión/ubicación (INTRA)
            $prision = '';
            $dire_prision = '';
            $dpto_prision = '';
            $mnpo_prision = '';
            $rgal_prision = '';
            $dpto_prision_extra = '';
            $mnpo_prision_extra = '';

            // Consulta para datos de prisión si carcel_id existe
            if($PSN1->f('carcel_id')) {
                $PSN3->query("SELECT RUU.reub_nom AS prision, RUU.reub_dir AS dire_prision,
                             CA.descripcion AS rgal_prision, MU.municipio AS mnpo_prision, DE.departamento AS dpto_prision
                             FROM tbl_regional_ubicacion AS RUU
                             LEFT JOIN categorias AS CA ON CA.id = RUU.reub_reg_fk
                             LEFT JOIN dane_municipios AS MU ON MU.id_municipio = RUU.reub_mun_fk
                             LEFT JOIN dane_departamentos AS DE ON DE.id_departamento = MU.departamento_id
                             WHERE RUU.reub_id = " . $PSN1->f('carcel_id'));
                if($PSN3->next_record()) {
                    $prision = $PSN3->f('prision');
                    $dire_prision = $PSN3->f('dire_prision');
                    $dpto_prision = $PSN3->f('dpto_prision');
                    $mnpo_prision = $PSN3->f('mnpo_prision');
                    $rgal_prision = $PSN3->f('rgal_prision');
                }
            }

            // Consulta para municipio y departamento de confraternidades EXTRA (RC.municipio_id)
            if($PSN1->f('municipio_id')) {
                $PSN3->query("SELECT M.municipio AS mnpo_prision_extra, D.departamento AS dpto_prision_extra
                             FROM dane_municipios AS M
                             LEFT JOIN dane_departamentos AS D ON D.id_departamento = M.departamento_id
                             WHERE M.id_municipio = " . $PSN1->f('municipio_id'));
                if($PSN3->next_record()) {
                    $dpto_prision_extra = $PSN3->f('dpto_prision_extra');
                    $mnpo_prision_extra = $PSN3->f('mnpo_prision_extra');
                }
            }

            // Agregar registro individual al array de datos (estructura del Excel original)
            $response['data'][] = [
                // Datos básicos del usuario
                'idUsuario' => $PSN1->f('usuario_id'),
                'nombreUsuario' => $PSN1->f("nombreUsuario"),
                'identificacionUsuario' => $PSN1->f("identificacionUsuario"),
                'direccionUsuario' => $PSN1->f("direccionUsuario"),

                // Datos de empresa/ubicación
                'empresa_sitio' => $PSN1->f("empresa_sitio"),
                'empresa_rm' => $PSN1->f("empresa_rm"),
                'empresa_proceso' => $PSN1->f("empresa_proceso"),
                'empresa_paisid' => $PSN1->f("empresa_paisid"),

                // Datos del grupo
                'tipo' => $PSN1->f("tipo"),
                'rep_entr' => $PSN1->f("entrenador"),
                'nombreGrupo_txt' => $PSN1->f("nombre_grupo_iglesia"),
                'grupoMadre_txt' => $grupoMadre_txt,
                'fechaInicio' => $PSN1->f("fecha_inicio_confraternidad"),
                'generacionNumero' => $PSN1->f("generacion"),

                // Datos de asistencia y métricas
                'asistencia_total' => intval($PSN1->f("asistencia_total")),
                'discipulado' => intval($PSN1->f("en_discipulado")),
                'desiciones' => intval($PSN1->f("decisiones_cristo")),
                'bautizados' => intval($PSN1->f("miembros_bautizados")),
                'bautizadosPeriodo' => intval($PSN1->f("bautizados_periodo")),
                'preparandose' => intval($PSN1->f("preparandose_bautismo")),
                'graduados' => intval($PSN1->f("graduados_periodo")),
                'graduadosPeriodo' => intval($PSN1->f("graduados_periodo")),
                'iglesias_reconocidas' => 0,

                // Mapeos espirituales
                'mapeo_oracion' => intval($PSN1->f("mapeo_oracion")),
                'mapeo_companerismo' => intval($PSN1->f("mapeo_companerismo")),
                'mapeo_adoracion' => intval($PSN1->f("mapeo_adoracion")),
                'mapeo_biblia' => intval($PSN1->f("mapeo_biblia")),
                'mapeo_evangelizar' => intval($PSN1->f("mapeo_evangelizar")),
                'mapeo_cena' => intval($PSN1->f("mapeo_cena")),
                'mapeo_dar' => intval($PSN1->f("mapeo_dar")),
                'mapeo_bautizar' => intval($PSN1->f("mapeo_bautizar")),
                'mapeo_trabajadores' => intval($PSN1->f("mapeo_trabajadores")),
                'mapeo_comprometido' => intval($PSN1->f("mapeo_comprometido")),
                'mapeo_fecha' => $PSN1->f("mapeo_fecha"),
                'mapeo_suma' => $suma,
                'mapeo_promedio' => $promedio,

                // Ubicaciones detalladas
                'dpto_usuario' => $dpto_usuario,
                'mnpo_usuario' => $mnpo_usuario,
                'prision' => $prision,
                'dire_prision' => $dire_prision,
                'dpto_prision' => $dpto_prision,
                'mnpo_prision' => $mnpo_prision,
                'dpto_prision_extra' => $dpto_prision_extra,
                'mnpo_prision_extra' => $mnpo_prision_extra,
                'rgal_usuario' => $rgal_usuario,
                'rgal_prision' => $rgal_prision,
                'direccion' => $PSN1->f("direccion")
            ];
        }
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
