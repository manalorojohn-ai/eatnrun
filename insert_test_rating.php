<?php
include 'config/db.php';

// First check if ratings table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'ratings'");
if (mysqli_num_rows($check_table) == 0) {
    echo "Ratings table doesn't exist. Please run create_ratings_table.php first.";
    exit;
}

// Insert a test rating
$query = "INSERT INTO ratings (user_id, menu_item_id, rating, review) 
          VALUES (1, 1, 5, 'Delicious! Highly recommended.') 
          ON DUPLICATE KEY UPDATE rating = 5, review = 'Delicious! Highly recommended.', updated_at = NOW()";

if (mysqli_query($conn, $query)) {
    echo "Test rating inserted successfully.";
} else {
    echo "Error inserting test rating: " . mysqli_error($conn);
}

// Insert another rating for the same item
$query = "INSERT INTO ratings (user_id, menu_item_id, rating, review) 
          VALUES (2, 1, 4, 'Very good, enjoyed it a lot.') 
          ON DUPLICATE KEY UPDATE rating = 4, review = 'Very good, enjoyed it a lot.', updated_at = NOW()";

if (mysqli_query($conn, $query)) {
    echo "\nSecond test rating inserted successfully.";
} else {
    echo "\nError inserting second test rating: " . mysqli_error($conn);
}

// Close connection
mysqli_close($conn);
?> 