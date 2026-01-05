<?php
// api/refresh_token.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ .  '/../vendor/autoload.php';
require_once __DIR__ . '/db_connection.php'; 
use Firebase\JWT\JWT;

$data = json_decode(file_get_contents("php://input"));

if (empty($data->refresh_token)) {
    http_response_code(400);
    echo json_encode(['message' => 'Refresh token is required.']);
    exit;
}

$refreshToken = $data->refresh_token;
$refreshTokenHash = hash('sha256', $refreshToken);

// Find user by the refresh token hash
$stmt = $pdo->prepare("SELECT id, email FROM users WHERE remember_token_hash = ?");
$stmt->execute([$refreshTokenHash]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401); // Unauthorized
    echo json_encode(['message' => 'Invalid refresh token. Please log in again.']);
    exit;
}

// If token is valid, issue a new Access Token (JWT)
$secret_key = "YOUR_SECRET_KEY";
$payload = [
    "iat" => time(),
    "exp" => time() + (60 * 60 * 24), // expires in 1 hour
    "data" => ["user_id" => $user['id'], "email" => $user['email']]
];
$newAccessToken = JWT::encode($payload, $secret_key, 'HS256');

http_response_code(200);
echo json_encode([
    'message' => 'Token refreshed successfully.',
    'token' => $newAccessToken
]);
?>