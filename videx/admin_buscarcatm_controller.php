<?php
/**
 * Admin Buscarcatm Controller
 * Business logic for category grid display
 *
 * @package VIDEX
 * @version 1.0
 */

require_once('admin_buscarcatm_service.php');

class AdminBuscarcatmController {
    private $service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->service = new AdminBuscarcatmService();
    }

    /**
     * Handle category grid display
     * @return array Categories to display or empty array if access denied
     */
    public function handleCategoryDisplay() {
        // Check permissions (allow superuser=1 or admin=2)
        if (!isset($_SESSION['perfil']) ||
            ($_SESSION['perfil'] != 1 && $_SESSION['perfil'] != 2)) {
            return [];
        }

        // Get all categories from service
        $categories = $this->service->getCategories();
        return $categories;
    }

    /**
     * Get category by ID (for future use)
     * @param int $id Category ID
     * @return array|null Category data or null
     */
    public function getCategory($id) {
        return $this->service->getCategoryById($id);
    }
}
?>
