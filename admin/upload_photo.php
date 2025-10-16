<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Set headers for JSON response
header('Content-Type: application/json');

// Create upload directory if it doesn't exist
$upload_dir = '../assets/images/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

try {
    // Handle photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Validate file type
        if (!in_array($filetype, $allowed)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
        }

        // Validate file size (5MB max)
        if ($_FILES['profile_photo']['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }

        // Get current user's photo
        $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $current_photo = $stmt->fetchColumn();

        // Delete old photo if exists
        if ($current_photo && file_exists($upload_dir . $current_photo)) {
            unlink($upload_dir . $current_photo);
        }

        // Generate unique filename
        $new_filename = uniqid('profile_') . '.' . $filetype;
        $upload_path = $upload_dir . $new_filename;

        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
            // Update database
            $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->execute([$new_filename, $_SESSION['user_id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Profile photo updated successfully',
                'filename' => $new_filename
            ]);
        } else {
            throw new Exception('Failed to upload file.');
        }
    } else {
        throw new Exception('No file uploaded or upload error occurred.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 