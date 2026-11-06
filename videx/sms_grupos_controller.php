<?php
/**
 * SMS Grupos Controller
 * Business logic and request routing for CRUD operations
 *
 * @package VIDEX
 * @version 1.0
 */

require_once('sms_grupos_service.php');
require_once('sms_grupos_config.php');

class SmsGruposController {
    private $service;

    public function __construct() {
        $this->service = new SmsGruposService();
    }

    /**
     * Check user authorization
     */
    public function authorize() {
        if (isset($_SESSION['perfil']) && in_array($_SESSION['perfil'], DENIED_PROFILES_SMS_GRUPOS)) {
            die("<h1>" . MESSAGES_SMS_GRUPOS['error_unauthorized'] . "</h1>");
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
            return ['mode' => 'create'];
        }

        // Validate data
        $validation = $this->validateData($post_data);
        if (!$validation['valid']) {
            return [
                'mode' => 'create',
                'errors' => $validation['errors'],
                'posted_data' => $post_data
            ];
        }

        // Create group
        $groupId = $this->service->createGroup($post_data);

        if (!$groupId) {
            return [
                'mode' => 'error',
                'error' => 'Error al crear el grupo'
            ];
        }

        return [
            'mode' => 'create_success',
            'group_id' => $groupId,
            'message' => MESSAGES_SMS_GRUPOS['success_create']
        ];
    }

    /**
     * Handle LIST operation with pagination
     * @param int $pagina Page number
     * @param array $filters Search filters
     * @return array Response with mode and data for list rendering
     */
    public function handleList($pagina = 1, $filters = []) {
        $pagina = max(1, intval($pagina));
        $offset = ($pagina - 1) * PAGINATION_SIZE_SMS_GRUPOS;

        $total_records = $this->service->getGroupCount($filters);
        $groups = $this->service->getGroups($offset, PAGINATION_SIZE_SMS_GRUPOS, $filters);

        $total_pages = ceil($total_records / PAGINATION_SIZE_SMS_GRUPOS);

        return [
            'mode' => 'list',
            'groups' => $groups,
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'current_page' => $pagina,
            'records_per_page' => PAGINATION_SIZE_SMS_GRUPOS,
            'filters' => $filters
        ];
    }

    /**
     * Handle EDIT operation
     * @param int $id Group ID
     * @param array $post_data POST data or empty array
     * @return array Response with mode and data for form rendering
     */
    public function handleEdit($id, $post_data = []) {
        $group = $this->service->getGroupById($id);

        if (!$group) {
            return [
                'mode' => 'error',
                'error' => MESSAGES_SMS_GRUPOS['error_not_found']
            ];
        }

        if (empty($post_data)) {
            // Display edit form pre-filled
            return [
                'mode' => 'edit',
                'group' => $group
            ];
        }

        // Check for special operations
        if (isset($post_data['funcion'])) {
            if ($post_data['funcion'] == 'limpiar') {
                // Clear group users
                $this->service->clearGroupUsers($id);
                return [
                    'mode' => 'edit_success',
                    'group_id' => $id,
                    'message' => MESSAGES_SMS_GRUPOS['success_clean']
                ];
            }
        }

        // Validate data
        $validation = $this->validateData($post_data);
        if (!$validation['valid']) {
            return [
                'mode' => 'edit',
                'group' => $group,
                'errors' => $validation['errors'],
                'posted_data' => $post_data
            ];
        }

        // Update group
        $result = $this->service->updateGroup($id, $post_data);

        if (!$result) {
            return [
                'mode' => 'error',
                'error' => 'Error al actualizar el grupo'
            ];
        }

        // Handle bulk upload if provided
        if (!empty($post_data['cargue_masivo'])) {
            $this->processBulkUpload($id, $post_data['cargue_masivo']);
        }

        return [
            'mode' => 'edit_success',
            'group_id' => $id,
            'message' => MESSAGES_SMS_GRUPOS['success_update']
        ];
    }

    /**
     * Process bulk user upload
     * @param int $groupId Group ID
     * @param string $csvData CSV data (tab-separated, newline-separated rows)
     * @return int Number of users added
     */
    private function processBulkUpload($groupId, $csvData) {
        $lines = explode("\n", $csvData);
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Replace commas with dots (for decimals)
            $line = str_replace(",", ".", $line);
            $fields = explode("\t", $line);

            if (count($fields) < 1 || empty($fields[0])) {
                continue;
            }

            // Map fields
            $userData = [
                'nombres' => trim($fields[0] ?? ''),
                'identificacion' => trim($fields[1] ?? ''),
                'direccion' => trim($fields[2] ?? ''),
                'paginaWeb' => trim($fields[3] ?? ''),
                'email' => trim($fields[4] ?? ''),
                'telfijo' => trim($fields[5] ?? ''),
                'celular' => trim($fields[6] ?? ''),
                'celular2' => trim($fields[7] ?? '')
            ];

            if (!empty($userData['nombres'])) {
                $userId = $this->service->addUserToGroup($groupId, $userData);
                if ($userId) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Validate group data
     * @param array $data Data to validate
     * @return array Validation result with 'valid' and 'errors' keys
     */
    private function validateData($data) {
        $errors = [];

        foreach (FIELD_RULES_SMS_GRUPOS as $field => $rules) {
            $value = $data[$field] ?? '';

            if ($rules['required']) {
                if (empty($value) || $value === '') {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " es requerido";
                    continue;
                }
            }

            if (!empty($value) && isset($rules['max_length'])) {
                if (strlen($value) > $rules['max_length']) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " no puede exceder " . $rules['max_length'] . " caracteres";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>
