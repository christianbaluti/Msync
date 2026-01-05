<?php
// api/image_proxy.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// ✅ HANDLE PREFLIGHT REQUEST
// This block correctly responds to the browser's permission check.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Define the absolute path to your main uploads directory.
$baseUploadsPath = realpath(__DIR__ . '/../uploads');

// Get the requested file path from the URL parameter.
$requestedFile = $_GET['file'] ?? '';

if (empty($requestedFile)) {
    http_response_code(400);
    die('File not specified.');
}

// Create the full, absolute path to the requested image.
$fullFilePath = realpath($baseUploadsPath . '/' . $requestedFile);

if (file_exists($fullFilePath) && is_readable($fullFilePath)) {
    // Get the file's MIME type (e.g., 'image/jpeg', 'image/png')
    $mimeType = mime_content_type($fullFilePath);

    // Set the appropriate headers to tell the browser it's an image
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($fullFilePath));
    header('Content-Disposition: inline'); // Display the image in the browser

    // Output the image file's contents
    readfile($fullFilePath);
    exit;
} else {
    // The file was not found
    http_response_code(404);
    die('Image not found.');
}
?>