<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

// Validation
if (empty($_POST['id']) || empty($_POST['title']) || empty($_POST['start_datetime']) || empty($_POST['end_datetime'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID, title, start time, and end time are required.']);
    exit();
}

$db = (new Database())->connect();
$event_id = $_POST['id'];
$upload_dir = dirname(__DIR__, 2) . '/public/uploads/events/';

// First, get the current image filename to delete it if a new one is uploaded
$stmt = $db->prepare("SELECT main_image FROM events WHERE id = :id");
$stmt->bindParam(':id', $event_id, PDO::PARAM_INT);
$stmt->execute();
$current_event = $stmt->fetch(PDO::FETCH_ASSOC);
$current_image = $current_event ? $current_event['main_image'] : null;

// Image Upload Handling
$image_filename = $current_image; // Keep old image by default
if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
    // Process new image upload (same logic as create.php)
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($_FILES['main_image']['type'], $allowed_types)) {
        $file_extension = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
        $new_image_filename = uniqid('event_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . $new_image_filename;

        if (move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_path)) {
            // If new image saved, delete the old one
            if ($current_image && file_exists($upload_dir . $current_image)) {
                unlink($upload_dir . $current_image);
            }
            $image_filename = $new_image_filename; // Set filename to the new one
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save the new image.']);
            exit();
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid new image file type.']);
        exit();
    }
}

// Database Operation
try {
    $query = "UPDATE events 
              SET title = :title, 
                  description = :description, 
                  start_datetime = :start_datetime, 
                  end_datetime = :end_datetime, 
                  location = :location, 
                  status = :status,
                  main_image = :main_image
              WHERE id = :id";
              
    $stmt = $db->prepare($query);

    // Bind data
    $stmt->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt->bindParam(':title', $_POST['title']);
    $stmt->bindParam(':description', $_POST['description']);
    $stmt->bindParam(':start_datetime', $_POST['start_datetime']);
    $stmt->bindParam(':end_datetime', $_POST['end_datetime']);
    $stmt->bindParam(':location', $_POST['location']);
    $stmt->bindParam(':status', $_POST['status']);
    $stmt->bindParam(':main_image', $image_filename);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Event updated successfully.']);
    } else {
        throw new Exception('Failed to execute update statement.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}