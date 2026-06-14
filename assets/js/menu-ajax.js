/**
 * Menu AJAX Functionality
 * Handles menu filtering and search without page reload
 */

document.addEventListener('DOMContentLoaded', function() {
    const menuGrid = document.getElementById('menuGrid');
    const categoryChips = document.querySelectorAll('.category-chip');
    const menuSearchInput = document.getElementById('menuSearch');

    let currentCategory = null;
    let currentSearch = null;
    let debounceTimer = null;

    /**
     * Render menu items to grid
     */
    function renderMenuItems(items) {
        if (!menuGrid) return;

        if (items.length === 0) {
            menuGrid.innerHTML = `
                <div class="empty-state-zen" style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                    <i class="fas fa-cloud fa-3x mb-3 text-muted"></i>
                    <h3>No items found</h3>
                    <p>Try a different search or category.</p>
                </div>
            `;
            return;
        }

        menuGrid.innerHTML = items.map((item, index) => `
            <div class="card-aesthetic animate-in" style="animation-delay: ${index * 0.05}s" 
                 data-category="${escapeHtml(item.category_name)}" 
                 data-name="${escapeHtml(item.name.toLowerCase())}" 
                 data-image="${escapeHtml(item.image_path)}" 
                 data-description="${escapeHtml(item.description)}" 
                 data-price="${escapeHtml(item.price)}" 
                 data-item-id="${item.id}">
                <div class="card-image-wrapper">
                    <img src="${escapeHtml(item.image_path)}" 
                         alt="${escapeHtml(item.name)}"
                         class="food-img"
                         loading="lazy">
                    <span class="badge-custom">${escapeHtml(item.category_name)}</span>
                </div>
                <div class="card-body-aesthetic">
                    <div class="card-meta">
                        <span class="rating"><i class="fas fa-star text-warning"></i> 4.9</span>
                        <span class="delivery-time"><i class="fas fa-clock text-muted"></i> 25-35 min</span>
                    </div>
                    <h3 class="food-name">${escapeHtml(item.name)}</h3>
                    <p class="food-desc">${escapeHtml(item.description)}</p>
                    <div class="card-footer-aesthetic">
                        <div class="food-price">
                            <span class="price-symbol">₱</span>
                            <span class="price-value">${parseFloat(item.price).toFixed(2)}</span>
                        </div>
                        <button type="button" class="btn-add-aesthetic" data-item-id="${item.id}" 
                                data-name="${escapeHtml(item.name)}" onclick="event.stopPropagation();">
                            <i class="fas fa-shopping-basket"></i> Add
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        // Re-attach event listeners
        attachCardListeners();
    }

    /**
     * Load menu items via AJAX
     */
    async function loadMenuItems(category = null, search = null) {
        loading.show();
        
        try {
            const data = {};
            if (category && category !== 'all') {
                data.category = category;
            }
            if (search) {
                data.search = search;
            }

            const result = await ajax.request('actions/menu/filter_menu.php', {
                method: 'GET',
                data: data,
                cache: true,
                cacheTime: 30000 // 30 seconds cache for menu
            });

            if (result.success) {
                renderMenuItems(result.items);
            } else {
                notify.error(result.message || 'Failed to load menu items');
            }
        } catch (error) {
            console.error('Error loading menu:', error);
            notify.error('Error loading menu items. Please try again.');
        } finally {
            loading.hide();
        }
    }

    /**
     * Handle category chip clicks
     */
    if (categoryChips.length > 0) {
        categoryChips.forEach(chip => {
            chip.addEventListener('click', function(e) {
                e.preventDefault();
                
                const category = this.dataset.category;
                
                // Update active state
                categoryChips.forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                currentCategory = category;
                currentSearch = null;
                if (menuSearchInput) menuSearchInput.value = '';

                loadMenuItems(category, null);
            });
        });
    }

    /**
     * Handle menu search with debounce
     */
    if (menuSearchInput) {
        menuSearchInput.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            
            const search = this.value.trim();
            currentSearch = search;

            debounceTimer = setTimeout(() => {
                // Reset category when searching
                if (search) {
                    categoryChips.forEach(c => c.classList.remove('active'));
                    currentCategory = null;
                }

                loadMenuItems(null, search || null);
            }, 300); // 300ms debounce
        });
    }

    /**
     * Attach listeners to cards
     */
    function attachCardListeners() {
        const cards = document.querySelectorAll('.card-aesthetic');
        const addButtons = document.querySelectorAll('.btn-add-aesthetic');

        cards.forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.closest('.btn-add-aesthetic')) {
                    return;
                }
                
                openItemModal(this);
            });
        });

        addButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const itemId = this.dataset.itemId;
                if (typeof addToCart === 'function') {
                    addToCart(itemId, 1);
                }
            });
        });
    }

    /**
     * Open item modal (if exists)
     */
    function openItemModal(card) {
        const modal = document.getElementById('itemModal');
        if (!modal) return;

        const itemId = card.dataset.itemId;
        const itemName = card.querySelector('.food-name').textContent;
        const itemImage = card.querySelector('.food-img').src;
        const itemDescription = card.querySelector('.food-desc').textContent;
        const itemPrice = card.querySelector('.price-value').textContent;
        const itemCategory = card.querySelector('.badge-custom').textContent;

        document.getElementById('modalName').textContent = itemName;
        document.getElementById('modalImage').src = itemImage;
        document.getElementById('modalDescription').textContent = itemDescription;
        document.getElementById('modalPrice').textContent = itemPrice;
        document.getElementById('modalCategory').textContent = itemCategory;
        document.getElementById('quantity').value = 1;
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initial render if items exist
    const initialCards = document.querySelectorAll('.card-aesthetic');
    if (initialCards.length > 0) {
        attachCardListeners();
    }
});
