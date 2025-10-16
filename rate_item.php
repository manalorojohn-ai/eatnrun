<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=menu.php");
    exit;
}

$error_message = '';
$success_message = '';
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$item_name = '';

// Get item details
if ($item_id > 0) {
    $item_query = "SELECT name FROM menu_items WHERE id = ?";
    $item_stmt = mysqli_prepare($conn, $item_query);
    mysqli_stmt_bind_param($item_stmt, "i", $item_id);
    mysqli_stmt_execute($item_stmt);
    $item_result = mysqli_stmt_get_result($item_stmt);
    
    if ($item_data = mysqli_fetch_assoc($item_result)) {
        $item_name = $item_data['name'];
    } else {
        $error_message = "Item not found";
    }
}

// Get current rating if it exists
$current_rating = 0;
$current_review = '';

if ($item_id > 0) {
    $rating_query = "SELECT rating, comment FROM ratings WHERE user_id = ? AND menu_item_id = ?";
    $rating_stmt = mysqli_prepare($conn, $rating_query);
    mysqli_stmt_bind_param($rating_stmt, "ii", $_SESSION['user_id'], $item_id);
    mysqli_stmt_execute($rating_stmt);
    $rating_result = mysqli_stmt_get_result($rating_stmt);
    
    if ($rating_data = mysqli_fetch_assoc($rating_result)) {
        $current_rating = $rating_data['rating'];
        $current_review = $rating_data['comment'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review = isset($_POST['review']) ? mysqli_real_escape_string($conn, trim($_POST['review'])) : '';
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    
    if ($rating < 1 || $rating > 5) {
        $error_message = "Please select a valid rating (1-5)";
    } elseif ($item_id <= 0) {
        $error_message = "Invalid item selected";
    } else {
        $user_id = $_SESSION['user_id'];
        
        try {
            // First verify that the menu item exists
            $verify_item = "SELECT id FROM menu_items WHERE id = ?";
            $verify_stmt = mysqli_prepare($conn, $verify_item);
            mysqli_stmt_bind_param($verify_stmt, "i", $item_id);
            mysqli_stmt_execute($verify_stmt);
            $verify_result = mysqli_stmt_get_result($verify_stmt);
            
            if (mysqli_num_rows($verify_result) === 0) {
                throw new Exception("Menu item not found");
            }
            
            // Check if user already rated this item
            $check_query = "SELECT id FROM ratings WHERE user_id = ? AND menu_item_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $item_id);
            mysqli_stmt_execute($check_stmt);
            $result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($result) > 0) {
                // Update existing rating
                $rating_id = mysqli_fetch_assoc($result)['id'];
                $update_query = "UPDATE ratings SET rating = ?, comment = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "isi", $rating, $review, $rating_id);
                $success = mysqli_stmt_execute($update_stmt);
            } else {
                // Insert new rating
                $insert_query = "INSERT INTO ratings (user_id, menu_item_id, rating, comment) VALUES (?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, "iiis", $user_id, $item_id, $rating, $review);
                $success = mysqli_stmt_execute($insert_stmt);
            }
            
            if ($success) {
                // Update the menu_items table with new average rating
                $update_avg_query = "UPDATE menu_items SET 
                    average_rating = (SELECT AVG(rating) FROM ratings WHERE menu_item_id = ?),
                    rating_count = (SELECT COUNT(*) FROM ratings WHERE menu_item_id = ?)
                    WHERE id = ?";
                $update_avg_stmt = mysqli_prepare($conn, $update_avg_query);
                mysqli_stmt_bind_param($update_avg_stmt, "iii", $item_id, $item_id, $item_id);
                mysqli_stmt_execute($update_avg_stmt);

                $success_message = "Rating submitted successfully!";
                header("Location: menu.php?rated=1#item-" . $item_id);
                exit;
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            $error_message = "Failed to submit rating: " . $e->getMessage();
        }
    }
}

// Get average rating and count
$avg_rating = 0;
$rating_count = 0;

if ($item_id > 0) {
    $avg_query = "SELECT AVG(rating) as average, COUNT(*) as count FROM ratings WHERE menu_item_id = ?";
    $avg_stmt = mysqli_prepare($conn, $avg_query);
    mysqli_stmt_bind_param($avg_stmt, "i", $item_id);
    mysqli_stmt_execute($avg_stmt);
    $avg_result = mysqli_stmt_get_result($avg_stmt);
    
    if ($avg_data = mysqli_fetch_assoc($avg_result)) {
        $avg_rating = round($avg_data['average'], 1);
        $rating_count = $avg_data['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Item - Eat&Run</title>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--bg-color);
            min-height: 100vh;
            line-height: 1.6;
            color: var(--text-color);
            padding-bottom: 2rem;
            margin: 0;
            overflow-y: auto;
            position: relative;
        }

        .container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }

        /* Add styles for navbar spacing */
        .navbar-spacer {
            height: 80px; /* Adjust this value based on your navbar height */
        }

        /* Update container margin for better spacing */
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
                margin: 1rem;
                margin-top: 100px; /* Add top margin for mobile */
            }
        }

        .page-title {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title h1 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .page-title p {
            color: var(--text-light);
            font-size: 1rem;
        }

        .rating-form {
            margin-top: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .rating-stars {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .star-input {
            display: none;
        }

        .star-label {
            font-size: 2rem;
            cursor: pointer;
            color: #ddd;
            transition: color 0.3s ease;
        }

        .star-input:checked ~ label.star-label {
            color: #ffd700;
        }

        .star-label:hover,
        .star-label:hover ~ label.star-label {
            color: #ffd700;
        }

        .review-input {
            width: 100%;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: block;
            width: 100%;
            font-size: 1rem;
        }

        .submit-btn:hover {
            background-color: var(--primary-dark);
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }

        .ratings-summary {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }

        .average-rating {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.2rem;
            color: #666;
        }

        .rating-count {
            font-size: 0.9rem;
            color: #888;
        }

        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="navbar-spacer"></div>

    <div class="container">
        <div class="page-title">
            <h1>Rate Item</h1>
            <?php if (!empty($item_name)): ?>
                <p>You are rating: <strong><?php echo htmlspecialchars($item_name); ?></strong></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($item_id > 0): ?>
            <div class="ratings-summary">
                <div class="average-rating">
                    <span><?php echo $avg_rating; ?></span>
                    <div class="stars">
                        <?php
                        $full_stars = floor($avg_rating);
                        $empty_stars = 5 - $full_stars;
                        echo str_repeat('★', $full_stars) . str_repeat('☆', $empty_stars);
                        ?>
                    </div>
                </div>
                <div class="rating-count"><?php echo $rating_count; ?> ratings</div>
            </div>

            <form class="rating-form" method="post" action="rate_item.php">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                
                <div class="form-group">
                    <label>Your Rating</label>
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" class="star-input" <?php echo ($current_rating == $i) ? 'checked' : ''; ?> required>
                            <label for="star<?php echo $i; ?>" class="star-label" title="<?php echo $i; ?> stars">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="review">Your Review (Optional)</label>
                    <textarea name="review" id="review" class="review-input" placeholder="Write your review here..."><?php echo htmlspecialchars($current_review); ?></textarea>
                </div>
                
                <button type="submit" name="submit_rating" class="submit-btn">Submit Rating</button>
            </form>
        <?php else: ?>
            <p>No valid item selected for rating.</p>
        <?php endif; ?>

        <a href="menu.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Menu</a>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 