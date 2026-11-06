<?php
if($_SESSION["superusuario"] != 1)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

$PSN1 = new DBbase_Sql;
if(isset($_POST["mensajes"]))
{
	$PSN = new DBbase_Sql;
	$mensajes = soloNumeros($_POST["mensajes"]);
	echo $mensajes;
	//exit;
	$audit_usuario = $_SESSION["id"];
	$audit_ip = $_SERVER['REMOTE_ADDR'];
	//
	if($mensajes != "")
	{
		$sql = "INSERT INTO sms_historico (tipo, mensajes, fecha, audit_usuario, audit_fecha, audit_ip) ";
		$sql .= " VALUES(1, '".$mensajes."', '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";
		$sql .= " ON DUPLICATE KEY UPDATE mensajes = (mensajes+".$mensajes.")";
		$PSN->query($sql);
	}

	?><div class="container">
        <div class="form-group">
            <h2 class="alert alert-info text-center">.AGREGAR SALDO SMS EN EL SISTEMA.</h2>
        </div>

        <div class="form-group">
            <h5 class="alert alert-warning text-center">Se ha creado correctamente el registro, en breve será redirigido, si no es redirigido de <a href="index.php?doc=sms_envio&opc=2">clic aquí</a>.</h5>
        </div>
    </div>
	<SCRIPT LANGUAGE="JavaScript">
	alert("Se ha aumentado correctamente el saldo!!!");
	window.location.href= "index.php?doc=sms_envio&opc=2";
	</script>
	<?
}
else
{
	$PSN = new DBbase_Sql;
	?><div class="container">
    <div class="form-group">
        <h2 class="text-center well">.AGREGAR SALDO SMS.</h2>
    </div>
    <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">

	<div class="form-group">
        <label class="form-label" for="mensajes">Mensajes</label>
		
			<input name="mensajes" type="text" id="mensajes" maxlength="10" value="<?=soloNumeros($_POST["mensajes"]); ?>" class="form-control" />
		</div>
	</div>

    <div class="row text-center">
        <input type="hidden" name="funcion" id="funcion" value="" />
        <input type="submit" value="Guardar cambios" class="btn btn-success" /> 
        <a href="index.php?doc=main" class="btn btn-danger">Cerrar</a>
    </div>
    </form>
    </div>
	
	<script language="javascript">
		function generarForm(){
            if(confirm("Esta accion agregara SALDO en el sistema, ¿esta seguro que desea continuar?"))
            {
                if(document.getElementById('mensajes').value != "" 
                )
                {
                    document.getElementById('funcion').value = "insertar";
                }
                else
                {
                    return false;
                    alert("La informacion es primordial para brindarle un excelente servicio, por favor digite al menos el campo de MENSAJES");
                }
            }
            else{
                return false;
            }
            return true;
		}
		function init(){
			document.getElementById('form1').onsubmit = function(){
					return generarForm();
			}
		}
				
		window.onload = function(){
			init();
		}
		</script>
					<?
}

?>