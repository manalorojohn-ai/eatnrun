<!-- Load Footer Styles -->
<link rel="stylesheet" href="assets/css/footer.css">

<!-- Add Font Awesome for social icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<footer>
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
                <li><a href="about">About Us</a></li>
                <li><a href="menu">Menu</a></li>
                <li><a href="customer_service"><i class="fas fa-headset"></i> Customer Service</a></li>
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

<!-- External Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Project Specific Scripts -->
<?php if (isset($extra_scripts)) echo $extra_scripts; ?>

</body>
</html>
