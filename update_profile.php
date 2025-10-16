<?php
session_start();
require_once 'includes/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field = $_POST['field'] ?? '';
    $value = trim($_POST['value'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Validation based on field type
    switch ($field) {
        case 'full_name':
            if (!preg_match('/^[a-zA-Z\s]{2,50}$/', $value)) {
                echo json_encode(['success' => false, 'message' => 'Invalid name format']);
                exit();
            }
            break;
            
        case 'email':
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit();
            }
            // Check for duplicate email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $value, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit();
            }
            break;
            
        case 'phone':
            if (!preg_match('/^09\d{9}$/', $value)) {
                echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
                exit();
            }
            break;
            
        case 'delivery_address':
            if (empty($value)) {
                echo json_encode(['success' => false, 'message' => 'Address cannot be empty']);
                exit();
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid field']);
            exit();
    }
    
    // Update the field
    $stmt = $conn->prepare("UPDATE users SET $field = ? WHERE id = ?");
    $stmt->bind_param("si", $value, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 