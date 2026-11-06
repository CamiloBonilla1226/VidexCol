<?php

$sms_celular = "3203184040";
$mensajecorreo = "Test curl mensaje";

$ch = curl_init();
curl_setopt($ch, CURLOPT_TIMEOUT, 0);
// set url
curl_setopt($ch, CURLOPT_URL, "https://sistemasmasivos.com/itcloud/api/sendsms/send.php?user=".urlencode("gerencia@videoexpress.org")."&password=EKxvhsA64L&GSM=".$sms_celular."&SMSText=".urlencode($mensajecorreo));
//return the transfer as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

if(FALSE === ($xml = curl_exec($ch))) {
    print "Mostrar el error sistemasmasivos.com con parametros:<br>";
    print curl_error($ch);
} else {
    print "Show answer domain sistemasmasivos.com con parametros:";
    var_dump($xml);
}

// close curl resource to free up system resources
curl_close($ch);


$ch = curl_init();
curl_setopt($ch, CURLOPT_TIMEOUT, 0);
// set url
curl_setopt($ch, CURLOPT_URL, "https://sistemasmasivos.com/itcloud/api/sendsms/send.php");
//return the transfer as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

if(FALSE === ($xml = curl_exec($ch))) {
    print "<br><br>Mostrar el error de sistemasmasivos.com sin parametros:<br>";
    print curl_error($ch);
} else {
    print "<br><br>Show answer domain sistemasmasivos.com sin parametros:";
    var_dump($xml);
}

// close curl resource to free up system resources
curl_close($ch);



$ch = curl_init();
curl_setopt($ch, CURLOPT_TIMEOUT, 0);
// set url
curl_setopt($ch, CURLOPT_URL, "https://namecheap.com");
//return the transfer as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

if(FALSE === ($xml = curl_exec($ch))) {
    print "Mostrar el error:<br>";
    print curl_error($ch);
} else {
    print "<p>show answer domain namecheap.com</p>";
    var_dump($xml);
}

// close curl resource to free up system resources
curl_close($ch);