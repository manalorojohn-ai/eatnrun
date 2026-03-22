<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../config/db.php';

// Start the session
start_session_once();

// Check if user is authenticated
require_admin_auth();

// Check if user is admin
$user_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;
$sql = "SELECT id FROM users WHERE id = ? AND role = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $response = array();
    $file = $_FILES['profile_photo'];
    
    // File properties
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowed = array('jpg', 'jpeg', 'png', 'gif');
    
    if (in_array($file_ext, $allowed)) {
        if ($file_error === 0) {
            if ($file_size <= 5242880) { // 5MB max
                // Create unique filename
                $file_name_new = uniqid('profile_') . '.' . $file_ext;
                
                // Define upload directory path
                $upload_dir = dirname(dirname(__DIR__)) . '/uploads/profile_photos';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_destination = $upload_dir . '/' . $file_name_new;
                
                // Delete old profile photo if exists
                $sql = "SELECT profile_image FROM users WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$user_id]);
                $old_image = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($old_image && $old_image['profile_image']) {
                    $old_file = $upload_dir . '/' . $old_image['profile_image'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                if (move_uploaded_file($file_tmp, $file_destination)) {
                    // Update database with new photo
                    $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt->execute([$file_name_new, $user_id])) {
                        $response['status'] = 'success';
                        $response['message'] = 'Profile photo updated successfully';
                        $response['image'] = $file_name_new;
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = 'Database update failed';
                    }
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'Failed to upload file';
                }
            } else {
                $response['status'] = 'error';
                $response['message'] = 'File size too large. Maximum size is 5MB';
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Error uploading file';
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Invalid file type. Allowed types: jpg, jpeg, png, gif';
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} 