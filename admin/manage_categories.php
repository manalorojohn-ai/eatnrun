<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle category actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':name' => $_POST['name'],
                    ':description' => $_POST['description']
                ]);
                break;

            case 'edit':
                $query = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':id' => $_POST['id'],
                    ':name' => $_POST['name'],
                    ':description' => $_POST['description']
                ]);
                break;

            case 'delete':
                $query = "DELETE FROM categories WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute([':id' => $_POST['id']]);
                break;
        }
        header("Location: manage_categories.php");
        exit();
    }
}

// Get all categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->query($query);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #fff;
            line-height: 1.6;
        }

        .hero-section {
            padding: 40px 24px;
            text-align: center;
            background-color: #FFF8E7;
        }

        .hero-title {
            color: #006C3B;
            font-size: 36px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .hero-subtitle {
            color: #4A4A4A;
            font-size: 16px;
            max-width: 800px;
            margin: 0 auto;
        }

        .categories-section {
            padding: 40px 24px;
        }

        .categories-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .categories-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .section-title {
            color: #006C3B;
            font-size: 24px;
            font-weight: 600;
        }

        .add-category-btn {
            background-color: #006C3B;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .add-category-btn:hover {
            background-color: #005530;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            color: #4A4A4A;
            font-weight: 600;
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .edit-btn, .delete-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
            text-decoration: none;
            color: white;
        }

        .edit-btn {
            background-color: #006C3B;
        }

        .edit-btn:hover {
            background-color: #005530;
        }

        .delete-btn {
            background-color: #dc3545;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 28px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_navbar.php'; ?>

    <section class="hero-section">
        <h1 class="hero-title">Manage Categories</h1>
        <p class="hero-subtitle">Add, edit, and manage your product categories</p>
    </section>

    <section class="categories-section">
        <div class="categories-container">
            <div class="categories-card">
                <div class="section-header">
                    <h2 class="section-title">All Categories</h2>
                    <a href="add_category.php" class="add-category-btn">Add New Category</a>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_category.php?id=<?php echo $category['id']; ?>" class="edit-btn">Edit</a>
                                        <a href="delete_category.php?id=<?php echo $category['id']; ?>" 
                                           class="delete-btn" 
                                           onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/admin_footer.php'; ?>
</body>
</html>