<?php
/**
 * Cliente Module Configuration
 * Centralized constants, validation rules, and configuration
 *
 * @package VIDEX
 * @version 1.0
 */

// Database settings
define('CLIENT_TABLE', 'cliente');
define('CLIENT_TYPE_CATEGORY_ID', 15);  // categorias WHERE idSec = 15

// Authorization
define('ALLOWED_PROFILES', [1, 2, 161, 162, 163]);
define('DENIED_PROFILES', [3, 4, 160]);

// Pagination
define('CLIENTS_PER_PAGE', 50);

// Client status
define('CLIENT_ACTIVE', 1);
define('CLIENT_INACTIVE', 0);

// Client types
define('CLIENT_TYPE_EXTERNAL', 0);   // Cliente externo
define('CLIENT_TYPE_INTERNAL', 1);   // Cliente propio

// Validation rules
$FIELD_RULES = [
    'nombre' => [
        'required' => true,
        'min_length' => 2,
        'max_length' => 250,
        'message' => 'El nombre es requerido (2-250 caracteres)'
    ],
    'nit' => [
        'required' => false,
        'max_length' => 250,
        'message' => 'NIT inválido'
    ],
    'representante_legal' => [
        'required' => false,
        'max_length' => 250,
        'message' => 'Representante legal inválido'
    ],
    'direccion' => [
        'required' => false,
        'max_length' => 250,
        'message' => 'Dirección inválida'
    ],
    'telefono' => [
        'required' => false,
        'numeric' => true,
        'max_length' => 20,
        'message' => 'Teléfono debe contener solo números'
    ],
    'celular' => [
        'required' => false,
        'numeric' => true,
        'max_length' => 20,
        'message' => 'Celular debe contener solo números'
    ],
    'email' => [
        'required' => false,
        'max_length' => 250,
        'message' => 'Email inválido'
    ],
    'estado' => [
        'required' => true,
        'in_array' => [0, 1],
        'message' => 'Estado es requerido'
    ],
    'tipoCliente' => [
        'required' => true,
        'message' => 'Tipo de cliente es requerido'
    ],
    'interno' => [
        'required' => false,
        'in_array' => [0, 1],
        'message' => 'Interno debe ser 0 o 1'
    ],
    'observaciones' => [
        'required' => false,
        'max_length' => 500,
        'message' => 'Observaciones inválidas'
    ],
    'paginaweb' => [
        'required' => false,
        'max_length' => 250,
        'message' => 'Página web inválida'
    ],
    'idComercial' => [
        'required' => false,
        'max_length' => 250,
        'message' => 'Comercial encargado inválido'
    ]
];

// Error messages
$ERROR_MESSAGES = [
    'DUPLICATE_NAME' => 'Ya existe un cliente con ese nombre en el sistema',
    'UNAUTHORIZED' => 'No está autorizado para acceder a este módulo',
    'READ_ONLY' => 'Su usuario es de solo lectura, no puede realizar modificaciones',
    'INVALID_ID' => 'ID de cliente inválido',
    'SAVED_SUCCESS' => 'Cliente guardado exitosamente',
    'UPDATED_SUCCESS' => 'Cliente actualizado exitosamente',
    'DELETED_SUCCESS' => 'Cliente eliminado exitosamente',
    'VALIDATION_ERROR' => 'Por favor corrija los errores en el formulario',
    'DATABASE_ERROR' => 'Error en la base de datos, intente más tarde'
];
?>
