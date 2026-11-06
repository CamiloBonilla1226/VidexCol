<?php
ini_set('max_execution_time', 600); //300 seconds = 5 minutes
//
if($_SESSION["perfil"] != 1 && $_SESSION["perfil"] != 2)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

if(!isset($_GET["opc"]))
{
	$opc = 1;
}
else
{
	$opc = eliminarInvalidos($_GET["opc"]);
}

$PSN = new DBbase_Sql;
if($opc == 1)
{
	//
	//
	$idCampana = soloNumeros($_POST["idCampana"]);
	if(isset($_POST["mensajecorreo"]))
	{
		$mensajecorreo = eliminarInvalidos($_POST["mensajecorreo"]);
		$asuntocorreo = eliminarInvalidos($_POST["asuntocorreo"]);
		$idConfig = soloNumeros($_POST["idConfig"]);
		//
		if(isset($_POST['listaasociar']))
		{
			$listas = implode(",", $_POST['listaasociar']);
		}
		//
		if($mensajecorreo != "" && $asuntocorreo != "" && $idConfig != "" && $idConfig != "0")
		{
			//
			$array_usr_sent = array();
			//
			$sql= "SELECT * ";
			$sql.=" FROM mail_config ";
			$sql.=" WHERE id = '".$idConfig."'";
			//
			$PSN->query($sql);
			if($PSN->num_rows() == 0)
			{
				//Nada
			}
			else
			{
				if($PSN->next_record())
				{
					echo "<strong>El script proceder&aacute; a enviar los correos, tenga en cuenta que aunque esta pagina pueda dejar de funcionar el servidor continuara enviando los mensajes, si esta enviando cientos de correos esto puede tomar desde algunos minutos hasta un par de horas, puede salir y/o cerrar esta ventana con tranquilidad el servidor ya recibio su solicitud y la esta procesando.</strong>";
					//
					$audit_usuario = $_SESSION["id"];
					$audit_ip = $_SERVER['REMOTE_ADDR'];
					//
					$body = str_replace('/scripts/', 'https://videx.sms-app.co/scripts/', $_REQUEST["mensajecorreo"]);
					//
					//$mail->isSMTP();
					$mail->isMail();
					$mail->IsHTML(true);
					//
					//
					
					/*$mail->Host = 'mail.jocadimama.com'; //s2000.websitewelcome.com';//'mail.videoexpress.org'; //s2000.websitewelcome.com
					$mail->Username = 'comunicaciones@jocadimama.com';
					$mail->Password = '807780_lrmXd';
					$mail->Port = 465;
					$remite_correo = "comunicaciones@jocadimama.com";
					$remite_nombre = "Jorge Pruebas Jocadimama";*/
					
					//
					//
					//
              		//$mail->SMTPDebug = 2;
					//
					$mail->Host = $PSN->f('host');
					$mail->Username = $PSN->f('usuario');
					$mail->Password = $PSN->f('password');
					$mail->Port = $PSN->f('puerto');

					$remite_correo = $PSN->f('correo');
					$remite_nombre = $PSN->f('nombre');
					//
					//$mail->Host = 's2000.websitewelcome.com'; //
					//$mail->Port = 465;
					//$mail->SMTPSecure = "tls";
					$mail->SMTPAuth = true;
					$mail->SMTPKeepAlive = true; // SMTP connection will not close after each email sent, reduces SMTP overhead
					$mail->setFrom($remite_correo, $remite_nombre);
					$mail->addReplyTo($remite_correo, $remite_nombre);
					$mail->Subject = eliminarInvalidos($_REQUEST["asuntocorreo"]);
					//
					if($_FILES['archivos']['name'][0] != ""){

						$file_ary = array();
						$file_count = count($_FILES['archivos']['name']);
						$file_keys = array_keys($_FILES['archivos']);

						for ($i=0; $i<$file_count; $i++) {
							foreach ($file_keys as $key) {
								$file_ary[$i][$key] = $_FILES['archivos'][$key][$i];
							}
						}

						if($file_count > 0) {
							foreach ($file_ary as $file) {
								//do your upload stuff here
								$ruta_temporal = $file['tmp_name'];
								$nombre_archivo = $file['name'];
								$tipo_archivo = $file['type'];
								$tamano_archivo = $file['size'];
								//You'll need to alter this to match your database
								$mail->AddAttachment($ruta_temporal, $nombre_archivo); //Assumes the image data is stored in the 
							}
						}				
					}


					//ENVIO A LISTAS
					if(count($_POST['listaasociar']) > 0 && trim($listas) != "" && $listas != ",")
					{
						$sql= "SELECT sms_usuarios.id, sms_usuarios.nombres, sms_usuarios.email ";
						$sql.=" FROM sms_grupos, sms_asociacion, sms_usuarios";
						$sql.=" WHERE sms_grupos.id IN (".$listas.") AND sms_asociacion.id_grupo = sms_grupos.id AND sms_usuarios.id = sms_asociacion.id_usuario  AND sms_usuarios.email != '' ORDER BY sms_usuarios.id ASC";

						$PSN->query($sql);
						$num=$PSN->num_rows();
						if($num > 0)
						{
							$PSNT = new DBbase_Sql;
							while($PSN->next_record())
							{
								if(!in_array($PSN->f('email'), $array_usr_sent))
								{
									$mail_email = $PSN->f('email');
									$mail_nombres = $PSN->f('nombres');
									
									if(filter_var($mail_email, FILTER_VALIDATE_EMAIL)) {
										//
										//
										$msgActual = $body."<img src='https://videx.sms-app.co/chefs/mail/img.php?usr=".$PSN->f('id')."&cam=".$idCampana."&tmp=".strtotime("now")."' />";

										$mail->msgHTML($msgActual);
										//If you generate a different body for each recipient (e.g. you're using a templating system),
										$mail->AltBody = 'Tu correo no admite mensajes en formato HTML. :(';
										//
										$mail->addAddress($mail_email, $mail_nombres);
										if(!$mail->send())
										{
											echo "<br />Error de correo (" . str_replace("@", "&#64;", $mail_email) . ') ' . $mail->ErrorInfo . '<br />';
											//break; //Abandon sending
											//
											//Guardamos el log de errores.
											$sql = "INSERT INTO mail_historico (idCampana, error, fecha, audit_usuario, audit_fecha, audit_ip) ";
											$sql .= " VALUES('".$idCampana."', 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";
											$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";
											$PSNT->query($sql);
											//
											$contadorError++;								
										}
										else 
										{
											$array_usr_sent[] = $mail_email;
											//echo "<br />Mensaje enviado a :" . $PSN->f('id') . " - ". $mail_nombres . ' (' . str_replace("@", "&#64;", $mail_email) . ')<br />';
											//
											//Guardamos el log de envio.
											$sql = "INSERT INTO mail_historico (idCampana, correos, fecha, audit_usuario, audit_fecha, audit_ip) ";
											$sql .= " VALUES('".$idCampana."', 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";
											$sql .= " ON DUPLICATE KEY UPDATE correos = (correos+1)";
											$PSNT->query($sql);
											//
											$contadorOk++;
										}
										$mail->ClearAllRecipients();//clearAddresses();	
									}
									else
									{
										echo "<br />Correo INVALIDO (" . str_replace("@", "&#64;", $mail_email) . ') ' . $mail->ErrorInfo . '<br />';
									}
								}
								else
								{
									echo "<br />Correo DUPLICADO (" . str_replace("@", "&#64;", $PSN->f('email')) . ') ' . $mail->ErrorInfo . '<br />';					
								}
							}
						}
					}//Fin Envio a listas.
					/*
					*
					*	ENVIO A CORREOS MANUALES
					*
					*/
					if(isset($_POST['mensajeusumanual']) && trim($_POST['mensajeusumanual']) != "")
					{
						$sql= "SELECT id, nombres, email ";
						$sql.=" FROM sms_usuarios";
						$sql.=" WHERE id IN (".htmlspecialchars($_POST['mensajeusumanual']).") AND email != ''";

						$PSN->query($sql);
						$num=$PSN->num_rows();
						if($num > 0)
						{
							$PSNT = new DBbase_Sql;
							while($PSN->next_record())
							{
								if(!in_array($PSN->f('email'), $array_usr_sent))
								{
									$mail_email = $PSN->f('email');
									$mail_nombres = $PSN->f('nombres');
									if(filter_var($mail_email, FILTER_VALIDATE_EMAIL))
									{
										//
										//
										$msgActual = $body."<img src='https://videx.sms-app.co/mail/img.php?usr=".$PSN->f('id')."&cam=".$idCampana."&tmp=".strtotime("now")."' />";
										//
										$mail->msgHTML($msgActual);
										//If you generate a different body for each recipient (e.g. you're using a templating system),
										$mail->AltBody = 'Tu correo no admite mensajes en formato HTML. :(';
										//
										$mail->addAddress($mail_email, $mail_nombres);
										//

										if (!$mail->send()) {
											echo "<br />Error de correo (" . str_replace("@", "&#64;", $mail_email) . ') ' . $mail->ErrorInfo . '<br />';
											//break; //Abandon sending
											//
											//Guardamos el log de errores.
											$sql = "INSERT INTO mail_historico (idCampana, error, fecha, audit_usuario, audit_fecha, audit_ip) ";
											$sql .= " VALUES('".$idCampana."', 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";
											$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";
											$PSNT->query($sql);
											//
											$contadorError++;								
										} else {
											$array_usr_sent[] = $mail_email;									
											//echo "<br />Mensaje enviado a :" . $PSN->f('id') . " - " . $mail_nombres . ' (' . str_replace("@", "&#64;", $mail_email) . ')<br />';
											//
											//Guardamos el log de envio.
											$sql = "INSERT INTO mail_historico (idCampana, correos, fecha, audit_usuario, audit_fecha, audit_ip) ";
											$sql .= " VALUES('".$idCampana."', 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";
											$sql .= " ON DUPLICATE KEY UPDATE correos = (correos+1)";
											$PSNT->query($sql);
											//
											$contadorOk++;
										}
										$mail->ClearAllRecipients();//clearAddresses();	
									}
									else
									{
										echo "<br />Correo INVALIDO (" . str_replace("@", "&#64;", $mail_email) . ') ' . $mail->ErrorInfo . '<br />';
									}
								}
							}
						}
					}//Fin correos manuales
					
					/*
					*
					*	ENVIO A CLIENTES
					*
					*/
					if(isset($_POST['idCliente']) && trim($_POST['idCliente']) != "" && soloNumeros($_POST['idCliente']) != "" && soloNumeros($_POST['idCliente']) != "0")
					{
						$sql= "SELECT id, nombre, email ";
						$sql.=" FROM cliente";
						if($_POST['idCliente'] == -99){
							$sql.=" WHERE email != ''";
						}
						else{
							$sql.=" WHERE id = '".soloNumeros($_POST['idCliente'])."' AND email != ''";
						}
						$PSN->query($sql);
						if($PSN->num_rows() > 0)
						{
							if($PSN->next_record())
							{
								if(!in_array($PSN->f('email'), $array_usr_sent))
								{
									$mail_email = $PSN->f('email');
									$mail_nombres = $PSN->f('nombre');
									if(filter_var($mail_email, FILTER_VALIDATE_EMAIL))
									{
										//
										//
										$msgActual = $body."<img src='https://videx.sms-app.co/mail/img.php?cli=".$PSN->f('id')."&cam=".$idCampana."&tmp=".strtotime("now")."' />";
										//
										$mail->msgHTML($msgActual);
										//If you generate a different body for each recipient (e.g. you're using a templating system),
										$mail->AltBody = 'Tu correo no admite mensajes en formato HTML. :(';
										//
										$mail->addAddress($mail_email, $mail_nombres);
										//

										if (!$mail->send()) {
											echo "<br />Error de correo CLIENTE: " . $PSN->f('id') . " - " . $mail_nombres . " (" . str_replace("@", "&#64;", $mail_email) . ') ' . $mail->ErrorInfo . '<br />';
											//break; //Abandon sending
											//
											//Guardamos el log de errores.
											$sql = "INSERT INTO mail_historico (idCampana, error, fecha, audit_usuario, audit_fecha, audit_ip) ";
											$sql .= " VALUES('".$idCampana."', 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";
											$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";
											$PSNT->query($sql);
											//
											$contadorError++;								
										} else {
											$array_usr_sent[] = $mail_email;
											//
											//echo "<br />Mensaje enviado a CLIENTE: " . $PSN->f('id') . " - " . $mail_nombres . ' (' . str_replace("@", "&#64;", $mail_email) . ')<br />';
											//
											//Guardamos el log de envio.
											$sql = "INSERT INTO mail_historico (idCampana, correos, fecha, audit_usuario, audit_fecha, audit_ip) ";
											$sql .= " VALUES('".$idCampana."', 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";
											$sql .= " ON DUPLICATE KEY UPDATE correos = (correos+1)";
											$PSNT->query($sql);
											//
											$contadorOk++;
										}
										$mail->ClearAllRecipients();//clearAddresses();	
									}
									else
									{
										echo "<br />Correo INVALIDO (" . str_replace("@", "&#64;", $mail_email) . ') ' . $mail->ErrorInfo . '<br />';
									}
								}
							}
						}
						$sql= "SELECT id, nombres, email ";
						$sql.=" FROM sms_usuarios";
						if($_POST['idCliente'] == -99){
							$sql.=" WHERE idCliente != 0 AND email != ''";
						}
						else{
							$sql.=" WHERE idCliente = '".soloNumeros($_POST['idCliente'])."' AND email != ''";
						}

						$PSN->query($sql);
						$num=$PSN->num_rows();
						if($num > 0)
						{
							$PSNT = new DBbase_Sql;
							while($PSN->next_record())
							{
								if(!in_array($PSN->f('email'), $array_usr_sent))
								{
									$mail_email = $PSN->f('email');
									$mail_nombres = $PSN->f('nombres');
									if(filter_var($mail_email, FILTER_VALIDATE_EMAIL))
									{
										//
										//
										$msgActual = $body."<img src='https://videx.sms-app.co/mail/img.php?usr=".$PSN->f('id')."&cam=".$idCampana."&tmp=".strtotime("now")."' />";
										//
										$mail->msgHTML($msgActual);
										//If you generate a different body for each recipient (e.g. you're using a templating system),
										$mail->AltBody = 'Tu correo no admite mensajes en formato HTML. :(';
										//
										$mail->addAddress($mail_email, $mail_nombres);
										//

										if (!$mail->send()) {
											echo "<br />Error de correo (" . str_replace("@", "&#64;", $mail_email) . ') ' . $mail->ErrorInfo . '<br />';
											//break; //Abandon sending
											//
											//Guardamos el log de errores.
											$sql = "INSERT INTO mail_historico (idCampana, error, fecha, audit_usuario, audit_fecha, audit_ip) ";
											$sql .= " VALUES('".$idCampana."', 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";
											$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";
											$PSNT->query($sql);
											//
											$contadorError++;								
										} else {
											$array_usr_sent[] = $mail_email;
											//
											//echo "<br />Mensaje enviado a :" . $PSN->f('id') . " - " . $mail_nombres . ' (' . str_replace("@", "&#64;", $mail_email) . ')<br />';
											//
											//Guardamos el log de envio.
											$sql = "INSERT INTO mail_historico (idCampana, correos, fecha, audit_usuario, audit_fecha, audit_ip) ";
											$sql .= " VALUES('".$idCampana."', 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";
											$sql .= " ON DUPLICATE KEY UPDATE correos = (correos+1)";
											$PSNT->query($sql);
											//
											$contadorOk++;
										}
										$mail->ClearAllRecipients();//clearAddresses();	
									}
									else
									{
										echo "<br />Correo INVALIDO (" . str_replace("@", "&#64;", $mail_email) . ') ' . $mail->ErrorInfo . '<br />';
									}									
								}
							}
						}
					}//Fin correos clientes					
				}
			}
		}
		
		if($contadorOk > 0)
		{
			?><center><h1>.MENSAJES ENVIADOS CON EXITO: <u><?=$contadorOk; ?>.</h1></center><?
		}
		if($contadorError > 0)
		{
			?><center><h1>.MENSAJES EN ERROR: <?=$contadorError; ?>.</h1></center><?
		}
	}

	echo '<script type="text/javascript" src="scripts/jquery-1.7.1.min.js"></script>'."\n";
	echo '<script type="text/javascript" src="scripts/jquery-ui/js/jquery-1.7.1.min.js"></script>'."\n";
	echo '<script type="text/javascript" src="scripts/jquery-ui/js/jquery-ui-1.8.17.custom.min.js"></script>'."\n";
	echo '<script type="text/javascript" src="scripts/jquery.form.js"></script>'."\n";
	echo '<script type="text/javascript" src="scripts/jquery.validate.js"></script>'."\n";
	echo '<script type="text/javascript" src="scripts/jquery.validate.additional-methods.js"></script>'."\n";
	echo '<script type="text/javascript" src="scripts/jquery.tokeninput.js"></script>'."\n";
	echo '<script type="text/javascript" language="javascript" src="scripts/jquery.corner.js"></script>'."\n";
	echo '<link href="scripts/token-input.css" rel="stylesheet" type="text/css">';
	echo '<script type="text/javascript" src="scripts/ckeditor/ckeditor.js"></script>'."\n";

	?><div class="container">
		<div class="row">
			<h2>.ENVIO DE CORREOS.</h2>
		</div>
	</div>
		
		<div class="container">
			<div class="row" style="border-bottom: 3px #939393 solid">
				<button class="tablink" onclick="openTab('tab1', this, '#3498DB')" id="defaultOpen">Datos generales</button>
				<button class="tablink" onclick="openTab('tab2', this, '#3498DB')" id="defaultOpen">Grupos para envio</button>
				<button class="tablink" onclick="openTab('tab3', this, '#3498DB')" id="defaultOpen">Contactos para envio</button>
				<button class="tablink" onclick="openTab('tab4', this, '#3498DB')" id="defaultOpen">Cliente para envio</button>
			</div>
		</div>

		<form action="index.php?doc=mail_envio&opc=1" method="post" id="envio_masivo_mail" name="envio_masivo_mail"  enctype="multipart/form-data">

		<div id="tab1" class="tabcontent">
		<div id="container">
		<div class="row">
			<div class="col-25">
			<label for="idCampana">Campa&ntilde;a</label>
			</div>
			<div class="col-75">
				<select name="idCampana">
					<option value="">Ninguna</option>
				<?
				$PSNTEMP = new DBbase_Sql;
				$sql= "SELECT id, nombre ";
					$sql.=" FROM campana ";
				$sql.=" WHERE fechaInicial <= '".date("Y-m-d")."' and fechaFinal >= '".date("Y-m-d")."'";
				$sql.=" ORDER BY nombre asc ";

				$PSNTEMP->query($sql);
				$num=$PSNTEMP->num_rows();
				if($num > 0)
				{
					while($PSNTEMP->next_record())
					{
						?><option value="<?=$PSNTEMP->f('id'); ?>" <?
						if(soloNumeros($_POST["idCampana"]) == $PSNTEMP->f('id'))
						{
							?>selected="selected"<? 
						}
						?>><?=$PSNTEMP->f('nombre'); ?></option><?
					}
				}
				?>
				</select>
			</div>
		</div>


		<div class="row">
			<div class="col-25">
			<label for="idConfig">Cuenta de remitente</label>
			</div>
			<div class="col-75">
				<select name="idConfig">
				<?
				$PSNTEMP = new DBbase_Sql;
				$sql= "SELECT id, nombre, defecto ";
					$sql.=" FROM mail_config ";
				$sql.=" ORDER BY nombre asc ";

				$PSNTEMP->query($sql);
				$num=$PSNTEMP->num_rows();
				if($num > 0)
				{
					while($PSNTEMP->next_record())
					{
						?><option value="<?=$PSNTEMP->f('id'); ?>" <?
						if(!isset($_POST["idConfig"]) && $PSNTEMP->f('defecto') == 1)
						{
							?>selected="selected"<? 							
						}
						else if(soloNumeros($_POST["idConfig"]) == $PSNTEMP->f('id'))
						{
							?>selected="selected"<? 
						}
						?>><?=$PSNTEMP->f('nombre'); ?></option><?
					}
				}
				?>
				</select>
			</div>
		</div>

		<div class="row">
			<div class="col-25">
				<label for="asuntocorreo">Asunto</label>
			</div>
			<div class="col-75">
				<input type="text" name="asuntocorreo" id="asuntocorreo" placeholder="Asunto del correo" />
			</div>
		</div>

		<div class="row">
			<div class="col-25">
				<label for="mensajecorreo">Mensaje</label>
			</div>
			<div class="col-75">
				<textarea name="mensajecorreo" id="mensajecorreo" cols="40" rows="6" class="ckeditor"></textarea>
			</div>
		</div>

		<div class="row">
			<div class="col-25">
				<label for="asuntocorreo">Archivos adjuntos</label>
			</div>
			<div class="col-75">
				<input type="file" name="archivos[]" id="archivos" class="inputfile" data-multiple-caption="{count} archivos seleccionados" multiple>
				<label for="archivos"><span><img src="images/outbox.png" height="26px" /> Escoger archivo(s) para adjuntar</span></label>
			</div>
		</div>
		</div>
		</div>

		<div id="tab2" class="tabcontent">
		<div id="container">
		<div class="row">
			<h2>Grupos</h2>
		</div>

		<div class="row">
		<?
		$sql= "SELECT sms_grupos.* ";
		$sql.=" FROM sms_grupos ";
		$sql.=" ORDER BY nombre asc ";

		$PSN->query($sql);
		$num=$PSN->num_rows();
		if($num > 0)
		{
			while($PSN->next_record())
			{
				?><div class="col-50">
					<input type="checkbox" name="listaasociar[]" value="<?=$PSN->f('id'); ?>"><?=$PSN->f('nombre'); ?>
				</div><?
			}
		}
		?>	
		</div>
		</div>
		</div>

		<div id="tab3" class="tabcontent">
		<div id="container">
		<div class="row">
			<label for="mensajeusumanual">Usuarios Individuales:</label>
		</div>
		<div class="row">
			<input type="text" id="mensajeusumanual" name="mensajeusumanual" />
		</div>
		</div>
		</div>


		<div id="tab4" class="tabcontent">
		<div id="container">
			<div class="row">
				<div class="col-25">
					<label for="idCliente">Cliente para envio</label>
				</div>
				<div class="col-75">
					<select name="idCliente">
						<option value="">Sin seleccionar</option>
						<option value="-99">TODOS LOS CLIENTES (OJO!)</option>
					<?
					$PSN = new DBbase_Sql;
					$sql= "SELECT cliente.id, cliente.nombre ";
						$sql.=" FROM cliente ";
					$sql.=" ORDER BY nombre asc ";

					$PSN->query($sql);
					$num=$PSN->num_rows();
					if($num > 0)
					{
						while($PSN->next_record())
						{
							?><option value="<?=$PSN->f('id'); ?>" <?
							/*if(soloNumeros($_POST["idCliente"]) == $PSN->f('id'))
							{
								?>selected="selected"<? 
							}*/
							?>><?=$PSN->f('nombre'); ?></option><?
						}
					}
					?>
					</select>
				</div>
				</div>
			</div>
			</div>		
			
			<div class="container">
				<div class="row">
					<input type="submit" name="button" value="Enviar Mensaje" />
				</div>
			</div>
			</form>
			<script type="text/javascript">
				function openTab(nomTab,elmnt,color)
				{
					var i, tabcontent, tablinks;
					tabcontent = document.getElementsByClassName("tabcontent");
					for (i = 0; i < tabcontent.length; i++) {
						tabcontent[i].style.display = "none";
					}
					tablinks = document.getElementsByClassName("tablink");
					for (i = 0; i < tablinks.length; i++) {
						tablinks[i].style.backgroundColor = "";
					}
					document.getElementById(nomTab).style.display = "block";
					elmnt.style.backgroundColor = color;
				}

				$(document).ready(function() {
					$("#mensajeusumanual").tokenInput("busquedaE.php");

				});

				var inputs = document.querySelectorAll( '.inputfile' );
				Array.prototype.forEach.call( inputs, function( input )
				{
					var label	 = input.nextElementSibling,
						labelVal = label.innerHTML;

					input.addEventListener( 'change', function( e )
					{
						var fileName = '';
						if( this.files && this.files.length > 1 )
							fileName = ( this.getAttribute( 'data-multiple-caption' ) || '' ).replace( '{count}', this.files.length );
						else
							fileName = e.target.value.split( '\\' ).pop();

						if( fileName )
							label.querySelector( 'span' ).innerHTML = fileName;
						else
							label.innerHTML = labelVal;
					});
				});

				window.onload = function(){
					document.getElementById("defaultOpen").click();
				}				
		</script>

	<?
}
else
{
	$registros = 50;
	$pagina = $_GET["pagina"];
	if (!$pagina) { 
		$inicio = 0; 
		$pagina = 1; 
	} 
	else
	{ 
		$inicio = ($pagina - 1) * $registros; 
	}

	$PSN = new DBbase_Sql;
	$saldo_actual = 0;
	$sql= "SELECT SUM(mensajes) as mensajes ";

	$sql.=" FROM sms_historico";
	$PSN->query($sql);
	$num=$PSN->num_rows();
	if($num > 0)
	{
		if($PSN->next_record())
		{
			$saldo_actual = $PSN->f('mensajes');
		}
	}
	/*
	*
	*
	*/
	$sql= "SELECT mail_historico.*, usuario.nombre ";
	$sql.=" FROM mail_historico, usuario";
	//
	$sql.=" WHERE usuario.id = mail_historico.audit_usuario ";
	$sql.=" ORDER BY audit_fecha DESC";
	$PSN->query($sql);
	$num=$PSN->num_rows();

	$total_registros = $num;
	$total_paginas = ceil($total_registros / $registros); 

	$sql= "SELECT mail_historico.*, usuario.nombre, campana.nombre as nombrecam ";
	$sql.=" FROM usuario, mail_historico";
	$sql.=" LEFT JOIN campana ON campana.id = mail_historico.idCampana";
	//
	$sql.=" WHERE usuario.id = mail_historico.audit_usuario ";
	$sql.=" ORDER BY audit_fecha DESC LIMIT ".$inicio.", ".$registros;;
	$PSN->query($sql);
	$num=$PSN->num_rows();
	

	?><div class="container">
       		<div class="row">
				<h2>.HISTORICO EMAIL.</h2>
			</div>

	
		<div class="row">
			<table width="90%" border="0" align="center" class="historico">
				<tr>
				  <th align="center">No.</th>
				  <th align="center">Campa&nacute;a</th>
				  <th align="center">Fecha</th>
				  <th align="center">Correos Enviados</th>
				  <th align="center">Correos en Error</th>
				  <th align="center">Usuario</th>
				</tr><?
		if($num > 0)
		{
			$izq = 1;
			$contador = 1;
			while($PSN->next_record())
			{
				$tipo = $PSN->f('tipo');
				?>
				<tr <? if($contador%2==0){ ?>bgcolor="#EEEEEE"<? } ?>>
                    <td><?=$contador; ?></td>
                    <td><?=$PSN->f('nombrecam');?></td>
                    <td><?=$PSN->f('fecha');?></td>
                    <td style="text-align:center"><?=$PSN->f('correos'); ?></td>
                    <td style="text-align:center"><?=$PSN->f('error');?></td>
                    <td><?=$PSN->f('nombre');?></td>
                </tr>
				<?
				$contador++;
			}
		}		
		else
		{
			?><tr>
			  <td colspan="10" align="center"><h2>.No hay historico aún.</h2></td>
			</tr><?
		}	
		?>
	</table>
	</div>
	<div class="row">
		<?
		if(($pagina - 1) > 0)
		{
			echo "<a href='".$_SERVER['REQUEST_URI']."&pagina=".($pagina-1)."' class='paginacion'>< Anterior</a> "; 
		}

		for ($i=1; $i<=$total_paginas; $i++)
		{ 
			if ($pagina == $i)
			{
				echo "<b>".$pagina."</b> "; 
			}
			else 
			{ 
				echo "<a href='".$_SERVER['REQUEST_URI']."&pagina=$i' class='paginacion'>$i</a> "; 
			} 
		}
		if(($pagina + 1)<=$total_paginas)
		{ 
			echo " <a href='".$_SERVER['REQUEST_URI']."&pagina=".($pagina+1)."' class='paginacion'>Siguiente ></a>"; 
		}
		?></div>
	</div><?
}
?>