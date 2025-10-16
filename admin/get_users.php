<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$type = $_GET['type'] ?? 'customers';

if ($type === 'customers') {
    $query = "SELECT u.*, 
              COUNT(DISTINCT o.id) as total_orders,
              SUM(o.total_amount) as total_spent
              FROM users u 
              LEFT JOIN orders o ON u.id = o.user_id
              WHERE u.role = 'customer'
              GROUP BY u.id
              ORDER BY u.created_at DESC";
} else {
    $query = "SELECT u.*, 
              MAX(l.created_at) as last_login
              FROM users u 
              LEFT JOIN login_logs l ON u.id = l.user_id
              WHERE u.role = 'admin'
              GROUP BY u.id
              ORDER BY u.created_at DESC";
}

$result = mysqli_query($conn, $query);
$users = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Remove sensitive information
    unset($row['password']);
    $users[] = $row;
}

header('Content-Type: application/json');
echo json_encode($users); 