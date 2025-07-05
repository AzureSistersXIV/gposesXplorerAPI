<?php

// Set CORS headers and content type
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

require_once "./utilities.php";

$sourceDir = getQueryParameter('folder');

if (!$sourceDir) {
    http_response_code(400);
    echo json_encode(['error' => 'Source directory is required.']);
    exit;
}

$pathArray = explode('/', $sourceDir);
$zipName = array_pop($pathArray) . ".zip";

$dirPath = "../screenshots/{$sourceDir}";
$zipPath = "../share/" . implode('/', $pathArray);
$zipFullPath = $zipPath . '/' . $zipName;

if (!is_dir($dirPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Directory not found.']);
    exit;
}

// Ensure the output directory exists
if (!is_dir($zipPath)) {
    mkdir($zipPath, 0777, true);
}

// Define allowed image extensions
$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

$zip = new ZipArchive();

if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo json_encode(['error' => "Cannot create zip at $zipFullPath"]);
    exit;
}

$files = scandir($dirPath);

foreach ($files as $file) {
    $filePath = $dirPath . DIRECTORY_SEPARATOR . $file;

    if (is_dir($filePath)) {
        continue;
    }

    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    if (in_array($extension, $imageExtensions)) {
        $zip->addFile($filePath, $file);
    }
}

$zip->close();

echo json_encode(["message" => "Zip archive created", "file" => $zipName]);
