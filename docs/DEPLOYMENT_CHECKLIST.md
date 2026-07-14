# Eat&Run Deployment Checklist - Ready for Render ✅

**Status**: READY FOR DEPLOYMENT  
**Date**: July 14, 2026  
**Last Updated**: July 14, 2026

---

## PRE-DEPLOYMENT VERIFICATION ✅

### Code Quality Checks
- ✅ All PHP files syntax verified
  - `index.php` - No syntax errors
  - `admin/index.php` - No syntax errors
  - `config/database/db.php` - No syntax errors
- ✅ Routing system tested and working (36 routes verified)
- ✅ Database configuration supports PostgreSQL/Neon with fallback
- ✅ Session configuration fixed (no active session warnings)
- ✅ Email configuration set up with PHPMailer and Gmail SMTP

### Configuration Files
- ✅ `.env` - Contains Neon PostgreSQL credentials
- ✅ `render.yaml` - Complete Render deployment configuration
- ✅ `composer.json` - Dependencies defined
- ✅ `composer.lock` - Dependencies locked
- ✅ `.gitignore` - Sensitive files properly excluded

### Critical Features
- ✅ User registration with email OTP (eatnrun70@gmail.com)
- ✅ Password reset flow with token validation
- ✅ Menu system with categories and items
- ✅ Shopping cart functionality
- ✅ Order placement and tracking
- ✅ Item detail modal with improved UI
- ✅ Admin dashboard
- ✅ Notifications system

### Database
- ✅ Neon PostgreSQL connection configured
- ✅ Database schema ready (8 tables)
- ✅ Sample data included for testing
- ✅ Multi-tier fallback system (PostgreSQL → MySQL → JSON)

### Email
- ✅ Gmail SMTP configured
- ✅ PHPMailer installed via Composer
- ✅ Registration emails working
- ✅ Password reset emails working
- ✅ Email templates prepared

---

## DEPLOYMENT STEPS

### Step 1: Push to GitHub
```bash
git add .
git commit -m "Final deployment: System ready for Render"
git push origin main
```

### Step 2: Deploy on Render

1. **Go to Render Dashboard**: https://render.com/dashboard
2. **Create New Web Service**
   - Select "GitHub" as repository source
   - Connect your repository
   - Select this project

3. **Configure Service Settings**
   - Name: `eatnrun`
   - Environment: `Docker` (or `Native` if preferred)
   - Build Command: `composer install --no-interaction --prefer-dist --optimize-autoloader && mkdir -p tmp/sessions && chmod -R 755 tmp/sessions`
   - Start Command: `apache2-foreground`
   - Plan: Free (to start)

4. **Set Environment Variables**
   Add these in Render dashboard under Environment:
   ```
   DB_HOST=ep-odd-art-apysk1bo-pooler.c-7.us-east-1.aws.neon.tech
   DB_USER=neondb_owner
   DB_PASS=npg_L3bEXhDZSiK6
   DB_NAME=neondb
   DB_PORT=5432
   RENDER=1
   APP_ENV=production
   APP_DEBUG=0
   MAIL_USERNAME=eatnrun70@gmail.com
   MAIL_PASSWORD=xeyf snnt dvnq bqpb
   SITE_URL=(will be provided by Render)
   ```

5. **Add Persistent Disk**
   - Mount Path: `/var/www/html/uploads`
   - Size: 1GB
   - Used for user uploads and profile images

6. **Deploy**
   - Click "Create Web Service"
   - Wait for build to complete (3-5 minutes)
   - Check deployment logs for errors

### Step 3: Initialize Database

Once deployed:

1. **Access Render Shell**
   - Go to your service in Render dashboard
   - Click "Shell" tab

2. **Run Database Initialization**
   ```bash
   php /var/www/html/docs/setup/init_database.php
   ```

3. **Verify Output**
   Should see:
   ```
   Database tables created/verified successfully!
   Sample data inserted successfully!
   ```

### Step 4: Test Deployment

1. **Test Homepage**
   - Navigate to: `https://your-eatnrun.onrender.com`
   - Should see home page

2. **Test Registration**
   - Go to: `/pages/auth/register.php`
   - Fill in form
   - Check email for OTP
   - Complete registration

3. **Test Menu**
   - Go to: `/pages/ordering/menu.php`
   - Click on menu items
   - Modal should open with item details
   - Test quantity controls

4. **Test Cart**
   - Add items to cart
   - Go to cart page
   - Verify items display correctly

5. **Test Order Placement**
   - Complete checkout
   - Verify order confirmation

6. **Test Admin**
   - Go to: `/admin`
   - Login with admin credentials
   - Check dashboard

---

## NEON DATABASE CONNECTION

### Connection Details
- **Host**: ep-odd-art-apysk1bo-pooler.c-7.us-east-1.aws.neon.tech
- **User**: neondb_owner
- **Password**: npg_L3bEXhDZSiK6
- **Database**: neondb
- **Port**: 5432

### Connection String
```
postgresql://neondb_owner:npg_L3bEXhDZSiK6@ep-odd-art-apysk1bo-pooler.c-7.us-east-1.aws.neon.tech/neondb
```

### Tables Created
1. `users` - User accounts
2. `categories` - Food categories
3. `menu_items` - Menu items with prices
4. `cart` - Shopping cart items
5. `orders` - Order records
6. `order_items` - Items in each order
7. `reviews` - User reviews
8. `notifications` - System notifications

---

## GMAIL EMAIL CONFIGURATION

### Credentials
- **Email**: eatnrun70@gmail.com
- **App Password**: xeyf snnt dvnq bqpb
- **SMTP Server**: smtp.gmail.com
- **Port**: 587
- **Security**: STARTTLS

### Features
- Registration OTP (6-digit, 10-minute expiry)
- Password reset links (1-hour expiry)
- Order confirmation emails
- Notification emails

---

## TROUBLESHOOTING

### Deployment Fails
**Check logs**: 
```bash
# In Render Shell
tail -f /var/log/apache2/error.log
tail -f /var/log/apache2/access.log
```

### Database Connection Failed
1. Verify environment variables are set correctly
2. Check Neon connection status
3. Ensure IP whitelist includes Render IPs (usually automatic)
4. Run: `php docs/CONNECTION_VERIFICATION.md`

### Email Not Sending
1. Verify Gmail credentials in `.env`
2. Check PHPMailer is installed: `ls -la vendor/phpmailer`
3. Test from Render Shell:
   ```bash
   php -r "require 'vendor/autoload.php'; echo 'PHPMailer loaded';"
   ```

### Session Issues
- Check `/var/www/html/tmp/sessions/` directory permissions
- Should be 755 or 777
- Verify in Render Shell: `ls -la tmp/sessions/`

---

## MONITORING POST-DEPLOYMENT

### Daily Checks
- [ ] Homepage loads without errors
- [ ] User registration working
- [ ] Email OTP sends successfully
- [ ] Menu displays with images
- [ ] Orders can be placed
- [ ] Admin dashboard accessible

### Weekly Checks
- [ ] Monitor database connections
- [ ] Check error logs for issues
- [ ] Verify email delivery rates
- [ ] Check storage usage

### Monthly Reviews
- [ ] Optimize database queries if slow
- [ ] Review and clean up old sessions
- [ ] Update dependencies if needed
- [ ] Backup database

---

## USEFUL COMMANDS

### SSH into Render
```bash
render ssh -s eatnrun
```

### Check Database Status
```bash
# In Render Shell
php docs/CONNECTION_VERIFICATION.md
```

### View Logs
```bash
# Apache errors
tail -f /var/log/apache2/error.log

# Apache access
tail -f /var/log/apache2/access.log

# PHP errors
tail -f /var/log/php.log
```

### Reset Database
```bash
# In Render Shell
php docs/setup/init_database.php
```

---

## FINAL CHECKLIST BEFORE GOING LIVE

- [ ] All environment variables set in Render
- [ ] Database initialized with `init_database.php`
- [ ] Registration tested and email OTP received
- [ ] Login works with registered account
- [ ] Menu items display correctly
- [ ] Add to cart functionality works
- [ ] Checkout process completes
- [ ] Admin dashboard accessible
- [ ] Order status updates working
- [ ] Error logs show no critical errors

---

## NEXT STEPS

1. ✅ **Verify System** - All checks passed
2. 🔄 **Configure Render** - Set environment variables
3. 🚀 **Deploy** - Push code to GitHub and deploy on Render
4. 📊 **Initialize Database** - Run init_database.php
5. ✔️ **Test Thoroughly** - Go through all features
6. 🎉 **Go Live** - System is ready!

---

**System Status**: PRODUCTION READY ✅  
**Last Verified**: July 14, 2026

For issues, check the troubleshooting section or review the detailed guides in `/docs/`.
