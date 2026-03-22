// Cart functionality
let isProcessing = false;

function addToCart(itemId, quantity = 1) {
    if (isProcessing) {
        console.log('Request already in progress');
        return;
    }

    isProcessing = true;
    const button = document.querySelector(`button[data-item-id="${itemId}"]`);
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    }

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_id: itemId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            updateCartCounter(data.cartCount);
            
            // Show success message
            showNotification(data.item.name + ' added to cart', 'success');
            
            // If redirect URL is provided, redirect after showing message
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            }
        } else {
            // Show error message
            showNotification(data.message || 'Failed to add item to cart', 'error');
            
            // Handle login redirect
            if (data.redirect === 'login.php') {
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        isProcessing = false;
        if (button) {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
        }
    });
}

// Toast notification system
function showNotification(message, type = 'success') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification-toast');
    existingNotifications.forEach(notification => notification.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-toast ${type}`;
    
    // Determine icon and title based on type
    const icon = type === 'success' ? 'fa-shopping-cart' : 'fa-exclamation-circle';
    const title = type === 'success' ? 'Added to Cart' : 'Error';
    
    notification.innerHTML = `
        <i class="fas ${icon}"></i>
        <div class="notification-content">
            <div class="notification-title">${title}</div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
        <div class="notification-progress"></div>
    `;

    // Add close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 500);
        });
    }

    // Add to document
    document.body.appendChild(notification);

    // Trigger animation
    setTimeout(() => notification.classList.add('show'), 10);

    // Remove after delay
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 500);
        }
    }, 5000);
}

// Update cart counter
function updateCartCounter(count) {
    const cartLink = document.querySelector('a[href="cart.php"]');
    if (!cartLink) return;

    let cartCounter = cartLink.querySelector('.cart-counter');
    if (count > 0) {
        if (!cartCounter) {
            cartCounter = document.createElement('span');
            cartCounter.className = 'cart-counter';
            cartLink.appendChild(cartCounter);
        }
        cartCounter.textContent = count;
        cartCounter.style.display = 'flex';
        
        // Add animation class
        cartCounter.classList.remove('scaleIn');
        void cartCounter.offsetWidth; // Force reflow
        cartCounter.classList.add('scaleIn');
    } else if (cartCounter) {
        cartCounter.style.display = 'none';
    }
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for add to cart buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.dataset.itemId;
            const itemName = this.dataset.itemName;
            if (itemId) {
                addToCart(itemId);
            }
        });
    });

    // Add styles for notifications if not already present
    if (!document.getElementById('notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification-toast {
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #e8f3ff 0%, #ffffff 100%);
                color: #475569;
                padding: 16px 20px;
                border-radius: 16px;
                display: flex;
                align-items: center;
                gap: 16px;
                transform: translateX(150%);
                transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
                z-index: 10000;
                min-width: 320px;
                max-width: 400px;
                box-shadow: 0 8px 32px rgba(156, 163, 175, 0.08);
                border: 1px solid rgba(226, 232, 240, 0.6);
                backdrop-filter: blur(8px);
            }

            .notification-toast.error {
                background: linear-gradient(135deg, #fff5f6 0%, #fff8f8 100%);
                border-left: 3px solid #fecaca;
            }

            .notification-toast.success {
                background: linear-gradient(135deg, #f0fdf4 0%, #f7fee7 100%);
                border-left: 3px solid #bbf7d0;
            }

            .notification-toast.show {
                transform: translateX(0);
                animation: gentle-float 4s ease-in-out infinite;
            }

            @keyframes gentle-float {
                0% { transform: translateY(0px); }
                50% { transform: translateY(-4px); }
                100% { transform: translateY(0px); }
            }

            .notification-toast i {
                font-size: 18px;
                border-radius: 12px;
                padding: 12px;
                width: 18px;
                height: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.8);
            }

            .notification-toast.error i {
                color: #f87171;
                background: rgba(254, 226, 226, 0.5);
            }

            .notification-toast.success i {
                color: #34d399;
                background: rgba(209, 250, 229, 0.5);
            }

            .notification-content {
                flex: 1;
            }

            .notification-title {
                font-weight: 600;
                font-size: 1rem;
                margin-bottom: 4px;
                color: #475569;
            }

            .notification-message {
                font-size: 0.95rem;
                color: #64748b;
                line-height: 1.5;
                letter-spacing: 0.01em;
            }

            .notification-close {
                padding: 6px;
                background: rgba(255, 255, 255, 0.8);
                border: none;
                color: #94a3b8;
                cursor: pointer;
                border-radius: 10px;
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                opacity: 0.7;
            }

            .notification-close:hover {
                background: rgba(255, 255, 255, 0.95);
                color: #64748b;
                opacity: 1;
            }

            .notification-progress {
                position: absolute;
                left: 0;
                bottom: 0;
                width: 100%;
                height: 3px;
                background: rgba(203, 213, 225, 0.3);
                border-radius: 0 0 16px 16px;
                overflow: hidden;
            }

            .notification-progress::after {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background: rgba(148, 163, 184, 0.3);
                transform: translateX(-100%);
                animation: progress 5s linear forwards;
            }

            @keyframes progress {
                to { transform: translateX(0); }
            }

            @media (max-width: 768px) {
                .notification-toast {
                    width: calc(100% - 32px);
                    max-width: none;
                    bottom: 20px;
                    top: auto;
                    left: 16px;
                    right: 16px;
                    border-radius: 12px;
                    padding: 14px 18px;
                }
            }
        `;
        document.head.appendChild(styles);
    }
}); 