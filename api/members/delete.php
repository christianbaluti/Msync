<?php
// /api/members/delete.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = json_decode(file_get_contents('php://input'));
if (empty($data->id)) { /* error */ }

$db = (new Database())->connect();
$query = "DELETE FROM membership_subscriptions WHERE id = :id";
$stmt = $db->prepare($query);
if ($stmt->execute(['id' => $data->id])) {
    echo json_encode(['success' => true, 'message' => 'Subscription deleted.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete subscription.']);
}