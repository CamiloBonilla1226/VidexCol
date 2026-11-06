<?php
/**
 * Contactos Config - Configuration & Constants
 * Centralized configuration for contactos module
 *
 * @package VIDEX
 * @version 1.0
 */

// Authorization
define('ALLOWED_PROFILES', [1, 2, 161, 162, 163]);
define('DENIED_PROFILES', [3, 4, 160]);

// Pagination
define('CONTACTS_PER_PAGE', 50);

// Category IDs
define('IDENTIFICATION_CATEGORY_ID', 2);

// Field validation rules
const FIELD_RULES = [
    'nombres' => [
        'required' => true,
        'min_length' => 3,
        'max_length' => 250
    ],
    'email' => [
        'required' => false,
        'max_length' => 250,
        'format' => 'email'
    ],
    'celular' => [
        'required' => false,
        'max_length' => 20,
        'format' => 'numeric'
    ],
    'celular2' => [
        'required' => false,
        'max_length' => 20,
        'format' => 'numeric'
    ],
    'telfijo' => [
        'required' => false,
        'max_length' => 20,
        'format' => 'numeric'
    ],
    'identificacion' => [
        'required' => false,
        'max_length' => 250
    ],
    'tipoIdentificacion' => [
        'required' => false,
        'format' => 'numeric'
    ],
    'direccion' => [
        'required' => false,
        'max_length' => 250
    ],
    'paginaWeb' => [
        'required' => false,
        'max_length' => 250
    ],
    'notas' => [
        'required' => false,
        'max_length' => 255
    ],
    'idCliente' => [
        'required' => false,
        'format' => 'numeric'
    ]
];

// Error messages
const ERROR_MESSAGES = [
    'nombres_required' => 'El nombre del contacto es requerido',
    'nombres_min_length' => 'El nombre debe tener al menos 3 caracteres',
    'nombres_max_length' => 'El nombre no puede exceder 250 caracteres',
    'email_format' => 'El email no es válido',
    'celular_format' => 'El celular debe contener solo dígitos',
    'phone_format' => 'El teléfono debe contener solo dígitos',
    'identificacion_max_length' => 'La identificación no puede exceder 250 caracteres',
    'direccion_max_length' => 'La dirección no puede exceder 250 caracteres',
    'duplicate_name' => 'Ya existe un contacto con este nombre',
    'contact_not_found' => 'El contacto no fue encontrado',
    'unauthorized' => 'No está autorizado para realizar esta acción',
    'database_error' => 'Error en la base de datos',
    'file_upload_error' => 'Error al cargar el archivo'
];

// Success messages
const SUCCESS_MESSAGES = [
    'contact_created' => 'Contacto creado exitosamente',
    'contact_updated' => 'Contacto actualizado exitosamente',
    'contact_deleted' => 'Contacto eliminado exitosamente'
];
?>
