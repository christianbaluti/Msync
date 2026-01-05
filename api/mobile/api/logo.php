<?php
header("Access-Control-Allow-Origin: *");

$imagePath = 'assets/logo.png';

if (file_exists($imagePath)) {
    // Get the file's extension to set the correct content type
    $fileExtension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

    // Determine the correct Content-Type header
    $contentType = '';
    switch ($fileExtension) {
        case 'png':
            $contentType = 'image/png';
            break;
        case 'jpg':
        case 'jpeg':
            $contentType = 'image/jpeg';
            break;
        case 'gif':
            $contentType = 'image/gif';
            break;
        default:
            // If the file type is not supported, exit
            http_response_code(404);
            exit;
    }

    // Set the header
    header('Content-Type: ' . $contentType);

    // Output the raw image file data
    readfile($imagePath);

} else {
    // If the image file is not found, send a 404 Not Found response
    http_response_code(404);
    echo "Image not found.";
}