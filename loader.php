<!-- Ultra Smooth Elegant Loader Component -->
<style>
.page-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.98);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.page-loader.hidden {
    opacity: 0;
    visibility: hidden;
    transform: scale(1.1);
}

.loader-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 25px;
    transform: translateY(0);
    transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.page-loader.hidden .loader-content {
    transform: translateY(-20px) scale(0.95);
}

.loader-brand {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 15px;
    position: relative;
}

.loader-logo-container {
    width: 70px;
    height: 70px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loader-logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
    position: relative;
    z-index: 2;
    animation: pulse 2s infinite cubic-bezier(0.45, 0, 0.55, 1);
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
}

.loader-ring {
    position: absolute;
    width: 70px;
    height: 70px;
    border-radius: 50%;
    border: 3px solid transparent;
    border-top-color: var(--primary-color, #2ECC71);
    border-left-color: var(--primary-color, #2ECC71);
    animation: spinner 2s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
}

.loader-ring:nth-child(2) {
    width: 60px;
    height: 60px;
    border-right-color: var(--primary-color, #2ECC71);
    border-top-color: transparent;
    animation: spinner 1.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite reverse;
}

.loader-brand-text {
    font-size: 28px;
    font-weight: 700;
    color: var(--primary-color, #2ECC71);
    letter-spacing: -0.5px;
    opacity: 0;
    animation: fadeInScale 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards 0.3s;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.loader-progress-container {
    width: 280px;
    height: 6px;
    background-color: rgba(46, 204, 113, 0.1);
    border-radius: 100px;
    overflow: hidden;
    position: relative;
    margin-bottom: 15px;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
}

.loader-progress-bar {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #2ECC71, #27AE60);
    border-radius: 100px;
    animation: progress 3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    background-size: 200% 100%;
    background-position: 0 0;
}

.loader-progress-bar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, 
                               transparent, 
                               rgba(255, 255, 255, 0.6), 
                               transparent);
    transform: translateX(-100%);
    animation: shimmer 1.5s infinite;
}

.loader-text {
    font-size: 15px;
    color: #555;
    text-align: center;
    opacity: 0;
    animation: fadeInUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards 0.5s;
    font-weight: 500;
}

.loader-percentage {
    font-size: 16px;
    font-weight: 600;
    color: var(--primary-color, #2ECC71);
    margin-bottom: 5px;
    opacity: 0;
    animation: fadeInScale 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards 0.4s;
}

@keyframes spinner {
    to {
        transform: rotate(360deg);
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(0.95);
        opacity: 0.8;
    }
    50% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}

@keyframes progress {
    0% {
        width: 0%;
        background-position: 0 0;
    }
    25% {
        width: 40%;
    }
    50% {
        width: 60%;
        background-position: 100% 0;
    }
    75% {
        width: 85%;
    }
    100% {
        width: 100%;
        background-position: 0 0;
    }
}

/* Add particle effects */
.particles {
    position: absolute;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.particle {
    position: absolute;
    width: 6px;
    height: 6px;
    background: var(--primary-color, #2ECC71);
    border-radius: 50%;
    opacity: 0;
    animation: particleFloat 3s infinite ease-in-out;
}

@keyframes particleFloat {
    0% {
        transform: translateY(0) scale(1);
        opacity: 0;
    }
    50% {
        opacity: 0.5;
    }
    100% {
        transform: translateY(-100px) scale(0);
        opacity: 0;
    }
}
</style>

<!-- Enhanced Loader HTML Structure -->
<div class="page-loader">
    <div class="particles"></div>
    <div class="loader-content">
        <div class="loader-brand">
            <div class="loader-logo-container">
                <div class="loader-ring"></div>
                <div class="loader-ring"></div>
                <img src="assets/images/logo.png" alt="Eat&Run" class="loader-logo">
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

<!-- Enhanced Loader Script -->
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
    for (let i = 0; i < 20; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 2 + 's';
        particles.appendChild(particle);
    }
    
    let progress = 0;
    let interval = setInterval(function() {
        if (progress < 30) {
            progress += 0.8;
        } else if (progress < 60) {
            progress += 0.6;
        } else if (progress < 80) {
            progress += 0.4;
        } else if (progress < 98) {
            progress += 0.2;
        } else {
            progress = 99;
        }
        
        percentageText.textContent = Math.floor(progress) + '%';
        
        const messageIndex = Math.floor((progress / 100) * loadingMessages.length);
        loadingText.textContent = loadingMessages[Math.min(messageIndex, loadingMessages.length - 1)];
        
        if (progress >= 100) {
            clearInterval(interval);
        }
    }, 30);
});

window.addEventListener('load', function() {
    document.querySelector('.loader-percentage').textContent = '100%';
    document.querySelector('.loader-progress-bar').style.width = '100%';
    
    setTimeout(function() {
        document.querySelector('.page-loader').classList.add('hidden');
    }, 600);
});

window.addEventListener('beforeunload', function() {
    document.querySelector('.page-loader').classList.remove('hidden');
    document.querySelector('.loader-progress-bar').style.width = '0%';
    document.querySelector('.loader-percentage').textContent = '0%';
});
</script> 