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
        .gr-wrap { color: #1F2937; max-width: 1180px; }
        .gr-wrap h2 { margin: 0 0 4px; color: #1E3A8A; }

        /* Filtros como una frase: "Mostrando [niñas y niños] de [todos los grados] en [2026]" */
        .gr-frase { display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px; margin: 4px 0 20px; padding: 12px 0; border-bottom: 1px solid #E3EAF8; font-size: 1.08em; color: #4B5563; line-height: 2; }
        .gr-frase select { appearance: none; -webkit-appearance: none; -moz-appearance: none; border: 0; border-bottom: 2px dotted #9DB8F5; border-radius: 0; background: transparent url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' fill='none' stroke='%232555B8' stroke-width='1.6' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 2px center; padding: 0 16px 0 2px; margin: 0; font: inherit; font-weight: 700; color: #2555B8; cursor: pointer; }
        .gr-frase select:hover { border-bottom-color: #3575EF; color: #1E3A8A; }
        .gr-frase select:focus-visible { outline: 2px solid #3575EF; outline-offset: 2px; }
        .gr-frase select.gr-activo { border-bottom-style: solid; border-bottom-color: #3575EF; }
        .gr-limpiar { display: none; margin-left: 8px; border: 0; background: none; padding: 0; font: inherit; font-size: .85em; color: #6b7280; text-decoration: underline; cursor: pointer; }
        .gr-limpiar:hover { color: #1E3A8A; }

        /* Cifras en una sola franja */
        .gr-cifras { display: grid; grid-template-columns: repeat(4, 1fr); margin-bottom: 22px; border: 1px solid #E3EAF8; border-radius: 10px; background: #fff; }
        .gr-cifra { padding: 14px 20px; border-left: 1px solid #E3EAF8; }
        .gr-cifra:first-child { border-left: 0; }
        .gr-cifra b { display: block; font-size: 2em; line-height: 1.1; font-weight: 700; color: #1E3A8A; font-variant-numeric: tabular-nums; }
        .gr-cifra:first-child b { color: #3575EF; }
        .gr-cifra span { font-size: .85em; color: #6b7280; }

        .gr-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .gr-card { background: #fff; border: 1px solid #E3EAF8; border-radius: 10px; padding: 18px 20px 10px; min-width: 0; }
        .gr-card.gr-wide { grid-column: 1 / -1; }
        .gr-card h3 { margin: 0 0 6px; font-size: 1.05em; font-weight: 700; color: #1E3A8A; }
        .gr-lectura { margin: 0 0 6px; font-size: .95em; color: #1F2937; line-height: 1.45; }
        .gr-lectura strong { color: #1E3A8A; }
        .gr-nota { margin: 4px 0 6px; font-size: .8em; color: #6b7280; }
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

        <div class="gr-frase">
            <span>Mostrando</span>
            <select id="f-genero" aria-label="Género">
                <option value="">niñas y niños</option>
                <option value="Niñas">solo niñas</option>
                <option value="Niños">solo niños</option>
            </select>
            <span>de</span>
            <select id="f-grado" aria-label="Grado"><option value="">todos los grados</option></select>
            <span>en el año escolar</span>
            <select id="f-anio" aria-label="Año escolar"></select>
            <button type="button" class="gr-limpiar" id="f-limpiar">quitar filtros</button>
        </div>

        <div class="gr-cifras">
            <div class="gr-cifra"><b id="kpi-total">–</b><span>estudiantes</span></div>
            <div class="gr-cifra"><b id="kpi-ninas">–</b><span id="kpi-ninas-sub">niñas</span></div>
            <div class="gr-cifra"><b id="kpi-ninos">–</b><span id="kpi-ninos-sub">niños</span></div>
            <div class="gr-cifra"><b id="kpi-edad">–</b><span>edad promedio (años)</span></div>
        </div>

        <div class="gr-empty" id="gr-empty">Sin datos para los filtros seleccionados</div>

        <div class="gr-grid" id="gr-graficas">
            <div class="gr-card"><h3>¿Cuántos estudiantes hay en cada grado?</h3><p class="gr-lectura" id="l-grado"></p><div class="gr-chart" id="g-grado"></div></div>
            <div class="gr-card"><h3>¿Cuántas niñas y cuántos niños?</h3><p class="gr-lectura" id="l-genero"></p><div class="gr-chart" id="g-genero"></div></div>
            <div class="gr-card"><h3>¿Cuántos están por encima de la edad de su grado?</h3><p class="gr-lectura" id="l-extraedad"></p><p class="gr-nota" id="n-extraedad"></p><div class="gr-chart" id="g-extraedad"></div></div>
            <div class="gr-card"><h3>¿De qué localidades vienen?</h3><p class="gr-lectura" id="l-localidad"></p><p class="gr-nota">Las localidades con menos de 5 estudiantes se suman en «Otras».</p><div class="gr-chart" id="g-localidad"></div></div>
            <div class="gr-card gr-wide"><h3>¿Cómo ha cambiado la matrícula con los años?</h3><p class="gr-lectura" id="l-evolucion"></p><div class="gr-chart" id="g-evolucion"></div></div>
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
        var COLOR = { primario: '#3575EF', oscuro: '#1E3A8A', claro: '#9DB8F5', gris: '#9CA3AF', alerta: '#D97706' };
        var COLOR_GENERO = { 'Niñas': COLOR.primario, 'Niños': COLOR.oscuro, 'Sin dato': COLOR.gris };

        // Columnas del DataTable
        var C = { ANIO: 0, GRADO: 1, ORDEN: 2, GRUPO: 3, GENERO: 4, EDAD: 5, LOCALIDAD: 6, NACIONALIDAD: 7, ANTIGUEDAD: 8 };
        var ALTO = 300;

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
                return [r.anio || 'Sin dato', r.grado || 'Sin dato', r.orden, r.grupo || 'Sin dato', r.genero || 'Sin dato',
                        r.edad === null || r.edad === undefined ? null : r.edad, r.localidad || 'Sin dato', r.nacionalidad || 'Sin dato', r.antiguedad];
            }));
            return t;
        }

        // ---- Filtros ---------------------------------------------------------

        function filtros(incluirAnio) {
            var f = [];
            if (incluirAnio) { f.push({ column: C.ANIO, value: estado.anio }); }
            if (estado.grado) { f.push({ column: C.GRADO, value: estado.grado }); }
            if (estado.genero) { f.push({ column: C.GENERO, value: estado.genero }); }
            return f;
        }

        function opcion(sel, valor, etiqueta) {
            var o = document.createElement('option');
            o.value = valor;
            o.textContent = etiqueta;
            sel.appendChild(o);
        }

        function iniciarFiltros() {
            // Años en el orden de la consulta (cronológico); grados en orden numérico.
            var anios = [], vistos = {};
            for (var i = 0; i < data.getNumberOfRows(); i++) {
                var a = data.getValue(i, C.ANIO);
                if (!vistos[a]) { vistos[a] = true; anios.push(a); }
            }
            var selAnio = $('f-anio');
            anios.slice().reverse().forEach(function (a) { opcion(selAnio, a, a); });
            estado.anio = anios.indexOf(ANIO_ACTUAL) !== -1 ? ANIO_ACTUAL : anios[anios.length - 1];
            selAnio.value = estado.anio;

            var g = google.visualization.data.group(data, [C.ORDEN, C.GRADO],
                [{ column: C.GRADO, aggregation: google.visualization.data.count, type: 'number' }]);
            var selGrado = $('f-grado'), grados = {};
            for (var j = 0; j < g.getNumberOfRows(); j++) {
                var nombre = g.getValue(j, 1);
                if (!grados[nombre]) { grados[nombre] = true; opcion(selGrado, nombre, nombre); }
            }

            [['f-anio', 'anio'], ['f-grado', 'grado'], ['f-genero', 'genero']].forEach(function (par) {
                $(par[0]).addEventListener('change', function () { estado[par[1]] = this.value; render(); });
            });
            $('f-limpiar').addEventListener('click', function () {
                estado.grado = ''; estado.genero = '';
                $('f-grado').value = ''; $('f-genero').value = '';
                render();
            });
        }

        function marcarFiltros() {
            $('f-grado').classList.toggle('gr-activo', !!estado.grado);
            $('f-genero').classList.toggle('gr-activo', !!estado.genero);
            mostrar('f-limpiar', !!(estado.grado || estado.genero), 'inline');
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
                lectura('l-grado', ['El grado con más estudiantes es ', { b: mayor.etiqueta + ' (' + num(mayor.valor) + ')' },
                    ' y el que tiene menos es ', { b: menor.etiqueta + ' (' + num(menor.valor) + ')' }, '.']);
            }
            nuevo('ColumnChart', 'g-grado').draw(tablaBarras('Grado', barras), opciones({ vAxis: { textPosition: 'none', gridlines: { color: 'transparent' } }, chartArea: { left: 8, right: 8, top: 24, bottom: 40 } }));
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
            var partes = ['Hay ', { b: num(cuentas['Niñas']) + ' niñas (' + pct(cuentas['Niñas'], total) + ')' },
                          ' y ', { b: num(cuentas['Niños']) + ' niños (' + pct(cuentas['Niños'], total) + ')' }, '.'];
            if (cuentas['Sin dato']) { partes.push(' A ' + num(cuentas['Sin dato']) + ' no se les registró el género.'); }
            lectura('l-genero', partes);
            nuevo('PieChart', 'g-genero').draw(t, opciones({
                pieHole: 0.55, slices: slices, pieSliceText: 'percentage', pieSliceBorderColor: '#fff',
                pieSliceTextStyle: { color: '#fff', bold: true, fontSize: 13 },
                legend: { position: 'right', alignment: 'center', textStyle: { color: '#1F2937', fontSize: 13 } },
                chartArea: { left: 8, right: 8, top: 16, bottom: 16 }
            }));
        }

        function graficaExtraedad(filas) {
            var grados = Object.keys(EDAD_ESPERADA_POR_GRADO).map(Number).sort(function (a, b) { return a - b; });
            if (grados.length) {
                var primero = grados[0], ultimo = grados[grados.length - 1];
                $('n-extraedad').textContent = 'Edad esperada: ' + primero + '° → ' + EDAD_ESPERADA_POR_GRADO[primero] + ' años, ' +
                    (primero + 1) + '° → ' + EDAD_ESPERADA_POR_GRADO[primero + 1] + ' años… hasta ' + ultimo + '° → ' + EDAD_ESPERADA_POR_GRADO[ultimo] +
                    ' años. Se cuenta con extraedad a quien tiene ' + MARGEN_EXTRAEDAD + ' años o más por encima.';
            }
            var vista = vistaDe(filas.filter(function (i) { return data.getValue(i, C.EDAD) !== null; }));
            vista.setColumns([C.ORDEN, C.GRADO, {
                type: 'string', label: 'Categoría',
                calc: function (dt, fila) {
                    var esperada = EDAD_ESPERADA_POR_GRADO[numeroGrado(dt.getValue(fila, C.GRADO), dt.getValue(fila, C.ORDEN))];
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
            var partes = [{ b: num(casos) + ' de ' + num(base) + ' estudiantes (' + pct(casos, base) + ')' }, ' tienen más edad de la esperada para su grado.'];
            if (peor) { partes.push(' El grado con más casos es ', { b: peor.etiqueta }, '.'); }
            lectura('l-extraedad', partes);
            nuevo('BarChart', 'g-extraedad').draw(tablaBarras('Grado', barras), opciones({
                height: Math.max(ALTO, 30 * barras.length + 40),
                hAxis: { format: '#%', minValue: 0, maxValue: 0.25, textStyle: { color: '#6b7280' }, gridlines: { color: '#F1F4FA' } },
                vAxis: { gridlines: { color: 'transparent' } },
                chartArea: { left: 80, right: 70, top: 8, bottom: 32 }
            }));
        }

        // Privacidad: las localidades con menos de MINIMO_CATEGORIA estudiantes (y todo lo que
        // quede fuera del Top 10) se suman en "Otras" para no identificar a nadie.
        function graficaLocalidad(filas) {
            var g = google.visualization.data.group(vistaDe(filas), [C.LOCALIDAD],
                [{ column: C.LOCALIDAD, aggregation: google.visualization.data.count, type: 'number' }]);
            g.sort([{ column: 1, desc: true }]);
            var total = filas.length, barras = [], otras = 0;
            for (var i = 0; i < g.getNumberOfRows(); i++) {
                var n = g.getValue(i, 1);
                if (barras.length < 10 && n >= MINIMO_CATEGORIA) {
                    barras.push({ etiqueta: g.getValue(i, 0), valor: n, texto: num(n), ayuda: g.getValue(i, 0) + ': ' + num(n) + ' estudiantes (' + pct(n, total) + ')' });
                } else { otras += n; }
            }
            if (otras > 0) { barras.push({ etiqueta: 'Otras', valor: otras, texto: num(otras), ayuda: 'Otras localidades: ' + num(otras) + ' estudiantes', color: COLOR.gris }); }
            var top = barras[0];
            if (top && top.etiqueta !== 'Otras') {
                lectura('l-localidad', ['La mayoría vive en ', { b: top.etiqueta }, ': ', { b: num(top.valor) + ' estudiantes (' + pct(top.valor, total) + ')' }, '.']);
            } else {
                lectura('l-localidad', ['Ninguna localidad reúne 5 estudiantes o más con estos filtros.']);
            }
            nuevo('BarChart', 'g-localidad').draw(tablaBarras('Localidad', barras), opciones({
                height: Math.max(ALTO, 30 * barras.length + 40),
                hAxis: { textPosition: 'none', gridlines: { color: 'transparent' } },
                vAxis: { gridlines: { color: 'transparent' } },
                chartArea: { left: 150, right: 50, top: 8, bottom: 8 }
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
                lectura('l-evolucion', ['Entre ', { b: a.etiqueta }, ' y ', { b: b.etiqueta }, ' la matrícula pasó de ', { b: num(a.valor) },
                    ' a ', { b: num(b.valor) }, ' estudiantes (' + (d > 0 ? 'subió ' + num(d) : d < 0 ? 'bajó ' + num(-d) : 'sin cambio') + '). La barra resaltada es el año que estás viendo.']);
            }
            nuevo('ColumnChart', 'g-evolucion').draw(tablaBarras('Año', barras), opciones({
                height: 260,
                bar: { groupWidth: '45%' },
                vAxis: { textPosition: 'none', gridlines: { color: 'transparent' } },
                chartArea: { left: 8, right: 8, top: 24, bottom: 32 }
            }));
        }

        function render() {
            marcarFiltros();
            var filas = data.getFilteredRows(filtros(true));
            actualizarCifras(filas);
            var hayDatos = filas.length > 0;
            mostrar('gr-empty', !hayDatos, 'block');
            mostrar('gr-graficas', hayDatos, 'grid');
            if (!hayDatos) { return; }
            graficaGrado(filas);
            graficaGenero(filas);
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
