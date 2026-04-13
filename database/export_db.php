<?php
// Script to export the food_ordering database to a SQL file for migration to Neon
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'food_ordering';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . PHP_EOL);
}

echo "Connected OK" . PHP_EOL;

$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

echo "Tables found: " . implode(", ", $tables) . PHP_EOL;

$output = "-- EatNRun Database Export\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
$output .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {
    // Structure
    $result = $conn->query("SHOW CREATE TABLE `$table`");
    $row = $result->fetch_row();
    $output .= "\n-- Table: $table\n";
    $output .= "DROP TABLE IF EXISTS `$table`;\n";
    $output .= $row[1] . ";\n\n";

    // Data
    $result = $conn->query("SELECT * FROM `$table`");
    while ($data_row = $result->fetch_assoc()) {
        $cols = array_keys($data_row);
        $vals = array_map(function($v) use ($conn) {
            return $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'";
        }, array_values($data_row));
        $output .= "INSERT INTO `$table` (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $vals) . ");\n";
    }
    $output .= "\n";
}

$output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(__DIR__ . '/food_ordering_export.sql', $output);
echo "Export complete! File: " . __DIR__ . '/food_ordering_export.sql' . PHP_EOL;
echo "Size: " . filesize(__DIR__ . '/food_ordering_export.sql') . " bytes" . PHP_EOL;
