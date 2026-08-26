<?php
/*
*   Pantalla de selección/creación de grupo para el programa Facilitadores.
*   Es el paso obligatorio previo al formulario de reporte: sin un grupo
*   seleccionado (existente o recién creado) no se habilita continuar.
*   Trabaja sobre la tabla ecu_grupos (ver CLAUDE.md, sección
*   "Unificación de Facilitadores y ECC").
*/
$PSN1 = new DBbase_Sql;
$PSN2 = new DBbase_Sql;
$webArchivo = "preoperacional";
$temp_letrero = "FACILITADORES";

$idUsuarioSesion = isset($_SESSION["id"]) ? intval($_SESSION["id"]) : 0;

if($idUsuarioSesion == 0){
    ?><div class="row">
        <h5 class="alert alert-danger text-center">Debe iniciar sesión para continuar.</h5>
    </div><?php
    return;
}

// Se abre la conexión para poder escapar cadenas de forma segura más abajo.
$PSN1->connect();

$errorGrupo = "";
$exitoGrupo = "";
$idGrupoSeleccionado = isset($_REQUEST["idgrupo"]) ? intval($_REQUEST["idgrupo"]) : 0;

/*
*   CREAR GRUPO NUEVO
*/
if(isset($_POST["funcion"]) && $_POST["funcion"] == "crear_grupo"){

    $nombre_grupo = trim($_POST["nombre_grupo"]);
    $grupo_anterior = isset($_POST["grupo_anterior"]) ? intval($_POST["grupo_anterior"]) : 0;

    if($nombre_grupo == ""){
        $errorGrupo = "El nombre del grupo es obligatorio.";
    }else{

        $generacion = 2;
        $grupoAnteriorSql = "NULL";

        if($grupo_anterior > 0){
            /*
            *   El grupo "padre" debe pertenecer al usuario de sesión.
            *   Generación 0 y 1 nunca aparecen aquí (no viven en ecu_grupos
            *   con id_usuario de un facilitador, ver documentación).
            */
            $sqlPadre = "SELECT generacion FROM ecu_grupos WHERE id_grupo = ".$grupo_anterior." AND id_usuario = ".$idUsuarioSesion." LIMIT 1";
            $PSN2->query($sqlPadre);
            if($PSN2->num_rows() > 0){
                $PSN2->next_record();
                $generacion = intval($PSN2->f("generacion")) + 1;
                $grupoAnteriorSql = $grupo_anterior;
            }else{
                $errorGrupo = "El grupo seleccionado como antecesor no es válido.";
            }
        }

        if($errorGrupo == ""){
            $nombreGrupoEscapado = mysqli_real_escape_string($PSN1->Link_ID, $nombre_grupo);

            $sqlInsert = "INSERT INTO ecu_grupos (nombre_grupo, id_usuario, generacion, grupo_anterior, fecha_creacion) ";
            $sqlInsert .= "VALUES ('".$nombreGrupoEscapado."', ".$idUsuarioSesion.", ".$generacion.", ".$grupoAnteriorSql.", CURDATE())";
            $PSN1->query($sqlInsert);

            $idGrupoSeleccionado = $PSN1->ultimoId();
            $exitoGrupo = "Grupo creado correctamente.";
        }
    }
}

/*
*   SELECCIONAR UN GRUPO YA EXISTENTE
*/
if(isset($_POST["funcion"]) && $_POST["funcion"] == "seleccionar_grupo"){
    $idGrupoSeleccionado = isset($_POST["idgrupo"]) ? intval($_POST["idgrupo"]) : 0;
    if($idGrupoSeleccionado == 0){
        $errorGrupo = "Debe seleccionar un grupo de la lista.";
    }
}

/*
*   VALIDACIÓN: el grupo seleccionado (venga de crear o de elegir) debe
*   pertenecer al usuario de sesión y no ser generación 0 ni 1.
*/
if($idGrupoSeleccionado > 0){
    $sqlValida = "SELECT id_grupo, nombre_grupo, generacion FROM ecu_grupos ";
    $sqlValida .= "WHERE id_grupo = ".$idGrupoSeleccionado." AND id_usuario = ".$idUsuarioSesion." AND generacion NOT IN (0,1) LIMIT 1";
    $PSN1->query($sqlValida);
    if($PSN1->num_rows() > 0){
        $PSN1->next_record();
        $nombreGrupoSeleccionado = $PSN1->f("nombre_grupo");
        $generacionGrupoSeleccionado = $PSN1->f("generacion");
    }else{
        $idGrupoSeleccionado = 0;
    }
}

/*
*   LISTADO DE GRUPOS DISPONIBLES PARA EL USUARIO DE SESIÓN
*   (excluye generación 0 y 1; sirve tanto para elegir grupo como para
*   ofrecer las opciones de "grupo_anterior" al crear uno nuevo)
*/
$gruposDisponibles = array();
$sqlGrupos = "SELECT id_grupo, nombre_grupo, generacion, grupo_anterior, fecha_creacion ";
$sqlGrupos .= "FROM ecu_grupos WHERE id_usuario = ".$idUsuarioSesion." AND generacion NOT IN (0,1) ";
$sqlGrupos .= "ORDER BY generacion ASC, nombre_grupo ASC";
$PSN1->query($sqlGrupos);
if($PSN1->num_rows() > 0){
    while($PSN1->next_record()){
        $gruposDisponibles[] = array(
            "id_grupo"       => $PSN1->f("id_grupo"),
            "nombre_grupo"   => $PSN1->f("nombre_grupo"),
            "generacion"     => $PSN1->f("generacion"),
            "grupo_anterior" => $PSN1->f("grupo_anterior"),
            "fecha_creacion" => $PSN1->f("fecha_creacion"),
        );
    }
}
?>
<style>
    /* Todo el bloque queda bajo .ecu-wrap para no afectar el resto del sitio */
    .ecu-wrap {
        --azul: #1D5FA6;
        --azul-dark: #154A82;
        --azul-tint: #E8F0FA;
        --verde: #2E8B4F;
        --verde-dark: #226B3C;
        --verde-tint: #E7F4EA;
        --negro: #1A1A1A;
        --gris-texto: #55595C;
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
        max-width: 720px;
        margin: 0 auto;
        padding: 32px 16px 64px;
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
        text-align: left;
    }
    .ecu-wrap h5.ecu-subtitle {
        font-size: 14px;
        font-weight: 400;
        color: var(--gris-texto);
        margin: 0 0 28px;
    }

    .ecu-wrap .ecu-banner {
        padding: 13px 16px;
        border-radius: var(--radius-control);
        font-size: 14px;
        margin-bottom: 24px;
        text-align: left;
    }
    .ecu-wrap .ecu-banner.ecu-success { background: var(--success-bg); color: var(--success-text); }
    .ecu-wrap .ecu-banner.ecu-error { background: var(--danger-bg); color: var(--danger-text); }
    .ecu-wrap .ecu-banner.ecu-warning { background: var(--warning-bg); color: var(--warning-text); }
    .ecu-wrap .ecu-banner.ecu-info { background: var(--azul-tint); color: var(--azul-dark); }

    .ecu-wrap .ecu-card {
        background: #FFFFFF;
        border: 1px solid var(--line);
        border-radius: var(--radius-card);
        padding: 24px;
        margin-bottom: 20px;
    }

    .ecu-wrap .ecu-section-title {
        font-family: 'Fraunces', Georgia, serif;
        font-weight: 500;
        font-size: 17px;
        margin: 0 0 4px;
        color: var(--negro);
        text-align: left;
    }
    .ecu-wrap .ecu-section-sub {
        font-size: 13px;
        color: var(--gris-texto);
        margin: 0 0 18px;
        text-align: left;
    }

    /* Anillo generacional: aro azul, aro verde interior */
    .ecu-wrap .ecu-ring {
        position: relative;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--azul-tint);
        border: 2px solid var(--azul);
        display: flex;
        align-items: center;
        justify-content: center;
        flex: none;
    }
    .ecu-wrap .ecu-ring::before {
        content: "";
        position: absolute;
        inset: 4px;
        border-radius: 50%;
        border: 1.5px solid var(--verde);
    }
    .ecu-wrap .ecu-ring::after {
        content: "";
        position: absolute;
        inset: 9px;
        border-radius: 50%;
        border: 1px solid var(--negro);
        opacity: 0.35;
    }
    .ecu-wrap .ecu-ring span {
        position: relative;
        z-index: 1;
        font-family: 'IBM Plex Mono', monospace;
        font-weight: 600;
        font-size: 11px;
        color: var(--azul-dark);
    }

    .ecu-wrap .ecu-group-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 20px;
    }
    .ecu-wrap .ecu-group-option {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 13px 15px;
        border: 1.5px solid var(--line);
        border-radius: var(--radius-control);
        cursor: pointer;
        transition: border-color 0.15s ease, background 0.15s ease;
        margin: 0;
    }
    .ecu-wrap .ecu-group-option:hover { border-color: var(--line-strong); }
    .ecu-wrap .ecu-group-option.ecu-selected {
        border-color: var(--azul);
        background: var(--azul-tint);
    }
    .ecu-wrap .ecu-group-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .ecu-wrap .ecu-group-meta { flex: 1; min-width: 0; }
    .ecu-wrap .ecu-group-name {
        font-weight: 600;
        font-size: 14.5px;
        color: var(--negro);
        margin: 0 0 2px;
    }
    .ecu-wrap .ecu-group-detail {
        font-size: 12.5px;
        color: var(--gris-texto);
        margin: 0;
    }
    .ecu-wrap .ecu-check {
        width: 18px; height: 18px;
        border-radius: 50%;
        border: 1.5px solid var(--line-strong);
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .ecu-wrap .ecu-group-option.ecu-selected .ecu-check {
        border-color: var(--azul);
        background: var(--azul);
    }
    .ecu-wrap .ecu-group-option.ecu-selected .ecu-check::after {
        content: "";
        width: 6px; height: 6px;
        border-radius: 50%;
        background: #FFFFFF;
    }

    .ecu-wrap label.ecu-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--negro);
        margin-bottom: 7px;
        text-align: left;
    }
    .ecu-wrap label.ecu-label .ecu-req { color: var(--azul); }
    .ecu-wrap label.ecu-label .ecu-opt { font-weight: 400; color: var(--gris-texto); font-size: 12.5px; }

    .ecu-wrap .ecu-field-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 20px;
    }

    .ecu-wrap input[type="text"].ecu-input,
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
        height: auto;
    }
    .ecu-wrap input[type="text"].ecu-input:focus,
    .ecu-wrap select.ecu-select:focus {
        border-color: var(--azul);
        box-shadow: 0 0 0 3px rgba(29, 95, 166, 0.15);
    }

    .ecu-wrap .ecu-btn {
        font-family: 'Public Sans', sans-serif;
        font-size: 14px;
        font-weight: 600;
        border-radius: var(--radius-control);
        padding: 12px 22px;
        cursor: pointer;
        border: none;
        transition: background 0.15s ease, transform 0.05s ease;
    }
    .ecu-wrap .ecu-btn:active { transform: scale(0.99); }
    .ecu-wrap .ecu-btn-primary { background: var(--verde); color: #FFFFFF; }
    .ecu-wrap .ecu-btn-primary:hover { background: var(--verde-dark); }
    .ecu-wrap .ecu-btn-secondary { background: var(--azul); color: #FFFFFF; border: 1.5px solid var(--azul); }
    .ecu-wrap .ecu-btn-secondary:hover { background: var(--azul-dark); border-color: var(--azul-dark); }

    .ecu-wrap .ecu-btn-row { display: flex; justify-content: center; margin-top: 4px; }

    .ecu-wrap .ecu-report-stub {
        border: 1.5px dashed var(--line-strong);
        border-radius: var(--radius-control);
        padding: 22px;
        text-align: center;
        font-size: 13.5px;
        color: var(--gris-texto);
    }

    /* CREAR GRUPO — fila superior, a todo el ancho */
    .ecu-wrap .ecu-crear-grupo-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        align-items: end;
    }

    /* Fila inferior: listado de grupos + formulario de reporte lado a lado */
    .ecu-wrap .ecu-fila-inferior {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        align-items: start;
    }
    .ecu-wrap .ecu-fila-inferior .ecu-card { margin-bottom: 0; }
    .ecu-wrap .ecu-panel-reporte { min-height: 220px; }

    /* ---------- ESCRITORIO ---------- */
    @media (min-width: 900px) {
        .ecu-wrap {
            max-width: 1180px;
            padding: 48px 32px 96px;
        }
        .ecu-wrap h3.ecu-title { font-size: 30px; }
        .ecu-wrap h5.ecu-subtitle { font-size: 15.5px; }

        .ecu-wrap .ecu-crear-grupo-row {
            grid-template-columns: 1fr 1fr auto;
        }

        .ecu-wrap .ecu-fila-inferior {
            grid-template-columns: 380px 1fr;
        }

        .ecu-wrap .ecu-card { padding: 28px; }
        .ecu-wrap .ecu-section-title { font-size: 18.5px; }
        .ecu-wrap .ecu-btn { padding: 12px 28px; }
    }

    @media (max-width: 560px) {
        .ecu-wrap .ecu-field-row { grid-template-columns: 1fr; }
        .ecu-wrap h3.ecu-title { font-size: 20px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .ecu-wrap * { transition: none !important; }
    }
</style>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500&family=Public+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@600&display=swap" rel="stylesheet">

<div class="ecu-wrap">

    <p class="ecu-eyebrow">ECU · Grupos</p>
    <h3 class="ecu-title">Reporte de <?=$temp_letrero; ?></h3>
    <h5 class="ecu-subtitle">Seleccione un grupo existente o cree uno nuevo para continuar</h5>

    <?php if($errorGrupo != ""){ ?>
        <div class="ecu-banner ecu-error"><?=htmlspecialchars($errorGrupo, ENT_QUOTES, "UTF-8"); ?></div>
    <?php } ?>
    <?php if($exitoGrupo != ""){ ?>
        <div class="ecu-banner ecu-success"><?=htmlspecialchars($exitoGrupo, ENT_QUOTES, "UTF-8"); ?></div>
    <?php } ?>

    <div class="ecu-card">
        <h4 class="ecu-section-title">Crear grupo nuevo</h4>
        <p class="ecu-section-sub">La generación se calcula automáticamente según el grupo del que parte.</p>

        <form method="post" id="formCrearGrupo" name="formCrearGrupo">
            <input type="hidden" name="funcion" value="crear_grupo" />

            <div class="ecu-crear-grupo-row">
                <div>
                    <label class="ecu-label">Nombre del grupo <span class="ecu-req">*</span></label>
                    <input type="text" name="nombre_grupo" id="nombre_grupo" maxlength="150" class="ecu-input" placeholder="Ej. Célula Vida Nueva" required />
                </div>
                <div>
                    <label class="ecu-label">Crear a partir de <span class="ecu-opt">(opcional)</span></label>
                    <select name="grupo_anterior" id="grupo_anterior" class="ecu-select">
                        <option value="">Ninguno (nuevo grupo de generación 2)</option>
                        <?php foreach($gruposDisponibles as $g){ ?>
                            <option value="<?=$g["id_grupo"]; ?>">
                                <?=htmlspecialchars($g["nombre_grupo"], ENT_QUOTES, "UTF-8"); ?> (Generación <?=$g["generacion"]; ?>)
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <input type="submit" name="button" value="Crear grupo" class="ecu-btn ecu-btn-secondary" style="width:100%;" />
                </div>
            </div>
        </form>
    </div>

    <div class="ecu-fila-inferior">
        <div class="ecu-card">
            <h4 class="ecu-section-title">Mis grupos</h4>
            <p class="ecu-section-sub">Elige el grupo al que le vas a hacer el reporte.</p>

            <form method="post" id="formGrupo" name="formGrupo">
                <input type="hidden" name="funcion" value="seleccionar_grupo" />

                <?php if(count($gruposDisponibles) > 0){ ?>
                    <div class="ecu-group-list" id="ecuGroupList">
                        <?php foreach($gruposDisponibles as $g){
                            $marcado = ($idGrupoSeleccionado == $g["id_grupo"]);
                            $fechaFmt = date("d/m/Y", strtotime($g["fecha_creacion"]));
                        ?>
                            <label class="ecu-group-option<?php if($marcado){ ?> ecu-selected<?php } ?>">
                                <input type="radio" name="idgrupo" value="<?=$g["id_grupo"]; ?>" <?php if($marcado){ ?>checked="checked"<?php } ?> required />
                                <span class="ecu-ring"><span><?=$g["generacion"]; ?></span></span>
                                <span class="ecu-group-meta">
                                    <p class="ecu-group-name"><?=htmlspecialchars($g["nombre_grupo"], ENT_QUOTES, "UTF-8"); ?></p>
                                    <p class="ecu-group-detail">Generación <?=$g["generacion"]; ?> · creado el <?=$fechaFmt; ?></p>
                                </span>
                                <span class="ecu-check"></span>
                            </label>
                        <?php } ?>
                    </div>
                    <div class="ecu-btn-row">
                        <input type="submit" name="button" value="Continuar con este grupo" class="ecu-btn ecu-btn-primary" />
                    </div>
                <?php }else{ ?>
                    <div class="ecu-banner ecu-info">Aún no tiene grupos creados. Cree uno nuevo arriba para continuar.</div>
                <?php } ?>
            </form>
        </div>

        <div class="ecu-card ecu-panel-reporte" style="border-style: dashed;">
            <h4 class="ecu-section-title">Formulario de reporte</h4>

            <?php if($idGrupoSeleccionado > 0){ ?>
                <div class="ecu-banner ecu-info" style="display:flex; align-items:center; gap:10px;">
                    <span class="ecu-ring" style="width:28px;height:28px;"><span><?=$generacionGrupoSeleccionado; ?></span></span>
                    Grupo seleccionado: <strong><?=htmlspecialchars($nombreGrupoSeleccionado, ENT_QUOTES, "UTF-8"); ?></strong>
                    (Generación <?=$generacionGrupoSeleccionado; ?>)
                </div>
                <!--
                    A partir de aquí va el formulario de reporte de Facilitadores
                    (pendiente de implementación), usando $idGrupoSeleccionado como
                    idgrupo del reporte en ecu_reportes.
                -->
            <?php }else{ ?>
                <div class="ecu-banner ecu-warning">Debe seleccionar o crear un grupo antes de continuar al formulario de reporte.</div>
            <?php } ?>
        </div>
    </div>

</div>

<script>
    (function(){
        var lista = document.getElementById('ecuGroupList');
        if(!lista){ return; }
        lista.addEventListener('click', function(e){
            var opcion = e.target.closest('.ecu-group-option');
            if(!opcion){ return; }
            var todas = lista.querySelectorAll('.ecu-group-option');
            for(var i = 0; i < todas.length; i++){
                todas[i].classList.remove('ecu-selected');
            }
            opcion.classList.add('ecu-selected');
            var input = opcion.querySelector('input[type="radio"]');
            if(input){ input.checked = true; }
        });
    })();
</script>