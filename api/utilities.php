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

function getFullPaths(string $screenshot): array
{
    // Set error reporting settings
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '../logs/error.log');

    $host = "../";
    $screenPath = $screenshot;
    $thumbPath = str_replace("screenshots/", "thumbnails/", $screenshot);

    
    if (!file_exists("../" . $screenPath)) {
        throw new Exception(`Artwork link not found: {$screenPath}`);
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
        $objThumbImage = new ThumbImage($host . $screenPath);
        $objThumbImage->createThumb($host . $thumbPath, 300);
    }

    return [$host . $screenPath, $host . $thumbPath];
}

function explorePath(string $path, bool $remove = false): array
{
    if (!is_dir($path)) {
        throw new Exception("The path provided is not a directory: " . $path);
    }

    $explored = [];
    // Get the list of files in the directory, excluding "." and ".."
    $files = array_filter(@scandir($path), function ($file) {
        return $file !== "." && $file !== "..";
    });

    // Files to remove.
    $toRemove = ["desktop.ini", "Thumbs.db", "@eaDir"];

    foreach ($files as $key => $file) {
        // Check if the file should be removed
        if (in_array($file, $toRemove) && $remove) {
            if (is_dir($path . "/" . $file)) {
                // Remove the directory and its contents
                system('rm -rf -- ' . escapeshellarg($path . "/" . $file), $retval);
            } else {
                // Remove the file
                unlink($path . "/" . $file);
            }
        } else {
            // Get the file extension
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            if (!empty($extension)) {
                $explored[] = $path . "/" . $file;
            } else {
                // If the file has no extension, explore it as a directory
                unset($files[$key]);
                $explored = array_merge($explored, explorePath($path . "/" . $file, $remove));
            }
        }
    }

    return $explored;
}