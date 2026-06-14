<?php
/**
 * Test User Creation Script
 * Creates sample test credentials for quick login testing
 */

require_once 'config/db.php';

// Test credentials
$testUsers = [
    [
        'full_name' => 'John Doe',
        'username' => 'johndoe',
        'email' => 'john@gmail.com',
        'phone' => '09123456789',
        'password' => 'password123' // In real app, this would be hashed
    ],
    [
        'full_name' => 'Maria Santos',
        'username' => 'maria',
        'email' => 'maria@yahoo.com',
        'phone' => '09987654321',
        'password' => 'maria123'
    ],
    [
        'full_name' => 'Juan Cruz',
        'username' => 'juancruz',
        'email' => 'juan@outlook.com',
        'phone' => '09234567890',
        'password' => 'juan2024'
    ]
];

echo "Creating test users...\n";
echo "======================\n\n";

foreach ($testUsers as $user) {
    // Hash password
    $hashed_password = password_hash($user['password'], PASSWORD_BCRYPT);
    
    // Check if user already exists
    $check_query = "SELECT id FROM users WHERE email = ? OR username = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "ss", $user['email'], $user['username']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo "⚠️  User '{$user['username']}' already exists. Skipping...\n";
        mysqli_stmt_close($stmt);
        continue;
    }
    mysqli_stmt_close($stmt);
    
    // Insert user
    $insert_query = "INSERT INTO users (full_name, username, email, phone, password, email_verified, created_at) 
                     VALUES (?, ?, ?, ?, ?, 1, NOW())";
    $stmt = mysqli_prepare($conn, $insert_query);
    
    if (!$stmt) {
        echo "❌ Error preparing statement: " . mysqli_error($conn) . "\n";
        continue;
    }
    
    $email_verified = 1;
    mysqli_stmt_bind_param($stmt, "sssss", 
        $user['full_name'],
        $user['username'],
        $user['email'],
        $user['phone'],
        $hashed_password
    );
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ User created successfully!\n";
        echo "   Name: {$user['full_name']}\n";
        echo "   Username: {$user['username']}\n";
        echo "   Email: {$user['email']}\n";
        echo "   Password: {$user['password']}\n";
        echo "\n";
    } else {
        echo "❌ Error creating user: " . mysqli_stmt_error($stmt) . "\n";
    }
    
    mysqli_stmt_close($stmt);
}

echo "======================\n";
echo "Test user creation complete!\n";
echo "\nYou can now login with any of these credentials:\n";
echo "1. Username: johndoe | Password: password123\n";
echo "2. Username: maria | Password: maria123\n";
echo "3. Username: juancruz | Password: juan2024\n";
?>
