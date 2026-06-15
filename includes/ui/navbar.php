<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/database/db.php';

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
if ($is_logged_in && $conn) {
    $user_id = $_SESSION['user_id'];
    
    try {
        // Get unread notifications count
        if ($conn instanceof PDO) {
            $notif_query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
            $stmt = $conn->prepare($notif_query);
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $unread_count = $row['unread_count'] ?? 0;
            
            // Get recent notifications
            $recent_notif_query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
            $stmt = $conn->prepare($recent_notif_query);
            $stmt->execute([$user_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($conn instanceof mysqli) {
            // MySQLi connection - use prepared statements
            $notif_query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
            $stmt = $conn->prepare($notif_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $notif_result = $stmt->get_result();
            if ($notif_result) {
                $row = $notif_result->fetch_assoc();
                $unread_count = $row['unread_count'] ?? 0;
            }

            // Get recent notifications
            $recent_notif_query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
            $stmt = $conn->prepare($recent_notif_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $recent_notif_result = $stmt->get_result();
            if ($recent_notif_result) {
                while ($notification = $recent_notif_result->fetch_assoc()) {
                    $notifications[] = $notification;
                }
            }
        } else {
            // Fallback for JSONDatabase or other connections
            $unread_count = 0;
            $notifications = [];
        }
    } catch (Exception $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
    }
}

// Get cart count if user is logged in
$cartCount = 0;
if ($is_logged_in && !$is_admin && $conn) {
    $user_id = $_SESSION['user_id'];
    
    try {
        if ($conn instanceof PDO) {
            $cart_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
            $stmt = $conn->prepare($cart_query);
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $cartCount = $row['total'] ?? 0;
        } elseif ($conn instanceof mysqli) {
            // MySQLi connection - use prepared statements
            $cart_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
            $stmt = $conn->prepare($cart_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $cart_result = $stmt->get_result();
            if ($cart_result) {
                $row = $cart_result->fetch_assoc();
                $cartCount = $row['total'] ?? 0;
            }
        } else {
            // Fallback for JSONDatabase or other connections
            $cartCount = 0;
        }
    } catch (Exception $e) {
        error_log("Error fetching cart count: " . $e->getMessage());
    }
}
?>

<nav class="navbar">
    <div class="nav-container">
        <a href="/index" class="navbar-brand">
            <img src="assets/images/logo.png" alt="Logo" class="logo">
            <span>Eat&Run</span>
        </a>

        <button class="burger-trigger" id="mobileMenuToggle">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>

        <div class="nav-menu" id="navMenu">
            <ul class="nav-links-container" id="navbar-menu">
                <?php if(!$is_admin): ?>
                    <li class="mobile-background-icons"><a href="/index" class="nav-link"><i class="fas fa-home"></i> <span class="nav-text">Home</span></a></li>
                    <li class="mobile-background-icons"><a href="/menu" class="nav-link"><i class="fas fa-utensils"></i> <span class="nav-text">Menu</span></a></li>
                    <li class="mobile-background-icons"><a href="/about" class="nav-link"><i class="fas fa-info-circle"></i> <span class="nav-text">About</span></a></li>
                <?php else: ?>
                    <li class="mobile-background-icons"><a href="/admin/dashboard" class="nav-link"><i class="fas fa-chart-line"></i> <span class="nav-text">Admin</span></a></li>
                <?php endif; ?>

                <?php if($is_logged_in): ?>
                    <?php if(!$is_admin): ?>
                        <li class="mobile-background-icons mobile-only"><a href="/orders" class="nav-link"><i class="fas fa-list-alt"></i> <span class="nav-text">My Orders</span></a></li>
                    <?php endif; ?>
                    <!-- Notification Bell (Mobile: Link, Desktop: Dropdown) -->
                    <li class="nav-item dropdown notification-bell-wrapper">
                        <!-- Desktop Bell -->
                        <div class="desktop-only">
                            <button class="notification-btn" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <?php if($unread_count > 0): ?>
                                    <span class="notification-counter"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notif-box animate__animated animate__fadeIn" id="notificationDropdown" aria-labelledby="notifDropdown">
                                <div class="notif-header">
                                    <span>Notifications</span>
                                    <?php if($unread_count > 0): ?>
                                        <button onclick="markAllAsRead()" class="mark-all">Mark all as read</button>
                                    <?php endif; ?>
                                </div>
                                <div class="notif-scroll">
                                    <?php if(empty($notifications)): ?>
                                        <div class="notif-empty">No new notifications</div>
                                    <?php else: ?>
                                        <?php foreach($notifications as $notif): ?>
                                            <a href="<?php echo $notif['link'] ?: '#'; ?>" class="notif-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                                                <div class="notif-icon">
                                                    <i class="fas <?php echo $notif['type'] == 'order' ? 'fa-shopping-bag' : 'fa-info-circle'; ?>"></i>
                                                </div>
                                                <div class="notif-content">
                                                    <span class="block"><?php echo htmlspecialchars($notif['message']); ?></span>
                                                    <span class="time"><?php echo date('M d, g:i a', strtotime($notif['created_at'])); ?></span>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <a href="/notifications" class="see-all">See All Messages</a>
                            </div>
                        </div>
                        
                        <!-- Mobile Bell -->
                        <a href="/notifications" class="nav-link mobile-only">
                            <i class="fas fa-bell"></i>
                            <span class="nav-text">Notifications</span>
                            <?php if($unread_count > 0): ?>
                                <span class="notification-count"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <?php if(!$is_admin): ?>
                        <li class="mobile-background-icons"><a href="/cart" class="nav-link"><i class="fas fa-shopping-cart"></i> <span class="nav-text">Cart</span></a></li>
                    <?php endif; ?>
                    <li><a href="/profile" class="nav-link"><i class="fas fa-user"></i> <span class="nav-text">Profile</span></a></li>
                    <li><a href="/logout" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span></a></li>
                <?php else: ?>
                    <li><a href="/login" class="nav-link"><i class="fas fa-sign-in-alt"></i> <span class="nav-text">Login</span></a></li>
                    <li><a href="/register" class="nav-link"><i class="fas fa-user-plus"></i> <span class="nav-text">Register</span></a></li>
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


