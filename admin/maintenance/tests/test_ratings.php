<?php
// Test file to verify ratings display

// Define hardcoded hotel ratings
$hotel_ratings = [
    [
        'id' => '1',
        'customer' => 'Hotel Guest',
        'menu_item' => 'Hotel Order',
        'order_id' => '101',
        'rating' => '5',
        'comment' => 'Excellent service!',
        'created_at' => '2025-07-17 23:10:50',
        'source' => 'hotel'
    ],
    [
        'id' => '2',
        'customer' => 'Hotel Guest',
        'menu_item' => 'Hotel Order',
        'order_id' => '102',
        'rating' => '4',
        'comment' => 'Good food, would order again',
        'created_at' => '2025-07-17 23:10:50',
        'source' => 'hotel'
    ],
    [
        'id' => '3',
        'customer' => 'Hotel Guest',
        'menu_item' => 'Hotel Order',
        'order_id' => '103',
        'rating' => '5',
        'comment' => 'Very fast delivery',
        'created_at' => '2025-07-17 23:10:50',
        'source' => 'hotel'
    ]
];

// Define a sample local rating
$local_ratings = [
    [
        'id' => '61',
        'customer' => 'Rojohn',
        'menu_item' => 'Anton\'s Special Pares With Rice',
        'order_id' => '17',
        'rating' => '5',
        'comment' => 'masarap pede ko na ikiss si anton',
        'created_at' => '2025-07-17 10:55:00',
        'source' => 'local'
    ]
];

// Combine ratings
$all_ratings = array_merge($local_ratings, $hotel_ratings);

// Sort by date
usort($all_ratings, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Output for debugging
echo "<h1>Test Ratings</h1>";
echo "<h2>All Ratings (" . count($all_ratings) . ")</h2>";
echo "<pre>";
print_r($all_ratings);
echo "</pre>";

echo "<h2>Table Display</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Customer</th><th>Menu Item</th><th>Order ID</th><th>Rating</th><th>Comment</th><th>Date</th><th>Source</th></tr>";

foreach ($all_ratings as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['customer']) . "</td>";
    echo "<td>" . htmlspecialchars($row['menu_item']) . "</td>";
    echo "<td>" . htmlspecialchars($row['order_id']) . "</td>";
    echo "<td>" . str_repeat('★', intval($row['rating'])) . str_repeat('☆', 5 - intval($row['rating'])) . "</td>";
    echo "<td>" . htmlspecialchars($row['comment']) . "</td>";
    echo "<td>" . date('M d, Y h:i A', strtotime($row['created_at'])) . "</td>";
    echo "<td>" . ucfirst($row['source']) . "</td>";
    echo "</tr>";
}

echo "</table>";
?> 