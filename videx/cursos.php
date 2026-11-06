<?php
/*
*	LOGUEO
*/
if($_SESSION["perfil"] != 1 && $_SESSION["perfil"] != 2)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

if(!isset($_GET["opc"]))
{
	$opc = 2;
}
else
{
	$opc = eliminarInvalidos($_GET["opc"]);
}

//
//	PARAMETROS CONFIGURABLES
//
$tablaConsulta = "cursos";
$webArchivo = "cursos";
$nombreConsulta = "CURSOS";

$PSN1 = new DBbase_Sql;
if($opc == 1)
{
		if(isset($_POST["nombre"]))
		{
			// - // - // - // - // - // - // - // - // - // - //
			$PSN = new DBbase_Sql;
			$sql= "SELECT ".$tablaConsulta.".id";
			$sql.=" FROM ".$tablaConsulta;
			$sql.=" WHERE ".$tablaConsulta.".nombre = '".eliminarInvalidos($_REQUEST["nombre"])."'";
			// - // - // - // - // - // - // - // - // - // - //
			$PSN->query($sql);
			$num=$PSN->num_rows();
			if($num > 0)
			{
				if($PSN->next_record())
				{
					?>
					<SCRIPT LANGUAGE="JavaScript">
					alert("YA EXISTE ESE NOMBRE EN EL SISTEMA, SERÁ AHORA DIRIGIDO AL MISMO");
					window.location.href= "index.php?doc=".$webArchivo."&opc=2&id=<?=$PSN->f('id'); ?>";
					</script>
					<?
				}
			}
			else
			{
				$nombre = eliminarInvalidos($_POST["nombre"]);
				$tipo = soloNumeros($_POST["tipo"]);
				$observaciones = eliminarInvalidos($_POST["observaciones"]);			
				$fechaInicial = eliminarInvalidos($_POST["fechaInicial"]);
				$fechaFinal = eliminarInvalidos($_POST["fechaFinal"]);
				$costo = soloNumeros($_POST["costo"]);
				//
				$creacionUsuario = $_SESSION["id"];
				//
				$sql = 'insert into '.$tablaConsulta.' (
					tipo,
					nombre,
					observaciones,
					fechaInicial,  
					fechaFinal,  
					costo,
					creacionUsuario,
					creacionFecha
				) ';

				$sql .= 'values (
					"'.$tipo.'", 
					"'.$nombre.'", 
					"'.$observaciones.'", 
					"'.$fechaInicial.'", 
					"'.$fechaFinal.'", 
					"'.$creacionUsuario.'", 
					NOW()
				)';

				$ultimoQuery = $PSN->query($sql);
				$ultimoId = mysql_insert_id();

				?>
				<SCRIPT LANGUAGE="JavaScript">
				alert("Se ha creado correctamente el registro.");
				window.location.href= "index.php?doc=<?=$webArchivo; ?>&opc=2&id=<?=$ultimoId; ?>";
				</script>
				<?
			}
		}
		else
		{
			$PSN = new DBbase_Sql;
			?>
			<div class="container">
            <form method="post" enctype="multipart/form-data" name="form1" id="form1">
				<div class="row"><h2>.CREACION DE <?=$nombreConsulta; ?> EN EL SISTEMA.</h2></div>
				
				<div class="row">
				  <div class="col-25">
					<label for="tipo">Tipo de curso</label>
				  </div>
				  <div class="col-75">
					<select name="tipo">
						<?
						$PSNTEMP = new DBbase_Sql;
						$sql= "SELECT categorias.* ";
						$sql.=" FROM categorias ";					
						$sql.=" WHERE idSec = 24 ";
						$sql.=" ORDER BY descripcion asc ";

						$PSNTEMP->query($sql);
						$num=$PSNTEMP->num_rows();
						if($num > 0)
						{
							while($PSNTEMP->next_record())
							{
								?><option value="<?=$PSNTEMP->f('id'); ?>" <?
								if(soloNumeros($_POST['tipo']) == $PSNTEMP->f('id'))
								{
									?>selected="selected"<? 
								}
								?>><?=$PSNTEMP->f('descripcion'); ?><br /><?
							}
						}
						?>
						</select>
				  </div>
				</div>
				
				<div class="row">
				  <div class="col-25">
					<label for="nombre">Nombre</label>
				  </div>
				  <div class="col-75">
					  <input name="nombre" id="nombre" type="text" maxlength="250" value="<?=eliminarInvalidos($_POST['nombre']); ?>" />
					</div>
				</div>
				
				<div class="row">
				  <div class="col-25">
					<label for="fechaInicial">Fecha Inicial</label>
				  </div>
				  <div class="col-75">
					  <input name="fechaInicial" type="date"  placeholder="AAAA-MM-DD" value="<?=eliminarInvalidos($_POST['fechaInicial']); ?>" />
					</div>
				</div>

				<div class="row">
				  <div class="col-25">
					<label for="fechaInicial">Fecha Final</label>
				  </div>
				  <div class="col-75">
					  <input name="fechaFinal" type="date"  placeholder="AAAA-MM-DD" value="<?=eliminarInvalidos($_POST['fechaFinal']); ?>" />
					</div>
				</div>

				<div class="row">
				  <div class="col-25">
					<label for="costo">Costo</label>
				  </div>
				  <div class="col-75">
						<input name="costo" type="number" maxlength="250" value="<?=soloNumeros($_POST['costo']); ?>" />
					</div>
				</div>

				<div class="row">
				  <div class="col-25">
					<label for="observaciones">Observaciones</label>
				  </div>
				  <div class="col-75">
					<input name="observaciones" type="text" maxlength="250" value="<?=eliminarInvalidos($_POST['observaciones']); ?>" />
					</div>
				</div>

				<div class="row">
					<input type="hidden" name="funcion" id="funcion" value="" />
					<input type="button" name="button" onclick="generarForm()" value="Crear" />
				</div>
				</form>
			</div>
              <script language="javascript">
                function generarForm(){
                        if(confirm("Esta accion generara el REGISTRO en el sistema, esta seguro que desea continuar?"))
                        {
                            if(document.getElementById('nombre').value != "")
                            {
                                document.getElementById('funcion').value = "insertar";
                                document.getElementById('form1').submit();
                            }
                            else
                            {
                                alert("La informacion es primordial para brindarle un excelente servicio, por favor digite al menos el campo de NOMBRE");
                            }
                        }
                }
                function init(){
                    document.getElementById('form1').onsubmit = function(){
                            return false;
                    }
                }
                
		 
                window.onload = function(){
                    init();
                }
                </script>
				<?
		}
}
else
{
	if(isset($_GET["id"]))
	{
		$PSN = new DBbase_Sql;
		if(isset($_POST["nombre"]))
		{
			/*
			*	ACTUALIZAR
			*/
			if($_POST["funcion"] == "actualizar")
			{
				$nombre = eliminarInvalidos($_POST["nombre"]);
				$tipo = eliminarInvalidos($_POST["tipo"]);
				$fechaInicial = eliminarInvalidos($_POST["fechaInicial"]);
				$fechaFinal = eliminarInvalidos($_POST["fechaFinal"]);
				$costo = soloNumeros($_POST["costo"]);
				$observaciones = eliminarInvalidos($_POST["observaciones"]);							
				//
				$modUsuario = $_SESSION["id"];
				//$modFecha;
				
				$sql = 'update '.$tablaConsulta.' set 
							tipo ="'.$tipo.'"
							nombre ="'.$nombre.'"
							,fechaInicial ="'.$fechaInicial.'"
							,fechaFinal ="'.$fechaFinal.'"
							,costo ="'.$costo.'"
							,observaciones ="'.$observaciones.'"
							,modUsuario ="'.$modUsuario.'"
							,modFecha = NOW() where id='.soloNumeros($_GET["id"]);				
				$PSN->query($sql);
	
				?>
				<SCRIPT LANGUAGE="JavaScript">
				alert("Se ha ACTUALIZADO correctamente el registro!");
				window.location.href= "index.php?doc=<?=$webArchivo; ?>&opc=2&id=<?=soloNumeros($_GET["id"]); ?>";
				</script>
				<?
			}
			/*
			*	ELIMINAR
			*/
			else if($_POST["funcion"] == "eliminar" && soloNumeros ($_GET["id"]) != "" && soloNumeros($_GET["id"]) > 0)
			{
				$sql = 'UPDATE '.$tablaConsulta.' SET estado = 0, modFecha = NOW(), modUsuario = "'.$_SESSION["id"].'" WHERE id = "'.soloNumeros($_GET["id"]).'"';
				$PSN->query($sql);
				?>
				<SCRIPT LANGUAGE="JavaScript">
				alert("Se ha ELIMINADO correctamente el registro!");
				window.location.href= "index.php?doc=<?=$webArchivo; ?>&opc=2&id=<?=soloNumeros($_GET["id"]); ?>";
				</script>
				<?
			}
		}
		else
		{
			$sql= "SELECT ".$tablaConsulta.".*, usuario.nombre as  creacionUsuarioNom, modif.nombre as modUsuarioNom";
			$sql.=" FROM ".$tablaConsulta;
				$sql.=" LEFT JOIN usuario ON usuario.id = ".$tablaConsulta.".creacionUsuario ";
				$sql.=" LEFT JOIN usuario modif ON modif.id = ".$tablaConsulta.".modUsuario ";			
			$sql.=" WHERE ".$tablaConsulta.".id='".soloNumeros($_GET["id"])."'";
			
			$PSN->query($sql);
			$num=$PSN->num_rows();
			if($num > 0)
			{
				$izq = 1;
				if($PSN->next_record())
				{
					$PSN2 = new DBbase_Sql;
 				?><div class="container">
	            <form method="post" enctype="multipart/form-data" name="form1" id="form1">
				<div class="row"><h2>.ACTUALIZACI&Oacute;N DE DATOS.</h2></div>
	            <?
				/*	
					if($PSN->f('estado') == 2){
						?><tr height="50px" bgcolor="#900"><td colspan="4"><center><h3 style="color:#fff">.ESTE REGISTRO HA SIDO ELIMINADO DE LA BASE PRINCIPAL, SOLO ESTA DISPONIBLE PARA CONSULTA PERO NO AFECTA LOS VALORES DEL TABLERO DE CONTROL NI LAS CONSULTAS PRINCIPALES.</h3></center></td></tr><?
					}	
				*/
				?>

					
				<div class="row">
				  <div class="col-25">
					<label for="tipo">Tipo</label>
				  </div>
				  <div class="col-75">
					<select name="tipo">
						<?
						$PSNTEMP = new DBbase_Sql;
						$sql= "SELECT categorias.* ";
						$sql.=" FROM categorias ";					
						$sql.=" WHERE idSec = 24 ";
						$sql.=" ORDER BY descripcion asc ";

						$PSNTEMP->query($sql);
						$num=$PSNTEMP->num_rows();
						if($num > 0)
						{
							while($PSNTEMP->next_record())
							{
								?><option value="<?=$PSNTEMP->f('id'); ?>" <?
								if($PSN->f('tipo') == $PSNTEMP->f('id'))
								{
									?>selected="selected"<? 
								}
								?>><?=$PSNTEMP->f('descripcion'); ?><br /><?
							}
						}
						?>
						</select>
				  </div>
				</div>
				
				<div class="row">
				  <div class="col-25">
					<label for="nombre">Nombre</label>
				  </div>
				  <div class="col-75">
					  <input name="nombre" id="nombre" type="text" maxlength="250" value="<?=$PSN->f('nombre'); ?>" />
					</div>
				</div>

				
				<div class="row">
				  <div class="col-25">
					<label for="fechaInicial">Fecha Inicial</label>
				  </div>
				  <div class="col-75">
					  <input name="fechaInicial" type="date"  placeholder="AAAA-MM-DD" value="<?=$PSN->f('fechaInicial'); ?>" />
					</div>
				</div>

				<div class="row">
				  <div class="col-25">
					<label for="fechaInicial">Fecha Final</label>
				  </div>
				  <div class="col-75">
					  <input name="fechaFinal" type="date"  placeholder="AAAA-MM-DD" value="<?=$PSN->f('fechaFinal'); ?>" />
					</div>
				</div>

				<div class="row">
				  <div class="col-25">
					<label for="costo">Costo</label>
				  </div>
				  <div class="col-75">
					 <input name="costo" type="number" maxlength="20" value="<?=$PSN->f('costo'); ?>" />
					</div>
				</div>

				<div class="row">
				  <div class="col-25">
					<label for="observaciones">Observaciones</label>
				  </div>
				  <div class="col-75">
					<input name="observaciones" type="text" maxlength="250" value="<?=$PSN->f('observaciones'); ?>" />
					</div>
				</div>

				<div class="row">
					<div class="col-25">
						<strong>Usuario que digito el registro</strong>
					</div>
					<div class="col-75">
						<?=$PSN->f('creacionUsuarioNom'); ?>
					</div>
				</div>


				<div class="row">
					<div class="col-25">
						<strong>Fecha de creaci&oacute;n del registro</strong>
					</div>
					<div class="col-75">
						<?=$PSN->f('creacionFecha'); ?>
					</div>
				</div>


				<div class="row">
					<div class="col-25">
						<strong>Usuario que realizo ultima modificaci&oacute;n</strong>
					</div>
					<div class="col-75">
						<?=$PSN->f('modUsuarioNom'); ?>
					</div>
				</div>


				<div class="row">
					<div class="col-25">
						<strong>Ultima modificaci&oacute;n Fecha</strong>
					</div>
					<div class="col-75">
						<?=$PSN->f('modFecha'); ?>
					</div>
				</div>

            <?
			if($PSN->f('estado') != 2){
			?>
				<div class="row">
					<input type="hidden" name="funcion" id="funcion" value="" />
					<input type="button" name="button" onclick="generarForm()" value="Actualizar Registro" />
					<input type="button" name="button" onclick="regresar()" class="cancelar" value="Cancelar">

				</div>
				
				<!--<br />
				<center><input type="button" name="button" onclick="generarFormDel()" value="Eliminar Registro" /></center>//-->
				</form>
						<script language="javascript">
							function generarForm(){
									if(confirm("Esta accion actualizara el REGISTRO en el sistema, esta seguro que desea continuar?"))
									{
										if(document.getElementById('nombre').value != "")
										{
											document.getElementById('funcion').value = "actualizar";
											document.getElementById('form1').submit();
										}
										else
										{
											alert("La informacion es primordial para brindarle un excelente servicio, por favor digite al menos el campo de NOMBRE");
										}
									}
							}


							function generarFormDel(){
									if(confirm("Esta accion ELIMINARA el REGISTRO en el sistema, esta seguro que desea continuar?"))
									{
										document.getElementById('funcion').value = "eliminar";
										document.getElementById('form1').submit();
									}
							}

							function init(){
								document.getElementById('form1').onsubmit = function(){
										return false;
								}
							}
							
							function regresar()
							{
								window.location.href = "index.php?doc=<?=$webArchivo; ?>";
							}

							window.onload = function(){
								init();
							}
							</script>
							<?
					}
				}
			}		
			else
			{
				?><div class="row"><h2><font color="#FF0000">ID Incorrecto. No Existe o no esta autorizado para visualizar la misma.</font></h2></div><?
			}	
		}
	}
	else
	{
		/********************************************************************************
		*********************************************************************************
		*****************				¡¡¡CONSULTA!!!
		*****************				¡¡¡CONSULTA!!!
		*********************************************************************************
		*********************************************************************************/
		$PSN = new DBbase_Sql;
		$PSNB = new DBbase_Sql;

		$registros = 50;
		$pagina = soloNumeros($_GET["pagina"]);
		if (!$pagina) { 
			$inicio = 0; 
			$pagina = 1; 
		} 
		else
		{ 
			$inicio = ($pagina - 1) * $registros; 
		}
		//
		$sqlB= "SELECT ".$tablaConsulta.".*, categorias.descripcion as nomTipoCurso";
		$sqlB.=" FROM ".$tablaConsulta;
			$sqlB.=" LEFT JOIN categorias ON categorias.id = ".$tablaConsulta.".tipoCampana ";
			$sqlB.=" LEFT JOIN mail_historico ON mail_historico.idCampana = ".$tablaConsulta.".id ";
			$sqlB.=" LEFT JOIN sms_historico ON sms_historico.idCampana = ".$tablaConsulta.".id ";
		$sqlB.=" WHERE 1 ";

		//
		$sqlC= "SELECT count(".$tablaConsulta.".id) as conteo"; // sum(".$tablaConsulta.".valor_prima_neta) as total_neto";
		$sqlC.=" FROM ".$tablaConsulta;
		$sqlC.=" WHERE 1 ";


		if(eliminarInvalidos($_GET["nombre"]) != "")
		{
			$sql.=" AND ".$tablaConsulta.".nombre  LIKE '%".eliminarInvalidos($_GET["nombre"])."%'";
		}

		if(soloNumeros($_GET["tipo"]) != "")
		{
			$sql.=" AND ".$tablaConsulta.".tipo LIKE '%".soloNumeros($_GET["tipo"])."%'";
		}

		
		if(eliminarInvalidos($_GET["fechaInicial"]) != "")
		{
			$sql.=" and ".$tablaConsulta.".fechaInicial >= '".eliminarInvalidos($_GET["fechaInicial"])."'";
			$fechaInicial = eliminarInvalidos($_GET["fechaInicial"]);
		}

		if(eliminarInvalidos($_GET["fechaFinal"]) != "")
		{
			$sql.=" and ".$tablaConsulta.".fechaFinal <= '".eliminarInvalidos($_GET["fechaFinal"])."'";
			$fechaFinal = eliminarInvalidos($_GET["fechaFinal"]);
		}
		
		
		$sql.=" GROUP BY ".$tablaConsulta.".id ORDER BY ".$tablaConsulta.".nombre ASC";
		$PSN->query($sqlC.$sql);
		$num=$PSN->num_rows();
		if($num > 0)
		{
			if($PSN->next_record()){
				$num = $PSN->f('conteo');
				$numTotal = $PSN->f('conteo');
			}
		}

		$total_registros = $num;
		$total_paginas = ceil($total_registros / $registros); 

		$sql.=" LIMIT ".$inicio.", ".$registros;;
		$PSN->query($sqlB.$sql);
		$num=$PSN->num_rows();
		?>
		<div class="container">
        	<div class="row">
				<h2>.<?=$nombreConsulta; ?>.</h2>
			</div>
	        <form action="index.php" method="get" name="form1">
    	    <input type="hidden" name="doc" value="<?=$webArchivo; ?>" />
	        <input type="hidden" name="opc" value="2" />
			<div class="row"><h2>.FILTROS DE BUSQUEDA.</h2></div>

			<div class="row">
				<div class="col-25">
				<label for="nombre">Nombre</label>
				</div>
				<div class="col-75">
					<input type="text" name="nombre" id="nombre" value="<?=eliminarInvalidos($_GET["nombre"]); ?>" />
				</div>
			</div>

			<div class="row">
				<div class="col-25">
				<label for="fechaInicial">Fecha inicial</label>
				</div>
				<div class="col-75">
					<input name="fechaInicial" type="date" id="fechaInicial" placeholder="AAAA-MM-DD" value="<?=eliminarInvalidos($_GET["fechaInicial"]); ?>" />
				</div>
			</div>

			<div class="row">
				<div class="col-25">
				<label for="fechaFinal">Fecha final</label>
				</div>
				<div class="col-75">
					<input name="fechaFinal" type="date" id="fechaFinal" placeholder="AAAA-MM-DD" value="<?=eliminarInvalidos($_GET["fechaFinal"]); ?>" />
				</div>
			</div>

			<div class="row">
				<div class="col-25">
				<label for="tipo">Tipo</label>
				</div>
				<div class="col-75">
					<select name="tipo">
					<option value="">Todos</option>
					<?
					$PSNTEMP = new DBbase_Sql;
					$sql= "SELECT categorias.* ";
						$sql.=" FROM categorias ";
						$sql.=" WHERE idSec = 24 ";
					$sql.=" ORDER BY descripcion asc ";

					$PSNTEMP->query($sql);
					$num=$PSNTEMP->num_rows();
					if($num > 0)
					{
						while($PSNTEMP->next_record())
						{
							?><option value="<?=$PSNTEMP->f('id'); ?>" <?
							if(soloNumeros($_GET["tipo"]) == $PSNTEMP->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMP->f('descripcion'); ?></option><?
						}
					}
					?>
					</select>
				</div>
			</div>

			<div class="row">
				<input type="submit" value="Buscar!" />
			</div>
			
			<div class="row"><h2>Se encontraron <?=intval($numTotal); ?> registros</h2></div>
    	    </form>
		</div>
		
		<table width="100%" border="0" cellspacing="2" cellpadding="2"  align="center" class="campanas">
		<tr>
			<th align="center">No.</th>
			<th align="center">Nombre</th>
			<th align="center">Tipo</th>
			<th align="center">Fecha Inicial</th>
			<th align="center">Fecha Final</th>
		</tr><?
			if($num > 0)
			{
				$izq = 1;
				$contador = $inicio+1;
				while($PSN->next_record())
				{
					$totalLeidos = 0;
					$sql = "SELECT sum(mail_log.visto) as totalLeidos";
					$sql.= " FROM mail_log";
					$sql.=" WHERE idCampana = '".$PSN->f('id')."'";
					$PSNTEMP->query($sql);
					$num=$PSNTEMP->num_rows();
					if($num > 0)
					{
						if($PSNTEMP->next_record())
						{
							$totalLeidos = $PSNTEMP->f('totalLeidos');
						}
					}

					?>
					<tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>>
						<td><a href="index.php?doc=<?=$webArchivo; ?>&opc=2&id=<?=$PSN->f('id');?>"><?=$contador; ?></a></td>
						<td><a href="index.php?doc=<?=$webArchivo; ?>&opc=2&id=<?=$PSN->f('id');?>"><?=$PSN->f('nombre');?></a></td>
						<td><?=$PSN->f('nomTipoCurso');?></td>
						<td align="center"><?=$PSN->f('fechaInicial'); ?></td>
						<td align="center"><?=$PSN->f('fechaFinal'); ?></td>
					</tr>
					<?
					$contador++;
				}
			}		
			else
			{
				?><tr>
				  <td colspan="10" align="center"><h2>.No hay registros.</h2></td>
				</tr><?
			}	
			?>  
		</table>
        <br />
		<?
		if(($pagina - 1) > 0)
		{
			echo "<a href='index.php?doc=".$webArchivo."
				&nombre=".eliminarInvalidos($_GET["nombre"])."
				&tipo=".eliminarInvalidos($_GET["tipo"])."
				&fechaInicial=".eliminarInvalidos($_GET["fechaInicial"])."
				&fechaFinal=".eliminarInvalidos($_GET["fechaFinal"])."
			&pagina=".($pagina-1)."' class='paginacion'>< Anterior</a> "; 
		}
	
		for ($i=1; $i<=$total_paginas; $i++)
		{ 
			if ($pagina == $i)
			{
				echo "<a href='#' class='paginacion_sel'>".$pagina."</a> "; 
			}
			else 
			{ 
				echo "<a href='index.php?doc=".$webArchivo."
				&nombre=".eliminarInvalidos($_GET["nombre"])."
				&tipo=".eliminarInvalidos($_GET["tipo"])."
				&fechaInicial=".eliminarInvalidos($_GET["fechaInicial"])."
				&fechaFinal=".eliminarInvalidos($_GET["fechaFinal"])."
				&pagina=$i' class='paginacion'>$i</a> "; 
			} 
		}
		if(($pagina + 1)<=$total_paginas)
		{ 
			echo " <a href='index.php?doc=".$webArchivo."
				&nombre=".eliminarInvalidos($_GET["nombre"])."
				&tipo=".eliminarInvalidos($_GET["tipo"])."
				&fechaInicial=".eliminarInvalidos($_GET["fechaInicial"])."
				&fechaFinal=".eliminarInvalidos($_GET["fechaFinal"])."
			&pagina=".($pagina+1)."' class='paginacion'>Siguiente ></a>"; 
		}
	}
}
?>