<?php
ini_set('max_execution_time', 600); //300 seconds = 5 minutes
//
if($_SESSION["perfil"] != 1 && $_SESSION["perfil"] != 2 && $_SESSION["perfil"] != 161 && $_SESSION["perfil"] != 162 && $_SESSION["perfil"] != 163)
{
	die("<h1>No esta autorizado para ver esta informaci&oacute;n</h1>");
}

if(!isset($_GET["opc"]))
{
	$opc = 1;
}
else
{
	$opc = soloNumeros($_GET["opc"]);
}

$PSN = new DBbase_Sql;
$mailEnvioAssetVersion = '20260420-0100';

// Helper: registrar en mail_audit desde opc=1 (sin closure)
function mq_insertAuditStatic($estado, $correo, $nombre, $idCampana, $remitente, $asunto, $razon, $idUsuario) {
    $db = new DBbase_Sql;
    $correo    = addslashes(substr(trim((string)$correo),    0, 255));
    $nombre    = addslashes(substr(trim((string)$nombre),    0, 255));
    $remitente = addslashes(substr(trim((string)$remitente), 0, 255));
    $asunto    = addslashes(substr(trim((string)$asunto),    0, 500));
    $razon     = addslashes(substr(trim((string)$razon),     0, 1000));
    $db->query(
        "INSERT INTO mail_audit (idCampana,correo,nombre,estado,razon,asunto,remitente,fecha,idUsuario,retry_count) VALUES ('" .
        (int)$idCampana . "','$correo','$nombre','$estado','$razon','$asunto','$remitente',NOW(),'" . (int)$idUsuario . "',0)"
    );
}
echo '<link href="mail-envio.css?v=' . $mailEnvioAssetVersion . '" rel="stylesheet" type="text/css">';
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
					//
					$audit_usuario = $_SESSION["id"];
					$audit_ip = $_SERVER['REMOTE_ADDR'];
					//
					// URL dinamica para reemplazar rutas de imagenes en el cuerpo del correo
					$app_url = rtrim(
					    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
					    . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']), '/');
					$body = str_replace('/scripts/', $app_url . '/scripts/', $_REQUEST["mensajecorreo"]);
					//
					$host     = $PSN->f('host');
					$username = $PSN->f('usuario');
					$password = $PSN->f('password');
					$port     = $PSN->f('puerto');
					// Leer encriptacion de la configuracion SMTP (ssl o tls)
					$encriptacion = $PSN->f('encriptacion');
					if (!in_array($encriptacion, ['ssl', 'tls'])) {
					    $encriptacion = ($port == 587) ? 'tls' : 'ssl';
					}
					//
					$remite_correo = $PSN->f('correo');
					$remite_nombre = $PSN->f('nombre');
					$subject = eliminarInvalidos($_REQUEST["asuntocorreo"]);
					//
					//
					$temp_attachment_ruta = array();
					$temp_attachment_nombre = array();
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
								$nombre_archivo = basename($file['name']);
								$tipo_archivo = $file['type'];
								$tamano_archivo = $file['size'];
								//You'll need to alter this to match your database
        						if(move_uploaded_file($ruta_temporal, "temporales/".$nombre_archivo)){
									$temp_attachment_ruta[] = "temporales/".$nombre_archivo;
									$temp_attachment_nombre[] = $nombre_archivo;
								}
							}
						}				
					}
					//
					if(count($temp_attachment_ruta) > 0 && count($temp_attachment_nombre) > 0){
						$attachment_ruta = implode(",", $temp_attachment_ruta);
						$attachment_nombre = implode(",", $temp_attachment_nombre);
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
									$queue_idUsuario = $PSN->f('id');
									$mail_email = $PSN->f('email');
									$mail_nombres = $PSN->f('nombres');
									
									// Verificar lista negra antes de encolar
									$blChk = new DBbase_Sql;
									$blChk->query("SELECT id FROM mail_blacklist WHERE correo='" . addslashes($mail_email) . "'");
									if($blChk->num_rows() > 0) {
										$mensajes_error .= "\nCorreo en LISTA NEGRA (" . str_replace("@", "&#64;", $mail_email) . ') NO fue a&ntilde;adido a la cola';
										mq_insertAuditStatic('rebote', $mail_email, $mail_nombres, $idCampana, $remite_correo, $subject, 'En lista negra (rebote previo)', $_SESSION['id']);
										$contadorError++;
									} elseif(filter_var($mail_email, FILTER_VALIDATE_EMAIL)) {
										$message = $body;
										$array_usr_sent[] = $mail_email;
										$sql = "INSERT INTO mail_workqueue (
											idUsuario,
											idCampana,
											host,
											username,
											password,
											port,
											encriptacion,
											remite_correo,
											remite_nombre,
											subject,
											attachment_ruta,
											attachment_nombre,
											message,
											mail_email,
											mail_nombres
											)
											VALUES (
											'".$_SESSION["id"]."',
											'".$idCampana."',
											'".$host."',
											'".$username."',
											'".$password."',
											'".$port."',
											'".$encriptacion."',
											'".$remite_correo."',
											'".$remite_nombre."',
											'".addslashes($subject)."',
											'".$attachment_ruta."',
											'".$attachment_nombre."',
											'".addslashes($message)."',
											'".$mail_email."',
											'".$mail_nombres."'
											)";
										$PSNT->query($sql);
										$contadorOk++;
									} else {
										$mensajes_error .= "\nCorreo INV&Aacute;LIDO (" . str_replace("@", "&#64;", $mail_email) . ') NO fue a&ntilde;adido a la cola de trabajo';
										mq_insertAuditStatic('invalido', $mail_email, $mail_nombres, $idCampana, $remite_correo, $subject, 'Formato invalido (filter_var)', $_SESSION['id']);
										$contadorError++;
									}
								}
								else
								{
									$mensajes_error .= "\nCorreo DUPLICADO (" . str_replace("@", "&#64;", $PSN->f('email')) . ') YA fue a&ntilde;adido a la cola de trabajo';
									mq_insertAuditStatic('duplicado', $PSN->f('email'), $PSN->f('nombres'), $idCampana, $remite_correo, $subject, 'Duplicado en lista de destinatarios', $_SESSION['id']);
									$contadorError++;
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
									$queue_idUsuario = $PSN->f('id');
									$mail_email = $PSN->f('email');
									$mail_nombres = $PSN->f('nombres');

									// Verificar lista negra antes de encolar
									$blChk = new DBbase_Sql;
									$blChk->query("SELECT id FROM mail_blacklist WHERE correo='" . addslashes($mail_email) . "'");
									if($blChk->num_rows() > 0) {
										$mensajes_error .= "\nCorreo en LISTA NEGRA (" . str_replace("@", "&#64;", $mail_email) . ') NO fue a&ntilde;adido a la cola';
										mq_insertAuditStatic('rebote', $mail_email, $mail_nombres, $idCampana, $remite_correo, $subject, 'En lista negra (rebote previo)', $_SESSION['id']);
										$contadorError++;
									} elseif(filter_var($mail_email, FILTER_VALIDATE_EMAIL)) {
										//
										//
										//$message = $body."<img src='https://videx.sms-app.co/mail/img.php?usr=".$PSN->f('id')."&cam=".$idCampana."&tmp=".strtotime("now")."' />";
										$message = $body;
										$array_usr_sent[] = $mail_email;
										//Guardamos el log de envio.
										$sql = "INSERT INTO mail_workqueue (
											idUsuario,
											idCampana,
											host,
											username,
											password,
											port,
											encriptacion,
											remite_correo,
											remite_nombre,
											subject,
											attachment_ruta,
											attachment_nombre,
											message,
											mail_email,
											mail_nombres
											)
											VALUES (";

										$sql .= "
											'".$_SESSION["id"]."',
											'".$idCampana."',
											'".$host."',
											'".$username."',
											'".$password."',
											'".$port."',
											'".$encriptacion."',
											'".$remite_correo."',
											'".$remite_nombre."',
											'".addslashes($subject)."',
											'".$attachment_ruta."',
											'".$attachment_nombre."',
											'".addslashes($message)."',
											'".$mail_email."',
											'".$mail_nombres."'
											)";

										$PSNT->query($sql);
										//
										$contadorOk++;
									} else {
										$mensajes_error .= "\nCorreo INV&Aacute;LIDO (" . str_replace("@", "&#64;", $mail_email) . ') NO fue a&ntilde;adido a la cola de trabajo';
										mq_insertAuditStatic('invalido', $mail_email, $mail_nombres, $idCampana, $remite_correo, $subject, 'Formato invalido (filter_var)', $_SESSION['id']);
										$contadorError++;
									}
								}
								else
								{
									$mensajes_error .= "\nCorreo DUPLICADO (" . str_replace("@", "&#64;", $PSN->f('email')) . ') YA fue a&ntilde;adido a la cola de trabajo';
									mq_insertAuditStatic('duplicado', $PSN->f('email'), $PSN->f('nombres'), $idCampana, $remite_correo, $subject, 'Duplicado en lista de destinatarios', $_SESSION['id']);
									$contadorError++;
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
									$queue_idUsuario = $PSN->f('id');
									$mail_email = $PSN->f('email');
									$mail_nombres = $PSN->f('nombres');

									// Verificar lista negra antes de encolar
									$blChk = new DBbase_Sql;
									$blChk->query("SELECT id FROM mail_blacklist WHERE correo='" . addslashes($mail_email) . "'");
									if($blChk->num_rows() > 0) {
										$mensajes_error .= "\nCorreo en LISTA NEGRA (" . str_replace("@", "&#64;", $mail_email) . ') NO fue a&ntilde;adido a la cola';
										mq_insertAuditStatic('rebote', $mail_email, $mail_nombres, $idCampana, $remite_correo, $subject, 'En lista negra (rebote previo)', $_SESSION['id']);
										$contadorError++;
									} elseif(filter_var($mail_email, FILTER_VALIDATE_EMAIL)) {
										//
										//
										//$message = $body."<img src='https://videx.sms-app.co/mail/img.php?usr=".$PSN->f('id')."&cam=".$idCampana."&tmp=".strtotime("now")."' />";
										$message = $body;

										$array_usr_sent[] = $mail_email;
										//Guardamos el log de envio.
										$sql = "INSERT INTO mail_workqueue (
											idUsuario,
											idCampana,
											host,
											username,
											password,
											port,
											encriptacion,
											remite_correo,
											remite_nombre,
											subject,
											attachment_ruta,
											attachment_nombre,
											message,
											mail_email,
											mail_nombres
											)
											VALUES (";

										$sql .= "
											'".$_SESSION["id"]."',
											'".$idCampana."',
											'".$host."',
											'".$username."',
											'".$password."',
											'".$port."',
											'".$encriptacion."',
											'".$remite_correo."',
											'".$remite_nombre."',
											'".addslashes($subject)."',
											'".$attachment_ruta."',
											'".$attachment_nombre."',
											'".addslashes($message)."',
											'".$mail_email."',
											'".$mail_nombres."'
											)";

										$PSNT->query($sql);
										//
										$contadorOk++;
									} else {
										$mensajes_error .= "\nCorreo INV&Aacute;LIDO (" . str_replace("@", "&#64;", $mail_email) . ') NO fue a&ntilde;adido a la cola de trabajo';
										mq_insertAuditStatic('invalido', $mail_email, $mail_nombres, $idCampana, $remite_correo, $subject, 'Formato invalido (filter_var)', $_SESSION['id']);
										$contadorError++;
									}
								}
								else
								{
									$mensajes_error .= "\nCorreo DUPLICADO (" . str_replace("@", "&#64;", $PSN->f('email')) . ') YA fue a&ntilde;adido a la cola de trabajo';
									mq_insertAuditStatic('duplicado', $PSN->f('email'), $PSN->f('nombres'), $idCampana, $remite_correo, $subject, 'Duplicado en lista de destinatarios', $_SESSION['id']);
									$contadorError++;
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
									$queue_idUsuario = $PSN->f('id');
									$mail_email = $PSN->f('email');
									$mail_nombres = $PSN->f('nombres');

									// Verificar lista negra antes de encolar
									$blChk = new DBbase_Sql;
									$blChk->query("SELECT id FROM mail_blacklist WHERE correo='" . addslashes($mail_email) . "'");
									if($blChk->num_rows() > 0) {
										$mensajes_error .= "\nCorreo en LISTA NEGRA (" . str_replace("@", "&#64;", $mail_email) . ') NO fue a&ntilde;adido a la cola';
										mq_insertAuditStatic('rebote', $mail_email, $mail_nombres, $idCampana, $remite_correo, $subject, 'En lista negra (rebote previo)', $_SESSION['id']);
										$contadorError++;
									} elseif(filter_var($mail_email, FILTER_VALIDATE_EMAIL)) {
										//
										//
										//$message = $body."<img src='https://videx.sms-app.co/mail/img.php?usr=".$PSN->f('id')."&cam=".$idCampana."&tmp=".strtotime("now")."' />";
										$message = $body;
										$array_usr_sent[] = $mail_email;
										//Guardamos el log de envio.
										$sql = "INSERT INTO mail_workqueue (
											idUsuario,
											idCampana,
											host,
											username,
											password,
											port,
											encriptacion,
											remite_correo,
											remite_nombre,
											subject,
											attachment_ruta,
											attachment_nombre,
											message,
											mail_email,
											mail_nombres
											)
											VALUES (";

										$sql .= "
											'".$_SESSION["id"]."',
											'".$idCampana."',
											'".$host."',
											'".$username."',
											'".$password."',
											'".$port."',
											'".$encriptacion."',
											'".$remite_correo."',
											'".$remite_nombre."',
											'".addslashes($subject)."',
											'".$attachment_ruta."',
											'".$attachment_nombre."',
											'".addslashes($message)."',
											'".$mail_email."',
											'".$mail_nombres."'
											)";

										$PSNT->query($sql);
										//
										$contadorOk++;
									} else {
										$mensajes_error .= "\nCorreo INV&Aacute;LIDO (" . str_replace("@", "&#64;", $mail_email) . ') NO fue a&ntilde;adido a la cola de trabajo';
										mq_insertAuditStatic('invalido', $mail_email, $mail_nombres, $idCampana, $remite_correo, $subject, 'Formato invalido (filter_var)', $_SESSION['id']);
										$contadorError++;
									}
								}
								else
								{
									$mensajes_error .= "\nCorreo DUPLICADO (" . str_replace("@", "&#64;", $PSN->f('email')) . ') YA fue a&ntilde;adido a la cola de trabajo';
									mq_insertAuditStatic('duplicado', $PSN->f('email'), $PSN->f('nombres'), $idCampana, $remite_correo, $subject, 'Duplicado en lista de destinatarios', $_SESSION['id']);
									$contadorError++;
								}
							}
						}
					}//Fin correos clientes					
				}
			}
		}

		if($contadorOk > 0){
			echo "<strong>Se han enviado <strong>".intval($contadorOk)."</strong> correos a la cola de trabajo.
			<br/><br />
			Tenga en cuenta que segun la configuracion del servidor los archivos enviados por medio de la cola de trabajo seran evacuados a razon de aproximadamente 300 correos por hora, esto para evitar que su servidor sea marcado como SPAM.</strong><br /><br />";			
		}
		
		if($contadorError > 0)
		{
			?><center><textarea cols="80" rows="20"><?=$mensajes_error; ?></textarea></center><?php
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

	?><div class="me-page">

    <!-- Encabezado -->
    <div class="me-header">
        <div class="me-header-icon"><i class="bi bi-envelope-fill"></i></div>
        <div>
            <h2>Env&iacute;o de Correos</h2>
            <p>Redacta y env&iacute;a correos masivos a grupos, contactos o prospectos</p>
        </div>
    </div>

    <form action="index.php?doc=mail_envio&opc=1" method="post" id="envio_masivo_mail" name="envio_masivo_mail" enctype="multipart/form-data">

        <!-- Card: Datos del mensaje -->
        <div class="me-card">
            <p class="me-card-title"><i class="bi bi-pencil-square"></i> Datos del mensaje</p>

            <div class="me-row">
                <div class="me-field">
                    <label class="me-label" for="idCampana">Campa&ntilde;a</label>
                    <select name="idCampana" id="idCampana" class="me-select">
                        <option value="">Ninguna</option>
                        <?php
                        $PSNTEMP = new DBbase_Sql;
                        $sql  = "SELECT id, nombre FROM campana ";
                        $sql .= " WHERE fechaInicial <= '".date("Y-m-d")."' AND fechaFinal >= '".date("Y-m-d")."'";
                        $sql .= " ORDER BY nombre ASC";
                        $PSNTEMP->query($sql);
                        while ($PSNTEMP->next_record()) {
                            $sel = (soloNumeros($_POST["idCampana"]) == $PSNTEMP->f('id')) ? ' selected' : '';
                            echo '<option value="'.$PSNTEMP->f('id').'"'.$sel.'>'.$PSNTEMP->f('nombre').'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="me-field">
                    <label class="me-label" for="idConfig">Cuenta remitente <span>*</span></label>
                    <select name="idConfig" id="idConfig" class="me-select">
                        <?php
                        $PSNTEMP = new DBbase_Sql;
                        $sql  = "SELECT id, nombre, defecto FROM mail_config ORDER BY nombre ASC";
                        $PSNTEMP->query($sql);
                        while ($PSNTEMP->next_record()) {
                            if (!isset($_POST["idConfig"]) && $PSNTEMP->f('defecto') == 1) {
                                $sel = ' selected';
                            } elseif (soloNumeros($_POST["idConfig"]) == $PSNTEMP->f('id')) {
                                $sel = ' selected';
                            } else {
                                $sel = '';
                            }
                            echo '<option value="'.$PSNTEMP->f('id').'"'.$sel.'>'.$PSNTEMP->f('nombre').'</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="me-row full">
                <div class="me-field">
                    <label class="me-label" for="asuntocorreo">Asunto <span>*</span></label>
                    <input type="text" name="asuntocorreo" id="asuntocorreo" placeholder="Asunto del correo" class="me-input" />
                </div>
            </div>

            <div class="me-row full">
                <div class="me-field">
                    <label class="me-label" for="mensajecorreo">Mensaje</label>
                    <div class="me-editor-wrap">
                        <textarea name="mensajecorreo" id="mensajecorreo" cols="40" rows="8" class="ckeditor"></textarea>
                    </div>
                </div>
            </div>

            <div class="me-row full">
                <div class="me-field">
                    <label class="me-label">Archivos adjuntos</label>
                    <div class="me-file-area" id="fileDropArea">
                        <input type="file" name="archivos[]" id="archivos" multiple />
                        <i class="bi bi-cloud-arrow-up me-file-icon"></i>
                        <span class="me-file-label">Arrastra archivos aqu&iacute; o <strong>haz clic para seleccionar</strong></span>
                        <p class="me-file-name" id="fileNames"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Destinatarios -->
        <div class="me-card">
            <p class="me-card-title"><i class="bi bi-people-fill"></i> Destinatarios</p>

            <div class="me-tabs">
                <button type="button" class="me-tab-btn active" data-tab="tab-grupos">
                    <i class="bi bi-collection"></i> Grupos
                </button>
                <button type="button" class="me-tab-btn" data-tab="tab-contactos">
                    <i class="bi bi-person-lines-fill"></i> Contactos individuales
                </button>
                <button type="button" class="me-tab-btn" data-tab="tab-prospectos">
                    <i class="bi bi-briefcase"></i> Prospectos
                </button>
            </div>

            <!-- Tab: Grupos -->
            <div id="tab-grupos" class="me-tab-panel active">
                <div class="me-groups-grid">
                    <?php
                    $sql  = "SELECT sms_grupos.* FROM sms_grupos ORDER BY nombre ASC";
                    $PSN->query($sql);
                    while ($PSN->next_record()) {
                        $checked = '';
                        if (isset($_POST['listaasociar']) && in_array($PSN->f('id'), $_POST['listaasociar'])) {
                            $checked = ' checked';
                        }
                        echo '<label class="me-group-item'.($checked ? ' checked' : '').'">';
                        echo '<input type="checkbox" name="listaasociar[]" value="'.$PSN->f('id').'"'.$checked.' />';
                        echo htmlspecialchars($PSN->f('nombre'));
                        echo '</label>';
                    }
                    ?>
                </div>
            </div>

            <!-- Tab: Contactos individuales -->
            <div id="tab-contactos" class="me-tab-panel">
                <div class="me-row full" style="margin-top:4px;">
                    <div class="me-field">
                        <label class="me-label" for="mensajeusumanual">Buscar y agregar usuarios</label>
                        <input type="text" id="mensajeusumanual" name="mensajeusumanual" class="me-input" placeholder="Escribe para buscar..." />
                    </div>
                </div>
            </div>

            <!-- Tab: Prospectos -->
            <div id="tab-prospectos" class="me-tab-panel">
                <div class="me-row full" style="margin-top:4px;">
                    <div class="me-field">
                        <label class="me-label" for="idCliente">Seleccionar prospecto</label>
                        <select name="idCliente" id="idCliente" class="me-select">
                            <option value="">Sin seleccionar</option>
                            <option value="-99">TODOS LOS PROSPECTOS</option>
                            <?php
                            $PSN2 = new DBbase_Sql;
                            $sql  = "SELECT cliente.id, cliente.nombre FROM cliente ";
                            if ($_SESSION["perfil"] == 162) {
                                $sql .= " WHERE idComercial = '".$_SESSION["id"]."'";
                            }
                            $sql .= " ORDER BY nombre ASC";
                            $PSN2->query($sql);
                            while ($PSN2->next_record()) {
                                echo '<option value="'.$PSN2->f('id').'">'.$PSN2->f('nombre').'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="me-actions">
            <a href="index.php?doc=main" class="me-btn-cancel"><i class="bi bi-x-circle"></i> Cerrar</a>
            <button type="submit" name="button" class="me-btn-send"><i class="bi bi-send-fill"></i> Enviar correos</button>
        </div>

        <!-- Correo de prueba -->
        <div class="me-card me-test-mail-card">
            <p class="me-card-title"><i class="bi bi-send"></i> Correo de prueba</p>
            <div class="me-row">
                <div class="me-field" style="flex:2">
                    <label class="me-label" for="test-email-address">Enviar prueba a</label>
                    <input type="email" id="test-email-address" placeholder="destinatario@ejemplo.com" class="me-input" />
                </div>
                <div class="me-field" style="flex:1;display:flex;align-items:flex-end;">
                    <button type="button" id="btn-test-email" class="me-status-refresh" style="width:100%;justify-content:center;">
                        <i class="bi bi-send"></i> Enviar prueba
                    </button>
                </div>
            </div>
            <p id="test-email-result" style="margin:6px 0 0;font-size:13px;font-weight:600;"></p>
        </div>

    </form>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Token input para contactos individuales
    $("#mensajeusumanual").tokenInput("busquedaE.php");

    // Tabs
    document.querySelectorAll('.me-tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var target = this.getAttribute('data-tab');
            document.querySelectorAll('.me-tab-btn').forEach(function(b) { b.classList.remove('active'); });
            document.querySelectorAll('.me-tab-panel').forEach(function(p) { p.classList.remove('active'); });
            this.classList.add('active');
            document.getElementById(target).classList.add('active');
        });
    });

    // Checkboxes de grupos: toggle clase "checked"
    document.querySelectorAll('.me-group-item input[type=checkbox]').forEach(function(cb) {
        cb.addEventListener('change', function() {
            this.closest('.me-group-item').classList.toggle('checked', this.checked);
        });
    });

    // Mostrar nombres de archivos seleccionados
    document.getElementById('archivos').addEventListener('change', function() {
        var names = Array.from(this.files).map(function(f) { return f.name; }).join(', ');
        document.getElementById('fileNames').textContent = names || '';
    });

    // Correo de prueba
    document.getElementById('btn-test-email').addEventListener('click', function() {
        var addr = document.getElementById('test-email-address').value.trim();
        var idConfig = document.getElementById('idConfig').value;
        var asunto = document.getElementById('asuntocorreo').value.trim() || 'Correo de prueba VIDEX';
        var resultEl = document.getElementById('test-email-result');
        if (!addr || !idConfig) {
            resultEl.textContent = 'Selecciona una cuenta remitente e ingresa un correo destino.';
            resultEl.style.color = '#b45309';
            return;
        }
        resultEl.textContent = 'Enviando...';
        resultEl.style.color = '#64748b';
        $.ajax({
            url: 'mail_test_send.php',
            type: 'POST',
            data: { idConfig: idConfig, to: addr, asunto: asunto },
            dataType: 'json',
            success: function(r) {
                resultEl.textContent = r.ok ? 'Enviado correctamente a ' + addr : 'Error: ' + r.msg;
                resultEl.style.color = r.ok ? '#15803d' : '#b91c1c';
            },
            error: function() { resultEl.textContent = 'Error de conexi\u00f3n al enviar prueba.'; resultEl.style.color = '#b91c1c'; }
        });
    });
});
</script>

	<?php
}
else if($opc == 2)
{
	$registros = 50;
	$pagina = isset($_GET["pagina"]) ? (int) soloNumeros($_GET["pagina"]) : 1;
	if ($pagina < 1) {
		$pagina = 1;
	}
	$inicio = ($pagina - 1) * $registros;

	$dbCount = new DBbase_Sql;
	$dbCount->query("SELECT COUNT(*) AS total FROM mail_historico");
	$total_registros = 0;
	if ($dbCount->next_record()) {
		$total_registros = (int) $dbCount->f('total');
	}
	$total_paginas = max(1, (int) ceil($total_registros / $registros));
	if ($pagina > $total_paginas) {
		$pagina = $total_paginas;
		$inicio = ($pagina - 1) * $registros;
	}

	$dbHist = new DBbase_Sql;
	$sql  = "SELECT mail_historico.*, usuario.nombre as usu_nombre, campana.nombre as nombrecam ";
	$sql .= " FROM mail_historico ";
	$sql .= " LEFT JOIN usuario ON usuario.id = mail_historico.audit_usuario";
	$sql .= " LEFT JOIN campana ON campana.id = mail_historico.idCampana";
	$sql .= " ORDER BY mail_historico.audit_fecha DESC LIMIT ".$inicio.", ".$registros;
	$dbHist->query($sql);

	$rows = [];
	while ($dbHist->next_record()) {
		$contenidoRawModal = (string) $dbHist->f('contenido');
		$contenidoPreview = trim((string) preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($contenidoRawModal, ENT_QUOTES, 'UTF-8'))));
		if (mb_strlen($contenidoPreview, 'UTF-8') > 260) {
			$contenidoPreview = mb_substr($contenidoPreview, 0, 260, 'UTF-8') . '...';
		}

		$rows[] = [
			'id' => (int) $dbHist->f('id'),
			'fecha' => (string) $dbHist->f('audit_fecha'),
			'campana' => (string) $dbHist->f('nombrecam'),
			'asunto' => (string) $dbHist->f('asunto'),
			'remitente' => (string) $dbHist->f('mhi_cuent_fk'),
			'enviados' => (int) $dbHist->f('correos'),
			'errores' => (int) $dbHist->f('error'),
			'usuario' => (string) $dbHist->f('usu_nombre'),
			'detalle' => trim((string) $dbHist->f('detalle_error')),
			'contenido_raw' => $contenidoRawModal,
			'contenido_preview' => $contenidoPreview,
		];
	}

	?><div class="me-page me-history-shell">
		<div class="me-header">
			<div class="me-header-icon"><i class="bi bi-clock-history"></i></div>
			<div>
				<h2>Histórico de envíos</h2>
				<p>Tabla refactorizada con scroll horizontal y modal para ver el detalle del envío.</p>
			</div>
		</div>

		<div class="me-card">
			<div class="me-history-toolbar">
				<strong><?php echo $total_registros; ?> registros</strong>
			</div>

			<div class="me-history-table-wrap">
				<table class="me-history-table">
					<thead>
						<tr>
							<th>#</th>
							<th>Fecha y hora</th>
							<th>Ver</th>
							<th>Asunto</th>
							<th>Contenido</th>
							<th>Campaña</th>
							<th>Usuario</th>
							<th>Enviados</th>
							<th>Errores</th>
							<th>Cuenta remitente</th>
						</tr>
					</thead>
					<tbody>
						<?php if (count($rows) > 0) { ?>
							<?php $n = $inicio + 1; ?>
							<?php foreach ($rows as $row) { ?>
								<tr class="<?php echo ($row['errores'] > 0) ? 'me-history-row-error' : ''; ?>">
									<td><?php echo $n; ?></td>
									<td><?php echo htmlspecialchars($row['fecha']); ?></td>
									<td>
										<button
											type="button"
											class="me-status-detail-btn js-open-mail-detail"
											data-fecha="<?php echo htmlspecialchars($row['fecha'], ENT_QUOTES, 'UTF-8'); ?>"
											data-campana="<?php echo htmlspecialchars($row['campana'] !== '' ? $row['campana'] : 'Sin campaña', ENT_QUOTES, 'UTF-8'); ?>"
											data-usuario="<?php echo htmlspecialchars($row['usuario'], ENT_QUOTES, 'UTF-8'); ?>"
											data-remitente="<?php echo htmlspecialchars($row['remitente'], ENT_QUOTES, 'UTF-8'); ?>"
											data-enviados="<?php echo (int) $row['enviados']; ?>"
											data-errores="<?php echo (int) $row['errores']; ?>"
											data-asunto="<?php echo htmlspecialchars($row['asunto'] !== '' ? $row['asunto'] : 'Sin asunto', ENT_QUOTES, 'UTF-8'); ?>"
											data-contenido="<?php echo htmlspecialchars($row['contenido_raw'] !== '' ? $row['contenido_raw'] : 'Sin contenido registrado.', ENT_QUOTES, 'UTF-8'); ?>"
											data-detalle="<?php echo htmlspecialchars($row['detalle'] !== '' ? $row['detalle'] : 'Sin detalle de error.', ENT_QUOTES, 'UTF-8'); ?>"
										>
											Ver
										</button>
									</td>
									<td><?php echo $row['asunto'] !== '' ? htmlspecialchars($row['asunto']) : '<span class="me-status-empty">Sin asunto</span>'; ?></td>
									<td><div class="me-history-content"><?php echo $row['contenido_preview'] !== '' ? htmlspecialchars($row['contenido_preview']) : 'Sin contenido'; ?></div></td>
									<td><?php echo $row['campana'] !== '' ? htmlspecialchars($row['campana']) : '<span class="me-status-empty">Sin campaña</span>'; ?></td>
									<td><?php echo htmlspecialchars($row['usuario']); ?></td>
									<td><?php echo (int) $row['enviados']; ?></td>
									<td><?php echo (int) $row['errores']; ?></td>
									<td><?php echo htmlspecialchars($row['remitente']); ?></td>
								</tr>
								<?php $n++; ?>
							<?php } ?>
						<?php } else { ?>
							<tr><td colspan="10" class="me-status-empty">No hay registros de envío aún.</td></tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="me-pagination-wrap">
			<ul class="pagination">
				<?php
				$paginaActualTxT = "&pagina=".$pagina;
				$_SERVER['REQUEST_URI'] = str_replace($paginaActualTxT,"", $_SERVER['REQUEST_URI']);
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

		<div class="me-modal" id="mailDetailModal" aria-hidden="true">
			<div class="me-modal-dialog">
				<div class="me-modal-card">
					<div class="me-modal-header">
						<div>
							<p class="me-card-title"><i class="bi bi-envelope-paper"></i> Detalle del envío</p>
							<h3 id="mailDetailSubject">Información del envío</h3>
						</div>
						<button type="button" class="me-modal-close" id="mailDetailClose" aria-label="Cerrar modal">&times;</button>
					</div>
					<div class="me-modal-body">
						<div class="me-modal-grid">
							<div><span>Fecha</span><strong id="mailDetailFecha">-</strong></div>
							<div><span>Campaña</span><strong id="mailDetailCampana">-</strong></div>
							<div><span>Usuario</span><strong id="mailDetailUsuario">-</strong></div>
							<div><span>Cuenta remitente</span><strong id="mailDetailRemitente">-</strong></div>
							<div><span>Enviados</span><strong id="mailDetailEnviados">0</strong></div>
							<div><span>Errores</span><strong id="mailDetailErrores">0</strong></div>
						</div>
						<div class="me-modal-section">
							<h4>Contenido enviado</h4>
							<div class="me-modal-content-box" id="mailDetailContenido">Sin contenido registrado.</div>
						</div>
						<div class="me-modal-section">
							<h4>Detalle del error</h4>
							<p id="mailDetailError">Sin detalle de error.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script>
	(function () {
		var openButtons = document.querySelectorAll('.js-open-mail-detail');
		var detailModal = document.getElementById('mailDetailModal');
		var detailClose = document.getElementById('mailDetailClose');
		if (!detailModal || !detailClose) {
			return;
		}

		var subjectEl = document.getElementById('mailDetailSubject');
		var fechaEl = document.getElementById('mailDetailFecha');
		var campanaEl = document.getElementById('mailDetailCampana');
		var usuarioEl = document.getElementById('mailDetailUsuario');
		var remitenteEl = document.getElementById('mailDetailRemitente');
		var enviadosEl = document.getElementById('mailDetailEnviados');
		var erroresEl = document.getElementById('mailDetailErrores');
		var contenidoEl = document.getElementById('mailDetailContenido');
		var errorEl = document.getElementById('mailDetailError');

		openButtons.forEach(function (button) {
			button.addEventListener('click', function () {
				subjectEl.textContent = this.getAttribute('data-asunto') || 'Información del envío';
				fechaEl.textContent = this.getAttribute('data-fecha') || '-';
				campanaEl.textContent = this.getAttribute('data-campana') || 'Sin campaña';
				usuarioEl.textContent = this.getAttribute('data-usuario') || '-';
				remitenteEl.textContent = this.getAttribute('data-remitente') || '-';
				enviadosEl.textContent = this.getAttribute('data-enviados') || '0';
				erroresEl.textContent = this.getAttribute('data-errores') || '0';
				contenidoEl.textContent = this.getAttribute('data-contenido') || 'Sin contenido registrado.';
				errorEl.textContent = this.getAttribute('data-detalle') || 'Sin detalle de error.';
				detailModal.classList.add('show');
				detailModal.setAttribute('aria-hidden', 'false');
			});
		});

		detailClose.addEventListener('click', function () {
			detailModal.classList.remove('show');
			detailModal.setAttribute('aria-hidden', 'true');
		});

		detailModal.addEventListener('click', function (event) {
			if (event.target === detailModal) {
				detailClose.click();
			}
		});
	})();
	</script>
	<?php
}
else if($opc == 3)
{
	// ── Cancelar envío activo (con o sin campaña asociada) ──────────────────
	// Agrupa mail_workqueue por idCampana+idUsuario+remite_correo+subject porque
	// no existe un identificador de "envío" individual en el esquema actual.
	$cancelMensaje = null;
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cancelar_envio') {
		$cIdCampana = soloNumeros($_POST['g_idCampana'] ?? '0');
		$cIdUsuario = soloNumeros($_POST['g_idUsuario'] ?? '0');
		$cRemite    = addslashes($_POST['g_remite'] ?? '');
		$cSubject   = addslashes($_POST['g_subject'] ?? '');

		$PSNCancelCnt = new DBbase_Sql;
		$PSNCancelCnt->query(
			"SELECT COUNT(*) as total FROM mail_workqueue
			 WHERE idCampana = '$cIdCampana' AND idUsuario = '$cIdUsuario'
			 AND remite_correo = '$cRemite' AND subject = '$cSubject'"
		);
		$cancelCount = 0;
		if ($PSNCancelCnt->next_record()) {
			$cancelCount = (int)$PSNCancelCnt->f('total');
		}

		if ($cancelCount > 0) {
			$PSNCancel = new DBbase_Sql;
			$PSNCancel->query(
				"DELETE FROM mail_workqueue
				 WHERE idCampana = '$cIdCampana' AND idUsuario = '$cIdUsuario'
				 AND remite_correo = '$cRemite' AND subject = '$cSubject'"
			);
			$cancelMensaje = ['tipo' => 'success', 'texto' => "Se cancelaron $cancelCount mensaje(s) pendiente(s) de ese envío."];
		} else {
			$cancelMensaje = ['tipo' => 'info', 'texto' => 'No se encontraron mensajes pendientes para ese envío (puede que ya se hayan procesado).'];
		}
	}

	// ── Contadores cola principal ───────────────────────────────────────────
	$registros = 50;
	$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
	$inicio    = ($pagina - 1) * $registros;

	$PSNColaCnt = new DBbase_Sql;
	$PSNColaCnt->query("SELECT COUNT(id) AS total FROM mail_workqueue");
	$PSNColaCnt->next_record();
	$pendientesHoy = (int)$PSNColaCnt->f('total');

	$PSNStat = new DBbase_Sql;
	$PSNStat->query("SELECT COALESCE(SUM(correos), 0) AS total FROM mail_historico WHERE fecha = '" . date('Y-m-d') . "'");
	$PSNStat->next_record();
	$enviadosHoy = (int)$PSNStat->f('total');

	$PSNStatErr = new DBbase_Sql;
	$PSNStatErr->query("SELECT COALESCE(SUM(error), 0) AS total FROM mail_historico WHERE fecha = '" . date('Y-m-d') . "'");
	$PSNStatErr->next_record();
	$erroresHoy = (int)$PSNStatErr->f('total');

	// ── Contadores auditoría ────────────────────────────────────────────────
	$PSNAudit = new DBbase_Sql;
	$PSNAudit->query("SELECT estado, COUNT(*) as cnt FROM mail_audit GROUP BY estado");
	$auditCounts = ['enviado' => 0, 'error' => 0, 'rebote' => 0, 'invalido' => 0, 'duplicado' => 0];
	while ($PSNAudit->next_record()) {
		$k = $PSNAudit->f('estado');
		if (isset($auditCounts[$k])) $auditCounts[$k] = (int)$PSNAudit->f('cnt');
	}
	$totalAudit = array_sum($auditCounts);

	// ── Estado pausa ────────────────────────────────────────────────────────
	$PSNCtrlOpc3 = new DBbase_Sql;
	$PSNCtrlOpc3->query("SELECT paused FROM mail_queue_control WHERE id = 1");
	$queueIsPaused = ($PSNCtrlOpc3->next_record() && (int)$PSNCtrlOpc3->f('paused') === 1);

	// ── Barra de progreso (auditoría + pendientes) ─────────────────────────
	$totalEstado = $totalAudit + $pendientesHoy;
	if ($totalEstado <= 0) {
		$porcEnviados = 0;
		$porcPendientes = 0;
		$porcErrores = 0;
		$porcRebotes = 0;
		$porcInvalidos = 0;
		$porcDuplicados = 0;
	} else {
		$porcEnviados   = round(($auditCounts['enviado']   / $totalEstado) * 100);
		$porcPendientes = round(($pendientesHoy            / $totalEstado) * 100);
		$porcErrores    = round(($auditCounts['error']     / $totalEstado) * 100);
		$porcRebotes    = round(($auditCounts['rebote']    / $totalEstado) * 100);
		$porcInvalidos  = round(($auditCounts['invalido']  / $totalEstado) * 100);
		$porcDuplicados = max(0, 100 - $porcEnviados - $porcPendientes - $porcErrores - $porcRebotes - $porcInvalidos);
	}

	// ── Filtros auditoría ───────────────────────────────────────────────────
	$filtro_estado  = in_array($_GET['filtro_estado'] ?? '', ['enviado','error','rebote','invalido','duplicado','']) ? ($_GET['filtro_estado'] ?? '') : '';
	$filtro_campana = soloNumeros($_GET['filtro_campana'] ?? '');
	$filtro_fecha   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['filtro_fecha'] ?? '') ? $_GET['filtro_fecha'] : '';
	$active_tab     = (($_GET['tab'] ?? '') === 'auditoria') ? 'auditoria' : 'cola';

	// ── Query cola ──────────────────────────────────────────────────────────
	$total_cola   = $pendientesHoy;
	$total_paginas_cola = max(1, (int)ceil($total_cola / $registros));
	$PSNColaList = new DBbase_Sql;
	$PSNColaList->query(
		"SELECT mail_workqueue.id, mail_workqueue.subject, mail_workqueue.mail_email,
		        mail_workqueue.mail_nombres, mail_workqueue.retry_count,
		        usuario.nombre, campana.nombre as nombrecam
		 FROM mail_workqueue
		 LEFT JOIN usuario ON usuario.id = mail_workqueue.idUsuario
		 LEFT JOIN campana ON campana.id = mail_workqueue.idCampana
		 ORDER BY mail_workqueue.id ASC
		 LIMIT $inicio, $registros"
	);

	// ── Envíos activos agrupados (con o sin campaña) — para poder cancelarlos ─
	$PSNGrupos = new DBbase_Sql;
	$PSNGrupos->query(
		"SELECT mail_workqueue.idCampana, mail_workqueue.idUsuario, mail_workqueue.remite_correo,
		        mail_workqueue.subject, COUNT(*) as total,
		        usuario.nombre as usuarioNombre, campana.nombre as campanaNombre
		 FROM mail_workqueue
		 LEFT JOIN usuario ON usuario.id = mail_workqueue.idUsuario
		 LEFT JOIN campana ON campana.id = mail_workqueue.idCampana
		 GROUP BY mail_workqueue.idCampana, mail_workqueue.idUsuario, mail_workqueue.remite_correo, mail_workqueue.subject
		 ORDER BY total DESC"
	);

	// ── Query auditoría ─────────────────────────────────────────────────────
	$whereAudit = "WHERE 1";
	if ($filtro_estado  !== '') $whereAudit .= " AND ma.estado='" . addslashes($filtro_estado) . "'";
	if ($filtro_campana !== '') $whereAudit .= " AND ma.idCampana='$filtro_campana'";
	if ($filtro_fecha   !== '') $whereAudit .= " AND DATE(ma.fecha)='" . addslashes($filtro_fecha) . "'";

	$pag_audit = max(1, (int)($_GET['pag_audit'] ?? 1));
	$off_audit  = ($pag_audit - 1) * $registros;

	$PSNAuditCnt = new DBbase_Sql;
	$PSNAuditCnt->query("SELECT COUNT(*) as total FROM mail_audit ma $whereAudit");
	$PSNAuditCnt->next_record();
	$total_audit_filtrado = (int)$PSNAuditCnt->f('total');
	$total_paginas_audit  = max(1, (int)ceil($total_audit_filtrado / $registros));

	$PSNAuditList = new DBbase_Sql;
	$PSNAuditList->query(
		"SELECT ma.id, ma.idCampana, ma.correo, ma.nombre AS audit_nombre,
		        ma.estado, ma.razon, ma.asunto, ma.remitente,
		        ma.fecha, ma.idUsuario, ma.retry_count,
		        c.nombre AS campana_nombre
		 FROM mail_audit ma
		 LEFT JOIN campana c ON c.id = ma.idCampana
		 $whereAudit
		 ORDER BY ma.fecha DESC
		 LIMIT $registros OFFSET $off_audit"
	);

	// Campañas para filtro
	$PSNCampFiltro = new DBbase_Sql;
	$PSNCampFiltro->query("SELECT id, nombre FROM campana ORDER BY nombre ASC");

	$cron_url = 'mail_cron.php?t=' . time();

	$badge_map = [
		'enviado'   => ['bg' => '#dcfce7', 'color' => '#15803d', 'label' => 'Enviado'],
		'error'     => ['bg' => '#fef2f2', 'color' => '#b91c1c', 'label' => 'Error'],
		'rebote'    => ['bg' => '#fff7ed', 'color' => '#c2410c', 'label' => 'Rebote'],
		'invalido'  => ['bg' => '#faf5ff', 'color' => '#7e22ce', 'label' => 'Inv&aacute;lido'],
		'duplicado' => ['bg' => '#ecfeff', 'color' => '#0e7490', 'label' => 'Duplicado'],
	];
	?>

	<div class="me-page me-status-page">
		<div class="me-header">
			<div class="me-header-icon"><i class="bi bi-activity"></i></div>
			<div>
				<h2>Cola de Mailing</h2>
				<p>Estado en tiempo real del env&iacute;o masivo &mdash; Cola pendiente y auditor&iacute;a de correos.</p>
			</div>
		</div>

		<?php if ($cancelMensaje): ?>
		<div class="alert alert-<?= $cancelMensaje['tipo'] === 'success' ? 'success' : 'info' ?>" style="margin-bottom:16px;">
			<?= htmlspecialchars($cancelMensaje['texto']) ?>
		</div>
		<?php endif; ?>

		<!-- ── Tarjeta de estado ─────────────────────────────────────────────── -->
		<div class="me-card me-status-summary-card">
			<div class="me-status-topline">
				<div>
					<p class="me-card-title"><i class="bi bi-broadcast"></i> Estado del env&iacute;o</p>
					<h3>Seguimiento en tiempo real &mdash; l&iacute;mite 300 correos/hora</h3>
				</div>
				<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
					<button type="button" id="btn-pause-queue"
						class="me-status-refresh <?= $queueIsPaused ? 'me-btn-paused' : '' ?>"
						style="<?= $queueIsPaused ? 'display:none' : '' ?>">
						<i class="bi bi-pause-fill"></i> Pausar cola
					</button>
					<button type="button" id="btn-resume-queue"
						class="me-status-refresh"
						style="<?= !$queueIsPaused ? 'display:none' : '' ?>">
						<i class="bi bi-play-fill"></i> Reanudar cola
					</button>
					<a href="<?= htmlspecialchars($cron_url) ?>" class="me-status-refresh">
						<i class="bi bi-arrow-repeat"></i> Actualizar manualmente
					</a>
				</div>
			</div>

			<!-- 7 contadores -->
			<div class="me-status-counters me-status-counters-7">
				<div class="me-status-counter me-status-counter-neutral">
					<span>Total auditor&iacute;a</span>
					<strong id="stat-total-audit"><?= $totalAudit ?></strong>
					<small>registros</small>
				</div>
				<div class="me-status-counter me-status-counter-success">
					<span>Enviados (audit)</span>
					<strong id="stat-audit-enviados"><?= $auditCounts['enviado'] ?></strong>
					<small>correos &oacute;k</small>
				</div>
				<div class="me-status-counter me-status-counter-warning">
					<span>Pendientes</span>
					<strong id="stat-pendientes"><?= $pendientesHoy ?></strong>
					<small>en cola</small>
				</div>
				<div class="me-status-counter me-status-counter-danger">
					<span>Errores</span>
					<strong id="stat-errores"><?= $auditCounts['error'] ?></strong>
					<small>temporales</small>
				</div>
				<div class="me-status-counter me-status-counter-bounce">
					<span>Rebotes</span>
					<strong id="stat-rebotes"><?= $auditCounts['rebote'] ?></strong>
					<small>permanentes</small>
				</div>
				<div class="me-status-counter me-status-counter-invalid">
					<span>Inv&aacute;lidos</span>
					<strong id="stat-invalidos"><?= $auditCounts['invalido'] ?></strong>
					<small>formato err&oacute;neo</small>
				</div>
				<div class="me-status-counter me-status-counter-dup">
					<span>Duplicados</span>
					<strong id="stat-duplicados"><?= $auditCounts['duplicado'] ?></strong>
					<small>omitidos</small>
				</div>
			</div>

			<div class="me-status-progress" aria-label="Distribución porcentual">
				<div class="me-status-progress-bar me-status-progress-success" id="pb-enviados" style="width:<?= $porcEnviados ?>%;">
					<span><?= $porcEnviados ?>%</span>
				</div>
				<div class="me-status-progress-bar me-status-progress-warning" id="pb-pendientes" style="width:<?= $porcPendientes ?>%;">
					<span><?= $porcPendientes ?>%</span>
				</div>
				<div class="me-status-progress-bar me-status-progress-danger" id="pb-errores" style="width:<?= $porcErrores ?>%;">
					<span><?= $porcErrores ?>%</span>
				</div>
				<div class="me-status-progress-bar me-status-progress-bounce" id="pb-rebotes" style="width:<?= $porcRebotes ?>%;">
					<span><?= $porcRebotes ?>%</span>
				</div>
				<div class="me-status-progress-bar me-status-progress-invalid" id="pb-invalidos" style="width:<?= $porcInvalidos ?>%;">
					<span><?= $porcInvalidos ?>%</span>
				</div>
				<div class="me-status-progress-bar me-status-progress-dup" id="pb-duplicados" style="width:<?= $porcDuplicados ?>%;">
					<span><?= $porcDuplicados ?>%</span>
				</div>
			</div>

			<div class="me-status-meta">
				<span><strong id="stat-pendientes-badge"><?= $pendientesHoy ?></strong> correos pendientes &mdash; l&iacute;mite: <span id="stat-rate-limit"><?= ($pendientesHoy > 300) ? '5/minuto (modo lento)' : '30/minuto (modo r&aacute;pido)' ?></span></span>
				<small id="mail-last-update">Cargando...</small>
			</div>
		</div>

		<!-- ── Tabs ──────────────────────────────────────────────────────────── -->
		<div class="me-queue-tabs" id="queueTabBar" data-active="<?= $active_tab ?>">
			<button type="button" class="me-queue-tab <?= $active_tab === 'cola' ? 'active' : '' ?>" data-panel="panel-cola">
				<i class="bi bi-inbox-fill"></i> Cola Pendiente
				<span class="me-queue-tab-badge" id="badge-pendientes"><?= $pendientesHoy ?></span>
			</button>
			<button type="button" class="me-queue-tab <?= $active_tab === 'auditoria' ? 'active' : '' ?>" data-panel="panel-auditoria">
				<i class="bi bi-journal-check"></i> Auditor&iacute;a
				<span class="me-queue-tab-badge me-badge-neutral" id="badge-auditoria"><?= $totalAudit ?></span>
			</button>
		</div>

		<!-- ── Panel: Cola Pendiente ─────────────────────────────────────────── -->
		<div id="panel-cola" class="me-queue-panel <?= $active_tab === 'cola' ? 'active' : '' ?>">
			<div class="me-card" style="margin-top:0;border-top-left-radius:0;">
				<p class="me-card-title"><i class="bi bi-broadcast-pin"></i> Env&iacute;os activos</p>
				<p style="margin-top:-8px;color:#6b7280;font-size:13px;">Agrupados por campa&ntilde;a (o &laquo;Sin campa&ntilde;a&raquo; para env&iacute;os masivos directos), usuario, remitente y asunto. Cancelar aqu&iacute; elimina de la cola solo los mensajes que a&uacute;n no se han enviado de ese env&iacute;o.</p>
				<div class="me-table-wrap">
					<table class="me-status-table">
						<thead>
							<tr>
								<th>Campa&ntilde;a</th>
								<th>Usuario</th>
								<th>Remitente</th>
								<th>Asunto</th>
								<th>Pendientes</th>
								<th>Acci&oacute;n</th>
							</tr>
						</thead>
						<tbody>
						<?php
						if ($PSNGrupos->num_rows() > 0) {
							while ($PSNGrupos->next_record()) {
								$gCampNombre = $PSNGrupos->f('campanaNombre');
								$gCampLabel  = $gCampNombre !== null && $gCampNombre !== '' ? $gCampNombre : 'Sin campaña';
								?>
								<tr>
									<td><?= htmlspecialchars((string)$gCampLabel) ?></td>
									<td><?= htmlspecialchars((string)$PSNGrupos->f('usuarioNombre')) ?></td>
									<td><?= htmlspecialchars((string)$PSNGrupos->f('remite_correo')) ?></td>
									<td><?= htmlspecialchars((string)$PSNGrupos->f('subject')) ?></td>
									<td><strong><?= (int)$PSNGrupos->f('total') ?></strong></td>
									<td>
										<form method="post" action="index.php?doc=mail_envio&opc=3&tab=cola" onsubmit="return confirm('ADVERTENCIA: ¿Está seguro que desea CANCELAR este envío? Se eliminarán de la cola los ' + <?= (int)$PSNGrupos->f('total') ?> + ' mensaje(s) pendientes. Esta acción no se puede deshacer.');" style="display:inline;">
											<input type="hidden" name="accion" value="cancelar_envio">
											<input type="hidden" name="g_idCampana" value="<?= (int)$PSNGrupos->f('idCampana') ?>">
											<input type="hidden" name="g_idUsuario" value="<?= (int)$PSNGrupos->f('idUsuario') ?>">
											<input type="hidden" name="g_remite" value="<?= htmlspecialchars((string)$PSNGrupos->f('remite_correo'), ENT_QUOTES, 'UTF-8') ?>">
											<input type="hidden" name="g_subject" value="<?= htmlspecialchars((string)$PSNGrupos->f('subject'), ENT_QUOTES, 'UTF-8') ?>">
											<button type="submit" class="btn btn-sm btn-danger">Cancelar env&iacute;o</button>
										</form>
									</td>
								</tr>
							<?php }
						} else { ?>
							<tr><td colspan="6" class="me-status-empty">No hay env&iacute;os activos.</td></tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="me-card">
				<p class="me-card-title"><i class="bi bi-inbox-fill"></i> Correos en cola</p>
				<div class="me-table-wrap">
					<table class="me-status-table">
						<thead>
							<tr>
								<th>#</th>
								<th>Campa&ntilde;a</th>
								<th>Usuario</th>
								<th>Asunto</th>
								<th>Para &mdash; Nombre</th>
								<th>Para &mdash; Correo</th>
								<th>Reintentos</th>
							</tr>
						</thead>
						<tbody>
						<?php
						$contador = $inicio + 1;
						$numColaRows = $PSNColaList->num_rows();
						if ($numColaRows > 0) {
							while ($PSNColaList->next_record()) { ?>
								<tr>
									<td><?= $contador ?></td>
									<td><?= htmlspecialchars((string)$PSNColaList->f('nombrecam')) ?></td>
									<td><?= htmlspecialchars((string)$PSNColaList->f('nombre')) ?></td>
									<td><?= htmlspecialchars((string)$PSNColaList->f('subject')) ?></td>
									<td><?= htmlspecialchars((string)$PSNColaList->f('mail_nombres')) ?></td>
									<td><?= htmlspecialchars((string)$PSNColaList->f('mail_email')) ?></td>
									<td><?= (int)$PSNColaList->f('retry_count') ?></td>
								</tr>
							<?php $contador++; }
						} else { ?>
							<tr><td colspan="7" class="me-status-empty">No hay correos en la cola.</td></tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="me-pagination-wrap">
				<ul class="pagination">
				<?php
				$baseUrlCola = 'index.php?doc=mail_envio&opc=3&tab=cola';
				if ($pagina > 1) echo "<li><a href='" . $baseUrlCola . "&pagina=" . ($pagina - 1) . "'>&laquo;</a></li>";
				for ($i = 1; $i <= $total_paginas_cola; $i++) {
					$cls = ($pagina == $i) ? "class='active'" : '';
					echo "<li $cls><a href='" . $baseUrlCola . "&pagina=$i'>$i</a></li>";
				}
				if ($pagina < $total_paginas_cola) echo "<li><a href='" . $baseUrlCola . "&pagina=" . ($pagina + 1) . "'>&raquo;</a></li>";
				?>
				</ul>
			</div>
		</div>

		<!-- ── Panel: Auditoría ──────────────────────────────────────────────── -->
		<div id="panel-auditoria" class="me-queue-panel <?= $active_tab === 'auditoria' ? 'active' : '' ?>">
			<div class="me-card" style="margin-top:0;border-top-left-radius:0;">
				<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
					<p class="me-card-title" style="margin:0"><i class="bi bi-journal-check"></i> Auditor&iacute;a de correos &mdash; <?= $total_audit_filtrado ?> registros</p>
					<a href="mail_audit_export.php?filtro_estado=<?= urlencode($filtro_estado) ?>&filtro_campana=<?= urlencode($filtro_campana) ?>&filtro_fecha=<?= urlencode($filtro_fecha) ?>"
					   class="me-status-refresh" style="text-decoration:none">
						<i class="bi bi-download"></i> Exportar CSV
					</a>
				</div>

				<!-- Filtros -->
				<form method="get" action="index.php" class="me-audit-filter-bar">
					<input type="hidden" name="doc" value="mail_envio" />
					<input type="hidden" name="opc" value="3" />
					<input type="hidden" name="tab" value="auditoria" />
					<select name="filtro_estado" class="me-select" style="width:auto;">
						<option value="">Todos los estados</option>
						<option value="enviado"   <?= $filtro_estado === 'enviado'   ? 'selected' : '' ?>>Enviados</option>
						<option value="error"     <?= $filtro_estado === 'error'     ? 'selected' : '' ?>>Errores</option>
						<option value="rebote"    <?= $filtro_estado === 'rebote'    ? 'selected' : '' ?>>Rebotes</option>
						<option value="invalido"  <?= $filtro_estado === 'invalido'  ? 'selected' : '' ?>>Inv&aacute;lidos</option>
						<option value="duplicado" <?= $filtro_estado === 'duplicado' ? 'selected' : '' ?>>Duplicados</option>
					</select>
					<select name="filtro_campana" class="me-select" style="width:auto;">
						<option value="">Todas las campa&ntilde;as</option>
						<?php while ($PSNCampFiltro->next_record()) {
							$sel = ($filtro_campana == $PSNCampFiltro->f('id')) ? 'selected' : '';
							echo '<option value="' . $PSNCampFiltro->f('id') . '" ' . $sel . '>' . htmlspecialchars($PSNCampFiltro->f('nombre')) . '</option>';
						} ?>
					</select>
					<input type="date" name="filtro_fecha" value="<?= htmlspecialchars($filtro_fecha) ?>" class="me-input" style="width:auto;" />
					<button type="submit" class="me-btn-send" style="width:auto;padding:8px 18px;">
						<i class="bi bi-funnel"></i> Filtrar
					</button>
					<?php if ($filtro_estado || $filtro_campana || $filtro_fecha) { ?>
					<a href="index.php?doc=mail_envio&opc=3&tab=auditoria" class="me-status-refresh" style="text-decoration:none">
						<i class="bi bi-x-circle"></i> Limpiar
					</a>
					<?php } ?>
				</form>

				<div class="me-table-wrap">
					<table class="me-status-table me-audit-table">
						<thead>
							<tr>
								<th>#</th>
								<th>Estado</th>
								<th>Correo</th>
								<th>Nombre</th>
								<th>Asunto</th>
								<th>Campa&ntilde;a</th>
								<th>Remitente</th>
								<th>Fecha</th>
								<th>Raz&oacute;n / Error</th>
								<th>Reintentos</th>
							</tr>
						</thead>
						<tbody>
						<?php
						$nAudit = $off_audit + 1;
						$numAuditRows = $PSNAuditList->num_rows();
						if ($numAuditRows > 0) {
							while ($PSNAuditList->next_record()) {
								$est = $PSNAuditList->f('estado');
								$b   = $badge_map[$est] ?? $badge_map['error'];
								$rowCls = 'me-audit-row-' . $est;
								$correoAudit = (string)$PSNAuditList->f('correo');
								$correoLink = 'index.php?doc=contactos&opc=2&bus_nombres=&bus_email=' . urlencode($correoAudit) . '&bus_celular=&idCliente=&bus_grupo=';
								?>
								<tr class="<?= $rowCls ?>">
									<td><?= $nAudit ?></td>
									<td>
										<span style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;padding:2px 10px;border-radius:999px;font-weight:700;font-size:11px;white-space:nowrap;">
											<?= $b['label'] ?>
										</span>
									</td>
									<td><a href="<?= htmlspecialchars($correoLink, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($correoAudit) ?></a></td>
									<td><?= htmlspecialchars((string)$PSNAuditList->f('audit_nombre')) ?></td>
									<td><?= htmlspecialchars((string)$PSNAuditList->f('asunto')) ?></td>
									<td><?= htmlspecialchars((string)$PSNAuditList->f('campana_nombre')) ?></td>
									<td><?= htmlspecialchars((string)$PSNAuditList->f('remitente')) ?></td>
									<td style="white-space:nowrap"><?= htmlspecialchars((string)$PSNAuditList->f('fecha')) ?></td>
									<td style="max-width:220px;word-break:break-word;"><?= htmlspecialchars((string)$PSNAuditList->f('razon')) ?></td>
									<td><?= (int)$PSNAuditList->f('retry_count') ?></td>
								</tr>
								<?php $nAudit++;
							}
						} else { ?>
							<tr><td colspan="10" class="me-status-empty">No hay registros de auditor&iacute;a<?= ($filtro_estado || $filtro_campana || $filtro_fecha) ? ' con los filtros aplicados' : ' a&uacute;n' ?>.</td></tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="me-pagination-wrap">
				<ul class="pagination">
				<?php
				$baseUrlAudit = 'index.php?doc=mail_envio&opc=3&tab=auditoria&filtro_estado=' . urlencode($filtro_estado) . '&filtro_campana=' . urlencode($filtro_campana) . '&filtro_fecha=' . urlencode($filtro_fecha);
				if ($pag_audit > 1) echo "<li><a href='" . $baseUrlAudit . "&pag_audit=" . ($pag_audit - 1) . "'>&laquo;</a></li>";
				for ($i = 1; $i <= $total_paginas_audit; $i++) {
					$cls = ($pag_audit == $i) ? "class='active'" : '';
					echo "<li $cls><a href='" . $baseUrlAudit . "&pag_audit=$i'>$i</a></li>";
				}
				if ($pag_audit < $total_paginas_audit) echo "<li><a href='" . $baseUrlAudit . "&pag_audit=" . ($pag_audit + 1) . "'>&raquo;</a></li>";
				?>
				</ul>
			</div>
		</div>

	</div><!-- /.me-page -->

	<script>
	(function() {
		var statusUrl    = 'mail_status.php';
		var pauseUrl     = 'mail_queue_pause.php';
		var pollInterval = 5000;
		var timer        = null;

		function updateStats() {
			$.ajax({
				url: statusUrl, type: 'GET', dataType: 'json', timeout: 8000,
				success: function(data) {
					var auditEnviados = data.audit_enviados || 0;
					var auditErrores = data.audit_errores || 0;
					var auditRebotes = data.audit_rebotes || 0;
					var auditInvalidos = data.audit_invalidos || 0;
					var auditDuplicados = data.audit_duplicados || 0;
					var pendientes = data.pendientes || 0;
					var totalAudit = auditEnviados + auditErrores + auditRebotes + auditInvalidos + auditDuplicados;
					var total = totalAudit + pendientes;
					if (total <= 0) total = 1;

					var pEnv = Math.round((auditEnviados / total) * 100);
					var pPen = Math.round((pendientes / total) * 100);
					var pErr = Math.round((auditErrores / total) * 100);
					var pReb = Math.round((auditRebotes / total) * 100);
					var pInv = Math.round((auditInvalidos / total) * 100);
					var pDup = Math.max(0, 100 - pEnv - pPen - pErr - pReb - pInv);

					$('#stat-pendientes').text(pendientes);
					$('#stat-pendientes-badge').text(pendientes);
					$('#stat-rate-limit').text(pendientes > 300 ? '5/minuto (modo lento)' : '30/minuto (modo rápido)');
					$('#badge-pendientes').text(pendientes);
					$('#stat-errores').text(auditErrores);
					$('#stat-total-audit').text(totalAudit);
					$('#stat-audit-enviados').text(auditEnviados);
					$('#stat-rebotes').text(auditRebotes);
					$('#stat-invalidos').text(auditInvalidos);
					$('#stat-duplicados').text(auditDuplicados);
					$('#badge-auditoria').text(totalAudit);

					$('#pb-enviados').css('width', pEnv + '%').find('span').text(pEnv + '%');
					$('#pb-pendientes').css('width', pPen + '%').find('span').text(pPen + '%');
					$('#pb-errores').css('width', pErr + '%').find('span').text(pErr + '%');
					$('#pb-rebotes').css('width', pReb + '%').find('span').text(pReb + '%');
					$('#pb-invalidos').css('width', pInv + '%').find('span').text(pInv + '%');
					$('#pb-duplicados').css('width', pDup + '%').find('span').text(pDup + '%');

					// Pausa
					if (typeof data.paused !== 'undefined') {
						if (data.paused) {
							$('#btn-pause-queue').hide();
							$('#btn-resume-queue').show();
						} else {
							$('#btn-pause-queue').show();
							$('#btn-resume-queue').hide();
						}
					}

					var now = new Date();
					$('#mail-last-update').text('Última actualización: ' + now.toLocaleTimeString());

					if (pendientes === 0 && timer !== null) {
						clearInterval(timer);
						timer = setInterval(updateStats, 30000);
						$('#mail-last-update').append(' — cola vacía, actualizando cada 30s');
					}
				},
				error: function() {
					$('#mail-last-update').text('Error al obtener estado. Reintentando...');
				}
			});
		}

		// Tabs
		document.querySelectorAll('.me-queue-tab').forEach(function(btn) {
			btn.addEventListener('click', function() {
				var panel = this.getAttribute('data-panel');
				document.querySelectorAll('.me-queue-tab').forEach(function(b) { b.classList.remove('active'); });
				document.querySelectorAll('.me-queue-panel').forEach(function(p) { p.classList.remove('active'); });
				this.classList.add('active');
				document.getElementById(panel).classList.add('active');
			});
		});

		// Pausar / Reanudar
		function toggleQueue(action) {
			$.ajax({
				url: pauseUrl, type: 'POST', data: { action: action }, dataType: 'json',
				success: function(r) {
					if (r.ok) {
						if (r.paused) {
							$('#btn-pause-queue').hide();
							$('#btn-resume-queue').show();
						} else {
							$('#btn-resume-queue').hide();
							$('#btn-pause-queue').show();
						}
					}
				}
			});
		}
		$('#btn-pause-queue').on('click', function()  { toggleQueue('pause');  });
		$('#btn-resume-queue').on('click', function() { toggleQueue('resume'); });

		updateStats();
		timer = setInterval(updateStats, pollInterval);
	})();
	</script><?php
}
?>