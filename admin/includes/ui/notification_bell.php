<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/notifications_handler.php';

// Get unread notifications count
$admin_id = $_SESSION['user_id'];
$unread_count = get_unread_notifications_count($conn, $admin_id);
?>

<div class="dropdown">
    <button class="btn btn-notification position-relative p-0 me-3" type="button" id="notificationDropdown">
        <div class="notification-bell">
            <i class="fas fa-bell"></i>
            <?php if ($unread_count > 0): ?>
                <span class="notification-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
    </button>
    <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg">
        <div class="p-3 border-bottom bg-light">
            <h6 class="notification-header-title">
                <span class="fw-semibold">Notifications</span>
                <span class="notification-count-badge">
                    <i class="fas fa-bell"></i>
                    <span id="unreadCount"><?php echo $unread_count; ?></span> New
                </span>
            </h6>
        </div>
        <div class="notification-list">
            <div class="list-group list-group-flush">
                <!-- Notifications will be loaded here by JavaScript -->
                <div class="text-center py-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-2 border-top text-center bg-light">
            <a href="notifications.php" class="text-success text-decoration-none fw-medium small">
                View all notifications
                <i class="fas fa-chevron-right ms-1 small"></i>
            </a>
        </div>
    </div>
</div>

<script>
// Initialize notification dropdown
document.getElementById('notificationDropdown').addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = this.nextElementSibling;
    dropdown.classList.toggle('show');
    
    if (dropdown.classList.contains('show')) {
        // Close when clicking outside
        setTimeout(() => {
            document.addEventListener('click', function closeDropdown(e) {
                if (!dropdown.contains(e.target) && e.target !== document.getElementById('notificationDropdown')) {
                    dropdown.classList.remove('show');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }, 0);
    }
});
</script>

<?php
function get_notification_color($type) {
    $colors = [
        'regular_order' => 'success',
        'hotel_order' => 'primary',
        'status_update' => 'info',
        'cancellation' => 'danger',
        'system' => 'warning'
    ];
    return $colors[$type] ?? 'secondary';
}
?> 