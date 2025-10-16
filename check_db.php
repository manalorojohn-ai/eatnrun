<?php
require_once 'includes/connection.php';

echo "<h2>Database Structure Check</h2>";

// Check users table
$query = "DESCRIBE users";
$result = mysqli_query($conn, $query);
echo "<h3>Users Table Structure:</h3>";
echo "<pre>";
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
echo "</pre>";

// Check email_verification table
$query = "DESCRIBE email_verification";
$result = mysqli_query($conn, $query);
echo "<h3>Email Verification Table Structure:</h3>";
echo "<pre>";
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
echo "</pre>";

// Check current OTP records
$query = "SELECT * FROM email_verification";
$result = mysqli_query($conn, $query);
echo "<h3>Current OTP Records:</h3>";
echo "<pre>";
while ($row = mysqli_fetch_assoc($result)) {
    // Mask the OTP code for security
    $row['otp_code'] = str_repeat('*', strlen($row['otp_code']));
    print_r($row);
}
echo "</pre>";

// Check user verification status
$query = "SELECT id, email, is_verified FROM users";
$result = mysqli_query($conn, $query);
echo "<h3>User Verification Status:</h3>";
echo "<pre>";
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
echo "</pre>";
?> 