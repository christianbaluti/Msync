<?php
header('Content-Type: application/json');
// Initializes database connection class
require_once dirname(__DIR__) . '/core/initialize.php'; 

// 1. Get parameters from the app's query string
$current_version_code = isset($_GET['version_code']) ? (int)$_GET['version_code'] : 0;
$platform = isset($_GET['platform']) ? $_GET['platform'] : 'all'; // 'android' or 'ios'

// 2. Derive the base update URL from the server variables
// This strips 'api/mobile/api/check_update.php' to get 'https://app.imm.mw/'
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$base_path = str_replace('/api/mobile/api/check_update.php', '', $_SERVER['REQUEST_URI']);
// Use rtrim to ensure it's a clean base URL ending with a slash
$update_url = rtrim($base_url . $base_path, '/') . '/';

// 3. Connect to the database
$db = (new Database())->connect();

try {
    // 4. Query the database
    // Find the newest *forced* update that is *greater* than the user's current version
    $query = "SELECT version_name, version_code, release_notes
              FROM update_release
              WHERE (platform = :platform OR platform = 'all')
                AND is_force_update = 1
                AND version_code > :current_version_code
              ORDER BY version_code DESC
              LIMIT 1";
    
    $stmt = $db->prepare($query);

    // Bind parameters
    $stmt->bindParam(':platform', $platform);
    $stmt->bindParam(':current_version_code', $current_version_code, PDO::PARAM_INT);

    $stmt->execute();
    
    $update = $stmt->fetch(PDO::FETCH_ASSOC);

    // 5. Send the response
    if ($update) {
        // An update is required
        echo json_encode([
            'success' => true, // Added success flag
            'update_required' => true,
            'latest_version_name' => $update['version_name'],
            'latest_version_code' => (int)$update['version_code'],
            'release_notes' => $update['release_notes'],
            'update_url' => $update_url 
        ]);
    } else {
        // No forced update found
        echo json_encode([
            'success' => true, // Added success flag
            'update_required' => false
        ]);
    }

} catch (Exception $e) {
    // Handle database or other errors
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'update_required' => false, // Default to false on error
        'message' => 'Error: ' . $e->getMessage()
    ]);
}