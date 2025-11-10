-- ============================================
-- MULTI-CURRENCY SYSTEM SCHEMA
-- Phase 1, Task 3: Multi-Currency Support
-- Database: u618910819_bookshelf_db
-- ============================================

-- ============================================
-- TABLE 1: CURRENCIES
-- Supported currencies and configurations
-- ============================================
CREATE TABLE IF NOT EXISTS `currencies` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `currency_code` VARCHAR(3) NOT NULL UNIQUE,
    `currency_name` VARCHAR(100) NOT NULL,
    `currency_symbol` VARCHAR(10) NOT NULL,
    `symbol_position` ENUM('before', 'after', 'before_space', 'after_space') DEFAULT 'before',
    `decimal_separator` VARCHAR(1) DEFAULT '.',
    `thousands_separator` VARCHAR(1) DEFAULT ',',
    `decimal_places` INT DEFAULT 2,

    -- Locale & Regional
    `country_code` VARCHAR(2),
    `locale` VARCHAR(10),
    `is_rtl` BOOLEAN DEFAULT FALSE,

    -- Status
    `is_active` BOOLEAN DEFAULT TRUE,
    `is_default` BOOLEAN DEFAULT FALSE,
    `display_order` INT DEFAULT 0,

    -- Rates
    `exchange_rate_to_usd` DECIMAL(15, 6) NOT NULL DEFAULT 1.000000,
    `last_rate_update` TIMESTAMP,
    `next_rate_update` TIMESTAMP,
    `auto_update_rates` BOOLEAN DEFAULT TRUE,

    -- Restrictions
    `enabled_in_regions` JSON,
    `disabled_in_regions` JSON,
    `min_transaction_amount` DECIMAL(12, 2),
    `max_transaction_amount` DECIMAL(12, 2),

    -- Metadata
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT,

    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
    INDEX `idx_currency_code` (`currency_code`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_is_default` (`is_default`),
    INDEX `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 2: CURRENCY RATES HISTORY
-- Track historical exchange rates
-- ============================================
CREATE TABLE IF NOT EXISTS `currency_rates_history` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `currency_id` INT NOT NULL,
    `exchange_rate_to_usd` DECIMAL(15, 6) NOT NULL,
    `rate_source` VARCHAR(100),
    `data_provider` VARCHAR(100),
    `rate_timestamp` TIMESTAMP,
    `effective_from` TIMESTAMP,
    `effective_to` TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`) ON DELETE CASCADE,
    INDEX `idx_currency_date` (`currency_id`, `effective_from`),
    INDEX `idx_effective_date` (`effective_from`, `effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 3: CURRENCY CONVERSION RULES
-- Special conversion rules between currencies
-- ============================================
CREATE TABLE IF NOT EXISTS `currency_conversion_rules` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `from_currency_id` INT NOT NULL,
    `to_currency_id` INT NOT NULL,
    `conversion_rate` DECIMAL(15, 6) NOT NULL,
    `rule_type` ENUM('fixed', 'percentage_markup', 'percentage_discount') DEFAULT 'fixed',
    `markup_percentage` DECIMAL(5, 2),
    `discount_percentage` DECIMAL(5, 2),
    `applies_to_amount_range_min` DECIMAL(12, 2),
    `applies_to_amount_range_max` DECIMAL(12, 2),
    `is_active` BOOLEAN DEFAULT TRUE,
    `effective_from` TIMESTAMP,
    `effective_to` TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`from_currency_id`) REFERENCES `currencies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`to_currency_id`) REFERENCES `currencies`(`id`) ON DELETE CASCADE,
    INDEX `idx_from_to` (`from_currency_id`, `to_currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 4: CUSTOMER CURRENCY PREFERENCES
-- Customer's preferred currency
-- ============================================
CREATE TABLE IF NOT EXISTS `customer_currency_preferences` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `customer_id` INT NOT NULL UNIQUE,
    `preferred_currency_id` INT NOT NULL,
    `show_multiple_currencies` BOOLEAN DEFAULT FALSE,
    `auto_convert_on_checkout` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`preferred_currency_id`) REFERENCES `currencies`(`id`),
    INDEX `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 5: PRODUCT PRICES IN MULTIPLE CURRENCIES
-- Store prices for each product in each currency
-- ============================================
CREATE TABLE IF NOT EXISTS `product_prices_multi_currency` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `currency_id` INT NOT NULL,
    `price` DECIMAL(12, 2) NOT NULL,
    `original_price` DECIMAL(12, 2),
    `cost_price` DECIMAL(12, 2),
    `profit_margin_percent` DECIMAL(5, 2),
    `is_calculated` BOOLEAN DEFAULT TRUE,
    `is_custom_price` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_product_currency` (`product_id`, `currency_id`),
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_currency_id` (`currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 6: ORDER CURRENCY DETAILS
-- Currency information for each order
-- ============================================
CREATE TABLE IF NOT EXISTS `order_currency_details` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `order_id` INT NOT NULL UNIQUE,
    `original_currency_id` INT,
    `payment_currency_id` INT NOT NULL,
    `subtotal_original_currency` DECIMAL(12, 2),
    `subtotal_payment_currency` DECIMAL(12, 2),
    `conversion_rate_applied` DECIMAL(15, 6),
    `rate_source` VARCHAR(100),
    `conversion_fee` DECIMAL(12, 2) DEFAULT 0,
    `conversion_fee_percentage` DECIMAL(5, 2),
    `total_original_currency` DECIMAL(12, 2),
    `total_payment_currency` DECIMAL(12, 2),
    `conversion_timestamp` TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`original_currency_id`) REFERENCES `currencies`(`id`),
    FOREIGN KEY (`payment_currency_id`) REFERENCES `currencies`(`id`),
    INDEX `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 7: CURRENCY API CONFIGURATIONS
-- External API credentials for rate updates
-- ============================================
CREATE TABLE IF NOT EXISTS `currency_api_configs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `provider_name` VARCHAR(100) NOT NULL UNIQUE,
    `api_endpoint` VARCHAR(500),
    `api_key` VARCHAR(255),
    `api_secret` VARCHAR(255),
    `update_frequency_minutes` INT DEFAULT 60,
    `last_update_attempt` TIMESTAMP NULL,
    `last_successful_update` TIMESTAMP NULL,
    `update_status` ENUM('active', 'inactive', 'error') DEFAULT 'inactive',
    `error_message` TEXT,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_provider` (`provider_name`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA: Supported Currencies
-- ============================================

-- US Dollar (Default)
INSERT INTO `currencies` (
    `currency_code`, `currency_name`, `currency_symbol`, `symbol_position`,
    `decimal_separator`, `thousands_separator`, `decimal_places`,
    `country_code`, `locale`, `exchange_rate_to_usd`,
    `is_active`, `is_default`, `display_order`
) VALUES (
    'USD', 'US Dollar', '$', 'before',
    '.', ',', 2,
    'US', 'en_US', 1.000000,
    TRUE, TRUE, 1
) ON DUPLICATE KEY UPDATE `currency_code`=`currency_code`;

-- Euro
INSERT INTO `currencies` (
    `currency_code`, `currency_name`, `currency_symbol`, `symbol_position`,
    `decimal_separator`, `thousands_separator`, `decimal_places`,
    `country_code`, `locale`, `exchange_rate_to_usd`,
    `is_active`, `is_default`, `display_order`
) VALUES (
    'EUR', 'Euro', '€', 'after_space',
    ',', '.', 2,
    'DE', 'de_DE', 0.920000,
    TRUE, FALSE, 2
) ON DUPLICATE KEY UPDATE `currency_code`=`currency_code`;

-- British Pound
INSERT INTO `currencies` (
    `currency_code`, `currency_name`, `currency_symbol`, `symbol_position`,
    `decimal_separator`, `thousands_separator`, `decimal_places`,
    `country_code`, `locale`, `exchange_rate_to_usd`,
    `is_active`, `is_default`, `display_order`
) VALUES (
    'GBP', 'British Pound', '£', 'before',
    '.', ',', 2,
    'GB', 'en_GB', 1.260000,
    TRUE, FALSE, 3
) ON DUPLICATE KEY UPDATE `currency_code`=`currency_code`;

-- Indian Rupee
INSERT INTO `currencies` (
    `currency_code`, `currency_name`, `currency_symbol`, `symbol_position`,
    `decimal_separator`, `thousands_separator`, `decimal_places`,
    `country_code`, `locale`, `exchange_rate_to_usd`,
    `is_active`, `is_default`, `display_order`
) VALUES (
    'INR', 'Indian Rupee', '₹', 'before',
    '.', ',', 2,
    'IN', 'hi_IN', 0.012000,
    TRUE, FALSE, 4
) ON DUPLICATE KEY UPDATE `currency_code`=`currency_code`;

-- Australian Dollar
INSERT INTO `currencies` (
    `currency_code`, `currency_name`, `currency_symbol`, `symbol_position`,
    `decimal_separator`, `thousands_separator`, `decimal_places`,
    `country_code`, `locale`, `exchange_rate_to_usd`,
    `is_active`, `is_default`, `display_order`
) VALUES (
    'AUD', 'Australian Dollar', 'A$', 'before',
    '.', ',', 2,
    'AU', 'en_AU', 0.650000,
    TRUE, FALSE, 5
) ON DUPLICATE KEY UPDATE `currency_code`=`currency_code`;

-- Canadian Dollar
INSERT INTO `currencies` (
    `currency_code`, `currency_name`, `currency_symbol`, `symbol_position`,
    `decimal_separator`, `thousands_separator`, `decimal_places`,
    `country_code`, `locale`, `exchange_rate_to_usd`,
    `is_active`, `is_default`, `display_order`
) VALUES (
    'CAD', 'Canadian Dollar', 'C$', 'before',
    '.', ',', 2,
    'CA', 'en_CA', 0.730000,
    TRUE, FALSE, 6
) ON DUPLICATE KEY UPDATE `currency_code`=`currency_code`;

-- Singapore Dollar
INSERT INTO `currencies` (
    `currency_code`, `currency_name`, `currency_symbol`, `symbol_position`,
    `decimal_separator`, `thousands_separator`, `decimal_places`,
    `country_code`, `locale`, `exchange_rate_to_usd`,
    `is_active`, `is_default`, `display_order`
) VALUES (
    'SGD', 'Singapore Dollar', 'S$', 'before',
    '.', ',', 2,
    'SG', 'en_SG', 0.750000,
    TRUE, FALSE, 7
) ON DUPLICATE KEY UPDATE `currency_code`=`currency_code`;

-- Japanese Yen
INSERT INTO `currencies` (
    `currency_code`, `currency_name`, `currency_symbol`, `symbol_position`,
    `decimal_separator`, `thousands_separator`, `decimal_places`,
    `country_code`, `locale`, `exchange_rate_to_usd`,
    `is_active`, `is_default`, `display_order`
) VALUES (
    'JPY', 'Japanese Yen', '¥', 'before',
    '.', ',', 0,
    'JP', 'ja_JP', 0.007000,
    TRUE, FALSE, 8
) ON DUPLICATE KEY UPDATE `currency_code`=`currency_code`;

-- UAE Dirham
INSERT INTO `currencies` (
    `currency_code`, `currency_name`, `currency_symbol`, `symbol_position`,
    `decimal_separator`, `thousands_separator`, `decimal_places`,
    `country_code`, `locale`, `exchange_rate_to_usd`,
    `is_active`, `is_default`, `display_order`
) VALUES (
    'AED', 'UAE Dirham', 'د.إ', 'after_space',
    '.', ',', 2,
    'AE', 'ar_AE', 0.272000,
    TRUE, FALSE, 9
) ON DUPLICATE KEY UPDATE `currency_code`=`currency_code`;

-- Saudi Riyal
INSERT INTO `currencies` (
    `currency_code`, `currency_name`, `currency_symbol`, `symbol_position`,
    `decimal_separator`, `thousands_separator`, `decimal_places`,
    `country_code`, `locale`, `exchange_rate_to_usd`,
    `is_active`, `is_default`, `display_order`
) VALUES (
    'SAR', 'Saudi Riyal', 'ر.س', 'after_space',
    '.', ',', 2,
    'SA', 'ar_SA', 0.267000,
    TRUE, FALSE, 10
) ON DUPLICATE KEY UPDATE `currency_code`=`currency_code`;

-- ============================================
-- SEED DATA: Currency API Configurations
-- ============================================

-- Open Exchange Rates
INSERT INTO `currency_api_configs` (
    `provider_name`, `api_endpoint`, `update_frequency_minutes`,
    `is_active`
) VALUES (
    'Open Exchange Rates',
    'https://openexchangerates.org/api/latest.json',
    60,
    TRUE
) ON DUPLICATE KEY UPDATE `provider_name`=`provider_name`;

-- Exchange Rate API
INSERT INTO `currency_api_configs` (
    `provider_name`, `api_endpoint`, `update_frequency_minutes`,
    `is_active`
) VALUES (
    'Exchange Rate API',
    'https://api.exchangerate-api.com/v4/latest/',
    60,
    TRUE
) ON DUPLICATE KEY UPDATE `provider_name`=`provider_name`;

-- Fixer.io
INSERT INTO `currency_api_configs` (
    `provider_name`, `api_endpoint`, `update_frequency_minutes`,
    `is_active`
) VALUES (
    'Fixer.io',
    'https://api.fixer.io/latest',
    60,
    FALSE
) ON DUPLICATE KEY UPDATE `provider_name`=`provider_name`;
