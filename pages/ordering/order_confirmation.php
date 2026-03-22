<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: index.php");
    exit();
}

try {
    // Get order details
    $stmt = $conn->prepare("
        SELECT o.*, u.name as customer_name 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$_GET['order_id'], $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header("Location: index.php");
        exit();
    }

    // Get order items
    $stmt = $conn->prepare("
        SELECT oi.*, m.name, m.image_url
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$_GET['order_id']]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .success-message {
            text-align: center;
            margin-bottom: 40px;
        }

        .success-icon {
            color: #006C3B;
            font-size: 4em;
            margin-bottom: 20px;
        }

        .order-details {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 30px;
        }

        .section-title {
            color: #333;
            font-size: 1.2em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #333;
            font-weight: 600;
        }

        .payment-instructions {
            background: #f8f8f8;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .payment-instructions h3 {
            color: #006C3B;
            margin-bottom: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .action-button {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .primary-button {
            background: #006C3B;
            color: white;
        }

        .secondary-button {
            background: #f0f0f0;
            color: #333;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="confirmation-container">
        <div class="success-message">
            <i class="fas fa-check-circle success-icon"></i>
            <h1>Order Placed Successfully!</h1>
            <p>Thank you for your order. Your order number is #<?php echo $order['id']; ?></p>
        </div>

        <div class="order-details">
            <h2 class="section-title">Order Details</h2>
            <div class="detail-row">
                <span class="detail-label">Order Number:</span>
                <span class="detail-value">#<?php echo $order['id']; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Order Date:</span>
                <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value"><?php echo ucfirst($order['payment_method']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value">₱<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Delivery Address:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
            </div>

            <?php if ($order['payment_method'] === 'gcash' || $order['payment_method'] === 'downpayment'): ?>
            <div class="payment-instructions">
                <h3>Payment Instructions</h3>
                <p>Please send your payment to:</p>
                <ul>
                    <li>GCash Number: 09123456789</li>
                    <li>Account Name: Eat&Run</li>
                    <li>Amount: ₱<?php echo number_format($order['payment_method'] === 'downpayment' ? $order['total_amount'] / 2 : $order['total_amount'], 2); ?></li>
                </ul>
                <p>Please include your order number (#<?php echo $order['id']; ?>) in the payment reference.</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="orders.php" class="action-button primary-button">
                <i class="fas fa-clipboard-list"></i>
                View My Orders
            </a>
            <a href="menu.php" class="action-button secondary-button">
                <i class="fas fa-utensils"></i>
                Continue Shopping
            </a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 