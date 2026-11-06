<?php
/**
 * Campaign Service Layer
 * Handles all database operations with optimized queries
 *
 * CRITICAL FIX: N+1 Problem eliminated by using single query with GROUP BY
 * Before: 51 queries/page (1 main + 50 mail_log queries)
 * After: 1-2 queries/page (1 main with all JOINs + 1 count query)
 *
 * @package VIDEX
 * @version 1.0
 */

require_once('campanha_config.php');

class CampanaService {
    private $db;

    public function __construct() {
        $this->db = new DBbase_Sql();
    }

    /**
     * Get all campaign types from categorias table
     * @return array Associative array [id => description]
     */
    public function getCategories() {
        $sql = "SELECT categorias.id, categorias.descripcion
                FROM categorias
                WHERE categorias.idSec = " . CAMPAIGN_TYPE_CATEGORY_ID . "
                ORDER BY categorias.descripcion ASC";

        $result = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $result[$this->db->f('id')] = $this->db->f('descripcion');
        }

        return $result;
    }

    /**
     * Count total campaigns matching filters (for pagination)
     * @param array $filters Search filters
     * @return int Total count
     */
    public function getCampaignCount($filters = []) {
        $sql = "SELECT COUNT(DISTINCT campana.id) as conteo
                FROM campana
                WHERE 1=1";

        $sql .= $this->buildFilterWhere($filters);

        $this->db->query($sql);
        if ($this->db->next_record()) {
            return intval($this->db->f('conteo'));
        }
        return 0;
    }

    /**
     * Get campaigns with pagination and filters
     *
     * OPTIMIZATION: Fetch mail/sms/mail_log stats in SINGLE query with GROUP BY
     * This eliminates the N+1 query problem that was in original lines 622-634
     *
     * Fixed MySQL 5.7+ ONLY_FULL_GROUP_BY compatibility by:
     * - Using SUM() instead of COUNT(DISTINCT) for aggregations
     * - Using COALESCE() for NULL handling
     * - Listing all non-aggregated columns explicitly
     *
     * @param int $offset Pagination offset
     * @param int $limit Records per page
     * @param array $filters Search filters
     * @return array Campaign records with all stats aggregated
     */
    public function getCampaigns($offset, $limit, $filters = []) {
        $sql = "SELECT campana.id,
                       campana.nombre,
                       campana.tipoCampana,
                       campana.estado,
                       campana.fechaInicial,
                       campana.fechaFinal,
                       campana.observaciones,
                       campana.creacionUsuario,
                       campana.creacionFecha,
                       campana.modUsuario,
                       campana.modFecha,
                       categorias.descripcion as nomTipoCampana,
                       usuario.nombre as creacionUsuarioNom,
                       modif.nombre as modUsuarioNom,
                       COALESCE(SUM(mail_historico.correos), 0) as totalCorreos,
                       COALESCE(SUM(sms_historico.mensajes), 0) as totalSMS,
                       COALESCE(SUM(CASE WHEN mail_log.visto = 1 THEN 1 ELSE 0 END), 0) as totalLeidos
                FROM campana
                LEFT JOIN categorias ON categorias.id = campana.tipoCampana
                LEFT JOIN usuario ON usuario.id = campana.creacionUsuario
                LEFT JOIN usuario modif ON modif.id = campana.modUsuario
                LEFT JOIN mail_historico ON mail_historico.idCampana = campana.id
                LEFT JOIN sms_historico ON sms_historico.idCampana = campana.id
                LEFT JOIN mail_log ON mail_log.idCampana = campana.id
                WHERE 1=1";

        $sql .= $this->buildFilterWhere($filters);
        $sql .= " GROUP BY campana.id";
        $sql .= " ORDER BY campana.nombre ASC";
        $sql .= " LIMIT " . intval($offset) . ", " . intval($limit);

        $result = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $result[] = [
                'id' => $this->db->f('id'),
                'nombre' => $this->db->f('nombre'),
                'tipoCampana' => $this->db->f('tipoCampana'),
                'nomTipoCampana' => $this->db->f('nomTipoCampana'),
                'estado' => $this->db->f('estado'),
                'fechaInicial' => $this->db->f('fechaInicial'),
                'fechaFinal' => $this->db->f('fechaFinal'),
                'observaciones' => $this->db->f('observaciones'),
                'totalCorreos' => intval($this->db->f('totalCorreos')),
                'totalSMS' => intval($this->db->f('totalSMS')),
                'totalLeidos' => intval($this->db->f('totalLeidos')),
                'creacionUsuario' => $this->db->f('creacionUsuario'),
                'creacionUsuarioNom' => $this->db->f('creacionUsuarioNom'),
                'creacionFecha' => $this->db->f('creacionFecha'),
                'modUsuario' => $this->db->f('modUsuario'),
                'modUsuarioNom' => $this->db->f('modUsuarioNom'),
                'modFecha' => $this->db->f('modFecha'),
            ];
        }

        return $result;
    }

    /**
     * Get single campaign by ID (for edit view)
     * @param int $id Campaign ID
     * @return array|null Campaign data or null if not found
     */
    public function getCampaignById($id) {
        $id = intval($id);
        $sql = "SELECT campana.*,
                       usuario.nombre as creacionUsuarioNom,
                       modif.nombre as modUsuarioNom
                FROM campana
                LEFT JOIN usuario ON usuario.id = campana.creacionUsuario
                LEFT JOIN usuario modif ON modif.id = campana.modUsuario
                WHERE campana.id = " . $id;

        $this->db->query($sql);

        if ($this->db->next_record()) {
            return [
                'id' => $this->db->f('id'),
                'nombre' => $this->db->f('nombre'),
                'fechaInicial' => $this->db->f('fechaInicial'),
                'fechaFinal' => $this->db->f('fechaFinal'),
                'estado' => $this->db->f('estado'),
                'tipoCampana' => $this->db->f('tipoCampana'),
                'observaciones' => $this->db->f('observaciones'),
                'creacionUsuario' => $this->db->f('creacionUsuario'),
                'creacionUsuarioNom' => $this->db->f('creacionUsuarioNom'),
                'creacionFecha' => $this->db->f('creacionFecha'),
                'modUsuario' => $this->db->f('modUsuario'),
                'modUsuarioNom' => $this->db->f('modUsuarioNom'),
                'modFecha' => $this->db->f('modFecha'),
            ];
        }

        return null;
    }

    /**
     * Check if campaign name exists (for duplicate prevention)
     * @param string $nombre Campaign name
     * @param int $exclude_id Exclude campaign ID (for edit case)
     * @return bool True if exists
     */
    public function campaignNameExists($nombre, $exclude_id = null) {
        $nombre = $this->escapeValue($nombre);
        $sql = "SELECT campana.id FROM campana WHERE campana.nombre = '" . $nombre . "'";

        if ($exclude_id) {
            $sql .= " AND campana.id != " . intval($exclude_id);
        }

        $this->db->query($sql);
        return $this->db->next_record();
    }

    /**
     * Create new campaign
     * @param array $data Campaign data
     * @return int|bool Last insert ID or false on failure
     */
    public function createCampaign($data) {
        $nombre = $this->escapeValue($data['nombre']);
        $fechaInicial = $this->escapeValue($data['fechaInicial']);
        $fechaFinal = $this->escapeValue($data['fechaFinal']);
        $estado = intval($data['estado']);
        $tipoCampana = intval($data['tipoCampana']);
        $observaciones = $this->escapeValue($data['observaciones'] ?? '');
        $creacionUsuario = intval($data['creacionUsuario']);

        $sql = "INSERT INTO " . TABLE_NAME . " (
                    nombre, fechaInicial, fechaFinal, estado, tipoCampana,
                    observaciones, creacionUsuario, creacionFecha
                ) VALUES (
                    '" . $nombre . "',
                    '" . $fechaInicial . "',
                    '" . $fechaFinal . "',
                    " . $estado . ",
                    " . $tipoCampana . ",
                    '" . $observaciones . "',
                    " . $creacionUsuario . ",
                    NOW()
                )";

        $result = $this->db->query($sql);
        if ($result) {
            return mysql_insert_id();
        }
        return false;
    }

    /**
     * Update campaign
     * @param int $id Campaign ID
     * @param array $data Updated data
     * @return bool Success
     */
    public function updateCampaign($id, $data) {
        $id = intval($id);
        $nombre = $this->escapeValue($data['nombre']);
        $fechaInicial = $this->escapeValue($data['fechaInicial']);
        $fechaFinal = $this->escapeValue($data['fechaFinal']);
        $estado = intval($data['estado']);
        $tipoCampana = intval($data['tipoCampana']);
        $observaciones = $this->escapeValue($data['observaciones'] ?? '');
        $modUsuario = intval($data['modUsuario']);

        $sql = "UPDATE " . TABLE_NAME . " SET
                    nombre = '" . $nombre . "',
                    fechaInicial = '" . $fechaInicial . "',
                    fechaFinal = '" . $fechaFinal . "',
                    estado = " . $estado . ",
                    tipoCampana = " . $tipoCampana . ",
                    observaciones = '" . $observaciones . "',
                    modUsuario = " . $modUsuario . ",
                    modFecha = NOW()
                WHERE id = " . $id;

        return $this->db->query($sql);
    }

    /**
     * Soft delete campaign (set estado = 0) and cancel any messages
     * still pending for it in the send queue
     * @param int $id Campaign ID
     * @return array ['success' => bool, 'queue_cancelled' => int]
     */
    public function deleteCampaign($id) {
        $id = intval($id);
        $modUsuario = intval($_SESSION['id']);

        $sql = "UPDATE " . TABLE_NAME . " SET
                    estado = " . CAMPAIGN_STATUS_DELETED . ",
                    modFecha = NOW(),
                    modUsuario = " . $modUsuario . "
                WHERE id = " . $id;

        if (!$this->db->query($sql)) {
            return ['success' => false, 'queue_cancelled' => 0];
        }

        return ['success' => true, 'queue_cancelled' => $this->cancelQueuedMessages($id)];
    }

    /**
     * Remove pending queued messages for a campaign (stops further sending)
     * @param int $id Campaign ID
     * @return int Number of queued messages removed
     */
    public function cancelQueuedMessages($id) {
        $id = intval($id);

        $countDb = new DBbase_Sql();
        $countDb->query("SELECT COUNT(id) AS total FROM mail_workqueue WHERE idCampana = " . $id);
        $pending = 0;
        if ($countDb->num_rows() > 0 && $countDb->next_record()) {
            $pending = (int)$countDb->f('total');
        }

        if ($pending > 0) {
            $delDb = new DBbase_Sql();
            $delDb->query("DELETE FROM mail_workqueue WHERE idCampana = " . $id);
        }

        return $pending;
    }

    /**
     * Build WHERE clause from filters
     * @param array $filters Filter parameters
     * @return string WHERE clause fragment
     */
    private function buildFilterWhere($filters = []) {
        $where = "";

        if (!empty($filters['nombre'])) {
            $nombre = $this->escapeValue($filters['nombre']);
            $where .= " AND campana.nombre LIKE '%" . $nombre . "%'";
        }

        if (!empty($filters['tipoCampana'])) {
            $tipoCampana = intval($filters['tipoCampana']);
            $where .= " AND campana.tipoCampana = " . $tipoCampana;
        }

        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $estado = intval($filters['estado']);
            $where .= " AND campana.estado = " . $estado;
        }

        if (!empty($filters['fechaInicial'])) {
            $fecha = $this->escapeValue($filters['fechaInicial']);
            $where .= " AND campana.fechaInicial >= '" . $fecha . "'";
        }

        if (!empty($filters['fechaFinal'])) {
            $fecha = $this->escapeValue($filters['fechaFinal']);
            $where .= " AND campana.fechaFinal <= '" . $fecha . "'";
        }

        return $where;
    }

    /**
     * Escape string value for SQL
     * (Temporary solution - should migrate to prepared statements)
     * @param string $value Value to escape
     * @return string Escaped value
     */
    private function escapeValue($value) {
        return addslashes(trim($value));
    }
}
?>
