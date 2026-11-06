<?php
/*if($_SESSION["superusuario"] != 1)
{
	die("<h1>No esta autorizado a visualizar esta opcion.</h1>");
}*/
if(isset($_GET["idcat"]))
{
	$varMiIdCat = $_GET["idcat"];
}
else
{
	die("Debe especificarse un ID de categoria.");
}

$varMiId = 0;
if(isset($_GET["id"]) && $_GET["id"] > 0)
{
	$varMiId = $_GET["id"];
}
else if(isset($_POST["id"]) && $_POST["id"] > 0)
{
	$varMiId = $_POST["id"];
}

// Objeto de Base de Datos
$PSN1 = new DBbase_Sql;

if(isset($_POST["funcion"]))
{
	if($_POST["funcion"] == "insertarCategoria" && trim(htmlspecialchars($_POST["categoria"])) != "")
	{
		if($varMiId == 0)
		{
			$sql = 'insert into categorias (idSec, descripcion, detalle) ';
			$sql .= 'values ('.$varMiIdCat.', "'.htmlspecialchars($_POST["categoria"]).'", "'.htmlspecialchars($_POST["categoria"]).'")';
		}
		else
		{
			$sql = 'update categorias set descripcion = "'.htmlspecialchars($_POST["categoria"]).'", detalle = "'.htmlspecialchars($_POST["categoria"]).'" where id = '.$varMiId;
		}

		$ultimoQuery = $PSN1->query($sql);		
		$varExito = 1;
	}
	else if($_POST["funcion"] == "borrarDato" && $varMiId != 0)
	{
		$sql = "DELETE FROM categorias where id = ".$varMiId;
		$ultimoQuery = $PSN1->query($sql);		
		$varExito = 1;
	}
}

// Array que nos servira para ir llevando cuenta de los requerimientos.
/*
*	TRAEMOS LAS CATEGORIAS
*/
$sql = "SELECT * ";
$sql.=" FROM categorias  ";
$sql.=" WHERE idSec = ".$varMiIdCat." ORDER BY descripcion ASC";

$PSN1->query($sql);
$numero=$PSN1->num_rows();
?><div class="container">

	<div class="row">
		<h2 class="text-center well">.CATEGORÍAS DE "<?
	switch($varMiIdCat)
	{
		case 1:
			echo "PERFILES";
			break;
		case 2:
			echo "TIPOS DE IDENTIFICACION";
			break;
		case 3:
			echo "TIPOS DE SERVICIO";
			break;
		case 4:
			echo "TIPOS DE REQUERIMIENTO";
			break;
		case 5:
			echo "ESTADOS DE LA ORDEN";
			break;
		case 6:
			echo "TIPO DE CONTRIBUYENTE";
			break;
		case 7:
			echo "REGIMEN DIAN";
			break;
		case 8:
			echo "CLASIFICACION DIAN";
			break;
		case 9:
			echo "TIPOS DE INDUSTRIA";
			break;
		case 10:
			echo "TIPOS DE SECTOR";
			break;
		case 11:
			echo "SEXO";
			break;
        case 15:
            echo "TIPO DE PROSPECTO/CLIENTE";
            break;
        case 16:
            echo "ESTADO COTIZACION";
            break;
        case 17:
            echo "ESTADO DEL PAGO";
            break;
        case 18:
            echo "CATEGORÍA PRINCIPAL";
            break;
        case 19:
            echo "METODO COTIZACION";
            break;
        case 20:
            echo "TIPO CONTACTO";
            break;
        case 21:
            echo "TIPO DE SEGUIMIENTO";
            break;
        case 22:
            echo "TIPO DE ABONO";
            break;
        case 23:
            echo "TIPO DE CAMPA&Ntilde;A";
            break;
        case 24:
            echo "TIPO DE GASTO";
            break;
        case 25:
            echo "TIPO DE SERVICIO";
            break;
        case 26:
            echo "TIPO DE CONTRATO";
            break;
        case 27:
            echo "TIPO DE SERVICIO CLIENTES";
            break;
        case 28:
            echo "TIPO DE CONTRATO CLIENTES";
            break;            
        case 29:
            echo "TIPO DE PERSONA CLIENTE";
            break;
        case 30:
            echo "TIPO DE OBSERVACIONES DE USUARIOS";
            break;
        case 31:
            echo "MARCAS DE VEHÍCULOS";
            break;
        case 32:
            echo "TIPO DE USUARIO CLIENTES";
            break;
        case 33:
            echo "VEHÍCULO - CLASE";
            break;
        case 34:
            echo "VEHÍCULO - TIPO DE OBSERVACIÓN";
            break;
        case 35:
            echo "VEHÍCULO - CIUDAD MANTENIMIENTO";
            break;
		case 36:
			echo "AUDITORIA - TIPO PERFIL";
			break;	

		default:
			echo "NADEDAD";
			break;
	}
	?>".</h2>
	</div>

<div class="row">
<div class="col-md-6 col-md-offset-3">
<table border="0" cellspacing="0" cellpadding="2"  align="center" class="table table-bordered" style="font-size:12px">
    <thead>
	<tr> 
		<th>Id</th>
		<th>Nombre</th>
	</tr>
    </thead>
    <tbody>
<?
if($numero > 0)
{
	$contador = 0;
	while($PSN1->next_record())
	{
		//Solo si no se ha modificado ya el formulario.
		$id = $PSN1->f('id');
		$descripcion = $PSN1->f('descripcion');
		?><tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>>
		<td><a href="index.php?doc=admin_buscarcat&idcat=<?=$varMiIdCat; ?>&id=<?=$id; ?>&opc=1#insertar"><?=str_pad($id, 6, "0", STR_PAD_LEFT); ?></a></td>
		<td><a href="index.php?doc=admin_buscarcat&idcat=<?=$varMiIdCat; ?>&id=<?=$id; ?>&opc=1#insertar"><?=$descripcion; ?></a></td>
		</tr>
		<?
		$contador++;
	}
}

/*
*	TRAEMOS LAS CATEGORIAS
*/
$sql = "SELECT * ";
$sql.=" FROM categorias  ";
$sql.=" WHERE id = ".$varMiId." ORDER BY descripcion ASC";

$PSN1->query($sql);
$numero=$PSN1->num_rows();

if($numero > 0)
{
	$contador = 0;
	$PSN1->next_record();
}
?></tbody>
</table>
</div>
</div>

<div class="row text-center">
	<input type="button" name="button" onclick="nuevoRegistro()" value="Insertar Registro" class="btn btn-success" /> 
	<input type="button" name="button" onclick="regresar()" value="Regresar" class="btn btn-danger" />

</div>
<?
if(isset($_REQUEST["opc"]))
{
	?><div class="container">
	<form method="post" enctype="multipart/form-data" name="form1" id="form1" action="index.php?doc=admin_buscarcat&idcat=<?=$varMiIdCat; ?>&id=<?=$varMiId; ?>" class="form">

    
    <a name="insertar"></a>
    <div class="form-group">
        <h2 class="text-center well">.<?
		if($varMiId > 0)
		{
			?>ACTUALIZAR<?
		}
	else
	{
		?>INSERTAR<?
	}
	?> REGISTRO.</h2>
    </div>
        
	<div class="form-group">
        <label class="form-label" for="categoria">Nombre</label>
		
			<input type="text" name="categoria" id="categoria" value="<?=$PSN1->f('descripcion'); ?>" class="form-control" />
		</div>
        
            <input type="button" name="button" onclick="generarForm()" value="<?
                if($varMiId > 0)
                {
                    echo "Guardar";
                }
                else
                {
                    echo "Insertar";
                }?>" class="btn btn-success" /> <input type="button" name="button" onclick="generarFormDel()" class="btn btn-danger" value="Eliminar Categoria" />
    </div>

        
	<input type="hidden" name="funcion" id="funcion" value="" />
	</form>
	</div>


	<script language="javascript">
		function generarForm(){
			if(confirm("Esta accion insertara un dato a la categoria actual, Esta seguro que desea continuar?"))
			{
				if(document.getElementById('categoria').value != "")
				{
					document.getElementById('funcion').value = "insertarCategoria";
					document.getElementById('form1').submit();
				}
				else
				{
					alert("Debe escribir algo.");
				}
			}
		}

		function generarFormDel(){
			if(confirm("Esta accion va a ELIMINAR el dato a la categoria actual, Esta seguro que desea continuar?"))
			{
				if(document.getElementById('categoria').value != "")
				{
					document.getElementById('funcion').value = "borrarDato";
					document.getElementById('form1').submit();
				}
				else
				{
					alert("Debe escribir algo.");
				}
			}
		}

		function init(){
			document.getElementById('form1').onsubmit = function(){
					return false;
			}
			<?
			if($varExito == 1)
			{
				?>alert("Operacion exitosa!");<?
			}
			?>
		}

		function nuevoRegistro()
		{
			window.location.href = "index.php?doc=admin_buscarcat&idcat=<?=$varMiIdCat; ?>&opc=1#insertar";
		}

		function regresar()
		{
			window.location.href = "index.php?doc=admin_buscarcatm";
		}

		window.onload = function(){
			init();
		}
		</script><?
}
else{
	?>
	<script language="javascript">
	function init(){
		<?
		if($varExito == 1)
		{
			?>alert("Operacion exitosa!");<?
		}
		?>
	}

	function nuevoRegistro()
	{
		window.location.href = "index.php?doc=admin_buscarcat&idcat=<?=$varMiIdCat; ?>&opc=1#insertar";
	}

	function regresar()
	{
		window.location.href = "index.php?doc=admin_buscarcatm";
	}

	window.onload = function(){
		init();
	}
	</script><?
}
?>