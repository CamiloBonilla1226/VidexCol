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
$nombreCreadorGrupo = "";
$totalReportesGrupo = 0;
$fechaCreacionGrupoSeleccionado = "";
$generacionAnteriorNombre = null;
$generacionAnteriorMensaje = null;

if($idGrupoSeleccionado > 0){
    $sqlValida = "SELECT id_grupo, nombre_grupo, generacion, grupo_anterior, fecha_creacion, id_usuario FROM ecu_grupos ";
    $sqlValida .= "WHERE id_grupo = ".$idGrupoSeleccionado." AND id_usuario = ".$idUsuarioSesion." AND generacion NOT IN (0,1) LIMIT 1";
    $PSN1->query($sqlValida);
    if($PSN1->num_rows() > 0){
        $PSN1->next_record();
        $nombreGrupoSeleccionado = $PSN1->f("nombre_grupo");
        $generacionGrupoSeleccionado = $PSN1->f("generacion");
        $grupoAnteriorIdSeleccionado = intval($PSN1->f("grupo_anterior"));
        $fechaCreacionGrupoSeleccionado = $PSN1->f("fecha_creacion");
        $idUsuarioCreadorGrupo = intval($PSN1->f("id_usuario"));

        // Nombre de quien creó el grupo
        $PSN3 = new DBbase_Sql;
        $sqlCreador = "SELECT nombre FROM usuario WHERE id = ".$idUsuarioCreadorGrupo." LIMIT 1";
        $PSN3->query($sqlCreador);
        if($PSN3->num_rows() > 0){
            $PSN3->next_record();
            $nombreCreadorGrupo = $PSN3->f("nombre");
        }

        // Cantidad de reportes que tiene este grupo en ecu_reportes
        $PSN4 = new DBbase_Sql;
        $sqlConteo = "SELECT COUNT(*) AS total FROM ecu_reportes WHERE idgrupo = ".$idGrupoSeleccionado;
        $PSN4->query($sqlConteo);
        if($PSN4->num_rows() > 0){
            $PSN4->next_record();
            $totalReportesGrupo = intval($PSN4->f("total"));
        }

        /*
        *   Grupo/generación anterior:
        *   - Generación 2: no tiene "grupo_anterior" en ecu_grupos (la
        *     generación 1 no vive en esta tabla). Se calcula a partir de
        *     usuario_empresa.empresa_proceso del usuario que creó el grupo.
        *   - Generación 3 en adelante: se busca el nombre del grupo
        *     referenciado por grupo_anterior.
        */
        if($generacionGrupoSeleccionado == 2){
            $PSN5 = new DBbase_Sql;
            $sqlProceso = "SELECT empresa_proceso FROM usuario_empresa WHERE idUsuario = ".$idUsuarioCreadorGrupo." LIMIT 1";
            $PSN5->query($sqlProceso);
            $empresaProcesoId = 0;
            if($PSN5->num_rows() > 0){
                $PSN5->next_record();
                $empresaProcesoId = intval($PSN5->f("empresa_proceso"));
            }
            if($empresaProcesoId > 0){
                $PSN6 = new DBbase_Sql;
                $sqlCategoria = "SELECT descripcion FROM categorias WHERE id = ".$empresaProcesoId." LIMIT 1";
                $PSN6->query($sqlCategoria);
                if($PSN6->num_rows() > 0){
                    $PSN6->next_record();
                    $generacionAnteriorNombre = $PSN6->f("descripcion");
                }
            }
            if($generacionAnteriorNombre === null){
                $generacionAnteriorMensaje = "El usuario actualmente no pertenece a un grupo de generación 1.";
            }
        }else{
            if($grupoAnteriorIdSeleccionado > 0){
                $PSN5 = new DBbase_Sql;
                $sqlPadreNombre = "SELECT nombre_grupo FROM ecu_grupos WHERE id_grupo = ".$grupoAnteriorIdSeleccionado." LIMIT 1";
                $PSN5->query($sqlPadreNombre);
                if($PSN5->num_rows() > 0){
                    $PSN5->next_record();
                    $generacionAnteriorNombre = $PSN5->f("nombre_grupo");
                }
            }
            if($generacionAnteriorNombre === null){
                $generacionAnteriorMensaje = "Este grupo no tiene un grupo antecesor registrado.";
            }
        }
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
$sqlGrupos .= "ORDER BY fecha_creacion DESC, id_grupo DESC";
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

/*
*   SOLO VISUAL: iniciales del creador del grupo, para el avatar circular
*   de la ficha de información. No es una consulta ni una regla de
*   negocio nueva, solo se deriva del nombre que ya se trajo arriba.
*/
$inicialesCreadorGrupo = "";
if($nombreCreadorGrupo !== ""){
    $partesNombreCreador = preg_split('/\s+/', trim($nombreCreadorGrupo));
    $inicialesCreadorGrupo = mb_strtoupper(mb_substr($partesNombreCreador[0], 0, 1, "UTF-8"), "UTF-8");
    if(count($partesNombreCreador) > 1){
        $inicialesCreadorGrupo .= mb_strtoupper(mb_substr($partesNombreCreador[count($partesNombreCreador) - 1], 0, 1, "UTF-8"), "UTF-8");
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
        max-width: 720px;
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
        margin: 0 0 28px;
        text-align: center;
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
        font-family: 'Public Sans', sans-serif;
        font-weight: 700;
        font-size: 16px;
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


    .ecu-wrap .ecu-group-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 20px;
        max-height: 360px;
        overflow-y: auto;
        padding-right: 4px;
        overscroll-behavior: contain;
    }
    .ecu-wrap .ecu-buscador {
        margin-bottom: 14px;
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
    /* SOLO VISUAL: variante outline, usada en "Editar" y "Cancelar" para
       que se lean como acciones secundarias frente a "Guardar" (verde). */
    .ecu-wrap .ecu-btn-azul-outline { background: #FFFFFF; color: var(--azul); border: 1.5px solid var(--azul); }
    .ecu-wrap .ecu-btn-azul-outline:hover { background: var(--azul-tint); }

    .ecu-wrap .ecu-btn-row { display: flex; justify-content: center; margin-top: 4px; }

    .ecu-wrap .ecu-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }
    .ecu-wrap .ecu-panel-header .ecu-section-title { margin: 0; }
    .ecu-wrap .ecu-btn-slim {
        padding: 7px 16px;
        font-size: 12.5px;
        flex: none;
    }

    /* Panel "Información del grupo": ocupa toda la altura disponible de la
       ficha; las acciones (eliminar) se empujan al fondo con margin-top:auto */
    #ecuPanelInfo {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    #ecuPanelInfo .ecu-banner { margin-bottom: 0; }
    .ecu-wrap .ecu-panel-actions {
        margin-top: auto;
        padding-top: 18px;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    .ecu-wrap .oculto { display: none !important; }

    /* Nombre del grupo, campos de generación y "pertenece al grupo" */
    .ecu-wrap .ecu-info-nombre-wrap { margin-bottom: 20px; }
    .ecu-wrap .ecu-info-field { margin-bottom: 14px; }
    .ecu-wrap .ecu-info-valor {
        font-size: 15px;
        font-weight: 600;
        color: var(--negro);
        margin: 0;
    }
    .ecu-wrap .ecu-info-mensaje {
        font-size: 13px;
        font-style: italic;
        color: var(--gris-texto);
        margin: 0;
    }
    .ecu-wrap .ecu-info-eyebrow {
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--gris-texto);
        margin: 0 0 3px;
        font-weight: 600;
    }
    .ecu-wrap .ecu-input-mini {
        font-family: 'Public Sans', sans-serif;
        font-weight: 600;
        padding: 4px 7px;
        border: 1.5px solid transparent;
        border-radius: 6px;
        background: transparent;
        color: var(--negro);
        outline: none;
    }
    .ecu-wrap .ecu-input-mini:read-only { cursor: default; }
    .ecu-wrap .ecu-input-mini:not(:read-only) {
        border-color: var(--azul);
        background: #FFFFFF;
        box-shadow: 0 0 0 3px rgba(29, 95, 166, 0.15);
    }
    .ecu-wrap .ecu-input-mini.ecu-input-nombre-grande {
        font-family: 'Public Sans', sans-serif;
        font-size: 17px;
        font-weight: 700;
        width: 100%;
        max-width: 100%;
        padding: 2px 6px;
    }

    /* Tarjetas de estadísticas del grupo.
       SOLO VISUAL: fondo neutro (gris-claro) en vez de azul-tint, para que
       no compitan con el color de foco/selección del resto de la pantalla. */
    .ecu-wrap .ecu-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        margin-bottom: 10px;
    }
    .ecu-wrap .ecu-info-tile {
        background: var(--gris-claro);
        border-radius: var(--radius-control);
        padding: 8px 12px;
    }
    .ecu-wrap .ecu-info-tile-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--gris-texto);
        margin: 0 0 2px;
        font-weight: 600;
    }
    .ecu-wrap .ecu-info-tile-value {
        font-size: 15px;
        font-weight: 700;
        color: var(--negro);
        margin: 0;
        font-family: 'Public Sans', sans-serif;
    }
    .ecu-wrap .ecu-info-creador {
        font-size: 13px;
        color: var(--gris-texto);
        margin: 0;
        padding-bottom: 4px;
        border-bottom: 1px solid var(--line);
    }
    .ecu-wrap .ecu-info-creador strong { color: var(--negro); }

    /* SOLO VISUAL: filas con ícono para Generación / Grupo anterior /
       Creado por, en vez del texto plano apilado. */
    .ecu-wrap .ecu-meta-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-top: 1px solid var(--line);
    }
    .ecu-wrap .ecu-meta-row:first-of-type { border-top: none; padding-top: 0; }
    .ecu-wrap .ecu-meta-icon {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: var(--azul-tint);
        color: var(--azul-dark);
        display: flex; align-items: center; justify-content: center;
        flex: none;
    }
    .ecu-wrap .ecu-meta-avatar {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: var(--verde-tint);
        color: var(--verde-dark);
        font-weight: 700;
        font-size: 12px;
        display: flex; align-items: center; justify-content: center;
        flex: none;
    }
    .ecu-wrap .ecu-meta-label {
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--gris-texto);
        margin: 0 0 2px;
    }
    .ecu-wrap .ecu-meta-value {
        font-size: 14px;
        font-weight: 600;
        margin: 0;
        color: var(--negro);
    }
    .ecu-wrap .ecu-meta-value.ecu-meta-value-mensaje {
        font-style: italic;
        font-weight: 400;
        color: var(--gris-texto);
    }

    /* Modal propio de la aplicación (reemplaza alert()/confirm() nativos) */
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

    /* Fila inferior: listado de grupos + formulario de reporte lado a lado.
       align-items queda en su valor por defecto (stretch) para que ambas
       fichas terminen con la misma altura. */
    .ecu-wrap .ecu-fila-inferior {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    /* min-height: 0 evita el bug clásico de flex/grid donde un hijo con
       contenido largo (la lista de grupos, con varios grupos creados)
       infla la altura "auto" del contenedor por encima de lo que se ve,
       dejando un espacio en blanco fantasma debajo de las fichas. */
    .ecu-wrap .ecu-fila-inferior .ecu-card {
        margin-bottom: 0;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .ecu-wrap .ecu-panel-reporte { min-height: 220px; }
    #ecuPanelInfo { min-height: 0; }

    /* ---------- ESCRITORIO ---------- */
    @media (min-width: 900px) {
        .ecu-wrap {
            max-width: 1180px;
            padding: 48px 32px 32px;
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
                            <option value="<?=$g["id_grupo"]; ?>" data-generacion="<?=$g["generacion"]; ?>">
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
                    <div class="ecu-buscador">
                        <input type="text" id="buscarGrupo" class="ecu-input" placeholder="Buscar grupo por nombre..." />
                    </div>
                    <div class="ecu-group-list" id="ecuGroupList">
                        <?php foreach($gruposDisponibles as $g){
                            $marcado = ($idGrupoSeleccionado == $g["id_grupo"]);
                            $nombreDataAttr = htmlspecialchars(mb_strtolower($g["nombre_grupo"], "UTF-8"), ENT_QUOTES, "UTF-8");
                        ?>
                            <label class="ecu-group-option<?php if($marcado){ ?> ecu-selected<?php } ?>" data-nombre="<?=$nombreDataAttr; ?>">
                                <input type="radio" name="idgrupo" value="<?=$g["id_grupo"]; ?>" <?php if($marcado){ ?>checked="checked"<?php } ?> required />
                                <span class="ecu-group-meta">
                                    <p class="ecu-group-name"><?=htmlspecialchars($g["nombre_grupo"], ENT_QUOTES, "UTF-8"); ?></p>
                                </span>
                                <span class="ecu-check"></span>
                            </label>
                        <?php } ?>
                    </div>
                    <noscript>
                        <div class="ecu-btn-row">
                            <input type="submit" name="button" value="Ver información del grupo" class="ecu-btn ecu-btn-secondary" />
                        </div>
                    </noscript>
                <?php }else{ ?>
                    <div class="ecu-banner ecu-info">Aún no tiene grupos creados. Cree uno nuevo arriba para continuar.</div>
                <?php } ?>
            </form>
        </div>

        <div class="ecu-card ecu-panel-reporte">
            <div class="ecu-panel-header">
                <h4 class="ecu-section-title">Información del grupo</h4>
                <a href="<?php echo ($idGrupoSeleccionado > 0) ? 'reportar_facilitador.php?idgrupo='.$idGrupoSeleccionado : '#'; ?>"
                   id="ecuBtnReporte"
                   class="ecu-btn ecu-btn-primary ecu-btn-slim"
                   style="text-decoration:none; display:<?php echo ($idGrupoSeleccionado > 0) ? 'inline-block' : 'none'; ?>;">Generar reporte</a>
            </div>

            <div id="ecuPanelInfo">
            <?php if($idGrupoSeleccionado > 0){
                $fechaCreacionFmt = date("d/m/Y", strtotime($fechaCreacionGrupoSeleccionado));
            ?>
                <div class="ecu-info-nombre-wrap">
                    <p class="ecu-info-eyebrow">Nombre del grupo</p>
                    <input type="text" id="ecuInputNombreGrupo" class="ecu-input-mini ecu-input-nombre-grande" maxlength="150" readonly value="<?=htmlspecialchars($nombreGrupoSeleccionado, ENT_QUOTES, "UTF-8"); ?>" />
                </div>

                <div class="ecu-info-grid">
                    <div class="ecu-info-tile">
                        <p class="ecu-info-tile-label">Reportes realizados</p>
                        <p class="ecu-info-tile-value"><?=$totalReportesGrupo; ?></p>
                    </div>
                    <div class="ecu-info-tile">
                        <p class="ecu-info-tile-label">Fecha de creación</p>
                        <p class="ecu-info-tile-value" style="font-size:13px;"><?=$fechaCreacionFmt; ?></p>
                    </div>
                </div>

                <div class="ecu-meta-row">
                    <div class="ecu-meta-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/></svg>
                    </div>
                    <div>
                        <p class="ecu-meta-label">Generación</p>
                        <p class="ecu-meta-value"><?=$generacionGrupoSeleccionado; ?></p>
                    </div>
                </div>

                <div class="ecu-meta-row">
                    <div class="ecu-meta-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                    </div>
                    <div>
                        <p class="ecu-meta-label">Grupo de generación anterior</p>
                        <?php if($generacionAnteriorNombre !== null){ ?>
                            <p class="ecu-meta-value"><?=htmlspecialchars($generacionAnteriorNombre, ENT_QUOTES, "UTF-8"); ?></p>
                        <?php }else{ ?>
                            <p class="ecu-meta-value ecu-meta-value-mensaje"><?=htmlspecialchars($generacionAnteriorMensaje, ENT_QUOTES, "UTF-8"); ?></p>
                        <?php } ?>
                    </div>
                </div>

                <div class="ecu-meta-row">
                    <div class="ecu-meta-avatar"><?=htmlspecialchars($inicialesCreadorGrupo, ENT_QUOTES, "UTF-8"); ?></div>
                    <div>
                        <p class="ecu-meta-label">Creado por</p>
                        <p class="ecu-meta-value"><?=htmlspecialchars($nombreCreadorGrupo, ENT_QUOTES, "UTF-8"); ?></p>
                    </div>
                </div>

                <div class="ecu-panel-actions">
                    <button type="button" class="ecu-btn ecu-btn-azul-outline ecu-btn-slim" data-action="iniciar-edicion">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                        Editar
                    </button>
                    <button type="button" class="ecu-btn ecu-btn-primary ecu-btn-slim oculto" data-action="guardar-nombre">Guardar</button>
                    <button type="button" class="ecu-btn ecu-btn-azul-outline ecu-btn-slim oculto" data-action="cancelar-nombre">Cancelar</button>
                    <button type="button" class="ecu-btn ecu-btn-danger ecu-btn-slim" data-action="eliminar-grupo">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
                        Eliminar grupo
                    </button>
                </div>
            <?php }else{ ?>
                <div class="ecu-banner ecu-warning" style="margin: auto 0;">Seleccione o cree un grupo para ver su información.</div>
            <?php } ?>
            </div>
        </div>
    </div>

    <div class="ecu-modal-overlay oculto" id="ecuModalOverlay">
        <div class="ecu-modal-card" id="ecuModalCard">
            <h4 class="ecu-modal-titulo" id="ecuModalTitulo">Aviso</h4>
            <p class="ecu-modal-mensaje" id="ecuModalMensaje"></p>
            <div class="ecu-modal-botones" id="ecuModalBotones"></div>
        </div>
    </div>

</div>

<script>
    (function(){
        var lista = document.getElementById('ecuGroupList');
        var panelInfo = document.getElementById('ecuPanelInfo');
        var btnReporte = document.getElementById('ecuBtnReporte');
        var selectGrupoAnterior = document.getElementById('grupo_anterior');
        var idGrupoActual = <?php echo intval($idGrupoSeleccionado); ?>;
        var nombreOriginalGrupo = '';

        function escaparHtml(texto){
            var div = document.createElement('div');
            div.textContent = (texto === null || texto === undefined) ? '' : String(texto);
            return div.innerHTML;
        }

        /*
        *   SOLO VISUAL: iniciales para el avatar de "Creado por", espejo
        *   del mismo cálculo que ya se hace en PHP para la carga inicial.
        */
        function obtenerIniciales(nombre){
            if(!nombre){ return ''; }
            var partes = nombre.trim().split(/\s+/);
            var iniciales = partes[0].charAt(0).toUpperCase();
            if(partes.length > 1){
                iniciales += partes[partes.length - 1].charAt(0).toUpperCase();
            }
            return iniciales;
        }

        function construirHtmlInfo(data){
            var html = '';
            html += '<div class="ecu-info-nombre-wrap">' +
                        '<p class="ecu-info-eyebrow">Nombre del grupo</p>' +
                        '<input type="text" id="ecuInputNombreGrupo" class="ecu-input-mini ecu-input-nombre-grande" maxlength="150" readonly value="' + escaparHtml(data.nombre_grupo) + '" />' +
                    '</div>';
            html += '<div class="ecu-info-grid">' +
                        '<div class="ecu-info-tile">' +
                            '<p class="ecu-info-tile-label">Reportes realizados</p>' +
                            '<p class="ecu-info-tile-value">' + escaparHtml(data.total_reportes) + '</p>' +
                        '</div>' +
                        '<div class="ecu-info-tile">' +
                            '<p class="ecu-info-tile-label">Fecha de creación</p>' +
                            '<p class="ecu-info-tile-value" style="font-size:13px;">' + escaparHtml(data.fecha_creacion) + '</p>' +
                        '</div>' +
                    '</div>';
            html += '<div class="ecu-meta-row">' +
                        '<div class="ecu-meta-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/></svg></div>' +
                        '<div>' +
                            '<p class="ecu-meta-label">Generación</p>' +
                            '<p class="ecu-meta-value">' + escaparHtml(data.generacion) + '</p>' +
                        '</div>' +
                    '</div>';
            html += '<div class="ecu-meta-row">' +
                        '<div class="ecu-meta-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg></div>' +
                        '<div>' +
                            '<p class="ecu-meta-label">Grupo de generación anterior</p>';
            if(data.generacion_anterior_nombre){
                html += '<p class="ecu-meta-value">' + escaparHtml(data.generacion_anterior_nombre) + '</p>';
            }else{
                html += '<p class="ecu-meta-value ecu-meta-value-mensaje">' + escaparHtml(data.generacion_anterior_mensaje) + '</p>';
            }
            html += '</div></div>';
            html += '<div class="ecu-meta-row">' +
                        '<div class="ecu-meta-avatar">' + escaparHtml(obtenerIniciales(data.creado_por)) + '</div>' +
                        '<div>' +
                            '<p class="ecu-meta-label">Creado por</p>' +
                            '<p class="ecu-meta-value">' + escaparHtml(data.creado_por) + '</p>' +
                        '</div>' +
                    '</div>';
            html += '<div class="ecu-panel-actions">' +
                        '<button type="button" class="ecu-btn ecu-btn-azul-outline ecu-btn-slim" data-action="iniciar-edicion">' +
                            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>' +
                            'Editar' +
                        '</button>' +
                        '<button type="button" class="ecu-btn ecu-btn-primary ecu-btn-slim oculto" data-action="guardar-nombre">Guardar</button>' +
                        '<button type="button" class="ecu-btn ecu-btn-azul-outline ecu-btn-slim oculto" data-action="cancelar-nombre">Cancelar</button>' +
                        '<button type="button" class="ecu-btn ecu-btn-danger ecu-btn-slim" data-action="eliminar-grupo">' +
                            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>' +
                            'Eliminar grupo' +
                        '</button>' +
                    '</div>';
            return html;
        }

        /*
        *   Modal propio de la aplicación: reemplaza los alert()/confirm()
        *   nativos del navegador para que los avisos se vean como parte
        *   del sistema y no como un popup del navegador.
        */
        var modalOverlay = document.getElementById('ecuModalOverlay');
        var modalCard = document.getElementById('ecuModalCard');
        var modalTitulo = document.getElementById('ecuModalTitulo');
        var modalMensaje = document.getElementById('ecuModalMensaje');
        var modalBotones = document.getElementById('ecuModalBotones');

        function cerrarModal(){
            if(modalOverlay){ modalOverlay.classList.add('oculto'); }
        }

        function mostrarModal(titulo, mensaje, tipo, botones){
            if(!modalOverlay){ return; }
            modalTitulo.textContent = titulo;
            modalMensaje.textContent = mensaje;
            modalCard.className = 'ecu-modal-card' + (tipo ? ' ecu-modal-' + tipo : '');
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

        <?php if($exitoGrupo != ""){ ?>
        mostrarAviso(<?=json_encode($exitoGrupo, JSON_UNESCAPED_UNICODE); ?>, 'Grupo creado con éxito');
        <?php } ?>

        function mostrarError(mensaje, titulo){
            mostrarModal(titulo || 'No fue posible completar la acción', mensaje, 'error', [
                { texto: 'Entendido', clase: 'ecu-btn-secondary' }
            ]);
        }

        function mostrarConfirmacion(mensaje, onConfirmar, titulo){
            mostrarModal(titulo || 'Confirmar acción', mensaje, 'confirmar', [
                { texto: 'Cancelar', clase: 'ecu-btn-secondary' },
                { texto: 'Eliminar', clase: 'ecu-btn-danger', onClick: onConfirmar }
            ]);
        }

        if(modalOverlay){
            modalOverlay.addEventListener('click', function(e){
                if(e.target === modalOverlay){ cerrarModal(); }
            });
            document.addEventListener('keydown', function(e){
                if(e.key === 'Escape'){ cerrarModal(); }
            });
        }

        function actualizarBotonReporte(idGrupo){
            if(!btnReporte){ return; }
            if(idGrupo){
                btnReporte.href = 'reportar_facilitador.php?idgrupo=' + encodeURIComponent(idGrupo);
                btnReporte.style.display = 'inline-block';
            }else{
                btnReporte.style.display = 'none';
            }
        }

        function cargarInfoGrupo(idGrupo){
            if(!panelInfo){ return; }
            idGrupoActual = parseInt(idGrupo, 10) || 0;
            panelInfo.innerHTML = '<div class="ecu-banner ecu-info">Cargando información del grupo...</div>';
            actualizarBotonReporte(null);
            fetch('ajax_info_grupo.php?idgrupo=' + encodeURIComponent(idGrupo), { credentials: 'same-origin' })
                .then(function(resp){ return resp.json(); })
                .then(function(data){
                    if(!data.ok){
                        panelInfo.innerHTML = '<div class="ecu-banner ecu-error">' + escaparHtml(data.mensaje || 'No se pudo cargar la información del grupo.') + '</div>';
                        return;
                    }
                    panelInfo.innerHTML = construirHtmlInfo(data);
                    actualizarBotonReporte(data.id_grupo);
                })
                .catch(function(){
                    panelInfo.innerHTML = '<div class="ecu-banner ecu-error">Ocurrió un error al consultar el grupo.</div>';
                });
        }

        /*
        *   Editar el nombre del grupo (único campo editable) y eliminar el
        *   grupo. Se usa delegación de eventos sobre panelInfo porque su
        *   contenido se reemplaza tanto por PHP (carga inicial) como por
        *   fetch (cargarInfoGrupo), y así los botones funcionan en ambos
        *   casos sin tener que volver a engancharlos.
        */
        function actualizarNombreEnListaYSelect(idGrupo, nombreNuevo){
            if(lista){
                var radio = lista.querySelector('input[type="radio"][value="' + idGrupo + '"]');
                if(radio){
                    var opcion = radio.closest('.ecu-group-option');
                    if(opcion){
                        var parrafoNombre = opcion.querySelector('.ecu-group-name');
                        if(parrafoNombre){ parrafoNombre.textContent = nombreNuevo; }
                        opcion.setAttribute('data-nombre', nombreNuevo.toLowerCase());
                    }
                }
            }
            if(selectGrupoAnterior){
                var opcionSelect = selectGrupoAnterior.querySelector('option[value="' + idGrupo + '"]');
                if(opcionSelect){
                    var generacionTxt = opcionSelect.getAttribute('data-generacion') || '';
                    opcionSelect.textContent = nombreNuevo + (generacionTxt ? ' (Generación ' + generacionTxt + ')' : '');
                }
            }
        }

        function quitarGrupoDeListaYSelect(idGrupo){
            if(lista){
                var radio = lista.querySelector('input[type="radio"][value="' + idGrupo + '"]');
                if(radio){
                    var opcion = radio.closest('.ecu-group-option');
                    if(opcion){ opcion.remove(); }
                }
                ajustarAlturaLista();
            }
            if(selectGrupoAnterior){
                var opcionSelect = selectGrupoAnterior.querySelector('option[value="' + idGrupo + '"]');
                if(opcionSelect){ opcionSelect.remove(); }
            }
        }

        if(panelInfo){
            panelInfo.addEventListener('click', function(e){
                var boton = e.target.closest('[data-action]');
                if(!boton){ return; }
                var accion = boton.getAttribute('data-action');
                var input = panelInfo.querySelector('#ecuInputNombreGrupo');

                if(accion === 'iniciar-edicion'){
                    if(!input){ return; }
                    nombreOriginalGrupo = input.value;
                    input.readOnly = false;
                    input.focus();
                    input.select();
                    boton.classList.add('oculto');
                    var btnEliminarOcultar = panelInfo.querySelector('[data-action="eliminar-grupo"]');
                    if(btnEliminarOcultar){ btnEliminarOcultar.classList.add('oculto'); }
                    panelInfo.querySelector('[data-action="guardar-nombre"]').classList.remove('oculto');
                    panelInfo.querySelector('[data-action="cancelar-nombre"]').classList.remove('oculto');

                }else if(accion === 'cancelar-nombre'){
                    if(!input){ return; }
                    input.value = nombreOriginalGrupo;
                    input.readOnly = true;
                    panelInfo.querySelector('[data-action="iniciar-edicion"]').classList.remove('oculto');
                    var btnEliminarMostrar = panelInfo.querySelector('[data-action="eliminar-grupo"]');
                    if(btnEliminarMostrar){ btnEliminarMostrar.classList.remove('oculto'); }
                    boton.classList.add('oculto');
                    var btnGuardar = panelInfo.querySelector('[data-action="guardar-nombre"]');
                    if(btnGuardar){ btnGuardar.classList.add('oculto'); }

                }else if(accion === 'guardar-nombre'){
                    if(!input){ return; }
                    var nombreNuevo = input.value.trim();
                    if(nombreNuevo === ''){
                        mostrarAviso('El nombre del grupo es obligatorio.');
                        return;
                    }
                    boton.disabled = true;

                    var datos = new URLSearchParams();
                    datos.set('accion', 'editar_nombre');
                    datos.set('idgrupo', idGrupoActual);
                    datos.set('nombre_grupo', nombreNuevo);

                    fetch('ajax_grupo_accion.php', { method: 'POST', credentials: 'same-origin', body: datos })
                        .then(function(resp){ return resp.json(); })
                        .then(function(data){
                            boton.disabled = false;
                            if(!data.ok){
                                mostrarError(data.mensaje || 'No se pudo guardar el nombre del grupo.');
                                return;
                            }
                            input.value = data.nombre_grupo;
                            input.readOnly = true;
                            panelInfo.querySelector('[data-action="iniciar-edicion"]').classList.remove('oculto');
                            var btnEliminarRestaurar = panelInfo.querySelector('[data-action="eliminar-grupo"]');
                            if(btnEliminarRestaurar){ btnEliminarRestaurar.classList.remove('oculto'); }
                            boton.classList.add('oculto');
                            var btnCancelar = panelInfo.querySelector('[data-action="cancelar-nombre"]');
                            if(btnCancelar){ btnCancelar.classList.add('oculto'); }
                            actualizarNombreEnListaYSelect(data.id_grupo, data.nombre_grupo);
                        })
                        .catch(function(){
                            boton.disabled = false;
                            mostrarError('Ocurrió un error de conexión al guardar el nombre del grupo. Intenta de nuevo.');
                        });

                }else if(accion === 'eliminar-grupo'){
                    mostrarConfirmacion(
                        '¿Está seguro que desea eliminar este grupo? Esta acción no se puede deshacer.',
                        function(){
                            boton.disabled = true;

                            var datosEliminar = new URLSearchParams();
                            datosEliminar.set('accion', 'eliminar');
                            datosEliminar.set('idgrupo', idGrupoActual);

                            fetch('ajax_grupo_accion.php', { method: 'POST', credentials: 'same-origin', body: datosEliminar })
                                .then(function(resp){ return resp.json(); })
                                .then(function(data){
                                    if(!data.ok){
                                        boton.disabled = false;
                                        mostrarError(data.mensaje || 'No se pudo eliminar el grupo.', 'No es posible eliminar este grupo');
                                        return;
                                    }
                                    quitarGrupoDeListaYSelect(idGrupoActual);
                                    idGrupoActual = 0;
                                    panelInfo.innerHTML = '<div class="ecu-banner ecu-warning" style="margin: auto 0;">Seleccione o cree un grupo para ver su información.</div>';
                                    actualizarBotonReporte(null);
                                    mostrarAviso('El grupo se eliminó con éxito.', 'Grupo eliminado con éxito');
                                })
                                .catch(function(){
                                    boton.disabled = false;
                                    mostrarError('Ocurrió un error de conexión al eliminar el grupo. Intenta de nuevo.');
                                });
                        },
                        'Eliminar grupo'
                    );
                }
            });
        }

        /*
        *   La lista muestra siempre los primeros 5 grupos sin recortar;
        *   el resto queda disponible haciendo scroll dentro del recuadro.
        *   Se calcula en JS (y no con un alto fijo en CSS) para que se
        *   ajuste al alto real de las tarjetas, sea cual sea el largo del
        *   nombre del grupo.
        */
        function ajustarAlturaLista(){
            if(!lista){ return; }
            var opciones = lista.querySelectorAll('.ecu-group-option');
            if(opciones.length <= 5){
                lista.style.maxHeight = 'none';
                return;
            }
            var gap = 10; // debe coincidir con el "gap" definido en .ecu-group-list
            var alturaTotal = 0;
            for(var i = 0; i < 5; i++){
                alturaTotal += opciones[i].offsetHeight;
                if(i > 0){ alturaTotal += gap; }
            }
            lista.style.maxHeight = alturaTotal + 'px';
        }

        ajustarAlturaLista();
        window.addEventListener('load', ajustarAlturaLista);

        if(lista){
            lista.addEventListener('click', function(e){
                var opcion = e.target.closest('.ecu-group-option');
                if(!opcion){ return; }

                /*
                *   Se evita el comportamiento nativo del <label> (enfocar el
                *   radio interno), ya que al estar posicionado de forma
                *   absoluta esto hacía que el navegador desplazara toda la
                *   página para "mostrarlo". El estado se marca a mano.
                */
                e.preventDefault();

                var input = opcion.querySelector('input[type="radio"]');
                if(input){ input.checked = true; }

                var todas = lista.querySelectorAll('.ecu-group-option');
                for(var i = 0; i < todas.length; i++){ todas[i].classList.remove('ecu-selected'); }
                opcion.classList.add('ecu-selected');

                if(input && input.value){
                    cargarInfoGrupo(input.value);
                }
            });

            /*
            *   Al llegar al tope de la lista, un primer scroll hacia arriba
            *   se "absorbe" (la lista se queda quieta); solo al segundo
            *   intento consecutivo se deja pasar el scroll a la página
            *   completa. Mejora la usabilidad: evita que un solo gesto de
            *   scroll dispare de golpe el scroll de toda la página.
            */
            var intentosArriba = 0;
            var restaurarContencionId = null;
            var UMBRAL_INTENTOS = 2;

            lista.addEventListener('wheel', function(e){
                var enElTope = lista.scrollTop <= 0;

                if(e.deltaY < 0 && enElTope){
                    intentosArriba++;
                    if(intentosArriba < UMBRAL_INTENTOS){
                        e.preventDefault();
                    }else{
                        intentosArriba = 0;
                        lista.style.overscrollBehavior = 'auto';
                        if(restaurarContencionId){ clearTimeout(restaurarContencionId); }
                        restaurarContencionId = setTimeout(function(){
                            lista.style.overscrollBehavior = 'contain';
                        }, 60);
                    }
                }else if(e.deltaY > 0 || !enElTope){
                    intentosArriba = 0;
                }
            }, { passive: false });
        }

        /*
        *   Búsqueda "difusa": no exige coincidencia exacta ni contigua.
        *   Basta con que las letras escritas aparezcan en ese orden dentro
        *   del nombre del grupo (ignorando acentos y mayúsculas), así las
        *   opciones van apareciendo a medida que se escribe algo similar.
        */
        function normalizarTexto(texto){
            return (texto || '')
                .normalize('NFD')
                .replace(/[̀-ͯ]/g, '')
                .toLowerCase();
        }

        function coincideDifuso(termino, texto){
            if(termino === ''){ return true; }
            var t = 0;
            for(var i = 0; i < texto.length && t < termino.length; i++){
                if(texto[i] === termino[t]){ t++; }
            }
            return t === termino.length;
        }

        var buscador = document.getElementById('buscarGrupo');
        if(buscador && lista){
            buscador.addEventListener('input', function(){
                var termino = normalizarTexto(buscador.value);
                var opciones = lista.querySelectorAll('.ecu-group-option');
                for(var i = 0; i < opciones.length; i++){
                    var nombre = normalizarTexto(opciones[i].getAttribute('data-nombre') || '');
                    opciones[i].style.display = coincideDifuso(termino, nombre) ? '' : 'none';
                }
            });
        }
    })();
</script>