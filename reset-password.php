<?php
session_start();
require_once 'includes/connection.php';

// If user is already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';
$valid_token = false;

// Check if the database connection is valid
if (!$conn) {
    $error = "Unable to connect to the database. Please try again later.";
    error_log("Database connection failed in reset-password.php");
} else {
    // Verify token
    if (!isset($_GET['token']) || empty($_GET['token'])) {
        $error = "Invalid or missing reset token.";
    } else {
        $token = mysqli_real_escape_string($conn, $_GET['token']);
        
        // Check if password_resets table exists
        $tables_result = mysqli_query($conn, "SHOW TABLES LIKE 'password_resets'");
        
        if (!$tables_result || mysqli_num_rows($tables_result) == 0) {
            $error = "Password reset functionality is not available. Please contact support.";
            error_log("password_resets table does not exist");
        } else {
            // Check table structure
            $columns_result = mysqli_query($conn, "DESCRIBE password_resets");
            if (!$columns_result) {
                $error = "Unable to verify password reset system. Please try again later.";
                error_log("Failed to get password_resets table structure: " . mysqli_error($conn));
            } else {
                // Create a map of column names to check existence
                $columns = [];
                while ($column = mysqli_fetch_assoc($columns_result)) {
                    $columns[$column['Field']] = true;
                }
                
                // Determine which columns to use based on table structure
                $expires_column = isset($columns['expires_at']) ? 'expires_at' : 'expires';
                $used_column_exists = isset($columns['used']);
                
                // Build the query based on available columns
                $query = "SELECT pr.user_id, pr.token, u.email 
                          FROM password_resets pr 
                          JOIN users u ON pr.user_id = u.id 
                          WHERE pr.token = ?";
                
                // Add expiry check if the column exists
                if (isset($columns[$expires_column])) {
                    $query .= " AND pr.$expires_column > NOW()";
                }
                
                // Add used check if the column exists
                if ($used_column_exists) {
                    $query .= " AND pr.used = 0";
                }
                
                try {
                    $stmt = $conn->prepare($query);
                    if (!$stmt) {
                        throw new Exception("Failed to prepare statement: " . $conn->error);
                    }
                    
                    $stmt->bind_param("s", $token);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to execute statement: " . $stmt->error);
                    }
                    
                    $result = $stmt->get_result();

                    if ($result->num_rows === 0) {
                        $error = "Invalid or expired reset link. Please request a new one.";
                        error_log("Invalid token or expired reset link attempted: " . $token);
                    } else {
                        $reset_data = $result->fetch_assoc();
                        $valid_token = true;
                        
                        // Handle form submission
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            // Verify token is still present in POST request (security check)
                            if (!isset($_GET['token']) || $_GET['token'] !== $token) {
                                $error = "Security validation failed. Please try again.";
                                error_log("Token mismatch in POST request");
                                $valid_token = false;
                            } else {
                                // Get and validate password input
                                $password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
                                $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
                                
                                // Validate password
                                if (strlen($password) < 8) {
                                    $error = "Password must be at least 8 characters long";
                                } elseif (!preg_match('/[a-z]/', $password)) {
                                    $error = "Password must contain at least one lowercase letter";
                                } elseif (!preg_match('/[A-Z]/', $password)) {
                                    $error = "Password must contain at least one uppercase letter";
                                } elseif (!preg_match('/[0-9]/', $password)) {
                                    $error = "Password must contain at least one number";
                                } elseif ($password !== $confirm_password) {
                                    $error = "Passwords do not match";
                                } else {
                                    try {
                                        // Start transaction
                                        $conn->begin_transaction();

                                        // Update password
                                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                                        
                                        if (!$update_stmt) {
                                            throw new Exception("Failed to prepare password update statement: " . $conn->error);
                                        }
                                        
                                        $update_stmt->bind_param("si", $hashed_password, $reset_data['user_id']);
                                        
                                        if (!$update_stmt->execute()) {
                                            throw new Exception("Failed to update password: " . $update_stmt->error);
                                        }
                                        
                                        if ($update_stmt->affected_rows > 0) {
                                            // Password updated successfully
                                            // Now handle the token based on the schema
                                            if ($used_column_exists) {
                                                // Mark token as used
                                                $mark_used_stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                                                if (!$mark_used_stmt) {
                                                    throw new Exception("Failed to prepare token update statement");
                                                }
                                                $mark_used_stmt->bind_param("s", $token);
                                                $mark_used_stmt->execute();
                                            } else {
                                                // Delete the token if used column doesn't exist
                                                $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                                                if (!$delete_stmt) {
                                                    throw new Exception("Failed to prepare token delete statement");
                                                }
                                                $delete_stmt->bind_param("s", $token);
                                                $delete_stmt->execute();
                                            }
                                            
                                            // Commit transaction
                                            $conn->commit();
                                            
                                            $success = "Password has been reset successfully. You can now login with your new password.";
                                            $valid_token = false; // Hide the form after successful reset
                                            error_log("Password reset successful for user ID: " . $reset_data['user_id']);
                                        } else {
                                            throw new Exception("No user was updated. User might not exist anymore.");
                                        }
                                    } catch (Exception $e) {
                                        // Rollback transaction on error
                                        $conn->rollback();
                                        $error = "An error occurred. Please try again later.";
                                        error_log("Password reset error: " . $e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $error = "A system error occurred. Please try again later.";
                    error_log("Password reset query error: " . $e->getMessage());
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .auth-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #006C3B;
            text-align: center;
            margin-bottom: 25px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #006C3B;
        }

        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 8px 0;
            font-size: 13px;
            color: #666;
        }

        .requirement.valid {
            color: #28a745;
        }

        .requirement.invalid {
            color: #dc3545;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #006C3B;
            color: white;
        }

        .btn-primary:hover {
            background: #005731;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .auth-links {
            text-align: center;
            margin-top: 20px;
        }

        .auth-links a {
            color: #006C3B;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .auth-links a:hover {
            color: #005731;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <h2>Reset Password</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($valid_token): ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?token=' . htmlspecialchars($token); ?>" id="resetForm">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                    </div>
                    
                    <div class="password-requirements">
                        <div class="requirement" id="length">
                            <i class="fas fa-times"></i> At least 8 characters
                        </div>
                        <div class="requirement" id="lowercase">
                            <i class="fas fa-times"></i> One lowercase letter
                        </div>
                        <div class="requirement" id="uppercase">
                            <i class="fas fa-times"></i> One uppercase letter
                        </div>
                        <div class="requirement" id="number">
                            <i class="fas fa-times"></i> One number
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
                
                <script>
                    document.getElementById('new_password').addEventListener('input', function() {
                        const password = this.value;
                        
                        // Check requirements
                        document.getElementById('length').className = 
                            'requirement ' + (password.length >= 8 ? 'valid' : 'invalid');
                        document.getElementById('lowercase').className = 
                            'requirement ' + (/[a-z]/.test(password) ? 'valid' : 'invalid');
                        document.getElementById('uppercase').className = 
                            'requirement ' + (/[A-Z]/.test(password) ? 'valid' : 'invalid');
                        document.getElementById('number').className = 
                            'requirement ' + (/[0-9]/.test(password) ? 'valid' : 'invalid');
                        
                        // Update icons
                        document.querySelectorAll('.requirement').forEach(req => {
                            const icon = req.querySelector('i');
                            icon.className = req.classList.contains('valid') ? 'fas fa-check' : 'fas fa-times';
                        });
                    });
                    
                    document.getElementById('resetForm').addEventListener('submit', function(e) {
                        const password = document.getElementById('new_password').value;
                        const confirm = document.getElementById('confirm_password').value;
                        
                        if (password !== confirm) {
                            e.preventDefault();
                            alert('Passwords do not match!');
                            return false;
                        }
                        
                        if (password.length < 8 || !/[a-z]/.test(password) || 
                            !/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
                            e.preventDefault();
                            alert('Please meet all password requirements!');
                            return false;
                        }
                    });
                </script>
            <?php endif; ?>
            
            <div class="auth-links">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html> 