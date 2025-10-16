// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && 
            !e.target.closest('.sidebar') && 
            !e.target.closest('.sidebar-toggle') && 
            sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
        }
    });

    // Active menu item highlighting
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar nav a').forEach(link => {
        if (currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });

    // Format currency values
    document.querySelectorAll('.currency').forEach(element => {
        const value = parseFloat(element.textContent);
        if (!isNaN(value)) {
            element.textContent = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
            }).format(value);
        }
    });

    // Format dates
    document.querySelectorAll('.date').forEach(element => {
        const date = new Date(element.textContent);
        if (date instanceof Date && !isNaN(date)) {
            element.textContent = new Intl.DateTimeFormat('en-PH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(date);
        }
    });

    // Format numbers with commas
    document.querySelectorAll('.number').forEach(element => {
        const value = parseInt(element.textContent);
        if (!isNaN(value)) {
            element.textContent = value.toLocaleString();
        }
    });

    // Handle form submissions with AJAX
    document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('[type="submit"]');
            const originalText = submitBtn.textContent;
            
            try {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
                
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: form.method,
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', result.message || 'Operation successful');
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    }
                } else {
                    showNotification('error', result.message || 'Operation failed');
                }
            } catch (error) {
                showNotification('error', 'An error occurred. Please try again.');
                console.error('Form submission error:', error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    });
});

// Notification system
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Confirmation dialog
function confirmAction(message) {
    return new Promise((resolve) => {
        const dialog = document.createElement('div');
        dialog.className = 'confirm-dialog';
        dialog.innerHTML = `
            <div class="confirm-content">
                <p>${message}</p>
                <div class="confirm-buttons">
                    <button class="btn-cancel">Cancel</button>
                    <button class="btn-confirm">Confirm</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(dialog);
        
        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'dialog-backdrop';
        document.body.appendChild(backdrop);
        
        // Handle button clicks
        dialog.querySelector('.btn-confirm').addEventListener('click', () => {
            resolve(true);
            closeDialog();
        });
        
        dialog.querySelector('.btn-cancel').addEventListener('click', () => {
            resolve(false);
            closeDialog();
        });
        
        function closeDialog() {
            dialog.remove();
            backdrop.remove();
        }
        
        // Show dialog with animation
        setTimeout(() => {
            dialog.classList.add('show');
            backdrop.classList.add('show');
        }, 10);
    });
}

// Add necessary styles for notifications and dialogs
const style = document.createElement('style');
style.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 4px;
        color: white;
        transform: translateX(120%);
        transition: transform 0.3s ease-in-out;
        z-index: 1000;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification.success {
        background-color: #28a745;
    }
    
    .notification.error {
        background-color: #dc3545;
    }
    
    .confirm-dialog {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.9);
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 1001;
        opacity: 0;
        transition: all 0.3s ease-in-out;
    }
    
    .confirm-dialog.show {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
    
    .dialog-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
        z-index: 1000;
    }
    
    .dialog-backdrop.show {
        opacity: 1;
    }
    
    .confirm-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn-cancel {
        padding: 8px 16px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .btn-confirm {
        padding: 8px 16px;
        background: #006400;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .btn-confirm:hover {
        background: #005000;
    }
`;

document.head.appendChild(style);

// Function to format numbers with commas
function formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Function to format currency
function formatCurrency(amount) {
    return '₱' + formatNumber(parseFloat(amount).toFixed(2));
}

// Function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()} ${date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}`;
}

// Function to update dashboard statistics
function updateDashboardStats() {
    fetch('../api/get_dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update orders count
                document.querySelector('.stats-card:nth-child(1) .stats-info h3').textContent = data.stats.orders_today;
                
                // Update revenue
                document.querySelector('.stats-card:nth-child(2) .stats-info h3').textContent = formatCurrency(data.stats.daily_revenue);
                
                // Update users count
                document.querySelector('.stats-card:nth-child(3) .stats-info h3').textContent = data.stats.users_count;
                
                // Update last updated time
                document.querySelector('.last-updated').textContent = 'Last updated: ' + formatDate(new Date());
            }
        })
        .catch(error => console.error('Error updating dashboard stats:', error));
}

// Function to update recent orders
function updateRecentOrders() {
    fetch('../api/get_recent_orders.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.orders.length > 0) {
                const ordersList = document.querySelector('.orders-list');
                const ordersContent = data.orders.map(order => `
                    <div class="order-item">
                        <div class="order-info">
                            <div class="order-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="order-details">
                                <h4>Order #${order.id} by ${order.full_name}</h4>
                                <p>${formatDate(order.created_at)}</p>
                            </div>
                        </div>
                        <div class="order-amount">${formatCurrency(order.total_amount)}</div>
                    </div>
                `).join('');
                
                // Update orders list content
                const ordersContainer = ordersList.querySelector('.section-header').nextElementSibling;
                ordersContainer.innerHTML = ordersContent;
            }
        })
        .catch(error => console.error('Error updating recent orders:', error));
}

// Function to update popular items
function updatePopularItems() {
    fetch('../api/get_popular_items.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.items.length > 0) {
                const itemsList = document.querySelector('.popular-items');
                const itemsContent = data.items.map(item => `
                    <div class="popular-item">
                        <div class="item-info">
                            <div class="item-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <div class="item-details">
                                <h4>${item.name}</h4>
                                <p>${item.order_count} orders</p>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                // Update items list content
                const itemsContainer = itemsList.querySelector('.section-header').nextElementSibling;
                itemsContainer.innerHTML = itemsContent;
            }
        })
        .catch(error => console.error('Error updating popular items:', error));
}

// Initialize real-time updates
document.addEventListener('DOMContentLoaded', function() {
    // Initial updates
    updateDashboardStats();
    updateRecentOrders();
    updatePopularItems();
    
    // Set up periodic updates
    setInterval(updateDashboardStats, 30000); // Update stats every 30 seconds
    setInterval(updateRecentOrders, 30000); // Update orders every 30 seconds
    setInterval(updatePopularItems, 60000); // Update popular items every minute
    
    // Profile dropdown functionality
    const profileSection = document.querySelector('.profile-section');
    const profileDropdown = document.getElementById('profileDropdown');
    
    profileSection.addEventListener('click', function(e) {
        e.stopPropagation();
        profileDropdown.classList.toggle('show');
    });
    
    document.addEventListener('click', function(e) {
        if (!profileDropdown.contains(e.target)) {
            profileDropdown.classList.remove('show');
        }
    });
}); 