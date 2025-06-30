<?php

// CORS headers must be at the TOP
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Include utility functions
require_once "./utilities.php";

// Decode the JSON file containing SFW data
$json = json_decode(file_get_contents("sfw.json"), true);
$previousJson = $json;

// If the JSON data is null, initialize it as an empty array
if ($json === null) {
    $json = [];
}

// Get the list of folders in the screenshots directory
$repository = getSources('../screenshots');

$news = [];
foreach ($repository as $folder) {
    if(!array_key_exists($folder, $json)){
        $news[$folder] = false;
        $json[$folder] = false;
    }
}

// Sort the JSON data naturally and case-insensitively
ksort($json, SORT_NATURAL | SORT_FLAG_CASE);

if ($previousJson !== $json) {
    // If the JSON data has changed, save the updated JSON data back to the file
    file_put_contents("sfw.json", json_encode($json));
}

// Output the new artists as a JSON response
echo json_encode($news);