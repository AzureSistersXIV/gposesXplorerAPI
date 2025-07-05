<?php

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

if(!file_exists($zipFullPath)){
    createZip($dirPath, $zipPath, $zipFullPath);
}

if (file_exists($zipFullPath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="gposesXplorer_'.basename($zipFullPath).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($zipFullPath));
    readfile($zipFullPath);
    exit;
}