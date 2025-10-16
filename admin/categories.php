<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Get all categories with product counts
$query = "
    SELECT 
        c.*,
        COUNT(mi.id) as product_count
    FROM categories c
    LEFT JOIN menu_items mi ON c.id = mi.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-light: #00A65A;
            --primary-dark: #005530;
            --white: #fff;
            --text-color: #333;
            --text-light: #666;
            --border-radius: 16px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 240px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .admin-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-color);
        }

        .add-category-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-category-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .categories-container {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .categories-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .categories-table th,
        .categories-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .categories-table th {
            font-weight: 600;
            color: var(--text-color);
            background: #f8f9fa;
        }

        .categories-table tr:hover {
            background: #f8f9fa;
        }

        .category-name {
            font-weight: 500;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-icon {
            width: 40px;
            height: 40px;
            background: rgba(0, 108, 59, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
        }

        .product-count {
            background: #e8f5e9;
            color: var(--primary-color);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .edit-btn, .delete-btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .edit-btn {
            background: #e3f2fd;
            color: #1976d2;
        }

        .delete-btn {
            background: #ffebee;
            color: #d32f2f;
        }

        .edit-btn:hover, .delete-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="admin-header">
            <h1 class="admin-title">Categories Management</h1>
            <button class="add-category-btn" onclick="location.href='add-category.php'">
                <i class="fas fa-plus"></i>
                Add New Category
            </button>
        </div>

        <div class="categories-container">
            <table class="categories-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Products</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <div class="category-name">
                                        <div class="category-icon">
                                            <i class="fas fa-utensils"></i>
                                        </div>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="product-count">
                                        <?php echo $category['product_count']; ?> products
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="edit-btn" onclick="editCategory(<?php echo $category['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="delete-btn" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No categories found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function editCategory(categoryId) {
            window.location.href = `edit-category.php?id=${categoryId}`;
        }

        function deleteCategory(categoryId) {
            if (confirm('Are you sure you want to delete this category? All associated products will be uncategorized.')) {
                window.location.href = `delete-category.php?id=${categoryId}`;
            }
        }
    </script>
</body>
</html> 