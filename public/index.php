<?php
// index.php
require_once '../api/core/initialize.php';
require_once dirname(__DIR__) . '/api/core/auth.php';
// Simple Router
$request_uri = explode('?', $_SERVER['REQUEST_URI'], 2);
$route = $request_uri[0];

// --- START: NEW API ROUTING LOGIC ---

// Check if the request is for an API endpoint
if (strpos($route, '/api/') === 0) {
    // Construct the path to the API file
    // Example: /api/auth/login.php -> ../api/auth/login.php
    $api_file_path = realpath(__DIR__ . '/..' . $route);

    // Ensure the file exists and is within the /api/ directory for security
    $base_api_path = realpath(__DIR__ . '/../api');
    
    if ($api_file_path && strpos($api_file_path, $base_api_path) === 0 && file_exists($api_file_path)) {
        // The file exists, so we load it
        require_once $api_file_path;
    } else {
        // The endpoint doesn't exist, return a 404
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'API Endpoint Not Found']);
    }
    // Stop the script to prevent loading a view
    exit();
}


// --- END: NEW API ROUTING LOGIC ---


// --- EXISTING VIEW ROUTING LOGIC ---
switch ($route) {
    case '/':
    case '/home':
        require __DIR__ . '/views/home.php';
        break;

    case '/error':
        require __DIR__ . '/views/error.php';
        break;
        
    case '/raffle':
        require __DIR__ . '/views/raffle.php';
        break;

    case '/login':
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            header('Location: /dashboard');
            exit;
        }
        require __DIR__ . '/views/login.php';
        break;

    case '/dashboard':
        // Protected route: check for login
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        require __DIR__ . '/views/dashboard.php';
        break;

    case '/users':
        // Protected route: check for permission
        if (!has_permission('users_read')) {
            http_response_code(403);
            require __DIR__ . '/views/403.php'; 
            exit;
        }
        require __DIR__ . '/views/users.php';
        break;
        
    case '/finance':
          require __DIR__ . '/views/finance.php';
          break;
          
    case '/privacy_policy':
          require __DIR__ . '/views/privacy_policy.php';
          break;
          
    case '/marketplace':
        require __DIR__ . '/views/marketplace.php';
        break;
        
    case '/ads':
        require __DIR__ . '/views/advertisements.php';
        break;
        
    case '/magazines':
        require __DIR__ . '/views/magazines.php';
        break;
        
    case '/audit-logs':
        require __DIR__ . '/views/audit.php';
        break;
        
    case '/ui-settings':
        require __DIR__ . '/views/settings.php';
        break;

    case '/memberships':
        // Protected route: check for permission
        if (!has_permission('membership_subscriptions_read')) {
            http_response_code(403);
            require __DIR__ . '/views/403.php'; // Create a simple "Forbidden" page if you like
            exit;
        }
        require __DIR__ . '/views/memberships.php';
        break;

    case '/memberships/create-id':
        // Protected route: check for permission
        if (!has_permission('membership_subscriptions_read')) {
            http_response_code(403);
            require __DIR__ . '/views/403.php'; // Create a simple "Forbidden" page if you like
            exit;
        }
        require __DIR__ . '/views/memberships/create_id.php';
        break;
        
    case '/memberships/subscribe':
        require __DIR__ . '/views/memberships/subscribe.php';
        
        break;

    case '/users/view':
        require __DIR__ . '/views/user/view_user.php';
        break;
        
    case '/import_users':
        require __DIR__ . '/views/user_imports.php';
        break;

    case '/news':
        require __DIR__ . '/views/news.php';
        break;
    case '/reports':
        require __DIR__ . '/views/reports.php';
        break;

    case '/companies':
        if (!has_permission('companies_read')) {
            http_response_code(403);
            require __DIR__ . '/views/403.php';
            exit;
        }
        require __DIR__ . '/views/companies.php';
        break;

    case '/events':
        if (!has_permission('events_read')) {
            http_response_code(403);
            require __DIR__ . '/views/403.php';
            exit;
        }
        require __DIR__ . '/views/events.php';
        break;

    case (strpos($route, '/events/manage') === 0):
        if (!has_permission('events_read')) { // Or a more specific permission
            http_response_code(403);
            require __DIR__ . '/views/403.php';
            exit;
        }
        // We create a new subfolder for better organization
        require __DIR__ . '/views/events/manage.php';
        break;

    //New cases for some routes under events
    case (strpos($route, '/events/checkin') === 0):
        // Add permission check if needed, e.g., for event staff
        require __DIR__ . '/views/events/checkin.php';
        break;

    case (strpos($route, '/events/nametags') === 0):
        require __DIR__ . '/views/events/nametags.php';
        break;

    case (strpos($route, '/events/purchases') === 0):
        require __DIR__ . '/views/events/purchases.php';
        break;

    case (strpos($route, '/events/schedules/manage') === 0):
        require __DIR__ . '/views/events/schedules/manage.php';
        break;

        
    default:
        http_response_code(404);
        require __DIR__ . '/views/404.php';
        break;
}
?>