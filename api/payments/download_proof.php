<?php
// =================================================================
// INITIALIZATION & SECURITY
// =================================================================
// This ensures we have access to the database, sessions, etc.
require_once dirname(__DIR__) . '/core/initialize.php';

// =================================================================
// INPUT VALIDATION
// =================================================================
$payment_id = $_GET['payment_id'] ?? null;

if (!$payment_id || !filter_var($payment_id, FILTER_VALIDATE_INT)) {
    header('HTTP/1.1 400 Bad Request');
    die('Invalid or missing Payment ID.');
}

// =================================================================
// DATA FETCHING & FILE PROCESSING
// =================================================================
try {
    $database = new Database();
    $db = $database->connect();

    // Fetch the payment record to get the 'meta' column
    $query = "SELECT meta FROM payments WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $payment_id, PDO::PARAM_INT);
    $stmt->execute();

    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        header('HTTP/1.1 404 Not Found');
        die('Payment record not found.');
    }

    // Decode the meta JSON
    $meta = json_decode($payment['meta'], true);
    $proof_path_web = $meta['proof_of_payment'] ?? null;

    if (!$proof_path_web) {
        header('HTTP/1.1 404 Not Found');
        die('No proof of payment is associated with this transaction.');
    }

    // --- CRITICAL: Construct the absolute file system path ---
    // The path in the DB like '/uploads/...' is a web path. We need the server's full path.
    // This assumes your project root is 4 directories up from this API file. Adjust if needed.
    $project_root = dirname(__DIR__, 2); 
    $file_path_system = $project_root . $proof_path_web;

    // Sanitize to prevent directory traversal attacks
    $real_file_path = realpath($file_path_system);
    if ($real_file_path === false || strpos($real_file_path, $project_root) !== 0) {
         header('HTTP/1.1 400 Bad Request');
         die('Invalid file path specified.');
    }
    
    // Check if the file actually exists on the server
    if (!file_exists($real_file_path)) {
        header('HTTP/1.1 404 Not Found');
        die('The proof of payment file could not be found on the server.');
    }

    // =================================================================
    // SERVE THE FILE FOR DOWNLOAD
    // =================================================================
    
    // Clear any previously sent headers or output
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Set headers to force download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream'); // Generic type for any file
    header('Content-Disposition: attachment; filename="' . basename($real_file_path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($real_file_path));
    
    // Read the file and send its contents to the browser
    readfile($real_file_path);
    exit;

} catch (PDOException $e) {
    // Log the database error for debugging
    error_log("Database Error in download_proof.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    die('A database error occurred.');
}