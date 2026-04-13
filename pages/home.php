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
    // Smooth scroll for link
    document.querySelector('.scroll-down').addEventListener('click', function(e) {
        e.preventDefault();
        const menuSection = document.querySelector('#menu');
        if (menuSection) {
            menuSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

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

    // Popup Logic
    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    if (!isLoggedIn && !sessionStorage.getItem('popupShown')) {
        setTimeout(showPopup, 3000);
    }

    document.getElementById('signupButton').addEventListener('click', closePopup);
    document.getElementById('popupOverlay').addEventListener('click', closePopup);
});

function showPopup() {
    const overlay = document.getElementById('popupOverlay');
    const ad = document.getElementById('popupAd');
    if (overlay && ad) {
        overlay.classList.add('show');
        ad.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closePopup() {
    const overlay = document.getElementById('popupOverlay');
    const ad = document.getElementById('popupAd');
    if (overlay && ad) {
        overlay.classList.remove('show');
        ad.classList.remove('show');
        document.body.style.overflow = '';
        if (document.getElementById('dontShowAgain').checked) {
            sessionStorage.setItem('popupShown', 'true');
        }
    }
}

// Close popup with ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePopup();
});
</script>
<?php 
$extra_scripts = ob_get_clean();

// Include the footer (contains closing </body></html> and common scripts)
include 'includes/ui/footer.php'; 
?>