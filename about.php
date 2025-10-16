<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: #e8f5e9;
            --primary-lighter: #f2f8f4;
            --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --text-dark: #000000;
            --text-light: #333333;
            --white: #fff;
            --gray-100: #f8f9fa;
            --gray-200: #eee;
            --gray-300: #e0e0e0;
            --gray-400: #bdbdbd;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.15);
            --radius-lg: 24px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
            --transition-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --transition-spring: cubic-bezier(0.68, -0.6, 0.32, 1.6);
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
            overflow-x: hidden;
            padding-top: 80px; /* Account for fixed navbar */
        }

        /* Enhanced Navbar Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(0, 108, 59, 0.1);
            transition: all 0.3s ease;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.99) !important;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.15);
            padding: 0.8rem 0;
        }

        .navbar-brand {
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            transform: translateY(-1px);
        }

        .navbar-brand img {
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover img {
            transform: scale(1.05);
        }

        .navbar-brand span {
            color: #006C3B !important;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .nav-link {
            color: #333 !important;
            font-weight: 500;
            padding: 0.75rem 1rem !important;
            border-radius: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .nav-link:hover {
            background: rgba(0, 108, 59, 0.1) !important;
            color: #006C3B !important;
            transform: translateY(-1px);
        }

        .nav-link.active {
            background: #006C3B !important;
            color: white !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 108, 59, 0.2);
        }

        .nav-link i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .nav-link:hover i {
            transform: translateY(-2px);
        }

        .btn-outline-success {
            border: 2px solid #006C3B !important;
            color: #006C3B !important;
            background: transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-outline-success:hover {
            background: #006C3B !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.15);
        }

        .btn-success {
            background: #006C3B !important;
            border: 2px solid #006C3B !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-success:hover {
            background: #005530 !important;
            border-color: #005530 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.15);
        }

        .navbar-toggler {
            border: none !important;
            padding: 0.5rem;
            transition: all 0.3s ease;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative;
            z-index: 1000;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(0, 108, 59, 0.25) !important;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2833, 37, 41, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: 1.5em !important;
            height: 1.5em !important;
        }

        /* Force navbar toggler to be visible */
        .navbar .navbar-toggler {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .navbar .navbar-toggler-icon {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Ensure toggler is visible on mobile */
        @media (max-width: 991.98px) {
            .navbar-toggler {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            .navbar-toggler-icon {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
        }

        /* Android Burger Menu - Always Visible */
        .navbar-toggler {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 1000 !important;
            background: transparent !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            margin-left: auto !important;
            min-height: 48px !important;
            min-width: 48px !important;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
            -webkit-appearance: none;
            appearance: none;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(0, 108, 59, 0.25) !important;
        }

        .navbar-toggler-icon {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: 24px !important;
            height: 24px !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2833, 37, 41, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat;
            background-position: center;
            background-size: 100%;
        }

        /* Navbar Container */
        .navbar > .container {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100% !important;
            position: relative !important;
        }

        /* Navbar Collapse */
        .navbar-collapse {
            flex-basis: 100% !important;
            flex-grow: 1 !important;
            align-items: center !important;
        }

        /* Android-Optimized Mobile responsiveness */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(15px);
                -webkit-backdrop-filter: blur(15px);
                border-radius: 15px;
                margin-top: 1rem;
                padding: 1rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                z-index: 1000;
                width: 100%;
            }

            .nav-link {
                padding: 1.2rem 1rem !important;
                margin: 0.25rem 0;
                text-align: center;
                min-height: 48px; /* Android minimum touch target */
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.1rem;
                -webkit-tap-highlight-color: transparent;
                touch-action: manipulation;
            }

            .d-flex.gap-2 {
                flex-direction: column;
                gap: 0.75rem !important;
                margin-top: 1rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
                min-height: 48px; /* Android minimum touch target */
                font-size: 1.1rem;
                padding: 1rem 1.5rem !important;
                -webkit-tap-highlight-color: transparent;
                touch-action: manipulation;
            }

            /* Ensure burger button is visible on mobile */
            .navbar-toggler {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                min-height: 48px !important;
                min-width: 48px !important;
                padding: 12px !important;
                -webkit-tap-highlight-color: transparent;
                touch-action: manipulation;
                border-radius: 8px !important;
                background: rgba(255, 255, 255, 0.1) !important;
                border: 1px solid rgba(0, 0, 0, 0.1) !important;
            }

            .navbar-toggler-icon {
                width: 24px !important;
                height: 24px !important;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
        }

        /* Android-specific optimizations */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.75rem 1rem !important;
                min-height: 64px; /* Android recommended navbar height */
            }

            .navbar-brand {
                font-size: 1.1rem !important;
            }

            .navbar-brand img {
                height: 36px !important;
            }

            /* Enhanced touch targets for Android */
            .nav-link, .btn, .navbar-toggler {
                min-height: 48px !important;
                -webkit-tap-highlight-color: transparent;
                touch-action: manipulation;
            }

            /* Android scroll optimization */
            .navbar-collapse {
                max-height: calc(100vh - 80px);
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Android Chrome specific fixes */
        @media screen and (-webkit-min-device-pixel-ratio: 0) {
            .navbar-toggler {
                -webkit-appearance: none;
                appearance: none;
            }
        }

        /* Android performance optimizations */
        .navbar, .navbar-toggler, .nav-link, .btn {
            will-change: transform;
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
        }

        /* Android touch feedback */
        .nav-link:active, .btn:active, .navbar-toggler:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }

        /* Force burger menu visibility on all screen sizes */
        .navbar-expand-lg .navbar-toggler {
            display: block !important;
        }

        /* Override Bootstrap's default hiding behavior */
        @media (min-width: 992px) {
            .navbar-expand-lg .navbar-toggler {
                display: block !important;
            }
        }

        /* Android-specific burger menu styling */
        .navbar-toggler {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
        }

        .navbar-toggler:hover {
            background-color: rgba(0, 108, 59, 0.1) !important;
            border-color: rgba(0, 108, 59, 0.2) !important;
        }

        /* Ensure burger icon is always visible */
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2833, 37, 41, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            background-size: 100% !important;
        }

        /* Additional toggler visibility fixes */
        .navbar-toggler {
            background-color: transparent !important;
            border: 1px solid transparent !important;
            border-radius: 0.375rem !important;
        }

        .navbar-toggler:not(:disabled):not(.disabled) {
            cursor: pointer !important;
        }

        /* Override any Bootstrap default hiding */
        .navbar-toggler[aria-expanded="false"] {
            display: block !important;
        }

        .navbar-toggler[aria-expanded="true"] {
            display: block !important;
        }

        /* Force visibility with highest specificity */
        .navbar .navbar-toggler.border-0 {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 9999 !important;
        }

        .navbar .navbar-toggler.border-0 .navbar-toggler-icon {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2833, 37, 41, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
            width: 1.5em !important;
            height: 1.5em !important;
        }

        /* Additional force visibility */
        button.navbar-toggler {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        button.navbar-toggler span.navbar-toggler-icon {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Final attempt to make toggler visible */
        .navbar-expand-lg .navbar-toggler {
            display: block !important;
        }

        /* Override Bootstrap's default behavior */
        @media (min-width: 992px) {
            .navbar-expand-lg .navbar-toggler {
                display: block !important;
            }
        }

        /* Force the button to be visible regardless of screen size */
        .navbar-toggler {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 10000 !important;
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            border-radius: 4px !important;
            padding: 4px 8px !important;
            float: right !important;
            margin-left: auto !important;
            margin-right: 0 !important;
        }

        /* Ensure navbar container positioning */
        .navbar .container {
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        /* Fix navbar brand positioning */
        .navbar-brand {
            flex-shrink: 0 !important;
            margin-right: auto !important;
        }

        /* Ensure toggler stays in navbar */
        .navbar .navbar-toggler {
            position: relative !important;
            top: auto !important;
            left: auto !important;
            right: auto !important;
            bottom: auto !important;
            transform: none !important;
            margin-left: auto !important;
            order: 2 !important;
        }

        /* Fix navbar flexbox layout */
        .navbar {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0.5rem 1rem !important;
        }

        .navbar > .container {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100% !important;
        }

        /* Ensure proper order */
        .navbar-brand {
            order: 1 !important;
        }

        .navbar-toggler {
            order: 2 !important;
        }

        .navbar-collapse {
            order: 3 !important;
        }


        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            animation: fadeIn 0.8s var(--transition-smooth);
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
            transform-style: preserve-3d;
            perspective: 1000px;
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

        /* Add responsive breakpoints */
        @media (max-width: 992px) {
            .features {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
                margin: 3rem auto;
            }
            
            .feature-card {
                padding: 1.75rem;
            }
        }

        @media (max-width: 768px) {
            .features {
                grid-template-columns: 1fr;
                gap: 1.25rem;
                margin: 2.5rem auto;
            }
            
            .feature-card {
                padding: 1.5rem;
                transform: none !important; /* Disable 3D transforms on mobile */
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
            }
            
            .feature-title {
                font-size: 1.35rem;
                margin-bottom: 0.75rem;
            }
            
            .feature-description {
                font-size: 0.95rem;
            }
        }

        /* Ensure animations work properly on mobile */
        @media (max-width: 768px) {
            .feature-card,
            .feature-icon,
            .feature-title,
            .feature-description {
                opacity: 1 !important;
                transform: none !important;
                animation: none !important;
            }
            
            .feature-card:hover {
                transform: none !important;
                box-shadow: var(--shadow);
            }
            
            .feature-card::before {
                display: none;
            }
        }

        /* Optimize hover effects for touch devices */
        @media (hover: none) {
            .feature-card:hover {
                transform: none;
                box-shadow: var(--shadow);
            }
            
            .feature-icon:hover {
                transform: none;
            }
            
            .feature-card:hover .feature-icon {
                transform: none;
                animation: none;
            }
        }

        .feature-card:nth-child(1) { animation-delay: 0.2s; }
        .feature-card:nth-child(2) { animation-delay: 0.4s; }
        .feature-card:nth-child(3) { animation-delay: 0.6s; }

        .feature-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.5s var(--transition-bounce);
            transform-origin: left;
        }

        .feature-card:hover::before {
            transform: scaleX(1);
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
            position: relative;
            z-index: 1;
            transform-style: preserve-3d;
        }

        .feature-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px dashed var(--primary);
            top: 0;
            left: 0;
            opacity: 0;
            transform: scale(1.2);
            transition: all 0.5s var(--transition-bounce);
        }

        .feature-icon i {
            font-size: 1.75rem;
            color: var(--primary);
            transition: all 0.5s var(--transition-bounce);
        }

        .feature-card:hover .feature-icon {
            transform: rotateY(360deg) scale(1.15);
            animation: floatWithRotate 3s var(--transition-spring) infinite;
        }

        .feature-card:hover .feature-icon::after {
            opacity: 0.4;
            transform: scale(1.4) rotate(15deg);
        }

        .feature-card:hover .feature-icon i {
            transform: rotateY(180deg);
            color: var(--white);
        }

        .feature-card:hover .feature-icon::before {
            opacity: 0.2;
            animation: spin 3s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .feature-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-title {
            color: var(--primary);
            transform: translateY(-3px);
        }

        .feature-description {
            color: #666;
            line-height: 1.7;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            transform: translateY(0);
            opacity: 0.9;
        }

        .feature-card:hover .feature-description {
                opacity: 1;
            transform: translateY(-2px);
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
            animation: fadeIn 0.8s ease-out forwards;
            letter-spacing: -0.02em;
            color: var(--text-dark);
        }

        .team-section h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary-gradient);
            animation: scaleIn 0.6s var(--transition-bounce) forwards;
        }

        /* Team Grid Styles */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            padding: 2rem 0;
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

        .team-member:hover img {
            transform: scale(1.1);
        }

        .img-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 108, 59, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .team-member:hover .img-overlay {
            opacity: 1;
        }

        .overlay-icons {
            display: flex;
            gap: 1rem;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s ease;
            transform: translateY(20px);
            opacity: 0;
        }

        .team-member:hover .social-icon {
            transform: translateY(0);
            opacity: 1;
        }

        .team-member:hover .social-icon:nth-child(1) { transition-delay: 0.1s; }
        .team-member:hover .social-icon:nth-child(2) { transition-delay: 0.2s; }

        .social-icon:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-3px);
        }

        .team-member h3 {
            color: var(--primary);
            font-size: 1.25rem;
            margin: 1rem 0 0.5rem;
        }

        .team-member p {
            color: var(--text-light);
            font-size: 0.9rem;
            margin: 0;
        }

        /* Responsive Grid */
        @media (max-width: 1200px) {
            .team-grid {
                grid-template-columns: repeat(3, 1fr);
                padding: 2rem 1rem;
            }
        }

        @media (max-width: 992px) {
            .team-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .team-grid {
                grid-template-columns: 1fr;
            }

            .team-member {
                padding: 1rem;
            }

            .img-container {
                width: 150px;
                height: 150px;
            }
        }

        /* Contact Section */
        .contact-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin: 5rem 0;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s var(--transition-bounce) 0.6s forwards;
        }

        .section-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 0.75rem;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary);
            transform: scaleX(0);
            transform-origin: left;
            animation: scaleInLeft 0.6s var(--transition-bounce) 0.8s forwards;
        }

        @keyframes scaleInLeft {
            to {
                transform: scaleX(1);
            }
        }

        .map-container {
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.6s var(--transition-spring);
            transform: translateY(0);
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .map-container:hover {
            transform: translateY(-10px) scale(1.02) rotateX(2deg);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.1);
        }

        .map-container iframe {
            width: 100%;
            height: 400px;
            border: none;
            transition: all 0.3s ease;
        }

        .contact-info {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .info-item i {
            color: var(--primary);
            font-size: 1.2rem;
            margin-top: 0.2rem;
            width: 20px;
        }

        .info-text {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-text p {
            margin: 0;
            color: var(--text-dark);
        }

        .service-tags {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }

        .service-tag {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .service-tag i {
            color: var(--primary);
            font-size: 1rem;
            width: auto;
            margin: 0;
        }

        .contact-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .contact-form:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #006C3B;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 108, 59, 0.1);
            outline: none;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #9ca3af;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .form-group input:focus::placeholder,
        .form-group textarea:focus::placeholder {
            opacity: 0.5;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group label {
            position: absolute;
            left: 16px;
            top: 0;
            font-size: 0.875rem;
            color: #006C3B;
            background: #ffffff;
            padding: 0 5px;
            transform: translateY(-50%);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .form-group input:focus ~ label,
        .form-group textarea:focus ~ label,
        .form-group input:not(:placeholder-shown) ~ label,
        .form-group textarea:not(:placeholder-shown) ~ label {
            opacity: 1;
        }

        #contactForm button {
            width: 100%;
            padding: 14px 24px;
            background: #006C3B;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        #contactForm button:hover {
            background: #005530;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.2);
        }

        #contactForm button:active {
            transform: translateY(0);
        }

        #contactForm button i {
            transition: transform 0.3s ease;
        }

        #contactForm button:hover i {
            transform: translateX(4px);
        }

        /* Form validation styles */
        .form-group input:invalid:not(:placeholder-shown),
        .form-group textarea:invalid:not(:placeholder-shown) {
            border-color: #dc3545;
        }

        .form-group input:valid:not(:placeholder-shown),
        .form-group textarea:valid:not(:placeholder-shown) {
            border-color: #28a745;
        }

        /* Success message animation */
        @keyframes formSuccess {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-message {
            display: none;
            color: #28a745;
            text-align: center;
            padding: 1rem;
            animation: formSuccess 0.5s ease forwards;
        }

        /* Loading animation for button */
        @keyframes buttonLoading {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        #contactForm button.loading {
            background: #004d2e;
            pointer-events: none;
        }

        #contactForm button.loading i {
            animation: buttonLoading 1s linear infinite;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .contact-form {
                padding: 1.5rem;
                margin: 1rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .form-group input,
            .form-group textarea {
                padding: 10px 14px;
                font-size: 0.95rem;
            }

            #contactForm button {
                padding: 12px 20px;
            }
        }

        /* Learn More Button */
        .learn-more-container {
            text-align: center;
            margin: 3rem 0 5rem;
            animation: fadeIn 0.8s ease-out 0.8s forwards;
            opacity: 0;
        }

        .learn-more-btn {
            display: inline-block;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: var(--white);
            padding: 1rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.5s var(--transition-spring);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 108, 59, 0.2);
        }

        .learn-more-btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
            background-size: 200% 100%;
        }

        .learn-more-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 30px rgba(0, 108, 59, 0.2);
        }

        .learn-more-btn:active {
            transform: translateY(-2px) scale(1.02);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes floatWithRotate {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-10px) rotate(2deg);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .pulse-animation {
            animation: pulse 2s var(--transition-smooth) infinite;
        }

        .float-animation {
            animation: floatWithRotate 4s var(--transition-spring) infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% center;
            }
            100% {
                background-position: 200% center;
            }
        }

        @keyframes scaleInWithFade {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInFromBottom {
            0% {
                opacity: 0;
                transform: translateY(40px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Enhanced Animations */
        .visible {
            animation: scaleInWithFade 0.8s var(--transition-spring) forwards;
        }

        .feature-card.visible,
        .team-member.visible {
            animation: slideInFromBottom 0.8s var(--transition-spring) forwards;
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        .message-response {
            padding: 16px;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 600px;
            text-align: center;
            font-weight: 500;
            animation: fadeIn 0.3s ease-out;
            transition: opacity 0.5s ease;
        }

        .message-response.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .message-response.error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Android-optimized navbar scroll effect
            let ticking = false;
            function updateNavbar() {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
                ticking = false;
            }

            window.addEventListener('scroll', function() {
                if (!ticking) {
                    requestAnimationFrame(updateNavbar);
                    ticking = true;
                }
            }, { passive: true });

            // Android burger menu optimization
            const navbarToggler = document.querySelector('.navbar-toggler');
            const navbarCollapse = document.querySelector('.navbar-collapse');
            
            if (navbarToggler) {
                // Android touch feedback
                navbarToggler.addEventListener('touchstart', function(e) {
                    this.style.transform = 'scale(0.95)';
                    this.style.backgroundColor = 'rgba(0, 108, 59, 0.1)';
                }, { passive: true });

                navbarToggler.addEventListener('touchend', function(e) {
                    this.style.transform = 'scale(1)';
                    this.style.backgroundColor = '';
                }, { passive: true });

                navbarToggler.addEventListener('touchcancel', function(e) {
                    this.style.transform = 'scale(1)';
                    this.style.backgroundColor = '';
                }, { passive: true });

                // Android click optimization
                navbarToggler.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Toggle the menu manually for Android
                    if (navbarCollapse.classList.contains('show')) {
                        navbarCollapse.classList.remove('show');
                        this.setAttribute('aria-expanded', 'false');
                    } else {
                        navbarCollapse.classList.add('show');
                        this.setAttribute('aria-expanded', 'true');
                    }
                });
            }

            // Android-optimized navbar collapse
            const navbarCollapse = document.querySelector('.navbar-collapse');
            if (navbarCollapse) {
                // Close menu when clicking outside on Android
                document.addEventListener('touchstart', function(e) {
                    if (!navbarCollapse.contains(e.target) && !navbarToggler.contains(e.target)) {
                        if (navbarCollapse.classList.contains('show')) {
                            navbarToggler.click();
                        }
                    }
                }, { passive: true });
            }

            // Enhanced smooth scroll
            const internalLinks = document.querySelectorAll('a[href^="#"]');
            
            internalLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        const headerOffset = 100;
                        const elementPosition = targetElement.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Enhanced intersection observer
            const observerOptions = {
                threshold: 0.2,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        
                        // Add staggered animation to child elements
                        if (entry.target.classList.contains('feature-card') || 
                            entry.target.classList.contains('team-member')) {
                            const children = entry.target.children;
                            Array.from(children).forEach((child, index) => {
                                child.style.animationDelay = `${index * 0.1}s`;
                                child.style.opacity = '0';
                                child.style.animation = 'scaleInWithFade 0.6s var(--transition-spring) forwards';
                            });
                        }
                        
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            // Elements to observe
            const animatedElements = document.querySelectorAll(
                '.feature-card, .team-member, .contact-form, .map-container, .section-title'
            );
            
            animatedElements.forEach(element => observer.observe(element));
            
            // Mouse movement parallax effect for feature cards
            document.querySelectorAll('.feature-card').forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    
                    const rotateX = (y - centerY) / 20;
                    const rotateY = (centerX - x) / 20;
                    
                    card.style.transform = `
                        perspective(1000px)
                        rotateX(${rotateX}deg)
                        rotateY(${rotateY}deg)
                        scale3d(1.02, 1.02, 1.02)
                    `;
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
                });
            });
            
            // Add floating animation to team images with random delays
            document.querySelectorAll('.team-member img').forEach(img => {
                const randomDelay = Math.random() * 2;
                img.style.animation = `floatWithRotate 4s var(--transition-spring) ${randomDelay}s infinite`;
            });
            
            // Add pulse animation to buttons
            document.querySelectorAll('.btn-send, .learn-more-btn').forEach(btn => {
                btn.addEventListener('mouseenter', () => {
                    btn.style.transform = 'translateY(-5px) scale(1.05)';
                });
                
                btn.addEventListener('mouseleave', () => {
                    btn.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Animation on scroll init
            const animateElements = document.querySelectorAll('.team-member');
            
            const observerScroll = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        observerScroll.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            animateElements.forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(30px)';
                observerScroll.observe(element);
            });
            
            // Add image loading animation
            const teamImages = document.querySelectorAll('.team-member img');
            teamImages.forEach(img => {
                // Add loading state
                img.style.opacity = '0';
                
                img.onload = function() {
                    this.style.opacity = '1';
                };
                
                // If image is already loaded
                if (img.complete) {
                    img.style.opacity = '1';
                }
            });

            const contactForm = document.getElementById('contactForm');
            const messageResponse = document.getElementById('messageResponse');

            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const button = this.querySelector('button');
                    const successMessage = document.querySelector('.success-message');
                    
                    // Add loading state
                    button.classList.add('loading');
                    button.innerHTML = '<i class="fas fa-spinner"></i> Sending...';
                    button.disabled = true;
                    
                    // Get form data
                    const formData = new FormData(this);
                    
                    // Send data to server
                    fetch('submit_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success state
                            button.classList.remove('loading');
                            button.innerHTML = '<i class="fas fa-check"></i> Sent!';
                            button.style.background = '#28a745';
                            
                            // Show success message
                            if (successMessage) {
                                successMessage.textContent = data.message;
                                successMessage.style.display = 'block';
                            }
                            
                            // Reset form
                            this.reset();
                            
                            // Reset button after 3 seconds
                            setTimeout(() => {
                                button.style.background = '';
                                button.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                                button.disabled = false;
                                if (successMessage) {
                                    successMessage.style.display = 'none';
                                }
                            }, 3000);
                        } else {
                            // Show error state
                            button.classList.remove('loading');
                            button.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                            button.disabled = false;
                            alert(data.message || 'Failed to send message. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        button.classList.remove('loading');
                        button.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                        button.disabled = false;
                        alert('An error occurred. Please try again.');
                    });
                });
            }

            // Add floating label effect
            document.querySelectorAll('.form-group input, .form-group textarea').forEach(field => {
                field.addEventListener('focus', function() {
                    this.parentNode.classList.add('focused');
                });
                
                field.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentNode.classList.remove('focused');
                    }
                });
            });
        });
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/images/logo.png" alt="Eat&Run Logo" height="40" class="me-2">
                <span class="fw-bold text-success fs-4">Eat&Run</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" style="display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; float: right !important; margin-left: auto !important;">
                <span class="navbar-toggler-icon" style="display: block !important; visibility: visible !important; opacity: 1 !important;"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="menu.php">
                            <i class="fas fa-utensils me-1"></i>Menu
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="about.php">
                            <i class="fas fa-info-circle me-1"></i>About
                        </a>
                    </li>
                </ul>
                
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-success px-3 py-2 fw-medium" href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                    <a class="btn btn-success px-3 py-2 fw-medium" href="register.php">
                        <i class="fas fa-user-plus me-1"></i>Register
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Features Section -->
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3 class="feature-title">Our Story</h3>
                <p class="feature-description">
                    Founded in 2025, Eat&Run started with a simple mission: to connect hungry customers with their favorite local restaurants. We've grown from a small startup to a trusted food delivery service.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h3 class="feature-title">Why Choose Us</h3>
                <p class="feature-description">
                    We pride ourselves on fast delivery, restaurant variety, and excellent customer service. Our platform makes ordering food as simple as a few clicks.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="feature-title">Our Community</h3>
                <p class="feature-description">
                    We work closely with local restaurants and delivery partners to create a seamless food delivery experience for our growing community.
                </p>
            </div>
        </div>

        <!-- Learn More Button -->
        <div class="learn-more-container">
            <a href="mission-vision.php" class="learn-more-btn">Learn More</a>
        </div>

        <!-- Meet The Kupals Section -->
        <section class="team-section">
            <h2>Meet The Wonder Pets</h2>
            <div class="team-grid">
                <div class="team-member" data-aos="fade-up" data-aos-delay="100">
                    <div class="img-container">
                        <img src="assets/images/team/anton.jpg" alt="Anton Ramos" onerror="this.src='assets/images/default-avatar.png'; this.classList.add('fallback');">
                        <div class="img-overlay">
                            <div class="overlay-icons">
                                <a href="#" class="social-icon"><i class="fab fa-github"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-linkedin"></i></a>
                            </div>
                        </div>
                    </div>
                    <h3>Anton Ramos</h3>
                    <p>Front-end / Documentation</p>
                </div>
                <div class="team-member" data-aos="fade-up" data-aos-delay="200">
                    <div class="img-container">
                        <img src="assets/images/team/ken.jpg" alt="Ken Coladilla" onerror="this.src='assets/images/default-avatar.png'; this.classList.add('fallback');">
                        <div class="img-overlay">
                            <div class="overlay-icons">
                                <a href="#" class="social-icon"><i class="fab fa-github"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-linkedin"></i></a>
                            </div>
                        </div>
                    </div>
                    <h3>Ken Coladilla</h3>
                    <p>Backend / Documentation</p>
                </div>
                <div class="team-member" data-aos="fade-up" data-aos-delay="300">
                    <div class="img-container">
                        <img src="assets/images/team/rojohn.jpg" alt="Rojohn Manalo" onerror="this.src='assets/images/default-avatar.png'; this.classList.add('fallback');">
                        <div class="img-overlay">
                            <div class="overlay-icons">
                                <a href="#" class="social-icon"><i class="fab fa-github"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-linkedin"></i></a>
                            </div>
                        </div>
                    </div>
                    <h3>Rojohn Manalo</h3>
                    <p>Backend / Documentation</p>
                </div>
                <div class="team-member" data-aos="fade-up" data-aos-delay="400">
                    <div class="img-container">
                        <img src="assets/images/team/jb.jpg" alt="JB Areza" onerror="this.src='assets/images/default-avatar.png'; this.classList.add('fallback');">
                        <div class="img-overlay">
                            <div class="overlay-icons">
                                <a href="#" class="social-icon"><i class="fab fa-github"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-linkedin"></i></a>
                            </div>
                        </div>
                    </div>
                    <h3>JB Areza</h3>
                    <p>Front-end / Documentation</p>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <div class="contact-section">
            <div class="location-section">
                <h2 class="section-title">Visit Us</h2>
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d965.6697706894911!2d121.40925692840576!3d14.282423989826446!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397e3d8ded519df%3A0x9c59944f57e731f9!2s518%20E%20Taleon%20St%2C%20Santa%20Cruz%2C%20Calabarzon!5e0!3m2!1sen!2sph!4v1648883811479!5m2!1sen!2sph" allowfullscreen="" loading="lazy"></iframe>
                </div>
                <div class="contact-info">
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="info-text">
                            <p>E. Taleon st</p>
                            <p>Santisima Cruz, Philippines</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div class="info-text">
                            <p>0912 345 6789</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div class="info-text">
                            <p>eat&run@example.com</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <div class="info-text">
                            <p>Main Branch: 10:00 AM - 10:00 PM</p>
                            <p>Bubukal Branch: 5:00 PM - 2:00 AM</p>
                        </div>
                    </div>
                    <div class="service-tags">
                        <span class="service-tag"><i class="fas fa-utensils"></i> Dine-In</span>
                        <span class="service-tag"><i class="fas fa-motorcycle"></i> Delivery</span>
                        <span class="service-tag"><i class="fas fa-shopping-bag"></i> Pick-up</span>
                    </div>
                </div>
            </div>

            <div class="message-section">
                <h2 class="section-title">Send us a Message</h2>
                <div class="contact-form">
                <form id="contactForm">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Your Name" required>
                            <label>Name</label>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Your Email" required>
                            <label>Email</label>
                    </div>
                    <div class="form-group">
                        <textarea name="message" placeholder="Your Message" required></textarea>
                            <label>Message</label>
                    </div>
                    <button type="submit">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
                    <div class="success-message">
                        Message sent successfully!
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 