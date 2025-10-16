<!-- Add Font Awesome for social icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
:root {
    --primary: #006C3B;
    --warning: #FFD700;
}

footer {
    background: var(--primary);
    padding: 1rem 0 0.5rem;
    color: white;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr;
    gap: 1rem;
}

.footer-section h3 {
    color: #FFD700;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
}

.footer-section p {
    color: white;
    font-size: 0.75rem;
    line-height: 1.3;
    margin-bottom: 0.3rem;
}

.social-links {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.social-links a {
    color: white;
    font-size: 1rem;
    transition: color 0.2s ease;
}

.social-links a:hover {
    color: var(--warning);
}

.footer-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-section ul li {
    margin-bottom: 0.3rem;
}

.footer-section ul li a {
    color: white;
    text-decoration: none;
    font-size: 0.75rem;
    transition: color 0.2s ease;
}

.footer-section ul li a:hover {
    color: var(--warning);
}

.footer-section i {
    margin-right: 0.4rem;
    color: var(--warning);
}

.copyright {
    text-align: center;
    padding-top: 0.5rem;
    margin-top: 0.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.7rem;
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 1rem;
    }

    .social-links {
        justify-content: center;
    }

    .footer-section i {
        width: 16px;
        text-align: center;
    }
}
</style><footer>
    <div class="footer-content">
        <div class="footer-section">
            <h3>About Eat&Run</h3>
            <p>Your favorite local restaurants delivered to your doorstep. Fast, fresh, and convenient food delivery service.</p>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
        </div>

        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="about.php">About Us</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="customer_service.php"><i class="fas fa-headset"></i> Customer Service</a></li>
            </ul>
        </div>

        <div class="footer-section">
            <h3>Contact Info</h3>
            <p><i class="fas fa-phone"></i> 0948 030 4952</p>
            <p><i class="fas fa-envelope"></i> eat&run@example.com</p>
            <p><i class="fas fa-map-marker-alt"></i> E. Taleon st, Santisima Cruz, Philippines</p>
            <p><i class="fab fa-facebook"></i> facebook.com/eatnrun2019</p>
        </div>
    </div>

    <div class="copyright">
        © <?php echo date('Y'); ?> Eat&Run. All rights reserved.
    </div>
</footer>

