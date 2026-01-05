<?php
// THIS SCRIPT IS ALREADY CORRECT

header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';

$event_id = $_GET['event_id'] ?? 0;
$query = $_GET['q'] ?? '';

if (empty($event_id) || empty($query)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$db = new Database();
$sql = "
    SELECT
        t.id AS ticket_id,
        t.ticket_code,
        COALESCE(u.full_name, c.name) AS attendee_name,
        c.name AS company_name,
        mc.status AS meal_status
    FROM event_tickets t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN companies c ON t.company_id = c.id OR u.company_id = c.id
    INNER JOIN meal_cards mc ON t.id = mc.ticket_id
    WHERE t.event_id = :event_id
    AND mc.status = 'about_to_collect'
    AND (u.full_name LIKE :query OR u.email LIKE :query OR c.name LIKE :query OR t.ticket_code = :ticket_code)
    GROUP BY t.id
    LIMIT 20
";

$stmt = $db->connect()->prepare($sql);
$stmt->bindValue(':event_id', $event_id);
$stmt->bindValue(':query', '%' . $query . '%');
$stmt->bindValue(':ticket_code', $query);

try {
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Search failed: ' . $e->getMessage()]);
}