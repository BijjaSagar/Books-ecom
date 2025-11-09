# Email & Cron Job Setup Guide

## 📧 Email Configuration

### 1. Set Up Email Provider

Choose one of the following options:

#### **Option A: Using Mailtrap (Development/Testing)**
1. Go to https://mailtrap.io
2. Sign up for a free account
3. Create a new Inbox
4. Get SMTP credentials from Demo
5. Update `.env`:
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=reports@bookory.local
```

#### **Option B: Using Gmail (Production)**
1. Enable 2-Factor Authentication on your Gmail account
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Update `.env`:
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
```

#### **Option C: Using SendGrid**
1. Sign up at https://sendgrid.com
2. Create API key
3. Update `.env`:
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

### 2. Configure in Admin Settings

1. Log into admin panel
2. Go to **Settings**
3. Set:
   - **Admin Email:** admin@bookory.local
   - **Report Frequency:** daily or weekly
   - **Enable Email Reports:** Yes

### 3. Test Email Configuration

Create a test file: `/test-email.php`
```php
<?php
require_once 'includes/db_connect.php';
require_once 'includes/EmailReportGenerator.php';

$generator = new EmailReportGenerator($conn);
$result = $generator->generateDailyReport('admin@bookory.local');

if ($result) {
    echo "✓ Email sent successfully!";
} else {
    echo "✗ Failed to send email. Check logs.";
}
?>
```

Run: `php test-email.php`

---

## ⏰ Cron Job Setup

### 1. Check if Cron is Available

```bash
# Check cron status
crontab -l

# If no crontab exists, create one
crontab -e
```

### 2. Add Cron Jobs for Daily Reports

Open crontab editor:
```bash
crontab -e
```

Add these lines:

```cron
# Daily report at 6 AM
0 6 * * * /usr/bin/php /home/user/Books-ecom/cron-reports.php >> /var/log/bookory-reports.log 2>&1

# Weekly report on Monday at 6 AM
0 6 * * 1 /usr/bin/php /home/user/Books-ecom/cron-reports.php weekly >> /var/log/bookory-reports.log 2>&1

# Sync payment data daily at 2 AM
0 2 * * * /usr/bin/php /home/user/Books-ecom/cron-sync-payments.php >> /var/log/bookory-payments.log 2>&1
```

### 3. Create Cron Log Files

```bash
# Create log directory
mkdir -p /var/log/bookory
chmod 755 /var/log/bookory

# Create log files
touch /var/log/bookory-reports.log
touch /var/log/bookory-payments.log
chmod 666 /var/log/bookory-*.log
```

### 4. Verify Cron Installation

Check crontab:
```bash
crontab -l
```

Expected output:
```
0 6 * * * /usr/bin/php /home/user/Books-ecom/cron-reports.php >> /var/log/bookory-reports.log 2>&1
0 6 * * 1 /usr/bin/php /home/user/Books-ecom/cron-reports.php weekly >> /var/log/bookory-reports.log 2>&1
0 2 * * * /usr/bin/php /home/user/Books-ecom/cron-sync-payments.php >> /var/log/bookory-payments.log 2>&1
```

### 5. Test Cron Jobs Manually

```bash
# Test daily report
php /home/user/Books-ecom/cron-reports.php

# Test weekly report
php /home/user/Books-ecom/cron-reports.php weekly

# Check logs
tail -f /var/log/bookory-reports.log
```

---

## 🔍 Monitoring Cron Jobs

### View Cron Logs

```bash
# View all cron activity
grep CRON /var/log/syslog | tail -20

# View bookory-specific logs
tail -f /var/log/bookory-reports.log
tail -f /var/log/bookory-payments.log
```

### Check Last Execution

```bash
# Check if cron ran recently
stat /var/log/bookory-reports.log | grep Modify

# Or check syslog
grep "cron-reports.php" /var/log/syslog | tail -5
```

### Common Issues

**Issue:** Cron not running
```bash
# Make sure cron service is running
sudo systemctl status cron

# Or
sudo service cron status

# Restart cron if needed
sudo systemctl restart cron
```

**Issue:** PHP path not found
```bash
# Find correct PHP path
which php
# Output: /usr/bin/php

# Use the full path in crontab
```

**Issue:** Database connection failing
```bash
# Make sure database credentials are in .env
# Check logs for connection errors
tail /var/log/bookory-reports.log
```

---

## 📱 Testing Email Delivery

### 1. Check Email Settings

```bash
# Test SMTP connection
telnet smtp.gmail.com 587

# Press Ctrl+] then type 'quit' to exit
```

### 2. Verify Email Recipients

Update `.env`:
```env
ADMIN_EMAIL=your-email@example.com
ENABLE_EMAIL_REPORTS=true
REPORT_FREQUENCY=daily
```

### 3. Manual Email Test

Create test script:
```php
<?php
$to = 'your-email@example.com';
$subject = 'Test Email from Bookory';
$message = '<h1>Test Email</h1><p>This is a test email.</p>';

$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: noreply@bookory.local\r\n";

if (mail($to, $subject, $message, $headers)) {
    echo "✓ Email sent successfully";
} else {
    echo "✗ Failed to send email";
}
?>
```

---

## 📊 Email Report Schedule

### Default Schedule

| Frequency | Time | Day | Details |
|-----------|------|-----|---------|
| Daily | 6:00 AM | Every day | Sales summary + alerts |
| Weekly | 6:00 AM | Monday | Full week analytics |

### Customize Schedule

Edit crontab:
```bash
crontab -e
```

Change the time and frequency:

**Every 2 hours:**
```cron
0 */2 * * * /usr/bin/php /home/user/Books-ecom/cron-reports.php
```

**Every day at 8 PM:**
```cron
0 20 * * * /usr/bin/php /home/user/Books-ecom/cron-reports.php
```

**Every Friday at 5 PM:**
```cron
0 17 * * 5 /usr/bin/php /home/user/Books-ecom/cron-reports.php weekly
```

---

## 🔐 Security Considerations

### 1. Protect Log Files

```bash
# Restrict permissions
chmod 640 /var/log/bookory-*.log
chown nobody:root /var/log/bookory-*.log
```

### 2. Secure Cron Script

```bash
# Make script executable but not readable
chmod 500 /home/user/Books-ecom/cron-reports.php
chown nobody /home/user/Books-ecom/cron-reports.php
```

### 3. Use .env for Credentials

Never hardcode passwords. Always use:
```env
PAYPAL_SECRET=xxxxx
RAZORPAY_KEY_SECRET=xxxxx
```

Load with:
```php
$secret = getenv('PAYPAL_SECRET');
```

---

## 📈 Email Report Contents

### Daily Report Includes:
- Total Revenue
- Orders Count
- Unique Customers
- Top 5 Products
- Active Alerts

### Weekly Report Includes:
- Weekly Revenue Total
- Daily Breakdown
- Top 10 Products
- Weekly Trends
- Performance Summary

---

## Troubleshooting

### Email not sending?

1. Check SMTP credentials
   ```bash
   php -r "echo mail('test@example.com', 'Test', 'Test message');"
   ```

2. Check PHP mail configuration
   ```bash
   php -i | grep -A 10 "mail"
   ```

3. Enable debug mode in cron script
   ```php
   error_log("Email attempt: " . date('Y-m-d H:i:s'));
   ```

### Cron not running?

1. Verify cron service
   ```bash
   sudo systemctl status cron
   ```

2. Check permissions
   ```bash
   ls -l /var/spool/cron/crontabs/
   ```

3. Check logs
   ```bash
   tail /var/log/syslog | grep CRON
   ```

### Database connection failing?

1. Test connection directly
   ```php
   php -r "mysqli_connect('localhost', 'user', 'pass', 'db');"
   ```

2. Check credentials in .env
3. Verify user has proper permissions

---

## Best Practices

1. ✅ Always use full paths in cron jobs
2. ✅ Redirect output to log files
3. ✅ Set proper file permissions
4. ✅ Monitor logs regularly
5. ✅ Test emails before deployment
6. ✅ Use environment variables for secrets
7. ✅ Set up email alerts for cron failures
8. ✅ Document any custom schedules

---

**Last Updated:** November 2024
**Status:** Production Ready
