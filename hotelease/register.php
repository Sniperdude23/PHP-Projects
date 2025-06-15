<?php 
require_once 'config.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = sanitizeInput($_POST['email']);
    $role = 'customer'; // Default role
    
    // Check if username already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $response['message'] = "Username or email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $password, $email, $role);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Registration successful! You can now login.";
        } else {
            $response['message'] = "Registration failed. Please try again.";
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>