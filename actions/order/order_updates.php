<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

session_start();
require_once 'config/db.php';

// Function to send SSE data
function sendSSEData($data) {
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendSSEData(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$last_check = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-1 minute'));

while (true) {
    // Check for new updates
    $query = "SELECT id, status, payment_status, updated_at 
              FROM orders 
              WHERE user_id = ? 
              AND updated_at > ?";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $last_check);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $updates = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $updates[] = $row;
    }
    
    if (!empty($updates)) {
        sendSSEData($updates);
        $last_check = date('Y-m-d H:i:s');
    }
    
    // Close the statement
    mysqli_stmt_close($stmt);
    
    // Wait for 2 seconds before next check
    sleep(2);
    
    // Clear output buffer
    if (connection_aborted()) {
        exit();
    }
}
?> 