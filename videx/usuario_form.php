<?php
/**
 * Usuario Form Rendering
 * Pure view layer - no PHP logic, only data presentation
 */

require_once('usuario_config.php');

class UsuarioForm {

    private function isDebugEnabled() {
        $debugGet = isset($_GET['debug']) ? $_GET['debug'] : '';
        $debugPost = isset($_POST['debug']) ? $_POST['debug'] : '';
        return ($debugGet === '1' || $debugGet === 'true' || $debugPost === '1' || $debugPost === 'true');
    }

    /**
     * Main render method
     */
    public function render($opc, $user = null, $permissions = [], $documents = [], $clients = [], $menus = [], $graphs = [], $availableClients = [], $errors = []) {
        $debugEnabled = $this->isDebugEnabled();

        // Start container
        echo '<div class="container usuario-form-container" style="max-width: 1200px; margin: 20px auto; padding: 20px; background: white; border: 1px solid #ccc;">';

        if ($debugEnabled) {
            $this->renderDebugPanel();
        }

        // Start form
        $this->renderFormStart($opc, $user, $debugEnabled);

        // Display errors
        if (!empty($errors)) {
            $this->renderErrors($errors);
        }

        // Render tabs
        echo '<div class="tab-content">';

        // 1. General Tab
        $this->renderGeneralTab($user);

        // 2. Company Tab
        $this->renderCompanyTab($user);

        // 3. Provider Tab (only for providers)
        if ($user && $user['tipo'] == USER_TYPE_PROVIDER) {
            $this->renderProviderTab($user);
        }

        // 4. Access Tab
        $this->renderAccessTab($user);

        // 5. Permissions Tab
        $this->renderPermissionsTab($user, $permissions, $menus);

        // 6. Graph Permissions Tab
        $this->renderGraphPermissionsTab($user, $permissions, $graphs);

        // 7. Documents Tab
        $this->renderDocumentsTab($user, $documents);

        // 8. Observaciones Tab
        $this->renderObservationsTab($user);

        echo '</div>'; // Close tab-content

        // Form buttons
        $this->renderFormButtons($opc, $user);

        // End form
        echo '</form>';

        // Close container
        echo '</div>';
    }

    /**
     * Render form start with tabs header
     */
    private function renderFormStart($opc, $user, $debugEnabled = false) {
        $csrfToken = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrfToken;

        echo '<form method="POST" action="" enctype="multipart/form-data" class="form" id="usuarioForm" novalidate>';
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">';
        echo '<input type="hidden" name="opc" value="' . $opc . '">';
        echo '<input type="hidden" name="funcion" id="usuario_funcion" value="' . ($opc == 1 ? 'insertar' : 'actualizar') . '">';
        if ($debugEnabled) {
            echo '<input type="hidden" name="debug" value="1">';
        }
        if ($user && isset($user['id'])) {
            echo '<input type="hidden" name="id" value="' . (int)$user['id'] . '">';
        }

        // Tabs header
        echo '<ul class="nav nav-tabs" role="tablist">';
        echo $this->renderTabHeader('general', 'General', 'bi-person', true);
        echo $this->renderTabHeader('company', 'Empresa', 'bi-building');
        if ($user && $user['tipo'] == USER_TYPE_PROVIDER) {
            echo $this->renderTabHeader('provider', 'Proveedor', 'bi-briefcase');
        }
        echo $this->renderTabHeader('access', 'Acceso', 'bi-shield-lock');
        echo $this->renderTabHeader('permissions', 'Permisos', 'bi-key');
        echo $this->renderTabHeader('graphs', 'Gráficas', 'bi-bar-chart');
        echo $this->renderTabHeader('documents', 'Documentos', 'bi-file-earmark');
        echo $this->renderTabHeader('observaciones', 'Observaciones', 'bi-chat-dots');
        echo '</ul>';
    }

    /**
     * Render single tab header
     */
    private function renderTabHeader($id, $label, $icon, $active = false) {
        $activeClass = $active ? 'active' : '';
        return '<li class="nav-item">
                    <a class="nav-link ' . $activeClass . '" href="#' . $id . '" data-bs-toggle="tab" role="tab">
                        <i class="bi ' . $icon . '"></i> ' . htmlspecialchars($label) . '
                    </a>
                </li>';
    }

    /**
     * Render errors
     */
    private function renderErrors($errors) {
        if (empty($errors)) return;
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        foreach ($errors as $error) {
            echo '<div>' . htmlspecialchars($error) . '</div>';
        }
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }

    private function renderDebugPanel() {
        echo '<div id="usuario-debug-panel" class="alert alert-warning" style="font-family: monospace; white-space: pre-wrap; word-break: break-word;">';
        echo '<strong>Debug Usuario (debug=1)</strong><br>';
        echo '<div id="usuario-debug-log">Esperando eventos...</div>';
        echo '</div>';

        echo '<script>';
        echo '(function () {';
        echo '  var box = document.getElementById("usuario-debug-log");';
        echo '  if (!box) return;';
        echo '  function toText(v) {';
        echo '    try {';
        echo '      if (v === null) return "null";';
        echo '      if (typeof v === "undefined") return "undefined";';
        echo '      if (typeof v === "string") return v;';
        echo '      if (v && v.stack) return String(v.stack);';
        echo '      return JSON.stringify(v);';
        echo '    } catch (e) {';
        echo '      return String(v);';
        echo '    }';
        echo '  }';
        echo '  function log(msg) {';
        echo '    var line = new Date().toISOString() + " | " + msg;';
        echo '    box.textContent = line + "\\n" + box.textContent;';
        echo '  }';
        echo '  window.addEventListener("error", function (event) {';
        echo '    log("window.error: " + event.message + " @ " + event.filename + ":" + event.lineno + ":" + event.colno);';
        echo '    if (event.error) log(toText(event.error));';
        echo '  });';
        echo '  window.addEventListener("unhandledrejection", function (event) {';
        echo '    log("unhandledrejection: " + toText(event.reason));';
        echo '  });';
        echo '  var origError = console.error;';
        echo '  console.error = function () {';
        echo '    var args = Array.prototype.slice.call(arguments).map(toText).join(" | ");';
        echo '    log("console.error: " + args);';
        echo '    return origError.apply(console, arguments);';
        echo '  };';
        echo '  log("Debug JS activo");';
        echo '})();';
        echo '</script>';
    }

    /**
     * Render General Tab
     */
    private function renderGeneralTab($user) {
        echo '<div class="tab-pane fade in active" id="general">';

        echo '<div class="form-group">';
        echo '<label class="form-label">Tipo de Usuario</label>';
        echo '<select name="tipo" class="form-control" id="tipoUsuario">';
        foreach (USER_TYPES as $val => $label) {
            $selected = ($user && $user['tipo'] == $val) ? 'selected' : '';
            echo '<option value="' . $val . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label class="form-label">Tipo de Identificación</label>';
        echo '<select name="tipoIdentificacion" class="form-control">';
        foreach (ID_TYPES as $code => $label) {
            $selected = ($user && $user['tipoIdentificacion'] == $code) ? 'selected' : '';
            echo '<option value="' . $code . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label class="form-label">Identificación *</label>';
        $identification = $user ? htmlspecialchars($user['identificacion']) : '';
        echo '<input type="text" name="identificacion" class="form-control" value="' . $identification . '" required>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label class="form-label">Nombre *</label>';
        $nombre = $user ? htmlspecialchars($user['nombre']) : '';
        echo '<input type="text" name="nombre" class="form-control" value="' . $nombre . '" required>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label class="form-label">Correo Electrónico</label>';
        $email = $user ? htmlspecialchars($user['email']) : '';
        echo '<input type="email" name="email" class="form-control" value="' . $email . '">';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label class="form-label">Contraseña' . ($user ? '' : ' *') . '</label>';
        echo '<input type="password" name="password" class="form-control" ' . ($user ? '' : 'required') . '>';
        if ($user) {
            echo '<small class="form-text text-muted">Dejar en blanco para mantener la contraseña actual</small>';
        }
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label class="form-label">Confirmar Contraseña' . ($user ? '' : ' *') . '</label>';
        echo '<input type="password" name="password_confirm" class="form-control" ' . ($user ? '' : 'required') . '>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label class="form-label">Dirección</label>';
        $direccion = $user ? htmlspecialchars($user['direccion']) : '';
        echo '<input type="text" name="direccion" class="form-control" value="' . $direccion . '">';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render Company Tab
     */
    private function renderCompanyTab($user) {
        echo '<div class="tab-pane fade" id="company">';

        echo '<div class="form-group">';
        echo '<label class="form-label">Teléfono 1</label>';
        $tel1 = $user ? htmlspecialchars($user['telefono1']) : '';
        echo '<input type="tel" name="telefono1" class="form-control" value="' . $tel1 . '">';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label class="form-label">Teléfono 2</label>';
        $tel2 = $user ? htmlspecialchars($user['telefono2']) : '';
        echo '<input type="tel" name="telefono2" class="form-control" value="' . $tel2 . '">';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label class="form-label">Celular</label>';
        $celular = $user ? htmlspecialchars($user['celular']) : '';
        echo '<input type="tel" name="celular" class="form-control" value="' . $celular . '">';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label class="form-label">Celular 2</label>';
        $celular2 = $user ? htmlspecialchars($user['celular2'] ?? '') : '';
        echo '<input type="tel" name="celular2" class="form-control" value="' . $celular2 . '">';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label class="form-label">Sitio Web</label>';
        $url = $user ? htmlspecialchars($user['url']) : '';
        echo '<input type="url" name="url" class="form-control" value="' . $url . '">';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render Provider Tab (only for provider type users)
     */
    private function renderProviderTab($user) {
        echo '<div class="tab-pane fade" id="provider">';

        echo '<div class="alert alert-info">';
        echo '<strong>Información de Proveedor</strong>';
        echo '<p>Datos específicos para proveedores de servicios en el sistema.</p>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label class="form-label">RADICADO</label>';
        $radicado = $user ? htmlspecialchars($user['radicado'] ?? '') : '';
        echo '<input type="text" name="radicado" class="form-control" value="' . $radicado . '">';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render Access Tab
     */
    private function renderAccessTab($user) {
        echo '<div class="tab-pane fade" id="access">';

        echo '<div class="alert alert-warning">';
        echo '<strong>Control de Acceso</strong>';
        echo '<p>Configura el acceso y permisos del usuario al sistema.</p>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && $user['acceso'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="acceso" value="1" class="form-check-input" id="acceso" ' . $checked . '>';
        echo '<label class="form-check-label" for="acceso">Usuario Habilitado</label>';
        echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && $user['acceso_solo_lectura'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="acceso_solo_lectura" value="1" class="form-check-input" id="soloLectura" ' . $checked . '>';
        echo '<label class="form-check-label" for="soloLectura">Solo Lectura</label>';
        echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && $user['acceso_graphs'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="acceso_graphs" value="1" class="form-check-input" id="accesGraphs" ' . $checked . '>';
        echo '<label class="form-check-label" for="accesGraphs">Acceso a Gráficas</label>';
        echo '</div>';
        echo '</div>';

        echo '<hr>';
        echo '<div class="alert alert-info">';
        echo '<strong>Gestión Proyectos</strong>';
        echo '<p>Permisos del inventario de proveedores/servicios y del inventario de dominios (no son excluyentes entre sí).</p>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && $user['gp_consulta'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="gp_consulta" value="1" class="form-check-input" id="gpConsulta" ' . $checked . '>';
        echo '<label class="form-check-label" for="gpConsulta">Consulta (ver el módulo)</label>';
        echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && $user['gp_modificacion'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="gp_modificacion" value="1" class="form-check-input" id="gpModificacion" ' . $checked . '>';
        echo '<label class="form-check-label" for="gpModificacion">Modificación (crear/editar registros)</label>';
        echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && $user['gp_ver_credenciales'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="gp_ver_credenciales" value="1" class="form-check-input" id="gpVerCredenciales" ' . $checked . '>';
        echo '<label class="form-check-label" for="gpVerCredenciales">Ver credenciales (puede reautenticarse para revelar contraseñas)</label>';
        echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && !empty($user['gp_eliminar_proveedores']) && $user['gp_eliminar_proveedores'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="gp_eliminar_proveedores" value="1" class="form-check-input" id="gpEliminarProveedores" ' . $checked . '>';
        echo '<label class="form-check-label" for="gpEliminarProveedores"><strong>Eliminar proveedores</strong> (permiso especial: puede eliminar un proveedor con todos sus servicios, proyectos, dominios y credenciales)</label>';
        echo '</div>';
        echo '</div>';

        echo '<hr>';
        echo '<div class="alert alert-info">';
        echo '<strong>Proyecto de Inversión</strong>';
        echo '<p>Permisos dedicados del módulo de capital/ingresos/egresos por cliente (independientes de los de Gestión Proyectos: tener acceso a Proveedores, Dominios o Tickets ya NO da acceso aquí).</p>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && $user['gp_inv_consulta'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="gp_inv_consulta" value="1" class="form-check-input" id="gpInvConsulta" ' . $checked . '>';
        echo '<label class="form-check-label" for="gpInvConsulta">Consulta (ver el módulo)</label>';
        echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && $user['gp_inv_modificacion'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="gp_inv_modificacion" value="1" class="form-check-input" id="gpInvModificacion" ' . $checked . '>';
        echo '<label class="form-check-label" for="gpInvModificacion">Modificación (crear/editar proyectos, cuentas, ingresos y egresos)</label>';
        echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && !empty($user['gp_inv_eliminar']) && $user['gp_inv_eliminar'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="gp_inv_eliminar" value="1" class="form-check-input" id="gpInvEliminar" ' . $checked . '>';
        echo '<label class="form-check-label" for="gpInvEliminar"><strong>Eliminar</strong> (permiso especial: eliminar proyectos, cuentas, ingresos y egresos de forma permanente)</label>';
        echo '</div>';
        echo '</div>';

        echo '<hr>';
        echo '<div class="alert alert-info">';
        echo '<strong>Tickets / Tareas</strong>';
        echo '<p>Permisos del módulo de tickets de seguimiento (no son excluyentes entre sí).</p>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && $user['gp_tickets_consulta'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="gp_tickets_consulta" value="1" class="form-check-input" id="gpTicketsConsulta" ' . $checked . '>';
        echo '<label class="form-check-label" for="gpTicketsConsulta">Consulta (ver el módulo)</label>';
        echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && $user['gp_tickets_modificacion'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="gp_tickets_modificacion" value="1" class="form-check-input" id="gpTicketsModificacion" ' . $checked . '>';
        echo '<label class="form-check-label" for="gpTicketsModificacion">Modificación (crear/editar tickets y escribir en el chat)</label>';
        echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<div class="form-check">';
        $checked = ($user && $user['gp_tickets_fechas'] == 1) ? 'checked' : '';
        echo '<input type="checkbox" name="gp_tickets_fechas" value="1" class="form-check-input" id="gpTicketsFechas" ' . $checked . '>';
        echo '<label class="form-check-label" for="gpTicketsFechas">Modificación de fechas (editar manualmente creación/inicio/fin)</label>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render Permissions Tab
     */
    private function renderPermissionsTab($user, $permissions, $menus) {
        echo '<div class="tab-pane fade" id="permissions">';
        // Marca que la grilla de menus viajo en el formulario: sin esto, desmarcar
        // TODOS los menus no envia ningun permisos[] y el controlador no puede
        // distinguirlo de "no se toco la pestaña" (y no revocaba nada).
        echo '<input type="hidden" name="permisos_presentes" value="1">';

        echo '<div class="alert alert-info">';
        echo '<strong>Permisos de Menú</strong>';
        echo '<p>Selecciona los menús a los que este usuario tiene acceso.</p>';
        echo '</div>';

        // Group menus byprincipale
        $groupedMenus = [];
        foreach ($menus as $menu) {
            $principal = $menu['principal'] ?? 'Otros';
            if (!isset($groupedMenus[$principal])) {
                $groupedMenus[$principal] = [];
            }
            $groupedMenus[$principal][] = $menu;
        }

        echo '<div class="permissions-grid">';
        foreach ($groupedMenus as $group => $items) {
            echo '<fieldset>';
            echo '<legend>' . htmlspecialchars($group) . '</legend>';
            foreach ($items as $menu) {
                // "Claves" es un permiso ESPECIAL: no es el checkbox normal de menu (permisos[]) sino
                // gp_claves, que ademas agrega/quita el menu (ver usuario_controller.php). Solo el
                // superadministrador principal lo puede cambiar; para el resto se muestra bloqueado.
                // "Dominios" sigue a los permisos de Inventario (Consulta/Modificacion, pestaña Acceso):
                // no se asigna desde aqui, por la misma razon.
                if (($menu['php'] ?? '') === 'gp_dom_proveedores') {
                    continue;
                }
                if (($menu['php'] ?? '') === 'gp_claves') {
                    $tieneClaves = ($user && !empty($user['gp_claves']) && $user['gp_claves'] == 1);
                    echo '<div class="form-check">';
                    if (usuario_puede_gestionar_permiso_claves()) {
                        echo '<input type="checkbox" name="gp_claves" value="1" class="form-check-input" id="gpClaves" ' . ($tieneClaves ? 'checked' : '') . '>';
                        echo '<label class="form-check-label" for="gpClaves">' . htmlspecialchars($menu['nombre']) . ' <small class="text-muted">(permiso especial)</small></label>';
                    } else {
                        echo '<input type="checkbox" class="form-check-input" id="gpClavesBloqueado" disabled ' . ($tieneClaves ? 'checked' : '') . '>';
                        echo '<label class="form-check-label" for="gpClavesBloqueado" style="opacity:.65">' . htmlspecialchars($menu['nombre']) . ' <small>(permiso especial: solo lo asigna el usuario principal 000001)</small></label>';
                    }
                    echo '</div>';
                    continue;
                }
                $checked = in_array($menu['id'], $permissions) ? 'checked' : '';
                echo '<div class="form-check">';
                echo '<input type="checkbox" name="permisos[]" value="' . (int)$menu['id'] . '" class="form-check-input" id="menu' . (int)$menu['id'] . '" ' . $checked . '>';
                echo '<label class="form-check-label" for="menu' . (int)$menu['id'] . '">' . htmlspecialchars($menu['nombre']) . '</label>';
                echo '</div>';
            }
            echo '</fieldset>';
        }
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render Graph Permissions Tab
     */
    private function renderGraphPermissionsTab($user, $permissions, $graphs) {
        echo '<div class="tab-pane fade" id="graphs">';

        echo '<div class="alert alert-info">';
        echo '<strong>Permisos de Gráficas</strong>';
        echo '<p>Selecciona las gráficas a las que este usuario tiene acceso.</p>';
        echo '</div>';

        echo '<div class="permissions-grid">';
        foreach ($graphs as $graph) {
            $checked = in_array($graph['id'], $permissions) ? 'checked' : '';
            echo '<div class="form-check">';
            echo '<input type="checkbox" name="permisos_graphs[]" value="' . (int)$graph['id'] . '" class="form-check-input" id="graph' . (int)$graph['id'] . '" ' . $checked . '>';
            echo '<label class="form-check-label" for="graph' . (int)$graph['id'] . '">' . htmlspecialchars($graph['nombre']) . '</label>';
            echo '</div>';
        }
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render Documents Tab
     */
    private function renderDocumentsTab($user, $documents) {
        echo '<div class="tab-pane fade" id="documents">';

        echo '<div class="form-group">';
        echo '<label class="form-label">Documento a Cargar</label>';
        echo '<input type="file" name="archivo" class="form-control" id="fileInput">';
        echo '<small class="form-text text-muted">Tipos permitidos: ' . implode(', ', ALLOWED_FILE_EXTENSIONS) . ' | Máx 5MB</small>';
        echo '</div>';

        if ($documents && !empty($documents)) {
            echo '<div class="documents-list">';
            echo '<h6>Documentos Cargados</h6>';
            echo '<table class="table table-sm table-hover">';
            echo '<thead>';
            echo '<tr><th>Nombre</th><th>Tipo</th><th>Fecha</th><th>Acción</th></tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach ($documents as $doc) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($doc['nombre']) . '</td>';
                echo '<td><span class="badge bg-secondary">' . htmlspecialchars($doc['tipo']) . '</span></td>';
                echo '<td>' . htmlspecialchars($doc['fecha']) . '</td>';
                echo '<td><a href="' . htmlspecialchars($doc['archivo']) . '" class="btn btn-sm btn-primary" download>Descargar</a></td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Render Observations Tab
     */
    private function renderObservationsTab($user) {
        echo '<div class="tab-pane fade" id="observaciones">';

        echo '<div class="form-group">';
        echo '<label class="form-label">Observaciones</label>';
        $obs = $user ? htmlspecialchars($user['observaciones']) : '';
        echo '<textarea name="observaciones" class="form-control" rows="6">' . $obs . '</textarea>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render form buttons
     */
    private function renderFormButtons($opc, $user) {
        echo '<div class="form-group text-center" style="margin-top: 30px;">';

        if ($opc == 1) {
                        echo '<button type="submit" name="funcion_btn" value="insertar" class="btn btn-success" onclick="document.getElementById(\'usuario_funcion\').value=\'insertar\'">
                    <i class="bi bi-check-circle"></i> Crear Usuario
                  </button>';
        } else {
                        echo '<button type="submit" name="funcion_btn" value="actualizar" class="btn btn-success" onclick="document.getElementById(\'usuario_funcion\').value=\'actualizar\'">
                    <i class="bi bi-check-circle"></i> Actualizar Usuario
                  </button>';
        }

        echo ' <a href="index.php?doc=usuario" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancelar
               </a>';

        echo '</div>';
    }

}

?>
