<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

try {
    $db = (new Database())->connect();
    $query = "SELECT id, design_name FROM card_designs ORDER BY design_name ASC";
    $stmt = $db->query($query);
    $designs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $designs]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}