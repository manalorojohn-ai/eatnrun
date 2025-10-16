<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include database connection
require_once '../config/db.php';

try {
    // Build query with filters
    $query = "SELECT o.*, u.full_name, u.phone 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              WHERE 1=1";
    $params = [];
    $types = "";
    
    // Status filter
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $query .= " AND o.status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }
    
    // Date filter
    if (isset($_GET['date']) && !empty($_GET['date'])) {
        $query .= " AND DATE(o.created_at) = ?";
        $params[] = $_GET['date'];
        $types .= "s";
    }
    
    // Order by latest first
    $query .= " ORDER BY o.created_at DESC";
    
    // Prepare and execute query
    $stmt = mysqli_prepare($conn, $query);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to fetch orders: " . mysqli_error($conn));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $orders = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Format dates
        $row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        if ($row['updated_at']) {
            $row['updated_at'] = date('Y-m-d H:i:s', strtotime($row['updated_at']));
        }
        
        // Format status
        $row['status'] = ucfirst(strtolower($row['status']));
        
        // Format amounts
        $row['total_amount'] = number_format((float)$row['total_amount'], 2, '.', '');
        if (isset($row['delivery_fee'])) {
            $row['delivery_fee'] = number_format((float)$row['delivery_fee'], 2, '.', '');
        }
        
        $orders[] = $row;
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);

} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Close database connection
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?> 