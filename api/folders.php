<?php
// CORS headers must be at the TOP
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Include utility functions
require_once "./utilities.php";

// Load folder data from JSON file
$folderData = json_decode(file_get_contents("sfw.json"), true);

if (empty($folderData) || $folderData === null) {
    $folderData = [];
}

// Filter folders based on the 'isNsfw' query parameter
$folderData = array_filter($folderData, function($folder): bool{
    return $folder === (!getQueryParameter("isNsfw") || getQueryParameter("isNsfw") !== "true");
});

// Sort folders array naturally and case-insensitively
ksort($folderData, SORT_NATURAL | SORT_FLAG_CASE);

// If folder data is already available, return it
echo json_encode($folderData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);