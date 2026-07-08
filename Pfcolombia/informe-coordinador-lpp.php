<?php

/*
*	CONSOLIDADO CON SUMATORIA DE CAMPOS
*/
// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;



if(!isset($_REQUEST["fechaInicial"]) || eliminarInvalidos($_REQUEST["fechaInicial"]) == ""){
    //$three_months_ago = date("Y-m-d", strtotime("-3 months"));
        $three_months_ago = '2000-01-01';
    $_REQUEST["fechaInicial"] = $three_months_ago;
}
if(!isset($_REQUEST["fechaFinal"]) || eliminarInvalidos($_REQUEST["fechaFinal"]) == ""){
    $siguiente_anho = date("Y", strtotime("+1 year"));
    //$_REQUEST["fechaFinal"] = $siguiente_anho."-01-31";
    $_REQUEST["fechaFinal"] = date("Y-m-d");
}

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
    $empresa_paisid_txt = "Confraternidad carcelaria";
    if(isset($_REQUEST["empresa_paisid"]) && soloNumeros($_REQUEST["empresa_paisid"]) != ""){
        $empresa_paisid = soloNumeros($_REQUEST["empresa_paisid"]);
        $sqlFiltro .= " AND usuario_empresa.empresa_paisid = '".$empresa_paisid."'";
        
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
    $PSN1->query($sql);
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

    $mergeacross = 30;
    ?>
    <Worksheet ss:Name="Coor">
    <Table ss:ExpandedColumnCount="<?=$mergeacross+7; ?>">
        <Row>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            <Cell ss:MergeAcross="<?=$mergeacross; ?>" ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
        </Row>
        <Row>
            <Cell ss:StyleID="verdosoBold" ss:MergeAcross="2"><Data ss:Type="String">INFORME DE COORDINADOR</Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"><?=$_REQUEST['rep_ani']; ?></Data></Cell>
            <Cell ss:MergeAcross="<?=$mergeacross; ?>" ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
        </Row>
            
        <Row>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String">Inicio: </Data></Cell>
            <Cell ss:StyleID="s68" ><Data ss:Type="String"><?=date("d-m-Y", strtotime($fechaInicial)); ?></Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String">Fin: </Data></Cell>
            <Cell ss:StyleID="s68" ><Data ss:Type="String"><?=date("d-m-Y", strtotime($fechaFinal)); ?></Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"></Data></Cell>
            <Cell ss:MergeAcross="<?=$mergeacross; ?>" ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
        </Row>
        
        <Row>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String">DESDE</Data></Cell>
            <Cell ss:StyleID="s68"><Data ss:Type="String"><?=date("d-m-Y", strtotime($iniQ));?></Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String">HASTA</Data></Cell>
            <Cell ss:StyleID="s68"><Data ss:Type="String"><?=date("d-m-Y", strtotime($finQ));?></Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"></Data></Cell>
            <Cell ss:MergeAcross="<?=$mergeacross; ?>" ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
        </Row>
        
        <Row>
            <Cell ss:StyleID="verdoso" ss:MergeAcross="2"><Data ss:Type="String" >NOMBRE DEL SOCIO </Data></Cell>
            <Cell><Data ss:Type="String">Juan Guillermo Cardona</Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            <Cell ss:MergeAcross="<?=$mergeacross; ?>" ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
        </Row>
                    
        <Row>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String">USUARIO</Data></Cell>
            <Cell ss:MergeAcross="2"><Data ss:Type="String"><?=$_SESSION["nombre"]; ?></Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            <Cell ss:MergeAcross="<?=$mergeacross; ?>" ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
        </Row>
        <Row>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String">ENTIDAD</Data></Cell>
            <Cell ss:MergeAcross="2"><Data ss:Type="String"><?=$empresa_paisid_txt; ?> </Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"></Data></Cell>
            <Cell ss:MergeAcross="<?=$mergeacross; ?>" ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
        </Row>         
        <Row>
            <Cell ss:MergeAcross="2" ss:StyleID="verdoso"><Data ss:Type="String"><?="Grupos"; ?></Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"><?="Nuevos grupos"; ?></Data></Cell>
            <Cell ss:MergeAcross="<?=$mergeacross; ?>" ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
        </Row>
                            
        <Row>
            <Cell ss:MergeAcross="5" ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Asistencia del grupo"; ?></Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Total de creyentes en el grupo"; ?></Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Nuevos creyentes en el grupo en este periodo"; ?></Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Total de bautizados en el grupo"; ?></Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Nuevos bautizados en el grupo en este periodo"; ?></Data></Cell>
            
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Orar"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Companerismo"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Adorar"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Aplicar la biblia"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Evangelizar"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Cena del Señor"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Dar"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Bautizar"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Entrenar nuevos lideres"; ?></Data></Cell>
            
            
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="Number">1</Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="Number">2</Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="Number">3</Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="Number">4</Data></Cell>

            
            <Cell ss:MergeAcross="11" ss:StyleID="verdoso"><Data ss:Type="String"> </Data></Cell>
            
            
        <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"> </Data></Cell>
        </Row>
            
                            
        <Row>
            <Cell ss:StyleID="verdoso"><Data ss:Type="String"><?="Nombre del lider"; ?></Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Nombre del grupo / iglesia"; ?></Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Fecha de Inicio"; ?></Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Generacion"; ?></Data></Cell>

                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Ubicacion"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Grupo Madre / Iglesia"; ?></Data></Cell>
            
            <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
            <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
            <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
            <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
            <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
            
                <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
                <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
                <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
                <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
                <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
                <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
                <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
                <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
                <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>     
            
            <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
            <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
            <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>
            <Cell ss:StyleID="verdosoBold" ss:Formula='=SUM(R[+1]C:R[+<?=intval($numero); ?>]C)'><Data ss:Type="Number">0</Data></Cell>       
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"> </Data></Cell>
            
            
            
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Ubicacion del entrenador"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Entrenador"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Carne de identidad"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Ch"; ?></Data></Cell>
            
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Suma"; ?></Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Promedio"; ?></Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Desde"; ?></Data></Cell>
            <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Hasta"; ?></Data></Cell>

                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Reunido"; ?></Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"><?="Nombre del socio"; ?></Data></Cell>
            
            
            
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"> </Data></Cell>
                <Cell ss:StyleID="verdosoBold"><Data ss:Type="String"> </Data></Cell>
        </Row>
            

        <?php
        if($numero > 0)
        {
            $contador = 1;
            while($PSN1->next_record())
            {
                
                ?><Row>
                    <Cell><Data ss:Type="String"><?=$PSN1->f('plantador'); ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?=$PSN1->f('nombreGrupo_txt'); ?></Data></Cell>
                    <Cell ss:StyleID="s68" ><Data ss:Type="String"><?=date("d-m-Y", strtotime($PSN1->f('fechaInicio'))); ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?=$PSN1->f('generacionNumero'); ?></Data></Cell>

                    <Cell><Data ss:Type="String"><?=$PSN1->f('barrio')." ".$PSN1->f('direccion')." ".$PSN1->f('ciudad'); ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?=$PSN1->f('grupoMadre_txt'); ?></Data></Cell>

                <Cell><Data ss:Type="Number"><?=intval($PSN1->f('asistencia_total')); ?></Data></Cell>
                <Cell><Data ss:Type="Number"><?=intval($PSN1->f('discipulado')); ?></Data></Cell>
                <Cell><Data ss:Type="Number"><?php 
                    $fecRep = date("Y-m-d", strtotime($PSN1->f('fechaInicio')));
                    //echo $iniQ."- ".$fecRep." - ".$finQ;
                    if ($iniQ<=$fecRep && $fecRep<=$finQ) {
                        echo intval($PSN1->f('desiciones'));
                    }else{
                        echo "0";
                    }
                  ?></Data></Cell>
                <Cell><Data ss:Type="Number"><?=intval($PSN1->f('bautizados')); ?></Data></Cell>
                <Cell><Data ss:Type="Number"><?php 
                    if ($iniQ<=$fecRep && $fecRep<=$finQ) {
                        echo intval($PSN1->f('bautizadosPeriodo'));
                    }else{
                        echo "0";
                    }
                  ?></Data></Cell> <?php // Nuevos bautizados. ?>

                        <Cell><Data ss:Type="Number"><?=intval($PSN1->f('mapeo_oracion')); ?></Data></Cell>
                        <Cell><Data ss:Type="Number"><?=intval($PSN1->f('mapeo_companerismo')); ?></Data></Cell>
                        <Cell><Data ss:Type="Number"><?=intval($PSN1->f('mapeo_adoracion')); ?></Data></Cell>
                        <Cell><Data ss:Type="Number"><?=intval($PSN1->f('mapeo_biblia')); ?></Data></Cell>
                        <Cell><Data ss:Type="Number"><?=intval($PSN1->f('mapeo_evangelizar')); ?></Data></Cell>
                        <Cell><Data ss:Type="Number"><?=intval($PSN1->f('mapeo_cena')); ?></Data></Cell>
                        <Cell><Data ss:Type="Number"><?=intval($PSN1->f('mapeo_dar')); ?></Data></Cell>
                        <Cell><Data ss:Type="Number"><?=intval($PSN1->f('mapeo_bautizar')); ?></Data></Cell>
                        <Cell><Data ss:Type="Number"><?=intval($PSN1->f('mapeo_trabajadores')); ?></Data></Cell>
        
                <Cell><Data ss:Type="Number">0</Data></Cell>
                <Cell><Data ss:Type="Number">0</Data></Cell>
                <Cell><Data ss:Type="Number">0</Data></Cell>
                <Cell><Data ss:Type="Number">0</Data></Cell>
                <Cell><Data ss:Type="String"> </Data></Cell>
                        
        
                        <Cell><Data ss:Type="String"><?=$PSN1->f('direccionUsuario'); ?></Data></Cell>
                        <Cell><Data ss:Type="String"><?=$PSN1->f('nombreUsuario'); ?></Data></Cell>
                        <Cell><Data ss:Type="String"><?=$PSN1->f('identificacionUsuario'); ?></Data></Cell>
                        <Cell><Data ss:Type="String"><?=$PSN1->f('mapeo_comprometido'); ?></Data></Cell>

                    <Cell><Data ss:Type="Number"><?php
                    $numero = $PSN1->f('mapeo_oracion')+$PSN1->f('mapeo_companerismo')+$PSN1->f('mapeo_adoracion')+$PSN1->f('mapeo_biblia')+$PSN1->f('mapeo_evangelizar')+$PSN1->f('mapeo_cena')+$PSN1->f('mapeo_dar')+$PSN1->f('mapeo_bautizar')+$PSN1->f('mapeo_trabajadores');
                    echo intval($numero);
                    ?></Data></Cell>
                    <Cell><Data ss:Type="Number"><?php
                    if($numero > 0){
                       $promedio = $numero/9;
                       $promedio = floatval($promedio);
                       echo round($promedio, 2);
                    }else{
                        ?>0<?php
                    }
                    ?></Data></Cell>
                    <Cell ss:StyleID="s68"><Data ss:Type="String"><?=date("d-m-Y", strtotime($iniQ)); ?></Data></Cell>
                    <Cell ss:StyleID="s68"><Data ss:Type="String"><?=date("d-m-Y", strtotime($finQ)); ?></Data></Cell>

                        <Cell ss:StyleID="s68"><Data ss:Type="String"><?=date("d-m-Y", strtotime($PSN1->f('mapeo_fecha'))); ?></Data></Cell>
                        <Cell><Data ss:Type="String"><?=$PSN1->f('empresa_socio'); ?></Data></Cell>
    
        
        
                <Cell><Data ss:Type="String"> </Data></Cell>
                <Cell><Data ss:Type="String"> </Data></Cell>
                </Row>
                <?php
                $contador++;
            }
        }
        ?>
        </Table>
    </Worksheet>
    <?php       
}
else{
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
    $sql .= " WHERE 1 AND sat_reportes.rep_tip = 307 ";
    //
    if($_SESSION["perfil"] == 163){
        $_REQUEST["idUsuario"] = $_SESSION["id"];
    }

    //  YA GENERACION 0 NO CUENTA
    $sqlFiltro .= " AND sat_reportes.generacionNumero != 0";
    $sqlFiltro .= " AND sat_reportes.generacionNumero != 77";
    

    if(!empty($_REQUEST["empresa_paisid"])){
        $empresa_paisid = soloNumeros($_REQUEST["empresa_paisid"]);
        $sqlFiltro .= " AND usuario_empresa.empresa_paisid = '".$empresa_paisid."'";
    }
    
    //
    if(isset($_REQUEST["idUsuario"]) && soloNumeros($_REQUEST["idUsuario"]) != ""){
        $buscar_idUsuario = soloNumeros($_REQUEST["idUsuario"]);
        $sqlFiltro .= " AND sat_reportes.idUsuario = '".$buscar_idUsuario."'";
    }
    //
    if(isset($_REQUEST["idGrupoMadre"]) && soloNumeros($_REQUEST["idGrupoMadre"]) != ""){
        $buscar_idGrupoMadre = soloNumeros($_REQUEST["idGrupoMadre"]);
        $sqlFiltro .= " AND sat_reportes.idGrupoMadre = '".$buscar_idGrupoMadre."'";
    }
    if(isset($_REQUEST["empresa_pd"]) && soloNumeros($_REQUEST["empresa_pd"]) != ""){
        $buscar_regional = soloNumeros($_REQUEST["empresa_pd"]);
        $sqlFiltro .= " AND UE.empresa_pd = '".$buscar_regional."'";
    }else if ($_SESSION["empresa_pd"]!="" && $_SESSION["empresa_pd"]!=0 && $_SESSION["empresa_sitio_cor"]=="") {
        $sqlFiltro .= " AND UE.empresa_pd = '".$_SESSION["empresa_pd"]."'";
        $_REQUEST["empresa_pd"] = $_SESSION["empresa_pd"];
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
    //    
    $sql .= $sqlFiltro." ORDER BY sat_reportes.id DESC";
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
    $sql = "SELECT
                sat_reportes.idUsuario,
                COUNT(sat_reportes.generacionNumero) AS gruposConteo,
                
                SUM(sat_reportes.asistencia_total) as asistencia_total,
                SUM(sat_reportes.asistencia_hom) as asistencia_hom,
                SUM(sat_reportes.asistencia_muj) as asistencia_muj,
                SUM(sat_reportes.asistencia_jov) as asistencia_jov,
                SUM(sat_reportes.asistencia_nin) as asistencia_nin,
                
                SUM(sat_reportes.bautizados) as bautizados,
                SUM(sat_reportes.bautizadosPeriodo) as bautizadosPeriodo,                
                SUM(sat_reportes.discipulado) as discipulado,
                SUM(sat_reportes.desiciones) as desiciones,
                SUM(sat_reportes.preparandose) as preparandose,
                SUM(sat_reportes.iglesias_reconocidas) as iglesias_reconocidas,
                SUM(sat_reportes.bautizados) as bautizados,
                usuario.nombre as nombreUsuario,
                usuario_empresa.empresa_sitio,
                usuario_empresa.empresa_rm,
                usuario_empresa.empresa_proceso                
                ";
    $sql.=" FROM sat_reportes ";
    $sql .= " LEFT JOIN usuario ON usuario.id = sat_reportes.idUsuario";
    $sql .= " LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = sat_reportes.idUsuario";
    //
    $sql.=" WHERE 1 ".$sqlFiltro." AND sat_reportes.rep_tip = 307 GROUP BY sat_reportes.idUsuario ORDER BY usuario.nombre ASC";
    $sql.= " LIMIT ".$inicio.", ".$registros;
    //
    $PSN1->query($sql);
    $numero=$PSN1->num_rows();
?>
<div class="container">
    <form name="form" id="form" method="get" class="form-horizontal">
        <input type="hidden" name="doc" value="informe-coordinador-lpp" />
        <div>
            <h3 class="alert alert-info text-center">REPORTE DE LA PEREGRINACIÓN DEL PRISIONERO (LPP)</h3>
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
            <div class="col-sm-2">
                <strong>Zona:</strong>
                <select name="empresa_sitio_cor" id="zona" class="form-control" onchange="this.form.submit()">
                    <option value="" <?php if($empresa_pd == ""){?>
                                selected="selected" <?php
                            } ?>>Todas la zonas</option>
                    <?php
                    /*
                    *   TRAEMOS LOS TIPOS DE CLIENTE/EMPRESA (15)
                    */
                    $sql = "SELECT * ";
                    $sql.=" FROM categorias ";
                    $sql.=" WHERE idSec = 85 ORDER BY descripcion asc";


                    $PSN1->query($sql);
                    $numero=$PSN1->num_rows();
                    if($numero > 0){
                        while($PSN1->next_record()){
                            ?><option value="<?=$PSN1->f('id'); ?>" <?php
                            if($buscar_zona == $PSN1->f('id')){?>
                                selected="selected" <?php
                            }
                            ?>><?=$PSN1->f('descripcion'); ?></option><?php
                        }
                    }?>
                </select>
            </div>
            <div class="col-sm-2">
                <strong>Regional:</strong>
                <select  name="empresa_pd" id="regional" class="form-control" onchange="this.form.submit()">
                    <?php echo($zona == "")?'<option value="" selected >Todas la regionales</option>':"";
                    $sql = "SELECT C.id, C.descripcion AS regional, CA.descripcion AS zona FROM categorias AS C";
                    $sql.=" LEFT JOIN categorias AS CA ON CA.id = C.idSec
                    WHERE CA.idSec = 85 ";
                    if (!empty($buscar_zona)) {
                        $sql.=" AND CA.id = ".$buscar_zona;
                    }
                    if ($_SESSION["empresa_pd"]!="" && $_SESSION["empresa_pd"]!=0) {
                        $sql.=" AND CA.id = ".$_SESSION["empresa_pd"];
                    }
                    $PSN2->query($sql); 
                    //echo $sql;
                    $numero=$PSN2->num_rows();
                    if($numero > 0){
                        while($PSN2->next_record()){?>
                            <option value="<?=$PSN2->f('id'); ?>" <?php
                            if($buscar_regional == $PSN2->f('id')){
                                ?>selected="selected"<?php
                            }
                            ?> ><?=$PSN2->f('regional'); ?></option><?php
                        }
                    }
                    ?>
                </select>                    
            </div>
            <div class="col-sm-2">
                <strong>Miembro de la regional:</strong><?php
            ?><select name="idUsuario" onchange="this.form.submit()" class="form-control">
            <?php
            if($_SESSION["perfil"] != 163){
                ?><option value="">Ver todos</option><?php
            }

            /*
            *   TRAEMOS LOS USUARIOS
            */
            $sql = "SELECT * ";
            $sql.=" FROM usuario AS U
            LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id";
            $sql.=" WHERE U.tipo IN (162, 163) ";
            if($_SESSION["perfil"] == 163){
                $sql.=" AND U.id = '".$_SESSION["id"]."'";
            }
            if (!empty($buscar_regional)) {
                $sql.=" AND UE.empresa_pd = ".$buscar_regional." ";
            }
            $sql.=" ORDER BY U.nombre asc";

            $PSN2->query($sql);
            $numero_coo=$PSN2->num_rows();
            if($numero_coo > 0)
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
            <div class="col-sm-3">
                <strong>Prisión:</strong>
                <select name="sitioReunion" id="rep_carcel" class="form-control" onchange="this.form.submit()">
                    <?php
                    /*
                    *   TRAEMOS LOS TIPOS DE SERVICIOS QUE PRESTA (25)
                    */
                    if ($_SESSION['empresa_pd'] != "") {
                        echo '<option value="">Sin especificar</option>';
                        $sql = "SELECT * ";
                        $sql.=" FROM tbl_regional_ubicacion ";
                        if(!empty($buscar_regional)){
                            $sql.=" WHERE reub_reg_fk = ".$buscar_regional;
                        }
                        $sql.=" ORDER BY reub_reg_fk asc";

                        $PSN2->query($sql);
                        $numero_pri=$PSN2->num_rows();
                        if($numero_pri > 0){
                            while($PSN2->next_record()){
                                ?><option value="<?=$PSN2->f('reub_id'); ?>" <?php
                                if($buscar_prision == $PSN2->f('reub_id'))
                                {
                                    ?>selected="selected"<?php
                                }
                                ?>><?=$PSN2->f('reub_nom'); ?></option><?php
                            }
                        }
                    }else{
                        echo '<option value="">Sin regional asignada</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-sm-2">
                <strong>Período:</strong>
                <select name="rep_qua" onchange="this.form.submit()" class="form-control">
                    <option value="">Sin especificar</option>
                    <option value="1" <?php echo($_REQUEST['rep_qua']==1)?'selected':''; ?>>Q1 (Ene - Mar)</option>
                    <option value="4" <?php echo($_REQUEST['rep_qua']==4)?'selected':''; ?>>Q2 (Abr - Jun)</option>
                    <option value="7" <?php echo($_REQUEST['rep_qua']==7)?'selected':''; ?>>Q3 (Jul - Sep)</option>
                    <option value="10" <?php echo($_REQUEST['rep_qua']==10)?'selected':''; ?>>Q4 (Oct - Dic)</option>
                </select>
            </div>
            <div class="col-sm-2">
                <strong>Fecha Inicial:</strong>
                <input type="date" name="fechaInicial" id="fechaInicial" value="<?=$fechaInicial; ?>" class="form-control" />
            </div>
            <div class="col-sm-2">
                <strong>Fecha Final:</strong>
                <input type="date" name="fechaFinal" id="fechaFinal" value="<?=$fechaFinal; ?>" class="form-control" />
            </div>
            <div class="col-sm-2"><br>
                <input type="submit" value="Buscar" class="btn btn-success" style="float:center" />
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
                <th>Grupos Nuevos</th>
                <th>Bautizados</th>
                    <th>Asistencia</th>
                <th>Decisiones</th>
                <th>Preparandose</th>
                    <th>Bautizados este periodo</th>
                <th>En Discipulado</th>
                    <th>Lideres capacitandose</th>
                <th>Iglesias Reconocidas</th>
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
                    
                    $sql = "SELECT SUM(asistencia_total) as total, COUNT(sat_reportes.id) as conteo ";
                    $sql.=" FROM sat_reportes ";
                    $sql .= " LEFT JOIN usuario ON usuario.id = sat_reportes.idUsuario";
                    $sql .= " LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = sat_reportes.idUsuario";
                    //
                    $sql.=" WHERE 1 ".$sqlFiltro." AND  sat_reportes.idUsuario = '".$idUsuario."' AND sat_reportes.generacionNumero = 0 GROUP BY sat_reportes.idUsuario";
                    $PSN2->query($sql);
                    if($PSN2->num_rows() > 0)
                    {
                        if($PSN2->next_record())
                        {
                            $lideresCapacitandose = ($PSN2->f('total')-$PSN2->f('conteo'));
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
                        <td align="center"><?=$gruposNuevos; ?></td>
                        <td align="center"><?=$bautizados; ?></td>
                            <td align="center"><?=$asistencia_total; ?></td>
                        <td align="center"><?=$desiciones; ?></td>
                        <td align="center"><?=$preparandose; ?></td>
                            <td align="center"><?=$bautizadosPeriodo; ?></td>
                        <td align="center"><?=$discipulado; ?></td>
                            <td align="center"><?=$lideresCapacitandose; ?></td>
                        <td align="center"><?=$iglesias_reconocidas; ?></td>
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
<!---
    <br />
    <center>
    <a href="generaExcel.php?idUsuario=<?php echo $_REQUEST["idUsuario"]; ?>&empresa_paisid=<?php echo $_REQUEST["empresa_paisid"]; ?>&rep_qua=<?php echo $_REQUEST['rep_qua']; ?>&rep_ani=<?=$_REQUEST['rep_ani']  ?>&fechaInicial=<?=$_REQUEST['fechaInicial']  ?>&fechaFinal=<?=$_REQUEST['fechaFinal']  ?>" target="_blank" class="btn btn-info"><span class="glyphicon glyphicon-cloud-download"></span> DESCARGAR PARA EXCEL</a></center>-->


    <script language="javascript">
    function init(){
    }
    window.onload = function(){
        init();
    }
    </script>


    <script>
    jQuery(document).ready(function($) {
        $(".clickable-row").click(function() {
            window.location = $(this).data("href");
        });
    });
    </script><?php
}
?>