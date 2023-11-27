<?php

// Query based redirect
// Example: https://util.berlin.freifunk.net/knoteninfo?knoten=Zwingli-Nord-2GHz&typ=wiki
// typ: wiki, monitor, owm, hopglass
//
// Path based redirect
// Example: https://ff.berlin/d/linie206-core
// /d/ -> documentation (wiki)
// /m/ -> map (hopglass)
// /s/ -> statistics (monitor)

// $path_elements = getPathElements($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);

$path = $_GET['path'];
$path_elements = explode("/", $path);

if ($_GET["knoten"]) {
  $knoten = ($_GET["knoten"] ?? "%");
  $typ = ($_GET["typ"] ?? "");
} else if (count($path_elements) == 2) {
  switch ($path_elements[0]) {
    case "d":
      $typ = "wiki";
      break;
    case "m":
      $typ = "hopglass";
      break;
    case "s":
      $typ = "monitor";
      break;
  }
  ;
  $knoten = $path_elements[1];
} else {
  die("Keine gültige Anfrage.");
}

$knoten = preg_replace("/\.olsr$/", "", $knoten);

if(preg_match('/[^A-Za-z0-9\\.\\-\\_]/', $knoten)) die("Ungültiger Knotenname.");


function getPathElements($request_uri, $script_name) {
  
  if (strpos($request_uri, $script_name) === 0) {
    $request_path = substr($request_uri, strlen($script_name));
  } else {
    $request_path = $request_uri;
  }
  
  $parsed_url = parse_url($request_path);
  $path = $parsed_url['path'];
  return explode('/', trim($path, '/'));
}


function getUrl($url) {
  $ctx = stream_context_create(["http" => ["method" => "GET"]]);
  $fp = fopen($url, "r", false, $ctx);
  if ($fp === false) return "";
  $res = stream_get_contents($fp);
  return $res;
}


function getWikiLink($knoten){
  $url = "https://wiki.freifunk.net/api.php?action=ask&query=[[Hat_Knoten%3A%3A".$knoten."]]|%3FModification%20date|sort%3DModification%20date|order%3Ddesc&format=json";
  $body = getUrl($url);
  $json = json_decode($body);
  foreach($json->query->results as $result) {
    return $result->fullurl;
  }
  return false;
}

$monurl = "https://monitor.berlin.freifunk.net/host.php?h=".$knoten;
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
