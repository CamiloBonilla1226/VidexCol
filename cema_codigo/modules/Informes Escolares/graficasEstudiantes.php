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
// Privacidad: categorías con menos estudiantes que esto se agrupan en "Otras".
$minimoCategoria = 5;

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
        // Solo campos agregables: sin nombres ni identificadores.
        $datos[] = [
            'anio' => $fila['anio'],
            'grado' => $fila['grado'],
            'orden' => (int) $fila['ordenGrado'],
            'grupo' => $fila['grupo'] !== '' ? $fila['grupo'] : 'Sin dato',
            'genero' => $fila['genero'],
            'edad' => $fila['edad'] === null ? null : (int) $fila['edad'],
            'localidad' => $fila['localidad'],
            'nacionalidad' => $fila['nacionalidad'],
            'antiguedad' => (int) $fila['antiguedad'],
        ];
    }
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;
    $generado = date('d/m/Y H:i');

    ?>
    <style>
        .gr-wrap { color: #1F2937; max-width: 1200px; }
        .gr-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .gr-head h2 { margin: 0; font-size: 1.6em; font-weight: 700; color: #1E3A8A; letter-spacing: -.01em; }
        .gr-head p { margin: 2px 0 0; color: #6b7280; font-size: .9em; }
        .gr-year { display: flex; align-items: center; gap: 8px; font-size: .85em; font-weight: 600; color: #4B5563; }
        .gr-year select { appearance: none; -webkit-appearance: none; border: 1px solid #CFDCF8; border-radius: 999px; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%233575EF' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 14px center; padding: 8px 36px 8px 16px; font-size: 1em; font-weight: 700; color: #1E3A8A; cursor: pointer; box-shadow: 0 1px 3px rgba(30,58,138,.08); }
        .gr-year select:focus { outline: 2px solid #3575EF; outline-offset: 1px; }
        .gr-filters { display: grid; gap: 10px; background: #fff; border: 1px solid #E3EAF8; border-radius: 16px; padding: 14px 18px; margin-bottom: 18px; box-shadow: 0 4px 16px rgba(30,58,138,.06); }
        .gr-frow { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .gr-flabel { min-width: 64px; font-size: .78em; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
        .gr-chip { border: 1px solid #CFDCF8; background: #fff; color: #2555B8; border-radius: 999px; padding: 5px 14px; font-size: .85em; font-weight: 600; cursor: pointer; transition: background .15s, color .15s, border-color .15s; }
        .gr-chip:hover { background: #EEF4FF; }
        .gr-chip:focus-visible { outline: 2px solid #3575EF; outline-offset: 1px; }
        .gr-chip.on { background: #3575EF; border-color: #3575EF; color: #fff; }
        .gr-kpis { display: grid; grid-template-columns: 1.4fr repeat(3, 1fr); gap: 14px; margin-bottom: 18px; }
        .gr-kpi { position: relative; background: #fff; border: 1px solid #E3EAF8; border-radius: 16px; padding: 16px 20px; box-shadow: 0 4px 16px rgba(30,58,138,.06); overflow: hidden; }
        .gr-kpi::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: #9DB8F5; }
        .gr-kpi.gr-hero { background: linear-gradient(135deg, #3575EF, #1E3A8A); border-color: transparent; color: #fff; }
        .gr-kpi.gr-hero::before { display: none; }
        .gr-kpi-label { font-size: .8em; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
        .gr-hero .gr-kpi-label { color: #DCE7FD; }
        .gr-kpi-value { font-size: 2.1em; font-weight: 800; line-height: 1.15; color: #1E3A8A; }
        .gr-hero .gr-kpi-value { font-size: 2.8em; color: #fff; }
        .gr-kpi-sub { font-size: .8em; color: #6b7280; min-height: 1.2em; }
        .gr-hero .gr-kpi-sub { color: #DCE7FD; }
        .gr-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 18px; }
        .gr-card { grid-column: span 3; background: #fff; border: 1px solid #E3EAF8; border-radius: 16px; padding: 16px 20px 12px; min-width: 0; box-shadow: 0 4px 16px rgba(30,58,138,.06); }
        .gr-card.gr-w4 { grid-column: span 4; }
        .gr-card.gr-w2 { grid-column: span 2; }
        .gr-card.gr-wide { grid-column: 1 / -1; }
        .gr-card h3 { margin: 0; color: #1E3A8A; font-size: 1.05em; font-weight: 700; }
        .gr-card p { margin: 2px 0 10px; font-size: .84em; color: #6b7280; }
        .gr-chart { width: 100%; }
        .gr-empty { display: none; border: 1px dashed #9DB8F5; border-radius: 16px; background: #F5F8FF; color: #1E3A8A; padding: 40px 16px; text-align: center; font-weight: 600; }
        .gr-foot { margin-top: 14px; font-size: .8em; color: #6b7280; }
        @media (max-width: 900px) {
            .gr-kpis { grid-template-columns: 1fr 1fr; }
            .gr-card, .gr-card.gr-w4, .gr-card.gr-w2 { grid-column: 1 / -1; }
        }
        @media (max-width: 480px) { .gr-kpis { grid-template-columns: 1fr; } }
    </style>

    <div class="gr-wrap" id="gr-wrap">
        <div class="gr-head">
            <div>
                <h2>Gráficas de estudiantes</h2>
                <p>Matrícula del colegio por año escolar. Los filtros son opcionales.</p>
            </div>
            <label class="gr-year">Año escolar <select id="f-anio"></select></label>
        </div>

        <div class="gr-filters">
            <div class="gr-frow"><span class="gr-flabel">Grado</span><span class="gr-frow" id="f-grado"></span></div>
            <div class="gr-frow"><span class="gr-flabel">Género</span><span class="gr-frow" id="f-genero"></span></div>
        </div>

        <div class="gr-kpis" id="gr-kpis">
            <div class="gr-kpi gr-hero"><div class="gr-kpi-label">Total de estudiantes</div><div class="gr-kpi-value" id="kpi-total">–</div><div class="gr-kpi-sub" id="kpi-total-sub"></div></div>
            <div class="gr-kpi"><div class="gr-kpi-label">Niñas</div><div class="gr-kpi-value" id="kpi-ninas">–</div><div class="gr-kpi-sub" id="kpi-ninas-sub"></div></div>
            <div class="gr-kpi"><div class="gr-kpi-label">Niños</div><div class="gr-kpi-value" id="kpi-ninos">–</div><div class="gr-kpi-sub" id="kpi-ninos-sub"></div></div>
            <div class="gr-kpi"><div class="gr-kpi-label">Edad promedio</div><div class="gr-kpi-value" id="kpi-edad">–</div><div class="gr-kpi-sub">años al inicio del año escolar</div></div>
        </div>

        <div class="gr-empty" id="gr-empty">Sin datos para los filtros seleccionados</div>

        <div class="gr-grid" id="gr-graficas">
            <div class="gr-card gr-w4"><h3>Matrícula por grado</h3><p>Total de estudiantes por grado (columnas) y porcentaje de niñas (línea).</p><div class="gr-chart" id="g-grado"></div></div>
            <div class="gr-card gr-w2"><h3>Distribución por género</h3><p>Proporción de niñas y niños.</p><div class="gr-chart" id="g-genero"></div></div>
            <div class="gr-card"><h3>Extraedad por grado</h3><p>Estudiantes en edad, adelantados o con extraedad según la edad esperada del grado.</p><div class="gr-chart" id="g-extraedad"></div></div>
            <div class="gr-card"><h3>Localidades de procedencia (Top 10)</h3><p>Las localidades con menos de 5 estudiantes se agrupan en «Otras».</p><div class="gr-chart" id="g-localidad"></div></div>
            <div class="gr-card gr-wide"><h3>Evolución de la matrícula por año</h3><p>Total de estudiantes en cada año escolar; ignora el filtro de año pero respeta los demás.</p><div class="gr-chart" id="g-evolucion"></div></div>
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
        var MINIMO_CATEGORIA = <?php echo (int) $minimoCategoria; ?>;

        // Paleta del proyecto (la misma de los reportes de Informes Escolares).
        var COLOR = { primario: '#3575EF', oscuro: '#1E3A8A', medio: '#2B60CF', claro: '#9DB8F5', pale: '#DCE7FD', gris: '#9CA3AF', alerta: '#D97706' };
        var COLOR_GENERO = { 'Niñas': COLOR.primario, 'Niños': COLOR.oscuro, 'Sin dato': COLOR.gris };

        // Columnas del DataTable
        var C = { ANIO: 0, GRADO: 1, ORDEN: 2, GRUPO: 3, GENERO: 4, EDAD: 5, LOCALIDAD: 6, NACIONALIDAD: 7, ANTIGUEDAD: 8 };
        var ALTO = 320;

        var data, redimensionar = null;
        // Sin selección en grado o género = todos (por eso los filtros no son obligatorios).
        var estado = { anio: null, grados: [], generos: [] };

        function texto(id, valor) { document.getElementById(id).textContent = valor; }
        function mostrar(id, visible, tipo) { document.getElementById(id).style.display = visible ? (tipo || '') : 'none'; }

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
                return [r.anio || 'Sin dato', r.grado || 'Sin dato', r.orden, r.grupo || 'Sin dato', r.genero || 'Sin dato',
                        r.edad === null || r.edad === undefined ? null : r.edad, r.localidad || 'Sin dato', r.nacionalidad || 'Sin dato', r.antiguedad];
            }));
            return t;
        }

        function unicos(columna) {
            var vistos = {}, lista = [];
            for (var i = 0; i < data.getNumberOfRows(); i++) {
                var v = data.getValue(i, columna);
                if (!vistos[v]) { vistos[v] = true; lista.push(v); }
            }
            return lista;
        }

        // ---- Filtros ---------------------------------------------------------

        function filtros(incluirAnio) {
            var f = [];
            if (incluirAnio) { f.push({ column: C.ANIO, value: estado.anio }); }
            if (estado.grados.length) { f.push({ column: C.GRADO, test: function (v) { return estado.grados.indexOf(v) !== -1; } }); }
            if (estado.generos.length) { f.push({ column: C.GENERO, test: function (v) { return estado.generos.indexOf(v) !== -1; } }); }
            return f;
        }

        function alternar(lista, valor) {
            var i = lista.indexOf(valor);
            if (i === -1) { lista.push(valor); } else { lista.splice(i, 1); }
        }

        function chip(etiqueta, activo, alClick) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'gr-chip' + (activo ? ' on' : '');
            b.setAttribute('aria-pressed', activo ? 'true' : 'false');
            b.textContent = etiqueta;
            b.addEventListener('click', alClick);
            return b;
        }

        function pintarChips(idContenedor, opciones, seleccion) {
            var cont = document.getElementById(idContenedor);
            while (cont.firstChild) { cont.removeChild(cont.firstChild); }
            cont.appendChild(chip('Todos', seleccion.length === 0, function () { seleccion.length = 0; render(); }));
            opciones.forEach(function (o) {
                cont.appendChild(chip(o, seleccion.indexOf(o) !== -1, function () { alternar(seleccion, o); render(); }));
            });
        }

        function pintarFiltros() {
            // Grados en orden numérico (no alfabético).
            var vista = new google.visualization.DataView(data);
            var g = google.visualization.data.group(vista, [C.ORDEN, C.GRADO],
                [{ column: C.GRADO, aggregation: google.visualization.data.count, type: 'number', label: 'n' }]);
            var grados = [];
            for (var i = 0; i < g.getNumberOfRows(); i++) { grados.push(g.getValue(i, 1)); }
            var generos = unicos(C.GENERO).sort();
            pintarChips('f-grado', grados, estado.grados);
            pintarChips('f-genero', generos, estado.generos);
        }

        function iniciarAnios() {
            var anios = unicos(C.ANIO);
            var sel = document.getElementById('f-anio');
            anios.forEach(function (a) {
                var o = document.createElement('option');
                o.value = a; o.textContent = a;
                sel.appendChild(o);
            });
            estado.anio = anios.indexOf(ANIO_ACTUAL) !== -1 ? ANIO_ACTUAL : anios[anios.length - 1];
            sel.value = estado.anio;
            sel.addEventListener('change', function () { estado.anio = sel.value; render(); });
        }

        // ---- Indicadores -----------------------------------------------------

        function vistaDe(filas) {
            var v = new google.visualization.DataView(data);
            v.setRows(filas);
            return v;
        }

        function actualizarKpis(filas) {
            var total = filas.length, ninas = 0, ninos = 0, suma = 0, conEdadN = 0;
            filas.forEach(function (i) {
                var g = data.getValue(i, C.GENERO), e = data.getValue(i, C.EDAD);
                if (g === 'Niñas') { ninas++; } else if (g === 'Niños') { ninos++; }
                if (e !== null) { suma += e; conEdadN++; }
            });
            function pct(n) { return total ? (100 * n / total).toFixed(1).replace('.', ',') + ' % del total' : ''; }
            texto('kpi-total', total.toLocaleString('es'));
            texto('kpi-total-sub', total ? 'año escolar ' + estado.anio : '');
            texto('kpi-ninas', ninas.toLocaleString('es'));
            texto('kpi-ninas-sub', pct(ninas));
            texto('kpi-ninos', ninos.toLocaleString('es'));
            texto('kpi-ninos-sub', pct(ninos));
            texto('kpi-edad', conEdadN ? (suma / conEdadN).toFixed(1).replace('.', ',') : '–');
        }

        // ---- Gráficas ----------------------------------------------------------

        function nuevo(tipo, id) { return new google.visualization[tipo](document.getElementById(id)); }

        function opciones(extra) {
            var o = {
                height: ALTO,
                fontSize: 12,
                fontName: 'Segoe UI, Roboto, Helvetica, Arial, sans-serif',
                backgroundColor: 'transparent',
                legend: { position: 'bottom', textStyle: { color: '#4B5563' } },
                chartArea: { left: 56, right: 24, top: 16, bottom: 56 },
                colors: [COLOR.primario, COLOR.oscuro, COLOR.claro, COLOR.gris],
                hAxis: { textStyle: { color: '#4B5563' }, gridlines: { color: '#EEF2FA' }, baselineColor: '#CFDCF8' },
                vAxis: { textStyle: { color: '#4B5563' }, gridlines: { color: '#EEF2FA' }, baselineColor: '#CFDCF8' },
                animation: { startup: true, duration: 350, easing: 'out' }
            };
            Object.keys(extra || {}).forEach(function (k) {
                o[k] = (typeof extra[k] === 'object' && !Array.isArray(extra[k]) && o[k] && typeof o[k] === 'object')
                    ? Object.assign({}, o[k], extra[k]) : extra[k];
            });
            return o;
        }

        function contarPor(vista, columna) {
            return google.visualization.data.group(vista, [columna],
                [{ column: columna, aggregation: google.visualization.data.count, type: 'number', label: 'Estudiantes' }]);
        }

        function contarValor(valor) {
            return function (valores) {
                var n = 0;
                valores.forEach(function (v) { if (v === valor) { n++; } });
                return n;
            };
        }

        function numeroGrado(nombre, orden) {
            var m = /\d+/.exec(nombre || '');
            return m ? parseInt(m[0], 10) : orden;
        }

        function conEdad(filas) {
            return filas.filter(function (i) { return data.getValue(i, C.EDAD) !== null; });
        }

        function graficaGenero(filas) {
            var g = contarPor(vistaDe(filas), C.GENERO);
            var slices = {};
            for (var i = 0; i < g.getNumberOfRows(); i++) {
                slices[i] = { color: COLOR_GENERO[g.getValue(i, 0)] || COLOR.claro };
            }
            nuevo('PieChart', 'g-genero').draw(g, opciones({ pieHole: 0.55, slices: slices, pieSliceBorderColor: '#fff', chartArea: { left: 16, right: 16, top: 16, bottom: 48 } }));
        }

        function graficaGrado(filas) {
            var g = google.visualization.data.group(vistaDe(filas), [C.ORDEN, C.GRADO], [
                { column: C.GENERO, aggregation: google.visualization.data.count, type: 'number', label: 'Total' },
                { column: C.GENERO, aggregation: contarValor('Niñas'), type: 'number', label: 'Niñas' }
            ]);
            var t = new google.visualization.DataTable();
            t.addColumn('string', 'Grado');
            t.addColumn('number', 'Estudiantes');
            t.addColumn('number', '% de niñas');
            for (var i = 0; i < g.getNumberOfRows(); i++) {
                var total = g.getValue(i, 2);
                t.addRow([g.getValue(i, 1), total, total ? g.getValue(i, 3) / total : null]);
            }
            new google.visualization.NumberFormat({ pattern: '#.#%' }).format(t, 2);
            nuevo('ComboChart', 'g-grado').draw(t, opciones({
                seriesType: 'bars',
                series: { 0: { color: COLOR.primario }, 1: { type: 'line', targetAxisIndex: 1, color: COLOR.oscuro, pointSize: 5 } },
                vAxes: { 0: { minValue: 0, title: 'Estudiantes' }, 1: { minValue: 0, maxValue: 1, format: '#%', title: '% de niñas' } },
                bar: { groupWidth: '65%' },
                chartArea: { left: 56, right: 56, top: 16, bottom: 56 }
            }));
        }

        function graficaExtraedad(filas) {
            var vista = vistaDe(conEdad(filas));
            vista.setColumns([C.ORDEN, C.GRADO, {
                type: 'string', label: 'Categoría',
                calc: function (dt, fila) {
                    var esperada = EDAD_ESPERADA_POR_GRADO[numeroGrado(dt.getValue(fila, C.GRADO), dt.getValue(fila, C.ORDEN))];
                    if (esperada === undefined) { return 'Sin referencia'; }
                    var edad = dt.getValue(fila, C.EDAD);
                    if (edad < esperada) { return 'Adelantado'; }
                    return edad >= esperada + MARGEN_EXTRAEDAD ? 'Extraedad' : 'En edad';
                }
            }]);
            var g = google.visualization.data.group(vista, [0, 1], [
                { column: 2, aggregation: contarValor('En edad'), type: 'number', label: 'En edad' },
                { column: 2, aggregation: contarValor('Adelantado'), type: 'number', label: 'Adelantado' },
                { column: 2, aggregation: contarValor('Extraedad'), type: 'number', label: 'Extraedad' }
            ]);
            var t = new google.visualization.DataTable();
            t.addColumn('string', 'Grado');
            ['En edad', 'Adelantado', 'Extraedad'].forEach(function (n) { t.addColumn('number', n); });
            for (var i = 0; i < g.getNumberOfRows(); i++) {
                var suma = g.getValue(i, 2) + g.getValue(i, 3) + g.getValue(i, 4);
                if (suma > 0) { t.addRow([g.getValue(i, 1), g.getValue(i, 2), g.getValue(i, 3), g.getValue(i, 4)]); }
            }
            var cont = document.getElementById('g-extraedad');
            if (!t.getNumberOfRows()) { cont.textContent = 'Sin edad esperada definida para estos grados.'; return; }
            nuevo('ColumnChart', 'g-extraedad').draw(t, opciones({
                isStacked: 'percent',
                colors: [COLOR.primario, COLOR.claro, COLOR.alerta],
                bar: { groupWidth: '65%' },
                vAxis: { format: '#%', minValue: 0 }
            }));
        }

        // Tabla [etiqueta, valor, anotación(, estilo)] a partir de pares ya ordenados.
        function tablaBarras(pares, titulo, estilos) {
            var t = new google.visualization.DataTable();
            t.addColumn('string', titulo);
            t.addColumn('number', 'Estudiantes');
            t.addColumn({ type: 'string', role: 'annotation' });
            if (estilos) { t.addColumn({ type: 'string', role: 'style' }); }
            pares.forEach(function (p, i) {
                var fila = [p[0], p[1], String(p[1])];
                if (estilos) { fila.push(estilos[i]); }
                t.addRow(fila);
            });
            return t;
        }

        // Privacidad: las categorías con menos de MINIMO_CATEGORIA estudiantes (y todo lo que
        // quede fuera del Top 10) se agrupan en "Otras" para no identificar a nadie.
        function graficaLocalidad(filas) {
            var g = contarPor(vistaDe(filas), C.LOCALIDAD);
            g.sort([{ column: 1, desc: true }]);
            var pares = [], otras = 0;
            for (var i = 0; i < g.getNumberOfRows(); i++) {
                var n = g.getValue(i, 1);
                if (pares.length < 10 && n >= MINIMO_CATEGORIA) { pares.push([g.getValue(i, 0), n]); } else { otras += n; }
            }
            var estilos = pares.map(function () { return 'color: ' + COLOR.primario; });
            if (otras > 0) { pares.push(['Otras', otras]); estilos.push('color: ' + COLOR.gris); }
            nuevo('BarChart', 'g-localidad').draw(tablaBarras(pares, 'Localidad', estilos), opciones({
                legend: { position: 'none' },
                height: Math.max(ALTO, 28 * pares.length + 60),
                hAxis: { minValue: 0 },
                bar: { groupWidth: '70%' },
                chartArea: { left: 150, right: 32, top: 16, bottom: 40 }
            }));
        }

        function graficaEvolucion(filasEvolucion) {
            var g = contarPor(vistaDe(filasEvolucion), C.ANIO);
            var pares = [];
            for (var i = 0; i < g.getNumberOfRows(); i++) { pares.push([g.getValue(i, 0), g.getValue(i, 1)]); }
            nuevo('LineChart', 'g-evolucion').draw(tablaBarras(pares, 'Año'), opciones({
                legend: { position: 'none' },
                colors: [COLOR.primario],
                pointSize: 7,
                lineWidth: 3,
                vAxis: { minValue: 0 }
            }));
        }

        function render() {
            pintarFiltros();
            var filas = data.getFilteredRows(filtros(true));
            actualizarKpis(filas);
            var hayDatos = filas.length > 0;
            mostrar('gr-empty', !hayDatos, 'block');
            mostrar('gr-graficas', hayDatos, 'grid');
            if (!hayDatos) { return; }
            graficaGenero(filas);
            graficaGrado(filas);
            graficaExtraedad(filas);
            graficaLocalidad(filas);
            graficaEvolucion(data.getFilteredRows(filtros(false)));
        }

        function iniciar() {
            data = construirTabla();
            if (!data.getNumberOfRows()) {
                mostrar('gr-graficas', false);
                mostrar('gr-empty', true, 'block');
                return;
            }
            iniciarAnios();
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
