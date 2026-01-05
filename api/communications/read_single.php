<?php
// /api/communications/read_single.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { exit; }

$db = (new Database())->connect();
$response = ['success' => false];

try {
    // Get main communication details
    $stmt_details = $db->prepare("SELECT * FROM communications WHERE id = ?");
    $stmt_details->execute([$id]);
    $details = $stmt_details->fetch(PDO::FETCH_ASSOC);

    if(!$details) {
        throw new Exception("Communication not found.");
    }

    // Get recipients
    $stmt_recipients = $db->prepare("
        SELECT u.full_name, u.email 
        FROM communication_logs cl
        JOIN users u ON cl.recipient_user_id = u.id
        WHERE cl.communication_id = ?
        ORDER BY u.full_name
    ");
    $stmt_recipients->execute([$id]);
    $recipients = $stmt_recipients->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = [
        'details' => $details,
        'recipients' => $recipients
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);