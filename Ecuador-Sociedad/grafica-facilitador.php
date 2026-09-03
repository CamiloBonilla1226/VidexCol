<?php
/*
*   Gráfica de consolidado del programa Facilitadores (ecu_reportes,
*   tipo_reporte = 318). Inspirada en grafica-consolidado-evangelistas.php
*   (mismo tipo de filtros y Google Charts), pero sobre la tabla nueva y con
*   el estilo visual .ecu-wrap ya usado en gestionar-facilitador.php /
*   reportar_facilitador.php / consultar-facilitador.php / editar_facilitador.php.
*   Ver CLAUDE.md, sección "Unificación de Facilitadores y ECC".
*/
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;
$temp_letrero = "FACILITADORES";

$idUsuarioSesion = isset($_SESSION["id"]) ? intval($_SESSION["id"]) : 0;

if($idUsuarioSesion == 0){
    ?><div class="row">
        <h5 class="alert alert-danger text-center">Debe iniciar sesión para continuar.</h5>
    </div><?php
    return;
}

$PSN1->connect();

/*
*   Mismo criterio de permisos que consultar-facilitador.php: el admin
*   (usuario.tipo = 2) ve el consolidado de TODOS los facilitadores;
*   cualquier otro usuario solo ve el suyo.
*/
$usuarioTipo = 0;
$PSN2->query("SELECT tipo FROM usuario WHERE id = ".$idUsuarioSesion." LIMIT 1");
if($PSN2->num_rows() > 0){
    $PSN2->next_record();
    $usuarioTipo = intval($PSN2->f("tipo"));
}
$esAdmin = ($usuarioTipo == 2);

function fechaValida($valor){
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1;
}

$fechaInicial = isset($_REQUEST["fechaInicial"]) ? trim($_REQUEST["fechaInicial"]) : "";
if(!fechaValida($fechaInicial)){
    $fechaInicial = date("Y-01-01");
}

$fechaFinal = isset($_REQUEST["fechaFinal"]) ? trim($_REQUEST["fechaFinal"]) : "";
if(!fechaValida($fechaFinal)){
    $fechaFinal = date("Y-m-d");
}

$idUsuarioFiltro = 0;
if($esAdmin){
    $idUsuarioFiltro = isset($_REQUEST["idUsuario"]) ? intval($_REQUEST["idUsuario"]) : 0;
}else{
    $idUsuarioFiltro = $idUsuarioSesion;
}

$sqlFiltro = " AND r.tipo_reporte = 318";
$sqlFiltro .= " AND r.fecha_inicio >= '".$fechaInicial."'";
$sqlFiltro .= " AND r.fecha_inicio <= '".$fechaFinal."'";
if($idUsuarioFiltro > 0){
    $sqlFiltro .= " AND r.idusuario = ".$idUsuarioFiltro;
}

$listaUsuarios = array();
if($esAdmin){
    $PSN2->query("SELECT DISTINCT U.id, U.nombre FROM usuario AS U
                  LEFT JOIN usuario_empresa AS UE ON UE.idUsuario = U.id
                  WHERE U.tipo IN (162, 163, 167)
                  ORDER BY U.nombre ASC");
    while($PSN2->next_record()){
        $listaUsuarios[] = array(
            "id"     => intval($PSN2->f("id")),
            "nombre" => $PSN2->f("nombre"),
        );
    }
}

/*
*   Consulta principal: un solo SELECT con todos los agregados que necesita
*   el consolidado (evita repetir el mismo WHERE en varias consultas, como
*   sí hacía el archivo viejo — y evita el bug que encontramos ahí de pedir
*   columnas que no venían en el SELECT).
*/
$sql = "SELECT
    COUNT(*) AS total_reportes,
    COALESCE(SUM(asistencia_hom), 0) AS tot_hom,
    COALESCE(SUM(asistencia_muj), 0) AS tot_muj,
    COALESCE(SUM(asistencia_jov), 0) AS tot_jov,
    COALESCE(SUM(asistencia_nin), 0) AS tot_nin,
    COALESCE(SUM(asistencia_total), 0) AS tot_asistencia,
    COALESCE(AVG(asistencia_total), 0) AS prom_asistencia,
    COALESCE(SUM(nuevos_creyentes_grupo), 0) AS tot_nuevos_creyentes,
    COALESCE(SUM(total_creyentes_grupo), 0) AS tot_creyentes,
    COALESCE(SUM(nuevos_bautizados_grupo), 0) AS tot_nuevos_bautizados,
    COALESCE(SUM(total_bautizados_grupo), 0) AS tot_bautizados,
    COUNT(DISTINCT NULLIF(carcel_ubicacion, '')) AS carceles_atendidas,
    COUNT(DISTINCT idgrupo) AS grupos_reportados,
    SUM(CASE WHEN foto IS NOT NULL AND foto <> '' THEN 1 ELSE 0 END) AS reportes_con_foto,
    COALESCE(SUM(mapeo_oracion), 0) AS act_oracion,
    COALESCE(SUM(mapeo_companerismo), 0) AS act_companerismo,
    COALESCE(SUM(mapeo_adoracion), 0) AS act_adoracion,
    COALESCE(SUM(mapeo_biblia), 0) AS act_biblia,
    COALESCE(SUM(mapeo_evangelizar), 0) AS act_evangelizar,
    COALESCE(SUM(mapeo_cena), 0) AS act_cena,
    COALESCE(SUM(mapeo_dar), 0) AS act_dar,
    COALESCE(SUM(mapeo_bautizar), 0) AS act_bautizar,
    COALESCE(SUM(mapeo_trabajadores), 0) AS act_trabajadores
    FROM ecu_reportes r
    WHERE 1 ".$sqlFiltro;
$PSN1->query($sql);

$totalReportes = 0;
if($PSN1->num_rows() > 0){
    $PSN1->next_record();
    $totalReportes           = intval($PSN1->f("total_reportes"));
    $totHom                  = intval($PSN1->f("tot_hom"));
    $totMuj                  = intval($PSN1->f("tot_muj"));
    $totJov                  = intval($PSN1->f("tot_jov"));
    $totNin                  = intval($PSN1->f("tot_nin"));
    $totAsistencia           = intval($PSN1->f("tot_asistencia"));
    $promAsistencia          = round(floatval($PSN1->f("prom_asistencia")), 1);
    $totNuevosCreyentes      = intval($PSN1->f("tot_nuevos_creyentes"));
    $totCreyentes            = intval($PSN1->f("tot_creyentes"));
    $totNuevosBautizados     = intval($PSN1->f("tot_nuevos_bautizados"));
    $totBautizados           = intval($PSN1->f("tot_bautizados"));
    $carcelesAtendidas       = intval($PSN1->f("carceles_atendidas"));
    $gruposReportados        = intval($PSN1->f("grupos_reportados"));
    $reportesConFoto         = intval($PSN1->f("reportes_con_foto"));
    $actOracion              = intval($PSN1->f("act_oracion"));
    $actCompanerismo         = intval($PSN1->f("act_companerismo"));
    $actAdoracion            = intval($PSN1->f("act_adoracion"));
    $actBiblia               = intval($PSN1->f("act_biblia"));
    $actEvangelizar          = intval($PSN1->f("act_evangelizar"));
    $actCena                 = intval($PSN1->f("act_cena"));
    $actDar                  = intval($PSN1->f("act_dar"));
    $actBautizar             = intval($PSN1->f("act_bautizar"));
    $actTrabajadores         = intval($PSN1->f("act_trabajadores"));
}

$porcentajeConFoto = ($totalReportes > 0) ? round(($reportesConFoto * 100) / $totalReportes) : 0;

?>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@600&display=swap" rel="stylesheet">
<style>
    .ecu-wrap {
        --azul: #1D5FA6;
        --azul-dark: #154A82;
        --azul-tint: #E8F0FA;
        --verde: #2E8B4F;
        --verde-dark: #226B3C;
        --verde-tint: #E7F4EA;
        --negro: #1A1A1A;
        --gris-texto: #55595C;
        --gris-claro: #F4F6F7;
        --line: #D9DEE2;
        --line-strong: #B9C1C7;
        --radius-card: 12px;
        --radius-control: 8px;

        background: #FFFFFF;
        color: var(--negro);
        font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        max-width: 1120px;
        margin: 0 auto;
        padding: 32px 16px 24px;
    }
    .ecu-wrap * { box-sizing: border-box; }

    .ecu-wrap .ecu-eyebrow {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--azul);
        margin: 0 0 16px;
    }
    .ecu-wrap h3.ecu-title {
        font-family: 'Fraunces', Georgia, serif;
        font-weight: 500;
        font-size: 24px;
        margin: 0 0 6px;
        color: var(--negro);
        text-align: center;
    }
    .ecu-wrap h5.ecu-subtitle {
        font-size: 14px;
        font-weight: 400;
        color: var(--gris-texto);
        margin: 0 0 20px;
        text-align: center;
    }

    .ecu-wrap .ecu-banner {
        padding: 13px 16px;
        border-radius: var(--radius-control);
        font-size: 14px;
        margin-bottom: 20px;
        background: var(--azul-tint);
        color: var(--azul-dark);
    }

    .ecu-wrap .ecu-card {
        background: var(--gris-claro);
        border: 1px solid var(--line-strong);
        border-radius: var(--radius-card);
        padding: 24px;
        margin-bottom: 20px;
    }
    .ecu-wrap .ecu-section-title {
        font-family: 'Public Sans', sans-serif;
        font-weight: 700;
        font-size: 16px;
        margin: 0 0 4px;
        color: var(--negro);
    }
    .ecu-wrap .ecu-section-sub {
        font-size: 13px;
        color: var(--gris-texto);
        margin: 0 0 18px;
    }

    .ecu-wrap label.ecu-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--negro);
        margin-bottom: 7px;
    }
    .ecu-wrap .ecu-filtros-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        align-items: end;
    }
    .ecu-wrap input[type="date"].ecu-input,
    .ecu-wrap select.ecu-select {
        width: 100%;
        font-family: 'Public Sans', sans-serif;
        font-size: 14px;
        padding: 11px 13px;
        border: 1.5px solid var(--line);
        border-radius: var(--radius-control);
        background: #FFFFFF;
        color: var(--negro);
        outline: none;
    }
    .ecu-wrap .ecu-btn {
        font-family: 'Public Sans', sans-serif;
        font-size: 14px;
        font-weight: 600;
        border-radius: var(--radius-control);
        padding: 11px 22px;
        cursor: pointer;
        border: none;
        background: var(--azul);
        color: #FFFFFF;
        width: 100%;
    }
    .ecu-wrap .ecu-btn:hover { background: var(--azul-dark); }

    /*
    *   Tarjetas de indicadores (KPI): el resumen numérico más importante,
    *   de un vistazo, antes de entrar a las gráficas.
    */
    .ecu-wrap .ecu-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 14px;
        margin-bottom: 20px;
    }
    .ecu-wrap .ecu-kpi-card {
        background: #FFFFFF;
        border: 1px solid var(--line);
        border-radius: var(--radius-card);
        padding: 16px;
        text-align: center;
    }
    .ecu-wrap .ecu-kpi-valor {
        font-family: 'Fraunces', Georgia, serif;
        font-weight: 500;
        font-size: 28px;
        color: var(--azul-dark);
        margin: 0;
        line-height: 1.1;
    }
    .ecu-wrap .ecu-kpi-label {
        font-size: 12px;
        color: var(--gris-texto);
        margin: 6px 0 0;
    }

    .ecu-wrap .ecu-grafica-box { width: 100%; }

    @media (max-width: 900px) {
        .ecu-wrap .ecu-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 800px) {
        .ecu-wrap .ecu-filtros-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 500px) {
        .ecu-wrap .ecu-filtros-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="ecu-wrap">

    <p class="ecu-eyebrow">ECU · Reportes</p>
    <h3 class="ecu-title">Consolidado de <?=$temp_letrero; ?></h3>
    <h5 class="ecu-subtitle">Resumen visual de los reportes en el rango seleccionado</h5>

    <?php if(!$esAdmin){ ?>
        <div class="ecu-banner">Este consolidado solo incluye los reportes que tú has creado.</div>
    <?php } ?>

    <div class="ecu-card">
        <h4 class="ecu-section-title">Filtros</h4>
        <form method="get" id="formFiltrosGrafica">
            <input type="hidden" name="doc" value="grafica-consolidado-facilitadores" />
            <div class="ecu-filtros-grid">
                <?php if($esAdmin){ ?>
                    <div>
                        <label class="ecu-label">Miembro de la regional</label>
                        <select name="idUsuario" class="ecu-select" onchange="this.form.submit()">
                            <option value="">Ver todos</option>
                            <?php foreach($listaUsuarios as $usuarioItem){ ?>
                                <option value="<?=$usuarioItem["id"]; ?>" <?php if($idUsuarioFiltro == $usuarioItem["id"]){ ?>selected="selected"<?php } ?>>
                                    <?=htmlspecialchars($usuarioItem["nombre"], ENT_QUOTES, "UTF-8"); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                <?php } ?>
                <div>
                    <label class="ecu-label">Fecha inicial</label>
                    <input type="date" name="fechaInicial" class="ecu-input" value="<?=htmlspecialchars($fechaInicial, ENT_QUOTES, "UTF-8"); ?>" />
                </div>
                <div>
                    <label class="ecu-label">Fecha final</label>
                    <input type="date" name="fechaFinal" class="ecu-input" value="<?=htmlspecialchars($fechaFinal, ENT_QUOTES, "UTF-8"); ?>" />
                </div>
                <div>
                    <button type="submit" class="ecu-btn">Filtrar</button>
                </div>
            </div>
        </form>
    </div>

    <?php if($totalReportes == 0){ ?>

        <div class="ecu-banner">No se encontraron reportes con los filtros seleccionados.</div>

    <?php }else{ ?>

        <div class="ecu-kpi-grid">
            <div class="ecu-kpi-card">
                <p class="ecu-kpi-valor"><?=$totalReportes; ?></p>
                <p class="ecu-kpi-label">Reportes</p>
            </div>
            <div class="ecu-kpi-card">
                <p class="ecu-kpi-valor"><?=$totAsistencia; ?></p>
                <p class="ecu-kpi-label">Asistencia total</p>
            </div>
            <div class="ecu-kpi-card">
                <p class="ecu-kpi-valor"><?=$promAsistencia; ?></p>
                <p class="ecu-kpi-label">Asistencia promedio / reporte</p>
            </div>
            <div class="ecu-kpi-card">
                <p class="ecu-kpi-valor"><?=$carcelesAtendidas; ?></p>
                <p class="ecu-kpi-label">Cárceles atendidas</p>
            </div>
            <div class="ecu-kpi-card">
                <p class="ecu-kpi-valor"><?=$porcentajeConFoto; ?>%</p>
                <p class="ecu-kpi-label">Reportes con evidencia (foto)</p>
            </div>
        </div>

        <div class="ecu-card">
            <h4 class="ecu-section-title">Asistencia por grupo poblacional</h4>
            <p class="ecu-section-sub">Suma de asistencia registrada en el rango de fechas.</p>
            <div id="graficaAsistencia" class="ecu-grafica-box" style="height: 320px;"></div>
        </div>

        <div class="ecu-card">
            <h4 class="ecu-section-title">Crecimiento del grupo</h4>
            <p class="ecu-section-sub">Nuevos vs. totales acumulados, en creyentes y bautizados.</p>
            <div id="graficaCrecimiento" class="ecu-grafica-box" style="height: 300px;"></div>
        </div>

        <div class="ecu-card">
            <h4 class="ecu-section-title">Método de verificación</h4>
            <p class="ecu-section-sub">De <?=$totalReportes; ?> reportes, cuántos marcaron Sí en cada actividad.</p>
            <div id="graficaMapeo" class="ecu-grafica-box" style="height: 380px;"></div>
        </div>

    <?php } ?>

</div>

<?php if($totalReportes > 0){ ?>
<script type="text/javascript">
    google.charts.load("current", {packages:["corechart", "bar"]});
    google.charts.setOnLoadCallback(dibujarGraficasFacilitadores);

    function dibujarGraficasFacilitadores(){

        var colorAzul = "#1D5FA6";
        var colorVerde = "#2E8B4F";
        var colorRojo = "#A3302F";
        var colorAmbar = "#C98A1F";

        var dataAsistencia = google.visualization.arrayToDataTable([
            ["Grupo", "Personas", { role: "style" }],
            ["Hombres", <?=$totHom; ?>, colorAzul],
            ["Mujeres", <?=$totMuj; ?>, colorAmbar],
            ["Jóvenes", <?=$totJov; ?>, colorVerde],
            ["Niños", <?=$totNin; ?>, colorRojo]
        ]);
        var viewAsistencia = new google.visualization.DataView(dataAsistencia);
        viewAsistencia.setColumns([0, 1,
            { calc: "stringify", sourceColumn: 1, type: "string", role: "annotation" }, 2]);
        new google.visualization.BarChart(document.getElementById("graficaAsistencia")).draw(viewAsistencia, {
            legend: { position: "none" },
            bar: { groupWidth: "60%" },
            chartArea: { width: "75%", height: "75%" }
        });

        var dataCrecimiento = google.visualization.arrayToDataTable([
            ["Indicador", "Nuevos", "Total acumulado"],
            ["Creyentes", <?=$totNuevosCreyentes; ?>, <?=$totCreyentes; ?>],
            ["Bautizados", <?=$totNuevosBautizados; ?>, <?=$totBautizados; ?>]
        ]);
        new google.visualization.BarChart(document.getElementById("graficaCrecimiento")).draw(dataCrecimiento, {
            legend: { position: "top" },
            colors: [colorVerde, colorAzul],
            bar: { groupWidth: "50%" },
            chartArea: { width: "75%", height: "65%" }
        });

        var dataMapeo = google.visualization.arrayToDataTable([
            ["Actividad", "Sí la realiza", "No la realiza"],
            ["Orar", <?=$actOracion; ?>, <?=$totalReportes - $actOracion; ?>],
            ["Compañerismo", <?=$actCompanerismo; ?>, <?=$totalReportes - $actCompanerismo; ?>],
            ["Adoración", <?=$actAdoracion; ?>, <?=$totalReportes - $actAdoracion; ?>],
            ["Aplicar la biblia", <?=$actBiblia; ?>, <?=$totalReportes - $actBiblia; ?>],
            ["Evangelizar", <?=$actEvangelizar; ?>, <?=$totalReportes - $actEvangelizar; ?>],
            ["Cena del Señor", <?=$actCena; ?>, <?=$totalReportes - $actCena; ?>],
            ["Dar", <?=$actDar; ?>, <?=$totalReportes - $actDar; ?>],
            ["Bautizar", <?=$actBautizar; ?>, <?=$totalReportes - $actBautizar; ?>],
            ["Entrenar líderes", <?=$actTrabajadores; ?>, <?=$totalReportes - $actTrabajadores; ?>]
        ]);
        new google.visualization.BarChart(document.getElementById("graficaMapeo")).draw(dataMapeo, {
            isStacked: "percent",
            legend: { position: "top" },
            colors: [colorVerde, colorRojo],
            chartArea: { width: "60%", height: "85%" }
        });

    }
</script>
<?php } ?>
