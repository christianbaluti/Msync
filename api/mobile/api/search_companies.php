<?php
// api/search_companies.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require 'db_connection.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name FROM companies WHERE name LIKE ? LIMIT 10");
    $stmt->execute(["%$query%"]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($companies);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error.']);
}
?>