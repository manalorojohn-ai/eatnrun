<?php
session_start();
require_once 'config/db.php';

// Handle reorder functionality
if (isset($_GET['reorder']) && !empty($_GET['reorder'])) {
    $order_id = intval($_GET['reorder']);
    
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // Get items from the previous order with menu item details
        $items_query = "SELECT oi.menu_item_id, oi.quantity, mi.name, mi.price, mi.status 
                       FROM order_items oi 
                       JOIN menu_items mi ON oi.menu_item_id = mi.id 
                       WHERE oi.order_id = ?";
        
        $stmt = mysqli_prepare($conn, $items_query);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to execute query: " . mysqli_stmt_error($stmt));
        }
        
        $result = mysqli_stmt_get_result($stmt);
        
        // Get total items in original order
        $total_query = "SELECT COUNT(*) as total FROM order_items WHERE order_id = ?";
        $total_stmt = mysqli_prepare($conn, $total_query);
        mysqli_stmt_bind_param($total_stmt, "i", $order_id);
        mysqli_stmt_execute($total_stmt);
        $total_result = mysqli_stmt_get_result($total_stmt);
        $total_row = mysqli_fetch_assoc($total_result);
        $total_items = $total_row['total'];
        
        $available_items = 0;
        
        // Process each item
        while ($item = mysqli_fetch_assoc($result)) {
            if ($item['status'] === 'available') {
                // Check if item already exists in cart
                $check_cart = "SELECT id, quantity FROM cart WHERE user_id = ? AND menu_item_id = ?";
                $cart_stmt = mysqli_prepare($conn, $check_cart);
                mysqli_stmt_bind_param($cart_stmt, "ii", $_SESSION['user_id'], $item['menu_item_id']);
                mysqli_stmt_execute($cart_stmt);
                $cart_result = mysqli_stmt_get_result($cart_stmt);
                $cart_item = mysqli_fetch_assoc($cart_result);
                mysqli_stmt_close($cart_stmt);
                
                if ($cart_item) {
                    // Update quantity if item exists
                    $new_quantity = $cart_item['quantity'] + $item['quantity'];
                    $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "ii", $new_quantity, $cart_item['id']);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                } else {
                    // Add new item to cart
                    $insert_query = "INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?)";
                    $insert_stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($insert_stmt, "iii", $_SESSION['user_id'], $item['menu_item_id'], $item['quantity']);
                    mysqli_stmt_execute($insert_stmt);
                    mysqli_stmt_close($insert_stmt);
                }
                
                $available_items++;
            }
        }
        
        mysqli_stmt_close($stmt);
        mysqli_stmt_close($total_stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Set appropriate message based on available items
        if ($available_items === 0) {
            $_SESSION['error'] = "Sorry, none of the items from your previous order are currently available.";
        } elseif ($available_items < $total_items) {
            $_SESSION['warning'] = "Some items from your previous order are no longer available. We've added the available items to your cart.";
        } else {
            $_SESSION['success'] = "All items from your previous order have been added to your cart!";
        }
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "An error occurred while processing your request: " . $e->getMessage();
    }
    
    // Redirect to remove the reorder parameter
    header("Location: menu.php");
    exit();
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Function to safely get categories
function getCategories($conn) {
    $categories = [];
    $query = "SELECT id, name FROM categories WHERE status = 'active' ORDER BY name";
    
    try {
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $categories[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching categories: " . $e->getMessage());
    }
    
    return $categories;
}

// Function to get menu items by category
function getMenuItems($conn, $category_name = null) {
    $menu_items = [];
    $where_clause = $category_name ? 
        "WHERE c.name = '" . mysqli_real_escape_string($conn, $category_name) . "' AND m.status = 'available'" : 
        "WHERE m.status = 'available'";
    
    $query = "SELECT m.*, c.name as category_name,
              COALESCE(m.image_path, 'assets/images/default-food.jpg') as image_path,
              COALESCE(AVG(r.rating), 0) as average_rating,
              COUNT(DISTINCT r.id) as total_ratings
          FROM menu_items m 
          LEFT JOIN categories c ON m.category_id = c.id 
              LEFT JOIN ratings r ON m.id = r.menu_item_id
              $where_clause 
              GROUP BY m.id
              ORDER BY m.name";
          
    try {
$result = mysqli_query($conn, $query);
        if ($result) {
while ($row = mysqli_fetch_assoc($result)) {
    $menu_items[] = $row;
}
        }
    } catch (Exception $e) {
        error_log("Error fetching menu items: " . $e->getMessage());
    }
    
    return $menu_items;
}

// Update the image paths in the query
$query = "UPDATE menu_items SET 
          image_path = CASE 
              WHEN name = 'Halo-Halo' THEN 'assets/images/menu/halo-halo.jpg'
              WHEN name = 'Pastil with Rice' THEN 'assets/images/menu/pastil.jpg'
              ELSE image_path 
          END,
          image_url = CASE 
              WHEN name = 'Halo-Halo' THEN 'assets/images/menu/halo-halo.jpg'
              WHEN name = 'Pastil with Rice' THEN 'assets/images/menu/pastil.jpg'
              ELSE image_url 
          END
          WHERE name IN ('Halo-Halo', 'Pastil with Rice')";

mysqli_query($conn, $query);

// Get all menu items with updated paths
$menu_query = "SELECT * FROM menu_items WHERE status = 'available' ORDER BY category_id, name";

// Get active categories
$categories = getCategories($conn);

// Get selected category
$selected_category = isset($_GET['category']) ? $_GET['category'] : null;

// Get menu items
$menu_items = getMenuItems($conn, $selected_category);

// Get ratings for menu items
$ratings_query = "SELECT menu_item_id, AVG(rating) as average_rating, COUNT(id) as rating_count 
                 FROM ratings 
                 GROUP BY menu_item_id";
$ratings_result = mysqli_query($conn, $ratings_query);

$ratings = [];
if ($ratings_result) {
while ($rating = mysqli_fetch_assoc($ratings_result)) {
    $ratings[$rating['menu_item_id']] = [
        'average' => round($rating['average_rating'], 1),
        'count' => $rating['rating_count']
    ];
}
mysqli_free_result($ratings_result);
}

// Handle adding to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $item_id = mysqli_real_escape_string($conn, $_POST['item_id']);
    $user_id = $_SESSION['user_id'];

    // Check if item already exists in cart
    $check_cart = mysqli_query($conn, "SELECT id, quantity FROM cart WHERE user_id = '$user_id' AND menu_item_id = '$item_id'");
    
    if ($existing_item = mysqli_fetch_assoc($check_cart)) {
        mysqli_query($conn, "UPDATE cart SET quantity = quantity + 1 WHERE id = '{$existing_item['id']}'");
    } else {
        mysqli_query($conn, "INSERT INTO cart (user_id, menu_item_id, quantity) VALUES ('$user_id', '$item_id', 1)");
    }

    // Redirect to cart.php instead of menu.php
    header("Location: cart.php");
            exit();
        }

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
            exit();
        }

    $item_id = mysqli_real_escape_string($conn, $_POST['item_id']);
    $rating = mysqli_real_escape_string($conn, $_POST['rating']);
    $review = isset($_POST['review']) ? mysqli_real_escape_string($conn, $_POST['review']) : '';
    $user_id = $_SESSION['user_id'];

    // Check if user already rated this item
    $check_rating = mysqli_query($conn, "SELECT id FROM ratings WHERE user_id = '$user_id' AND menu_item_id = '$item_id'");
    
    if (mysqli_num_rows($check_rating) > 0) {
        // Update existing rating
        $rating_id = mysqli_fetch_assoc($check_rating)['id'];
        mysqli_query($conn, "UPDATE ratings SET rating = '$rating', review = '$review', updated_at = NOW() WHERE id = '$rating_id'");
    } else {
        // Insert new rating
        mysqli_query($conn, "INSERT INTO ratings (user_id, menu_item_id, rating, review) VALUES ('$user_id', '$item_id', '$rating', '$review')");
    }

    // Redirect to prevent form resubmission
    header("Location: menu.php?rated=1#item-" . $item_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Menu - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-light: #00A65A;
            --primary-dark: #005530;
            --accent-color: #FFD700;
            --text-color: #333333;
            --text-light: #666666;
            --bg-color: #f8f9fa;
            --white: #ffffff;
            
            --transition: all 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
            --shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.15);
            
            --border-radius-sm: 10px;
            --border-radius-md: 16px;
            --border-radius-lg: 24px;
            --border-radius-xl: 32px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }

        body {
            background: var(--bg-color);
            min-height: 100vh;
            line-height: 1.6;
            color: var(--text-color);
            overflow-x: hidden;
            overflow-y: auto;
            animation: fadeIn 0.5s ease-in-out;
            position: relative;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            padding-top: 4rem;
        }

        /* Page title styles */
        .page-title {
            text-align: center;
            margin: 2rem 0 3rem;
            opacity: 1;
            transform: none;
            animation: none;
            position: relative;
            z-index: 1;
        }

        .page-title h1 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 700;
            position: relative;
            display: inline-block;
            opacity: 1;
        }

        .page-title h1::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
            animation: widthPulse 3s infinite alternate;
        }

        .page-title p {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 1.5rem auto 0;
        }

        /* Filter styles */
        .filter-section {
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 20px 0;
            margin: 10px 0 30px;
            position: relative;
        }

        .category-wrapper {
            background: #ffffff;
            border-radius: 50px;
            padding: 10px;
            display: inline-flex;
            gap: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.04);
            position: relative;
            transition: all 0.3s ease;
        }

        .category-wrapper:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        .category-link {
            padding: 8px 24px;
            border-radius: 25px;
            color: #555;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            background: transparent;
            letter-spacing: 0.2px;
        }

        .category-link:hover {
            color: var(--primary-color);
            transform: translateY(-1px);
        }

        .category-link.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 108, 59, 0.2);
        }

        .category-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 108, 59, 0.04);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
            border-radius: 25px;
        }

        .category-link:hover::before {
            transform: scaleX(1);
            transform-origin: left;
        }

        .category-link.active::before {
            display: none;
        }

        @media (max-width: 768px) {
            .filter-section {
                padding: 15px 10px;
                margin: 0 0 20px;
            }

            .category-wrapper {
                overflow-x: auto;
                max-width: calc(100% - 20px);
                -ms-overflow-style: none;
                scrollbar-width: none;
                padding: 8px;
            }

            .category-wrapper::-webkit-scrollbar {
                display: none;
            }

            .category-link {
                padding: 8px 20px;
                font-size: 0.9rem;
                white-space: nowrap;
            }
        }

        /* Menu grid styles */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            padding: 10px 0;
            opacity: 1;
            transform: none;
            transition: all 0.3s ease;
        }

        .menu-item {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            isolation: isolate;
            visibility: visible !important;
            will-change: transform, box-shadow;
        }

        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .menu-item-image {
            height: 220px;
            width: 100%;
            overflow: hidden;
            position: relative;
            background-color: #f0f0f0;
        }

        .menu-item-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0) 70%, rgba(0,0,0,0.05) 100%);
            z-index: 1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .menu-item:hover .menu-item-image::after {
            opacity: 1;
        }

        .menu-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .menu-item:hover .menu-item-image img {
            transform: scale(1.08);
        }

        .menu-item-info {
            padding: 20px;
        }

        .category-tag {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(0, 108, 59, 0.08);
            color: var(--primary-color);
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .menu-item-name {
            font-size: 1.2rem;
            color: #333;
            margin: 8px 0;
            font-weight: 600;
        }

        .price {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 700;
            margin: 12px 0;
            display: block;
        }

        .action-buttons {
            display: flex;
            margin-top: 15px;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 10px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
        }

        .add-to-cart-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .add-to-cart-container {
            width: 100%;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        @keyframes widthPulse {
            from { width: 40px; }
            to { width: 80px; }
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-title h1 {
                font-size: 2rem;
            }

            .filter-section {
                padding: 15px 10px;
                margin: 0 0 20px;
            }

            .category-wrapper {
                overflow-x: auto;
                max-width: calc(100% - 20px);
                -ms-overflow-style: none;
                scrollbar-width: none;
                padding: 8px;
            }

            .category-wrapper::-webkit-scrollbar {
                display: none;
            }

            .category-link {
                padding: 8px 20px;
            font-size: 0.9rem;
                white-space: nowrap;
            }

            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
        }

        /* Rating modal styles */
        .rating-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .rating-modal.active {
            display: flex;
            animation: fadeIn 0.3s ease-out;
        }

        .rating-content {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .rating-header {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .rating-header h3 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .star-btn {
            background: none;
            border: none;
            font-size: 1.8rem;
            color: #ffd700;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .star-btn:hover {
            transform: scale(1.2);
        }

        .review-input {
            width: 100%;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 1rem 0;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }

        .rating-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }

        .submit-rating,
        .cancel-rating {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .submit-rating {
            background-color: var(--primary-color);
            color: white;
        }

        .cancel-rating {
            background-color: #f0f0f0;
            color: #666;
        }

        .submit-rating:hover {
            background-color: var(--primary-dark);
        }

        .cancel-rating:hover {
            background-color: #e0e0e0;
        }

        .ratings-summary {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .average-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.2rem;
            color: #666;
        }

        .rating-count {
            font-size: 0.9rem;
            color: #888;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--white);
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
            border-left: 4px solid var(--primary-color);
        }

        .toast.error {
            border-left-color: #e74c3c;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast i {
            font-size: 1.2rem;
        }

        .toast i.fa-check-circle {
            color: var(--primary-color);
        }

        .toast i.fa-exclamation-circle {
            color: #e74c3c;
        }

        .categories-wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 2rem 0;
            padding: 0 1rem;
        }

        .categories-container {
            background: white;
            padding: 8px;
            border-radius: 30px;
            display: flex;
            gap: 10px;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .category-btn {
            padding: 8px 20px;
            border-radius: 20px;
            color: #666;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background: transparent;
        }

        .category-btn:hover {
            color: var(--primary-color);
        }

        .category-btn.active {
            background-color: var(--primary-color);
            color: white;
        }

        @media (max-width: 768px) {
            .categories-container {
                overflow-x: auto;
                -ms-overflow-style: none;
                scrollbar-width: none;
                padding: 8px;
                gap: 8px;
            }

            .categories-container::-webkit-scrollbar {
                display: none;
            }

            .category-btn {
                padding: 8px 16px;
            font-size: 0.9rem;
                white-space: nowrap;
            }
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 0 auto 20px;
            max-width: 800px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease-out;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            font-size: 1rem;
            line-height: 1.5;
        }

        .alert i {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .alert-warning {
            background-color: #fff3e0;
            color: #ef6c00;
            border-left: 4px solid #ef6c00;
        }

        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Auto-hide functionality */
        .alert.hide {
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease-out;
        }

        .cart-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #006C3B;
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 108, 59, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(120%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10000;
        }

        .cart-notification.show {
            transform: translateX(0);
        }

        .cart-notification i {
            font-size: 20px;
        }

        .cart-notification-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .cart-notification-title {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .cart-notification-subtitle {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .cart-counter {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #dc3545;
            color: #fff;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
            position: static;
            padding: 2px;
        }

        .cart-counter.scaleIn {
            animation: counterScale 0.5s cubic-bezier(0.17, 0.67, 0.14, 1.53);
        }

        @keyframes counterScale {
            0% { 
                transform: scale(0.5); 
                opacity: 0;
            }
            50% { 
                transform: scale(1.2); 
            }
            100% { 
                transform: scale(1); 
                opacity: 1;
            }
        }

        /* Cart link styles */
        a[href="cart.php"] {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        a[href="cart.php"]:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        a[href="cart.php"] i {
            margin-right: 5px;
        }

        /* Animation for counter updates */
        @keyframes counterPop {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .cart-counter.update {
            animation: counterPop 0.3s ease-out;
        }

        /* Enhanced Notification Styles */
        .notification-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #006C3B;
            color: white;
            padding: 16px 20px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            transform: translateX(150%);
            transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 10000;
            min-width: 320px;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .notification-toast.error {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        .notification-toast.success {
            background: linear-gradient(135deg, #006C3B 0%, #005530 100%);
        }

        .notification-toast.show {
            transform: translateX(0);
            animation: slideIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55), 
                       float 3s ease-in-out infinite;
        }

        @keyframes slideIn {
            0% { transform: translateX(150%); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        .notification-toast i {
            font-size: 24px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            padding: 20px;
        }

        .notification-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .notification-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 2px;
        }

        .notification-message {
            font-size: 0.9rem;
            opacity: 0.9;
            line-height: 1.4;
        }

        .notification-progress {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 3px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0 0 16px 16px;
            overflow: hidden;
        }

        .notification-progress::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-100%);
            animation: progress 3s linear forwards;
        }

        @keyframes progress {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-title">
            <h1>Our Menu</h1>
        </div>

        <div class="filter-section">
            <div class="category-wrapper">
                <a href="menu.php" class="category-link <?php echo !isset($_GET['category']) ? 'active' : ''; ?>">
                    All
                </a>
                <?php 
                $categories_query = "SELECT * FROM categories ORDER BY id ASC";
                $categories_result = mysqli_query($conn, $categories_query);
                while ($category = mysqli_fetch_assoc($categories_result)): 
                ?>
                    <a href="menu.php?category=<?php echo urlencode($category['name']); ?>" 
                       class="category-link <?php echo (isset($_GET['category']) && $_GET['category'] === $category['name']) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="menu-container">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" id="errorMessage">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['warning'])): ?>
                <div class="alert alert-warning" id="warningMessage">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION['warning']); 
                    unset($_SESSION['warning']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" id="successMessage">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION['success']); 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['reorder_message'])): ?>
                <div class="success-message" id="reorderMessage">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION['reorder_message']); 
                    unset($_SESSION['reorder_message']);
                    ?>
                </div>
            <?php endif; ?>

        <div class="menu-grid">
            <?php foreach ($menu_items as $item): ?>
                <div class="menu-item" data-category="<?php echo htmlspecialchars($item['category_name']); ?>">
                    <div class="menu-item-image">
                                <?php 
                        $image_path = $item['image_path'];
                        $default_image = 'assets/images/menu/default-food.jpg';
                        $image_url = file_exists($image_path) ? $image_path : $default_image;
                        ?>
                        <img src="<?php echo htmlspecialchars($image_url); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             onerror="this.src='<?php echo $default_image; ?>'"
                             loading="lazy">
                            </div>
                    <div class="menu-item-info">
                        <div class="category-tag"><?php echo htmlspecialchars($item['category_name']); ?></div>
                        <h3 class="menu-item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="menu-item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                        <div class="menu-item-footer">
                            <span class="price">₱<?php echo number_format($item['price'], 2); ?></span>
                            <div class="action-buttons">
                                <div class="add-to-cart-container">
                                    <button type="button" class="add-to-cart-btn" 
                                            data-item-id="<?php echo $item['id']; ?>"
                                            data-item-name="<?php echo htmlspecialchars($item['name']); ?>">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="rating-modal" id="ratingModal">
        <!-- Rating modal content here -->
            </div>

    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span>Item added to cart!</span>
        </div>

    <?php include 'footer.php'; ?>
    
    <!-- Bootstrap 5 JS - Required for navbar notification dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="js/cart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.classList.add('hide');
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    });
    </script>
</body>
</html></html>
