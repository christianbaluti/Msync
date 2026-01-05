<?php
// Set headers
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

// Include necessary files
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

// Check for permission
if (!has_permission('users_delete')) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'You do not have permission to delete users.']);
    exit();
}

// Get raw posted data
$data = json_decode(file_get_contents("php://input"));

// Validate input
if (empty($data->id)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit();
}

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

try {
    // Prepare the delete query
    // It's good practice to ensure the user is not deleting their own account,
    // though this check might be better handled in application logic or triggers.
    // For now, a direct delete is implemented as requested.
    $query = "DELETE FROM users WHERE id = :id";
    
    // Prepare statement
    $stmt = $db->prepare($query);

    // Sanitize and bind the ID
    $id = htmlspecialchars(strip_tags($data->id));
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    // Execute query
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
        } else {
            // No rows were deleted, likely means the user ID didn't exist
            http_response_code(404); // Not Found
            echo json_encode(['success' => false, 'message' => 'User not found.']);
        }
    } else {
        // The execute call failed
        throw new Exception('Failed to execute the delete statement.');
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    // In a production environment, you might log the detailed error and show a generic message.
    error_log($e->getMessage()); 
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the user.']);
}