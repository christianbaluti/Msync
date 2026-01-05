<?php
// /api/members/designs/read_all.php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';

$db = (new Database())->connect();
$stmt = $db->query("SELECT id, design_name FROM card_designs ORDER BY design_name ASC");
echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);