<?php
// /api/members/read_for_card_generation.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$query = "SELECT 
            ms.id AS subscription_id,
            u.full_name AS member_name,
            ms.membership_card_number AS member_id,
            mt.name AS membership_type,
            ms.end_date AS expiry_date,
            c.name AS company_name
          FROM membership_subscriptions ms
          JOIN users u ON ms.user_id = u.id
          JOIN membership_types mt ON ms.membership_type_id = mt.id
          LEFT JOIN companies c ON u.company_id = c.id
          WHERE ms.status = 'active'
          ORDER BY u.full_name ASC";

$stmt = $db->query($query);
echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);