<?php
/**
 * Claves (Inventario) - Configuracion y permiso especial.
 *
 * Registro de claves personales (tarjetas debito, correos, aplicaciones...)
 * NO asociadas a proveedores/proyectos. Es un modulo aparte dentro de
 * Inventario (categoria 7) y solo lo usa quien tenga el permiso especial
 * "gp_claves" (Usuario > Acceso). Aplica tambien a superusuarios.
 */

const GP_CLAVES_MAX_INTENTOS = 5;          // reautenticaciones fallidas permitidas...
const GP_CLAVES_VENTANA_MINUTOS = 10;      // ...dentro de esta ventana
const GP_CLAVES_REAUTH_SEGUNDOS = 600;     // tras confirmar la contraseña se puede editar/eliminar esa clave durante 10 min

const GP_CLAVES_CATEGORIAS_SUGERIDAS = [
    'Tarjeta débito',
    'Tarjeta crédito',
    'Banco / cuenta',
    'Correo electrónico',
    'Aplicación',
    'Red social',
    'Otro',
];

/*
 * El permiso se consulta EN VIVO en la BD (no en la sesion): si se le quita el
 * permiso a un usuario, pierde el acceso de inmediato aunque tenga la sesion
 * abierta. Si la columna todavia no existe, se niega el acceso.
 */
function gp_claves_puede_acceder() {
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
    $r = mysqli_query($db->Link_ID, "SELECT gp_claves FROM usuario WHERE id = $idUsuario AND acceso = 1");
    if ($r) {
        $fila = mysqli_fetch_assoc($r);
        $resultado = $fila && (int) $fila['gp_claves'] === 1;
    }
    return $resultado;
}

function gp_claves_requerir_acceso() {
    if (!gp_claves_puede_acceder()) {
        die('<div style="padding:40px;text-align:center;color:#64748b;font-family:Inter,sans-serif;">
            <h3>No tiene permiso para acceder a este módulo.</h3>
            <p>Solicite el permiso especial de Claves a un administrador.</p>
        </div>');
    }
}

function gp_claves_requerir_acceso_json() {
    if (!gp_claves_puede_acceder()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'No tiene permiso para acceder a Claves.']);
        exit;
    }
}
