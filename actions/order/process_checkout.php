<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login first";
    header("Location: login.php");
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Check if cart is empty
    $check_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    if ($count === 0) {
        $_SESSION['error'] = "Your cart is empty";
        header("Location: checkout.php");
        exit();
    }
    
    // Check if checkout data exists
    if (!isset($_SESSION['checkout_data'])) {
        $_SESSION['error'] = "Invalid checkout process";
            header("Location: checkout.php");
            exit();
    }
    
    // Extract checkout data
    $checkout_data = $_SESSION['checkout_data'];
    $full_name = $checkout_data['full_name'];
    $delivery_address = $checkout_data['delivery_address'];
    $payment_method = $checkout_data['payment_method'];
    $notes = $checkout_data['notes'] ?? '';
    $phone = $checkout_data['phone'];
    $payment_proof = isset($checkout_data['payment_proof']) ? $checkout_data['payment_proof'] : null;
    
    // Handle file upload for payment proof
    if (($payment_method === 'gcash' || $payment_method === 'half_payment') && isset($_FILES['payment_proof'])) {
        if ($_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/payment_proofs/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception("Failed to create upload directory");
                }
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $_FILES['payment_proof']['tmp_name']);
            finfo_close($file_info);
            
            if (!in_array($mime_type, $allowed_types)) {
                throw new Exception("Please upload a valid image file (JPG, PNG, or GIF)");
            }
            
            // Validate file size (max 5MB)
            if ($_FILES['payment_proof']['size'] > 5 * 1024 * 1024) {
                throw new Exception("File size should be less than 5MB");
            }
            
            $file_extension = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            $file_name = uniqid('payment_') . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;
            
            if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_file)) {
                throw new Exception("Failed to upload payment proof");
            }
            
            $_SESSION['checkout_data']['payment_proof'] = $target_file;
        }
    }

    // Redirect to process_checkout.php
    header("Location: process_checkout.php");
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    
    // If there was a file upload and an error occurred, remove the uploaded file
    if (isset($payment_proof) && file_exists($payment_proof)) {
        unlink($payment_proof);
    }
    
    $_SESSION['error'] = "Error processing checkout: " . $e->getMessage();
    header("Location: checkout.php");
    exit();
} 