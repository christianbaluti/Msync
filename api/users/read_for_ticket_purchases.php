<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

// Check if we need to fetch all users for the bulk list
if (isset($_GET['all'])) {
    $query = "SELECT u.id, u.full_name, u.email, u.is_employed, u.company_id, c.name as company_name 
              FROM users u 
              LEFT JOIN companies c ON u.company_id = c.id
              ORDER BY u.full_name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['users' => $users]);
    exit();
}

// --- This is the completed search logic ---
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Exit early if the search term is empty or too short
if (strlen($search_term) < 1) {
    echo json_encode(['users' => []]);
    exit();
}

$search_param = '%' . $search_term . '%';

// Prepare a query to search by name or email, with a limit for performance
$query = "SELECT id, full_name, email 
          FROM users 
          WHERE full_name LIKE :search OR email LIKE :search 
          ORDER BY full_name ASC 
          LIMIT 10";

$stmt = $db->prepare($query);
$stmt->execute([':search' => $search_param]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['users' => $users]);