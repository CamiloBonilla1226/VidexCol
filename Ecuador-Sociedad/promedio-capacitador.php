<?php
/*
*   Dashboard de promedios del "Método de verificación" de Capacitadores
*   (ecu_reportes, tipo_reporte = 308). Promedia, por miembro, los 9 campos
*   mapeo_* de la escala 1-4 (mapeo_oracion ... mapeo_trabajadores) —
*   EXCLUYE mapeo_iglesia a propósito, porque esa pregunta es Sí/No aparte
*   de la escala y no forma parte de esta medición.
*   Ver CLAUDE.md, sección "Unificación de Capacitadores y ECC".
*/
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;
$temp_letrero = "CAPACITADORES";

$idUsuarioSesion = isset($_SESSION["id"]) ? intval($_SESSION["id"]) : 0;

if($idUsuarioSesion == 0){
    ?><div class="row">
        <h5 class="alert alert-danger text-center">Debe iniciar sesión para continuar.</h5>
    </div><?php
    return;
}

$PSN1->connect();

/*
*   Mismo criterio de permisos que consultar-capacitador.php / grafica-capacitador.php:
*   el admin (usuario.tipo = 2) ve el promedio de TODOS los capacitadores;
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
    $fechaInicial = "2000-01-01";
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

/*
*   Estado: 1 = Por mejorar, 2 = Regular, 3 = Bueno, 4 = Excelente. Se
*   calcula en PHP a partir del promedio general de cada miembro (no viene
*   de ninguna columna), así que el filtro se aplica después de calcular
*   los promedios, no en el SQL.
*/
$estadosTexto = array(
    1 => "Por mejorar",
    2 => "Regular",
    3 => "Bueno",
    4 => "Excelente",
);
$estadoFiltro = isset($_REQUEST["estado"]) ? intval($_REQUEST["estado"]) : 0;

$sqlFiltro = " AND r.tipo_reporte = 308";
$sqlFiltro .= " AND r.fecha_inicio >= '".$fechaInicial."'";
$sqlFiltro .= " AND r.fecha_inicio <= '".$fechaFinal."'";
if($idUsuarioFiltro > 0){
    $sqlFiltro .= " AND r.idusuario = ".$idUsuarioFiltro;
}

/*
*   Un solo GROUP BY por idusuario trae el promedio de cada uno de los 9
*   campos de la escala 1-4. El "% general" y el "Estado" se calculan en
*   PHP a partir de estos 9 promedios (ver más abajo).
*/
$sqlPromedios = "SELECT r.idusuario, u.nombre AS nombre_usuario, COUNT(*) AS total_reportes,
    AVG(r.mapeo_oracion) AS avg_oracion,
    AVG(r.mapeo_companerismo) AS avg_companerismo,
    AVG(r.mapeo_adoracion) AS avg_adoracion,
    AVG(r.mapeo_biblia) AS avg_biblia,
    AVG(r.mapeo_evangelizar) AS avg_evangelizar,
    AVG(r.mapeo_cena) AS avg_cena,
    AVG(r.mapeo_dar) AS avg_dar,
    AVG(r.mapeo_bautizar) AS avg_bautizar,
    AVG(r.mapeo_trabajadores) AS avg_trabajadores
    FROM ecu_reportes r
    LEFT JOIN usuario u ON u.id = r.idusuario
    WHERE 1 ".$sqlFiltro."
    GROUP BY r.idusuario, u.nombre
    ORDER BY u.nombre ASC";
$PSN1->query($sqlPromedios);

$filasPromedios = array();
while($PSN1->next_record()){
    $promediosCampos = array(
        "oracion"      => round(floatval($PSN1->f("avg_oracion")), 1),
        "companerismo" => round(floatval($PSN1->f("avg_companerismo")), 1),
        "adoracion"    => round(floatval($PSN1->f("avg_adoracion")), 1),
        "biblia"       => round(floatval($PSN1->f("avg_biblia")), 1),
        "evangelizar"  => round(floatval($PSN1->f("avg_evangelizar")), 1),
        "cena"         => round(floatval($PSN1->f("avg_cena")), 1),
        "dar"          => round(floatval($PSN1->f("avg_dar")), 1),
        "bautizar"     => round(floatval($PSN1->f("avg_bautizar")), 1),
        "trabajadores" => round(floatval($PSN1->f("avg_trabajadores")), 1),
    );

    $promedioGeneral = round(array_sum($promediosCampos) / count($promediosCampos), 1);

    $estadoNumero = (int) round($promedioGeneral);
    if($estadoNumero < 1){ $estadoNumero = 1; }
    if($estadoNumero > 4){ $estadoNumero = 4; }

    $filasPromedios[] = array(
        "idusuario"        => intval($PSN1->f("idusuario")),
        "nombre_usuario"   => $PSN1->f("nombre_usuario"),
        "total_reportes"   => intval($PSN1->f("total_reportes")),
        "campos"           => $promediosCampos,
        "promedio_general" => $promedioGeneral,
        "estado_numero"    => $estadoNumero,
        "estado_texto"     => $estadosTexto[$estadoNumero],
    );
}

/*
*   Filtro "Estado": se aplica aquí porque depende del promedio calculado
*   en PHP, no de una columna directa de la base de datos.
*/
if($estadoFiltro > 0){
    $filasPromedios = array_values(array_filter($filasPromedios, function($fila) use ($estadoFiltro){
        return $fila["estado_numero"] == $estadoFiltro;
    }));
}

/*
*   Combo "Miembro": solo se llena para el administrador, misma lógica que
*   consultar-capacitador.php (lista TODOS los usuarios con esos roles,
*   hayan reportado o no).
*/
$listaUsuarios = array();
if($esAdmin){
    $PSN2->query("SELECT U.id, U.nombre FROM usuario AS U
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

$columnas = array(
    "oracion"      => "ORAC",
    "companerismo" => "COMP",
    "adoracion"    => "ADOR",
    "biblia"       => "BIBLI",
    "evangelizar"  => "EVAN",
    "cena"         => "CENA",
    "dar"          => "DAR",
    "bautizar"     => "BAUT",
    "trabajadores" => "TRAB",
);
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
        --danger-bg: #FBEAEA;
        --danger-text: #A3302F;
        --warning-bg: #FCF3DC;
        --warning-text: #8A6414;
        --info-bg: #E8F0FA;
        --info-text: #154A82;
        --success-bg: #E7F4EA;
        --success-text: #226B3C;
        --radius-card: 12px;
        --radius-control: 8px;

        background: #FFFFFF;
        color: var(--negro);
        font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        max-width: 1180px;
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
        box-shadow: 0 1px 3px rgba(26, 26, 26, 0.06);
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
    .ecu-wrap .ecu-btn-secondary {
        background: #FFFFFF;
        color: var(--azul);
        border: 1.5px solid var(--azul);
    }
    .ecu-wrap .ecu-btn-secondary:hover { background: var(--azul-tint); }

    /*
    *   Leyenda de la escala 1-4: mismo criterio de colores que se usará en
    *   la columna "Estado" de la tabla.
    */
    .ecu-wrap .ecu-escala-leyenda {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }
    .ecu-wrap .ecu-escala-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12.5px;
        font-weight: 600;
    }
    .ecu-wrap .ecu-estado-1 { background: var(--danger-bg); color: var(--danger-text); }
    .ecu-wrap .ecu-estado-2 { background: var(--warning-bg); color: var(--warning-text); }
    .ecu-wrap .ecu-estado-3 { background: var(--info-bg); color: var(--info-text); }
    .ecu-wrap .ecu-estado-4 { background: var(--success-bg); color: var(--success-text); }

    .ecu-wrap .ecu-tabla-wrap { overflow-x: auto; }
    .ecu-wrap table.ecu-tabla {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    .ecu-wrap table.ecu-tabla th,
    .ecu-wrap table.ecu-tabla td {
        border: 1px solid var(--line);
        padding: 10px 8px;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }
    .ecu-wrap table.ecu-tabla th {
        background: #FFFFFF;
        font-weight: 700;
        color: var(--negro);
    }
    .ecu-wrap table.ecu-tabla td.ecu-col-miembro {
        text-align: left;
        font-weight: 600;
        white-space: normal;
    }
    .ecu-wrap table.ecu-tabla tbody tr:nth-child(even) td {
        background: #FFFFFF;
    }
    .ecu-wrap table.ecu-tabla td.ecu-col-general {
        font-weight: 700;
    }
    .ecu-wrap .ecu-estado-pill {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
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
    <h3 class="ecu-title">Promedio de Método de verificación · <?=$temp_letrero; ?></h3>
    <h5 class="ecu-subtitle">Promedio por miembro de la escala 1-4 (no incluye "¿Comprometido como iglesia?")</h5>

    <?php if(!$esAdmin){ ?>
        <div class="ecu-banner">Este dashboard solo incluye tus propios reportes.</div>
    <?php } ?>

    <div class="ecu-escala-leyenda">
        <span class="ecu-escala-chip ecu-estado-1">1 · Por mejorar</span>
        <span class="ecu-escala-chip ecu-estado-2">2 · Regular</span>
        <span class="ecu-escala-chip ecu-estado-3">3 · Bueno</span>
        <span class="ecu-escala-chip ecu-estado-4">4 · Excelente</span>
    </div>

    <div class="ecu-card">
        <h4 class="ecu-section-title">Filtros</h4>
        <form method="get" id="formFiltrosPromedio">
            <input type="hidden" name="doc" value="promedio-capacitador" />
            <div class="ecu-filtros-grid">
                <div>
                    <label class="ecu-label">Fecha inicial</label>
                    <input type="date" name="fechaInicial" class="ecu-input" value="<?=htmlspecialchars($fechaInicial, ENT_QUOTES, "UTF-8"); ?>" />
                </div>
                <div>
                    <label class="ecu-label">Fecha final</label>
                    <input type="date" name="fechaFinal" class="ecu-input" value="<?=htmlspecialchars($fechaFinal, ENT_QUOTES, "UTF-8"); ?>" />
                </div>
                <div>
                    <label class="ecu-label">Estado</label>
                    <select name="estado" class="ecu-select">
                        <option value="">Ver todos</option>
                        <?php foreach($estadosTexto as $numeroEstado => $textoEstado){ ?>
                            <option value="<?=$numeroEstado; ?>" <?php if($estadoFiltro == $numeroEstado){ ?>selected="selected"<?php } ?>>
                                <?=$numeroEstado; ?> · <?=$textoEstado; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <?php if($esAdmin){ ?>
                    <div>
                        <label class="ecu-label">Miembro</label>
                        <select name="idUsuario" class="ecu-select">
                            <option value="">Ver todos</option>
                            <?php foreach($listaUsuarios as $usuarioItem){ ?>
                                <option value="<?=$usuarioItem["id"]; ?>" <?php if($idUsuarioFiltro == $usuarioItem["id"]){ ?>selected="selected"<?php } ?>>
                                    <?=htmlspecialchars($usuarioItem["nombre"], ENT_QUOTES, "UTF-8"); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                <?php } ?>
            </div>
            <div class="ecu-filtros-grid" style="margin-top:16px;">
                <div>
                    <button type="submit" class="ecu-btn">Filtrar</button>
                </div>
                <div>
                    <a href="index.php?doc=promedio-capacitador" class="ecu-btn ecu-btn-secondary" style="display:block; text-align:center; text-decoration:none;">Limpiar filtros</a>
                </div>
            </div>
        </form>
    </div>

    <?php if(count($filasPromedios) == 0){ ?>

        <div class="ecu-banner">No se encontraron reportes con los filtros seleccionados.</div>

    <?php }else{ ?>

        <div class="ecu-card">
            <h4 class="ecu-section-title">Promedio por miembro</h4>
            <p class="ecu-section-sub">Promedio de cada actividad (escala 1-4) sobre los reportes del rango filtrado.</p>

            <div class="ecu-tabla-wrap">
                <table class="ecu-tabla">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Miembro</th>
                            <th>Reportes</th>
                            <?php foreach($columnas as $etiquetaColumna){ ?>
                                <th><?=$etiquetaColumna; ?></th>
                            <?php } ?>
                            <th>% GEN</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($filasPromedios as $fila){ ?>
                            <tr>
                                <td class="ecu-col-miembro"><?=htmlspecialchars($fila["nombre_usuario"], ENT_QUOTES, "UTF-8"); ?></td>
                                <td><?=$fila["total_reportes"]; ?></td>
                                <?php foreach($columnas as $campo => $etiquetaColumna){ ?>
                                    <td><?=$fila["campos"][$campo]; ?></td>
                                <?php } ?>
                                <td class="ecu-col-general"><?=$fila["promedio_general"]; ?></td>
                                <td>
                                    <span class="ecu-estado-pill ecu-estado-<?=$fila["estado_numero"]; ?>">
                                        <?=htmlspecialchars($fila["estado_texto"], ENT_QUOTES, "UTF-8"); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php } ?>

</div>
