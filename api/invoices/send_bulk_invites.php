<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';
require_once dirname(__DIR__) . '/core/mailer.php';

// 2. INITIALIZE
$database = new Database();
$db = $database->connect();
$mailer = new Mailer();

// Get the currently logged-in admin ID for the audit trail
// Assuming auth.php sets a session variable or helper function exists.
$admin_id = $_SESSION['user_id'] ?? 0; 

// Configuration
$base_url = "https://" . $_SERVER['HTTP_HOST'] . "/memberships/subscribe"; // Adjust this route to your actual frontend page
$token_expiry_days = 120;

// 3. INPUT VALIDATION
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_ids']) || !is_array($input['user_ids']) || empty($input['user_ids'])) {
    echo json_encode(['success' => false, 'message' => 'No users selected.', 'sent_count' => 0]);
    exit();
}

$subject = isset($input['subject']) && !empty($input['subject']) ? trim($input['subject']) : 'Invitation to Subscribe';
$user_ids = array_map('intval', $input['user_ids']);

// 4. PREPARE PARENT COMMUNICATION RECORD
// We log one main "Communication" entry representing this bulk action
try {
    $comm_body_summary = "Bulk Membership Invitations sent to " . count($user_ids) . " users.";
    
    $comm_stmt = $db->prepare("INSERT INTO communications (admin_id, channel, subject, body, sent_at) VALUES (:admin_id, 'email', :subject, :body, NOW())");
    $comm_stmt->execute([
        ':admin_id' => $admin_id,
        ':subject' => $subject,
        ':body' => $comm_body_summary
    ]);
    $communication_id = $db->lastInsertId();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error initializing communication log.', 'sent_count' => 0]);
    exit();
}

// 5. PROCESSING LOOP
$sent_count = 0;
$errors = [];

// Prepare statements for the loop to maximize performance
$user_fetch_sql = "SELECT id, full_name, email FROM users WHERE id IN (" . implode(',', $user_ids) . ")";
$token_insert_sql = "INSERT INTO invoice_access_tokens (token, user_id, target_type, status, expires_at) VALUES (:token, :user_id, 'user', 'pending', :expires_at)";
$log_insert_sql = "INSERT INTO communication_logs (communication_id, recipient_user_id, delivered_at, status) VALUES (:comm_id, :user_id, NOW(), :status)";

$stmt_users = $db->query($user_fetch_sql);
$stmt_token = $db->prepare($token_insert_sql);
$stmt_log = $db->prepare($log_insert_sql);

// Fetch all users at once
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $uid = $user['id'];
    $email = $user['email'];
    $name = $user['full_name'];

    if (empty($email)) {
        $errors[] = "User ID $uid has no email.";
        continue;
    }

    try {
        // A. Generate Unique Token
        // Generate a cryptographically secure 64-char hex token
        $token = bin2hex(random_bytes(6)); 
        $expires_at = date('Y-m-d H:i:s', strtotime("+$token_expiry_days days"));

        // B. Store Token in DB
        $stmt_token->execute([
            ':token' => $token,
            ':user_id' => $uid,
            ':expires_at' => $expires_at
        ]);

        // C. Construct the Unique Link
        // Structure: domain.com/page?uid=123&token=abcde...
        $unique_link = "{$base_url}?uid={$uid}&token={$token}";

        // D. Construct Email Body
        // Simple HTML template
        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2>Hello " . htmlspecialchars($name) . ",</h2>
            <p>You have been invited to subscribe to our membership program.</p>
            <p>Please click the button below to view membership options and secure your subscription via Malipo.</p>
            <p style='margin: 20px 0;'>
                <a href='{$unique_link}' style='background-color: #4F46E5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                    View & Pay Invoice
                </a>
            </p>
            <p style='font-size: 12px; color: #666;'>
                Or copy this link: <br> {$unique_link}
            </p>
            <p>This link is valid for {$token_expiry_days} days.</p>
        </div>";

        // E. Send Email
        $mail_sent = $mailer->send($email, $name, $subject, $body);

        // F. Log Result
        $status = $mail_sent ? 'sent' : 'failed';
        
        $stmt_log->execute([
            ':comm_id' => $communication_id,
            ':user_id' => $uid,
            ':status' => $status
        ]);

        if ($mail_sent) {
            $sent_count++;
        } else {
            $errors[] = "Mailer failed for $email";
        }

    } catch (Exception $e) {
        $errors[] = "Error processing user $uid: " . $e->getMessage();
        // Log failure in DB as well if possible
    }
}

// 6. RETURN RESPONSE
echo json_encode([
    'success' => $sent_count > 0,
    'message' => "Process complete.",
    'sent_count' => $sent_count,
    'total_attempted' => count($user_ids),
    'errors' => $errors
]);
?>