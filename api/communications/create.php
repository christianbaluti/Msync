<?php
// /api/communications/create.php

header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/mailer.php';

// ============================
// CONFIGURATIONS
// ============================
define('DEBUG_MODE', true); // set to false in production

// ============================
// UNIVERSAL ERROR HANDLING
// ============================
set_error_handler(function ($severity, $message, $file, $line) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'type' => 'PHP Error',
        'message' => $message,
        'file' => $file,
        'line' => $line
    ]);
    exit;
});

set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'type' => 'Exception',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => DEBUG_MODE ? $e->getTrace() : null
    ]);
    exit;
});

// ============================
// VALIDATE INPUT
// ============================
$data = json_decode(file_get_contents('php://input'));
if (empty($data->channel) || empty($data->body) || empty($data->recipients)) {
    echo json_encode([
        'success' => false,
        'message' => 'Channel, body, and recipients are required.'
    ]);
    exit;
}

// ============================
// MAIN LOGIC
// ============================
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        throw new Exception("User not authenticated.");
    }

    $db = (new Database())->connect();
    $db->beginTransaction();

    // Create the communication record
    $stmt = $db->prepare("INSERT INTO communications (admin_id, channel, subject, body) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $data->channel, $data->subject ?? null, $data->body]);
    $communication_id = $db->lastInsertId();

    // Build recipient query
    $recipients_query = "SELECT DISTINCT u.id, u.full_name, u.email, u.phone FROM users u ";
    $joins = "";
    $where_clauses = [];
    $params = [];

    foreach ($data->recipients as $recipient_group) {
        if ($recipient_group === 'all_users') {
            $where_clauses = ['u.is_active = 1'];
            $params = []; // Clear params if all_users is selected
            break;
        }
        if ($recipient_group === 'all_members') {
            $joins = " JOIN membership_subscriptions ms ON u.id = ms.user_id ";
            $where_clauses[] = "ms.status = 'active'";
        }
        if (strpos($recipient_group, 'type_') === 0) {
            $joins = " JOIN membership_subscriptions ms ON u.id = ms.user_id ";
            $type_id = (int) str_replace('type_', '', $recipient_group);
            $where_clauses[] = "ms.membership_type_id = ?";
            $params[] = $type_id;
        }
    }

    if (!empty($where_clauses)) {
        $recipients_query .= $joins . " WHERE " . implode(' OR ', $where_clauses);
    }

    // Execute recipient query
    $stmt_recipients = $db->prepare($recipients_query);
    $stmt_recipients->execute($params);
    $recipients = $stmt_recipients->fetchAll(PDO::FETCH_ASSOC);

    if (empty($recipients)) {
        throw new Exception("No recipients found for the selected criteria.");
    }

    // Send communications
    $log_query = $db->prepare("INSERT INTO communication_logs (communication_id, recipient_user_id, status) VALUES (?, ?, ?)");
    $mailer = new Mailer();
    $sent_count = 0;

    foreach ($recipients as $recipient) {
        $status = 'failed';
        if ($data->channel === 'email' && !empty($recipient['email'])) {
            if ($mailer->send($recipient['email'], $recipient['full_name'], $data->subject, $data->body)) {
                $status = 'sent';
                $sent_count++;
            }
        }
        if ($data->channel === 'whatsapp') {
            // Placeholder for a WhatsApp API call
            $status = 'queued';
            $sent_count++;
        }
        $log_query->execute([$communication_id, $recipient['id'], $status]);
    }

    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Communication sent successfully.',
        'sent_to' => $sent_count,
        'total_recipients' => count($recipients)
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    throw $e; // handled by the exception handler above
}
