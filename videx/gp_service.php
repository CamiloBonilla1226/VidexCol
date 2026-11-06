<?php
/**
 * Gestion Proyectos - Service Layer
 * Mismo patron que UsuarioService (usuario_service.php): $this->db es un
 * DBbase_Sql de funciones.php, esc()/executeSql() para SQL manual seguro
 * (nunca se usa eliminarInvalidos() para SQL: esa funcion no sanitiza nada,
 * solo aplica y descarta strip_tags/addslashes/trim sin reasignar el resultado).
 */

require_once('gp_crypto.php');

class GpService {
    private $db;
    private $lastError = '';

    public function __construct() {
        $this->db = new DBbase_Sql();
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
            error_log('GpService SQL error: ' . $this->lastError . ' | SQL: ' . $sql);
            return false;
        }
        return true;
    }

    private function ultimoId() {
        return mysqli_insert_id($this->db->Link_ID);
    }

    // ================================================================
    // PROVEEDORES
    // ================================================================

    private function subconsultasMetricasProveedor() {
        return "(SELECT COUNT(*) FROM gp_servicio s WHERE s.proveedor_id = p.id AND s.inactivo = 0) AS total_servicios,
                (SELECT COUNT(*) FROM gp_proyecto pr JOIN gp_servicio s2 ON s2.id = pr.servicio_id
                    WHERE s2.proveedor_id = p.id AND s2.inactivo = 0 AND pr.inactivo = 0) AS total_proyectos,
                ((SELECT COUNT(*) FROM gp_proveedor_credencial pc WHERE pc.proveedor_id = p.id AND pc.inactivo = 0)
                + (SELECT COUNT(*) FROM gp_servicio_credencial sc JOIN gp_servicio s3 ON s3.id = sc.servicio_id
                    WHERE s3.proveedor_id = p.id AND s3.inactivo = 0 AND sc.inactivo = 0)
                + (SELECT COUNT(*) FROM gp_proyecto_credencial pjc
                    JOIN gp_proyecto pj ON pj.id = pjc.proyecto_id
                    JOIN gp_servicio s4 ON s4.id = pj.servicio_id
                    WHERE s4.proveedor_id = p.id AND s4.inactivo = 0 AND pj.inactivo = 0 AND pjc.inactivo = 0)
                ) AS total_credenciales";
    }

    public function listarProveedores($busqueda = '', $pagina = 1, $porPagina = 10) {
        $sql = "SELECT p.*, " . $this->subconsultasMetricasProveedor() . "
                FROM gp_proveedor p WHERE p.inactivo = 0";
        if ($busqueda !== '') {
            $sql .= " AND (p.nombre LIKE '%" . $this->esc($busqueda) . "%' OR p.host LIKE '%" . $this->esc($busqueda) . "%')";
        }
        $sql .= " ORDER BY p.nombre ASC";
        $pagina = max(1, (int) $pagina);
        $porPagina = max(1, (int) $porPagina);
        $sql .= " LIMIT " . $porPagina . " OFFSET " . (($pagina - 1) * $porPagina);
        $this->db->query($sql);
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    public function contarProveedores($busqueda = '') {
        $sql = "SELECT COUNT(*) AS n FROM gp_proveedor WHERE inactivo = 0";
        if ($busqueda !== '') {
            $sql .= " AND (nombre LIKE '%" . $this->esc($busqueda) . "%' OR host LIKE '%" . $this->esc($busqueda) . "%')";
        }
        $this->db->query($sql);
        return $this->db->next_record() ? (int) $this->db->f('n') : 0;
    }

    public function getProveedor($id) {
        $id = (int) $id;
        $this->db->query("SELECT p.*, " . $this->subconsultasMetricasProveedor() . "
                FROM gp_proveedor p WHERE p.id = $id AND p.inactivo = 0");
        return $this->db->next_record() ? $this->db->Record : null;
    }

    public function crearProveedor($data, $idUsuario) {
        $passCifrado = $this->esc(gp_encrypt($data['pass'] ?? ''));
        $sql = "INSERT INTO gp_proveedor (nombre, host, user, pass_cifrado, inactivo, creacion_fecha, creacion_usuario)
                VALUES ('" . $this->esc($data['nombre']) . "', '" . $this->esc($data['host'] ?? '') . "', '" . $this->esc($data['user'] ?? '') . "',
                '$passCifrado', 0, NOW(), " . (int) $idUsuario . ")";
        if (!$this->executeSql($sql)) {
            return false;
        }
        return $this->ultimoId();
    }

    public function actualizarProveedor($id, $data, $idUsuario) {
        $id = (int) $id;
        $sql = "UPDATE gp_proveedor SET
                nombre = '" . $this->esc($data['nombre']) . "',
                host = '" . $this->esc($data['host'] ?? '') . "',
                user = '" . $this->esc($data['user'] ?? '') . "',
                modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario;
        if (!empty($data['pass'])) {
            $sql .= ", pass_cifrado = '" . $this->esc(gp_encrypt($data['pass'])) . "'";
        }
        $sql .= " WHERE id = $id";
        return $this->executeSql($sql);
    }

    /**
     * Lo que se llevaria consigo eliminar un proveedor (para mostrarlo antes de confirmar).
     */
    public function resumenEliminacionProveedor($id) {
        $id = (int) $id;
        $conteo = function ($sql) {
            $this->db->query($sql);
            $this->db->next_record();
            return (int) $this->db->f('n');
        };
        $servicios = "SELECT id FROM gp_servicio WHERE proveedor_id = $id AND inactivo = 0";
        $proyectos = "SELECT id FROM gp_proyecto WHERE inactivo = 0 AND servicio_id IN ($servicios)";
        return [
            'servicios' => $conteo("SELECT COUNT(*) AS n FROM gp_servicio WHERE proveedor_id = $id AND inactivo = 0"),
            'proyectos' => $conteo("SELECT COUNT(*) AS n FROM ($proyectos) x"),
            'dominios' => $conteo("SELECT COUNT(*) AS n FROM gp_dominio WHERE inactivo = 0 AND servicio_id IN ($servicios)"),
            'credenciales' => $conteo("SELECT (SELECT COUNT(*) FROM gp_proveedor_credencial WHERE proveedor_id = $id AND inactivo = 0)
                    + (SELECT COUNT(*) FROM gp_servicio_credencial WHERE inactivo = 0 AND servicio_id IN ($servicios))
                    + (SELECT COUNT(*) FROM gp_proyecto_credencial WHERE inactivo = 0 AND proyecto_id IN ($proyectos)) AS n"),
            'tickets' => $conteo("SELECT COUNT(*) AS n FROM gp_ticket WHERE inactivo = 0 AND proyecto_id IN ($proyectos)"),
        ];
    }

    /**
     * Elimina un proveedor y, en cascada, sus servicios, proyectos, dominios y
     * credenciales. Es una eliminacion LOGICA (inactivo = 1, igual que el resto del
     * modulo): nada se borra fisicamente, asi que se puede recuperar desde la BD.
     * Todo ocurre en una transaccion: o se elimina todo o nada. Los tickets
     * ligados a esos proyectos no se tocan (quedan sin proyecto en los selectores).
     */
    public function eliminarProveedor($id, $idUsuario) {
        $id = (int) $id;
        $idUsuario = (int) $idUsuario;
        if (!$this->getProveedor($id)) {
            $this->lastError = 'Proveedor no encontrado.';
            return false;
        }
        if (!isset($this->db->Link_ID) || !$this->db->Link_ID) {
            $this->db->connect();
        }
        $servicios = "SELECT id FROM gp_servicio WHERE proveedor_id = $id";
        $proyectos = "SELECT id FROM gp_proyecto WHERE servicio_id IN ($servicios)";
        $pasos = [
            "UPDATE gp_proyecto_credencial SET inactivo = 1 WHERE proyecto_id IN ($proyectos)",
            "UPDATE gp_servicio_credencial SET inactivo = 1 WHERE servicio_id IN ($servicios)",
            "UPDATE gp_dominio SET inactivo = 1 WHERE servicio_id IN ($servicios)",
            "UPDATE gp_proyecto SET inactivo = 1 WHERE servicio_id IN ($servicios)",
            "UPDATE gp_servicio SET inactivo = 1 WHERE proveedor_id = $id",
            "UPDATE gp_proveedor_credencial SET inactivo = 1 WHERE proveedor_id = $id",
            "UPDATE gp_proveedor SET inactivo = 1, modificacion_fecha = NOW(), modificacion_usuario = $idUsuario WHERE id = $id",
        ];
        mysqli_begin_transaction($this->db->Link_ID);
        foreach ($pasos as $sql) {
            if (!$this->executeSql($sql)) {
                mysqli_rollback($this->db->Link_ID);
                return false;
            }
        }
        mysqli_commit($this->db->Link_ID);
        return true;
    }

    /**
     * Lo que se llevaria consigo eliminar un SERVICIO (sin tocar el proveedor).
     */
    public function resumenEliminacionServicio($id) {
        $id = (int) $id;
        $conteo = function ($sql) {
            $this->db->query($sql);
            $this->db->next_record();
            return (int) $this->db->f('n');
        };
        $proyectos = "SELECT id FROM gp_proyecto WHERE servicio_id = $id AND inactivo = 0";
        return [
            'proyectos' => $conteo("SELECT COUNT(*) AS n FROM ($proyectos) x"),
            'dominios' => $conteo("SELECT COUNT(*) AS n FROM gp_dominio WHERE inactivo = 0 AND servicio_id = $id"),
            'credenciales' => $conteo("SELECT (SELECT COUNT(*) FROM gp_servicio_credencial WHERE servicio_id = $id AND inactivo = 0)
                    + (SELECT COUNT(*) FROM gp_proyecto_credencial WHERE inactivo = 0 AND proyecto_id IN ($proyectos)) AS n"),
            'tickets' => $conteo("SELECT COUNT(*) AS n FROM gp_ticket WHERE inactivo = 0 AND proyecto_id IN ($proyectos)"),
        ];
    }

    /**
     * Elimina un servicio y, en cascada, sus proyectos, dominios y
     * credenciales (del servicio y de sus proyectos). Eliminacion LOGICA,
     * en una transaccion, igual que eliminarProveedor(). No toca el
     * proveedor ni otros servicios. Los tickets ligados a sus proyectos no
     * se tocan (quedan sin proyecto seleccionable).
     */
    public function eliminarServicio($id, $idUsuario) {
        $id = (int) $id;
        $idUsuario = (int) $idUsuario;
        if (!$this->getServicio($id)) {
            $this->lastError = 'Servicio no encontrado.';
            return false;
        }
        if (!isset($this->db->Link_ID) || !$this->db->Link_ID) {
            $this->db->connect();
        }
        $proyectos = "SELECT id FROM gp_proyecto WHERE servicio_id = $id";
        $pasos = [
            "UPDATE gp_proyecto_credencial SET inactivo = 1 WHERE proyecto_id IN ($proyectos)",
            "UPDATE gp_dominio SET inactivo = 1 WHERE servicio_id = $id",
            "UPDATE gp_proyecto SET inactivo = 1, modificacion_fecha = NOW(), modificacion_usuario = $idUsuario WHERE servicio_id = $id",
            "UPDATE gp_servicio_credencial SET inactivo = 1 WHERE servicio_id = $id",
            "UPDATE gp_servicio SET inactivo = 1, modificacion_fecha = NOW(), modificacion_usuario = $idUsuario WHERE id = $id",
        ];
        mysqli_begin_transaction($this->db->Link_ID);
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
    // CREDENCIALES DE PROVEEDOR
    // ================================================================

    public function listarCredencialesProveedor($proveedorId) {
        $proveedorId = (int) $proveedorId;
        $this->db->query("SELECT * FROM gp_proveedor_credencial WHERE proveedor_id = $proveedorId AND inactivo = 0 ORDER BY id DESC");
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    public function getCredencialProveedor($id) {
        $id = (int) $id;
        $this->db->query("SELECT * FROM gp_proveedor_credencial WHERE id = $id AND inactivo = 0");
        return $this->db->next_record() ? $this->db->Record : null;
    }

    public function guardarCredencialProveedor($id, $data, $idUsuario) {
        $id = (int) $id;
        if ($id > 0) {
            $sql = "UPDATE gp_proveedor_credencial SET
                    tipo = '" . $this->esc($data['tipo']) . "', user = '" . $this->esc($data['user'] ?? '') . "',
                    host = '" . $this->esc($data['host'] ?? '') . "', puerto = '" . $this->esc($data['puerto'] ?? '') . "',
                    destino = '" . $this->esc($data['destino'] ?? '') . "',
                    modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario;
            if (!empty($data['pass'])) {
                $sql .= ", pass_cifrado = '" . $this->esc(gp_encrypt($data['pass'])) . "'";
            }
            $sql .= " WHERE id = $id";
            return $this->executeSql($sql) ? $id : false;
        }
        $sql = "INSERT INTO gp_proveedor_credencial (proveedor_id, tipo, user, pass_cifrado, host, puerto, destino, inactivo, creacion_fecha, creacion_usuario)
                VALUES (" . (int) $data['proveedor_id'] . ", '" . $this->esc($data['tipo']) . "', '" . $this->esc($data['user'] ?? '') . "',
                '" . $this->esc(gp_encrypt($data['pass'] ?? '')) . "', '" . $this->esc($data['host'] ?? '') . "', '" . $this->esc($data['puerto'] ?? '') . "',
                '" . $this->esc($data['destino'] ?? '') . "', 0, NOW(), " . (int) $idUsuario . ")";
        if (!$this->executeSql($sql)) {
            return false;
        }
        return $this->ultimoId();
    }

    public function eliminarCredencialProveedor($id) {
        $id = (int) $id;
        return $this->executeSql("UPDATE gp_proveedor_credencial SET inactivo = 1 WHERE id = $id");
    }

    // ================================================================
    // SERVICIOS
    // ================================================================

    public function listarServiciosPorProveedor($proveedorId) {
        $proveedorId = (int) $proveedorId;
        $this->db->query("SELECT s.*,
                (SELECT COUNT(*) FROM gp_servicio_credencial sc WHERE sc.servicio_id = s.id AND sc.inactivo = 0) AS total_credenciales,
                (SELECT COUNT(*) FROM gp_dominio d WHERE d.servicio_id = s.id AND d.inactivo = 0) AS total_dominios,
                (SELECT COUNT(*) FROM gp_proyecto pr WHERE pr.servicio_id = s.id AND pr.inactivo = 0) AS total_proyectos
                FROM gp_servicio s WHERE s.proveedor_id = $proveedorId AND s.inactivo = 0 ORDER BY s.nombre ASC");
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    public function getServicio($id) {
        $id = (int) $id;
        $this->db->query("SELECT s.*, p.nombre AS proveedor_nombre FROM gp_servicio s JOIN gp_proveedor p ON p.id = s.proveedor_id WHERE s.id = $id AND s.inactivo = 0");
        return $this->db->next_record() ? $this->db->Record : null;
    }

    /** DATE 'YYYY-MM-DD' valida o NULL; vacio/invalido => NULL (el vencimiento del servicio es opcional). */
    private function fechaSql($valor) {
        $valor = trim((string) $valor);
        if ($valor === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return 'NULL';
        }
        $partes = explode('-', $valor);
        return checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0]) ? "'" . $this->esc($valor) . "'" : 'NULL';
    }

    public function crearServicio($data, $idUsuario) {
        $vence = $this->fechaSql($data['fecha_vencimiento'] ?? '');
        $sql = "INSERT INTO gp_servicio (proveedor_id, tipo, nombre, host_ip, fecha_vencimiento, inactivo, creacion_fecha, creacion_usuario)
                VALUES (" . (int) $data['proveedor_id'] . ", '" . $this->esc($data['tipo']) . "', '" . $this->esc($data['nombre']) . "',
                '" . $this->esc($data['host_ip'] ?? '') . "', $vence, 0, NOW(), " . (int) $idUsuario . ")";
        if (!$this->executeSql($sql)) {
            return false;
        }
        return $this->ultimoId();
    }

    public function actualizarServicio($id, $data, $idUsuario) {
        $id = (int) $id;
        $vence = $this->fechaSql($data['fecha_vencimiento'] ?? '');
        $sql = "UPDATE gp_servicio SET
                tipo = '" . $this->esc($data['tipo']) . "', nombre = '" . $this->esc($data['nombre']) . "',
                host_ip = '" . $this->esc($data['host_ip'] ?? '') . "', fecha_vencimiento = $vence,
                modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . "
                WHERE id = $id";
        return $this->executeSql($sql);
    }

    // ================================================================
    // CREDENCIALES DE SERVICIO ("Contrasenas")
    // ================================================================

    public function listarCredencialesServicio($servicioId) {
        $servicioId = (int) $servicioId;
        $this->db->query("SELECT * FROM gp_servicio_credencial WHERE servicio_id = $servicioId AND inactivo = 0 ORDER BY id DESC");
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    public function getCredencialServicio($id) {
        $id = (int) $id;
        $this->db->query("SELECT * FROM gp_servicio_credencial WHERE id = $id AND inactivo = 0");
        return $this->db->next_record() ? $this->db->Record : null;
    }

    public function guardarCredencialServicio($id, $data, $idUsuario) {
        $id = (int) $id;
        if ($id > 0) {
            $sql = "UPDATE gp_servicio_credencial SET
                    tipo = '" . $this->esc($data['tipo']) . "', user = '" . $this->esc($data['user'] ?? '') . "',
                    host = '" . $this->esc($data['host'] ?? '') . "', puerto = '" . $this->esc($data['puerto'] ?? '') . "',
                    destino = '" . $this->esc($data['destino'] ?? '') . "',
                    modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario;
            if (!empty($data['pass'])) {
                $sql .= ", pass_cifrado = '" . $this->esc(gp_encrypt($data['pass'])) . "'";
            }
            $sql .= " WHERE id = $id";
            return $this->executeSql($sql) ? $id : false;
        }
        $sql = "INSERT INTO gp_servicio_credencial (servicio_id, tipo, user, pass_cifrado, host, puerto, destino, inactivo, creacion_fecha, creacion_usuario)
                VALUES (" . (int) $data['servicio_id'] . ", '" . $this->esc($data['tipo']) . "', '" . $this->esc($data['user'] ?? '') . "',
                '" . $this->esc(gp_encrypt($data['pass'] ?? '')) . "', '" . $this->esc($data['host'] ?? '') . "', '" . $this->esc($data['puerto'] ?? '') . "',
                '" . $this->esc($data['destino'] ?? '') . "', 0, NOW(), " . (int) $idUsuario . ")";
        if (!$this->executeSql($sql)) {
            return false;
        }
        return $this->ultimoId();
    }

    public function eliminarCredencialServicio($id) {
        $id = (int) $id;
        return $this->executeSql("UPDATE gp_servicio_credencial SET inactivo = 1 WHERE id = $id");
    }

    // ================================================================
    // PROYECTOS (hijos de Servicio)
    // ================================================================

    public function listarProyectosPorServicio($servicioId) {
        $servicioId = (int) $servicioId;
        $sql = "SELECT p.*, (SELECT COUNT(*) FROM gp_proyecto_credencial c WHERE c.proyecto_id = p.id AND c.inactivo = 0) AS total_credenciales
                FROM gp_proyecto p WHERE p.servicio_id = $servicioId AND p.inactivo = 0 ORDER BY p.nombre ASC";
        $this->db->query($sql);
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    public function getProyecto($id) {
        $id = (int) $id;
        $this->db->query("SELECT * FROM gp_proyecto WHERE id = $id AND inactivo = 0");
        return $this->db->next_record() ? $this->db->Record : null;
    }

    public function guardarProyecto($id, $data, $idUsuario) {
        $id = (int) $id;
        $estado = empty($data['estado']) ? 0 : 1;
        if ($id > 0) {
            $sql = "UPDATE gp_proyecto SET
                    nombre = '" . $this->esc($data['nombre']) . "', descripcion = '" . $this->esc($data['descripcion'] ?? '') . "',
                    estado = $estado,
                    modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . "
                    WHERE id = $id";
            return $this->executeSql($sql) ? $id : false;
        }
        $sql = "INSERT INTO gp_proyecto (servicio_id, nombre, descripcion, estado, inactivo, creacion_fecha, creacion_usuario)
                VALUES (" . (int) $data['servicio_id'] . ", '" . $this->esc($data['nombre']) . "', '" . $this->esc($data['descripcion'] ?? '') . "',
                $estado, 0, NOW(), " . (int) $idUsuario . ")";
        if (!$this->executeSql($sql)) {
            return false;
        }
        return $this->ultimoId();
    }

    /**
     * Lo que se llevaria consigo eliminar un PROYECTO (sin tocar el servicio).
     */
    public function resumenEliminacionProyecto($id) {
        $id = (int) $id;
        return [
            'credenciales' => $this->contarCredencialesPorProyecto($id),
            'tickets' => (function () use ($id) {
                $this->db->query("SELECT COUNT(*) AS n FROM gp_ticket WHERE inactivo = 0 AND proyecto_id = $id");
                $this->db->next_record();
                return (int) $this->db->f('n');
            })(),
        ];
    }

    /**
     * Elimina un proyecto y, en cascada, sus credenciales. Eliminacion
     * LOGICA, en una transaccion. No toca el servicio ni otros proyectos.
     * Los tickets ligados a el no se tocan (quedan sin proyecto seleccionable).
     */
    public function eliminarProyecto($id, $idUsuario) {
        $id = (int) $id;
        $idUsuario = (int) $idUsuario;
        if (!$this->getProyecto($id)) {
            $this->lastError = 'Proyecto no encontrado.';
            return false;
        }
        if (!isset($this->db->Link_ID) || !$this->db->Link_ID) {
            $this->db->connect();
        }
        $pasos = [
            "UPDATE gp_proyecto_credencial SET inactivo = 1 WHERE proyecto_id = $id",
            "UPDATE gp_proyecto SET inactivo = 1, modificacion_fecha = NOW(), modificacion_usuario = $idUsuario WHERE id = $id",
        ];
        mysqli_begin_transaction($this->db->Link_ID);
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
    // CREDENCIALES DE PROYECTO (varias por proyecto, igual que servicio)
    // ================================================================

    public function contarCredencialesPorProyecto($proyectoId) {
        $proyectoId = (int) $proyectoId;
        $this->db->query("SELECT COUNT(*) AS n FROM gp_proyecto_credencial WHERE proyecto_id = $proyectoId AND inactivo = 0");
        return $this->db->next_record() ? (int) $this->db->f('n') : 0;
    }

    public function listarCredencialesProyecto($proyectoId) {
        $proyectoId = (int) $proyectoId;
        $this->db->query("SELECT * FROM gp_proyecto_credencial WHERE proyecto_id = $proyectoId AND inactivo = 0 ORDER BY id DESC");
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    public function getCredencialProyecto($id) {
        $id = (int) $id;
        $this->db->query("SELECT * FROM gp_proyecto_credencial WHERE id = $id AND inactivo = 0");
        return $this->db->next_record() ? $this->db->Record : null;
    }

    public function guardarCredencialProyecto($id, $data, $idUsuario) {
        $id = (int) $id;
        if ($id > 0) {
            $sql = "UPDATE gp_proyecto_credencial SET
                    tipo = '" . $this->esc($data['tipo']) . "', user = '" . $this->esc($data['user'] ?? '') . "',
                    host = '" . $this->esc($data['host'] ?? '') . "', puerto = '" . $this->esc($data['puerto'] ?? '') . "',
                    destino = '" . $this->esc($data['destino'] ?? '') . "', persona_asignada = '" . $this->esc($data['persona_asignada'] ?? '') . "',
                    modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario;
            if (!empty($data['pass'])) {
                $sql .= ", pass_cifrado = '" . $this->esc(gp_encrypt($data['pass'])) . "'";
            }
            $sql .= " WHERE id = $id";
            return $this->executeSql($sql) ? $id : false;
        }
        $sql = "INSERT INTO gp_proyecto_credencial (proyecto_id, tipo, user, pass_cifrado, host, puerto, destino, persona_asignada, inactivo, creacion_fecha, creacion_usuario)
                VALUES (" . (int) $data['proyecto_id'] . ", '" . $this->esc($data['tipo']) . "', '" . $this->esc($data['user'] ?? '') . "',
                '" . $this->esc(gp_encrypt($data['pass'] ?? '')) . "', '" . $this->esc($data['host'] ?? '') . "', '" . $this->esc($data['puerto'] ?? '') . "',
                '" . $this->esc($data['destino'] ?? '') . "', '" . $this->esc($data['persona_asignada'] ?? '') . "', 0, NOW(), " . (int) $idUsuario . ")";
        if (!$this->executeSql($sql)) {
            return false;
        }
        return $this->ultimoId();
    }

    public function eliminarCredencialProyecto($id) {
        $id = (int) $id;
        return $this->executeSql("UPDATE gp_proyecto_credencial SET inactivo = 1 WHERE id = $id");
    }

    // ================================================================
    // DOMINIOS (hijos de Servicio)
    // ================================================================

    public function listarDominiosPorServicio($servicioId) {
        $servicioId = (int) $servicioId;
        $sql = "SELECT d.*, pr.nombre AS proyecto_nombre
                FROM gp_dominio d LEFT JOIN gp_proyecto pr ON pr.id = d.proyecto_id
                WHERE d.servicio_id = $servicioId AND d.inactivo = 0 ORDER BY d.dominio ASC";
        $this->db->query($sql);
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    public function getDominio($id) {
        $id = (int) $id;
        $this->db->query("SELECT * FROM gp_dominio WHERE id = $id AND inactivo = 0");
        return $this->db->next_record() ? $this->db->Record : null;
    }

    public function guardarDominio($id, $data, $idUsuario) {
        $id = (int) $id;
        $proyectoIdSql = (!empty($data['proyecto_id'])) ? (int) $data['proyecto_id'] : 'NULL';
        $estado = empty($data['estado']) ? 0 : 1;
        if ($id > 0) {
            $sql = "UPDATE gp_dominio SET
                    dominio = '" . $this->esc($data['dominio']) . "', descripcion = '" . $this->esc($data['descripcion'] ?? '') . "',
                    carpeta_destino = '" . $this->esc($data['carpeta_destino'] ?? '') . "',
                    proyecto_id = $proyectoIdSql, estado = $estado,
                    modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . "
                    WHERE id = $id";
            return $this->executeSql($sql) ? $id : false;
        }
        $sql = "INSERT INTO gp_dominio (servicio_id, dominio, descripcion, carpeta_destino, proyecto_id, estado, inactivo, creacion_fecha, creacion_usuario)
                VALUES (" . (int) $data['servicio_id'] . ", '" . $this->esc($data['dominio']) . "', '" . $this->esc($data['descripcion'] ?? '') . "',
                '" . $this->esc($data['carpeta_destino'] ?? '') . "', $proyectoIdSql, $estado, 0, NOW(), " . (int) $idUsuario . ")";
        if (!$this->executeSql($sql)) {
            return false;
        }
        return $this->ultimoId();
    }

    public function eliminarDominio($id) {
        $id = (int) $id;
        return $this->executeSql("UPDATE gp_dominio SET inactivo = 1 WHERE id = $id");
    }

    // ================================================================
    // METRICAS (dashboard de categoria)
    // ================================================================

    public function contarServicios() {
        $this->db->query("SELECT COUNT(*) AS n FROM gp_servicio WHERE inactivo = 0");
        return $this->db->next_record() ? (int) $this->db->f('n') : 0;
    }

    public function contarProyectos() {
        $this->db->query("SELECT COUNT(*) AS total, SUM(estado = 1) AS activos FROM gp_proyecto WHERE inactivo = 0");
        if (!$this->db->next_record()) {
            return ['total' => 0, 'activos' => 0];
        }
        return ['total' => (int) $this->db->f('total'), 'activos' => (int) $this->db->f('activos')];
    }

    public function contarDominios() {
        $this->db->query("SELECT COUNT(*) AS n FROM gp_dominio WHERE inactivo = 0");
        return $this->db->next_record() ? (int) $this->db->f('n') : 0;
    }

    public function contarCredencialesTotal() {
        $this->db->query("SELECT
                (SELECT COUNT(*) FROM gp_proveedor_credencial WHERE inactivo = 0)
                + (SELECT COUNT(*) FROM gp_servicio_credencial WHERE inactivo = 0)
                + (SELECT COUNT(*) FROM gp_proyecto_credencial WHERE inactivo = 0) AS n");
        return $this->db->next_record() ? (int) $this->db->f('n') : 0;
    }

    public function listarTodosLosProyectos() {
        $sql = "SELECT p.id, p.nombre, p.estado, s.id AS servicio_id, s.nombre AS servicio_nombre,
                       pr.id AS proveedor_id, pr.nombre AS proveedor_nombre
                FROM gp_proyecto p
                JOIN gp_servicio s ON s.id = p.servicio_id AND s.inactivo = 0
                JOIN gp_proveedor pr ON pr.id = s.proveedor_id AND pr.inactivo = 0
                WHERE p.inactivo = 0
                ORDER BY p.nombre ASC";
        $this->db->query($sql);
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }
}
