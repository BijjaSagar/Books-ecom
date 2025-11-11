# Books Bookstore - Complete E-Commerce Platform

A modern, feature-rich online bookstore built with PHP and MySQL. Comprehensive admin panel, customer-friendly interface, and powerful backend systems.

## 🌟 Features

### Admin Panel
- **Dashboard & Analytics**: Real-time sales metrics, date range filtering, trend analysis
- **Order Management**: Complete order lifecycle, status tracking, timeline history
- **Product Management**: Full CRUD operations, inventory tracking, stock alerts
- **Coupon System**: Create and manage discount codes with advanced rules
- **Review Moderation**: Approve/reject customer reviews, moderation logs
- **Email Notifications**: Queue-based email system with retry logic
- **Data Exports**: Export orders, products, customers to CSV/Excel
- **Comprehensive Reports**: Business analytics with date range filtering

### Customer Features
- **User Dashboard**: Order history, account information, tracking
- **Product Browsing**: Search, filtering, detailed product pages
- **Shopping Cart**: Add/remove items, persistent storage
- **Checkout**: Secure payment processing
- **Order Tracking**: Real-time order status updates
- **Reviews & Ratings**: Write and read product reviews
- **Profile Management**: Edit account info, change password
- **Wishlist**: Save favorite books for later

### Technical Highlights
- **REST API**: Complete API for third-party integration
- **Manager Classes**: Object-oriented architecture for business logic
- **Email Templates**: Professional HTML templates for all notifications
- **Security**: SQL injection prevention, password hashing, session management
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Database Transactions**: Ensures data consistency

---

## 📋 System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache/Nginx with .htaccess support
- **Browser**: Modern browser (Chrome, Firefox, Safari, Edge)

---

## 🚀 Installation

### 1. Clone/Extract Repository
```bash
cd /var/www/html
# Extract repository files here
```

### 2. Create Database
```sql
-- Create database
CREATE DATABASE books_ecom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE books_ecom;

-- Run migration/setup scripts
-- Tables are auto-created by Manager classes on first use
```

### 3. Configure Database Connection
Edit `includes/config.php`:
```php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'password';
$db_name = 'books_ecom';
```

### 4. Set File Permissions
```bash
chmod 755 /var/www/html/books-ecom
chmod 755 /var/www/html/books-ecom/uploads
chmod 755 /var/www/html/books-ecom/admin
```

### 5. Access Application
- **Customer Site**: `http://localhost/Books-ecom/`
- **Admin Panel**: `http://localhost/Books-ecom/admin/`

---

## 📁 Project Structure

```
Books-ecom/
├── admin/                    # Admin panel pages
│   ├── dashboard.php        # Sales analytics & metrics
│   ├── coupons.php          # Coupon management
│   ├── reviews.php          # Review moderation
│   ├── notifications.php    # Email queue management
│   ├── exports.php          # Data export interface
│   ├── reports.php          # Comprehensive reports
│   └── ...
├── customer/                 # Customer pages
│   ├── dashboard.php        # Customer dashboard
│   ├── edit-profile.php     # Profile management
│   └── ...
├── includes/                 # Backend systems
│   ├── config.php           # Database configuration
│   ├── admin_header.php     # Admin layout template
│   ├── OrderManager.php     # Order management class
│   ├── CouponManager.php    # Coupon system class
│   ├── ReviewManager.php    # Review moderation class
│   ├── EmailNotificationManager.php  # Email queue
│   ├── ExportManager.php    # Data export class
│   └── email_templates/     # HTML email templates
├── api/                      # REST API
│   └── index.php            # API router & handlers
├── uploads/                  # User uploads (images, etc)
├── public_html/             # Public assets
│   ├── css/
│   ├── js/
│   └── images/
├── API_DOCUMENTATION.md     # API reference
└── README.md               # This file
```

---

## 🔑 Key Classes

### OrderManager
Manages complete order lifecycle.
```php
require_once '../includes/OrderManager.php';
$order_manager = new OrderManager($conn);

// Update order status with notification
$result = $order_manager->updateOrderStatus(
    $order_id,
    'shipped',
    'Package shipped via FedEx'
);
```

### CouponManager
Handles discount code management.
```php
require_once '../includes/CouponManager.php';
$coupon_manager = new CouponManager($conn);

// Create coupon
$result = $coupon_manager->createCoupon([
    'code' => 'SUMMER20',
    'discount_type' => 'percentage',
    'discount_value' => 20
]);

// Validate coupon
$validation = $coupon_manager->validateCoupon('SUMMER20', 2500);
```

### ReviewManager
Manages product reviews and moderation.
```php
require_once '../includes/ReviewManager.php';
$review_manager = new ReviewManager($conn);

// Submit review
$result = $review_manager->submitReview(
    $customer_id,
    $product_id,
    5,
    'Amazing book!',
    'Highly recommend to everyone'
);
```

### EmailNotificationManager
Queue-based email delivery system.
```php
require_once '../includes/EmailNotificationManager.php';
$email_manager = new EmailNotificationManager($conn);

// Queue email
$email_manager->queueEmail([
    'to' => 'customer@example.com',
    'type' => 'order_confirmation',
    'data' => [
        'customer_name' => 'John Doe',
        'order_number' => 'ORD-001'
    ]
]);

// Process queue
$result = $email_manager->processQueue(10);
```

### ExportManager
Export data in multiple formats.
```php
require_once '../includes/ExportManager.php';
$export_manager = new ExportManager($conn);

// Export orders to CSV
$csv = $export_manager->exportOrdersCSV([
    'status' => 'completed',
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31'
]);
```

---

## 📊 Database Schema

### Main Tables

**users**: Customer and admin accounts
- id, email, password, name, role, phone, address, created_at

**products**: Book catalog
- id, title, author, description, price, stock_quantity, category_id, rating, review_count

**orders**: Customer orders
- id, user_id, total_amount, order_status, payment_method, created_at

**order_items**: Items in each order
- id, order_id, product_id, quantity, price, total

**coupons**: Discount codes
- id, code, discount_type, discount_value, valid_from, valid_until, status

**product_reviews**: Customer reviews
- id, product_id, user_id, rating, title, comment, is_approved, verified_purchase

**email_queue**: Email notifications pending
- id, to_email, type, subject, body, status, retry_count

---

## 🔐 Security Features

- **SQL Injection Prevention**: All queries use prepared statements
- **Password Security**: Bcrypt hashing for passwords
- **Session Management**: Secure session handling with role-based access
- **Input Validation**: Server-side validation on all inputs
- **CSRF Protection**: Token-based CSRF protection on forms
- **Output Encoding**: HTML entity encoding to prevent XSS
- **File Upload Security**: Restricted file types and locations

---

## 📡 REST API Usage

### Get Products
```bash
curl "http://localhost/Books-ecom/api/index.php?resource=products&limit=10"
```

### Validate Coupon
```bash
curl "http://localhost/Books-ecom/api/index.php?resource=coupons&endpoint=validate&code=SAVE20&order_total=2500"
```

### Submit Review
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "rating": 5,
    "title": "Great book!",
    "comment": "Highly recommended"
  }' \
  "http://localhost/Books-ecom/api/index.php?resource=reviews&endpoint=submit"
```

See `API_DOCUMENTATION.md` for complete API reference.

---

## 📧 Email Configuration

The system uses a queue-based email system. Configure SMTP:

1. Update `EmailNotificationManager.php` with your SMTP settings
2. Configure cron job to process email queue:
```bash
*/5 * * * * php /var/www/html/Books-ecom/cron/send-emails.php
```

---

## 🎨 Customization

### Color Scheme
Edit CSS variables in any template:
```css
:root {
    --primary: #1e40af;
    --primary-dark: #1e3a8a;
    --success: #10b981;
    --danger: #ef4444;
}
```

### Email Templates
Modify templates in `includes/email_templates/`:
- `order_confirmation.html`
- `order_status_update.html`
- `welcome_email.html`
- `password_reset.html`
- `low_stock_alert.html`

---

## 🧪 Testing Accounts

### Admin Account
- **Email**: admin@bookstore.com
- **Password**: admin123

### Customer Account
- **Email**: customer@bookstore.com
- **Password**: password123

**⚠️ Change credentials in production!**

---

## 📈 Analytics & Reporting

**Admin Dashboard Features:**
- Real-time sales metrics
- Date range filtering (today, 7/30/90 days, custom)
- Top selling products
- Category performance
- Customer acquisition metrics
- Payment method distribution

**Reports Page Features:**
- Order status distribution
- Top products with ratings
- Category performance
- Daily sales trends
- Customer statistics

---

## 🚨 Troubleshooting

### Database Connection Error
- Check MySQL is running
- Verify credentials in `config.php`
- Ensure database is created

### File Upload Issues
- Check `uploads/` directory permissions (755)
- Verify disk space available
- Check file size limits

### Email Not Sending
- Verify SMTP configuration
- Check email queue table for errors
- Ensure cron job is running

### Session Issues
- Clear browser cookies
- Check PHP session settings
- Verify session storage directory

---

## 🔄 Maintenance

### Regular Tasks
1. **Database Backup**: Weekly MySQL backups recommended
2. **Log Rotation**: Monitor error logs in `logs/`
3. **Security Updates**: Keep PHP and libraries updated
4. **Email Cleanup**: Archive old email logs

### Database Optimization
```sql
-- Optimize tables
OPTIMIZE TABLE users, products, orders, email_queue;

-- Check table integrity
CHECK TABLE users, products, orders;
```

---

## 📞 Support & Contact

- **Email**: support@bookstore.com
- **Documentation**: See `API_DOCUMENTATION.md`
- **Issues**: Report bugs in project tracker

---

## 📜 License

This project is proprietary and confidential.

---

## 👥 Contributing

For contribution guidelines, contact the development team.

---

## 📝 Version History

**Version 1.0** - January 2024
- Complete e-commerce platform
- Admin management system
- REST API
- Email notifications
- Data exports & reports

---

**Last Updated**: January 15, 2024  
**Maintained By**: Development Team
