<?php
/*
*   Edición de un reporte de Facilitadores (ecu_reportes, tipo_reporte = 318).
*   Se llega aquí desde consultar-facilitador.php (clic en una fila). Mismo
*   formulario y estilo que reportar_facilitador.php, pero prediligenciado
*   y con UPDATE en vez de INSERT. Ver CLAUDE.md, sección "Unificación de
*   Facilitadores y ECC".
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
*   Permisos de VISUALIZACIÓN: usuario.tipo = 2 (admin) puede ver cualquier
*   reporte de Facilitadores; cualquier otro usuario solo puede ver los
*   suyos (misma regla usada en consultar-facilitador.php). Permisos de
*   EDICIÓN/ELIMINACIÓN: ver $puedeEditar más abajo — solo el admin.
*/
$usuarioTipo = 0;
$PSN2->query("SELECT tipo FROM usuario WHERE id = ".$idUsuarioSesion." LIMIT 1");
if($PSN2->num_rows() > 0){
    $PSN2->next_record();
    $usuarioTipo = intval($PSN2->f("tipo"));
}
$esAdmin = ($usuarioTipo == 2);

/*
*   Solo el administrador puede editar o eliminar un reporte. Cualquier
*   otro usuario que sea dueño del reporte puede abrir esta pantalla, pero
*   únicamente para consultarlo (formulario de solo lectura, sin botones de
*   guardar/eliminar).
*/
$puedeEditar = $esAdmin;

$idReporte = isset($_REQUEST["idreporte"]) ? intval($_REQUEST["idreporte"]) : 0;

$reporte = null;
if($idReporte > 0){
    $sqlReporte = "SELECT r.*, u.nombre AS nombre_usuario_reporta FROM ecu_reportes r
                   LEFT JOIN usuario u ON u.id = r.idusuario
                   WHERE r.idreporte = ".$idReporte." AND r.tipo_reporte = 318 LIMIT 1";
    $PSN1->query($sqlReporte);
    if($PSN1->num_rows() > 0){
        $PSN1->next_record();
        $idUsuarioDelReporte = intval($PSN1->f("idusuario"));
        if($esAdmin || $idUsuarioDelReporte == $idUsuarioSesion){
            $reporte = array(
                "idreporte"               => intval($PSN1->f("idreporte")),
                "idgrupo"                 => intval($PSN1->f("idgrupo")),
                "idusuario"               => $idUsuarioDelReporte,
                "nombre_usuario_reporta"  => $PSN1->f("nombre_usuario_reporta"),
                "nombre_grupo"            => $PSN1->f("nombre_grupo"),
                "generacion"              => intval($PSN1->f("generacion")),
                "fecha_inicio"            => $PSN1->f("fecha_inicio"),
                "nombre_lider"            => $PSN1->f("nombre_lider"),
                "ubicacion"               => $PSN1->f("ubicacion"),
                "asistencia_hom"          => intval($PSN1->f("asistencia_hom")),
                "asistencia_muj"          => intval($PSN1->f("asistencia_muj")),
                "asistencia_jov"          => intval($PSN1->f("asistencia_jov")),
                "asistencia_nin"          => intval($PSN1->f("asistencia_nin")),
                "total_creyentes_grupo"   => intval($PSN1->f("total_creyentes_grupo")),
                "nuevos_creyentes_grupo"  => intval($PSN1->f("nuevos_creyentes_grupo")),
                "total_bautizados_grupo"  => intval($PSN1->f("total_bautizados_grupo")),
                "nuevos_bautizados_grupo" => intval($PSN1->f("nuevos_bautizados_grupo")),
                "carcel_ubicacion"        => $PSN1->f("carcel_ubicacion"),
                "pabellon"                => $PSN1->f("pabellon"),
                "comentario"              => $PSN1->f("comentario"),
                "foto"                    => $PSN1->f("foto"),
                "mapeo_oracion"           => intval($PSN1->f("mapeo_oracion")),
                "mapeo_companerismo"      => intval($PSN1->f("mapeo_companerismo")),
                "mapeo_adoracion"         => intval($PSN1->f("mapeo_adoracion")),
                "mapeo_biblia"            => intval($PSN1->f("mapeo_biblia")),
                "mapeo_evangelizar"       => intval($PSN1->f("mapeo_evangelizar")),
                "mapeo_cena"              => intval($PSN1->f("mapeo_cena")),
                "mapeo_dar"               => intval($PSN1->f("mapeo_dar")),
                "mapeo_bautizar"          => intval($PSN1->f("mapeo_bautizar")),
                "mapeo_trabajadores"      => intval($PSN1->f("mapeo_trabajadores")),
            );
        }
    }
}

if($reporte === null){
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@600&display=swap" rel="stylesheet">
    <div class="ecu-wrap">
        <p class="ecu-eyebrow">ECU · Reportes</p>
        <h3 class="ecu-title">Editar reporte de <?=$temp_letrero; ?></h3>
        <div class="ecu-banner ecu-error">No se encontró el reporte solicitado, o no tiene permiso para editarlo.</div>
        <div class="ecu-btn-row">
            <a href="index.php?doc=consultar-facilitador" class="ecu-btn ecu-btn-secondary" style="text-decoration:none;">Volver a consultar reportes</a>
        </div>
    </div>
    <?php
    return;
}

/*
*   Navegación anterior/siguiente: mismo criterio de visibilidad que el
*   resto de la pantalla (el admin navega entre todos los reportes de
*   Facilitadores; cualquier otro usuario solo entre los suyos).
*/
$sqlAmbito = " AND tipo_reporte = 318";
if(!$esAdmin){
    $sqlAmbito .= " AND idusuario = ".$idUsuarioSesion;
}

$idReporteAnterior = 0;
$PSN2->query("SELECT MAX(idreporte) AS id FROM ecu_reportes WHERE idreporte < ".$idReporte.$sqlAmbito);
if($PSN2->num_rows() > 0){
    $PSN2->next_record();
    $idReporteAnterior = intval($PSN2->f("id"));
}

$idReporteSiguiente = 0;
$PSN2->query("SELECT MIN(idreporte) AS id FROM ecu_reportes WHERE idreporte > ".$idReporte.$sqlAmbito);
if($PSN2->num_rows() > 0){
    $PSN2->next_record();
    $idReporteSiguiente = intval($PSN2->f("id"));
}

$errorReporte = "";
$exitoReporte = "";

if(isset($_GET["actualizado"]) && $_GET["actualizado"] == "1"){
    $exitoReporte = "Reporte actualizado correctamente.";
}

/*
*   Ítems del "Método de verificación" (mapeo_*): mismo checkbox Sí/No,
*   mismos íconos e igual switch visual que reportar_facilitador.php /
*   gestionar-sub-programa-evangelistas.php.
*/
$camposMapeo = array(
    "mapeo_oracion"      => "Orar",
    "mapeo_companerismo" => "Compañerismo",
    "mapeo_adoracion"    => "Adorar",
    "mapeo_biblia"       => "Aplicar la biblia",
    "mapeo_evangelizar"  => "Evangelizar",
    "mapeo_cena"         => "Cena del Señor",
    "mapeo_dar"          => "Dar",
    "mapeo_bautizar"     => "Bautizar",
    "mapeo_trabajadores" => "Entrenar nuevos líderes",
);

/*
*   Catálogo de cárceles (igual que reportar_facilitador.php, filtro de
*   zona pendiente por implementar por la misma razón documentada allá:
*   registros con reub_reg_fk huérfano).
*/
$listaCarceles = array();
$PSN3 = new DBbase_Sql;
$PSN3->query("SELECT reub_id, reub_nom FROM tbl_regional_ubicacion ORDER BY reub_nom ASC");
while($PSN3->next_record()){
    $listaCarceles[] = array(
        "id"     => intval($PSN3->f("reub_id")),
        "nombre" => $PSN3->f("reub_nom"),
    );
}

/*
*   La cárcel se guarda como NOMBRE (texto libre) en ecu_reportes, no como
*   reub_id. Para preseleccionar el <select> se busca el reub_id cuyo
*   nombre coincida; si la cárcel fue renombrada o eliminada del catálogo
*   después de crear el reporte, simplemente no queda nada preseleccionado.
*/
$carcelIdActual = 0;
if($reporte["carcel_ubicacion"] != ""){
    foreach($listaCarceles as $carcel){
        if($carcel["nombre"] == $reporte["carcel_ubicacion"]){
            $carcelIdActual = $carcel["id"];
            break;
        }
    }
}

/*
*   ACTUALIZAR REPORTE
*/
if($puedeEditar && isset($_POST["funcion"]) && $_POST["funcion"] == "actualizar_reporte"){

    $nombre_lider = trim($_POST["nombre_lider"]);
    $carcelUbicacionIdPostulada = isset($_POST["carcel_ubicacion_id"]) ? intval($_POST["carcel_ubicacion_id"]) : 0;

    /*
    *   Checkbox Sí/No: si no viene marcado, el navegador no envía el
    *   campo — se guarda 0 (No) en ese caso.
    */
    $valoresMapeo = array();
    foreach($camposMapeo as $campo => $etiqueta){
        $valoresMapeo[$campo] = (isset($_POST[$campo]) && $_POST[$campo] == "1") ? 1 : 0;
    }

    if($nombre_lider == ""){
        $errorReporte = "El nombre del líder es obligatorio.";
    }else if($carcelUbicacionIdPostulada <= 0){
        $errorReporte = "Debe seleccionar una cárcel.";
    }

    /*
    *   La foto es opcional al editar: si no se sube una nueva, se
    *   conserva la que ya tenía el reporte.
    */
    $extFoto = "";
    if($errorReporte == ""){
        $extensionesPermitidas = array("jpg", "jpeg", "png", "gif", "webp");
        if(isset($_FILES["foto"]) && $_FILES["foto"]["error"] == UPLOAD_ERR_OK && $_FILES["foto"]["name"] != ""){
            $extFoto = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));
            if(!in_array($extFoto, $extensionesPermitidas)){
                $errorReporte = "La foto debe ser una imagen (jpg, jpeg, png, gif o webp).";
                $extFoto = "";
            }
        }
    }

    if($errorReporte == ""){

        $asistencia_hom = intval($_POST["asistencia_hom"]);
        $asistencia_muj = intval($_POST["asistencia_muj"]);
        $asistencia_jov = intval($_POST["asistencia_jov"]);
        $asistencia_nin = intval($_POST["asistencia_nin"]);
        $total_creyentes_grupo = intval($_POST["total_creyentes_grupo"]);
        $nuevos_creyentes_grupo = intval($_POST["nuevos_creyentes_grupo"]);
        $total_bautizados_grupo = intval($_POST["total_bautizados_grupo"]);
        $nuevos_bautizados_grupo = intval($_POST["nuevos_bautizados_grupo"]);

        if(
            $asistencia_hom < 0 || $asistencia_muj < 0 || $asistencia_jov < 0 || $asistencia_nin < 0 ||
            $total_creyentes_grupo < 0 || $nuevos_creyentes_grupo < 0 ||
            $total_bautizados_grupo < 0 || $nuevos_bautizados_grupo < 0
        ){
            $errorReporte = "Ninguno de los campos numéricos puede ser negativo.";
        }

        /*
        *   Calculados SIEMPRE en el servidor, nunca confiando en un valor
        *   que llegue del navegador (mismo criterio de reportar_facilitador.php).
        */
        $asistencia_total = $asistencia_hom + $asistencia_muj + $asistencia_jov + $asistencia_nin;
        $asistencia_grupo = $total_creyentes_grupo + $nuevos_creyentes_grupo + $total_bautizados_grupo + $nuevos_bautizados_grupo;

        if($errorReporte == "" && $asistencia_total <= 0){
            $errorReporte = "La asistencia total debe ser mayor a 0.";
        }

        if($errorReporte == ""){

            /*
            *   Igual que en reportar_facilitador.php: se recibe el reub_id
            *   del <select>, pero se guarda el NOMBRE (columna de texto
            *   libre). Revalidación por zona pendiente (ver comentario más
            *   arriba, mismo motivo: registros con reub_reg_fk huérfano).
            */
            $carcel_ubicacion = "";
            if($carcelUbicacionIdPostulada > 0){
                $sqlNombreCarcel = "SELECT reub_nom FROM tbl_regional_ubicacion WHERE reub_id = ".$carcelUbicacionIdPostulada." LIMIT 1";
                $PSN2->query($sqlNombreCarcel);
                if($PSN2->num_rows() > 0){
                    $PSN2->next_record();
                    $carcel_ubicacion = $PSN2->f("reub_nom");
                }
            }
            $ubicacion = trim($_POST["ubicacion"]);
            $pabellon = trim($_POST["pabellon"]);
            $comentario = trim($_POST["comentario"]);

            $nombreLiderEscapado = mysqli_real_escape_string($PSN1->Link_ID, $nombre_lider);
            $ubicacionEscapada = mysqli_real_escape_string($PSN1->Link_ID, $ubicacion);
            $carcelUbicacionSql = ($carcel_ubicacion == "") ? "NULL" : "'".mysqli_real_escape_string($PSN1->Link_ID, $carcel_ubicacion)."'";
            $pabellonSql = ($pabellon == "") ? "NULL" : "'".mysqli_real_escape_string($PSN1->Link_ID, $pabellon)."'";
            $comentarioSql = ($comentario == "") ? "NULL" : "'".mysqli_real_escape_string($PSN1->Link_ID, $comentario)."'";

            /*
            *   Foto: si se subió una nueva, se reemplaza el archivo físico
            *   (y se borra el anterior si tenía una extensión distinta);
            *   si no, se deja la columna `foto` tal cual estaba.
            */
            $fotoSqlSet = "";
            if($extFoto != ""){
                if(!is_dir("archivos")){
                    mkdir("archivos", 0755, true);
                }
                if($reporte["foto"] != "" && $reporte["foto"] != $extFoto){
                    $rutaAnterior = "archivos/facilitador_".$idReporte.".".$reporte["foto"];
                    if(file_exists($rutaAnterior)){
                        unlink($rutaAnterior);
                    }
                }
                move_uploaded_file($_FILES["foto"]["tmp_name"], "archivos/facilitador_".$idReporte.".".$extFoto);
                $fotoSqlSet = ", foto = '".$extFoto."'";
            }

            /*
            *   idgrupo, idusuario, tipo_reporte, nombre_grupo, generacion,
            *   grupo_madre y fecha_inicio NO se tocan: son la fotografía
            *   histórica del momento en que se creó el reporte.
            */
            $sqlUpdate = "UPDATE ecu_reportes SET
                nombre_lider = '".$nombreLiderEscapado."',
                ubicacion = '".$ubicacionEscapada."',
                asistencia_hom = ".$asistencia_hom.",
                asistencia_muj = ".$asistencia_muj.",
                asistencia_jov = ".$asistencia_jov.",
                asistencia_nin = ".$asistencia_nin.",
                asistencia_total = ".$asistencia_total.",
                total_creyentes_grupo = ".$total_creyentes_grupo.",
                nuevos_creyentes_grupo = ".$nuevos_creyentes_grupo.",
                total_bautizados_grupo = ".$total_bautizados_grupo.",
                nuevos_bautizados_grupo = ".$nuevos_bautizados_grupo.",
                asistencia_grupo = ".$asistencia_grupo.",
                mapeo_oracion = ".$valoresMapeo["mapeo_oracion"].",
                mapeo_companerismo = ".$valoresMapeo["mapeo_companerismo"].",
                mapeo_adoracion = ".$valoresMapeo["mapeo_adoracion"].",
                mapeo_biblia = ".$valoresMapeo["mapeo_biblia"].",
                mapeo_evangelizar = ".$valoresMapeo["mapeo_evangelizar"].",
                mapeo_cena = ".$valoresMapeo["mapeo_cena"].",
                mapeo_dar = ".$valoresMapeo["mapeo_dar"].",
                mapeo_bautizar = ".$valoresMapeo["mapeo_bautizar"].",
                mapeo_trabajadores = ".$valoresMapeo["mapeo_trabajadores"].",
                comentario = ".$comentarioSql.",
                carcel_ubicacion = ".$carcelUbicacionSql.",
                pabellon = ".$pabellonSql.$fotoSqlSet."
                WHERE idreporte = ".$idReporte;
            $PSN1->query($sqlUpdate);

            /*
            *   Patrón POST/Redirect/GET (igual que reportar_facilitador.php):
            *   se redirige desde el cliente para que un F5 posterior sea un
            *   GET y no repita el UPDATE.
            */
            $urlRedirect = "index.php?doc=".urlencode($_GET["doc"])."&idreporte=".$idReporte."&actualizado=1";
            ?><script>window.location.replace(<?=json_encode($urlRedirect); ?>);</script><?php
            return;
        }
    }
}

/*
*   Repobla el formulario: si viene de un error de validación usa lo que el
*   usuario ya había escrito ($_POST); si no, usa el valor guardado del
*   reporte ($reporte).
*/
$disabled = $puedeEditar ? "" : 'disabled="disabled"';

function valorCampo($nombre, $reporte, $default = ""){
    if(isset($_POST[$nombre])){
        return htmlspecialchars(trim($_POST[$nombre]), ENT_QUOTES, "UTF-8");
    }
    if(isset($reporte[$nombre]) && $reporte[$nombre] !== null){
        return htmlspecialchars($reporte[$nombre], ENT_QUOTES, "UTF-8");
    }
    return $default;
}
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
        --success-bg: #E7F4EA;
        --success-text: #226B3C;
        --danger-bg: #FBEAEA;
        --danger-text: #A3302F;
        --warning-bg: #FCF3DC;
        --warning-text: #8A6414;
        --radius-card: 12px;
        --radius-control: 8px;

        background: #FFFFFF;
        color: var(--negro);
        font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        max-width: 980px;
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
        line-height: 1.25;
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
        text-align: left;
    }
    .ecu-wrap .ecu-banner.ecu-success { background: var(--success-bg); color: var(--success-text); }
    .ecu-wrap .ecu-banner.ecu-error { background: var(--danger-bg); color: var(--danger-text); }
    .ecu-wrap .ecu-banner.ecu-info { background: var(--azul-tint); color: var(--azul-dark); }

    .ecu-wrap .ecu-nav-reporte {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 16px;
    }
    .ecu-wrap .ecu-nav-id {
        font-size: 13px;
        font-weight: 600;
        color: var(--gris-texto);
    }
    .ecu-wrap .ecu-nav-deshabilitado {
        opacity: 0.4;
        pointer-events: none;
        cursor: default;
    }

    .ecu-wrap .ecu-grupo-actual {
        background: var(--azul-tint);
        border: 1px solid var(--azul);
        border-radius: var(--radius-card);
        padding: 14px 18px;
        margin-bottom: 22px;
    }
    .ecu-wrap .ecu-grupo-actual-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .ecu-wrap .ecu-resumen-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid rgba(29, 95, 166, 0.25);
    }
    .ecu-wrap .ecu-resumen-item { display: flex; flex-direction: column; gap: 2px; }
    .ecu-wrap .ecu-resumen-label {
        font-size: 11.5px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--azul-dark);
        opacity: 0.75;
    }
    .ecu-wrap .ecu-resumen-valor {
        font-size: 14px;
        font-weight: 600;
        color: var(--negro);
    }
    .ecu-wrap .ecu-grupo-actual-nombre {
        font-family: 'Fraunces', Georgia, serif;
        font-weight: 500;
        font-size: 17px;
        color: var(--azul-dark);
        margin: 0;
    }
    .ecu-wrap .ecu-grupo-actual-gen {
        font-size: 12.5px;
        color: var(--gris-texto);
        margin: 2px 0 0;
    }
    .ecu-wrap .ecu-grupo-actual a {
        font-size: 13px;
        font-weight: 600;
        color: var(--azul);
        text-decoration: none;
        white-space: nowrap;
    }
    .ecu-wrap .ecu-grupo-actual a:hover { text-decoration: underline; }

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

    .ecu-wrap .ecu-seccion { margin-bottom: 26px; }
    .ecu-wrap .ecu-seccion:last-child { margin-bottom: 0; }
    .ecu-wrap .ecu-divider {
        border: none;
        border-top: 1px solid var(--line);
        margin: 26px 0;
    }

    .ecu-wrap label.ecu-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--negro);
        margin-bottom: 7px;
    }
    .ecu-wrap label.ecu-label .ecu-req { color: var(--azul); }
    .ecu-wrap label.ecu-label .ecu-opt { font-weight: 400; color: var(--gris-texto); font-size: 12.5px; }

    .ecu-wrap .ecu-field { margin-bottom: 18px; }
    .ecu-wrap .ecu-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .ecu-wrap .ecu-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }

    .ecu-wrap input[type="text"].ecu-input,
    .ecu-wrap input[type="number"].ecu-input,
    .ecu-wrap input[type="file"].ecu-input,
    .ecu-wrap textarea.ecu-input,
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
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .ecu-wrap textarea.ecu-input { resize: vertical; min-height: 90px; font-family: inherit; }
    .ecu-wrap input[type="text"].ecu-input:focus,
    .ecu-wrap input[type="number"].ecu-input:focus,
    .ecu-wrap textarea.ecu-input:focus,
    .ecu-wrap select.ecu-select:focus {
        border-color: var(--azul);
        box-shadow: 0 0 0 3px rgba(29, 95, 166, 0.15);
    }
    .ecu-wrap input[type="file"].ecu-input { padding: 9px 10px; font-size: 13px; }
    .ecu-wrap input.ecu-input[readonly] {
        background: #FFFFFF;
        border-style: dashed;
    }
    .ecu-wrap input.ecu-input[disabled],
    .ecu-wrap select.ecu-select[disabled],
    .ecu-wrap textarea.ecu-input[disabled] {
        background: #FFFFFF;
        opacity: 0.65;
        cursor: not-allowed;
    }

    .ecu-wrap .ecu-foto-input.ecu-input {
        padding: 14px 13px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    .ecu-wrap .ecu-foto-ayuda {
        font-size: 12.5px;
        color: var(--gris-texto);
        margin: 8px 0 0;
    }
    .ecu-wrap .ecu-fotos-grid {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 6px;
    }
    .ecu-wrap .ecu-foto-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
    .ecu-wrap .ecu-foto-item a { display: inline-block; }
    .ecu-wrap .ecu-foto-item img {
        display: block;
        width: 100%;
        max-width: 300px;
        max-height: 300px;
        object-fit: cover;
        border-radius: var(--radius-card);
        border: 1px solid var(--line);
        cursor: zoom-in;
    }

    .ecu-wrap .ecu-mapeo-fila { margin-top: 4px; }
    .ecu-wrap .ecu-mapeo-fila > div { margin-bottom: 16px; }
    .ecu-wrap .ecu-mapeo-toggle {
        margin-bottom: 0;
        padding: 14px 16px;
        border: 1px solid var(--line);
        border-radius: var(--radius-control);
        background: #FFFFFF;
        overflow: hidden;
    }
    .ecu-wrap .ecu-mapeo-toggle > .col-sm-12 { padding-left: 0; padding-right: 0; }
    .ecu-wrap .ecu-mapeo-toggle h5 { margin: 0; font-size: 14px; }

    .ecu-wrap .ecu-btn {
        font-family: 'Public Sans', sans-serif;
        font-size: 14px;
        font-weight: 600;
        border-radius: var(--radius-control);
        padding: 12px 22px;
        cursor: pointer;
        border: none;
        transition: background 0.15s ease, transform 0.05s ease;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }
    .ecu-wrap .ecu-btn:active { transform: scale(0.99); }
    .ecu-wrap .ecu-btn-primary { background: var(--verde); color: #FFFFFF; }
    .ecu-wrap .ecu-btn-primary:hover { background: var(--verde-dark); }
    .ecu-wrap .ecu-btn-secondary { background: var(--azul); color: #FFFFFF; border: 1.5px solid var(--azul); }
    .ecu-wrap .ecu-btn-secondary:hover { background: var(--azul-dark); border-color: var(--azul-dark); }
    .ecu-wrap .ecu-btn-danger { background: var(--danger-text); color: #FFFFFF; }
    .ecu-wrap .ecu-btn-danger:hover { background: #7E2523; }
    .ecu-wrap .ecu-btn-slim { padding: 8px 16px; font-size: 13px; }
    .ecu-wrap .ecu-btn-row { display: flex; justify-content: center; margin-top: 4px; }
    .ecu-wrap .ecu-btn-row-split { display: flex; justify-content: space-between; align-items: center; margin-top: 4px; }

    .ecu-wrap .ecu-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(26, 26, 26, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 1000;
    }
    .ecu-wrap .ecu-modal-overlay.oculto { display: none; }
    .ecu-wrap .ecu-modal-card {
        background: #FFFFFF;
        border-radius: var(--radius-card);
        padding: 26px;
        max-width: 420px;
        width: 100%;
        box-shadow: 0 16px 48px rgba(0,0,0,0.2);
    }
    .ecu-wrap .ecu-modal-icono {
        display: none;
        font-size: 34px;
        line-height: 1;
        margin: 0 0 10px;
    }
    .ecu-wrap .ecu-modal-icono.ecu-modal-icono-visible { display: block; }
    .ecu-wrap .ecu-modal-titulo {
        font-family: 'Fraunces', Georgia, serif;
        font-weight: 500;
        font-size: 18px;
        margin: 0 0 10px;
        color: var(--negro);
    }
    .ecu-wrap .ecu-modal-card.ecu-modal-error .ecu-modal-titulo { color: var(--danger-text); }
    .ecu-wrap .ecu-modal-mensaje {
        font-size: 14px;
        color: var(--gris-texto);
        line-height: 1.5;
        margin: 0 0 22px;
        text-align: left;
    }
    .ecu-wrap .ecu-modal-botones {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    @media (max-width: 640px) {
        .ecu-wrap .ecu-grid-2 { grid-template-columns: 1fr; }
        .ecu-wrap .ecu-grid-4 { grid-template-columns: 1fr 1fr; }
        .ecu-wrap .ecu-resumen-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="ecu-wrap">

    <p class="ecu-eyebrow">ECU · Reportes</p>
    <h3 class="ecu-title"><?=$puedeEditar ? "Editar" : "Consultar"; ?> reporte de <?=$temp_letrero; ?></h3>
    <h5 class="ecu-subtitle"><?=$puedeEditar ? "Modifique la información y guarde los cambios" : "Información del reporte (solo lectura)"; ?></h5>

    <?php if($errorReporte != ""){ ?>
        <div class="ecu-banner ecu-error"><?=htmlspecialchars($errorReporte, ENT_QUOTES, "UTF-8"); ?></div>
    <?php } ?>

    <?php if(!$puedeEditar){ ?>
        <div class="ecu-banner ecu-info">Solo un administrador puede editar o eliminar este reporte. Aquí solo puede consultar la información.</div>
    <?php } ?>

    <div class="ecu-nav-reporte">
        <?php if($idReporteAnterior > 0){ ?>
            <a href="index.php?doc=editar_facilitador&idreporte=<?=$idReporteAnterior; ?>" class="ecu-btn ecu-btn-secondary ecu-btn-slim" style="text-decoration:none;">&laquo; Anterior</a>
        <?php }else{ ?>
            <span class="ecu-btn ecu-btn-secondary ecu-btn-slim ecu-nav-deshabilitado">&laquo; Anterior</span>
        <?php } ?>

        <span class="ecu-nav-id">Reporte #<?=str_pad($reporte["idreporte"], 6, "0", STR_PAD_LEFT); ?></span>

        <?php if($idReporteSiguiente > 0){ ?>
            <a href="index.php?doc=editar_facilitador&idreporte=<?=$idReporteSiguiente; ?>" class="ecu-btn ecu-btn-secondary ecu-btn-slim" style="text-decoration:none;">Siguiente &raquo;</a>
        <?php }else{ ?>
            <span class="ecu-btn ecu-btn-secondary ecu-btn-slim ecu-nav-deshabilitado">Siguiente &raquo;</span>
        <?php } ?>
    </div>

    <div class="ecu-grupo-actual">
        <div class="ecu-grupo-actual-top">
            <div>
                <p class="ecu-grupo-actual-nombre"><?=htmlspecialchars($reporte["nombre_grupo"], ENT_QUOTES, "UTF-8"); ?></p>
                <p class="ecu-grupo-actual-gen">Generación <?=$reporte["generacion"]; ?></p>
            </div>
            <a href="index.php?doc=consultar-facilitador">Volver a consultar reportes</a>
        </div>
        <div class="ecu-resumen-grid">
            <div class="ecu-resumen-item">
                <span class="ecu-resumen-label">ID de grupo</span>
                <span class="ecu-resumen-valor"><?=$reporte["idgrupo"]; ?></span>
            </div>
            <div class="ecu-resumen-item">
                <span class="ecu-resumen-label">Usuario que reporta</span>
                <span class="ecu-resumen-valor"><?=htmlspecialchars($reporte["nombre_usuario_reporta"], ENT_QUOTES, "UTF-8"); ?></span>
            </div>
            <div class="ecu-resumen-item">
                <span class="ecu-resumen-label">Fecha del reporte</span>
                <span class="ecu-resumen-valor"><?=date("d/m/Y", strtotime($reporte["fecha_inicio"])); ?></span>
            </div>
        </div>
    </div>

    <form method="post" id="formReporte" name="formReporte" enctype="multipart/form-data">
        <input type="hidden" name="funcion" value="actualizar_reporte" />
        <input type="hidden" name="idreporte" value="<?=$reporte["idreporte"]; ?>" />

        <div class="ecu-card">

            <div class="ecu-seccion">
                <div class="ecu-field" style="margin-bottom:0;">
                    <label class="ecu-label">Nombre del líder <span class="ecu-req">*</span></label>
                    <input type="text" name="nombre_lider" class="ecu-input" maxlength="150" required value="<?=valorCampo('nombre_lider', $reporte); ?>" <?=$disabled; ?> />
                </div>
            </div>

            <hr class="ecu-divider" />

            <div class="ecu-seccion">
                <h4 class="ecu-section-title">Asistencia</h4>
                <p class="ecu-section-sub">Personas que asistieron este mes.</p>

                <div class="ecu-grid-4">
                    <div class="ecu-field">
                        <label class="ecu-label">Hombres</label>
                        <input type="number" name="asistencia_hom" id="asistencia_hom" class="ecu-input" min="0" value="<?=valorCampo('asistencia_hom', $reporte, '0'); ?>" <?=$disabled; ?> />
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Mujeres</label>
                        <input type="number" name="asistencia_muj" id="asistencia_muj" class="ecu-input" min="0" value="<?=valorCampo('asistencia_muj', $reporte, '0'); ?>" <?=$disabled; ?> />
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Jóvenes</label>
                        <input type="number" name="asistencia_jov" id="asistencia_jov" class="ecu-input" min="0" value="<?=valorCampo('asistencia_jov', $reporte, '0'); ?>" <?=$disabled; ?> />
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Niños</label>
                        <input type="number" name="asistencia_nin" id="asistencia_nin" class="ecu-input" min="0" value="<?=valorCampo('asistencia_nin', $reporte, '0'); ?>" <?=$disabled; ?> />
                    </div>
                </div>

                <div class="ecu-field" style="margin-bottom:0;">
                    <label class="ecu-label">Asistencia total</label>
                    <input type="text" id="asistencia_total_mostrar" class="ecu-input" readonly value="0" style="font-weight:600;" />
                </div>
            </div>

            <hr class="ecu-divider" />

            <div class="ecu-seccion">
                <h4 class="ecu-section-title">Crecimiento del grupo</h4>
                <p class="ecu-section-sub">Cifras acumuladas del grupo en el mes reportado.</p>

                <div class="ecu-grid-4">
                    <div class="ecu-field">
                        <label class="ecu-label">Nuevos creyentes</label>
                        <input type="number" name="nuevos_creyentes_grupo" id="nuevos_creyentes_grupo" class="ecu-input ecu-input-crecimiento" min="0" value="<?=valorCampo('nuevos_creyentes_grupo', $reporte, '0'); ?>" <?=$disabled; ?> />
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Total de creyentes</label>
                        <input type="number" name="total_creyentes_grupo" id="total_creyentes_grupo" class="ecu-input ecu-input-crecimiento" min="0" value="<?=valorCampo('total_creyentes_grupo', $reporte, '0'); ?>" <?=$disabled; ?> />
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Nuevos bautizados</label>
                        <input type="number" name="nuevos_bautizados_grupo" id="nuevos_bautizados_grupo" class="ecu-input ecu-input-crecimiento" min="0" value="<?=valorCampo('nuevos_bautizados_grupo', $reporte, '0'); ?>" <?=$disabled; ?> />
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Total bautizados</label>
                        <input type="number" name="total_bautizados_grupo" id="total_bautizados_grupo" class="ecu-input ecu-input-crecimiento" min="0" value="<?=valorCampo('total_bautizados_grupo', $reporte, '0'); ?>" <?=$disabled; ?> />
                    </div>
                </div>

                <div class="ecu-field" style="margin-bottom:0;">
                    <label class="ecu-label">Asistencia del grupo</label>
                    <input type="text" id="asistencia_grupo_mostrar" class="ecu-input" readonly value="0" style="font-weight:600;" />
                </div>
            </div>

            <hr class="ecu-divider" />

            <div class="ecu-seccion">
                <h4 class="ecu-section-title">Información de la cárcel</h4>
                <p class="ecu-section-sub">Campos exclusivos del reporte de Facilitadores.</p>

                <div class="ecu-grid-2">
                    <div class="ecu-field">
                        <label class="ecu-label">Cárcel / ubicación <span class="ecu-req">*</span></label>
                        <select name="carcel_ubicacion_id" id="carcelUbicacionSelect" class="ecu-select" required <?=$disabled; ?>>
                            <option value="">Seleccione una cárcel</option>
                            <?php
                            $carcelSeleccionadaId = isset($_POST["carcel_ubicacion_id"]) ? intval($_POST["carcel_ubicacion_id"]) : $carcelIdActual;
                            foreach($listaCarceles as $carcel){ ?>
                                <option value="<?=$carcel["id"]; ?>" <?php if($carcelSeleccionadaId == $carcel["id"]){ ?>selected="selected"<?php } ?>>
                                    <?=htmlspecialchars($carcel["nombre"], ENT_QUOTES, "UTF-8"); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Pabellón <span class="ecu-opt">(opcional)</span></label>
                        <input type="text" name="pabellon" class="ecu-input" maxlength="150" value="<?=valorCampo('pabellon', $reporte); ?>" <?=$disabled; ?> />
                    </div>
                </div>

                <div class="ecu-field" style="margin-bottom:0;">
                    <label class="ecu-label">Ubicación</label>
                    <input type="text" name="ubicacion" id="carcelDireccion" class="ecu-input" readonly value="<?=valorCampo('ubicacion', $reporte); ?>" />
                </div>
            </div>

            <hr class="ecu-divider" />

            <div class="ecu-seccion">
                <h4 class="ecu-section-title">Método de verificación</h4>
                <p class="ecu-section-sub">Active la actividad si el grupo la realizó.</p>

                <div class="row ecu-mapeo-fila">
                    <?php foreach($camposMapeo as $campo => $etiqueta){
                        $marcado = isset($_POST[$campo]) ? ($_POST[$campo] == "1") : ($reporte[$campo] == 1);
                    ?>
                        <div class="col-sm-4">
                            <div class="form-group ecu-mapeo-toggle">
                                <div class="col-sm-12 cont-flex-2 vl-cent fl-sbet">
                                    <div class="cont-flex-2 vl-cent">
                                        <img style="margin-right: 15px" width="35px" src="mapeo_img/<?=$campo; ?>2.png" class="img-responsive" />
                                        <h5><?=htmlspecialchars($etiqueta, ENT_QUOTES, "UTF-8"); ?></h5>
                                    </div>
                                    <label>
                                        <input type="checkbox" name="<?=$campo; ?>" value="1" <?php if($marcado){ ?>checked="checked"<?php } ?> <?=$disabled; ?> />
                                        <span class="check"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <hr class="ecu-divider" />

            <div class="ecu-seccion">
                <h4 class="ecu-section-title">Foto y comentario</h4>

                <div class="ecu-field">
                    <label class="ecu-label">Foto <span class="ecu-opt">(opcional al editar)</span></label>
                    <?php if($reporte["foto"] != ""){
                        $rutaFotoActual = "archivos/facilitador_".$reporte["idreporte"].".".$reporte["foto"];
                    ?>
                        <div class="ecu-fotos-grid">
                            <div class="ecu-foto-item">
                                <a href="<?=htmlspecialchars($rutaFotoActual, ENT_QUOTES, "UTF-8"); ?>" target="_blank" rel="noopener">
                                    <img src="<?=htmlspecialchars($rutaFotoActual, ENT_QUOTES, "UTF-8"); ?>" alt="Foto del reporte" />
                                </a>
                            </div>
                        </div>
                        <p class="ecu-foto-ayuda" style="text-align:center;">Clic en la foto para verla en tamaño completo. Suba un archivo nuevo solo si desea reemplazarla.</p>
                    <?php }else{ ?>
                        <p class="ecu-foto-ayuda" style="margin-top:0;">Este reporte no tiene foto todavía.</p>
                    <?php } ?>
                    <?php if($puedeEditar){ ?>
                        <input type="file" name="foto" id="fotoInput" class="ecu-input ecu-foto-input" accept=".jpg,.jpeg,.png,.gif,.webp" />
                        <p class="ecu-foto-ayuda">Formatos permitidos: JPG, PNG, GIF o WEBP.</p>
                    <?php } ?>
                </div>
                <div class="ecu-field" style="margin-bottom:0;">
                    <label class="ecu-label">Comentario <span class="ecu-opt">(opcional)</span></label>
                    <textarea name="comentario" class="ecu-input" <?=$disabled; ?>><?=valorCampo('comentario', $reporte); ?></textarea>
                </div>
            </div>

        </div>

        <?php if($puedeEditar){ ?>
            <div class="ecu-btn-row-split">
                <button type="button" class="ecu-btn ecu-btn-danger" id="btnEliminarReporte">Eliminar reporte</button>
                <button type="submit" class="ecu-btn ecu-btn-primary">Guardar cambios</button>
            </div>
        <?php } ?>
    </form>

    <div class="ecu-modal-overlay oculto" id="ecuModalOverlay">
        <div class="ecu-modal-card" id="ecuModalCard">
            <div class="ecu-modal-icono" id="ecuModalIcono"></div>
            <h4 class="ecu-modal-titulo" id="ecuModalTitulo">Aviso</h4>
            <p class="ecu-modal-mensaje" id="ecuModalMensaje"></p>
            <div class="ecu-modal-botones" id="ecuModalBotones"></div>
        </div>
    </div>

</div>

<script>
    (function(){
        var modalOverlay = document.getElementById('ecuModalOverlay');
        var modalCard = document.getElementById('ecuModalCard');
        var modalIcono = document.getElementById('ecuModalIcono');
        var modalTitulo = document.getElementById('ecuModalTitulo');
        var modalMensaje = document.getElementById('ecuModalMensaje');
        var modalBotones = document.getElementById('ecuModalBotones');
        var ICONOS_MODAL = { aviso: '✅', error: '❌', confirmar: '⚠️' };

        function cerrarModal(){
            if(modalOverlay){ modalOverlay.classList.add('oculto'); }
        }

        function mostrarModal(titulo, mensaje, tipo, botones){
            if(!modalOverlay){ return; }
            modalTitulo.textContent = titulo;
            modalMensaje.textContent = mensaje;
            modalCard.className = 'ecu-modal-card' + (tipo ? ' ecu-modal-' + tipo : '');
            if(modalIcono){
                var icono = ICONOS_MODAL[tipo] || '';
                modalIcono.textContent = icono;
                modalIcono.classList.toggle('ecu-modal-icono-visible', icono !== '');
            }
            modalBotones.innerHTML = '';
            botones.forEach(function(b){
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ecu-btn ecu-btn-slim ' + (b.clase || 'ecu-btn-secondary');
                btn.textContent = b.texto;
                btn.addEventListener('click', function(){
                    cerrarModal();
                    if(b.onClick){ b.onClick(); }
                });
                modalBotones.appendChild(btn);
            });
            modalOverlay.classList.remove('oculto');
        }

        function mostrarAviso(mensaje, titulo){
            mostrarModal(titulo || 'Aviso', mensaje, 'aviso', [
                { texto: 'Entendido', clase: 'ecu-btn-secondary' }
            ]);
        }

        function mostrarError(mensaje, titulo){
            mostrarModal(titulo || 'No fue posible completar la acción', mensaje, 'error', [
                { texto: 'Entendido', clase: 'ecu-btn-secondary' }
            ]);
        }

        function mostrarConfirmacion(mensaje, onConfirmar, titulo, opciones){
            opciones = opciones || {};
            mostrarModal(titulo || 'Confirmar acción', mensaje, 'confirmar', [
                { texto: opciones.textoNo || 'Cancelar', clase: 'ecu-btn-secondary', onClick: opciones.onCancelar },
                { texto: opciones.textoSi || 'Eliminar', clase: opciones.claseSi || 'ecu-btn-danger', onClick: onConfirmar }
            ]);
        }

        /*
        *   Eliminar reporte: mismo patrón de confirmación + AJAX que ya
        *   usa gestionar-facilitador.php para eliminar un grupo.
        */
        var btnEliminarReporte = document.getElementById('btnEliminarReporte');
        if(btnEliminarReporte){
            btnEliminarReporte.addEventListener('click', function(){
                mostrarConfirmacion(
                    '¿Está seguro que desea eliminar este reporte? Esta acción no se puede deshacer.',
                    function(){
                        btnEliminarReporte.disabled = true;

                        var datos = new URLSearchParams();
                        datos.set('idreporte', <?=$reporte["idreporte"]; ?>);

                        fetch('ajax_eliminar_reporte_facilitador.php', { method: 'POST', credentials: 'same-origin', body: datos })
                            .then(function(resp){ return resp.json(); })
                            .then(function(data){
                                if(!data.ok){
                                    btnEliminarReporte.disabled = false;
                                    mostrarError(data.mensaje || 'No se pudo eliminar el reporte.');
                                    return;
                                }
                                window.location.href = 'index.php?doc=consultar-facilitador';
                            })
                            .catch(function(){
                                btnEliminarReporte.disabled = false;
                                mostrarError('Ocurrió un error de conexión al eliminar el reporte. Intenta de nuevo.');
                            });
                    },
                    'Eliminar reporte'
                );
            });
        }

        var camposAsistencia = ['asistencia_hom', 'asistencia_muj', 'asistencia_jov', 'asistencia_nin'];
        var camposCrecimiento = ['nuevos_creyentes_grupo', 'total_creyentes_grupo', 'nuevos_bautizados_grupo', 'total_bautizados_grupo'];
        var asistenciaTotalMostrar = document.getElementById('asistencia_total_mostrar');
        var asistenciaGrupoMostrar = document.getElementById('asistencia_grupo_mostrar');

        function sumarCampos(nombres){
            var total = 0;
            nombres.forEach(function(nombre){
                var input = document.getElementById(nombre);
                total += input ? (parseInt(input.value, 10) || 0) : 0;
            });
            return total;
        }

        var asistenciaHomInput = document.getElementById('asistencia_hom');

        function actualizarAsistenciaTotal(){
            var total = sumarCampos(camposAsistencia);
            if(asistenciaTotalMostrar){ asistenciaTotalMostrar.value = total; }
            if(asistenciaHomInput){
                asistenciaHomInput.setCustomValidity(total <= 0 ? 'La asistencia total debe ser mayor a 0.' : '');
            }
            return total;
        }

        function actualizarAsistenciaGrupo(){
            if(asistenciaGrupoMostrar){ asistenciaGrupoMostrar.value = sumarCampos(camposCrecimiento); }
        }

        camposAsistencia.forEach(function(nombre){
            var input = document.getElementById(nombre);
            if(input){ input.addEventListener('input', actualizarAsistenciaTotal); }
        });
        camposCrecimiento.forEach(function(nombre){
            var input = document.getElementById(nombre);
            if(input){ input.addEventListener('input', actualizarAsistenciaGrupo); }
        });
        actualizarAsistenciaTotal();
        actualizarAsistenciaGrupo();

        var carcelSelect = document.getElementById('carcelUbicacionSelect');
        var carcelDireccion = document.getElementById('carcelDireccion');

        function cargarInfoCarcel(idCarcel){
            if(!idCarcel){
                if(carcelDireccion){ carcelDireccion.value = ''; }
                return;
            }
            if(carcelDireccion){ carcelDireccion.value = 'Cargando...'; }
            fetch('ajax_info_carcel.php?id_carcel=' + encodeURIComponent(idCarcel), { credentials: 'same-origin' })
                .then(function(resp){ return resp.json(); })
                .then(function(data){
                    if(!data.ok){
                        if(carcelDireccion){ carcelDireccion.value = ''; }
                        return;
                    }
                    if(carcelDireccion){ carcelDireccion.value = data.reub_dir || ''; }
                })
                .catch(function(){
                    if(carcelDireccion){ carcelDireccion.value = ''; }
                    mostrarError('No se pudo consultar la información de la cárcel.');
                });
        }

        /*
        *   A diferencia de reportar_facilitador.php, aquí el reporte ya
        *   tiene una "Ubicación" guardada de antes; solo se recalcula si el
        *   usuario cambia la cárcel seleccionada (no al cargar la página).
        */
        if(carcelSelect){
            carcelSelect.addEventListener('change', function(){
                cargarInfoCarcel(carcelSelect.value);
            });
        }

        <?php if($exitoReporte != ""){ ?>
        mostrarAviso(<?=json_encode($exitoReporte, JSON_UNESCAPED_UNICODE); ?>, 'Reporte actualizado con éxito');
        <?php } ?>

        if(modalOverlay){
            modalOverlay.addEventListener('click', function(e){
                if(e.target === modalOverlay){ cerrarModal(); }
            });
            document.addEventListener('keydown', function(e){
                if(e.key === 'Escape'){ cerrarModal(); }
            });
        }

        var url = new URL(window.location.href);
        if(url.searchParams.has('actualizado')){
            url.searchParams.delete('actualizado');
            window.history.replaceState({}, '', url.toString());
        }
    })();
</script>
