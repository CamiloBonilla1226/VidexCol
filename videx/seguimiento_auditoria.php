<?php ?><!-- jQuery UI -->
			<script src="scripts/jquery-1.12.4.js"></script>
			<link rel="stylesheet" href="scripts/jquery-ui.css">
			<script src="scripts/jquery-ui.js"></script>
<?php



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
$tablaConsulta = "proyecto_auditoria";
$webArchivo = "seguimiento_auditoria";
$nombreConsulta = "SEGUIMIENTOS DE AUDITORIA";

?>
<style>
	#auditoria-modern {
		--bg: #f4f7fb;
		--surface: #ffffff;
		--surface-2: #f9fbfe;
		--line: #d9e2ec;
		--line-strong: #c8d5e3;
		--text: #1b2a3a;
		--muted: #5d6b78;
		--primary: #1f5d92;
		--primary-soft: #e8f2fb;
		font-family: "Segoe UI", Tahoma, Arial, sans-serif;
		color: var(--text);
		width: 100%;
		max-width: 100%;
		margin: 0;
		padding: 12px;
		background: var(--bg);
		color-scheme: light;
		border: 1px solid var(--line);
		border-radius: 12px;
	}

	/* Modo claro estricto para evitar blanco sobre blanco heredado del tema global */
	#auditoria-modern,
	#auditoria-modern p,
	#auditoria-modern span,
	#auditoria-modern div,
	#auditoria-modern td,
	#auditoria-modern th,
	#auditoria-modern label,
	#auditoria-modern h1,
	#auditoria-modern h2,
	#auditoria-modern h3,
	#auditoria-modern h4,
	#auditoria-modern h5,
	#auditoria-modern h6 {
		color: var(--text);
	}

	#auditoria-modern a {
		color: #1f5d92;
	}

	#auditoria-modern * {
		box-sizing: border-box;
	}

	#auditoria-modern .container {
		width: 100%;
		max-width: 100% !important;
		margin: 0 0 12px 0;
		padding: 16px;
		background: var(--surface);
		border: 1px solid var(--line);
		border-radius: 10px;
		box-shadow: 0 3px 10px rgba(16, 42, 67, 0.05);
	}

	#auditoria-modern .row {
		margin-left: 0;
		margin-right: 0;
	}

	#auditoria-modern form {
		width: 100%;
		max-width: 100%;
	}

	#auditoria-modern .form-group {
		display: block;
		width: 100%;
		margin-bottom: 12px;
	}

	#auditoria-modern .form-label,
	#auditoria-modern label {
		display: inline-block;
		margin-bottom: 6px;
		font-weight: 600;
		color: var(--text);
	}

	#auditoria-modern .form-control,
	#auditoria-modern select,
	#auditoria-modern textarea,
	#auditoria-modern input[type="text"],
	#auditoria-modern input[type="date"] {
		display: block;
		width: 100%;
		max-width: 100%;
		padding: 9px 11px;
		border: 1px solid var(--line-strong);
		border-radius: 8px;
		background: #fff;
		font-size: 14px;
	}

	#auditoria-modern .form-control:focus,
	#auditoria-modern select:focus,
	#auditoria-modern textarea:focus,
	#auditoria-modern input[type="text"]:focus,
	#auditoria-modern input[type="date"]:focus {
		outline: none;
		border-color: var(--primary);
		box-shadow: 0 0 0 2px rgba(31, 93, 146, 0.15);
	}

	#auditoria-modern .btn {
		padding: 9px 14px;
		border-radius: 8px;
		font-weight: 600;
	}

	#auditoria-modern .btn-success {
		background: #1f8a55;
		border-color: #1f8a55;
		color: #fff !important;
	}

	#auditoria-modern .btn-danger {
		background: #c94545;
		border-color: #c94545;
		color: #fff !important;
	}

	#auditoria-modern .btn-info {
		background: #2f77b3;
		border-color: #2f77b3;
		color: #fff !important;
	}

	#auditoria-modern .form-control,
	#auditoria-modern select,
	#auditoria-modern textarea,
	#auditoria-modern input[type="text"],
	#auditoria-modern input[type="date"],
	#auditoria-modern select option {
		color: #1b2a3a !important;
		background: #fff !important;
	}

	#auditoria-modern h2,
	#auditoria-modern h3 {
		margin-top: 0;
	}

	#auditoria-modern .well,
	#auditoria-modern .alert-info,
	#auditoria-modern .alert-dark {
		background: var(--primary-soft);
		color: #17486f;
		border: 1px solid #cfe0ef;
		border-radius: 8px;
		padding: 10px 12px;
	}

	#auditoria-modern iframe {
		width: 100%;
		max-width: 100%;
		border: 1px solid var(--line);
		border-radius: 8px;
		background: var(--surface-2);
	}

	#auditoria-modern .auditoria-table-wrap {
		width: 100%;
		overflow-x: auto;
		border: 1px solid var(--line);
		border-radius: 8px;
		background: var(--surface);
	}

	#auditoria-modern table {
		width: 100%;
		margin: 0;
		border-collapse: collapse;
		min-width: 920px;
	}

	#auditoria-modern table th {
		background: #eef4fa;
		color: #224762;
		font-weight: 700;
	}

	#auditoria-modern table th,
	#auditoria-modern table td {
		padding: 9px 10px;
		border-color: var(--line) !important;
		vertical-align: top;
		font-size: 13px;
	}

	#auditoria-modern .auditoria-modern-table tbody tr:nth-child(even) {
		background: #f7fafd;
	}

	#auditoria-modern .auditoria-modern-table tbody tr:hover {
		background: #edf4fb;
	}

	#auditoria-modern .auditoria-modern-table th:nth-child(3),
	#auditoria-modern .auditoria-modern-table td:nth-child(3) {
		max-width: 500px;
		width: 500px;
		word-break: break-word;
		overflow-wrap: break-word;
		white-space: normal;
	}

	#auditoria-modern .estado-grid {
		display: flex;
		flex-wrap: wrap;
		gap: 10px;
		max-width: 470px;
		margin-top: 6px;
	}

	#auditoria-modern .estado-option {
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		gap: 6px;
		padding: 10px;
		border: 1px solid var(--line-strong);
		border-radius: 8px;
		background: var(--surface-2);
		cursor: pointer;
		transition: border-color .18s ease, background-color .18s ease;
		min-height: 108px;
		text-align: center;
		width: 145px;
		flex: 0 0 145px;
	}

	#auditoria-modern .estado-option.is-checked {
		border-color: var(--primary);
		background: var(--primary-soft);
	}

	#auditoria-modern .estado-option img {
		width: 48px !important;
		height: 48px !important;
		max-width: 50px !important;
		max-height: 50px !important;
		object-fit: contain;
	}

	#auditoria-modern .estado-option span {
		font-size: 14px;
		font-weight: 600;
		color: #25455f;
	}

	#auditoria-modern .estado-option input[type="radio"] {
		margin: 0;
	}

	#auditoria-modern img {
		max-width: 100%;
		height: auto;
	}

	@media (max-width: 900px) {
		#auditoria-modern {
			padding: 8px;
		}

		#auditoria-modern .container {
			padding: 12px;
		}

		#auditoria-modern .estado-grid {
			max-width: 100%;
		}

		#auditoria-modern .estado-option {
			width: calc(50% - 6px);
			flex: 0 0 calc(50% - 6px);
		}
	}

	@media (max-width: 560px) {
		#auditoria-modern .estado-option {
			width: 100%;
			flex: 0 0 100%;
		}
	}

	#auditoria-modern .auditoria-editor-form {
		width: 100%;
	}

	#auditoria-modern .auditoria-editor-shell {
		display: block;
		width: 100%;
	}

	#auditoria-modern .auditoria-editor-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(220px, 1fr));
		gap: 12px;
	}

	#auditoria-modern .auditoria-editor-grid .form-group {
		margin-bottom: 0;
	}

	#auditoria-modern .auditoria-editor-grid .form-group.full {
		grid-column: 1 / -1;
	}

	#auditoria-modern .auditoria-actions {
		padding-top: 10px;
	}

	@media (max-width: 900px) {
		#auditoria-modern .auditoria-editor-grid {
			grid-template-columns: 1fr;
		}
	}
</style>
<div id="auditoria-modern">
<script>
	document.addEventListener('DOMContentLoaded', function () {
		var root = document.getElementById('auditoria-modern');
		if (!root) return;

		var main = document.querySelector('.main-content, #main-content, #maincontent, #mainContent');
		if (main && !main.contains(root)) {
			main.appendChild(root);
		}

		// Si por HTML legacy hay nodos huérfanos fuera del wrapper, reubicarlos dentro.
		var orphanSelectors = [
			'body > .container',
			'body > center',
			'body > br',
			'.videx-wrap > .container',
			'.videx-wrap > .form-group',
			'.videx-wrap > .row',
			'.videx-wrap > .auditoria-actions'
		];
		orphanSelectors.forEach(function (selector) {
			document.querySelectorAll(selector).forEach(function (node) {
				if (node === root) return;
				if (root.contains(node)) return;
				root.appendChild(node);
			});
		});

		// Defensa adicional: cualquier form-group suelto fuera del módulo se integra al wrapper.
		document.querySelectorAll('.form-group, .auditoria-actions').forEach(function (node) {
			if (root.contains(node)) return;
			if (node.closest('.videx-sidebar')) return;
			if (node.closest('.videx-footer')) return;
			if (node.closest('#auditoria-modern')) return;
			root.appendChild(node);
		});

		var tables = root.querySelectorAll('table');
		tables.forEach(function (table) {
			if (table.parentElement && table.parentElement.classList.contains('auditoria-table-wrap')) {
				return;
			}
			var wrap = document.createElement('div');
			wrap.className = 'auditoria-table-wrap';
			table.parentNode.insertBefore(wrap, table);
			wrap.appendChild(table);
		});

		function syncEstadoCards() {
			root.querySelectorAll('.estado-option').forEach(function (card) {
				var radio = card.querySelector('input[type="radio"][name="estado"]');
				if (!radio) return;
				card.classList.toggle('is-checked', !!radio.checked);
			});
		}

		root.querySelectorAll('.estado-option input[type="radio"][name="estado"]').forEach(function (radio) {
			radio.addEventListener('change', syncEstadoCards);
		});

		syncEstadoCards();
	});
</script>
<?php

$PSN1 = new DBbase_Sql;
if($opc == 1)
{
	
		if(isset($_POST["NombProyecto"]))
		{
			
			$PSN = new DBbase_Sql;

			/*$sql= "SELECT ".$tablaConsulta.".id";
			$sql.=" FROM ".$tablaConsulta;
			$sql.=" WHERE ".$tablaConsulta.".contactoNombre = '".eliminarInvalidos($_REQUEST["contactoNombre"])."'";
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
			else*/
			if(1)
			{
				$idCliente = soloNumeros($_POST["idCliente"]);
				$NombProyecto = eliminarInvalidos($_POST["NombProyecto"]);
				$fechainicio = eliminarInvalidos($_POST["fechainicio"]);
				$fechafinal = eliminarInvalidos($_POST["fechafinal"]);
				$descripcionproyecto = eliminarInvalidos($_POST["descripcionproyecto"]);
				$estado = eliminarInvalidos($_POST["estado"]);
				$idClienteUno = soloNumeros($_POST["idClienteUno"]);
				$idClienteDos = soloNumeros($_POST["idClienteDos"]);
				$PerfilUno = soloNumeros($_POST["PerfilUno"]);
				$idPersonaUno = soloNumeros($_POST["idPersonaUno"]);
				$idvalPerUno = soloNumeros($_POST["idvalPerUno"]);
				$PerfilDos = soloNumeros($_POST["PerfilDos"]);
				$idPersonaDos = soloNumeros($_POST["idPersonaDos"]);
				$idvalPerDos = soloNumeros($_POST["idvalPerDos"]);
				$PerfilTres = soloNumeros($_POST["PerfilDos"]);
				$idPersonaTres = soloNumeros($_POST["idPersonaTres"]);
				$idvalPerTres = soloNumeros($_POST["idvalPerTres"]);
				$idCotizacion = soloNumeros($_POST["idCotizacion"]);
				$idEstadoAuditoria= soloNumeros($_POST["idEstadoAuditoria"]);
				
				$sql = 'insert into '.$tablaConsulta.' (
					id_prospecto, 
					nomb_proyecto,
					fec_inicio,
					fec_fin,
					descrip_proyecto,
					estado,
					idClienteUno,
					idClienteDos,
					PerfilUno,
					idPersonaUno,
					idvalPerUno,
					PerfilDos,
					idPersonaDos,
					idvalPerDos,
					PerfilTres,
					idPersonaTres,
					idvalPerTres,
					idCotizacion,
					idEstadoAuditoria


					) ';

				$sql .= 'values (
					"'.$idCliente.'", 
					"'.$NombProyecto.'", 
					"'.$fechainicio.'", 
					"'.$fechafinal.'", 
					"'.$descripcionproyecto.'", 
					"'.$estado.'", 
					"'.$idClienteUno.'", 
					"'.$idClienteDos.'", 
					"'.$PerfilUno.'", 
					"'.$idPersonaUno.'", 
					"'.$idvalPerUno.'", 
					"'.$PerfilDos.'", 
					"'.$idPersonaDos.'", 
					"'.$idvalPerDos.'",
					"'.$PerfilTres.'", 
					"'.$idPersonaTres.'", 
					"'.$idvalPerTres.'", 
					"'.$idCotizacion.'", 
					"'.$idEstadoAuditoria.'"

				)';

				$ultimoQuery = $PSN->query($sql);
				$ultimoId = $PSN->ultimoId();
				//
				//
				

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
			?><div class="container">
            <form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form">
                
            <div class="form-group">
                <h2 class="alert alert-info text-center">.CREACION DE <?=$nombreConsulta; ?> </h2>
            </div>

			<div class="form-group">
            	<label class="form-label" for="idCliente">Prospecto</label>
				
					<select name="idCliente" id="idCliente"  class="form-control">
						<option value="">Seleccione un prospecto...</option>
					<?
					$PSNTEMP = new DBbase_Sql;
					$sql= "SELECT cliente.id, cliente.nombre ";
					$sql.=" FROM cliente WHERE estado != 0";
                    $sql.=" ORDER BY nombre asc";

					$PSNTEMP->query($sql);
					$num=$PSNTEMP->num_rows();
					if($num > 0)
					{
						while($PSNTEMP->next_record())
						{
							?><option value="<?=$PSNTEMP->f('id'); ?>" <?
							if(soloNumeros($_POST["idCliente"]) == $PSNTEMP->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMP->f('nombre'); ?></option><?
						}
					}
					?>
					</select>
				</div>
				<label class="form-label" for="NombProyecto">Nombre del Proyecto</label>
				
					<div class="ui-widget">
						<input name="NombProyecto" id="NombProyecto" type="text" maxlength="250" 
						value="<?=eliminarInvalidos($_POST['NombProyecto']); ?>" class="form-control" />
					</div>
				</div>

			</div>

			<div class="form-group">
				
			</div>
			
			<div class="form-group">
                
                <label class="form-label" for="fechainicio">Fecha de inicio</label>
				
					<input name="fechainicio"  type="date"  placeholder="AAAA-MM-DD" maxlength="250" value="<?=eliminarInvalidos($_POST['fechaCotizacion']); ?>" class="form-control" />
				</div>

				<label class="form-label" for="fechafinal">Fecha final</label>
				
					<input name="fechafinal"  type="date"  placeholder="AAAA-MM-DD" maxlength="250" value="<?=eliminarInvalidos($_POST['fechaCotizacion']); ?>" class="form-control" />
				</div>
			</div>

			<div class="form-group">
            	<label class="form-label" for="idCotizacion">Cotizacion</label>
				
				<select name="idCotizacion" id="idCotizacion" class="form-control">
						<option value="">--No hay cotizaciones--</option>
						</select>
				</div>
				<label class="form-label" for="idEstadoAuditoria">Estado Auditoria</label>
				
					
					<select name="idEstadoAuditoria" id="idEstadoAuditoria"  class="form-control">
						<option value="" selected="selected">Seleccione el Estado...</option>
						<option value="1" >Iniciado</option>
						<option value="2" >Terminado</option>
						
					</select>
			</div>

			

			<div class="form-group">
                <label class="form-label" for="descripcionproyecto">Descripci&oacute;n del proyecto</label>
				
					<textarea name="descripcionproyecto" rows="10" cols="100" class="form-control"><?=eliminarInvalidos($_POST['descripcion']); ?></textarea>
				</div>

				<label class="form-label" for="estadodelproyecto">Estado del proyecto</label>
				<div class="estado-grid">
					<label class="estado-option">
						<input type="radio" name="estado" value="Relajado">
						<img src="images/consultores/relajado.png" alt="Relajado">
						<span>Relajado</span>
					</label>
					<label class="estado-option">
						<input type="radio" name="estado" value="Caminando">
						<img src="images/consultores/caminando.png" alt="Caminando">
						<span>Caminando</span>
					</label>
					<label class="estado-option">
						<input type="radio" name="estado" value="Corriendo">
						<img src="images/consultores/corriendo.png" alt="Corriendo">
						<span>Corriendo</span>
					</label>
				</div>
				</div>
			</div>
		
			<div class="form-group">
            	<label class="form-label" for="idClienteUno">Cliente 1</label>
				
				<select name="idClienteUno" id="idClienteUno" class="form-control">
						<option value="">--No hay clientes--</option>
						</select>
			</div>

			<div class="form-group">
            	<label class="form-label" for="idClienteDos">Cliente 2</label>
				
				<select name="idClienteDos" id="idClienteDos" class="form-control">
						<option value="">--No hay clientes--</option>
						</select>
			</div>

			<div class="form-group">
			<label class="form-label" for="PerfilUno">Perfil 1</label>
				
					<select name="PerfilUno" id="PerfilUno"  class="form-control">
						<option value="">Seleccione un perfil...</option>
					<?
					$PSNTEMPcat = new DBbase_Sql;
					$sql= "SELECT id, descripcion ";
					$sql.=" FROM categorias WHERE idSec=36";
                    $sql.=" ORDER BY descripcion asc";

					$PSNTEMPcat->query($sql);
					$num=$PSNTEMPcat->num_rows();
					if($num > 0)
					{
						while($PSNTEMPcat->next_record())
						{
							?><option value="<?=$PSNTEMPcat->f('id'); ?>" <?
							if(soloNumeros($_POST["idCliente"]) == $PSNTEMPcat->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMPcat->f('descripcion'); ?></option><?
						}
					}
					?>
					</select>



            	
				</div>
				<div class="form-group">
				<label class="form-label" for="idPersonaUno">Persona 1</label>
				
					<select name="idPersonaUno" id="idPersonaUno"  class="form-control">
						<option value="" selected="selected">--No hay Personal--</option>
					
					</select>
				</div>

				<label class="form-label" for="idvalPerUno">Valoracion</label>
				
					
					<select name="idvalPerUno" id="idvalPerUno"  class="form-control">
						<option value="" selected="selected">Seleccione la calificacion...</option>
						<option value="1" >Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" >Regular</option>
						<option value="4" >Buena</option>
						<option value="5" >Excelente</option>
					</select>

			</div>

			<div class="form-group">
			<label class="form-label" for="PerfilDos">Perfil 2</label>
				
					<select name="PerfilDos" id="PerfilDos"  class="form-control">
						<option value="">Seleccione un perfil...</option>
					<?
					$PSNTEMPcat = new DBbase_Sql;
					$sql= "SELECT id, descripcion ";
					$sql.=" FROM categorias WHERE idSec=36";
                    $sql.=" ORDER BY descripcion asc";

					$PSNTEMPcat->query($sql);
					$num=$PSNTEMPcat->num_rows();
					if($num > 0)
					{
						while($PSNTEMPcat->next_record())
						{
							?><option value="<?=$PSNTEMPcat->f('id'); ?>" <?
							if(soloNumeros($_POST["PerfilDos"]) == $PSNTEMPcat->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMPcat->f('descripcion'); ?></option><?
						}
					}
					?>
					</select>



            	
				</div>




			<div class="form-group">
            	<label class="form-label" for="idPersonaDos">Persona 2</label>
				
					<select name="idPersonaDos" id="idPersonaDos"  class="form-control">
						<option value="" selected="selected">Seleccione un Personal...</option>
					
					</select>
				</div>
				<label class="form-label" for="idvalPerDos">Valoracion</label>
				
					
					<select name="idvalPerDos" id="idvalPerDos"  class="form-control">
						<option value="" selected="selected">Seleccione la calificacion...</option>
						<option value="1" >Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" >Regular</option>
						<option value="4" >Buena</option>
						<option value="5" >Excelente</option>
					</select>

			</div>
            <div class="form-group">
			<label class="form-label" for="PerfilTres">Perfil 3</label>
				
					<select name="PerfilTres" id="PerfilTres"  class="form-control">
						<option value="">Seleccione un perfil...</option>
					<?
					$PSNTEMPcat = new DBbase_Sql;
					$sql= "SELECT id, descripcion ";
					$sql.=" FROM categorias WHERE idSec=36";
                    $sql.=" ORDER BY descripcion asc";

					$PSNTEMPcat->query($sql);
					$num=$PSNTEMPcat->num_rows();
					if($num > 0)
					{
						while($PSNTEMPcat->next_record())
						{
							?><option value="<?=$PSNTEMPcat->f('id'); ?>" <?
							if(soloNumeros($_POST["idCliente"]) == $PSNTEMPcat->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMPcat->f('descripcion'); ?></option><?
						}
					}
					?>
					</select>



            	
				</div>
				<div class="form-group">
				<label class="form-label" for="idPersonaTres">Persona 3</label>
				
					<select name="idPersonaTres" id="idPersonaTres"  class="form-control">
						<option value="" selected="selected">--No hay Personal--</option>
					
					</select>
				</div>

				<label class="form-label" for="idvalPerTres">Valoracion</label>
				
					
					<select name="idvalPerTres" id="idvalPerTres"  class="form-control">
						<option value="" selected="selected">Seleccione la calificacion...</option>
						<option value="1" >Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" >Regular</option>
						<option value="4" >Buena</option>
						<option value="5" >Excelente</option>
					</select>

			</div>









            <div class="row text-center">
                <input type="hidden" name="funcion" id="funcion" value="" />
                <input type="submit" value="Guardar cambios" class="btn btn-success" />
            </div>

            </form>
			</div>
			<script src="scripts/jquery-1.12.4.js"></script>
            <script language="javascript">

					// traer id cotizacion 
					$(function(){
					$("#idCliente").on('change',function(){
						var varidCliente = $("#idCliente").val();
						//document.getElementById('idCliente').value; // selected value
					//console.log(varidCliente);
					var url = "busquedaCotizacionAuditoria.php";
					$.ajax( {
						type:'POST',
						url:url,
						data:'idCliente='+varidCliente,
						success: function( data ) {
										$("#idCotizacion option").remove();
										$("#idCotizacion").append(data);
										

									}
									
									
									
								} );

					}); 
					return false;
					});





                // revisarparte1 
				$(function(){
					$("#idCliente").on('change',function(){
						var varidCliente = $("#idCliente").val();
						//document.getElementById('idCliente').value; // selected value
					//console.log(varidCliente);
					var url = "busquedaContactoAuditoria.php";
					$.ajax( {
						type:'POST',
						url:url,
						data:'idCliente='+varidCliente,
						success: function( data ) {
										$("#idClienteUno option").remove();
										$("#idClienteUno").append(data);
										$("#idClienteDos option").remove();
										$("#idClienteDos").append(data);

									}
									
									
									
								} );

					}); 
					return false;
					});

					// perfil Uno
					// revisar
					$(function(){
					$("#PerfilUno").on('change',function(){
						var varidCliente = $("#PerfilUno").val();
						//document.getElementById('idCliente').value; // selected value
					// console.log(varidCliente);
					var url = "busquedaPerfilAuditoria.php";
					$.ajax( {
						type:'POST',
						url:url,
						data:'idPerfil='+varidCliente,
						success: function( data ) {
										$("#idPersonaUno option").remove();
										$("#idPersonaUno").append(data);
										

									}
									
									
									
								} );

					});
					return false;
								});

						// Perfil Dos

					$(function(){
					$("#PerfilDos").on('change',function(){
						var varidCliente = $("#PerfilDos").val();
						//document.getElementById('idCliente').value; // selected value
					//console.log(varidCliente);
					var url = "busquedaPerfilAuditoria.php";
					$.ajax( {
						type:'POST',
						url:url,
						data:'idPerfil='+varidCliente,
						success: function( data ) {
										$("#idPersonaDos option").remove();
										$("#idPersonaDos").append(data);
										

									}
									
									
									
								} );

						});
						return false;
							});
							$(function(){
					$("#PerfilTres").on('change',function(){
						var varidCliente = $("#PerfilTres").val();
						//document.getElementById('idCliente').value; // selected value
					//console.log(varidCliente);
					var url = "busquedaPerfilAuditoria.php";
					$.ajax( {
						type:'POST',
						url:url,
						data:'idPerfil='+varidCliente,
						success: function( data ) {
										$("#idPersonaTres option").remove();
										$("#idPersonaTres").append(data);
										

									}
									
									
									
								} );

						});
						return false;
							});			







				function generarForm(){
                        if(confirm("Esta accion generara el REGISTRO en el sistema, esta seguro que desea continuar?"))
                        {
                            if(document.getElementById('NombProyecto').value != "" && document.getElementById('idCliente').value != "")
                            {
                                document.getElementById('funcion').value = "insertar";
                              //  document.getElementById('form1').submit();
                              return true;
                            }
                            else
                            {
								document.getElementById('idContacto').value = "0";
                                alert("La informacion es primordial para brindarle un excelente servicio, por favor seleccione al menos un prospecto y digite el campo de NOMBRE DEL CONTACTO");
                            }
                        }
                              return false;
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
}
else
{
	if(isset($_GET["id"]))
	{
		$PSN = new DBbase_Sql;
		if(isset($_POST["NombProyecto"]))
		{
			//
			/*
			*	ACTUALIZAR
			*/
			if($_POST["funcion"] == "actualizar")
			{
				$idCliente = soloNumeros($_POST["idCliente"]);
				$NombProyecto = eliminarInvalidos($_POST["NombProyecto"]);
				$fechainicio = eliminarInvalidos($_POST["fechainicio"]);
				$fechafinal = eliminarInvalidos($_POST["fechafinal"]);
				$descripcionproyecto = eliminarInvalidos($_POST["descripcionproyecto"]);
				$estado = eliminarInvalidos($_POST["estado"]);
				$idClienteUno = soloNumeros($_POST["idClienteUno"]);
				$idClienteDos = soloNumeros($_POST["idClienteDos"]);
				$PerfilUno = soloNumeros($_POST["PerfilUno"]);
				$idPersonaUno = soloNumeros($_POST["idPersonaUno"]);
				$idvalPerUno = soloNumeros($_POST["idvalPerUno"]);
				$PerfilDos = soloNumeros($_POST["PerfilDos"]);
				$idPersonaDos = soloNumeros($_POST["idPersonaDos"]);
				$idvalPerDos = soloNumeros($_POST["idvalPerDos"]);
				$PerfilTres = soloNumeros($_POST["PerfilDos"]);
				$idPersonaTres = soloNumeros($_POST["idPersonaTres"]);
				$idvalPerTres = soloNumeros($_POST["idvalPerTres"]);
				$idCotizacion = soloNumeros($_POST["idCotizacion"]);
				$idEstadoAuditoria= soloNumeros($_POST["idEstadoAuditoria"]);
				
				
				$sql = 'update '.$tablaConsulta.' set 
				id_prospecto = "'.$idCliente.'",
				nomb_proyecto="'.$NombProyecto.'",
				fec_inicio="'.$fechainicio.'",
				fec_fin="'.$fechafinal.'",
				descrip_proyecto="'.$descripcionproyecto.'", 
				estado="'.$estado.'",
				idClienteUno="'.$idClienteUno.'",
				idClienteDos="'.$idClienteDos.'",
				PerfilUno="'.$PerfilUno.'", 
				idPersonaUno="'.$idPersonaUno.'",
				idvalPerUno="'.$idvalPerUno.'",
				PerfilDos="'.$PerfilDos.'",
				idPersonaDos="'.$idPersonaDos.'",
				idvalPerDos="'.$idvalPerDos.'",
				PerfilTres="'.$PerfilTres.'",
				idPersonaTres="'.$idPersonaTres.'",
				idvalPerTres="'.$idvalPerTres.'",
				idCotizacion="'.$idCotizacion.'",
				idEstadoAuditoria="'.$idEstadoAuditoria.'"
				where id='.soloNumeros($_GET["id"]);				
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
			$sql= "SELECT ".$tablaConsulta.".*";
			$sql.=" FROM ".$tablaConsulta;
			//	$sql.=" LEFT JOIN usuario ON usuario.id = ".$tablaConsulta.".creacionUsuario ";
			//	$sql.=" LEFT JOIN usuario modif ON modif.id = ".$tablaConsulta.".modUsuario ";			
			$sql.=" WHERE ".$tablaConsulta.".id='".soloNumeros($_GET["id"])."'";
			
			$PSN->query($sql);
			$num=$PSN->num_rows();
			if($num > 0)
			{
				$izq = 1;
				if($PSN->next_record())
				{
					$PSN2 = new DBbase_Sql;


            echo '<script type="text/javascript" src="scripts/ckeditor/ckeditor.js"></script>'."\n";

            ?><div class="container">
            <div class="form-group">
                <h2 class="alert alert-info text-center">.ACTUALIZACI&Oacute;N <?=$nombreConsulta; ?>.</h2>
            </div>
                    
			<form method="post" enctype="multipart/form-data" name="form1" id="form1" class="form auditoria-editor-form">
			<div class="auditoria-editor-shell">
            <br />
			<div class="auditoria-editor-grid">

            <div class="form-group">
				<label class="form-label" for="idCliente">Prospecto</label>
				
					<select name="idCliente" id="idCliente" class="form-control">
					<?
					$PSNTEMP = new DBbase_Sql;
					$sql= "SELECT cliente.id, cliente.nombre ";
					$sql.=" FROM cliente ";
                    $sql.=" ORDER BY nombre asc ";

					$PSNTEMP->query($sql);
					$num=$PSNTEMP->num_rows();
					if($num > 0)
					{
						while($PSNTEMP->next_record())
						{
							?><option value="<?=$PSNTEMP->f('id'); ?>" <?
							if($PSN->f("id_prospecto") == $PSNTEMP->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMP->f('nombre'); ?></option><?
						}
					}
					?>
					</select>
				</div>

				<div class="form-group">
					<label class="form-label" for="NombProyecto">Nombre del Proyecto</label>
					<div class="ui-widget">
						<input name="NombProyecto" id="NombProyecto" type="text" maxlength="250" value="<?=$PSN->f('nomb_proyecto'); ?>" class="form-control" />
					</div>
				</div>

				<div class="form-group">
					<label class="form-label" for="fechainicio">Fecha de inicio</label>
					<input name="fechainicio" type="date" placeholder="AAAA-MM-DD" maxlength="250" value="<?=$PSN->f('fec_inicio'); ?>" class="form-control" />
				</div>

				<div class="form-group">
					<label class="form-label" for="fechafinal">Fecha finalizacion</label>
					<input name="fechafinal" type="date" placeholder="AAAA-MM-DD" maxlength="250" value="<?=$PSN->f('fec_fin'); ?>" class="form-control" />
				</div>

			<div class="form-group">
            	<label class="form-label" for="idCotizacion">Cotizacion</label>
				
				<select name="idCotizacion" id="idCotizacion" class="form-control">
						<option value="">Elige tu proyecto a Auditar</option>
						<?
					$PSNTEMPcliuno = new DBbase_Sql;
					$sql= "SELECT id, descripcion ";
					$sql.=" FROM cotizacion where idCliente=".$PSN->f("id_prospecto");
                    $sql.=" ORDER BY id asc";

					$PSNTEMPcliuno->query($sql);
					$num=$PSNTEMPcliuno->num_rows();
					if($num > 0)
					{
						while($PSNTEMPcliuno->next_record())
						{
							?><option value="<?=$PSNTEMPcliuno->f('id'); ?>" <?
							if($PSN->f("idCotizacion") == $PSNTEMPcliuno->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMPcliuno->f('descripcion'); ?></option><?
						}
					}
					?>
				</select>
				</div>

				<div class="form-group">
					<label class="form-label" for="idEstadoAuditoria">Estado Auditoria</label>
					<select name="idEstadoAuditoria" id="idEstadoAuditoria" class="form-control">
						<option value="">Elige el estado de tu auditoria</option>
						
						<? if($PSN->f("idEstadoAuditoria") == "1"){?>
						<option value="1" selected="selected">Iniciado </option>
						<option value="2" >Terminado</option>
						<? } else {?>
						<option value="1" >Iniciado </option>
						<option value="2" selected="selected" >Terminado</option>
						<? } ?>

						
					</select>
				</div>










			<div class="form-group full">
                <label class="form-label" for="descripcionproyecto">Descripci&oacute;n del proyecto</label>
				
					<textarea name="descripcionproyecto" rows="10" cols="100" class="form-control"><?=$PSN->f('descrip_proyecto'); ?></textarea>
				</div>

				<div class="form-group full">
				<label class="form-label" for="descripcionproyecto">Estado del proyecto</label>
				<div class="estado-grid">
					<label class="estado-option">
						<input type="radio" name="estado" value="Relajado" <? if($PSN->f('estado')=="Relajado"){ ?>checked<? } ?>>
						<img src="images/consultores/relajado.png" alt="Relajado">
						<span>Relajado</span>
					</label>
					<label class="estado-option">
						<input type="radio" name="estado" value="Caminando" <? if($PSN->f('estado')=="Caminando"){ ?>checked<? } ?>>
						<img src="images/consultores/caminando.png" alt="Caminando">
						<span>Caminando</span>
					</label>
					<label class="estado-option">
						<input type="radio" name="estado" value="Corriendo" <? if($PSN->f('estado')=="Corriendo"){ ?>checked<? } ?>>
						<img src="images/consultores/corriendo.png" alt="Corriendo">
						<span>Corriendo</span>
					</label>
				</div>
				</div>
			</div>

			<div class="form-group">
            	<label class="form-label" for="idClienteUno">Cliente 1</label>
				
				<select name="idClienteUno" id="idClienteUno" class="form-control">
						<option value="">Elige tu cliente</option>
						<?
					$PSNTEMPcliuno = new DBbase_Sql;
					$sql= "SELECT id, nombres ";
					$sql.=" FROM sms_usuarios where idCliente=".$PSN->f("id_prospecto");
                    $sql.=" ORDER BY nombres asc";

					$PSNTEMPcliuno->query($sql);
					$num=$PSNTEMPcliuno->num_rows();
					if($num > 0)
					{
						while($PSNTEMPcliuno->next_record())
						{
							?><option value="<?=$PSNTEMPcliuno->f('id'); ?>" <?
							if($PSN->f("idClienteUno") == $PSNTEMPcliuno->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMPcliuno->f('nombres'); ?></option><?
						}
					}
					?>
				
				
				</select>
			</div>

			<div class="form-group">
            	<label class="form-label" for="idClienteDos">Cliente 2</label>
				
				<select name="idClienteDos" id="idClienteDos" class="form-control">
				<option value="">Elige tu cliente</option>
						<?
					$PSNTEMPclidos = new DBbase_Sql;
					$sql= "SELECT id, nombres ";
					$sql.=" FROM sms_usuarios where idCliente=".$PSN->f("id_prospecto");
                    $sql.=" ORDER BY nombres asc";

					$PSNTEMPclidos->query($sql);
					$num=$PSNTEMPclidos->num_rows();
					if($num > 0)
					{
						while($PSNTEMPclidos->next_record())
						{
							?><option value="<?=$PSNTEMPclidos->f('id'); ?>" <?
							if($PSN->f("idClienteDos") == $PSNTEMPclidos->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMPclidos->f('nombres'); ?></option><?
						}
					}
					?>
						</select>
			</div>

			<div class="form-group">
			<label class="form-label" for="PerfilUno">Perfil 1</label>
				
					<select name="PerfilUno" id="PerfilUno"  class="form-control">
						<option value="">Seleccione un perfil...</option>
						
					<?
					$PSNTEMPperfuno = new DBbase_Sql;
					$sql= "SELECT id, descripcion ";
					$sql.=" FROM categorias WHERE idSec=36";
                    $sql.=" ORDER BY descripcion asc";

					$PSNTEMPperfuno->query($sql);
					$num=$PSNTEMPperfuno->num_rows();
					if($num > 0)
					{
						while($PSNTEMPperfuno->next_record())
						{
							?><option value="<?=$PSNTEMPperfuno->f('id'); ?>" <?
							if($PSN->f("PerfilUno") == $PSNTEMPperfuno->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMPperfuno->f('descripcion'); ?></option><?
						}
					}
					?>
					</select>



            	
				</div>
				<div class="form-group">
				<label class="form-label" for="idPersonaUno">Persona 1</label>
				
					<select name="idPersonaUno" id="idPersonaUno"  class="form-control">
					<option value="">Elige tu cliente</option>
						<?
					$PSNTEMPperuno = new DBbase_Sql;
					$sql= "SELECT cc, nombres ";
					$sql.=" FROM personal where id_tipo_personal=".$PSN->f("PerfilUno");
                    $sql.=" ORDER BY nombres asc";

					$PSNTEMPperuno->query($sql);
					$num=$PSNTEMPperuno->num_rows();
					if($num > 0)
					{
						while($PSNTEMPperuno->next_record())
						{
							?><option value="<?=$PSNTEMPperuno->f('cc'); ?>" <?
							if($PSN->f("idPersonaUno") == $PSNTEMPperuno->f('cc'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMPperuno->f('nombres'); ?></option><?
						}
					}
					?>
					





					</select>
				</div>

				<label class="form-label" for="idvalPerUno">Valoracion</label>
				
					
					<select name="idvalPerUno" id="idvalPerUno"  class="form-control">
					<option value="">Seleccione la calificacion...</option>
					<?   if($PSN->f('idvalPerUno')==1) { ?>
						<option value="1" selected="selected">Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" >Regular</option>
						<option value="4" >Buena</option>
						<option value="5"  >Excelente</option>
						<? } elseif($PSN->f('idvalPerUno')==2) { ?>
						<option value="1" >Deficiente</option>
						<option value="2" selected="selected">Malo</option>
						<option value="3" >Regular</option>
						<option value="4" >Buena</option>
						<option value="5" >Excelente</option>
						<? } elseif($PSN->f('idvalPerUno')==3) { ?>
						<option value="1" >Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" selected="selected">Regular</option>
						<option value="4" >Buena</option>
						<option value="5" >Excelente</option>
						<? } elseif($PSN->f('idvalPerUno')==4) { ?>
						<option value="1" >Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" >Regular</option>
						<option value="4" selected="selected">Buena</option>
						<option value="5" >Excelente</option>
						<? } else  { ?>
						<option value="1" >Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" >Regular</option>
						<option value="4" >Buena</option>
						<option value="5" selected="selected">Excelente</option>
						<? } ?>

					</select>

			</div>

			<div class="form-group">
			<label class="form-label" for="PerfilDos">Perfil 2</label>
				
					<select name="PerfilDos" id="PerfilDos"  class="form-control">
						<option value="">Seleccione un perfil...</option>
					<?
					$PSNTEMPperfdos = new DBbase_Sql;
					$sql= "SELECT id, descripcion ";
					$sql.=" FROM categorias WHERE idSec=36";
                    $sql.=" ORDER BY descripcion asc";

					$PSNTEMPperfdos->query($sql);
					$num=$PSNTEMPperfdos->num_rows();
					if($num > 0)
					{
						while($PSNTEMPperfdos->next_record())
						{
							?><option value="<?=$PSNTEMPperfdos->f('id'); ?>" <?
							if($PSN->f("PerfilDos") == $PSNTEMPperfdos->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMPperfdos->f('descripcion'); ?></option><?
						}
					}
					?>
					</select>



            	
				</div>




			<div class="form-group">
            	<label class="form-label" for="idPersonaDos">Persona 2</label>
				
					<select name="idPersonaDos" id="idPersonaDos"  class="form-control">
					<option value="">Elige tu cliente</option>
						<?
					$PSNTEMPperuno = new DBbase_Sql;
					$sql= "SELECT cc, nombres ";
					$sql.=" FROM personal where id_tipo_personal=".$PSN->f("PerfilDos");
                    $sql.=" ORDER BY nombres asc";

					$PSNTEMPperuno->query($sql);
					$num=$PSNTEMPperuno->num_rows();
					if($num > 0)
					{
						while($PSNTEMPperuno->next_record())
						{
							?><option value="<?=$PSNTEMPperuno->f('cc'); ?>" <?
							if($PSN->f("idPersonaDos") == $PSNTEMPperuno->f('cc'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMPperuno->f('nombres'); ?></option><?
						}
					}
					?>
					
					</select>
				</div>
				<label class="form-label" for="idvalPerDos">Valoracion</label>
				
					
					<select name="idvalPerDos" id="idvalPerDos"  class="form-control">
						<option value="">Seleccione la calificacion...</option>
						<?   if($PSN->f('idvalPerDos')==1) { ?>
						<option value="1" selected="selected">Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" >Regular</option>
						<option value="4" >Buena</option>
						<option value="5"  >Excelente</option>
						<? } elseif($PSN->f('idvalPerDos')==2) { ?>
						<option value="1" >Deficiente</option>
						<option value="2" selected="selected">Malo</option>
						<option value="3" >Regular</option>
						<option value="4" >Buena</option>
						<option value="5" >Excelente</option>
						<? } elseif($PSN->f('idvalPerDos')==3) { ?>
						<option value="1" >Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" selected="selected">Regular</option>
						<option value="4" >Buena</option>
						<option value="5" >Excelente</option>
						<? } elseif($PSN->f('idvalPerDos')==4) { ?>
						<option value="1" >Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" >Regular</option>
						<option value="4" selected="selected">Buena</option>
						<option value="5" >Excelente</option>
						<? } else  { ?>
						<option value="1" >Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" >Regular</option>
						<option value="4" >Buena</option>
						<option value="5" selected="selected">Excelente</option>
						<? } ?>




					</select>

			</div>


			<div class="form-group">
			<label class="form-label" for="PerfilTres">Perfil 3</label>
				
					<select name="PerfilTres" id="PerfilTres"  class="form-control">
						<option value="">Seleccione un perfil...</option>
					<?
					$PSNTEMPperfdos = new DBbase_Sql;
					$sql= "SELECT id, descripcion ";
					$sql.=" FROM categorias WHERE idSec=36";
                    $sql.=" ORDER BY descripcion asc";

					$PSNTEMPperfdos->query($sql);
					$num=$PSNTEMPperfdos->num_rows();
					if($num > 0)
					{
						while($PSNTEMPperfdos->next_record())
						{
							?><option value="<?=$PSNTEMPperfdos->f('id'); ?>" <?
							if($PSN->f("PerfilTres") == $PSNTEMPperfdos->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMPperfdos->f('descripcion'); ?></option><?
						}
					}
					?>
					</select>



            	
				</div>

			<div class="form-group">
            	<label class="form-label" for="idPersonaTres">Persona 3</label>
				
					<select name="idPersonaTres" id="idPersonaTres"  class="form-control">
					<option value="">Elige tu cliente</option>
						<?
					$PSNTEMPperuno = new DBbase_Sql;
					$sql= "SELECT cc, nombres ";
					$sql.=" FROM personal where id_tipo_personal=".$PSN->f("PerfilTres");
                    $sql.=" ORDER BY nombres asc";

					$PSNTEMPperuno->query($sql);
					$num=$PSNTEMPperuno->num_rows();
					if($num > 0)
					{
						while($PSNTEMPperuno->next_record())
						{
							?><option value="<?=$PSNTEMPperuno->f('cc'); ?>" <?
							if($PSN->f("idPersonaTres") == $PSNTEMPperuno->f('cc'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMPperuno->f('nombres'); ?></option><?
						}
					}
					?>
					
					</select>
				</div>
				<label class="form-label" for="idvalPerTres">Valoracion</label>
				
					
					<select name="idvalPerTres" id="idvalPerTres"  class="form-control">
						<option value="">Seleccione la calificacion...</option>
						<?   if($PSN->f('idvalPerTres')==1) { ?>
						<option value="1" selected="selected">Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" >Regular</option>
						<option value="4" >Buena</option>
						<option value="5"  >Excelente</option>
						<? } elseif($PSN->f('idvalPerDos')==2) { ?>
						<option value="1" >Deficiente</option>
						<option value="2" selected="selected">Malo</option>
						<option value="3" >Regular</option>
						<option value="4" >Buena</option>
						<option value="5" >Excelente</option>
						<? } elseif($PSN->f('idvalPerDos')==3) { ?>
						<option value="1" >Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" selected="selected">Regular</option>
						<option value="4" >Buena</option>
						<option value="5" >Excelente</option>
						<? } elseif($PSN->f('idvalPerDos')==4) { ?>
						<option value="1" >Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" >Regular</option>
						<option value="4" selected="selected">Buena</option>
						<option value="5" >Excelente</option>
						<? } else  { ?>
						<option value="1" >Deficiente</option>
						<option value="2" >Malo</option>
						<option value="3" >Regular</option>
						<option value="4" >Buena</option>
						<option value="5" selected="selected">Excelente</option>
						<? } ?>




					</select>

			</div>

			</div>

        
            
        <br />
		<div class="row text-center auditoria-actions">
            <input type="hidden" name="funcion" id="funcion" value="" />
            <input type="submit" value="Guardar cambios" class="btn btn-success" /> <a href="index.php?doc=main" class="btn btn-danger">Cerrar</a>
        </div>
                
            
                

            <?
			//if($PSN->f('estado') != 2){
			?>
				<!--<br />
				<center><input type="button" name="button" onclick="generarFormDel()" value="Eliminar Registro" /></center>//-->
                    
            <div class="form-group">
                <h3 class="text-center alert alert-dark">OBJETIVO DEL PROYECTO</h3>
            </div>

			<div class="form-group">
				<iframe src ="int.php?doc=int_seguimientosauditoria&id=<?=soloNumeros($_GET["id"]); ?>#final" name="frameObs" id="frameObs" width="100%" height="300px" frameborder="0" marginheight="0" marginwidth="0"></iframe>
			</div>

			<div class="form-group">
				<input type="button" class="btn btn-success" name="button" onclick="asignarObservacion()" value="+ Seguimiento">
			</div>

			<div class="form-group">
                <h2 class="text-center alert alert-dark">INSERTAR CAMBIOS</h2>
            </div>
                
			<div class="form-group">
				<iframe src ="int.php?doc=int_gestioncambios&id=<?=soloNumeros($_GET["id"]); ?>#final" name="frameObsF" id="frameObsF" width="100%" height="300px" frameborder="0" marginheight="0" marginwidth="0"></iframe>
			</div>

			<div class="form-group">
				<input type="button" class="btn btn-success" name="button" onclick="gestionarCambio()" value="+ Insertar">
			</div>

			


										           
            <div class="form-group">
                <h3 class="text-center alert alert-dark">COSTOS ADICIONALES DEL PROYECTO</h3>
            </div>

			<div class="form-group">
				<iframe src ="int.php?doc=int_gastosauditoria&id=<?=soloNumeros($_GET["id"]); ?>#final" name="frameObsG" id="frameObsG" width="100%" height="300px" frameborder="0" marginheight="0" marginwidth="0"></iframe>
			</div>

			<div class="form-group">
				<input type="button" class="btn btn-success" name="button" onclick="asignarGasto()" value="+ Costo A.">
			</div>
							           
<!--             <div class="form-group">
                <h3 class="text-center alert alert-dark">FACTURACIÓN</H3>
            </div> 
                
			<div class="form-group">
				<iframe src ="int.php?doc=int_facturacionauditoria&id=<?=soloNumeros($_GET["id"]); ?>#final" name="frameObsF" id="frameObsF" width="100%" height="300px" frameborder="0" marginheight="0" marginwidth="0"></iframe>
			</div>

			<div class="form-group">
				<input type="button" class="btn btn-success" name="button" onclick="asignarAbono()" value="+ Abono">
			</div> -->

			






			</div>



            </form>

			<script language="javascript">
				
				function generarForm(){
						if(confirm("Esta accion actualizara el REGISTRO en el sistema, esta seguro que desea continuar?"))
						{
							if(document.getElementById('NombProyecto').value != "")
							{
								document.getElementById('funcion').value = "actualizar";
								//document.getElementById('form1').submit();
								return true;
							}
							else
							{
								alert("La informacion es primordial para brindarle un excelente servicio, por favor digite al menos el campo de NOMBRE");
							}
						}
						return false;
				}

				function regresar()
				{
					window.location.href = "index.php?doc=seguimiento_auditoria";
				}
				
				function asignarObservacion(idd){
					window.open("pop_up.php?doc=pop_seguimientoauditoria&id=<?=soloNumeros($_GET["id"]); ?>", "seguimientoauditoria", "status=1, scrollbars=1, height=600, width=840");
				}

				function asignarAbono(idd){
					window.open("pop_up.php?doc=pop_abonoauditoria&id=<?=soloNumeros($_GET["id"]); ?>", "abonoauditoria", "status=1, scrollbars=1, height=600, width=840");
				}

				function asignarGasto(idd){
					window.open("pop_up.php?doc=pop_gastoauditoria&id=<?=soloNumeros($_GET["id"]); ?>", "gastoauditoria", "status=1, scrollbars=1, height=600, width=840");
				}
				function gestionarCambio(idd){
					window.open("pop_up.php?doc=pop_seguimientocambios&id=<?=soloNumeros($_GET["id"]); ?>", "gastoauditoria", "status=1, scrollbars=1, height=600, width=840");
				}




				function generarFormDel(){
						if(confirm("Esta accion ELIMINARA el REGISTRO en el sistema, esta seguro que desea continuar?"))
						{
							document.getElementById('funcion').value = "eliminar";
							document.getElementById('form1').submit();
						}
				}

				$(function(){
					$("#idCliente").on('change',function(){
						var varidCliente = $("#idCliente").val();
						//document.getElementById('idCliente').value; // selected value
					console.log(varidCliente);
					var url = "busquedaContactoAuditoria.php";
					$.ajax( {
						type:'POST',
						url:url,
						data:'idCliente='+varidCliente,
						success: function( data ) {
										$("#idClienteUno option").remove();
										$("#idClienteUno").append(data);
										$("#idClienteDos option").remove();
										$("#idClienteDos").append(data);

									}
									
									
									
								} );

					}); 
					return false;
					});

					// perfil Uno
					// revisar
					$(function(){
					$("#PerfilUno").on('change',function(){
						var varidCliente = $("#PerfilUno").val();
						//document.getElementById('idCliente').value; // selected value
					// console.log(varidCliente);
					var url = "busquedaPerfilAuditoria.php";
					$.ajax( {
						type:'POST',
						url:url,
						data:'idPerfil='+varidCliente,
						success: function( data ) {
										$("#idPersonaUno option").remove();
										$("#idPersonaUno").append(data);
										

									}
									
									
									
								} );

					});
					return false;
								});

						// Perfil Dos
						

					$(function(){
					$("#PerfilDos").on('change',function(){
						var varidCliente = $("#PerfilDos").val();
						//document.getElementById('idCliente').value; // selected value
					//console.log(varidCliente);
					var url = "busquedaPerfilAuditoria.php";
					$.ajax( {
						type:'POST',
						url:url,
						data:'idPerfil='+varidCliente,
						success: function( data ) {
										$("#idPersonaDos option").remove();
										$("#idPersonaDos").append(data);
										

									}
									
									
									
								} );

						});
						return false;
							});

							// revisar perfilTres

							$(function(){
					$("#PerfilTres").on('change',function(){
						var varidCliente = $("#PerfilTres").val();
						//document.getElementById('idCliente').value; // selected value
					//console.log(varidCliente);
					var url = "busquedaPerfilAuditoria.php";
					$.ajax( {
						type:'POST',
						url:url,
						data:'idPerfil='+varidCliente,
						success: function( data ) {
										$("#idPersonaTres option").remove();
										$("#idPersonaTres").append(data);
										

									}
									
									
									
								} );

						});
						return false;
							});	

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
				//}
				}
			}		
			else
			{
				?><div class="container"></div><div class="form-group">
				<h2><font color="#FF0000">ID Incorrecto. No Existe o no esta autorizado para visualizar la misma.</font></h2></div>
				</div><?
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
		$sqlB= "SELECT ".$tablaConsulta.".*,cliente.nombre,sms_usuarios.nombres,sms_usuariosDos.nombres as nombresCDos,personal.nombres as nombresPUno,personalDos.nombres as nombresPDos,personalTres.nombres as nombresPTres";
		$sqlB.=" FROM ".$tablaConsulta;
		$sqlB.=" LEFT JOIN cliente ON cliente.id = ".$tablaConsulta.".id_prospecto ";
		$sqlB.=" LEFT JOIN sms_usuarios ON sms_usuarios.id = ".$tablaConsulta.".idClienteUno ";
		$sqlB.=" LEFT JOIN sms_usuarios sms_usuariosDos ON sms_usuariosDos.id = ".$tablaConsulta.".idClienteDos ";
		$sqlB.=" LEFT JOIN personal ON personal.cc = ".$tablaConsulta.".idPersonaUno ";
		$sqlB.=" LEFT JOIN personal personalDos ON personalDos.cc = ".$tablaConsulta.".idPersonaDos ";
		$sqlB.=" LEFT JOIN personal personalTres ON personalTres.cc = ".$tablaConsulta.".idPersonaTres ";
		$sqlB.=" WHERE 1 ";
        //
        
       
    
		
		//
		$sqlC= "SELECT count(".$tablaConsulta.".id) as conteo"; // sum(".$tablaConsulta.".valor_prima_neta) as total_neto";
		$sqlC.=" FROM ".$tablaConsulta;
	//	$sqlC.=" LEFT JOIN cliente ON cliente.id = cotizacion.idCliente";
		$sqlC.=" WHERE 1 ";

		
		if(soloNumeros($_GET["idCliente"]) != "" )
		{
			$sql.=" AND ".$tablaConsulta.".id_prospecto   = '".soloNumeros($_GET["idCliente"])."'";
		}

		if(eliminarInvalidos($_GET["estado"]) != "")
		{
			$sql.=" AND ".$tablaConsulta.".estado = '".eliminarInvalidos($_GET["estado"])."'";
		}
		
		if(eliminarInvalidos($_GET["fechainicio"]) != "")
		{
			$sql.=" and ".$tablaConsulta.".fec_inicio >= '".eliminarInvalidos($_GET["fechainicio"])."'";
			$fechaInicial = eliminarInvalidos($_GET["fechainicio"]);
		}

		if(eliminarInvalidos($_GET["fechafinal"]) != "")
		{
			$sql.=" and ".$tablaConsulta.".fec_fin <= '".eliminarInvalidos($_GET["fechafinal"])."'";
			$fechaFinal = eliminarInvalidos($_GET["fechafinal"]);
		}
		
		
		$sqlO =" ORDER BY ".$tablaConsulta.".id DESC";

// Tablero de proyectos con información fundamenal - inicio 	
		if($opc == 2){
			$PSN->query($sqlC.$sql.$sqlO);
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

			$sql.=" GROUP BY proyecto_auditoria.id LIMIT ".$inicio.", ".$registros;;
			$PSN->query($sqlB.$sql);
			$num=$PSN->num_rows();
			?><div class="container">
            <form action="index.php" name="form" id="form" method="get" class="form">
            <input type="hidden" name="doc" value="<?=$webArchivo; ?>" />
            <input type="hidden" name="opc" value="2" />
                
                
                <div class="form-group">
                    <h2 class="text-center well">.FILTROS DE BUSQUEDA - <?=$nombreConsulta; ?>.</h2>
                </div>
                
				<div class="form-group">
            	<label class="form-label" for="idCliente">Prospecto</label>
				
					<select name="idCliente" id="idCliente"  class="form-control">
						<option value="">Seleccione un prospecto...</option>
					<?
					$PSNTEMP = new DBbase_Sql;
					$sql= "SELECT cliente.id, cliente.nombre ";
					$sql.=" FROM cliente WHERE estado != 0";
                    $sql.=" ORDER BY nombre desc";

					$PSNTEMP->query($sql);
					$num=$PSNTEMP->num_rows();
					if($num > 0)
					{
						while($PSNTEMP->next_record())
						{
							?><option value="<?=$PSNTEMP->f('id'); ?>" <?
							if(soloNumeros($_POST["idCliente"]) == $PSNTEMP->f('id'))
							{
								?>selected="selected"<? 
							}
							?>><?=$PSNTEMP->f('nombre'); ?></option><?
						}
					}
					?>
					</select>
				

			</div>

			<div class="form-group">
				<label class="form-label" for="fechainicio">Fecha de inicio</label>
				<input name="fechainicio" type="date" placeholder="AAAA-MM-DD" maxlength="250" value="<?=eliminarInvalidos($_GET['fechainicio']); ?>" class="form-control" />
			</div>

			<div class="form-group">
				<label class="form-label" for="fechafinal">Fecha final</label>
				<input name="fechafinal" type="date" placeholder="AAAA-MM-DD" maxlength="250" value="<?=eliminarInvalidos($_GET['fechafinal']); ?>" class="form-control" />
			</div>
			
			<div class="form-group">
			
			<label class="form-label" for="descripcionproyecto">Estado del proyecto</label>
			<div class="estado-grid">
				<label class="estado-option">
					<input type="radio" name="estado" value="Relajado" <? if(eliminarInvalidos($_GET['estado'])=="Relajado"){ ?>checked<? } ?>>
					<img src="images/consultores/relajado.png" alt="Relajado">
					<span>Relajado</span>
				</label>
				<label class="estado-option">
					<input type="radio" name="estado" value="Caminando" <? if(eliminarInvalidos($_GET['estado'])=="Caminando"){ ?>checked<? } ?>>
					<img src="images/consultores/caminando.png" alt="Caminando">
					<span>Caminando</span>
				</label>
				<label class="estado-option">
					<input type="radio" name="estado" value="Corriendo" <? if(eliminarInvalidos($_GET['estado'])=="Corriendo"){ ?>checked<? } ?>>
					<img src="images/consultores/corriendo.png" alt="Corriendo">
					<span>Corriendo</span>
				</label>
			</div>
			</div>


					
            <div class="row text-center">
                <input type="submit" value="Buscar" class="btn btn-success" />
            </div>

            </form>
			</div>

            <div class="container">
            <div class="row">
                <h2 class="text-center well">.Se encontraron <?=$numTotal; ?> registros.</h2>
            </div>

			<table border="0" cellspacing="0" cellpadding="2"  align="center" class="table table-bordered auditoria-modern-table" style="font-size:12px">
            <thead>
			<tr>
			<!--	<th align="center">No</th> -->
				
				<th style="width: 100px">Prospecto</th>
				<th style="width: 150px">Nombre del Proyecto</th>
				<th>Descripcion</th>
				<th style="width: 75px;white-space:nowrap;">Ejecución</th>
				<th style="width: 80px">Fecha Inicio</th>
				<th style="width: 75px">Fecha Final</th>
				
				
			</tr>
            </thead>
            <tbody><?
				if($num > 0)
				{
					$izq = 1;
					$contador = $inicio+1;
					while($PSN->next_record())
					{
					    
					    $fechaVencimiento = date("Y-m-d", strtotime("+364 days", strtotime($PSN->f('fecha_vigencia_ini'))));
					    $westado_terminado = $PSN->f('idEstadoAuditoria');
						?>
						<tr <? if($westado_terminado==2){ ?>bgcolor="#ffea61"<? } elseif($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>>
						
						<? /*<td><a href="index.php?doc=<?=$webArchivo; ?>&opc=2&id=<?=$PSN->f('id');?>"><?=$PSN->f('id');?></a></td> */ ?>
							<td style="width: 100px"><a href="index.php?doc=<?=$webArchivo; ?>&opc=2&id=<?=$PSN->f('id');?>"><?=$PSN->f('nombre');?></a></td>
							<td style="width: 150px;white-space:wrap;"><a href="index.php?doc=<?=$webArchivo; ?>&opc=2&id=<?=$PSN->f('id');?>"><?=$PSN->f('nomb_proyecto');?></a></td>
							<td><?=$PSN->f('descrip_proyecto');?></td>
							<td style="width: 75px"><?=$PSN->f('estado');?></td>
							<td style="width: 80px"> <?=$PSN->f('fec_inicio');?></td>
							<td style="width: 75px"><?=$PSN->f('fec_fin');?></td>
							
							
						</tr>
						<?


						$contador++;
					}
				}		
				?>  
        </tbody>
        </table>
		</div>

        <br />
        <center>
        <div class="container">
            <ul class="pagination">
                <?
                //
                $paginaActualTxT = "&pagina=".$pagina;
                $_SERVER['REQUEST_URI'] = str_replace($paginaActualTxT,"", $_SERVER['REQUEST_URI']);
                //
                if(($pagina - 1) > 0)
                {
                    echo "<li><a href='".$_SERVER['REQUEST_URI']."&pagina=".($pagina-1)."'>&laquo;</a></li>"; 
                }

                for ($i=1; $i<=$total_paginas; $i++)
                { 
                    if ($pagina == $i)
                    {
                        echo "<li class='active'><a href='".$_SERVER['REQUEST_URI']."&pagina=$i'>$i</a>"; 
                    }
                    else 
                    { 
                        echo "<li><a href='".$_SERVER['REQUEST_URI']."&pagina=$i'>$i</a></li>";
                    } 
                }

                if(($pagina + 1)<=$total_paginas)
                { 
                    echo "<li><a href='".$_SERVER['REQUEST_URI']."&pagina=".($pagina+1)."'>&raquo;</a></li>"; 
                }
                ?>
            </ul>
        </div>
        </center>


			<center><? //excel descarga
			echo "<a href='index.php?doc=seguimiento_auditoria&opc=3&excelX=
						&idCliente=".eliminarInvalidos($_GET["idCliente"])."
						&estado=".eliminarInvalidos($_GET["estado"])."
						&estadoPago=".eliminarInvalidos($_GET["estadoPago"])."
						&categoriaPrincipal=".eliminarInvalidos($_GET["categoriaPrincipal"])."
						&metodoCotizacion=".eliminarInvalidos($_GET["metodoCotizacion"])."
						&tipoContacto=".eliminarInvalidos($_GET["tipoContacto"])."
						&contactoNombre=".eliminarInvalidos($_GET["contactoNombre"])."
						&contactoTelefono=".eliminarInvalidos($_GET["contactoTelefono"])."
						&contactoCelular=".eliminarInvalidos($_GET["contactoCelular"])."
						&contactoEmail=".eliminarInvalidos($_GET["contactoEmail"])."
						&descripcion=".eliminarInvalidos($_GET["descripcion"])."
						&valorCotizado=".eliminarInvalidos($_GET["valorCotizado"])."
						&valorCobrado=".eliminarInvalidos($_GET["valorCobrado"])."
						&valorRecibido=".eliminarInvalidos($_GET["valorRecibido"])."
						&fechaCotizacion=".eliminarInvalidos($_GET["fechaCotizacion"])."
						&fechaFacturacion=".eliminarInvalidos($_GET["fechaFacturacion"])."
						&fechaUltimoPago=".eliminarInvalidos($_GET["fechaUltimoPago"])."
						&excel=1'
						class='btn btn-info'><span class='glyphicon glyphicon-cloud-download'></span> DESCARGAR PARA EXCEL</a> ";    
            ?></center><?
		}

// Tablero de proyectos con información fundamenal - final


		else if(isset($_GET["excel"]))
		{
			//
			$sqlB= "SELECT ".$tablaConsulta.".id,".$tablaConsulta.".nomb_proyecto,".$tablaConsulta.".descrip_proyecto,".$tablaConsulta.".fec_inicio,".$tablaConsulta.".fec_fin,".$tablaConsulta.".estado,
			cliente.nombre,sms_usuarios.nombres,sms_usuariosDos.nombres as nombresCDos,personal.nombres as nombresPUno,personalDos.nombres as nombresPDos,personalTres.nombres as nombresPTres";
		$sqlB.=" FROM ".$tablaConsulta;
		$sqlB.=" LEFT JOIN cliente ON cliente.id = ".$tablaConsulta.".id_prospecto ";
		$sqlB.=" LEFT JOIN sms_usuarios ON sms_usuarios.id = ".$tablaConsulta.".idClienteUno ";
		$sqlB.=" LEFT JOIN sms_usuarios sms_usuariosDos ON sms_usuariosDos.id = ".$tablaConsulta.".idClienteDos ";
		$sqlB.=" LEFT JOIN personal ON personal.cc = ".$tablaConsulta.".idPersonaUno ";
		$sqlB.=" LEFT JOIN personal personalDos ON personalDos.cc = ".$tablaConsulta.".idPersonaDos ";
		$sqlB.=" LEFT JOIN personal personalTres ON personalTres.cc = ".$tablaConsulta.".idPersonaTres ";
		$sqlB.=" WHERE 1 ";
			
			$sql .= " GROUP BY ".$tablaConsulta.".id";
			$PSN->query($sqlB.$sql);
			$num=$PSN->num_rows();
			?><table class="auditoria-modern-table">
			<tr>
				<th colspan="15">Se han encontrado <?=$total_registros; ?> registros.</th>
			</tr>
			<tr>
				<th>No</th>
				<th>Prospecto</th>
				<th>Nombre del Proyecto</th>
				<th>Descripcion</th>
				<th>Fecha Inicio</th>
				<th>Fecha Final</th>
				<th>Estado</th>
				<th>Cliente 1</th>
				<th>Cliente 2</th>
				<th>Persona 1</th>
				<th>Persona 2</th>
				<th>Persona 3</th>				
				
			</tr><?
				if($num > 0)
				{
					$izq = 1;
					$contador = $inicio+1;
					while($PSN->next_record())
					{
					$fechaVencimiento = date("Y-m-d", strtotime("+364 days", strtotime($PSN->f('fecha_vigencia_ini'))));
						?>
						<tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>>
							<td><?=$contador; ?></td>
							<td><?=$PSN->f('nombre');?></td>
							<td><?=$PSN->f('nomb_proyecto');?></td>
							<td><?=$PSN->f('descrip_proyecto');?></td>
							<td><?=$PSN->f('fec_inicio');?></td>
							<td><?=$PSN->f('fec_fin');?></td>
							<td><?=$PSN->f('estado');?></td>
							<td><?=$PSN->f('nombres');?></td>
							<td><?=$PSN->f('nombresCDos');?></td>
							<td><?=$PSN->f('nombresPUno');?></td>
							<td><?=$PSN->f('nombresPDos');?></td>
							<td><?=$PSN->f('nombresPTres');?></td>
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
			<p align="left">
			<?			
		}
	}
}

echo '</div>';
?>