<?php
session_start();
header('Content-Type: application/json');

// Require database connection
require_once '../config/db.php';

// Get parameters
$name = isset($_GET['name']) ? $_GET['name'] : '';
$price = isset($_GET['price']) ? floatval($_GET['price']) : 0;

if (empty($name) || $price <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Name and price are required'
    ]);
    exit;
}

// Try to find the menu item by name and price (with some tolerance for price)
$query = "SELECT id, name, price FROM menu_items 
          WHERE name LIKE ? 
          AND price BETWEEN ? * 0.95 AND ? * 1.05
          LIMIT 1";

$stmt = mysqli_prepare($conn, $query);
$nameLike = "%" . $name . "%";
mysqli_stmt_bind_param($stmt, "sdd", $nameLike, $price, $price);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'success' => true,
        'menu_item_id' => $row['id'],
        'name' => $row['name'],
        'price' => $row['price']
    ]);
} else {
    // If not found by name and price, try just by name
    $query = "SELECT id, name, price FROM menu_items 
              WHERE name LIKE ? 
              LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $nameLike);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode([
            'success' => true,
            'menu_item_id' => $row['id'],
            'name' => $row['name'],
            'price' => $row['price']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Menu item not found'
        ]);
    }
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 