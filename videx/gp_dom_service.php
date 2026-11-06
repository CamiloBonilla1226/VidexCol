<?php
/**
 * Inventario de Dominios - Service Layer. Mismo patron que GpService.
 *
 * Es independiente del inventario por proveedores de hosting: tiene sus
 * propios proveedores (registradores como Namecheap, GoDaddy, etc.) y los
 * dominios se registran dentro de cada proveedor. Tablas propias:
 *   gp_dom_proveedor  -> proveedor / registrador (con usuario y contrasena cifrada)
 *   gp_dom_dominio    -> dominios de ese proveedor (registro, vencimiento, renovacion)
 * Todo se elimina de forma logica (inactivo = 1), como el resto del modulo.
 */
require_once(__DIR__ . '/gp_crypto.php');
require_once(__DIR__ . '/gp_config.php'); // gp_dias_para_vencer() / gp_badge_vencimiento()

class GpDomService {
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
            error_log('GpDomService SQL error: ' . $this->lastError);
            return false;
        }
        return true;
    }

    private function ultimoId() {
        return mysqli_insert_id($this->db->Link_ID);
    }

    /** No hay sistema de migraciones: las tablas se crean solas la primera vez. */
    private function asegurarEsquema() {
        if (self::$esquemaListo) {
            return;
        }
        $this->executeSql("CREATE TABLE IF NOT EXISTS gp_dom_proveedor (
            id INT NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(150) NOT NULL,
            sitio_web VARCHAR(255) NULL,
            user VARCHAR(150) NULL,
            pass_cifrado TEXT NULL,
            notas TEXT NULL,
            inactivo TINYINT(1) NOT NULL DEFAULT 0,
            creacion_fecha DATETIME NULL,
            creacion_usuario INT NULL,
            modificacion_fecha DATETIME NULL,
            modificacion_usuario INT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->executeSql("CREATE TABLE IF NOT EXISTS gp_dom_dominio (
            id INT NOT NULL AUTO_INCREMENT,
            proveedor_id INT NOT NULL,
            dominio VARCHAR(255) NOT NULL,
            fecha_registro DATE NULL,
            fecha_vencimiento DATE NULL,
            renovacion_automatica TINYINT(1) NOT NULL DEFAULT 0,
            estado TINYINT(1) NOT NULL DEFAULT 1,
            notas VARCHAR(500) NULL,
            ssl_fecha_vencimiento DATE NULL,
            ssl_proveedor VARCHAR(150) NULL,
            ssl_notas VARCHAR(255) NULL,
            inactivo TINYINT(1) NOT NULL DEFAULT 0,
            creacion_fecha DATETIME NULL,
            creacion_usuario INT NULL,
            modificacion_fecha DATETIME NULL,
            modificacion_usuario INT NULL,
            PRIMARY KEY (id),
            KEY idx_proveedor (proveedor_id),
            KEY idx_dominio (dominio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        self::$esquemaListo = true;
    }

    private function filas($sql) {
        $this->db->query($sql);
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    // ================================================================
    // UTILIDADES
    // ================================================================

    /** minusculas, sin protocolo, sin "www.", sin ruta ni espacios. */
    public static function normalizarDominio($texto) {
        $d = mb_strtolower(trim((string) $texto));
        $d = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $d);
        $d = preg_replace('#[/?\#].*$#', '', $d);
        $d = preg_replace('#^www\.#', '', $d);
        return rtrim($d, '.');
    }

    public static function dominioValido($dominio) {
        return strlen($dominio) <= 253
            && (bool) preg_match('/^(?=.{1,253}$)([\p{L}\p{N}]([\p{L}\p{N}-]{0,61}[\p{L}\p{N}])?\.)+[\p{L}\p{N}]([\p{L}\p{N}-]{0,61}[\p{L}\p{N}])?$/u', $dominio);
    }

    /** Dias hasta el vencimiento (negativo = ya vencido), o null si no hay fecha. Delega en gp_config.php (compartido con Servicios). */
    public static function diasParaVencer($fechaVencimiento) {
        return gp_dias_para_vencer($fechaVencimiento);
    }

    /** HTML del estado de vencimiento (vigente / vence pronto / vencido / sin fecha). Delega en gp_config.php. */
    public static function badgeVencimiento($fechaVencimiento) {
        return gp_badge_vencimiento($fechaVencimiento);
    }

    private function fechaSql($valor) {
        $valor = trim((string) $valor);
        if ($valor === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return 'NULL';
        }
        $partes = explode('-', $valor);
        return checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0]) ? "'" . $this->esc($valor) . "'" : 'NULL';
    }

    // ================================================================
    // PROVEEDORES DE DOMINIOS
    // ================================================================

    private function metricasProveedor() {
        return "(SELECT COUNT(*) FROM gp_dom_dominio d WHERE d.proveedor_id = p.id AND d.inactivo = 0) AS total_dominios,
                (SELECT COUNT(*) FROM gp_dom_dominio d WHERE d.proveedor_id = p.id AND d.inactivo = 0
                    AND d.fecha_vencimiento IS NOT NULL AND d.fecha_vencimiento < CURDATE()) AS total_vencidos,
                (SELECT COUNT(*) FROM gp_dom_dominio d WHERE d.proveedor_id = p.id AND d.inactivo = 0
                    AND d.fecha_vencimiento BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY) AS total_por_vencer";
    }

    public function listarProveedores($busqueda = '') {
        $sql = "SELECT p.*, " . $this->metricasProveedor() . " FROM gp_dom_proveedor p WHERE p.inactivo = 0";
        if ($busqueda !== '') {
            $b = $this->esc($busqueda);
            $sql .= " AND (p.nombre LIKE '%$b%' OR p.sitio_web LIKE '%$b%')";
        }
        return $this->filas($sql . " ORDER BY p.nombre ASC");
    }

    public function getProveedor($id) {
        $id = (int) $id;
        $filas = $this->filas("SELECT p.*, " . $this->metricasProveedor() . " FROM gp_dom_proveedor p WHERE p.id = $id AND p.inactivo = 0");
        return $filas ? $filas[0] : null;
    }

    public function guardarProveedor($id, $data, $idUsuario) {
        $id = (int) $id;
        $nombre = $this->esc($data['nombre']);
        $sitio = $this->esc($data['sitio_web'] ?? '');
        $user = $this->esc($data['user'] ?? '');
        $notas = $this->esc($data['notas'] ?? '');
        if ($id > 0) {
            $sql = "UPDATE gp_dom_proveedor SET nombre = '$nombre', sitio_web = '$sitio', user = '$user', notas = '$notas',
                    modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario;
            if (!empty($data['pass'])) {
                $sql .= ", pass_cifrado = '" . $this->esc(gp_encrypt($data['pass'])) . "'";
            }
            return $this->executeSql($sql . " WHERE id = $id") ? $id : false;
        }
        $sql = "INSERT INTO gp_dom_proveedor (nombre, sitio_web, user, pass_cifrado, notas, inactivo, creacion_fecha, creacion_usuario)
                VALUES ('$nombre', '$sitio', '$user', '" . $this->esc(gp_encrypt($data['pass'] ?? '')) . "', '$notas', 0, NOW(), " . (int) $idUsuario . ")";
        return $this->executeSql($sql) ? $this->ultimoId() : false;
    }

    /** Eliminacion logica del proveedor y de sus dominios, en una transaccion. */
    public function eliminarProveedor($id, $idUsuario) {
        $id = (int) $id;
        if (!$this->getProveedor($id)) {
            $this->lastError = 'Proveedor no encontrado.';
            return false;
        }
        mysqli_begin_transaction($this->db->Link_ID);
        $pasos = [
            "UPDATE gp_dom_dominio SET inactivo = 1, modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . " WHERE proveedor_id = $id",
            "UPDATE gp_dom_proveedor SET inactivo = 1, modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . " WHERE id = $id",
        ];
        foreach ($pasos as $sql) {
            if (!$this->executeSql($sql)) {
                mysqli_rollback($this->db->Link_ID);
                return false;
            }
        }
        mysqli_commit($this->db->Link_ID);
        return true;
    }

    // ================================================================
    // DOMINIOS
    // ================================================================

    public function listarDominiosPorProveedor($proveedorId) {
        return $this->filas("SELECT * FROM gp_dom_dominio WHERE proveedor_id = " . (int) $proveedorId . " AND inactivo = 0
                ORDER BY fecha_vencimiento IS NULL, fecha_vencimiento ASC, dominio ASC");
    }

    public function listarTodosLosDominios() {
        return $this->filas("SELECT d.*, p.nombre AS proveedor_nombre
                FROM gp_dom_dominio d JOIN gp_dom_proveedor p ON p.id = d.proveedor_id
                WHERE d.inactivo = 0 AND p.inactivo = 0
                ORDER BY d.fecha_vencimiento IS NULL, d.fecha_vencimiento ASC, d.dominio ASC");
    }

    public function getDominio($id) {
        $filas = $this->filas("SELECT * FROM gp_dom_dominio WHERE id = " . (int) $id . " AND inactivo = 0");
        return $filas ? $filas[0] : null;
    }

    /** Devuelve el proveedor donde ya esta registrado ese dominio (activo), o null. */
    public function dominioYaRegistrado($dominio, $excluirId = 0) {
        $filas = $this->filas("SELECT p.nombre AS proveedor_nombre FROM gp_dom_dominio d JOIN gp_dom_proveedor p ON p.id = d.proveedor_id
                WHERE d.inactivo = 0 AND p.inactivo = 0 AND d.dominio = '" . $this->esc($dominio) . "' AND d.id <> " . (int) $excluirId . " LIMIT 1");
        return $filas ? $filas[0]['proveedor_nombre'] : null;
    }

    public function guardarDominio($id, $data, $idUsuario) {
        $id = (int) $id;
        $renov = empty($data['renovacion_automatica']) ? 0 : 1;
        $estado = empty($data['estado']) ? 0 : 1;
        $registro = $this->fechaSql($data['fecha_registro'] ?? '');
        $vence = $this->fechaSql($data['fecha_vencimiento'] ?? '');
        $notas = $this->esc(mb_substr((string) ($data['notas'] ?? ''), 0, 500));
        // SSL es opcional: si no se informa fecha, queda NULL (sin certificado registrado, sin alertas).
        $sslVence = $this->fechaSql($data['ssl_fecha_vencimiento'] ?? '');
        $sslProveedor = $this->esc(mb_substr((string) ($data['ssl_proveedor'] ?? ''), 0, 150));
        $sslNotas = $this->esc(mb_substr((string) ($data['ssl_notas'] ?? ''), 0, 255));
        if ($id > 0) {
            $sql = "UPDATE gp_dom_dominio SET dominio = '" . $this->esc($data['dominio']) . "', fecha_registro = $registro,
                    fecha_vencimiento = $vence, renovacion_automatica = $renov, estado = $estado, notas = '$notas',
                    ssl_fecha_vencimiento = $sslVence, ssl_proveedor = '$sslProveedor', ssl_notas = '$sslNotas',
                    modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . "
                    WHERE id = $id AND proveedor_id = " . (int) $data['proveedor_id'];
            return $this->executeSql($sql) ? $id : false;
        }
        $sql = "INSERT INTO gp_dom_dominio (proveedor_id, dominio, fecha_registro, fecha_vencimiento, renovacion_automatica, estado, notas,
                ssl_fecha_vencimiento, ssl_proveedor, ssl_notas, inactivo, creacion_fecha, creacion_usuario)
                VALUES (" . (int) $data['proveedor_id'] . ", '" . $this->esc($data['dominio']) . "', $registro, $vence, $renov, $estado, '$notas',
                $sslVence, '$sslProveedor', '$sslNotas', 0, NOW(), " . (int) $idUsuario . ")";
        return $this->executeSql($sql) ? $this->ultimoId() : false;
    }

    public function eliminarDominio($id, $idUsuario) {
        return $this->executeSql("UPDATE gp_dom_dominio SET inactivo = 1, modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . " WHERE id = " . (int) $id);
    }

    // ================================================================
    // METRICAS (panel de la categoria)
    // ================================================================

    public function contarResumen() {
        $filas = $this->filas("SELECT COUNT(*) AS total,
                SUM(fecha_vencimiento IS NOT NULL AND fecha_vencimiento < CURDATE()) AS vencidos,
                SUM(fecha_vencimiento BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY) AS por_vencer
                FROM gp_dom_dominio d JOIN gp_dom_proveedor p ON p.id = d.proveedor_id
                WHERE d.inactivo = 0 AND p.inactivo = 0");
        $f = $filas[0] ?? [];
        return ['total' => (int) ($f['total'] ?? 0), 'vencidos' => (int) ($f['vencidos'] ?? 0), 'por_vencer' => (int) ($f['por_vencer'] ?? 0)];
    }
}
