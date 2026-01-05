<?php
// /api/memberships/update_type.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = json_decode(file_get_contents('php://input'));
if (empty($data->id) || empty($data->name) || !isset($data->fee) || !isset($data->renewal_month)) {
    echo json_encode(['success' => false, 'message' => 'ID, Name, Fee, and Renewal Period are required.']);
    exit;
}

$db = (new Database())->connect();
$query = "UPDATE membership_types SET name = :name, fee = :fee, renewal_month = :renewal_month, description = :description WHERE id = :id";
$stmt = $db->prepare($query);

// --- FIX STARTS HERE ---

// Sanitize string inputs using standard PHP functions
$sanitized_name = htmlspecialchars(strip_tags(trim($data->name)));
$sanitized_description = isset($data->description) ? htmlspecialchars(strip_tags(trim($data->description))) : null;

$stmt->bindValue(':id', filter_var($data->id, FILTER_VALIDATE_INT));
$stmt->bindValue(':name', $sanitized_name);
$stmt->bindValue(':fee', $data->fee);
$stmt->bindValue(':renewal_month', filter_var($data->renewal_month, FILTER_VALIDATE_INT));
$stmt->bindValue(':description', $sanitized_description);

// --- FIX ENDS HERE ---

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Membership type updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update membership type.']);
}