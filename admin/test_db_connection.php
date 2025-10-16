<?php
require_once '../config/db.php';

echo "<h2>Database Connection Test</h2>";

// Test primary database connection
echo "<h3>Primary Database (food_ordering)</h3>";
if ($conn) {
    echo "✅ Connected to primary database<br>";
    echo "Database: " . DB_NAME . "<br>";
    
    // Test ratings table
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'ratings'");
    if (mysqli_num_rows($result) > 0) {
        echo "✅ ratings table exists<br>";
        
        // Count ratings
        $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM ratings");
        $count = mysqli_fetch_assoc($count_result)['count'];
        echo "📊 Found $count ratings<br>";
    } else {
        echo "❌ ratings table does not exist<br>";
    }
    
    // Test orders table
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
    if (mysqli_num_rows($result) > 0) {
        echo "✅ orders table exists<br>";
        
        // Count orders
        $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders");
        $count = mysqli_fetch_assoc($count_result)['count'];
        echo "📊 Found $count orders<br>";
    } else {
        echo "❌ orders table does not exist<br>";
    }
    
} else {
    echo "❌ Failed to connect to primary database<br>";
}

echo "<hr>";

// Test hotel_management database connection
echo "<h3>Hotel Management Database</h3>";

// First connect without specifying database
$temp_conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
if ($temp_conn) {
    echo "✅ Connected to MySQL server<br>";
    
    // Check available databases
    $result = mysqli_query($temp_conn, "SHOW DATABASES");
    echo "📋 Available databases:<br>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- " . $row['Database'] . "<br>";
    }
    
    // Check if hotel_management exists
    $result = mysqli_query($temp_conn, "SHOW DATABASES LIKE 'hotel_management'");
    if (mysqli_num_rows($result) > 0) {
        echo "✅ hotel_management database exists<br>";
        
        // Try to connect to it
        $hotel_conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, 'hotel_management');
        if ($hotel_conn) {
            echo "✅ Connected to hotel_management database<br>";
            
            // Check eatnrun_rating table
            $result = mysqli_query($hotel_conn, "SHOW TABLES LIKE 'eatnrun_rating'");
            if (mysqli_num_rows($result) > 0) {
                echo "✅ eatnrun_rating table exists<br>";
                
                // Count ratings
                $count_result = mysqli_query($hotel_conn, "SELECT COUNT(*) as count FROM eatnrun_rating");
                $count = mysqli_fetch_assoc($count_result)['count'];
                echo "📊 Found $count hotel ratings<br>";
            } else {
                echo "❌ eatnrun_rating table does not exist<br>";
            }
            
            mysqli_close($hotel_conn);
        } else {
            echo "❌ Failed to connect to hotel_management database<br>";
            echo "Error: " . mysqli_connect_error() . "<br>";
        }
    } else {
        echo "❌ hotel_management database does not exist<br>";
    }
    
    mysqli_close($temp_conn);
} else {
    echo "❌ Failed to connect to MySQL server<br>";
    echo "Error: " . mysqli_connect_error() . "<br>";
}

echo "<hr>";
echo "<h3>Connection Details</h3>";
echo "Host: " . DB_HOST . "<br>";
echo "User: " . DB_USER . "<br>";
echo "Password: " . (DB_PASS ? "Set" : "Not set") . "<br>";
echo "Primary DB: " . DB_NAME . "<br>";
?>
