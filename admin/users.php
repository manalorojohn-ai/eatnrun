<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle document approval/rejection
if (isset($_POST['approve_documents'])) {
    $response = array('success' => false);
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';
    
    if ($user_id > 0 && in_array($action, ['approve', 'reject'])) {
        mysqli_begin_transaction($conn);
        
        try {
            if ($action === 'approve') {
                $update_query = "UPDATE users SET 
                    document_status = 'approved', 
                    documents_reviewed_at = NOW(),
                    documents_reviewed_by = ?,
                    rejection_reason = NULL
                    WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $user_id);
            } else {
                if (empty($rejection_reason)) {
                    throw new Exception("Rejection reason is required");
                }
                $update_query = "UPDATE users SET 
                    document_status = 'rejected', 
                    documents_reviewed_at = NOW(),
                    documents_reviewed_by = ?,
                    rejection_reason = ?
                    WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "isi", $_SESSION['user_id'], $rejection_reason, $user_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                // Create notification for user
                $message = $action === 'approve' 
                    ? "Your documents have been approved! You can now access your account."
                    : "Your documents were rejected. Reason: " . $rejection_reason;
                
                $notif_query = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'document_review')";
                $stmt = mysqli_prepare($conn, $notif_query);
                mysqli_stmt_bind_param($stmt, "is", $user_id, $message);
                mysqli_stmt_execute($stmt);
                
                mysqli_commit($conn);
                $response['success'] = true;
                $response['message'] = "Documents " . $action . "d successfully";
            } else {
                throw new Exception("Failed to update document status");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = "Error: " . $e->getMessage();
        }
    } else {
        $response['message'] = "Invalid request";
    }
    
    echo json_encode($response);
    exit();
}

// Handle user status updates
if (isset($_POST['update_status'])) {
    $response = array('success' => false);
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
    
    if ($user_id > 0) {
        // Prevent deactivating self
        if ($user_id == $_SESSION['user_id']) {
            $response['message'] = "You cannot deactivate your own account.";
            echo json_encode($response);
            exit();
        }
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // First, check if user exists
            $check_query = "SELECT role FROM users WHERE id = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "i", $user_id);
            mysqli_stmt_execute($check_stmt);
            $result = mysqli_stmt_get_result($check_stmt);
            
            if ($user = mysqli_fetch_assoc($result)) {
                // Update user status
                $update_query = "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "ii", $status, $user_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    // If user is being deactivated, also update their orders to 'cancelled' status
                    if ($status == 0) {
                        $update_orders_query = "UPDATE orders SET status = 'cancelled' WHERE user_id = ? AND status IN ('pending', 'processing')";
                        $orders_stmt = mysqli_prepare($conn, $update_orders_query);
                        mysqli_stmt_bind_param($orders_stmt, "i", $user_id);
                        mysqli_stmt_execute($orders_stmt);
                    }
                    
                    mysqli_commit($conn);
                    $response['success'] = true;
                } else {
                    throw new Exception("Failed to update user status");
                }
            } else {
                throw new Exception("User not found");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = "Error: " . $e->getMessage();
        }
    } else {
        $response['message'] = "Invalid user ID";
    }
    
    echo json_encode($response);
    exit();
}

// Get users statistics
$stats_query = "SELECT 
    COUNT(CASE WHEN role = 'customer' AND status = 'active' THEN 1 END) as active_customers,
    COUNT(CASE WHEN role = 'customer' AND status = 'inactive' THEN 1 END) as inactive_customers,
    COUNT(CASE WHEN role = 'admin' THEN 1 END) as total_admins,
    COUNT(CASE WHEN document_status = 'pending' THEN 1 END) as pending_documents
    FROM users";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get pending document submissions
$pending_docs_query = "SELECT 
    u.id, 
    u.full_name, 
    u.email, 
    u.phone, 
    u.photo_1x1, 
    u.photo_2x2, 
    u.valid_id_type, 
    u.valid_id_image, 
    u.documents_submitted_at,
    u.rejection_reason
    FROM users u 
    WHERE u.document_status = 'pending' 
    ORDER BY u.documents_submitted_at ASC";
$pending_docs_result = mysqli_query($conn, $pending_docs_query);

// Check users table structure to get actual column names
$check_table_query = "DESCRIBE users";
$table_structure = mysqli_query($conn, $check_table_query);
$has_fullname = false;
$name_column = "name";

// Determine whether to use name or full_name for queries
while ($column = mysqli_fetch_assoc($table_structure)) {
    if ($column['Field'] == 'full_name') {
        $has_fullname = true;
        $name_column = "full_name";
        break;
    }
}

// Get admin list
$admin_query = "SELECT 
    id, 
    COALESCE($name_column, '') as display_name, 
    COALESCE(email, '') as email, 
    COALESCE(phone, '') as phone, 
    COALESCE(status, 'active') as status, 
    created_at 
    FROM users 
    WHERE role = 'admin'";
$admin_result = mysqli_query($conn, $admin_query);

// Get customer list
$customer_query = "SELECT 
    u.id, 
    COALESCE(u.$name_column, '') as display_name, 
    COALESCE(u.email, '') as email, 
    COALESCE(u.phone, '') as phone, 
    COALESCE(u.status, 0) as status, 
    u.created_at,
    COUNT(o.id) as order_count, 
    COALESCE(SUM(o.total_amount), 0) as total_spent 
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id 
    WHERE u.role = 'customer' 
    GROUP BY u.id, u.$name_column, u.email, u.phone, u.status, u.created_at";

// Execute the customer query with error handling
$customer_result = mysqli_query($conn, $customer_query);

// Check for SQL errors
if (!$customer_result) {
    echo "<div class='alert alert-danger'>Error loading customer data: " . mysqli_error($conn) . "</div>";
    // Try a simpler query without JOINs as fallback
    $fallback_query = "SELECT id, $name_column as display_name, email, phone, status, created_at 
                      FROM users 
                      WHERE role = 'customer'";
    $customer_result = mysqli_query($conn, $fallback_query);
    
    if (!$customer_result) {
        echo "<div class='alert alert-danger'>Fatal error: Cannot load user data. Please check your database structure.</div>";
        // Create an empty result set to avoid further errors
        $customer_result = false;
    }
}

// Add error handling and debug information removal
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/notifications.css" rel="stylesheet">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: rgba(0,108,59,0.1);
            --danger: #dc3545;
            --danger-dark: #c82333;
            --success: #28a745;
            --warning: #ffc107;
            --light: #f8f9fa;
            --dark: #343a40;
            --white: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-hover: 0 6px 16px rgba(0,0,0,0.12);
            --shadow-modal: 0 10px 25px rgba(0,0,0,0.18);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --border-radius-lg: 16px;
            --transition-speed: 0.3s;
            --transition-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --animation-duration: 0.6s;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: var(--gray-100);
            color: var(--gray-700);
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 210px;
            background-color: #006C3B;
            background-image: linear-gradient(to bottom, #006C3B, #005530);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .brand-section {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px;
            background: rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .brand-section img {
            width: 36px;
            height: 36px;
            border-radius: 6px;
        }

        .brand-text {
            color: white;
            font-size: 16px;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
        }

        .nav-links {
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }

        .nav-item {
            border-radius: 6px;
            transition: var(--transition);
            position: relative;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: var(--transition);
            font-size: 14px;
            white-space: nowrap;
        }

        .nav-item.active {
            background: white;
        }

        .nav-item.active a {
            color: #006C3B;
            font-weight: 500;
        }

        .nav-item i {
            width: 18px;
            font-size: 16px;
            text-align: center;
        }

        .nav-bottom {
            padding: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }

        .nav-bottom .nav-item {
            margin-bottom: 6px;
        }

        .badge {
            background-color: #f8f9fa;
            color: #006C3B;
            border-radius: 50%;
            font-size: 11px;
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            font-weight: bold;
        }

        .user-status {
            position: absolute;
            top: 50%;
            right: 12px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #4ade80;
            transform: translateY(-50%);
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 240px;
            padding: 0 24px 24px 24px;
            transition: var(--transition);
            max-width: calc(100% - 240px);
            box-sizing: border-box;
            position: relative;
        }

        /* Header With Admin Info */
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            background: transparent;
            position: relative;
            border-bottom: 1px solid var(--gray-200);
            padding: 24px 0 20px 0;
            animation: slideInDown 0.5s ease-out;
        }

        .users-title {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 26px;
            font-weight: 600;
            color: var(--dark);
            transition: all 0.4s var(--transition-bounce);
        }

        .users-title:hover {
            transform: translateY(-4px) scale(1.03);
        }

        .users-title i {
            color: var(--primary);
            font-size: 24px;
            transition: all 0.4s var(--transition-bounce);
        }

        .users-title:hover i {
            transform: rotate(15deg) scale(1.2);
            color: var(--primary-dark);
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 18px;
            background: var(--white);
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow-sm);
            transition: all 0.4s var(--transition-bounce);
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .admin-profile:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 108, 59, 0.15);
            border-color: var(--primary);
        }

        .admin-profile::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0) 0%, rgba(0,108,59,0.05) 50%, rgba(255,255,255,0) 100%);
            transform: translateX(-100%);
            transition: transform 0.8s ease;
        }

        .admin-profile:hover::before {
            transform: translateX(100%);
        }

        .admin-avatar {
            width: 44.8px;
            height: 44.8px;
            border-radius: 50%;
            object-fit: cover;
            background: rgba(0, 108, 59, 0.04);
        }

        .admin-profile:hover .admin-avatar {
            transform: scale(1.15) rotate(10deg);
            border: 2px solid var(--primary);
            box-shadow: 0 5px 15px rgba(0, 108, 59, 0.2);
        }

        .admin-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            transition: all 0.3s ease;
        }

        .admin-profile:hover .admin-info {
            transform: translateX(5px);
        }

        .admin-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
            transition: color 0.3s ease;
        }

        .admin-profile:hover .admin-name {
            color: var(--primary-dark);
        }

        .admin-role {
            font-size: 12px;
            color: var(--primary);
            text-transform: capitalize;
        }

        /* Stats Cards */
        .stats-grid {
            display: flex;
            justify-content: space-between;
            gap: 35px !important;
            margin-bottom: 40px !important;
            animation: slideInUp 0.6s ease forwards;
        }

        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all var(--transition-speed) var(--transition-bounce);
            position: relative;
            border: 1px solid var(--gray-200);
            height: 100%;
            flex: 1;
            transform: translateY(0);
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-light);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .stat-card:hover::after {
            opacity: 1;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 50%);
            transform: translateX(-100%);
            transition: transform 0.6s ease-out;
            pointer-events: none;
        }

        .stat-card:hover::before {
            transform: translateX(100%);
        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            font-size: 20px;
            flex-shrink: 0;
            transition: all 0.4s var(--transition-bounce);
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.15) rotate(10deg);
        }

        .active-users {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .inactive-users {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .admin-users {
            background: rgba(0, 108, 59, 0.1);
            color: var(--primary);
        }

        .pending-documents {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .stat-content {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
            margin-bottom: 6px;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-value {
            color: var(--primary);
            transform: scale(1.1);
        }

        .stat-label {
            font-size: 14px;
            color: var(--gray-600);
            font-weight: 500;
        }

        /* Tabs Navigation */
        .tabs {
            display: flex;
            gap: 25px !important;
            margin-top: 25px !important;
            margin-bottom: 30px !important;
            border-bottom: 1px solid var(--gray-200);
            position: relative;
            animation: fadeIn 0.7s ease forwards;
            padding-bottom: 10px;
        }

        .tab {
            padding: 12px 25px;
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-600);
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s var(--transition-smooth);
            position: relative;
            overflow: hidden;
            margin-right: 15px;
        }

        .tab.active {
            color: var(--primary);
            font-weight: 600;
            border-bottom-color: var(--primary);
            animation: pulse 2s infinite;
        }

        .tab:hover:not(.active) {
            color: var(--primary-dark);
            background: var(--gray-100);
        }

        .tab::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background-color: var(--primary);
            transition: all 0.3s var(--transition-bounce);
            transform: translateX(-50%);
        }

        .tab:hover::before {
            width: 100%;
        }

        .tab.active::before {
            width: 100%;
        }

        .tab::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.4s var(--transition-bounce);
        }

        .tab:hover::after, .tab.active::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .tab:active {
            transform: scale(0.95);
        }

        /* Search and Filter Tools */
        .tools-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            background: var(--white);
            padding: 18px;
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.8s ease forwards;
        }

        .tools-section::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--primary);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .tools-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }

        .tools-section:hover::before {
            opacity: 1;
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 360px;
        }

        .search-input {
            width: 100%;
            padding: 12px 12px 12px 42px;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius-sm);
            font-family: inherit;
            font-size: 14px;
            background: var(--white);
            transition: all 0.3s var(--transition-smooth);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: 16px;
            transition: all 0.2s ease;
        }

        .search-input:focus + .search-icon {
            color: var(--primary);
        }

        .filter-select {
            padding: 12px 36px 12px 16px;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius-sm);
            font-family: inherit;
            font-size: 14px;
            color: var(--gray-700);
            background-color: var(--white);
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23006C3B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            min-width: 160px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,108,59,0.1);
        }

        /* Users Table */
        .users-table {
            background: var(--white);
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 24px;
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            animation: fadeIn 0.9s ease forwards;
        }

        .users-table:hover {
            box-shadow: var(--shadow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        th {
            padding: 16px;
            font-weight: 600;
            text-align: left;
            color: var(--gray-700);
            background: var(--gray-100);
            border-bottom: 1px solid var(--gray-200);
            font-size: 13px;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
            transition: background-color 0.2s ease;
        }

        th:hover {
            background-color: var(--gray-200);
        }

        th i.fas {
            color: var(--gray-500);
            font-size: 12px;
            margin-left: 4px;
            transition: transform 0.2s ease;
        }

        th.sort-asc i.fas {
            transform: rotate(180deg);
            color: var(--primary);
        }

        th.sort-desc i.fas {
            transform: rotate(0deg);
            color: var(--primary);
        }

        th.active-sort {
            color: var(--primary);
            background-color: rgba(0,108,59,0.05);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--gray-200);
            font-size: 14px;
            vertical-align: middle;
        }

        tr {
            transition: all 0.2s ease;
            background: var(--white);
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInUp 0.5s forwards;
            animation-delay: calc(var(--row-index, 0) * 0.05s);
        }

        tr:hover {
            background: rgba(0, 108, 59, 0.04);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            z-index: 10;
            position: relative;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        tr:hover .user-avatar {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .user-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            line-height: 1.2;
        }

        .user-email {
            font-size: 12px;
            color: var(--gray-600);
            line-height: 1.2;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            position: relative;
            gap: 6px;
            white-space: nowrap;
            transition: all 0.3s var(--transition-bounce);
            transform-origin: center;
        }

        tr:hover .status-badge {
            transform: scale(1.08);
        }

        .status-badge::before {
            content: '';
            display: block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .status-active {
            background: rgba(40, 167, 69, 0.1);
            color: #198754;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .status-active::before {
            background: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.2);
        }

        .status-inactive {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .status-inactive::before {
            background: #dc3545;
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s var(--transition-bounce);
            border: none;
            background: transparent;
            color: var(--gray-600);
            font-size: 14px;
            position: relative;
            overflow: hidden;
        }

        .btn-view {
            background: var(--primary-light);
            color: var(--primary);
        }

        .btn-view:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 8px 15px rgba(0, 108, 59, 0.2);
        }

        .btn-deactivate {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .btn-deactivate:hover {
            background: var(--danger);
            color: var(--white);
            transform: translateY(-5px) scale(1.05);
        }

        .btn-activate {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .btn-activate:hover {
            background: var(--success);
            color: var(--white);
            transform: translateY(-5px) scale(1.05);
        }

        .btn-approve {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .btn-approve:hover {
            background: var(--success);
            color: var(--white);
            transform: translateY(-5px) scale(1.05);
        }

        .btn-reject {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .btn-reject:hover {
            background: var(--danger);
            color: var(--white);
            transform: translateY(-5px) scale(1.05);
        }

        .document-preview {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .document-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .document-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            background: rgba(0, 108, 59, 0.1);
            transition: all 0.2s ease;
        }

        .document-link:hover {
            background: var(--primary);
            color: white;
            text-decoration: none;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            opacity: 0;
            transition: backdrop-filter 0.5s ease, opacity 0.4s ease;
            backdrop-filter: blur(0px);
        }

        .modal.show {
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--white);
            margin: 8vh auto;
            width: 90%;
            max-width: 400px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-modal);
            position: relative;
            transform: translateY(40px) scale(0.95);
            opacity: 0;
            transition: transform 0.4s var(--transition-bounce), opacity 0.3s ease;
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        .modal.show .modal-content {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .close {
            position: absolute;
            right: 14px;
            top: 14px;
            font-size: 20px;
            font-weight: bold;
            color: var(--gray-500);
            z-index: 20;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-100);
        }

        .close:hover {
            color: var(--danger);
            transform: rotate(90deg);
            background: var(--gray-200);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .modal-header h3 {
            font-size: 18px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-dark);
        }

        .modal-header h3 i {
            color: var(--primary);
        }

        .modal-body {
            padding: 0;
        }

        .user-profile {
            text-align: center;
            padding: 24px 20px;
            border-bottom: 1px solid var(--gray-200);
            background: linear-gradient(to bottom, rgba(0,108,59,0.03), transparent);
        }

        .user-avatar-lg {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: var(--primary);
            border: 2px solid rgba(0,108,59,0.2);
            box-shadow: 0 4px 12px rgba(0,108,59,0.15);
            transition: all 0.3s ease;
        }

        .user-avatar-lg:hover {
            transform: scale(1.05) rotate(5deg);
        }

        .user-avatar-lg i {
            font-size: 36px;
        }

        .user-profile h4 {
            margin: 0 0 6px;
            font-size: 20px;
            color: var(--dark);
        }

        .user-profile p {
            margin: 0 0 10px;
            font-size: 14px;
            color: var(--gray-600);
        }

        .user-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
            background: var(--white);
        }

        .user-stats .stat-card {
            padding: 15px;
            box-shadow: var(--shadow-sm);
            border-radius: var(--border-radius-sm);
            background: var(--gray-50);
        }

        .user-stats .stat-icon {
            width: 40px;
            height: 40px;
        }

        .user-stats .stat-value {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .user-stats .stat-label {
            font-size: 13px;
        }

        .user-actions {
            padding: 20px;
            background: var(--gray-50);
        }

        .deactivate-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 12px;
            background: white;
            color: #dc2626;
            border: 1px solid #dc2626;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(220, 38, 38, 0.1);
        }

        .deactivate-btn:hover {
            background: #dc2626;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(220, 38, 38, 0.2);
        }

        .deactivate-btn.activate {
            color: #059669;
            border-color: #059669;
            box-shadow: 0 2px 5px rgba(5, 150, 105, 0.1);
        }

        .deactivate-btn.activate:hover {
            background: #059669;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.2);
        }

        .deactivate-btn.pressed {
            transform: scale(0.95);
            transition: transform 0.2s ease;
        }

        /* Table highlight effects */
        tr {
            position: relative;
            overflow: hidden;
        }

        tr::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(0,108,59,0.03), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
            pointer-events: none;
            z-index: 1;
        }

        tr:hover::after {
            transform: translateX(100%);
        }

        /* Improve sort icons */
        th[data-sort] {
            cursor: pointer;
            user-select: none;
        }

        th[data-sort]::after {
            content: '\f0dc';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-left: 5px;
            font-size: 12px;
            color: var(--gray-400);
            transition: all 0.2s ease;
        }

        th[data-sort].sort-asc::after {
            content: '\f0de';
            color: var(--primary);
        }

        th[data-sort].sort-desc::after {
            content: '\f0dd';
            color: var(--primary);
        }

        th[data-sort]:hover::after {
            color: var(--primary);
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                width: 280px; /* Android standard drawer width */
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                max-width: 100%;
                padding: 0 16px; /* Android standard padding */
            }

            .header-container {
                margin: 8px;
                border-radius: 8px;
            }

            .burger-icon {
                width: 40px;
                height: 40px;
                margin-right: 12px;
            }

            .profile-section {
                padding: 8px 12px;
                gap: 8px;
            }

            .admin-avatar {
                width: 40px;
                height: 40px;
            }

            .admin-name {
                font-size: 14px;
            }

            .admin-role {
                font-size: 12px;
            }

            .last-updated {
                font-size: 11px;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px !important;
                margin: 16px 8px !important;
            }
            
            .users-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                padding: 16px 8px;
            }
            
            .tools-section {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                padding: 12px;
                margin: 8px;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .filter-select {
                width: 100%;
                height: 40px; /* Android standard height */
            }
            
            .stat-card {
                padding: 12px;
                min-height: 100px;
            }

            .stat-value {
                font-size: 24px;
            }

            .stat-label {
                font-size: 12px;
            }
            
            th, td {
                padding: 12px 8px;
                font-size: 13px;
            }

            .user-info {
                gap: 8px;
            }

            .user-avatar {
                width: 36px;
                height: 36px;
            }

            .user-name {
                font-size: 13px;
            }

            .user-email {
                font-size: 11px;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 0;
            }
            
            .sidebar.show {
                width: 280px; /* Android standard drawer width */
            }
            
            .main-content {
                padding: 0 8px;
            }
            
            .header-container {
                margin: 8px 4px;
                padding: 12px 8px;
            }

            .title-text h1 {
                font-size: 18px;
            }

            .title-text h2 {
                font-size: 14px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 8px !important;
                margin: 12px 4px !important;
            }

            .tools-section {
                margin: 8px 4px;
                padding: 12px 8px;
            }

            .search-input {
                height: 40px; /* Android standard height */
                font-size: 14px;
            }

            .action-buttons {
                gap: 4px;
            }
            
            .action-btn {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }

            .status-badge {
                padding: 4px 8px;
                font-size: 11px;
            }

            .modal-content {
                width: 95%;
                margin: 16px auto;
            }
        }

        /* Overlay styles for burger menu */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 998;
        }

        .sidebar-overlay.show {
            display: block;
        }

        body.sidebar-open {
            overflow: hidden;
        }

        /* Enhanced burger icon */
        .burger-icon {
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 999;
            background: #e8f8f1;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        @media (max-width: 992px) {
            .burger-icon {
                display: flex;
            }
        }

        .burger-icon:active {
            background: #d1f2e4;
            transform: scale(0.95);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fadeIn {
            animation: fadeIn 0.3s ease forwards;
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }

        .fadeOut {
            animation: fadeOut 0.3s ease forwards;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .stat-card:nth-child(1) {
            animation: slideInRight 0.3s ease forwards;
            animation-delay: 0.1s;
        }

        .stat-card:nth-child(2) {
            animation: slideInRight 0.3s ease forwards;
            animation-delay: 0.2s;
        }

        .stat-card:nth-child(3) {
            animation: slideInRight 0.3s ease forwards;
            animation-delay: 0.3s;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .tabs {
            animation: fadeIn 0.5s ease forwards;
        }

        .tab.active {
            animation: pulse 2s infinite;
        }

        .modal-content {
            animation: fadeIn 0.4s ease-out forwards;
        }

        /* Fix for the sidebar mobile toggle */
        #menu-toggle {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1100;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            width: 36px;
            height: 36px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow);
        }

        @media (max-width: 576px) {
            #menu-toggle {
                display: flex;
            }
        }

        /* Add a smooth page loader */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .page-loader.hidden {
                opacity: 0; 
            visibility: hidden;
        }

        .loader {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 4px solid var(--gray-200);
            border-top-color: var(--primary);
            animation: spinner 1s infinite linear;
        }

        .loader-logo {
            position: absolute;
            width: 30px;
            height: 30px;
            animation: pulse 1.5s infinite ease-in-out;
        }

        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(0.8);
                opacity: 0.8;
            }
            50% {
                transform: scale(1); 
                opacity: 1;
            }
        }

        /* Enhanced filter transitions */
        .filter-select {
            transition: all 0.3s var(--transition-smooth);
        }

        .filter-select:hover {
            border-color: var(--primary-light);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .filter-select:focus {
            transform: translateY(-2px);
        }

        /* Add fluid table transitions */
        .users-table {
            transition: all 0.4s ease;
        }

        .users-table:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
            transform: translateY(-5px);
        }

        /* Enhance user info transitions */
        .user-info {
            transition: all 0.3s ease;
            padding: 5px;
            border-radius: 8px;
        }

        tr:hover .user-info {
            background-color: rgba(0, 108, 59, 0.03);
            transform: translateX(5px);
        }

        /* Add animation to status toggle */
        .status-badge {
            position: relative;
            overflow: hidden;
        }

        .status-badge::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.5) 50%, rgba(255,255,255,0) 100%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        tr:hover .status-badge::after {
            transform: translateX(100%);
        }

        /* More pronounced hover state for action buttons */
        .action-buttons {
            transition: all 0.3s ease;
        }

        tr:hover .action-buttons {
            transform: scale(1.05);
        }

        /* Enhance modal user profile */
        .user-avatar-lg {
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .user-avatar-lg:hover {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 10px 25px rgba(0, 108, 59, 0.2);
        }

        .user-profile h4 {
            transition: all 0.3s ease;
        }

        .user-profile h4:hover {
            transform: translateY(-2px);
            color: var(--primary-dark);
        }

        /* Enhance the deactivate button */
        .deactivate-btn {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .deactivate-btn::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
        }

        .deactivate-btn:hover::after {
            transform: translateX(100%);
            transition: transform 0.6s ease;
        }

        /* Add table row focus styling */
        tr:focus-within {
            background-color: rgba(0, 108, 59, 0.05);
            outline: none;
        }

        .burger-icon {
            width: 48px;
            height: 48px;
            background: #e8f8f1;
            border-radius: 12px;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-right: 16px;
        }

        .burger-icon i {
            color: var(--primary);
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .burger-icon:hover {
            background: #d1f2e4;
            transform: translateY(-2px);
        }

        .burger-icon:hover i {
            transform: scale(1.1);
        }

        @media (max-width: 992px) {
            .burger-icon {
                display: flex;
            }

            .main-content {
                margin-left: 0;
                max-width: 100%;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                z-index: 99;
            }

            .overlay.show {
                opacity: 1;
                visibility: visible;
            }
        }

        .header-container {
            background: var(--white);
            padding: 2rem;
            border-radius: 20px;
            margin: 1.5rem 2rem 1rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .burger-icon {
            width: 48px;
            height: 48px;
            background: #e8f8f1;
            border-radius: 12px;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .burger-icon i {
            color: var(--primary);
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .burger-icon:hover {
            background: #d1f2e4;
            transform: translateY(-2px);
        }

        .burger-icon:hover i {
            transform: scale(1.1);
        }

        .title-text {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .title-text h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .title-text h2 {
            font-size: 0.875rem;
            color: var(--gray-600);
            font-weight: 400;
            margin: 0;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1rem 1.5rem;
            background: rgba(0, 108, 59, 0.04);
            border-radius: 16px;
        }

        .admin-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.25rem;
        }

        .admin-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .admin-role {
            font-size: 0.875rem;
            color: var(--primary);
        }

        .last-updated {
            font-size: 0.75rem;
            color: var(--gray-600);
        }

        .admin-avatar {
            width: 44.8px;
            height: 44.8px;
            border-radius: 50%;
            object-fit: cover;
            background: rgba(0, 108, 59, 0.04);
        }

        @media (max-width: 992px) {
            .burger-icon {
                display: flex;
            }

            .main-content {
                margin-left: 0;
                max-width: 100%;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }

        @media (max-width: 768px) {
            .header-container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .profile-section {
                padding: 0.75rem 1rem;
            }

            .admin-name {
                font-size: 0.875rem;
            }

            .admin-role {
                font-size: 0.75rem;
            }
        }

        /* Profile section styles */
        .profile-section {
            background: rgba(0, 108, 59, 0.04);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .profile-section:hover {
            background: rgba(0, 108, 59, 0.08);
        }

        .notification-bell {
            position: relative;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(0, 108, 59, 0.1), rgba(0, 108, 59, 0.05));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 2px solid transparent;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .notification-bell:hover {
            background: linear-gradient(45deg, rgba(0, 108, 59, 0.15), rgba(0, 108, 59, 0.1));
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-color: rgba(0, 108, 59, 0.1);
        }

        .notification-bell i {
            color: #006C3B;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .notification-bell:hover i {
            transform: scale(1.1);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(255, 68, 68, 0.3);
        }

        .profile-image {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .profile-section:hover .profile-image {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .text-end {
            text-align: right;
        }

        .fw-bold {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }

        .text-success {
            color: #006C3B !important;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .text-muted {
            color: #6c757d;
        }

        .small {
            font-size: 0.75rem;
        }

        .me-3 {
            margin-right: 1rem;
        }

        .p-3 {
            padding: 1rem;
        }

        .d-flex {
            display: flex;
        }

        .align-items-center {
            align-items: center;
        }

        .gap-3 {
            gap: 1rem;
        }

        /* Enhanced Notification Styles */
        .notification-bell {
            position: relative;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            background: rgba(0, 108, 59, 0.1);
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
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }

        /* Dropdown Menu Styles */
        .dropdown-menu {
            z-index: 1050;
            animation: fadeInUp 0.2s ease-out;
        }

        .notification-list::-webkit-scrollbar {
            width: 6px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .notification-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .list-group-item-action {
            transition: all 0.2s ease;
        }

        .list-group-item-action:hover {
            background-color: rgba(0, 108, 59, 0.05);
        }

        .list-group-item-action:active {
            background-color: rgba(0, 108, 59, 0.1);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .dropdown-menu {
                position: fixed !important;
                top: 70px !important;
                left: 1rem !important;
                right: 1rem !important;
                width: auto !important;
                transform: none !important;
                margin-top: 0 !important;
            }
            
            .notification-list {
                max-height: calc(100vh - 200px) !important;
            }
        }

        .btn-notification {
            background: none !important;
            border: none !important;
            padding: 0 !important;
            box-shadow: none !important;
            outline: none !important;
        }

        .btn-notification:focus {
            box-shadow: none !important;
            outline: none !important;
        }

        /* Add these styles at the top of the file */
        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: #e8f5f0;
            border-radius: 12px;
            padding: 20px;
            width: 300px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 1050;
            margin-top: 10px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .notification-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown {
            position: relative;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .notification-header h6 {
            font-size: 16px;
            color: #2d3436;
            margin: 0;
        }

        .notification-count {
            font-size: 14px;
            color: #006C3B;
        }

        .notification-content {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }

        .no-notifications {
            text-align: center;
            color: #666;
        }

        .notification-footer {
            margin-top: 15px;
        }

        .view-all-link {
            color: #006C3B;
            text-decoration: none;
            font-size: 14px;
            display: block;
            text-align: center;
        }
        .dropdown-menu[data-popper-placement="bottom-end"] {
    transform: translate3d(0, 0, 0) !important;
    top: 100% !important;
}
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="header-container">
            <div class="header-content">
                <div class="page-title">
                    <div class="burger-icon" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </div>
                    <div class="title-text">
                        <h1>Manage Users</h1>
                        <h2>Overview</h2>
                    </div>
                </div>
                <div class="profile-section p-3 d-flex align-items-center gap-3">
                    <?php include 'includes/notification_dropdown.php'; ?>
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
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon active-users">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['active_customers']; ?></div>
                    <div class="stat-label">Active Customers</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon inactive-users">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['inactive_customers']; ?></div>
                    <div class="stat-label">Inactive Customers</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon admin-users">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['total_admins']; ?></div>
                    <div class="stat-label">Total Admins</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending-documents">
                    <i class="fas fa-file-upload"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['pending_documents']; ?></div>
                    <div class="stat-label">Pending Documents</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" data-tab="customers">Customers</button>
            <button class="tab" data-tab="administrators">Administrators</button>
            <button class="tab" data-tab="documents">Document Reviews</button>
        </div>

        <!-- Search and Filter Tools -->
        <div class="tools-section">
            <div class="search-box">
                <input type="text" id="searchInput" class="search-input" placeholder="Search users...">
                <i class="fas fa-search search-icon"></i>
            </div>
                <select id="statusFilter" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
        </div>

        <!-- Customers Table -->
        <div id="customerTable" class="users-table">
            <table>
                <thead>
                    <tr>
                        <th data-sort="name">User <i class="fas fa-sort"></i></th>
                        <th data-sort="status">Status <i class="fas fa-sort"></i></th>
                        <th data-sort="orders">Orders <i class="fas fa-sort"></i></th>
                        <th data-sort="spent">Total Spent <i class="fas fa-sort"></i></th>
                        <th data-sort="created">Joined <i class="fas fa-sort"></i></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($customer_result && mysqli_num_rows($customer_result) > 0) {
                        while ($row = mysqli_fetch_assoc($customer_result)) {
                            // Format order count and total spent
                            $order_count = isset($row['order_count']) ? $row['order_count'] : 0;
                            $total_spent = isset($row['total_spent']) ? '₱' . number_format($row['total_spent'], 2) : '₱0.00';
                            
                            // Set user name based on the column we determined earlier
                            $display_name = $row['display_name'] ?? 'User';
                            $name_data = htmlspecialchars(strtolower($display_name));
                            
                            // Format date for display
                            $created_date = date('M d, Y', strtotime($row['created_at']));
                            $created_timestamp = strtotime($row['created_at']);

                            // Set status - convert from numeric to text
                            $status = ($row['status'] == 1 || $row['status'] == 'active') ? 'active' : 'inactive';
                            $status_class = $status == 'active' ? 'status-active' : 'status-inactive';
                            $status_text = ucfirst($status);
                        ?>
                            <tr data-id="<?php echo $row['id']; ?>" 
                                data-name="<?php echo $name_data; ?>" 
                                data-email="<?php echo htmlspecialchars(strtolower($row['email'])); ?>" 
                                data-phone="<?php echo htmlspecialchars($row['phone'] ?? ''); ?>" 
                                data-status="<?php echo $status; ?>" 
                                data-orders="<?php echo $order_count; ?>" 
                                data-spent="<?php echo $row['total_spent'] ?? 0; ?>" 
                                data-created="<?php echo $created_timestamp; ?>">
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name"><?php echo htmlspecialchars($display_name); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($row['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                <td><?php echo $order_count; ?></td>
                                <td><?php echo $total_spent; ?></td>
                                <td><?php echo $created_date; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn btn-view" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($display_name); ?>"
                                                data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                                data-phone="<?php echo htmlspecialchars($row['phone'] ?? ''); ?>"
                                                data-status="<?php echo $status; ?>"
                                                data-orders="<?php echo $order_count; ?>"
                                                data-spent="<?php echo $row['total_spent'] ?? 0; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($status == 'active'): ?>
                                        <button class="action-btn btn-deactivate" data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-user-times"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="action-btn btn-activate" data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-user-check"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center">No customers found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Admins Table -->
        <div id="adminTable" class="users-table" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th data-sort="name">Administrator <i class="fas fa-sort"></i></th>
                        <th data-sort="status">Status <i class="fas fa-sort"></i></th>
                        <th data-sort="created">Joined <i class="fas fa-sort"></i></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($admin_result && mysqli_num_rows($admin_result) > 0) {
                        while ($row = mysqli_fetch_assoc($admin_result)) {
                            // Set user name based on the column we determined earlier
                            $display_name = $row['display_name'] ?? 'Admin';
                            $name_data = htmlspecialchars(strtolower($display_name));
                            
                            // Format date for display
                            $created_date = date('M d, Y', strtotime($row['created_at']));
                            $created_timestamp = strtotime($row['created_at']);
                            
                            // Set status - convert from numeric to text
                            $status = ($row['status'] == 1 || $row['status'] == 'active') ? 'active' : 'inactive';
                            $status_class = $status == 'active' ? 'status-active' : 'status-inactive';
                            $status_text = ucfirst($status);
                            
                            // Check if this is the current user
                            $is_current_user = ($_SESSION['user_id'] == $row['id']);
                        ?>
                            <tr data-id="<?php echo $row['id']; ?>" 
                                data-name="<?php echo $name_data; ?>" 
                                data-email="<?php echo htmlspecialchars(strtolower($row['email'])); ?>" 
                                data-phone="<?php echo htmlspecialchars($row['phone'] ?? ''); ?>" 
                                data-status="<?php echo $status; ?>" 
                                data-created="<?php echo $created_timestamp; ?>">
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="user-details">
                                            <div class="user-name"><?php echo htmlspecialchars($display_name); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($row['email']); ?></div>
                                </div>
                            </div>
                        </td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                <td><?php echo $created_date; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn btn-view" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($display_name); ?>"
                                                data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                                data-phone="<?php echo htmlspecialchars($row['phone'] ?? ''); ?>"
                                                data-status="<?php echo $status; ?>"
                                                data-orders="0"
                                                data-spent="0">
                                            <i class="fas fa-eye"></i>
                            </button>
                                        <?php if (!$is_current_user && $status == 'active'): ?>
                                        <button class="action-btn btn-deactivate" data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-user-times"></i>
                                        </button>
                                        <?php elseif (!$is_current_user && $status == 'inactive'): ?>
                                        <button class="action-btn btn-activate" data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-user-check"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                        </td>
                    </tr>
                        <?php
                        }
                    } else {
                        echo '<tr><td colspan="4" class="text-center">No administrators found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Document Review Table -->
        <div id="documentTable" class="users-table" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th data-sort="name">User <i class="fas fa-sort"></i></th>
                        <th data-sort="submitted">Submitted <i class="fas fa-sort"></i></th>
                        <th>Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($pending_docs_result && mysqli_num_rows($pending_docs_result) > 0) {
                        while ($row = mysqli_fetch_assoc($pending_docs_result)) {
                            $submitted_date = date('M d, Y H:i', strtotime($row['documents_submitted_at']));
                            $submitted_timestamp = strtotime($row['documents_submitted_at']);
                            $id_type_labels = [
                                'driver_license' => 'Driver\'s License',
                                'passport' => 'Passport',
                                'student_id' => 'Student ID',
                                'national_id' => 'National ID',
                                'other' => 'Other Government ID'
                            ];
                            $id_type_label = $id_type_labels[$row['valid_id_type']] ?? 'Unknown';
                        ?>
                            <tr data-id="<?php echo $row['id']; ?>" 
                                data-name="<?php echo htmlspecialchars(strtolower($row['full_name'])); ?>" 
                                data-submitted="<?php echo $submitted_timestamp; ?>">
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($row['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $submitted_date; ?></td>
                                <td>
                                    <div class="document-preview">
                                        <div class="document-item">
                                            <strong>1x1 Photo:</strong> 
                                            <a href="../uploads/documents/1x1_photos/<?php echo $row['photo_1x1']; ?>" target="_blank" class="document-link">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                        <div class="document-item">
                                            <strong>2x2 Photo:</strong> 
                                            <a href="../uploads/documents/2x2_photos/<?php echo $row['photo_2x2']; ?>" target="_blank" class="document-link">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                        <div class="document-item">
                                            <strong><?php echo $id_type_label; ?>:</strong> 
                                            <a href="../uploads/documents/valid_ids/<?php echo $row['valid_id_image']; ?>" target="_blank" class="document-link">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn btn-approve" data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="action-btn btn-reject" data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        }
                    } else {
                        echo '<tr><td colspan="4" class="text-center">No pending document submissions</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Detail Modal -->
    <div id="userDetailModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h3><i class="fas fa-user-circle"></i> User Details</h3>
            </div>
            <div class="modal-body">
                <div class="user-profile">
                    <div class="user-avatar-lg">
                        <i class="fas fa-user"></i>
                    </div>
                    <h4 id="modalUserName">John Doe</h4>
                    <p id="modalUserEmail">johndoe@example.com</p>
                    <p id="modalUserPhone">(123) 456-7890</p>
                    <span id="modalUserStatus" class="status-badge status-active">Active</span>
                </div>
                
                <div class="user-stats">
                    <div class="stat-card">
                        <div class="stat-value" id="modalOrderCount">0</div>
                        <div class="stat-label">Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="modalTotalSpent">₱0</div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                </div>
                
                <div class="user-actions">
                    <button id="modalStatusButton" class="deactivate-btn">
                        <i class="fas fa-user-slash"></i>
                        Deactivate User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add loader element to the page -->
    <div class="page-loader">
        <div class="loader"></div>
        <img src="../assets/images/logo.png" alt="Loading" class="loader-logo">
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal Elements
            const modal = document.getElementById('userDetailModal');
            const closeBtn = document.querySelector('.close');
            const modalUserName = document.getElementById('modalUserName');
            const modalUserEmail = document.getElementById('modalUserEmail');
            const modalUserPhone = document.getElementById('modalUserPhone');
            const modalUserStatus = document.getElementById('modalUserStatus');
            const modalOrderCount = document.getElementById('modalOrderCount');
            const modalTotalSpent = document.getElementById('modalTotalSpent');
            const modalStatusButton = document.getElementById('modalStatusButton');
            
            // Tab Elements
            const customerTab = document.querySelector('.tab[data-tab="customers"]');
            const adminTab = document.querySelector('.tab[data-tab="administrators"]');
            const documentTab = document.querySelector('.tab[data-tab="documents"]');
            const customerTable = document.getElementById('customerTable');
            const adminTable = document.getElementById('adminTable');
            const documentTable = document.getElementById('documentTable');
            
            // Search and Filter Elements
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            
            // Open user detail modal
            document.querySelectorAll('.btn-view').forEach(btn => {
                btn.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const userName = this.getAttribute('data-name');
                    const userEmail = this.getAttribute('data-email');
                    const userPhone = this.getAttribute('data-phone');
                    const userStatus = this.getAttribute('data-status');
                    const userOrders = this.getAttribute('data-orders');
                    const userSpent = this.getAttribute('data-spent');
                    
                    modalUserName.textContent = userName;
                    modalUserEmail.textContent = userEmail;
                    modalUserPhone.textContent = userPhone || 'No phone number';
                    
                    // Update status badge
                    modalUserStatus.className = 'status-badge';
                    if (userStatus === 'active') {
                        modalUserStatus.classList.add('status-active');
                        modalUserStatus.textContent = 'Active';
                        modalStatusButton.classList.remove('activate');
                        modalStatusButton.classList.add('deactivate');
                        modalStatusButton.innerHTML = '<i class="fas fa-user-slash"></i> Deactivate User';
                    } else {
                        modalUserStatus.classList.add('status-inactive');
                        modalUserStatus.textContent = 'Inactive';
                        modalStatusButton.classList.remove('deactivate');
                        modalStatusButton.classList.add('activate');
                        modalStatusButton.innerHTML = '<i class="fas fa-user-check"></i> Activate User';
                    }
                    
                    // Update order count and total spent
                    modalOrderCount.textContent = userOrders || '0';
                    modalTotalSpent.textContent = '₱' + (userSpent || '0');
                    
                    // Set button data
                    modalStatusButton.setAttribute('data-id', userId);
                    modalStatusButton.setAttribute('data-status', userStatus);
                    
                    // Show modal with animation
                    modal.style.display = 'block';
                    setTimeout(() => {
                        modal.classList.add('show');
                    }, 10);
                });
            });
            
            // Close modal
            closeBtn.addEventListener('click', function() {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            });
            
            // Click outside to close
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.classList.remove('show');
                    setTimeout(() => {
                        modal.style.display = 'none';
                    }, 300);
                }
            });
            
            // Toggle user status
            modalStatusButton.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const currentStatus = this.getAttribute('data-status');
                const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
                
                // Add pressed effect
                this.classList.add('pressed');
                setTimeout(() => {
                    this.classList.remove('pressed');
                }, 200);
                
                // Update button appearance
                if (newStatus === 'active') {
                    this.classList.remove('activate');
                    this.innerHTML = '<i class="fas fa-user-slash"></i> Deactivate User';
                } else {
                    this.classList.add('activate');
                    this.innerHTML = '<i class="fas fa-user-check"></i> Activate User';
                }
                
                // Call API to update status
                fetch('users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `update_status=1&user_id=${userId}&status=${newStatus === 'active' ? 1 : 0}`
                })
                .then(response => {
                    // Handle non-JSON responses 
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text().then(text => {
                        try {
                            return text ? JSON.parse(text) : {};
                        } catch (e) {
                            return { success: true }; // Assume success if response is not JSON
                        }
                    });
                })
                .then(data => {
                    // Update the button's data attribute
                    this.setAttribute('data-status', newStatus);
                    
                    // Update the table row status
                    const tableRow = document.querySelector(`tr[data-id="${userId}"]`);
                    if (tableRow) {
                        const statusBadge = tableRow.querySelector('.status-badge');
                        const statusBtn = tableRow.querySelector('.btn-activate, .btn-deactivate');
                        
                        if (statusBadge) {
                            statusBadge.className = 'status-badge';
                            statusBadge.classList.add(newStatus === 'active' ? 'status-active' : 'status-inactive');
                            statusBadge.textContent = newStatus === 'active' ? 'Active' : 'Inactive';
                        }
                        
                        if (statusBtn) {
                            if (newStatus === 'active') {
                                statusBtn.className = 'action-btn btn-deactivate';
                                statusBtn.innerHTML = '<i class="fas fa-user-times"></i>';
                                statusBtn.title = 'Deactivate';
                            } else {
                                statusBtn.className = 'action-btn btn-activate';
                                statusBtn.innerHTML = '<i class="fas fa-user-check"></i>';
                                statusBtn.title = 'Activate';
                            }
                        }
                        
                        // Update the row's data attribute
                        tableRow.setAttribute('data-status', newStatus);
                    }
                    
                    // Update dashboard stats
                    const activeCustomersElement = document.querySelector('.stats-grid .stat-card:nth-child(1) .stat-value');
                    const inactiveCustomersElement = document.querySelector('.stats-grid .stat-card:nth-child(2) .stat-value');
                    
                    if (activeCustomersElement && inactiveCustomersElement) {
                        let activeCount = parseInt(activeCustomersElement.textContent || '0');
                        let inactiveCount = parseInt(inactiveCustomersElement.textContent || '0');
                        
                        if (newStatus === 'active' && currentStatus === 'inactive') {
                            activeCount++;
                            inactiveCount = Math.max(0, inactiveCount - 1);
                        } else if (newStatus === 'inactive' && currentStatus === 'active') {
                            inactiveCount++;
                            activeCount = Math.max(0, activeCount - 1);
                        }
                        
                        activeCustomersElement.textContent = activeCount;
                        inactiveCustomersElement.textContent = inactiveCount;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the user status.');
                });
            });
            
            // Tab switching
            customerTab.addEventListener('click', function() {
                customerTab.classList.add('active');
                adminTab.classList.remove('active');
                customerTable.style.display = 'block';
                adminTable.style.display = 'none';
            });
            
            adminTab.addEventListener('click', function() {
                adminTab.classList.add('active');
                customerTab.classList.remove('active');
                documentTab.classList.remove('active');
                adminTable.style.display = 'block';
                customerTable.style.display = 'none';
                documentTable.style.display = 'none';
            });
            
            documentTab.addEventListener('click', function() {
                documentTab.classList.add('active');
                customerTab.classList.remove('active');
                adminTab.classList.remove('active');
                documentTable.style.display = 'block';
                customerTable.style.display = 'none';
                adminTable.style.display = 'none';
            });
            
            // Search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                filterUsers(searchTerm, statusFilter.value);
            });
            
            // Status filter
            statusFilter.addEventListener('change', function() {
                filterUsers(searchInput.value.toLowerCase(), this.value);
            });
            
            // Function to filter users
            function filterUsers(searchTerm, statusFilter) {
                const tables = [customerTable, adminTable];
                
                tables.forEach(table => {
                    if (table) {
                        const rows = table.querySelectorAll('tbody tr');
                        
                        rows.forEach(row => {
                            const userName = row.getAttribute('data-name')?.toLowerCase() || '';
                            const userEmail = row.getAttribute('data-email')?.toLowerCase() || '';
                            const userStatus = row.getAttribute('data-status')?.toLowerCase() || '';
                            
                            const matchesSearch = userName.includes(searchTerm) || userEmail.includes(searchTerm);
                            const matchesStatus = statusFilter === '' || userStatus === statusFilter;
                            
                            if (matchesSearch && matchesStatus) {
                                row.style.display = '';
                                row.classList.add('fadeIn');
                                setTimeout(() => {
                                    row.classList.remove('fadeIn');
                                }, 500);
                            } else {
                                row.classList.add('fadeOut');
                                setTimeout(() => {
                                    row.style.display = 'none';
                                    row.classList.remove('fadeOut');
                                }, 300);
                            }
                        });
                    }
                });
            }
            
            // Sort functionality for table headers
            document.querySelectorAll('th[data-sort]').forEach(header => {
                header.addEventListener('click', function() {
                    const sortKey = this.getAttribute('data-sort');
                    const table = this.closest('table');
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const isAsc = !this.classList.contains('sort-asc');
                    
                    // Update header state
                    document.querySelectorAll('th').forEach(th => {
                        th.classList.remove('sort-asc', 'sort-desc', 'active-sort');
                    });
                    
                    this.classList.add('active-sort');
                    this.classList.add(isAsc ? 'sort-asc' : 'sort-desc');
                    
                    // Sort rows
                    rows.sort((a, b) => {
                        let aValue = a.getAttribute(`data-${sortKey}`) || '';
                        let bValue = b.getAttribute(`data-${sortKey}`) || '';
                        
                        // Handle numeric values
                        if (sortKey === 'orders' || sortKey === 'spent') {
                            aValue = parseFloat(aValue) || 0;
                            bValue = parseFloat(bValue) || 0;
                        }
                        
                        if (typeof aValue === 'string') {
                            return isAsc 
                                ? aValue.localeCompare(bValue) 
                                : bValue.localeCompare(aValue);
                        } else {
                            return isAsc 
                                ? aValue - bValue 
                                : bValue - aValue;
                        }
                    });
                    
                    // Reorder rows with animation
                    rows.forEach((row, index) => {
                        row.style.opacity = '0';
                        row.style.transform = 'translateY(-10px)';
                        
                        setTimeout(() => {
                            tbody.appendChild(row);
                            row.style.opacity = '1';
                            row.style.transform = 'translateY(0)';
                        }, index * 50);
                    });
                });
            });
            
            // Initialize animations for table rows
            function initRowAnimations() {
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach((row, index) => {
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.style.opacity = '1';
                        row.classList.add('fadeIn');
                        setTimeout(() => {
                            row.classList.remove('fadeIn');
                        }, 500);
                    }, index * 100);
                });
            }
            
            initRowAnimations();
            
            // Document approval/rejection functionality
            document.querySelectorAll('.btn-approve').forEach(btn => {
                btn.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    approveDocuments(userId, 'approve');
                });
            });
            
            document.querySelectorAll('.btn-reject').forEach(btn => {
                btn.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const reason = prompt('Please provide a reason for rejection:');
                    if (reason && reason.trim()) {
                        approveDocuments(userId, 'reject', reason.trim());
                    }
                });
            });
            
            function approveDocuments(userId, action, rejectionReason = '') {
                const formData = new FormData();
                formData.append('approve_documents', '1');
                formData.append('user_id', userId);
                formData.append('action', action);
                if (rejectionReason) {
                    formData.append('rejection_reason', rejectionReason);
                }
                
                fetch('users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row from the table
                        const row = document.querySelector(`tr[data-id="${userId}"]`);
                        if (row) {
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(-100%)';
                            setTimeout(() => {
                                row.remove();
                            }, 300);
                        }
                        
                        // Update pending documents count
                        const pendingCountElement = document.querySelector('.stat-card:nth-child(4) .stat-value');
                        if (pendingCountElement) {
                            let currentCount = parseInt(pendingCountElement.textContent || '0');
                            pendingCountElement.textContent = Math.max(0, currentCount - 1);
                        }
                        
                        // Show success message
                        showNotification(data.message, 'success');
                    } else {
                        showNotification(data.message || 'An error occurred', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while processing the request', 'error');
                });
            }
            
            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'}`;
                notification.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <div>${message}</div>
                `;
                
                document.querySelector('.main-content').insertBefore(notification, document.querySelector('.stats-grid'));
                
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }
        });

        // Page loader
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.querySelector('.page-loader').classList.add('hidden');
            }, 500);
        });
        
        // Enhanced sorting animation
        document.querySelectorAll('th[data-sort]').forEach(header => {
            header.addEventListener('click', function() {
                // Add clicking animation
                this.style.transform = 'scale(0.97)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
                
                // Add table refresh animation
                const table = this.closest('table');
                const tbody = table.querySelector('tbody');
                
                tbody.style.opacity = '0.5';
                tbody.style.transform = 'translateY(10px)';
                
                setTimeout(() => {
                    tbody.style.opacity = '1';
                    tbody.style.transform = 'translateY(0)';
                    tbody.style.transition = 'all 0.4s ease';
                }, 300);
            });
        });
        
        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        
        // Create overlay element if it doesn't exist
        let overlay = document.querySelector('.overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'overlay';
            document.body.appendChild(overlay);
        }

        function toggleSidebar() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleSidebar();
            });
        }

        // Close sidebar when clicking overlay
        overlay.addEventListener('click', toggleSidebar);

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target) && 
                sidebar.classList.contains('show')) {
                toggleSidebar();
            }
        });

        // Prevent sidebar from closing when clicking inside it
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        });

        function toggleNotifications() {
            const menu = document.getElementById('notificationMenu');
            menu.classList.toggle('show');
            
            // Close when clicking outside
            if (menu.classList.contains('show')) {
                setTimeout(() => {
                    document.addEventListener('click', function closeMenu(e) {
                        const dropdown = document.querySelector('.dropdown');
                        const button = document.getElementById('notificationDropdown');
                        if (!dropdown.contains(e.target) && e.target !== button) {
                            menu.classList.remove('show');
                            document.removeEventListener('click', closeMenu);
                        }
                    });
                }, 0);
            }
        }
    </script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/notifications.js"></script>
</body>
</html> 