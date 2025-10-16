<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

// Create upload directory if it doesn't exist
$upload_dir = '../../assets/images/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            // Get current user's photo
            $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $current_photo = $stmt->fetchColumn();

            // Delete old photo if exists
            if ($current_photo && file_exists($upload_dir . $current_photo)) {
                unlink($upload_dir . $current_photo);
            }

            // Generate unique filename
            $new_filename = 'profile_' . $_SESSION['user_id'] . '_' . uniqid() . '.' . $filetype;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                // Update database
                $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                $stmt->execute([$new_filename, $_SESSION['user_id']]);
                $_SESSION['success'] = "Profile photo updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to upload file.";
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        }
    }
    header('Location: ../profile.php');
    exit();
}
?> 