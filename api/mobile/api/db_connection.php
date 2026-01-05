<?php
// db_connection.php

require_once dirname(__DIR__, 2) . '/core/env.php';;

$host     = $_ENV['DB_HOST'];
$db       = $_ENV['DB_DATABASE'];
$user     = $_ENV['DB_USERNAME'];
$pass     = $_ENV['DB_PASSWORD'];
$charset  = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Database connection failed",
        "error"   => $e->getMessage()
    ]);
    exit;
}
