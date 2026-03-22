<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

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

// Get total orders
$total_orders_query = "SELECT COUNT(*) as count FROM orders";
$total_orders_result = mysqli_query($conn, $total_orders_query);
$total_orders = mysqli_fetch_assoc($total_orders_result)['count'] ?? 0;

// Get total revenue
$total_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status != 'Cancelled'";
$total_revenue_result = mysqli_query($conn, $total_revenue_query);
$total_revenue = mysqli_fetch_assoc($total_revenue_result)['revenue'] ?? 0;

// Get total cancelled orders
$cancelled_orders_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'Cancelled'";
$cancelled_orders_result = mysqli_query($conn, $cancelled_orders_query);
$cancelled_orders = mysqli_fetch_assoc($cancelled_orders_result)['count'] ?? 0;

// Get weekly revenue data
$weekly_revenue_query = "SELECT 
    DATE(created_at) as date,
    COALESCE(SUM(total_amount), 0) as revenue
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND status != 'Cancelled'
    GROUP BY DATE(created_at)
    ORDER BY date ASC";
$weekly_revenue_result = mysqli_query($conn, $weekly_revenue_query);
$weekly_revenue_data = [];
$weekly_revenue_labels = [];
while ($row = mysqli_fetch_assoc($weekly_revenue_result)) {
    $weekly_revenue_data[] = $row['revenue'];
    $weekly_revenue_labels[] = date('D', strtotime($row['date']));
}

// Get monthly revenue data
$monthly_revenue_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COALESCE(SUM(total_amount), 0) as revenue
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    AND status != 'Cancelled'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC";
$monthly_revenue_result = mysqli_query($conn, $monthly_revenue_query);
$monthly_revenue_data = [];
$monthly_revenue_labels = [];
while ($row = mysqli_fetch_assoc($monthly_revenue_result)) {
    $monthly_revenue_data[] = $row['revenue'];
    $monthly_revenue_labels[] = date('M Y', strtotime($row['month'] . '-01'));
}

// Get order status distribution
$order_status_query = "SELECT 
    status,
    COUNT(*) as count
    FROM orders
    GROUP BY status";
$order_status_result = mysqli_query($conn, $order_status_query);
$order_status_data = [];
$order_status_labels = [];
while ($row = mysqli_fetch_assoc($order_status_result)) {
    $order_status_data[] = $row['count'];
    $order_status_labels[] = $row['status'];
}

// Get top selling items
$popular_items_query = "SELECT 
    m.name,
    COUNT(od.menu_item_id) as order_count,
    COALESCE(SUM(od.quantity), 0) as total_quantity
    FROM menu_items m
    LEFT JOIN order_details od ON m.id = od.menu_item_id
    LEFT JOIN orders o ON od.order_id = o.id
    WHERE o.status != 'Cancelled'
    GROUP BY m.id
    ORDER BY total_quantity DESC
    LIMIT 10";
$popular_items_result = mysqli_query($conn, $popular_items_query);
$popular_items_data = [];
$popular_items_labels = [];
while ($row = mysqli_fetch_assoc($popular_items_result)) {
    $popular_items_data[] = $row['total_quantity'];
    $popular_items_labels[] = $row['name'];
}

// Convert the data to JSON for JavaScript use
$chart_data = [
    'weekly' => [
        'labels' => $weekly_revenue_labels,
        'data' => $weekly_revenue_data
    ],
    'monthly' => [
        'labels' => $monthly_revenue_labels,
        'data' => $monthly_revenue_data
    ],
    'orderStatus' => [
        'labels' => $order_status_labels,
        'data' => $order_status_data
    ],
    'popularItems' => [
        'labels' => $popular_items_labels,
        'data' => $popular_items_data
    ]
];

?>
<?php
// New: Build data for requested dashboard layout
$period = $_GET['period'] ?? '7days';
$startParam = $_GET['start'] ?? null;
$endParam = $_GET['end'] ?? null;
$startDate = null;
$endDate = null;
if ($period === 'custom' && $startParam && $endParam) {
    $startDate = date('Y-m-d', strtotime($startParam));
    $endDate = date('Y-m-d', strtotime($endParam));
} else {
    switch ($period) {
        case '30days': $startDate = date('Y-m-d', strtotime('-30 days')); break;
        case '90days': $startDate = date('Y-m-d', strtotime('-90 days')); break;
        case '7days':
        default: $startDate = date('Y-m-d', strtotime('-7 days')); break;
    }
    $endDate = date('Y-m-d');
}
$dateFilterWhere = "created_at BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59'";
$dateFilterWhereOrdersAliased = "o.created_at BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59'";

// Booking stats mapped from order statuses
$booking_stats = ['approved' => 0, 'pending' => 0, 'completed' => 0, 'cancelled' => 0];
$status_q = mysqli_query($conn, "SELECT LOWER(o.status) as s, COUNT(*) c FROM orders o WHERE {$dateFilterWhereOrdersAliased} GROUP BY o.status");
while ($row = mysqli_fetch_assoc($status_q)) { if (isset($booking_stats[$row['s']])) { $booking_stats[$row['s']] = (int)$row['c']; } }

// Revenue stats
$revenue_stats = ['total' => 0.0, 'average' => 0.0];
$rev_q = mysqli_query($conn, "SELECT COALESCE(SUM(o.total_amount),0) t, COALESCE(AVG(o.total_amount),0) a FROM orders o WHERE {$dateFilterWhereOrdersAliased} AND o.status <> 'Cancelled'");
if ($r = mysqli_fetch_assoc($rev_q)) { $revenue_stats['total'] = (float)$r['t']; $revenue_stats['average'] = (float)$r['a']; }

// User stats
$user_stats = ['total' => 0, 'regular' => 0, 'admin' => 0];
$ut = mysqli_query($conn, "SELECT COUNT(*) c FROM users");
$user_stats['total'] = (int) (mysqli_fetch_assoc($ut)['c'] ?? 0);
$ur = mysqli_query($conn, "SELECT role, COUNT(*) c FROM users GROUP BY role");
while ($row = mysqli_fetch_assoc($ur)) { if ($row['role'] === 'admin') { $user_stats['admin'] = (int)$row['c']; } else { $user_stats['regular'] += (int)$row['c']; } }

// Booking trends per day
$booking_trends = ['labels' => [], 'datasets' => [[ 'label' => 'Bookings', 'data' => [], 'borderColor' => '#6D4C41', 'backgroundColor' => 'rgba(109,76,65,0.1)', 'fill' => true ]]];
$bt = mysqli_query($conn, "SELECT DATE(o.created_at) d, COUNT(*) c FROM orders o WHERE {$dateFilterWhereOrdersAliased} GROUP BY DATE(o.created_at) ORDER BY d ASC");
while ($row = mysqli_fetch_assoc($bt)) { $booking_trends['labels'][] = date('M j', strtotime($row['d'])); $booking_trends['datasets'][0]['data'][] = (int)$row['c']; }

// Order status distribution (filtered by date range)
$order_status_chart = ['labels' => [], 'data' => []];
$os = mysqli_query($conn, "SELECT LOWER(o.status) s, COUNT(*) c FROM orders o WHERE {$dateFilterWhereOrdersAliased} GROUP BY o.status");
while ($row = mysqli_fetch_assoc($os)) { $order_status_chart['labels'][] = ucfirst($row['s']); $order_status_chart['data'][] = (int)$row['c']; }

// Revenue trends per day
$revenue_trends = ['labels' => [], 'data' => [], 'averages' => []];
$rt = mysqli_query($conn, "SELECT DATE(o.created_at) d, COALESCE(SUM(o.total_amount),0) t, COALESCE(AVG(o.total_amount),0) a FROM orders o WHERE {$dateFilterWhereOrdersAliased} AND o.status <> 'Cancelled' GROUP BY DATE(o.created_at) ORDER BY d ASC");
while ($row = mysqli_fetch_assoc($rt)) { $revenue_trends['labels'][] = date('M j', strtotime($row['d'])); $revenue_trends['data'][] = round((float)$row['t'],2); $revenue_trends['averages'][] = round((float)$row['a'],2); }

// Cancellation reasons (top 10) within selected period
$cancel_reasons = ['labels' => [], 'data' => []];
$cr = mysqli_query($conn, "SELECT TRIM(COALESCE(NULLIF(o.cancel_reason,''),'Unspecified')) r, COUNT(*) c FROM orders o WHERE {$dateFilterWhereOrdersAliased} AND LOWER(o.status)='cancelled' GROUP BY r ORDER BY c DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($cr)) { $cancel_reasons['labels'][] = $row['r']; $cancel_reasons['data'][] = (int)$row['c']; }

// Number of bookings per item (proxy for room)
$room_bookings = ['labels' => [], 'data' => []];
$rb = mysqli_query($conn, "SELECT m.name n, COALESCE(SUM(od.quantity),0) q FROM menu_items m LEFT JOIN order_details od ON m.id=od.menu_item_id LEFT JOIN orders o ON od.order_id=o.id WHERE o.id IS NOT NULL AND {$dateFilterWhereOrdersAliased} AND o.status <> 'Cancelled' GROUP BY m.id ORDER BY q DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($rb)) { $room_bookings['labels'][] = $row['n']; $room_bookings['data'][] = (int)$row['q']; }

// Hourly trends
$hourly_trends = ['labels' => [], 'bookings' => [], 'revenue' => []];
for ($h=0;$h<24;$h++){ $hourly_trends['labels'][] = str_pad((string)$h,2,'0',STR_PAD_LEFT).':00'; $hr = mysqli_query($conn, "SELECT COUNT(*) c, COALESCE(SUM(total_amount),0) t FROM orders o WHERE {$dateFilterWhereOrdersAliased} AND HOUR(o.created_at)={$h} AND o.status <> 'Cancelled'"); $row = mysqli_fetch_assoc($hr); $hourly_trends['bookings'][] = (int)($row['c'] ?? 0); $hourly_trends['revenue'][] = round((float)($row['t'] ?? 0),2); }

// Recent bookings
$recent_bookings = [];
$rbk = mysqli_query($conn, "SELECT o.id, COALESCE(u.full_name,'Guest') u, o.total_amount, o.status, o.created_at FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($rbk)) { $recent_bookings[] = ['id'=>(int)$row['id'],'user'=>$row['u'],'room_type'=>'—','room_number'=>'—','check_in'=>'—','check_out'=>'—','total_price'=>(float)$row['total_amount'],'status'=>$row['status'],'created_at'=>$row['created_at']]; }

$has_30_days = true; $has_90_days = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Eat&Run</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Date Range Picker CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
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
            --shadow-hover: 0 4px 8px rgba(0,0,0,0.15);
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

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
        }

        /* Additional styles for injected header block */
        .page-title { display: flex; align-items: center; gap: 14px; }
        .burger-icon { width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: rgba(0,108,59,0.08); cursor: pointer; transition: var(--transition); }
        .burger-icon:hover { background: rgba(0,108,59,0.16); transform: translateY(-1px); }
        .title-text h1 { margin: 0; font-size: 1.4rem; line-height: 1.2; }
        .title-text h2 { margin: 2px 0 0 0; font-size: 0.9rem; font-weight: 500; color: var(--text-light); }
        .profile-section .admin-info { display: flex; flex-direction: column; margin-right: 12px; }
        .admin-name { font-weight: 600; }
        .admin-role, .last-updated { font-size: 0.85rem; color: var(--text-light); }
        .admin-avatar, .profile-image { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.12); }
        /* Notification dropdown refined */
        .notification-bell { position: relative; width: 36px; height: 36px; border: none; background: #e8f5f0; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .notification-bell i { color: #006C3B; }
        .notification-badge { position: absolute; top: -4px; right: -2px; background: #ff4444; color: #fff; width: 18px; height: 18px; font-size: 11px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .notification-dropdown { width: 340px; max-width: calc(100vw - 24px); }
        /* Mini profile pill on the right */
        .mini-profile { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); padding: 8px 10px; display: inline-flex; align-items: center; gap: 10px; }
        .mini-name { font-weight: 600; color: #2c3e50; white-space: nowrap; }
        .notification-header { padding: 12px 14px; border-bottom: 1px solid rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; }
        .notification-list { max-height: 320px; overflow-y: auto; }
        .notification-item { display: flex; gap: 12px; padding: 12px 14px; border-bottom: 1px solid rgba(0,0,0,0.04); cursor: pointer; }
        .notification-item:hover { background: #f8f9fa; }
        .notification-item.unread { background: #f1fff7; }
        .notification-icon { width: 36px; height: 36px; border-radius: 8px; background: #e8f5f0; color: #006C3B; display: flex; align-items: center; justify-content: center; }
        .notification-message { font-size: 0.95rem; color: #333; }
        .notification-time { font-size: 0.8rem; color: #888; }
        .notification-footer { padding: 10px 14px; border-top: 1px solid rgba(0,0,0,0.06); text-align: center; }
        .notification-footer a { text-decoration: none; color: #006C3B; font-weight: 500; }
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .header-container { padding: 1rem; margin: 0.75rem; }
            .title-text h1 { font-size: 1.1rem; }
            .title-text h2 { font-size: 0.8rem; }
            .profile-section { gap: 0.5rem; padding: 0.5rem 0.75rem; }
            .admin-avatar { width: 40px; height: 40px; }
            .burger-icon { width: 38px; height: 38px; }
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

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
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

        .chart-container {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .date-range-picker {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
        }

        .stats-icon.orders { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .stats-icon.revenue { background: linear-gradient(135deg, #e67e22, #f39c12); }
        .stats-icon.cancelled { background: linear-gradient(135deg, #e74c3c, #c0392b); }

        /* Profile Section Styles */
        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            background: linear-gradient(to right, rgba(0, 108, 59, 0.05), rgba(0, 108, 59, 0.02));
            transition: all 0.3s ease;
        }

        .profile-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .notification-bell {
            position: relative;
            cursor: pointer;
            width: 40px;
            height: 40px;
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

        .profile-image {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
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

        /* Print Button Styles */
        .print-btn {
            padding: 12px 24px;
            background: #006C3B;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .print-btn:hover {
            background: #005530;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.2);
        }

        /* Print-specific styles */
        @media print {
            body {
                padding: 20px;
                background: white;
            }

            .navbar,
            .notification-bell,
            .profile-section,
            .print-btn,
            .scroll-down,
            .loader-wrapper {
                display: none !important;
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }

            .header-container {
                box-shadow: none !important;
                margin: 0 0 20px 0 !important;
                padding: 0 !important;
            }

            .stats-card {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }

            .chart-container {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                margin-bottom: 20px !important;
            }

            /* Add page break before charts section */
            .chart-container:first-of-type {
                page-break-before: always;
            }

            /* Ensure charts are visible */
            canvas {
                max-width: 100% !important;
                height: auto !important;
            }

            /* Add print header */
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
            }

            .print-header h1 {
                color: #006C3B;
                font-size: 24px;
                margin-bottom: 5px;
            }

            .print-header p {
                color: #666;
                font-size: 14px;
            }
        }

        /* Add these styles for the notification dropdown */
        .dropdown-menu {
            min-width: 300px !important;
            margin-top: 0.5rem !important;
            padding: 0 !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
        }

        .notification-header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0;
        }

        .notification-count-badge {
            display: flex;
            align-items: center;
            gap: 4px;
            background: #e8f5f0;
            color: #006C3B;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
        }

        .notification-count-badge i {
            font-size: 10px;
        }

        .notification-list {
            max-height: 300px;
            overflow-y: auto;
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

        .text-success {
            color: #006C3B !important;
        }

        .bg-light {
            background-color: #fff !important;
        }

        .border-bottom {
            border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
        }

        .border-top {
            border-top: 1px solid rgba(0, 0, 0, 0.08) !important;
        }

        .notification-bell {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(0, 108, 59, 0.1);
            transition: all 0.3s ease;
        }

        .notification-bell i {
            color: #006C3B;
            font-size: 1.2rem;
        }

        .notification-bell:hover {
            background: rgba(0, 108, 59, 0.2);
            transform: translateY(-2px);
        }

        .btn-notification {
            background: none !important;
            border: none !important;
            padding: 0 !important;
            box-shadow: none !important;
        }

        .btn-notification:focus {
            outline: none !important;
            box-shadow: none !important;
        }
        /* Dashboard layout fixes */
        .dashboard-container { padding: 20px; max-width: 1400px; margin: 0 auto; margin-left: 240px; }
        /* Improve burger (hamburger) mode support via body class flags */
        body.sidebar-collapsed .dashboard-container { margin-left: 70px; }
        body.sidebar-hidden .dashboard-container { margin-left: 0; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .date-filter { display: flex; gap: 15px; align-items: center; }
        .date-filter select, .date-filter input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .quick-stat-card { padding: 20px; border-radius: 10px; color: white; display: flex; flex-direction: column; gap: 10px; }
        .quick-stat-card.pink { background: linear-gradient(135deg, #FF6B6B, #FF8787); }
        .quick-stat-card.blue { background: linear-gradient(135deg, #42A5F5, #64B5F6); }
        .quick-stat-card.green { background: linear-gradient(135deg, #66BB6A, #81C784); }
        .quick-stat-card.brown { background: linear-gradient(135deg, #8D6E63, #A1887F); }
        .stat-icon { font-size: 24px; margin-bottom: 10px; }
        .stat-label { font-size: 14px; opacity: 0.9; }
        .stat-value { font-size: 24px; font-weight: bold; }
        .stat-detail { font-size: 14px; opacity: 0.9; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.2); }
        .charts-row, .bottom-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .chart-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .chart-card h2 { margin-bottom: 20px; color: #333; font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .chart-container { height: 300px; position: relative; }
        .chart-container canvas { width: 100% !important; height: 300px !important; }
        .recent-bookings { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .recent-bookings h2 { margin-bottom: 20px; color: #333; font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .admin-table-status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; text-transform: capitalize; color: white; display: inline-block; }
        .admin-table-status-pending { background: #FFF3CD; color: #856404; }
        .admin-table-status-approved { background: #D4EDDA; color: #155724; }
        .admin-table-status-completed { background: #CCE5FF; color: #004085; }
        .admin-table-status-cancelled { background: #F8D7DA; color: #721C24; }
        .admin-table-status-rejected { background: #FFE5E5; color: #DC3545; }
        @media (max-width: 992px) { .dashboard-container { margin-left: 70px; } }
        @media (max-width: 768px) {
            .dashboard-container { padding: 8px; margin-left: 0; }
            .dashboard-header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .stats-row, .charts-row, .bottom-row { display: flex; flex-direction: column; gap: 14px; }
            .quick-stat-card, .chart-card, .recent-bookings { padding: 12px; }
            .chart-container { height: 220px; }
            .chart-container canvas { height: 220px !important; }
            .recent-bookings h2 { font-size: 1.1rem; }
            .table-responsive { overflow-x: auto; }
            .table th, .table td { padding: 8px; font-size: 13px; }
            .date-filter select, .date-filter input { padding: 6px; font-size: 13px; }
            .dashboard-header h1 { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="header-container">
                <div class="header-content d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1">Reports</h1>
                            <p class="text-muted mb-0">View and manage all customer orders</p>
                        </div>
                    <div class="d-flex align-items-center" style="gap:12px;">
                        <div class="mini-profile">
                            <div class="dropdown">
                                <button type="button" class="notification-bell" id="notificationDropdown" aria-label="Notifications" title="Notifications" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell"></i>
                                    <span class="notification-badge" id="notificationCount" style="display:none;">0</span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end notification-dropdown" id="notificationMenu" aria-labelledby="notificationDropdown">
                                    <div class="notification-header">
                                        <h6 class="mb-0"><span class="fw-semibold">Notifications</span></h6>
                                    </div>
                                    <div class="notification-list" id="notificationList"><div class="p-3 text-muted small">No notifications</div></div>
                                    <div class="notification-footer">
                                        <a href="notifications.php">View All Notifications <i class="fas fa-chevron-right"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="mini-name"><?php echo htmlspecialchars($admin_name); ?></div>
                            <?php
                                $avatarPath = !empty($admin_profile_image) ? ('../' . ltrim($admin_profile_image, '/')) : '../img/default-avatar.png';
                            ?>
                            <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Profile" class="profile-image">
                        </div>
                    </div>
                </div>
            </div>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <div class="date-filter">
                <select id="periodSelect" onchange="updateDashboard(this.value)">
                    <option value="7days" <?php echo $period==='7days'?'selected':''; ?>>Last 7 Days</option>
                    <option value="30days" <?php echo $period==='30days'?'selected':''; ?>>Last 30 Days</option>
                    <option value="90days" <?php echo $period==='90days'?'selected':''; ?>>Last 90 Days</option>
                    <option value="custom" <?php echo $period==='custom'?'selected':''; ?>>Custom Range</option>
                </select>
                <div id="customDateRange" style="display: <?php echo $period==='custom'?'block':'none'; ?>;">
                    <input type="date" id="startDate" value="<?php echo htmlspecialchars($startParam ?? ''); ?>" onchange="updateCustomRange()">
                    <input type="date" id="endDate" value="<?php echo htmlspecialchars($endParam ?? ''); ?>" onchange="updateCustomRange()">
                    </div>
                                </div>
                            </div>

        <div class="stats-row">
            <div class="quick-stat-card pink">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-label">Active Bookings</div>
                <div class="stat-value"><?php echo (int)($booking_stats['approved'] ?? 0); ?></div>
                <div class="stat-detail">Pending: <?php echo (int)($booking_stats['pending'] ?? 0); ?></div>
                        </div>

            <div class="quick-stat-card blue">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-label">Completed Bookings</div>
                <div class="stat-value"><?php echo (int)($booking_stats['completed'] ?? 0); ?></div>
                <div class="stat-detail">Cancelled: <?php echo (int)($booking_stats['cancelled'] ?? 0); ?></div>
            </div>

            <div class="quick-stat-card green">
                <div class="stat-icon"><i class="fas fa-coins"></i></div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">₱<?php echo number_format($revenue_stats['total'] ?? 0, 2); ?></div>
                <div class="stat-detail">Avg. per booking: ₱<?php echo number_format($revenue_stats['average'] ?? 0, 2); ?></div>
            </div>

            <div class="quick-stat-card brown">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?php echo (int)($user_stats['total'] ?? 0); ?></div>
                <div class="stat-detail">Regular: <?php echo (int)($user_stats['regular'] ?? 0); ?> | Admin: <?php echo (int)($user_stats['admin'] ?? 0); ?></div>
                </div>
            </div>

        <div class="charts-row">
            <div class="chart-card">
                <h2><i class="fas fa-chart-line"></i>Booking Trends</h2>
                <div class="chart-container">
                    <canvas id="bookingTrendsChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2><i class="fas fa-coins"></i>Revenue Overview</h2>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2><i class="fas fa-ban"></i>Cancellation Reasons</h2>
                <div class="chart-container">
                    <canvas id="cancelReasonsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="bottom-row">
            <div class="chart-card">
                <h2><i class="fas fa-bed"></i>Number of Bookings per Room Name</h2>
                <div class="chart-container">
                    <canvas id="roomBookingsChart"></canvas>
                    </div>
                </div>

            <div class="chart-card">
                <h2><i class="fas fa-clock"></i>Hourly Booking Distribution</h2>
                <div class="chart-container">
                    <canvas id="hourlyBookingChart"></canvas>
                        </div>
                        </div>
            <div class="chart-card">
                <h2><i class="fas fa-chart-pie"></i>Order Status Distribution</h2>
                <div class="chart-container">
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="recent-bookings">
            <h2><i class="fas fa-list"></i>Recent Bookings</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Booked On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($booking['id']); ?></td>
                            <td><?php echo htmlspecialchars($booking['user']); ?></td>
                            <td><?php echo htmlspecialchars($booking['room_type']); ?> (<?php echo htmlspecialchars($booking['room_number']); ?>)</td>
                            <td><?php echo htmlspecialchars($booking['check_in']); ?></td>
                            <td><?php echo htmlspecialchars($booking['check_out']); ?></td>
                            <td>₱<?php echo number_format($booking['total_price'], 2); ?></td>
                            <td><span class="admin-table-status admin-table-status-<?php echo strtolower($booking['status']); ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                            <td><span class="created-at-timestamp" data-timestamp="<?php echo htmlspecialchars($booking['created_at']); ?>"><?php echo htmlspecialchars($booking['created_at']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    console.log('Chart.js loaded:', typeof Chart);
    const bookingTrends = <?php echo json_encode($booking_trends); ?>;
    const revenueTrends = <?php echo json_encode($revenue_trends); ?>;
    const roomBookings = <?php echo json_encode($room_bookings); ?>;
    const hourlyTrends = <?php echo json_encode($hourly_trends); ?>;
    const cancelReasons = <?php echo json_encode($cancel_reasons); ?>;
    const orderStatusChart = <?php echo json_encode($order_status_chart); ?>;

    // Keep references to all charts so we can resize them when burger mode toggles
    const dashboardCharts = [];

    document.addEventListener('DOMContentLoaded', function() {
        const commonOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } };

        const bookingTrendsLabels = bookingTrends.labels || [];
        const bookingTrendsDatasets = bookingTrends.datasets || [];
        const hasBookingTrendsData = bookingTrendsLabels.length > 0 && bookingTrendsDatasets.length > 0;
        const bookingTrendsChart = new Chart(document.getElementById('bookingTrendsChart'), {
                type: 'line',
                data: {
                labels: hasBookingTrendsData ? bookingTrendsLabels : [''],
                datasets: hasBookingTrendsData ? bookingTrendsDatasets : [{ label: 'Bookings', data: [], borderColor: '#6D4C41', backgroundColor: 'rgba(109,76,65,0.1)', fill: false }]
                },
                options: {
                ...commonOptions,
                plugins: { legend: { display: true, position: 'top' }, tooltip: { mode: 'index', intersect: false } },
                scales: { x: { title: { display: true, text: 'Date' } }, y: { title: { display: true, text: 'Bookings' }, beginAtZero: true } }
            }
        });
        dashboardCharts.push(bookingTrendsChart);

        const revenueChartInst = new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
                data: {
                labels: revenueTrends.labels || [],
                datasets: [
                    { label: 'Total Revenue (₱)', data: revenueTrends.data || [], backgroundColor: '#42A5F5', borderRadius: 8, order: 2, yAxisID: 'y' },
                    { label: 'Average Revenue (₱)', data: revenueTrends.averages || [], type: 'line', borderColor: '#66BB6A', backgroundColor: 'transparent', order: 1, yAxisID: 'y1' }
                ]
                },
                options: {
                ...commonOptions,
                scales: {
                    y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Total Revenue (₱)' } },
                    y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Average Revenue (₱)' }, grid: { drawOnChartArea: false } }
                },
                plugins: { tooltip: { callbacks: { label: function(context){ return context.dataset.label + ': ₱' + Number(context.raw||0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}); } } } }
            }
        });
        dashboardCharts.push(revenueChartInst);

        const roomBookingsChartInst = new Chart(document.getElementById('roomBookingsChart'), {
                type: 'bar',
            data: { labels: roomBookings.labels || [], datasets: [{ label: 'Number of Bookings', data: roomBookings.data || [], backgroundColor: '#42A5F5', borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'Number of Bookings' } }, x: { title: { display: true, text: 'Room Name' } } } }
        });
        dashboardCharts.push(roomBookingsChartInst);

        const hourlyBookingChartInst = new Chart(document.getElementById('hourlyBookingChart'), {
            type: 'line',
                data: {
                labels: hourlyTrends.labels || [],
                datasets: [
                    { label: 'Number of Bookings', data: hourlyTrends.bookings || [], borderColor: '#8D6E63', backgroundColor: 'rgba(141, 110, 99, 0.1)', fill: true, pointBackgroundColor: '#8D6E63', yAxisID: 'y' },
                    { label: 'Revenue (₱)', data: hourlyTrends.revenue || [], borderColor: '#66BB6A', backgroundColor: 'transparent', borderDash: [5,5], fill: false, yAxisID: 'y1' }
                ]
                },
                options: {
                ...commonOptions,
                    scales: {
                    x: { title: { display: true, text: 'Hour of Day' } },
                    y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Number of Bookings' } },
                    y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Revenue (₱)' }, grid: { drawOnChartArea: false } }
                },
                plugins: { tooltip: { callbacks: { label: function(context){ if(context.dataset.label==='Revenue (₱)'){ return context.dataset.label + ': ₱' + Number(context.raw||0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}); } return context.dataset.label + ': ' + context.raw; } } } }
            }
        });
        dashboardCharts.push(hourlyBookingChartInst);

        // Cancellation Reasons (Horizontal Bar)
        const crLabels = cancelReasons.labels || [];
        const crData = cancelReasons.data || [];
        if (crLabels.length > 0) {
            const cancelReasonsChartInst = new Chart(document.getElementById('cancelReasonsChart'), {
                type: 'bar',
                data: { labels: crLabels, datasets: [{ label: 'Cancellations', data: crData, backgroundColor: '#e74c3c', borderRadius: 6 }] },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { x: { beginAtZero: true, title: { display: true, text: 'Count' } }, y: { title: { display: true, text: 'Reason' } } } }
            });
            dashboardCharts.push(cancelReasonsChartInst);
        }

        // Order Status Distribution (Food Orders)
        const osLabels = orderStatusChart.labels || [];
        const osData = orderStatusChart.data || [];
        if (osLabels.length > 0) {
            const orderStatusChartInst = new Chart(document.getElementById('orderStatusChart'), {
                type: 'doughnut',
                data: { labels: osLabels, datasets: [{ data: osData, backgroundColor: ['#f1c40f','#3498db','#2ecc71','#e74c3c','#9b59b6','#95a5a6'] }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
            });
            dashboardCharts.push(orderStatusChartInst);
        }

        document.querySelectorAll('.created-at-timestamp').forEach(function(element){ const timestamp = element.getAttribute('data-timestamp'); element.textContent = formatTimestamp(timestamp); });
    });

    function formatTimestamp(timestamp){ const date = new Date(timestamp); const now = new Date(); const isToday = date.toDateString() === now.toDateString(); let hours = date.getHours(); const minutes = date.getMinutes(); const ampm = hours >= 12 ? 'PM' : 'AM'; hours = hours % 12; hours = hours ? hours : 12; const minutesStr = minutes < 10 ? '0' + minutes : minutes; const timeStr = `${hours}:${minutesStr} ${ampm}`; if (isToday) { return timeStr; } else { return `${date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}, ${timeStr}`; } }

    function updateDashboard(period){ const customDateRange = document.getElementById('customDateRange'); if (period === 'custom') { customDateRange.style.display = 'block'; } else { customDateRange.style.display = 'none'; window.location.href = `?period=${period}`; } }
    function updateCustomRange(){ const startDate = document.getElementById('startDate').value; const endDate = document.getElementById('endDate').value; if (startDate && endDate) { window.location.href = `?period=custom&start=${startDate}&end=${endDate}`; } }

    // Sidebar toggle handler (burger icon)
    (function(){ const toggle = document.getElementById('sidebarToggle'); if(toggle){ toggle.addEventListener('click', function(){ const b=document.body; if (b.classList.contains('sidebar-hidden')) { b.classList.remove('sidebar-hidden'); b.classList.add('sidebar-collapsed'); } else if (b.classList.contains('sidebar-collapsed')) { b.classList.remove('sidebar-collapsed'); } else { b.classList.add('sidebar-collapsed'); } }); } })();

    // Notifications: Bootstrap dropdown + fetch, render, mark read
    (function(){
        const bellBtn = document.getElementById('notificationDropdown');
        const menu = document.getElementById('notificationMenu');
        const badge = document.getElementById('notificationCount');
        const list = document.getElementById('notificationList');

        function humanDate(ts){ try { const d=new Date(ts); if(String(ts).match(/^\d{4}-/)) { return d.toLocaleDateString(undefined,{month:'short',day:'numeric',year:'numeric'}); } return String(ts); } catch(e){ return String(ts||''); } }

        async function fetchCount(){
            try { const res = await fetch('../get_notification_count.php', { credentials:'same-origin' }); const txt = await res.text(); const num = parseInt(txt,10)||0; if(num>0){ badge.style.display='flex'; badge.textContent=String(num); } else { badge.style.display='none'; } } catch(e){}
        }
        async function fetchList(){
            try {
                const res = await fetch('../fetch_notifications.php', { credentials:'same-origin' });
                const contentType = res.headers.get('content-type')||'';
                if (contentType.includes('application/json')){
                    const data = await res.json();
                    const items = (data.notifications||[]).map(function(n){
                        const id = n.id; const link = n.link || '#'; const message = n.message || 'Notification'; const time = n.time_ago || humanDate(n.created_at);
                        const unreadClass = n.is_read? '' : ' unread';
                        return '<div class="notification-item'+unreadClass+'" data-id="'+id+'" data-link="'+link+'">\
                                <div class="notification-icon"><i class="fas fa-bell"></i></div>\
                                <div class="notification-content">\
                                    <div class="notification-message">'+message+'</div>\
                                    <div class="notification-time">'+time+'</div>\
                                </div>\
                            </div>';
                    }).join('');
                    list.innerHTML = items || '<div class="p-3 text-muted small">No notifications</div>';
                } else {
                    const html = await res.text();
                    list.innerHTML = html || '<div class="p-3 text-muted small">No notifications</div>';
                }
            } catch(e){ list.innerHTML = '<div class="p-3 text-muted small">Failed to load</div>'; }
        }
        async function markAllRead(){ try { await fetch('../mark_notifications_read.php', { credentials:'same-origin' }); fetchCount(); } catch(e){} }
        function wireClicks(){ list.querySelectorAll('.notification-item').forEach(function(item){ item.addEventListener('click', function(){ const link=this.getAttribute('data-link'); if(link){ window.location.href = link; } }); }); }

        if (bellBtn && menu){
            bellBtn.addEventListener('show.bs.dropdown', async function(){ await fetchList(); await markAllRead(); setTimeout(wireClicks, 0); });
            // initial count + polling
            fetchCount(); setInterval(fetchCount, 30000);
        }
    })();

    // Resize charts on window resize and when burger (sidebar) state changes
    (function(){
        let resizeTimer = null;
        function resizeCharts(){
            dashboardCharts.forEach(function(c){ try { if (c && typeof c.resize === 'function') { c.resize(); } } catch(e) { /* ignore */ } });
        }
        window.addEventListener('resize', function(){
            if (resizeTimer) { clearTimeout(resizeTimer); }
            resizeTimer = setTimeout(resizeCharts, 150);
        });
        // Observe body class changes (e.g., sidebar open/collapse toggles)
        const body = document.querySelector('body');
        if (window.MutationObserver && body){
            const observer = new MutationObserver(function(mutations){
                for (const m of mutations){
                    if (m.type === 'attributes' && m.attributeName === 'class'){
                        setTimeout(resizeCharts, 250); // after sidebar transition
                        break;
                    }
                }
            });
            observer.observe(body, { attributes: true, attributeFilter: ['class'] });
        }
    })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
