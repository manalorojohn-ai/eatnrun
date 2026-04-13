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

<?php
$page_title = "Dashboard";
include 'includes/ui/header.php';
?>

<style>
    .dashboard {
        padding: 2rem;
        background-color: var(--bg-color);
        min-height: calc(100vh - 80px);
        margin-top: 80px;
    }

    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        animation: pageEntry 0.6s var(--transition-sharp);
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2.5rem;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .welcome-text h1 {
        font-family: 'Unbounded', sans-serif;
        font-size: 1.8rem;
        color: var(--text-color);
        margin-bottom: 0.5rem;
    }

    .welcome-text p {
        color: var(--text-light);
    }

    .dashboard-actions {
        display: flex;
        gap: 1rem;
    }

    .action-btn {
        padding: 0.8rem 1.8rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        transition: var(--transition-fast);
        font-family: 'Outfit', sans-serif;
    }

    .action-btn.primary {
        background: var(--primary-color);
        color: white;
        box-shadow: var(--shadow-md);
    }

    .action-btn.primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .action-btn.secondary {
        background: white;
        color: var(--primary-color);
        border: 2px solid var(--primary-color);
    }

    .action-btn.secondary:hover {
        background: rgba(0, 184, 148, 0.05);
        transform: translateY(-2px);
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .dashboard-card {
        background: white;
        border-radius: var(--radius-lg);
        padding: 2rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid rgba(0,0,0,0.03);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .card-header h2 {
        font-family: 'Unbounded', sans-serif;
        font-size: 1.2rem;
        color: var(--text-color);
    }

    .order-list {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
    }

    .order-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.2rem;
        border-radius: var(--radius-md);
        background-color: var(--bg-alt);
        transition: var(--transition-fast);
        border: 1px solid transparent;
    }

    .order-item:hover {
        transform: scale(1.01);
        border-color: var(--primary-light);
        box-shadow: var(--shadow-sm);
    }

    .order-info {
        display: flex;
        align-items: center;
        gap: 1.2rem;
    }

    .order-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-sm);
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color);
        box-shadow: var(--shadow-sm);
    }

    .order-details h3 {
        font-size: 1.05rem;
        color: var(--text-color);
        margin-bottom: 0.25rem;
    }

    .order-details p {
        font-size: 0.9rem;
        color: var(--text-light);
    }

    .order-status {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-delivered {
        background: rgba(0, 184, 148, 0.1);
        color: var(--primary-color);
    }

    .status-processing {
        background: rgba(255, 159, 137, 0.1);
        color: #e67e22;
    }

    .profile-info {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
    }

    .info-group {
        display: flex;
        align-items: center;
        gap: 1.2rem;
        padding: 1.2rem;
        border-radius: var(--radius-md);
        background-color: var(--bg-alt);
    }

    .info-icon {
        width: 44px;
        height: 44px;
        border-radius: var(--radius-sm);
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color);
        box-shadow: var(--shadow-sm);
    }

    .info-details h3 {
        font-size: 0.85rem;
        color: var(--text-light);
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-details p {
        font-size: 1rem;
        color: var(--text-color);
        font-weight: 600;
    }

    @media (max-width: 992px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .dashboard-actions {
            width: 100%;
        }
        
        .action-btn {
            flex: 1;
        }
    }
</style>
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