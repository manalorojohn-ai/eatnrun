<?php
echo "<h2>PHP Extension Information:</h2>";
echo "<pre>";

// Check if PDO is installed
echo "PDO Installed: " . (class_exists('PDO') ? 'Yes' : 'No') . "\n";

// List loaded extensions
echo "\nLoaded Extensions:\n";
print_r(get_loaded_extensions());

// Check PDO drivers
echo "\nPDO Drivers Available:\n";
if (class_exists('PDO')) {
    print_r(PDO::getAvailableDrivers());
} else {
    echo "PDO is not installed\n";
}

// Try MySQL connection
echo "\nTesting MySQL Connection:\n";
try {
    $conn = new PDO("mysql:host=localhost", "root", "");
    echo "MySQL connection successful!";
} catch (PDOException $e) {
    echo "Connection Error: " . $e->getMessage();
}

echo "</pre>";
?> 