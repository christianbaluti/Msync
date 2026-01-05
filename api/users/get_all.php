<?php
// File: /api/users/get_all.php
header('Content-Type: application/json');
// UPDATED PATH:
require_once dirname(__DIR__) . '/core/database.php';

$db = (new Database())->connect();
$stmt = $db->query("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'users' => $users]);