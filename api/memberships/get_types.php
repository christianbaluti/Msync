<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
$db = (new Database())->connect();
$stmt = $db->query("SELECT * FROM membership_types ORDER BY name ASC");
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'data' => $types]);