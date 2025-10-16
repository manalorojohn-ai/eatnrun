<?php
session_start(); // Main session initialization - navbar.php no longer calls session_start()
require_once '../config/db.php';
require_once 'includes/notifications_handler.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
            exit();
        }

// Create notifications table if it doesn't exist
create_notifications_table($conn);

// Get admin user details
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT full_name, profile_image FROM users WHERE id = ? AND role = 'admin'";
$stmt = mysqli_prepare($conn, $admin_query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$admin_result = mysqli_stmt_get_result($stmt);
$admin_data = mysqli_fetch_assoc($admin_result);
$admin_name = $admin_data['full_name'] ?? 'Administrator';
$admin_profile_image = $admin_data['profile_image'] ?? '';

// Check for new notifications
check_new_orders($conn, $admin_id);
check_order_status_changes($conn, $admin_id);

// Get unread notifications count
$unread_messages = get_admin_unread_count($conn, $admin_id);

// Get orders count for today
$orders_today_query = "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()";
$orders_today_result = mysqli_query($conn, $orders_today_query);
$orders_today = mysqli_fetch_assoc($orders_today_result)['count'] ?? 0;

// Get daily revenue
$daily_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(created_at) = CURDATE()";
$daily_revenue_result = mysqli_query($conn, $daily_revenue_query);
$daily_revenue = mysqli_fetch_assoc($daily_revenue_result)['revenue'] ?? 0;

// Get total users count
$users_count_query = "SELECT COUNT(*) as count FROM users";
$users_count_result = mysqli_query($conn, $users_count_query);
$users_count = mysqli_fetch_assoc($users_count_result)['count'] ?? 0;

// Get recent orders with error handling
$recent_orders_query = "SELECT o.*, u.full_name 
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id 
                       ORDER BY o.created_at DESC 
                       LIMIT 5";
$recent_orders = mysqli_query($conn, $recent_orders_query);
if (!$recent_orders) {
    $recent_orders = false;
}

// Get popular items with error handling
$popular_items_query = "SELECT m.name, COUNT(od.menu_item_id) as order_count
                       FROM menu_items m
                       LEFT JOIN order_details od ON m.id = od.menu_item_id
                       GROUP BY m.id
                       ORDER BY order_count DESC
                       LIMIT 5";
$popular_items = mysqli_query($conn, $popular_items_query);
if (!$popular_items) {
    $popular_items = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Eat&Run</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/notifications.css" rel="stylesheet">
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

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title {
            display: flex;
            align-items: center;
        }

        .title-text {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .title-text h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .title-text h2 {
            font-size: 1.25rem;
            font-weight: 500;
            color: #7f8c8d;
            margin: 0;
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

        .profile-dropdown {
            position: absolute;
            top: calc(100% + 15px);
            right: 0;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 240px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow: hidden;
        }

        .profile-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-dropdown::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 30px;
            width: 16px;
            height: 16px;
            background: var(--white);
            transform: rotate(45deg);
            border-radius: 3px;
            box-shadow: -2px -2px 5px rgba(0, 0, 0, 0.04);
            z-index: -1;
        }

        .dropdown-header {
            padding: 1.25rem;
            border-bottom: 1px solid #eee;
            text-align: center;
            background: linear-gradient(45deg, var(--primary-light), rgba(0, 108, 59, 0.02));
        }

        .dropdown-header h4 {
            margin: 0;
            color: var(--text-dark);
            font-size: 1.1rem;
            font-weight: 600;
        }

        .dropdown-header p {
            margin: 4px 0 0;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .dropdown-items {
            padding: 0.75rem;
        }

        .dropdown-item {
            padding: 0.85rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 1rem;
            border-radius: 8px;
            margin-bottom: 0.25rem;
        }

        .dropdown-item:last-child {
            margin-bottom: 0;
        }

        .dropdown-item:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .dropdown-item:active {
            transform: scale(0.98);
        }

        .dropdown-item i {
            font-size: 1rem;
            width: 24px;
            text-align: center;
            color: inherit;
        }

        .dropdown-divider {
            height: 1px;
            background: #eee;
            margin: 0.75rem 0;
        }

        .last-updated {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .profile-section:hover .profile-image {
            transform: scale(1.05);
        }

        .dashboard-content {
            padding: 0 2rem 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .stats-card {
            background: var(--white);
            padding: 1.75rem;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 1.75rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
            opacity: 0;
            transition: var(--transition);
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .stats-card:hover::before {
            opacity: 1;
        }

        .stats-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .stats-icon i {
            font-size: 1.85rem;
            color: var(--white);
            transition: var(--transition);
        }

        .stats-card:hover .stats-icon {
            transform: scale(1.1);
        }

        .stats-info h3 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .stats-info p {
            color: #7f8c8d;
            margin: 0.25rem 0 0;
            font-size: 0.95rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .section-title i {
            color: var(--primary);
        }

        .action-button {
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            box-shadow: 0 2px 8px rgba(0, 108, 59, 0.2);
        }

        .action-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.3);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.8fr 1.2fr;
            gap: 2rem;
            margin-top: 2rem;
            padding: 0 2rem 2rem;
        }

        .orders-list, .popular-items {
            background: var(--white);
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            height: 100%;
        }

        .orders-list:hover, .popular-items:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .order-item, .popular-item {
            padding: 1rem;
            margin: 0.5rem 0;
            border-radius: 12px;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .order-item:hover, .popular-item:hover {
            background: #f8f9fa;
            border-color: #e9ecef;
            transform: translateX(4px);
        }

        .order-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .order-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .order-details h4 {
            margin: 0;
            font-size: 0.95rem;
            color: var(--text-dark);
        }

        .order-details p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .order-amount {
            font-weight: 600;
            color: var(--primary);
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .item-icon {
            width: 36px;
            height: 36px;
            background: var(--primary-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .item-details h4 {
            margin: 0;
            font-size: 0.95rem;
            color: var(--text-dark);
        }

        .item-details p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding-top: 56px; /* clear mobile header height from admin navbar */
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Enhanced mobile layout for burger mode */
        @media (max-width: 768px) {
            /* Compact header/profile row */
            .header-section .card-body {
                gap: 0.5rem;
            }
            .header-section .profile-section {
                padding: 0.35rem 0.5rem !important;
                gap: 0.4rem !important;
                border-radius: 12px;
                background: rgba(0, 108, 59, 0.04);
                flex-wrap: nowrap;
                justify-content: flex-end;
                margin-left: auto;
            }
            .header-section .profile-section > .dropdown { flex: 0 0 auto; }
            .header-section .profile-section .profile-image { flex: 0 0 auto; }
            .header-section .profile-section .text-end { flex: 1 1 0; }
            .header-section .profile-section .text-end {
                max-width: 55%;
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
                min-width: 0;
            }
            .header-section .profile-section .fw-bold {
                font-size: 0.88rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .header-section .profile-section .text-success {
                font-size: 0.75rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .header-section .profile-section .text-muted.small {
                font-size: 0.68rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            /* Bell sizing and badge for touch */
            .btn-notification .notification-bell {
                width: 36px;
                height: 36px;
            }
            .btn-notification .notification-badge,
            .notification-badge {
                top: -3px;
                right: -3px;
                width: 16px;
                height: 16px;
                font-size: 0.65rem;
            }

            /* Avatar smaller on phones */
            .header-section .profile-image {
                width: 36px !important;
                height: 36px !important;
            }

            /* Slightly narrow: hide role label earlier */
            @media (max-width: 420px) {
                .header-section .profile-section .text-success { display: none; }
                .header-section .profile-section .fw-bold { font-size: 0.9rem; }
            }

            /* If space is extremely tight, hide timestamp */
            @media (max-width: 380px) {
                .header-section .profile-section .text-muted.small { display: none; }
                .header-section .profile-section .text-end { max-width: 68%; }
            }

            .header-container {
                margin: 0.75rem 1rem 0.5rem;
                padding: 1rem 1rem;
                border-radius: 14px;
            }

            .header-content {
                gap: 0.75rem;
            }

            .title-text h1 {
                font-size: 1.35rem;
            }

            .title-text h2 {
                font-size: 1rem;
            }

            .profile-section {
                padding: 0.5rem 0.75rem;
                gap: 0.75rem;
                border-radius: 10px;
            }

            .profile-name { font-size: 1rem; }
            .profile-role { font-size: 0.85rem; }
            .profile-image { width: 44px; height: 44px; }

            .dashboard-content { padding: 0 1rem 1rem; }
            .content-grid { padding: 0 1rem 1rem; gap: 1rem; grid-template-columns: 1fr; }
            .orders-list, .popular-items { padding: 1rem; border-radius: 14px; }
            .section-header { margin-bottom: 1rem; }
            .stats-grid { gap: 1rem; margin-top: 1rem; }
            .stats-card { padding: 1.25rem; gap: 1rem; border-radius: 14px; }
            .stats-info h3 { font-size: 1.4rem; }

            /* Charts smaller on mobile */
            .card .card-body > div[style*="height:"] { height: 240px !important; }
        }

        .stats-icon.orders {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .stats-icon.revenue {
            background: linear-gradient(135deg, #e67e22, #f39c12);
        }

        .stats-icon.users {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        /* Toast Notification Styles */
        .toast-notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            padding: 15px;
            min-width: 300px;
            max-width: 400px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            z-index: 1100;
            cursor: pointer;
        }
        
        .toast-notification.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .toast-icon {
            width: 40px;
            height: 40px;
            background: #e8f5e9;
            color: #006C3B;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 5px;
            color: #333;
        }
        
        .toast-message {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.5;
        }
        
        .toast-close {
            background: none;
            border: none;
            color: #999;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
            margin: 0;
            line-height: 1;
            transition: color 0.2s ease;
        }
        
        .toast-close:hover {
            color: #333;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 992px) {
            body {
                position: relative;
                overflow-x: hidden;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                position: relative;
                min-height: 100vh;
                transition: transform 0.3s ease;
                will-change: transform;
            }

            .main-content.shifted {
                transform: translateX(240px);
            }

            /* When burger opens, gently dim/blur content for focus */
            body.nav-open .main-content {
                filter: blur(1.5px);
                transition: filter 0.2s ease;
            }

            .burger-icon {
                display: flex;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: -240px;
                height: 100vh;
                width: 240px;
                background: var(--white);
                transition: transform 0.3s ease;
                z-index: 1050;
                box-shadow: none;
                will-change: transform;
            }

            .sidebar.show {
                transform: translateX(240px);
                box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
            }

            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.5);
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                z-index: 1040;
                backdrop-filter: blur(2px);
                will-change: opacity, visibility;
            }

            .overlay.show {
                opacity: 1;
                visibility: visible;
            }

            .header-container {
                margin: 1rem;
                border-radius: 16px;
                position: relative;
                z-index: 1;
            }

            .header-content {
                padding: 1rem;
            }

            .dashboard-content {
                padding: 0 1rem;
                position: relative;
                z-index: 1;
            }

            .content-grid {
                padding: 0 1rem 1rem;
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stats-grid {
                padding: 0;
                margin: 1rem 0;
            }

            .stats-card {
                margin: 0.5rem 0;
            }

            .profile-section {
                padding: 0.5rem 1rem;
                position: relative;
                z-index: 1045;
            }

            .profile-dropdown {
                right: 1rem;
                z-index: 1046;
            }

            /* Ensure navbar stays on top */
            .navbar {
                position: relative;
                z-index: 1045;
            }

            /* Fix for any dropdowns in the navbar */
            .navbar .dropdown-menu {
                z-index: 1046;
            }

            /* Prevent content scrolling when sidebar is open */
            body.sidebar-open {
                overflow: hidden;
            }
        }

        @media (max-width: 576px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .profile-section {
                width: 100%;
                justify-content: space-between;
            }

            .profile-dropdown {
                width: calc(100% - 2rem);
                right: 1rem;
            }

            .stats-icon {
                width: 48px;
                height: 48px;
            }

            .stats-icon i {
                font-size: 1.5rem;
            }

            .stats-info h3 {
                font-size: 1.5rem;
            }
        }

        /* Chart Card Styles */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.12);
        }

        .card-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .btn-group .btn {
            padding: 0.375rem 1rem;
            font-size: 0.875rem;
        }

        .btn-group .btn.active {
            background-color: #006C3B;
            border-color: #006C3B;
            color: #fff;
        }

        .btn-outline-secondary {
            color: #6c757d;
            border-color: #dee2e6;
        }

        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #006C3B;
        }

        /* Animation for Charts */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Enhanced Notification Styles */
        .notification-dropdown {
            min-width: 320px !important;
            max-width: 400px !important;
            margin-top: 0.5rem !important;
            padding: 0 !important;
            border: none !important;
            border-radius: 12px !important;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15) !important;
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background-color: rgba(0, 108, 59, 0.05);
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0, 108, 59, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-icon i {
            color: #006C3B;
            font-size: 1.1rem;
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            margin-bottom: 0.25rem;
            color: #2c3e50;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .notification-time {
            color: #95a5a6;
            font-size: 0.8rem;
        }

        .notification-item.unread {
            background-color: rgba(0, 108, 59, 0.05);
        }

        .notification-item.unread .notification-message {
            font-weight: 600;
            color: #2c3e50;
        }

        .no-notifications {
            padding: 2rem;
            text-align: center;
            color: #95a5a6;
        }

        .no-notifications i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            opacity: 0.5;
        }

        .notification-header {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: #f8f9fa;
            border-radius: 12px 12px 0 0;
        }

        .notification-header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0;
        }

        .notification-count-badge {
            background: rgba(0, 108, 59, 0.1);
            color: #006C3B;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-footer {
            padding: 0.75rem;
            text-align: center;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
        }

        .notification-footer a {
            color: #006C3B;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .notification-footer a:hover {
            text-decoration: underline;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .notification-dropdown {
                position: fixed !important;
                top: 60px !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                height: calc(100vh - 60px) !important;
                border-radius: 0 !important;
            }

            .notification-list {
                max-height: calc(100vh - 180px) !important;
            }

            .notification-header {
                border-radius: 0;
            }

            .notification-footer {
                border-radius: 0;
                padding: 1rem;
            }
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
            z-index: 100000 !important;
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

        /* Update HTML structure */
        <div class="card mb-4 header-section">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div>
                        <h1 class="h3 mb-0">Dashboard</h1>
                        <p class="text-muted mb-0">Overview</p>
                    </div>
                </div>
                <div class="profile-section p-3 d-flex align-items-center gap-3">
                    <?php include 'includes/notification_dropdown.php'; ?>
                    <div class="text-end">
                        <div class="fw-bold"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div class="text-success">Administrator</div>
                        <div class="text-muted small">
                            <i class="fas fa-clock"></i>
                            Last updated: <?php echo date('h:i A'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </style>
</head>
<body>
    <?php include 'includes/loader.php'; ?>
    
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <!-- Header Section -->
            <div class="card mb-4 header-section">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div>
                            <h1 class="h3 mb-0">Dashboard</h1>
                            <p class="text-muted mb-0">Overview</p>
                        </div>
                    </div>
                    <div class="profile-section p-3 d-flex align-items-center gap-3">
                        <?php include 'includes/notification_dropdown.php'; ?>
                        <div class="text-end">
                            <div class="fw-bold"><?php echo htmlspecialchars($admin_name); ?></div>
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
            
            <!-- Profile Dropdown (Separate from profile section) -->
            <div class="profile-dropdown" id="profileDropdown">
                <div class="dropdown-header">
                    <h4><?php echo htmlspecialchars($admin_name); ?></h4>
                    <p>Administrator Account</p>
                </div>
                <div class="dropdown-items">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        My Profile
                    </a>
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="help.php" class="dropdown-item">
                        <i class="fas fa-question-circle"></i>
                        Help Center
                    </a>
                    <a href="#" class="dropdown-item" id="feedbackBtn">
                        <i class="fas fa-comment"></i>
                        Send Feedback
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
            
            <div class="dashboard-content">
                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon orders rounded-3 me-3">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                    <div>
                                        <h3 class="h2 fw-bold mb-0"><?php echo $orders_today; ?></h3>
                                        <p class="text-muted mb-0">Orders Today</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon revenue rounded-3 me-3">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                    <div>
                                        <h3 class="h2 fw-bold mb-0">₱<?php echo number_format($daily_revenue, 2); ?></h3>
                                        <p class="text-muted mb-0">24h Revenue</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon users rounded-3 me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h3 class="h2 fw-bold mb-0"><?php echo $users_count; ?></h3>
                                        <p class="text-muted mb-0">Total Users</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add this after the stats cards row and before the content grid -->
                <div class="row g-4 mb-4">
                    <!-- Revenue Chart -->
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Revenue Overview</h5>
                                    <div class="btn-group" id="revenuePeriodSelector">
                                        <button type="button" class="btn btn-sm btn-outline-secondary active" data-period="week">Week</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-period="month">Month</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-period="year">Year</button>
                                    </div>
                                </div>
                                <div style="height: 300px;">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Stats Chart -->
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Order Statistics</h5>
                                <div style="height: 300px;">
                                    <canvas id="orderStatsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-grid">
                    <div class="orders-list">
                        <div class="section-header">
                            <div class="section-title">
                                <i class="fas fa-history"></i>
                                Recent Orders
                            </div>
                            <a href="orders.php" class="action-button text-white text-decoration-none">
                                <i class="fas fa-eye"></i>
                                View All
                            </a>
                        </div>
                        <?php if ($recent_orders && mysqli_num_rows($recent_orders) > 0): ?>
                            <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                                <div class="order-item">
                                    <div class="order-info">
                                        <div class="order-icon">
                                            <i class="fas fa-receipt"></i>
                                    </div>
                                        <div class="order-details">
                                            <h4>Order #<?php echo $order['id']; ?> by <?php echo htmlspecialchars($order['full_name']); ?></h4>
                                            <p><?php echo date('M j, Y g:i a', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    </div>
                                    <div class="order-amount">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No recent orders found.</p>
                        <?php endif; ?>
                    </div>

                    <div class="popular-items">
                        <div class="section-header">
                            <div class="section-title">
                                <i class="fas fa-star"></i>
                                Popular Items
                </div>
                            <a href="menu_items.php" class="action-button text-white text-decoration-none">
                                <i class="fas fa-utensils"></i>
                                Manage Menu
                            </a>
                    </div>
                        <?php if ($popular_items && mysqli_num_rows($popular_items) > 0): ?>
                            <?php while ($item = mysqli_fetch_assoc($popular_items)): ?>
                                <div class="popular-item">
                                    <div class="item-info">
                                        <div class="item-icon">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                        <div class="item-details">
                                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                            <p><?php echo $item['order_count']; ?> orders</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No popular items found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom Dashboard JavaScript -->
    <script src="../assets/js/dashboard.js"></script>
    <!-- Notifications JavaScript -->
    <script src="js/notifications.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize NotificationManager
            NotificationManager.init();
            
            // Initialize charts
            initRevenueChart('week');

            // Add click event listeners to period selector buttons
            const periodButtons = document.querySelectorAll('#revenuePeriodSelector button');
            periodButtons.forEach(button => {
                button.addEventListener('click', function() {
                    periodButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    initRevenueChart(this.dataset.period);
                });
            });
        });
    </script>
</body>
</html> 