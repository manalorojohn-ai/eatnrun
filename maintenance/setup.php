<?php
/**
 * Setup script for Eat&Run Food Ordering System
 * This script will check and create necessary database tables
 */

// Include database connection
require_once 'config/db.php';

// Function to check if a table exists
function tableExists($tableName, $conn) {
    $check_query = "SHOW TABLES LIKE '$tableName'";
    $result = mysqli_query($conn, $check_query);
    return mysqli_num_rows($result) > 0;
}

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eat&Run - Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 40px;
            background-color: #f8f9fa;
        }
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #006C3B;
            margin-bottom: 20px;
        }
        .table-check {
            margin-bottom: 30px;
        }
        .status {
            font-weight: bold;
        }
        .status.exists {
            color: #28a745;
        }
        .status.missing {
            color: #dc3545;
        }
        .setup-actions {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>Eat&Run Database Setup</h1>
        <p>This script will check your database and create any missing tables needed for the application to work properly.</p>
        
        <div class="table-check">
            <h3>Checking database tables...</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // List of required tables
                    $requiredTables = [
                        'users', 'products', 'categories', 'orders', 'order_items',
                        'cart', 'notifications', 'ratings', 'reviews'
                    ];
                    
                    $missingTables = [];
                    
                    foreach ($requiredTables as $table) {
                        $exists = tableExists($table, $conn);
                        echo "<tr>";
                        echo "<td>$table</td>";
                        if ($exists) {
                            echo "<td><span class='status exists'>Exists</span></td>";
                        } else {
                            echo "<td><span class='status missing'>Missing</span></td>";
                            $missingTables[] = $table;
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($missingTables)): ?>
        <div class="missing-tables-actions">
            <h3>Create Missing Tables</h3>
            <p>The following tables need to be created: <?php echo implode(', ', $missingTables); ?></p>
            
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="hidden" name="create_tables" value="1">
                <button type="submit" class="btn btn-primary">Create Missing Tables</button>
            </form>
        </div>
        <?php else: ?>
        <div class="alert alert-success">
            All required tables exist in the database. Your system is ready to use!
        </div>
        <?php endif; ?>
        
        <div class="setup-actions mt-4">
            <a href="index.php" class="btn btn-success">Go to Homepage</a>
        </div>
    </div>
</body>
</html>

<?php
// Handle table creation if form is submitted
if (isset($_POST['create_tables'])) {
    // Create notifications table if it doesn't exist
    if (!tableExists('notifications', $conn)) {
        $create_notifications = "CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) DEFAULT NULL,
            link VARCHAR(255) DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        if (mysqli_query($conn, $create_notifications)) {
            echo "<script>alert('Notifications table created successfully.');</script>";
        } else {
            echo "<script>alert('Error creating notifications table: " . mysqli_error($conn) . "');</script>";
        }
    }
    
    // Create cart table if it doesn't exist
    if (!tableExists('cart', $conn)) {
        $create_cart = "CREATE TABLE cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )";
        
        if (mysqli_query($conn, $create_cart)) {
            echo "<script>alert('Cart table created successfully.');</script>";
        } else {
            echo "<script>alert('Error creating cart table: " . mysqli_error($conn) . "');</script>";
        }
    }
    
    // Reload the page to show updated status
    echo "<script>window.location.href = window.location.pathname;</script>";
}

// Close connection
mysqli_close($conn);
?> 