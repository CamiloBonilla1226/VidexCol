<?php



//http://api.messaging-service.com

//http://107.20.199.106/sms/1/text/query?username=videoexpr20&password=Sofonias317@&to=+573138746366&text=Prueba%20nocturna



//$url = "http://api.messaging-service.com";



function nuevo_enviarSMS($data){

    $retorno = "";

    $url = "http://107.20.199.106/sms/1/text/query";



    $ch = curl_init();

    $data = http_build_query($data);

    $getUrl = $url."?".$data;

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    curl_setopt($ch, CURLOPT_URL, $getUrl);

    curl_setopt($ch, CURLOPT_TIMEOUT, 80);

 

    $response = curl_exec($ch);

     

    if(curl_error($ch)){

    	//echo '{ "Request Error:' . curl_error($ch);

        curl_close($ch);

		$error_retorno = 99;

		return "ERROR - NO SE LOGRO CONEXION";

    }

    else

    {

		$json_decodificado = json_decode($response, true);

		$error_retorno = intval($json_decodificado["messages"][0]["status"]["id"]);

    	curl_close($ch);
    	//echo $response;

    }



    if($error_retorno == 3 || $error_retorno == 7 || $error_retorno == 26 || $error_retorno == 2 || $error_retorno == 5){

        $retorno = "OK";

    }

    else{

        $retorno =  "ERROR DE ENVIO: Codigo: ".$error_retorno." -> ".$json_decodificado["messages"][0]["status"]["id"]." - ".$json_decodificado["messages"][0]["status"]["name"];

        echo $retorno;

    }

    return $retorno;

}




/*


$cel = "3138746366";

$mensaje = "Mi mensaje de correo";

//

if(strlen($cel) == 10){

    $cel = "+57".$cel;

}

//

$data = array(

    "to"=> $cel,

    "text"=> $mensaje,

    "username"=>"videoexpr20",

    "password"=>"317@Sofonias",

);



$json_decodificado = json_decode(nuevo_enviarSMS($data), true); //

echo "Codigo de error o exito: ".intval($json_decodificado["messages"][0]["status"]["id"]);



?><hr /><hr /><hr /><?

var_dump($json_decodificado);

?><hr /><hr /><hr /><?

var_dump($json_decodificado["messages"][0]["status"]);



exit;


*/










//Si es un usuario externo o cliente o proveedor NO mostrar.

if($_SESSION["perfil"] == 3 || $_SESSION["perfil"] == 4 || $_SESSION["perfil"] == 160)

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



function envioCurl($url, $parametros) {

    echo "URL: ".$url;

    echo "<br />Parametros: ".$parametros;

    //

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_POST, TRUE);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $parametros);

    $postResult = curl_exec($ch);

    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close ($ch);





    echo "<br />PostResult: ".$postResult;

    echo "<br />ResponseCode: ".$response_code;



    return $postResult;

}







$PSN = new DBbase_Sql;

if($opc == 1)

{

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



		//

		$idCampana = soloNumeros($_POST["idCampana"]);

		if(isset($_POST["mensajecorreo"]))

		{

			//

			$array_usr_sent = array();

			//

			

			$mensajecorreo = eliminarInvalidos($_POST["mensajecorreo"]);

			//$mensajecorreo = urlencode($mensajecorreo);

			$mensajecorreo = $mensajecorreo;

			

			if(isset($_POST['listaasociar']))

			{

				$listas = implode(",", $_POST['listaasociar']);

			}

			

			

			if($mensajecorreo != "")

			{

				//

				$audit_usuario = $_SESSION["id"];

				$audit_ip = $_SERVER['REMOTE_ADDR'];

				//

				$PSNT = new DBbase_Sql;

				//ENVIO A LISTAS

				if(count($_POST['listaasociar']) > 0 && trim($listas) != "" && $listas != ",")

				{

					$sql= "SELECT sms_usuarios.nombres, sms_usuarios.celular ";

					$sql.=" FROM sms_grupos, sms_asociacion, sms_usuarios";

					$sql.=" WHERE sms_grupos.id IN (".$listas.") AND sms_asociacion.id_grupo = sms_grupos.id AND sms_usuarios.id = sms_asociacion.id_usuario AND celular != ''";

					

					$PSN->query($sql);

					$num=$PSN->num_rows();

					if($num > 0)

					{

						while($PSN->next_record())

						{

							if(!in_array($PSN->f('celular'), $array_usr_sent))

							{

								if($saldo_actual <= 0){

									$sms_celular = $PSN->f('celular');

									$sql = "INSERT INTO sms_historico (idCampana, tipo, error, fecha, audit_usuario, audit_fecha, audit_ip, mensaje) ";

									$sql .= " VALUES('".$idCampana."', 2, 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."', '".$mensajecorreo."')";

									$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";

									$PSNT->query($sql);

									echo "<strong>Saldo INSUFICIENTE (#".$sms_celular.")...</strong><br />";

								}
								else
								{

									$sms_celular = $PSN->f('celular');
                                    $cel = $sms_celular;
                                    $mensaje = $mensajecorreo;
                                    //
                                    if(strlen($cel) == 10){
                                        $cel = "+57".$cel;
                                    }

                                    //
                                    if(strlen($cel) == 13){

                                        $data = array(

                                            "to"=> $cel,

                                            "text"=> $mensaje,

                                            "username"=>"videoexpr20",

                                            "password"=>"317@Sofonias",

                                        );

                                        

                                        

                                        $codigo_retorno = nuevo_enviarSMS($data); //json_decode

    									if($codigo_retorno == "OK")

    									{

    										$sql = "INSERT INTO sms_historico (idCampana, tipo, mensajes, fecha, audit_usuario, audit_fecha, audit_ip, mensaje) ";

    										$sql .= " VALUES('".$idCampana."', 2, -1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."', '".$mensajecorreo."')";

    										$sql .= " ON DUPLICATE KEY UPDATE mensajes = (mensajes-1)";

    										$PSNT->query($sql);

    										//

    										$contadorOk++;

    										$saldo_actual--;

    										$array_usr_sent[] = $sms_celular;

    									}
    									else
    									{

    										$sql = "INSERT INTO sms_historico (idCampana, tipo, error, fecha, audit_usuario, audit_fecha, audit_ip, mensaje) ";

    										$sql .= " VALUES('".$idCampana."', 2, 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."', '".$mensajecorreo."')";

    										$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";

    										$PSNT->query($sql);

    										//

    										if($retorno[0] < 0)

    										{

    											echo "<br /><strong><u>Error:</u></strong> ".$codigo_retorno." - Celular: ".$sms_celular;

    										}

    										$contadorError++;

    									}							

                                    }

                                    else{

										echo "<br /><strong><u>Error:</u></strong> Numero celular no valido - Celular: ".$sms_celular;

                                    }//Fin comprobación

                                    

								}

							}

							//echo $PSN->f('nombres');

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

					$sql= "SELECT sms_usuarios.celular ";

					$sql.=" FROM sms_usuarios";

					$sql.=" WHERE id IN (".htmlspecialchars($_POST['mensajeusumanual']).") AND celular != ''";

					

					$PSN->query($sql);

					$num=$PSN->num_rows();

					if($num > 0)

					{

						while($PSN->next_record())

						{

							if(!in_array($PSN->f('celular'), $array_usr_sent))

							{

								if($saldo_actual <= 0){

									$sms_celular = $PSN->f('celular');

									$sql = "INSERT INTO sms_historico (idCampana, tipo, error, fecha, audit_usuario, audit_fecha, audit_ip, mensaje) ";

									$sql .= " VALUES('".$idCampana."', 2, 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."', '".htmlspecialchars($_POST['mensajeusumanual'])."')";

									$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";

									$PSNT->query($sql);

									echo "<strong>Saldo INSUFICIENTE (#".$sms_celular.")...</strong><br />";

								}

								else

								{

									$sms_celular = $PSN->f('celular');

                                    $cel = $sms_celular;

                                    $mensaje = $mensajecorreo;

                                    //

                                    if(strlen($cel) == 10){

                                        $cel = "+57".$cel;

                                    }

                                    //

                                    if(strlen($cel) == 13){

                                        $data = array(

                                            "to"=> $cel,

                                            "text"=> $mensaje,

                                            "username"=>"videoexpr20",

                                            "password"=>"317@Sofonias",

                                        );

                                        

                                        

                                        $codigo_retorno = nuevo_enviarSMS($data); //json_decode

    									if($codigo_retorno == "OK")

    									{

    										$sql = "INSERT INTO sms_historico (idCampana, tipo, mensajes, fecha, audit_usuario, audit_fecha, audit_ip, mensaje) ";

    										$sql .= " VALUES('".$idCampana."', 2, -1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."', '".$mensaje."')";

    										$sql .= " ON DUPLICATE KEY UPDATE mensajes = (mensajes-1)";

    										$PSNT->query($sql);

    										//

    										$contadorOk++;

    										$saldo_actual--;

    										$array_usr_sent[] = $sms_celular;

    									}

    									else

    									{

    										$sql = "INSERT INTO sms_historico (idCampana, tipo, error, fecha, audit_usuario, audit_fecha, audit_ip, mensaje) ";

    										$sql .= " VALUES('".$idCampana."', 2, 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."', '".$mensaje."')";

    										$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";

    										$PSNT->query($sql);

    										//

    										if($retorno[0] < 0)

    										{

    											echo "<br /><strong><u>Error:</u></strong> ".$codigo_retorno." - Celular: ".$sms_celular;

    										}

    										$contadorError++;

    									}							

                                    }

                                    else{

										echo "<br /><strong><u>Error:</u></strong> Numero celular no valido - Celular: ".$sms_celular;

                                    }//Fin comprobación								    

								    

								    

								    

									/*$sms_celular = $PSN->f('celular');

									//$xml = file_get_contents("https://sistemasmasivos.com/itcloud/api/sendsms/send.php?user=".urlencode("gerencia@videoexpress.org")."&password=EKxvhsA64L&GSM=".$sms_celular."&SMSText=".$mensajecorreo);

                                        $cel = $sms_celular;

                                        $mensaje = $mensajecorreo;

                                        $urlSms        = "https://sistemasmasivos.com/itcloud/api/sendsms/send.php?";

                                        $parametrosms="user=gerencia@videoexpress.org&password=EKxvhsA64L&SMSText=$mensaje&GSM=$cel";

                                        $xml = envioCurl($urlSms, $parametrosms);									

									//

									$retorno = explode(",", $xml);

									if(trim($retorno[1]) > 0 && trim($retorno[1]) != "")

									{

										$sql = "INSERT INTO sms_historico (idCampana, tipo, mensajes, fecha, audit_usuario, audit_fecha, audit_ip) ";

										$sql .= " VALUES('".$idCampana."', 2, -1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";

										$sql .= " ON DUPLICATE KEY UPDATE mensajes = (mensajes-1)";

										$PSNT->query($sql);

										//

										$contadorOk++;

										$saldo_actual--;

										$array_usr_sent[] = $sms_celular;

									}

									else

									{

										$sql = "INSERT INTO sms_historico (idCampana, tipo, error, fecha, audit_usuario, audit_fecha, audit_ip) ";

										$sql .= " VALUES('".$idCampana."', 2, 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";

										$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";

										$PSNT->query($sql);

										//

										if($retorno[0] < 0)

										{

											echo "<strong><u>Error:</u></strong> ";

											switch($retorno[0])

											{

												case -3:

													echo "<strong>Saldo INSUFICIENTE (#".$sms_celular.")...</strong><br />";

													break;

												case -4:

													echo "<strong>Celular INVALIDO (#".$sms_celular.")...</strong><br />";

													break;

												case -5:

													echo "<strong>Mensaje invalido  (#".$sms_celular.")...</strong><br />";

													break;

												case -6:

													echo "<strong>Sistema en mantenimiento, perdone los inconvenientes  (#".$sms_celular.")...</strong><br />";

													break;

												default:

													echo "<strong>Error de autenticacion  (Contacto Cel  #".$sms_celular.")...</strong><br />";

													break;

											}

										}

										$contadorError++;

									}*/							

								}

							}

							//echo $PSN->f('nombres');

						}

					}

				}//Fin correos manuales

				/*

				*

				*	ENVIO A CLIENES

				*

				*/

				if(isset($_POST['idCliente']) && trim($_POST['idCliente']) != "" && soloNumeros($_POST['idCliente']) != "" && soloNumeros($_POST['idCliente']) != "0")

				{

					$sql= "SELECT id, nombre, celular ";

					$sql.=" FROM cliente";

					if($_POST['idCliente'] == -99){

						$sql.=" WHERE celular != ''";

					}

					else{

						$sql.=" WHERE id = '".soloNumeros($_POST['idCliente'])."' AND celular != ''";

					}

					$PSN->query($sql);

					if($PSN->num_rows() > 0)

					{

						if($PSN->next_record())

						{

							if(!in_array($PSN->f('celular'), $array_usr_sent))

							{

								if($saldo_actual <= 0){

									$sms_celular = $PSN->f('celular');

									$sql = "INSERT INTO sms_historico (idCampana, tipo, error, fecha, audit_usuario, audit_fecha, audit_ip, mensaje) ";

									$sql .= " VALUES('".$idCampana."', 2, 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."', '".$mensajecorreo."')";

									$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";

									$PSNT->query($sql);

									echo "<strong>Saldo INSUFICIENTE (#".$sms_celular.")...</strong><br />";

								}

								else

								{

									$sms_celular = $PSN->f('celular');

                                    $cel = $sms_celular;

                                    $mensaje = $mensajecorreo;

                                    //

                                    if(strlen($cel) == 10){

                                        $cel = "+57".$cel;

                                    }

                                    //

                                    if(strlen($cel) == 13){

                                        $data = array(

                                            "to"=> $cel,

                                            "text"=> $mensaje,

                                            "username"=>"videoexpr20",

                                            "password"=>"317@Sofonias",

                                        );

                                        

                                        

                                        $codigo_retorno = nuevo_enviarSMS($data); //json_decode

    									if($codigo_retorno == "OK")

    									{

    										$sql = "INSERT INTO sms_historico (idCampana, tipo, mensajes, fecha, audit_usuario, audit_fecha, audit_ip, mensaje) ";

    										$sql .= " VALUES('".$idCampana."', 2, -1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."', '".$mensajecorreo."')";

    										$sql .= " ON DUPLICATE KEY UPDATE mensajes = (mensajes-1)";

    										$PSNT->query($sql);

    										//

    										$contadorOk++;

    										$saldo_actual--;

    										$array_usr_sent[] = $sms_celular;

    									}

    									else

    									{

    										$sql = "INSERT INTO sms_historico (idCampana, tipo, error, fecha, audit_usuario, audit_fecha, audit_ip, mensaje) ";

    										$sql .= " VALUES('".$idCampana."', 2, 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."', '".$mensajecorreo."')";

    										$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";

    										$PSNT->query($sql);

    										//

    										if($retorno[0] < 0)

    										{

    											echo "<br /><strong><u>Error:</u></strong> ".$codigo_retorno." - Celular: ".$sms_celular;

    										}

    										$contadorError++;

    									}							

                                    }

                                    else{

										echo "<br /><strong><u>Error:</u></strong> Numero celular no valido - Celular: ".$sms_celular;

                                    }//Fin comprobación				

								    

									/*$sms_celular = $PSN->f('celular');

									//$xml = file_get_contents("https://sistemasmasivos.com/itcloud/api/sendsms/send.php?user=".urlencode("gerencia@videoexpress.org")."&password=EKxvhsA64L&GSM=".$sms_celular."&SMSText=".$mensajecorreo);

									//

									$cel = $sms_celular;

                                    $mensaje = $mensajecorreo;

                                    $urlSms        = "https://sistemasmasivos.com/itcloud/api/sendsms/send.php?";

                                        $parametrosms="user=gerencia@videoexpress.org&password=EKxvhsA64L&SMSText=$mensaje&GSM=$cel";

                                        $xml = envioCurl($urlSms, $parametrosms);									

									//

									$retorno = explode(",", $xml);

									if(trim($retorno[1]) > 0 && trim($retorno[1]) != "")

									{

										$sql = "INSERT INTO sms_historico (idCampana, tipo, mensajes, fecha, audit_usuario, audit_fecha, audit_ip) ";

										$sql .= " VALUES('".$idCampana."', 2, -1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";

										$sql .= " ON DUPLICATE KEY UPDATE mensajes = (mensajes-1)";

										$PSNT->query($sql);

										//

										$contadorOk++;

										$saldo_actual--;

										$array_usr_sent[] = $sms_celular;									

									}

									else

									{

										$sql = "INSERT INTO sms_historico (idCampana, tipo, error, fecha, audit_usuario, audit_fecha, audit_ip) ";

										$sql .= " VALUES('".$idCampana."', 2, 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";

										$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";

										$PSNT->query($sql);

										//

										if($retorno[0] < 0)

										{

											echo "<strong><u>Error:</u></strong> ";

											switch($retorno[0])

											{

												case -3:

													echo "<strong>Saldo INSUFICIENTE (#".$sms_celular.")...</strong><br />";

													break;

												case -4:

													echo "<strong>Celular INVALIDO (#".$sms_celular.")...</strong><br />";

													break;

												case -5:

													echo "<strong>Mensaje invalido  (#".$sms_celular.")...</strong><br />";

													break;

												case -6:

													echo "<strong>Sistema en mantenimiento, perdone los inconvenientes  (#".$sms_celular.")...</strong><br />";

													break;

												default:

													echo "<strong>Error de autenticacion  (Contacto de cliente cel #".$sms_celular.")...</strong><br />";

													break;

											}

										}

										$contadorError++;

									}*/							

								}								

							}

						}

					}

					

					$sql= "SELECT sms_usuarios.celular ";

					$sql.=" FROM sms_usuarios";

					if($_POST['idCliente'] == -99){

						$sql.=" WHERE idCliente != 0 AND celular != ''";

					}

					else{

						$sql.=" WHERE idCliente = '".soloNumeros($_POST['idCliente'])."' AND celular != ''";

					}

					$PSN->query($sql);

					$num=$PSN->num_rows();

					if($num > 0)

					{

						while($PSN->next_record())

						{

							if(!in_array($PSN->f('celular'), $array_usr_sent))

							{

								if($saldo_actual <= 0){

									$sms_celular = $PSN->f('celular');

									$sql = "INSERT INTO sms_historico (idCampana, tipo, error, fecha, audit_usuario, audit_fecha, audit_ip, mensaje) ";

									$sql .= " VALUES('".$idCampana."', 2, 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."', '".$mensajecorreo."')";

									$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";

									$PSNT->query($sql);

									echo "<strong>Saldo INSUFICIENTE (#".$sms_celular.")...</strong><br />";

								}
								else
								{
									$sms_celular = $PSN->f('celular');
                                    $cel = $sms_celular;
                                    $mensaje = $mensajecorreo;
                                    //

                                    if(strlen($cel) == 10){

                                        $cel = "+57".$cel;

                                    }

                                    //

                                    if(strlen($cel) == 13){

                                        $data = array(

                                            "to"=> $cel,

                                            "text"=> $mensaje,

                                            "username"=>"videoexpr20",

                                            "password"=>"317@Sofonias",

                                        );

                                        

                                        

                                        $codigo_retorno = nuevo_enviarSMS($data); //json_decode

    									if($codigo_retorno == "OK")

    									{

    										$sql = "INSERT INTO sms_historico (idCampana, tipo, mensajes, fecha, audit_usuario, audit_fecha, audit_ip, mensaje) ";

    										$sql .= " VALUES('".$idCampana."', 2, -1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."', '".$mensajecorreo."')";

    										$sql .= " ON DUPLICATE KEY UPDATE mensajes = (mensajes-1)";

    										$PSNT->query($sql);

    										//

    										$contadorOk++;

    										$saldo_actual--;

    										$array_usr_sent[] = $sms_celular;

    									}

    									else

    									{

    										$sql = "INSERT INTO sms_historico (idCampana, tipo, error, fecha, audit_usuario, audit_fecha, audit_ip, mensaje) ";

    										$sql .= " VALUES('".$idCampana."', 2, 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."', '".$mensajecorreo."')";

    										$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";

    										$PSNT->query($sql);

    										//

    										if($retorno[0] < 0)

    										{

    											echo "<br /><strong><u>Error:</u></strong> ".$codigo_retorno." - Celular: ".$sms_celular;

    										}

    										$contadorError++;

    									}							

                                    }

                                    else{

										echo "<br /><strong><u>Error:</u></strong> Numero celular no valido - Celular: ".$sms_celular;

                                    }//Fin comprobación								    

								    

								    

								    

								    

									/*$sms_celular = $PSN->f('celular');

									//$xml = file_get_contents("https://sistemasmasivos.com/itcloud/api/sendsms/send.php?user=".urlencode("gerencia@videoexpress.org")."&password=EKxvhsA64L&GSM=".$sms_celular."&SMSText=".$mensajecorreo);

									//

                                        $cel = $sms_celular;

                                        $mensaje = $mensajecorreo;

                                        $urlSms        = "https://sistemasmasivos.com/itcloud/api/sendsms/send.php?";

                                        $parametrosms="user=gerencia@videoexpress.org&password=EKxvhsA64L&SMSText=$mensaje&GSM=$cel";

                                        $xml = envioCurl($urlSms, $parametrosms);																		

									$retorno = explode(",", $xml);

									if(trim($retorno[1]) > 0 && trim($retorno[1]) != "")

									{

										$sql = "INSERT INTO sms_historico (idCampana, tipo, mensajes, fecha, audit_usuario, audit_fecha, audit_ip) ";

										$sql .= " VALUES('".$idCampana."', 2, -1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";

										$sql .= " ON DUPLICATE KEY UPDATE mensajes = (mensajes-1)";

										$PSNT->query($sql);

										//

										$contadorOk++;

										$saldo_actual--;

										$array_usr_sent[] = $sms_celular;									

									}

									else

									{

										$sql = "INSERT INTO sms_historico (idCampana, tipo, error, fecha, audit_usuario, audit_fecha, audit_ip) ";

										$sql .= " VALUES('".$idCampana."', 2, 1, '".date("Y-m-d")."', '".$audit_usuario."', NOW(), '".$audit_ip."')";

										$sql .= " ON DUPLICATE KEY UPDATE error = (error+1)";

										$PSNT->query($sql);

										//

										if($retorno[0] < 0)

										{

											echo "<strong><u>Error:</u></strong> ";

											switch($retorno[0])

											{

												case -3:

													echo "<strong>Saldo INSUFICIENTE (#".$sms_celular.")...</strong><br />";

													break;

												case -4:

													echo "<strong>Celular INVALIDO (#".$sms_celular.")...</strong><br />";

													break;

												case -5:

													echo "<strong>Mensaje invalido  (#".$sms_celular.")...</strong><br />";

													break;

												case -6:

													echo "<strong>Sistema en mantenimiento, perdone los inconvenientes  (#".$sms_celular.")...</strong><br />";

													break;

												default:

													echo "<strong>Error de autenticacion  (Contacto de cliente cel #".$sms_celular.")...</strong><br />";

													break;

											}

										}

										$contadorError++;

									}*/							

								}

							}

							//echo $PSN->f('nombres');

						}

					}

				}//Fin correos clientes

			}

			

			if($contadorOk > 0)

			{

				?><center><h1>.MENSAJES ENVIADOS CON EXITO: <u><?=$contadorOk; ?></u>.</h1></center><?

			}

			if($contadorError > 0)

			{

				?><center><h1>.MENSAJES EN ERROR: <?=$contadorError; ?>.</h1></center><?

			}







			//

			//

			//



			/*

			$sql = 'insert into sms_usuarios (

				nombres,

				email,

				celular,

				celular2,

				audit_usuario,

				audit_fecha,

				audit_ip

			) ';



			$sql .= 'values (

				"'.$nombres.'",

				"'.$email.'",

				"'.$celular.'",

				"'.$celular2.'",

				"'.$audit_usuario.'",

				NOW(),

				"'.$audit_ip.'"

			)';



			$ultimoQuery = $PSN->query($sql);

			$ultimoId = mysql_insert_id();



			if(isset($_POST['listaasociar']))

			{

				if (is_array($_POST['listaasociar']))

				{

					foreach($_POST['listaasociar'] as $value)

					{

						if(soloNumeros($value) != "")

						{

							$sql = "REPLACE INTO sms_asociacion (id_grupo, id_usuario) VALUES (".soloNumeros($value).",".$ultimoId.")";

							$ultimoQuery = $PSN->query($sql);

						}

					}

				}

				else

				{

					//echo $value;

				}

			}

			

			*/

			?>

			<SCRIPT LANGUAGE="JavaScript">

			//alert("Se ha ENVIADO correctamente el usuario de mensajeria!!!");

			//window.location.href= "index.php?doc=sms_usuarios&opc=2&id=<?=$ultimoId; ?>";

			</script>

			<?

		}



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

			echo '<script type="text/javascript" src="scripts/jquery-1.7.1.min.js"></script>'."\n";

			echo '<script type="text/javascript" src="scripts/jquery-ui/js/jquery-1.7.1.min.js"></script>'."\n";

			echo '<script type="text/javascript" src="scripts/jquery-ui/js/jquery-ui-1.8.17.custom.min.js"></script>'."\n";

			echo '<script type="text/javascript" src="scripts/jquery.form.js"></script>'."\n";

			echo '<script type="text/javascript" src="scripts/jquery.validate.js"></script>'."\n";

			echo '<script type="text/javascript" src="scripts/jquery.validate.additional-methods.js"></script>'."\n";

			echo '<script type="text/javascript" src="scripts/jquery.tokeninput.js"></script>'."\n";

			echo '<script type="text/javascript" language="javascript" src="scripts/jquery.corner.js"></script>'."\n";

			echo '<link href="scripts/token-input.css" rel="stylesheet" type="text/css">';

            //$saldo_actual = 1;

			if($saldo_actual > 0)

			{

				?><div class="form-group">

                    <h2 class="alert alert-info text-center">.ENVIO DE MENSAJES SMS.</h2>

                     Usted actualmente cuenta con un saldo disponible de <u><?=$saldo_actual; ?></u> mensajes de texto.

                </div>



                <ul class="nav nav-tabs">

                    <li class="active"><a data-toggle="tab" href="#tab1">Datos generales</a></li>

                    <li><a data-toggle="tab" href="#tab2">Grupos para envio</a></li>

                    <li><a data-toggle="tab" href="#tab3">Contactos para envio</a></li>

                    <li><a data-toggle="tab" href="#tab4">Prospecto para envio</a></li>

                </ul>    

				

				<form action="index.php?doc=sms_envio&opc=1" method="post" id="envio_masivo_sms" name="envio_masivo_sms" onSubmit="return valorar()" class="form">



            <div class="row">

            <div class="tab-content">



            <div id="tab1" class="tab-pane fade in active">



                <div id="container">

                    <br />

					<div class="form-group">

                    <label class="form-label" for="idCampana">Campa&ntilde;a</label>

                    <select name="idCampana" class="form-control">

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



						

					<div class="form-group">

                        <label class="form-label" for="mensajecorreo">Mensaje</label>

                        <textarea name="mensajecorreo" id="mensajecorreo" cols="40" rows="6" class="form-control"><?=soloNumeros($_POST["mensajecorreo"]); ?></textarea><br /><i>Recuerde que debe tener m&aacute;ximo 160 caracteres.

							<br />

							Caracteres disponibles: <span id="contador_mensajes">160</span></i>

						</div>

					</div>

                </div>

            </div>



            <div id="tab2" class="tab-pane fade">

            <div id="container">

                <br />

					<div class="form-group">

                        <h3 class="text-center well">.GRUPOS.</h3>

					</div>



					<div class="form-group">

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

								<input type="checkbox" name="listaasociar[]" value="<?=$PSN->f('id'); ?>" class="form-control"><?=$PSN->f('nombre'); ?>

							</div><?

						}

					}

					?>

					</div>

				</div>

				</div>



            <div id="tab3" class="tab-pane fade">

                <div id="container">

                    <br />

                    <div class="form-group">

                        <label class="form-label" "mensajeusumanual">Contactos individuales:</label>

                        <input type="text" id="mensajeusumanual" name="mensajeusumanual" class="form-control" />

					</div>

				</div>

            </div>



            <div id="tab4" class="tab-pane fade">

                <div id="container">

                    <br />

                    <div class="form-group">

                        <label class="form-label" for="idCliente">Prospecto para envio</label>

                        <select name="idCliente" class="form-control">

								<option value="">Sin seleccionar</option>

								<option value="-99">TODOS LOS PROSPECTOS (OJO!)</option>

							<?

							$PSN = new DBbase_Sql;

							$sql= "SELECT cliente.id, cliente.nombre ";

								$sql.=" FROM cliente ";

                            //Si es un comercial solo puede agregar prospectos de si mismo

                            if($_SESSION["perfil"] == 162){

                                $sql.=" WHERE idComercial = '".$_SESSION["id"]."'";

                            }

                            //                

							$sql.=" ORDER BY nombre asc ";



							$PSN->query($sql);

							$num=$PSN->num_rows();

							if($num > 0)

							{

								while($PSN->next_record())

								{

									?><option value="<?=$PSN->f('id'); ?>" <?

									if(soloNumeros($_POST["idCliente"]) == $PSN->f('id'))

									{

										?>selected="selected"<? 

									}

									?>><?=$PSN->f('nombre'); ?></option><?

								}

							}

							?>

							</select>

				    </div>

                </div>

                </div>

                

                </div>

                </div>

                

                <div class="container">

                    <div class="row">

                        <center><input type="submit" name="button" value="Enviar mensaje" class="btn btn-success"> <a href="index.php?doc=main" class="btn btn-danger">Cerrar</a> </center>

                    </div>

                </div>

            </form>

				

					<script type="text/javascript">

						$(document).ready(function() {

							$("#mensajeusumanual").tokenInput("busqueda.php");



							$('#mensajecorreo').keyup(function () {

							  var max = 160;

							  var len = $(this).val().length;

							  if (len >= max) {

								$('#contador_mensajes').text('0.');

								$(this).val($(this).val().substring(0,max));

							  } else {

								var char = max - len;

								$('#contador_mensajes').text(char);

							  }

							});				

						});

						

						function valorar()

						{

							var value = $("#mensajecorreo").val();

							if (value.length < 10 || value.length > 160)

							{

								alert("El mensaje debe tener minimo 10 caracteres y maximo 160.");

								return false;

							}

							else

							{

								return true;

							}

						}

						

						window.onload = function(){

							//document.getElementById("defaultOpen").click();

						}

						</script>

						

				<?

			}

			else

			{

				?><h1>Usted no tiene saldo disponible, por favor pida una recarga para continuar.</h1><?

			}

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

	$sql= "SELECT sms_historico.*, usuario.nombre ";

	$sql.=" FROM sms_historico, usuario";

	//

	$sql.=" WHERE usuario.id = sms_historico.audit_usuario ";

	$sql.=" ORDER BY audit_fecha DESC";

	$PSN->query($sql);

	$num=$PSN->num_rows();



	$total_registros = $num;

	$total_paginas = ceil($total_registros / $registros); 



	$sql= "SELECT sms_historico.*, usuario.nombre, campana.nombre as nombrecam ";

	$sql.=" FROM usuario, sms_historico";

	$sql.=" LEFT JOIN campana ON campana.id = sms_historico.idCampana";

	//

	$sql.=" WHERE usuario.id = sms_historico.audit_usuario ";

	$sql.=" ORDER BY audit_fecha DESC LIMIT ".$inicio.", ".$registros;;

	$PSN->query($sql);

	$num=$PSN->num_rows();

	



	?><div class="container">

        <div class="row">

            <h2 class="text-center well">.HISTORICO SMS.</h2>

        </div>

            

        <div class="alert alert-warning">

               Usted actualmente cuenta con un saldo disponible de <u><?=intval($saldo_actual); ?></u> mensajes de texto.

        </div>

                

        <div class="row">

            <h2 class="text-center well">.Se encontraron <?=intval($num); ?> registros.</h2>

        </div>



        <table border="0" cellspacing="0" cellpadding="2"  align="center" class="table table-bordered" style="font-size:12px">

            <thead>    

				<tr>

				  <th align="center">No.</th>

				  <th align="center">Campa&nacute;a</th>

				  <th align="center">Fecha</th>

				  <th align="center">SMS Cargados</th>

				  <th align="center">SMS Enviados</th>

				  <th align="center">SMS en Error</th>

				  <th align="center">Usuario</th>

				  <th align="center">Mensaje enviado</th>

				</tr>

            </thead>

            <tbody><?

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

                    <td style="text-align:center"><?

                    if($tipo == 1)

                    {

	                    echo $PSN->f('mensajes');

                    }

                    ?></td>

                    <td style="text-align:center"><?

                    if($tipo == 2)

                    {

    	                echo $PSN->f('mensajes')*-1;

                    }

                    ?></td>

                    <td style="text-align:center"><?=$PSN->f('error');?></td>

                    <td><?=$PSN->f('nombre');?></td>

                    <td><textarea readonly><?=$PSN->f('mensaje');?></textarea></td>
                </tr>

				<?

				$contador++;

			}

		}		

		?></tbody>

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

    <?

}

?>