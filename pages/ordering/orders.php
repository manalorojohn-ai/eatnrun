<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$orders = [];
$error = null;

try {
    // Initial orders fetch
    $orders_query = "SELECT o.*, 
                    DATE_FORMAT(o.created_at, '%M %d, %Y %h:%i %p') as formatted_date,
                    DATE_FORMAT(o.received_at, '%M %d, %Y %h:%i %p') as formatted_received_date,
                    CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as has_rating,
                    r.rating as rating_value,
                    r.comment as rating_comment,
                    GROUP_CONCAT(
                        CONCAT(
                            od.quantity, 
                            'x ', 
                            mi.name,
                            ' (₱', 
                            FORMAT(od.price * od.quantity, 2),
                            ')'
                        ) 
                        ORDER BY mi.name 
                        SEPARATOR '<br>'
                    ) as order_items
                 FROM orders o 
                 LEFT JOIN ratings r ON o.id = r.order_id AND r.user_id = o.user_id
                    LEFT JOIN order_details od ON o.id = od.order_id
                    LEFT JOIN menu_items mi ON od.menu_item_id = mi.id
                 WHERE o.user_id = ?
                    GROUP BY o.id
                 ORDER BY o.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $orders_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Normalize status values to lowercase for consistent comparisons
        if (isset($row['status'])) {
            $row['status'] = strtolower($row['status']);
        }
        if (isset($row['payment_status'])) {
            $row['payment_status'] = strtolower($row['payment_status']);
        }
        // Add status badge color
        switch($row['status']) {
            case 'pending':
                $row['badge_color'] = 'warning';
                break;
            case 'processing':
                $row['badge_color'] = 'info';
                break;
            case 'completed':
                $row['badge_color'] = 'success';
                break;
            case 'cancelled':
                $row['badge_color'] = 'danger';
                break;
            default:
                $row['badge_color'] = 'secondary';
        }
        
        // Add payment status badge
        switch($row['payment_status']) {
            case 'paid':
                $row['payment_badge_color'] = 'success';
                break;
            case 'pending':
                $row['payment_badge_color'] = 'warning';
                break;
            case 'failed':
                $row['payment_badge_color'] = 'danger';
                break;
            default:
                $row['payment_badge_color'] = 'secondary';
        }
        
        $orders[] = $row;
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error fetching orders: " . $error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Eat&Run</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: #e8f5e9;
            --secondary: #FFD700;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #0dcaf0;
            --light-bg: #f8f9fa;
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Poppins', sans-serif;
            color: #2d3436;
            line-height: 1.6;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 3rem 0;
            margin-bottom: 2.5rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: url('assets/images/pattern.svg') repeat;
            opacity: 0.1;
            pointer-events: none;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            line-height: 1.6;
        }

        .order-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .order-header {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .order-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, rgba(255,255,255,0.1), transparent);
        }

        .order-date {
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            opacity: 0.9;
        }

        .order-date i {
            color: var(--secondary);
            font-size: 1.1rem;
        }

        .status-badges {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            transition: var(--transition);
        }

        .status-badge:hover {
            transform: translateY(-2px);
            background: rgba(255,255,255,0.25);
        }

        .status-badge i {
            font-size: 0.85rem;
        }

        .order-body {
            padding: 1.75rem;
        }

        .received-on {
            color: var(--success);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--primary-light);
            border-radius: var(--border-radius);
            font-weight: 500;
            border: 1px solid rgba(25, 135, 84, 0.1);
        }

        .received-on i {
            font-size: 1.25rem;
        }

        .items-ordered {
            background: var(--light-bg);
            padding: 1.25rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .items-ordered h6 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
        }

        .items-ordered i {
            color: var(--primary);
            font-size: 1.2rem;
        }

        .delivery-info, .payment-info {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: var(--light-bg);
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .delivery-info i, .payment-info i {
            color: var(--primary);
            font-size: 1.2rem;
            margin-top: 0.25rem;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-weight: 500;
            color: #2d3436;
        }

        .total-amount {
                font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-align: right;
            margin: 1.5rem 0;
            padding: 1rem;
            background: var(--primary-light);
            border-radius: var(--border-radius);
            border: 1px solid rgba(0, 108, 59, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            border: none;
            color: white;
            box-shadow: 0 4px 12px rgba(25, 135, 84, 0.2);
        }

        .btn-success:hover {
            background: #157347;
            box-shadow: 0 6px 16px rgba(25, 135, 84, 0.3);
        }

        .btn-danger {
            background: var(--danger);
            border: none;
            color: white;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }

        .btn-danger:hover {
            background: #bb2d3b;
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.3);
        }

        .btn-view {
            background: #0d6efd;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }

        .btn-view:hover {
            background: #0b5ed7;
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.3);
        }

        /* Add new rating button styles */
        .btn-rate {
            background: var(--primary);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.2);
        }

        .btn-rate:hover {
            background: var(--primary-dark);
            box-shadow: 0 6px 16px rgba(0, 108, 59, 0.3);
            color: white;
        }

        .rating-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .rating-badge i {
            color: var(--warning);
        }

        /* Modal Styles */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            border-bottom: none;
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header {
                padding: 2rem 0;
            }

            .page-title {
                font-size: 2rem;
            }

            .order-header {
                flex-direction: column;
            align-items: flex-start;
            }

            .status-badges {
                margin-top: 1rem;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn {
            width: 100%;
            justify-content: center;
            }

            .btn-primary {
                padding: 0.875rem 1.75rem;
                font-size: 1rem;
            }

            .btn-lg {
                padding: 1rem 2rem;
                font-size: 1.1rem;
                width: 100%; /* Full width on mobile */
                justify-content: center;
            }

            .btn-primary i {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .btn-primary {
                padding: 0.75rem 1.5rem;
                font-size: 0.95rem;
            }

            .btn-lg {
                padding: 0.875rem 1.75rem;
            }
        }

        /* Animations */
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
            animation: fadeInUp 0.6s ease forwards;
        }

        .order-card {
                opacity: 0;
            animation: fadeInUp 0.6s ease forwards;
        }

        .order-card:nth-child(1) { animation-delay: 0.1s; }
        .order-card:nth-child(2) { animation-delay: 0.2s; }
        .order-card:nth-child(3) { animation-delay: 0.3s; }
        .order-card:nth-child(4) { animation-delay: 0.4s; }
        .order-card:nth-child(5) { animation-delay: 0.5s; }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 108, 59, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 108, 59, 0.3);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            transition: all 0.5s ease;
            opacity: 0;
        }

        .btn-primary:hover::before {
            opacity: 1;
            transform: rotate(45deg) translate(50%, 50%);
        }

        .btn-primary i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .btn-primary:hover i {
            transform: translateX(3px);
        }

        .btn-primary span {
            position: relative;
            z-index: 1;
        }

        .btn-lg {
            padding: 1.25rem 2.5rem;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1 class="page-title animate-fade-in-up">My Orders</h1>
            <p class="page-subtitle animate-fade-in-up">Track and manage your orders with real-time updates and detailed information</p>
                    </div>
                    </div>

    <div class="container mb-5">
                <?php if (empty($orders)): ?>
            <div class="text-center py-5 animate-fade-in-up">
                    <div class="empty-state">
                    <i class="fas fa-shopping-bag fa-4x mb-4" style="color: var(--primary);"></i>
                    <h3 class="mb-3">No Orders Yet</h3>
                    <p class="text-muted mb-4">Start exploring our delicious menu and place your first order!</p>
                    <a href="menu.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-utensils"></i>
                        <span>Browse Our Menu</span>
                        </a>
                </div>
                    </div>
                <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                <div class="order-card" data-order-id="<?php echo $order['id']; ?>">
                    <div class="order-header">
                                        <div class="order-date">
                                            <i class="far fa-calendar-alt"></i>
                            <?php echo $order['formatted_date']; ?>
                                        </div>
                        <div class="status-badges">
                            <span class="status-badge">
                                <i class="fas fa-circle-notch <?php echo $order['status'] === 'pending' ? 'fa-spin' : ''; ?>"></i>
                                            <?php echo ucfirst($order['status']); ?>
                            </span>
                            <span class="status-badge">
                                <i class="fas fa-wallet"></i>
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                                        </div>
                                    </div>

                    <div class="order-body">
                        <?php if ($order['formatted_received_date']): ?>
                            <div class="received-on">
                                <i class="fas fa-check-circle"></i>
                                <span>Order received on <?php echo $order['formatted_received_date']; ?></span>
                                            </div>
                        <?php endif; ?>

                        <div class="items-ordered">
                            <h6><i class="fas fa-utensils"></i> Items Ordered</h6>
                            <div class="order-items-list">
                                <?php echo $order['order_items']; ?>
                                            </div>
                                            </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="delivery-info">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div class="info-content">
                                        <div class="info-label">Delivery Address</div>
                                        <div class="info-value"><?php echo htmlspecialchars($order['delivery_address']); ?></div>
                                            </div>
                                        </div>
                                            </div>
                            <div class="col-md-6">
                                <div class="payment-info">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <div class="info-content">
                                        <div class="info-label">Payment Method</div>
                                        <div class="info-value"><?php echo strtoupper($order['payment_method']); ?></div>
                                            </div>
                                            </div>
                                        </div>
                                            </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="total-amount">
                                Total: ₱<?php echo number_format($order['total_amount'], 2); ?>
                                                </div>
                            <div class="action-buttons">
                                <?php if (strtolower($order['status']) === 'processing'): ?>
                                    <button class="btn btn-success" onclick="handleReceiveOrder(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-check"></i> Mark as Received
                                    </button>
                                            <?php endif; ?>
                                <?php if (in_array(strtolower($order['status']), ['pending', 'processing'])): ?>
                                    <button class="btn btn-danger" onclick="handleCancelOrder(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-times"></i> Cancel Order
                                                    </button>
                                                <?php endif; ?>
                                <?php if (strtolower($order['status']) === 'completed' && !$order['has_rating']): ?>
                                    <a href="ratings.php?order_id=<?php echo $order['id']; ?>&menu_item_id=<?php echo $order['menu_item_id']; ?>" class="btn btn-rate">
                                        <i class="fas fa-star"></i> Rate Order
                                    </a>
                                <?php elseif (strtolower($order['status']) === 'completed' && $order['has_rating']): ?>
                                    <div class="rating-badge">
                                        <i class="fas fa-star"></i> 
                                        Rated: <?php echo $order['rating_value']; ?>/5
                                    </div>
                                            <?php endif; ?>
                                <button class="btn btn-view" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                <?php endif; ?>
            </div>

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle me-2"></i>Cancel Order
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
                <div class="modal-body">
                    <p class="mb-4">Please select a reason for cancellation:</p>
                    <div class="cancel-reasons">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="cancelReason" id="reason1" value="Changed my mind">
                            <label class="form-check-label" for="reason1">Changed my mind</label>
    </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="cancelReason" id="reason2" value="Ordered by mistake">
                            <label class="form-check-label" for="reason2">Ordered by mistake</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="cancelReason" id="reason3" value="Delivery time too long">
                            <label class="form-check-label" for="reason3">Delivery time too long</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="cancelReason" id="reasonOther" value="other">
                            <label class="form-check-label" for="reasonOther">Other reason</label>
                        </div>
                        <div class="form-group" id="otherReasonContainer" style="display: none;">
                            <textarea class="form-control" id="otherReasonText" rows="3" placeholder="Please specify your reason"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelBtn" disabled>
                        <i class="fas fa-times me-2"></i>Confirm Cancellation
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-alt me-2"></i>Order Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
                <div class="modal-body" id="orderDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
        </div>
                        <p class="mt-3">Loading order details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-success" id="downloadReceiptBtn">
                        <i class="fas fa-download me-2"></i>Download Receipt
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Bootstrap 5 JS - Required for navbar notification dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let lastUpdate = 0;
        const ordersContainer = document.getElementById('ordersContainer'); // This element doesn't exist in the new HTML
        // const loadingSpinner = document.getElementById('loadingSpinner'); // Removed loading spinner

        // Function to format currency
        function formatCurrency(amount) {
            return parseFloat(amount).toFixed(2);
        }

        // Function to create order card HTML
        function createOrderCard(order) {
            const status = (order.status || '').toString().toLowerCase();
            const paymentStatus = (order.payment_status || '').toString().toLowerCase();
            return `
                <div class="order-card" data-order-id="${order.id}">
                    <div class="order-header">
                        <div class="order-date">
                            <i class="far fa-calendar-alt"></i>
                            ${order.formatted_date}
                        </div>
                        <div class="status-badges">
                            <span class="status-badge">
                                <i class="fas fa-circle-notch ${status === 'pending' ? 'fa-spin' : ''}"></i>
                                ${status.charAt(0).toUpperCase() + status.slice(1)}
                            </span>
                            <span class="status-badge">
                                <i class="fas fa-wallet"></i>
                                ${paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1)}
                            </span>
                        </div>
                    </div>

                    <div class="order-body">
                        ${order.formatted_received_date ? `
                            <div class="received-on">
                                <i class="fas fa-check-circle"></i>
                                <span>Order received on ${order.formatted_received_date}</span>
                            </div>
                        ` : ''}

                        <div class="items-ordered">
                            <h6><i class="fas fa-utensils"></i> Items Ordered</h6>
                            <div class="order-items-list">
                                ${order.order_items}
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="delivery-info">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div class="info-content">
                                        <div class="info-label">Delivery Address</div>
                                        <div class="info-value">${order.delivery_address}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-info">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <div class="info-content">
                                        <div class="info-label">Payment Method</div>
                                        <div class="info-value">${order.payment_method.toUpperCase()}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="total-amount">
                                Total: ₱${formatCurrency(order.total_amount)}
                            </div>
                            ${['pending', 'processing'].includes(status) ? `
                                <div class="action-buttons">
                                    ${status === 'processing' ? `
                                        <button class="btn btn-success" onclick="handleReceiveOrder(${order.id})">
                                            <i class="fas fa-check"></i> Mark as Received
                                        </button>
                                    ` : ''}
                                    <button class="btn btn-danger" onclick="handleCancelOrder(${order.id})">
                                        <i class="fas fa-times"></i> Cancel Order
                                    </button>
                                </div>
                            ` : ''}
                            <div class="action-buttons">
                                ${status === 'completed' && !order.has_rating ? `
                                    <a href="ratings.php?order_id=${order.id}&menu_item_id=${order.menu_item_id}" class="btn btn-rate">
                                        <i class="fas fa-star"></i> Rate Order
                                    </a>
                                ` : ''}
                                ${status === 'completed' && order.has_rating ? `
                                    <div class="rating-badge">
                                        <i class="fas fa-star"></i> 
                                        Rated: ${order.rating_value}/5
                                    </div>
                                ` : ''}
                                <button class="btn btn-view" onclick="viewOrder(${order.id})">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Function to update orders
        async function updateOrders() {
            try {
                const response = await fetch(`get_orders_updates.php?last_update=${lastUpdate}`);
                const data = await response.json();

                if (data.success && data.orders.length > 0) {
                    lastUpdate = data.timestamp;
                    
                    data.orders.forEach(order => {
                        const existingOrder = document.querySelector(`[data-order-id="${order.id}"]`);
                        if (existingOrder) {
                            existingOrder.outerHTML = createOrderCard(order);
                } else {
                            // This part of the logic needs to be adapted to the new HTML structure
                            // For now, we'll just append if it's not in the container
                            // The new HTML doesn't have a specific container for orders,
                            // so we'll append directly to the body or a new container if needed.
                            // For simplicity, assuming the new HTML handles its own rendering.
                            // If the new HTML has a specific container for orders, this logic needs adjustment.
                            // Based on the new HTML, there's no explicit `ordersContainer` to append to.
                            // The orders are directly in the `mb-5` container.
                            // So, we'll just append the new order card to the container.
                            document.querySelector('.container.mb-5').insertAdjacentHTML('beforeend', createOrderCard(order));
                        }
                    });
                }
            } catch (error) {
                console.error('Error updating orders:', error);
            }
        }

        // Function to handle order reception
        async function handleReceiveOrder(orderId) {
            try {
                // loadingSpinner.style.display = 'block'; // Removed loading spinner
                const response = await fetch('update_order_status.php', {
                method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        action: 'receive'
                    })
                });

                const data = await response.json();
                if (data.success) {
                    await updateOrders();
                } else {
                    alert(data.message || 'Failed to update order status');
                }
                } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating the order');
            } finally {
                // loadingSpinner.style.display = 'none'; // Removed loading spinner
            }
        }

        // Function to handle order cancellation
        async function handleCancelOrder(orderId) {
            // Store the order ID globally
            window.currentOrderId = orderId;
            
            // Reset form
            const reasonInputs = document.querySelectorAll('input[name="cancelReason"]');
            const otherReasonText = document.getElementById('otherReasonText');
            const otherReasonContainer = document.getElementById('otherReasonContainer');
            const confirmCancelBtn = document.getElementById('confirmCancelBtn');
            
            // Reset all inputs
            reasonInputs.forEach(input => input.checked = false);
            otherReasonText.value = '';
            otherReasonContainer.style.display = 'none'; // Hide other reason container
            otherReasonText.required = false; // Ensure it's not required
            confirmCancelBtn.disabled = true;
            
            // Show the modal
            const cancelModal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
            cancelModal.show();
        }

        // Start real-time updates
        setInterval(updateOrders, 5000);

        // Initial update when the page loads
        document.addEventListener('DOMContentLoaded', updateOrders);

        document.addEventListener('DOMContentLoaded', function() {
            const confirmCancelBtn = document.getElementById('confirmCancelBtn');
            const otherReasonContainer = document.getElementById('otherReasonContainer');
            const reasonInputs = document.querySelectorAll('input[name="cancelReason"]');
            const otherReasonText = document.getElementById('otherReasonText');
            const cancelModal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));

            // Show/hide other reason textarea
            reasonInputs.forEach(input => {
                input.addEventListener('change', function() {
                    confirmCancelBtn.disabled = false;
                    if (this.value === 'other') {
                        otherReasonContainer.style.display = 'block';
                        otherReasonText.required = true;
                        confirmCancelBtn.disabled = !otherReasonText.value.trim();
                    } else {
                        otherReasonContainer.style.display = 'none';
                        otherReasonText.required = false;
                    }
                    });
                });

            // Enable/disable confirm button for other reason
            otherReasonText.addEventListener('input', function() {
                if (document.getElementById('reasonOther').checked) {
                    confirmCancelBtn.disabled = !this.value.trim();
                }
            });

            // Handle cancel confirmation
            confirmCancelBtn.addEventListener('click', async function() {
                const selectedReason = document.querySelector('input[name="cancelReason"]:checked');
                if (!selectedReason) return;

                let cancelReason = selectedReason.value;
                if (cancelReason === 'other') {
                    cancelReason = otherReasonText.value.trim();
                    if (!cancelReason) {
                        alert('Please specify your reason for cancellation');
                        return;
                    }
                }

                try {
                    // Disable the button and show loading state
                    confirmCancelBtn.disabled = true;
                    confirmCancelBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

                    const response = await fetch('update_order_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            order_id: window.currentOrderId,
                            action: 'cancel',
                            reason: cancelReason
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        // Hide the modal
                        cancelModal.hide();
                        
                        // Show success message
                        const successToast = document.createElement('div');
                        successToast.className = 'alert alert-success position-fixed bottom-0 end-0 m-3';
                        successToast.innerHTML = `
                            <i class="fas fa-check-circle me-2"></i>
                            Order cancelled successfully
                        `;
                        document.body.appendChild(successToast);
                        
                        // Remove the toast after 3 seconds
                        setTimeout(() => {
                            successToast.remove();
                        }, 3000);

                        // Update the orders display
                        await updateOrders();
                    } else {
                        throw new Error(data.message || 'Failed to cancel order');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the order: ' + error.message);
                } finally {
                    // Reset button state
                    confirmCancelBtn.disabled = false;
                    confirmCancelBtn.innerHTML = '<i class="fas fa-times me-2"></i>Confirm Cancellation';
                }
            });
        });

        function viewOrder(orderId) {
            const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
            const contentDiv = document.getElementById('orderDetailsContent');
            const downloadBtn = document.getElementById('downloadReceiptBtn');
            
            // Show loading state
            contentDiv.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading order details...</p>
                </div>
            `;
            
            // Show the modal
            modal.show();
            
            // Fetch order details
            fetch(`get_order_details.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        contentDiv.innerHTML = `
                            <div class="order-details-section">
                                <div class="detail-header">
                                    <i class="fas fa-info-circle"></i>
                                    Order Information
                                </div>
                                <div class="detail-content">
                                    <p><strong>Order ID:</strong> #${data.order.id}</p>
                                    <p><strong>Date:</strong> ${data.order.created_at}</p>
                                    <p><strong>Status:</strong> ${data.order.status}</p>
                                    <p><strong>Payment Status:</strong> ${data.order.payment_status}</p>
                            ${data.order.received_at ? `<p><strong>Received on:</strong> ${data.order.received_at}</p>` : ''}
                        </div>
            </div>
            
                    <div class="order-details-section">
                        <div class="detail-header">
                            <i class="fas fa-shopping-cart"></i>
                            Items Ordered
            </div>
                        <div class="detail-content">
                            ${data.order.items.map(item => `
                                <div class="d-flex justify-content-between mb-2">
                                    <span>${item.quantity}x ${item.name}</span>
                                    <span>₱${parseFloat(item.price * item.quantity).toFixed(2)}</span>
                                </div>
                            `).join('')}
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Subtotal:</strong>
                                <span>₱${parseFloat(data.order.subtotal).toFixed(2)}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong>Delivery Fee:</strong>
                                <span>₱${parseFloat(data.order.delivery_fee).toFixed(2)}</span>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <strong>Total:</strong>
                                <span class="text-primary fw-bold">₱${parseFloat(data.order.total_amount).toFixed(2)}</span>
                            </div>
        </div>
    </div>

                    <div class="order-details-section">
                        <div class="detail-header">
                            <i class="fas fa-map-marker-alt"></i>
                            Delivery Information
            </div>
                        <div class="detail-content">
                            <p><strong>Address:</strong> ${data.order.delivery_address}</p>
                            <p><strong>Contact:</strong> ${data.order.phone}</p>
                            ${data.order.notes ? `<p><strong>Notes:</strong> ${data.order.notes}</p>` : ''}
        </div>
        </div>
                `;
                        
                        // Update download link
                        downloadBtn.href = `generate_receipt.php?order_id=${orderId}`;
                        downloadBtn.style.display = 'inline-flex';
                    } else {
                        contentDiv.innerHTML = `
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Failed to load order details. Please try again.
    </div>
                        `;
                        downloadBtn.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            An error occurred while loading order details.
                        </div>
                    `;
                    downloadBtn.style.display = 'none';
                });
        }
    </script>
</body>
</html> 