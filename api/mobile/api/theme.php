<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// include DB connection
require_once 'db_connection.php';

$response = [];

try {
    $sql = "
        SELECT 
            uts.id AS theme_id,
            uts.name,
            uts.is_default,
            uts.config,
            aus.updated_at
        FROM active_ui_setting aus
        LEFT JOIN ui_themes uts ON aus.theme_id = uts.id
        WHERE aus.id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => 1]); // bind parameter

    $row = $stmt->fetch();

    if ($row) {
        $response = [
            "status" => "success",
            "theme_id" => $row['theme_id'],
            "name" => $row['name'],
            "is_default" => (bool)$row['is_default'],
            "config" => json_decode($row['config'], true), // decode JSON into array
            "updated_at" => $row['updated_at']
        ];
    } else {
        $response = [
            "status" => "error",
            "message" => "No active theme found"
        ];
    }

} catch (PDOException $e) {
    $response = [
        "status" => "error",
        "message" => $e->getMessage()
    ];
}

echo json_encode($response);
