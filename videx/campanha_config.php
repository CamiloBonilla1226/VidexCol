<?php
/**
 * Campaign Module Configuration
 * Centralized settings, constants, and validation rules
 *
 * @package VIDEX
 * @version 1.0
 */

// Allowed profiles to access campaigns
const ALLOWED_PROFILES = [1, 2, 161, 162, 163];

// Pagination
const PAGINATION_SIZE = 50;

// Campaign Type Category ID (from categorias table, idSec = 23)
const CAMPAIGN_TYPE_CATEGORY_ID = 23;

// Campaign Status Constants
const CAMPAIGN_STATUS_ACTIVE = 1;
const CAMPAIGN_STATUS_INACTIVE = 0;
const CAMPAIGN_STATUS_DELETED = 0;

const CAMPAIGN_STATUS_LABELS = [
    1 => 'Activo',
    0 => 'Inactivo',
];

// Field Validation Rules
const FIELD_RULES = [
    'nombre' => ['required' => true, 'max_length' => 250],
    'fechaInicial' => ['required' => true, 'type' => 'date'],
    'fechaFinal' => ['required' => true, 'type' => 'date'],
    'estado' => ['required' => true, 'allowed_values' => [0, 1]],
    'tipoCampana' => ['required' => true, 'type' => 'numeric'],
    'observaciones' => ['required' => false, 'max_length' => 1000],
];

// Error & Success Messages
const MESSAGES = [
    'success_create' => 'Campaña creada correctamente',
    'success_update' => 'Campaña actualizada correctamente',
    'success_delete' => 'Campaña eliminada correctamente',
    'error_duplicate' => 'Ya existe una campaña con ese nombre',
    'error_not_found' => 'Campaña no encontrada',
    'error_unauthorized' => 'No está autorizado para acceder',
    'error_validation' => 'Por favor complete todos los campos requeridos',
];

// Table name
const TABLE_NAME = 'campana';

// Default filters
const DEFAULT_FILTERS = [
    'nombre' => '',
    'tipoCampana' => '',
    'estado' => '',
    'fechaInicial' => '',
    'fechaFinal' => '',
];
?>
