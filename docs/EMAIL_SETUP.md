# Email Configuration Guide - Eat&Run

## Overview
This document outlines the email setup for Eat&Run using Gmail SMTP and PHPMailer with Composer.

## Configuration Details

### Email Account
- **Gmail:** eatnrun70@gmail.com
- **App Password:** xeyf snnt dvnq bqpb

### SMTP Settings
- **Host:** smtp.gmail.com
- **Port:** 587
- **Security:** STARTTLS
- **Authentication:** Enabled

## Features Implemented

### 1. User Registration Email
**File:** `pages/auth/register.php`
- Sends verification OTP email when user registers
- 6-digit OTP generated automatically
- OTP expires in 10 minutes
- User must verify email to complete registration

### 2. Forgot Password Email
**File:** `actions/auth/reset_password_email.php`
- Sends password reset link to user's email
- Reset token valid for 1 hour
- Secure token generation using random bytes

### 3. Password Reset Flow
**Files:** 
- `pages/auth/forgot-password.php` - Forgot password form
- `pages/auth/reset-password.php` - Password reset form

### 4. Order Verification Email
**File:** `actions/auth/verify_email.php`
- Sends verification code after order placement
- 6-digit code generated and expires in 10 minutes

## Email Templates

### Registration Email
- Welcome message
- 6-digit OTP code
- Expiry information
- Instructions to verify

### Password Reset Email
- Password reset request confirmation
- Clickable reset link
- Link expiry (1 hour)
- Security notice

### Order Verification Email
- Order confirmation
- 6-digit verification code
- Expiry information
- Support instructions

## Setup Instructions

### 1. Composer Installation
PHPMailer is installed via Composer:
```bash
composer install
```

### 2. Email Configuration
All email configurations use:
- From: eatnrun70@gmail.com
- Reply-To: eatnrun70@gmail.com
- SMTP: smtp.gmail.com:587

### 3. Database Requirements
Ensure these tables exist:
```sql
-- Users table should have columns:
ALTER TABLE users ADD COLUMN reset_token VARCHAR(64);
ALTER TABLE users ADD COLUMN reset_expiry DATETIME;
ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT 0;

-- Email verifications table:
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expiry DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## Testing

### Manual Test
1. Visit `http://yourdomain.com/test_email.php` (temporary test file)
2. Check email logs for connection status
3. Verify test email arrives in eatnrun70@gmail.com

### Registration Test
1. Go to Register page
2. Fill in all required fields
3. Check email for OTP code
4. Enter OTP to verify email

### Forgot Password Test
1. Go to Forgot Password page
2. Enter registered email
3. Check email for reset link
4. Click link and set new password

## Troubleshooting

### Issue: "SMTP connection failed"
- Check Gmail credentials are correct
- Verify port 587 is not blocked
- Ensure app password is being used (not regular password)
- Enable "Less secure app access" in Gmail if needed

### Issue: "Failed to send email"
- Check internet connection
- Verify email address is valid
- Look in `/tmp/` or logs for detailed error messages
- Test using the email test file

### Issue: "OTP not received"
- Check spam/junk folder
- Verify email address is correct
- Ensure OTP hasn't expired (10 minutes)
- Resend OTP option available

## Security Notes

1. **App Password:** Never commit real passwords to Git
2. **Reset Tokens:** Generated using random_bytes() for security
3. **OTP Expiry:** Set to 10 minutes for registration and orders
4. **Token Expiry:** Set to 1 hour for password resets
5. **Rate Limiting:** Consider adding rate limiting for email sends

## Environment Variables (Optional)

For production, consider using environment variables:
```php
$mail->Username = getenv('MAIL_USERNAME');
$mail->Password = getenv('MAIL_PASSWORD');
```

## File Structure

```
eatnrun/
├── vendor/
│   └── phpmailer/phpmailer/
│       └── src/
│           ├── PHPMailer.php
│           ├── SMTP.php
│           └── Exception.php
├── pages/auth/
│   ├── register.php          # Registration with OTP email
│   ├── login.php
│   ├── forgot-password.php   # Forgot password form
│   └── reset-password.php    # Password reset form
├── actions/auth/
│   ├── verify_email.php      # Order verification email
│   └── reset_password_email.php  # Send password reset link
├── test_email.php            # Email configuration test
└── docs/
    └── EMAIL_SETUP.md        # This file
```

## Next Steps

1. Run database migrations to add required columns
2. Test email sending with test_email.php
3. Test registration flow
4. Test forgot password flow
5. Remove test_email.php from production

## Support

For issues or questions:
1. Check error logs in browser console
2. Review server error logs
3. Test SMTP connection manually
4. Verify Gmail credentials and app password

---
Last Updated: 2026-06-07
Maintainer: Eat&Run Development Team
