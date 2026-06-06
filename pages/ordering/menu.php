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
                    <div class="card-aesthetic animate-in" style="animation-delay: <?php echo $index * 0.05; ?>s" data-category="<?php echo htmlspecialchars($item['category_name']); ?>" data-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>" data-image="<?php echo htmlspecialchars($item['image_path']); ?>" data-description="<?php echo htmlspecialchars($item['description']); ?>" data-price="<?php echo htmlspecialchars(number_format($item['price'], 2)); ?>">
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

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #006C3B 0%, #00A65A 100%); border: none; padding: 20px 24px;">
                <h5 class="modal-title" id="quickViewModalLabel" style="color: white; font-weight: 700; font-size: 1.4rem;">🍽️ Item Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body" style="padding: 32px;">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="quick-view-image-wrapper" style="border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.08);">
                            <img id="quickViewImage" src="" alt="" class="img-fluid" style="width:100%;height:320px;object-fit:cover;">
                        </div>
                    </div>
                    <div class="col-md-6 d-flex flex-column">
                        <span id="quickViewCategory" class="badge" style="background: rgba(0, 108, 59, 0.1); color: #006C3B; padding: 8px 16px; border-radius: 50px; font-weight: 600; width: fit-content; margin-bottom: 16px;"></span>
                        <h3 id="quickViewName" style="color: #2d3436; font-weight: 800; font-size: 2rem; margin-bottom: 12px; line-height: 1.2;"></h3>
                        <p id="quickViewDescription" style="color: #636e72; font-size: 1.05rem; line-height: 1.6; margin-bottom: 24px; flex-grow: 1;"></p>
                        <div class="quick-view-footer d-flex align-items-center justify-content-between gap-3">
                            <p id="quickViewPrice" style="font-weight: 800; font-size: 2rem; color: #006C3B; margin: 0;"></p>
                            <button type="button" class="btn-add-to-cart-modal" style="background: linear-gradient(135deg, #006C3B 0%, #00A65A 100%); color: white; border: none; padding: 14px 32px; border-radius: 16px; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; box-shadow: 0 8px 24px rgba(0, 108, 59, 0.25); transition: all 0.3s ease;">
                                <i class="fas fa-shopping-basket"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-add-to-cart-modal:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0, 108, 59, 0.35);
}
.btn-add-to-cart-modal:active {
    transform: translateY(-1px);
}
.quick-view-image-wrapper {
    transition: transform 0.3s ease;
}
.quick-view-image-wrapper:hover {
    transform: scale(1.02);
}
</style>

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

    // Quick View Button Handler
    let currentItemId = null;
    document.querySelectorAll('.btn-quick-view').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const card = this.closest('.card-aesthetic');
            const name = card.querySelector('.food-name').textContent;
            const category = card.dataset.category;
            const description = card.dataset.description;
            const price = card.dataset.price;
            const image = card.dataset.image;
            currentItemId = this.dataset.itemId;
            
            document.getElementById('quickViewName').textContent = name;
            document.getElementById('quickViewCategory').textContent = category;
            document.getElementById('quickViewDescription').textContent = description;
            document.getElementById('quickViewPrice').textContent = `₱${price}`;
            document.getElementById('quickViewImage').src = image;
            document.getElementById('quickViewImage').alt = name;
            
            const quickViewModal = new bootstrap.Modal(document.getElementById('quickViewModal'));
            quickViewModal.show();
        });
    });

    // Modal Add to Cart Button Handler
    document.querySelector('.btn-add-to-cart-modal')?.addEventListener('click', function() {
        if (currentItemId && typeof addToCart === 'function') {
            const originalHTML = this.innerHTML;
            this.classList.add('loading');
            this.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Adding...';
            addToCart(currentItemId);
            setTimeout(() => {
                this.classList.remove('loading');
                this.innerHTML = '<i class="fas fa-check"></i> Added!';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-shopping-basket"></i> Add to Cart';
                }, 1500);
            }, 800);
        }
    });

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

