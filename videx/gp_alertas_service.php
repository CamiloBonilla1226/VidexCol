<?php
/**
 * Alertas de vencimiento (Dominios, SSL y Servicios) - Service Layer.
 * Mismo patron que GpDomService/GpInversionService: las tablas se crean
 * solas la primera vez.
 *
 * Tablas propias:
 *   gp_alertas_email     -> destinatarios a los que se les envian las alertas
 *   gp_alertas_enviadas  -> registro de "ya se avisó este item hoy" (dedupe:
 *                           permite correr el cron las veces que sea sin
 *                           duplicar correos el mismo dia).
 */
require_once(__DIR__ . '/gp_config.php');

const GP_ALERTAS_DIAS_ANTICIPACION = 5;

class GpAlertasService {
    private $db;
    private $lastError = '';
    private static $esquemaListo = false;

    public function __construct() {
        $this->db = new DBbase_Sql();
        $this->asegurarEsquema();
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function esc($value) {
        if (!isset($this->db->Link_ID) || !$this->db->Link_ID) {
            $this->db->connect();
        }
        return mysqli_real_escape_string($this->db->Link_ID, (string) $value);
    }

    private function executeSql($sql) {
        $this->lastError = '';
        if (!isset($this->db->Link_ID) || !$this->db->Link_ID) {
            $this->db->connect();
        }
        $ok = mysqli_query($this->db->Link_ID, $sql);
        if ($ok === false) {
            $this->lastError = mysqli_error($this->db->Link_ID);
            error_log('GpAlertasService SQL error: ' . $this->lastError);
            return false;
        }
        return true;
    }

    private function ultimoId() {
        return mysqli_insert_id($this->db->Link_ID);
    }

    private function filas($sql) {
        $this->db->query($sql);
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    private function asegurarEsquema() {
        if (self::$esquemaListo) {
            return;
        }
        $this->executeSql("CREATE TABLE IF NOT EXISTS gp_alertas_email (
            id INT NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            nombre VARCHAR(150) NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            creacion_fecha DATETIME NULL,
            creacion_usuario INT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_alertas_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // tipo: 'dominio' | 'ssl' | 'servicio'. UNIQUE (tipo, referencia_id, fecha_envio)
        // es lo que hace el cron idempotente: si ya se corrio hoy, no reenvia.
        $this->executeSql("CREATE TABLE IF NOT EXISTS gp_alertas_enviadas (
            id INT NOT NULL AUTO_INCREMENT,
            tipo VARCHAR(20) NOT NULL,
            referencia_id INT NOT NULL,
            fecha_vencimiento DATE NULL,
            fecha_envio DATE NOT NULL,
            creacion_fecha DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_alertas_envio (tipo, referencia_id, fecha_envio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::$esquemaListo = true;
    }

    // ================================================================
    // DESTINATARIOS
    // ================================================================

    public function listarDestinatarios() {
        return $this->filas("SELECT * FROM gp_alertas_email ORDER BY activo DESC, email ASC");
    }

    public function listarDestinatariosActivos() {
        $filas = $this->filas("SELECT email FROM gp_alertas_email WHERE activo = 1");
        return array_values(array_filter(array_map(function ($f) { return $f['email']; }, $filas)));
    }

    public function agregarDestinatario($email, $nombre, $idUsuario) {
        $email = trim((string) $email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'El correo no es válido.';
            return false;
        }
        $sql = "INSERT INTO gp_alertas_email (email, nombre, activo, creacion_fecha, creacion_usuario)
                VALUES ('" . $this->esc($email) . "', '" . $this->esc(trim((string) $nombre)) . "', 1, NOW(), " . (int) $idUsuario . ")
                ON DUPLICATE KEY UPDATE activo = 1, nombre = VALUES(nombre)";
        if (!$this->executeSql($sql)) {
            // Lo mas probable es el UNIQUE KEY si ya existe inactivo; el ON DUPLICATE ya lo reactiva.
            $this->lastError = $this->lastError ?: 'No se pudo agregar el destinatario.';
            return false;
        }
        return $this->ultimoId() ?: true;
    }

    public function cambiarActivoDestinatario($id, $activo) {
        return $this->executeSql("UPDATE gp_alertas_email SET activo = " . ($activo ? 1 : 0) . " WHERE id = " . (int) $id);
    }

    public function eliminarDestinatario($id) {
        return $this->executeSql("DELETE FROM gp_alertas_email WHERE id = " . (int) $id);
    }

    // ================================================================
    // ITEMS CON VENCIMIENTO (para el panel y el cron)
    // ================================================================

    /** Dominios, SSL y Servicios cuyo vencimiento cae dentro de la ventana de alerta (<=5 dias, incluye ya vencidos). */
    public function listarItemsEnVentanaDeAlerta() {
        $dias = GP_ALERTAS_DIAS_ANTICIPACION;
        $items = [];

        $dominios = $this->filas("SELECT d.id, d.dominio AS etiqueta, d.fecha_vencimiento, p.nombre AS proveedor_nombre
                FROM gp_dom_dominio d JOIN gp_dom_proveedor p ON p.id = d.proveedor_id
                WHERE d.inactivo = 0 AND p.inactivo = 0 AND d.estado = 1
                AND d.fecha_vencimiento IS NOT NULL AND d.fecha_vencimiento <= CURDATE() + INTERVAL $dias DAY");
        foreach ($dominios as $d) {
            $items[] = ['tipo' => 'dominio', 'id' => (int) $d['id'], 'etiqueta' => $d['etiqueta'], 'vencimiento' => $d['fecha_vencimiento'], 'contexto' => $d['proveedor_nombre']];
        }

        $ssl = $this->filas("SELECT d.id, d.dominio AS etiqueta, d.ssl_fecha_vencimiento AS fecha_vencimiento, p.nombre AS proveedor_nombre
                FROM gp_dom_dominio d JOIN gp_dom_proveedor p ON p.id = d.proveedor_id
                WHERE d.inactivo = 0 AND p.inactivo = 0 AND d.estado = 1
                AND d.ssl_fecha_vencimiento IS NOT NULL AND d.ssl_fecha_vencimiento <= CURDATE() + INTERVAL $dias DAY");
        foreach ($ssl as $d) {
            $items[] = ['tipo' => 'ssl', 'id' => (int) $d['id'], 'etiqueta' => $d['etiqueta'], 'vencimiento' => $d['fecha_vencimiento'], 'contexto' => $d['proveedor_nombre']];
        }

        $servicios = $this->filas("SELECT s.id, s.nombre AS etiqueta, s.fecha_vencimiento, p.nombre AS proveedor_nombre
                FROM gp_servicio s JOIN gp_proveedor p ON p.id = s.proveedor_id
                WHERE s.inactivo = 0 AND p.inactivo = 0
                AND s.fecha_vencimiento IS NOT NULL AND s.fecha_vencimiento <= CURDATE() + INTERVAL $dias DAY");
        foreach ($servicios as $s) {
            $items[] = ['tipo' => 'servicio', 'id' => (int) $s['id'], 'etiqueta' => $s['etiqueta'], 'vencimiento' => $s['fecha_vencimiento'], 'contexto' => $s['proveedor_nombre']];
        }

        return $items;
    }

    public function yaEnviadoHoy($tipo, $referenciaId) {
        $filas = $this->filas("SELECT id FROM gp_alertas_enviadas
                WHERE tipo = '" . $this->esc($tipo) . "' AND referencia_id = " . (int) $referenciaId . " AND fecha_envio = CURDATE()");
        return !empty($filas);
    }

    /** INSERT IGNORE: si dos ejecuciones del cron se pisan, el UNIQUE KEY evita el duplicado. */
    public function registrarEnvio($tipo, $referenciaId, $fechaVencimiento) {
        $vence = $fechaVencimiento ? "'" . $this->esc($fechaVencimiento) . "'" : 'NULL';
        $this->executeSql("INSERT IGNORE INTO gp_alertas_enviadas (tipo, referencia_id, fecha_vencimiento, fecha_envio, creacion_fecha)
                VALUES ('" . $this->esc($tipo) . "', " . (int) $referenciaId . ", $vence, CURDATE(), NOW())");
    }
}
