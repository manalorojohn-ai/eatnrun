<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get unread notifications count
    if (isset($_GET['count'])) {
        $count_query = "SELECT COUNT(*) as count FROM admin_notifications WHERE admin_id = ? AND is_read = 0";
        $stmt = mysqli_prepare($conn, $count_query);
        mysqli_stmt_bind_param($stmt, "i", $admin_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];
        
        echo json_encode(['unread_count' => $count]);
        exit();
    }

    // Get latest notifications
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
    $query = "SELECT * FROM admin_notifications 
              WHERE admin_id = ? 
              ORDER BY created_at DESC 
              LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $admin_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = [
            'id' => $row['id'],
            'type' => $row['type'],
            'message' => $row['message'],
            'link' => $row['link'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode(['notifications' => $notifications]);
    exit();
}

// Mark notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['mark_read'])) {
        $notification_id = intval($data['notification_id']);
        
        $update_query = "UPDATE admin_notifications SET is_read = 1 
                        WHERE id = ? AND admin_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ii", $notification_id, $admin_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to mark notification as read']);
        }
        exit();
    }

    // Mark all as read
    if (isset($data['mark_all_read'])) {
        $update_query = "UPDATE admin_notifications SET is_read = 1 
                        WHERE admin_id = ? AND is_read = 0";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $admin_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to mark all notifications as read']);
        }
        exit();
    }
} 