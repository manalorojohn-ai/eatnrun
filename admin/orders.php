<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once __DIR__ . '/includes/notifications_handler.php';

// Create notifications table if it doesn't exist
create_notifications_table($conn);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Create admins table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS `admins` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `profile_image` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($conn, $create_table_sql)) {
    die("Error creating admins table: " . mysqli_error($conn));
}

// Create order_logs table if it doesn't exist
$create_order_logs_sql = "CREATE TABLE IF NOT EXISTS `order_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `action` varchar(50) NOT NULL,
    `details` text NOT NULL,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($conn, $create_order_logs_sql)) {
    die("Error creating order_logs table: " . mysqli_error($conn));
}

// Insert default admin if not exists
$insert_admin_sql = "INSERT IGNORE INTO `admins` (`name`, `email`, `password`) 
                    VALUES ('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')";
mysqli_query($conn, $insert_admin_sql);

// Initialize filter variables
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query with filters
$query = "SELECT o.*, 
          u.email as user_email, 
          COALESCE(o.full_name, u.full_name) as display_name,
          COALESCE(o.phone, u.phone) as display_phone,
          COALESCE(NULLIF(o.email, ''), u.email, 'N/A') as display_email 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          WHERE 1=1";

// Apply status filter
if (!empty($status_filter)) {
    $query .= " AND LOWER(o.status) = LOWER('" . mysqli_real_escape_string($conn, $status_filter) . "')";
}

// Apply date filter
if (!empty($date_filter)) {
    $query .= " AND DATE(o.created_at) = '" . mysqli_real_escape_string($conn, $date_filter) . "'";
}

// Add order by clause
$query .= " ORDER BY o.id DESC, o.created_at DESC";

// Set the limit to a high number to show all orders
$query .= " LIMIT 1000";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Fetch admin information from users table
$admin_query = "SELECT * FROM users WHERE id = ? AND role = 'admin'";
$stmt = mysqli_prepare($conn, $admin_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$admin_result = mysqli_stmt_get_result($stmt);
$admin_data = mysqli_fetch_assoc($admin_result);

// If no data found, set default values
if (!$admin_data) {
    $admin_data = [
        'full_name' => 'Administrator',
        'profile_image' => null
    ];
}

// Get unread notifications count
$unread_messages = get_admin_unread_count($conn, $_SESSION['user_id']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: rgba(0, 108, 59, 0.1);
            --white: #fff;
            --text-dark: #333;
            --text-light: #666;
            --border-radius: 12px;
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-hover: 0 4px 8px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        .main-content {
            margin-left: 240px;
            padding: 0;
            transition: var(--transition);
            min-height: 100vh;
        }

        .header-container {
            background: var(--white);
            padding: 2rem;
            border-radius: 20px;
            margin: 1.5rem 2rem 1rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
        }

        .header-container:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        .profile-section {
            position: relative;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            background: linear-gradient(45deg, var(--primary-light), rgba(0, 108, 59, 0.05));
            transition: var(--transition);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-section:hover {
            background: linear-gradient(45deg, rgba(0, 108, 59, 0.15), rgba(0, 108, 59, 0.05));
        }

        .profile-info {
            text-align: right;
        }

        .profile-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .profile-role {
            color: #27ae60;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .profile-image {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--white);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
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
            border: none;
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
            box-shadow: 0 2px 4px rgba(255, 68, 68, 0.3);
        }

        .order-card {
                background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending { background-color: #FEF3C7; color: #92400E; }
        .status-processing { background-color: #DBEAFE; color: #1E40AF; }
        .status-completed { background-color: #D1FAE5; color: #065F46; }
        .status-cancelled { background-color: #FEE2E2; color: #991B1B; }

        .filter-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.2);
        }

        .form-select, .form-control {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 0.625rem 1rem;
            transition: var(--transition);
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 108, 59, 0.1);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            
            .header-container {
                margin: 1rem;
            }
            
            .filter-section {
                margin: 1rem;
            }
            
            .order-grid {
                padding: 0 1rem;
            }
        }

        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1050;
        }

        .toast {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            margin-bottom: 1rem;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast-header {
            background: none;
            border: none;
            padding: 1rem;
            display: flex;
            align-items: center;
        }

        .toast-body {
            padding: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Dropdown Menu Styles */
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            padding: 0.5rem;
        }

        .dropdown-item {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background-color: var(--primary-light);
            color: var(--primary);
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-in {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Mobile Responsive Styles */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .profile-section {
                width: 100%;
            }

            .filter-section .row {
                flex-direction: column;
            }

            .filter-section .col-md-4 {
                margin-bottom: 1rem;
            }
        }

        /* Burger/mobile optimizations */
        @media (max-width: 768px) {
            .header-content { gap: 0.75rem; }
            .profile-section {
                padding: 0.35rem 0.5rem;
                gap: 0.4rem;
                background: rgba(0, 108, 59, 0.04);
                border-radius: 12px;
                flex-wrap: nowrap;
                justify-content: flex-end;
                margin-left: auto;
            }
            .profile-section > .dropdown { flex: 0 0 auto; }
            .profile-section .profile-image { flex: 0 0 auto; width: 36px; height: 36px; }
            .profile-section .text-end { flex: 1 1 0; max-width: 55%; min-width: 0; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
            .profile-section .fw-bold { font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .profile-section .text-success { font-size: 0.75rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .profile-section .text-muted.small { font-size: 0.68rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

            .notification-bell { width: 36px; height: 36px; }
            .notification-badge { top: -3px; right: -3px; width: 16px; height: 16px; font-size: 0.65rem; }

            /* Hide role earlier to keep one line */
            @media (max-width: 420px) {
                .profile-section .text-success { display: none; }
            }
            /* Hide timestamp on very tight screens */
            @media (max-width: 380px) {
                .profile-section .text-muted.small { display: none; }
                .profile-section .text-end { max-width: 68%; }
            }
        }

        /* Notification Dropdown Styles */
        .notification-dropdown {
            min-width: 320px;
            max-width: 400px;
            padding: 0;
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .notification-header {
            background: linear-gradient(to right, rgba(0, 108, 59, 0.05), rgba(0, 108, 59, 0.02));
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .notification-header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0;
        }

        .notification-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            background: rgba(0, 108, 59, 0.1);
            border-radius: 30px;
            color: var(--primary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .notification-count i {
            font-size: 0.75rem;
        }

        .notification-list {
            max-height: 360px;
            overflow-y: auto;
            padding: 0.5rem 0;
        }

        .notification-list::-webkit-scrollbar {
            width: 6px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }

        .notification-item {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.2s ease;
            cursor: pointer;
            border-left: 3px solid transparent;
        }

        .notification-item:hover {
            background: rgba(0, 108, 59, 0.05);
            border-left-color: var(--primary);
        }

        .notification-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 108, 59, 0.1);
            color: var(--primary);
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .notification-text {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #999;
        }

        .notification-footer {
            padding: 0.75rem;
            text-align: center;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            background: rgba(0, 108, 59, 0.02);
        }

        .notification-footer a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .notification-footer a:hover {
            color: var(--primary-dark);
        }

        .no-notifications {
            padding: 2rem;
            text-align: center;
        }

        .no-notifications i {
            font-size: 2.5rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .no-notifications p {
            color: var(--text-light);
            margin: 0;
            font-size: 0.9375rem;
        }

        /* Update the notification bell button in the HTML */
        .btn-notification {
            background: none;
            border: none;
            padding: 0;
            position: relative;
            z-index: 100000;
        }

        .btn-notification:focus {
            outline: none;
            box-shadow: none;
        }

        .btn-notification.show .notification-bell {
            background: rgba(0, 108, 59, 0.15);
            border-color: rgba(0, 108, 59, 0.2);
        }

        /* Dropdown Menu Styles */
        .dropdown-menu {
            position: absolute !important;
            z-index: 99999 !important;
            margin-top: 0.5rem !important;
            animation: fadeInUp 0.2s ease-out;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            transform: none !important;
            background: #fff !important;
        }

        .dropdown-menu .border-bottom {
            border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
        }

        .dropdown-menu .bg-light {
            background-color: #fff !important;
        }

        .notification-header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .notification-header-title .fw-semibold {
            color: #2c3e50;
            font-size: 0.95rem;
            font-weight: 600 !important;
        }

        .notification-count-badge {
            background: rgba(0, 108, 59, 0.1) !important;
            color: #006C3B !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            padding: 0.35rem 0.65rem !important;
            border-radius: 20px !important;
            display: flex !important;
            align-items: center !important;
            gap: 4px !important;
        }

        .notification-count-badge i {
            font-size: 0.7rem;
        }

        /* Ensure dropdown is above other elements */
        .dropdown {
            position: relative;
            z-index: 99999;
        }

        .dropdown-menu-end {
            right: 0 !important;
            left: auto !important;
        }

        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 20px;
            width: 16px;
            height: 16px;
            background: white;
            transform: rotate(45deg);
            border-left: 1px solid rgba(0, 0, 0, 0.08);
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            z-index: -1;
        }

        .notification-list {
            position: relative;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1;
        }

        /* Ensure dropdown is above other elements */
        .dropdown {
            position: relative;
            z-index: 99999;
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
                top: 60px !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                border-radius: 0 !important;
                height: auto !important;
                max-height: calc(100vh - 60px) !important;
                border: none !important;
                transform: none !important;
            }
            
            .dropdown-menu::before {
                display: none;
            }

            .notification-list {
                max-height: calc(100vh - 180px);
            }
        }

        /* Add these styles to match navbar.php */
        .notification-dropdown {
            position: absolute;
            top: calc(100% + 15px);
            right: -10px;
            width: 380px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px) scale(0.98);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: calc(100vh - 100px);
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .notification-dropdown::before {
            content: '';
            position: absolute;
            top: -6px;
            right: 25px;
            width: 12px;
            height: 12px;
            background: white;
            transform: rotate(45deg);
            border-left: 1px solid rgba(0, 0, 0, 0.08);
            border-top: 1px solid rgba(0, 0, 0, 0.08);
        }

        .notification-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .notification-header {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8f9fa;
            border-radius: 12px 12px 0 0;
        }

        .notification-header h3 {
            font-size: 1rem;
            color: #1a1a1a;
            margin: 0;
            font-weight: 600;
        }

        .mark-all-read {
            background: none;
            border: none;
            color: #006C3B;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .mark-all-read:hover {
            background: rgba(0, 108, 59, 0.1);
        }

        .notification-close {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 6px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 8px;
            transition: all 0.2s ease;
        }

        .notification-close:hover {
            background: rgba(0, 0, 0, 0.05);
            color: #333;
        }

        .notification-list {
            overflow-y: auto;
            max-height: 400px;
            padding: 8px 0;
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
        }

        .notification-list::-webkit-scrollbar {
            width: 6px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }

        @media (max-width: 768px) {
            .notification-dropdown {
                position: fixed;
                top: 60px;
                left: 0;
                right: 0;
                width: 100%;
                height: calc(100vh - 60px);
                max-height: none;
                border-radius: 0;
                transform: translateX(100%);
            }

            .notification-dropdown.show {
                transform: translateX(0);
            }

            .notification-dropdown::before {
                display: none;
            }

            .notification-list {
                max-height: none;
                flex: 1;
            }

            .notification-header {
                border-radius: 0;
            }

            .notification-footer {
                border-radius: 0;
            }
        }
        /* Stats Card Styles */
        .stats-icon {
            position: relative;
            z-index: 1;
        }

        .card {
            position: relative;
            z-index: 1;
        }

        /* Enhanced Dropdown Menu Styles */
        .dropdown {
            position: relative;
            z-index: 99999 !important;
        }

        .dropdown-menu {
            position: absolute !important;
            z-index: 99999 !important;
            margin-top: 0.5rem !important;
            animation: fadeInUp 0.2s ease-out;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            transform: none !important;
            background: #fff !important;
        }

        /* Profile Section Styles */
        .profile-section {
            position: relative;
            z-index: 1100 !important;
        }

        /* Notification Dropdown Container */
        .notification-container {
            position: relative;
            z-index: 100000 !important;
        }

        /* Update dropdown menu positioning */
        .dropdown-menu.show {
            display: block !important;
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            left: auto !important;
            transform: none !important;
            margin-top: 0.5rem !important;
            z-index: 100000 !important;
        }

        /* Ensure the notification bell stays above */
        .notification-bell {
            position: relative;
            z-index: 100000 !important;
        }

        /* Lower z-index for content elements */
        .card-body {
            position: relative;
            z-index: 1;
        }

        .stats-icon.revenue {
            position: relative;
            z-index: 1;
        }

        /* Ensure dropdown arrow stays visible */
        .dropdown-menu::before {
            z-index: 100001 !important;
        }

        /* Additional positioning fixes */
        .dropdown-menu[data-popper-placement="bottom-end"] {
            transform: translate3d(0, 0, 0) !important;
            top: 100% !important;
        }

        /* Ensure notification content stays above other elements */
        .notification-list,
        .notification-header-title,
        .notification-count-badge {
            position: relative;
            z-index: 100000 !important;
        }

        /* Fix stacking context for main containers */
            .main-content {
            position: relative;
            z-index: 1;
        }

        .container-fluid {
            position: relative;
            z-index: 1;
        }

        /* Ensure proper stacking for cards */
        .card {
            position: relative;
            z-index: 1;
        }

        /* Fix mobile dropdown positioning */
        @media (max-width: 768px) {
            .dropdown-menu {
                position: fixed !important;
                top: 60px !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                border-radius: 0 !important;
                height: auto !important;
                max-height: calc(100vh - 60px) !important;
                border: none !important;
                transform: none !important;
                z-index: 100000 !important;
            }
        }
        /* Fix Notification Dropdown Positioning and Z-index */
        .header-section {
            position: relative;
            z-index: 1100 !important;
        }

        .profile-section {
            position: relative;
            z-index: 1100 !important;
        }

        .dropdown {
            position: static !important;
        }

        .dropdown-menu {
            position: absolute !important;
            inset: auto 0px auto auto !important;
            margin-top: 0.5rem !important;
            z-index: 1200 !important;
            transform: none !important;
            min-width: 320px !important;
            width: auto !important;
            border: none !important;
            border-radius: 1rem !important;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15) !important;
            animation: dropdownFade 0.2s ease-out !important;
        }

        @keyframes dropdownFade {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 20px;
            width: 16px;
            height: 16px;
            background: white;
            transform: rotate(45deg);
            border-left: 1px solid rgba(0, 0, 0, 0.08);
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            z-index: -1;
            }
            
            .notification-list {
            max-height: 300px !important;
            overflow-y: auto !important;
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
        }

        /* Dashboard Content Z-index */
        .dashboard-content {
            position: relative;
            z-index: 1;
        }

        .card {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <?php include 'includes/loader.php'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <!-- Header Section -->
            <div class="header-container">
                <div class="header-content d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0">Manage Orders</h1>
                            <p class="text-muted mb-0">View and manage all customer orders</p>
                        </div>
                    <div class="profile-section">
                        <!-- Notification Bell -->
                        <div class="dropdown">
                            <button type="button" class="notification-bell" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <?php if ($unread_messages > 0): ?>
                                <span class="notification-badge"><?php echo $unread_messages; ?></span>
                                <?php endif; ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown" id="notificationMenu" aria-labelledby="notificationDropdown">
                                <div class="notification-header">
                                    <h6>
                                        <span class="fw-semibold">Notifications</span>
                                        <?php if ($unread_messages > 0): ?>
                                        <span class="notification-count-badge">
                                            <i class="fas fa-bell"></i>
                                            <span><?php echo $unread_messages; ?> New</span>
                                        </span>
                                        <?php endif; ?>
                                    </h6>
                                </div>
                                <div class="notification-list" id="notificationList">
                                    <!-- Notifications will be loaded here via JavaScript -->
                                    <div class="no-notifications">
                                        <i class="fas fa-bell-slash"></i>
                                        <p>No new notifications</p>
                                    </div>
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
                                <div class="fw-bold"><?php echo htmlspecialchars($admin_data['full_name']); ?></div>
                                <div class="text-success">Administrator</div>
                                <div class="text-muted small">
                                    <i class="fas fa-clock"></i>
                                    Last updated: <?php echo date('h:i A'); ?>
                                </div>
                            </div>
                            <?php if (!empty($admin_data['profile_image']) && file_exists("../uploads/profile_photos/{$admin_data['profile_image']}")): ?>
                                <img src="../uploads/profile_photos/<?php echo htmlspecialchars($admin_data['profile_image']); ?>" alt="Profile" class="profile-image">
                            <?php else: ?>
                                <img src="../assets/images/admin-avatar.png" alt="Profile" class="profile-image">
                            <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Filter by Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Orders</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="date" class="form-label">Filter by Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button type="button" class="btn btn-primary flex-grow-1" onclick="applyFilters()">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                    <i class="fas fa-times me-2"></i>Clear
                        </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Grid -->
            <div class="row g-4 order-grid">
                <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($order = mysqli_fetch_assoc($result)): ?>
                    <div class="col-md-4">
                            <div class="order-card fade-in">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Order #<?php echo htmlspecialchars($order['id']); ?></h5>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst(strtolower($order['status'])); ?>
                                    </span>
                            </div>
                                <div class="order-details mb-3">
                                    <p class="mb-1"><strong>Customer:</strong> <?php echo htmlspecialchars($order['display_name'] ?? 'N/A'); ?></p>
                                    <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['display_phone'] ?? 'N/A'); ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['display_email'] ?? 'N/A'); ?></p>
                                    <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address'] ?? 'N/A'); ?></p>
                                    <p class="mb-1"><strong>Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                                    <p class="mb-1"><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                                    <?php if($order['delivery_notes']): ?>
                                        <p class="mb-1"><strong>Notes:</strong> <?php echo htmlspecialchars($order['delivery_notes']); ?></p>
                                    <?php endif; ?>
                                    <p class="mb-0 text-muted">
                                        <small><i class="fas fa-clock me-1"></i><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></small>
                                    </p>
                        </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <select class="form-select form-select-sm w-auto" data-order-id="<?php echo $order['id']; ?>" 
                                        <?php echo (strtolower($order['status']) === 'completed') ? 'disabled' : ''; ?>>
                                    <option value="Pending" <?php echo strtolower($order['status']) === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Processing" <?php echo strtolower($order['status']) === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="Completed" <?php echo strtolower($order['status']) === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo strtolower($order['status']) === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="updateStatus(<?php echo $order['id']; ?>)"
                                        <?php echo (strtolower($order['status']) === 'completed') ? 'disabled' : ''; ?>>
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                            </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4>No Orders Found</h4>
                            <p class="text-muted">There are no orders matching your filters</p>
                            <button class="btn btn-primary" onclick="clearFilters()">
                                <i class="fas fa-sync-alt me-2"></i>Reset Filters
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Bootstrap 5 JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Function to update order status
        function updateStatus(orderId) {
            const select = document.querySelector(`select[data-order-id="${orderId}"]`);
            const newStatus = select.value;
            const card = select.closest('.order-card');
            const updateBtn = card.querySelector('button[onclick^="updateStatus"]');
            
            // Add loading state
            updateBtn.disabled = true;
            updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('../api/update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: newStatus,
                    admin_id: <?php echo $_SESSION['user_id']; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update status badge
                    const statusBadge = card.querySelector('.status-badge');
                    statusBadge.className = `status-badge status-${newStatus.toLowerCase()}`;
                    statusBadge.textContent = newStatus;

                    // Show success toast
                    showToast('Success', 'Order status updated successfully', 'success');

                    // If status is completed, disable the controls
                    if (newStatus.toLowerCase() === 'completed') {
                        select.disabled = true;
                        updateBtn.disabled = true;
                    }
                } else {
                    showToast('Error', data.message || 'Failed to update order status', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'Failed to update order status', 'danger');
            })
            .finally(() => {
                // Reset button state
                updateBtn.disabled = false;
                updateBtn.innerHTML = '<i class="fas fa-save"></i>';
            });
        }

        // Function to show toast notifications
        function showToast(title, message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = `toast fade-in bg-${type} text-white`;
            toast.innerHTML = `
                <div class="toast-header bg-${type} text-white">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;
            toastContainer.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 3000
            });
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        // Filter functionality
        function applyFilters() {
            const status = document.getElementById('status').value;
            const date = document.getElementById('date').value;
            
            let url = window.location.pathname;
            let params = [];
            
            if (status) params.push(`status=${encodeURIComponent(status)}`);
            if (date) params.push(`date=${encodeURIComponent(date)}`);
            
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            window.location.href = url;
        }

        function clearFilters() {
            window.location.href = window.location.pathname;
        }
    </script>
    <script src="js/notifications.js"></script>
</body>
</html> 