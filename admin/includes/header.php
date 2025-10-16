<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get current page title
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_title = ucfirst(str_replace('_', ' ', $current_page));

// Get admin user details if not already set
if (!isset($admin_name)) {
    $admin_id = $_SESSION['user_id'];
    $admin_query = "SELECT full_name, profile_image FROM users WHERE id = ? AND role = 'admin'";
    $stmt = mysqli_prepare($conn, $admin_query);
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    mysqli_stmt_execute($stmt);
    $admin_result = mysqli_stmt_get_result($stmt);
    $admin_data = mysqli_fetch_assoc($admin_result);
    $admin_name = $admin_data['full_name'] ?? 'Administrator';
    $admin_profile_image = $admin_data['profile_image'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EatsRun Admin</title>
    <!-- Import Poppins font directly in header -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../admin/css/admin-fonts.css">
    <style>
        :root {
            --sidebar-width: 180px;
            --primary-color: #006837;
            --active-color: rgba(255, 255, 255, 0.1);
            --text-light: #ffffff;
            --transition: all 0.3s ease;
        }

        * {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif !important;
        }

        body {
            background-color: #f5f1eb;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary-color);
            color: var(--text-light);
            transition: var(--transition);
            z-index: 1000;
            padding: 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 0.5rem;
        }

        .brand img {
            width: 28px;
            height: 28px;
            border-radius: 4px;
        }

        .brand span {
            font-size: 0.8125rem;
            font-weight: 600;
            white-space: nowrap;
            opacity: 0.95;
            letter-spacing: 0.2px;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 1.25rem;
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition);
            opacity: 0.85;
            font-size: 0.8125rem;
            letter-spacing: 0.2px;
        }

        .nav-link:hover {
            background: var(--active-color);
            opacity: 1;
        }

        .nav-link.active {
            background: var(--active-color);
            opacity: 1;
        }

        .nav-link i {
            font-size: 0.9375rem;
            width: 18px;
            text-align: center;
            opacity: 0.95;
        }

        .nav-link span {
            transform: translateY(1px);
        }

        .logout-link {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 0.625rem 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            opacity: 0.85;
        }

        .logout-link:hover {
            background: var(--active-color);
            opacity: 1;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
            background-color: #f5f1eb;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }

        .header-container {
            background: var(--white);
            padding: 2rem;
            border-radius: 20px;
            margin: 1.5rem 2rem 1rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .burger-icon {
            width: 48px;
            height: 48px;
            background: #e8f8f1;
            border-radius: 12px;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .burger-icon i {
            color: var(--primary);
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .burger-icon:hover {
            background: #d1f2e4;
            transform: translateY(-2px);
        }

        .burger-icon:hover i {
            transform: scale(1.1);
        }

        .title-text {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .title-text h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .title-text h2 {
            font-size: 1.25rem;
            font-weight: 500;
            color: #7f8c8d;
            margin: 0;
        }

        @media (max-width: 992px) {
            .burger-icon {
                display: flex;
                z-index: 100;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                position: fixed;
                z-index: 1000;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                z-index: 99;
            }

            .overlay.show {
                opacity: 1;
                visibility: visible;
            }
        }
    </style>
</head>
<body>
    <div class="header-container">
        <div class="header-content">
            <div class="page-title">
                <div class="burger-icon" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="title-text">
                    <h1><?php echo $page_title; ?></h1>
                    <?php if ($current_page === 'dashboard'): ?>
                        <h2>Overview</h2>
                    <?php endif; ?>
                </div>
            </div>
            <div class="profile-section" id="profileToggle">
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars($admin_name); ?></div>
                    <div class="profile-role">Administrator</div>
                    <div class="last-updated">
                        <i class="fas fa-clock"></i>
                        <span>Last updated: <?php echo date('h:i A'); ?></span>
                    </div>
                </div>
                <?php if (!empty($admin_profile_image) && file_exists("../uploads/profile_photos/{$admin_profile_image}")): ?>
                    <img src="../uploads/profile_photos/<?php echo htmlspecialchars($admin_profile_image); ?>" alt="Profile" class="profile-image">
                <?php else: ?>
                    <img src="../assets/images/admin-avatar.png" alt="Profile" class="profile-image">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <nav class="sidebar">
        <div class="brand">
            <img src="../assets/images/logo.png" alt="EatsRun Admin">
            <span>Eat&Run Admin</span>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="menu_items.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'menu_items.php' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i>
                    <span>Menu Items</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="messages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
        </ul>

        <a href="../logout.php" class="nav-link logout-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>

    <div class="main-content">

<script src="../assets/js/notifications.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize notification system
    if (typeof NotificationSystem !== 'undefined') {
        window.notificationSystem = new NotificationSystem();
    }

    // Enhanced ripple effect
    const buttons = document.querySelectorAll('.nav-item, .logout a');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            
            const diameter = Math.max(this.clientWidth, this.clientHeight);
            const radius = diameter / 2;
            
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left - radius;
            const y = e.clientY - rect.top - radius;
            
            ripple.style.width = ripple.style.height = `${diameter}px`;
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });

    // Add hover effect for stats cards and chart containers
    const cards = document.querySelectorAll('.stats-card, .chart-container');
    cards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            
            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0)';
        });
    });
});
</script> 

    <!-- Clean up EventSource when leaving the page -->
    <script>
    window.addEventListener('beforeunload', function() {
        if (eventSource) {
            eventSource.close();
        }
    });
    
    // Fix for admin navigation links - this ensures all links to dashboard work correctly
    document.addEventListener('DOMContentLoaded', function() {
        // Check all navigation links
        const navLinks = document.querySelectorAll('a.nav-item, a.sidebar-link, .admin-menu-link');
        
        navLinks.forEach(link => {
            // Handle links to index.php
            if (link.getAttribute('href') === 'index.php' || link.getAttribute('href') === './index.php') {
                link.setAttribute('href', 'dashboard.php');
            }
            
            // Handle links to dashboard
            if (link.textContent.trim().toLowerCase().includes('dashboard')) {
                link.setAttribute('href', 'dashboard.php');
            }
        });
        
        // Add click event handler for any dashboard-related items
        document.body.addEventListener('click', function(e) {
            const target = e.target.closest('a');
            if (target && target.textContent.trim().toLowerCase().includes('dashboard')) {
                e.preventDefault();
                window.location.href = 'dashboard.php';
            }
        });
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        // Create overlay element if it doesn't exist
        let overlay = document.querySelector('.overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'overlay';
            document.body.appendChild(overlay);
        }

        function toggleSidebar() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        }

        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });

        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            toggleSidebar();
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target) && 
                sidebar.classList.contains('show')) {
                toggleSidebar();
            }
        });

        // Prevent sidebar from closing when clicking inside it
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });
    </script>
</body>
</html> 