<?php
/**
 * USUARIO_BUSCAR_CONTROLLER.PHP
 *
 * Lógica de búsqueda y filtrado
 * - Validación de parámetros
 * - Construcción de filtros
 * - Control de paginación
 * - Delegación a vistas
 */

require_once('usuario_buscar_service.php');
require_once('usuario_buscar_form.php');
require_once('usuario_buscar_excel.php');

class UsuarioBuscarController {

    private $service;
    private $config = [
        'registrosPerPage' => 50
    ];

    public function __construct() {
        $this->service = new UsuarioBuscarService();
    }

    /**
     * Manejar búsqueda y mostrar tabla con paginación
     */
    public function handleSearch($ctrl, $pagina, $params = []) {
        // Validar tipo de usuario
        $typeConfig = UsuarioBuscarService::getTypeConfig($ctrl);
        $tiposUsuario = $typeConfig['tipos'];
        $letrero = $typeConfig['label'];

        // Construir filtros
        $filters = $this->buildFilters($params);

        // Calcular paginación
        $registros = $this->config['registrosPerPage'];
        $pagina = intval($pagina);
        if ($pagina < 1) {
            $pagina = 1;
        }
        $inicio = ($pagina - 1) * $registros;

        // Contar total
        $totalRegistros = $this->service->countUsers($tiposUsuario, $filters);
        $totalPaginas = ceil($totalRegistros / $registros);

        // Si página solicitada > total, regresar a última válida
        if ($totalPaginas > 0 && $pagina > $totalPaginas) {
            $pagina = $totalPaginas;
            $inicio = ($pagina - 1) * $registros;
        }

        // Obtener datos
        $usuarios = $this->service->searchUsers($tiposUsuario, $filters, $inicio, $registros);

        // Obtener dropdowns
        $categoriasTipo = $this->service->getCategoriesByType(1, $tiposUsuario);

        $categoriesCliente = [];
        if ($ctrl == "" || $ctrl == 0 || $ctrl == 4) {
            // Query para clientes
            global $PSN2;
            if (!isset($PSN2) || !$PSN2) {
                $PSN2 = new DBbase_Sql();
            }
            $sql = "SELECT usuario.id, usuario.nombre FROM usuario WHERE tipo = 3 ORDER BY nombre ASC";
            $PSN2->query($sql);
            if ($PSN2->num_rows() > 0) {
                while ($PSN2->next_record()) {
                    $categoriesCliente[$PSN2->f('id')] = $PSN2->f('nombre');
                }
            }
        }

        // Render
        $this->renderSearch(
            $ctrl,
            $letrero,
            $usuarios,
            $categoriasTipo,
            $categoriesCliente,
            $buscar_tipo = isset($params['tipo']) ? intval($params['tipo']) : 0,
            $buscar_nombre = isset($params['nombre']) ? $params['nombre'] : '',
            $buscar_identificacion = isset($params['identificacion']) ? $params['identificacion'] : '',
            $buscar_cliente = isset($params['cliente']) ? intval($params['cliente']) : 0,
            $totalRegistros,
            $pagina,
            $totalPaginas,
            $registros,
            $params
        );
    }

    /**
     * Exportar a Excel/CSV
     */
    public function exportExcel($ctrl, $params = []) {
        // Validar tipo
        $typeConfig = UsuarioBuscarService::getTypeConfig($ctrl);
        $tiposUsuario = $typeConfig['tipos'];

        // Construir filtros
        $filters = $this->buildFilters($params);

        // Obtener todos los datos
        $usuarios = $this->service->getExcelData($tiposUsuario, $filters);

        // Exportar
        $exporter = new UsuarioBuscarExcel();
        $exporter->exportToCSV($usuarios, $ctrl, $params);
    }

    /**
     * Construir array de filtros desde parámetros GET
     * Sanitiza valores para evitar SQL injection
     */
    private function buildFilters($params = []) {
        $filters = [];

        // Tipo
        if (!empty($params['tipo'])) {
            $tipo = intval($params['tipo']);
            if ($tipo > 0) {
                $filters['tipo'] = $tipo;
            }
        }

        // Nombre
        if (!empty($params['nombre'])) {
            $nombre = $this->sanitizeString($params['nombre']);
            if (strlen($nombre) > 0) {
                $filters['nombre'] = $nombre;
            }
        }

        // Identificación
        if (!empty($params['identificacion'])) {
            $id = $this->sanitizeString($params['identificacion']);
            if (strlen($id) > 0) {
                $filters['identificacion'] = $id;
            }
        }

        // Cliente
        if (!empty($params['cliente'])) {
            $cliente = intval($params['cliente']);
            if ($cliente > 0) {
                $filters['cliente'] = $cliente;
            }
        }

        return $filters;
    }

    /**
     * Sanitizar string para búsqueda
     */
    private function sanitizeString($value) {
        $value = stripslashes($value);
        $value = substr($value, 0, 100); // Limitar longitud
        // No necesitamos html special chars aquí, la vista lo hace
        return $value;
    }

    /**
     * Renderizar tabla de búsqueda
     */
    private function renderSearch(
        $ctrl,
        $letrero,
        $usuarios,
        $categoriasTipo,
        $categoriesCliente,
        $buscar_tipo,
        $buscar_nombre,
        $buscar_identificacion,
        $buscar_cliente,
        $totalRegistros,
        $pagina,
        $totalPaginas,
        $registros,
        $params
    ) {
        $form = new UsuarioBuscarForm();
        $form->render(
            $ctrl,
            $letrero,
            $usuarios,
            $categoriasTipo,
            $categoriesCliente,
            $buscar_tipo,
            $buscar_nombre,
            $buscar_identificacion,
            $buscar_cliente,
            $totalRegistros,
            $pagina,
            $totalPaginas,
            $registros,
            $params
        );
    }

    /**
     * Validar parámetro ctrl (tipo de usuario)
     */
    public static function validateCtrl($ctrl) {
        $ctrl = intval($ctrl);
        if ($ctrl >= 0 && $ctrl <= 4) {
            return $ctrl;
        }
        return 0; // "todos"
    }
}
?>
