<?php
// Remove any whitespace before opening PHP tag
ob_start(); // Add output buffering
session_start();
require_once "config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// Get user information
$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
$query = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    session_destroy();
    header("Location: login");
    exit();
}

// Get recent orders
$query = "SELECT * FROM orders WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 5";
$recent_orders_result = mysqli_query($conn, $query);
$recent_orders = [];
if ($recent_orders_result) {
    while ($row = mysqli_fetch_assoc($recent_orders_result)) {
        $recent_orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #006C3B;
            --secondary-green: #008543;
            --accent-yellow: #FFB800;
            --light-yellow: #FFF5D6;
            --text-dark: #2C2C2C;
            --text-light: #FFFFFF;
            --bg-light: #F8F9FA;
            --border-color: #E5E5E5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        .dashboard {
            padding: 2rem;
            background-color: var(--bg-light);
            min-height: calc(100vh - var(--nav-height));
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            margin-top: 5rem !important;

        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .welcome-text h1 {
            font-size: 1.8rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .welcome-text p {
            color: #666;
        }

        .dashboard-actions {
            display: flex;
            gap: 1rem;
        }

        .action-btn {
            padding: 0.7rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .action-btn.primary {
            background-color: var(--primary-green);
            color: var(--text-light);
        }

        .action-btn.primary:hover {
            background-color: var(--secondary-green);
        }

        .action-btn.secondary {
            background-color: var(--text-light);
            color: var(--primary-green);
            border: 2px solid var(--primary-green);
        }

        .action-btn.secondary:hover {
            background-color: rgba(0, 108, 59, 0.05);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: var(--text-light);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-header h2 {
            font-size: 1.2rem;
            color: var(--text-dark);
        }

        .order-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-radius: 8px;
            background-color: var(--bg-light);
            transition: all 0.3s ease;
        }

        .order-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .order-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .order-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background-color: var(--light-yellow);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-yellow);
        }

        .order-details h3 {
            font-size: 1rem;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .order-details p {
            font-size: 0.9rem;
            color: #666;
        }

        .order-status {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-delivered {
            background-color: rgba(0, 108, 59, 0.1);
            color: var(--primary-green);
        }

        .status-processing {
            background-color: rgba(255, 184, 0, 0.1);
            color: var(--accent-yellow);
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            background-color: var(--bg-light);
        }

        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background-color: var(--light-yellow);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-yellow);
        }

        .info-details h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .info-details p {
            font-size: 1rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .dashboard {
                padding: 1rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .welcome-text h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/ui/navbar.php'; ?>

    <div class="dashboard">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                    <p>Manage your orders and account settings</p>
                </div>
                <div class="dashboard-actions">
                    <a href="menu" class="action-btn primary">
                        <i class="fas fa-plus"></i>
                        New Order
                    </a>
                    <a href="profile" class="action-btn secondary">
                        <i class="fas fa-user"></i>
                        Edit Profile
                    </a>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>Recent Orders</h2>
                        <a href="my_orders" class="action-btn secondary">View All</a>
                    </div>
                    <div class="order-list" id="order-list">
                        <div id="loading-spinner" style="display: none;">Loading...</div>
                        <?php if (empty($recent_orders)): ?>
                            <p>No orders yet. Start ordering your favorite food!</p>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="order-item">
                                    <div class="order-info">
                                        <div class="order-icon">
                                            <i class="fas fa-utensils"></i>
                                        </div>
                                        <div class="order-details">
                                            <h3>Order #<?php echo htmlspecialchars($order['id']); ?></h3>
                                            <p><?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <span class="order-status <?php echo htmlspecialchars($order['status'] == 'completed' ? 'status-delivered' : 'status-processing'); ?>">
                                        <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>Profile Information</h2>
                    </div>
                    <div class="profile-info">
                        <div class="info-group">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-details">
                                <h3>Email Address</h3>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        <div class="info-group">
                            <div class="info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="info-details">
                                <h3>Phone Number</h3>
                                <p><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></p>
                            </div>
                        </div>
                        <div class="info-group">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-details">
                                <h3>Default Address</h3>
                                <p><?php echo htmlspecialchars($user['address'] ?? 'Not set'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/ui/footer.php'; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const orderList = document.getElementById('order-list');
            const loadingSpinner = document.getElementById('loading-spinner');

            // Show loading spinner
            loadingSpinner.style.display = 'block';

            // Simulate loading time
            setTimeout(() => {
                loadingSpinner.style.display = 'none';
            }, 1000); // Adjust time as needed
        });
    </script>
</body>
</html> 