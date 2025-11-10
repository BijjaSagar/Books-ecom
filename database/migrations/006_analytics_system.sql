-- Phase 1, Task 9: Analytics System
-- Created: November 10, 2025
-- Purpose: Complete analytics and reporting for admin dashboard

-- ============================================
-- TABLE: page_views
-- Purpose: Track all page views and traffic
-- ============================================
CREATE TABLE IF NOT EXISTS page_views (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    page_url VARCHAR(500) NOT NULL,
    page_title VARCHAR(255),
    referrer VARCHAR(500),
    user_agent VARCHAR(500),
    ip_address VARCHAR(45),
    device_type ENUM('mobile', 'tablet', 'desktop', 'unknown') DEFAULT 'unknown',
    session_id VARCHAR(255),
    time_on_page INT DEFAULT 0, -- seconds
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_created_at (created_at),
    INDEX idx_customer_id (customer_id),
    INDEX idx_page_url (page_url),
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: product_views
-- Purpose: Track product page views and interest
-- ============================================
CREATE TABLE IF NOT EXISTS product_views (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    customer_id INT,
    session_id VARCHAR(255),
    referrer VARCHAR(500),
    time_on_page INT DEFAULT 0, -- seconds
    clicked_cta BOOLEAN DEFAULT FALSE, -- Did customer click add to cart?
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_created_at (created_at),
    INDEX idx_product_id (product_id),
    INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: search_queries
-- Purpose: Track customer search behavior
-- ============================================
CREATE TABLE IF NOT EXISTS search_queries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    search_term VARCHAR(255) NOT NULL,
    results_count INT DEFAULT 0,
    clicked_result BOOLEAN DEFAULT FALSE,
    session_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_search_term (search_term),
    INDEX idx_created_at (created_at),
    INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: conversion_funnels
-- Purpose: Track conversion funnel steps (browse -> cart -> checkout -> order)
-- ============================================
CREATE TABLE IF NOT EXISTS conversion_funnels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    session_id VARCHAR(255),
    step VARCHAR(50) NOT NULL, -- browse, cart_add, checkout, payment, complete
    step_data LONGTEXT, -- JSON with step-specific data
    completed BOOLEAN DEFAULT FALSE,
    dropped_at TIMESTAMP NULL, -- When customer left the funnel
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_customer_id (customer_id),
    INDEX idx_session_id (session_id),
    INDEX idx_step (step),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: cart_abandonment
-- Purpose: Track abandoned carts and recovery attempts
-- ============================================
CREATE TABLE IF NOT EXISTS cart_abandonment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    cart_id INT,
    items_count INT,
    cart_value DECIMAL(10, 2),
    abandoned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recovery_email_sent BOOLEAN DEFAULT FALSE,
    recovery_email_sent_at TIMESTAMP NULL,
    email_opened BOOLEAN DEFAULT FALSE,
    recovered BOOLEAN DEFAULT FALSE, -- Did customer come back and purchase?
    recovered_at TIMESTAMP NULL,
    order_id INT,

    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,

    INDEX idx_abandoned_at (abandoned_at),
    INDEX idx_customer_id (customer_id),
    INDEX idx_recovered (recovered)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: daily_metrics
-- Purpose: Aggregated daily metrics for quick reporting
-- ============================================
CREATE TABLE IF NOT EXISTS daily_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    metric_date DATE NOT NULL UNIQUE,
    total_visitors INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    total_page_views INT DEFAULT 0,
    total_orders INT DEFAULT 0,
    total_revenue DECIMAL(12, 2) DEFAULT 0.00,
    average_order_value DECIMAL(10, 2) DEFAULT 0.00,
    conversion_rate DECIMAL(5, 2) DEFAULT 0.00, -- percentage
    cart_abandonment_rate DECIMAL(5, 2) DEFAULT 0.00, -- percentage
    top_product_id INT,
    top_product_count INT DEFAULT 0,
    bounce_rate DECIMAL(5, 2) DEFAULT 0.00, -- percentage
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (top_product_id) REFERENCES products(id) ON DELETE SET NULL,

    INDEX idx_metric_date (metric_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: revenue_metrics
-- Purpose: Track revenue by various dimensions
-- ============================================
CREATE TABLE IF NOT EXISTS revenue_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    metric_date DATE,
    metric_type ENUM('hourly', 'daily', 'weekly', 'monthly') DEFAULT 'daily',
    total_revenue DECIMAL(12, 2),
    orders_count INT,
    average_order_value DECIMAL(10, 2),
    refund_amount DECIMAL(12, 2) DEFAULT 0.00,
    currency VARCHAR(3),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_metric_date (metric_date),
    INDEX idx_metric_type (metric_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: customer_metrics
-- Purpose: Track customer acquisition and retention
-- ============================================
CREATE TABLE IF NOT EXISTS customer_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    metric_date DATE,
    new_customers INT DEFAULT 0,
    returning_customers INT DEFAULT 0,
    customer_lifetime_value DECIMAL(12, 2) DEFAULT 0.00, -- average
    repeat_purchase_rate DECIMAL(5, 2) DEFAULT 0.00, -- percentage
    churn_rate DECIMAL(5, 2) DEFAULT 0.00, -- percentage
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_metric_date (metric_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: product_analytics
-- Purpose: Track performance of each product
-- ============================================
CREATE TABLE IF NOT EXISTS product_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    metric_date DATE,
    views_count INT DEFAULT 0,
    click_through_rate DECIMAL(5, 2) DEFAULT 0.00, -- percentage
    orders_count INT DEFAULT 0,
    total_quantity_sold INT DEFAULT 0,
    total_revenue DECIMAL(12, 2) DEFAULT 0.00,
    returns_count INT DEFAULT 0,
    average_rating DECIMAL(3, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    INDEX idx_product_id (product_id),
    INDEX idx_metric_date (metric_date),
    INDEX idx_total_revenue (total_revenue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: traffic_sources
-- Purpose: Track where traffic comes from (organic, direct, referral, etc)
-- ============================================
CREATE TABLE IF NOT EXISTS traffic_sources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    metric_date DATE,
    source_type ENUM('direct', 'organic', 'referral', 'social', 'paid', 'email', 'other') DEFAULT 'other',
    source_name VARCHAR(255), -- e.g., "google.com", "facebook.com", etc
    sessions_count INT DEFAULT 0,
    users_count INT DEFAULT 0,
    bounce_rate DECIMAL(5, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_metric_date (metric_date),
    INDEX idx_source_type (source_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VIEWS for Analytics Dashboard
-- ============================================

-- Top performing products
CREATE OR REPLACE VIEW vw_top_products AS
SELECT
    p.id,
    p.title,
    pa.metric_date,
    pa.views_count,
    pa.orders_count,
    pa.total_revenue,
    pa.total_quantity_sold,
    (pa.orders_count / NULLIF(pa.views_count, 0) * 100) as conversion_rate
FROM products p
LEFT JOIN product_analytics pa ON p.id = pa.product_id
WHERE pa.metric_date = CURDATE()
ORDER BY pa.total_revenue DESC;

-- Revenue summary
CREATE OR REPLACE VIEW vw_revenue_summary AS
SELECT
    rm.metric_date,
    rm.total_revenue,
    rm.orders_count,
    rm.average_order_value,
    COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN o.id END) as completed_orders,
    COUNT(DISTINCT CASE WHEN o.status = 'pending' THEN o.id END) as pending_orders,
    COUNT(DISTINCT CASE WHEN o.status = 'cancelled' THEN o.id END) as cancelled_orders
FROM revenue_metrics rm
LEFT JOIN orders o ON DATE(o.created_at) = rm.metric_date
GROUP BY rm.metric_date;

-- Customer acquisition
CREATE OR REPLACE VIEW vw_customer_acquisition AS
SELECT
    cm.metric_date,
    cm.new_customers,
    cm.returning_customers,
    (cm.new_customers + cm.returning_customers) as total_customers,
    (cm.returning_customers / NULLIF(cm.new_customers + cm.returning_customers, 0) * 100) as return_rate,
    cm.customer_lifetime_value
FROM customer_metrics cm
ORDER BY cm.metric_date DESC;

-- Daily metrics summary
CREATE OR REPLACE VIEW vw_daily_summary AS
SELECT
    dm.metric_date,
    dm.total_visitors,
    dm.unique_visitors,
    dm.total_page_views,
    dm.total_orders,
    dm.total_revenue,
    dm.average_order_value,
    dm.conversion_rate,
    dm.cart_abandonment_rate,
    dm.bounce_rate,
    (dm.total_visitors / NULLIF(dm.total_page_views, 0) * 100) as pages_per_visitor
FROM daily_metrics dm
ORDER BY dm.metric_date DESC;
