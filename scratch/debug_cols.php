<?php
require_once 'config/database/db.php';
$res = mysqli_query($conn, "SELECT * FROM users LIMIT 1");
if (!$res) {
    echo "ERROR: " . mysqli_error($conn) . "\n";
} else {
    $row = mysqli_fetch_assoc($res);
    print_r(array_keys($row));
}
