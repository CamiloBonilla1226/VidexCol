<?php
/**
 * Claves (Inventario) - Service Layer. Mismo patron que GpTicketService.
 *
 * Cada clave pertenece al usuario que la creo (usuario_id) y SOLO ese usuario
 * puede listarla, verla, editarla o eliminarla: todas las consultas filtran
 * por usuario_id, incluso para superusuarios.
 *
 * Cifrado (ver claves_crypto.php): usuario_cifrado, clave_cifrada y
 * notas_cifradas. Titulo, categoria y sitio quedan en claro para poder listar
 * y buscar sin descifrar; por eso no deben contener datos secretos.
 */
require_once(__DIR__ . '/claves_crypto.php');

class GpClaveService {
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
            // No se registra el SQL completo: puede contener datos cifrados.
            error_log('GpClaveService SQL error: ' . $this->lastError);
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
        $this->executeSql("CREATE TABLE IF NOT EXISTS gp_clave (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id INT NOT NULL,
            titulo VARCHAR(150) NOT NULL,
            categoria VARCHAR(60) NOT NULL DEFAULT '',
            sitio VARCHAR(255) NOT NULL DEFAULT '',
            usuario_cifrado TEXT NULL,
            clave_cifrada TEXT NULL,
            notas_cifradas MEDIUMTEXT NULL,
            creacion_fecha DATETIME NOT NULL,
            modificacion_fecha DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_usuario (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->executeSql("CREATE TABLE IF NOT EXISTS gp_clave_log (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id INT NOT NULL,
            clave_id INT NULL,
            accion VARCHAR(30) NOT NULL,
            ip VARCHAR(45) NOT NULL DEFAULT '',
            fecha DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_usuario_accion_fecha (usuario_id, accion, fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        self::$esquemaListo = true;
    }

    // ================================================================
    // CONSULTA (sin datos secretos)
    // ================================================================

    public function listar($idUsuario, $busqueda = '', $categoria = '') {
        $sql = "SELECT id, titulo, categoria, sitio, creacion_fecha, modificacion_fecha,
                    (usuario_cifrado <> '' AND usuario_cifrado IS NOT NULL) AS tiene_usuario,
                    (clave_cifrada <> '' AND clave_cifrada IS NOT NULL) AS tiene_clave,
                    (notas_cifradas <> '' AND notas_cifradas IS NOT NULL) AS tiene_notas
                FROM gp_clave WHERE usuario_id = " . (int) $idUsuario;
        if ($busqueda !== '') {
            $b = $this->esc($busqueda);
            $sql .= " AND (titulo LIKE '%$b%' OR categoria LIKE '%$b%' OR sitio LIKE '%$b%')";
        }
        if ($categoria !== '') {
            $sql .= " AND categoria = '" . $this->esc($categoria) . "'";
        }
        $sql .= " ORDER BY titulo ASC";
        $this->db->query($sql);
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    public function listarCategorias($idUsuario) {
        $this->db->query("SELECT DISTINCT categoria FROM gp_clave WHERE usuario_id = " . (int) $idUsuario . " AND categoria <> '' ORDER BY categoria ASC");
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->f('categoria');
        }
        return $resultado;
    }

    /** Fila completa (con cifrados), solo si pertenece al usuario. Uso interno. */
    private function getFila($id, $idUsuario) {
        $this->db->query("SELECT * FROM gp_clave WHERE id = " . (int) $id . " AND usuario_id = " . (int) $idUsuario);
        return $this->db->next_record() ? $this->db->Record : null;
    }

    public function existe($id, $idUsuario) {
        return $this->getFila($id, $idUsuario) !== null;
    }

    /**
     * Descifra una clave del usuario. Devuelve el arreglo con los datos en
     * claro, null si no existe / no es suya, o false si no se pudo descifrar.
     */
    public function revelar($id, $idUsuario) {
        $fila = $this->getFila($id, $idUsuario);
        if (!$fila) {
            return null;
        }
        $usuario = claves_descifrar($fila['usuario_cifrado'], 'usuario');
        $clave = claves_descifrar($fila['clave_cifrada'], 'clave');
        $notas = claves_descifrar($fila['notas_cifradas'], 'notas');
        if ($usuario === false || $clave === false || $notas === false) {
            return false;
        }
        return [
            'id' => (int) $fila['id'],
            'titulo' => $fila['titulo'],
            'categoria' => $fila['categoria'],
            'sitio' => $fila['sitio'],
            'usuario' => $usuario,
            'clave' => $clave,
            'notas' => $notas,
        ];
    }

    // ================================================================
    // ESCRITURA
    // ================================================================

    /** Crea (id = 0) o actualiza (id > 0, solo si es del usuario). Devuelve el id o false. */
    public function guardar($id, $data, $idUsuario) {
        $id = (int) $id;
        $idUsuario = (int) $idUsuario;
        try {
            $usuarioCif = claves_cifrar($data['usuario'] ?? '', 'usuario');
            $claveCif = claves_cifrar($data['clave'] ?? '', 'clave');
            $notasCif = claves_cifrar($data['notas'] ?? '', 'notas');
        } catch (ClavesCryptoException $e) {
            $this->lastError = $e->getMessage();
            error_log('GpClaveService cifrado: ' . $e->getMessage());
            return false;
        }

        if ($id > 0) {
            if (!$this->existe($id, $idUsuario)) {
                $this->lastError = 'Clave no encontrada.';
                return false;
            }
            $sql = "UPDATE gp_clave SET
                    titulo = '" . $this->esc($data['titulo']) . "',
                    categoria = '" . $this->esc($data['categoria'] ?? '') . "',
                    sitio = '" . $this->esc($data['sitio'] ?? '') . "',
                    usuario_cifrado = '" . $this->esc($usuarioCif) . "',
                    clave_cifrada = '" . $this->esc($claveCif) . "',
                    notas_cifradas = '" . $this->esc($notasCif) . "',
                    modificacion_fecha = NOW()
                    WHERE id = $id AND usuario_id = $idUsuario";
            return $this->executeSql($sql) ? $id : false;
        }

        $sql = "INSERT INTO gp_clave (usuario_id, titulo, categoria, sitio, usuario_cifrado, clave_cifrada, notas_cifradas, creacion_fecha)
                VALUES ($idUsuario, '" . $this->esc($data['titulo']) . "', '" . $this->esc($data['categoria'] ?? '') . "',
                '" . $this->esc($data['sitio'] ?? '') . "', '" . $this->esc($usuarioCif) . "', '" . $this->esc($claveCif) . "',
                '" . $this->esc($notasCif) . "', NOW())";
        if (!$this->executeSql($sql)) {
            return false;
        }
        return $this->ultimoId();
    }

    /** Eliminacion real (no se conserva el cifrado). */
    public function eliminar($id, $idUsuario) {
        return $this->executeSql("DELETE FROM gp_clave WHERE id = " . (int) $id . " AND usuario_id = " . (int) $idUsuario);
    }

    // ================================================================
    // BITACORA Y LIMITE DE INTENTOS
    // ================================================================

    public function registrarAcceso($idUsuario, $claveId, $accion) {
        $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $this->executeSql("INSERT INTO gp_clave_log (usuario_id, clave_id, accion, ip, fecha)
                VALUES (" . (int) $idUsuario . ", " . ($claveId ? (int) $claveId : 'NULL') . ", '" . $this->esc($accion) . "', '" . $this->esc($ip) . "', NOW())");
    }

    /** True si el usuario ya agoto los intentos fallidos de reautenticacion recientes. */
    public function estaBloqueado($idUsuario) {
        $this->db->query("SELECT COUNT(*) AS n FROM gp_clave_log WHERE usuario_id = " . (int) $idUsuario . "
                AND accion = 'reauth_fallida' AND fecha >= NOW() - INTERVAL " . (int) GP_CLAVES_VENTANA_MINUTOS . " MINUTE");
        $this->db->next_record();
        return (int) $this->db->f('n') >= GP_CLAVES_MAX_INTENTOS;
    }
}
