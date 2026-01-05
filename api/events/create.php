<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

// Validation (Now using $_POST)
if (empty($_POST['title']) || empty($_POST['start_datetime']) || empty($_POST['end_datetime'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title, start time, and end time are required.']);
    exit();
}

// Image Upload Handling
$image_filename = null;
if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = dirname(__DIR__, 2) . '/public/uploads/events/';
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = $_FILES['main_image']['type'];

    if (in_array($file_type, $allowed_types)) {
        $file_extension = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
        $image_filename = uniqid('event_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . $image_filename;

        if (!move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_path)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save the uploaded image.']);
            exit();
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid image file type.']);
        exit();
    }
}

// Database Operation
$db = (new Database())->connect();
try {
    $query = "INSERT INTO events (title, description, start_datetime, end_datetime, location, status, main_image, created_by) 
              VALUES (:title, :description, :start_datetime, :end_datetime, :location, :status, :main_image, :created_by)";
              
    $stmt = $db->prepare($query);

    // Bind data from $_POST
    $stmt->bindParam(':title', $_POST['title']);
    $stmt->bindParam(':description', $_POST['description']);
    $stmt->bindParam(':start_datetime', $_POST['start_datetime']);
    $stmt->bindParam(':end_datetime', $_POST['end_datetime']);
    $stmt->bindParam(':location', $_POST['location']);
    $stmt->bindParam(':status', $_POST['status']);
    $stmt->bindParam(':main_image', $image_filename); // Store the new filename
    $stmt->bindParam(':created_by', $_SESSION['user_id'], PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Event created successfully.']);
    } else {
        throw new Exception('Failed to execute statement.');
    }
} catch (Exception $e) {
    // If DB insert fails, delete uploaded file
    if ($image_filename && file_exists($upload_dir . $image_filename)) {
        unlink($upload_dir . $image_filename);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}