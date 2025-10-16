<?php
// Start with database connection
require_once '../config/db.php';

// Then check session and role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
            (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

if (!$is_admin) {
    header("Location: ../login.php");
    exit();
}

// Get unread messages count
$unread_messages = 0;
$unread_query = "SELECT COUNT(*) as unread FROM messages WHERE is_read = 0";
$unread_result = mysqli_query($conn, $unread_query);
if ($unread_result) {
    $unread_messages = mysqli_fetch_assoc($unread_result)['unread'];
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<style>
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: 240px;
        background-color: #006C3B;
        background-image: linear-gradient(to bottom, #006C3B, #005530);
        display: flex;
        flex-direction: column;
        z-index: 1002; /* above overlay and header */
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        animation: slideInLeft 0.5s ease-out;
    }

    @keyframes slideInLeft {
        from { transform: translateX(-100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    .brand-section {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 24px 20px;
        background: rgba(0, 0, 0, 0.1);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }

    .brand-section:hover {
        background: rgba(0, 0, 0, 0.15);
    }

    .brand-section img {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .brand-section:hover img {
        transform: rotate(5deg) scale(1.05);
    }

    .brand-text {
        color: white;
        font-size: 20px;
        font-weight: 600;
        line-height: 1.2;
        transition: all 0.3s ease;
    }

    .brand-section:hover .brand-text {
        text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
    }

    .nav-links {
        padding: 20px 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
    }

    .nav-item {
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
    }

    .nav-item a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        font-size: 15px;
        position: relative;
        z-index: 1;
    }

    .nav-item:not(.active):hover {
        transform: translateX(5px);
    }

    .nav-item:hover a {
        color: white;
    }

    .nav-item:not(.active) a::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
        transform: scaleX(0);
        transform-origin: right;
        transition: transform 0.3s ease;
        z-index: -1;
    }

    .nav-item:not(.active):hover a::before {
        transform: scaleX(1);
        transform-origin: left;
    }

    .nav-item.active {
        background: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        animation: pulseHighlight 1.5s infinite alternate;
    }

    @keyframes pulseHighlight {
        from { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }
        to { box-shadow: 0 4px 15px rgba(0, 108, 59, 0.3); }
    }

    .nav-item.active a {
        color: #006C3B;
        font-weight: 500;
    }

    .nav-item.active::after {
        content: '';
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #006C3B;
    }

    .nav-item i {
        width: 20px;
        font-size: 18px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .nav-item:hover i {
        transform: translateY(-2px);
    }

    .nav-bottom {
        padding: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .nav-bottom .nav-item:last-child:hover {
        background: rgba(255, 70, 70, 0.2);
    }

    .nav-bottom .nav-item:last-child:hover a {
        color: #ffeeee;
    }

    .nav-bottom .nav-item:first-child {
        margin-bottom: 8px;
        background: rgba(255, 255, 255, 0.1);
        position: relative;
    }

    .user-status {
        position: absolute;
        top: 12px;
        right: 14px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #4ade80;
        box-shadow: 0 0 0 2px rgba(0, 108, 59, 0.5);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7);
        }
        70% {
            box-shadow: 0 0 0 6px rgba(74, 222, 128, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(74, 222, 128, 0);
        }
    }

    .nav-bottom .nav-item:first-child:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateX(5px);
    }

    .nav-bottom .nav-item:first-child i {
        color: rgba(255, 255, 255, 0.95);
    }

    .nav-bottom .nav-item.active {
        background: white;
    }

    .nav-bottom .nav-item.active a {
        color: #006C3B;
    }

    .nav-bottom .nav-item.active i {
        color: #006C3B;
    }

    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .sidebar.active {
            transform: translateX(0) !important;
        }
    }

    .badge {
        background-color: #f8f9fa;
        color: #006C3B;
        border-radius: 50%;
        font-size: 12px;
        width: 22px;
        height: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: auto;
        font-weight: bold;
    }
    
    .active .badge {
        background-color: #006C3B;
        color: white;
    }

    /* Mobile styles */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            width: 85%;
            max-width: 280px;
        }

        .sidebar.active {
            transform: translateX(0) !important;
        }

        .mobile-header {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: linear-gradient(135deg, #006C3B 0%, #005530 100%);
            padding: 0 1rem;
            align-items: center;
            z-index: 1001; /* keep header above overlay */
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }

        .mobile-header .header-title {
            flex: 1;
            text-align: center;
            color: #ffffff;
            font-weight: 600;
            font-size: 1.05rem;
            letter-spacing: 0.3px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.25);
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .mobile-header::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            height: 1px;
            background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.35), rgba(255,255,255,0));
            pointer-events: none;
        }

        .burger-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            transition: background 0.3s ease;
        }

        .burger-icon:active {
            background: rgba(255, 255, 255, 0.2);
        }

        .burger-icon i {
            font-size: 1.25rem;
        }

        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000; /* underneath header(1001) and sidebar(1002) */
            opacity: 0;
            transition: opacity 0.3s ease;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            pointer-events: none;
            cursor: pointer;
        }

        .mobile-overlay.active {
            display: block;
            opacity: 1;
            pointer-events: auto;
        }

        /* Ensure page loader never blocks taps while hidden and sits below overlay */
        .page-loader { z-index: 500 !important; }
        .page-loader.hidden { pointer-events: none !important; }

        .main-content {
            margin-left: 0;
            padding-top: 56px;
        }

        /* Handle safe areas */
        @supports (padding: max(0px)) {
            .mobile-header {
                padding-left: max(1rem, env(safe-area-inset-left));
                padding-right: max(1rem, env(safe-area-inset-right));
                padding-top: max(0px, env(safe-area-inset-top));
            }

            .sidebar {
                padding-top: max(0px, env(safe-area-inset-top));
                padding-bottom: max(0px, env(safe-area-inset-bottom));
            }
        }

        /* Prevent body scroll when nav is open */
        body.nav-open {
            overflow: hidden;
            position: fixed;
            width: 100%;
            height: 100%;
        }
    }

    .notification-dropdown {
        position: absolute !important;
        top: 100% !important;
        right: 0 !important;
        width: 320px !important;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        z-index: 9999 !important;
        margin-top: 0.5rem;
        border: 1px solid rgba(0, 0, 0, 0.08);
        transform: none !important;
    }

    .notification-dropdown::before {
        content: '';
        position: absolute;
        top: -8px;
        right: 20px;
        width: 16px;
        height: 16px;
        background: white;
        transform: rotate(45deg);
        border-left: 1px solid rgba(0, 0, 0, 0.08);
        border-top: 1px solid rgba(0, 0, 0, 0.08);
        z-index: -1;
    }

    /* Fix mobile positioning */
    @media (max-width: 768px) {
        .notification-dropdown {
            position: fixed !important;
            top: 60px !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            border-radius: 0 !important;
            height: auto !important;
            max-height: calc(100vh - 60px) !important;
            border: none !important;
        }

        .notification-dropdown::before {
            display: none;
        }

        .notification-list {
            max-height: calc(100vh - 180px);
        }
    }
</style>

<div class="mobile-overlay" id="mobileOverlay" aria-hidden="true"></div>

<div class="mobile-header">
    <div class="burger-icon" id="sidebarToggle" role="button" aria-label="Toggle admin navigation" aria-controls="adminSidebar" aria-expanded="false" tabindex="0">
        <i class="fas fa-bars"></i>
    </div>
    <div class="header-title">
        <?php
        $page_titles = [
            'dashboard' => 'Dashboard',
            'orders' => 'Orders',
            'menu_items' => 'Menu Items',
            'users' => 'Users',
            'reports' => 'Reports',
            'messages' => 'Messages',
            'ratings' => 'Ratings',
            'profile' => 'My Profile'
        ];
        echo isset($page_titles[$current_page]) ? $page_titles[$current_page] : 'Admin Dashboard';
        ?>
    </div>
</div>

<nav class="sidebar" id="adminSidebar" aria-label="Admin navigation">
    <div class="brand-section">
        <img src="../assets/images/logo.png" alt="Logo">
        <div class="brand-text">
            Eat&Run<br>Admin
        </div>
    </div>
    
    <div class="nav-links">
        <div class="nav-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
        </div>
        <div class="nav-item <?php echo $current_page === 'orders' ? 'active' : ''; ?>">
            <a href="orders.php">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
            </a>
        </div>
        <div class="nav-item <?php echo $current_page === 'menu_items' ? 'active' : ''; ?>">
            <a href="menu_items.php">
                <i class="fas fa-utensils"></i>
                <span>Menu Items</span>
            </a>
        </div>
        <div class="nav-item <?php echo $current_page === 'users' ? 'active' : ''; ?>">
            <a href="users.php">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
        </div>
        <div class="nav-item <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
            <a href="reports.php">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </div>
        <div class="nav-item <?php echo $current_page === 'messages' ? 'active' : ''; ?>">
            <a href="messages.php">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
                <?php if ($unread_messages > 0): ?>
                <span class="badge"><?php echo $unread_messages; ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="nav-item <?php echo $current_page === 'ratings' ? 'active' : ''; ?>">
            <a href="ratings.php">
                <i class="fas fa-star"></i>
                <span>Ratings</span>
            </a>
        </div>
    </div>

    <div class="nav-bottom">
        <div class="nav-item <?php echo $current_page === 'profile' ? 'active' : ''; ?>">
            <a href="profile.php">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
                <?php if (isset($_SESSION['user_id'])): ?>
                <span class="user-status"></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="nav-item">
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('adminSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileOverlay = document.getElementById('mobileOverlay');

    // Burger/overlay toggle helpers
    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('active');
        if (mobileOverlay) {
            mobileOverlay.classList.add('active');
            mobileOverlay.setAttribute('aria-hidden', 'false');
        }
        if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('nav-open');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('active');
        if (mobileOverlay) {
            mobileOverlay.classList.remove('active');
            mobileOverlay.setAttribute('aria-hidden', 'true');
        }
        if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('nav-open');
        document.body.style.overflow = '';
    }

    function toggleSidebar() {
        if (sidebar && sidebar.classList.contains('active')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e){ e.stopPropagation(); toggleSidebar(); });
        sidebarToggle.addEventListener('keydown', function(e){ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleSidebar(); } });
    }

    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', closeSidebar);
    }

    // Close when clicking outside
    document.addEventListener('click', function(e){
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('active')) {
            const isInside = sidebar.contains(e.target) || (sidebarToggle && sidebarToggle.contains(e.target));
            if (!isInside) closeSidebar();
        }
    });

    // Close on ESC
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('active')) closeSidebar();
    });

    // Reset on resize to desktop
    window.addEventListener('resize', function(){
        if (window.innerWidth > 768) closeSidebar();
    });

    // Add a ripple effect to nav items
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            const rect = item.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const ripple = document.createElement('span');
            ripple.style.position = 'absolute';
            ripple.style.width = '1px';
            ripple.style.height = '1px';
            ripple.style.background = 'rgba(255, 255, 255, 0.4)';
            ripple.style.borderRadius = '50%';
            ripple.style.transform = 'scale(0)';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.pointerEvents = 'none';
            
            item.appendChild(ripple);
            
            setTimeout(() => {
                ripple.style.transition = 'transform 0.5s, opacity 0.5s';
                ripple.style.transform = 'scale(100)';
                ripple.style.opacity = '0';
                
                setTimeout(() => {
                    ripple.remove();
                }, 500);
            }, 10);
        });
    });
    
    // Track current path for navigation
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        if (currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
});
</script> 