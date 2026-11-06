<?php
$mailConfigAssetVersion = '20260409-2215';
?>
<link rel="stylesheet" href="mail_config.css?v=<?php echo $mailConfigAssetVersion; ?>">
<script>
(function () {
    function ensureMailConfigClass() {
        if (document.body && !document.body.classList.contains('mail-config-page')) {
            document.body.classList.add('mail-config-page');
        }
    }

    ensureMailConfigClass();
    document.addEventListener('DOMContentLoaded', ensureMailConfigClass);
})();
</script>
<?php

if ((int) ($_SESSION['perfil'] ?? 0) !== 1 && (int) ($_SESSION['perfil'] ?? 0) !== 2 && (int) ($_SESSION['perfil'] ?? 0) !== 161 && (int) ($_SESSION['perfil'] ?? 0) !== 162 && (int) ($_SESSION['perfil'] ?? 0) !== 163) {
    die('<h1>No esta autorizado para ver esta información</h1>');
}

$opc = isset($_GET['opc']) ? (int) eliminarInvalidos($_GET['opc']) : 2;
$tablaConsulta = 'mail_config';
$webArchivo = 'mail_config';
$nombreConsulta = 'CONFIGURACIÓN DE CORREO';
$db = new DBbase_Sql;

function mailConfigBodyValues(): array
{
    return [
        'nombre' => eliminarInvalidos($_POST['nombre'] ?? ''),
        'correo' => eliminarInvalidos($_POST['correo'] ?? ''),
        'host' => eliminarInvalidos($_POST['host'] ?? ''),
        'puerto' => soloNumeros($_POST['puerto'] ?? ''),
        'usuario' => eliminarInvalidos($_POST['usuario'] ?? ''),
        'password' => eliminarInvalidos($_POST['password'] ?? ''),
        'encriptacion' => eliminarInvalidos($_POST['encriptacion'] ?? 'ssl'),
        'defecto' => (isset($_POST['defecto']) && soloNumeros($_POST['defecto']) == 1) ? 1 : 0,
    ];
}

function mailConfigRenderShellStart(string $title, string $subtitle): void
{
    ?>
    <div class="mail-config-shell">
        <div class="mail-config-hero">
            <p class="mail-config-eyebrow">Configuración SMTP</p>
            <h2><?php echo htmlspecialchars($title); ?></h2>
            <p class="mail-config-subtitle"><?php echo htmlspecialchars($subtitle); ?></p>
        </div>
    <?php
}

function mailConfigRenderShellEnd(): void
{
    ?>
    </div>
    <?php
}

function mailConfigRenderHelpBlock(): void
{
    ?>
    <div class="mail-config-help">
        <strong><i class="bi bi-shield-lock"></i> ¿Usa verificación en 2 pasos (2FA)?</strong>
        <p>No ingreses tu contraseña normal. Debes crear una Contraseña de Aplicación.</p>
        <ol>
            <li>Ve a myaccount.google.com → Seguridad → Contraseñas de aplicaciones</li>
            <li>Selecciona “Otra aplicación”, escribe VIDEX y genera la clave</li>
            <li>Pega la clave de 16 caracteres en el campo Password</li>
        </ol>
        <span>Para Outlook con 2FA: busca “Contraseñas de aplicación” en tu cuenta Microsoft.</span>
    </div>
    <?php
}

function mailConfigRenderPasswordField(string $value): void
{
    ?>
    <div class="form-group mail-config-password-field">
        <label class="form-label" for="password">Password</label>
        <div class="mail-config-password-input-wrap">
            <input name="password" id="password" type="password" maxlength="255" value="<?php echo htmlspecialchars($value); ?>" class="form-control mail-config-password-input" required />
            <button type="button" class="mail-config-password-toggle" data-target="password" aria-pressed="false" aria-label="Ver contraseña">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>
    <?php
}

function mailConfigRenderActions(bool $isEdit): void
{
    ?>
    <div class="mail-config-actions">
        <button type="button" class="btn btn-primary" onclick="document.getElementById('exampleModal').classList.add('show')">Guardar Cambios</button>
        <?php if ($isEdit): ?>
            <input type="button" class="btn btn-danger" onclick="generarFormDel()" value="Eliminar Registro" />
        <?php endif; ?>
        <a href="index.php?doc=main" class="btn btn-danger">Cerrar</a>
    </div>
    <?php
}

if ($opc === 1) {
    if (isset($_POST['nombre'])) {
        $exists = new DBbase_Sql;
        $sql = 'SELECT id FROM ' . $tablaConsulta . ' WHERE usuario = "' . eliminarInvalidos($_REQUEST['usuario'] ?? '') . '"';
        $exists->query($sql);
        if ($exists->num_rows() > 0 && $exists->next_record()) {
            ?>
            <script>
            alert('YA EXISTE ESE USUARIO DE CORREO EN EL SISTEMA, SERÁ DIRIGIDO AL MISMO');
            window.location.href = <?php echo json_encode('index.php?doc=' . $webArchivo . '&opc=2&id=' . $exists->f('id')); ?>;
            </script>
            <?php
            return;
        }

        $values = mailConfigBodyValues();
        $creacionUsuario = (int) ($_SESSION['id'] ?? 0);
        $encriptacion = in_array($values['encriptacion'], ['ssl', 'tls'], true) ? $values['encriptacion'] : (($values['puerto'] == 587) ? 'tls' : 'ssl');

        $sql = 'INSERT INTO ' . $tablaConsulta . ' (nombre, host, usuario, password, puerto, encriptacion, correo, defecto, audit_usuario, audit_fecha) VALUES ('
            . '"' . $values['nombre'] . '", '
            . '"' . $values['host'] . '", '
            . '"' . $values['usuario'] . '", '
            . '"' . $values['password'] . '", '
            . '"' . $values['puerto'] . '", '
            . '"' . $encriptacion . '", '
            . '"' . $values['correo'] . '", '
            . '"' . $values['defecto'] . '", '
            . '"' . $creacionUsuario . '", '
            . 'NOW()'
            . ')';
        $db->query($sql);
        $ultimoId = $db->ultimoId();
        if ($values['defecto'] == 1 && $ultimoId > 0) {
            $db->query('UPDATE ' . $tablaConsulta . ' SET defecto = 0 WHERE id != ' . $ultimoId);
        }
        ?>
        <script>
        alert('Se ha creado correctamente el registro.');
        window.location.href = <?php echo json_encode('index.php?doc=' . $webArchivo . '&opc=2&id=' . $ultimoId); ?>;
        </script>
        <?php
        return;
    }

    $values = mailConfigBodyValues();
    mailConfigRenderShellStart('.CREACIÓN DE CONFIGURACIÓN DE CORREO.', 'Todos los campos quedan dentro del contenido principal y adaptados a responsive.');
    ?>
    <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="mail-config-form">
        <input type="hidden" name="funcion" id="funcion" value="" />

        <div class="mail-config-section">
            <h3>Datos principales</h3>
            <div class="mail-config-grid-2">
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input name="nombre" id="nombre" type="text" maxlength="250" value="<?php echo htmlspecialchars($values['nombre']); ?>" class="form-control" required />
                </div>
                <div class="form-group">
                    <label class="form-label" for="correo">Correo</label>
                    <input name="correo" id="correo" type="email" maxlength="100" value="<?php echo htmlspecialchars($values['correo']); ?>" class="form-control" required />
                </div>
                <div class="form-group">
                    <label class="form-label" for="host">Host</label>
                    <input name="host" id="host" type="text" maxlength="50" value="<?php echo htmlspecialchars($values['host']); ?>" class="form-control" required />
                </div>
                <div class="form-group">
                    <label class="form-label" for="puerto">Puerto</label>
                    <input name="puerto" id="puerto" type="number" maxlength="10" value="<?php echo htmlspecialchars((string) $values['puerto']); ?>" class="form-control" required />
                </div>
                <div class="form-group">
                    <label class="form-label" for="usuario">Usuario</label>
                    <input name="usuario" id="usuario" type="text" maxlength="100" value="<?php echo htmlspecialchars($values['usuario']); ?>" class="form-control" required />
                </div>
                <?php mailConfigRenderPasswordField($values['password']); ?>
            </div>
        </div>

        <div class="mail-config-section">
            <h3>Seguridad</h3>
            <div class="mail-config-grid-2">
                <div class="form-group">
                    <label class="form-label" for="encriptacion">Encriptación</label>
                    <select name="encriptacion" id="encriptacion" class="form-control" required>
                        <option value="ssl" <?php echo ($values['encriptacion'] === 'ssl') ? 'selected="selected"' : ''; ?>>SSL (puerto 465)</option>
                        <option value="tls" <?php echo ($values['encriptacion'] === 'tls') ? 'selected="selected"' : ''; ?>>TLS / STARTTLS (puerto 587)</option>
                    </select>
                </div>
                <?php mailConfigRenderHelpBlock(); ?>
            </div>
        </div>

        <div class="mail-config-section">
            <h3>Preferencias</h3>
            <div class="mail-config-grid-1">
                <div class="mail-config-checkbox">
                    <input type="checkbox" name="defecto" id="defecto" value="1" <?php echo ($values['defecto'] == 1) ? 'checked' : ''; ?> />
                    <label for="defecto">Usar esta cuenta por defecto</label>
                </div>
            </div>
        </div>

        <?php mailConfigRenderActions(false); ?>

        <div class="modal" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Confirmación</h5>
                        <button type="button" class="modal-close" aria-label="Close" onclick="document.getElementById('exampleModal').classList.remove('show')"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">¿Está seguro de que desea guardar los datos?</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').classList.remove('show')">Cerrar</button>
                        <input type="submit" value="Sí" class="btn btn-success" />
                    </div>
                </div>
            </div>
        </div>
    </form>
    <script>
    function generarForm(){
        if (!confirm('Esta accion generara el REGISTRO en el sistema, esta seguro que desea continuar?')) {
            return false;
        }
        if (
            document.getElementById('nombre').value !== '' &&
            document.getElementById('correo').value !== '' &&
            document.getElementById('host').value !== '' &&
            document.getElementById('puerto').value !== '' &&
            document.getElementById('usuario').value !== '' &&
            document.getElementById('password').value !== ''
        ) {
            document.getElementById('funcion').value = 'insertar';
            return true;
        }
        alert('La información es primordial para brindarle un excelente servicio, por favor digite todos los campos');
        return false;
    }
    document.getElementById('form1').onsubmit = function(){ return generarForm(); };
    document.querySelectorAll('.mail-config-password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if (!input) { return; }
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
            this.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            this.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Ver contraseña');
        });
    });
    document.getElementById('puerto').addEventListener('input', function() {
        var enc = document.getElementById('encriptacion');
        if (this.value === '587') { enc.value = 'tls'; }
        else if (this.value === '465') { enc.value = 'ssl'; }
    });
    </script>
    <?php
    mailConfigRenderShellEnd();
    return;
}

if (isset($_GET['id'])) {
    $id = soloNumeros($_GET['id']);
    if (isset($_POST['nombre'])) {
        $values = mailConfigBodyValues();
        $encriptacion = in_array($values['encriptacion'], ['ssl', 'tls'], true) ? $values['encriptacion'] : (($values['puerto'] == 587) ? 'tls' : 'ssl');

        if (($_POST['funcion'] ?? '') === 'actualizar') {
            $sql = 'UPDATE ' . $tablaConsulta . ' SET '
                . 'nombre = "' . $values['nombre'] . '", '
                . 'host = "' . $values['host'] . '", '
                . 'usuario = "' . $values['usuario'] . '", '
                . 'password = "' . $values['password'] . '", '
                . 'puerto = "' . $values['puerto'] . '", '
                . 'encriptacion = "' . $encriptacion . '", '
                . 'correo = "' . $values['correo'] . '", '
                . 'defecto = "' . $values['defecto'] . '", '
                . 'audit_usuario = "' . (int) ($_SESSION['id'] ?? 0) . '", '
                . 'audit_fecha = NOW() '
                . 'WHERE id = ' . $id;
            $db->query($sql);
            if ($values['defecto'] == 1) {
                $db->query('UPDATE ' . $tablaConsulta . ' SET defecto = 0 WHERE id != ' . $id);
            }
            ?>
            <script>
            alert('Se ha ACTUALIZADO correctamente el registro!');
            window.location.href = <?php echo json_encode('index.php?doc=' . $webArchivo . '&opc=2&id=' . $id); ?>;
            </script>
            <?php
            return;
        }

        if (($_POST['funcion'] ?? '') === 'eliminar') {
            $db->query('DELETE FROM ' . $tablaConsulta . ' WHERE id = ' . $id);
            ?>
            <script>
            alert('Se ha ELIMINADO correctamente el registro!');
            window.location.href = <?php echo json_encode('index.php?doc=' . $webArchivo . '&opc=2'); ?>;
            </script>
            <?php
            return;
        }
    }

    $db->query('SELECT * FROM ' . $tablaConsulta . ' WHERE id = ' . $id);
    if ($db->num_rows() <= 0 || !$db->next_record()) {
        echo '<div class="alert alert-warning">Registro no encontrado.</div>';
        return;
    }

    mailConfigRenderShellStart('.ACTUALIZACIÓN DE CONFIGURACIÓN DE CORREO.', 'Edición de cuenta SMTP dentro del contenido principal, sin bloques sueltos.');
    ?>
    <form method="post" enctype="multipart/form-data" name="form2" id="form2" class="mail-config-form">
        <input type="hidden" name="funcion" id="funcion" value="actualizar" />

        <div class="mail-config-section">
            <h3>Datos principales</h3>
            <div class="mail-config-grid-2">
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input name="nombre" id="nombre" type="text" maxlength="250" value="<?php echo htmlspecialchars((string) $db->f('nombre')); ?>" class="form-control" required />
                </div>
                <div class="form-group">
                    <label class="form-label" for="correo">Correo</label>
                    <input name="correo" id="correo" type="email" maxlength="100" value="<?php echo htmlspecialchars((string) $db->f('correo')); ?>" class="form-control" required />
                </div>
                <div class="form-group">
                    <label class="form-label" for="host">Host</label>
                    <input name="host" id="host" type="text" maxlength="50" value="<?php echo htmlspecialchars((string) $db->f('host')); ?>" class="form-control" required />
                </div>
                <div class="form-group">
                    <label class="form-label" for="puerto">Puerto</label>
                    <input name="puerto" id="puerto" type="number" maxlength="10" value="<?php echo htmlspecialchars((string) $db->f('puerto')); ?>" class="form-control" required />
                </div>
                <div class="form-group">
                    <label class="form-label" for="usuario">Usuario</label>
                    <input name="usuario" id="usuario" type="text" maxlength="100" value="<?php echo htmlspecialchars((string) $db->f('usuario')); ?>" class="form-control" required />
                </div>
                <?php mailConfigRenderPasswordField((string) $db->f('password')); ?>
            </div>
        </div>

        <div class="mail-config-section">
            <h3>Seguridad</h3>
            <div class="mail-config-grid-2">
                <div class="form-group">
                    <label class="form-label" for="encriptacion">Encriptación</label>
                    <select name="encriptacion" id="encriptacion" class="form-control" required>
                        <option value="ssl" <?php echo ((string) $db->f('encriptacion') === 'ssl') ? 'selected="selected"' : ''; ?>>SSL (puerto 465)</option>
                        <option value="tls" <?php echo ((string) $db->f('encriptacion') === 'tls') ? 'selected="selected"' : ''; ?>>TLS / STARTTLS (puerto 587)</option>
                    </select>
                </div>
                <?php mailConfigRenderHelpBlock(); ?>
            </div>
        </div>

        <div class="mail-config-section">
            <h3>Preferencias</h3>
            <div class="mail-config-grid-1">
                <div class="mail-config-checkbox">
                    <input type="checkbox" name="defecto" id="defecto" value="1" <?php echo ((int) $db->f('defecto') === 1) ? 'checked' : ''; ?> />
                    <label for="defecto">Usar esta cuenta por defecto</label>
                </div>
            </div>
        </div>

        <?php mailConfigRenderActions(true); ?>

        <div class="modal" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Confirmación</h5>
                        <button type="button" class="modal-close" aria-label="Close" onclick="document.getElementById('exampleModal').classList.remove('show')"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">¿Está seguro de que desea guardar los cambios?</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').classList.remove('show')">Cerrar</button>
                        <input type="submit" value="Sí" class="btn btn-success" />
                    </div>
                </div>
            </div>
        </div>
    </form>
    <script>
    function generarForm(){
        if (!confirm('Esta accion actualizara el REGISTRO en el sistema, esta seguro que desea continuar?')) {
            return false;
        }
        if (
            document.getElementById('nombre').value !== '' &&
            document.getElementById('host').value !== '' &&
            document.getElementById('usuario').value !== '' &&
            document.getElementById('password').value !== '' &&
            document.getElementById('puerto').value !== '' &&
            document.getElementById('correo').value !== ''
        ) {
            document.getElementById('funcion').value = 'actualizar';
            return true;
        }
        alert('La informacion es primordial para brindarle un excelente servicio, por favor digite todos los campos de configuración');
        return false;
    }

    function generarFormDel(){
        if (!confirm('Esta accion ELIMINARA el REGISTRO en el sistema, esta seguro que desea continuar?')) {
            return;
        }
        document.getElementById('funcion').value = 'eliminar';
        document.getElementById('form2').submit();
    }

    document.getElementById('form2').onsubmit = function(){ return generarForm(); };
    document.querySelectorAll('.mail-config-password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if (!input) { return; }
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
            this.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            this.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Ver contraseña');
        });
    });
    document.getElementById('puerto').addEventListener('input', function() {
        var enc = document.getElementById('encriptacion');
        if (this.value === '587') { enc.value = 'tls'; }
        else if (this.value === '465') { enc.value = 'ssl'; }
    });
    </script>
    <?php
    mailConfigRenderShellEnd();
    return;
}

$registros = 50;
$pagina = (int) soloNumeros($_GET['pagina'] ?? 1);
if ($pagina < 1) {
    $pagina = 1;
}
$inicio = ($pagina - 1) * $registros;

$nombreFiltro = eliminarInvalidos($_GET['nombre'] ?? '');
$sqlFiltro = ' WHERE 1 ';
if ($nombreFiltro !== '') {
    $sqlFiltro .= ' AND ' . $tablaConsulta . '.nombre LIKE "%' . $nombreFiltro . '%"';
}

$countSql = 'SELECT COUNT(id) AS conteo FROM ' . $tablaConsulta . $sqlFiltro;
$db->query($countSql);
$totalRegistros = 0;
if ($db->next_record()) {
    $totalRegistros = (int) $db->f('conteo');
}
$totalPaginas = max(1, (int) ceil($totalRegistros / $registros));
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $inicio = ($pagina - 1) * $registros;
}

$listSql = 'SELECT * FROM ' . $tablaConsulta . $sqlFiltro . ' ORDER BY ' . $tablaConsulta . '.nombre ASC LIMIT ' . $inicio . ', ' . $registros;
$db->query($listSql);
$num = $db->num_rows();

mailConfigRenderShellStart($nombreConsulta, 'Listado de configuraciones de correo con diseño limpio y responsive.');
?>
<div class="mail-config-section">
    <h3>Filtros de búsqueda</h3>
    <form action="index.php" name="form" id="form" method="get" class="mail-config-form">
        <input type="hidden" name="doc" value="<?php echo htmlspecialchars($webArchivo); ?>" />
        <input type="hidden" name="opc" value="2" />
        <div class="mail-config-grid-1">
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre</label>
                <input type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($nombreFiltro); ?>" class="form-control" />
            </div>
        </div>
        <div class="mail-config-actions">
            <input type="submit" value="Buscar" class="btn btn-success" />
        </div>
    </form>
</div>

<div class="mail-config-section">
    <h3>Resultados</h3>
    <div class="mail-config-summary"><strong><?php echo (int) $totalRegistros; ?></strong> registros encontrados.</div>
    <div class="mail-config-table-wrap">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nombre</th>
                    <th>Host</th>
                    <th>Correo</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($num > 0) { ?>
                    <?php $contador = $inicio + 1; ?>
                    <?php while ($db->next_record()) { ?>
                        <tr>
                            <td><a href="index.php?doc=<?php echo htmlspecialchars($webArchivo); ?>&opc=2&id=<?php echo (int) $db->f('id'); ?>"><?php echo $contador; ?></a></td>
                            <td><a href="index.php?doc=<?php echo htmlspecialchars($webArchivo); ?>&opc=2&id=<?php echo (int) $db->f('id'); ?>"><?php echo htmlspecialchars((string) $db->f('nombre')); ?></a></td>
                            <td><?php echo htmlspecialchars((string) $db->f('host')); ?></td>
                            <td><?php echo htmlspecialchars((string) $db->f('correo')); ?></td>
                        </tr>
                        <?php $contador++; ?>
                    <?php } ?>
                <?php } else { ?>
                    <tr><td colspan="4" class="mail-config-empty">No hay configuraciones registradas.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mail-config-pagination-wrap">
    <ul class="pagination">
        <?php if (($pagina - 1) > 0) { ?>
            <li><a href="index.php?doc=<?php echo urlencode($webArchivo); ?>&opc=2&nombre=<?php echo urlencode($nombreFiltro); ?>&pagina=<?php echo $pagina - 1; ?>">&laquo;</a></li>
        <?php } ?>
        <?php for ($i = 1; $i <= $totalPaginas; $i++) { ?>
            <li class="<?php echo ($pagina === $i) ? 'active' : ''; ?>"><a href="index.php?doc=<?php echo urlencode($webArchivo); ?>&opc=2&nombre=<?php echo urlencode($nombreFiltro); ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a></li>
        <?php } ?>
        <?php if (($pagina + 1) <= $totalPaginas) { ?>
            <li><a href="index.php?doc=<?php echo urlencode($webArchivo); ?>&opc=2&nombre=<?php echo urlencode($nombreFiltro); ?>&pagina=<?php echo $pagina + 1; ?>">&raquo;</a></li>
        <?php } ?>
    </ul>
</div>
<?php
mailConfigRenderShellEnd();
?>