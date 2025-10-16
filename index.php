<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


if (extension_loaded('session')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else {
    die("PHP Session module is not installed. Please enable it in php.ini");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-light: #00A65A;
            --primary-dark: #005530;
            --accent-color: #FFD700;
            --text-color: #333333;
            --text-light: #666666;
            --bg-color: #f8f9fa;
            --white: #ffffff;
            
            --transition: all 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
            --shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.15);
            
            --border-radius-sm: 10px;
            --border-radius-md: 16px;
            --border-radius-lg: 24px;
            --border-radius-xl: 32px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }

        body {
            background: var(--bg-color);
            min-height: 100vh;
            line-height: 1.6;
            color: var(--text-color);
            overflow-x: hidden;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Hero Section Styles */
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 80px 24px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.15) 0%, transparent 60%),
                radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.12) 0%, transparent 60%);
            opacity: 0.8;
            animation: gradientShift 15s ease infinite alternate;
        }

        .hero-content {
            max-width: 1000px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 3;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1.2s ease forwards;
        }

        .hero-title {
            font-size: 72px;
            font-weight: 800;
            color: white;
            margin-bottom: 24px;
            line-height: 1.1;
            letter-spacing: -1px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background: linear-gradient(to right, #ffffff, #f0f0f0);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-block;
        }

        .hero-subtitle {
            font-size: 22px;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1.6;
            margin-bottom: 48px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 400;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        .hero-cta {
            display: flex;
            justify-content: center;
            gap: 24px;
            perspective: 1000px;
        }

        .hero-btn {
            padding: 16px 36px;
            border-radius: var(--border-radius-md);
            font-weight: 600;
            font-size: 17px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
            transform: translateZ(0);
            letter-spacing: 0.5px;
        }

        .primary-btn {
            background: var(--white);
            color: var(--primary-color);
            box-shadow: var(--shadow-md);
        }

        .primary-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
            transition: 0.6s;
        }

        .primary-btn:hover::before {
            left: 100%;
        }

        .primary-btn:hover {
            transform: translateY(-4px) translateZ(10px);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(to right, white, #f8f8f8);
        }

        .secondary-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .secondary-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-4px) translateZ(10px);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        /* Scroll Down Indicator */
        .scroll-down {
            position: absolute;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            text-decoration: none;
            z-index: 100;
        }

        .scroll-indicator {
            position: relative;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2), 
                        inset 0 2px 4px rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .scroll-indicator::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(to bottom, 
                        rgba(255, 255, 255, 0.2), 
                        rgba(255, 255, 255, 0));
            border-radius: 60px 60px 0 0;
            pointer-events: none;
        }

        .scroll-indicator i {
            font-size: 24px;
            color: white;
            animation: arrowPulse 2s ease-in-out infinite;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        @keyframes arrowPulse {
            0%, 100% {
                transform: translateY(0);
                opacity: 0.8;
            }
            50% {
                transform: translateY(8px);
                opacity: 1;
            }
        }

        .scroll-down:hover .scroll-indicator {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3),
                        inset 0 2px 6px rgba(255, 255, 255, 0.4);
        }

        /* Pulse effect around the arrow */
        .pulse-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            background: rgba(255, 255, 255, 0.1);
        }

        @keyframes pulse {
            0% {
                transform: scale(0.8);
                opacity: 0.8;
            }
            50% {
                transform: scale(1.2);
                opacity: 0;
            }
            100% {
                transform: scale(0.8);
                opacity: 0;
            }
        }

        /* Menu Section Styles */
        .menu-section {
            padding: 120px 24px;
            margin-top: -60px;
            position: relative;
            background: linear-gradient(180deg, var(--bg-color) 0%, #ffffff 100%);
        }

        .menu-container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--white);
            padding: 60px;
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-lg);
            transform: translateY(0);
            transition: var(--transition);
            position: relative;
            z-index: 10;
        }

        .menu-container:hover {
            transform: translateY(-5px);
        }

        .menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 60px;
        }

        .menu-title {
            font-size: 42px;
            color: var(--primary-color);
            font-weight: 700;
            letter-spacing: -0.5px;
            position: relative;
        }

        .menu-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-light));
            border-radius: 2px;
        }

        .view-all {
            padding: 14px 28px;
            border-radius: var(--border-radius-md);
            background: rgba(0, 108, 59, 0.08);
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .view-all:hover {
            background: rgba(0, 108, 59, 0.12);
            transform: translateX(5px);
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            perspective: 1000px;
        }

        .menu-item {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            transform-style: preserve-3d;
            transform: translateZ(0);
            position: relative;
        }

        .menu-item:hover {
            transform: translateY(-8px) rotateX(2deg);
            box-shadow: var(--shadow-lg);
        }

        .menu-item img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            transition: var(--transition);
        }

        .menu-item:hover img {
            transform: scale(1.08);
        }

        .menu-item-content {
            padding: 35px;
        }

        .menu-item-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 30px;
            position: relative;
            transition: var(--transition);
        }

        .menu-item-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 2px;
            transition: var(--transition);
        }

        .menu-item:hover .menu-item-title {
            color: var(--primary-color);
        }

        .menu-item:hover .menu-item-title::after {
            width: 70px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-light));
        }

        .order-btn {
            width: 100%;
            padding: 16px;
            border-radius: var(--border-radius-md);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            transform: translateZ(0);
            letter-spacing: 0.5px;
        }

        .order-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: 0.6s;
        }

        .order-btn:hover::before {
            left: 100%;
        }

        .order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 108, 59, 0.3);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 20px;
            }

            .hero-title {
                font-size: 42px;
            }

            .hero-subtitle {
                font-size: 18px;
            }

            .hero-cta {
                flex-direction: column;
                gap: 16px;
            }

            .hero-btn {
                width: 100%;
                justify-content: center;
            }

            .menu-container {
                padding: 40px 20px;
            }

            .menu-title {
                font-size: 32px;
            }

            .menu-header {
                flex-direction: column;
                gap: 24px;
                text-align: center;
            }

            .menu-title::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .view-all {
                width: 100%;
                justify-content: center;
            }
        }

        /* Additional elegant design enhancements */
        .menu-item {
            position: relative;
            overflow: visible;
        }

        .menu-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: var(--border-radius-lg);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transition: var(--transition);
            z-index: -1;
        }

        .menu-item:hover::before {
            opacity: 1;
            transform: scale(1.05);
        }

        .menu-item-content {
            padding: 35px;
        }

        /* Enhanced order button */
        .order-btn {
            padding: 16px;
            font-size: 17px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 108, 59, 0.15);
        }

        .order-btn:hover {
            box-shadow: 0 8px 25px rgba(0, 108, 59, 0.3);
        }

        /* Enhanced animations */
        @keyframes gradientShift {
            0% {
                transform: scale(1) rotate(0deg);
            }
            100% {
                transform: scale(1.1) rotate(3deg);
            }
        }

        /* Enhanced menu items with subtle hover effects */
        .menu-item {
            transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .menu-item::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: var(--border-radius-lg);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
            z-index: -1;
        }

        .menu-item:hover::after {
            opacity: 1;
        }

        /* Elegant image hover effect */
        .menu-item .img-container {
            position: relative;
            overflow: hidden;
        }

        .menu-item .img-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.4), transparent);
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .menu-item:hover .img-overlay {
            opacity: 1;
        }

        /* Final elegant enhancements */
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 80px;
            background: linear-gradient(to top, var(--bg-color), transparent);
            z-index: 2;
        }

        .hero-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: #FFD700;
            border-radius: 2px;
            animation: widthPulse 3s infinite alternate;
        }

        @keyframes widthPulse {
            from { width: 40px; }
            to { width: 80px; }
        }

        /* Floating elements animation */
        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-1 {
            width: 150px;
            height: 150px;
            top: 15%;
            left: 10%;
            animation: float 15s ease-in-out infinite;
        }

        .floating-2 {
            width: 100px;
            height: 100px;
            top: 60%;
            right: 15%;
            animation: float 12s ease-in-out infinite 2s;
        }

        .floating-3 {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation: float 10s ease-in-out infinite 1s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0);
            }
            25% {
                transform: translateY(-20px) translateX(10px);
            }
            50% {
                transform: translateY(10px) translateX(-15px);
            }
            75% {
                transform: translateY(15px) translateX(5px);
            }

        }

        /* Enhanced menu container */
        .menu-container {
            position: relative;
        }

        .menu-container::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 1px solid rgba(0, 108, 59, 0.05);
            border-radius: var(--border-radius-xl);
            pointer-events: none;
            z-index: 0;
        }

        /* Enhanced order button */
        .order-btn {
            position: relative;
            overflow: hidden;
        }

        .order-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: 0.6s;
        }

        .order-btn:hover::before {
            left: 100%;
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }

        /* Enhanced animations for menu items */
        .menu-item {
            transform: translateZ(0);
            backface-visibility: hidden;
        }

        .menu-item:hover {
            transform: translateY(-8px) scale(1.02);
        }

        .menu-item img {
            transform: scale(1);
            transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .menu-item:hover img {
            transform: scale(1.08);
        }

        .featured-menu {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .section-title {
            text-align: center;
            font-size: 36px;
            color: var(--primary-color);
            margin-bottom: 50px;
            font-weight: 700;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 2px;
        }

        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }

        .featured-item {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .featured-item:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .featured-image {
            position: relative;
            height: 250px;
            overflow: hidden;
        }

        .featured-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .featured-item:hover .featured-image img {
            transform: scale(1.1);
        }

        .featured-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 15px;
            opacity: 0;
            transition: var(--transition);
        }

        .featured-item:hover .featured-overlay {
            opacity: 1;
        }

        .price {
            color: white;
            font-size: 24px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .order-btn {
            background: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            transform: translateY(20px);
        }

        .featured-item:hover .order-btn {
            transform: translateY(0);
        }

        .order-btn:hover {
            background: var(--primary-light);
            transform: translateY(-3px) !important;
        }

        .featured-content {
            padding: 20px;
            text-align: center;
        }

        .featured-content h3 {
            color: var(--text-color);
            font-size: 20px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .featured-content p {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .featured-menu {
                padding: 60px 0;
            }

            .section-title {
                font-size: 28px;
            }

            .featured-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }

            .featured-image {
                height: 200px;
            }
        }

        /* Add these new styles to your existing CSS */
        .menu-link {
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
            transition: all 0.3s ease;
        }

        .menu-item {
            cursor: pointer;
            overflow: hidden;
            position: relative;
            transition: all 0.3s ease;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .menu-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .img-container {
            position: relative;
            overflow: hidden;
            height: 250px;
        }

        .img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .menu-item:hover .img-container img {
            transform: scale(1.1);
        }

        .img-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent, rgba(0, 0, 0, 0.3));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .menu-item:hover .img-overlay {
            opacity: 1;
        }

        .menu-item-content {
            padding: 20px;
            text-align: center;
        }

        .menu-item-title {
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            position: relative;
            padding-bottom: 10px;
        }

        .menu-item-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .menu-item:hover .menu-item-title::after {
            width: 50px;
        }

        /* Popup Ad Styles */
        .popup-ad {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.7);
            background: white;
            padding: 0;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            z-index: 1000;
            max-width: 420px;
            width: 95%;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .popup-ad.show {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
            visibility: visible;
        }

        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(6px);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .popup-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .popup-close {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .popup-close:hover {
            background: white;
            transform: rotate(90deg);
        }

        .popup-close i {
            color: #333;
            font-size: 16px;
        }

        .popup-content {
            text-align: center;
            padding: 0;
        }

        .popup-image-container {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .popup-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .popup-text-content {
            padding: 30px;
            background: white;
        }

        .popup-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 15px;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .popup-description {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .popup-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 32px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 108, 59, 0.2);
            width: 100%;
            max-width: 240px;
        }

        .popup-cta:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 108, 59, 0.3);
            color: white;
            text-decoration: none;
        }

        .popup-footer {
            font-size: 13px;
            color: #718096;
            margin-top: 5px;
            font-style: italic;
        }

        .dont-show-again {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            cursor: pointer;
            font-size: 14px;
            color: #718096;
        }

        .dont-show-again input {
            cursor: pointer;
            width: 16px;
            height: 16px;
        }

        @media (max-width: 480px) {
            .popup-image-container {
                height: 150px;
            }

            .popup-text-content {
                padding: 25px 20px;
            }

            .popup-title {
                font-size: 28px;
            }

            .popup-description {
                font-size: 15px;
                margin-bottom: 20px;
            }

            .popup-cta {
                padding: 12px 24px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'loader.php'; ?>
    <?php include 'navbar.php'; ?>

    <!-- Popup Advertisement -->
    <div class="popup-overlay" id="popupOverlay"></div>
    <div class="popup-ad" id="popupAd">
        <div class="popup-close" onclick="closePopup()">
            <i class="fas fa-times"></i>
        </div>
                    <div class="popup-content">
                <div class="popup-image-container" style="background: linear-gradient(45deg, #8B4513, #A0522D);">
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1NiIgaGVpZ2h0PSIyOCI+CiAgPHBhdGggZD0iTTI4IDAgTDU2IDE0IEwyOCAyOCBMMCAxNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMSkiIHN0cm9rZS13aWR0aD0iMiI+PC9wYXRoPgo8L3N2Zz4=') center/cover"></div>
                    <div style="position: relative; z-index: 1; padding: 40px 20px; color: white; text-align: center;">
                        <i class="fas fa-hotel" style="font-size: 40px; margin-bottom: 15px;"></i>
                        <h2 style="font-size: 32px; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">EXCLUSIVE OFFER</h2>
                    </div>
                </div>
                <div class="popup-text-content">
                    <h3 class="popup-title">Get 25% OFF</h3>
                    <p class="popup-description">Book now and enjoy an exclusive discount on your first stay! Experience luxury and comfort at FAWNA Hotel, where every moment feels like home. Nothing beats a Jet2Holiday and right now you can save £50 per person. That's £200 off for a family of 4. We've got millions of free child place holidays available with 22kg of baggage included. Book now with Jet2holidays. Package holidays you can trust!

</p>
                    <a href="http://10.203.57.85:5000/" class="popup-cta" id="signupButton" target="_blank">
                        <i class="fas fa-bed" style="margin-right: 8px;"></i>
                        Book Now & Save
                    </a>
                    <div class="popup-footer">
                        *Limited time offer for new guests only
                    </div>
                    <label class="dont-show-again">
                        <input type="checkbox" id="dontShowAgain">
                        Don't show this again
                    </label>
                </div>
            </div>
    </div>

    <section class="hero-section">
        <div class="floating-element floating-1"></div>
        <div class="floating-element floating-2"></div>
        <div class="floating-element floating-3"></div>
        
        <div class="hero-content">
            <h1 class="hero-title">Welcome to Eat&Run!</h1>
            <p class="hero-subtitle">Your favorite local restaurants delivered to your doorstep. Fast, fresh, and convenient food delivery service.</p>
            <div class="hero-cta">
                <a href="menu.php" class="hero-btn primary-btn">
                    <i class="fas fa-utensils"></i>
                    Browse Menu
                </a>
                <a href="about.php" class="hero-btn secondary-btn">
                    <i class="fas fa-info-circle"></i>
                    Learn More
                </a>
            </div>
        </div>
        <a href="#menu" class="scroll-down">
            <div class="scroll-indicator">
                <div class="pulse-ring"></div>
                <i class="fas fa-chevron-down"></i>
            </div>
        </a>
    </section>

    <section id="menu" class="menu-section">
        <div class="menu-container">
            <div class="menu-header">
                <h2 class="menu-title">Featured Menu</h2>
                <a href="menu.php" class="view-all">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="menu-grid">
                <div class="menu-item">
                    <div class="img-container">
                        <img src="assets/images/menu/plain-burger.jpg" alt="Plain Burger">
                        <div class="img-overlay"></div>
                    </div>
                    <div class="menu-item-content">
                        <h3 class="menu-item-title">Plain Burger</h3>
                    </div>
                </div>

                <div class="menu-item">
                    <div class="img-container">
                        <img src="assets/images/menu/bicol-express.jpg" alt="Bicol Express">
                        <div class="img-overlay"></div>
                    </div>
                    <div class="menu-item-content">
                        <h3 class="menu-item-title">Bicol Express</h3>
                    </div>
                </div>

                <div class="menu-item">
                    <div class="img-container">
                        <img src="assets/images/menu/halo-halo.jpg" alt="Halo-Halo">
                        <div class="img-overlay"></div>
                    </div>
                    <div class="menu-item-content">
                        <h3 class="menu-item-title">Halo-Halo</h3>
                    </div>
                </div>

                <div class="menu-item">
                    <div class="img-container">
                        <img src="assets/images/menu/mango-juice.jpg" alt="Mango Juice">
                        <div class="img-overlay"></div>
                    </div>
                    <div class="menu-item-content">
                        <h3 class="menu-item-title">Mango Juice</h3>
                    </div>
                </div>
            </div>
        </div>
    </section>

    

    <?php include 'footer.php'; ?>
    
    <!-- Bootstrap 5 JS - Required for navbar notification dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.querySelector('.scroll-down').addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector('#menu').scrollIntoView({ behavior: 'smooth' });
    });

    // Enhanced intersection observer with smoother animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0) scale(1)';
            }
        });
    }, { 
        threshold: 0.15,
        rootMargin: '0px 0px -100px 0px'
    });

    document.querySelectorAll('.menu-item').forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px) scale(0.95)';
        item.style.transition = 'all 0.8s cubic-bezier(0.25, 0.1, 0.25, 1)';
        item.style.transitionDelay = `${index * 0.1}s`;
        observer.observe(item);
    });

    // Preload images for smoother experience
    function preloadImages() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            const src = img.getAttribute('src');
            if (src) {
                const newImg = new Image();
                newImg.src = src;
            }
        });
    }
    
    window.addEventListener('load', preloadImages);

    // Popup Advertisement Scripts
    document.addEventListener('DOMContentLoaded', function() {
        // Check PHP session status and sessionStorage
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        
        if (!isLoggedIn && !sessionStorage.getItem('popupShown')) {
            setTimeout(showPopup, 2000); // Show popup after 2 seconds
        }

        // Add click handler for signup button
        document.getElementById('signupButton').addEventListener('click', function(e) {
            if (document.getElementById('dontShowAgain').checked) {
                sessionStorage.setItem('popupShown', 'true');
            }
        });
    });

    function showPopup() {
        // Double check session status before showing popup
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        if (!isLoggedIn) {
            document.getElementById('popupOverlay').classList.add('show');
            document.getElementById('popupAd').classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }
    }

    function closePopup() {
        document.getElementById('popupOverlay').classList.remove('show');
        document.getElementById('popupAd').classList.remove('show');
        document.body.style.overflow = ''; // Restore scrolling

        // Check if "Don't show again" is checked
        if (document.getElementById('dontShowAgain').checked) {
            sessionStorage.setItem('popupShown', 'true');
        }
    }

    // Close popup when clicking overlay
    document.getElementById('popupOverlay').addEventListener('click', closePopup);

    // Prevent popup from closing when clicking inside it
    document.getElementById('popupAd').addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Close popup with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePopup();
        }
    });
    </script>
</body>
</html>
</html>