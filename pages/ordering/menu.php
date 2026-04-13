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
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $categories[] = $row;
            }
        }
    } catch (Exception $e) { error_log($e->getMessage()); }
    return $categories;
}

// Function to get menu items
function getMenuItems($conn, $category_name = null) {
    $menu_items = [];
    $where_clause = $category_name ? 
        "WHERE c.name = '" . mysqli_real_escape_string($conn, $category_name) . "' AND m.status = 'available'" : 
        "WHERE m.status = 'available'";
    
    $query = "SELECT m.*, c.name as category_name,
              COALESCE(m.image_path, 'assets/images/default-food.jpg') as image_path
          FROM menu_items m 
          LEFT JOIN categories c ON m.category_id = c.id 
          $where_clause 
          ORDER BY m.name";
    try {
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
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
                    <div class="card-aesthetic animate-in" style="animation-delay: <?php echo $index * 0.05; ?>s" data-category="<?php echo htmlspecialchars($item['category_name']); ?>" data-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>">
                        <div class="card-image-wrapper">
                            <?php 
                            $image_url = (file_exists($item['image_path']) && !empty($item['image_path'])) ? $item['image_path'] : 'assets/images/menu/default-food.jpg';
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 class="food-img"
                                 loading="lazy">
                            <div class="card-hover-overlay">
                                <button class="btn-quick-view" data-item-id="<?php echo $item['id']; ?>">
                                    <i class="fas fa-expand-alt"></i>
                                </button>
                            </div>
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
                                <button type="button" class="btn-add-aesthetic" data-item-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
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

<?php 
// Include JS
ob_start(); ?>
<script src="assets/js/cart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('menuSearch');
    const menuGrid = document.getElementById('menuGrid');
    const cards = document.querySelectorAll('.card-aesthetic');
    
    // Search functionality with aesthetic transitions
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            let visibleCount = 0;
            
            cards.forEach(card => {
                const name = card.dataset.name;
                const desc = card.querySelector('.food-desc').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || desc.includes(searchTerm)) {
                    card.classList.remove('fade-out');
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.classList.add('fade-out');
                    setTimeout(() => { if(card.classList.contains('fade-out')) card.style.display = 'none'; }, 300);
                }
            });
        });
    }

    // Add to cart click synergy
    document.querySelectorAll('.btn-add-aesthetic').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const originalHTML = this.innerHTML;
            
            this.classList.add('loading');
            this.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
            
            if (typeof addToCart === 'function') {
                addToCart(itemId);
                setTimeout(() => {
                    this.classList.remove('loading');
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(() => { this.innerHTML = originalHTML; }, 1500);
                }, 800);
            }
        });
    });
});
</script>
<?php 
$extra_scripts = ob_get_clean();
include 'includes/ui/footer.php'; 
?>?>

