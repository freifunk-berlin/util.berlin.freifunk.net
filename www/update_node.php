<?php
$nodename = $_SERVER["REQUEST_URI"];
$nodename = preg_replace("/^\/update_node\//", "", $nodename);
if(strlen($nodename) > 50) die("Node name too long");

$entityBody = file_get_contents('php://input');
$entityBody = preg_replace("/\"mac\"\:\"..\:..\:XX\:XX\:..\:..\"/", "\"mac\":\"redacted\"", $entityBody);

$filename = "/var/opt/ffmapdata/" . str_replace(".", "%2E", urlencode($nodename));
file_put_contents($filename . ".json_new", $entityBody);
rename($filename . ".json_new", $filename . ".json");
if(!is_file($filename . ".ctime")) {
  touch($filename . ".ctime");
}
?>
