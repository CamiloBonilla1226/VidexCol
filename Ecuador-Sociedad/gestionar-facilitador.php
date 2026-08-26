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
<div class="container">
    <div class="cont-tit">
        <div class="hr"><hr></div>
        <div class="tit-cen">
            <h3 class="text-center">REPORTE DE <?=$temp_letrero; ?></h3>
            <h5>Seleccione un grupo existente o cree uno nuevo para continuar</h5>
        </div>
        <div class="hr"><hr></div>
    </div>

    <?php if($errorGrupo != ""){ ?>
        <div class="row">
            <h5 class="alert alert-danger text-center"><?=htmlspecialchars($errorGrupo, ENT_QUOTES, "UTF-8"); ?></h5>
        </div>
    <?php } ?>
    <?php if($exitoGrupo != ""){ ?>
        <div class="row">
            <h5 class="alert alert-success text-center"><?=htmlspecialchars($exitoGrupo, ENT_QUOTES, "UTF-8"); ?></h5>
        </div>
    <?php } ?>

    <div class="cont-tit">
        <div class="hr"><hr></div>
        <div class="tit-cen">
            <h3 class="text-center">MIS GRUPOS</h3>
        </div>
        <div class="hr"><hr></div>
    </div>

    <form method="post" id="formGrupo" name="formGrupo" class="form-horizontal">
        <input type="hidden" name="funcion" value="seleccionar_grupo" />
        <div class="form-group">
            <div class="col-sm-2"></div>
            <div class="col-sm-8">
                <?php if(count($gruposDisponibles) > 0){ ?>
                    <strong>Grupo:</strong>
                    <select name="idgrupo" id="idgrupo" class="form-control" required>
                        <option value="">Seleccione un grupo...</option>
                        <?php foreach($gruposDisponibles as $g){ ?>
                            <option value="<?=$g["id_grupo"]; ?>" <?php if($idGrupoSeleccionado == $g["id_grupo"]){ ?>selected="selected"<?php } ?>>
                                <?=htmlspecialchars($g["nombre_grupo"], ENT_QUOTES, "UTF-8"); ?> (Generación <?=$g["generacion"]; ?>)
                            </option>
                        <?php } ?>
                    </select>
                <?php }else{ ?>
                    <div class="alert alert-info text-center">Aún no tiene grupos creados. Cree uno nuevo más abajo para continuar.</div>
                <?php } ?>
            </div>
            <div class="col-sm-2"></div>
        </div>
        <?php if(count($gruposDisponibles) > 0){ ?>
            <div class="cont-btn cont-flex fl-cent">
                <div class="item-btn">
                    <input type="submit" name="button" value="Continuar con este grupo" class="btn btn-success" />
                </div>
            </div>
        <?php } ?>
    </form>

    <div class="cont-tit">
        <div class="hr"><hr></div>
        <div class="tit-cen">
            <h3 class="text-center">CREAR GRUPO NUEVO</h3>
        </div>
        <div class="hr"><hr></div>
    </div>

    <form method="post" id="formCrearGrupo" name="formCrearGrupo" class="form-horizontal">
        <input type="hidden" name="funcion" value="crear_grupo" />
        <div class="form-group">
            <div class="col-sm-1"></div>
            <div class="col-sm-5">
                <strong>Nombre del grupo: <span class="text-danger">*</span></strong>
                <input type="text" name="nombre_grupo" id="nombre_grupo" maxlength="150" class="form-control" required />
            </div>
            <div class="col-sm-5">
                <strong>Crear a partir de (opcional):</strong>
                <select name="grupo_anterior" id="grupo_anterior" class="form-control">
                    <option value="">Ninguno (nuevo grupo de generación 2)</option>
                    <?php foreach($gruposDisponibles as $g){ ?>
                        <option value="<?=$g["id_grupo"]; ?>">
                            <?=htmlspecialchars($g["nombre_grupo"], ENT_QUOTES, "UTF-8"); ?> (Generación <?=$g["generacion"]; ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-sm-1"></div>
        </div>
        <div class="cont-btn cont-flex fl-cent">
            <div class="item-btn">
                <input type="submit" name="button" value="Crear grupo" class="btn btn-primary" />
            </div>
        </div>
    </form>

    <div class="cont-tit">
        <div class="hr"><hr></div>
        <div class="tit-cen">
            <h3 class="text-center">FORMULARIO DE REPORTE</h3>
        </div>
        <div class="hr"><hr></div>
    </div>

    <?php if($idGrupoSeleccionado > 0){ ?>
        <div class="row">
            <h5 class="alert alert-success text-center">
                Grupo seleccionado: <strong><?=htmlspecialchars($nombreGrupoSeleccionado, ENT_QUOTES, "UTF-8"); ?></strong>
                (Generación <?=$generacionGrupoSeleccionado; ?>)
            </h5>
        </div>
        <!--
            A partir de aquí va el formulario de reporte de Facilitadores
            (pendiente de implementación), usando $idGrupoSeleccionado como
            idgrupo del reporte en ecu_reportes.
        -->
    <?php }else{ ?>
        <div class="row">
            <h5 class="alert alert-warning text-center">Debe seleccionar o crear un grupo antes de continuar al formulario de reporte.</h5>
        </div>
    <?php } ?>
</div>
