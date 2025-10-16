<?php
require_once 'config/db.php';

// Function to check table structure
function checkTableStructure($conn) {
    echo "<h2>Checking Password Resets Table Structure</h2>";
    
    // Check if table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'password_resets'");
    if (mysqli_num_rows($result) == 0) {
        echo "Error: password_resets table does not exist!<br>";
        return false;
    }
    
    echo "✓ Table 'password_resets' exists<br>";
    
    // Check columns
    $columns = [
        'id' => false,
        'user_id' => false,
        'token' => false,
        'expires' => false,
        'created_at' => false
    ];
    
    $result = mysqli_query($conn, "SHOW COLUMNS FROM password_resets");
    while ($row = mysqli_fetch_assoc($result)) {
        if (array_key_exists($row['Field'], $columns)) {
            $columns[$row['Field']] = true;
        }
    }
    
    $allColumnsExist = true;
    foreach ($columns as $column => $exists) {
        if ($exists) {
            echo "✓ Column '$column' exists<br>";
        } else {
            echo "✗ Column '$column' is missing!<br>";
            $allColumnsExist = false;
        }
    }
    
    if (!$allColumnsExist) {
        return false;
    }
    
    return true;
}

// Function to test token insertion and retrieval
function testTokenFunctionality($conn) {
    echo "<h2>Testing Token Functionality</h2>";
    
    // First, get a valid user ID from the database
    $result = mysqli_query($conn, "SELECT id FROM users LIMIT 1");
    if (mysqli_num_rows($result) == 0) {
        echo "Error: No users found in the database!<br>";
        return false;
    }
    
    $user = mysqli_fetch_assoc($result);
    $userId = $user['id'];
    
    echo "Using user ID: $userId for testing<br>";
    
    // Generate test token
    $token = bin2hex(random_bytes(16));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Delete any existing tokens for this user
    mysqli_query($conn, "DELETE FROM password_resets WHERE user_id = $userId");
    
    // Insert test token
    $insertQuery = "INSERT INTO password_resets (user_id, token, expires) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insertQuery);
    mysqli_stmt_bind_param($stmt, "iss", $userId, $token, $expires);
    
    if (!mysqli_stmt_execute($stmt)) {
        echo "Error inserting token: " . mysqli_error($conn) . "<br>";
        return false;
    }
    
    echo "✓ Test token inserted successfully<br>";
    
    // Verify token retrieval
    $selectQuery = "SELECT * FROM password_resets WHERE token = ?";
    $stmt = mysqli_prepare($conn, $selectQuery);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        echo "Error: Could not retrieve the inserted token!<br>";
        return false;
    }
    
    $retrievedToken = mysqli_fetch_assoc($result);
    echo "✓ Test token retrieved successfully<br>";
    
    // Clean up
    mysqli_query($conn, "DELETE FROM password_resets WHERE token = '$token'");
    echo "✓ Test token cleaned up<br>";
    
    return true;
}

// Run the tests
$tableOk = checkTableStructure($conn);
if ($tableOk) {
    echo "<div style='color: green;'>✓ Table structure is correct</div><br>";
    
    $tokenOk = testTokenFunctionality($conn);
    if ($tokenOk) {
        echo "<div style='color: green;'>✓ Token functionality is working properly</div><br>";
        echo "<p>The forgot password system should now be working correctly. You can try it at <a href='forgot-password.php'>forgot-password.php</a></p>";
    } else {
        echo "<div style='color: red;'>✗ Token functionality test failed</div><br>";
    }
} else {
    echo "<div style='color: red;'>✗ Table structure is incorrect</div><br>";
    echo "<p>Please run <a href='check_password_resets_table.php'>check_password_resets_table.php</a> to fix the table structure.</p>";
}
?> 