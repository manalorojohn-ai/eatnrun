class OrderReview {
    constructor() {
        this.modal = null;
        this.currentItems = [];
        this.init();
    }

    init() {
        // Create modal HTML
        this.createModal();
        
        // Add event listeners for all possible button classes
        document.addEventListener('click', (e) => {
            // Check if the click is on a button or a child element of a button
            const button = e.target.closest('.received-btn') || e.target.closest('.receive-btn');
            if (button) {
                e.preventDefault();
                this.handleReceivedClick(button);
            }
        });
    }

    createModal() {
        const modalHtml = `
            <div id="reviewModal" class="review-modal">
                <div class="review-modal-content">
                    <div class="review-modal-header">
                        <h2>Review Your Order</h2>
                        <button class="close-modal">&times;</button>
                    </div>
                    <div class="review-modal-body">
                        <p>Please rate the items from your order:</p>
                        <div id="reviewItems" class="review-items"></div>
                    </div>
                    <div class="review-modal-footer">
                        <button class="skip-review">Skip Review</button>
                        <button class="submit-reviews">Submit Reviews</button>
                    </div>
                </div>
            </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        this.modal = document.getElementById('reviewModal');

        // Add event listeners for modal
        this.modal.querySelector('.close-modal').addEventListener('click', () => this.closeModal());
        this.modal.querySelector('.skip-review').addEventListener('click', () => this.closeModal());
        this.modal.querySelector('.submit-reviews').addEventListener('click', () => this.submitReviews());

        // Add modal styles
        const styleSheet = document.createElement('style');
        styleSheet.textContent = `
            .review-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1000;
            }

            .review-modal-content {
                position: relative;
                background-color: #fff;
                margin: 15vh auto;
                padding: 20px;
                width: 90%;
                max-width: 600px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            .review-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .review-modal-header h2 {
                margin: 0;
                color: #006C3B;
            }

            .close-modal {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
            }

            .review-items {
                max-height: 400px;
                overflow-y: auto;
            }

            .review-item {
                padding: 15px;
                border-bottom: 1px solid #eee;
            }

            .review-item:last-child {
                border-bottom: none;
            }

            .item-name {
                font-weight: 600;
                margin-bottom: 10px;
            }

            .star-rating {
                display: flex;
                gap: 5px;
                margin-bottom: 10px;
            }

            .star-rating button {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #ddd;
                transition: color 0.2s;
            }

            .star-rating button.active {
                color: #FFD700;
            }

            .review-textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                resize: vertical;
                min-height: 60px;
            }

            .review-modal-footer {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 20px;
            }

            .review-modal-footer button {
                padding: 8px 16px;
                border-radius: 4px;
                cursor: pointer;
                border: none;
            }

            .skip-review {
                background-color: #f0f0f0;
                color: #666;
            }

            .submit-reviews {
                background-color: #006C3B;
                color: white;
            }

            .submit-reviews:hover {
                background-color: #005530;
            }
        `;
        document.head.appendChild(styleSheet);
    }

    async handleReceivedClick(button) {
        if (!button) return;
        
        const orderId = button.dataset.orderId;
        if (!orderId) {
            this.showToast('Invalid order ID', 'error');
            return;
        }
        
        // Disable button and show loading state
        button.disabled = true;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        try {
            const response = await fetch('update_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ order_id: orderId })
            });

            if (!response.ok) {
                throw new Error('Server returned an error');
            }

            const data = await response.json();
            
            if (data.success) {
                // Find the payment status element
                const orderCard = button.closest('.order-card');
                const paymentStatus = orderCard ? orderCard.querySelector('.payment-status') : null;
                
                // Update payment status if found
                if (paymentStatus) {
                    paymentStatus.innerHTML = '<i class="fas fa-check-circle"></i> Paid';
                    paymentStatus.className = 'payment-status paid';
                }
                
                // Remove the button with fade out animation
                button.style.transition = 'opacity 0.3s ease';
                button.style.opacity = '0';
                setTimeout(() => {
                    if (button.parentElement) {
                        button.parentElement.removeChild(button);
                    }
                }, 300);

                // Show success message
                this.showToast(data.message || 'Payment marked as received successfully', 'success');
            } else {
                throw new Error(data.error || 'Failed to update payment status');
            }
        } catch (error) {
            console.error('Error:', error);
            
            // Revert button state
            button.disabled = false;
            button.innerHTML = originalText;
            
            // Show error message
            this.showToast(error.message || 'An error occurred while updating payment status', 'error');
        }
    }

    showReviewModal(items) {
        this.currentItems = items;
        const reviewItems = document.getElementById('reviewItems');
        reviewItems.innerHTML = items.map((item, index) => `
            <div class="review-item" data-item-id="${item.menu_item_id}">
                <div class="item-name">${item.name} (Qty: ${item.quantity})</div>
                <div class="star-rating" data-index="${index}">
                    ${[1, 2, 3, 4, 5].map(star => `
                        <button type="button" data-star="${star}">★</button>
                    `).join('')}
                </div>
                <textarea class="review-textarea" placeholder="Write your review (optional)"></textarea>
            </div>
        `).join('');

        // Add event listeners for star ratings
        reviewItems.querySelectorAll('.star-rating button').forEach(button => {
            button.addEventListener('click', (e) => this.handleStarClick(e));
        });

        this.modal.style.display = 'block';
    }

    handleStarClick(e) {
        const starButton = e.target;
        const rating = parseInt(starButton.dataset.star);
        const starRating = starButton.parentElement;
        const stars = starRating.querySelectorAll('button');
        
        stars.forEach(star => {
            const starValue = parseInt(star.dataset.star);
            star.classList.toggle('active', starValue <= rating);
        });
    }

    async submitReviews() {
        const reviews = [];
        this.modal.querySelectorAll('.review-item').forEach(item => {
            const itemId = item.dataset.itemId;
            const rating = item.querySelector('.star-rating button.active:last-child')?.dataset.star || 0;
            const comment = item.querySelector('.review-textarea').value.trim();
            
            if (rating > 0) {
                reviews.push({
                    menu_item_id: itemId,
                    rating: rating,
                    comment: comment
                });
            }
        });

        if (reviews.length > 0) {
            try {
                const response = await fetch('submit_reviews.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ reviews: reviews })
                });

                const data = await response.json();
                
                if (data.success) {
                    this.showToast('Thank you for your reviews!', 'success');
                } else {
                    this.showToast(data.error || 'Failed to submit reviews', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showToast('An error occurred while submitting reviews', 'error');
            }
        }

        this.closeModal();
    }

    closeModal() {
        this.modal.style.display = 'none';
        this.currentItems = [];
    }

    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize the OrderReview class when the document is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.orderReview = new OrderReview();
}); 