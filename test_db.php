<?php
require_once "config/database.php";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "Database connection successful!";
        
        // Create a test admin user
        $email = "admin@test.com";
        $password = password_hash("admin123", PASSWORD_DEFAULT);
        $role = "admin";
        
        $query = "INSERT IGNORE INTO users (email, password, role, first_name, last_name) 
                  VALUES (:email, :password, :role, 'Admin', 'User')";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":role", $role);
        $stmt->execute();
        
        echo "<br>Test admin user created (if not exists):<br>";
        echo "Email: admin@test.com<br>";
        echo "Password: admin123<br>";
        echo "Role: admin";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 