<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 3) . '/core/initialize.php';
// auth check would go here

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Material ID is required.']);
    exit();
}

$db = (new Database())->connect();

try {
    // 1. Get the record to find the file path BEFORE deleting
    $stmt = $db->prepare("SELECT url, type FROM training_materials WHERE id = :id");
    $stmt->execute([':id' => $data->id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Delete the record from the database
    $delete_stmt = $db->prepare("DELETE FROM training_materials WHERE id = :id");
    $delete_stmt->execute([':id' => $data->id]);

    if ($delete_stmt->rowCount() > 0) {
        // 3. If DB deletion was successful, delete the file from the server
        if ($material && $material['type'] !== 'link' && !empty($material['url'])) {
            $file_path = dirname(__DIR__, 4) . '/public' . $material['url'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        echo json_encode(['success' => true, 'message' => 'Material deleted successfully.']);
    } else {
        throw new Exception('Material not found or could not be deleted.');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete material: ' . $e->getMessage()]);
}