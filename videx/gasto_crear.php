<?php
$gastoCrearAssetVersion = '20260409-2110';
?>
<link rel="stylesheet" href="gasto_crear.css?v=<?php echo $gastoCrearAssetVersion; ?>">
<script>
(function () {
    function ensureGastoCrearClass() {
        if (document.body && !document.body.classList.contains('gasto-crear-page')) {
            document.body.classList.add('gasto-crear-page');
        }
    }

    ensureGastoCrearClass();
    document.addEventListener('DOMContentLoaded', ensureGastoCrearClass);
})();
</script>
<?php

// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;
$varExito = 0;
$ultimoId = 0;
$mensajeError = '';

$sql = "SELECT idMenu ";
$sql .= " FROM usuarios_menu ";
$sql .= " WHERE idMenu = 47 AND idUsuario = '" . $_SESSION["id"] . "'";
$PSN1->query($sql);
if ($PSN1->num_rows() == 0) {
    die("NO esta autorizado a ver esta opción.");
}

if ($_SESSION["acceso_solo_lectura"] == 1) {
    ?>
    <div class="gasto-crear-shell">
        <h5 class="alert alert-danger text-center"><strong>Atención:</strong> Su usuario es de solo lectura, no puede realizar modificaciones.</h5>
    </div>
    <?php
    exit;
}

if (isset($_POST["funcion"])) {
    if ($_SESSION["acceso_solo_lectura"] == 1) {
        ?>
        <div class="gasto-crear-shell">
            <h5 class="alert alert-danger text-center"><strong>Atención:</strong> Su usuario es de solo lectura, no puede realizar modificaciones.</h5>
        </div>
        <?php
        exit;
    }

    $tipo = soloNumeros($_POST["tipo"] ?? '');
    $valor = soloNumeros($_POST["valor"] ?? '');
    $fechaCreacion = eliminarInvalidos($_POST["fechaCreacion"] ?? date("Y-m-d"));
    $observacion = eliminarInvalidos($_POST["observacion"] ?? '');

    if ($fechaCreacion === '') {
        $fechaCreacion = date("Y-m-d");
    }

    if ($_POST["funcion"] == "insertarAbono" && $valor != "" && $tipo != "" && $tipo != "0") {
        $nombre_archivo = $_FILES['archivo']['name'] ?? '';
        $ext = extension_archivo($nombre_archivo);

        $sql = 'insert into gastos (
                    idCotizacion,
                    idUsuario,
                    tipo,
                    valor,
                    observacion,
                    fechaCreacion,
                    fechaAuditoria,
                    ext
                ) ';
        $sql .= 'values (
                    0,
                    ' . $_SESSION["id"] . ',
                    "' . $tipo . '",
                    "' . $valor . '",
                    "' . $observacion . '",
                    "' . $fechaCreacion . '",
                    NOW(),
                    "' . $ext . '"
                )';

        $PSN1->query($sql);
        $ultimoId = $PSN1->ultimoId();
        $varExito = 1;

        if (!empty($_FILES['archivo']['tmp_name'])) {
            move_uploaded_file($_FILES['archivo']['tmp_name'], "archivos/abn" . $ultimoId . "." . $ext);
        }
    } else {
        $mensajeError = 'Debe especificar por lo menos el valor y el tipo de gasto.';
    }
}

if ($varExito != 1) {
    $tipoSeleccionado = soloNumeros($_POST["tipo"] ?? '');
    $fechaForm = eliminarInvalidos($_POST["fechaCreacion"] ?? '');
    if ($fechaForm === '') {
        $fechaForm = date("Y-m-d");
    }
    $observacionForm = eliminarInvalidos($_POST["observacion"] ?? '');
    ?>
    <div class="gasto-crear-shell">
        <div class="gasto-crear-header">
            <h2>Insertar gasto global</h2>
            <p>Registro unificado en modo claro para control de gastos.</p>
        </div>

        <?php if ($mensajeError !== '') { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($mensajeError); ?></div>
        <?php } ?>

        <form method="post" enctype="multipart/form-data" name="form1" id="form1" action="index.php?doc=gasto_crear" class="gasto-crear-form">
            <div class="gasto-crear-card">
                <h3>Información principal</h3>
                <div class="gasto-crear-grid">
                    <div class="form-group">
                        <label class="form-label" for="tipo">Tipo</label>
                        <select name="tipo" id="tipo" class="form-control">
                            <option value="">Seleccione una opción</option>
                            <?php
                            $sql = "SELECT * ";
                            $sql .= " FROM categorias ";
                            $sql .= " WHERE idSec = 24 ORDER BY descripcion asc";

                            $PSN1->query($sql);
                            if ($PSN1->num_rows() > 0) {
                                while ($PSN1->next_record()) {
                                    ?>
                                    <option value="<?php echo $PSN1->f('id'); ?>" <?php echo ((string) $tipoSeleccionado === (string) $PSN1->f('id')) ? 'selected="selected"' : ''; ?>>
                                        <?php echo $PSN1->f('descripcion'); ?>
                                    </option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fechaCreacion">Fecha del gasto</label>
                        <input name="fechaCreacion" id="fechaCreacion" type="date" placeholder="AAAA-MM-DD" value="<?php echo htmlspecialchars($fechaForm); ?>" class="form-control" />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="valor">Valor del gasto</label>
                        <input type="number" name="valor" id="valor" value="<?php echo htmlspecialchars(soloNumeros($_POST['valor'] ?? '')); ?>" class="form-control" />
                    </div>
                </div>
            </div>

            <div class="gasto-crear-card">
                <h3>Soporte y detalle</h3>
                <div class="form-group">
                    <label class="form-label" for="observacion">Observación</label>
                    <textarea name="observacion" id="observacion" cols="80" rows="5" class="form-control"><?php echo htmlspecialchars($observacionForm); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="archivo">Archivo anexo</label>
                    <input name="archivo" type="file" id="archivo" class="form-control" />
                </div>
            </div>

            <div class="gasto-crear-actions">
                <input type="hidden" name="funcion" id="funcion" value="" />
                <input type="submit" value="Guardar gasto" class="btn btn-success" />
                <a href="index.php?doc=gastos" class="btn btn-default">Volver al listado</a>
            </div>
        </form>
    </div>

    <script>
    function generarForm() {
        if (!confirm("Esta accion insertara un gasto global. Esta seguro que desea continuar?")) {
            return false;
        }

        if (document.getElementById('valor').value !== "") {
            document.getElementById('funcion').value = "insertarAbono";
            return true;
        }

        alert("Debe escribir el valor del gasto.");
        return false;
    }

    document.getElementById('form1').onsubmit = function () {
        return generarForm();
    };
    </script>
    <?php
} else {
    ?>
    <script>
    window.onload = function () {
        window.location.href = 'index.php?doc=gasto_editar&id=<?php echo (int) $ultimoId; ?>';
    };
    </script>
    <?php
}
?>
