<?php
/**
 * Usuario Controller
 * Handles validation, business logic, and routing
 */

require_once('usuario_config.php');
require_once('usuario_service.php');
require_once('usuario_form.php');

class UsuarioController {
    private $service;
    private $errors = [];
    private $success = [];

    private function isDebugEnabled() {
        $debugGet = isset($_GET['debug']) ? $_GET['debug'] : '';
        $debugPost = isset($_POST['debug']) ? $_POST['debug'] : '';
        return ($debugGet === '1' || $debugGet === 'true' || $debugPost === '1' || $debugPost === 'true');
    }

    public function __construct() {
        $this->service = new UsuarioService();
    }

    // ============================================================
    // MAIN HANDLERS
    // ============================================================

    /**
     * Handle user creation
     */
    public function handleUserCreate($postData) {
        // Clear previous errors
        $this->errors = [];

        // Sanitize inputs
        $userData = $this->sanitizeCreateInput($postData);

        // Validate inputs
        if (!$this->validateUserInput($userData, 'create')) {
            // Render form with errors
            return $this->getUserForm(1, 0);
        }

        // Check identification uniqueness
        if ($this->service->identificationExists($userData['identificacion'])) {
            $this->errors[] = getErrorMessage(ERROR_IDENTIFICATION_EXISTS);
            return $this->getUserForm(1, 0);
        }

        // Validate email if provided
        if (!empty($userData['email']) && !isValidEmail($userData['email'])) {
            $this->errors[] = getErrorMessage(ERROR_INVALID_EMAIL);
            return $this->getUserForm(1, 0);
        }

        // Create user
        $userId = $this->service->createUser($userData);

        if (!$userId) {
            $this->errors[] = getErrorMessage(ERROR_DATABASE_ERROR);
            if ($this->isDebugEnabled()) {
                $this->errors[] = 'DEBUG SQL ERROR: ' . ($this->service->getLastError() ?: 'Sin detalle');
                if ($this->service->getLastQuery()) {
                    $this->errors[] = 'DEBUG SQL QUERY: ' . $this->service->getLastQuery();
                }
            }
            return $this->getUserForm(1, 0);
        }

        // Handle permissions if set (siempre se llama si hay algun permiso de
        // Gestion Proyectos activo, aunque no se haya marcado nada en la
        // grilla de menus: ver syncGestionProyectosMenuPermiso).
        $permisos = $postData['permisos'] ?? [];
        $permisos = $this->syncGestionProyectosMenuPermiso($userData, $permisos);
        $permisos = $this->syncTicketsMenuPermiso($userData, $permisos);
        $permisos = $this->syncClavesMenuPermiso($userData, $permisos, $userId);
        // Con la grilla presente en el formulario se guarda aunque quede vacia (revocar todo).
        if (!empty($permisos) || !empty($postData['permisos_presentes'])) {
            $this->service->saveUserPermissions($userId, $permisos);
        }

        // Handle graph permissions if set
        if (!empty($postData['permisos_graphs'])) {
            $this->service->saveGraphPermissions($userId, $postData['permisos_graphs']);
        }

        // Handle documents upload if any
        if (!empty($_FILES) && $_FILES['archivo']['size'] > 0) {
            $this->handleDocumentUpload($userId, $_FILES);
        }

        // Success message
        echo '<div class="alert alert-success">Usuario creado exitosamente</div>';
        return $this->getUserForm(1, $userId);
    }

    /**
     * Handle user update
     */
    public function handleUserUpdate($postData) {
        // Clear previous errors
        $this->errors = [];

        $userId = isset($postData['id']) ? (int)$postData['id'] : 0;

        if ($userId <= 0) {
            $this->errors[] = 'ID de usuario inválido';
            return $this->getUserForm(2, 0);
        }

        // Sanitize inputs
        $userData = $this->sanitizeUpdateInput($postData);

        // Validate inputs
        if (!$this->validateUserInput($userData, 'update')) {
            // Render form with errors
            return $this->getUserForm(2, $userId);
        }

        // Validate email if provided
        if (!empty($userData['email']) && !isValidEmail($userData['email'])) {
            $this->errors[] = getErrorMessage(ERROR_INVALID_EMAIL);
            return $this->getUserForm(2, $userId);
        }

        // Update user
        if (!$this->service->updateUser($userId, $userData)) {
            $this->errors[] = getErrorMessage(ERROR_DATABASE_ERROR);
            if ($this->isDebugEnabled()) {
                $this->errors[] = 'DEBUG SQL ERROR: ' . ($this->service->getLastError() ?: 'Sin detalle');
                if ($this->service->getLastQuery()) {
                    $this->errors[] = 'DEBUG SQL QUERY: ' . $this->service->getLastQuery();
                }
            }
            return $this->getUserForm(2, $userId);
        }

        // Handle permissions if set (ver syncGestionProyectosMenuPermiso)
        $permisos = $postData['permisos'] ?? [];
        $permisos = $this->syncGestionProyectosMenuPermiso($userData, $permisos);
        $permisos = $this->syncTicketsMenuPermiso($userData, $permisos);
        $permisos = $this->syncClavesMenuPermiso($userData, $permisos, $userId);
        // Con la grilla presente en el formulario se guarda aunque quede vacia (revocar todo).
        if (!empty($permisos) || !empty($postData['permisos_presentes'])) {
            $this->service->saveUserPermissions($userId, $permisos);
        }

        // Handle graph permissions if set
        if (!empty($postData['permisos_graphs'])) {
            $this->service->saveGraphPermissions($userId, $postData['permisos_graphs']);
        }

        // Handle documents upload if any
        if (!empty($_FILES) && $_FILES['archivo']['size'] > 0) {
            $this->handleDocumentUpload($userId, $_FILES);
        }

        // Success message
        echo '<div class="alert alert-success">Usuario actualizado exitosamente</div>';
        return $this->getUserForm(2, $userId);
    }

    /**
     * Get and render user form
     */
    public function getUserForm($opc, $userId) {
        $user = null;
        $permissions = [];
        $documents = [];
        $clients = [];
        $availableMenus = [];
        $availableGraphs = [];
        $availableClients = [];

        if ($userId > 0) {
            $user = $this->service->getUserById($userId);
            if (!$user) {
                echo '<div class="alert alert-danger">Usuario no encontrado</div>';
                return;
            }
            $permissions = $this->service->getUserPermissions($userId);
            $documents = $this->service->getUserDocuments($userId);
            $clients = $this->service->getUserClients($userId);
        }

        // Load dropdowns data
        $availableMenus = $this->service->getAvailableMenus();
        $availableGraphs = $this->service->getAvailableGraphs();
        $availableClients = $this->service->getAvailableClients();

        // Render form
        $form = new UsuarioForm();
        $form->render($opc, $user, $permissions, $documents, $clients, $availableMenus, $availableGraphs, $availableClients, $this->errors);
    }

    // ============================================================
    // VALIDATION METHODS
    // ============================================================

    /**
     * Validate user input data
     */
    private function validateUserInput($userData, $action = 'create') {
        $isValid = true;

        // Required fields check
        if (empty($userData['nombre'])) {
            $this->errors[] = 'El nombre es requerido';
            $isValid = false;
        }

        if (empty($userData['identificacion'])) {
            $this->errors[] = 'La identificación es requerida';
            $isValid = false;
        }

        // Password validation for create
        if ($action === 'create') {
            if (empty($userData['password'])) {
                $this->errors[] = 'La contraseña es requerida';
                $isValid = false;
            } elseif (!isValidPassword($userData['password'])) {
                $this->errors[] = 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, números y caracteres especiales';
                $isValid = false;
            }
        } elseif ($action === 'update' && !empty($userData['password'])) {
            // Optional password update
            if (!isValidPassword($userData['password'])) {
                $this->errors[] = 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, números y caracteres especiales';
                $isValid = false;
            }
        }

        // Phone validation if provided
        if (!empty($userData['telefono1']) && !isValidPhone($userData['telefono1'])) {
            $this->errors[] = 'Teléfono 1 inválido';
            $isValid = false;
        }

        if (!empty($userData['celular']) && !isValidPhone($userData['celular'])) {
            $this->errors[] = 'Celular inválido';
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * El acceso al menu "Inventario" (Gestion Proyectos) se controla con el
     * checkbox generico de la grilla de Permisos, pero los 3 checkboxes de
     * gp_consulta/gp_modificacion/gp_ver_credenciales viven aparte, en la
     * pestana de Acceso. Si no se sincronizan, un usuario puede quedar con
     * permisos de modulo activos pero sin ver el item en el menu (o viceversa
     * al desmarcarlos). Aqui se agrega/quita el id de ese menu del arreglo de
     * permisos ANTES de guardarlos, para que ambas cosas queden consistentes
     * con una sola accion.
     */
    private function syncGestionProyectosMenuPermiso($userData, $permisos) {
        // El inventario de proveedores de hosting y el de dominios comparten los mismos
        // permisos de Gestion Proyectos, asi que ambos menus se sincronizan igual.
        $permisos = array_map('intval', (array) $permisos);
        $tieneAcceso = !empty($userData['gp_consulta']) || !empty($userData['gp_modificacion']);
        foreach (['gp_proveedores', 'gp_dom_proveedores'] as $php) {
            $menuId = $this->service->getMenuIdByPhp($php);
            if ($menuId <= 0) {
                continue;
            }
            $yaEstaba = in_array($menuId, $permisos, true);
            if ($tieneAcceso && !$yaEstaba) {
                $permisos[] = $menuId;
            } elseif (!$tieneAcceso && $yaEstaba) {
                $permisos = array_values(array_diff($permisos, [$menuId]));
            }
        }
        return $permisos;
    }

    /**
     * Mismo mecanismo que syncGestionProyectosMenuPermiso, para el menu
     * "Tickets/Tareas" y sus 3 checkboxes gp_tickets_* de la pestana Acceso.
     */
    private function syncTicketsMenuPermiso($userData, $permisos) {
        $menuId = $this->service->getMenuIdByPhp('gp_tickets');
        if ($menuId <= 0) {
            return $permisos;
        }
        $permisos = array_map('intval', (array) $permisos);
        $tieneAcceso = !empty($userData['gp_tickets_consulta']) || !empty($userData['gp_tickets_modificacion']);
        $yaEstaba = in_array($menuId, $permisos, true);
        if ($tieneAcceso && !$yaEstaba) {
            $permisos[] = $menuId;
        } elseif (!$tieneAcceso && $yaEstaba) {
            $permisos = array_values(array_diff($permisos, [$menuId]));
        }
        return $permisos;
    }

    /**
     * Permiso ESPECIAL "Claves" (registro cifrado de claves, dentro de Inventario).
     * Mismo mecanismo que los otros, pero solo se sincroniza cuando el permiso
     * viene en los datos: quien no sea el superadministrador principal no puede concederlo ni
     * quitarlo (ver sanitizeCreateInput / sanitizeUpdateInput), asi que para
     * ellos no se toca el menu.
     */
    private function syncClavesMenuPermiso($userData, $permisos, $userId = 0) {
        $menuId = $this->service->getMenuIdByPhp('gp_claves');
        if ($menuId <= 0) {
            return $permisos;
        }
        $permisos = array_map('intval', (array) $permisos);
        if (!array_key_exists('gp_claves', $userData)) {
            // Quien edita no puede tocar este permiso: el menu queda exactamente como el
            // usuario ya lo tenia (la grilla no incluye "Claves"; si alguien lo fuerza a
            // mano en el POST se ignora).
            $actuales = ((int) $userId > 0)
                ? array_map('intval', (array) $this->service->getUserPermissions((int) $userId))
                : [];
            $permisos = array_values(array_diff($permisos, [$menuId]));
            if (in_array($menuId, $actuales, true)) {
                $permisos[] = $menuId;
            }
            return $permisos;
        }
        $tieneAcceso = !empty($userData['gp_claves']);
        $yaEstaba = in_array($menuId, $permisos, true);
        if ($tieneAcceso && !$yaEstaba) {
            $permisos[] = $menuId;
        } elseif (!$tieneAcceso && $yaEstaba) {
            $permisos = array_values(array_diff($permisos, [$menuId]));
        }
        return $permisos;
    }

    /** Solo el superadministrador principal (id 1) concede o quita el permiso especial de Claves. */
    private function puedeGestionarPermisoClaves() {
        return usuario_puede_gestionar_permiso_claves();
    }

    // ============================================================
    // SANITIZATION METHODS
    // ============================================================

    /**
     * Sanitize input for user creation
     */
    private function sanitizeCreateInput($postData) {
        return [
            'tipo' => isset($postData['tipo']) ? (int)$postData['tipo'] : USER_TYPE_ADMIN,
            'identificacion' => isset($postData['identificacion']) ? trim($postData['identificacion']) : '',
            'nombre' => isset($postData['nombre']) ? trim($postData['nombre']) : '',
            'password' => isset($postData['password']) ? $postData['password'] : '', // Not escaped for bcrypt
            'acceso' => isset($postData['acceso']) ? (int)$postData['acceso'] : 1,
            'tipo_user_cli' => isset($postData['tipo_user_cli']) ? (int)$postData['tipo_user_cli'] : 0,
            'tipoIdentificacion' => isset($postData['tipoIdentificacion']) ? trim($postData['tipoIdentificacion']) : 'CC',
            'direccion' => isset($postData['direccion']) ? trim($postData['direccion']) : '',
            'telefono1' => isset($postData['telefono1']) ? trim($postData['telefono1']) : '',
            'telefono2' => isset($postData['telefono2']) ? trim($postData['telefono2']) : '',
            'celular' => isset($postData['celular']) ? trim($postData['celular']) : '',
            'email' => isset($postData['email']) ? trim($postData['email']) : '',
            'url' => isset($postData['url']) ? trim($postData['url']) : '',
            'observaciones' => isset($postData['observaciones']) ? trim($postData['observaciones']) : '',
            'acceso_solo_lectura' => isset($postData['acceso_solo_lectura']) ? (int)$postData['acceso_solo_lectura'] : 0,
            'acceso_graphs' => isset($postData['acceso_graphs']) ? (int)$postData['acceso_graphs'] : 0,
            'gp_consulta' => isset($postData['gp_consulta']) ? 1 : 0,
            'gp_modificacion' => isset($postData['gp_modificacion']) ? 1 : 0,
            'gp_ver_credenciales' => isset($postData['gp_ver_credenciales']) ? 1 : 0,
            'gp_tickets_consulta' => isset($postData['gp_tickets_consulta']) ? 1 : 0,
            'gp_tickets_modificacion' => isset($postData['gp_tickets_modificacion']) ? 1 : 0,
            'gp_tickets_fechas' => isset($postData['gp_tickets_fechas']) ? 1 : 0,
            'gp_claves' => ($this->puedeGestionarPermisoClaves() && isset($postData['gp_claves'])) ? 1 : 0,
            'gp_eliminar_proveedores' => isset($postData['gp_eliminar_proveedores']) ? 1 : 0,
            'gp_inv_consulta' => isset($postData['gp_inv_consulta']) ? 1 : 0,
            'gp_inv_modificacion' => isset($postData['gp_inv_modificacion']) ? 1 : 0,
            'gp_inv_eliminar' => isset($postData['gp_inv_eliminar']) ? 1 : 0
        ];
    }

    /**
     * Sanitize input for user update
     */
    private function sanitizeUpdateInput($postData) {
        $data = [];

        // Campos requeridos también en actualización
        if (isset($postData['identificacion'])) {
            $data['identificacion'] = trim($postData['identificacion']);
        }
        if (isset($postData['tipo'])) {
            $data['tipo'] = (int)$postData['tipo'];
        }
        if (isset($postData['tipoIdentificacion'])) {
            $data['tipoIdentificacion'] = trim($postData['tipoIdentificacion']);
        }

        if (isset($postData['nombre'])) {
            $data['nombre'] = trim($postData['nombre']);
        }
        if (isset($postData['email'])) {
            $data['email'] = trim($postData['email']);
        }
        if (isset($postData['password']) && !empty($postData['password'])) {
            $data['password'] = $postData['password']; // Not escaped for bcrypt
        }
        if (isset($postData['acceso'])) {
            $data['acceso'] = (int)$postData['acceso'];
        }
        if (isset($postData['direccion'])) {
            $data['direccion'] = trim($postData['direccion']);
        }
        if (isset($postData['telefono1'])) {
            $data['telefono1'] = trim($postData['telefono1']);
        }
        if (isset($postData['telefono2'])) {
            $data['telefono2'] = trim($postData['telefono2']);
        }
        if (isset($postData['celular'])) {
            $data['celular'] = trim($postData['celular']);
        }
        if (isset($postData['celular2'])) {
            $data['celular2'] = trim($postData['celular2']);
        }
        if (isset($postData['url'])) {
            $data['url'] = trim($postData['url']);
        }
        if (isset($postData['observaciones'])) {
            $data['observaciones'] = trim($postData['observaciones']);
        }

        // Checkboxes: cuando no vienen en POST, deben guardarse en 0
        $data['acceso'] = isset($postData['acceso']) ? 1 : 0;
        $data['acceso_solo_lectura'] = isset($postData['acceso_solo_lectura']) ? 1 : 0;
        $data['acceso_graphs'] = isset($postData['acceso_graphs']) ? 1 : 0;
        $data['gp_consulta'] = isset($postData['gp_consulta']) ? 1 : 0;
        $data['gp_modificacion'] = isset($postData['gp_modificacion']) ? 1 : 0;
        $data['gp_ver_credenciales'] = isset($postData['gp_ver_credenciales']) ? 1 : 0;
        $data['gp_tickets_consulta'] = isset($postData['gp_tickets_consulta']) ? 1 : 0;
        $data['gp_tickets_modificacion'] = isset($postData['gp_tickets_modificacion']) ? 1 : 0;
        $data['gp_tickets_fechas'] = isset($postData['gp_tickets_fechas']) ? 1 : 0;
        $data['gp_eliminar_proveedores'] = isset($postData['gp_eliminar_proveedores']) ? 1 : 0;
        $data['gp_inv_consulta'] = isset($postData['gp_inv_consulta']) ? 1 : 0;
        $data['gp_inv_modificacion'] = isset($postData['gp_inv_modificacion']) ? 1 : 0;
        $data['gp_inv_eliminar'] = isset($postData['gp_inv_eliminar']) ? 1 : 0;
        // Permiso especial: si quien edita no es el superadministrador principal, no se toca (ni se deja enviar).
        if ($this->puedeGestionarPermisoClaves()) {
            $data['gp_claves'] = isset($postData['gp_claves']) ? 1 : 0;
        }

        return $data;
    }

    // ============================================================
    // FILE UPLOAD HANDLER
    // ============================================================

    /**
     * Handle document upload with validation
     */
    private function handleDocumentUpload($userId, $files) {
        if (!isset($files['archivo']) || $files['archivo']['error'] != UPLOAD_ERR_OK) {
            $this->errors[] = getErrorMessage(ERROR_FILE_UPLOAD_FAILED);
            return false;
        }

        $file = $files['archivo'];

        // Validate file size
        if ($file['size'] > MAX_FILE_SIZE) {
            $this->errors[] = 'El archivo excede el tamaño máximo de 5MB';
            return false;
        }

        // Validate file type
        if (!isAllowedFileType($file['type'])) {
            $this->errors[] = 'Tipo de archivo no permitido. Tipos aceptados: ' . implode(', ', ALLOWED_FILE_EXTENSIONS);
            return false;
        }

        // Validate file extension
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), ALLOWED_FILE_EXTENSIONS)) {
            $this->errors[] = 'Extensión de archivo no permitida';
            return false;
        }

        // Generate safe filename
        $safeFileName = 'usuario_' . $userId . '_' . time() . '.' . $ext;
        $uploadDir = dirname(__FILE__) . '/documentos/usuarios/';

        // Create directory if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadPath = $uploadDir . $safeFileName;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Save to database
            $this->service->uploadUserDocument(
                $userId,
                'documentos/usuarios/' . $safeFileName,
                $file['name'],
                isset($files['tipo']) ? $files['tipo'] : 'other'
            );
            $this->success[] = 'Documento cargado exitosamente';
            return true;
        } else {
            $this->errors[] = getErrorMessage(ERROR_FILE_UPLOAD_FAILED);
            return false;
        }
    }

    // ============================================================
    // GETTERS
    // ============================================================

    /**
     * Get all errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get all success messages
     */
    public function getSuccessMessages() {
        return $this->success;
    }

    /**
     * Check if there are errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }

}

?>
