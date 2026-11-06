<?php
/**
 * SMS Grupos Module Configuration
 * Centralized settings, constants, and validation rules
 *
 * @package VIDEX
 * @version 1.0
 */

// Allowed profiles to access SMS groups
const ALLOWED_PROFILES_SMS_GRUPOS = [1, 2, 161, 162, 163];

// Denied profiles
const DENIED_PROFILES_SMS_GRUPOS = [3, 4, 160];

// Pagination
const PAGINATION_SIZE_SMS_GRUPOS = 50;

// Table names
const TABLE_GRUPOS = 'sms_grupos';
const TABLE_ASOCIACION = 'sms_asociacion';
const TABLE_USUARIOS = 'sms_usuarios';

// Field Validation Rules
const FIELD_RULES_SMS_GRUPOS = [
    'nombre' => ['required' => true, 'max_length' => 250],
    'descripcion' => ['required' => false, 'max_length' => 1000],
];

// Bulk upload field mapping (tab-separated)
const BULK_UPLOAD_FIELDS = [
    'nombres',
    'identificacion',
    'direccion',
    'paginaWeb',
    'email',
    'telfijo',
    'celular',
    'celular2'
];

// Error & Success Messages
const MESSAGES_SMS_GRUPOS = [
    'success_create' => 'Grupo creado correctamente',
    'success_update' => 'Grupo actualizado correctamente',
    'success_delete' => 'Grupo eliminado correctamente',
    'success_clean' => 'Usuarios del grupo eliminados correctamente',
    'success_bulk_upload' => 'Cargue masivo completado correctamente',
    'error_duplicate' => 'Ya existe un grupo con ese nombre',
    'error_not_found' => 'Grupo no encontrado',
    'error_unauthorized' => 'No está autorizado para acceder',
    'error_validation' => 'Por favor complete todos los campos requeridos',
    'error_invalid_data' => 'Los datos ingresados no son válidos',
];

// Default filters
const DEFAULT_FILTERS_SMS_GRUPOS = [
    'nombre' => '',
];
?>
