<?php
/**
 * Proyecto de Inversion - Configuracion y permisos.
 *
 * Modulo independiente dentro de Gestion Proyectos (cat=7), asociado a la
 * tabla `cliente` (CRM de cotizaciones/facturacion) existente, NO al sistema
 * legado usuario.tipo=3/160 + usuario_relacion + $_SESSION['micliente']
 * (ese es de otro modulo -flota/vehiculo- y usa usuario.id, no cliente.id).
 *
 * Roles (pedido por el cliente en el requerimiento):
 *  - Super Administrador / Administrador: acceso total. Mapea a superusuario=1
 *    o al permiso especial gp_inv_eliminar para poder eliminar registros.
 *  - Operador / Contador: permisos DEDICADOS del modulo (gp_inv_consulta /
 *    gp_inv_modificacion), independientes del generico gp_consulta/
 *    gp_modificacion de Gestion Proyectos -antes bastaba tener acceso a
 *    Proveedores, Dominios o Tickets para entrar tambien aqui, aunque nadie
 *    lo hubiera autorizado explicitamente-, con edicion restringida a una
 *    ventana de gracia tras crear el movimiento.
 *  - Cliente / Inversionista (solo lectura): un usuario cualquiera con la
 *    columna usuario.gp_inv_cliente_id apuntando a un cliente.id. No necesita
 *    ningun permiso ni aparece en el menu lateral: se le entrega el enlace
 *    directo a su proyecto y el sistema lo resuelve solo.
 */
require_once(__DIR__ . '/gp_config.php');

const GP_INV_HORAS_GRACIA_EDICION = 48;

const GP_INV_TIPOS_INGRESO = [
    'aporte_capital'    => 'Aporte / Inyección de Capital Inicial',
    'rendimiento'       => 'Rendimientos / Utilidades Generadas',
    'retorno_inversion' => 'Retorno de Inversión (Recuperación de Capital)',
];

const GP_INV_CLASIFICACION_EGRESO = [
    'gasto_operativo' => 'Gasto Operativo / Administrativo',
    'inversion'        => 'Inversión / Capital de Trabajo',
];

const GP_INV_ANEXO_MIME_PERMITIDOS = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'application/zip' => 'zip',
    'application/x-zip-compressed' => 'zip',
    'application/vnd.rar' => 'rar',
    'application/x-rar-compressed' => 'rar',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'text/csv' => 'csv',
    'application/csv' => 'csv',
];

const GP_INV_ANEXO_TAMANO_MAXIMO = 10485760; // 10 MB

/**
 * Directorio de almacenamiento de anexos, FUERA del docroot publico
 * (hermano de la carpeta videx.sms-app.co/ en la raiz de la cuenta), tal
 * como exige el requerimiento. Se crea solo la primera vez.
 */
function gp_inv_dir_anexos() {
    $dir = dirname(__DIR__) . '/gp_inversion_anexos';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    return $dir;
}

/**
 * idCliente al que esta restringido un usuario externo (Inversionista), o
 * null si el usuario es staff interno (con acceso normal a Gestion
 * Proyectos) o no tiene ninguna restriccion configurada. Se consulta EN VIVO
 * en la BD (no se cachea en sesion) para que quitar el acceso surta efecto
 * de inmediato, igual que gp_puede_eliminar_proveedores().
 */
function gp_inv_cliente_restriccion() {
    static $calculado = false;
    static $valor = null;
    if ($calculado) {
        return $valor;
    }
    $calculado = true;

    // Staff con acceso al modulo de Inversion: sin restriccion, ve todos los proyectos.
    if (gp_inv_puede_consultar()) {
        return $valor; // null
    }

    $idUsuario = (int) ($_SESSION['id'] ?? 0);
    if ($idUsuario <= 0) {
        return $valor;
    }
    $db = new DBbase_Sql();
    $db->connect();
    if (!$db->Link_ID) {
        return $valor;
    }
    $r = mysqli_query($db->Link_ID, "SELECT gp_inv_cliente_id FROM usuario WHERE id = $idUsuario AND acceso = 1");
    if ($r && ($f = mysqli_fetch_assoc($r)) && !empty($f['gp_inv_cliente_id'])) {
        $valor = (int) $f['gp_inv_cliente_id'];
    }
    return $valor;
}

/** true si el usuario logueado es un inversionista externo (sin acceso de staff). */
function gp_inv_es_externo() {
    return gp_inv_cliente_restriccion() !== null;
}

/**
 * Puede ver el modulo de Inversion? Permiso DEDICADO (gp_inv_consulta /
 * gp_inv_modificacion), distinto del generico gp_consulta/gp_modificacion de
 * Gestion Proyectos: antes cualquier usuario con acceso a Proveedores,
 * Dominios o Tickets entraba tambien a Inversion (datos financieros
 * sensibles) sin que nadie se lo hubiera otorgado explicitamente.
 * Se cachea en sesion al login, igual que gp_consulta/gp_modificacion.
 */
function gp_inv_puede_consultar() {
    if ((int) ($_SESSION['superusuario'] ?? 0) === 1) {
        return true;
    }
    return (int) ($_SESSION['gp_inv_consulta'] ?? 0) === 1 || (int) ($_SESSION['gp_inv_modificacion'] ?? 0) === 1;
}

/** Puede crear/editar movimientos (ingresos/egresos) en general? */
function gp_inv_puede_escribir() {
    if (gp_inv_es_externo()) {
        return false;
    }
    return (int) ($_SESSION['superusuario'] ?? 0) === 1 || (int) ($_SESSION['gp_inv_modificacion'] ?? 0) === 1;
}

/**
 * Permiso especial gp_inv_eliminar (Usuario > Acceso): eliminar proyectos,
 * movimientos y cuentas. Accion destructiva -> se consulta en vivo en la BD.
 */
function gp_inv_puede_eliminar() {
    static $resultado = null;
    if ($resultado !== null) {
        return $resultado;
    }
    $resultado = false;
    if (gp_inv_es_externo()) {
        return $resultado;
    }
    if ((int) ($_SESSION['superusuario'] ?? 0) === 1) {
        $resultado = true;
        return $resultado;
    }
    $idUsuario = (int) ($_SESSION['id'] ?? 0);
    if ($idUsuario <= 0) {
        return $resultado;
    }
    $db = new DBbase_Sql();
    $db->connect();
    if (!$db->Link_ID) {
        return $resultado;
    }
    $r = mysqli_query($db->Link_ID, "SELECT gp_inv_eliminar FROM usuario WHERE id = $idUsuario AND acceso = 1");
    if ($r && ($f = mysqli_fetch_assoc($r))) {
        $resultado = (int) $f['gp_inv_eliminar'] === 1;
    }
    return $resultado;
}

/**
 * Puede editar/eliminar ESTE movimiento en particular ahora mismo?
 * Admin (superusuario o gp_inv_eliminar): siempre. Operador/Contador: solo
 * dentro de la ventana de gracia desde su creacion.
 */
function gp_inv_puede_editar_movimiento($fechaCreacion) {
    if (gp_inv_es_externo()) {
        return false;
    }
    if ((int) ($_SESSION['superusuario'] ?? 0) === 1 || gp_inv_puede_eliminar()) {
        return true;
    }
    if (!gp_inv_puede_escribir() || !$fechaCreacion) {
        return false;
    }
    $creado = strtotime($fechaCreacion);
    if ($creado === false) {
        return false;
    }
    return (time() - $creado) <= GP_INV_HORAS_GRACIA_EDICION * 3600;
}

/**
 * Cualquier pagina del modulo debe llamar esto al inicio. Ademas de validar
 * el acceso, garantiza que exista el token CSRF en sesion: las paginas del
 * modulo solo lo IMPRIMIAN (nunca lo generaban), asi que si el usuario no
 * habia pasado antes por otra pantalla que si lo generara (gp_proyecto.php,
 * gp_servicio.php, etc.) el token viajaba vacio al formulario y el AJAX
 * rechazaba cualquier guardado con "Sesion expirada".
 */
function gp_inv_requerir_acceso() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    if (gp_inv_es_externo()) {
        return; // lectura permitida, acotada a su cliente
    }
    if (!gp_inv_puede_consultar()) {
        die('<div style="padding:40px;text-align:center;color:#64748b;font-family:Inter,sans-serif;">
            <h3>No tiene permiso para acceder a este módulo.</h3>
            <p>Solicite acceso a Proyecto de Inversión a un administrador.</p>
        </div>');
    }
}

/** Toda escritura (AJAX) debe llamar esto antes de tocar la base de datos. */
function gp_inv_requerir_escritura() {
    if (!gp_inv_puede_escribir()) {
        http_response_code(403);
        die(json_encode(['error' => 'No tiene permiso de modificación en Proyecto de Inversión.']));
    }
}

function gp_inv_formato_moneda($valor, $moneda = 'COP') {
    return '$' . number_format((float) $valor, 0, ',', '.') . ' ' . htmlspecialchars((string) $moneda, ENT_QUOTES);
}
