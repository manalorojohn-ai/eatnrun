<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Remote Database Connection Test</h1>";

// Configuration - Remote Hotel Database
$remote_host = '192.168.0.101';  // IP of laptop with hotel database
$remote_user = 'root';
$remote_pass = '';  // No MySQL password
$remote_db = 'hotel_management';

echo "<h2>Testing Connection to Remote Database</h2>";
echo "<p><strong>Target:</strong> $remote_host</p>";
echo "<p><strong>Database:</strong> $remote_db</p>";

// Test 1: Network connectivity
echo "<h3>1. Network Connectivity Test</h3>";
$connection = @fsockopen($remote_host, 3306, $errno, $errstr, 5);
if (is_resource($connection)) {
    echo "✅ Network connection to $remote_host:3306 is successful<br>";
    fclose($connection);
} else {
    echo "❌ Network connection to $remote_host:3306 failed: $errstr ($errno)<br>";
    echo "<p><strong>Troubleshooting:</strong></p>";
    echo "<ul>";
    echo "<li>Check if the other laptop is on the same network</li>";
    echo "<li>Verify the IP address is correct</li>";
    echo "<li>Check if MySQL is running on the remote laptop</li>";
    echo "<li>Check if port 3306 is open on the remote laptop</li>";
    echo "</ul>";
}

// Test 2: MySQL connection
echo "<h3>2. MySQL Connection Test</h3>";
try {
    $hotel_conn = mysqli_connect($remote_host, $remote_user, $remote_pass, $remote_db);
    if ($hotel_conn) {
        echo "✅ Successfully connected to remote hotel_management database<br>";
        
        // Check for eatnrun_rating table
        $table_check = mysqli_query($hotel_conn, "SHOW TABLES LIKE 'eatnrun_rating'");
        if (mysqli_num_rows($table_check) > 0) {
            echo "✅ eatnrun_rating table found<br>";
            
            // Count ratings
            $count_result = mysqli_query($hotel_conn, "SELECT COUNT(*) as count FROM eatnrun_rating");
            $count_row = mysqli_fetch_assoc($count_result);
            echo "✅ Found " . $count_row['count'] . " ratings in the table<br>";
            
            // Show sample data
            $sample_result = mysqli_query($hotel_conn, "SELECT * FROM eatnrun_rating LIMIT 3");
            if ($sample_result && mysqli_num_rows($sample_result) > 0) {
                echo "<h4>Sample Ratings:</h4>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Rating</th><th>Comment</th><th>Created At</th></tr>";
                while ($row = mysqli_fetch_assoc($sample_result)) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['rating'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['comment']) . "</td>";
                    echo "<td>" . $row['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "❌ eatnrun_rating table not found<br>";
        }
        mysqli_close($hotel_conn);
    } else {
        echo "❌ Failed to connect to remote database: " . mysqli_connect_error() . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Connection exception: " . $e->getMessage() . "<br>";
}

echo "<h3>3. Configuration Instructions</h3>";
echo "<p>If the connection fails, you need to configure the remote laptop:</p>";
echo "<ol>";
echo "<li><strong>On the remote laptop:</strong> Open MySQL configuration</li>";
echo "<li><strong>Bind MySQL to all interfaces:</strong> Change bind-address to 0.0.0.0</li>";
echo "<li><strong>Grant permissions:</strong> GRANT ALL PRIVILEGES ON hotel_management.* TO 'root'@'%' IDENTIFIED BY 'password';</li>";
echo "<li><strong>Flush privileges:</strong> FLUSH PRIVILEGES;</li>";
echo "<li><strong>Open firewall:</strong> Allow port 3306</li>";
echo "</ol>";

echo "<p><a href='test_api_connection.php'>← Back to API Test</a></p>";
?>
