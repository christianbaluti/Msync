<?php
// We are NOT outputting JSON, so remove the json content-type header.
// Headers will be set later to force the file download.

require_once dirname(__DIR__, 3) . '/core/initialize.php';

// 1. GET AND VALIDATE THE ID
// We use $_GET because the ID comes from the URL (e.g., ?id=123)
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400); // Bad Request
    die('Error: A valid material ID is required.');
}

$material_id = (int)$_GET['id'];

try {
    $db = (new Database())->connect();

    // 2. FETCH FILE INFORMATION FROM DATABASE
    // We need the 'title' for the new filename and 'url' for the actual file path.
    $query = "SELECT title, url, type FROM training_materials WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $material_id, PDO::PARAM_INT);
    $stmt->execute();

    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. VALIDATE THE RECORD AND FILE
    if (!$material) {
        http_response_code(404); // Not Found
        die('Error: The requested material does not exist.');
    }

    if (empty($material['url'])) {
        http_response_code(404);
        die('Error: No file is associated with this material record.');
    }
    
    // Construct the full, absolute path to the file.
    // This assumes your '/public' folder is in the project's root directory.
    // Adjust `dirname(__DIR__, 2)` if your folder structure is different.
    $file_path = dirname(__DIR__, 4) . '/public/' . $material['url'];

    if (!file_exists($file_path) || !is_readable($file_path)) {
        http_response_code(404);
        die('Error: The file could not be found on the server.');
    }

    // 4. PREPARE FILENAME AND HEADERS FOR DOWNLOAD
    
    // Get the original file extension (e.g., 'pdf', 'mp4').
    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
    
    // Sanitize the title from the database to create a safe filename.
    // This removes characters that are invalid in filenames.
    $safe_filename = preg_replace('/[^\p{L}\p{N}\s\.-]/u', '', $material['title']);
    $download_filename = $safe_filename . '.' . $extension;

    // Clear any previously sent headers or output.
    if (ob_get_level()) {
      ob_end_clean();
    }
    
    // Set the necessary HTTP headers to trigger a download dialog in the browser.
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream'); // A generic type to force download
    header('Content-Disposition: attachment; filename="' . basename($download_filename) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    
    // 5. READ AND OUTPUT THE FILE
    // This function reads the file and writes it directly to the output buffer.
    readfile($file_path);
    
    // Stop the script from running further.
    exit();

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    // Log the detailed error for the admin, but show a generic message to the user.
    error_log($e->getMessage());
    die('A database error occurred. Please try again later.');
}