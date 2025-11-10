-- Phase 1, Task 5: User Dashboard System
-- Created: November 10, 2025
-- Purpose: Complete customer dashboard with orders, wishlist, addresses, downloads, support tickets

-- ============================================
-- TABLE: wishlist
-- Purpose: Track customer wishlists and saved items
-- ============================================
CREATE TABLE IF NOT EXISTS wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    UNIQUE KEY unique_wishlist (customer_id, product_id),
    INDEX idx_customer_wishlist (customer_id),
    INDEX idx_product_wishlist (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: support_tickets
-- Purpose: Customer support ticket system
-- ============================================
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    order_id INT,
    ticket_number VARCHAR(20) UNIQUE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    category VARCHAR(100),
    status ENUM('open', 'in_progress', 'waiting_customer', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,

    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_customer_tickets (customer_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_ticket_number (ticket_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: ticket_replies
-- Purpose: Store replies to support tickets
-- ============================================
CREATE TABLE IF NOT EXISTS ticket_replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    reply_text LONGTEXT NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    attachment_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_ticket_replies (ticket_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: notification_preferences
-- Purpose: Customer notification settings
-- ============================================
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL UNIQUE,
    email_order_confirmation BOOLEAN DEFAULT TRUE,
    email_shipment_notification BOOLEAN DEFAULT TRUE,
    email_delivery_notification BOOLEAN DEFAULT TRUE,
    email_refund_notification BOOLEAN DEFAULT TRUE,
    email_promotion BOOLEAN DEFAULT TRUE,
    email_newsletter BOOLEAN DEFAULT TRUE,
    email_reviews BOOLEAN DEFAULT TRUE,
    sms_orders BOOLEAN DEFAULT FALSE,
    sms_shipment BOOLEAN DEFAULT FALSE,
    push_notifications BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_customer_prefs (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: customer_activity_log
-- Purpose: Track customer account activities
-- ============================================
CREATE TABLE IF NOT EXISTS customer_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    activity_type VARCHAR(100),
    description VARCHAR(500),
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_customer_activity (customer_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: customer_reviews
-- Purpose: Product reviews and ratings from customers
-- ============================================
CREATE TABLE IF NOT EXISTS customer_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    order_id INT,
    product_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    review_text LONGTEXT,
    helpful_count INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    INDEX idx_customer_reviews (customer_id),
    INDEX idx_product_reviews (product_id),
    INDEX idx_rating (rating),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: saved_payment_methods
-- Purpose: Store customer's saved payment methods for quick checkout
-- ============================================
CREATE TABLE IF NOT EXISTS saved_payment_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    payment_method_type ENUM('credit_card', 'paypal', 'bank_transfer') NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,

    -- Credit card fields (encrypted)
    card_last_four VARCHAR(4),
    card_brand VARCHAR(50),
    card_expiry_month INT,
    card_expiry_year INT,

    -- PayPal fields (encrypted)
    paypal_email VARCHAR(255),

    -- Bank transfer fields
    bank_account_last_four VARCHAR(4),
    bank_name VARCHAR(255),

    -- Gateway references
    stripe_customer_id VARCHAR(255),
    stripe_payment_method_id VARCHAR(255),

    status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_customer_payment_methods (customer_id),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: customer_account_settings
-- Purpose: Store customer account preferences and settings
-- ============================================
CREATE TABLE IF NOT EXISTS customer_account_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL UNIQUE,
    phone_number VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other', 'prefer_not'),
    company_name VARCHAR(255),
    tax_id VARCHAR(50),
    preferred_language VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(50),
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_method ENUM('email', 'sms', 'authenticator'),
    newsletter_signup BOOLEAN DEFAULT FALSE,
    marketing_consent BOOLEAN DEFAULT FALSE,
    account_verified BOOLEAN DEFAULT FALSE,
    account_verified_at TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    last_password_change TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_customer_settings (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA: Notification Preferences
-- ============================================
-- Note: Add default preferences for existing customers in application code

-- ============================================
-- ALTER: Add preferences to users table
-- ============================================
ALTER TABLE users ADD COLUMN IF NOT EXISTS preferred_currency VARCHAR(3) DEFAULT 'USD' AFTER email;
ALTER TABLE users ADD COLUMN IF NOT EXISTS customer_status ENUM('active', 'inactive', 'suspended', 'deleted') DEFAULT 'active' AFTER preferred_currency;

-- ============================================
-- CREATE INDEXES for Performance
-- ============================================
CREATE INDEX IF NOT EXISTS idx_orders_customer_created ON orders(customer_id, created_at);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items(order_id);
CREATE INDEX IF NOT EXISTS idx_digital_downloads_customer ON digital_downloads(customer_id) GENERATED ALWAYS AS (
    SELECT customer_id FROM orders WHERE orders.id = digital_downloads.order_id
) VIRTUAL;

-- ============================================
-- VIEWS for Dashboard Queries
-- ============================================

-- Customer Order Summary View
CREATE OR REPLACE VIEW vw_customer_order_summary AS
SELECT
    o.id,
    o.customer_id,
    o.order_number,
    o.total,
    o.currency,
    o.status,
    o.payment_status,
    o.created_at,
    o.updated_at,
    COUNT(oi.id) as item_count,
    SUM(oi.quantity) as total_quantity
FROM orders o
LEFT JOIN order_items oi ON o.id = oi.order_id
GROUP BY o.id;

-- Customer Download Summary View
CREATE OR REPLACE VIEW vw_customer_downloads AS
SELECT
    dd.id,
    dd.customer_id,
    dd.order_id,
    p.id as product_id,
    p.title as product_title,
    dd.download_count,
    dd.created_at,
    dd.expires_at,
    CASE WHEN dd.expires_at IS NULL OR dd.expires_at > NOW() THEN 'active' ELSE 'expired' END as status
FROM digital_downloads dd
JOIN products p ON dd.product_id = p.id
LEFT JOIN orders o ON dd.order_id = o.id;

-- Customer Activity Summary View
CREATE OR REPLACE VIEW vw_customer_summary AS
SELECT
    u.id as customer_id,
    u.name as customer_name,
    u.email,
    COUNT(DISTINCT o.id) as total_orders,
    COUNT(DISTINCT w.id) as wishlist_items,
    COUNT(DISTINCT st.id) as open_tickets,
    SUM(CASE WHEN o.status = 'completed' THEN o.total ELSE 0 END) as lifetime_value,
    MAX(o.created_at) as last_order_date,
    csl.last_login,
    u.created_at as member_since
FROM users u
LEFT JOIN orders o ON u.id = o.customer_id
LEFT JOIN wishlist w ON u.id = w.customer_id
LEFT JOIN support_tickets st ON u.id = st.customer_id AND st.status IN ('open', 'in_progress')
LEFT JOIN customer_account_settings csl ON u.id = csl.customer_id
WHERE u.role = 'customer'
GROUP BY u.id;

-- Support Ticket Summary View
CREATE OR REPLACE VIEW vw_customer_tickets AS
SELECT
    st.id,
    st.customer_id,
    st.ticket_number,
    st.subject,
    st.priority,
    st.status,
    st.category,
    COUNT(DISTINCT tr.id) as reply_count,
    st.created_at,
    st.updated_at,
    DATEDIFF(NOW(), st.created_at) as days_open
FROM support_tickets st
LEFT JOIN ticket_replies tr ON st.id = tr.ticket_id
GROUP BY st.id;
