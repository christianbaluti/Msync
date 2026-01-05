<?php
// /api/users/search.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (strlen($term) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$db = (new Database())->connect();

$query = "SELECT id, full_name, email, phone 
          FROM users 
          WHERE full_name LIKE :term 
             OR email LIKE :term 
             OR phone LIKE :term
          LIMIT 10";
          
$stmt = $db->prepare($query);
$stmt->execute(['term' => '%' . $term . '%']);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $users]);