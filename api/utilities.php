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

/**
 * Scan the screenshots directory and return all sources (folders and subfolders).
 *
 * @param string $screenshotsDir The path to the screenshots directory.
 * @return array List of folder paths (e.g., "mainfolder/subfolder" or "mainfolder").
 */
function getSources(string $screenshotsDir): array
{
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

/**
 * Get the full paths for a screenshot and its thumbnail, creating the thumbnail if needed.
 *
 * @param string $screenshot The relative path to the screenshot.
 * @return array Array with [screenshot path, thumbnail path].
 * @throws Exception If the screenshot does not exist.
 */
function getFullPaths(string $screenshot): array
{
    // Set error reporting settings
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '../logs/error.log');

    $host = "../";
    $screenPath = $screenshot;
    $thumbPath = str_replace("screenshots/", "thumbnails/", $screenshot);

    // Check if the screenshot file exists
    if (!file_exists("../" . $screenPath)) {
        throw new Exception(`Artwork link not found: {$screenPath}`);
    }

    $dirPath = "";
    // Split the thumbnail path into directories and create them if needed
    $words = explode("/", "../" . $thumbPath);
    foreach ($words as $key => $word) {
        $dirPath .= $word . ($key < count($words) - 1 ? "/" : "");
        // Create the directory if it does not exist
        if (!is_dir($dirPath) && $key < count($words) - 1) {
            mkdir($dirPath, 0755, false);
        }
    }

    // Check if the thumbnail file exists, create it if not
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

/**
 * Recursively explore a directory and return all image files.
 * Optionally remove unwanted files/directories.
 *
 * @param string $path The directory path to explore.
 * @param bool $remove Whether to remove unwanted files/directories.
 * @return array List of image file paths.
 * @throws Exception If the path is not a directory.
 */
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

            if (!empty($extension) && in_array(strtolower($extension), ["png", "jpg", "jpeg", "gif", "webp", "bmp"])) {
                // If it's an image file, add to the result
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

/**
 * Get the relative folder path from a file path.
 *
 * @param string $file The full file path.
 * @param string $folderName The folder name.
 * @param string $fileName The file name.
 * @return string The relative folder path.
 */
function getRelativeFolderPath($file, $folderName, $fileName)
{
    // 15 = strlen("../screenshots/")
    return substr($file, 15, -strlen($folderName . '/' . $fileName));
}

/**
 * Add folder info to $orderedFiles if not already present.
 * Includes preview image if available.
 *
 * @param array &$orderedFiles The array to add to.
 * @param string $folderName The folder name.
 * @param int $modTime The modification time.
 * @param string $relativeFolder The relative folder path.
 * @param string $preview The preview image filename.
 */
function addFolderIfNotExists(&$orderedFiles, $folderName, $modTime, $relativeFolder, $preview)
{
    $folderPath = substr($relativeFolder, 0, -1);

    foreach ($orderedFiles as &$item) {
        if ($item['name'] === $folderName) {
            // If the new modTime is more recent, update modTime and preview
            if (strtotime($item['modTime']) < $modTime) {
                $item['modTime'] = date('Y-m-d H:i:s', $modTime);
                $item['preview'] = is_dir("../thumbnails/{$folderPath}/{$folderName}") ? "thumbnails/{$folderPath}/{$folderName}/{$preview}" : "./assets/img/folder.png";
            }
            return;
        }
    }
    unset($item);

    $orderedFiles[] = [
        'name' => $folderName,
        'modTime' => date('Y-m-d H:i:s', $modTime),
        'folder' => $folderPath,
        // Use folder preview if exists, otherwise fallback to default image
        'preview' => is_dir("../thumbnails/{$folderPath}/{$folderName}") ? "thumbnails/{$folderPath}/{$folderName}/{$preview}" : "./assets/img/folder.png"
    ];
}

/**
 * Add file info to $orderedFiles.
 *
 * @param array &$orderedFiles The array to add to.
 * @param string $fileName The file name.
 * @param int $modTime The modification time.
 * @param string $relativeFolder The relative folder path.
 */
function addFile(&$orderedFiles, $fileName, $modTime, $relativeFolder)
{
    $orderedFiles[] = [
        'name' => $fileName,
        'modTime' => date('Y-m-d H:i:s', $modTime),
        'folder' => $relativeFolder,
    ];
}

function createZip($dirPath, $zipPath, $zipFullPath)
{
    // Check if the source directory exists; if not, return a 404 error
    if (!is_dir($dirPath)) {
        http_response_code(404);
        echo json_encode(['error' => 'Directory not found.']);
        exit;
    }

    // Ensure the output directory for the zip file exists; create it if it doesn't
    if (!is_dir($zipPath)) {
        mkdir($zipPath, 0777, true);
    }

    // Define the allowed image file extensions to include in the zip
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    // Create a new ZipArchive instance
    $zip = new ZipArchive();

    // Attempt to open (create/overwrite) the zip file; return 500 error if it fails
    if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        echo json_encode(['error' => "Cannot create ZIP at $zipFullPath"]);
        exit;
    }

    // Get a list of files in the source directory
    $files = scandir($dirPath);

    // Loop through each file in the directory
    foreach ($files as $file) {
        $filePath = $dirPath . DIRECTORY_SEPARATOR . $file;

        // Skip directories (only process files)
        if (is_dir($filePath)) {
            continue;
        }

        // Get the file extension and check if it's an allowed image type
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, $imageExtensions)) {
            // Add the image file to the zip archive
            $zip->addFile($filePath, $file);
        }
    }

    // Close the zip archive to finalize it
    $zip->close();

    return true;
}

function updateZipIfNeeded($dirPath, $zipPath, $zipFullPath)
{

    if (!file_exists($zipFullPath)) {
        http_response_code(404);
        echo json_encode(['error' => 'No ZIP file found.']);
        exit;
    }

    $zip = new ZipArchive();

    if ($zip->open($zipFullPath) === TRUE) {

        // Get a list of files in the source directory
        $files = scandir($dirPath);

        $filesToZip = array_filter($files, function ($file) {
            // Define the allowed image file extensions to include in the zip
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            // Get the file extension and check if it's an allowed image type
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return $file != "." && $file != ".." && in_array($extension, $imageExtensions);
        });

        if ($zip->numFiles != count($filesToZip)) {
            unlink($zipFullPath);
            return createZip($dirPath, $zipPath, $zipFullPath);
        }

    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to open ZIP file.']);
        exit;
    }
}