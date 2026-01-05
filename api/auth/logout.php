<?php
header('Content-Type: application/json');
// AFTER
require_once dirname(__DIR__) . '/core/initialize.php';

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Return a success response
echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
?>  
