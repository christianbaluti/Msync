<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

$current_month_start = date('Y-m-01 00:00:00');

$query = "
    SELECT id, full_name, email, created_at 
    FROM users 
    WHERE created_at >= ?
    ORDER BY created_at DESC 
    LIMIT 5
";

$stmt = $db->prepare($query);
$stmt->execute([$current_month_start]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $users]);