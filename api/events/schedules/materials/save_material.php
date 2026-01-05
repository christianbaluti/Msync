<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 3) . '/core/initialize.php';

/**
 * Logs a message to a central error log file.
 * @param string $message The error message to log.
 */
function log_error($message) {
    // Define a log file path outside of the public web root for security.
    $log_file = dirname(__DIR__, 4) . '/logs/app_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    
    // Ensure the logs directory exists.
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0775, true);
    }
    
    // Append the formatted error message to the log file.
    file_put_contents($log_file, "[$timestamp] " . $message . PHP_EOL, FILE_APPEND);
}


// Assuming you have an authentication check here
// if (!is_user_logged_in()) { exit; }

$db = (new Database())->connect();

// --- Configuration ---
$upload_dir_base = dirname(__DIR__, 4) . '/public';
$upload_dir_path = '/uploads/';
$upload_dir = $upload_dir_base . $upload_dir_path;
$max_file_size = 50 * 1024 * 1024; // 50 MB

// Create upload directory if it doesn't exist
if (!is_dir($upload_dir)) {
    if (!@mkdir($upload_dir, 0777, true)) {
        $error_message = 'Failed to create upload directory. Check server permissions.';
        log_error("save_material.php: " . $error_message . " Path: " . $upload_dir);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server configuration error.']);
        exit;
    }
}

// --- Validation ---
if (empty($_POST['schedule_id']) || !isset($_POST['title']) || !isset($_POST['type'])) {
    $error_message = 'Missing required fields: schedule_id, title, and type are required.';
    log_error("save_material.php: " . $error_message . " - Data received: " . json_encode($_POST));
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

// --- Logic ---
$id = !empty($_POST['id']) ? $_POST['id'] : null;
$schedule_id = $_POST['schedule_id'];
$title = $_POST['title'];
$description = $_POST['description'] ?? null;
$type = $_POST['type'];
$url = $_POST['url'] ?? null;

$is_new_file_uploaded = isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK;

// A. Handle New File Upload
if ($is_new_file_uploaded) {
    $file = $_FILES['material_file'];

    if ($file['size'] > $max_file_size) {
        $error_message = 'Error: File is larger than 50 MB.';
        log_error("save_material.php: " . $error_message . " Filename: " . $file['name'] . ", Size: " . $file['size']);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit;
    }

    $filename = uniqid() . '-' . basename(preg_replace("/[^a-zA-Z0-9.\s_-]/", "", $file['name']));
    $destination = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $url = $upload_dir_path . $filename;
    } else {
        $error_message = 'Error: Could not move uploaded file.';
        log_error("save_material.php: " . $error_message . " Check permissions for destination: " . $destination);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error_message . ' Check server permissions.']);
        exit;
    }
}

// B. Database Operation (Create vs Update)
try {
    if ($id) {
        // --- UPDATE ---
        $stmt_old = $db->prepare("SELECT url, type FROM training_materials WHERE id = :id");
        $stmt_old->execute([':id' => $id]);
        $old_material = $stmt_old->fetch(PDO::FETCH_ASSOC);

        if ($old_material) {
            $old_file_path = $old_material['url'];
            $old_type_was_file = $old_material['type'] !== 'link';

            if ($old_type_was_file && ($is_new_file_uploaded || ($type === 'link' && $url !== $old_file_path))) {
                $old_file_server_path = $upload_dir_base . $old_file_path;
                if ($old_file_path && file_exists($old_file_server_path)) {
                    // Log a warning if the old file cannot be deleted
                    if (!unlink($old_file_server_path)) {
                        log_error("save_material.php: WARNING - Could not delete old file: " . $old_file_server_path);
                    }
                }
            }
        }
        
        $query = "UPDATE training_materials SET title = :title, description = :description, type = :type, url = :url WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
    } else {
        // --- CREATE ---
        $query = "INSERT INTO training_materials (schedule_id, title, description, type, url) VALUES (:schedule_id, :title, :description, :type, :url)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':schedule_id', $schedule_id);
    }
    
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':url', $url);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Material saved successfully.']);
    } else {
        $errorInfo = $stmt->errorInfo();
        throw new Exception('Database execution failed. SQLSTATE[' . $errorInfo[0] . '] ' . $errorInfo[2]);
    }

} catch (Exception $e) {
    // Log the detailed, technical error for the developer.
    log_error("save_material.php: Exception caught - " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    http_response_code(500);
    // Provide a generic, safe error message to the client.
    echo json_encode(['success' => false, 'message' => 'An unexpected server error occurred. Please try again later.']);
}