<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';
// --- FIX 1: Include the Mailer class ---
require_once dirname(__DIR__) . '/core/mailer.php';

$data = json_decode(file_get_contents("php://input"));

// Extended validation
if (empty($data->full_name) || empty($data->email) || empty($data->password) || empty($data->role)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields (Full Name, Email, Password, Role).']);
    exit();
}
if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit();
}
if (strlen($data->password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit();
}


$database = new Database();
$db = $database->connect();
$db->beginTransaction();

try {
    // Check if email or phone already exists
    $check_stmt = $db->prepare("SELECT id FROM users WHERE email = :email OR (phone IS NOT NULL AND phone = :phone)");
    $check_stmt->bindParam(':email', $data->email);
    $check_stmt->bindParam(':phone', $data->phone);
    $check_stmt->execute();
    if ($check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'A user with this email or phone number already exists.']);
        $db->rollBack();
        exit();
    }

    // Insert into users table
    $query = "INSERT INTO users (full_name, email, phone, password_hash, gender, is_employed, company_id, position, role, is_active) 
              VALUES (:full_name, :email, :phone, :password_hash, :gender, :is_employed, :company_id, :position, :role, :is_active)";
    
    $stmt = $db->prepare($query);

    // We use the plain-text password from $data->password for the email
    // and hash it for the database.
    $password_hash = password_hash($data->password, PASSWORD_DEFAULT);

    $stmt->bindParam(':full_name', $data->full_name);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':phone', $data->phone);
    $stmt->bindParam(':password_hash', $password_hash);
    $stmt->bindParam(':gender', $data->gender);
    $stmt->bindParam(':is_employed', $data->is_employed, PDO::PARAM_INT);
    $stmt->bindParam(':company_id', $data->company_id, $data->company_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindParam(':position', $data->position);
    $stmt->bindParam(':role', $data->role);
    $stmt->bindParam(':is_active', $data->is_active, PDO::PARAM_INT);
    
    $stmt->execute();
    $user_id = $db->lastInsertId();

    // If the base role is 'admin' and admin roles are provided, add them
    if ($data->role === 'admin' && !empty($data->admin_roles)) {
        $role_query = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
        $role_stmt = $db->prepare($role_query);
        foreach ($data->admin_roles as $role_id) {
            $role_stmt->bindParam(':user_id', $user_id);
            $role_stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
            $role_stmt->execute();
        }
    }

    // --- FIX 2: Add the email sending logic here ---
    // This happens after the user is successfully inserted into the database.
    $mailer = new Mailer();
    $subject = "Your New Account Has Been Created";
    
    // Sanitize the name for display
    $user_name = htmlspecialchars($data->full_name);
    
    $body = "Hello {$user_name},<br><br>" .
            "An account has been created for you on our platform.<br><br>" .
            "You can now log in using the following credentials:<br>" .
            "<b>Email:</b> {$data->email}<br>" .
            // IMPORTANT: We use the original plain-text password from the form submission
            "<b>Password:</b> " . htmlspecialchars($data->password) . "<br><br>" .
            "We strongly recommend you change your password after your first login.<br><br>" .
            "Thank you.";

    // The send method will throw an Exception on failure, which will be caught below
    // and cause the database transaction to roll back.
    $emailSent = $mailer->send($data->email, $user_name, $subject, $body);


    if ($emailSent) {
     // --- FIX 3: Commit the transaction after everything succeeds ---
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'User created successfully and welcome email sent.']);
     } else {
     // If email fails, throw an exception to trigger the rollback
     throw new Exception("User created, but failed to send welcome email. Check mailer settings.");
    }

} catch (PDOException $e) {
    $db->rollBack();
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Error: A user with this email or phone already exists.']);
    } else {
        error_log("PDO Error in create user: " . $e->getMessage()); // Log detailed error
        echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
    }
} catch (Exception $e) {
    $db->rollBack();
    error_log("General Error in create user: " . $e->getMessage()); // Log detailed error
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}