<?php
//
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('default_socket_timeout', 900);
ini_set("memory_limit","256M");
ini_set("max_execution_time",0);

echo "aqui es";
exit;

/*
$address = "http://sistemasmasivos.com/";
$_REQUEST['port'] = 80;

  if (isset($_REQUEST['port']) and
      (!strlen($_REQUEST['port'])==0))
    $port=$_REQUEST['port'];
  else
    unset($port);
   
  if (isset($port) and
      ($socket=socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) and
      (socket_connect($socket, $address, $port)))
    {
      $text="Connection successful on IP $address, port $port";
      socket_close($socket);
    }
  else
    $text="Unable to connect<pre>".socket_strerror(socket_last_error())."</pre>";
   
  echo "<html><head></head><body>".
       $text.
       "</body></html>";


exit;

*/

$sms_celular = urlencode("3015735714");
$mensajecorreo = urlencode("PLaya MI mensaje de pruebaaaaaa");

$url = "http://sistemasmasivos.com/itcloud/api/sendsms/send.php?user=".urlencode("gerencia@videoexpress.org")."&password=EKxvhsA64L&GSM=".$sms_celular."&SMSText=".$mensajecorreo;

$lurl=get_fcontent($url);
echo"cid:".$lurl[0]."<BR>";


function get_fcontent( $url,  $javascript_loop = 0, $timeout = 5 ) {
    $url = str_replace( "&amp;", "&", urldecode(trim($url)) );

    $cookie = tempnam ("/tmp", "CURLCOOKIE");
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $ch, CURLOPT_ENCODING, "" );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
    curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
    curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
    $content = curl_exec( $ch );
    $response = curl_getinfo( $ch );
    curl_close ( $ch );

    if ($response['http_code'] == 301 || $response['http_code'] == 302) {
        ini_set("user_agent", "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");

        if ( $headers = get_headers($response['url']) ) {
            foreach( $headers as $value ) {
                if ( substr( strtolower($value), 0, 9 ) == "location:" )
                    return get_url( trim( substr( $value, 9, strlen($value) ) ) );
            }
        }
    }

    if (    ( preg_match("/>[[:space:]]+window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match("/>[[:space:]]+window\.location\=\"(.*)\"/i", $content, $value) ) && $javascript_loop < 5) {
        return get_url( $value[1], $javascript_loop+1 );
    } else {
        return array( $content, $response );
    }
}


echo "<br />HTTP normal con mensaje";

echo file_get_contents($url);

echo "<br />HTTPS sin mensaje";

echo file_get_contents($url);

echo "<br />HTTPS con mensaje";

echo file_get_contents($url);


echo  "<h1>PING</h1>";

function MYPING($url){
    echo "<br />PING a ".$url."...";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if($httpcode>=200 && $httpcode<300){
      echo 'worked';
    } else {
      echo "didn't work";
    }
}

MYPING("https://google.com");
MYPING("http://google.com");
MYPING("google.com");
MYPING("https://saturacolombia.org");
MYPING("https://sistemasmasivos.com");
MYPING("http://sistemasmasivos.com");
MYPING("sistemasmasivos.com");
MYPING("amoryrestauracion.co");

?>