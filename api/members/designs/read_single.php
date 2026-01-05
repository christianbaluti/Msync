<?php
// /api/members/designs/read_single.php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { exit; }

$db = (new Database())->connect();
$stmt = $db->prepare("SELECT * FROM card_designs WHERE id = ?");
$stmt->execute([$id]);
$design = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $design]);