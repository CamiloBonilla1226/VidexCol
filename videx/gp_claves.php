<?php
/**
 * Claves (Inventario): registro cifrado de claves personales (tarjetas debito,
 * correos, aplicaciones...), aparte de los proveedores/servicios/proyectos.
 * Solo con el permiso especial gp_claves. Cada usuario ve unicamente las suyas.
 */
require_once('gp_claves_config.php');
require_once('gp_claves_service.php');
gp_claves_requerir_acceso();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$service = new GpClaveService();
$idUsuario = (int) $_SESSION['id'];
$buscar = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$categoriaFiltro = isset($_GET['categoria']) ? trim((string) $_GET['categoria']) : '';

$claves = $service->listar($idUsuario, $buscar, $categoriaFiltro);
$categoriasUsadas = $service->listarCategorias($idUsuario);
$categoriasSugeridas = array_values(array_unique(array_merge(GP_CLAVES_CATEGORIAS_SUGERIDAS, $categoriasUsadas)));
?>
<link rel="stylesheet" href="gp.css?v=<?= @filemtime('gp.css'); ?>" />
<link rel="stylesheet" href="gp_claves.css?v=<?= @filemtime('gp_claves.css'); ?>" />

<div class="gp-wrap" id="gpcApp" data-csrf="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>" data-reauth-min="<?= (int) (GP_CLAVES_REAUTH_SEGUNDOS / 60); ?>">
    <div class="gp-page-header">
        <div>
            <h1>Claves</h1>
            <p class="gp-page-header-sub"><i class="bi bi-shield-lock"></i> Registro personal y cifrado de claves (tarjetas, correos, aplicaciones). Solo usted ve las suyas y debe confirmar su contraseña para verlas.</p>
        </div>
    </div>

    <div class="gp-toolbar">
        <form method="get" class="gp-search">
            <input type="hidden" name="doc" value="gp_claves" />
            <i class="bi bi-search"></i>
            <input type="text" name="q" placeholder="Buscar por título, categoría o sitio..." value="<?= htmlspecialchars($buscar, ENT_QUOTES); ?>" onchange="this.form.submit()" />
            <?php if ($categoriaFiltro !== '') { ?><input type="hidden" name="categoria" value="<?= htmlspecialchars($categoriaFiltro, ENT_QUOTES); ?>" /><?php } ?>
        </form>
        <?php if (!empty($categoriasUsadas)) { ?>
            <form method="get" class="gpc-filtro">
                <input type="hidden" name="doc" value="gp_claves" />
                <?php if ($buscar !== '') { ?><input type="hidden" name="q" value="<?= htmlspecialchars($buscar, ENT_QUOTES); ?>" /><?php } ?>
                <select name="categoria" class="form-control" onchange="this.form.submit()">
                    <option value="">Categoría: Todas</option>
                    <?php foreach ($categoriasUsadas as $c) { ?>
                        <option value="<?= htmlspecialchars($c, ENT_QUOTES); ?>" <?= $categoriaFiltro === $c ? 'selected' : ''; ?>><?= htmlspecialchars($c, ENT_QUOTES); ?></option>
                    <?php } ?>
                </select>
            </form>
        <?php } ?>
        <button type="button" class="gp-btn-primary" id="gpcBtnNueva"><i class="bi bi-plus-lg"></i> Agregar clave</button>
    </div>

    <div class="gp-card">
        <?php if (empty($claves)) { ?>
            <div class="gp-table-empty">
                <i class="bi bi-key"></i>
                <p><?= ($buscar !== '' || $categoriaFiltro !== '') ? 'No hay claves que coincidan con el filtro.' : 'Aún no ha registrado claves.'; ?></p>
            </div>
        <?php } else { ?>
            <table class="gp-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Sitio</th>
                        <th>Datos guardados</th>
                        <th>Actualizada</th>
                        <th class="gp-th-derecha">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($claves as $c) { ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['titulo'], ENT_QUOTES); ?></strong></td>
                            <td><?= $c['categoria'] !== '' ? '<span class="gp-badge gp-badge-gris">' . htmlspecialchars($c['categoria'], ENT_QUOTES) . '</span>' : '<span class="gpc-vacio">—</span>'; ?></td>
                            <td class="gp-mono"><?= $c['sitio'] !== '' ? htmlspecialchars($c['sitio'], ENT_QUOTES) : '<span class="gpc-vacio">—</span>'; ?></td>
                            <td class="gpc-datos">
                                <?php if ($c['tiene_usuario']) { ?><i class="bi bi-person" title="Usuario / número"></i><?php } ?>
                                <?php if ($c['tiene_clave']) { ?><i class="bi bi-key" title="Clave"></i><?php } ?>
                                <?php if ($c['tiene_notas']) { ?><i class="bi bi-journal-text" title="Notas"></i><?php } ?>
                                <span class="gpc-cifrado"><i class="bi bi-lock-fill"></i> cifrado</span>
                            </td>
                            <td><?= date('d M Y', strtotime($c['modificacion_fecha'] ?: $c['creacion_fecha'])); ?></td>
                            <td class="gp-col-accion">
                                <button type="button" class="gp-icon-btn gpc-btn-ver" data-id="<?= (int) $c['id']; ?>" title="Ver (requiere su contraseña)"><i class="bi bi-eye"></i></button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="gp-card-footer">Mostrando <?= count($claves); ?> clave<?= count($claves) == 1 ? '' : 's'; ?></div>
        <?php } ?>
    </div>
</div>

<datalist id="gpcCategorias">
    <?php foreach ($categoriasSugeridas as $c) { ?><option value="<?= htmlspecialchars($c, ENT_QUOTES); ?>"><?php } ?>
</datalist>

<script src="js/gp_claves.js?v=<?= @filemtime('js/gp_claves.js'); ?>"></script>
