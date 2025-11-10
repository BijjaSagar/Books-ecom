-- ============================================
-- CHECKOUT & ORDERS SYSTEM SCHEMA
-- Phase 1, Task 4: Complete Checkout Flow
-- Database: u618910819_bookshelf_db
-- ============================================

-- ============================================
-- TABLE 1: CUSTOMER ADDRESSES
-- Store multiple shipping addresses per customer
-- ============================================
CREATE TABLE IF NOT EXISTS `customer_addresses` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `type` ENUM('shipping', 'billing', 'both') DEFAULT 'shipping',
    `is_default` BOOLEAN DEFAULT FALSE,

    -- Address Information
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `company_name` VARCHAR(150),
    `street_address_1` VARCHAR(255) NOT NULL,
    `street_address_2` VARCHAR(255),
    `city` VARCHAR(100) NOT NULL,
    `state_province` VARCHAR(100) NOT NULL,
    `postal_code` VARCHAR(20) NOT NULL,
    `country` VARCHAR(2) NOT NULL,
    `phone_number` VARCHAR(20),
    `email` VARCHAR(255),

    -- Validation
    `is_validated` BOOLEAN DEFAULT FALSE,
    `validation_result` JSON,
    `validated_at` TIMESTAMP NULL,

    -- Metadata
    `nickname` VARCHAR(100),
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_is_default` (`is_default`),
    INDEX `idx_country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 2: SHOPPING CARTS
-- Temporary cart storage before checkout
-- ============================================
CREATE TABLE IF NOT EXISTS `shopping_carts` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `customer_id` INT,
    `session_id` VARCHAR(255),
    `currency_code` VARCHAR(3) DEFAULT 'USD',

    -- Cart Totals
    `subtotal` DECIMAL(12, 2) DEFAULT 0,
    `subtotal_before_discount` DECIMAL(12, 2) DEFAULT 0,
    `discount_amount` DECIMAL(12, 2) DEFAULT 0,
    `discount_code` VARCHAR(50),
    `tax_amount` DECIMAL(12, 2) DEFAULT 0,
    `shipping_cost` DECIMAL(12, 2) DEFAULT 0,
    `total` DECIMAL(12, 2) DEFAULT 0,

    -- Items
    `items_count` INT DEFAULT 0,
    `items_weight_kg` DECIMAL(10, 3) DEFAULT 0,

    -- Status
    `status` ENUM('active', 'abandoned', 'converted', 'expired') DEFAULT 'active',
    `abandoned_at` TIMESTAMP NULL,
    `converted_to_order_id` INT,

    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NULL,

    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`converted_to_order_id`) REFERENCES `orders`(`id`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_session_id` (`session_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 3: CART ITEMS
-- Individual items in shopping cart
-- ============================================
CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `cart_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `price_at_time` DECIMAL(12, 2) NOT NULL,
    `currency_code` VARCHAR(3) DEFAULT 'USD',
    `line_total` DECIMAL(12, 2) NOT NULL,
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`cart_id`) REFERENCES `shopping_carts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_cart_id` (`cart_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 4: ORDERS (Enhanced)
-- Main orders table
-- ============================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `customer_id` INT NOT NULL,

    -- Address Information
    `shipping_address_id` INT,
    `billing_address_id` INT,
    `shipping_first_name` VARCHAR(100),
    `shipping_last_name` VARCHAR(100),
    `shipping_street_1` VARCHAR(255),
    `shipping_street_2` VARCHAR(255),
    `shipping_city` VARCHAR(100),
    `shipping_state` VARCHAR(100),
    `shipping_postal_code` VARCHAR(20),
    `shipping_country` VARCHAR(2),
    `shipping_phone` VARCHAR(20),
    `shipping_email` VARCHAR(255),

    -- Order Content
    `items_count` INT NOT NULL DEFAULT 0,
    `subtotal` DECIMAL(12, 2) NOT NULL,
    `discount_amount` DECIMAL(12, 2) DEFAULT 0,
    `discount_code` VARCHAR(50),
    `tax_amount` DECIMAL(12, 2) DEFAULT 0,
    `shipping_cost` DECIMAL(12, 2) DEFAULT 0,
    `total` DECIMAL(12, 2) NOT NULL,
    `currency_code` VARCHAR(3) DEFAULT 'USD',

    -- Status
    `status` ENUM(
        'pending',
        'confirmed',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
        'refunded',
        'on_hold'
    ) DEFAULT 'pending',

    -- Payment
    `payment_method_id` INT,
    `payment_status` ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    `paid_at` TIMESTAMP NULL,

    -- Shipping
    `shipping_method_id` INT,
    `shipped_at` TIMESTAMP NULL,
    `delivered_at` TIMESTAMP NULL,

    -- Notes
    `customer_notes` TEXT,
    `admin_notes` TEXT,

    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shipping_address_id`) REFERENCES `customer_addresses`(`id`),
    FOREIGN KEY (`billing_address_id`) REFERENCES `customer_addresses`(`id`),
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`),
    FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods`(`id`),

    UNIQUE KEY `unique_order_number` (`order_number`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_payment_status` (`payment_status`),
    INDEX `idx_order_number` (`order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 5: ORDER ITEMS
-- Individual items in each order
-- ============================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `product_author` VARCHAR(100),
    `product_isbn` VARCHAR(20),
    `quantity` INT NOT NULL DEFAULT 1,
    `price_at_purchase` DECIMAL(12, 2) NOT NULL,
    `currency_code` VARCHAR(3) DEFAULT 'USD',
    `line_total` DECIMAL(12, 2) NOT NULL,
    `is_digital` BOOLEAN DEFAULT FALSE,
    `digital_delivery_sent_at` TIMESTAMP NULL,

    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
    INDEX `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 6: DIGITAL DOWNLOADS
-- Track digital product downloads
-- ============================================
CREATE TABLE IF NOT EXISTS `digital_downloads` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `order_item_id` INT NOT NULL,
    `customer_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `download_token` VARCHAR(100) NOT NULL UNIQUE,
    `file_path` VARCHAR(500) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_size_bytes` BIGINT,
    `file_type` VARCHAR(50),

    -- Download Tracking
    `download_count` INT DEFAULT 0,
    `max_downloads` INT DEFAULT 999,
    `last_downloaded_at` TIMESTAMP NULL,

    -- Expiration
    `available_from` TIMESTAMP,
    `available_until` TIMESTAMP,
    `is_available` BOOLEAN DEFAULT TRUE,

    -- Metadata
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),

    UNIQUE KEY `unique_download_token` (`download_token`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_is_available` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 7: CHECKOUT SESSIONS
-- Track checkout flow progress
-- ============================================
CREATE TABLE IF NOT EXISTS `checkout_sessions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `session_id` VARCHAR(255) NOT NULL UNIQUE,
    `customer_id` INT,
    `cart_id` INT,

    -- Checkout Progress
    `current_step` ENUM('cart', 'address', 'shipping', 'payment', 'review', 'complete') DEFAULT 'cart',
    `step_1_complete` BOOLEAN DEFAULT FALSE,
    `step_2_complete` BOOLEAN DEFAULT FALSE,
    `step_3_complete` BOOLEAN DEFAULT FALSE,

    -- Selected Options
    `shipping_address_id` INT,
    `billing_address_id` INT,
    `shipping_method_id` INT,
    `payment_method_id` INT,

    -- Session Data
    `data` JSON,
    `last_activity_at` TIMESTAMP,
    `expires_at` TIMESTAMP,
    `abandoned_at` TIMESTAMP NULL,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`cart_id`) REFERENCES `shopping_carts`(`id`) ON DELETE CASCADE,

    UNIQUE KEY `unique_session_id` (`session_id`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 8: COUPON CODES (For discount support)
-- ============================================
CREATE TABLE IF NOT EXISTS `coupon_codes` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT,
    `discount_type` ENUM('percentage', 'fixed_amount') DEFAULT 'percentage',
    `discount_value` DECIMAL(10, 2) NOT NULL,
    `min_order_amount` DECIMAL(12, 2),
    `max_discount_amount` DECIMAL(12, 2),
    `usage_limit` INT,
    `usage_count` INT DEFAULT 0,
    `applicable_products` JSON,
    `applicable_categories` JSON,
    `usage_per_customer_limit` INT DEFAULT 1,
    `is_active` BOOLEAN DEFAULT TRUE,
    `valid_from` DATE,
    `valid_to` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_code` (`code`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Alter orders table to add missing columns
-- ============================================

ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `user_id` INT AFTER `customer_id`;

ALTER TABLE `orders`
ADD FOREIGN KEY IF NOT EXISTS `fk_orders_user` (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;
