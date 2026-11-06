<?php
/**
 * Cifrado reversible para credenciales del modulo Gestion Proyectos.
 * No existe ninguna utilidad de cifrado reversible en el resto del proyecto
 * (solo password_hash/md5 para contrasenas de usuario, que son de un solo
 * sentido), asi que esto es exclusivo de este modulo.
 *
 * Formato almacenado: base64(iv) . ':' . base64(ciphertext)
 * IV aleatorio por cada valor cifrado (nunca reutilizado).
 */

define('GP_ENCRYPTION_KEY', 'f3b8e6a1c9d2470eae5b6c8d1f2a3e4b7c9d0e1f2a3b4c5d6e7f8091a2b3c4d5');
define('GP_ENCRYPTION_METHOD', 'aes-256-cbc');

function gp_encrypt($texto_plano) {
    if ($texto_plano === null || $texto_plano === '') {
        return '';
    }
    $key = hex2bin(GP_ENCRYPTION_KEY);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(GP_ENCRYPTION_METHOD));
    $cifrado = openssl_encrypt($texto_plano, GP_ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv);
    if ($cifrado === false) {
        return '';
    }
    return base64_encode($iv) . ':' . base64_encode($cifrado);
}

function gp_decrypt($valor_cifrado) {
    if ($valor_cifrado === null || $valor_cifrado === '') {
        return '';
    }
    $partes = explode(':', $valor_cifrado, 2);
    if (count($partes) !== 2) {
        return '';
    }
    $iv = base64_decode($partes[0]);
    $cifrado = base64_decode($partes[1]);
    if ($iv === false || $cifrado === false) {
        return '';
    }
    $key = hex2bin(GP_ENCRYPTION_KEY);
    $resultado = openssl_decrypt($cifrado, GP_ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv);
    return ($resultado === false) ? '' : $resultado;
}

/**
 * Verifica la contrasena de acceso al sistema del usuario en sesion, para el
 * flujo de reautenticacion antes de revelar una credencial. Soporta tanto
 * bcrypt (usuarios nuevos, ver usuario_service.php) como el md5 legacy
 * (usuarios viejos, ver el login en index.php) para no depender de cual de
 * los dos formatos tenga el usuario actual.
 */
function gp_verificar_password_propia($idUsuario, $passwordIngresada) {
    if ($idUsuario == '' || $idUsuario == 0 || $passwordIngresada === '') {
        return false;
    }
    $conn = mysqli_connect('localhost', 'twsselvr_root', 'Videx@2024*.', 'twsselvr_videx_app');
    if (!$conn) {
        return false;
    }
    $idUsuario = (int) $idUsuario;
    $r = mysqli_query($conn, "SELECT password FROM usuario WHERE id = $idUsuario");
    $row = $r ? mysqli_fetch_assoc($r) : null;
    mysqli_close($conn);
    if (!$row) {
        return false;
    }
    $hashGuardado = $row['password'];
    if (strlen($hashGuardado) === 32) {
        // Formato legacy.
        return md5($passwordIngresada) === $hashGuardado;
    }
    return password_verify($passwordIngresada, $hashGuardado);
}
