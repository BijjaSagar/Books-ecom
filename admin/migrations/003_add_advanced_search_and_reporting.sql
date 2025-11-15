-- ============================================
-- Advanced Search, Reporting, and PWA Features
-- ============================================

-- Saved Searches Table
CREATE TABLE IF NOT EXISTS `saved_searches` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `filter_name` varchar(255) NOT NULL,
  `filters` longtext,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_filter` (`user_id`, `filter_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Payment Transactions Table
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` int(11),
  `payment_gateway` varchar(50),
  `gateway_transaction_id` varchar(255),
  `amount` decimal(10,2),
  `currency` varchar(3) DEFAULT 'USD',
  `status` enum('pending','completed','failed','refunded','cancelled') DEFAULT 'pending',
  `payment_method` varchar(100),
  `card_last4` varchar(4),
  `customer_email` varchar(255),
  `response_code` varchar(50),
  `response_message` text,
  `fee` decimal(10,2) DEFAULT 0,
  `net_amount` decimal(10,2),
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
  KEY `status_date` (`status`, `created_at`),
  KEY `gateway_id` (`payment_gateway`, `gateway_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Audit Log Table
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11),
  `action` varchar(255),
  `details` text,
  `ip_address` varchar(45),
  `timestamp` timestamp DEFAULT current_timestamp(),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  KEY `action_date` (`action`, `timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Real-time Events Table (for WebSocket)
CREATE TABLE IF NOT EXISTS `realtime_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `event_type` varchar(100),
  `event_data` longtext,
  `status` enum('pending','processed','expired') DEFAULT 'pending',
  `created_at` timestamp DEFAULT current_timestamp(),
  KEY `status_type` (`status`, `event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- PWA Service Worker Cache Table
CREATE TABLE IF NOT EXISTS `cache_manifest` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `resource_path` varchar(255) UNIQUE,
  `file_hash` varchar(64),
  `version` varchar(50),
  `created_at` timestamp DEFAULT current_timestamp(),
  KEY `version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- WebSocket Connections (temporary)
CREATE TABLE IF NOT EXISTS `websocket_connections` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11),
  `session_id` varchar(255),
  `connected_at` timestamp DEFAULT current_timestamp(),
  `last_ping` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` int(1) DEFAULT 1,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  KEY `active_connections` (`is_active`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Paypal Integration Data
CREATE TABLE IF NOT EXISTS `paypal_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `transaction_id` varchar(255) UNIQUE,
  `order_id` int(11),
  `amount` decimal(10,2),
  `status` varchar(50),
  `payer_email` varchar(255),
  `created_at` timestamp DEFAULT current_timestamp(),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Razorpay Integration Data
CREATE TABLE IF NOT EXISTS `razorpay_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `payment_id` varchar(255) UNIQUE,
  `order_id` int(11),
  `razorpay_order_id` varchar(255),
  `amount` decimal(10,2),
  `status` varchar(50),
  `customer_email` varchar(255),
  `notes` text,
  `created_at` timestamp DEFAULT current_timestamp(),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dashboard Widget Preferences
CREATE TABLE IF NOT EXISTS `widget_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `widget_name` varchar(100),
  `position` int(11),
  `is_visible` int(1) DEFAULT 1,
  `size` varchar(20),
  `refresh_interval` int(11) DEFAULT 300,
  `created_at` timestamp DEFAULT current_timestamp(),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_widget` (`user_id`, `widget_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Performance Optimization Indexes
CREATE INDEX idx_saved_search_user ON saved_searches(user_id);
CREATE INDEX idx_payment_status ON payment_transactions(status);
CREATE INDEX idx_audit_action ON audit_log(action);
CREATE INDEX idx_realtime_event ON realtime_events(event_type, status);
CREATE INDEX idx_paypal_order ON paypal_transactions(order_id);
CREATE INDEX idx_razorpay_order ON razorpay_transactions(order_id);
