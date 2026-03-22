<?php
// Get unread notifications count
$unread_count = isset($unread_messages) ? $unread_messages : 0;
?>

<!-- Notification Bell Dropdown -->
<div class="dropdown">
    <button class="btn btn-notification p-0" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="notification-bell">
            <i class="fas fa-bell"></i>
            <?php if ($unread_count > 0): ?>
            <span class="notification-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
    </button>
    <div class="dropdown-menu dropdown-menu-end notification-dropdown">
        <div class="notification-header">
            <h6 class="notification-header-title">
                <span class="fw-semibold">Notifications</span>
                <?php if ($unread_count > 0): ?>
                <span class="notification-count-badge">
                    <i class="fas fa-bell"></i>
                    <span><?php echo $unread_count; ?> New</span>
                </span>
                <?php endif; ?>
            </h6>
        </div>
        <div class="notification-list" id="notificationList">
            <!-- Notifications will be loaded here via JavaScript -->
            <div class="no-notifications">
                <i class="fas fa-bell-slash"></i>
                <p>No new notifications</p>
            </div>
        </div>
        <div class="notification-footer">
            <a href="notifications.php">
                View All Notifications
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
</div> 