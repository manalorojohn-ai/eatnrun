<?php
// Remove the navbar include and add proper session handling
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission & Vision - Eat&Run</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Library for Scroll Animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Add custom scrollbar -->
    <link rel="stylesheet" href="https://unpkg.com/simplebar@5.3.6/dist/simplebar.min.css">
    <!-- Add Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #006C3B;
            --primary-light: #008C4C;
            --primary-dark: #005530;
            --warning: #FFD700;
            --dark: #2C2C2C;
            --gray: #666;
            --white: #ffffff;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        /* Improved Header Section */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 8rem 0 6rem;
            position: relative;
            overflow: hidden;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
            animation: scale-in 1s ease-out;
        }

        .page-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background-size: 200% 200%;
            background-image: linear-gradient(
                45deg,
                rgba(255,255,255,0.1) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255,255,255,0.1) 50%,
                rgba(255,255,255,0.1) 75%,
                transparent 75%,
                transparent
            );
            animation: shimmer 8s linear infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(0,108,59,0.4); }
            50% { box-shadow: 0 0 30px 5px rgba(0,108,59,0.2); }
        }

        @keyframes slide-in-left {
            0% { transform: translateX(-100px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        @keyframes slide-in-right {
            0% { transform: translateX(100px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        @keyframes scale-in {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes rotate-clockwise {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes rotate-counterclockwise {
            from { transform: rotate(360deg); }
            to { transform: rotate(0deg); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        .page-title {
            color: var(--white);
            font-weight: 800;
            text-align: center;
            margin: 0;
            position: relative;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            font-size: 3.5rem;
            letter-spacing: -0.5px;
            animation: fadeInDown 1s ease-out;
        }

        .page-title::after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: var(--warning);
            margin: 1rem auto;
            border-radius: 2px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Enhanced Card Styles */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            transition: var(--transition);
            overflow: hidden;
            height: 100%;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            transform-origin: center;
        }

        .card:hover {
            transform: translateY(-15px) scale(1.02);
            animation: pulse-glow 2s infinite;
        }

        .card-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: var(--white);
            font-size: 2.5rem;
            position: relative;
            z-index: 1;
            transition: var(--transition);
        }

        .card-icon::before,
        .card-icon::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: var(--primary-light);
            opacity: 0.3;
            transition: var(--transition);
        }

        .card-icon::before {
            inset: -50%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: rotate(45deg);
            transition: 0.5s;
            animation: rotate-clockwise 8s linear infinite;
        }

        .card:hover .card-icon::before {
            animation: rotate-counterclockwise 4s linear infinite;
        }

        .card:hover .card-icon::after {
            opacity: 0.15;
            animation: pulse 2s infinite 0.3s;
        }

        /* Improved Values Section */
        .values-section {
            background: var(--white);
            border-radius: 30px;
            padding: 6rem 0;
            margin: 6rem 0;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .values-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--warning) 100%);
        }

        .values-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 50%, rgba(0,108,59,0.05) 0%, transparent 70%);
            animation: pulse-glow 4s infinite;
            pointer-events: none;
        }

        .values-card {
            animation: float 6s ease-in-out infinite;
            animation-delay: calc(var(--animation-order) * 0.2s);
        }

        /* Enhanced Goals Section */
        .goals-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 8rem 0;
            position: relative;
            overflow: hidden;
            margin-bottom: 0;
        }

        .goals-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255,255,255,0.1) 0%, transparent 50%);
            opacity: 0.6;
            animation: rotate-clockwise 30s linear infinite;
        }

        .goal-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            padding: 3rem;
            text-align: center;
            color: var(--white);
            transition: var(--transition);
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
        }

        .goal-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 100%);
            opacity: 0;
            transition: var(--transition);
        }

        .goal-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent,
                rgba(255,255,255,0.1),
                transparent
            );
            transform: rotate(45deg);
            transition: 0.5s;
            opacity: 0;
        }

        .goal-card:hover {
            transform: translateY(-15px);
            border-color: rgba(255,255,255,0.3);
        }

        .goal-card:hover::before {
            opacity: 1;
        }

        .goal-card:hover::after {
            opacity: 1;
            animation: rotate-clockwise 2s linear infinite;
        }

        .goal-card .display-4 {
            color: var(--warning);
            opacity: 0.9;
            transition: var(--transition);
            margin-bottom: 1.5rem;
            font-size: 3rem;
            animation: float 4s ease-in-out infinite;
        }

        .goal-card:hover .display-4 {
            transform: scale(1.1);
            opacity: 1;
        }

        .goal-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .goal-card p {
            font-size: 1rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        /* Section Titles */
        .section-title {
            position: relative;
            margin-bottom: 4rem;
            padding-bottom: 1rem;
            font-weight: 800;
            font-size: 2.5rem;
            text-align: center;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--warning) 100%);
            border-radius: 2px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .page-header {
                padding: 6rem 0 4rem;
            }
            
            .page-title {
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 2rem;
                margin-bottom: 3rem;
            }
            
            .card {
                margin-bottom: 2rem;
            }

            .values-section {
                border-radius: 20px;
                padding: 4rem 0;
                margin: 4rem 0;
            }

            .goals-section {
                padding: 6rem 0;
            }

            .goal-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }

            .goal-card:last-child {
                margin-bottom: 0;
            }

            .goal-card .display-4 {
                font-size: 2.5rem;
            }

            .card:hover {
                transform: translateY(-10px) scale(1.01);
            }

            .values-card {
                animation: none;
            }

            .goal-card:hover {
                transform: translateY(-10px);
            }
        }

        /* Animation Classes */
        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .slide-in-left {
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .slide-in-left.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .slide-in-right {
            opacity: 0;
            transform: translateX(50px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .slide-in-right.visible {
            opacity: 1;
            transform: translateX(0);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 6px;
            border: 3px solid #f1f1f1;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-light);
        }

        /* Bootstrap Animation Extensions */
        .hover-grow {
            transition: transform 0.3s ease;
        }

        .hover-grow:hover {
            transform: scale(1.05);
        }

        .hover-lift {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .rotate-hover:hover {
            animation: rotate360 1s ease-in-out;
        }

        .pulse-on-hover:hover {
            animation: pulse 1s infinite;
        }

        /* Enhanced Bootstrap Card Animations */
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255,255,255,0.2),
                transparent
            );
            transition: 0.5s;
        }

        .card:hover::before {
            left: 100%;
            transition: 0.5s;
        }

        .card-hover-overlay {
            position: relative;
            overflow: hidden;
        }

        .card-hover-overlay::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, var(--primary-light), transparent);
            opacity: 0;
            transition: 0.3s ease;
        }

        .card-hover-overlay:hover::after {
            opacity: 0.1;
        }

        /* Bootstrap Button Animations */
        .btn {
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255,255,255,0.1);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
            z-index: -1;
        }

        .btn:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        /* Section Transition Effects */
        .section-fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: 1s ease;
        }

        .section-fade-in.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Custom Animations */
        @keyframes rotate360 {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes shine {
            from { transform: translateX(-100%) rotate(45deg); }
            to { transform: translateX(100%) rotate(45deg); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Enhanced Section Styles */
        .page-header::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shine 3s infinite;
        }

        .values-card {
            animation: float 6s infinite ease-in-out;
            animation-delay: calc(var(--animation-order) * 0.2s);
        }

        .goal-card {
            transition: var(--transition);
        }

        .goal-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .goal-card .icon-wrapper {
            transition: 0.3s ease;
        }

        .goal-card:hover .icon-wrapper {
            transform: scale(1.1) rotate(10deg);
        }

        /* Bootstrap Toast Animations */
        .toast.show {
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <?php include 'includes/ui/navbar.php'; ?>

    <header class="page-header">
        <div class="container">
            <h1 class="page-title animate__animated animate__fadeInDown">Our Mission, Vision & Values</h1>
        </div>
    </header>

    <div class="container py-5">
        <div class="row g-4 mb-5">
            <div class="col-md-6" data-aos="fade-right">
                <div class="card hover-lift card-hover-overlay h-100 p-4">
                    <div class="icon-wrapper rotate-hover mb-4">
                        <i class="fas fa-bullseye fa-3x text-primary"></i>
                    </div>
                    <div class="card-body">
                        <h2 class="h3 mb-4 fw-bold text-primary">Our Mission</h2>
                        <p class="card-text">
                            To provide convenient, reliable, and delicious food delivery services that connect local restaurants with hungry customers, while ensuring exceptional customer satisfaction and supporting local businesses.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6" data-aos="fade-left">
                <div class="card hover-lift card-hover-overlay h-100 p-4">
                    <div class="icon-wrapper rotate-hover mb-4">
                        <i class="fas fa-eye fa-3x text-primary"></i>
                    </div>
                    <div class="card-body">
                        <h2 class="h3 mb-4 fw-bold text-primary">Our Vision</h2>
                        <p class="card-text">
                            To become the leading food delivery platform that transforms how people experience food delivery, creating a seamless connection between restaurants and customers while fostering community growth.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="values-section section-fade-in">
            <div class="container">
                <h2 class="section-title text-primary" data-aos="fade-up">Our Core Values</h2>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="100">
                        <div class="card values-card hover-grow h-100 p-4">
                            <div class="icon-wrapper pulse-on-hover">
                                <i class="fas fa-heart fa-3x text-primary"></i>
                            </div>
                            <div class="card-body">
                                <h3 class="h4 mb-3 fw-bold">Customer First</h3>
                                <p class="card-text">We prioritize our customers' needs and satisfaction in everything we do.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="200">
                        <div class="card values-card hover-grow h-100 p-4">
                            <div class="icon-wrapper pulse-on-hover">
                                <i class="fas fa-handshake fa-3x text-primary"></i>
                            </div>
                            <div class="card-body">
                                <h3 class="h4 mb-3 fw-bold">Reliability</h3>
                                <p class="card-text">We deliver on our promises and maintain consistent quality in our services.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="300">
                        <div class="card values-card hover-grow h-100 p-4">
                            <div class="icon-wrapper pulse-on-hover">
                                <i class="fas fa-bolt fa-3x text-primary"></i>
                            </div>
                            <div class="card-body">
                                <h3 class="h4 mb-3 fw-bold">Innovation</h3>
                                <p class="card-text">We continuously improve our services through technological advancement.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="400">
                        <div class="card values-card hover-grow h-100 p-4">
                            <div class="icon-wrapper pulse-on-hover">
                                <i class="fas fa-users fa-3x text-primary"></i>
                            </div>
                            <div class="card-body">
                                <h3 class="h4 mb-3 fw-bold">Community</h3>
                                <p class="card-text">We support local businesses and contribute to community growth.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="goals-section">
        <div class="container">
            <h2 class="section-title text-white mb-5" data-aos="fade-up">Our Goals</h2>
            <div class="row g-4">
                <div class="col-md-4" data-aos="flip-left" data-aos-delay="100">
                    <div class="goal-card">
                        <div class="icon-wrapper mb-4">
                            <i class="fas fa-clock fa-3x text-warning"></i>
                        </div>
                        <h3>Fast Delivery</h3>
                        <p>Ensure quick and efficient delivery times for all orders.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="flip-left" data-aos-delay="200">
                    <div class="goal-card">
                        <div class="icon-wrapper mb-4">
                            <i class="fas fa-utensils fa-3x text-warning"></i>
                        </div>
                        <h3>Quality Food</h3>
                        <p>Partner with the best restaurants to deliver excellent food.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="flip-left" data-aos-delay="300">
                    <div class="goal-card">
                        <div class="icon-wrapper mb-4">
                            <i class="fas fa-smile fa-3x text-warning"></i>
                        </div>
                        <h3>Customer Satisfaction</h3>
                        <p>Maintain high standards of customer service and satisfaction.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/ui/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS
            AOS.init({
                duration: 800,
                once: true,
                offset: 100,
                easing: 'ease-in-out'
            });

            // Section fade in observer
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.section-fade-in').forEach(section => {
                observer.observe(section);
            });

            // Add stagger effect for cards
            document.querySelectorAll('.values-card').forEach((card, index) => {
                card.style.animationDelay = `${index * 0.2}s`;
            });

            // Parallax effect for goals section
            window.addEventListener('scroll', () => {
                const goalsSection = document.querySelector('.goals-section');
                if (goalsSection) {
                    const scrolled = window.pageYOffset;
                    goalsSection.style.backgroundPosition = `center ${scrolled * 0.5}px`;
                }
            });

            // Initialize Bootstrap tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html> 