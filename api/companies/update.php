<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/core/initialize.php';
require_once dirname(__DIR__) . '/core/auth.php';

if (!has_permission('companies_update')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

// Basic validation
if (empty($data->id) || empty($data->name) || empty($data->email)) {
    echo json_encode(['success' => false, 'message' => 'Required fields (ID, Name, Email) are missing.']);
    exit();
}

$db = (new Database())->connect();

try {
    // Conditionally add password update to the query
    $password_sql = "";
    if (!empty($data->password)) {
        $password_sql = ", password_hash = :password_hash";
    }

    $query = "UPDATE companies SET 
                name = :name,
                email = :email,
                phone = :phone,
                address = :address,
                allow_login = :allow_login,
                is_active = :is_active
                $password_sql
              WHERE id = :id";
    
    $stmt = $db->prepare($query);

    // Bind parameters
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':name', $data->name);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':phone', $data->phone);
    $stmt->bindParam(':address', $data->address);
    $stmt->bindParam(':allow_login', $data->allow_login, PDO::PARAM_INT);
    $stmt->bindParam(':is_active', $data->is_active, PDO::PARAM_INT);

    if (!empty($data->password)) {
        $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
        $stmt->bindParam(':password_hash', $password_hash);
    }
    
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Company updated successfully.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error updating company: ' . $e->getMessage()]);
}