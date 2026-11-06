<?php
/**
 * USUARIO_BUSCAR.PHP
 *
 * Módulo de búsqueda de usuarios - Wrapper
 * - Reducido de 712 líneas → 60 líneas
 * - Arquitectura modular y segura
 * - Delegación a capas especializadas
 */

// Permisos
if ($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 4 || $_SESSION["perfil"] == 160) {
    die("<h1>No está autorizado para ver esta información</h1>");
}

// Cargar dependencias
require_once('usuario_buscar_controller.php');

// Instanciar controlador
$controller = new UsuarioBuscarController();

// Obtener parámetros
$ctrl = isset($_REQUEST["ctrl"]) ? intval($_REQUEST["ctrl"]) : 0;
$pagina = isset($_GET["pagina"]) ? intval($_GET["pagina"]) : 1;

// Manejo de Excel export
if (isset($_REQUEST["excelX"])) {
    $controller->exportExcel($ctrl, $_GET);
    exit;
}

// Mostrar búsqueda
$controller->handleSearch($ctrl, $pagina, $_GET);
?>
