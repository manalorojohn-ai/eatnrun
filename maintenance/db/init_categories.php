<?php
require_once 'config/db.php';

// Function to safely execute SQL queries
function executeQuery($conn, $query, $error_message) {
    if (!mysqli_query($conn, $query)) {
        die("$error_message: " . mysqli_error($conn));
    }
    return true;
}

// Function to check if a category exists
function categoryExists($conn, $name) {
    $name = mysqli_real_escape_string($conn, $name);
    $query = "SELECT id FROM categories WHERE name = '$name'";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

// Function to add a category
function addCategory($conn, $name, $description) {
    if (!categoryExists($conn, $name)) {
        $name = mysqli_real_escape_string($conn, $name);
        $description = mysqli_real_escape_string($conn, $description);
        $query = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
        return executeQuery($conn, $query, "Error adding category '$name'");
    }
    return false;
}

// Function to update a category
function updateCategory($conn, $id, $name, $description) {
    $id = (int)$id;
    $name = mysqli_real_escape_string($conn, $name);
    $description = mysqli_real_escape_string($conn, $description);
    $query = "UPDATE categories SET name = '$name', description = '$description' WHERE id = $id";
    return executeQuery($conn, $query, "Error updating category ID $id");
}

// Default categories
$categories = [
    ['name' => 'Rice Meals', 'description' => 'Traditional Filipino rice meals'],
    ['name' => 'Burgers', 'description' => 'Delicious burger selections'],
    ['name' => 'Desserts', 'description' => 'Sweet treats and desserts'],
    ['name' => 'Beverages', 'description' => 'Refreshing drinks']
];

// Add or update categories
foreach ($categories as $category) {
    if (!categoryExists($conn, $category['name'])) {
        if (addCategory($conn, $category['name'], $category['description'])) {
            echo "Added category: {$category['name']}\n";
        }
    } else {
        echo "Category already exists: {$category['name']}\n";
    }
}

echo "Category initialization completed.\n";
?> 