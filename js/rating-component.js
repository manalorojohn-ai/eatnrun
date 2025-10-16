class RatingComponent {
    constructor(containerId, itemId) {
        this.container = document.getElementById(containerId);
        this.itemId = itemId;
        this.userRating = 0;
        this.pollInterval = null;
        this.lastUpdate = 0;
        this.init();
    }

    async init() {
        this.render();
        await this.loadRatings();
        this.startPolling();
    }

    render() {
        this.container.innerHTML = `
            <div class="rating-component">
                <div class="rating-summary">
                    <div class="average-rating">
                        <span class="rating-number">0.0</span>
                        <div class="rating-stars"></div>
                        <span class="total-ratings">(0 ratings)</span>
                    </div>
                    <div class="rating-bars"></div>
                </div>
                <div class="user-rating">
                    <h4>Rate this item</h4>
                    <div class="star-rating">
                        ${Array(5).fill(0).map((_, i) => `
                            <i class="far fa-star" data-rating="${i + 1}"></i>
                        `).join('')}
                    </div>
                    <textarea class="review-text" placeholder="Write your review (optional)"></textarea>
                    <button class="submit-rating">Submit Rating</button>
                </div>
                <div class="recent-reviews"></div>
            </div>
        `;

        // Add event listeners
        const stars = this.container.querySelectorAll('.star-rating i');
        stars.forEach(star => {
            star.addEventListener('mouseover', () => this.highlightStars(star.dataset.rating));
            star.addEventListener('mouseout', () => this.highlightStars(this.userRating));
            star.addEventListener('click', () => this.setRating(star.dataset.rating));
        });

        this.container.querySelector('.submit-rating').addEventListener('click', () => this.submitRating());

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .rating-component {
                padding: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }

            .rating-summary {
                display: flex;
                gap: 40px;
                margin-bottom: 30px;
            }

            .average-rating {
                text-align: center;
            }

            .rating-number {
                font-size: 48px;
                font-weight: bold;
                color: #333;
            }

            .rating-stars {
                color: #ffc107;
                font-size: 24px;
                margin: 10px 0;
            }

            .total-ratings {
                color: #666;
            }

            .rating-bars {
                flex-grow: 1;
            }

            .rating-bar {
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 5px 0;
            }

            .bar-label {
                min-width: 60px;
            }

            .bar-fill {
                height: 8px;
                background: #eee;
                border-radius: 4px;
                flex-grow: 1;
            }

            .bar-value {
                background: #ffc107;
                height: 100%;
                border-radius: 4px;
                transition: width 0.3s ease;
            }

            .bar-count {
                min-width: 40px;
                text-align: right;
                color: #666;
            }

            .user-rating {
                margin: 20px 0;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
            }

            .star-rating {
                font-size: 24px;
                color: #ffc107;
                cursor: pointer;
                margin: 10px 0;
            }

            .star-rating i {
                margin-right: 5px;
                transition: transform 0.2s ease;
            }

            .star-rating i:hover {
                transform: scale(1.2);
            }

            .review-text {
                width: 100%;
                height: 100px;
                margin: 10px 0;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                resize: vertical;
            }

            .submit-rating {
                background: #006C3B;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }

            .submit-rating:hover {
                background: #005530;
            }

            .recent-reviews {
                margin-top: 30px;
            }

            .review-item {
                display: flex;
                gap: 15px;
                padding: 15px 0;
                border-bottom: 1px solid #eee;
            }

            .review-item:last-child {
                border-bottom: none;
            }

            .review-user-image {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                object-fit: cover;
            }

            .review-content {
                flex-grow: 1;
            }

            .review-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 5px;
            }

            .review-username {
                font-weight: 500;
            }

            .review-date {
                color: #666;
                font-size: 0.9em;
            }

            .review-stars {
                color: #ffc107;
                margin-bottom: 5px;
            }

            .review-text {
                color: #333;
            }
        `;
        document.head.appendChild(style);
    }

    async loadRatings() {
        try {
            const response = await fetch(`../get_ratings.php?item_id=${this.itemId}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateUI(data.data);
                this.lastUpdate = Date.now();
            }
        } catch (error) {
            console.error('Error loading ratings:', error);
        }
    }

    updateUI(data) {
        const { stats, recent_reviews, user_rating } = data;
        
        // Update average rating
        const avgRating = parseFloat(stats.average_rating || 0).toFixed(1);
        this.container.querySelector('.rating-number').textContent = avgRating;
        this.container.querySelector('.total-ratings').textContent = 
            `(${stats.total_ratings} rating${stats.total_ratings !== 1 ? 's' : ''})`;
        
        // Update rating stars
        this.container.querySelector('.rating-stars').innerHTML = 
            this.getStarHTML(avgRating);
        
        // Update rating bars
        const barsHTML = [5,4,3,2,1].map(stars => {
            const count = stats[`${this.numberToWord(stars)}_star`];
            const percentage = stats.total_ratings ? (count / stats.total_ratings * 100) : 0;
            return `
                <div class="rating-bar">
                    <span class="bar-label">${stars} stars</span>
                    <div class="bar-fill">
                        <div class="bar-value" style="width: ${percentage}%"></div>
                    </div>
                    <span class="bar-count">${count}</span>
                </div>
            `;
        }).join('');
        this.container.querySelector('.rating-bars').innerHTML = barsHTML;
        
        // Update user rating if available
        if (user_rating) {
            this.userRating = user_rating.rating;
            this.highlightStars(this.userRating);
            this.container.querySelector('.review-text').value = user_rating.review || '';
        }
        
        // Update recent reviews
        const reviewsHTML = recent_reviews.map(review => `
            <div class="review-item">
                <img src="${review.profile_image || 'assets/images/default-avatar.png'}" 
                     alt="${review.username}" 
                     class="review-user-image">
                <div class="review-content">
                    <div class="review-header">
                        <span class="review-username">${review.username}</span>
                        <span class="review-date">${this.formatDate(review.created_at)}</span>
                    </div>
                    <div class="review-stars">${this.getStarHTML(review.rating)}</div>
                    ${review.review ? `<div class="review-text">${review.review}</div>` : ''}
                </div>
            </div>
        `).join('');
        this.container.querySelector('.recent-reviews').innerHTML = reviewsHTML;
    }

    highlightStars(rating) {
        const stars = this.container.querySelectorAll('.star-rating i');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('far');
                star.classList.add('fas');
            } else {
                star.classList.remove('fas');
                star.classList.add('far');
            }
        });
    }

    setRating(rating) {
        this.userRating = parseInt(rating);
        this.highlightStars(this.userRating);
    }

    async submitRating() {
        if (!this.userRating) {
            alert('Please select a rating');
            return;
        }

        try {
            const review = this.container.querySelector('.review-text').value;
            const response = await fetch('../submit_rating.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    item_id: this.itemId,
                    rating: this.userRating,
                    review: review
                })
            });

            const data = await response.json();
            if (data.success) {
                this.updateUI(data.data);
                alert('Rating submitted successfully!');
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Error submitting rating:', error);
            alert('Failed to submit rating. Please try again.');
        }
    }

    startPolling() {
        // Check for updates every 30 seconds
        this.pollInterval = setInterval(async () => {
            await this.loadRatings();
        }, 30000);

        // Stop polling when page is hidden
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(this.pollInterval);
            } else {
                this.startPolling();
            }
        });
    }

    getStarHTML(rating) {
        return Array(5).fill(0).map((_, i) => 
            `<i class="${i < rating ? 'fas' : 'far'} fa-star"></i>`
        ).join('');
    }

    numberToWord(num) {
        const words = ['zero', 'one', 'two', 'three', 'four', 'five'];
        return words[num];
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return `${Math.floor(diff/60000)} minutes ago`;
        if (diff < 86400000) return `${Math.floor(diff/3600000)} hours ago`;
        if (diff < 2592000000) return `${Math.floor(diff/86400000)} days ago`;
        
        return date.toLocaleDateString();
    }
} 