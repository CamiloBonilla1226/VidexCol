<?php
/**
 * Contactos Controller - Business Logic
 * Handles CRUD operations and validation
 *
 * @package VIDEX
 * @version 1.0
 */

require_once('contactos_config.php');
require_once('contactos_service.php');

class ContactosController {
    private $service;
    public $errors = [];

    public function __construct() {
        $this->service = new ContactosService();
    }

    /**
     * Check if user is authorized
     */
    public function authorize($profile) {
        return in_array($profile, ALLOWED_PROFILES);
    }

    /**
     * Validate contact data
     */
    public function validateData($data) {
        $this->errors = [];

        // Required: nombres
        if (empty($data['nombres'])) {
            $this->errors['nombres'] = ERROR_MESSAGES['nombres_required'];
            return false;
        }

        if (strlen($data['nombres']) < 3) {
            $this->errors['nombres'] = ERROR_MESSAGES['nombres_min_length'];
            return false;
        }

        if (strlen($data['nombres']) > 250) {
            $this->errors['nombres'] = ERROR_MESSAGES['nombres_max_length'];
            return false;
        }

        // Phone validations if provided
        if (!empty($data['celular']) && !is_numeric($data['celular'])) {
            $this->errors['celular'] = ERROR_MESSAGES['celular_format'];
        }

        if (!empty($data['celular2']) && !is_numeric($data['celular2'])) {
            $this->errors['celular2'] = ERROR_MESSAGES['celular_format'];
        }

        if (!empty($data['telfijo']) && !is_numeric($data['telfijo'])) {
            $this->errors['telfijo'] = ERROR_MESSAGES['phone_format'];
        }

        // Email format if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = ERROR_MESSAGES['email_format'];
        }

        return empty($this->errors);
    }

    /**
     * Handle create operation
     */
    public function handleCreate($post_data, $user_id) {
        if (!$this->validateData($post_data)) {
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }

        // Check duplicate name
        if ($this->service->contactNameExists($post_data['nombres'])) {
            return [
                'success' => false,
                'error' => ERROR_MESSAGES['duplicate_name']
            ];
        }

        // Prepare data for insert
        $data = [
            'nombres' => $post_data['nombres'],
            'email' => $post_data['email'] ?? '',
            'celular' => $post_data['celular'] ?? '',
            'celular2' => $post_data['celular2'] ?? '',
            'telfijo' => $post_data['telfijo'] ?? '',
            'identificacion' => $post_data['identificacion'] ?? '',
            'tipoIdentificacion' => $post_data['tipoIdentificacion'] ?? 0,
            'direccion' => $post_data['direccion'] ?? '',
            'paginaWeb' => $post_data['paginaWeb'] ?? '',
            'notas' => $post_data['notas'] ?? '',
            'idCliente' => $post_data['idCliente'] ?? 0,
            'audit_usuario' => $user_id,
            'audit_ip' => $_SERVER['REMOTE_ADDR']
        ];

        $contact_id = $this->service->createContact($data);

        // Add group associations
        if (!empty($post_data['listaasociar']) && is_array($post_data['listaasociar'])) {
            $db = new DBbase_Sql();
            foreach ($post_data['listaasociar'] as $group_id) {
                $group_id = intval($group_id);
                if ($group_id > 0) {
                    $sql = "REPLACE INTO sms_asociacion (id_grupo, id_usuario) VALUES (" . $group_id . ", " . intval($contact_id) . ")";
                    $db->query($sql);
                }
            }
        }

        return [
            'success' => true,
            'message' => SUCCESS_MESSAGES['contact_created'],
            'id' => $contact_id
        ];
    }

    /**
     * Handle edit operation
     */
    public function handleEdit($id, $post_data, $user_id) {
        $id = intval($id);

        // Verify contact exists
        $contact = $this->service->getContactById($id);
        if (!$contact) {
            return [
                'success' => false,
                'error' => ERROR_MESSAGES['contact_not_found']
            ];
        }

        if (!$this->validateData($post_data)) {
            return [
                'success' => false,
                'errors' => $this->errors
            ];
        }

        // Check duplicate name (excluding current contact)
        if ($this->service->contactNameExists($post_data['nombres'], $id)) {
            return [
                'success' => false,
                'error' => ERROR_MESSAGES['duplicate_name']
            ];
        }

        // Prepare data
        $data = [
            'nombres' => $post_data['nombres'],
            'email' => $post_data['email'] ?? '',
            'celular' => $post_data['celular'] ?? '',
            'celular2' => $post_data['celular2'] ?? '',
            'telfijo' => $post_data['telfijo'] ?? '',
            'identificacion' => $post_data['identificacion'] ?? '',
            'tipoIdentificacion' => $post_data['tipoIdentificacion'] ?? 0,
            'direccion' => $post_data['direccion'] ?? '',
            'paginaWeb' => $post_data['paginaWeb'] ?? '',
            'notas' => $post_data['notas'] ?? '',
            'idCliente' => $post_data['idCliente'] ?? 0
        ];

        $this->service->updateContact($id, $data);

        // Update group associations
        $db = new DBbase_Sql();
        $db->query("DELETE FROM sms_asociacion WHERE id_usuario = " . $id);

        if (!empty($post_data['listaasociar']) && is_array($post_data['listaasociar'])) {
            foreach ($post_data['listaasociar'] as $group_id) {
                $group_id = intval($group_id);
                if ($group_id > 0) {
                    $sql = "REPLACE INTO sms_asociacion (id_grupo, id_usuario) VALUES (" . $group_id . ", " . $id . ")";
                    $db->query($sql);
                }
            }
        }

        return [
            'success' => true,
            'message' => SUCCESS_MESSAGES['contact_updated']
        ];
    }

    /**
     * Handle delete operation
     */
    public function handleDelete($id) {
        $id = intval($id);

        $contact = $this->service->getContactById($id);
        if (!$contact) {
            return [
                'success' => false,
                'error' => ERROR_MESSAGES['contact_not_found']
            ];
        }

        $this->service->deleteContact($id);

        return [
            'success' => true,
            'message' => SUCCESS_MESSAGES['contact_deleted']
        ];
    }

    /**
     * Handle list operation
     */
    public function handleList($page, $filters, $comercial_restriction) {
        $page = max(1, intval($page));
        $offset = ($page - 1) * CONTACTS_PER_PAGE;

        $total = $this->service->getContactCount($filters, $comercial_restriction);
        $contacts = $this->service->getContacts($offset, CONTACTS_PER_PAGE, $filters, $comercial_restriction);

        $total_pages = ceil($total / CONTACTS_PER_PAGE);
        $total_pages = max(1, $total_pages);
        $page = min($page, $total_pages);

        return [
            'contacts' => $contacts,
            'total' => $total,
            'page' => $page,
            'per_page' => CONTACTS_PER_PAGE,
            'total_pages' => $total_pages
        ];
    }

    /**
     * Get dropdown data
     */
    public function getDropdownData($comercial_restriction = null) {
        return [
            'clients' => $this->service->getClients($comercial_restriction),
            'identification_types' => $this->service->getIdentificationTypes(),
            'groups' => $this->service->getGroups()
        ];
    }
}
?>
