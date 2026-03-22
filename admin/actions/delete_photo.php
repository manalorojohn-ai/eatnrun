<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = '../../assets/images/profiles/';
    
    // Get current user's photo
    $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_photo = $stmt->fetchColumn();

    if ($current_photo && file_exists($upload_dir . $current_photo)) {
        unlink($upload_dir . $current_photo);
        
        // Update database
        $stmt = $conn->prepare("UPDATE users SET profile_photo = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['success'] = "Profile photo removed successfully!";
    } else {
        $_SESSION['error'] = "No photo found to delete.";
    }
    
    header('Location: ../profile.php');
    exit();
}
?> 