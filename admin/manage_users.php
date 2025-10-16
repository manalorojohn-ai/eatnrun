<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle user status updates
if (isset($_POST['update_status']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];
    
    $query = "UPDATE users SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':status' => $status,
        ':id' => $user_id
    ]);
}

// Get all users except current admin
$query = "SELECT * FROM users WHERE id != :current_user ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([':current_user' => $_SESSION['user_id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #fff;
            line-height: 1.6;
        }

        .hero-section {
            padding: 40px 24px;
            text-align: center;
            background-color: #FFF8E7;
        }

        .hero-title {
            color: #006C3B;
            font-size: 36px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .hero-subtitle {
            color: #4A4A4A;
            font-size: 16px;
            max-width: 800px;
            margin: 0 auto;
        }

        .users-section {
            padding: 40px 24px;
        }

        .users-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .users-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .section-title {
            color: #006C3B;
            font-size: 24px;
            font-weight: 600;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            color: #4A4A4A;
            font-weight: 600;
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        /* Status Badge */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Action Buttons */
        .action-btn {
            background-color: #006C3B;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .action-btn:hover {
            background-color: #005530;
        }

        .delete-btn {
            background-color: #dc3545;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 28px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_navbar.php'; ?>

    <section class="hero-section">
        <h1 class="hero-title">Manage Users</h1>
        <p class="hero-subtitle">View and manage user accounts</p>
    </section>

    <section class="users-section">
        <div class="users-container">
            <div class="users-card">
                <div class="section-header">
                    <h2 class="section-title">All Users</h2>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?php echo str_pad($user['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="status" class="status-badge status-<?php echo $user['status'] ?? 'active'; ?>" 
                                                onchange="this.form.submit()">
                                            <option value="active" <?php echo ($user['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($user['status'] ?? 'active') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="action-btn delete-btn" 
                                            onclick="if(confirm('Are you sure you want to delete this user?')) window.location.href='delete_user.php?id=<?php echo $user['id']; ?>'">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/admin_footer.php'; ?>
</body>
</html> 