# How to Deploy Using render.yaml on Render

## Quick Overview
The `render.yaml` file is a configuration file that tells Render how to build and run your Eat&Run application. Instead of manually configuring everything in the Render dashboard, the YAML file automates the entire setup process.

## Prerequisites
Before starting, you need:
1. **GitHub Account** - Your code must be in a GitHub repository
2. **Render Account** - Create one at https://render.com (sign up with GitHub)
3. **Neon Database** - Create a PostgreSQL database at https://neon.tech
4. **Gmail Account** - With app password generated for email sending

## Step-by-Step Deployment Guide

### Step 1: Prepare Your GitHub Repository

1. Make sure your code is pushed to GitHub with the `render.yaml` file:
```bash
git add .
git commit -m "Ready for Render deployment"
git push origin main
```

2. Verify the `render.yaml` file is in the **root directory** of your repository

### Step 2: Create Neon PostgreSQL Database

1. Go to https://neon.tech
2. Sign up or log in
3. Create a new project
4. Copy your database credentials:
   - **Host:** (looks like `ec2-xxx-xxx-xxx-xxx.compute-1.amazonaws.com`)
   - **Database:** `neondb`
   - **User:** `neondb_owner`
   - **Password:** (your generated password)
   - **Port:** `5432`

**Keep these credentials handy - you'll need them in Render**

### Step 3: Connect Render to GitHub

1. Go to https://render.com
2. Click on your profile icon (top right) → **Dashboard**
3. Click **+ New** → **Web Service**
4. In the "Connect a repository" section, click **Connect Account** (GitHub)
5. Authorize Render to access your GitHub
6. Select your repository (`eatnrun`)

### Step 4: Render Auto-Detection

**Important:** Since your `render.yaml` file is in the root directory:

1. After selecting the repository, Render will **automatically detect** `render.yaml`
2. You'll see a message: "A render.yaml file was found in your repo"
3. Click **Deploy using Render configuration** (or it may auto-proceed)

### Step 5: Add Environment Variables

On the configuration page, you need to add environment variables for your database credentials:

**Go to Environment tab and add these variables:**

| Variable | Value | Example |
|----------|-------|---------|
| `DB_HOST` | Your Neon host | `ec2-52-12-34-56.compute-1.amazonaws.com` |
| `DB_USER` | Neon username | `neondb_owner` |
| `DB_PASS` | Neon password | `your_password_here` |
| `DB_NAME` | Database name | `neondb` |
| `GMAIL_APP_PASSWORD` | Gmail app password | `xeyf snnt dvnq bqpb` |

**How to add variables:**
1. Click **Add Environment Variable**
2. Enter the variable name (e.g., `DB_HOST`)
3. Enter the value (your Neon host)
4. Click the + icon to add more
5. Repeat for all variables above

### Step 6: Deploy

1. Click **Create Web Service** or **Deploy**
2. Render will start building your application
3. Watch the build logs in real-time

**Build Process (what Render does):**
- Reads `render.yaml` configuration
- Installs PHP dependencies via Composer
- Creates session directory
- Sets up the web server (Apache)
- Deploys your application

### Step 7: Initialize Database

After deployment succeeds:

1. Go to your Render service dashboard
2. Click on your service name
3. Go to **Shell** tab (top right)
4. In the shell, run:
```bash
php docs/setup/init_database.php
```

This creates all necessary database tables (users, categories, menu_items, etc.)

### Step 8: Test Your Application

1. Find your Render URL (looks like `https://eatnrun-xxx.onrender.com`)
2. Visit the URL in your browser
3. Test these features:
   - ✅ Homepage loads
   - ✅ Menu shows items
   - ✅ Try to register
   - ✅ Check email for OTP
   - ✅ Complete registration
   - ✅ Login with test account

## Detailed Explanation of render.yaml

### Services Section
```yaml
services:
  - type: web          # This is a web service (not static, worker, etc.)
    name: eatnrun      # Name of your service
    runtime: php       # Uses PHP runtime
    plan: free         # Free tier (upgrade for production)
```

### Build Command
```yaml
buildCommand: |
  composer install --no-interaction --prefer-dist --optimize-autoloader
  mkdir -p tmp/sessions
  chmod -R 755 tmp/sessions
```
This runs when Render builds your app:
- Installs PHP dependencies
- Creates session storage directory
- Sets proper permissions

### Start Command
```yaml
startCommand: "apache2-foreground"
```
Starts Apache web server to run your PHP application

### Environment Variables
```yaml
envVars:
  - key: DB_HOST
    scope: build,runtime
    value: ${DATABASE_HOST}
```
- `${DATABASE_HOST}` - Placeholder for Render to replace with your value
- `scope: build,runtime` - Used during both build and while running

### Health Check
```yaml
healthCheckPath: /
```
Render checks if your app is running by visiting `/`
If it fails, Render will automatically restart

### Persistent Storage (Disk)
```yaml
disk:
  name: eatnrun-storage
  mountPath: /var/www/html/uploads
  sizeGB: 1
```
Keeps uploaded files between restarts
- 1GB storage for user uploads
- Files persist even if service restarts

## Environment Variables Explained

### What They Do

**Database Variables:**
- `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` → Connect to Neon database
- `DB_PORT` → PostgreSQL port (always 5432)

**Email Variables:**
- `MAIL_USERNAME` → Gmail address (eatnrun70@gmail.com)
- `MAIL_PASSWORD` → Gmail app password

**Application Variables:**
- `RENDER` → Set to 1 to enable Render mode
- `APP_ENV` → Set to "production" for live site
- `APP_DEBUG` → Set to 0 to hide debug info
- `SITE_URL` → Set to your Render URL

### How to Set Them

**Method 1: Render Dashboard (During Setup)**
1. In the configuration form, add each variable
2. Click **Create Web Service**

**Method 2: Render Dashboard (After Deployment)**
1. Go to your service
2. Click **Environment** tab
3. Click **Add Environment Variable**
4. Enter name and value
5. Click **Save**
6. Your app will auto-redeploy

## Monitoring and Logs

### View Deployment Logs
1. Go to your Render service
2. Click **Logs** tab
3. See real-time build and runtime logs
4. Search for errors or debug info

### Common Log Messages
```
Composer installing...                    # Installing dependencies
Apache starting...                        # Web server starting
[notice] Database connected               # DB connection successful
Warning: session_save_path()              # Session warnings (fixed)
```

### Troubleshooting with Logs

If deployment fails:
1. Check the red error messages in logs
2. Look for specific error code or message
3. Common issues:
   - Missing environment variables
   - Database connection failed
   - Composer dependency issues

## After Deployment

### Run Database Setup
```bash
# In Render Shell tab:
php docs/setup/init_database.php
```

This creates all tables with sample data.

### Test Email System
```bash
# Optional - test email functionality
php test_email.php
```
(Will send test email to eatnrun70@gmail.com)

### Monitor Performance
1. Check **Metrics** tab for:
   - CPU usage
   - Memory usage
   - Requests per second
   - Error rate

### Update Code
Simply push to GitHub:
```bash
git push origin main
```
If autoDeploy is enabled in render.yaml, Render automatically redeploys with your changes.

## Common Issues and Solutions

### Issue: "Composer install failed"
**Solution:**
- Check `composer.json` is valid
- Ensure `composer.lock` is committed
- Look for incompatible PHP version requirements

### Issue: "Database connection failed"
**Solution:**
- Verify all DB_* environment variables are correct
- Check Neon database is running
- Ensure Render IP can access Neon
- Test with: `psql -h host -U user -d dbname -p 5432`

### Issue: "Session save path error"
**Solution:**
- This is already fixed in our config.php
- If you still see it, check the fix was applied

### Issue: "Uploads not persisting"
**Solution:**
- Check disk is attached to service
- Verify mountPath is `/var/www/html/uploads`
- Ensure directory permissions are 755

### Issue: "Emails not sending"
**Solution:**
- Verify MAIL_USERNAME and MAIL_PASSWORD
- Check Gmail app password is correct (not regular password)
- Check 2FA is enabled on Gmail
- Look for SMTP errors in logs

## Scaling Your Application

### Current Setup (Free Tier)
- Reasonable for development and testing
- May be slow for production
- Limited resources

### Upgrade Steps

1. **Go to Plan Selection**
   - Service page → Settings → Plan
   - Choose "Standard" or higher

2. **Add More Resources**
   - Increase disk storage
   - Upgrade database plan on Neon

3. **Enable Auto-scaling**
   - Only available on paid plans
   - Service automatically scales based on traffic

## Security Best Practices

### Protect Environment Variables
- ✅ Never commit actual values to Git
- ✅ Use Render's environment variable system
- ✅ Rotate sensitive values regularly

### Database Security
- ✅ Use strong passwords
- ✅ Enable SSL connections
- ✅ Limit database access

### Application Security
- ✅ Keep dependencies updated: `composer update`
- ✅ Enable HTTPS (Render provides free SSL)
- ✅ Implement rate limiting
- ✅ Use CSRF tokens (already in place)

## Next Steps

1. ✅ Push code to GitHub
2. ✅ Create Neon database
3. ✅ Connect Render to GitHub
4. ✅ Add environment variables
5. ✅ Deploy using render.yaml
6. ✅ Run database initialization
7. ✅ Test application
8. ✅ Share your live URL!

## Support Resources

- **Render Docs:** https://render.com/docs
- **Neon Docs:** https://neon.tech/docs
- **Render Community:** https://render.com/community
- **Status Page:** https://status.render.com

## Your Render URL Format
After deployment, your app will be at:
```
https://eatnrun-xxx.onrender.com
```

The `xxx` is automatically generated by Render.

---

**Last Updated:** June 7, 2026
**Yaml Version:** 1.0
**Status:** Production Ready
