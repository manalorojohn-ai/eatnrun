<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || (!isset($_SESSION['user_role']) && !isset($_SESSION['role'])) || 
    (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin') && 
    (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}

// Get admin user details
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT full_name, profile_image, role FROM users WHERE id = ? AND role = 'admin'";
$stmt = mysqli_prepare($conn, $admin_query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$admin_result = mysqli_stmt_get_result($stmt);
$admin_data = mysqli_fetch_assoc($admin_result);
$admin_name = $admin_data['full_name'] ?? 'Administrator';
$admin_profile_image = $admin_data['profile_image'] ?? '';
$admin_role = $admin_data['role'] ?? 'Administrator';

// Get initial counts
$unread_query = "SELECT COUNT(*) as unread FROM messages WHERE is_read = 0";
$unread_result = $conn->query($unread_query);
$unread_count = $unread_result->fetch_assoc()['unread'];

// AJAX handler for marking message as read
if (isset($_POST['action']) && $_POST['action'] === 'mark_read' && isset($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);
    $mark_query = "UPDATE messages SET is_read = 1 WHERE id = ?";
    $mark_stmt = $conn->prepare($mark_query);
    $mark_stmt->bind_param("i", $message_id);
    $result = $mark_stmt->execute();
    
    if ($result) {
        // Notify all connected clients about the update
        $response = [
            'type' => 'message_read',
            'message_id' => $message_id
        ];
        // Send WebSocket update (implementation in websocket_server.php)
        sendWebSocketUpdate($response);
    }
    
    echo json_encode(['success' => $result]);
    exit;
}

// AJAX handler for deleting message
if (isset($_POST['action']) && $_POST['action'] === 'delete_message' && isset($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);
    $delete_query = "DELETE FROM messages WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $message_id);
    $result = $delete_stmt->execute();
    
    if ($result) {
        // Notify all connected clients about the deletion
        $response = [
            'type' => 'message_deleted',
            'message_id' => $message_id
        ];
        // Send WebSocket update
        sendWebSocketUpdate($response);
    }
    
    echo json_encode(['success' => $result]);
    exit;
}

// AJAX handler for fetching messages
if (isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT m.*, 
              CASE 
                  WHEN TIMESTAMPDIFF(MINUTE, m.created_at, NOW()) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE, m.created_at, NOW()), ' minutes ago')
                  WHEN TIMESTAMPDIFF(HOUR, m.created_at, NOW()) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR, m.created_at, NOW()), ' hours ago')
                  ELSE DATE_FORMAT(m.created_at, '%b %d, %Y at %h:%i %p')
              END as time_ago
              FROM messages m
              ORDER BY m.created_at DESC
              LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];

    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM messages";
    $count_result = $conn->query($count_query);
    $total_messages = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_messages / $limit);
    
    // Get unread count
    $unread_query = "SELECT COUNT(*) as unread FROM messages WHERE is_read = 0";
    $unread_result = $conn->query($unread_query);
    $unread_count = $unread_result->fetch_assoc()['unread'];
    
    echo json_encode([
        'messages' => $messages,
        'total_messages' => $total_messages,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'unread_count' => $unread_count
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Messages</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: rgba(0, 108, 59, 0.05);
            --primary-lighter: rgba(0, 108, 59, 0.02);
            --primary-gradient: linear-gradient(135deg, #006C3B 0%, #008749 100%);
            --danger: #dc3545;
            --danger-light: rgba(220, 53, 69, 0.1);
            --danger-gradient: linear-gradient(135deg, #dc3545 0%, #e4606d 100%);
            --success: #28a745;
            --success-light: rgba(40, 167, 69, 0.1);
            --warning: #ffc107;
            --warning-light: rgba(255, 193, 7, 0.1);
            --info: #17a2b8;
            --gray-50: #fafafa;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.03);
            --shadow-xl: 0 15px 25px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: var(--gray-50);
            color: var(--gray-700);
            line-height: 1.6;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 240px;
            transition: var(--transition);
            min-height: 100vh;
            background: linear-gradient(180deg, var(--gray-50) 0%, white 100%);
            position: relative;
            z-index: 1;
        }
        
        .header-section {
            background: white;
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: visible;
            z-index: 9999;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }
        
        .header-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        
        .page-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .header-title {
            display: flex;
            flex-direction: column;
        }
        
        .header-title h1 {
            font-size: 2rem;
            color: var(--gray-800);
            font-weight: 600;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }
        
        .header-title .subtitle {
            color: var(--gray-600);
            font-size: 1rem;
            font-weight: 400;
        }
        
        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-xl);
            background: linear-gradient(to right, var(--primary-light), rgba(0, 108, 59, 0.02));
            transition: var(--transition);
            position: relative;
            z-index: 99999;
        }
        
        .profile-section:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .profile-image {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .notification-bell {
            position: relative;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(0, 108, 59, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .notification-bell:hover {
            background: rgba(0, 108, 59, 0.2);
            transform: translateY(-2px);
        }

        .notification-bell i {
            color: #006C3B;
            font-size: 1.2rem;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(255, 68, 68, 0.3);
        }

        .text-success {
            color: #006C3B !important;
        }

        .text-muted {
            color: #6c757d !important;
        }

        .small {
            font-size: 0.875rem;
        }

        .fw-bold {
            font-weight: 600;
        }
        
        .messages-container {
            background: white;
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--gray-200);
            position: relative;
            z-index: 1;
        }
        
        .refresh-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1.5rem;
            background: var(--success-light);
            border-radius: var(--radius-lg);
            border: 1px solid var(--success);
            transition: var(--transition);
        }
        
        .auto-refresh:hover {
            background: var(--success-light);
            transform: translateY(-1px);
        }
        
        .auto-refresh input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            accent-color: var(--success);
            cursor: pointer;
        }
        
        .auto-refresh label {
            color: var(--gray-800);
            font-size: 0.925rem;
            font-weight: 500;
            cursor: pointer;
        }
        
        .connection-status {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            border-radius: var(--radius-lg);
            font-size: 0.925rem;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .connection-status.connected {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .connection-status:not(.connected) {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid var(--warning);
        }
        
        .connection-status i {
            font-size: 0.75rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.875rem 1.75rem;
            border-radius: var(--radius-lg);
            font-size: 0.925rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            white-space: nowrap;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
                width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(-100%);
            transition: var(--transition);
        }
        
        .btn:hover::before {
            transform: translateX(0);
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.15);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 108, 59, 0.2);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.15);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.2);
        }

        .message-card {
            background: white;
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .message-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-light);
        }
        
        .message-card.new {
            animation: slideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .message-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
            opacity: 0;
            transition: var(--transition);
        }

        .message-card:hover::before {
            opacity: 1;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .sender-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .sender-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
            letter-spacing: -0.5px;
        }

        .sender-email {
            color: var(--gray-600);
            font-size: 0.925rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sender-email i {
            font-size: 0.875rem;
            color: var(--primary);
        }

        .message-date {
            color: var(--gray-500);
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .message-date i {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .message-content {
            color: var(--gray-700);
            line-height: 1.7;
            margin-bottom: 1.75rem;
            font-size: 1rem;
            padding: 1.5rem;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
        }

        .message-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        .unread-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: var(--success-gradient);
            color: white;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .unread-badge i {
            font-size: 0.75rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            color: var(--gray-600);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--gray-400);
            margin-bottom: 2rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: var(--gray-800);
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .empty-state p {
            color: var(--gray-600);
            font-size: 1rem;
            max-width: 400px;
            margin: 0 auto;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
        }
        
        .pagination button {
            min-width: 2.75rem;
            height: 2.75rem;
            padding: 0 1rem;
            border-radius: var(--radius-lg);
            background: white;
            border: 1px solid var(--gray-200);
            color: var(--gray-700);
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .pagination button:hover:not(.active):not(.disabled) {
            background: var(--gray-50);
            border-color: var(--gray-300);
            transform: translateY(-2px);
        }
        
        .pagination button.active {
            background: var(--primary-gradient);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.15);
        }
        
        .pagination button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .header-wrapper {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .header-title h1 {
                font-size: 1.75rem;
            }
            
            .profile-section {
                width: 100%;
            }

            .refresh-section {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .message-header {
                flex-direction: column;
                gap: 1rem;
            }

            .message-date {
                align-self: flex-start;
            }
            
            .message-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                width: 100%;
            }
            
            .pagination {
                gap: 0.5rem;
            }
            
            .pagination button {
                min-width: 2.5rem;
                height: 2.5rem;
                padding: 0 0.75rem;
                font-size: 0.875rem;
            }
        }
        
        /* Loading animation */
        .btn.loading {
            position: relative;
            pointer-events: none;
        }
        
        .btn.loading i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        .dropdown {
            position: relative;
        }

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 380px;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--gray-200);
            z-index: 99999;
            display: none;
            overflow: hidden;
            margin-top: 10px;
            transform-origin: top right;
        }

        .notification-dropdown.show {
            display: block;
            animation: dropdownFadeIn 0.2s ease-out;
        }

        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .notification-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
        }

        .notification-header h6 {
            font-size: 1rem;
            color: var(--gray-800);
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .notification-count-badge {
            background: var(--success-light);
            color: var(--success);
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-list {
            max-height: 400px;
            overflow-y: auto;
            background: var(--gray-50);
        }

        .notification-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            background: white;
        }

        .notification-item:hover {
            background: var(--gray-50);
        }

        .notification-item.unread {
            background: var(--primary-lighter);
        }

        .notification-item.unread::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary-gradient);
        }

        .notification-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-icon i {
            color: var(--primary);
            font-size: 1rem;
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            color: var(--gray-800);
            font-size: 0.925rem;
            line-height: 1.5;
            margin-bottom: 0.25rem;
        }

        .notification-time {
            color: var(--gray-600);
            font-size: 0.813rem;
        }

        .notification-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-200);
            background: white;
        }

        .notification-footer a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.925rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .notification-footer a:hover {
            color: var(--primary-dark);
        }

        .no-notifications {
            padding: 2rem;
            text-align: center;
            color: var(--gray-600);
            background: white;
        }

        .no-notifications i {
            font-size: 2rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }

        .dropdown {
            position: relative;
        }

        .notification-bell {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            position: relative;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }

        .notification-bell:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .notification-bell:focus {
            outline: none;
        }

        /* Add styles for connection status */
        .connection-status {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .connection-status.connected {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid #28a745;
        }

        .connection-status.disconnected {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid #ffc107;
        }

        .connection-status i {
            margin-right: 8px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="header-section">
            <div class="header-wrapper">
                <div class="page-header">
                    <div class="burger-icon" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </div>
                    <div class="header-title">
                        <h1>Messages</h1>
                        <span class="subtitle">Overview</span>
                    </div>
                </div>
                <div class="profile-section p-3 d-flex align-items-center gap-3">
                    <!-- Notification Bell Dropdown -->
                    <div class="dropdown">
                        <button type="button" class="notification-bell" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu notification-dropdown" id="notificationMenu" aria-labelledby="notificationDropdown">
                            <div class="notification-header">
                                <h6>
                                    <span class="fw-semibold">Notifications</span>
                                    <?php if ($unread_count > 0): ?>
                                    <span class="notification-count-badge">
                                        <i class="fas fa-bell"></i>
                                        <span><?php echo $unread_count; ?> New</span>
                                    </span>
                                    <?php endif; ?>
                                </h6>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <!-- Notifications will be loaded here via JavaScript -->
                            </div>
                            <div class="notification-footer">
                                <a href="notifications.php">
                                    View All Notifications
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold"><?php echo htmlspecialchars($admin_name ?? 'Administrator'); ?></div>
                        <div class="text-success">Administrator</div>
                        <div class="text-muted small">
                            <i class="fas fa-clock"></i>
                            Last updated: <?php echo date('h:i A'); ?>
                        </div>
                    </div>
                    <?php if (!empty($admin_profile_image) && file_exists("../uploads/profile_photos/{$admin_profile_image}")): ?>
                        <img src="../uploads/profile_photos/<?php echo htmlspecialchars($admin_profile_image); ?>" alt="Profile" class="profile-image">
                    <?php else: ?>
                        <img src="../assets/images/admin-avatar.png" alt="Profile" class="profile-image">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="messages-container">
            <div class="refresh-section">
                <div class="auto-refresh">
                    <input type="checkbox" id="autoRefresh" checked>
                    <label for="autoRefresh">Real-time updates active</label>
                </div>
                <div class="connection-status connected" id="connectionStatus">
                    <i class="fas fa-circle"></i>
                    <span>Connected</span>
                </div>
                <button id="refreshBtn" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh</span>
                </button>
            </div>
            
            <div id="messagesList" class="messages-list">
                <!-- Messages will be loaded here -->
            </div>
            
            <div id="pagination" class="pagination">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'))
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl, {
                    offset: [0, 10],
                    popperConfig: function (defaultBsPopperConfig) {
                        return {
                            ...defaultBsPopperConfig,
                            strategy: 'fixed'
                        }
                    }
                });
            });

            // Load notifications when dropdown is shown
            const notificationDropdown = document.getElementById('notificationDropdown');
            notificationDropdown.addEventListener('shown.bs.dropdown', function () {
                loadNotifications();
            });

            const messagesList = document.getElementById('messagesList');
            const pagination = document.getElementById('pagination');
            const refreshBtn = document.getElementById('refreshBtn');
            const autoRefreshCheckbox = document.getElementById('autoRefresh');
            const connectionStatus = document.getElementById('connectionStatus');
            const unreadCountElement = document.getElementById('unreadCount');
            const lastUpdated = document.getElementById('lastUpdated');
            
            let currentPage = 1;
            let isRefreshing = false;
            let ws = null;
            
            // Initialize WebSocket
            function initWebSocket() {
                // Use dynamic protocol (ws:// or wss://) based on page protocol
                const protocol = window.location.protocol === 'https:' ? 'wss://' : 'ws://';
                const host = window.location.hostname;
                ws = new WebSocket(`${protocol}${host}:8080`);
                
                ws.onopen = function() {
                    console.log('WebSocket Connected');
                    connectionStatus.classList.add('connected');
                    connectionStatus.innerHTML = '<i class="fas fa-circle"></i> Connected';
                    document.querySelector('.connection-status').classList.remove('disconnected');
                    document.querySelector('.connection-status').classList.add('connected');
                    
                    // Register as admin
                    ws.send(JSON.stringify({
                        type: 'register_admin',
                        adminId: <?php echo $_SESSION['user_id']; ?>
                    }));

                    // Load initial notifications
                    loadNotifications();
                };
                
                ws.onclose = function() {
                    console.log('WebSocket Disconnected');
                    connectionStatus.classList.remove('connected');
                    connectionStatus.innerHTML = '<i class="fas fa-circle"></i> Disconnected';
                    document.querySelector('.connection-status').classList.remove('connected');
                    document.querySelector('.connection-status').classList.add('disconnected');
                    
                    // Try to reconnect after 5 seconds
                    setTimeout(initWebSocket, 5000);
                };
                
                ws.onerror = function(error) {
                    console.error('WebSocket Error:', error);
                    connectionStatus.classList.remove('connected');
                    connectionStatus.innerHTML = '<i class="fas fa-circle"></i> Connection Error';
                    document.querySelector('.connection-status').classList.remove('connected');
                    document.querySelector('.connection-status').classList.add('disconnected');
                };
                
                ws.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    handleWebSocketMessage(data);
                    
                    // Update notifications if needed
                    if (data.type === 'new_notification' || data.type === 'notification_update') {
                        loadNotifications();
                    }
                };
            }

            // Function to load notifications
            function loadNotifications() {
                fetch('api/admin_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        const notificationList = document.getElementById('notificationList');
                        if (data.notifications && data.notifications.length > 0) {
                            notificationList.innerHTML = data.notifications.map(notification => `
                                <div class="notification-item ${notification.is_read ? 'read' : 'unread'}" 
                                     data-id="${notification.id}" 
                                     data-link="${notification.link}" 
                                     onclick="handleNotificationClick(event, this)">
                                    <div class="notification-icon">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-message">${notification.message}</div>
                                        <div class="notification-time">${notification.time_ago}</div>
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            notificationList.innerHTML = `
                                <div class="no-notifications">
                                    <i class="fas fa-bell-slash"></i>
                                    <p>No new notifications</p>
                                </div>
                            `;
                        }
                        
                        // Update unread count
                        updateUnreadCount(data.unread_count);
                    });
            }

            // Function to handle notification click
            function handleNotificationClick(event, element) {
                event.stopPropagation(); // Prevent dropdown from closing
                const id = element.dataset.id;
                const link = element.dataset.link;
                
                // Mark as read
                fetch('api/admin_notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=mark_read&notification_id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        element.classList.remove('unread');
                        element.classList.add('read');
                        updateUnreadCount(data.unread_count);
                        
                        // Navigate to the link
                        if (link) {
                            window.location.href = link;
                        }
                    }
                });
            }
            
            function handleWebSocketMessage(data) {
                switch(data.type) {
                    case 'new_message':
                        loadMessages(currentPage, true);
                        updateUnreadCount(data.unread_count);
                        showNotification('New Message', 'You have received a new message');
                        break;
                    case 'message_read':
                        updateMessageReadStatus(data.message_id);
                        updateUnreadCount(data.unread_count);
                        break;
                    case 'message_deleted':
                        removeMessage(data.message_id);
                        break;
                }
                updateLastUpdated();
            }
            
            function updateUnreadCount(count) {
                unreadCountElement.textContent = count;
                unreadCountElement.style.display = count > 0 ? 'flex' : 'none';
            }
            
            function updateLastUpdated() {
                const now = new Date();
                lastUpdated.textContent = now.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit', 
                    hour12: true 
                });
            }
            
            function loadMessages(page = 1, isNewMessage = false) {
                if (isRefreshing) return;
                
                isRefreshing = true;
                refreshBtn.classList.add('loading');
                
                fetch(`messages.php?action=get_messages&page=${page}`)
                    .then(response => response.json())
                    .then(data => {
                        currentPage = data.current_page;
                            renderMessages(data.messages, isNewMessage);
                            renderPagination(data.total_pages, data.current_page);
                        updateUnreadCount(data.unread_count);
                        isRefreshing = false;
                        refreshBtn.classList.remove('loading');
                        updateLastUpdated();
                    })
                    .catch(error => {
                        console.error('Error loading messages:', error);
                        isRefreshing = false;
                        refreshBtn.classList.remove('loading');
                    });
            }
            
            function renderMessages(messages, isNewMessage) {
                if (messages.length === 0) {
                            messagesList.innerHTML = `
                                <div class="empty-state">
                                    <i class="far fa-envelope"></i>
                                    <h3>No messages yet</h3>
                                    <p>When you receive messages, they will appear here.</p>
                                </div>
                    `;
                    return;
                }
                
                const messagesHTML = messages.map(message => `
                    <div class="message-card${isNewMessage ? ' new' : ''}" id="message-${message.id}">
                        <div class="message-header">
                            <div class="sender-info">
                                <div class="sender-name">${message.name}</div>
                                <div class="sender-email">
                                    <i class="fas fa-envelope"></i>
                                    ${message.email}
                                </div>
                            </div>
                            <div class="message-date">
                                <i class="fas fa-clock"></i>
                                ${message.time_ago}
                            </div>
                        </div>
                        <div class="message-content">${message.message}</div>
                        <div class="message-actions">
                            ${!message.is_read ? `
                                <button class="btn btn-outline" onclick="markAsRead(${message.id})">
                                    <i class="fas fa-check"></i>
                                    Mark as Read
                                </button>
                            ` : ''}
                            <button class="btn btn-danger" onclick="deleteMessage(${message.id})">
                                <i class="fas fa-trash"></i>
                                Delete
                            </button>
                        </div>
                        ${!message.is_read ? `
                            <div class="unread-badge">
                                <i class="fas fa-envelope"></i>
                                New Message
                            </div>
                        ` : ''}
                    </div>
                `).join('');
                
                messagesList.innerHTML = messagesHTML;
            }
            
            function renderPagination(totalPages, currentPage) {
                if (totalPages <= 1) {
                    pagination.innerHTML = '';
                    return;
                }
                
                let paginationHTML = `
                    <button onclick="loadMessages(1)" class="btn-page${currentPage === 1 ? ' disabled' : ''}"${currentPage === 1 ? ' disabled' : ''}>
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                    <button onclick="loadMessages(${currentPage - 1})" class="btn-page${currentPage === 1 ? ' disabled' : ''}"${currentPage === 1 ? ' disabled' : ''}>
                        <i class="fas fa-angle-left"></i>
                    </button>
                `;
                
                for (let i = 1; i <= totalPages; i++) {
                    if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                        paginationHTML += `
                            <button onclick="loadMessages(${i})" class="btn-page${currentPage === i ? ' active' : ''}">${i}</button>
                        `;
                    } else if (i === currentPage - 3 || i === currentPage + 3) {
                        paginationHTML += '<span class="pagination-dots">...</span>';
                    }
                }
                
                paginationHTML += `
                    <button onclick="loadMessages(${currentPage + 1})" class="btn-page${currentPage === totalPages ? ' disabled' : ''}"${currentPage === totalPages ? ' disabled' : ''}>
                        <i class="fas fa-angle-right"></i>
                    </button>
                    <button onclick="loadMessages(${totalPages})" class="btn-page${currentPage === totalPages ? ' disabled' : ''}"${currentPage === totalPages ? ' disabled' : ''}>
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                `;
                
                pagination.innerHTML = paginationHTML;
            }
            
            function markAsRead(messageId) {
                fetch('messages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=mark_read&message_id=${messageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateMessageReadStatus(messageId);
                    }
                });
            }
            
            function deleteMessage(messageId) {
                if (!confirm('Are you sure you want to delete this message?')) return;
                
                fetch('messages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_message&message_id=${messageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        removeMessage(messageId);
                    }
                });
            }
            
            function updateMessageReadStatus(messageId) {
                const messageCard = document.getElementById(`message-${messageId}`);
                if (messageCard) {
                    const unreadBadge = messageCard.querySelector('.unread-badge');
                    const markAsReadBtn = messageCard.querySelector('.btn-outline');
                    
                    if (unreadBadge) unreadBadge.remove();
                    if (markAsReadBtn) markAsReadBtn.remove();
                }
            }
            
            function removeMessage(messageId) {
                const messageCard = document.getElementById(`message-${messageId}`);
                if (messageCard) {
                    messageCard.style.animation = 'slideOut 0.3s ease-out forwards';
                    setTimeout(() => {
                        messageCard.remove();
                        if (messagesList.children.length === 0) {
                            loadMessages(currentPage);
                        }
                    }, 300);
                }
            }
            
            function showNotification(title, message) {
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification(title, { body: message });
                } else if ('Notification' in window && Notification.permission !== 'denied') {
                    Notification.requestPermission().then(permission => {
                        if (permission === 'granted') {
                            new Notification(title, { body: message });
                        }
                    });
                }
            }
            
            // Event Listeners
            refreshBtn.addEventListener('click', () => loadMessages(currentPage));
            
            autoRefreshCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    initWebSocket();
                } else {
                    if (ws) ws.close();
                }
            });
            
            // Initialize
            loadMessages();
            if (autoRefreshCheckbox.checked) {
                initWebSocket();
            }
            
            // Request notification permission
            if ('Notification' in window) {
                Notification.requestPermission();
            }
            
            // Make functions globally available
            window.loadMessages = loadMessages;
            window.markAsRead = markAsRead;
            window.deleteMessage = deleteMessage;
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/notifications.js"></script>
</body>
</html>