class ReviewManager {
    constructor() {
        this.reviewForms = document.querySelectorAll('.review-form');
        this.ratingInputs = document.querySelectorAll('.rating-input');
        this.reviewQueue = [];
        this.isSubmitting = false;

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeStarRatings();
    }

    setupEventListeners() {
        this.reviewForms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleReviewSubmit(e));
        });

        // Handle star rating clicks
        document.querySelectorAll('.star-rating').forEach(container => {
            container.addEventListener('click', (e) => this.handleStarClick(e));
        });
    }

    initializeStarRatings() {
        document.querySelectorAll('.star-rating').forEach(container => {
            const input = container.querySelector('.rating-input');
            const stars = container.querySelectorAll('.star');
            const rating = parseInt(input.value) || 0;
            
            this.updateStars(stars, rating);
        });
    }

    handleStarClick(e) {
        if (!e.target.classList.contains('star')) return;
        
        const container = e.target.closest('.star-rating');
        const stars = container.querySelectorAll('.star');
        const input = container.querySelector('.rating-input');
        const rating = parseInt(e.target.dataset.value);
        
        input.value = rating;
        this.updateStars(stars, rating);
    }

    updateStars(stars, rating) {
        stars.forEach((star, index) => {
            star.classList.toggle('active', index < rating);
        });
    }

    async handleReviewSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const menuItemId = form.querySelector('[name="menu_item_id"]').value;
        const rating = form.querySelector('.rating-input').value;
        const comment = form.querySelector('[name="comment"]').value;

        if (!rating) {
            this.showToast('Please select a rating', 'error');
            return;
        }

        const review = { menu_item_id: menuItemId, rating, comment };
        this.reviewQueue.push(review);
        
        form.reset();
        form.querySelector('.star-rating').querySelectorAll('.star')
            .forEach(star => star.classList.remove('active'));
        
        this.showToast('Review added to queue', 'success');
        
        if (!this.isSubmitting) {
            this.processReviewQueue();
        }
    }

    async processReviewQueue() {
        if (this.reviewQueue.length === 0) return;
        
        this.isSubmitting = true;
        
        try {
            const response = await fetch('submit_reviews.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ reviews: this.reviewQueue })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast('Reviews submitted successfully', 'success');
                this.reviewQueue = [];
                // Refresh average ratings on the page
                this.updateAverageRatings(data.updated_items);
            } else {
                throw new Error(data.message || 'Failed to submit reviews');
            }
        } catch (error) {
            this.showToast(error.message, 'error');
        } finally {
            this.isSubmitting = false;
            
            // If there are more reviews in the queue, process them
            if (this.reviewQueue.length > 0) {
                setTimeout(() => this.processReviewQueue(), 1000);
            }
        }
    }

    updateAverageRatings(updatedItems) {
        updatedItems.forEach(item => {
            const ratingDisplay = document.querySelector(`#rating-${item.id}`);
            if (ratingDisplay) {
                ratingDisplay.textContent = parseFloat(item.average_rating).toFixed(1);
                
                const totalReviews = document.querySelector(`#total-reviews-${item.id}`);
                if (totalReviews) {
                    totalReviews.textContent = item.total_reviews;
                }
            }
        });
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }, 100);
    }
}

// Initialize the review manager when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ReviewManager();
}); 