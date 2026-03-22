<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Ratings Connection Diagnostic</h1>";

// Test 1: Check if config file exists
echo "<h2>1. Configuration File Check</h2>";
$config_path = __DIR__ . '/api/database_config.php';
if (file_exists($config_path)) {
    echo "✅ Config file exists at: $config_path<br>";
    
    // Test 2: Try to include the config
    echo "<h2>2. Configuration Loading</h2>";
    try {
        require_once $config_path;
        echo "✅ Configuration loaded successfully<br>";
        echo "- REMOTE_DB_HOST: " . REMOTE_DB_HOST . "<br>";
        echo "- REMOTE_DB_USER: " . REMOTE_DB_USER . "<br>";
        echo "- REMOTE_DB_NAME: " . REMOTE_DB_NAME . "<br>";
        echo "- REMOTE_DB_PASS: " . (empty(REMOTE_DB_PASS) ? '(empty)' : '(set)') . "<br>";
    } catch (Exception $e) {
        echo "❌ Error loading config: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Config file not found at: $config_path<br>";
    exit;
}

// Test 3: Network connectivity
echo "<h2>3. Network Connectivity Test</h2>";
$host = REMOTE_DB_HOST;
$port = 3306;
$connection = @fsockopen($host, $port, $errno, $errstr, 5);
if (is_resource($connection)) {
    echo "✅ Network connection to $host:$port is successful<br>";
    fclose($connection);
} else {
    echo "❌ Network connection to $host:$port failed: $errstr ($errno)<br>";
}

// Test 4: MySQL connection without database
echo "<h2>4. MySQL Server Connection Test</h2>";
try {
    $temp_conn = mysqli_connect(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS);
    if ($temp_conn) {
        echo "✅ Successfully connected to MySQL server<br>";
        
        // Test 5: Check if database exists
        echo "<h2>5. Database Existence Check</h2>";
        $result = mysqli_query($temp_conn, "SHOW DATABASES LIKE '" . REMOTE_DB_NAME . "'");
        if (mysqli_num_rows($result) > 0) {
            echo "✅ Database '" . REMOTE_DB_NAME . "' exists<br>";
            
            // Test 6: Connect to specific database
            echo "<h2>6. Database Connection Test</h2>";
            mysqli_close($temp_conn);
            $hotel_conn = mysqli_connect(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS, REMOTE_DB_NAME);
            if ($hotel_conn) {
                echo "✅ Successfully connected to hotel_management database<br>";
                
                // Test 7: Check if table exists
                echo "<h2>7. Table Existence Check</h2>";
                $table_result = mysqli_query($hotel_conn, "SHOW TABLES LIKE 'eatnrun_rating'");
                if (mysqli_num_rows($table_result) > 0) {
                    echo "✅ eatnrun_rating table exists<br>";
                    
                    // Test 8: Count records
                    $count_result = mysqli_query($hotel_conn, "SELECT COUNT(*) as count FROM eatnrun_rating");
                    $count_row = mysqli_fetch_assoc($count_result);
                    echo "✅ Found " . $count_row['count'] . " ratings in the table<br>";
                    
                    // Test 9: Sample data
                    echo "<h2>8. Sample Data Check</h2>";
                    $sample_result = mysqli_query($hotel_conn, "SELECT * FROM eatnrun_rating LIMIT 3");
                    if ($sample_result && mysqli_num_rows($sample_result) > 0) {
                        echo "✅ Sample data found:<br>";
                        while ($row = mysqli_fetch_assoc($sample_result)) {
                            echo "- ID: " . $row['id'] . ", Rating: " . $row['rating'] . ", Comment: " . substr($row['comment'], 0, 50) . "...<br>";
                        }
                    } else {
                        echo "⚠️ No sample data found<br>";
                    }
                } else {
                    echo "❌ eatnrun_rating table does not exist<br>";
                    
                    // Show available tables
                    echo "<h3>Available tables in hotel_management:</h3>";
                    $tables_result = mysqli_query($hotel_conn, "SHOW TABLES");
                    if ($tables_result) {
                        while ($table = mysqli_fetch_array($tables_result)) {
                            echo "- " . $table[0] . "<br>";
                        }
                    }
                }
                mysqli_close($hotel_conn);
            } else {
                echo "❌ Failed to connect to hotel_management database: " . mysqli_connect_error() . "<br>";
            }
        } else {
            echo "❌ Database '" . REMOTE_DB_NAME . "' does not exist<br>";
            
            // Show available databases
            echo "<h3>Available databases:</h3>";
            $databases_result = mysqli_query($temp_conn, "SHOW DATABASES");
            if ($databases_result) {
                while ($db = mysqli_fetch_array($databases_result)) {
                    echo "- " . $db[0] . "<br>";
                }
            }
        }
        mysqli_close($temp_conn);
    } else {
        echo "❌ Failed to connect to MySQL server: " . mysqli_connect_error() . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Exception during connection test: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>9. Recommendations</h2>";
echo "<ul>";
echo "<li>If network connectivity fails: Check if MySQL is running on localhost</li>";
echo "<li>If MySQL connection fails: Check if MySQL is running on localhost</li>";
echo "<li>If database doesn't exist: Create the hotel_management database on localhost</li>";
echo "<li>If table doesn't exist: Create the eatnrun_rating table on localhost</li>";
echo "<li>If access denied: Check MySQL user permissions and password</li>";
echo "</ul>";

echo "<p><a href='ratings.php'>← Back to Ratings Page</a></p>";
?>
