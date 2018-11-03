<?php

// https://util.berlin.freifunk.net/knoteninfo?knoten=Zwingli-Nord-2GHz&typ=wiki
// typ: wiki, monitor, owm, hopglass

$knoten = ($_GET["knoten"] ?? "%");
$knoten = preg_replace("/\.olsr$/", "", $knoten);

if(preg_match('/[^A-Za-z0-9\\.\\-\\_]/', $knoten)) die("Ungültiger Knotenname.");

$typ = ($_GET["typ"] ?? "");

function getUrl($url) {
  $ctx = stream_context_create(["http" => ["method" => "GET"]]);
  $fp = fopen($url, "r", false, $ctx);
  if ($fp === false) return "";
  $res = stream_get_contents($fp);
  return $res;
}

function getWikiLink($knoten) {
  $url = "https://wiki.freifunk.net/api.php?action=ask&query=[[Hat_Knoten%3A%3A".$knoten."]]|%3FModification%20date|sort%3DModification%20date|order%3Ddesc&format=json";
  $body = getUrl($url);
  $json = json_decode($body);
  foreach($json->query->results as $result) {
    return $result->fullurl;
  }
  return false;
}

$monurl = "http://monitor.berlin.freifunk.net/host.php?h=".$knoten;
$owmurl = "https://openwifimap.net/#detail?node=".$knoten.".olsr";
$hgurl = "https://hopglass.berlin.freifunk.net/#!v:m;n:".$knoten.".olsr";

if($typ === "wiki") {
  $url = getWikiLink($knoten);
  if($url === false) {
    header("HTTP/1.0 404 Not Found");
    echo "<body><p>Kein Artikel zum Knoten im Wiki gefunden.</p>";
  } else {
    header("Location: ".$url);
    exit;
  }
} else if($typ === "monitor") {
  header("Location: ".$monurl);
  exit;
} else if($typ === "owm") {
  header("Location: ".$owmurl);
  exit;
} else if($typ === "hopglass") {
  header("Location: ".$hgurl);
  exit;
} else {
  echo "<body>";
}

echo "<i>$knoten</i> auf...";
echo "<ul>".
     "<li><a href=\"$owmurl\">openwifimap.net</a></li>".
     "<li><a href=\"$hgurl\">hopglass.berlin.freifunk.net</a></li>".
     "<li><a href=\"/knoteninfo?knoten=$knoten&typ=wiki\">wiki.freifunk.net</a></li>".
     "<li><a href=\"$monurl\">monitor.berlin.freifunk.net</a></li>".
     "</ul>".
     "zu <a href=\"https://berlin.freifunk.net/\">berlin.freifunk.net</a></body>";

?>
