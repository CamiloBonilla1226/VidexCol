<?php
/**
 * Admin Buscarcatm
 * Category management interface - wrapper using modular architecture
 *
 * Original: 132 lines (monolithic)
 * Refactored: 40 lines (modular)
 *
 * @package VIDEX
 * @version 2.0
 */

session_start();
include_once('funciones.php');

// Permission check: Allow only superuser (perfil=1) and admin (perfil=2)
// Block if: not logged in OR not admin/superuser
if (!isset($_SESSION["id"]) ||
    $_SESSION["id"] == "" ||
    !isset($_SESSION["perfil"]) ||
    $_SESSION["perfil"] == "" ||
    ($_SESSION["perfil"] != 1 && $_SESSION["perfil"] != 2)) {
    header("Location: index.php?salir=");
    exit;
}

// Import modular layers
require_once('admin_buscarcatm_config.php');
require_once('admin_buscarcatm_service.php');
require_once('admin_buscarcatm_controller.php');
require_once('admin_buscarcatm_form.php');

// Initialize controller and get categories
$controller = new AdminBuscarcatmController();
$categories = $controller->handleCategoryDisplay();

// Render view
if (!empty($categories)): ?>
    <div class="admin-buscarcatm-container">
        <div class="admin-buscarcatm-header">
            <h1>CATEGORÍAS DISPONIBLES</h1>
        </div>

        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <div class="category-button-wrapper">
                    <a href="<?php echo htmlspecialchars($cat['url']); ?>"
                       class="category-btn category-btn-primary"
                       title="Gestionar <?php echo htmlspecialchars($cat['name']); ?>">
                        <span class="btn-label"><?php echo htmlspecialchars($cat['name']); ?></span>
                        <span class="btn-icon">→</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="admin-buscarcatm-container">
        <div class="empty-state">
            <p>No hay categorías disponibles</p>
        </div>
    </div>
<?php endif; ?>

