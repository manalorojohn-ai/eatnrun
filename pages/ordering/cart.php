<?php
session_start();
require_once 'config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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

$stmt = mysqli_prepare($conn, $cart_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($item = mysqli_fetch_assoc($result)) {
    $cart_items[] = $item;
    $total += $item['subtotal'];
}
mysqli_stmt_close($stmt);

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $cart_id = $_POST['cart_id'];
        $quantity = max(1, intval($_POST['quantity'])); // Ensure quantity is at least 1
        
        $stmt = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "iii", $quantity, $cart_id, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } elseif (isset($_POST['remove_item'])) {
        $cart_id = $_POST['cart_id'];
        
        $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $cart_id, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // Redirect to prevent form resubmission
    header("Location: cart.php");
    exit();
}

// Add a checkout handler
if (isset($_GET['checkout']) && !empty($cart_items)) {
    header("Location: checkout.php");
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
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: var(--bs-body-font-family);
        }

        .cart-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            margin: 2rem auto;
            max-width: 1000px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .cart-item {
            background: #fff;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            transition: all 0.3s ease;
            animation: slideIn 0.5s ease-out;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .item-details {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .item-name {
            font-weight: 500;
            font-size: 1.1rem;
            color: #333;
        }

        .item-price {
            color: var(--bs-primary);
            font-weight: 500;
            margin-left: 1rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0 1.5rem;
        }

        .quantity-btn {
            background: none;
            border: none;
            padding: 0;
            color: var(--bs-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.2s ease;
        }

        .quantity-btn:hover {
            color: #00A65A;
            transform: scale(1.1);
        }

        .quantity-input {
            width: 3rem;
            text-align: center;
            border: none;
            background: none;
            font-weight: 500;
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
            color: #dc3545;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.2s ease;
        }
        
        .remove-item:hover {
            color: #bb2d3b;
            transform: scale(1.1);
        }
        
        .cart-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .summary-total {
            font-weight: 600;
            color: var(--bs-primary);
            font-size: 1.2rem;
        }

        .checkout-btn {
            background: linear-gradient(90deg, var(--bs-primary) 0%, #00A65A 100%);
            color: white;
            border: none;
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 108, 59, 0.3);
        }

        .empty-cart {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-cart-icon {
            font-size: 5rem;
            color: #006C3B;
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .btn-continue-shopping {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(90deg, var(--bs-primary) 0%, #00A65A 100%);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-continue-shopping:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 108, 59, 0.3);
            color: white;
        }

        @media (max-width: 768px) {
            .cart-container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .cart-item {
                flex-wrap: wrap;
                gap: 1rem;
                padding: 1rem;
            }

            .item-details {
            width: 100%;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
        }
        
            .item-price {
                margin-left: 0;
        }
        
            .quantity-control {
                margin: 0;
            width: 100%;
            justify-content: center;
        }
        
            .remove-item {
                width: 100%;
                text-align: center;
                padding: 0.5rem;
                background: #f8f9fa;
                border-radius: 8px;
        }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <div class="cart-container">
            <div class="cart-header text-center mb-4">
                <h1 class="display-5 fw-bold text-primary mb-0">Shopping Cart</h1>
    </div>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                    <i class="fas fa-shopping-cart empty-cart-icon"></i>
                    <h2 class="mb-3">Your cart is empty</h2>
                    <p class="text-muted mb-4">Add some delicious items to your cart!</p>
                    <a href="menu.php" class="btn-continue-shopping">
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
                                <div class="item-price">₱<span class="price-value"><?php echo number_format($item['price'], 2); ?></span></div>
                            </div>

                            <div class="quantity-control">
                                <button class="quantity-btn minus" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, 'decrease')">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" 
                                       class="quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="99"
                                       onchange="updateQuantityManual(<?php echo $item['cart_id']; ?>, this.value)"
                                       onkeyup="updateQuantityManual(<?php echo $item['cart_id']; ?>, this.value)"
                                       onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                                <button class="quantity-btn plus" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, 'increase')">
                                    <i class="fas fa-plus"></i>
                                </button>
                                </div>

                            <button class="remove-item" onclick="removeItem(<?php echo $item['cart_id']; ?>)">
                                <i class="fas fa-trash-alt"></i>
                                </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₱<span id="subtotal"><?php echo number_format($total, 2); ?></span></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <span>₱<span id="delivery-fee">50.00</span></span>
                </div>
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span>₱<span id="total"><?php echo number_format($total + 50, 2); ?></span></span>
                    </div>
                    <button class="checkout-btn" onclick="window.location.href='checkout.php'">
                        Proceed to Checkout
                    </button>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <!-- Bootstrap 5 JS - Removed duplicate, navbar.php already includes Bootstrap 5.3.2 -->

    <script>
    function updateQuantity(cartId, action) {
        const quantityInput = document.querySelector(`.cart-item[data-id="${cartId}"] .quantity-input`);
        let newQuantity = parseInt(quantityInput.value);
        
        if (action === 'increase') {
            if (newQuantity >= 99) return; // Prevent going over 99
            newQuantity += 1;
        } else if (action === 'decrease' && newQuantity > 1) {
            newQuantity -= 1;
        }
        
        updateCart(cartId, newQuantity);
                    }

    function updateQuantityManual(cartId, value) {
        let quantity = parseInt(value) || 1;
        
        // Limit to 99
        if (quantity > 99) {
            quantity = 99;
        } else if (quantity < 1) {
            quantity = 1;
        }
        
        updateCart(cartId, quantity);
    }

    function updateCart(cartId, newQuantity) {
        const cartItem = document.querySelector(`.cart-item[data-id="${cartId}"]`);
        const quantityInput = cartItem.querySelector('.quantity-input');
        
        // Ensure quantity is within limits before updating
        if (newQuantity > 99) {
            newQuantity = 99;
        } else if (newQuantity < 1) {
            newQuantity = 1;
        }
        
        // Update input value immediately for better UX
        quantityInput.value = newQuantity;
        
        const pricePerItem = parseFloat(cartItem.dataset.price);
        const newItemTotal = pricePerItem * newQuantity;
        
        // Update item price display
        cartItem.querySelector('.price-value').textContent = newItemTotal.toFixed(2);
        
        // Calculate new subtotal
        let newSubtotal = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const qty = parseInt(item.querySelector('.quantity-input').value);
            const price = parseFloat(item.dataset.price);
            newSubtotal += qty * price;
        });
        
        // Update displays
        document.getElementById('subtotal').textContent = newSubtotal.toFixed(2);
        document.getElementById('total').textContent = (newSubtotal + 50).toFixed(2);

        // Update database
        fetch('update_cart.php', {
                method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cart_id=${cartId}&quantity=${newQuantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart count in header
                updateCartCount();
            } else {
                alert('Failed to update quantity. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
        }

    function removeItem(cartId) {
        if (!confirm('Are you sure you want to remove this item?')) return;

        fetch('remove_from_cart.php', {
                method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cart_id=${cartId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.cart-item[data-id="${cartId}"]`);
                item.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => {
                    item.remove();
                
                    // Recalculate totals
                    let newSubtotal = 0;
                    document.querySelectorAll('.cart-item').forEach(item => {
                        const qty = parseInt(item.querySelector('.quantity-input').value);
                        const price = parseFloat(item.dataset.price);
                        newSubtotal += qty * price;
                    });
                    
                    document.getElementById('subtotal').textContent = newSubtotal.toFixed(2);
                    document.getElementById('total').textContent = (newSubtotal + 50).toFixed(2);
                
                    // Check if cart is empty
                    const cartItems = document.querySelectorAll('.cart-item');
                    if (cartItems.length === 0) {
                        location.reload(); // Reload to show empty cart state
                }
                    
                    // Update cart count in header
                    updateCartCount();
                }, 300);
            } else {
                alert('Failed to remove item. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    function updateCartCount() {
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            fetch('get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.count > 0) {
                    cartCountElement.textContent = data.count;
                    cartCountElement.style.display = 'block';
                } else {
                    cartCountElement.style.display = 'none';
                }
            });
        }
    }
    
    // Add slideOut animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(-20px); }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html> 