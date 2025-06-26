<?php
// CORS headers must be at the TOP
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Include utility functions
require_once "./utilities.php";

if(!getFormParameter("folder")) {
    // If no folder is specified, return an error
    echo json_encode(["error" => "No folder specified"]);
    exit;
}

$folder = getFormParameter("folder");

if(getFormParameter("subfolder")) {
    // If a subfolder is specified, append it to the folder path
    $folder .= "/" . getFormParameter("subfolder");
}

if(!is_dir("../screenshots/{$folder}")) {
    // If the specified folder does not exist, return an error
    echo json_encode(["error" => "Folder does not exist"]);
    exit;
}

echo json_encode([
    "folder" => exploreFolder($folder)
]);