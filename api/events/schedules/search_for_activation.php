<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';

$event_id = $_GET['event_id'] ?? 0;
if (!$event_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required.']);
    exit;
}

$db = (new Database())->connect();

// Base query
$sql = "
    SELECT
        t.id AS ticket_id,
        u.full_name,
        u.email,
        u.is_employed,
        c.name AS company_name,
        aat.name AS attending_as_name,
        mc.status AS meal_status
    FROM event_tickets t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN companies c ON u.company_id = c.id
    LEFT JOIN attending_as_types aat ON t.attending_as_id = aat.id
    LEFT JOIN meal_cards mc ON t.id = mc.ticket_id
    WHERE t.event_id = :event_id
    AND (mc.status IS NULL OR mc.status = 'inactive')
";

$params = [':event_id' => $event_id];

// Dynamically add filters to the query
if (!empty($_GET['q'])) {
    $sql .= " AND (u.full_name LIKE :q OR u.email LIKE :q OR c.name LIKE :q)";
    $params[':q'] = '%' . $_GET['q'] . '%';
}
if (isset($_GET['is_employed']) && $_GET['is_employed'] !== '') {
    $sql .= " AND u.is_employed = :is_employed";
    $params[':is_employed'] = (int)$_GET['is_employed'];
}
if (!empty($_GET['attending_as_id'])) {
    $sql .= " AND t.attending_as_id = :attending_as_id";
    $params[':attending_as_id'] = (int)$_GET['attending_as_id'];
}

$sql .= " ORDER BY u.full_name ASC LIMIT 100"; // Limit results for performance

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Search failed: ' . $e->getMessage()]);
}