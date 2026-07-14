<?php
/**
 * Quick script to update user password in database
 * Usage: php update_user_password.php email@example.com NewPassword123
 */

require_once dirname(__DIR__, 2) . '/config/database/db.php';

if ($argc < 3) {
    echo "Usage: php update_user_password.php <email> <new_password>\n";
    echo "Example: php update_user_password.php thismaria@yahoo.com Password123!\n";
    exit(1);
}

$email = $argv[1];
$new_password = $argv[2];

// Hash the password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update in database
$query = "UPDATE users SET password = '" . mysqli_real_escape_string($conn, $hashed_password) . "' WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'";

$result = mysqli_query($conn, $query);

if ($result) {
    $affected = mysqli_affected_rows($conn);
    if ($affected > 0) {
        echo "✓ Password updated successfully for: $email\n";
        echo "  New password: $new_password\n";
    } else {
        echo "✗ User not found: $email\n";
    }
} else {
    echo "✗ Error updating password: " . mysqli_error($conn) . "\n";
}

?>
