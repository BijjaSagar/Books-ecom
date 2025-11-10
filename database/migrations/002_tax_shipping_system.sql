-- ============================================
-- TAX & SHIPPING SYSTEM SCHEMA
-- Phase 1, Task 2: Tax & Shipping Configuration
-- Database: u618910819_bookshelf_db
-- ============================================

-- ============================================
-- TABLE 1: TAX RATES
-- State/Country-wise tax configuration
-- ============================================
CREATE TABLE IF NOT EXISTS `tax_rates` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `country` VARCHAR(2) NOT NULL,
    `state_province` VARCHAR(100),
    `tax_region_code` VARCHAR(50),
    `tax_percentage` DECIMAL(5, 2) NOT NULL,
    `effective_from` DATE NOT NULL,
    `effective_to` DATE,
    `applies_to` ENUM('products', 'digital_goods', 'services', 'all') DEFAULT 'all',
    `tax_type` ENUM('sales_tax', 'vat', 'gst', 'custom') DEFAULT 'sales_tax',
    `is_compound` BOOLEAN DEFAULT FALSE,
    `compound_on_tax_id` INT,
    `priority` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT,

    FOREIGN KEY (`compound_on_tax_id`) REFERENCES `tax_rates`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),

    UNIQUE KEY `unique_tax_region` (`country`, `state_province`, `applies_to`),
    INDEX `idx_country_state` (`country`, `state_province`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_effective_date` (`effective_from`, `effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 2: TAX EXEMPTIONS
-- Products/customers exempted from tax
-- ============================================
CREATE TABLE IF NOT EXISTS `tax_exemptions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `exemption_type` ENUM('product', 'customer_type', 'order_type') NOT NULL,
    `entity_id` INT,
    `customer_type` ENUM('individual', 'business', 'nonprofit', 'government') DEFAULT 'individual',
    `tax_exempt_reason` VARCHAR(100),
    `tax_exempt_certificate` VARCHAR(255),
    `tax_exempt_certificate_expiry` DATE,
    `countries_applicable` JSON,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_type_entity` (`exemption_type`, `entity_id`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 3: SHIPPING ZONES
-- Geographic areas with shipping rules
-- ============================================
CREATE TABLE IF NOT EXISTS `shipping_zones` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `zone_type` ENUM('country', 'state', 'city', 'postal_code', 'region', 'custom') NOT NULL,
    `countries` JSON,
    `states` JSON,
    `cities` JSON,
    `postal_codes_pattern` VARCHAR(255),
    `exclude_areas` JSON,
    `priority` INT DEFAULT 0,
    `is_default_zone` BOOLEAN DEFAULT FALSE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT,

    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
    INDEX `idx_priority` (`priority`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_is_default` (`is_default_zone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 4: SHIPPING METHODS
-- Shipping options available to customers
-- ============================================
CREATE TABLE IF NOT EXISTS `shipping_methods` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `shipping_zone_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `shipping_type` ENUM('standard', 'express', 'overnight', 'pickup', 'digital', 'free') DEFAULT 'standard',
    `provider` VARCHAR(100),
    `provider_code` VARCHAR(50),
    `icon_url` VARCHAR(255),

    -- Pricing
    `base_price` DECIMAL(10, 2) NOT NULL DEFAULT 0,
    `price_per_item` DECIMAL(10, 2) DEFAULT 0,
    `price_per_kg` DECIMAL(10, 2) DEFAULT 0,
    `min_order_amount` DECIMAL(12, 2) DEFAULT 0,
    `max_order_amount` DECIMAL(12, 2),
    `free_shipping_threshold` DECIMAL(12, 2),

    -- Delivery Time
    `min_days` INT DEFAULT 1,
    `max_days` INT DEFAULT 5,
    `cutoff_time` TIME,
    `cutoff_timezone` VARCHAR(50),

    -- Restrictions
    `requires_signature` BOOLEAN DEFAULT FALSE,
    `allows_po_box` BOOLEAN DEFAULT TRUE,
    `max_weight_kg` DECIMAL(10, 2),
    `max_dimensions_cm` VARCHAR(100),

    -- Tracking & Insurance
    `provides_tracking` BOOLEAN DEFAULT TRUE,
    `includes_insurance` BOOLEAN DEFAULT FALSE,
    `insurance_percentage` DECIMAL(5, 2),

    -- Status
    `is_active` BOOLEAN DEFAULT TRUE,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT,

    FOREIGN KEY (`shipping_zone_id`) REFERENCES `shipping_zones`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),

    INDEX `idx_zone_id` (`shipping_zone_id`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_type` (`shipping_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 5: SHIPPING RATES
-- Complex rate calculation rules
-- ============================================
CREATE TABLE IF NOT EXISTS `shipping_rates` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `shipping_method_id` INT NOT NULL,
    `rate_name` VARCHAR(100),
    `rate_type` ENUM('flat', 'weight_based', 'quantity_based', 'order_total', 'tiered') DEFAULT 'flat',

    -- For weight-based rates
    `min_weight_kg` DECIMAL(10, 2),
    `max_weight_kg` DECIMAL(10, 2),
    `weight_rate_per_kg` DECIMAL(10, 2),

    -- For quantity-based rates
    `min_quantity` INT,
    `max_quantity` INT,
    `quantity_rate_per_item` DECIMAL(10, 2),

    -- For order total-based rates
    `min_order_total` DECIMAL(12, 2),
    `max_order_total` DECIMAL(12, 2),
    `order_total_rate` DECIMAL(10, 2),

    -- For tiered/bulk rates
    `rate_value` DECIMAL(10, 2) NOT NULL DEFAULT 0,

    -- Additional charges
    `cod_fee` DECIMAL(10, 2) DEFAULT 0,
    `handling_fee` DECIMAL(10, 2) DEFAULT 0,

    -- Conditions
    `applicable_days` VARCHAR(50),
    `applicable_product_types` JSON,
    `exclude_products` JSON,

    `is_active` BOOLEAN DEFAULT TRUE,
    `priority` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods`(`id`) ON DELETE CASCADE,
    INDEX `idx_method_id` (`shipping_method_id`),
    INDEX `idx_rate_type` (`rate_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 6: SHIPPING RULES
-- Business rules for shipping eligibility
-- ============================================
CREATE TABLE IF NOT EXISTS `shipping_rules` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `shipping_method_id` INT NOT NULL,
    `rule_type` ENUM('require_all', 'require_any', 'exclude') DEFAULT 'require_all',

    -- Product restrictions
    `applicable_product_types` JSON,
    `exclude_product_types` JSON,
    `requires_inventory` BOOLEAN DEFAULT TRUE,
    `min_stock_required` INT DEFAULT 1,

    -- Customer restrictions
    `applicable_customer_types` JSON,
    `exclude_customer_groups` JSON,
    `min_order_value` DECIMAL(12, 2),
    `max_order_value` DECIMAL(12, 2),

    -- Destination restrictions
    `allowed_countries` JSON,
    `excluded_countries` JSON,
    `allowed_states` JSON,

    -- Time restrictions
    `available_from` DATETIME,
    `available_to` DATETIME,
    `available_on_days` VARCHAR(50),

    `is_active` BOOLEAN DEFAULT TRUE,
    `priority` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods`(`id`) ON DELETE CASCADE,
    INDEX `idx_method_id` (`shipping_method_id`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 7: PRODUCT SHIPPING DATA
-- Product-specific shipping properties
-- ============================================
CREATE TABLE IF NOT EXISTS `product_shipping` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL UNIQUE,
    `is_shippable` BOOLEAN DEFAULT TRUE,
    `weight_kg` DECIMAL(10, 3),
    `length_cm` DECIMAL(10, 2),
    `width_cm` DECIMAL(10, 2),
    `height_cm` DECIMAL(10, 2),
    `shipping_class` VARCHAR(50),
    `freight_class` VARCHAR(20),
    `harmonized_code` VARCHAR(20),
    `hs_code` VARCHAR(20),
    `origin_country` VARCHAR(2),
    `requires_refrigeration` BOOLEAN DEFAULT FALSE,
    `fragile` BOOLEAN DEFAULT FALSE,
    `hazardous` BOOLEAN DEFAULT FALSE,
    `hazmat_class` VARCHAR(50),
    `additional_shipping_cost` DECIMAL(10, 2) DEFAULT 0,
    `disable_shipping_methods` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_shippable` (`is_shippable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 8: ORDER TAX DETAILS
-- Tax breakdown for each order
-- ============================================
CREATE TABLE IF NOT EXISTS `order_tax_details` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `order_id` INT NOT NULL UNIQUE,
    `subtotal_before_tax` DECIMAL(12, 2) NOT NULL,
    `subtotal_taxable` DECIMAL(12, 2) NOT NULL,
    `subtotal_exempt` DECIMAL(12, 2) DEFAULT 0,

    -- Tax breakdown
    `tax_applied` JSON,
    `total_tax` DECIMAL(12, 2) NOT NULL DEFAULT 0,
    `tax_percentage` DECIMAL(5, 2),

    -- Tax IDs
    `customer_tax_id` VARCHAR(50),
    `business_tax_id` VARCHAR(50),

    -- Tax calculations
    `tax_calculation_method` ENUM('compound', 'sequential', 'simple') DEFAULT 'simple',
    `rounding_method` ENUM('round', 'ceil', 'floor') DEFAULT 'round',

    -- Audit
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    INDEX `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 9: ORDER SHIPPING DETAILS
-- Shipping information for each order
-- ============================================
CREATE TABLE IF NOT EXISTS `order_shipping_details` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `order_id` INT NOT NULL UNIQUE,
    `shipping_method_id` INT,
    `shipping_method_name` VARCHAR(100) NOT NULL,
    `shipping_provider` VARCHAR(100),
    `tracking_number` VARCHAR(100),
    `tracking_url` VARCHAR(500),

    -- Shipping cost breakdown
    `base_shipping_cost` DECIMAL(12, 2),
    `additional_charges` DECIMAL(12, 2) DEFAULT 0,
    `handling_fee` DECIMAL(12, 2) DEFAULT 0,
    `cod_fee` DECIMAL(12, 2) DEFAULT 0,
    `total_shipping_cost` DECIMAL(12, 2) NOT NULL,

    -- Delivery estimates
    `estimated_delivery_date` DATE,
    `actual_delivery_date` DATE,
    `current_location` VARCHAR(255),

    -- Shipping status
    `shipping_status` ENUM(
        'not_yet_shipped',
        'picked_up',
        'in_transit',
        'out_for_delivery',
        'delivered',
        'failed_attempt',
        'returned',
        'lost'
    ) DEFAULT 'not_yet_shipped',

    -- Weight & dimensions
    `weight_kg` DECIMAL(10, 3),
    `dimensions_cm` VARCHAR(100),

    -- Additional info
    `signature_required` BOOLEAN DEFAULT FALSE,
    `requires_adult_signature` BOOLEAN DEFAULT FALSE,
    `insurance_claimed` BOOLEAN DEFAULT FALSE,
    `insurance_amount` DECIMAL(12, 2),

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods`(`id`),

    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_tracking_number` (`tracking_number`),
    INDEX `idx_status` (`shipping_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 10: SHIPPING ZONE MAPPINGS (Postal Codes)
-- Detailed postal code to zone mappings
-- ============================================
CREATE TABLE IF NOT EXISTS `shipping_zone_postcodes` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `shipping_zone_id` INT NOT NULL,
    `country` VARCHAR(2) NOT NULL,
    `state` VARCHAR(100),
    `postal_code_from` VARCHAR(20),
    `postal_code_to` VARCHAR(20),
    `city` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`shipping_zone_id`) REFERENCES `shipping_zones`(`id`) ON DELETE CASCADE,
    INDEX `idx_zone_postal` (`shipping_zone_id`, `postal_code_from`),
    INDEX `idx_country_postal` (`country`, `postal_code_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA: Sample Tax Rates
-- ============================================

-- US Sales Tax Examples
INSERT INTO `tax_rates` (
    `name`, `country`, `state_province`, `tax_percentage`,
    `effective_from`, `applies_to`, `tax_type`, `is_active`
) VALUES
    ('US - California Sales Tax', 'US', 'CA', 7.25, '2025-01-01', 'all', 'sales_tax', TRUE),
    ('US - New York Sales Tax', 'US', 'NY', 8.00, '2025-01-01', 'all', 'sales_tax', TRUE),
    ('US - Texas Sales Tax', 'US', 'TX', 6.25, '2025-01-01', 'all', 'sales_tax', TRUE),
    ('US - Florida Sales Tax', 'US', 'FL', 6.00, '2025-01-01', 'all', 'sales_tax', TRUE)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- India GST Examples
INSERT INTO `tax_rates` (
    `name`, `country`, `state_province`, `tax_percentage`,
    `effective_from`, `applies_to`, `tax_type`, `is_active`
) VALUES
    ('India - GST Standard (Books)', 'IN', NULL, 5.00, '2025-01-01', 'products', 'gst', TRUE),
    ('India - GST Digital Goods', 'IN', NULL, 18.00, '2025-01-01', 'digital_goods', 'gst', TRUE)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- UK VAT
INSERT INTO `tax_rates` (
    `name`, `country`, `state_province`, `tax_percentage`,
    `effective_from`, `applies_to`, `tax_type`, `is_active`
) VALUES
    ('UK - VAT Standard Rate', 'GB', NULL, 20.00, '2025-01-01', 'all', 'vat', TRUE),
    ('UK - VAT Reduced (Books)', 'GB', NULL, 0.00, '2025-01-01', 'products', 'vat', TRUE)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- EU VAT
INSERT INTO `tax_rates` (
    `name`, `country`, `state_province`, `tax_percentage`,
    `effective_from`, `applies_to`, `tax_type`, `is_active`
) VALUES
    ('EU - Germany VAT', 'DE', NULL, 19.00, '2025-01-01', 'all', 'vat', TRUE),
    ('EU - France VAT', 'FR', NULL, 20.00, '2025-01-01', 'all', 'vat', TRUE),
    ('EU - Italy VAT', 'IT', NULL, 22.00, '2025-01-01', 'all', 'vat', TRUE)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- ============================================
-- SEED DATA: Sample Shipping Zones
-- ============================================

INSERT INTO `shipping_zones` (
    `name`, `zone_type`, `countries`, `priority`, `is_active`
) VALUES
    ('Domestic US', 'country', JSON_ARRAY('US'), 1, TRUE),
    ('Canada', 'country', JSON_ARRAY('CA'), 2, TRUE),
    ('European Union', 'region', JSON_ARRAY('DE', 'FR', 'IT', 'ES', 'GB'), 3, TRUE),
    ('Asia Pacific', 'region', JSON_ARRAY('IN', 'AU', 'JP', 'SG'), 4, TRUE),
    ('Rest of World', 'custom', JSON_ARRAY('*'), 999, TRUE)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- ============================================
-- SEED DATA: Sample Shipping Methods
-- ============================================

-- Standard Shipping - Domestic US
INSERT INTO `shipping_methods` (
    `shipping_zone_id`, `name`, `shipping_type`, `base_price`,
    `min_days`, `max_days`, `is_active`, `display_order`
) VALUES
    (
        (SELECT id FROM shipping_zones WHERE name = 'Domestic US'),
        'Standard Shipping (5-7 days)',
        'standard',
        5.99,
        5, 7, TRUE, 1
    )
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Express Shipping - Domestic US
INSERT INTO `shipping_methods` (
    `shipping_zone_id`, `name`, `shipping_type`, `base_price`,
    `min_days`, `max_days`, `is_active`, `display_order`
) VALUES
    (
        (SELECT id FROM shipping_zones WHERE name = 'Domestic US'),
        'Express Shipping (2-3 days)',
        'express',
        12.99,
        2, 3, TRUE, 2
    )
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Overnight Shipping - Domestic US
INSERT INTO `shipping_methods` (
    `shipping_zone_id`, `name`, `shipping_type`, `base_price`,
    `min_days`, `max_days`, `is_active`, `display_order`
) VALUES
    (
        (SELECT id FROM shipping_zones WHERE name = 'Domestic US'),
        'Overnight Shipping (Next Day)',
        'overnight',
        24.99,
        1, 1, TRUE, 3
    )
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Canada Shipping
INSERT INTO `shipping_methods` (
    `shipping_zone_id`, `name`, `shipping_type`, `base_price`,
    `min_days`, `max_days`, `is_active`, `display_order`
) VALUES
    (
        (SELECT id FROM shipping_zones WHERE name = 'Canada'),
        'Standard Shipping (7-10 days)',
        'standard',
        12.99,
        7, 10, TRUE, 1
    )
ON DUPLICATE KEY UPDATE `name`=`name`;

-- International Shipping
INSERT INTO `shipping_methods` (
    `shipping_zone_id`, `name`, `shipping_type`, `base_price`,
    `min_days`, `max_days`, `is_active`, `display_order`
) VALUES
    (
        (SELECT id FROM shipping_zones WHERE name = 'European Union'),
        'International Standard (10-15 days)',
        'standard',
        19.99,
        10, 15, TRUE, 1
    )
ON DUPLICATE KEY UPDATE `name`=`name`;
