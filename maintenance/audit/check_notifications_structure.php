<?php
require_once 'config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Checking Notifications Table Structure</h1>";

// Check if notifications table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
if (mysqli_num_rows($table_check) == 0) {
    echo "<p>Notifications table does not exist!</p>";
    exit;
}

// Get the structure of the notifications table
echo "<h2>Notifications Table Structure:</h2>";
$result = mysqli_query($conn, "DESCRIBE notifications");
if (!$result) {
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    exit;
}

echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    foreach ($row as $key => $value) {
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Check for 'type' column specifically
$has_type = false;
mysqli_data_seek($result, 0); // Reset the result pointer to the beginning
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['Field'] == 'type') {
        $has_type = true;
        break;
    }
}

if (!$has_type) {
    echo "<p style='color: red; font-weight: bold;'>ERROR: 'type' column missing from notifications table!</p>";
    echo "<h3>Solution:</h3>";
    echo "<p>Run the following SQL command to add the missing column:</p>";
    echo "<pre>ALTER TABLE notifications ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'system' AFTER message;</pre>";
}

// Test inserting a notification - success case
echo "<h2>Testing Notification Insert (Avoiding 'type' column):</h2>";
$test_query = "INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (1, 'Test notification', 0, NOW())";
if (mysqli_query($conn, $test_query)) {
    echo "<p style='color: green;'>Success: Test notification inserted without specifying 'type'</p>";
    
    // Clean up test data
    mysqli_query($conn, "DELETE FROM notifications WHERE message = 'Test notification'");
} else {
    echo "<p style='color: red;'>Error: " . mysqli_error($conn) . "</p>";
}

// Test inserting a notification with 'type' - error case
echo "<h2>Testing Notification Insert (With 'type' column):</h2>";
$test_query = "INSERT INTO notifications (user_id, message, type, is_read, created_at) VALUES (1, 'Test notification with type', 'order', 0, NOW())";
if (mysqli_query($conn, $test_query)) {
    echo "<p style='color: green;'>Success: Test notification inserted with 'type'</p>";
    
    // Clean up test data
    mysqli_query($conn, "DELETE FROM notifications WHERE message = 'Test notification with type'");
} else {
    echo "<p style='color: red;'>Error: " . mysqli_error($conn) . "</p>";
}

// Show notifications SQL creation script
echo "<h2>Recommended Notifications Table Structure:</h2>";
echo "<pre>
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
</pre>"; 