-- ============================================
-- Add Product Keywords and Languages Tables
-- ============================================

-- Add missing columns to products table if they don't exist
ALTER TABLE `products`
ADD COLUMN IF NOT EXISTS `item_type` varchar(100),
ADD COLUMN IF NOT EXISTS `manufacturer` varchar(255),
ADD COLUMN IF NOT EXISTS `edition` varchar(100),
ADD COLUMN IF NOT EXISTS `min_reading_age` int(3),
ADD COLUMN IF NOT EXISTS `max_reading_age` int(3),
ADD COLUMN IF NOT EXISTS `format` varchar(100),
ADD COLUMN IF NOT EXISTS `min_grade` varchar(50),
ADD COLUMN IF NOT EXISTS `max_grade` varchar(50),
ADD COLUMN IF NOT EXISTS `school_type` varchar(100),
ADD COLUMN IF NOT EXISTS `series_title` varchar(255),
ADD COLUMN IF NOT EXISTS `series_number` int(11),
ADD COLUMN IF NOT EXISTS `volume` varchar(100),
ADD COLUMN IF NOT EXISTS `textbook_type` varchar(50);

-- Product Keywords Table
CREATE TABLE IF NOT EXISTS `product_keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` int(11) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  KEY `product_keyword` (`product_id`, `keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Product Languages Table
CREATE TABLE IF NOT EXISTS `product_languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` int(11) NOT NULL,
  `language` varchar(10) NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `product_language` (`product_id`, `language`),
  KEY `language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Language Translations Table (for UI translations)
CREATE TABLE IF NOT EXISTS `language_translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `language_code` varchar(10) NOT NULL,
  `translation_key` varchar(100) NOT NULL,
  `translation_value` longtext NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY `language_key` (`language_code`, `translation_key`),
  KEY `language_code` (`language_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default language codes
INSERT INTO `language_translations` (`language_code`, `translation_key`, `translation_value`) VALUES
('en', 'add_product', 'Add Product'),
('ar', 'add_product', 'إضافة منتج'),
('ur', 'add_product', 'پروڈکٹ شامل کریں'),
('hi', 'add_product', 'उत्पाद जोड़ें'),
('en', 'product_details', 'Product Details'),
('ar', 'product_details', 'تفاصيل المنتج'),
('ur', 'product_details', 'پروڈکٹ کی تفصیلات'),
('hi', 'product_details', 'उत्पाद विवरण');

-- Create indexes for performance
CREATE INDEX idx_product_keywords_product ON product_keywords(product_id);
CREATE INDEX idx_product_languages_product ON product_languages(product_id);
CREATE INDEX idx_language_translations_lang ON language_translations(language_code);
