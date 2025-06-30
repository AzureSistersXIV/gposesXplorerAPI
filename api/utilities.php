<?php

require_once "./thumbimage.class.php";

/**
 * Get a query parameter from the URL.
 *
 * @param string $arg The name of the query parameter.
 * @return string|bool The value of the query parameter or false if not found.
 */
function getQueryParameter(string $arg): string|bool
{
    // Check if the query parameter exists in the URL
    if (isset($_GET) && array_key_exists($arg, $_GET)) {
        return $_GET[$arg];
    }
    return false;
}

/**
 * Get a form parameter from POST data or JSON input.
 *
 * @param string $arg The name of the form parameter.
 * @return mixed The value of the form parameter or false if not found.
 */
function getFormParameter(string $arg): mixed
{
    // Check if the form parameter exists in POST data
    if (isset($_POST) && array_key_exists($arg, $_POST)) {
        return $_POST[$arg];
    }

    // Read the raw input data
    $input = file_get_contents('php://input');
    // Decode the JSON input data
    $data = json_decode($input, true);
    // Check if the form parameter exists in the JSON input data
    if ($data !== null && array_key_exists($arg, $data)) {
        return $data[$arg];
    }

    return false;
}

// Helper function to scan sources.
function getSources(string $screenshotsDir): array {
    // Get all directories in the screenshots directory, excluding '.' and '..'
    $mainFolders = array_filter(
        scandir($screenshotsDir),
        function ($item) use ($screenshotsDir) {
            $path = "{$screenshotsDir}/{$item}";
            return $item !== '.' && $item !== '..' && is_dir($path);
        }
    );

    $resultFolders = [];

    foreach ($mainFolders as $folder) {
        $folderPath = "{$screenshotsDir}/{$folder}";
        // Get all subdirectories in the current main folder
        $subFolders = array_filter(
            scandir($folderPath),
            function ($item) use ($folderPath) {
                $subPath = "{$folderPath}/{$item}";
                return $item !== '.' && $item !== '..' && is_dir($subPath);
            }
        );

        if (!empty($subFolders)) {
            // Add each subfolder as "mainfolder/subfolder"
            foreach ($subFolders as $subfolder) {
                $resultFolders[] = "{$folder}/{$subfolder}";
            }
        } else {
            // If no subfolders, add the main folder itself
            $resultFolders[] = $folder;
        }
    }
    return $resultFolders;
}