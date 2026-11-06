<?php

const COTIZACION_TABLE = 'cotizacion';
const COTIZACION_NAME = 'COTIZACIONES';
const COTIZACION_PAGE_SIZE = 50;

const COTIZACION_ALLOWED_PROFILES = [1, 2, 161, 162, 163];
const COTIZACION_DENIED_PROFILES = [3, 4, 160];

const COTIZACION_SECTION_ESTADO = 16;
const COTIZACION_SECTION_ESTADO_PAGO = 17;
const COTIZACION_SECTION_CATEGORIA = 18;
const COTIZACION_SECTION_METODO = 19;
const COTIZACION_SECTION_TIPO_CONTACTO = 20;

const COTIZACION_MESSAGES = [
    'create_success' => 'Se ha creado correctamente el registro.',
    'update_success' => 'Se ha ACTUALIZADO correctamente el registro!',
    'delete_success' => 'Se ha ELIMINADO correctamente el registro!',
    'unauthorized' => 'No esta autorizado para ver esta informacion',
    'readonly' => 'Su usuario es de solo lectura, no puede realizar modificaciones.',
    'invalid_id' => 'ID Incorrecto. No Existe o no esta autorizado para visualizar la misma.',
    'validation_create' => 'La informacion es primordial para brindarle un excelente servicio, por favor seleccione al menos un prospecto y digite el campo de NOMBRE DEL CONTACTO',
    'validation_update' => 'La informacion es primordial para brindarle un excelente servicio, por favor digite al menos el campo de NOMBRE',
];

function cotizacion_is_denied_profile($perfil)
{
    return in_array((int) $perfil, COTIZACION_DENIED_PROFILES, true);
}

function cotizacion_is_allowed_profile($perfil)
{
    return in_array((int) $perfil, COTIZACION_ALLOWED_PROFILES, true);
}

function cotizacion_string($value)
{
    return trim((string) $value);
}

function cotizacion_int($value)
{
    return (int) $value;
}

function cotizacion_date($value)
{
    $value = trim((string) $value);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
}

function cotizacion_build_request_filters($source)
{
    return [
        'idCliente' => cotizacion_int($source['idCliente'] ?? 0),
        'estado' => cotizacion_int($source['estado'] ?? 0),
        'estadoPago' => cotizacion_int($source['estadoPago'] ?? 0),
        'categoriaPrincipal' => cotizacion_int($source['categoriaPrincipal'] ?? 0),
        'metodoCotizacion' => cotizacion_int($source['metodoCotizacion'] ?? 0),
        'tipoContacto' => cotizacion_int($source['tipoContacto'] ?? 0),
        'interno' => isset($source['interno']) && $source['interno'] !== '' ? cotizacion_int($source['interno']) : '',
        'contactoNombre' => cotizacion_string($source['contactoNombre'] ?? ''),
        'contactoTelefono' => cotizacion_string($source['contactoTelefono'] ?? ''),
        'contactoCelular' => cotizacion_string($source['contactoCelular'] ?? ''),
        'contactoEmail' => cotizacion_string($source['contactoEmail'] ?? ''),
        'fechaInicial' => cotizacion_date($source['fechaInicial'] ?? ''),
        'fechaFinal' => cotizacion_date($source['fechaFinal'] ?? ''),
        'activos' => cotizacion_int($source['activos'] ?? 0),
    ];
}
