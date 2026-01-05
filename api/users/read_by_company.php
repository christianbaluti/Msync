<?php
// /api/users/read_by_company.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
if (!$company_id) {
    echo json_encode(['success' => false, 'message' => 'Company ID is required.']);
    exit;
}

$db = (new Database())->connect();

// Find users in the selected company who do NOT have an active or pending membership
$query = "
    SELECT u.id, u.full_name, u.email 
    FROM users u
    LEFT JOIN membership_subscriptions ms ON u.id = ms.user_id AND ms.status IN ('active', 'pending')
    WHERE u.company_id = ? AND ms.id IS NULL
    ORDER BY u.full_name ASC
";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([$company_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $users]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}