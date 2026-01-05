<?php
// File: /api/events/upload_cert_asset.php

header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/auth.php'; // Ensure user is logged in
// You might want to check permissions here too if strictly required

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No image uploaded.']);
    exit;
}

$file = $_FILES['image'];
$uploadDir = dirname(__DIR__, 2) . '/public/uploads/certificates/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('cert_asset_') . '.' . $ext;
$targetPath = $uploadDir . $filename;
$publicUrl = '/uploads/certificates/' . $filename;

// Validation (Basic)
$allowed = ['jpg', 'jpeg', 'png', 'gif'];
if (!in_array(strtolower($ext), $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
    exit;
}

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['success' => true, 'url' => $publicUrl]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
}
