<?php

// CORS headers must be at the TOP
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Include utility functions (e.g., getQueryParameter, explorePath)
require_once "./utilities.php";

// Load folder data from JSON file (sfw.json)
$folderData = json_decode(file_get_contents("sfw.json"), true);

// If the JSON file is empty or invalid, initialize as empty array
if (empty($folderData) || $folderData === null) {
    $folderData = [];
}

// Determine if grouping by folder is requested via 'folder' query parameter
$groupByFolder = getQueryParameter('folder') === "true";

// Filter folders based on the 'isNsfw' query parameter
$folderData = array_filter($folderData, function ($isNsfw): bool {
    // If isNsfw is not set or not "true", include only SFW (false) folders
    // If isNsfw is "true", include only NSFW (true) folders
    return $isNsfw === (!getQueryParameter("isNsfw") || getQueryParameter("isNsfw") !== "true");
});

// Initialize array to hold ordered file/folder data
$orderedFiles = [];

// Iterate over each folder in the filtered folder data
foreach ($folderData as $folderKey => $isNsfw) {
    $files = explorePath("../screenshots/{$folderKey}", true);
    foreach ($files as $file) {
        $modTime = filemtime($file);
        $folderName = basename(dirname($file));
        $fileName = basename($file);

        if ($groupByFolder) {
            $relativeFolder = getRelativeFolderPath($file, $folderName, $fileName);
            addFolderIfNotExists($orderedFiles, $folderName, $modTime, $relativeFolder, basename($file));
        } else {
            // 15 = strlen("../screenshots/")
            $relativeFolder = substr($file, 15, -strlen('/' . $fileName));
            addFile($orderedFiles, $fileName, $modTime, $relativeFolder);
        }
    }
}

// Sort the result array by modification time, descending (most recent first)
usort($orderedFiles, function ($a, $b) {
    return strtotime($b['modTime']) - strtotime($a['modTime']);
});

// Output the result as pretty-printed JSON
echo json_encode($orderedFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);