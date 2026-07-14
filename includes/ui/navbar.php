<?php
// Session is already started by includes/config.php
// Only start if not started elsewhere (for direct includes outside of config.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure database connection
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
        <!-- Brand/Logo -->
        <a href="/index" class="navbar-brand">
            <img src="assets/images/logo.png" alt="Logo" class="logo">
            <span class="desktop-only">Eat&Run</span>
        </a>

        <!-- DESKTOP NAVIGATION -->
        <div class="nav-menu desktop-nav">
            <ul class="nav-links-container desktop-only">
                <?php if(!$is_admin): ?>
                    <li><a href="/index" class="nav-link <?php echo $current_page === 'index' ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="/menu" class="nav-link <?php echo $current_page === 'menu' ? 'active' : ''; ?>">Menu</a></li>
                    <li><a href="/about" class="nav-link <?php echo $current_page === 'about' ? 'active' : ''; ?>">About</a></li>
                <?php else: ?>
                    <li><a href="/admin/dashboard" class="nav-link <?php echo strpos($current_page, 'admin') !== false ? 'active' : ''; ?>">Dashboard</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- DESKTOP RIGHT SECTION (Icons + User) -->
        <div class="nav-right desktop-only">
            <?php if($is_logged_in): ?>
                <?php if(!$is_admin): ?>
                    <!-- Desktop Cart Icon -->
                    <a href="/cart" class="nav-icon-link cart-icon" title="Shopping Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if($cartCount > 0): ?>
                            <span class="cart-badge"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Desktop Notifications Dropdown -->
                    <div class="dropdown-wrapper">
                        <button class="nav-icon-link notification-icon" id="notifDropdown" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <?php if($unread_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu notif-box" id="notificationDropdown">
                            <div class="notif-header">
                                <span><i class="fas fa-bell"></i> Notifications</span>
                                <?php if($unread_count > 0): ?>
                                    <button onclick="markAllAsRead()" class="mark-all">Mark all as read</button>
                                <?php endif; ?>
                            </div>
                            <div class="notif-scroll">
                                <?php if(empty($notifications)): ?>
                                    <div class="notif-empty">No new notifications</div>
                                <?php else: ?>
                                    <?php foreach($notifications as $notif): ?>
                                        <a href="<?php echo $notif['link'] ?: '#'; ?>" class="notif-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                                            <div class="notif-icon">
                                                <i class="fas <?php 
                                                    echo ($notif['type'] == 'order') ? 'fa-shopping-bag' : 
                                                         (($notif['type'] == 'payment') ? 'fa-credit-card' : 
                                                         (($notif['type'] == 'delivery') ? 'fa-truck' : 'fa-bell'));
                                                ?>"></i>
                                            </div>
                                            <div class="notif-content">
                                                <span class="notif-msg"><?php echo htmlspecialchars($notif['message']); ?></span>
                                                <span class="notif-time"><i class="fas fa-clock"></i> <?php echo date('M d, g:i a', strtotime($notif['created_at'])); ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <a href="/notifications" class="see-all">See All Notifications</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Desktop User Menu -->
                <div class="dropdown-wrapper user-menu-wrapper">
                    <button class="nav-icon-link user-icon" id="userDropdown" title="User Menu">
                        <i class="fas fa-user-circle"></i>
                    </button>
                    <div class="dropdown-menu user-menu" id="userMenuDropdown">
                        <a href="/profile" class="dropdown-item"><i class="fas fa-user"></i> Profile</a>
                        <?php if(!$is_admin): ?>
                            <a href="/orders" class="dropdown-item"><i class="fas fa-history"></i> My Orders</a>
                        <?php endif; ?>
                        <a href="/logout" class="dropdown-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Desktop Login/Register -->
                <a href="/login" class="nav-btn btn-login">Login</a>
                <a href="/register" class="nav-btn btn-register">Register</a>
            <?php endif; ?>
        </div>

        <!-- MOBILE MENU TOGGLE BUTTON -->
        <button class="burger-trigger mobile-only" id="mobileMenuToggle" aria-label="Toggle menu">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>

        <!-- MOBILE NAVIGATION MENU -->
        <div class="mobile-nav mobile-only" id="mobileMenu">
            <div class="mobile-nav-header">
                <span>Menu</span>
                <button class="close-btn" id="closeMobileMenu" aria-label="Close menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <ul class="mobile-nav-links">
                <?php if(!$is_admin): ?>
                    <li><a href="/index" class="mobile-nav-link">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a></li>
                    <li><a href="/menu" class="mobile-nav-link">
                        <i class="fas fa-utensils"></i>
                        <span>Menu</span>
                    </a></li>
                    <li><a href="/about" class="mobile-nav-link">
                        <i class="fas fa-info-circle"></i>
                        <span>About</span>
                    </a></li>
                <?php else: ?>
                    <li><a href="/admin/dashboard" class="mobile-nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a></li>
                <?php endif; ?>

                <?php if($is_logged_in): ?>
                    <?php if(!$is_admin): ?>
                        <li><a href="/cart" class="mobile-nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Cart <?php echo $cartCount > 0 ? "($cartCount)" : ''; ?></span>
                        </a></li>
                        <li><a href="/orders" class="mobile-nav-link">
                            <i class="fas fa-list-alt"></i>
                            <span>My Orders</span>
                        </a></li>
                        <li><a href="/notifications" class="mobile-nav-link">
                            <i class="fas fa-bell"></i>
                            <span>Notifications <?php echo $unread_count > 0 ? "($unread_count)" : ''; ?></span>
                        </a></li>
                    <?php endif; ?>
                    <li><a href="/profile" class="mobile-nav-link">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a></li>
                    <li><a href="/logout" class="mobile-nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a></li>
                <?php else: ?>
                    <li><a href="/login" class="mobile-nav-link">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a></li>
                    <li><a href="/register" class="mobile-nav-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Register</span>
                    </a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const closeMobileMenu = document.getElementById('closeMobileMenu');

    if (mobileMenuToggle && mobileMenu) {
        // Open menu
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        // Close menu
        closeMobileMenu?.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        });

        // Close on link click
        mobileMenu.querySelectorAll('.mobile-nav-link').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });

        // Close on overlay click
        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }

    // Desktop Notification Dropdown
    const notifDropdown = document.getElementById('notifDropdown');
    const notificationDropdown = document.getElementById('notificationDropdown');

    if (notifDropdown && notificationDropdown) {
        notifDropdown.addEventListener('click', function() {
            notificationDropdown.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!notifDropdown.contains(e.target) && !notificationDropdown.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
    }

    // Desktop User Menu Dropdown
    const userDropdown = document.getElementById('userDropdown');
    const userMenuDropdown = document.getElementById('userMenuDropdown');

    if (userDropdown && userMenuDropdown) {
        userDropdown.addEventListener('click', function() {
            userMenuDropdown.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!userDropdown.contains(e.target) && !userMenuDropdown.contains(e.target)) {
                userMenuDropdown.classList.remove('show');
            }
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
    fetch('/actions/notifications/mark_all_notifications_read.php', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(e => console.error('Error:', e));
}
</script>


