<?php
/*
*	$PSN = new DBbase_Sql;
*/
// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;

/*
*   PAGINACION
*/
$registros = 50;
$pagina = soloNumeros($_GET["pagina"]);

if (!$pagina) {
    $inicio = 0;
    $pagina = 1;
} else {
    $inicio = ($pagina - 1) * $registros;
}

/*
*   FILTROS DE BUSQUEDA
*/
$sqlFiltro = "";
$buscar_programa = "";
$buscar_facilitador = "";
$buscar_nombre = "";
$buscar_tarjeta = "";

if (isset($_REQUEST["rep_tip"]) && soloNumeros($_REQUEST["rep_tip"]) != "") {
    $buscar_programa = soloNumeros($_REQUEST["rep_tip"]);
    $sqlFiltro .= " AND sat_reportes.rep_tip = '" . $buscar_programa . "'";
}

if (isset($_REQUEST["idUsuario"]) && soloNumeros($_REQUEST["idUsuario"]) != "") {
    $buscar_facilitador = soloNumeros($_REQUEST["idUsuario"]);
    $sqlFiltro .= " AND (sat_reportes.idUsuario = '" . $buscar_facilitador . "' OR FIND_IN_SET('" . $buscar_facilitador . "', sat_reportes.idUsuario))";
}

if (isset($_REQUEST["nombre"]) && eliminarInvalidos($_REQUEST["nombre"]) != "") {
    $buscar_nombre = eliminarInvalidos($_REQUEST["nombre"]);
    $sqlFiltro .= " AND ADJ.adj_nom LIKE '%" . $buscar_nombre . "%'";
}

if (isset($_REQUEST["tarjeta"]) && eliminarInvalidos($_REQUEST["tarjeta"]) != "") {
    $buscar_tarjeta = eliminarInvalidos($_REQUEST["tarjeta"]);
    $sqlFiltro .= " AND ADJ.adj_url LIKE '%" . $buscar_tarjeta . "%'";
}

/*
*   BASE DE LA CONSULTA: GRADUADOS (tbl_adjuntos.adj_tip = 1)
*/
$sqlBase = " FROM tbl_adjuntos AS ADJ
    INNER JOIN sat_reportes ON sat_reportes.id = ADJ.adj_rep_fk
    LEFT JOIN usuario AS U ON (U.id = sat_reportes.idUsuario OR FIND_IN_SET(U.id, sat_reportes.idUsuario))
    LEFT JOIN categorias AS C ON C.id = sat_reportes.rep_tip
    WHERE ADJ.adj_tip = 1 " . $sqlFiltro;

/*
*   TOTAL DE REGISTROS
*/
$sql = "SELECT COUNT(DISTINCT ADJ.adj_id) as conteo " . $sqlBase;
$PSN1->query($sql);
$total_registros = 0;
if ($PSN1->num_rows() > 0) {
    if ($PSN1->next_record()) {
        $total_registros = $PSN1->f('conteo');
    }
}
$total_paginas = ceil($total_registros / $registros);

/*
*   REGISTROS DE LA PAGINA ACTUAL
*/
$sql = "SELECT DISTINCT ADJ.adj_id, ADJ.adj_nom, ADJ.adj_url, ADJ.adj_fec, U.nombre AS nombreFacilitador, C.descripcion AS nombrePrograma " . $sqlBase;
$sql .= " ORDER BY ADJ.adj_fec DESC, ADJ.adj_id DESC";
$sql .= " LIMIT " . $inicio . ", " . $registros;
$PSN1->query($sql);
$numero = $PSN1->num_rows();
?>
<div class="container">
    <form name="form" id="form" method="get" class="form-horizontal">
        <input type="hidden" name="doc" value="buscar_personas" />
        <div>
            <h3 class="alert alert-info text-center">BUSCAR GRADUADOS</h3>
        </div>
        <div class="cont-tit">
            <div class="hr"><hr></div>
            <div class="tit-cen">
                <h3 class="text-center">FILTRO DE BUSQUEDA</h3>
                <h5>de graduados</h5>
            </div>
            <div class="hr"><hr></div>
        </div>
        <div class="form-group">
            <div class="col-sm-3">
                <strong>Programa:</strong>
                <select name="rep_tip" id="rep_tip" class="form-control" onchange="this.form.submit()">
                    <option value="">Todos los programas</option>
                    <?php
                    /*
                    *   TRAEMOS LOS PROGRAMAS DEL CATALOGO (categorias)
                    */
                    $sql = "SELECT id, descripcion
                        FROM categorias
                        WHERE id IN (308, 317, 318, 327)
                        ORDER BY descripcion ASC";
                    $PSN2->query($sql);
                    if ($PSN2->num_rows() > 0) {
                        while ($PSN2->next_record()) {
                            ?><option value="<?=$PSN2->f('id'); ?>" <?php
                                if ($buscar_programa == $PSN2->f('id')) {
                                    ?>selected="selected"<?php
                                }
                            ?>><?=$PSN2->f('descripcion'); ?></option><?php
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-sm-3">
                <strong>Facilitador:</strong>
                <select name="idUsuario" id="idUsuario" class="form-control" onchange="this.form.submit()">
                    <option value="">Todos los facilitadores</option>
                    <?php
                    /*
                    *   TRAEMOS LOS USUARIOS FACILITADORES (mismo criterio que
                    *   consultar-sub-programa-evangelistas.php)
                    */
                    $sql = "SELECT U.id, U.nombre
                        FROM usuario AS U
                        LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
                        WHERE U.tipo IN (162, 163, 167, 168)
                        ORDER BY U.nombre ASC";
                    $PSN2->query($sql);
                    if ($PSN2->num_rows() > 0) {
                        while ($PSN2->next_record()) {
                            ?><option value="<?=$PSN2->f('id'); ?>" <?php
                                if ($buscar_facilitador == $PSN2->f('id')) {
                                    ?>selected="selected"<?php
                                }
                            ?>><?=$PSN2->f('nombre'); ?></option><?php
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-sm-3">
                <strong>Nombre del graduado:</strong>
                <input type="text" name="nombre" id="nombre" list="lista_graduados" value="<?=htmlspecialchars($buscar_nombre, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" autocomplete="off" placeholder="Escriba para buscar..." />
                <datalist id="lista_graduados">
                    <?php
                    /*
                    *   SUGERENCIAS DE NOMBRES PARA EL AUTOCOMPLETADO
                    */
                    $sql = "SELECT DISTINCT ADJ.adj_nom
                        FROM tbl_adjuntos AS ADJ
                        WHERE ADJ.adj_tip = 1 AND ADJ.adj_nom <> ''
                        ORDER BY ADJ.adj_nom ASC";
                    $PSN2->query($sql);
                    if ($PSN2->num_rows() > 0) {
                        while ($PSN2->next_record()) {
                            ?><option value="<?=htmlspecialchars($PSN2->f('adj_nom'), ENT_QUOTES, 'UTF-8'); ?>"><?php
                        }
                    }
                    ?>
                </datalist>
            </div>
            <div class="col-sm-2">
                <strong>N&deg; de tarjeta:</strong>
                <input type="text" name="tarjeta" id="tarjeta" value="<?=htmlspecialchars($buscar_tarjeta, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="N° tarjeta / identificación" />
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
    color: #000;
}

.table thead tr {
    background-color: #C7C7C7;
}

.table th, .table td {
    text-align: center;
    vertical-align: middle !important;
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
        <table border="0" cellspacing="0" cellpadding="2" align="center" class="table table-striped">
            <thead>
                <tr>
                    <th>Facilitador</th>
                    <th>Nombre del graduado</th>
                    <th>N&deg; de tarjeta</th>
                    <th>Programa</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($numero > 0) {
                    while ($PSN1->next_record()) {
                        ?><tr>
                            <td><?=$PSN1->f("nombreFacilitador"); ?></td>
                            <td><?=$PSN1->f("adj_nom"); ?></td>
                            <td><?=$PSN1->f("adj_url"); ?></td>
                            <td><?=$PSN1->f("nombrePrograma"); ?></td>
                        </tr><?php
                    }
                } else {
                    ?><tr><td colspan="4">Sin registros</td></tr><?php
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
        $paginaActualTxT = "&pagina=" . $pagina;
        $_SERVER['REQUEST_URI'] = str_replace($paginaActualTxT, "", $_SERVER['REQUEST_URI']);

        if (($pagina - 1) > 0) {
            echo "<li><a href='" . $_SERVER['REQUEST_URI'] . "&pagina=" . ($pagina - 1) . "'>&laquo;</a></li>";
        }

        for ($i = 1; $i <= $total_paginas; $i++) {
            if ($pagina == $i) {
                echo "<li class='active'><a href='" . $_SERVER['REQUEST_URI'] . "&pagina=$i'>$i</a>";
            } else {
                echo "<li><a href='" . $_SERVER['REQUEST_URI'] . "&pagina=$i'>$i</a></li>";
            }
        }

        if (($pagina + 1) <= $total_paginas) {
            echo "<li><a href='" . $_SERVER['REQUEST_URI'] . "&pagina=" . ($pagina + 1) . "'>&raquo;</a></li>";
        }
        ?>
    </ul>
</div>
</center>
