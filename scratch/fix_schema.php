<?php
require_once 'config/database/db.php';

// Add address column if it doesn't exist
$alter_query = "ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT";
if (mysqli_query($conn, $alter_query)) {
    echo "Address column added (or already exists).\n";
} else {
    echo "Error adding address column: " . mysqli_error($conn) . "\n";
}

// Also add profile_photo column if it doesn't exist
$alter_photo = "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255)";
if (mysqli_query($conn, $alter_photo)) {
    echo "Profile photo column added.\n";
}
