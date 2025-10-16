// NotificationsHandler class
class NotificationsHandler {
    constructor() {
        this.notifications = [];
    }

    initialize() {
        // Initialize notifications functionality
        console.log('Notifications handler initialized');
    }
}

// Cancel Modal functionality
function openCancelModal(event, button) {
    event.preventDefault();
    const orderId = button.getAttribute('data-order-id');
    
    // Get the modal
    const modal = document.getElementById('cancelOrderModal');
    const orderIdInput = document.getElementById('cancelOrderId');
    
    // Set the order ID in the hidden input
    if (orderIdInput) {
        orderIdInput.value = orderId;
    }
    
    // Show the modal
    if (modal) {
        modal.style.display = 'block';
        
        // Handle radio button change for "Other" reason
        const radioButtons = modal.querySelectorAll('input[type="radio"]');
        const otherReasonTextarea = document.getElementById('otherReason');
        
        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'Other') {
                    otherReasonTextarea.style.display = 'block';
                    otherReasonTextarea.required = true;
                } else {
                    otherReasonTextarea.style.display = 'none';
                    otherReasonTextarea.required = false;
                }
            });
        });
        
        // Handle form submission
        const form = document.getElementById('cancelOrderForm');
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const selectedReason = document.querySelector('input[name="cancelReason"]:checked').value;
            const otherReasonText = document.getElementById('otherReason').value;
            const finalReason = selectedReason === 'Other' ? otherReasonText : selectedReason;
            
            try {
                const response = await fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        cancel_reason: finalReason
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Success', 'Order cancelled successfully', 'success');
                    modal.style.display = 'none';
                    
                    // Reload the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to cancel order');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error', error.message || 'Failed to cancel order', 'error');
            }
        });
        
        // Handle close button click
        const closeBtn = document.getElementById('cancelModalClose');
        if (closeBtn) {
            closeBtn.onclick = function() {
                modal.style.display = 'none';
            };
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    }
}

// Orders page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle cancel button clicks
    document.querySelectorAll('.btn-cancel').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to cancel this order?')) {
                return;
            }

            const orderId = this.getAttribute('data-order-id');
            if (!orderId) {
                showToast('Invalid order ID', 'error');
                return;
            }

            try {
                // Disable the button while processing
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';

                const response = await fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ order_id: orderId })
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Order cancelled successfully', 'success');
                    // Update the order status in the UI
                    const orderCard = this.closest('.order-card');
                    if (orderCard) {
                        // Update status indicator
                        const statusIndicator = orderCard.querySelector('.status-indicator');
                        if (statusIndicator) {
                            statusIndicator.textContent = 'Cancelled';
                            statusIndicator.className = 'status-indicator status-cancelled';
                        }

                        // Update payment status if it exists
                        const paymentStatus = orderCard.querySelector('.payment-status');
                        if (paymentStatus) {
                            paymentStatus.textContent = 'Cancelled';
                        }

                        // Hide the received button if it exists
                        const receivedBtn = orderCard.querySelector('.btn-received');
                        if (receivedBtn) {
                            receivedBtn.style.display = 'none';
                        }

                        // Update the cancel button
                        this.disabled = true;
                        this.innerHTML = '<i class="fas fa-times"></i> Cancelled';
                        this.classList.add('cancelled');
                    }

                    // Reload the page after a short delay to ensure everything is updated
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Re-enable the button if there's an error
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-times"></i> Cancel Order';
                    showToast(data.message || 'Failed to cancel order', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                // Re-enable the button if there's an error
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-times"></i> Cancel Order';
                showToast('An error occurred while cancelling the order', 'error');
            }
        });
    });

    // Handle received button clicks
    document.querySelectorAll('.btn-received').forEach(button => {
        button.addEventListener('click', function(e) {
            handlePaymentClick(e, this);
        });
    });

    // Toast notification function
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas ${getToastIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Remove existing toasts
        document.querySelectorAll('.toast').forEach(existingToast => {
            if (existingToast !== toast) {
                existingToast.remove();
            }
        });

        // Show new toast
        setTimeout(() => {
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }, 100);
    }

    // Helper function to get toast icon
    function getToastIcon(type) {
        switch (type) {
            case 'success':
                return 'fa-check-circle';
            case 'error':
                return 'fa-exclamation-circle';
            case 'warning':
                return 'fa-exclamation-triangle';
            default:
                return 'fa-info-circle';
        }
    }

    async function handlePaymentClick(e, button) {
        e.preventDefault();
        const orderId = button.dataset.orderId;
        const buttonGroup = button.closest('.button-group');
        
        try {
            // Disable both buttons
            const buttons = buttonGroup.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);
            
            // Add loading spinner to clicked button
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            const response = await fetch('update_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ order_id: orderId })
            });

            const data = await response.json();

            if (data.success) {
                // Update order status
                const orderCard = button.closest('.order-card');
                orderCard.querySelector('.status-indicator').textContent = 'Completed';
                
                // Remove the button group with animation
                buttonGroup.classList.add('removing');
                setTimeout(() => buttonGroup.remove(), 300);
                
                // Show rating modal
                showRatingModal(orderId);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            // Reset button state
            button.innerHTML = 'Mark as Received';
            button.disabled = false;
        }
    }

    async function showRatingModal(orderId) {
        try {
            const response = await fetch(`api/get_order_items.php?order_id=${orderId}`);
            const data = await response.json();
            
            if (data.success) {
                const modal = createRatingModal(orderId, data.items);
                document.body.appendChild(modal);
                setTimeout(() => modal.classList.add('show'), 50);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    function createRatingModal(orderId, items) {
        const modal = document.createElement('div');
        modal.className = 'rating-modal';
        
        const content = document.createElement('div');
        content.className = 'rating-content';
        
        content.innerHTML = `
            <h3>Rate Your Order</h3>
            <div class="rating-items">
                ${items.map(item => `
                    <div class="rating-item" data-item-id="${item.menu_item_id}">
                        <div class="item-name">${item.name}</div>
                        <div class="stars">
                            ${Array(5).fill(0).map((_, i) => `
                                <i class="star fas fa-star" data-rating="${i + 1}"></i>
                            `).join('')}
                        </div>
                        <textarea class="rating-comment" placeholder="Add a comment (optional)"></textarea>
                    </div>
                `).join('')}
            </div>
            <div class="rating-actions">
                <button class="btn-skip-rating">Skip</button>
                <button class="btn-submit-rating">Submit Ratings</button>
            </div>
        `;
        
        // Add event listeners for stars
        content.querySelectorAll('.rating-item').forEach(item => {
            const stars = item.querySelectorAll('.star');
            stars.forEach(star => {
                star.addEventListener('click', () => {
                    const rating = parseInt(star.dataset.rating);
                    stars.forEach(s => {
                        s.classList.toggle('active', parseInt(s.dataset.rating) <= rating);
                    });
                });
            });
        });
        
        // Add event listeners for buttons
        content.querySelector('.btn-skip-rating').addEventListener('click', () => {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        });
        
        content.querySelector('.btn-submit-rating').addEventListener('click', () => {
            submitRatings(orderId, modal);
        });
        
        modal.appendChild(content);
        return modal;
    }

    async function submitRatings(orderId, modal) {
        const ratings = [];
        modal.querySelectorAll('.rating-item').forEach(item => {
            const itemId = item.dataset.itemId;
            const activeStars = item.querySelectorAll('.star.active').length;
            const comment = item.querySelector('.rating-comment').value.trim();
            
            if (activeStars > 0) {
                ratings.push({
                    item_id: itemId,
                    rating: activeStars,
                    comment: comment
                });
            }
        });
        
        if (ratings.length === 0) {
            alert('Please rate at least one item before submitting.');
            return;
        }
        
        try {
            const response = await fetch('api/submit_rating.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    ratings: ratings
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to submit ratings. Please try again.');
        }
    }

    // Function to handle order received confirmation
    function confirmOrderReceived(orderId) {
        if (!confirm('Have you received this order? This action cannot be undone.')) {
            return;
        }

        // Show loading state
        const button = document.querySelector(`button[data-order-id="${orderId}"]`);
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

        // Send AJAX request to update order status
        fetch('update_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the status display
                const statusCell = document.querySelector(`#order-${orderId} .order-status`);
                const paymentStatusCell = document.querySelector(`#order-${orderId} .payment-status`);
                
                if (statusCell) {
                    statusCell.innerHTML = '<span class="badge bg-success">Completed</span>';
                }
                if (paymentStatusCell) {
                    paymentStatusCell.innerHTML = '<span class="badge bg-success">Paid</span>';
                }
                
                // Remove the button
                button.remove();
                
                // Show success message
                showToast('Success', 'Order marked as received successfully', 'success');
            } else {
                // Show error message
                showToast('Error', data.message, 'error');
                
                // Reset button state
                button.disabled = false;
                button.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error', 'Failed to update order status. Please try again.', 'error');
            
            // Reset button state
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }

    // Initialize notification handler
    const notificationsHandler = new NotificationsHandler();
    notificationsHandler.initialize();

    async function cancelOrder(orderId) {
        if (!confirm('Are you sure you want to cancel this order?')) {
            return;
        }

        const button = document.querySelector(`.cancel-btn[data-order-id="${orderId}"]`);
        if (!button) return;

        // Disable button and show loading state
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';

        try {
            const response = await fetch('cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ order_id: orderId })
            });

            const data = await response.json();

            if (data.success) {
                // Update the order status in the UI
                const orderCard = button.closest('.order-card');
                if (orderCard) {
                    // Update status indicator
                    const statusIndicator = orderCard.querySelector('.status-indicator');
                    if (statusIndicator) {
                        statusIndicator.className = 'status-indicator status-cancelled';
                        statusIndicator.textContent = 'Cancelled';
                    }

                    // Hide the received button if it exists
                    const receivedBtn = orderCard.querySelector('.receive-btn');
                    if (receivedBtn) {
                        receivedBtn.style.display = 'none';
                    }

                    // Update the cancel button
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-times-circle"></i> Cancelled';
                    button.classList.add('cancelled');
                }

                showToast('Order cancelled successfully', 'success');

                // Reload the page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                throw new Error(data.message || 'Failed to cancel order');
            }
        } catch (error) {
            console.error('Error:', error);
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-times-circle"></i> Cancel Order';
            showToast(error.message || 'Failed to cancel order', 'error');
        }
    }

    // Initialize the cancel order functionality
    initializeCancelOrder();

    // Toast notification function
    function showToast(title, message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    // Helper function to get toast icon
    function getToastIcon(type) {
        switch (type) {
            case 'success':
                return 'fa-check-circle';
            case 'error':
                return 'fa-exclamation-circle';
            case 'warning':
                return 'fa-exclamation-triangle';
            default:
                return 'fa-info-circle';
        }
    }

    // Make functions available globally
    window.openCancelModal = openCancelModal;
    window.showToast = showToast;
}); 