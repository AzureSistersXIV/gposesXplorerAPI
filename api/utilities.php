<?php

require_once "./thumbimage.class.php";

// Helper function to scan and build folder structure
function getFolders(string $screenshotsDir): array {
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

function exploreFolder(string $folder): array {
    $screenshotsDir = '../screenshots';
    $fullPath = "{$screenshotsDir}/{$folder}";
    $result = [];

    // Define allowed image extensions
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    // Helper function for recursion
    $scan = function ($dir, $relativePath = '') use (&$scan, $imageExtensions, $folder, $screenshotsDir, &$result) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $itemPath = "{$dir}/{$item}";
            $itemRelativePath = $relativePath === '' ? $item : "{$relativePath}/{$item}";
            if (is_dir($itemPath)) {
                $scan($itemPath, $itemRelativePath);
            } else {
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                if (in_array($ext, $imageExtensions)) {
                    // Return path relative to the input $folder
                    $result[] = "{$screenshotsDir}/{$folder}/{$itemRelativePath}";
                }
            }
        }
    };

    if (is_dir($fullPath)) {
        $scan($fullPath);
    }
    return $result;
}

/**
 * Get the full paths for an screenshot and its thumbnail.
 *
 * @param string $screenshot The relative path to the screenshot.
 * @return array An array containing the full paths to the screenshot and its thumbnail.
 * @throws Exception If the screenshot link is not found.
 */
function getFullPaths(string $screenshot): array
{
    // Set error reporting settings
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '/path/to/custom.log');

    $host = "../";
    $commPath = $screenshot;
    $thumbPath = str_replace("screenshots/", "thumbnails/", $screenshot);

    // Check if the screenshot file exists
    if (!file_exists("../" . $commPath)) {
        throw new Exception("screenshot link not found: {$commPath}");
    }

    $dirPath = "";
    // Split the thumbnail path into directories
    $words = explode("/", "../" . $thumbPath);
    foreach ($words as $key => $word) {
        $dirPath .= $word . ($key < count($words) - 1 ? "/" : "");
        // Create the directory if it does not exist
        if (!is_dir($dirPath) && $key < count($words) - 1) {
            mkdir($dirPath, 0755, false);
        }
    }

    // Check if the thumbnail file exists
    if (!file_exists("../" . $thumbPath)) {
        // Create the directory for the thumbnail if it does not exist
        $temp = explode("/", $thumbPath);
        unset($temp[count($temp) - 1]);
        $temp = "../" . implode("/", $temp);
        if (!is_dir($temp))
            mkdir($temp, 0777, false);

        // Create the thumbnail image
        $objThumbImage = new ThumbImage($host . $commPath);
        $objThumbImage->createThumb($host . $thumbPath, 250);
    }

    return ["{$host}{$commPath}", "{$host}{$thumbPath}"];
}

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