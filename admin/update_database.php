<?php
require_once '../config/db.php';

try {
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/database/update_order_logs.sql');
    
    // Split into individual queries
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    // Execute each query
    foreach ($queries as $query) {
        if (!empty($query)) {
            if (!mysqli_query($conn, $query)) {
                throw new Exception("Error executing query: " . mysqli_error($conn));
            }
        }
    }
    
    echo "Database updated successfully!";
    
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
} 