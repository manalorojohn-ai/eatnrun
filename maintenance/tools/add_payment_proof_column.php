<?php
// Script to add payment_proof column to orders table if it doesn't exist
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';

try {
    echo "Checking orders table structure...\n";
    
    // Check if payment_proof column exists
    $query = "SHOW COLUMNS FROM orders LIKE 'payment_proof'";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo "Query error: " . mysqli_error($conn) . "\n";
    } else {
        if (mysqli_num_rows($result) == 0) {
            echo "Adding payment_proof column to orders table...\n";
            
            // Add the column if it doesn't exist
            $alter_query = "ALTER TABLE orders ADD COLUMN payment_proof VARCHAR(255) NULL AFTER payment_method";
            
            if (mysqli_query($conn, $alter_query)) {
                echo "Successfully added payment_proof column to orders table.\n";
            } else {
                echo "Failed to add column: " . mysqli_error($conn) . "\n";
            }
        } else {
            echo "payment_proof column already exists in orders table.\n";
        }
    }
    
    // Check if notes column exists
    $query = "SHOW COLUMNS FROM orders LIKE 'notes'";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo "Query error: " . mysqli_error($conn) . "\n";
    } else {
        if (mysqli_num_rows($result) == 0) {
            echo "Adding notes column to orders table...\n";
            
            // Add the column if it doesn't exist
            $alter_query = "ALTER TABLE orders ADD COLUMN notes TEXT NULL AFTER payment_proof";
            
            if (mysqli_query($conn, $alter_query)) {
                echo "Successfully added notes column to orders table.\n";
            } else {
                echo "Failed to add column: " . mysqli_error($conn) . "\n";
            }
        } else {
            echo "notes column already exists in orders table.\n";
        }
    }
    
    echo "Database structure check completed.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/payment_proofs/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "Created uploads directory for payment proofs.\n";
    } else {
        echo "Failed to create uploads directory.\n";
    }
} else {
    echo "Uploads directory already exists.\n";
}

echo "All checks completed.\n";
?> 