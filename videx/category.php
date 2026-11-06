<?php
/**
 * Vista para mostrar submenús y dashboard de una categoría
 * Parámetro GET: cat (ID o nombre de la categoría)
 */

$categoryAssetVersion = '20260409-2045';

$categorias = array(
	1 => array('name' => 'Administración', 'icon' => 'bi-gear', 'color' => '#2563eb'),
	2 => array('name' => 'SMS', 'icon' => 'bi-chat-dots', 'color' => '#10b981'),
	20 => array('name' => 'Emailing', 'icon' => 'bi-envelope', 'color' => '#f59e0b'),
	3 => array('name' => 'Contactos', 'icon' => 'bi-people', 'color' => '#8b5cf6'),
	4 => array('name' => 'Flota', 'icon' => 'bi-truck', 'color' => '#ef4444'),
	5 => array('name' => 'Mantenimiento', 'icon' => 'bi-wrench', 'color' => '#6366f1'),
	6 => array('name' => 'Auditoría de Proyectos', 'icon' => 'bi-clipboard2-check', 'color' => '#14b8a6'),
	99 => array('name' => 'Mi cuenta', 'icon' => 'bi-person-circle', 'color' => '#64748b'),
	'graphs' => array('name' => 'GRÁFICAS', 'icon' => 'bi-bar-chart-line', 'color' => '#3b82f6'),
	'admin' => array('name' => 'ADMIN', 'icon' => 'bi-shield-lock', 'color' => '#dc2626')
);
?>
<link rel="stylesheet" href="category.css?v=<?php echo $categoryAssetVersion; ?>">
<script>
(function () {
    function ensureCategoryClass() {
        if (document.body && !document.body.classList.contains('category-page')) {
            document.body.classList.add('category-page');
        }
    }

    ensureCategoryClass();
    document.addEventListener('DOMContentLoaded', ensureCategoryClass);
})();
</script>
<?php

function categoryBuildMenuUrl($menu)
{
    $url = 'index.php?doc=' . $menu->f('php');

    if ((int) $menu->f('opc') > 0) {
        $url .= '&opc=' . $menu->f('opc');
    }

    if ($menu->f('extra') != '') {
        $url .= '&' . $menu->f('extra');
    }

    return $url;
}

function categoryIsCurrentItemUrl($url)
{
    $currentQuery = [];
    parse_str(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '', $currentQuery);

    $targetQuery = [];
    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $targetQuery);

    if (empty($targetQuery)) {
        return false;
    }

    foreach ($targetQuery as $key => $value) {
        if (!array_key_exists($key, $currentQuery) || (string) $currentQuery[$key] !== (string) $value) {
            return false;
        }
    }

    return isset($targetQuery['doc']) ? ((string) ($currentQuery['doc'] ?? '') === (string) $targetQuery['doc']) : true;
}

// Mapeo de categorías con datos
$categorias = array(
	1 => array('name' => 'Administración', 'icon' => 'bi-gear', 'color' => '#2563eb'),
	2 => array('name' => 'SMS', 'icon' => 'bi-chat-dots', 'color' => '#10b981'),
	20 => array('name' => 'Emailing', 'icon' => 'bi-envelope', 'color' => '#f59e0b'),
	3 => array('name' => 'Contactos', 'icon' => 'bi-people', 'color' => '#8b5cf6'),
	4 => array('name' => 'Flota', 'icon' => 'bi-truck', 'color' => '#ef4444'),
	5 => array('name' => 'Mantenimiento', 'icon' => 'bi-wrench', 'color' => '#6366f1'),
	7 => array('name' => 'Gestión Proyectos', 'icon' => 'bi-kanban', 'color' => '#2563eb'),
	99 => array('name' => 'Mi cuenta', 'icon' => 'bi-person-circle', 'color' => '#64748b'),
	'graphs' => array('name' => 'GRÁFICAS', 'icon' => 'bi-bar-chart-line', 'color' => '#3b82f6'),
	'admin' => array('name' => 'ADMIN', 'icon' => 'bi-shield-lock', 'color' => '#dc2626')
);

// Obtener categoría desde parámetro
$cat = isset($_GET['cat']) ? $_GET['cat'] : null;

// Si la categoría no está en el mapa estático, intentar resolverla dinámicamente
// para no dejar ocultos módulos con principal nuevo en la tabla menu.
if ($cat !== null && !isset($categorias[$cat]) && ctype_digit((string) $cat)) {
	$PSNCat = new DBbase_Sql;
	$sqlCat = "SELECT principal FROM menu, usuarios_menu
	          WHERE menu.id = usuarios_menu.idMenu
	          AND usuarios_menu.idUsuario = " . $_SESSION["id"] . "
	          AND menu.principal = " . (int) $cat . "
	          LIMIT 1";
	$PSNCat->query($sqlCat);

	if ($PSNCat->num_rows() > 0) {
		$categorias[$cat] = array(
			'name' => 'Categoría ' . (int) $cat,
			'icon' => 'bi-grid-3x3-gap',
			'color' => '#64748b'
		);
	}
}

// Validar que la categoría existe y tiene permisos
$categoria_valida = false;
$categoria_data = null;
$submenues = array();

if ($cat && isset($categorias[$cat])) {
	$categoria_data = $categorias[$cat];
	$categoria_valida = true;

	// Obtener submenús según el tipo de categoría
	if ($cat === 'graphs' && (int) ($_SESSION["menu_graphs"] ?? 0) === 1) {
		$PSNMenu = new DBbase_Sql;
		$sqlMenu = "SELECT menu_graphs.* FROM menu_graphs, usuarios_menu_graphs
		            WHERE menu_graphs.id = usuarios_menu_graphs.idMenu
		            AND usuarios_menu_graphs.idUsuario = ".$_SESSION["id"]."
		            ORDER BY orden asc";
		$PSNMenu->query($sqlMenu);

		if ($PSNMenu->num_rows() > 0) {
			while ($PSNMenu->next_record()) {
				$submenues[] = array(
					'nombre' => $PSNMenu->f("nombre"),
					'url' => categoryBuildMenuUrl($PSNMenu),
					'icon' => 'bi-bar-chart-line'
				);
			}
		}
	} elseif ($cat === 'admin' && (int) ($_SESSION["superusuario"] ?? 0) === 1) {
		$submenues = array(
			array('nombre' => 'Saldo SMS', 'url' => 'index.php?doc=sms_saldo', 'icon' => 'bi-plus-circle'),
			array('nombre' => 'Crear Personal', 'url' => 'index.php?doc=Personal&opc=1', 'icon' => 'bi-person-plus'),
			array('nombre' => 'Consulta Personal', 'url' => 'index.php?doc=Personal&opc=2', 'icon' => 'bi-search')
		);
	} else {
		$PSNMenu = new DBbase_Sql;
		$sqlMenu = "SELECT menu.* FROM menu, usuarios_menu
		            WHERE menu.id = usuarios_menu.idMenu
		            AND usuarios_menu.idUsuario = ".$_SESSION["id"]."
		            AND menu.principal = ".$cat."
		            ORDER BY orden asc";
		$PSNMenu->query($sqlMenu);

		if ($PSNMenu->num_rows() > 0) {
			while ($PSNMenu->next_record()) {
				$submenues[] = array(
					'nombre' => $PSNMenu->f("nombre"),
					'url' => categoryBuildMenuUrl($PSNMenu),
					'icon' => 'bi-circle-fill'
				);
			}
		}
	}
}

// Gestion Proyectos (cat=7): metricas del modulo para el panel principal.
$gpMetricas = null;
if ($categoria_valida && (string) $cat === '7') {
	require_once('gp_config.php');
	require_once('gp_service.php');
	require_once('gp_ticket_service.php');
	require_once('gp_dom_service.php');

	if (gp_puede_consultar()) {
		$gpService = new GpService();
		$gpTicketService = new GpTicketService();

		$gpProyectos = $gpService->contarProyectos();
		$gpTicketsPorEstado = $gpTicketService->contarTicketsPorEstado((int) $_SESSION['id'], (int) ($_SESSION['superusuario'] ?? 0) === 1);
		$gpTicketsAbiertos = $gpTicketsPorEstado['solicitado'] + $gpTicketsPorEstado['revisado_pendiente'] + $gpTicketsPorEstado['en_ejecucion'];

		$gpMetricas = array(
			'proveedores' => $gpService->contarProveedores(),
			'servicios' => $gpService->contarServicios(),
			'proyectos_activos' => $gpProyectos['activos'],
			'proyectos_total' => $gpProyectos['total'],
			'dominios' => $gpService->contarDominios(),
			'credenciales' => $gpService->contarCredencialesTotal(),
			'tickets_abiertos' => $gpTicketsAbiertos,
			'tickets_por_estado' => $gpTicketsPorEstado,
			'dom_resumen' => (new GpDomService())->contarResumen(),
		);
	}
}

// Contactos (cat=3): agregar opcion de consultar cotizaciones en el arreglo de submenu.
// Solo si el usuario tiene permiso sobre el modulo de cotizaciones.
if ($categoria_valida && (int) $cat === 3 && videx_doc_permitido('cotizacion')) {
	$submenues[] = array(
		'nombre' => 'Consultar cotizaciones',
		'url' => 'index.php?doc=cotizacion&opc=2',
		'icon' => 'bi-search'
	);
}

// Si categoría no es válida, mostrar error
if (!$categoria_valida) {
	?>
	<div class="category-empty-state">
		<div class="category-empty-state__icon">
			<i class="bi bi-exclamation-triangle"></i>
		</div>
		<h2>Categoría no encontrada</h2>
		<p>La categoría solicitada no existe o no tienes permisos para verla.</p>
		<div class="category-empty-state__note">Vuelve al menú principal y selecciona una categoría válida.</div>
	</div>
	<?php
	return;
}
?>

<div class="category-view" data-category-root="1">
	<div class="category-hero">
		<div class="category-hero__identity">
			<div class="category-hero__icon">
				<i class="bi <?= $categoria_data['icon']; ?>"></i>
			</div>
			<div>
				<div class="category-eyebrow">Navegación principal</div>
				<h1><?= htmlspecialchars($categoria_data['name']); ?></h1>
				<p><?= count($submenues); ?> opciones disponibles.</p>
			</div>
		</div>
	</div>

	<div class="category-layout">
		<aside class="category-sidebar">
			<div class="category-sidebar__header">
				<h2>Submenú</h2>
				<p>Accesos habilitados para la categoría actual.</p>
			</div>

			<nav class="category-menu" aria-label="Submenú de <?= htmlspecialchars($categoria_data['name']); ?>">
				<?php if (count($submenues) > 0) { ?>
					<?php foreach ($submenues as $item) { ?>
						<?php $is_current = categoryIsCurrentItemUrl($item['url']); ?>
						<a class="category-menu__item <?= $is_current ? 'is-active' : ''; ?>"
						   href="<?= htmlspecialchars($item['url']); ?>"
						   title="<?= htmlspecialchars($item['nombre']); ?>">
							<span class="category-menu__icon">
								<i class="bi <?= $item['icon']; ?>"></i>
							</span>
							<span class="category-menu__label"><?= htmlspecialchars($item['nombre']); ?></span>
							<i class="bi bi-chevron-right category-menu__arrow"></i>
						</a>
					<?php } ?>
				<?php } else { ?>
					<div class="category-menu__empty">
						No hay opciones disponibles para esta categoría.
					</div>
				<?php } ?>
			</nav>
		</aside>

		<main class="category-main">
			<div class="category-main__panel">
				<div class="category-main__intro">
					<div class="category-main__hero">
						<div class="category-main__glyph">
							<i class="bi <?= $categoria_data['icon']; ?>"></i>
						</div>
						<div>
							<h2>Dashboard de <?= htmlspecialchars($categoria_data['name']); ?></h2>
							<p class="category-main__lead">Panel central preparado para mantener una experiencia limpia, clara y consistente con el resto del sistema.</p>
						</div>
					</div>

					<?php if ($gpMetricas !== null) { ?>
					<div class="category-meta-grid">
						<div class="category-meta-card">
							<span>Proveedores</span>
							<strong><?= (int) $gpMetricas['proveedores']; ?> activos</strong>
						</div>
						<div class="category-meta-card">
							<span>Servicios</span>
							<strong><?= (int) $gpMetricas['servicios']; ?> activos</strong>
						</div>
						<div class="category-meta-card">
							<span>Proyectos</span>
							<strong><?= (int) $gpMetricas['proyectos_activos']; ?> activos de <?= (int) $gpMetricas['proyectos_total']; ?></strong>
						</div>
						<div class="category-meta-card">
							<span>Dominios</span>
							<strong><?= (int) $gpMetricas['dominios']; ?> registrados</strong>
						</div>
						<div class="category-meta-card">
							<span>Inventario de dominios</span>
							<strong><?= (int) $gpMetricas['dom_resumen']['total']; ?> registrados</strong>
							<small><?= (int) $gpMetricas['dom_resumen']['por_vencer']; ?> por vencer &middot; <?= (int) $gpMetricas['dom_resumen']['vencidos']; ?> vencidos</small>
						</div>
						<div class="category-meta-card">
							<span>Credenciales</span>
							<strong><?= (int) $gpMetricas['credenciales']; ?> guardadas</strong>
						</div>
						<div class="category-meta-card">
							<span>Tickets abiertos</span>
							<strong><?= (int) $gpMetricas['tickets_abiertos']; ?></strong>
							<small style="display:block;margin-top:.35rem;color:var(--category-muted);font-size:.72rem;font-weight:600;">
								Solicitado <?= (int) $gpMetricas['tickets_por_estado']['solicitado']; ?>
								&middot; Revisión <?= (int) $gpMetricas['tickets_por_estado']['revisado_pendiente']; ?>
								&middot; Ejecución <?= (int) $gpMetricas['tickets_por_estado']['en_ejecucion']; ?>
							</small>
						</div>
						<div class="category-meta-card">
							<span>Tickets finalizados</span>
							<strong><?= (int) $gpMetricas['tickets_por_estado']['finalizado']; ?></strong>
						</div>
					</div>
					<?php } ?>
				</div>
			</div>
		</main>
	</div>
</div>
