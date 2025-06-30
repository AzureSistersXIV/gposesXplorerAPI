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

// Scan the directory for files (thumbnails), filtering out '.' and '..' and directories
$thumbnails = array_filter(
    scandir($dirPath),
    function($thumbnail) use ($dirPath) {
        // Only include files, skip '.' and '..'
        return $thumbnail !== '.' && $thumbnail !== '..' && !is_dir("{$dirPath}/{$thumbnail}");
    }
);

// Sort the file names in a natural, case-insensitive order
sort($thumbnails, SORT_NATURAL | SORT_FLAG_CASE);

// Return the list of thumbnails as a JSON response
echo json_encode(['thumbnails' => $thumbnails]);