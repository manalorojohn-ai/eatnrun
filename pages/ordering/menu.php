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
    header("Location: menu.php");
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
$page_title = 'Our Menu';
$current_page = 'menu.php';

// Pass extra styles
ob_start(); ?>
<link rel="stylesheet" href="assets/css/menu-enhanced.css">
<?php $extra_styles = ob_get_clean();

include 'includes/ui/header.php';
include 'includes/ui/loader.php';
include 'includes/ui/navbar.php';
?>

<div class="container py-5">
    <div class="page-title">
        <h1>Our Menu</h1>
        <p>Discover our delicious collection of freshly prepared meals, snacks, and drinks.</p>
    </div>

    <div class="filter-section">
        <div class="category-wrapper">
            <a href="menu.php" class="category-link <?php echo !$selected_category ? 'active' : ''; ?>">
                All
            </a>
            <?php foreach ($categories as $category): ?>
                <a href="menu.php?category=<?php echo urlencode($category['name']); ?>" 
                   class="category-link <?php echo ($selected_category === $category['name']) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Notifications -->
    <div class="notifications-container mb-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['warning']); unset($_SESSION['warning']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Menu Grid -->
    <div class="menu-grid">
        <?php if (empty($menu_items)): ?>
            <div class="text-center w-100 py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <p class="lead text-muted">No items found in this category.</p>
            </div>
        <?php else: ?>
            <?php foreach ($menu_items as $item): ?>
                <div class="menu-item" id="item-<?php echo $item['id']; ?>">
                    <div class="menu-item-image">
                        <?php 
                        $image_url = (file_exists($item['image_path']) && !empty($item['image_path'])) ? $item['image_path'] : 'assets/images/menu/default-food.jpg';
                        ?>
                        <img src="<?php echo htmlspecialchars($image_url); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             onerror="this.onerror=null; this.src='assets/images/menu/default-food.jpg'"
                             loading="lazy">
                    </div>
                    <div class="menu-item-info">
                        <div class="category-tag"><?php echo htmlspecialchars($item['category_name']); ?></div>
                        <h3 class="menu-item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="menu-item-description text-muted small mb-3"><?php echo htmlspecialchars($item['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price">₱<?php echo number_format($item['price'], 2); ?></span>
                            <form action="add_to_cart.php" method="POST" class="add-to-cart-form">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php 
// Extra scripts for menu page
ob_start(); ?>
<script src="assets/js/cart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.4s ease';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });
});
</script>
<?php 
$extra_scripts = ob_get_clean();

include 'includes/ui/footer.php'; 
?>
