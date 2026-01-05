<?php
// /api/ads/read_single.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

$db = (new Database())->connect();
$ad_id = $_GET['id'] ?? null;

if (!$ad_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ad ID is required.']);
    exit;
}

try {
    // 1. Get Ad Details
    $stmt_ad = $db->prepare("SELECT * FROM ads WHERE id = ?");
    $stmt_ad->execute([$ad_id]);
    $ad = $stmt_ad->fetch(PDO::FETCH_ASSOC);

    if (!$ad) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ad not found.']);
        exit;
    }

    // 2. Get Placements
    $stmt_placements = $db->prepare("SELECT * FROM ad_placements WHERE ad_id = ?");
    $stmt_placements->execute([$ad_id]);
    $placements = $stmt_placements->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'ad' => $ad,
            'placements' => $placements
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>