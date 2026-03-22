<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch admin information from users table
$admin_query = "SELECT * FROM users WHERE id = ? AND role = 'admin'";
$stmt = mysqli_prepare($conn, $admin_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$admin_result = mysqli_stmt_get_result($stmt);
$admin_data = mysqli_fetch_assoc($admin_result);

// If no data found, set default values
if (!$admin_data) {
    $admin_data = [
        'full_name' => 'Administrator',
        'profile_image' => null
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Service - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: rgba(0, 108, 59, 0.1);
            --white: #ffffff;
            --text-dark: #2D3748;
            --text-light: #718096;
            --border-light: #E2E8F0;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-hover: 0 8px 16px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
            --border-radius: 12px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
            color: var(--text-dark);
        }

        .main-content {
            margin-left: 240px;
            padding: 2rem;
            min-height: calc(100vh - 4rem);
        }

        .header-container {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .profile-section {
            position: relative;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            background: linear-gradient(45deg, var(--primary-light), rgba(0, 108, 59, 0.05));
            transition: var(--transition);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-info {
            text-align: right;
        }

        .profile-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-dark);
        }

        .profile-role {
            color: var(--primary);
            font-size: 0.9rem;
        }

        .profile-image {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--white);
            box-shadow: var(--shadow);
        }

        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .help-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .help-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .help-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .help-card-header i {
            font-size: 1.5rem;
            color: var(--primary);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-light);
            border-radius: 50%;
        }

        .help-card h3 {
            margin: 0;
            color: var(--text-dark);
        }

        .faq-section {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .faq-item {
            border-bottom: 1px solid var(--border-light);
            padding: 1rem 0;
        }

        .faq-question {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding: 1rem;
            background: var(--primary-light);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .faq-question h4 {
            margin: 0;
            color: var(--primary-dark);
        }

        .faq-answer {
            padding: 0 1rem;
            color: var(--text-light);
            display: none;
        }

        .faq-item.active .faq-answer {
            display: block;
        }

        .contact-info {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .contact-item i {
            color: var(--primary);
            font-size: 1.2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            font-weight: 500;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .help-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Footer Styles */
        .footer {
            background: var(--white);
            padding: 2rem;
            margin-top: 3rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            box-shadow: var(--shadow);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-section h3 {
            color: var(--text-dark);
            font-size: 1.2rem;
            margin-bottom: 1rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background: var(--primary);
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-links a:hover {
            color: var(--primary);
            transform: translateX(5px);
        }

        .footer-links i {
            font-size: 0.875rem;
            color: var(--primary);
        }

        .footer-bottom {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-light);
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .social-links a:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-section h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .footer-links a {
                justify-content: center;
            }

            .social-links {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/loader.php'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="header-container">
            <div class="header-content">
                <div class="page-title">
                    <i class="fas fa-headset"></i>
                    <h1>Customer Service</h1>
                </div>
                <div class="profile-section">
                    <div class="profile-info">
                        <div class="profile-name"><?php echo htmlspecialchars($admin_data['full_name']); ?></div>
                        <div class="profile-role">Administrator</div>
                    </div>
                    <?php if (!empty($admin_data['profile_image'])): ?>
                        <img src="../uploads/profile_photos/<?php echo htmlspecialchars($admin_data['profile_image']); ?>" alt="Profile" class="profile-image">
                    <?php else: ?>
                        <img src="../assets/images/default-avatar.png" alt="Default Profile Photo" class="profile-image">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="help-grid">
            <div class="help-card">
                <div class="help-card-header">
                    <i class="fas fa-phone-alt"></i>
                    <h3>Contact Support</h3>
                </div>
                <p>Need immediate assistance? Our support team is available 24/7.</p>
                <a href="tel:+1234567890" class="btn">
                    <i class="fas fa-phone"></i> Call Support
                </a>
            </div>

            <div class="help-card">
                <div class="help-card-header">
                    <i class="fas fa-envelope"></i>
                    <h3>Email Support</h3>
                </div>
                <p>Send us an email and we'll get back to you within 24 hours.</p>
                <a href="mailto:eat&run@example.com" class="btn">
                    <i class="fas fa-envelope"></i> Send Email
                </a>
            </div>

            <div class="help-card">
                <div class="help-card-header">
                    <i class="fas fa-book"></i>
                    <h3>Documentation</h3>
                </div>
                <p>Access our comprehensive documentation and user guides.</p>
                <a href="#" class="btn">
                    <i class="fas fa-external-link-alt"></i> View Docs
                </a>
            </div>
        </div>

        <div class="faq-section">
            <h2>Frequently Asked Questions</h2>
            <div class="faq-item">
                <div class="faq-question">
                    <h4>How do I update menu items?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>To update menu items, go to the Menu Items section, click on the Edit button next to the item you want to modify, make your changes, and click Save.</p>
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    <h4>How do I process orders?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Navigate to the Orders section, find the order you want to process, and use the status dropdown to update its status. Don't forget to click Update to save changes.</p>
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    <h4>How do I manage user accounts?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>In the Users section, you can view all user accounts, edit their information, or deactivate accounts if necessary. You can also add new users using the Add New User button.</p>
                </div>
            </div>
        </div>

        <div class="contact-info">
            <h2>Contact Information</h2>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <p>123 Restaurant Street, Food City, FC 12345</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <p>0912 345 6789</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <p>eat&run@example.com</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <p>Support Hours: 24/7</p>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                    <li><a href="menu_items.php"><i class="fas fa-utensils"></i> Menu Items</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Help & Support</h3>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-book"></i> Documentation</a></li>
                    <li><a href="#"><i class="fas fa-question-circle"></i> FAQs</a></li>
                    <li><a href="#"><i class="fas fa-headset"></i> Live Support</a></li>
                    <li><a href="#"><i class="fas fa-envelope"></i> Contact Us</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Connect With Us</h3>
                <ul class="footer-links">
                    <li><a href="tel:0912 345 6789"><i class="fas fa-phone"></i> 0912 345 6789</a></li>
                    <li><a href="mailto:eat&run@example.com"><i class="fas fa-envelope"></i> eat&run@example.com</a></li>
                    <li><a href="#"><i class="fas fa-map-marker-alt"></i> 123 Restaurant Street</a></li>
                </ul>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Eat&Run Admin Dashboard. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // FAQ Toggle functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const faqItem = question.parentElement;
                faqItem.classList.toggle('active');
                
                // Update icon
                const icon = question.querySelector('i');
                icon.classList.toggle('fa-chevron-down');
                icon.classList.toggle('fa-chevron-up');
                
                // Toggle answer visibility with animation
                const answer = faqItem.querySelector('.faq-answer');
                if (faqItem.classList.contains('active')) {
                    answer.style.display = 'block';
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                } else {
                    answer.style.maxHeight = '0';
                    setTimeout(() => {
                        answer.style.display = 'none';
                    }, 300);
                }
            });
        });

        // Add hover effect to help cards
        document.querySelectorAll('.help-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
                card.style.boxShadow = var(--shadow-hover);
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = var(--shadow);
            });
        });
    </script>
</body>
</html> 