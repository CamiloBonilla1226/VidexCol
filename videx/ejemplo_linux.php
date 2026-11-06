<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);



function envioCurl($url, $parametros) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parametros);
    $postResult = curl_exec($ch);
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close ($ch);
    if ($response_code == '404') {
        echo "Pag&iacute;na no encontrada<br/>";
        return false;
    } else {
        echo $postResult;
        return true;
    }
}




$cel="573124907879";
$Fecha= date("Y-m-d H:i:s");
$mensaje="mensaje de prueba $Fecha  cel $cel . Gracias ";
   $urlSms        = "https://sistemasmasivos.com/itcloud/api/sendsms/send.php?";
   $parametrosms="user=gerencia@videoexpress.org&password=aaaaaaaaaaaaa&SMSText=$mensaje&GSM=$cel";
   $ResultadoEnvio=  envioCurl($urlSms, $parametrosms);


   if ($ResultadoEnvio>0)
     $ResultadoEnvio="Mensaje enviado ok";
  else  {
       switch($tipo)
         {
           case '-1': $ResultadoEnvio="Autenticación Fallida";    break;
           case '-2': $ResultadoEnvio="Fuera horario autorizado";    break;
           case '-3': $ResultadoEnvio="SIN creditos SMS ITC";    break;
           case '-4': $ResultadoEnvio="Eror en el numero destino";    break;
           case '-5': $ResultadoEnvio="Mensaje supera los caracteres ";    break;
           case '-6': $ResultadoEnvio="sistemas en mantenimiento ";    break;
           case '-7': $ResultadoEnvio="supera cantidad maxima de celulares ";    break;
           case '-8': $ResultadoEnvio="celular en lista de bloqueo";    break;

         }

  }

echo " <p> >> Fecha $Fecha cel $cel ResultadoEnvio $ResultadoEnvio\n ";





?>
