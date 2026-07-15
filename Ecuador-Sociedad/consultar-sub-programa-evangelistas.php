<?php
/*
*	$PSN = new DBbase_Sql;
*/
// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;
$PSN3 = new DBbase_Sql;


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

/*
*   GENERAR EXCEL
*/
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
    *	TRAEMOS LOS registros.
    */
    $sql = "SELECT count(DISTINCT sat_reportes.id) as conteo ";
    $sql .= " FROM sat_reportes ";
    $sql .= " LEFT JOIN usuario AS U ON U.id = sat_reportes.idUsuario
    LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
LEFT JOIN categorias AS C ON C.id = UE.empresa_pd 
LEFT JOIN categorias AS CA ON CA.id = C.idSec ";
    $sql .= " WHERE 1 ";
    //
    if($_SESSION["perfil"] == 163){
        $_REQUEST["idUsuario"] = $_SESSION["id"];
    }
    //
    if(isset($_REQUEST["idUsuario"]) && soloNumeros($_REQUEST["idUsuario"]) != ""){
        $buscar_idUsuario = soloNumeros($_REQUEST["idUsuario"]);
        $sqlFiltro .= " AND sat_reportes.idUsuario = '".$buscar_idUsuario."'";
    }
    if ($_SESSION["id_zona"]!="" && $_SESSION["id_zona"]!=0) {
        $sqlFiltro .= " AND C.idSec = '".$_SESSION["id_zona"]."'";
        $_REQUEST["empresa_sitio_cor"] = $_SESSION["id_zona"];
        $buscar_zona = $_SESSION["id_zona"];
    }
    if(isset($_REQUEST["empresa_sitio_cor"]) && soloNumeros($_REQUEST["empresa_sitio_cor"]) != ""){
        $buscar_zona = soloNumeros($_REQUEST["empresa_sitio_cor"]);
        $sqlFiltro .= " AND C.idSec = '".$buscar_zona."'";
    }
    
    if(isset($_REQUEST["empresa_pd"]) && soloNumeros($_REQUEST["empresa_pd"]) != ""){
        $buscar_regional = soloNumeros($_REQUEST["empresa_pd"]);
        $sqlFiltro .= " AND UE.empresa_pd = '".$buscar_regional."'";
    }else if ($_SESSION["empresa_pd"]!="" && $_SESSION["empresa_pd"]!=0) {
        $buscar_regional = soloNumeros($_SESSION["empresa_pd"]);
        $sqlFiltro .= " AND UE.empresa_pd = '".$_SESSION["empresa_pd"]."'";
        $_REQUEST["empresa_pd"] = $_SESSION["empresa_pd"];
    }
    if(isset($_REQUEST["sitioReunion"]) && soloNumeros($_REQUEST["sitioReunion"]) != ""){
        $buscar_prision = soloNumeros($_REQUEST["sitioReunion"]);
        $sqlFiltro .= " AND sat_reportes.sitioReunion = ".$buscar_prision."";
    }
    
    //
    if(isset($_REQUEST["rep_inex"]) && eliminarInvalidos($_REQUEST["rep_inex"]) != ""){
        $tipo = eliminarInvalidos($_REQUEST["rep_inex"]);
        if ($tipo == 2) {
            $sqlFiltro .= " AND sat_reportes.sitioReunion = 0 ";
        }else{
            $sqlFiltro .= " AND sat_reportes.sitioReunion <> 0 ";
        }    
    }else{
        $_REQUEST["rep_inex"] = "";
    }
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
    $sql .= $sqlFiltro." AND sat_reportes.rep_tip = 318 ORDER BY sat_reportes.id DESC";
    //
    $PSN1->query($sql);
    //echo $sql;
    if($PSN1->num_rows() > 0){
        if($PSN1->next_record()){
            $total_registros = $PSN1->f('conteo');
        }
    }
    $total_paginas = ceil($total_registros / $registros); 

    $sql = "SELECT C.descripcion AS regional, sat_reportes.*, U.nombre as nombreUsuario, sat_grupos.nombre as nombreGrupo, tbl_adjuntos.adj_url 
    FROM sat_reportes ";
    $sql .= " LEFT JOIN usuario AS U ON U.id = sat_reportes.idUsuario 
LEFT JOIN sat_grupos ON sat_grupos.id = sat_reportes.idGrupoMadre
LEFT JOIN tbl_adjuntos ON sat_reportes.id = tbl_adjuntos.adj_rep_fk 
LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
LEFT JOIN categorias AS C ON C.id = UE.empresa_pd
LEFT JOIN categorias AS CA ON CA.id = C.idSec";
    //
    $sql.=" WHERE 1 ".$sqlFiltro." AND sat_reportes.rep_tip = 318 GROUP BY sat_reportes.id ORDER BY fechaReporte DESC";
    $sql.= " LIMIT ".$inicio.", ".$registros;
    //
    
    $PSN1->query($sql);
    //echo $sql;
    //$total_registros=$PSN1->num_rows();
    //$total_paginas = ceil($total_registros / $registros);

    ?><div class="container">

    <form name="form" id="form" method="get" class="form-horizontal">
        <input type="hidden" name="doc" value="consultar-sub-programa-evangelistas" />
        <div>
            <h3 class="alert alert-info text-center">CONSULTAR REPORTES - EVANGELISTAS</h3>
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
                    <?php echo($zona == "" && $_SESSION["perfil"]!=162 )?'<option value="" selected >Todas las Zonas</option>':"";
                    /*
                    *   TRAEMOS LOS TIPOS DE CLIENTE/EMPRESA (15)
                    */
                    $sql = "SELECT * ";
                    $sql.=" FROM categorias ";
                    $sql.=" WHERE idSec = 85 ";
                    if ($_SESSION["id_zona"]!="" && $_SESSION["id_zona"]!=0) {
                        $sql.=" AND id = ".$_SESSION["id_zona"];
                    }
                    $sql .= " ORDER BY descripcion ASC ";

                    $PSN3->query($sql);
                    $numero=$PSN3->num_rows();
                    if($numero > 0){
                        while($PSN3->next_record()){
                            ?><option value="<?=$PSN3->f('id'); ?>" <?php
                            if($buscar_zona == $PSN3->f('id')){?>
                                selected="selected" <?php
                            }
                            ?>><?=$PSN3->f('descripcion'); ?></option><?php
                        }
                    }?>
                </select>
            </div>
            <div class="col-sm-2">
                <strong>Regional:</strong>
                <select  name="empresa_pd" id="regional" class="form-control" onchange="this.form.submit()">
                    <?php echo($zona == "" && $_SESSION["perfil"]!=163 && $_SESSION["perfil"]!=162 )?'<option value="" selected >Todas las regionales</option>':"";
                    $sql = "SELECT C.id, C.descripcion AS regional, CA.descripcion AS zona FROM categorias AS C";
                    $sql.=" LEFT JOIN categorias AS CA ON CA.id = C.idSec
                    WHERE CA.idSec = 85 ";
                    if (!empty($buscar_zona)) {
                        $sql.=" AND CA.id = ".$buscar_zona;
                    }
                    if ($_SESSION["perfil"]!=167) {
                        if ($_SESSION["empresa_pd"]!="" && $_SESSION["empresa_pd"]!=0) {
                            $sql.=" AND C.id = ".$_SESSION["empresa_pd"];
                        }
                    }
                    
                    $PSN2->query($sql); 
                    echo $sql;
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
            <div class="col-sm-3">
                <strong>Miembro de la regional:</strong>
                <select name="idUsuario" onchange="this.form.submit()" class="form-control">
                <?php
                if($_SESSION["perfil"] != 163){
                    ?><option value="">Ver todos</option><?php
                }

                $sql = "SELECT * ";
                $sql.=" FROM usuario AS U
                LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id";
                $sql.=" WHERE U.tipo IN (162, 163, 167) ";
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
                <div class="col-sm-2">
                    <strong>Fecha Inicial:</strong>
                    <input type="date" name="fechaInicial" id="fechaInicial" value="<?=$fechaInicial; ?>" class="form-control" />
                </div>
                <div class="col-sm-2">
                    <strong>Fecha Final:</strong>
                    <input type="date" name="fechaFinal" id="fechaFinal" value="<?=$fechaFinal; ?>" class="form-control" />
                </div>
                <div class="col-sm-1" >
                    <br>
                    <input type="submit" value="Filtrar" class="btn btn-success" />
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
                <h3 class="text-center">RESULTADOS DE BUSQUEDA</h3>
                <h5><?php echo $total_registros; ?> Registros encontrados</h5>
            </div>
            <div class="hr"><hr></div>
        </div>
        <div style="overflow-x: auto;">
            <table border="0" cellspacing="0" cellpadding="2"  align="center" class="table table-striped" style="font-size:12px">
                <thead>
                    <tr> 
                        <th width="120px">Regional</th>
                        <th>Prisiónes atendidas</th>
                        <th>Miembro de la Regional</th>
                        <th width="50px" title="Grupos intramuros atendidos">Intramuros atendidos</th>
                        <th width="50px" title="Grupos extramuros atendidos">Extramuros atendidos</th>
                        <th width="30px"  title="Total de creyentes">Creyentes</th>
                        <th width="30px"  title="Total de discípulos">Discípulos</th>
                        <th width="30px"  title="Número de nuevos bautizados">Nuevos Bautizados</th>
                        <th width="30px"  title="Número de voluntarios internos">Voluntarios internos</th>
                        <th width="30px"  title="Número de voluntarios externos">Voluntarios externos</th>
                        <th width="30px"  title="Número de pospenados">Número de pospenados</th>
                        <th width="80px">Fecha de reporte</th>
                        <th>Foto</th>

                    </tr>
                </thead>
                <tbody>
                    <?php
                    if($total_registros > 0){
                        $contador = 0;
                        while($PSN1->next_record()){
                            //Solo si no se ha modificado ya el formulario.
                            $id = $PSN1->f('id');
                            $plantador = $PSN1->f("plantador");
                            $rep_entr = $PSN1->f("rep_entr");
                            $fechaReporte = $PSN1->f("fechaReporte");
                            $fechaInicio = $PSN1->f("fechaInicio");        
                            $sitioReunion = $PSN1->f("sitioReunion");
                            $grupoMadre_txt = $PSN1->f("grupoMadre_txt");
                            $idGrupoMadre = $PSN1->f("idGrupoMadre");
                            $pabellon = $PSN1->f("pabellon");
                            $ciudad = $PSN1->f("ciudad");
                            $direccion = $PSN1->f("direccion");
                            $generacionNumero = intval($PSN1->f("generacionNumero"));
                            
                            $nombreUsuario = $PSN1->f("nombreUsuario");
                            $nombreGrupo = $PSN1->f("nombreGrupo");

                            $mapeo_comprometido = $PSN1->f("mapeo_comprometido");
                            $nombreGrupo_txt = $PSN1->f("nombreGrupo_txt");
                            $mapeo_fecha = $PSN1->f("mapeo_fecha");  

                            $mapeo_oracion = $PSN1->f("mapeo_oracion");  
                            $mapeo_companerismo = $PSN1->f("mapeo_companerismo");  
                            $mapeo_adoracion = $PSN1->f("mapeo_adoracion");  
                            $mapeo_biblia = $PSN1->f("mapeo_biblia");  
                            $mapeo_evangelizar = $PSN1->f("mapeo_evangelizar");  
                            $mapeo_cena = $PSN1->f("mapeo_cena");  
                            $mapeo_dar = $PSN1->f("mapeo_dar");  
                            $mapeo_bautizar = $PSN1->f("mapeo_bautizar");  
                            $mapeo_trabajadores = $PSN1->f("mapeo_trabajadores");  
                            

                            $ext1 = $PSN1->f("ext1");
                            $ext2 = $PSN1->f("ext2");
                            $ext3 = $PSN1->f("ext3");
                            

                            $asistencia_hom = $PSN1->f("asistencia_hom");
                            $asistencia_muj = $PSN1->f("asistencia_muj");
                            $asistencia_jov = $PSN1->f("asistencia_jov");
                            $asistencia_nin = $PSN1->f("asistencia_nin");

                            $bautizados = $PSN1->f("bautizados");

                            //Calculados:
                            $asistencia_total  = $PSN1->f("asistencia_total");
                            $discipulado  = $PSN1->f("discipulado");
                            $desiciones  = $PSN1->f("desiciones");
                            $preparandose  = $PSN1->f("preparandose");
                            $url_baut  = $PSN1->f("adj_nom");
                            $iglesias_reconocidas = $PSN1->f("iglesias_reconocidas");  
                            
                            ?><tr class='clickable-row' data-href='index.php?doc=gestionar-sub-programa-evangelistas&id=<?=$id; ?>' >
                                <!--<td><a href="index.php?doc=gestionar-sub-programa-evangelistas&id=<?=$id; ?>"><?=str_pad($id, 6, "0", STR_PAD_LEFT); ?></a></td>//-->
                                <?php if($sitioReunion != 0){
                                        $sql = "SELECT LOWER(D.departamento) AS departamento, M.municipio, C.descripcion AS regional, RU.reub_nom,RU.reub_dir ";
                                        $sql.=" FROM tbl_regional_ubicacion AS RU
                                        LEFT JOIN dane_municipios AS M ON M.id_municipio = RU.reub_mun_fk
                                        LEFT JOIN dane_departamentos AS D ON D.id_departamento = M.departamento_id
                                        LEFT JOIN categorias AS C ON C.id = RU.reub_reg_fk
                                         WHERE RU.reub_id = ".$sitioReunion;

                                        $PSN2->query($sql);
                                        $numero=$PSN2->num_rows();
                                        if($numero > 0){
                                            while($PSN2->next_record()){
                                                $departamento = $PSN2->f("departamento");
                                                $municipio = $PSN2->f("municipio");
                                                $regional = $PSN2->f("regional");
                                                $prision = $PSN2->f("reub_nom");
                                            }
                                        }
                                        
                                        
                                    }else{
                                        $sql = "SELECT M.municipio,LOWER(D.departamento) AS departamento ";
                                        $sql.=" FROM dane_municipios AS M
                                        LEFT JOIN dane_departamentos AS D ON D.id_departamento = M.departamento_id
                                         WHERE M.id_municipio = ".$ciudad;

                                        $PSN2->query($sql);
                                        $numero=$PSN2->num_rows();
                                        if($numero > 0){
                                            while($PSN2->next_record()){
                                                $regional = $PSN2->f("departamento");
                                                $prision = $PSN2->f("municipio");
                                            }
                                        }
                                        
                                    }?>
                                <td><?=$PSN1->f("regional"); ?></td>
                                <td><?=$asistencia_total;?></td>
                                <td><a href="index.php?doc=gestionar-sub-programa-evangelistas&id=<?=$id; ?>"><?=$nombreUsuario; ?></a></td>
                                <td><?=$asistencia_hom; ?></td>
                                <td><?=$asistencia_muj; ?></td>
                                <td><?=$asistencia_jov; ?></td>
                                <td><?=$asistencia_nin; ?></td>
                                <td><?=$bautizados; ?></td> 
                                <td><?=$discipulado; ?></td>   
                                <td><?=$desiciones; ?></td> 
                                <td><?=$preparandose; ?></td>                                
                                <td><?= date("d-m-Y", strtotime($fechaReporte)); ?></td>
                                <td align="center"><?php
                                    if($ext1 != ""){
                                        ?><!--<img src="images/png/thumb-up.png" width="20px" />-->
                                    <i class="fas fa-thumbs-up ico-lik"></i><?php
                                    }else{
                                        ?><!--<img src="images/png/thumb-down.png" width="20px" />-->
                                        <i class="fas fa-thumbs-down ico-dli"></i>
                                    <?php }?>
                                </td>
                                
                            </tr>
                            <?php
                            $contador++;
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>       
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

    <!--<br />
    <center>
    <a href="index.php?excelX=&doc=usuario_buscar&nombre=<?=$buscar_nombre; ?>&identificacion=<?=$buscar_identificacion; ?>&tipo=<?=$buscar_tipo; ?>&ctrl=<?=$ctrl; ?>" class="btn btn-info"><span class="glyphicon glyphicon-cloud-download"></span> DESCARGAR PARA EXCEL</a></center>//-->


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
    </script>