<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #006C3B;
        }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .error {
            color: #f44336;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Session Test</h1>
        
        <h2>Current Session Data:</h2>
        <pre><?php print_r($_SESSION); ?></pre>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="success">✅ User is logged in with ID: <?php echo $_SESSION['user_id']; ?></p>
        <?php else: ?>
            <p class="error">❌ User is not logged in</p>
        <?php endif; ?>
        
        <h2>Test Links:</h2>
        <ul>
            <li><a href="ratings.php">Go to Ratings Page</a></li>
            <li><a href="menu.php">Go to Menu Page</a></li>
            <li><a href="orders.php">Go to Orders Page</a></li>
        </ul>
        
        <h2>Session Management:</h2>
        <p>Session ID: <?php echo session_id(); ?></p>
        <p>Session Name: <?php echo session_name(); ?></p>
        <p>Session Status: <?php 
            $status = session_status();
            if ($status === PHP_SESSION_DISABLED) {
                echo "Sessions are disabled";
            } elseif ($status === PHP_SESSION_NONE) {
                echo "Sessions are enabled, but no session has started";
            } else {
                echo "Sessions are enabled, and a session has started";
            }
        ?></p>
    </div>
</body>
</html> 