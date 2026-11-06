<?php
/**
 * Cliente Controller
 * Business logic for CRUD operations
 *
 * @package VIDEX
 * @version 1.0
 */

require_once('cliente_config.php');
require_once('cliente_service.php');

class ClienteController {
    private $service;
    private $errors = [];

    public function __construct() {
        $this->service = new ClienteService();
    }

    /**
     * Check if user is authorized
     */
    public function authorize($profile, $deny_readonly = false) {
        if (!in_array($profile, ALLOWED_PROFILES)) {
            return false;
        }
        return true;
    }

    /**
     * Validate form data
     */
    public function validateData($data) {
        global $FIELD_RULES;
        $this->errors = [];

        foreach ($FIELD_RULES as $field => $rules) {
            $value = $data[$field] ?? '';

            // Check required
            if ($rules['required'] && empty($value)) {
                $this->errors[$field] = $rules['message'];
                continue;
            }

            if (empty($value) && !$rules['required']) {
                continue;
            }

            // Check length
            if (!empty($value)) {
                if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
                    $this->errors[$field] = "Máximo " . $rules['max_length'] . " caracteres";
                    continue;
                }
                if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
                    $this->errors[$field] = "Mínimo " . $rules['min_length'] . " caracteres";
                    continue;
                }

                // Check numeric
                if (!empty($rules['numeric']) && !is_numeric($value)) {
                    $this->errors[$field] = $rules['message'];
                    continue;
                }

                // Check in_array
                if (!empty($rules['in_array']) && !in_array($value, $rules['in_array'])) {
                    $this->errors[$field] = $rules['message'];
                    continue;
                }
            }
        }

        return count($this->errors) == 0;
    }

    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Handle create operation
     */
    public function handleCreate($post_data, $user_id) {
        if (!$this->validateData($post_data)) {
            return ['success' => false, 'errors' => $this->errors];
        }

        // Check for duplicates
        if ($this->service->clientNameExists($post_data['nombre'])) {
            return ['success' => false, 'error' => $GLOBALS['ERROR_MESSAGES']['DUPLICATE_NAME']];
        }

        // Prepare data
        $data = [
            'nombre' => $post_data['nombre'],
            'nit' => $post_data['nit'] ?? '',
            'representante_legal' => $post_data['representante_legal'] ?? '',
            'direccion' => $post_data['direccion'] ?? '',
            'telefono' => $post_data['telefono'] ?? '',
            'celular' => $post_data['celular'] ?? '',
            'email' => $post_data['email'] ?? '',
            'estado' => intval($post_data['estado'] ?? 1),
            'tipoCliente' => intval($post_data['tipoCliente'] ?? 0),
            'interno' => intval($post_data['interno'] ?? 0),
            'observaciones' => $post_data['observaciones'] ?? '',
            'paginaweb' => $post_data['paginaweb'] ?? '',
            'idComercial' => trim($post_data['idComercial'] ?? ''),
            'creacionUsuario' => $user_id
        ];

        $id = $this->service->createClient($data);
        return ['success' => true, 'id' => $id, 'message' => 'Cliente creado exitosamente'];
    }

    /**
     * Handle edit operation
     */
    public function handleEdit($id, $post_data, $user_id) {
        $client = $this->service->getClientById($id);
        if (!$client) {
            return ['success' => false, 'error' => 'Cliente no encontrado'];
        }

        if (!$this->validateData($post_data)) {
            return ['success' => false, 'errors' => $this->errors];
        }

        // Check for duplicates (except this client)
        if ($this->service->clientNameExists($post_data['nombre'], $id)) {
            return ['success' => false, 'error' => $GLOBALS['ERROR_MESSAGES']['DUPLICATE_NAME']];
        }

        // Prepare data
        $data = [
            'nombre' => $post_data['nombre'],
            'nit' => $post_data['nit'] ?? '',
            'representante_legal' => $post_data['representante_legal'] ?? '',
            'direccion' => $post_data['direccion'] ?? '',
            'telefono' => $post_data['telefono'] ?? '',
            'celular' => $post_data['celular'] ?? '',
            'email' => $post_data['email'] ?? '',
            'estado' => intval($post_data['estado'] ?? 1),
            'tipoCliente' => intval($post_data['tipoCliente'] ?? 0),
            'interno' => intval($post_data['interno'] ?? 0),
            'observaciones' => $post_data['observaciones'] ?? '',
            'paginaweb' => $post_data['paginaweb'] ?? '',
            'idComercial' => trim($post_data['idComercial'] ?? ''),
            'modUsuario' => $user_id
        ];

        $this->service->updateClient($id, $data);
        return ['success' => true, 'message' => 'Cliente actualizado exitosamente'];
    }

    /**
     * Handle delete operation
     */
    public function handleDelete($id) {
        $client = $this->service->getClientById($id);
        if (!$client) {
            return ['success' => false, 'error' => 'Cliente no encontrado'];
        }

        $this->service->deleteClient($id);
        return ['success' => true, 'message' => 'Cliente eliminado exitosamente'];
    }

    /**
     * Handle list operation
     */
    public function handleList($page, $filters, $comercial_restriction = null) {
        $page = max(1, intval($page));
        $offset = ($page - 1) * CLIENTS_PER_PAGE;

        $total = $this->service->getClientCount($filters);
        $total_pages = ceil($total / CLIENTS_PER_PAGE);

        $clients = $this->service->getClients($offset, CLIENTS_PER_PAGE, $filters, $comercial_restriction);

        return [
            'success' => true,
            'clients' => $clients,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'per_page' => CLIENTS_PER_PAGE
        ];
    }

    /**
     * Get dropdowns data
     */
    public function getDropdownData($comercial_restriction = null) {
        return [
            'tipos' => $this->service->getClientTypes(),
            'comerciales' => $this->service->getCommercials($comercial_restriction)
        ];
    }
}
?>
