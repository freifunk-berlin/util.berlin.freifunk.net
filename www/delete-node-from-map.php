<?php

// $node = 'example-host';
$node = is_string($_GET["name"]) ? $_GET["name"] : "";

if (empty($node)) {
    echo "please provide a non empty node name";
} else {
    // API URL
    $url = 'http://api.openwifimap.net/update_node/' . $node . '.olsr';

    // Create a new cURL resource
    $ch = curl_init($url);

    // Setup payload
    $data = array(
        "hostname" => $node
    );
    $payload = json_encode($data);

    // Attach encoded JSON string to the POST fields
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // Set the content type to application/json
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));

    // Return response instead of outputting
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // set PUT method
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

    // Execute the PUT request
    $result = curl_exec($ch);

    // Close cURL resource
    curl_close($ch);

    // Output result
    echo $result;
}
