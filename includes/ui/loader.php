<link rel="stylesheet" href="assets/css/loader-enhanced.css">

<div class="page-loader">
    <div class="particles"></div>
    <div class="loader-content">
        <div class="loader-brand">
            <div class="loader-logo-container">
                <div class="loader-ring"></div>
                <div class="loader-ring"></div>
                <img src="assets/images/logo.png" alt="Eat&Run" class="loader-logo" onerror="this.onerror=null; this.src='https://via.placeholder.com/60?text=E%26R'">
            </div>
            <div class="loader-brand-text">Eat&Run</div>
        </div>
        <div class="loader-percentage">0%</div>
        <div class="loader-progress-container">
            <div class="loader-progress-bar"></div>
        </div>
        <div class="loader-text">Loading your experience...</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const progressBar = document.querySelector('.loader-progress-bar');
    const percentageText = document.querySelector('.loader-percentage');
    const loadingText = document.querySelector('.loader-text');
    const particles = document.querySelector('.particles');
    
    const loadingMessages = [
        "Preparing your delicious experience...",
        "Getting the menu ready...",
        "Adding the final ingredients...",
        "Almost ready to serve...",
        "Setting the table..."
    ];

    // Create floating particles
    if (particles) {
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 2 + 's';
            particles.appendChild(particle);
        }
    }
    
    let progress = 0;
    let interval = setInterval(function() {
        if (progress < 98) {
            progress += (100 - progress) * 0.05;
            
            if (percentageText) percentageText.textContent = Math.floor(progress) + '%';
            if (progressBar) progressBar.style.width = progress + '%';
            
            const messageIndex = Math.floor((progress / 100) * loadingMessages.length);
            if (loadingText) loadingText.textContent = loadingMessages[Math.min(messageIndex, loadingMessages.length - 1)];
        }
        
        if (progress >= 99) {
            clearInterval(interval);
        }
    }, 100);
});

window.addEventListener('load', function() {
    const percentageText = document.querySelector('.loader-percentage');
    const progressBar = document.querySelector('.loader-progress-bar');
    const loader = document.querySelector('.page-loader');

    if (percentageText) percentageText.textContent = '100%';
    if (progressBar) progressBar.style.width = '100%';
    
    setTimeout(function() {
        if (loader) loader.classList.add('hidden');
        document.body.style.overflow = ''; // Ensure body overflow is reset
    }, 600);
});

// Safety timeout: reveal page after 5 seconds no matter what
setTimeout(function() {
    const loader = document.querySelector('.page-loader');
    if (loader && !loader.classList.contains('hidden')) {
        console.warn('Loader timed out, forcing reveal.');
        const percentageText = document.querySelector('.loader-percentage');
        const progressBar = document.querySelector('.loader-progress-bar');
        if (percentageText) percentageText.textContent = '100%';
        if (progressBar) progressBar.style.width = '100%';
        setTimeout(function() {
            loader.classList.add('hidden');
            document.body.style.overflow = '';
        }, 600);
    }
}, 5000);

// Show loader on page transition
window.addEventListener('beforeunload', function() {
    const loader = document.querySelector('.page-loader');
    const progressBar = document.querySelector('.loader-progress-bar');
    const percentageText = document.querySelector('.loader-percentage');

    if (loader) loader.classList.remove('hidden');
    if (progressBar) progressBar.style.width = '0%';
    if (percentageText) percentageText.textContent = '0%';
});
</script>