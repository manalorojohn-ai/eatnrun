<?php
/**
 * Test script for the PHP Ratings API
 * This script will test the API and show the results
 */

echo "<h1>🔍 Testing PHP Ratings API</h1>";
echo "<hr>";

// Test 1: Check if the API file exists
echo "<h2>1. Checking API File</h2>";
if (file_exists('ratings_api.php')) {
    echo "✅ ratings_api.php file exists<br>";
} else {
    echo "❌ ratings_api.php file not found<br>";
    exit;
}

// Test 2: Check database configuration
echo "<h2>2. Checking Database Configuration</h2>";
if (file_exists('database_config.php')) {
    echo "✅ database_config.php file exists<br>";
    include_once 'database_config.php';
    echo "Remote DB Host: " . (defined('REMOTE_DB_HOST') ? REMOTE_DB_HOST : 'Not defined') . "<br>";
    echo "Remote DB User: " . (defined('REMOTE_DB_USER') ? REMOTE_DB_USER : 'Not defined') . "<br>";
    echo "Remote DB Name: " . (defined('REMOTE_DB_NAME') ? REMOTE_DB_NAME : 'Not defined') . "<br>";
} else {
    echo "❌ database_config.php file not found<br>";
}

// Test 3: Check main database configuration
echo "<h2>3. Checking Main Database Configuration</h2>";
if (file_exists('../../config/db.php')) {
    echo "✅ config/db.php file exists<br>";
    include_once '../../config/db.php';
    echo "Local DB Host: " . (defined('DB_HOST') ? DB_HOST : 'Not defined') . "<br>";
    echo "Local DB Name: " . (defined('DB_NAME') ? DB_NAME : 'Not defined') . "<br>";
} else {
    echo "❌ config/db.php file not found<br>";
}

// Test 4: Test local database connection
echo "<h2>4. Testing Local Database Connection</h2>";
try {
    $test_conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($test_conn) {
        echo "✅ Local database connection successful<br>";
        
        // Check if ratings table exists
        $result = mysqli_query($test_conn, "SHOW TABLES LIKE 'ratings'");
        if (mysqli_num_rows($result) > 0) {
            echo "✅ ratings table exists<br>";
            
            // Count ratings
            $count_result = mysqli_query($test_conn, "SELECT COUNT(*) as count FROM ratings");
            $count = mysqli_fetch_assoc($count_result)['count'];
            echo "📊 Total ratings in database: $count<br>";
        } else {
            echo "❌ ratings table does not exist<br>";
        }
        
        // Check if orders table exists
        $result = mysqli_query($test_conn, "SHOW TABLES LIKE 'orders'");
        if (mysqli_num_rows($result) > 0) {
            echo "✅ orders table exists<br>";
            
            // Count pending orders
            $count_result = mysqli_query($test_conn, "SELECT COUNT(*) as count FROM orders WHERE is_rated = 0 AND status = 'completed'");
            $count = mysqli_fetch_assoc($count_result)['count'];
            echo "📊 Pending orders: $count<br>";
        } else {
            echo "❌ orders table does not exist<br>";
        }
        
        mysqli_close($test_conn);
    } else {
        echo "❌ Local database connection failed: " . mysqli_connect_error() . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Local database error: " . $e->getMessage() . "<br>";
}

// Test 5: Test remote database connection
echo "<h2>5. Testing Remote Database Connection</h2>";
if (defined('REMOTE_DB_HOST') && defined('REMOTE_DB_USER') && defined('REMOTE_DB_PASS') && defined('REMOTE_DB_NAME')) {
    try {
        $test_remote_conn = mysqli_connect(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS);
        if ($test_remote_conn) {
            echo "✅ Remote server connection successful<br>";
            
            // Check if database exists
            $result = mysqli_query($test_remote_conn, "SHOW DATABASES LIKE '" . REMOTE_DB_NAME . "'");
            if (mysqli_num_rows($result) > 0) {
                echo "✅ " . REMOTE_DB_NAME . " database exists on remote server<br>";
                
                // Try to connect to the specific database
                $test_db_conn = mysqli_connect(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS, REMOTE_DB_NAME);
                if ($test_db_conn) {
                    echo "✅ Connected to " . REMOTE_DB_NAME . " database<br>";
                    
                    // Check if eatnrun_rating table exists
                    $result = mysqli_query($test_db_conn, "SHOW TABLES LIKE 'eatnrun_rating'");
                    if (mysqli_num_rows($result) > 0) {
                        echo "✅ eatnrun_rating table exists<br>";
                        
                        // Count ratings
                        $count_result = mysqli_query($test_db_conn, "SELECT COUNT(*) as count FROM eatnrun_rating");
                        $count = mysqli_fetch_assoc($count_result)['count'];
                        echo "📊 Total hotel ratings: $count<br>";
                    } else {
                        echo "❌ eatnrun_rating table does not exist<br>";
                    }
                    
                    mysqli_close($test_db_conn);
                } else {
                    echo "❌ Failed to connect to " . REMOTE_DB_NAME . " database: " . mysqli_connect_error() . "<br>";
                }
            } else {
                echo "❌ " . REMOTE_DB_NAME . " database does not exist on remote server<br>";
            }
            
            mysqli_close($test_remote_conn);
        } else {
            echo "❌ Remote server connection failed: " . mysqli_connect_error() . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ Remote database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Remote database configuration not complete<br>";
}

// Test 6: Test the actual API
echo "<h2>6. Testing API Response</h2>";
echo "<p>Testing API endpoint...</p>";

// Capture the API output
ob_start();
include 'ratings_api.php';
$api_output = ob_get_clean();

// Try to decode JSON
$json_data = json_decode($api_output, true);

if ($json_data) {
    echo "✅ API returned valid JSON<br>";
    echo "Success: " . ($json_data['success'] ? 'Yes' : 'No') . "<br>";
    
    if (isset($json_data['statistics'])) {
        $stats = $json_data['statistics'];
        echo "📊 Statistics:<br>";
        echo "- Total ratings: " . $stats['total_ratings'] . "<br>";
        echo "- Food ratings: " . $stats['food_ratings'] . "<br>";
        echo "- Hotel ratings: " . $stats['hotel_ratings'] . "<br>";
        echo "- Pending orders: " . $stats['pending_orders'] . "<br>";
        echo "- Average rating: " . $stats['average_rating'] . "<br>";
    }
    
    if (isset($json_data['ratings'])) {
        echo "📋 Sample ratings (" . count($json_data['ratings']) . " total):<br>";
        $sample_count = min(3, count($json_data['ratings']));
        for ($i = 0; $i < $sample_count; $i++) {
            $rating = $json_data['ratings'][$i];
            echo "- ID: " . $rating['id'] . ", Rating: " . $rating['rating'] . ", Source: " . $rating['source'] . "<br>";
        }
    }
    
    if (isset($json_data['error'])) {
        echo "❌ API Error: " . $json_data['error'] . "<br>";
    }
} else {
    echo "❌ API did not return valid JSON<br>";
    echo "Raw output:<br>";
    echo "<pre>" . htmlspecialchars($api_output) . "</pre>";
}

echo "<hr>";
echo "<h2>🎯 Test Complete!</h2>";
echo "<p>If you see any ❌ errors above, please fix them before using the API.</p>";
echo "<p>To use the API in your application, make a GET request to: <code>admin/api/ratings_api.php</code></p>";
?>
