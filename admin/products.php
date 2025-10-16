<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle product status updates
if (isset($_POST['update_status'])) {
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_query = "UPDATE menu_items SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $product_id);
    mysqli_stmt_execute($stmt);
}

// Get all products with their categories
$query = "SELECT m.*, c.name as category_name,
          COALESCE(AVG(r.rating), 0) as avg_rating,
          COUNT(DISTINCT r.id) as total_ratings,
          COUNT(DISTINCT oi.id) as total_orders
          FROM menu_items m 
          LEFT JOIN categories c ON m.category_id = c.id
          LEFT JOIN ratings r ON m.id = r.menu_item_id
          LEFT JOIN order_items oi ON m.id = oi.menu_item_id
          GROUP BY m.id
          ORDER BY m.category_id, m.name";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            color: #333;
            margin: 0;
            font-size: 1.5rem;
        }

        .add-product-btn {
            background: #006C3B;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.3s ease;
        }

        .add-product-btn:hover {
            background: #005530;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-content {
            padding: 1.5rem;
        }

        .product-category {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .product-price {
            color: #006C3B;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .product-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .product-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-unavailable {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #006C3B;
            color: white;
        }

        .btn-primary:hover {
            background: #005530;
        }

        .btn-secondary {
            background: #e9ecef;
            color: #333;
        }

        .btn-secondary:hover {
            background: #dde2e6;
        }

        @media (max-width: 768px) {
            .header-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .add-product-btn {
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin-navbar.php'; ?>

    <div class="container">
        <div class="header-actions">
            <h1 class="page-title">Manage Products</h1>
            <a href="add_product.php" class="add-product-btn">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        </div>

        <div class="products-grid">
            <?php while ($product = mysqli_fetch_assoc($result)): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="product-image">
                    
                    <div class="product-content">
                        <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                        
                        <div class="product-stats">
                            <div class="stat">
                                <i class="fas fa-star" style="color: #ffc107;"></i>
                                <?php echo number_format($product['avg_rating'], 1); ?>
                                (<?php echo $product['total_ratings']; ?>)
                            </div>
                            <div class="stat">
                                <i class="fas fa-shopping-bag"></i>
                                <?php echo $product['total_orders']; ?> orders
                            </div>
                        </div>

                        <span class="product-status status-<?php echo $product['status']; ?>">
                            <?php echo ucfirst($product['status']); ?>
                        </span>

                        <div class="action-buttons">
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button class="btn btn-secondary" onclick="toggleStatus(<?php echo $product['id']; ?>)">
                                <i class="fas fa-power-off"></i> Toggle Status
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
        function toggleStatus(productId) {
            if (confirm('Are you sure you want to toggle this product\'s status?')) {
                // Implement status toggle functionality
                // You can use AJAX to update the status without page reload
            }
        }
    </script>
</body>
</html> 