<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Verify order belongs to user
$order_query = "SELECT o.*, u.full_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ? AND o.user_id = ?";
$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header("Location: index.php");
    exit();
}

// Mark the order placement notification as read to prevent the toast from showing
$notification_message = "Your order #{$order_id} has been placed successfully!";
$mark_read_query = "UPDATE notifications SET is_read = 1 
                   WHERE user_id = ? AND message = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $mark_read_query);
mysqli_stmt_bind_param($stmt, "is", $user_id, $notification_message);
mysqli_stmt_execute($stmt);

// Set a session flag to prevent showing this notification as a toast
$_SESSION['order_placed_'.$order_id] = true;

// Clear any session messages since we're using notifications
unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/notifications.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .success-container {
            width: 100%;
            max-width: 600px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            padding: 3rem 2rem;
            text-align: center;
        }

        .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e8f5e9;
            margin: 0 auto 2rem;
        }

        .checkmark i {
            color: #006C3B;
            font-size: 40px;
        }

        .success-container h1 {
            color: #006C3B;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .success-message {
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .success-message p {
            margin: 0.5rem 0;
            color: #333;
        }

        .order-number {
            font-size: 1.2rem;
            font-weight: 600;
            color: #006C3B;
            display: block;
            margin: 1rem 0;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            background: #006C3B;
            color: white;
            border: 2px solid #006C3B;
        }

        .btn:hover {
            background: #005530;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.2);
        }

        @media (max-width: 768px) {
            .success-container {
                padding: 2rem 1.5rem;
            }

            .success-container h1 {
                font-size: 1.75rem;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="success-container">
            <div class="checkmark">
                <i class="fas fa-check"></i>
            </div>
            <h1>Order Placed Successfully!</h1>
            <div class="success-message">
                <p>Thank you for your order, <?php echo htmlspecialchars($order['full_name']); ?>!</p>
                <span class="order-number">Order #<?php echo $order_id; ?></span>
                <p>You can track your order status in the My Orders section.</p>
            </div>
            <a href="orders.php" class="btn">View My Orders</a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="assets/js/notifications-handler.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure any notification toasts for this order are removed
        const toasts = document.querySelectorAll('.notification-toast');
        toasts.forEach(toast => {
            const content = toast.querySelector('.notification-toast-content');
            if (content && content.textContent.includes('order #<?php echo $order_id; ?>')) {
                toast.remove();
            }
        });
        
        // Store in localStorage that we've already seen this notification
        const orderMessage = "Your order #<?php echo $order_id; ?> has been placed successfully!";
        const seenNotifications = JSON.parse(localStorage.getItem('seenNotifications') || '[]');
        if (!seenNotifications.includes(orderMessage)) {
            seenNotifications.push(orderMessage);
            localStorage.setItem('seenNotifications', JSON.stringify(seenNotifications));
        }
    });
    </script>
</body>
</html> 