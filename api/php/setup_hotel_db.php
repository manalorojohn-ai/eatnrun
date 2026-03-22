<?php
require_once 'db_utils.php';

try {
    // Create hotel_management database if it doesn't exist
    $conn = new mysqli('localhost', 'root', '');
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create database if not exists
    if (!$conn->query("CREATE DATABASE IF NOT EXISTS hotel_management")) {
        throw new Exception("Error creating database: " . $conn->error);
    }
    $conn->close();

    // Connect to the hotel database and set up tables
    $hotel_conn = get_hotel_db_connection();
    ensure_hotel_rating_table($hotel_conn);
    $inserted = insert_sample_hotel_ratings($hotel_conn);
    
    // Display setup results
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Hotel Database Setup</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .success { color: green; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <h1>Hotel Database Setup</h1>
        <div class='success'>
            <p>✓ Database 'hotel_management' created successfully</p>
            <p>✓ Table 'eatnrun_rating' created successfully</p>
            <p>✓ " . ($inserted > 0 ? $inserted . " sample ratings inserted" : "Sample data already exists") . "</p>
        </div>
        <p><a href='../admin/ratings.php'>Go to Ratings Page</a></p>
    </body>
    </html>";
    
    $hotel_conn->close();

} catch (Exception $e) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Setup Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <h1>Setup Error</h1>
        <div class='error'>
            <p>Error: " . htmlspecialchars($e->getMessage()) . "</p>
        </div>
    </body>
    </html>";
}
?> 