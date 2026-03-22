<?php
// This is a simple router for the development server on port 3000
$requestUri = $_SERVER['REQUEST_URI'];

// Parse the request path
$path = parse_url($requestUri, PHP_URL_PATH);

// Routing logic
if ($path === '/forgot-password.php' || $path === '/forgot_password.php') {
    // Include the forgot-password.php file
    include 'forgot-password.php';
    exit;
} else {
    // Handle other routes normally
    $filePath = '.' . $path;
    if (file_exists($filePath) && is_file($filePath)) {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        // Set appropriate content type headers
        switch ($extension) {
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            case 'json':
                header('Content-Type: application/json');
                break;
            case 'png':
                header('Content-Type: image/png');
                break;
            case 'jpg':
            case 'jpeg':
                header('Content-Type: image/jpeg');
                break;
            case 'gif':
                header('Content-Type: image/gif');
                break;
            case 'svg':
                header('Content-Type: image/svg+xml');
                break;
            default:
                // For PHP and HTML files
                if ($extension === 'php') {
                    include $filePath;
                    exit;
                }
                break;
        }
        
        // Output the file content for non-PHP files
        if ($extension !== 'php') {
            readfile($filePath);
        }
    } else {
        // Return 404 if file not found
        header('HTTP/1.0 404 Not Found');
        echo "404 Not Found: The requested file does not exist.";
    }
}
?> 