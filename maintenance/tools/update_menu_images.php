<?php
require_once 'config/db.php';

// Function to safely execute queries and handle errors
function executeQuery($conn, $query, $params = []) {
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        error_log("Error preparing statement: " . mysqli_error($conn));
        return false;
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Error executing statement: " . mysqli_error($conn));
        return false;
    }

    return true;
}

// Array of menu items with their corresponding image paths
$menu_items = [
    // Rice Meals
    ['name' => 'Adobo with Rice', 'image_path' => 'assets/images/menu/Rice Meals/adobo.jpg'],
    ['name' => 'Bicol Express with Rice', 'image_path' => 'assets/images/menu/Rice Meals/bicol-express.jpg'],
    ['name' => 'Fried Chicken with Rice', 'image_path' => 'assets/images/menu/Rice Meals/fried-chicken.jpg'],
    ['name' => 'Pastil', 'image_path' => 'assets/images/menu/Rice Meals/pastil.jpg'],
    ['name' => 'Sinigang with Rice', 'image_path' => 'assets/images/menu/Rice Meals/sinigang with rice.jpg'],
    
    // Burgers
    ['name' => 'Cheese Burger', 'image_path' => 'assets/images/menu/Burgers/cheese-burger.jpg'],
    ['name' => 'Plain Burger', 'image_path' => 'assets/images/menu/Burgers/plain-burger.jpg'],
    
    // Desserts
    ['name' => 'Banana Split', 'image_path' => 'assets/images/menu/Desserts/banana-split.jpg'],
    ['name' => 'Halo Halo', 'image_path' => 'assets/images/menu/Desserts/halo-halo.jpg'],
    ['name' => 'Leche Flan', 'image_path' => 'assets/images/menu/Desserts/leche-flan.jpg'],
    ['name' => 'Mais Con Yelo', 'image_path' => 'assets/images/menu/Desserts/mais-con-yelo.jpg'],
    
    // Beverages
    ['name' => 'C2 Green', 'image_path' => 'assets/images/menu/Beverages/c2-green.jpg'],
    ['name' => 'C2 Red', 'image_path' => 'assets/images/menu/Beverages/c2-red.jpg'],
    ['name' => 'C2 Yellow', 'image_path' => 'assets/images/menu/Beverages/c2-yellow.jpg'],
    ['name' => 'Calamansi Juice', 'image_path' => 'assets/images/menu/Beverages/calamansi-juice.jpg'],
    ['name' => 'Coke', 'image_path' => 'assets/images/menu/Beverages/coke.jpg'],
    ['name' => 'Mango Juice', 'image_path' => 'assets/images/menu/Beverages/mango-juice.jpg'],
    ['name' => 'Royal', 'image_path' => 'assets/images/menu/Beverages/royal.jpg'],
    ['name' => 'Sprite', 'image_path' => 'assets/images/menu/Beverages/sprite.jpg']
];

// Update each menu item with its image path
foreach ($menu_items as $item) {
    $query = "UPDATE menu_items SET image_path = ? WHERE name = ?";
    $params = [$item['image_path'], $item['name']];
    
    if (executeQuery($conn, $query, $params)) {
        echo "Updated image for {$item['name']}\n";
    } else {
        echo "Failed to update image for {$item['name']}\n";
    }
}

// Update Halo-Halo image
$update_halo = "UPDATE menu_items SET 
                image_path = 'assets/images/menu/halo-halo.jpg',
                image_url = 'assets/images/menu/halo-halo.jpg'
                WHERE name = 'Halo-Halo'";
mysqli_query($conn, $update_halo);

// Update Pastil with Rice image
$update_pastil = "UPDATE menu_items SET 
                  image_path = 'assets/images/menu/pastil-with-rice.jpg',
                  image_url = 'assets/images/menu/pastil-with-rice.jpg'
                  WHERE name = 'Pastil with Rice'";
mysqli_query($conn, $update_pastil);

echo "Image update process completed.\n";
mysqli_close($conn);
?> 