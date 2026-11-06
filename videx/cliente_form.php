<?php
/**
 * Cliente Form - View Layer
 * Pure view rendering with no business logic
 *
 * @package VIDEX
 * @version 1.0
 */

function renderClientForm($client = [], $mode = 'create', $errors = [], $tipos = [], $comerciales = []) {
    $is_edit = ($mode === 'edit');
    $title = $is_edit ? 'Editar Cliente' : 'Crear Cliente';

    // Prepopulate values
    $nombre = htmlspecialchars($client['nombre'] ?? '');
    $nit = htmlspecialchars($client['nit'] ?? '');
    $representante_legal = htmlspecialchars($client['representante_legal'] ?? '');
    $direccion = htmlspecialchars($client['direccion'] ?? '');
    $telefono = htmlspecialchars($client['telefono'] ?? '');
    $celular = htmlspecialchars($client['celular'] ?? '');
    $email = htmlspecialchars($client['email'] ?? '');
    $estado = intval($client['estado'] ?? 1);
    $tipoCliente = intval($client['tipoCliente'] ?? 0);
    $interno = intval($client['interno'] ?? 0);
    $observaciones = htmlspecialchars($client['observaciones'] ?? '');
    $paginaweb = htmlspecialchars($client['paginaweb'] ?? '');
    $idComercial = $client['idComercial'] ?? '';
    ?>

    <div class="cliente-container">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Errores de validación:</strong>
                <ul>
                    <?php foreach ($errors as $field => $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="clienteForm" method="POST" class="cliente-form">
            <input type="hidden" name="id" value="<?php echo intval($client['id'] ?? 0); ?>">
            <input type="hidden" name="funcion" id="funcion" value="">

            <!-- Nombre (Full Width) -->
            <div class="form-group full-width">
                <label class="form-label" for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" class="form-control <?php echo isset($errors['nombre']) ? 'error' : ''; ?>"
                       value="<?php echo $nombre; ?>" required maxlength="250">
                <?php if (isset($errors['nombre'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($errors['nombre']); ?></small>
                <?php endif; ?>
            </div>

            <!-- Tipo Cliente | Estado -->
            <div class="form-group">
                <label class="form-label" for="tipoCliente">Tipo de Cliente *</label>
                <select id="tipoCliente" name="tipoCliente" class="form-control <?php echo isset($errors['tipoCliente']) ? 'error' : ''; ?>" required>
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($tipos as $id => $desc): ?>
                        <option value="<?php echo intval($id); ?>" <?php echo ($tipoCliente == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($desc); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['tipoCliente'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($errors['tipoCliente']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="estado">Estado *</label>
                <select id="estado" name="estado" class="form-control" required>
                    <option value="1" <?php echo ($estado == 1) ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo ($estado == 0) ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>

            <!-- Comercial | Interno -->
            <div class="form-group">
                <label class="form-label" for="idComercial">Comercial Encargado</label>
                <input type="text" id="idComercial" name="idComercial" class="form-control <?php echo isset($errors['idComercial']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($idComercial); ?>" maxlength="250" placeholder="Nombre del comercial (opcional)">
                <?php if (isset($errors['idComercial'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($errors['idComercial']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="interno">¿Cliente o Propio? *</label>
                <select id="interno" name="interno" class="form-control" required>
                    <option value="0" <?php echo ($interno == 0) ? 'selected' : ''; ?>>Cliente Externo</option>
                    <option value="1" <?php echo ($interno == 1) ? 'selected' : ''; ?>>Propio</option>
                </select>
            </div>

            <!-- NIT | Representante Legal -->
            <div class="form-group">
                <label class="form-label" for="nit">NIT</label>
                <input type="text" id="nit" name="nit" class="form-control" value="<?php echo $nit; ?>" maxlength="250">
                <?php if (isset($errors['nit'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($errors['nit']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="representante_legal">Representante Legal</label>
                <input type="text" id="representante_legal" name="representante_legal" class="form-control"
                       value="<?php echo $representante_legal; ?>" maxlength="250">
                <?php if (isset($errors['representante_legal'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($errors['representante_legal']); ?></small>
                <?php endif; ?>
            </div>

            <!-- Dirección -->
            <div class="form-group full-width">
                <label class="form-label" for="direccion">Dirección</label>
                <input type="text" id="direccion" name="direccion" class="form-control" value="<?php echo $direccion; ?>" maxlength="250">
                <?php if (isset($errors['direccion'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($errors['direccion']); ?></small>
                <?php endif; ?>
            </div>

            <!-- Teléfono | Celular -->
            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" class="form-control" value="<?php echo $telefono; ?>" maxlength="20">
                <?php if (isset($errors['telefono'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($errors['telefono']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="celular">Celular</label>
                <input type="text" id="celular" name="celular" class="form-control" value="<?php echo $celular; ?>" maxlength="20">
                <?php if (isset($errors['celular'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($errors['celular']); ?></small>
                <?php endif; ?>
            </div>

            <!-- Email | Página Web -->
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo $email; ?>" maxlength="250">
                <?php if (isset($errors['email'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($errors['email']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="paginaweb">Página Web</label>
                <input type="text" id="paginaweb" name="paginaweb" class="form-control" value="<?php echo $paginaweb; ?>" maxlength="250">
                <?php if (isset($errors['paginaweb'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($errors['paginaweb']); ?></small>
                <?php endif; ?>
            </div>

            <!-- Observaciones (Full Width) -->
            <div class="form-group full-width">
                <label class="form-label" for="observaciones">Observaciones</label>
                <textarea id="observaciones" name="observaciones" class="form-control" rows="3" maxlength="500"><?php echo $observaciones; ?></textarea>
                <?php if (isset($errors['observaciones'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($errors['observaciones']); ?></small>
                <?php endif; ?>
            </div>

            <!-- Audit Info (Edit Only) -->
            <?php if ($is_edit && !empty($client)): ?>
                <div class="form-group full-width audit-info">
                    <small>
                        Creado: <?php echo htmlspecialchars($client['creadorNom'] ?? ''); ?>
                        el <?php echo htmlspecialchars($client['creacionFecha'] ?? ''); ?>
                        <?php if (!empty($client['modNom'])): ?>
                            | Modificado: <?php echo htmlspecialchars($client['modNom']); ?>
                            el <?php echo htmlspecialchars($client['modFecha'] ?? ''); ?>
                        <?php endif; ?>
                    </small>
                </div>
            <?php endif; ?>

            <!-- Buttons -->
            <div class="form-actions full-width">
                <?php if ($is_edit): ?>
                    <button type="button" class="btn btn-primary" onclick="submitClientForm('actualizar')">Actualizar</button>
                    <a href="index.php?doc=cliente&opc=2" class="btn btn-secondary">Cancelar</a>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo intval($client['id']); ?>, '<?php echo addslashes(htmlspecialchars($client['nombre'])); ?>')">Eliminar Cliente</button>
                <?php else: ?>
                    <button type="button" class="btn btn-primary" onclick="submitClientForm('insertar')">Guardar Cliente</button>
                    <a href="index.php?doc=cliente&opc=2" class="btn btn-secondary">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php
}

function renderFilterForm($current_filters = [], $tipos = [], $comerciales = []) {
    $nombre = htmlspecialchars($current_filters['nombre'] ?? '');
    $tipoCliente = intval($current_filters['tipoCliente'] ?? 0);
    $idComercial = intval($current_filters['idComercial'] ?? 0);
    $estado = $current_filters['estado'] ?? '';
    ?>

    <div class="cliente-filter-section">
        <form method="GET" class="cliente-filter-form">
            <input type="hidden" name="doc" value="cliente">
            <input type="hidden" name="opc" value="2">

            <!-- Nombre -->
            <div class="form-group">
                <label class="form-label" for="filter_nombre">Buscar por Nombre</label>
                <input type="text" id="filter_nombre" name="nombre" class="form-control"
                       placeholder="Ingrese nombre..." value="<?php echo $nombre; ?>" maxlength="250">
            </div>

            <!-- Tipo | Estado -->
            <div class="form-group">
                <label class="form-label" for="filter_tipo">Tipo de Cliente</label>
                <select id="filter_tipo" name="tipoCliente" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Todos --</option>
                    <?php foreach ($tipos as $id => $desc): ?>
                        <option value="<?php echo intval($id); ?>" <?php echo ($tipoCliente == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($desc); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="filter_estado">Estado</label>
                <select id="filter_estado" name="estado" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Todos --</option>
                    <option value="1" <?php echo ($estado === '1') ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo ($estado === '0') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>

            <!-- Comercial -->
            <div class="form-group">
                <label class="form-label" for="filter_comercial">Comercial</label>
                <select id="filter_comercial" name="idComercial" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Todos --</option>
                    <?php foreach ($comerciales as $id => $com_nombre): ?>
                        <option value="<?php echo intval($id); ?>" <?php echo ($idComercial == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($com_nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Buttons -->
            <div class="filter-buttons">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <button type="button" class="btn btn-secondary" onclick="clearFilters()">Limpiar</button>
                <a href="index.php?doc=cliente&opc=1" class="btn btn-success">+ Nuevo Cliente</a>
            </div>
        </form>
    </div>

    <?php
}

function renderClientTable($clients = [], $total_records = 0, $current_page = 1, $page_size = 50, $filters = []) {
    $start_num = (($current_page - 1) * $page_size) + 1;
    ?>

    <div class="cliente-table-container">
        <div class="table-info">
            Se encontraron <strong><?php echo intval($total_records); ?></strong> cliente(s).
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Comercial</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay clientes registrados</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clients as $i => $client): ?>
                        <tr>
                            <td><?php echo ($start_num + $i); ?></td>
                            <td><strong><?php echo htmlspecialchars($client['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($client['tipoClienteNom'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($client['creadorNom'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(substr($client['email'] ?? '', 0, 30)); ?></td>
                            <td><?php echo htmlspecialchars($client['celular'] ?? $client['telefono'] ?? '-'); ?></td>
                            <td>
                                <span class="badge <?php echo ($client['estado'] == 1) ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ($client['estado'] == 1) ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editClientModal(<?php echo intval($client['id']); ?>)">Editar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    // Pagination
    $total_pages = ceil($total_records / $page_size);
    if ($total_pages > 1) {
        renderPagination($total_pages, $current_page, $filters);
    }
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

    <nav class="pagination-nav">
        <ul class="pagination">
            <?php if ($current_page > 1): ?>
                <li><a href="index.php?doc=cliente&opc=2&pag=1<?php echo $query_string; ?>">« Primera</a></li>
                <li><a href="index.php?doc=cliente&opc=2&pag=<?php echo ($current_page - 1); ?><?php echo $query_string; ?>">‹ Anterior</a></li>
            <?php endif; ?>

            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <li <?php echo ($i == $current_page) ? 'class="active"' : ''; ?>>
                    <a href="index.php?doc=cliente&opc=2&pag=<?php echo $i; ?><?php echo $query_string; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <li><a href="index.php?doc=cliente&opc=2&pag=<?php echo ($current_page + 1); ?><?php echo $query_string; ?>">Siguiente ›</a></li>
                <li><a href="index.php?doc=cliente&opc=2&pag=<?php echo $total_pages; ?><?php echo $query_string; ?>">Última »</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <?php
}

/**
 * Render Client Modal Form
 * Modal overlay for editing cliente via AJAX
 */
function renderClientModal($client = [], $tipos = [], $comerciales = []) {
    $id = intval($client['id'] ?? 0);
    $nombre = htmlspecialchars($client['nombre'] ?? '');
    $nit = htmlspecialchars($client['nit'] ?? '');
    $representante_legal = htmlspecialchars($client['representante_legal'] ?? '');
    $direccion = htmlspecialchars($client['direccion'] ?? '');
    $telefono = htmlspecialchars($client['telefono'] ?? '');
    $celular = htmlspecialchars($client['celular'] ?? '');
    $email = htmlspecialchars($client['email'] ?? '');
    $estado = intval($client['estado'] ?? 1);
    $tipoCliente = intval($client['tipoCliente'] ?? 0);
    $interno = intval($client['interno'] ?? 0);
    $observaciones = htmlspecialchars($client['observaciones'] ?? '');
    $paginaweb = htmlspecialchars($client['paginaweb'] ?? '');
    $idComercial = $client['idComercial'] ?? '';
    ?>

    <div id="clienteModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2 class="modal-title">Editar Cliente</h2>
                <button class="modal-close" onclick="closeClientModal()" aria-label="Cerrar">&times;</button>
            </div>

            <div class="modal-body">
                <form id="clienteModalForm" class="cliente-modal-form">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="funcion" value="actualizar">

                    <!-- Nombre (Full Width) -->
                    <div class="form-group full-width">
                        <label class="form-label" for="modal_nombre">Nombre *</label>
                        <input type="text" id="modal_nombre" name="nombre" class="form-control"
                               value="<?php echo $nombre; ?>" required maxlength="250">
                    </div>

                    <!-- Tipo Cliente | Estado -->
                    <div class="form-group">
                        <label class="form-label" for="modal_tipoCliente">Tipo de Cliente *</label>
                        <select id="modal_tipoCliente" name="tipoCliente" class="form-control" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($tipos as $type_id => $desc): ?>
                                <option value="<?php echo intval($type_id); ?>" <?php echo ($tipoCliente == $type_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($desc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modal_estado">Estado *</label>
                        <select id="modal_estado" name="estado" class="form-control" required>
                            <option value="1" <?php echo ($estado == 1) ? 'selected' : ''; ?>>Activo</option>
                            <option value="0" <?php echo ($estado == 0) ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>

                    <!-- Comercial | Interno -->
                    <div class="form-group">
                        <label class="form-label" for="modal_idComercial">Comercial Encargado</label>
                        <input type="text" id="modal_idComercial" name="idComercial" class="form-control" value="<?php echo htmlspecialchars($idComercial); ?>" maxlength="250" placeholder="Nombre del comercial (opcional)">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modal_interno">¿Cliente o Propio? *</label>
                        <select id="modal_interno" name="interno" class="form-control" required>
                            <option value="0" <?php echo ($interno == 0) ? 'selected' : ''; ?>>Cliente Externo</option>
                            <option value="1" <?php echo ($interno == 1) ? 'selected' : ''; ?>>Propio</option>
                        </select>
                    </div>

                    <!-- NIT | Representante Legal -->
                    <div class="form-group">
                        <label class="form-label" for="modal_nit">NIT</label>
                        <input type="text" id="modal_nit" name="nit" class="form-control" value="<?php echo $nit; ?>" maxlength="250">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modal_representante_legal">Representante Legal</label>
                        <input type="text" id="modal_representante_legal" name="representante_legal" class="form-control"
                               value="<?php echo $representante_legal; ?>" maxlength="250">
                    </div>

                    <!-- Dirección -->
                    <div class="form-group full-width">
                        <label class="form-label" for="modal_direccion">Dirección</label>
                        <input type="text" id="modal_direccion" name="direccion" class="form-control" value="<?php echo $direccion; ?>" maxlength="250">
                    </div>

                    <!-- Teléfono | Celular -->
                    <div class="form-group">
                        <label class="form-label" for="modal_telefono">Teléfono</label>
                        <input type="text" id="modal_telefono" name="telefono" class="form-control" value="<?php echo $telefono; ?>" maxlength="20">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modal_celular">Celular</label>
                        <input type="text" id="modal_celular" name="celular" class="form-control" value="<?php echo $celular; ?>" maxlength="20">
                    </div>

                    <!-- Email | Página Web -->
                    <div class="form-group">
                        <label class="form-label" for="modal_email">Email</label>
                        <input type="email" id="modal_email" name="email" class="form-control" value="<?php echo $email; ?>" maxlength="250">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modal_paginaweb">Página Web</label>
                        <input type="text" id="modal_paginaweb" name="paginaweb" class="form-control" value="<?php echo $paginaweb; ?>" maxlength="250">
                    </div>

                    <!-- Observaciones (Full Width) -->
                    <div class="form-group full-width">
                        <label class="form-label" for="modal_observaciones">Observaciones</label>
                        <textarea id="modal_observaciones" name="observaciones" class="form-control" rows="3" maxlength="500"><?php echo $observaciones; ?></textarea>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeClientModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="submitClientModalForm()">Guardar Cambios</button>
            </div>
        </div>
    </div>

    <?php
}

/**
 * Render Client Card (Mobile/Tablet)
 * Card layout for responsive display
 */
function renderClientCard($client = []) {
    $id = intval($client['id'] ?? 0);
    $nombre = htmlspecialchars($client['nombre'] ?? 'Sin nombre');
    $tipoClienteNom = htmlspecialchars($client['tipoClienteNom'] ?? 'N/A');
    $email = htmlspecialchars($client['email'] ?? '-');
    $celular = htmlspecialchars($client['celular'] ?? '');
    $telefono = htmlspecialchars($client['telefono'] ?? '');
    $direccion = htmlspecialchars($client['direccion'] ?? '-');
    $nit = htmlspecialchars($client['nit'] ?? '-');
    $estado = intval($client['estado'] ?? 1);
    $estado_text = ($estado == 1) ? 'Activo' : 'Inactivo';
    $estado_class = ($estado == 1) ? 'badge-success' : 'badge-danger';
    $telefonoDisplay = $celular ? $celular : ($telefono ? $telefono : '-');
    ?>

    <div class="cliente-card">
        <div class="card-header">
            <span><?php echo $nombre; ?></span>
            <span class="badge <?php echo $estado_class; ?>"><?php echo $estado_text; ?></span>
        </div>

        <div class="card-body">
            <div class="card-body-row">
                <span class="card-body-label">Tipo:</span>
                <span class="card-body-value"><?php echo $tipoClienteNom; ?></span>
            </div>

            <div class="card-body-row">
                <span class="card-body-label">Email:</span>
                <span class="card-body-value"><?php echo $email; ?></span>
            </div>

            <div class="card-body-row">
                <span class="card-body-label">Teléfono:</span>
                <span class="card-body-value"><?php echo $telefonoDisplay; ?></span>
            </div>

            <div class="card-body-row">
                <span class="card-body-label">Dirección:</span>
                <span class="card-body-value"><?php echo $direccion; ?></span>
            </div>

            <div class="card-body-row">
                <span class="card-body-label">NIT:</span>
                <span class="card-body-value"><?php echo $nit; ?></span>
            </div>
        </div>

        <div class="card-footer">
            <button class="btn btn-primary btn-sm" onclick="editClientModal(<?php echo $id; ?>)">Editar</button>
            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $id; ?>, '<?php echo addslashes($nombre); ?>')">Eliminar</button>
        </div>
    </div>

    <?php
}

/**
 * Render Cards for Mobile/Tablet List
 * Converts table data into card layout
 */
function renderClientCardsList($clients = [], $total_records = 0, $current_page = 1, $page_size = 50, $filters = []) {
    $start_num = (($current_page - 1) * $page_size) + 1;
    ?>

    <div class="table-mobile">
        <div class="table-info">
            Se encontraron <strong><?php echo intval($total_records); ?></strong> cliente(s).
        </div>

        <?php if (empty($clients)): ?>
            <div style="padding: 2rem; text-align: center; color: #6b7280;">
                No hay clientes registrados
            </div>
        <?php else: ?>
            <?php foreach ($clients as $client): ?>
                <?php renderClientCard($client); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php
    // Pagination
    $total_pages = ceil($total_records / $page_size);
    if ($total_pages > 1) {
        renderPagination($total_pages, $current_page, $filters);
    }
}
?>
