<?php
// /api/memberships/delete_type.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = json_decode(file_get_contents('php://input'));
if (empty($data->id)) {
    echo json_encode(['success' => false, 'message' => 'ID is required.']);
    exit;
}

$db = (new Database())->connect();
$query = "DELETE FROM membership_types WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', filter_var($data->id, FILTER_VALIDATE_INT));

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Membership type deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete. It might be in use.']);
}