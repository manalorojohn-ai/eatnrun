<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['id'];

// Get order details with all necessary information
$query = "SELECT o.*, u.full_name, u.phone 
        FROM orders o
          JOIN users u ON o.user_id = u.id
          WHERE o.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

    if (!$order) {
        header("Location: orders.php");
        exit();
    }

    // Get order items
$items_query = "SELECT od.*, mi.name, mi.image_path, mi.price
                FROM order_details od
                JOIN menu_items mi ON od.menu_item_id = mi.id
                WHERE od.order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
$items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
}

// Update order status
if (isset($_POST['status']) && isset($_POST['order_id'])) {
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $update_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    
    $update_query = "UPDATE orders SET status = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "si", $new_status, $update_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        // Add to order logs
        $log_query = "INSERT INTO order_logs (order_id, user_id, action, details) 
                      VALUES (?, ?, 'status_update', ?)";
        $log_stmt = mysqli_prepare($conn, $log_query);
        $log_details = "Status updated to " . ucfirst($new_status);
        mysqli_stmt_bind_param($log_stmt, "iis", $order_id, $_SESSION['user_id'], $log_details);
        mysqli_stmt_execute($log_stmt);
        
        header("Location: order-details.php?id=" . $order_id . "&status=updated");
        exit();
    }
}

$status_updated = isset($_GET['status']) && $_GET['status'] === 'updated';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
    <script>
        const ws = new WebSocket('ws://localhost:8080');
        
        function sendNotification(orderId, status, username) {
            const notification = {
                type: 'order_status',
                orderId: orderId,
                status: status,
                username: username,
                timestamp: new Date().toISOString()
            };
            ws.send(JSON.stringify(notification));
        }
    </script>
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: #e6f4ef;
            --bg-color: #f8f9fa;
            --text-color: #2D3748;
            --border-color: #E2E8F0;
            --success: #0D9488;
            --warning: #F59E0B;
            --danger: #EF4444;
            --info: #3B82F6;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            --hover-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .admin-content {
            margin-left: 240px;
            padding: 2rem;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            width: calc(100% - 240px);
        }

        .order-details {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            max-width: 1000px;
            width: 100%;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            margin: 0 auto;
        }

        .order-details:hover {
            box-shadow: var(--hover-shadow);
        }

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .loading.show {
            opacity: 1;
            pointer-events: all;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--primary-light);
        }

        .header h1 {
            font-size: 2.2rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header h1 i {
            color: var(--primary);
            font-size: 2rem;
        }

        .back-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.9rem 2rem;
            border-radius: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 500;
            transition: var(--transition);
            font-size: 1rem;
            box-shadow: 0 4px 8px rgba(0, 108, 59, 0.15);
        }

        .back-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 108, 59, 0.25);
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.8rem;
            margin-bottom: 3rem;
            padding: 2.5rem;
            background: var(--primary-light);
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .info-group {
            background: white;
            padding: 1.8rem;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            transform: translateY(0);
        }

        .info-group:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .info-label {
            font-size: 0.95rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .info-label i {
            color: var(--primary);
            font-size: 1.2rem;
        }

        .info-value {
            font-size: 1.3rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 3rem;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            background: white;
        }

        .items-table th {
            background: var(--primary-light);
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            padding: 1.5rem;
        }

        .items-table td {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .items-table tbody tr {
            transition: var(--transition);
        }

        .items-table tbody tr:hover {
            background: var(--primary-light);
            transform: scale(1.01);
        }

        .total-section {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            margin-top: 3rem;
            box-shadow: var(--card-shadow);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .total-row:last-child {
            border-bottom: none;
            padding-top: 1.8rem;
        }

        .total-label {
            color: #666;
            font-size: 1.1rem;
        }

        .total-value {
            font-weight: 600;
            font-size: 1.3rem;
            color: var(--text-color);
        }

        .grand-total {
            color: var(--primary);
            font-size: 1.6rem;
        }

        .order-status {
            margin: 3rem 0;
            padding: 2.5rem;
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }

        .order-status h3 {
            color: var(--text-color);
            margin-bottom: 1.8rem;
            font-size: 1.4rem;
        }

        .status-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .status-choice {
            padding: 1.2rem;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .status-choice:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .status-choice.pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .status-choice.processing {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .status-choice.completed {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-choice.cancelled {
            background: #FEE2E2;
            color: #991B1B;
        }

        .status-choice.active {
            outline: 3px solid currentColor;
            outline-offset: -3px;
            font-weight: 600;
            transform: scale(1.05);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge i {
            font-size: 0.7rem;
        }
        
        .status-badge.status-paid {
            background-color: #D1FAE5;
            color: #065F46;
        }
        
        .status-badge.status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }
        
        .status-badge.status-failed {
            background-color: #FEE2E2;
            color: #991B1B;
        }
        
        .status-badge.status-refunded {
            background-color: #E0E7FF;
            color: #3730A3;
        }

        .item-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .item-image:hover {
            transform: scale(1.15);
        }

        /* Animation Keyframes */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }

        @media (max-width: 768px) {
            .admin-content {
                margin-left: 0;
                padding: 1.5rem;
                width: 100%;
            }

            .order-details {
                padding: 1.8rem;
                border-radius: 16px;
            }

            .header {
                flex-direction: column;
                gap: 1.5rem;
                align-items: flex-start;
            }

            .order-info {
                grid-template-columns: 1fr;
                padding: 1.8rem;
            }

            .items-table {
                display: block;
                overflow-x: auto;
            }

            .status-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin-navbar.php'; ?>

    <div class="loading">
        <div class="loading-spinner"></div>
    </div>

    <div class="admin-content">
        <div class="order-details animate-fade-in-up">
            <div class="header">
                <h1>
                    <i class="fas fa-shopping-bag"></i>
                    Order #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?>
                </h1>
                <a href="orders.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Orders
                </a>
            </div>

            <div class="order-info animate-fade-in-up delay-1">
                <div class="info-group">
                    <div class="info-label">
                        <i class="far fa-user"></i>
                        Customer Name
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($order['full_name']); ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-phone"></i>
                        Contact Number
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-map-marker-alt"></i>
                        Delivery Address
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($order['delivery_address']); ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-credit-card"></i>
                        Payment Method
                    </div>
                    <div class="info-value"><?php echo ucfirst($order['payment_method']); ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-money-check"></i>
                        Payment Status
                    </div>
                    <div class="info-value">
                        <span class="status-badge status-<?php echo strtolower($order['payment_status']); ?>">
                            <i class="fas fa-circle"></i>
                            <?php echo $order['payment_status']; ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($order['special_instructions'])): ?>
                <div class="info-group">
                    <div class="info-label">
                        <i class="fas fa-comment-alt"></i>
                        Special Instructions
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($order['special_instructions']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="items-section animate-fade-in-up delay-2">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Image</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        foreach ($items as $item): 
                            $item_total = $item['price'] * $item['quantity'];
                            $subtotal += $item_total;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td>
                                <?php if (!empty($item['image_path'])): ?>
                                <img src="../uploads/menu/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="item-image">
                                <?php else: ?>
                                <div style="width: 70px; height: 70px; background: #f3f4f6; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image" style="color: #aaa;"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><strong>₱<?php echo number_format($item_total, 2); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="total-section animate-fade-in-up delay-3">
                <div class="total-row">
                    <span class="total-label">Subtotal</span>
                    <span class="total-value">₱<?php echo number_format($order['subtotal'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span class="total-label">Delivery Fee</span>
                    <span class="total-value">₱<?php echo number_format($order['delivery_fee'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span class="total-label">Grand Total</span>
                    <span class="total-value grand-total">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>

            <div class="order-status animate-fade-in-up delay-4">
                <h3>Update Order Status</h3>
                <form method="POST" class="status-options">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <?php
                    $statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];
                    foreach ($statuses as $status):
                        $isActive = $order['status'] === $status;
                    ?>
                    <button type="submit" 
                            name="status" 
                            value="<?php echo $status; ?>" 
                            class="status-choice <?php echo strtolower($status); ?> <?php echo $isActive ? 'active' : ''; ?>">
                        <i class="fas fa-<?php 
                            echo $status === 'Pending' ? 'clock' : 
                                ($status === 'Processing' ? 'spinner' : 
                                ($status === 'Completed' ? 'check-circle' : 'times-circle')); 
                        ?>"></i>
                        <?php echo $status; ?>
                    </button>
                    <?php endforeach; ?>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate the main content on page load
            setTimeout(() => {
                document.querySelector('.order-details').classList.add('animate-fade-in-up');
                
                // Animate the sections sequentially
                const sections = [
                    '.order-info',
                    '.items-section',
                    '.total-section',
                    '.order-status'
                ];
                
                sections.forEach((selector, index) => {
                    setTimeout(() => {
                        document.querySelector(selector).classList.add('animate-fade-in-up');
                    }, 100 + (index * 150));
                });
            }, 100);
            
            // Show loading state
            const loading = document.querySelector('.loading');
            loading.classList.remove('show');
            
            // Show loading when form is submitted
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', () => {
                    loading.classList.add('show');
                });
            });

            // Add smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });

            // Add animation for status updates
            const statusButtons = document.querySelectorAll('.status-choice');
            statusButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'translateY(-1px)';
                    }, 100);
                });
            });

            // Add hover effect for back button
            const backBtn = document.querySelector('.back-btn');
            backBtn.addEventListener('mouseenter', () => {
                backBtn.style.transform = 'translateX(-5px)';
            });
            backBtn.addEventListener('mouseleave', () => {
                backBtn.style.transform = 'translateX(0)';
            });

            // Animate items table rows
            const tableRows = document.querySelectorAll('.items-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.animation = `fadeIn 0.3s ease forwards ${0.3 + index * 0.1}s`;
            });

            // WebSocket connection handling
            ws.onopen = () => {
                console.log('Connected to WebSocket server');
            };

            ws.onclose = () => {
                console.log('Disconnected from WebSocket server');
                // Attempt to reconnect
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            };

            ws.onerror = (error) => {
                console.error('WebSocket error:', error);
            };

            ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'order_status' && data.orderId === <?php echo $order_id; ?>) {
                    window.location.reload();
                }
            };

            // Show success message if status was updated
            <?php if ($status_updated): ?>
                const successMessage = document.createElement('div');
                successMessage.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #D1FAE5;
                    color: #065F46;
                    padding: 1rem 1.5rem;
                    border-radius: 12px;
                    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
                    display: flex;
                    align-items: center;
                    gap: 0.8rem;
                    z-index: 1000;
                    animation: slideIn 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
                    font-weight: 500;
                `;
                successMessage.innerHTML = `
                    <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
                    Order status updated successfully
                `;
                document.body.appendChild(successMessage);
                setTimeout(() => {
                    successMessage.style.animation = 'fadeOut 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards';
                    setTimeout(() => successMessage.remove(), 400);
                }, 3000);
            <?php endif; ?>

            // Add animation keyframes
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateX(50px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                @keyframes fadeOut {
                    from {
                        opacity: 1;
                        transform: translateX(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateX(50px);
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html> 