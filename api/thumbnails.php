<?php
// CORS headers must be at the TOP
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Include utility functions
require_once "./utilities.php";

// Get the 'screenshot' parameter from the form submission
$screenshot = getFormParameter("screenshot");

// Initialize an empty array to store the JSON response
$json = [];

if (!$screenshot) {
    // If 'screenshot' is not provided, get the 'screenshots' parameter
    $screenshots = getFormParameter("screenshots");

    // Loop through each screenshot and get its full path
    foreach ($screenshots as $screenshot) {
        $json[] = getFullPaths($screenshot);
    }
} else {
    try {
        // If 'screenshot' is provided, get its full path
        $json[] = getFullPaths($screenshot);
    } catch (Throwable $e) {
        // If an error occurs, return an error message
        $json = ["error" => "Failed to retrieve screenshot: " . $e->getMessage()];
    }
}

// Encode the array to JSON format and output it
echo json_encode($json);