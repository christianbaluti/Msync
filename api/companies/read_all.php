<?php
// /api/companies/read_all.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();

$query = "SELECT * FROM companies WHERE is_active = 1 ORDER BY name ASC";

try {
    $stmt = $db->query($query);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $companies]);

} catch (PDOException $e) {
    error_log($e->getMessage()); // Log error for debugging
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve company list.']);
}