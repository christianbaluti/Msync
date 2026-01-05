<?php
// api/schedules/redeem_meal_card.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require '../../vendor/autoload.php';
require '../db_connection.php';
require '../auth_middleware_for_all.php';

// ✅ START: Read POST body ONCE
$data = json_decode(file_get_contents("php://input"));
if ($data === null) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid JSON body.']);
    exit;
}
// ✅ END: Read POST body

// ✅ CHANGED: Pass $data to auth function
$auth_data = get_auth_user($data);
$user_id = $auth_data->user_id;

// ✅ CHANGED: Read from $data object
$meal_card_id = $data->meal_card_id ?? null;

if (!$meal_card_id) {
    http_response_code(400);
    echo json_encode(['message' => 'Meal Card ID is required.']);
    exit;
}

try {
    // Security check: Make sure the user redeeming the card is the one who owns the ticket
    $stmt_check = $pdo->prepare("SELECT mc.status FROM meal_cards mc JOIN event_tickets et ON mc.ticket_id = et.id WHERE mc.id = ? AND et.user_id = ?");
    $stmt_check->execute([$meal_card_id, $user_id]);
    $card = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$card) {
        http_response_code(403);
        echo json_encode(['message' => 'You do not have permission to redeem this card.']);
        exit;
    }

    if ($card['status'] !== 'about_to_collect') {
        http_response_code(409); // Conflict
        echo json_encode(['message' => 'This meal card is not currently redeemable. It might be inactive or already collected.']);
        exit;
    }

    // Update the status to 'collected'
    $stmt_update = $pdo->prepare("UPDATE meal_cards SET status = 'collected' WHERE id = ?");
    $stmt_update->execute([$meal_card_id]);

    echo json_encode(['message' => 'Meal card redeemed successfully! Enjoy your meal.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}
?>