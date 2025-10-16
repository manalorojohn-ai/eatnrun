<?php
session_start();
require_once '../config/db.php';
require_once '../includes/notification_helper.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle test notification creation
if (isset($_POST['create_test_notification'])) {
    $message = "Test real-time notification created at " . date('Y-m-d H:i:s');
    $link = "ratings.php";
    
    notify_all_admins(
        'system',
        $message,
        $link
    );
    
    $success_message = "Test notification created! Check the notification dropdown.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Real-time Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .test-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-connected { background-color: #28a745; }
        .status-disconnected { background-color: #dc3545; }
        .status-connecting { background-color: #ffc107; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="mb-4">
            <i class="fas fa-bell me-2"></i>
            Real-time Notifications Test
        </h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plug me-2"></i>
                            Connection Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <span class="status-indicator status-connecting" id="connectionStatus"></span>
                            <span id="connectionText">Connecting...</span>
                        </p>
                        <small class="text-muted">
                            Real-time notifications use Server-Sent Events (SSE) for instant updates.
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cog me-2"></i>
                            Test Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-3">
                            <button type="submit" name="create_test_notification" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                Create Test Notification
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-outline-secondary" onclick="testOrderStatusChange()">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Simulate Order Update
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    How it Works
                </h5>
            </div>
            <div class="card-body">
                <ol>
                    <li><strong>Server-Sent Events (SSE):</strong> The browser establishes a persistent connection to the server.</li>
                    <li><strong>Real-time Updates:</strong> When new notifications are created, they're instantly pushed to all connected admin users.</li>
                    <li><strong>Automatic Reconnection:</strong> If the connection drops, the system automatically attempts to reconnect.</li>
                    <li><strong>Fallback:</strong> If SSE fails, the system falls back to traditional polling every 30 seconds.</li>
                </ol>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Tip:</strong> Open this page in multiple browser tabs to see notifications appear simultaneously across all tabs.
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="ratings.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Ratings
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/notifications.js"></script>
    <script>
        // Test connection status
        function updateConnectionStatus(status, text) {
            const statusElement = document.getElementById('connectionStatus');
            const textElement = document.getElementById('connectionText');
            
            statusElement.className = `status-indicator status-${status}`;
            textElement.textContent = text;
        }
        
        // Override the NotificationManager to show connection status
        const originalInit = NotificationManager.init;
        NotificationManager.init = function() {
            originalInit.call(this);
            
            // Monitor connection status
            if (this.eventSource) {
                this.eventSource.onopen = () => {
                    updateConnectionStatus('connected', 'Connected');
                };
                
                this.eventSource.onerror = () => {
                    updateConnectionStatus('disconnected', 'Disconnected');
                };
            }
        };
        
        // Test function to simulate order status change
        function testOrderStatusChange() {
            fetch('api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'test_order_update',
                    order_id: Math.floor(Math.random() * 1000) + 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Test order update notification created!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating test notification');
            });
        }
    </script>
</body>
</html>
