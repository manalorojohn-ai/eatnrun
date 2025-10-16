<?php
session_start();
require_once 'config/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Service - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: rgba(0, 108, 59, 0.1);
            --warning: #FFD700;
            --text-dark: #2D3748;
            --text-light: #718096;
            --white: #ffffff;
            --gray-100: #f7fafc;
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--gray-100);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .page-header h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .help-card {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .help-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .help-card-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .help-card-icon i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .help-card h3 {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .help-card p {
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        .help-card .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
        }

        .help-card .btn:hover {
            background: var(--primary-dark);
        }

        .faq-section {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 3rem;
        }

        .faq-section h2 {
            color: var(--primary);
            margin-bottom: 2rem;
            text-align: center;
        }

        .faq-item {
            border-bottom: 1px solid var(--primary-light);
            padding: 1.5rem 0;
        }

        .faq-question {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-weight: 500;
        }

        .faq-question i {
            color: var(--primary);
            transition: var(--transition);
        }

        .faq-answer {
            color: var(--text-light);
            margin-top: 1rem;
            display: none;
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        .faq-item.active .faq-answer {
            display: block;
        }

        .contact-section {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
        }

        .contact-section h2 {
            color: var(--primary);
            margin-bottom: 2rem;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .contact-item {
            padding: 1.5rem;
        }

        .contact-item i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .contact-item h3 {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .contact-item p {
            color: var(--text-light);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .help-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>How Can We Help You?</h1>
            <p>We're here to help and answer any question you might have</p>
        </div>

        <div class="help-grid">
            <div class="help-card">
                <div class="help-card-icon">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <h3>Call Support</h3>
                <p>Need immediate assistance? Our support team is available 24/7 to help you.</p>
                <a href="tel:0912 345 6789" class="btn">
                    <i class="fas fa-phone"></i> Call Now
                </a>
            </div>

            <div class="help-card">
                <div class="help-card-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3>Email Support</h3>
                <p>Send us an email and we'll get back to you within 24 hours.</p>
                <a href="mailto:eat&run@example.com" class="btn">
                    <i class="fas fa-envelope"></i> Email Us
                </a>
            </div>

            <div class="help-card">
                <div class="help-card-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3>Live Chat</h3>
                <p>Chat with our customer service team in real-time for quick answers.</p>
                <a href="#" class="btn" id="startChat">
                    <i class="fas fa-comment-dots"></i> Start Chat
                </a>
            </div>
        </div>

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
                    <p>eat&run@example.com</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <?php include 'includes/chat_interface.php'; ?>

    <script>
        // FAQ Toggle functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const faqItem = question.parentElement;
                faqItem.classList.toggle('active');
            });
        });

        // Live Chat Button
        document.getElementById('startChat').addEventListener('click', (e) => {
            e.preventDefault();
            alert('Live chat feature coming soon!');
        });
    </script>
</body>
</html> 