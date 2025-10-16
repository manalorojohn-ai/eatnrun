<?php
// Prevent direct access to this file
if (!defined('CUSTOMER_SERVICE_ICON_INCLUDED')) {
    define('CUSTOMER_SERVICE_ICON_INCLUDED', true);
}
?>

<!-- Customer Service Icon -->
<div class="customer-service-icon" id="customerServiceIcon">
    <button class="cs-icon-button" id="csIconButton" title="Customer Service">
        <i class="fas fa-headset"></i>
        <span class="cs-tooltip">Need Help?</span>
    </button>
</div>

<!-- Chat Interface -->
<div class="chat-interface" id="chatInterface">
    <div class="chat-header">
        <div class="chat-title">
            <i class="fas fa-robot"></i>
            <span>Hi! I am Yummy Helper 😊</span>
        </div>
        <div class="chat-subtitle">How can I help you today?</div>
    </div>
    <div class="chat-actions">
        <button class="chat-action-btn" onclick="window.location.href='menu.php'">
            <i class="fas fa-utensils"></i> View Menu
        </button>
        <button class="chat-action-btn" onclick="window.location.href='#'">
            <i class="fas fa-map-marker-alt"></i> Our Locations
        </button>
        <button class="chat-action-btn" onclick="window.location.href='#'">
            <i class="fas fa-truck"></i> Delivery Info
        </button>
        <button class="chat-action-btn" onclick="window.location.href='#'">
            <i class="fas fa-credit-card"></i> Payment Methods
        </button>
    </div>
    <div class="chat-input-container">
        <input type="text" id="chatInput" placeholder="Type your message here..." class="chat-input">
        <button class="chat-send-btn">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<style>
/* Customer Service Icon Styles */
.customer-service-icon {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
}

.cs-icon-button {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--primary-color, #006C3B);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
    position: relative;
}

.cs-icon-button:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.cs-icon-button i {
    color: white;
    font-size: 24px;
}

.cs-tooltip {
    position: absolute;
    top: -40px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 14px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.cs-tooltip::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid rgba(0, 0, 0, 0.8);
}

.cs-icon-button:hover .cs-tooltip {
    opacity: 1;
    visibility: visible;
    top: -45px;
}

/* Chat Interface Styles */
.chat-interface {
    position: fixed;
    bottom: 100px;
    right: 30px;
    width: 320px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
    z-index: 999;
    display: none;
    overflow: hidden;
    transition: all 0.3s ease;
}

.chat-interface.active {
    display: block;
    animation: slideIn 0.3s ease;
}

.chat-header {
    background: var(--primary-color, #006C3B);
    color: white;
    padding: 20px;
}

.chat-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 5px;
}

.chat-subtitle {
    font-size: 14px;
    opacity: 0.9;
}

.chat-actions {
    padding: 15px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.chat-action-btn {
    background: #f8f9fa;
    border: none;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
    color: #333;
    transition: all 0.2s ease;
}

.chat-action-btn:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.chat-input-container {
    padding: 15px;
    display: flex;
    gap: 10px;
    border-top: 1px solid #eee;
}

.chat-input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 20px;
    outline: none;
    font-size: 14px;
}

.chat-input:focus {
    border-color: var(--primary-color, #006C3B);
}

.chat-send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color, #006C3B);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.chat-send-btn:hover {
    transform: scale(1.1);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .customer-service-icon {
        bottom: 20px;
        right: 20px;
    }

    .chat-interface {
        width: calc(100% - 40px);
        bottom: 90px;
        right: 20px;
    }

    .cs-icon-button {
        width: 50px;
        height: 50px;
    }

    .cs-icon-button i {
        font-size: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatIcon = document.getElementById('csIconButton');
    const chatInterface = document.getElementById('chatInterface');
    const chatInput = document.getElementById('chatInput');
    const sendButton = document.querySelector('.chat-send-btn');

    // Toggle chat interface
    chatIcon.addEventListener('click', function() {
        chatInterface.classList.toggle('active');
        if (chatInterface.classList.contains('active')) {
            chatInput.focus();
        }
    });

    // Close chat when clicking outside
    document.addEventListener('click', function(e) {
        if (!chatInterface.contains(e.target) && !chatIcon.contains(e.target)) {
            chatInterface.classList.remove('active');
        }
    });

    // Handle send button click
    sendButton.addEventListener('click', function() {
        const message = chatInput.value.trim();
        if (message) {
            // Here you can implement the chat functionality
            console.log('Message sent:', message);
            chatInput.value = '';
        }
    });

    // Handle enter key in input
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendButton.click();
        }
    });
});
</script> 