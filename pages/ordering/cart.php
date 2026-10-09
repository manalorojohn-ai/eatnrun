<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/includes/helpers/init_session.php';
require_once dirname(__DIR__, 2) . '/config/database/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = [];
$total = 0;

// Get cart items from database
$cart_query = "SELECT c.id as cart_id, c.quantity, c.menu_item_id, m.name, m.price, m.image,
               (m.price * c.quantity) as subtotal 
               FROM cart c 
               JOIN menu_items m ON c.menu_item_id = m.id 
               WHERE c.user_id = ?";

try {
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare($cart_query);
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($conn instanceof mysqli) {
        $stmt = $conn->prepare($cart_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($item = $result->fetch_assoc()) {
            $cart_items[] = $item;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Cart load error: " . $e->getMessage());
}

// Calculate total
foreach ($cart_items as $item) {
    $total += $item['subtotal'];
}

// Handle quantity updates & deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $cart_id = intval($_POST['cart_id']);
        $quantity = max(1, intval($_POST['quantity']));

        $update_query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare($update_query);
            $stmt->execute([$quantity, $cart_id, $user_id]);
        } elseif ($conn instanceof mysqli) {
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif (isset($_POST['remove_item'])) {
        $cart_id = intval($_POST['cart_id']);
        $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare($delete_query);
            $stmt->execute([$cart_id, $user_id]);
        } elseif ($conn instanceof mysqli) {
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: cart");
    exit();
}

$page_title = "My Shopping Cart";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Eat&Run</title>
    
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Navbar CSS explicitly included for 100% styled navbar -->
    <link rel="stylesheet" href="assets/css/navbar-enhanced.css">

    <style>
        :root {
            --primary: #006C3B;
            --primary-rgb: 0, 108, 59;
            --primary-dark: #00502b;
            --primary-light: #e6f4ea;
            --accent: #FFB800;
            --bg-page: #f8fafc;
            --surface: #ffffff;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --border-subtle: #e2e8f0;
            --radius-xl: 24px;
            --radius-lg: 18px;
            --radius-md: 12px;
            --radius-pill: 9999px;
            --shadow-card: 0 10px 30px -5px rgba(15, 23, 42, 0.07), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
            --shadow-hover: 0 16px 36px -6px rgba(0, 108, 59, 0.15);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-top: 85px !important;
        }

        /* Cart Hero Header */
        .cart-hero {
            background: linear-gradient(135deg, #005a31 0%, #006C3B 60%, #008749 100%);
            position: relative;
            padding: 40px 24px 90px;
            overflow: hidden;
        }

        .cart-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(255, 184, 0, 0.18) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .cart-hero-inner {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .cart-breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: var(--radius-pill);
            background: rgba(255, 255, 255, 0.15);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 14px;
            font-weight: 500;
        }

        .cart-breadcrumb a {
            color: #fff;
            text-decoration: none;
        }

        .cart-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .cart-title-row h1 {
            color: #ffffff;
            font-size: clamp(2rem, 3.5vw, 2.5rem);
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .cart-title-row p {
            color: rgba(255, 255, 255, 0.88);
            margin: 4px 0 0;
            font-size: 0.95rem;
        }

        .badge-count {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: var(--radius-pill);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Cart Main Layout */
        .cart-main-wrap {
            max-width: 1200px;
            width: 100%;
            margin: -60px auto 60px;
            padding: 0 24px;
            position: relative;
            z-index: 3;
            flex: 1;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 28px;
            align-items: start;
        }

        /* Cart Item Card */
        .cart-items-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: var(--shadow-card);
            padding: 28px;
        }

        .cart-items-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 18px;
            border-bottom: 1.5px solid var(--border-subtle);
            margin-bottom: 20px;
        }

        .cart-items-card-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-heading);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cart-item-row {
            display: grid;
            grid-template-columns: 80px 1fr auto auto;
            align-items: center;
            gap: 18px;
            padding: 20px 0;
            border-bottom: 1px solid var(--border-subtle);
            transition: all 0.2s ease;
        }

        .cart-item-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .item-thumb {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-md);
            background: #f1f5f9;
            object-fit: cover;
            border: 1px solid var(--border-subtle);
        }

        .item-thumb-fallback {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-md);
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .item-info h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-heading);
            margin: 0 0 4px;
        }

        .item-info .item-unit-price {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin: 0 0 4px;
        }

        .item-info .item-subtotal-mobile {
            display: none;
            font-weight: 700;
            color: var(--primary);
            font-size: 0.95rem;
        }

        /* Modern Quantity Controls */
        .qty-stepper {
            display: inline-flex;
            align-items: center;
            border: 1.5px solid var(--border-subtle);
            border-radius: var(--radius-pill);
            background: #f8fafc;
            padding: 4px;
            gap: 4px;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: #ffffff;
            color: var(--text-heading);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: all 0.2s ease;
            font-size: 0.85rem;
        }

        .qty-btn:hover {
            background: var(--primary);
            color: #ffffff;
            transform: scale(1.08);
        }

        .qty-input {
            width: 40px;
            text-align: center;
            border: none;
            background: transparent;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-heading);
        }

        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Item Total & Remove */
        .item-right-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .item-row-subtotal {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--primary);
            min-width: 90px;
            text-align: right;
        }

        .btn-trash-item {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: #fee2e2;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-trash-item:hover {
            background: #ef4444;
            color: #ffffff;
            transform: scale(1.1);
        }

        /* Order Summary Card */
        .summary-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: var(--shadow-card);
            padding: 28px;
            position: sticky;
            top: 105px;
        }

        .summary-card h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-heading);
            margin: 0 0 20px;
            padding-bottom: 14px;
            border-bottom: 1.5px solid var(--border-subtle);
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.94rem;
            color: var(--text-muted);
            margin-bottom: 14px;
        }

        .summary-line strong {
            color: var(--text-heading);
            font-weight: 600;
        }

        .summary-divider {
            height: 1px;
            background: var(--border-subtle);
            margin: 20px 0;
        }

        .summary-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .summary-total-row .total-label {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-heading);
        }

        .summary-total-row .total-price {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .btn-checkout-now {
            width: 100%;
            background: linear-gradient(135deg, #006C3B 0%, #008749 100%);
            color: #ffffff;
            border: none;
            padding: 15px 24px;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 6px 20px rgba(0, 108, 59, 0.28);
        }

        .btn-checkout-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(0, 108, 59, 0.38);
            color: #ffffff;
        }

        .secure-badge-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 16px;
        }

        .secure-badge-box i {
            color: var(--primary);
        }

        /* Empty Cart State */
        .empty-cart-box {
            text-align: center;
            padding: 70px 24px;
            background: var(--surface);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-subtle);
        }

        .empty-cart-icon-wrap {
            width: 110px;
            height: 110px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 24px;
        }

        .empty-cart-box h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0 0 8px;
        }

        .empty-cart-box p {
            color: var(--text-muted);
            font-size: 1rem;
            max-width: 420px;
            margin: 0 auto 28px;
        }

        .btn-browse-menu {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #006C3B 0%, #008749 100%);
            color: #ffffff;
            padding: 14px 32px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 700;
            transition: all 0.25s ease;
            box-shadow: 0 6px 18px rgba(0, 108, 59, 0.25);
        }

        .btn-browse-menu:hover {
            transform: translateY(-2px);
            color: #ffffff;
            box-shadow: 0 10px 24px rgba(0, 108, 59, 0.35);
        }

        /* Responsive Breakpoints */
        @media (max-width: 991px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
            .summary-card {
                position: static;
            }
        }

        @media (max-width: 650px) {
            body {
                padding-top: 75px !important;
            }
            .cart-hero {
                padding: 30px 16px 80px;
            }
            .cart-main-wrap {
                margin-top: -55px;
                padding: 0 14px;
            }
            .cart-items-card {
                padding: 20px 16px;
            }
            .cart-item-row {
                grid-template-columns: 70px 1fr;
                gap: 14px;
                position: relative;
            }
            .item-thumb, .item-thumb-fallback {
                width: 70px;
                height: 70px;
            }
            .item-info .item-subtotal-mobile {
                display: block;
                margin-top: 4px;
            }
            .item-right-actions {
                grid-column: 2 / -1;
                justify-content: space-between;
                width: 100%;
                margin-top: 8px;
            }
            .item-row-subtotal {
                display: none;
            }
            .summary-card {
                padding: 22px 18px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/ui/navbar.php'; ?>

    <!-- Cart Hero Header -->
    <header class="cart-hero">
        <div class="cart-hero-inner">
            <div class="cart-breadcrumb">
                <a href="index"><i class="fas fa-home me-1"></i> Home</a>
                <i class="fas fa-chevron-right" style="font-size: 0.65rem; opacity: 0.7;"></i>
                <span>Cart</span>
            </div>

            <div class="cart-title-row">
                <div>
                    <h1>Shopping Cart</h1>
                    <p>Review your chosen meals before checking out</p>
                </div>
                <div class="badge-count">
                    <i class="fas fa-bag-shopping"></i>
                    <span><?php echo count($cart_items); ?> Item<?php echo count($cart_items) === 1 ? '' : 's'; ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Cart Container -->
    <main class="cart-main-wrap">
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart-box">
                <div class="empty-cart-icon-wrap">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <h2>Your Cart is Empty</h2>
                <p>Looks like you haven't added anything delicious yet. Browse our freshly prepared menu items now!</p>
                <a href="menu" class="btn-browse-menu">
                    <i class="fas fa-utensils"></i> Explore Our Menu
                </a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                
                <!-- Left: Items List -->
                <div class="cart-items-card">
                    <div class="cart-items-card-header">
                        <h2><i class="fas fa-utensils text-success"></i> Ordered Dishes</h2>
                        <a href="menu" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold">
                            <i class="fas fa-plus me-1"></i> Add More
                        </a>
                    </div>

                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item-row" id="cart-item-<?php echo $item['cart_id']; ?>">
                            <?php if (!empty($item['image']) && file_exists('uploads/menu/' . $item['image'])): ?>
                                <img src="uploads/menu/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-thumb">
                            <?php elseif (!empty($item['image']) && file_exists('assets/images/menu/' . $item['image'])): ?>
                                <img src="assets/images/menu/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-thumb">
                            <?php else: ?>
                                <div class="item-thumb-fallback">
                                    <i class="fas fa-bowl-food"></i>
                                </div>
                            <?php endif; ?>

                            <div class="item-info">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <div class="item-unit-price">₱<?php echo number_format($item['price'], 2); ?> each</div>
                                <div class="item-subtotal-mobile">₱<?php echo number_format($item['subtotal'], 2); ?></div>
                            </div>

                            <div class="item-right-actions">
                                <div class="qty-stepper">
                                    <button type="button" class="qty-btn" onclick="modifyQty(<?php echo $item['cart_id']; ?>, -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="qty-input" id="qty-input-<?php echo $item['cart_id']; ?>" 
                                           value="<?php echo (int)$item['quantity']; ?>" min="1" max="99" 
                                           onchange="submitDirectQty(<?php echo $item['cart_id']; ?>, this.value)">
                                    <button type="button" class="qty-btn" onclick="modifyQty(<?php echo $item['cart_id']; ?>, 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>

                                <div class="item-row-subtotal">
                                    ₱<?php echo number_format($item['subtotal'], 2); ?>
                                </div>

                                <button type="button" class="btn-trash-item" onclick="confirmDeleteItem(<?php echo $item['cart_id']; ?>)" title="Remove item">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Right: Order Summary -->
                <aside class="summary-col">
                    <div class="summary-card">
                        <h2>Order Summary</h2>
                        
                        <div class="summary-line">
                            <span>Subtotal (<?php echo count($cart_items); ?> items)</span>
                            <strong>₱<?php echo number_format($total, 2); ?></strong>
                        </div>

                        <div class="summary-line">
                            <span>Estimated Delivery Fee</span>
                            <strong class="text-success">₱50.00</strong>
                        </div>

                        <div class="summary-divider"></div>

                        <div class="summary-total-row">
                            <span class="total-label">Total Amount</span>
                            <span class="total-price">₱<?php echo number_format($total + 50, 2); ?></span>
                        </div>

                        <a href="checkout" class="btn-checkout-now">
                            <i class="fas fa-shield-halved"></i> Proceed to Checkout
                        </a>

                        <div class="secure-badge-box">
                            <i class="fas fa-lock"></i>
                            <span>Safe and Encrypted Checkout</span>
                        </div>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </main>

    <!-- Hidden form for fast native POST actions -->
    <form id="cartActionForm" method="POST" action="cart" style="display: none;">
        <input type="hidden" name="cart_id" id="postCartId">
        <input type="hidden" name="quantity" id="postQuantity">
        <input type="hidden" name="update_quantity" id="postUpdateFlag" value="1">
        <input type="hidden" name="remove_item" id="postRemoveFlag">
    </form>

    <?php include 'includes/ui/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function modifyQty(cartId, change) {
        const input = document.getElementById('qty-input-' + cartId);
        let current = parseInt(input.value) || 1;
        let newQty = current + change;
        if (newQty < 1) {
            confirmDeleteItem(cartId);
            return;
        }
        submitDirectQty(cartId, newQty);
    }

    function submitDirectQty(cartId, qty) {
        const form = document.getElementById('cartActionForm');
        document.getElementById('postCartId').value = cartId;
        document.getElementById('postQuantity').value = Math.max(1, parseInt(qty) || 1);
        document.getElementById('postUpdateFlag').disabled = false;
        document.getElementById('postRemoveFlag').disabled = true;
        form.submit();
    }

    function confirmDeleteItem(cartId) {
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            const form = document.getElementById('cartActionForm');
            document.getElementById('postCartId').value = cartId;
            document.getElementById('postUpdateFlag').disabled = true;
            document.getElementById('postRemoveFlag').disabled = false;
            document.getElementById('postRemoveFlag').value = '1';
            form.submit();
        }
    }
    </script>
</body>
</html>