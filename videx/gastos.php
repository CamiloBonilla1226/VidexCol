<?php
$gastosAssetVersion = '20260417-1810';
?>
<link rel="stylesheet" href="gastos.css?v=<?php echo $gastosAssetVersion; ?>">
<script>
(function () {
    function ensureGastosClass() {
        if (document.body && !document.body.classList.contains('gastos-page')) {
            document.body.classList.add('gastos-page');
        }
    }

    ensureGastosClass();
    document.addEventListener('DOMContentLoaded', ensureGastosClass);
})();
</script>
<?php

$PSN1 = new DBbase_Sql;
$PSNTEMP = new DBbase_Sql;

$sql = "SELECT idMenu ";
$sql .= " FROM usuarios_menu ";
$sql .= " WHERE idMenu = 48 ";
$sql .= " AND idUsuario = '" . $_SESSION["id"] . "'";
$PSN1->query($sql);
if ($PSN1->num_rows() == 0) {
    die("NO esta autorizado a ver esta opción.");
}

$registros = 50;
$pagina = (int) soloNumeros($_GET["pagina"] ?? 1);
if ($pagina < 1) {
    $pagina = 1;
}
$inicio = ($pagina - 1) * $registros;

$tipo = soloNumeros($_GET["tipo"] ?? '');
$observacion = eliminarInvalidos($_GET["observacion"] ?? '');
$fechaInicial = eliminarInvalidos($_GET["fechaInicial"] ?? '');
$fechaFinal = eliminarInvalidos($_GET["fechaFinal"] ?? '');

$sqlFiltro = "";
if ($tipo !== "") {
    $sqlFiltro .= " AND gastos.tipo = '" . $tipo . "'";
}
if ($observacion !== "") {
    $sqlFiltro .= " AND gastos.observacion LIKE '%" . $observacion . "%'";
}
if ($fechaInicial !== "") {
    $sqlFiltro .= " AND gastos.fechaCreacion >= '" . $fechaInicial . "'";
}
if ($fechaFinal !== "") {
    $sqlFiltro .= " AND gastos.fechaCreacion <= '" . $fechaFinal . "'";
}

$valorGastado = 0;
$totalRegistros = 0;

$sql = "SELECT COUNT(gastos.id) AS total, COALESCE(SUM(gastos.valor), 0) AS total_valor ";
$sql .= " FROM gastos ";
$sql .= " WHERE gastos.idCotizacion = 0";
$PSN1->query($sql . $sqlFiltro);
if ($PSN1->next_record()) {
    $valorGastado = (float) $PSN1->f('total_valor');
    $totalRegistros = (int) $PSN1->f('total');
}

$totalPaginas = max(1, (int) ceil($totalRegistros / $registros));
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $inicio = ($pagina - 1) * $registros;
}

$sql = "SELECT ";
$sql .= " gastos.id AS idGasto, ";
$sql .= " gastos.fechaCreacion AS fechaCreacion2, ";
$sql .= " gastos.observacion, ";
$sql .= " gastos.ext, ";
$sql .= " gastos.valor, ";
$sql .= " usuario.nombre, ";
$sql .= " categorias.descripcion AS tipoDescripcion ";
$sql .= " FROM gastos ";
$sql .= " INNER JOIN usuario ON gastos.idUsuario = usuario.id ";
$sql .= " LEFT JOIN categorias ON categorias.id = gastos.tipo ";
$sql .= " WHERE gastos.idCotizacion = 0";
$sql .= $sqlFiltro;
$sql .= " ORDER BY gastos.fechaCreacion DESC, gastos.id DESC";
$sql .= " LIMIT " . $inicio . ", " . $registros;

$PSN1->query($sql);
$numero = $PSN1->num_rows();

$baseParams = [
    'doc' => 'gastos',
    'opc' => 2,
];
if ($tipo !== '') {
    $baseParams['tipo'] = $tipo;
}
if ($observacion !== '') {
    $baseParams['observacion'] = $observacion;
}
if ($fechaInicial !== '') {
    $baseParams['fechaInicial'] = $fechaInicial;
}
if ($fechaFinal !== '') {
    $baseParams['fechaFinal'] = $fechaFinal;
}
?>

<div class="gastos-shell">
    <div class="gastos-header">
        <h2>Gastos globales</h2>
        <p>Vista reorganizada con los registros más recientes al inicio.</p>
    </div>

    <form action="index.php" name="form" id="form" method="get" class="gastos-form-card">
        <input type="hidden" name="doc" value="gastos" />
        <input type="hidden" name="opc" value="2" />

        <div class="gastos-grid">
            <div class="form-group">
                <label class="form-label" for="observacion">Observación</label>
                <input type="text" name="observacion" id="observacion" value="<?php echo htmlspecialchars($observacion); ?>" class="form-control" />
            </div>

            <div class="form-group">
                <label class="form-label" for="tipo">Tipo del gasto</label>
                <select name="tipo" id="tipo" class="form-control">
                    <option value="">Todos</option>
                    <?php
                    $sql = "SELECT categorias.* ";
                    $sql .= " FROM categorias ";
                    $sql .= " WHERE idSec = 24 ";
                    $sql .= " ORDER BY descripcion asc";

                    $PSNTEMP->query($sql);
                    if ($PSNTEMP->num_rows() > 0) {
                        while ($PSNTEMP->next_record()) {
                            ?>
                            <option value="<?php echo $PSNTEMP->f('id'); ?>" <?php echo ((string) $tipo === (string) $PSNTEMP->f('id')) ? 'selected="selected"' : ''; ?>>
                                <?php echo $PSNTEMP->f('descripcion'); ?>
                            </option>
                            <?php
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="fechaInicial">Fecha inicial</label>
                <input name="fechaInicial" type="date" id="fechaInicial" value="<?php echo htmlspecialchars($fechaInicial); ?>" class="form-control" />
            </div>

            <div class="form-group">
                <label class="form-label" for="fechaFinal">Fecha final</label>
                <input name="fechaFinal" type="date" id="fechaFinal" value="<?php echo htmlspecialchars($fechaFinal); ?>" class="form-control" />
            </div>
        </div>

        <div class="gastos-actions">
            <button type="submit" class="btn btn-success">Buscar</button>
            <a href="index.php?doc=gasto_crear" class="btn btn-info">Nuevo gasto</a>
        </div>
    </form>

    <div class="gastos-summary">
        <strong><?php echo $totalRegistros; ?></strong> registros por un total de
        <strong>$<?php echo number_format($valorGastado, 0, ",", "."); ?></strong>.
    </div>

    <div class="gastos-table-scroll-note">Desliza horizontalmente para ver todas las columnas.</div>

    <div class="gastos-table-wrap" tabindex="0" aria-label="Tabla de gastos con desplazamiento horizontal">
        <table class="table table-bordered gastos-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Tipo de gasto</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                    <th>Observación</th>
                    <th>Valor</th>
                    <th>Archivo</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($numero > 0) { ?>
                    <?php $contador = $inicio + 1; ?>
                    <?php while ($PSN1->next_record()) { ?>
                        <?php
                        $idGasto = (int) $PSN1->f('idGasto');
                        $valor = (float) $PSN1->f('valor');
                        $ext = $PSN1->f('ext');
                        $tipoDescripcion = $PSN1->f('tipoDescripcion');
                        $obs = $PSN1->f('observacion');
                        $fecha = $PSN1->f('fechaCreacion2');
                        $usuario = $PSN1->f('nombre');
                        $pathArchivo = "archivos/abn";
                        $archivoExiste = ($ext !== '' && file_exists($pathArchivo . $idGasto . "." . $ext));
                        ?>
                        <tr>
                            <td><a href="index.php?doc=gasto_editar&id=<?php echo $idGasto; ?>"><?php echo $contador; ?></a></td>
                            <td><a href="index.php?doc=gasto_editar&id=<?php echo $idGasto; ?>"><?php echo htmlspecialchars((string) $tipoDescripcion); ?></a></td>
                            <td><a href="index.php?doc=gasto_editar&id=<?php echo $idGasto; ?>"><?php echo htmlspecialchars((string) $usuario); ?></a></td>
                            <td><?php echo htmlspecialchars((string) $fecha); ?></td>
                            <td><?php echo htmlspecialchars((string) $obs); ?></td>
                            <td class="td-right">$<?php echo number_format($valor, 0, ",", "."); ?></td>
                            <td>
                                <?php if ($archivoExiste) { ?>
                                    <a href="<?php echo $pathArchivo . $idGasto . "." . $ext; ?>" target="_blank" rel="noopener">Bajar archivo</a>
                                <?php } else { ?>
                                    <span class="muted">Sin archivo</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php $contador++; ?>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="7" class="gastos-empty">Sin gastos globales reportados.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="gastos-pagination-wrap">
        <ul class="pagination">
            <?php if (($pagina - 1) > 0) { ?>
                <?php $baseParams['pagina'] = $pagina - 1; ?>
                <li><a href="index.php?<?php echo http_build_query($baseParams); ?>">&laquo;</a></li>
            <?php } ?>

            <?php for ($i = 1; $i <= $totalPaginas; $i++) { ?>
                <?php $baseParams['pagina'] = $i; ?>
                <?php if ($pagina == $i) { ?>
                    <li class="active"><a href="index.php?<?php echo http_build_query($baseParams); ?>"><?php echo $i; ?></a></li>
                <?php } else { ?>
                    <li><a href="index.php?<?php echo http_build_query($baseParams); ?>"><?php echo $i; ?></a></li>
                <?php } ?>
            <?php } ?>

            <?php if (($pagina + 1) <= $totalPaginas) { ?>
                <?php $baseParams['pagina'] = $pagina + 1; ?>
                <li><a href="index.php?<?php echo http_build_query($baseParams); ?>">&raquo;</a></li>
             <?php } ?>
         </ul>
     </div>
 </div>
