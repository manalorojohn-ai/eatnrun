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

<!-- Item Detail Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden; background: #fff;">
            <div class="modal-header" style="background: #f8f9fa; border: none; padding: 20px 30px;">
                <h5 class="modal-title" id="itemModalLabel" style="color: #333; font-weight: 700; font-size: 1.2rem;">Item Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 1.5rem;"></button>
            </div>
            <div class="modal-body" style="padding: 0;">
                <div class="row g-0">
                    <!-- Left: Image Section -->
                    <div class="col-md-6" style="background: #f8f9fa;">
                        <div class="p-4">
                            <div class="main-image-wrapper" style="border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                                <img id="modalImage" src="" alt="" class="img-fluid" style="width: 100%; height: 450px; object-fit: cover;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right: Info Section -->
                    <div class="col-md-6" style="padding: 40px;">
                        <span id="modalCategory" class="badge" style="background: rgba(0, 108, 59, 0.1); color: #006C3B; padding: 8px 16px; border-radius: 50px; font-weight: 600; font-size: 0.9rem; display: inline-block; margin-bottom: 15px;"></span>
                        
                        <h2 id="modalName" style="color: #2d3436; font-weight: 800; font-size: 2.2rem; margin-bottom: 10px; line-height: 1.2;"></h2>
                        
                        <p id="modalDescription" style="color: #636e72; font-size: 1rem; line-height: 1.6; margin-bottom: 25px;"></p>
                        
                        <p id="modalPrice" style="font-weight: 800; font-size: 2.5rem; color: #006C3B; margin: 0 0 30px 0;"></p>
                        
                        <!-- Quantity Control -->
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #2d3436; font-size: 1rem; margin-bottom: 10px;">Quantity</label>
                            <div class="quantity-control" style="display: flex; align-items: center; gap: 15px; background: #f8f9fa; padding: 10px 15px; border-radius: 12px; width: fit-content;">
                                <button type="button" id="qtyDecrease" class="qty-btn" style="width: 50px; height: 50px; border-radius: 10px; border: none; background: #006C3B; color: white; font-size: 1.6rem; font-weight: 800; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; cursor: pointer;">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="modalQuantity" value="1" min="1" style="width: 90px; height: 50px; text-align: center; font-size: 1.8rem; font-weight: 800; border: 2px solid #e0e0e0; border-radius: 10px; color: #006C3B; background: white;">
                                <button type="button" id="qtyIncrease" class="qty-btn" style="width: 50px; height: 50px; border-radius: 10px; border: none; background: #006C3B; color: white; font-size: 1.6rem; font-weight: 800; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; cursor: pointer;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Add to Cart Button -->
                        <button type="button" id="modalAddBtn" style="background: linear-gradient(135deg, #006C3B 0%, #00A65A 100%); color: white; border: none; padding: 18px 40px; border-radius: 12px; font-weight: 700; font-size: 1.1rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 8px 24px rgba(0, 108, 59, 0.3); transition: all 0.3s ease; cursor: pointer;">
                            <i class="fas fa-shopping-basket"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                    <div class="card-aesthetic animate-in" style="animation-delay: <?php echo $index * 0.05; ?>s; cursor: pointer;" data-category="<?php echo htmlspecialchars($item['category_name']); ?>" data-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>" data-image="<?php echo htmlspecialchars($item['image_path']); ?>" data-description="<?php echo htmlspecialchars($item['description']); ?>" data-price="<?php echo htmlspecialchars(number_format($item['price'], 2)); ?>" data-item-id="<?php echo $item['id']; ?>">
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
</main>

<?php 
// Include JS
ob_start(); ?>
<script src="assets/js/cart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('menuSearch');
    const menuGrid = document.getElementById('menuGrid');
    const cards = document.querySelectorAll('.card-aesthetic');
    let currentItemId = null;
    
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

    // Card click handler to open modal
    document.querySelectorAll('.card-aesthetic').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.btn-add-aesthetic')) return;
            
            const name = card.querySelector('.food-name').textContent;
            const category = card.dataset.category;
            const description = card.dataset.description;
            const price = card.dataset.price;
            const image = card.dataset.image;
            currentItemId = card.dataset.itemId;
            
            // Populate modal
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalCategory').textContent = category;
            document.getElementById('modalDescription').textContent = description;
            document.getElementById('modalPrice').textContent = '₱' + price;
            document.getElementById('modalImage').src = image;
            document.getElementById('modalImage').alt = name;
            document.getElementById('modalQuantity').value = 1;
            
            const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
            itemModal.show();
        });
    });

    // Quantity controls
    const qtyInput = document.getElementById('modalQuantity');
    const qtyIncrease = document.getElementById('qtyIncrease');
    const qtyDecrease = document.getElementById('qtyDecrease');

    if (qtyIncrease) {
        qtyIncrease.addEventListener('click', function() {
            let current = parseInt(qtyInput.value);
            qtyInput.value = current + 1;
        });
    }

    if (qtyDecrease) {
        qtyDecrease.addEventListener('click', function() {
            let current = parseInt(qtyInput.value);
            if (current > 1) {
                qtyInput.value = current - 1;
            }
        });
    }

    // Modal Add to Cart button
    document.getElementById('modalAddBtn')?.addEventListener('click', function() {
        if (currentItemId && typeof addToCart === 'function') {
            const originalHTML = this.innerHTML;
            this.classList.add('loading');
            this.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Adding...';
            
            // Call addToCart for each quantity
            const qty = parseInt(qtyInput.value);
            for (let i = 0; i < qty; i++) {
                addToCart(currentItemId);
            }
            
            setTimeout(() => {
                this.classList.remove('loading');
                this.innerHTML = '<i class="fas fa-check"></i> Added!';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-shopping-basket"></i> Add to Cart';
                    const modal = bootstrap.Modal.getInstance(document.getElementById('itemModal'));
                    if (modal) modal.hide();
                }, 1000);
            }, 800);
        }
    });

    // Add to Cart button on card (still works)
    document.querySelectorAll('.btn-add-aesthetic').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
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

