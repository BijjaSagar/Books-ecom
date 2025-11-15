# 📚 Books Bookstore - Hostinger Setup Guide

Complete step-by-step guide for setting up the Books Bookstore application on Hostinger.

---

## 🚀 **Step 1: Get Your Database Credentials from Hostinger**

1. **Log into hPanel** (https://hpanel.hostinger.com)
2. Go to **Hosting** → **MySQL Databases**
3. Find your database (should look like `u618910819_xxxxx`)
4. Click **Manage** button
5. You'll see a form with these fields:
   - **Database Name**: `u618910819_bookshelf_db` (or similar)
   - **MySQL Username**: `u618910819_xxxxx` (copy this)
   - **MySQL Password**: Your password (copy this)
   - **MySQL Host**: Usually `localhost` on shared hosting

**⚠️ Important:** Copy these values carefully!

---

## 📝 **Step 2: Update Configuration File**

Edit `includes/config.php` on your server:

### Option A: Using FTP
1. Connect via FTP to your Hostinger account
2. Navigate to `/includes/` folder
3. Download `config.php`
4. Open with text editor
5. Update these lines:

```php
$db_host = 'localhost';                    // From Hostinger (usually localhost)
$db_username = 'u618910819_books';         // YOUR MySQL Username
$db_password = 'your_password_here';       // YOUR MySQL Password
$db_name = 'u618910819_bookshelf_db';      // YOUR Database Name
```

6. Save and upload back

### Option B: Using Hostinger File Manager
1. In hPanel, go to **Files** → **File Manager**
2. Navigate to `public_html/Books-ecom/includes/`
3. Right-click `config.php` → **Edit**
4. Update the database credentials
5. Save

---

## 🧪 **Step 3: Test Your Database Connection**

1. Upload the entire `Books-ecom` folder to your Hostinger hosting
2. Visit: `https://yourdomain.com/setup/test-connection.php`
3. You should see:
   - ✓ Connection Successful
   - MySQL Version details
   - Character set information

**If connection fails:**
- Double-check username and password
- Verify database name spelling
- Make sure host is correct (usually `localhost`)
- Check with Hostinger support if needed

---

## 🗄️ **Step 4: Create Database Tables**

After connection test passes:

1. Visit: `https://yourdomain.com/setup/database-init.php`
2. Click **"✓ Create Tables"** button
3. Wait for all tables to be created (should show ~10 green checkmarks)
4. You should see:
   ```
   ✓ users
   ✓ categories  
   ✓ products
   ✓ orders
   ✓ order_items
   ✓ product_reviews
   ✓ coupons
   ✓ wishlists
   ✓ email_queue
   ✓ support_tickets
   ✓ system_settings
   ```

**⚠️ IMPORTANT:** Delete `database-init.php` after this step for security!

---

## 📊 **Step 5: Seed Sample Data**

Run the database seeder to populate test data:

### Option A: Using SSH Terminal (if available on your plan)
```bash
cd public_html/Books-ecom
php cron/seed-database.php
```

### Option B: Using cPanel/File Manager
1. In File Manager, navigate to `cron/` folder
2. Create a simple file `run-seeder.php`:
```php
<?php
require_once '../includes/config.php';
include 'seed-database.php';
?>
```
3. Visit: `https://yourdomain.com/Books-ecom/run-seeder.php`

### What gets created:
- **8 Product Categories**
- **20 Sample Books** with prices and descriptions
- **Test Customer Account**: 
  - Email: `customer@bookstore.com`
  - Password: `password123`
- **Test Admin Account**:
  - Email: `admin@bookstore.com`
  - Password: `admin123`

---

## 🔐 **Step 6: Access Your Application**

### Customer Site
```
https://yourdomain.com/Books-ecom/
```

### Admin Panel
```
https://yourdomain.com/Books-ecom/admin/
```
- Email: `admin@bookstore.com`
- Password: `admin123`

---

## ⚙️ **Step 7: Set Up Email Cron Job (Optional)**

For automatic email sending, set up a cron job:

1. In hPanel, go to **Advanced** → **Cron Jobs**
2. Create new cron job with these settings:
   - **Email address**: Your email
   - **Frequency**: Every 5 minutes
   - **Command**:
   ```
   php /home/u618910819/public_html/Books-ecom/cron/send-emails.php
   ```
   
   (Replace `u618910819` with your Hostinger account number)

This processes the email queue automatically every 5 minutes.

---

## 📋 **Troubleshooting**

### "Database Connection Failed"
- **Solution**: Check credentials in `config.php`
- Visit test page: `/setup/test-connection.php`
- Get correct values from hPanel MySQL Databases section

### "Table already exists"
- **Solution**: This is normal, safe to run setup again
- Tables won't be recreated if they exist

### "Can't connect to MySQL server"
- **Cause**: Wrong host or credentials
- **Solution**: 
  1. Verify host is `localhost` (for shared hosting)
  2. Check username matches exactly from hPanel
  3. Check password has no typos

### Admin panel shows blank page
- **Cause**: PHP error
- **Solution**:
  1. Check if all tables were created
  2. Verify `config.php` has correct database info
  3. Check error logs in hPanel

### Email not sending
- **Cause**: Cron job not set up or SMTP not configured
- **Solution**:
  1. Set up cron job in hPanel (see Step 7)
  2. Or send emails manually by visiting: `/admin/notifications.php`

---

## 📁 **File Structure on Hostinger**

After upload, your folder structure should be:

```
public_html/
├── Books-ecom/
│   ├── admin/              # Admin pages
│   ├── customer/           # Customer pages
│   ├── includes/           # Backend (config.php here!)
│   ├── api/                # REST API
│   ├── cron/               # Background jobs
│   ├── setup/              # Setup scripts (DELETE after setup!)
│   ├── README.md
│   ├── API_DOCUMENTATION.md
│   └── HOSTINGER_SETUP.md  # This file
```

---

## 🔒 **Security Checklist**

After setup, secure your installation:

- [ ] Delete `/setup/database-init.php`
- [ ] Delete `/setup/test-connection.php`
- [ ] Change admin password in `/admin/` → Settings
- [ ] Change customer test account password
- [ ] Set up HTTPS (hPanel → SSL Certificates)
- [ ] Review `/includes/config.php` permissions
- [ ] Remove sample products if going live
- [ ] Set up proper email notifications

---

## 🆘 **Need Help?**

### If you still have issues:

1. **Check Hostinger Docs**: hPanel usually has MySQL setup guides
2. **Test Database Directly**: 
   - Use phpMyAdmin in hPanel
   - Go to MySQL Databases → Manage
   - Open phpMyAdmin to verify database is working
3. **Contact Hostinger Support**: They can verify your credentials

---

## ✨ **You're Ready!**

Your Books Bookstore e-commerce platform is now ready to use on Hostinger!

**Key URLs:**
- **Customer Site**: https://yourdomain.com/Books-ecom/
- **Admin Panel**: https://yourdomain.com/Books-ecom/admin/
- **API**: https://yourdomain.com/Books-ecom/api/

Enjoy your e-commerce platform! 🎉
