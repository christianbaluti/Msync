<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

if (!has_permission('users_update')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

// Basic validation
if (empty($data->id) || empty($data->full_name) || empty($data->email)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing.']);
    exit();
}

$database = new Database();
$db = $database->connect();
$db->beginTransaction();

try {
    // Update users table
    $password_sql = "";
    if (!empty($data->password)) {
        $password_sql = ", password_hash = :password_hash";
    }

    $query = "UPDATE users SET 
                full_name = :full_name,
                email = :email,
                phone = :phone,
                gender = :gender,
                is_employed = :is_employed,
                company_id = :company_id,
                position = :position,
                role = :role,
                is_active = :is_active
                $password_sql
              WHERE id = :id";
    
    $stmt = $db->prepare($query);

    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':full_name', $data->full_name);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':phone', $data->phone);
    $stmt->bindParam(':gender', $data->gender);
    $stmt->bindParam(':is_employed', $data->is_employed, PDO::PARAM_INT);
    $stmt->bindParam(':company_id', $data->company_id, $data->company_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindParam(':position', $data->position);
    $stmt->bindParam(':role', $data->role);
    $stmt->bindParam(':is_active', $data->is_active, PDO::PARAM_INT);

    if (!empty($data->password)) {
        $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
        $stmt->bindParam(':password_hash', $password_hash);
    }
    
    $stmt->execute();

    // First, remove all existing admin roles for this user
    $delete_roles_stmt = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
    $delete_roles_stmt->bindParam(':user_id', $data->id);
    $delete_roles_stmt->execute();
    
    // If the base role is 'admin' and admin roles are provided, add them
    if ($data->role === 'admin' && !empty($data->admin_roles)) {
        $role_query = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
        $role_stmt = $db->prepare($role_query);
        foreach ($data->admin_roles as $role_id) {
            $role_stmt->bindParam(':user_id', $data->id);
            $role_stmt->bindParam(':role_id', $role_id);
            $role_stmt->execute();
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'User updated successfully.']);

} catch (Exception $e) {
    $db->rollBack();
    // Provide a more specific error in a development environment
    echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()]);
}