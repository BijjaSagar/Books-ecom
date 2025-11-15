-- Phase 2, Task 3: Enhanced Analytics System
-- Created: November 10, 2025
-- Purpose: Advanced analytics, cohort analysis, predictions, and custom reporting

-- ============================================
-- TABLE: customer_cohorts
-- Purpose: Track customer cohorts by acquisition date and behavior
-- ============================================
CREATE TABLE IF NOT EXISTS customer_cohorts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cohort_name VARCHAR(255) NOT NULL,
    cohort_month DATE NOT NULL, -- First day of month when cohort was acquired
    customer_count INT DEFAULT 0,
    total_revenue DECIMAL(12, 2) DEFAULT 0,
    avg_lifetime_value DECIMAL(10, 2) DEFAULT 0,
    retention_rate DECIMAL(5, 2) DEFAULT 0, -- Percentage
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_cohort (cohort_month),
    INDEX idx_cohort_month (cohort_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: cohort_analysis
-- Purpose: Detailed cohort analysis by month
-- ============================================
CREATE TABLE IF NOT EXISTS cohort_analysis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cohort_id INT NOT NULL,
    analysis_month DATE,
    month_number INT, -- 0=cohort month, 1=next month, etc
    users_count INT DEFAULT 0,
    revenue DECIMAL(12, 2) DEFAULT 0,
    orders DECIMAL(10, 2) DEFAULT 0,
    repeat_purchase_rate DECIMAL(5, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (cohort_id) REFERENCES customer_cohorts(id) ON DELETE CASCADE,

    UNIQUE KEY unique_cohort_analysis (cohort_id, month_number),
    INDEX idx_cohort_id (cohort_id),
    INDEX idx_analysis_month (analysis_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: customer_segments
-- Purpose: Segment customers by behavior and value
-- ============================================
CREATE TABLE IF NOT EXISTS customer_segments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    segment_type VARCHAR(100), -- 'high_value', 'at_risk', 'dormant', 'new', 'loyal', etc
    segment_score DECIMAL(5, 2), -- Score for ranking within segment
    total_orders INT DEFAULT 0,
    total_spent DECIMAL(12, 2) DEFAULT 0,
    days_since_purchase INT, -- Days since last purchase
    purchase_frequency INT DEFAULT 0, -- Orders per month
    avg_order_value DECIMAL(10, 2) DEFAULT 0,
    churn_risk DECIMAL(5, 2) DEFAULT 0, -- Percentage risk
    engagement_score DECIMAL(5, 2) DEFAULT 0, -- 0-100
    segmented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    UNIQUE KEY unique_user_segment (user_id),
    INDEX idx_segment_type (segment_type),
    INDEX idx_total_spent (total_spent),
    INDEX idx_days_since_purchase (days_since_purchase),
    INDEX idx_churn_risk (churn_risk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: predictive_metrics
-- Purpose: Store predictions for customer behavior
-- ============================================
CREATE TABLE IF NOT EXISTS predictive_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    metric_type VARCHAR(100), -- 'churn_probability', 'ltv_prediction', 'purchase_likelihood'
    metric_value DECIMAL(10, 4),
    confidence DECIMAL(5, 2), -- Percentage confidence
    prediction_date DATE,
    actual_result VARCHAR(255), -- What actually happened
    accuracy_score DECIMAL(5, 2), -- 0-100
    analysis_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_user_id (user_id),
    INDEX idx_metric_type (metric_type),
    INDEX idx_prediction_date (prediction_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: funnel_analysis
-- Purpose: Track multi-step funnels
-- ============================================
CREATE TABLE IF NOT EXISTS funnel_analysis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    funnel_name VARCHAR(255) NOT NULL UNIQUE,
    description LONGTEXT,
    step_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_funnel_name (funnel_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: funnel_steps
-- Purpose: Define steps in a funnel
-- ============================================
CREATE TABLE IF NOT EXISTS funnel_steps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    funnel_id INT NOT NULL,
    step_number INT,
    step_name VARCHAR(255),
    event_type VARCHAR(100), -- 'page_view', 'click', 'purchase', etc
    event_filter VARCHAR(500), -- JSON criteria
    conversion_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (funnel_id) REFERENCES funnel_analysis(id) ON DELETE CASCADE,

    UNIQUE KEY unique_funnel_step (funnel_id, step_number),
    INDEX idx_funnel_id (funnel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: custom_reports
-- Purpose: Store custom reports created by admins
-- ============================================
CREATE TABLE IF NOT EXISTS custom_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_name VARCHAR(255) NOT NULL,
    description LONGTEXT,
    report_type VARCHAR(100), -- 'sales', 'customer', 'product', 'traffic', 'custom'
    created_by INT,
    report_config LONGTEXT, -- JSON with filters, metrics, dimensions
    schedule_frequency VARCHAR(50), -- 'daily', 'weekly', 'monthly', null = manual
    last_run TIMESTAMP NULL,
    next_run TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_report_type (report_type),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: report_runs
-- Purpose: Store generated report data
-- ============================================
CREATE TABLE IF NOT EXISTS report_runs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    run_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_json LONGTEXT, -- JSON report data
    row_count INT DEFAULT 0,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (report_id) REFERENCES custom_reports(id) ON DELETE CASCADE,

    INDEX idx_report_id (report_id),
    INDEX idx_run_date (run_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: analytics_goals
-- Purpose: Track business goals and KPIs
-- ============================================
CREATE TABLE IF NOT EXISTS analytics_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    goal_name VARCHAR(255) NOT NULL,
    goal_type VARCHAR(100), -- 'revenue', 'orders', 'customers', 'conversion_rate', etc
    target_value DECIMAL(12, 2),
    current_value DECIMAL(12, 2) DEFAULT 0,
    target_date DATE,
    progress_percentage DECIMAL(5, 2) DEFAULT 0,
    is_achieved BOOLEAN DEFAULT FALSE,
    achieved_date TIMESTAMP NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_goal_type (goal_type),
    INDEX idx_target_date (target_date),
    INDEX idx_is_achieved (is_achieved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: product_analytics
-- Purpose: Detailed product performance metrics
-- ============================================
CREATE TABLE IF NOT EXISTS product_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    analysis_date DATE,
    views INT DEFAULT 0,
    add_to_cart INT DEFAULT 0,
    purchases INT DEFAULT 0,
    revenue DECIMAL(12, 2) DEFAULT 0,
    returns INT DEFAULT 0,
    reviews_count INT DEFAULT 0,
    avg_rating DECIMAL(3, 2) DEFAULT 0,
    conversion_rate DECIMAL(5, 2) DEFAULT 0, -- view to purchase %
    view_to_cart_rate DECIMAL(5, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    UNIQUE KEY unique_product_date (product_id, analysis_date),
    INDEX idx_product_id (product_id),
    INDEX idx_analysis_date (analysis_date),
    INDEX idx_revenue (revenue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: channel_analytics
-- Purpose: Track performance by traffic channel
-- ============================================
CREATE TABLE IF NOT EXISTS channel_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    channel_name VARCHAR(100), -- 'organic', 'paid', 'social', 'email', 'direct', 'referral'
    analysis_date DATE,
    sessions INT DEFAULT 0,
    users INT DEFAULT 0,
    page_views INT DEFAULT 0,
    bounce_rate DECIMAL(5, 2) DEFAULT 0,
    avg_session_duration INT DEFAULT 0, -- seconds
    conversion_rate DECIMAL(5, 2) DEFAULT 0,
    transactions INT DEFAULT 0,
    revenue DECIMAL(12, 2) DEFAULT 0,
    cost DECIMAL(10, 2), -- Ad spend or cost
    roi DECIMAL(7, 2), -- Return on investment
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_channel_date (channel_name, analysis_date),
    INDEX idx_channel_name (channel_name),
    INDEX idx_analysis_date (analysis_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VIEWS for Enhanced Analytics
-- ============================================

-- Cohort retention table
CREATE OR REPLACE VIEW vw_cohort_retention AS
SELECT
    cc.cohort_name,
    cc.cohort_month,
    ca.month_number,
    ca.users_count,
    ca.revenue,
    (ca.users_count / cc.customer_count * 100) as retention_percentage
FROM customer_cohorts cc
LEFT JOIN cohort_analysis ca ON cc.id = ca.cohort_id
ORDER BY cc.cohort_month, ca.month_number;

-- Customer segments summary
CREATE OR REPLACE VIEW vw_customer_segments_summary AS
SELECT
    segment_type,
    COUNT(DISTINCT user_id) as segment_count,
    AVG(total_spent) as avg_spent,
    AVG(total_orders) as avg_orders,
    AVG(engagement_score) as avg_engagement,
    AVG(churn_risk) as avg_churn_risk
FROM customer_segments
WHERE segment_type IS NOT NULL
GROUP BY segment_type;

-- Product performance ranking
CREATE OR REPLACE VIEW vw_product_performance_ranking AS
SELECT
    p.id,
    p.title,
    pa.analysis_date,
    pa.views,
    pa.purchases,
    pa.revenue,
    pa.conversion_rate,
    ROW_NUMBER() OVER (ORDER BY pa.revenue DESC) as revenue_rank,
    ROW_NUMBER() OVER (ORDER BY pa.conversion_rate DESC) as conversion_rank
FROM products p
LEFT JOIN product_analytics pa ON p.id = pa.product_id
WHERE pa.analysis_date = CURDATE()
ORDER BY pa.revenue DESC;

-- Channel performance comparison
CREATE OR REPLACE VIEW vw_channel_performance AS
SELECT
    channel_name,
    SUM(sessions) as total_sessions,
    SUM(users) as total_users,
    SUM(transactions) as total_transactions,
    SUM(revenue) as total_revenue,
    SUM(cost) as total_cost,
    AVG(bounce_rate) as avg_bounce_rate,
    AVG(conversion_rate) as avg_conversion_rate,
    (SUM(revenue) - SUM(cost)) / NULLIF(SUM(cost), 0) as roi
FROM channel_analytics
GROUP BY channel_name
ORDER BY total_revenue DESC;
