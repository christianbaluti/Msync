<?php
// api/login.php

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ .  '/../vendor/autoload.php';
require_once __DIR__ . '/db_connection.php'; 
use Firebase\JWT\JWT;

$data = json_decode(file_get_contents("php://input"));

// 1. Find user by email
$stmt = $pdo->prepare("SELECT id, password_hash, is_active FROM users WHERE email = ?");
$stmt->execute([$data->email]);
$user = $stmt->fetch();

// 2. Verify user and password
if (!$user || !password_verify($data->password, $user['password_hash'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['message' => 'Invalid email or password.']);
    exit;
}

if ($user['is_active'] == 0) {
    http_response_code(403); // Forbidden
    echo json_encode(['message' => 'Account not verified.']);
    exit;
}

// 3. Update last_login
$pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

// 4. Generate Access Token (JWT)
$secret_key = "YOUR_SECRET_KEY";
$payload = [
    "iat" => time(),
    "exp" => time() + (60 * 60 * 24), // expires in 24 hours
    "data" => ["user_id" => $user['id'], "email" => $data->email]
];
$accessToken = JWT::encode($payload, $secret_key, 'HS256');

// 5. Generate and store Refresh Token
$refreshToken = bin2hex(random_bytes(32)); // Generate a secure random token
$refreshTokenHash = hash('sha256', $refreshToken);

$stmt = $pdo->prepare("UPDATE users SET remember_token_hash = ? WHERE id = ?");
$stmt->execute([$refreshTokenHash, $user['id']]);

// 6. Send both tokens to the app
http_response_code(200);
echo json_encode([
    'message' => 'Login successful.',
    'token' => $accessToken,
    'refresh_token' => $refreshToken
]);
?>