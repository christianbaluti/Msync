<?php
// /api/elections/get_nominees_for_position.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

// Validate that a position_id was provided in the URL
if (!isset($_GET['position_id']) || !filter_var($_GET['position_id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'A valid Position ID is required.']);
    exit;
}
$position_id = $_GET['position_id'];

$db = (new Database())->connect();

// This query joins across users and companies to get the display name for any nominee type
$query = "
    SELECT
        en.id,
        en.nomination_text,
        u.full_name AS user_name,
        c.name AS company_name
    FROM election_nominations en
    LEFT JOIN users u ON en.nominee_user_id = u.id
    LEFT JOIN companies c ON en.nominee_company_id = c.id
    WHERE en.seat_id = :position_id
    ORDER BY COALESCE(u.full_name, c.name, en.nomination_text) ASC
";

try {
    $stmt = $db->prepare($query);
    $stmt->bindParam(':position_id', $position_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $nominations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $results = [];

    foreach ($nominations as $nom) {
        // This logic creates a single, clean 'display_name' for the frontend to use
        $display_name = $nom['user_name'] ?? $nom['company_name'] ?? $nom['nomination_text'] ?? 'N/A';
        $results[] = [
            'id' => $nom['id'],
            'display_name' => htmlspecialchars($display_name) // Sanitize for safety
        ];
    }

    echo json_encode(['success' => true, 'data' => $results]);

} catch (PDOException $e) {
    // For debugging, you can log the error: error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred while fetching nominees.']);
}