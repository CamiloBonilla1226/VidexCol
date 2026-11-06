<?php
/**
 * Control financiero y seguimiento - helpers de interfaz compartidos por
 * graphs_rentabilidad.php y graphs_cartera.php.
 *
 * Estilo: minimalista, sobrio, responsive. Colores de series validados
 * (azul = ingresos, naranja = costos directos, violeta = auditoria,
 * magenta = gastos globales, aqua = resultado).
 */

function gf_h($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function gf_money($v)
{
    $v = (float) $v;
    $s = number_format(abs($v), 0, ',', '.');
    return ($v < 0 ? '-$' : '$') . $s;
}

/** Version corta para textos de ayuda: $4,2 M / $350 mil. */
function gf_money_corto($v)
{
    $v = (float) $v;
    $a = abs($v);
    $sig = $v < 0 ? '-' : '';
    if ($a >= 1000000) { return $sig . '$' . number_format($a / 1000000, 1, ',', '.') . ' M'; }
    if ($a >= 1000) { return $sig . '$' . number_format($a / 1000, 0, ',', '.') . ' mil'; }
    return $sig . '$' . number_format($a, 0, ',', '.');
}

function gf_pct($v)
{
    return $v === null ? '—' : number_format((float) $v, 1, ',', '.') . ' %';
}

function gf_fecha($f)
{
    $f = (string) $f;
    if ($f === '' || strpos($f, '0000') === 0) {
        return '—';
    }
    $t = strtotime($f);
    return $t ? date('d/m/Y', $t) : '—';
}

function gf_fecha_hora($f)
{
    $t = strtotime((string) $f);
    return $t ? date('d/m/Y H:i', $t) : '—';
}

/** '2026-03' -> 'mar 26' */
function gf_mes_corto($ym)
{
    $m = ['01' => 'ene', '02' => 'feb', '03' => 'mar', '04' => 'abr', '05' => 'may', '06' => 'jun',
          '07' => 'jul', '08' => 'ago', '09' => 'sep', '10' => 'oct', '11' => 'nov', '12' => 'dic'];
    if (!preg_match('/^(\d{4})-(\d{2})$/', (string) $ym, $x)) {
        return (string) $ym;
    }
    return (isset($m[$x[2]]) ? $m[$x[2]] : $x[2]) . ' ' . substr($x[1], 2);
}

function gf_clase_valor($v)
{
    if ($v > 0) { return 'gf-pos'; }
    if ($v < 0) { return 'gf-neg'; }
    return '';
}

function gf_resumir($txt, $max = 140)
{
    $txt = trim(preg_replace('/\s+/', ' ', strip_tags((string) $txt)));
    if (function_exists('mb_strlen') && mb_strlen($txt, 'UTF-8') > $max) {
        return mb_substr($txt, 0, $max, 'UTF-8') . '…';
    }
    return $txt;
}

/** Control de acceso propio de la pagina (ademas de la compuerta de index.php). */
function gf_requerir_acceso($doc)
{
    $perfil = (int) ($_SESSION['perfil'] ?? 0);
    if (in_array($perfil, [3, 4, 160], true)) {
        die('<h1>No esta autorizado para ver esta informaci&oacute;n</h1>');
    }
    if ((int) ($_SESSION['superusuario'] ?? 0) === 1) {
        return;
    }
    $db = new DBbase_Sql;
    $db->connect();
    $r = mysqli_query($db->Link_ID, "SELECT 1 FROM usuarios_menu_graphs ug
            JOIN menu_graphs mg ON mg.id = ug.idMenu
            WHERE ug.idUsuario = " . (int) ($_SESSION['id'] ?? 0) . "
            AND mg.php = '" . mysqli_real_escape_string($db->Link_ID, $doc) . "' LIMIT 1");
    if (!$r || mysqli_num_rows($r) === 0) {
        die('NO esta autorizado a ver esta grafica.');
    }
}

/** Opciones de ambito validadas. */
function gf_ambito($v)
{
    return in_array($v, ['negocio', 'personal', 'todo'], true) ? $v : 'negocio';
}

/** Años para el selector: del actual hasta 2017. */
function gf_anios()
{
    $a = [];
    for ($y = (int) date('Y'); $y >= 2017; $y--) {
        $a[] = (string) $y;
    }
    return $a;
}

function gf_nombre_ambito($ambito)
{
    $n = ['negocio' => 'Solo negocio', 'personal' => 'Solo personal / familiar', 'todo' => 'Todo (negocio + personal)'];
    return $n[$ambito];
}

// ---------------------------------------------------------------------------
// Estilos
// ---------------------------------------------------------------------------

function gf_estilos()
{
    ?>
<style>
.gf-wrap {
    --gf-ink: #0f172a; --gf-text: #334155; --gf-muted: #64748b; --gf-line: #e5e7eb; --gf-soft: #f8fafc; --gf-surface: #ffffff;
    --gf-azul: #2a78d6; --gf-naranja: #eb6834; --gf-violeta: #6250d6; --gf-magenta: #e87ba4; --gf-aqua: #1baf7a; --gf-ambar: #eda100;
    --gf-pos: #15803d; --gf-neg: #b91c1c; --gf-radius: 10px;
    max-width: 1320px; margin: 0 auto; padding: 8px clamp(12px, 3vw, 28px) 48px;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: var(--gf-text); font-size: 14px; line-height: 1.45;
}
.gf-wrap *, .gf-wrap *:before, .gf-wrap *:after { box-sizing: border-box; }
.gf-wrap a { color: var(--gf-azul); text-decoration: none; }
.gf-wrap a:hover { text-decoration: underline; }

/* Encabezado */
.gf-hero { background: none; color: var(--gf-ink); padding: 8px 0 14px; margin: 0 0 4px; border-radius: 0; border-bottom: 1px solid var(--gf-line); }
.gf-hero h1 { margin: 0; font-size: clamp(20px, 3.2vw, 26px); font-weight: 650; letter-spacing: -.01em; color: var(--gf-ink); }
.gf-hero p { margin: 4px 0 0; color: var(--gf-muted); font-size: 13px; opacity: 1; }
.gf-hero .gf-tag { vertical-align: middle; }

/* Tarjetas */
.gf-card { background: var(--gf-surface); border: 1px solid var(--gf-line); border-radius: var(--gf-radius); padding: 16px 18px; margin: 14px 0 0; box-shadow: none; }
.gf-card > h2, .gf-card header h2 { margin: 0; font-size: 13px; font-weight: 650; color: var(--gf-ink); letter-spacing: .01em; }
.gf-card > h2 { margin-bottom: 12px; }
.gf-card h2 small { font-weight: 400; color: var(--gf-muted); font-size: 12px; margin-left: 6px; }
.gf-card header { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; }

/* Filtros */
details.gf-filtros-wrap { margin: 14px 0 0; }
details.gf-filtros-wrap > summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--gf-muted); padding: 4px 0; }
details.gf-filtros-wrap > summary::-webkit-details-marker { display: none; }
details.gf-filtros-wrap > summary:before { content: '▸'; font-size: 11px; transition: transform .15s; }
details.gf-filtros-wrap[open] > summary:before { transform: rotate(90deg); }
.gf-filtros { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px 12px; align-items: end; margin-top: 8px; }
.gf-field label { display: block; font-size: 11px; font-weight: 600; color: var(--gf-muted); margin-bottom: 4px; }
.gf-field input, .gf-field select { width: 100%; height: 36px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: var(--gf-ink); font-size: 13px; }
.gf-field input:focus, .gf-field select:focus { outline: 2px solid rgba(42,120,214,.25); border-color: var(--gf-azul); }
.gf-field input[type=checkbox] { width: auto; height: auto; }

/* Botones */
.gf-btn { display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 14px; border-radius: 8px; border: 1px solid transparent; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none !important; line-height: 1; }
.gf-btn-primary { background: var(--gf-ink); color: #fff !important; }
.gf-btn-ghost, .gf-btn-ok, .gf-btn-pdf { background: #fff; color: var(--gf-text) !important; border-color: #d1d5db; }
.gf-btn-ok i { color: #15803d; } .gf-btn-pdf i { color: #b91c1c; }
.gf-btn:hover { filter: none; background: var(--gf-soft); }
.gf-btn-primary:hover { background: #1e293b; }
.gf-acciones { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; align-items: center; }
.gf-acciones .sep { flex: 1; }

/* KPIs */
.gf-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 0; margin: 14px 0 0; border: 1px solid var(--gf-line); border-radius: var(--gf-radius); background: var(--gf-surface); overflow: hidden; }
.gf-kpi { background: none; border: 0; border-right: 1px solid var(--gf-line); border-bottom: 1px solid var(--gf-line); border-radius: 0; padding: 14px 16px; margin: 0 -1px -1px 0; }
.gf-kpi .t { font-size: 12px; font-weight: 500; color: var(--gf-muted); display: flex; align-items: center; gap: 7px; text-transform: none; letter-spacing: 0; }
.gf-kpi .t:before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; flex: none; }
.gf-kpi .v { font-size: clamp(19px, 2.4vw, 24px); font-weight: 650; margin-top: 6px; color: var(--gf-ink); letter-spacing: -.01em; }
.gf-kpi .s { font-size: 12px; color: var(--gf-muted); margin-top: 2px; }
.gf-kpi.azul .t:before { background: var(--gf-azul); } .gf-kpi.verde .t:before { background: var(--gf-aqua); }
.gf-kpi.rojo .t:before { background: var(--gf-neg); } .gf-kpi.naranja .t:before { background: var(--gf-naranja); }
.gf-kpi.morado .t:before { background: var(--gf-violeta); } .gf-kpi.gris .t:before { background: #94a3b8; }
.gf-kpi.magenta .t:before { background: var(--gf-magenta); } .gf-kpi.ambar .t:before { background: var(--gf-ambar); }
.gf-pos { color: var(--gf-pos) !important; } .gf-neg { color: var(--gf-neg) !important; }

/* Rejillas */
.gf-grid2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 14px; }
.gf-grid3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 14px; }
.gf-grid2 > .gf-card, .gf-grid3 > .gf-card { margin-top: 0; }

/* Graficas */
.gf-chart { width: 100%; height: 280px; min-height: 0; }
.gf-legend { display: flex; flex-wrap: wrap; gap: 4px 14px; margin: 2px 0 6px; font-size: 12px; color: var(--gf-text); }
.gf-legend span { display: inline-flex; align-items: center; gap: 6px; }
.gf-legend i { width: 9px; height: 9px; border-radius: 2px; display: inline-block; flex: none; }
.gf-legend b { font-weight: 600; color: var(--gf-ink); }
.gf-info-btn { width: 24px; height: 24px; border-radius: 50%; border: 1px solid #cbd5e1; background: #fff; color: var(--gf-muted); font: italic 700 13px/1 Georgia, serif; cursor: pointer; padding: 0; flex: none; }
.gf-info-btn:hover, .gf-info-btn[aria-expanded=true] { background: var(--gf-ink); color: #fff; border-color: var(--gf-ink); }
.gf-info { background: var(--gf-soft); border: 1px solid var(--gf-line); border-radius: 8px; padding: 10px 12px; margin: 2px 0 8px; font-size: 12.5px; }
.gf-info[hidden] { display: none; }
.gf-info dl { margin: 0; display: grid; grid-template-columns: 92px 1fr; gap: 5px 10px; }
.gf-info dt { font-weight: 650; color: var(--gf-ink); }
.gf-info dd { margin: 0; color: var(--gf-text); }
.gf-vacio { color: var(--gf-muted); text-align: center; padding: 60px 10px; font-size: 13px; }

/* Tablas */
.gf-tablewrap { overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0 -4px; padding: 0 4px; }
table.gf-table { width: 100%; border-collapse: collapse; font-size: 13px; }
table.gf-table th { background: none; color: var(--gf-muted); font-weight: 600; font-size: 12px; text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--gf-line); white-space: nowrap; position: static; }
table.gf-table th.sortable { cursor: pointer; user-select: none; }
table.gf-table th.sortable:hover { color: var(--gf-ink); }
table.gf-table td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; color: var(--gf-text); }
table.gf-table tbody tr:hover td { background: var(--gf-soft); }
table.gf-table td.n, table.gf-table th.n { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
table.gf-table tfoot td { font-weight: 650; color: var(--gf-ink); border-top: 1px solid #cbd5e1; border-bottom: 0; background: none; }

/* Etiquetas */
.gf-tag { display: inline-block; padding: 1px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #f1f5f9; color: #475569; white-space: nowrap; border: 1px solid transparent; }
.gf-tag.verde { background: #ecfdf5; color: #166534; } .gf-tag.amarillo { background: #fefce8; color: #854d0e; }
.gf-tag.rojo { background: #fef2f2; color: #991b1b; } .gf-tag.azul { background: #eff6ff; color: #1e40af; }
.gf-tag.morado { background: #f5f3ff; color: #5b21b6; }
.gf-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 6px; }
.gf-dot.verde { background: #0ca30c; } .gf-dot.amarillo { background: #fab219; } .gf-dot.rojo { background: #d03b3b; }

/* Avisos y notas */
.gf-nota { font-size: 12px; color: var(--gf-muted); line-height: 1.5; }
.gf-aviso, .gf-error { padding: 9px 12px; border-radius: 8px; margin: 12px 0 0; font-size: 13px; border: 1px solid; }
.gf-aviso { background: #fffbeb; border-color: #fde68a; color: #92400e; }
.gf-error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
details.gf-det { margin-top: 14px; } details.gf-det > summary { cursor: pointer; font-size: 13px; font-weight: 600; color: var(--gf-muted); }
details.gf-det ul { margin: 8px 0 0; padding-left: 18px; }

/* Estado de resultados */
.gf-resultado { width: 100%; border-collapse: collapse; }
.gf-resultado td { padding: 7px 0; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; }
.gf-resultado td:last-child { text-align: right; font-variant-numeric: tabular-nums; }
.gf-resultado tr.total td { font-weight: 650; color: var(--gf-ink); border-bottom: 1px solid #cbd5e1; }

/* Ficha */
.gf-timeline { border-left: 2px solid var(--gf-line); margin-left: 6px; padding-left: 16px; }
.gf-tl-item { position: relative; margin-bottom: 14px; }
.gf-tl-item:before { content: ''; position: absolute; left: -22px; top: 5px; width: 9px; height: 9px; border-radius: 50%; background: var(--gf-azul); box-shadow: 0 0 0 3px #fff; }
.gf-tl-item .h { font-size: 12px; color: var(--gf-muted); margin-bottom: 2px; }
.gf-tl-item .b { font-size: 13.5px; white-space: pre-wrap; color: var(--gf-text); }
.gf-kv { display: grid; grid-template-columns: 130px 1fr; gap: 6px 12px; font-size: 13.5px; margin: 0; }
.gf-kv dt { color: var(--gf-muted); } .gf-kv dd { margin: 0; color: var(--gf-ink); word-break: break-word; }

/* Responsive */
@media (max-width: 1100px) { .gf-grid3 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 900px) {
    .gf-grid2, .gf-grid3 { grid-template-columns: minmax(0, 1fr); }
    .gf-chart { height: 250px; }
    .gf-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .gf-t-proy th:nth-child(3), .gf-t-proy td:nth-child(3), .gf-t-proy th:nth-child(5), .gf-t-proy td:nth-child(5),
    .gf-t-proy th:nth-child(6), .gf-t-proy td:nth-child(6), .gf-t-proy th:nth-child(7), .gf-t-proy td:nth-child(7),
    .gf-t-proy th:nth-child(12), .gf-t-proy td:nth-child(12), .gf-t-proy th:nth-child(13), .gf-t-proy td:nth-child(13),
    .gf-t-proy th:nth-child(14), .gf-t-proy td:nth-child(14), .gf-t-proy tfoot { display: none; }
    .gf-t-cart th:nth-child(2), .gf-t-cart td:nth-child(2), .gf-t-cart th:nth-child(3), .gf-t-cart td:nth-child(3),
    .gf-t-cart th:nth-child(4), .gf-t-cart td:nth-child(4), .gf-t-cart th:nth-child(11), .gf-t-cart td:nth-child(11),
    .gf-t-cart th:nth-child(13), .gf-t-cart td:nth-child(13) { display: none; }
}
@media (max-width: 600px) {
    .gf-wrap { padding-left: 10px; padding-right: 10px; }
    .gf-card { padding: 13px 12px; }
    .gf-filtros { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .gf-kv { grid-template-columns: 1fr; gap: 0; } .gf-kv dd { margin-bottom: 8px; }
    .gf-info dl { grid-template-columns: 1fr; gap: 0; } .gf-info dd { margin-bottom: 6px; }
    .gf-t-proy th:nth-child(4), .gf-t-proy td:nth-child(4), .gf-t-proy th:nth-child(11), .gf-t-proy td:nth-child(11),
    .gf-t-proy th:nth-child(16), .gf-t-proy td:nth-child(16),
    .gf-t-cart th:nth-child(5), .gf-t-cart td:nth-child(5), .gf-t-cart th:nth-child(6), .gf-t-cart td:nth-child(6),
    .gf-t-cart th:nth-child(10), .gf-t-cart td:nth-child(10) { display: none; }
    .gf-btn { flex: 1 1 auto; justify-content: center; }
}

/* Impresion / PDF */
@media print {
    @page { size: A4 landscape; margin: 10mm; }
    aside, .sidebar-backdrop, .videx-footer, .gf-noprint, nav, .sidebar, .gf-info-btn, .gf-info, details.gf-filtros-wrap { display: none !important; }
    .main-content, main { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
    body { background: #fff !important; }
    .gf-wrap { max-width: 100%; padding: 0; font-size: 11px; }
    .gf-card, .gf-kpis { break-inside: avoid; page-break-inside: avoid; }
    .gf-grid2, .gf-grid3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .gf-chart { height: 230px; }
    .gf-tablewrap { overflow: visible; }
    table.gf-table { font-size: 10px; }
    .gf-tag, .gf-dot, .gf-kpi .t:before, .gf-legend i { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
<?php
}

// ---------------------------------------------------------------------------
// Componentes
// ---------------------------------------------------------------------------

/**
 * Tarjeta de grafica con boton (i) que despliega una explicacion corta.
 * $info: ['que' => ..., 'leer' => ..., 'ojo' => ..., 'dato' => ...] (todas opcionales, textos cortos).
 */
function gf_grafica($id, $titulo, array $info, $alto = 280)
{
    $filas = [
        'que' => 'Qué muestra', 'leer' => 'Cómo leerla', 'ojo' => 'Ojo con', 'dato' => 'En este período',
    ];
    ?>
<section class="gf-card">
    <header>
        <h2><?= gf_h($titulo); ?></h2>
        <button type="button" class="gf-info-btn gf-noprint" data-gf-info="<?= gf_h($id); ?>" aria-expanded="false"
                aria-controls="<?= gf_h($id); ?>-info" title="¿Qué dice esta gráfica?" aria-label="Información sobre la gráfica «<?= gf_h($titulo); ?>»">i</button>
    </header>
    <div class="gf-info" id="<?= gf_h($id); ?>-info" hidden>
        <dl>
        <?php foreach ($filas as $k => $rotulo) { if (!empty($info[$k])) { ?>
            <dt><?= gf_h($rotulo); ?></dt><dd><?= gf_h($info[$k]); ?></dd>
        <?php } } ?>
        </dl>
    </div>
    <div class="gf-legend" id="<?= gf_h($id); ?>-leg"></div>
    <div id="<?= gf_h($id); ?>" class="gf-chart" style="height:<?= (int) $alto; ?>px"></div>
</section>
<?php
}

/** Script comun: tema de graficas, leyendas, boton (i) y ordenar tablas. Imprimir UNA vez por pagina. */
function gf_script_base()
{
    ?>
<script>
window.GF = (function () {
    var C = {azul: '#2a78d6', naranja: '#eb6834', violeta: '#6250d6', magenta: '#e87ba4', aqua: '#1baf7a', ambar: '#eda100',
             ok: '#0ca30c', aviso: '#fab219', critico: '#d03b3b', muted: '#64748b', grid: '#e5e7eb', ink: '#0f172a'};
    function merge(a, b) {
        for (var k in b) {
            if (b[k] && typeof b[k] === 'object' && !Array.isArray(b[k]) && a[k] && typeof a[k] === 'object') { merge(a[k], b[k]); }
            else { a[k] = b[k]; }
        }
        return a;
    }
    function base(extra) {
        var o = {
            fontName: 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif', fontSize: 11, backgroundColor: 'transparent',
            legend: {position: 'none'}, animation: {duration: 0},
            chartArea: {left: 44, top: 8, right: 6, bottom: 26, width: '100%', height: '82%'},
            hAxis: {textStyle: {color: C.muted}, baselineColor: C.grid, gridlines: {color: 'transparent'}},
            vAxis: {textStyle: {color: C.muted}, gridlines: {color: C.grid}, minorGridlines: {color: 'transparent'}, baselineColor: C.grid, format: 'short'},
            tooltip: {textStyle: {fontSize: 12}}
        };
        return merge(o, extra || {});
    }
    function dinero(vp) {
        return new google.visualization.NumberFormat({prefix: '$', groupingSymbol: '.', decimalSymbol: ',', fractionDigits: 0});
    }
    function leyenda(id, items) {
        var el = document.getElementById(id + '-leg');
        if (!el) { return; }
        el.innerHTML = items.map(function (i) {
            return '<span><i style="background:' + i.color + '"></i>' + i.label + (i.valor ? ' <b>' + i.valor + '</b>' : '') + '</span>';
        }).join('');
    }
    function vacio(id) {
        document.getElementById(id).innerHTML = '<div class="gf-vacio">Sin datos en este período</div>';
    }
    function formatear(tabla, cols) {
        var f = dinero();
        cols.forEach(function (c) { f.format(tabla, c); });
    }
    return {C: C, base: base, leyenda: leyenda, vacio: vacio, formatear: formatear};
})();

(function () {
    // Boton (i): abre/cierra la explicacion de la grafica
    function cerrarTodos(salvo) {
        document.querySelectorAll('.gf-info-btn[aria-expanded=true]').forEach(function (b) {
            if (b !== salvo) { b.setAttribute('aria-expanded', 'false'); document.getElementById(b.getAttribute('aria-controls')).hidden = true; }
        });
    }
    document.addEventListener('click', function (e) {
        var b = e.target.closest ? e.target.closest('.gf-info-btn') : null;
        if (!b) { return; }
        var panel = document.getElementById(b.getAttribute('aria-controls'));
        var abrir = panel.hidden;
        cerrarTodos(b);
        panel.hidden = !abrir;
        b.setAttribute('aria-expanded', abrir ? 'true' : 'false');
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { cerrarTodos(null); } });

    // Filtros plegados en pantallas pequeñas
    var det = document.querySelector('details.gf-filtros-wrap');
    if (det && window.matchMedia && window.matchMedia('(max-width: 700px)').matches) { det.removeAttribute('open'); }

    // Ordenar tablas con clic en el encabezado
    function valor(td) {
        var t = td.getAttribute('data-v');
        if (t === null) { t = td.textContent.trim(); }
        var n = parseFloat(t);
        return isNaN(n) || !/^-?[0-9.,]+$/.test(String(t).replace(/[$\s%]/g, '')) ? t.toLowerCase() : n;
    }
    document.querySelectorAll('table.gf-table').forEach(function (tabla) {
        tabla.querySelectorAll('th.sortable').forEach(function (th) {
            th.addEventListener('click', function () {
                var cuerpo = tabla.tBodies[0];
                var filas = Array.prototype.slice.call(cuerpo.rows);
                var col = Array.prototype.indexOf.call(th.parentNode.children, th);
                var asc = th.getAttribute('data-asc') !== '1';
                th.parentNode.querySelectorAll('th').forEach(function (o) { o.removeAttribute('data-asc'); });
                th.setAttribute('data-asc', asc ? '1' : '0');
                filas.sort(function (a, b) {
                    var x = valor(a.cells[col]), y = valor(b.cells[col]);
                    if (x < y) { return asc ? -1 : 1; }
                    if (x > y) { return asc ? 1 : -1; }
                    return 0;
                });
                filas.forEach(function (f) { cuerpo.appendChild(f); });
            });
        });
    });
})();
</script>
<?php
}

/** Compatibilidad: ahora el ordenamiento de tablas va dentro de gf_script_base(). */
function gf_script_tablas()
{
}

/** Barra de exportacion: Excel (excelX=1) y PDF (impresion del navegador). */
function gf_barra_exportar(array $params)
{
    $q = $params;
    $q['excelX'] = 1;
    ?>
<div class="gf-acciones gf-noprint">
    <span class="sep"></span>
    <a class="gf-btn gf-btn-ok" href="index.php?<?= gf_h(http_build_query($q)); ?>" title="Descarga las tablas en Excel"><i class="bi bi-file-earmark-excel"></i> Excel</a>
    <button type="button" class="gf-btn gf-btn-pdf" onclick="window.print();" title="En la ventana de impresión elija «Guardar como PDF», horizontal"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
</div>
<?php
}

/**
 * Color fijo por medio de comunicacion (el color sigue a la entidad, no a su posicion).
 * Los medios conocidos tienen su color; los nuevos toman los siguientes de la paleta.
 */
function gf_colores_medio(array $nombres)
{
    $fijos = ['e-mail / SMS' => '#2a78d6', 'Meet / Videollamada' => '#eb6834', 'Llamada' => '#1baf7a', 'WA' => '#eda100', 'Visita' => '#e87ba4'];
    $extra = ['#008300', '#6250d6', '#e34948', '#64748b'];
    $map = [];
    $i = 0;
    sort($nombres);
    foreach ($nombres as $n) {
        if (isset($fijos[$n])) { $map[$n] = $fijos[$n]; }
        else { $map[$n] = $extra[$i % count($extra)]; $i++; }
    }
    return $map;
}
