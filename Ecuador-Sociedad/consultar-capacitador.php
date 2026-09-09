<?php
/*
*   Consulta de reportes del programa Capacitadores (ecu_reportes,
*   tipo_reporte = 308). Misma lógica de filtros/permisos que
*   consultar-sub-programa-evangelistas.php, adaptada a la tabla nueva.
*   Ver CLAUDE.md, sección "Unificación de Capacitadores y ECC".
*/
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;
$temp_letrero = "CAPACITADORES";

$idUsuarioSesion = isset($_SESSION["id"]) ? intval($_SESSION["id"]) : 0;

if($idUsuarioSesion == 0){
    ?><div class="row">
        <h5 class="alert alert-danger text-center">Debe iniciar sesión para continuar.</h5>
    </div><?php
    return;
}

$PSN1->connect();

/*
*   Permisos: usuario.tipo = 2 ve los reportes de TODOS los capacitadores;
*   cualquier otro usuario solo ve los suyos (misma regla ya usada en
*   reportar_capacitador.php para el filtro de cárceles).
*/
$usuarioTipo = 0;
$PSN2->query("SELECT tipo FROM usuario WHERE id = ".$idUsuarioSesion." LIMIT 1");
if($PSN2->num_rows() > 0){
    $PSN2->next_record();
    $usuarioTipo = intval($PSN2->f("tipo"));
}
$esAdmin = ($usuarioTipo == 2);

/*
*   Fechas del filtro: mismo default que consultar-sub-programa-evangelistas.php
*   (desde el año 2000 hasta hoy si no se especifica nada). Se valida el
*   formato con una expresión regular antes de concatenarlas en el SQL.
*/
function fechaValida($valor){
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1;
}

$fechaInicial = isset($_REQUEST["fechaInicial"]) ? trim($_REQUEST["fechaInicial"]) : "";
if(!fechaValida($fechaInicial)){
    $fechaInicial = "2000-01-01";
}

$fechaFinal = isset($_REQUEST["fechaFinal"]) ? trim($_REQUEST["fechaFinal"]) : "";
if(!fechaValida($fechaFinal)){
    $fechaFinal = date("Y-m-d");
}

/*
*   Filtro "Miembro de la regional": solo el administrador (tipo = 2) puede
*   elegir a cuál usuario ver; cualquier otro usuario queda forzado a ver
*   únicamente sus propios reportes, sin importar qué venga en el request.
*/
$idUsuarioFiltro = 0;
if($esAdmin){
    $idUsuarioFiltro = isset($_REQUEST["idUsuario"]) ? intval($_REQUEST["idUsuario"]) : 0;
}else{
    $idUsuarioFiltro = $idUsuarioSesion;
}

/*
*   Filtro "Provincia": r.provincia_id apunta a dane_departamentos.id_departamento.
*/
$provinciaFiltro = isset($_REQUEST["provinciaId"]) ? intval($_REQUEST["provinciaId"]) : 0;

$sqlFiltro = " AND r.tipo_reporte = 308";
$sqlFiltro .= " AND r.fecha_inicio >= '".$fechaInicial."'";
$sqlFiltro .= " AND r.fecha_inicio <= '".$fechaFinal."'";
if($idUsuarioFiltro > 0){
    $sqlFiltro .= " AND r.idusuario = ".$idUsuarioFiltro;
}
if($provinciaFiltro > 0){
    $sqlFiltro .= " AND r.provincia_id = ".$provinciaFiltro;
}

/*
*   Paginación: mismo esquema que consultar-sub-programa-evangelistas.php
*   (50 registros por página).
*/
$registros = 50;
$pagina = isset($_GET["pagina"]) ? intval($_GET["pagina"]) : 0;
if($pagina < 1){
    $pagina = 1;
    $inicio = 0;
}else{
    $inicio = ($pagina - 1) * $registros;
}

$PSN1->query("SELECT COUNT(*) AS conteo FROM ecu_reportes r WHERE 1 ".$sqlFiltro);
$totalRegistros = 0;
if($PSN1->num_rows() > 0){
    $PSN1->next_record();
    $totalRegistros = intval($PSN1->f("conteo"));
}
$totalPaginas = ceil($totalRegistros / $registros);

$sqlLista = "SELECT r.idreporte, r.ubicacion, r.total_creyentes_grupo, r.nuevos_creyentes_grupo, ";
$sqlLista .= "r.total_bautizados_grupo, r.nuevos_bautizados_grupo, r.asistencia_grupo, r.foto, r.fecha_inicio, ";
$sqlLista .= "u.nombre AS nombre_usuario ";
$sqlLista .= "FROM ecu_reportes r LEFT JOIN usuario u ON u.id = r.idusuario ";
$sqlLista .= "WHERE 1 ".$sqlFiltro." ORDER BY r.idreporte DESC ";
$sqlLista .= "LIMIT ".$inicio.", ".$registros;
$PSN1->query($sqlLista);

/*
*   Combo "Miembro de la regional": solo se llena para el administrador,
*   con la misma lógica que consultar-sub-programa-evangelistas.php (lista
*   TODOS los usuarios con esos roles, hayan reportado o no — no solo los
*   que ya tienen reportes en ecu_reportes).
*/
$listaUsuarios = array();
if($esAdmin){
    $PSN2->query("SELECT U.id, U.nombre FROM usuario AS U
                  LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
                  WHERE U.tipo IN (162, 163, 167)
                  ORDER BY U.nombre ASC");
    while($PSN2->next_record()){
        $listaUsuarios[] = array(
            "id"     => intval($PSN2->f("id")),
            "nombre" => $PSN2->f("nombre"),
        );
    }
}

/*
*   Combo "Provincia": todas las provincias del catálogo (dane_departamentos),
*   sin importar si tienen o no reportes registrados.
*/
$listaProvincias = array();
$PSN2->query("SELECT id_departamento, departamento FROM dane_departamentos ORDER BY departamento ASC");
while($PSN2->next_record()){
    $listaProvincias[] = array(
        "id"     => intval($PSN2->f("id_departamento")),
        "nombre" => $PSN2->f("departamento"),
    );
}

?>
<div class="container">

    <form name="form" id="form" method="get" class="form-horizontal">
        <input type="hidden" name="doc" value="consultar-capacitador" />
        <div>
            <h3 class="alert alert-info text-center">CONSULTAR REPORTES - CAPACITADORES</h3>
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
            <?php if($esAdmin){ ?>
                <div class="col-sm-3">
                    <strong>Miembro de la regional:</strong>
                    <select name="idUsuario" onchange="this.form.submit()" class="form-control">
                        <option value="">Ver todos</option>
                        <?php foreach($listaUsuarios as $usuarioItem){ ?>
                            <option value="<?=$usuarioItem["id"]; ?>" <?php if($idUsuarioFiltro == $usuarioItem["id"]){ ?>selected="selected"<?php } ?>>
                                <?=htmlspecialchars($usuarioItem["nombre"], ENT_QUOTES, "UTF-8"); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            <?php }else{ ?>
                <div class="col-sm-3">
                    <strong>Miembro de la regional:</strong>
                    <input type="text" class="form-control" value="Solo tus reportes" disabled="disabled" />
                </div>
            <?php } ?>
            <div class="col-sm-3">
                <strong>Provincia:</strong>
                <select name="provinciaId" onchange="this.form.submit()" class="form-control">
                    <option value="">Ver todas</option>
                    <?php foreach($listaProvincias as $provinciaItem){ ?>
                        <option value="<?=$provinciaItem["id"]; ?>" <?php if($provinciaFiltro == $provinciaItem["id"]){ ?>selected="selected"<?php } ?>>
                            <?=htmlspecialchars($provinciaItem["nombre"], ENT_QUOTES, "UTF-8"); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-sm-2">
                <strong>Fecha Inicial:</strong>
                <input type="date" name="fechaInicial" id="fechaInicial" value="<?=htmlspecialchars($fechaInicial, ENT_QUOTES, "UTF-8"); ?>" class="form-control" />
            </div>
            <div class="col-sm-2">
                <strong>Fecha Final:</strong>
                <input type="date" name="fechaFinal" id="fechaFinal" value="<?=htmlspecialchars($fechaFinal, ENT_QUOTES, "UTF-8"); ?>" class="form-control" />
            </div>
            <div class="col-sm-1">
                <br>
                <input type="submit" value="Filtrar" class="btn btn-success" />
            </div>
        </div>
    </form>
</div>
<style>
.table tbody tr:hover td, .table tbody tr:hover th {
    background-color: #E0EEEE;
    color:#000;
}

.clickable-row {
    cursor: pointer;
}

.table thead tr{
    background-color: #C7C7C7;
}

.table thead th{
    vertical-align: middle;
    text-align: center;
    border: 1px solid #D5D5D5;
}

.table tbody td{
    vertical-align: middle;
    text-align: center;
    border: 1px solid #E3E3E3;
}

.table{
    border-collapse: collapse;
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
            <h5><?php echo $totalRegistros; ?> Registros encontrados</h5>
        </div>
        <div class="hr"><hr></div>
    </div>
    <div style="overflow-x: auto;">
        <table border="0" cellspacing="0" cellpadding="2" align="center" class="table table-striped" style="font-size:12px">
            <thead>
                <tr>
                    <th width="80px">ID reporte</th>
                    <th width="220px">Miembro de la Regional</th>
                    <th width="220px">Ubicación</th>
                    <th width="70px" title="Total creyentes">Total creyentes</th>
                    <th width="70px" title="Nuevos creyentes">Nuevos creyentes</th>
                    <th width="70px" title="Total bautizados">Total bautizados</th>
                    <th width="70px" title="Nuevos bautizados">Nuevos bautizados</th>
                    <th width="70px" title="Asistencia del grupo">Asistencia del grupo</th>
                    <th width="60px">Foto</th>
                </tr>
            </thead>
            <tbody>
                <?php if($totalRegistros > 0){
                    while($PSN1->next_record()){
                        $foto = $PSN1->f("foto");
                ?>
                    <tr class="clickable-row" data-href="index.php?doc=editar_capacitador&idreporte=<?=$PSN1->f("idreporte"); ?>">
                        <td><?=str_pad($PSN1->f("idreporte"), 6, "0", STR_PAD_LEFT); ?></td>
                        <td><?=htmlspecialchars($PSN1->f("nombre_usuario"), ENT_QUOTES, "UTF-8"); ?></td>
                        <td><?=htmlspecialchars($PSN1->f("ubicacion"), ENT_QUOTES, "UTF-8"); ?></td>
                        <td><?=$PSN1->f("total_creyentes_grupo"); ?></td>
                        <td><?=$PSN1->f("nuevos_creyentes_grupo"); ?></td>
                        <td><?=$PSN1->f("total_bautizados_grupo"); ?></td>
                        <td><?=$PSN1->f("nuevos_bautizados_grupo"); ?></td>
                        <td><strong><?=$PSN1->f("asistencia_grupo"); ?></strong></td>
                        <td align="center">
                            <?php if($foto != ""){ ?>
                                <i class="fas fa-thumbs-up ico-lik" title="Con foto"></i>
                            <?php }else{ ?>
                                <i class="fas fa-thumbs-down ico-dli" title="Sin foto"></i>
                            <?php } ?>
                        </td>
                    </tr>
                <?php }
                }else{ ?>
                    <tr>
                        <td colspan="9" class="text-center">No se encontraron reportes con los filtros seleccionados.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<center>
<div class="container">
    <ul class="pagination">
        <?php
        $queryActual = $_GET;
        unset($queryActual["pagina"]);

        if(($pagina - 1) > 0){
            $queryActual["pagina"] = $pagina - 1;
            echo "<li><a href='index.php?".http_build_query($queryActual)."'>&laquo;</a></li>";
        }

        for($i = 1; $i <= $totalPaginas; $i++){
            $queryActual["pagina"] = $i;
            $url = "index.php?".http_build_query($queryActual);
            if($pagina == $i){
                echo "<li class='active'><a href='".$url."'>".$i."</a>";
            }else{
                echo "<li><a href='".$url."'>".$i."</a></li>";
            }
        }

        if(($pagina + 1) <= $totalPaginas){
            $queryActual["pagina"] = $pagina + 1;
            echo "<li><a href='index.php?".http_build_query($queryActual)."'>&raquo;</a></li>";
        }
        ?>
    </ul>
</div>
</center>

<script>
jQuery(document).ready(function($) {
    $(".clickable-row").click(function() {
        window.location = $(this).data("href");
    });
});
</script>
