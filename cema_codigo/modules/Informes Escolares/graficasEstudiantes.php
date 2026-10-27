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

use Modules\InformesEscolares\InformesEscolaresGateway;

require_once __DIR__ . '/src/InformesEscolaresGateway.php';

// SUPUESTO (mío, no del colegio): edad esperada por número de grado. Depende de la fecha de
// corte del colegio (la edad se mide al primer día del año escolar), así que ajústala aquí.
// "En edad" = esperada o esperada + 1; "Adelantado" = menor; "Extraedad" = esperada + MARGEN o más.
$edadEsperadaPorGrado = [1 => 6, 2 => 7, 3 => 8, 4 => 9, 5 => 10, 6 => 11, 7 => 12, 8 => 13, 9 => 14, 10 => 15, 11 => 16];
$margenExtraedad = 2;

// Columna de gibbonPerson con la nacionalidad. No está confirmada en el esquema del
// repositorio: si no existe en la BD, el gateway devuelve "Sin dato" en lugar de fallar.
$columnaNacionalidad = 'nationality';

// Permiso que se valida. Otra página (modules/Graficas/estudiantes.php) puede definir
// $accionGraficas antes de incluir este archivo para usar su propia acción.
$accionGraficas = $accionGraficas ?? '/modules/Informes Escolares/graficasEstudiantes.php';

if (isActionAccessible($guid, $connection2, $accionGraficas) == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add('Gráficas de estudiantes');

    $gateway = $container->get(InformesEscolaresGateway::class);
    $filas = $gateway->queryGraficasEstudiantes($columnaNacionalidad);

    $anioActual = '';
    $datos = [];
    foreach ($filas as $fila) {
        if ($fila['estadoAnio'] === 'Current') {
            $anioActual = $fila['anio'];
        }
        // Solo los campos que usan las gráficas: sin nombres ni identificadores.
        $datos[] = [
            'anio' => $fila['anio'],
            'grado' => $fila['grado'],
            'orden' => (int) $fila['ordenGrado'],
            'grupo' => $fila['grupo'] !== '' ? $fila['grupo'] : 'Sin dato',
            'genero' => $fila['genero'],
            'edad' => $fila['edad'] === null ? null : (int) $fila['edad'],
            'antiguedad' => (int) $fila['antiguedad'],
        ];
    }
    $margenTexto = (int) $margenExtraedad;
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
        .gr-pop { position: absolute; top: 46px; right: 14px; z-index: 5; width: min(360px, calc(100% - 28px)); padding: 16px 18px; background: #fff; border: 1px solid #E3EAF8; border-radius: 10px; box-shadow: 0 12px 32px rgba(30,58,138,.16); font-size: 1em; line-height: 1.55; color: #1F2937; }
        .gr-pop[hidden] { display: none; }
        .gr-pop p { margin: 0 0 8px; }
        .gr-pop p:last-child { margin-bottom: 0; }
        .gr-pop strong { color: #1E3A8A; }
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
    </style>

    <div class="gr-wrap" id="gr-wrap">
        <h2>Gráficas de estudiantes</h2>

        <div class="gr-filtros" role="group" aria-label="Filtros">
            <div class="gr-campo"><span>Año escolar</span><div class="gr-seg" id="f-anio"></div></div>
            <div class="gr-campo"><span>Grado</span><select class="gr-select" id="f-grado"><option value="">Todos los grados</option></select></div>
            <div class="gr-campo"><span>Género</span><div class="gr-seg" id="f-genero"></div></div>
            <button type="button" class="gr-limpiar" id="f-limpiar">Quitar filtros</button>
        </div>

        <div class="gr-cifras">
            <div class="gr-cifra"><b id="kpi-total">–</b><span>estudiantes</span></div>
            <div class="gr-cifra"><b id="kpi-ninas">–</b><span id="kpi-ninas-sub">niñas</span></div>
            <div class="gr-cifra"><b id="kpi-ninos">–</b><span id="kpi-ninos-sub">niños</span></div>
            <div class="gr-cifra"><b id="kpi-edad">–</b><span>edad promedio (años)</span></div>
        </div>

        <div class="gr-empty" id="gr-empty">Sin datos para los filtros seleccionados</div>

        <div class="gr-grid" id="gr-graficas">
            <div class="gr-card">
                <div class="gr-card-head"><h3>¿Cuántos estudiantes hay en cada grado?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-grado"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-grado" role="dialog" aria-label="¿Cuántos estudiantes hay en cada grado?" hidden><p>Cuántos estudiantes hay en cada grado.</p><p>Pasa el mouse sobre una barra para ver cuántas son niñas y cuántos niños.</p></div>
                <p class="gr-lectura" id="l-grado"></p>
                <div class="gr-chart" id="g-grado"></div>
            </div>
            <div class="gr-card">
                <div class="gr-card-head"><h3>¿Cuántas niñas y cuántos niños?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-genero"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-genero" role="dialog" aria-label="¿Cuántas niñas y cuántos niños?" hidden><p>Cuántas niñas y cuántos niños hay.</p><p><strong>Sin dato:</strong> estudiantes sin género registrado.</p></div>
                <p class="gr-lectura" id="l-genero"></p>
                <div class="gr-chart" id="g-genero"></div>
            </div>
            <div class="gr-card">
                <div class="gr-card-head"><h3>¿Cuántos están por encima de la edad de su grado?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-extraedad"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-extraedad" role="dialog" aria-label="¿Cuántos están por encima de la edad de su grado?" hidden><p>Un estudiante está en <strong>extraedad</strong> cuando tiene <?php echo $margenTexto; ?> años o más de lo normal para su grado.</p><p><strong>Ejemplo:</strong> en Primero lo normal es tener 6 años. Con 8 años o más, está en extraedad.</p><p>Lo normal: Primero 6 años, Segundo 7… hasta Once 16.</p></div>
                <p class="gr-lectura" id="l-extraedad"></p>
                <div class="gr-chart" id="g-extraedad"></div>
            </div>
            <div class="gr-card">
                <div class="gr-card-head"><h3>¿Cuántos estudiantes son nuevos?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-antiguedad"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-antiguedad" role="dialog" aria-label="¿Cuántos estudiantes son nuevos?" hidden><p>Cuántos estudiantes llegaron este año y cuántos ya llevan 2 o más años en el colegio.</p><p>Solo cuenta los años registrados en Gibbon.</p></div>
                <p class="gr-lectura" id="l-antiguedad"></p>
                <div class="gr-chart" id="g-antiguedad"></div>
            </div>
            <div class="gr-card gr-wide">
                <div class="gr-card-head"><h3>¿Cómo ha cambiado la matrícula con los años?</h3><button type="button" class="gr-info" aria-label="Qué muestra esta gráfica" aria-expanded="false" aria-controls="i-evolucion"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg></button></div>
                <div class="gr-pop" id="i-evolucion" role="dialog" aria-label="¿Cómo ha cambiado la matrícula con los años?" hidden><p>Total de estudiantes en cada año.</p><p>La barra azul fuerte es el año que estás viendo.</p></div>
                <p class="gr-lectura" id="l-evolucion"></p>
                <div class="gr-chart" id="g-evolucion"></div>
            </div>
        </div>

        <div class="gr-foot">Fuente: matrícula registrada en Gibbon. Datos generados el <?php echo htmlspecialchars($generado, ENT_QUOTES, 'UTF-8'); ?>. En el año actual solo se cuentan estudiantes con estado «Full».</div>
    </div>

    <script src="https://www.gstatic.com/charts/loader.js"></script>
    <script>
    (function () {
        'use strict';

        var DATOS = <?php echo json_encode($datos, $jsonFlags); ?>;
        var ANIO_ACTUAL = <?php echo json_encode($anioActual, $jsonFlags); ?>;
        var EDAD_ESPERADA_POR_GRADO = <?php echo json_encode($edadEsperadaPorGrado, $jsonFlags); ?>;
        var MARGEN_EXTRAEDAD = <?php echo (int) $margenExtraedad; ?>;
        
        // Paleta del proyecto (la misma de los reportes de Informes Escolares).
        var COLOR = { primario: '#3575EF', oscuro: '#1E3A8A', claro: '#9DB8F5', gris: '#9CA3AF', alerta: '#D97706' };
        var COLOR_GENERO = { 'Niñas': COLOR.primario, 'Niños': COLOR.oscuro, 'Sin dato': COLOR.gris };

        // Columnas del DataTable
        var C = { ANIO: 0, GRADO: 1, ORDEN: 2, GRUPO: 3, GENERO: 4, EDAD: 5, LOCALIDAD: 6, NACIONALIDAD: 7, ANTIGUEDAD: 8 };
        var ALTO = 300;
        var ALTO_FILA2 = 380; // extraedad y estudiantes nuevos: mismo alto para que las tarjetas queden parejas

        var data, redimensionar = null;
        // Vacío = todos: no hace falta elegir nada para ver los datos.
        var estado = { anio: '', grado: '', genero: '' };

        function $(id) { return document.getElementById(id); }
        function mostrar(id, visible, tipo) { $(id).style.display = visible ? (tipo || '') : 'none'; }
        function num(n) { return Number(n).toLocaleString('es'); }
        function pct(n, total) { return total ? Math.round(100 * n / total) + ' %' : '0 %'; }

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

        function construirTabla() {
            var t = new google.visualization.DataTable();
            t.addColumn('string', 'Año');
            t.addColumn('string', 'Grado');
            t.addColumn('number', 'Orden');
            t.addColumn('string', 'Grupo');
            t.addColumn('string', 'Género');
            t.addColumn('number', 'Edad');
            t.addColumn('string', 'Localidad');
            t.addColumn('string', 'Nacionalidad');
            t.addColumn('number', 'Antigüedad');
            t.addRows(DATOS.map(function (r) {
                function txt(v) { return v === null || v === undefined || v === '' ? 'Sin dato' : String(v); }
                function n(v) { return v === null || v === undefined || v === '' || isNaN(Number(v)) ? null : Number(v); }
                return [txt(r.anio), txt(r.grado), n(r.orden), txt(r.grupo), txt(r.genero), n(r.edad), txt(r.localidad), txt(r.nacionalidad), n(r.antiguedad)];
            }));
            return t;
        }

        // ---- Filtros ---------------------------------------------------------

        function filasFiltradas(incluirAnio) {
            var f = [];
            if (incluirAnio) { f.push({ column: C.ANIO, value: estado.anio }); }
            if (estado.grado) { f.push({ column: C.GRADO, value: estado.grado }); }
            if (estado.genero) { f.push({ column: C.GENERO, value: estado.genero }); }
            if (f.length) { return data.getFilteredRows(f); }
            var todas = [];
            for (var i = 0; i < data.getNumberOfRows(); i++) { todas.push(i); }
            return todas;
        }

        function opcion(sel, valor, etiqueta) {
            var o = document.createElement('option');
            o.value = valor;
            o.textContent = etiqueta;
            sel.appendChild(o);
        }

        // Control segmentado: un botón por opción, el elegido queda resaltado.
        function segmentado(id, opciones, clave) {
            var cont = $(id);
            opciones.forEach(function (o) {
                var b = document.createElement('button');
                b.type = 'button';
                b.textContent = o.etiqueta;
                b.setAttribute('data-valor', o.valor);
                b.addEventListener('click', function () { estado[clave] = o.valor; render(); });
                cont.appendChild(b);
            });
        }

        function iniciarFiltros() {
            // Años en orden cronológico; grados en orden numérico.
            var anios = [], vistos = {};
            for (var i = 0; i < data.getNumberOfRows(); i++) {
                var a = data.getValue(i, C.ANIO);
                if (!vistos[a]) { vistos[a] = true; anios.push(a); }
            }
            estado.anio = anios.indexOf(String(ANIO_ACTUAL)) !== -1 ? String(ANIO_ACTUAL) : anios[anios.length - 1];
            segmentado('f-anio', anios.map(function (a) { return { valor: a, etiqueta: a }; }), 'anio');
            segmentado('f-genero', [{ valor: '', etiqueta: 'Todos' }, { valor: 'Niñas', etiqueta: 'Niñas' }, { valor: 'Niños', etiqueta: 'Niños' }], 'genero');

            var g = google.visualization.data.group(data, [C.ORDEN, C.GRADO],
                [{ column: C.GRADO, aggregation: google.visualization.data.count, type: 'number' }]);
            var selGrado = $('f-grado'), grados = {};
            for (var j = 0; j < g.getNumberOfRows(); j++) {
                var nombre = g.getValue(j, 1);
                if (!grados[nombre]) { grados[nombre] = true; opcion(selGrado, nombre, nombre); }
            }
            selGrado.addEventListener('change', function () { estado.grado = this.value; render(); });

            $('f-limpiar').addEventListener('click', function () {
                estado.grado = ''; estado.genero = '';
                selGrado.value = '';
                render();
            });
        }

        function marcarFiltros() {
            [['f-anio', 'anio'], ['f-genero', 'genero']].forEach(function (par) {
                Array.prototype.forEach.call($(par[0]).querySelectorAll('button'), function (b) {
                    b.setAttribute('aria-pressed', b.getAttribute('data-valor') === estado[par[1]] ? 'true' : 'false');
                });
            });
            $('f-grado').classList.toggle('gr-activo', !!estado.grado);
            mostrar('f-limpiar', !!(estado.grado || estado.genero), 'inline-block');
        }

        // ---- Cifras ----------------------------------------------------------

        function vistaDe(filas) {
            var v = new google.visualization.DataView(data);
            v.setRows(filas);
            return v;
        }

        function actualizarCifras(filas) {
            var total = filas.length, ninas = 0, ninos = 0, suma = 0, conEdadN = 0;
            filas.forEach(function (i) {
                var g = data.getValue(i, C.GENERO), e = data.getValue(i, C.EDAD);
                if (g === 'Niñas') { ninas++; } else if (g === 'Niños') { ninos++; }
                if (e !== null) { suma += e; conEdadN++; }
            });
            $('kpi-total').textContent = num(total);
            $('kpi-ninas').textContent = num(ninas);
            $('kpi-ninas-sub').textContent = 'niñas (' + pct(ninas, total) + ')';
            $('kpi-ninos').textContent = num(ninos);
            $('kpi-ninos-sub').textContent = 'niños (' + pct(ninos, total) + ')';
            $('kpi-edad').textContent = conEdadN ? (suma / conEdadN).toFixed(1).replace('.', ',') : '–';
        }

        // ---- Gráficas ----------------------------------------------------------

        function nuevo(tipo, id) { return new google.visualization[tipo]($(id)); }

        // Gráficas limpias: sin ejes recargados, el número va escrito sobre cada barra.
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
            t.addColumn('number', 'Estudiantes');
            t.addColumn({ type: 'string', role: 'annotation' });
            t.addColumn({ type: 'string', role: 'tooltip' });
            t.addColumn({ type: 'string', role: 'style' });
            filas.forEach(function (f) { t.addRow([f.etiqueta, f.valor, f.texto, f.ayuda, 'color: ' + (f.color || COLOR.primario)]); });
            return t;
        }

        // Eje de valores con 15 % de aire arriba para que el número quepa sobre la barra.
        function ejeConAire(barras) {
            var max = 0;
            barras.forEach(function (b) { if (b.valor > max) { max = b.valor; } });
            return { textPosition: 'none', gridlines: { color: 'transparent' }, viewWindow: { min: 0, max: Math.max(1, max * 1.15) } };
        }

        function contarValor(valor) {
            return function (valores) {
                var n = 0;
                valores.forEach(function (v) { if (v === valor) { n++; } });
                return n;
            };
        }

        // Número de grado a partir del nombre: "1°", "Grado 1" o "Primero"... "Once".
        // Si no se reconoce (p. ej. "Transición"), no se compara su edad.
        var GRADOS_EN_LETRAS = { primero: 1, segundo: 2, tercero: 3, cuarto: 4, quinto: 5, sexto: 6, septimo: 7,
                                 octavo: 8, noveno: 9, decimo: 10, once: 11, undecimo: 11 };
        function numeroGrado(nombre) {
            var texto = String(nombre || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            var m = /\d+/.exec(texto);
            if (m) { return parseInt(m[0], 10); }
            var palabra = /[a-z]+/.exec(texto);
            return palabra && GRADOS_EN_LETRAS[palabra[0]] ? GRADOS_EN_LETRAS[palabra[0]] : null;
        }

        function graficaGrado(filas) {
            var g = google.visualization.data.group(vistaDe(filas), [C.ORDEN, C.GRADO], [
                { column: C.GENERO, aggregation: google.visualization.data.count, type: 'number' },
                { column: C.GENERO, aggregation: contarValor('Niñas'), type: 'number' },
                { column: C.GENERO, aggregation: contarValor('Niños'), type: 'number' }
            ]);
            var barras = [], mayor = null, menor = null;
            for (var i = 0; i < g.getNumberOfRows(); i++) {
                var f = { etiqueta: g.getValue(i, 1), valor: g.getValue(i, 2) };
                f.texto = num(f.valor);
                f.ayuda = f.etiqueta + ': ' + num(f.valor) + ' estudiantes (' + num(g.getValue(i, 3)) + ' niñas, ' + num(g.getValue(i, 4)) + ' niños)';
                barras.push(f);
                if (!mayor || f.valor > mayor.valor) { mayor = f; }
                if (!menor || f.valor < menor.valor) { menor = f; }
            }
            if (barras.length === 1) {
                lectura('l-grado', ['En ', { b: mayor.etiqueta }, ' hay ', { b: num(mayor.valor) + ' estudiantes' }, '.']);
            } else {
                lectura('l-grado', ['Más en ', { b: mayor.etiqueta + ' (' + num(mayor.valor) + ')' }, ' · menos en ', { b: menor.etiqueta + ' (' + num(menor.valor) + ')' }]);
            }
            nuevo('ColumnChart', 'g-grado').draw(tablaBarras('Grado', barras), opciones({ vAxis: ejeConAire(barras), chartArea: { left: 8, right: 8, top: 24, bottom: 40 } }));
        }

        function graficaGenero(filas) {
            var cuentas = { 'Niñas': 0, 'Niños': 0, 'Sin dato': 0 };
            filas.forEach(function (i) { var g = data.getValue(i, C.GENERO); cuentas[g] = (cuentas[g] || 0) + 1; });
            var total = filas.length;
            var t = new google.visualization.DataTable();
            t.addColumn('string', 'Género');
            t.addColumn('number', 'Estudiantes');
            var slices = {};
            Object.keys(cuentas).forEach(function (k) {
                if (!cuentas[k]) { return; }
                slices[t.getNumberOfRows()] = { color: COLOR_GENERO[k] || COLOR.claro };
                t.addRow([k + ': ' + num(cuentas[k]), cuentas[k]]);
            });
            var partes = [{ b: pct(cuentas['Niñas'], total) + ' niñas' }, ' · ', { b: pct(cuentas['Niños'], total) + ' niños' }];
            if (cuentas['Sin dato']) { partes.push(' · ' + num(cuentas['Sin dato']) + ' sin dato'); }
            lectura('l-genero', partes);
            nuevo('PieChart', 'g-genero').draw(t, opciones({
                pieHole: 0.55, slices: slices, pieSliceText: 'percentage', pieSliceBorderColor: '#fff',
                pieSliceTextStyle: { color: '#fff', bold: true, fontSize: 13 },
                legend: { position: 'right', alignment: 'center', textStyle: { color: '#1F2937', fontSize: 13 } },
                chartArea: { left: 8, right: 8, top: 16, bottom: 16 }
            }));
        }

        function graficaExtraedad(filas) {
            var vista = vistaDe(filas.filter(function (i) { return data.getValue(i, C.EDAD) !== null; }));
            vista.setColumns([C.ORDEN, C.GRADO, {
                type: 'string', label: 'Categoría',
                calc: function (dt, fila) {
                    var esperada = EDAD_ESPERADA_POR_GRADO[numeroGrado(dt.getValue(fila, C.GRADO))];
                    if (esperada === undefined) { return 'Sin referencia'; }
                    return dt.getValue(fila, C.EDAD) >= esperada + MARGEN_EXTRAEDAD ? 'Extraedad' : 'Normal';
                }
            }]);
            var g = google.visualization.data.group(vista, [0, 1], [
                { column: 2, aggregation: contarValor('Extraedad'), type: 'number' },
                { column: 2, aggregation: function (v) { return v.length - contarValor('Sin referencia')(v); }, type: 'number' }
            ]);
            var barras = [], casos = 0, base = 0, peor = null;
            for (var i = 0; i < g.getNumberOfRows(); i++) {
                var extra = g.getValue(i, 2), totalGrado = g.getValue(i, 3);
                if (!totalGrado) { continue; }
                var f = { etiqueta: g.getValue(i, 1), valor: extra / totalGrado, color: COLOR.alerta };
                f.texto = num(extra) + ' de ' + num(totalGrado);
                f.ayuda = f.etiqueta + ': ' + num(extra) + ' de ' + num(totalGrado) + ' estudiantes (' + pct(extra, totalGrado) + ')';
                barras.push(f);
                casos += extra; base += totalGrado;
                if (extra && (!peor || f.valor > peor.valor)) { peor = f; }
            }
            if (!barras.length) {
                lectura('l-extraedad', ['No hay edades registradas para estos grados.']);
                $('g-extraedad').textContent = '';
                return;
            }
            var partes = [{ b: num(casos) + ' de ' + num(base) + ' (' + pct(casos, base) + ')' }, ' en extraedad'];
            if (peor) { partes.push(' · más casos en ', { b: peor.etiqueta }); }
            lectura('l-extraedad', partes);
            nuevo('BarChart', 'g-extraedad').draw(tablaBarras('Grado', barras), opciones({
                height: Math.max(ALTO_FILA2, 30 * barras.length + 40),
                hAxis: { format: '#%', minValue: 0, maxValue: 0.25, textStyle: { color: '#6b7280' }, gridlines: { color: '#F1F4FA' } },
                vAxis: { gridlines: { color: 'transparent' } },
                chartArea: { left: 80, right: 70, top: 8, bottom: 32 }
            }));
        }

        function graficaAntiguedad(filas) {
            var grupos = [
                { etiqueta: 'Nuevos este año', valor: 0, color: COLOR.primario },
                { etiqueta: '2 años', valor: 0, color: COLOR.claro },
                { etiqueta: '3 años o más', valor: 0, color: COLOR.oscuro }
            ];
            var conDato = 0;
            filas.forEach(function (i) {
                var a = data.getValue(i, C.ANTIGUEDAD);
                if (a === null || a < 1) { return; }
                grupos[a === 1 ? 0 : (a === 2 ? 1 : 2)].valor++;
                conDato++;
            });
            grupos.forEach(function (g) {
                g.texto = num(g.valor) + ' (' + pct(g.valor, conDato) + ')';
                g.ayuda = g.etiqueta + ': ' + num(g.valor) + ' estudiantes';
            });
            lectura('l-antiguedad', [{ b: num(grupos[0].valor) + ' (' + pct(grupos[0].valor, conDato) + ')' }, ' entraron este año']);
            nuevo('ColumnChart', 'g-antiguedad').draw(tablaBarras('Antigüedad', grupos), opciones({
                height: ALTO_FILA2,
                bar: { groupWidth: '50%' },
                vAxis: ejeConAire(grupos),
                hAxis: { textStyle: { color: '#1F2937', fontSize: 13 } },
                chartArea: { left: 8, right: 8, top: 28, bottom: 36 }
            }));
        }

        function graficaEvolucion(filasEvolucion) {
            var g = google.visualization.data.group(vistaDe(filasEvolucion), [C.ANIO],
                [{ column: C.ANIO, aggregation: google.visualization.data.count, type: 'number' }]);
            var barras = [];
            for (var i = 0; i < g.getNumberOfRows(); i++) {
                var anio = g.getValue(i, 0), n = g.getValue(i, 1);
                barras.push({ etiqueta: anio, valor: n, texto: num(n), ayuda: anio + ': ' + num(n) + ' estudiantes',
                              color: anio === estado.anio ? COLOR.primario : COLOR.claro });
            }
            if (barras.length < 2) {
                lectura('l-evolucion', ['Solo hay datos de un año escolar.']);
            } else {
                var a = barras[0], b = barras[barras.length - 1], d = b.valor - a.valor;
                lectura('l-evolucion', ['De ', { b: num(a.valor) }, ' en ' + a.etiqueta + ' a ', { b: num(b.valor) }, ' en ' + b.etiqueta,
                    ' (' + (d > 0 ? '+' + num(d) : d < 0 ? '−' + num(-d) : 'sin cambio') + ')']);
            }
            nuevo('ColumnChart', 'g-evolucion').draw(tablaBarras('Año', barras), opciones({
                height: 260,
                bar: { groupWidth: '40%' },
                vAxis: ejeConAire(barras),
                chartArea: { left: 8, right: 8, top: 28, bottom: 32 }
            }));
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
            var filas = filasFiltradas(true);
            actualizarCifras(filas);
            var hayDatos = filas.length > 0;
            mostrar('gr-empty', !hayDatos, 'block');
            mostrar('gr-graficas', hayDatos, 'grid');
            if (!hayDatos) { return; }
            dibujar('g-grado', function () { graficaGrado(filas); });
            dibujar('g-genero', function () { graficaGenero(filas); });
            dibujar('g-extraedad', function () { graficaExtraedad(filas); });
            dibujar('g-antiguedad', function () { graficaAntiguedad(filas); });
            dibujar('g-evolucion', function () { graficaEvolucion(filasFiltradas(false)); });
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
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') { return; }
                botones.forEach(function (b) {
                    if (b.getAttribute('aria-expanded') === 'true') { cerrar(b); b.focus(); }
                });
            });
        }

        function iniciar() {
            iniciarAyudas();
            data = construirTabla();
            if (!data.getNumberOfRows()) {
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
