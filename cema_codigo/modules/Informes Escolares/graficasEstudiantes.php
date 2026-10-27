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

        function dibujarGraficas(filas, filasEvolucion) {
            // Las gráficas se agregan en el siguiente commit.
        }

        google.charts.load('current', { packages: ['corechart', 'controls'], language: 'es' });
        google.charts.setOnLoadCallback(iniciar);
    })();
    </script>
    <?php
}
