<?php
/**
 * Test Remote Database Connection
 * 
 * This script tests the connection to the remote hotel_management database
 */

require_once 'database_config.php';

echo "<h2>🔍 Testing Remote Database Connection</h2>";
echo "<hr>";

// Test 1: Basic connection test
echo "<h3>1. Testing Basic Connection</h3>";
try {
    $conn = mysqli_connect(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS);
    if ($conn) {
        echo "✅ Successfully connected to remote MySQL server at " . REMOTE_DB_HOST . "<br>";
        mysqli_close($conn);
    } else {
        echo "❌ Failed to connect to remote MySQL server<br>";
        echo "Error: " . mysqli_connect_error() . "<br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Connection error: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Database existence test
echo "<h3>2. Testing Database Existence</h3>";
try {
    $conn = mysqli_connect(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS);
    $result = mysqli_query($conn, "SHOW DATABASES LIKE '" . REMOTE_DB_NAME . "'");
    if (mysqli_num_rows($result) > 0) {
        echo "✅ Database '" . REMOTE_DB_NAME . "' exists on remote server<br>";
    } else {
        echo "❌ Database '" . REMOTE_DB_NAME . "' does not exist on remote server<br>";
        echo "Available databases:<br>";
        $all_dbs = mysqli_query($conn, "SHOW DATABASES");
        while ($row = mysqli_fetch_array($all_dbs)) {
            echo "- " . $row[0] . "<br>";
        }
        mysqli_close($conn);
        exit;
    }
    mysqli_close($conn);
} catch (Exception $e) {
    echo "❌ Database check error: " . $e->getMessage() . "<br>";
    exit;
}

// Test 3: Connect to specific database
echo "<h3>3. Testing Database Connection</h3>";
try {
    $conn = mysqli_connect(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS, REMOTE_DB_NAME);
    if ($conn) {
        echo "✅ Successfully connected to '" . REMOTE_DB_NAME . "' database<br>";
        
        // Test 4: Check tables
        echo "<h3>4. Checking Tables</h3>";
        $tables = ['eatnrun_rating', 'user'];
        foreach ($tables as $table) {
            $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
            if (mysqli_num_rows($result) > 0) {
                echo "✅ Table '$table' exists<br>";
                
                // Count rows
                $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
                $count_row = mysqli_fetch_assoc($count_result);
                echo "   - Records: " . $count_row['count'] . "<br>";
            } else {
                echo "❌ Table '$table' does not exist<br>";
            }
        }
        
        // Test 5: Sample data
        echo "<h3>5. Sample Data</h3>";
        $result = mysqli_query($conn, "SELECT * FROM eatnrun_rating LIMIT 3");
        if ($result && mysqli_num_rows($result) > 0) {
            echo "✅ Sample ratings data:<br>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "- ID: " . $row['id'] . ", Rating: " . $row['rating'] . ", Comment: " . $row['comment'] . "<br>";
            }
        } else {
            echo "⚠️ No ratings data found<br>";
        }
        
        mysqli_close($conn);
    } else {
        echo "❌ Failed to connect to '" . REMOTE_DB_NAME . "' database<br>";
        echo "Error: " . mysqli_connect_error() . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>🔧 Troubleshooting Tips:</h3>";
echo "<ul>";
echo "<li>Make sure you've set the correct password in database_config.php</li>";
echo "<li>Check if the remote server allows connections from your IP</li>";
echo "<li>Verify the database and tables exist on the remote server</li>";
echo "<li>Check firewall settings on the remote server</li>";
echo "</ul>";

echo "<h3>📝 Current Configuration:</h3>";
echo "Host: " . REMOTE_DB_HOST . "<br>";
echo "User: " . REMOTE_DB_USER . "<br>";
echo "Database: " . REMOTE_DB_NAME . "<br>";
echo "Password: " . (empty(REMOTE_DB_PASS) ? "❌ NOT SET" : "✅ SET") . "<br>";
?>
