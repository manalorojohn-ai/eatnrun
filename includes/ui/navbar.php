<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database/db.php';

// Get current page for active link highlighting
$current_page = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$script_name = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
$current_page = str_replace($script_name, '', $current_page);
$current_page = trim($current_page, '/');
if (empty($current_page)) $current_page = 'index';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Check if user is admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Initialize variables
$unread_count = 0;
$notifications = [];

// Get notifications if user is logged in
if ($is_logged_in) {
    $user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    
    // Get unread notifications count
    $notif_query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = '$user_id' AND is_read = 0";
    $notif_result = mysqli_query($conn, $notif_query);
    if ($notif_result) {
        $row = mysqli_fetch_assoc($notif_result);
        $unread_count = $row['unread_count'];
        mysqli_free_result($notif_result);
    }

    // Get recent notifications
    $recent_notif_query = "SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 5";
    $recent_notif_result = mysqli_query($conn, $recent_notif_query);
    if ($recent_notif_result) {
        while ($notification = mysqli_fetch_assoc($recent_notif_result)) {
            $notifications[] = $notification;
        }
        mysqli_free_result($recent_notif_result);
    }
}

// Get cart count if user is logged in
$cartCount = 0;
if ($is_logged_in && !$is_admin) {
    $user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    $cart_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = '$user_id'";
    $cart_result = mysqli_query($conn, $cart_query);
    if ($cart_result) {
        $row = mysqli_fetch_assoc($cart_result);
        $cartCount = $row['total'] ?? 0;
        mysqli_free_result($cart_result);
    }
}
?>

<nav class="navbar" role="navigation" aria-label="Main navigation">
    <div class="nav-container">
        <!-- Logo and Brand -->
        <a href="index" class="navbar-brand" aria-label="Eat&Run Home">
            <img src="assets/images/logo.png" alt="Logo" class="logo" onerror="this.onerror=null; this.src='https://via.placeholder.com/40x40?text=E%26R'">
            <span>Eat&Run</span>
        </a>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="navMenu" type="button">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>

        <!-- Navigation Menu Container -->
        <div class="nav-menu" id="navMenu" aria-hidden="true">
            <ul class="nav-links-container" id="navbar-menu">
                <?php if($is_admin): ?>
                    <li id="mobile-background-icons"><a href="admin/dashboard" class="nav-link <?php echo $current_page == 'admin/dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> <span class="nav-text">Dashboard</span>
                    </a></li>
                    <li id="mobile-background-icons"><a href="admin/orders" class="nav-link <?php echo $current_page == 'admin/orders' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-bag"></i> <span class="nav-text">Orders</span>
                    </a></li>
                    <li id="mobile-background-icons"><a href="admin/products" class="nav-link <?php echo $current_page == 'admin/products' ? 'active' : ''; ?>">
                        <i class="fas fa-utensils"></i> <span class="nav-text">Products</span>
                    </a></li>
                    <li id="mobile-background-icons"><a href="admin/users" class="nav-link <?php echo $current_page == 'admin/users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> <span class="nav-text">Users</span>
                    </a></li>
                <?php else: ?>
                    <li id="mobile-background-icons"><a href="index" class="nav-link <?php echo ($current_page == 'index' || $current_page == 'home') ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> <span class="nav-text">Home</span>
                    </a></li>
                    <li id="mobile-background-icons"><a href="menu" class="nav-link <?php echo $current_page == 'menu' ? 'active' : ''; ?>">
                        <i class="fas fa-utensils"></i> <span class="nav-text">Menu</span>
                    </a></li>
                    <li id="mobile-background-icons"><a href="about" class="nav-link <?php echo $current_page == 'about' ? 'active' : ''; ?>">
                        <i class="fas fa-info-circle"></i> <span class="nav-text">About</span>
                    </a></li>
                    <?php if($is_logged_in): ?>
                        <li id="mobile-background-icons"><a href="my_orders" class="nav-link <?php echo ($current_page == 'my_orders' || $current_page == 'orders') ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-list"></i> <span class="nav-text">My Orders</span>
                        </a></li>
                        <li id="mobile-background-icons" class="cart-item"><a href="cart" class="nav-link <?php echo $current_page == 'cart' ? 'active' : ''; ?>">
                            <i class="fas fa-shopping-cart"></i> <span class="nav-text">Cart</span>
                            <?php if($cartCount > 0): ?>
                                <span class="cart-counter"><?php echo $cartCount; ?></span>
                            <?php endif; ?>
                        </a></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if($is_logged_in): ?>
                    <li id="mobile-background-icons" class="mobile-only"><a href="profile" class="nav-link <?php echo $current_page == 'profile' ? 'active' : ''; ?>">
                        <i class="fas fa-user-circle"></i> <span class="nav-text">Profile</span>
                    </a></li>
                    <li id="mobile-background-icons" class="mobile-only"><a href="logout" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span>
                    </a></li>
                <?php else: ?>
                    <li id="mobile-background-icons" class="mobile-only"><a href="login" class="nav-link <?php echo $current_page == 'login' ? 'active' : ''; ?>">
                        <i class="fas fa-sign-in-alt"></i> <span class="nav-text">Login</span>
                    </a></li>
                    <li id="mobile-background-icons" class="mobile-only"><a href="register" class="nav-link <?php echo $current_page == 'register' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i> <span class="nav-text">Register</span>
                    </a></li>
                <?php endif; ?>
            </ul>

            <div class="auth-buttons desktop-only">
                <?php if($is_logged_in): ?>
                    <?php if(!$is_admin): ?>
                        <!-- Notifications -->
                        <div class="dropdown notification-dropdown">
                            <button class="notification-btn" id="notificationButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-counter"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </button>
                            <ul class="dropdown-menu notif-box animated fadeIn" id="notificationDropdown">
                                <li>
                                    <div class="dropdown-title d-flex justify-content-between align-items-center">
                                        <span>Notifications (<?php echo (int)$unread_count; ?> new)</span>
                                        <button type="button" class="btn btn-sm btn-link" onclick="markAllAsRead()">Mark all read</button>
                                    </div>
                                </li>
                                <li>
                                    <div class="notif-scroll">
                                        <div class="notif-center" id="notif-center-list">
                                            <?php if (!empty($notifications)): ?>
                                                <?php foreach ($notifications as $n): ?>
                                                    <a href="<?php echo htmlspecialchars($n['link'] ?? 'notifications'); ?>" class="notification-item <?php echo ($n['is_read'] ? '' : 'unread'); ?>" data-id="<?php echo $n['id']; ?>">
                                                        <div class="notif-icon">
                                                            <i class="fas <?php 
                                                                $ntype = strtolower($n['type'] ?? 'system');
                                                                echo ($ntype === 'order' ? 'fa-receipt' : ($ntype === 'payment' ? 'fa-credit-card' : 'fa-bell')); 
                                                            ?>"></i>
                                                        </div>
                                                        <div class="notif-content">
                                                            <span class="block"><?php echo htmlspecialchars($n['title'] ?? $n['message']); ?></span>
                                                            <span class="time"><?php echo htmlspecialchars($n['created_at']); ?></span>
                                                        </div>
                                                    </a>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="px-4 py-3 text-muted">No new notifications</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                                <li><a class="see-all" href="notifications">See all notifications <i class="fa fa-angle-right"></i></a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <a href="<?php echo $is_admin ? 'admin/dashboard' : 'profile'; ?>" class="login-btn">
                        <i class="fas fa-user<?php echo $is_admin ? '-shield' : ''; ?>"></i> <?php echo $is_admin ? 'Admin' : 'Profile'; ?>
                    </a>
                    <a href="logout" class="register-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login" class="login-btn">Login</a>
                    <a href="register" class="register-btn">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            const menuList = document.getElementById('navbar-menu');
            this.setAttribute('aria-expanded', !isExpanded);
            navMenu.classList.toggle('active');
            if (menuList) menuList.classList.toggle('active');
            this.classList.toggle('active');
            document.body.style.overflow = isExpanded ? '' : 'hidden';
        });

        // Close menu on link click
        navMenu.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            });
        });
    }

    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
});

function markAllAsRead() {
    fetch('mark_all_notifications_read', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.notification-counter');
            if (badge) badge.remove();
            document.querySelectorAll('.notification-item.unread').forEach(i => i.classList.remove('unread'));
        }
    });
}
</script>

