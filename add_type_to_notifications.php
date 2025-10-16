<?php
require_once 'config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Adding 'type' column to notifications table</h1>";

try {
    // Check if notifications table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
    if (mysqli_num_rows($table_check) == 0) {
        throw new Exception("Notifications table does not exist!");
    }

    // Check if 'type' column already exists
    $column_check = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'type'");
    if (mysqli_num_rows($column_check) > 0) {
        echo "<p>The 'type' column already exists in the notifications table.</p>";
    } else {
        // Add the 'type' column to the notifications table
        $alter_query = "ALTER TABLE notifications ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'system' AFTER message";
        if (mysqli_query($conn, $alter_query)) {
            echo "<p style='color: green;'>Successfully added 'type' column to notifications table!</p>";
        } else {
            throw new Exception("Failed to add 'type' column: " . mysqli_error($conn));
        }
    }

    // Update existing notifications to have a type
    $update_query = "UPDATE notifications SET type = 'system' WHERE type IS NULL OR type = ''";
    if (mysqli_query($conn, $update_query)) {
        $affected_rows = mysqli_affected_rows($conn);
        echo "<p>Updated $affected_rows existing notifications to have 'system' type.</p>";
    } else {
        echo "<p style='color: orange;'>Warning: Failed to update existing notifications: " . mysqli_error($conn) . "</p>";
    }

    echo "<p>Notifications table has been successfully updated.</p>";
    echo "<p>You can now return to the checkout page and complete your order.</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Add a link to go back to checkout
echo "<p><a href='checkout.php' style='display: inline-block; padding: 10px 20px; background-color: #006C3B; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px;'>Return to Checkout</a></p>";
?> 