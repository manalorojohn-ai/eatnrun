<?php
require_once 'includes/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set page-specific variables
$current_page = 'home';
$page_title = 'Delicious Food Delivery';

// Pass extra styles for the home page
ob_start(); ?>
<link rel="stylesheet" href="assets/css/home-enhanced.css">
<?php 
$extra_styles = ob_get_clean();

// Include the header (contains <head> and opening <body>)
include 'includes/ui/header.php';

// Include the loader
include 'includes/ui/loader.php';

// Include the navbar
include 'includes/ui/navbar.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="floating-element floating-1"></div>
    <div class="floating-element floating-2"></div>
    <div class="floating-element floating-3"></div>
    
    <div class="hero-content">
        <h1 class="hero-title">Welcome to Eat&Run!</h1>
        <p class="hero-subtitle">Your favorite local restaurants delivered to your doorstep. Fast, fresh, and convenient food delivery service.</p>
        <div class="hero-cta">
            <a href="menu" class="hero-btn primary-btn" aria-label="Browse our menu">
                <i class="fas fa-utensils"></i>
                Browse Menu
            </a>
            <a href="about" class="hero-btn secondary-btn" aria-label="Learn more about us">
                <i class="fas fa-info-circle"></i>
                Learn More
            </a>
        </div>
    </div>
    
    <a href="#menu" class="scroll-down" aria-label="Scroll to featured menu">
        <div class="scroll-indicator">
            <div class="pulse-ring"></div>
            <i class="fas fa-chevron-down"></i>
        </div>
    </a>
</section>

<!-- Featured Menu Section -->
<section id="menu" class="menu-section">
    <div class="menu-container">
        <div class="menu-header">
            <h2 class="menu-title">Featured Menu</h2>
            <a href="menu" class="view-all">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="menu-grid">
            <!-- Featured Items -->
            <div class="menu-item" onclick="window.location.href='menu'">
                <div class="img-container">
                    <img src="assets/images/menu/plain-burger.jpg" alt="Plain Burger" loading="lazy" onerror="this.onerror=null; this.src='https://via.placeholder.com/300x200?text=Burger'">
                    <div class="img-overlay"></div>
                </div>
                <div class="menu-item-content">
                    <h3 class="menu-item-title">Plain Burger</h3>
                </div>
            </div>

            <div class="menu-item" onclick="window.location.href='menu'">
                <div class="img-container">
                    <img src="assets/images/menu/bicol-express.jpg" alt="Bicol Express" loading="lazy" onerror="this.onerror=null; this.src='https://via.placeholder.com/300x200?text=Bicol+Express'">
                    <div class="img-overlay"></div>
                </div>
                <div class="menu-item-content">
                    <h3 class="menu-item-title">Bicol Express</h3>
                </div>
            </div>

            <div class="menu-item" onclick="window.location.href='menu'">
                <div class="img-container">
                    <img src="assets/images/menu/halo-halo.jpg" alt="Halo-Halo" loading="lazy" onerror="this.onerror=null; this.src='https://via.placeholder.com/300x200?text=Halo-Halo'">
                    <div class="img-overlay"></div>
                </div>
                <div class="menu-item-content">
                    <h3 class="menu-item-title">Halo-Halo</h3>
                </div>
            </div>

            <div class="menu-item" onclick="window.location.href='menu'">
                <div class="img-container">
                    <img src="assets/images/menu/mango-juice.jpg" alt="Mango Juice" loading="lazy" onerror="this.onerror=null; this.src='https://via.placeholder.com/300x200?text=Mango+Juice'">
                    <div class="img-overlay"></div>
                </div>
                <div class="menu-item-content">
                    <h3 class="menu-item-title">Mango Juice</h3>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Privacy & Cookie Consent Card (Modal Overlay) -->
<div class="privacy-modal-overlay" id="privacyOverlay"></div>
<div class="privacy-card-modal" id="privacyCardModal">
    <div class="privacy-card">
        <div class="privacy-content">
            <span class="privacy-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" height="46" width="65" viewBox="0 0 65 46">
                    <path stroke="#000" fill="#EAB789" d="M49.157 15.69L44.58.655l-12.422 1.96L21.044.654l-8.499 2.615-6.538 5.23-4.576 9.153v11.114l4.576 8.5 7.846 5.23 10.46 1.96 7.845-2.614 9.153 2.615 11.768-2.615 7.846-7.846 1.96-5.884.655-7.191-7.846-1.308-6.537-3.922z"></path>
                    <path fill="#9C6750" d="M32.286 3.749c-6.94 3.65-11.69 11.053-11.69 19.591 0 8.137 4.313 15.242 10.724 19.052a20.513 20.513 0 01-8.723 1.937c-11.598 0-21-9.626-21-21.5 0-11.875 9.402-21.5 21-21.5 3.495 0 6.79.874 9.689 2.42z" clip-rule="evenodd" fill-rule="evenodd"></path>
                    <path fill="#634647" d="M64.472 20.305a.954.954 0 00-1.172-.824 4.508 4.508 0 01-3.958-.934.953.953 0 00-1.076-.11c-.46.252-.977.383-1.502.382a3.154 3.154 0 01-2.97-2.11.954.954 0 00-.833-.634 4.54 4.54 0 01-4.205-4.507c.002-.23.022-.46.06-.687a.952.952 0 00-.213-.767 3.497 3.497 0 01-.614-3.5.953.953 0 00-.382-1.138 3.522 3.522 0 01-1.5-3.992.951.951 0 00-.762-1.227A22.611 22.611 0 0032.3 2.16 22.41 22.41 0 0022.657.001a22.654 22.654 0 109.648 43.15 22.644 22.644 0 0032.167-22.847zM22.657 43.4a20.746 20.746 0 110-41.493c2.566-.004 5.11.473 7.501 1.407a22.64 22.64 0 00.003 38.682 20.6 20.6 0 01-7.504 1.404zm19.286 0a20.746 20.746 0 112.131-41.384 5.417 5.417 0 001.918 4.635 5.346 5.346 0 00-.133 1.182A5.441 5.441 0 0046.879 11a5.804 5.804 0 00-.028.568 6.456 6.456 0 005.38 6.345 5.053 5.053 0 006.378 2.472 6.412 6.412 0 004.05 1.12 20.768 20.768 0 01-20.716 21.897z"></path>
                    <path fill="#644647" d="M54.962 34.3a17.719 17.719 0 01-2.602 2.378.954.954 0 001.14 1.53 19.637 19.637 0 002.884-2.634.955.955 0 00-1.422-1.274z"></path>
                    <path stroke-width="1.8" stroke="#644647" fill="#845556" d="M44.5 32.829c-.512 0-1.574.215-2 .5-.426.284-.342.263-.537.736a2.59 2.59 0 104.98.99c0-.686-.458-1.241-.943-1.726-.485-.486-.814-.5-1.5-.5zm-30.916-2.5c-.296 0-.912.134-1.159.311-.246.177-.197.164-.31.459a1.725 1.725 0 00-.086.932c.058.312.2.6.41.825.21.226.477.38.768.442.291.062.593.03.867-.092s.508-.329.673-.594a1.7 1.7 0 00.253-.896c0-.428-.266-.774-.547-1.076-.281-.302-.471-.31-.869-.311zm17.805-11.375c-.143-.492-.647-1.451-1.04-1.78-.392-.33-.348-.255-.857-.31a2.588 2.588 0 10.441 5.06c.66-.194 1.064-.788 1.395-1.39.33-.601.252-.92.06-1.58zm-22 2c-.143-.492-.647-1.451-1.04-1.78-.391-.33-.347-.255-.856-.31a2.589 2.589 0 10.44 5.06c.66-.194 1.064-.788 1.395-1.39.33-.601.252-.92.06-1.58zM38.112 7.329c-.395 0-1.216.179-1.545.415-.328.236-.263.218-.415.611-.151.393-.19.826-.114 1.243.078.417.268.8.548 1.1.28.301.636.506 1.024.59.388.082.79.04 1.155-.123.366-.163.678-.438.898-.792.22-.354.337-.77.337-1.195 0-.57-.354-1.031-.73-1.434-.374-.403-.628-.415-1.158-.415zm-19.123.703c.023-.296-.062-.92-.219-1.18-.157-.26-.148-.21-.432-.347a1.726 1.726 0 00-.922-.159 1.654 1.654 0 00-.856.344 1.471 1.471 0 00-.501.73c-.085.285-.077.589.023.872.1.282.287.532.538.718a1.7 1.7 0 00.873.323c.427.033.793-.204 1.116-.46.324-.256.347-.445.38-.841z"></path>
                    <path fill="#634647" d="M15.027 15.605a.954.954 0 00-1.553 1.108l1.332 1.863a.955.955 0 001.705-.77.955.955 0 00-.153-.34l-1.331-1.861z"></path>
                    <path fill="#644647" d="M43.31 23.21a.954.954 0 101.553-1.11l-1.266-1.772a.954.954 0 10-1.552 1.11l1.266 1.772z"></path>
                    <path fill="#634647" d="M19.672 35.374a.954.954 0 00-.954.953v2.363a.954.954 0 001.907 0v-2.362a.954.954 0 00-.953-.954z"></path>
                    <path fill="#644647" d="M33.129 29.18l-2.803 1.065a.953.953 0 00-.053 1.764.957.957 0 00.73.022l2.803-1.065a.953.953 0 00-.677-1.783v-.003zm24.373-3.628l-2.167.823a.956.956 0 00-.054 1.764.954.954 0 00.73.021l2.169-.823a.954.954 0 10-.678-1.784v-.001z"></path>
                </svg>
            </span>
            <p class="privacy-title">Your privacy is important to us</p>
            <p class="privacy-description">We process your personal information to measure and improve our sites and services, to assist our campaigns and to provide personalized content.<br />For more information see our<a href="info/privacy" class="privacy-link"> Privacy Policy</a>.</p>
            <div class="privacy-actions">
                <button class="more-options-btn">More Options</button>
                <button class="accept-btn">Accept</button>
            </div>
        </div>
    </div>
</div>

<!-- Popup Advertisement -->
<div class="popup-overlay" id="popupOverlay"></div>
<div class="popup-ad" id="popupAd" role="dialog" aria-labelledby="popupTitle">
    <div class="popup-close" onclick="closePopup()" aria-label="Close promotion">
        <i class="fas fa-times"></i>
    </div>
    <div class="popup-content">
        <div class="popup-image-container" style="background: linear-gradient(45deg, #8B4513, #A0522D);">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1NiIgaGVpZ2h0PSIyOCI+CiAgPHBhdGggZD0iTTI4IDAgTDU2IDE0IEwyOCAyOCBMMCAxNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMSkiIHN0cm9rZS13aWR0aD0iMiI+PC9wYXRoPgo8L3N2Zz4=') center/cover"></div>
            <div style="position: relative; z-index: 1; padding: 40px 20px; color: white; text-align: center;">
                <i class="fas fa-hotel" style="font-size: 40px; margin-bottom: 15px;"></i>
                <h2 style="font-size: 32px; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">EXCLUSIVE OFFER</h2>
            </div>
        </div>
        <div class="popup-text-content">
            <h3 class="popup-title" id="popupTitle">Get 25% OFF</h3>
            <p class="popup-description">Book now and enjoy an exclusive discount on your first stay at FAWNA Hotel! Limited time offer package for new customers.</p>
            <a href="https://fawna-hotel.onrender.com/" class="popup-cta" id="signupButton" target="_blank">
                <i class="fas fa-bed" style="margin-right: 8px;"></i>
                Book Now & Save
            </a>
            <div class="popup-footer">
                *Limited time offer for new guests only
            </div>
            <label class="dont-show-again">
                <input type="checkbox" id="dontShowAgain">
                Don't show this again
            </label>
        </div>
    </div>
</div>

<?php 
// Pass home page specific scripts
ob_start(); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded - DOMContentLoaded fired');
    
    // Smooth scroll for link
    const scrollDownBtn = document.querySelector('.scroll-down');
    if (scrollDownBtn) {
        scrollDownBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const menuSection = document.querySelector('#menu');
            if (menuSection) {
                menuSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    // Intersection Observer for menu items
    const observerOptions = { threshold: 0.15, rootMargin: '0px 0px -50px 0px' };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.menu-item').forEach((item, index) => {
        item.style.setProperty('--delay', `${index * 0.1}s`);
        observer.observe(item);
    });

    // ==================== PRIVACY CARD MODAL ====================
    const privacyOverlay = document.querySelector('.privacy-modal-overlay');
    const privacyCardModal = document.querySelector('.privacy-card-modal');
    const acceptBtn = document.querySelector('.accept-btn');
    const moreOptionsBtn = document.querySelector('.more-options-btn');
    
    const privacyAccepted = localStorage.getItem('privacyAccepted');
    console.log('Privacy accepted status:', privacyAccepted);
    
    // Show privacy card if not previously accepted
    if (!privacyAccepted) {
        console.log('Showing privacy card modal');
        if (privacyOverlay) {
            privacyOverlay.classList.add('show');
            privacyOverlay.style.display = 'block';
        }
        if (privacyCardModal) {
            privacyCardModal.classList.add('show');
            privacyCardModal.style.display = 'flex';
        }
    }

    // Accept button handler
    if (acceptBtn) {
        acceptBtn.addEventListener('click', function() {
            console.log('Privacy accepted - closing modal');
            localStorage.setItem('privacyAccepted', 'true');
            if (privacyCardModal && privacyOverlay) {
                privacyCardModal.style.animation = 'slideOut 0.4s ease-out forwards';
                setTimeout(() => {
                    privacyCardModal.classList.remove('show');
                    privacyCardModal.style.display = 'none';
                    privacyOverlay.classList.remove('show');
                    privacyOverlay.style.display = 'none';
                    // Show popup after privacy is closed
                    showPopup();
                }, 400);
            }
        });
    }

    // More Options button handler
    if (moreOptionsBtn) {
        moreOptionsBtn.addEventListener('click', function() {
            window.location.href = 'info/privacy';
        });
    }

    // Overlay click handler
    if (privacyOverlay) {
        privacyOverlay.addEventListener('click', function(e) {
            if (e.target === privacyOverlay) {
                console.log('Privacy overlay clicked - closing');
                localStorage.setItem('privacyAccepted', 'true');
                privacyCardModal.style.animation = 'slideOut 0.4s ease-out forwards';
                setTimeout(() => {
                    privacyCardModal.classList.remove('show');
                    privacyCardModal.style.display = 'none';
                    privacyOverlay.classList.remove('show');
                    privacyOverlay.style.display = 'none';
                    // Show popup after privacy is closed
                    showPopup();
                }, 400);
            }
        });
    }

    // ==================== POPUP AD LOGIC ====================
    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    console.log('User logged in:', isLoggedIn);
    console.log('Privacy accepted:', privacyAccepted);
    
    // Only show popup if user not logged in, privacy was already accepted
    if (!isLoggedIn && privacyAccepted && !sessionStorage.getItem('popupShown')) {
        console.log('Will show popup after 3 seconds');
        setTimeout(showPopup, 3000);
    } else if (!isLoggedIn && !privacyAccepted) {
        console.log('Privacy not accepted - popup will show after privacy modal closes');
    }

    // Popup event listeners
    const signupButton = document.getElementById('signupButton');
    const popupOverlay = document.getElementById('popupOverlay');
    
    if (signupButton) {
        signupButton.addEventListener('click', closePopup);
    }
    if (popupOverlay) {
        popupOverlay.addEventListener('click', closePopup);
    }
});

// ==================== POPUP FUNCTIONS ====================
function showPopup() {
    console.log('showPopup() called');
    const overlay = document.getElementById('popupOverlay');
    const ad = document.getElementById('popupAd');
    
    if (overlay && ad) {
        console.log('Showing popup ad');
        overlay.classList.add('show');
        ad.classList.add('show');
        document.body.style.overflow = 'hidden';
    } else {
        console.log('Popup elements not found - overlay:', !!overlay, 'ad:', !!ad);
    }
}

function closePopup() {
    console.log('closePopup() called');
    const overlay = document.getElementById('popupOverlay');
    const ad = document.getElementById('popupAd');
    
    if (overlay && ad) {
        overlay.classList.remove('show');
        ad.classList.remove('show');
        document.body.style.overflow = '';
        
        const dontShowAgain = document.getElementById('dontShowAgain');
        if (dontShowAgain && dontShowAgain.checked) {
            sessionStorage.setItem('popupShown', 'true');
        }
    }
}

// Close popup with ESC key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        console.log('ESC pressed - closing popup');
        closePopup();
    }
});
</script>
<?php 
$extra_scripts = ob_get_clean();

// Include the footer (contains closing </body></html> and common scripts)
include 'includes/ui/footer.php'; 
?>