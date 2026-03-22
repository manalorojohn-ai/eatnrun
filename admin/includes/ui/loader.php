<!-- Ultra Smooth Elegant Loader Component -->
<style>
.page-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.98);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: opacity 0.8s cubic-bezier(0.19, 1, 0.22, 1), visibility 0.8s;
    backdrop-filter: blur(5px);
}

.page-loader.hidden {
    opacity: 0;
    visibility: hidden;
}

.loader-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    transform: translateY(0);
    transition: transform 0.6s cubic-bezier(0.19, 1, 0.22, 1);
}

.page-loader.hidden .loader-content {
    transform: translateY(-20px);
}

.loader-brand {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
}

.loader-logo-container {
    width: 60px;
    height: 60px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loader-logo {
    width: 50px;
    height: 50px;
    object-fit: contain;
    position: relative;
    z-index: 2;
    animation: pulse 2s infinite cubic-bezier(0.45, 0, 0.55, 1);
}

.loader-ring {
    position: absolute;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3px solid transparent;
    border-top-color: var(--primary, #006C3B);
    border-left-color: var(--primary, #006C3B);
    animation: spinner 1.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
}

.loader-ring:nth-child(2) {
    width: 50px;
    height: 50px;
    border-right-color: var(--primary, #006C3B);
    border-top-color: transparent;
    animation: spinner 1.2s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite reverse;
}

.loader-brand-text {
    font-size: 24px;
    font-weight: 600;
    color: var(--primary, #006C3B);
    letter-spacing: 0.5px;
    opacity: 0;
    animation: fadeIn 0.8s cubic-bezier(0.19, 1, 0.22, 1) forwards 0.3s;
}

.loader-progress-container {
    width: 240px;
    height: 4px;
    background-color: rgba(0, 108, 59, 0.1);
    border-radius: 4px;
    overflow: hidden;
    position: relative;
    margin-bottom: 10px;
}

.loader-progress-bar {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #006C3B, #2ecc71);
    border-radius: 4px;
    animation: progress 3s cubic-bezier(0.19, 1, 0.22, 1) forwards;
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
                               rgba(255, 255, 255, 0.4), 
                               transparent);
    transform: translateX(-100%);
    animation: shimmer 2s infinite;
}

.loader-text {
    font-size: 14px;
    color: #666;
    text-align: center;
    opacity: 0;
    animation: fadeIn 0.8s cubic-bezier(0.19, 1, 0.22, 1) forwards 0.5s;
}

.loader-percentage {
    font-size: 14px;
    font-weight: 500;
    color: var(--primary, #006C3B);
    margin-bottom: 5px;
    opacity: 0;
    animation: fadeIn 0.6s cubic-bezier(0.19, 1, 0.22, 1) forwards 0.4s;
}

@keyframes spinner {
    to {
        transform: rotate(360deg);
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(0.9);
        opacity: 0.8;
    }
    50% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
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
    }
    15% {
        width: 23%;
    }
    25% {
        width: 40%;
    }
    35% {
        width: 45%;
    }
    45% {
        width: 55%;
    }
    60% {
        width: 70%;
    }
    75% {
        width: 85%;
    }
    85% {
        width: 92%;
    }
    100% {
        width: 100%;
    }
}
</style>

<!-- Elegant Loader HTML Structure -->
<div class="page-loader">
    <div class="loader-content">
        <div class="loader-brand">
            <div class="loader-logo-container">
                <div class="loader-ring"></div>
                <div class="loader-ring"></div>
                <img src="../assets/images/logo.png" alt="Eat&Run" class="loader-logo">
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
    // Simulate loading progress
    document.addEventListener('DOMContentLoaded', function() {
        const progressBar = document.querySelector('.loader-progress-bar');
        const percentageText = document.querySelector('.loader-percentage');
        const loadingText = document.querySelector('.loader-text');
        const loadingMessages = [
            "Loading your experience...",
            "Preparing dashboard...",
            "Fetching latest data...",
            "Almost ready...",
            "Finalizing..."
        ];
        
        let progress = 0;
        let interval = setInterval(function() {
            // Accelerate progress over time for realistic loading feel
            if (progress < 30) {
                progress += 1;
            } else if (progress < 60) {
                progress += 0.8;
            } else if (progress < 75) {
                progress += 0.5;
            } else if (progress < 90) {
                progress += 0.3;
            } else if (progress < 98) {
                progress += 0.1;
            } else {
                progress = 99; // Hold at 99% until fully loaded
            }
            
            // Update percentage display
            percentageText.textContent = Math.floor(progress) + '%';
            
            // Update loading text
            if (progress < 20) {
                loadingText.textContent = loadingMessages[0];
            } else if (progress < 40) {
                loadingText.textContent = loadingMessages[1];
            } else if (progress < 60) {
                loadingText.textContent = loadingMessages[2];
            } else if (progress < 80) {
                loadingText.textContent = loadingMessages[3];
            } else {
                loadingText.textContent = loadingMessages[4];
            }
            
            if (progress >= 100) {
                clearInterval(interval);
            }
        }, 50);
    });
    
    // Hide loader when page is fully loaded
    window.addEventListener('load', function() {
        // Update to 100% when actually loaded
        document.querySelector('.loader-percentage').textContent = '100%';
        document.querySelector('.loader-progress-bar').style.width = '100%';
        
        // Delay hiding to ensure progress bar reaches 100%
        setTimeout(function() {
            document.querySelector('.page-loader').classList.add('hidden');
        }, 800);
    });
    
    // Show loader before page unload (navigation)
    window.addEventListener('beforeunload', function() {
        document.querySelector('.page-loader').classList.remove('hidden');
        
        // Reset progress bar for next page load
        document.querySelector('.loader-progress-bar').style.width = '0%';
        document.querySelector('.loader-percentage').textContent = '0%';
    });
</script> 