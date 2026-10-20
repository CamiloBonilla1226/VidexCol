<?php
/**
 * includes/glosario.php
 * Definiciones del glosario de Satura APP (fuente: Glosario.md).
 * Para agregar una definición nueva basta con añadir una línea al array.
 */

$GLOSARIO_DEFINICIONES = array(
    23 => 'Es la nueva información de un grupo existente que se encuentra registrado dentro del sistema de gestión y en el cual se le hace seguimiento de diferentes actividades: coaching, celebraciones, bautismos, etc.',
    44 => 'Es un grupo de creyentes maduros que no solo crece en números para ellos mismos, sino que tiene la capacidad de multiplicarse en otros grupos, en diferentes espacios, territorios, culturas y lenguas.',
    57 => 'Es un medio donde se ponen datos o información de una iglesia local que ha iniciado el proceso de plantación con la fundación; este se registra en el sistema de gestión.',
);

$GLOSARIO_TERMINOS = array(
    23 => 'Nuevo reporte',
    44 => 'IPG (Iglesia pequeña o grupo)',
    57 => 'Crear un grupo IPG',
);

/**
 * Devuelve la definición $n escapada para HTML ('' si no existe).
 */
function glosario($n)
{
    global $GLOSARIO_DEFINICIONES;
    $n = (int)$n;
    return isset($GLOSARIO_DEFINICIONES[$n]) ? htmlspecialchars($GLOSARIO_DEFINICIONES[$n], ENT_QUOTES, 'UTF-8') : '';
}

/**
 * Atributos HTML para marcar un elemento con tooltip del glosario.
 * Uso: <span <?= glosario_attr(44) ?>>IPG</span>
 */
function glosario_attr($n)
{
    return 'data-glosario="' . (int)$n . '" data-glosario-texto="' . glosario($n) . '"';
}

/**
 * Icono de información (i): tooltip al pasar el mouse y modal al dar click.
 * Uso: <?= glosario_icono(44) ?> junto al texto que tiene definición.
 */
function glosario_icono($n)
{
    global $GLOSARIO_TERMINOS;
    $n = (int)$n;
    $titulo = isset($GLOSARIO_TERMINOS[$n]) ? htmlspecialchars($GLOSARIO_TERMINOS[$n], ENT_QUOTES, 'UTF-8') : '';
    return '<span class="glosario-info" ' . glosario_attr($n) . ' data-glosario-titulo="' . $titulo . '" data-glosario-modal tabindex="0" role="button" aria-label="Ver definición">i</span>';
}

/**
 * Imprime una sola vez los archivos CSS y JS del tooltip.
 */
function glosario_assets()
{
    static $impreso = false;
    if ($impreso) {
        return;
    }
    $impreso = true;
    echo '<link rel="stylesheet" href="css/glosario.css">' . "\n";
    echo '<script src="js/glosario.js"></script>' . "\n";
}
