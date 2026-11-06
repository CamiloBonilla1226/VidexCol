<?php
/**
 * Admin Buscarcatm Form View
 * View layer for rendering category grid
 *
 * @package VIDEX
 * @version 1.0
 */

/**
 * Render category grid with all available categories
 * @param array $categories Array of category objects with id, name, url
 * @return void Outputs HTML
 */
function renderCategoryGrid($categories) {
    ?>
    <div class="admin-buscarcatm-container">
        <div class="admin-buscarcatm-header">
            <h1>CATEGORÍAS DISPONIBLES</h1>
        </div>

        <div class="category-grid">
            <?php if (!empty($categories)): ?>
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
            <?php else: ?>
                <div class="empty-state">
                    <p>No hay categorías disponibles</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render single category button (optional, for future use)
 * @param array $category Category object
 * @return void Outputs HTML
 */
function renderCategoryButton($category) {
    ?>
    <a href="<?php echo htmlspecialchars($category['url']); ?>"
       class="category-btn category-btn-primary">
        <span class="btn-label"><?php echo htmlspecialchars($category['name']); ?></span>
        <span class="btn-icon">→</span>
    </a>
    <?php
}

/**
 * Render empty state message
 * @return void Outputs HTML
 */
function renderEmptyState() {
    ?>
    <div class="empty-state">
        <p>No hay categorías disponibles</p>
    </div>
    <?php
}
?>
