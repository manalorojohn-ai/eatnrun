<?php
require_once 'connection.php';

// Create menu_items table with category
$create_menu_items = "CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) NOT NULL,
    category VARCHAR(50) DEFAULT 'Rice Meals',
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_menu_items)) {
    echo "Menu items table created successfully\n";
} else {
    echo "Error creating menu items table: " . mysqli_error($conn) . "\n";
}

// Clear existing items
mysqli_query($conn, "TRUNCATE TABLE menu_items");

// Insert all menu items
$menu_items = [
    // Rice Meals
    [
        'name' => 'Adobo With Rice',
        'description' => 'A classic Filipino dish made of pork simmered in soy sauce, vinegar, and garlic',
        'price' => 80.00,
        'image' => 'adobo-with-rice.jpg',
        'category' => 'Rice Meals',
        'status' => 'available'
    ],
    [
        'name' => 'Burger Steak with Rice',
        'description' => 'Juicy burger steak served with steamed rice',
        'price' => 85.00,
        'image' => 'burger-steak-with-rice.jpg',
        'category' => 'Rice Meals',
        'status' => 'available'
    ],
    [
        'name' => 'Fried Chicken with Rice',
        'description' => 'Crispy fried chicken served with steamed rice',
        'price' => 90.00,
        'image' => 'fried-chicken-with-rice.jpg',
        'category' => 'Rice Meals',
        'status' => 'available'
    ],
    [
        'name' => 'Longganisa with Rice',
        'description' => 'Sweet Filipino sausage served with steamed rice',
        'price' => 85.00,
        'image' => 'longganisa-with-rice.jpg',
        'category' => 'Rice Meals',
        'status' => 'available'
    ],
    [
        'name' => 'Pork Tapa with Rice',
        'description' => 'Traditional cured pork tapa served with steamed rice',
        'price' => 85.00,
        'image' => 'pork-tapa-with-rice.jpg',
        'category' => 'Rice Meals',
        'status' => 'available'
    ],
    [
        'name' => 'Sinigang With Rice',
        'description' => 'A sour and savory soup with pork or shrimp served with steamed rice',
        'price' => 90.00,
        'image' => 'sinigang-with-rice.jpg',
        'category' => 'Rice Meals',
        'status' => 'available'
    ],
    [
        'name' => 'Sisig with Rice',
        'description' => 'Sizzling chopped pork with rice',
        'price' => 90.00,
        'image' => 'sisig-with-rice.jpg',
        'category' => 'Rice Meals',
        'status' => 'available'
    ],
    [
        'name' => 'Tocino with Rice',
        'description' => 'Sweet cured pork tocino served with steamed rice',
        'price' => 85.00,
        'image' => 'tocino-with-rice.jpg',
        'category' => 'Rice Meals',
        'status' => 'available'
    ],
    
    // Burgers
    [
        'name' => 'Cheese Burger',
        'description' => 'Juicy beef patty with melted cheese and fresh vegetables',
        'price' => 50.00,
        'image' => 'cheese-burger.jpg',
        'category' => 'Burgers',
        'status' => 'available'
    ],
    [
        'name' => 'Plain Burger',
        'description' => 'Simple yet delicious beef burger with fresh herbs',
        'price' => 40.00,
        'image' => 'plain-burger.jpg',
        'category' => 'Burgers',
        'status' => 'available'
    ],
    
    // Beverages
    [
        'name' => 'Calamansi Juice',
        'description' => 'Refreshing calamansi juice',
        'price' => 50.00,
        'image' => 'calamansi-juice.jpg',
        'category' => 'Beverages',
        'status' => 'available'
    ],
    [
        'name' => 'Coke',
        'description' => 'Refreshing Coca-Cola',
        'price' => 20.00,
        'image' => 'coke.jpg',
        'category' => 'Beverages',
        'status' => 'available'
    ],
    [
        'name' => 'Mango Juice',
        'description' => 'Fresh mango juice',
        'price' => 50.00,
        'image' => 'mango-juice.jpg',
        'category' => 'Beverages',
        'status' => 'available'
    ],
    [
        'name' => 'Royal',
        'description' => 'Orange flavored soft drink',
        'price' => 20.00,
        'image' => 'royal.jpg',
        'category' => 'Beverages',
        'status' => 'available'
    ],
    [
        'name' => 'Sprite',
        'description' => 'Lemon-lime soda',
        'price' => 20.00,
        'image' => 'sprite.jpg',
        'category' => 'Beverages',
        'status' => 'available'
    ],
    
    // Desserts
    [
        'name' => 'Halo-Halo',
        'description' => 'Filipino dessert with mixed sweets, shaved ice, and milk',
        'price' => 65.00,
        'image' => 'halo-halo.jpg',
        'category' => 'Desserts',
        'status' => 'available'
    ],
    [
        'name' => 'Leche Flan',
        'description' => 'Creamy caramel custard dessert',
        'price' => 50.00,
        'image' => 'leche-flan.jpg',
        'category' => 'Desserts',
        'status' => 'available'
    ]
];

foreach ($menu_items as $item) {
    $insert_query = "INSERT INTO menu_items (name, description, price, image, category, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "ssdsss", 
        $item['name'], 
        $item['description'], 
        $item['price'], 
        $item['image'], 
        $item['category'],
        $item['status']
    );
    mysqli_stmt_execute($stmt);
}

echo "All menu items inserted successfully\n";

mysqli_close($conn);
?> 