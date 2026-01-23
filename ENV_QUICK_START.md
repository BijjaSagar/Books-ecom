# ⚡ Environment Configuration - Quick Start

**5-Minute Setup Guide for Hostinger**

---

## 🎯 Prerequisites

- [ ] Hostinger hosting account active
- [ ] Domain pointed to Hostinger
- [ ] Files uploaded to `public_html/Books-ecom/`
- [ ] hPanel access credentials

---

## 🚀 Setup Steps

### Step 1: Create .env File (2 minutes)

**Option A: Via SSH**
```bash
cd /home/u618910819/public_html/Books-ecom/
cp .env.hostinger .env
chmod 600 .env
nano .env
```

**Option B: Via File Manager**
1. hPanel → **Files** → **File Manager**
2. Navigate to `public_html/Books-ecom/`
3. Right-click `.env.hostinger` → **Copy**
4. Rename copy to `.env`
5. Right-click `.env` → **Edit**

### Step 2: Get Database Credentials (1 minute)

1. hPanel → **Hosting** → **MySQL Databases**
2. Click **Manage** next to your database
3. Copy these values:

```
Database Name: u618910819_bookshelf_db
MySQL Username: u618910819_books
MySQL Password: [copy from hPanel]
MySQL Host: localhost
```

### Step 3: Update .env File (2 minutes)

**Find these lines in .env and update:**

```ini
# Line 18-24: Database Configuration
DB_HOST=localhost
DB_DATABASE=u618910819_bookshelf_db
DB_USERNAME=u618910819_books
DB_PASSWORD=PASTE_YOUR_PASSWORD_HERE

# Line 10: Your Domain
APP_URL=https://yourdomain.com

# Line 49-52: Email Configuration
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=YOUR_EMAIL_PASSWORD
MAIL_FROM_ADDRESS=noreply@yourdomain.com
ADMIN_EMAIL=admin@yourdomain.com
```

**Save the file** (Ctrl+O in nano, or Save button in File Manager)

---

## ✅ Test Configuration

### Test 1: Database Connection

Visit: `https://yourdomain.com/setup/test-connection.php`

**Expected:** ✅ Connection Successful

**If failed:** Double-check database credentials in .env

### Test 2: Create Database Tables

Visit: `https://yourdomain.com/setup/database-init.php`

Click **Create Tables** button

**Expected:** 10+ green checkmarks

### Test 3: Access Application

Visit: `https://yourdomain.com/`

**Expected:** Homepage loads with products

---

## 🔐 Security (IMPORTANT!)

```bash
# Secure .env file
chmod 600 .env

# Delete setup files after database creation
rm setup/database-init.php
rm setup/test-connection.php
```

---

## 💳 Payment Gateways (Optional - Configure Later)

### PayPal
1. Get credentials: https://developer.paypal.com/dashboard/
2. Update in .env:
   ```ini
   PAYPAL_MODE=live
   PAYPAL_CLIENT_ID=your_client_id
   PAYPAL_SECRET=your_secret
   ```

### Stripe
1. Get credentials: https://dashboard.stripe.com/apikeys
2. Update in .env:
   ```ini
   STRIPE_PUBLIC_KEY=pk_live_xxxxx
   STRIPE_SECRET_KEY=sk_live_xxxxx
   ```

---

## 📧 Email Setup (Optional - Configure Later)

### Hostinger Email
1. hPanel → **Email** → **Email Accounts**
2. Create: `noreply@yourdomain.com`
3. Copy password
4. Update in .env:
   ```ini
   MAIL_HOST=smtp.hostinger.com
   MAIL_PORT=465
   MAIL_USERNAME=noreply@yourdomain.com
   MAIL_PASSWORD=your_email_password
   ```

---

## 🛠️ Cron Jobs (Recommended)

hPanel → **Advanced** → **Cron Jobs**

**Add these 3 cron jobs:**

```bash
# Every 5 minutes - Process email queue
*/5 * * * * /usr/bin/php /home/u618910819/public_html/Books-ecom/cron/send-emails.php

# Daily at 6 AM - Send reports
0 6 * * * /usr/bin/php /home/u618910819/public_html/Books-ecom/cron/cron-reports.php

# Daily at 2 AM - Backup database
0 2 * * * /usr/bin/php /home/u618910819/public_html/Books-ecom/cron/backup-database.php
```

---

## 🔥 Common Issues & Fixes

### "Database connection failed"
```bash
# Check .env has correct credentials
cat .env | grep DB_

# Test manually
mysql -h localhost -u u618910819_books -p
# Enter password when prompted
```

### "Environment file not found"
```bash
# Make sure .env exists
ls -la .env

# If not, create it
cp .env.hostinger .env
```

### "500 Internal Server Error"
```bash
# Check file permissions
chmod 755 .
chmod 644 *.php
chmod 600 .env

# Check error logs
tail -f ../error_log
```

### "Session errors"
```ini
# In .env, if not using HTTPS yet:
SESSION_SECURE=false
```

---

## 📚 Full Documentation

For detailed instructions, see:
- **[ENV_CONFIGURATION_GUIDE.md](ENV_CONFIGURATION_GUIDE.md)** - Complete configuration guide
- **[HOSTINGER_SETUP.md](HOSTINGER_SETUP.md)** - Hostinger deployment guide
- **[SECURITY_FIXES.md](SECURITY_FIXES.md)** - Security best practices

---

## 🎉 You're Done!

Your Bookory store is now configured and ready to use!

**Next steps:**
1. Create admin account: `/register.php` (first user is auto-admin)
2. Add products: Admin Panel → Products
3. Configure payment gateways
4. Test checkout process
5. Launch! 🚀

---

## 📞 Need Help?

**Hostinger Support:**
- Live Chat: Available in hPanel
- Email: support@hostinger.com
- Docs: https://support.hostinger.com

**Application Issues:**
- Check logs: `logs/YYYY-MM-DD.log`
- Review documentation above
- Enable debug mode temporarily (in .env):
  ```ini
  APP_DEBUG=true
  DISPLAY_ERRORS=true
  ```

---

**Quick Start Version:** 1.0
**Last Updated:** November 15, 2025
