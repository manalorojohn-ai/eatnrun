<?php
/**
 * Utility functions for the Eat&Run online food ordering system
 */

/**
 * Sanitize input data to prevent XSS attacks
 * 
 * @param string $data The data to sanitize
 * @return string The sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email format
 * 
 * @param string $email The email to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate a random alphanumeric string
 * 
 * @param int $length The length of the string to generate
 * @return string The generated string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_length = strlen($characters);
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, $characters_length - 1)];
    }
    return $random_string;
}

/**
 * Format a date in a human-readable format
 * 
 * @param string $date The date to format
 * @param string $format The format to use (default: 'F j, Y g:i A')
 * @return string The formatted date
 */
function format_date($date, $format = 'F j, Y g:i A') {
    return date($format, strtotime($date));
}

/**
 * Format a price with the peso sign and proper decimal places
 * 
 * @param float $price The price to format
 * @return string The formatted price
 */
function format_price($price) {
    return '₱' . number_format($price, 2);
}

/**
 * Get user-friendly status label
 * 
 * @param string $status The status code
 * @return string The user-friendly status label
 */
function get_status_label($status) {
    $labels = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];
    
    return $labels[strtolower($status)] ?? ucfirst($status);
}

/**
 * Log a system message to the error log
 * 
 * @param string $message The message to log
 * @param string $level The log level (info, warning, error)
 */
function log_message($message, $level = 'info') {
    $prefix = '[' . strtoupper($level) . '] ';
    error_log($prefix . $message);
}

/**
 * Create a JSON response
 * 
 * @param bool $success Whether the request was successful
 * @param string $message The message to display
 * @param array $data Additional data to include in the response
 * @return string The JSON response
 */
function json_response($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    return json_encode($response);
}

/**
 * Check if a user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if a user has admin privileges
 * 
 * @return bool True if admin, false otherwise
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
?> 