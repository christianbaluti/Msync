<?php
// api/events/buy_ticket.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require '../../vendor/autoload.php';
require '../db_connection.php';
require '../auth_middleware.php';

$auth_data = get_auth_user();
$user_id = $auth_data->user_id;

$data = json_decode(file_get_contents("php://input"));
$event_id = $data->event_id ?? null;

if (!$event_id) {
    http_response_code(400);
    echo json_encode(['message' => 'Event ID is required.']);
    exit;
}

try {
    // Check if user already has a ticket
    $stmt_check = $pdo->prepare("SELECT id FROM event_tickets WHERE event_id = ? AND user_id = ?");
    $stmt_check->execute([$event_id, $user_id]);
    if ($stmt_check->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode(['message' => 'You already have a ticket for this event.']);
        exit;
    }
    
    // For simulation, find the first available ticket type for this event
    $stmt_type = $pdo->prepare("SELECT id, price FROM event_ticket_types WHERE event_id = ? LIMIT 1");
    $stmt_type->execute([$event_id]);
    $ticket_type = $stmt_type->fetch(PDO::FETCH_ASSOC);

    if (!$ticket_type) {
        // Create a default ticket type if none exist for the event
        $stmt_create_type = $pdo->prepare("INSERT INTO event_ticket_types (event_id, name, price) VALUES (?, 'General Admission', 0.00)");
        $stmt_create_type->execute([$event_id]);
        $ticket_type_id = $pdo->lastInsertId();
        $ticket_price = 0.00;
    } else {
        $ticket_type_id = $ticket_type['id'];
        $ticket_price = $ticket_type['price'];
    }

    // Insert the bought ticket
    $stmt_insert = $pdo->prepare("INSERT INTO event_tickets (ticket_type_id, event_id, user_id, status, price) VALUES (?, ?, ?, 'bought', ?)");
    $stmt_insert->execute([$ticket_type_id, $event_id, $user_id, $ticket_price]);
    
    http_response_code(201);
    echo json_encode(['message' => 'Ticket purchased successfully!']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>