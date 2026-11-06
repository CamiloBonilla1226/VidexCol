<?php
/**
 * Tickets/Tareas - Service Layer. Mismo patron que GpService (gp_service.php).
 */

class GpTicketService {
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
            error_log('GpTicketService SQL error: ' . $this->lastError . ' | SQL: ' . $sql);
            return false;
        }
        return true;
    }

    private function ultimoId() {
        return mysqli_insert_id($this->db->Link_ID);
    }

    // ================================================================
    // COLABORADORES Y VISIBILIDAD (roles por ticket)
    // ================================================================

    private static $colaboradoresDisponibles = null;

    /*
     * La tabla gp_ticket_colaborador se crea sola la primera vez (no hay
     * sistema de migraciones en este proyecto). Si por permisos de BD no se
     * pudiera crear, el modulo sigue funcionando en modo seguro: solo
     * solicitante/encargado/admin ven tickets y no hay colaboradores.
     */
    private function colaboradoresDisponibles() {
        if (self::$colaboradoresDisponibles !== null) {
            return self::$colaboradoresDisponibles;
        }
        $this->db->query("SHOW TABLES LIKE 'gp_ticket_colaborador'");
        $existe = $this->db->next_record();
        if (!$existe) {
            $this->executeSql("CREATE TABLE IF NOT EXISTS gp_ticket_colaborador (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                ticket_id INT NOT NULL,
                usuario_id INT NOT NULL,
                creacion_fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                creacion_usuario INT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ticket_usuario (ticket_id, usuario_id),
                KEY idx_usuario (usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            $this->db->query("SHOW TABLES LIKE 'gp_ticket_colaborador'");
            $existe = $this->db->next_record();
        }
        self::$colaboradoresDisponibles = (bool) $existe;
        return self::$colaboradoresDisponibles;
    }

    /**
     * Fragmento SQL " AND (...)" que limita una consulta sobre gp_ticket t a los
     * tickets que el usuario puede ver. El admin no tiene filtro. Sin usuario
     * valido el filtro no deja pasar nada (falla cerrado).
     */
    private function filtroVisibilidad($idUsuario, $esAdmin) {
        if ($esAdmin) {
            return '';
        }
        $uid = (int) $idUsuario;
        $cond = "t.solicitado_por = $uid OR t.asignado_a = $uid";
        if ($this->colaboradoresDisponibles()) {
            $cond .= " OR EXISTS (SELECT 1 FROM gp_ticket_colaborador c WHERE c.ticket_id = t.id AND c.usuario_id = $uid)";
        }
        return $uid > 0 ? " AND ($cond)" : " AND 1 = 0";
    }

    public function esColaborador($ticketId, $idUsuario) {
        if (!$this->colaboradoresDisponibles()) {
            return false;
        }
        $this->db->query("SELECT 1 FROM gp_ticket_colaborador WHERE ticket_id = " . (int) $ticketId . " AND usuario_id = " . (int) $idUsuario . " LIMIT 1");
        return (bool) $this->db->next_record();
    }

    /** Solicitante, encargado o admin: pueden modificar la informacion del ticket. */
    public function puedeEditarTicket($ticket, $idUsuario, $esAdmin) {
        if (!$ticket) {
            return false;
        }
        if ($esAdmin) {
            return true;
        }
        $uid = (int) $idUsuario;
        return $uid > 0 && ((int) $ticket['solicitado_por'] === $uid || (int) $ticket['asignado_a'] === $uid);
    }

    /**
     * Quien puede ELIMINAR un ticket: el solicitante (quien lo creo), el encargado
     * (a quien se le asigno) y el administrador del sistema. Un colaborador o
     * cualquier otro usuario NO puede, aunque vea el ticket.
     */
    public function puedeEliminarTicket($ticket, $idUsuario, $esAdmin) {
        if (!$ticket) {
            return false;
        }
        if ($esAdmin) {
            return true;
        }
        $uid = (int) $idUsuario;
        return $uid > 0 && ((int) $ticket['solicitado_por'] === $uid || (int) $ticket['asignado_a'] === $uid);
    }

    /** Editores + colaboradores: pueden ver el ticket y escribir en el seguimiento. */
    public function puedeVerTicket($ticket, $idUsuario, $esAdmin) {
        if (!$ticket) {
            return false;
        }
        return $this->puedeEditarTicket($ticket, $idUsuario, $esAdmin) || $this->esColaborador($ticket['id'], $idUsuario);
    }

    /** Etiqueta del rol del usuario en el ticket (para mostrarlo en pantalla). */
    public function rolEnTicket($ticket, $idUsuario, $esAdmin) {
        if ($esAdmin) {
            return 'Administrador';
        }
        $uid = (int) $idUsuario;
        if ((int) $ticket['solicitado_por'] === $uid) {
            return 'Solicitante';
        }
        if ((int) $ticket['asignado_a'] === $uid) {
            return 'Encargado';
        }
        return $this->esColaborador($ticket['id'], $uid) ? 'Colaborador' : '';
    }

    public function listarColaboradores($ticketId) {
        if (!$this->colaboradoresDisponibles()) {
            return [];
        }
        $this->db->query("SELECT u.id, u.nombre, u.email
                FROM gp_ticket_colaborador c
                JOIN usuario u ON u.id = c.usuario_id
                WHERE c.ticket_id = " . (int) $ticketId . "
                ORDER BY u.nombre ASC");
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    /**
     * Deja como colaboradores exactamente $idsDeseados (sin el solicitante ni el
     * encargado, que ya tienen acceso completo). Devuelve los ids recien agregados.
     * Solo lo llama guardarTicket(), que ya verifico que quien guarda es editor.
     */
    private function sincronizarColaboradores($ticketId, array $idsDeseados, $idUsuario, $solicitadoPor, $asignadoA) {
        if (!$this->colaboradoresDisponibles()) {
            return [];
        }
        $ticketId = (int) $ticketId;
        $ids = [];
        foreach ($idsDeseados as $i) {
            $i = (int) $i;
            if ($i > 0 && $i !== (int) $solicitadoPor && $i !== (int) $asignadoA) {
                $ids[$i] = $i;
            }
        }
        // Solo usuarios activos existentes.
        if (!empty($ids)) {
            $this->db->query("SELECT id FROM usuario WHERE acceso = 1 AND id IN (" . implode(',', $ids) . ")");
            $validos = [];
            while ($this->db->next_record()) {
                $validos[(int) $this->db->f('id')] = (int) $this->db->f('id');
            }
            $ids = $validos;
        }

        $actuales = [];
        foreach ($this->listarColaboradores($ticketId) as $c) {
            $actuales[(int) $c['id']] = (int) $c['id'];
        }

        $quitar = array_diff($actuales, $ids);
        if (!empty($quitar)) {
            $this->executeSql("DELETE FROM gp_ticket_colaborador WHERE ticket_id = $ticketId AND usuario_id IN (" . implode(',', $quitar) . ")");
        }
        $nuevos = array_diff($ids, $actuales);
        foreach ($nuevos as $nuevo) {
            $this->executeSql("INSERT IGNORE INTO gp_ticket_colaborador (ticket_id, usuario_id, creacion_fecha, creacion_usuario)
                    VALUES ($ticketId, " . (int) $nuevo . ", NOW(), " . (int) $idUsuario . ")");
        }
        return array_values($nuevos);
    }

    // ================================================================
    // TICKETS
    // ================================================================

    private function selectBase() {
        return "SELECT t.*, p.nombre AS proyecto_nombre, p.descripcion AS proyecto_descripcion,
                    us.nombre AS solicitado_por_nombre,
                    ua.nombre AS asignado_a_nombre,
                    um.nombre AS modificacion_usuario_nombre,
                    (SELECT COUNT(*) FROM gp_ticket_mensaje m WHERE m.ticket_id = t.id) AS total_mensajes
                FROM gp_ticket t
                LEFT JOIN gp_proyecto p ON p.id = t.proyecto_id
                LEFT JOIN usuario us ON us.id = t.solicitado_por
                LEFT JOIN usuario ua ON ua.id = t.asignado_a
                LEFT JOIN usuario um ON um.id = t.modificacion_usuario
                WHERE t.inactivo = 0";
    }

    /*
     * $idUsuario/$esAdmin son obligatorios en la practica: sin ellos la lista
     * sale vacia (un usuario solo ve sus tickets, ver filtroVisibilidad()).
     */
    public function listarTickets($busqueda = '', $estado = '', $proyectoId = 0, $idUsuario = 0, $esAdmin = false, $asignadoA = 0) {
        $sql = $this->selectBase() . $this->filtroVisibilidad($idUsuario, $esAdmin);
        if ($busqueda !== '') {
            $sql .= " AND t.titulo LIKE '%" . $this->esc($busqueda) . "%'";
        }
        if ($estado !== '') {
            $sql .= " AND t.estado = '" . $this->esc($estado) . "'";
        }
        if ($proyectoId > 0) {
            $sql .= " AND t.proyecto_id = " . (int) $proyectoId;
        }
        if ($asignadoA > 0) {
            $sql .= " AND t.asignado_a = " . (int) $asignadoA;
        }
        $sql .= " ORDER BY t.creacion_fecha DESC";
        $this->db->query($sql);
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    /** Uso interno (sin chequeo de visibilidad): no exponer directamente a la pantalla. */
    public function getTicket($id) {
        $id = (int) $id;
        $this->db->query($this->selectBase() . " AND t.id = $id");
        return $this->db->next_record() ? $this->db->Record : null;
    }

    /** Devuelve el ticket solo si el usuario puede verlo (admin, solicitante, encargado o colaborador). */
    public function getTicketVisible($id, $idUsuario, $esAdmin) {
        $ticket = $this->getTicket($id);
        return $this->puedeVerTicket($ticket, $idUsuario, $esAdmin) ? $ticket : null;
    }

    public function guardarTicket($id, $data, $idUsuario, $puedeModificarFechas, $esAdmin = false) {
        $id = (int) $id;
        $proyectoIdSql = (!empty($data['proyecto_id'])) ? (int) $data['proyecto_id'] : 'NULL';
        $asignadoASql = (!empty($data['asignado_a'])) ? (int) $data['asignado_a'] : 'NULL';

        if ($id > 0) {
            $anterior = $this->getTicket($id);
            if (!$anterior) {
                return false;
            }
            // Barrera de backend: un colaborador (o cualquiera ajeno al ticket) no
            // puede cambiar nada, sin importar lo que envie el formulario.
            if (!$this->puedeEditarTicket($anterior, $idUsuario, $esAdmin)) {
                $this->lastError = 'Sin permiso para modificar este ticket.';
                return false;
            }
            $estadoNuevo = $data['estado'];
            $estadoAnterior = $anterior['estado'];

            $inicioFechaSql = $anterior['inicio_fecha'] ? "'" . $this->esc($anterior['inicio_fecha']) . "'" : 'NULL';
            $finFechaSql = $anterior['fin_fecha'] ? "'" . $this->esc($anterior['fin_fecha']) . "'" : 'NULL';

            // Auto-registro de fechas al cambiar de estado (a menos que ya existieran).
            if ($estadoNuevo === 'en_ejecucion' && !$anterior['inicio_fecha']) {
                $inicioFechaSql = 'NOW()';
            }
            if ($estadoNuevo === 'finalizado' && !$anterior['fin_fecha']) {
                $finFechaSql = 'NOW()';
            }

            // Solo quien tiene el permiso especial puede sobrescribir manualmente las 3 fechas.
            if ($puedeModificarFechas) {
                if (array_key_exists('creacion_fecha_manual', $data) && $data['creacion_fecha_manual'] !== '') {
                    $data['creacion_fecha_override'] = "'" . $this->esc($data['creacion_fecha_manual']) . "'";
                }
                // Un campo vacio NO borra la fecha ni bloquea el auto-llenado por
                // cambio de estado: solo se sobrescribe cuando el usuario elige
                // explicitamente una fecha en el input (valor no vacio).
                if (!empty($data['inicio_fecha_manual'])) {
                    $inicioFechaSql = "'" . $this->esc($data['inicio_fecha_manual']) . "'";
                }
                if (!empty($data['fin_fecha_manual'])) {
                    $finFechaSql = "'" . $this->esc($data['fin_fecha_manual']) . "'";
                }
            }

            // Fecha estimada de entrega: campo normal (no es un dato de auditoria como
            // las 3 fechas de arriba), lo puede tocar cualquier editor del ticket.
            // Quien llega aqui ya paso el chequeo de puedeEditarTicket() de mas arriba.
            $fechaEntregaSql = array_key_exists('fecha_entrega_estimada', $data)
                ? (trim((string) $data['fecha_entrega_estimada']) !== '' ? "'" . $this->esc($data['fecha_entrega_estimada']) . "'" : 'NULL')
                : ($anterior['fecha_entrega_estimada'] ? "'" . $this->esc($anterior['fecha_entrega_estimada']) . "'" : 'NULL');

            $sql = "UPDATE gp_ticket SET
                    titulo = '" . $this->esc($data['titulo']) . "',
                    descripcion = '" . $this->esc($data['descripcion'] ?? '') . "',
                    estado = '" . $this->esc($estadoNuevo) . "',
                    proyecto_id = $proyectoIdSql,
                    asignado_a = $asignadoASql,
                    inicio_fecha = $inicioFechaSql,
                    fin_fecha = $finFechaSql,
                    fecha_entrega_estimada = $fechaEntregaSql,
                    " . (isset($data['creacion_fecha_override']) ? "creacion_fecha = " . $data['creacion_fecha_override'] . "," : "") . "
                    modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . "
                    WHERE id = $id";
            if (!$this->executeSql($sql)) {
                return false;
            }
            $colaboradoresNuevos = array_key_exists('colaboradores', $data)
                ? $this->sincronizarColaboradores($id, (array) $data['colaboradores'], $idUsuario, $anterior['solicitado_por'], $asignadoASql === 'NULL' ? 0 : $asignadoASql)
                : [];
            return ['id' => $id, 'estado_anterior' => $estadoAnterior, 'estado_nuevo' => $estadoNuevo, 'colaboradores_nuevos' => $colaboradoresNuevos];
        }

        $fechaEntregaSql = !empty($data['fecha_entrega_estimada']) ? "'" . $this->esc($data['fecha_entrega_estimada']) . "'" : 'NULL';
        $sql = "INSERT INTO gp_ticket (titulo, descripcion, estado, proyecto_id, solicitado_por, asignado_a, fecha_entrega_estimada, inactivo, creacion_fecha, creacion_usuario)
                VALUES ('" . $this->esc($data['titulo']) . "', '" . $this->esc($data['descripcion'] ?? '') . "',
                '" . $this->esc($data['estado']) . "', $proyectoIdSql, " . (int) $idUsuario . ", $asignadoASql, $fechaEntregaSql, 0, NOW(), " . (int) $idUsuario . ")";
        if (!$this->executeSql($sql)) {
            return false;
        }
        $nuevoId = $this->ultimoId();
        $colaboradoresNuevos = array_key_exists('colaboradores', $data)
            ? $this->sincronizarColaboradores($nuevoId, (array) $data['colaboradores'], $idUsuario, $idUsuario, $asignadoASql === 'NULL' ? 0 : $asignadoASql)
            : [];
        return ['id' => $nuevoId, 'estado_anterior' => null, 'estado_nuevo' => $data['estado'], 'colaboradores_nuevos' => $colaboradoresNuevos];
    }

    /** Borrado logico (inactivo = 1): el registro se conserva, quien lo elimino y cuando queda en modificacion_*. */
    public function eliminarTicket($id, $idUsuario = 0, $esAdmin = false) {
        $id = (int) $id;
        if (!$this->puedeEliminarTicket($this->getTicket($id), $idUsuario, $esAdmin)) {
            $this->lastError = 'Sin permiso para eliminar este ticket.';
            return false;
        }
        return $this->executeSql("UPDATE gp_ticket SET inactivo = 1, modificacion_fecha = NOW(), modificacion_usuario = " . (int) $idUsuario . " WHERE id = $id AND inactivo = 0");
    }

    // ================================================================
    // MENSAJES DE SEGUIMIENTO (chat)
    // ================================================================

    public function listarMensajes($ticketId) {
        $ticketId = (int) $ticketId;
        $this->db->query("SELECT m.*, u.nombre AS usuario_nombre
                FROM gp_ticket_mensaje m
                JOIN usuario u ON u.id = m.usuario_id
                WHERE m.ticket_id = $ticketId
                ORDER BY m.creacion_fecha ASC, m.id ASC");
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    public function guardarMensaje($ticketId, $usuarioId, $mensaje, $archivo = null, $tipoEvento = 'mensaje') {
        $ticketId = (int) $ticketId;
        $usuarioId = (int) $usuarioId;
        $archivoNombreSql = $archivo ? "'" . $this->esc($archivo['nombre']) . "'" : 'NULL';
        $archivoRutaSql = $archivo ? "'" . $this->esc($archivo['ruta']) . "'" : 'NULL';
        $archivoTipoSql = $archivo ? "'" . $this->esc($archivo['tipo']) . "'" : 'NULL';
        $archivoTamanoSql = $archivo ? (int) $archivo['tamano'] : 'NULL';

        $sql = "INSERT INTO gp_ticket_mensaje (ticket_id, usuario_id, mensaje, archivo_nombre, archivo_ruta, archivo_tipo, archivo_tamano, tipo_evento, creacion_fecha)
                VALUES ($ticketId, $usuarioId, '" . $this->esc($mensaje ?? '') . "', $archivoNombreSql, $archivoRutaSql, $archivoTipoSql, $archivoTamanoSql, '" . $this->esc($tipoEvento) . "', NOW())";
        if (!$this->executeSql($sql)) {
            return false;
        }
        return $this->ultimoId();
    }

    public function getMensaje($id) {
        $id = (int) $id;
        $this->db->query("SELECT * FROM gp_ticket_mensaje WHERE id = $id");
        return $this->db->next_record() ? $this->db->Record : null;
    }

    // ================================================================
    // METRICAS (dashboard de categoria)
    // ================================================================

    public function contarTicketsPorEstado($idUsuario = 0, $esAdmin = false) {
        $resultado = ['solicitado' => 0, 'revisado_pendiente' => 0, 'en_ejecucion' => 0, 'finalizado' => 0];
        $this->db->query("SELECT t.estado AS estado, COUNT(*) AS n FROM gp_ticket t WHERE t.inactivo = 0" . $this->filtroVisibilidad($idUsuario, $esAdmin) . " GROUP BY t.estado");
        while ($this->db->next_record()) {
            $estado = $this->db->f('estado');
            if (array_key_exists($estado, $resultado)) {
                $resultado[$estado] = (int) $this->db->f('n');
            }
        }
        return $resultado;
    }

    // ================================================================
    // INFORME DIARIO (tareas activas: ni Solicitado ni Finalizado)
    // ================================================================

    /*
     * Tareas activas (Revisado-Pendiente / En ejecucion) siempre se
     * incluyen. Las recien Finalizadas se incluyen UNA sola vez: solo si
     * su fin_fecha cae dentro de las ultimas 24h (es decir, se finalizaron
     * desde el informe de ayer). Al dia siguiente ya no aparecen. Esto evita
     * tener que llevar un registro aparte de "ya se avisó" y se alinea solo
     * con la cadencia diaria del cron.
     */
    public function listarTicketsActivosParaInforme() {
        $this->db->query("SELECT t.id, t.titulo, t.estado,
                    t.solicitado_por, us.nombre AS solicitado_por_nombre, us.email AS solicitado_por_email,
                    t.asignado_a, ua.nombre AS asignado_a_nombre, ua.email AS asignado_a_email,
                    p.nombre AS proyecto_nombre
                FROM gp_ticket t
                LEFT JOIN usuario us ON us.id = t.solicitado_por
                LEFT JOIN usuario ua ON ua.id = t.asignado_a
                LEFT JOIN gp_proyecto p ON p.id = t.proyecto_id
                WHERE t.inactivo = 0 AND (
                    t.estado IN ('revisado_pendiente', 'en_ejecucion')
                    OR (t.estado = 'finalizado' AND t.fin_fecha >= NOW() - INTERVAL 1 DAY)
                )
                ORDER BY t.estado = 'finalizado' ASC, t.titulo ASC");
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    // ================================================================
    // LISTAS AUXILIARES (para los selects del formulario)
    // ================================================================

    public function listarProyectosParaSelect() {
        $this->db->query("SELECT id, nombre, descripcion FROM gp_proyecto WHERE inactivo = 0 ORDER BY nombre ASC");
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    /** Solo usuarios con al menos un ticket activo asignado (dentro de los que el usuario puede ver). */
    public function listarAsignadosParaSelect($idUsuario = 0, $esAdmin = false) {
        $sql = "SELECT DISTINCT ua.id, ua.nombre
                FROM gp_ticket t
                INNER JOIN usuario ua ON ua.id = t.asignado_a
                WHERE t.inactivo = 0" . $this->filtroVisibilidad($idUsuario, $esAdmin) . "
                ORDER BY ua.nombre ASC";
        $this->db->query($sql);
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    public function listarUsuariosParaSelect() {
        $this->db->query("SELECT id, nombre FROM usuario WHERE acceso = 1 ORDER BY nombre ASC");
        $resultado = [];
        while ($this->db->next_record()) {
            $resultado[] = $this->db->Record;
        }
        return $resultado;
    }

    public function getUsuarioEmail($usuarioId) {
        $usuarioId = (int) $usuarioId;
        $this->db->query("SELECT email FROM usuario WHERE id = $usuarioId");
        return $this->db->next_record() ? $this->db->f('email') : null;
    }
}
