<?php
/**
 * Admin Buscarcatm Service
 * Service layer for category management
 *
 * @package VIDEX
 * @version 1.0
 */

require_once('admin_buscarcatm_config.php');

class AdminBuscarcatmService {

    /**
     * Get all categories
     * @return array Array of categories with id, name, and url
     */
    public function getCategories() {
        $categories = [];
        foreach (CATEGORIES as $id => $name) {
            $categories[] = [
                'id' => $id,
                'name' => $name,
                'url' => $this->getCategoryUrl($id)
            ];
        }
        return $categories;
    }

    /**
     * Get single category by ID
     * @param int $id Category ID
     * @return array|null Category data or null if not found
     */
    public function getCategoryById($id) {
        $id = intval($id);
        if (!isset(CATEGORIES[$id])) {
            return null;
        }
        return [
            'id' => $id,
            'name' => CATEGORIES[$id],
            'url' => $this->getCategoryUrl($id)
        ];
    }

    /**
     * Get category URL
     * @param int $id Category ID
     * @return string Formatted URL
     */
    public function getCategoryUrl($id) {
        return getCategoryUrl($id);
    }

    /**
     * Validate category ID
     * @param int $id Category ID
     * @return bool True if valid, false otherwise
     */
    public function validateCategory($id) {
        return isValidCategoryId($id);
    }
}
?>
