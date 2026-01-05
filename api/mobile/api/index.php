<?php
// Allow all origins
header("Access-Control-Allow-Origin: *");

// Allow all methods
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

// Allow all headers
header("Access-Control-Allow-Headers: *");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>
<h1>Hi</h1>
