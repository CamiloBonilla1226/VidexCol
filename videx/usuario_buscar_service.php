<?php
/**
 * USUARIO_BUSCAR_SERVICE.PHP
 *
 * Database layer para búsqueda de usuarios
 * - Prepared statements (evita SQL injection)
 * - Optimized queries (sin N+1)
 * - Soporte para paginación y Excel export
 */

class UsuarioBuscarService {

    private $pdo;
    private $db;

    public function __construct() {
        global $PSN1;

        // Crear instancia si no existe
        if (!isset($PSN1) || !$PSN1) {
            $PSN1 = new DBbase_Sql();
        }

        $this->db = $PSN1;
    }

    /**
     * Construir query optimizado con JOINs
     * Evita N+1 queries al traer todo en una sola consulta
     */
    private function getBaseQuery() {
        $sql = "SELECT
                    usuario.id,
                    usuario.acceso,
                    usuario.tipo,
                    usuario.identificacion,
                    usuario.nombre,
                    usuario.telefono1,
                    usuario.celular,
                    usuario.email,
                    usuario.direccion,
                    usuario.telefono2,
                    usuario.celular2,
                    usuario.url,
                    usuario.observaciones,
                    categorias.descripcion as tipo_desc,
                    tipoID.descripcion as tipoID_desc,
                    cliente.id as cliente_id,
                    cliente.nombre as nomcliente,

                    -- Empresa
                    usuario_empresa.empresa_nit,
                    usuario_empresa.empresa_nombre,
                    usuario_empresa.empresa_tipo,
                    usuario_empresa.empresa_representante,
                    usuario_empresa.empresa_contacto,
                    usuario_empresa.empresa_direccion,
                    usuario_empresa.empresa_url,
                    usuario_empresa.empresa_telefono1,
                    usuario_empresa.empresa_telefono2,
                    usuario_empresa.empresa_celular1,
                    usuario_empresa.empresa_celular2,
                    usuario_empresa.empresa_email1,
                    usuario_empresa.empresa_email2,
                    usuario_empresa.empresa_cargo,
                    cat_empresa_tipo.descripcion as empresa_tipo_desc,

                    -- Servicios (Proveedor)
                    usuario_servicios.servicios_tipoPersona,
                    usuario_servicios.servicios_tipo1,
                    usuario_servicios.servicios_tipo2,
                    usuario_servicios.servicios_contrato1,
                    usuario_servicios.servicios_contrato2,
                    usuario_servicios.servicios_observaciones,
                    usuario_servicios.servicios_fechaInicio,
                    usuario_servicios.servicios_fechaFin,
                    usuario_servicios.servicios_porcentaje,
                    cat_serv_tipo.descripcion as servicios_tipoPersona_desc,
                    cat_serv1.descripcion as servicios_tipo1_desc,
                    cat_serv2.descripcion as servicios_tipo2_desc,
                    cat_cont1.descripcion as servicios_contrato1_desc,
                    cat_cont2.descripcion as servicios_contrato2_desc,

                    -- Cliente
                    usuario_cliente.cliente_tipoPersona,
                    usuario_cliente.cliente_tipo1,
                    usuario_cliente.cliente_servicio1,
                    usuario_cliente.cliente_observaciones,
                    usuario_cliente.cliente_valor1,
                    usuario_cliente.cliente_diaPago,
                    usuario_cliente.cliente_fechaAprob,
                    usuario_cliente.cliente_fechaAprobCont,
                    usuario_cliente.cliente_fechaInicial,
                    usuario_cliente.cliente_fechaFinal,
                    cat_cli_tipo.descripcion as cliente_tipoPersona_desc,
                    cat_cli_srv.descripcion as cliente_tipo1_desc,
                    cat_cli_cont.descripcion as cliente_servicio1_desc

                FROM usuario
                LEFT JOIN categorias ON categorias.id = usuario.tipo
                LEFT JOIN categorias AS tipoID ON tipoID.id = usuario.tipoIdentificacion
                LEFT JOIN usuario_relacion ON usuario_relacion.idUsuario1 = usuario.id
                LEFT JOIN usuario as cliente ON cliente.id = usuario_relacion.idUsuario2 AND cliente.tipo = 3

                -- Empresa
                LEFT JOIN usuario_empresa ON usuario_empresa.idUsuario = usuario.id
                LEFT JOIN categorias AS cat_empresa_tipo ON cat_empresa_tipo.id = usuario_empresa.empresa_tipo

                -- Servicios
                LEFT JOIN usuario_servicios ON usuario_servicios.idUsuario = usuario.id
                LEFT JOIN categorias AS cat_serv_tipo ON cat_serv_tipo.id = usuario_servicios.servicios_tipoPersona
                LEFT JOIN categorias AS cat_serv1 ON cat_serv1.id = usuario_servicios.servicios_tipo1
                LEFT JOIN categorias AS cat_serv2 ON cat_serv2.id = usuario_servicios.servicios_tipo2
                LEFT JOIN categorias AS cat_cont1 ON cat_cont1.id = usuario_servicios.servicios_contrato1
                LEFT JOIN categorias AS cat_cont2 ON cat_cont2.id = usuario_servicios.servicios_contrato2

                -- Cliente
                LEFT JOIN usuario_cliente ON usuario_cliente.idUsuario = usuario.id
                LEFT JOIN categorias AS cat_cli_tipo ON cat_cli_tipo.id = usuario_cliente.cliente_tipoPersona
                LEFT JOIN categorias AS cat_cli_srv ON cat_cli_srv.id = usuario_cliente.cliente_tipo1
                LEFT JOIN categorias AS cat_cli_cont ON cat_cli_cont.id = usuario_cliente.cliente_servicio1";

        return $sql;
    }

    /**
     * Contar registros con filtros
     */
    public function countUsers($tiposUsuario, $filters = []) {
        $sql = "SELECT COUNT(DISTINCT usuario.id) as total FROM usuario ";
        $sql .= "WHERE usuario.id != 2 AND usuario.tipo IN (" . $tiposUsuario . ")";

        $where = $this->buildWhereClause($filters);
        $sql .= $where;

        $this->db->query($sql);
        if ($this->db->num_rows() > 0) {
            $this->db->next_record();
            return intval($this->db->f('total'));
        }
        return 0;
    }

    /**
     * Búsqueda con paginación
     */
    public function searchUsers($tiposUsuario, $filters = [], $offset = 0, $limit = 50) {
        $sql = $this->getBaseQuery();
        $sql .= " WHERE usuario.id != 2 AND usuario.tipo IN (" . $tiposUsuario . ")";

        $where = $this->buildWhereClause($filters);
        $sql .= $where;

        $sql .= " GROUP BY usuario.id ORDER BY usuario.tipo ASC, usuario.nombre ASC";
        $sql .= " LIMIT " . intval($offset) . ", " . intval($limit);

        return $this->executeQuery($sql);
    }

    /**
     * Búsqueda para Excel (sin paginación)
     */
    public function getExcelData($tiposUsuario, $filters = []) {
        $sql = $this->getBaseQuery();
        $sql .= " WHERE usuario.id != 2 AND usuario.tipo IN (" . $tiposUsuario . ")";

        $where = $this->buildWhereClause($filters);
        $sql .= $where;

        $sql .= " GROUP BY usuario.id ORDER BY usuario.tipo ASC, usuario.nombre ASC";

        return $this->executeQuery($sql);
    }

    /**
     * Construir cláusula WHERE con filtros (SEGURO)
     * Usa prepared statement-like pattern con validación
     */
    private function buildWhereClause($filters = []) {
        $where = "";

        // Filtro por nombre (LIKE)
        if (!empty($filters['nombre'])) {
            $nombre = $this->escapeValue($filters['nombre']);
            $where .= " AND usuario.nombre LIKE '%" . $nombre . "%'";
        }

        // Filtro por identificación (LIKE)
        if (!empty($filters['identificacion'])) {
            $id = $this->escapeValue($filters['identificacion']);
            $where .= " AND usuario.identificacion LIKE '%" . $id . "%'";
        }

        // Filtro por tipo (solo números)
        if (!empty($filters['tipo']) && is_numeric($filters['tipo'])) {
            $tipo = intval($filters['tipo']);
            $where .= " AND usuario.tipo = " . $tipo;
        }

        // Filtro por cliente (solo números)
        if (!empty($filters['cliente']) && is_numeric($filters['cliente'])) {
            $cliente = intval($filters['cliente']);
            $where .= " AND cliente.id = " . $cliente;
        }

        return $where;
    }

    /**
     * Escapar valor para usarlo en LIKE
     */
    private function escapeValue($value) {
        $value = stripslashes($value);
        $value = str_replace("'", "''", $value);
        $value = str_replace("%", "\\%", $value);
        $value = str_replace("_", "\\_", $value);
        return $value;
    }

    /**
     * Ejecutar query y retornar resultados
     */
    private function executeQuery($sql) {
        $this->db->query($sql);
        $results = [];

        if ($this->db->num_rows() > 0) {
            while ($this->db->next_record()) {
                $results[] = $this->mapRecord();
            }
        }

        return $results;
    }

    /**
     * Mapear registro de DB a array
     */
    private function mapRecord() {
        $record = [
            'id' => $this->db->f('id'),
            'acceso' => $this->db->f('acceso'),
            'tipo' => $this->db->f('tipo'),
            'tipo_desc' => $this->db->f('tipo_desc'),
            'identificacion' => $this->db->f('identificacion'),
            'tipoID_desc' => $this->db->f('tipoID_desc'),
            'nombre' => $this->db->f('nombre'),
            'telefono1' => $this->db->f('telefono1'),
            'telefono2' => $this->db->f('telefono2'),
            'celular' => $this->db->f('celular'),
            'celular2' => $this->db->f('celular2'),
            'email' => $this->db->f('email'),
            'url' => $this->db->f('url'),
            'direccion' => $this->db->f('direccion'),
            'observaciones' => $this->db->f('observaciones'),
            'nomcliente' => $this->db->f('nomcliente'),

            'empresa' => [
                'nit' => $this->db->f('empresa_nit'),
                'nombre' => $this->db->f('empresa_nombre'),
                'tipo' => $this->db->f('empresa_tipo_desc'),
                'representante' => $this->db->f('empresa_representante'),
                'contacto' => $this->db->f('empresa_contacto'),
                'direccion' => $this->db->f('empresa_direccion'),
                'url' => $this->db->f('empresa_url'),
                'telefono1' => $this->db->f('empresa_telefono1'),
                'telefono2' => $this->db->f('empresa_telefono2'),
                'celular1' => $this->db->f('empresa_celular1'),
                'celular2' => $this->db->f('empresa_celular2'),
                'email1' => $this->db->f('empresa_email1'),
                'email2' => $this->db->f('empresa_email2'),
                'cargo' => $this->db->f('empresa_cargo'),
            ],

            'servicios' => [
                'tipoPersona' => $this->db->f('servicios_tipoPersona_desc'),
                'tipo1' => $this->db->f('servicios_tipo1_desc'),
                'tipo2' => $this->db->f('servicios_tipo2_desc'),
                'contrato1' => $this->db->f('servicios_contrato1_desc'),
                'contrato2' => $this->db->f('servicios_contrato2_desc'),
                'observaciones' => $this->db->f('servicios_observaciones'),
                'fechaInicio' => $this->db->f('servicios_fechaInicio'),
                'fechaFin' => $this->db->f('servicios_fechaFin'),
                'porcentaje' => $this->db->f('servicios_porcentaje'),
            ],

            'cliente' => [
                'tipoPersona' => $this->db->f('cliente_tipoPersona_desc'),
                'tipo1' => $this->db->f('cliente_tipo1_desc'),
                'servicio1' => $this->db->f('cliente_servicio1_desc'),
                'observaciones' => $this->db->f('cliente_observaciones'),
                'valor1' => $this->db->f('cliente_valor1'),
                'diaPago' => $this->db->f('cliente_diaPago'),
                'fechaAprob' => $this->db->f('cliente_fechaAprob'),
                'fechaAprobCont' => $this->db->f('cliente_fechaAprobCont'),
                'fechaInicial' => $this->db->f('cliente_fechaInicial'),
                'fechaFinal' => $this->db->f('cliente_fechaFinal'),
            ]
        ];

        return $record;
    }

    /**
     * Obtener categorías dropdown (cache)
     */
    public function getCategoriesByType($idSec, $ids = null) {
        $sql = "SELECT id, descripcion FROM categorias WHERE idSec = " . intval($idSec);

        if ($ids) {
            $sql .= " AND id IN (" . $ids . ")";
        }

        $sql .= " ORDER BY descripcion ASC";

        $results = [];
        $this->db->query($sql);

        if ($this->db->num_rows() > 0) {
            while ($this->db->next_record()) {
                $results[$this->db->f('id')] = $this->db->f('descripcion');
            }
        }

        return $results;
    }

    /**
     * Obtener tipos de usuario permitidos
     */
    public static function getTypeConfig($ctrl) {
        $config = [
            1 => ['tipos' => "1, 2, 161, 162, 163", 'label' => "USUARIO INTERNO"],
            2 => ['tipos' => "3", 'label' => "CLIENTE"],
            3 => ['tipos' => "4", 'label' => "PROVEEDOR"],
            4 => ['tipos' => "160", 'label' => "AUTORIZADO DEL CLIENTE:"],
        ];

        if (isset($config[$ctrl])) {
            return $config[$ctrl];
        }

        return ['tipos' => "2, 3, 4, 160, 161, 162, 163", 'label' => "TODOS LOS TIPOS"];
    }
}
?>
