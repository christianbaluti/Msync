<?php
// api/verify-otp.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-control-allow-headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ .  '/../vendor/autoload.php';
require_once __DIR__ . '/db_connection.php'; 
use Firebase\JWT\JWT;

$data = json_decode(file_get_contents("php://input"));

// 1. Get user by email
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$data->email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['message' => 'User not found.']);
    exit;
}

// 2. Find and verify the OTP
$stmt = $pdo->prepare("SELECT * FROM otp_verifications WHERE target_id = ? AND code = ? AND purpose = 'signup' AND used = 0 AND expires_at > NOW()");
$stmt->execute([$user['id'], $data->code]);
$otp = $stmt->fetch();

if (!$otp) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid or expired OTP.']);
    exit;
}

// 3. Update user to active and OTP to used
$pdo->prepare("UPDATE users SET is_active = 1, last_login = NOW() WHERE id = ?")->execute([$user['id']]);
$pdo->prepare("UPDATE otp_verifications SET used = 1 WHERE id = ?")->execute([$otp['id']]);

// 4. Generate Access Token (JWT)
$secret_key = "YOUR_SECRET_KEY";
$payload = [
    "iat" => time(),
    "exp" => time() + (60 * 60 * 24), // Expires in 24 hours
    "data" => ["user_id" => $user['id'], "email" => $data->email]
];
$accessToken = JWT::encode($payload, $secret_key, 'HS256');

// 5. NEW: Generate and store Refresh Token
$refreshToken = bin2hex(random_bytes(32));
$refreshTokenHash = hash('sha256', $refreshToken);

$stmt = $pdo->prepare("UPDATE users SET remember_token_hash = ? WHERE id = ?");
$stmt->execute([$refreshTokenHash, $user['id']]);

// 6. MODIFIED: Send both tokens to the app
http_response_code(200);
echo json_encode([
    'message' => 'Verification successful.',
    'token' => $accessToken,
    'refresh_token' => $refreshToken
]);
?>