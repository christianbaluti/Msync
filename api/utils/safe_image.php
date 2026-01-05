<?php
ini_set('display_errors', 0);
error_reporting(0);

$file = isset($_GET['file']) ? basename($_GET['file']) : '';
$uploadDir = dirname($_SERVER['DOCUMENT_ROOT']) . '/uploads/events/';
$path = $uploadDir . $file;
$placeholder = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/placeholder.png';

// Debug mode (only text output)
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain');
    echo "Upload dir: $uploadDir\n";
    echo "Full path: $path\n";
    echo "Placeholder: $placeholder\n";
    exit;
}

if (empty($file) || !file_exists($path) || !is_readable($path)) {
    $path = $placeholder;
    if (!file_exists($path)) {
        header('Content-Type: text/plain');
        http_response_code(404);
        echo 'Error: Image not found.';
        exit;
    }
}

$mime = mime_content_type($path) ?: 'image/png';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
?>
