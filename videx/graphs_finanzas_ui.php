<?php
/**
 * Control financiero y seguimiento - helpers de interfaz compartidos por
 * graphs_rentabilidad.php y graphs_cartera.php.
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

function gf_estilos()
{
    ?>
<style>
.gf-wrap { max-width: 1500px; margin: 0 auto; padding: 18px 20px 40px; font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; color: #1e293b; }
.gf-hero { background: linear-gradient(135deg, #1e3a8a 0%, #0f766e 100%); color: #fff; padding: 22px 26px; border-radius: 14px; margin-bottom: 18px; }
.gf-hero h1 { margin: 0 0 4px; font-size: 26px; font-weight: 700; }
.gf-hero p { margin: 0; opacity: .9; font-size: 14px; }
.gf-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 18px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
.gf-card h2 { margin: 0 0 12px; font-size: 16px; font-weight: 700; color: #0f172a; }
.gf-card h2 small { font-weight: 500; color: #64748b; font-size: 12px; margin-left: 6px; }
.gf-filtros { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; align-items: end; }
.gf-field label { display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px; letter-spacing: .03em; }
.gf-field input, .gf-field select { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; font-size: 14px; box-sizing: border-box; }
.gf-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; border: 0; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; }
.gf-btn-primary { background: #1d4ed8; color: #fff; }
.gf-btn-ok { background: #15803d; color: #fff; }
.gf-btn-pdf { background: #b91c1c; color: #fff; }
.gf-btn-ghost { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
.gf-btn:hover { filter: brightness(1.08); color: inherit; }
.gf-btn-primary:hover, .gf-btn-ok:hover, .gf-btn-pdf:hover { color: #fff; }
.gf-acciones { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.gf-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-bottom: 16px; }
.gf-kpi { background: #fff; border: 1px solid #e2e8f0; border-left: 5px solid #94a3b8; border-radius: 12px; padding: 12px 14px; }
.gf-kpi .t { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .03em; }
.gf-kpi .v { font-size: 22px; font-weight: 800; margin-top: 4px; }
.gf-kpi .s { font-size: 12px; color: #64748b; margin-top: 2px; }
.gf-kpi.azul { border-left-color: #2563eb; } .gf-kpi.verde { border-left-color: #16a34a; }
.gf-kpi.rojo { border-left-color: #dc2626; } .gf-kpi.naranja { border-left-color: #ea580c; }
.gf-kpi.morado { border-left-color: #7c3aed; } .gf-kpi.gris { border-left-color: #64748b; }
.gf-pos { color: #15803d; } .gf-neg { color: #b91c1c; }
.gf-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.gf-grid3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.gf-chart { width: 100%; min-height: 340px; }
.gf-tablewrap { overflow-x: auto; }
table.gf-table { width: 100%; border-collapse: collapse; font-size: 13px; }
table.gf-table th { background: #f1f5f9; color: #334155; text-align: left; padding: 8px 9px; border-bottom: 2px solid #cbd5e1; white-space: nowrap; position: sticky; top: 0; }
table.gf-table th.sortable { cursor: pointer; }
table.gf-table th.sortable:hover { background: #e2e8f0; }
table.gf-table td { padding: 7px 9px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
table.gf-table tr:hover td { background: #f8fafc; }
table.gf-table td.n, table.gf-table th.n { text-align: right; white-space: nowrap; }
table.gf-table tfoot td { font-weight: 800; background: #f1f5f9; border-top: 2px solid #cbd5e1; }
.gf-tag { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; background: #e2e8f0; color: #334155; white-space: nowrap; }
.gf-tag.verde { background: #dcfce7; color: #166534; } .gf-tag.amarillo { background: #fef9c3; color: #854d0e; }
.gf-tag.rojo { background: #fee2e2; color: #991b1b; } .gf-tag.azul { background: #dbeafe; color: #1e40af; }
.gf-tag.morado { background: #ede9fe; color: #5b21b6; }
.gf-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; }
.gf-dot.verde { background: #16a34a; } .gf-dot.amarillo { background: #eab308; } .gf-dot.rojo { background: #dc2626; }
.gf-nota { font-size: 12px; color: #64748b; line-height: 1.5; }
.gf-aviso { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 10px 14px; border-radius: 10px; margin-bottom: 14px; font-size: 13px; }
.gf-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 14px; border-radius: 10px; margin-bottom: 14px; font-size: 13px; }
.gf-resultado td { padding: 6px 4px; font-size: 14px; }
.gf-resultado tr.total td { border-top: 2px solid #94a3b8; font-weight: 800; font-size: 15px; }
.gf-timeline { border-left: 3px solid #cbd5e1; margin-left: 8px; padding-left: 16px; }
.gf-tl-item { position: relative; margin-bottom: 14px; }
.gf-tl-item:before { content: ''; position: absolute; left: -23px; top: 5px; width: 11px; height: 11px; border-radius: 50%; background: #2563eb; border: 2px solid #fff; }
.gf-tl-item .h { font-size: 12px; color: #64748b; margin-bottom: 2px; }
.gf-tl-item .b { font-size: 14px; white-space: pre-wrap; }
.gf-kv { display: grid; grid-template-columns: 150px 1fr; gap: 4px 12px; font-size: 14px; }
.gf-kv dt { color: #64748b; font-weight: 600; } .gf-kv dd { margin: 0; }
@media (max-width: 900px) { .gf-grid2, .gf-grid3 { grid-template-columns: 1fr; } .gf-hero h1 { font-size: 21px; } }
@media print {
    @page { size: A4 landscape; margin: 10mm; }
    aside, .sidebar-backdrop, .videx-footer, .gf-noprint, nav, .sidebar { display: none !important; }
    .main-content, main { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
    body { background: #fff !important; }
    .gf-wrap { max-width: 100%; padding: 0; }
    .gf-card, .gf-kpi { box-shadow: none; break-inside: avoid; page-break-inside: avoid; }
    .gf-hero { background: #1e3a8a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .gf-tablewrap { overflow: visible; }
    table.gf-table { font-size: 10px; }
    table.gf-table th { position: static; }
    .gf-tag, .gf-dot, .gf-kpi { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
<?php
}

/** Script comun: ordenar tablas con clic en el encabezado. */
function gf_script_tablas()
{
    ?>
<script>
(function () {
    function valor(td) {
        var t = td.getAttribute('data-v');
        if (t === null) { t = td.textContent.trim(); }
        var n = parseFloat(t);
        return isNaN(n) || !/^-?[0-9.,]+$/.test(String(t).replace(/[$\s%]/g, '')) ? t.toLowerCase() : n;
    }
    document.querySelectorAll('table.gf-table').forEach(function (tabla) {
        tabla.querySelectorAll('th.sortable').forEach(function (th, idx) {
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

/** Barra de exportacion: Excel (excelX=1) y PDF (impresion del navegador). */
function gf_barra_exportar(array $params)
{
    $q = $params;
    $q['excelX'] = 1;
    ?>
<div class="gf-acciones gf-noprint">
    <a class="gf-btn gf-btn-ok" href="index.php?<?= gf_h(http_build_query($q)); ?>"><i class="bi bi-file-earmark-excel"></i> Exportar a Excel</a>
    <button type="button" class="gf-btn gf-btn-pdf" onclick="window.print();"><i class="bi bi-file-earmark-pdf"></i> Exportar a PDF</button>
    <span class="gf-nota" style="align-self:center;">En PDF: en la ventana de impresión elija «Guardar como PDF» (orientación horizontal).</span>
</div>
<?php
}
