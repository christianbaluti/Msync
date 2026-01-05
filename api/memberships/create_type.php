<?php
// /api/memberships/create_type.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$data = json_decode(file_get_contents('php://input'));
if (empty($data->name) || !isset($data->fee) || !isset($data->renewal_month)) {
    echo json_encode(['success' => false, 'message' => 'Name, Fee, and Renewal Period are required.']);
    exit;
}

$db = (new Database())->connect();
$query = "INSERT INTO membership_types (name, fee, renewal_month, description) VALUES (:name, :fee, :renewal_month, :description)";
$stmt = $db->prepare($query);

// --- FIX STARTS HERE ---

// Sanitize string inputs using standard PHP functions to prevent XSS
$sanitized_name = htmlspecialchars(strip_tags(trim($data->name)));
$sanitized_description = isset($data->description) ? htmlspecialchars(strip_tags(trim($data->description))) : null;

$stmt->bindValue(':name', $sanitized_name);
$stmt->bindValue(':fee', $data->fee);
$stmt->bindValue(':renewal_month', filter_var($data->renewal_month, FILTER_VALIDATE_INT));
$stmt->bindValue(':description', $sanitized_description);

// --- FIX ENDS HERE ---

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Membership type created successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create membership type.']);
}