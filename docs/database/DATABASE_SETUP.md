# Database Setup Guide - Eat&Run

## 🗄️ Neon PostgreSQL Connection

This guide will help you connect your Eat&Run application to a Neon PostgreSQL database.

---

## 📋 Prerequisites

- Neon account (https://neon.tech)
- PostgreSQL database created in Neon
- Connection string from Neon console

---

## 🔗 Getting Your Neon Connection String

### Step 1: Open Neon Console
1. Go to https://console.neon.tech
2. Select your project
3. Click on "Connection string" or "Connect"

### Step 2: Copy Connection Details
You'll see a connection string that looks like:
```
postgresql://neondb_owner:your_password@ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com:5432/neondb?sslmode=require
```

Extract these values:
- **Host**: `ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com`
- **User**: `neondb_owner`
- **Password**: `your_password`
- **Database**: `neondb`
- **Port**: `5432`

---

## ⚙️ Configuration Methods

### Method 1: Environment Variables (Recommended)

#### On Linux/Mac:
```bash
export DB_HOST="ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com"
export DB_USER="neondb_owner"
export DB_PASS="your_password"
export DB_NAME="neondb"
export DB_PORT="5432"
```

#### On Windows (PowerShell):
```powershell
$env:DB_HOST="ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com"
$env:DB_USER="neondb_owner"
$env:DB_PASS="your_password"
$env:DB_NAME="neondb"
$env:DB_PORT="5432"
```

#### On Windows (Command Prompt):
```cmd
set DB_HOST=ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com
set DB_USER=neondb_owner
set DB_PASS=your_password
set DB_NAME=neondb
set DB_PORT=5432
```

### Method 2: .env File

1. Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

2. Edit `.env` with your Neon credentials:
```
DB_HOST=ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com
DB_USER=neondb_owner
DB_PASS=your_password
DB_NAME=neondb
DB_PORT=5432
RENDER=0
```

3. Load the .env file in your application:
```php
<?php
// At the top of your main config file
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}
?>
```

### Method 3: Direct Configuration

Edit `config/database/db.php` and modify the defaults:
```php
if (!defined('DB_HOST')) define('DB_HOST', 'ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com');
if (!defined('DB_USER')) define('DB_USER', 'neondb_owner');
if (!defined('DB_PASS')) define('DB_PASS', 'your_password');
if (!defined('DB_NAME')) define('DB_NAME', 'neondb');
if (!defined('DB_PORT')) define('DB_PORT', 5432);
```

---

## 🧪 Testing the Connection

### Create a Test File

Create `test_db_connection.php`:

```php
<?php
// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Include database configuration
require_once 'config/database/db.php';

echo "=== Database Connection Test ===\n\n";

// Test connection
try {
    if (isset($conn)) {
        echo "✓ Connection object created\n";
        
        // Try a simple query
        $result = $conn->query("SELECT 1 as test");
        if ($result) {
            echo "✓ Query executed successfully\n";
            echo "✓ Database connection is working!\n";
        } else {
            echo "✗ Query failed\n";
        }
    } else {
        echo "✗ Connection object not created\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Connection Details ===\n";
echo "Host: " . getenv('DB_HOST') . "\n";
echo "User: " . getenv('DB_USER') . "\n";
echo "Database: " . getenv('DB_NAME') . "\n";
echo "Port: " . getenv('DB_PORT') . "\n";
?>
```

### Run the Test

```bash
php test_db_connection.php
```

Expected output:
```
=== Database Connection Test ===

✓ Connection object created
✓ Query executed successfully
✓ Database connection is working!

=== Connection Details ===
Host: ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com
User: neondb_owner
Database: neondb
Port: 5432
```

---

## 🗂️ Database Schema Setup

### Option 1: Import Existing Schema

If you have a database dump file:

```bash
# Using psql command line
psql -h ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com \
     -U neondb_owner \
     -d neondb \
     -f database_dump.sql
```

### Option 2: Create Tables Manually

Use the Neon SQL Editor to create tables:

```sql
-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'customer',
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Menu items table
CREATE TABLE menu_items (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100),
    image_url VARCHAR(255),
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    total_amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Order items table
CREATE TABLE order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders(id),
    menu_item_id INTEGER NOT NULL REFERENCES menu_items(id),
    quantity INTEGER NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reviews table
CREATE TABLE reviews (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    menu_item_id INTEGER NOT NULL REFERENCES menu_items(id),
    rating INTEGER CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔐 Security Best Practices

### 1. Never Commit Credentials
Add `.env` to `.gitignore`:
```
.env
.env.local
.env.*.local
```

### 2. Use Strong Passwords
- Neon generates strong passwords by default
- Don't share your password
- Rotate passwords regularly

### 3. Use SSL/TLS
- Neon requires SSL connections
- The configuration already includes `sslmode=require`
- This is automatically handled

### 4. Limit Database Access
- Use Neon's IP whitelist feature
- Only allow connections from your application servers
- Restrict admin access

### 5. Environment-Specific Configs
- Use different databases for development, staging, and production
- Never use production credentials in development
- Use separate Neon projects for each environment

---

## 🐛 Troubleshooting

### Connection Refused
**Problem**: `Connection refused` error

**Solutions**:
1. Verify host is correct (check Neon console)
2. Verify port is 5432
3. Check if SSL is required (it is for Neon)
4. Verify credentials are correct

### Authentication Failed
**Problem**: `FATAL: password authentication failed`

**Solutions**:
1. Double-check password in Neon console
2. Verify username is correct
3. Check for special characters in password
4. Reset password in Neon console if needed

### Database Not Found
**Problem**: `database "neondb" does not exist`

**Solutions**:
1. Verify database name is correct
2. Check if database was created in Neon
3. Create database if it doesn't exist

### SSL Connection Error
**Problem**: `SSL connection error`

**Solutions**:
1. Ensure `sslmode=require` is set
2. Update PHP PostgreSQL extension
3. Check firewall settings

### Timeout Error
**Problem**: `Connection timeout`

**Solutions**:
1. Check internet connection
2. Verify Neon service is running
3. Check firewall/network settings
4. Increase timeout value in config

---

## 📊 Monitoring Connection

### Check Connection Status

Create `check_db_status.php`:

```php
<?php
require_once 'config/database/db.php';

$status = [
    'connected' => false,
    'host' => getenv('DB_HOST'),
    'database' => getenv('DB_NAME'),
    'user' => getenv('DB_USER'),
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    $result = $conn->query("SELECT 1");
    $status['connected'] = $result ? true : false;
} catch (Exception $e) {
    $status['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($status, JSON_PRETTY_PRINT);
?>
```

Access: `http://example.com/check_db_status.php`

---

## 🚀 Deployment

### On Render.com

1. Set environment variables in Render dashboard:
   - `DB_HOST`
   - `DB_USER`
   - `DB_PASS`
   - `DB_NAME`
   - `DB_PORT`
   - `RENDER=1`

2. The application will automatically detect Render and use PostgreSQL

### On Other Platforms

1. Set environment variables in your hosting control panel
2. Or use `.env` file (ensure it's not in version control)
3. Or modify `config/database/db.php` directly

---

## 📚 Additional Resources

- [Neon Documentation](https://neon.tech/docs)
- [PostgreSQL Documentation](https://www.postgresql.org/docs)
- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)

---

## ✅ Verification Checklist

- [ ] Neon account created
- [ ] Database created in Neon
- [ ] Connection string copied
- [ ] Environment variables set
- [ ] Test connection successful
- [ ] Database schema created
- [ ] Application can read/write data
- [ ] SSL connection working
- [ ] Credentials secured

---

## 🆘 Getting Help

If you encounter issues:

1. Check the troubleshooting section above
2. Review Neon documentation
3. Check application logs
4. Verify environment variables are set correctly
5. Test connection with `test_db_connection.php`

---

**Last Updated**: May 27, 2026
**Status**: ✅ Ready for Production
