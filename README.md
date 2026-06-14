# Eat&Run - Online Food Ordering System

A modern, fast, and responsive food ordering web application built with PHP, JavaScript, and PostgreSQL.

## 🎯 Quick Start

### Prerequisites
- PHP 7.4+
- PostgreSQL (or Neon)
- Composer
- Node.js (optional, for frontend tools)

### Setup

1. **Clone the repository**
```bash
git clone https://github.com/manalorojohn-ai/eatnrun.git
cd eatnrun
```

2. **Install dependencies**
```bash
composer install
```

3. **Configure environment**
Copy `.env.example` to `.env` and update with your database credentials:
```bash
cp .env.example .env
```

4. **Initialize database**
Visit `https://your-domain.com/setup_neon_database.php` or run:
```bash
php setup_neon_database.php
```

5. **Start the application**
```bash
php -S localhost:8000
```

## 📁 Project Structure

```
eatnrun/
├── actions/              # AJAX endpoint handlers
│   ├── cart/            # Cart operations (add, update, remove)
│   ├── menu/            # Menu filtering & search
│   └── ...
├── admin/               # Admin dashboard & management
├── api/                 # REST API endpoints
├── assets/              # Frontend resources
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   │   ├── ajax-handler.js      # Core AJAX utilities
│   │   ├── cart-ajax.js         # Cart AJAX operations
│   │   ├── menu-ajax.js         # Menu AJAX operations
│   │   └── ...
│   └── images/         # Product images
├── config/             # Configuration files
│   ├── database/       # Database setup
│   └── db.php          # DB connection bridge
├── database/           # Database migrations & seeds
│   ├── food_ordering_export.sql
│   └── insert_test_users.sql
├── includes/           # Reusable components
│   ├── ui/            # Header, navbar, footer, etc.
│   ├── services/      # Email, notifications, etc.
│   └── helpers/       # Utility functions
├── pages/             # Page templates (organized by feature)
│   ├── auth/          # Login, register, password recovery
│   ├── ordering/      # Menu, cart, checkout
│   ├── account/       # User profile, orders, settings
│   └── ...
├── vendor/            # Composer dependencies
├── index.php          # Main router
├── setup_neon_database.php  # Database initializer
└── .env               # Environment variables (git-ignored)
```

## 🚀 Features

### User-Facing
- **Menu Browsing** - View products with AJAX filtering
- **Real-time Search** - Debounced search with instant results
- **Shopping Cart** - Add/remove/update items without page reloads
- **Checkout** - Secure order placement
- **Order History** - View past orders
- **User Profile** - Manage account settings

### Technical
- **AJAX-Powered** - Smooth, fast user experience
- **Responsive Design** - Mobile-first approach
- **Database Abstraction** - Works with MySQL, PostgreSQL
- **Security** - Parameterized queries, password hashing
- **Performance** - Caching, lazy loading, optimized queries

## 🔐 Test Credentials

**Email:** `maria@yahoo.com`  
**Password:** `maria123`

Other test accounts:
- `john@gmail.com` / `password123`
- `juan@outlook.com` / `juan2024`

## 📊 Database

Uses **Neon PostgreSQL** with the following main tables:

- `users` - User accounts
- `menu_items` - Food products
- `categories` - Product categories
- `cart` - Shopping carts
- `orders` - Order history
- `order_details` - Order items
- `ratings` - Product reviews

## 🛠️ API Endpoints

### Cart Operations
- `POST /actions/cart/add_to_cart.php` - Add item to cart
- `POST /actions/cart/update_cart.php` - Update quantity
- `POST /actions/cart/remove_from_cart.php` - Remove item
- `GET /actions/cart/get_cart_count.php` - Get cart item count
- `GET /actions/cart/get_cart_items.php` - Get all cart items

### Menu Operations
- `GET /actions/menu/filter_menu.php?category=X&search=Y` - Filter/search products

## 🎨 Frontend Architecture

### AJAX Layer (`assets/js/`)
- **ajax-handler.js** - Core AJAX utilities with caching & deduplication
- **cart-ajax.js** - Cart operations with real-time updates
- **menu-ajax.js** - Menu filtering & search without page reload

### Notification System
- Toast notifications for success/error/info/warning
- Auto-dismiss after 4 seconds
- User can manually close

### Loading Indicators
- Top progress bar during AJAX operations
- Visual feedback for better UX

## 📝 Environment Variables

```env
DB_HOST=your-database-host
DB_USER=your-username
DB_PASS=your-password
DB_NAME=neondb
DB_PORT=5432
RENDER=1
```

## 🌐 Deployment

### On Render
1. Connect GitHub repository
2. Set environment variables in Render dashboard
3. Deploy - app will automatically initialize database on first run

### Local Development
```bash
php -S localhost:8000
# Visit http://localhost:8000
```

## 📚 Key Files

- **index.php** - Main router, handles URL rewriting
- **setup_neon_database.php** - Initialize database schema
- **pages/auth/login.php** - Authentication with test user fallback
- **config/database/db.php** - Database abstraction layer

## 🐛 Troubleshooting

### Database Connection Failed
- Check `.env` credentials
- Verify Neon database is active
- Run `setup_neon_database.php`

### Login Not Working
- Use test credentials provided above
- Check browser console (F12) for errors
- Verify database initialization completed

### AJAX Requests Failing
- Check browser Network tab (F12)
- Verify endpoints exist in `/actions/`
- Check PHP error logs

## 📞 Support

For issues or questions:
1. Check the logs in Render dashboard
2. Review browser console errors (F12)
3. Verify `.env` configuration
4. Run database setup script

## 📄 License

This project is part of the Eat&Run food ordering platform.

---

**Status:** ✅ Connected to Neon PostgreSQL | ✅ AJAX Enhanced | ✅ Production Ready
