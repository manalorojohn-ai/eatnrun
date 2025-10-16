<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? $_GET['id'] : null;

// Get order details
if ($order_id) {
    $query = "SELECT o.*, 
              (o.total_amount - o.delivery_fee) AS subtotal,
              u.full_name 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = ? AND o.user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    $order_result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($order_result);

    // Get order items
    if ($order) {
        $items_query = "SELECT od.*, m.name FROM order_details od 
                       JOIN menu_items m ON od.menu_item_id = m.id 
                       WHERE od.order_id = ?";
        $stmt = mysqli_prepare($conn, $items_query);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        $items_result = mysqli_stmt_get_result($stmt);
        
        $order_items = [];
        while ($item = mysqli_fetch_assoc($items_result)) {
            $order_items[] = $item;
        }
    }
}

// If order not found or doesn't belong to the user
if (!isset($order) || !$order) {
    header("Location: orders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - Eat&Run</title>
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

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .order-header {
            margin-bottom: 1.5rem;
        }

        .order-title {
            font-size: 1.75rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .order-items {
            background-color: var(--primary-light);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-group {
            margin-bottom: 1rem;
        }

        .detail-label {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 500;
        }

        .order-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
        }

        .summary-section {
            display: flex;
            gap: 2rem;
        }

        .summary-group {
            text-align: center;
        }

        .summary-label {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .summary-value {
            font-weight: 600;
            font-size: 1.25rem;
        }

        .status-indicator {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
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

        .receive-btn {
            padding: 0.8rem 1.5rem;
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
            margin-left: 1rem;
            animation: pulse 2s infinite;
        }

        .receive-btn:hover {
            background-color: #3d8b40;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(76, 175, 80, 0.4);
            animation: none;
        }

        .receive-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(76, 175, 80, 0.3);
        }

        .receive-btn i {
            font-size: 1.1rem;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            top: 30px;
            right: 30px;
            background: white;
            color: var(--text-color);
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 1000;
            transform: translateX(calc(100% + 20px));
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            max-width: 350px;
        }

        .toast.success {
            border-left: 4px solid var(--success-color);
        }

        .toast.error {
            border-left: 4px solid var(--danger-color);
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-icon {
            font-size: 1.3rem;
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
            font-size: 0.95rem;
        }

        .toast-close {
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 0.25rem;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="order-header">
            <h2 class="order-title">Order #<?php echo htmlspecialchars($order['id']); ?></h2>
        </div>

        <div class="order-items">
            <?php 
            if (!empty($order_items)) {
                foreach ($order_items as $item) {
                    echo htmlspecialchars($item['quantity']) . 'x ' . htmlspecialchars($item['name']) . '<br>';
                }
            } else {
                echo 'No items found';
            }
            ?>
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
            <div class="summary-section">
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

            <div style="display: flex; align-items: center;">
                <div id="statusIndicator" class="status-indicator status-<?php echo strtolower($order['status']); ?>">
                    <?php echo ucfirst($order['status']); ?>
                </div>
                
                <?php if ($order['status'] == 'Processing' || $order['status'] == 'Pending'): ?>
                <button id="receiveOrderBtn" class="receive-btn" data-order-id="<?php echo $order['id']; ?>">
                    <i class="fas fa-check-circle"></i> Mark as Received
                </button>
                <?php endif; ?>
            </div>
        </div>
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
                const statusIndicator = document.getElementById('statusIndicator');
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
                        statusIndicator.className = 'status-indicator status-completed';
                        statusIndicator.textContent = 'Completed';
                        
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