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

// Get categories for dropdown
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->query($query);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/products/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = 'assets/images/products/' . $file_name;
        }
    }

    // Insert product into database
    $query = "INSERT INTO products (name, category_id, price, description, image) 
              VALUES (:name, :category_id, :price, :description, :image)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':name' => $name,
        ':category_id' => $category_id,
        ':price' => $price,
        ':description' => $description,
        ':image' => $image_path
    ]);

    header("Location: products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Eat&Run</title>
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

        .form-section {
            padding: 40px 24px;
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            color: #4A4A4A;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: #006C3B;
            outline: none;
        }

        .form-textarea {
            height: 120px;
            resize: vertical;
        }

        .button-group {
            display: flex;
            gap: 16px;
            margin-top: 32px;
        }

        .submit-btn,
        .cancel-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .submit-btn {
            background-color: #006C3B;
            color: white;
        }

        .submit-btn:hover {
            background-color: #005530;
        }

        .cancel-btn {
            background-color: #dc3545;
            color: white;
            text-decoration: none;
        }

        .cancel-btn:hover {
            background-color: #c82333;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 28px;
            }

            .form-card {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_navbar.php'; ?>

    <section class="hero-section">
        
        <p class="hero-subtitle">Create a new menu item for your restaurant</p>
    </section>

    <section class="form-section">
        <div class="form-container">
            <div class="form-card">
                <form action="add_product.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label" for="name">Product Name</label>
                        <input type="text" id="name" name="name" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="category">Category</label>
                        <select id="category" name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="price">Price (₱)</label>
                        <input type="number" id="price" name="price" class="form-input" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-textarea" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="image">Product Image</label>
                        <input type="file" id="image" name="image" class="form-input" accept="image/*" required>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="submit-btn">Add Product</button>
                        <a href="products.php" class="cancel-btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <?php include 'includes/admin_footer.php'; ?>
</body>
</html>
