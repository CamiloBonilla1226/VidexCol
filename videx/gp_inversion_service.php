<?php
/**
 * Proyecto de Inversion - Service Layer. Mismo patron que GpDomService:
 * las tablas se crean solas la primera vez, todo se elimina de forma logica.
 *
 * Tablas propias:
 *   gp_inversion_proyecto   -> el "proyecto de inversion" de un cliente
 *   gp_inversion_cuenta     -> cuentas/bancos del proyecto (multideposito)
 *   gp_inversion_movimiento -> ingresos y egresos (un solo tipo con columna tipo_movimiento)
 *   gp_inversion_anexo      -> archivos adjuntos de un movimiento (hasta 3)
 */
require_once(__DIR__ . '/gp_inversion_config.php');

class GpInversionService {
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
            error_log('GpInversionService SQL error: ' . $this->lastError);
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

    private function fechaSql($valor) {
        $valor = trim((string) $valor);
        if ($valor === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return 'NULL';
        }
        $partes = explode('-', $valor);
        return checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0]) ? "'" . $this->esc($valor) . "'" : 'NULL';
    }

    /** No hay sistema de migraciones: las tablas se crean solas la primera vez. */
    private function asegurarEsquema() {
        if (self::$esquemaListo) {
            return;
        }
        $this->executeSql("CREATE TABLE IF NOT EXISTS gp_inversion_proyecto (
            id INT NOT NULL AUTO_INCREMENT,
            cliente_id INT NOT NULL,
            nombre VARCHAR(200) NOT NULL,
            descripcion VARCHAR(500) NULL,
            estado TINYINT(1) NOT NULL DEFAULT 1,
            inactivo TINYINT(1) NOT NULL DEFAULT 0,
            creacion_fecha DATETIME NULL,
            creacion_usuario INT NULL,
            modificacion_fecha DATETIME NULL,
            modificacion_usuario INT NULL,
            PRIMARY KEY (id),
            KEY idx_cliente (cliente_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->executeSql("CREATE TABLE IF NOT EXISTS gp_inversion_cuenta (
            id INT NOT NULL AUTO_INCREMENT,
            proyecto_id INT NOT NULL,
            banco VARCHAR(150) NOT NULL,
            tipo_cuenta VARCHAR(100) NULL,
            numero_alias VARCHAR(100) NULL,
            inactivo TINYINT(1) NOT NULL DEFAULT 0,
            creacion_fecha DATETIME NULL,
            creacion_usuario INT NULL,
            PRIMARY KEY (id),
            KEY idx_proyecto (proyecto_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->executeSql("CREATE TABLE IF NOT EXISTS gp_inversion_movimiento (
            id INT NOT NULL AUTO_INCREMENT,
            proyecto_id INT NOT NULL,
            tipo_movimiento ENUM('ingreso','egreso') NOT NULL,
            fecha DATE NOT NULL,
            valor DECIMAL(18,2) NOT NULL,
            moneda VARCHAR(5) NOT NULL DEFAULT 'COP',
            cuenta_id INT NULL,
            tipo_ingreso VARCHAR(30) NULL,
            origen_fondos VARCHAR(255) NULL,
            destino_fondo VARCHAR(255) NULL,
            clasificacion_egreso VARCHAR(30) NULL,
            capital_asociado VARCHAR(255) NULL,
            concepto VARCHAR(1000) NULL,
            inactivo TINYINT(1) NOT NULL DEFAULT 0,
            creacion_fecha DATETIME NULL,
            creacion_usuario INT NULL,
            modificacion_fecha DATETIME NULL,
            modificacion_usuario INT NULL,
            PRIMARY KEY (id),
            KEY idx_proyecto_fecha (proyecto_id, fecha),
            KEY idx_tipo (tipo_movimiento),
            KEY idx_cuenta (cuenta_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->executeSql("CREATE TABLE IF NOT EXISTS gp_inversion_anexo (
            id INT NOT NULL AUTO_INCREMENT,
            movimiento_id INT NOT NULL,
            slot TINYINT NOT NULL DEFAULT 1,
            nombre_original VARCHAR(255) NOT NULL,
            nombre_archivo VARCHAR(255) NOT NULL,
            mime_tipo VARCHAR(100) NOT NULL,
            tamano INT NOT NULL,
            creacion_fecha DATETIME NULL,
            creacion_usuario INT NULL,
            PRIMARY KEY (id),
            KEY idx_movimiento (movimiento_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::$esquemaListo = true;
    }

    // ================================================================
    // PROYECTOS
    // ================================================================

    public function listarProyectos($busqueda = '', $clienteId = null) {
        $sql = "SELECT pr.*, c.nombre AS cliente_nombre, c.nit AS cliente_nit
                FROM gp_inversion_proyecto pr
                JOIN cliente c ON c.id = pr.cliente_id
                WHERE pr.inactivo = 0";
        if ($clienteId !== null) {
            $sql .= " AND pr.cliente_id = " . (int) $clienteId;
        }
        if ($busqueda !== '') {
            $b = $this->esc($busqueda);
            $sql .= " AND (pr.nombre LIKE '%$b%' OR c.nombre LIKE '%$b%')";
        }
        return $this->filas($sql . " ORDER BY pr.nombre ASC");
    }

    public function getProyecto($id) {
        $id = (int) $id;
        $filas = $this->filas("SELECT pr.*, c.nombre AS cliente_nombre, c.nit AS cliente_nit
                FROM gp_inversion_proyecto pr JOIN cliente c ON c.id = pr.cliente_id
                WHERE pr.id = $id AND pr.inactivo = 0");
        return $filas ? $filas[0] : null;
    }

    /** Primer proyecto activo de un cliente (para resolver al inversionista externo). */
    public function getProyectoPorCliente($clienteId) {
        $filas = $this->filas("SELECT pr.*, c.nombre AS cliente_nombre, c.nit AS cliente_nit
                FROM gp_inversion_proyecto pr JOIN cliente c ON c.id = pr.cliente_id
                WHERE pr.cliente_id = " . (int) $clienteId . " AND pr.inactivo = 0 ORDER BY pr.id ASC LIMIT 1");
        return $filas ? $filas[0] : null;
    }

    public function guardarProyecto($id, $data, $idUsuario) {
        $id = (int) $id;
        $nombre = $this->esc($data['nombre']);
        $clienteId = (int) $data['cliente_id'];
        $descripcion = $this->esc($data['descripcion'] ?? '');
        $estado = empty($data['estado']) ? 0 : 1;
        if ($id > 0) {
            $sql = "UPDATE gp_inversion_proyecto SET nombre = '$nombre', cliente_id = $clienteId, descripcion = '$descripcion',
                    estado = $estado, modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . " WHERE id = $id";
            return $this->executeSql($sql) ? $id : false;
        }
        $sql = "INSERT INTO gp_inversion_proyecto (cliente_id, nombre, descripcion, estado, inactivo, creacion_fecha, creacion_usuario)
                VALUES ($clienteId, '$nombre', '$descripcion', $estado, 0, NOW(), " . (int) $idUsuario . ")";
        return $this->executeSql($sql) ? $this->ultimoId() : false;
    }

    public function eliminarProyecto($id, $idUsuario) {
        $id = (int) $id;
        if (!$this->getProyecto($id)) {
            $this->lastError = 'Proyecto no encontrado.';
            return false;
        }
        mysqli_begin_transaction($this->db->Link_ID);
        $pasos = [
            "UPDATE gp_inversion_movimiento SET inactivo = 1, modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . " WHERE proyecto_id = $id",
            "UPDATE gp_inversion_cuenta SET inactivo = 1 WHERE proyecto_id = $id",
            "UPDATE gp_inversion_proyecto SET inactivo = 1, modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . " WHERE id = $id",
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
    // CUENTAS (multideposito)
    // ================================================================

    public function listarCuentas($proyectoId) {
        return $this->filas("SELECT * FROM gp_inversion_cuenta WHERE proyecto_id = " . (int) $proyectoId . " AND inactivo = 0 ORDER BY banco ASC");
    }

    public function getCuenta($id) {
        $filas = $this->filas("SELECT * FROM gp_inversion_cuenta WHERE id = " . (int) $id . " AND inactivo = 0");
        return $filas ? $filas[0] : null;
    }

    public function guardarCuenta($id, $data, $idUsuario) {
        $id = (int) $id;
        $proyectoId = (int) $data['proyecto_id'];
        $banco = $this->esc($data['banco']);
        $tipo = $this->esc($data['tipo_cuenta'] ?? '');
        $alias = $this->esc($data['numero_alias'] ?? '');
        if ($id > 0) {
            $sql = "UPDATE gp_inversion_cuenta SET banco = '$banco', tipo_cuenta = '$tipo', numero_alias = '$alias'
                    WHERE id = $id AND proyecto_id = $proyectoId";
            return $this->executeSql($sql) ? $id : false;
        }
        $sql = "INSERT INTO gp_inversion_cuenta (proyecto_id, banco, tipo_cuenta, numero_alias, inactivo, creacion_fecha, creacion_usuario)
                VALUES ($proyectoId, '$banco', '$tipo', '$alias', 0, NOW(), " . (int) $idUsuario . ")";
        return $this->executeSql($sql) ? $this->ultimoId() : false;
    }

    public function eliminarCuenta($id) {
        return $this->executeSql("UPDATE gp_inversion_cuenta SET inactivo = 1 WHERE id = " . (int) $id);
    }

    // ================================================================
    // MOVIMIENTOS (ingresos / egresos)
    // ================================================================

    private function condicionRango($desde, $hasta) {
        $cond = '';
        if ($desde !== '') {
            $cond .= " AND m.fecha >= " . $this->fechaSql($desde);
        }
        if ($hasta !== '') {
            $cond .= " AND m.fecha <= " . $this->fechaSql($hasta);
        }
        return $cond;
    }

    public function listarMovimientos($proyectoId, $tipo, $desde = '', $hasta = '') {
        $sql = "SELECT m.*, c.banco AS cuenta_banco, c.numero_alias AS cuenta_alias
                FROM gp_inversion_movimiento m LEFT JOIN gp_inversion_cuenta c ON c.id = m.cuenta_id
                WHERE m.inactivo = 0 AND m.proyecto_id = " . (int) $proyectoId . " AND m.tipo_movimiento = '" . $this->esc($tipo) . "'";
        $sql .= $this->condicionRango($desde, $hasta);
        return $this->filas($sql . " ORDER BY m.fecha DESC, m.id DESC");
    }

    public function getMovimiento($id) {
        $filas = $this->filas("SELECT m.*, c.banco AS cuenta_banco, c.numero_alias AS cuenta_alias
                FROM gp_inversion_movimiento m LEFT JOIN gp_inversion_cuenta c ON c.id = m.cuenta_id
                WHERE m.id = " . (int) $id . " AND m.inactivo = 0");
        return $filas ? $filas[0] : null;
    }

    public function guardarIngreso($id, $data, $idUsuario) {
        $id = (int) $id;
        $proyectoId = (int) $data['proyecto_id'];
        $fecha = $this->fechaSql($data['fecha'] ?? '');
        $valor = (float) str_replace([',', '$', ' '], '', (string) ($data['valor'] ?? 0));
        $tipoIngreso = $this->esc($data['tipo_ingreso'] ?? '');
        $origen = $this->esc($data['origen_fondos'] ?? '');
        $cuentaId = !empty($data['cuenta_id']) ? (int) $data['cuenta_id'] : 'NULL';
        $capital = $this->esc($data['capital_asociado'] ?? '');
        $concepto = $this->esc($data['concepto'] ?? '');
        $moneda = $this->esc($data['moneda'] ?? 'COP');
        if ($id > 0) {
            $sql = "UPDATE gp_inversion_movimiento SET fecha = $fecha, valor = $valor, moneda = '$moneda',
                    tipo_ingreso = '$tipoIngreso', origen_fondos = '$origen', cuenta_id = $cuentaId,
                    capital_asociado = '$capital', concepto = '$concepto',
                    modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . "
                    WHERE id = $id AND proyecto_id = $proyectoId AND tipo_movimiento = 'ingreso'";
            return $this->executeSql($sql) ? $id : false;
        }
        $sql = "INSERT INTO gp_inversion_movimiento
                (proyecto_id, tipo_movimiento, fecha, valor, moneda, tipo_ingreso, origen_fondos, cuenta_id, capital_asociado, concepto, inactivo, creacion_fecha, creacion_usuario)
                VALUES ($proyectoId, 'ingreso', $fecha, $valor, '$moneda', '$tipoIngreso', '$origen', $cuentaId, '$capital', '$concepto', 0, NOW(), " . (int) $idUsuario . ")";
        return $this->executeSql($sql) ? $this->ultimoId() : false;
    }

    public function guardarEgreso($id, $data, $idUsuario) {
        $id = (int) $id;
        $proyectoId = (int) $data['proyecto_id'];
        $fecha = $this->fechaSql($data['fecha'] ?? '');
        $valor = (float) str_replace([',', '$', ' '], '', (string) ($data['valor'] ?? 0));
        $destino = $this->esc($data['destino_fondo'] ?? '');
        $cuentaId = !empty($data['cuenta_id']) ? (int) $data['cuenta_id'] : 'NULL';
        $clasificacion = $this->esc($data['clasificacion_egreso'] ?? '');
        $concepto = $this->esc($data['concepto'] ?? '');
        $moneda = $this->esc($data['moneda'] ?? 'COP');
        if ($id > 0) {
            $sql = "UPDATE gp_inversion_movimiento SET fecha = $fecha, valor = $valor, moneda = '$moneda',
                    destino_fondo = '$destino', cuenta_id = $cuentaId, clasificacion_egreso = '$clasificacion', concepto = '$concepto',
                    modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . "
                    WHERE id = $id AND proyecto_id = $proyectoId AND tipo_movimiento = 'egreso'";
            return $this->executeSql($sql) ? $id : false;
        }
        $sql = "INSERT INTO gp_inversion_movimiento
                (proyecto_id, tipo_movimiento, fecha, valor, moneda, destino_fondo, cuenta_id, clasificacion_egreso, concepto, inactivo, creacion_fecha, creacion_usuario)
                VALUES ($proyectoId, 'egreso', $fecha, $valor, '$moneda', '$destino', $cuentaId, '$clasificacion', '$concepto', 0, NOW(), " . (int) $idUsuario . ")";
        return $this->executeSql($sql) ? $this->ultimoId() : false;
    }

    public function eliminarMovimiento($id, $idUsuario) {
        return $this->executeSql("UPDATE gp_inversion_movimiento SET inactivo = 1, modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . " WHERE id = " . (int) $id);
    }

    // ================================================================
    // ANEXOS
    // ================================================================

    public function listarAnexos($movimientoId) {
        return $this->filas("SELECT * FROM gp_inversion_anexo WHERE movimiento_id = " . (int) $movimientoId . " ORDER BY slot ASC");
    }

    public function getAnexo($id) {
        $filas = $this->filas("SELECT * FROM gp_inversion_anexo WHERE id = " . (int) $id);
        return $filas ? $filas[0] : null;
    }

    /**
     * Valida y guarda un anexo subido ($_FILES[...]). Verifica el tipo MIME
     * real con finfo (no confia en el que manda el navegador), sanitiza el
     * nombre y lo guarda fuera del docroot. Devuelve el id del anexo o
     * ['error' => '...'] si algo fallo.
     */
    public function guardarAnexo($movimientoId, $slot, array $archivo, $idUsuario) {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null; // slot vacio, no es un error
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Error al subir el archivo (código ' . $archivo['error'] . ').'];
        }
        if ($archivo['size'] <= 0 || $archivo['size'] > GP_INV_ANEXO_TAMANO_MAXIMO) {
            return ['error' => 'El archivo supera el tamaño máximo de 10 MB.'];
        }
        if (!is_uploaded_file($archivo['tmp_name'])) {
            return ['error' => 'Carga de archivo inválida.'];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        if (!isset(GP_INV_ANEXO_MIME_PERMITIDOS[$mimeReal])) {
            return ['error' => 'Tipo de archivo no permitido (' . htmlspecialchars((string) $mimeReal, ENT_QUOTES) . '). Use PDF, imagen, ZIP, RAR, XLSX o CSV.'];
        }
        $ext = GP_INV_ANEXO_MIME_PERMITIDOS[$mimeReal];
        $nombreOriginal = preg_replace('/[^\p{L}\p{N}._-]/u', '_', basename((string) $archivo['name']));
        $nombreOriginal = mb_substr($nombreOriginal, 0, 200);
        $nombreGuardado = bin2hex(random_bytes(16)) . '.' . $ext;

        $dir = gp_inv_dir_anexos();
        $destino = $dir . '/' . $nombreGuardado;
        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            return ['error' => 'No se pudo guardar el archivo en el servidor.'];
        }
        @chmod($destino, 0640);

        $sql = "INSERT INTO gp_inversion_anexo (movimiento_id, slot, nombre_original, nombre_archivo, mime_tipo, tamano, creacion_fecha, creacion_usuario)
                VALUES (" . (int) $movimientoId . ", " . (int) $slot . ", '" . $this->esc($nombreOriginal) . "', '" . $this->esc($nombreGuardado) . "',
                '" . $this->esc($mimeReal) . "', " . (int) $archivo['size'] . ", NOW(), " . (int) $idUsuario . ")";
        if (!$this->executeSql($sql)) {
            @unlink($destino);
            return ['error' => 'No se pudo registrar el archivo.'];
        }
        return $this->ultimoId();
    }

    public function rutaFisicaAnexo($anexo) {
        return gp_inv_dir_anexos() . '/' . $anexo['nombre_archivo'];
    }

    // ================================================================
    // CONSOLIDACION / METRICAS (dashboard e informe ejecutivo)
    // ================================================================

    /**
     * Totales del rango: capital invertido, utilidades, retorno, gastos,
     * nuevas inversiones (egresos clasificados como inversion), saldo neto y ROI.
     */
    public function calcularConsolidado($proyectoId, $desde = '', $hasta = '') {
        $cond = $this->condicionRango($desde, $hasta);
        $proyectoId = (int) $proyectoId;

        $ing = $this->filas("SELECT
                SUM(CASE WHEN tipo_ingreso = 'aporte_capital' THEN valor ELSE 0 END) AS capital,
                SUM(CASE WHEN tipo_ingreso = 'rendimiento' THEN valor ELSE 0 END) AS utilidades,
                SUM(CASE WHEN tipo_ingreso = 'retorno_inversion' THEN valor ELSE 0 END) AS retorno,
                SUM(valor) AS total
                FROM gp_inversion_movimiento m WHERE m.inactivo = 0 AND m.proyecto_id = $proyectoId AND m.tipo_movimiento = 'ingreso'" . $cond);
        $egr = $this->filas("SELECT
                SUM(CASE WHEN clasificacion_egreso = 'gasto_operativo' THEN valor ELSE 0 END) AS gastos,
                SUM(CASE WHEN clasificacion_egreso = 'inversion' THEN valor ELSE 0 END) AS nuevas_inversiones,
                SUM(valor) AS total
                FROM gp_inversion_movimiento m WHERE m.inactivo = 0 AND m.proyecto_id = $proyectoId AND m.tipo_movimiento = 'egreso'" . $cond);

        $i = $ing[0] ?? [];
        $e = $egr[0] ?? [];
        $capital = (float) ($i['capital'] ?? 0);
        $utilidades = (float) ($i['utilidades'] ?? 0);
        $retorno = (float) ($i['retorno'] ?? 0);
        $totalIngresos = (float) ($i['total'] ?? 0);
        $gastos = (float) ($e['gastos'] ?? 0);
        $nuevasInversiones = (float) ($e['nuevas_inversiones'] ?? 0);
        $totalEgresos = (float) ($e['total'] ?? 0);

        return [
            'capital_invertido' => $capital,
            'utilidades' => $utilidades,
            'retorno_inversion' => $retorno,
            'total_ingresos' => $totalIngresos,
            'gastos' => $gastos,
            'nuevas_inversiones' => $nuevasInversiones,
            'total_egresos' => $totalEgresos,
            'saldo_neto' => $totalIngresos - $totalEgresos,
            'roi' => $capital > 0 ? round(($utilidades / $capital) * 100, 2) : 0.0,
        ];
    }

    /** Saldo disponible por cuenta/banco (consolidacion multibanco), sin filtro de fecha: es el saldo actual. */
    public function consolidadoPorCuenta($proyectoId) {
        $proyectoId = (int) $proyectoId;
        $filas = $this->filas("SELECT c.id, c.banco, c.tipo_cuenta, c.numero_alias,
                COALESCE((SELECT SUM(valor) FROM gp_inversion_movimiento m WHERE m.inactivo = 0 AND m.tipo_movimiento = 'ingreso' AND m.cuenta_id = c.id), 0)
                - COALESCE((SELECT SUM(valor) FROM gp_inversion_movimiento m WHERE m.inactivo = 0 AND m.tipo_movimiento = 'egreso' AND m.cuenta_id = c.id), 0) AS saldo
                FROM gp_inversion_cuenta c WHERE c.proyecto_id = $proyectoId AND c.inactivo = 0 ORDER BY c.banco ASC");
        $total = 0.0;
        foreach ($filas as $f) {
            $total += (float) $f['saldo'];
        }
        foreach ($filas as &$f) {
            $f['saldo'] = (float) $f['saldo'];
            $f['porcentaje'] = $total > 0 ? round(($f['saldo'] / $total) * 100, 1) : 0.0;
        }
        unset($f);
        return ['cuentas' => $filas, 'total' => $total];
    }

    /** Evolucion mensual: capital invertido, utilidades y gastos por mes dentro del rango. */
    public function evolucionTemporal($proyectoId, $desde, $hasta) {
        $cond = $this->condicionRango($desde, $hasta);
        $proyectoId = (int) $proyectoId;
        $ing = $this->filas("SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes,
                SUM(CASE WHEN tipo_ingreso = 'aporte_capital' THEN valor ELSE 0 END) AS capital,
                SUM(CASE WHEN tipo_ingreso = 'rendimiento' THEN valor ELSE 0 END) AS utilidades
                FROM gp_inversion_movimiento m WHERE m.inactivo = 0 AND m.proyecto_id = $proyectoId AND m.tipo_movimiento = 'ingreso'" . $cond . "
                GROUP BY mes");
        $egr = $this->filas("SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(valor) AS gastos
                FROM gp_inversion_movimiento m WHERE m.inactivo = 0 AND m.proyecto_id = $proyectoId AND m.tipo_movimiento = 'egreso'" . $cond . "
                GROUP BY mes");
        $meses = [];
        foreach ($ing as $f) {
            $meses[$f['mes']]['capital'] = (float) $f['capital'];
            $meses[$f['mes']]['utilidades'] = (float) $f['utilidades'];
        }
        foreach ($egr as $f) {
            $meses[$f['mes']]['gastos'] = (float) $f['gastos'];
        }
        ksort($meses);
        $resultado = [];
        foreach ($meses as $mes => $datos) {
            $resultado[] = [
                'mes' => $mes,
                'capital' => $datos['capital'] ?? 0.0,
                'utilidades' => $datos['utilidades'] ?? 0.0,
                'gastos' => $datos['gastos'] ?? 0.0,
            ];
        }
        return $resultado;
    }

    /** Rentabilidad por cuenta: capital asignado (ingresos) y utilidades generadas por esa cuenta. */
    public function rentabilidadPorCuenta($proyectoId, $desde = '', $hasta = '') {
        $cond = $this->condicionRango($desde, $hasta);
        $proyectoId = (int) $proyectoId;
        $filas = $this->filas("SELECT c.id, c.banco, c.numero_alias,
                COALESCE(SUM(CASE WHEN m.tipo_ingreso = 'aporte_capital' THEN m.valor ELSE 0 END), 0) AS capital,
                COALESCE(SUM(CASE WHEN m.tipo_ingreso = 'rendimiento' THEN m.valor ELSE 0 END), 0) AS utilidades
                FROM gp_inversion_cuenta c
                LEFT JOIN gp_inversion_movimiento m ON m.cuenta_id = c.id AND m.inactivo = 0 AND m.tipo_movimiento = 'ingreso'" . str_replace('m.fecha', 'm.fecha', $cond) . "
                WHERE c.proyecto_id = $proyectoId AND c.inactivo = 0
                GROUP BY c.id ORDER BY c.banco ASC");
        foreach ($filas as &$f) {
            $capital = (float) $f['capital'];
            $utilidades = (float) $f['utilidades'];
            $f['capital'] = $capital;
            $f['utilidades'] = $utilidades;
            $f['roi'] = $capital > 0 ? round(($utilidades / $capital) * 100, 2) : 0.0;
        }
        unset($f);
        return $filas;
    }
}
