<?php
function displayProfilePhoto($user) {
    $upload_dir = '../assets/images/profiles/';
    if ($user['profile_photo'] && file_exists($upload_dir . $user['profile_photo'])) {
        return '<img src="../assets/images/profiles/' . htmlspecialchars($user['profile_photo']) . 
               '" alt="Profile Photo" class="profile-photo">';
    } else {
        return '<div class="profile-photo-text">' . 
               strtoupper(substr($user['username'], 0, 1)) . 
               '</div>';
    }
}
?> 