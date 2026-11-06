<?php
/**
 * Cifrado del registro de claves (modulo "Claves" de Inventario).
 *
 * Diferencias con gp_crypto.php (credenciales de proveedores):
 *  - La llave maestra NO esta en el codigo: se lee de un archivo fuera de la
 *    carpeta web (por defecto <carpeta padre del sitio>/.videx_claves.key,
 *    64 caracteres hex = 32 bytes). Asi un volcado de la BD o una copia del
 *    codigo por si solos no permiten descifrar nada.
 *  - Es cifrado autenticado (AES-256-CBC + HMAC-SHA256, "encrypt-then-MAC"):
 *    si alguien altera el dato en la BD, o se copia el cifrado de una columna
 *    a otra, el descifrado falla en vez de devolver basura.
 *  - Cada valor lleva IV aleatorio propio y el HMAC incluye un "contexto"
 *    (usuario / clave / notas) que ata el cifrado a su columna.
 *
 * Formato almacenado: v1:base64(iv):base64(hmac):base64(cifrado)
 *
 * IMPORTANTE: si se pierde el archivo de llave, las claves guardadas NO se
 * pueden recuperar. Mantener una copia de respaldo fuera del servidor.
 */

class ClavesCryptoException extends Exception {}

function claves_ruta_llave() {
    if (defined('VIDEX_CLAVES_KEY_FILE')) {
        return VIDEX_CLAVES_KEY_FILE;
    }
    return dirname(__DIR__) . '/.videx_claves.key';
}

function claves_llave_maestra() {
    static $llave = null;
    if ($llave !== null) {
        return $llave;
    }
    $ruta = claves_ruta_llave();
    $hex = is_readable($ruta) ? trim((string) file_get_contents($ruta)) : '';
    if (strlen($hex) !== 64 || !ctype_xdigit($hex)) {
        throw new ClavesCryptoException('La llave de cifrado no está disponible o es inválida.');
    }
    $llave = hex2bin($hex);
    return $llave;
}

/** Deriva subllaves independientes (cifrado / autenticacion) de la llave maestra. */
function claves_subllave($proposito) {
    return hash_hmac('sha256', $proposito, claves_llave_maestra(), true);
}

function claves_cifrar($textoPlano, $contexto) {
    if ($textoPlano === null || $textoPlano === '') {
        return '';
    }
    $iv = openssl_random_pseudo_bytes(16, $fuerte);
    if ($iv === false || !$fuerte) {
        throw new ClavesCryptoException('No se pudo generar un IV seguro.');
    }
    $cifrado = openssl_encrypt((string) $textoPlano, 'aes-256-cbc', claves_subllave('enc'), OPENSSL_RAW_DATA, $iv);
    if ($cifrado === false) {
        throw new ClavesCryptoException('No se pudo cifrar el dato.');
    }
    $mac = hash_hmac('sha256', 'v1|' . $contexto . '|' . $iv . $cifrado, claves_subllave('mac'), true);
    return 'v1:' . base64_encode($iv) . ':' . base64_encode($mac) . ':' . base64_encode($cifrado);
}

/**
 * Devuelve el texto plano, '' si el valor almacenado esta vacio, o false si el
 * dato esta corrupto/alterado o fue cifrado con otra llave.
 */
function claves_descifrar($valor, $contexto) {
    if ($valor === null || $valor === '') {
        return '';
    }
    $partes = explode(':', $valor);
    if (count($partes) !== 4 || $partes[0] !== 'v1') {
        return false;
    }
    $iv = base64_decode($partes[1], true);
    $mac = base64_decode($partes[2], true);
    $cifrado = base64_decode($partes[3], true);
    if ($iv === false || $mac === false || $cifrado === false || strlen($iv) !== 16) {
        return false;
    }
    $esperado = hash_hmac('sha256', 'v1|' . $contexto . '|' . $iv . $cifrado, claves_subllave('mac'), true);
    if (!hash_equals($esperado, $mac)) {
        return false;
    }
    $plano = openssl_decrypt($cifrado, 'aes-256-cbc', claves_subllave('enc'), OPENSSL_RAW_DATA, $iv);
    return $plano === false ? false : $plano;
}
