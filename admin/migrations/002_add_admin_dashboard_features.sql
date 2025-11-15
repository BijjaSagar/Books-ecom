-- ============================================
-- Admin Dashboard - Amazon Seller Central Style
-- ============================================

-- Sales Analytics Table
CREATE TABLE IF NOT EXISTS `sales_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `analytics_date` date NOT NULL,
  `period_type` enum('daily','weekly','monthly','yearly') DEFAULT 'daily',
  `units_sold` int(11) DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0,
  `average_selling_price` decimal(10,2) DEFAULT 0,
  `net_revenue` decimal(10,2) DEFAULT 0,
  `refunds_count` int(11) DEFAULT 0,
  `returns_count` int(11) DEFAULT 0,
  `page_views` int(11) DEFAULT 0,
  `conversion_rate` decimal(5,2) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  UNIQUE KEY `date_period` (`analytics_date`, `period_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Product Analytics Table
CREATE TABLE IF NOT EXISTS `product_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` int(11) NOT NULL,
  `asin` varchar(10),
  `sku` varchar(100),
  `analytics_date` date NOT NULL,
  `units_sold` int(11) DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0,
  `page_views` int(11) DEFAULT 0,
  `conversion_rate` decimal(5,2) DEFAULT 0,
  `rating` decimal(3,2),
  `review_count` int(11) DEFAULT 0,
  `status` enum('active','inactive','suppressed','archived') DEFAULT 'active',
  `created_at` timestamp DEFAULT current_timestamp(),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `product_date` (`product_id`, `analytics_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inventory Alerts Table
CREATE TABLE IF NOT EXISTS `inventory_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` int(11) NOT NULL,
  `alert_type` enum('low_stock','out_of_stock','overstock','slow_moving') DEFAULT 'low_stock',
  `current_stock` int(11),
  `reorder_level` int(11),
  `threshold` int(11),
  `status` enum('active','resolved','dismissed') DEFAULT 'active',
  `resolved_at` timestamp NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  KEY `product_status` (`product_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inventory Transactions Log
CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` int(11) NOT NULL,
  `transaction_type` enum('purchase','sale','return','adjustment','restock','damage') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(255),
  `reference_id` varchar(100),
  `notes` text,
  `created_at` timestamp DEFAULT current_timestamp(),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  KEY `product_date` (`product_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Advertising Campaigns Table
CREATE TABLE IF NOT EXISTS `advertising_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `campaign_name` varchar(255) NOT NULL,
  `campaign_type` enum('Sponsored Products','Sponsored Brands','Display','Manual CPC') DEFAULT 'Sponsored Products',
  `budget` decimal(10,2),
  `daily_budget` decimal(10,2),
  `spend` decimal(10,2) DEFAULT 0,
  `impressions` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `orders` int(11) DEFAULT 0,
  `sales` decimal(10,2) DEFAULT 0,
  `acos` decimal(5,2) DEFAULT 0,
  `roas` decimal(5,2) DEFAULT 0,
  `cpc` decimal(5,3) DEFAULT 0,
  `status` enum('active','paused','archived','expired') DEFAULT 'active',
  `start_date` date,
  `end_date` date,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  KEY `status_date` (`status`, `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Campaign Keywords & Bids
CREATE TABLE IF NOT EXISTS `campaign_keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` int(11) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `match_type` enum('exact','phrase','broad') DEFAULT 'broad',
  `bid` decimal(5,2) NOT NULL,
  `impressions` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `spend` decimal(10,2) DEFAULT 0,
  `status` enum('active','paused','negative') DEFAULT 'active',
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  FOREIGN KEY (`campaign_id`) REFERENCES `advertising_campaigns`(`id`) ON DELETE CASCADE,
  KEY `campaign_status` (`campaign_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Financial Reports Table
CREATE TABLE IF NOT EXISTS `financial_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `report_date` date NOT NULL,
  `period_type` enum('daily','weekly','monthly','yearly') DEFAULT 'daily',
  `total_revenue` decimal(10,2) DEFAULT 0,
  `total_cogs` decimal(10,2) DEFAULT 0,
  `referral_fees` decimal(10,2) DEFAULT 0,
  `fulfillment_fees` decimal(10,2) DEFAULT 0,
  `storage_fees` decimal(10,2) DEFAULT 0,
  `subscription_fees` decimal(10,2) DEFAULT 0,
  `advertising_spend` decimal(10,2) DEFAULT 0,
  `shipping_costs` decimal(10,2) DEFAULT 0,
  `returns_refunds` decimal(10,2) DEFAULT 0,
  `other_expenses` decimal(10,2) DEFAULT 0,
  `total_expenses` decimal(10,2) DEFAULT 0,
  `gross_profit` decimal(10,2) DEFAULT 0,
  `net_profit` decimal(10,2) DEFAULT 0,
  `profit_margin` decimal(5,2) DEFAULT 0,
  `roi` decimal(5,2) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  UNIQUE KEY `date_period` (`report_date`, `period_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Category Performance Table
CREATE TABLE IF NOT EXISTS `category_performance` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `category_id` int(11) NOT NULL,
  `analytics_date` date NOT NULL,
  `units_sold` int(11) DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0,
  `average_price` decimal(10,2) DEFAULT 0,
  `profit` decimal(10,2) DEFAULT 0,
  `product_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `category_date` (`category_id`, `analytics_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Account Health Score Table
CREATE TABLE IF NOT EXISTS `account_health` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `check_date` date NOT NULL,
  `health_score` decimal(5,2) DEFAULT 100,
  `seller_rating` decimal(3,2) DEFAULT 0,
  `negative_feedback_count` int(11) DEFAULT 0,
  `policy_violations` int(11) DEFAULT 0,
  `return_defect_rate` decimal(5,2) DEFAULT 0,
  `late_shipment_rate` decimal(5,2) DEFAULT 0,
  `cancellation_rate` decimal(5,2) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  UNIQUE KEY `check_date` (`check_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Bulk Upload History Table
CREATE TABLE IF NOT EXISTS `bulk_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `upload_type` enum('products','inventory','prices','listings') DEFAULT 'products',
  `filename` varchar(255) NOT NULL,
  `total_records` int(11),
  `successful_records` int(11) DEFAULT 0,
  `failed_records` int(11) DEFAULT 0,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `error_log` longtext,
  `created_by` int(11),
  `created_at` timestamp DEFAULT current_timestamp(),
  `completed_at` timestamp NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Listing Quality Dashboard Table
CREATE TABLE IF NOT EXISTS `listing_quality` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` int(11) NOT NULL,
  `quality_score` int(3) DEFAULT 100,
  `has_image` int(1) DEFAULT 1,
  `has_description` int(1) DEFAULT 1,
  `has_price` int(1) DEFAULT 1,
  `missing_attributes` text,
  `warnings` text,
  `suppression_reason` varchar(255),
  `is_suppressed` int(1) DEFAULT 0,
  `last_checked` timestamp DEFAULT current_timestamp(),
  `created_at` timestamp DEFAULT current_timestamp(),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `product_quality` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Payouts/Settlement Table
CREATE TABLE IF NOT EXISTS `seller_payouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `payout_id` varchar(100) UNIQUE,
  `payout_amount` decimal(10,2) NOT NULL,
  `payout_period_start` date,
  `payout_period_end` date,
  `status` enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
  `transaction_fee` decimal(10,2) DEFAULT 0,
  `net_payout` decimal(10,2),
  `payment_method` varchar(100),
  `paid_at` timestamp NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  KEY `status_date` (`status`, `payout_period_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create Indexes for Performance
CREATE INDEX idx_sales_analytics_date ON sales_analytics(analytics_date);
CREATE INDEX idx_product_analytics_date ON product_analytics(analytics_date, product_id);
CREATE INDEX idx_inventory_alerts_status ON inventory_alerts(status, product_id);
CREATE INDEX idx_campaign_status ON advertising_campaigns(status);
CREATE INDEX idx_financial_report_date ON financial_reports(report_date);
CREATE INDEX idx_category_perf_date ON category_performance(analytics_date);
CREATE INDEX idx_listing_quality_score ON listing_quality(quality_score);
CREATE INDEX idx_inventory_trans_date ON inventory_transactions(created_at, product_id);
