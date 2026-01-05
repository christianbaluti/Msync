<?php
// api/register.php

// NEW: Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ START: CORRECTED CORS HANDLING
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle the preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
// ✅ END: CORRECTED CORS HANDLING

require '../vendor/autoload.php'; // NEW: Autoload includes PHPMailer
require 'db_connection.php';

// NEW: Set the timezone for your server location (Blantyre)
date_default_timezone_set('Africa/Blantyre');

$data = json_decode(file_get_contents("php://input"));

// 1. Validate basic input
if (empty($data->full_name) || empty($data->email) || empty($data->phone) || empty($data->password) || empty($data->gender) || !isset($data->is_employed)) {
    http_response_code(400);
    echo json_encode(['message' => 'All required fields must be filled.']);
    exit;
}

// 2. Check for existing user (no changes here)
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
$stmt->execute([$data->email, $data->phone]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['message' => 'Email or phone number already exists.']);
    exit;
}

// 3. Handle Company Logic (no changes here)
$company_id = null;
$position = null;
if ($data->is_employed) {
    if (empty($data->company_name) || empty($data->position)) {
        http_response_code(400);
        echo json_encode(['message' => 'Company name and position are required for employed users.']);
        exit;
    }
    $position = $data->position;
    if (!empty($data->company_id)) {
        $company_id = $data->company_id;
    } else {
        $stmt = $pdo->prepare("SELECT id FROM companies WHERE name = ?");
        $stmt->execute([$data->company_name]);
        $company = $stmt->fetch();
        if ($company) {
            $company_id = $company['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO companies (name, email, phone, password_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data->company_name, "temp_" . $data->email, "temp_" . $data->phone, "dummy_hash"]);
            $company_id = $pdo->lastInsertId();
        }
    }
} else {
    $position = !empty($data->unemployed_details) ? $data->unemployed_details : null;
}

// 4. Create the user
$password_hash = password_hash($data->password, PASSWORD_BCRYPT);
$sql = "INSERT INTO users (full_name, email, phone, password_hash, gender, is_employed, company_id, position, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$data->full_name, $data->email, $data->phone, $password_hash, $data->gender, $data->is_employed, $company_id, $position]);
$user_id = $pdo->lastInsertId();

// 5. Generate and store OTP
$otp_code = rand(100000, 999999);
// MODIFIED: OTP expiry set to 1 hour
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
$otp_channel = 'email'; // MODIFIED: Hardcoded to email

$stmt = $pdo->prepare("INSERT INTO otp_verifications (target_type, target_id, channel, code, purpose, expires_at) VALUES ('user', ?, ?, ?, 'signup', ?)");
$stmt->execute([$user_id, $otp_channel, $otp_code, $expires_at]);

// 6. NEW: Fetch SMTP settings and send email with PHPMailer
try {
    $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'smtp_%'");
    $smtp_settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $mail = new PHPMailer(true);
    //Server settings
    $mail->isSMTP();
    $mail->Host       = $smtp_settings['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_settings['smtp_username'];
    $mail->Password   = $smtp_settings['smtp_password'];
    $mail->SMTPSecure = $smtp_settings['smtp_secure'];
    $mail->Port       = $smtp_settings['smtp_port'];
    
    $mail->systemName = $smtp_settings['system_name'];

    //Recipients
    $mail->setFrom($smtp_settings['smtp_from_email'], $smtp_settings['smtp_from_name']);
    $mail->addAddress($data->email, $data->full_name);

    //Content
    $mail->isHTML(true);
    $mail->Subject = 'Your Verification Code for the ' .$systemName. ' is';
    $mail->Body    = "Hello {$data->full_name},<br><br>Your verification code is: <b>{$otp_code}</b><br>This code is valid for 1 hour.<br><br>Thank you,<br>The ' .$systemName. ' Team";
    $mail->AltBody = "Hello {$data->full_name}, Your verification code is: {$otp_code}";

    $mail->send();

    http_response_code(201); // Created
    echo json_encode(['message' => 'Registration successful. Please check your email for the OTP.']);

} catch (Exception $e) {
    // If email fails, provide a specific error message
    http_response_code(500);
    echo json_encode(['message' => "User created, but OTP could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
}

?>