<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/core/initialize.php';
require_once dirname(__DIR__, 2) . '/core/auth.php';

// --- Validate ID ---
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameter: id.']);
    exit();
}

$id = intval($_GET['id']);
$db = (new Database())->connect();

try {
    $query = "
        SELECT 
            ett.id,
            ett.event_id,
            ett.name,
            ett.price,
            ett.member_type_id,
            mt.name AS member_type_name
        FROM event_ticket_types ett
        LEFT JOIN membership_types mt ON ett.member_type_id = mt.id
        WHERE ett.id = :id
        LIMIT 1
    ";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $ticket_type = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ticket_type) {
        // Return raw JSON object — frontend expects direct fields, not wrapped
        echo json_encode($ticket_type);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket type not found.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
