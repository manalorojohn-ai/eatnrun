<?php 
// Define connection parameters
$host = "localhost";  // Hostname, usually 'localhost'
$username = "root";  // Your MySQL username
$password = "";  // Your MySQL password
$dbname = "online_food_ordering";  // The database you want to connect to

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully to the database.";
}
