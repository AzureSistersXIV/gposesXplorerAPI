<?php

// Set CORS headers to allow cross-origin requests and specify content type
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Include utility functions (e.g., for getting query parameters)
require_once "./utilities.php";

// Get the 'folder' query parameter from the request
$sourceDir = getQueryParameter('folder');

// If no folder is specified, return a 400 Bad Request error
if (!$sourceDir) {
    http_response_code(400);
    echo json_encode(['error' => 'Source directory is required.']);
    exit;
}

// Split the folder path into parts and prepare the zip file name
$pathArray = explode('/', $sourceDir);
$zipName = array_pop($pathArray) . ".zip";

// Build the full path to the source directory and the output zip file
$dirPath = "../screenshots/{$sourceDir}";
$zipPath = "../share/" . implode('/', $pathArray);
$zipFullPath = "{$zipPath}/{$zipName}";

$success = !file_exists($zipFullPath) ? createZip($dirPath, $zipPath, $zipFullPath) : $success = updateZipIfNeeded($dirPath, $zipPath, $zipFullPath);
echo json_encode(["success" => $success]);