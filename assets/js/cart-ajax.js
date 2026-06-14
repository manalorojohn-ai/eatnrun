/**
 * Enhanced Cart AJAX Functionality
 * Handles all cart operations with AJAX
 */

let isProcessing = false;

/**
 * Add item to cart via AJAX
 */
async function addToCart(itemId, quantity = 1) {
    if (isProcessing) {
        console.log('Request already in progress');
        return;
    }

    isProcessing = true;
    loading.show();

    const button = document.querySelector(`button[data-item-id="${itemId}"]`);
    const originalHTML = button?.innerHTML;

    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    }

    try {
        const result = await ajax.request('actions/cart/add_to_cart.php', {
            method: 'POST',
            data: {
                item_id: itemId,
                quantity: quantity
            },
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (result.success) {
            // Update cart counter
            updateCartCounter(result.cartCount);
            
            // Show success notification
            await notify.success(`${result.message || 'Item added to cart'}`, 3000);
            
            // Update any cart display
            if (result.cartCount > 0) {
                updateCartDisplay();
            }
        } else {
            notify.error(result.message || 'Failed to add item to cart');
            
            if (result.message && result.message.includes('login')) {
                setTimeout(() => {
                    window.location.href = 'login';
                }, 2000);
            }
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        notify.error('An error occurred. Please try again.');
    } finally {
        isProcessing = false;
        loading.hide();

        if (button) {
            button.disabled = false;
            button.innerHTML = originalHTML || '<i class="fas fa-shopping-basket"></i> Add';
        }
    }
}

/**
 * Update cart item quantity via AJAX
 */
async function updateCartQuantity(cartId, newQuantity) {
    if (newQuantity < 1 || newQuantity > 99) return;

    const cartItem = document.querySelector(`.cart-item[data-id="${cartId}"]`);
    if (!cartItem) return;

    loading.show();

    try {
        const result = await ajax.request('actions/cart/update_cart.php', {
            method: 'POST',
            data: {
                cart_id: cartId,
                quantity: newQuantity
            }
        });

        if (result.success) {
            // Update cart count
            updateCartCounter(result.cartCount);
            
            // Recalculate totals
            recalculateCartTotals();
        } else {
            notify.error(result.message || 'Failed to update quantity');
            location.reload();
        }
    } catch (error) {
        console.error('Error updating cart:', error);
        notify.error('Error updating cart. Please try again.');
        location.reload();
    } finally {
        loading.hide();
    }
}

/**
 * Remove item from cart via AJAX
 */
async function removeFromCart(cartId) {
    if (!confirm('Are you sure you want to remove this item?')) return;

    loading.show();

    try {
        const result = await ajax.request('actions/cart/remove_from_cart.php', {
            method: 'POST',
            data: {
                cart_id: cartId
            }
        });

        if (result.success) {
            const cartItem = document.querySelector(`.cart-item[data-id="${cartId}"]`);
            if (cartItem) {
                // Animate removal
                cartItem.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => {
                    cartItem.remove();
                    
                    // Check if cart is empty
                    const remainingItems = document.querySelectorAll('.cart-item');
                    if (remainingItems.length === 0) {
                        location.reload();
                    } else {
                        recalculateCartTotals();
                    }
                    
                    updateCartCounter(result.cartCount);
                    notify.success('Item removed from cart', 2000);
                }, 300);
            }
        } else {
            notify.error(result.message || 'Failed to remove item');
        }
    } catch (error) {
        console.error('Error removing from cart:', error);
        notify.error('Error removing item. Please try again.');
    } finally {
        loading.hide();
    }
}

/**
 * Recalculate cart totals
 */
function recalculateCartTotals() {
    let newSubtotal = 0;
    
    document.querySelectorAll('.cart-item').forEach(item => {
        const qty = parseInt(item.querySelector('.quantity-input')?.value || 0);
        const price = parseFloat(item.dataset.price || 0);
        newSubtotal += qty * price;
    });

    const deliveryFee = 50;
    const total = newSubtotal + deliveryFee;

    const subtotalEl = document.getElementById('subtotal');
    const totalEl = document.getElementById('total');

    if (subtotalEl) subtotalEl.textContent = newSubtotal.toFixed(2);
    if (totalEl) totalEl.textContent = total.toFixed(2);
}

/**
 * Update cart counter in header
 */
function updateCartCounter(count) {
    const cartLink = document.querySelector('a[href*="cart"]');
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
        
        // Add scale animation
        cartCounter.style.animation = 'none';
        void cartCounter.offsetWidth; // Force reflow
        cartCounter.style.animation = 'scaleIn 0.3s ease-out';
    } else if (cartCounter) {
        cartCounter.style.display = 'none';
    }
}

/**
 * Update cart display (for cart page)
 */
async function updateCartDisplay() {
    try {
        const result = await ajax.request('actions/cart/get_cart_items.php', {
            method: 'GET',
            cache: false
        });

        if (result.success) {
            // Update count
            updateCartCounter(result.count);
            
            // Refresh totals
            recalculateCartTotals();
        }
    } catch (error) {
        console.error('Error updating cart display:', error);
    }
}

/**
 * Initialize cart page event listeners
 */
document.addEventListener('DOMContentLoaded', function() {
    // Add slide-out animation style
    if (!document.getElementById('cart-ajax-styles')) {
        const style = document.createElement('style');
        style.id = 'cart-ajax-styles';
        style.textContent = `
            @keyframes slideOut {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(-20px); }
            }

            @keyframes scaleIn {
                from { transform: scale(0.8); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }

            .cart-item {
                transition: all 0.3s ease;
            }
        `;
        document.head.appendChild(style);
    }

    // Attach quantity control listeners
    const quantityInputs = document.querySelectorAll('.quantity-input');
    const quantityButtons = document.querySelectorAll('.quantity-btn');
    const removeButtons = document.querySelectorAll('.remove-item');

    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const cartItem = this.closest('.cart-item');
            if (!cartItem) return;
            
            const cartId = parseInt(cartItem.dataset.id);
            let quantity = parseInt(this.value);

            // Validate quantity
            if (quantity < 1) {
                quantity = 1;
                this.value = 1;
            }
            if (quantity > 99) {
                quantity = 99;
                this.value = 99;
            }

            updateCartQuantity(cartId, quantity);
        });

        input.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                this.blur();
            }
        });
    });

    quantityButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const cartItem = this.closest('.cart-item');
            if (!cartItem) return;

            const input = cartItem.querySelector('.quantity-input');
            let quantity = parseInt(input.value);

            if (this.classList.contains('plus')) {
                if (quantity < 99) quantity++;
            } else if (this.classList.contains('minus')) {
                if (quantity > 1) quantity--;
            }

            input.value = quantity;
            const cartId = parseInt(cartItem.dataset.id);
            updateCartQuantity(cartId, quantity);
        });
    });

    removeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const cartItem = this.closest('.cart-item');
            if (!cartItem) return;

            const cartId = parseInt(cartItem.dataset.id);
            removeFromCart(cartId);
        });
    });

    // Load cart count on page load
    updateCartDisplay();
});
