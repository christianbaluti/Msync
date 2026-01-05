<?php
// api/auth_middleware_for_all.php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function get_auth_user($data) {
    
    // 1. Check if the 'auth_token' field exists in the body
    if (!isset($data->auth_token) || empty($data->auth_token)) {
        http_response_code(401);
        echo json_encode(['message' => 'Authentication token is missing from the request body.']);
        exit;
    }

    $jwt = $data->auth_token;
    $secret_key = "YOUR_SECRET_KEY";

    try {
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
        return $decoded->data; // Returns the 'data' object from the JWT payload
    } catch (Exception $e) {
        // This catch block handles "expired token", "invalid signature", etc.
        http_response_code(401);
        echo json_encode(['message' => 'Access denied: ' . $e->getMessage()]);
        exit;
    }
}
?>