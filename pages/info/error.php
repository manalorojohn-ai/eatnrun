<?php
// Prevent direct access to this file
if (!defined('DB_ERROR') && !isset($conn)) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }

        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        h1 {
            color: #2D3748;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        p {
            color: #718096;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #006C3B;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn:hover {
            background: #005530;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔌</div>
        <h1>Connection Error</h1>
        <p>We're having trouble connecting to our servers. Please try again in a few moments.</p>
        <a href="javascript:location.reload()" class="btn">Try Again</a>
    </div>
</body>
</html> 