<?php
// Set headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods, Authorization, X-Requested-With');

require_once dirname(__DIR__) . '/core/initialize.php';

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

// Get raw posted data
$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->email) || !isset($data->password)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

$email = $data->email;
$password = $data->password;

// Find user by email
$query = "SELECT id, full_name, password_hash, 'user' as type FROM users WHERE email = :email LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();

// NOTE: The UNION with the 'companies' table was removed for simplicity.
// If companies need to log into the same dashboard, you'll need a similar RBAC for them.

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($password, $row['password_hash'])) {
        // --- START: NEW PERMISSION LOGIC ---
        
        // Fetch user permissions
        $perm_query = "
            SELECT p.name 
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = :user_id
        ";
        
        $perm_stmt = $db->prepare($perm_query);
        $perm_stmt->bindParam(':user_id', $row['id']);
        $perm_stmt->execute();
        
        $permissions = $perm_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Password is correct, start session
        session_regenerate_id(); // Security best practice
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_name'] = $row['full_name'];
        $_SESSION['permissions'] = $permissions; // Store permissions array in session
        
        // --- END: NEW PERMISSION LOGIC ---
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
}
?>