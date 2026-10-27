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

// PRIVACIDAD: este dashboard trabaja con datos de menores y de sus familias (religión,
// ocupación, parentesco, datos de contacto). El acceso a esta acción debe limitarse a
// directivos y pastoral (Permisos de acción). Al navegador NO llegan nombres, correos,
// teléfonos, direcciones ni IDs reales: estudiantes, familias y acudientes viajan como claves
// anónimas secuenciales, y las categorías con pocas personas se agrupan en "Otras".

use Modules\Graficas\GraficasGateway;

require_once __DIR__ . '/src/GraficasGateway.php';

// Las categorías (religión, parentesco, ocupación) con menos personas que esto se agrupan en "Otras".
$minimoCategoria = 5;

if (isActionAccessible($guid, $connection2, '/modules/Graficas/familias.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add('Gráficas de familias');

    $gateway = $container->get(GraficasGateway::class);
    $filas = $gateway->queryGraficasFamilias();

    // Diccionarios: cada texto viaja una sola vez; cada fila usa su posición.
    $diccionarios = ['anio' => [], 'grado' => [], 'genero' => [], 'religion' => [], 'parentesco' => [], 'profesion' => []];
    $indice = function ($campo, $valor) use (&$diccionarios) {
        if (!isset($diccionarios[$campo][$valor])) { $diccionarios[$campo][$valor] = count($diccionarios[$campo]); }
        return $diccionarios[$campo][$valor];
    };
    // Claves anónimas secuenciales (en orden de aparición): los IDs reales no salen del servidor.
    $claves = ['est' => [], 'fam' => [], 'acu' => []];
    $clave = function ($tipo, $id) use (&$claves) {
        if ($id === null) { return -1; }
        if (!isset($claves[$tipo][$id])) { $claves[$tipo][$id] = count($claves[$tipo]); }
        return $claves[$tipo][$id];
    };
    $anioActual = '';
    $ordenGrado = [];
    $datos = [];
    foreach ($filas as $f) {
        if ($f['estadoAnio'] === 'Current') { $anioActual = $f['anio']; }
        $ordenGrado[$f['grado']] = (int) $f['ordenGrado'];
        $datos[] = [
            $indice('anio', $f['anio']), $indice('grado', $f['grado']), $indice('genero', $f['genero']),
            $indice('religion', $f['religion']), $indice('parentesco', $f['parentesco']), $indice('profesion', $f['profesion']),
            (int) $f['hijos'],
            $f['tieneCorreo'] === null ? -1 : (int) $f['tieneCorreo'],
            $f['tieneTelefono'] === null ? -1 : (int) $f['tieneTelefono'],
            $f['tieneDireccion'] === null ? -1 : (int) $f['tieneDireccion'],
            $clave('est', $f['idEstudiante']), $clave('fam', $f['idFamilia']), $clave('acu', $f['idAcudiente']),
        ];
    }
    // Grados en orden numérico (sequenceNumber) para los gráficos y la lista.
    $listaGrados = array_keys($diccionarios['grado']);
    $ordenDeGrados = [];
    foreach ($listaGrados as $i => $g) { $ordenDeGrados[$i] = $ordenGrado[$g] ?? 0; }
    $listas = [];
    foreach ($diccionarios as $campo => $mapa) { $listas[$campo] = array_keys($mapa); }
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
        /* Extras de familias */
        .gr-vacio { padding: 36px 12px; text-align: center; color: #6b7280; font-size: .95em; }
        .gr-nota { margin: 6px 0 4px; font-size: .84em; color: #6b7280; }
        @media (min-width: 901px) { .gr-card.gr-w2 { grid-column: span 1; } }
    </style>

    <div class="gr-wrap" id="gr-wrap">
        <h2>Gráficas de familias</h2>

        <div class="gr-filtros" role="group" aria-label="Filtros">
            <div class="gr-campo"><span>Año escolar</span><div class="gr-seg" id="f-anio"></div></div>
            <div class="gr-campo"><span>Grado</span><select class="gr-select" id="f-grado"><option value="">Todos los grados</option></select></div>
            <div class="gr-campo"><span>Género del estudiante</span><div class="gr-seg" id="f-genero"></div></div>
            <div class="gr-campo"><span>Religión</span><select class="gr-select" id="f-religion"><option value="">Todas</option></select></div>
            <div class="gr-campo"><span>Hijos en el colegio</span><div class="gr-seg" id="f-hijos"></div></div>
            <button type="button" class="gr-limpiar" id="f-limpiar">Quitar filtros</button>
        </div>

        <div class="gr-cifras">
            <div class="gr-cifra"><b id="kpi-est">–</b><span>estudiantes</span></div>
            <div class="gr-cifra"><b id="kpi-fam">–</b><span>familias</span></div>
            <div class="gr-cifra"><b id="kpi-prom">–</b><span>hijos por familia (promedio)</span></div>
            <div class="gr-cifra"><b id="kpi-acu">–</b><span>acudientes</span></div>
        </div>

        <div class="gr-empty" id="gr-empty">Sin datos para los filtros seleccionados</div>

        <div class="gr-grid" id="gr-graficas">
            <div class="gr-card">
                <div class="gr-card-head"><h3>¿Cuántos hijos tiene cada familia en el colegio?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-hijos"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-hijos" role="dialog" aria-label="¿Cuántos hijos tiene cada familia en el colegio?" hidden><div class="gr-pop-head"><span class="gr-pop-icono" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 11v6"/><circle cx="12" cy="7" r=".8" fill="currentColor"/></svg></span><span class="gr-pop-titulo">¿Qué muestra esta gráfica?</span><button type="button" class="gr-pop-cerrar" aria-label="Cerrar">&times;</button></div><div class="gr-pop-body"><p>Cuántas familias tienen 1, 2 o 3 o más hijos matriculados en el año elegido.</p><p class="gr-pop-dato">Se cuentan familias, no estudiantes.</p></div></div>
                <p class="gr-lectura" id="l-hijos"></p>
                <div class="gr-chart" id="g-hijos"></div>
            </div>
            <div class="gr-card">
                <div class="gr-card-head"><h3>¿Qué religión tienen los estudiantes?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-religion"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-religion" role="dialog" aria-label="¿Qué religión tienen los estudiantes?" hidden><div class="gr-pop-head"><span class="gr-pop-icono" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 11v6"/><circle cx="12" cy="7" r=".8" fill="currentColor"/></svg></span><span class="gr-pop-titulo">¿Qué muestra esta gráfica?</span><button type="button" class="gr-pop-cerrar" aria-label="Cerrar">&times;</button></div><div class="gr-pop-body"><p>Proporción de estudiantes según la religión registrada en su ficha.</p><p class="gr-pop-dato">Las religiones con menos de 5 estudiantes se suman en «Otras» para no identificar a nadie.</p></div></div>
                <p class="gr-lectura" id="l-religion"></p>
                <div class="gr-chart" id="g-religion"></div>
            </div>
            <div class="gr-card">
                <div class="gr-card-head"><h3>¿Quiénes son los acudientes?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-parentesco"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-parentesco" role="dialog" aria-label="¿Quiénes son los acudientes?" hidden><div class="gr-pop-head"><span class="gr-pop-icono" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 11v6"/><circle cx="12" cy="7" r=".8" fill="currentColor"/></svg></span><span class="gr-pop-titulo">¿Qué muestra esta gráfica?</span><button type="button" class="gr-pop-cerrar" aria-label="Cerrar">&times;</button></div><div class="gr-pop-body"><p>Cuántos acudientes hay de cada parentesco (madre, padre, abuela…).</p><p class="gr-pop-dato">Los parentescos con menos de 5 personas se suman en «Otras». También dice cuántos estudiantes no tienen acudiente registrado.</p></div></div>
                <p class="gr-lectura" id="l-parentesco"></p>
                <div class="gr-chart" id="g-parentesco"></div>
            </div>
            <div class="gr-card">
                <div class="gr-card-head"><h3>¿En qué trabajan los acudientes?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-ocupacion"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-ocupacion" role="dialog" aria-label="¿En qué trabajan los acudientes?" hidden><div class="gr-pop-head"><span class="gr-pop-icono" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 11v6"/><circle cx="12" cy="7" r=".8" fill="currentColor"/></svg></span><span class="gr-pop-titulo">¿Qué muestra esta gráfica?</span><button type="button" class="gr-pop-cerrar" aria-label="Cerrar">&times;</button></div><div class="gr-pop-body"><p>Las 10 ocupaciones más frecuentes entre los acudientes. El resto se suma en «Otras».</p><p class="gr-pop-dato">Los acudientes sin ocupación registrada no se grafican; su número aparece debajo de la frase.</p></div></div>
                <p class="gr-lectura" id="l-ocupacion"></p>
                <p class="gr-nota" id="n-ocupacion"></p>
                <div class="gr-chart" id="g-ocupacion"></div>
            </div>
            <div class="gr-card gr-wide">
                <div class="gr-card-head"><h3>¿A cuántos acudientes se les puede contactar?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-contacto"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-contacto" role="dialog" aria-label="¿A cuántos acudientes se les puede contactar?" hidden><div class="gr-pop-head"><span class="gr-pop-icono" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 11v6"/><circle cx="12" cy="7" r=".8" fill="currentColor"/></svg></span><span class="gr-pop-titulo">¿Qué muestra esta gráfica?</span><button type="button" class="gr-pop-cerrar" aria-label="Cerrar">&times;</button></div><div class="gr-pop-body"><p>Por cada grado, el porcentaje de acudientes que tienen <strong>correo</strong>, <strong>teléfono</strong> y <strong>dirección</strong> registrados en Gibbon.</p><p class="gr-pop-ej">Ejemplo: 90 % en correo = 9 de cada 10 acudientes tienen correo.</p><p class="gr-pop-dato">No se muestra ningún dato personal, solo si existe o no.</p></div></div>
                <p class="gr-lectura" id="l-contacto"></p>
                <div class="gr-chart" id="g-contacto"></div>
            </div>
        </div>

        <div class="gr-foot">Fuente: matrícula y familias registradas en Gibbon. Datos generados el <?php echo htmlspecialchars($generado, ENT_QUOTES, 'UTF-8'); ?>. En el año actual solo se cuentan estudiantes con estado «Full». Sin nombres ni datos personales.</div>
    </div>

    <script src="https://www.gstatic.com/charts/loader.js"></script>
    <script>
    (function () {
        'use strict';

        // Fila = [año, grado, género, religión, parentesco, ocupación (posiciones en LISTAS),
        //         hijos, tiene correo, tiene teléfono, tiene dirección (1/0, -1 = sin acudiente),
        //         clave estudiante, clave familia, clave acudiente (-1 = ninguna)]
        var FILAS = <?php echo json_encode($datos, $jsonFlags); ?>;
        var LISTAS = <?php echo json_encode($listas, $jsonFlags); ?>;
        var ORDEN_GRADOS = <?php echo json_encode($ordenDeGrados, $jsonFlags); ?>;
        var ANIO_ACTUAL = <?php echo json_encode($anioActual, $jsonFlags); ?>;
        var MINIMO_CATEGORIA = <?php echo (int) $minimoCategoria; ?>;

        // Posiciones dentro de cada fila
        var F = { ANIO: 0, GRADO: 1, GENERO: 2, RELIGION: 3, PARENTESCO: 4, PROFESION: 5, HIJOS: 6, CORREO: 7, TELEFONO: 8, DIRECCION: 9, EST: 10, FAM: 11, ACU: 12 };

        // Paleta del proyecto (la misma de los reportes de Informes Escolares).
        var COLOR = { primario: '#3575EF', oscuro: '#1E3A8A', medio: '#2B60CF', claro: '#9DB8F5', pale: '#DCE7FD', gris: '#9CA3AF' };
        var ALTO = 320;

        // Vacío = todos. Las listas (grado, religión) guardan la posición en LISTAS; hijos guarda 1, 2 o 3 (3 o más).
        var estado = { anio: '', grado: '', genero: '', religion: '', hijos: '' };
        var redimensionar = null;

        function $(id) { return document.getElementById(id); }
        function mostrar(id, visible, tipo) { $(id).style.display = visible ? (tipo || '') : 'none'; }
        function num(n) { return Number(n).toLocaleString('es-CO'); }
        function pct(n, total) { return total ? Math.round(100 * n / total) + ' %' : '0 %'; }
        function texto(lista, i) { return LISTAS[lista][i]; }

        function elemento(tag, clase, txt) {
            var e = document.createElement(tag);
            if (clase) { e.className = clase; }
            if (txt !== undefined) { e.textContent = txt; }
            return e;
        }

        // Escribe una frase con partes en negrita sin usar innerHTML (todo se escapa).
        function lectura(id, partes) {
            var el = $(id);
            while (el.firstChild) { el.removeChild(el.firstChild); }
            partes.forEach(function (p) {
                if (typeof p === 'string') { el.appendChild(document.createTextNode(p)); return; }
                el.appendChild(elemento('strong', '', p.b));
            });
        }

        function aviso(idGrafica, idLectura, msg) {
            var el = $(idGrafica);
            while (el.firstChild) { el.removeChild(el.firstChild); }
            el.appendChild(elemento('div', 'gr-vacio', msg));
            if (idLectura) { lectura(idLectura, []); }
        }

        // ---- Filtros ---------------------------------------------------------

        function pasa(f) {
            if (estado.anio !== '' && f[F.ANIO] !== estado.anio) { return false; }
            if (estado.grado !== '' && f[F.GRADO] !== estado.grado) { return false; }
            if (estado.genero !== '' && texto('genero', f[F.GENERO]) !== estado.genero) { return false; }
            if (estado.religion !== '' && f[F.RELIGION] !== estado.religion) { return false; }
            if (estado.hijos !== '') {
                var h = f[F.HIJOS];
                if (estado.hijos === 3 ? h < 3 : h !== estado.hijos) { return false; }
            }
            return true;
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
                var b = elemento('button', '', o.etiqueta);
                b.type = 'button';
                b.setAttribute('data-valor', String(o.valor));
                b.addEventListener('click', function () { estado[clave] = o.valor; render(); });
                cont.appendChild(b);
            });
        }

        function iniciarFiltros() {
            var anios = LISTAS.anio;
            var actual = anios.indexOf(String(ANIO_ACTUAL));
            estado.anio = actual !== -1 ? actual : anios.length - 1;
            segmentado('f-anio', anios.map(function (a, i) { return { valor: i, etiqueta: a }; }), 'anio');
            segmentado('f-genero', [{ valor: '', etiqueta: 'Todos' }, { valor: 'Niñas', etiqueta: 'Niñas' }, { valor: 'Niños', etiqueta: 'Niños' }], 'genero');
            segmentado('f-hijos', [{ valor: '', etiqueta: 'Todos' }, { valor: 1, etiqueta: '1' }, { valor: 2, etiqueta: '2' }, { valor: 3, etiqueta: '3 o más' }], 'hijos');

            var selGrado = $('f-grado');
            LISTAS.grado.map(function (g, i) { return { g: g, i: i, o: ORDEN_GRADOS[i] }; })
                .sort(function (a, b) { return a.o - b.o; })
                .forEach(function (x) { opcion(selGrado, x.i, x.g); });
            selGrado.addEventListener('change', function () { estado.grado = this.value === '' ? '' : Number(this.value); render(); });

            var selReligion = $('f-religion');
            LISTAS.religion.map(function (r, i) { return { r: r, i: i }; })
                .sort(function (a, b) { return a.r.localeCompare(b.r, 'es'); })
                .forEach(function (x) { opcion(selReligion, x.i, x.r); });
            selReligion.addEventListener('change', function () { estado.religion = this.value === '' ? '' : Number(this.value); render(); });

            $('f-limpiar').addEventListener('click', function () {
                estado.grado = ''; estado.genero = ''; estado.religion = ''; estado.hijos = '';
                selGrado.value = ''; selReligion.value = '';
                render();
            });
        }

        function marcarFiltros() {
            [['f-anio', 'anio'], ['f-genero', 'genero'], ['f-hijos', 'hijos']].forEach(function (par) {
                Array.prototype.forEach.call($(par[0]).querySelectorAll('button'), function (b) {
                    b.setAttribute('aria-pressed', b.getAttribute('data-valor') === String(estado[par[1]]) ? 'true' : 'false');
                });
            });
            $('f-grado').classList.toggle('gr-activo', estado.grado !== '');
            $('f-religion').classList.toggle('gr-activo', estado.religion !== '');
            mostrar('f-limpiar', !!(estado.grado !== '' || estado.genero || estado.religion !== '' || estado.hijos !== ''), 'inline-block');
        }

        // ---- Conteos (siempre de claves distintas, no de filas) ----------------

        // Claves distintas de una columna (ignora -1) agrupadas por una categoría.
        function distintosPor(filas, columnaClave, categoria) {
            var vistos = {}, cuentas = {};
            filas.forEach(function (f) {
                var k = f[columnaClave];
                if (k === -1) { return; }
                var c = categoria(f);
                var set = vistos[c] || (vistos[c] = {});
                if (set[k]) { return; }
                set[k] = true;
                cuentas[c] = (cuentas[c] || 0) + 1;
            });
            return cuentas;
        }

        function distintos(filas, columnaClave) {
            var set = {}, n = 0;
            filas.forEach(function (f) { var k = f[columnaClave]; if (k !== -1 && !set[k]) { set[k] = true; n++; } });
            return n;
        }

        // Agrupa en "Otras" lo que tenga menos de MINIMO_CATEGORIA personas (y, si se pide, lo que pase del top).
        // cuentas: { posición: n }. Devuelve [{ nombre, valor }] ordenado de mayor a menor, "Otras" al final.
        function conOtras(cuentas, lista, maximo) {
            var items = Object.keys(cuentas).map(function (k) { return { nombre: lista[k], valor: cuentas[k] }; })
                .sort(function (a, b) { return b.valor - a.valor || a.nombre.localeCompare(b.nombre, 'es'); });
            var salida = [], otras = 0;
            items.forEach(function (it) {
                if (it.valor >= MINIMO_CATEGORIA && (!maximo || salida.length < maximo)) { salida.push(it); } else { otras += it.valor; }
            });
            if (otras > 0) { salida.push({ nombre: 'Otras', valor: otras, otras: true }); }
            return salida;
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
        function tablaBarras(titulo, nombreValor, filas) {
            var t = new google.visualization.DataTable();
            t.addColumn('string', titulo);
            t.addColumn('number', nombreValor);
            t.addColumn({ type: 'string', role: 'annotation' });
            t.addColumn({ type: 'string', role: 'tooltip' });
            t.addColumn({ type: 'string', role: 'style' });
            filas.forEach(function (f) { t.addRow([f.etiqueta, f.valor, num(f.valor), f.ayuda, 'color: ' + (f.color || COLOR.primario)]); });
            return t;
        }

        function maximo(barras) {
            return barras.reduce(function (m, b) { return b.valor > m ? b.valor : m; }, 0);
        }

        function ejeConAire(barras) {
            var max = 0;
            barras.forEach(function (b) { if (b.valor > max) { max = b.valor; } });
            return { textPosition: 'none', gridlines: { color: 'transparent' }, viewWindow: { min: 0, max: Math.max(1, max * 1.15) } };
        }

        // 1. Hijos por familia (1, 2, 3 o más): número de familias de cada tipo.
        function graficaHijos(filas) {
            var porFamilia = {};
            filas.forEach(function (f) { if (f[F.FAM] !== -1) { porFamilia[f[F.FAM]] = f[F.HIJOS]; } });
            var grupos = [{ etiqueta: '1 hijo', valor: 0 }, { etiqueta: '2 hijos', valor: 0 }, { etiqueta: '3 o más hijos', valor: 0 }];
            Object.keys(porFamilia).forEach(function (k) {
                var h = porFamilia[k];
                if (h >= 1) { grupos[h >= 3 ? 2 : h - 1].valor++; }
            });
            var total = grupos[0].valor + grupos[1].valor + grupos[2].valor;
            if (!total) { aviso('g-hijos', 'l-hijos', 'No hay familias registradas con esos filtros.'); return; }
            grupos.forEach(function (g) { g.ayuda = g.etiqueta + ': ' + num(g.valor) + ' familias (' + pct(g.valor, total) + ')'; g.color = g === grupos[0] ? COLOR.claro : (g === grupos[1] ? COLOR.primario : COLOR.oscuro); });
            lectura('l-hijos', [{ b: pct(grupos[0].valor, total) }, ' de las familias tienen solo 1 hijo en el colegio']);
            nuevo('ColumnChart', 'g-hijos').draw(tablaBarras('Hijos', 'Familias', grupos), opciones({
                vAxis: ejeConAire(grupos),
                chartArea: { left: 8, right: 8, top: 28, bottom: 36 }
            }));
        }

        // 2. Acudientes por parentesco (barras horizontales; las categorías pequeñas van en "Otras").
        function graficaParentesco(filas) {
            var cuentas = distintosPor(filas, F.ACU, function (f) { return f[F.PARENTESCO]; });
            var barras = conOtras(cuentas, LISTAS.parentesco);
            var sinAcudiente = distintos(filas.filter(function (f) { return f[F.ACU] === -1; }), F.EST);
            if (!barras.length) { aviso('g-parentesco', 'l-parentesco', 'No hay acudientes registrados con esos filtros.'); return; }
            var total = barras.reduce(function (s, b) { return s + b.valor; }, 0);
            var partes = [{ b: barras[0].nombre }, ' es el parentesco más frecuente (', { b: pct(barras[0].valor, total) }, ')'];
            if (sinAcudiente) { partes.push(' · ' + num(sinAcudiente) + ' estudiantes sin acudiente registrado'); }
            lectura('l-parentesco', partes);
            var filasTabla = barras.map(function (b) {
                return { etiqueta: b.nombre, valor: b.valor, ayuda: b.nombre + ': ' + num(b.valor) + ' acudientes', color: b.otras ? COLOR.gris : COLOR.primario };
            });
            nuevo('BarChart', 'g-parentesco').draw(tablaBarras('Parentesco', 'Acudientes', filasTabla), opciones({
                height: Math.max(ALTO, 34 * filasTabla.length + 40),
                hAxis: { textPosition: 'none', gridlines: { color: 'transparent' }, viewWindow: { min: 0, max: Math.max(1, maximo(barras) * 1.15) } },
                vAxis: { gridlines: { color: 'transparent' }, textStyle: { color: '#1F2937', fontSize: 13 } },
                chartArea: { left: 130, right: 50, top: 8, bottom: 8 },
                bar: { groupWidth: '65%' }
            }));
        }

        // 3. Datos de contacto por grado: % de acudientes que tienen correo, teléfono y dirección registrados.
        function graficaContacto(filas) {
            var porGrado = {}, totalAcu = {}, general = { c: {}, t: {}, d: {}, n: {} };
            var vistos = {};
            filas.forEach(function (f) {
                var a = f[F.ACU];
                if (a === -1) { return; }
                var clave = f[F.GRADO] + '|' + a;
                if (vistos[clave]) { return; }
                vistos[clave] = true;
                var g = porGrado[f[F.GRADO]] || (porGrado[f[F.GRADO]] = { n: 0, c: 0, t: 0, d: 0 });
                g.n++; g.c += f[F.CORREO] === 1 ? 1 : 0; g.t += f[F.TELEFONO] === 1 ? 1 : 0; g.d += f[F.DIRECCION] === 1 ? 1 : 0;
            });
            var grados = Object.keys(porGrado).map(Number).sort(function (a, b) { return ORDEN_GRADOS[a] - ORDEN_GRADOS[b]; });
            if (!grados.length) { aviso('g-contacto', 'l-contacto', 'No hay acudientes registrados con esos filtros.'); return; }
            var tot = { n: 0, c: 0, t: 0, d: 0 };
            var t = new google.visualization.DataTable();
            t.addColumn('string', 'Grado');
            ['Correo', 'Teléfono', 'Dirección'].forEach(function (n) { t.addColumn('number', n); });
            grados.forEach(function (gi) {
                var g = porGrado[gi];
                tot.n += g.n; tot.c += g.c; tot.t += g.t; tot.d += g.d;
                t.addRow([LISTAS.grado[gi], g.c / g.n, g.t / g.n, g.d / g.n]);
            });
            new google.visualization.NumberFormat({ pattern: '#%' }).format(t, 1);
            new google.visualization.NumberFormat({ pattern: '#%' }).format(t, 2);
            new google.visualization.NumberFormat({ pattern: '#%' }).format(t, 3);
            lectura('l-contacto', ['Tienen correo ', { b: pct(tot.c, tot.n) }, ' · teléfono ', { b: pct(tot.t, tot.n) }, ' · dirección ', { b: pct(tot.d, tot.n) }]);
            nuevo('ColumnChart', 'g-contacto').draw(t, opciones({
                legend: { position: 'bottom', textStyle: { color: '#4B5563' } },
                colors: [COLOR.primario, COLOR.oscuro, COLOR.claro],
                bar: { groupWidth: '80%' },
                vAxis: { format: '#%', minValue: 0, maxValue: 1, viewWindow: { min: 0, max: 1 } },
                chartArea: { left: 48, right: 8, top: 16, bottom: 64 }
            }));
        }

        // 4. Religión o confesión (por estudiantes; las menores de 5 en "Otras").
        function graficaReligion(filas) {
            var cuentas = distintosPor(filas, F.EST, function (f) { return f[F.RELIGION]; });
            var items = conOtras(cuentas, LISTAS.religion);
            if (!items.length) { aviso('g-religion', 'l-religion', 'No hay estudiantes con esos filtros.'); return; }
            var total = items.reduce(function (s, b) { return s + b.valor; }, 0);
            var colores = [COLOR.primario, COLOR.oscuro, COLOR.claro, COLOR.medio, COLOR.pale];
            var t = new google.visualization.DataTable();
            t.addColumn('string', 'Religión');
            t.addColumn('number', 'Estudiantes');
            var slices = {};
            items.forEach(function (it, i) {
                slices[i] = { color: it.otras || it.nombre === 'Sin dato' ? COLOR.gris : colores[i % colores.length] };
                t.addRow([it.nombre + ': ' + num(it.valor), it.valor]);
            });
            lectura('l-religion', [{ b: items[0].nombre }, ' es la más frecuente (', { b: pct(items[0].valor, total) }, ' de los estudiantes)']);
            nuevo('PieChart', 'g-religion').draw(t, opciones({
                pieHole: 0.55, slices: slices, pieSliceText: 'percentage', pieSliceBorderColor: '#fff',
                pieSliceTextStyle: { color: '#fff', bold: true, fontSize: 13 },
                legend: { position: 'right', alignment: 'center', textStyle: { color: '#1F2937', fontSize: 13 } },
                chartArea: { left: 8, right: 8, top: 16, bottom: 16 }
            }));
        }

        // 5. Ocupación de los acudientes (Top 10; el resto en "Otras"; "Sin dato" solo como nota).
        function graficaOcupacion(filas) {
            var cuentas = distintosPor(filas, F.ACU, function (f) { return f[F.PROFESION]; });
            var sinDato = 0;
            Object.keys(cuentas).forEach(function (k) { if (LISTAS.profesion[k] === 'SIN DATO') { sinDato = cuentas[k]; delete cuentas[k]; } });
            var barras = conOtras(cuentas, LISTAS.profesion, 10);
            var nota = $('n-ocupacion');
            nota.textContent = sinDato ? num(sinDato) + ' acudientes sin ocupación registrada (no se grafican).' : '';
            if (!barras.length) { aviso('g-ocupacion', 'l-ocupacion', 'No hay ocupaciones registradas con esos filtros.'); return; }
            lectura('l-ocupacion', ['La ocupación más frecuente es ', { b: barras[0].nombre.toLowerCase() + ' (' + num(barras[0].valor) + ')' }]);
            var filasTabla = barras.map(function (b) {
                return { etiqueta: b.nombre, valor: b.valor, ayuda: b.nombre + ': ' + num(b.valor) + ' acudientes', color: b.otras ? COLOR.gris : COLOR.primario };
            });
            nuevo('BarChart', 'g-ocupacion').draw(tablaBarras('Ocupación', 'Acudientes', filasTabla), opciones({
                height: Math.max(ALTO, 30 * filasTabla.length + 40),
                hAxis: { textPosition: 'none', gridlines: { color: 'transparent' }, viewWindow: { min: 0, max: Math.max(1, maximo(barras) * 1.15) } },
                vAxis: { gridlines: { color: 'transparent' }, textStyle: { color: '#1F2937' } },
                chartArea: { left: 170, right: 50, top: 8, bottom: 8 },
                bar: { groupWidth: '70%' }
            }));
        }

        // ---- Cifras y dibujo general ----------------------------------------

        function actualizarCifras(filas) {
            var est = distintos(filas, F.EST), acu = distintos(filas, F.ACU);
            var hijosPorFamilia = {};
            filas.forEach(function (f) { if (f[F.FAM] !== -1) { hijosPorFamilia[f[F.FAM]] = f[F.HIJOS]; } });
            var fams = Object.keys(hijosPorFamilia);
            var suma = 0;
            fams.forEach(function (k) { suma += hijosPorFamilia[k]; });
            $('kpi-est').textContent = num(est);
            $('kpi-fam').textContent = num(fams.length);
            $('kpi-prom').textContent = fams.length ? (suma / fams.length).toFixed(1).replace('.', ',') : '–';
            $('kpi-acu').textContent = num(acu);
        }

        // Si una gráfica falla, las demás se siguen dibujando.
        function dibujar(id, fn) {
            try { fn(); } catch (e) {
                $(id).textContent = 'No se pudo dibujar esta gráfica.';
                if (window.console) { console.error(e); }
            }
        }

        function render() {
            marcarFiltros();
            var filas = FILAS.filter(pasa);
            actualizarCifras(filas);
            var hayDatos = filas.length > 0;
            mostrar('gr-empty', !hayDatos, 'block');
            mostrar('gr-graficas', hayDatos, 'grid');
            if (!hayDatos) { return; }
            dibujar('g-hijos', function () { graficaHijos(filas); });
            dibujar('g-parentesco', function () { graficaParentesco(filas); });
            dibujar('g-contacto', function () { graficaContacto(filas); });
            dibujar('g-religion', function () { graficaReligion(filas); });
            dibujar('g-ocupacion', function () { graficaOcupacion(filas); });
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
            if (!FILAS.length) {
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
