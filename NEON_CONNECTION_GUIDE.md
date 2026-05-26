# 🗄️ Neon PostgreSQL Connection Guide

## Quick Start (5 Minutes)

### Step 1: Get Your Neon Connection String

1. Go to https://console.neon.tech
2. Select your project
3. Click "Connection string" or "Connect"
4. Copy the connection string (looks like: `postgresql://user:password@host:5432/database?sslmode=require`)

### Step 2: Extract Connection Details

From the connection string, extract:
- **Host**: `ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com`
- **User**: `neondb_owner`
- **Password**: `your_password`
- **Database**: `neondb`
- **Port**: `5432`

### Step 3: Create .env File

Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

Edit `.env` with your Neon details:
```
DB_HOST=ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com
DB_USER=neondb_owner
DB_PASS=your_password
DB_NAME=neondb
DB_PORT=5432
RENDER=0
```

### Step 4: Test Connection

Run the test script:
```bash
php test_db_connection.php
```

Or use the setup wizard:
```bash
php setup_neon_connection.php test
```

Expected output:
```
✓ Connection Successful
Host: ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com
User: neondb_owner
Database: neondb
Port: 5432
Timestamp: 2026-05-27 12:00:00
```

---

## 📋 Detailed Setup Instructions

### For Windows Users

#### Using Command Prompt

1. Create `.env` file:
```cmd
copy .env.example .env
```

2. Edit `.env` with your credentials (use Notepad or any text editor)

3. Test connection:
```cmd
php test_db_connection.php
```

#### Using PowerShell

1. Create `.env` file:
```powershell
Copy-Item .env.example .env
```

2. Edit `.env`:
```powershell
notepad .env
```

3. Test connection:
```powershell
php test_db_connection.php
```

### For Mac/Linux Users

1. Create `.env` file:
```bash
cp .env.example .env
```

2. Edit `.env`:
```bash
nano .env
# or
vim .env
```

3. Test connection:
```bash
php test_db_connection.php
```

---

## 🔍 Finding Your Neon Connection Details

### Method 1: Neon Console (Easiest)

1. Log in to https://console.neon.tech
2. Select your project
3. Click on "Connection string" button
4. Select "Connection string" tab
5. Copy the full connection string
6. Extract the details as shown above

### Method 2: Neon Dashboard

1. Go to https://console.neon.tech
2. Click on your project
3. Go to "Connection details" section
4. You'll see:
   - Host
   - User
   - Password
   - Database name
   - Port (usually 5432)

### Method 3: From Connection String

If you have a connection string like:
```
postgresql://neondb_owner:your_password@ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com:5432/neondb?sslmode=require
```

Extract as follows:
```
postgresql://[USER]:[PASSWORD]@[HOST]:[PORT]/[DATABASE]?sslmode=require
```

---

## 🧪 Testing Your Connection

### Method 1: Web Interface

1. Open `test_db_connection.php` in your browser
2. Enter your Neon connection details
3. Click "Test Connection"
4. You should see a success message

### Method 2: Command Line

```bash
php test_db_connection.php
```

### Method 3: Interactive Setup

```bash
php setup_neon_connection.php setup
```

This will guide you through the setup process interactively.

### Method 4: Check Status

```bash
php setup_neon_connection.php status
```

This shows your current connection status.

---

## 🔐 Security Best Practices

### 1. Never Commit .env File

Add to `.gitignore`:
```
.env
.env.local
.env.*.local
```

### 2. Use Environment Variables

Instead of hardcoding credentials, use environment variables:

```php
<?php
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$password = getenv('DB_PASS');
$database = getenv('DB_NAME');
$port = getenv('DB_PORT');
?>
```

### 3. Rotate Passwords Regularly

In Neon console:
1. Go to your project
2. Click "Reset password"
3. Update your `.env` file

### 4. Use Strong Passwords

- Neon generates strong passwords by default
- Don't modify them unless necessary
- Use at least 16 characters

### 5. Limit Database Access

In Neon console:
1. Go to "Settings"
2. Configure IP whitelist
3. Only allow your application servers

---

## 🐛 Troubleshooting

### Connection Refused

**Error**: `Connection refused`

**Causes**:
- Wrong host
- Wrong port
- Neon service down

**Solutions**:
1. Verify host from Neon console
2. Ensure port is 5432
3. Check internet connection
4. Check Neon service status

### Authentication Failed

**Error**: `FATAL: password authentication failed`

**Causes**:
- Wrong password
- Wrong username
- Special characters in password

**Solutions**:
1. Copy password from Neon console again
2. Verify username is correct
3. Check for special characters
4. Reset password in Neon if needed

### Database Not Found

**Error**: `database "neondb" does not exist`

**Causes**:
- Wrong database name
- Database not created

**Solutions**:
1. Verify database name from Neon console
2. Create database if it doesn't exist
3. Check spelling

### SSL Connection Error

**Error**: `SSL connection error`

**Causes**:
- SSL not enabled
- Old PHP version
- Missing PostgreSQL extension

**Solutions**:
1. Ensure `sslmode=require` is set
2. Update PHP to latest version
3. Install PostgreSQL extension: `php-pgsql`

### Timeout Error

**Error**: `Connection timeout`

**Causes**:
- Slow internet
- Firewall blocking
- Neon service slow

**Solutions**:
1. Check internet connection
2. Check firewall settings
3. Try again later
4. Increase timeout value

---

## 📊 Connection Details Reference

### Neon Connection String Format

```
postgresql://[USER]:[PASSWORD]@[HOST]:[PORT]/[DATABASE]?sslmode=require
```

### PHP PDO Connection

```php
<?php
$dsn = "pgsql:host=$host;port=$port;dbname=$database;sslmode=require";
$conn = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 5
]);
?>
```

### Environment Variables

```
DB_HOST=ec2-xx-xxx-xxx-xxx.compute-1.amazonaws.com
DB_USER=neondb_owner
DB_PASS=your_password
DB_NAME=neondb
DB_PORT=5432
```

---

## 🚀 Deployment

### On Render.com

1. Go to your Render service
2. Go to "Environment"
3. Add variables:
   - `DB_HOST`
   - `DB_USER`
   - `DB_PASS`
   - `DB_NAME`
   - `DB_PORT`
   - `RENDER=1`

4. Deploy

### On Heroku

1. Go to your Heroku app
2. Go to "Settings"
3. Click "Reveal Config Vars"
4. Add:
   - `DB_HOST`
   - `DB_USER`
   - `DB_PASS`
   - `DB_NAME`
   - `DB_PORT`

5. Deploy

### On Other Platforms

1. Set environment variables in your hosting control panel
2. Or use `.env` file (ensure it's not in version control)
3. Or modify `config/database/db.php` directly

---

## 📚 Useful Commands

### Test Connection (CLI)

```bash
php test_db_connection.php
```

### Interactive Setup

```bash
php setup_neon_connection.php setup
```

### Check Status

```bash
php setup_neon_connection.php status
```

### View Help

```bash
php setup_neon_connection.php help
```

---

## ✅ Verification Checklist

- [ ] Neon account created
- [ ] Database created in Neon
- [ ] Connection string copied
- [ ] .env file created
- [ ] Credentials entered in .env
- [ ] Test connection successful
- [ ] Application can read data
- [ ] Application can write data
- [ ] SSL connection working
- [ ] Credentials secured

---

## 🆘 Getting Help

### Resources

- [Neon Documentation](https://neon.tech/docs)
- [PostgreSQL Documentation](https://www.postgresql.org/docs)
- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)

### Support

1. Check troubleshooting section above
2. Review Neon documentation
3. Check application logs
4. Run `test_db_connection.php`
5. Contact Neon support

---

## 📝 Example .env File

```
# Eat&Run Database Configuration
# Generated: 2026-05-27

# PostgreSQL/Neon Configuration
DB_HOST=ec2-12-345-678-901.compute-1.amazonaws.com
DB_USER=neondb_owner
DB_PASS=your_secure_password_here
DB_NAME=neondb
DB_PORT=5432

# Optional: Set to 1 if running on Render
RENDER=0

# Application Settings
APP_ENV=production
APP_DEBUG=0
```

---

## 🎯 Next Steps

1. ✅ Create .env file
2. ✅ Add Neon credentials
3. ✅ Test connection
4. ✅ Deploy application
5. ✅ Monitor performance

---

**Last Updated**: May 27, 2026
**Status**: ✅ Ready for Production
**Support**: Neon PostgreSQL
