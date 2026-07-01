<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\SpreadSheet;
///use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
/*
*	CONSOLIDADO CON SUMATORIA DE CAMPOS
*/
// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;


// Si la fecha no fue declarada o no existe, se toma la fecha de 2021-02-01 como fecha inicial
if(!isset($_REQUEST["fechaInicial"]) || eliminarInvalidos($_REQUEST["fechaInicial"]) == ""){
    $_REQUEST["fechaInicial"] = date("2021-02-01");
}

// Si la fecha final no fue declarada y no existe, se toma la fecha del d��a como fecha final
if(!isset($_REQUEST["fechaFinal"]) || eliminarInvalidos($_REQUEST["fechaFinal"]) == ""){
    $siguiente_anho = date("Y", strtotime("+1 year"));
    //$_REQUEST["fechaFinal"] = $siguiente_anho."-01-31";
    $_REQUEST["fechaFinal"] = date("Y-m-d");
}

// Si el a�0�9o fue declarado, se toma ese a�0�9o de referencia de lo contratio, se tomar�� el a�0�9o actual
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
//echo $iniQ.' - '.$finQ;
/*
*   GENERAR EXCEL
*/
if(isset($_REQUEST["excelXML"])){

    //  YA GENERACION 0 NO CUENTA
    $sqlFiltro .= " AND sat_reportes.generacionNumero != 0";
    $sqlFiltro .= " AND sat_reportes.generacionNumero != 77";
    $sqlFiltro .= " AND sat_reportes.generacionNumero != 8";

    if($_SESSION["perfil"] == 163){
        $_REQUEST["idUsuario"] = $_SESSION["id"];
    }
    //    
    $empresa_paisid_txt = "Satura Naciones";
    if(isset($_REQUEST["empresa_paisid"]) && soloNumeros($_REQUEST["empresa_paisid"]) != ""){
        $empresa_paisid = soloNumeros($_REQUEST["empresa_paisid"]);
        $sqlFiltro .= " AND usuario_empresa.empresa_paisid = '".$empresa_paisid."'";
        
        /*
        *   TRAEMOS LOS TIPOS DE CLIENTE/EMPRESA (15)
        */
        $sql = "SELECT * ";
        $sql.=" FROM categorias ";
        $sql.=" WHERE idSec = 37 ORDER BY descripcion asc";
        $PSN2->query($sql);
        $numero=$PSN2->num_rows();
        if($numero > 0)
        {
            while($PSN2->next_record())
            {
                $empresa_paisid_txt = "Satura ".$PSN2->f('descripcion');
            }
        }        
        
    }


    if(isset($_REQUEST["idUsuario"]) && soloNumeros($_REQUEST["idUsuario"]) != ""){
        $buscar_idUsuario = soloNumeros($_REQUEST["idUsuario"]);
        $sqlFiltro .= " AND sat_reportes.idUsuario = '".$buscar_idUsuario."'";
    }
    //
    if(isset($_REQUEST["idGrupoMadre"]) && soloNumeros($_REQUEST["idGrupoMadre"]) != ""){
        $buscar_idGrupoMadre = soloNumeros($_REQUEST["idGrupoMadre"]);
        $sqlFiltro .= " AND sat_reportes.idGrupoMadre = '".$buscar_idGrupoMadre."'";
    }

    //
    if(isset($_REQUEST["nombre"]) && eliminarInvalidos($_REQUEST["nombre"]) != ""){
        $buscar_nombre = eliminarInvalidos($_REQUEST["nombre"]);
        $sqlFiltro .= " AND sat_reportes.plantador LIKE '%".$buscar_nombre."%'";
    }

    //
    if(isset($_REQUEST["fechaInicial"]) && eliminarInvalidos($_REQUEST["fechaInicial"]) != ""){
        $fechaInicial = eliminarInvalidos($_REQUEST["fechaInicial"]);
        $sqlFiltro .= " AND sat_reportes.fechaReporte >= '".$fechaInicial."'";
    }

    //
    if(isset($_REQUEST["fechaFinal"]) && eliminarInvalidos($_REQUEST["fechaFinal"]) != ""){
        $fechaFinal = eliminarInvalidos($_REQUEST["fechaFinal"]);
        $sqlFiltro .= " AND sat_reportes.fechaReporte <= '".$fechaFinal."'";
    }
                    
    $sql = "SELECT
                sat_reportes.*,
                usuario.nombre as nombreUsuario,
                usuario.direccion as direccionUsuario,
                usuario.identificacion as identificacionUsuario,
                usuario_empresa.empresa_sitio,
                usuario_empresa.empresa_socio,
                usuario_empresa.empresa_rm,
                usuario_empresa.empresa_proceso,
                usuario_empresa.empresa_paisid 
                ";
    $sql.=" FROM sat_reportes ";
    $sql .= " LEFT JOIN usuario ON usuario.id = sat_reportes.idUsuario";
    $sql .= " LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = sat_reportes.idUsuario";
    //
    $sql.=" WHERE 1 ".$sqlFiltro." ORDER BY usuario_empresa.empresa_paisid ASC, usuario.nombre ASC";
    $reportCSV = $PSN1->query($sql);
    $numero=$PSN1->num_rows();
    //
    //
    $sql = "SELECT usuario.*, usuario_empresa.* FROM usuario LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = usuario.id WHERE usuario.id = '".$_SESSION["id"]."'";
    $PSN2->query($sql);
    if($PSN2->num_rows() > 0)
    {
        if($PSN2->next_record())
        {
            $empresa_pais = $PSN2->f('empresa_pais');
            $empresa_sitio_cor = $PSN2->f('empresa_sitio_cor');
            $empresa_socio = $PSN2->f('empresa_socio');   
            $empresa_rm = $PSN2->f('empresa_rm');
        }
    }
    $ubi_regist = $numero+10;
    $mergeacross = 30;
    $spreadsheet = new SpreadSheet();
    $spreadsheet->getProperties()->setCreator("Andres Torres")->setTitle("Informe de Satura");
    $spreadsheet->setActiveSheetIndex(0);
    /*$spreadsheet->getActiveSheet()->getStyle('C')
            ->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);*/
    $hojaActiva = $spreadsheet->getActiveSheet();
    $spreadsheet->getActiveSheet()->mergeCells('A2:C2');
    $hojaActiva->setCellValue('A2','INFORME DE COORDINADOR ');
    $hojaActiva->setCellValue('A3','INICIO:');
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Informe-satura-naciones.xlsx"');
    header('Cache-Control: max-age=0');
    $spreadsheet->setActiveSheetIndex(0);
    $hojaActiva = $spreadsheet->getActiveSheet();

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');     
}else{
    //
    $registros = 50;
    $pagina = soloNumeros($_GET["pagina"]);

    if (!$pagina) { 
        $inicio = 0; 
        $pagina = 1; 
    } 
    else
    { 
        $inicio = ($pagina - 1) * $registros; 
    }


    /*
    *	TRAEMOS EL CONTEO ED LOS REGISTROS POR USUARIO QUE ES EL AGRUPADOR.
    */
    $sql = "SELECT count(DISTINCT sat_reportes.idUsuario) as conteo ";
    $sql .= " FROM sat_reportes ";
    $sql .= " LEFT JOIN usuario ON usuario.id = sat_reportes.idUsuario";
    $sql .= " LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = sat_reportes.idUsuario";
    $sql .= " WHERE 1 ";
    //
    if($_SESSION["perfil"] == 163){
        $_REQUEST["idUsuario"] = $_SESSION["id"];
    }

    $sqlFiltro = "";
    $sqlFiltroBase = "";
    $sqlFiltroAsistencia = "";

    //  YA GENERACION 0 NO CUENTA
    $sqlFiltro .= " AND sat_reportes.generacionNumero != 0";
    $sqlFiltro .= " AND sat_reportes.generacionNumero != 77";
    

    if(!empty($_REQUEST["empresa_paisid"])){
        $empresa_paisid = soloNumeros($_REQUEST["empresa_paisid"]);
        $sqlFiltro .= " AND usuario_empresa.empresa_paisid = '".$empresa_paisid."'";
        $sqlFiltroBase .= " AND EXISTS (
            SELECT 1
            FROM usuario_empresa
            WHERE usuario_empresa.idUsuario = sat_reportes.idUsuario
              AND usuario_empresa.empresa_paisid = '".$empresa_paisid."'
        )";
        $sqlFiltroAsistencia .= " AND EXISTS (
            SELECT 1
            FROM usuario_empresa AS usuario_empresa_asistencia
            WHERE usuario_empresa_asistencia.idUsuario = sat_reportes_asistencia.idUsuario
              AND usuario_empresa_asistencia.empresa_paisid = '".$empresa_paisid."'
        )";
    }
    
    //
    if(isset($_REQUEST["idUsuario"]) && soloNumeros($_REQUEST["idUsuario"]) != ""){
        $buscar_idUsuario = soloNumeros($_REQUEST["idUsuario"]);
        $sqlFiltro .= " AND sat_reportes.idUsuario = '".$buscar_idUsuario."'";
        $sqlFiltroBase .= " AND sat_reportes.idUsuario = '".$buscar_idUsuario."'";
        $sqlFiltroAsistencia .= " AND sat_reportes_asistencia.idUsuario = '".$buscar_idUsuario."'";
    }
    //
    if(isset($_REQUEST["idGrupoMadre"]) && soloNumeros($_REQUEST["idGrupoMadre"]) != ""){
        $buscar_idGrupoMadre = soloNumeros($_REQUEST["idGrupoMadre"]);
        $sqlFiltro .= " AND sat_reportes.idGrupoMadre = '".$buscar_idGrupoMadre."'";
        $sqlFiltroBase .= " AND sat_reportes.idGrupoMadre = '".$buscar_idGrupoMadre."'";
        $sqlFiltroAsistencia .= " AND sat_reportes_asistencia.idGrupoMadre = '".$buscar_idGrupoMadre."'";
    }
    //
    if(isset($_REQUEST["nombre"]) && eliminarInvalidos($_REQUEST["nombre"]) != ""){
        $buscar_nombre = eliminarInvalidos($_REQUEST["nombre"]);
        $sqlFiltro .= " AND sat_reportes.plantador LIKE '%".$buscar_nombre."%'";
        $sqlFiltroBase .= " AND sat_reportes.plantador LIKE '%".$buscar_nombre."%'";
        $sqlFiltroAsistencia .= " AND sat_reportes_asistencia.plantador LIKE '%".$buscar_nombre."%'";
    }
    //
    if(isset($_REQUEST["fechaInicial"]) && eliminarInvalidos($_REQUEST["fechaInicial"]) != ""){
        $fechaInicial = eliminarInvalidos($_REQUEST["fechaInicial"]);
        $sqlFiltro .= " AND sat_reportes.fechaReporte >= '".$fechaInicial."'";
        $sqlFiltroBase .= " AND sat_reportes.fechaReporte >= '".$fechaInicial."'";
        $sqlFiltroAsistencia .= " AND sat_reportes_asistencia.fechaReporte >= '".$fechaInicial."'";
    }
    //
    if(isset($_REQUEST["fechaFinal"]) && eliminarInvalidos($_REQUEST["fechaFinal"]) != ""){
        $fechaFinal = eliminarInvalidos($_REQUEST["fechaFinal"]);
        $sqlFiltro .= " AND sat_reportes.fechaReporte <= '".$fechaFinal."'";
        $sqlFiltroBase .= " AND sat_reportes.fechaReporte <= '".$fechaFinal."'";
        $sqlFiltroAsistencia .= " AND sat_reportes_asistencia.fechaReporte <= '".$fechaFinal."'";
    }
    //    
    $sql .= $sqlFiltroBase." ORDER BY sat_reportes.id DESC";
    //

    $PSN1->query($sql);
    if($PSN1->num_rows() > 0)
    {
        if($PSN1->next_record())
        {
            $total_registros = $PSN1->f('conteo');
        }
    }
    $total_paginas = ceil($total_registros / $registros); 

    //GRupos nuevos es el conteo de grupos cuya generación sea mayor a 0.
    $sqlGenIncluidaReporte = "(sat_reportes.generacionNumero != 0 AND sat_reportes.generacionNumero != 77)";
    $sql = "SELECT
                sat_reportes.idUsuario,
                (
    SELECT COUNT(*)
    FROM sat_reportes sr_grupos
    WHERE sr_grupos.idUsuario = sat_reportes.idUsuario
    AND sr_grupos.id_grupo = 0
) AS gruposConteo,
                
                (SELECT SUM(sat_reportes_asistencia.asistencia_total)
                 FROM sat_reportes AS sat_reportes_asistencia
                 WHERE sat_reportes_asistencia.idUsuario = sat_reportes.idUsuario
                 ".$sqlFiltroAsistencia.") as asistencia_total,
                (SELECT SUM(sat_reportes_asistencia.asistencia_hom)
                 FROM sat_reportes AS sat_reportes_asistencia
                 WHERE sat_reportes_asistencia.idUsuario = sat_reportes.idUsuario
                 ".$sqlFiltroAsistencia.") as asistencia_hom,
                (SELECT SUM(sat_reportes_asistencia.asistencia_muj)
                 FROM sat_reportes AS sat_reportes_asistencia
                 WHERE sat_reportes_asistencia.idUsuario = sat_reportes.idUsuario
                 ".$sqlFiltroAsistencia.") as asistencia_muj,
                (SELECT SUM(sat_reportes_asistencia.asistencia_jov)
                 FROM sat_reportes AS sat_reportes_asistencia
                 WHERE sat_reportes_asistencia.idUsuario = sat_reportes.idUsuario
                 ".$sqlFiltroAsistencia.") as asistencia_jov,
                (SELECT SUM(sat_reportes_asistencia.asistencia_nin)
                 FROM sat_reportes AS sat_reportes_asistencia
                 WHERE sat_reportes_asistencia.idUsuario = sat_reportes.idUsuario
                 ".$sqlFiltroAsistencia.") as asistencia_nin,
                
                SUM(sat_reportes.bautizados) as bautizados,
SUM(sat_reportes.discipulado) as discipulado,
SUM(sat_reportes.desiciones) as desiciones,
SUM(sat_reportes.preparandose) as preparandose,
                usuario.nombre as nombreUsuario,
                usuario_empresa.empresa_sitio,
                usuario_empresa.empresa_rm,
                usuario_empresa.empresa_proceso                
                ";
    $sql.=" FROM sat_reportes ";
    $sql .= " LEFT JOIN usuario ON usuario.id = sat_reportes.idUsuario";
    $sql .= " LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = sat_reportes.idUsuario";
    //
    $sql.=" WHERE 1 ".$sqlFiltroBase." GROUP BY sat_reportes.idUsuario ORDER BY usuario.nombre ASC";
    $sql.= " LIMIT ".$inicio.", ".$registros;
    //
    $PSN1->query($sql);
    $numero=$PSN1->num_rows();
//    print_r($sql);
?>
<div class="container">
    <form name="form" id="form" method="get" class="form-horizontal">
        <input type="hidden" name="doc" value="reportar_buscar_con" />
        <div>
            <h2 class="alert alert-info text-center">INFORMES DE COORDINADOR</h2>
        </div>
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3>FILTRO DE BUSQUEDA</h3>
                <h5>de REPORTES</h5>
            </div>
            <div class="hr"><hr></div>
        </div>
        <div class="form-group">
            <div class="col-sm-3">
                <strong>Facilitador Satura:</strong>
                <?php
                ?><select name="idUsuario" onchange="this.form.submit()" class="form-control">
                <?php
                if($_SESSION["perfil"] != 163){?>
                    <option value="">Ver todos</option><?php
                }
            $sql = "SELECT * ";
            $sql.=" FROM usuario ";
            $sql.=" WHERE tipo IN (162, 163) ";
            if($_SESSION["perfil"] == 163){
                $sql.=" AND id = '".$_SESSION["id"]."'";
            }
            $sql.=" ORDER BY nombre asc";

            $PSN2->query($sql);
            $numero=$PSN2->num_rows();
            if($numero > 0)
            {
                while($PSN2->next_record())
                {
                    ?><option value="<?=$PSN2->f('id'); ?>" <?php
                    if($buscar_idUsuario == $PSN2->f('id'))
                    {
                        ?>selected="selected"<?php
                    }
                    ?>><?=$PSN2->f('nombre'); ?></option><?php
                }
            }
            ?></select>
            </div>
            
            <div class="col-sm-2">
                <strong>Nombre del pais:</strong>
            <select name="empresa_paisid" onchange="this.form.submit()" class="form-control">                    
            <option value="">Satura naciones</option>
            <?php
            /*
            *	TRAEMOS LOS TIPOS DE CLIENTE/EMPRESA (15)
            */
            $sql = "SELECT * ";
            $sql.=" FROM categorias ";
            $sql.=" WHERE idSec = 37 ORDER BY descripcion asc";


            $PSN2->query($sql);
            $numero=$PSN2->num_rows();
            if($numero > 0)
                {
                while($PSN2->next_record())
                {
                    ?><option value="<?=$PSN2->f('id'); ?>" <?php
                    if($empresa_paisid == $PSN2->f('id'))
                    {
                        ?>selected="selected"<?php
                    }
                    ?>>Satura <?=$PSN2->f('descripcion'); ?></option><?php
                }
            }
            ?>
            </select></div>
            <div class="col-sm-2">
                <strong>Fecha Inicial:</strong>
                <input type="date" onchange="this.form.submit()" name="fechaInicial" id="fechaInicial" value="<?=$fechaInicial; ?>" class="form-control" />
            </div>
            <div class="col-sm-2">
                <strong>Fecha Final:</strong>
                <input type="date" onchange="this.form.submit()" name="fechaFinal" id="fechaFinal" value="<?=$fechaFinal; ?>" class="form-control" />
            </div>
            <div class="col-sm-1">
                <strong>Periodo:</strong>
                <select name="rep_qua" onchange="this.form.submit()" class="form-control">
                    <option value="">Sin especificar</option>
                    <option value="1" <?php echo($_REQUEST['rep_qua']==1)?'selected':''; ?>>Q1 (Ene - Mar)</option>
                    <option value="4" <?php echo($_REQUEST['rep_qua']==4)?'selected':''; ?>>Q2 (Abr - Jun)</option>
                    <option value="7" <?php echo($_REQUEST['rep_qua']==7)?'selected':''; ?>>Q3 (Jul - Sep)</option>
                    <option value="10" <?php echo($_REQUEST['rep_qua']==10)?'selected':''; ?>>Q4 (Oct - Dic)</option>
                </select>
            </div>
            <div class="col-sm-1">
                <strong>Año:</strong>
                <select name="rep_ani" onchange="this.form.submit()" class="form-control">
                    <option value="">Sin especificar</option>
                    <?php for ($i=2021; $i <= date('Y'); $i++) { 
                        echo '<option value="'.$i.'"';
                        echo ($i == $_REQUEST['rep_ani'])?'selected':'';
                        echo ' >'.$i.'</option>';
                    } ?>
                </select>
            </div>
            <div class="col-sm-1">
                <br>
                <input type="submit" value="Buscar" class="btn btn-success" />
            </div>
        </div>
    </form>
</div>

    <style>
    .table tbody tr:hover td, .table tbody tr:hover th {
        background-color: #E0EEEE;
        cursor:pointer;
        color:#000;
    }

    .table thead tr{
        background-color: #C7C7C7;
    }

    .table thead th{
        vertical-align: middle;text-align: center;
    }

    .table a{
        color:#000;
    }
    

    </style>

    <div class="container">
    <div class="cont-tit">
        <div class="hr"><hr></div>

        <div class="tit-cen">
            <h3>RESULTADOS DE BUSQUEDA</h3>
            <h5><?php echo $total_registros; ?> Registros encontrados</h5>

            <div style="margin-top:10px;">
                <button type="button" class="btn btn-info" data-toggle="collapse" data-target="#infoReporte">
                    <span class="glyphicon glyphicon-info-sign"></span>
                    Más información
                    
                </button>
            </div>

            <div id="infoReporte" class="collapse" style="margin-top:15px;">

    <div class="alert alert-info" style="text-align:left; padding:20px;">

        <h3 style="margin-top:0;">
            📊 Guía de Interpretación del Reporte
        </h3>

        <p style="font-size:15px; margin-bottom:20px;">
            Esta tabla presenta un resumen consolidado de la información registrada por cada facilitador.
        </p>
        <style>
#infoReporte,
#infoReporte p,
#infoReporte strong{
    font-size:15px;
}
</style>

        <div style="font-size:15px; line-height:1.8;">

            <p>
                👥 <strong>Grupos:</strong>
                Total histórico de grupos creados. Este valor no se ve afectado por los filtros de fecha.
            </p>

            <p>
                📈 <strong>Asistencia:</strong>
                Suma total de asistentes registrados en los grupos y reportes consultados.
            </p>

            <p>
                🙌 <strong>Bautizados:</strong>
                Suma total de bautizados registrados en los reportes de bautismo.
            </p>

            <p>
                ❤️ <strong>Decisiones:</strong>
                Suma total de decisiones de fe registradas en reportes de evangelismo y coaching.
            </p>

            <p>
                📚 <strong>Preparándose:</strong>
                Suma total de personas que se encuentran en proceso de preparación en reportes de coaching.
            </p>

            <p>
                🎯 <strong>En Discipulado:</strong>
               Suma total de personas que se encuentran en proceso de discipulado en reportes de coaching.
            </p>

            <p>
                👨‍🏫 <strong>Líderes Capacitándose:</strong>
                Suma de la asistencia total registrada en los reportes y grupos de Generación 0
            </p>

        </div>

    </div>

</div>
        </div>

        <div class="hr"><hr></div>
    </div>

    <table border="0" cellspacing="0" cellpadding="2"  align="center" class="table table-bordered" style="font-size:12px">
        <thead>
            <tr> 
                <th>Id</th>
                <th>Facilitador</th>
                <th>RM</th>
                <th>Proceso</th>
                <th>Sitio</th>
                <th>Grupos</th>
                <th>Asistencia</th>
                <th>Bautizados</th>
                <th>Decisiones</th>
                <th>Preparandose</th>
                <th>En Discipulado</th>
                <th>Lideres capacitandose</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if($total_registros > 0)
            {
                $contador = 1;
                while($PSN1->next_record())
                {
                    //Solo si no se ha modificado ya el formulario.
                    $idUsuario = $PSN1->f('idUsuario');
                    $plantador = $PSN1->f("plantador");
                    $fechaReporte = $PSN1->f("fechaReporte");
                    $fechaInicio = $PSN1->f("fechaInicio");        
                    $sitioReunion = $PSN1->f("sitioReunion");
                    $grupoMadre_txt = $PSN1->f("grupoMadre_txt");
                        
                    $idGrupoMadre = $PSN1->f("idGrupoMadre");
                    $generacionNumero = intval($PSN1->f("generacionNumero"));

                    $gruposConteo = $PSN1->f("gruposConteo");
                    
                    $sql = "SELECT COUNT(DISTINCT sat_reportes.idGrupoMadre) as conteo ";
                    $sql.=" FROM sat_reportes ";
                    //
                    //
                    $sqlFiltro = "";
                    if(isset($_REQUEST["fechaInicial"]) && eliminarInvalidos($_REQUEST["fechaInicial"]) != ""){
                        $sqlFiltro .= " AND sat_reportes.fechaInicio >= '".$fechaInicial."'";
                    }
                    //
                    if(isset($_REQUEST["fechaFinal"]) && eliminarInvalidos($_REQUEST["fechaFinal"]) != ""){
                        $sqlFiltro .= " AND sat_reportes.fechaInicio <= '".$fechaFinal."'";
                    }

                    $sql.=" WHERE idUsuario = '".$idUsuario."' ".$sqlFiltro;
                    $PSN2->query($sql);
                    if($PSN2->num_rows() > 0)
                    {
                        if($PSN2->next_record())
                        {
                            $gruposNuevos = $PSN2->f('conteo');
                        }
                    }
                    
                    
                    
                    $nombreUsuario = $PSN1->f("nombreUsuario");
                        $empresa_sitio = $PSN1->f("empresa_sitio");
                        $empresa_rm = $PSN1->f("empresa_rm");
                        $empresa_proceso = $PSN1->f("empresa_proceso");

                    $asistencia_hom = $PSN1->f("asistencia_hom");
                    $asistencia_muj = $PSN1->f("asistencia_muj");
                    $asistencia_jov = $PSN1->f("asistencia_jov");
                    $asistencia_nin = $PSN1->f("asistencia_nin");

                    $bautizados = $PSN1->f("bautizados");
                    $bautizadosPeriodo = $PSN1->f("bautizadosPeriodo");

                    //Calculados:
                    $asistencia_total  = $PSN1->f("asistencia_total");
                    $discipulado  = $PSN1->f("discipulado");
                    $desiciones  = $PSN1->f("desiciones");
                    $preparandose  = $PSN1->f("preparandose");
                    $iglesias_reconocidas = $PSN1->f("iglesias_reconocidas");  
                    
                    $lideresCapacitandose = 0;

                    $sqlFiltroCapacitacion = "";
                    if(isset($_REQUEST["fechaInicial"]) && eliminarInvalidos($_REQUEST["fechaInicial"]) != ""){
                        $sqlFiltroCapacitacion .= " AND sat_reportes.fechaReporte >= '".$fechaInicial."'";
                    }
                    if(isset($_REQUEST["fechaFinal"]) && eliminarInvalidos($_REQUEST["fechaFinal"]) != ""){
                        $sqlFiltroCapacitacion .= " AND sat_reportes.fechaReporte <= '".$fechaFinal."'";
                    }
                    if(!empty($_REQUEST["empresa_paisid"])) {
                        $empresa_paisid_cap = soloNumeros($_REQUEST["empresa_paisid"]);
                        $sqlFiltroCapacitacion .= " AND EXISTS (
                            SELECT 1
                            FROM usuario_empresa
                            WHERE usuario_empresa.idUsuario = sat_reportes.idUsuario
                              AND usuario_empresa.empresa_paisid = '".$empresa_paisid_cap."'
                        )";
                    }

                    $sqlGenEsCeroCapacitacion = "(sat_reportes.generacionNumero = 0 OR sat_reportes.generacionNumero IS NULL OR sat_reportes.generacionNumero = '')";
                    $sql = "SELECT COUNT(sat_reportes.id) as conteo, SUM(sat_reportes.asistencia_total) as asistencia_total ";
                    $sql.=" FROM sat_reportes ";
                    $sql.=" WHERE ".$sqlGenEsCeroCapacitacion." ".$sqlFiltroCapacitacion." AND sat_reportes.idUsuario = '".$idUsuario."'";
                    $PSN2->query($sql);
                    if($PSN2->num_rows() > 0)
                    {
                        if($PSN2->next_record())
                        {
                            $lideresCapacitandose = (int)$PSN2->f('asistencia_total');
                            if($lideresCapacitandose < 0){ $lideresCapacitandose = 0; }
                        }
                    }
                    //
                    ?><tr>
                        <td><?=$contador; ?></td>
                        <td><?=$nombreUsuario; ?></td>
                        <td><?=$empresa_rm; ?></td>
                        <td><?=$empresa_proceso; ?></td>
                        <td><?=$empresa_sitio; ?></td>
                        <td align="center"><?=$gruposConteo; ?></td>
                        <td align="center"><?=$asistencia_total; ?></td>
                        <td align="center"><?=$bautizados; ?></td>
                        <td align="center"><?=$desiciones; ?></td>
                        <td align="center"><?=$preparandose; ?></td>
                        <td align="center"><?=$discipulado; ?></td>
                        <td align="center"><?=$lideresCapacitandose; ?></td>
                    </tr>
                    <?php
                    $contador++;
                }
            }
            ?>
        </tbody>
        </table>
    </div>


    <center>
    <div class="container">
        <ul class="pagination">
            <?php
            //
            $paginaActualTxT = "&pagina=".$pagina;
            $_SERVER['REQUEST_URI'] = str_replace($paginaActualTxT,"", $_SERVER['REQUEST_URI']);
            //
            if(($pagina - 1) > 0)
            {
                echo "<li><a href='".$_SERVER['REQUEST_URI']."&pagina=".($pagina-1)."'>&laquo;</a></li>"; 
            }

            for ($i=1; $i<=$total_paginas; $i++)
            { 
                if ($pagina == $i)
                {
                    echo "<li class='active'><a href='".$_SERVER['REQUEST_URI']."&pagina=$i'>$i</a>"; 
                }
                else 
                { 
                    echo "<li><a href='".$_SERVER['REQUEST_URI']."&pagina=$i'>$i</a></li>";
                } 
            }

            if(($pagina + 1)<=$total_paginas)
            { 
                echo "<li><a href='".$_SERVER['REQUEST_URI']."&pagina=".($pagina+1)."'>&raquo;</a></li>"; 
            }
            ?>
        </ul>
    </div>
    </center>

    <br />
   <center>
    <a href="generaExcel.php?idUsuario=<?php echo $_REQUEST["idUsuario"]; ?>&empresa_paisid=<?php echo $_REQUEST["empresa_paisid"]; ?>&rep_qua=<?php echo $_REQUEST['rep_qua']; ?>&rep_ani=<?=$_REQUEST['rep_ani']  ?>&fechaInicial=<?=$_REQUEST['fechaInicial']  ?>&fechaFinal=<?=$_REQUEST['fechaFinal']  ?>" target="_blank" class="btn btn-info"><span class="glyphicon glyphicon-cloud-download"></span> DESCARGAR PARA EXCEL</a>
    </center>

    <script language="javascript">
    function init(){
    }
    window.onload = function(){
        init();
    }
    </script>

<style>
#infoReporte,
#infoReporte p,
#infoReporte strong{
    font-size:15px;
}
</style>
    <script>
    jQuery(document).ready(function($) {
        $(".clickable-row").click(function() {
            window.location = $(this).data("href");
        });
    });
    </script><?php
}
?>
