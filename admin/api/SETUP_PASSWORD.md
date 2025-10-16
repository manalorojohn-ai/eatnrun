# 🔐 Database Password Setup Guide

## ⚠️ IMPORTANT: Set Your Database Password

The error "Access denied for user 'root'@'192.168.0.104' (using password: NO)" means the password is not set.

### Step 1: Edit the Configuration File

Open `admin/api/database_config.php` and change this line:

```php
// Change this:
define('REMOTE_DB_PASS', 'your_actual_password_here');

// To this (replace with your actual password):
define('REMOTE_DB_PASS', 'your_real_password');
```

### Step 2: Common Password Options

If you don't know your password, try these common ones:

```php
// Option 1: No password (if your MySQL doesn't require one)
define('REMOTE_DB_PASS', '');

// Option 2: Common XAMPP password
define('REMOTE_DB_PASS', '');

// Option 3: Common MySQL password
define('REMOTE_DB_PASS', 'root');

// Option 4: Your custom password
define('REMOTE_DB_PASS', 'your_custom_password');
```

### Step 3: Test the Connection

After setting the password, test it:

1. Visit: `http://localhost:3000/admin/api/test_remote_connection.php`
2. Check if it shows "✅ Successfully connected"

### Step 4: If Still Not Working

If you still get "Access denied", you need to:

1. **Check if the remote server allows connections**
2. **Grant permissions on the remote server**
3. **Check firewall settings**

### Quick Test

Try this first - set no password:
```php
define('REMOTE_DB_PASS', '');
```

Then test the connection. If it works, you know the issue was the password.
