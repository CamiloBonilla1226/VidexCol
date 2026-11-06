<?php

require_once('cotizacion_config.php');

class CotizacionService
{
    private $db;

    public function __construct()
    {
        $this->db = new DBbase_Sql();
    }

    public function getCategoriesBySection($sectionId)
    {
        $sectionId = cotizacion_int($sectionId);
        $items = [];

        $sql = 'SELECT id, descripcion FROM categorias WHERE idSec = ' . $sectionId . ' ORDER BY descripcion ASC';
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $items[] = [
                'id' => $this->db->f('id'),
                'descripcion' => $this->db->f('descripcion'),
            ];
        }

        return $items;
    }

    public function getClientesForForm($perfil, $userId, $clientesFiltro = '')
    {
        $perfil = cotizacion_int($perfil);
        $userId = cotizacion_int($userId);

        $sql = 'SELECT cliente.id, cliente.nombre FROM cliente WHERE estado != 0';

        if ($perfil === 162) {
            $sql .= ' AND cliente.idComercial = "' . $userId . '"';
        }

        if ($clientesFiltro !== '') {
            $sql .= ' AND cliente.id IN (' . $clientesFiltro . ')';
        }

        $sql .= ' ORDER BY nombre ASC';

        $items = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $items[] = [
                'id' => $this->db->f('id'),
                'nombre' => $this->db->f('nombre'),
            ];
        }

        return $items;
    }

    public function getClientesForFilters($perfil, $userId, $clientesFiltro = '', $activos = 0)
    {
        $perfil = cotizacion_int($perfil);
        $userId = cotizacion_int($userId);
        $activos = cotizacion_int($activos);

        $sql = 'SELECT cliente.id, cliente.nombre, COUNT(cotizacion.id) AS totalCot '
            . 'FROM cliente, cotizacion '
            . 'WHERE cotizacion.idCliente = cliente.id';

        if ($perfil === 162) {
            $sql .= ' AND cliente.idComercial = "' . $userId . '"';
        }

        if ($clientesFiltro !== '') {
            $sql .= ' AND cliente.id IN (' . $clientesFiltro . ')';
        }

        if ($activos === 1) {
            $sql .= ' AND cliente.estado = 1';
        } else {
            $sql .= ' AND cliente.estado = 0';
        }

        $sql .= ' GROUP BY cliente.id ORDER BY nombre ASC';

        $items = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $items[] = [
                'id' => $this->db->f('id'),
                'nombre' => $this->db->f('nombre'),
                'totalCot' => $this->db->f('totalCot'),
            ];
        }

        return $items;
    }

    public function getCotizacionById($id)
    {
        $id = cotizacion_int($id);
        if ($id <= 0) {
            return null;
        }

        $sql = 'SELECT cotizacion.*, usuario.nombre AS creacionUsuarioNom, modif.nombre AS modUsuarioNom '
            . 'FROM cotizacion '
            . 'LEFT JOIN usuario ON usuario.id = cotizacion.creacionUsuario '
            . 'LEFT JOIN usuario modif ON modif.id = cotizacion.modUsuario '
            . 'WHERE cotizacion.id = ' . $id;

        $this->db->query($sql);
        if (!$this->db->next_record()) {
            return null;
        }

        return $this->fetchCotizacionRow();
    }

    public function createCotizacion(array $data)
    {
        $sql = 'INSERT INTO cotizacion (
            idCliente, idContacto, estado, estadoPago, categoriaPrincipal, metodoCotizacion,
            tipoContacto, contactoNombre, contactoTelefono, contactoCelular, contactoEmail,
            descripcion, mensajecotizacion, valorCotizado, valorCobrado, valorRecibido,
            fechaCotizacion, fechaFacturacion, fechaUltimoPago, creacionUsuario, creacionFecha
        ) VALUES (
            "' . cotizacion_int($data['idCliente'] ?? 0) . '",
            "' . cotizacion_int($data['idContacto'] ?? 0) . '",
            "' . cotizacion_int($data['estado'] ?? 0) . '",
            "' . cotizacion_int($data['estadoPago'] ?? 0) . '",
            "' . cotizacion_int($data['categoriaPrincipal'] ?? 0) . '",
            "' . cotizacion_int($data['metodoCotizacion'] ?? 0) . '",
            "' . cotizacion_int($data['tipoContacto'] ?? 0) . '",
            "' . $this->escape($data['contactoNombre'] ?? '') . '",
            "' . $this->escape($data['contactoTelefono'] ?? '') . '",
            "' . $this->escape($data['contactoCelular'] ?? '') . '",
            "' . $this->escape($data['contactoEmail'] ?? '') . '",
            "' . $this->escape($data['descripcion'] ?? '') . '",
            "' . $this->escape($data['mensajecotizacion'] ?? '') . '",
            "' . cotizacion_int($data['valorCotizado'] ?? 0) . '",
            "' . cotizacion_int($data['valorCobrado'] ?? 0) . '",
            "' . cotizacion_int($data['valorRecibido'] ?? 0) . '",
            "' . $this->escape($data['fechaCotizacion'] ?? '') . '",
            "' . $this->escape($data['fechaFacturacion'] ?? '') . '",
            "' . $this->escape($data['fechaUltimoPago'] ?? '') . '",
            "' . cotizacion_int($data['creacionUsuario'] ?? 0) . '",
            NOW()
        )';

        $this->db->query($sql);
        return $this->db->ultimoId();
    }

    public function updateCotizacion($id, array $data)
    {
        $id = cotizacion_int($id);

        $sql = 'UPDATE cotizacion SET
            idCliente = "' . cotizacion_int($data['idCliente'] ?? 0) . '",
            idContacto = "' . cotizacion_int($data['idContacto'] ?? 0) . '",
            estado = "' . cotizacion_int($data['estado'] ?? 0) . '",
            estadoPago = "' . cotizacion_int($data['estadoPago'] ?? 0) . '",
            categoriaPrincipal = "' . cotizacion_int($data['categoriaPrincipal'] ?? 0) . '",
            metodoCotizacion = "' . cotizacion_int($data['metodoCotizacion'] ?? 0) . '",
            tipoContacto = "' . cotizacion_int($data['tipoContacto'] ?? 0) . '",
            contactoNombre = "' . $this->escape($data['contactoNombre'] ?? '') . '",
            contactoTelefono = "' . $this->escape($data['contactoTelefono'] ?? '') . '",
            contactoCelular = "' . $this->escape($data['contactoCelular'] ?? '') . '",
            contactoEmail = "' . $this->escape($data['contactoEmail'] ?? '') . '",
            descripcion = "' . $this->escape($data['descripcion'] ?? '') . '",
            mensajecotizacion = "' . $this->escape($data['mensajecotizacion'] ?? '') . '",
            valorCotizado = "' . cotizacion_int($data['valorCotizado'] ?? 0) . '",
            valorCobrado = "' . cotizacion_int($data['valorCobrado'] ?? 0) . '",
            valorRecibido = "' . cotizacion_int($data['valorRecibido'] ?? 0) . '",
            fechaCotizacion = "' . $this->escape($data['fechaCotizacion'] ?? '') . '",
            fechaFacturacion = "' . $this->escape($data['fechaFacturacion'] ?? '') . '",
            fechaUltimoPago = "' . $this->escape($data['fechaUltimoPago'] ?? '') . '",
            modUsuario = "' . cotizacion_int($data['modUsuario'] ?? 0) . '",
            modFecha = NOW()
            WHERE id = ' . $id;

        return $this->db->query($sql);
    }

    public function softDeleteCotizacion($id, $userId)
    {
        $id = cotizacion_int($id);
        $userId = cotizacion_int($userId);

        $sql = 'UPDATE cotizacion SET estado = 0, modFecha = NOW(), modUsuario = "' . $userId . '" WHERE id = ' . $id;
        return $this->db->query($sql);
    }

    public function syncSmsUsuarioFromCotizacion($idContacto, array $data)
    {
        $idContacto = cotizacion_int($idContacto);
        if ($idContacto <= 0) {
            return false;
        }

        $sql = 'UPDATE sms_usuarios SET '
            . 'nombres = "' . $this->escape($data['contactoNombre'] ?? '') . '", '
            . 'telfijo = "' . $this->escape($data['contactoTelefono'] ?? '') . '", '
            . 'celular = "' . $this->escape($data['contactoCelular'] ?? '') . '", '
            . 'email = "' . $this->escape($data['contactoEmail'] ?? '') . '" '
            . 'WHERE id = "' . $idContacto . '"';

        return $this->db->query($sql);
    }

    public function countCotizaciones(array $filters, $perfil, $userId, $clientesFiltro = '')
    {
        $sql = 'SELECT COUNT(cotizacion.id) AS conteo FROM cotizacion LEFT JOIN cliente ON cliente.id = cotizacion.idCliente WHERE 1 = 1';
        $sql .= $this->buildWhere($filters, $perfil, $userId, $clientesFiltro);
        $this->db->query($sql);

        if ($this->db->next_record()) {
            return cotizacion_int($this->db->f('conteo'));
        }

        return 0;
    }

    public function listCotizaciones(array $filters, $perfil, $userId, $clientesFiltro = '', $offset = 0, $limit = 50)
    {
        $sql = 'SELECT cotizacion.*, '
            . 'categorias.descripcion AS nomEstado, '
            . 'categoriasPa.descripcion AS nomEstadoPago, '
            . 'categoriasPr.descripcion AS nomCategoriaPrincipal, '
            . 'categoriasCo.descripcion AS nomMetodoCotizacion, '
            . 'cliente.nombre AS nombreCliente, '
            . 'cliente.estado AS estadoC '
            . 'FROM cotizacion '
            . 'LEFT JOIN categorias ON categorias.id = cotizacion.estado '
            . 'LEFT JOIN categorias categoriasPa ON categoriasPa.id = cotizacion.estadoPago '
            . 'LEFT JOIN categorias categoriasPr ON categoriasPr.id = cotizacion.categoriaPrincipal '
            . 'LEFT JOIN categorias categoriasCo ON categoriasCo.id = cotizacion.metodoCotizacion '
            . 'LEFT JOIN cliente ON cliente.id = cotizacion.idCliente '
            . 'WHERE 1 = 1';

        $sql .= $this->buildWhere($filters, $perfil, $userId, $clientesFiltro);
        $sql .= ' ORDER BY cotizacion.id DESC LIMIT ' . cotizacion_int($offset) . ', ' . cotizacion_int($limit);

        $items = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $items[] = $this->fetchCotizacionRow();
        }

        return $items;
    }

    public function exportCotizaciones(array $filters, $perfil, $userId, $clientesFiltro = '')
    {
        $sql = 'SELECT cotizacion.*, '
            . 'categorias.descripcion AS nomEstado, '
            . 'categoriasPa.descripcion AS nomEstadoPago, '
            . 'categoriasPr.descripcion AS nomCategoriaPrincipal, '
            . 'categoriasCo.descripcion AS nomMetodoCotizacion, '
            . 'SUM(abonos.valor) AS totalAbonos, '
            . 'cliente.nombre AS nombreCliente, '
            . 'cliente.estado AS estadoC '
            . 'FROM cotizacion '
            . 'LEFT JOIN categorias ON categorias.id = cotizacion.estado '
            . 'LEFT JOIN categorias categoriasPa ON categoriasPa.id = cotizacion.estadoPago '
            . 'LEFT JOIN categorias categoriasPr ON categoriasPr.id = cotizacion.categoriaPrincipal '
            . 'LEFT JOIN categorias categoriasCo ON categoriasCo.id = cotizacion.metodoCotizacion '
            . 'LEFT JOIN cliente ON cliente.id = cotizacion.idCliente '
            . 'LEFT JOIN abonos ON abonos.idCotizacion = cotizacion.id '
            . 'WHERE 1 = 1';

        $sql .= $this->buildWhere($filters, $perfil, $userId, $clientesFiltro);
        $sql .= ' GROUP BY cotizacion.id ORDER BY cotizacion.id DESC';

        $items = [];
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $items[] = $this->fetchCotizacionRow();
        }

        return $items;
    }

    public function getListViewData(array $filters, $perfil, $userId, $clientesFiltro = '', $activos = 0, $page = 1)
    {
        $page = max(1, cotizacion_int($page));
        $offset = ($page - 1) * COTIZACION_PAGE_SIZE;

        return [
            'filters' => $filters,
            'clients' => $this->getClientesForFilters($perfil, $userId, $clientesFiltro, $activos),
            'categories' => [
                'estado' => $this->getCategoriesBySection(COTIZACION_SECTION_ESTADO),
                'estadoPago' => $this->getCategoriesBySection(COTIZACION_SECTION_ESTADO_PAGO),
                'categoriaPrincipal' => $this->getCategoriesBySection(COTIZACION_SECTION_CATEGORIA),
                'metodoCotizacion' => $this->getCategoriesBySection(COTIZACION_SECTION_METODO),
            ],
            'totalRecords' => $this->countCotizaciones(
                $filters,
                $perfil,
                $userId,
                $clientesFiltro
            ),
            'records' => $this->listCotizaciones(
                $filters,
                $perfil,
                $userId,
                $clientesFiltro,
                $offset,
                COTIZACION_PAGE_SIZE
            ),
            'page' => $page,
            'pages' => max(1, (int) ceil($this->countCotizaciones($filters, $perfil, $userId, $clientesFiltro) / COTIZACION_PAGE_SIZE)),
        ];
    }

    private function buildWhere(array $filters, $perfil, $userId, $clientesFiltro = '')
    {
        $sql = '';

        if ($perfil === 162) {
            $sql .= ' AND cliente.idComercial = "' . cotizacion_int($userId) . '"';
        }

        if ($clientesFiltro !== '') {
            $sql .= ' AND cliente.id IN (' . $clientesFiltro . ')';
        }

        if (!empty($filters['activos'])) {
            $sql .= ' AND cliente.estado = 1';
        } elseif (isset($filters['activos']) && $filters['activos'] !== '') {
            $sql .= ' AND cliente.estado = 0';
        }

        foreach (['contactoNombre', 'contactoTelefono', 'contactoCelular', 'contactoEmail'] as $field) {
            if (!empty($filters[$field])) {
                $sql .= ' AND cotizacion.' . $field . ' LIKE "%' . $this->escape($filters[$field]) . '%"';
            }
        }

        foreach (['estado', 'idCliente', 'estadoPago', 'categoriaPrincipal', 'metodoCotizacion', 'tipoContacto'] as $field) {
            if (!empty($filters[$field])) {
                $sql .= ' AND cotizacion.' . $field . ' = "' . cotizacion_int($filters[$field]) . '"';
            }
        }

        if ($filters['interno'] !== '') {
            $sql .= ' AND cliente.interno = "' . cotizacion_int($filters['interno']) . '"';
        }

        if (!empty($filters['fechaInicial'])) {
            $sql .= ' AND cotizacion.fechaCotizacion >= "' . $this->escape($filters['fechaInicial']) . '"';
        }

        if (!empty($filters['fechaFinal'])) {
            $sql .= ' AND cotizacion.fechaCotizacion <= "' . $this->escape($filters['fechaFinal']) . '"';
        }

        return $sql;
    }

    private function fetchCotizacionRow()
    {
        return [
            'id' => $this->db->f('id'),
            'idCliente' => $this->db->f('idCliente'),
            'idContacto' => $this->db->f('idContacto'),
            'estado' => $this->db->f('estado'),
            'estadoPago' => $this->db->f('estadoPago'),
            'categoriaPrincipal' => $this->db->f('categoriaPrincipal'),
            'metodoCotizacion' => $this->db->f('metodoCotizacion'),
            'tipoContacto' => $this->db->f('tipoContacto'),
            'contactoNombre' => $this->db->f('contactoNombre'),
            'contactoTelefono' => $this->db->f('contactoTelefono'),
            'contactoCelular' => $this->db->f('contactoCelular'),
            'contactoEmail' => $this->db->f('contactoEmail'),
            'descripcion' => $this->db->f('descripcion'),
            'mensajecotizacion' => $this->db->f('mensajecotizacion'),
            'valorCotizado' => $this->db->f('valorCotizado'),
            'valorCobrado' => $this->db->f('valorCobrado'),
            'valorRecibido' => $this->db->f('valorRecibido'),
            'fechaCotizacion' => $this->db->f('fechaCotizacion'),
            'fechaFacturacion' => $this->db->f('fechaFacturacion'),
            'fechaUltimoPago' => $this->db->f('fechaUltimoPago'),
            'creacionUsuario' => $this->db->f('creacionUsuario'),
            'creacionFecha' => $this->db->f('creacionFecha'),
            'modUsuario' => $this->db->f('modUsuario'),
            'modFecha' => $this->db->f('modFecha'),
            'creacionUsuarioNom' => $this->db->f('creacionUsuarioNom'),
            'modUsuarioNom' => $this->db->f('modUsuarioNom'),
            'nomEstado' => $this->db->f('nomEstado'),
            'nomEstadoPago' => $this->db->f('nomEstadoPago'),
            'nomCategoriaPrincipal' => $this->db->f('nomCategoriaPrincipal'),
            'nomMetodoCotizacion' => $this->db->f('nomMetodoCotizacion'),
            'nombreCliente' => $this->db->f('nombreCliente'),
            'estadoC' => $this->db->f('estadoC'),
            'totalAbonos' => $this->db->f('totalAbonos'),
        ];
    }

    private function escape($value)
    {
        return addslashes(cotizacion_string($value));
    }
}
