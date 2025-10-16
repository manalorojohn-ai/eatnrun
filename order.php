<?php
session_start();
require_once 'includes/connection.php';

// Initialize error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to handle database errors
function handleDatabaseError($conn, $error_message) {
    return "Database Error: " . mysqli_error($conn) . " - " . $error_message;
}

// Function to validate order ID
function validateOrderId($order_id) {
    return filter_var($order_id, FILTER_VALIDATE_INT) !== false && $order_id > 0;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get order item details with validation
if (isset($_GET['item']) && validateOrderId($_GET['item'])) {
    $order_id = mysqli_real_escape_string($conn, $_GET['item']);
    
    $query = "SELECT o.*, oi.*, m.name as menu_item_name, m.price as menu_item_price, 
                 m.image_url as menu_item_image,
                 m.description as menu_item_description, m.category_id,
                 c.name as category_name
          FROM orders o 
          JOIN order_items oi ON o.id = oi.order_id 
          JOIN menu_items m ON oi.menu_item_id = m.id 
          LEFT JOIN categories c ON m.category_id = c.id
          WHERE o.id = ? AND o.user_id = ?";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $_SESSION['user_id']);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = handleDatabaseError($conn, "Error fetching order details");
    } else {
        $result = mysqli_stmt_get_result($stmt);
        $order_item = mysqli_fetch_assoc($result);
        
        if (!$order_item) {
            $error = "Order not found or unauthorized access";
        }
    }
} else {
    header("Location: my_orders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            min-height: 100vh;
            opacity: 0;
            animation: fadeInPage 0.6s ease-out forwards;
        }

        @keyframes fadeInPage {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .order-details {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(0);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            animation: slideUp 0.6s ease-out forwards;
            animation-delay: 0.3s;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .order-details:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(0, 108, 59, 0.1);
            animation: slideInDown 0.6s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .order-header h1 {
            color: #006C3B;
            font-size: 2rem;
            font-weight: 600;
            position: relative;
            padding-bottom: 8px;
        }

        .order-header h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #006C3B, #00A65A);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .order-header:hover h1::after {
            width: 60px;
        }

        .order-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            animation: fadeIn 0.6s ease-out;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 2px solid rgba(133, 100, 4, 0.2);
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
            border: 2px solid rgba(21, 87, 36, 0.2);
        }

        .item-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 24px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 108, 59, 0.1);
            opacity: 0;
            animation: slideIn 0.6s ease-out forwards;
            animation-delay: 0.6s;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .item-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .item-image {
            flex-shrink: 0;
            width: 140px;
            height: 140px;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            transition: all 0.4s ease;
        }

        .item-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(0, 108, 59, 0.1), transparent);
            z-index: 1;
            transition: opacity 0.3s ease;
        }

        .item-image:hover::before {
            opacity: 0;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .item-image:hover img {
            transform: scale(1.1) rotate(2deg);
        }

        .item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .item-name {
            font-size: 1.4rem;
            font-weight: 600;
            color: #006C3B;
            margin-bottom: 5px;
            transition: color 0.3s ease;
        }

        .item-card:hover .item-name {
            color: #00A65A;
        }

        .item-description {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .item-category {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background: rgba(0, 108, 59, 0.1);
            color: #006C3B;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .item-card:hover .item-category {
            background: rgba(0, 108, 59, 0.15);
            transform: translateX(5px);
        }

        .item-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #006C3B;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .item-quantity {
            font-size: 0.9rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .order-summary {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 16px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 108, 59, 0.1);
            opacity: 0;
            animation: fadeInUp 0.6s ease-out forwards;
            animation-delay: 0.9s;
        }

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

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 108, 59, 0.1);
            transition: all 0.3s ease;
        }

        .summary-row:hover {
            background: rgba(0, 108, 59, 0.02);
            transform: translateX(5px);
            padding-left: 10px;
            padding-right: 10px;
        }

        .summary-total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid rgba(0, 108, 59, 0.2);
            font-size: 1.3rem;
            font-weight: 600;
            color: #006C3B;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            background: #006C3B;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            margin-top: 30px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid #006C3B;
            opacity: 0;
            animation: fadeIn 0.6s ease-out forwards;
            animation-delay: 1.2s;
        }

        .back-button:hover {
            background: transparent;
            color: #006C3B;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.2);
        }

        .back-button i {
            margin-right: 8px;
            transition: transform 0.3s ease;
        }

        .back-button:hover i {
            transform: translateX(-5px);
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(153, 27, 27, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .error-message i {
            font-size: 1.5rem;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .order-details {
                margin: 20px;
                padding: 20px;
            }

            .item-card {
                flex-direction: column;
                gap: 15px;
            }

            .item-image {
                width: 100%;
                height: 200px;
            }

            .order-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .order-header h1::after {
                left: 50%;
                transform: translateX(-50%);
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="order-details">
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php else: ?>
            <div class="order-header">
                <h1>Order #<?php echo $order_item['order_id']; ?></h1>
                <span class="order-status status-<?php echo strtolower($order_item['status']); ?>">
                    <?php echo ucfirst($order_item['status']); ?>
                </span>
            </div>

            <div class="order-items">
                <div class="item-card">
                    <div class="item-image">
                        <?php 
                        $image_url = !empty($order_item['menu_item_image']) 
                            ? $order_item['menu_item_image']
                            : 'assets/images/default-food.png';
                        
                        $image_url = str_replace('\\', '/', $image_url);
                        $image_url = ltrim($image_url, '/');
                        
                        try {
                            if (!file_exists($image_url)) {
                                $image_url = 'assets/images/default-food.png';
                            }
                        } catch (Exception $e) {
                            $image_url = 'assets/images/default-food.png';
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($image_url); ?>" 
                             alt="<?php echo htmlspecialchars($order_item['menu_item_name']); ?>"
                             onerror="this.src='assets/images/default-food.png';"
                             loading="lazy">
                    </div>
                    <div class="item-details">
                        <div class="item-name"><?php echo htmlspecialchars($order_item['menu_item_name']); ?></div>
                        <div class="item-description"><?php echo htmlspecialchars($order_item['menu_item_description']); ?></div>
                        <div class="item-category">
                            <i class="fas fa-utensils"></i>
                            <span><?php echo htmlspecialchars($order_item['category_name']); ?></span>
                        </div>
                        <div class="item-price">
                            <i class="fas fa-tag"></i>
                            ₱<?php echo number_format($order_item['menu_item_price'], 2); ?>
                        </div>
                        <div class="item-quantity">
                            <i class="fas fa-shopping-basket"></i>
                            Quantity: <?php echo $order_item['quantity']; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>₱<?php echo number_format($order_item['menu_item_price'] * $order_item['quantity'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee:</span>
                    <span>₱<?php echo number_format($order_item['delivery_fee'], 2); ?></span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total:</span>
                    <span>₱<?php echo number_format($order_item['total_amount'], 2); ?></span>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="my_orders.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Orders
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle image loading with smooth transitions
        const images = document.querySelectorAll('.item-image img');
        images.forEach(img => {
            img.parentElement.classList.add('loading');
            
            img.onload = function() {
                this.parentElement.classList.remove('loading');
                this.style.opacity = '1';
            }
            
            img.onerror = function() {
                this.parentElement.classList.remove('loading');
                this.src = 'assets/images/default-food.png';
                this.style.opacity = '1';
            }

            // Set initial opacity
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.5s ease-in-out';
        });

        // Add hover effect for summary rows
        const summaryRows = document.querySelectorAll('.summary-row');
        summaryRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    });
    </script>
</body>
</html> 