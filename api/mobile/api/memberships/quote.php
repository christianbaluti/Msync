<?php
// api/memberships/quote.php

// NEW: Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require '../../vendor/autoload.php';
require '../db_connection.php';
require '../auth_middleware.php';

// NEW: Set the timezone
date_default_timezone_set('Africa/Blantyre');

$auth_data = get_auth_user();
$user_id = $auth_data->user_id;
$data = json_decode(file_get_contents("php://input"));

if (empty($data->membership_type_id)) {
    http_response_code(400);
    echo json_encode(['message' => 'Membership Type ID is required.']);
    exit;
}
$membership_type_id = $data->membership_type_id;

try {
    // MODIFIED: Fetch more details for the email
    $stmt_type = $pdo->prepare("SELECT name, fee FROM membership_types WHERE id = ?");
    $stmt_type->execute([$membership_type_id]);
    $type = $stmt_type->fetch(PDO::FETCH_ASSOC);
    if (!$type) { throw new Exception("Invalid Membership Type."); }
    
    $membership_name = $type['name'];
    $fee = $type['fee'];

    // NEW: Fetch user details for the email
    $stmt_user = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    if (!$user) { throw new Exception("Authenticated user not found."); }
    
    // Using a dummy admin_id=1. In a real app, this might be a system user.
    $stmt_quote = $pdo->prepare("INSERT INTO quotations (admin_id, user_id, quote_type, reference_id, total_amount, status) VALUES (1, ?, 'membership', ?, ?, 'sent')");
    $stmt_quote->execute([$user_id, $membership_type_id, $fee]);
    $quote_id = $pdo->lastInsertId();
    
    // ✅ NEW: Fetch SMTP settings and send email with PHPMailer
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

    //Recipients
    $mail->setFrom($smtp_settings['smtp_from_email'], $smtp_settings['smtp_from_name']);
    $mail->addAddress($user['email'], $user['full_name']);

    //Content
    $mail->isHTML(true);
    $mail->Subject = 'Your Membership Quotation from MemberSync';
    
    // Construct a professional HTML email body for the quote
    $quote_number = 'MMSC-' . date('Y') . '-' . $quote_id;
    $issue_date = date('F j, Y');

    $mail->Body    = "
        <p>Dear {$user['full_name']},</p>
        <p>Thank you for your interest. Please find your membership quotation details below:</p>
        <hr>
        <h3>Quotation Details</h3>
        <p><strong>Quote Number:</strong> {$quote_number}</p>
        <p><strong>Date of Issue:</strong> {$issue_date}</p>
        <table border='1' cellpadding='10' cellspacing='0' style='width: 100%; border-collapse: collapse;'>
            <thead>
                <tr style='background-color: #f2f2f2;'>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Membership Subscription - {$membership_name}</td>
                    <td style='text-align: right;'>\${$fee}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr style='background-color: #f2f2f2;'>
                    <td style='text-align: right;'><strong>Total:</strong></td>
                    <td style='text-align: right;'><strong>\${$fee}</strong></td>
                </tr>
            </tfoot>
        </table>
        <br>
        <p>If you have any questions, feel free to contact us.</p>
        <p>Best regards,<br>The MemberSync Team</p>
    ";

    $mail->send();
    
    echo json_encode(['message' => 'Quotation has been successfully generated and sent to your email.']);

} catch (Exception $e) {
    http_response_code(500);
    // Provide a more specific error message if the mailer fails
    if (isset($mail) && $mail instanceof PHPMailer) {
        echo json_encode(['message' => "Quote created, but could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    } else {
        echo json_encode(['message' => 'Could not generate quote: ' . $e->getMessage()]);
    }
}
?>