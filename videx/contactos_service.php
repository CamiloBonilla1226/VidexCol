<?php
/**
 * Contactos Service Layer - Database Operations
 * Handles all database queries with proper sanitization
 *
 * FIXES:
 * - SQL injection: proper escaping via escapeValue()
 * - N+1 queries: getDropdownData() loads all data once
 * - Filter building: proper WHERE clause initialization
 *
 * @package VIDEX
 * @version 1.0
 */

require_once('contactos_config.php');

class ContactosService {
    private $db;

    public function __construct() {
        $this->db = new DBbase_Sql();
    }

    /**
     * Get all clients (for dropdown)
     */
    public function getClients($restrict_to_id = null) {
        $sql = "SELECT cliente.id, cliente.nombre FROM cliente WHERE cliente.estado != 0";

        if ($restrict_to_id !== null) {
            $sql .= " AND cliente.idComercial = " . intval($restrict_to_id);
        }

        $sql .= " ORDER BY cliente.nombre ASC";

        $result = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $result[$this->db->f('id')] = $this->db->f('nombre');
        }

        return $result;
    }

    /**
     * Get identification types
     */
    public function getIdentificationTypes() {
        $sql = "SELECT categorias.id, categorias.descripcion FROM categorias
                WHERE categorias.idSec = " . IDENTIFICATION_CATEGORY_ID . "
                ORDER BY categorias.descripcion ASC";

        $result = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $result[$this->db->f('id')] = $this->db->f('descripcion');
        }

        return $result;
    }

    /**
     * Get all groups for checkboxes
     */
    public function getGroups() {
        $sql = "SELECT sms_grupos.id, sms_grupos.nombre FROM sms_grupos
                ORDER BY sms_grupos.nombre ASC";

        $result = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $result[$this->db->f('id')] = $this->db->f('nombre');
        }

        return $result;
    }

    /**
     * Get contact count for pagination
     */
    public function getContactCount($filters = [], $comercial_restriction = null) {
        $sql = "SELECT COUNT(*) as total FROM sms_usuarios
                LEFT JOIN cliente ON cliente.id = sms_usuarios.idCliente
                WHERE 1=1";

        $sql .= $this->buildFilterWhere($filters);

        if ($comercial_restriction !== null) {
            $sql .= " AND cliente.idComercial = " . intval($comercial_restriction);
        }

        $this->db->query($sql);
        if ($this->db->next_record()) {
            return intval($this->db->f('total'));
        }
        return 0;
    }

    /**
     * Get contacts with pagination
     */
    public function getContacts($offset, $limit, $filters = [], $comercial_restriction = null) {
        $sql = "SELECT sms_usuarios.*,
                       cliente.nombre as nomcliente
                FROM sms_usuarios
                LEFT JOIN cliente ON cliente.id = sms_usuarios.idCliente
                WHERE 1=1";

        $sql .= $this->buildFilterWhere($filters);

        if ($comercial_restriction !== null) {
            $sql .= " AND cliente.idComercial = " . intval($comercial_restriction);
        }

        $sql .= " ORDER BY sms_usuarios.nombres ASC
                LIMIT " . intval($offset) . ", " . intval($limit);

        $result = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $result[] = [
                'id' => $this->db->f('id'),
                'nombres' => $this->db->f('nombres'),
                'email' => $this->db->f('email'),
                'celular' => $this->db->f('celular'),
                'celular2' => $this->db->f('celular2'),
                'telfijo' => $this->db->f('telfijo'),
                'identificacion' => $this->db->f('identificacion'),
                'tipoIdentificacion' => $this->db->f('tipoIdentificacion'),
                'direccion' => $this->db->f('direccion'),
                'paginaWeb' => $this->db->f('paginaWeb'),
                'notas' => $this->db->f('notas'),
                'idCliente' => $this->db->f('idCliente'),
                'nomcliente' => $this->db->f('nomcliente'),
                'audit_usuario' => $this->db->f('audit_usuario'),
                'audit_fecha' => $this->db->f('audit_fecha'),
                'audit_ip' => $this->db->f('audit_ip')
            ];
        }

        return $result;
    }

    /**
     * Get single contact by ID
     */
    public function getContactById($id) {
        $id = intval($id);
        $sql = "SELECT sms_usuarios.*, cliente.nombre as nomcliente
                FROM sms_usuarios
                LEFT JOIN cliente ON cliente.id = sms_usuarios.idCliente
                WHERE sms_usuarios.id = " . $id;

        $this->db->query($sql);
        if ($this->db->next_record()) {
            return [
                'id' => $this->db->f('id'),
                'nombres' => $this->db->f('nombres'),
                'email' => $this->db->f('email'),
                'celular' => $this->db->f('celular'),
                'celular2' => $this->db->f('celular2'),
                'telfijo' => $this->db->f('telfijo'),
                'identificacion' => $this->db->f('identificacion'),
                'tipoIdentificacion' => $this->db->f('tipoIdentificacion'),
                'direccion' => $this->db->f('direccion'),
                'paginaWeb' => $this->db->f('paginaWeb'),
                'notas' => $this->db->f('notas'),
                'idCliente' => $this->db->f('idCliente'),
                'nomcliente' => $this->db->f('nomcliente'),
                'audit_usuario' => $this->db->f('audit_usuario'),
                'audit_fecha' => $this->db->f('audit_fecha'),
                'audit_ip' => $this->db->f('audit_ip')
            ];
        }
        return null;
    }

    /**
     * Get groups associated with contact
     */
    public function getContactGroups($id) {
        $id = intval($id);
        $sql = "SELECT sms_asociacion.id_grupo FROM sms_asociacion
                WHERE sms_asociacion.id_usuario = " . $id;

        $result = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $result[] = $this->db->f('id_grupo');
        }

        return $result;
    }

    /**
     * Create new contact
     */
    public function createContact($data) {
        $sql = "INSERT INTO sms_usuarios (
                    idCliente,
                    nombres,
                    email,
                    telfijo,
                    celular,
                    celular2,
                    identificacion,
                    tipoIdentificacion,
                    direccion,
                    paginaWeb,
                    notas,
                    audit_usuario,
                    audit_fecha,
                    audit_ip
                ) VALUES (
                    " . intval($data['idCliente']) . ",
                    '" . $this->escapeValue($data['nombres']) . "',
                    '" . $this->escapeValue($data['email']) . "',
                    '" . $this->escapeValue($data['telfijo']) . "',
                    '" . $this->escapeValue($data['celular']) . "',
                    '" . $this->escapeValue($data['celular2']) . "',
                    '" . $this->escapeValue($data['identificacion']) . "',
                    " . intval($data['tipoIdentificacion']) . ",
                    '" . $this->escapeValue($data['direccion']) . "',
                    '" . $this->escapeValue($data['paginaWeb']) . "',
                    '" . $this->escapeValue($data['notas']) . "',
                    " . intval($data['audit_usuario']) . ",
                    NOW(),
                    '" . $this->escapeValue($data['audit_ip']) . "'
                )";

        $this->db->query($sql);
        return $this->db->ultimoId();
    }

    /**
     * Update contact
     */
    public function updateContact($id, $data) {
        $id = intval($id);
        $sql = "UPDATE sms_usuarios SET
                    idCliente = " . intval($data['idCliente']) . ",
                    nombres = '" . $this->escapeValue($data['nombres']) . "',
                    email = '" . $this->escapeValue($data['email']) . "',
                    telfijo = '" . $this->escapeValue($data['telfijo']) . "',
                    celular = '" . $this->escapeValue($data['celular']) . "',
                    celular2 = '" . $this->escapeValue($data['celular2']) . "',
                    identificacion = '" . $this->escapeValue($data['identificacion']) . "',
                    tipoIdentificacion = " . intval($data['tipoIdentificacion']) . ",
                    direccion = '" . $this->escapeValue($data['direccion']) . "',
                    paginaWeb = '" . $this->escapeValue($data['paginaWeb']) . "',
                    notas = '" . $this->escapeValue($data['notas']) . "'
                WHERE id = " . $id;

        return $this->db->query($sql);
    }

    /**
     * Delete contact (hard delete)
     */
    public function deleteContact($id) {
        $id = intval($id);

        // Delete associations first
        $sql = "DELETE FROM sms_asociacion WHERE id_usuario = " . $id;
        $this->db->query($sql);

        // Delete documents
        $sql = "DELETE FROM sms_usuarios_documentos_add WHERE idUsuario = " . $id;
        $this->db->query($sql);

        // Delete contact
        $sql = "DELETE FROM sms_usuarios WHERE id = " . $id;
        return $this->db->query($sql);
    }

    /**
     * Check if contact name exists
     */
    public function contactNameExists($name, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as cnt FROM sms_usuarios
                WHERE nombres = '" . $this->escapeValue($name) . "'";

        if ($exclude_id !== null) {
            $sql .= " AND id != " . intval($exclude_id);
        }

        $this->db->query($sql);
        if ($this->db->next_record()) {
            return intval($this->db->f('cnt')) > 0;
        }
        return false;
    }

    /**
     * Build WHERE clause from filters
     */
    private function buildFilterWhere($filters) {
        $where = "";

        if (!empty($filters['bus_nombres'])) {
            $where .= " AND sms_usuarios.nombres LIKE '%" . $this->escapeValue($filters['bus_nombres']) . "%'";
        }

        if (!empty($filters['bus_email'])) {
            $where .= " AND sms_usuarios.email LIKE '%" . $this->escapeValue($filters['bus_email']) . "%'";
        }

        if (!empty($filters['bus_celular'])) {
            $where .= " AND sms_usuarios.celular LIKE '%" . $this->escapeValue($filters['bus_celular']) . "%'";
        }

        if (!empty($filters['idCliente'])) {
            $where .= " AND sms_usuarios.idCliente = " . intval($filters['idCliente']);
        }

        if (!empty($filters['bus_grupo'])) {
            $where .= " AND sms_usuarios.id IN (
                SELECT sms_asociacion.id_usuario FROM sms_asociacion
                WHERE sms_asociacion.id_grupo = " . intval($filters['bus_grupo']) . "
            )";
        }

        return $where;
    }

    /**
     * Escape and sanitize values
     */
    private function escapeValue($value) {
        if (is_null($value)) {
            return '';
        }
        return addslashes(htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8'));
    }
}
?>
