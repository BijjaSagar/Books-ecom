# Quick Start Guide - Bookory Admin Dashboard

## ⚡ 5-Minute Quick Start

### 1. Copy Environment File
```bash
cp .env.example .env
```

### 2. Edit Configuration
```bash
nano .env
# Update: DB_HOST, DB_USERNAME, DB_PASSWORD, ADMIN_EMAIL
```

### 3. Run Database Migrations
```bash
# Via Web Browser
# Navigate to: https://yourdomain.com/admin/setup-migrations.php

# Or via CLI
php admin/setup-migrations.php
```

### 4. Access Dashboard
```
Admin URL: https://yourdomain.com/admin/dashboard.php
Mobile URL: https://yourdomain.com/admin/mobile-dashboard.php
Login: admin / password
```

---

## 📋 Complete Setup (30 Minutes)

### Step 1: Download & Setup (2 min)
```bash
cd /home/user/Books-ecom
cp .env.example .env
chmod 600 .env
```

### Step 2: Configure Environment (5 min)
```bash
nano .env
# Fill in:
# - Database credentials
# - PayPal API keys
# - Razorpay API keys
# - Email settings
# - Admin email
```

### Step 3: Database Setup (5 min)
```bash
# Option A: Web Interface
# Visit: /admin/setup-migrations.php

# Option B: Command Line
php admin/setup-migrations.php

# Verify success
mysql -u root -p u618910819_bookshelf_db -e "SHOW TABLES LIKE 'sales_analytics';"
```

### Step 4: Configure Web Server (10 min)
```bash
# Apache
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers
sudo cp /var/www/bookory/.htaccess.example /var/www/bookory/.htaccess

# Nginx - use config from PRODUCTION_DEPLOYMENT.md
```

### Step 5: Setup Cron Jobs (5 min)
```bash
sudo -u www-data crontab -e

# Add:
0 6 * * * /usr/bin/php /home/user/Books-ecom/cron-reports.php
0 2 * * * /usr/bin/php /home/user/Books-ecom/cron-sync-payments.php
```

### Step 6: Test Everything (3 min)
```bash
# Test login
curl -I https://yourdomain.com/admin/

# Test email
php -r "require 'cron-reports.php';"

# Check logs
tail /var/log/bookory/reports.log
```

---

## 🎯 Feature Breakdown

### Installed Features

| Feature | Location | Status |
|---------|----------|--------|
| Sales Dashboard | `/admin/sales-dashboard.php` | ✅ Ready |
| Inventory Management | `/admin/inventory-management.php` | ✅ Ready |
| Advertising Campaigns | `/admin/advertising-campaigns.php` | ✅ Ready |
| Financial Dashboard | `/admin/financial-dashboard.php` | ✅ Ready |
| Performance Metrics | `/admin/performance-metrics.php` | ✅ Ready |
| Bulk Upload | `/admin/bulk-upload.php` | ✅ Ready |
| Payment Analytics | `/admin/payment-analytics.php` | ✅ Ready |
| Mobile PWA | `/admin/mobile-dashboard.php` | ✅ Ready |
| Email Reports | `/cron-reports.php` | ⚙️ Requires Setup |
| Advanced Search | `/admin/` (integrated) | ✅ Ready |

---

## 🔧 Configuration Quick Reference

### Database
```env
DB_HOST=localhost
DB_DATABASE=u618910819_bookshelf_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### PayPal
```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=xxx
PAYPAL_SECRET=xxx
```

### Razorpay
```env
RAZORPAY_KEY_ID=xxx
RAZORPAY_KEY_SECRET=xxx
```

### Email
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
ADMIN_EMAIL=admin@yourdomain.com
```

---

## 📱 Mobile Setup

### Install PWA on Mobile
1. **iOS:**
   - Open `/admin/mobile-dashboard.php` in Safari
   - Tap **Share** → **Add to Home Screen**

2. **Android:**
   - Open in Chrome
   - Tap **Menu** → **Install app**

### Features on Mobile
- Offline access (cached data)
- Native app experience
- Bottom navigation
- Real-time sync
- Push notifications (optional)

---

## 📊 Dashboard Overview

### Main Dashboards

**Sales Performance Dashboard**
- Revenue metrics (daily/weekly/monthly)
- Order trends
- Top selling products
- Category breakdown
- CSV/PDF export

**Inventory Management**
- Real-time stock levels
- Low stock alerts
- Reorder recommendations
- Transaction history
- Stock filtering

**Financial Dashboard**
- Revenue & expense tracking
- Profit analysis
- Cost breakdown (COGS, fees, etc.)
- Monthly comparisons
- Settlement tracking

**Payment Analytics**
- PayPal integration
- Razorpay integration
- Transaction tracking
- Failed payment detection
- Gateway comparison

**Performance Metrics**
- Account health score (0-100%)
- Customer feedback
- Return rate monitoring
- KPI dashboard
- Recommendations

**Bulk Product Upload**
- CSV/Excel file upload
- Drag-and-drop interface
- Three upload types:
  - Products (create/update)
  - Inventory (stock only)
  - Prices (price only)
- Upload history

---

## 🔐 Security Checklist

Before Going Live:

- [ ] HTTPS enabled
- [ ] .env file permissions (600)
- [ ] Database user restricted
- [ ] Admin credentials changed
- [ ] Backup system configured
- [ ] Firewall rules configured
- [ ] Error logging enabled
- [ ] Monitoring setup
- [ ] Rate limiting enabled
- [ ] CSRF protection active

---

## 📝 Database Tables Reference

### Analytics (4 tables)
- `sales_analytics` - Daily sales metrics
- `product_analytics` - Per-product performance
- `category_performance` - Category metrics
- `account_health` - Health scores

### Inventory (3 tables)
- `inventory_alerts` - Stock warnings
- `inventory_transactions` - Transaction history
- `bulk_uploads` - Upload tracking

### Advertising (2 tables)
- `advertising_campaigns` - Campaign data
- `campaign_keywords` - Keyword bids

### Financial (2 tables)
- `financial_reports` - Financial records
- `seller_payouts` - Settlement tracking

### Payments (3 tables)
- `payment_transactions` - Generic tracking
- `paypal_transactions` - PayPal data
- `razorpay_transactions` - Razorpay data

### Advanced (5 tables)
- `saved_searches` - Filter profiles
- `audit_log` - Action tracking
- `realtime_events` - Event queue
- `websocket_connections` - Active connections
- `widget_preferences` - Customization

---

## 🆘 Troubleshooting

### Database Issues
```bash
# Check connection
mysql -u root -p -h localhost
USE u618910819_bookshelf_db;
SHOW TABLES;

# If tables missing, run migrations
php admin/setup-migrations.php
```

### Email Not Sending
```bash
# Test SMTP
php -r "
mail('test@example.com', 'Test', 'Message');
"

# Check mail config
php -i | grep mail
```

### Permission Errors
```bash
# Fix file permissions
chmod 755 /home/user/Books-ecom/
chmod 755 /home/user/Books-ecom/uploads/
chmod 644 /home/user/Books-ecom/*.php
chown -R www-data:www-data /home/user/Books-ecom/
```

### Cron Not Running
```bash
# Check service
systemctl status cron

# Verify crontab
sudo -u www-data crontab -l

# Check logs
grep CRON /var/log/syslog | tail -5
```

---

## 📞 Support Resources

### Documentation Files
- **FEATURES.md** - Complete feature documentation
- **SETUP_EMAIL_CRON.md** - Email and cron setup
- **MOBILE_PWA_TESTING.md** - PWA testing guide
- **PRODUCTION_DEPLOYMENT.md** - Production setup

### API Documentation
- **PayPal API:** https://developer.paypal.com
- **Razorpay API:** https://razorpay.com/docs
- **Bootstrap:** https://getbootstrap.com/docs
- **Chart.js:** https://www.chartjs.org/docs

### Contact
- GitHub Issues: https://github.com/BijjaSagar/Books-ecom/issues
- Email: support@bookory.local

---

## 🎉 You're All Set!

Your Bookory Admin Dashboard is now:
- ✅ Fully configured
- ✅ All features installed
- ✅ Database ready
- ✅ Email configured
- ✅ Cron jobs running
- ✅ Mobile PWA ready
- ✅ Production ready

**Next Steps:**
1. Test all features
2. Configure monitoring
3. Set up backups
4. Train team
5. Go live!

---

**Last Updated:** November 2024
**Version:** 2.0.0
**Status:** Production Ready ✓
