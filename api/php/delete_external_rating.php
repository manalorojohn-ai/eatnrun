<?php
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Rating ID is required'
    ]);
    exit;
}

$rating_id = intval($_GET['id']);

// Define external database connection parameters
define('EXT_DB_HOST', 'localhost'); // Change this to the external server IP/hostname
define('EXT_DB_USER', 'root');      // Change to external database username
define('EXT_DB_PASS', '');          // Change to external database password
define('EXT_DB_NAME', 'hotel_management');  // External database name

// Create connection to external database
$ext_conn = new mysqli(EXT_DB_HOST, EXT_DB_USER, EXT_DB_PASS, EXT_DB_NAME);

// Check connection
if ($ext_conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'External database connection failed: ' . $ext_conn->connect_error
    ]);
    exit;
}

// Set charset
$ext_conn->set_charset("utf8mb4");

// Prepare and execute delete statement
$stmt = $ext_conn->prepare("DELETE FROM eatnrun_rating WHERE id = ?");
$stmt->bind_param("i", $rating_id);
$result = $stmt->execute();

if ($result) {
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    $ext_conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => ($affected_rows > 0) ? 'Rating deleted successfully' : 'No rating found with the provided ID',
        'affected_rows' => $affected_rows
    ]);
} else {
    $stmt->close();
    $ext_conn->close();
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete rating: ' . $ext_conn->error
    ]);
} 