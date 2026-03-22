<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>API Connection Test</h1>";

// Test 1: Test the PHP API endpoint
echo "<h2>1. Testing PHP API Endpoint</h2>";
$api_url = 'http://localhost/online-food-ordering/admin/api/ratings_api.php';
$response = file_get_contents($api_url);

if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        echo "✅ PHP API is working<br>";
        
        // Debug: Show the actual response structure
        echo "<h3>API Response Structure:</h3>";
        echo "<pre>" . print_r($data, true) . "</pre>";
        
        // Check if ratings key exists
        if (isset($data['ratings']) && is_array($data['ratings'])) {
            echo "Total ratings: " . count($data['ratings']) . "<br>";
            
            // Show hotel ratings
            $hotel_ratings = array_filter($data['ratings'], function($r) {
                return isset($r['source']) && $r['source'] === 'hotel';
            });
            echo "Hotel ratings: " . count($hotel_ratings) . "<br>";
            
            if (count($hotel_ratings) > 0) {
                echo "<h3>Hotel Ratings Found:</h3>";
                foreach ($hotel_ratings as $rating) {
                    echo "- ID: " . $rating['id'] . ", Rating: " . $rating['rating'] . ", Comment: " . $rating['comment'] . "<br>";
                }
            }
        } else {
            echo "❌ No 'ratings' key found in API response<br>";
            echo "Available keys: " . implode(', ', array_keys($data)) . "<br>";
        }
    } else {
        echo "❌ PHP API returned invalid JSON<br>";
        echo "Response: " . htmlspecialchars($response) . "<br>";
    }
} else {
    echo "❌ Failed to connect to PHP API<br>";
}

// Test 2: Test Python API endpoint (if running)
echo "<h2>2. Testing Python API Endpoint</h2>";
echo "<p><strong>Note:</strong> Make sure to run: <code>flask run --host=0.0.0.0 --port=5000</code></p>";
$python_api_url = 'http://localhost:5000/api/hotel-ratings';
$context = stream_context_create([
    'http' => [
        'timeout' => 5
    ]
]);

$python_response = @file_get_contents($python_api_url, false, $context);

if ($python_response !== false) {
    $python_data = json_decode($python_response, true);
    if ($python_data && isset($python_data['success'])) {
        echo "✅ Python API is working<br>";
        echo "Total ratings: " . count($python_data['data']) . "<br>";
        
        if (count($python_data['data']) > 0) {
            echo "<h3>Python API Ratings Found:</h3>";
            foreach ($python_data['data'] as $rating) {
                echo "- ID: " . $rating['id'] . ", Rating: " . $rating['rating'] . ", Comment: " . $rating['comment'] . "<br>";
            }
        }
    } else {
        echo "❌ Python API returned invalid JSON<br>";
        echo "Response: " . htmlspecialchars($python_response) . "<br>";
    }
} else {
    echo "❌ Python API is not running or not accessible<br>";
    echo "To start the Python API, run: python hotel_ratings_api.py<br>";
}

// Test 3: Direct database connection
echo "<h2>3. Testing Direct Database Connection</h2>";
try {
    $hotel_db = mysqli_connect('localhost', 'root', '', 'hotel_management');
    if ($hotel_db) {
        echo "✅ Direct database connection successful<br>";
        
        $result = mysqli_query($hotel_db, "SELECT COUNT(*) as count FROM eatnrun_rating");
        $row = mysqli_fetch_assoc($result);
        echo "Total ratings in database: " . $row['count'] . "<br>";
        
        $sample_result = mysqli_query($hotel_db, "SELECT * FROM eatnrun_rating LIMIT 3");
        if ($sample_result && mysqli_num_rows($sample_result) > 0) {
            echo "<h3>Sample Database Records:</h3>";
            while ($row = mysqli_fetch_assoc($sample_result)) {
                echo "- ID: " . $row['id'] . ", Rating: " . $row['rating'] . ", Comment: " . $row['comment'] . "<br>";
            }
        }
        mysqli_close($hotel_db);
    } else {
        echo "❌ Direct database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection exception: " . $e->getMessage() . "<br>";
}

echo "<p><a href='ratings.php'>← Back to Ratings Page</a></p>";
?>
