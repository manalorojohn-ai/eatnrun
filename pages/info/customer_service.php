<?php
require_once 'includes/config.php';
$page_title = 'Customer Service';
$current_page = 'customer_service.php';

// Pass extra styles for the customer service page
ob_start(); ?>
<link rel="stylesheet" href="assets/css/customer-service-enhanced.css">
<?php 
$extra_styles = ob_get_clean();

// Include standard header
include 'includes/ui/header.php';

// Include loader
include 'includes/ui/loader.php';

// Include navbar
include 'includes/ui/navbar.php';
?>

<div class="container py-5">
    <!-- Page Header -->
    <div class="page-header">
        <h1>How Can We Help You?</h1>
        <p>We're here to help and answer any question you might have about our service.</p>
    </div>

    <!-- Help Grid -->
    <div class="help-grid">
        <div class="help-card">
            <div class="help-card-icon">
                <i class="fas fa-phone-alt"></i>
            </div>
            <h3>Call Support</h3>
            <p>Need immediate assistance? Our support team is available 24/7 to help you.</p>
            <a href="tel:0912 345 6789" class="btn-help" aria-label="Call support">
                <i class="fas fa-phone"></i> Call Now
            </a>
        </div>

        <div class="help-card">
            <div class="help-card-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h3>Email Support</h3>
            <p>Send us an email and we'll get back to you within 24 hours.</p>
            <a href="mailto:eat&run@example.com" class="btn-help" aria-label="Email support">
                <i class="fas fa-envelope"></i> Email Us
            </a>
        </div>

        <div class="help-card">
            <div class="help-card-icon">
                <i class="fas fa-comments"></i>
            </div>
            <h3>Live Chat</h3>
            <p>Chat with our customer service team in real-time for quick answers.</p>
            <a href="#" class="btn-help" id="startChat" aria-label="Start live chat">
                <i class="fas fa-comment-dots"></i> Start Chat
            </a>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section">
        <h2>Frequently Asked Questions</h2>
        
        <div class="faq-item">
            <div class="faq-question">
                <span>How do I place an order?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>To place an order, simply browse our menu, select your items, add them to your cart, and proceed to checkout. You'll need to be logged in to complete your order.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>What payment methods do you accept?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>We accept various payment methods including credit/debit cards, PayPal, and cash on delivery. All online payments are secure and encrypted.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>How long does delivery take?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Delivery times typically range from 30-45 minutes depending on your location and order volume. You can track your order in real-time through our app.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>What is your refund policy?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>If you're not satisfied with your order, please contact us within 24 hours of delivery. We'll review your case and process a refund if applicable.</p>
            </div>
        </div>
    </div>

    <!-- Contact Details -->
    <div class="contact-section">
        <h2>Get in Touch</h2>
        <div class="contact-grid">
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Visit Us</h3>
                <p>E. Taleon st, Santisima Cruz,<br>Philippines</p>
            </div>

            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <h3>Business Hours</h3>
                <p>Monday - Sunday<br>10:00 AM - 10:00 PM</p>
            </div>

            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <h3>Call Us</h3>
                <p>0912 345 6789</p>
            </div>

            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <h3>Email Us</h3>
                <p>help@eatrun.com</p>
            </div>
        </div>
    </div>
</div>

<?php 
// Include standard footer
include 'includes/ui/footer.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // FAQ Toggle
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', () => {
            const faqItem = question.parentElement;
            faqItem.classList.toggle('active');
        });
    });

    // Live Chat Placeholder
    const chatBtn = document.getElementById('startChat');
    if (chatBtn) {
        chatBtn.addEventListener('click', (e) => {
            e.preventDefault();
            alert('Live chat is coming soon! For now, please call us.');
        });
    }
});
</script>