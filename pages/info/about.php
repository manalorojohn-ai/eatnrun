<?php
require_once 'includes/config.php';
$page_title = 'About Us';
$current_page = 'about.php';

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

    /* Container & Grid Styles */
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

    /* Form Styles */
    .contact-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
        margin: 5rem 0;
        opacity: 0;
        transform: translateY(30px);
        animation: fadeInUp 0.8s var(--transition-bounce) 0.6s forwards;
    }

    @media (max-width: 992px) { .contact-section { grid-template-columns: 1fr; } }

    .contact-form {
        padding: 2rem;
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    .form-group { margin-bottom: 1.5rem; position: relative; }
    .form-group input, .form-group textarea {
        width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0;
        border-radius: 10px; background: #f8f9fa; transition: all 0.3s ease;
    }
    .form-group input:focus, .form-group textarea:focus { border-color: var(--primary); background: #fff; outline: none; }

    .btn-send {
        width: 100%; padding: 14px 24px; background: var(--primary); color: #fff;
        border: none; border-radius: 10px; font-weight: 600; cursor: pointer;
        transition: all 0.3s ease;
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

// Include standard header
include 'includes/ui/header.php';

// Include loader
include 'includes/ui/loader.php';

// Include navbar
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

        <!-- Contact Section -->
        <div class="contact-section">
            <div class="location-section">
                <h2 class="section-title">Visit Us</h2>
                <div class="map-container rounded-4 overflow-hidden shadow-sm" style="height: 350px;">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d965.6697706894911!2d121.40925692840576!3d14.282423989826446!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397e3d8ded519df%3A0x9c59944f57e731f9!2s518%20E%20Taleon%20St%2C%20Santa%20Cruz%2C%20Calabarzon!5e0!3m2!1sen!2sph!4v1648883811479!5m2!1sen!2sph" class="w-100 h-100 border-0"></iframe>
                </div>
                <div class="mt-4">
                    <p><i class="fas fa-map-marker-alt text-success me-2"></i> E. Taleon st, Santisima Cruz, Philippines</p>
                    <p><i class="fas fa-phone text-success me-2"></i> 0912 345 6789</p>
                    <p><i class="fas fa-envelope text-success me-2"></i> eat&run@example.com</p>
                </div>
            </div>

            <div class="message-section">
                <h2 class="section-title">Send us a Message</h2>
                <div class="contact-form">
                    <form id="contactForm">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <textarea name="message" placeholder="Your Message" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn-send">
                            <i class="fas fa-paper-plane me-2"></i> Send Message
                        </button>
                    </form>
                    <div id="formResponse" class="mt-3 text-center" style="display: none;"></div>
                </div>
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

                    fetch('actions/reviews/submit_message.php', {
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