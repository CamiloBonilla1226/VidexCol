<?php
/*
*   $PSN = new DBbase_Sql;
*/
// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;


if(!isset($_REQUEST["fechaInicial"]) || eliminarInvalidos($_REQUEST["fechaInicial"]) == ""){
    $_REQUEST["fechaInicial"] = '2000-01-01';
}
if(!isset($_REQUEST["fechaFinal"]) || eliminarInvalidos($_REQUEST["fechaFinal"]) == ""){
    $_REQUEST["fechaFinal"] = date("Y-m-d");
}

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
*   FILTROS
*/
$sqlFiltro = "";
$buscar_idUsuario = "";
$buscar_nombre = isset($_REQUEST["nombre"]) ? eliminarInvalidos($_REQUEST["nombre"]) : "";
$buscar_identificacion = isset($_REQUEST["identificacion"]) ? eliminarInvalidos($_REQUEST["identificacion"]) : "";
$buscar_programa = isset($_REQUEST["programa_id"]) ? soloNumeros($_REQUEST["programa_id"]) : "";
$fechaInicial = isset($_REQUEST["fechaInicial"]) ? $_REQUEST["fechaInicial"] : "";
$fechaFinal   = isset($_REQUEST["fechaFinal"])   ? $_REQUEST["fechaFinal"]   : "";

// Perfil 163 (facilitador) solo puede ver los graduados que le pertenecen
if($_SESSION["perfil"] == 163){
    $buscar_idUsuario = soloNumeros($_SESSION["id"]);
} elseif(isset($_REQUEST["idUsuario"]) && soloNumeros($_REQUEST["idUsuario"]) > 0){
    $buscar_idUsuario = soloNumeros($_REQUEST["idUsuario"]);
}

if($buscar_idUsuario != ""){
    $sqlFiltro .= " AND TG.usuario_id = '".$buscar_idUsuario."'";
}

if($buscar_nombre != ""){
    $sqlFiltro .= " AND TG.nombre_graduado LIKE '%".addslashes($buscar_nombre)."%'";
}

if($buscar_identificacion != ""){
    $sqlFiltro .= " AND TG.identificacion_graduado LIKE '%".addslashes($buscar_identificacion)."%'";
}

if($buscar_programa == 307 || $buscar_programa == 308){
    $sqlFiltro .= " AND TG.programa_id = '".$buscar_programa."'";
}

if($fechaInicial != ""){
    $sqlFiltro .= " AND TG.fecha_reporte >= '".$fechaInicial."'";
}

if($fechaFinal != ""){
    $sqlFiltro .= " AND TG.fecha_reporte <= '".$fechaFinal."'";
}

/*
*   UNION DE GRADUADOS: LPP (307) + C&M (308)
*   Son los únicos programas que almacenan graduados de forma individual (nombre + identificación).
*/
$sqlUnion = "
    SELECT
        CONVERT(G.nombre USING utf8mb4)         AS nombre_graduado,
        CONVERT(G.identificacion USING utf8mb4) AS identificacion_graduado,
        RL.usuario_id    AS usuario_id,
        RL.fecha_reporte AS fecha_reporte,
        RL.programa_id   AS programa_id
    FROM reporte_graduado_lpp AS G
    INNER JOIN reporte_lpp AS RL ON RL.id_lpp = G.id_reporte_lpp

    UNION ALL

    SELECT
        CONVERT(G.nombre USING utf8mb4)         AS nombre_graduado,
        CONVERT(G.identificacion USING utf8mb4) AS identificacion_graduado,
        RC.usuario_id    AS usuario_id,
        RC.fecha_reporte AS fecha_reporte,
        RC.programa_id   AS programa_id
    FROM reporte_graduado_cm AS G
    INNER JOIN reporte_cm AS RC ON RC.id_cm = G.id_cm
";

// Conteo
$sql = "SELECT count(*) AS conteo FROM (".$sqlUnion.") AS TG WHERE 1=1 ".$sqlFiltro;
$PSN1->query($sql);
$total_registros = 0;
if($PSN1->num_rows() > 0){
    if($PSN1->next_record()){
        $total_registros = $PSN1->f('conteo');
    }
}
$total_paginas = ceil($total_registros / $registros);

// Datos de la página
if($total_registros > 0){
    $sql = "SELECT TG.*, U.nombre AS facilitador,
            CASE TG.programa_id
                WHEN 307 THEN 'La Peregrinación del Prisionero (LPP)'
                WHEN 308 THEN 'Cada Comunidad para Cristo (C&M)'
                ELSE 'Otro'
            END AS programa
        FROM (".$sqlUnion.") AS TG
        LEFT JOIN usuario AS U ON U.id = TG.usuario_id
        WHERE 1=1 ".$sqlFiltro."
        ORDER BY TG.fecha_reporte DESC
        LIMIT ".$inicio.", ".$registros;

    $PSN1->query($sql);
}
?><div class="container">

<form name="form" id="form" method="get" class="form-horizontal">
    <input type="hidden" name="doc" value="buscar_personas" />
    <div>
        <h3 class="alert alert-info text-center">BUSCAR PERSONAS - GRADUADOS</h3>
    </div>
    <div class="cont-tit">
        <div class="hr"><hr></div>
        <div class="tit-cen">
            <h3>FILTRO DE BUSQUEDA</h3>
            <h5>de GRADUADOS</h5>
        </div>
        <div class="hr"><hr></div>
    </div>
    <div class="form-group">
        <div class="col-sm-2">
            <strong>Facilitador:</strong>
            <select name="idUsuario" class="form-control">
                <?php if($_SESSION["perfil"] != 163){ ?>
                    <option value="">— Ver todos —</option>
                <?php }
                $sql = "SELECT U.id, U.nombre FROM usuario AS U WHERE U.tipo IN (162, 163, 167) ";
                if($_SESSION["perfil"] == 163){
                    $sql .= " AND U.id = '".soloNumeros($_SESSION["id"])."'";
                }
                $sql .= " ORDER BY U.nombre ASC";
                $PSN2->query($sql);
                while($PSN2->next_record()){
                    $sel = ($buscar_idUsuario == $PSN2->f('id')) ? 'selected' : '';
                    echo '<option value="'.$PSN2->f('id').'" '.$sel.'>'.htmlspecialchars($PSN2->f('nombre')).'</option>';
                }
                ?>
            </select>
        </div>
        <div class="col-sm-2">
            <strong>Nombre del graduado:</strong>
            <input type="text" name="nombre" value="<?= htmlspecialchars($buscar_nombre) ?>" class="form-control" placeholder="Nombre" />
        </div>
        <div class="col-sm-2">
            <strong>Tarjeta de identificación:</strong>
            <input type="text" name="identificacion" value="<?= htmlspecialchars($buscar_identificacion) ?>" class="form-control" placeholder="Identificación" />
        </div>
        <div class="col-sm-2">
            <strong>Programa:</strong>
            <select name="programa_id" class="form-control">
                <option value="">Todos los programas</option>
                <option value="307" <?= $buscar_programa=="307" ? 'selected' : '' ?>>La Peregrinación del Prisionero (LPP)</option>
                <option value="308" <?= $buscar_programa=="308" ? 'selected' : '' ?>>Cada Comunidad para Cristo (C&M)</option>
            </select>
        </div>
        <div class="col-sm-2">
            <strong>Fecha inicial:</strong>
            <input type="date" name="fechaInicial" value="<?= htmlspecialchars($fechaInicial) ?>" class="form-control" />
        </div>
        <div class="col-sm-2">
            <strong>Fecha final:</strong>
            <input type="date" name="fechaFinal" value="<?= htmlspecialchars($fechaFinal) ?>" class="form-control" />
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-2" style="padding-top:4px">
            <input type="submit" value="Filtrar" class="btn btn-success" />
            <a href="?doc=buscar_personas" class="btn btn-default">Limpiar</a>
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
        <table border="0" cellspacing="0" cellpadding="4" align="center" class="table table-striped table-bordered table-hover" style="font-size:13px">
            <thead style="background-color:#5a7a9e;color:#fff;">
                <tr>
                    <th style="text-align:center">Facilitador</th>
                    <th style="text-align:center">Nombre del graduado</th>
                    <th style="text-align:center">Tarjeta de identificación</th>
                    <th style="text-align:center">Programa</th>
                    <th style="text-align:center;white-space:nowrap">Fecha reporte</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if($total_registros > 0){
                    while($PSN1->next_record()){
                        $facilitador          = $PSN1->f("facilitador");
                        $nombre_graduado       = $PSN1->f("nombre_graduado");
                        $identificacion_graduado = $PSN1->f("identificacion_graduado");
                        $programa              = $PSN1->f("programa");
                        $fecha_reporte         = $PSN1->f("fecha_reporte");
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($facilitador) ?></td>
                            <td><?= htmlspecialchars($nombre_graduado) ?></td>
                            <td style="text-align:center"><?= htmlspecialchars($identificacion_graduado) ?></td>
                            <td><?= htmlspecialchars($programa) ?></td>
                            <td style="text-align:center;white-space:nowrap"><?= $fecha_reporte ? date("d-m-Y", strtotime($fecha_reporte)) : '—' ?></td>
                        </tr>
                        <?php
                    }
                } else { ?>
                    <tr><td colspan="5" class="text-center" style="padding:20px;color:#888">No se encontraron registros</td></tr>
                <?php } ?>
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
