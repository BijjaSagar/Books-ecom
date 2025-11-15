-- Phase 2, Task 2: Loyalty Program System
-- Created: November 10, 2025
-- Purpose: Customer loyalty, points system, tier management, and referral tracking

-- ============================================
-- TABLE: loyalty_tiers
-- Purpose: Define membership tier levels and benefits
-- ============================================
CREATE TABLE IF NOT EXISTS loyalty_tiers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tier_name VARCHAR(100) NOT NULL UNIQUE,
    tier_level INT NOT NULL UNIQUE,
    description LONGTEXT,
    min_points_required INT DEFAULT 0,
    max_points_required INT DEFAULT 999999999,
    points_multiplier DECIMAL(3, 2) DEFAULT 1.0, -- 1.5x = 50% more points
    discount_percentage DECIMAL(5, 2) DEFAULT 0,
    free_shipping BOOLEAN DEFAULT FALSE,
    exclusive_benefits LONGTEXT, -- JSON or comma-separated
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_tier_level (tier_level),
    INDEX idx_min_points (min_points_required)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loyalty_members
-- Purpose: Track customer loyalty program membership
-- ============================================
CREATE TABLE IF NOT EXISTS loyalty_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    current_tier_id INT DEFAULT 1,
    total_points INT DEFAULT 0,
    available_points INT DEFAULT 0, -- Points not yet redeemed
    lifetime_points INT DEFAULT 0, -- Total points earned (never decreases)
    join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    enrollment_bonus_received BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (current_tier_id) REFERENCES loyalty_tiers(id) ON DELETE SET NULL,

    INDEX idx_user_id (user_id),
    INDEX idx_current_tier (current_tier_id),
    INDEX idx_total_points (total_points),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loyalty_transactions
-- Purpose: Audit trail of points earned and spent
-- ============================================
CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    transaction_type ENUM('purchase', 'redemption', 'bonus', 'adjustment', 'referral', 'birthday', 'expiration') DEFAULT 'adjustment',
    points_change INT,
    reason VARCHAR(255),
    reference_id INT, -- Order ID, Redemption ID, etc
    reference_type VARCHAR(50), -- 'order', 'reward', 'referral', etc
    balance_before INT,
    balance_after INT,
    notes LONGTEXT,
    performed_by INT, -- Admin who made adjustment
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_user_id (user_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at),
    INDEX idx_reference_id (reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loyalty_rewards
-- Purpose: Available rewards for redemption
-- ============================================
CREATE TABLE IF NOT EXISTS loyalty_rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reward_name VARCHAR(255) NOT NULL,
    description LONGTEXT,
    reward_type ENUM('discount', 'free_product', 'free_shipping', 'bonus_points', 'exclusive_access') DEFAULT 'discount',
    points_required INT NOT NULL,
    reward_value DECIMAL(10, 2), -- Dollar amount or percentage
    reward_code VARCHAR(50), -- Discount code
    max_redemptions INT, -- NULL = unlimited
    current_redemptions INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    expiration_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_reward_type (reward_type),
    INDEX idx_points_required (points_required),
    INDEX idx_is_active (is_active),
    INDEX idx_expiration_date (expiration_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loyalty_redemptions
-- Purpose: Track reward redemption history
-- ============================================
CREATE TABLE IF NOT EXISTS loyalty_redemptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    points_redeemed INT,
    reward_received VARCHAR(255), -- Code, description, etc
    order_id INT, -- If applied to order
    is_used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL,
    expiration_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES loyalty_rewards(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,

    INDEX idx_user_id (user_id),
    INDEX idx_reward_id (reward_id),
    INDEX idx_is_used (is_used),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loyalty_referrals
-- Purpose: Track referral program
-- ============================================
CREATE TABLE IF NOT EXISTS loyalty_referrals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referrer_id INT NOT NULL, -- User who referred
    referred_id INT, -- User who was referred (NULL until signup)
    referral_code VARCHAR(50) UNIQUE,
    referral_link VARCHAR(500),
    points_offered INT DEFAULT 100, -- Points for successful referral
    status ENUM('pending', 'activated', 'converted') DEFAULT 'pending',
    referred_email VARCHAR(255), -- Email sent to
    activation_bonus_given BOOLEAN DEFAULT FALSE,
    conversion_bonus_given BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    converted_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,

    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_referrer_id (referrer_id),
    INDEX idx_referred_id (referred_id),
    INDEX idx_referral_code (referral_code),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loyalty_point_rules
-- Purpose: Configure points earning rules
-- ============================================
CREATE TABLE IF NOT EXISTS loyalty_point_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rule_name VARCHAR(255) NOT NULL,
    rule_type ENUM('purchase', 'review', 'signup', 'birthday', 'referral') DEFAULT 'purchase',
    points_per_unit DECIMAL(5, 2), -- Points per dollar spent or per action
    applies_to_tier_id INT, -- NULL = all tiers
    min_order_amount DECIMAL(10, 2), -- Minimum purchase amount
    max_points_per_order INT, -- Cap on points per order
    is_active BOOLEAN DEFAULT TRUE,
    description LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (applies_to_tier_id) REFERENCES loyalty_tiers(id) ON DELETE SET NULL,

    INDEX idx_rule_type (rule_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loyalty_milestones
-- Purpose: Track customer achievement milestones
-- ============================================
CREATE TABLE IF NOT EXISTS loyalty_milestones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    milestone_type VARCHAR(50), -- 'first_purchase', 'vip_status', 'referral_count', etc
    milestone_name VARCHAR(255),
    milestone_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    badge_earned VARCHAR(100), -- Badge/achievement name
    points_awarded INT DEFAULT 0,
    description LONGTEXT,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_user_id (user_id),
    INDEX idx_milestone_type (milestone_type),
    INDEX idx_milestone_date (milestone_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VIEWS for Loyalty Program
-- ============================================

-- Member tier distribution
CREATE OR REPLACE VIEW vw_member_tier_distribution AS
SELECT
    lt.id,
    lt.tier_name,
    COUNT(DISTINCT lm.user_id) as member_count,
    AVG(lm.total_points) as avg_points,
    SUM(lm.total_points) as total_tier_points
FROM loyalty_tiers lt
LEFT JOIN loyalty_members lm ON lt.id = lm.current_tier_id
GROUP BY lt.id
ORDER BY lt.tier_level;

-- Top referrers
CREATE OR REPLACE VIEW vw_top_referrers AS
SELECT
    u.id,
    u.name,
    u.email,
    COUNT(DISTINCT lr.id) as total_referrals,
    SUM(CASE WHEN lr.status = 'converted' THEN 1 ELSE 0 END) as successful_referrals,
    SUM(CASE WHEN lr.activation_bonus_given = TRUE THEN 1 ELSE 0 END) as bonus_received_count
FROM users u
LEFT JOIN loyalty_referrals lr ON u.id = lr.referrer_id
WHERE u.id IN (SELECT DISTINCT referrer_id FROM loyalty_referrals)
GROUP BY u.id
ORDER BY successful_referrals DESC;

-- Top rewards
CREATE OR REPLACE VIEW vw_top_rewards AS
SELECT
    lr.id,
    lr.reward_name,
    lr.reward_type,
    lr.points_required,
    COUNT(DISTINCT lrd.id) as times_redeemed,
    lr.max_redemptions,
    (lr.max_redemptions - lr.current_redemptions) as remaining
FROM loyalty_rewards lr
LEFT JOIN loyalty_redemptions lrd ON lr.id = lrd.reward_id AND lrd.is_used = TRUE
WHERE lr.is_active = TRUE
GROUP BY lr.id
ORDER BY times_redeemed DESC;

-- Member program statistics
CREATE OR REPLACE VIEW vw_loyalty_program_stats AS
SELECT
    COUNT(DISTINCT lm.user_id) as total_members,
    SUM(lm.total_points) as total_points_in_circulation,
    AVG(lm.total_points) as avg_points_per_member,
    COUNT(DISTINCT CASE WHEN lm.last_activity >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN lm.user_id END) as active_members_30d,
    COUNT(DISTINCT lr.referrer_id) as members_with_referrals,
    COUNT(DISTINCT CASE WHEN lr.status = 'converted' THEN lr.referred_id END) as conversions_from_referrals
FROM loyalty_members lm
LEFT JOIN loyalty_referrals lr ON lm.user_id = lr.referrer_id;
