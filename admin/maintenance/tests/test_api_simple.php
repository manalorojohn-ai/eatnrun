<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple API Test</h1>";

// Test the API directly
$api_url = 'http://localhost/online-food-ordering/admin/api/ratings_api.php';
echo "<h2>Testing API: $api_url</h2>";

$response = file_get_contents($api_url);

echo "<h3>Raw Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h3>Decoded Response:</h3>";
$data = json_decode($response, true);
if ($data) {
    echo "<pre>" . print_r($data, true) . "</pre>";
} else {
    echo "❌ Failed to decode JSON<br>";
    echo "JSON Error: " . json_last_error_msg() . "<br>";
}

echo "<p><a href='test_api_connection.php'>← Back to Full Test</a></p>";
?>
