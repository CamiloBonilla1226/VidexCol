<?php
/*
*   Formulario de reporte de un grupo de Facilitadores.
*   Inserta en ecu_reportes con tipo_reporte = 318 (fijo). Solo es accesible
*   después de haber seleccionado un grupo en gestionar-facilitador.php
*   (Punto 1: selección/creación de grupo). Ver CLAUDE.md, sección
*   "Unificación de Facilitadores y ECC".
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

$idGrupo = isset($_REQUEST["idgrupo"]) ? intval($_REQUEST["idgrupo"]) : 0;

/*
*   El grupo debe pertenecer al usuario de sesión y no ser generación 0 ni 1
*   (misma regla que en gestionar-facilitador.php: esas generaciones no
*   viven en ecu_grupos como grupos "reportables" por un facilitador).
*/
$nombreGrupo = "";
$generacionGrupo = 0;
$grupoValido = false;

if($idGrupo > 0){
    $sqlGrupo = "SELECT id_grupo, nombre_grupo, generacion FROM ecu_grupos ";
    $sqlGrupo .= "WHERE id_grupo = ".$idGrupo." AND id_usuario = ".$idUsuarioSesion." AND generacion NOT IN (0,1) LIMIT 1";
    $PSN1->query($sqlGrupo);
    if($PSN1->num_rows() > 0){
        $PSN1->next_record();
        $nombreGrupo = $PSN1->f("nombre_grupo");
        $generacionGrupo = intval($PSN1->f("generacion"));
        $grupoValido = true;
    }
}

/*
*   Datos informativos de cabecera (solo lectura, nunca editables): el
*   usuario que reporta, el grupo, su generación y la fecha del reporte.
*/
$nombreUsuarioReporta = "";
$PSN8 = new DBbase_Sql;
$PSN8->query("SELECT nombre FROM usuario WHERE id = ".$idUsuarioSesion." LIMIT 1");
if($PSN8->num_rows() > 0){
    $PSN8->next_record();
    $nombreUsuarioReporta = $PSN8->f("nombre");
}
$fechaReporteHoy = date("d/m/Y");

if(!$grupoValido){
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@600&display=swap" rel="stylesheet">
    <div class="ecu-wrap">
        <p class="ecu-eyebrow">ECU · Reportes</p>
        <h3 class="ecu-title">Reporte de <?=$temp_letrero; ?></h3>
        <div class="ecu-banner ecu-error">No se encontró el grupo seleccionado, o no le pertenece a su usuario. Vuelva a la lista de grupos e inténtelo de nuevo.</div>
        <div class="ecu-btn-row">
            <a href="index.php?doc=gestionar-facilitador" class="ecu-btn ecu-btn-secondary" style="text-decoration:none;">Volver a mis grupos</a>
        </div>
    </div>
    <?php
    return;
}

/*
*   "Grupo madre": grupo de generación 1 del USUARIO QUE REPORTA (no del
*   creador del grupo seleccionado), calculado a partir de
*   usuario_empresa.empresa_proceso -> categorias.descripcion. Es una
*   fotografía en texto tomada al momento de guardar, no una relación (ver
*   CLAUDE.md, sección "ecu_reportes").
*/
$grupoMadre = null;
$sqlProceso = "SELECT empresa_proceso FROM usuario_empresa WHERE idUsuario = ".$idUsuarioSesion." LIMIT 1";
$PSN2->query($sqlProceso);
if($PSN2->num_rows() > 0){
    $PSN2->next_record();
    $empresaProcesoId = intval($PSN2->f("empresa_proceso"));
    if($empresaProcesoId > 0){
        $PSN3 = new DBbase_Sql;
        $sqlCategoria = "SELECT descripcion FROM categorias WHERE id = ".$empresaProcesoId." LIMIT 1";
        $PSN3->query($sqlCategoria);
        if($PSN3->num_rows() > 0){
            $PSN3->next_record();
            $grupoMadre = $PSN3->f("descripcion");
        }
    }
}

$errorReporte = "";
$exitoReporte = "";

if(isset($_GET["creado"]) && $_GET["creado"] == "1"){
    $exitoReporte = "Reporte guardado correctamente.";
}

/*
*   Ítems del "Método de verificación" (mapeo_*). Mismo set de 9 campos que
*   sat_reportes / gestionar-sub-programa-evangelistas.php: mismo checkbox
*   Sí/No (1 = Sí, 0 = No), mismos íconos (mapeo_img/{campo}2.png) y mismo
*   switch visual (.check, definido globalmente en estilos_chart.css).
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
*   Catálogo de cárceles: mismo select usado en
*   gestionar-sub-programa-evangelistas.php ("Cárcel ubicación", tabla
*   tbl_regional_ubicacion). El <select> guarda el reub_id; el NOMBRE
*   (reub_nom) es lo que finalmente se persiste en
*   ecu_reportes.carcel_ubicacion (columna de texto libre, no una relación
*   con tbl_regional_ubicacion).
*
*   PENDIENTE POR IMPLEMENTAR: filtro por regional del usuario. La idea es
*   que solo vea las cárceles de su propia zona
*   (tbl_regional_ubicacion.reub_reg_fk = usuario_empresa.empresa_pd),
*   salvo que sea usuario.tipo = 2 (ese sí vería todas, sin filtro). Se deja
*   comentado porque hay 8 registros en tbl_regional_ubicacion cuyo
*   reub_reg_fk no coincide con ningún empresa_pd (códigos huérfanos: 352,
*   354, 355, 358, 359, 363, 366) — hay que corregir esos datos antes de
*   activar el filtro, o esas cárceles quedarían invisibles para todos los
*   usuarios normales. Por ahora se muestran TODAS las cárceles, sin filtrar.
*/
$usuarioTipo = 0;
$PSN4 = new DBbase_Sql;
$PSN4->query("SELECT tipo FROM usuario WHERE id = ".$idUsuarioSesion." LIMIT 1");
if($PSN4->num_rows() > 0){
    $PSN4->next_record();
    $usuarioTipo = intval($PSN4->f("tipo"));
}

$empresaPd = 0;
$PSN5 = new DBbase_Sql;
$PSN5->query("SELECT empresa_pd FROM usuario_empresa WHERE idUsuario = ".$idUsuarioSesion." LIMIT 1");
if($PSN5->num_rows() > 0){
    $PSN5->next_record();
    $empresaPd = intval($PSN5->f("empresa_pd"));
}

$listaCarceles = array();
$sqlCarceles = "SELECT reub_id, reub_nom FROM tbl_regional_ubicacion";
/*
if($usuarioTipo != 2){
    $sqlCarceles .= " WHERE reub_reg_fk = ".$empresaPd;
}
*/
$sqlCarceles .= " ORDER BY reub_nom ASC";
$PSN6 = new DBbase_Sql;
$PSN6->query($sqlCarceles);
while($PSN6->next_record()){
    $listaCarceles[] = array(
        "id"     => intval($PSN6->f("reub_id")),
        "nombre" => $PSN6->f("reub_nom"),
    );
}

/*
*   GUARDAR REPORTE
*/
if(isset($_POST["funcion"]) && $_POST["funcion"] == "guardar_reporte"){

    $nombre_lider = trim($_POST["nombre_lider"]);
    $ubicacion = trim($_POST["ubicacion"]);
    $carcelUbicacionIdPostulada = isset($_POST["carcel_ubicacion_id"]) ? intval($_POST["carcel_ubicacion_id"]) : 0;

    /*
    *   Checkbox Sí/No: igual que en gestionar-sub-programa-evangelistas.php,
    *   si el checkbox no viene marcado el navegador simplemente no envía el
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
    }else if(!isset($_FILES["foto"]) || $_FILES["foto"]["error"] != UPLOAD_ERR_OK || $_FILES["foto"]["name"] == ""){
        $errorReporte = "La foto es obligatoria.";
    }

    $extFoto = "";
    if($errorReporte == ""){
        /*
        *   Foto: solo se guarda la extensión en la columna `foto`; el
        *   archivo físico se mueve después del INSERT, usando el id recién
        *   generado en el nombre (misma convención de
        *   gestionar-sub-programa-evangelistas.php: "archivos/evi_{id}_1.{ext}",
        *   adaptada aquí como "archivos/facilitador_{id}.{ext}").
        */
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
        *   que llegue del navegador — corrige de raíz el patrón visto en
        *   subcategoria-ecc.php (bloque "final_*" calculado en JS y
        *   guardado tal cual por el servidor).
        */
        $asistencia_total = $asistencia_hom + $asistencia_muj + $asistencia_jov + $asistencia_nin;
        $asistencia_grupo = $total_creyentes_grupo + $nuevos_creyentes_grupo + $total_bautizados_grupo + $nuevos_bautizados_grupo;

        if($errorReporte == "" && $asistencia_total <= 0){
            $errorReporte = "La asistencia total debe ser mayor a 0.";
        }

        if($errorReporte == ""){

            /*
            *   Se recibe el reub_id del <select>, pero se guarda el NOMBRE
            *   (columna de texto libre).
            *
            *   PENDIENTE POR IMPLEMENTAR: junto con el filtro de zona del
            *   <select> (ver comentario más arriba, donde se arma
            *   $listaCarceles), aquí debería revalidarse que el reub_id
            *   recibido pertenezca a la regional del usuario
            *   (reub_reg_fk = empresa_pd), para que nadie pueda enviar a
            *   mano el id de una cárcel de otra zona aunque el <select> del
            *   navegador ya venga filtrado. Queda comentado por la misma
            *   razón: los 8 registros con reub_reg_fk huérfano.
            */
            $carcel_ubicacion = "";
            $carcelUbicacionId = isset($_POST["carcel_ubicacion_id"]) ? intval($_POST["carcel_ubicacion_id"]) : 0;
            if($carcelUbicacionId > 0){
                $sqlNombreCarcel = "SELECT reub_nom FROM tbl_regional_ubicacion WHERE reub_id = ".$carcelUbicacionId;
                /*
                if($usuarioTipo != 2){
                    $sqlNombreCarcel .= " AND reub_reg_fk = ".$empresaPd;
                }
                */
                $sqlNombreCarcel .= " LIMIT 1";
                $PSN7 = new DBbase_Sql;
                $PSN7->query($sqlNombreCarcel);
                if($PSN7->num_rows() > 0){
                    $PSN7->next_record();
                    $carcel_ubicacion = $PSN7->f("reub_nom");
                }
            }
            $pabellon = trim($_POST["pabellon"]);
            $comentario = trim($_POST["comentario"]);

            $nombreLiderEscapado = mysqli_real_escape_string($PSN1->Link_ID, $nombre_lider);
            $ubicacionEscapada = mysqli_real_escape_string($PSN1->Link_ID, $ubicacion);
            $nombreGrupoEscapado = mysqli_real_escape_string($PSN1->Link_ID, $nombreGrupo);
            $grupoMadreSql = ($grupoMadre === null) ? "NULL" : "'".mysqli_real_escape_string($PSN1->Link_ID, $grupoMadre)."'";
            $carcelUbicacionSql = ($carcel_ubicacion == "") ? "NULL" : "'".mysqli_real_escape_string($PSN1->Link_ID, $carcel_ubicacion)."'";
            $pabellonSql = ($pabellon == "") ? "NULL" : "'".mysqli_real_escape_string($PSN1->Link_ID, $pabellon)."'";
            $comentarioSql = ($comentario == "") ? "NULL" : "'".mysqli_real_escape_string($PSN1->Link_ID, $comentario)."'";
            $fotoSql = ($extFoto == "") ? "NULL" : "'".$extFoto."'";

            $sqlInsert = "INSERT INTO ecu_reportes (
                idgrupo, idusuario, tipo_reporte, nombre_lider, nombre_grupo, fecha_inicio,
                generacion, grupo_madre, ubicacion,
                asistencia_hom, asistencia_muj, asistencia_jov, asistencia_nin, asistencia_total,
                total_creyentes_grupo, nuevos_creyentes_grupo, total_bautizados_grupo, nuevos_bautizados_grupo, asistencia_grupo,
                mapeo_oracion, mapeo_companerismo, mapeo_adoracion, mapeo_biblia, mapeo_evangelizar,
                mapeo_cena, mapeo_dar, mapeo_bautizar, mapeo_trabajadores,
                comentario, carcel_ubicacion, pabellon, foto
            ) VALUES (
                ".$idGrupo.", ".$idUsuarioSesion.", 318, '".$nombreLiderEscapado."', '".$nombreGrupoEscapado."', CURDATE(),
                ".$generacionGrupo.", ".$grupoMadreSql.", '".$ubicacionEscapada."',
                ".$asistencia_hom.", ".$asistencia_muj.", ".$asistencia_jov.", ".$asistencia_nin.", ".$asistencia_total.",
                ".$total_creyentes_grupo.", ".$nuevos_creyentes_grupo.", ".$total_bautizados_grupo.", ".$nuevos_bautizados_grupo.", ".$asistencia_grupo.",
                ".$valoresMapeo["mapeo_oracion"].", ".$valoresMapeo["mapeo_companerismo"].", ".$valoresMapeo["mapeo_adoracion"].", ".$valoresMapeo["mapeo_biblia"].", ".$valoresMapeo["mapeo_evangelizar"].",
                ".$valoresMapeo["mapeo_cena"].", ".$valoresMapeo["mapeo_dar"].", ".$valoresMapeo["mapeo_bautizar"].", ".$valoresMapeo["mapeo_trabajadores"].",
                ".$comentarioSql.", ".$carcelUbicacionSql.", ".$pabellonSql.", ".$fotoSql."
            )";
            $PSN1->query($sqlInsert);

            $idReporteNuevo = $PSN1->ultimoId();

            if($extFoto != ""){
                if(!is_dir("archivos")){
                    mkdir("archivos", 0755, true);
                }
                move_uploaded_file($_FILES["foto"]["tmp_name"], "archivos/facilitador_".$idReporteNuevo.".".$extFoto);
            }

            /*
            *   Patrón POST/Redirect/GET (igual que en gestionar-facilitador.php):
            *   ya se envió HTML antes de llegar a este include, así que
            *   header() no serviría. Se redirige desde el cliente para que un
            *   F5 posterior sea un GET y no repita el INSERT.
            */
            $urlRedirect = "index.php?doc=".urlencode($_GET["doc"])."&idgrupo=".$idGrupo."&creado=1";
            ?><script>window.location.replace(<?=json_encode($urlRedirect); ?>);</script><?php
            return;
        }
    }
}

/*
*   Ayuda para repoblar el formulario tal cual quedó tras un error de
*   validación, sin perder lo que el usuario ya había escrito.
*/
function valorPrevio($nombre, $default = ""){
    return isset($_POST[$nombre]) ? htmlspecialchars(trim($_POST[$nombre]), ENT_QUOTES, "UTF-8") : $default;
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
        background: #FFFFFF;
        border: 1px solid var(--line);
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

    /*
    *   La foto es obligatoria y antes casi no se notaba junto a los demás
    *   campos; se le da un poco más de tamaño, sin recuadro llamativo.
    */
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

    /*
    *   El toggle Sí/No del "Método de verificación" (.check) y las clases
    *   de layout (cont-flex-2, vl-cent, fl-sbet, row, col-sm-*) son las
    *   mismas de gestionar-sub-programa-evangelistas.php, ya cargadas
    *   globalmente por estilos_chart.css / Bootstrap. Aquí solo se les da
    *   más aire vertical y una tarjeta propia por ítem.
    */
    .ecu-wrap .ecu-mapeo-fila { margin-top: 4px; }
    .ecu-wrap .ecu-mapeo-fila > div { margin-bottom: 16px; }
    .ecu-wrap .ecu-mapeo-toggle {
        margin-bottom: 0;
        padding: 14px 16px;
        border: 1px solid var(--line);
        border-radius: var(--radius-control);
        background: #FFFFFF;
        /* .col-sm-12 de adentro flota (grid de Bootstrap); sin este
           clearfix la tarjeta colapsaba a 0px de alto y el borde/fondo
           quedaba desalineado del contenido real. */
        overflow: hidden;
    }
    /* El .col-sm-12 interno ya no necesita su padding lateral: lo
       reemplaza el padding de .ecu-mapeo-toggle, para que el contenido
       quede centrado dentro del recuadro y no descuadrado hacia un lado. */
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
    .ecu-wrap .ecu-btn-slim { padding: 8px 16px; font-size: 13px; }
    .ecu-wrap .ecu-btn-row { display: flex; justify-content: center; margin-top: 4px; }

    /* Modal propio (igual convención que gestionar-facilitador.php) */
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
    <h3 class="ecu-title">Reporte de <?=$temp_letrero; ?></h3>
    <h5 class="ecu-subtitle">Complete la información del mes para este grupo</h5>

    <?php if($errorReporte != ""){ ?>
        <div class="ecu-banner ecu-error"><?=htmlspecialchars($errorReporte, ENT_QUOTES, "UTF-8"); ?></div>
    <?php } ?>

    <div class="ecu-grupo-actual">
        <div class="ecu-grupo-actual-top">
            <div>
                <p class="ecu-grupo-actual-nombre"><?=htmlspecialchars($nombreGrupo, ENT_QUOTES, "UTF-8"); ?></p>
                <p class="ecu-grupo-actual-gen">Generación <?=$generacionGrupo; ?></p>
            </div>
            <a href="index.php?doc=gestionar-facilitador">Cambiar de grupo</a>
        </div>
        <div class="ecu-resumen-grid">
            <div class="ecu-resumen-item">
                <span class="ecu-resumen-label">ID de grupo</span>
                <span class="ecu-resumen-valor"><?=$idGrupo; ?></span>
            </div>
            <div class="ecu-resumen-item">
                <span class="ecu-resumen-label">Usuario que reporta</span>
                <span class="ecu-resumen-valor"><?=htmlspecialchars($nombreUsuarioReporta, ENT_QUOTES, "UTF-8"); ?></span>
            </div>
            <div class="ecu-resumen-item">
                <span class="ecu-resumen-label">Fecha del reporte</span>
                <span class="ecu-resumen-valor"><?=$fechaReporteHoy; ?></span>
            </div>
        </div>
    </div>

    <form method="post" id="formReporte" name="formReporte" enctype="multipart/form-data">
        <input type="hidden" name="funcion" value="guardar_reporte" />
        <input type="hidden" name="idgrupo" value="<?=$idGrupo; ?>" />

        <div class="ecu-card">

            <div class="ecu-seccion">
                <div class="ecu-field" style="margin-bottom:0;">
                    <label class="ecu-label">Nombre del líder <span class="ecu-req">*</span></label>
                    <input type="text" name="nombre_lider" class="ecu-input" maxlength="150" required value="<?=valorPrevio('nombre_lider'); ?>" />
                </div>
            </div>

            <hr class="ecu-divider" />

            <div class="ecu-seccion">
                <h4 class="ecu-section-title">Asistencia</h4>
                <p class="ecu-section-sub">Personas que asistieron este mes.</p>

                <div class="ecu-grid-4">
                    <div class="ecu-field">
                        <label class="ecu-label">Hombres</label>
                        <input type="number" name="asistencia_hom" id="asistencia_hom" class="ecu-input" min="0" value="<?=valorPrevio('asistencia_hom', '0'); ?>" />
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Mujeres</label>
                        <input type="number" name="asistencia_muj" id="asistencia_muj" class="ecu-input" min="0" value="<?=valorPrevio('asistencia_muj', '0'); ?>" />
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Jóvenes</label>
                        <input type="number" name="asistencia_jov" id="asistencia_jov" class="ecu-input" min="0" value="<?=valorPrevio('asistencia_jov', '0'); ?>" />
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Niños</label>
                        <input type="number" name="asistencia_nin" id="asistencia_nin" class="ecu-input" min="0" value="<?=valorPrevio('asistencia_nin', '0'); ?>" />
                    </div>
                </div>

                <div class="ecu-field" style="margin-bottom:0;">
                    <label class="ecu-label">Asistencia total</label>
                    <input type="text" id="asistencia_total_mostrar" class="ecu-input" readonly value="0" style="background: var(--gris-claro); font-weight:600;" />
                </div>
            </div>

            <hr class="ecu-divider" />

            <div class="ecu-seccion">
                <h4 class="ecu-section-title">Crecimiento del grupo</h4>
                <p class="ecu-section-sub">Cifras acumuladas del grupo en el mes reportado.</p>

                <div class="ecu-grid-4">
                    <div class="ecu-field">
                        <label class="ecu-label">Nuevos creyentes</label>
                        <input type="number" name="nuevos_creyentes_grupo" id="nuevos_creyentes_grupo" class="ecu-input ecu-input-crecimiento" min="0" value="<?=valorPrevio('nuevos_creyentes_grupo', '0'); ?>" />
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Total de creyentes</label>
                        <input type="number" name="total_creyentes_grupo" id="total_creyentes_grupo" class="ecu-input ecu-input-crecimiento" min="0" value="<?=valorPrevio('total_creyentes_grupo', '0'); ?>" />
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Nuevos bautizados</label>
                        <input type="number" name="nuevos_bautizados_grupo" id="nuevos_bautizados_grupo" class="ecu-input ecu-input-crecimiento" min="0" value="<?=valorPrevio('nuevos_bautizados_grupo', '0'); ?>" />
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Total bautizados</label>
                        <input type="number" name="total_bautizados_grupo" id="total_bautizados_grupo" class="ecu-input ecu-input-crecimiento" min="0" value="<?=valorPrevio('total_bautizados_grupo', '0'); ?>" />
                    </div>
                </div>

                <div class="ecu-field" style="margin-bottom:0;">
                    <label class="ecu-label">Asistencia del grupo</label>
                    <input type="text" id="asistencia_grupo_mostrar" class="ecu-input" readonly value="0" style="background: var(--gris-claro); font-weight:600;" />
                </div>
            </div>

            <hr class="ecu-divider" />

            <div class="ecu-seccion">
                <h4 class="ecu-section-title">Información de la cárcel</h4>
                <p class="ecu-section-sub">Campos exclusivos del reporte de Facilitadores.</p>

                <div class="ecu-grid-2">
                    <div class="ecu-field">
                        <label class="ecu-label">Cárcel / ubicación <span class="ecu-req">*</span></label>
                        <select name="carcel_ubicacion_id" id="carcelUbicacionSelect" class="ecu-select" required>
                            <option value="">Seleccione una cárcel</option>
                            <?php
                            $carcelSeleccionadaId = isset($_POST["carcel_ubicacion_id"]) ? intval($_POST["carcel_ubicacion_id"]) : 0;
                            foreach($listaCarceles as $carcel){ ?>
                                <option value="<?=$carcel["id"]; ?>" <?php if($carcelSeleccionadaId == $carcel["id"]){ ?>selected="selected"<?php } ?>>
                                    <?=htmlspecialchars($carcel["nombre"], ENT_QUOTES, "UTF-8"); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="ecu-field">
                        <label class="ecu-label">Pabellón <span class="ecu-opt">(opcional)</span></label>
                        <input type="text" name="pabellon" class="ecu-input" maxlength="150" value="<?=valorPrevio('pabellon'); ?>" />
                    </div>
                </div>

                <!--
                <div class="ecu-field" style="margin-bottom:0;">
                    <label class="ecu-label">Departamento</label>
                    <input type="text" id="carcelDepartamento" class="ecu-input" readonly value="" style="background: var(--gris-claro);" />
                </div>
                -->
                <div class="ecu-field" style="margin-bottom:0;">
                    <label class="ecu-label">Ubicación</label>
                    <input type="text" name="ubicacion" id="carcelDireccion" class="ecu-input" readonly value="<?=valorPrevio('ubicacion'); ?>" style="background: var(--gris-claro);" />
                </div>
            </div>

            <hr class="ecu-divider" />

            <div class="ecu-seccion">
                <h4 class="ecu-section-title">Método de verificación</h4>
                <p class="ecu-section-sub">Active la actividad si el grupo la realizó.</p>

                <div class="row ecu-mapeo-fila">
                    <?php foreach($camposMapeo as $campo => $etiqueta){
                        $marcado = isset($_POST[$campo]) && $_POST[$campo] == "1";
                    ?>
                        <div class="col-sm-4">
                            <div class="form-group ecu-mapeo-toggle">
                                <div class="col-sm-12 cont-flex-2 vl-cent fl-sbet">
                                    <div class="cont-flex-2 vl-cent">
                                        <img style="margin-right: 15px" width="35px" src="mapeo_img/<?=$campo; ?>2.png" class="img-responsive" />
                                        <h5><?=htmlspecialchars($etiqueta, ENT_QUOTES, "UTF-8"); ?></h5>
                                    </div>
                                    <label>
                                        <input type="checkbox" name="<?=$campo; ?>" value="1" <?php if($marcado){ ?>checked="checked"<?php } ?> />
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
                    <label class="ecu-label">Foto <span class="ecu-req">*</span></label>
                    <input type="file" name="foto" id="fotoInput" class="ecu-input ecu-foto-input" accept=".jpg,.jpeg,.png,.gif,.webp" required />
                    <p class="ecu-foto-ayuda">Formatos permitidos: JPG, PNG, GIF o WEBP.</p>
                </div>
                <div class="ecu-field" style="margin-bottom:0;">
                    <label class="ecu-label">Comentario <span class="ecu-opt">(opcional)</span></label>
                    <textarea name="comentario" class="ecu-input"><?=valorPrevio('comentario'); ?></textarea>
                </div>
            </div>

        </div>

        <div class="ecu-btn-row">
            <button type="submit" class="ecu-btn ecu-btn-primary">Guardar reporte</button>
        </div>
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

        /*
        *   Asistencia total y Asistencia del grupo: se muestran en vivo,
        *   son de solo lectura (no editables por el usuario) y no se
        *   validan entre sí (no hay tope de una sobre la otra).
        */
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

        /*
        *   La suma de asistencia > 0 no se puede expresar con atributos
        *   HTML (min/required), así que se usa la Constraint Validation
        *   API nativa del navegador: se marca "asistencia_hom" como
        *   inválido con setCustomValidity(), y al enviar el formulario el
        *   propio navegador salta a ese campo y muestra su globo de aviso
        *   nativo — igual que hace con cualquier otro campo requerido, sin
        *   modal propio.
        */
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

        /*
        *   Al elegir una cárcel del <select>, se consulta su departamento y
        *   dirección, y se muestran en dos campos de solo lectura.
        */
        var carcelSelect = document.getElementById('carcelUbicacionSelect');
        var carcelDepartamento = document.getElementById('carcelDepartamento');
        var carcelDireccion = document.getElementById('carcelDireccion');

        function limpiarInfoCarcel(){
            if(carcelDepartamento){ carcelDepartamento.value = ''; }
            if(carcelDireccion){ carcelDireccion.value = ''; }
        }

        function cargarInfoCarcel(idCarcel){
            if(!idCarcel){
                limpiarInfoCarcel();
                return;
            }
            if(carcelDepartamento){ carcelDepartamento.value = 'Cargando...'; }
            if(carcelDireccion){ carcelDireccion.value = 'Cargando...'; }
            fetch('ajax_info_carcel.php?id_carcel=' + encodeURIComponent(idCarcel), { credentials: 'same-origin' })
                .then(function(resp){ return resp.json(); })
                .then(function(data){
                    if(!data.ok){
                        limpiarInfoCarcel();
                        return;
                    }
                    if(carcelDepartamento){ carcelDepartamento.value = data.departamento || ''; }
                    if(carcelDireccion){ carcelDireccion.value = data.reub_dir || ''; }
                })
                .catch(function(){
                    limpiarInfoCarcel();
                    mostrarError('No se pudo consultar la información de la cárcel.');
                });
        }

        if(carcelSelect){
            carcelSelect.addEventListener('change', function(){
                cargarInfoCarcel(carcelSelect.value);
            });
            if(carcelSelect.value){ cargarInfoCarcel(carcelSelect.value); }
        }

        <?php if($exitoReporte != ""){ ?>
        mostrarAviso(<?=json_encode($exitoReporte, JSON_UNESCAPED_UNICODE); ?>, 'Reporte guardado con éxito');
        <?php } ?>

        if(modalOverlay){
            modalOverlay.addEventListener('click', function(e){
                if(e.target === modalOverlay){ cerrarModal(); }
            });
            document.addEventListener('keydown', function(e){
                if(e.key === 'Escape'){ cerrarModal(); }
            });
        }

        /*
        *   El "?creado=1" de la URL solo debe disparar el modal una vez.
        */
        var url = new URL(window.location.href);
        if(url.searchParams.has('creado')){
            url.searchParams.delete('creado');
            window.history.replaceState({}, '', url.toString());
        }
    })();
</script>
