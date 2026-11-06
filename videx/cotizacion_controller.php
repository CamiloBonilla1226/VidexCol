<?php

require_once('cotizacion_service.php');

class CotizacionController
{
    private $service;

    public function __construct()
    {
        $this->service = new CotizacionService();
    }

    public function authorize()
    {
        if (cotizacion_is_denied_profile($_SESSION['perfil'] ?? 0)) {
            die('<h1>' . htmlspecialchars(COTIZACION_MESSAGES['unauthorized']) . '</h1>');
        }
    }

    public function buildFiltersFromRequest($source)
    {
        return cotizacion_build_request_filters($source);
    }

    public function getCreateContext()
    {
        return [
            'mode' => 'create',
            'categories' => [
                'estado' => $this->service->getCategoriesBySection(COTIZACION_SECTION_ESTADO),
                'estadoPago' => $this->service->getCategoriesBySection(COTIZACION_SECTION_ESTADO_PAGO),
                'categoriaPrincipal' => $this->service->getCategoriesBySection(COTIZACION_SECTION_CATEGORIA),
                'metodoCotizacion' => $this->service->getCategoriesBySection(COTIZACION_SECTION_METODO),
                'tipoContacto' => $this->service->getCategoriesBySection(COTIZACION_SECTION_TIPO_CONTACTO),
            ],
            'clientes' => $this->service->getClientesForForm(
                $_SESSION['perfil'] ?? 0,
                $_SESSION['id'] ?? 0,
                $_SESSION['clientes_filtro'] ?? ''
            ),
        ];
    }

    public function getEditContext($id)
    {
        return [
            'mode' => 'edit',
            'record' => $this->service->getCotizacionById($id),
            'categories' => [
                'estado' => $this->service->getCategoriesBySection(COTIZACION_SECTION_ESTADO),
                'estadoPago' => $this->service->getCategoriesBySection(COTIZACION_SECTION_ESTADO_PAGO),
                'categoriaPrincipal' => $this->service->getCategoriesBySection(COTIZACION_SECTION_CATEGORIA),
                'metodoCotizacion' => $this->service->getCategoriesBySection(COTIZACION_SECTION_METODO),
                'tipoContacto' => $this->service->getCategoriesBySection(COTIZACION_SECTION_TIPO_CONTACTO),
            ],
            'clientes' => $this->service->getClientesForForm(
                $_SESSION['perfil'] ?? 0,
                $_SESSION['id'] ?? 0,
                $_SESSION['clientes_filtro'] ?? ''
            ),
        ];
    }

    public function getListContext($page, array $filters)
    {
        $page = max(1, cotizacion_int($page));
        $offset = ($page - 1) * COTIZACION_PAGE_SIZE;

        $total = $this->service->countCotizaciones(
            $filters,
            $_SESSION['perfil'] ?? 0,
            $_SESSION['id'] ?? 0,
            $_SESSION['clientes_filtro'] ?? ''
        );

        $records = $this->service->listCotizaciones(
            $filters,
            $_SESSION['perfil'] ?? 0,
            $_SESSION['id'] ?? 0,
            $_SESSION['clientes_filtro'] ?? '',
            $offset,
            COTIZACION_PAGE_SIZE
        );

        return [
            'mode' => 'list',
            'filters' => $filters,
            'records' => $records,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / COTIZACION_PAGE_SIZE)),
        ];
    }

    public function getListViewContext(array $filters, $activos, $page)
    {
        return $this->service->getListViewData(
            $filters,
            $_SESSION['perfil'] ?? 0,
            $_SESSION['id'] ?? 0,
            $_SESSION['clientes_filtro'] ?? '',
            $activos,
            $page
        );
    }

    public function saveCreate(array $post)
    {
        $data = $this->normalizePost($post);
        $data['creacionUsuario'] = $_SESSION['id'] ?? 0;
        $id = $this->service->createCotizacion($data);
        if ($id && ($data['idContacto'] ?? 0) > 0) {
            $this->service->syncSmsUsuarioFromCotizacion($data['idContacto'], $data);
        }

        return $id;
    }

    public function saveUpdate($id, array $post)
    {
        $data = $this->normalizePost($post);
        $data['modUsuario'] = $_SESSION['id'] ?? 0;
        $result = $this->service->updateCotizacion($id, $data);
        if ($result && ($data['idContacto'] ?? 0) > 0) {
            $this->service->syncSmsUsuarioFromCotizacion($data['idContacto'], $data);
        }

        return $result;
    }

    public function delete($id)
    {
        return $this->service->softDeleteCotizacion($id, $_SESSION['id'] ?? 0);
    }

    private function normalizePost(array $post)
    {
        return [
            'idCliente' => cotizacion_int($post['idCliente'] ?? 0),
            'idContacto' => cotizacion_int($post['idContacto'] ?? 0),
            'estado' => cotizacion_int($post['estado'] ?? 0),
            'estadoPago' => cotizacion_int($post['estadoPago'] ?? 0),
            'categoriaPrincipal' => cotizacion_int($post['categoriaPrincipal'] ?? 0),
            'metodoCotizacion' => cotizacion_int($post['metodoCotizacion'] ?? 0),
            'tipoContacto' => cotizacion_int($post['tipoContacto'] ?? 0),
            'contactoNombre' => cotizacion_string($post['contactoNombre'] ?? ''),
            'contactoTelefono' => cotizacion_string($post['contactoTelefono'] ?? ''),
            'contactoCelular' => cotizacion_string($post['contactoCelular'] ?? ''),
            'contactoEmail' => cotizacion_string($post['contactoEmail'] ?? ''),
            'descripcion' => cotizacion_string($post['descripcion'] ?? ''),
            'mensajecotizacion' => cotizacion_string($post['mensajecotizacion'] ?? ''),
            'valorCotizado' => cotizacion_int($post['valorCotizado'] ?? 0),
            'valorCobrado' => cotizacion_int($post['valorCobrado'] ?? 0),
            'valorRecibido' => cotizacion_int($post['valorRecibido'] ?? 0),
            'fechaCotizacion' => cotizacion_date($post['fechaCotizacion'] ?? ''),
            'fechaFacturacion' => cotizacion_date($post['fechaFacturacion'] ?? ''),
            'fechaUltimoPago' => cotizacion_date($post['fechaUltimoPago'] ?? ''),
        ];
    }
}
