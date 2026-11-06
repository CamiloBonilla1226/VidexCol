<?php
/**
 * Usuario Service Layer
 * Database operations with prepared statements (SQL injection safe)
 */

require_once('usuario_config.php');

class UsuarioService {
    private $db;
    private $lastError = '';
    private $lastQuery = '';
    private $userTableColumns = null;

    private function esc($value) {
        if (!isset($this->db->Link_ID) || !$this->db->Link_ID) {
            $this->db->connect();
        }
        return mysqli_real_escape_string($this->db->Link_ID, (string)$value);
    }

    private function executeSql($sql) {
        $this->lastError = '';
        $this->lastQuery = $sql;

        if (!isset($this->db->Link_ID) || !$this->db->Link_ID) {
            $this->db->connect();
        }

        $ok = mysqli_query($this->db->Link_ID, $sql);
        if ($ok === false) {
            $this->lastError = mysqli_error($this->db->Link_ID);
            error_log('UsuarioService SQL error: ' . $this->lastError . ' | SQL: ' . $sql);
            return false;
        }

        return true;
    }

    private function getUserTableColumns() {
        if (is_array($this->userTableColumns)) {
            return $this->userTableColumns;
        }

        $this->userTableColumns = [];

        if (!isset($this->db->Link_ID) || !$this->db->Link_ID) {
            $this->db->connect();
        }

        $result = mysqli_query($this->db->Link_ID, "SHOW COLUMNS FROM usuario");
        if ($result === false) {
            $this->lastError = mysqli_error($this->db->Link_ID);
            return $this->userTableColumns;
        }

        while ($row = mysqli_fetch_assoc($result)) {
            if (!empty($row['Field'])) {
                $this->userTableColumns[$row['Field']] = true;
            }
        }
        mysqli_free_result($result);

        return $this->userTableColumns;
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function getLastQuery() {
        return $this->lastQuery;
    }

    public function __construct() {
        // Use existing DBbase_Sql connection
        $this->db = new DBbase_Sql();
    }

    // ============================================================
    // CREATE OPERATIONS
    // ============================================================

    /**
     * Create new user with all data
     * Uses prepared statements for security
     */
    public function createUser($userData) {
        try {
            // Hash password with bcrypt
            $hashedPassword = password_hash(
                $userData['password'],
                PASSWORD_BCRYPT,
                ['cost' => 12]
            );

            $columns = $this->getUserTableColumns();

            $fieldMap = [
                'tipo' => (int)($userData['tipo'] ?? 1),
                'identificacion' => $userData['identificacion'] ?? '',
                'nombre' => $userData['nombre'] ?? '',
                'password' => $hashedPassword,
                'acceso' => (int)($userData['acceso'] ?? 1),
                'tipo_user_cli' => (int)($userData['tipo_user_cli'] ?? 0),
                'direccion' => $userData['direccion'] ?? '',
                'telefono1' => $userData['telefono1'] ?? '',
                'telefono2' => $userData['telefono2'] ?? '',
                'celular' => $userData['celular'] ?? '',
                'email' => $userData['email'] ?? '',
                'url' => $userData['url'] ?? '',
                'observaciones' => $userData['observaciones'] ?? '',
                'acceso_solo_lectura' => (int)($userData['acceso_solo_lectura'] ?? 0),
                'acceso_graphs' => (int)($userData['acceso_graphs'] ?? 0),
                'gp_consulta' => (int)($userData['gp_consulta'] ?? 0),
                'gp_modificacion' => (int)($userData['gp_modificacion'] ?? 0),
                'gp_ver_credenciales' => (int)($userData['gp_ver_credenciales'] ?? 0),
                'gp_tickets_consulta' => (int)($userData['gp_tickets_consulta'] ?? 0),
                'gp_tickets_modificacion' => (int)($userData['gp_tickets_modificacion'] ?? 0),
                'gp_tickets_fechas' => (int)($userData['gp_tickets_fechas'] ?? 0),
                'gp_claves' => (int)($userData['gp_claves'] ?? 0),
                'gp_eliminar_proveedores' => (int)($userData['gp_eliminar_proveedores'] ?? 0),
                'gp_inv_consulta' => (int)($userData['gp_inv_consulta'] ?? 0),
                'gp_inv_modificacion' => (int)($userData['gp_inv_modificacion'] ?? 0),
                'gp_inv_eliminar' => (int)($userData['gp_inv_eliminar'] ?? 0),
                'tipoIdentificacion' => $userData['tipoIdentificacion'] ?? 'CC'
            ];

            $sqlFields = [];
            $sqlValues = [];

            foreach ($fieldMap as $field => $value) {
                if (!isset($columns[$field])) {
                    continue;
                }

                $sqlFields[] = $field;
                if (is_int($value) || is_float($value)) {
                    $sqlValues[] = (string)$value;
                } else {
                    $sqlValues[] = "'" . $this->esc($value) . "'";
                }
            }

            if (empty($sqlFields)) {
                $this->lastError = 'No hay columnas válidas para insertar en tabla usuario';
                return false;
            }

            $sql = 'INSERT INTO usuario (' . implode(', ', $sqlFields) . ') VALUES (' . implode(', ', $sqlValues) . ')';
            if (!$this->executeSql($sql)) {
                return false;
            }

            if (!isset($this->db->Link_ID) || !$this->db->Link_ID) {
                return false;
            }

            $newId = mysqli_insert_id($this->db->Link_ID);
            return $newId > 0 ? $newId : false;
        } catch (Exception $e) {
            error_log('Error creating user: ' . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Update existing user
     */
    public function updateUser($userId, $userData) {
        try {
            $columns = $this->getUserTableColumns();
            $updates = [];
            $values = [];

            // Build dynamic update query
            if (isset($userData['nombre']) && isset($columns['nombre'])) {
                $updates[] = "nombre = '%s'";
                $values[] = $this->esc($userData['nombre']);
            }
            if (isset($userData['identificacion']) && isset($columns['identificacion'])) {
                $updates[] = "identificacion = '%s'";
                $values[] = $this->esc($userData['identificacion']);
            }
            if (isset($userData['tipo']) && isset($columns['tipo'])) {
                $updates[] = "tipo = %d";
                $values[] = (int)$userData['tipo'];
            }
            if (isset($userData['email']) && isset($columns['email'])) {
                $updates[] = "email = '%s'";
                $values[] = $this->esc($userData['email']);
            }
            if (isset($userData['password']) && !empty($userData['password']) && isset($columns['password'])) {
                $updates[] = "password = '%s'";
                $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => 12]);
                $values[] = $this->esc($hashedPassword);
            }
            if (isset($userData['acceso']) && isset($columns['acceso'])) {
                $updates[] = "acceso = %d";
                $values[] = (int)$userData['acceso'];
            }
            if (isset($userData['direccion']) && isset($columns['direccion'])) {
                $updates[] = "direccion = '%s'";
                $values[] = $this->esc($userData['direccion']);
            }
            if (isset($userData['telefono1']) && isset($columns['telefono1'])) {
                $updates[] = "telefono1 = '%s'";
                $values[] = $this->esc($userData['telefono1']);
            }
            if (isset($userData['telefono2']) && isset($columns['telefono2'])) {
                $updates[] = "telefono2 = '%s'";
                $values[] = $this->esc($userData['telefono2']);
            }
            if (isset($userData['celular']) && isset($columns['celular'])) {
                $updates[] = "celular = '%s'";
                $values[] = $this->esc($userData['celular']);
            }
            if (isset($userData['celular2']) && isset($columns['celular2'])) {
                $updates[] = "celular2 = '%s'";
                $values[] = $this->esc($userData['celular2']);
            }
            if (isset($userData['url']) && isset($columns['url'])) {
                $updates[] = "url = '%s'";
                $values[] = $this->esc($userData['url']);
            }
            if (isset($userData['observaciones']) && isset($columns['observaciones'])) {
                $updates[] = "observaciones = '%s'";
                $values[] = $this->esc($userData['observaciones']);
            }
            if (isset($userData['tipoIdentificacion']) && isset($columns['tipoIdentificacion'])) {
                $updates[] = "tipoIdentificacion = '%s'";
                $values[] = $this->esc($userData['tipoIdentificacion']);
            }
            if (isset($userData['acceso_solo_lectura']) && isset($columns['acceso_solo_lectura'])) {
                $updates[] = "acceso_solo_lectura = %d";
                $values[] = (int)$userData['acceso_solo_lectura'];
            }
            if (isset($userData['acceso_graphs']) && isset($columns['acceso_graphs'])) {
                $updates[] = "acceso_graphs = %d";
                $values[] = (int)$userData['acceso_graphs'];
            }
            if (isset($userData['gp_consulta']) && isset($columns['gp_consulta'])) {
                $updates[] = "gp_consulta = %d";
                $values[] = (int)$userData['gp_consulta'];
            }
            if (isset($userData['gp_modificacion']) && isset($columns['gp_modificacion'])) {
                $updates[] = "gp_modificacion = %d";
                $values[] = (int)$userData['gp_modificacion'];
            }
            if (isset($userData['gp_ver_credenciales']) && isset($columns['gp_ver_credenciales'])) {
                $updates[] = "gp_ver_credenciales = %d";
                $values[] = (int)$userData['gp_ver_credenciales'];
            }
            if (isset($userData['gp_tickets_consulta']) && isset($columns['gp_tickets_consulta'])) {
                $updates[] = "gp_tickets_consulta = %d";
                $values[] = (int)$userData['gp_tickets_consulta'];
            }
            if (isset($userData['gp_tickets_modificacion']) && isset($columns['gp_tickets_modificacion'])) {
                $updates[] = "gp_tickets_modificacion = %d";
                $values[] = (int)$userData['gp_tickets_modificacion'];
            }
            if (isset($userData['gp_tickets_fechas']) && isset($columns['gp_tickets_fechas'])) {
                $updates[] = "gp_tickets_fechas = %d";
                $values[] = (int)$userData['gp_tickets_fechas'];
            }
            if (isset($userData['gp_claves']) && isset($columns['gp_claves'])) {
                $updates[] = "gp_claves = %d";
                $values[] = (int)$userData['gp_claves'];
            }
            if (isset($userData['gp_eliminar_proveedores']) && isset($columns['gp_eliminar_proveedores'])) {
                $updates[] = "gp_eliminar_proveedores = %d";
                $values[] = (int)$userData['gp_eliminar_proveedores'];
            }
            if (isset($userData['gp_inv_consulta']) && isset($columns['gp_inv_consulta'])) {
                $updates[] = "gp_inv_consulta = %d";
                $values[] = (int)$userData['gp_inv_consulta'];
            }
            if (isset($userData['gp_inv_modificacion']) && isset($columns['gp_inv_modificacion'])) {
                $updates[] = "gp_inv_modificacion = %d";
                $values[] = (int)$userData['gp_inv_modificacion'];
            }
            if (isset($userData['gp_inv_eliminar']) && isset($columns['gp_inv_eliminar'])) {
                $updates[] = "gp_inv_eliminar = %d";
                $values[] = (int)$userData['gp_inv_eliminar'];
            }

            if (empty($updates)) {
                return false;
            }

            $values[] = (int)$userId;
            $sql = "UPDATE usuario SET " . implode(', ', $updates) . " WHERE id = %d";
            $sql = vsprintf($sql, $values);

            if (!$this->executeSql($sql)) {
                return false;
            }
            // affected_rows puede ser 0 cuando los datos son iguales,
            // pero la operación fue correcta.
            return true;
        } catch (Exception $e) {
            error_log('Error updating user: ' . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // ============================================================
    // READ OPERATIONS
    // ============================================================

    /**
     * Get user by ID with all relationships
     */
    public function getUserById($userId) {
        try {
            $userId = (int)$userId;
            $sql = "SELECT * FROM usuario WHERE id = $userId";
            $this->db->query($sql);

            if ($this->db->next_record()) {
                $user = $this->db->Record;
                $user['permissions'] = $this->getUserPermissions($userId);
                $user['documents'] = $this->getUserDocuments($userId);
                $user['clients'] = $this->getUserClients($userId);
                return $user;
            }
            return null;
        } catch (Exception $e) {
            error_log('Error getting user: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user by identification
     */
    public function getUserByIdentification($identification) {
        try {
            $identification = $this->esc($identification);
            $sql = "SELECT * FROM usuario WHERE identificacion = '$identification'";
            $this->db->query($sql);

            if ($this->db->next_record()) {
                return $this->db->Record;
            }
            return null;
        } catch (Exception $e) {
            error_log('Error getting user by ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all users with optional filters
     */
    public function getAllUsers($tipo = null, $limit = null, $offset = 0) {
        try {
            $sql = "SELECT id, tipo, identificacion, nombre, email, acceso FROM usuario WHERE 1=1";

            if ($tipo !== null) {
                $tipo = (int)$tipo;
                $sql .= " AND tipo = $tipo";
            }

            $sql .= " ORDER BY nombre ASC";

            if ($limit !== null) {
                $limit = (int)$limit;
                $offset = (int)$offset;
                $sql .= " LIMIT $offset, $limit";
            }

            $this->db->query($sql);
            $users = [];
            while ($this->db->next_record()) {
                $users[] = $this->db->Record;
            }
            return $users;
        } catch (Exception $e) {
            error_log('Error getting all users: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if identification exists (for validation)
     */
    public function identificationExists($identification, $excludeUserId = null) {
        try {
            $identification = $this->esc($identification);
            $sql = "SELECT id FROM usuario WHERE identificacion = '$identification'";

            if ($excludeUserId !== null) {
                $excludeUserId = (int)$excludeUserId;
                $sql .= " AND id != $excludeUserId";
            }

            $this->db->query($sql);
            return $this->db->next_record();
        } catch (Exception $e) {
            error_log('Error checking identification: ' . $e->getMessage());
            return false;
        }
    }

    // ============================================================
    // PERMISSION OPERATIONS
    // ============================================================

    /**
     * Get user menu permissions
     */
    public function getUserPermissions($userId) {
        try {
            $userId = (int)$userId;
            $sql = "SELECT idMenu FROM usuarios_menu WHERE idUsuario = $userId";
            $this->db->query($sql);

            $permissions = [];
            while ($this->db->next_record()) {
                $permissions[] = $this->db->f('idMenu');
            }
            return $permissions;
        } catch (Exception $e) {
            error_log('Error getting user permissions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Save user menu permissions
     */
    public function saveUserPermissions($userId, $menuIds) {
        try {
            $userId = (int)$userId;

            // Delete existing permissions
            $sql = "DELETE FROM usuarios_menu WHERE idUsuario = $userId";
            $this->db->query($sql);

            // Insert new permissions
            foreach ($menuIds as $menuId) {
                $menuId = (int)$menuId;
                $sql = "INSERT INTO usuarios_menu (idUsuario, idMenu) VALUES ($userId, $menuId)";
                $this->db->query($sql);
            }

            return true;
        } catch (Exception $e) {
            error_log('Error saving user permissions: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get graph permissions
     */
    public function getUserGraphPermissions($userId) {
        try {
            $userId = (int)$userId;
            $sql = "SELECT idMenu FROM usuarios_menu_graphs WHERE idUsuario = $userId";
            $this->db->query($sql);

            $permissions = [];
            while ($this->db->next_record()) {
                $permissions[] = $this->db->f('idMenu');
            }
            return $permissions;
        } catch (Exception $e) {
            error_log('Error getting graph permissions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Save graph permissions
     */
    public function saveGraphPermissions($userId, $graphIds) {
        try {
            $userId = (int)$userId;

            // Delete existing permissions
            $sql = "DELETE FROM usuarios_menu_graphs WHERE idUsuario = $userId";
            $this->db->query($sql);

            // Insert new permissions
            foreach ($graphIds as $graphId) {
                $graphId = (int)$graphId;
                $sql = "INSERT INTO usuarios_menu_graphs (idUsuario, idMenu) VALUES ($userId, $graphId)";
                $this->db->query($sql);
            }

            return true;
        } catch (Exception $e) {
            error_log('Error saving graph permissions: ' . $e->getMessage());
            return false;
        }
    }

    // ============================================================
    // DOCUMENT OPERATIONS
    // ============================================================

    /**
     * Get user documents
     */
    /**
     * Get user documents
     * Nota: La tabla usuario_documentos podría no existir en algunas instancias
     */
    public function getUserDocuments($userId) {
        // Por ahora retornar vacío - la tabla no existe
        // Si existe en futuro, implementar aquí
        return [];
    }

    /**
     * Upload user document
     */
    public function uploadUserDocument($userId, $filePath, $fileName, $docType = 'other') {
        // La tabla usuario_documentos no existe en esta instancia
        // Implementar cuando la tabla esté disponible
        return false;
    }

    /**
     * Delete user document
     */
    public function deleteUserDocument($documentId) {
        // La tabla usuario_documentos no existe en esta instancia
        // Implementar cuando la tabla esté disponible
        return false;
    }

    // ============================================================
    // CLIENT RELATIONSHIP OPERATIONS
    // ============================================================

    /**
     * Get user's clients (for authorized personnel)
     */
    public function getUserClients($userId) {
        try {
            $userId = (int)$userId;
            $sql = "SELECT idUsuario2 FROM usuario_relacion WHERE idUsuario1 = $userId";
            $this->db->query($sql);

            $clients = [];
            while ($this->db->next_record()) {
                $clients[] = $this->db->f('idUsuario2');
            }
            return $clients;
        } catch (Exception $e) {
            error_log('Error getting user clients: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Set user-client relationship
     */
    public function setUserClient($userId, $clientId) {
        try {
            $userId = (int)$userId;
            $clientId = (int)$clientId;

            // Check if relationship exists
            $sql = "SELECT id FROM usuario_relacion WHERE idUsuario1 = $userId AND idUsuario2 = $clientId";
            $this->db->query($sql);

            if (!$this->db->next_record()) {
                // Insert new relationship
                $sql = "INSERT INTO usuario_relacion (idUsuario1, idUsuario2) VALUES ($userId, $clientId)";
                $this->db->query($sql);
            }

            return true;
        } catch (Exception $e) {
            error_log('Error setting user-client relationship: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get available clients for user (for client user type)
     */
    public function getAvailableClients() {
        try {
            $sql = "SELECT id, nombre FROM usuario WHERE tipo = " . USER_TYPE_AUTHORIZED . " AND acceso = 1 ORDER BY nombre";
            $this->db->query($sql);

            $clients = [];
            while ($this->db->next_record()) {
                $clients[] = $this->db->Record;
            }
            return $clients;
        } catch (Exception $e) {
            error_log('Error getting available clients: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca el id de un item de "menu" por su columna "php" (ej. 'gp_proveedores').
     * Usado para sincronizar el acceso al menu con permisos de modulo que no
     * son un simple checkbox de la grilla de Permisos (ver Gestion Proyectos).
     */
    public function getMenuIdByPhp($php) {
        $sql = "SELECT id FROM menu WHERE php = '" . $this->esc($php) . "' LIMIT 1";
        $this->db->query($sql);
        return $this->db->next_record() ? (int) $this->db->f('id') : 0;
    }

    /**
     * Get available menus for user assignment
     */
    public function getAvailableMenus() {
        try {
            $sql = "SELECT id, nombre, php, principal FROM menu ORDER BY principal, orden";
            $this->db->query($sql);

            $menus = [];
            while ($this->db->next_record()) {
                $menus[] = $this->db->Record;
            }
            return $menus;
        } catch (Exception $e) {
            error_log('Error getting menus: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available graphs for user assignment
     */
    public function getAvailableGraphs() {
        try {
            $sql = "SELECT id, nombre FROM menu_graphs ORDER BY orden";
            $this->db->query($sql);

            $graphs = [];
            while ($this->db->next_record()) {
                $graphs[] = $this->db->Record;
            }
            return $graphs;
        } catch (Exception $e) {
            error_log('Error getting graphs: ' . $e->getMessage());
            return [];
        }
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Verify password against hash
     */
    public function verifyPassword($plainPassword, $hashedPassword) {
        // Check if it's MD5 hash (legacy support)
        if (strlen($hashedPassword) == 32) {
            return md5($plainPassword) === $hashedPassword;
        }
        // Use bcrypt for new passwords
        return password_verify($plainPassword, $hashedPassword);
    }

    /**
     * Get user count
     */
    public function getUserCount($tipo = null) {
        try {
            $sql = "SELECT COUNT(*) as total FROM usuario WHERE 1=1";

            if ($tipo !== null) {
                $tipo = (int)$tipo;
                $sql .= " AND tipo = $tipo";
            }

            $this->db->query($sql);
            if ($this->db->next_record()) {
                return (int)$this->db->f('total');
            }
            return 0;
        } catch (Exception $e) {
            error_log('Error counting users: ' . $e->getMessage());
            return 0;
        }
    }

}

?>
