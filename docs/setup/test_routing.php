<?php
/**
 * Routing System Test Script
 * Tests all major routes in the Eat&Run application
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define test cases
$test_routes = [
    // Home and main pages
    ['route' => '/', 'expected_file' => 'pages/home.php', 'description' => 'Home page'],
    ['route' => '/index', 'expected_file' => 'pages/home.php', 'description' => 'Index redirect'],
    ['route' => '/index.php', 'expected_file' => 'pages/home.php', 'description' => 'Index.php redirect'],
    
    // Auth pages
    ['route' => '/login', 'expected_file' => 'pages/auth/login.php', 'description' => 'Login page'],
    ['route' => '/register', 'expected_file' => 'pages/auth/register.php', 'description' => 'Register page'],
    
    // Account pages
    ['route' => '/dashboard', 'expected_file' => 'pages/account/dashboard.php', 'description' => 'User dashboard'],
    ['route' => '/profile', 'expected_file' => 'pages/account/profile.php', 'description' => 'User profile'],
    ['route' => '/my_orders', 'expected_file' => 'pages/account/my_orders.php', 'description' => 'My orders'],
    ['route' => '/notifications', 'expected_file' => 'pages/account/notifications.php', 'description' => 'Notifications'],
    
    // Ordering pages
    ['route' => '/menu', 'expected_file' => 'pages/ordering/menu.php', 'description' => 'Menu page'],
    ['route' => '/cart', 'expected_file' => 'pages/ordering/cart.php', 'description' => 'Shopping cart'],
    ['route' => '/checkout', 'expected_file' => 'pages/ordering/checkout.php', 'description' => 'Checkout page'],
    ['route' => '/order', 'expected_file' => 'pages/ordering/order.php', 'description' => 'Order page'],
    ['route' => '/orders', 'expected_file' => 'pages/ordering/orders.php', 'description' => 'Orders list'],
    ['route' => '/order_confirmation', 'expected_file' => 'pages/ordering/order_confirmation.php', 'description' => 'Order confirmation'],
    ['route' => '/order_success', 'expected_file' => 'pages/ordering/order_success.php', 'description' => 'Order success'],
    ['route' => '/order_detail', 'expected_file' => 'pages/ordering/order_detail.php', 'description' => 'Order detail'],
    ['route' => '/view_order', 'expected_file' => 'pages/ordering/view_order.php', 'description' => 'View order'],
    ['route' => '/view_order_receipt', 'expected_file' => 'pages/ordering/view_order_receipt.php', 'description' => 'View receipt'],
    ['route' => '/menu-item', 'expected_file' => 'pages/ordering/menu-item.php', 'description' => 'Menu item detail'],
    
    // Info pages
    ['route' => '/about', 'expected_file' => 'pages/info/about.php', 'description' => 'About page'],
    ['route' => '/customer_service', 'expected_file' => 'pages/info/customer_service.php', 'description' => 'Customer service'],
    ['route' => '/mission-vision', 'expected_file' => 'pages/info/mission-vision.php', 'description' => 'Mission & Vision'],
    
    // Reviews pages
    ['route' => '/ratings', 'expected_file' => 'pages/reviews/ratings.php', 'description' => 'Ratings page'],
    ['route' => '/rate_item', 'expected_file' => 'pages/reviews/rate_item.php', 'description' => 'Rate item page'],
    
    // Admin pages
    ['route' => '/admin', 'expected_file' => 'admin/pages/dashboard.php', 'description' => 'Admin dashboard'],
    ['route' => '/admin/dashboard', 'expected_file' => 'admin/pages/dashboard.php', 'description' => 'Admin dashboard (explicit)'],
    ['route' => '/admin/login', 'expected_file' => 'admin/pages/auth/login.php', 'description' => 'Admin login'],
    ['route' => '/admin/menu', 'expected_file' => 'admin/pages/menu.php', 'description' => 'Admin menu management'],
    ['route' => '/admin/menu_items', 'expected_file' => 'admin/pages/menu_items.php', 'description' => 'Admin menu items'],
    ['route' => '/admin/orders', 'expected_file' => 'admin/pages/orders/orders.php', 'description' => 'Admin orders'],
    ['route' => '/admin/users', 'expected_file' => 'admin/pages/users/users.php', 'description' => 'Admin users'],
    ['route' => '/admin/reviews', 'expected_file' => 'admin/pages/reviews/reviews.php', 'description' => 'Admin reviews'],
    ['route' => '/admin/reports', 'expected_file' => 'admin/pages/reports/reports.php', 'description' => 'Admin reports'],
    ['route' => '/admin/profile', 'expected_file' => 'admin/pages/profile.php', 'description' => 'Admin profile'],
    ['route' => '/admin/settings', 'expected_file' => 'admin/pages/settings.php', 'description' => 'Admin settings'],
];

// Test results
$results = [
    'passed' => 0,
    'failed' => 0,
    'missing_files' => [],
    'routing_issues' => []
];

echo "=== ROUTING SYSTEM TEST ===\n\n";

// Check if all expected files exist
foreach ($test_routes as $test) {
    $file_path = $test['expected_file'];
    if (!file_exists($file_path)) {
        $results['failed']++;
        $results['missing_files'][] = [
            'route' => $test['route'],
            'expected_file' => $file_path,
            'description' => $test['description']
        ];
        echo "❌ FAIL: {$test['description']} ({$test['route']})\n";
        echo "   Expected file not found: {$file_path}\n\n";
    } else {
        $results['passed']++;
        echo "✓ PASS: {$test['description']} ({$test['route']})\n";
    }
}

echo "\n=== TEST SUMMARY ===\n";
echo "Passed: {$results['passed']}\n";
echo "Failed: {$results['failed']}\n";
echo "Total: " . ($results['passed'] + $results['failed']) . "\n\n";

if (!empty($results['missing_files'])) {
    echo "=== MISSING FILES ===\n";
    foreach ($results['missing_files'] as $missing) {
        echo "- {$missing['expected_file']} (Route: {$missing['route']})\n";
    }
    echo "\n";
}

// Test routing logic
echo "=== ROUTING LOGIC TEST ===\n\n";

// Simulate routing for a few key routes
$test_routing_logic = [
    '/' => 'pages/home.php',
    '/login' => 'pages/auth/login.php',
    '/admin' => 'admin/pages/dashboard.php',
    '/menu' => 'pages/ordering/menu.php',
];

foreach ($test_routing_logic as $route => $expected) {
    // Simulate the routing logic from index.php
    $path = trim($route, '/');
    
    if (empty($path) || $path === 'index.php' || $path === 'index') {
        $resolved = 'pages/home.php';
    } elseif (str_starts_with($path, 'admin/') || $path === 'admin') {
        // This would be handled by admin/index.php
        if ($path === 'admin') {
            $resolved = 'admin/pages/dashboard.php';
        } else {
            $admin_path = substr($path, 6);
            $admin_file = $admin_path . '.php';
            $search_folders = ['', 'auth', 'products', 'orders', 'users', 'reviews', 'reports'];
            $resolved = null;
            foreach ($search_folders as $folder) {
                $test_path = 'admin/pages/' . (empty($folder) ? '' : $folder . '/') . $admin_file;
                if (file_exists($test_path)) {
                    $resolved = $test_path;
                    break;
                }
            }
        }
    } else {
        $page_file = $path . '.php';
        $search_folders = [
            'pages/ordering', 
            'pages/account', 
            'pages/auth', 
            'pages/info', 
            'pages/reviews',
            'actions/cart',
            'actions/auth',
            'actions/order',
            'actions/account',
            'actions/notifications',
            'actions/reviews',
            'actions'
        ];
        
        $resolved = null;
        if (file_exists('pages/' . $page_file)) {
            $resolved = 'pages/' . $page_file;
        } else {
            foreach ($search_folders as $folder) {
                if (file_exists("$folder/$page_file")) {
                    $resolved = "$folder/$page_file";
                    break;
                }
            }
        }
    }
    
    if ($resolved === $expected) {
        echo "✓ PASS: Route '$route' resolves to '$expected'\n";
    } else {
        echo "❌ FAIL: Route '$route' expected '$expected' but got '" . ($resolved ?? 'null') . "'\n";
    }
}

echo "\n=== HTACCESS REWRITE RULES TEST ===\n\n";

// Check if .htaccess files exist and are readable
$htaccess_files = [
    '.htaccess' => 'Root .htaccess',
    'admin/.htaccess' => 'Admin .htaccess'
];

foreach ($htaccess_files as $file => $description) {
    if (file_exists($file)) {
        echo "✓ PASS: {$description} exists\n";
        $content = file_get_contents($file);
        if (strpos($content, 'RewriteEngine On') !== false) {
            echo "  - RewriteEngine is enabled\n";
        }
        if (strpos($content, 'RewriteRule') !== false) {
            echo "  - RewriteRules are configured\n";
        }
    } else {
        echo "❌ FAIL: {$description} not found\n";
    }
}

echo "\n=== CONFIGURATION TEST ===\n\n";

// Check if config files exist
$config_files = [
    'config/database/db.php' => 'Database configuration',
    'includes/config.php' => 'Main configuration'
];

foreach ($config_files as $file => $description) {
    if (file_exists($file)) {
        echo "✓ PASS: {$description} exists\n";
    } else {
        echo "❌ FAIL: {$description} not found\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
?>
