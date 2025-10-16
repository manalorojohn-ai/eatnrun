<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once 'config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Check if user is admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Initialize variables
$notifications_count = 0;
$notifications = [];
$unread_count = 0;

// Get notifications if user is logged in
if ($is_logged_in) {
    $user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    
        // Get unread notifications count
        $notif_query = "SELECT COUNT(*) as unread_count FROM notifications 
                        WHERE user_id = '$user_id' AND is_read = 0";
        $notif_result = mysqli_query($conn, $notif_query);
        
        if ($notif_result) {
            $row = mysqli_fetch_assoc($notif_result);
            $unread_count = $row['unread_count'];
            mysqli_free_result($notif_result);
        }

        // Get recent notifications
        $recent_notif_query = "SELECT * FROM notifications 
                             WHERE user_id = '$user_id' 
                             ORDER BY created_at DESC 
                             LIMIT 5";
        $recent_notif_result = mysqli_query($conn, $recent_notif_query);
        
        if ($recent_notif_result) {
            while ($notification = mysqli_fetch_assoc($recent_notif_result)) {
                $notifications[] = $notification;
            }
            mysqli_free_result($recent_notif_result);
    }
}

// Get cart count if user is logged in
$cart_count = 0;
if ($is_logged_in) {
    $user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    $cart_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = '$user_id'";
    $cart_result = mysqli_query($conn, $cart_query);
    
    if ($cart_result) {
        $row = mysqli_fetch_assoc($cart_result);
        $cart_count = $row['total'] ?? 0;
        mysqli_free_result($cart_result);
    }
}

$cartCount = isset($_SESSION['user_id']) ? $cart_count : 0;
?>

<style>
    /* Reset default styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Navbar container */
    .navbar {
        background: rgba(255, 255, 255, 0.98);
        padding: 1rem 2rem;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        -webkit-backdrop-filter: blur(15px);
        backdrop-filter: blur(15px);
        transition: all 0.3s ease;
        border-bottom: 1px solid rgba(0, 108, 59, 0.1);
    }

    .navbar.scrolled {
        padding: 0.8rem 2rem;
        background: rgba(255, 255, 255, 0.99);
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.15);
    }

    /* Main navigation container */
    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        position: relative;
        height: 60px;
    }

    /* Enhanced Mobile Menu Toggle Design */
    .mobile-menu-toggle {
        display: none; /* hidden by default; shown only in burger/mobile mode */
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        border: 2px solid rgba(255, 255, 255, 0.2);
        cursor: pointer;
        padding: 12px;
        border-radius: 16px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
        box-shadow: 0 4px 12px rgba(0, 108, 59, 0.3);
        position: relative;
        overflow: hidden;
        outline: none;
    }

    /* Active state visuals (visibility handled by media queries) */
    .mobile-menu-toggle.active {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        border-color: rgba(255, 255, 255, 0.4);
        box-shadow: 0 6px 20px rgba(0, 108, 59, 0.4);
        transform: scale(1.05);
    }

    /* Hover Effects */
    .mobile-menu-toggle:hover {
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 108, 59, 0.4);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .mobile-menu-toggle:active {
        transform: scale(0.95);
        box-shadow: 0 2px 8px rgba(0, 108, 59, 0.3);
    }

    .mobile-menu-toggle:focus-visible {
        box-shadow: 0 0 0 3px rgba(0, 108, 59, 0.35), 0 4px 12px rgba(0, 108, 59, 0.3);
    }

    /* Enhanced Hamburger Lines */
    .hamburger-line {
        width: 28px;
        height: 4px;
        background: white;
        margin: 3px 0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 3px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    /* Animated X Transformation */
    .mobile-menu-toggle.active .hamburger-line:nth-child(1) {
        transform: rotate(45deg) translate(8px, 8px);
        background: white;
    }

    .mobile-menu-toggle.active .hamburger-line:nth-child(2) {
        opacity: 0;
        transform: scale(0);
    }

    .mobile-menu-toggle.active .hamburger-line:nth-child(3) {
        transform: rotate(-45deg) translate(8px, -8px);
        background: white;
    }

    /* Shimmer Effect */
    .mobile-menu-toggle::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.2),
            transparent
        );
        transition: left 0.5s;
    }

    .mobile-menu-toggle:hover::before {
        left: 100%;
    }

    /* Pulse Animation for Active State */
    .mobile-menu-toggle.active::after {
        content: '';
        position: absolute;
        top: -4px;
        left: -4px;
        right: -4px;
        bottom: -4px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 20px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 1;
        }
        50% {
            transform: scale(1.1);
            opacity: 0.7;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Respect reduced-motion preferences */
    @media (prefers-reduced-motion: reduce) {
        .mobile-menu-toggle,
        .hamburger-line,
        .nav-menu,
        .nav-link,
        .login-btn,
        .register-btn {
            transition: none !important;
            animation: none !important;
        }
    }

    /* Android-Optimized Navigation Menu */
    .nav-menu {
        display: flex;
        align-items: center;
        gap: 2rem;
        flex: 1;
        justify-content: space-between;
    }

    /* Android-Optimized Touch Targets */
    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 12px 16px;
        text-decoration: none;
        color: var(--text-color);
        border-radius: 8px;
        transition: all 0.3s ease;
        min-height: 44px; /* Android minimum touch target */
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
        position: relative;
    }

    .nav-link:hover {
        background: rgba(0, 108, 59, 0.1);
        color: var(--primary-color);
        transform: translateY(-1px);
    }

    .nav-link:active {
        transform: translateY(0);
        background: rgba(0, 108, 59, 0.2);
    }

    .nav-link.active {
        background: var(--primary-color);
        color: white;
        font-weight: 600;
    }

    .nav-link i {
        font-size: 1.1rem;
        transition: transform 0.3s ease;
    }

    .nav-link:hover i {
        transform: scale(1.1);
    }

    /* Android-Optimized Cart Counter */
    .cart-counter {
        background: var(--danger-color);
        color: white;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 12px;
        min-width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: -4px;
        right: -8px;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Android-Optimized Auth Buttons */
    .auth-buttons {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .login-btn, .register-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        min-height: 44px; /* Android minimum touch target */
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
        border: 2px solid transparent;
    }

    .login-btn {
        background: transparent;
        color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .login-btn:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 108, 59, 0.3);
    }

    .register-btn {
        background: var(--primary-color);
        color: white;
    }

    .register-btn:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 108, 59, 0.3);
    }

    .login-btn:active, .register-btn:active {
        transform: translateY(0);
    }

    /* Logo and brand */
    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        transition: transform 0.3s ease;
        flex-shrink: 0;
    }

    .navbar-brand:hover {
        transform: translateY(-1px);
    }

    .navbar-brand img {
        height: 40px;
        width: auto;
        transition: transform 0.3s ease;
    }

    .navbar-brand:hover img {
        transform: scale(1.05);
    }

    .navbar-brand span {
        font-size: 1.5rem;
        font-weight: 700;
        color: #006C3B;
        letter-spacing: -0.3px;
    }

    /* Navigation links */
    .nav-links {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        list-style: none;
        margin: 0;
        padding: 0;
        flex-grow: 1;
        justify-content: center;
    }

    .nav-link {
        text-decoration: none;
        color: #333;
        font-weight: 500;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
        overflow: hidden;
        white-space: nowrap;
    }

    .nav-link i {
        font-size: 1.1rem;
        transition: transform 0.3s ease;
    }

    .nav-link:hover i {
        transform: translateY(-2px);
    }

    .nav-link:hover {
        background: rgba(0, 108, 59, 0.1);
        color: #006C3B;
        transform: translateY(-1px);
    }

    .nav-link.active {
        background: #006C3B;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 108, 59, 0.2);
    }

    .nav-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.2),
            transparent
        );
        transition: 0.5s;
    }

    .nav-link:hover::before {
        left: 100%;
    }

    /* Auth buttons */
    .auth-buttons {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-shrink: 0;
    }

    .login-btn,
    .register-btn {
        padding: 0.75rem 1.25rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        white-space: nowrap;
    }

    .login-btn {
        color: #006C3B;
        border: 2px solid #006C3B;
        background: transparent;
    }

    .register-btn {
        background: #006C3B;
        color: white;
        border: 2px solid #006C3B;
    }

    .login-btn:hover,
    .register-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 108, 59, 0.15);
    }

    .login-btn:hover {
        background: rgba(0, 108, 59, 0.1);
        color: #006C3B;
    }

    .register-btn:hover {
        background: #005530;
        color: white;
    }

    /* Cart counter */
    .cart-counter {
        background: #ff4444;
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        margin-left: 0.5rem;
        min-width: 20px;
        height: 20px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 6px rgba(255, 68, 68, 0.3);
        animation: pulse 2s infinite;
        border: 2px solid white;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
        }
    }

    /* Notification styles */
    .notification-dropdown { position: relative; }

    /* New Bootstrap-like notif dropdown */
    #notificationDropdown.dropdown-menu {
        width: 380px;
        max-width: 90vw;
        border-radius: 15px;
        border: 1px solid rgba(0,0,0,.08);
        box-shadow: 0 10px 30px rgba(0,0,0,.15);
        padding: 0;
        overflow: hidden;
        right: 0;
        left: auto;
        background: #ffffff !important;
        margin-top: 0.5rem;
    }

    .notif-box .dropdown-title {
        padding: .75rem 1rem;
        background: #f8f9fa !important; /* solid */
        border-bottom: 1px solid #eef0f2;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .notif-scroll { max-height: 320px; overflow-y: auto; }
    .notif-scroll::-webkit-scrollbar { width: 8px; }
    .notif-scroll::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 8px; }
    .notif-scroll::-webkit-scrollbar-track { background: transparent; }

    .notif-center { padding: .25rem 0; }
    .notif-center .notification-item {
        display: flex;
        gap: .75rem;
        align-items: flex-start;
        padding: 1rem;
        color: inherit;
        text-decoration: none;
        border-bottom: 1px solid #f1f3f5;
        transition: all 0.3s ease;
    }
    .notif-center .notification-item:last-child { border-bottom: 0; }
    .notif-center .notification-item:hover { 
        background: #f8f9fa; 
        transform: translateX(2px);
    }
    .notif-center .notification-item.unread { 
        background: #f4fff6; 
        border-left: 3px solid #006C3B;
    }

    .notif-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eef6ff;
        color: #0d6efd;
        flex: 0 0 40px;
        font-size: 1.1rem;
    }
    .notification-item[data-type="order"] .notif-icon { background:#e8fff1; color:#198754; }
    .notification-item[data-type="payment"] .notif-icon { background:#e7f1ff; color:#0d6efd; }
    .notification-item[data-type="system"] .notif-icon { background:#f1f8ff; color:#0d6efd; }

    .notif-content { padding: 0; line-height: 1.2; }
    .notif-content .block { display: block; font-weight: 600; color: #212529; }
    .notif-content .time { display: block; font-size: .8rem; color: #6c757d; margin-top: .25rem; }

    .notif-box .see-all { 
        display: block; 
        padding: 1rem; 
        text-align: center; 
        border-top: 1px solid #eef0f2; 
        color: #006C3B; 
        text-decoration: none; 
        position: sticky; 
        bottom: 0; 
        background: #ffffff !important; 
        z-index: 1; 
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .notif-box .see-all:hover { 
        background: #f8f9fa; 
        color: #005530;
    }

    /* Simple fade-in for .animated.fadeIn at full opacity */
    .animated.fadeIn { animation: fadeIn .12s ease-in forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

    .nowrap { white-space: nowrap; }
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        line-clamp: 2; /* standard */
        overflow: hidden;
    }

    .notification-btn {
        background: none;
        border: none;
        padding: 0.75rem;
        cursor: pointer;
        position: relative;
        color: #333;
        border-radius: 10px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .notification-btn:hover {
        background: rgba(0, 108, 59, 0.1);
        color: #006C3B;
    }

    .notification-counter {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ff4444;
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 50%;
        min-width: 20px;
        height: 20px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(255, 68, 68, 0.3);
    }

    /* hide by default; show when .show present (works with BS 5) */
    #notificationDropdown { display: none; position: absolute; }
    #notificationDropdown.show { display: block; }

    /* Responsive design */
    @media (max-width: 1024px) {
        .nav-container {
            padding: 0 1rem;
        }
    }

    /* Android-Optimized Responsive Design */
    @media (max-width: 768px) {
        .mobile-menu-toggle {
            display: flex !important; /* ensure visible on all pages in mobile */
            margin-left: auto;
        }

        .nav-menu {
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            background: #ffffff;
            flex-direction: column;
            padding: 1.5rem 1rem 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-100%);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            gap: 0.75rem;
            height: calc(100vh - 80px);
            overflow-y: auto;
        }

        .nav-menu.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }

        /* Ensure nav links show when menu is open */
        .nav-menu.active .nav-links {
            display: flex;
            position: static;
        padding: 0;
            box-shadow: none;
        }

        .nav-links { flex-direction: column; width: 100%; gap: 0.25rem; }

        .nav-links li {
            width: 100%;
        }

        .nav-link {
            width: 100%;
            justify-content: flex-start;
            padding: 18px 20px; /* bigger tap target */
            font-size: 1.05rem;
            border-radius: 12px;
        }

        .nav-link i { font-size: 1.2rem; }

        .auth-buttons {
            flex-direction: column;
            width: 100%;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }

        .login-btn, .register-btn {
            width: 100%;
            justify-content: center;
            padding: 16px 20px;
            font-size: 1.05rem;
            border-radius: 12px;
        }

        /* Android-specific optimizations */
        .nav-link:active {
            background: rgba(0, 108, 59, 0.15);
            transform: scale(0.98);
        }

        .login-btn:active, .register-btn:active {
            transform: scale(0.98);
        }

        /* Improve touch targets for Android */
        .nav-link, .login-btn, .register-btn {
            min-height: 48px;
        }

        /* Notifications dropdown fit on mobile */
        #notificationDropdown.dropdown-menu { 
            width: 94vw; 
            max-width: 94vw; 
            right: 3vw; 
            left: 3vw; 
            margin-top: 0.5rem;
        }
        .notif-scroll { max-height: 50vh; }
    }

    @media (max-width: 480px) {
        .nav-container {
            padding: 0 0.75rem;
        }

        .navbar-brand {
            font-size: 1.1rem;
        }

        .logo {
            height: 32px;
        }

        .nav-link {
            padding: 18px 20px;
            font-size: 1.2rem;
        }

        .login-btn, .register-btn {
            padding: 18px 24px;
            font-size: 1.2rem;
        }

        /* Extra large touch targets for small Android devices */
        .nav-link, .login-btn, .register-btn {
            min-height: 52px;
        }

        /* Enhanced mobile menu toggle for small screens */
        .mobile-menu-toggle {
            width: 52px;
            height: 52px;
            padding: 10px;
        }

        .hamburger-line {
            width: 26px;
            height: 3px;
        }
    }

    /* Special styling for login page (position only; visibility still controlled by breakpoints) */
    body.login-page .mobile-menu-toggle {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        box-shadow: 0 6px 20px rgba(0, 108, 59, 0.4);
    }

    /* Layering for all pages */
    .mobile-menu-toggle {
        z-index: 1000;
        position: relative;
    }
    /* Desktop default: show nav links inline */
    .nav-links { position: static; box-shadow: none; }
    .mobile-menu-btn { display: block; }
    .auth-buttons { margin-left: auto; }

    /* Add padding to body to account for fixed navbar */
    body {
        padding-top: 90px;
        transition: padding-top 0.3s ease;
    }

    body.scrolled {
        padding-top: 80px;
    }

    /* Add smooth scrolling to page */
    html {
        scroll-behavior: smooth;
    }

    .notification-bell {
        position: relative;
        cursor: pointer;
    }

    .notification-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ff4444;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(255, 68, 68, 0.3);
    }

    .notification-link {
        text-decoration: none;
        color: inherit;
    }
</style>

<nav class="navbar" role="navigation" aria-label="Main navigation">
    <div class="nav-container">
        <!-- Logo and Brand -->
        <a href="index.php" class="navbar-brand" aria-label="Eat&Run Home">
            <img src="assets/images/logo.png" alt="Eat&Run Logo" class="logo">
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
            <!-- Navigation Links -->
                <?php if($is_admin): ?>
            <!-- Admin Navigation -->
            <ul class="nav-links">
                <li><a href="admin/dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a></li>
                <li><a href="admin/orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-bag"></i> Orders
                </a></li>
                <li><a href="admin/products.php" class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i> Products
                </a></li>
                <li><a href="admin/users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a></li>
            </ul>
                <?php else: ?>
            <!-- Regular User Navigation -->
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a></li>
                <li><a href="menu.php" class="nav-link <?php echo $current_page == 'menu.php' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i> Menu
                </a></li>
                <li><a href="about.php" class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>">
                    <i class="fas fa-info-circle"></i> About
                </a></li>
                    <?php if($is_logged_in): ?>
                    <li><a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>" aria-label="My Orders">
                        <i class="fas fa-clipboard-list" aria-hidden="true"></i> My Orders
                    </a></li>
                    <li><a href="ratings.php" class="nav-link <?php echo $current_page == 'ratings.php' ? 'active' : ''; ?>" aria-label="My Ratings">
                        <i class="fas fa-star" aria-hidden="true"></i> My Ratings
                    </a></li>
                    <li class="cart-item"><a href="cart.php" class="nav-link <?php echo $current_page == 'cart.php' ? 'active' : ''; ?>" aria-label="Shopping Cart">
                        <i class="fas fa-shopping-cart no-translateY" aria-hidden="true"></i> Cart
                                <?php if($cartCount > 0): ?>
                            <span class="cart-counter" aria-label="<?php echo $cartCount; ?> items in cart"><?php echo $cartCount; ?></span>
                                <?php endif; ?>
                    </a></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>

        <!-- Auth Buttons and Notifications -->
        <div class="auth-buttons">
                <?php if($is_logged_in): ?>
                    <?php if(!$is_admin): ?>
                    <!-- Notifications -->
                    <div class="dropdown notification-dropdown">
                        <button class="notification-btn" id="notificationButton" aria-label="Notifications" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="notification-counter"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </button>
                        <ul class="dropdown-menu notif-box animated fadeIn" id="notificationDropdown" aria-labelledby="notificationButton">
                            <li>
                                <div class="dropdown-title text-left d-flex justify-content-between align-items-center">
                                    <span>You have <?php echo (int)$unread_count; ?> new notifications.</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="(function(){ if(window.markAllAsRead) markAllAsRead(); })()">Mark all read</button>
                                    </div>
                            </li>
                            <li>
                                <div class="notif-scroll">
                                    <div class="notif-center">
                                <?php if (!empty($notifications)): ?>
                                    <?php foreach ($notifications as $n): ?>
                                        <?php 
                                            $ntype = strtolower($n['type'] ?? ($n['notification_type'] ?? 'system'));
                                            $icon = 'fa-bell';
                                                    if ($ntype === 'order') { $icon = 'fa-receipt'; }
                                                    elseif ($ntype === 'payment') { $icon = 'fa-credit-card'; }
                                                    elseif ($ntype === 'system') { $icon = 'fa-info-circle'; }
                                            $isRead = intval($n['is_read'] ?? 0) === 1;
                                        ?>
                                                <a href="<?php echo htmlspecialchars($n['link'] ?? '#'); ?>" class="notification-item <?php echo $isRead ? '' : 'unread'; ?>" data-type="<?php echo htmlspecialchars($ntype); ?>" data-id="<?php echo $n['id']; ?>" aria-label="<?php echo htmlspecialchars($n['title'] ?? ($n['message'] ?? 'Notification')); ?>" title="<?php echo htmlspecialchars($n['title'] ?? ($n['message'] ?? 'Notification')); ?>">
                                                    <div class="notif-icon"><i class="fas <?php echo $icon; ?>"></i></div>
                                                    <div class="notif-content">
                                                        <span class="block"><?php echo htmlspecialchars($n['title'] ?? ($n['message'] ?? 'Notification')); ?></span>
                                                        <span class="time"><i><?php echo htmlspecialchars($n['created_at'] ?? ''); ?></i></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                            <div class="px-4 py-3 text-muted">No new notifications</div>
                    <?php endif; ?>
                            </div>
                            </div>
                            </li>
                            <li>
                                <a class="see-all" href="notifications.php" aria-label="See all notifications" title="See all notifications">See all notifications<i class="fa fa-angle-right"></i></a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                
                <!-- Profile and Logout -->
                <a href="<?php echo $is_admin ? 'admin/profile.php' : 'profile.php'; ?>" class="login-btn">
                    <i class="fas fa-user<?php echo $is_admin ? '-shield' : ''; ?>"></i>
                            <?php echo $is_admin ? 'Admin' : 'Profile'; ?>
                        </a>
                <a href="logout.php" class="register-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                <?php else: ?>
                <a href="login.php" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="register.php" class="register-btn">
                    <i class="fas fa-user-plus"></i> Register
                </a>
                <?php endif; ?>
        </div>
        </div>
    </div>
</nav>

<script>
    // Android-Optimized Mobile Menu Toggle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const navMenu = document.getElementById('navMenu');
        const notifBtn = document.getElementById('notificationButton');
        const notifDropdown = document.getElementById('notificationDropdown');
        
        if (mobileMenuToggle && navMenu) {
            function toggleMobileMenu() {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                
                // Toggle menu visibility
                navMenu.classList.toggle('active');
                this.classList.toggle('active');
                
                // Update ARIA attributes for accessibility
                this.setAttribute('aria-expanded', !isExpanded);
                navMenu.setAttribute('aria-hidden', isExpanded ? 'true' : 'false');
                
                // Prevent body scroll when menu is open
                if (!isExpanded) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }

            mobileMenuToggle.addEventListener('click', toggleMobileMenu);
            mobileMenuToggle.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleMobileMenu.call(mobileMenuToggle);
                }
            });

            // Close menu when clicking on nav links
            navMenu.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function() {
                    navMenu.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                    navMenu.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                });
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!navMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                    navMenu.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                    navMenu.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                }
            });

            // Handle escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && navMenu.classList.contains('active')) {
                    navMenu.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                    navMenu.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                }
            });

            // Close on resize back to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768 && navMenu.classList.contains('active')) {
                    navMenu.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                    navMenu.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                }
            });
        }

        // --- Realtime (polling) notifications ---
        let notifPollTimer = null;
        const POLL_INTERVAL_MS = 5000; // 5s

        function startNotificationPolling() {
            if (notifPollTimer) return;
            notifPollTimer = setInterval(() => {
                updateNotificationCountOnly();
            }, POLL_INTERVAL_MS);
        }

        function stopNotificationPolling() {
            if (notifPollTimer) {
                clearInterval(notifPollTimer);
                notifPollTimer = null;
            }
        }

        function updateNotificationCountOnly() {
            fetch('get_notification_count.php')
                .then(r => r.ok ? r.json() : { count: 0 })
                .then(({ count }) => {
                    const counterEls = document.querySelectorAll('.notification-counter, .notification-count');
                    counterEls.forEach(el => {
                        if (!el) return;
                        if (count && count > 0) {
                            el.textContent = count;
                            el.style.display = '';
                        } else {
                            el.style.display = 'none';
                        }
                    });
                })
                .catch(() => {});
        }

        function loadNotificationsList() {
            if (!notifDropdown) return;
            const list = notifDropdown.querySelector('.notification-list');
            if (list) {
                list.innerHTML = '<div style="padding:1rem;text-align:center;opacity:.7;">Loading…</div>';
            }
            fetch('fetch_notifications.php?limit=10')
                .then(r => r.ok ? r.json() : { notifications: [], unread_count: 0 })
            .then(data => {
                    const items = (data && Array.isArray(data.notifications)) ? data.notifications : [];
                    const unread = (data && typeof data.unread_count === 'number') ? data.unread_count : 0;
                    updateNotificationBadge(unread);
                    renderNotificationItems(items);
                })
                .catch(() => {
                    if (list) {
                        list.innerHTML = '<div style="padding:1rem;text-align:center;color:#c00;">Failed to load notifications</div>';
                    }
                });
        }

        function updateNotificationBadge(count) {
            const counterEls = document.querySelectorAll('.notification-counter, .notification-count');
            counterEls.forEach(el => {
                if (!el) return;
                if (count && count > 0) {
                    el.textContent = count;
                    el.style.display = '';
                } else {
                    el.style.display = 'none';
                }
            });
        }

        function renderNotificationItems(items) {
            const list = notifDropdown ? notifDropdown.querySelector('.notification-list') : null;
            if (!list) return;
            if (!items.length) {
                list.innerHTML = '<div class="no-notifications" style="padding:1rem;text-align:center;color:#666;">No new notifications</div>';
                return;
            }
            list.innerHTML = items.map(n => {
                const title = (n.title || n.message || 'Notification');
                const ts = (n.created_at || '');
                const id = (n.id || '');
                const href = (n.link || '#');
                return `
                <a href="${href}" class="notification-item" data-id="${id}" aria-label="${title}" title="${title}" style="display:block;padding:.75rem 1rem;border-bottom:1px solid #f1f1f1;text-decoration:none;color:#333;">
                    <div style="font-weight:600;">${title}</div>
                    <small style="opacity:.7;">${ts}</small>
                </a>`;
            }).join('');
        }

        // Start polling as soon as page is ready
        startNotificationPolling();

        // Load list when dropdown is opened
        if (notifBtn) {
            notifBtn.addEventListener('click', function(e) {
                // Allow the toggle logic above to run; then load content
                setTimeout(loadNotificationsList, 0);
                setTimeout(positionNotificationDropdown, 0);
            });
            
            // Also listen for Bootstrap dropdown events
            notifBtn.addEventListener('show.bs.dropdown', function() {
                setTimeout(loadNotificationsList, 0);
                setTimeout(positionNotificationDropdown, 0);
            });
        }
    });

    // Update scroll event for navbar
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        const body = document.body;
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
            body.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
            body.classList.remove('scrolled');
        }
    });

    // Smooth hover effects for nav links
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('mouseenter', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'translateY(-2px)';
            }
        });
        
        link.addEventListener('mouseleave', function() {
            const icon = this.querySelector('i');
            if (icon && !this.classList.contains('active')) {
                icon.style.transform = 'translateY(0)';
            }
        });
    });

document.addEventListener('DOMContentLoaded', function() {
        const notificationButton = document.getElementById('notificationButton');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        // When dropdown is shown, (re)load notifications and position it
        if (notificationButton) {
            notificationButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Toggle dropdown manually if Bootstrap doesn't handle it
                const dropdown = document.getElementById('notificationDropdown');
                if (dropdown) {
                    const isVisible = dropdown.classList.contains('show') || dropdown.style.display === 'block';
                    
                    if (isVisible) {
                        dropdown.classList.remove('show');
                        dropdown.style.display = 'none';
                    } else {
                        dropdown.classList.add('show');
                        dropdown.style.display = 'block';
                        setTimeout(loadNotificationsList, 0);
                        setTimeout(positionNotificationDropdown, 0);
                    }
                }
            });
        }

        // Close when clicking outside (for non-BS usage too)
        document.addEventListener('click', function(e) {
            if (notificationDropdown && !notificationDropdown.contains(e.target) && !notificationButton.contains(e.target)) {
                notificationDropdown.classList.remove('show');
                resetNotificationDropdownPosition();
            }
        });

        // Mark notification as read when clicked
        document.addEventListener('click', function(e) {
            const item = e.target.closest('.notification-item');
            if (item) {
                const notifId = item.dataset.id;
                if (notifId) markAsRead(notifId);
            }
        });

        function markAsRead(notifId) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notifId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationCount();
                }
            });
        }

        function markAllAsRead() {
            fetch('mark_all_notifications_read.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationCount();
                        const center = document.querySelector('#notificationDropdown .notif-center');
                        if (center) center.innerHTML = '<div class="px-4 py-3 text-muted">No new notifications</div>';
                }
            });
        }

        // ---- Smart positioning for notification dropdown on mobile ----
        function positionNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            const btn = document.getElementById('notificationButton');
            if (!dropdown || !btn) return;
            const isMobile = window.matchMedia('(max-width: 768px)').matches;
            const isDropdownVisible = dropdown.classList.contains('show') || dropdown.style.display === 'block';
            
            if (!isMobile || !isDropdownVisible) {
                resetNotificationDropdownPosition();
                return;
            }
            const rect = btn.getBoundingClientRect();
            const vw = window.innerWidth;
            const margin = 8;
            const desiredWidth = Math.min(380, vw - margin * 2);
            const left = Math.min(Math.max(margin, rect.right - desiredWidth), vw - desiredWidth - margin);
            const top = rect.bottom + margin;
            dropdown.style.position = 'fixed';
            dropdown.style.width = desiredWidth + 'px';
            dropdown.style.maxWidth = desiredWidth + 'px';
            dropdown.style.left = left + 'px';
            dropdown.style.right = 'auto';
            dropdown.style.top = top + 'px';
        }

        function resetNotificationDropdownPosition() {
            const dropdown = document.getElementById('notificationDropdown');
            if (!dropdown) return;
            dropdown.style.position = '';
            dropdown.style.left = '';
            dropdown.style.right = '';
            dropdown.style.top = '';
            dropdown.style.width = '';
            dropdown.style.maxWidth = '';
        }

        window.addEventListener('resize', positionNotificationDropdown);
        window.addEventListener('scroll', positionNotificationDropdown, { passive: true });

        function updateNotificationCount() {
            const counter = document.querySelector('.notification-counter');
            if (counter) {
                const currentCount = parseInt(counter.textContent) - 1;
                if (currentCount <= 0) {
                    counter.remove();
                } else {
                    counter.textContent = currentCount;
                }
            }
        }
});
</script>

<!-- Bootstrap 5 JS - Required for dropdown functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<style>
body {
    padding-top: var(--nav-height);
}
</style>