<?php
// /api/payments/get_methods.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';

try {
    $db = (new Database())->connect();

    // 1. UPDATED QUERY: 
    // We select the raw COLUMN_TYPE.
    // We use DATABASE() to automatically use the currently connected DB name.
    $query = "SELECT COLUMN_TYPE 
              FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'payments'
              AND COLUMN_NAME = 'method'";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // 2. UPDATED PARSING LOGIC:
        // The raw string looks like: enum('cash','card','mobile_money')
        // We use Regex to pull out the content between the parentheses safely.
        $type = $result['COLUMN_TYPE'];
        $matches = [];
        
        // Match content inside enum(...)
        if (preg_match("/^enum\((.*)\)$/", $type, $matches)) {
            // Remove the surrounding single quotes from the string (e.g., 'cash','card' -> cash,card)
            $enum_content = str_replace("'", "", $matches[1]);
            $methods = explode(",", $enum_content);
            $methods = array_map('trim', $methods);

            // Exclude 'credit' 
            $filtered_methods = array_filter($methods, function($method) {
                return $method !== 'credit';
            });

            // Check for MALIPO gateway
            $gateway_check_stmt = $db->prepare("SELECT 1 FROM payment_gateways WHERE name = 'MALIPO' LIMIT 1");
            $gateway_check_stmt->execute();
            $malipo_exists = $gateway_check_stmt->rowCount() > 0;

            // Filter out 'gateway' if Malipo doesn't exist
            if (!$malipo_exists) {
                $filtered_methods = array_filter($filtered_methods, function($m) { 
                    return $m !== 'gateway'; 
                });
            }

            echo json_encode(['success' => true, 'data' => array_values($filtered_methods)]);
        } else {
             throw new Exception("Column is not an ENUM type.");
        }

    } else {
        // This usually happens if the table name is wrong or the DB connection didn't select a DB
        throw new Exception("Could not find the 'payments' table or 'method' column in the current database.");
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>