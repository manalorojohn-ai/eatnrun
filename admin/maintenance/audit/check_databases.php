<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Discovery Tool</h1>";

// Connect to MySQL server without specifying a database
echo "<h2>Connecting to MySQL server...</h2>";
$conn = mysqli_connect('localhost', 'root', '');

if ($conn) {
    echo "✅ Successfully connected to MySQL server<br>";
    
    // Show all databases
    echo "<h2>Available Databases:</h2>";
    $result = mysqli_query($conn, "SHOW DATABASES");
    
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Database Name</th><th>Actions</th></tr>";
        
        while ($row = mysqli_fetch_array($result)) {
            $db_name = $row[0];
            echo "<tr>";
            echo "<td>" . htmlspecialchars($db_name) . "</td>";
            echo "<td>";
            
            // Check if this database has hotel-related tables
            $db_conn = mysqli_connect('localhost', 'root', '', $db_name);
            if ($db_conn) {
                $tables_result = mysqli_query($db_conn, "SHOW TABLES");
                $has_hotel_tables = false;
                $hotel_tables = [];
                
                while ($table_row = mysqli_fetch_array($tables_result)) {
                    $table_name = $table_row[0];
                    if (strpos(strtolower($table_name), 'hotel') !== false || 
                        strpos(strtolower($table_name), 'rating') !== false ||
                        strpos(strtolower($table_name), 'eat') !== false) {
                        $has_hotel_tables = true;
                        $hotel_tables[] = $table_name;
                    }
                }
                
                if ($has_hotel_tables) {
                    echo "<strong style='color: green;'>🎯 HOTEL DATABASE FOUND!</strong><br>";
                    echo "Hotel-related tables: " . implode(', ', $hotel_tables) . "<br>";
                    
                    // Check for eatnrun_rating table specifically
                    $rating_check = mysqli_query($db_conn, "SHOW TABLES LIKE 'eatnrun_rating'");
                    if (mysqli_num_rows($rating_check) > 0) {
                        echo "<strong style='color: blue;'>✅ eatnrun_rating table found!</strong><br>";
                        
                        // Count ratings
                        $count_result = mysqli_query($db_conn, "SELECT COUNT(*) as count FROM eatnrun_rating");
                        $count_row = mysqli_fetch_assoc($count_result);
                        echo "Ratings count: " . $count_row['count'] . "<br>";
                    }
                } else {
                    echo "<span style='color: gray;'>No hotel tables found</span>";
                }
                
                mysqli_close($db_conn);
            } else {
                echo "<span style='color: red;'>Cannot access database</span>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "❌ Error getting databases: " . mysqli_error($conn);
    }
    
    mysqli_close($conn);
} else {
    echo "❌ Failed to connect to MySQL server: " . mysqli_connect_error();
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Look for the database with hotel-related tables (marked with 🎯)</li>";
echo "<li>Note the exact database name</li>";
echo "<li>Update the configuration files with the correct database name</li>";
echo "</ol>";

echo "<p><a href='test_api_connection.php'>← Back to API Test</a></p>";
?>
