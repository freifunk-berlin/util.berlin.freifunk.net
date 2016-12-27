<?php

// https://util.berlin.freifunk.net/mapcenter?latlon=a,b&map=owm
// map: owm/osm/gmap

$coor = $_GET['latlon'];
$latlon = explode(",", $coor);
$map = $_GET['map'];

if(!is_numeric($latlon[0]) || !is_numeric($latlon[1])) die("Falsche Koordinaten.");

$latw = 0.004;
$lonw = 0.01;
$owmurl = "http://openwifimap.net/map.html#bbox=" . ($latlon[0] - ($latw/2)) . "," . ($latlon[1] - ($lonw/2)) . "," . ($latlon[0] + ($latw/2)) . "," . ($latlon[1] + ($lonw/2));
$osmurl = "http://www.openstreetmap.org/?mlat=$latlon[0]&mlon=$latlon[1]&zoom=16&layers=M";
$gmurl = "http://maps.google.com/maps?ll=$latlon[0],$latlon[1]&spn=0.01,0.01&t=m";

if($map == "owm") {
  $url = $owmurl;
} else if($map === "osm") {
  $url = $osmurl;
} else if($map === "gmap") {
  $url = $gmurl;
} else {
  echo "<body><a href=\"https://berlin.freifunk.net/\">berlin.freifunk.net</a><ul><li><a href=\"$owmurl\">OpenWifiMap.net</a></li><li><a href=\"$osmurl\">OpenStreetMap.org</a></li><li><a href=\"$gmurl\">GMaps</a></li></ul></body>";
  exit;
}

header("Location: ".$url);

?>
