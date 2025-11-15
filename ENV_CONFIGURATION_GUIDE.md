# 🔐 Environment Configuration Guide

Complete guide for configuring your Bookory production environment securely.

---

## 📋 Table of Contents

1. [Quick Start](#quick-start)
2. [Configuration Files](#configuration-files)
3. [Step-by-Step Setup](#step-by-step-setup)
4. [Security Best Practices](#security-best-practices)
5. [Hostinger-Specific Instructions](#hostinger-specific-instructions)
6. [Payment Gateway Setup](#payment-gateway-setup)
7. [Email Configuration](#email-configuration)
8. [Troubleshooting](#troubleshooting)

---

## 🚀 Quick Start

### For Hostinger Users

```bash
# 1. Copy the Hostinger template
cp .env.hostinger .env

# 2. Edit .env with your credentials
nano .env
# OR use File Manager in hPanel

# 3. Secure the file
chmod 600 .env

# 4. Test your configuration
php -r "require 'includes/env.php'; echo 'Environment loaded successfully!';"
```

### For Other Hosting Providers

```bash
# 1. Copy the example template
cp .env.example .env

# 2. Edit with your hosting details
nano .env

# 3. Secure the file
chmod 600 .env
```

---

## 📁 Configuration Files

Your project now has three configuration templates:

| File | Purpose | When to Use |
|------|---------|-------------|
| `.env.example` | Generic template with all options | Any hosting provider |
| `.env.hostinger` | Hostinger-specific template | Hostinger shared hosting |
| `.env` | **Your actual config** (create this) | Production/Development |

**IMPORTANT:** Only `.env` is used by the application. The others are templates.

---

## 🔧 Step-by-Step Setup

### Step 1: Get Database Credentials

#### Hostinger
1. Log into [hPanel](https://hpanel.hostinger.com)
2. Go to **Hosting** → **MySQL Databases**
3. Click **Manage** next to your database
4. Copy these values:
   - Database Name: `u618910819_bookshelf_db`
   - MySQL Username: `u618910819_books`
   - MySQL Password: *(your password)*
   - MySQL Host: `localhost`

#### Other Providers
- **cPanel**: MySQL Databases section
- **Plesk**: Databases section
- **VPS/Dedicated**: Use root credentials or create new user

### Step 2: Create .env File

```bash
# On your server (via SSH)
cd /path/to/Books-ecom
cp .env.hostinger .env

# OR using FTP
# Download .env.hostinger
# Rename to .env
# Upload back to server
```

### Step 3: Configure Database

Edit `.env` and update these lines:

```ini
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u618910819_bookshelf_db
DB_USERNAME=u618910819_books
DB_PASSWORD=your_actual_password_here
```

**Test database connection:**
```bash
php setup/test-connection.php
```

### Step 4: Configure Application URL

```ini
APP_URL=https://yourdomain.com
```

This is used for:
- Email links
- Session cookies
- Absolute URLs in emails

### Step 5: Configure Email (SMTP)

#### Hostinger Email
```ini
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
ADMIN_EMAIL=admin@yourdomain.com
```

#### Gmail SMTP
```ini
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your.email@gmail.com
MAIL_PASSWORD=your_app_password
```

**Note:** Gmail requires [App Password](https://support.google.com/accounts/answer/185833)

#### SendGrid
```ini
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=apikey
MAIL_PASSWORD=YOUR_SENDGRID_API_KEY
```

### Step 6: Configure Payment Gateways

See [Payment Gateway Setup](#payment-gateway-setup) section below.

### Step 7: Secure the .env File

```bash
# Set restrictive permissions (owner read/write only)
chmod 600 .env

# Verify permissions
ls -la .env
# Should show: -rw------- (600)

# Ensure .env is in .gitignore
echo ".env" >> .gitignore
```

---

## 🔒 Security Best Practices

### 1. File Permissions

```bash
# .env file - Owner read/write only
chmod 600 .env

# includes/config.php - Owner read/write only
chmod 600 includes/config.php

# includes/env.php - Owner read/write only
chmod 600 includes/env.php

# Uploads directory - Owner read/write/execute
chmod 755 public/images/products/
chmod 755 uploads/

# Logs directory
mkdir -p logs
chmod 755 logs
```

### 2. Production Security Checklist

**BEFORE going live, verify these settings in .env:**

```ini
# ✅ Production Environment
APP_ENV=production
APP_DEBUG=false
DEVELOPMENT_MODE=false
DISPLAY_ERRORS=false
ERROR_REPORTING=0

# ✅ Secure Sessions
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# ✅ CSRF Protection
CSRF_PROTECTION=true

# ✅ Strong Credentials
# - No default passwords
# - No "password", "123456", etc.
# - Minimum 12 characters
# - Mix of letters, numbers, symbols

# ✅ Payment Gateways in Live Mode
PAYPAL_MODE=live
# Use live keys for Stripe, Razorpay
```

### 3. Password Security

**Generate Strong Passwords:**

```bash
# Linux/Mac - Generate 32 character password
openssl rand -base64 32

# PHP - Generate random string
php -r "echo bin2hex(random_bytes(16));"

# Online (use trusted source only)
# https://passwordsgenerator.net/
```

**Examples of INSECURE passwords to avoid:**
- `password`
- `123456`
- `admin123`
- `yourpassword`
- `CHANGE_THIS_PASSWORD`

### 4. SSL/HTTPS Configuration

**Hostinger automatically provides free SSL. Enable it:**

1. hPanel → **SSL** section
2. Click **Install SSL**
3. Wait 10-15 minutes for activation

**Force HTTPS in .htaccess:**

```apache
# Add to .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 5. Hide Sensitive Files

**Add to .htaccess:**

```apache
# Protect .env file
<Files .env>
    Order allow,deny
    Deny from all
</Files>

# Protect configuration files
<FilesMatch "^(config|env)\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Disable directory listing
Options -Indexes
```

### 6. Database Security

**Use least-privilege database user:**

```sql
-- Create dedicated database user
CREATE USER 'bookory_app'@'localhost' IDENTIFIED BY 'strong_password_here';

-- Grant only necessary permissions
GRANT SELECT, INSERT, UPDATE, DELETE
ON u618910819_bookshelf_db.*
TO 'bookory_app'@'localhost';

FLUSH PRIVILEGES;
```

**Update .env:**
```ini
DB_USERNAME=bookory_app
DB_PASSWORD=strong_password_here
```

### 7. Regular Security Updates

```bash
# Create monthly security checklist
# □ Update passwords every 90 days
# □ Review access logs
# □ Check for PHP updates
# □ Review database backups
# □ Test backup restoration
# □ Review user accounts
# □ Check for failed login attempts
```

---

## 🏠 Hostinger-Specific Instructions

### Getting Your Credentials

1. **Database Credentials**
   - hPanel → Hosting → MySQL Databases
   - Click "Manage" → Copy credentials

2. **Email Credentials**
   - hPanel → Email → Email Accounts
   - Click "Manage" → View password
   - Or create new email account

3. **File Manager Access**
   - hPanel → Files → File Manager
   - Navigate to `public_html/Books-ecom/`
   - Right-click `.env.hostinger` → Rename to `.env`
   - Right-click `.env` → Edit

### Hostinger File Structure

```
/home/u618910819/
├── public_html/           # Your web root
│   └── Books-ecom/        # Application
│       ├── .env           # Configuration (CREATE THIS)
│       ├── includes/
│       ├── admin/
│       └── ...
├── logs/                  # Application logs
└── backups/               # Database backups
```

### Setting Up Cron Jobs on Hostinger

1. hPanel → **Advanced** → **Cron Jobs**
2. Click **Create Cron Job**

**Email Queue Processor (Every 5 minutes):**
```bash
*/5 * * * * /usr/bin/php /home/u618910819/public_html/Books-ecom/cron/send-emails.php
```

**Daily Reports (Every day at 6 AM):**
```bash
0 6 * * * /usr/bin/php /home/u618910819/public_html/Books-ecom/cron/cron-reports.php
```

**Database Backup (Every day at 2 AM):**
```bash
0 2 * * * /usr/bin/php /home/u618910819/public_html/Books-ecom/cron/backup-database.php
```

### Hostinger Performance Tips

```ini
# In .env - Optimize for shared hosting
CACHE_DRIVER=file
ENABLE_QUERY_CACHE=true
ENABLE_GZIP=true
LOG_LEVEL=error
ENABLE_ACCESS_LOGGING=false
```

---

## 💳 Payment Gateway Setup

### PayPal Integration

**1. Create PayPal Business Account**
- Visit [PayPal Business](https://www.paypal.com/business)
- Sign up for business account

**2. Get API Credentials**
- Log into [PayPal Developer](https://developer.paypal.com/dashboard/)
- Go to **My Apps & Credentials**
- Under **Live**, click **Create App**
- Copy **Client ID** and **Secret**

**3. Configure in .env**
```ini
PAYPAL_MODE=live
PAYPAL_CLIENT_ID=YOUR_LIVE_CLIENT_ID
PAYPAL_SECRET=YOUR_LIVE_SECRET
```

**For Testing (Sandbox):**
```ini
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=YOUR_SANDBOX_CLIENT_ID
PAYPAL_SECRET=YOUR_SANDBOX_SECRET
```

### Stripe Integration

**1. Create Stripe Account**
- Visit [Stripe Dashboard](https://dashboard.stripe.com/register)
- Complete business verification

**2. Get API Keys**
- Dashboard → **Developers** → **API Keys**
- Copy **Publishable key** and **Secret key**

**3. Configure in .env**
```ini
# Production keys
STRIPE_PUBLIC_KEY=pk_live_xxxxxxxxxxxxx
STRIPE_SECRET_KEY=sk_live_xxxxxxxxxxxxx

# Test keys (for testing)
# STRIPE_PUBLIC_KEY=pk_test_xxxxxxxxxxxxx
# STRIPE_SECRET_KEY=sk_test_xxxxxxxxxxxxx
```

**4. Set up Webhooks**
- Dashboard → **Developers** → **Webhooks**
- Add endpoint: `https://yourdomain.com/webhooks/stripe.php`
- Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`

### Razorpay Integration (India)

**1. Create Razorpay Account**
- Visit [Razorpay](https://dashboard.razorpay.com/signup)
- Complete KYC verification

**2. Get API Keys**
- Dashboard → **Settings** → **API Keys**
- Generate keys (Live/Test)

**3. Configure in .env**
```ini
# Production
RAZORPAY_KEY_ID=rzp_live_xxxxxxxxxxxxx
RAZORPAY_KEY_SECRET=xxxxxxxxxxxxxxxx

# Testing
# RAZORPAY_KEY_ID=rzp_test_xxxxxxxxxxxxx
# RAZORPAY_KEY_SECRET=xxxxxxxxxxxxxxxx
```

### Testing Payment Gateways

**Always test in sandbox/test mode first:**

```ini
# Testing configuration
PAYPAL_MODE=sandbox
STRIPE_PUBLIC_KEY=pk_test_xxxxx
STRIPE_SECRET_KEY=sk_test_xxxxx
RAZORPAY_KEY_ID=rzp_test_xxxxx
```

**Test cards:**
- Stripe: `4242 4242 4242 4242` (any future date, any CVV)
- PayPal: Use sandbox test accounts
- Razorpay: `4111 1111 1111 1111`

---

## 📧 Email Configuration

### Email Templates Location

```
includes/email_templates/
├── order_confirmation.html
├── shipment_notification.html
├── delivery_confirmation.html
├── refund_notification.html
├── review_request.html
├── newsletter.html
├── promotional.html
└── support_ticket.html
```

### Testing Email Configuration

Create `test-email.php`:

```php
<?php
require_once 'includes/config.php';

// Test email
$to = 'your-email@example.com';
$subject = 'Bookory Email Test';
$message = 'If you receive this, email configuration is working!';
$headers = 'From: ' . MAIL_FROM_ADDRESS;

if (mail($to, $subject, $message, $headers)) {
    echo "✅ Email sent successfully!";
} else {
    echo "❌ Email failed to send.";
}
?>
```

Visit: `https://yourdomain.com/test-email.php`

### Common Email Issues

**Problem:** Emails not sending

**Solutions:**
1. Check SMTP credentials in .env
2. Verify email account exists in hPanel
3. Check spam folder
4. Review logs: `logs/YYYY-MM-DD.log`
5. Test SMTP connection:

```bash
telnet smtp.hostinger.com 465
```

**Problem:** Emails going to spam

**Solutions:**
1. Set up SPF record
2. Set up DKIM
3. Use dedicated email address (not noreply)
4. Avoid spam trigger words

---

## 🐛 Troubleshooting

### Issue: "Database connection failed"

**Checklist:**
1. ✅ .env file exists and readable
2. ✅ DB credentials are correct
3. ✅ Database exists in hPanel
4. ✅ DB_HOST is 'localhost' (for Hostinger)
5. ✅ MySQL service is running

**Test connection:**
```php
<?php
// test-db.php
$host = 'localhost';
$user = 'your_username';
$pass = 'your_password';
$db = 'your_database';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Database connected successfully!";
$conn->close();
?>
```

### Issue: "Environment file not found"

**Solution:**
```bash
# Check file exists
ls -la .env

# If not, create it
cp .env.hostinger .env

# Check permissions
chmod 600 .env
```

### Issue: Session errors

**Solution:**
```ini
# In .env, adjust session settings
SESSION_SECURE=false  # If not using HTTPS yet
SESSION_SAME_SITE=lax  # Less strict
```

### Issue: 500 Internal Server Error

**Debug steps:**
1. Enable error display temporarily:
   ```ini
   APP_DEBUG=true
   DISPLAY_ERRORS=true
   ```

2. Check error logs:
   ```bash
   tail -f logs/$(date +%Y-%m-%d).log
   ```

3. Check Apache/PHP error logs (Hostinger):
   - hPanel → Files → File Manager
   - Navigate to `/logs/error_log`

### Issue: Payment gateway not working

**Checklist:**
1. ✅ Using correct mode (live/sandbox)
2. ✅ API keys are correct
3. ✅ Webhooks configured (if required)
4. ✅ SSL certificate installed
5. ✅ Currency matches gateway settings

### Issue: Emails not sending

**Debug:**
```php
// Add to cron/send-emails.php
error_log("Email debug: " . print_r($email_config, true));
```

**Check:**
1. SMTP credentials
2. Port 465 is open (firewall)
3. Email account exists
4. SPF/DKIM records

---

## 📚 Additional Resources

### Documentation
- [Hostinger Setup Guide](HOSTINGER_SETUP.md)
- [Deployment Summary](DEPLOYMENT_SUMMARY.md)
- [Security Fixes](SECURITY_FIXES.md)
- [API Documentation](API_DOCUMENTATION.md)

### Hostinger Support
- Knowledge Base: https://support.hostinger.com
- Live Chat: Available in hPanel
- Email: support@hostinger.com

### Payment Gateway Docs
- PayPal: https://developer.paypal.com/docs/
- Stripe: https://stripe.com/docs
- Razorpay: https://razorpay.com/docs/

### Security Resources
- OWASP Top 10: https://owasp.org/www-project-top-ten/
- PHP Security: https://www.php.net/manual/en/security.php
- SSL Test: https://www.ssllabs.com/ssltest/

---

## ✅ Production Launch Checklist

Before going live, verify:

### Environment Configuration
- [ ] `.env` file created with production values
- [ ] All `CHANGE_THIS_*` placeholders updated
- [ ] Strong, unique passwords used
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `DISPLAY_ERRORS=false`

### Database
- [ ] Database created and accessible
- [ ] Tables created via `setup/database-init.php`
- [ ] Sample data removed (if any)
- [ ] Database backups configured

### Security
- [ ] `.env` file permissions set to 600
- [ ] `.env` added to `.gitignore`
- [ ] SSL certificate installed
- [ ] HTTPS enforced via .htaccess
- [ ] Security headers configured
- [ ] Admin password changed from default

### Email
- [ ] SMTP configured correctly
- [ ] Test email sent successfully
- [ ] Email templates customized
- [ ] SPF/DKIM records set up

### Payment Gateways
- [ ] PayPal in live mode with live keys
- [ ] Stripe in live mode with live keys
- [ ] Razorpay in live mode (if applicable)
- [ ] Test transaction completed
- [ ] Webhooks configured

### Performance
- [ ] Cron jobs set up
- [ ] Cache configured
- [ ] File permissions optimized
- [ ] Error logging enabled

### Testing
- [ ] User registration works
- [ ] Login/logout works
- [ ] Product browsing works
- [ ] Add to cart works
- [ ] Checkout process works
- [ ] Payment processing works
- [ ] Email notifications sent
- [ ] Admin panel accessible
- [ ] Order management works

---

## 🆘 Need Help?

1. **Check logs first:**
   ```bash
   tail -f logs/$(date +%Y-%m-%d).log
   ```

2. **Enable debug mode temporarily:**
   ```ini
   APP_DEBUG=true
   DISPLAY_ERRORS=true
   DEBUG_SQL=true
   ```

3. **Contact support:**
   - Hostinger: hPanel live chat
   - Payment gateways: Developer support
   - Community: Stack Overflow with `[php]` tag

---

**Last Updated:** November 15, 2025
**Version:** 2.0.0
