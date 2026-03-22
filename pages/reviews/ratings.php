<?php
session_start();
require_once 'config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = null;
$success = null;

// If order_id and menu_item_id are provided, show the rating form
if (isset($_GET['order_id']) && isset($_GET['menu_item_id'])) {
$order_id = intval($_GET['order_id']);
$menu_item_id = intval($_GET['menu_item_id']);

// Verify that the order belongs to the user and is completed
$order_query = "SELECT o.*, m.name as item_name, m.image_path, od.quantity, od.price
                FROM orders o
                JOIN order_details od ON o.id = od.order_id
                JOIN menu_items m ON od.menu_item_id = m.id
                WHERE o.id = ? AND o.user_id = ? AND o.status = 'completed'";

$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: orders.php");
    exit();
}

$order = mysqli_fetch_assoc($result);

// Check if the order has already been rated
$rating_query = "SELECT * FROM ratings WHERE order_id = ? AND menu_item_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $rating_query);
mysqli_stmt_bind_param($stmt, "iii", $order_id, $menu_item_id, $user_id);
    mysqli_stmt_execute($stmt);
$rating_result = mysqli_stmt_get_result($stmt);
$existing_rating = mysqli_fetch_assoc($rating_result);

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    
    if ($rating && $rating >= 1 && $rating <= 5) {
        try {
            mysqli_begin_transaction($conn);
            
            if ($existing_rating) {
                // Update existing rating
                $update_query = "UPDATE ratings 
                               SET rating = ?, comment = ?, updated_at = NOW() 
                               WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "isi", $rating, $comment, $existing_rating['id']);
            } else {
                // Insert new rating
                $insert_query = "INSERT INTO ratings (user_id, order_id, menu_item_id, rating, comment, created_at) 
                               VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "iiiis", $user_id, $order_id, $menu_item_id, $rating, $comment);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                // Update order's is_rated status
                $update_order = "UPDATE orders SET is_rated = 1 WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_order);
                mysqli_stmt_bind_param($stmt, "i", $order_id);
                mysqli_stmt_execute($stmt);
                
                mysqli_commit($conn);
                $success = "Thank you for your rating!";
                
                // Redirect back to orders page after successful rating
                header("Location: orders.php?rating_success=1");
                exit();
            } else {
                throw new Exception("Failed to save rating");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("Error saving rating: " . $e->getMessage());
            $error = "An error occurred while saving your rating. Please try again.";
        }
    } else {
        $error = "Please select a valid rating (1-5 stars).";
        }
    }
} else {
    // Show all ratings for the user
    $ratings_query = "SELECT r.*, m.name as item_name, m.image_path, o.created_at as order_date 
                     FROM ratings r 
                     JOIN menu_items m ON r.menu_item_id = m.id 
                     JOIN orders o ON r.order_id = o.id 
                     WHERE r.user_id = ? 
                     ORDER BY r.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $ratings_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $ratings_result = mysqli_stmt_get_result($stmt);
    
    $ratings = [];
    while ($rating = mysqli_fetch_assoc($ratings_result)) {
        $ratings[] = $rating;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ratings - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: #e8f5e9;
            --warning: #ffc107;
            --bs-border-radius: 1rem;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            padding-top: 0 !important; /* Remove default padding */
            margin-top: 60px; /* Add margin for fixed navbar */
        }

        /* Header Styles */
        .page-header {
            background: var(--primary);
            padding: 2rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            transform: skewY(-3deg);
            transform-origin: top left;
        }

        .page-header .container {
            position: relative;
            z-index: 1;
        }

        .page-title {
            color: white;
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .page-title i {
            font-size: 1.8rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem;
            border-radius: 12px;
            box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Main Content Styles */
        .main-content {
            padding: 2rem 0;
            min-height: calc(100vh - 60px - 4rem); /* Subtract navbar height and header padding */
        }

        .ratings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Rating Card Styles */
        .rating-card {
            background: white;
            border-radius: var(--bs-border-radius);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .rating-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.12);
        }

        /* Empty State Styles */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--bs-border-radius);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .empty-state h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .empty-state p {
            color: #6c757d;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem 0;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .page-title i {
                font-size: 1.4rem;
            }

            .main-content {
                padding: 1rem 0;
            }
        }

        /* Keep your existing styles below */
        .item-details {
            padding: 2rem;
            background: linear-gradient(to right, white, #f8f9fa);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .item-image {
            width: 160px;
            height: 160px;
            object-fit: cover;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 5px solid white;
            transition: transform 0.3s ease;
        }

        .item-image:hover {
            transform: scale(1.05);
        }

        .item-info h2 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0.25rem;
            transition: all 0.3s ease;
        }

        .meta-item i {
            margin-right: 0.5rem;
        }

        .meta-item:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .rating-section {
            padding: 2.5rem;
            background: white;
        }

        .star-rating {
            display: flex;
            gap: 1rem;
            font-size: 2.5rem;
            justify-content: center;
            margin: 2rem 0;
            direction: rtl;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: var(--warning);
            transform: scale(1.2) rotate(5deg);
        }

        .form-control {
            border: 2px solid #eee;
            padding: 1rem;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 108, 59, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.2);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 108, 59, 0.3);
        }

        .btn-outline-secondary {
            border: 2px solid #dee2e6;
            color: #666;
        }

        .btn-outline-secondary:hover {
            background: #f8f9fa;
            border-color: #dee2e6;
            color: #444;
            transform: translateY(-2px);
        }

        /* Ratings List Styles */
        .ratings-list {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .rating-item {
            background: white;
            border-radius: var(--bs-border-radius);
            box-shadow: 0 0.25rem 1rem rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .rating-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 2rem rgba(0,0,0,0.12);
        }

        .rating-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background: linear-gradient(to right, white, #f8f9fa);
        }

        .rating-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 4px solid white;
            transition: transform 0.3s ease;
        }

        .rating-img:hover {
            transform: scale(1.05);
        }

        .rating-content {
            padding: 1.5rem;
            background: white;
        }

        .rating-stars {
            color: var(--warning);
            font-size: 1.2rem;
            margin: 0.5rem 0;
        }

        .rating-date {
            color: #6c757d;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .empty-ratings {
            text-align: center;
            padding: 4rem 1rem;
            background: white;
            border-radius: var(--bs-border-radius);
            box-shadow: 0 0.25rem 1rem rgba(0,0,0,0.08);
        }

        .empty-ratings i {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .alert {
            border: none;
            border-radius: var(--bs-border-radius);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .page-header {
                padding: 2rem 0;
            }

            .rating-container {
                margin-top: -1rem;
            }

            .item-image {
                width: 120px;
                height: 120px;
            }

            .star-rating {
                font-size: 2rem;
                gap: 0.5rem;
            }
        }

        /* Add these button styles to your existing CSS */
        .btn-primary.btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            background: linear-gradient(45deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(0, 108, 59, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-primary.btn-lg:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 6px 20px rgba(0, 108, 59, 0.4);
            background: linear-gradient(45deg, var(--primary-dark) 0%, var(--primary) 100%);
        }

        .btn-primary.btn-lg:active {
            transform: translateY(1px);
            box-shadow: 0 2px 10px rgba(0, 108, 59, 0.3);
        }

        .btn-primary.btn-lg i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .btn-primary.btn-lg:hover i {
            transform: translateX(-3px);
        }

        .btn-primary.btn-lg::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(45deg);
            transition: 0.5s;
            opacity: 0;
        }

        .btn-primary.btn-lg:hover::after {
            animation: shine 1.5s ease-out infinite;
        }

        @keyframes shine {
            0% {
                transform: rotate(45deg) translateX(-100%);
                opacity: 0;
            }
            50% {
                opacity: 0.7;
            }
            100% {
                transform: rotate(45deg) translateX(100%);
                opacity: 0;
            }
        }

        /* Update the empty state button container */
        .empty-state .btn-container {
            margin-top: 2rem;
            display: inline-flex;
            position: relative;
        }

        .empty-state .btn-container::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: calc(100% + 20px);
            height: calc(100% + 20px);
            background: radial-gradient(circle, rgba(0, 108, 59, 0.1) 0%, rgba(0, 108, 59, 0) 70%);
            border-radius: 50px;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .empty-state .btn-container:hover::before {
            opacity: 1;
        }

        /* Update the empty state HTML */
        .empty-state .btn-container {
            margin-top: 2rem;
            display: inline-flex;
            position: relative;
        }

        .empty-state .btn-container::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: calc(100% + 20px);
            height: calc(100% + 20px);
            background: radial-gradient(circle, rgba(0, 108, 59, 0.1) 0%, rgba(0, 108, 59, 0) 70%);
            border-radius: 50px;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .empty-state .btn-container:hover::before {
            opacity: 1;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="page-header">
    <div class="container">
            <h1 class="page-title">
                <i class="fas fa-star"></i>
                My Ratings
            </h1>
        </div>
    </div>

    <div class="main-content">
        <div class="ratings-container">
            <?php if (isset($_GET['order_id']) && isset($_GET['menu_item_id'])): ?>
                    <div class="rating-card">
            <div class="item-details">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                        <img src="<?php echo htmlspecialchars($order['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($order['item_name']); ?>" 
                             class="item-image">
                                </div>
                    <div class="col-md-8">
                                <h2 class="h3 mb-3"><?php echo htmlspecialchars($order['item_name']); ?></h2>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="meta-item">
                                        <i class="fas fa-box"></i>
                                Quantity: <?php echo $order['quantity']; ?>
                                    </span>
                                    <span class="meta-item">
                                        <i class="fas fa-tag"></i>
                                Price: ₱<?php echo number_format($order['price'], 2); ?>
                                    </span>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" class="rating-section">
                        <h3 class="text-center mb-4">How would you rate this item?</h3>
                
                <div class="star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>"
                            <?php echo ($existing_rating && $existing_rating['rating'] == $i) ? 'checked' : ''; ?>>
                                <label for="star<?php echo $i; ?>">
                            <i class="fas fa-star"></i>
                        </label>
                    <?php endfor; ?>
                </div>

                <div class="mb-4">
                    <label for="comment" class="form-label">
                        <i class="fas fa-comment-alt me-2"></i>
                        Share your thoughts (Optional)
                    </label>
                    <textarea class="form-control" id="comment" name="comment" rows="4" 
                              placeholder="Tell us what you think about this item..."><?php echo $existing_rating ? htmlspecialchars($existing_rating['comment']) : ''; ?></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-3">
                    <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Back to Orders
                    </a>
                    <button type="submit" name="submit_rating" class="btn btn-primary">
                                <i class="fas fa-star"></i>
                        <?php echo $existing_rating ? 'Update Rating' : 'Submit Rating'; ?>
                    </button>
                </div>
            </form>
                </div>
            <?php else: ?>
                <?php if (empty($ratings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-star"></i>
                        <h3>No Ratings Yet</h3>
                        <p>You haven't rated any orders yet. Share your experience and help others make better choices!</p>
                        <div class="btn-container">
                            <a href="orders.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-clipboard-list"></i>
                                <span>View My Orders</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($ratings as $rating): ?>
                        <div class="rating-card">
                            <div class="rating-header">
                                <div class="row align-items-center g-4">
                                    <div class="col-auto">
                                        <img src="<?php echo htmlspecialchars($rating['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($rating['item_name']); ?>" 
                                             class="rating-img">
                                    </div>
                                    <div class="col">
                                        <h5 class="mb-2"><?php echo htmlspecialchars($rating['item_name']); ?></h5>
                                        <div class="rating-stars mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $rating['rating'] ? 'text-warning' : 'text-muted opacity-25'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="rating-date text-muted">
                                            <i class="far fa-calendar-alt me-2"></i>
                                            <?php echo date('M d, Y', strtotime($rating['order_date'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($rating['comment'])): ?>
                                <div class="rating-content">
                                    <p class="mb-0"><?php echo htmlspecialchars($rating['comment']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <!-- Bootstrap 5 JS - Removed duplicate, navbar.php already includes Bootstrap 5.3.2 -->
</body>
</html> 