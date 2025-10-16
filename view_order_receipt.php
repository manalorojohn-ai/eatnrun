<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get order details
$order_query = "SELECT o.*, 
                DATE_FORMAT(o.created_at, '%M %d, %Y %h:%i %p') as formatted_date,
                DATE_FORMAT(o.received_at, '%M %d, %Y %h:%i %p') as formatted_received_date
                FROM orders o 
                WHERE o.id = ? AND o.user_id = ?";

$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Get order items
$items_query = "SELECT oi.*, m.name, m.price 
                FROM order_items oi 
                JOIN menu_items m ON oi.menu_item_id = m.id 
                WHERE oi.order_id = ?";

$stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
$order_items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $order_items[] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #006C3B;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 2rem;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .receipt-header img {
            height: 60px;
            margin-bottom: 1rem;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
        }

        .info-value {
            font-size: 1rem;
            color: #333;
        }

        .items-table {
            width: 100%;
            margin-bottom: 2rem;
            border-collapse: collapse;
        }

        .items-table th {
            background-color: #f8f9fa;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #333;
        }

        .items-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
        }

        .total-section {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-end;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid #eee;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            width: 200px;
        }

        .total-label {
            color: #666;
        }

        .total-value {
            font-weight: 600;
            color: #333;
        }

        .grand-total {
            font-size: 1.2rem;
            color: var(--primary-color);
            font-weight: 700;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn-print {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-print:hover {
            background: #005530;
            transform: translateY(-2px);
        }

        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .receipt-container {
                box-shadow: none;
                max-width: 100%;
                padding: 1rem;
            }

            .action-buttons {
                display: none;
            }

            .status-badge {
                border: 1px solid currentColor;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <img src="assets/images/logo.png" alt="Eat&Run Logo">
            <h1>Order Receipt</h1>
            <div class="status-badge status-<?php echo strtolower($order['status']); ?>">
                <?php echo ucfirst($order['status']); ?>
            </div>
        </div>

        <div class="order-info">
            <div class="info-group">
                <div class="info-label">Order ID</div>
                <div class="info-value">#<?php echo $order_id; ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Order Date</div>
                <div class="info-value"><?php echo $order['formatted_date']; ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Delivery Address</div>
                <div class="info-value"><?php echo htmlspecialchars($order['delivery_address']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Contact Number</div>
                <div class="info-value"><?php echo htmlspecialchars($order['phone']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Payment Method</div>
                <div class="info-value"><?php echo strtoupper($order['payment_method']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Payment Status</div>
                <div class="info-value"><?php echo ucfirst($order['payment_status']); ?></div>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                        <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span class="total-value">₱<?php echo number_format($order['total_amount'] - $order['delivery_fee'], 2); ?></span>
            </div>
            <div class="total-row">
                <span class="total-label">Delivery Fee:</span>
                <span class="total-value">₱<?php echo number_format($order['delivery_fee'], 2); ?></span>
            </div>
            <div class="total-row grand-total">
                <span class="total-label">Total:</span>
                <span class="total-value">₱<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <div class="action-buttons">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            <a href="orders.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>

    <script>
        // Add print event listener
        window.addEventListener('beforeprint', function() {
            document.title = 'Order Receipt #<?php echo $order_id; ?>';
        });
    </script>
</body>
</html> 