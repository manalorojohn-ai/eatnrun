<?php
/**
 * Advanced Routing System for Eat&Run
 * Provides centralized routing logic with logging and error handling
 */

class Router {
    private $request_path;
    private $base_dir;
    private $found_file;
    private $debug_mode = false;
    
    public function __construct($debug = false) {
        $this->debug_mode = $debug;
        $this->parseRequest();
    }
    
    /**
     * Parse the incoming request
     */
    private function parseRequest() {
        $request = $_SERVER['REQUEST_URI'];
        $script_name = $_SERVER['SCRIPT_NAME'];
        $this->base_dir = str_replace('index.php', '', $script_name);
        
        // Get the actual request path after the base directory
        $path = str_replace($this->base_dir, '', $request);
        $path = parse_url($path, PHP_URL_PATH);
        $this->request_path = trim($path, '/');
    }
    
    /**
     * Route the request to the appropriate file
     */
    public function route() {
        // Handle built-in server
        if (php_sapi_name() == 'cli-server') {
            $url = parse_url($_SERVER['REQUEST_URI']);
            $file = './' . ltrim($url['path'], '/');
            if (file_exists($file) && is_file($file)) {
                return false;
            }
        }
        
        // Handle default route
        if (empty($this->request_path) || $this->request_path === 'index.php' || $this->request_path === 'index') {
            return $this->loadFile('pages/home.php');
        }
        
        // Handle admin routes
        if (str_starts_with($this->request_path, 'admin/') || $this->request_path === 'admin') {
            return $this->routeAdmin();
        }
        
        // Prepare page file name
        $page_file = $this->request_path;
        if (!str_ends_with($page_file, '.php')) {
            $page_file .= '.php';
        }
        
        // Search for the page
        return $this->searchAndLoad($page_file);
    }
    
    /**
     * Route admin requests
     */
    private function routeAdmin() {
        if (file_exists('admin/index.php')) {
            return $this->loadFile('admin/index.php');
        }
        return $this->notFound('Admin router not found');
    }
    
    /**
     * Search for and load a page file
     */
    private function searchAndLoad($page_file) {
        $search_folders = [
            'pages',
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
        
        // Check primary pages/ folder first
        if (file_exists('pages/' . $page_file)) {
            return $this->loadFile('pages/' . $page_file);
        }
        
        // Search subfolders
        foreach ($search_folders as $folder) {
            $full_path = "$folder/$page_file";
            if (file_exists($full_path)) {
                return $this->loadFile($full_path);
            }
        }
        
        // Not found
        return $this->notFound($page_file);
    }
    
    /**
     * Load and include a file
     */
    private function loadFile($file) {
        if ($this->debug_mode) {
            error_log("Router: Loading file: $file");
        }
        
        $this->found_file = $file;
        require_once $file;
        return true;
    }
    
    /**
     * Handle 404 errors
     */
    private function notFound($page_file) {
        if ($this->debug_mode) {
            error_log("Router: File not found: $page_file");
        }
        
        http_response_code(404);
        
        if (file_exists('pages/info/error.php')) {
            require_once 'pages/info/error.php';
        } else {
            echo "404 - Page not found (" . htmlspecialchars($page_file) . ")";
        }
        
        return false;
    }
    
    /**
     * Get the found file path
     */
    public function getFoundFile() {
        return $this->found_file;
    }
    
    /**
     * Get the request path
     */
    public function getRequestPath() {
        return $this->request_path;
    }
}

/**
 * Helper function to get the current route
 */
function getCurrentRoute() {
    $request = $_SERVER['REQUEST_URI'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_dir = str_replace('index.php', '', $script_name);
    $path = str_replace($base_dir, '', $request);
    $path = parse_url($path, PHP_URL_PATH);
    return trim($path, '/');
}

/**
 * Helper function to check if current route matches a pattern
 */
function isRoute($pattern) {
    $current = getCurrentRoute();
    return preg_match($pattern, $current) === 1;
}

/**
 * Helper function to generate URLs
 */
function url($path) {
    $base = $_SERVER['SCRIPT_NAME'];
    $base = str_replace('index.php', '', $base);
    return $base . ltrim($path, '/');
}

/**
 * Helper function to redirect
 */
function redirect($path, $code = 302) {
    header('Location: ' . url($path), true, $code);
    exit();
}

/**
 * Helper function to check if user is on admin section
 */
function isAdminRoute() {
    return str_starts_with(getCurrentRoute(), 'admin/') || getCurrentRoute() === 'admin';
}

/**
 * Helper function to check if user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Helper function to check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Helper function to require authentication
 */
function requireAuth($redirect_to = '/login') {
    if (!isAuthenticated()) {
        redirect($redirect_to);
    }
}

/**
 * Helper function to require admin access
 */
function requireAdmin($redirect_to = '/admin/login') {
    if (!isAdmin()) {
        redirect($redirect_to);
    }
}
?>
