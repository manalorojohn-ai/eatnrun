<?php
// Get user data if not already available
if (!isset($user) || !isset($user['profile_image'])) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT id, full_name, email, profile_image, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();
    $stmt->close();
}

// Get last updated time
$last_updated = date('g:i:s A');
?>

<div class="profile-section">
    <div class="profile-image">
        <img src="<?php echo !empty($user['profile_image']) ? '../uploads/profile_photos/' . htmlspecialchars($user['profile_image']) : '../assets/images/default-avatar.png'; ?>" 
             alt="Profile Image" 
             id="headerProfileImage">
    </div>
    <div class="profile-info">
        <div class="profile-name">
            <?php echo htmlspecialchars($user['full_name']); ?> 
            <i class="fas fa-chevron-down chevron"></i>
        </div>
        <div class="profile-role">
            <i class="fas fa-user-shield"></i> 
            <?php echo htmlspecialchars($user['role']); ?>
        </div>
    </div>
    <div class="last-updated">
        <i class="far fa-clock"></i>
        Last updated: <?php echo $last_updated; ?>
    </div>
</div>

<div class="profile-dropdown">
    <a href="profile.php" class="dropdown-item">
        <i class="fas fa-user"></i>
        View Profile
    </a>
    <a href="profile.php#change-password" class="dropdown-item">
        <i class="fas fa-lock"></i>
        Change Password
    </a>
    <a href="../logout.php" class="dropdown-item text-danger">
        <i class="fas fa-sign-out-alt"></i>
        Logout
    </a>
</div>

<style>
.profile-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1.25rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(8px);
    min-width: 280px;
}

.profile-section:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
}

.profile-image {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--primary-light);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.8);
}

.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-info {
    flex: 1;
    min-width: 0;
}

.profile-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: white;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.profile-name .chevron {
    font-size: 1rem;
    color: var(--text-muted);
    transition: transform 0.3s ease;
}

.profile-role {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.profile-role i {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.75rem;
}

.last-updated {
    display: none;
}

.profile-dropdown {
    position: absolute;
    top: calc(100% + 0.75rem);
    right: 0;
    background: var(--white);
    border-radius: 12px;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    min-width: 220px;
    z-index: 1000;
    transform-origin: top right;
    opacity: 0;
    visibility: hidden;
    transform: scale(0.95);
    transition: all 0.2s ease;
}

.profile-section.active + .profile-dropdown {
    opacity: 1;
    visibility: visible;
    transform: scale(1);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-color);
    text-decoration: none;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background: var(--gray-50);
}

.dropdown-item i {
    font-size: 1rem;
    color: var(--primary-color);
}

.dropdown-item.text-danger i {
    color: var(--danger);
}

.dropdown-item.text-danger {
    color: var(--danger);
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileSection = document.querySelector('.profile-section');
    
    profileSection.addEventListener('click', function() {
        this.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!profileSection.contains(e.target)) {
            profileSection.classList.remove('active');
        }
    });

    // Update header profile image when main profile image changes
    const mainProfileImage = document.getElementById('currentProfileImage');
    const headerProfileImage = document.getElementById('headerProfileImage');
    
    if (mainProfileImage && headerProfileImage) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
                    headerProfileImage.src = mainProfileImage.src;
                }
            });
        });

        observer.observe(mainProfileImage, {
            attributes: true,
            attributeFilter: ['src']
        });
    }
});
</script> 