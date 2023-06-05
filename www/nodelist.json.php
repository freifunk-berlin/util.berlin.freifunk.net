<?php

// Fetch the JSON data
$jsonUrl = 'https://hopglass.berlin.freifunk.net/nodes.json';
$jsonData = file_get_contents($jsonUrl);

// Output the JSON data
header('Content-Type: application/json');
echo $jsonData;
