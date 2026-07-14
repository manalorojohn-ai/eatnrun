<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';
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
$cart_query = "SELECT c.id as cart_id, c.quantity, c.menu_item_id, m.name, m.price, 
               (m.price * c.quantity) as subtotal 
               FROM cart c 
               JOIN menu_items m ON c.menu_item_id = m.id 
               WHERE c.user_id = ?";
$cart_items = [];
$total = 0;

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
} else {
    // JSONDatabase or fallback - empty cart
    $cart_items = [];
}

// Calculate total
foreach ($cart_items as $item) {
    $total += $item['subtotal'];
}

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $cart_id = $_POST['cart_id'];
        $quantity = max(1, intval($_POST['quantity'])); // Ensure quantity is at least 1

        $update_query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
        
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare($update_query);
            $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
        } elseif ($conn instanceof mysqli) {
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("iii", $quantity, $cart_id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
        }
    } elseif (isset($_POST['remove_item'])) {
        $cart_id = $_POST['cart_id'];

        $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare($delete_query);
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
        } elseif ($conn instanceof mysqli) {
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Redirect to prevent form resubmission
    header("Location: cart");
    exit();
}

// Add a checkout handler
if (isset($_GET['checkout']) && !empty($cart_items)) {
    header("Location: checkout");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Eat&Run</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bs-primary: #006C3B;
            --bs-primary-rgb: 0, 108, 59;
            --bs-body-font-family: 'Poppins', sans-serif;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #FFC107;
            --light-bg: #f8f9fa;
            --border-color: #e0e0e0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f4f8 0%, #e9ecef 100%);
            font-family: var(--bs-body-font-family);
            padding-top: 80px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .cart-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 108, 59, 0.1);
            padding: 2.5rem;
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cart-header {
            border-bottom: 3px solid var(--bs-primary);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }

        .cart-header h1 {
            color: var(--bs-primary);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .cart-header p {
            color: #666;
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        .cart-items {
            margin-bottom: 2rem;
        }

        .cart-item {
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 1.5rem;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInScale 0.5s ease-out;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .cart-item:hover {
            background: white;
            border-color: var(--bs-primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 108, 59, 0.15);
        }

        .item-details {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .item-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #222;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .item-name::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 4px;
            background: var(--bs-primary);
            border-radius: 50%;
        }

        .item-price {
            color: var(--bs-primary);
            font-weight: 700;
            font-size: 1.25rem;
        }

        .item-subtotal {
            color: #666;
            font-size: 0.9rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            transition: all 0.2s ease;
        }

        .quantity-control:hover {
            border-color: var(--bs-primary);
            box-shadow: 0 2px 8px rgba(0, 108, 59, 0.1);
        }

        .quantity-btn {
            background: none;
            border: none;
            padding: 0.3rem 0.6rem;
            color: var(--bs-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.2s ease;
            border-radius: 6px;
        }

        .quantity-btn:hover {
            color: white;
            background: var(--bs-primary);
            transform: scale(1.1);
        }

        .quantity-input {
            width: 3rem;
            text-align: center;
            border: none;
            background: none;
            font-weight: 600;
            font-size: 1rem;
            padding: 0;
            color: #333;
        }

        .quantity-input::-webkit-inner-spin-button,
        .quantity-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .quantity-input:focus {
            outline: none;
        }
        
        .remove-item {
            background: white;
            color: var(--danger);
            border: 2px solid var(--danger);
            padding: 0.6rem 1.2rem;
            cursor: pointer;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .remove-item:hover {
            color: white;
            background: var(--danger);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .cart-summary {
            background: linear-gradient(135deg, #f0f4f8 0%, #e9ecef 100%);
            border: 2px solid var(--bs-primary);
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 108, 59, 0.1);
        }

        .summary-row:last-of-type {
            border-bottom: none;
        }

        .summary-label {
            color: #666;
            font-weight: 500;
        }

        .summary-value {
            font-weight: 600;
            color: #222;
        }

        .summary-total {
            font-weight: 700;
            color: var(--bs-primary);
            font-size: 1.3rem;
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 2px solid var(--bs-primary);
        }

        .checkout-btn {
            background: linear-gradient(135deg, var(--bs-primary) 0%, #00A65A 100%);
            color: white;
            border: none;
            width: 100%;
            padding: 1.2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.05rem;
            margin-top: 1.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 5px 20px rgba(0, 108, 59, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 108, 59, 0.4);
        }

        .checkout-btn:active {
            transform: translateY(-1px);
        }

        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, rgba(0, 108, 59, 0.05) 0%, rgba(0, 108, 59, 0.02) 100%);
            border-radius: 16px;
            border: 2px dashed var(--bs-primary);
        }

        .empty-cart-icon {
            font-size: 6rem;
            color: var(--bs-primary);
            margin-bottom: 1.5rem;
            opacity: 0.8;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .empty-cart h2 {
            color: #222;
            font-weight: 700;
            margin-bottom: 0.75rem;
            font-size: 1.8rem;
        }

        .empty-cart p {
            color: #666;
            font-size: 1.05rem;
            margin-bottom: 2rem;
        }

        .btn-continue-shopping {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: linear-gradient(135deg, var(--bs-primary) 0%, #00A65A 100%);
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.05rem;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 5px 20px rgba(0, 108, 59, 0.3);
            cursor: pointer;
        }

        .btn-continue-shopping:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 108, 59, 0.4);
            color: white;
        }

        @media (max-width: 768px) {
            .cart-container {
                padding: 1.5rem;
            }

            .cart-item {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .quantity-control {
                width: 100%;
                justify-content: center;
            }

            .remove-item {
                width: 100%;
                justify-content: center;
            }

            .cart-header h1 {
                font-size: 1.8rem;
            }

            .empty-cart {
                padding: 2.5rem 1rem;
            }

            .empty-cart-icon {
                font-size: 4rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/ui/navbar.php'; ?>

    <div class="container">
        <div class="cart-container">
            <div class="cart-header">
                <h1><i class="fas fa-shopping-cart" style="margin-right: 0.75rem;"></i>Shopping Cart</h1>
                <p><?php echo empty($cart_items) ? 'No items yet' : count($cart_items) . ' item' . (count($cart_items) !== 1 ? 's' : '') . ' in your cart'; ?></p>
            </div>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart empty-cart-icon"></i>
                    <h2>Your cart is empty</h2>
                    <p>Let's add some delicious food to your order!</p>
                    <a href="/menu" class="btn-continue-shopping">
                        <i class="fas fa-arrow-left"></i>
                        Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" data-id="<?php echo $item['cart_id']; ?>" data-price="<?php echo $item['price']; ?>">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-price">₱<?php echo number_format($item['price'], 2); ?>/item</div>
                                <div class="item-subtotal">Subtotal: ₱<?php echo number_format($item['subtotal'], 2); ?></div>
                            </div>

                            <div class="quantity-control">
                                <button class="quantity-btn" onclick="decreaseQuantity(this, <?php echo $item['cart_id']; ?>)" title="Decrease quantity">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" 
                                       class="quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="99"
                                       data-cart-id="<?php echo $item['cart_id']; ?>"
                                       data-price="<?php echo $item['price']; ?>"
                                       onchange="updateQuantity(this, <?php echo $item['cart_id']; ?>)">
                                <button class="quantity-btn" onclick="increaseQuantity(this, <?php echo $item['cart_id']; ?>)" title="Increase quantity">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>

                            <button class="remove-item" onclick="removeFromCart(<?php echo $item['cart_id']; ?>)" title="Remove item">
                                <i class="fas fa-trash-alt"></i>
                                <span>Remove</span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span class="summary-label">Subtotal (<?php echo count($cart_items); ?> items)</span>
                        <span class="summary-value">₱<span id="subtotal"><?php echo number_format($total, 2); ?></span></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Delivery Fee</span>
                        <span class="summary-value">₱<span id="delivery-fee">50.00</span></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total Amount</span>
                        <span>₱<span id="total"><?php echo number_format($total + 50, 2); ?></span></span>
                    </div>
                    <button class="checkout-btn" onclick="proceedToCheckout()" title="Proceed to checkout">
                        <i class="fas fa-lock"></i>
                        Proceed to Checkout
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/ui/footer.php'; ?>
    
    <script src="assets/js/ajax-handler.js"></script>
    <script src="assets/js/cart-ajax.js"></script>
    <script>
        function proceedToCheckout() {
            window.location.href = '/checkout';
        }

        function increaseQuantity(btn, cartId) {
            const input = btn.parentElement.querySelector('.quantity-input');
            if(parseInt(input.value) < 99) {
                input.value = parseInt(input.value) + 1;
                updateQuantity(input, cartId);
            }
        }

        function decreaseQuantity(btn, cartId) {
            const input = btn.parentElement.querySelector('.quantity-input');
            if(parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                updateQuantity(input, cartId);
            } else {
                removeFromCart(cartId);
            }
        }

        function updateQuantity(input, cartId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="update_quantity" value="1">
                <input type="hidden" name="cart_id" value="${cartId}">
                <input type="hidden" name="quantity" value="${input.value}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function removeFromCart(cartId) {
            if(confirm('Are you sure you want to remove this item?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="remove_item" value="1">
                    <input type="hidden" name="cart_id" value="${cartId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 