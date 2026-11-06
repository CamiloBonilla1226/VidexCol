<?php
//Id de estudiante a partir del 20 de Marzo de 2017 - 25797
if(isset($_GET["id"]))
{
	$varMiAbn = soloNumeros($_GET["id"]);
}
else
{
	die("Debe especificarse un ID del abono.");
}

	if($_SESSION["acceso_solo_lectura"] == 1){
		?><div class="row">
			<h5 class="alert alert-danger text-center"><strong>Atención:</strong> Su usuario es de solo lectura, no puede realizar modificaciones.</h5>
		</div><?
		exit;
	}
	//

// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;

$sql= "SELECT idMenu ";
$sql.=" FROM usuarios_menu ";
$sql.=" WHERE idMenu = 48 
AND idUsuario = '".$_SESSION["id"]."'";
$PSN1->query($sql);
if($PSN1->num_rows() == 0)
{
    die("NO esta autorizado a ver esta opción.");
}

//
// COMPROBAMOS QUE EL USUARIO PERTENEZCA A ESTE REPORTE O SEA LIDER/ADMINISTRADOR
//
$sql = "SELECT gastos.* ";
$sql.=" FROM gastos ";
$sql.=" WHERE gastos.id = '".$varMiAbn."'";
//
$PSN1->query($sql);
$numero=$PSN1->num_rows();
if($numero == 0)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}
else
{
	if($PSN1->next_record())
	{
		$varEstado = $PSN1->f('estado');
		$idClienteOk = $PSN1->f('idCliente');
		$contactoNombre = $PSN1->f('contactoNombre');

		$fechaCreacion = $PSN1->f('fechaCreacion');
		$abonoTipo = $PSN1->f('tipo');
		$abonoValor = $PSN1->f('valor');
		$abonoObservacion = $PSN1->f('observacion');
        $abonoExt = $PSN1->f('ext');

	}
}
//
//	FIN COMPROBACION
//

// Array que nos servira para ir llevando cuenta de los requerimientos.
$arrayRequerimientos = array();
$varExito = 0;
if(isset($_POST["funcion"]))
{
	if($_SESSION["acceso_solo_lectura"] == 1){
		?><div class="row">
			<h5 class="alert alert-danger text-center"><strong>Atención:</strong> Su usuario es de solo lectura, no puede realizar modificaciones.</h5>
		</div><?
		exit;
	}
	//
	
	if($_POST["funcion"] == "updateAbono" && eliminarInvalidos($_POST["valor"]) != "")
	{
        $nombreArchivo = $_FILES['archivo']['name'] ?? '';
        $extNueva = extension_archivo($nombreArchivo);
        $sqlExt = '';
        if($extNueva != '')
        {
            $sqlExt = ', ext = "'.$extNueva.'"';
        }

		$sql = 'UPDATE gastos SET 
					tipo = "'.soloNumeros($_POST["tipo"]).'", 
					valor = "'.soloNumeros($_POST["valor"]).'",
					fechaCreacion = "'.eliminarInvalidos($_POST["fechaCreacion"]).'", 
                    observacion = "'.eliminarInvalidos($_POST["observacion"]).'" 
                    '.$sqlExt.'
				WHERE 
					id = "'.$varMiAbn.'"
				';		
		$ultimoQuery = $PSN1->query($sql);		
        if(!empty($_FILES['archivo']['tmp_name']) && $extNueva != '')
        {
            move_uploaded_file($_FILES['archivo']['tmp_name'], "archivos/abn".$varMiAbn.".".$extNueva);
            $abonoExt = $extNueva;
        }
		$varExito = 1;

		//DEBEMOS ACTUALIZAR EL REPORTE PARA SABER QUIEN FUE LA ULTIMA PERSONA EN ESCRIBIR
		//YA SEA UNA MODIFICACIÓN A LA INFORMACIÓN, UNA OBSERVACIÓN, ETC.
		$sql = "UPDATE cotizacion SET ";
			$sql .= "modUsuario = '".$_SESSION["id"]."'";
			$sql .= ", modFecha = NOW()";
		$sql .= " WHERE id = '".$varMiId."'";
		$PSN1->query($sql);
		//FIN ACTUALIZACIÓN DE REGISTROS
	
		//Compruebo si las características del archivo son las que deseo 
		/*if(move_uploaded_file($_FILES['archivo']['tmp_name'], "archivos/abn".$ultimoId.".".$ext))
		{
			echo "Se movio...";
		}
		else
		{
			echo "NO se movio...";
		}*/
	}
}

if($varExito != 1){	
    ?><style>
    #gasto-modern {
        --bg: #f4f7fb;
        --surface: #ffffff;
        --line: #d9e2ec;
        --line-strong: #c8d5e3;
        --text: #1b2a3a;
        --primary: #1f5d92;
        --primary-soft: #e8f2fb;
        color-scheme: light;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        color: var(--text);
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 12px;
        background: var(--bg);
        border: 1px solid var(--line);
        border-radius: 12px;
    }

    #gasto-modern * { box-sizing: border-box; }

    #gasto-modern .gasto-card {
        width: 100%;
        max-width: 780px;
        margin: 0 auto;
        padding: 16px;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(16, 42, 67, 0.05);
    }

    #gasto-modern .well {
        background: var(--primary-soft);
        color: #17486f;
        border: 1px solid #cfe0ef;
        border-radius: 8px;
        padding: 10px 12px;
        margin-top: 0;
    }

    #gasto-modern .gasto-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(220px, 1fr));
        gap: 12px;
    }

    #gasto-modern .form-group { margin-bottom: 0; }
    #gasto-modern .form-group.full { grid-column: 1 / -1; }

    #gasto-modern .form-label,
    #gasto-modern label {
        display: inline-block;
        margin-bottom: 6px;
        font-weight: 600;
        color: var(--text);
    }

    #gasto-modern .form-control,
    #gasto-modern select,
    #gasto-modern textarea,
    #gasto-modern input[type="number"],
    #gasto-modern input[type="date"] {
        display: block;
        width: 100%;
        padding: 9px 11px;
        border: 1px solid var(--line-strong);
        border-radius: 8px;
        background: #fff;
        color: #1b2a3a;
        font-size: 14px;
    }

    #gasto-modern .form-control:focus,
    #gasto-modern select:focus,
    #gasto-modern textarea:focus,
    #gasto-modern input[type="number"]:focus,
    #gasto-modern input[type="date"]:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(31, 93, 146, 0.15);
    }

    #gasto-modern .gasto-actions {
        margin-top: 14px;
        text-align: center;
    }

    #gasto-modern .btn {
        padding: 9px 14px;
        border-radius: 8px;
        font-weight: 600;
    }

    #gasto-modern .btn-success {
        background: #1f8a55;
        border-color: #1f8a55;
        color: #fff;
    }

    #gasto-modern .btn-danger {
        background: #c94545;
        border-color: #c94545;
        color: #fff;
    }

    @media (max-width: 700px) {
        #gasto-modern .gasto-card { padding: 12px; }
        #gasto-modern .gasto-grid { grid-template-columns: 1fr; }
    }
    </style>

    <div id="gasto-modern">
        <div class="gasto-card">
            <form method="post" enctype="multipart/form-data" name="form1" id="form1" action="index.php?doc=gasto_editar&id=<?=$varMiAbn ; ?>" class="form">
                <div class="form-group full">
                    <h3 class="text-center well">.EDITAR GASTO.</h3>
                </div>

                <div class="gasto-grid">
                    <div class="form-group">
                        <label class="form-label" for="tipo">Tipo</label>
                        <select name="tipo" id="tipo" class="form-control">
                        <?
                        /*
                        *	TRAEMOS LOS TIPOS DE GASTOS
                        */
                        $sql = "SELECT * ";
                        $sql.=" FROM categorias ";
                        $sql.=" WHERE idSec = 24 ORDER BY descripcion asc";

                        $PSN1->query($sql);
                        $numero=$PSN1->num_rows();
                        if($numero > 0)
                        {
                            while($PSN1->next_record())
                            {
                                ?><option value="<?=$PSN1->f('id'); ?>" <?
                                if($abonoTipo == $PSN1->f('id'))
                                {
                                    ?>selected="selected"<?
                                }
                                ?>><?=$PSN1->f('descripcion'); ?></option><?
                            }
                        }
                        ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fechaCreacion">Fecha del Gasto</label>
                        <input name="fechaCreacion" id="fechaCreacion" type="date" placeholder="AAAA-MM-DD" value="<?=$fechaCreacion; ?>" class="form-control" />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="valor">Gasto</label>
                        <input type="number" name="valor" id="valor" value="<?=$abonoValor; ?>" class="form-control" />
                    </div>

                    <div class="form-group full">
                        <label class="form-label" for="observacion">Detalle</label>
                        <textarea name="observacion" id="observacion" cols="80" rows="5" class="form-control"><?=$abonoObservacion; ?></textarea>
                    </div>

                    <div class="form-group full">
                        <label class="form-label" for="archivo">Archivo anexo</label>
                        <input name="archivo" type="file" id="archivo" class="form-control" />
                        <?
                        $archivoActual = '';
                        if(isset($abonoExt) && $abonoExt != '')
                        {
                            $rutaArchivo = "archivos/abn".$varMiAbn.".".$abonoExt;
                            if(file_exists($rutaArchivo))
                            {
                                $archivoActual = $rutaArchivo;
                            }
                        }
                        if($archivoActual != '')
                        {
                            ?><p style="margin-top:8px; font-size:13px;">Archivo actual: <a href="<?=$archivoActual; ?>" target="_blank" rel="noopener">Ver adjunto</a></p><?
                        }
                        ?>
                    </div>
                </div>

                <div class="gasto-actions">
                    <input type="hidden" name="funcion" id="funcion" value="" />
                    <input type="submit" value="Guardar cambios" class="btn btn-success" />
                    <a href="index.php?doc=gastos" class="btn btn-danger">Cerrar</a>
                </div>
            </form>
        </div>
    </div>

    <script language="javascript">
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('gasto-modern');
        if (!root) return;

        var main = document.querySelector('.main-content, #main-content, #maincontent, #mainContent');
        if (main && !main.contains(root)) {
            main.appendChild(root);
        }

        var orphanSelectors = ['body > .container', 'body > .form-group', '.videx-wrap > .container', '.videx-wrap > .form-group', '.videx-wrap > form'];
        orphanSelectors.forEach(function (selector) {
            document.querySelectorAll(selector).forEach(function (node) {
                if (node === root) return;
                if (root.contains(node)) return;
                root.appendChild(node);
            });
        });
    });

    function generarForm(){
        if(confirm("Esta accion actualizara el gasto de la cotizacion seleccionada, esta seguro que desea continuar?"))
        {
            if(document.getElementById('valor').value != "")
            {
                document.getElementById('funcion').value = "updateAbono";
                return true;
            }
            else
            {
                alert("Debe escribir algo.");
            }
        }
        return false;
    }

    function init(){
        document.getElementById('form1').onsubmit = function(){
                return generarForm();
        }
        <?
        if($varExito == 1)
        {
            ?>window.location.href = 'index.php?doc=gasto_editar&id=<?=$varMiAbn; ?>';<?
        }
        ?>
    }

    window.onload = function(){
        init();
    }
    </script><?
}
else
{
	?><script language="javascript">
	function init(){
		<?
		if($varExito == 1)
		{
			?>window.location.href = 'index.php?doc=gasto_editar&id=<?=$varMiAbn; ?>';<?
		}
		?>
	}
	
	window.onload = function(){
		init();
	}
</script><?
}
?>