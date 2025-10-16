<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Hotel Database Connection Test</h1>";

// Test local hotel database connection
echo "<h2>Testing Local Hotel Database Connection</h2>";

try {
    $hotel_db = mysqli_connect('localhost', 'root', '', 'hotel_management');
    
    if ($hotel_db) {
        echo "✅ Successfully connected to hotel_management database<br>";
        
        // Check if eatnrun_rating table exists
        $table_check = mysqli_query($hotel_db, "SHOW TABLES LIKE 'eatnrun_rating'");
        if (mysqli_num_rows($table_check) > 0) {
            echo "✅ eatnrun_rating table exists<br>";
            
            // Count records
            $count_result = mysqli_query($hotel_db, "SELECT COUNT(*) as count FROM eatnrun_rating");
            $count_row = mysqli_fetch_assoc($count_result);
            echo "✅ Found " . $count_row['count'] . " ratings in the table<br>";
            
            // Show sample data
            echo "<h3>Sample Data:</h3>";
            $sample_result = mysqli_query($hotel_db, "SELECT * FROM eatnrun_rating LIMIT 3");
            if ($sample_result && mysqli_num_rows($sample_result) > 0) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Rating</th><th>Comment</th><th>Created At</th><th>Order ID</th></tr>";
                while ($row = mysqli_fetch_assoc($sample_result)) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['rating'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['comment']) . "</td>";
                    echo "<td>" . $row['created_at'] . "</td>";
                    echo "<td>" . $row['order_id'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "❌ eatnrun_rating table does not exist<br>";
        }
        mysqli_close($hotel_db);
    } else {
        echo "❌ Failed to connect to hotel_management database: " . mysqli_connect_error() . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

echo "<p><a href='ratings.php'>← Back to Ratings Page</a></p>";
?>
