<?php
/**
 * glosario.php
 * Definiciones del glosario de Satura APP (fuente: Glosario.md).
 * Para agregar una definición nueva basta con añadir una línea al array.
 */

$GLOSARIO_DEFINICIONES = array(
    23 => 'Es la nueva información de un grupo existente que se encuentra registrado dentro del sistema de gestión y en el cual se le hace seguimiento de diferentes actividades: coaching, celebraciones, bautismos, etc.',
    44 => 'Es un grupo de creyentes maduros que no solo crece en números para ellos mismos, sino que tiene la capacidad de multiplicarse en otros grupos, en diferentes espacios, territorios, culturas y lenguas.',
    57 => 'Es un medio donde se ponen datos o información de una iglesia local que ha iniciado el proceso de plantación con la fundación; este se registra en el sistema de gestión.',
    7 => 'Herramienta clara y estratégica para llegar a un objetivo.',
    12 => 'Es una persona que escucha, comprende y responde al evangelio de manera positiva. Es la persona con la cual se inicia un IPG.',
    43 => 'Función de la iglesia saludable. Consiste en predicar la Palabra y así encontrar personas, hijos de paz.',
    49 => 'Es un encuentro especial en el que se reúnen las iglesias de pequeños grupos para celebrar lo que Dios está haciendo en las vidas de las personas. Se sugiere compartir alimentos en la celebración.',
    51 => 'Actividad pedagógica que se realiza para capacitar un grupo a través de la interactividad de los participantes y los facilitadores, siguiendo las 4 tareas de aprendizaje. Esta estrategia es usada en Satura Colombia para todos los procesos.',
    60 => 'Es una de las 9 funciones de la iglesia saludable; es el resultado final de discipular e inicio a la apertura de un IPG, y también es un mandato del Señor.',
);

$GLOSARIO_TERMINOS = array(
    23 => 'Nuevo reporte',
    44 => 'IPG (Iglesia pequeña o grupo)',
    57 => 'Crear un grupo IPG',
    7 => 'Coaching',
    12 => 'Persona de paz',
    43 => 'Función de evangelismo',
    49 => 'Gran celebración',
    51 => 'Taller',
    60 => 'Bautismo',
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
    echo '<link rel="stylesheet" href="glosario.css?v=' . @filemtime(__DIR__ . '/glosario.css') . '">' . "\n";
    echo '<script src="glosario.js?v=' . @filemtime(__DIR__ . '/glosario.js') . '"></script>' . "\n";
}
