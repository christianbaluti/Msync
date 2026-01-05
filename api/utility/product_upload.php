<?php
header('Content-Type: application/json');

$uploadDir = dirname(__DIR__, 2) . '/public/uploads/products/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

$filePaths = [];
foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
    $fileName = basename($_FILES['images']['name'][$key]);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($tmpName, $targetPath)) {
        $filePaths[] = 'products/' . $fileName; // relative path for front-end rendering
    }
}

echo json_encode([
    'success' => true,
    'filePaths' => $filePaths
]);
?>
