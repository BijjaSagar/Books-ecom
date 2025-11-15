-- ============================================
-- PAYMENT GATEWAY INTEGRATION SCHEMA
-- Phase 1, Task 1: Payment Gateway Implementation
-- Database: u618910819_bookshelf_db
-- ============================================

-- ============================================
-- TABLE 1: PAYMENT METHODS
-- Tracks which payment gateways are enabled
-- ============================================
CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `gateway_name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `gateway_type` ENUM('card', 'wallet', 'bank', 'upi', 'crypto', 'other') NOT NULL,
    `icon_url` VARCHAR(255),
    `position` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `supports_refund` BOOLEAN DEFAULT TRUE,
    `requires_3d_secure` BOOLEAN DEFAULT FALSE,
    `transaction_fee_percent` DECIMAL(5, 2) DEFAULT 2.9,
    `transaction_fee_fixed` DECIMAL(10, 2) DEFAULT 0.30,
    `processing_time_hours` INT DEFAULT 24,
    `countries_supported` JSON,
    `currencies_supported` JSON,
    `test_mode_available` BOOLEAN DEFAULT TRUE,
    `test_keys_configured` BOOLEAN DEFAULT FALSE,
    `live_keys_configured` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT,
    `notes` TEXT,
    INDEX `idx_gateway_name` (`gateway_name`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 2: GATEWAY KEYS (API Keys Management)
-- Securely stores API credentials for each gateway
-- ============================================
CREATE TABLE IF NOT EXISTS `gateway_keys` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `payment_method_id` INT NOT NULL,
    `is_test_mode` BOOLEAN DEFAULT TRUE,
    `api_key` VARCHAR(500) NOT NULL,
    `api_secret` VARCHAR(500) NOT NULL,
    `merchant_id` VARCHAR(255),
    `account_id` VARCHAR(255),
    `additional_config` JSON,
    `webhook_secret` VARCHAR(500),
    `webhook_url` VARCHAR(255),
    `is_active` BOOLEAN DEFAULT TRUE,
    `last_tested_at` TIMESTAMP NULL,
    `test_result` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT,
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_gateway_mode` (`payment_method_id`, `is_test_mode`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 3: TRANSACTIONS (Core Payment Records)
-- Every payment attempt is logged here
-- ============================================
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
    `order_id` INT NOT NULL,
    `customer_id` INT NOT NULL,
    `payment_method_id` INT NOT NULL,
    `gateway_name` VARCHAR(50) NOT NULL,
    `gateway_transaction_id` VARCHAR(255),
    `gateway_reference_id` VARCHAR(255),

    -- Amount Details
    `currency` VARCHAR(3) DEFAULT 'USD',
    `amount` DECIMAL(12, 2) NOT NULL,
    `original_amount` DECIMAL(12, 2),
    `currency_conversion_rate` DECIMAL(10, 6),
    `transaction_fee` DECIMAL(12, 2) DEFAULT 0,
    `final_amount_received` DECIMAL(12, 2),

    -- Status Tracking
    `status` ENUM(
        'initiated',
        'processing',
        'pending_3d',
        'authorized',
        'completed',
        'failed',
        'declined',
        'cancelled',
        'refunded',
        'partial_refund',
        'fraud_suspected',
        'error'
    ) DEFAULT 'initiated',
    `sub_status` VARCHAR(100),

    -- Card/Payment Information (Tokenized)
    `payment_source_token` VARCHAR(500),
    `card_last_four` VARCHAR(4),
    `card_brand` VARCHAR(50),
    `card_expiry_month` VARCHAR(2),
    `card_expiry_year` VARCHAR(4),

    -- 3D Secure
    `three_d_secure_required` BOOLEAN DEFAULT FALSE,
    `three_d_secure_status` ENUM('not_required', 'attempted', 'failed', 'passed') DEFAULT 'not_required',
    `three_d_secure_eci` VARCHAR(10),
    `three_d_secure_cavv` VARCHAR(255),

    -- Risk & Fraud
    `risk_score` DECIMAL(5, 2),
    `risk_level` ENUM('low', 'medium', 'high') DEFAULT 'low',
    `fraud_check_result` VARCHAR(50),
    `avs_result_code` VARCHAR(5),
    `cvv_check_result` VARCHAR(5),

    -- Customer Information
    `customer_email` VARCHAR(255),
    `customer_phone` VARCHAR(20),
    `customer_ip_address` VARCHAR(45),
    `customer_user_agent` TEXT,

    -- Billing Address
    `billing_first_name` VARCHAR(100),
    `billing_last_name` VARCHAR(100),
    `billing_address_line1` VARCHAR(255),
    `billing_address_line2` VARCHAR(255),
    `billing_city` VARCHAR(100),
    `billing_state` VARCHAR(100),
    `billing_postal_code` VARCHAR(20),
    `billing_country` VARCHAR(2),

    -- Response Data
    `gateway_response_code` VARCHAR(20),
    `gateway_response_message` TEXT,
    `gateway_response_json` JSON,

    -- Timestamps
    `initiated_at` TIMESTAMP,
    `processed_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `failed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Notes & Debugging
    `internal_notes` TEXT,
    `payment_error_details` JSON,
    `retry_count` INT DEFAULT 0,
    `retry_reason` VARCHAR(255),

    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`),

    UNIQUE KEY `unique_transaction_id` (`transaction_id`),
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_gateway_name` (`gateway_name`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_gateway_tx_id` (`gateway_transaction_id`),
    INDEX `idx_risk_level` (`risk_level`),
    FULLTEXT INDEX `idx_fulltext_search` (`customer_email`, `customer_phone`, `gateway_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 4: PAYMENT WEBHOOKS
-- Tracks webhook events from payment gateways
-- ============================================
CREATE TABLE IF NOT EXISTS `payment_webhooks` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `transaction_id` INT,
    `gateway_name` VARCHAR(50) NOT NULL,
    `event_type` VARCHAR(100) NOT NULL,
    `webhook_id` VARCHAR(255),
    `raw_payload` JSON,
    `processed` BOOLEAN DEFAULT FALSE,
    `processed_at` TIMESTAMP NULL,
    `processing_error` TEXT,
    `ip_address` VARCHAR(45),
    `signature_verified` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE SET NULL,
    INDEX `idx_gateway_name` (`gateway_name`),
    INDEX `idx_event_type` (`event_type`),
    INDEX `idx_processed` (`processed`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 5: REFUNDS
-- Track all refund operations
-- ============================================
CREATE TABLE IF NOT EXISTS `refunds` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `transaction_id` INT NOT NULL,
    `refund_id` VARCHAR(100) NOT NULL UNIQUE,
    `gateway_refund_id` VARCHAR(255),
    `amount` DECIMAL(12, 2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'USD',
    `reason` ENUM(
        'customer_request',
        'duplicate_charge',
        'fraud',
        'return',
        'product_damaged',
        'service_not_provided',
        'order_cancelled',
        'other'
    ) NOT NULL,
    `reason_description` TEXT,
    `status` ENUM('initiated', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'initiated',
    `gateway_response_code` VARCHAR(20),
    `gateway_response_message` TEXT,
    `refunded_by_user_id` INT,
    `refunded_to_method` ENUM('original_payment', 'account_credit', 'check') DEFAULT 'original_payment',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `processed_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`refunded_by_user_id`) REFERENCES `users`(`id`),
    INDEX `idx_transaction_id` (`transaction_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 6: PAYMENT INTENTS (For ACH, Bank Transfers, etc.)
-- Handles payment methods that require setup/verification
-- ============================================
CREATE TABLE IF NOT EXISTS `payment_intents` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `intent_id` VARCHAR(100) NOT NULL UNIQUE,
    `customer_id` INT NOT NULL,
    `payment_method_type` VARCHAR(50) NOT NULL,
    `gateway_intent_id` VARCHAR(255),
    `status` ENUM('requires_payment_method', 'requires_action', 'processing', 'requires_capture', 'succeeded', 'cancelled') DEFAULT 'requires_payment_method',
    `amount` DECIMAL(12, 2),
    `currency` VARCHAR(3),
    `bank_account_token` VARCHAR(500),
    `bank_account_last_four` VARCHAR(4),
    `bank_routing_number` VARCHAR(20),
    `mandate_id` VARCHAR(255),
    `gateway_response` JSON,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 7: SAVED PAYMENT METHODS
-- Customers can save multiple payment methods
-- ============================================
CREATE TABLE IF NOT EXISTS `saved_payment_methods` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `payment_method_id` INT NOT NULL,
    `gateway_payment_method_token` VARCHAR(500) NOT NULL,
    `nickname` VARCHAR(100),
    `card_brand` VARCHAR(50),
    `card_last_four` VARCHAR(4),
    `card_expiry_month` VARCHAR(2),
    `card_expiry_year` VARCHAR(4),
    `card_fingerprint` VARCHAR(255),
    `bank_account_name` VARCHAR(100),
    `bank_account_last_four` VARCHAR(4),
    `is_default` BOOLEAN DEFAULT FALSE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `billing_address_same_as_shipping` BOOLEAN DEFAULT TRUE,
    `billing_first_name` VARCHAR(100),
    `billing_last_name` VARCHAR(100),
    `billing_address_line1` VARCHAR(255),
    `billing_city` VARCHAR(100),
    `billing_state` VARCHAR(100),
    `billing_postal_code` VARCHAR(20),
    `billing_country` VARCHAR(2),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_used_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`),
    UNIQUE KEY `unique_customer_method` (`customer_id`, `gateway_payment_method_token`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 8: SUBSCRIPTION PAYMENTS (For future subscriptions)
-- ============================================
CREATE TABLE IF NOT EXISTS `subscription_payments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `subscription_id` INT,
    `customer_id` INT NOT NULL,
    `payment_method_id` INT NOT NULL,
    `gateway_subscription_id` VARCHAR(255),
    `amount` DECIMAL(12, 2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'USD',
    `billing_cycle_start` DATE,
    `billing_cycle_end` DATE,
    `status` ENUM('active', 'past_due', 'cancelled', 'failed') DEFAULT 'active',
    `auto_retry_enabled` BOOLEAN DEFAULT TRUE,
    `retry_count` INT DEFAULT 0,
    `gateway_response` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA: Popular Payment Gateways
-- ============================================

-- Stripe (Primary Gateway)
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    'stripe', 'Credit/Debit Card (Stripe)', 'Pay securely with Stripe',
    'card', 1, TRUE, FALSE, 2.9, 0.30, 24,
    JSON_ARRAY('US', 'UK', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES', 'IN', 'AE', 'SA'),
    JSON_ARRAY('USD', 'EUR', 'GBP', 'AUD', 'CAD', 'INR', 'AED', 'SAR')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;

-- PayPal
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    'paypal', 'PayPal', 'Pay with your PayPal account',
    'wallet', 2, TRUE, FALSE, 3.49, 0.49, 24,
    JSON_ARRAY('US', 'UK', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES', 'IN', 'AE', 'SA'),
    JSON_ARRAY('USD', 'EUR', 'GBP', 'AUD', 'CAD', 'INR', 'AED')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;

-- Razorpay (For India)
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    'razorpay', 'Razorpay (Cards, Wallets, UPI, Bank)', 'India payment gateway',
    'card', 3, TRUE, FALSE, 1.96, 0.00, 12,
    JSON_ARRAY('IN'),
    JSON_ARRAY('INR')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;

-- Square
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    'square', 'Square', 'Pay with Square payment processing',
    'card', 4, TRUE, FALSE, 2.9, 0.30, 24,
    JSON_ARRAY('US', 'CA', 'AU', 'JP', 'UK'),
    JSON_ARRAY('USD', 'CAD', 'AUD', 'JPY', 'GBP')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;

-- Paytm (For India)
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    'paytm', 'Paytm', 'India mobile payment wallet',
    'wallet', 5, TRUE, FALSE, 2.0, 0.00, 12,
    JSON_ARRAY('IN'),
    JSON_ARRAY('INR')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;

-- Google Pay / Apple Pay (Stripe)
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    'google_pay', 'Google Pay', 'Fast and secure with Google Pay',
    'wallet', 6, TRUE, FALSE, 2.9, 0.30, 24,
    JSON_ARRAY('US', 'UK', 'CA', 'AU', 'DE', 'FR', 'IT', 'IN'),
    JSON_ARRAY('USD', 'EUR', 'GBP', 'AUD', 'CAD', 'INR')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;

-- Authorize.net
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    'authorize_net', 'Authorize.Net', 'Secure payment processing',
    'card', 7, TRUE, TRUE, 2.9, 0.30, 24,
    JSON_ARRAY('US', 'CA', 'GB', 'AU'),
    JSON_ARRAY('USD', 'CAD', 'GBP', 'AUD')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;

-- Skrill (For International transfers)
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    'skrill', 'Skrill', 'International digital wallet',
    'wallet', 8, TRUE, FALSE, 3.5, 0.70, 48,
    JSON_ARRAY('IN', 'AE', 'SA', 'PK', 'BD'),
    JSON_ARRAY('INR', 'AED', 'SAR', 'USD')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;

-- 2Checkout
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    '2checkout', '2Checkout', 'Global payment processing',
    'card', 9, TRUE, FALSE, 3.45, 0.45, 24,
    JSON_ARRAY('US', 'UK', 'CA', 'AU', 'DE', 'FR', 'IN'),
    JSON_ARRAY('USD', 'EUR', 'GBP', 'INR')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;

-- Mollie (For Europe)
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    'mollie', 'Mollie', 'European payment platform',
    'card', 10, TRUE, FALSE, 1.75, 0.29, 24,
    JSON_ARRAY('NL', 'DE', 'FR', 'BE', 'AT', 'ES', 'IT'),
    JSON_ARRAY('EUR')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;

-- Amazon Pay
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    'amazon_pay', 'Amazon Pay', 'Pay with Amazon account',
    'wallet', 11, TRUE, FALSE, 2.5, 0.00, 24,
    JSON_ARRAY('US', 'UK', 'DE', 'FR', 'IT', 'ES', 'JP', 'AU'),
    JSON_ARRAY('USD', 'EUR', 'GBP', 'JPY', 'AUD')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;

-- Instamojo (For India)
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    'instamojo', 'Instamojo', 'India payments platform',
    'card', 12, TRUE, FALSE, 3.45, 3.0, 12,
    JSON_ARRAY('IN'),
    JSON_ARRAY('INR')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;

-- Cashfree (For India)
INSERT INTO `payment_methods` (
    `gateway_name`, `display_name`, `description`, `gateway_type`,
    `position`, `supports_refund`, `requires_3d_secure`,
    `transaction_fee_percent`, `transaction_fee_fixed`,
    `processing_time_hours`, `countries_supported`, `currencies_supported`
) VALUES (
    'cashfree', 'Cashfree', 'India all-in-one payment platform',
    'card', 13, TRUE, FALSE, 1.96, 0.00, 12,
    JSON_ARRAY('IN'),
    JSON_ARRAY('INR')
) ON DUPLICATE KEY UPDATE `gateway_name`=`gateway_name`;
