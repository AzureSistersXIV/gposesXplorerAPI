<?php

// CORS headers must be at the TOP
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Include utility functions
require_once "./utilities.php";

$sourceDir = getFormParameter('source');

if(!$sourceDir) {
    http_response_code(400);
    echo json_encode(['error' => 'Source directory is required.']);
    exit;
}

$dirPath = "../screenshots/{$sourceDir}";
if (!is_dir($dirPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Directory not found.']);
    exit;
}

$folders = array_filter(scandir($dirPath), function($folder) use ($dirPath) {
    return $folder !== '.' && $folder !== '..' && is_dir("{$dirPath}/{$folder}");
});

sort($folders, SORT_NATURAL | SORT_FLAG_CASE);

echo json_encode(['folders' => $folders]);