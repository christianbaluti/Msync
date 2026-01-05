<?php
/**
 * Event attendees info API
 * Compatible with LiteSpeed + PHP 8.2 + older MySQL (no ANY_VALUE)
 */

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/core/initialize.php';

$event_id = $_GET['event_id'] ?? 0;
$query    = trim($_GET['q'] ?? '');

if (empty($event_id) || empty($query)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    // Initialize DB connection
    $database = new Database();
    $db = $database->connect();

    // --- MAIN QUERY (no ANY_VALUE) ---
    $sql = "
        SELECT
            t.id AS ticket_id,
            t.ticket_code,
            COALESCE(MIN(u.full_name), MIN(c.name)) AS attendee_name,
            MIN(c.name) AS company_name,
            MAX(IF(ea.id IS NOT NULL AND ea.checked_in = 1, 1, 0)) AS is_checked_in,
            (
                SELECT JSON_ARRAYAGG(md.merch_id)
                FROM merchandise_distribution md
                WHERE md.ticket_id = t.id
            ) AS collected_merch_ids
        FROM event_tickets t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN companies c ON t.company_id = c.id OR u.company_id = c.id
        LEFT JOIN event_attendance ea ON t.id = ea.ticket_id
        WHERE t.event_id = :event_id
          AND (
                u.full_name LIKE :query
                OR u.email LIKE :query
                OR c.name LIKE :query
                OR c.email LIKE :query
                OR t.ticket_code = :ticket_code
          )
        GROUP BY t.id
        LIMIT 20
    ";

    $stmt = $db->prepare($sql);

    // Bind values safely
    $stmt->bindValue(':event_id', $event_id, PDO::PARAM_INT);
    $searchLike = '%' . $query . '%';
    $stmt->bindValue(':query', $searchLike, PDO::PARAM_STR);
    $stmt->bindValue(':ticket_code', $query, PDO::PARAM_STR);

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode collected_merch_ids JSON safely
    foreach ($results as &$row) {
        $row['collected_merch_ids'] = $row['collected_merch_ids']
            ? json_decode($row['collected_merch_ids'], true)
            : [];
    }

    echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Search failed',
        'error'   => $e->getMessage()
    ]);
}
