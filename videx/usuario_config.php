<?php
/**
 * Usuario Module Configuration
 * Constants, enums, and configuration for user management
 */

// ============================================================
// USER TYPES (Tipos de Usuario)
// ============================================================
const USER_TYPE_ADMIN = 1;         // Administrador (staff con rol administrativo)
const USER_TYPE_OPERATOR = 2;      // Operario (staff con rol operativo)
const USER_TYPE_PROVIDER = 3;      // Proveedor (supplier)
const USER_TYPE_AUTHORIZED = 160;  // Usuario Cliente / Authorized Personnel

const USER_TYPES = [
    USER_TYPE_ADMIN => 'Administrador',
    USER_TYPE_OPERATOR => 'Operario',
    USER_TYPE_PROVIDER => 'Proveedor',
    USER_TYPE_AUTHORIZED => 'Usuario Cliente'
];

// ============================================================
// CLIENT USER TYPES (Tipos de Usuario Cliente)
// ============================================================
const CLIENT_USER_TYPE_REGULAR = 1;
const CLIENT_USER_TYPE_CONTACT = 2;
const CLIENT_USER_TYPE_ADMIN = 3;

const CLIENT_USER_TYPES = [
    CLIENT_USER_TYPE_REGULAR => 'Usuario Regular',
    CLIENT_USER_TYPE_CONTACT => 'Contacto',
    CLIENT_USER_TYPE_ADMIN => 'Administrador'
];

// ============================================================
// IDENTIFICATION TYPES (Tipos de Identificación)
// ============================================================
const ID_TYPE_CC = 'CC';           // Cédula de Ciudadanía
const ID_TYPE_CE = 'CE';           // Cédula de Extranjería
const ID_TYPE_NIT = 'NIT';         // NIT
const ID_TYPE_PASSPORT = 'Pasaporte';
const ID_TYPE_UNKNOWN = '6';       // Unknown/Legacy type

const ID_TYPES = [
    ID_TYPE_CC => 'Cédula de Ciudadanía',
    ID_TYPE_CE => 'Cédula de Extranjería',
    ID_TYPE_NIT => 'NIT',
    ID_TYPE_PASSPORT => 'Pasaporte',
    ID_TYPE_UNKNOWN => 'Otro',
    '6' => 'Otro'
];

// ============================================================
// ACCESS LEVELS
// ============================================================
const ACCESS_ENABLED = 1;
const ACCESS_DISABLED = 0;

// ============================================================
// ERROR CODES
// ============================================================
const ERROR_REQUIRED_FIELDS_BLANK = 1;
const ERROR_PASSWORD_MISMATCH = 2;
const ERROR_IDENTIFICATION_EXISTS = 3;
const ERROR_INVALID_EMAIL = 4;
const ERROR_INVALID_PHONE = 5;
const ERROR_FILE_UPLOAD_FAILED = 6;
const ERROR_DATABASE_ERROR = 7;
const ERROR_PERMISSION_DENIED = 8;

// ============================================================
// FILE UPLOAD CONFIGURATION
// ============================================================
const MAX_FILE_SIZE = 5242880;  // 5MB in bytes
const ALLOWED_FILE_TYPES = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
];
const ALLOWED_FILE_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx'];

// ============================================================
// PASSWORD REQUIREMENTS
// ============================================================
const PASSWORD_MIN_LENGTH = 8;
const PASSWORD_REQUIRE_UPPERCASE = true;
const PASSWORD_REQUIRE_NUMBERS = true;
const PASSWORD_REQUIRE_SPECIAL = true;

// ============================================================
// PAGINATION & LIMITS
// ============================================================
const USERS_PER_PAGE = 20;
const MAX_DOCUMENTS_PER_USER = 10;

// ============================================================
// MENU PERMISSIONS (Permisos de Menú)
// ============================================================
const MENU_PERMISSIONS = [
    'admin' => 'Administração',
    'sms' => 'SMS',
    'email' => 'Emailing',
    'contacts' => 'Contactos',
    'fleet' => 'Flota',
    'maintenance' => 'Mantenimiento',
    'reports' => 'Reportes',
    'graphs' => 'Gráficas'
];

// ============================================================
// DOCUMENT TYPES FOR USERS
// ============================================================
const DOCUMENT_TYPES = [
    'id' => 'Documento de Identidad',
    'rut' => 'RUT',
    'camera' => 'Cámara',
    'license' => 'Licencia',
    'insurance' => 'Póliza',
    'contract' => 'Contrato',
    'other' => 'Otro'
];

// ============================================================
// HELPER FUNCTIONS FOR CONFIG
// ============================================================

/**
 * Get user type label
 */
function getUserTypeLabel($type) {
    return USER_TYPES[$type] ?? 'Desconocido';
}

/**
 * Get identification type label
 */
function getIdTypeLabel($type) {
    return ID_TYPES[$type] ?? $type;
}

/**
 * Check if file type is allowed
 */
function isAllowedFileType($mimeType) {
    return isset(ALLOWED_FILE_TYPES[$mimeType]);
}

/**
 * Get file extension from mime type
 */
function getFileExtension($mimeType) {
    return ALLOWED_FILE_TYPES[$mimeType] ?? null;
}

/**
 * Get allowed mime types as string
 */
function getAllowedMimeTypes() {
    return implode(',', array_keys(ALLOWED_FILE_TYPES));
}

/**
 * Get error message for error code
 */
function getErrorMessage($code) {
    $messages = [
        ERROR_REQUIRED_FIELDS_BLANK => 'Los campos requeridos no pueden estar vacíos',
        ERROR_PASSWORD_MISMATCH => 'Las contraseñas no coinciden',
        ERROR_IDENTIFICATION_EXISTS => 'Esta identificación ya existe en el sistema',
        ERROR_INVALID_EMAIL => 'El formato del correo electrónico no es válido',
        ERROR_INVALID_PHONE => 'El número de teléfono no es válido',
        ERROR_FILE_UPLOAD_FAILED => 'Error al cargar el archivo',
        ERROR_DATABASE_ERROR => 'Error en la base de datos',
        ERROR_PERMISSION_DENIED => 'No tienes permiso para realizar esta acción'
    ];
    return $messages[$code] ?? 'Error desconocido';
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password complexity
 */
function isValidPassword($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }
    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        return false;
    }
    if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
        return false;
    }
    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        return false;
    }
    return true;
}

/**
 * Validate phone number (basic)
 */
function isValidPhone($phone) {
    // Remove non-digits
    $phone = preg_replace('/\D/', '', $phone);
    // Check if length is between 7-15 (international standard)
    return strlen($phone) >= 7 && strlen($phone) <= 15;
}

/**
 * Permiso especial "Claves" (registro cifrado de claves): SOLO el superadministrador
 * principal (usuario id 1, "000001") puede concederlo o quitarlo. Los demas
 * superusuarios NO, aunque tengan superusuario = 1. Se usa tanto para mostrar el
 * checkbox (usuario_form.php) como para aceptar el valor enviado (usuario_controller.php),
 * de modo que no se pueda saltar desde el frontend.
 */
const USUARIO_ID_ADMIN_CLAVES = 1;

function usuario_puede_gestionar_permiso_claves() {
    return (int) ($_SESSION['id'] ?? 0) === USUARIO_ID_ADMIN_CLAVES
        && (int) ($_SESSION['superusuario'] ?? 0) === 1;
}

?>
