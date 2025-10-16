document.addEventListener('DOMContentLoaded', () => {
    // Find all rating containers on the page
    const ratingContainers = document.querySelectorAll('[data-rating-container]');
    
    ratingContainers.forEach(container => {
        const itemId = container.dataset.itemId;
        if (itemId) {
            // Initialize rating component for each container
            new RatingComponent(container.id, itemId);
        }
    });
}); 