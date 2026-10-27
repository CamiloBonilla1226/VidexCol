<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// PRIVACIDAD: este dashboard muestra el nombre (apellido y nombre) de cada docente con su
// cantidad de planeaciones y observaciones. El acceso a esta acción debe limitarse a
// directivos (Permisos de acción). No se envían IDs de persona ni datos de contacto.

use Modules\Graficas\GraficasGateway;

require_once __DIR__ . '/src/GraficasGateway.php';

// Las observaciones con fecha anterior a esta se descartan (hay años mal digitados: 26, 206…).
$fechaMinima = '2020-01-01';
// false = se excluyen planeaciones y observaciones con fecha posterior a hoy (los docentes
// pueden planear por adelantado). Ponlo en true para incluirlas.
$incluirFechasFuturas = false;

if (isActionAccessible($guid, $connection2, '/modules/Graficas/docentes.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add('Gráficas de docentes');

    $gateway = $container->get(GraficasGateway::class);
    $eventos = $gateway->queryGraficasDocentes($fechaMinima, $incluirFechasFuturas ? null : date('Y-m-d'));
    $activos = $gateway->queryDocentesActivos();

    // Para que el navegador reciba poco: los nombres y materias van una sola vez en listas y
    // cada evento es [tipo (0 planeación, 1 observación), fecha AAAAMMDD, docente, materia, id].
    $docentes = [];
    $materias = [];
    $filas = [];
    foreach ($eventos as $e) {
        $d = $e['docente'];
        $m = $e['materia'];
        if (!isset($docentes[$d])) { $docentes[$d] = count($docentes); }
        if (!isset($materias[$m])) { $materias[$m] = count($materias); }
        $filas[] = [$e['tipo'] === 'P' ? 0 : 1, (int) str_replace('-', '', $e['fecha']), $docentes[$d], $materias[$m], (int) $e['idEvento']];
    }
    $listaDocentes = array_keys($docentes);
    $listaMaterias = array_keys($materias);
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;
    $generado = date('d/m/Y H:i');

    ?>
    <style>
        .gr-wrap { color: #1F2937; max-width: 1180px; }
        .gr-wrap h2 { margin: 0 0 4px; color: #1E3A8A; }

        /* Barra de filtros: controles segmentados, sin bordes duros */
        .gr-filtros { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px 28px; margin: 6px 0 22px; padding: 14px 18px; background: #F7F9FE; border-radius: 14px; }
        .gr-campo { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        .gr-campo > span { font-size: .78em; font-weight: 600; color: #6b7280; }
        .gr-seg { display: inline-flex; flex-wrap: wrap; gap: 2px; padding: 3px; background: #E9EEF8; border-radius: 10px; }
        .gr-seg button { border: 0; background: transparent; color: #4B5563; font: inherit; font-size: .9em; font-weight: 600; padding: 6px 14px; border-radius: 8px; cursor: pointer; transition: background .15s, color .15s, box-shadow .15s; }
        .gr-seg button:hover { color: #1E3A8A; }
        .gr-seg button[aria-pressed="true"] { background: #fff; color: #1E3A8A; box-shadow: 0 1px 3px rgba(30,58,138,.15); }
        .gr-seg button:focus-visible, .gr-select:focus-visible, .gr-limpiar:focus-visible { outline: 2px solid #3575EF; outline-offset: 2px; }
        .gr-select { appearance: none; -webkit-appearance: none; -moz-appearance: none; min-width: 170px; border: 0; border-radius: 10px; background: #E9EEF8 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' fill='none' stroke='%231E3A8A' stroke-width='1.6' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 12px center; padding: 9px 34px 9px 14px; font: inherit; font-size: .9em; font-weight: 600; color: #1E3A8A; cursor: pointer; }
        .gr-select.gr-activo { background-color: #fff; box-shadow: 0 0 0 2px #3575EF inset; }
        .gr-limpiar { display: none; margin-left: auto; align-self: center; border: 0; background: none; padding: 6px 4px; font: inherit; font-size: .85em; font-weight: 600; color: #3575EF; cursor: pointer; }
        .gr-limpiar:hover { color: #1E3A8A; text-decoration: underline; }

        /* Cifras en una sola franja */
        .gr-cifras { display: grid; grid-template-columns: repeat(4, 1fr); margin-bottom: 22px; border: 1px solid #E3EAF8; border-radius: 10px; background: #fff; }
        .gr-cifra { padding: 14px 20px; border-left: 1px solid #E3EAF8; }
        .gr-cifra:first-child { border-left: 0; }
        .gr-cifra b { display: block; font-size: 2em; line-height: 1.1; font-weight: 700; color: #1E3A8A; font-variant-numeric: tabular-nums; }
        .gr-cifra:first-child b { color: #3575EF; }
        .gr-cifra span { font-size: .85em; color: #6b7280; }

        .gr-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; align-items: stretch; }
        .gr-card { background: #fff; border: 1px solid #E3EAF8; border-radius: 10px; padding: 18px 20px 10px; min-width: 0; }
        .gr-card.gr-wide { grid-column: 1 / -1; }
        .gr-card { position: relative; }
        .gr-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 4px; }
        .gr-card h3 { margin: 0; font-size: 1.05em; font-weight: 700; color: #1E3A8A; }
        .gr-info { flex: none; display: grid; place-items: center; width: 28px; height: 28px; margin: -4px -6px 0 0; padding: 0; border: 0; border-radius: 50%; background: transparent; color: #9CA3AF; cursor: pointer; transition: background .15s, color .15s; }
        .gr-info:hover, .gr-info[aria-expanded="true"] { background: #EEF4FF; color: #3575EF; }
        .gr-info:focus-visible { outline: 2px solid #3575EF; outline-offset: 1px; }
        .gr-pop { position: absolute; top: 50px; right: 12px; z-index: 5; width: min(340px, calc(100% - 24px)); background: #fff; border: 1px solid #DCE7FD; border-radius: 14px; box-shadow: 0 18px 40px rgba(30,58,138,.18), 0 2px 6px rgba(30,58,138,.06); color: #1F2937; animation: gr-aparecer .16s ease-out; }
        .gr-pop[hidden] { display: none; }
        .gr-pop::before { content: ''; position: absolute; top: -7px; right: 11px; width: 12px; height: 12px; background: #F5F8FF; border-left: 1px solid #DCE7FD; border-top: 1px solid #DCE7FD; transform: rotate(45deg); }
        .gr-pop-head { display: flex; align-items: center; gap: 10px; padding: 12px 12px 12px 16px; background: #F5F8FF; border-bottom: 1px solid #E3EAF8; border-radius: 14px 14px 0 0; }
        .gr-pop-icono { display: grid; place-items: center; flex: none; width: 28px; height: 28px; border-radius: 50%; background: #3575EF; color: #fff; }
        .gr-pop-titulo { flex: 1; font-weight: 700; font-size: .95em; color: #1E3A8A; }
        .gr-pop-cerrar { display: grid; place-items: center; flex: none; width: 28px; height: 28px; padding: 0; border: 0; border-radius: 50%; background: transparent; color: #6b7280; font-size: 1.3em; line-height: 1; cursor: pointer; }
        .gr-pop-cerrar:hover { background: #E3ECFF; color: #1E3A8A; }
        .gr-pop-cerrar:focus-visible { outline: 2px solid #3575EF; outline-offset: 1px; }
        .gr-pop-body { padding: 14px 18px 16px; font-size: 1em; line-height: 1.6; }
        .gr-pop-body p { margin: 0 0 10px; }
        .gr-pop-body p:last-child { margin-bottom: 0; }
        .gr-pop strong { color: #1E3A8A; }
        .gr-pop-ej { padding: 10px 12px; background: #F5F8FF; border-left: 3px solid #3575EF; border-radius: 8px; }
        .gr-pop-dato { font-size: .9em; color: #4B5563; }
        @keyframes gr-aparecer { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .gr-pop { animation: none; } }
        .gr-lectura { margin: 0 0 6px; font-size: .95em; color: #1F2937; line-height: 1.45; }
        .gr-lectura strong { color: #1E3A8A; }
        .gr-chart { width: 100%; }
        .gr-empty { display: none; border: 1px dashed #9DB8F5; border-radius: 10px; background: #F5F8FF; color: #1E3A8A; padding: 40px 16px; text-align: center; font-weight: 600; }
        .gr-foot { margin-top: 14px; font-size: .8em; color: #6b7280; }
        @media (max-width: 900px) {
            .gr-cifras { grid-template-columns: 1fr 1fr; }
            .gr-cifra:nth-child(3) { border-left: 0; }
            .gr-cifra:nth-child(n+3) { border-top: 1px solid #E3EAF8; }
            .gr-grid { grid-template-columns: 1fr; }
        }
        /* Extras de docentes */
        .gr-fecha, .gr-buscar { border: 0; border-radius: 10px; background: #E9EEF8; padding: 8px 12px; font: inherit; font-size: .9em; font-weight: 600; color: #1E3A8A; }
        .gr-fecha { min-width: 0; width: 150px; max-width: 100%; }
        .gr-buscar { min-width: 200px; }
        .gr-buscar::placeholder { color: #6b7280; font-weight: 500; }
        .gr-fecha:focus-visible, .gr-buscar:focus-visible { outline: 2px solid #3575EF; outline-offset: 2px; }
        .gr-rango { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; color: #6b7280; }
        .gr-vacio { padding: 36px 12px; text-align: center; color: #6b7280; font-size: .95em; }
        .gr-cumpl { padding: 8px 4px 14px; }
        .gr-cumpl-num { font-size: 3.4em; font-weight: 800; line-height: 1; color: #1E3A8A; font-variant-numeric: tabular-nums; }
        .gr-pista { height: 14px; margin: 14px 0 22px; border-radius: 999px; background: #E3ECFF; overflow: hidden; }
        .gr-relleno { height: 100%; border-radius: 999px; background: #3575EF; transition: width .4s ease; }
        .gr-sin-titulo { margin: 0 0 8px; font-size: .9em; font-weight: 600; color: #6b7280; }
        .gr-sin { display: flex; flex-wrap: wrap; gap: 6px; margin: 0; padding: 0; list-style: none; }
        .gr-sin li { padding: 4px 12px; border: 1px solid #DCE7FD; border-radius: 999px; background: #F5F8FF; font-size: .88em; color: #1E3A8A; }
        .gr-todos { margin: 0; font-weight: 600; color: #1E3A8A; }
        .gr-cifras.gr-tres { grid-template-columns: repeat(3, 1fr); }
        .gr-cifras.gr-tres .gr-cifra:nth-child(3) { border-left: 1px solid #E3EAF8; border-top: 0; }
        @media (max-width: 900px) { .gr-cifras.gr-tres { grid-template-columns: 1fr; } .gr-cifras.gr-tres .gr-cifra { border-left: 0; border-top: 1px solid #E3EAF8; } .gr-cifras.gr-tres .gr-cifra:first-child { border-top: 0; } }
    </style>

    <div class="gr-wrap" id="gr-wrap">
        <h2>Gráficas de docentes</h2>

        <div class="gr-filtros" role="group" aria-label="Filtros">
            <div class="gr-campo"><span>Año</span><div class="gr-seg" id="f-anio"></div></div>
            <div class="gr-campo"><span>Fechas (opcional)</span>
                <div class="gr-rango"><input type="date" class="gr-fecha" id="f-desde" aria-label="Desde"> <span>a</span> <input type="date" class="gr-fecha" id="f-hasta" aria-label="Hasta"></div>
            </div>
            <div class="gr-campo"><span>Docente</span><input type="search" class="gr-buscar" id="f-docente" list="dl-docentes" placeholder="Escribe para buscar" autocomplete="off"><datalist id="dl-docentes"></datalist></div>
            <div class="gr-campo"><span>Materia</span><select class="gr-select" id="f-materia"><option value="">Todas las materias</option></select></div>
            <div class="gr-campo"><span>Tipo</span><div class="gr-seg" id="f-tipo"></div></div>
            <button type="button" class="gr-limpiar" id="f-limpiar">Quitar filtros</button>
        </div>

        <div class="gr-cifras gr-tres">
            <div class="gr-cifra"><b id="kpi-plan">–</b><span>planeaciones</span></div>
            <div class="gr-cifra"><b id="kpi-obs">–</b><span>observaciones</span></div>
            <div class="gr-cifra"><b id="kpi-doc">–</b><span>docentes con actividad</span></div>
        </div>

        <div class="gr-empty" id="gr-empty">Sin datos para los filtros seleccionados</div>

        <div class="gr-grid" id="gr-graficas">
            <div class="gr-card gr-wide">
                <div class="gr-card-head"><h3>¿Cuántas planeaciones se hacen cada mes?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-meses"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-meses" role="dialog" aria-label="¿Cuántas planeaciones se hacen cada mes?" hidden><div class="gr-pop-head"><span class="gr-pop-icono" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 11v6"/><circle cx="12" cy="7" r=".8" fill="currentColor"/></svg></span><span class="gr-pop-titulo">¿Qué muestra esta gráfica?</span><button type="button" class="gr-pop-cerrar" aria-label="Cerrar">&times;</button></div><div class="gr-pop-body"><p>Cuántas planeaciones se registran en cada mes del año elegido. El mes con más planeaciones va en azul fuerte.</p><p class="gr-pop-dato">El mes en curso aún no termina. Con «Todos» en el año se suman todos los años.</p></div></div>
                <p class="gr-lectura" id="l-meses"></p>
                <div class="gr-chart" id="g-meses"></div>
            </div>
            <div class="gr-card">
                <div class="gr-card-head"><h3>¿Quiénes planean más?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-top-plan"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-top-plan" role="dialog" aria-label="¿Quiénes planean más?" hidden><div class="gr-pop-head"><span class="gr-pop-icono" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 11v6"/><circle cx="12" cy="7" r=".8" fill="currentColor"/></svg></span><span class="gr-pop-titulo">¿Qué muestra esta gráfica?</span><button type="button" class="gr-pop-cerrar" aria-label="Cerrar">&times;</button></div><div class="gr-pop-body"><p>Los 15 docentes con más planeaciones en el año y las fechas elegidos.</p><p class="gr-pop-dato">Cada planeación se cuenta para quien la creó en Gibbon.</p></div></div>
                <p class="gr-lectura" id="l-top-plan"></p>
                <div class="gr-chart" id="g-top-plan"></div>
            </div>
            <div class="gr-card">
                <div class="gr-card-head"><h3>¿Quiénes registran más observaciones?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-top-obs"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-top-obs" role="dialog" aria-label="¿Quiénes registran más observaciones?" hidden><div class="gr-pop-head"><span class="gr-pop-icono" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 11v6"/><circle cx="12" cy="7" r=".8" fill="currentColor"/></svg></span><span class="gr-pop-titulo">¿Qué muestra esta gráfica?</span><button type="button" class="gr-pop-cerrar" aria-label="Cerrar">&times;</button></div><div class="gr-pop-body"><p>Los 15 docentes que más observaciones de estudiantes registraron en el año y las fechas elegidos.</p><p class="gr-pop-dato">Las observaciones no tienen materia: si eliges una materia, esta gráfica queda vacía.</p></div></div>
                <p class="gr-lectura" id="l-top-obs"></p>
                <div class="gr-chart" id="g-top-obs"></div>
            </div>
            <div class="gr-card">
                <div class="gr-card-head"><h3>¿Han subido o bajado las observaciones?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-obsanios"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-obsanios" role="dialog" aria-label="¿Han subido o bajado las observaciones?" hidden><div class="gr-pop-head"><span class="gr-pop-icono" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 11v6"/><circle cx="12" cy="7" r=".8" fill="currentColor"/></svg></span><span class="gr-pop-titulo">¿Qué muestra esta gráfica?</span><button type="button" class="gr-pop-cerrar" aria-label="Cerrar">&times;</button></div><div class="gr-pop-body"><p>Total de observaciones registradas en cada año. La barra azul fuerte es el año que estás viendo.</p><p class="gr-pop-dato">El año en curso aún no termina, por eso puede verse más bajo. No cambia con el filtro de año.</p></div></div>
                <p class="gr-lectura" id="l-obsanios"></p>
                <div class="gr-chart" id="g-obsanios"></div>
            </div>
            <div class="gr-card">
                <div class="gr-card-head"><h3>¿Cuántos docentes están al día con su planeación?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-cumpl"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-cumpl" role="dialog" aria-label="¿Cuántos docentes están al día con su planeación?" hidden><div class="gr-pop-head"><span class="gr-pop-icono" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 11v6"/><circle cx="12" cy="7" r=".8" fill="currentColor"/></svg></span><span class="gr-pop-titulo">¿Qué muestra esta gráfica?</span><button type="button" class="gr-pop-cerrar" aria-label="Cerrar">&times;</button></div><div class="gr-pop-body"><p>De todos los docentes activos, cuántos tienen <strong>al menos una planeación</strong> en el año y las fechas elegidos.</p><p class="gr-pop-ej">Ejemplo: 24 de 28 docentes = 86 %.</p><p class="gr-pop-dato">Abajo aparecen los que todavía no tienen ninguna. Los docentes activos son el personal de Gibbon con estado «Full». No cambia con el filtro de docente.</p></div></div>
                <p class="gr-lectura" id="l-cumpl"></p>
                <div class="gr-chart" id="g-cumpl"></div>
            </div>
        </div>

        <div class="gr-foot">Fuente: planeaciones y observaciones registradas en Gibbon. Datos generados el <?php echo htmlspecialchars($generado, ENT_QUOTES, 'UTF-8'); ?>.<?php echo $incluirFechasFuturas ? '' : ' Solo hasta la fecha de hoy.'; ?></div>
    </div>

    <script src="https://www.gstatic.com/charts/loader.js"></script>
    <script>
    (function () {
        'use strict';

        // Evento = [tipo (0 planeación, 1 observación), fecha AAAAMMDD, índice de docente, índice de materia, id]
        var EVENTOS = <?php echo json_encode($filas, $jsonFlags); ?>;
        var DOCENTES = <?php echo json_encode($listaDocentes, $jsonFlags); ?>;
        var MATERIAS = <?php echo json_encode($listaMaterias, $jsonFlags); ?>;
        var ACTIVOS = <?php echo json_encode($activos, $jsonFlags); ?>;
        var FECHA_MINIMA = <?php echo (int) str_replace('-', '', $fechaMinima); ?>;

        // Paleta del proyecto (la misma de los reportes de Informes Escolares).
        var COLOR = { primario: '#3575EF', oscuro: '#1E3A8A', claro: '#9DB8F5', pale: '#DCE7FD', gris: '#9CA3AF' };
        var MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        var MESES_LARGO = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        var TOP = 15;
        var ALTO = 320;
        var ALTO_TOP = 30 * TOP + 40;

        // Vacío = sin filtro. docente y materia guardan el índice dentro de DOCENTES / MATERIAS.
        var estado = { anio: '', desde: 0, hasta: 0, docente: '', materia: '', tipo: '' };
        var anios = [], redimensionar = null;

        function $(id) { return document.getElementById(id); }
        function mostrar(id, visible, tipo) { $(id).style.display = visible ? (tipo || '') : 'none'; }
        function num(n) { return Number(n).toLocaleString('es-CO'); }
        function pct(n, total) { return total ? Math.round(100 * n / total) + ' %' : '0 %'; }
        function anioDe(fecha) { return Math.floor(fecha / 10000); }
        function mesDe(fecha) { return Math.floor(fecha / 100) % 100; }
        function aEntero(valor) { return valor ? parseInt(String(valor).replace(/-/g, ''), 10) : 0; }

        // Escribe una frase con partes en negrita sin usar innerHTML (todo se escapa).
        function lectura(id, partes) {
            var el = $(id);
            while (el.firstChild) { el.removeChild(el.firstChild); }
            partes.forEach(function (p) {
                if (typeof p === 'string') { el.appendChild(document.createTextNode(p)); return; }
                var b = document.createElement('strong');
                b.textContent = p.b;
                el.appendChild(b);
            });
        }

        function aviso(idGrafica, idLectura, texto) {
            var el = $(idGrafica);
            while (el.firstChild) { el.removeChild(el.firstChild); }
            var d = document.createElement('div');
            d.className = 'gr-vacio';
            d.textContent = texto;
            el.appendChild(d);
            if (idLectura) { lectura(idLectura, []); }
        }

        // ---- Filtros ---------------------------------------------------------
        // o indica qué filtros aplican a cada gráfica: anio, rango, docente, materia, tipo.
        function pasa(e, o) {
            if (o.tipo && estado.tipo !== '' && e[0] !== estado.tipo) { return false; }
            if (o.anio && estado.anio !== '' && anioDe(e[1]) !== estado.anio) { return false; }
            if (o.rango) {
                if (estado.desde && e[1] < estado.desde) { return false; }
                if (estado.hasta && e[1] > estado.hasta) { return false; }
            }
            if (o.docente && estado.docente !== '' && e[2] !== estado.docente) { return false; }
            // Las observaciones no tienen materia: con una materia elegida solo quedan planeaciones.
            if (o.materia && estado.materia !== '' && (e[0] === 1 || e[3] !== estado.materia)) { return false; }
            return true;
        }

        function filtrar(o) { return EVENTOS.filter(function (e) { return pasa(e, o); }); }

        // Cuenta eventos distintos por clave (una planeación de una clase con varios docentes
        // aparece repetida y debe contarse una sola vez).
        function contarPor(filas, clave) {
            var vistos = {}, cuentas = {};
            filas.forEach(function (e) {
                var k = clave(e);
                var set = vistos[k] || (vistos[k] = {});
                if (set[e[4]]) { return; }
                set[e[4]] = true;
                cuentas[k] = (cuentas[k] || 0) + 1;
            });
            return cuentas;
        }

        function distintos(filas, tipo) {
            var set = {}, n = 0;
            filas.forEach(function (e) { if (e[0] === tipo && !set[e[4]]) { set[e[4]] = true; n++; } });
            return n;
        }

        function opcion(sel, valor, etiqueta) {
            var o = document.createElement('option');
            o.value = valor;
            o.textContent = etiqueta;
            sel.appendChild(o);
        }

        function segmentado(id, opciones, clave) {
            var cont = $(id);
            opciones.forEach(function (o) {
                var b = document.createElement('button');
                b.type = 'button';
                b.textContent = o.etiqueta;
                b.setAttribute('data-valor', String(o.valor));
                b.addEventListener('click', function () { estado[clave] = o.valor; render(); });
                cont.appendChild(b);
            });
        }

        function iniciarFiltros() {
            var vistos = {};
            EVENTOS.forEach(function (e) { var a = anioDe(e[1]); if (!vistos[a]) { vistos[a] = true; anios.push(a); } });
            anios.sort(function (a, b) { return a - b; });
            var hoy = new Date().getFullYear();
            estado.anio = anios.indexOf(hoy) !== -1 ? hoy : anios[anios.length - 1];
            segmentado('f-anio', [{ valor: '', etiqueta: 'Todos' }].concat(anios.map(function (a) { return { valor: a, etiqueta: String(a) }; })), 'anio');
            segmentado('f-tipo', [{ valor: '', etiqueta: 'Ambos' }, { valor: 0, etiqueta: 'Planeaciones' }, { valor: 1, etiqueta: 'Observaciones' }], 'tipo');

            var lista = $('dl-docentes');
            DOCENTES.slice().sort().forEach(function (d) { var o = document.createElement('option'); o.value = d; lista.appendChild(o); });
            var selMateria = $('f-materia');
            MATERIAS.map(function (m, i) { return { m: m, i: i }; })
                .filter(function (x) { return x.m !== 'Sin materia'; })
                .sort(function (a, b) { return a.m.localeCompare(b.m, 'es'); })
                .forEach(function (x) { opcion(selMateria, x.i, x.m); });

            $('f-desde').addEventListener('change', function () { estado.desde = aEntero(this.value); render(); });
            $('f-hasta').addEventListener('change', function () { estado.hasta = aEntero(this.value); render(); });
            $('f-docente').addEventListener('input', function () {
                var i = DOCENTES.indexOf(this.value);
                var nuevo = i === -1 ? '' : i;
                if (nuevo !== estado.docente) { estado.docente = nuevo; render(); }
            });
            selMateria.addEventListener('change', function () { estado.materia = this.value === '' ? '' : Number(this.value); render(); });
            $('f-limpiar').addEventListener('click', function () {
                estado.desde = 0; estado.hasta = 0; estado.docente = ''; estado.materia = ''; estado.tipo = '';
                $('f-desde').value = ''; $('f-hasta').value = ''; $('f-docente').value = ''; selMateria.value = '';
                render();
            });
        }

        function marcarFiltros() {
            [['f-anio', 'anio'], ['f-tipo', 'tipo']].forEach(function (par) {
                Array.prototype.forEach.call($(par[0]).querySelectorAll('button'), function (b) {
                    b.setAttribute('aria-pressed', b.getAttribute('data-valor') === String(estado[par[1]]) ? 'true' : 'false');
                });
            });
            $('f-materia').classList.toggle('gr-activo', estado.materia !== '');
            var hay = estado.desde || estado.hasta || estado.docente !== '' || estado.materia !== '' || estado.tipo !== '';
            mostrar('f-limpiar', !!hay, 'inline-block');
        }

        // ---- Gráficas ----------------------------------------------------------

        function nuevo(tipo, id) { return new google.visualization[tipo]($(id)); }

        function opciones(extra) {
            var o = {
                height: ALTO,
                fontSize: 12,
                backgroundColor: 'transparent',
                legend: { position: 'none' },
                chartArea: { left: 40, right: 16, top: 24, bottom: 40 },
                colors: [COLOR.primario],
                bar: { groupWidth: '62%' },
                annotations: { alwaysOutside: true, stem: { color: 'transparent', length: 2 }, textStyle: { fontSize: 12, bold: true, color: '#1F2937', auraColor: 'none' } },
                hAxis: { textStyle: { color: '#4B5563' }, gridlines: { color: 'transparent' }, baselineColor: '#CFDCF8' },
                vAxis: { textStyle: { color: '#4B5563' }, gridlines: { color: '#F1F4FA' }, baselineColor: '#CFDCF8', minValue: 0 },
                tooltip: { textStyle: { fontSize: 12 } }
            };
            Object.keys(extra || {}).forEach(function (k) {
                o[k] = (extra[k] && typeof extra[k] === 'object' && !Array.isArray(extra[k]) && o[k] && typeof o[k] === 'object')
                    ? Object.assign({}, o[k], extra[k]) : extra[k];
            });
            return o;
        }

        // Tabla [categoría, valor, número escrito, ayuda al pasar el mouse, color].
        function tablaBarras(titulo, filas) {
            var t = new google.visualization.DataTable();
            t.addColumn('string', titulo);
            t.addColumn('number', 'Cantidad');
            t.addColumn({ type: 'string', role: 'annotation' });
            t.addColumn({ type: 'string', role: 'tooltip' });
            t.addColumn({ type: 'string', role: 'style' });
            filas.forEach(function (f) { t.addRow([f.etiqueta, f.valor, num(f.valor), f.ayuda, 'color: ' + (f.color || COLOR.primario)]); });
            return t;
        }

        function ejeConAire(barras) {
            var max = 0;
            barras.forEach(function (b) { if (b.valor > max) { max = b.valor; } });
            return { textPosition: 'none', gridlines: { color: 'transparent' }, viewWindow: { min: 0, max: Math.max(1, max * 1.15) } };
        }

        // Con "Tipo" elegido, las gráficas del otro tipo avisan en vez de mostrar datos.
        function ocultoPorTipo(tipo, idGrafica, idLectura) {
            if (estado.tipo === '' || estado.tipo === tipo) { return false; }
            aviso(idGrafica, idLectura, 'Filtro de tipo activo: aquí se muestran ' + (tipo === 0 ? 'planeaciones' : 'observaciones') + ' solo con «Ambos» o «' + (tipo === 0 ? 'Planeaciones' : 'Observaciones') + '».');
            return true;
        }

        // 1. Planeaciones por mes: una columna por mes del año elegido (con «Todos», suma de todos los años).
        function graficaMeses() {
            if (ocultoPorTipo(0, 'g-meses', 'l-meses')) { return; }
            var filas = filtrar({ anio: true, rango: true, docente: true, materia: true }).filter(function (e) { return e[0] === 0; });
            if (!filas.length) { aviso('g-meses', 'l-meses', 'No hay planeaciones con esos filtros.'); return; }
            var cuentas = contarPor(filas, function (e) { return mesDe(e[1]); });
            var hoy = new Date();
            var enCurso = estado.anio === hoy.getFullYear() ? hoy.getMonth() + 1 : 0;   // mes que aún no termina
            var hasta = enCurso || 12;
            var barras = [];
            for (var m = 1; m <= hasta; m++) {
                var n = cuentas[m] || 0;
                barras.push({ mes: m, etiqueta: MESES[m - 1], valor: n,
                              ayuda: MESES_LARGO[m - 1].charAt(0).toUpperCase() + MESES_LARGO[m - 1].slice(1) + ': ' + num(n) + ' planeaciones' + (m === enCurso ? ' (mes en curso)' : '') });
            }
            // El mes en curso no compite para "más" ni "menos" porque está incompleto.
            var completos = barras.filter(function (b) { return b.mes !== enCurso; });
            if (!completos.length) { completos = barras; }
            var mayor = completos.reduce(function (a, b) { return b.valor > a.valor ? b : a; }, completos[0]);
            var menor = completos.reduce(function (a, b) { return b.valor < a.valor ? b : a; }, completos[0]);
            barras.forEach(function (b) { b.color = b === mayor ? COLOR.primario : COLOR.claro; });
            var periodo = estado.anio === '' ? 'En todos los años' : 'En ' + estado.anio;
            var partes = [periodo + ' el mes con más planeaciones es ', { b: MESES_LARGO[mayor.mes - 1] + ' (' + num(mayor.valor) + ')' }];
            if (menor !== mayor) { partes.push(' y el que menos, ', { b: MESES_LARGO[menor.mes - 1] + ' (' + num(menor.valor) + ')' }); }
            lectura('l-meses', partes);
            nuevo('ColumnChart', 'g-meses').draw(tablaBarras('Mes', barras), opciones({
                vAxis: ejeConAire(barras),
                chartArea: { left: 8, right: 8, top: 28, bottom: 36 }
            }));
        }

        // Barras horizontales Top N de un tipo de evento (2 y 4).
        function graficaTop(tipo, idGrafica, idLectura, palabra) {
            if (ocultoPorTipo(tipo, idGrafica, idLectura)) { return; }
            var filas = filtrar({ anio: true, rango: true, docente: true, materia: true }).filter(function (e) { return e[0] === tipo; });
            if (!filas.length) {
                aviso(idGrafica, idLectura, tipo === 1 && estado.materia !== '' ? 'Las observaciones no tienen materia: quita la materia para verlas.' : 'No hay ' + palabra + ' con esos filtros.');
                return;
            }
            var cuentas = contarPor(filas, function (e) { return e[2]; });
            var lista = Object.keys(cuentas).map(function (k) { return { nombre: DOCENTES[k], valor: cuentas[k] }; })
                .sort(function (a, b) { return b.valor - a.valor || a.nombre.localeCompare(b.nombre, 'es'); });
            var barras = lista.slice(0, TOP).map(function (x) {
                return { etiqueta: x.nombre, valor: x.valor, ayuda: x.nombre + ': ' + num(x.valor) + ' ' + palabra };
            });
            lectura(idLectura, [{ b: lista[0].nombre }, ' lidera con ', { b: num(lista[0].valor) },
                lista.length > TOP ? ' · se muestran los ' + TOP + ' primeros de ' + num(lista.length) : ' · ' + num(lista.length) + (lista.length === 1 ? ' docente' : ' docentes')]);
            nuevo('BarChart', idGrafica).draw(tablaBarras('Docente', barras), opciones({
                height: Math.max(ALTO, 30 * barras.length + 40),
                hAxis: { textPosition: 'none', gridlines: { color: 'transparent' }, viewWindow: { min: 0, max: Math.max(1, lista[0].valor * 1.15) } },
                vAxis: { gridlines: { color: 'transparent' }, textStyle: { color: '#1F2937' } },
                chartArea: { left: 190, right: 40, top: 8, bottom: 8 },
                bar: { groupWidth: '70%' }
            }));
        }

        // 3. Observaciones por año: ignora año y fechas. Con una materia elegida no hay observaciones.
        function graficaObsAnios() {
            if (ocultoPorTipo(1, 'g-obsanios', 'l-obsanios')) { return; }
            var filas = filtrar({ docente: true, materia: true }).filter(function (e) { return e[0] === 1; });
            if (!filas.length) { aviso('g-obsanios', 'l-obsanios', estado.materia !== '' ? 'Las observaciones no tienen materia: quita la materia para verlas.' : 'No hay observaciones con esos filtros.'); return; }
            var cuentas = contarPor(filas, function (e) { return anioDe(e[1]); });
            var barras = Object.keys(cuentas).map(Number).sort(function (a, b) { return a - b; }).map(function (a) {
                return { etiqueta: String(a), valor: cuentas[a], ayuda: a + ': ' + num(cuentas[a]) + ' observaciones', color: a === estado.anio ? COLOR.primario : COLOR.claro };
            });
            var pico = barras.reduce(function (p, b) { return b.valor > p.valor ? b : p; }, barras[0]);
            var ultimo = barras[barras.length - 1];
            var partes = ['Máximo en ', { b: pico.etiqueta + ' (' + num(pico.valor) + ')' }];
            if (ultimo !== pico) {
                var cambio = Math.round(100 * (ultimo.valor - pico.valor) / pico.valor);
                partes.push(' · ' + ultimo.etiqueta + ': ', { b: num(ultimo.valor) + ' (' + (cambio > 0 ? '+' : '') + cambio + ' %)' });
            }
            lectura('l-obsanios', partes);
            nuevo('ColumnChart', 'g-obsanios').draw(tablaBarras('Año', barras), opciones({
                vAxis: ejeConAire(barras),
                chartArea: { left: 8, right: 8, top: 28, bottom: 36 }
            }));
        }

        function elemento(tag, clase, texto) {
            var e = document.createElement(tag);
            if (clase) { e.className = clase; }
            if (texto !== undefined) { e.textContent = texto; }
            return e;
        }

        // 5. Cumplimiento: % de docentes activos con al menos una planeación (ignora docente y tipo).
        function graficaCumplimiento() {
            if (ocultoPorTipo(0, 'g-cumpl', 'l-cumpl')) { return; }
            var filas = filtrar({ anio: true, rango: true, materia: true }).filter(function (e) { return e[0] === 0; });
            var conPlaneacion = {};
            filas.forEach(function (e) { conPlaneacion[DOCENTES[e[2]]] = true; });
            var total = ACTIVOS.length;
            var sin = ACTIVOS.filter(function (n) { return !conPlaneacion[n]; });
            var cumplen = total - sin.length;
            var porcentaje = total ? Math.round(100 * cumplen / total) : 0;
            var cont = $('g-cumpl');
            while (cont.firstChild) { cont.removeChild(cont.firstChild); }
            if (!total) { aviso('g-cumpl', 'l-cumpl', 'No hay docentes activos registrados.'); return; }
            lectura('l-cumpl', [{ b: num(cumplen) + ' de ' + num(total) + ' docentes' }, ' tienen al menos una planeación']);

            var caja = elemento('div', 'gr-cumpl');
            caja.appendChild(elemento('div', 'gr-cumpl-num', porcentaje + ' %'));
            var pista = elemento('div', 'gr-pista');
            pista.setAttribute('role', 'img');
            pista.setAttribute('aria-label', porcentaje + ' por ciento de los docentes');
            var relleno = elemento('div', 'gr-relleno');
            relleno.style.width = porcentaje + '%';
            pista.appendChild(relleno);
            caja.appendChild(pista);
            if (sin.length) {
                caja.appendChild(elemento('p', 'gr-sin-titulo', 'Sin planeaciones (' + num(sin.length) + '):'));
                var lista = elemento('ul', 'gr-sin');
                sin.slice(0, 8).forEach(function (n) { lista.appendChild(elemento('li', '', n)); });
                if (sin.length > 8) { lista.appendChild(elemento('li', '', 'y ' + num(sin.length - 8) + ' más')); }
                caja.appendChild(lista);
            } else {
                caja.appendChild(elemento('p', 'gr-todos', 'Todos los docentes tienen planeaciones.'));
            }
            cont.appendChild(caja);
        }

        // ---- Cifras y dibujo general ----------------------------------------

        function actualizarCifras(filas) {
            var docs = {};
            filas.forEach(function (e) { docs[e[2]] = true; });
            $('kpi-plan').textContent = num(distintos(filas, 0));
            $('kpi-obs').textContent = num(distintos(filas, 1));
            $('kpi-doc').textContent = num(Object.keys(docs).length);
        }

        // Si una gráfica falla, las demás se siguen dibujando.
        function dibujar(id, fn) {
            try { fn(); } catch (e) {
                var el = $(id);
                el.textContent = 'No se pudo dibujar esta gráfica.';
                if (window.console) { console.error(e); }
            }
        }

        function render() {
            marcarFiltros();
            var filas = filtrar({ anio: true, rango: true, docente: true, materia: true, tipo: true });
            actualizarCifras(filas);
            var hayDatos = filas.length > 0;
            mostrar('gr-empty', !hayDatos, 'block');
            mostrar('gr-graficas', hayDatos, 'grid');
            if (!hayDatos) { return; }
            dibujar('g-meses', graficaMeses);
            dibujar('g-top-plan', function () { graficaTop(0, 'g-top-plan', 'l-top-plan', 'planeaciones'); });
            dibujar('g-top-obs', function () { graficaTop(1, 'g-top-obs', 'l-top-obs', 'observaciones'); });
            dibujar('g-obsanios', graficaObsAnios);
            dibujar('g-cumpl', graficaCumplimiento);
        }

        function iniciarAyudas() {
            var botones = Array.prototype.slice.call(document.querySelectorAll('#gr-wrap .gr-info'));
            function cerrar(b) {
                b.setAttribute('aria-expanded', 'false');
                $(b.getAttribute('aria-controls')).hidden = true;
            }
            botones.forEach(function (b) {
                b.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var abierta = b.getAttribute('aria-expanded') === 'true';
                    botones.forEach(cerrar);
                    if (!abierta) {
                        b.setAttribute('aria-expanded', 'true');
                        $(b.getAttribute('aria-controls')).hidden = false;
                    }
                });
            });
            document.addEventListener('click', function (e) {
                if (!e.target.closest || !e.target.closest('.gr-pop')) { botones.forEach(cerrar); }
            });
            Array.prototype.forEach.call(document.querySelectorAll('#gr-wrap .gr-pop-cerrar'), function (x) {
                x.addEventListener('click', function () {
                    var pop = x.closest('.gr-pop');
                    botones.forEach(function (b) {
                        if (b.getAttribute('aria-controls') === pop.id) { cerrar(b); b.focus(); }
                    });
                });
            });
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') { return; }
                botones.forEach(function (b) {
                    if (b.getAttribute('aria-expanded') === 'true') { cerrar(b); b.focus(); }
                });
            });
        }

        function iniciar() {
            iniciarAyudas();
            if (!EVENTOS.length) {
                mostrar('gr-graficas', false);
                mostrar('gr-empty', true, 'block');
                return;
            }
            iniciarFiltros();
            render();
            window.addEventListener('resize', function () {
                clearTimeout(redimensionar);
                redimensionar = setTimeout(render, 200);
            });
        }

        google.charts.load('current', { packages: ['corechart'], language: 'es' });
        google.charts.setOnLoadCallback(iniciar);
    })();
    </script>
    <?php
}
