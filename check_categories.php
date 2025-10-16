<?php
require_once 'includes/connection.php';

// Get all category names as they are stored in the database
$query = "SELECT id, name FROM categories ORDER BY name";
$result = mysqli_query($conn, $query);

echo "<h2>Categories in Database</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>URL Encoded</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . urlencode($row['name']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Get product counts by category
$query = "SELECT c.name, COUNT(p.id) as product_count 
          FROM categories c
          LEFT JOIN products p ON c.id = p.category_id
          GROUP BY c.id
          ORDER BY c.name";
$result = mysqli_query($conn, $query);

echo "<h2>Product Counts by Category</h2>";
echo "<table border='1'>";
echo "<tr><th>Category</th><th>Product Count</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['product_count'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Test the query used in the menu.php file
echo "<h2>Test Queries</h2>";

$categories = ['Rice Meals', 'Burgers', 'Desserts', 'Beverages'];

foreach ($categories as $category) {
    $query = "SELECT COUNT(*) as count FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE c.name = '" . mysqli_real_escape_string($conn, $category) . "'";
    
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    
    echo "<p>Category: <strong>" . $category . "</strong> - Products: " . $row['count'] . "</p>";
    echo "<p>Query: <code>" . $query . "</code></p>";
}
?> 