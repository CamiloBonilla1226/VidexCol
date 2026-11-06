<?php
/**
 * USUARIO_BUSCAR_FORM.PHP
 *
 * Rendering de formulario de búsqueda y tabla de resultados
 * - Sin lógica PHP
 * - Solo presentación de datos
 * - HTML5 semántico
 */

class UsuarioBuscarForm {

    /**
     * Renderizar página completa de búsqueda
     */
    public function render(
        $ctrl,
        $letrero,
        $usuarios,
        $categoriasTipo,
        $categoriesCliente,
        $buscar_tipo,
        $buscar_nombre,
        $buscar_identificacion,
        $buscar_cliente,
        $totalRegistros,
        $pagina,
        $totalPaginas,
        $registros,
        $params
    ) {
        ?>
        <!-- Search Form Section -->
        <div class="container usuario-buscar-container">
            <form name="form" id="form" method="get" class="form usuario-buscar-form">
                <input type="hidden" name="doc" value="usuario_buscar" />
                <input type="hidden" name="ctrl" value="<?=htmlspecialchars($ctrl); ?>" />

                <div class="form-section">
                    <h2 class="text-center well">FILTROS DE BÚSQUEDA - USUARIOS</h2>
                </div>

                <div class="form-row">
                    <!-- Tipo Usuario -->
                    <div class="form-group col-md-6">
                        <label class="form-label" for="tipo"><strong>Tipo de usuario:</strong></label>
                        <select name="tipo" onchange="autoSubmitForm()" class="form-control" id="tipo">
                            <?php if ($ctrl == "" || $ctrl == 0 || $ctrl == 4): ?>
                                <option value="">Ver todos</option>
                            <?php endif; ?>
                            <?php foreach ($categoriasTipo as $id => $desc): ?>
                                <option value="<?=htmlspecialchars($id); ?>" <?php if ($buscar_tipo == $id) echo 'selected="selected"'; ?>>
                                    <?=htmlspecialchars($desc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Cliente (Autorizado) -->
                    <?php if ($ctrl == "" || $ctrl == 4): ?>
                        <div class="form-group col-md-6">
                            <label class="form-label" for="cliente"><strong>Autorizado del cliente:</strong></label>
                            <select name="cliente" onchange="autoSubmitForm()" class="form-control" id="cliente">
                                <option value="">Ver todos</option>
                                <?php foreach ($categoriesCliente as $id => $nombre): ?>
                                    <option value="<?=htmlspecialchars($id); ?>" <?php if ($buscar_cliente == $id) echo 'selected="selected"'; ?>>
                                        <?=htmlspecialchars($nombre); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <!-- Nombre -->
                    <div class="form-group col-md-6">
                        <label class="form-label" for="nombre"><strong>Nombre:</strong></label>
                        <input type="text" name="nombre" id="nombre" value="<?=htmlspecialchars($buscar_nombre); ?>" class="form-control" placeholder="Buscar por nombre..." />
                    </div>

                    <!-- Identificación -->
                    <div class="form-group col-md-6">
                        <label class="form-label" for="identificacion"><strong>Identificación:</strong></label>
                        <input type="text" name="identificacion" id="identificacion" value="<?=htmlspecialchars($buscar_identificacion); ?>" class="form-control" placeholder="Buscar por identificación..." />
                    </div>
                </div>

                <div class="form-row text-center">
                    <div class="form-group">
                        <button type="submit" class="btn btn-success"><i class="bi bi-search"></i> Buscar</button>
                        <a href="index.php?doc=usuario_buscar&ctrl=<?=$ctrl; ?>" class="btn btn-secondary"><i class="bi bi-arrow-clockwise"></i> Limpiar</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Table Section -->
        <div class="container usuario-buscar-results">
            <div class="results-header">
                <h2 class="text-center well"><?=intval($totalRegistros); ?> REGISTROS DE USUARIOS DEL TIPO: <?=htmlspecialchars($letrero); ?></h2>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered usuario-table">
                    <thead>
                        <tr>
                            <th class="col-id">Id</th>
                            <th class="col-foto">Foto</th>
                            <th class="col-tipo">Tipo</th>
                            <th class="col-acceso">Acceso</th>
                            <?php if ($ctrl == "" || $ctrl == 4): ?>
                                <th class="col-cliente">Autorizado Cliente</th>
                            <?php endif; ?>
                            <th class="col-nombre">Nombre</th>
                            <th class="col-id-card">Identificación</th>
                            <th class="col-telefono">Teléfono</th>
                            <th class="col-celular">Celular</th>
                            <th class="col-email">E-Mail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <?php $this->renderTableRow($usuario, $ctrl); ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">Sin registros</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPaginas > 1): ?>
                <nav class="pagination-wrapper">
                    <ul class="pagination">
                        <!-- Previous -->
                        <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $this->buildPaginationUrl($params, 1, $ctrl); ?>">
                                    <i class="bi bi-chevron-double-left"></i> Primera
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $this->buildPaginationUrl($params, $pagina - 1, $ctrl); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $start = max(1, $pagina - 2);
                        $end = min($totalPaginas, $pagina + 2);
                        if ($start > 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        for ($i = $start; $i <= $end; $i++) {
                            $active = ($i == $pagina) ? 'active' : '';
                            echo '<li class="page-item ' . $active . '">';
                            if ($i == $pagina) {
                                echo '<span class="page-link">' . $i . '</span>';
                            } else {
                                echo '<a class="page-link" href="' . $this->buildPaginationUrl($params, $i, $ctrl) . '">' . $i . '</a>';
                            }
                            echo '</li>';
                        }
                        if ($end < $totalPaginas) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        ?>

                        <!-- Next -->
                        <?php if ($pagina < $totalPaginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $this->buildPaginationUrl($params, $pagina + 1, $ctrl); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $this->buildPaginationUrl($params, $totalPaginas, $ctrl); ?>">
                                    Última <i class="bi bi-chevron-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

            <!-- Export Button -->
            <div class="export-section text-center">
                <a href="index.php?excelX=&doc=usuario_buscar&nombre=<?=urlencode($buscar_nombre); ?>&identificacion=<?=urlencode($buscar_identificacion); ?>&tipo=<?=$buscar_tipo; ?>&cliente=<?=$buscar_cliente; ?>&ctrl=<?=$ctrl; ?>" class="btn btn-info">
                    <i class="bi bi-download"></i> DESCARGAR PARA EXCEL
                </a>
            </div>
        </div>

        <style>
            .usuario-buscar-container {
                margin: 20px auto;
                max-width: 1200px;
            }

            .usuario-buscar-form {
                background: var(--color-bg-light, #f8f9fa);
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 30px;
            }

            .form-section {
                margin-bottom: 20px;
            }

            .form-row {
                display: flex;
                gap: 15px;
                margin-bottom: 15px;
                flex-wrap: wrap;
            }

            .form-group {
                flex: 1;
                min-width: 250px;
            }

            .usuario-buscar-results {
                margin: 20px auto;
                max-width: 1200px;
            }

            .results-header {
                margin-bottom: 20px;
            }

            .table-responsive {
                overflow-x: auto;
                margin-bottom: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .usuario-table {
                margin-bottom: 0;
                font-size: 14px;
            }

            .usuario-table thead {
                background: #f0f0f0;
                font-weight: 600;
            }

            .usuario-table tbody tr {
                cursor: pointer;
                transition: background-color 0.2s;
            }

            .usuario-table tbody tr:hover {
                background: var(--primary, #2563eb) !important;
                color: white;
            }

            .usuario-table a {
                color: inherit;
                text-decoration: none;
            }

            .usuario-table tbody tr:hover a {
                color: white;
                text-decoration: underline;
            }

            .col-id { width: 80px; }
            .col-foto { width: 60px; }
            .col-tipo { width: 100px; }
            .col-acceso { width: 80px; }
            .col-cliente { width: 120px; }
            .col-nombre { width: 150px; }
            .col-id-card { width: 120px; }
            .col-telefono { width: 100px; }
            .col-celular { width: 100px; }
            .col-email { width: 150px; }

            .pagination-wrapper {
                text-align: center;
                margin: 30px 0;
            }

            .pagination {
                display: inline-flex;
                list-style: none;
                padding: 0;
                gap: 5px;
            }

            .page-item {
                display: inline-block;
            }

            .page-link {
                display: inline-block;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                color: var(--primary, #2563eb);
                text-decoration: none;
                transition: all 0.2s;
            }

            .page-link:hover {
                background: var(--primary, #2563eb);
                color: white;
                border-color: var(--primary, #2563eb);
            }

            .page-item.active .page-link {
                background: var(--primary, #2563eb);
                color: white;
                border-color: var(--primary, #2563eb);
            }

            .export-section {
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
               margin-top: 30px;
            }

            @media (max-width: 768px) {
                .form-row {
                    flex-direction: column;
                }

                .form-group {
                    min-width: 100%;
                }

                .usuario-table {
                    font-size: 12px;
                }

                .col-id, .col-foto, .col-tipo, .col-acceso, .col-cliente {
                    display: none;
                }
            }
        </style>

        <script>
        function autoSubmitForm() {
            document.getElementById('form').submit();
        }

        // Row click navigation
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('.usuario-table tbody tr');
            tableRows.forEach(row => {
                const link = row.querySelector('a');
                if (link) {
                    row.addEventListener('click', function() {
                        window.location.href = link.href;
                    });
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Renderizar fila individual
     */
    private function renderTableRow($usuario, $ctrl) {
        $id = $usuario['id'];
        $foto = 'images/usuarios/' . $id . '.jpg';
        $foto = (file_exists($foto)) ? $foto : 'images/consultores/desconocido.jpg';
        ?>
        <tr>
            <td><a href="index.php?doc=usuario&id=<?=intval($id); ?>"><?=str_pad($id, 6, "0", STR_PAD_LEFT); ?></a></td>
            <td><img src="<?=htmlspecialchars($foto); ?>" alt="Foto" width="40" height="40" style="border-radius: 4px;" /></td>
            <td><a href="index.php?doc=usuario&id=<?=intval($id); ?>"><?=htmlspecialchars($usuario['tipo_desc']); ?></a></td>
            <td><a href="index.php?doc=usuario&id=<?=intval($id); ?>"><?=($usuario['acceso'] == 1) ? 'Si' : 'No'; ?></a></td>
            <?php if ($ctrl == "" || $ctrl == 4): ?>
                <td><a href="index.php?doc=usuario&id=<?=intval($id); ?>"><?=htmlspecialchars($usuario['nomcliente'] ?? ''); ?></a></td>
            <?php endif; ?>
            <td><a href="index.php?doc=usuario&id=<?=intval($id); ?>"><?=htmlspecialchars($usuario['nombre']); ?></a></td>
            <td><a href="index.php?doc=usuario&id=<?=intval($id); ?>"><?=htmlspecialchars($usuario['identificacion']); ?></a></td>
            <td><a href="tel:031<?=htmlspecialchars($usuario['telefono1']); ?>"><?=htmlspecialchars($usuario['telefono1']); ?></a></td>
            <td><a href="tel:<?=htmlspecialchars($usuario['celular']); ?>"><?=htmlspecialchars($usuario['celular']); ?></a></td>
            <td><?=htmlspecialchars($usuario['email']); ?></td>
        </tr>
        <?php
    }

    /**
     * Construir URL de paginación manteniendo filtros
     */
    private function buildPaginationUrl($params, $pagina, $ctrl) {
        $url = "index.php?doc=usuario_buscar&ctrl=" . intval($ctrl) . "&pagina=" . intval($pagina);

        if (!empty($params['tipo'])) {
            $url .= "&tipo=" . intval($params['tipo']);
        }
        if (!empty($params['nombre'])) {
            $url .= "&nombre=" . urlencode($params['nombre']);
        }
        if (!empty($params['identificacion'])) {
            $url .= "&identificacion=" . urlencode($params['identificacion']);
        }
        if (!empty($params['cliente'])) {
            $url .= "&cliente=" . intval($params['cliente']);
        }

        return $url;
    }
}
?>
