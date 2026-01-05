<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

$db = (new Database())->connect();

// This is a global list, so no event_id is needed here.
// We only need id and name for the dropdown.
$query = "SELECT id, name FROM attending_as_types ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));