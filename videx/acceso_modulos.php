<?php
/**
 * Control de acceso por modulo (compuerta central de index.php).
 *
 * Antes, index.php hacia include_once($_GET["doc"] . ".php") sin verificar nada:
 * el menu lateral solo ocultaba los enlaces, pero cualquiera con sesion podia
 * abrir cualquier modulo escribiendo la URL o usando los enlaces del dashboard.
 *
 * Regla: un usuario puede abrir un "doc" si:
 *   - es un doc libre (main, category), o
 *   - es superusuario, o
 *   - el doc (o una pagina de su mismo grupo) esta en sus permisos de menu
 *     (usuarios_menu / usuarios_menu_graphs), o
 *   - es perfil 4 (mantenimiento externo) y el doc es mant_*.
 *
 * Los permisos se leen de la BD en cada request (no se cachean en sesion), asi
 * que quitar un permiso en Usuario > Acceso surte efecto de inmediato.
 *
 * El control es a nivel de modulo (doc), no de opc: cada modulo sigue
 * decidiendo internamente que puede hacer (ej. gp_requerir_modificacion()).
 */

const VIDEX_DOCS_LIBRES = ['main', 'category'];

/*
 * Docs que solo puede abrir un superusuario aunque no esten en la tabla menu
 * (son las entradas de la categoria ADMIN que index.php agrega a mano).
 */
const VIDEX_DOCS_SOLO_SUPERUSUARIO = ['Personal', 'sms_saldo'];

/**
 * Paginas que forman parte de un mismo modulo: formularios de edicion,
 * popups y vistas hijas que NO tienen fila propia en la tabla menu.
 * Si el usuario tiene permiso sobre cualquiera del grupo, accede a todo el grupo.
 * Si se crea una pagina hija nueva enlazada con index.php?doc=..., agregarla aqui.
 */
function videx_grupos_modulos() {
    return array(
        // Administracion de usuarios
        array('usuario', 'usuario_buscar', 'usuario_buscar_excel', 'usuario_int_obs'),
        array('admin_usu3', 'admin_usu4'),
        array('admin_buscarcat', 'admin_buscarcatm'),
        // Gastos
        array('gastos', 'gasto_crear', 'gasto_editar'),
        // Contactos / SMS / correo
        array('sms_envio', 'sms_usuarios'),
        array('sms_grupos'),
        // Proyectos (cotizacion) y sus popups
        array(
            'cotizacion', 'cotizacion_imp',
            'int', 'int_new', 'int_seguimientos', 'int_gastos', 'int_facturacion',
            'pop_seguimiento', 'pop_seguimiento_edit', 'pop_seguimento',
            'pop_gasto', 'pop_gasto_edit', 'pop_abono', 'pop_abono_edit', 'pop_up',
        ),
        // Auditoria de proyectos y sus popups
        array(
            'seguimiento_auditoria', 'int_gestioncambios', 'int_facturacionauditoria',
            'int_gastosauditoria', 'int_seguimientosauditoria',
            'pop_seguimientocambios', 'pop_seguimientocambios_edit',
            'pop_seguimientoauditoria', 'pop_seguimientoauditoria_edit',
            'pop_gastoauditoria', 'pop_gastoauditoria_edit',
            'pop_abonoauditoria', 'pop_abonoauditoria_edit',
        ),
        // Gestion Proyectos (cat=7)
        array('gp_proveedores', 'gp_proveedor', 'gp_servicio', 'gp_proyecto', 'gp_alertas_email'),
        // Inventario de dominios (proveedores de dominios y sus dominios)
        array('gp_dom_proveedores', 'gp_dom_proveedor', 'gp_alertas_email'),
        array('gp_tickets', 'gp_ticket', 'gp_ticket_informe_diario'),
        // Proyecto de Inversion (capital, ingresos/egresos por cliente)
        array('gp_inversion', 'gp_inversion_proyecto'),
    );
}

/**
 * Devuelve el nombre de doc limpio, o '' si no es un nombre de archivo valido.
 * Ademas de normalizar, evita path traversal (doc=../algo) en el include.
 */
function videx_doc_limpio($doc) {
    $doc = trim((string) $doc);
    return preg_match('/^[A-Za-z0-9_-]+$/', $doc) ? $doc : '';
}

/**
 * Lista de docs que el usuario logueado tiene asignados en el menu (sin grupos).
 */
function videx_docs_menu_usuario() {
    static $docs = null;
    if ($docs !== null) {
        return $docs;
    }

    $docs = array();
    $idUsuario = (int) ($_SESSION['id'] ?? 0);
    if ($idUsuario <= 0) {
        return $docs;
    }

    $db = new DBbase_Sql;
    $db->query("SELECT DISTINCT menu.php FROM menu, usuarios_menu
                WHERE menu.id = usuarios_menu.idMenu AND usuarios_menu.idUsuario = " . $idUsuario);
    while ($db->next_record()) {
        $docs[] = (string) $db->f('php');
    }

    if ((int) ($_SESSION['menu_graphs'] ?? 0) === 1) {
        $db->query("SELECT DISTINCT menu_graphs.php FROM menu_graphs, usuarios_menu_graphs
                    WHERE menu_graphs.id = usuarios_menu_graphs.idMenu AND usuarios_menu_graphs.idUsuario = " . $idUsuario);
        while ($db->next_record()) {
            $docs[] = (string) $db->f('php');
        }
    }

    return $docs;
}

/**
 * Decide si el usuario logueado puede abrir index.php?doc=$doc.
 */
function videx_doc_permitido($doc) {
    $doc = videx_doc_limpio($doc);
    if ($doc === '') {
        return false;
    }

    if (in_array($doc, VIDEX_DOCS_LIBRES, true)) {
        return true;
    }

    if ((int) ($_SESSION['superusuario'] ?? 0) === 1) {
        return true;
    }

    if (in_array($doc, VIDEX_DOCS_SOLO_SUPERUSUARIO, true)) {
        return false;
    }

    // Perfil 4 (mantenimiento externo): index.php le pinta un menu fijo mant_*
    if ((int) ($_SESSION['perfil'] ?? 0) === 4) {
        return strpos($doc, 'mant_') === 0;
    }

    // Inversionista externo (usuario.gp_inv_cliente_id): sin menu lateral, solo
    // puede abrir su propio proyecto de inversion (gp_inversion resuelve y
    // redirige; gp_inversion_proyecto verifica que el proyecto sea el suyo).
    if (in_array($doc, ['gp_inversion', 'gp_inversion_proyecto'], true)) {
        require_once(__DIR__ . '/gp_inversion_config.php');
        if (gp_inv_cliente_restriccion() !== null) {
            return true;
        }
    }

    $permitidos = videx_docs_menu_usuario();
    if (in_array($doc, $permitidos, true)) {
        return true;
    }

    foreach (videx_grupos_modulos() as $grupo) {
        if (in_array($doc, $grupo, true) && count(array_intersect($grupo, $permitidos)) > 0) {
            return true;
        }
    }

    return false;
}

/**
 * Decide si el usuario puede ver la pagina de una categoria (doc=category&cat=X).
 * Sin esto cualquiera veia la categoria (y cosas extra que category.php agrega
 * por fuera del menu) aunque no tuviera ningun permiso dentro de ella.
 */
function videx_categoria_permitida($cat) {
    $cat = (string) $cat;
    if ($cat === '') {
        return false;
    }

    if ((int) ($_SESSION['superusuario'] ?? 0) === 1) {
        return true;
    }

    if ($cat === 'admin') {
        return false;
    }

    $idUsuario = (int) ($_SESSION['id'] ?? 0);
    $db = new DBbase_Sql;

    if ($cat === 'graphs') {
        if ((int) ($_SESSION['menu_graphs'] ?? 0) !== 1) {
            return false;
        }
        $db->query("SELECT 1 FROM usuarios_menu_graphs WHERE idUsuario = " . $idUsuario . " LIMIT 1");
        return $db->num_rows() > 0;
    }

    if (!ctype_digit($cat)) {
        return false;
    }

    $db->query("SELECT 1 FROM menu, usuarios_menu
                WHERE menu.id = usuarios_menu.idMenu
                AND usuarios_menu.idUsuario = " . $idUsuario . "
                AND menu.principal = " . (int) $cat . " LIMIT 1");
    return $db->num_rows() > 0;
}

/**
 * Compuerta completa para index.php: valida doc (+ cat si es una categoria).
 */
function videx_acceso_permitido($doc, $cat = null) {
    if (!videx_doc_permitido($doc)) {
        return false;
    }
    if (videx_doc_limpio($doc) === 'category') {
        return videx_categoria_permitida($cat);
    }
    return true;
}
