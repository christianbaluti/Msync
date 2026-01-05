<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

$current_month_start = date('Y-m-01');

$query = "
    SELECT 
        ms.id, 
        u.full_name, 
        mt.name as membership_type,
        ms.start_date
    FROM membership_subscriptions ms
    JOIN users u ON ms.user_id = u.id
    JOIN membership_types mt ON ms.membership_type_id = mt.id
    WHERE ms.start_date >= ? AND ms.status = 'active'
    ORDER BY ms.start_date DESC
    LIMIT 5
";

$stmt = $db->prepare($query);
$stmt->execute([$current_month_start]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $members]);