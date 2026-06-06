<?php
/**
 * Database Initialization Script
 * Creates all necessary tables and inserts sample data
 */

require_once dirname(__DIR__, 2) . '/config/database/db.php';

echo "=== Database Initialization ===\n\n";

// Create tables
$tables = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'customer',
        status VARCHAR(50) DEFAULT 'active',
        profile_image VARCHAR(255),
        phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'categories' => "CREATE TABLE IF NOT EXISTS categories (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        description TEXT,
        status VARCHAR(50) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'menu_items' => "CREATE TABLE IF NOT EXISTS menu_items (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        category_id INTEGER REFERENCES categories(id),
        image_path VARCHAR(255),
        status VARCHAR(50) DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'cart' => "CREATE TABLE IF NOT EXISTS cart (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id),
        menu_item_id INTEGER NOT NULL REFERENCES menu_items(id),
        quantity INTEGER NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, menu_item_id)
    )",
    
    'orders' => "CREATE TABLE IF NOT EXISTS orders (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id),
        total_amount DECIMAL(10, 2) NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        payment_method VARCHAR(50),
        delivery_address TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'order_items' => "CREATE TABLE IF NOT EXISTS order_items (
        id SERIAL PRIMARY KEY,
        order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
        menu_item_id INTEGER NOT NULL REFERENCES menu_items(id),
        quantity INTEGER NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'reviews' => "CREATE TABLE IF NOT EXISTS reviews (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id),
        menu_item_id INTEGER NOT NULL REFERENCES menu_items(id),
        rating INTEGER CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'notifications' => "CREATE TABLE IF NOT EXISTS notifications (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id),
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'info',
        status VARCHAR(50) DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

// Create each table
foreach ($tables as $table_name => $sql) {
    try {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            echo "✓ Table '$table_name' created/verified\n";
        } else {
            echo "✗ Failed to create table '$table_name'\n";
            echo "  Error: " . mysqli_error($conn) . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception creating table '$table_name': " . $e->getMessage() . "\n";
    }
}

echo "\n=== Inserting Sample Data ===\n\n";

// Insert categories
$categories = [
    ['Burgers', 'Delicious beef and chicken burgers'],
    ['Pizza', 'Wood-fired artisan pizzas'],
    ['Pasta', 'Traditional Italian pasta dishes'],
    ['Beverages', 'Refreshing drinks and smoothies'],
    ['Desserts', 'Sweet treats to end your meal'],
    ['Filipino Dishes', 'Traditional Filipino cuisine']
];

foreach ($categories as $cat) {
    $check = "SELECT id FROM categories WHERE name = '" . mysqli_real_escape_string($conn, $cat[0]) . "'";
    $result = mysqli_query($conn, $check);
    if (mysqli_num_rows($result) == 0) {
        $insert = "INSERT INTO categories (name, description, status) VALUES ('" . 
                  mysqli_real_escape_string($conn, $cat[0]) . "', '" . 
                  mysqli_real_escape_string($conn, $cat[1]) . "', 'active')";
        if (mysqli_query($conn, $insert)) {
            echo "✓ Category '{$cat[0]}' inserted\n";
        }
    } else {
        echo "- Category '{$cat[0]}' already exists\n";
    }
}

// Insert sample menu items
$menu_items = [
    ['Plain Burger', 'Classic beef burger with lettuce and tomato', 150.00, 'Burgers', 'assets/images/menu/plain-burger.jpg'],
    ['Cheese Burger', 'Beef burger with melted cheese', 180.00, 'Burgers', 'assets/images/menu/cheese-burger.jpg'],
    ['Margherita Pizza', 'Fresh tomatoes, mozzarella, and basil', 350.00, 'Pizza', 'assets/images/menu/margherita-pizza.jpg'],
    ['Pepperoni Pizza', 'Classic pepperoni with mozzarella cheese', 400.00, 'Pizza', 'assets/images/menu/pepperoni-pizza.jpg'],
    ['Carbonara', 'Creamy pasta with bacon and parmesan', 220.00, 'Pasta', 'assets/images/menu/carbonara.jpg'],
    ['Spaghetti Bolognese', 'Rich meat sauce with spaghetti', 200.00, 'Pasta', 'assets/images/menu/bolognese.jpg'],
    ['Mango Juice', 'Fresh mango smoothie', 80.00, 'Beverages', 'assets/images/menu/mango-juice.jpg'],
    ['Iced Coffee', 'Refreshing cold brew coffee', 120.00, 'Beverages', 'assets/images/menu/iced-coffee.jpg'],
    ['Chocolate Cake', 'Rich chocolate layer cake', 150.00, 'Desserts', 'assets/images/menu/chocolate-cake.jpg'],
    ['Halo-Halo', 'Traditional Filipino shaved ice dessert', 120.00, 'Desserts', 'assets/images/menu/halo-halo.jpg'],
    ['Adobo', 'Classic Filipino chicken adobo', 180.00, 'Filipino Dishes', 'assets/images/menu/adobo.jpg'],
    ['Bicol Express', 'Spicy pork in coconut milk', 200.00, 'Filipino Dishes', 'assets/images/menu/bicol-express.jpg']
];

foreach ($menu_items as $item) {
    // Get category ID
    $cat_query = "SELECT id FROM categories WHERE name = '" . mysqli_real_escape_string($conn, $item[3]) . "'";
    $cat_result = mysqli_query($conn, $cat_query);
    $cat_row = mysqli_fetch_assoc($cat_result);
    
    if ($cat_row) {
        $check = "SELECT id FROM menu_items WHERE name = '" . mysqli_real_escape_string($conn, $item[0]) . "'";
        $result = mysqli_query($conn, $check);
        if (mysqli_num_rows($result) == 0) {
            $insert = "INSERT INTO menu_items (name, description, price, category_id, image_path, status) VALUES ('" . 
                      mysqli_real_escape_string($conn, $item[0]) . "', '" . 
                      mysqli_real_escape_string($conn, $item[1]) . "', " . 
                      $item[2] . ", " . 
                      $cat_row['id'] . ", '" . 
                      mysqli_real_escape_string($conn, $item[4]) . "', 'available')";
            if (mysqli_query($conn, $insert)) {
                echo "✓ Menu item '{$item[0]}' inserted\n";
            }
        } else {
            echo "- Menu item '{$item[0]}' already exists\n";
        }
    }
}

echo "\n=== Database Initialization Complete ===\n";
echo "\nYou can now use the application!\n";
?>
