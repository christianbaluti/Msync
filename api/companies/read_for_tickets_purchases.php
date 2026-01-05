<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

$db = (new Database())->connect();

// Fetch only the ID and name for the dropdown list.
$query = "SELECT id, name FROM companies ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($companies === false) {
    // This case handles potential PDO errors
    http_response_code(500);
    echo json_encode([]);
    exit();
}

echo json_encode($companies);