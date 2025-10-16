<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php");
    exit();
}

$message = '';
$messageType = '';

// Get current settings
try {
    $stmt = $conn->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching settings: " . $e->getMessage());
    $settings = [
        'delivery_fee' => 50,
        'min_order' => 100,
        'opening_time' => '08:00',
        'closing_time' => '22:00',
        'email_notifications' => 1,
        'push_notifications' => 1
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize inputs
        $delivery_fee = filter_var($_POST['delivery_fee'], FILTER_VALIDATE_FLOAT);
        $min_order = filter_var($_POST['min_order'], FILTER_VALIDATE_FLOAT);
        $opening_time = filter_var($_POST['opening_time'], FILTER_SANITIZE_STRING);
        $closing_time = filter_var($_POST['closing_time'], FILTER_SANITIZE_STRING);
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;

        // Update settings
        $stmt = $conn->prepare("
            UPDATE settings SET 
                delivery_fee = ?,
                min_order = ?,
                opening_time = ?,
                closing_time = ?,
                email_notifications = ?,
                push_notifications = ?
            WHERE id = (SELECT id FROM (SELECT id FROM settings ORDER BY id DESC LIMIT 1) AS s)
        ");

        $stmt->execute([
            $delivery_fee,
            $min_order,
            $opening_time,
            $closing_time,
            $email_notifications,
            $push_notifications
        ]);

        // Refresh settings
        $stmt = $conn->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        $message = "Settings updated successfully!";
        $messageType = "success";

    } catch(PDOException $e) {
        error_log("Error updating settings: " . $e->getMessage());
        $message = "Error updating settings. Please try again.";
        $messageType = "error";
    }
}

$page_title = "Settings - Admin Panel";
include 'includes/admin-header.php';
?>

<div class="admin-container">
    <?php include 'includes/admin-nav.php'; ?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <h1>Settings</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            <form method="POST" class="settings-form">
                <div class="settings-grid">
                    <!-- General Settings -->
                    <div class="settings-card">
                        <div class="card-header">
                            <i class="fas fa-cog"></i>
                            <h2>General Settings</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="delivery_fee">Delivery Fee (₱)</label>
                                <input type="number" id="delivery_fee" name="delivery_fee" 
                                       value="<?php echo htmlspecialchars($settings['delivery_fee']); ?>" 
                                       step="0.01" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="min_order">Minimum Order Amount (₱)</label>
                                <input type="number" id="min_order" name="min_order" 
                                       value="<?php echo htmlspecialchars($settings['min_order']); ?>" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>

                    <!-- Store Hours -->
                    <div class="settings-card">
                        <div class="card-header">
                            <i class="fas fa-clock"></i>
                            <h2>Store Hours</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="opening_time">Opening Time</label>
                                <input type="time" id="opening_time" name="opening_time" 
                                       value="<?php echo htmlspecialchars($settings['opening_time']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="closing_time">Closing Time</label>
                                <input type="time" id="closing_time" name="closing_time" 
                                       value="<?php echo htmlspecialchars($settings['closing_time']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="settings-card">
                        <div class="card-header">
                            <i class="fas fa-bell"></i>
                            <h2>Notification Settings</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="email_notifications"
                                           <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                                    <span>Enable Email Notifications</span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="push_notifications"
                                           <?php echo $settings['push_notifications'] ? 'checked' : ''; ?>>
                                    <span>Enable Push Notifications</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Content Layout */
.content-wrapper {
    margin-left: var(--admin-nav-width);
    padding: 2rem;
    min-height: 100vh;
    background: #f3f4f6;
}

.content-header {
    margin-bottom: 2rem;
}

.content-header h1 {
    font-size: 1.875rem;
    font-weight: 600;
    color: #1f2937;
}

/* Settings Container */
.settings-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Settings Grid */
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

/* Settings Card */
.settings-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.settings-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 1.25rem;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.card-header i {
    font-size: 1.25rem;
    color: #006C3B;
}

.card-header h2 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.card-body {
    padding: 1.5rem;
}

/* Form Elements */
.form-group {
    margin-bottom: 1.25rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #4b5563;
    font-weight: 500;
}

.form-group input[type="number"],
.form-group input[type="time"] {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: border-color 0.2s;
}

.form-group input:focus {
    outline: none;
    border-color: #006C3B;
    box-shadow: 0 0 0 3px rgba(0, 108, 59, 0.1);
}

/* Checkbox Styles */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    padding: 0.5rem 0;
}

.checkbox-label input[type="checkbox"] {
    width: 1.125rem;
    height: 1.125rem;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
}

.checkbox-label span {
    color: #4b5563;
    font-weight: 500;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #006C3B;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-primary:hover {
    background: #005731;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .content-wrapper {
        margin-left: 0;
    }
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .content-wrapper {
        padding: 1rem;
    }
}

/* Alert Styles */
.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-success {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-info {
    background-color: #e0f2fe;
    color: #075985;
    border: 1px solid #bae6fd;
}

.alert i {
    font-size: 1.25rem;
}
</style>

<?php include 'includes/admin-footer.php'; ?> 