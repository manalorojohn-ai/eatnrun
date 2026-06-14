<?php
require_once 'includes/config.php';

// Handle reorder functionality
if (isset($_GET['reorder']) && !empty($_GET['reorder'])) {
    $order_id = intval($_GET['reorder']);
    
    try {
        mysqli_begin_transaction($conn);
        $items_query = "SELECT oi.menu_item_id, oi.quantity, mi.name, mi.price, mi.status 
                       FROM order_items oi 
                       JOIN menu_items mi ON oi.menu_item_id = mi.id 
                       WHERE oi.order_id = ?";
        
        $stmt = mysqli_prepare($conn, $items_query);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $total_query = "SELECT COUNT(*) as total FROM order_items WHERE order_id = ?";
        $total_stmt = mysqli_prepare($conn, $total_query);
        mysqli_stmt_bind_param($total_stmt, "i", $order_id);
        mysqli_stmt_execute($total_stmt);
        $total_result = mysqli_stmt_get_result($total_stmt);
        $total_row = mysqli_fetch_assoc($total_result);
        $total_items = $total_row['total'];
        
        $available_items = 0;
        while ($item = mysqli_fetch_assoc($result)) {
            if ($item['status'] === 'available') {
                $check_cart = "SELECT id, quantity FROM cart WHERE user_id = ? AND menu_item_id = ?";
                $cart_stmt = mysqli_prepare($conn, $check_cart);
                mysqli_stmt_bind_param($cart_stmt, "ii", $_SESSION['user_id'], $item['menu_item_id']);
                mysqli_stmt_execute($cart_stmt);
                $cart_result = mysqli_stmt_get_result($cart_stmt);
                $cart_item = mysqli_fetch_assoc($cart_result);
                mysqli_stmt_close($cart_stmt);
                
                if ($cart_item) {
                    $new_quantity = $cart_item['quantity'] + $item['quantity'];
                    $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "ii", $new_quantity, $cart_item['id']);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                } else {
                    $insert_query = "INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?)";
                    $insert_stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($insert_stmt, "iii", $_SESSION['user_id'], $item['menu_item_id'], $item['quantity']);
                    mysqli_stmt_execute($insert_stmt);
                    mysqli_stmt_close($insert_stmt);
                }
                $available_items++;
            }
        }
        mysqli_stmt_close($stmt);
        mysqli_stmt_close($total_stmt);
        mysqli_commit($conn);
        
        if ($available_items === 0) {
            $_SESSION['error'] = "Sorry, none of the items from your previous order are currently available.";
        } elseif ($available_items < $total_items) {
            $_SESSION['warning'] = "Some items from your previous order are no longer available. We've added the available items to your cart.";
        } else {
            $_SESSION['success'] = "All items from your previous order have been added to your cart!";
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "An error occurred while processing your request: " . $e->getMessage();
    }
    header("Location: menu");
    exit();
}

// Function to safely get categories
function getCategories($conn) {
    $categories = [];
    $query = "SELECT id, name FROM categories WHERE status = 'active' ORDER BY name";
    try {
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
    } catch (Exception $e) { error_log($e->getMessage()); }
    return $categories;
}

// Function to get menu items
function getMenuItems($conn, $category_name = null) {
    $menu_items = [];
    $escaped_category = $conn->real_escape_string($category_name ?? '');
    $where_clause = $category_name ? 
        "WHERE c.name = '" . $escaped_category . "' AND m.status = 'available'" : 
        "WHERE m.status = 'available'";
    
    $query = "SELECT m.*, c.name as category_name,
              COALESCE(m.image_path, 'assets/images/default-food.jpg') as image_path
          FROM menu_items m 
          LEFT JOIN categories c ON m.category_id = c.id 
          $where_clause 
          ORDER BY m.name";
    try {
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $menu_items[] = $row;
            }
        }
    } catch (Exception $e) { error_log($e->getMessage()); }
    return $menu_items;
}

// Page variables
$categories = getCategories($conn);
$selected_category = isset($_GET['category']) ? $_GET['category'] : null;
$menu_items = getMenuItems($conn, $selected_category);
$page_title = 'Our Delicious Menu';
$current_page = 'menu';

// Include CSS
ob_start(); ?>
<link rel="stylesheet" href="assets/css/menu-enhanced.css">
<?php $extra_styles = ob_get_clean();

include 'includes/ui/header.php';
include 'includes/ui/loader.php';
include 'includes/ui/navbar.php';
?>

<main>
<div class="menu-hero-aesthetic">
    <div class="container py-5 text-center">
        <div class="hero-content-zen">
            <h1 class="display-3 fw-bold animate-up">Our <span class="text-gradient">Menu</span>.</h1>
            <p class="lead text-secondary animate-up-delayed">Crafted with precision, delivered with care.</p>
            
            <div class="search-glass-container animate-up-delayed-more">
                <div class="search-glass">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="menuSearch" placeholder="What are you craving today?" autocomplete="off">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-2">
    <div class="filter-wrapper-zen sticky-top">
        <div class="category-grid-aesthetic">
            <a href="menu" class="category-chip <?php echo !$selected_category ? 'active' : ''; ?>" data-category="all">
                <i class="fas fa-utensils"></i> All
            </a>
            <?php foreach ($categories as $category): ?>
                <a href="menu?category=<?php echo urlencode($category['name']); ?>" 
                   class="category-chip <?php echo ($selected_category === $category['name']) ? 'active' : ''; ?>"
                   data-category="<?php echo htmlspecialchars($category['name']); ?>">
                    <?php
                    $cat_name = strtolower($category['name']);
                    if (strpos($cat_name, 'pizza') !== false) echo '<i class="fas fa-pizza-slice"></i>';
                    elseif (strpos($cat_name, 'burger') !== false) echo '<i class="fas fa-hamburger"></i>';
                    elseif (strpos($cat_name, 'drink') !== false) echo '<i class="fas fa-glass-martini-alt"></i>';
                    elseif (strpos($cat_name, 'dessert') !== false) echo '<i class="fas fa-ice-cream"></i>';
                    elseif (strpos($cat_name, 'pasta') !== false) echo '<i class="fas fa-wine-glass-alt"></i>';
                    else echo '<i class="fas fa-dot-circle"></i>';
                    ?>
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Notifications Area -->
    <div class="notifications-area">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="menu-alert error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="menu-alert success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Menu Grid -->
    <div class="menu-container mt-5">
        <div class="menu-grid-aesthetic" id="menuGrid">
            <?php if (empty($menu_items)): ?>
                <div class="empty-state-zen">
                    <i class="fas fa-cloud fa-3x mb-3 text-muted"></i>
                    <h3>Quiet for now...</h3>
                    <p>We couldn't find matches. Try another search.</p>
                </div>
            <?php else: ?>
                <?php foreach ($menu_items as $index => $item): ?>
                    <div class="card-aesthetic animate-in" style="animation-delay: <?php echo $index * 0.05; ?>s" data-category="<?php echo htmlspecialchars($item['category_name']); ?>" data-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>" data-image="<?php echo htmlspecialchars($item['image_path']); ?>" data-description="<?php echo htmlspecialchars($item['description']); ?>" data-price="<?php echo htmlspecialchars(number_format($item['price'], 2)); ?>" data-item-id="<?php echo $item['id']; ?>">
                        <div class="card-image-wrapper">
                            <?php 
                            $image_url = (file_exists($item['image_path']) && !empty($item['image_path'])) ? $item['image_path'] : 'assets/images/menu/default-food.jpg';
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 class="food-img"
                                 loading="lazy">
                            <span class="badge-custom"><?php echo htmlspecialchars($item['category_name']); ?></span>
                        </div>
                        <div class="card-body-aesthetic">
                            <div class="card-meta">
                                <span class="rating"><i class="fas fa-star text-warning"></i> 4.9</span>
                                <span class="delivery-time"><i class="fas fa-clock text-muted"></i> 25-35 min</span>
                            </div>
                            <h3 class="food-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="food-desc"><?php echo htmlspecialchars($item['description']); ?></p>
                            <div class="card-footer-aesthetic">
                                <div class="food-price">
                                    <span class="price-symbol">₱</span>
                                    <span class="price-value"><?php echo number_format($item['price'], 2); ?></span>
                                </div>
                                <button type="button" class="btn-add-aesthetic" data-item-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" onclick="event.stopPropagation();">
                                    <i class="fas fa-shopping-basket"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>

<!-- Item Detail Modal -->
<div class="item-modal" id="itemModal">
    <div class="modal-overlay" id="modalOverlay"></div>
    <div class="modal-content">
        <button class="modal-close" id="closeModal">×</button>
        
        <div class="modal-body">
            <!-- Left: Image -->
            <div class="modal-image-section">
                <img id="modalImage" src="" alt="Item" class="modal-image">
            </div>
            
            <!-- Right: Content -->
            <div class="modal-content-section">
                <div class="modal-category" id="modalCategory">Category</div>
                <h2 id="modalName" class="modal-title">Item Name</h2>
                
                <p id="modalDescription" class="modal-description">Item description goes here</p>
                
                <div class="modal-meta">
                    <div class="meta-item">
                        <span class="meta-label">Price</span>
                        <span class="meta-value">
                            <span class="currency">₱</span><span id="modalPrice">0.00</span>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Calories</span>
                        <span class="meta-value">548 CAL</span>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <div class="quantity-control">
                        <button class="qty-btn qty-minus" id="qtyMinus">−</button>
                        <input type="number" id="quantity" value="1" min="1" max="99" readonly>
                        <button class="qty-btn qty-plus" id="qtyPlus">+</button>
                    </div>
                    <button class="btn-add-order" id="addToCartBtn">ADD TO ORDER</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Aesthetic Modal Styles */
.item-modal { 
    display: none; 
    position: fixed; 
    top: 0; 
    left: 0; 
    width: 100%; 
    height: 100%; 
    z-index: 1000;
    padding: 20px;
    box-sizing: border-box;
}

.item-modal.active { 
    display: flex; 
    align-items: center;
    justify-content: center;
}

.modal-overlay { 
    position: fixed; 
    top: 0; 
    left: 0; 
    width: 100%; 
    height: 100%; 
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(4px);
    z-index: 999; 
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-content { 
    position: relative;
    background: white; 
    border-radius: 4px; 
    width: 100%;
    max-width: 850px;
    height: auto;
    max-height: 90vh;
    z-index: 1001; 
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    animation: slideIn 0.4s ease-out;
    border: 1px solid #f0f0f0;
    overflow: hidden;
}

.modal-close { 
    position: absolute; 
    top: 20px; 
    right: 20px; 
    background: none; 
    border: none; 
    font-size: 28px; 
    cursor: pointer; 
    color: #999; 
    width: 30px; 
    height: 30px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    z-index: 1002;
    transition: all 0.2s ease;
}

.modal-close:hover { 
    color: #333;
}

.modal-body { 
    display: flex; 
    flex-direction: row; 
    height: 100%;
}

/* Left: Image Section */
.modal-image-section { 
    flex: 0 0 40%;
    background: #f9f9f9;
    padding: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px 0 0 4px;
    border-right: 1px solid #f0f0f0;
}

.modal-image { 
    width: 100%;
    height: auto;
    max-width: 100%;
    display: block;
    object-fit: cover;
    border-radius: 2px;
}

/* Right: Content Section */
.modal-content-section { 
    flex: 0 0 60%;
    padding: 35px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.modal-content-section::-webkit-scrollbar { 
    width: 4px; 
}
.modal-content-section::-webkit-scrollbar-track { 
    background: transparent; 
}
.modal-content-section::-webkit-scrollbar-thumb { 
    background: #ddd; 
    border-radius: 2px;
}

.modal-category { 
    font-size: 11px; 
    font-weight: 600; 
    color: #999;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 10px;
    font-style: italic;
}

.modal-title { 
    font-size: 28px; 
    font-weight: 600; 
    margin: 0 0 12px 0; 
    color: #333;
    letter-spacing: -0.5px;
    line-height: 1.2;
    font-family: Georgia, serif;
}

.modal-description { 
    font-size: 13px; 
    color: #666; 
    margin: 0 0 20px 0; 
    line-height: 1.5;
    font-weight: 400;
}

.modal-meta {
    display: flex;
    gap: 30px;
    margin-bottom: 25px;
    padding-bottom: 25px;
    border-bottom: 1px solid #f0f0f0;
}

.meta-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.meta-label {
    font-size: 10px;
    font-weight: 700;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.meta-value {
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.currency {
    font-size: 13px;
    color: #999;
    margin-right: 2px;
}

.modal-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: auto;
    padding-top: 20px;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 0;
    border: 1px solid #ddd;
    border-radius: 2px;
    overflow: hidden;
}

.qty-btn {
    background: white;
    border: none;
    border-right: 1px solid #ddd;
    width: 38px;
    height: 38px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    color: #333;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:last-of-type {
    border-right: none;
}

.qty-btn:hover {
    background: #f9f9f9;
}

#quantity { 
    width: 45px;
    height: 38px;
    text-align: center;
    border: none;
    border-right: 1px solid #ddd;
    font-size: 13px;
    font-weight: 600;
    background: white;
    color: #333;
}

#quantity:focus {
    outline: none;
    background: #f9f9f9;
}

.btn-add-order {
    flex: 1;
    padding: 11px 20px;
    background: #333;
    color: white;
    border: none;
    border-radius: 2px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
}

.btn-add-order:hover {
    background: #000;
}

.btn-add-order:active {
    transform: scale(0.98);
}

/* Responsive */
@media (max-width: 900px) {
    .modal-content {
        max-width: 90%;
    }
}

@media (max-width: 768px) {
    .modal-content {
        max-width: 95%;
        border-radius: 4px;
    }
    
    .modal-body {
        flex-direction: column;
    }
    
    .modal-image-section {
        flex: 0 0 auto;
        padding: 25px 20px;
        border-radius: 4px 4px 0 0;
        border-right: none;
        border-bottom: 1px solid #f0f0f0;
        max-height: 280px;
    }
    
    .modal-content-section {
        flex: 0 0 auto;
        padding: 25px 20px;
    }
    
    .modal-title {
        font-size: 22px;
    }
    
    .modal-meta {
        gap: 25px;
    }
    
    .modal-actions {
        flex-direction: column;
    }
    
    .quantity-control {
        width: 100%;
    }
    
    .btn-add-order {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .modal-content {
        max-width: 100%;
        max-height: 95vh;
    }
    
    .modal-image-section {
        padding: 20px;
        max-height: 240px;
    }
    
    .modal-content-section {
        padding: 20px;
    }
    
    .modal-title {
        font-size: 18px;
    }
    
    .modal-description {
        font-size: 12px;
    }
    
    .modal-meta {
        gap: 20px;
        margin-bottom: 20px;
    }
}
</style>

<?php 
// Include JS
ob_start(); ?>
<script src="assets/js/ajax-handler.js"></script>
<script src="assets/js/cart-ajax.js"></script>
<script src="assets/js/menu-ajax.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('itemModal');
    const modalOverlay = document.getElementById('modalOverlay');
    const closeBtn = document.getElementById('closeModal');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const qtyMinus = document.getElementById('qtyMinus');
    const qtyPlus = document.getElementById('qtyPlus');
    const qtyInput = document.getElementById('quantity');
    let currentItemId = null;
    
    // Close modal
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    closeBtn.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', closeModal);
    
    // Quantity minus
    qtyMinus.addEventListener('click', function(e) {
        e.preventDefault();
        let qty = parseInt(qtyInput.value);
        if (qty > 1) qtyInput.value = qty - 1;
    });
    
    // Quantity plus
    qtyPlus.addEventListener('click', function(e) {
        e.preventDefault();
        let qty = parseInt(qtyInput.value);
        if (qty < 99) qtyInput.value = qty + 1;
    });
    
    // Quantity input validation
    qtyInput.addEventListener('change', function() {
        let qty = parseInt(this.value);
        if (isNaN(qty) || qty < 1) this.value = 1;
        if (qty > 99) this.value = 99;
    });
    
    // Add to cart from modal
    addToCartBtn.addEventListener('click', function() {
        const quantity = parseInt(qtyInput.value);
        if (typeof addToCart === 'function') {
            addToCart(currentItemId, quantity);
            setTimeout(() => {
                closeModal();
            }, 1000);
        }
    });
    
    // Close modal with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });

    // Store current item ID when modal is opened via menu-ajax.js
    const originalOpenModal = window.openItemModal;
    window.openItemModal = function(card) {
        currentItemId = card.dataset.itemId;
        if (originalOpenModal) {
            originalOpenModal(card);
        }
    };
});
</script>
<?php 
$extra_scripts = ob_get_clean();
include 'includes/ui/footer.php'; 
?>?>

