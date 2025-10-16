<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$order_id = intval($_GET['id']);

// Get order details with all necessary information
$query = "SELECT o.*, 
          u.email as user_email,
          COALESCE(o.full_name, u.full_name) as display_name,
          COALESCE(o.phone, u.phone) as display_phone,
          COALESCE(NULLIF(o.email, ''), u.email, 'N/A') as display_email,
          o.delivery_notes,
          o.payment_method,
          o.status,
          o.created_at,
          o.delivery_address
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.id
          WHERE o.id = ?";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    header('Location: orders.php');
exit();
}

// Get order items if they exist
$items_query = "SELECT oi.*, mi.name as item_name, mi.price as item_price 
                FROM order_items oi 
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id 
                WHERE oi.order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?php echo $order_id; ?> - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .main-content {
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 500;
            color: #333;
            margin: 0;
        }

        .back-button {
            text-decoration: none;
            color: #0d6efd;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .back-button:hover {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .info-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .info-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .info-row {
            margin-bottom: 1rem;
        }

        .info-label {
            font-weight: 600;
            color: #333;
            margin-right: 0.5rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-processing {
            background-color: #DBEAFE;
            color: #1E40AF;
        }

        .status-completed {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .status-cancelled {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .update-status {
            margin-top: 2rem;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .status-select {
            max-width: 200px;
            margin-right: 1rem;
        }

        .update-btn {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .update-btn:hover {
            background-color: #0b5ed7;
        }
    </style>
</head>
<body>
    <?php include 'admin-navbar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Order Details #<?php echo $order_id; ?></h1>
            <div class="d-flex gap-2">
                <a href="../print_receipt.php?order_id=<?php echo $order_id; ?>" class="btn btn-success" target="_blank">
                    <i class="fas fa-print me-2"></i>Print Receipt
                </a>
                <a href="orders.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Orders
                </a>
            </div>
        </div>

        <div class="info-card">
            <div class="row">
                <!-- Customer Information -->
                <div class="col-md-6">
                    <div class="info-section">
                        <h2 class="section-title">Customer Information</h2>
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <?php echo htmlspecialchars($order['display_name']); ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <?php echo htmlspecialchars($order['display_phone']); ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <?php echo htmlspecialchars($order['display_email']); ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <?php echo htmlspecialchars($order['delivery_address']); ?>
                        </div>
                    </div>
                </div>

                <!-- Order Information -->
                <div class="col-md-6">
                    <div class="info-section">
                        <h2 class="section-title">Order Information</h2>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payment Method:</span>
                            <?php echo ucfirst($order['payment_method']); ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Order Date:</span>
                            <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                        </div>
                        <?php if($order['delivery_notes']): ?>
                        <div class="info-row">
                            <span class="info-label">Delivery Notes:</span>
                            <?php echo htmlspecialchars($order['delivery_notes']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Status Section -->
        <div class="update-status">
            <h2 class="section-title">Update Status</h2>
            <div class="d-flex align-items-center">
                <select class="form-select status-select" id="orderStatus">
                    <option value="Pending" <?php echo strtolower($order['status']) === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Processing" <?php echo strtolower($order['status']) === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="Completed" <?php echo strtolower($order['status']) === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?php echo strtolower($order['status']) === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button class="update-btn" onclick="updateOrderStatus(<?php echo $order_id; ?>)">Update Status</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateOrderStatus(orderId) {
            const status = document.getElementById('orderStatus').value;
            
            fetch('../api/update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: status,
                    admin_id: <?php echo $_SESSION['user_id']; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the status badge
                    const statusBadge = document.querySelector('.status-badge');
                    statusBadge.className = `status-badge status-${status.toLowerCase()}`;
                    statusBadge.textContent = status;
                    
                    // Show success message
                    alert('Order status updated successfully');
                } else {
                    alert(data.message || 'Failed to update order status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update order status');
            });
        }
    </script>
</body>
</html> 