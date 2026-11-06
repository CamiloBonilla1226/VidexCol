<?php
/**
 * SMS Grupos Service Layer
 * Handles all database operations with optimized queries
 *
 * @package VIDEX
 * @version 1.0
 */

require_once('sms_grupos_config.php');

class SmsGruposService {
    private $db;

    public function __construct() {
        $this->db = new DBbase_Sql();
    }

    /**
     * Get all groups with user count
     * @param int $offset Pagination offset
     * @param int $limit Records per page
     * @param array $filters Search filters
     * @return array Group records with user count
     */
    public function getGroups($offset, $limit, $filters = []) {
        $where = "";

        if (!empty($filters['nombre'])) {
            $nombre = $this->escapeValue($filters['nombre']);
            $where .= " WHERE " . TABLE_GRUPOS . ".nombre LIKE '%" . $nombre . "%'";
        }

        // Optimized query: single query with LEFT JOIN and GROUP BY (no N+1)
        $sql = "SELECT " . TABLE_GRUPOS . ".id,
                       " . TABLE_GRUPOS . ".nombre,
                       " . TABLE_GRUPOS . ".descripcion,
                       COUNT(" . TABLE_ASOCIACION . ".id_usuario) as conteo
                FROM " . TABLE_GRUPOS . "
                LEFT JOIN " . TABLE_ASOCIACION . " ON " . TABLE_ASOCIACION . ".id_grupo = " . TABLE_GRUPOS . ".id
                " . $where . "
                GROUP BY " . TABLE_GRUPOS . ".id
                ORDER BY " . TABLE_GRUPOS . ".nombre ASC
                LIMIT " . intval($offset) . ", " . intval($limit);

        $result = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $result[] = [
                'id' => $this->db->f('id'),
                'nombre' => $this->db->f('nombre'),
                'descripcion' => $this->db->f('descripcion'),
                'conteo' => intval($this->db->f('conteo'))
            ];
        }

        return $result;
    }

    /**
     * Count total groups matching filters
     * @param array $filters Search filters
     * @return int Total count
     */
    public function getGroupCount($filters = []) {
        $sql = "SELECT COUNT(DISTINCT " . TABLE_GRUPOS . ".id) as conteo
                FROM " . TABLE_GRUPOS . "
                WHERE 1=1";

        if (!empty($filters['nombre'])) {
            $nombre = $this->escapeValue($filters['nombre']);
            $sql .= " AND " . TABLE_GRUPOS . ".nombre LIKE '%" . $nombre . "%'";
        }

        $this->db->query($sql);
        if ($this->db->next_record()) {
            return intval($this->db->f('conteo'));
        }
        return 0;
    }

    /**
     * Get group by ID
     * @param int $id Group ID
     * @return array|null Group data or null
     */
    public function getGroupById($id) {
        $id = intval($id);
        $sql = "SELECT " . TABLE_GRUPOS . ".*,
                       COUNT(" . TABLE_ASOCIACION . ".id_usuario) as conteo
                FROM " . TABLE_GRUPOS . "
                LEFT JOIN " . TABLE_ASOCIACION . " ON " . TABLE_ASOCIACION . ".id_grupo = " . TABLE_GRUPOS . ".id
                WHERE " . TABLE_GRUPOS . ".id = " . $id . "
                GROUP BY " . TABLE_GRUPOS . ".id";

        $this->db->query($sql);
        if ($this->db->next_record()) {
            return [
                'id' => $this->db->f('id'),
                'nombre' => $this->db->f('nombre'),
                'descripcion' => $this->db->f('descripcion'),
                'conteo' => intval($this->db->f('conteo'))
            ];
        }
        return null;
    }

    /**
     * Create new group
     * @param array $data Group data
     * @return int|bool New group ID or false
     */
    public function createGroup($data) {
        $nombre = $this->escapeValue($data['nombre']);
        $descripcion = $this->escapeValue($data['descripcion'] ?? '');

        $sql = "INSERT INTO " . TABLE_GRUPOS . " (nombre, descripcion)
                VALUES ('" . $nombre . "', '" . $descripcion . "')";

        $result = $this->db->query($sql);

        if ($result) {
            return $this->db->ultimoId();
        }
        return false;
    }

    /**
     * Update group
     * @param int $id Group ID
     * @param array $data Group data
     * @return bool Success
     */
    public function updateGroup($id, $data) {
        $id = intval($id);
        $nombre = $this->escapeValue($data['nombre']);
        $descripcion = $this->escapeValue($data['descripcion'] ?? '');

        $sql = "UPDATE " . TABLE_GRUPOS . " SET
                    nombre = '" . $nombre . "',
                    descripcion = '" . $descripcion . "'
                WHERE id = " . $id;

        return $this->db->query($sql);
    }

    /**
     * Add user to group (bulk insert)
     * @param int $groupId Group ID
     * @param array $userData User data array
     * @return int|bool User ID or false
     */
    public function addUserToGroup($groupId, $userData) {
        $groupId = intval($groupId);

        // Create user
        $sql = "INSERT INTO " . TABLE_USUARIOS . " (
                    nombres, identificacion, direccion, paginaWeb,
                    email, telfijo, celular, celular2
                ) VALUES (
                    '" . $this->escapeValue($userData['nombres']) . "',
                    '" . $this->escapeValue($userData['identificacion']) . "',
                    '" . $this->escapeValue($userData['direccion']) . "',
                    '" . $this->escapeValue($userData['paginaWeb']) . "',
                    '" . $this->escapeValue($userData['email']) . "',
                    '" . $this->escapeValue($userData['telfijo']) . "',
                    '" . $this->escapeValue($userData['celular']) . "',
                    '" . $this->escapeValue($userData['celular2']) . "'
                )";

        $userCreated = $this->db->query($sql);
        if (!$userCreated) {
            return false;
        }

        $userId = $this->db->ultimoId();
        if (intval($userId) <= 0) {
            return false;
        }

        // Associate user with group
        $sql = "INSERT INTO " . TABLE_ASOCIACION . " (id_grupo, id_usuario)
                VALUES (" . $groupId . ", " . intval($userId) . ")";

        if ($this->db->query($sql)) {
            return $userId;
        }
        return false;
    }

    /**
     * Clear all users from group
     * @param int $groupId Group ID
     * @return bool Success
     */
    public function clearGroupUsers($groupId) {
        $groupId = intval($groupId);

        $sql = "DELETE sms_asociacion, sms_usuarios
                FROM " . TABLE_ASOCIACION . "
                INNER JOIN " . TABLE_USUARIOS . " ON " . TABLE_USUARIOS . ".id = " . TABLE_ASOCIACION . ".id_usuario
                WHERE " . TABLE_ASOCIACION . ".id_grupo = " . $groupId;

        return $this->db->query($sql);
    }

    /**
     * Escape value for safe SQL query
     * @param string $value Value to escape
     * @return string Escaped value
     */
    private function escapeValue($value) {
        $value = trim($value);
        $value = stripslashes($value);
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return $value;
    }
}
?>
