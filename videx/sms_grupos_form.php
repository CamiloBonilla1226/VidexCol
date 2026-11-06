<?php
/**
 * SMS Grupos Form/View Layer
 * All HTML rendering for forms, tables, filters, modals
 *
 * @package VIDEX
 * @version 1.0
 */

function showToastMessage($message, $type = 'info') {
    $bgClass = $type == 'success' ? 'alert-success' : ($type == 'danger' ? 'alert-danger' : 'alert-info');
    ?>
    <div class="alert <?php echo $bgClass; ?> alert-dismissible fade show" role="alert" style="margin: 20px;">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php
}

function renderGroupForm($data = [], $mode = 'create', $errors = []) {
    $title = ($mode == 'edit') ? 'Editar Grupo' : 'Crear Grupo';
    $submitText = ($mode == 'edit') ? 'Actualizar' : 'Guardar';
    ?>
    <div class="sms-grupos-container">
        <div class="grupos-header">
            <h2><?php echo $title; ?></h2>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Errores de validación:</strong>
                <ul>
                    <?php foreach ($errors as $field => $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="form1" class="grupos-form" method="post">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['id'] ?? 0); ?>">
            <input type="hidden" name="funcion" id="funcion" value="">
            <input type="hidden" name="doc" value="sms_grupos">
            <input type="hidden" name="opc" value="2">

            <div class="form-group">
                <label class="form-label">Nombre del grupo:</label>
                <input type="text" name="nombre" id="nombre" class="form-control"
                       value="<?php echo htmlspecialchars($data['nombre'] ?? ''); ?>" required>
                <?php if (isset($errors['nombre'])): ?>
                    <small class="form-text text-danger"><?php echo htmlspecialchars($errors['nombre']); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción:</label>
                <textarea name="descripcion" id="descripcion" class="form-control" rows="4"><?php echo htmlspecialchars($data['descripcion'] ?? ''); ?></textarea>
                <?php if (isset($errors['descripcion'])): ?>
                    <small class="form-text text-danger"><?php echo htmlspecialchars($errors['descripcion']); ?></small>
                <?php endif; ?>
            </div>

            <?php if ($mode == 'edit'): ?>
                <div class="form-group">
                    <label class="form-label">Cargue masivo de usuarios (TSV):</label>
                    <small class="form-text text-muted">Formato: nombre &lt;tab&gt; identificación &lt;tab&gt; dirección &lt;tab&gt; web &lt;tab&gt; email &lt;tab&gt; teléfono &lt;tab&gt; celular &lt;tab&gt; celular2</small>
                    <textarea name="cargue_masivo" id="cargue_masivo" class="form-control" rows="5"></textarea>
                </div>
            <?php endif; ?>

            <div class="form-group text-center">
                <button type="submit" class="btn btn-primary"><?php echo $submitText; ?></button>
                <a href="index.php?doc=sms_grupos&opc=2" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>

        <?php if ($mode == 'edit'): ?>
            <div class="form-group text-center" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccc;">
                <h4>Opciones avanzadas</h4>
                <button type="button" class="btn btn-warning" onclick="limpiarGrupo()">Limpiar Grupo (Eliminar todos los usuarios)</button>
                <small class="form-text text-muted">Esta acción eliminará todos los usuarios del grupo pero NO eliminará el grupo.</small>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function limpiarGrupo() {
            if (confirm('¿ESTÁ SEGURO que desea ELIMINAR todos los usuarios del grupo?\n\nEsta acción no se puede deshacer.')) {
                document.getElementById('funcion').value = 'limpiar';
                document.getElementById('form1').submit();
            }
            return false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('form1').addEventListener('submit', function(e) {
                const nombre = document.getElementById('nombre').value.trim();
                if (!nombre) {
                    alert('Por favor ingrese el nombre del grupo');
                    e.preventDefault();
                    return false;
                }
                return true;
            });
        });
    </script>
    <?php
}

function renderGroupList($groups = [], $total_records = 0, $current_page = 1, $records_per_page = 50, $filters = []) {
    $total_pages = ceil($total_records / $records_per_page);
    ?>
    <div class="sms-grupos-container">
        <div class="grupos-header">
            <h2>Gestión de Grupos SMS</h2>
            <a href="index.php?doc=sms_grupos&opc=1" class="btn btn-success">+ Crear Grupo</a>
        </div>

        <div class="grupos-search">
            <form method="get" class="groups-filter-form">
                <input type="hidden" name="doc" value="sms_grupos">
                <input type="hidden" name="opc" value="2">

                <div class="form-group">
                    <label class="form-label">Buscar por nombre:</label>
                    <input type="text" name="nombre" class="form-control"
                           value="<?php echo htmlspecialchars($filters['nombre'] ?? ''); ?>"
                           placeholder="Nombre del grupo...">
                </div>

                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <a href="index.php?doc=sms_grupos&opc=2" class="btn btn-secondary">Limpiar filtros</a>
                </div>
            </form>
        </div>

        <div class="grupos-info">
            <p>Se encontraron <strong><?php echo intval($total_records); ?></strong> grupos.</p>
        </div>

        <!-- DESKTOP TABLE -->
        <div class="table-desktop">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Usuarios</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($groups)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No hay grupos registrados</td>
                        </tr>
                    <?php else: ?>
                        <?php $counter = (($current_page - 1) * $records_per_page) + 1; ?>
                        <?php foreach ($groups as $group): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($group['nombre']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars(substr($group['descripcion'], 0, 50)); ?></td>
                                <td class="text-center">
                                    <a href="index.php?doc=contactos&opc=2&bus_grupo=<?php echo intval($group['id']); ?>">
                                        <?php echo intval($group['conteo']); ?>
                                    </a>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="loadGroupModal(<?php echo intval($group['id']); ?>)">Editar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- MOBILE CARD VIEW -->
        <div class="table-mobile">
            <?php if (empty($groups)): ?>
                <div class="alert alert-info">No hay grupos registrados</div>
            <?php else: ?>
                <?php $counter = (($current_page - 1) * $records_per_page) + 1; ?>
                <?php foreach ($groups as $group): ?>
                    <div class="grupo-card">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($group['nombre']); ?></h3>
                            <span class="card-counter"><?php echo intval($group['conteo']); ?> usuarios</span>
                        </div>
                        <div class="card-body">
                            <p><?php echo htmlspecialchars(substr($group['descripcion'], 0, 100)) . (strlen($group['descripcion']) > 100 ? '...' : ''); ?></p>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-sm btn-primary" onclick="loadGroupModal(<?php echo intval($group['id']); ?>)">Editar</button>
                            <a href="index.php?doc=contactos&opc=2&bus_grupo=<?php echo intval($group['id']); ?>" class="btn btn-sm btn-info">Ver usuarios</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="pagination-container">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="<?php echo ($i == $current_page ? 'active' : ''); ?>">
                            <a href="index.php?doc=sms_grupos&opc=2&pagina=<?php echo $i; ?><?php echo !empty($filters['nombre']) ? '&nombre=' . urlencode($filters['nombre']) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- EDIT MODAL -->
    <?php renderEditModal(); ?>

    <?php
}

function renderEditModal() {
    ?>
    <div id="editGroupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Editar Grupo</h2>
                <button type="button" class="close" onclick="closeEditModal()">&times;</button>
            </div>
            <div id="modalLoading" class="modal-body text-center">
                <p>Cargando...</p>
            </div>
            <form id="editGroupForm" method="post" action="index.php?doc=sms_grupos&opc=2" class="d-none">
                <div class="modal-body" id="modalFormBody">
                    <input type="hidden" name="id" id="modalGroupId">
                    <input type="hidden" name="funcion" id="modalFuncion" value="">
                    <input type="hidden" name="doc" value="sms_grupos">
                    <input type="hidden" name="opc" value="2">

                    <div class="form-group">
                        <label class="form-label">Nombre del grupo:</label>
                        <input type="text" id="modalNombre" name="nombre" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción:</label>
                        <textarea id="modalDescripcion" name="descripcion" class="form-control" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cargue masivo de usuarios (TSV):</label>
                        <small class="form-text text-muted">Formato: nombre &lt;tab&gt; identificación &lt;tab&gt; dirección &lt;tab&gt; web &lt;tab&gt; email &lt;tab&gt; teléfono &lt;tab&gt; celular &lt;tab&gt; celular2</small>
                        <textarea id="modalCargue" name="cargue_masivo" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="submitModalClear()">Limpiar Grupo</button>
                    <button type="button" class="btn btn-primary" onclick="submitModalEdit()">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}
?>
