<?php
/**
 * glosario_graficas.php
 * Sección Glosario: libro de dos páginas con las definiciones del helper glosario.php.
 * Se carga desde index.php?doc=glosario_graficas (menú y sesión los pone index.php).
 */

// El usuario debe estar autenticado (verificado por index.php)
if (!isset($_SESSION['id'])) {
    echo "No autorizado";
    exit();
}

require_once __DIR__ . '/glosario.php';

// [numero, termino, definicion] para el script de la sección
$glosarioLista = array();
foreach ($GLOSARIO as $glosarioNum => $glosarioDef) {
    $glosarioLista[] = array((int)$glosarioNum, $glosarioDef['termino'], $glosarioDef['definicion']);
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=EB+Garamond:ital,wght@0,400;0,500;0,700;1,400&display=swap">
<link rel="stylesheet" href="glosario_libro.css?v=<?= @filemtime(__DIR__ . '/glosario_libro.css') ?>">

<div class="glosario" id="glosario">
    <header class="gl-banner">
        <div class="gl-cruz">✠</div>
        <h1>GLOSARIO</h1>
        <p>Términos del sistema de gestión Satura Colombia</p>
    </header>

    <div class="gl-herramientas">
        <input id="glBuscar" class="gl-buscador" type="search" placeholder="Buscar un término o una definición…" aria-label="Buscar en el glosario">
    </div>

    <nav id="glLetras" class="gl-letras" aria-label="Filtrar por letra"></nav>

    <div class="gl-libro">
        <section class="gl-lomo" aria-live="polite">
            <div class="gl-pag izq" id="glPagIzq"></div>
            <div class="gl-pag der" id="glPagDer"></div>
        </section>
    </div>

    <div class="gl-nav">
        <button type="button" id="glBtnPrev">‹ Anterior</button>
        <span class="gl-estado" id="glEstado"></span>
        <button type="button" id="glBtnNext">Siguiente ›</button>
    </div>

    <div class="gl-pie">Fundación Satura Colombia · Saturar a Colombia de Cristo · Usa las flechas ← → del teclado para pasar de página</div>
</div>

<script>
const GLOSARIO = <?= json_encode($glosarioLista, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script src="glosario_libro.js?v=<?= @filemtime(__DIR__ . '/glosario_libro.js') ?>"></script>
