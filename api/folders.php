<?php

// Set CORS headers to allow cross-origin requests and specify content type
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Include utility functions (e.g., getFormParameter)
require_once "./utilities.php";

// Retrieve the 'source' parameter from the request (POST/GET)
$sourceDir = getFormParameter('source');

// If 'source' parameter is missing, return HTTP 400 with error message
if(!$sourceDir) {
    http_response_code(400);
    echo json_encode(['error' => 'Source directory is required.']);
    exit;
}

// Build the path to the target directory inside ../screenshots/
$dirPath = "../screenshots/{$sourceDir}";

// Check if the directory exists; if not, return HTTP 404 with error message
if (!is_dir($dirPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Directory not found.']);
    exit;
}

// Scan the directory for subfolders, filtering out '.' and '..'
$folders = array_filter(
    scandir($dirPath),
    function($folder) use ($dirPath) {
        // Only include directories, skip '.' and '..'
        return $folder !== '.' && $folder !== '..' && is_dir("{$dirPath}/{$folder}");
    }
);

$foldersData = [];
foreach ($folders as $key => $folder) {
    if(is_dir("../thumbnails/{$sourceDir}/{$folder}")) {
        $thumbnail = explorePath("../thumbnails/{$sourceDir}/{$folder}");
        sort($thumbnail);
        $foldersData[$folder] = array_key_exists(0, $thumbnail) ? $thumbnail[0] : "./assets/img/folder.png";
    } else {
        $foldersData[$folder] = "../assets/img/folder.png";
    }
}

// Sort the folder names in a natural, case-insensitive order
sort($folders, SORT_NATURAL | SORT_FLAG_CASE);

// Return the list of folders as a JSON response
echo json_encode($foldersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);