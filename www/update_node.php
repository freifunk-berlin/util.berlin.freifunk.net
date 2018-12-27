<?php
$nodename = $_SERVER["REQUEST_URI"];
$nodename = preg_replace("/^\/update_node\//", "", $nodename);
if(strlen($nodename) > 50) die("Node name too long");

$entityBody = file_get_contents('php://input');
$entityBody = preg_replace("/\"mac\"\:\"..\:..\:XX\:XX\:..\:..\"/", "\"mac\":\"redacted\"", $entityBody);

$filename = "/var/opt/ffmapdata/" . str_replace(".", "%2E", urlencode($nodename)) . ".json";
file_put_contents($filename, $entityBody, LOCK_EX);

$options = array(
  'http' => array(
    'method'  => 'PUT',
    'content' => $entityBody,
    'header'=>  "Content-Type: application/json\r\n" .
                "Accept: application/json\r\n"
    )
);
$context  = stream_context_create( $options );
$result = file_get_contents( "http://api.openwifimap.net/update_node/".$nodename, false, $context );
foreach($http_response_header as $k=>$v) {
  if(preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out)) {
    $code = intval($out[1]);
    http_response_code(intval($out[1]));
    if($code === 404) {
      echo "Not found or invalid method";
    }
  }
}
echo $result;
?>
