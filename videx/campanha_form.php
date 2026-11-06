<?php
function renderCategorySelect($selected = null, $name = 'tipoCampana') {
    $service = new CampanaService();
    $categories = $service->getCategories();
    ?>
    <select name="<?php echo htmlspecialchars($name); ?>" id="<?php echo htmlspecialchars($name); ?>" class="form-control" required>
        <option value="">-- Seleccionar --</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo ($selected == $cat['id'] ? 'selected' : ''); ?>>
                <?php echo htmlspecialchars($cat['nombre']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

function showToastMessage($message, $type = 'info') {
    $bgClass = $type == 'success' ? 'alert-success' : ($type == 'danger' ? 'alert-danger' : 'alert-info');
    ?>
    <div class="alert <?php echo $bgClass; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php
}

function renderCampaignForm($data = [], $mode = 'create', $errors = [], $categories = []) {
    if (empty($categories)) {
        $service = new CampanaService();
        $categories = $service->getCategories();
    }

    $title = ($mode == 'edit') ? 'Editar Campaña' : 'Crear Campaña';
    $submitText = ($mode == 'edit') ? 'Actualizar' : 'Guardar';
    $buttonAction = ($mode == 'edit') ? 'update' : 'create';
    ?>
    <div class="campaign-form-container">
        <h1 class="well text-center"><?php echo $title; ?></h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Errores de validacion:</strong>
                <ul>
                    <?php foreach ($errors as $field => $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="form1" class="form-horizontal" method="post">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['id'] ?? 0); ?>">
            <input type="hidden" name="funcion" id="funcion" value="">
            <input type="hidden" name="doc" value="campanha">
            <input type="hidden" name="opc" value="2">

            <div class="form-group">
                <label class="col-sm-2 control-label">Nombre:</label>
                <div class="col-sm-10">
                    <input type="text" name="nombre" id="nombre" class="form-control"
                           value="<?php echo htmlspecialchars($data['nombre'] ?? ''); ?>" required>
                    <?php if (isset($errors['nombre'])): ?>
                        <small class="form-text text-danger"><?php echo htmlspecialchars($errors['nombre']); ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Tipo de Campanha:</label>
                <div class="col-sm-10">
                    <select name="tipoCampana" id="tipoCampana" class="form-control" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['id']); ?>"
                                    <?php echo ($data['tipoCampana'] == $cat['id'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['tipoCampana'])): ?>
                        <small class="form-text text-danger"><?php echo htmlspecialchars($errors['tipoCampana']); ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Fecha Inicial:</label>
                <div class="col-sm-10">
                    <input type="date" name="fechaInicial" id="fechaInicial" class="form-control"
                           value="<?php echo htmlspecialchars($data['fechaInicial'] ?? ''); ?>" required>
                    <?php if (isset($errors['fechaInicial'])): ?>
                        <small class="form-text text-danger"><?php echo htmlspecialchars($errors['fechaInicial']); ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Fecha Final:</label>
                <div class="col-sm-10">
                    <input type="date" name="fechaFinal" id="fechaFinal" class="form-control"
                           value="<?php echo htmlspecialchars($data['fechaFinal'] ?? ''); ?>" required>
                    <?php if (isset($errors['fechaFinal'])): ?>
                        <small class="form-text text-danger"><?php echo htmlspecialchars($errors['fechaFinal']); ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Estado:</label>
                <div class="col-sm-10">
                    <select name="estado" id="estado" class="form-control" required>
                        <option value="">-- Seleccionar --</option>
                        <option value="1" <?php echo ($data['estado'] == 1 ? 'selected' : ''); ?>>Activo</option>
                        <option value="0" <?php echo ($data['estado'] == 0 ? 'selected' : ''); ?>>Inactivo</option>
                    </select>
                    <?php if (isset($errors['estado'])): ?>
                        <small class="form-text text-danger"><?php echo htmlspecialchars($errors['estado']); ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Observaciones:</label>
                <div class="col-sm-10">
                    <textarea name="observaciones" id="observaciones" class="form-control" rows="4"><?php echo htmlspecialchars($data['observaciones'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="button" class="btn btn-success" onclick="submitCampaignForm('<?php echo $buttonAction; ?>')">
                        <?php echo $submitText; ?>
                    </button>
                    <a href="index.php?doc=campanha&opc=2" class="btn btn-default">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
    <?php
}

function renderFilterForm($filters = [], $categories = []) {
    if (empty($categories)) {
        $service = new CampanaService();
        $categories = $service->getCategories();
    }
    ?>
    <div class="campaign-filter-container">
        <h3>Filtros de Busqueda</h3>
        <form id="filterForm" method="get" class="form-inline">
            <input type="hidden" name="doc" value="campanha">
            <input type="hidden" name="opc" value="2">

            <div class="form-group">
                <label>Nombre:</label>
                <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($filters['nombre'] ?? ''); ?>" placeholder="Buscar por nombre">
            </div>

            <div class="form-group">
                <label>Tipo:</label>
                <select name="tipoCampana" class="form-control">
                    <option value="">-- Todos --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo ($filters['tipoCampana'] == $cat['id'] ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Estado:</label>
                <select name="estado" class="form-control">
                    <option value="">-- Todos --</option>
                    <option value="1" <?php echo ($filters['estado'] == '1' ? 'selected' : ''); ?>>Activo</option>
                    <option value="0" <?php echo ($filters['estado'] == '0' ? 'selected' : ''); ?>>Inactivo</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Buscar</button>
            <button type="button" class="btn btn-default" onclick="clearAllFilters()">Limpiar</button>
            <a href="index.php?doc=campanha&opc=1" class="btn btn-success">+ Nueva</a>
        </form>
    </div>
    <?php
}

function renderListTable($campaigns = [], $total_records = 0, $current_page = 1, $records_per_page = 50) {
    ?>
    <div class="campaigns-info">
        <p>Se encontraron <strong><?php echo intval($total_records); ?></strong> campañas.</p>
    </div>

    <!-- DESKTOP TABLE -->
    <div class="table-desktop">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Correos</th>
                    <th>SMS</th>
                    <th>Leidos</th>
                    <th>Creacion</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campaigns)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No hay campañas registradas</td>
                    </tr>
                <?php else: ?>
                    <?php $counter = (($current_page - 1) * $records_per_page) + 1; ?>
                    <?php foreach ($campaigns as $campaign): ?>
                        <tr class="campaign-row">
                            <td><?php echo $counter++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($campaign['nombre']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($campaign['tipoCampanaNombre'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="badge <?php echo ($campaign['estado'] == 1 ? 'badge-success' : 'badge-danger'); ?>">
                                    <?php echo ($campaign['estado'] == 1 ? 'Activo' : 'Inactivo'); ?>
                                </span>
                            </td>
                            <td class="text-center"><?php echo htmlspecialchars($campaign['totalCorreos'] ?? 0); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($campaign['totalSMS'] ?? 0); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($campaign['totalLeidos'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars(substr($campaign['fechaCreacion'] ?? '', 0, 10)); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="loadCampaignModal(<?php echo intval($campaign['id']); ?>, false)">Editar</button>
                                <button class="btn btn-sm btn-info" onclick="loadCampaignModal(<?php echo intval($campaign['id']); ?>, true)">Ver</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- MOBILE CARD VIEW -->
    <div class="table-mobile">
        <?php if (empty($campaigns)): ?>
            <div class="alert alert-info">No hay campañas registradas</div>
        <?php else: ?>
            <?php $counter = (($current_page - 1) * $records_per_page) + 1; ?>
            <?php foreach ($campaigns as $campaign): ?>
                <div class="campaign-card">
                    <div class="card-header">
                        <div class="card-title"><?php echo htmlspecialchars($campaign['nombre']); ?></div>
                        <span class="badge <?php echo ($campaign['estado'] == 1 ? 'badge-success' : 'badge-danger'); ?>">
                            <?php echo ($campaign['estado'] == 1 ? 'Activo' : 'Inactivo'); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="card-info-row">
                            <span class="card-label">Tipo:</span>
                            <span class="card-value"><?php echo htmlspecialchars($campaign['tipoCampanaNombre'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="card-info-row">
                            <span class="card-label">Correos:</span>
                            <span class="card-value"><?php echo htmlspecialchars($campaign['totalCorreos'] ?? 0); ?></span>
                        </div>
                        <div class="card-info-row">
                            <span class="card-label">SMS:</span>
                            <span class="card-value"><?php echo htmlspecialchars($campaign['totalSMS'] ?? 0); ?></span>
                        </div>
                        <div class="card-info-row">
                            <span class="card-label">Leidos:</span>
                            <span class="card-value"><?php echo htmlspecialchars($campaign['totalLeidos'] ?? 0); ?></span>
                        </div>
                        <div class="card-info-row">
                            <span class="card-label">Creacion:</span>
                            <span class="card-value"><?php echo htmlspecialchars(substr($campaign['fechaCreacion'] ?? '', 0, 10)); ?></span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-primary" onclick="loadCampaignModal(<?php echo intval($campaign['id']); ?>, false)">Editar</button>
                        <button class="btn btn-sm btn-info" onclick="loadCampaignModal(<?php echo intval($campaign['id']); ?>, true)">Ver</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}

function renderPagination($total_pages = 1, $current_page = 1, $filters = []) {
    $query_string = '';
    if (!empty($filters)) {
        $filter_params = [];
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $filter_params[] = urlencode($key) . '=' . urlencode($value);
            }
        }
        if (!empty($filter_params)) {
            $query_string = '&' . implode('&', $filter_params);
        }
    }
    ?>
    <nav class="pagination-container">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="<?php echo ($i == $current_page ? 'active' : ''); ?>">
                    <a href="index.php?doc=campanha&opc=2&pagina=<?php echo $i; echo $query_string; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php
}

function renderEditModal() {
    ?>
    <div id="editCampaignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Editar Campaña</h2>
                <button type="button" class="close" onclick="closeEditModal()">&times;</button>
            </div>
            <div id="modalLoading" class="modal-body text-center">
                <p>Cargando...</p>
            </div>
            <form id="editCampaignForm" method="post" action="index.php?doc=campanha&opc=2" class="d-none">
                <div class="modal-body" id="modalFormBody">
                    <input type="hidden" name="id" id="modalCampaignId">
                    <input type="hidden" name="funcion" id="modalFuncion" value="">
                    <input type="hidden" name="doc" value="campanha">
                    <input type="hidden" name="opc" value="2">

                    <div class="form-group">
                        <label>Nombre:</label>
                        <input type="text" id="modalNombre" name="nombre" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Tipo de Campaña:</label>
                        <select id="modalTipoCampana" name="tipoCampana" class="form-control" required></select>
                    </div>

                    <div class="form-group">
                        <label>Estado:</label>
                        <select id="modalEstado" name="estado" class="form-control" required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Fecha Inicial:</label>
                        <input type="date" id="modalFechaInicial" name="fechaInicial" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Fecha Final:</label>
                        <input type="date" id="modalFechaFinal" name="fechaFinal" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Observaciones:</label>
                        <textarea id="modalObservaciones" name="observaciones" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="submitModalDelete()">Eliminar</button>
                    <button type="button" class="btn btn-primary" onclick="submitModalEdit()">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}
?>
