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

    <form name="form" id="form" method="get" class="form-horizontal filtro-personas">
        <input type="hidden" name="doc" value="buscar_personas" />
        <div class="panel-filtro">
            <div class="row">
                <div class="col-sm-6 col-md-3 filtro-col">
                    <label>Facilitador</label>
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
                <div class="col-sm-6 col-md-3 filtro-col">
                    <label>Nombre del graduado</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($buscar_nombre) ?>" class="form-control" placeholder="Buscar por nombre..." />
                </div>
                <div class="col-sm-6 col-md-3 filtro-col">
                    <label>Tarjeta de identificación</label>
                    <input type="text" name="identificacion" value="<?= htmlspecialchars($buscar_identificacion) ?>" class="form-control" placeholder="Buscar por identificación..." />
                </div>
                <div class="col-sm-6 col-md-3 filtro-col">
                    <label>Programa</label>
                    <select name="programa_id" class="form-control">
                        <option value="">Todos los programas</option>
                        <option value="307" <?= $buscar_programa=="307" ? 'selected' : '' ?>>La Peregrinación del Prisionero (LPP)</option>
                        <option value="308" <?= $buscar_programa=="308" ? 'selected' : '' ?>>Cada Comunidad para Cristo (C&M)</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-3 filtro-col">
                    <label>Fecha inicial</label>
                    <input type="date" name="fechaInicial" value="<?= htmlspecialchars($fechaInicial) ?>" class="form-control" />
                </div>
                <div class="col-sm-6 col-md-3 filtro-col">
                    <label>Fecha final</label>
                    <input type="date" name="fechaFinal" value="<?= htmlspecialchars($fechaFinal) ?>" class="form-control" />
                </div>
                <div class="col-sm-12 col-md-6 filtro-botones">
                    <label class="filtro-botones-spacer">&nbsp;</label>
                    <div class="filtro-botones-fila">
                        <input type="submit" value="Filtrar" class="btn btn-success" />
                        <a href="?doc=buscar_personas" class="btn btn-default">Limpiar</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<style>
.filtro-personas .panel-filtro {
    background-color: #fff;
    border: 1px solid #e3e3e3;
    border-radius: 4px;
    padding: 20px 20px 6px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.filtro-personas label {
    display: block;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .3px;
    color: #2f6f9f;
    margin-bottom: 6px;
}

.filtro-personas .form-control {
    height: 38px;
}

.filtro-personas .form-control:focus {
    border-color: #2f6f9f;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 6px rgba(47,111,159,.4);
}

.filtro-personas .filtro-col,
.filtro-personas .filtro-botones {
    margin-bottom: 16px;
}

.filtro-personas .row {
    margin-left: -12px;
    margin-right: -12px;
}

.filtro-personas .filtro-col {
    padding-left: 12px;
    padding-right: 12px;
}

@media (min-width: 992px) {
    .filtro-personas .filtro-col + .filtro-col {
        border-left: 1px solid #eef0f2;
    }
}

.filtro-botones-spacer {
    visibility: hidden;
}

.filtro-botones-fila {
    display: flex;
    gap: 10px;
}

.filtro-botones-fila .btn {
    height: 38px;
    padding: 8px 24px;
    line-height: 1.2;
}

.filtro-personas .btn-success {
    background-color: #2f6f9f;
    border-color: #2f6f9f;
}

.filtro-personas .btn-success:hover {
    background-color: #255a80;
    border-color: #255a80;
}

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

.table-resultados {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    border-collapse: separate;
    border-spacing: 0;
    background-color: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.table-resultados thead th {
    background-color: #2f6f9f;
    color: #fff;
    font-weight: 600;
    padding: 10px 8px;
    border: 1px solid #2f6f9f;
}

.table-resultados tbody td {
    padding: 8px 10px;
    border: 1px solid #e3e3e3;
    vertical-align: middle;
    text-align: center;
}

.table-resultados tbody tr:nth-child(even) {
    background-color: #f8f9fb;
}

.table-resultados tbody tr:hover {
    background-color: #eef6ff;
}

.table-resultados td:first-child,
.table-resultados th:first-child {
    text-align: left;
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
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover table-resultados" style="font-size:13px">
            <thead>
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
