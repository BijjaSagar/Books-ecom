-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 28, 2025 at 01:52 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u618910819_bookshelf_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`u618910819_bookshelf_db`@`127.0.0.1` PROCEDURE `RebuildCategoryMenus` ()   BEGIN
    -- Clear existing category menu items
    DELETE FROM menu_items WHERE is_category_link = 1;
    
    -- Rebuild categories dropdown (menu_id = 2)
    INSERT INTO menu_items (menu_id, parent_id, title, url, icon_class, is_category_link, category_id, sort_order, status) 
    SELECT 
        2 as menu_id,
        NULL as parent_id,
        name as title,
        CONCAT('/bookshelf/shop.php?category=', id) as url,
        'bi bi-book' as icon_class,
        1 as is_category_link,
        id as category_id,
        sort_order,
        'active' as status
    FROM categories 
    WHERE status = 'active'
    ORDER BY sort_order, name;
    
    -- Rebuild categories submenu in main menu (parent_id = 3)
    INSERT INTO menu_items (menu_id, parent_id, title, url, icon_class, is_category_link, category_id, sort_order, status) 
    SELECT 
        1 as menu_id,
        3 as parent_id,
        name as title,
        CONCAT('/bookshelf/shop.php?category=', id) as url,
        'bi bi-book' as icon_class,
        1 as is_category_link,
        id as category_id,
        sort_order,
        'active' as status
    FROM categories 
    WHERE status = 'active'
    ORDER BY sort_order, name
    LIMIT 8;
    
    -- Clear menu cache
    DELETE FROM menu_cache;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image_url`, `parent_id`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Fiction', 'fiction', 'Fictional books and novels', NULL, NULL, 'active', 1, '2025-08-03 07:50:01', '2025-08-03 07:50:01'),
(2, 'Non-Fiction', 'non-fiction', 'Non-fictional books and educational content', NULL, NULL, 'active', 2, '2025-08-03 07:50:01', '2025-08-03 07:50:01'),
(3, 'Romance', 'romance', 'Romantic novels and love stories', NULL, NULL, 'active', 3, '2025-08-03 07:50:01', '2025-08-03 07:50:01'),
(4, 'Mystery & Thriller', 'mystery-thriller', 'Mystery, thriller, and suspense books', NULL, NULL, 'active', 4, '2025-08-03 07:50:01', '2025-08-03 07:50:01'),
(5, 'Science Fiction', 'science-fiction', 'Science fiction and futuristic novels', NULL, NULL, 'active', 5, '2025-08-03 07:50:01', '2025-08-03 07:50:01'),
(6, 'Biography', 'biography', 'Biographies and autobiographies', NULL, NULL, 'active', 6, '2025-08-03 07:50:01', '2025-08-03 07:50:01'),
(7, 'Children\'s Books', 'childrens-books', 'Books for children and young readers', NULL, NULL, 'active', 7, '2025-08-03 07:50:01', '2025-08-03 07:50:01'),
(8, 'Business', 'business', 'Business and entrepreneurship books', NULL, NULL, 'active', 8, '2025-08-03 07:50:01', '2025-08-03 07:50:01'),
(9, 'Self-Help', 'self-help', 'Self-improvement and motivational books', NULL, NULL, 'active', 9, '2025-08-03 07:50:01', '2025-08-03 07:50:01'),
(10, 'History', 'history', 'Historical books and documentaries', NULL, NULL, 'active', 10, '2025-08-03 07:50:01', '2025-08-03 07:50:01'),
(11, 'Mystery', 'mystery', 'Mystery and thriller books', NULL, NULL, 'active', 4, '2025-08-03 08:11:04', '2025-08-06 11:53:09'),
(12, 'Children', 'children', 'Books for children and young readers', NULL, NULL, 'active', 7, '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(14, 'Anthropology', '', 'The study of human beings in their biological, social and cultural aspects.', NULL, NULL, 'active', 0, '2025-09-15 10:44:00', '2025-09-15 10:44:00');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT 0.00,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `valid_from` date NOT NULL,
  `valid_until` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_cache`
--

CREATE TABLE `menu_cache` (
  `id` int(11) NOT NULL,
  `cache_key` varchar(100) NOT NULL,
  `cached_html` longtext NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `target` enum('_self','_blank','_parent','_top') DEFAULT '_self',
  `icon_class` varchar(100) DEFAULT NULL,
  `css_class` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_category_link` tinyint(1) DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `visibility` enum('public','logged_in','logged_out','admin') DEFAULT 'public',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `menu_id`, `parent_id`, `title`, `url`, `target`, `icon_class`, `css_class`, `description`, `is_category_link`, `category_id`, `sort_order`, `status`, `visibility`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Home', '/bookshelf/', '_self', 'bi bi-house', NULL, NULL, 0, NULL, 1, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(2, 1, NULL, 'Shop', '/bookshelf/shop.php', '_self', 'bi bi-shop', NULL, NULL, 0, NULL, 2, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(3, 1, NULL, 'Categories', '#', '_self', 'bi bi-grid', NULL, NULL, 0, NULL, 3, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(4, 1, NULL, 'New Arrivals', '/bookshelf/shop.php?sort=newest', '_self', 'bi bi-star', NULL, NULL, 0, NULL, 4, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(5, 1, NULL, 'Bestsellers', '/bookshelf/shop.php?sort=bestseller', '_self', 'bi bi-trophy', NULL, NULL, 0, NULL, 5, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(6, 1, NULL, 'Contact', '/bookshelf/contact.php', '_self', 'bi bi-envelope', NULL, NULL, 0, NULL, 6, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(7, 2, NULL, 'Fiction', '/bookshelf/shop.php?category=1', '_self', 'bi bi-book', NULL, NULL, 1, 1, 1, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(8, 2, NULL, 'Non-Fiction', '/bookshelf/shop.php?category=2', '_self', 'bi bi-book', NULL, NULL, 1, 2, 2, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(9, 2, NULL, 'Romance', '/bookshelf/shop.php?category=3', '_self', 'bi bi-book', NULL, NULL, 1, 3, 3, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(10, 2, NULL, 'Mystery & Thriller', '/bookshelf/shop.php?category=4', '_self', 'bi bi-book', NULL, NULL, 1, 4, 4, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(11, 2, NULL, 'Science Fiction', '/bookshelf/shop.php?category=5', '_self', 'bi bi-book', NULL, NULL, 1, 5, 5, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(12, 2, NULL, 'Biography', '/bookshelf/shop.php?category=6', '_self', 'bi bi-book', NULL, NULL, 1, 6, 6, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(13, 2, NULL, 'Children\'s Books', '/bookshelf/shop.php?category=7', '_self', 'bi bi-book', NULL, NULL, 1, 7, 7, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(14, 2, NULL, 'Business', '/bookshelf/shop.php?category=8', '_self', 'bi bi-book', NULL, NULL, 1, 8, 8, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(15, 2, NULL, 'Self-Help', '/bookshelf/shop.php?category=9', '_self', 'bi bi-book', NULL, NULL, 1, 9, 9, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(16, 2, NULL, 'History', '/bookshelf/shop.php?category=10', '_self', 'bi bi-book', NULL, NULL, 1, 10, 10, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(22, 1, 3, 'Fiction', '/bookshelf/shop.php?category=1', '_self', 'bi bi-book', NULL, NULL, 1, 1, 1, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(23, 1, 3, 'Non-Fiction', '/bookshelf/shop.php?category=2', '_self', 'bi bi-book', NULL, NULL, 1, 2, 2, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(24, 1, 3, 'Romance', '/bookshelf/shop.php?category=3', '_self', 'bi bi-book', NULL, NULL, 1, 3, 3, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(25, 1, 3, 'Mystery & Thriller', '/bookshelf/shop.php?category=4', '_self', 'bi bi-book', NULL, NULL, 1, 4, 4, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(26, 1, 3, 'Science Fiction', '/bookshelf/shop.php?category=5', '_self', 'bi bi-book', NULL, NULL, 1, 5, 5, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(27, 1, 3, 'Biography', '/bookshelf/shop.php?category=6', '_self', 'bi bi-book', NULL, NULL, 1, 6, 6, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(29, 3, NULL, 'About Us', '/bookshelf/about.php', '_self', NULL, NULL, NULL, 0, NULL, 1, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(30, 3, NULL, 'Privacy Policy', '/bookshelf/privacy.php', '_self', NULL, NULL, NULL, 0, NULL, 2, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(31, 3, NULL, 'Terms & Conditions', '/bookshelf/terms.php', '_self', NULL, NULL, NULL, 0, NULL, 3, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(32, 3, NULL, 'Contact', '/bookshelf/contact.php', '_self', NULL, NULL, NULL, 0, NULL, 4, 'active', 'public', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(33, 1, NULL, 'Home', '/bookshelf/', '_self', 'bi bi-house', NULL, NULL, 0, NULL, 1, 'active', 'public', '2025-08-03 18:12:25', '2025-08-03 18:12:25'),
(34, 1, NULL, 'Shop', '/bookshelf/shop.php', '_self', 'bi bi-shop', NULL, NULL, 0, NULL, 2, 'active', 'public', '2025-08-03 18:12:25', '2025-08-03 18:12:25'),
(35, 1, NULL, 'Categories', '#', '_self', 'bi bi-grid', NULL, NULL, 0, NULL, 3, 'active', 'public', '2025-08-03 18:12:25', '2025-08-03 18:12:25'),
(36, 1, NULL, 'About', '/bookshelf/about.php', '_self', 'bi bi-info-circle', NULL, NULL, 0, NULL, 4, 'active', 'public', '2025-08-03 18:12:25', '2025-08-03 18:12:25'),
(37, 1, NULL, 'Contact', '/bookshelf/contact.php', '_self', 'bi bi-envelope', NULL, NULL, 0, NULL, 5, 'active', 'public', '2025-08-03 18:12:25', '2025-08-03 18:12:25'),
(38, 1, NULL, 'Home', '/bookshelf/', '_self', 'bi bi-house', NULL, NULL, 0, NULL, 1, 'active', 'public', '2025-08-03 18:12:52', '2025-08-03 18:12:52'),
(39, 1, NULL, 'Shop', '/bookshelf/shop.php', '_self', 'bi bi-shop', NULL, NULL, 0, NULL, 2, 'active', 'public', '2025-08-03 18:12:52', '2025-08-03 18:12:52'),
(40, 1, NULL, 'Categories', '#', '_self', 'bi bi-grid', NULL, NULL, 0, NULL, 3, 'active', 'public', '2025-08-03 18:12:52', '2025-08-03 18:12:52'),
(41, 1, NULL, 'About', '/bookshelf/about.php', '_self', 'bi bi-info-circle', NULL, NULL, 0, NULL, 4, 'active', 'public', '2025-08-03 18:12:52', '2025-08-03 18:12:52'),
(42, 1, NULL, 'Contact', '/bookshelf/contact.php', '_self', 'bi bi-envelope', NULL, NULL, 0, NULL, 5, 'active', 'public', '2025-08-03 18:12:52', '2025-08-03 18:12:52'),
(43, 1, NULL, 'Home', '/bookshelf/', '_self', 'bi bi-house', NULL, NULL, 0, NULL, 1, 'active', 'public', '2025-08-03 18:13:03', '2025-08-03 18:13:03'),
(44, 1, NULL, 'Shop', '/bookshelf/shop.php', '_self', 'bi bi-shop', NULL, NULL, 0, NULL, 2, 'active', 'public', '2025-08-03 18:13:03', '2025-08-03 18:13:03'),
(45, 1, NULL, 'Categories', '#', '_self', 'bi bi-grid', NULL, NULL, 0, NULL, 3, 'active', 'public', '2025-08-03 18:13:03', '2025-08-03 18:13:03'),
(46, 1, NULL, 'About', '/bookshelf/about.php', '_self', 'bi bi-info-circle', NULL, NULL, 0, NULL, 4, 'active', 'public', '2025-08-03 18:13:03', '2025-08-03 18:13:03'),
(47, 1, NULL, 'Contact', '/bookshelf/contact.php', '_self', 'bi bi-envelope', NULL, NULL, 0, NULL, 5, 'active', 'public', '2025-08-03 18:13:03', '2025-08-03 18:13:03'),
(48, 1, NULL, 'Home', '/bookshelf/', '_self', 'bi bi-house', NULL, NULL, 0, NULL, 1, 'active', 'public', '2025-08-03 18:13:23', '2025-08-03 18:13:23'),
(49, 1, NULL, 'Shop', '/bookshelf/shop.php', '_self', 'bi bi-shop', NULL, NULL, 0, NULL, 2, 'active', 'public', '2025-08-03 18:13:23', '2025-08-03 18:13:23'),
(50, 1, NULL, 'Categories', '#', '_self', 'bi bi-grid', NULL, NULL, 0, NULL, 3, 'active', 'public', '2025-08-03 18:13:23', '2025-08-03 18:13:23'),
(51, 1, NULL, 'About', '/bookshelf/about.php', '_self', 'bi bi-info-circle', NULL, NULL, 0, NULL, 4, 'active', 'public', '2025-08-03 18:13:23', '2025-08-03 18:13:23'),
(52, 1, NULL, 'Contact', '/bookshelf/contact.php', '_self', 'bi bi-envelope', NULL, NULL, 0, NULL, 5, 'active', 'public', '2025-08-03 18:13:23', '2025-08-03 18:13:23');

-- --------------------------------------------------------

--
-- Table structure for table `navigation_menus`
--

CREATE TABLE `navigation_menus` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` enum('header','footer','mobile','admin') DEFAULT 'header',
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `navigation_menus`
--

INSERT INTO `navigation_menus` (`id`, `name`, `location`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Main Header Menu', 'header', 'Primary navigation menu displayed in header', 'active', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(2, 'Categories Dropdown', 'header', 'Categories dropdown menu', 'active', '2025-08-03 08:11:04', '2025-08-03 08:11:04'),
(3, 'Footer Menu', 'footer', 'Footer navigation links', 'active', '2025-08-03 08:11:04', '2025-08-03 08:11:04');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `order_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `order_status` enum('pending','processing','shipped','delivered','completed','cancelled') DEFAULT 'pending',
  `customer_email` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `shipping_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `total_amount`, `status`, `payment_method`, `payment_status`, `shipping_address`, `order_notes`, `created_at`, `updated_at`, `order_status`, `customer_email`, `first_name`, `last_name`, `subtotal`, `tax_amount`, `shipping_amount`) VALUES
(2, NULL, 1, 35.78, 'pending', NULL, '', '', NULL, '2025-07-20 18:25:31', '2025-08-03 18:25:31', 'completed', 'john@example.com', 'John', 'Doe', 27.98, 2.80, 5.00),
(3, NULL, 3, 25.89, 'pending', NULL, 'pending', '', NULL, '2025-07-18 18:25:31', '2025-08-03 18:25:31', 'pending', 'jane@example.com', 'Jane', 'Smith', 18.99, 1.90, 5.00),
(4, NULL, 2, 13.19, 'pending', NULL, '', '', NULL, '2025-07-13 18:25:31', '2025-08-03 18:25:31', 'completed', 'bob@example.com', 'Bob', 'Johnson', 11.99, 1.20, 0.00),
(5, NULL, 3, 21.49, 'pending', NULL, '', '', NULL, '2025-07-24 18:25:31', '2025-08-03 18:25:31', 'delivered', 'alice@example.com', 'Alice', 'Wilson', 14.99, 1.50, 5.00),
(6, NULL, 2, 15.39, 'pending', NULL, '', '', NULL, '2025-07-21 18:25:31', '2025-08-03 18:25:31', 'completed', 'mike@example.com', 'Mike', 'Brown', 13.99, 1.40, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_title` varchar(255) DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `isbn_10` varchar(10) DEFAULT NULL,
  `isbn_13` varchar(13) DEFAULT NULL,
  `description` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `product_type` enum('physical','digital','affiliate','both') DEFAULT 'physical',
  `featured` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive','draft','out_of_stock','discontinued') DEFAULT 'active',
  `cover_image` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `additional_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_images`)),
  `digital_file_path` varchar(255) DEFAULT NULL,
  `affiliate_link` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `reviews_count` int(11) DEFAULT 0,
  `sales_count` int(11) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sku` varchar(100) DEFAULT NULL,
  `ean` varchar(13) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `language` varchar(100) DEFAULT 'English',
  `pages` int(11) DEFAULT NULL,
  `binding_type` enum('Hardcover','Paperback','eBook','Audiobook','Board Book','Mass Market Paperback') DEFAULT 'Paperback',
  `dimensions` varchar(100) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `edition` varchar(100) DEFAULT NULL,
  `series` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `title`, `author`, `isbn_10`, `isbn_13`, `description`, `category_id`, `price`, `original_price`, `product_type`, `featured`, `status`, `cover_image`, `image_url`, `additional_images`, `digital_file_path`, `affiliate_link`, `rating`, `reviews_count`, `sales_count`, `meta_title`, `meta_description`, `stock_quantity`, `created_at`, `updated_at`, `sku`, `ean`, `publisher`, `publication_date`, `language`, `pages`, `binding_type`, `dimensions`, `weight`, `edition`, `series`) VALUES
(1, 'The Pragmatic Programmer', 'Andy Hunt & Dave Thomas', NULL, NULL, 'A classic book about software craftsmanship.', 1, 45.50, NULL, 'physical', 1, 'active', NULL, 'https://placehold.co/300x400/e2e8f0/64748b?text=Book', NULL, NULL, NULL, 4.50, 25, 100, NULL, NULL, 50, '2025-06-23 12:13:32', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(2, 'Clean Code', 'Robert C. Martin', NULL, NULL, 'A handbook of agile software craftsmanship.', 1, 39.99, NULL, 'physical', 1, 'active', NULL, 'https://placehold.co/300x400/e2e8f0/64748b?text=Book', NULL, NULL, NULL, 4.50, 25, 100, NULL, NULL, 35, '2025-06-23 12:13:32', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(3, 'Klara and the Sun', 'Kazuo Ishiguro', NULL, NULL, 'A magnificent new novel from the Nobel laureate Kazuo Ishiguro—author of Never Let Me Go and the Booker Prize-winning The Remains of the Day.', 1, 28.95, NULL, 'physical', 1, 'active', 'klara_and_the_sun.jpg', 'klara_and_the_sun.jpg', NULL, NULL, NULL, 4.50, 25, 100, NULL, NULL, 40, '2025-06-23 16:00:57', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(4, 'Project Hail Mary', 'Andy Weir', NULL, NULL, 'A lone astronaut. An impossible mission. An ally he never imagined. From the author of The Martian, a new favorite of 2021.', 1, 27.50, NULL, 'physical', 1, 'active', 'project_hail_mary.jpg', 'project_hail_mary.jpg', NULL, NULL, NULL, 4.50, 25, 100, NULL, NULL, 60, '2025-06-23 16:00:57', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(5, 'The Four Winds', 'Kristin Hannah', NULL, NULL, 'An epic novel of love and heroism and hope, set during the Great Depression, a time when the country was in crisis and the land was failing.', 1, 30.00, NULL, 'physical', 1, 'active', 'the_four_winds.jpg', 'the_four_winds.jpg', NULL, NULL, NULL, 4.50, 25, 100, NULL, NULL, 25, '2025-06-23 16:00:57', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(6, 'The Midnight Library', 'Matt Haig', NULL, NULL, 'A dazzling novel about all the choices that go into a life well lived, from the internationally bestselling author of Reasons to Stay Alive and How To Stop Time.', 1, 26.00, NULL, 'physical', 1, 'active', 'the_midnight_library.jpg', 'the_midnight_library.jpg', NULL, NULL, NULL, 4.50, 25, 100, NULL, NULL, 75, '2025-06-23 16:00:57', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(7, 'Dune', 'Frank Herbert', NULL, NULL, 'A stunning blend of adventure and mysticism, environmentalism and politics, Dune is a powerful, fantastical tale that has become a science fiction classic.', 1, 18.00, NULL, 'physical', 1, 'active', 'dune.jpg', 'dune.jpg', NULL, NULL, NULL, 4.50, 25, 100, NULL, NULL, 100, '2025-06-23 16:00:57', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(8, 'Circe', 'Madeline Miller', NULL, NULL, 'In the house of Helios, god of the sun and mightiest of the Titans, a daughter is born. But Circe is a strange child—not powerful, like her father, nor viciously alluring like her mother.', 1, 16.99, NULL, 'physical', 1, 'active', 'circe.jpg', 'circe.jpg', NULL, NULL, NULL, 4.50, 25, 100, NULL, NULL, 80, '2025-06-23 16:00:57', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(9, 'Atomic Habits', 'James Clear', NULL, NULL, 'An easy and proven way to build good habits and break bad ones. Tiny Changes, Remarkable Results.', 1, 27.00, NULL, 'physical', 1, 'active', 'atomic_habits.jpg', 'atomic_habits.jpg', NULL, NULL, NULL, 4.50, 25, 100, NULL, NULL, 120, '2025-06-23 16:00:57', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(10, 'Educated: A Memoir', 'Tara Westover', NULL, NULL, 'An unforgettable memoir about a young girl who, kept out of school, leaves her survivalist family and goes on to earn a PhD from Cambridge University.', 1, 18.99, NULL, 'physical', 1, 'active', 'educated.jpg', 'educated.jpg', NULL, NULL, NULL, 4.50, 25, 100, NULL, NULL, 55, '2025-06-23 16:00:57', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(11, 'The Great Gatsby', 'F. Scott Fitzgerald', NULL, NULL, 'A classic American novel about the Jazz Age and the American Dream.', 1, 12.99, 15.99, 'physical', 1, 'active', NULL, 'https://placehold.co/300x400/4f46e5/white?text=The+Great+Gatsby', NULL, NULL, NULL, 4.80, 156, 234, NULL, NULL, 50, '2025-08-03 07:50:02', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(12, 'To Kill a Mockingbird', 'Harper Lee', NULL, NULL, 'A powerful story of racial injustice and childhood innocence in the American South.', 1, 13.99, 16.99, 'physical', 1, 'active', NULL, 'https://placehold.co/300x400/059669/white?text=To+Kill+a+Mockingbird', NULL, NULL, NULL, 4.90, 203, 312, NULL, NULL, 35, '2025-08-03 07:50:02', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(13, 'Pride and Prejudice', 'Jane Austen', NULL, NULL, 'A romantic novel of manners about love, marriage, and social expectations.', 3, 11.99, 14.99, 'physical', 1, 'active', NULL, 'https://placehold.co/300x400/dc2626/white?text=Pride+and+Prejudice', NULL, NULL, NULL, 4.70, 189, 287, NULL, NULL, 42, '2025-08-03 07:50:02', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(14, 'The Catcher in the Rye', 'J.D. Salinger', NULL, NULL, 'A controversial coming-of-age story about teenage rebellion and identity.', 1, 14.99, 17.99, 'physical', 0, 'active', NULL, 'https://placehold.co/300x400/7c3aed/white?text=The+Catcher+in+the+Rye', NULL, NULL, NULL, 4.30, 142, 198, NULL, NULL, 28, '2025-08-03 07:50:02', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(15, '1984', 'George Orwell', NULL, NULL, 'A dystopian novel about totalitarianism, surveillance, and the power of language.', 1, 13.49, 15.99, 'physical', 1, 'active', NULL, 'https://placehold.co/300x400/1f2937/white?text=1984', NULL, NULL, NULL, 4.60, 278, 445, NULL, NULL, 67, '2025-08-03 07:50:02', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(16, 'Dune', 'Frank Herbert', NULL, NULL, 'An epic science fiction novel about politics, religion, and ecology on a desert planet.', 5, 16.99, 19.99, 'physical', 1, 'active', NULL, 'https://placehold.co/300x400/f59e0b/white?text=Dune', NULL, NULL, NULL, 4.50, 167, 253, NULL, NULL, 31, '2025-08-03 07:50:02', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(17, 'The Girl with the Dragon Tattoo', 'Stieg Larsson', NULL, NULL, 'A gripping thriller about murder, corruption, and family secrets in Sweden.', 4, 15.99, 18.99, 'physical', 0, 'active', NULL, 'https://placehold.co/300x400/374151/white?text=Dragon+Tattoo', NULL, NULL, NULL, 4.40, 134, 189, NULL, NULL, 23, '2025-08-03 07:50:02', '2025-08-03 07:50:02', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL),
(18, 'Steve Jobs', 'Walter Isaacson', NULL, NULL, 'The authorized biography of Apple co-founder Steve Jobs.', 6, 18.99, 22.99, 'physical', 1, 'active', '', 'https://placehold.co/300x400/6b7280/white?text=Steve+Jobs', NULL, NULL, NULL, 4.20, 98, 156, NULL, NULL, 20, '2025-08-03 07:50:02', '2025-08-08 08:37:04', NULL, NULL, NULL, NULL, 'English', NULL, 'Paperback', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(255) DEFAULT NULL,
  `review` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_title` varchar(255) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `reviews`
--
DELIMITER $$
CREATE TRIGGER `update_product_rating` AFTER INSERT ON `reviews` FOR EACH ROW BEGIN
    UPDATE products 
    SET rating = (
        SELECT AVG(rating) 
        FROM reviews 
        WHERE product_id = NEW.product_id AND status = 'approved'
    ),
    reviews_count = (
        SELECT COUNT(*) 
        FROM reviews 
        WHERE product_id = NEW.product_id AND status = 'approved'
    )
    WHERE id = NEW.product_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_product_rating_on_update` AFTER UPDATE ON `reviews` FOR EACH ROW BEGIN
    UPDATE products 
    SET rating = (
        SELECT AVG(rating) 
        FROM reviews 
        WHERE product_id = NEW.product_id AND status = 'approved'
    ),
    reviews_count = (
        SELECT COUNT(*) 
        FROM reviews 
        WHERE product_id = NEW.product_id AND status = 'approved'
    )
    WHERE id = NEW.product_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `shopping_cart`
--

CREATE TABLE `shopping_cart` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'BookShelf', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(2, 'site_tagline', 'Your Favorite Online Bookstore', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(3, 'site_email', 'info@bookshelf.com', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(4, 'site_phone', '+1 840-841-2569', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(5, 'site_address', '123 Book Street, Reading City, RC 12345', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(6, 'site_currency', 'USD', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(7, 'timezone', 'America/New_York', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(8, 'primary_color', '#0891b2', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(9, 'secondary_color', '#0e7490', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(10, 'accent_color', '#06b6d4', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(11, 'theme_mode', 'light', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(12, 'facebook_url', '', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(13, 'twitter_url', '', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(14, 'instagram_url', '', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(15, 'linkedin_url', '', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(16, 'youtube_url', '', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(17, 'pinterest_url', '', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(18, 'stripe_public_key', '', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(19, 'stripe_secret_key', '', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(20, 'paypal_client_id', '', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(21, 'paypal_secret', '', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(22, 'google_analytics_id', '', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(23, 'facebook_pixel_id', '', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(24, 'site_logo', 'logo_1754985611_689af48ba7f43.png', '2025-08-12 07:24:41', '2025-08-12 08:00:11'),
(25, 'site_favicon', 'favicon_1754985611_689af48ba8290.png', '2025-08-12 07:24:41', '2025-08-12 08:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT '#',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sliders`
--

INSERT INTO `sliders` (`id`, `title`, `subtitle`, `image_path`, `link_url`, `is_active`, `sort_order`) VALUES
(1, 'Summer Reading Collection', 'Explore hot new titles perfect for a summer day.', 'slide1.png', '#', 1, 0),
(2, 'Up to 30% Off Bestsellers', 'Don\'t miss out on our limited-time offers on popular books.', 'slide2.png', '#', 1, 0),
(3, 'Summer Reading Collection', 'Explore hot new titles perfect for a summer day.', 'slider1.png', '/bookshelf/shop.php?category=1', 1, 0),
(4, 'Up to 30% Off Bestsellers', 'Don\'t miss out on our limited-time offers on popular books.', 'slider2.png', '/bookshelf/shop.php?featured=1', 1, 0),
(5, 'New Arrivals', 'Check out the latest books from your favorite authors.', 'slider3.png', '/bookshelf/shop.php?sort=newest', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `created_at`, `status`) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-06-23 12:13:32', 'active'),
(2, 'Customer User', 'customer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '2025-06-23 12:13:32', 'active'),
(3, 'Sagar bijja', 'sagar.bijja@gmail.com', '$2y$10$Zc/cynpm1iHQD8Qd/WUBp.Xf5uYSX9by9Ejx4cGa0Il0mX0jA4ICe', 'customer', '2025-06-23 16:51:04', 'active'),
(4, 'Admin User', 'admin@bookshelf.com', '$2y$10$REegTnHcYrHqv4LPc3Luf.sCo9RUKLJ8/CdwRoVk8SbLb.UrpdnWm', 'customer', '2025-08-03 18:16:17', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `menu_cache`
--
ALTER TABLE `menu_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cache_key` (`cache_key`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_id` (`menu_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `sort_order` (`sort_order`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_menu_items_menu_parent_status` (`menu_id`,`parent_id`,`status`),
  ADD KEY `idx_menu_items_sort_order` (`sort_order`),
  ADD KEY `idx_menu_items_visibility` (`visibility`);

--
-- Indexes for table `navigation_menus`
--
ALTER TABLE `navigation_menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name_location` (`name`,`location`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_orders_date` (`created_at`),
  ADD KEY `idx_orders_total` (`total_amount`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_products_category_status` (`category_id`,`status`),
  ADD KEY `idx_products_featured_status` (`featured`,`status`),
  ADD KEY `idx_products_rating` (`rating` DESC),
  ADD KEY `idx_products_sales_count` (`sales_count` DESC),
  ADD KEY `idx_products_created_at` (`created_at` DESC),
  ADD KEY `idx_products_price` (`price`),
  ADD KEY `idx_products_author` (`author`),
  ADD KEY `idx_isbn_10` (`isbn_10`),
  ADD KEY `idx_isbn_13` (`isbn_13`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `idx_publisher` (`publisher`),
  ADD KEY `idx_binding_type` (`binding_type`),
  ADD KEY `idx_language` (`language`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`featured`),
  ADD KEY `idx_additional_images` (`additional_images`(768));

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_rating` (`rating`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product_review` (`product_id`,`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_cache`
--
ALTER TABLE `menu_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `navigation_menus`
--
ALTER TABLE `navigation_menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
