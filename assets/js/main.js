// Define categories globally for food menu filtering
const categories = ["All", "Rice Meals", "Burgers", "Desserts", "Beverages"];

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Initialize date pickers
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    const oneYearFromNow = new Date();
    oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
    const maxDate = oneYearFromNow.toISOString().split('T')[0];
    
    dateInputs.forEach(input => {
        if (input.id === 'check_in') {
            input.min = today;
            input.max = maxDate;
        } else if (input.id === 'check_out') {
            input.min = today;
            input.max = maxDate;
        }
    });

    // Check-out date validation
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    
    if (checkInInput && checkOutInput) {
        checkInInput.addEventListener('change', function() {
            checkOutInput.min = this.value;
            
            // If check-out date is before check-in date, update it
            if (checkOutInput.value && new Date(checkOutInput.value) < new Date(this.value)) {
                checkOutInput.value = this.value;
            }
            
            // If check-out date is beyond one year from now, update it
            if (checkOutInput.value && new Date(checkOutInput.value) > oneYearFromNow) {
                checkOutInput.value = maxDate;
            }
        });
        
        checkOutInput.addEventListener('change', function() {
            // If check-out date is beyond one year from check-in, limit it
            const selectedCheckIn = new Date(checkInInput.value || today);
            const maxCheckOut = new Date(selectedCheckIn);
            maxCheckOut.setFullYear(maxCheckOut.getFullYear() + 1);
            
            if (new Date(this.value) > maxCheckOut) {
                this.value = maxCheckOut.toISOString().split('T')[0];
            }
        });
    }

    // Food Menu Modal logic
    const viewFoodMenuBtn = document.getElementById('view-food-menu-btn');
    const fabBtn = document.getElementById('view-food-menu-btn-fab');
    const foodMenuModal = document.getElementById('foodMenuModal');
    const foodMenuContent = document.getElementById('food-menu-content');
    let bsFoodMenuModal = null;
    if (foodMenuModal && typeof bootstrap !== 'undefined') {
        bsFoodMenuModal = new bootstrap.Modal(foodMenuModal);
    }
    function showFoodMenuModal() {
        if (!foodMenuContent) return;
            foodMenuContent.innerHTML = '<div class="food-menu-content-card"><div class="text-center text-muted">Loading menu...</div></div>';
            fetch('/api/food-menu')
                .then(response => response.json())
                .then(data => {
                    let html = '';
                if (data.error === 'Food service is currently unavailable. Please try again later.') {
                    html = `<div class='food-unavailable-message' style="background:#e7d7ce;color:#6D4C41;font-size:1.6rem;padding:32px 16px;margin:0 auto;border-radius:16px;width:100%;min-height:120px;display:flex;flex-direction:column;align-items:center;justify-content:center;box-shadow:0 4px 24px rgba(0,0,0,0.10);text-align:center;">
        <i class='fas fa-utensils fa-2x mb-3'></i>
        <span style='font-weight:700;'>Food service is unavailable.</span>
    </div>`;
                } else if (data.error) {
                        html = `<div class='alert alert-danger'>Error: ${data.error}</div>`;
                        if (data.raw_response) {
                            html += `<pre style="max-height:200px;overflow:auto;background:#f8f9fa;border:1px solid #ccc;padding:8px;">${data.raw_response}</pre>`;
                        }
                    } else if (Array.isArray(data.menu)) {
                        html = '<ul class="list-group">';
                        data.menu.forEach(item => {
                            html += `<li class="list-group-item"><strong>${item.name}</strong>: ₱${item.price} <br>${item.description || ''}</li>`;
                        });
                        html += '</ul>';
                    } else if (Array.isArray(data)) {
                        // Add a style block for category buttons and responsive grid
                        const catStyle = document.createElement('style');
                        catStyle.innerHTML = `
                        .food-cat-btn {
                          margin: 0 6px 10px 0 !important;
                          border-radius: 20px !important;
                          font-weight: 600;
                          min-width: 110px;
                          background: #fff;
                          color: #6D4C41;
                          border: 2px solid #6D4C41;
                          transition: background 0.2s, color 0.2s, border 0.2s;
                        }
                        .food-cat-btn.active, .food-cat-btn:focus {
                          background: #6D4C41 !important;
                          color: #fff !important;
                          border: 2px solid #6D4C41 !important;
                          outline: none;
                        }
                        .food-cat-btn:hover {
                          background: #e7d7ce !important;
                          color: #6D4C41 !important;
                          border: 2px solid #6D4C41 !important;
                        }
                        .food-menu-grid {
                          max-width: 1400px;
                          margin: 0 auto;
                        }
                        @media (max-width: 1200px) {
                          .food-menu-grid {
                            grid-template-columns: repeat(3, 1fr) !important;
                          }
                        }
                        @media (max-width: 800px) {
                          .food-menu-grid {
                            grid-template-columns: repeat(1, 1fr) !important;
                          }
                        }
                        `;
                        document.head.appendChild(catStyle);
                        html = '<div style="margin-bottom:16px;text-align:center;">';
                        categories.forEach((cat, idx) => {
                            html += `<button class="food-cat-btn${idx === 0 ? ' active' : ''}" data-cat="${cat}">${cat}</button> `;
                        });
                        // Add 'Your Orders' button in line with categories
                        html += `<button class="food-cat-btn" id="yourOrdersCatBtn">Your Orders</button>`;
                        html += '</div>';
                        html += '<div class="food-menu-grid" style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;max-width:1400px;margin:0 auto;">';
                        data.forEach(item => {
                            const itemCat = item.category || "Other";
                            html += `
                            <div class="food-menu-item" data-category="${itemCat}" style="background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.04);padding:12px;display:flex;flex-direction:column;align-items:center;min-height:320px;height:100%;justify-content:space-between;">
                                <img src="${item.image}" alt="${item.name}" style="width:140px;height:140px;object-fit:cover;border-radius:8px;">
                                <button class="order-food-btn" data-id="${item.id}" data-name="${item.name}" data-price="${item.price}" data-image="${item.image}">Order</button>
                                <div style="margin-top:8px;text-align:center;flex:1;display:flex;flex-direction:column;justify-content:space-between;width:100%;">
                                    <strong>${item.name}</strong>: ₱${item.price}<br>
                                    <span>${item.description || ''}</span><br>
                                </div>
                            </div>`;
                        });
                        html += '</div>';
                    } else {
                        html = '<div class="text-muted">No menu available.</div>';
                    }
                    foodMenuContent.innerHTML = `<div class='food-menu-content-card'>${html}</div>`;
                })
                .catch(err => {
                    foodMenuContent.innerHTML = `<div class='food-menu-content-card'><div class='alert alert-danger'>Failed to load menu: ${err}</div></div>`;
                });
            if (bsFoodMenuModal) bsFoodMenuModal.show();
    }
    window.showFoodMenuModal = showFoodMenuModal;
    if (viewFoodMenuBtn) {
        viewFoodMenuBtn.addEventListener('click', showFoodMenuModal);
    }
    if (fabBtn) {
        fabBtn.addEventListener('click', showFoodMenuModal);
    }
});

// Fix: Remove lingering modal-backdrop and modal-open class when food menu modal is closed
const foodMenuModalEl = document.getElementById('foodMenuModal');
if (foodMenuModalEl) {
    // Always fetch and render menu when modal is shown
    foodMenuModalEl.addEventListener('show.bs.modal', function () {
        const foodMenuContent = document.getElementById('food-menu-content');
        if (foodMenuContent) {
            foodMenuContent.innerHTML = '<div class="food-menu-content-card"><div class="text-center text-muted">Loading menu...</div></div>';
            fetch('/api/food-menu')
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    if (data.error === 'Food service is currently unavailable. Please try again later.') {
                        html = `<div class='food-unavailable-message' style="background:#e7d7ce;color:#6D4C41;font-size:1.6rem;padding:32px 16px;margin:0 auto;border-radius:16px;width:100%;min-height:120px;display:flex;flex-direction:column;align-items:center;justify-content:center;box-shadow:0 4px 24px rgba(0,0,0,0.10);text-align:center;">
        <i class='fas fa-utensils fa-2x mb-3'></i>
        <span style='font-weight:700;'>Food service is unavailable.</span>
    </div>`;
                    } else if (data.error) {
                        html = `<div class='alert alert-danger'>Error: ${data.error}</div>`;
                        if (data.raw_response) {
                            html += `<pre style="max-height:200px;overflow:auto;background:#f8f9fa;border:1px solid #ccc;padding:8px;">${data.raw_response}</pre>`;
                        }
                    } else if (Array.isArray(data.menu)) {
                        html = '<ul class="list-group">';
                        data.menu.forEach(item => {
                            html += `<li class="list-group-item"><strong>${item.name}</strong>: ₱${item.price} <br>${item.description || ''}</li>`;
                        });
                        html += '</ul>';
                    } else if (Array.isArray(data)) {
                        // Add a style block for category buttons
                        const catStyle = document.createElement('style');
                        catStyle.innerHTML = `
                        .food-cat-btn {
                          margin: 0 6px 10px 0 !important;
                          border-radius: 20px !important;
                          font-weight: 600;
                          min-width: 110px;
                          background: #fff;
                          color: #6D4C41;
                          border: 2px solid #6D4C41;
                          transition: background 0.2s, color 0.2s, border 0.2s;
                        }
                        .food-cat-btn.active, .food-cat-btn:focus {
                          background: #6D4C41 !important;
                          color: #fff !important;
                          border: 2px solid #6D4C41 !important;
                          outline: none;
                        }
                        .food-cat-btn:hover {
                          background: #e7d7ce !important;
                          color: #6D4C41 !important;
                          border: 2px solid #6D4C41 !important;
                        }`;
                        document.head.appendChild(catStyle);
                        // When rendering category buttons, use only food-cat-btn class (no Bootstrap btn-outline-primary or btn-sm)
                        html = '<div style="margin-bottom:16px;text-align:center;">';
                        categories.forEach((cat, idx) => {
                            html += `<button class="food-cat-btn${idx === 0 ? ' active' : ''}" data-cat="${cat}">${cat}</button> `;
                        });
                        // Add 'Your Orders' button in line with categories
                        html += `<button class="food-cat-btn" id="yourOrdersCatBtn">Your Orders</button>`;
                        html += '</div>';
                        html += '<div class="food-menu-grid" style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;">';
                        data.forEach(item => {
                            const itemCat = item.category || "Other";
                            html += `
                            <div class="food-menu-item" data-category="${itemCat}" style="background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.04);padding:12px;display:flex;flex-direction:column;align-items:center;min-height:320px;height:100%;justify-content:space-between;">
                                <img src="${item.image}" alt="${item.name}" style="width:140px;height:140px;object-fit:cover;border-radius:8px;">
                                <button class="order-food-btn" data-id="${item.id}" data-name="${item.name}" data-price="${item.price}" data-image="${item.image}">Order</button>
                                <div style="margin-top:8px;text-align:center;flex:1;display:flex;flex-direction:column;justify-content:space-between;width:100%;">
                                    <strong>${item.name}</strong>: ₱${item.price}<br>
                                    <span>${item.description || ''}</span><br>
                                </div>
                            </div>`;
                        });
                        html += '</div>';
                    } else {
                        html = '<div class="text-muted">No menu available.</div>';
                    }
                    foodMenuContent.innerHTML = `<div class='food-menu-content-card'>${html}</div>`;
                })
                .catch(err => {
                    foodMenuContent.innerHTML = `<div class='food-menu-content-card'><div class='alert alert-danger'>Failed to load menu: ${err}</div></div>`;
                });
        }
    });
    // Remove lingering modal-backdrop and modal-open class when food menu modal is closed
    foodMenuModalEl.addEventListener('hidden.bs.modal', function () {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style = '';
    });
}

// Add fade-in animation to food menu modal
if (foodMenuModalEl) {
    foodMenuModalEl.classList.add('fade');
    foodMenuModalEl.addEventListener('show.bs.modal', function () {
        foodMenuModalEl.classList.add('show');
    });
    foodMenuModalEl.addEventListener('hidden.bs.modal', function () {
        foodMenuModalEl.classList.remove('show');
    });
}

// Floating action button for Order Food
const fabBtn = document.getElementById('view-food-menu-btn-fab');
if (fabBtn) {
    // Remove the old click handler here (handled below)
}

// Make the floating action button draggable and only open modal on true, quick click (not after drag or long press)
(function() {
    const fab = document.getElementById('view-food-menu-btn-fab');
    if (!fab) return;

    let startX, startY, mouseDownTime = 0, dragMoved = false, blockClick = false;

    fab.addEventListener('mousedown', function(e) {
        mouseDownTime = Date.now();
        startX = e.clientX;
        startY = e.clientY;
        dragMoved = false;
        blockClick = false;
        fab._dragStartLeft = fab.offsetLeft;
        fab._dragStartTop = fab.offsetTop;
        fab.style.transition = 'none';
        document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', function(e) {
        if (mouseDownTime === 0) return;
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        if (Math.abs(dx) > 5 || Math.abs(dy) > 5) { // Use a slightly larger threshold
            dragMoved = true;
            blockClick = true;
            let newLeft = fab._dragStartLeft + dx;
            let newTop = fab._dragStartTop + dy;
            // Clamp within viewport
            const minTop = 80;
            const maxTop = window.innerHeight - fab.offsetHeight - 16;
            const minLeft = 0;
            const maxLeft = window.innerWidth - fab.offsetWidth - 16;
            newTop = Math.max(minTop, Math.min(newTop, maxTop));
            newLeft = Math.max(minLeft, Math.min(newLeft, maxLeft));
            fab.style.position = 'fixed';
            fab.style.left = newLeft + 'px';
            fab.style.top = newTop + 'px';
            fab.style.right = 'auto';
            fab.style.bottom = 'auto';
        }
    });

    document.addEventListener('mouseup', function() {
        mouseDownTime = 0;
        fab.style.transition = '';
        document.body.style.userSelect = '';
    });

    fab.addEventListener('click', function(e) {
        if (blockClick) {
            e.preventDefault();
            blockClick = false;
            dragMoved = false;
            return;
        }
        // Only open modal if not dragged and quick click
        if (!dragMoved) {
            if (typeof bootstrap !== 'undefined') {
                const foodMenuModal = new bootstrap.Modal(document.getElementById('foodMenuModal'));
                foodMenuModal.show();
            }
        }
        e.preventDefault();
        mouseDownTime = 0;
        dragMoved = false;
    });

    // Touch support
    let touchStartX, touchStartY, touchMoved = false;
    fab.addEventListener('touchstart', function(e) {
        if (e.touches.length !== 1) return;
        touchMoved = false;
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
        fab._dragStartLeft = fab.offsetLeft;
        fab._dragStartTop = fab.offsetTop;
        fab.style.transition = 'none';
    }, { passive: false });

    fab.addEventListener('touchmove', function(e) {
        if (e.touches.length !== 1) return;
        const dx = e.touches[0].clientX - touchStartX;
        const dy = e.touches[0].clientY - touchStartY;
        if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
            touchMoved = true;
            let newLeft = fab._dragStartLeft + dx;
            let newTop = fab._dragStartTop + dy;
            // Clamp within viewport
            const minTop = 80;
            const maxTop = window.innerHeight - fab.offsetHeight - 16;
            const minLeft = 0;
            const maxLeft = window.innerWidth - fab.offsetWidth - 16;
            newTop = Math.max(minTop, Math.min(newTop, maxTop));
            newLeft = Math.max(minLeft, Math.min(newLeft, maxLeft));
            fab.style.position = 'fixed';
            fab.style.left = newLeft + 'px';
            fab.style.top = newTop + 'px';
            fab.style.right = 'auto';
            fab.style.bottom = 'auto';
        }
    }, { passive: false });

    fab.addEventListener('touchend', function(e) {
        fab.style.transition = '';
        if (!touchMoved) {
            if (typeof bootstrap !== 'undefined') {
                const foodMenuModal = new bootstrap.Modal(document.getElementById('foodMenuModal'));
                foodMenuModal.show();
            }
        }
    });
})();

// Dynamic price calculation
function calculatePrice() {
    const checkIn = new Date(document.getElementById('check_in').value);
    const checkOut = new Date(document.getElementById('check_out').value);
    const pricePerNight = parseFloat(document.getElementById('price_per_night').value);
    
    if (checkIn && checkOut && pricePerNight) {
        const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
        const totalPrice = nights * pricePerNight;
        document.getElementById('total_price').textContent = totalPrice.toFixed(2);
    }
}

// Star rating system
function setRating(rating) {
    document.getElementById('rating').value = rating;
    const stars = document.querySelectorAll('.rating-star');
    stars.forEach((star, index) => {
        star.classList.toggle('active', index < rating);
    });
}

// Amenity selection
function toggleAmenity(amenityId) {
    const checkbox = document.getElementById(`amenity-${amenityId}`);
    const card = checkbox.closest('.amenity-card');
    card.classList.toggle('selected');
}

// Search and filter rooms
function filterRooms() {
    const searchTerm = document.getElementById('search').value.toLowerCase();
    const rooms = document.querySelectorAll('.room-card');
    
    rooms.forEach(room => {
        const roomType = room.querySelector('.room-type').textContent.toLowerCase();
        const roomNumber = room.querySelector('.room-number').textContent.toLowerCase();
        
        if (roomType.includes(searchTerm) || roomNumber.includes(searchTerm)) {
            room.style.display = 'block';
        } else {
            room.style.display = 'none';
        }
    });
}

// Confirmation dialogs
function confirmDelete(itemType, itemId) {
    return confirm(`Are you sure you want to delete this ${itemType}?`);
}

function confirmCancel(bookingId) {
    return confirm('Are you sure you want to cancel this booking?');
}

// Toast notifications
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type} show`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Image preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image-preview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Responsive table
function makeTableResponsive() {
    const tables = document.querySelectorAll('.table-responsive table');
    tables.forEach(table => {
        const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent);
        const cells = table.querySelectorAll('td');
        
        cells.forEach((cell, index) => {
            cell.setAttribute('data-label', headers[index % headers.length]);
        });
    });
}

// Initialize all responsive tables
document.addEventListener('DOMContentLoaded', makeTableResponsive);

// Profile dropdown functionality
function toggleProfileDropdown(event) {
    event.preventDefault();
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    const profileLink = document.querySelector('.profile a');
    
    if (!profileLink.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
}); 

document.addEventListener('DOMContentLoaded', function() {
  var toggler = document.getElementById('navbar-toggler');
  var menu = document.getElementById('navbar-menu');
  if (toggler && menu) {
    toggler.addEventListener('click', function(e) {
      e.stopPropagation();
      menu.classList.toggle('active');
    });
  }
  document.addEventListener('click', function(e) {
    if (menu && menu.classList.contains('active')) {
      if (!menu.contains(e.target) && e.target !== toggler) {
        menu.classList.remove('active');
      }
    }
  });
});

// Add these at the top of the file or before the click handler:
let selectedItemName = '';
let selectedItemPrice = '';
let selectedItemImage = '';
let selectedItemDescription = '';

// In the order-food-btn click handler, set these values:
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('order-food-btn')) {
        // Get user info from hidden fields
        const userId = document.getElementById('user-id')?.value || '';
        const userName = document.getElementById('user-name')?.value || '';
        const userPhone = document.getElementById('user-phone')?.value || '';
        const userAddress = document.getElementById('user-address')?.value || '';
        const userEmail = document.getElementById('user-email')?.value || ''; // Get user email

        // Get item info
        const itemId = e.target.getAttribute('data-id');
        const itemName = e.target.getAttribute('data-name');
        const itemPrice = e.target.getAttribute('data-price');
        const itemImage = e.target.getAttribute('data-image');
        // Extract description from the DOM (adjust selector as needed)
        const itemDescription = e.target.closest('.food-menu-item').querySelector('span')?.textContent.trim() || '';
        selectedItemName = itemName;
        selectedItemPrice = itemPrice;
        selectedItemImage = itemImage;
        selectedItemDescription = itemDescription;

        // Get approved bookings from a global JS variable or hidden JSON
        let approvedBookings = [];
        const approvedBookingsInput = document.getElementById('approved-bookings-json');
        if (approvedBookingsInput) {
            try {
                approvedBookings = JSON.parse(approvedBookingsInput.value);
            } catch (e) { approvedBookings = []; }
        } else {
            approvedBookings = [];
        }

        // Barangay and sitio data (hardcoded for now)
        const barangays = [
            'Alipit','Bagumbayan','Bubukal','Calios','Duhat','Gatid','Labuin','Malinao','Oogong','Pagsawitan','Palasan','Patimbao','Poblacion I','Poblacion II','Poblacion III','Poblacion IV','San Jose','San Juan','San Pablo Norte','San Pablo Sur','Santa Cruz Putol','Santo Angel Central','Santo Angel Norte','Santo Angel Sur','Santisima Cruz','Sapa','Tagapo'
        ];
        const sitiosByBarangay = {
            'Alipit': ['Sitio Uno','Sitio Dos','Sitio Tres'],
            'Bagumbayan': ['Purok 1','Purok 2','Purok 3'],
            'Bubukal': ['Spring Side','Central','Riverside'],
            'Calios': ['Lakeview','Market Area','Fishermen\'s Village'],
            'Duhat': ['Orchard Side','Main Road','Interior'],
            'Gatid': ['Rice Field Area','Highway Side','School Zone'],
            'Labuin': ['Farmers Village','Centro','Riverside'],
            'Malinao': ['Lakeside','Upper Malinao','Lower Malinao'],
            'Oogong': ['Main','Riverside','Market Area'],
            'Pagsawitan': ['Centro','Riverside','Highway'],
            'Palasan': ['Upper Palasan','Lower Palasan','Central'],
            'Patimbao': ['Main Road','Interior','Riverside'],
            'Poblacion I': ['Plaza Area','Market Side','Church Area'],
            'Poblacion II': ['Commercial District','School Zone','Residential Area'],
            'Poblacion III': ['Main Street','Park Side','Market Area'],
            'Poblacion IV': ['Town Center','Business District','Residential Zone'],
            'San Jose': ['Upper San Jose','Lower San Jose','Central'],
            'San Juan': ['Riverside','Centro','Highway'],
            'San Pablo Norte': ['Upper Area','Central','Highway Side'],
            'San Pablo Sur': ['Main Road','Interior','Market Area'],
            'Santa Cruz Putol': ['Central','Riverside','Highway'],
            'Santo Angel Central': ['Plaza Area','Church Side','Market Zone'],
            'Santo Angel Norte': ['Upper Area','Central','Lower Area'],
            'Santo Angel Sur': ['Main Road','Interior','Highway Side'],
            'Santisima Cruz': ['Church Area','Plaza Side','Residential Zone'],
            'Sapa': ['Riverside','Central','Market Area'],
            'Tagapo': ['Upper Tagapo','Lower Tagapo','Central']
        };

        // Build dropdown HTML
        let dropdownHtml = '';
        if (approvedBookings.length > 0) {
            dropdownHtml = `<div style='margin-bottom:16px;text-align:left;'>
                <label for='deliveryBookingSelect' style='font-weight:600;'>Select Room for Delivery:</label>
                <select id='deliveryBookingSelect' class='form-select' style='max-width:340px;'>
                    <option value='' disabled selected>Select a room</option>`;
            approvedBookings.forEach(b => {
                dropdownHtml += `<option value='${b.id}'>Room ${b.room_number} (${b.room_type}) - Booking #${b.id}</option>`;
            });
            dropdownHtml += `</select></div>`;
        } else {
            dropdownHtml = `<div class='text-danger mb-3' style='text-align:left;'>No approved rooms available for delivery.</div>`;
        }

        // Build order summary HTML as a form
        const summaryHtml = `
            <form id="orderDeliveryForm" autocomplete="off" style="max-width:1200px;width:100%;margin:0;padding:12px 32px;border-radius:24px;background:#f8f9fa;box-shadow:0 4px 24px rgba(0,0,0,0.08);font-size:1.15rem;">
                <div style="display:flex;align-items:center;margin-bottom:18px;">
  <button type="button" id="backFromDeliveryDetailsBtn" style="background:none;border:none;font-size:1.8rem;color:#6D4C41;margin-right:12px;cursor:pointer;line-height:1;" aria-label="Back">&#8592;</button>
  <h3 style='font-weight:700;margin:0;'>Delivery Details</h3>
</div>
                <h5 style='margin-top:0;margin-bottom:10px;'>Contact Information</h5>
                <div class="mb-2">
                    <label for="orderFullName" class="form-label">Full Name</label>
                    <input type="text" id="orderFullName" class="form-control" value="${userName}" readonly aria-label="Full Name">
                </div>
                <div class="mb-2">
                    <label for="orderEmail" class="form-label">Email</label>
                    <input type="email" id="orderEmail" class="form-control" value="${userEmail || ''}" placeholder="your@email.com" autocomplete="email" required aria-label="Email" readonly>
                    <div class="invalid-feedback" id="orderEmailError" style="display:none;">Please enter a valid email address.</div>
                </div>
                <div class="mb-2">
                    <label for="orderPhone" class="form-label">Phone Number</label>
                    <input type="tel" id="orderPhone" class="form-control" value="${userPhone}" placeholder="09XXXXXXXXX" autocomplete="tel" required aria-label="Phone Number" readonly>
                    <div class="invalid-feedback" id="orderPhoneError" style="display:none;">Please enter a valid phone number.</div>
                </div>
                <h5 style='margin-top:18px;margin-bottom:10px;'>Delivery Address</h5>
                <div class="mb-2">
                    <label for="orderBarangay" class="form-label">Barangay</label>
                    <select id="orderBarangay" class="form-select" required aria-label="Barangay" readonly>
                        <option value="Santa Cruz" selected>Santa Cruz</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label for="orderSitio" class="form-label">Sitio/Purok</label>
                    <select id="orderSitio" class="form-select" required aria-label="Sitio/Purok" readonly>
                        <option value="Central" selected>Central</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label for="orderLandmarks" class="form-label">Landmarks</label>
                    <input type="text" id="orderLandmarks" class="form-control" placeholder="Nearby landmarks or additional directions" aria-label="Landmarks" value="Fawna Hotel" readonly>
                </div>
                <div class="mb-2">
                    <label for="approvedRoomSelect" class="form-label">Select Room for Delivery</label>
                    <select id="approvedRoomSelect" class="form-select" required>
                        <option value="" disabled selected>Select a room</option>
                        ${approvedBookings.map(b => `<option value="${b.id}">Room ${b.room_number} (${b.room_type}) - Booking #${b.id}</option>`).join('')}
                    </select>
                </div>
                <div class="mb-2">
                    <label for="orderDeliveryNotes" class="form-label">Delivery Notes</label>
                    <textarea id="orderDeliveryNotes" class="form-control" placeholder="Room info will appear here" aria-label="Delivery Notes" readonly></textarea>
                </div>
                <div class="mb-2" id="orderedFoodDisplay" style="border:1px solid #e0e0e0;border-radius:8px;padding:12px 16px;margin-top:12px;background:#fff;display:flex;align-items:center;gap:16px;">
                  <img src="${selectedItemImage}" alt="${selectedItemName}" style="width:60px;height:60px;object-fit:cover;border-radius:8px;">
                  <div>
                    <div style="font-weight:600;font-size:1.1rem;">${selectedItemName}</div>
                    <div style="color:#555;font-size:0.98rem;margin-bottom:2px;">${selectedItemDescription || ''}</div>
                    <div style="color:#6D4C41;font-weight:500;">₱${selectedItemPrice}</div>
                  </div>
                </div>
                <div class="mb-2" id="orderBreakdown" style="font-size:1.08em;color:#4e342e;">
                  <div style="display:flex;justify-content:space-between;"><span>Subtotal:</span><span>₱${selectedItemPrice}</span></div>
                  <div style="display:flex;justify-content:space-between;"><span>Delivery Fee:</span><span>₱50.00</span></div>
                  <div style="display:flex;justify-content:space-between;font-weight:700;border-top:1px solid #e0e0e0;margin-top:4px;padding-top:4px;"><span>Total:</span><span>₱${(parseFloat(selectedItemPrice) + 50).toFixed(2)}</span></div>
                </div>
                <div class="mb-2">
                    <label for="paymentMethodSelect" class="form-label">Payment Method</label>
                    <select id="paymentMethodSelect" class="form-select" required aria-label="Payment Method" style="margin-bottom: 1.2em;">
                        <option value="" disabled selected>Select payment method</option>
                        <option value="cod">Cash on Delivery</option>
                        <option value="gcash">GCash</option>
                        <option value="half_payment">Half Payment (GCash + COD)</option>
                    </select>
                </div>
                <div class="mb-2" id="gcashQrSection" style="display:none;text-align:left;margin-top:8px;">
                  <button type="button" id="showGcashQrBtn" class="btn" style="background:rgba(109,76,65,0.08);color:#6D4C41;font-weight:600;border-radius:2rem;padding:0.55em 2.2em 0.55em 2.2em;display:inline-flex;align-items:center;gap:0.9em;margin-bottom:18px;border:2px solid #6D4C41;">
                    <i class="fas fa-qrcode" aria-hidden="true" style="font-size:1.2em;margin-right:0.5em;"></i> Show Electronic Payment
                  </button>
                </div>
                <!-- Modal for GCASH QR -->
                <div class="modal fade" id="gcashQrModal" tabindex="-1" aria-labelledby="gcashQrModalLabel" aria-hidden="true" data-bs-backdrop="static">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius:18px;">
                      <div class="modal-header" style="border-bottom:none;">
                        <h5 class="modal-title" id="gcashQrModalLabel">Electronic Payment (GCASH)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body" style="text-align:center;">
                        <img src="http://" + foodServiceIp + "/online-food-ordering/assets/images/gcash-qr-code.png" alt="GCASH QR Code" style="max-width:320px;width:100%;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.08);margin-bottom:8px;" />
                        <div style="font-weight:600;margin-top:8px;">Scan to pay via GCASH</div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="mt-3" style="margin-top: 2.2em;">
                    <button type="submit" class="btn btn-lg btn-primary" id="confirmOrderBtn" style="min-width:140px;">
                        <span id="confirmOrderBtnText">Confirm Order</span>
                        <span id="confirmOrderSpinner" class="spinner-border spinner-border-sm" style="display:none;" role="status" aria-hidden="true"></span>
                    </button>
                    <button type="button" class="btn btn-lg btn-secondary ms-2" id="cancelOrderBtn" style="min-width:120px;">Cancel</button>
                </div>
            </form>
        `;

        // Replace the menu content with the summary
        const menuContent = document.getElementById('food-menu-content');
        if (menuContent) {
            menuContent.innerHTML = `<div class='food-menu-content-card'>${summaryHtml}</div>`;
            setTimeout(() => {
              const backBtn = document.getElementById('backFromDeliveryDetailsBtn');
              if (backBtn) {
                backBtn.onclick = function() {
                  // Redirect to the food menu view
                  if (typeof showFoodMenuModal === 'function') {
                    showFoodMenuModal();
                  }
                };
              }
            }, 100);
        }

        // Populate sitio options when barangay changes
        setTimeout(() => {
            const barangaySelect = document.getElementById('orderBarangay');
            const sitioSelect = document.getElementById('orderSitio');
            if (barangaySelect && sitioSelect) {
                barangaySelect.addEventListener('change', function() {
                    const val = this.value;
                    sitioSelect.innerHTML = '<option value="">Select Sitio/Purok</option>';
                    if (val && sitiosByBarangay[val]) {
                        sitiosByBarangay[val].forEach(sitio => {
                            const opt = document.createElement('option');
                            opt.value = sitio;
                            opt.textContent = sitio;
                            sitioSelect.appendChild(opt);
                        });
                        sitioSelect.disabled = false;
                    } else {
                        sitioSelect.disabled = true;
                    }
                });
            }

            // Handle confirm/cancel
            const form = document.getElementById('orderDeliveryForm');
            if (form) {
                form.onsubmit = function(e) {
                    e.preventDefault();
                    // Validate email and phone
                    const emailInput = document.getElementById('orderEmail');
                    const phoneInput = document.getElementById('orderPhone');
                    const emailError = document.getElementById('orderEmailError');
                    const phoneError = document.getElementById('orderPhoneError');
                    let valid = true;
                    // Email validation
                    if (!emailInput.value || !/^\S+@\S+\.\S+$/.test(emailInput.value)) {
                        emailInput.classList.add('is-invalid');
                        emailError.style.display = '';
                        valid = false;
                    } else {
                        emailInput.classList.remove('is-invalid');
                        emailError.style.display = 'none';
                    }
                    // Phone validation (only check not empty)
                    if (!phoneInput.value) {
                        phoneInput.classList.add('is-invalid');
                        phoneError.style.display = '';
                        valid = false;
                    } else {
                        phoneInput.classList.remove('is-invalid');
                        phoneError.style.display = 'none';
                    }
                    if (!valid) return;
                    // Compose delivery address
                    const barangay = document.getElementById('orderBarangay').value.trim();
                    const sitio = document.getElementById('orderSitio').value.trim();
                    const landmarks = document.getElementById('orderLandmarks').value.trim();
                    let deliveryAddress = '';
                    if (sitio) deliveryAddress += sitio;
                    if (barangay) deliveryAddress += ', ' + barangay;
                    deliveryAddress += ', Santa Cruz, Laguna';
                    if (landmarks) deliveryAddress += ' (' + landmarks + ')';
                    const deliveryNotesField = document.getElementById('orderDeliveryNotes');
                    let deliveryNotesValue = deliveryNotesField ? deliveryNotesField.value : '';
                    const orderedItemLine = `Ordered Item: ${selectedItemName} (₱${selectedItemPrice})`;
                    if (!deliveryNotesValue.startsWith(orderedItemLine)) {
                        deliveryNotesValue = `${orderedItemLine}\n` + deliveryNotesValue;
                    }
                    const payload = {
                        user_id: userId,
                        full_name: document.getElementById('orderFullName').value,
                        email: document.getElementById('orderEmail').value,
                        phone: document.getElementById('orderPhone').value,
                        delivery_address: deliveryAddress,
                        payment_method: document.getElementById('paymentMethodSelect').value,
                        notes: '',
                        delivery_notes: deliveryNotesValue,
                        subtotal: selectedItemPrice,
                        delivery_fee: 50,
                        total_amount: parseFloat(selectedItemPrice) + 50,
                        menu_item_id: itemId,  // Add the menu_item_id directly to the order
                        items: [
                            {
                                menu_item_id: itemId,
                                quantity: 1,
                                price: selectedItemPrice
                            }
                        ]
                    };
                    // Disable button and show spinner
                    const confirmOrderBtn = document.getElementById('confirmOrderBtn');
                    const confirmOrderBtnText = document.getElementById('confirmOrderBtnText');
                    const confirmOrderSpinner = document.getElementById('confirmOrderSpinner');
                    confirmOrderBtn.disabled = true;
                    confirmOrderBtnText.textContent = 'Processing...';
                    confirmOrderSpinner.style.display = '';
                    fetch('/api/order-food', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRFToken': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(data => {
                        confirmOrderBtn.disabled = false;
                        confirmOrderBtnText.textContent = 'Confirm Order';
                        confirmOrderSpinner.style.display = 'none';
                        if (data.success) {
                            // Hide Delivery Details modal if present
                            const deliveryDetailsModal = document.getElementById('deliveryDetailsModal');
                            if (deliveryDetailsModal && typeof bootstrap !== 'undefined') {
                                const ddModalInstance = bootstrap.Modal.getInstance(deliveryDetailsModal);
                                if (ddModalInstance) ddModalInstance.hide();
                            }
                            // Hide Food Menu modal if present
                            const foodMenuModal = document.getElementById('foodMenuModal');
                            if (foodMenuModal && typeof bootstrap !== 'undefined') {
                                const fmModalInstance = bootstrap.Modal.getInstance(foodMenuModal);
                                if (fmModalInstance) fmModalInstance.hide();
                            }
                            // Show the order success modal
                            const orderSuccessModal = new bootstrap.Modal(document.getElementById('orderSuccessModal'), {
                                backdrop: 'static',
                                keyboard: false
                            });
                            orderSuccessModal.show();
                        } else {
                            menuContent.innerHTML = `<div class='food-menu-content-card'><div class='text-center p-4'><strong style='font-size:1.4em;color:red;'>${data.message || 'Order placed!'}</strong></div></div>`;
                        }
                    })
                    .catch(err => {
                        confirmOrderBtn.disabled = false;
                        confirmOrderBtnText.textContent = 'Confirm Order';
                        confirmOrderSpinner.style.display = 'none';
                        menuContent.innerHTML = `<div class='food-menu-content-card'><div class='text-center text-danger p-4'><strong style='font-size:1.4em;'>Order failed. Please try again.</strong></div></div>`;
                    });
                };
            }
            const cancelBtn = document.getElementById('cancelOrderBtn');
            if (cancelBtn) {
                cancelBtn.onclick = function() {
                    location.reload();
                };
            }

            // Set up approved room dropdown to update delivery notes
            const approvedRoomSelect = document.getElementById('approvedRoomSelect');
            const deliveryNotes = document.getElementById('orderDeliveryNotes');
            function updateDeliveryNotesFromDropdown() {
                console.log('approvedBookings:', approvedBookings);
                const roomSelectValue = approvedRoomSelect.value;
                console.log('approvedRoomSelect.value:', roomSelectValue);
                // Try both string and number comparison
                const selected = approvedBookings.find(b => b.id == roomSelectValue || b.id === roomSelectValue);
                console.log('selected booking:', selected);
                let notes = '';
                let roomInfoDiv = document.getElementById('selectedRoomInfo');
                if (!roomInfoDiv) {
                    // Insert after orderedFoodDisplay
                    const foodDisplay = document.getElementById('orderedFoodDisplay');
                    roomInfoDiv = document.createElement('div');
                    roomInfoDiv.id = 'selectedRoomInfo';
                    roomInfoDiv.style = 'margin: 8px 0 0 0; font-size: 1.05em; color: #4e342e; font-weight: 500;';
                    if (foodDisplay && foodDisplay.parentNode) {
                        foodDisplay.parentNode.insertBefore(roomInfoDiv, foodDisplay.nextSibling);
                    }
                }
                if (selected) {
                    notes = `Room ${selected.room_number} (${selected.room_type}) - Booking #${selected.id}`;
                    roomInfoDiv.textContent = `Room: ${selected.room_number} (${selected.room_type}) | Booking #${selected.id}`;
                    roomInfoDiv.style.display = '';
                    console.log('Room info div updated:', roomInfoDiv.textContent);
                } else {
                    roomInfoDiv.textContent = '';
                    roomInfoDiv.style.display = 'none';
                    console.log('Room info div hidden');
                }
                // Fix: define orderedItemLine here
                const orderedItemLine = `Ordered Item: ${selectedItemName} (₱${selectedItemPrice})`;
                if (selectedItemName && selectedItemPrice) {
                    notes = `${orderedItemLine}\n` + notes;
                }
                deliveryNotes.value = notes;
            }
            if (approvedRoomSelect && deliveryNotes) {
                approvedRoomSelect.addEventListener('change', updateDeliveryNotesFromDropdown);
            }
            // Always update delivery notes after rendering the form
            if (deliveryNotes) {
                    updateDeliveryNotesFromDropdown();
            }

            // Payment method QR logic
            const paymentSelect = document.getElementById('paymentMethodSelect');
            const gcashQrSection = document.getElementById('gcashQrSection');
            const showGcashQrBtn = document.getElementById('showGcashQrBtn');
            if (paymentSelect && gcashQrSection) {
                paymentSelect.addEventListener('change', function() {
                    if (this.value === 'gcash' || this.value === 'half_payment') {
                        gcashQrSection.style.display = '';
                    } else {
                        gcashQrSection.style.display = 'none';
                    }
                });
            }
            if (showGcashQrBtn) {
                showGcashQrBtn.onclick = function(e) {
                    e.preventDefault();
                    // Move the modal to body if not already there
                    const modalEl = document.getElementById('gcashQrModal');
                    if (modalEl && modalEl.parentNode !== document.body) {
                        document.body.appendChild(modalEl);
                    }
                    // Open with static backdrop
                    const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: true, focus: true });
                    modal.show();
                };
            }
        }, 100);
    }
}); 
// Add a hover effect for order-food-btn (brown)
const style = document.createElement('style');
style.innerHTML = `.order-food-btn:hover { background: #4E342E !important; color: #fff !important; }
.order-food-btn { display: block; margin-left: auto; margin-right: auto; align-self: center !important; }

/* Animation for food menu grid */
.food-menu-grid.fade {
  opacity: 0;
  transition: opacity 0.3s;
}
.food-menu-grid.fade.show {
  opacity: 1;
  transition: opacity 0.3s;
}`;
document.head.appendChild(style); 

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('food-cat-btn')) {
        const cat = e.target.getAttribute('data-cat');
        // Remove active from all, add to clicked
        document.querySelectorAll('.food-cat-btn').forEach(btn => btn.classList.remove('active'));
        e.target.classList.add('active');
        const grid = document.querySelector('.food-menu-grid');
        if (grid) {
            grid.classList.remove('show');
            grid.classList.add('fade');
            setTimeout(() => {
                document.querySelectorAll('.food-menu-item').forEach(item => {
                    if (cat === 'All' || item.getAttribute('data-category') === cat) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
                grid.classList.add('show');
            }, 200);
            setTimeout(() => {
                grid.classList.remove('fade');
            }, 500);
        } else {
            document.querySelectorAll('.food-menu-item').forEach(item => {
                if (cat === 'All' || item.getAttribute('data-category') === cat) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    }
}); 

// After rendering the food category buttons and Your Orders button, add this event listener:
document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'yourOrdersCatBtn') {
        // Remove 'active' from all category buttons
        document.querySelectorAll('.food-cat-btn').forEach(btn => btn.classList.remove('active'));
        // Set 'active' on this button
        e.target.classList.add('active');
        // Load order history
        loadYourOrders();
    }
}); 

// Cancellation reason dropdown logic
const cancelReasonSelect = document.getElementById('cancel_reason_select');
const cancelOtherReasonGroup = document.getElementById('cancel_other_reason_group');
const cancelOtherReason = document.getElementById('cancel_other_reason');
if (cancelReasonSelect && cancelOtherReasonGroup) {
    cancelReasonSelect.addEventListener('change', function() {
        if (this.value === 'Other') {
            cancelOtherReasonGroup.style.display = 'block';
            cancelOtherReason.required = true;
        } else {
            cancelOtherReasonGroup.style.display = 'none';
            cancelOtherReason.required = false;
            cancelOtherReason.value = '';
        }
    });
}

// Update cancel booking logic to send both reason and other_reason
const confirmCancelBtn = document.getElementById('confirmCancel');
if (confirmCancelBtn) {
    confirmCancelBtn.addEventListener('click', function() {
        const form = document.getElementById('cancelBookingForm');
        const bookingId = document.getElementById('cancel_booking_id').value;
        const reasonSelect = document.getElementById('cancel_reason_select').value;
        const otherReasonTextarea = document.getElementById('cancel_other_reason').value;
        const modal = bootstrap.Modal.getInstance(document.getElementById('cancelBookingModal'));
        let reason = '';
        if (reasonSelect === 'Other') {
            if (!otherReasonTextarea) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required',
                    text: 'Please provide a reason for cancellation.',
                    confirmButtonColor: '#6D4C41'
                });
                return;
            }
            reason = otherReasonTextarea;
        } else {
            reason = reasonSelect;
        }
        if (!reason) {
            Swal.fire({
                icon: 'warning',
                title: 'Required',
                text: 'Please select a reason for cancellation.',
                confirmButtonColor: '#6D4C41'
            });
            return;
        }
        // Show loading state
        const confirmButton = document.getElementById('confirmCancel');
        const originalText = confirmButton.innerHTML;
        confirmButton.disabled = true;
        confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        fetch(`/bookings/${bookingId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRFToken': document.querySelector('input[name="csrf_token"]').value
            },
            body: JSON.stringify({
                reason: reason,
                reason_select: reasonSelect,
                other_reason: otherReasonTextarea
            }),
            credentials: 'same-origin'
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Failed to cancel booking');
            }
            return data;
        })
        .then(data => {
            // Close the modal first
            modal.hide();
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message || 'Booking cancelled successfully',
                showConfirmButton: false,
                timer: 2000
            }).then(() => {
                // Reload the page after the message
                window.location.reload();
            });
        })
        .catch(error => {
            console.error('Error:', error);
            // Show error message
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'An error occurred while cancelling the booking. Please try again.',
                confirmButtonColor: '#6D4C41'
            });
        })
        .finally(() => {
            // Reset button state
            confirmButton.disabled = false;
            confirmButton.innerHTML = originalText;
        });
    });
} 

// --- Add Your Orders Tab to Food Menu Modal ---
// (Function and event listener removed)

function renderCategoryBar() {
  // Render the category bar with 'Your Orders' right-aligned
  const categories = ['All', 'Rice Meals', 'Burgers', 'Desserts', 'Beverages'];
  let catHtml = '<div class="d-flex justify-content-between align-items-center mb-3" id="food-category-bar">';
  catHtml += '<div>';
  categories.forEach(cat => {
    catHtml += `<button class="food-cat-btn" data-cat="${cat}">${cat}</button> `;
  });
  catHtml += '</div>';
  catHtml += '<button class="food-cat-btn ms-3" id="yourOrdersCatBtn" data-cat="Your Orders">Your Orders</button>';
  catHtml += '</div>';
  return catHtml;
}

function loadYourOrders() {
  const content = document.getElementById('food-menu-content');
  // Hide the category bar if present
  const categoryBar = document.getElementById('food-category-bar');
  if (categoryBar) categoryBar.style.display = 'none';
  // Show Back to Menu button
  let backBtnHtml = `<div style="margin-bottom:14px;"><button id="backToMenuBtn" type="button" style="background:none;border:none;padding:0;margin:0;font-size:1.18em;font-weight:600;cursor:pointer;display:flex;align-items:center;white-space:nowrap;color:inherit;"><span style='font-size:1.28em;margin-right:10px;font-weight:700;'>&#8592;</span><span style='font-size:1em;font-weight:600;'>Back to Menu</span></button></div>`;
  content.innerHTML = backBtnHtml + '<div id="order-history-list"></div>';
  // Add event listener for Back to Menu button
  setTimeout(() => {
    const backBtn = document.getElementById('backToMenuBtn');
    if (backBtn) {
      backBtn.onclick = function() {
        // Show the category bar again
        const catBar = document.getElementById('food-category-bar');
        if (catBar) catBar.style.display = '';
        // Show the menu/categories view
        if (typeof showFoodMenuModal === 'function') {
          showFoodMenuModal();
        }
      };
    }
  }, 0);
  // Now render the order history
  fetch('/api/food-order-history')
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        document.getElementById('order-history-list').innerHTML = '<div class="text-center text-danger">Failed to load your orders.</div>';
        return;
      }
      if (!Array.isArray(data.orders) || data.orders.length === 0) {
        document.getElementById('order-history-list').innerHTML = '<div class="text-center text-muted">No food orders found.</div>';
        return;
      }
      let html = '<div class="order-history-grid">';
      data.orders.forEach(order => {
        const foodServiceIp = window.FOOD_SERVICE_IP || '192.168.1.9';
        const receiptUrl = `http://${foodServiceIp}/online-food-ordering/print_receipt.php?order_id=${order.id}`;
        // Determine status class
        let statusClass = '';
        let statusText = (order.status || '').toLowerCase();
        if (statusText === 'completed') statusClass = 'order-status-completed';
        else if (statusText === 'pending') statusClass = 'order-status-pending';
        else if (statusText === 'processing') statusClass = 'order-status-processing';
        else if (statusText === 'cancelled' || statusText === 'rejected') statusClass = 'order-status-cancelled';
        else statusClass = 'order-status-pending';
        // Capitalize first letter of status for display
        const displayStatus = order.status ? order.status.charAt(0).toUpperCase() + order.status.slice(1).toLowerCase() : '';
        
        // Get menu_item_id from various sources with fallbacks
        let menuItemId = order.menu_item_id || 0;
        
        // Fallback 1: Check if it's directly in the order object
        if ((!menuItemId || menuItemId === 0) && order.menu_item_id) {
          menuItemId = parseInt(order.menu_item_id);
        }
        
        // Fallback 2: Check the items array if available
        if ((!menuItemId || menuItemId === 0) && order.items && order.items.length > 0) {
          for (let i = 0; i < order.items.length; i++) {
            if (order.items[i].menu_item_id && parseInt(order.items[i].menu_item_id) > 0) {
              menuItemId = parseInt(order.items[i].menu_item_id);
              break;
            }
          }
        }
        
        // Fallback 3: For Fawna Hotel integration - extract from notes if possible
        if ((!menuItemId || menuItemId === 0) && order.delivery_notes) {
          const menuItemMatch = order.delivery_notes.match(/Ordered Item: (.*?) \(₱(\d+(\.\d+)?)\)/);
          if (menuItemMatch) {
            // We found the item name and price, now try to find the corresponding menu item ID
            const itemName = menuItemMatch[1];
            const itemPrice = menuItemMatch[2];
            
            // Make an API call to get the menu item ID based on name and price
            fetch(`/api/get-menu-item-by-name?name=${encodeURIComponent(itemName)}&price=${itemPrice}`)
              .then(res => res.json())
              .then(data => {
                if (data.success && data.menu_item_id) {
                  // Update the menu item ID in the DOM
                  const orderTile = document.querySelector(`.order-history-tile[data-order-id="${order.id}"]`);
                  if (orderTile) {
                    orderTile.setAttribute('data-menu-item-id', data.menu_item_id);
                    const rateBtn = orderTile.querySelector('.btn-rate-order');
                    if (rateBtn) {
                      rateBtn.setAttribute('data-menu-item-id', data.menu_item_id);
                      rateBtn.setAttribute('onclick', `rateOrder(${order.id}, ${data.menu_item_id})`);
                    }
                  }
                  
                  // Also update the order in the database
                  fetch('/api/update-order-menu-item', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json',
                      'X-CSRFToken': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                      order_id: order.id,
                      menu_item_id: data.menu_item_id
                    })
                  });
                }
              })
              .catch(err => console.error('Error fetching menu item ID:', err));
          }
        }
        
        // For debugging
        console.log(`Order #${order.id}: menu_item_id = ${menuItemId}`);
        
        // For completed orders without a rating, make sure we store the menuItemId in a data attribute for better retrieval
        const rateButton = order.status && order.status.toLowerCase() === 'completed' && !order.has_rating ? 
          `<button class="btn btn-rate-order full-width-rate" 
                  onclick="rateOrder(${order.id || 0}, ${menuItemId || 0})" 
                  data-order-id="${order.id || 0}" 
                  data-menu-item-id="${menuItemId || 0}">
              <i class='fas fa-star'></i> Rate
           </button>` : '';
        
        html += `<div class="order-history-tile" data-order-id="${order.id}" data-menu-item-id="${menuItemId || 0}">
          <div class="mb-2"><strong>Order #${order.id || ''}</strong> - ${order.created_at || ''}</div>
          <div><span>Status: </span><span class="order-status-badge ${statusClass}">${displayStatus}</span></div>
          <div>Total: ₱${order.total_amount || ''}</div>
          <div class="order-history-actions">
            <a class="btn btn-sm btn-primary" href="${receiptUrl}" target="_blank" download>View/Download Receipt</a>
            ${order.status && (order.status.toLowerCase() === 'pending' || order.status.toLowerCase() === 'processing') ? `<button class="btn btn-cancel-order" onclick="cancelOrder(${order.id || 0}, this)">Cancel</button>` : ''}
            ${order.status && (order.status.toLowerCase() !== 'pending' && order.status.toLowerCase() !== 'processing') ? `<button class="btn btn-sm btn-danger" onclick="deleteOrder(${order.id || 0}, this)">Delete</button>` : ''}
            ${rateButton}
          </div>
        </div>`;
      });
      html += '</div>';
      document.getElementById('order-history-list').innerHTML = html;
    })
    .catch(() => {
      document.getElementById('order-history-list').innerHTML = '<div class="text-center text-danger">Failed to load your orders.</div>';
    });
}

function deleteOrder(orderId, btn) {
  if (!confirm('Delete this order?')) return;
  btn.disabled = true;
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  fetch(`/api/delete-food-order/${orderId}`, {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRFToken': csrfToken
    }
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Remove the order card (order-history-tile) from the UI
        let card = btn.closest('.order-history-tile');
        if (card) card.remove();
      } else {
        alert('Failed to delete order.');
        btn.disabled = false;
      }
    })
    .catch(() => {
      alert('Failed to delete order.');
      btn.disabled = false;
    });
}

// Add cancelOrder function
function cancelOrder(orderId, btn) {
  if (!confirm('Cancel this order?')) return;
  btn.disabled = true;
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  fetch(`/api/cancel-food-order/${orderId}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRFToken': csrfToken
    }
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Update status to cancelled and remove the cancel button
        let card = btn.closest('.order-history-tile');
        if (card) {
          let statusDiv = card.querySelector('.order-status-badge');
          if (statusDiv) {
            statusDiv.textContent = 'Cancelled';
            statusDiv.classList.remove('order-status-pending', 'order-status-processing', 'order-status-completed');
            statusDiv.classList.add('order-status-cancelled');
          }
        btn.remove();
          // Re-render the Delete button if not present
          let actionsDiv = card.querySelector('.order-history-actions');
          if (actionsDiv && !actionsDiv.querySelector('.btn-danger')) {
            const orderId = card.querySelector('.order-status-badge').closest('.order-history-tile').querySelector('.mb-2').textContent.match(/Order #(\d+)/)[1];
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn btn-sm btn-danger';
            deleteBtn.textContent = 'Delete';
            deleteBtn.onclick = function() { deleteOrder(orderId, deleteBtn); };
            actionsDiv.appendChild(deleteBtn);
          }
        }
      } else {
        alert(data.message || 'Failed to cancel order.');
        btn.disabled = false;
      }
    })
    .catch(() => {
      alert('Failed to cancel order.');
      btn.disabled = false;
    });
}

function rateOrder(orderId, menuItemId) {
  console.log(`Starting rating process for order ${orderId} with menu_item_id ${menuItemId}`);
  
  // If menuItemId is 0 or null, fetch it from the order first
  if (!menuItemId || menuItemId === 0) {
    // First try to get it from the DOM
    const orderTile = document.querySelector(`.order-history-tile[data-order-id="${orderId}"]`);
    if (orderTile && orderTile.getAttribute('data-menu-item-id') && parseInt(orderTile.getAttribute('data-menu-item-id')) > 0) {
      menuItemId = parseInt(orderTile.getAttribute('data-menu-item-id'));
      console.log(`Found menu item ID ${menuItemId} from DOM for order ${orderId}`);
      fetchRatingAndShowModal(orderId, menuItemId);
      return;
    }
    
    // If not found in DOM, fetch it from the API
    fetch(`/api/get-order-menu-item?order_id=${orderId}`)
    .then(res => res.json())
    .then(data => {
        if (data.success && data.menu_item_id && data.menu_item_id > 0) {
          console.log(`Found menu item ID ${data.menu_item_id} for order ${orderId}`);
          // Use the menu item ID from the order
          menuItemId = data.menu_item_id;
          fetchRatingAndShowModal(orderId, menuItemId);
        } else {
          console.warn(`Could not find menu item ID for order ${orderId} from API`);
          
          // Try to extract from delivery notes
          fetch(`/api/get-order-details?order_id=${orderId}`)
            .then(res => res.json())
            .then(orderData => {
              if (orderData.success && orderData.order && orderData.order.delivery_notes) {
                const menuItemMatch = orderData.order.delivery_notes.match(/Ordered Item: (.*?) \(₱(\d+(\.\d+)?)\)/);
                if (menuItemMatch) {
                  const itemName = menuItemMatch[1];
                  const itemPrice = menuItemMatch[2];
                  
                  // Try to get the menu item ID by name and price
                  fetch(`/api/get-menu-item-by-name?name=${encodeURIComponent(itemName)}&price=${itemPrice}`)
                    .then(res => res.json())
                    .then(menuData => {
                      if (menuData.success && menuData.menu_item_id) {
                        console.log(`Found menu item ID ${menuData.menu_item_id} from name/price for order ${orderId}`);
                        menuItemId = menuData.menu_item_id;
                        
                        // Update the order with this menu item ID
                        fetch('/api/update-order-menu-item', {
                          method: 'POST',
                          headers: {
                            'Content-Type': 'application/json',
                            'X-CSRFToken': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                          },
                          body: JSON.stringify({
                            order_id: orderId,
                            menu_item_id: menuItemId
                          })
                        });
                        
                        fetchRatingAndShowModal(orderId, menuItemId);
                      } else {
                        console.warn(`Could not find menu item ID for order ${orderId} from name/price`);
                        // Get a default menu item
                        getDefaultMenuItem(orderId);
                      }
                    })
                    .catch(err => {
                      console.error("Error fetching menu item ID by name/price:", err);
                      // Get a default menu item
                      getDefaultMenuItem(orderId);
                    });
                } else {
                  console.warn(`Could not extract menu item from delivery notes for order ${orderId}`);
                  // Get a default menu item
                  getDefaultMenuItem(orderId);
                }
              } else {
                console.warn(`Could not get order details for order ${orderId}`);
                // Get a default menu item
                getDefaultMenuItem(orderId);
              }
            })
            .catch(err => {
              console.error("Error fetching order details:", err);
              // Get a default menu item
              getDefaultMenuItem(orderId);
            });
        }
      })
      .catch(err => {
        console.error("Error fetching menu item ID:", err);
        // Get a default menu item
        getDefaultMenuItem(orderId);
      });
  } else {
    // MenuItemId is valid, continue directly
    fetchRatingAndShowModal(orderId, menuItemId);
  }
}

// Helper function to get a default menu item and continue with rating
function getDefaultMenuItem(orderId) {
  fetch('/api/get-default-menu-item.php')
    .then(res => res.json())
    .then(data => {
      if (data.success && data.menu_item_id) {
        console.log(`Using default menu item ID ${data.menu_item_id} for order ${orderId}`);
        const menuItemId = data.menu_item_id;
        
        // Update the order with this menu item ID
        fetch('/api/update-order-menu-item', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRFToken': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          body: JSON.stringify({
            order_id: orderId,
            menu_item_id: menuItemId
          })
        });
        
        fetchRatingAndShowModal(orderId, menuItemId);
      } else {
        console.error("Could not get default menu item, using ID 1");
        fetchRatingAndShowModal(orderId, 1);
      }
    })
    .catch(err => {
      console.error("Error fetching default menu item:", err);
      fetchRatingAndShowModal(orderId, 1);
    });
}

function fetchRatingAndShowModal(orderId, menuItemId) {
  console.log(`Fetching rating for order ${orderId} with menu_item_id ${menuItemId}`);
  
  // Skip fetching existing rating and just show the modal
  showRateOrderModal(orderId, menuItemId, {});
}

function showRateOrderModal(orderId, menuItemId, existingRating) {
  // If menuItemId is still 0, use a default value instead of showing an error
  if (!menuItemId || menuItemId === 0) {
    console.warn(`Using default menu_item_id for order ${orderId} as none could be found`);
    // Use 1 as a default menu_item_id to allow rating to proceed
    menuItemId = 1;
  }

  // Check if the container exists, if not create it
  let container = document.getElementById('ratingsModalContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'ratingsModalContainer';
    document.body.appendChild(container);
  }
  
  // Add star rating CSS if not already added
  if (!document.getElementById('starRatingStyles')) {
    const style = document.createElement('style');
    style.id = 'starRatingStyles';
    style.innerHTML = `
      .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: center;
        font-size: 1.5em;
      }
      .star-rating input {
        display: none;
      }
      .star-rating label {
        color: #ddd;
        cursor: pointer;
        padding: 0 0.1em;
      }
      .star-rating label:before {
        content: '★';
      }
      .star-rating input:checked ~ label,
      .star-rating label:hover,
      .star-rating label:hover ~ label {
        color: #f90;
      }
      .rate-order-comment-box {
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 8px;
        font-family: inherit;
      }
      #rateOrderSubmitBtn {
        background-color: #6D4C41;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 8px;
      }
      #closeRateOrderModal {
        background-color: #f1f1f1;
        border: 1px solid #ccc;
        border-radius: 4px;
        cursor: pointer;
      }
    `;
    document.head.appendChild(style);
  }
  
  container.innerHTML = '';
  container.style.display = 'flex';
  container.style.position = 'fixed';
  container.style.top = '0';
  container.style.left = '0';
  container.style.width = '100vw';
  container.style.height = '100vh';
  container.style.background = 'rgba(0,0,0,0.25)';
  container.style.zIndex = '99999';
  container.style.alignItems = 'center';
  container.style.justifyContent = 'center';

  let modal = document.createElement('div');
  modal.style.background = '#fff';
  modal.style.padding = '32px';
  modal.style.borderRadius = '12px';
  modal.style.boxShadow = '0 4px 24px rgba(0,0,0,0.18)';
    modal.innerHTML = `
    <h2 style="margin-bottom:16px;">Rate Your Order</h2>
    <div class="star-rating" style="margin-bottom:18px;">
      <input type="radio" id="star5" name="rating" value="5"><label for="star5" title="5 stars"></label>
      <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars"></label>
      <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars"></label>
      <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars"></label>
      <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star"></label>
        </div>
    <textarea class="rate-order-comment-box" maxlength="100" rows="4" style="width:300px;height:100px;font-size:1.1em;" placeholder="Share your experience..."></textarea>
    <div style="text-align:right;font-size:0.97em;color:#a08b7b;margin-bottom:18px;">
      <span id="rateOrderWordCount">0</span>/100 words
          </div>
    <button id="rateOrderSubmitBtn" style="margin-top:16px;padding:8px 18px;font-size:1em;">Submit Review</button>
    <button id="closeRateOrderModal" style="margin-top:16px;padding:8px 18px;font-size:1em;">Close</button>
  `;
  container.appendChild(modal);

  // Set existing comment if present
  const commentBox = container.querySelector('.rate-order-comment-box');
  if (existingRating && existingRating.comment) {
    commentBox.value = existingRating.comment;
    container.querySelector('#rateOrderWordCount').textContent = commentBox.value.length;
  }
  commentBox.oninput = function() {
    container.querySelector('#rateOrderWordCount').textContent = commentBox.value.length;
  };
  setTimeout(() => { commentBox.focus(); }, 50);

  // Star rating logic
  let selectedRating = 0;
  if (existingRating && existingRating.rating) {
    selectedRating = parseInt(existingRating.rating);
    const starInput = container.querySelector(`#star${selectedRating}`);
    if (starInput) starInput.checked = true;
  }
  const starInputs = container.querySelectorAll('.star-rating input[type="radio"]');
  starInputs.forEach(input => {
    input.onclick = function() {
      selectedRating = parseInt(input.value);
    };
  });

  // Submit logic (add your fetch here)
  container.querySelector('#rateOrderSubmitBtn').onclick = function() {
    if (!selectedRating) {
      alert('Please select a rating.');
      return;
    }
    const review = commentBox.value;
    
    // Show loading state
    const submitBtn = container.querySelector('#rateOrderSubmitBtn');
    const closeBtn = container.querySelector('#closeRateOrderModal');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Submitting...';
    submitBtn.disabled = true;
    closeBtn.disabled = true;
    
    fetch('/api/submit_rating.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        order_id: orderId,
        menu_item_id: menuItemId,
        rating: selectedRating,
        comment: review
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Thank you for your review!');
        
        // Update the UI to show that the order has been rated
        const orderTile = document.querySelector(`.order-history-tile[data-order-id="${orderId}"]`);
        if (orderTile) {
          const rateBtn = orderTile.querySelector('.btn-rate-order');
          if (rateBtn) {
            rateBtn.remove();
          }
        }
        
        // Close the modal
      container.style.display = 'none';
      container.innerHTML = '';
      } else {
        alert('Failed to submit review: ' + (data.message || 'Unknown error'));
        
        // Reset button state
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        closeBtn.disabled = false;
      }
    })
    .catch(err => {
      console.error('Error submitting rating:', err);
      alert('Failed to submit review. Please try again.');
      
      // Reset button state
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
      closeBtn.disabled = false;
    });
  };
  container.querySelector('#closeRateOrderModal').onclick = function() {
    container.style.display = 'none';
    container.innerHTML = '';
  };
}

// Add the tab when the food menu modal is shown
const foodMenuModalEl2 = document.getElementById('foodMenuModal');
if (foodMenuModalEl2) {
  foodMenuModalEl2.addEventListener('show.bs.modal', addYourOrdersTab);
} 

// After rendering the Delivery Details form, add this event handler:
setTimeout(() => {
  const backBtn = document.getElementById('backFromDeliveryDetailsBtn');
  if (backBtn) {
    backBtn.onclick = function() {
      // Close the food menu modal
      const foodMenuModal = document.getElementById('foodMenuModal');
      if (foodMenuModal && typeof bootstrap !== 'undefined') {
        const modalInstance = bootstrap.Modal.getInstance(foodMenuModal);
        if (modalInstance) modalInstance.hide();
      }
    };
  }
}, 0); 
