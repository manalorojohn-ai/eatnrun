# Render Deployment Guide - Eat&Run

## Overview
This guide explains how to deploy Eat&Run to Render using the provided `render.yaml` configuration file.

## Prerequisites
1. Render account (https://render.com)
2. GitHub repository with the code pushed
3. Neon PostgreSQL database account
4. Gmail account with app password configured

## Deployment Steps

### 1. Prepare Your Repository
Ensure these files are committed and pushed to GitHub:
- `render.yaml` - Render configuration
- `composer.json` - PHP dependencies
- `composer.lock` - Composer lock file
- `.gitignore` - Excludes unnecessary files

### 2. Set Up Neon PostgreSQL Database
1. Go to https://neon.tech
2. Create a new project
3. Get your database credentials:
   - Host: `ec2-xxx-xx-xxx-xxx.compute-1.amazonaws.com`
   - Database: `neondb`
   - User: `neondb_owner`
   - Password: Your password
   - Port: `5432`

### 3. Create Render Service
1. Go to https://render.com/dashboard
2. Click "New +" → "Web Service"
3. Select your GitHub repository
4. Fill in the form:
   - **Name:** eatnrun
   - **Runtime:** PHP
   - **Build Command:** `composer install --no-interaction --prefer-dist --optimize-autoloader && mkdir -p tmp/sessions && chmod -R 755 tmp/sessions`
   - **Start Command:** `apache2-foreground`
   - **Plan:** Free tier (or paid for better performance)

### 4. Configure Environment Variables

Add these environment variables in Render Dashboard:

| Variable | Value | Notes |
|----------|-------|-------|
| `DB_HOST` | From Neon | Database host URL |
| `DB_USER` | From Neon | Database username |
| `DB_PASS` | From Neon | Database password |
| `DB_NAME` | From Neon | Database name |
| `DB_PORT` | `5432` | PostgreSQL port |
| `RENDER` | `1` | Enable Render mode |
| `APP_ENV` | `production` | Environment mode |
| `APP_DEBUG` | `0` | Disable debug mode |
| `MAIL_USERNAME` | `eatnrun70@gmail.com` | Gmail address |
| `MAIL_PASSWORD` | Your Gmail app password | Google generated password |

### 5. Initialize Database
After deployment:
1. Access the Render service logs
2. Run the database initialization:
   ```bash
   php docs/setup/init_database.php
   ```

### 6. Verify Deployment
1. Check service logs for errors
2. Visit your Render URL
3. Test functionality:
   - Homepage loads
   - Menu displays items
   - Registration works
   - Email verification functions

## Configuration Details

### render.yaml Sections

**Web Service Configuration:**
- `runtime: php` - PHP runtime
- `plan: free` - Free tier (upgrade for production)
- `region: oregon` - Server location

**Build Process:**
1. Installs Composer dependencies
2. Creates session directory
3. Sets proper permissions

**Environment Variables:**
- Database credentials from Neon
- Gmail credentials for email
- Render detection flag
- Application settings

**Disk Configuration:**
- Persistent storage for uploads
- 1GB allocation (can be increased)
- Mounted at `/var/www/html/uploads`

**Health Check:**
- Monitors `/` endpoint
- Automatically restarts if unhealthy

## Database Setup

### Tables Created
The application will automatically create:
- `users` - User accounts and authentication
- `categories` - Food categories
- `menu_items` - Menu items
- `cart` - Shopping cart
- `orders` - Customer orders
- `order_items` - Order line items
- `reviews` - Product reviews
- `notifications` - System notifications
- `email_verifications` - Email OTP tracking
- `login_history` - User login tracking

### Initialize Database

**Option 1: Using Render Web Service**
1. SSH into the service
2. Run: `php docs/setup/init_database.php`

**Option 2: Using Neon Console**
1. Go to Neon console
2. Run SQL migrations manually

## Email Configuration

### Gmail Setup
1. Enable 2-factor authentication on Gmail
2. Go to Google Account → Security
3. Create App Password for "Mail"
4. Use this password in `MAIL_PASSWORD`

### Email Features
- **Registration:** OTP verification email
- **Forgot Password:** Reset link email
- **Order Verification:** Order confirmation email

## File Structure on Render

```
/var/www/html/
├── public/          # Web root
├── tmp/
│   └── sessions/    # Session storage (persistent)
├── uploads/         # User uploads (persistent disk)
├── vendor/          # Composer packages
├── pages/
├── actions/
├── includes/
├── config/
├── assets/
├── docs/
└── render.yaml      # This configuration
```

## Troubleshooting

### Build Fails
1. Check if `composer.json` is valid
2. Verify PHP version compatibility
3. Check error logs in Render dashboard

### Database Connection Error
1. Verify Neon database is running
2. Check environment variables match Neon credentials
3. Ensure firewall allows Render IP
4. Test with psql: `psql -h host -U user -d dbname`

### Email Not Sending
1. Verify Gmail app password is correct
2. Check SMTP settings in `config/database/db.php`
3. Look for errors in Render logs
4. Test with `test_email.php`

### Session Issues
1. Verify `tmp/sessions` directory exists
2. Check directory permissions (755)
3. Ensure persistent storage is enabled

### Uploads Not Working
1. Verify disk is attached to service
2. Check upload path matches mountPath
3. Ensure directory permissions are set

## Performance Tips

1. **Use Paid Plan for Production**
   - Free tier may be slow
   - Upgrade to "Standard" for production

2. **Database Optimization**
   - Add indexes to frequently queried columns
   - Use connection pooling
   - Consider Render PostgreSQL service

3. **Caching**
   - Implement Redis for sessions
   - Cache menu items
   - Cache category listings

4. **Assets**
   - Use CDN for static files
   - Compress images before upload
   - Minify CSS/JS

## Security Considerations

1. **Environment Variables**
   - Never commit `.env` files
   - Use Render secrets for sensitive data
   - Rotate passwords regularly

2. **Database Security**
   - Use strong passwords
   - Limit connections
   - Enable SSL connections

3. **Application Security**
   - Keep dependencies updated: `composer update`
   - Enable HTTPS (Render provides free SSL)
   - Implement rate limiting
   - Use CSRF tokens

## Monitoring & Logs

### View Logs
1. Go to Render dashboard
2. Select your service
3. Click "Logs" tab
4. Filter by date/time

### Common Log Messages
- `Composer installing...` - Dependencies being installed
- `Apache starting...` - Web server starting
- `Database connection successful` - DB connected
- `Error: Connection refused` - DB connection failed

## Updating Code

1. Push changes to GitHub
2. Render automatically redeploys (if autoDeploy enabled)
3. Check deployment logs
4. Verify changes live

## Scaling

### For Better Performance
1. Upgrade to paid plan
2. Add more database resources
3. Consider separate caching layer
4. Implement load balancing

### Database Scaling
- Upgrade Neon plan for more connections
- Use read replicas for scaling queries
- Consider separate analytics database

## Cost Estimation (Free Tier)
- Render Web Service: Free
- Neon Database (Free tier): Limited
- Email (Gmail): Free
- **Total: Free**

## Cost Estimation (Production)
- Render Web Service (Standard): ~$7/month
- Neon Database: ~$15/month
- CDN (if needed): ~$10/month
- **Total: ~$32/month minimum**

## Support & Resources

- **Render Documentation:** https://render.com/docs
- **Neon Documentation:** https://neon.tech/docs
- **Composer Documentation:** https://getcomposer.org/doc
- **PHP Documentation:** https://www.php.net/manual

---

Last Updated: 2026-06-07
Deployment Guide Version: 1.0
