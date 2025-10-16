<?php
require_once 'config/db.php';

if (!isset($_GET['order_id'])) {
    die('Order ID is required');
}

$order_id = mysqli_real_escape_string($conn, $_GET['order_id']);

// Get order details with the correct column names from your database
$order_query = "SELECT o.*, 
                COALESCE(o.full_name, u.full_name) as customer_name,
                COALESCE(o.email, u.email) as customer_email,
                COALESCE(o.phone, u.phone) as customer_phone,
                o.delivery_address,
                o.payment_method,
                o.status,
                o.created_at,
                o.delivery_notes,
                o.subtotal,
                o.delivery_fee,
                o.total_amount
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = ?";

$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    die('Order not found');
}

// Get order items
$items_query = "SELECT oi.*, mi.name, mi.price
                FROM order_items oi
                JOIN menu_items mi ON oi.menu_item_id = mi.id
                WHERE oi.order_id = ?";

$stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);

$items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt #<?php echo $order_id; ?> - Eat&Run</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .container {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 15px;
            }
        }

        .receipt {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background: white;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #006C3B;
        }

        .receipt-header img {
            max-width: 150px;
            margin-bottom: 10px;
        }

        .receipt-body {
            margin-bottom: 30px;
        }

        .receipt-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .receipt-table th {
            background-color: #f8f9fa;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }

        .print-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #006C3B;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .print-button:hover {
            background: #005530;
        }

        .order-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="receipt">
            <div class="receipt-header">
                <img src="assets/images/logo.png" alt="Eat&Run Logo">
                <h2>Eat&Run</h2>
                <p>Your Trusted Food Delivery Partner</p>
            </div>

            <div class="receipt-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Order Information</h5>
                        <p><strong>Order #:</strong> <?php echo $order_id; ?></p>
                        <p><strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </p>
                        <p><strong>Payment Method:</strong> <?php echo strtoupper($order['payment_method']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Customer Information</h5>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                        <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                    </div>
                </div>

                <h5>Order Details</h5>
                <table class="table receipt-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end">₱<?php echo number_format($item['price'], 2); ?></td>
                                <td class="text-end">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                            <td class="text-end">₱<?php echo number_format($order['subtotal'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Delivery Fee</strong></td>
                            <td class="text-end">₱<?php echo number_format($order['delivery_fee'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total Amount</strong></td>
                            <td class="text-end"><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>

                <?php if (!empty($order['delivery_notes'])): ?>
                    <div class="mt-4">
                        <h5>Delivery Notes</h5>
                        <ul style="margin-bottom:0;">
                        <?php
                        $notes = preg_split('/\r\n|\r|\n/', $order['delivery_notes']);
                        foreach ($notes as $note) {
                            if (trim($note)) {
                                echo '<li>' . htmlspecialchars($note) . '</li>';
                            }
                        }
                        ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <div class="receipt-footer">
                <p>Thank you for choosing Eat&Run!</p>
                <p>For any concerns, please contact us at support@eatandrun.com</p>
                <small>This is a computer-generated receipt. No signature required.</small>
            </div>
        </div>
    </div>

    <button onclick="window.print()" class="print-button no-print">
        <i class="fas fa-print"></i> Print Receipt
    </button>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html> 