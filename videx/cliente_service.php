<?php
/**
 * Cliente Service Layer
 * Handles all database operations with proper query building
 *
 * FIXES:
 * - $sql undefined variable bug (line 651 original)
 * - Proper WHERE clause building for filters
 * - Consistent query structure
 *
 * @package VIDEX
 * @version 1.0
 */

require_once('cliente_config.php');

class ClienteService {
    private $db;

    public function __construct() {
        $this->db = new DBbase_Sql();
    }

    /**
     * Get all client types from categorias table
     */
    public function getClientTypes() {
        $sql = "SELECT categorias.id, categorias.descripcion
                FROM categorias
                WHERE categorias.idSec = " . CLIENT_TYPE_CATEGORY_ID . "
                ORDER BY categorias.descripcion ASC";

        $result = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $result[$this->db->f('id')] = $this->db->f('descripcion');
        }

        return $result;
    }

    /**
     * Get all commercials (usuarios with profile 162)
     */
    public function getCommercials($restrict_to_id = null) {
        $sql = "SELECT usuario.id, usuario.nombre
                FROM usuario
                WHERE usuario.tipo = 162";

        if ($restrict_to_id !== null) {
            $sql .= " AND usuario.id = " . intval($restrict_to_id);
        }

        $sql .= " ORDER BY usuario.nombre ASC";

        $result = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $result[$this->db->f('id')] = $this->db->f('nombre');
        }

        return $result;
    }

    /**
     * Count total clients matching filters
     * FIX: Properly initialize WHERE clause (bug line 651)
     */
    public function getClientCount($filters = []) {
        $sql = "SELECT COUNT(*) as total
                FROM cliente
                WHERE cliente.estado != 0";  // Exclude soft-deleted

        $sql .= $this->buildFilterWhere($filters);

        $this->db->query($sql);
        if ($this->db->next_record()) {
            return intval($this->db->f('total'));
        }
        return 0;
    }

    /**
     * Get clients with pagination and filters
     * Includes user creation/modification info
     */
    public function getClients($offset, $limit, $filters = [], $comercial_restriction = null) {
        $sql = "SELECT cliente.*,
                       categorias.descripcion as tipoClienteNom,
                       usuario.nombre as creadorNom,
                       modif.nombre as modNom
                FROM cliente
                LEFT JOIN categorias ON categorias.id = cliente.tipoCliente
                LEFT JOIN usuario ON usuario.id = cliente.creacionUsuario
                LEFT JOIN usuario modif ON modif.id = cliente.modUsuario
                WHERE cliente.estado != 0";  // Exclude soft-deleted

        $sql .= $this->buildFilterWhere($filters);

        if ($comercial_restriction !== null) {
            $sql .= " AND cliente.idComercial = " . intval($comercial_restriction);
        }

        $sql .= " ORDER BY cliente.nombre ASC
                LIMIT " . intval($offset) . ", " . intval($limit);

        $result = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $result[] = [
                'id' => $this->db->f('id'),
                'nombre' => $this->db->f('nombre'),
                'nit' => $this->db->f('nit'),
                'representante_legal' => $this->db->f('representante_legal'),
                'direccion' => $this->db->f('direccion'),
                'telefono' => $this->db->f('telefono'),
                'celular' => $this->db->f('celular'),
                'email' => $this->db->f('email'),
                'estado' => $this->db->f('estado'),
                'tipoCliente' => $this->db->f('tipoCliente'),
                'tipoClienteNom' => $this->db->f('tipoClienteNom'),
                'interno' => $this->db->f('interno'),
                'observaciones' => $this->db->f('observaciones'),
                'paginaweb' => $this->db->f('paginaweb'),
                'idComercial' => $this->db->f('idComercial'),
                'creacionUsuario' => $this->db->f('creacionUsuario'),
                'creacionFecha' => $this->db->f('creacionFecha'),
                'creadorNom' => $this->db->f('creadorNom'),
                'modUsuario' => $this->db->f('modUsuario'),
                'modFecha' => $this->db->f('modFecha'),
                'modNom' => $this->db->f('modNom')
            ];
        }

        return $result;
    }

    /**
     * Get single client by ID
     */
    public function getClientById($id) {
        $id = intval($id);
        $sql = "SELECT cliente.*,
                       categorias.descripcion as tipoClienteNom,
                       usuario.nombre as creadorNom,
                       modif.nombre as modNom
                FROM cliente
                LEFT JOIN categorias ON categorias.id = cliente.tipoCliente
                LEFT JOIN usuario ON usuario.id = cliente.creacionUsuario
                LEFT JOIN usuario modif ON modif.id = cliente.modUsuario
                WHERE cliente.id = " . $id . "
                AND cliente.estado != 0";

        $this->db->query($sql);
        if ($this->db->next_record()) {
            return [
                'id' => $this->db->f('id'),
                'nombre' => $this->db->f('nombre'),
                'nit' => $this->db->f('nit'),
                'representante_legal' => $this->db->f('representante_legal'),
                'direccion' => $this->db->f('direccion'),
                'telefono' => $this->db->f('telefono'),
                'celular' => $this->db->f('celular'),
                'email' => $this->db->f('email'),
                'estado' => $this->db->f('estado'),
                'tipoCliente' => $this->db->f('tipoCliente'),
                'tipoClienteNom' => $this->db->f('tipoClienteNom'),
                'interno' => $this->db->f('interno'),
                'observaciones' => $this->db->f('observaciones'),
                'paginaweb' => $this->db->f('paginaweb'),
                'idComercial' => $this->db->f('idComercial'),
                'creacionUsuario' => $this->db->f('creacionUsuario'),
                'creacionFecha' => $this->db->f('creacionFecha'),
                'creadorNom' => $this->db->f('creadorNom'),
                'modUsuario' => $this->db->f('modUsuario'),
                'modFecha' => $this->db->f('modFecha'),
                'modNom' => $this->db->f('modNom')
            ];
        }
        return null;
    }

    /**
     * Create new client
     */
    public function createClient($data) {
        $idComercialSql = (trim($data['idComercial'] ?? '') === '')
            ? "NULL"
            : "'" . $this->escapeValue(trim($data['idComercial'])) . "'";
        $sql = "INSERT INTO cliente (
                    nombre,
                    nit,
                    representante_legal,
                    direccion,
                    telefono,
                    celular,
                    email,
                    estado,
                    tipoCliente,
                    interno,
                    observaciones,
                    paginaweb,
                    idComercial,
                    creacionUsuario,
                    creacionFecha
                ) VALUES (
                    '" . $this->escapeValue($data['nombre']) . "',
                    '" . $this->escapeValue($data['nit']) . "',
                    '" . $this->escapeValue($data['representante_legal']) . "',
                    '" . $this->escapeValue($data['direccion']) . "',
                    '" . $this->escapeValue($data['telefono']) . "',
                    '" . $this->escapeValue($data['celular']) . "',
                    '" . $this->escapeValue($data['email']) . "',
                    " . intval($data['estado']) . ",
                    " . intval($data['tipoCliente']) . ",
                    " . intval($data['interno']) . ",
                    '" . $this->escapeValue($data['observaciones']) . "',
                    '" . $this->escapeValue($data['paginaweb']) . "',
                    " . $idComercialSql . ",
                    " . intval($data['creacionUsuario']) . ",
                    NOW()
                )";

        $result = $this->db->query($sql);
        return $this->db->ultimoId();
    }

    /**
     * Update existing client
     */
    public function updateClient($id, $data) {
        $id = intval($id);
        $idComercialSql = (trim($data['idComercial'] ?? '') === '')
            ? "NULL"
            : "'" . $this->escapeValue(trim($data['idComercial'])) . "'";
        $sql = "UPDATE cliente SET
                    nombre = '" . $this->escapeValue($data['nombre']) . "',
                    nit = '" . $this->escapeValue($data['nit']) . "',
                    representante_legal = '" . $this->escapeValue($data['representante_legal']) . "',
                    direccion = '" . $this->escapeValue($data['direccion']) . "',
                    telefono = '" . $this->escapeValue($data['telefono']) . "',
                    celular = '" . $this->escapeValue($data['celular']) . "',
                    email = '" . $this->escapeValue($data['email']) . "',
                    estado = " . intval($data['estado']) . ",
                    tipoCliente = " . intval($data['tipoCliente']) . ",
                    interno = " . intval($data['interno']) . ",
                    observaciones = '" . $this->escapeValue($data['observaciones']) . "',
                    paginaweb = '" . $this->escapeValue($data['paginaweb']) . "',
                    idComercial = " . $idComercialSql . ",
                    modUsuario = " . intval($data['modUsuario']) . ",
                    modFecha = NOW()
                WHERE id = " . $id;

        return $this->db->query($sql);
    }

    /**
     * Soft delete client (set estado = 0)
     */
    public function deleteClient($id) {
        $id = intval($id);
        $sql = "UPDATE cliente SET estado = 0 WHERE id = " . $id;
        return $this->db->query($sql);
    }

    /**
     * Check if client name already exists (except current client)
     */
    public function clientNameExists($name, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as cnt FROM cliente
                WHERE nombre = '" . $this->escapeValue($name) . "'
                AND estado != 0";

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
     * FIX: Initialize $where properly (bug from original line 651)
     */
    private function buildFilterWhere($filters) {
        $where = "";  // INITIALIZE EMPTY (was undefined in original!)

        if (!empty($filters['nombre'])) {
            $where .= " AND cliente.nombre LIKE '%" . $this->escapeValue($filters['nombre']) . "%'";
        }

        if (!empty($filters['nit'])) {
            $where .= " AND cliente.nit LIKE '%" . $this->escapeValue($filters['nit']) . "%'";
        }

        if (!empty($filters['tipoCliente'])) {
            $where .= " AND cliente.tipoCliente = " . intval($filters['tipoCliente']);
        }

        if (!empty($filters['idComercial'])) {
            $where .= " AND cliente.idComercial = " . intval($filters['idComercial']);
        }

        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $where .= " AND cliente.estado = " . intval($filters['estado']);
        }

        if (!empty($filters['interno'])) {
            $where .= " AND cliente.interno = " . intval($filters['interno']);
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
