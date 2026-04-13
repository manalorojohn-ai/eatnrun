<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Function to get order status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'status-pending';
        case 'processing':
            return 'status-processing';
        case 'completed':
            return 'status-completed';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return 'status-default';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-light: #e8f5e9;
            --primary-dark: #005530;
            --text-color: #2d3436;
            --border-color: #e0e0e0;
            --success-color: #4CAF50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --info-color: #2196F3;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 8px 16px rgba(0, 0, 0, 0.1);
            --gradient-primary: linear-gradient(135deg, #006C3B 0%, #00875A 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: var(--text-color);
            line-height: 1.6;
        }

        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .page-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.1) 0%, transparent 60%),
                radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.08) 0%, transparent 60%);
            opacity: 0.8;
            animation: gradientShift 15s ease infinite alternate;
        }

        .orders-title {
            color: var(--white);
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }

        .orders-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            font-weight: 400;
            position: relative;
            z-index: 2;
        }

        .orders-container {
            max-width: 1200px;
            margin: -40px auto 40px;
            padding: 40px;
            background: var(--white);
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 3;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
        }

        .no-orders {
            text-align: center;
            padding: 60px 20px;
            background: linear-gradient(to bottom, var(--bg-color), #ffffff);
            border-radius: var(--border-radius-lg);
            margin: 20px 0;
        }

        .no-orders-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 24px;
            color: var(--primary-light);
            opacity: 0.8;
            animation: floatIcon 3s ease-in-out infinite;
        }

        .no-orders-text {
            font-size: 24px;
            color: var(--text-color);
            margin-bottom: 12px;
            font-weight: 600;
        }

        .no-orders-subtext {
            color: var(--text-light);
            margin-bottom: 32px;
            font-size: 16px;
        }

        .browse-menu-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            border-radius: var(--border-radius-md);
            font-weight: 500;
            font-size: 15px;
            transition: var(--transition);
        }

        .browse-menu-btn:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .error-message {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes floatIcon {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @media (max-width: 768px) {
            .orders-container {
                margin: 20px;
                padding: 20px;
            }

            .orders-title {
                font-size: 32px;
            }

            .no-orders {
                padding: 40px 20px;
            }

            .no-orders-icon {
                width: 60px;
                height: 60px;
            }
        }

        .error-banner {
            background-color: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .order-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .order-id {
            font-weight: 600;
            color: var(--primary-color);
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
        }

        .order-details {
            margin-bottom: 15px;
        }

        .order-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3e0;
            color: #ef6c00;
        }

        .status-processing {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        .status-completed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .buy-again-btn {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .buy-again-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
    </style>
</head>
<body>
    <?php include 'includes/ui/navbar.php'; ?>

    <div class="orders-hero">
        <h1 class="orders-title">My Orders</h1>
        <p class="orders-subtitle">Track and manage your orders</p>
    </div>

    <div class="orders-container">
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="no-orders">
            <svg class="no-orders-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 3H5c-1.11 0-2 .89-2 2v14c0 1.11.89 2 2 2h14c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm0 16H5V5h14v14zm-7-2h2v-4h4v-2h-4V7h-2v4H8v2h4z"/>
            </svg>
            <h2 class="no-orders-text">No Orders Yet</h2>
            <p class="no-orders-subtext">Start ordering your favorite dishes!</p>
            <a href="menu.php" class="browse-menu-btn">
                <i class="fas fa-utensils"></i>
                Browse Menu
            </a>
        </div>
    </div>

    <?php include 'includes/ui/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ordersContainer = document.querySelector('.orders-container');
            const noOrders = document.querySelector('.no-orders');
            
            // Initial state
            noOrders.style.opacity = '0';
            noOrders.style.transform = 'translateY(20px)';
            
            // Animate in with delay
            setTimeout(() => {
                noOrders.style.opacity = '1';
                noOrders.style.transform = 'translateY(0)';
                noOrders.style.transition = 'all 0.8s cubic-bezier(0.165, 0.84, 0.44, 1)';
            }, 300);

            // Error message handling
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.style.display = 'block';
                setTimeout(() => {
                    errorMessage.style.opacity = '0';
                    setTimeout(() => {
                        errorMessage.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });

        function formatDate(dateString) {
            const options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit', 
                minute: '2-digit'
            };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        function formatPrice(price) {
            return '₱' + parseFloat(price).toFixed(2);
        }

        function getStatusBadgeClass(status) {
            switch (status.toLowerCase()) {
                case 'pending':
                    return 'status-pending';
                case 'processing':
                    return 'status-processing';
                case 'completed':
                    return 'status-completed';
                case 'cancelled':
                    return 'status-cancelled';
                default:
                    return '';
            }
        }

        function fetchOrders() {
            document.getElementById('loadingIndicator').style.display = 'flex';
            
            fetch('fetch_orders.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loadingIndicator').style.display = 'none';
                    const ordersContent = document.getElementById('ordersContent');
                    
                    if (data.length === 0) {
                        ordersContent.innerHTML = `
                            <div class="no-orders">
                                <i class="fas fa-shopping-bag no-orders-icon"></i>
                                <h3 class="no-orders-text">No orders yet</h3>
                                <p class="no-orders-subtext">You haven't placed any orders yet. Start ordering your favorite meals!</p>
                                <a href="menu" class="browse-menu-btn">
                                    <i class="fas fa-utensils"></i>
                                    Browse Menu
                                </a>
                            </div>
                        `;
                        return;
                    }
                    
                    const ordersHtml = data.map(order => `
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div style="font-size: 1.1rem; font-weight: 600;">Order #${order.id}</div>
                                    <div style="font-size: 0.9rem; opacity: 0.9;">${formatDate(order.created_at)}</div>
                                </div>
                                <span class="status-badge ${getStatusBadgeClass(order.status)}">
                                    ${order.status}
                                </span>
                            </div>
                            <div class="order-content">
                                <div class="order-items">
                                    ${order.items.map(item => `
                                        <div class="order-item">
                                            <div>
                                                <div style="font-weight: 500;">${item.name}</div>
                                                <div style="color: #666; font-size: 0.9rem;">Quantity: ${item.quantity}</div>
                                            </div>
                                            <div>${formatPrice(item.price * item.quantity)}</div>
                                        </div>
                                    `).join('')}
                                </div>
                                <div class="order-summary">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span>Subtotal</span>
                                        <span>${formatPrice(order.subtotal)}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span>Delivery Fee</span>
                                        <span>${formatPrice(order.delivery_fee || 50)}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding-top: 0.5rem; border-top: 2px dashed #eee; font-weight: 600; color: #006C3B;">
                                        <span>Total</span>
                                        <span>${formatPrice(order.total_amount)}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="order-actions">
                                <?php if (strtolower($order['status']) === 'cancelled'): ?>
                                    <div class="button-group">
                                    </div>
                                <?php elseif (strtolower($order['status']) === 'completed'): ?>
                                    <div class="button-group">
                                        <?php if (isset($order['has_rating']) && $order['has_rating']): ?>
                                            <button type="button" class="rated-btn" disabled>
                                                <i class="fas fa-check"></i> Rated
                                                <?php if (isset($order['rating_value'])): ?>
                                                    (<?php echo $order['rating_value']; ?> ★)
                                                <?php endif; ?>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" 
                                                onclick="openRatingModal(<?php echo $order['id']; ?>)" 
                                                class="rate-btn">
                                                <i class="fas fa-star"></i> Rate Order
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($order['status'] === 'Processing'): ?>
                                    <button type="button" onclick="confirmReceived(${order.id})" class="receive-btn">
                                        <i class="fas fa-check-circle"></i> Received
                                    </button>
                                    <button type="button" onclick="cancelOrder(${order.id})" class="cancel-btn">
                                        <i class="fas fa-times-circle"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    `).join('');
                    
                    ordersContent.innerHTML = ordersHtml;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('loadingIndicator').style.display = 'none';
                    ordersContent.innerHTML = `
                        <div class="error-banner">
                            <i class="fas fa-exclamation-circle"></i>
                            Failed to load orders. Please try again later.
                        </div>
                    `;
                });
        }

        // Initial fetch
        fetchOrders();

        // Refresh orders every 30 seconds
        setInterval(fetchOrders, 30000);

        function confirmReceived(orderId) {
            if (confirm('Have you received this order? This action cannot be undone.')) {
                updateOrderStatus(orderId, 'completed');
            }
        }

        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                updateOrderStatus(orderId, 'cancelled');
            }
        }

        function updateOrderStatus(orderId, status) {
            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'An error occurred. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        function openRatingModal(orderId) {
            // Implement your rating modal logic here
        }
    </script>
</body>
</html> 