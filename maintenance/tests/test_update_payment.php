<?php
session_start();
require_once 'config/db.php';

// Log information for debugging
error_log("Test update payment - Session: " . json_encode($_SESSION));

// HTML for testing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Payment Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .test-panel {
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
        }
        button {
            padding: 10px 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        input {
            padding: 8px;
            width: 100px;
            margin-right: 10px;
        }
        #status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .error {
            background: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body>
    <h1>Test Payment Update Endpoint</h1>
    
    <div class="test-panel">
        <h2>Session Information</h2>
        <pre><?php echo json_encode($_SESSION, JSON_PRETTY_PRINT); ?></pre>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <p style="color: red;">Warning: You are not logged in. The payment update will fail.</p>
            <a href="login.php" style="display: inline-block; margin-top: 10px; padding: 8px 15px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px;">Login first</a>
        <?php else: ?>
            <p style="color: green;">You are logged in as User ID: <?php echo $_SESSION['user_id']; ?></p>
        <?php endif; ?>
    </div>
    
    <div class="test-panel">
        <h2>Test Payment Update</h2>
        <form id="testForm">
            <label for="orderId">Order ID:</label>
            <input type="number" id="orderId" required>
            <button type="submit">Test Update</button>
        </form>
        
        <div id="status"></div>
        
        <div id="responseContainer" style="margin-top: 20px;">
            <h3>Response:</h3>
            <pre id="response">No response yet</pre>
        </div>
    </div>
    
    <script>
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const orderId = document.getElementById('orderId').value;
            const status = document.getElementById('status');
            const response = document.getElementById('response');
            
            status.style.display = 'block';
            status.textContent = 'Sending request...';
            status.className = '';
            
            // Make the API call
            fetch('update_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(async res => {
                const contentType = res.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await res.json();
                    return { status: res.status, ok: res.ok, data };
                } else {
                    const text = await res.text();
                    return { status: res.status, ok: res.ok, text };
                }
            })
            .then(result => {
                if (result.data) {
                    response.textContent = JSON.stringify(result.data, null, 2);
                    if (result.ok) {
                        status.textContent = 'Success: Payment status updated!';
                        status.className = 'success';
                    } else {
                        status.textContent = 'Error: ' + (result.data.message || 'Unknown error');
                        status.className = 'error';
                    }
                } else {
                    response.textContent = result.text || 'No valid JSON response';
                    status.textContent = 'Error: Received non-JSON response';
                    status.className = 'error';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                response.textContent = err.toString();
                status.textContent = 'Error: ' + err.message;
                status.className = 'error';
            });
        });
    </script>
</body>
</html> 