<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Eat&Run' : 'Eat&Run'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/shared-styles.css">
    <link rel="stylesheet" href="assets/css/notifications.css">
    <link rel="stylesheet" href="assets/css/toast.css">
    <style>
        /* Header-specific styles */
        .main-header {
            background: var(--white);
            box-shadow: var(--shadow-sm);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: var(--transition-normal);
        }

        .main-header.scrolled {
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo i {
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition-fast);
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: var(--transition-fast);
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }

        .user-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .cart-icon, .notification-icon {
            position: relative;
            font-size: 1.2rem;
            color: var(--text-color);
            text-decoration: none;
            transition: var(--transition-fast);
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--primary-color);
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-color);
            cursor: pointer;
            padding: 0.5rem;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }

            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--white);
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                box-shadow: var(--shadow-md);
            }

            .nav-links.active {
                display: flex;
            }

            .user-actions {
                margin-left: auto;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <i class="fas fa-utensils"></i>
                Eat&Run
            </a>
            
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>

            <nav class="nav-links">
                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a>
                <a href="menu.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'active' : ''; ?>">Menu</a>
                <a href="about.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">About</a>
                <a href="my_orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_orders.php' ? 'active' : ''; ?>">My Orders</a>
            </nav>

            <div class="user-actions">
                <a href="cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <?php
                    if(isset($_SESSION['user_id'])) {
                        require_once 'config/database.php';
                        $user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
                        
                        // Check if cart table exists before querying
                        $table_check_query = "SHOW TABLES LIKE 'cart'";
                        $table_check = mysqli_query($conn, $table_check_query);
                        
                        if ($table_check && mysqli_num_rows($table_check) > 0) {
                            // Cart table exists, proceed with the query
                            $cart_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = '$user_id'";
                            $cart_result = mysqli_query($conn, $cart_query);
                            
                            if ($cart_result) {
                                $row = mysqli_fetch_assoc($cart_result);
                                $cart_count = $row['total'] ?? 0;
                                mysqli_free_result($cart_result);
                            }
                        } else {
                            // Cart table doesn't exist, log this information
                            error_log("Cart table does not exist in the database");
                        }

                        $cartCount = isset($_SESSION['user_id']) ? $cart_count : 0;

                        if($cartCount > 0) {
                            echo "<span class='cart-count'>$cartCount</span>";
                        }
                    }
                    ?>
                </a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                <!-- Notification Bell -->
                <div class="notification-icon" id="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notification-count">0</span>
                    
                    <div class="notification-dropdown" id="notification-dropdown">
                        <div class="notification-header">
                            <h3>Notifications</h3>
                            <button id="mark-all-read">Mark all read</button>
                        </div>
                        <div class="notification-list" id="notification-list">
                            <!-- Notifications will be loaded here -->
                            <div class="notification-empty">
                                <i class="fas fa-bell-slash"></i>
                                <p>No notifications yet</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="profile.php" class="btn-elegant">Profile</a>
                <a href="logout.php" class="btn-elegant">Logout</a>
                <?php else: ?>
                <a href="login.php" class="btn-elegant">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <script>
        // Header scroll effect
        window.addEventListener('scroll', () => {
            const header = document.querySelector('.main-header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Mobile menu toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-links') && !e.target.closest('.mobile-menu-btn')) {
                navLinks.classList.remove('active');
            }
        });
        
        <?php if(isset($_SESSION['user_id'])): ?>
        // Initialize notification functionality
        document.addEventListener('DOMContentLoaded', function() {
            const notificationBell = document.getElementById('notification-bell');
            const notificationDropdown = document.getElementById('notification-dropdown');
            const markAllReadBtn = document.getElementById('mark-all-read');
            
            // Toggle notification dropdown
            notificationBell.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!notificationBell.contains(e.target)) {
                    notificationDropdown.classList.remove('show');
                }
            });
            
            // Mark all notifications as read
            markAllReadBtn.addEventListener('click', function() {
                fetch('mark_all_notifications_read.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('notification-count').style.display = 'none';
                        const items = document.querySelectorAll('.notification-item');
                        items.forEach(item => item.classList.add('read'));
                    }
                })
                .catch(error => console.error('Error marking notifications as read:', error));
            });
            
            // Load notifications
            loadNotifications();
            
            // Check for new notifications periodically
            setInterval(checkNewNotifications, 30000); // Every 30 seconds
            
            function loadNotifications() {
                fetch('fetch_notifications.php')
                .then(response => response.json())
                .then(data => {
                    updateNotifications(data);
                })
                .catch(error => console.error('Error loading notifications:', error));
            }
            
            function checkNewNotifications() {
                const lastId = document.querySelector('.notification-item')?.dataset.id || 0;
                fetch(`check_new_notifications.php?last_id=${lastId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.notifications.length > 0) {
                        // Add new notifications to the list
                        const list = document.getElementById('notification-list');
                        data.notifications.forEach(notification => {
                            const notificationHtml = createNotificationHtml(notification);
                            list.insertAdjacentHTML('afterbegin', notificationHtml);
                        });
                        
                        // Update badge count
                        document.getElementById('notification-count').textContent = data.unread_count;
                        document.getElementById('notification-count').style.display = data.unread_count > 0 ? 'flex' : 'none';
                        
                        // Show toast for new notifications
                        data.notifications.forEach(notification => {
                            showToast(notification.message);
                        });
                    }
                })
                .catch(error => console.error('Error checking new notifications:', error));
            }
            
            function updateNotifications(notifications) {
                const list = document.getElementById('notification-list');
                const unreadCount = notifications.filter(n => !n.is_read).length;
                
                if (notifications.length === 0) {
                    list.innerHTML = `
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>No notifications yet</p>
                        </div>
                    `;
                } else {
                    list.innerHTML = notifications.map(notification => 
                        createNotificationHtml(notification)
                    ).join('');
                }
                
                // Update badge
                document.getElementById('notification-count').textContent = unreadCount;
                document.getElementById('notification-count').style.display = unreadCount > 0 ? 'flex' : 'none';
            }
            
            function createNotificationHtml(notification) {
                const icon = getNotificationIcon(notification.type);
                return `
                    <a href="${notification.link || '#'}" class="notification-item ${notification.is_read ? 'read' : ''}" data-id="${notification.id}">
                        <div class="notification-icon">
                            <i class="${icon}"></i>
                        </div>
                        <div class="notification-content">
                            <p class="notification-text">${notification.message}</p>
                            <span class="notification-time">${notification.time_ago}</span>
                        </div>
                    </a>
                `;
            }
            
            function getNotificationIcon(type) {
                switch (type) {
                    case 'order': return 'fas fa-shopping-bag';
                    case 'payment': return 'fas fa-credit-card';
                    case 'delivery': return 'fas fa-truck';
                    case 'system': return 'fas fa-exclamation-circle';
                    default: return 'fas fa-bell';
                }
            }
            
            function showToast(message) {
                const toast = document.createElement('div');
                toast.className = 'toast';
                toast.innerHTML = `
                    <div class="toast-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="toast-content">
                        <p>${message}</p>
                    </div>
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.classList.add('show');
                }, 100);
                
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        document.body.removeChild(toast);
                    }, 300);
                }, 5000);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html> 