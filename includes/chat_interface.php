<?php
if (!defined('CHAT_INTERFACE_INCLUDED')) {
    define('CHAT_INTERFACE_INCLUDED', true);
}

require_once 'api/chat_responses.php';
$chatHandler = new ChatResponseHandler();
?>

<!-- Chat Interface -->
<div class="chat-toggle" id="chatToggle">
    <i class="fas fa-comments"></i>
</div>

<div class="chat-interface" id="chatInterface">
    <div class="chat-header">
        <div class="bot-avatar">
            <i class="fas fa-robot"></i>
        </div>
        <div class="chat-title">
            Hi! I am Yummy Helper 😊
        </div>
    </div>

    <div class="chat-messages" id="chatMessages">
        <!-- Initial bot message -->
        <div class="message bot-message">
            <div class="message-content">
                How can I help you today?
            </div>
        </div>
        
        <!-- Default suggestion chips -->
        <div class="suggestion-chips">
            <button class="suggestion-chip">🍽️ View Menu</button>
            <button class="suggestion-chip">📍 Our Locations</button>
            <button class="suggestion-chip">🚚 Delivery Info</button>
            <button class="suggestion-chip">💳 Payment Methods</button>
        </div>
    </div>

    <div class="chat-input">
        <input type="text" id="messageInput" placeholder="Type your message here...">
        <button id="sendMessage" class="send-button">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<style>
.chat-interface {
    position: fixed;
    right: 30px;
    bottom: 100px;
    width: 380px;
    height: 500px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    z-index: 997;
    transform: translateY(20px);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.1);
}

.chat-interface.show {
    transform: translateY(0);
    opacity: 1;
    visibility: visible;
}

.chat-header {
    padding: 20px;
    background: #006C3B;
    color: white;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.bot-avatar {
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.bot-avatar i {
    color: #006C3B;
    font-size: 20px;
}

.chat-title {
    font-size: 16px;
    font-weight: 600;
}

.chat-messages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: #f8f9fa;
}

.message {
    max-width: 85%;
    padding: 12px 16px;
    border-radius: 15px;
    position: relative;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 4px;
}

.message-content {
    word-wrap: break-word;
    white-space: pre-wrap;
}

.message-content p {
    margin: 0;
    padding: 0;
    text-align: left;
}

.message-content br {
    display: block;
    content: "";
    margin-top: 4px;
}

.message-content br + br {
    margin-top: 8px;
}

.message-content • {
    display: inline-block;
    padding-left: 8px;
}

.message-content p br + • {
    margin-top: 4px;
}

.message-content p br + [class^="emoji"] {
    margin-left: 4px;
}

.bot-message {
    align-self: flex-start;
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    border-bottom-left-radius: 5px;
}

.user-message {
    align-self: flex-end;
    background: #006C3B;
    color: white;
    border-bottom-right-radius: 5px;
}

.message-time {
    font-size: 11px;
    color: rgba(0,0,0,0.5);
    margin-top: 4px;
}

.user-message .message-time {
    color: rgba(255,255,255,0.8);
}

.suggestion-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
    padding: 0 20px;
}

.suggestion-chip {
    background: rgba(0,108,59,0.1);
    color: #006C3B;
    border: 1px solid #006C3B;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.suggestion-chip:hover {
    background: #006C3B;
    color: white;
}

.feedback-container {
    display: flex;
    gap: 12px;
    margin-top: 10px;
    align-items: center;
    padding: 4px 0;
}

.feedback-question {
    font-size: 12px;
    color: #666;
    flex: 1;
}

.feedback-buttons {
    display: flex;
    gap: 12px;
}

.feedback-button {
    background: none;
    border: none;
    cursor: pointer;
    transition: transform 0.2s;
    font-size: 16px;
}

.feedback-button:hover {
    transform: scale(1.2);
}

.feedback-button.active {
    color: #006C3B;
}

.feedback-button.negative.active {
    color: #e74c3c;
}

.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 12px 16px;
    background: white;
    border-radius: 15px;
    align-self: flex-start;
    margin-bottom: 8px;
}

.typing-dot {
    width: 8px;
    height: 8px;
    background: #006C3B;
    border-radius: 50%;
    animation: typing 1s infinite;
}

.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.chat-input {
    padding: 15px 20px;
    background: white;
    border-top: 1px solid rgba(0,0,0,0.1);
    display: flex;
    gap: 10px;
}

#messageInput {
    flex: 1;
    border: 1px solid rgba(0,0,0,0.1);
    padding: 10px 15px;
    border-radius: 25px;
    outline: none;
    font-size: 14px;
}

#messageInput:focus {
    border-color: #006C3B;
}

#sendMessage {
    background: #006C3B;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

#sendMessage:hover {
    background: #005530;
}

#sendMessage:active {
    transform: translateY(0);
}

@media (max-width: 768px) {
    .chat-interface {
        right: 10px;
        left: 10px;
        bottom: 80px;
        width: auto;
        height: 60vh;
    }
    
    .message {
        max-width: 90%;
        font-size: 13px;
    }
    
    .suggestion-chip {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .chat-messages {
        padding: 15px;
    }
}

/* Custom scrollbar */
.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #ccc;
}

/* Add these new styles */
.emoji {
    display: inline-block;
    vertical-align: middle;
    margin: 0 2px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatToggle = document.getElementById('chatToggle');
    const chatInterface = document.getElementById('chatInterface');
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendMessage');
    const chatMessages = document.getElementById('chatMessages');

    // Toggle chat interface
    chatToggle.addEventListener('click', function() {
        chatInterface.classList.toggle('active');
        if (chatInterface.classList.contains('active')) {
            messageInput.focus();
        }
    });

    // Send message function
    function sendMessage() {
        const message = messageInput.value.trim();
        if (message === '') return;

        // Add user message
        addMessage(message, 'user');

        // Show typing indicator
        showTypingIndicator();

        // Send to backend
        fetch('/api/get_response.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                message: message,
                user_id: getUserId() // Implement this function based on your auth system
            })
        })
        .then(response => response.json())
        .then(data => {
            // Hide typing indicator
            hideTypingIndicator();
            
            // Add bot response
            addMessage(data.response, 'bot');
            
            // Add suggestion chips if available
            if (data.suggestions) {
                addSuggestionChips(data.suggestions);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            hideTypingIndicator();
            addMessage('Sorry, I encountered an error. Please try again.', 'bot');
        });

        // Clear input
        messageInput.value = '';
    }

    // Add message to chat
    function addMessage(text, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message`;
        
        const content = document.createElement('div');
        content.className = 'message-content';
        content.textContent = text;
        
        const time = document.createElement('div');
        time.className = 'message-time';
        time.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        messageDiv.appendChild(content);
        messageDiv.appendChild(time);
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Show typing indicator
    function showTypingIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'typing-indicator';
        indicator.innerHTML = `
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        `;
        chatMessages.appendChild(indicator);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Hide typing indicator
    function hideTypingIndicator() {
        const indicator = chatMessages.querySelector('.typing-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    // Add suggestion chips
    function addSuggestionChips(suggestions) {
        const chipsDiv = document.createElement('div');
        chipsDiv.className = 'suggestion-chips';
        
        suggestions.forEach(suggestion => {
            const chip = document.createElement('button');
            chip.className = 'suggestion-chip';
            chip.textContent = suggestion;
            chip.addEventListener('click', () => {
                messageInput.value = suggestion;
                sendMessage();
            });
            chipsDiv.appendChild(chip);
        });
        
        chatMessages.appendChild(chipsDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Event listeners
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
});
</script> 