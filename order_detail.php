<?php
session_start();
require_once 'config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable direct error output to page

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = mysqli_real_escape_string($conn, $_GET['id']);
$order = null;
$error = null;

// Get the order with error handling
try {
    // First check if we can connect to database
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Get the order details
    $order_query = "SELECT o.*, 
                      (o.total_amount - o.delivery_fee) AS subtotal,
                      GROUP_CONCAT(CONCAT(od.quantity, 'x ', m.name) SEPARATOR ', ') as items
                   FROM orders o 
                   LEFT JOIN order_details od ON o.id = od.order_id
                   LEFT JOIN menu_items m ON od.menu_item_id = m.id
                   WHERE o.id = ? AND o.user_id = ?
                   GROUP BY o.id";
    
    $stmt = mysqli_prepare($conn, $order_query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to execute statement: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new Exception("Failed to get result: " . mysqli_error($conn));
    }
    
    $order = mysqli_fetch_assoc($result);
    if (!$order) {
        throw new Exception("Order not found or does not belong to you.");
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    error_log("Error fetching order: " . $e->getMessage());
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo htmlspecialchars($order_id); ?> - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-light: #e8f5e9;
            --primary-dark: #005530;
            --text-color: #2d3436;
            --text-light: #666;
            --border-color: #e0e0e0;
            --success-color: #4CAF50;
            --warning-color: #FFA000;
            --processing-color: #1976d2;
            --danger-color: #f44336;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 8px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 16px rgba(0,0,0,0.1);
            --border-radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color);
            min-height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .page-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
        }

        .order-container {
            max-width: 1000px;
            width: 100%;
            margin: 1.5rem auto;
            padding: 0 1rem;
            animation: fadeIn 0.5s ease forwards;
            flex: 1 0 auto;
        }

        .order-header {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            border-left: 5px solid var(--primary-color);
            animation: slideDown 0.6s forwards;
        }

        .order-title {
            font-size: 1.75rem;
            color: var(--primary-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .order-title i {
            font-size: 1.5rem;
        }

        .order-date {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .order-content {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.5s ease forwards;
        }

        .order-items {
            padding: 1.5rem;
            background-color: var(--primary-light);
            font-size: 1.2rem;
            line-height: 1.6;
            color: var(--text-color);
            border-bottom: 1px solid var(--border-color);
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            padding: 1.5rem;
            gap: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-group {
            position: relative;
        }

        .detail-label {
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.4rem;
        }

        .detail-value {
            color: var(--text-color);
            font-weight: 500;
            font-size: 1.1rem;
        }

        .order-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            padding: 1.5rem;
            background-color: #f8f9fa;
            align-items: center;
        }

        .summary-group {
            text-align: center;
        }

        .summary-label {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.4rem;
        }

        .summary-value {
            font-weight: 600;
            font-size: 1.3rem;
            color: var(--primary-color);
        }

        .status-bar {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            animation: fadeInUp 0.5s ease forwards;
            animation-delay: 0.1s;
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-label {
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-value {
            padding: 0.7rem 1.5rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .status-value i {
            font-size: 1.1rem;
        }

        .status-pending {
            background-color: #fff3e0;
            color: var(--warning-color);
        }

        .status-processing {
            background-color: #e3f2fd;
            color: var(--processing-color);
        }

        .status-completed {
            background-color: #e8f5e9;
            color: var(--success-color);
        }

        .status-cancelled {
            background-color: #ffebee;
            color: var(--danger-color);
        }

        .receive-btn {
            padding: 0.75rem 1.5rem;
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
            animation: pulse 2s infinite;
        }

        .receive-btn:hover {
            background-color: #3d8b40;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(76, 175, 80, 0.5);
            animation: none;
        }

        .receive-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(76, 175, 80, 0.3);
        }

        .receive-btn i {
            font-size: 1.2rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.8rem 1.5rem;
            background-color: var(--text-light);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .back-btn:hover {
            background-color: var(--text-color);
            transform: translateX(-5px);
        }

        /* Toast notification styles */
        .toast {
            position: fixed;
            top: 30px;
            right: 30px;
            background: white;
            color: var(--text-color);
            padding: 1.2rem 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 1000;
            transform: translateX(calc(100% + 20px));
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            max-width: 400px;
            min-width: 300px;
        }

        .toast.success {
            border-left: 5px solid var(--success-color);
        }

        .toast.error {
            border-left: 5px solid var(--danger-color);
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-icon {
            font-size: 1.5rem;
        }

        .toast-icon.success {
            color: var(--success-color);
        }

        .toast-icon.error {
            color: var(--danger-color);
        }

        .toast-content {
            flex: 1;
        }

        .toast-message {
            margin: 0;
            font-size: 1rem;
            line-height: 1.4;
        }

        .toast-close {
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.3rem;
            padding: 0.25rem;
            transition: color 0.3s ease;
        }
        
        .toast-close:hover {
            color: var(--text-color);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from { 
                opacity: 0;
                transform: translateY(-20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .order-summary {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .summary-group {
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .status-bar {
                flex-direction: column;
                gap: 1.5rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <?php include 'navbar.php'; ?>

        <div class="order-container">
            <?php if (isset($error)): ?>
                <div class="error-banner">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php elseif ($order): ?>
                <a href="orders.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to All Orders
                </a>

                <div class="order-header">
                    <div class="order-title">
                        <i class="fas fa-receipt"></i>
                        Order #<?php echo htmlspecialchars($order['id']); ?>
                    </div>
                    <div class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></div>
                </div>

                <div class="status-bar">
                    <div class="status-info">
                        <div class="status-label">Status:</div>
                        <div class="status-value status-<?php echo strtolower($order['status']); ?>">
                            <i class="fas <?php 
                                switch(strtolower($order['status'])) {
                                    case 'pending': echo 'fa-clock'; break;
                                    case 'processing': echo 'fa-spinner fa-spin'; break;
                                    case 'completed': echo 'fa-check-circle'; break;
                                    case 'cancelled': echo 'fa-times-circle'; break;
                                    default: echo 'fa-info-circle';
                                }
                            ?>"></i>
                            <?php echo ucfirst($order['status']); ?>
                        </div>
                    </div>
                    
                    <?php if ($order['status'] == 'Processing' || $order['status'] == 'Pending'): ?>
                    <button id="receiveOrderBtn" class="receive-btn" data-order-id="<?php echo $order['id']; ?>">
                        <i class="fas fa-check-circle"></i> Mark as Received
                    </button>
                    <?php endif; ?>
                </div>

                <div class="order-content">
                    <div class="order-items">
                        <?php echo htmlspecialchars($order['items'] ?? 'No items found'); ?>
                    </div>

                    <div class="order-details">
                        <div class="detail-group">
                            <div class="detail-label">Delivery Address</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['delivery_address'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Contact</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value"><?php echo ucfirst(htmlspecialchars($order['payment_method'] ?? 'N/A')); ?></div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value" id="paymentStatus"><?php echo ucfirst(htmlspecialchars($order['payment_status'] ?? 'Pending')); ?></div>
                        </div>
                    </div>

                    <div class="order-summary">
                        <div class="summary-group">
                            <div class="summary-label">Subtotal</div>
                            <div class="summary-value">₱<?php echo number_format($order['subtotal'] ?? ($order['total_amount'] - $order['delivery_fee']), 2); ?></div>
                        </div>
                        <div class="summary-group">
                            <div class="summary-label">Delivery Fee</div>
                            <div class="summary-value">₱<?php echo number_format($order['delivery_fee'] ?? 0, 2); ?></div>
                        </div>
                        <div class="summary-group">
                            <div class="summary-label">Total</div>
                            <div class="summary-value">₱<?php echo number_format($order['total_amount'] ?? 0, 2); ?></div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="error-banner">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>Order not found or you do not have permission to view it.</div>
                </div>
                <a href="orders.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to All Orders
                </a>
            <?php endif; ?>
        </div>

        <!-- Toast Notification -->
        <div id="toast" class="toast">
            <div id="toastIcon" class="toast-icon"></div>
            <div class="toast-content">
                <p id="toastMessage" class="toast-message"></p>
            </div>
            <div class="toast-close" onclick="hideToast()">×</div>
        </div>

        <?php include 'footer.php'; ?>
    </div>

    <script>
        // Toast notification functions
        const toast = document.getElementById('toast');
        const toastIcon = document.getElementById('toastIcon');
        const toastMessage = document.getElementById('toastMessage');

        function showToast(message, type = 'success') {
            // Clear any existing timeout
            if (window.toastTimeout) {
                clearTimeout(window.toastTimeout);
            }
            
            toastMessage.textContent = message;
            toast.className = 'toast ' + type;
            toastIcon.className = 'toast-icon ' + type;
            
            if (type === 'success') {
                toastIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
            } else {
                toastIcon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
            }
            
            // Remove show class first to reset animation
            toast.classList.remove('show');
            
            // Force browser reflow
            void toast.offsetWidth;
            
            // Add show class after a short delay
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // Auto hide after 5 seconds
            window.toastTimeout = setTimeout(hideToast, 5000);
        }

        function hideToast() {
            toast.classList.remove('show');
        }

        // Handle "Mark as Received" button click
        const receiveOrderBtn = document.getElementById('receiveOrderBtn');
        if (receiveOrderBtn) {
            receiveOrderBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const orderId = this.getAttribute('data-order-id');
                const statusValue = document.querySelector('.status-value');
                const paymentStatus = document.getElementById('paymentStatus');
                
                // Disable button to prevent multiple clicks
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Send AJAX request to update order status
                fetch('update_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ order_id: orderId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the UI
                        statusValue.className = 'status-value status-completed';
                        statusValue.innerHTML = '<i class="fas fa-check-circle"></i> Completed';
                        
                        // Update payment status
                        if (paymentStatus) {
                            paymentStatus.textContent = 'Paid';
                        }
                        
                        // Remove button with animation
                        this.style.transition = 'all 0.3s ease';
                        this.style.transform = 'scale(0)';
                        this.style.opacity = '0';
                        
                        setTimeout(() => {
                            this.remove();
                        }, 300);
                        
                        // Show success message
                        showToast('Order marked as received and payment completed!', 'success');
                    } else {
                        // Re-enable the button
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-check-circle"></i> Mark as Received';
                        
                        // Show error message
                        showToast(data.message || 'Failed to update order status.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Re-enable the button
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-check-circle"></i> Mark as Received';
                    
                    // Show error message
                    showToast('An error occurred. Please try again.', 'error');
                });
            });
        }
    </script>
</body>
</html> 