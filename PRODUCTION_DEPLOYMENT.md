# Production Deployment Guide

## 🚀 Pre-Deployment Checklist

### Code & Database

- [ ] All code committed to git
- [ ] Database migrations tested in development
- [ ] No debug code left in production files
- [ ] All credentials in .env (not in code)
- [ ] Error logs configured properly
- [ ] Database backups created

### Security

- [ ] HTTPS certificate installed
- [ ] SSL enforced (redirect HTTP to HTTPS)
- [ ] .env file permissions set correctly (600)
- [ ] Database user permissions restricted
- [ ] Admin panel behind authentication
- [ ] Input validation implemented
- [ ] SQL injection prevention (prepared statements)
- [ ] XSS protection enabled
- [ ] CSRF tokens implemented
- [ ] Rate limiting configured
- [ ] Secure headers set (Content-Security-Policy, etc.)

### Performance

- [ ] Database indexes optimized
- [ ] Query performance tested
- [ ] Caching configured
- [ ] CDN configured for static assets
- [ ] Image optimization done
- [ ] Minified CSS/JS
- [ ] Gzip compression enabled

### Features

- [ ] All 10+ features tested
- [ ] Email reports tested
- [ ] Cron jobs tested
- [ ] Payment gateways configured
- [ ] PWA tested on mobile
- [ ] Offline mode works
- [ ] Bulk upload works
- [ ] Advanced search works

### Monitoring & Logging

- [ ] Error logging configured
- [ ] Monitoring setup (Sentry, etc.)
- [ ] Application logs setup
- [ ] Database logs setup
- [ ] Access logs setup
- [ ] Alert system configured

---

## 📋 Deployment Steps

### Step 1: Pre-Deployment Backup

```bash
# Backup database
mysqldump -u root -p u618910819_bookshelf_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup application files
tar -czf bookory_backup_$(date +%Y%m%d_%H%M%S).tar.gz /home/user/Books-ecom/

# Verify backups
ls -lh *.sql *.tar.gz
```

### Step 2: Environment Setup

```bash
# Copy .env.example to .env
cp /home/user/Books-ecom/.env.example /home/user/Books-ecom/.env

# Edit with production values
nano /home/user/Books-ecom/.env
```

**Production .env values:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_HOST=production_db_host
DB_DATABASE=production_database
DB_USERNAME=production_user
DB_PASSWORD=strong_password_here

# PayPal
PAYPAL_MODE=live
PAYPAL_CLIENT_ID=your_live_client_id
PAYPAL_SECRET=your_live_secret

# Email
MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=your_app_password

# Admin settings
ADMIN_EMAIL=admin@yourdomain.com
ENABLE_EMAIL_REPORTS=true
REPORT_FREQUENCY=daily
```

### Step 3: File Permissions

```bash
# Set proper permissions
chmod 644 /home/user/Books-ecom/public/*.php
chmod 755 /home/user/Books-ecom/admin/
chmod 755 /home/user/Books-ecom/includes/
chmod 755 /home/user/Books-ecom/uploads/

# Protect sensitive files
chmod 600 /home/user/Books-ecom/.env
chmod 600 /home/user/Books-ecom/.env.example

# Make cron scripts executable
chmod 755 /home/user/Books-ecom/cron-reports.php
chmod 755 /home/user/Books-ecom/admin/setup-migrations.php

# Set ownership
chown -R www-data:www-data /home/user/Books-ecom/
chown -R www-data:www-data /home/user/Books-ecom/uploads/
chown -R www-data:www-data /var/log/bookory/
```

### Step 4: Run Database Migrations

```bash
# Run migrations
php /home/user/Books-ecom/admin/setup-migrations.php

# Or from CLI
php /home/user/Books-ecom/admin/setup-migrations.php > migration_result.log 2>&1
cat migration_result.log
```

**Expected output:**
```
[2024-11-09 14:30:45] Starting migrations...
[2024-11-09 14:30:45] Processing: 001_add_admin_dashboard_features.sql
  ✓ Executed statement
  ...
[2024-11-09 14:30:47] Processing: 002_add_admin_dashboard_features.sql
  ...
[2024-11-09 14:30:50] Processing: 003_add_advanced_search_and_reporting.sql
  ...
[2024-11-09 14:31:00] ✓ All migrations completed successfully!
```

### Step 5: Configure Web Server

#### Apache Configuration

Create `/etc/apache2/sites-available/bookory.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /home/user/Books-ecom

    # Redirect HTTP to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /home/user/Books-ecom

    # SSL Certificate
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/cert.pem
    SSLCertificateKeyFile /etc/ssl/private/key.pem
    SSLCertificateChainFile /etc/ssl/certs/chain.pem

    # PHP Handler
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>

    # Mod Rewrite
    <Directory /home/user/Books-ecom>
        RewriteEngine On
        AllowOverride All
        Require all granted

        # Deny access to sensitive files
        <FilesMatch "^\.env|\.env\.*|\.git|\.gitignore">
            Deny from all
        </FilesMatch>
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/bookory_error.log
    CustomLog ${APACHE_LOG_DIR}/bookory_access.log combined

    # Security Headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "no-referrer-when-downgrade"
</VirtualHost>
```

Enable site:
```bash
a2ensite bookory.conf
a2enmod rewrite
a2enmod headers
a2enmod ssl
systemctl restart apache2
```

#### Nginx Configuration

Create `/etc/nginx/sites-available/bookory`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /home/user/Books-ecom;

    # SSL Certificates
    ssl_certificate /etc/ssl/certs/cert.pem;
    ssl_certificate_key /etc/ssl/private/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Logging
    access_log /var/log/nginx/bookory_access.log;
    error_log /var/log/nginx/bookory_error.log;

    # Security Headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Deny access to sensitive files
    location ~ /\.env {
        deny all;
    }

    location ~ /\.git {
        deny all;
    }

    # PHP Handler
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable site:
```bash
ln -s /etc/nginx/sites-available/bookory /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

### Step 6: Configure Email

```bash
# Test email configuration
php -r "
require_once '/home/user/Books-ecom/includes/EmailReportGenerator.php';
require_once '/home/user/Books-ecom/includes/db_connect.php';
\$gen = new EmailReportGenerator(\$conn);
\$result = \$gen->generateDailyReport('admin@yourdomain.com');
echo \$result ? '✓ Email OK' : '✗ Email failed';
"
```

### Step 7: Configure Cron Jobs

```bash
# Edit crontab
sudo -u www-data crontab -e

# Add these lines:
0 6 * * * /usr/bin/php /home/user/Books-ecom/cron-reports.php >> /var/log/bookory/reports.log 2>&1
0 6 * * 1 /usr/bin/php /home/user/Books-ecom/cron-reports.php weekly >> /var/log/bookory/reports.log 2>&1
0 2 * * * /usr/bin/php /home/user/Books-ecom/cron-sync-payments.php >> /var/log/bookory/payments.log 2>&1

# Verify
sudo -u www-data crontab -l
```

### Step 8: Configure HTTPS/SSL

```bash
# Using Let's Encrypt (free)
sudo apt-get install certbot python3-certbot-apache

# Generate certificate
sudo certbot certonly --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renew
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer

# Verify SSL
curl -I https://yourdomain.com
```

### Step 9: Configure Firewall

```bash
# UFW (Ubuntu)
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp      # SSH
sudo ufw allow 80/tcp      # HTTP
sudo ufw allow 443/tcp     # HTTPS
sudo ufw enable
sudo ufw status

# Or iptables
iptables -A INPUT -p tcp --dport 22 -j ACCEPT
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT
iptables -A INPUT -j DROP
```

### Step 10: Test Deployment

```bash
# Test connectivity
curl -I https://yourdomain.com/admin/

# Check HTTP status codes
# Should be 200 OK

# Test login
# Open browser and login with admin credentials

# Test features
# - View dashboard
# - Check sales metrics
# - Run bulk upload
# - Check email (should receive report at 6 AM)
```

### Step 11: Monitor & Verify

```bash
# Check logs
tail -f /var/log/apache2/bookory_error.log
tail -f /var/log/apache2/bookory_access.log
tail -f /var/log/bookory/reports.log

# Check database
mysql -u root -p -e "USE u618910819_bookshelf_db; SHOW TABLES;"

# Check disk space
df -h

# Check memory
free -h

# Monitor uptime
uptime
```

---

## 🔒 Security Hardening

### Disable Directory Listing

Add to .htaccess:
```apache
Options -Indexes
```

Or nginx:
```nginx
autoindex off;
```

### Restrict Admin Access

Add to .htaccess:
```apache
<Directory /home/user/Books-ecom/admin>
    Allow from 192.168.1.0/24
    Deny from all
</Directory>
```

### Hide PHP Version

```php
// In php.ini
expose_php = Off

# Or in Apache
Header always unset X-Powered-By
```

### Set Security Headers

```php
// In index.php or config
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer-when-downgrade');
header('Strict-Transport-Security: max-age=31536000');
```

---

## 📊 Post-Deployment Verification

### Checklist

- [ ] Website loads (HTTP redirects to HTTPS)
- [ ] Admin login works
- [ ] All dashboards display correctly
- [ ] Database connection working
- [ ] Email reports working (test first report)
- [ ] Cron jobs running (check logs)
- [ ] Payment gateways connected
- [ ] Mobile PWA installs
- [ ] Offline mode works
- [ ] Search functions work
- [ ] Bulk upload works
- [ ] All pages responsive
- [ ] No console errors
- [ ] SSL certificate valid
- [ ] Backups automated

### Monitoring Setup

1. **Set up monitoring (e.g., Uptime Robot)**
   - Monitor: https://yourdomain.com/admin/
   - Alert email: admin@yourdomain.com
   - Check every 5 minutes

2. **Set up error tracking (e.g., Sentry)**
   ```php
   require_once 'vendor/autoload.php';
   Sentry\init(['dsn' => 'YOUR_SENTRY_DSN']);
   ```

3. **Monitor logs**
   ```bash
   # Set up log rotation
   sudo nano /etc/logrotate.d/bookory
   ```

---

## 🆘 Rollback Plan

If deployment fails:

```bash
# Stop web server
sudo systemctl stop apache2

# Restore backup
tar -xzf bookory_backup_20241109_143000.tar.gz -C /

# Restore database
mysql -u root -p u618910819_bookshelf_db < backup_20241109_143000.sql

# Start web server
sudo systemctl start apache2

# Verify restoration
curl -I https://yourdomain.com/admin/
```

---

## 📈 Scaling Considerations

### When to Scale

- Traffic exceeds 1000 concurrent users
- Database queries slow down
- CPU usage consistently > 80%
- Memory usage consistently > 80%
- Response time > 2 seconds

### Scaling Options

1. **Vertical Scaling**
   - Increase server resources
   - More CPU cores
   - More RAM

2. **Horizontal Scaling**
   - Load balancer (Nginx, HAProxy)
   - Multiple app servers
   - Separate database server
   - CDN for static content

3. **Database Optimization**
   - Read replicas
   - Caching layer (Redis)
   - Database sharding

---

## 📝 Deployment Checklist Summary

```markdown
## Bookory Admin Dashboard - Production Deployment

Date: YYYY-MM-DD
Server: yourdomain.com

### Pre-Deployment
- [x] Backups created
- [x] Code committed
- [x] Tests passed
- [x] Security audit done

### Deployment
- [x] Files uploaded
- [x] .env configured
- [x] Permissions set
- [x] Migrations run
- [x] Web server configured
- [x] SSL installed
- [x] Email tested
- [x] Cron jobs added
- [x] Firewall configured

### Post-Deployment
- [x] All systems online
- [x] Features tested
- [x] Monitoring enabled
- [x] Backups verified
- [x] Documentation updated

### Status: ✓ DEPLOYED TO PRODUCTION
```

---

**Last Updated:** November 2024
**Status:** Production Ready
