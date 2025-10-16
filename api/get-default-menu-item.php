<?php
session_start();
header('Content-Type: application/json');

// Require database connection
require_once '../config/db.php';

// Get the first available menu item from the database
$query = "SELECT id, name, price FROM menu_items ORDER BY id ASC LIMIT 1";
$result = mysqli_query($conn, $query);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'success' => true,
        'menu_item_id' => $row['id'],
        'name' => $row['name'],
        'price' => $row['price']
    ]);
} else {
    // If no menu items exist, create a default one
    $query = "INSERT INTO menu_items (name, price, description, category, image) 
              VALUES ('Default Item', 100, 'Default menu item for rating purposes', 'Other', '/assets/images/menu/default-food.jpg')";
    
    if (mysqli_query($conn, $query)) {
        $new_id = mysqli_insert_id($conn);
        echo json_encode([
            'success' => true,
            'menu_item_id' => $new_id,
            'name' => 'Default Item',
            'price' => 100,
            'created' => true
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Could not create default menu item: ' . mysqli_error($conn)
        ]);
    }
}

mysqli_close($conn);
?> 