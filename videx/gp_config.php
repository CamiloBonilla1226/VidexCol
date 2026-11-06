<?php
/**
 * Gestion Proyectos - Configuracion
 * Constantes y helpers de permiso. Sigue el mismo patron que usuario_config.php.
 */

const GP_TIPO_HOSTING = 'HOSTING';
const GP_TIPO_VPS = 'VPS';

const GP_TIPOS_SERVICIO = [
    GP_TIPO_HOSTING => 'Hosting',
    GP_TIPO_VPS => 'VPS',
];

const GP_TIPOS_CREDENCIAL = [
    'FTP' => 'FTP',
    'SSH' => 'SSH',
    'CPANEL' => 'cPanel',
    'BASE_DATOS' => 'Base de datos',
    'API' => 'API Key',
    'OTRO' => 'Otro',
];

/*
 * Permisos del modulo (parametrizables por usuario desde Usuario > Acceso):
 * - gp_consulta: puede ver el modulo (sin esto, ni siquiera se le muestra el menu)
 * - gp_modificacion: puede crear/editar registros
 * - gp_ver_credenciales: puede reautenticarse para revelar contrasenas
 * No son excluyentes entre si.
 */
function gp_puede_consultar() {
    return isset($_SESSION['gp_consulta']) && $_SESSION['gp_consulta'] == 1;
}

function gp_puede_modificar() {
    return isset($_SESSION['gp_modificacion']) && $_SESSION['gp_modificacion'] == 1;
}

function gp_puede_ver_credenciales() {
    return isset($_SESSION['gp_ver_credenciales']) && $_SESSION['gp_ver_credenciales'] == 1;
}

/*
 * Permiso ESPECIAL gp_eliminar_proveedores (Usuario > Acceso): eliminar
 * proveedores (y, en cascada, sus servicios/proyectos/dominios/credenciales).
 * Es una accion destructiva, asi que se consulta EN VIVO en la BD (no en la
 * sesion): quitar el permiso surte efecto de inmediato. Si la columna aun no
 * existe, se niega.
 */
function gp_puede_eliminar_proveedores() {
    static $resultado = null;
    if ($resultado !== null) {
        return $resultado;
    }
    $resultado = false;
    $idUsuario = (int) ($_SESSION['id'] ?? 0);
    if ($idUsuario <= 0) {
        return $resultado;
    }
    $db = new DBbase_Sql();
    $db->connect();
    if (!$db->Link_ID) {
        return $resultado;
    }
    $r = mysqli_query($db->Link_ID, "SELECT gp_eliminar_proveedores FROM usuario WHERE id = $idUsuario AND acceso = 1");
    if ($r) {
        $fila = mysqli_fetch_assoc($r);
        $resultado = $fila && (int) $fila['gp_eliminar_proveedores'] === 1;
    }
    return $resultado;
}

/*
 * Cualquier pagina del modulo debe llamar esto al inicio. Corta la ejecucion
 * si el usuario no tiene ni consulta ni modificacion (nunca confiar en que
 * el menu simplemente no se le muestre: hay que bloquear tambien la URL directa).
 */
function gp_requerir_acceso() {
    if (!gp_puede_consultar() && !gp_puede_modificar()) {
        die('<div style="padding:40px;text-align:center;color:#64748b;font-family:Inter,sans-serif;">
            <h3>No tiene permiso para acceder a este modulo.</h3>
            <p>Solicite acceso a Gestion Proyectos a un administrador.</p>
        </div>');
    }
}

/*
 * Toda accion de escritura (crear/editar/eliminar/agregar via AJAX) debe
 * llamar esto ANTES de tocar la base de datos. Es la verificacion real; el
 * frontend solo oculta los controles por comodidad, nunca es la barrera.
 */
function gp_requerir_modificacion() {
    if (!gp_puede_modificar()) {
        http_response_code(403);
        die(json_encode(['error' => 'No tiene permiso de modificacion en Gestion Proyectos.']));
    }
}

/*
 * Vencimientos (dominios, SSL, servicios): helpers compartidos para que
 * cualquier submodulo de Gestion Proyectos pinte el mismo badge. Antes
 * vivian solo dentro de GpDomService; se movieron aqui para reutilizarlos
 * tambien en Servicios (gp_servicio.php) sin duplicar la logica. Las clases
 * CSS (.gpd-badge-venc-*) estan en gp_dominios.css.
 */

/** Dias hasta una fecha (negativo = ya vencido), o null si no hay fecha. */
function gp_dias_para_vencer($fecha) {
    if (!$fecha || $fecha === '0000-00-00') {
        return null;
    }
    $hoy = new DateTime('today');
    $venc = DateTime::createFromFormat('Y-m-d', substr($fecha, 0, 10));
    if (!$venc) {
        return null;
    }
    $venc->setTime(0, 0, 0);
    return (int) $hoy->diff($venc)->format('%r%a');
}

/** HTML del estado de vencimiento (vigente / vence pronto / vencido / sin fecha). */
function gp_badge_vencimiento($fecha) {
    $dias = gp_dias_para_vencer($fecha);
    if ($dias === null) {
        return '<span class="gpd-badge-venc gpd-venc-sin">Sin fecha</span>';
    }
    $f = date('d M Y', strtotime($fecha));
    if ($dias < 0) {
        return '<span class="gpd-badge-venc gpd-venc-vencido" title="' . $f . '">Vencido hace ' . abs($dias) . ' día' . (abs($dias) == 1 ? '' : 's') . '</span>';
    }
    if ($dias <= 30) {
        return '<span class="gpd-badge-venc gpd-venc-pronto" title="' . $f . '">' . ($dias === 0 ? 'Vence hoy' : 'Vence en ' . $dias . ' día' . ($dias == 1 ? '' : 's')) . '</span>';
    }
    return '<span class="gpd-badge-venc gpd-venc-ok" title="' . $f . '">' . $f . '</span>';
}
