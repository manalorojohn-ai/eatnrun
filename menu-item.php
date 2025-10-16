<?php
session_start();
require_once 'config/db.php';

// Check if item ID is provided
if (!isset($_GET['id'])) {
    header("Location: menu.php");
    exit();
}

$item_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get menu item details with category and ratings
$query = "SELECT m.*, c.name as category_name,
          COALESCE(m.image_path, 'assets/images/default-food.jpg') as image_path,
          COALESCE(AVG(r.rating), 0) as average_rating,
          COUNT(DISTINCT r.id) as total_ratings
          FROM menu_items m 
          LEFT JOIN categories c ON m.category_id = c.id
          LEFT JOIN ratings r ON m.id = r.menu_item_id
          WHERE m.id = ? AND m.status = 'available'
          GROUP BY m.id";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: menu.php");
    exit();
}

$item = mysqli_fetch_assoc($result);

// Get user's rating if logged in
$user_rating = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $rating_query = "SELECT rating, comment, created_at 
                    FROM ratings 
                    WHERE user_id = ? AND menu_item_id = ?";
    $stmt = mysqli_prepare($conn, $rating_query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
    mysqli_stmt_execute($stmt);
    $rating_result = mysqli_stmt_get_result($stmt);
    if ($rating_result && mysqli_num_rows($rating_result) > 0) {
        $user_rating = mysqli_fetch_assoc($rating_result);
    }
}

// Get related items from the same category
$related_query = "SELECT m.*, 
                 COALESCE(m.image_path, 'assets/images/default-food.jpg') as image_path,
                 COALESCE(AVG(r.rating), 0) as average_rating,
                 COUNT(DISTINCT r.id) as total_ratings
                 FROM menu_items m
                 LEFT JOIN ratings r ON m.id = r.menu_item_id
                 WHERE m.category_id = ? 
                 AND m.id != ? 
                 AND m.status = 'available'
                 GROUP BY m.id
                 ORDER BY RAND()
                 LIMIT 4";

$stmt = mysqli_prepare($conn, $related_query);
mysqli_stmt_bind_param($stmt, "ii", $item['category_id'], $item_id);
mysqli_stmt_execute($stmt);
$related_result = mysqli_stmt_get_result($stmt);
$related_items = [];
while ($related = mysqli_fetch_assoc($related_result)) {
    $related_items[] = $related;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['name']); ?> - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-light: #00A65A;
            --primary-dark: #005530;
            --text-dark: #333;
            --text-light: #666;
            --white: #fff;
            --radius-sm: 4px;
            --radius-md: 8px;
            --radius-lg: 16px;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f8f9fa;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .item-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-bottom: 40px;
        }

        .item-image {
            position: relative;
            height: 100%;
            min-height: 400px;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition-slow);
        }

        .item-details:hover .item-image img {
            transform: scale(1.05);
        }

        .item-content {
            padding: 40px;
        }

        .item-category {
            display: inline-block;
            padding: 6px 12px;
            background: rgba(0,108,59,0.1);
            color: var(--primary-color);
            border-radius: var(--radius-sm);
            font-size: 0.9em;
            font-weight: 500;
            margin-bottom: 16px;
        }

        .item-title {
            font-size: 2.5em;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 16px;
        }

        .item-description {
            color: var(--text-light);
            font-size: 1.1em;
            margin-bottom: 24px;
            line-height: 1.8;
        }

        .item-price {
            font-size: 2em;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 32px;
        }

        .item-price::before {
            content: '₱';
            font-size: 0.8em;
            margin-right: 4px;
        }

        .add-to-cart {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8f9fa;
            padding: 8px 16px;
            border-radius: var(--radius-md);
        }

        .quantity-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 1.2em;
            cursor: pointer;
            padding: 4px 8px;
            transition: var(--transition);
        }

        .quantity-btn:hover {
            color: var(--primary-dark);
            transform: scale(1.1);
        }

        .quantity {
            font-size: 1.1em;
            font-weight: 600;
            color: var(--text-dark);
            min-width: 40px;
            text-align: center;
        }

        .add-to-cart-btn {
            flex: 1;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            padding: 12px 24px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .add-to-cart-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .rating-section {
            margin-top: 32px;
            padding-top: 32px;
            border-top: 1px solid #eee;
        }

        .rating-title {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 16px;
        }

        .rating-stars {
            display: flex;
            gap: 4px;
            margin-bottom: 16px;
        }

        .star {
            color: #ffc107;
            font-size: 1.2em;
        }

        .rating-count {
            color: var(--text-light);
            font-size: 0.9em;
        }

        .related-items {
            margin-top: 60px;
        }

        .related-title {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 32px;
            text-align: center;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
        }

        .related-item {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .related-item:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
        }

        .related-image {
            height: 200px;
            overflow: hidden;
        }

        .related-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition-slow);
        }

        .related-item:hover .related-image img {
            transform: scale(1.1);
        }

        .related-content {
            padding: 20px;
        }

        .related-name {
            font-size: 1.1em;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .related-price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.2em;
        }

        @media (max-width: 768px) {
            .item-details {
                grid-template-columns: 1fr;
            }

            .item-image {
                min-height: 300px;
            }

            .related-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="item-details">
            <div class="item-image">
                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                     onerror="this.src='assets/images/default-food.jpg';">
            </div>
            <div class="item-content">
                <span class="item-category"><?php echo htmlspecialchars($item['category_name']); ?></span>
                <h1 class="item-title"><?php echo htmlspecialchars($item['name']); ?></h1>
                <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                <div class="item-price"><?php echo number_format($item['price'], 2); ?></div>
                
                <form action="add_to_cart.php" method="POST" class="add-to-cart">
                    <div class="quantity-control">
                        <button type="button" class="quantity-btn minus"><i class="fas fa-minus"></i></button>
                        <input type="number" name="quantity" value="1" min="1" class="quantity" readonly>
                        <button type="button" class="quantity-btn plus"><i class="fas fa-plus"></i></button>
                    </div>
                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                    <button type="submit" class="add-to-cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        Add to Cart
                    </button>
                </form>

                <div class="rating-section">
                    <h2 class="rating-title">Customer Rating</h2>
                    <div class="rating-stars">
                        <?php
                        $rating = round($item['average_rating']);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="fas fa-star star"></i>';
                            } else {
                                echo '<i class="far fa-star star"></i>';
                            }
                        }
                        ?>
                        <span class="rating-count">(<?php echo $item['total_ratings']; ?> ratings)</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($related_items)): ?>
        <div class="related-items">
            <h2 class="related-title">You May Also Like</h2>
            <div class="related-grid">
                <?php foreach ($related_items as $related): ?>
                    <a href="menu-item.php?id=<?php echo $related['id']; ?>" class="related-item">
                        <div class="related-image">
                            <img src="<?php echo htmlspecialchars($related['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($related['name']); ?>"
                                 onerror="this.src='assets/images/default-food.jpg';"
                                 loading="lazy">
                        </div>
                        <div class="related-content">
                            <h3 class="related-name"><?php echo htmlspecialchars($related['name']); ?></h3>
                            <div class="related-price">₱<?php echo number_format($related['price'], 2); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInput = document.querySelector('.quantity');
            const minusBtn = document.querySelector('.minus');
            const plusBtn = document.querySelector('.plus');

            minusBtn.addEventListener('click', () => {
                let value = parseInt(quantityInput.value);
                if (value > 1) {
                    quantityInput.value = value - 1;
                }
            });

            plusBtn.addEventListener('click', () => {
                let value = parseInt(quantityInput.value);
                quantityInput.value = value + 1;
            });
        });
    </script>
</body>
</html> 