<?php
/**
 * Campaign Controller
 * Business logic and request routing for CRUD operations
 *
 * @package VIDEX
 * @version 1.0
 */

require_once('campanha_service.php');
require_once('campanha_config.php');

class CampanaController {
    private $service;

    public function __construct() {
        $this->service = new CampanaService();
    }

    /**
     * Check user authorization
     */
    public function authorize() {
        if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ALLOWED_PROFILES)) {
            die("<h1>" . MESSAGES['error_unauthorized'] . "</h1>");
        }
    }

    /**
     * Handle CREATE operation
     * @param array $post_data POST data or empty array
     * @return array Response with mode and data for form rendering
     */
    public function handleCreate($post_data = []) {
        if (empty($post_data)) {
            // Display form
            return [
                'mode' => 'create',
                'categories' => $this->service->getCategories()
            ];
        }

        // Validate data
        $validation = $this->validateData($post_data);
        if (!$validation['valid']) {
            return [
                'mode' => 'create',
                'errors' => $validation['errors'],
                'posted_data' => $post_data,
                'categories' => $this->service->getCategories()
            ];
        }

        // Check duplicate name
        if ($this->service->campaignNameExists($post_data['nombre'])) {
            return [
                'mode' => 'create',
                'error' => MESSAGES['error_duplicate'],
                'posted_data' => $post_data,
                'categories' => $this->service->getCategories()
            ];
        }

        // Create campaign
        $post_data['creacionUsuario'] = $_SESSION['id'];
        $campaign_id = $this->service->createCampaign($post_data);

        if (!$campaign_id) {
            return [
                'mode' => 'error',
                'error' => 'Error al crear la campa�a'
            ];
        }

        return [
            'mode' => 'create_success',
            'campaign_id' => $campaign_id,
            'message' => MESSAGES['success_create']
        ];
    }

    /**
     * Handle LIST operation with search and pagination
     * @param int $page Current page number
     * @param array $filters Search filters
     * @return array Response with campaigns and pagination data
     */
    public function handleList($page = 1, $filters = []) {
        $page = max(1, intval($page));
        $offset = ($page - 1) * PAGINATION_SIZE;

        $total = $this->service->getCampaignCount($filters);
        $campaigns = $this->service->getCampaigns($offset, PAGINATION_SIZE, $filters);

        $total_pages = ceil($total / PAGINATION_SIZE);

        return [
            'mode' => 'list',
            'campaigns' => $campaigns,
            'filters' => $filters,
            'total_records' => $total,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'records_per_page' => PAGINATION_SIZE,
            'categories' => $this->service->getCategories()
        ];
    }

    /**
     * Handle EDIT operation
     * @param int $id Campaign ID
     * @param array $post_data POST data or empty array
     * @return array Response with mode and data for form rendering
     */
    public function handleEdit($id, $post_data = []) {
        $campaign = $this->service->getCampaignById($id);

        if (!$campaign) {
            return [
                'mode' => 'error',
                'error' => MESSAGES['error_not_found']
            ];
        }

        if (empty($post_data)) {
            // Display edit form pre-filled
            return [
                'mode' => 'edit',
                'campaign' => $campaign,
                'categories' => $this->service->getCategories()
            ];
        }

        // Validate data
        $validation = $this->validateData($post_data);
        if (!$validation['valid']) {
            return [
                'mode' => 'edit',
                'campaign' => $campaign,
                'errors' => $validation['errors'],
                'posted_data' => $post_data,
                'categories' => $this->service->getCategories()
            ];
        }

        // Update campaign
        $post_data['modUsuario'] = $_SESSION['id'];
        $result = $this->service->updateCampaign($id, $post_data);

        if (!$result) {
            return [
                'mode' => 'error',
                'error' => 'Error al actualizar la campa�a'
            ];
        }

        return [
            'mode' => 'edit_success',
            'campaign_id' => $id,
            'message' => MESSAGES['success_update']
        ];
    }

    /**
     * Get campaign by ID (for AJAX)
     * @param int $id Campaign ID
     * @return array|null Campaign data or null
     */
    public function getCategory($id) {
        return $this->service->getCampaignById($id);
    }

    /**
     * Handle DELETE operation (soft delete + cancel pending queue sends)
     * @param int $id Campaign ID
     * @return array Response with mode and result
     */
    public function handleDelete($id) {
        $campaign = $this->service->getCampaignById($id);

        if (!$campaign) {
            return [
                'mode' => 'error',
                'error' => MESSAGES['error_not_found']
            ];
        }

        $result = $this->service->deleteCampaign($id);

        if (!$result['success']) {
            return [
                'mode' => 'error',
                'error' => 'Error al eliminar la campa�a'
            ];
        }

        $message = MESSAGES['success_delete'];
        if ($result['queue_cancelled'] > 0) {
            $message .= ' Se cancelaron ' . $result['queue_cancelled'] . ' mensaje(s) pendiente(s) en la cola de env�o.';
        }

        return [
            'mode' => 'delete_success',
            'message' => $message,
            'queue_cancelled' => $result['queue_cancelled']
        ];
    }

    /**
     * Validate campaign data against field rules
     * @param array $data Data to validate
     * @return array Validation result with 'valid' bool and 'errors' array
     */
    private function validateData($data) {
        $errors = [];

        foreach (FIELD_RULES as $field => $rules) {
            $value = $data[$field] ?? '';

            // Normalize estado and tipoCampana to int for comparison
            if ($field === 'estado' || $field === 'tipoCampana') {
                $value = intval($value);
                $data[$field] = $value;
            }

            // Check required - handle 0 as valid value
            if ($rules['required']) {
                if ($value === '' || $value === null) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " es requerido";
                    continue;
                }
            }

            if ($value === '' || $value === null) {
                continue;
            }

            // Check max length
            if (!empty($rules['max_length']) && strlen((string)$value) > $rules['max_length']) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " no debe exceder " . $rules['max_length'] . " caracteres";
            }

            // Check type
            if (!empty($rules['type']) && $rules['type'] == 'date') {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value)) {
                    $errors[$field] = "Formato de fecha inv�lido (use YYYY-MM-DD)";
                }
            }

            if (!empty($rules['type']) && $rules['type'] == 'numeric') {
                if (!is_numeric($value)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " debe ser num�rico";
                }
            }

            // Check allowed values
            if (!empty($rules['allowed_values']) && !in_array($value, $rules['allowed_values'], true)) {
                $errors[$field] = "Valor inv�lido para " . ltrim($field);
            }
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }
}
?>
