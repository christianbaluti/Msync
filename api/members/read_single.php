<?php
// /api/members/read_single.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$subscription_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$subscription_id) { /* error */ }

$db = (new Database())->connect();
$query = "SELECT 
            ms.id AS subscription_id,
            ms.user_id,
            ms.membership_type_id,
            ms.start_date,
            u.full_name,
            u.email,
            u.phone
          FROM membership_subscriptions ms
          JOIN users u ON ms.user_id = u.id
          WHERE ms.id = :id";
$stmt = $db->prepare($query);
$stmt->execute(['id' => $subscription_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if ($member) {
    echo json_encode(['success' => true, 'data' => $member]);
} else {
    echo json_encode(['success' => false, 'message' => 'Member not found.']);
}