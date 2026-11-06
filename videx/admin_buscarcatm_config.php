<?php
/**
 * Admin Buscarcatm Configuration
 * Centralized category definitions for admin_buscarcatm module
 *
 * @package VIDEX
 * @version 1.0
 */

// CATEGORY_ID => CATEGORY_NAME mappings
const CATEGORIES = [
    15 => "TIPO DE PROSPECTO/CLIENTE",
    16 => "ESTADO COTIZACION",
    17 => "ESTADO DEL PAGO",
    18 => "CATEGORÍA PRINCIPAL",
    19 => "METODO COTIZACION",
    20 => "TIPO CONTACTO",
    21 => "TIPO DE SEGUIMIENTO",
    22 => "TIPO DE ABONO",
    23 => "TIPO DE CAMPAÑA",
    24 => "TIPO DE GASTO",
    25 => "PROVEEDOR - TIPO DE SERVICIO",
    26 => "PROVEEDOR - TIPO DE CONTRATO",
    27 => "CLIENTE - TIPO DE SERVICIO",
    28 => "CLIENTE - TIPO DE CONTRATO",
    30 => "USUARIO - TIPO DE OBSERVACIÓN",
    31 => "VEHÍCULO - MARCA",
    32 => "CLIENTE - TIPO DE USUARIO",
    33 => "VEHÍCULO - CLASE",
    34 => "VEHÍCULO - TIPO DE OBSERVACIÓN",
    35 => "VEHÍCULO - CIUDAD MANTENIMIENTO",
    36 => "AUDITORIA - TIPO PERFIL",
];

/**
 * Get category name by ID
 * @param int $id Category ID
 * @return string Category name or "Unknown"
 */
function getCategoryName($id) {
    return CATEGORIES[$id] ?? "Unknown";
}

/**
 * Get formatted URL for category
 * @param int $id Category ID
 * @return string URL to admin_buscarcat.php with idcat parameter
 */
function getCategoryUrl($id) {
    return "index.php?doc=admin_buscarcat&idcat=" . intval($id);
}

/**
 * Validate if category ID exists
 * @param int $id Category ID
 * @return bool True if valid, false otherwise
 */
function isValidCategoryId($id) {
    return isset(CATEGORIES[intval($id)]);
}
?>
