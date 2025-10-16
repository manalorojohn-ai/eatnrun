<?php
header('Content-Type: application/json');

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
        'message' => 'External database connection failed: ' . $ext_conn->connect_error,
        'data' => []
    ]);
    exit;
}

// Set charset
$ext_conn->set_charset("utf8mb4");

// Fetch ratings from external database
$query = "SELECT 
            id,
            rating,
            comment,
            created_at,
            updated_at,
            order_id,
            'external' as source
          FROM eatnrun_rating 
          ORDER BY created_at DESC";

$result = $ext_conn->query($query);

$ratings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ratings[] = $row;
    }
    $result->free();
}

// Close the connection
$ext_conn->close();

// Return the data as JSON
echo json_encode([
    'success' => true,
    'message' => 'External ratings fetched successfully',
    'data' => $ratings
]); 