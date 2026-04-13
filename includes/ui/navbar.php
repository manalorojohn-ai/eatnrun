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

<nav class="navbar">
    <div class="nav-container">
        <a href="index" class="navbar-brand">
            <img src="assets/images/logo.png" alt="Logo" class="logo">
            <span>Eat&Run</span>
        </a>

        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>

        <div class="nav-menu" id="navMenu">
            <ul class="nav-links-container" id="navbar-menu">
                <?php if(!$is_admin): ?>
                    <li id="mobile-background-icons"><a href="index" class="nav-link"><i class="fas fa-home"></i> <span class="nav-text">Home</span></a></li>
                    <li id="mobile-background-icons"><a href="menu" class="nav-link"><i class="fas fa-utensils"></i> <span class="nav-text">Menu</span></a></li>
                    <li id="mobile-background-icons"><a href="about" class="nav-link"><i class="fas fa-info-circle"></i> <span class="nav-text">About</span></a></li>
                <?php else: ?>
                    <li id="mobile-background-icons"><a href="admin/dashboard" class="nav-link"><i class="fas fa-chart-line"></i> <span class="nav-text">Admin</span></a></li>
                <?php endif; ?>

                <?php if($is_logged_in): ?>
                    <?php if(!$is_admin): ?>
                        <li id="mobile-background-icons"><a href="cart" class="nav-link"><i class="fas fa-shopping-cart"></i> <span class="nav-text">Cart</span></a></li>
                    <?php endif; ?>
                    <li><a href="profile" class="nav-link"><i class="fas fa-user"></i> <span class="nav-text">Profile</span></a></li>
                    <li><a href="logout" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span></a></li>
                <?php else: ?>
                    <li><a href="login" class="nav-link"><i class="fas fa-sign-in-alt"></i> <span class="nav-text">Login</span></a></li>
                    <li><a href="register" class="nav-link"><i class="fas fa-user-plus"></i> <span class="nav-text">Register</span></a></li>
                <?php endif; ?>
            </ul>
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

