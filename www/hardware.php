<?php

// https://util.berlin.freifunk.net/hardware?name=tl-wr842n-v3

// Cronjobs generate/fetch the list of available files from the
// buildbot server; this PHP file parses that list and generates
// a user-friendly download page tailored for one router.

// Cronjob (buildbot):
// #!/bin/bash
// PATH=/bin:/usr/bin
// cd /usr/local/src/www/htdocs/buildbot
// (find . -type f | cut -c 3- ; \
// grep "Firmware: git branch" unstable/*/*/VERSION.txt ; \
// find . -name "VERSION.txt" -printf "UNIXTIME %C@ %p\n" \
// ) | lzma > /usr/local/src/www/htdocs/buildbot/.files.lzma.new
// mv /usr/local/src/www/htdocs/buildbot/.files.lzma.new /usr/local/src/www/htdocs/buildbot/.files.lzma

// Cronjob (util.berlin):
// (cd /var/www/util.berlin.freifunk.net/www && curl -s https://buildbot.berlin.freifunk.net/buildbot/.files.lzma|lzcat|grep -v -F ".ipk">.files.txt.new && mv .files.txt.new .files.txt)

$hardware = ($_GET["name"] ?? "");
$complete = ($_GET["complete"] ?? "") === "true";

header("Content-Type: text/html; charset=utf-8");

// helper for array_filter
function grepArray($line) {
  global $search;
  return (strpos($line, $search)!==false);
}

// fetched from buildbot by cronjob
$bbfiles = file(".files.txt");

// filter files list; make sure we match the right files (ubnt-nano-m vs. ubnt-nano-m-xw)
$matchingFiles = [];
if(strlen($hardware)>50) die("Ungültiger Gerätename.");
$routerids = explode(",", $hardware);
foreach($routerids as $routerid) {
  if(preg_match('/[^A-Za-z0-9\\.\\-\\_]/', $routerid) || strlen($routerid)<5 || strlen($routerid)>25 ) die("Ungültiger Gerätename.");
  $search = $routerid."-squashfs"; // pre-0.2.0
  $matchingFiles = array_merge($matchingFiles, array_filter($bbfiles, "grepArray"));
  $search = $routerid."-factory";
  $matchingFiles = array_merge($matchingFiles, array_filter($bbfiles, "grepArray"));
  $search = $routerid."-sysupgrade";
  $matchingFiles = array_merge($matchingFiles, array_filter($bbfiles, "grepArray"));
}

$search = "VERSION.txt:Firmware: git branch";
$branchLines = array_filter($bbfiles, "grepArray");

$search = "UNIXTIME ";
$timeLines = array_filter($bbfiles, "grepArray");

//echo implode("<br>", $matchingFiles);

// convert files list into multi-dimensional array
foreach($matchingFiles as $path) {
  $path = trim($path);
  $ext = substr($path, -4);
  if($ext !== ".img" && $ext !== ".bin"  && $ext !== ".tar") continue; // 0.0.0 used .bin, ubnt-erx uses .tar
  if(strpos($path, "sysupgrade")!==false) {
    $imgType = "sysupgrade";
  } else if(strpos($path, "factory")!==false) {
    $imgType = "factory";
  } else {
    continue;
  }
  if(strpos($path, "-eu.bin")!==false) {
    $imgSubType = "eu";
  } else if(strpos($path, "-us.bin")!==false) {
    continue;
  } else {
    $imgSubType = "";
  }
  $matchingFile = explode("/", $path);
  // unstable/ramips/395/default_4MB/kathleen-0.2.0-beta+3232601-ramips-mt7620-ex2700-factory.bin
  // stable/0.2.0/ramips/default/kathleen-0.2.0-ramips-mt7620-wt3020-8M-factory.bin
  $releaseType = $matchingFile[0];
  $arch = $matchingFile[$releaseType=="stable"?2:1];
  $number = $matchingFile[$releaseType=="stable"?1:2];
  if($releaseType!="stable") $number = intval($number);
  $package = ($number!=="0.0.0") ? $matchingFile[3] : "default"; // 0.0.0 didn't have multiple package sets
  // unstable/ramips/400/VERSION.txt:Firmware: git branch "master"
  $search = "$releaseType/$arch/$number/VERSION.txt:Firmware: git branch";
  $branchNames = array_values(array_filter($branchLines, "grepArray"));
  if(sizeof($branchNames)>0) {
    $branchName = explode("\"", $branchNames[0])[1];
  } else {
    $branchName = $number;
  }
  // echo "$releaseType / $branchName / $number / $package / $imgType / $imgSubType / $path<br>";
  $images[$releaseType][$branchName][$number][$package][$imgType][$imgSubType] = $path;
}

// sort files array
ksort($images); // sort releaseTypes
foreach($images as $releaseType => $branchNames) {
  if($releaseType==="stable") {
    krsort($images[$releaseType]); // sort branchNames
  } else {
    ksort($images[$releaseType]); // sort branchNames
  }
  foreach($images[$releaseType] as $branchName => $numbers) {
    krsort($images[$releaseType][$branchName]); // sort numbers
    foreach($numbers as $number => $packages) {
      krsort($images[$releaseType][$branchName][$number]); // sort packages
    }
  }
}

//var_dump($images);

$bburl = "http://buildbot.berlin.freifunk.net/buildbot/";

echo "<html><head><title>Firmware-Images Freifunk Berlin</title></head>";
echo "<body><h2>Freifunk Berlin Firmware-Images für $routerids[0]...</h2>";
echo "<ul>";

$somethingListed = false;
foreach($images as $releaseType => $branchNames) {
  $haveReleaseHeadline = false;
  foreach($branchNames as $branchName => $numbers) {
    if($releaseType==="unstable" && $branchName!=="master" && !$complete) continue;
    if(!$haveReleaseHeadline) {
      $haveReleaseHeadline = true;
      $releaseTypeL = ($releaseType==="stable") ? "Releases" : "Development";
      echo "<li><b>$releaseTypeL</b>";
      if($releaseType==="stable") echo " (<a href=\"https://wiki.freifunk.net/Berlin:Firmware#Releases\">Info</a>)";
      echo "<ul>";
    }

    echo "<li><b>$branchName</b>";
    foreach($numbers as $number => $packages) {
      $haveBranchHeadline = false;
      foreach($packages as $package => $imgTypes) {
        if(!$haveBranchHeadline) {
          $haveBranchHeadline = true;
          // have to find arch from actual image path as arch strings have changed over time (i.e., "ar71xx" vs. "ar71xx-generic")
          $pathParts = explode("/", $imgTypes["sysupgrade"][""]);
          $arch = ($releaseType!=="stable") ? $pathParts[1] : $pathParts[2];
          // UNIXTIME 1478746672.5508087040 ./unstable/ramips/395/VERSION.txt
          // UNIXTIME 1478746672.5508087040 ./stable/0.2.0/ar71xx/VERSION.txt
          $search = ($releaseType!=="stable") ? "/unstable/$arch/$number/VERSION.txt" : "/stable/$branchName/$arch/VERSION.txt";
          $times = array_values(array_filter($timeLines, "grepArray"));
          if(sizeof($times)>0) {
            if($releaseType!=="stable") {
              echo " (<a href=\"https://buildbot.berlin.freifunk.net/builders/$arch/builds/$number\">$number</a>)";
            }
            $timestamp = explode(" ", $times[0])[1];
            echo " vom ".date("Y-m-d H:i:s", $timestamp);
          }
          echo "<ul>";
        }

        echo "<li>$package (<a href=\"https://wiki.freifunk.net/Berlin:Firmware#Image-Typen\">?</a>) - ";
        if(array_key_exists("factory", $imgTypes)) {
          echo "<a href=\"".$bburl.$imgTypes["factory"][""]."\">Erstinstallation</a>";
          if(array_key_exists("eu", $imgTypes["factory"])) {
            echo " (<a href=\"".$bburl.$imgTypes["factory"]["eu"]."\">EU-Version</a>)";
          }
          echo ", <a href=\"".$bburl.$imgTypes["sysupgrade"][""]."\">Aktualisierung</a>";
        } else {
          // Some routers, e.g., GL-AR150, have no special "factory" image
          echo "<a href=\"".$bburl.$imgTypes["sysupgrade"][""]."\">Download</a>";
        }
        echo "</li>";
        $somethingListed = true;
      }
      echo "</ul>";
      break;
    }
    echo "</li>";
    if($releaseType==="stable" && !$complete) break;
  }
  if($haveReleaseHeadline) {
    echo "</ul>";
    echo "</li>";
  }
}

if($complete && !$somethingListed) {
  echo "<li><i>Keine Images verfügbar.</i></li>";
}

echo "</ul>";

if(!$complete) {
  if($somethingListed) {
    echo "<ul><li><a href=\"/hardware?name=$hardware&complete=true\">Auch alte Releases und Development-Branches anzeigen</a></li></ul>";
  } else {
    echo "<ul><li>Für diese Hardware gibt es zur Zeit keine Unterstützung in Releases oder im Development-Master.<br/><a href=\"/hardware?name=$hardware&complete=true\">Auch Development-Branches anzeigen</a>.</li></ul>";
  }
}

echo "<ul>".
     "<li><b>Bitte Hinweise im Wiki-Artikel <a href=\"https://wiki.freifunk.net/Berlin:Firmware\">Berlin:Firmware</a> beachten!</b></li>".
     "<li><a href=\"https://wiki.freifunk.net/Berlin:Firmware:Howto\">Howto</a> zur Einrichtung der Firmware (Deutsch)</li>".
     "<li><a href=\"https://wiki.freifunk.net/Berlin:Firmware:En:Howto\">Howto</a> setup the Firmware (English)</li>".
     "</ul>".
     "zu <a href=\"https://berlin.freifunk.net/\">berlin.freifunk.net</a>".
     "</body></html>";

?>
