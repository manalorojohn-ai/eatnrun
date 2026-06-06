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
        <button class="modal-close" id="closeModal">&times;</button>
        <div class="modal-body">
            <div class="modal-image-container">
                <img id="modalImage" src="" alt="Item" class="modal-image">
            </div>
            <div class="modal-info">
                <h2 id="modalName" class="modal-title"></h2>
                <p id="modalDescription" class="modal-description"></p>
                
                <div class="modal-rating">
                    <span class="stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                        <span class="rating-text">4.9 (156 reviews)</span>
                    </span>
                </div>
                
                <div class="modal-price-section">
                    <div class="price-display">
                        <span class="currency">₱</span>
                        <span id="modalPrice" class="price-large">0.00</span>
                    </div>
                </div>
                
                <div class="quantity-selector">
                    <label>Quantity:</label>
                    <div class="qty-control">
                        <button class="qty-btn" id="qtyMinus">−</button>
                        <input type="number" id="quantity" value="1" min="1" max="99">
                        <button class="qty-btn" id="qtyPlus">+</button>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button class="btn-cancel" id="cancelBtn">Cancel</button>
                    <button class="btn-add-to-cart" id="addToCartBtn">Add to Cart</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.item-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000; }
.item-modal.active { display: flex; }
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); z-index: 999; }
.modal-content { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 12px; max-width: 600px; width: 95%; max-height: 90vh; overflow-y: auto; z-index: 1001; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); }
.modal-close { position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 32px; cursor: pointer; color: #999; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; z-index: 1002; }
.modal-close:hover { color: #333; }
.modal-body { display: flex; flex-direction: column; padding: 0; }
.modal-image-container { width: 100%; height: 300px; background: #f5f5f5; border-radius: 12px 12px 0 0; overflow: hidden; }
.modal-image { width: 100%; height: 100%; object-fit: cover; }
.modal-info { padding: 30px 25px; }
.modal-title { font-size: 28px; font-weight: 700; margin: 0 0 15px 0; color: #333; }
.modal-description { font-size: 15px; color: #666; margin: 0 0 20px 0; line-height: 1.5; }
.modal-rating { margin-bottom: 20px; }
.stars { display: flex; align-items: center; gap: 8px; }
.stars i { color: #ffc107; font-size: 16px; }
.rating-text { margin-left: 8px; font-size: 14px; color: #666; }
.modal-price-section { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 25px; }
.price-display { display: flex; align-items: baseline; gap: 5px; }
.currency { font-size: 20px; font-weight: 600; color: #006C3B; }
.price-large { font-size: 36px; font-weight: 700; color: #006C3B; }
.quantity-selector { margin-bottom: 25px; }
.quantity-selector label { display: block; font-weight: 600; margin-bottom: 10px; color: #333; }
.qty-control { display: flex; align-items: center; gap: 10px; background: #f5f5f5; border-radius: 8px; padding: 8px; width: fit-content; }
.qty-btn { background: white; border: 1px solid #ddd; width: 36px; height: 36px; border-radius: 6px; cursor: pointer; font-size: 18px; font-weight: bold; color: #006C3B; transition: all 0.2s; }
.qty-btn:hover { background: #006C3B; color: white; border-color: #006C3B; }
#quantity { width: 50px; height: 36px; text-align: center; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; font-weight: 600; }
#quantity:focus { outline: none; border-color: #006C3B; }
.modal-actions { display: flex; gap: 12px; }
.btn-cancel { flex: 1; padding: 12px 20px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; color: #666; transition: all 0.3s; }
.btn-cancel:hover { background: #e8e8e8; }
.btn-add-to-cart { flex: 1; padding: 12px 20px; background: #006C3B; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-add-to-cart:hover { background: #005530; transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0, 108, 59, 0.3); }
.btn-add-to-cart.loading { opacity: 0.7; pointer-events: none; }
@media (max-width: 600px) { .modal-content { width: 98%; max-height: 95vh; } .modal-info { padding: 20px 15px; } .modal-title { font-size: 22px; } .price-large { font-size: 28px; } }
</style>

<?php 
// Include JS
ob_start(); ?>
<script src="assets/js/cart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('itemModal');
    const modalOverlay = document.getElementById('modalOverlay');
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const qtyMinus = document.getElementById('qtyMinus');
    const qtyPlus = document.getElementById('qtyPlus');
    const qtyInput = document.getElementById('quantity');
    let currentItemId = null;
    
    // Open modal ONLY when clicking card (not Add button)
    document.querySelectorAll('.card-aesthetic').forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't open modal if clicked on the Add button
            if (e.target.closest('.btn-add-aesthetic')) {
                return;
            }
            
            const itemId = this.dataset.itemId;
            const itemName = this.querySelector('.food-name').textContent;
            const itemImage = this.querySelector('.food-img').src;
            const itemDescription = this.querySelector('.food-desc').textContent;
            const itemPrice = this.querySelector('.price-value').textContent;
            
            currentItemId = itemId;
            document.getElementById('modalName').textContent = itemName;
            document.getElementById('modalImage').src = itemImage;
            document.getElementById('modalDescription').textContent = itemDescription;
            document.getElementById('modalPrice').textContent = itemPrice;
            document.getElementById('quantity').value = 1;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });
    
    // Close modal
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', closeModal);
    
    // Quantity minus
    qtyMinus.addEventListener('click', function() {
        let qty = parseInt(qtyInput.value);
        if (qty > 1) qtyInput.value = qty - 1;
    });
    
    // Quantity plus
    qtyPlus.addEventListener('click', function() {
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
            this.classList.add('loading');
            const originalText = this.textContent;
            this.textContent = 'Adding...';
            addToCart(currentItemId, quantity);
            setTimeout(() => {
                this.textContent = 'Added!';
                setTimeout(() => {
                    this.textContent = originalText;
                    this.classList.remove('loading');
                    closeModal();
                }, 1000);
            }, 500);
        }
    });
    
    // Close modal with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
    
    // Direct Add button functionality (without opening modal)
    document.querySelectorAll('.btn-add-aesthetic').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const itemId = this.dataset.itemId;
            if (typeof addToCart === 'function') {
                addToCart(itemId, 1);
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                }, 1500);
            }
        });
    });
});
</script>
<?php 
$extra_scripts = ob_get_clean();
include 'includes/ui/footer.php'; 
?>?>

