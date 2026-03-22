<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/images/logo.png" alt="Eat&Run Logo" class="logo-img">
            <span class="brand-text ml-2">Eat&Run</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                       href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'restaurants.php' ? 'active' : ''; ?>" 
                       href="restaurants.php">Restaurants</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'active' : ''; ?>" 
                       href="menu.php">Menu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>" 
                       href="about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_orders.php' ? 'active' : ''; ?>" 
                       href="my_orders.php">My Orders</a>
                </li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link cart-link <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : ''; ?>" 
                           href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-text">Cart</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user-circle"></i>
                            <span class="ml-1">Account</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user mr-2"></i>Profile
                            </a>
                            <a class="dropdown-item" href="my_orders.php">
                                <i class="fas fa-shopping-bag mr-2"></i>My Orders
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-success login-btn" href="login.php">Login</a>
                    </li>
                    <li class="nav-item ml-2">
                        <a class="nav-link btn btn-success register-btn" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar {
    padding: 1rem 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
}

.navbar-brand {
    font-weight: 600;
    font-size: 1.25rem;
    color: #006400;
}

.logo-img {
    height: 35px;
    width: auto;
}

.brand-text {
    color: #006400;
    font-weight: 600;
}

.nav-link {
    font-weight: 500;
    color: #4a5568 !important;
    padding: 0.5rem 1rem !important;
    transition: all 0.2s ease;
}

.nav-link:hover {
    color: #006400 !important;
}

.nav-link.active {
    color: #006400 !important;
    font-weight: 600;
}

.cart-link {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cart-text {
    margin-left: 4px;
}

.login-btn, .register-btn {
    padding: 0.5rem 1.25rem !important;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.login-btn {
    border: 1px solid #006400;
    color: #006400 !important;
}

.login-btn:hover {
    background-color: #006400;
    color: white !important;
}

.register-btn {
    background-color: #006400;
    color: white !important;
}

.register-btn:hover {
    background-color: #005500;
    transform: translateY(-1px);
}

.dropdown-menu {
    border: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-radius: 8px;
    padding: 0.5rem;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: #f7fafc;
    color: #006400;
}

.dropdown-divider {
    margin: 0.5rem 0;
}

@media (max-width: 991.98px) {
    .navbar-nav {
        padding: 1rem 0;
    }
    
    .nav-item {
        margin: 0.25rem 0;
    }
    
    .login-btn, .register-btn {
        display: block;
        text-align: center;
        margin: 0.5rem 0;
    }
}
</style>

<!-- Add padding to body to account for fixed navbar -->
<div style="padding-top: 76px;"></div> 