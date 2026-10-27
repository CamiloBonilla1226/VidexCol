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

if (isActionAccessible($guid, $connection2, '/modules/Informes Escolares/graficasEstudiantes.php') == false) {
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
        .gr-wrap { color: #1F2937; }
        .gr-filters { border: 1px solid #3575EF; border-radius: 8px; background: #F5F8FF; padding: 12px 16px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(53,117,239,.15); }
        .gr-filters h3 { margin: 0 0 8px; color: #1E3A8A; font-size: 1em; }
        .gr-filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: start; }
        .gr-filter-grid label { display: block; font-size: .85em; font-weight: bold; margin-bottom: 4px; color: #1E3A8A; }
        .gr-filter-grid .gr-ctl { min-height: 48px; }
        .gr-kpis { display: grid; grid-template-columns: 1.4fr repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
        .gr-kpi { background: #fff; border: 1px solid #9DB8F5; border-radius: 8px; padding: 12px 16px; }
        .gr-kpi.gr-hero { background: #3575EF; border-color: #3575EF; color: #fff; }
        .gr-kpi .gr-kpi-label { font-size: .85em; font-weight: 600; color: #4B5563; }
        .gr-kpi.gr-hero .gr-kpi-label { color: #DCE7FD; }
        .gr-kpi .gr-kpi-value { font-size: 2em; font-weight: 700; line-height: 1.2; color: #1E3A8A; }
        .gr-kpi.gr-hero .gr-kpi-value { font-size: 2.6em; color: #fff; }
        .gr-kpi .gr-kpi-sub { font-size: .8em; color: #6b7280; }
        .gr-kpi.gr-hero .gr-kpi-sub { color: #DCE7FD; }
        .gr-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .gr-card { background: #fff; border: 1px solid #9DB8F5; border-radius: 8px; padding: 12px 16px; min-width: 0; }
        .gr-card.gr-wide { grid-column: 1 / -1; }
        .gr-card h3 { margin: 0; color: #1E3A8A; font-size: 1.05em; }
        .gr-card p { margin: 2px 0 8px; font-size: .85em; color: #4B5563; }
        .gr-chart { width: 100%; }
        .gr-empty { display: none; border: 1px dashed #9DB8F5; border-radius: 8px; background: #F5F8FF; color: #1E3A8A; padding: 32px 16px; text-align: center; font-weight: 600; }
        .gr-foot { margin-top: 12px; font-size: .8em; color: #6b7280; }
        @media (max-width: 900px) {
            .gr-kpis { grid-template-columns: 1fr 1fr; }
            .gr-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) { .gr-kpis { grid-template-columns: 1fr; } }
    </style>

    <div class="gr-wrap" id="gr-wrap">
        <div id="gr-dashboard">
            <div class="gr-filters">
                <h3>Filtros</h3>
                <div class="gr-filter-grid">
                    <div><label>Año escolar</label><div class="gr-ctl" id="ctl-anio"></div></div>
                    <div><label>Grado</label><div class="gr-ctl" id="ctl-grado"></div></div>
                    <div><label>Grupo</label><div class="gr-ctl" id="ctl-grupo"></div></div>
                    <div><label>Género</label><div class="gr-ctl" id="ctl-genero"></div></div>
                    <div><label>Rango de edad</label><div class="gr-ctl" id="ctl-edad"></div></div>
                    <div><label>Rango de antigüedad (años)</label><div class="gr-ctl" id="ctl-antiguedad"></div></div>
                </div>
            </div>
            <div id="ctl-helper" style="display:none"></div>
        </div>

        <div class="gr-kpis" id="gr-kpis">
            <div class="gr-kpi gr-hero"><div class="gr-kpi-label">Total de estudiantes</div><div class="gr-kpi-value" id="kpi-total">–</div><div class="gr-kpi-sub" id="kpi-total-sub"></div></div>
            <div class="gr-kpi"><div class="gr-kpi-label">Niñas</div><div class="gr-kpi-value" id="kpi-ninas">–</div><div class="gr-kpi-sub" id="kpi-ninas-sub"></div></div>
            <div class="gr-kpi"><div class="gr-kpi-label">Niños</div><div class="gr-kpi-value" id="kpi-ninos">–</div><div class="gr-kpi-sub" id="kpi-ninos-sub"></div></div>
            <div class="gr-kpi"><div class="gr-kpi-label">Edad promedio</div><div class="gr-kpi-value" id="kpi-edad">–</div><div class="gr-kpi-sub">años cumplidos al inicio del año escolar</div></div>
        </div>

        <div class="gr-empty" id="gr-empty">Sin datos para los filtros seleccionados</div>

        <div class="gr-grid" id="gr-graficas">
            <div class="gr-card"><h3>Distribución por género</h3><p>Proporción de niñas y niños entre los estudiantes filtrados.</p><div class="gr-chart" id="g-genero"></div></div>
            <div class="gr-card"><h3>Matrícula por grado</h3><p>Total de estudiantes por grado (columnas) y porcentaje de niñas (línea).</p><div class="gr-chart" id="g-grado"></div></div>
            <div class="gr-card"><h3>Pirámide poblacional</h3><p>Estudiantes por edad: niños a la izquierda, niñas a la derecha.</p><div class="gr-chart" id="g-piramide"></div></div>
            <div class="gr-card"><h3>Extraedad por grado</h3><p>Proporción de estudiantes en edad, adelantados o con extraedad respecto a la edad esperada del grado.</p><div class="gr-chart" id="g-extraedad"></div></div>
            <div class="gr-card"><h3>Estudiantes por grupo</h3><p>Tamaño de cada grupo, del más numeroso al más pequeño.</p><div class="gr-chart" id="g-grupo"></div></div>
            <div class="gr-card"><h3>Localidades de procedencia (Top 10)</h3><p>Barrios o localidades con más estudiantes; las de menos de 5 se agrupan en «Otras».</p><div class="gr-chart" id="g-localidad"></div></div>
            <div class="gr-card"><h3>Antigüedad en el colegio</h3><p>Años escolares que el estudiante lleva matriculado, contando el año en curso.</p><div class="gr-chart" id="g-antiguedad"></div></div>
            <div class="gr-card"><h3>Evolución de la matrícula por año</h3><p>Total de estudiantes en cada año escolar; ignora el filtro de año pero respeta los demás.</p><div class="gr-chart" id="g-evolucion"></div></div>
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

        // Columnas del DataTable
        var C = { ANIO: 0, GRADO: 1, ORDEN: 2, GRUPO: 3, GENERO: 4, EDAD: 5, LOCALIDAD: 6, NACIONALIDAD: 7, ANTIGUEDAD: 8 };

        var data, dashboard, controles = {}, temporizador = null;

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

        function categoria(columna, etiqueta, multiple, ordenar) {
            return new google.visualization.ControlWrapper({
                controlType: 'CategoryFilter',
                containerId: 'ctl-' + etiqueta,
                options: {
                    filterColumnIndex: columna,
                    ui: { allowMultiple: multiple, allowNone: multiple, allowTyping: false, label: '', sortValues: ordenar, selectedValuesLayout: 'belowWrapping' }
                }
            });
        }

        function rango(columna, etiqueta) {
            return new google.visualization.ControlWrapper({
                controlType: 'NumberRangeFilter',
                containerId: 'ctl-' + etiqueta,
                options: { filterColumnIndex: columna, ui: { label: '', labelStacking: 'vertical', format: { pattern: '#' } } }
            });
        }

        function seleccion(ctl) {
            var s = ctl.getState() || {};
            return s.selectedValues && s.selectedValues.length ? s.selectedValues : null;
        }

        // Arma los filtros de getFilteredRows a partir del estado de los controles.
        // Con incluirAnio = false se ignora el año (gráfica de evolución).
        function filtrosActuales(incluirAnio) {
            var filtros = [];
            var anio = seleccion(controles.anio) || [ANIO_ACTUAL];
            function enLista(lista) { return function (v) { return lista.indexOf(v) !== -1; }; }
            if (incluirAnio) { filtros.push({ column: C.ANIO, test: enLista(anio) }); }
            [['grado', C.GRADO], ['grupo', C.GRUPO], ['genero', C.GENERO]].forEach(function (par) {
                var sel = seleccion(controles[par[0]]);
                if (sel) { filtros.push({ column: par[1], test: enLista(sel) }); }
            });
            // Los rangos solo filtran si el usuario los movió: así los valores nulos no se pierden.
            [['edad', C.EDAD], ['antiguedad', C.ANTIGUEDAD]].forEach(function (par) {
                var s = controles[par[0]].getState() || {};
                if (s.lowValue === undefined || s.lowValue === null || s.highValue === undefined || s.highValue === null) { return; }
                var vista = new google.visualization.DataView(data);
                vista.setRows(data.getFilteredRows(filtros));
                var lim = vista.getColumnRange(par[1]);
                if (lim.min === null || lim.min === undefined) { return; }
                if (s.lowValue <= lim.min && s.highValue >= lim.max) { return; }
                filtros.push({ column: par[1], minValue: s.lowValue, maxValue: s.highValue });
            });
            return filtros;
        }

        function vistaDe(filas) {
            var v = new google.visualization.DataView(data);
            v.setRows(filas);
            return v;
        }

        function actualizarKpis(filas) {
            var total = filas.length, ninas = 0, ninos = 0, suma = 0, conEdad = 0;
            filas.forEach(function (i) {
                var g = data.getValue(i, C.GENERO), e = data.getValue(i, C.EDAD);
                if (g === 'Niñas') { ninas++; } else if (g === 'Niños') { ninos++; }
                if (e !== null) { suma += e; conEdad++; }
            });
            function pct(n) { return total ? (100 * n / total).toFixed(1).replace('.', ',') + ' % del total' : ''; }
            texto('kpi-total', total.toLocaleString('es'));
            texto('kpi-total-sub', total ? 'según los filtros seleccionados' : '');
            texto('kpi-ninas', ninas.toLocaleString('es'));
            texto('kpi-ninas-sub', pct(ninas));
            texto('kpi-ninos', ninos.toLocaleString('es'));
            texto('kpi-ninos-sub', pct(ninos));
            texto('kpi-edad', conEdad ? (suma / conEdad).toFixed(1).replace('.', ',') : '–');
        }

        function render() {
            var filas = data.getFilteredRows(filtrosActuales(true));
            actualizarKpis(filas);
            var hayDatos = filas.length > 0;
            mostrar('gr-empty', !hayDatos, 'block');
            mostrar('gr-graficas', hayDatos, 'grid');
            if (hayDatos) {
                dibujarGraficas(filas, data.getFilteredRows(filtrosActuales(false)));
            }
        }

        function programar() {
            clearTimeout(temporizador);
            temporizador = setTimeout(render, 50);
        }

        function iniciar() {
            data = construirTabla();

            controles.anio = categoria(C.ANIO, 'anio', false, true);
            controles.grado = categoria(C.GRADO, 'grado', true, false);
            controles.grupo = categoria(C.GRUPO, 'grupo', true, false);
            controles.genero = categoria(C.GENERO, 'genero', true, true);
            controles.edad = rango(C.EDAD, 'edad');
            controles.antiguedad = rango(C.ANTIGUEDAD, 'antiguedad');
            controles.anio.setState({ selectedValues: [ANIO_ACTUAL] });
            controles.anio.setOption('ui', { allowMultiple: false, allowNone: false, allowTyping: false, label: '' });

            // Cada control recibe los datos ya filtrados por el anterior: así "Grupo" solo ofrece
            // los grupos de los grados elegidos. El auxiliar (oculto) cierra la cadena.
            var auxiliar = new google.visualization.ChartWrapper({ chartType: 'Table', containerId: 'ctl-helper' });
            dashboard = new google.visualization.Dashboard(document.getElementById('gr-dashboard'));
            dashboard.bind(controles.anio, controles.grado)
                     .bind(controles.grado, controles.grupo)
                     .bind(controles.grupo, controles.genero)
                     .bind(controles.genero, controles.edad)
                     .bind(controles.edad, controles.antiguedad)
                     .bind(controles.antiguedad, auxiliar);

            Object.keys(controles).forEach(function (k) {
                google.visualization.events.addListener(controles[k], 'statechange', programar);
            });
            google.visualization.events.addListener(dashboard, 'ready', programar);

            var redimensionar = null;
            window.addEventListener('resize', function () {
                clearTimeout(redimensionar);
                redimensionar = setTimeout(render, 200);
            });

            dashboard.draw(data);
        }

        // ---- Gráficas -----------------------------------------------------------

        var COLOR_GENERO = { 'Niñas': COLOR.primario, 'Niños': COLOR.oscuro, 'Sin dato': COLOR.gris };
        var ALTO = 320;

        function nuevo(tipo, id) { return new google.visualization[tipo](document.getElementById(id)); }

        function opciones(extra) {
            var o = {
                height: ALTO,
                fontSize: 12,
                legend: { position: 'bottom' },
                chartArea: { left: 56, right: 24, top: 16, bottom: 56 },
                colors: [COLOR.primario, COLOR.oscuro, COLOR.claro, COLOR.gris]
            };
            Object.keys(extra || {}).forEach(function (k) { o[k] = extra[k]; });
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
            nuevo('PieChart', 'g-genero').draw(g, opciones({ pieHole: 0.5, slices: slices, chartArea: { left: 16, right: 16, top: 16, bottom: 48 } }));
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
                chartArea: { left: 56, right: 56, top: 16, bottom: 56 }
            }));
        }

        function graficaPiramide(filas) {
            var g = google.visualization.data.group(vistaDe(conEdad(filas)), [C.EDAD], [
                { column: C.GENERO, aggregation: function (v) { return -contarValor('Niños')(v); }, type: 'number', label: 'Niños' },
                { column: C.GENERO, aggregation: contarValor('Niñas'), type: 'number', label: 'Niñas' }
            ]);
            var t = new google.visualization.DataTable();
            t.addColumn('string', 'Edad');
            t.addColumn('number', 'Niños');
            t.addColumn('number', 'Niñas');
            // Las edades mayores arriba, como en una pirámide.
            for (var i = g.getNumberOfRows() - 1; i >= 0; i--) {
                t.addRow([String(g.getValue(i, 0)), g.getValue(i, 1), g.getValue(i, 2)]);
            }
            new google.visualization.NumberFormat({ pattern: '#,##0;#,##0' }).format(t, 1);
            nuevo('BarChart', 'g-piramide').draw(t, opciones({
                isStacked: true,
                height: Math.max(ALTO, 24 * t.getNumberOfRows() + 80),
                colors: [COLOR_GENERO['Niños'], COLOR_GENERO['Niñas']],
                hAxis: { format: '#,##0;#,##0' },
                vAxis: { title: 'Edad (años)' },
                bar: { groupWidth: '85%' }
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

        function graficaGrupo(filas) {
            var g = contarPor(vistaDe(filas), C.GRUPO);
            g.sort([{ column: 1, desc: true }]);
            var pares = [];
            for (var i = 0; i < g.getNumberOfRows(); i++) { pares.push([g.getValue(i, 0), g.getValue(i, 1)]); }
            nuevo('BarChart', 'g-grupo').draw(tablaBarras(pares, 'Grupo'), opciones({
                legend: { position: 'none' },
                height: Math.max(ALTO, 26 * pares.length + 60),
                colors: [COLOR.primario],
                hAxis: { minValue: 0 },
                chartArea: { left: 80, right: 32, top: 16, bottom: 40 }
            }));
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
                chartArea: { left: 150, right: 32, top: 16, bottom: 40 }
            }));
        }

        function graficaAntiguedad(filas) {
            var cuentas = { '1 año': 0, '2 años': 0, '3 o más años': 0 };
            filas.forEach(function (i) {
                var a = data.getValue(i, C.ANTIGUEDAD);
                if (a === null || a < 1) { return; }
                cuentas[a === 1 ? '1 año' : (a === 2 ? '2 años' : '3 o más años')]++;
            });
            var pares = Object.keys(cuentas).map(function (k) { return [k, cuentas[k]]; });
            nuevo('ColumnChart', 'g-antiguedad').draw(tablaBarras(pares, 'Antigüedad'), opciones({
                legend: { position: 'none' },
                colors: [COLOR.primario],
                bar: { groupWidth: '95%' },
                vAxis: { minValue: 0 }
            }));
        }

        function graficaEvolucion(filasEvolucion) {
            var g = contarPor(vistaDe(filasEvolucion), C.ANIO);
            var pares = [];
            for (var i = 0; i < g.getNumberOfRows(); i++) { pares.push([g.getValue(i, 0), g.getValue(i, 1)]); }
            nuevo('LineChart', 'g-evolucion').draw(tablaBarras(pares, 'Año'), opciones({
                legend: { position: 'none' },
                colors: [COLOR.primario],
                pointSize: 6,
                lineWidth: 3,
                vAxis: { minValue: 0 }
            }));
        }

        function dibujarGraficas(filas, filasEvolucion) {
            graficaGenero(filas);
            graficaGrado(filas);
            graficaPiramide(filas);
            graficaExtraedad(filas);
            graficaGrupo(filas);
            graficaLocalidad(filas);
            graficaAntiguedad(filas);
            graficaEvolucion(filasEvolucion);
        }

        google.charts.load('current', { packages: ['corechart', 'controls'], language: 'es' });
        google.charts.setOnLoadCallback(iniciar);
    })();
    </script>
    <?php
}
