<?php
require_once 'includes/config.php';
$page_title = 'About Us';
$current_page = 'about';

// Pass extra styles for the about page
ob_start(); ?>
<style>
    :root {
        --primary: #006C3B;
        --primary-dark: #005530;
        --primary-light: #e8f5e9;
        --primary-lighter: #f2f8f4;
        --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        --white: #fff;
        --gray-100: #f8f9fa;
        --gray-200: #eee;
        --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --transition-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
        --transition-smooth: cubic-bezier(0.4, 0, 0.2, 1);
        --transition-spring: cubic-bezier(0.68, -0.6, 0.32, 1.6);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1.5rem;
    }

    /* Features Section */
    .features {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
        max-width: 1200px;
        margin: 4rem auto;
        padding: 0 1.5rem;
    }

    .feature-card {
        background: linear-gradient(135deg, var(--white) 0%, var(--gray-100) 100%);
        backdrop-filter: blur(10px);
        padding: 2rem;
        border-radius: 16px;
        box-shadow: var(--shadow);
        text-align: center;
        transition: var(--transition-bounce);
        position: relative;
        overflow: hidden;
        animation: fadeInUp 0.6s var(--transition-bounce) forwards;
        opacity: 0;
        transform: translateY(30px);
    }

    @media (max-width: 992px) {
        .features { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
        .features { grid-template-columns: 1fr; }
        .feature-card { transform: none !important; animation: none !important; opacity: 1 !important; }
    }

    .feature-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
    }

    .feature-icon {
        width: 70px;
        height: 70px;
        background: var(--primary-light);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        transition: transform 0.8s var(--transition-spring);
    }

    .feature-icon i {
        font-size: 1.75rem;
        color: var(--primary);
    }

    .feature-title {
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .feature-description {
        color: #666;
        line-height: 1.7;
    }

    /* Team Section */
    .team-section {
        max-width: 1200px;
        margin: 5rem auto;
        padding: 0 1.5rem;
    }

    .team-section h2 {
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 3rem;
        position: relative;
    }

    .team-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .team-member {
        background: var(--white);
        border-radius: 20px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s var(--transition-spring);
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }

    .team-member:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .img-container {
        width: 180px;
        height: 180px;
        margin: 0 auto 1.5rem;
        border-radius: 50%;
        overflow: hidden;
        position: relative;
    }

    .img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .img-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 108, 59, 0.8);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: all 0.3s ease;
    }

    .team-member:hover .img-overlay { opacity: 1; }

    @media (max-width: 1200px) { .team-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 992px) { .team-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 576px) { .team-grid { grid-template-columns: 1fr; } }

    /* Contact & Map Styles */
    .contact-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2.5rem;
        margin: 4rem 0 6rem;
        opacity: 0;
        transform: translateY(30px);
        animation: fadeInUp 0.8s var(--transition-bounce) 0.6s forwards;
    }

    @media (max-width: 992px) { 
        .contact-section { grid-template-columns: 1fr; gap: 2rem; } 
    }

    .location-card, .message-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 2.2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
        border: 1px solid #eef2f6;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .location-card:hover, .message-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 36px rgba(0, 108, 59, 0.12);
    }

    .card-header-styled {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 1.6rem;
    }

    .card-header-styled .icon-circle {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: var(--primary-light);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .card-header-styled .section-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: #1e293b;
    }

    .card-header-styled .section-subtitle {
        font-size: 0.85rem;
        color: #64748b;
        margin: 2px 0 0;
    }

    /* Map Styling */
    .map-wrapper {
        position: relative;
        height: 270px;
        border-radius: 16px;
        overflow: hidden;
        border: 1.5px solid #e2e8f0;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .map-wrapper iframe {
        width: 100%;
        height: 100%;
        border: 0;
    }

    .map-floating-badge {
        position: absolute;
        bottom: 12px;
        right: 12px;
        background: #ffffff;
        color: #006C3B;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 30px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        z-index: 5;
    }

    .map-floating-badge:hover {
        background: #006C3B;
        color: #ffffff;
        transform: translateY(-2px);
    }

    .location-details-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .loc-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .loc-item .loc-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #e8f5e9;
        color: #006C3B;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .loc-item strong {
        display: block;
        font-size: 0.88rem;
        color: #0f172a;
        margin-bottom: 2px;
    }

    .loc-item p {
        font-size: 0.84rem;
        color: #64748b;
        margin: 0;
    }

    /* Modern Contact Form */
    .form-floating-group {
        margin-bottom: 1.25rem;
    }

    .form-floating-group label {
        display: block;
        font-size: 0.86rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
    }

    .modern-input {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
        font-size: 0.92rem;
        color: #1e293b;
        transition: all 0.25s ease;
    }

    .modern-input:focus {
        border-color: #006C3B;
        background: #ffffff;
        box-shadow: 0 0 0 3.5px rgba(0, 108, 59, 0.12);
        outline: none;
    }

    .btn-send-modern {
        width: 100%;
        padding: 14px 24px;
        background: linear-gradient(135deg, #006C3B 0%, #008749 100%);
        color: #ffffff;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.96rem;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 6px 18px rgba(0, 108, 59, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-send-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0, 108, 59, 0.35);
        color: #ffffff;
    }

    /* Learn More Button */
    .learn-more-container { text-align: center; margin: 3rem 0 5rem; }
    .learn-more-btn {
        display: inline-block; background: var(--primary-gradient); color: #fff;
        padding: 1rem 2.5rem; border-radius: 50px; text-decoration: none;
        transition: all 0.5s var(--transition-spring);
    }

    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
</style>
<?php 
$extra_styles = ob_get_clean();

include 'includes/ui/header.php';
include 'includes/ui/loader.php';
include 'includes/ui/navbar.php';
?>

    <div class="container">
        <!-- Features Section -->
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-history"></i></div>
                <h3 class="feature-title">Our Story</h3>
                <p class="feature-description">Founded in 2025, Eat&Run started with a simple mission: to connect hungry customers with their favorite local restaurants. We've grown from a small startup to a trusted food delivery service.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-star"></i></div>
                <h3 class="feature-title">Why Choose Us</h3>
                <p class="feature-description">We pride ourselves on fast delivery, restaurant variety, and excellent customer service. Our platform makes ordering food as simple as a few clicks.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-users"></i></div>
                <h3 class="feature-title">Our Community</h3>
                <p class="feature-description">We work closely with local restaurants and delivery partners to create a seamless food delivery experience for our growing community.</p>
            </div>
        </div>

        <div class="learn-more-container">
            <a href="mission-vision" class="learn-more-btn">Learn More</a>
        </div>

        <!-- Team Section -->
        <section class="team-section">
            <h2>Meet The Wonder Pets</h2>
            <div class="team-grid">
                <?php
                $team = [
                    ['name' => 'Anton Ramos', 'role' => 'Front-end / Documentation', 'img' => 'anton.jpg'],
                    ['name' => 'Ken Coladilla', 'role' => 'Backend / Documentation', 'img' => 'ken.jpg'],
                    ['name' => 'Rojohn Manalo', 'role' => 'Backend / Documentation', 'img' => 'rojohn.jpg'],
                    ['name' => 'JB Areza', 'role' => 'Front-end / Documentation', 'img' => 'jb.jpg']
                ];
                foreach ($team as $member):
                ?>
                <div class="team-member">
                    <div class="img-container">
                        <img src="assets/images/team/<?php echo $member['img']; ?>" alt="<?php echo $member['name']; ?>" onerror="this.src='assets/images/default-avatar.png';">
                        <div class="img-overlay">
                            <div class="overlay-icons">
                                <a href="#" class="btn btn-light btn-sm rounded-circle mx-1"><i class="fab fa-github"></i></a>
                                <a href="#" class="btn btn-light btn-sm rounded-circle mx-1"><i class="fab fa-linkedin"></i></a>
                            </div>
                        </div>
                    </div>
                    <h3><?php echo $member['name']; ?></h3>
                    <p><?php echo $member['role']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Contact & Location Section -->
        <div class="contact-section">
            <!-- Location & Map Card -->
            <div class="location-card">
                <div class="card-header-styled">
                    <div class="icon-circle"><i class="fas fa-location-dot"></i></div>
                    <div>
                        <h2 class="section-title mb-0">Visit Us</h2>
                        <p class="section-subtitle">Drop by our main branch or find us on Google Maps</p>
                    </div>
                </div>

                <div class="map-wrapper">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d965.6697706894911!2d121.40925692840576!3d14.282423989826446!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397e3d8ded519df%3A0x9c59944f57e731f9!2s518%20E%20Taleon%20St%2C%20Santa%20Cruz%2C%20Calabarzon!5e0!3m2!1sen!2sph!4v1648883811479!5m2!1sen!2sph" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Eat&Run Location Map">
                    </iframe>
                    <a href="https://maps.google.com/?q=14.282423989826446,121.40925692840576" target="_blank" rel="noopener" class="map-floating-badge">
                        <i class="fas fa-arrow-up-right-from-square"></i> Open in Maps
                    </a>
                </div>

                <div class="location-details-list">
                    <div class="loc-item">
                        <div class="loc-icon"><i class="fas fa-map-pin"></i></div>
                        <div>
                            <strong>Main Kitchen & Office</strong>
                            <p>E. Taleon St, Santisima Cruz, Santa Cruz, Laguna, Philippines</p>
                        </div>
                    </div>
                    <div class="loc-item">
                        <div class="loc-icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <strong>Contact Hotline</strong>
                            <p>0912 345 6789 / (049) 501-2345</p>
                        </div>
                    </div>
                    <div class="loc-item">
                        <div class="loc-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <strong>Customer Support</strong>
                            <p>eat&run@example.com</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Form Card -->
            <div class="message-card">
                <div class="card-header-styled">
                    <div class="icon-circle"><i class="fas fa-paper-plane"></i></div>
                    <div>
                        <h2 class="section-title mb-0">Send us a Message</h2>
                        <p class="section-subtitle">We would love to hear feedback, questions, or inquiries</p>
                    </div>
                </div>

                <form id="contactForm" class="modern-contact-form">
                    <div class="form-floating-group">
                        <label><i class="fas fa-user me-2 text-success"></i> Your Full Name</label>
                        <input type="text" name="name" class="modern-input" placeholder="e.g. Maria Santos" required>
                    </div>
                    <div class="form-floating-group">
                        <label><i class="fas fa-envelope me-2 text-success"></i> Email Address</label>
                        <input type="email" name="email" class="modern-input" placeholder="e.g. maria@example.com" required>
                    </div>
                    <div class="form-floating-group">
                        <label><i class="fas fa-comment-dots me-2 text-success"></i> Your Message</label>
                        <textarea name="message" class="modern-input" placeholder="Write your inquiry or feedback here..." rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn-send-modern">
                        <i class="fas fa-paper-plane me-2"></i> Send Message
                    </button>
                </form>
                <div id="formResponse" class="mt-3 text-center" style="display: none;"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const contactForm = document.getElementById('contactForm');
            const formResponse = document.getElementById('formResponse');

            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const button = this.querySelector('button');
                    const originalText = button.innerHTML;
                    
                    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending...';
                    button.disabled = true;

                    fetch('submit_message', {
                        method: 'POST',
                        body: new FormData(this)
                    })
                    .then(r => r.json())
                    .then(data => {
                        formResponse.style.display = 'block';
                        formResponse.textContent = data.message;
                        formResponse.className = data.success ? 'mt-3 text-success fw-bold' : 'mt-3 text-danger fw-bold';
                        
                        if (data.success) {
                            contactForm.reset();
                            button.innerHTML = '<i class="fas fa-check me-2"></i> Sent!';
                        } else {
                            button.innerHTML = originalText;
                            button.disabled = false;
                        }

                        setTimeout(() => {
                            formResponse.style.display = 'none';
                            button.innerHTML = originalText;
                            button.disabled = false;
                        }, 5000);
                    });
                });
            }
        });
    </script>

    <?php include 'includes/ui/footer.php'; ?>