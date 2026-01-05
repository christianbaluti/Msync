<?php
// Set a custom session save path
///api/core/initialize.php
$session_path = realpath(__DIR__ . '/../sessions');
if (is_writable($session_path)) {
    session_save_path($session_path);
}

// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- CSRF Protection ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 64-char token
}
//we need to change this once deployed
define('UPLOAD_BASE_URL', 'http://localhost:8000/uploads/');

// Include the database connection
require_once 'database.php';
?>