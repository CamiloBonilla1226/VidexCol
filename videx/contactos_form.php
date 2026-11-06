<?php
/**
 * Contactos Form - View Layer
 * Pure view rendering with no business logic
 *
 * @package VIDEX
 * @version 1.0
 */

function renderContactForm($contact = [], $mode = 'create', $errors = [], $dropdown_data = []) {
    $is_edit = ($mode === 'edit');
    $title = $is_edit ? 'Editar Contacto' : 'Crear Contacto';

    // Pre-populate values
    $nombres = htmlspecialchars($contact['nombres'] ?? '');
    $email = htmlspecialchars($contact['email'] ?? '');
    $celular = htmlspecialchars($contact['celular'] ?? '');
    $celular2 = htmlspecialchars($contact['celular2'] ?? '');
    $telfijo = htmlspecialchars($contact['telfijo'] ?? '');
    $identificacion = htmlspecialchars($contact['identificacion'] ?? '');
    $tipoIdentificacion = intval($contact['tipoIdentificacion'] ?? 0);
    $direccion = htmlspecialchars($contact['direccion'] ?? '');
    $paginaWeb = htmlspecialchars($contact['paginaWeb'] ?? '');
    $notas = htmlspecialchars($contact['notas'] ?? '');
    $idCliente = intval($contact['idCliente'] ?? 0);

    $clients = $dropdown_data['clients'] ?? [];
    $identification_types = $dropdown_data['identification_types'] ?? [];
    $groups = $dropdown_data['groups'] ?? [];
    $contact_groups = $is_edit ? ($contact['groups'] ?? []) : [];
    ?>

    <div class="contactos-container">

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

        <h2 class="contactos-title"><?php echo $title; ?></h2>

        <form id="contactForm" method="POST" class="contactos-form">
            <input type="hidden" name="id" value="<?php echo intval($contact['id'] ?? 0); ?>">

            <!-- Tabs Navigation -->
            <div class="contactos-tabs">
                <button type="button" class="contactos-tab-button active" data-tab="general">
                    <i class="bi bi-person"></i> General
                </button>
                <button type="button" class="contactos-tab-button" data-tab="documentos">
                    <i class="bi bi-file-text"></i> Documentos
                </button>
                <button type="button" class="contactos-tab-button" data-tab="grupos">
                    <i class="bi bi-people"></i> Grupos
                </button>
            </div>

            <!-- Tab Content -->
            <div class="contactos-tab-content">

                <!-- GENERAL TAB -->
                <div id="general" class="contactos-tab-pane active">

                    <!-- Nombre (Full Width) -->
                    <div class="form-group full-width">
                        <label class="form-label" for="nombres">Nombre *</label>
                        <input type="text" id="nombres" name="nombres" class="form-control <?php echo isset($errors['nombres']) ? 'error' : ''; ?>"
                               value="<?php echo $nombres; ?>" placeholder="Nombre completo" maxlength="250" required>
                        <?php if (isset($errors['nombres'])): ?>
                            <span class="error-text"><?php echo $errors['nombres']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Cliente / Identificación (2 cols) -->
                    <div class="form-group">
                        <label class="form-label" for="idCliente">Cliente/Prospecto</label>
                        <select name="idCliente" id="idCliente" class="form-control">
                            <option value="0">-- Sin seleccionar --</option>
                            <?php foreach ($clients as $id => $name): ?>
                                <option value="<?php echo intval($id); ?>" <?php echo ($idCliente == $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="identificacion">Identificación</label>
                        <input type="text" id="identificacion" name="identificacion" class="form-control"
                               value="<?php echo $identificacion; ?>" placeholder="Número de identificación" maxlength="250">
                    </div>

                    <!-- Tipo Identificación / Dirección (2 cols) -->
                    <div class="form-group">
                        <label class="form-label" for="tipoIdentificacion">Tipo de Identificación</label>
                        <select name="tipoIdentificacion" id="tipoIdentificacion" class="form-control">
                            <option value="0">-- Seleccionar --</option>
                            <?php foreach ($identification_types as $id => $desc): ?>
                                <option value="<?php echo intval($id); ?>" <?php echo ($tipoIdentificacion == $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($desc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="direccion">Dirección</label>
                        <input type="text" id="direccion" name="direccion" class="form-control"
                               value="<?php echo $direccion; ?>" placeholder="Dirección completa" maxlength="250">
                    </div>

                    <!-- Email / Teléfono (2 cols) -->
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control <?php echo isset($errors['email']) ? 'error' : ''; ?>"
                               value="<?php echo $email; ?>" placeholder="correo@ejemplo.com" maxlength="250">
                        <?php if (isset($errors['email'])): ?>
                            <span class="error-text"><?php echo $errors['email']; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="telfijo">Teléfono Fijo</label>
                        <input type="text" id="telfijo" name="telfijo" class="form-control <?php echo isset($errors['telfijo']) ? 'error' : ''; ?>"
                               value="<?php echo $telfijo; ?>" placeholder="Teléfono" maxlength="20">
                        <?php if (isset($errors['telfijo'])): ?>
                            <span class="error-text"><?php echo $errors['telfijo']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Celulares (2 cols) -->
                    <div class="form-group">
                        <label class="form-label" for="celular">Celular 1</label>
                        <input type="text" id="celular" name="celular" class="form-control <?php echo isset($errors['celular']) ? 'error' : ''; ?>"
                               value="<?php echo $celular; ?>" placeholder="Celular 1" maxlength="20">
                        <?php if (isset($errors['celular'])): ?>
                            <span class="error-text"><?php echo $errors['celular']; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="celular2">Celular 2</label>
                        <input type="text" id="celular2" name="celular2" class="form-control <?php echo isset($errors['celular2']) ? 'error' : ''; ?>"
                               value="<?php echo $celular2; ?>" placeholder="Celular 2 (opcional)" maxlength="20">
                        <?php if (isset($errors['celular2'])): ?>
                            <span class="error-text"><?php echo $errors['celular2']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Página Web / Notas (2 cols then full) -->
                    <div class="form-group">
                        <label class="form-label" for="paginaWeb">Página Web</label>
                        <input type="text" id="paginaWeb" name="paginaWeb" class="form-control"
                               value="<?php echo $paginaWeb; ?>" placeholder="www.ejemplo.com" maxlength="250">
                    </div>

                    <div class="form-group">
                        <label class="form-label"></label>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label" for="notas">Notas</label>
                        <textarea id="notas" name="notas" class="form-control" rows="4" placeholder="Notas adicionales" maxlength="500"><?php echo $notas; ?></textarea>
                    </div>

                </div>

                <!-- DOCUMENTOS TAB (Edit only) -->
                <?php if ($is_edit): ?>
                <div id="documentos" class="contactos-tab-pane">
                    <div class="documento-section" style="grid-column: 1 / -1; width: 100%;">
                        <label class="form-label" style="margin-bottom: 1.5rem;">Gestión de Documentos</label>

                        <div class="form-group full-width">
                            <label class="form-label" for="documento">Seleccionar Documento</label>
                            <input type="file" id="documento" name="documento" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <span class="form-text text-muted">Formatos permitidos: PDF, DOC, DOCX, JPG, PNG (máx 5MB)</span>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label" for="descripcionDocumento">Descripción del Documento (Opcional)</label>
                            <input type="text" id="descripcionDocumento" name="descripcionDocumento" class="form-control" placeholder="Ej: 2022 Resultados de Examen" maxlength="500">
                            <span class="form-text text-muted">Si dejas en blanco, se usará el nombre del archivo</span>
                        </div>

                        <div class="form-group full-width">
                            <button type="button" id="uploadDocumentoBtn" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Subir Documento
                            </button>
                        </div>

                        <div id="uploadProgress" style="display: none; margin-top: 1rem;">
                            <div style="background: #e5e7eb; border-radius: 4px; height: 24px; overflow: hidden;">
                                <div id="progressBar" style="background: #2563eb; height: 100%; width: 0%; transition: width 0.3s ease;"></div>
                            </div>
                            <p id="uploadStatus" style="color: #6b7280; font-size: 13px; margin-top: 0.5rem;">Subiendo...</p>
                        </div>

                        <div style="margin-top: 1.5rem; border-top: 1px solid #e5e7eb; padding-top: 1.5rem;">
                            <p class="form-info">Documentos Adjuntos</p>
                            <ul id="documentoListItems" style="list-style: none; padding: 0; margin: 0;">
                                <li style="color: #6b7280; font-size: 13px;">Cargando documentos...</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- GRUPOS TAB -->
                <div id="grupos" class="contactos-tab-pane">
                    <label class="form-label">Seleccionar Grupos para envío</label>
                    <div class="grupos-grid">
                        <?php foreach ($groups as $id => $name): ?>
                            <div class="grupo-checkbox">
                                <input type="checkbox" id="grupo_<?php echo intval($id); ?>"
                                       name="listaasociar[]" value="<?php echo intval($id); ?>"
                                       <?php if (in_array($id, $contact_groups)) echo 'checked'; ?>>
                                <label for="grupo_<?php echo intval($id); ?>">
                                    <?php echo htmlspecialchars($name); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- Buttons -->
            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="submitContactForm('save')">
                    <?php echo $is_edit ? 'Actualizar' : 'Guardar'; ?>
                </button>
                <button type="button" class="btn btn-secondary" onclick="goToList()">
                    Cancelar
                </button>
                <?php if ($is_edit): ?>
                    <button type="button" class="btn btn-danger" style="margin-left: auto;" onclick="confirmDelete(<?php echo intval($contact['id']); ?>, '<?php echo htmlspecialchars($contact['nombres']); ?>')">
                        Eliminar
                    </button>
                <?php endif; ?>
            </div>

        </form>

    </div>

    <?php
}

function renderFilterForm($current_filters = [], $dropdown_data = []) {
    $nombres = htmlspecialchars($current_filters['bus_nombres'] ?? '');
    $email = htmlspecialchars($current_filters['bus_email'] ?? '');
    $celular = htmlspecialchars($current_filters['bus_celular'] ?? '');
    $idCliente = intval($current_filters['idCliente'] ?? 0);
    $bus_grupo = intval($current_filters['bus_grupo'] ?? 0);

    $clients = $dropdown_data['clients'] ?? [];
    $groups = $dropdown_data['groups'] ?? [];
    ?>

    <div class="contactos-filter-section">
        <form method="GET" class="contactos-filter-form">
            <input type="hidden" name="doc" value="contactos">
            <input type="hidden" name="opc" value="2">

            <!-- Row 1: Nombre (full width) -->
            <div class="filter-row">
                <div class="form-group full-width">
                    <label class="form-label" for="filter_nombres">Buscar por Nombre</label>
                    <input type="text" id="filter_nombres" name="bus_nombres" class="form-control"
                           placeholder="Ingrese nombre..." value="<?php echo $nombres; ?>" maxlength="250">
                </div>
            </div>

            <!-- Row 2: Email, Celular -->
            <div class="filter-row">
                <div class="form-group">
                    <label class="form-label" for="filter_email">Email</label>
                    <input type="text" id="filter_email" name="bus_email" class="form-control"
                           placeholder="Ingrese email..." value="<?php echo $email; ?>" maxlength="250">
                </div>
                <div class="form-group">
                    <label class="form-label" for="filter_celular">Celular</label>
                    <input type="text" id="filter_celular" name="bus_celular" class="form-control"
                           placeholder="Ingrese celular..." value="<?php echo $celular; ?>" maxlength="20">
                </div>
            </div>

            <!-- Row 3: Cliente, Grupo -->
            <div class="filter-row">
                <div class="form-group">
                    <label class="form-label" for="filter_idCliente">Cliente</label>
                    <select id="filter_idCliente" name="idCliente" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Todos --</option>
                        <?php foreach ($clients as $id => $name): ?>
                            <option value="<?php echo intval($id); ?>" <?php echo ($idCliente == $id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="filter_grupo">Grupo</label>
                    <select id="filter_grupo" name="bus_grupo" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Todos --</option>
                        <?php foreach ($groups as $id => $name): ?>
                            <option value="<?php echo intval($id); ?>" <?php echo ($bus_grupo == $id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Row 4: Buttons -->
            <div class="filter-buttons">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <button type="button" class="btn btn-secondary" onclick="clearFilters()">Limpiar</button>
                <a href="index.php?doc=contactos&opc=1" class="btn btn-success">+ Nuevo Contacto</a>
            </div>
        </form>
    </div>

    <?php
}

function renderContactTable($contacts = [], $total_records = 0, $current_page = 1, $page_size = 50, $filters = []) {
    $start_num = (($current_page - 1) * $page_size) + 1;
    ?>

    <div class="contactos-table-container">
        <div class="table-info">
            Se encontraron <strong><?php echo intval($total_records); ?></strong> contacto(s).
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Celular</th>
                    <th>Cliente</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contacts)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No hay contactos registrados</td>
                    </tr>
                <?php else: ?>
                    <?php $counter = $start_num; ?>
                    <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td><?php echo $counter; ?></td>
                            <td>
                                <a href="index.php?doc=contactos&opc=2&id=<?php echo intval($contact['id']); ?>">
                                    <strong><?php echo htmlspecialchars($contact['nombres']); ?></strong>
                                </a>
                                <?php if (!empty($contact['nomcliente'])): ?>
                                    <div style="font-size: 12px; color: #6b7280;">
                                        <?php echo htmlspecialchars($contact['nomcliente']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($contact['email']); ?></td>
                            <td><?php echo htmlspecialchars($contact['celular']); ?></td>
                            <td><?php echo htmlspecialchars($contact['nomcliente']); ?></td>
                            <td>
                                <a href="index.php?doc=contactos&opc=2&id=<?php echo intval($contact['id']); ?>" class="btn btn-sm btn-primary">
                                    Editar
                                </a>
                            </td>
                        </tr>
                        <?php $counter++; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    // Pagination
    $total_pages = ceil($total_records / $page_size);
    if ($total_pages > 1):
        renderPagination($total_pages, $current_page, $filters);
    endif;
}

function renderPagination($total_pages, $current_page, $filters) {
    $current_page = max(1, intval($current_page));
    $total_pages = max(1, intval($total_pages));
    ?>

    <div class="pagination-nav">
        <ul class="pagination">
            <?php if ($current_page > 1): ?>
                <li><a href="<?php echo buildPaginationUrl(1, $filters); ?>">« Primera</a></li>
                <li><a href="<?php echo buildPaginationUrl($current_page - 1, $filters); ?>">‹ Anterior</a></li>
            <?php endif; ?>

            <?php
            $start = max(1, $current_page - 5);
            $end = min($total_pages, $current_page + 5);

            if ($start > 1):
                ?>
                <li><a href="<?php echo buildPaginationUrl(1, $filters); ?>">1</a></li>
                <li><span>...</span></li>
                <?php
            endif;

            for ($i = $start; $i <= $end; $i++):
                if ($i == $current_page):
                    ?>
                    <li class="active"><a href="#"><?php echo $i; ?></a></li>
                    <?php
                else:
                    ?>
                    <li><a href="<?php echo buildPaginationUrl($i, $filters); ?>"><?php echo $i; ?></a></li>
                    <?php
                endif;
            endfor;

            if ($end < $total_pages):
                ?>
                <li><span>...</span></li>
                <li><a href="<?php echo buildPaginationUrl($total_pages, $filters); ?>"><?php echo $total_pages; ?></a></li>
                <?php
            endif;
            ?>

            <?php if ($current_page < $total_pages): ?>
                <li><a href="<?php echo buildPaginationUrl($current_page + 1, $filters); ?>">Siguiente ›</a></li>
                <li><a href="<?php echo buildPaginationUrl($total_pages, $filters); ?>">Última »</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <?php
}

function buildPaginationUrl($page, $filters) {
    $url = 'index.php?doc=contactos&opc=2&pagina=' . intval($page);

    if (!empty($filters['bus_nombres'])) {
        $url .= '&bus_nombres=' . urlencode($filters['bus_nombres']);
    }
    if (!empty($filters['bus_email'])) {
        $url .= '&bus_email=' . urlencode($filters['bus_email']);
    }
    if (!empty($filters['bus_celular'])) {
        $url .= '&bus_celular=' . urlencode($filters['bus_celular']);
    }
    if (!empty($filters['idCliente'])) {
        $url .= '&idCliente=' . intval($filters['idCliente']);
    }
    if (!empty($filters['bus_grupo'])) {
        $url .= '&bus_grupo=' . intval($filters['bus_grupo']);
    }

    return $url;
}
?>
