class MenuManagement {
    constructor(userId) {
        this.userId = userId;
        this.socket = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.setupEventListeners();
        this.connect();
    }

    connect() {
        try {
            this.socket = new WebSocket('ws://localhost:8080');

            this.socket.onopen = () => {
                console.log('WebSocket connected');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                
                // Register as admin
                this.socket.send(JSON.stringify({
                    action: 'register_admin',
                    userId: this.userId
                }));
            };

            this.socket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleMessage(data);
            };

            this.socket.onclose = () => {
                console.log('WebSocket disconnected');
                this.isConnected = false;
                this.attemptReconnect();
            };

            this.socket.onerror = (error) => {
                console.error('WebSocket error:', error);
            };

        } catch (error) {
            console.error('Failed to connect to WebSocket:', error);
            this.attemptReconnect();
        }
    }

    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
            setTimeout(() => this.connect(), 5000);
        } else {
            console.error('Max reconnection attempts reached');
            this.showToast('Connection lost. Please refresh the page.', 'error');
        }
    }

    handleMessage(data) {
        switch (data.type) {
            case 'menu_update':
                this.updateMenuItems(data.items);
                break;
            case 'error':
                this.showToast(data.message, 'error');
                break;
        }
    }

    updateMenuItems(items) {
        const container = document.getElementById('menu-items-container');
        if (!container) return;

        // Group items by category
        const itemsByCategory = items.reduce((acc, item) => {
            if (!acc[item.category_id]) {
                acc[item.category_id] = {
                    name: item.category_name,
                    items: []
                };
            }
            acc[item.category_id].items.push(item);
            return acc;
        }, {});

        // Clear container
        container.innerHTML = '';

        // Create category sections
        Object.values(itemsByCategory).forEach(category => {
            const categorySection = this.createCategorySection(category);
            container.appendChild(categorySection);
        });
    }

    createCategorySection(category) {
        const section = document.createElement('div');
        section.className = 'category-section mb-4';
        
        section.innerHTML = `
            <h3 class="category-title mb-3">${category.name}</h3>
            <div class="menu-items-grid">
                ${category.items.map(item => this.createMenuItemCard(item)).join('')}
            </div>
        `;

        return section;
    }

    createMenuItemCard(item) {
        return `
            <div class="menu-item-card" data-id="${item.id}">
                <div class="item-image">
                    <img src="${item.image_path}" alt="${item.name}">
                    <div class="item-status ${item.status}">${item.status}</div>
                </div>
                <div class="item-details">
                    <h4 class="item-name">${item.name}</h4>
                    <p class="item-description">${item.description}</p>
                    <div class="item-price">₱${parseFloat(item.price).toFixed(2)}</div>
                    <div class="item-actions">
                        <button class="btn btn-edit" onclick="menuManager.editItem(${item.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-delete" onclick="menuManager.deleteItem(${item.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    addItem(formData) {
        if (!this.isConnected) {
            this.showToast('Not connected to server', 'error');
            return;
        }

        this.socket.send(JSON.stringify({
            action: 'add_item',
            item: formData
        }));
    }

    editItem(itemId) {
        // Show edit modal with item data
        const item = document.querySelector(`[data-id="${itemId}"]`);
        if (!item) return;

        // Populate edit form
        document.getElementById('edit-item-id').value = itemId;
        document.getElementById('edit-item-name').value = item.querySelector('.item-name').textContent;
        document.getElementById('edit-item-description').value = item.querySelector('.item-description').textContent;
        document.getElementById('edit-item-price').value = item.querySelector('.item-price').textContent.replace('₱', '');
        
        // Show modal
        const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));
        editModal.show();
    }

    updateItem(formData) {
        if (!this.isConnected) {
            this.showToast('Not connected to server', 'error');
            return;
        }

        this.socket.send(JSON.stringify({
            action: 'update_item',
            item: formData
        }));
    }

    deleteItem(itemId) {
        if (confirm('Are you sure you want to delete this item?')) {
            if (!this.isConnected) {
                this.showToast('Not connected to server', 'error');
                return;
            }

            this.socket.send(JSON.stringify({
                action: 'delete_item',
                itemId: itemId
            }));
        }
    }

    setupEventListeners() {
        // Add item form submission
        const addForm = document.getElementById('add-item-form');
        if (addForm) {
            addForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(addForm);
                this.addItem(Object.fromEntries(formData));
                addForm.reset();
                bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();
            });
        }

        // Edit item form submission
        const editForm = document.getElementById('edit-item-form');
        if (editForm) {
            editForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(editForm);
                this.updateItem(Object.fromEntries(formData));
                editForm.reset();
                bootstrap.Modal.getInstance(document.getElementById('editItemModal')).hide();
            });
        }
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
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

// Initialize when the page loads
document.addEventListener('DOMContentLoaded', () => {
    const userId = document.body.dataset.userId;
    if (userId) {
        window.menuManager = new MenuManagement(userId);
    }
}); 