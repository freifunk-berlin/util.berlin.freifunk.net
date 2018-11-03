<?php
$nodename = $_SERVER["REQUEST_URI"];
$nodename = preg_replace("/^\/update_node\//", "", $nodename);

$entityBody = file_get_contents('php://input');
$entityBody = preg_replace("/\"mac\"\:\"..\:..\:XX\:XX\:..\:..\"/", "\"mac\":\"redacted\"", $entityBody);
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
